<?php
class DocumentTemplate_Save_Action extends Vtiger_Save_Action {
	protected function logDebug($message, array $context = array()) {
		$line = '[' . date('Y-m-d H:i:s') . '] ' . $message;
		if (!empty($context)) {
			$line .= ' | ' . json_encode($context);
		}
		$line .= PHP_EOL;
		@file_put_contents('logs/documenttemplate_save.log', $line, FILE_APPEND);
	}

	public function requiresPermission(\Vtiger_Request $request) {
		return array();
	}

	public function checkPermission($request) {
		return true;
	}

	public function process(Vtiger_Request $request) {
		try {
			$db = PearDatabase::getInstance();
			$userId = (int) Users_Record_Model::getCurrentUserModel()->getId();
			$now = date('Y-m-d H:i:s');

			$recordId = (int) $request->get('record');
			$copyFromId = (int) $request->get('copyFrom');

			$templatename = trim((string) $request->get('templatename'));
			$feature = (string) $request->get('feature');
			$description = (string) $request->get('description');
			$content = (string) $request->getRaw('content');
			$isdefault = ((string) $request->get('isdefault') === '1') ? 1 : 0;

			$this->logDebug('Save request received', array(
				'record' => $recordId,
				'copyFrom' => $copyFromId,
				'templatename_len' => strlen($templatename),
				'feature' => $feature,
				'content_len' => strlen($content),
				'isdefault' => $isdefault,
				'userId' => $userId,
			));

			if ($templatename === '' || $feature === '') {
				$viewerUrl = 'index.php?module=DocumentTemplate&view=Edit&app=TOOLS&record=' . $recordId . '&validation=1';
				$this->logDebug('Validation failed, redirecting', array('url' => $viewerUrl));
				header('Location: ' . $viewerUrl);
				exit;
			}

			$allowedFeatures = array('Invoice', 'Quote', 'Contract', 'Other');
			if (!in_array($feature, $allowedFeatures, true)) {
				$feature = 'Other';
			}

			$savedId = 0;
			if ($copyFromId > 0 || $recordId <= 0) {
				$mode = ($copyFromId > 0) ? 'copy' : 'create';
				$version = 1;
				$newId = (int) $db->getUniqueID('vtiger_documenttemplates');
				$sql = "INSERT INTO vtiger_documenttemplates
							(templateid, templatename, feature, description, content, version, isdefault, createdby, updatedby, createdtime, updatedtime, deleted)
						VALUES (?,?,?,?,?,?,?,?,?,?,?,0)";
				$db->pquery($sql, array(
					$newId, $templatename, $feature, $description, $content, $version,
					$isdefault, $userId, $userId, $now, $now,
				));
				$savedId = $newId;
				$this->logDebug('Insert success', array('mode' => $mode, 'templateid' => $savedId));
			} else {
				$current = $db->pquery(
					"SELECT version FROM vtiger_documenttemplates WHERE templateid = ? AND deleted = 0",
					array($recordId)
				);
				if ($db->num_rows($current) <= 0) {
					$this->logDebug('Update target missing, redirect list', array('record' => $recordId));
					header("Location: index.php?module=DocumentTemplate&view=List&app=TOOLS");
					exit;
				}
				$curRow = $db->fetchByAssoc($current);
				$newVersion = ((int) $curRow['version']) + 1;
				$db->pquery(
					"UPDATE vtiger_documenttemplates
						SET templatename = ?, feature = ?, description = ?, content = ?, version = ?, isdefault = ?, updatedby = ?, updatedtime = ?
					  WHERE templateid = ? AND deleted = 0",
					array($templatename, $feature, $description, $content, $newVersion, $isdefault, $userId, $now, $recordId)
				);
				$savedId = $recordId;
				$this->logDebug('Update success', array('templateid' => $savedId, 'version' => $newVersion));
			}

			if ($isdefault === 1 && $savedId > 0) {
				$db->pquery(
					"UPDATE vtiger_documenttemplates SET isdefault = 0 WHERE feature = ? AND templateid <> ? AND deleted = 0",
					array($feature, $savedId)
				);
			}

			// Keep post-save stable: list view is guaranteed to exist and avoids white-screen on broken detail routes.
			$loadUrl = 'index.php?module=DocumentTemplate&view=List&app=TOOLS&saved=1';
			$this->logDebug('Redirect after save', array('templateid' => $savedId, 'url' => $loadUrl));
			header("Location: $loadUrl");
			exit;
		} catch (Throwable $e) {
			$this->logDebug('Save exception', array(
				'message' => $e->getMessage(),
				'file' => $e->getFile(),
				'line' => $e->getLine(),
			));
			header('Location: index.php?module=DocumentTemplate&view=Edit&app=TOOLS&saveError=1');
			exit;
		}
	}
}
?>

