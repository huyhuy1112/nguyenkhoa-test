<?php
/*+***********************************************************************************
 * Catalog sản phẩm Khách hàng — khóa học Edubit + gói bán offline.
 * Rule: mua offline khóa nào → tặng online cùng course_id đó.
 *************************************************************************************/

class Contacts_ProductCatalog {

	/** Edubit course IDs đang dùng (bỏ nháp/test). */
	const COURSE_MQBB_990 = '29403';
	const COURSE_PCTH = '29218';
	const COURSE_PCTH_BASIC = '28108';
	const COURSE_FREE_OPP = '27312';

	/**
	 * Catalog khóa Online Edubit (đồng bộ admin Edubit).
	 * @return array
	 */
	public static function edubitCourses() {
		return array(
			array(
				'id' => self::COURSE_MQBB_990,
				'label' => 'KHÓA HỌC KHAI TRƯƠNG QUÁN BÀI BẢN',
				'short' => 'MQBB 990k',
				'price' => 990000,
				'price_label' => '990.000đ',
				'lane' => 'courses',
				'route' => 'contact',
				'is_free' => false,
			),
			array(
				'id' => self::COURSE_PCTH,
				'label' => 'KHÓA HỌC PHA CHẾ TỔNG HỢP',
				'short' => 'PCTH',
				'price' => 3000000,
				'price_label' => '3.000.000đ',
				'lane' => 'courses',
				'route' => 'contact',
				'is_free' => false,
			),
			array(
				'id' => self::COURSE_PCTH_BASIC,
				'label' => 'KHÓA HỌC PHA CHẾ TỔNG HỢP CƠ BẢN',
				'short' => 'PCTH Cơ bản',
				'price' => 299000,
				'price_label' => '299.000đ',
				'lane' => 'courses',
				'route' => 'contact',
				'is_free' => false,
			),
			array(
				'id' => self::COURSE_FREE_OPP,
				'label' => 'KHÓA HỌC PHA CHẾ KINH DOANH (Miễn Phí)',
				'short' => 'Online miễn phí',
				'price' => 0,
				'price_label' => 'Miễn phí',
				'lane' => 'courses',
				'route' => 'opportunity',
				'is_free' => true,
			),
		);
	}

	/**
	 * Gói bán offline trên Khách hàng (đăng ký học + tặng online cùng ID).
	 * @return array
	 */
	public static function sellableProducts() {
		return array(
			array(
				'code' => 'mqbb',
				'label' => 'Mở quán bài bản (990k)',
				'edubit_course_id' => self::COURSE_MQBB_990,
				'gift_online' => true,
				'lane' => 'courses',
				'class_codes' => array('mqbb'),
			),
			array(
				'code' => 'pcth',
				'label' => 'Pha chế tổng hợp',
				'edubit_course_id' => self::COURSE_PCTH,
				'gift_online' => true,
				'lane' => 'courses',
				'class_codes' => array('pcth'),
			),
			array(
				'code' => 'pcth_cb',
				'label' => 'Pha chế tổng hợp cơ bản',
				'edubit_course_id' => self::COURSE_PCTH_BASIC,
				'gift_online' => true,
				'lane' => 'courses',
				'class_codes' => array('pcth_cb'),
			),
			array(
				'code' => 'combo_mqbb_pcth',
				'label' => 'Combo MQBB + PCTH',
				'edubit_course_id' => '',
				'gift_online' => true,
				'lane' => 'courses',
				'class_codes' => array('mqbb', 'pcth'),
				'gift_course_ids' => array(self::COURSE_MQBB_990, self::COURSE_PCTH),
			),
		);
	}

	public static function courseById($courseId) {
		$id = trim((string) $courseId);
		foreach (self::edubitCourses() as $c) {
			if ($c['id'] === $id) {
				return $c;
			}
		}
		return null;
	}

	public static function productByCode($code) {
		$code = strtolower(trim((string) $code));
		foreach (self::sellableProducts() as $p) {
			if ($p['code'] === $code) {
				return $p;
			}
		}
		return null;
	}

	/**
	 * Offline class_code → course_id được tặng online.
	 */
	public static function giftCourseIdForClassCode($classCode) {
		$code = strtolower(trim((string) $classCode));
		$map = array(
			'mqbb' => self::COURSE_MQBB_990,
			'pcth' => self::COURSE_PCTH,
			'pcth_cb' => self::COURSE_PCTH_BASIC,
		);
		return isset($map[$code]) ? $map[$code] : '';
	}

	/**
	 * @return array course_id list to gift for a product/class registration
	 */
	public static function giftCourseIdsForProduct($productCode) {
		$p = self::productByCode($productCode);
		if (!$p) {
			$cid = self::giftCourseIdForClassCode($productCode);
			return $cid !== '' ? array($cid) : array();
		}
		if (!empty($p['gift_course_ids']) && is_array($p['gift_course_ids'])) {
			return $p['gift_course_ids'];
		}
		if (!empty($p['edubit_course_id'])) {
			return array($p['edubit_course_id']);
		}
		return array();
	}

	public static function isPaidContactCourse($courseId) {
		$id = trim((string) $courseId);
		return in_array($id, array(self::COURSE_MQBB_990, self::COURSE_PCTH, self::COURSE_PCTH_BASIC), true);
	}

	public static function isFreeOppCourse($courseId) {
		return trim((string) $courseId) === self::COURSE_FREE_OPP;
	}

	/**
	 * Payload cho UI / API.
	 */
	public static function catalogPayload() {
		return array(
			'edubit_courses' => self::edubitCourses(),
			'products' => self::sellableProducts(),
			'lanes' => array(
				array('id' => 'courses', 'label' => 'Khóa học'),
				array('id' => 'materials', 'label' => 'Nguyên liệu'),
				array('id' => 'franchise', 'label' => 'Nhượng quyền'),
			),
			'gift_rule' => 'Mua offline khóa nào → tặng online cùng course_id đó.',
		);
	}
}
