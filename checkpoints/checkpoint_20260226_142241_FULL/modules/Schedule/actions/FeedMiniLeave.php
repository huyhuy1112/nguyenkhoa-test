<?php
/*+***********************************************************************************
 * Schedule dùng chung feed đơn nghỉ với Calendar: chỉ Admin/CEO thấy hết, role khác chỉ thấy đơn của mình.
 *************************************************************************************/

require_once 'modules/Calendar/actions/FeedMiniLeave.php';

class Schedule_FeedMiniLeave_Action extends Calendar_FeedMiniLeave_Action {
	// Kế thừa process() từ Calendar: cùng bộ lọc theo role (Admin/CEO = tất cả, còn lại = đơn của mình)
}
