<?php
/**
 * ProductsServices Save — after create/edit, return to the Hàng hoá list (not Detail).
 */
class ProductsServices_Save_Action extends Vtiger_Save_Action {

	public function saveRecord($request) {
		require_once 'modules/ProductsServices/helpers/UnitOptions.php';
		ProductsServices_UnitOptions_Helper::ensurePicklist();
		if ($request->get('unit') !== null && $request->get('unit') !== '') {
			$request->set('unit', ProductsServices_UnitOptions_Helper::normalize($request->get('unit')));
		}
		$recordModel = parent::saveRecord($request);
		$this->saveQtyRules((int) $recordModel->getId(), $request);
		return $recordModel;
	}

	protected function saveQtyRules($recordId, Vtiger_Request $request) {
		if ($recordId <= 0 || !array_key_exists('mk_kl_qty_rules', $_REQUEST)) {
			return;
		}
		$raw = html_entity_decode(trim((string) $request->get('mk_kl_qty_rules')), ENT_QUOTES, 'UTF-8');
		$db = PearDatabase::getInstance();
		$check = $db->pquery('SHOW COLUMNS FROM vtiger_productsservices LIKE ?', array('kl_qty_rules'));
		if (!$check || $db->num_rows($check) < 1) {
			$db->pquery('ALTER TABLE vtiger_productsservices ADD COLUMN kl_qty_rules TEXT NULL', array());
		}
		$data = $raw === '' ? array() : json_decode($raw, true);
		$rules = array();
		$allowed = array('gte_3m' => true, 'gte_5m' => true, 'gte_7m' => true);
		if (is_array($data) && !empty($data['rules']) && is_array($data['rules'])) {
			foreach ($data['rules'] as $rule) {
				if (!is_array($rule)) {
					continue;
				}
				$min = isset($rule['min']) ? (int) $rule['min'] : 0;
				$tier = isset($rule['tier']) ? (string) $rule['tier'] : '';
				$unit = $this->cleanQtyUnit(isset($rule['unit']) ? $rule['unit'] : '');
				if ($min < 1 || $min > 1000000 || $unit === '' || empty($allowed[$tier])) {
					continue;
				}
				$rules[] = array('min' => $min, 'unit' => $unit, 'tier' => $tier);
				if (count($rules) >= 12) {
					break;
				}
			}
		}
		if (!$rules) {
			$db->pquery(
				'UPDATE vtiger_productsservices SET kl_qty_rules = NULL WHERE productsservicesid = ?',
				array($recordId)
			);
			return;
		}
		$sell = $this->cleanQtyUnit(isset($data['sell_unit']) ? $data['sell_unit'] : '');
		if ($sell === '') {
			$sell = $rules[0]['unit'];
		}
		$per = isset($data['per_thung']) ? (int) $data['per_thung'] : 0;
		if ($per < 0) {
			$per = 0;
		}
		if ($per > 10000) {
			$per = 10000;
		}
		$payload = json_encode(array(
			'sell_unit' => $sell,
			'per_thung' => $per,
			'rules' => $rules,
		), JSON_UNESCAPED_UNICODE);
		$db->pquery(
			'UPDATE vtiger_productsservices SET kl_qty_rules = ? WHERE productsservicesid = ?',
			array($payload, $recordId)
		);
	}

	protected function cleanQtyUnit($value) {
		$value = mb_strtolower(trim((string) $value), 'UTF-8');
		$map = array(
			'lon' => 'lon',
			'bich' => 'bich', 'bịch' => 'bich',
			'hop' => 'hop', 'hộp' => 'hop',
			'bao' => 'bao',
			'thung' => 'thung', 'thùng' => 'thung',
			'binh' => 'binh', 'bình' => 'binh',
			'chai' => 'chai',
			'goi' => 'goi', 'gói' => 'goi',
			'cai' => 'cai', 'cái' => 'cai',
			'hu' => 'hu', 'hũ' => 'hu',
			'can' => 'can',
			'cuon' => 'cuon', 'cuộn' => 'cuon',
			'con' => 'con',
			'kg' => 'kg',
			'bo' => 'bo', 'bộ' => 'bo',
			'set' => 'set',
			'lit' => 'lit', 'lít' => 'lit',
		);
		if (isset($map[$value])) {
			return $map[$value];
		}
		$value = preg_replace('/[^a-z0-9]+/', '', $value);
		if ($value === '' || strlen($value) > 16) {
			return '';
		}
		return $value;
	}

	public function process(Vtiger_Request $request) {
		if (!$request->get('relationOperation') && !$request->get('returntab_label')) {
			$request->set('returnToList', '1');
			if (!$request->get('returnmodule')) {
				$request->set('returnmodule', 'ProductsServices');
			}
			if (!$request->get('returnview')) {
				$request->set('returnview', 'List');
			}
			$appName = (string) $request->get('appName');
			if ($appName === '') {
				$app = strtoupper((string) $request->get('app'));
				if ($app !== 'SALES' && $app !== 'INVENTORY') {
					$app = 'INVENTORY';
				}
				$request->set('appName', '&app=' . $app);
			}
		}
		parent::process($request);
	}
}
