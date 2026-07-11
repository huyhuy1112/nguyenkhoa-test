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

	public static function stockByWarehouse() {
		return array(
			'WH-001' => array(
				array('sku' => 'MED-001', 'name' => 'Paracetamol 500mg', 'lot' => 'LOT-2605A', 'mfg' => '2026-05-01', 'expiry' => '2027-05-01', 'qty' => 800, 'location' => 'A1-02', 'price' => 25000),
				array('sku' => 'MED-002', 'name' => 'Amoxicillin 250mg', 'lot' => 'LOT-2604B', 'mfg' => '2026-02-15', 'expiry' => '2026-08-15', 'qty' => 120, 'location' => 'B2-01', 'price' => 45000),
				array('sku' => 'MED-003', 'name' => 'Vitamin C 1000mg', 'lot' => 'LOT-2603C', 'mfg' => '2025-12-01', 'expiry' => '2026-06-01', 'qty' => 45, 'location' => 'C1-03', 'price' => 120000),
			),
			'WH-002' => array(
				array('sku' => 'MED-001', 'name' => 'Paracetamol 500mg', 'lot' => 'LOT-HN01', 'mfg' => '2026-04-01', 'expiry' => '2027-04-01', 'qty' => 300, 'location' => 'B1-01', 'price' => 25000),
				array('sku' => 'MED-003', 'name' => 'Vitamin C 1000mg', 'lot' => 'LOT-HN02', 'mfg' => '2026-09-01', 'expiry' => '2027-09-01', 'qty' => 220, 'location' => 'B1-02', 'price' => 120000),
			),
			'WH-003' => array(
				array('sku' => 'MED-002', 'name' => 'Amoxicillin 250mg', 'lot' => 'LOT-BD01', 'mfg' => '2026-06-01', 'expiry' => '2026-12-01', 'qty' => 180, 'location' => 'C1-01', 'price' => 45000),
			),
		);
	}
}

?>
