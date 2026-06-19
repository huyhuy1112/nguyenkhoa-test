<?php
/*+***********************************************************************************
 * Leads Detail feed — ModTracker updates + ModComments for modern detail UI.
 *************************************************************************************/

class Leads_DetailFeedService {

	const MODULE = 'Leads';

	public static function getComments($leadId, $limit = 50) {
		if (!vtlib_isModuleActive('ModComments')) {
			return array();
		}
		require_once 'modules/ModComments/models/Record.php';
		$leadId = self::resolveLeadId($leadId);
		if (!$leadId) {
			return array();
		}
		$records = ModComments_Record_Model::getAllParentComments($leadId);
		if (!is_array($records)) {
			return array();
		}
		$out = array();
		$limit = max(1, (int)$limit);
		foreach ($records as $rec) {
			if (count($out) >= $limit) {
				break;
			}
			$out[] = self::mapComment($rec);
		}
		return $out;
	}

	public static function saveComment($leadId, $text) {
		global $current_user;
		if (!vtlib_isModuleActive('ModComments')) {
			throw new Exception('ModComments module is not active.');
		}
		$leadId = self::resolveLeadId($leadId);
		if (!$leadId) {
			throw new Exception('Lead not found.');
		}
		$text = trim((string)$text);
		if ($text === '') {
			throw new Exception('Comment cannot be empty.');
		}
		if (!Users_Privileges_Model::isPermitted(self::MODULE, 'DetailView', $leadId)) {
			throw new Exception(vtranslate('LBL_PERMISSION_DENIED'));
		}

		$recordModel = Vtiger_Record_Model::getCleanInstance('ModComments');
		$userId = (int)$current_user->id;
		$recordModel->set('mode', '');
		$recordModel->set('commentcontent', $text);
		$recordModel->set('related_to', $leadId);
		$recordModel->set('assigned_user_id', $userId);
		$recordModel->set('userid', $userId);
		$recordModel->save();

		require_once 'modules/ModComments/models/Record.php';
		$saved = ModComments_Record_Model::getInstanceById($recordModel->getId(), 'ModComments');
		return self::mapComment($saved);
	}

	public static function getUpdates($leadId, $limit = 30) {
		if (!vtlib_isModuleActive('ModTracker')) {
			return array();
		}
		require_once 'modules/ModTracker/models/Record.php';
		$leadId = self::resolveLeadId($leadId);
		if (!$leadId) {
			return array();
		}

		$pagingModel = new Vtiger_Paging_Model();
		$pagingModel->set('page', 1);
		$pagingModel->set('limit', max(1, (int)$limit));
		$records = ModTracker_Record_Model::getUpdates($leadId, $pagingModel, self::MODULE);
		$out = array();
		foreach ($records as $rec) {
			$mapped = self::mapUpdate($rec);
			if ($mapped) {
				$out[] = $mapped;
			}
		}
		return $out;
	}

	protected static function mapComment(ModComments_Record_Model $rec) {
		$created = $rec->get('createdtime');
		return array(
			'id' => (int)$rec->getId(),
			'html' => $rec->getCommentContentForDisplay(),
			'text' => decode_html($rec->get('commentcontent')),
			'author' => decode_html($rec->getCommentedByName()),
			'time' => $created,
			'timeLabel' => self::formatTimeLabel($created),
		);
	}

	protected static function mapUpdate(ModTracker_Record_Model $rec) {
		$userModel = $rec->getModifiedBy();
		$userName = $userModel ? decode_html($userModel->getDisplayName()) : '';
		$time = $rec->getActivityTime();
		$item = array(
			'id' => (int)$rec->get('id'),
			'kind' => 'update',
			'user' => $userName,
			'time' => $time,
			'timeLabel' => self::formatTimeLabel($time),
			'title' => '',
			'changes' => array(),
			'relation' => null,
		);

		if ($rec->isCreate()) {
			$item['kind'] = 'create';
			$item['title'] = trim($userName . ' ' . vtranslate('LBL_CREATED', self::MODULE));
			return $item;
		}

		if ($rec->isDelete()) {
			$item['kind'] = 'delete';
			$item['title'] = trim($userName . ' ' . vtranslate('LBL_DELETED', 'Vtiger'));
			return $item;
		}

		if ($rec->isRestore()) {
			$item['kind'] = 'restore';
			$item['title'] = trim($userName . ' ' . vtranslate('LBL_RESTORED', 'Vtiger'));
			return $item;
		}

		if ($rec->isRelationLink() || $rec->isRelationUnLink()) {
			$relation = $rec->getRelationInstance();
			if (!$relation) {
				return null;
			}
			$linked = $relation->getLinkedRecord();
			if (!$linked) {
				return null;
			}
			$moduleName = $linked->getModuleName();
			$label = decode_html($linked->getName());
			$item['kind'] = $rec->isRelationLink() ? 'link' : 'unlink';
			$item['title'] = vtranslate($moduleName, $moduleName) . ' ' . decode_html($label);
			$item['relation'] = array(
				'module' => $moduleName,
				'label' => $label,
				'recordId' => (int)$linked->getId(),
				'url' => $linked->getDetailViewUrl(),
				'action' => $rec->isRelationLink() ? 'linked' : 'unlinked',
			);
			return $item;
		}

		if ($rec->isUpdate()) {
			$item['kind'] = 'update';
			$item['title'] = trim($userName . ' ' . vtranslate('LBL_UPDATED', self::MODULE));
			foreach ($rec->getFieldInstances() as $fieldModel) {
				if (!$fieldModel || !$fieldModel->getFieldInstance()) {
					continue;
				}
				$fieldInstance = $fieldModel->getFieldInstance();
				if (!$fieldInstance->isViewable() || $fieldInstance->getDisplayType() == '5') {
					continue;
				}
				$pre = decode_html($fieldModel->get('prevalue'));
				$post = decode_html($fieldModel->get('postvalue'));
				$fieldDataType = $fieldInstance->getFieldDataType();
				if ($fieldDataType === 'reference' && ($post === '0' || $pre === '0')) {
					continue;
				}
				$preDisplay = self::plainDisplay($fieldModel->getOldValue());
				$postDisplay = self::plainDisplay($fieldModel->getNewValue());
				$action = 'changed';
				if ($pre !== '' && $post !== '') {
					$action = 'changed';
				} elseif ($post === '' || ($fieldDataType === 'reference' && $post === '0')) {
					$action = 'removed';
				} elseif ($post !== '') {
					$action = 'added';
				}
				$item['changes'][] = array(
					'field' => vtranslate($fieldInstance->getName(), self::MODULE),
					'from' => $preDisplay,
					'to' => $postDisplay,
					'action' => $action,
				);
			}
			if (empty($item['changes'])) {
				return null;
			}
			return $item;
		}

		return null;
	}

	protected static function plainDisplay($value) {
		$text = trim(strip_tags(decode_html((string)$value)));
		return $text === '' ? '—' : $text;
	}

	protected static function formatTimeLabel($time) {
		if (!$time) {
			return '';
		}
		if (class_exists('Vtiger_Util_Helper')) {
			return Vtiger_Util_Helper::formatDateDiffInStrings($time);
		}
		return $time;
	}

	protected static function resolveLeadId($idOrCacheId) {
		if ($idOrCacheId === null || $idOrCacheId === '') {
			return null;
		}
		if (is_numeric($idOrCacheId)) {
			return (int)$idOrCacheId;
		}
		$adb = PearDatabase::getInstance();
		$res = $adb->pquery("SELECT leadid FROM bace_lead_profile WHERE mk_cache_id = ?", array($idOrCacheId));
		if ($res && $adb->num_rows($res) > 0) {
			return (int)$adb->query_result($res, 0, 'leadid');
		}
		return null;
	}
}
