<?php
/*+***********************************************************************************
 * Pricing engine: quantity + invoice-tier prices for Quotes / SalesOrder.
 * DO NOT modify Vtiger core.
 *************************************************************************************/

class ProductsServices_PricingEngine_Model {

	/** Invoice amount tiers used by sales policy (Bảng giá theo mức hóa đơn). */
	const INVOICE_TIER_FIELDS = array(
		'lt_1m' => 'price_lt_1m',
		'gte_1m' => 'price_gte_1m',
		'gte_3m' => 'price_gte_3m',
		'gte_5m' => 'price_gte_5m',
		'gte_7m' => 'price_gte_7m',
	);

	/**
	 * Human labels for invoice tiers (VI).
	 * @return array<string,string> tierKey => label
	 */
	public static function getInvoiceTier() {
		return array(
			'lt_1m' => 'Giá < 1 triệu',
			'gte_1m' => 'Giá ≥ 1 triệu',
			'gte_3m' => 'Giá ≥ 3 triệu',
			'gte_5m' => 'Giá ≥ 5 triệu',
			'gte_7m' => 'Giá ≥ 7 triệu',
		);
	}

	/**
	 * Resolve unit price from a selected invoice-tier key.
	 *
	 * @param string $tierKey lt_1m|gte_1m|gte_3m|gte_5m|gte_7m
	 * @param array $prices field => value
	 * @return float|null
	 */
	public static function getPriceByInvoiceTier($tierKey, $prices) {
		$tierKey = (string) $tierKey;
		if (!isset(self::INVOICE_TIER_FIELDS[$tierKey])) {
			return null;
		}
		$field = self::INVOICE_TIER_FIELDS[$tierKey];
		if (!isset($prices[$field]) || $prices[$field] === '' || $prices[$field] === null) {
			return null;
		}
		return (float) $prices[$field];
	}

	/**
	 * Channel: "tuibao" uses flat price_tuibao; "retail" (default) uses invoice-tier columns.
	 *
	 * @param string $channel tuibao|retail
	 * @param string $tierKey invoice tier when retail
	 * @param array $prices field values (price, price_tuibao, price_*)
	 * @return float|null
	 */
	public static function resolveUnitPrice($channel, $tierKey, array $prices) {
		$channel = strtolower(trim((string) $channel));
		if ($channel === 'tuibao') {
			if (isset($prices['price_tuibao']) && $prices['price_tuibao'] !== '' && $prices['price_tuibao'] !== null) {
				return (float) $prices['price_tuibao'];
			}
			if (isset($prices['price']) && $prices['price'] !== '' && $prices['price'] !== null) {
				return (float) $prices['price'];
			}
			$fallback = self::getPriceByInvoiceTier('gte_7m', $prices);
			return $fallback;
		}
		$tierPrice = self::getPriceByInvoiceTier($tierKey, $prices);
		if ($tierPrice !== null) {
			return $tierPrice;
		}
		if (isset($prices['price']) && $prices['price'] !== '' && $prices['price'] !== null) {
			return (float) $prices['price'];
		}
		return null;
	}

	/**
	 * True when account is a Tuibao franchise customer (Accounts tb_* contract fields).
	 *
	 * @param int $accountId
	 * @return bool
	 */
	public static function isTuibaoAccount($accountId) {
		$accountId = (int) $accountId;
		if ($accountId <= 0) {
			return false;
		}
		$db = PearDatabase::getInstance();
		// Prefer customfields table if franchise cols live there
		$tables = array('vtiger_accountscf', 'vtiger_account');
		$markers = array('tb_contract_no', 'tb_sc_customer_id', 'tb_party_b_name', 'tb_store_address');
		foreach ($tables as $table) {
			$exists = $db->pquery('SHOW TABLES LIKE ?', array($table));
			if (!$exists || $db->num_rows($exists) <= 0) {
				continue;
			}
			$cols = array();
			foreach ($markers as $col) {
				$c = $db->pquery("SHOW COLUMNS FROM `$table` LIKE ?", array($col));
				if ($c && $db->num_rows($c) > 0) {
					$cols[] = $col;
				}
			}
			if (!$cols) {
				continue;
			}
			$parts = array();
			foreach ($cols as $col) {
				$parts[] = "($col IS NOT NULL AND TRIM($col) <> '')";
			}
			$pk = $table === 'vtiger_account' ? 'accountid' : 'accountid';
			$sql = "SELECT $pk FROM `$table` WHERE $pk = ? AND (" . implode(' OR ', $parts) . ") LIMIT 1";
			$rs = $db->pquery($sql, array($accountId));
			if ($rs && $db->num_rows($rs) > 0) {
				return true;
			}
		}
		// Related ServiceContracts → franchise customer
		$sc = $db->pquery(
			"SELECT sc.servicecontractsid
			 FROM vtiger_servicecontracts sc
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = sc.servicecontractsid AND ce.deleted = 0
			 WHERE sc.sc_related_to = ?
			 LIMIT 1",
			array($accountId)
		);
		return $sc && $db->num_rows($sc) > 0;
	}

	/**
	 * Infer tier from invoice/order total (auto mode).
	 * Thresholds in VND: 1M / 3M / 5M / 7M.
	 *
	 * @param float $invoiceTotal
	 * @return string tier key
	 */
	public static function resolveTierFromInvoiceTotal($invoiceTotal) {
		$total = (float) $invoiceTotal;
		if ($total >= 7000000) {
			return 'gte_7m';
		}
		if ($total >= 5000000) {
			return 'gte_5m';
		}
		if ($total >= 3000000) {
			return 'gte_3m';
		}
		if ($total >= 1000000) {
			return 'gte_1m';
		}
		return 'lt_1m';
	}

	/**
	 * Get unit price for a given quantity.
	 * IF quantity >= minimum_qty_bulk   -> bulk_price
	 * ELSE IF quantity >= minimum_qty_wholesale -> wholesale_price
	 * ELSE -> retail_price
	 *
	 * @param int|float $quantity
	 * @param array $prices [ 'retail_price' => n, 'wholesale_price' => n, 'bulk_price' => n, 'minimum_qty_wholesale' => n, 'minimum_qty_bulk' => n ]
	 * @return float|null Unit price or null if no price defined
	 */
	public static function getPriceByQuantity($quantity, $prices) {
		$qty = (float) $quantity;
		$retail = isset($prices['retail_price']) ? (float) $prices['retail_price'] : null;
		$wholesale = isset($prices['wholesale_price']) ? (float) $prices['wholesale_price'] : null;
		$bulk = isset($prices['bulk_price']) ? (float) $prices['bulk_price'] : null;
		$minWholesale = isset($prices['minimum_qty_wholesale']) ? (float) $prices['minimum_qty_wholesale'] : 0;
		$minBulk = isset($prices['minimum_qty_bulk']) ? (float) $prices['minimum_qty_bulk'] : 0;

		if ($minBulk > 0 && $qty >= $minBulk && $bulk !== null) {
			return $bulk;
		}
		if ($minWholesale > 0 && $qty >= $minWholesale && $wholesale !== null) {
			return $wholesale;
		}
		return $retail;
	}

	/**
	 * Get price from a ProductsServices record (by record id or array of field values).
	 * @param int $recordId ProductsServices record id
	 * @param int|float $quantity
	 * @return float|null
	 */
	public static function getPriceForRecord($recordId, $quantity) {
		$db = PearDatabase::getInstance();
		$row = $db->pquery(
			"SELECT retail_price, wholesale_price, bulk_price, minimum_qty_wholesale, minimum_qty_bulk FROM vtiger_productsservices WHERE productsservicesid = ?",
			array($recordId)
		);
		if (!$row || $db->num_rows($row) === 0) {
			return null;
		}
		$r = $db->fetchByAssoc($row, 0);
		return self::getPriceByQuantity($quantity, $r);
	}

	/**
	 * Get invoice-tier unit price for a ProductsServices record.
	 *
	 * @param int $recordId
	 * @param string $tierKey
	 * @return float|null
	 */
	public static function getInvoiceTierPriceForRecord($recordId, $tierKey) {
		$db = PearDatabase::getInstance();
		$cols = array_values(self::INVOICE_TIER_FIELDS);
		$select = array();
		foreach ($cols as $col) {
			$select[] = $col;
		}
		// Only query columns that exist.
		$existing = array();
		foreach ($cols as $col) {
			$check = $db->pquery('SHOW COLUMNS FROM `vtiger_productsservices` LIKE ?', array($col));
			if ($check && $db->num_rows($check) > 0) {
				$existing[] = $col;
			}
		}
		if (empty($existing)) {
			return null;
		}
		$sql = 'SELECT ' . implode(', ', $existing)
			. ' FROM vtiger_productsservices WHERE productsservicesid = ?';
		$row = $db->pquery($sql, array((int) $recordId));
		if (!$row || $db->num_rows($row) === 0) {
			return null;
		}
		$r = $db->fetchByAssoc($row, 0);
		$price = self::getPriceByInvoiceTier($tierKey, $r);
		if ($price !== null) {
			return $price;
		}
		// Fallback: base price / wholesale if tier empty.
		$fallback = $db->pquery(
			'SELECT price, wholesale_price FROM vtiger_productsservices WHERE productsservicesid = ?',
			array((int) $recordId)
		);
		if ($fallback && $db->num_rows($fallback) > 0) {
			$f = $db->fetchByAssoc($fallback, 0);
			if (isset($f['price']) && $f['price'] !== '' && $f['price'] !== null) {
				return (float) $f['price'];
			}
			if (isset($f['wholesale_price']) && $f['wholesale_price'] !== '' && $f['wholesale_price'] !== null) {
				return (float) $f['wholesale_price'];
			}
		}
		return null;
	}
}
