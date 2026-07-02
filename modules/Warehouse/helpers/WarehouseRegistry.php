<?php
/**
 * Canonical warehouse list — reads from DB with static fallback.
 */
require_once 'modules/Warehouse/models/WhMgmtService.php';

class Warehouse_Registry {

	protected static $fallback = array(
		array(
			'id' => 'WH-001',
			'code' => 'WH-001',
			'name' => 'Kho Hồ Chí Minh',
			'address' => 'Q.7, TP.HCM',
		),
		array(
			'id' => 'WH-002',
			'code' => 'WH-002',
			'name' => 'Kho Hà Nội',
			'address' => 'Long Biên, Hà Nội',
		),
		array(
			'id' => 'WH-003',
			'code' => 'WH-003',
			'name' => 'Kho Bình Dương',
			'address' => 'Thuận An, Bình Dương',
		),
	);

	public static function getAll() {
		try {
			Warehouse_WhMgmtService::ensureInstalled();
			$rows = Warehouse_WhMgmtService::listWarehouses();
			if (!empty($rows)) {
				$out = array();
				foreach ($rows as $w) {
					if (isset($w['status']) && $w['status'] === 'archived') {
						continue;
					}
					$out[] = array(
						'id' => $w['id'],
						'code' => $w['code'],
						'name' => $w['name'],
						'address' => isset($w['address']) ? $w['address'] : '',
					);
				}
				if (!empty($out)) {
					return $out;
				}
			}
		} catch (Exception $e) {
			// fallback below
		}
		return self::$fallback;
	}

	public static function findById($warehouseId) {
		$warehouseId = trim((string) $warehouseId);
		foreach (self::getAll() as $row) {
			if ($row['id'] === $warehouseId || $row['code'] === $warehouseId) {
				return $row;
			}
		}
		return null;
	}

	public static function getName($warehouseId) {
		$row = self::findById($warehouseId);
		return $row ? (string) $row['name'] : '';
	}
}

?>
