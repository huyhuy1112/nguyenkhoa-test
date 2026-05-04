<?php
/**
 * Campaign phase count: max(stored count, highest meaningful phase), trim trailing empty, clamp 2–5.
 */

class Campaigns_CampaignPhase_Helper {

	/**
	 * @param array $data Field name => value (getData() or column_fields)
	 */
	public static function phaseHasMeaningfulData(array $data, $phaseIndex) {
		$i = (int) $phaseIndex;
		if ($i < 1 || $i > 5) {
			return false;
		}
		$exp = self::getField($data, "phase{$i}_expected");
		$act = self::getField($data, "phase{$i}_actual");
		if (self::numericMeaningful($exp) || self::numericMeaningful($act)) {
			return true;
		}
		if (self::textMeaningful(self::getField($data, "phase{$i}_comment"))) {
			return true;
		}
		if (self::dateMeaningful(self::getField($data, "phase{$i}_start_date"))) {
			return true;
		}
		if (self::dateMeaningful(self::getField($data, "phase{$i}_end_date"))) {
			return true;
		}
		return false;
	}

	protected static function getField(array $data, $name) {
		return isset($data[$name]) ? $data[$name] : null;
	}

	protected static function numericMeaningful($v) {
		if ($v === '' || $v === null || $v === false) {
			return false;
		}
		if (is_string($v)) {
			$v = trim($v);
			if ($v === '') {
				return false;
			}
		}
		if (!is_numeric($v)) {
			return false;
		}
		return abs((float) $v) > 0.0000001;
	}

	protected static function textMeaningful($v) {
		if ($v === '' || $v === null || $v === false) {
			return false;
		}
		return trim((string) $v) !== '';
	}

	protected static function dateMeaningful($v) {
		if ($v === '' || $v === null || $v === false) {
			return false;
		}
		$s = trim((string) $v);
		if ($s === '' || preg_match('/^0000-00-00/', $s)) {
			return false;
		}
		return true;
	}

	public static function highestMeaningfulPhaseIndex(array $data) {
		$h = 0;
		for ($i = 1; $i <= 5; $i++) {
			if (self::phaseHasMeaningfulData($data, $i)) {
				$h = $i;
			}
		}
		return $h;
	}

	/**
	 * Stored count clamped 2–5, merged with meaningful phases; trailing empty phases trimmed (but never below 2).
	 */
	public static function effectivePhaseCount(array $data) {
		$s = isset($data['campaign_phase_count']) ? (int) $data['campaign_phase_count'] : 2;
		if ($s < 2) {
			$s = 2;
		}
		if ($s > 5) {
			$s = 5;
		}
		$h = self::highestMeaningfulPhaseIndex($data);
		$N = max(2, $s, $h);
		if ($N > 5) {
			$N = 5;
		}
		while ($N > 2 && !self::phaseHasMeaningfulData($data, $N) && $N > $h) {
			$N--;
		}
		return $N;
	}

	public static function phaseIndicesFromData(array $data) {
		$n = self::effectivePhaseCount($data);
		$indices = array();
		for ($i = 1; $i <= $n; $i++) {
			$indices[] = $i;
		}
		return $indices;
	}

	public static function phaseIndicesFromRecord(Vtiger_Record_Model $recordModel) {
		return self::phaseIndicesFromData($recordModel->getData());
	}
}
