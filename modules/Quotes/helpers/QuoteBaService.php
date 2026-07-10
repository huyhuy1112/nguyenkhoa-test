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
		if (is_numeric($value)) {
			return (float) $value;
		}
		$normalized = preg_replace('/[^\d.,-]/', '', (string) $value);
		$normalized = str_replace(array('.', ','), array('', '.'), preg_replace('/\.(?=.*\.)/', '', $normalized));
		return (float) $normalized;
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

		// New quotes always start as Nháp.
		$recordId = (int) $request->get('record');
		if ($recordId <= 0) {
			$request->set('quotestage', self::resolveDraftQuoteStage());
		}

		if (trim((string) $request->get('mk_quote_date')) === '') {
			$request->set('mk_quote_date', date('Y-m-d'));
		}

		$subtotal = self::parseMoneyNumber($request->get('hdnSubTotal'));
		if ($subtotal <= 0) {
			$subtotal = self::parseMoneyNumber($request->get('hdnGrandTotal'));
		}
		$vatPercent = self::parseMoneyNumber($request->get('mk_vat_percent'));
		if ($vatPercent <= 0) {
			$vatPercent = self::DEFAULT_VAT_PERCENT;
			$request->set('mk_vat_percent', (string) $vatPercent);
		}
		$vatAmount = round($subtotal * $vatPercent / 100);
		$request->set('mk_vat_amount', (string) $vatAmount);

		$grandTotal = self::parseMoneyNumber($request->get('hdnGrandTotal'));
		if ($grandTotal <= 0) {
			$grandTotal = $subtotal + $vatAmount;
		}
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
		try {
			$moduleModel = Vtiger_Module_Model::getInstance('Quotes');
			$fieldModel = $moduleModel ? Vtiger_Field_Model::getInstance('quotestage', $moduleModel) : null;
			$values = ($fieldModel && method_exists($fieldModel, 'getPicklistValues'))
				? $fieldModel->getPicklistValues()
				: array();
			if (is_array($values) && !empty($values)) {
				foreach ($candidates as $candidate) {
					if (isset($values[$candidate])) {
						$resolved = $candidate;
						return $resolved;
					}
					foreach ($values as $key => $label) {
						if (strcasecmp((string) $key, $candidate) === 0 || strcasecmp((string) $label, $candidate) === 0) {
							$resolved = (string) $key;
							return $resolved;
						}
					}
				}
			}
		} catch (Exception $e) {
			// fall through
		}
		$resolved = 'Nháp';
		return $resolved;
	}
}
