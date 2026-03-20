<?php
/*+***********************************************************************************
 * Campaigns_CampaignProgress
 * - Calculate time and result progress for a Campaign record
 *************************************************************************************/

require_once 'include/utils/utils.php';
require_once 'include/utils/DateTimeField.php';

class Campaigns_CampaignProgress {

	public static function getTimeProgressPercent(Vtiger_Record_Model $record) {
		$start = $record->get('start_date');
		$end   = $record->get('closingdate');

		if (empty($start) || empty($end)) {
			return null;
		}

		$startDb = DateTimeField::convertToDBFormat($start);
		$endDb   = DateTimeField::convertToDBFormat($end);

		try {
			$startDt = new DateTime($startDb);
			$endDt   = new DateTime($endDb);
			$todayDt = new DateTime();
		} catch (Exception $e) {
			return null;
		}

		if ($endDt <= $startDt) {
			return null;
		}

		$total   = $endDt->getTimestamp() - $startDt->getTimestamp();
		$elapsed = $todayDt->getTimestamp() - $startDt->getTimestamp();

		if ($elapsed <= 0) {
			return 0;
		}

		$percent = ($elapsed / $total) * 100.0;
		if ($percent > 100) $percent = 100;

		return (int) round($percent);
	}

	public static function getResultProgressPercent(Vtiger_Record_Model $record) {
		$sumExpected = 0.0;
		$sumActual   = 0.0;

		for ($i = 1; $i <= 5; $i++) {
			$expected = $record->get("phase{$i}_expected");
			$actual   = $record->get("phase{$i}_actual");

			if ($expected !== null && $expected !== '') {
				$sumExpected += (float)$expected;
			}
			if ($actual !== null && $actual !== '') {
				$sumActual += (float)$actual;
			}
		}

		if ($sumExpected <= 0.0) {
			return null;
		}

		$percent = ($sumActual / $sumExpected) * 100.0;
		if ($percent < 0)   $percent = 0;
		if ($percent > 100) $percent = 100;

		return (int) round($percent);
	}

	public static function getProgress(Vtiger_Record_Model $record) {
		return array(
			'time'   => self::getTimeProgressPercent($record),
			'result' => self::getResultProgressPercent($record),
		);
	}
}

