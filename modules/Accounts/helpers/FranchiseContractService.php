<?php
/*+***********************************************************************************
 * Tuibao (Accounts) — Hợp đồng nhượng quyền TUI BAO: schema, merge, PDF.
 *************************************************************************************/

require_once 'modules/Accounts/helpers/FranchiseContractHtml.php';

class Accounts_FranchiseContractService_Helper {

	const FONT = 'freeserif';
	const BLANK = '………………';
	const BLOCK_LABEL = 'LBL_TB_FRANCHISE_CONTRACT';

	/** Lazy-load PDF stack only when exporting (avoid fatal on List/Edit). */
	protected static function loadPdfDeps() {
		static $loaded = false;
		if ($loaded) {
			return;
		}
		$loaded = true;
		require_once 'modules/Quotes/helpers/QuoteBaService.php';
		require_once 'modules/Quotes/helpers/QuoteExcelExport.php';
		include_once 'vtlib/Vtiger/PDF/PDFGenerator.php';
	}

	/**
	 * Ensure franchise block + custom fields on vtiger_accountscf.
	 */
	public static function ensureFranchiseFields() {
		static $done = false;
		if ($done) {
			return;
		}
		$done = true;

		require_once 'vtlib/Vtiger/Module.php';
		require_once 'vtlib/Vtiger/Block.php';
		require_once 'vtlib/Vtiger/Field.php';

		$module = Vtiger_Module::getInstance('Accounts');
		if (!$module) {
			return;
		}

		$block = Vtiger_Block::getInstance(self::BLOCK_LABEL, $module);
		if (!$block) {
			$block = new Vtiger_Block();
			$block->label = self::BLOCK_LABEL;
			$module->addBlock($block);
		}

		// Create/Edit: field nhập liệu. Các field mirror/ẩn tự điền khi merge PDF.
		$specs = array(
			array('tb_contract_no', 'Số hợp đồng', 1, 'VARCHAR(64)', 'V~O', true),
			array('tb_sign_date', 'Ngày ký', 5, 'DATE', 'D~O', true),
			array('tb_term_years', 'Thời hạn (năm)', 7, 'VARCHAR(8)', 'N~O', false),
			array('tb_party_b_name', 'Họ tên Bên B', 1, 'VARCHAR(255)', 'V~O', false),
			array('tb_party_b_cccd', 'CCCD', 1, 'VARCHAR(64)', 'V~O', true),
			array('tb_party_b_cccd_date', 'Ngày cấp CCCD', 5, 'DATE', 'D~O', true),
			array('tb_party_b_cccd_place', 'Nơi cấp CCCD', 1, 'VARCHAR(255)', 'V~O', true),
			array('tb_party_b_permanent_addr', 'Địa chỉ thường trú / liên hệ', 1, 'VARCHAR(512)', 'V~O', true),
			array('tb_party_b_contact_addr', 'Địa chỉ liên hệ Bên B', 1, 'VARCHAR(512)', 'V~O', false),
			array('tb_party_b_phone', 'Điện thoại Bên B', 11, 'VARCHAR(50)', 'V~O', false),
			array('tb_party_b_email', 'Email Bên B', 13, 'VARCHAR(100)', 'E~O', false),
			array('tb_store_address', 'Địa chỉ cửa hàng', 19, 'TEXT', 'V~O', true),
			array('tb_fee_franchise', 'Phí nhượng quyền', 71, 'DECIMAL(25,2)', 'N~O', true),
			array('tb_fee_marketing', 'Phí marketing thương hiệu', 71, 'DECIMAL(25,2)', 'N~O', true),
			array('tb_fee_consult', 'Phí tư vấn / hỗ trợ vận hành', 71, 'DECIMAL(25,2)', 'N~O', true),
			array('tb_fee_opening', 'Phí marketing khai trương', 71, 'DECIMAL(25,2)', 'N~O', true),
			array('tb_fee_deposit', 'Tiền ký quỹ bảo đảm (Đợt 1)', 71, 'DECIMAL(25,2)', 'N~O', true),
			array('tb_pay_1', 'Thanh toán đợt 1 (ký quỹ)', 71, 'DECIMAL(25,2)', 'N~O', false),
			array('tb_pay_2', 'Đặt cọc đợt 2', 71, 'DECIMAL(25,2)', 'N~O', false),
			array('tb_pay_3', 'Thanh toán đợt 3 (còn lại)', 71, 'DECIMAL(25,2)', 'N~O', false),
			array('tb_order_min_free', 'Đơn hàng tối thiểu miễn ship', 71, 'DECIMAL(25,2)', 'N~O', true),
			array('tb_order_min_ship', 'Đơn hàng dưới mức (có ship)', 71, 'DECIMAL(25,2)', 'N~O', true),
			array('tb_order_ship_fee', 'Phí ship nội thành', 71, 'DECIMAL(25,2)', 'N~O', true),
			array('tb_order_min_pickup', 'Đơn hàng tự đến kho lấy', 71, 'DECIMAL(25,2)', 'N~O', true),
		);

		foreach ($specs as $spec) {
			self::addOrSyncField($module, $block, $spec[0], $spec[1], $spec[2], $spec[3], $spec[4], !empty($spec[5]));
		}

		$adb = PearDatabase::getInstance();
		$defaults = array(
			'tb_term_years' => '4',
			'tb_order_min_free' => '10000000',
			'tb_order_min_ship' => '10000000',
			'tb_order_ship_fee' => '50000',
			'tb_order_min_pickup' => '5000000',
		);
		foreach ($defaults as $fname => $fval) {
			$adb->pquery(
				"UPDATE vtiger_field SET defaultvalue = ? WHERE tabid = ? AND fieldname = ? AND (defaultvalue IS NULL OR defaultvalue = '')",
				array($fval, (int) $module->id, $fname)
			);
		}
	}

	/**
	 * @param bool $showOnEdit true = Create/Edit+Detail; false = Detail only (ẩn khỏi form nhập)
	 */
	protected static function addOrSyncField(Vtiger_Module $module, Vtiger_Block $block, $name, $label, $uitype, $columntype, $typeofdata, $showOnEdit) {
		$displaytype = $showOnEdit ? 1 : 2;
		$existing = Vtiger_Field::getInstance($name, $module);
		if ($existing) {
			$adb = PearDatabase::getInstance();
			$adb->pquery(
				'UPDATE vtiger_field SET fieldlabel = ?, displaytype = ?, presence = 2 WHERE fieldid = ?',
				array($label, $displaytype, (int) $existing->id)
			);
			return $existing;
		}
		$field = new Vtiger_Field();
		$field->name = $name;
		$field->label = $label;
		$field->table = 'vtiger_accountscf';
		$field->column = $name;
		$field->columntype = $columntype;
		$field->uitype = $uitype;
		$field->typeofdata = $typeofdata;
		$field->displaytype = $displaytype;
		$field->presence = 2;
		$field->quickcreate = $showOnEdit ? 1 : 0;
		$field->masseditable = $showOnEdit ? 1 : 0;
		$block->addField($field);
		return $field;
	}

	/**
	 * @param Vtiger_Record_Model|CRMEntity $record
	 * @return array
	 */
	public static function buildContext($record) {
		self::ensureFranchiseFields();

		$get = function ($name) use ($record) {
			if ($record instanceof Vtiger_Record_Model) {
				return $record->get($name);
			}
			if (is_object($record) && isset($record->column_fields) && isset($record->column_fields[$name])) {
				return $record->column_fields[$name];
			}
			return '';
		};

		$partyName = trim((string) $get('tb_party_b_name'));
		if ($partyName === '') {
			$partyName = trim((string) $get('accountname'));
		}
		$phone = trim((string) $get('tb_party_b_phone'));
		if ($phone === '') {
			$phone = trim((string) $get('phone'));
		}
		$email = trim((string) $get('tb_party_b_email'));
		if ($email === '') {
			$email = trim((string) $get('email1'));
		}

		$permanent = trim((string) $get('tb_party_b_permanent_addr'));
		if ($permanent === '') {
			$permanent = trim((string) $get('bill_street'));
		}
		$contact = trim((string) $get('tb_party_b_contact_addr'));
		if ($contact === '') {
			$contact = $permanent;
		}
		$store = trim((string) $get('tb_store_address'));
		if ($store === '') {
			$store = $permanent;
		}

		$signRaw = (string) $get('tb_sign_date');
		if (trim($signRaw) === '' || $signRaw === '0000-00-00') {
			$signRaw = date('Y-m-d');
		}
		$signParts = self::splitDate($signRaw);

		$termYears = trim((string) $get('tb_term_years'));
		if ($termYears === '') {
			$termYears = '4';
		}

		$feeFranchise = self::toFloat($get('tb_fee_franchise'));
		$feeMarketing = self::toFloat($get('tb_fee_marketing'));
		$feeConsult = self::toFloat($get('tb_fee_consult'));
		$feeOpening = self::toFloat($get('tb_fee_opening'));
		$feeTotal = $feeFranchise + $feeMarketing + $feeConsult + $feeOpening;

		// Tiền ký quỹ = Đợt 1 (cùng một số). Fallback: nếu thiếu deposit thì lấy pay_1 cũ (nếu có).
		$feeDeposit = self::toFloat($get('tb_fee_deposit'));
		if ($feeDeposit <= 0) {
			$feeDeposit = self::toFloat($get('tb_pay_1'));
		}
		$pay1 = $feeDeposit;
		$pay2 = $feeFranchise;
		$pay3 = max(0, $feeTotal - $feeFranchise);

		// Ngưỡng đơn hàng: mặc định vận hành nếu user không nhập
		$orderMinFree = self::toFloat($get('tb_order_min_free'));
		$orderMinShip = self::toFloat($get('tb_order_min_ship'));
		$orderShipFee = self::toFloat($get('tb_order_ship_fee'));
		$orderMinPickup = self::toFloat($get('tb_order_min_pickup'));
		if ($orderMinFree <= 0) {
			$orderMinFree = 10000000;
		}
		if ($orderMinShip <= 0) {
			$orderMinShip = 10000000;
		}
		if ($orderShipFee <= 0) {
			$orderShipFee = 50000;
		}
		if ($orderMinPickup <= 0) {
			$orderMinPickup = 5000000;
		}

		$contractNo = trim((string) $get('tb_contract_no'));
		if ($contractNo === '') {
			$recordId = 0;
			if ($record instanceof Vtiger_Record_Model) {
				$recordId = (int) $record->getId();
			} elseif (is_object($record) && isset($record->id)) {
				$recordId = (int) $record->id;
			}
			$contractNo = $recordId > 0 ? (string) $recordId : '';
		}

		return array(
			'contract_no' => self::displayText($contractNo),
			'sign_day' => $signParts['d'],
			'sign_month' => $signParts['m'],
			'sign_year' => $signParts['y'],
			'party_b_name' => self::displayText($partyName),
			'party_b_cccd' => self::displayText($get('tb_party_b_cccd')),
			'party_b_cccd_date' => self::formatDateVi($get('tb_party_b_cccd_date')),
			'party_b_cccd_place' => self::displayText($get('tb_party_b_cccd_place')),
			'party_b_permanent_addr' => self::displayText($permanent),
			'party_b_contact_addr' => self::displayText($contact),
			'party_b_phone' => self::displayText($phone),
			'party_b_email' => self::displayText($email),
			'store_address' => self::displayText($store),
			'term_years' => self::displayText($termYears),
			'term_years_display' => self::displayTermYears($termYears),
			'fee_franchise' => self::displayMoney($feeFranchise),
			'fee_franchise_words' => self::displayWords($feeFranchise),
			'fee_marketing' => self::displayMoney($feeMarketing),
			'fee_marketing_words' => self::displayWords($feeMarketing),
			'fee_consult' => self::displayMoney($feeConsult),
			'fee_consult_words' => self::displayWords($feeConsult),
			'fee_opening' => self::displayMoney($feeOpening),
			'fee_opening_words' => self::displayWords($feeOpening),
			'fee_total' => self::displayMoney($feeTotal),
			'fee_total_words' => self::displayWords($feeTotal),
			'fee_deposit' => self::displayMoney($feeDeposit),
			'fee_deposit_words' => self::displayWords($feeDeposit),
			'pay_1' => self::displayMoney($pay1),
			'pay_1_words' => self::displayWords($pay1),
			'pay_2' => self::displayMoney($pay2),
			'pay_2_words' => self::displayWords($pay2),
			'pay_3' => self::displayMoney($pay3),
			'pay_3_words' => self::displayWords($pay3),
			'order_min_free' => self::displayMoney($orderMinFree),
			'order_min_free_words' => self::displayWords($orderMinFree),
			'order_min_ship' => self::displayMoney($orderMinShip),
			'order_min_ship_words' => self::displayWords($orderMinShip),
			'order_ship_fee' => self::displayMoney($orderShipFee),
			'order_ship_fee_words' => self::displayWords($orderShipFee),
			'order_min_pickup' => self::displayMoney($orderMinPickup),
			'order_min_pickup_words' => self::displayWords($orderMinPickup),
		);
	}

	public static function buildHtml($record) {
		$ctx = self::buildContext($record);
		$html = Accounts_FranchiseContractHtml_Helper::template();
		foreach ($ctx as $key => $value) {
			$html = str_replace('{{' . $key . '}}', htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'), $html);
		}
		return $html;
	}

	/**
	 * @param Vtiger_Record_Model|CRMEntity $record
	 * @param string $fileName
	 * @param string $dest I|D|F|S
	 */
	public static function outputPdf($record, $fileName, $dest = 'I') {
		self::loadPdfDeps();
		$html = self::buildHtml($record);
		$pdf = new Vtiger_PDF_TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);
		$pdf->SetCreator('Nguyên Khoa');
		$pdf->SetAuthor('Công ty CP TM DV SX Nguyên Khoa');
		$pdf->SetTitle('Hợp đồng nhượng quyền TUI BAO');
		$pdf->SetMargins(16, 14, 16);
		$pdf->SetAutoPageBreak(true, 16);
		$pdf->AddPage();
		$pdf->SetFont(self::FONT, '', 12);
		$pdf->writeHTML($html, true, false, true, false, '');
		return $pdf->Output($fileName, $dest);
	}

	protected static function splitDate($raw) {
		$raw = trim((string) $raw);
		$d = self::BLANK;
		$m = self::BLANK;
		$y = self::BLANK;
		if ($raw === '' || $raw === '0000-00-00') {
			return array('d' => $d, 'm' => $m, 'y' => $y);
		}
		$ts = strtotime($raw);
		if ($ts) {
			return array(
				'd' => date('d', $ts),
				'm' => date('m', $ts),
				'y' => date('Y', $ts),
			);
		}
		if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $raw, $m2)) {
			return array('d' => $m2[3], 'm' => $m2[2], 'y' => $m2[1]);
		}
		return array('d' => $d, 'm' => $m, 'y' => $y);
	}

	protected static function formatDateVi($raw) {
		$raw = trim((string) $raw);
		if ($raw === '' || $raw === '0000-00-00') {
			return self::BLANK;
		}
		$ts = strtotime($raw);
		if ($ts) {
			return date('d/m/Y', $ts);
		}
		if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $raw, $m)) {
			return $m[3] . '/' . $m[2] . '/' . $m[1];
		}
		return self::displayText($raw);
	}

	protected static function toFloat($value) {
		if ($value === null || $value === '') {
			return 0.0;
		}
		if (is_numeric($value)) {
			return (float) $value;
		}
		$s = trim((string) $value);
		$s = str_replace(array(' ', "\xc2\xa0"), '', $s);
		// VI: 1.000.000,50 or US: 1,000,000.50
		if (preg_match('/^\d{1,3}(\.\d{3})+(,\d+)?$/', $s)) {
			$s = str_replace('.', '', $s);
			$s = str_replace(',', '.', $s);
		} else {
			$s = str_replace(',', '', $s);
		}
		return (float) $s;
	}

	protected static function displayText($value) {
		$value = trim(html_entity_decode((string) $value, ENT_QUOTES, 'UTF-8'));
		return $value !== '' ? $value : self::BLANK;
	}

	protected static function displayMoney($amount) {
		$amount = (float) $amount;
		if ($amount <= 0) {
			return self::BLANK;
		}
		return Quotes_QuoteExcelExport_Helper::formatMoneyVnPublic($amount);
	}

	protected static function displayWords($amount) {
		$amount = (float) $amount;
		if ($amount <= 0) {
			return self::BLANK;
		}
		$words = Quotes_QuoteBaService_Helper::amountInWordsVi($amount);
		$words = trim((string) $words);
		if ($words === '') {
			return self::BLANK;
		}
		return $words;
	}

	protected static function displayTermYears($years) {
		$y = (int) $years;
		if ($y <= 0) {
			$y = 4;
		}
		$map = array(
			1 => 'một', 2 => 'hai', 3 => 'ba', 4 => 'bốn', 5 => 'năm',
			6 => 'sáu', 7 => 'bảy', 8 => 'tám', 9 => 'chín', 10 => 'mười',
		);
		$word = isset($map[$y]) ? $map[$y] : (string) $y;
		return str_pad((string) $y, 2, '0', STR_PAD_LEFT) . ' (' . $word . ')';
	}
}
