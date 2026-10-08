<?php
/*+***********************************************************************************
 * Đơn hàng CRM → Đơn đặt hàng (sa_order) trên AMIS (voucher_type 20).
 * Chốt A: bấm Chuyển qua MISA → đơn vào tab Đơn đặt hàng ngay.
 * Kế toán lập chứng từ bán hàng trên MISA khi cần (bước sau, không chặn tạo đơn).
 * Menu Hóa đơn CRM: Chờ kế toán · Kế toán đã duyệt · Kế toán từ chối
 *************************************************************************************/

class Invoice_MisaSyncService {

	const STATUS_WAIT = 'Chờ kế toán';
	const STATUS_OK = 'Kế toán đã duyệt';
	const STATUS_NO = 'Kế toán từ chối';

	public static function install(PearDatabase $adb = null) {
		if (!$adb) {
			$adb = PearDatabase::getInstance();
		}
		$adb->pquery(
			'CREATE TABLE IF NOT EXISTS mk_misa_voucher (
				salesorderid INT NOT NULL,
				invoiceid INT NOT NULL,
				org_refid VARCHAR(64) NOT NULL,
				status VARCHAR(32) NOT NULL,
				message TEXT NULL,
				misa_refno VARCHAR(64) NULL,
				updated_at DATETIME NULL,
				PRIMARY KEY (salesorderid),
				KEY mk_misa_invoice (invoiceid),
				KEY mk_misa_org (org_refid)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8',
			array()
		);
	}

	/**
	 * @param NkApi_Misa_Adapter $api
	 * @param Vtiger_Record_Model $soModel
	 * @return array
	 */
	public static function push($api, $soModel) {
		$adb = PearDatabase::getInstance();
		self::install($adb);
		self::ensureStatuses();

		$soId = (int) $soModel->getId();
		if ($soId <= 0) {
			return array('error' => 'Không có đơn hàng.');
		}
		$orderNo = trim((string) $soModel->get('salesorder_no'));
		if ($orderNo === '') {
			$orderNo = 'SO' . $soId;
		}

		$existing = self::findBySalesOrder($soId);
		if ($existing) {
			$issuedLabel = self::invoiceStatusOf((int) $existing['invoiceid']);
			if ($existing['status'] === 'published' || $issuedLabel === 'Đã phát hành' || $existing['status'] === 'approved') {
				return array(
					'error' => 'Đơn hàng này đã phát hành. Vui lòng kiểm tra hóa đơn.',
					'invoiceid' => (int) $existing['invoiceid'],
				);
			}
		}

		$lines = self::linesFor($soId);
		if (!$lines) {
			return array('error' => 'Đơn ' . $orderNo . ' chưa có dòng hàng để gửi Đơn đặt hàng.');
		}

		$orgRefid = $existing ? $existing['org_refid'] : self::guidFrom('so-' . $soId);
		$built = self::buildVoucher($api, $soModel, $lines, $orgRefid, $orderNo);
		$api->saveVoucher($built['voucher'], $built['dictionary']);

		$invoiceId = $existing ? (int) $existing['invoiceid'] : self::createInvoice($soModel, $orderNo);
		self::setInvoiceStatus($invoiceId, self::STATUS_WAIT);
		self::saveLink($soId, $invoiceId, $orgRefid, 'pending', 'Đã gửi Đơn đặt hàng sang MISA (tab Đơn đặt hàng).', '');

		$invoiceNo = self::invoiceNo($invoiceId);
		return array(
			'success' => true,
			'message' => 'Đã gửi Đơn đặt hàng ' . ($invoiceNo !== '' ? $invoiceNo : ('#' . $invoiceId))
				. ' sang MISA. Kiểm tra AMIS → Bán hàng → Đơn đặt hàng. Trạng thái CRM: Chờ kế toán.',
			'invoiceid' => $invoiceId,
			'misa_status' => self::STATUS_WAIT,
			'misa_refno' => '',
			'misa_state' => 'pending',
		);
	}

	public static function refreshPending($api = null) {
		try {
			$adb = PearDatabase::getInstance();
			self::install($adb);
			if (!$api) {
				require_once 'modules/Vtiger/helpers/NkApiConnection.php';
				$api = NkApiConnection::adapter('misa');
			}
			if (!$api || !method_exists($api, 'prepareConnection')) {
				return;
			}
			$api->prepareConnection();
			if (!$api->isEnabled() || $api->accessCode() === '') {
				return;
			}
			$res = $adb->pquery(
				"SELECT salesorderid FROM mk_misa_voucher WHERE status IN ('pending','active') LIMIT 1",
				array()
			);
			if (!$res || $adb->num_rows($res) < 1) {
				return;
			}
			$row = NkApiConnection::getRow('misa');
			$extra = isset($row['extra']) && is_array($row['extra']) ? $row['extra'] : array();
			$last = isset($extra['last_status_poll']) ? (int) $extra['last_status_poll'] : 0;
			if ($last > time() - 45) {
				return;
			}
			$extra['last_status_poll'] = time();
			NkApiConnection::saveRow('misa', array('extra' => $extra), 0);

			$items = $api->fetchCallbackResults(date('Y-m-d', time() - 30 * 86400), date('Y-m-d'));
			foreach ($items as $item) {
				if (is_array($item)) {
					self::applyResult($item);
				}
			}
		} catch (Exception $e) {
			// Danh sách hóa đơn vẫn mở được khi MISA không trả trạng thái.
		}
	}

	/**
	 * Payload callback MISA gọi vào CRM.
	 * @param array $payload
	 * @return array
	 */
	public static function applyCallback(array $payload) {
		$adb = PearDatabase::getInstance();
		self::install($adb);
		require_once 'modules/Vtiger/helpers/NkApiConnection.php';
		$api = NkApiConnection::adapter('misa');
		if ($api && method_exists($api, 'prepareConnection')) {
			$api->prepareConnection();
		}
		$appId = isset($payload['app_id']) ? (string) $payload['app_id'] : '';
		if ($api && $appId !== '' && $appId !== $api->appId()) {
			return array('Success' => false, 'ErrorMessage' => 'Sai App ID.');
		}
		if (!self::signatureOk($payload, $api)) {
			return array('Success' => false, 'ErrorMessage' => 'Sai chữ ký.');
		}
		$data = isset($payload['data']) ? $payload['data'] : array();
		if (is_string($data) && $api && method_exists($api, 'decodeData')) {
			$decoded = $api->decodeData($data);
			$data = $decoded ? $decoded : $data;
		}
		self::applyResult($payload);
		if (is_array($data)) {
			self::walkResults($data);
		}
		return array('Success' => true, 'ErrorMessage' => '');
	}

	protected static function signatureOk(array $payload, $api) {
		if (empty($payload['signature']) || !$api) {
			return true;
		}
		$data = isset($payload['data']) ? $payload['data'] : '';
		if (is_array($data)) {
			$data = json_encode($data, JSON_UNESCAPED_UNICODE);
		}
		$given = strtolower(trim((string) $payload['signature']));
		$calc = hash_hmac('sha256', (string) $data, $api->appId());
		return function_exists('hash_equals') ? hash_equals($calc, $given) : ($calc === $given);
	}

	protected static function walkResults($node) {
		if (!is_array($node)) {
			return;
		}
		if (isset($node['org_refid']) || isset($node['success']) || isset($node['Success'])) {
			self::applyResult($node);
		}
		foreach ($node as $child) {
			if (is_array($child)) {
				self::walkResults($child);
			}
		}
	}

	public static function applyResult(array $item) {
		$orgRefid = isset($item['org_refid']) ? trim((string) $item['org_refid']) : '';
		if ($orgRefid === '' && isset($item['voucher']) && is_array($item['voucher'])) {
			foreach ($item['voucher'] as $voucher) {
				if (is_array($voucher)) {
					self::applyResult($voucher);
				}
			}
			return;
		}
		if ($orgRefid === '') {
			return;
		}
		$link = self::findByOrgRefid($orgRefid);
		if (!$link) {
			return;
		}
		if (isset($item['sa_invoice']) && is_array($item['sa_invoice'])) {
			foreach (array('publish_status', 'inv_no', 'is_invoice_deleted', 'is_invoice_cancel', 'refno_finance') as $key) {
				if ((!isset($item[$key]) || $item[$key] === '' || $item[$key] === null) && isset($item['sa_invoice'][$key])) {
					$item[$key] = $item['sa_invoice'][$key];
				}
			}
		}

		$errorCode = isset($item['error_code']) ? (string) $item['error_code'] : (isset($item['ErrorCode']) ? (string) $item['ErrorCode'] : '');
		$errorMessage = isset($item['error_message']) ? (string) $item['error_message'] : (isset($item['ErrorMessage']) ? (string) $item['ErrorMessage'] : '');
		$success = array_key_exists('success', $item) ? !empty($item['success']) : (array_key_exists('Success', $item) ? !empty($item['Success']) : true);

		if ($errorCode === '99' || stripos($errorMessage, 'callback') !== false) {
			return;
		}
		if (!$thisCreated = self::voucherWasCreated($item, $errorCode)) {
			if (!$success && $errorMessage !== '') {
				self::mark((int) $link['salesorderid'], (int) $link['invoiceid'], $orgRefid, 'rejected', self::STATUS_NO, $errorMessage, '');
			}
			return;
		}
		$label = self::misaStatusLabel($item);
		$bucket = self::statusBucket($label);
		self::mark((int) $link['salesorderid'], (int) $link['invoiceid'], $orgRefid, $bucket, $label, $label, self::refnoFrom($item));
	}

	protected static function voucherWasCreated(array $item, $errorCode) {
		if ($errorCode === 'IsCreatedVoucher') {
			return true;
		}
		if (!empty($item['is_created_savoucher'])) {
			return true;
		}
		if (self::refnoFrom($item) !== '') {
			return true;
		}
		if (isset($item['publish_status']) && $item['publish_status'] !== '' && $item['publish_status'] !== null && (int) $item['publish_status'] > 0) {
			return true;
		}
		return false;
	}

	protected static function misaStatusLabel(array $item) {
		if (!empty($item['is_invoice_deleted']) || !empty($item['is_invoice_cancel'])) {
			return 'Hóa đơn đã hủy';
		}
		$publish = null;
		if (isset($item['publish_status']) && $item['publish_status'] !== '' && $item['publish_status'] !== null) {
			$publish = (int) $item['publish_status'];
		}
		if ($publish === 4) {
			return 'Phát hành lỗi';
		}
		if ($publish === 3 || ($publish === null && self::refnoFrom($item) !== '' && !empty($item['inv_no']))) {
			return 'Đã phát hành';
		}
		if ($publish === 2) {
			return 'Đang gửi';
		}
		if ($publish === 1) {
			return 'Đợi gửi';
		}
		return 'Hóa đơn mới';
	}

	protected static function statusBucket($label) {
		if ($label === 'Đã phát hành') {
			return 'published';
		}
		if ($label === 'Hóa đơn đã hủy' || $label === 'Phát hành lỗi' || $label === self::STATUS_NO) {
			return 'rejected';
		}
		return 'active';
	}

	protected static function refnoFrom(array $item) {
		$finance = !empty($item['refno_finance']) ? trim((string) $item['refno_finance']) : '';
		$inv = !empty($item['inv_no']) ? trim((string) $item['inv_no']) : '';
		$parts = array();
		if ($finance !== '') {
			$parts[] = 'Số CT: ' . $finance;
		}
		if ($inv !== '' && $inv !== $finance) {
			$parts[] = 'Số HĐ: ' . $inv;
		}
		if (!$parts && !empty($item['misa_refno'])) {
			return trim((string) $item['misa_refno']);
		}
		return implode(' · ', $parts);
	}

	public static function salesViewForOrder($soId) {
		self::refreshPending();
		$row = self::findBySalesOrder((int) $soId);
		if (!$row) {
			return array('label' => '', 'refno' => '', 'updated' => '', 'state' => '', 'note' => '');
		}
		$state = isset($row['status']) ? (string) $row['status'] : '';
		$label = ($state === 'published')
			? 'Đã phát hành'
			: self::invoiceStatusOf((int) $row['invoiceid']);
		$message = isset($row['message']) ? trim((string) $row['message']) : '';
		if ($label === '' && $message !== '') {
			$label = $message;
		}
		$note = '';
		if ($state === 'rejected' && $message !== '' && $message !== $label) {
			$note = $message;
		}
		return array(
			'label' => $label,
			'refno' => isset($row['misa_refno']) ? (string) $row['misa_refno'] : '',
			'updated' => isset($row['updated_at']) ? (string) $row['updated_at'] : '',
			'state' => $state,
			'note' => $note,
		);
	}

	protected static function mark($soId, $invoiceId, $orgRefid, $status, $invoiceStatus, $message, $refno) {
		self::ensureStatuses();
		self::setInvoiceStatus($invoiceId, $invoiceStatus);
		self::saveLink($soId, $invoiceId, $orgRefid, $status, $message, $refno);
	}

	protected static function createInvoice($soModel, $orderNo) {
		$soId = (int) $soModel->getId();
		$user = Users_Record_Model::getCurrentUserModel();
		$owner = $user ? (int) $user->getId() : (int) $soModel->get('assigned_user_id');
		if ($owner <= 0) {
			$owner = 1;
		}

		$invoiceId = 0;
		$prev = array(
			'action' => isset($_REQUEST['action']) ? $_REQUEST['action'] : '',
			'module' => isset($_REQUEST['module']) ? $_REQUEST['module'] : '',
			'ajxaction' => isset($_REQUEST['ajxaction']) ? $_REQUEST['ajxaction'] : '',
		);
		$_REQUEST['action'] = 'SaveAjax';
		$_REQUEST['module'] = 'Invoice';
		$_REQUEST['ajxaction'] = 'DETAILVIEW';

		try {
			$focus = CRMEntity::getInstance('Invoice');
			$focus->mode = '';
			$copy = array(
				'subject', 'account_id', 'contact_id', 'currency_id', 'conversion_rate',
				'bill_street', 'bill_city', 'bill_state', 'bill_code', 'bill_country', 'bill_pobox',
				'ship_street', 'ship_city', 'ship_state', 'ship_code', 'ship_country', 'ship_pobox',
				'terms_conditions', 'description',
			);
			foreach ($copy as $name) {
				$value = $soModel->get($name);
				if ($value !== null && $value !== '') {
					$focus->column_fields[$name] = $value;
				}
			}
			$subject = trim((string) $soModel->get('subject'));
			$focus->column_fields['subject'] = $subject !== '' ? $subject : ('Hóa đơn ' . $orderNo);
			$focus->column_fields['salesorder_id'] = $soId;
			$focus->column_fields['invoicestatus'] = self::STATUS_WAIT;
			$focus->column_fields['invoicedate'] = date('Y-m-d');
			$due = trim((string) $soModel->get('duedate'));
			$focus->column_fields['duedate'] = $due !== '' ? $due : date('Y-m-d');
			$focus->column_fields['assigned_user_id'] = $owner;
			if (empty($focus->column_fields['currency_id'])) {
				$focus->column_fields['currency_id'] = $soModel->get('currency_id');
			}
			if ($focus->column_fields['currency_id'] === '' || $focus->column_fields['currency_id'] === null) {
				$focus->column_fields['currency_id'] = 1;
			}
			if ($focus->column_fields['conversion_rate'] === '' || $focus->column_fields['conversion_rate'] === null) {
				$focus->column_fields['conversion_rate'] = 1;
			}
			$focus->save('Invoice');
			$invoiceId = (int) $focus->id;
		} finally {
			foreach ($prev as $key => $value) {
				$_REQUEST[$key] = $value;
			}
		}

		if ($invoiceId <= 0) {
			throw new Exception('CRM không tạo được hóa đơn cho đơn ' . $orderNo . '.');
		}
		self::copyLines($soId, $invoiceId);
		self::copyTotals($soModel, $invoiceId);
		return $invoiceId;
	}

	protected static function copyLines($soId, $invoiceId) {
		$adb = PearDatabase::getInstance();
		$adb->pquery('DELETE FROM vtiger_inventoryproductrel WHERE id = ?', array($invoiceId));
		$res = $adb->pquery(
			'SELECT * FROM vtiger_inventoryproductrel WHERE id = ? ORDER BY sequence_no ASC',
			array($soId)
		);
		if (!$res) {
			return;
		}
		$count = $adb->num_rows($res);
		$fields = $adb->getFieldsArray($res);
		$used = array();
		$columns = array();
		foreach ($fields as $field) {
			$key = strtolower((string) $field);
			if ($key === 'lineitem_id' || isset($used[$key])) {
				continue;
			}
			$used[$key] = 1;
			$columns[] = $key;
		}
		for ($i = 0; $i < $count; $i++) {
			$vals = array();
			$names = array();
			foreach ($columns as $column) {
				$names[] = $column;
				$vals[] = ($column === 'id') ? $invoiceId : $adb->query_result($res, $i, $column);
			}
			if (!$names) {
				continue;
			}
			$sql = 'INSERT INTO vtiger_inventoryproductrel (' . implode(',', $names) . ') VALUES (' . generateQuestionMarks($vals) . ')';
			$adb->pquery($sql, $vals);
		}
	}

	protected static function copyTotals($soModel, $invoiceId) {
		$adb = PearDatabase::getInstance();
		$total = self::money($soModel->get('hdnGrandTotal'));
		if ($total == 0.0) {
			$total = self::money($soModel->get('total'));
		}
		$sub = self::money($soModel->get('hdnSubTotal'));
		if ($sub == 0.0) {
			$sub = self::money($soModel->get('subtotal'));
		}
		$adb->pquery(
			'UPDATE vtiger_invoice SET subtotal = ?, total = ?, balance = ?, invoicestatus = ? WHERE invoiceid = ?',
			array($sub, $total, $total, self::STATUS_WAIT, $invoiceId)
		);
	}

	protected static function linesFor($soId) {
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			'SELECT r.sequence_no, r.quantity, r.listprice, r.discount_percent, r.discount_amount, r.comment, r.productid
			 FROM vtiger_inventoryproductrel r
			 WHERE r.id = ?
			 ORDER BY r.sequence_no ASC',
			array($soId)
		);
		$lines = array();
		if (!$res) {
			return $lines;
		}
		$count = $adb->num_rows($res);
		for ($i = 0; $i < $count; $i++) {
			$productId = (int) $adb->query_result($res, $i, 'productid');
			if ($productId <= 0) {
				continue;
			}
			$comment = trim(decode_html($adb->query_result($res, $i, 'comment')));
			$resolved = self::resolveProductLabel($productId, $comment);
			$code = $resolved['code'];
			$name = $resolved['name'];
			$qty = self::money($adb->query_result($res, $i, 'quantity'));
			$price = self::money($adb->query_result($res, $i, 'listprice'));
			$amount = $qty * $price;
			$discPct = self::money($adb->query_result($res, $i, 'discount_percent'));
			$discAmt = self::money($adb->query_result($res, $i, 'discount_amount'));
			if ($discPct > 0) {
				$discAmt = $amount * $discPct / 100;
			}
			$amount = $amount - $discAmt;
			if ($amount < 0) {
				$amount = 0;
			}
			$rate = self::taxRate($soId, $productId, (int) $adb->query_result($res, $i, 'sequence_no'));
			$vat = round($amount * $rate / 100, 2);
			$lines[] = array(
				'seq' => (int) $adb->query_result($res, $i, 'sequence_no'),
				'product_id' => $productId,
				'code' => $code,
				'name' => $name,
				'unit' => $resolved['unit'],
				'qty' => $qty,
				'price' => $price,
				'amount' => round($amount, 2),
				'discount' => round($discAmt, 2),
				'discount_rate' => $discPct,
				'vat_rate' => $rate,
				'vat' => $vat,
				'comment' => $comment,
			);
		}
		return $lines;
	}

	/**
	 * Resolve SKU + display name for MISA (name must not fall back to code when a real name exists).
	 * @return array{code:string,name:string,unit:string}
	 */
	protected static function resolveProductLabel($productId, $comment = '') {
		$adb = PearDatabase::getInstance();
		$name = '';
		$code = '';
		$unit = 'Cái';

		$prod = $adb->pquery('SELECT productname, productcode FROM vtiger_products WHERE productid = ?', array($productId));
		if ($prod && $adb->num_rows($prod) > 0) {
			$name = trim(decode_html($adb->query_result($prod, 0, 'productname')));
			$code = trim(decode_html($adb->query_result($prod, 0, 'productcode')));
		} else {
			$svc = $adb->pquery('SELECT servicename, service_no FROM vtiger_service WHERE serviceid = ?', array($productId));
			if ($svc && $adb->num_rows($svc) > 0) {
				$name = trim(decode_html($adb->query_result($svc, 0, 'servicename')));
				$code = trim(decode_html($adb->query_result($svc, 0, 'service_no')));
			}
		}

		// ProductsServices catalog (sku / productsservicesname) — preferred display name when CRM stores code as productname.
		$psName = '';
		$psSku = '';
		$psUnit = '';
		try {
			$ps = null;
			if ($code !== '') {
				$ps = @$adb->pquery(
					'SELECT productsservicesname, sku, unit FROM vtiger_productsservices WHERE sku = ? LIMIT 1',
					array($code)
				);
			}
			if (!$ps || $adb->num_rows($ps) < 1) {
				$ps = @$adb->pquery(
					'SELECT productsservicesname, sku, unit FROM vtiger_productsservices WHERE productsservicesid = ? LIMIT 1',
					array($productId)
				);
			}
			if ($ps && $adb->num_rows($ps) > 0) {
				$psName = trim(decode_html($adb->query_result($ps, 0, 'productsservicesname')));
				$psSku = trim(decode_html($adb->query_result($ps, 0, 'sku')));
				$psUnit = trim(decode_html($adb->query_result($ps, 0, 'unit')));
			}
		} catch (Exception $e) {
			// Table may not exist on some installs.
		}
		if ($code === '' && $psSku !== '') {
			$code = $psSku;
		}
		if ($psUnit !== '') {
			$unit = $psUnit;
		}

		$comment = trim((string) $comment);
		$nameLooksLikeCode = ($name === '' || ($code !== '' && strcasecmp($name, $code) === 0));
		if ($nameLooksLikeCode && $psName !== '' && strcasecmp($psName, $code) !== 0) {
			$name = $psName;
		}
		if (($name === '' || ($code !== '' && strcasecmp($name, $code) === 0)) && $comment !== '' && strcasecmp($comment, $code) !== 0) {
			$name = $comment;
		}
		if ($code === '') {
			$code = 'SP' . $productId;
		}
		if ($name === '') {
			$name = $psName !== '' ? $psName : ($comment !== '' ? $comment : $code);
		}

		return array(
			'code' => $code,
			'name' => $name,
			'unit' => $unit !== '' ? $unit : 'Cái',
		);
	}

	protected static function taxRate($soId, $productId, $sequence) {
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery(
			'SELECT * FROM vtiger_inventoryproductrel WHERE id = ? AND productid = ? AND sequence_no = ? LIMIT 1',
			array($soId, $productId, $sequence)
		);
		if (!$res || $adb->num_rows($res) < 1) {
			return 0;
		}
		$row = $adb->query_result_rowdata($res, 0);
		$rate = 0;
		$seen = array();
		foreach ($row as $key => $value) {
			if (!is_string($key)) {
				continue;
			}
			$lk = strtolower($key);
			if (isset($seen[$lk]) || !preg_match('/^tax\d+$/', $lk)) {
				continue;
			}
			$seen[$lk] = 1;
			$rate += (float) $value;
		}
		return $rate;
	}

	protected static function buildVoucher($api, $soModel, array $lines, $orgRefid, $orderNo) {
		$soId = (int) $soModel->getId();
		$party = self::party($soModel);
		$partyId = self::guidFrom('party-' . $party['key']);
		$today = date('Y-m-d');
		$now = date('Y-m-d\TH:i:s.000P');
		$sub = 0;
		$vat = 0;
		$discount = 0;
		$details = array();
		$dictionary = array();
		$dictionary[] = array(
			'dictionary_type' => 1,
			'account_object_id' => $partyId,
			'account_object_type' => 0,
			'is_customer' => true,
			'is_vendor' => false,
			'is_employee' => false,
			'inactive' => false,
			'account_object_code' => $party['code'],
			'account_object_name' => $party['name'],
			'address' => $party['address'],
			'company_tax_code' => $party['tax'],
			'country' => 'Việt Nam',
		);

		foreach ($lines as $line) {
			$sub += $line['amount'];
			$vat += $line['vat'];
			$discount += $line['discount'];
			$itemId = self::guidFrom('item-' . $line['product_id']);
			$detailId = self::guidFrom('line-' . $soId . '-' . $line['seq']);
			$unitName = !empty($line['unit']) ? $line['unit'] : 'Cái';
			$details[] = array(
				'ref_detail_id' => $detailId,
				'refid' => $orgRefid,
				'inventory_item_id' => $itemId,
				'inventory_item_code' => $line['code'],
				'inventory_item_name' => $line['name'],
				'description' => $line['name'],
				'sort_order' => $line['seq'] > 0 ? $line['seq'] : 1,
				'quantity' => $line['qty'],
				'main_quantity' => $line['qty'],
				'unit_price' => $line['price'],
				'main_unit_price' => $line['price'],
				'unit_name' => $unitName,
				'main_unit_name' => $unitName,
				'amount_oc' => $line['amount'],
				'amount' => $line['amount'],
				'discount_rate' => $line['discount_rate'],
				'discount_amount_oc' => $line['discount'],
				'discount_amount' => $line['discount'],
				'vat_rate' => $line['vat_rate'],
				'vat_amount_oc' => $line['vat'],
				'vat_amount' => $line['vat'],
				'account_object_id' => $partyId,
				'account_object_code' => $party['code'],
				'account_object_name' => $party['name'],
				'account_object_address' => $party['address'],
				'main_convert_rate' => 1,
				'is_promotion' => false,
				'is_description' => false,
				'crm_id' => (string) $line['product_id'],
				'state' => 0,
			);
			$dictionary[] = array(
				'dictionary_type' => 3,
				'inventory_item_id' => $itemId,
				'inventory_item_code' => $line['code'],
				'inventory_item_name' => $line['name'],
				'inventory_item_type' => 0,
				'unit_name' => $unitName,
				'inactive' => false,
			);
		}

		$grand = round($sub + $vat, 2);
		// voucher_type 20 = Đơn đặt hàng (sa_order); reftype 3520 = Đơn đặt hàng AMIS.
		$voucher = array(
			'voucher_type' => 20,
			'org_refid' => $orgRefid,
			'org_refno' => $orderNo,
			'org_reftype' => 3520,
			'org_reftype_name' => 'Đơn đặt hàng',
			'reftype' => 3520,
			'refdate' => $today,
			'crm_id' => (string) $soId,
			'currency_id' => 'VND',
			'exchange_rate' => 1,
			'account_object_id' => $partyId,
			'account_object_code' => $party['code'],
			'account_object_name' => $party['name'],
			'account_object_address' => $party['address'],
			'account_object_tax_code' => $party['tax'],
			'journal_memo' => 'Đơn đặt hàng từ CRM ' . $orderNo,
			'total_sale_amount_oc' => round($sub, 2),
			'total_sale_amount' => round($sub, 2),
			'total_discount_amount_oc' => round($discount, 2),
			'total_discount_amount' => round($discount, 2),
			'total_vat_amount_oc' => round($vat, 2),
			'total_vat_amount' => round($vat, 2),
			'total_amount_oc' => $grand,
			'total_amount' => $grand,
			'discount_type' => 0,
			'discount_rate_voucher' => 0,
			'status' => 0,
			'delivered_status' => 0,
			'is_invoiced' => false,
			'due_day' => 0,
			'created_date' => $now,
			'modified_date' => $now,
			'detail' => $details,
		);
		return array('voucher' => $voucher, 'dictionary' => $dictionary);
	}

	protected static function party($soModel) {
		$accountId = (int) $soModel->get('account_id');
		$contactId = (int) $soModel->get('contact_id');
		$name = '';
		$tax = '';
		$address = trim(implode(', ', array_filter(array(
			trim((string) $soModel->get('bill_street')),
			trim((string) $soModel->get('bill_city')),
			trim((string) $soModel->get('bill_state')),
		))));
		if ($accountId > 0) {
			$account = Vtiger_Record_Model::getInstanceById($accountId, 'Accounts');
			if ($account) {
				$name = trim((string) $account->get('accountname'));
			}
			$adb = PearDatabase::getInstance();
			$res = $adb->pquery('SELECT siccode FROM vtiger_account WHERE accountid = ?', array($accountId));
			if ($res && $adb->num_rows($res) > 0) {
				$tax = trim((string) decode_html($adb->query_result($res, 0, 'siccode')));
			}
		}
		if ($name === '' && $contactId > 0) {
			$contact = Vtiger_Record_Model::getInstanceById($contactId, 'Contacts');
			if ($contact) {
				$name = trim($contact->get('firstname') . ' ' . $contact->get('lastname'));
			}
		}
		if ($name === '') {
			$name = trim((string) $soModel->get('subject'));
		}
		if ($name === '') {
			$name = 'Khách hàng';
		}
		$key = $accountId > 0 ? ('a' . $accountId) : ('c' . $contactId);
		$code = $accountId > 0 ? ('KH' . $accountId) : ('KH' . ($contactId > 0 ? $contactId : $soModel->getId()));
		return array(
			'key' => $key,
			'code' => $code,
			'name' => $name,
			'address' => $address,
			'tax' => $tax,
		);
	}

	protected static function ensureStatuses() {
		$module = Vtiger_Module_Model::getInstance('Invoice');
		$field = $module ? Vtiger_Field_Model::getInstance('invoicestatus', $module) : null;
		if (!$field) {
			return;
		}
		$existing = $field->getPicklistValues();
		$need = array(
			self::STATUS_WAIT,
			self::STATUS_OK,
			self::STATUS_NO,
			'Hóa đơn mới',
			'Đã phát hành',
			'Đợi gửi',
			'Đang gửi',
			'Phát hành lỗi',
			'Hóa đơn đã hủy',
		);
		$missing = array();
		foreach ($need as $label) {
			$found = false;
			if (is_array($existing)) {
				foreach ($existing as $key => $value) {
					if ((string) $key === $label || (string) $value === $label) {
						$found = true;
						break;
					}
				}
			}
			if (!$found) {
				$missing[] = $label;
			}
		}
		if (!$missing) {
			return;
		}
		try {
			require_once 'modules/Settings/Picklist/models/Module.php';
			require_once 'modules/Settings/Picklist/models/Field.php';
			$settings = Settings_Picklist_Module_Model::getInstance('Invoice');
			$fieldModel = Settings_Picklist_Field_Model::getInstanceFromFieldObject($field);
			$roles = array();
			$adb = PearDatabase::getInstance();
			$res = $adb->pquery('SELECT roleid FROM vtiger_role', array());
			if ($res) {
				for ($i = 0; $i < $adb->num_rows($res); $i++) {
					$roles[] = $adb->query_result($res, $i, 'roleid');
				}
			}
			foreach ($missing as $label) {
				$settings->addPickListValues($fieldModel, $label, $roles, '');
			}
		} catch (Exception $e) {
			// Trạng thái vẫn được ghi trên hóa đơn nếu picklist chưa nhận giá trị mới.
		}
	}

	protected static function setInvoiceStatus($invoiceId, $status) {
		$invoiceId = (int) $invoiceId;
		if ($invoiceId <= 0) {
			return;
		}
		PearDatabase::getInstance()->pquery(
			'UPDATE vtiger_invoice SET invoicestatus = ? WHERE invoiceid = ?',
			array($status, $invoiceId)
		);
	}

	protected static function saveLink($soId, $invoiceId, $orgRefid, $status, $message, $refno) {
		$adb = PearDatabase::getInstance();
		self::install($adb);
		$now = date('Y-m-d H:i:s');
		$found = $adb->pquery('SELECT salesorderid FROM mk_misa_voucher WHERE salesorderid = ?', array($soId));
		if ($found && $adb->num_rows($found) > 0) {
			$adb->pquery(
				'UPDATE mk_misa_voucher SET invoiceid = ?, org_refid = ?, status = ?, message = ?, misa_refno = ?, updated_at = ? WHERE salesorderid = ?',
				array($invoiceId, $orgRefid, $status, $message, $refno, $now, $soId)
			);
			return;
		}
		$adb->pquery(
			'INSERT INTO mk_misa_voucher (salesorderid, invoiceid, org_refid, status, message, misa_refno, updated_at) VALUES (?,?,?,?,?,?,?)',
			array($soId, $invoiceId, $orgRefid, $status, $message, $refno, $now)
		);
	}

	public static function invoiceLabelForSalesOrder($soId) {
		self::install();
		$row = self::findBySalesOrder((int) $soId);
		if (!$row) {
			return '';
		}
		if ($row['status'] === 'published') {
			return 'Đã phát hành';
		}
		return self::invoiceStatusOf((int) $row['invoiceid']);
	}

	protected static function findBySalesOrder($soId) {
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery('SELECT * FROM mk_misa_voucher WHERE salesorderid = ?', array($soId));
		if (!$res || $adb->num_rows($res) < 1) {
			return null;
		}
		return $adb->query_result_rowdata($res, 0);
	}

	protected static function invoiceStatusOf($invoiceId) {
		$invoiceId = (int) $invoiceId;
		if ($invoiceId <= 0) {
			return '';
		}
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery('SELECT invoicestatus FROM vtiger_invoice WHERE invoiceid = ?', array($invoiceId));
		if (!$res || $adb->num_rows($res) < 1) {
			return '';
		}
		return trim((string) decode_html($adb->query_result($res, 0, 'invoicestatus')));
	}

	protected static function findByOrgRefid($orgRefid) {
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery('SELECT * FROM mk_misa_voucher WHERE org_refid = ? LIMIT 1', array($orgRefid));
		if (!$res || $adb->num_rows($res) < 1) {
			return null;
		}
		return $adb->query_result_rowdata($res, 0);
	}

	protected static function invoiceNo($invoiceId) {
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery('SELECT invoice_no FROM vtiger_invoice WHERE invoiceid = ?', array($invoiceId));
		if (!$res || $adb->num_rows($res) < 1) {
			return '';
		}
		return trim((string) decode_html($adb->query_result($res, 0, 'invoice_no')));
	}

	public static function guidFrom($seed) {
		$h = md5('nk-misa-act-' . $seed);
		$h[12] = '4';
		$variant = hexdec($h[16]);
		$h[16] = dechex(($variant & 0x3) | 0x8);
		return substr($h, 0, 8) . '-' . substr($h, 8, 4) . '-' . substr($h, 12, 4) . '-' . substr($h, 16, 4) . '-' . substr($h, 20, 12);
	}

	protected static function money($value) {
		if (is_string($value)) {
			$value = str_replace(array(' ', ','), array('', ''), $value);
		}
		return (float) $value;
	}
}
