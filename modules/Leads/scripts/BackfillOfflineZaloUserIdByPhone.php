<?php
/**
 * Backfill: copy zalo_user_id từ lead (thường Online/OA) sang Offline cùng SĐT.
 * Usage:
 *   php modules/Leads/scripts/BackfillOfflineZaloUserIdByPhone.php           # dry-run
 *   php modules/Leads/scripts/BackfillOfflineZaloUserIdByPhone.php --apply
 */
$root = dirname(dirname(dirname(__DIR__)));
chdir($root);
require_once $root . '/config.inc.php';
require_once $root . '/include/utils/utils.php';
require_once $root . '/includes/Loader.php';
require_once $root . '/modules/Leads/models/OfflineGd11Service.php';

$apply = in_array('--apply', isset($argv) ? $argv : array(), true);
$adb = PearDatabase::getInstance();
Leads_OfflineGd11Service::installSchema();

$res = $adb->pquery(
	"SELECT p.leadid AS offline_id, la.phone, src.zalo_user_id, src.leadid AS source_id
	 FROM bace_lead_profile p
	 INNER JOIN vtiger_crmentity ce ON ce.crmid = p.leadid AND ce.deleted = 0
	 LEFT JOIN vtiger_leadaddress la ON la.leadaddressid = p.leadid
	 INNER JOIN bace_lead_profile src ON src.zalo_user_id IS NOT NULL AND src.zalo_user_id <> ''
	 INNER JOIN vtiger_crmentity sce ON sce.crmid = src.leadid AND sce.deleted = 0
	 LEFT JOIN vtiger_leadaddress sla ON sla.leadaddressid = src.leadid
	 WHERE p.is_modern = 1
	   AND (p.zalo_user_id IS NULL OR p.zalo_user_id = '')
	   AND (
			(p.offline_status IS NOT NULL AND p.offline_status <> '')
			OR IFNULL(p.sheet_source, 0) = 1
			OR EXISTS (
				SELECT 1 FROM vtiger_freetagged_objects fo
				INNER JOIN vtiger_freetags t ON t.id = fo.tag_id
				WHERE fo.object_id = p.leadid
				  AND (LOWER(t.tag) = 'mien_phi_offline' OR LOWER(t.tag) LIKE 'offline_%')
			)
	   )
	   AND src.leadid <> p.leadid
	   AND REPLACE(REPLACE(REPLACE(REPLACE(IFNULL(la.phone,''),' ',''),'-',''),'.',''),'+','')
	     = REPLACE(REPLACE(REPLACE(REPLACE(IFNULL(sla.phone,''),' ',''),'-',''),'.',''),'+','')
	   AND IFNULL(la.phone,'') <> ''
	 ORDER BY p.leadid ASC, src.leadid DESC",
	array()
);

$n = $res ? $adb->num_rows($res) : 0;
$seen = array();
$would = 0;
$updated = 0;

for ($i = 0; $i < $n; $i++) {
	$offlineId = (int) $adb->query_result($res, $i, 'offline_id');
	if ($offlineId <= 0 || isset($seen[$offlineId])) {
		continue;
	}
	$seen[$offlineId] = true;
	$uid = trim((string) $adb->query_result($res, $i, 'zalo_user_id'));
	$phone = (string) $adb->query_result($res, $i, 'phone');
	$srcId = (int) $adb->query_result($res, $i, 'source_id');
	$norm = Leads_OfflineGd11Service::normalizeVnPhone($phone);
	if ($uid === '') {
		continue;
	}
	$would++;
	if ($apply) {
		$link = Leads_OfflineGd11Service::linkZaloUserIdByPhone($norm !== '' ? $norm : $phone, $uid);
		if (!empty($link['updated'])) {
			$updated += (int) $link['updated'];
			echo "Linked OA {$uid} (from lead {$srcId}) → offline {$offlineId} (phone {$norm})\n";
		}
	} else {
		echo "[dry-run] would link OA {$uid} (from lead {$srcId}) → offline {$offlineId} (phone {$norm})\n";
	}
}

if (!$apply) {
	echo "Dry-run: {$would} Offline lead(s) có thể gắn OA id. Re-run với --apply để ghi.\n";
} else {
	echo "Done: updated {$updated} Offline lead(s).\n";
}
