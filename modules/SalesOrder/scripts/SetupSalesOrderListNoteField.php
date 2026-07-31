<?php
/*+***********************************************************************************
 * Ensure mk_list_note (Ghi chú list) exists for SalesOrder.
 * Run: php -f modules/SalesOrder/scripts/SetupSalesOrderListNoteField.php
 *************************************************************************************/
chdir(dirname(__DIR__, 3));
require_once 'config.inc.php';
require_once 'include/utils/utils.php';
require_once 'vtlib/Vtiger/Module.php';
require_once 'modules/SalesOrder/helpers/ListNoteField.php';

$name = SalesOrder_ListNoteField_Helper::ensure();
echo "Ensured field: {$name}\nDone.\n";
