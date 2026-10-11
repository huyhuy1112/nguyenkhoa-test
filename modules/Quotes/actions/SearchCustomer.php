<?php
/*+***********************************************************************************
 * Quote create: search customer across Contacts / Potentials / Leads / Accounts.
 * Tab Nhượng quyền = Danh sách chủ quán (Accounts) → Giá Tuibao.
 * Bucket key stays ServiceContracts so the existing tab wiring keeps working.
 *************************************************************************************/

class Quotes_SearchCustomer_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		if (!Users_Privileges_Model::isPermitted('Quotes', 'EditView')
			&& !Users_Privileges_Model::isPermitted('Quotes', 'CreateView')) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
		}
	}

	public function process(Vtiger_Request $request) {
		$response = new Vtiger_Response();
		$q = trim(decode_html((string) $request->get('q')));
		if ($q === '') {
			$q = trim(decode_html((string) $request->get('search_value')));
		}
		$limit = (int) $request->get('limit');
		if ($limit <= 0 || $limit > 60) {
			$limit = 30;
		}
		$scope = strtolower(trim((string) $request->get('scope')));
		if (!in_array($scope, array(
			'all', 'contacts', 'potentials', 'leads',
			'franchise', 'servicecontracts', 'nhuong_quyen',
		), true)) {
			$scope = 'all';
		}
		try {
			$grouped = $this->searchGrouped($q, $limit, $scope);
			$flat = array();
			foreach ($grouped as $bucket) {
				$flat = array_merge($flat, $bucket);
			}
			$response->setResult(array(
				'success' => true,
				'query' => $q,
				'results' => $flat,
				'grouped' => $grouped,
				'counts' => array(
					'Contacts' => count($grouped['Contacts']),
					'Potentials' => count($grouped['Potentials']),
					'Leads' => count($grouped['Leads']),
					'ServiceContracts' => count($grouped['ServiceContracts']),
				),
			));
		} catch (Exception $e) {
			$response->setError($e->getMessage());
		}
		$response->emit();
	}

	/**
	 * @return array{Contacts:array,Potentials:array,Leads:array,ServiceContracts:array}
	 */
	protected function searchGrouped($q, $limit, $scope) {
		$per = max(8, (int) $limit);
		$out = array(
			'Contacts' => array(),
			'Potentials' => array(),
			'Leads' => array(),
			'ServiceContracts' => array(),
		);
		$wantFranchise = in_array($scope, array('all', 'franchise', 'servicecontracts', 'nhuong_quyen'), true);
		if ($scope === 'all' || $scope === 'contacts') {
			$out['Contacts'] = $this->searchContacts($q, $per);
		}
		if ($scope === 'all' || $scope === 'potentials') {
			$out['Potentials'] = $this->searchPotentials($q, $per);
		}
		if ($scope === 'all' || $scope === 'leads') {
			$out['Leads'] = $this->searchLeads($q, $per);
		}
		if ($wantFranchise) {
			$out['ServiceContracts'] = $this->searchFranchiseAccounts($q, $per);
		}
		return $out;
	}

	protected function accountColumnExists($adb, $column) {
		static $cache = array();
		$column = (string) $column;
		if (array_key_exists($column, $cache)) {
			return $cache[$column];
		}
		$rs = $adb->pquery('SHOW COLUMNS FROM vtiger_account LIKE ?', array($column));
		$cache[$column] = $rs && $adb->num_rows($rs) > 0;
		return $cache[$column];
	}

	/**
	 * Danh sách chủ quán (Accounts) → Giá Tuibao.
	 * Same live rows as the Accounts list, not ServiceContracts prospects.
	 */
	protected function searchFranchiseAccounts($q, $limit) {
		if (!Users_Privileges_Model::isPermitted('Accounts', 'DetailView')) {
			return array();
		}
		$adb = PearDatabase::getInstance();
		$hasStore = $this->accountColumnExists($adb, 'tb_store_address');
		$hasPartyPhone = $this->accountColumnExists($adb, 'tb_party_b_phone');
		$columns = array('a.accountname', 'a.account_no', 'a.phone', 'a.email1', 'bill.bill_street');
		if ($hasStore) {
			$columns[] = 'a.tb_store_address';
		}
		if ($hasPartyPhone) {
			$columns[] = 'a.tb_party_b_phone';
		}
		list($where, $params) = $this->buildLikeClause($adb, $q, $columns);
		$storeSelect = $hasStore ? 'a.tb_store_address' : 'NULL AS tb_store_address';
		$partySelect = $hasPartyPhone ? 'a.tb_party_b_phone' : 'NULL AS tb_party_b_phone';
		$sql = "SELECT a.accountid, a.accountname, a.account_no, a.phone, a.email1,
				{$storeSelect}, {$partySelect}, bill.bill_street, bill.bill_city
			FROM vtiger_account a
			INNER JOIN vtiger_crmentity ce ON ce.crmid = a.accountid AND ce.deleted = 0 AND ce.setype = 'Accounts'
			LEFT JOIN vtiger_accountbillads bill ON bill.accountaddressid = a.accountid
			WHERE {$where}
			ORDER BY ce.modifiedtime DESC
			LIMIT " . (int) $limit;
		$res = $adb->pquery($sql, $params);
		$rows = array();
		if (!$res) {
			return $rows;
		}
		for ($i = 0; $i < $adb->num_rows($res); $i++) {
			$id = (int) $adb->query_result($res, $i, 'accountid');
			$name = decode_html((string) $adb->query_result($res, $i, 'accountname'));
			$code = decode_html((string) $adb->query_result($res, $i, 'account_no'));
			$phone = decode_html((string) $adb->query_result($res, $i, 'phone'));
			if ($phone === '') {
				$phone = decode_html((string) $adb->query_result($res, $i, 'tb_party_b_phone'));
			}
			$email = decode_html((string) $adb->query_result($res, $i, 'email1'));
			$address = trim(decode_html((string) $adb->query_result($res, $i, 'tb_store_address')));
			if ($address === '') {
				$address = trim(decode_html((string) $adb->query_result($res, $i, 'bill_street')));
				$city = trim(decode_html((string) $adb->query_result($res, $i, 'bill_city')));
				if ($city !== '') {
					$address = $address !== '' ? ($address . ', ' . $city) : $city;
				}
			}
			$label = $name !== '' ? $name : ($code !== '' ? $code : ('#' . $id));
			$parts = array_filter(array($code, $phone, $email));
			$rows[] = array(
				'id' => $id,
				'module' => 'Accounts',
				'module_label' => 'Chủ quán',
				'label' => $label,
				'subtitle' => implode(' · ', $parts),
				'phone' => $phone,
				'email' => $email,
				'extra' => $label,
				'address' => $address,
				'business_note' => '',
				'contact_id' => 0,
				'potential_id' => 0,
				'lead_id' => 0,
				'servicecontract_id' => 0,
				'account_id' => $id,
				'price_channel' => 'tuibao',
				'customer_code' => $code !== '' ? $code : 'TUIBAO',
			);
		}
		return $rows;
	}

	protected function buildLikeClause($adb, $q, $columns) {
		$q = trim((string) $q);
		if ($q === '') {
			return array('1=1', array());
		}
		$like = '%' . $adb->sql_escape_string($q) . '%';
		$parts = array();
		$params = array();
		foreach ($columns as $col) {
			$parts[] = $col . ' LIKE ?';
			$params[] = $like;
		}
		return array('(' . implode(' OR ', $parts) . ')', $params);
	}

	protected function searchContacts($q, $limit) {
		if (!Users_Privileges_Model::isPermitted('Contacts', 'DetailView')) {
			return array();
		}
		$adb = PearDatabase::getInstance();
		list($where, $params) = $this->buildLikeClause($adb, $q, array(
			'cd.firstname', 'cd.lastname',
			"CONCAT(IFNULL(cd.firstname,''),' ',IFNULL(cd.lastname,''))",
			'cd.phone', 'cd.mobile', 'cd.email', 'cd.secondaryemail',
			'acc.accountname',
		));
		$sql = "SELECT cd.contactid, cd.contact_no, cd.firstname, cd.lastname, cd.phone, cd.mobile, cd.email, cd.secondaryemail,
				cd.accountid, acc.accountname,
				ca.mailingstreet, ca.mailingcity
			FROM vtiger_contactdetails cd
			INNER JOIN vtiger_crmentity ce ON ce.crmid = cd.contactid AND ce.deleted = 0
			LEFT JOIN vtiger_account acc ON acc.accountid = cd.accountid
			LEFT JOIN vtiger_contactaddress ca ON ca.contactaddressid = cd.contactid
			WHERE {$where}
			ORDER BY ce.modifiedtime DESC
			LIMIT " . (int) $limit;
		$res = $adb->pquery($sql, $params);
		$rows = array();
		for ($i = 0; $res && $i < $adb->num_rows($res); $i++) {
			$id = (int) $adb->query_result($res, $i, 'contactid');
			$first = decode_html((string) $adb->query_result($res, $i, 'firstname'));
			$last = decode_html((string) $adb->query_result($res, $i, 'lastname'));
			$name = trim($first . ' ' . $last);
			if ($name === '') {
				$name = $last !== '' ? $last : $first;
			}
			$phone = decode_html((string) $adb->query_result($res, $i, 'phone'));
			if ($phone === '') {
				$phone = decode_html((string) $adb->query_result($res, $i, 'mobile'));
			}
			$email = decode_html((string) $adb->query_result($res, $i, 'email'));
			if ($email === '') {
				$email = decode_html((string) $adb->query_result($res, $i, 'secondaryemail'));
			}
			$account = decode_html((string) $adb->query_result($res, $i, 'accountname'));
			$address = $this->firstFilled(array(
				$adb->query_result($res, $i, 'mailingstreet'),
			));
			$city = $this->firstFilled(array($adb->query_result($res, $i, 'mailingcity')));
			if ($city !== '' && $address !== '' && stripos($address, $city) === false) {
				$address .= ', ' . $city;
			} elseif ($address === '') {
				$address = $city;
			}
			$parts = array_filter(array($phone, $email, $address, $account));
			$rows[] = array(
				'id' => $id,
				'module' => 'Contacts',
				'module_label' => 'Khách hàng',
				'label' => $name !== '' ? $name : ('#' . $id),
				'subtitle' => implode(' · ', $parts),
				'phone' => $phone,
				'email' => $email,
				'address' => $address,
				'account_id' => (int) $adb->query_result($res, $i, 'accountid'),
				'extra' => $account,
				'contact_id' => $id,
				'potential_id' => 0,
				'lead_id' => 0,
				'customer_code' => decode_html((string) $adb->query_result($res, $i, 'contact_no')),
			);
		}
		return $rows;
	}

	protected function firstFilled(array $values) {
		foreach ($values as $value) {
			$text = trim(decode_html((string) $value));
			if ($text !== '' && $text !== '-' && $text !== '--') {
				return $text;
			}
		}
		return '';
	}

	protected function searchPotentials($q, $limit) {
		if (!Users_Privileges_Model::isPermitted('Potentials', 'DetailView')) {
			return array();
		}
		$adb = PearDatabase::getInstance();
		list($where, $params) = $this->buildLikeClause($adb, $q, array(
			'p.potentialname', 'acc.accountname',
			'cd.firstname', 'cd.lastname',
			"CONCAT(IFNULL(cd.firstname,''),' ',IFNULL(cd.lastname,''))",
			'cd.phone', 'cd.mobile', 'cd.email',
			'acc.phone', 'acc.email1',
			'pp.phone', 'la.phone', 'la.mobile', 'ld.email',
		));
		$sql = "SELECT p.potentialid, p.potentialname, p.contact_id, p.related_to, acc.accountname,
				acc.phone AS acc_phone, acc.email1 AS acc_email,
				cd.firstname, cd.lastname, cd.phone AS contact_phone, cd.mobile AS contact_mobile, cd.email AS contact_email,
				pp.phone AS pot_phone, pp.address_line AS pot_address, pp.district AS pot_district,
				la.phone AS lead_phone, la.mobile AS lead_mobile, la.lane AS lead_lane,
				lp.address_line AS lead_address, lp.district AS lead_district, ld.email AS lead_email,
				ca.mailingstreet AS contact_street, ca.mailingcity AS contact_city
			FROM vtiger_potential p
			INNER JOIN vtiger_crmentity ce ON ce.crmid = p.potentialid AND ce.deleted = 0
			LEFT JOIN vtiger_account acc ON acc.accountid = p.related_to
			LEFT JOIN vtiger_contactdetails cd ON cd.contactid = p.contact_id
			LEFT JOIN vtiger_contactaddress ca ON ca.contactaddressid = p.contact_id
			LEFT JOIN bace_potential_profile pp ON pp.potentialid = p.potentialid
			LEFT JOIN bace_lead_profile lp ON lp.potential_id = p.potentialid
			LEFT JOIN vtiger_leaddetails ld ON ld.leadid = lp.leadid
			LEFT JOIN vtiger_leadaddress la ON la.leadaddressid = lp.leadid
			WHERE {$where}
			ORDER BY ce.modifiedtime DESC
			LIMIT " . (int) $limit;
		$res = $adb->pquery($sql, $params);
		$rows = array();
		for ($i = 0; $res && $i < $adb->num_rows($res); $i++) {
			$id = (int) $adb->query_result($res, $i, 'potentialid');
			$pname = decode_html((string) $adb->query_result($res, $i, 'potentialname'));
			$contactId = (int) $adb->query_result($res, $i, 'contact_id');
			$first = decode_html((string) $adb->query_result($res, $i, 'firstname'));
			$last = decode_html((string) $adb->query_result($res, $i, 'lastname'));
			$cname = trim($first . ' ' . $last);
			$account = decode_html((string) $adb->query_result($res, $i, 'accountname'));
			$customer = $cname !== '' ? $cname : $account;
			$label = $customer !== '' ? $customer : $pname;
			$phone = $this->firstFilled(array(
				$adb->query_result($res, $i, 'pot_phone'),
				$adb->query_result($res, $i, 'contact_mobile'),
				$adb->query_result($res, $i, 'contact_phone'),
				$adb->query_result($res, $i, 'lead_mobile'),
				$adb->query_result($res, $i, 'lead_phone'),
				$adb->query_result($res, $i, 'acc_phone'),
			));
			$email = $this->firstFilled(array(
				$adb->query_result($res, $i, 'contact_email'),
				$adb->query_result($res, $i, 'lead_email'),
				$adb->query_result($res, $i, 'acc_email'),
			));
			$address = $this->firstFilled(array(
				$adb->query_result($res, $i, 'pot_address'),
				$adb->query_result($res, $i, 'lead_address'),
				$adb->query_result($res, $i, 'lead_lane'),
				$adb->query_result($res, $i, 'contact_street'),
			));
			$district = $this->firstFilled(array(
				$adb->query_result($res, $i, 'pot_district'),
				$adb->query_result($res, $i, 'lead_district'),
				$adb->query_result($res, $i, 'contact_city'),
			));
			if ($district !== '' && $address !== '' && stripos($address, $district) === false) {
				$address .= ', ' . $district;
			} elseif ($address === '') {
				$address = $district;
			}
			$subtitleParts = array();
			if ($pname !== '' && $pname !== $label) {
				$subtitleParts[] = $pname;
			}
			if ($account !== '' && $account !== $label) {
				$subtitleParts[] = $account;
			}
			if ($phone !== '') {
				$subtitleParts[] = $phone;
			}
			if ($email !== '') {
				$subtitleParts[] = $email;
			}
			if ($address !== '') {
				$subtitleParts[] = $address;
			}
			$rows[] = array(
				'id' => $id,
				'module' => 'Potentials',
				'module_label' => 'Cơ hội',
				'label' => $label !== '' ? $label : ('#' . $id),
				'subtitle' => implode(' · ', $subtitleParts),
				'phone' => $phone,
				'email' => $email,
				'address' => $address,
				'extra' => $pname,
				'contact_id' => $contactId,
				'potential_id' => $id,
				'lead_id' => 0,
			);
		}
		return $rows;
	}

	protected function searchLeads($q, $limit) {
		if (!Users_Privileges_Model::isPermitted('Leads', 'DetailView')) {
			return array();
		}
		$adb = PearDatabase::getInstance();
		// phone/mobile live on vtiger_leadaddress (not leaddetails)
		list($where, $params) = $this->buildLikeClause($adb, $q, array(
			'l.firstname', 'l.lastname',
			"CONCAT(IFNULL(l.firstname,''),' ',IFNULL(l.lastname,''))",
			"CONCAT(IFNULL(l.lastname,''),' ',IFNULL(l.firstname,''))",
			'l.company', 'l.email', 'l.secondaryemail',
			'la.phone', 'la.mobile',
		));
		$sql = "SELECT l.leadid, l.firstname, l.lastname, l.company, l.email,
				la.phone, la.mobile
			FROM vtiger_leaddetails l
			INNER JOIN vtiger_crmentity ce ON ce.crmid = l.leadid AND ce.deleted = 0
			LEFT JOIN vtiger_leadaddress la ON la.leadaddressid = l.leadid
			WHERE l.converted = 0 AND {$where}
			ORDER BY ce.modifiedtime DESC
			LIMIT " . (int) $limit;
		$res = $adb->pquery($sql, $params);
		$rows = array();
		if (!$res) {
			return $rows;
		}
		for ($i = 0; $i < $adb->num_rows($res); $i++) {
			$id = (int) $adb->query_result($res, $i, 'leadid');
			$first = decode_html((string) $adb->query_result($res, $i, 'firstname'));
			$last = decode_html((string) $adb->query_result($res, $i, 'lastname'));
			$name = trim($first . ' ' . $last);
			if ($name === '') {
				$name = $last !== '' ? $last : $first;
			}
			$company = decode_html((string) $adb->query_result($res, $i, 'company'));
			if ($company === '-' || $company === '--') {
				$company = '';
			}
			$email = decode_html((string) $adb->query_result($res, $i, 'email'));
			$phone = decode_html((string) $adb->query_result($res, $i, 'phone'));
			if ($phone === '') {
				$phone = decode_html((string) $adb->query_result($res, $i, 'mobile'));
			}
			$parts = array_filter(array($company, $phone, $email));
			$rows[] = array(
				'id' => $id,
				'module' => 'Leads',
				'module_label' => 'Leads',
				'label' => $name !== '' ? $name : ($company !== '' ? $company : ('#' . $id)),
				'subtitle' => implode(' · ', $parts),
				'phone' => $phone,
				'extra' => $company,
				'contact_id' => 0,
				'potential_id' => 0,
				'lead_id' => $id,
			);
		}
		return $rows;
	}
}
