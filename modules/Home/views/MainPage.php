<?php
/*+***********************************************************************************
 * Main Page (Management) - custom landing view.
 * Allow all logged-in users; load real Project/ProjectTask data and link to modules.
 *************************************************************************************/

class Home_MainPage_View extends Vtiger_Index_View {

	public function requiresPermission(\Vtiger_Request $request) {
		return array();
	}

	public function checkPermission(Vtiger_Request $request) {
		return;
	}

	public function preProcess(Vtiger_Request $request, $display = true) {
		parent::preProcess($request, false);
		$viewer = $this->getViewer($request);
		$viewer->assign('VIEW', 'MainPage');
		// Main Page: luôn dùng MANAGEMENT và hiển thị app menu (sidebar)
		$viewer->assign('SELECTED_MENU_CATEGORY', 'MANAGEMENT');
		$viewer->assign('SELECTED_MENU_CATEGORY_LABEL', vtranslate('LBL_MANAGEMENT', 'Vtiger'));
		$menuGroupedByParent = Settings_MenuEditor_Module_Model::getAllVisibleModules();
		if (isset($menuGroupedByParent['MANAGEMENT'])) {
			$viewer->assign('SELECTED_CATEGORY_MENU_LIST', $menuGroupedByParent['MANAGEMENT']);
		}
		if ($display) {
			$this->preProcessDisplay($request);
		}
	}

	protected function preProcessTplName(Vtiger_Request $request) {
		return 'MainPageViewPreProcess.tpl';
	}

	public function postProcess(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$viewer->view('MainPagePostProcess.tpl', $request->getModule());
		Vtiger_Basic_View::postProcess($request);
	}

	/**
	 * Fallback: lấy Project/ProjectTask trực tiếp từ DB theo smownerid (khi list view trả về 0).
	 */
	protected function getMainPageListFromDb($moduleName, $userId, $limit = 8) {
		$rows = array();
		$db = PearDatabase::getInstance();
		$userId = (int) $userId;
		try {
			if ($moduleName === 'Project') {
				$sql = "SELECT p.projectid AS id, p.projectname, p.startdate, p.enddate, p.projectstatus
					FROM vtiger_project p
					INNER JOIN vtiger_crmentity c ON c.crmid = p.projectid AND c.deleted = 0 AND c.smownerid = ?
					ORDER BY c.modifiedtime DESC LIMIT ?";
				$res = $db->pquery($sql, array($userId, $limit));
				while ($row = $db->fetchByAssoc($res)) {
					$id = $row['id'];
					$rec = Vtiger_Record_Model::getInstanceById($id, $moduleName);
					$rows[] = array(
						'id' => $id,
						'url' => $rec ? $rec->getDetailViewUrl() : ('index.php?module=Project&view=Detail&record=' . $id),
						'title' => $row['projectname'] ?: ('#' . $id),
						'startdate' => $rec ? $rec->getDisplayValue('startdate') : $row['startdate'],
						'enddate' => $rec ? $rec->getDisplayValue('enddate') : $row['enddate'],
						'status' => $rec ? $rec->getDisplayValue('projectstatus') : $row['projectstatus'],
						'status_raw' => $row['projectstatus'],
					);
				}
			} elseif ($moduleName === 'ProjectTask') {
				$sql = "SELECT t.projecttaskid AS id, t.projecttaskname, t.startdate, t.enddate, t.projecttaskprogress
					FROM vtiger_projecttask t
					INNER JOIN vtiger_crmentity c ON c.crmid = t.projecttaskid AND c.deleted = 0 AND c.smownerid = ?
					ORDER BY c.modifiedtime DESC LIMIT ?";
				$res = $db->pquery($sql, array($userId, $limit));
				while ($row = $db->fetchByAssoc($res)) {
					$id = $row['id'];
					$rec = Vtiger_Record_Model::getInstanceById($id, $moduleName);
					$rows[] = array(
						'id' => $id,
						'url' => $rec ? $rec->getDetailViewUrl() : ('index.php?module=ProjectTask&view=Detail&record=' . $id),
						'title' => $row['projecttaskname'] ?: ('Task #' . $id),
						'duedate' => $rec ? ($rec->getDisplayValue('enddate') ?: $rec->getDisplayValue('startdate')) : $row['enddate'],
						'status' => $rec ? $rec->getDisplayValue('projecttaskprogress') : $row['projecttaskprogress'],
						'status_raw' => $row['projecttaskprogress'],
					);
				}
			}
		} catch (Exception $e) {
			// ignore
		}
		return $rows;
	}

	/**
	 * Fetch recent records from a module assigned to current user (max 8).
	 * For Project and ProjectTask only records assigned to me are shown.
	 * @param string $moduleName
	 * @return array list of assoc: id, url, display fields
	 */
	protected function getMainPageList($moduleName, $limit = 8) {
		$rows = array();
		$currentUser = Users_Record_Model::getCurrentUserModel();
		if (!Users_Privileges_Model::isPermitted($moduleName, 'DetailView')) {
			return $rows;
		}
		try {
			$listViewModel = Vtiger_ListView_Model::getInstance($moduleName, '0', array());
			// Project/ProjectTask: thêm assigned_user_id vào query để lọc "assign cho tôi" trong PHP
			if ($moduleName === 'Project' || $moduleName === 'ProjectTask') {
				$qg = $listViewModel->get('query_generator');
				$fields = $qg->getFields();
				if (!in_array('assigned_user_id', $fields)) {
					$fields[] = 'assigned_user_id';
					$qg->setFields($fields);
				}
			}
			$pagingModel = new Vtiger_Paging_Model();
			$pagingModel->set('page', 1);
			$fetchLimit = ($moduleName === 'Project' || $moduleName === 'ProjectTask') ? 80 : $limit;
			$pagingModel->set('limit', $fetchLimit);
			$entries = $listViewModel->getListViewEntries($pagingModel);
			if (!is_array($entries)) {
				return $rows;
			}
			// Fallback: list view trả về 0 (do custom view/permission) thì lấy trực tiếp từ DB theo smownerid
			if (($moduleName === 'Project' || $moduleName === 'ProjectTask') && count($entries) === 0) {
				return $this->getMainPageListFromDb($moduleName, $currentUser->getId(), $limit);
			}
			$currentUserId = $currentUser->getId();
			foreach ($entries as $recordId => $recordModel) {
				if (!$recordModel || !($recordModel instanceof Vtiger_Record_Model)) {
					continue;
				}
				// Chỉ hiển thị bản ghi assign cho user hiện tại (owner id từ raw data; list view gán assigned_user_id = tên hiển thị)
				if ($moduleName === 'Project' || $moduleName === 'ProjectTask') {
					$rawData = $recordModel->getRawData();
					$ownerId = null;
					if (is_array($rawData)) {
						if (isset($rawData['smownerid'])) {
							$ownerId = $rawData['smownerid'];
						} elseif (isset($rawData['assigned_user_id'])) {
							$ownerId = $rawData['assigned_user_id'];
						} else {
							foreach (array('smownerid', 'assigned_user_id', 'vtiger_crmentity_smownerid') as $k) {
								if (isset($rawData[$k])) { $ownerId = $rawData[$k]; break; }
							}
							if ($ownerId === null) {
								foreach ($rawData as $k => $v) {
									if (stripos($k, 'owner') !== false && is_numeric($v)) { $ownerId = $v; break; }
								}
							}
						}
					}
					if ($ownerId !== null && $ownerId !== $currentUserId && $ownerId !== (string) $currentUserId && (string) $ownerId !== (string) $currentUserId) {
						continue;
					}
				}
				if (count($rows) >= $limit) {
					break;
				}
				$url = $recordModel->getDetailViewUrl();
				if ($moduleName === 'Project') {
					$rows[] = array(
						'id' => $recordId,
						'url' => $url,
						'title' => $recordModel->get('projectname') ?: ('#' . $recordId),
						'startdate' => $recordModel->getDisplayValue('startdate'),
						'enddate' => $recordModel->getDisplayValue('enddate'),
						'status' => $recordModel->getDisplayValue('projectstatus'),
						'status_raw' => $recordModel->get('projectstatus'),
					);
				} elseif ($moduleName === 'ProjectTask') {
					$rows[] = array(
						'id' => $recordId,
						'url' => $url,
						'title' => $recordModel->get('projecttaskname') ?: ('Task #' . $recordId),
						'duedate' => $recordModel->getDisplayValue('enddate') ?: $recordModel->getDisplayValue('startdate'),
						'status' => $recordModel->getDisplayValue('projecttaskprogress') ?: '-',
						'status_raw' => $recordModel->get('projecttaskprogress'),
					);
				}
			}
		} catch (Exception $e) {
			// ignore
		}
		return $rows;
	}

	/**
	 * Count ProjectTask records assigned to current user (for badge).
	 */
	protected function getProjectTaskCount() {
		$currentUser = Users_Record_Model::getCurrentUserModel();
		if (!Users_Privileges_Model::isPermitted('ProjectTask', 'DetailView')) {
			return 0;
		}
		try {
			$listViewModel = Vtiger_ListView_Model::getInstance('ProjectTask', '0', array());
			$qg = $listViewModel->get('query_generator');
			$fields = $qg->getFields();
			if (!in_array('assigned_user_id', $fields)) {
				$fields[] = 'assigned_user_id';
				$qg->setFields($fields);
			}
			$pagingModel = new Vtiger_Paging_Model();
			$pagingModel->set('page', 1);
			$pagingModel->set('limit', 500);
			$entries = $listViewModel->getListViewEntries($pagingModel);
			if (!is_array($entries)) {
				return 0;
			}
			if (count($entries) === 0) {
				$db = PearDatabase::getInstance();
				$res = $db->pquery("SELECT COUNT(1) AS c FROM vtiger_projecttask t INNER JOIN vtiger_crmentity c ON c.crmid = t.projecttaskid AND c.deleted = 0 AND c.smownerid = ?", array($currentUser->getId()));
				return (int) $db->query_result($res, 0, 'c');
			}
			$currentUserId = $currentUser->getId();
			$count = 0;
			foreach ($entries as $recordModel) {
				if (!$recordModel || !($recordModel instanceof Vtiger_Record_Model)) {
					continue;
				}
				$rawData = $recordModel->getRawData();
				$ownerId = null;
				if (is_array($rawData) && isset($rawData['smownerid'])) {
					$ownerId = $rawData['smownerid'];
				} elseif (is_array($rawData) && isset($rawData['assigned_user_id'])) {
					$ownerId = $rawData['assigned_user_id'];
				} else {
					$ownerId = $recordModel->get('assigned_user_id');
				}
				if ($ownerId === $currentUserId || $ownerId === (string) $currentUserId || (string) $ownerId === (string) $currentUserId) {
					$count++;
				}
			}
			return $count;
		} catch (Exception $e) {
			return 0;
		}
	}

	/**
	 * Build one agenda item from activity (date + time display for UI giống Today).
	 */
	/** Màu mặc định theo loại (Schedule): Task, Call, Meeting, ... */
	protected static $agendaTypeColors = array(
		'Task' => '#6c757d',
		'Call' => '#3498db',
		'Meeting' => '#27ae60',
		'Planned' => '#9b59b6',
		'Events' => '#3498db',
	);

	protected function buildAgendaItem(Vtiger_Record_Model $activity) {
		$id = $activity->getId();
		$subject = $activity->get('subject');
		$type = $activity->get('activitytype') ?: 'Events';
		if ($activity->get('activitytype') === 'Task') {
			$dateDisplay = $activity->getDisplayValue('due_date');
			$timeDisplay = '';
			$dateTime = $dateDisplay;
		} else {
			$dateDisplay = $activity->getDisplayValue('date_start');
			$timeStart = $activity->getDisplayValue('time_start');
			$timeEnd = $activity->getDisplayValue('time_end');
			$timeDisplay = trim($timeStart . ($timeEnd ? ' ' . $timeEnd : ''));
			$dateTime = $dateDisplay . ($timeDisplay ? ' ' . $timeDisplay : '');
		}
		$color = isset(self::$agendaTypeColors[$type]) ? self::$agendaTypeColors[$type] : '#95a5a6';
		return array(
			'id' => $id,
			'url' => $activity->getDetailViewUrl(),
			'title' => $subject ?: ('#' . $id),
			'dateTime' => $dateTime,
			'dateDisplay' => $dateDisplay,
			'timeDisplay' => $timeDisplay,
			'type' => $type,
			'color' => $color,
		);
	}

	/**
	 * Agenda: chỉ lịch hôm nay (Today).
	 */
	protected function getAgendaToday($limit = 10) {
		$out = array();
		$currentUser = Users_Record_Model::getCurrentUserModel();
		if (!Users_Privileges_Model::isPermitted('Calendar', 'DetailView')) {
			return $out;
		}
		try {
			$homeModel = Vtiger_Module_Model::getInstance('Home');
			if (!method_exists($homeModel, 'getCalendarActivities')) {
				return $out;
			}
			$pagingModel = new Vtiger_Paging_Model();
			$pagingModel->set('page', 1);
			$pagingModel->set('limit', $limit);
			$activities = $homeModel->getCalendarActivities('today', $pagingModel, $currentUser->getId(), null);
			if (is_array($activities)) {
				foreach ($activities as $activity) {
					if ($activity instanceof Vtiger_Record_Model) {
						$out[] = $this->buildAgendaItem($activity);
					}
				}
			}
		} catch (Exception $e) {
			// ignore
		}
		return $out;
	}

	/**
	 * Agenda: lịch sắp tới (ngày mai, ngày kia...) – không gồm hôm nay.
	 */
	protected function getAgendaUpcoming($limit = 10) {
		$out = array();
		$currentUser = Users_Record_Model::getCurrentUserModel();
		if (!Users_Privileges_Model::isPermitted('Calendar', 'DetailView')) {
			return $out;
		}
		try {
			$homeModel = Vtiger_Module_Model::getInstance('Home');
			if (!method_exists($homeModel, 'getCalendarActivities')) {
				return $out;
			}
			$pagingModel = new Vtiger_Paging_Model();
			$pagingModel->set('page', 1);
			$pagingModel->set('limit', $limit);
			$activities = $homeModel->getCalendarActivities('upcoming', $pagingModel, $currentUser->getId(), null);
			if (is_array($activities)) {
				$todayDate = date('Y-m-d');
				foreach ($activities as $activity) {
					if (!($activity instanceof Vtiger_Record_Model)) {
						continue;
					}
					$start = $activity->get('date_start');
					if ($start === $todayDate) {
						continue;
					}
					$out[] = $this->buildAgendaItem($activity);
					if (count($out) >= $limit) {
						break;
					}
				}
			}
		} catch (Exception $e) {
			// ignore
		}
		return $out;
	}

	/**
	 * Lối tắt Main Page — chỉ hiện khi user có quyền module tương ứng.
	 * @return array[] keys: type (link|info), label, url?, badge?, icon (tasks|calendar|clock)
	 */
	protected function buildMainPageShortcuts(array $links, $taskCount, $loggedTimeDisplay) {
		$shortcuts = array();

		if (Users_Privileges_Model::isPermitted('ProjectTask', 'DetailView')) {
			$shortcuts[] = array(
				'type' => 'link',
				'icon' => 'tasks',
				'label' => vtranslate('LBL_MK_SHORTCUT_MY_TASKS', 'Home'),
				'url' => $links['projecttask_list'],
				'badge' => (int) $taskCount,
			);
		}

		if (Users_Privileges_Model::isPermitted('Calendar', 'DetailView')) {
			$shortcuts[] = array(
				'type' => 'link',
				'icon' => 'calendar',
				'label' => vtranslate('LBL_MK_SHORTCUT_EVENTS', 'Home'),
				'url' => $links['calendar'],
				'badge' => 0,
			);
		}

		if ($loggedTimeDisplay !== '') {
			$shortcuts[] = array(
				'type' => 'info',
				'icon' => 'clock',
				'label' => vtranslate('LBL_MK_SHORTCUT_SESSION', 'Home'),
				'badge' => $loggedTimeDisplay,
			);
		}

		return $shortcuts;
	}

	public function process(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$currentUser = Users_Record_Model::getCurrentUserModel();

		// Real data: projects and tasks (assigned to me only), agenda (my schedule)
		$mainPageProjects = $this->getMainPageList('Project', 8);
		$mainPageTasks = $this->getMainPageList('ProjectTask', 8);
		$projectTaskCount = $this->getProjectTaskCount();
		$mainPageAgenda = $this->getAgendaToday(10);
		$mainPageAgendaUpcoming = $this->getAgendaUpcoming(10);

		// Links for shortcuts (with app=MANAGEMENT)
		$app = 'MANAGEMENT';
		$mainPageLinks = array(
			'projecttask_list' => 'index.php?module=ProjectTask&view=List&app=' . $app,
			'project_list' => 'index.php?module=Project&view=List&app=' . $app,
			'calendar' => 'index.php?module=Calendar&view=Calendar&app=' . $app,
			'home' => 'index.php?module=Home&view=DashBoard&app=' . $app,
		);

		$viewer->assign('CURRENT_USER', $currentUser);
		$viewer->assign('MAINPAGE_PROJECTS', $mainPageProjects);
		$viewer->assign('MAINPAGE_TASKS', $mainPageTasks);
		$viewer->assign('MAINPAGE_TASK_COUNT', $projectTaskCount);
		$viewer->assign('MAINPAGE_AGENDA', $mainPageAgenda);
		$viewer->assign('MAINPAGE_AGENDA_UPCOMING', $mainPageAgendaUpcoming);
		$viewer->assign('MAINPAGE_LINKS', $mainPageLinks);

		// Announcements from vtiger_announcement (filter by current user: assigned to me or to all)
		$mainPageAnnouncements = array();
		$mainPageAssignableUsers = array();
		$mainPageAccessibleGroups = array();
		if (class_exists('Settings_Vtiger_Announcement_Model')) {
			$mainPageAnnouncements = Settings_Vtiger_Announcement_Model::getAllForDisplay(20, $currentUser->getId());
			$mainPageAssignableUsers = $currentUser->getAccessibleUsers();
			if (!is_array($mainPageAssignableUsers)) {
				$mainPageAssignableUsers = array();
			}
			$mainPageAccessibleGroups = $currentUser->getAccessibleGroups();
			if (!is_array($mainPageAccessibleGroups)) {
				$mainPageAccessibleGroups = array();
			}
		}
		$viewer->assign('MAINPAGE_ANNOUNCEMENTS', $mainPageAnnouncements);
		$viewer->assign('MAINPAGE_ASSIGNABLE_USERS', $mainPageAssignableUsers);
		$viewer->assign('MAINPAGE_ACCESSIBLE_GROUPS', $mainPageAccessibleGroups);
		$viewer->assign('MAINPAGE_CURRENT_USER_ID', $currentUser->getId());

		// My logged time: tính từ lúc đăng nhập (session)
		$loginTime = isset($_SESSION['user_login_time']) ? (int)$_SESSION['user_login_time'] : 0;
		$loggedTimeDisplay = '';
		$loggedTimeSeconds = 0;
		if ($loginTime > 0) {
			$loggedTimeSeconds = time() - $loginTime;
			$loggedTimeDisplay = self::formatLoggedTime($loggedTimeSeconds);
		}
		$viewer->assign('MAINPAGE_LOGIN_TIMESTAMP', $loginTime);
		$viewer->assign('MAINPAGE_LOGGED_TIME_DISPLAY', $loggedTimeDisplay);
		$viewer->assign('MAINPAGE_LOGGED_TIME_SECONDS', $loggedTimeSeconds);

		// Shortcuts: chỉ module user có quyền; bỏ Stickies/Bookmarks (không có module)
		$viewer->assign('MAINPAGE_SHORTCUTS', $this->buildMainPageShortcuts($mainPageLinks, $projectTaskCount, $loggedTimeDisplay));

		// Heartbeat: cập nhật login_time của phiên hiện tại để Team Status (30 phút) coi user đang có hoạt động
		if (class_exists('LoginHeartbeat')) {
			LoginHeartbeat::update($currentUser->get('user_name'));
		}

		// Lịch sử đăng nhập (vtiger_loginhistory) cho user hiện tại
		$mainPageLoginHistory = self::getLoginHistoryForUser($currentUser->get('user_name'), 15);
		$viewer->assign('MAINPAGE_LOGIN_HISTORY', $mainPageLoginHistory);

		// Team Status: chỉ CEO/Admin xem danh sách thành viên (online/offline/ngày nghỉ) + bộ lọc
		$canSeeTeamStatus = self::isUserCEOOrAdmin($currentUser);
		$mainPageTeamStatus = array();
		$teamFilterOptions = array('users' => array(), 'departments' => array());
		if ($canSeeTeamStatus) {
			// Luôn lấy danh sách TẤT CẢ user (không lọc theo người phụ trách/phòng ban) để cả 2 acc đều thấy nhau online
			$filterDate = $request->get('team_filter_date');
			$mainPageTeamStatus = self::getTeamStatusForCEO('', '', $filterDate);
			// Giữ lại giá trị filter cho form (hiển thị đúng dropdown, nhưng danh sách đã là tất cả)
			$filterUser = isset($_REQUEST['team_filter_user']) ? trim((string) $_REQUEST['team_filter_user']) : '';
			$filterDept = isset($_REQUEST['team_filter_department']) ? trim((string) $_REQUEST['team_filter_department']) : '';
			if ($filterUser === '' || $filterUser === '0') $filterUser = '';
			if ($filterDept === '') $filterDept = '';
			// User đang đăng nhập (có session) luôn hiển thị Online trong Team Status
			$currentUserId = $currentUser->getId();
			foreach ($mainPageTeamStatus as &$m) {
				if (isset($m['id']) && (int)$m['id'] === (int)$currentUserId && (!isset($m['status']) || $m['status'] !== 'leave')) {
					$m['status'] = 'online';
					$m['status_label'] = 'Online';
				}
			}
			unset($m);
			$teamFilterOptions = self::getTeamStatusFilterOptions();
			$mainPageTeamStatusLeaveOnly = array_filter($mainPageTeamStatus, function ($m) { return isset($m['status']) && $m['status'] === 'leave'; });
			$teamFilterDateDisplay = !empty($filterDate) ? date('d/m/Y', strtotime($filterDate)) : date('d/m/Y');
		} else {
			$mainPageTeamStatusLeaveOnly = array();
			$teamFilterDateDisplay = date('d/m/Y');
		}
		$viewer->assign('MAINPAGE_CAN_SEE_TEAM_STATUS', $canSeeTeamStatus);
		$viewer->assign('MAINPAGE_TEAM_STATUS', $mainPageTeamStatus);
		$viewer->assign('MAINPAGE_TEAM_STATUS_LEAVE_ONLY', $mainPageTeamStatusLeaveOnly);
		$viewer->assign('MAINPAGE_TEAM_FILTER_DATE_DISPLAY', $teamFilterDateDisplay);
		$viewer->assign('MAINPAGE_TEAM_FILTER_USER', $request->get('team_filter_user'));
		$viewer->assign('MAINPAGE_TEAM_FILTER_DEPARTMENT', $request->get('team_filter_department'));
		$teamFilterDate = $request->get('team_filter_date');
		if (empty($teamFilterDate)) {
			$teamFilterDate = date('Y-m-d');
		}
		$viewer->assign('MAINPAGE_TEAM_FILTER_DATE', $teamFilterDate);
		$viewer->assign('MAINPAGE_TEAM_FILTER_OPTIONS', $teamFilterOptions);

		$viewer->view('MainPage.tpl', 'Home');
	}

	/**
	 * Format seconds to human string e.g. "2h 15m", "45m", "5 giây"
	 */
	protected static function formatLoggedTime($seconds) {
		$seconds = (int)$seconds;
		if ($seconds < 60) {
			return $seconds . ' giây';
		}
		if ($seconds < 3600) {
			return floor($seconds / 60) . ' phút';
		}
		$h = floor($seconds / 3600);
		$m = floor(($seconds % 3600) / 60);
		if ($m > 0) {
			return $h . 'h ' . $m . 'm';
		}
		return $h . 'h';
	}

	/**
	 * Kiểm tra user có phải CEO hoặc Admin (được xem Team Status tất cả thành viên).
	 */
	protected static function isUserCEOOrAdmin($userModel) {
		if (!$userModel) {
			return false;
		}
		if ($userModel->isAdminUser()) {
			return true;
		}
		$roleId = $userModel->get('roleid');
		if (empty($roleId)) {
			return false;
		}
		$db = PearDatabase::getInstance();
		$r = $db->pquery("SELECT rolename FROM vtiger_role WHERE roleid = ?", array($roleId));
		if ($db->num_rows($r)) {
			$name = strtolower(trim($db->query_result($r, 0, 'rolename')));
			return ($name === 'ceo' || preg_match('/\bceo\b/', $name));
		}
		return false;
	}

	/**
	 * Lấy danh sách user và phòng ban để làm option cho bộ lọc Team Status.
	 */
	protected static function getTeamStatusFilterOptions() {
		$out = array('users' => array(), 'departments' => array());
		try {
			$db = PearDatabase::getInstance();
			$r = $db->pquery("SELECT id, first_name, last_name, user_name FROM vtiger_users WHERE status = 'Active' ORDER BY last_name, first_name", array());
			if ($r) {
				while ($row = $db->fetchByAssoc($r)) {
					$name = trim($row['first_name'] . ' ' . $row['last_name']);
					if (empty($name)) $name = $row['user_name'];
					$out['users'][] = array('id' => (int)$row['id'], 'name' => $name);
				}
			}
			$r2 = $db->pquery("SELECT DISTINCT department FROM vtiger_users WHERE status = 'Active' AND department IS NOT NULL AND TRIM(department) != '' ORDER BY department", array());
			if ($r2) {
				while ($row = $db->fetchByAssoc($r2)) {
					$out['departments'][] = $row['department'];
				}
			}
		} catch (Exception $e) { }
		return $out;
	}

	/**
	 * Lấy danh sách thành viên với trạng thái: online, offline, ngày nghỉ.
	 * Chỉ gọi khi user là CEO/Admin.
	 * @param string|int $filterUser Người phụ trách (reports_to_id) - chỉ lấy user thuộc quyền người này; rỗng = tất cả
	 * @param string $filterDept Phòng ban; rỗng = tất cả
	 * @param string $filterDate Ngày xem trạng thái (Y-m-d); rỗng = hôm nay
	 */
	protected static function getTeamStatusForCEO($filterUser = '', $filterDept = '', $filterDate = '') {
		$list = array();
		$today = !empty($filterDate) ? date('Y-m-d', strtotime($filterDate)) : date('Y-m-d');
		try {
			$db = PearDatabase::getInstance();
			$params = array();
			$sqlUsers = "SELECT u.id, u.user_name, u.first_name, u.last_name, u.reports_to_id, u.department
				FROM vtiger_users u
				WHERE u.status = 'Active'";
			if ($filterUser !== '' && $filterUser !== null) {
				$sqlUsers .= " AND u.reports_to_id = ?";
				$params[] = $filterUser;
			}
			if ($filterDept !== '' && $filterDept !== null) {
				$sqlUsers .= " AND TRIM(COALESCE(u.department,'')) = ?";
				$params[] = trim($filterDept);
			}
			$sqlUsers .= " ORDER BY u.last_name, u.first_name";
			$resUsers = $db->pquery($sqlUsers, $params);
			if (!$resUsers) {
				return $list;
			}
			$userNames = array();
			while ($row = $db->fetchByAssoc($resUsers)) {
				$id = (int) $row['id'];
				$name = trim($row['first_name'] . ' ' . $row['last_name']);
				if (empty($name)) {
					$name = $row['user_name'];
				}
				$initial = mb_strtoupper(mb_substr($name, 0, 1));
				if (empty($initial)) {
					$initial = mb_strtoupper(mb_substr($row['user_name'], 0, 1));
				}
				$list[$id] = array(
					'id' => $id,
					'name' => $name,
					'initial' => $initial ?: '?',
					'user_name' => $row['user_name'],
					'status' => 'offline',
					'status_label' => 'Offline',
					'leave_note' => '',
				);
				$userNames[$row['user_name']] = $id;
			}
			if (empty($list)) {
				return $list;
			}

			// Online/Offline: dùng chung logic với Teams (bảng vtiger_user_activity, trường last_seen).
			// - Current user: luôn Online nếu không Inactive.
			// - Các user khác: Online nếu last_seen trong vòng 2 phút (120 giây), còn lại = Offline.
			$userIds = array_keys($list);
			if (!empty($userIds) && Vtiger_Utils::CheckTable('vtiger_user_activity')) {
				$placeholders = generateQuestionMarks($userIds);
				$sqlPresence = "SELECT u.id, u.status AS user_status, ua.last_seen
					FROM vtiger_users u
					LEFT JOIN vtiger_user_activity ua ON ua.userid = u.id
					WHERE u.deleted = 0 AND u.id IN ($placeholders)";
				$resPresence = $db->pquery($sqlPresence, $userIds);
				$now = time();
				$currentUserId = Users_Record_Model::getCurrentUserModel()->getId();
				while ($resPresence && ($row = $db->fetchByAssoc($resPresence))) {
					$uid = (int)$row['id'];
					if (!isset($list[$uid])) {
						continue;
					}
					$isInactive = ($row['user_status'] === 'Inactive');
					$lastSeen = $row['last_seen'];
					$isOnline = false;
					if ($uid === (int)$currentUserId && !$isInactive) {
						// User hiện tại luôn Online (giống Teams)
						$isOnline = true;
					} elseif (!$isInactive && !empty($lastSeen)) {
						$ts = strtotime($lastSeen);
						if ($ts !== false) {
							$diff = $now - $ts;
							if ($diff >= 0 && $diff <= 120) {
								$isOnline = true;
							}
						}
					}
					if ($isOnline) {
						$list[$uid]['status'] = 'online';
						$list[$uid]['status_label'] = 'Online';
					} else {
						$list[$uid]['status'] = 'offline';
						$list[$uid]['status_label'] = 'Offline';
					}
				}
			}

			// Ngày nghỉ phép: vtiger_leaverequest approved, hôm nay nằm trong [date_start, due_date]
			if (Vtiger_Utils::CheckTable('vtiger_leaverequest')) {
				$sqlLeave = "SELECT created_user_id, subject, leave_type FROM vtiger_leaverequest
					WHERE approval_status = 'approved'
					AND created_user_id > 0
					AND date_start <= ? AND (due_date >= ? OR (due_date IS NULL AND date_start = ?))";
				$resLeave = $db->pquery($sqlLeave, array($today, $today, $today));
				if ($resLeave) {
				while ($row = $db->fetchByAssoc($resLeave)) {
					$uid = (int) $row['created_user_id'];
					if (isset($list[$uid])) {
						$list[$uid]['status'] = 'leave';
						$list[$uid]['status_label'] = 'Ngày nghỉ';
						$note = decode_html($row['subject']);
						if (empty($note)) {
							$note = $row['leave_type'] === 'unpaid' ? 'Nghỉ không lương' : 'Nghỉ phép';
						}
						$list[$uid]['leave_note'] = $note;
					}
				}
				}
			}
			// Sắp xếp: Ngày nghỉ → Online → Offline (người nghỉ phép hiển thị trước)
			$order = array('leave' => 0, 'online' => 1, 'offline' => 2);
			usort($list, function ($a, $b) use ($order) {
				$oa = isset($order[$a['status']]) ? $order[$a['status']] : 2;
				$ob = isset($order[$b['status']]) ? $order[$b['status']] : 2;
				return $oa - $ob;
			});
		} catch (Exception $e) {
			// ignore
		}
		return array_values($list);
	}

	/**
	 * Lấy lịch sử đăng nhập/đăng xuất của user (vtiger_loginhistory).
	 * @param string $userName
	 * @param int $limit
	 * @return array list of { login_time, logout_time, status, login_display, logout_display, duration_display }
	 */
	protected static function getLoginHistoryForUser($userName, $limit = 15) {
		$list = array();
		if (empty($userName)) {
			return $list;
		}
		try {
			$db = PearDatabase::getInstance();
			$sql = "SELECT login_id, login_time, logout_time, status FROM vtiger_loginhistory 
				WHERE user_name = ? ORDER BY login_id DESC LIMIT " . (int)$limit;
			$res = $db->pquery($sql, array($userName));
			while ($row = $db->fetchByAssoc($res)) {
				$loginTs = strtotime($row['login_time']);
				$logoutTs = !empty($row['logout_time']) && $row['logout_time'] !== '0000-00-00 00:00:00' ? strtotime($row['logout_time']) : 0;
				$durationSec = $logoutTs > 0 ? ($logoutTs - $loginTs) : 0;
				$list[] = array(
					'login_time' => $row['login_time'],
					'logout_time' => $row['logout_time'],
					'status' => $row['status'],
					'login_display' => $loginTs ? date('d/m H:i', $loginTs) : '-',
					'logout_display' => $logoutTs ? date('d/m H:i', $logoutTs) : '-',
					'duration_display' => $durationSec > 0 ? self::formatLoggedTime($durationSec) : '-',
				);
			}
		} catch (Exception $e) {
			// ignore
		}
		return $list;
	}
}
