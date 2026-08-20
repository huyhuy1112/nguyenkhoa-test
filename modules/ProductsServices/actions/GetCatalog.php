<?php
/**
 * ProductsServices — client catalog for BA list (stock, SO demand, images, warehouses).
 */
require_once 'modules/ProductsServices/models/Record.php';

class ProductsServices_GetCatalog_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		if (!Users_Privileges_Model::isPermitted('ProductsServices', 'DetailView')) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
	}

	public function process(Vtiger_Request $request) {
		$response = new Vtiger_Response();
		try {
			$db = PearDatabase::getInstance();
			$hasPriceLt = $this->columnExists($db, 'vtiger_productsservices', 'price_lt_1m');
			$priceLtCol = $hasPriceLt ? ', IFNULL(ps.price_lt_1m, 0) AS price_lt_1m' : '';

			$sql = "SELECT ps.productsservicesid AS id,
					ps.productsservicesname AS name,
					IFNULL(ps.sku, '') AS sku,
					IFNULL(ps.product_group, '') AS product_group,
					IFNULL(ps.item_type, '') AS item_type,
					IFNULL(ps.price, 0) AS price,
					IFNULL(ps.price_tuibao, 0) AS price_tuibao,
					IFNULL(ps.unit, '') AS unit,
					ce.createdtime AS createdtime
					{$priceLtCol}
				FROM vtiger_productsservices ps
				INNER JOIN vtiger_crmentity ce ON ce.crmid = ps.productsservicesid AND ce.deleted = 0
				ORDER BY ps.productsservicesname ASC, ps.productsservicesid ASC";
			$rs = $db->pquery($sql, array());
			$items = array();
			$ids = array();
			while ($rs && ($row = $db->fetchByAssoc($rs))) {
				$id = (int) $row['id'];
				$ids[] = $id;
				$priceLt = $hasPriceLt
					? (float) $row['price_lt_1m']
					: (float) $row['price'];
				$items[] = array(
					'id' => $id,
					'name' => decode_html((string) $row['name']),
					'sku' => decode_html((string) $row['sku']),
					'product_group' => decode_html((string) $row['product_group']),
					'item_type' => decode_html((string) $row['item_type']),
					'price' => (float) $row['price'],
					'price_lt_1m' => $priceLt,
					'price_tuibao' => (float) $row['price_tuibao'],
					'unit' => decode_html((string) $row['unit']),
					'createdtime' => (string) $row['createdtime'],
					'stock' => 0.0,
					'qty_so' => 0.0,
					'stock_by_wh' => array(),
					'image_url' => '',
					'starred' => 0,
				);
			}

			$stockMap = $this->mapWarehouseStock($db, $ids);
			$stockByWh = $this->mapWarehouseStockByWarehouse($db, $ids);
			$soMap = $this->mapOpenSalesOrderDemand($db, $ids);
			$imageMap = $this->mapProductImages($db, $ids);
			$starMap = $this->mapStarred($db, $ids);

			foreach ($items as &$item) {
				$pid = $item['id'];
				if (isset($stockMap[$pid])) {
					$item['stock'] = (float) $stockMap[$pid];
				}
				if (isset($stockByWh[$pid])) {
					$item['stock_by_wh'] = $stockByWh[$pid];
				}
				if (isset($soMap[$pid])) {
					$item['qty_so'] = (float) $soMap[$pid];
				}
				if (isset($imageMap[$pid])) {
					$item['image_url'] = $imageMap[$pid];
				}
				if (isset($starMap[$pid])) {
					$item['starred'] = (int) $starMap[$pid];
				}
			}
			unset($item);

			$warehouses = array();
			try {
				if (is_file('modules/Warehouse/helpers/WarehouseRegistry.php')) {
					require_once 'modules/Warehouse/helpers/WarehouseRegistry.php';
					$warehouses = Warehouse_Registry::getAll();
				}
			} catch (Exception $e) {
				$warehouses = array();
			}

			$groups = array();
			foreach ($items as $it) {
				$g = trim((string) $it['product_group']);
				if ($g !== '' && !in_array($g, $groups, true)) {
					$groups[] = $g;
				}
			}
			sort($groups, SORT_NATURAL | SORT_FLAG_CASE);

			$response->setResult(array(
				'items' => $items,
				'count' => php7_count($items),
				'warehouses' => $warehouses,
				'groups' => $groups,
			));
		} catch (Exception $e) {
			$response->setError(500, $e->getMessage());
		}
		$response->emit();
	}

	public function validateRequest(Vtiger_Request $request) {
		$request->validateReadAccess();
	}

	protected function columnExists(PearDatabase $db, $table, $column) {
		static $cache = array();
		$key = $table . '.' . $column;
		if (isset($cache[$key])) {
			return $cache[$key];
		}
		$res = $db->pquery('SHOW COLUMNS FROM `' . $table . '` LIKE ?', array($column));
		$cache[$key] = ($res && $db->num_rows($res) > 0);
		return $cache[$key];
	}

	protected function tableExists(PearDatabase $db, $table) {
		static $cache = array();
		if (isset($cache[$table])) {
			return $cache[$table];
		}
		$res = $db->pquery('SHOW TABLES LIKE ?', array($table));
		$cache[$table] = ($res && $db->num_rows($res) > 0);
		return $cache[$table];
	}

	/**
	 * Tồn kho tổng: SUM(quantity - shrinkage) mọi kho.
	 */
	protected function mapWarehouseStock(PearDatabase $db, array $productIds) {
		$map = array();
		if (empty($productIds) || !$this->tableExists($db, 'vtiger_warehouse_stock')) {
			return $map;
		}
		$chunks = array_chunk(array_values(array_unique(array_map('intval', $productIds))), 400);
		foreach ($chunks as $chunk) {
			$chunk = array_filter($chunk);
			if (empty($chunk)) {
				continue;
			}
			$marks = generateQuestionMarks($chunk);
			$rs = $db->pquery(
				"SELECT productid,
					SUM(GREATEST(IFNULL(quantity,0) - IFNULL(shrinkage_qty,0), 0)) AS stock_qty
				 FROM vtiger_warehouse_stock
				 WHERE productid IN ({$marks})
				 GROUP BY productid",
				$chunk
			);
			if (!$rs) {
				continue;
			}
			while ($row = $db->fetchByAssoc($rs)) {
				$map[(int) $row['productid']] = (float) $row['stock_qty'];
			}
		}
		return $map;
	}

	/**
	 * Tồn theo mã kho (warehouse_id / code).
	 * @return array<int, array<string,float>>
	 */
	protected function mapWarehouseStockByWarehouse(PearDatabase $db, array $productIds) {
		$map = array();
		if (empty($productIds) || !$this->tableExists($db, 'vtiger_warehouse_stock')) {
			return $map;
		}
		$hasWhCol = $this->columnExists($db, 'vtiger_warehouse_stock', 'warehouse_id');
		if (!$hasWhCol) {
			return $map;
		}
		$chunks = array_chunk(array_values(array_unique(array_map('intval', $productIds))), 400);
		foreach ($chunks as $chunk) {
			$chunk = array_filter($chunk);
			if (empty($chunk)) {
				continue;
			}
			$marks = generateQuestionMarks($chunk);
			$rs = $db->pquery(
				"SELECT productid, warehouse_id,
					SUM(GREATEST(IFNULL(quantity,0) - IFNULL(shrinkage_qty,0), 0)) AS stock_qty
				 FROM vtiger_warehouse_stock
				 WHERE productid IN ({$marks})
				 GROUP BY productid, warehouse_id",
				$chunk
			);
			if (!$rs) {
				continue;
			}
			while ($row = $db->fetchByAssoc($rs)) {
				$pid = (int) $row['productid'];
				$wh = trim((string) $row['warehouse_id']);
				if ($wh === '') {
					continue;
				}
				if (!isset($map[$pid])) {
					$map[$pid] = array();
				}
				$map[$pid][$wh] = (float) $row['stock_qty'];
			}
		}
		return $map;
	}

	/**
	 * Khách đặt: chỉ Sales Order từ Created (Phiếu tạm) trở đi;
	 * loại Cancelled / Delivered / Completed / Rejected.
	 */
	protected function mapOpenSalesOrderDemand(PearDatabase $db, array $productIds) {
		$map = array();
		if (empty($productIds)
			|| !$this->tableExists($db, 'vtiger_inventoryproductrel')
			|| !$this->tableExists($db, 'vtiger_salesorder')) {
			return $map;
		}
		$chunks = array_chunk(array_values(array_unique(array_map('intval', $productIds))), 400);
		foreach ($chunks as $chunk) {
			$chunk = array_filter($chunk);
			if (empty($chunk)) {
				continue;
			}
			$marks = generateQuestionMarks($chunk);
			$rs = $db->pquery(
				"SELECT ip.productid, SUM(ip.quantity) AS qty
				 FROM vtiger_inventoryproductrel ip
				 INNER JOIN vtiger_salesorder so ON so.salesorderid = ip.id
				 INNER JOIN vtiger_crmentity ce ON ce.crmid = so.salesorderid AND ce.deleted = 0
				 WHERE ip.productid IN ({$marks})
				   AND (so.sostatus IS NULL OR so.sostatus = '' OR so.sostatus NOT IN (
						'Cancelled','Delivered','Completed','Rejected'
				   ))
				 GROUP BY ip.productid",
				$chunk
			);
			if (!$rs) {
				continue;
			}
			while ($row = $db->fetchByAssoc($rs)) {
				$map[(int) $row['productid']] = (float) $row['qty'];
			}
		}
		return $map;
	}

	/**
	 * Starred (follow) flags for current user — vtiger_crmentity_user_field.
	 * @return array<int,int>
	 */
	protected function mapStarred(PearDatabase $db, array $productIds) {
		$map = array();
		if (empty($productIds) || !$this->tableExists($db, 'vtiger_crmentity_user_field')) {
			return $map;
		}
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$userId = $currentUser ? (int) $currentUser->getId() : 0;
		if ($userId <= 0) {
			return $map;
		}
		$chunks = array_chunk(array_values(array_unique(array_map('intval', $productIds))), 400);
		foreach ($chunks as $chunk) {
			$chunk = array_values(array_filter($chunk));
			if (empty($chunk)) {
				continue;
			}
			$marks = generateQuestionMarks($chunk);
			$params = $chunk;
			$params[] = $userId;
			$rs = $db->pquery(
				"SELECT recordid, starred
				 FROM vtiger_crmentity_user_field
				 WHERE recordid IN ({$marks}) AND userid = ?",
				$params
			);
			if (!$rs) {
				continue;
			}
			while ($row = $db->fetchByAssoc($rs)) {
				$rid = (int) (isset($row['recordid']) ? $row['recordid'] : (isset($row['RECORDID']) ? $row['RECORDID'] : 0));
				if ($rid <= 0) {
					continue;
				}
				$flag = 0;
				if (isset($row['starred'])) {
					$flag = $row['starred'];
				} elseif (isset($row['STARRED'])) {
					$flag = $row['STARRED'];
				}
				$map[$rid] = ((int) $flag) ? 1 : 0;
			}
		}
		return $map;
	}

	/**
	 * First image URL per product (DownloadImage — bypasses public.php key mismatches).
	 */
	protected function mapProductImages(PearDatabase $db, array $productIds) {
		$map = array();
		if (empty($productIds)
			|| !$this->tableExists($db, 'vtiger_attachments')
			|| !$this->tableExists($db, 'vtiger_seattachmentsrel')) {
			return $map;
		}
		$chunks = array_chunk(array_values(array_unique(array_map('intval', $productIds))), 400);
		foreach ($chunks as $chunk) {
			$chunk = array_values(array_filter($chunk));
			if (empty($chunk)) {
				continue;
			}
			$marks = generateQuestionMarks($chunk);
			$rs = $db->pquery(
				"SELECT sar.crmid AS productid, a.attachmentsid, a.name
				 FROM vtiger_seattachmentsrel sar
				 INNER JOIN vtiger_attachments a ON a.attachmentsid = sar.attachmentsid
				 INNER JOIN vtiger_crmentity ce ON ce.crmid = a.attachmentsid AND ce.deleted = 0
				 WHERE sar.crmid IN ({$marks})
				 ORDER BY CASE WHEN ce.setype LIKE '% Image' THEN 0
				               WHEN IFNULL(a.type,'') LIKE 'image/%' THEN 1
				               ELSE 2 END,
				          a.attachmentsid DESC",
				$chunk
			);
			if (!$rs) {
				continue;
			}
			while ($row = $db->fetchByAssoc($rs)) {
				$pid = (int) (isset($row['productid']) ? $row['productid'] : 0);
				if ($pid <= 0 || isset($map[$pid])) {
					continue;
				}
				$imageId = (int) (isset($row['attachmentsid']) ? $row['attachmentsid'] : 0);
				if ($imageId <= 0) {
					continue;
				}
				$imageName = isset($row['name']) ? (string) $row['name'] : '';
				$map[$pid] = ProductsServices_Record_Model::listImageUrl($pid, $imageId, $imageName);
			}
		}
		return $map;
	}
}
