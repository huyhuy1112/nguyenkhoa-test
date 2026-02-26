<?php
/**
 * Create minimal tables to allow Vtiger to run installation wizard
 */

require_once 'config.inc.php';

$db_server = $dbconfig['db_server'];
$db_user = $dbconfig['db_username'];
$db_pass = $dbconfig['db_password'];
$db_name = $dbconfig['db_name'];
$db_port = str_replace(':', '', $dbconfig['db_port']);

echo "🔧 Creating minimal database structure...\n\n";

$conn = mysqli_connect($db_server, $db_user, $db_pass, $db_name, $db_port ?: 3306);

if (!$conn) {
    die("❌ Connection failed: " . mysqli_connect_error() . "\n");
}

echo "✅ Connected to database: $db_name\n\n";

// Create vtiger_organizationdetails table (minimal structure)
$sql = "CREATE TABLE IF NOT EXISTS `vtiger_organizationdetails` (
  `organization_id` int(11) NOT NULL AUTO_INCREMENT,
  `organizationname` varchar(100) DEFAULT NULL,
  `address` text,
  `city` varchar(30) DEFAULT NULL,
  `state` varchar(30) DEFAULT NULL,
  `code` varchar(30) DEFAULT NULL,
  `country` varchar(30) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `fax` varchar(30) DEFAULT NULL,
  `website` varchar(100) DEFAULT NULL,
  `vatid` varchar(30) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`organization_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

if (mysqli_query($conn, $sql)) {
    echo "✅ Created table: vtiger_organizationdetails\n";
} else {
    echo "⚠️  Table vtiger_organizationdetails: " . mysqli_error($conn) . "\n";
}

mysqli_close($conn);

echo "\n✅ Minimal structure created!\n";
echo "💡 Now try accessing: http://localhost:8080/index.php?module=Install&view=Index\n";


