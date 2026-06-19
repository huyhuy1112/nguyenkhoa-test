<?php
/*+***********************************************************************************
 * Phase 1 — Modern Leads SALES UI backend (side tables + seed).
 *
 * Run from vtiger root:
 *   php -f modules/Leads/scripts/InstallModernLeadsBackend.php
 *   php -f modules/Leads/scripts/InstallModernLeadsBackend.php -- --force-seed
 *************************************************************************************/

chdir(dirname(__DIR__, 3));
require_once 'vendor/autoload.php';
require_once 'config.php';
include_once 'vtlib/Vtiger/Cron.php';
vimport('includes.runtime.EntryPoint');
require_once 'modules/Users/Users.php';
require_once 'modules/Leads/models/ModernService.php';

global $adb, $current_user;
$current_user = Users::getActiveAdminUser();

$forceSeed = in_array('--force-seed', $argv ?? array(), true);

echo "=== Install Modern Leads Backend (Phase 1) ===\n";

Leads_ModernService::installSchema($adb);
echo "Schema ensured.\n";

$seed = Leads_ModernService::seedDemoLeads($forceSeed);
echo "Seed: created={$seed['created']}, skipped={$seed['skipped']}, total_modern={$seed['total']}\n";

echo "=== Done ===\n";
