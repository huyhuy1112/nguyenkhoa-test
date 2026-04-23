<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Calendar_Year_View extends Calendar_Calendar_View {

	public function process(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$currentUserModel = Users_Record_Model::getCurrentUserModel();

		// Calendar shared layout expects these to exist (avoid null dereference in compiled templates)
		$viewer->assign('CURRENT_USER', $currentUserModel);
		$viewer->assign('CURRENT_USER_MODEL', $currentUserModel);
		// Ensure permission flags exist even if preProcess is bypassed by some route
		$viewer->assign('IS_CREATE_PERMITTED', isPermitted('Calendar', 'CreateView'));
		if (!$viewer->getTemplateVars('SHOW_MINI_CALENDAR_LEAVE')) {
			$viewer->assign('SHOW_MINI_CALENDAR_LEAVE', true);
		}
		if ($viewer->getTemplateVars('SHOW_LEAVE_REQUEST') === null) {
			$viewer->assign('SHOW_LEAVE_REQUEST', false);
		}

		$year = (int)$request->get('year');
		if ($year <= 0) {
			$year = (int)date('Y');
		}
		$monthGrids = array();
		for ($m = 1; $m <= 12; $m++) {
			$first = new DateTime(sprintf('%04d-%02d-01', $year, $m));
			$daysInMonth = (int)$first->format('t');
			// 1..7 (Mon..Sun)
			$firstDow = (int)$first->format('N');
			$cells = array();
			// leading blanks
			for ($i = 1; $i < $firstDow; $i++) {
				$cells[] = array('blank' => true);
			}
			// days
			for ($d = 1; $d <= $daysInMonth; $d++) {
				$cells[] = array(
					'blank' => false,
					'day' => $d,
					'date' => sprintf('%04d-%02d-%02d', $year, $m, $d),
				);
			}
			// trailing blanks to complete weeks
			while (count($cells) % 7 !== 0) {
				$cells[] = array('blank' => true);
			}
			// normalize to 6 weeks (42 cells)
			while (count($cells) < 42) {
				$cells[] = array('blank' => true);
			}
			$monthGrids[$m] = array(
				'month' => $m,
				'label' => sprintf('%02d/%04d', $m, $year),
				'cells' => $cells,
			);
		}
		$viewer->assign('YEAR', $year);
		$viewer->assign('MONTH_GRIDS', $monthGrids);
		$viewer->view('YearView.tpl', $request->getModule());
	}
}

