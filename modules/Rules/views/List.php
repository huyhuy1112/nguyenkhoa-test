<?php
/*+**********************************************************************************
 * Rules_List_View
 *
 * Module "Rules" chỉ dùng làm menu entry trong app SUPPORT.
 * Khi người dùng bấm SUPPORT → Rules (module=Rules&view=List),
 * ta redirect sang trang Rules Engine mới nằm trong HelpDesk:
 *
 *   module=HelpDesk&view=Rules&app=SUPPORT
 *
 ************************************************************************************/

class Rules_List_View extends Vtiger_Index_View {

	public function process(Vtiger_Request $request) {
		// Redirect vĩnh viễn sang HelpDesk Rules
		header('Location: index.php?module=HelpDesk&view=Rules&app=SUPPORT');
		exit;
	}
}
