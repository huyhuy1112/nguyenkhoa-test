<?php
/*+***********************************************************************************
 * Quote create: search customer across Contacts / Potentials / Leads.
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
		if (!in_array($scope, array('all', 'contacts', 'potentials', 'leads'), true)) {
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
				),
			));
		} catch (Exception $e) {
			$response->setError($e->getMessage());
		}
		$response->emit();
	}

	/**
	 * @return array{Contacts:array,Potentials:array,Leads:array}
	 */
	protected function searchGrouped($q, $limit, $scope) {
		$per = max(8, (int) $limit);
		$out = array(
			'Contacts' => array(),
			'Potentials' => array(),
			'Leads' => array(),
		);
		if ($scope === 'all' || $scope === 'contacts') {
			$out['Contacts'] = $this->searchContacts($q, $per);
		}
		if ($scope === 'all' || $scope === 'potentials') {
			$out['Potentials'] = $this->searchPotentials($q, $per);
		}
		if ($scope === 'all' || $scope === 'leads') {
			$out['Leads'] = $this->searchLeads($q, $per);
		}
		return $out;
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
			'cd.phone', 'cd.mobile', 'acc.accountname',
		));
		$sql = "SELECT cd.contactid, cd.firstname, cd.lastname, cd.phone, cd.mobile, acc.accountname
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
			$account = decode_html((string) $adb->query_result($res, $i, 'accountname'));
			$parts = array_filter(array($phone, $account));
			$rows[] = array(
				'id' => $id,
				'module' => 'Contacts',
				'module_label' => 'Khách hàng',
				'label' => $name !== '' ? $name : ('#' . $id),
				'subtitle' => implode(' · ', $parts),
				'phone' => $phone,
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
		));
		$sql = "SELECT p.potentialid, p.potentialname, p.contact_id, p.related_to, acc.accountname,
				cd.firstname, cd.lastname
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
			$subtitleParts = array();
			if ($pname !== '' && $pname !== $label) {
				$subtitleParts[] = $pname;
			}
			if ($account !== '' && $account !== $label) {
				$subtitleParts[] = $account;
			}
			$rows[] = array(
				'id' => $id,
				'module' => 'Potentials',
				'module_label' => 'Cơ hội',
				'label' => $label !== '' ? $label : ('#' . $id),
				'subtitle' => implode(' · ', $subtitleParts),
				'phone' => '',
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
