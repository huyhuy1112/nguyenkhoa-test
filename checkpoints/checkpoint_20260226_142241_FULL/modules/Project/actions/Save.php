<?php
/*+***********************************************************************************
 * Project Save: xử lý assign team group (assigned_user_id < 0) và lưu project_team_groups
 *************************************************************************************/

class Project_Save_Action extends Vtiger_Save_Action {

	public function process(Vtiger_Request $request) {
		// Giữ smownerid = -groupid khi assign team group để cột Assigned To hiển thị tên nhóm
		parent::process($request);
	}
}
