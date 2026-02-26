<?php
/**
 * Debug script to check project assignment and team group mapping
 * Usage: Access via browser: http://your-domain/debug_project_assignment.php?projectid=XXX
 */

require_once 'config.inc.php';
require_once 'includes/main/WebUI.php';

$db = PearDatabase::getInstance();

// Get project ID from query string
$projectId = isset($_GET['projectid']) ? (int)$_GET['projectid'] : 0;

if (empty($projectId)) {
    echo "<h2>Project Assignment Debug Tool</h2>";
    echo "<p>Usage: ?projectid=XXX</p>";
    
    // List recent projects
    echo "<h3>Recent Projects:</h3>";
    $recentProjects = $db->pquery(
        "SELECT projectid, projectname, smownerid 
         FROM vtiger_project p
         INNER JOIN vtiger_crmentity e ON e.crmid = p.projectid
         WHERE e.deleted = 0
         ORDER BY e.createdtime DESC
         LIMIT 10",
        array()
    );
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Project ID</th><th>Project Name</th><th>Owner ID</th><th>Action</th></tr>";
    while ($row = $db->fetchByAssoc($recentProjects)) {
        $pid = $row['projectid'];
        $name = htmlspecialchars($row['projectname']);
        $owner = $row['smownerid'];
        echo "<tr>";
        echo "<td>$pid</td>";
        echo "<td>$name</td>";
        echo "<td>$owner</td>";
        echo "<td><a href='?projectid=$pid'>Debug</a></td>";
        echo "</tr>";
    }
    echo "</table>";
    exit;
}

echo "<h2>Project Assignment Debug for Project ID: $projectId</h2>";

// 1. Check project exists
$projectRes = $db->pquery(
    "SELECT projectid, projectname, smownerid 
     FROM vtiger_project p
     INNER JOIN vtiger_crmentity e ON e.crmid = p.projectid
     WHERE p.projectid = ? AND e.deleted = 0",
    array($projectId)
);

if ($db->num_rows($projectRes) == 0) {
    echo "<p style='color:red;'>Project not found!</p>";
    exit;
}

$project = $db->fetchByAssoc($projectRes);
echo "<h3>Project Info:</h3>";
echo "<ul>";
echo "<li><strong>Project ID:</strong> " . $project['projectid'] . "</li>";
echo "<li><strong>Project Name:</strong> " . htmlspecialchars($project['projectname']) . "</li>";
echo "<li><strong>Owner ID (smownerid):</strong> " . $project['smownerid'] . "</li>";
echo "</ul>";

// 2. Check team group mapping
echo "<h3>Team Group Mapping:</h3>";
$mappingRes = $db->pquery(
    "SELECT team_groupid FROM vtiger_project_team_groups WHERE projectid = ?",
    array($projectId)
);

if ($db->num_rows($mappingRes) > 0) {
    $teamGroupId = (int)$db->query_result($mappingRes, 0, 'team_groupid');
    echo "<p style='color:green;'>✅ Team Group Mapping Found: Group ID = $teamGroupId</p>";
    
    // Get group name
    $groupRes = $db->pquery(
        "SELECT group_name FROM vtiger_team_groups WHERE groupid = ?",
        array($teamGroupId)
    );
    
    if ($db->num_rows($groupRes) > 0) {
        $groupName = $db->query_result($groupRes, 0, 'group_name');
        echo "<p><strong>Group Name:</strong> " . htmlspecialchars($groupName) . "</p>";
    }
    
    // Get all users in group
    $usersRes = $db->pquery(
        "SELECT u.id, u.first_name, u.last_name, u.user_name
         FROM vtiger_users u
         INNER JOIN vtiger_team_group_users tgu ON tgu.userid = u.id
         WHERE tgu.groupid = ? AND u.deleted = 0
         ORDER BY u.last_name, u.first_name",
        array($teamGroupId)
    );
    
    echo "<h4>Users in Group:</h4>";
    echo "<ul>";
    $userCount = 0;
    while ($userRow = $db->fetchByAssoc($usersRes)) {
        $userCount++;
        $fullName = trim($userRow['first_name'] . ' ' . $userRow['last_name']);
        echo "<li>ID: {$userRow['id']} - " . htmlspecialchars($fullName) . " ({$userRow['user_name']})</li>";
    }
    echo "</ul>";
    echo "<p><strong>Total Users:</strong> $userCount</p>";
    
} else {
    echo "<p style='color:orange;'>⚠️ No Team Group Mapping Found</p>";
}

// 3. Check owner type
$ownerId = (int)$project['smownerid'];
echo "<h3>Owner Analysis:</h3>";
echo "<ul>";
echo "<li><strong>Owner ID:</strong> $ownerId</li>";

if ($ownerId < 0) {
    echo "<li style='color:green;'>✅ Owner ID is negative (Team Group): " . abs($ownerId) . "</li>";
} else {
    echo "<li style='color:orange;'>⚠️ Owner ID is positive (Single User)</li>";
    
    // Check if it's a user
    $userCheck = $db->pquery("SELECT id, first_name, last_name FROM vtiger_users WHERE id = ?", array($ownerId));
    if ($db->num_rows($userCheck) > 0) {
        $user = $db->fetchByAssoc($userCheck);
        $userName = trim($user['first_name'] . ' ' . $user['last_name']);
        echo "<li><strong>User Name:</strong> " . htmlspecialchars($userName) . "</li>";
    } else {
        echo "<li style='color:red;'>❌ Not a valid user ID</li>";
    }
}
echo "</ul>";

// 4. Check notifications
echo "<h3>Notifications Sent:</h3>";
$notifRes = $db->pquery(
    "SELECT id, userid, message, created_at 
     FROM vtiger_notifications 
     WHERE module = 'Project' AND recordid = ?
     ORDER BY created_at DESC
     LIMIT 20",
    array($projectId)
);

if ($db->num_rows($notifRes) > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>User ID</th><th>Message</th><th>Created At</th></tr>";
    while ($notif = $db->fetchByAssoc($notifRes)) {
        echo "<tr>";
        echo "<td>{$notif['id']}</td>";
        echo "<td>{$notif['userid']}</td>";
        echo "<td>" . htmlspecialchars($notif['message']) . "</td>";
        echo "<td>{$notif['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:orange;'>⚠️ No notifications found</p>";
}

echo "<hr>";
echo "<p><a href='?projectid=$projectId'>Refresh</a> | <a href='?'>Back to List</a></p>";
