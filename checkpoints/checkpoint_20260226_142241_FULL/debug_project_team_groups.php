<?php
/**
 * Debug script to check Project Team Groups assignment
 * Run this from browser: http://your-domain/debug_project_team_groups.php?projectid=XXX
 */

require_once 'config.inc.php';
require_once 'include/utils/utils.php';
require_once 'includes/Loader.php';
vimport('includes.runtime.EntryPoint');

$app = new Vtiger_WebUI();
$app->login();

$db = PearDatabase::getInstance();

// Get project ID from URL
$projectId = isset($_GET['projectid']) ? (int)$_GET['projectid'] : null;

echo "<h2>Project Team Groups Debug</h2>";

// 1. Check if mapping table exists
echo "<h3>1. Check vtiger_project_team_groups table</h3>";
$tableCheck = $db->pquery("SHOW TABLES LIKE 'vtiger_project_team_groups'", array());
if ($db->num_rows($tableCheck) > 0) {
    echo "✅ Table exists<br>";
    
    // Check structure
    $structure = $db->pquery("DESCRIBE vtiger_project_team_groups", array());
    echo "<strong>Table structure:</strong><br>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($row = $db->fetchByAssoc($structure)) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td></tr>";
    }
    echo "</table><br>";
    
    // Check all mappings
    $allMappings = $db->pquery("SELECT * FROM vtiger_project_team_groups", array());
    echo "<strong>All mappings:</strong><br>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>projectid</th><th>team_groupid</th></tr>";
    while ($row = $db->fetchByAssoc($allMappings)) {
        echo "<tr><td>{$row['projectid']}</td><td>{$row['team_groupid']}</td></tr>";
    }
    echo "</table><br>";
} else {
    echo "❌ Table does NOT exist<br>";
}

// 2. Check specific project if provided
if ($projectId) {
    echo "<h3>2. Check Project ID: $projectId</h3>";
    
    // Get smownerid from vtiger_crmentity
    $ownerRes = $db->pquery("SELECT smownerid FROM vtiger_crmentity WHERE crmid = ?", array($projectId));
    if ($db->num_rows($ownerRes) > 0) {
        $smownerid = $db->query_result($ownerRes, 0, 'smownerid');
        echo "<strong>smownerid in vtiger_crmentity:</strong> $smownerid<br>";
        
        if ($smownerid < 0) {
            echo "✅ Negative ID detected (team group): " . abs($smownerid) . "<br>";
        } else {
            echo "⚠️ Positive ID (user or default group)<br>";
            
            // Check if it's a user
            $userCheck = $db->pquery("SELECT id, first_name, last_name FROM vtiger_users WHERE id = ?", array($smownerid));
            if ($db->num_rows($userCheck) > 0) {
                $user = $db->fetchByAssoc($userCheck);
                echo "→ This is a USER: {$user['first_name']} {$user['last_name']}<br>";
            } else {
                // Check if it's a default group
                $groupCheck = $db->pquery("SELECT groupname FROM vtiger_groups WHERE groupid = ?", array($smownerid));
                if ($db->num_rows($groupCheck) > 0) {
                    $group = $db->fetchByAssoc($groupCheck);
                    echo "→ This is a DEFAULT GROUP: {$group['groupname']}<br>";
                }
            }
        }
    }
    
    // Check mapping table for this project
    $mappingRes = $db->pquery("SELECT team_groupid FROM vtiger_project_team_groups WHERE projectid = ?", array($projectId));
    if ($db->num_rows($mappingRes) > 0) {
        $teamGroupId = $db->query_result($mappingRes, 0, 'team_groupid');
        echo "<strong>Mapping found:</strong> team_groupid = $teamGroupId<br>";
        
        // Get team group name
        $groupRes = $db->pquery("SELECT group_name FROM vtiger_team_groups WHERE groupid = ?", array($teamGroupId));
        if ($db->num_rows($groupRes) > 0) {
            $groupName = $db->query_result($groupRes, 0, 'group_name');
            echo "<strong>Team Group Name:</strong> $groupName<br>";
        }
        
        // Get all users in this team group
        $usersRes = $db->pquery(
            "SELECT u.id, u.first_name, u.last_name 
             FROM vtiger_users u
             INNER JOIN vtiger_team_group_users tgu ON tgu.userid = u.id
             WHERE tgu.groupid = ? AND u.deleted = 0
             ORDER BY u.last_name, u.first_name",
            array($teamGroupId)
        );
        
        echo "<strong>Users in this team group:</strong><br>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>First Name</th><th>Last Name</th></tr>";
        $userCount = 0;
        while ($user = $db->fetchByAssoc($usersRes)) {
            echo "<tr><td>{$user['id']}</td><td>{$user['first_name']}</td><td>{$user['last_name']}</td></tr>";
            $userCount++;
        }
        echo "</table><br>";
        echo "<strong>Total users:</strong> $userCount<br>";
    } else {
        echo "❌ No mapping found for this project<br>";
    }
} else {
    echo "<h3>2. No project ID provided</h3>";
    echo "Add ?projectid=XXX to URL to check specific project<br>";
}

// 3. List all team groups
echo "<h3>3. All Team Groups</h3>";
$teamGroupsRes = $db->pquery("SELECT groupid, group_name FROM vtiger_team_groups ORDER BY group_name", array());
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Group ID</th><th>Group Name</th><th>User Count</th></tr>";
while ($group = $db->fetchByAssoc($teamGroupsRes)) {
    $groupId = $group['groupid'];
    $groupName = $group['group_name'];
    
    // Count users in this group
    $userCountRes = $db->pquery("SELECT COUNT(*) as cnt FROM vtiger_team_group_users WHERE groupid = ?", array($groupId));
    $userCount = $db->num_rows($userCountRes) > 0 ? $db->query_result($userCountRes, 0, 'cnt') : 0;
    
    echo "<tr><td>$groupId</td><td>$groupName</td><td>$userCount</td></tr>";
}
echo "</table><br>";

// 4. List all projects with their owners
echo "<h3>4. All Projects (last 10)</h3>";
$projectsRes = $db->pquery(
    "SELECT p.projectid, p.projectname, c.smownerid 
     FROM vtiger_project p
     INNER JOIN vtiger_crmentity c ON c.crmid = p.projectid
     WHERE c.deleted = 0
     ORDER BY p.projectid DESC
     LIMIT 10",
    array()
);
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Project ID</th><th>Project Name</th><th>smownerid</th><th>Has Mapping?</th></tr>";
while ($project = $db->fetchByAssoc($projectsRes)) {
    $pid = $project['projectid'];
    $pname = $project['projectname'];
    $owner = $project['smownerid'];
    
    // Check if has mapping
    $hasMapping = $db->pquery("SELECT 1 FROM vtiger_project_team_groups WHERE projectid = ?", array($pid));
    $hasMappingText = $db->num_rows($hasMapping) > 0 ? "✅ Yes" : "❌ No";
    
    echo "<tr><td><a href='?projectid=$pid'>$pid</a></td><td>$pname</td><td>$owner</td><td>$hasMappingText</td></tr>";
}
echo "</table><br>";

echo "<hr>";
echo "<p><strong>Usage:</strong> Add ?projectid=XXX to URL to check specific project</p>";
