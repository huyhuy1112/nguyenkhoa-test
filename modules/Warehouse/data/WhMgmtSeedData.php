<?php
/**
 * Seed warehouses + per-warehouse demo stock (mirrors WarehouseLocalStore.js).
 */
class Warehouse_WhMgmtSeedData {

	public static function warehouses() {
		return array(
			array(
				'code' => 'WH-001',
				'name' => 'Kho Hồ Chí Minh',
				'type' => 'central',
				'address' => 'Q.7, TP.HCM',
				'manager' => 'QL Tuấn',
				'status' => 'active',
				'created_at' => '2026-01-15 08:00:00',
			),
			array(
				'code' => 'WH-002',
				'name' => 'Kho Hà Nội',
				'type' => 'branch',
				'address' => 'Long Biên, Hà Nội',
				'manager' => 'QL Nam',
				'status' => 'active',
				'created_at' => '2026-02-20 08:00:00',
			),
			array(
				'code' => 'WH-003',
				'name' => 'Kho Bình Dương',
				'type' => 'branch',
				'address' => 'Thuận An, Bình Dương',
				'manager' => 'QL Hùng',
				'status' => 'active',
				'created_at' => '2026-03-10 08:00:00',
			),
		);
	}

	/**
	 * Legacy static demo rows — unused. Stock is seeded from ProductsServices via
	 * Warehouse_WhMgmtService::resetStockFromCatalog().
	 */
	public static function stockByWarehouse() {
		return array();
	}
}

?>
