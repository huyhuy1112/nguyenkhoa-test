<?php
/*+***********************************************************************************
 * Contact tag whitelist — BA Excel (Lead convert → Contact).
 * Chỉ các cột: Hạng KH, Tag lớp học, Tag NL, Tag NQ, Hạng thành viên.
 *************************************************************************************/

class Contacts_ContactTagCatalog {

	/** Canonical keys allowed on Contact (from BA Excel). */
	protected static $allowedKeys = array(
		// Hạng Khách Hàng
		'moi_quen', 'da_co_quan_he',
		// Tag lớp học (Contact)
		'chua_mqbh', 'da_tg_free',
		// Tag nguyên liệu
		'mua_lan_dau', 'mua_lai', 'dang_cham_soc', 'kh_can_nhac', 'khong_mua', 'ngung_mua',
		// Tag nhượng quyền
		'nhuong_quyen', 'da_ky_quy', 'dang_tu_van',
		// Hạng thành viên (tier)
		'vang', 'bac', 'dong',
	);

	protected static $aliases = array(
		'gold' => 'vang',
		'silver' => 'bac',
		'bronze' => 'dong',
		'vàng' => 'vang',
		'bạc' => 'bac',
		'đồng' => 'dong',
		'ch_moi_quen' => 'moi_quen',
		'ch - moi quen' => 'moi_quen',
		'chi_moi_quen' => 'moi_quen',
		'chi moi quen' => 'moi_quen',
		'chỉ mới quen' => 'moi_quen',
		'co_quan_he' => 'da_co_quan_he',
		'đã có quan hệ' => 'da_co_quan_he',
		'chua mqbh' => 'chua_mqbh',
		'chua_mqhh' => 'chua_mqbh',
		'chua mqhh' => 'chua_mqbh',
		'đã tg free' => 'da_tg_free',
		'đã từng free' => 'da_tg_free',
		'da_tung_free' => 'da_tg_free',
		'da_tg_free' => 'da_tg_free',
		'nhuong_quyen' => 'nhuong_quyen',
		'nhượng_quyền' => 'nhuong_quyen',
		'da_ky_quy' => 'da_ky_quy',
		'đã ký quỹ' => 'da_ky_quy',
		'dang_tu_van' => 'dang_tu_van',
		'đang tư vấn' => 'dang_tu_van',
		'mua_lan_dau' => 'mua_lan_dau',
		'mua_lại' => 'mua_lai',
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
	 * @return array tag ids allowed for Contact
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

	public static function getAllowedKeys() {
		return self::$allowedKeys;
	}
}
