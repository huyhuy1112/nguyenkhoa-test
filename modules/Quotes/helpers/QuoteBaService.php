<?php
/*+***********************************************************************************
 * Quotes BA form helpers — company profile, defaults, amount in words, term templates.
 *************************************************************************************/

class Quotes_QuoteBaService_Helper {

	const DEFAULT_VAT_PERCENT = 8;
	const DEFAULT_VALID_DAYS = 30;
	const DEFAULT_COMPANY_NAME = 'CÔNG TY TNHH ỨNG DỤNG KỸ THUẬT VÀ GIẢI PHÁP SỐ TDB';
	const DEFAULT_TAX_CODE = '0318137951';
	const DEFAULT_ADDRESS = 'Số 26, Đường số 14, Khu dân cư Vạn Phúc, Phường Hiệp Bình, TP Hồ Chí Minh, Việt Nam';
	const DEFAULT_WEBSITE = '';
	const QUOTE_LOGO_REL_PATH = 'layouts/v7/modules/Quotes/resources/images/tdb-quote-logo.png';

	protected static function isStockVtigerOrganization(array $row) {
		$name = strtolower(trim((string) ($row['organizationname'] ?? '')));
		$website = strtolower(trim((string) ($row['website'] ?? '')));
		$address = strtolower(trim((string) ($row['address'] ?? '')));
		if ($name === 'vtiger' || $name === '') {
			return true;
		}
		if ($website === 'www.vtiger.com') {
			return true;
		}
		if ($address !== '' && (strpos($address, 'bangalore') !== false || strpos($address, 'rajajinagar') !== false)) {
			return true;
		}
		return false;
	}

	protected static function getProjectRootPaths() {
		global $root_directory;
		$roots = array();
		if (!empty($root_directory)) {
			$roots[] = rtrim((string) $root_directory, "/\\");
		}
		$moduleRoot = realpath(dirname(__FILE__) . '/../../..');
		if ($moduleRoot) {
			$roots[] = $moduleRoot;
		}
		$cwd = getcwd();
		if ($cwd) {
			$roots[] = rtrim((string) $cwd, "/\\");
		}
		return array_values(array_unique(array_filter($roots)));
	}

	public static function isValidQuoteLogoImage($absolutePath) {
		if ($absolutePath === '' || !is_readable($absolutePath)) {
			return false;
		}
		$info = @getimagesize($absolutePath);
		if (!$info || empty($info[0]) || empty($info[1])) {
			return false;
		}
		$type = isset($info[2]) ? (int) $info[2] : 0;
		return in_array($type, array(IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF), true);
	}

	public static function resolveQuoteLogoPath($logoname = '') {
		$relativeCandidates = array(self::QUOTE_LOGO_REL_PATH);
		$logoname = trim((string) $logoname);
		if ($logoname !== '') {
			$relativeCandidates[] = 'test/logo/' . $logoname;
		}
		foreach ($relativeCandidates as $relativePath) {
			foreach (self::getProjectRootPaths() as $root) {
				$absolutePath = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
				if (self::isValidQuoteLogoImage($absolutePath)) {
					return $absolutePath;
				}
			}
		}
		return '';
	}

	protected static function normalizeCompanyField($value, $fallback) {
		$value = trim((string) $value);
		return $value !== '' ? $value : $fallback;
	}

	public static function ensureOrganizationBankColumns() {
		$db = PearDatabase::getInstance();
		$table = 'vtiger_organizationdetails';
		$columns = array(
			'bank_name' => 'VARCHAR(255) DEFAULT NULL',
			'bank_account' => 'VARCHAR(100) DEFAULT NULL',
			'account_holder' => 'VARCHAR(255) DEFAULT NULL',
		);
		foreach ($columns as $column => $definition) {
			$result = $db->pquery('SHOW COLUMNS FROM ' . $table . ' LIKE ?', array($column));
			if (!$result || !$db->num_rows($result)) {
				$db->pquery('ALTER TABLE ' . $table . ' ADD COLUMN ' . $column . ' ' . $definition, array());
			}
		}
	}

	/**
	 * Column linking quote ↔ ServiceContracts (created from Khách hàng nhượng quyền).
	 * DB-only: list filter + save update; no UI field required.
	 */
	public static function ensureServiceContractLinkColumn() {
		static $done = false;
		if ($done) {
			return true;
		}
		$db = PearDatabase::getInstance();
		try {
			$rs = $db->pquery('SHOW COLUMNS FROM vtiger_quotes LIKE ?', array('mk_servicecontract_id'));
			if (!$rs || !$db->num_rows($rs)) {
				$db->pquery(
					'ALTER TABLE vtiger_quotes ADD COLUMN mk_servicecontract_id INT(19) DEFAULT NULL',
					array()
				);
				try {
					$db->pquery(
						'ALTER TABLE vtiger_quotes ADD INDEX mk_quotes_sc_id (mk_servicecontract_id)',
						array()
					);
				} catch (Exception $eIdx) {
					// Index may already exist or fail on some engines — non-fatal.
				}
			}
			$done = true;
			return true;
		} catch (Exception $e) {
			return false;
		}
	}

	/**
	 * Read SC id from create URL/form (`servicecontract_id` or `mk_servicecontract_id`).
	 */
	public static function resolveServiceContractIdFromRequest(Vtiger_Request $request) {
		$scId = (int) $request->get('mk_servicecontract_id');
		if ($scId <= 0) {
			$scId = (int) $request->get('servicecontract_id');
		}
		return $scId > 0 ? $scId : 0;
	}

	/**
	 * Persist SC link after quote save. Only updates when $scId > 0 (does not clear on normal edit).
	 *
	 * @param int $quoteId
	 * @param int $scId
	 * @return bool
	 */
	public static function persistServiceContractLink($quoteId, $scId) {
		$quoteId = (int) $quoteId;
		$scId = (int) $scId;
		if ($quoteId <= 0 || $scId <= 0) {
			return false;
		}
		if (!self::ensureServiceContractLinkColumn()) {
			return false;
		}
		$db = PearDatabase::getInstance();
		$live = $db->pquery(
			'SELECT 1 FROM vtiger_servicecontracts sc
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = sc.servicecontractsid AND ce.deleted = 0
			 WHERE sc.servicecontractsid = ? LIMIT 1',
			array($scId)
		);
		if (!$live || !$db->num_rows($live)) {
			return false;
		}
		$db->pquery(
			'UPDATE vtiger_quotes SET mk_servicecontract_id = ? WHERE quoteid = ?',
			array($scId, $quoteId)
		);

		// Enrich customer fields so list/display keep the franchise name + phone.
		try {
			self::syncQuoteCustomerFromServiceContract($quoteId, $scId);
		} catch (Exception $e) {
			// non-fatal
		}

		// Related list both ways when missing.
		try {
			$rel = $db->pquery(
				'SELECT 1 FROM vtiger_crmentityrel
				 WHERE (crmid = ? AND relcrmid = ?) OR (crmid = ? AND relcrmid = ?)
				 LIMIT 1',
				array($scId, $quoteId, $quoteId, $scId)
			);
			if (!$rel || !$db->num_rows($rel)) {
				$db->pquery(
					'INSERT INTO vtiger_crmentityrel (crmid, module, relcrmid, relmodule) VALUES (?, ?, ?, ?)',
					array($scId, 'ServiceContracts', $quoteId, 'Quotes')
				);
			}
		} catch (Exception $e) {
			// Relation table shape may differ — ignore.
		}
		return true;
	}

	/**
	 * Fill subject / account / phone / email from franchise SC when quote lacks contact.
	 *
	 * @param int $quoteId
	 * @param int $scId
	 * @return void
	 */
	public static function syncQuoteCustomerFromServiceContract($quoteId, $scId) {
		$quoteId = (int) $quoteId;
		$scId = (int) $scId;
		if ($quoteId <= 0 || $scId <= 0) {
			return;
		}
		$db = PearDatabase::getInstance();
		$scRes = $db->pquery(
			'SELECT sc.subject, sc.sc_related_to, acc.accountname
			 FROM vtiger_servicecontracts sc
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = sc.servicecontractsid AND ce.deleted = 0
			 LEFT JOIN vtiger_account acc ON acc.accountid = sc.sc_related_to
			 WHERE sc.servicecontractsid = ?',
			array($scId)
		);
		if (!$scRes || !$db->num_rows($scRes)) {
			return;
		}
		$subject = trim(html_entity_decode((string) $db->query_result($scRes, 0, 'subject'), ENT_QUOTES, 'UTF-8'));
		$accountId = (int) $db->query_result($scRes, 0, 'sc_related_to');
		$accountName = trim(html_entity_decode((string) $db->query_result($scRes, 0, 'accountname'), ENT_QUOTES, 'UTF-8'));
		$label = $subject !== '' ? $subject : $accountName;

		$phone = '';
		$email = '';
		try {
			$pRes = $db->pquery(
				'SELECT phone, email FROM bace_sc_profile WHERE servicecontractsid = ?',
				array($scId)
			);
			if ($pRes && $db->num_rows($pRes) > 0) {
				$phone = trim(html_entity_decode((string) $db->query_result($pRes, 0, 'phone'), ENT_QUOTES, 'UTF-8'));
				$email = trim(html_entity_decode((string) $db->query_result($pRes, 0, 'email'), ENT_QUOTES, 'UTF-8'));
			}
		} catch (Exception $e) {
			// profile table optional
		}

		$qRes = $db->pquery(
			'SELECT subject, accountid FROM vtiger_quotes WHERE quoteid = ?',
			array($quoteId)
		);
		if (!$qRes || !$db->num_rows($qRes)) {
			return;
		}
		$curSubject = trim(html_entity_decode((string) $db->query_result($qRes, 0, 'subject'), ENT_QUOTES, 'UTF-8'));
		$curAccount = (int) $db->query_result($qRes, 0, 'accountid');

		if (($curSubject === '' || preg_match('/^\.+$/', $curSubject)) && $label !== '') {
			$db->pquery('UPDATE vtiger_quotes SET subject = ? WHERE quoteid = ?', array($label, $quoteId));
		}
		if ($curAccount <= 0 && $accountId > 0) {
			$db->pquery('UPDATE vtiger_quotes SET accountid = ? WHERE quoteid = ? AND (accountid IS NULL OR accountid = 0)', array($accountId, $quoteId));
		}

		// Optional BA phone/email columns
		foreach (array(
			'mk_customer_phone' => $phone,
			'mk_customer_email' => $email,
		) as $col => $val) {
			if ($val === '') {
				continue;
			}
			try {
				$chk = $db->pquery('SHOW COLUMNS FROM vtiger_quotes LIKE ?', array($col));
				if ($chk && $db->num_rows($chk) > 0) {
					$db->pquery(
						'UPDATE vtiger_quotes SET `' . $col . '` = ? WHERE quoteid = ? AND (`' . $col . '` IS NULL OR `' . $col . '` = \'\')',
						array($val, $quoteId)
					);
				}
			} catch (Exception $e) {
				// ignore
			}
		}
	}

	public static function getCompanyProfile() {
		self::ensureOrganizationBankColumns();
		$db = PearDatabase::getInstance();
		$result = $db->pquery('SELECT * FROM vtiger_organizationdetails LIMIT 1', array());
		$row = ($result && $db->num_rows($result)) ? $db->fetchByAssoc($result) : array();
		$addressParts = array();
		foreach (array('address', 'city', 'state', 'code', 'country') as $key) {
			if (!empty($row[$key])) {
				$addressParts[] = $row[$key];
			}
		}
		$address = implode(', ', $addressParts);
		$useTdbDefaults = self::isStockVtigerOrganization($row);

		$companyName = (string) ($row['organizationname'] ?? '');
		$taxCode = (string) ($row['vatid'] ?? '');
		$website = (string) ($row['website'] ?? '');
		if ($useTdbDefaults) {
			$companyName = self::DEFAULT_COMPANY_NAME;
			$taxCode = self::normalizeCompanyField($taxCode, self::DEFAULT_TAX_CODE);
			$website = self::DEFAULT_WEBSITE;
			if ($address === '' || stripos($address, 'bangalore') !== false || stripos($address, 'rajajinagar') !== false) {
				$address = self::DEFAULT_ADDRESS;
			}
		} else {
			$companyName = self::normalizeCompanyField($companyName, self::DEFAULT_COMPANY_NAME);
			$taxCode = self::normalizeCompanyField($taxCode, self::DEFAULT_TAX_CODE);
			if ($address === '') {
				$address = self::DEFAULT_ADDRESS;
			}
		}

		return array(
			'company_name' => $companyName,
			'tax_code' => $taxCode,
			'website' => $website,
			'address' => $address,
			'phone' => (string) ($row['phone'] ?? ''),
			'bank_name' => (string) ($row['bank_name'] ?? ''),
			'bank_account' => (string) ($row['bank_account'] ?? ''),
			'account_holder' => (string) ($row['account_holder'] ?? ''),
			'logo_path' => self::resolveQuoteLogoPath($row['logoname'] ?? ''),
		);
	}

	public static function getTermsTemplates() {
		$standardBody = '<p><strong>1. Thông tin sản phẩm:</strong></p>'
			. '<p><br></p>'
			. '<p><strong>2. Điều khoản và phương thức thanh toán:</strong></p>'
			. '<p><strong>2.1. Điều khoản thanh toán:</strong></p>'
			. '<p><em>Thanh toán lần 1:</em> Ứng trước 70% trong vòng 02 ngày sau khi xác nhận thông tin báo giá.</p>'
			. '<p><em>Thanh toán lần 2:</em> Thanh toán 30% giá trị đơn hàng trong vòng 07 ngày sau khi có biên bản nghiệm thu hoàn thành công việc/ theo Hợp đồng.</p>'
			. '<p><strong>2.2. Phương thức &amp; thông tin thanh toán: Chuyển khoản</strong></p>'
			. '<p><strong><em>[Ngân hàng]:</em></strong></p>'
			. '<p><strong><em>[Người thụ hưởng]:</em></strong></p>'
			. '<p><strong><em>[Số tài khoản]:</em></strong></p>'
			. '<p><strong>3. Thời gian thực hiện:</strong></p>'
			. '<p><strong>3.1. Thời gian triển khai công việc:</strong> 3-5 ngày làm việc</p>'
			. '<p><strong>4. Chú ý:</strong></p>'
			. '<p><strong>4.1. [Xác nhận báo giá]:</strong> Việc gửi đơn đặt hàng (PO) qua email được xem là xác nhận và đồng ý với toàn bộ điều khoản trong báo giá.</p>'
			. '<p><strong>4.2. [Hủy báo giá]:</strong> Trường hợp hủy đơn phương sau khi đã ký xác nhận, bên hủy phải bồi thường 100% giá trị đơn hàng.</p>'
			. '<p><strong>4.3. [Hiệu lực báo giá]:</strong> Báo giá có hiệu lực trong vòng ba (03) ngày kể từ ngày phát hành.</p>';

		return array(
			array('id' => 'standard', 'label' => 'Mẫu BÁO GIÁ (1–4)', 'html' => $standardBody),
			array(
				'id' => 'product_info_only',
				'label' => 'Chỉ mục 1 — Thông tin sản phẩm',
				'html' => '<p><strong>1. Thông tin sản phẩm:</strong></p><p>Mô tả chi tiết sản phẩm/dịch vụ theo bảng giá đính kèm.</p>',
			),
			array('id' => 'blank', 'label' => 'Trống (tự nhập)', 'html' => ''),
		);
	}

	public static function getBaContext() {
		return array(
			'company' => self::getCompanyProfile(),
			'vat_percent_default' => self::DEFAULT_VAT_PERCENT,
			'valid_days_default' => self::DEFAULT_VALID_DAYS,
			'terms_templates' => self::getTermsTemplates(),
			'today' => date('Y-m-d'),
			'valid_until_default' => date('Y-m-d', strtotime('+' . self::DEFAULT_VALID_DAYS . ' days')),
		);
	}

	public static function parseMoneyNumber($value) {
		if ($value === null || $value === '') {
			return 0.0;
		}
		if (is_numeric($value) && !is_string($value)) {
			return (float) $value;
		}
		// Reuse inventory VN money parser when available.
		if (function_exists('mk_inventory_parse_money')) {
			return mk_inventory_parse_money($value);
		}
		$s = trim((string) $value);
		if ($s === '') {
			return 0.0;
		}
		if (preg_match('/^-?\d{1,3}(\.\d{3})+(,\d+)?$/', $s)) {
			$s = str_replace('.', '', $s);
			$s = str_replace(',', '.', $s);
			return (float) $s;
		}
		if (preg_match('/^-?\d{1,3}(,\d{3})+(\.\d+)?$/', $s)) {
			return (float) str_replace(',', '', $s);
		}
		$normalized = preg_replace('/[^\d.,-]/', '', $s);
		if (strpos($normalized, ',') !== false && strpos($normalized, '.') !== false) {
			if (strrpos($normalized, ',') > strrpos($normalized, '.')) {
				$normalized = str_replace('.', '', $normalized);
				$normalized = str_replace(',', '.', $normalized);
			} else {
				$normalized = str_replace(',', '', $normalized);
			}
		} elseif (strpos($normalized, ',') !== false) {
			$parts = explode(',', $normalized);
			if (count($parts) === 2 && strlen($parts[1]) <= 2) {
				$normalized = $parts[0] . '.' . $parts[1];
			} else {
				$normalized = str_replace(',', '', $normalized);
			}
		} elseif (preg_match('/^\d{1,3}(\.\d{3})+$/', $normalized)) {
			$normalized = str_replace('.', '', $normalized);
		}
		return is_numeric($normalized) ? (float) $normalized : 0.0;
	}

	public static function amountInWordsVi($amount) {
		$amount = (int) round(self::parseMoneyNumber($amount));
		if ($amount === 0) {
			return 'Không đồng';
		}
		if ($amount < 0) {
			return 'Âm ' . self::amountInWordsVi(abs($amount));
		}

		$units = array('', 'nghìn', 'triệu', 'tỷ', 'nghìn tỷ', 'triệu tỷ');
		$words = array('không', 'một', 'hai', 'ba', 'bốn', 'năm', 'sáu', 'bảy', 'tám', 'chín');

		$readTriple = function ($num) use ($words) {
			$hundreds = (int) floor($num / 100);
			$tens = (int) floor(($num % 100) / 10);
			$ones = (int) ($num % 10);
			$parts = array();
			if ($hundreds > 0) {
				$parts[] = $words[$hundreds] . ' trăm';
			}
			if ($tens > 1) {
				$parts[] = $words[$tens] . ' mươi';
				if ($ones === 1) {
					$parts[] = 'mốt';
				} elseif ($ones === 5) {
					$parts[] = 'lăm';
				} elseif ($ones > 0) {
					$parts[] = $words[$ones];
				}
			} elseif ($tens === 1) {
				$parts[] = 'mười';
				if ($ones === 5) {
					$parts[] = 'lăm';
				} elseif ($ones > 0) {
					$parts[] = $words[$ones];
				}
			} elseif ($ones > 0) {
				if ($hundreds > 0) {
					$parts[] = 'lẻ';
				}
				if ($ones === 5 && $hundreds > 0) {
					$parts[] = 'lăm';
				} else {
					$parts[] = $words[$ones];
				}
			}
			return implode(' ', array_filter($parts));
		};

		$chunks = array();
		$unitIndex = 0;
		while ($amount > 0) {
			$chunk = $amount % 1000;
			if ($chunk > 0) {
				$chunkText = $readTriple($chunk);
				if ($units[$unitIndex] !== '') {
					$chunkText .= ' ' . $units[$unitIndex];
				}
				array_unshift($chunks, trim($chunkText));
			}
			$amount = (int) floor($amount / 1000);
			$unitIndex++;
		}

		$text = implode(' ', $chunks);
		$text = preg_replace('/\s+/', ' ', trim($text));
		return ucfirst($text) . ' đồng';
	}

	/**
	 * Remove director / quotation signature placeholders from rich-text terms (CRM UI only).
	 */
	public static function stripSignatureFromTermsHtml($html) {
		if ($html === '' || $html === null) {
			return '';
		}
		$s = (string) $html;
		$s = preg_replace('/<div[^>]*mk-quote-signature-block[^>]*>[\s\S]*?<\/div>/iu', '', $s);
		$s = preg_replace('/<table[^>]*>[\s\S]*?(?:Giám\s*đốc|\(Director\)|Người\s*báo\s*giá|\(Quotation\s*created\s*by\)|\(Customer\))[\s\S]*?<\/table>/iu', '', $s);
		$s = preg_replace('/<(?:p|div|td|th|li|h[1-6])[^>]*>\s*(?:<[^>]+>\s*)*(?:Giám\s*đốc|\(Director\)|Người\s*báo\s*giá|\(Quotation\s*created\s*by\)|Khách\s*hàng|\(Customer\)|XÁC\s*NHẬN\s*ĐẶT\s*HÀNG)\s*(?:<[^>]+>\s*)*<\/(?:p|div|td|th|li|h[1-6])>/iu', '', $s);
		$s = preg_replace('/<p[^>]*>\s*<strong>\s*Giám\s*đốc\s*<\/strong>\s*<\/p>/iu', '', $s);
		return trim($s);
	}

	public static function applySaveDefaults(Vtiger_Request $request) {
		$terms = $request->get('terms_conditions');
		if ($terms !== null) {
			$request->set('terms_conditions', self::stripSignatureFromTermsHtml($terms));
		}

		// New quotes / confirm: "Báo giá". Draft only when explicitly requested.
		$recordId = (int) $request->get('record');
		$mode = strtolower(trim((string) $request->get('mk_quote_save_mode')));
		if ($mode === 'confirm' || ($mode === '' && $recordId <= 0)) {
			$request->set('quotestage', self::resolveConfirmedQuoteStage());
		} elseif ($mode === 'draft') {
			$request->set('quotestage', self::resolveDraftQuoteStage());
		}

		if (trim((string) $request->get('mk_quote_date')) === '') {
			$request->set('mk_quote_date', date('Y-m-d'));
		}

		$subtotal = self::parseMoneyNumber($request->get('subtotal'));
		if ($subtotal <= 0) {
			$subtotal = self::parseMoneyNumber($request->get('pre_tax_total'));
		}
		if ($subtotal <= 0) {
			$subtotal = self::parseMoneyNumber($request->get('hdnSubTotal'));
		}
		if ($subtotal <= 0) {
			$subtotal = self::parseMoneyNumber($request->get('hdnGrandTotal'));
		}

		$vatPercent = self::parseMoneyNumber($request->get('mk_vat_percent'));
		if ($vatPercent <= 0) {
			// Prefer group tax % from inventory form when BA field is empty.
			foreach (array('tax1_group_percentage', 'tax2_group_percentage', 'tax3_group_percentage') as $taxPctKey) {
				$candidate = self::parseMoneyNumber($request->get($taxPctKey));
				if ($candidate > 0) {
					$vatPercent = $candidate;
					break;
				}
			}
		}
		if ($vatPercent <= 0 || $vatPercent > 100) {
			$vatPercent = self::DEFAULT_VAT_PERCENT;
			$request->set('mk_vat_percent', (string) $vatPercent);
		} else {
			$request->set('mk_vat_percent', (string) $vatPercent);
		}

		$vatAmount = self::parseMoneyNumber($request->get('mk_vat_amount'));
		if ($vatAmount <= 0) {
			foreach (array('tax1_group_amount', 'tax2_group_amount', 'tax3_group_amount') as $taxAmtKey) {
				$candidate = self::parseMoneyNumber($request->get($taxAmtKey));
				if ($candidate > 0) {
					$vatAmount = $candidate;
					break;
				}
			}
		}
		if ($vatAmount <= 0 && $subtotal > 0 && $vatPercent > 0) {
			$vatAmount = round($subtotal * $vatPercent / 100);
		}
		// Guard absurd VAT amounts (money formatting / wrong field bleed).
		if ($subtotal > 0 && $vatAmount > ($subtotal * 0.5)) {
			$vatAmount = round($subtotal * $vatPercent / 100);
		}
		$request->set('mk_vat_amount', (string) $vatAmount);

		// Keep inventory group-tax request fields aligned so tax_totalamount persists.
		if ($vatPercent > 0) {
			$request->set('tax1_group_percentage', (string) $vatPercent);
			$request->set('tax1_group_amount', (string) $vatAmount);
		}

		$grandTotal = self::parseMoneyNumber($request->get('total'));
		if ($grandTotal <= 0) {
			$grandTotal = self::parseMoneyNumber($request->get('hdnGrandTotal'));
		}
		if ($grandTotal <= 0 || ($subtotal > 0 && $grandTotal > ($subtotal * 2))) {
			$grandTotal = $subtotal + $vatAmount;
		}
		$request->set('total', (string) $grandTotal);
		$request->set('mk_amount_in_words', self::amountInWordsVi($grandTotal));
	}

	/**
	 * Prefer picklist value "Nháp"; fall back to Created/Draft if needed.
	 */
	public static function resolveDraftQuoteStage() {
		static $resolved = null;
		if ($resolved !== null) {
			return $resolved;
		}
		$candidates = array('Nháp', 'Created', 'Draft', 'Đã tạo');
		$resolved = self::resolveQuoteStageFromCandidates($candidates, 'Nháp');
		return $resolved;
	}

	/**
	 * Prefer "Báo giá"; fall back to legacy confirmed values if needed.
	 */
	public static function resolveConfirmedQuoteStage() {
		static $resolved = null;
		if ($resolved !== null) {
			return $resolved;
		}
		$candidates = array('Báo giá', 'Xác nhận', 'Accepted', 'Confirmed', 'Chấp nhận', 'Delivered');
		$resolved = self::resolveQuoteStageFromCandidates($candidates, 'Accepted');
		return $resolved;
	}

	public static function isDraftQuoteStage($stage) {
		$stage = trim((string) $stage);
		if ($stage === '') {
			return true;
		}
		$drafts = array('Nháp', 'Created', 'Draft', 'Đã tạo');
		foreach ($drafts as $draft) {
			if (strcasecmp($stage, $draft) === 0) {
				return true;
			}
		}
		return false;
	}

	public static function isConfirmedQuoteStage($stage) {
		if (self::isDraftQuoteStage($stage)) {
			return false;
		}
		$stage = trim((string) $stage);
		if ($stage === '') {
			return false;
		}
		$confirmed = array('Báo giá', 'Xác nhận', 'Accepted', 'Confirmed', 'Chấp nhận', 'Delivered');
		foreach ($confirmed as $value) {
			if (strcasecmp($stage, $value) === 0) {
				return true;
			}
		}
		return false;
	}

	public static function getQuoteStageDisplayLabel($stage) {
		$stage = trim((string) $stage);
		if ($stage === '') {
			return '';
		}
		$map = array(
			'Created' => 'Nháp',
			'Nháp' => 'Nháp',
			'Draft' => 'Nháp',
			'Đã tạo' => 'Nháp',
			'Báo giá' => 'Báo giá',
			'Accepted' => 'Báo giá',
			'Confirmed' => 'Báo giá',
			'Xác nhận' => 'Báo giá',
			'Chấp nhận' => 'Báo giá',
			'Delivered' => 'Báo giá',
		);
		if (isset($map[$stage])) {
			return $map[$stage];
		}
		foreach ($map as $key => $label) {
			if (strcasecmp($key, $stage) === 0 || strcasecmp($label, $stage) === 0) {
				return $label;
			}
		}
		return $stage;
	}

	/**
	 * Resolve quote reference for Sales Order (includes soft-deleted quotes).
	 *
	 * @param int $quoteId
	 * @return array|null keys: quote_id, quote_no, subject, deleted
	 */
	public static function resolveQuoteReference($quoteId) {
		$quoteId = (int) $quoteId;
		if ($quoteId <= 0) {
			return null;
		}
		$db = PearDatabase::getInstance();
		$rs = $db->pquery(
			'SELECT q.quoteid, q.quote_no, q.subject, ce.deleted
			 FROM vtiger_quotes q
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = q.quoteid
			 WHERE q.quoteid = ?
			 LIMIT 1',
			array($quoteId)
		);
		if (!$rs || $db->num_rows($rs) <= 0) {
			return null;
		}
		$quoteNo = trim(decode_html((string) $db->query_result($rs, 0, 'quote_no')));
		$subject = trim(decode_html((string) $db->query_result($rs, 0, 'subject')));
		if ($quoteNo === '') {
			$quoteNo = 'BG' . str_pad((string) $quoteId, 5, '0', STR_PAD_LEFT);
		}
		return array(
			'quote_id' => (int) $db->query_result($rs, 0, 'quoteid'),
			'quote_no' => $quoteNo,
			'subject' => $subject,
			'deleted' => (int) $db->query_result($rs, 0, 'deleted'),
		);
	}

	/**
	 * True when a non-deleted Sales Order points at this quote.
	 */
	public static function hasActiveSalesOrderForQuote($quoteId) {
		$quoteId = (int) $quoteId;
		if ($quoteId <= 0) {
			return false;
		}
		$db = PearDatabase::getInstance();
		$rs = $db->pquery(
			'SELECT so.salesorderid
			 FROM vtiger_salesorder so
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid
			 WHERE ce.deleted = 0 AND so.quoteid = ?
			 LIMIT 1',
			array($quoteId)
		);
		return ($rs && $db->num_rows($rs) > 0);
	}

	public static function buildQuoteRefDetailUrl($quoteId) {
		$quoteId = (int) $quoteId;
		if ($quoteId <= 0) {
			return '';
		}
		return 'index.php?module=Quotes&view=Detail&record=' . $quoteId . '&app=SALES&mk_so_ref=1';
	}

	/**
	 * Safe HTML for SO inline "Tham chiếu báo giá" field.
	 */
	public static function buildQuoteRefInlineHtml($quoteId) {
		$ref = self::resolveQuoteReference($quoteId);
		if (!$ref) {
			return '—';
		}
		$url = self::buildQuoteRefDetailUrl($ref['quote_id']);
		$label = htmlspecialchars($ref['quote_no'], ENT_QUOTES, 'UTF-8');
		$urlEsc = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
		return '<a class="mk-so-quote-ref-link" href="' . $urlEsc . '" title="Xem báo giá gốc">' . $label . '</a>';
	}

	protected static function resolveQuoteStageFromCandidates(array $candidates, $fallback) {
		try {
			$moduleModel = Vtiger_Module_Model::getInstance('Quotes');
			$fieldModel = $moduleModel ? Vtiger_Field_Model::getInstance('quotestage', $moduleModel) : null;
			$values = ($fieldModel && method_exists($fieldModel, 'getPicklistValues'))
				? $fieldModel->getPicklistValues()
				: array();
			if (is_array($values) && !empty($values)) {
				foreach ($candidates as $candidate) {
					if (isset($values[$candidate])) {
						return $candidate;
					}
					foreach ($values as $key => $label) {
						if (strcasecmp((string) $key, $candidate) === 0 || strcasecmp((string) $label, $candidate) === 0) {
							return (string) $key;
						}
					}
				}
			}
		} catch (Exception $e) {
			// fall through
		}
		return $fallback;
	}
}
