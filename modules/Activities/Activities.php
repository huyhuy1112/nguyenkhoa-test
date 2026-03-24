<?php
/*+***********************************************************************************
 * Activities module entity definition (minimal CRMEntity wiring).
 ************************************************************************************/
class Activities extends CRMEntity {
	public $table_name = 'vtiger_activities';
	public $table_index = 'activityid';

	public $customFieldTable = ['vtiger_activitiescf', 'activityid'];

	public $tab_name = ['vtiger_crmentity', 'vtiger_activities', 'vtiger_activitiescf'];
	public $tab_name_index = [
		'vtiger_crmentity'   => 'crmid',
		'vtiger_activities'  => 'activityid',
		'vtiger_activitiescf'=> 'activityid',
	];

	// Field mapping for list/detail
	public $list_fields = [
		'Activity Name' => ['vtiger_activities', 'activity_name'],
		'Type'          => ['vtiger_activities', 'activity_type'],
		'Organization'  => ['vtiger_activities', 'organization_id'],
		'Ticket'        => ['vtiger_activities', 'ticket_id'],
		'Assigned To'   => ['vtiger_crmentity', 'smownerid'],
		'Date'          => ['vtiger_activities', 'activity_date'],
		'Status'        => ['vtiger_activities', 'status'],
	];
	public $list_fields_name = [
		'Activity Name' => 'activity_name',
		'Type'          => 'activity_type',
		'Organization'  => 'organization_id',
		'Ticket'        => 'ticket_id',
		'Assigned To'   => 'assigned_user_id',
		'Date'          => 'activity_date',
		'Status'        => 'status',
	];

	public $list_link_field = 'activity_name';

	public $search_fields = [
		'Activity Name' => ['vtiger_activities', 'activity_name'],
		'Type'          => ['vtiger_activities', 'activity_type'],
		'Status'        => ['vtiger_activities', 'status'],
	];
	public $search_fields_name = [
		'Activity Name' => 'activity_name',
		'Type'          => 'activity_type',
		'Status'        => 'status',
	];

	public $popup_fields = ['activity_name'];
	public $def_basicsearch_col = 'activity_name';
	public $def_detailview_recname = 'activity_name';
	public $mandatory_fields = ['activity_name', 'assigned_user_id'];
	public $default_order_by = 'activity_date';
	public $default_sort_order = 'DESC';
}
