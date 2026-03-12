<?php
/*********************************************************************************
 * Custom SupportFAQ module for Vtiger CRM.
 * Simple FAQ-like entity under SUPPORT app.
 ********************************************************************************/

include_once 'modules/Vtiger/CRMEntity.php';

class SupportFAQ extends CRMEntity {
	// Main table
	public $table_name = 'vtiger_supportfaq';
	public $table_index = 'supportfaqid';

	// Custom fields table (kept for vtlib compatibility, not used now)
	public $customFieldTable = array('vtiger_supportfaqcf', 'supportfaqid');

	// All tables for this entity
	public $tab_name = array(
		'vtiger_crmentity',
		'vtiger_supportfaq',
		'vtiger_supportfaqcf',
	);

	public $tab_name_index = array(
		'vtiger_crmentity'   => 'crmid',
		'vtiger_supportfaq'  => 'supportfaqid',
		'vtiger_supportfaqcf'=> 'supportfaqid',
	);

	public $entity_table = 'vtiger_crmentity';

	public $column_fields = array();

	// List view columns
	public $list_fields = array(
		'Question'        => array('supportfaq' => 'question'),
		'Occurrence Count'=> array('supportfaq' => 'occurrence_count'),
		'Related Ticket'  => array('supportfaq' => 'related_ticket_id'),
		'Assigned To'     => array('crmentity'  => 'smownerid'),
		'Created Time'    => array('crmentity'  => 'createdtime'),
	);

	public $list_fields_name = array(
		'Question'         => 'question',
		'Occurrence Count' => 'occurrence_count',
		'Related Ticket'   => 'related_ticket_id',
		'Assigned To'      => 'assigned_user_id',
		'Created Time'     => 'createdtime',
	);

	public $list_link_field = 'question';

	// Search fields
	public $search_fields = array(
		'Question'       => array('supportfaq' => 'question'),
		'Related Ticket' => array('supportfaq' => 'related_ticket_id'),
	);

	public $search_fields_name = array(
		'Question'       => 'question',
		'Related Ticket' => 'related_ticket_id',
	);

	// Popup fields
	public $popup_fields = array('question');

	public $def_basicsearch_col = 'question';
	public $def_detailview_recname = 'question';

	// Mandatory fields
	public $mandatory_fields = array('question', 'assigned_user_id', 'createdtime', 'modifiedtime');

	public $default_order_by = 'createdtime';
	public $default_sort_order = 'DESC';

	public function __construct() {
		$this->log = Logger::getLogger('supportfaq');
		$this->db = PearDatabase::getInstance();
		$this->column_fields = getColumnFields('SupportFAQ');
	}
	function SupportFAQ() {
		self::__construct();
	}

	function save_module($module) {
		// No special behavior required on save for now.
	}
}

