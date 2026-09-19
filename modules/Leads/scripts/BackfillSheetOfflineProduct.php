<?php
/**
 * Backfill: lead Google Sheet (sheet_source=1) → tag mien_phi_offline + sản phẩm Offline.
 *
 * Dry-run (mặc định):
 *   php modules/Leads/scripts/BackfillSheetOfflineProduct.php
 *
 * Apply:
 *   php modules/Leads/scripts/BackfillSheetOfflineProduct.php --apply
 *   php modules/Leads/scripts/BackfillSheetOfflineProduct.php --apply --limit=1000
 */
chdir(dirname(dirname(dirname(__DIR__))));
require_once 'includes/main/WebUI.php';

$apply = in_array('--apply', $argv, true);
$limit = 500;
foreach ($argv as $arg) {
	if (strpos($arg, '--limit=') === 0) {
		$limit = max(1, min(5000, (int) substr($arg, 8)));
	}
}

$adb = PearDatabase::getInstance();
require_once 'modules/Leads/models/LeadProductsService.php';
require_once 'modules/Leads/models/OfflineGd11Service.php';
Leads_LeadProductsService::installSchema($adb);
Leads_OfflineGd11Service::installSchema($adb);

$sql = "SELECT p.leadid
	FROM bace_lead_profile p
	INNER JOIN vtiger_crmentity ce ON ce.crmid = p.leadid AND ce.deleted = 0 AND ce.setype = 'Leads'
	WHERE IFNULL(p.sheet_source, 0) = 1
	  AND NOT EXISTS (
		SELECT 1 FROM bace_lead_products lp
		WHERE lp.leadid = p.leadid AND lp.group_code = 'offline'
	  )
	ORDER BY p.leadid ASC
	LIMIT " . (int) $limit;

$res = $adb->pquery($sql, array());
$total = $res ? $adb->num_rows($res) : 0;
echo ($apply ? "[APPLY]" : "[DRY-RUN]") . " sheet leads missing Offline product: {$total}\n";

$ok = 0;
$fail = 0;
for ($i = 0; $i < $total; $i++) {
	$leadId = (int) $adb->query_result($res, $i, 'leadid');
	echo " - lead #{$leadId}";
	if (!$apply) {
		echo " (skip)\n";
		continue;
	}
	try {
		$tagName = 'mien_phi_offline';
		$hasTag = $adb->pquery(
			"SELECT 1
			 FROM vtiger_freetagged_objects fo
			 INNER JOIN vtiger_freetags t ON t.id = fo.tag_id
			 WHERE fo.object_id = ? AND LOWER(t.tag) = ?",
			array($leadId, $tagName)
		);
		if (!$hasTag || $adb->num_rows($hasTag) < 1) {
			$tidRes = $adb->pquery(
				'SELECT id FROM vtiger_freetags WHERE LOWER(tag) = ? OR LOWER(raw_tag) = ? LIMIT 1',
				array($tagName, $tagName)
			);
			$tagId = 0;
			if ($tidRes && $adb->num_rows($tidRes) > 0) {
				$tagId = (int) $adb->query_result($tidRes, 0, 'id');
			} else {
				$tagId = (int) $adb->getUniqueId('vtiger_freetags');
				$adb->pquery(
					'INSERT INTO vtiger_freetags (id, tag, raw_tag, visibility, owner) VALUES (?,?,?,?,?)',
					array($tagId, $tagName, $tagName, 'public', 1)
				);
			}
			if ($tagId > 0) {
				// tag_id, tagger_id, object_id, tagged_on, module
				$adb->pquery(
					'INSERT INTO vtiger_freetagged_objects VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE tag_id = ?',
					array($tagId, 1, $leadId, date('Y-m-d H:i:s'), 'Leads', $tagId)
				);
			}
		}
		Leads_LeadProductsService::ensureGroup($leadId, 'offline', 1);
		echo " OK\n";
		$ok++;
	} catch (Exception $e) {
		echo " FAIL: " . $e->getMessage() . "\n";
		$fail++;
	}
}

echo "Done. ok={$ok} fail={$fail}\n";
if (!$apply && $total > 0) {
	echo "Chạy lại với --apply để ghi DB.\n";
}
