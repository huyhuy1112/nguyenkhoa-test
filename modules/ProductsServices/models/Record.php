<?php
/*+***********************************************************************************
 * Custom record model: ProductsServices
 *
 * Purpose: Make Inventory line-item popup selection work with the unified
 * "Products & Services" selector while keeping existing inventory calculations.
 *************************************************************************************/

class ProductsServices_Record_Model extends Products_Record_Model {

	/**
	 * Lazily loaded underlying Products or Services record (same crmid when unified row mirrors inventory).
	 * null = not resolved yet, false = resolution failed / skip further attempts.
	 *
	 * @var Vtiger_Record_Model|false|null
	 */
	protected $underlyingInventoryRecord = null;

	/**
	 * Map ProductsServices.item_type => underlying inventory module.
	 *
	 * Uses raw column data only — never $this->get('item_type'), which would re-enter get() and
	 * previously combined with getInstanceById-on-every-get caused pathological load / memory use.
	 *
	 * Note: Inventory calculations rely on core Products/Services tables.
	 */
	protected function getUnderlyingInventoryModuleName() {
		$data = $this->getData();
		if (!is_array($data)) {
			$data = array();
		}
		$itemType = isset($data['item_type']) ? $data['item_type'] : '';
		if (empty($itemType)) {
			return 'Products';
		}
		$itemTypeLower = strtolower(trim($itemType));
		if ($itemTypeLower === 'product' || $itemTypeLower === 'products') {
			return 'Products';
		}
		if ($itemTypeLower === 'service' || $itemTypeLower === 'services') {
			return 'Services';
		}
		return 'Products';
	}

	/**
	 * Single cached load of the underlying inventory record for delegation.
	 *
	 * @return Products_Record_Model|Services_Record_Model|null
	 */
	protected function getUnderlyingInventoryRecord() {
		if ($this->underlyingInventoryRecord !== null) {
			return $this->underlyingInventoryRecord === false ? null : $this->underlyingInventoryRecord;
		}
		$id = $this->getId();
		if (empty($id)) {
			$this->underlyingInventoryRecord = false;
			return null;
		}
		$moduleName = $this->getUnderlyingInventoryModuleName();
		$record = null;
		try {
			$record = Vtiger_Record_Model::getInstanceById($id, $moduleName);
		} catch (Exception $e) {
			$record = null;
		}
		$this->underlyingInventoryRecord = $record ? $record : false;
		return $record;
	}

	/**
	 * Override module name used by InventoryUtils (prices/taxes/base currency).
	 */
	public function getModuleName() {
		return $this->getUnderlyingInventoryModuleName();
	}

	/**
	 * Detail navigation must stay on ProductsServices module.
	 *
	 * Inventory/tax logic needs getModuleName() to resolve to Products/Services, but
	 * UI navigation for ProductsServices records must not route to Products/Services,
	 * otherwise users can hit Permission denied on the wrong module.
	 */
	public function getDetailViewUrl() {
		return 'index.php?module=ProductsServices&view=Detail&record=' . $this->getId();
	}

	public function getEditViewUrl() {
		return 'index.php?module=ProductsServices&view=Edit&record=' . $this->getId();
	}

	/**
	 * Images for ProductsServices use setype "ProductsServices Image"
	 * (uitype 69 on used_projects). Parent Products model only looks up "Products Image".
	 */
	/**
	 * List/detail thumb URL.
	 * Prefer public.php (no CSRF / WebUI) with sanitized key; fall back to DownloadImage.
	 */
	public static function listImageUrl($recordId, $attachmentId = 0, $fileName = '') {
		$recordId = (int) $recordId;
		$attachmentId = (int) $attachmentId;
		$fileName = decode_html((string) $fileName);
		if ($attachmentId > 0 && $fileName !== '') {
			$key = '';
			if (class_exists('Vtiger_Functions') && method_exists('Vtiger_Functions', 'getAttachmentPublicKey')) {
				$key = (string) Vtiger_Functions::getAttachmentPublicKey($fileName);
			}
			if ($key === '') {
				$key = md5($fileName);
			}
			return 'public.php?fid=' . $attachmentId . '&key=' . $key;
		}
		if ($recordId <= 0) {
			return '';
		}
		$url = 'index.php?module=ProductsServices&action=DownloadImage&record=' . $recordId;
		if ($attachmentId > 0) {
			$url .= '&aid=' . $attachmentId;
		}
		return $url;
	}

	/**
	 * Resolve on-disk image for a ProductsServices record.
	 * @return array{path:string,type:string,name:string,id:int}|null
	 */
	public static function resolveImageFile($recordId, $attachmentId = 0) {
		$recordId = (int) $recordId;
		$attachmentId = (int) $attachmentId;
		if ($recordId <= 0) {
			return null;
		}
		$db = PearDatabase::getInstance();
		$params = array($recordId);
		$aidSql = '';
		if ($attachmentId > 0) {
			$aidSql = ' AND a.attachmentsid = ? ';
			$params[] = $attachmentId;
		}
		$rs = $db->pquery(
			"SELECT a.attachmentsid, a.name, a.type, a.path, a.storedname
			 FROM vtiger_seattachmentsrel sar
			 INNER JOIN vtiger_attachments a ON a.attachmentsid = sar.attachmentsid
			 INNER JOIN vtiger_crmentity ce ON ce.crmid = a.attachmentsid AND ce.deleted = 0
			 WHERE sar.crmid = ? {$aidSql}
			 ORDER BY CASE WHEN ce.setype LIKE '% Image' THEN 0
			               WHEN IFNULL(a.type,'') LIKE 'image/%' THEN 1
			               ELSE 2 END,
			          a.attachmentsid DESC
			 LIMIT 1",
			$params
		);
		if (!$rs || !$db->num_rows($rs)) {
			return null;
		}
		$row = $db->fetchByAssoc($rs);
		$fileId = (int) $row['attachmentsid'];
		$path = (string) $row['path'];
		$stored = (string) (isset($row['storedname']) ? $row['storedname'] : '');
		$nameRaw = (string) $row['name'];
		$name = decode_html($nameRaw);
		$uploadBadext = vglobal('upload_badext');
		$sanitized = function_exists('sanitizeUploadFileName')
			? sanitizeUploadFileName($name, $uploadBadext)
			: $name;

		$candidates = array();
		if ($stored !== '') {
			$candidates[] = $path . $fileId . '_' . $stored;
		}
		if ($sanitized !== '') {
			$candidates[] = $path . $fileId . '_' . $sanitized;
		}
		if ($name !== '') {
			$candidates[] = $path . $fileId . '_' . $name;
		}
		if ($nameRaw !== '' && $nameRaw !== $name) {
			$candidates[] = $path . $fileId . '_' . $nameRaw;
		}
		// Older uploads sometimes stored md5(name) as the on-disk suffix.
		$candidates[] = $path . $fileId . '_' . md5($name);
		if ($sanitized !== '') {
			$candidates[] = $path . $fileId . '_' . md5($sanitized);
		}

		$root = rtrim((string) vglobal('root_directory'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		foreach ($candidates as $candidate) {
			if ($candidate && is_readable($candidate) && @filesize($candidate) > 0) {
				return array(
					'id' => $fileId,
					'path' => $candidate,
					'type' => (string) $row['type'],
					'name' => $name !== '' ? $name : $nameRaw,
				);
			}
			$abs = $root . ltrim(str_replace('\\', '/', $candidate), '/');
			if ($abs && is_readable($abs) && @filesize($abs) > 0) {
				return array(
					'id' => $fileId,
					'path' => $abs,
					'type' => (string) $row['type'],
					'name' => $name !== '' ? $name : $nameRaw,
				);
			}
		}

		// Last resort: any file matching {attachmentsid}_* in the attachment folder.
		$dirs = array();
		$relDir = rtrim(str_replace('\\', '/', $path), '/');
		if ($relDir !== '') {
			$dirs[] = $relDir;
			$dirs[] = $root . ltrim($relDir, '/');
		}
		foreach ($dirs as $dir) {
			if (!$dir || !is_dir($dir)) {
				continue;
			}
			$hits = glob($dir . DIRECTORY_SEPARATOR . $fileId . '_*');
			if (!is_array($hits)) {
				continue;
			}
			foreach ($hits as $hit) {
				if ($hit && is_readable($hit) && @filesize($hit) > 0) {
					return array(
						'id' => $fileId,
						'path' => $hit,
						'type' => (string) $row['type'],
						'name' => $name !== '' ? $name : $nameRaw,
					);
				}
			}
		}
		return null;
	}

	public function getImageDetails() {
		$db = PearDatabase::getInstance();
		$imageDetails = array();
		$recordId = $this->getId();
		if (!$recordId) {
			return $imageDetails;
		}

		$sql = "SELECT vtiger_attachments.*, vtiger_crmentity.setype FROM vtiger_attachments
					INNER JOIN vtiger_seattachmentsrel ON vtiger_seattachmentsrel.attachmentsid = vtiger_attachments.attachmentsid
					INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_attachments.attachmentsid
					WHERE vtiger_seattachmentsrel.crmid = ?
					  AND vtiger_crmentity.deleted = 0
					ORDER BY vtiger_attachments.attachmentsid DESC";
		$result = $db->pquery($sql, array($recordId));
		$count = $db->num_rows($result);
		$imageIdsList = array();
		$imagePathList = array();
		$imageNamesList = array();
		$imageOriginalNamesList = array();
		$imageUrlsList = array();

		for ($i = 0; $i < $count; $i++) {
			$imageId = $db->query_result($result, $i, 'attachmentsid');
			$imageIdsList[] = $imageId;
			$imagePathList[] = $db->query_result($result, $i, 'path');
			$imageName = $db->query_result($result, $i, 'name');
			$url = self::listImageUrl($recordId, $imageId, $imageName);
			$imageOriginalNamesList[] = urlencode(decode_html($imageName));
			$imageNamesList[] = $imageName;
			$imageUrlsList[] = $url;
		}
		$countOfImages = php7_count($imageOriginalNamesList);
		for ($j = 0; $j < $countOfImages; $j++) {
			$imageDetails[] = array(
				'id' => $imageIdsList[$j],
				'orgname' => $imageOriginalNamesList[$j],
				'path' => $imagePathList[$j] . $imageIdsList[$j],
				'name' => $imageNamesList[$j],
				'url' => $imageUrlsList[$j],
			);
		}
		return $imageDetails;
	}

	/**
	 * Provide Inventory_GetTaxes_Action compatible fields by delegating to
	 * the underlying Products/Services record.
	 *
	 * Important: only touch keys that need delegation. The previous implementation called
	 * getInstanceById on every get() (label, priceDetails, name fields, etc.), which could
	 * exhaust memory and stack under GetTaxes / getName / getPriceDetails.
	 */
	public function get($key) {
		if ($key === 'starred') {
			return $this->readStarredFromUserTable();
		}
		if ($key === 'item_type') {
			return parent::get($key);
		}

		$delegatedKeys = array('unit_price', 'purchase_cost', 'qtyinstock', 'description');
		if (!in_array($key, $delegatedKeys, true)) {
			return parent::get($key);
		}

		$underlyingRecord = $this->getUnderlyingInventoryRecord();
		if ($underlyingRecord) {
			$value = $underlyingRecord->get($key);
			if (!empty($value) || $value === '0' || $value === 0) {
				return $value;
			}
		}

		$data = $this->getData();
		if (!is_array($data)) {
			$data = array();
		}

		if ($key === 'unit_price') {
			foreach (array('price', 'retail_price', 'wholesale_price') as $field) {
				if (isset($data[$field]) && $data[$field] !== '' && $data[$field] !== null) {
					return $data[$field];
				}
			}
			return parent::get($key);
		}
		if ($key === 'purchase_cost') {
			$pc = parent::get('purchase_cost');
			if ($pc !== null && $pc !== '') {
				return $pc;
			}
			if (isset($data['wholesale_price']) && $data['wholesale_price'] !== '' && $data['wholesale_price'] !== null) {
				return $data['wholesale_price'];
			}
			return parent::get($key);
		}
		if ($key === 'qtyinstock') {
			$q = parent::get('qtyinstock');
			if ($q !== null && $q !== '') {
				return $q;
			}
			if (isset($data['stock']) && $data['stock'] !== '' && $data['stock'] !== null) {
				return $data['stock'];
			}
			return parent::get($key);
		}
		if ($key === 'description') {
			return parent::get($key);
		}

		return parent::get($key);
	}

	/**
	 * Follow flag lives in vtiger_crmentity_user_field (per user), not the product row.
	 */
	protected function readStarredFromUserTable() {
		$recordId = (int) $this->getId();
		if ($recordId <= 0) {
			return 0;
		}
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$userId = $currentUser ? (int) $currentUser->getId() : 0;
		if ($userId <= 0) {
			return 0;
		}
		$db = PearDatabase::getInstance();
		$rs = $db->pquery(
			'SELECT starred FROM vtiger_crmentity_user_field WHERE recordid = ? AND userid = ?',
			array($recordId, $userId)
		);
		if (!$rs || !$db->num_rows($rs)) {
			return 0;
		}
		return ((int) $db->query_result($rs, 0, 'starred')) ? 1 : 0;
	}
}
