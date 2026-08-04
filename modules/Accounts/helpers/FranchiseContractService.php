<?php
/*+***********************************************************************************
 * Tuibao (Accounts) — Hợp đồng nhượng quyền TUI BAO.
 * Layout: DOCX template gốc. Data: buildContext (CRM fields). Export/Preview: Word.
 *************************************************************************************/

class Accounts_FranchiseContractService_Helper {

	const BLANK = '………………';
	const BLOCK_LABEL = 'LBL_TB_FRANCHISE_CONTRACT';

	/** Template file (copy of client Word gốc + ${placeholders}). */
	const TEMPLATE_REL = 'modules/Accounts/resources/templates/HopDong_NhuongQuyen_TUIBAO.docx';

	/**
	 * Ensure franchise block + custom fields on vtiger_accountscf.
	 */
	/** Ensure franchise field set on Accounts. */
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
			array('tb_sc_customer_id', 'Khách hàng nhượng quyền (SC)', 7, 'VARCHAR(32)', 'V~O', false),
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
	 * @param bool $showOnEdit true = Create/Edit+Detail; false = Detail only
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

		$feeDeposit = self::toFloat($get('tb_fee_deposit'));
		if ($feeDeposit <= 0) {
			$feeDeposit = self::toFloat($get('tb_pay_1'));
		}
		$pay1 = $feeDeposit;
		$pay2 = $feeFranchise;
		$pay3 = max(0, $feeTotal - $feeFranchise);

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

	/** Absolute path to DOCX template. */
	public static function templatePath() {
		$rel = self::TEMPLATE_REL;
		if (is_file($rel)) {
			return $rel;
		}
		// modules/Accounts/helpers → CRM root
		$root = dirname(dirname(dirname(__FILE__)));
		$alt = $root . '/' . $rel;
		if (is_file($alt)) {
			return $alt;
		}
		return $rel;
	}

	/**
	 * Build filled .docx binary from template + CRM context.
	 *
	 * @param Vtiger_Record_Model|CRMEntity $record
	 * @return string
	 */
	public static function buildDocxBinary($record) {
		$template = self::templatePath();
		if (!is_file($template)) {
			throw new AppException('Không tìm thấy file template hợp đồng Word: ' . self::TEMPLATE_REL);
		}
		if (!class_exists('ZipArchive')) {
			throw new AppException('ZipArchive không khả dụng — không thể tạo file Word.');
		}

		$ctx = self::buildContext($record);
		return self::fillDocxTemplate($template, $ctx);
	}

	/**
	 * Replace ${key} in all OPC XML parts of the template.
	 *
	 * @param string $templatePath
	 * @param array $ctx
	 * @return string binary docx
	 */
	protected static function fillDocxTemplate($templatePath, array $ctx) {
		// Longest keys first (avoid partial collisions — none expected with ${_})
		$keys = array_keys($ctx);
		usort($keys, function ($a, $b) {
			return strlen($b) - strlen($a);
		});

		$replacements = array();
		foreach ($keys as $key) {
			$val = (string) $ctx[$key];
			// Word XML text must escape XML specials
			$val = htmlspecialchars($val, ENT_QUOTES | ENT_XML1, 'UTF-8');
			$replacements['${' . $key . '}'] = $val;
		}

		$tmp = tempnam(sys_get_temp_dir(), 'mk_fc_');
		if ($tmp === false) {
			throw new AppException('Không tạo được file tạm cho Word.');
		}
		$outPath = $tmp . '.docx';
		@unlink($outPath);
		if (!@copy($templatePath, $outPath)) {
			@unlink($tmp);
			throw new AppException('Không copy được template Word.');
		}
		@unlink($tmp);

		$zip = new ZipArchive();
		if ($zip->open($outPath) !== true) {
			@unlink($outPath);
			throw new AppException('Không mở được template Word.');
		}

		for ($i = 0; $i < $zip->numFiles; $i++) {
			$stat = $zip->statIndex($i);
			if (empty($stat['name'])) {
				continue;
			}
			$name = $stat['name'];
			// Only text parts that may hold placeholders
			if (!preg_match('#\.(xml|rels)$#i', $name)) {
				continue;
			}
			if (strpos($name, 'word/') !== 0 && strpos($name, 'docProps/') !== 0) {
				// still process word/* mainly
				if (strpos($name, 'word/') !== 0) {
					continue;
				}
			}
			$raw = $zip->getFromIndex($i);
			if ($raw === false || $raw === '') {
				continue;
			}
			// Skip binary-ish
			if (strpos($raw, '${') === false) {
				continue;
			}
			$new = str_replace(array_keys($replacements), array_values($replacements), $raw);
			// Broken split placeholders: ${ + key + } across runs — try merge common case
			if (strpos($new, '${') !== false) {
				$new = self::fixSplitPlaceholders($new, $ctx);
			}
			if ($new !== $raw) {
				$zip->deleteName($name);
				$zip->addFromString($name, $new);
			}
		}
		$zip->close();

		$binary = @file_get_contents($outPath);
		@unlink($outPath);
		if ($binary === false || $binary === '') {
			throw new AppException('Không đọc được file Word sau khi merge.');
		}
		return $binary;
	}

	/**
	 * Repair ${key} when Word split across <w:t> nodes.
	 */
	protected static function fixSplitPlaceholders($xml, array $ctx) {
		// Collapse ${ ... } when only tags/whitespace between
		$xml = preg_replace_callback(
			'/\$\{(?:<[^>]+>|[^<])*?\}/u',
			function ($m) use ($ctx) {
				$inner = strip_tags($m[0]);
				$inner = html_entity_decode($inner, ENT_QUOTES, 'UTF-8');
				$inner = preg_replace('/\s+/u', '', $inner);
				if (preg_match('/^\$\{([a-z0-9_]+)\}$/', $inner, $mm)) {
					$key = $mm[1];
					if (array_key_exists($key, $ctx)) {
						return htmlspecialchars((string) $ctx[$key], ENT_QUOTES | ENT_XML1, 'UTF-8');
					}
				}
				return $m[0];
			},
			$xml
		);
		return $xml;
	}

	/**
	 * Download filled contract as .docx (print truth = Microsoft Word).
	 *
	 * @param Vtiger_Record_Model|CRMEntity $record
	 * @param string $fileName
	 * @param bool $inline Content-Disposition inline (stream for in-CRM viewer)
	 */
	public static function outputWord($record, $fileName, $inline = false) {
		$binary = self::buildDocxBinary($record);
		while (ob_get_level() > 0) {
			ob_end_clean();
		}
		header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
		$disp = $inline ? 'inline' : 'attachment';
		header('Content-Disposition: ' . $disp . '; filename="' . str_replace('"', '', $fileName) . '"');
		header('Content-Length: ' . strlen($binary));
		header('Cache-Control: private, max-age=0, must-revalidate');
		header('Pragma: public');
		echo $binary;
		return true;
	}

	/**
	 * Resolve soffice binary (Docker Debian: /usr/bin/soffice).
	 * @return string|null
	 */
	protected static function findSofficeBinary() {
		$candidates = array(
			'/usr/bin/soffice',
			'/usr/bin/libreoffice',
			'/usr/lib/libreoffice/program/soffice',
			'soffice',
			'libreoffice',
		);
		foreach ($candidates as $bin) {
			if ($bin === 'soffice' || $bin === 'libreoffice') {
				$which = @trim((string) shell_exec('command -v ' . escapeshellarg($bin) . ' 2>/dev/null'));
				if ($which !== '' && is_executable($which)) {
					return $which;
				}
				continue;
			}
			if (is_executable($bin)) {
				return $bin;
			}
		}
		return null;
	}

	/**
	 * Convert filled DOCX binary → PDF via LibreOffice headless.
	 *
	 * @param string $docxBinary
	 * @return string PDF binary
	 * @throws AppException
	 */
	protected static function convertDocxBinaryToPdf($docxBinary) {
		if ($docxBinary === '' || $docxBinary === null) {
			throw new AppException('File Word rỗng — không convert PDF.');
		}
		$soffice = self::findSofficeBinary();
		if ($soffice === null) {
			throw new AppException(
				'Chưa cài LibreOffice trên server (soffice). Rebuild Docker image (Dockerfile: libreoffice-writer) rồi thử lại, hoặc dùng Tải / in Word.'
			);
		}

		$base = tempnam(sys_get_temp_dir(), 'mk_fc_');
		if ($base === false) {
			throw new AppException('Không tạo được thư mục tạm cho convert PDF.');
		}
		@unlink($base);
		$workDir = $base . '_lo';
		if (!@mkdir($workDir, 0700, true)) {
			throw new AppException('Không tạo được thư mục tạm convert PDF.');
		}

		$docxPath = $workDir . '/contract.docx';
		$pdfPath = $workDir . '/contract.pdf';
		$ok = @file_put_contents($docxPath, $docxBinary);
		if ($ok === false || !is_file($docxPath)) {
			self::rrmdir($workDir);
			throw new AppException('Không ghi file Word tạm.');
		}

		// Unique user profile avoids concurrent soffice lock under www-data
		$profile = 'file://' . $workDir . '/lo_profile';
		@mkdir($workDir . '/lo_profile', 0700, true);

		$cmd = escapeshellarg($soffice)
			. ' --headless --nologo --nofirststartwizard --norestore'
			. ' -env:UserInstallation=' . escapeshellarg($profile)
			. ' --convert-to pdf --outdir ' . escapeshellarg($workDir)
			. ' ' . escapeshellarg($docxPath)
			. ' 2>&1';

		$out = array();
		$code = 0;
		@exec($cmd, $out, $code);
		$log = implode("\n", $out);

		// LibreOffice names output from input basename
		if (!is_file($pdfPath) || filesize($pdfPath) < 32) {
			// Sometimes extension casing / alternate name
			$found = glob($workDir . '/*.pdf');
			if (!empty($found) && is_file($found[0])) {
				$pdfPath = $found[0];
			}
		}

		if (!is_file($pdfPath) || filesize($pdfPath) < 32) {
			self::rrmdir($workDir);
			$hint = $log !== '' ? ' (' . substr(preg_replace('/\s+/', ' ', $log), 0, 200) . ')' : '';
			throw new AppException('LibreOffice convert PDF thất bại' . $hint . '. Dùng Tải / in Word.');
		}

		$pdf = @file_get_contents($pdfPath);
		self::rrmdir($workDir);
		if ($pdf === false || $pdf === '' || substr($pdf, 0, 4) !== '%PDF') {
			throw new AppException('File PDF sau convert không hợp lệ. Dùng Tải / in Word.');
		}
		return $pdf;
	}

	/**
	 * @param string $dir
	 */
	protected static function rrmdir($dir) {
		if (!is_dir($dir)) {
			return;
		}
		$items = @scandir($dir);
		if ($items === false) {
			@rmdir($dir);
			return;
		}
		foreach ($items as $item) {
			if ($item === '.' || $item === '..') {
				continue;
			}
			$path = $dir . DIRECTORY_SEPARATOR . $item;
			if (is_dir($path)) {
				self::rrmdir($path);
			} else {
				@unlink($path);
			}
		}
		@rmdir($dir);
	}

	/**
	 * In-CRM preview: PDF from the same filled DOCX as download (LibreOffice).
	 * Print truth remains: Tải Word → Microsoft Word.
	 *
	 * @param Vtiger_Record_Model|CRMEntity $record
	 * @param string $title unused (kept for call-site compat)
	 * @param bool $autoPrint unused
	 * @param string $streamUrl unused
	 */
	public static function outputWordPreviewPdf($record, $title = 'Hợp đồng nhượng quyền TUI BAO', $autoPrint = false, $streamUrl = '') {
		try {
			$docx = self::buildDocxBinary($record);
			$pdf = self::convertDocxBinaryToPdf($docx);
		} catch (Exception $e) {
			return self::outputPreviewErrorHtml($e->getMessage());
		} catch (Throwable $e) {
			return self::outputPreviewErrorHtml($e->getMessage());
		}

		while (ob_get_level() > 0) {
			ob_end_clean();
		}
		header('Content-Type: application/pdf');
		header('Content-Disposition: inline; filename="HopDong_NhuongQuyen_TUI_BAO_preview.pdf"');
		header('Content-Length: ' . strlen($pdf));
		header('Cache-Control: private, max-age=0, must-revalidate');
		header('Pragma: public');
		echo $pdf;
		return true;
	}

	/**
	 * Backward-compatible name — preview is PDF now.
	 */
	public static function outputWordPreviewHtml($record, $title = 'Hợp đồng nhượng quyền TUI BAO', $autoPrint = false, $streamUrl = '') {
		return self::outputWordPreviewPdf($record, $title, $autoPrint, $streamUrl);
	}

	/**
	 * @param string $message
	 */
	protected static function outputPreviewErrorHtml($message) {
		while (ob_get_level() > 0) {
			ob_end_clean();
		}
		header('Content-Type: text/html; charset=UTF-8');
		header('Cache-Control: no-store');
		$msg = htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8');
		echo '<!DOCTYPE html><html lang="vi"><head><meta charset="utf-8"/><title>Xem trước</title>'
			. '<style>body{font-family:system-ui,sans-serif;padding:32px;background:#fafafa;color:#18181b;line-height:1.5}'
			. '.box{max-width:520px;margin:40px auto;padding:24px;background:#fff;border:1px solid #e4e4e7;border-radius:10px}'
			. 'h1{font-size:18px;margin:0 0 12px}p{margin:0 0 10px;font-size:14px;color:#3f3f46}</style></head><body>'
			. '<div class="box"><h1>Không tạo được bản xem trước</h1>'
			. '<p>' . $msg . '</p>'
			. '<p>Vui lòng dùng <strong>Tải / in Word</strong> và mở bằng Microsoft Word để xem/in đúng format.</p>'
			. '</div></body></html>';
		return true;
	}

	/**
	 * Legacy name used by ExportFranchisePDF — preview PDF / download docx.
	 *
	 * @param Vtiger_Record_Model|CRMEntity $record
	 * @param string $fileName
	 * @param string $dest I = preview PDF, D = download docx
	 */
	public static function outputPdf($record, $fileName, $dest = 'I') {
		if ($dest === 'D') {
			$docxName = preg_replace('/\.pdf$/i', '.docx', $fileName);
			if (substr($docxName, -5) !== '.docx') {
				$docxName .= '.docx';
			}
			return self::outputWord($record, $docxName);
		}
		return self::outputWordPreviewPdf($record, 'Hợp đồng nhượng quyền TUI BAO');
	}

	// —— display helpers (unchanged logic) ——

	protected static function loadMoneyDeps() {
		static $ok = false;
		if ($ok) {
			return;
		}
		$ok = true;
		@require_once 'modules/Quotes/helpers/QuoteBaService.php';
		@require_once 'modules/Quotes/helpers/QuoteExcelExport.php';
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
		self::loadMoneyDeps();
		$amount = (float) $amount;
		if ($amount <= 0) {
			return self::BLANK;
		}
		if (class_exists('Quotes_QuoteExcelExport_Helper')) {
			return Quotes_QuoteExcelExport_Helper::formatMoneyVnPublic($amount);
		}
		return number_format($amount, 0, ',', '.');
	}

	protected static function displayWords($amount) {
		self::loadMoneyDeps();
		$amount = (float) $amount;
		if ($amount <= 0) {
			return self::BLANK;
		}
		if (class_exists('Quotes_QuoteBaService_Helper')) {
			$words = Quotes_QuoteBaService_Helper::amountInWordsVi($amount);
			$words = trim((string) $words);
			if ($words !== '') {
				return $words;
			}
		}
		return self::BLANK;
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
