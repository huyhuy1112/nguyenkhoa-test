<?php
/*+***********************************************************************************
 * Custom module: ProductsServices
 *************************************************************************************/

include_once 'modules/Vtiger/CRMEntity.php';

class ProductsServices extends Vtiger_CRMEntity {
	public $table_name = 'vtiger_productsservices';
	public $table_index = 'productsservicesid';

	public $customFieldTable = array('vtiger_productsservicescf', 'productsservicesid');

	public $tab_name = array('vtiger_crmentity', 'vtiger_productsservices', 'vtiger_productsservicescf');
	public $tab_name_index = array(
		'vtiger_crmentity' => 'crmid',
		'vtiger_productsservices' => 'productsservicesid',
		'vtiger_productsservicescf' => 'productsservicesid',
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
}

