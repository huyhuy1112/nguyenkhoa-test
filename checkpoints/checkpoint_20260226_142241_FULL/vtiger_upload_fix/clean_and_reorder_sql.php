<?php
/**
 * Clean and Reorder SQL Dump for phpMyAdmin Import
 * 
 * Requirements:
 * 1. Remove all non-SQL lines (warnings, comments)
 * 2. Ensure vtiger_crmentity is created first
 * 3. Add SET FOREIGN_KEY_CHECKS=0 at beginning
 * 4. Add SET FOREIGN_KEY_CHECKS=1 at end
 * 5. Preserve all valid SQL statements
 */

$input_file = isset($argv[1]) ? $argv[1] : 'backup/db/vtiger_prod_pure.sql';
$output_file = isset($argv[2]) ? $argv[2] : 'backup/db/vtiger_prod_final.sql';

echo "==========================================\n";
echo "Clean and Reorder SQL Dump\n";
echo "==========================================\n\n";

if (!file_exists($input_file)) {
    die("ERROR: Input file not found: $input_file\n");
}

echo "Input file: $input_file\n";
echo "Output file: $output_file\n\n";

// Read the entire file
$content = file_get_contents($input_file);
$lines = explode("\n", $content);

echo "Processing " . count($lines) . " lines...\n\n";

// Step 1: Remove unwanted lines
echo "Step 1: Removing non-SQL lines...\n";
$cleaned_lines = array();
$removed_count = 0;

foreach ($lines as $line) {
    $trimmed = trim($line);
    
    // Skip empty lines
    if (empty($trimmed)) {
        continue;
    }
    
    // Skip mysqldump warnings
    if (preg_match('/^mysqldump:/i', $trimmed)) {
        $removed_count++;
        continue;
    }
    
    // Skip warning messages
    if (preg_match('/\[Warning\]/i', $trimmed)) {
        $removed_count++;
        continue;
    }
    
    // Skip MySQL dump headers
    if (preg_match('/^-- MySQL dump/i', $trimmed)) {
        $removed_count++;
        continue;
    }
    
    // Skip Host/Server version comments
    if (preg_match('/^-- (Host|Server version|Dump completed|Current Database)/i', $trimmed)) {
        $removed_count++;
        continue;
    }
    
    // Skip separator lines
    if (preg_match('/^--[-\s]+$/', $trimmed)) {
        $removed_count++;
        continue;
    }
    
    // Skip lines with only dashes
    if (preg_match('/^[-]+$/', $trimmed)) {
        $removed_count++;
        continue;
    }
    
    // Keep all other lines
    $cleaned_lines[] = $line;
}

echo "  Removed $removed_count non-SQL lines\n";
echo "  Kept " . count($cleaned_lines) . " lines\n\n";

// Step 2: Extract CREATE TABLE statements and find vtiger_crmentity
echo "Step 2: Extracting CREATE TABLE statements...\n";

$create_tables = array();
$insert_statements = array();
$other_statements = array();
$current_statement = '';
$in_create = false;
$in_insert = false;
$table_name = '';

foreach ($cleaned_lines as $line_num => $line) {
    // Check for CREATE TABLE
    if (preg_match('/^CREATE TABLE\s+`?(\w+)`?/i', $line, $matches)) {
        // Save previous statement if exists
        if (!empty($current_statement)) {
            if ($in_create && !empty($table_name)) {
                $create_tables[$table_name] = trim($current_statement);
            } elseif ($in_insert) {
                $insert_statements[] = trim($current_statement);
            } else {
                $other_statements[] = trim($current_statement);
            }
        }
        
        // Start new CREATE TABLE
        $table_name = $matches[1];
        $current_statement = $line;
        $in_create = true;
        $in_insert = false;
        continue;
    }
    
    // Check for INSERT INTO
    if (preg_match('/^INSERT INTO/i', $line)) {
        // Save previous statement
        if (!empty($current_statement)) {
            if ($in_create && !empty($table_name)) {
                $create_tables[$table_name] = trim($current_statement);
            } elseif ($in_insert) {
                $insert_statements[] = trim($current_statement);
            } else {
                $other_statements[] = trim($current_statement);
            }
        }
        
        // Start new INSERT
        $current_statement = $line;
        $in_create = false;
        $in_insert = true;
        $table_name = '';
        continue;
    }
    
    // Check if statement ends (semicolon at end of line)
    if (preg_match('/;\s*$/', $line)) {
        $current_statement .= "\n" . $line;
        
        // Save statement
        if ($in_create && !empty($table_name)) {
            $create_tables[$table_name] = trim($current_statement);
            $in_create = false;
            $table_name = '';
        } elseif ($in_insert) {
            $insert_statements[] = trim($current_statement);
            $in_insert = false;
        } else {
            $other_statements[] = trim($current_statement);
        }
        
        $current_statement = '';
        continue;
    }
    
    // Continue building current statement
    if (!empty($current_statement) || !empty(trim($line))) {
        $current_statement .= "\n" . $line;
    }
}

// Save last statement if exists
if (!empty($current_statement)) {
    if ($in_create && !empty($table_name)) {
        $create_tables[$table_name] = trim($current_statement);
    } elseif ($in_insert) {
        $insert_statements[] = trim($current_statement);
    } else {
        $other_statements[] = trim($current_statement);
    }
}

echo "  Found " . count($create_tables) . " CREATE TABLE statements\n";
echo "  Found " . count($insert_statements) . " INSERT statements\n";
echo "  Found " . count($other_statements) . " other statements\n\n";

// Step 3: Reorder - put vtiger_crmentity first
echo "Step 3: Reordering tables (vtiger_crmentity first)...\n";

$ordered_tables = array();
$crmentity_found = false;

// Check if vtiger_crmentity exists
if (isset($create_tables['vtiger_crmentity'])) {
    $ordered_tables['vtiger_crmentity'] = $create_tables['vtiger_crmentity'];
    unset($create_tables['vtiger_crmentity']);
    $crmentity_found = true;
    echo "  ✓ vtiger_crmentity found and moved to first position\n";
} else {
    echo "  ⚠ vtiger_crmentity not found in CREATE TABLE statements\n";
}

// Add all other tables in original order (preserve order from file)
foreach ($create_tables as $table => $statement) {
    $ordered_tables[$table] = $statement;
}

echo "  Total tables to create: " . count($ordered_tables) . "\n\n";

// Step 4: Build final SQL
echo "Step 4: Building final SQL file...\n";

$final_sql = array();

// Add FOREIGN_KEY_CHECKS=0 at the beginning
$final_sql[] = "SET FOREIGN_KEY_CHECKS=0;";
$final_sql[] = "";

// Add all CREATE TABLE statements (vtiger_crmentity first)
foreach ($ordered_tables as $table => $statement) {
    $final_sql[] = $statement;
    $final_sql[] = "";
}

// Add all INSERT statements (preserve original order)
foreach ($insert_statements as $statement) {
    $final_sql[] = $statement;
    $final_sql[] = "";
}

// Add other statements (SET statements, etc.)
foreach ($other_statements as $statement) {
    // Skip if it's a SET statement we don't want
    if (preg_match('/SET @saved_cs_client|SET character_set_client = @saved_cs_client/i', $statement)) {
        continue;
    }
    $final_sql[] = $statement;
    $final_sql[] = "";
}

// Add FOREIGN_KEY_CHECKS=1 at the end
$final_sql[] = "SET FOREIGN_KEY_CHECKS=1;";

// Write to file
$output_content = implode("\n", $final_sql);
file_put_contents($output_file, $output_content);

echo "  ✓ Final SQL file created\n\n";

// Step 5: Verification
echo "Step 5: Verifying output...\n";

$output_lines = explode("\n", $output_content);
$output_size = strlen($output_content);

echo "  File size: " . number_format($output_size / 1024, 2) . " KB\n";
echo "  Total lines: " . count($output_lines) . "\n";

// Check first line
$first_line = trim($output_lines[0]);
if ($first_line === 'SET FOREIGN_KEY_CHECKS=0;') {
    echo "  ✓ Starts with SET FOREIGN_KEY_CHECKS=0;\n";
} else {
    echo "  ✗ First line incorrect: $first_line\n";
}

// Check last line
$last_line = trim(end($output_lines));
if ($last_line === 'SET FOREIGN_KEY_CHECKS=1;') {
    echo "  ✓ Ends with SET FOREIGN_KEY_CHECKS=1;\n";
} else {
    echo "  ✗ Last line incorrect: $last_line\n";
}

// Check vtiger_crmentity position
$crmentity_pos = -1;
foreach ($output_lines as $idx => $line) {
    if (preg_match('/^CREATE TABLE\s+`?vtiger_crmentity`?/i', $line)) {
        $crmentity_pos = $idx;
        break;
    }
}

if ($crmentity_pos > 0 && $crmentity_pos < 10) {
    echo "  ✓ vtiger_crmentity is near the beginning (line " . ($crmentity_pos + 1) . ")\n";
} elseif ($crmentity_pos >= 0) {
    echo "  ⚠ vtiger_crmentity found at line " . ($crmentity_pos + 1) . "\n";
} else {
    echo "  ✗ vtiger_crmentity not found!\n";
}

// Count statements
$create_count = preg_match_all('/^CREATE TABLE/i', $output_content, $matches);
$insert_count = preg_match_all('/^INSERT INTO/i', $output_content, $matches);
$warning_count = preg_match_all('/mysqldump:|\[Warning\]/i', $output_content, $matches);

echo "  CREATE TABLE statements: $create_count\n";
echo "  INSERT INTO statements: $insert_count\n";
echo "  Warning messages: $warning_count\n\n";

echo "==========================================\n";
echo "✓ Clean and reordered SQL file created!\n";
echo "==========================================\n\n";
echo "Output file: $output_file\n";
echo "Ready for phpMyAdmin import!\n\n";

