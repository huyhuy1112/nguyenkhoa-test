<?php
/**
 * Đơn vị tính hàng hóa: một danh sách, lưu và hiện cùng một chữ.
 * Mã cũ (pcs, box, …) được đổi về chữ trong danh sách.
 */
class ProductsServices_UnitOptions_Helper {

	public static function canonical() {
		return array(
			'Cái', 'Bịch', 'Bao', 'Lon', 'Hộp', 'Hũ', 'Gói', 'Can',
			'Thùng', 'Bình', 'Chai', 'Cuộn', 'Con', 'Kg', 'Bộ', 'Lít',
		);
	}

	public static function options() {
		$out = array();
		foreach (self::canonical() as $label) {
			$out[$label] = $label;
		}
		return $out;
	}

	public static function normalize($value) {
		$value = trim(html_entity_decode((string) $value, ENT_QUOTES, 'UTF-8'));
		if ($value === '' || $value === '—' || $value === '-') {
			return '';
		}
		$fold = self::fold($value);
		$aliases = array(
			'pcs' => 'Cái',
			'piece' => 'Cái',
			'cai' => 'Cái',
			'box' => 'Hộp',
			'hop' => 'Hộp',
			'set' => 'Bộ',
			'bo' => 'Bộ',
			'kg' => 'Kg',
			'bich' => 'Bịch',
			'bao' => 'Bao',
			'lon' => 'Lon',
			'hu' => 'Hũ',
			'goi' => 'Gói',
			'can' => 'Can',
			'thung' => 'Thùng',
			'binh' => 'Bình',
			'chai' => 'Chai',
			'cuon' => 'Cuộn',
			'con' => 'Con',
			'lit' => 'Lít',
			'liter' => 'Lít',
			'litre' => 'Lít',
		);
		if (isset($aliases[$fold])) {
			return $aliases[$fold];
		}
		foreach (self::canonical() as $label) {
			if (self::fold($label) === $fold) {
				return $label;
			}
		}
		return $value;
	}

	public static function ensurePicklist() {
		static $done = false;
		if ($done) {
			return;
		}
		$done = true;
		try {
			require_once 'vtlib/Vtiger/Module.php';
			$module = Vtiger_Module::getInstance('ProductsServices');
			if (!$module) {
				return;
			}
			$field = Vtiger_Field::getInstance('unit', $module);
			if (!$field) {
				return;
			}
			$field->setPicklistValues(self::canonical());
		} catch (Exception $e) {
			return;
		}
	}

	protected static function fold($value) {
		$value = trim((string) $value);
		if (function_exists('mb_strtolower')) {
			$value = mb_strtolower($value, 'UTF-8');
		} else {
			$value = strtolower($value);
		}
		$from = array(
			'á','à','ả','ã','ạ','ă','ắ','ằ','ẳ','ẵ','ặ','â','ấ','ầ','ẩ','ẫ','ậ',
			'é','è','ẻ','ẽ','ẹ','ê','ế','ề','ể','ễ','ệ',
			'í','ì','ỉ','ĩ','ị',
			'ó','ò','ỏ','õ','ọ','ô','ố','ồ','ổ','ỗ','ộ','ơ','ớ','ờ','ở','ỡ','ợ',
			'ú','ù','ủ','ũ','ụ','ư','ứ','ừ','ử','ữ','ự',
			'ý','ỳ','ỷ','ỹ','ỵ','đ',
		);
		$to = array(
			'a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a',
			'e','e','e','e','e','e','e','e','e','e','e',
			'i','i','i','i','i',
			'o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o',
			'u','u','u','u','u','u','u','u','u','u','u',
			'y','y','y','y','y','d',
		);
		return str_replace($from, $to, $value);
	}
}
