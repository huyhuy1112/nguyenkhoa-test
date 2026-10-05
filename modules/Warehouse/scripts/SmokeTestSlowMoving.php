<?php
chdir(dirname(__DIR__, 3));
require_once 'config.inc.php';
require_once 'include/utils/utils.php';
require_once 'modules/Warehouse/helpers/SettingsHelper.php';
require_once 'modules/Warehouse/helpers/SlowMovingHelper.php';

Warehouse_Settings_Helper::ensureTable();
$c = Warehouse_Settings_Helper::slowMovingConfig();
echo 'config=' . json_encode($c) . "\n";
$r = Warehouse_SlowMoving_Helper::compute('', 0);
echo 'items=' . count($r['items']) . ' alert=' . $r['summary']['alert_count'] . ' total=' . $r['summary']['total'] . "\n";
if (!empty($r['items'][0])) {
	echo 'sample=' . json_encode($r['items'][0], JSON_UNESCAPED_UNICODE) . "\n";
}
echo "OK\n";
