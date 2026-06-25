<?php
/*+***********************************************************************************
 * Admin-only endpoint to rebuild Users privilege flat files safely from web requests.
 *
 * This is meant for environments where running CLI scripts is not possible.
 * It rebuilds privileges in small batches to avoid timeouts/500 errors.
 *************************************************************************************/

class Users_RebuildPrivilegesAjax_Action extends Vtiger_Action_Controller {

	public function requiresPermission(Vtiger_Request $request) {
		return array();
	}

	public function checkPermission(Vtiger_Request $request) {
		$currentUser = Users_Record_Model::getCurrentUserModel();
		if (!$currentUser || !$currentUser->getId() || !$currentUser->isAdminUser()) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED', 'Vtiger'));
		}
		return true;
	}

	protected function getTargetRoots($targets) {
		$roots = array();

		$rootDirectory = vglobal('root_directory');
		if (!empty($rootDirectory)) {
			$mainRoot = rtrim($rootDirectory, "/\\") . DIRECTORY_SEPARATOR;
			if ($targets === 'main' || $targets === 'both') {
				$roots[] = $mainRoot;
			}
			$testRoot = $mainRoot . 'test' . DIRECTORY_SEPARATOR;
			if (($targets === 'test' || $targets === 'both') && is_dir($testRoot)) {
				$roots[] = $testRoot;
			}
		}

		$roots = array_values(array_unique(array_filter($roots)));
		return $roots;
	}

	protected function ensurePrivilegesDir($root) {
		$dir = $root . 'user_privileges';
		if (!is_dir($dir)) {
			@mkdir($dir, 0775, true);
		}
		@chmod($dir, 0775);
		return $dir;
	}

	protected function fileMissingForUser($root, $userId) {
		$dir = $root . 'user_privileges' . DIRECTORY_SEPARATOR;
		$u = $dir . 'user_privileges_' . $userId . '.php';
		$s = $dir . 'sharing_privileges_' . $userId . '.php';
		return (!is_file($u) || !is_file($s));
	}

	public function process(Vtiger_Request $request) {
		$response = new Vtiger_Response();

		$mode = $request->get('mode');
		$targets = $request->get('targets');
		$batch = (int) $request->get('batch');
		$offset = (int) $request->get('offset');

		if (empty($mode)) $mode = 'missing-only'; // missing-only | all
		if (empty($targets)) $targets = 'both';   // main | test | both
		if ($batch < 1) $batch = 25;
		if ($offset < 0) $offset = 0;

		$targetRoots = $this->getTargetRoots($targets);
		if (empty($targetRoots)) {
			$response->setError('NO_TARGET_ROOTS', 'No target roots found for privilege rebuild');
			$response->emit();
			return;
		}

		require_once('modules/Users/CreateUserPrivilegeFile.php');

		$db = PearDatabase::getInstance();
		$result = $db->pquery('SELECT id, user_name, status FROM vtiger_users ORDER BY id ASC', array());
		$totalUsers = $db->num_rows($result);

		$processed = 0;
		$rebuilt = 0;
		$skipped = 0;
		$errors = array();

		$oldRoot = vglobal('root_directory');

		for ($i = $offset; $i < $totalUsers && $processed < $batch; $i++) {
			$userId = (int) $db->query_result($result, $i, 'id');
			if ($userId < 1) {
				continue;
			}

			$processed++;

			foreach ($targetRoots as $root) {
				$this->ensurePrivilegesDir($root);

				$needs = ($mode === 'all') ? true : $this->fileMissingForUser($root, $userId);
				if (!$needs) {
					$skipped++;
					continue;
				}

				try {
					// Temporarily switch root_directory so vtiger's generator writes into the chosen root.
					vglobal('root_directory', $root);
					@createUserPrivilegesfile($userId);
					@createUserSharingPrivilegesfile($userId);
					Vtiger_AccessControl::clearUserPrivileges($userId);
					$rebuilt++;
				} catch (Exception $e) {
					$errors[] = array(
						'userId' => $userId,
						'root' => $root,
						'message' => $e->getMessage(),
					);
				}
			}
		}

		// Restore root_directory
		if (!empty($oldRoot)) {
			vglobal('root_directory', $oldRoot);
		}

		$nextOffset = $offset + $processed;
		$done = ($nextOffset >= $totalUsers);

		$response->setResult(array(
			'mode' => $mode,
			'targets' => $targets,
			'targetRoots' => $targetRoots,
			'totalUsers' => $totalUsers,
			'offset' => $offset,
			'processed' => $processed,
			'rebuilt' => $rebuilt,
			'skipped' => $skipped,
			'nextOffset' => $nextOffset,
			'done' => $done,
			'errors' => $errors,
		));
		$response->emit();
	}
}

