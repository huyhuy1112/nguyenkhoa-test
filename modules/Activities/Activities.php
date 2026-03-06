<?php
/*+***********************************************************************************
 * Activities module entity definition (minimal CRMEntity wiring).
 ************************************************************************************/
class Activities extends CRMEntity {
	public $table_name = 'vtiger_activities';
	public $table_index = 'activityid';

	// Không dùng bảng cf cho module tối giản này
	public $customFieldTable = [];

	public $tab_name = ['vtiger_crmentity', 'vtiger_activities'];
	public $tab_name_index = [
		'vtiger_crmentity'  => 'crmid',
		'vtiger_activities' => 'activityid',
	];

	public $list_fields = [
		'Type'        => ['vtiger_activities', 'activity_type'],
		'Content'     => ['vtiger_activities', 'content'],
		'Org'         => ['vtiger_activities', 'organizationid'],
		'Project'     => ['vtiger_activities', 'projectid'],
		'Assigned To' => ['vtiger_crmentity', 'smownerid'],
		'Date'        => ['vtiger_activities', 'activity_date'],
		'Status'      => ['vtiger_activities', 'status'],
	];
	public $list_fields_name = [
		'Type'        => 'activity_type',
		'Content'     => 'content',
		'Org'         => 'organizationid',
		'Project'     => 'projectid',
		'Assigned To' => 'assigned_user_id',
		'Date'        => 'activity_date',
		'Status'      => 'status',
	];

	public $list_link_field = 'content';

	public $search_fields = [
		'Type'    => ['vtiger_activities', 'activity_type'],
		'Status'  => ['vtiger_activities', 'status'],
		'Content' => ['vtiger_activities', 'content'],
	];
	public $search_fields_name = [
		'Type'    => 'activity_type',
		'Status'  => 'status',
		'Content' => 'content',
	];

	public $popup_fields = ['content'];
	public $def_basicsearch_col = 'content';
	public $def_detailview_recname = 'content';
	public $mandatory_fields = ['content', 'activity_type', 'status', 'assigned_user_id'];
	public $default_order_by = 'activity_date';
	public $default_sort_order = 'DESC';
}
