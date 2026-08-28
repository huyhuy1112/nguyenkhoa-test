<?php
/*+***********************************************************************************
 * Quote create: search customer across Contacts / Potentials / Leads / ServiceContracts
 * (Nhượng quyền → Giá Tuibao).
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
			$out['ServiceContracts'] = $this->searchServiceContracts($q, $per);
		}
		return $out;
	}

	protected function hasBaceScProfile($adb) {
		static $ok = null;
		if ($ok !== null) {
			return $ok;
		}
		$rs = $adb->pquery('SHOW TABLES LIKE ?', array('bace_sc_profile'));
		$ok = $rs && $adb->num_rows($rs) > 0;
		return $ok;
	}

	/**
	 * Khách hàng nhượng quyền (ServiceContracts) → Giá Tuibao.
	 */
	protected function searchServiceContracts($q, $limit) {
		if (!Users_Privileges_Model::isPermitted('ServiceContracts', 'DetailView')) {
			return array();
		}
		$adb = PearDatabase::getInstance();
		$hasProfile = $this->hasBaceScProfile($adb);
		$columns = array('sc.subject', 'sc.contract_no', 'acc.accountname');
		if ($hasProfile) {
			$columns = array_merge($columns, array('p.phone', 'p.email', 'p.affiliate_code', 'p.business_note', 'p.address_line'));
		}
		list($where, $params) = $this->buildLikeClause($adb, $q, $columns);
		$profileJoin = $hasProfile
			? 'LEFT JOIN bace_sc_profile p ON p.servicecontractsid = sc.servicecontractsid'
			: '';
		$profileSelect = $hasProfile
			? ', p.phone, p.email, p.affiliate_code, p.business_note, p.address_line'
			: ', NULL AS phone, NULL AS email, NULL AS affiliate_code, NULL AS business_note, NULL AS address_line';
		$sql = "SELECT sc.servicecontractsid, sc.subject, sc.contract_no, sc.sc_related_to,
				acc.accountname{$profileSelect}
			FROM vtiger_servicecontracts sc
			INNER JOIN vtiger_crmentity ce ON ce.crmid = sc.servicecontractsid AND ce.deleted = 0
			LEFT JOIN vtiger_account acc ON acc.accountid = sc.sc_related_to
			{$profileJoin}
			WHERE {$where}
			ORDER BY ce.modifiedtime DESC
			LIMIT " . (int) $limit;
		$res = $adb->pquery($sql, $params);
		$rows = array();
		if (!$res) {
			return $rows;
		}
		for ($i = 0; $i < $adb->num_rows($res); $i++) {
			$id = (int) $adb->query_result($res, $i, 'servicecontractsid');
			$subject = decode_html((string) $adb->query_result($res, $i, 'subject'));
			$contractNo = decode_html((string) $adb->query_result($res, $i, 'contract_no'));
			$accountId = (int) $adb->query_result($res, $i, 'sc_related_to');
			$account = decode_html((string) $adb->query_result($res, $i, 'accountname'));
			$phone = decode_html((string) $adb->query_result($res, $i, 'phone'));
			$email = decode_html((string) $adb->query_result($res, $i, 'email'));
			$code = decode_html((string) $adb->query_result($res, $i, 'affiliate_code'));
			$businessNote = trim(decode_html((string) $adb->query_result($res, $i, 'business_note')));
			$addressLine = trim(decode_html((string) $adb->query_result($res, $i, 'address_line')));
			$address = $businessNote !== '' ? $businessNote : $addressLine;
			$label = $subject !== '' ? $subject : ($account !== '' ? $account : ('#' . $id));
			$parts = array_filter(array($code, $contractNo, $account, $phone, $email));
			$rows[] = array(
				'id' => $id,
				'module' => 'ServiceContracts',
				'module_label' => 'Nhượng quyền',
				'label' => $label,
				'subtitle' => implode(' · ', $parts),
				'phone' => $phone,
				'email' => $email,
				'extra' => $account,
				'address' => $address,
				'business_note' => $businessNote,
				'contact_id' => 0,
				'potential_id' => 0,
				'lead_id' => 0,
				'servicecontract_id' => $id,
				'account_id' => $accountId,
				'price_channel' => 'tuibao',
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
		$sql = "SELECT cd.contactid, cd.firstname, cd.lastname, cd.phone, cd.mobile, cd.email, cd.secondaryemail, acc.accountname
			FROM vtiger_contactdetails cd
			INNER JOIN vtiger_crmentity ce ON ce.crmid = cd.contactid AND ce.deleted = 0
			LEFT JOIN vtiger_account acc ON acc.accountid = cd.accountid
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
			$parts = array_filter(array($phone, $email, $account));
			$rows[] = array(
				'id' => $id,
				'module' => 'Contacts',
				'module_label' => 'Khách hàng',
				'label' => $name !== '' ? $name : ('#' . $id),
				'subtitle' => implode(' · ', $parts),
				'phone' => $phone,
				'email' => $email,
				'extra' => $account,
				'contact_id' => $id,
				'potential_id' => 0,
				'lead_id' => 0,
			);
		}
		return $rows;
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
		));
		$sql = "SELECT p.potentialid, p.potentialname, p.contact_id, p.related_to, acc.accountname,
				acc.phone AS acc_phone, acc.email1 AS acc_email,
				cd.firstname, cd.lastname, cd.phone AS contact_phone, cd.mobile AS contact_mobile, cd.email AS contact_email
			FROM vtiger_potential p
			INNER JOIN vtiger_crmentity ce ON ce.crmid = p.potentialid AND ce.deleted = 0
			LEFT JOIN vtiger_account acc ON acc.accountid = p.related_to
			LEFT JOIN vtiger_contactdetails cd ON cd.contactid = p.contact_id
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
			$phone = decode_html((string) $adb->query_result($res, $i, 'contact_mobile'));
			if ($phone === '') {
				$phone = decode_html((string) $adb->query_result($res, $i, 'contact_phone'));
			}
			if ($phone === '') {
				$phone = decode_html((string) $adb->query_result($res, $i, 'acc_phone'));
			}
			$email = decode_html((string) $adb->query_result($res, $i, 'contact_email'));
			if ($email === '') {
				$email = decode_html((string) $adb->query_result($res, $i, 'acc_email'));
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
			$rows[] = array(
				'id' => $id,
				'module' => 'Potentials',
				'module_label' => 'Cơ hội',
				'label' => $label !== '' ? $label : ('#' . $id),
				'subtitle' => implode(' · ', $subtitleParts),
				'phone' => $phone,
				'email' => $email,
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
