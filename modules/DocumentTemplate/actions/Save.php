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
			require_once 'modules/DocumentTemplate/helpers/TemplateSetup.php';
			DocumentTemplate_TemplateSetup_Helper::runAll();

			$db = PearDatabase::getInstance();
			$userId = (int) Users_Record_Model::getCurrentUserModel()->getId();
			$now = date('Y-m-d H:i:s');

			$recordId = (int) $request->get('record');
			$copyFromId = (int) $request->get('copyFrom');

			$templatename = trim((string) $request->get('templatename'));
			$feature = (string) $request->get('feature');
			$description = (string) $request->get('description');
			$content = (string) $request->getRaw('content');
			// BA: default templates are system-seeded and protected; user cannot set isdefault via UI.
			$isdefault = 0;

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
			// BA: copy-first workflow. Block direct create without copyFrom.
			if ($recordId <= 0 && $copyFromId <= 0) {
				header('Location: index.php?module=DocumentTemplate&view=List&app=TOOLS&copyFirst=1');
				exit;
			}

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
				DocumentTemplate_TemplateSetup_Helper::recordHistory($db, $savedId, 1, $userId, $now, $mode, array(
					'templatename' => $templatename,
					'description' => $description,
					'content' => $content,
				));
			} else {
				// BA: block edit of protected default templates.
				$defaultCheck = $db->pquery(
					"SELECT isdefault, version FROM vtiger_documenttemplates WHERE templateid = ? AND deleted = 0",
					array($recordId)
				);
				if ($db->num_rows($defaultCheck) <= 0) {
					header("Location: index.php?module=DocumentTemplate&view=List&app=TOOLS");
					exit;
				}
				$isdefaultExisting = (int) $db->query_result($defaultCheck, 0, 'isdefault');
				if ($isdefaultExisting === 1) {
					header('Location: index.php?module=DocumentTemplate&view=Detail&record='.$recordId.'&app=TOOLS&readonlyDefault=1');
					exit;
				}

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
						SET templatename = ?, feature = ?, description = ?, content = ?, version = ?, isdefault = 0, updatedby = ?, updatedtime = ?
					  WHERE templateid = ? AND deleted = 0",
					array($templatename, $feature, $description, $content, $newVersion, $userId, $now, $recordId)
				);
				$savedId = $recordId;
				$this->logDebug('Update success', array('templateid' => $savedId, 'version' => $newVersion));
				DocumentTemplate_TemplateSetup_Helper::recordHistory($db, $savedId, $newVersion, $userId, $now, 'edit', array(
					'templatename' => $templatename,
					'description' => $description,
					'content' => $content,
				));
			}

			$loadUrl = 'index.php?module=DocumentTemplate&view=Detail&record=' . (int) $savedId . '&app=TOOLS&saved=1';
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

