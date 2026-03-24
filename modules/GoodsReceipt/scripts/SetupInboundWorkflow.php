<?php
require_once 'config.inc.php';
require_once 'include/database/PearDatabase.php';
require_once 'include/utils/utils.php';
require_once 'modules/GoodsReceipt/helpers/WorkflowSetup.php';

GoodsReceipt_WorkflowSetup_Helper::runAll();
echo "Inbound workflow setup completed.\n";
