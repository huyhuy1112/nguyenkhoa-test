<?php
// One-time setup script to align HelpDesk (Tickets) with business spec.
// Run this from the vtiger root in browser or CLI: php add_helpdesk_ticket_features.php

chdir(__DIR__);
require_once 'config.inc.php';
require_once 'include/utils/utils.php';
require_once 'include/utils/VtlibUtils.php';
require_once 'vtlib/Vtiger/Module.php';
require_once 'vtlib/Vtiger/Block.php';
require_once 'vtlib/Vtiger/Field.php';

global $adb;

Vtiger_Utils::CheckTable('vtiger_troubletickets');

function ensureHelpDeskField($name, $label, $uitype, $typeofdata, $blockLabel, $picklistValues = []) {
    $module = Vtiger_Module::getInstance('HelpDesk');
    if (!$module) {
        echo "HelpDesk module not found.\n";
        return;
    }

    $field = Vtiger_Field::getInstance($name, $module);
    if ($field) {
        echo "Field $name already exists.\n";
        return;
    }

    $block = Vtiger_Block::getInstance($blockLabel, $module);
    if (!$block) {
        $block = new Vtiger_Block();
        $block->label = $blockLabel;
        $module->addBlock($block);
    }

    $field = new Vtiger_Field();
    $field->name       = $name;
    $field->label      = $label;
    $field->uitype     = $uitype;
    $field->column     = $name;
    $field->columntype = ($uitype == 5 || $uitype == 6 || $uitype == 23) ? 'DATE' : (($uitype == 70) ? 'DATETIME' : 'VARCHAR(255)');
    $field->typeofdata = $typeofdata;

    $block->addField($field);

    if ($uitype == 15 && !empty($picklistValues)) {
        $field->setPicklistValues($picklistValues);
    }

    echo "Created field $name ($label).\n";
}

// 1. Ticket Type: Project vs Non-Project
ensureHelpDeskField(
    'ticket_type',
    'Ticket Type',
    15,
    'V~O',
    'LBL_TICKET_INFORMATION',
    ['Project', 'Non-Project']
);

// 2. First Response Time
ensureHelpDeskField(
    'first_response_time',
    'First Response Time',
    70,
    'T~O',
    'LBL_TICKET_INFORMATION'
);

// 3. Resolved Time
ensureHelpDeskField(
    'resolved_time',
    'Resolved Time',
    70,
    'T~O',
    'LBL_TICKET_INFORMATION'
);

// 4. Closed Time
ensureHelpDeskField(
    'closed_time',
    'Closed Time',
    70,
    'T~O',
    'LBL_TICKET_INFORMATION'
);

echo "Done.\n";

