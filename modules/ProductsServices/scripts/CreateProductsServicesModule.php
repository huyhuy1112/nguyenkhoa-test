<?php
/*+***********************************************************************************
 * Create ProductsServices module (unified Products + Services) using vtlib.
 *
 * Safe to run multiple times (idempotent-ish):
 * - If module exists, it will attempt to add missing blocks/fields/filters/relations.
 *
 * IMPORTANT:
 * - Does NOT modify any core vtiger modules.
 * - Inventory line-items (Quotes/SO/Invoice) still rely on core Products/Services tables.
 *   This script only adds related-lists / reference fields for compatibility without core edits.
 *************************************************************************************/

chdir(dirname(__DIR__, 3)); // modules/ProductsServices/scripts -> vtiger root

require_once 'config.inc.php';
require_once 'include/utils/utils.php';
require_once 'vtlib/Vtiger/Module.php';

$moduleName  = 'ProductsServices';
$moduleLabel = 'Products & Services';

function println($s) {
	echo $s . "\n";
}

function getOrCreateBlock(Vtiger_Module $module, $label) {
	$block = Vtiger_Block::getInstance($label, $module);
	if ($block) return $block;
	$block = new Vtiger_Block();
	$block->label = $label;
	$module->addBlock($block);
	return $block;
}

function getOrCreateField(Vtiger_Module $module, Vtiger_Block $block, $fieldname) {
	$field = Vtiger_Field::getInstance($fieldname, $module);
	if ($field) return $field;
	$field = new Vtiger_Field();
	$field->name = $fieldname;
	$block->addField($field);
	return $field;
}

println("=== ProductsServices vtlib installer ===");

$module = Vtiger_Module::getInstance($moduleName);
if (!$module) {
	println("Module not found. Creating: $moduleName");

	$module = new Vtiger_Module();
	$module->name = $moduleName;
	$module->label = $moduleLabel;
	$module->parent = 'SALES';
	$module->save();

	$module->initTables();

	// Enable standard features
	$module->enableTools(Array('Import', 'Export', 'Merge'));
	$module->disableTools('ConvertLead');

	println("✓ Module created and tables initialized.");
} else {
	println("Module already exists: $moduleName");
}

// Blocks
$blockInfo = getOrCreateBlock($module, 'LBL_PRODUCTS_SERVICES_INFORMATION');

// Fields
// 1) name (entity identifier)
$nameField = Vtiger_Field::getInstance('productsservicesname', $module);
if (!$nameField) {
	$nameField = new Vtiger_Field();
	$nameField->name = 'productsservicesname';
	$nameField->label = 'Name';
	$nameField->uitype = 2; // text
	$nameField->column = 'productsservicesname';
	$nameField->columntype = 'VARCHAR(255)';
	$nameField->typeofdata = 'V~M';
	$blockInfo->addField($nameField);
	println("✓ Field created: productsservicesname");
} else {
	println("Field exists: productsservicesname");
}

// Set entity identifier
try {
	$module->setEntityIdentifier($nameField);
} catch (Exception $e) {
	// ignore
}

// 2) item_type picklist: Product, Service
$typeField = Vtiger_Field::getInstance('item_type', $module);
if (!$typeField) {
	$typeField = new Vtiger_Field();
	$typeField->name = 'item_type';
	$typeField->label = 'Type';
	$typeField->uitype = 15; // picklist
	$typeField->column = 'item_type';
	$typeField->columntype = 'VARCHAR(200)';
	$typeField->typeofdata = 'V~M';
	$blockInfo->addField($typeField);
	$typeField->setPicklistValues(array('Product', 'Service'));
	println("✓ Field created: item_type (picklist)");
} else {
	println("Field exists: item_type");
}

// 3) price (currency)
$priceField = Vtiger_Field::getInstance('price', $module);
if (!$priceField) {
	$priceField = new Vtiger_Field();
	$priceField->name = 'price';
	$priceField->label = 'Price';
	$priceField->uitype = 71; // currency
	$priceField->column = 'price';
	$priceField->columntype = 'DECIMAL(25,8)';
	$priceField->typeofdata = 'N~O';
	$blockInfo->addField($priceField);
	println("✓ Field created: price");
} else {
	println("Field exists: price");
}

// 4) wholesale_price (currency)
$wholesaleField = Vtiger_Field::getInstance('wholesale_price', $module);
if (!$wholesaleField) {
	$wholesaleField = new Vtiger_Field();
	$wholesaleField->name = 'wholesale_price';
	$wholesaleField->label = 'Wholesale Price';
	$wholesaleField->uitype = 71; // currency
	$wholesaleField->column = 'wholesale_price';
	$wholesaleField->columntype = 'DECIMAL(25,8)';
	$wholesaleField->typeofdata = 'N~O';
	$blockInfo->addField($wholesaleField);
	println("✓ Field created: wholesale_price");
} else {
	println("Field exists: wholesale_price");
}

// 5) specification (textarea)
$specField = Vtiger_Field::getInstance('specification', $module);
if (!$specField) {
	$specField = new Vtiger_Field();
	$specField->name = 'specification';
	$specField->label = 'Specification';
	$specField->uitype = 19; // textarea
	$specField->column = 'specification';
	$specField->columntype = 'TEXT';
	$specField->typeofdata = 'V~O';
	$blockInfo->addField($specField);
	println("✓ Field created: specification");
} else {
	println("Field exists: specification");
}

// 6) warranty (text)
$warrantyField = Vtiger_Field::getInstance('warranty', $module);
if (!$warrantyField) {
	$warrantyField = new Vtiger_Field();
	$warrantyField->name = 'warranty';
	$warrantyField->label = 'Warranty';
	$warrantyField->uitype = 1; // text
	$warrantyField->column = 'warranty';
	$warrantyField->columntype = 'VARCHAR(255)';
	$warrantyField->typeofdata = 'V~O';
	$blockInfo->addField($warrantyField);
	println("✓ Field created: warranty");
} else {
	println("Field exists: warranty");
}

// 7) related_projects (reference to Project)
$projectField = Vtiger_Field::getInstance('related_projects', $module);
if (!$projectField) {
	$projectField = new Vtiger_Field();
	$projectField->name = 'related_projects';
	$projectField->label = 'Related Project';
	$projectField->uitype = 10; // reference
	$projectField->column = 'related_projects';
	$projectField->columntype = 'INT(11)';
	$projectField->typeofdata = 'V~O';
	$blockInfo->addField($projectField);
	$projectField->setRelatedModules(array('Project'));
	println("✓ Field created: related_projects (reference to Project)");
} else {
	println("Field exists: related_projects");
}

// Default list view (All)
try {
	$all = Vtiger_Filter::getInstance('All', $module);
	if (!$all) {
		$all = new Vtiger_Filter();
		$all->name = 'All';
		$all->isdefault = true;
		$module->addFilter($all);
		println("✓ Created default filter: All");
	}

	$columns = array($nameField, $typeField, $priceField, $wholesaleField, $warrantyField);
	$seq = 1;
	foreach ($columns as $f) {
		if ($f) {
			$all->addField($f, $seq);
			$seq++;
		}
	}
	$all->save();
	println("✓ Ensured list view columns on 'All'");
} catch (Exception $e) {
	println("! Could not configure default list view: " . $e->getMessage());
}

// Custom views: Product / Service (using advanced filter table directly)
try {
	global $adb;

	$createFilter = function ($name, $itemTypeValue) use ($module, $nameField, $typeField, $priceField, $wholesaleField, $warrantyField, $adb) {
		$existing = Vtiger_Filter::getInstance($name, $module);
		if ($existing) {
			println("Filter '$name' already exists (cvid={$existing->id})");
			return;
		}

		$filter = new Vtiger_Filter();
		$filter->name        = $name;
		$filter->isdefault   = 0;
		$filter->isfeatured  = 0;
		$filter->status      = 0;
		$filter->entitytype  = $module->name;
		$filter->description = $name;

		$module->addFilter($filter);
		println("Created filter '$name' (cvid={$filter->id})");

		$cols = array($nameField, $typeField, $priceField, $wholesaleField, $warrantyField);
		foreach ($cols as $f) {
			if ($f) {
				$filter->addField($f);
			}
		}

		if ($typeField) {
			$columnName = $typeField->table . ':' . $typeField->column . ':' . $typeField->name . ':' . $module->name;
			$adb->pquery(
				'INSERT INTO vtiger_cvadvfilter (cvid,columnindex,columnname,comparator,value,groupid,column_condition)
                 VALUES (?,?,?,?,?,?,?)',
				array(
					$filter->id,
					1,
					$columnName,
					'e',
					$itemTypeValue,
					1,
					''
				)
			);
			println("  Added condition item_type = '$itemTypeValue' to '$name'");
		}
	};

	$createFilter('Product', 'Product');
	$createFilter('Service', 'Service');
} catch (Exception $e) {
	println("! Could not create Product/Service filters: " . $e->getMessage());
}

// Relations (Related Lists) to keep compatibility without touching inventory core
$relatedTargets = array('Potentials', 'Quotes', 'SalesOrder', 'Invoice');
foreach ($relatedTargets as $target) {
	try {
		$targetModule = Vtiger_Module::getInstance($target);
		if ($targetModule) {
			$targetModule->setRelatedList($module, $moduleLabel, array('ADD', 'SELECT'), 'get_related_list');
			println("✓ Related list added: $target -> $moduleName");
		}
	} catch (Exception $e) {
		println("! Relation failed for $target: " . $e->getMessage());
	}
}

// Relation to Project
try {
	$projectModule = Vtiger_Module::getInstance('Project');
	if ($projectModule) {
		$projectModule->setRelatedList($module, $moduleLabel, array('ADD', 'SELECT'), 'get_related_list');
		println("✓ Related list added: Project -> $moduleName");
	}
} catch (Exception $e) {
	println("! Relation failed for Project: " . $e->getMessage());
}

// Register in vtiger_ws_entity so "Permission denied for name : ProductsServices" is avoided
try {
	require_once 'include/Webservices/Utils.php';
	vtws_addDefaultModuleTypeEntity($moduleName);
	println("✓ Registered $moduleName in vtiger_ws_entity (webservice).");
} catch (Exception $e) {
	println("! Webservice registration failed: " . $e->getMessage());
}

println("=== Done ===");

