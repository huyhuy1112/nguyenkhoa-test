<?php
/**
 * Tạo và thêm các module quản lý kho vào menu INVENTORY:
 * - Nhập kho (GoodsReceipt)
 * - Lưu kho (Warehouse)
 * - Xuất kho (GoodsIssue)
 */

require_once 'config.inc.php';
require_once 'include/database/PearDatabase.php';
require_once 'include/utils/utils.php';
require_once 'include/utils/VtlibUtils.php';

$adb = PearDatabase::getInstance();

echo "==========================================\n";
echo "Tạo và thêm module quản lý kho vào INVENTORY\n";
echo "==========================================\n\n";

// Định nghĩa các module cần tạo
$warehouseModules = array(
    'GoodsReceipt' => array(
        'label' => 'Nhập kho',
        'display' => 'Nhập kho'
    ),
    'Warehouse' => array(
        'label' => 'Lưu kho',
        'display' => 'Lưu kho'
    ),
    'GoodsIssue' => array(
        'label' => 'Xuất kho',
        'display' => 'Xuất kho'
    )
);

$appName = 'INVENTORY';

// Bước 1: Kiểm tra module đã tồn tại chưa
echo "Bước 1: Kiểm tra module hiện có...\n";
$moduleTabIds = array();
foreach ($warehouseModules as $moduleName => $config) {
    $result = $adb->pquery("SELECT tabid, tablabel FROM vtiger_tab WHERE name = ?", array($moduleName));
    if ($adb->num_rows($result) > 0) {
        $tabid = $adb->query_result($result, 0, 'tabid');
        $tablabel = $adb->query_result($result, 0, 'tablabel');
        $moduleTabIds[$moduleName] = array(
            'tabid' => $tabid,
            'tablabel' => $tablabel,
            'exists' => true
        );
        echo "  ✓ $moduleName: đã tồn tại (tabid=$tabid, label='$tablabel')\n";
    } else {
        $moduleTabIds[$moduleName] = array(
            'tabid' => null,
            'tablabel' => $config['label'],
            'exists' => false
        );
        echo "  - $moduleName: chưa tồn tại (sẽ tạo mới)\n";
    }
}

// Bước 2: Tạo module mới (nếu chưa có)
echo "\nBước 2: Tạo module mới (nếu cần)...\n";
foreach ($warehouseModules as $moduleName => $config) {
    if (!$moduleTabIds[$moduleName]['exists']) {
        echo "  Tạo module: $moduleName...\n";
        
        // Lấy tabid tiếp theo
        $maxTabResult = $adb->pquery("SELECT MAX(tabid) AS max_tabid FROM vtiger_tab", array());
        $maxTabId = $adb->query_result($maxTabResult, 0, 'max_tabid');
        $newTabId = $maxTabId + 1;
        
        // Lấy sequence tiếp theo
        $maxSeqResult = $adb->pquery("SELECT MAX(tabsequence) AS max_seq FROM vtiger_tab WHERE tabsequence > 0", array());
        $maxSeq = $adb->query_result($maxSeqResult, 0, 'max_seq');
        $newSeq = ($maxSeq ? $maxSeq + 1 : 1);
        
        // Insert vào vtiger_tab
        $adb->pquery(
            "INSERT INTO vtiger_tab (tabid, name, presence, tabsequence, tablabel, modifiedby, modifiedtime, customized, ownedby, version, parent, isentitytype) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            array(
                $newTabId,
                $moduleName,
                0, // presence: 0 = visible
                $newSeq,
                $config['display'],
                null,
                null,
                1, // customized: 1 = custom module
                0, // ownedby
                '1.0', // version
                $appName, // parent
                0  // isentitytype: 0 = no database tables (placeholder)
            )
        );
        
        $moduleTabIds[$moduleName]['tabid'] = $newTabId;
        echo "    ✓ Đã tạo trong vtiger_tab (tabid: $newTabId)\n";
        
        // Tạo thư mục module cơ bản
        $moduleDir = "modules/$moduleName";
        if (!is_dir($moduleDir)) {
            mkdir($moduleDir, 0755, true);
        }
        
        // Tạo file module chính
        $moduleFile = "$moduleDir/$moduleName.php";
        if (!file_exists($moduleFile)) {
            $moduleContent = <<<PHP
<?php
/*+**********************************************************************************
 * Placeholder module: $moduleName
 * Label: {$config['label']}
 */
class $moduleName extends CRMEntity {
    var \$db, \$log;
    var \$column_fields = Array();
    var \$IsCustomModule = true;
    var \$isentitytype = false;
    
    function __construct() {
        global \$log;
        \$this->log = \$log;
        \$this->db = PearDatabase::getInstance();
    }
    
    function vtlib_handler(\$modulename, \$event_type) {
        // Placeholder module - no special handling needed
    }
}
?>
PHP;
            file_put_contents($moduleFile, $moduleContent);
            echo "    ✓ Đã tạo $moduleFile\n";
        }
        
        // Tạo manifest.xml
        $manifestFile = "$moduleDir/manifest.xml";
        if (!file_exists($manifestFile)) {
            $manifestContent = <<<XML
<?xml version='1.0'?>
<module>
    <name>$moduleName</name>
    <label>{$config['label']}</label>
    <parent>$appName</parent>
    <type>extension</type>
    <version>1.0</version>
    <dependencies>
        <vtiger_version>8.0.0</vtiger_version>
    </dependencies>
</module>
XML;
            file_put_contents($manifestFile, $manifestContent);
            echo "    ✓ Đã tạo $manifestFile\n";
        }
        
        // Tạo models/Module.php cơ bản
        $modelsDir = "$moduleDir/models";
        if (!is_dir($modelsDir)) {
            mkdir($modelsDir, 0755, true);
        }
        $modelFile = "$modelsDir/Module.php";
        if (!file_exists($modelFile)) {
            $modelContent = <<<PHP
<?php
vimport('~~/vtlib/Vtiger/Module.php');
class {$moduleName}_Module_Model extends Vtiger_Module_Model {
    // Placeholder module model
}
?>
PHP;
            file_put_contents($modelFile, $modelContent);
            echo "    ✓ Đã tạo $modelFile\n";
        }
        
        // Tạo views/List.php cơ bản
        $viewsDir = "$moduleDir/views";
        if (!is_dir($viewsDir)) {
            mkdir($viewsDir, 0755, true);
        }
        $listViewFile = "$viewsDir/List.php";
        if (!file_exists($listViewFile)) {
            $listViewContent = <<<PHP
<?php
class {$moduleName}_List_View extends Vtiger_List_View {
    // Placeholder list view
}
?>
PHP;
            file_put_contents($listViewFile, $listViewContent);
            echo "    ✓ Đã tạo $listViewFile\n";
        }
    }
}

// Bước 3: Thêm vào menu INVENTORY
echo "\nBước 3: Thêm vào menu INVENTORY...\n";
$seqResult = $adb->pquery("SELECT MAX(sequence) AS max_seq FROM vtiger_app2tab WHERE appname = ?", array($appName));
$nextSeq = ($adb->num_rows($seqResult) > 0 && $adb->query_result($seqResult, 0, 'max_seq') !== null)
    ? (int) $adb->query_result($seqResult, 0, 'max_seq') + 1 : 1;

foreach ($warehouseModules as $moduleName => $config) {
    $tabid = $moduleTabIds[$moduleName]['tabid'];
    if (!$tabid) continue;
    
    $check = $adb->pquery("SELECT 1 FROM vtiger_app2tab WHERE appname = ? AND tabid = ?", array($appName, $tabid));
    if ($adb->num_rows($check) == 0) {
        $adb->pquery(
            "INSERT INTO vtiger_app2tab (appname, tabid, sequence, visible) VALUES (?, ?, ?, ?)",
            array($appName, $tabid, $nextSeq, 1)
        );
        echo "  ✓ Đã thêm $moduleName vào INVENTORY (sequence: $nextSeq)\n";
    } else {
        $adb->pquery(
            "UPDATE vtiger_app2tab SET sequence = ?, visible = 1 WHERE appname = ? AND tabid = ?",
            array($nextSeq, $appName, $tabid)
        );
        echo "  ✓ Đã cập nhật $moduleName trong INVENTORY (sequence: $nextSeq)\n";
    }
    $nextSeq++;
}

echo "\n==========================================\n";
echo "Hoàn thành!\n";
echo "==========================================\n";
echo "\nCác module đã được tạo và thêm vào menu INVENTORY:\n";
foreach ($warehouseModules as $moduleName => $config) {
    echo "  - $moduleName ({$config['label']})\n";
}
echo "\nTiếp theo:\n";
echo "  1. Settings → Configuration → Clear Cache\n";
echo "  2. Logout và login lại\n";
echo "  3. Kiểm tra menu INVENTORY - sẽ thấy: Nhập kho, Lưu kho, Xuất kho\n";
