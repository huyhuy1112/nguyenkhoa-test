<?php
/**
 * CREATE CACHE.PHP - Auto-create missing Cache.php file
 * 
 * File này sẽ tự động tạo file Cache.php nếu thiếu
 * 
 * SỬ DỤNG:
 *   1. Upload file này lên hosting
 *   2. Truy cập: https://supertestcrm.tdbsolution.com/MISSING_FILES_FIX/create_cache_file.php
 *   3. File sẽ tự động tạo Cache.php
 *   4. Test website
 *   5. XÓA file này sau khi fix xong
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>CREATE MISSING CACHE.PHP FILE</h1>";
echo "<hr>";

$root_dir = '/home/nhtdbus8/supertestcrm.tdbsolution.com';
$cache_file = $root_dir . '/includes/runtime/Cache.php';
$cache_dir = $root_dir . '/includes/runtime/cache';
$connector_file = $cache_dir . '/Connector.php';
$connectors_file = $cache_dir . '/Connectors.php';

// Check if Cache.php exists
if (file_exists($cache_file)) {
    echo "<p style='color:green'><strong>✅ Cache.php already exists</strong></p>";
    echo "<p>File: $cache_file</p>";
    exit;
}

echo "<p style='color:red'><strong>✗ Cache.php NOT FOUND</strong></p>";
echo "<p>Creating file...</p>";

// Create directory if not exists
if (!is_dir(dirname($cache_file))) {
    if (mkdir(dirname($cache_file), 0755, true)) {
        echo "<p style='color:green'><strong>✅ Created directory: " . dirname($cache_file) . "</strong></p>";
    } else {
        die("<p style='color:red'><strong>✗ Cannot create directory</strong></p>");
    }
}

// Create cache subdirectory if not exists
if (!is_dir($cache_dir)) {
    if (mkdir($cache_dir, 0755, true)) {
        echo "<p style='color:green'><strong>✅ Created directory: $cache_dir</strong></p>";
    } else {
        echo "<p style='color:orange'><strong>⚠️ Cannot create cache directory (may already exist)</strong></p>";
    }
}

// Cache.php content
$cache_content = '<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

include_once dirname(__FILE__).\'/cache/Connector.php\';

class Vtiger_Cache {
	private static $selfInstance = false;
	public static $cacheEnable = true;

	protected $connector;

	private function __construct() {
		$runtime_configs = Vtiger_Runtime_Configs::getInstance();
		$connector_class = $runtime_configs->getConnector(\'cache\', \'Vtiger_Cache_Connector\');
		$this->connector = $connector_class::getInstance();
	}

	public static function getInstance(){
		if(self::$selfInstance){
			return self::$selfInstance;
		} else{
			self::$selfInstance = new self();
			return self::$selfInstance;
		}
	}

	public static function get($ns, $key) {
		$self = self::getInstance();
		if($key && self::$cacheEnable) {
			return $self->connector->get($ns, $key);
		}
		return false;
	}

	public static function delete($ns, $key) {
		$self = self::getInstance();
		if ($key && self::$cacheEnable) {
			$self->connector->delete($ns, $key);
		}
	}

	public static function set($ns, $key, $value) {
		$self = self::getInstance();
		if(self::$cacheEnable) {
			$self->connector->set($ns, $key, $value);
		}
	}

	public static function flush(){
		$self = self::getInstance();
		$self->connector->flush();
	}


	private static $_user_list;

	public function getUserList($module,$currentUser){
		if(isset(self::$_user_list[$currentUser][$module])){
			return self::$_user_list[$currentUser][$module];
		}
		return false;
	}

	public function setUserList($module,$userList,$currentUser){
		if(self::$cacheEnable){
			self::$_user_list[$currentUser][$module]=$userList;
		}
	}

	private static $_group_list;

	public function getGroupList($module,$currentUser){
		if(isset(self::$_group_list[$currentUser][$module])){
			return self::$_group_list[$currentUser][$module];
		}
		return false;
	}

	public function setGroupList($module,$GroupList,$currentUser){
		if(self::$cacheEnable){
			self::$_group_list[$currentUser][$module]=$GroupList;
		}
	}

	public function getPicklistValues($fieldName){
		if(self::get(\'PicklistValues\',$fieldName)){
			return self::get(\'PicklistValues\',$fieldName);
		}
		return false;
	}

	public function setPicklistValues($fieldName,$values){
		if(isset($values) && isset($fieldName)){
			self::set(\'PicklistValues\',$fieldName,$values);
		}
	}

	public function getPicklistDetails($module,$field){
		if(self::get(\'PicklistDetails\',$field)){
			return self::get(\'PicklistDetails\',$field);
		}
		return false;
	}

	public function setPicklistDetails($module,$field,$picklistDetails){
		if(isset($picklistDetails) && isset($field)){
			self::set(\'PicklistDetails\',$field,$picklistDetails);
		}
	}

	private static $_module_ownedby;

	public function getModuleOwned($module){
		if(isset(self::$_module_ownedby[$module])){
			return self::$_module_ownedby[$module];
		}
		return false;
	}

	public function setModuleOwned($module,$ownedby){
		if(self::$cacheEnable){
			self::$_module_ownedby[$module] = $ownedby;
		}
	}

	public function getBlockInstance($block, $moduleName){
		if(Vtiger_Cache::get(\'BlockInstance\',$moduleName.\'-\'.$block)){
			return Vtiger_Cache::get(\'BlockInstance\',$moduleName.\'-\'.$block);
		}
		return false;
	}

	public function setBlockInstance($block, $moduleName, $instance){
		if(isset($instance) && isset($block)){
			Vtiger_Cache::set(\'BlockInstance\',$moduleName.\'-\'.$block,$instance);
		}
	}


	private static $_field_instance;

	public function getFieldInstance($field,$moduleId){
		if(isset(self::$_field_instance[$moduleId][$field])){
			return self::$_field_instance[$moduleId][$field];
		}
		return false;
	}

	public function setFieldInstance($field,$moduleId,$instance){
		if(self::$cacheEnable){
			self::$_field_instance[$moduleId][$field] = $instance;
		}
	}

	private static $_admin_user_id = false;

	public function getAdminUserId(){
			return self::$_admin_user_id;
	}

	public function setAdminUserId($userId){
		if(self::$cacheEnable){
			self::$_admin_user_id = $userId;
		}
	}

	//cache for the module Instance
	private static  $_module_name = array();

	public function getModuleName($moduleId){
	if(isset(self::$_module_name[$moduleId])){
		return self::$_module_name[$moduleId];
	}
	return false;
	}

	public function setModuleName($moduleId,$moduleName){
		if(self::$cacheEnable){
			self::$_module_name[$moduleId] = $moduleName;
		}
	}

	//cache for the module Instance
	private static  $_workflow_for_module = array();

	public function getWorkflowForModule($module){
		if(isset(self::$_workflow_for_module[$module])){
			return self::$_workflow_for_module[$module];
		}
		return false;
	}

	public function setWorkflowForModule($module,$workflows){
		if(self::$cacheEnable){
			self::$_workflow_for_module[$module] = $workflows;
		}
	}

	//cache for the module Instance
	private static  $_workflow_for_module_supporting_comments = array();

	public function getWorkflowForModuleSupportingComments($module){
		if(isset(self::$_workflow_for_module_supporting_comments[$module])){
			return self::$_workflow_for_module_supporting_comments[$module];
		}
		return false;
	}

	public function setWorkflowForModuleSupportingComments($module,$workflows){
		if(self::$cacheEnable){
			self::$_workflow_for_module_supporting_comments[$module] = $workflows;
		}
	}

	//cache for the workflow, supporting product update from inventory
	private static  $_workflows_of_inventory_supporting_product_qty_update = array();

	public function hasInventoryWorkflowsSupportingProductQtyUpdate($module) {
		if(isset(self::$_workflows_of_inventory_supporting_product_qty_update[$module])) {
			return true;
		}
		return false;
	}

	public function getInventoryWorkflowsSupportingProductQtyUpdate($module) {
		return self::$_workflows_of_inventory_supporting_product_qty_update[$module];
	}

	public function setInventoryWorkflowsSupportingProductQtyUpdate($module, $workflows) {
		if(self::$cacheEnable) {
			self::$_workflows_of_inventory_supporting_product_qty_update[$module] = $workflows;
		}
	}

	private static $_user_id ;

	public function getUserId($userName){
		if(isset(self::$_user_id[$userName])){
			return self::$_user_id[$userName];
		}
		return false;
	}

	public function setUserId($userName,$userId){
		if(self::$cacheEnable){
			self::$_user_id[$userName] = $userId;
		}
	}

	private static $_table_exists ;

	public function getTableExists($tableName){
		if(isset(self::$_table_exists[$tableName])){
			return self::$_table_exists[$tableName];
		}
		return false;
	}

	public function setTableExists($tableName,$exists){
		if(self::$cacheEnable){
			self::$_table_exists[$tableName] = $exists;
		}
	}

	private static $_picklist_id;

	public function getPicklistId($fieldName,$moduleName){
		if(isset(self::$_picklist_id[$moduleName][$fieldName])){
			return self::$_picklist_id[$moduleName][$fieldName];
		}
		return false;
	}
	public function setPicklistId($fieldName,$moduleName,$picklistId){
		if(self::$cacheEnable){
			self::$_picklist_id[$moduleName][$fieldName] = $picklistId;
		}
	}

	private static $_group_id;

	public function getGroupId($groupName){
		if(isset(self::$_group_id[$groupName])){
			return self::$_group_id[$groupName];
		}
		return false;
	}

	public function setGroupId($groupName,$groupId){
		if(self::$cacheEnable){
			self::$_group_id[$groupName]=$groupId;
		}
	}

	public function getAssignedPicklistValues($tableName,$roleId){
		if(self::get(\'PicklistRoleBasedValues\',$tableName.$roleId)){
			return self::get(\'PicklistRoleBasedValues\',$tableName.$roleId);
		}
		return false;
	}

	public function setAssignedPicklistValues($tableName,$roleId,$values){
		if(isset($values)){
			self::set(\'PicklistRoleBasedValues\',$tableName.$roleId,$values);
		}
	}

	public function hasAssignedPicklistValues($tableName, $roleId) {
		$values = $this->getAssignedPicklistValues($tableName, $roleId);
		return $values !== false;
	}

	public function getBlockFields($moduleName,$blockId){
		if(Vtiger_Cache::get(\'BlockFields\',$moduleName.\'_\'.$blockId)){
			return Vtiger_Cache::get(\'BlockFields\',$moduleName.\'_\'.$blockId);
		}
		return false;
	}

	public function setBlockFields($moduleName,$blockId,$fields){
		Vtiger_Cache::set(\'BlockFields\',$moduleName.\'_\'.$blockId,$fields);
	}

	private static $_name_fields;	

	public function getNameFields($module){
		if(isset(self::$_name_fields[$module])){
			return self::$_name_fields[$module];
		}
		return false;
	}

	public function setNameFields($module,$nameFields){
		if(self::$cacheEnable){
			self::$_name_fields[$module] = $nameFields; 
		}
	}

	public function purifyGet($key) {
		if (self::$cacheEnable) {
			return $this->connector->get(\'purify\', $key);
		}
		return false;
	}

	public function purifySet($key, $value) {
		if (self::$cacheEnable) {
			$this->connector->set(\'purify\', $key, $value);
		}
	}

	private static $_owners_names_list;

	public function getOwnerName($id){
		if(isset(self::$_owners_names_list[$id])) {
			return self::$_owners_names_list[$id];
		}
		return false;
	}

	public function setOwnerName($id, $value){
		if(self::$cacheEnable){
			self::$_owners_names_list[$id] = $value;
		}
	}

	public function hasOwnerName($id) {
		$value = $this->getOwnerName($id);
		return $value !== false;
	}

	private static $_owners_db_names_list;

	public function getOwnerDbName($id){
		if(isset(self::$_owners_db_names_list[$id])) {
			return self::$_owners_db_names_list[$id];
		}
		return false;
	}

	public function setOwnerDbName($id, $value){
		if(self::$cacheEnable){
			self::$_owners_db_names_list[$id] = $value;
		}
	}

	public function hasOwnerDbName($id) {
		$value = $this->getOwnerDbName($id);
		return $value !== false;
	}

	private static $_creator_ids_list;

	public function getCreator($id){
		if(isset(self::$_creator_ids_list[$id])) {
			return self::$_creator_ids_list[$id];
		}
		return false;
	}

	public function setCreator($id, $value){
		if(self::$cacheEnable){
			self::$_creator_ids_list[$id] = $value;
		}
	}

	public function hasCreator($id) {
		$value = $this->getCreator($id);
		return $value !== false;
	}	

	/**
	 * To clear module information from cache
	 * @param type $moduleName
	 */
	public static function flushModuleCache($moduleName) {
		$module = Vtiger_Module_Model::getInstance($moduleName);
		if (empty($module))
			return;

		Vtiger_Cache::delete(\'module\', $moduleName);
		Vtiger_Cache::delete(\'module\', $module->id);

		$moduleBlocks = $module->getBlocks();
		foreach ($moduleBlocks as $label => $block) {
			Vtiger_Cache::delete(\'BlockInstance\', $module->id.\'-\'.$label);
			Vtiger_Cache::delete(\'BlockInstance\', $module->id.\'-\'.$block->id);
		}

		Vtiger_Cache::delete(\'ModuleFieldInfo\', $moduleName);
		Vtiger_Cache::delete(\'ModuleFieldInfo\', $module->id);

		Vtiger_Cache::delete(\'ModuleFields\', $module->id);
		Vtiger_Cache::delete(\'ModuleFields\', $moduleName);

		Vtiger_Cache::delete(\'ModuleBlocks\', $moduleName);
		Vtiger_Cache::delete(\'ModuleBlocks\', $module->id);
	}

	/**
	 * Function to clear Picklist values from cache
	 * @param type $pickListName
	 * @param type $rolesList
	 */
	public static function flushPicklistCache($pickListName, $rolesList = false) {
		Vtiger_Cache::delete(\'PicklistValues\', $pickListName);
		Vtiger_Cache::delete(\'EditablePicklistValues\', $pickListName);
		Vtiger_Cache::delete(\'NonEditablePicklistValues\', $pickListName);
		Vtiger_Cache::delete(\'AllPicklistValues\', $pickListName);
		Vtiger_Cache::delete(\'PicklistDetails\', $pickListName);

		if ($rolesList) {
			foreach ($rolesList as $key => $roleId) {
				Vtiger_Cache::delete(\'PicklistRoleBasedValues\', $pickListName.$roleId);
			}
		}
	}

	/**
	 * Function to clear Module and Block Field data from cache
	 * @param type $module
	 * @param type $blockId
	 */
	public static function flushModuleandBlockFieldsCache($module, $blockId = false) {
		Vtiger_Cache::delete(\'ModuleFieldInfo\', $module->name);
		Vtiger_Cache::delete(\'ModuleFields\', $module->id);

		if ($blockId) {
			Vtiger_Cache::delete(\'BlockFields\', $module->name.\'_\'.$blockId);
		} else {
			$blocks = $module->getBlocks();
			foreach ($blocks as $label => $block) {
				Vtiger_Cache::delete(\'BlockFields\', $module->name.\'_\'.$block->id);
			}
		}
	}

	/**
	 * Function to clear module Block information from cache
	 * @param type $moduleInstance
	 * @param type $block
	 */
	static function flushModuleBlocksCache($moduleInstance, $block = null) {
		if ($block == null) {
			$moduleModel = Vtiger_Module_Model::getInstance($moduleInstance->name);
			$moduleBlocks = $moduleModel->getBlocks();
			Vtiger_Cache::delete(\'ModuleBlocks\', $moduleInstance->id);
			foreach ($moduleBlocks as $label => $block) {
				Vtiger_Cache::delete(\'BlockInstance\', $moduleInstance->id.\'-\'.$label);
				Vtiger_Cache::delete(\'BlockInstance\', $moduleInstance->id.\'-\'.$block->id);
				Vtiger_Cache::delete(\'BlockFields\', $moduleInstance->name.\'_\'.$block->label);
			}
		} else {
			Vtiger_Cache::delete(\'ModuleBlocks\', $moduleInstance->id);
			Vtiger_Cache::delete(\'BlockInstance\', $moduleInstance->id.\'-\'.$block->label);
			Vtiger_Cache::delete(\'BlockInstance\', $moduleInstance->id.\'-\'.$block->id);
			Vtiger_Cache::delete(\'BlockFields\', $moduleInstance->name.\'_\'.$block->id);
		}
	}

}
';

// Write Cache.php
if (file_put_contents($cache_file, $cache_content)) {
    chmod($cache_file, 0644);
    echo "<p style='color:green'><strong>✅ Cache.php created successfully!</strong></p>";
    echo "<p>File: $cache_file</p>";
} else {
    die("<p style='color:red'><strong>✗ Cannot create Cache.php</strong></p>");
}

// Check if Connector.php exists
if (!file_exists($connector_file)) {
    echo "<p style='color:orange'><strong>⚠️ Connector.php NOT FOUND: $connector_file</strong></p>";
    echo "<p>You may need to create this file as well. Check CACHE_CONNECTOR_FIX/ directory.</p>";
} else {
    echo "<p style='color:green'><strong>✅ Connector.php exists</strong></p>";
}

echo "<hr>";
echo "<h2>✅ FILE CREATED</h2>";
echo "<p><strong>Next steps:</strong></p>";
echo "<ol>";
echo "<li>Test website: <a href='../index.php' target='_blank'>index.php</a></li>";
echo "<li>Or test: <a href='../index_test.php' target='_blank'>index_test.php</a></li>";
echo "<li>If it works, delete this file</li>";
echo "</ol>";
echo "<p><strong>Lưu ý:</strong> Xóa file này sau khi fix xong!</p>";
?>

