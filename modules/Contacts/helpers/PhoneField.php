<?php
/*+***********************************************************************************
 * Contacts phone fields — normalize and validate digit length.
 *************************************************************************************/

class Contacts_PhoneField_Helper {

	const MAX_DIGITS = 10;

	const FIELD_NAMES = array(
		'phone',
		'mobile',
		'homephone',
		'otherphone',
		'fax',
		'assistantphone',
	);

	public static function normalizeValue($value) {
		$digits = preg_replace('/\D+/', '', (string) $value);
		if ($digits === '') {
			return '';
		}
		return substr($digits, 0, self::MAX_DIGITS);
	}

	public static function sanitizeRequest(Vtiger_Request $request) {
		foreach (self::FIELD_NAMES as $fieldName) {
			if (!$request->has($fieldName)) {
				continue;
			}
			$request->set($fieldName, self::normalizeValue($request->get($fieldName)));
		}
	}

	public static function validateRequest(Vtiger_Request $request) {
		foreach (self::FIELD_NAMES as $fieldName) {
			if (!$request->has($fieldName)) {
				continue;
			}
			$value = trim((string) $request->get($fieldName));
			if ($value === '') {
				continue;
			}
			$digits = preg_replace('/\D+/', '', $value);
			if (strlen($digits) > self::MAX_DIGITS) {
				throw new Exception('Số điện thoại chỉ được nhập tối đa 10 số.');
			}
		}
	}
}
