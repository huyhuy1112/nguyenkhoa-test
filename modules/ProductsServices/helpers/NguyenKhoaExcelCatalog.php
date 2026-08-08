<?php
/*+***********************************************************************************
 * Parse "Danh muc SP Nguyen Khoa.xlsx" for catalog seed.
 * Sheet 1: Tong NL | Sheet 4: CCDC - May moc
 * Quy cách + note SL are ignored.
 *************************************************************************************/

class ProductsServices_NguyenKhoaExcelCatalog_Helper {

	/**
	 * @param string $xlsxPath
	 * @return array{products:array<int,array>,errors:string[]}
	 */
	public static function parse($xlsxPath) {
		$errors = array();
		$products = array();
		if (!is_file($xlsxPath)) {
			return array('products' => array(), 'errors' => array('File not found: ' . $xlsxPath));
		}
		try {
			$book = self::openWorkbook($xlsxPath);
		} catch (Exception $e) {
			return array('products' => array(), 'errors' => array($e->getMessage()));
		}

		$sheet1 = isset($book['sheets'][0]) ? $book['sheets'][0] : null;
		$sheet4 = isset($book['sheets'][3]) ? $book['sheets'][3] : null;
		foreach ($book['sheets'] as $sheet) {
			$name = isset($sheet['name']) ? (string) $sheet['name'] : '';
			if (stripos($name, 'Tong NL') !== false || stripos($name, 'Tổng NL') !== false || $name === '1. Tong NL') {
				$sheet1 = $sheet;
			}
			if (stripos($name, 'CCDC') !== false || stripos($name, 'May moc') !== false || stripos($name, 'Máy') !== false) {
				$sheet4 = $sheet;
			}
		}

		if ($sheet4) {
			$products = array_merge($products, self::parseCcdc($sheet4, $book['shared'], $errors));
		} else {
			$errors[] = 'Sheet 4.CCDC not found';
		}
		if ($sheet1) {
			$products = array_merge($products, self::parseTongNl($sheet1, $book['shared'], $errors));
		} else {
			$errors[] = 'Sheet 1.Tong NL not found';
		}

		$bySku = array();
		$out = array();
		foreach ($products as $p) {
			$sku = $p['sku'];
			if ($sku === '' || isset($bySku[$sku])) {
				if ($sku !== '' && isset($bySku[$sku])) {
					$errors[] = 'Duplicate SKU skipped: ' . $sku;
				}
				continue;
			}
			$bySku[$sku] = true;
			$out[] = $p;
		}

		return array('products' => $out, 'errors' => $errors);
	}

	/**
	 * @param array $sheet
	 * @param array $shared
	 * @param array $errors
	 * @return array
	 */
	protected static function parseTongNl(array $sheet, array $shared, array &$errors) {
		$rows = self::sheetRows($sheet, $shared);
		$products = array();
		$lastGroup = '';
		foreach ($rows as $r => $cells) {
			if ($r < 6) {
				continue;
			}
			$group = self::cell($cells, 1);
			$sku = trim(self::cell($cells, 2));
			$name = trim(self::cell($cells, 3));
			$unit = trim(self::cell($cells, 4));
			if ($group !== '') {
				$lastGroup = $group;
			}
			if ($sku === '' || $name === '') {
				continue;
			}
			if (stripos($sku, 'Mã') !== false || stripos($name, 'Tên sản') !== false) {
				continue;
			}
			$t1 = self::money(self::cell($cells, 7));
			$t2 = self::money(self::cell($cells, 8));
			$t3 = self::money(self::cell($cells, 9));
			$t4 = self::money(self::cell($cells, 10));
			$t5 = self::money(self::cell($cells, 11));
			$tuibao = self::money(self::cell($cells, 13));
			$tiers = self::fillTiers(array($t1, $t2, $t3, $t4, $t5));
			$base = $tiers[0];
			if ($tuibao === null) {
				$tuibao = $tiers[4] !== null ? $tiers[4] : $base;
			}
			if ($base === null && $tuibao === null) {
				$errors[] = "NL $sku has no price — imported with 0";
				$base = 0;
				$tiers = array(0, 0, 0, 0, 0);
				$tuibao = 0;
			}
			$products[] = array(
				'source' => 'nl',
				'sku' => $sku,
				'name' => $name,
				'unit' => $unit,
				'product_group' => $lastGroup,
				'item_type' => 'Product',
				'price' => $base !== null ? $base : 0,
				'price_lt_1m' => $tiers[0],
				'price_gte_1m' => $tiers[1],
				'price_gte_3m' => $tiers[2],
				'price_gte_5m' => $tiers[3],
				'price_gte_7m' => $tiers[4],
				'price_tuibao' => $tuibao !== null ? $tuibao : $base,
			);
		}
		return $products;
	}

	/**
	 * @param array $sheet
	 * @param array $shared
	 * @param array $errors
	 * @return array
	 */
	protected static function parseCcdc(array $sheet, array $shared, array &$errors) {
		$rows = self::sheetRows($sheet, $shared);
		$products = array();
		foreach ($rows as $r => $cells) {
			if ($r < 2) {
				continue;
			}
			$group = trim(self::cell($cells, 2));
			$sku = trim(self::cell($cells, 3));
			$name = trim(self::cell($cells, 4));
			$price = self::money(self::cell($cells, 5));
			if ($sku === '' || $name === '') {
				continue;
			}
			if (stripos($sku, 'Mã') !== false || stripos($name, 'Tên hàng') !== false) {
				continue;
			}
			if ($price === null) {
				$price = 0;
			}
			$products[] = array(
				'source' => 'ccdc',
				'sku' => $sku,
				'name' => $name,
				'unit' => '',
				'product_group' => $group !== '' ? $group : 'CCDC',
				'item_type' => 'Product',
				'price' => $price,
				'price_lt_1m' => $price,
				'price_gte_1m' => $price,
				'price_gte_3m' => $price,
				'price_gte_5m' => $price,
				'price_gte_7m' => $price,
				'price_tuibao' => $price,
			);
		}
		return $products;
	}

	/**
	 * @param array $tiers
	 * @return array
	 */
	protected static function fillTiers(array $tiers) {
		$last = null;
		for ($i = 0; $i < 5; $i++) {
			if ($tiers[$i] !== null) {
				$last = $tiers[$i];
			} elseif ($last !== null) {
				$tiers[$i] = $last;
			}
		}
		$first = null;
		for ($i = 0; $i < 5; $i++) {
			if ($tiers[$i] !== null) {
				$first = $tiers[$i];
				break;
			}
		}
		if ($first !== null) {
			for ($i = 0; $i < 5; $i++) {
				if ($tiers[$i] === null) {
					$tiers[$i] = $first;
				}
			}
		}
		return $tiers;
	}

	protected static function money($raw) {
		$s = trim((string) $raw);
		if ($s === '' || $s === '-' || strtolower($s) === 'null') {
			return null;
		}
		$s = str_replace(array(' ', ','), array('', '.'), $s);
		$s = preg_replace('/[^\d.\-]/', '', $s);
		if ($s === '' || !is_numeric($s)) {
			return null;
		}
		return (float) $s;
	}

	protected static function cell(array $cells, $col1Based) {
		return isset($cells[$col1Based]) ? $cells[$col1Based] : '';
	}

	/**
	 * @return array{shared:array,sheets:array}
	 */
	protected static function openWorkbook($path) {
		$zip = new ZipArchive();
		if ($zip->open($path) !== true) {
			throw new Exception('Cannot open xlsx: ' . $path);
		}
		$ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
		$shared = array();
		$ssXml = $zip->getFromName('xl/sharedStrings.xml');
		if ($ssXml !== false) {
			$sx = @simplexml_load_string($ssXml);
			if ($sx) {
				$sx->registerXPathNamespace('m', $ns);
				foreach ($sx->xpath('//m:si') as $si) {
					$shared[] = self::xmlText($si);
				}
			}
		}

		$wbXml = $zip->getFromName('xl/workbook.xml');
		$relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
		if ($wbXml === false || $relsXml === false) {
			$zip->close();
			throw new Exception('Invalid xlsx workbook');
		}
		$wb = simplexml_load_string($wbXml);
		$rels = simplexml_load_string($relsXml);
		$wb->registerXPathNamespace('m', $ns);
		$ridMap = array();
		foreach ($rels->Relationship as $rel) {
			$ridMap[(string) $rel['Id']] = (string) $rel['Target'];
		}
		$sheets = array();
		foreach ($wb->xpath('//m:sheets/m:sheet') as $s) {
			$name = (string) $s['name'];
			$rid = (string) $s->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')->id;
			if ($rid === '') {
				$rid = (string) $s['id'];
			}
			$target = isset($ridMap[$rid]) ? $ridMap[$rid] : '';
			if ($target === '') {
				continue;
			}
			if (strpos($target, 'xl/') !== 0) {
				$target = 'xl/' . ltrim($target, '/');
			}
			$xml = $zip->getFromName($target);
			$sheets[] = array('name' => $name, 'xml' => $xml !== false ? $xml : '');
		}
		$zip->close();
		return array('shared' => $shared, 'sheets' => $sheets);
	}

	/**
	 * Concatenate all //t text nodes under a SimpleXML node (namespace-safe).
	 *
	 * @param SimpleXMLElement $node
	 * @return string
	 */
	protected static function xmlText($node) {
		$buf = '';
		if (!$node instanceof SimpleXMLElement) {
			return '';
		}
		foreach ($node->xpath('.//*[local-name()="t"]') as $t) {
			$buf .= (string) $t;
		}
		if ($buf === '' && isset($node->t)) {
			$buf = (string) $node->t;
		}
		return $buf;
	}

	/**
	 * @return array<int,array<int,string>>
	 */
	protected static function sheetRows(array $sheet, array $shared) {
		$xml = isset($sheet['xml']) ? $sheet['xml'] : '';
		if ($xml === '') {
			return array();
		}
		$sx = @simplexml_load_string($xml);
		if (!$sx) {
			return array();
		}
		$ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
		$sx->registerXPathNamespace('m', $ns);
		$out = array();
		foreach ($sx->xpath('//m:sheetData/m:row') as $row) {
			$r = (int) $row['r'];
			if ($r <= 0) {
				continue;
			}
			$cells = array();
			foreach ($row->c as $c) {
				$ref = (string) $c['r'];
				$col = self::colIndex($ref);
				if ($col <= 0) {
					continue;
				}
				$t = (string) $c['t'];
				$val = '';
				if (isset($c->v)) {
					$raw = (string) $c->v;
					if ($t === 's') {
						$idx = (int) $raw;
						$val = isset($shared[$idx]) ? $shared[$idx] : $raw;
					} else {
						$val = $raw;
					}
				} elseif (isset($c->is)) {
					$val = self::xmlText($c->is);
				}
				$cells[$col] = $val;
			}
			$out[$r] = $cells;
		}
		return $out;
	}

	protected static function colIndex($ref) {
		if (!preg_match('/^([A-Z]+)/i', $ref, $m)) {
			return 0;
		}
		$s = strtoupper($m[1]);
		$n = 0;
		$len = strlen($s);
		for ($i = 0; $i < $len; $i++) {
			$n = $n * 26 + (ord($s[$i]) - 64);
		}
		return $n;
	}
}
