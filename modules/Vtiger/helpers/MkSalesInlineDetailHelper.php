<?php
/**
 * Shared builders for Sales list inline detail panels (Accounts / Leads / Opp / Contacts).
 */
class Vtiger_MkSalesInlineDetailHelper {

	/** Canonical tag key → Vietnamese label (aligned with LeadsLovableRef TAG_META). */
	protected static $tagLabels = array(
		'facebook' => 'Facebook',
		'tiktok' => 'TikTok',
		'website' => 'Website',
		'zalo' => 'Zalo',
		'hotline' => 'Hotline',
		'other' => 'Khác',
		'other_source' => 'Khác',
		'ladipage_fb' => 'Ladipage FB',
		'chua_hoc' => 'Chưa học',
		'da_hoc' => 'Đã học',
		'mien_phi_online' => 'Miễn phí Online',
		'mien_phi_offline' => 'Miễn phí Offline',
		'pcth' => 'PCTH',
		'van_hanh' => 'Vận hành',
		'mkt' => 'Marketing',
		'lop_khac' => 'Lớp khác',
		'nhuong_quyen' => 'Nhượng quyền',
		'nguyen_lieu_chuoi' => 'NL chuỗi',
		'mua_lan_dau' => 'Mua lần đầu',
		'mua_lai' => 'Mua lại',
		'khong_mua' => 'Không mua',
		'ngung_mua' => 'Ngưng mua',
		'mua_on_dinh' => 'Mua ổn định',
		'tiem_nang' => 'Tiềm năng',
		'dang_cham_soc' => 'Đang chăm sóc',
		'dang_tu_van' => 'Đang tư vấn',
		'kh_can_nhac' => 'KH cân nhắc',
		'vang' => 'Vàng',
		'bac' => 'Bạc',
		'dong' => 'Đồng',
		'kv1' => 'Khu vực 1',
		'kv2' => 'Khu vực 2',
		'kv3' => 'Khu vực 3',
		'individual' => 'Cá nhân',
		'company' => 'Công ty',
		'co_quan' => 'Có quán',
		'chuan_bi_mo' => 'Chuẩn bị mở',
		'gia_dinh' => 'Gia đình',
		'moi_quen' => 'Mới quen',
		'da_co_quan_he' => 'Đã có quan hệ',
		'chua_mqbh' => 'Chưa MQBH',
		'da_tg_free' => 'Đã TG Free',
		'da_tg_fb1' => 'Đã TG FB1',
		'thu_3' => 'Thứ 3',
		'da_ky_quy' => 'Đã ký quỹ',
		'xac_nhan_tham_gia' => 'Xác nhận tham gia',
		'khong_xac_nhan_tham_gia' => 'Không tham gia',
		'l1' => 'L1',
		'l2' => 'L2',
		'chua_990k' => 'Chưa 990k',
		'da_990k' => 'Đã 990k',
		'lop_online' => 'Lớp online',
		'moi_lai' => 'Mời lại',
		'doi_lich' => 'Dời lịch',
		'khong_nghe_may' => 'Không nghe máy',
		'thue_bao' => 'Thuê bao',
		'trung_so' => 'Trùng số',
		'ngung_cham_soc' => 'Ngừng chăm sóc',
		'hoan_tien_lop_hoc' => 'Hoàn tiền lớp học',
		'khong_hoc' => 'Không học',
		'da_pcth' => 'Đã PCTH',
		'chua_pcth' => 'Chưa PCTH',
		'ca_nhan' => 'Cá nhân',
		'dung_cham_soc' => 'Dừng chăm sóc',
		'mua_it_lai' => 'Mua ít lại',
		'mien_bac' => 'Miền Bắc',
		'tham_khao' => 'Tham khảo',
		'khong_du_tai_chinh' => 'Không đủ tài chính',
		'ngu' => 'ngu',
		'chua_mqbb' => 'Chưa MQBB',
		'da_mqbb' => 'Đã MQBB',
		'da_mqbb_chua_pcth' => 'Đã MQBB + Chưa PCTH',
		'da_mqbb_da_pcth' => 'Đã MQBB + Đã PCTH',
		'chua_mqbb_chua_pcth' => 'Chưa MQBB + Chưa PCTH',
		'chua_mqbb_da_pcth' => 'Chưa MQBB + Đã PCTH',
	);

	protected static $tagAliases = array(
		'gold' => 'vang',
		'silver' => 'bac',
		'bronze' => 'dong',
		'ca_nhan' => 'individual',
	);

	public static function decodeText($text) {
		$text = decode_html((string) $text);
		$flags = ENT_QUOTES;
		if (defined('ENT_HTML5')) {
			$flags |= ENT_HTML5;
		}
		$text = html_entity_decode($text, $flags, 'UTF-8');
		// Second pass covers double-encoded entities (Ng&amp;ocirc; → Ngô)
		$text = html_entity_decode($text, $flags, 'UTF-8');
		return trim(strip_tags($text));
	}

	public static function buildFieldEntry(Vtiger_Module_Model $moduleModel, Vtiger_Record_Model $recordModel, $fieldName, $label) {
		$fieldModel = $moduleModel->getField($fieldName);
		if (!$fieldModel || !$fieldModel->isViewable()) {
			return null;
		}
		$value = self::decodeText($recordModel->getDisplayValue($fieldName));
		if ($value === '') {
			$value = '—';
		}
		$dataType = $fieldModel->getFieldDataType();
		$rawValue = $recordModel->get($fieldName);
		$editValue = $rawValue;
		if ($dataType === 'date' || $dataType === 'datetime') {
			$editValue = $fieldModel->getUITypeModel()->getDisplayValue($rawValue);
		}
		$picklistValues = array();
		if ($dataType === 'picklist') {
			$picklistValues = $fieldModel->getPicklistValues();
		}
		$readOnlyFields = array('smcreatorid', 'created_user_id', 'createdtime', 'modifiedtime', 'modifiedby');
		$nonInlineTypes = array('reference', 'multireference', 'image', 'file');
		return array(
			'name' => $fieldName,
			'label' => $label,
			'value' => $value,
			'raw_value' => $editValue,
			'data_type' => $dataType,
			'editable' => $fieldModel->isEditable()
				&& !in_array($fieldName, $readOnlyFields, true)
				&& !in_array($dataType, $nonInlineTypes, true),
			'picklist_values' => $picklistValues,
		);
	}

	/**
	 * @param array $candidates list of array($fieldName, $label)
	 */
	public static function buildFields(Vtiger_Module_Model $moduleModel, Vtiger_Record_Model $recordModel, array $candidates) {
		$fields = array();
		foreach ($candidates as $pair) {
			$entry = self::buildFieldEntry($moduleModel, $recordModel, $pair[0], $pair[1]);
			if ($entry) {
				$fields[] = $entry;
			}
		}
		return $fields;
	}

	public static function normalizeTagKey($tagName) {
		$s = self::decodeText($tagName);
		if ($s === '') {
			return '';
		}
		if (isset($s[0]) && $s[0] === '#') {
			$s = substr($s, 1);
		}
		$s = mb_strtolower($s, 'UTF-8');
		$s = str_replace(array('đ', 'Đ'), array('d', 'd'), $s);
		if (function_exists('transliterator_transliterate')) {
			$s = transliterator_transliterate('Any-Latin; Latin-ASCII', $s);
		}
		$s = preg_replace('/[^a-z0-9]+/', '_', $s);
		$s = trim($s, '_');
		if (isset(self::$tagAliases[$s])) {
			$s = self::$tagAliases[$s];
		}
		if (preg_match('/^\d{1,2}$/', $s)) {
			return 'goi_lan_' . (int) $s;
		}
		if (preg_match('/^goi_lan_(\d+)$/', $s, $m)) {
			return 'goi_lan_' . (int) $m[1];
		}
		return $s;
	}

	public static function labelForTag($key, $fallback = '') {
		$key = self::normalizeTagKey($key);
		if ($key !== '' && isset(self::$tagLabels[$key])) {
			return self::$tagLabels[$key];
		}
		if (preg_match('/^goi_lan_(\d+)$/', $key, $m)) {
			return 'Gọi lần ' . (int) $m[1];
		}
		if (preg_match('/^kv([123])$/', $key, $m)) {
			return 'Khu vực ' . $m[1];
		}
		$fallback = self::decodeText($fallback);
		return $fallback !== '' ? $fallback : $key;
	}

	public static function cssClassForTag($key) {
		if ($key === '') {
			return 'mk-tag mk-tag--other';
		}
		if (preg_match('/^goi_lan_(\d+)$/', $key, $m)) {
			$n = min((int) $m[1], 3);
			return 'mk-tag mk-tag--goi-lan-' . $n;
		}
		// Known palette classes use kebab-case; unknown → --other so luôn có pill UI.
		$known = array(
			'facebook', 'tiktok', 'website', 'zalo', 'hotline', 'other', 'other_source', 'ladipage_fb',
			'chua_hoc', 'da_hoc', 'mien_phi_online', 'mien_phi_offline', 'pcth', 'van_hanh', 'mkt', 'lop_khac',
			'nhuong_quyen', 'nguyen_lieu_chuoi', 'mua_lan_dau', 'mua_lai', 'khong_mua', 'ngung_mua', 'mua_on_dinh',
			'tiem_nang', 'dang_cham_soc', 'dang_tu_van', 'kh_can_nhac', 'vang', 'bac', 'dong',
			'kv1', 'kv2', 'kv3', 'individual', 'company', 'ca_nhan', 'co_quan', 'chuan_bi_mo', 'gia_dinh',
			'moi_quen', 'da_co_quan_he', 'chua_mqbh', 'da_tg_free', 'da_tg_fb1', 'thu_3', 'da_ky_quy',
			'xac_nhan_tham_gia', 'khong_xac_nhan_tham_gia', 'l1', 'l2', 'chua_990k', 'da_990k',
			'lop_online', 'moi_lai', 'doi_lich', 'khong_nghe_may', 'thue_bao', 'trung_so', 'ngung_cham_soc',
			'hoan_tien_lop_hoc', 'khong_hoc', 'da_pcth', 'chua_pcth',
			'chua_mqbb', 'da_mqbb', 'da_mqbb_chua_pcth', 'da_mqbb_da_pcth', 'chua_mqbb_chua_pcth', 'chua_mqbb_da_pcth',
			'dung_cham_soc', 'tham_khao', 'khong_du_tai_chinh',
		);
		if (!in_array($key, $known, true)) {
			return 'mk-tag mk-tag--other';
		}
		if ($key === 'ca_nhan') {
			return 'mk-tag mk-tag--individual';
		}
		if ($key === 'xac_nhan_tham_gia') {
			return 'mk-tag mk-tag--xac-nhan';
		}
		if ($key === 'khong_xac_nhan_tham_gia') {
			return 'mk-tag mk-tag--khong-xac-nhan';
		}
		if ($key === 'ladipage_fb') {
			return 'mk-tag mk-tag--ladipage';
		}
		if ($key === 'mien_phi_online') {
			return 'mk-tag mk-tag--free-online';
		}
		if ($key === 'mien_phi_offline') {
			return 'mk-tag mk-tag--free-offline';
		}
		$slug = str_replace('_', '-', $key);
		return 'mk-tag mk-tag--' . $slug;
	}

	/**
	 * @return array list of array(name, key, label, cls)
	 */
	public static function buildInlineTags($moduleName, $recordId) {
		$recordId = (int) $recordId;
		if ($recordId <= 0 || $moduleName === '') {
			return array();
		}
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$tagModels = Vtiger_Tag_Model::getAllAccessible($currentUser->getId(), $moduleName, $recordId);
		$tags = array();
		$seen = array();
		foreach ($tagModels as $tagModel) {
			$raw = self::decodeText($tagModel->getName());
			if ($raw === '') {
				continue;
			}
			$key = self::normalizeTagKey($raw);
			$dedupe = $key !== '' ? $key : mb_strtolower($raw, 'UTF-8');
			if (isset($seen[$dedupe])) {
				continue;
			}
			$seen[$dedupe] = true;
			$tags[] = array(
				'name' => $raw,
				'key' => $key,
				'label' => self::labelForTag($key, $raw),
				'cls' => self::cssClassForTag($key),
			);
		}
		return $tags;
	}

	public static function assignCommon(Vtiger_Viewer $viewer, Vtiger_Record_Model $recordModel, $moduleName, $app, array $infoFields, $title, $subtitle = '') {
		$app = $app !== '' ? $app : 'SALES';
		$title = self::decodeText($title);
		$subtitle = self::decodeText($subtitle);
		$notes = self::decodeText($recordModel->get('description'));
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('USER_MODEL', $currentUser);
		$viewer->assign('INLINE_TITLE', $title !== '' ? $title : '—');
		$viewer->assign('INLINE_SUBTITLE', $subtitle);
		$viewer->assign('INLINE_NOTES', $notes);
		$viewer->assign('INLINE_EDIT_URL', $recordModel->getEditViewUrl() . '&app=' . $app);
		$viewer->assign('INLINE_DETAIL_URL', $recordModel->getDetailViewUrl() . '&app=' . $app);
		$viewer->assign('INLINE_INFO_FIELDS', $infoFields);
		$viewer->assign('INLINE_ASSIGNED_USERS', $currentUser->getAccessibleUsersForModule($moduleName));
		$viewer->assign('INLINE_TAGS', self::buildInlineTags($moduleName, $recordModel->getId()));
	}
}
