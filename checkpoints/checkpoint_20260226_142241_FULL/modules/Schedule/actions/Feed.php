<?php
/*+***********************************************************************************
 * Schedule dùng chung Feed với Calendar: lấy Task/Event theo Activity Types, màu đúng.
 *************************************************************************************/

require_once 'modules/Calendar/actions/Feed.php';

class Schedule_Feed_Action extends Calendar_Feed_Action {
	// Kế thừa process() từ Calendar: cùng logic pullTasks, pullEvents, màu từ request
}
