<?php
/*+***********************************************************************************
 * Custom module: ProductsServices
 *************************************************************************************/

include_once 'modules/Vtiger/CRMEntity.php';

class ProductsServices extends Vtiger_CRMEntity {
	public $table_name = 'vtiger_productsservices';
	public $table_index = 'productsservicesid';

	public $customFieldTable = array('vtiger_productsservicescf', 'productsservicesid');

	public $tab_name = array('vtiger_crmentity', 'vtiger_productsservices', 'vtiger_productsservicescf', 'vtiger_crmentity_user_field');
	public $tab_name_index = array(
		'vtiger_crmentity' => 'crmid',
		'vtiger_productsservices' => 'productsservicesid',
		'vtiger_productsservicescf' => 'productsservicesid',
		'vtiger_crmentity_user_field' => 'recordid',
	);

	public $list_fields = array(
		'Name' => array('productsservices' => 'productsservicesname'),
		'Type' => array('productsservices' => 'item_type'),
		'Price' => array('productsservices' => 'price'),
		'Wholesale Price' => array('productsservices' => 'wholesale_price'),
		'Warranty' => array('productsservices' => 'warranty'),
	);
	public $list_fields_name = array(
		'Name' => 'productsservicesname',
		'Type' => 'item_type',
		'Price' => 'price',
		'Wholesale Price' => 'wholesale_price',
		'Warranty' => 'warranty',
	);

	public $list_link_field = 'productsservicesname';
	public $search_fields = array(
		'Name' => array('productsservices' => 'productsservicesname'),
	);
	public $search_fields_name = array(
		'Name' => 'productsservicesname',
	);

	public $popup_fields = array('productsservicesname');
	public $def_basicsearch_col = 'productsservicesname';
	public $def_detailview_recname = 'productsservicesname';

	public $mandatory_fields = array('productsservicesname', 'assigned_user_id');
	public $default_order_by = 'modifiedtime';
	public $default_sort_order = 'DESC';

	public function __construct() {
		global $log;
		$this->log = $log;
		$this->db = PearDatabase::getInstance();
	}

	/**
	 * Product photos: do not use core validateImage (false-positive on JPEG binary / HEIC / missing extension).
	 */
	public function uploadAndSaveFile($id, $module, $file_details, $attachmentType = 'Attachment') {
		if ($attachmentType !== 'Image') {
			return parent::uploadAndSaveFile($id, $module, $file_details, $attachmentType);
		}
		$file_details = $this->normalizeProductImageUpload($file_details);
		if (!$this->isAcceptableProductImage($file_details)) {
			return false;
		}

		global $adb, $current_user, $upload_badext;
		$date_var = date('Y-m-d H:i:s');
		$ownerid = isset($this->column_fields['assigned_user_id']) ? $this->column_fields['assigned_user_id'] : '';
		if ($ownerid === '' || $ownerid === null) {
			$ownerid = $current_user->id;
		}
		$file_name = !empty($file_details['original_name']) ? $file_details['original_name'] : $file_details['name'];
		$binFile = sanitizeUploadFileName($file_name, $upload_badext);
		$current_id = $adb->getUniqueID('vtiger_crmentity');
		$filename = ltrim(basename(' ' . $binFile));
		$filetype = isset($file_details['type']) ? $file_details['type'] : 'image/jpeg';
		$filetmp_name = $file_details['tmp_name'];
		$upload_file_path = decideFilePath();
		$encryptFileName = Vtiger_Util_Helper::getEncryptedFileName($binFile);
		$upload_status = copy($filetmp_name, $upload_file_path . $current_id . '_' . $encryptFileName);
		if (!$upload_status) {
			return false;
		}

		$desc = isset($this->column_fields['description']) ? $this->column_fields['description'] : '';
		$adb->pquery(
			'INSERT INTO vtiger_crmentity (crmid,smcreatorid,smownerid,setype,description,createdtime,modifiedtime) VALUES (?, ?, ?, ?, ?, ?, ?)',
			array(
				$current_id,
				$current_user->id,
				$ownerid,
				$module . ' Image',
				$desc,
				$adb->formatDate($date_var, true),
				$adb->formatDate($date_var, true),
			)
		);
		$adb->pquery(
			'INSERT INTO vtiger_attachments(attachmentsid, name, description, type, path, storedname) values(?, ?, ?, ?, ?, ?)',
			array($current_id, $filename, $desc, $filetype, $upload_file_path, $encryptFileName)
		);
		$adb->pquery('INSERT INTO vtiger_seattachmentsrel VALUES(?,?)', array($id, $current_id));
		return $current_id;
	}

	protected function normalizeProductImageUpload(array $file) {
		$name = isset($file['name']) ? (string) $file['name'] : '';
		$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
		$tmp = isset($file['tmp_name']) ? $file['tmp_name'] : '';
		if ($tmp && in_array($ext, array('heic', 'heif'), true) && class_exists('Imagick')) {
			try {
				$im = new Imagick($tmp);
				$im->setImageFormat('jpeg');
				$out = $tmp . '.ps.jpg';
				$im->writeImage($out);
				$im->clear();
				$im->destroy();
				if (is_file($out)) {
					$file['tmp_name'] = $out;
					$file['type'] = 'image/jpeg';
					$file['name'] = preg_replace('/\.(heic|heif)$/i', '.jpg', $name);
					$file['size'] = filesize($out);
					if (empty($file['original_name'])) {
						$file['original_name'] = $file['name'];
					} else {
						$file['original_name'] = preg_replace('/\.(heic|heif)$/i', '.jpg', $file['original_name']);
					}
				}
			} catch (Exception $e) {
				/* keep original; may still save if mime is image */
			}
		}
		$ext = strtolower(pathinfo(isset($file['name']) ? $file['name'] : '', PATHINFO_EXTENSION));
		if ($ext === '' && !empty($file['type'])) {
			$map = array(
				'image/jpeg' => 'jpg',
				'image/jpg' => 'jpg',
				'image/pjpeg' => 'jpg',
				'image/png' => 'png',
				'image/gif' => 'gif',
				'image/webp' => 'webp',
				'image/bmp' => 'bmp',
				'image/x-ms-bmp' => 'bmp',
			);
			$mime = strtolower((string) $file['type']);
			if (isset($map[$mime])) {
				$file['name'] = rtrim($name, '.') . '.' . $map[$mime];
			}
		}
		return $file;
	}

	protected function isAcceptableProductImage(array $file) {
		if (empty($file['tmp_name']) || empty($file['name'])) {
			return false;
		}
		if (isset($file['error']) && (int) $file['error'] !== 0) {
			return false;
		}
		if (empty($file['size']) || (int) $file['size'] <= 0) {
			return false;
		}
		$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
		$allowedExt = array('jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'heic', 'heif', 'ico');
		$mime = '';
		if (function_exists('mime_content_type')) {
			$mime = strtolower((string) @mime_content_type($file['tmp_name']));
		}
		if ($mime === '' && !empty($file['type'])) {
			$mime = strtolower((string) $file['type']);
		}
		$isImageMime = (strpos($mime, 'image/') === 0);
		if (!$isImageMime && !in_array($ext, $allowedExt, true)) {
			return false;
		}
		$head = @file_get_contents($file['tmp_name'], false, null, 0, 8192);
		if ($head !== false && (stripos($head, '<?php') !== false || stripos($head, '<?=') !== false)) {
			return false;
		}
		return true;
	}
}

