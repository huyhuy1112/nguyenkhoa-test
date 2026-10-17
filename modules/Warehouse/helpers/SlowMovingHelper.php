<?php
/**
 * Slow-moving inventory (tồn lâu ngày) — DOI / DSI / Age / Risk_Score.
 * Data: vtiger_warehouse_stock + goodsissue/goodsreceipt (+ purchase_cost).
 */
class Warehouse_SlowMoving_Helper {

	/**
	 * @return array
	 */
	public static function getConfig() {
		require_once 'modules/Warehouse/helpers/SettingsHelper.php';
		return Warehouse_Settings_Helper::slowMovingConfig();
	}

	/**
	 * @param string|null $warehouseId empty = all warehouses
	 * @param int $minRisk only rows with risk >= this (0 = all with qty > 0)
	 * @return array{items:array,summary:array,config:array}
	 */
	public static function compute($warehouseId = null, $minRisk = 0.0) {
		$db = PearDatabase::getInstance();
		$config = self::getConfig();
		$warehouseId = trim((string) $warehouseId);
		$minRisk = (float) $minRisk;

		$stockRows = self::loadStockAgg($db, $warehouseId);
		if (empty($stockRows)) {
			return array(
				'items' => array(),
				'summary' => self::emptySummary(),
				'config' => $config,
			);
		}

		$productIds = array();
		foreach ($stockRows as $row) {
			$pid = (int) $row['productid'];
			if ($pid > 0) {
				$productIds[$pid] = true;
			}
		}
		$productIds = array_keys($productIds);

		$window = (int) $config['window_days'];
		$outMap = self::mapOutbound($db, $productIds, $warehouseId, $window);
		$inMap = self::mapInbound($db, $productIds, $warehouseId);
		$costMap = self::mapUnitCost($db, $productIds, $stockRows);

		$valueTotal = 0.0;
		$prepared = array();
		$today = strtotime(date('Y-m-d'));

		foreach ($stockRows as $row) {
			$qty = (float) $row['qty'];
			if ($qty <= 0) {
				continue;
			}
			$pid = (int) $row['productid'];
			$wh = (string) $row['warehouse_id'];
			$key = $wh . ':' . $pid;

			$soldQty = 0.0;
			$lastOut = null;
			if ($pid > 0 && isset($outMap[$key])) {
				$soldQty = (float) $outMap[$key]['sold_qty'];
				$lastOut = $outMap[$key]['last_out'];
			} elseif ($pid > 0 && isset($outMap['*:' . $pid]) && $warehouseId === '') {
				$soldQty = (float) $outMap['*:' . $pid]['sold_qty'];
				$lastOut = $outMap['*:' . $pid]['last_out'];
			}

			$lastIn = null;
			if ($pid > 0 && isset($inMap[$key])) {
				$lastIn = $inMap[$key];
			} elseif ($pid > 0 && isset($inMap['*:' . $pid]) && $warehouseId === '') {
				$lastIn = $inMap['*:' . $pid];
			}

			$avgSales = $window > 0 ? ($soldQty / $window) : 0.0;
			$doi = null;
			if ($avgSales > 0) {
				$doi = $qty / $avgSales;
			}

			$dsi = null;
			if ($lastOut) {
				$tsi = strtotime($lastOut);
				if ($tsi !== false) {
					$dsi = (int) floor(($today - $tsi) / 86400);
					if ($dsi < 0) {
						$dsi = 0;
					}
				}
			} else {
				// Never sold out while in stock → treat as high DSI (use Age_max * 2 or window)
				$dsi = (int) max($window, (int) $config['age_max']);
			}

			$age = null;
			if ($lastIn) {
				$tin = strtotime($lastIn);
				if ($tin !== false) {
					$age = (int) floor(($today - $tin) / 86400);
					if ($age < 0) {
						$age = 0;
					}
				}
			} else {
				// Fallback: stock createdtime
				$created = isset($row['createdtime']) ? $row['createdtime'] : '';
				if ($created !== '') {
					$tc = strtotime(substr($created, 0, 10));
					if ($tc !== false) {
						$age = (int) floor(($today - $tc) / 86400);
						if ($age < 0) {
							$age = 0;
						}
					}
				}
			}

			$unitCost = 0.0;
			if ($pid > 0 && isset($costMap[$pid])) {
				$unitCost = (float) $costMap[$pid];
			} elseif (isset($row['last_price'])) {
				$unitCost = (float) $row['last_price'];
			}
			$value = $qty * $unitCost;
			$valueTotal += $value;

			$prepared[] = array(
				'warehouse_id' => $wh,
				'productid' => $pid,
				'product_name' => (string) $row['product_name'],
				'sku' => (string) (isset($row['sku']) ? $row['sku'] : ''),
				'qty' => round($qty, 4),
				'avg_sales' => round($avgSales, 6),
				'sold_qty' => round($soldQty, 4),
				'doi' => $doi !== null ? round($doi, 2) : null,
				'dsi' => $dsi,
				'age' => $age,
				'last_out' => $lastOut,
				'last_in' => $lastIn,
				'unit_cost' => round($unitCost, 4),
				'value' => round($value, 2),
			);
		}

		if ($valueTotal <= 0) {
			$valueTotal = 0.0;
		}

		$items = array();
		$summary = self::emptySummary();
		foreach ($prepared as $row) {
			$risk = self::riskScore($row, $config, $valueTotal);
			$level = self::riskLevel($risk);
			$row['value_total'] = round($valueTotal, 2);
			$row['risk_score'] = $risk;
			$row['risk_level'] = $level['key'];
			$row['risk_label'] = $level['label'];
			$row['risk_color'] = $level['color'];
			$row['action_hint'] = $level['action'];

			$summary['total'] += 1;
			$summary[$level['key']] += 1;
			if ($risk >= 1.0) {
				$summary['alert_count'] += 1;
			}

			if ($minRisk > 0 && $risk < $minRisk) {
				continue;
			}
			$items[] = $row;
		}

		usort($items, function ($a, $b) {
			$ra = isset($a['risk_score']) ? (float) $a['risk_score'] : 0;
			$rb = isset($b['risk_score']) ? (float) $b['risk_score'] : 0;
			if ($ra === $rb) {
				return strcmp((string) $a['product_name'], (string) $b['product_name']);
			}
			return ($ra < $rb) ? 1 : -1;
		});

		return array(
			'items' => $items,
			'summary' => $summary,
			'config' => $config,
		);
	}

	/**
	 * @param array $row
	 * @param array $config
	 * @param float $valueTotal
	 * @return float
	 */
	public static function riskScore(array $row, array $config, $valueTotal) {
		$doiTh = (float) $config['doi_threshold'];
		$dsiTh = (float) $config['dsi_threshold'];
		$ageMax = (float) $config['age_max'];
		$w1 = (float) $config['w1'];
		$w2 = (float) $config['w2'];
		$w3 = (float) $config['w3'];
		$w4 = (float) $config['w4'];

		$ratioDoi = 0.0;
		if ($row['doi'] !== null && $doiTh > 0) {
			$ratioDoi = ((float) $row['doi']) / $doiTh;
		}
		$ratioDsi = 0.0;
		if ($row['dsi'] !== null && $dsiTh > 0) {
			$ratioDsi = ((float) $row['dsi']) / $dsiTh;
		}
		$ratioAge = 0.0;
		if ($row['age'] !== null && $ageMax > 0) {
			$ratioAge = ((float) $row['age']) / $ageMax;
		}
		$ratioVal = 0.0;
		if ($valueTotal > 0 && isset($row['value'])) {
			$ratioVal = ((float) $row['value']) / $valueTotal;
		}

		$score = ($w1 * $ratioDoi) + ($w2 * $ratioDsi) + ($w3 * $ratioAge) + ($w4 * $ratioVal);
		return round($score, 3);
	}

	/**
	 * @param float $risk
	 * @return array{key:string,label:string,color:string,action:string}
	 */
	public static function riskLevel($risk) {
		$risk = (float) $risk;
		if ($risk < 1.0) {
			return array(
				'key' => 'normal',
				'label' => 'Bình thường',
				'color' => 'green',
				'action' => 'Theo dõi',
			);
		}
		if ($risk < 1.5) {
			return array(
				'key' => 'mild',
				'label' => 'Cảnh báo nhẹ',
				'color' => 'yellow',
				'action' => 'Xem xét khuyến mãi',
			);
		}
		if ($risk <= 2.5) {
			return array(
				'key' => 'warning',
				'label' => 'Cảnh báo',
				'color' => 'orange',
				'action' => 'Giảm giá',
			);
		}
		return array(
			'key' => 'critical',
			'label' => 'Nghiêm trọng',
			'color' => 'red',
			'action' => 'Thanh lý / xử lý gấp',
		);
	}

	protected static function emptySummary() {
		return array(
			'total' => 0,
			'alert_count' => 0,
			'normal' => 0,
			'mild' => 0,
			'warning' => 0,
			'critical' => 0,
		);
	}

	/**
	 * Aggregate on-hand by warehouse + productid (sum lots).
	 * @return array
	 */
	protected static function loadStockAgg(PearDatabase $db, $warehouseId) {
		if (!self::tableExists($db, 'vtiger_warehouse_stock')) {
			return array();
		}
		$sql = "SELECT warehouse_id,
					COALESCE(productid, 0) AS productid,
					MAX(product_name) AS product_name,
					MAX(COALESCE(code, '')) AS sku,
					SUM(quantity) AS qty,
					MAX(last_price) AS last_price,
					MIN(createdtime) AS createdtime
				FROM vtiger_warehouse_stock
				WHERE quantity > 0";
		$params = array();
		if ($warehouseId !== '') {
			$sql .= ' AND warehouse_id = ?';
			$params[] = $warehouseId;
		}
		$sql .= ' GROUP BY warehouse_id, COALESCE(productid, 0)';
		$rs = $db->pquery($sql, $params);
		$out = array();
		if (!$rs) {
			return $out;
		}
		while ($row = $db->fetchByAssoc($rs)) {
			$out[] = array(
				'warehouse_id' => (string) (isset($row['warehouse_id']) ? $row['warehouse_id'] : ''),
				'productid' => (int) $row['productid'],
				'product_name' => html_entity_decode((string) $row['product_name'], ENT_QUOTES, 'UTF-8'),
				'sku' => (string) $row['sku'],
				'qty' => (float) $row['qty'],
				'last_price' => (float) $row['last_price'],
				'createdtime' => (string) (isset($row['createdtime']) ? $row['createdtime'] : ''),
			);
		}
		return $out;
	}

	/**
	 * Outbound qty in window + last out date, keyed by warehouse_id:productid.
	 * @return array
	 */
	protected static function mapOutbound(PearDatabase $db, array $productIds, $warehouseId, $windowDays) {
		$map = array();
		if (empty($productIds)
			|| !self::tableExists($db, 'vtiger_goodsissue')
			|| !self::tableExists($db, 'vtiger_goodsissue_items')) {
			return $map;
		}
		$windowDays = max(1, (int) $windowDays);
		$fromDate = date('Y-m-d', strtotime('-' . $windowDays . ' days'));

		$chunks = array_chunk($productIds, 400);
		foreach ($chunks as $chunk) {
			$marks = generateQuestionMarks($chunk);
			$params = $chunk;
			$params[] = $fromDate;
			$whSql = '';
			if ($warehouseId !== '') {
				$whSql = ' AND gi.warehouse_id = ?';
				$params[] = $warehouseId;
			}
			$rs = $db->pquery(
				"SELECT gi.warehouse_id, gii.productid,
						SUM(gii.quantity) AS sold_qty,
						MAX(gi.issued_date) AS last_out
				 FROM vtiger_goodsissue gi
				 INNER JOIN vtiger_goodsissue_items gii ON gii.issueid = gi.issueid
				 WHERE gi.deleted = 0
				   AND gii.productid IN ($marks)
				   AND gi.issued_date IS NOT NULL
				   AND gi.issued_date >= ?
				   AND LOWER(COALESCE(gi.status, '')) NOT IN ('cancelled','canceled','draft','waiting_print','picking')
				   $whSql
				 GROUP BY gi.warehouse_id, gii.productid",
				$params
			);
			if (!$rs) {
				continue;
			}
			while ($row = $db->fetchByAssoc($rs)) {
				$wh = (string) (isset($row['warehouse_id']) ? $row['warehouse_id'] : '');
				$pid = (int) $row['productid'];
				$key = $wh . ':' . $pid;
				$map[$key] = array(
					'sold_qty' => (float) $row['sold_qty'],
					'last_out' => (string) $row['last_out'],
				);
			}

			// Also last_out ever (for DSI) when no sales in window
			$params2 = $chunk;
			$whSql2 = '';
			if ($warehouseId !== '') {
				$whSql2 = ' AND gi.warehouse_id = ?';
				$params2[] = $warehouseId;
			}
			$rs2 = $db->pquery(
				"SELECT gi.warehouse_id, gii.productid, MAX(gi.issued_date) AS last_out
				 FROM vtiger_goodsissue gi
				 INNER JOIN vtiger_goodsissue_items gii ON gii.issueid = gi.issueid
				 WHERE gi.deleted = 0
				   AND gii.productid IN ($marks)
				   AND gi.issued_date IS NOT NULL
				   AND LOWER(COALESCE(gi.status, '')) NOT IN ('cancelled','canceled','draft')
				   $whSql2
				 GROUP BY gi.warehouse_id, gii.productid",
				$params2
			);
			if ($rs2) {
				while ($row = $db->fetchByAssoc($rs2)) {
					$wh = (string) (isset($row['warehouse_id']) ? $row['warehouse_id'] : '');
					$pid = (int) $row['productid'];
					$key = $wh . ':' . $pid;
					if (!isset($map[$key])) {
						$map[$key] = array('sold_qty' => 0.0, 'last_out' => (string) $row['last_out']);
					} elseif (empty($map[$key]['last_out'])) {
						$map[$key]['last_out'] = (string) $row['last_out'];
					}
				}
			}
		}
		return $map;
	}

	/**
	 * Last inbound date keyed by warehouse_id:productid.
	 * @return array
	 */
	protected static function mapInbound(PearDatabase $db, array $productIds, $warehouseId) {
		$map = array();
		if (empty($productIds)
			|| !self::tableExists($db, 'vtiger_goodsreceipt')
			|| !self::tableExists($db, 'vtiger_goodsreceipt_items')) {
			return $map;
		}
		$chunks = array_chunk($productIds, 400);
		foreach ($chunks as $chunk) {
			$marks = generateQuestionMarks($chunk);
			$params = $chunk;
			$whSql = '';
			if ($warehouseId !== '') {
				$whSql = ' AND gr.warehouse_id = ?';
				$params[] = $warehouseId;
			}
			$rs = $db->pquery(
				"SELECT gr.warehouse_id, gri.productid, MAX(gr.received_date) AS last_in
				 FROM vtiger_goodsreceipt gr
				 INNER JOIN vtiger_goodsreceipt_items gri ON gri.receiptid = gr.receiptid
				 WHERE gr.deleted = 0
				   AND gri.productid IN ($marks)
				   AND gr.received_date IS NOT NULL
				   AND LOWER(COALESCE(gr.status, '')) NOT IN ('cancelled','canceled','draft')
				   $whSql
				 GROUP BY gr.warehouse_id, gri.productid",
				$params
			);
			if (!$rs) {
				continue;
			}
			while ($row = $db->fetchByAssoc($rs)) {
				$wh = (string) (isset($row['warehouse_id']) ? $row['warehouse_id'] : '');
				$pid = (int) $row['productid'];
				$map[$wh . ':' . $pid] = (string) $row['last_in'];
			}
		}
		return $map;
	}

	/**
	 * Unit cost: purchase_cost on ProductsServices, else stock last_price.
	 * @return array productid => cost
	 */
	protected static function mapUnitCost(PearDatabase $db, array $productIds, array $stockRows) {
		$map = array();
		foreach ($stockRows as $row) {
			$pid = (int) $row['productid'];
			if ($pid > 0 && !isset($map[$pid]) && (float) $row['last_price'] > 0) {
				$map[$pid] = (float) $row['last_price'];
			}
		}
		if (empty($productIds) || !self::tableExists($db, 'vtiger_productsservices')) {
			return $map;
		}
		$hasPurchase = self::columnExists($db, 'vtiger_productsservices', 'purchase_cost');
		if (!$hasPurchase) {
			return $map;
		}
		$chunks = array_chunk($productIds, 400);
		foreach ($chunks as $chunk) {
			$marks = generateQuestionMarks($chunk);
			$rs = $db->pquery(
				"SELECT productsservicesid, purchase_cost
				 FROM vtiger_productsservices
				 WHERE productsservicesid IN ($marks)",
				$chunk
			);
			if (!$rs) {
				continue;
			}
			while ($row = $db->fetchByAssoc($rs)) {
				$pid = (int) $row['productsservicesid'];
				$cost = (float) $row['purchase_cost'];
				if ($cost > 0) {
					$map[$pid] = $cost;
				}
			}
		}
		return $map;
	}

	protected static function tableExists(PearDatabase $db, $table) {
		static $cache = array();
		if (isset($cache[$table])) {
			return $cache[$table];
		}
		$rs = $db->pquery('SHOW TABLES LIKE ?', array($table));
		$cache[$table] = ($rs && $db->num_rows($rs) > 0);
		return $cache[$table];
	}

	protected static function columnExists(PearDatabase $db, $table, $column) {
		static $cache = array();
		$key = $table . '.' . $column;
		if (isset($cache[$key])) {
			return $cache[$key];
		}
		$rs = $db->pquery("SHOW COLUMNS FROM {$table} LIKE ?", array($column));
		$cache[$key] = ($rs && $db->num_rows($rs) > 0);
		return $cache[$key];
	}
}
