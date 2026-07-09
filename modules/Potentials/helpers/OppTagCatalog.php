<?php
/*+***********************************************************************************
 * Opportunity tag whitelist — BA Excel categories (Lead convert → Opp detail).
 *************************************************************************************/

class Potentials_OppTagCatalog {

	/** Canonical keys allowed on Opportunity (from BA Excel). */
	protected static $allowedKeys = array(
		// Khu vực
		'kv1', 'kv2', 'kv3',
		// Nguồn data
		'facebook', 'tiktok', 'ladipage_fb', 'website', 'zalo', 'other', 'other_source',
		// Dạng khách hàng
		'individual', 'company', 'co_quan', 'chuan_bi_mo', 'gia_dinh',
		// Tag lớp học / intent
		'mien_phi_online', 'mien_phi_offline', 'da_tg_free', 'chua_hoc', 'da_hoc',
		'pcth', 'van_hanh', 'mkt', 'lop_khac', 'nguyen_lieu_chuoi',
		// Tag nguyên liệu
		'mua_lan_dau', 'mua_lai', 'dang_cham_soc', 'dang_tu_van', 'kh_can_nhac', 'khong_mua', 'ngung_mua',
		// Tag nhượng quyền
		'nhuong_quyen', 'da_ky_quy',
		// Xác nhận tham gia
		'xac_nhan_tham_gia', 'khong_xac_nhan_tham_gia',
		// Hạng khách (tier)
		'vang', 'bac', 'dong',
	);

	protected static $aliases = array(
		'gold' => 'vang',
		'silver' => 'bac',
		'bronze' => 'dong',
		'vàng' => 'vang',
		'bạc' => 'bac',
		'đồng' => 'dong',
		'ladipage_fb' => 'ladipage_fb',
		'ladipage' => 'ladipage_fb',
		'da_co_quan' => 'co_quan',
		'chi_moi_quan' => 'chuan_bi_mo',
		'da_tg_free' => 'da_tg_free',
		'nhuong_quyen' => 'nhuong_quyen',
		'nhượng_quyền' => 'nhuong_quyen',
		'mua_lan_dau' => 'mua_lan_dau',
		'mua_lại' => 'mua_lai',
		'khong_mua' => 'khong_mua',
		'ngung_mua' => 'ngung_mua',
		'zalo' => 'zalo',
		'facebook' => 'facebook',
		'tiktok' => 'tiktok',
		'pcth' => 'pcth',
		'company' => 'company',
		'individual' => 'individual',
	);

	public static function normalizeKey($tagName) {
		$s = trim(decode_html((string)$tagName));
		if ($s === '') {
			return '';
		}
		if ($s[0] === '#') {
			$s = substr($s, 1);
		}
		$s = mb_strtolower($s, 'UTF-8');
		$s = str_replace(array('đ', 'Đ'), array('d', 'd'), $s);
		if (function_exists('transliterator_transliterate')) {
			$s = transliterator_transliterate('Any-Latin; Latin-ASCII', $s);
		}
		$s = preg_replace('/[^a-z0-9]+/', '_', $s);
		$s = trim($s, '_');
		if ($s === '') {
			return '';
		}
		if (isset(self::$aliases[$s])) {
			return self::$aliases[$s];
		}
		return $s;
	}

	public static function isAllowed($tagName) {
		$key = self::normalizeKey($tagName);
		if ($key === '') {
			return false;
		}
		if (preg_match('/^goi_lan_\d+$/', $key)) {
			return false;
		}
		return in_array($key, self::$allowedKeys, true);
	}

	/**
	 * @param array $tagModels id => Vtiger_Tag_Model
	 * @return array tag ids allowed for Opportunity
	 */
	public static function filterTagModelIds(array $tagModels) {
		$ids = array();
		foreach ($tagModels as $tagId => $tagModel) {
			$name = is_object($tagModel) && method_exists($tagModel, 'getName')
				? $tagModel->getName()
				: (string)$tagModel;
			if (self::isAllowed($name)) {
				$ids[] = (int)$tagId;
			}
		}
		return array_values(array_unique(array_filter($ids)));
	}

	public static function filterTagNames(array $tagNames) {
		$out = array();
		foreach ($tagNames as $name) {
			if (self::isAllowed($name)) {
				$out[] = (string)$name;
			}
		}
		return array_values(array_unique($out));
	}
}
