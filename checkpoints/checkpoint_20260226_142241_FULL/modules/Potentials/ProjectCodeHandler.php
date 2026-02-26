<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

require_once 'data/VTEntityDelta.php';

/**
 * Slugify Vietnamese text to ASCII-safe slug
 * ONE SOURCE OF TRUTH for Vietnamese character normalization
 * 
 * SAFETY: Normalizer is OPTIONAL - falls back to basic ASCII slug if intl extension is missing
 * NEVER throws exception - always returns valid string
 * 
 * @param string $string Input string (can contain Vietnamese characters)
 * @return string ASCII-safe slug
 */
function slugifyVietnamese(string $string): string
{
    // Safety: Ensure string is valid
    if (!is_string($string) || empty($string)) {
        return '';
    }

    try {
        // Check if Normalizer class is available (intl extension)
        if (class_exists('Normalizer')) {
            // 1. Normalize Unicode (critical) - only if Normalizer exists
            $normalized = Normalizer::normalize($string, Normalizer::FORM_D);
            if ($normalized !== false) {
                $string = $normalized;
            }

            // 2. Remove all combining marks (only works with proper Unicode normalization)
            $string = preg_replace('/\p{Mn}/u', '', $string);
        }
        // If Normalizer is NOT available, skip normalization and use fallback
    } catch (Throwable $e) {
        // Silent fallback - continue with basic slugify
        // Log error if logger is available
        global $log;
        if (isset($log)) {
            $log->error("[slugifyVietnamese] Normalizer error (falling back to basic slug): " . $e->getMessage());
        }
    }

    // 3. Vietnamese special character (always apply, regardless of Normalizer)
    $string = str_replace(['đ', 'Đ'], 'd', $string);

    // 4. Lowercase
    $string = strtolower($string);

    // 5. Replace non-alphanumeric characters with dash
    $string = preg_replace('/[^a-z0-9]+/', '-', $string);

    // 6. Trim extra dashes
    $result = trim($string, '-');
    
    // Safety: Ensure we always return a non-empty string
    return !empty($result) ? $result : 'project';
}

class ProjectCodeHandler extends VTEventHandler {

	function handleEvent($eventName, $entityData) {
		global $log, $adb;

		// CRITICAL: Catch ALL errors including fatal errors (Throwable includes Error and Exception)
		try {
			// STRICT: Handle ONLY vtiger.entity.aftersave (NOT final to avoid recursion)
			if ($eventName !== 'vtiger.entity.aftersave') {
				return;
			}

			$moduleName = $entityData->getModuleName();
			if ($moduleName !== 'Potentials') {
				return;
			}

			$recordId = $entityData->getId();
			if (empty($recordId)) {
				return;
			}

			// CRITICAL: Only process NEW records
			if (!$entityData->isNew()) {
				if ($log) {
					$log->debug("[ProjectCodeHandler] Skipping - not a new record (ID: $recordId)");
				}
				return;
			}

			// Check if Project Code already exists
			$codeCheck = $adb->pquery(
				"SELECT cf_859 FROM vtiger_potentialscf WHERE potentialid = ?",
				array($recordId)
			);
			
			if ($adb->num_rows($codeCheck) > 0) {
				$existingCode = $adb->query_result($codeCheck, 0, 'cf_859');
				if (!empty($existingCode)) {
					if ($log) {
						$log->debug("[ProjectCodeHandler] Project Code already exists: $existingCode (ID: $recordId)");
					}
					return; // Already generated, skip
				}
			}

			// Get Opportunity data
			$potentialResult = $adb->pquery(
			"SELECT p.potentialid, p.potentialname, p.related_to, ce.createdtime
				 FROM vtiger_potential p
				 INNER JOIN vtiger_crmentity ce ON ce.crmid = p.potentialid
				 WHERE p.potentialid = ?",
				array($recordId)
			);
			
			if ($adb->num_rows($potentialResult) == 0) {
				if ($log) {
					$log->debug("[ProjectCodeHandler] Opportunity not found (ID: $recordId)");
				}
				return;
			}
			
			$potentialRow = $adb->fetchByAssoc($potentialResult);
			$accountId = $potentialRow['related_to'];
			$createdTime = $potentialRow['createdtime'];
			
		// MANDATORY: Validate Organization (Account) - REQUIRED for Project Code generation
			if (empty($accountId) || $accountId == 0) {
				if ($log) {
				$log->error("[ProjectCodeHandler] No Organization (Account) linked - Project Code will NOT be generated (ID: $recordId). User must select an Organization when creating Opportunity.");
			}
			return; // Exit - Organization is mandatory
			}

		// 1. CREATE_DATE: Format createdtime as YYMMDD (2-digit year)
		$createDate = '';
		try {
			if (!empty($createdTime)) {
				$dateObj = new DateTime($createdTime);
				$createDate = $dateObj->format('ymd'); // y = 2-digit year (e.g., 26 for 2026)
			} else {
				$createDate = date('ymd'); // y = 2-digit year
			}
		} catch (Throwable $e) {
			// Fallback to current date if DateTime fails
			$createDate = date('ymd'); // y = 2-digit year
			if ($log) {
				$log->error("[ProjectCodeHandler] DateTime error (using current date): " . $e->getMessage());
			}
		}

		// 2. ORGANIZATION_NO and INDEX_IN_YEAR: Get account_no and calculate index per Organization per year
		// Format: {ACCOUNT_NO}{INDEX_IN_YEAR} (e.g., ACC101 = Organization ACC1, index 01)
		// CRITICAL: Use account_no directly from vtiger_account.account_no, DO NOT use accountid
		$organizationWithIndex = '';
		$indexInYear = '01'; // Default to 01 if calculation fails
		try {
			// Get account_no from Organization (Account) - this is the Organization Number
			$accountResult = $adb->pquery(
				"SELECT account_no FROM vtiger_account WHERE accountid = ?",
				array($accountId)
			);
			
			if ($adb->num_rows($accountResult) == 0) {
				if ($log) {
					$log->error("[ProjectCodeHandler] Organization (Account) not found (accountid: $accountId) - Project Code will NOT be generated (ID: $recordId)");
				}
				return; // Exit - Organization not found
			}
			
			$accountNo = $adb->query_result($accountResult, 0, 'account_no');
			if (empty($accountNo)) {
				if ($log) {
					$log->error("[ProjectCodeHandler] Organization account_no is empty (accountid: $accountId) - Project Code will NOT be generated (ID: $recordId)");
				}
				return; // Exit - account_no is required
			}

			// Get year from createdtime for INDEX_IN_YEAR calculation
			$createdYear = '';
			if (!empty($createdTime)) {
				$dateObj = new DateTime($createdTime);
				$createdYear = $dateObj->format('Y');
			} else {
				$createdYear = date('Y');
			}

			// Calculate INDEX_IN_YEAR: Count existing Opportunities for the SAME Organization in the SAME year
			// Exclude current record (recordId) and deleted records
			$indexQuery = $adb->pquery(
				"SELECT COUNT(*) as index_count
				 FROM vtiger_potential p
				 INNER JOIN vtiger_crmentity e ON e.crmid = p.potentialid
				 WHERE p.related_to = ?
				 AND YEAR(e.createdtime) = ?
				 AND e.deleted = 0
				 AND p.potentialid != ?",
				array($accountId, $createdYear, $recordId)
			);

			if ($adb->num_rows($indexQuery) > 0) {
				$existingCount = $adb->query_result($indexQuery, 0, 'index_count');
				$indexNumber = intval($existingCount) + 1; // Add 1 for current record
				$indexInYear = str_pad($indexNumber, 2, '0', STR_PAD_LEFT); // Pad to 2 digits (01, 02, 03...)
			}

			// CRITICAL: Use account_no directly - DO NOT extract digits, DO NOT prefix ORG, DO NOT use accountid
			// Format: {ACCOUNT_NO}{INDEX_IN_YEAR} (e.g., "ACC101" if account_no is "ACC1" and index is "01")
			$organizationWithIndex = "{$accountNo}{$indexInYear}";

			if ($log) {
				$log->debug("[ProjectCodeHandler] Organization with index: $organizationWithIndex (from account_no: $accountNo, index: $indexInYear, Year: $createdYear, Organization ID: $accountId)");
			}
		} catch (Throwable $e) {
			// If calculation fails, use default
			if ($log) {
				$log->error("[ProjectCodeHandler] Organization/Index calculation error (using default): " . $e->getMessage());
			}
			// Try to get at least account_no for fallback
			try {
				$accountResult = $adb->pquery(
					"SELECT account_no FROM vtiger_account WHERE accountid = ?",
					array($accountId)
				);
				if ($adb->num_rows($accountResult) > 0) {
					$accountNo = $adb->query_result($accountResult, 0, 'account_no');
					if (!empty($accountNo)) {
						$organizationWithIndex = "{$accountNo}01"; // Use account_no directly with default index 01
					} else {
						return; // Cannot proceed without account_no
					}
				} else {
					return; // Cannot proceed without account_no
				}
			} catch (Throwable $e2) {
				if ($log) {
					$log->error("[ProjectCodeHandler] Fallback account_no retrieval failed: " . $e2->getMessage());
				}
				return; // Cannot proceed
			}
		}

		// 3. COMPANY_CODE: Get from Account's cf_855 (Organization level - single source of truth)
		// CRITICAL: Company Code MUST come from Account, NOT from Opportunity
		$companyCodeResult = $adb->pquery(
			"SELECT acf.cf_855, a.account_no
				 FROM vtiger_account a
				 LEFT JOIN vtiger_accountscf acf ON acf.accountid = a.accountid
				 WHERE a.accountid = ?",
				array($accountId)
			);
			
		if ($adb->num_rows($companyCodeResult) == 0) {
				if ($log) {
				$log->error("[ProjectCodeHandler] Account not found (accountid: $accountId) - Project Code will NOT be generated (ID: $recordId)");
			}
			return; // Exit - Account not found
		}
		
		$accountRow = $adb->fetchByAssoc($companyCodeResult);
			$companyCode = $accountRow['cf_855'];
		
		// MANDATORY RULE: If Account's Company Code is empty → DO NOT generate Project Code
			if (empty($companyCode)) {
			if ($log) {
				$log->error("[ProjectCodeHandler] Account (ID: $accountId) has no Company Code (cf_855 is empty) - Project Code will NOT be generated for Opportunity (ID: $recordId)");
			}
			return; // Exit - Company Code is required at Account level
		}
		
		// CRITICAL: Decode HTML entities before slugify
		// Vtiger's query_result() and fetchByAssoc() apply to_html() which encodes UTF-8 to HTML entities
		// Example: "chế" → "ch&eacute;" - we need raw UTF-8 for proper Unicode normalization
		try {
			$companyCode = html_entity_decode($companyCode, ENT_QUOTES, 'UTF-8');
		} catch (Throwable $e) {
			// If decode fails, continue with original (might already be UTF-8)
			if ($log) {
				$log->error("[ProjectCodeHandler] html_entity_decode error for Company Code: " . $e->getMessage());
			}
		}
		
		// Sanitize company code using slugifyVietnamese() - ONE SOURCE OF TRUTH
		// Safe: slugifyVietnamese() never throws, always returns valid string
		try {
			$companyCode = slugifyVietnamese($companyCode);
		} catch (Throwable $e) {
			// Extra safety: if slugify fails, use basic sanitization
			$companyCode = strtolower(preg_replace('/[^a-z0-9]+/', '-', $companyCode));
			$companyCode = trim($companyCode, '-');
			if ($log) {
				$log->error("[ProjectCodeHandler] slugifyVietnamese error for Company Code (using fallback): " . $e->getMessage());
			}
		}
		
			if (empty($companyCode)) {
				if ($log) {
				$log->error("[ProjectCodeHandler] Account's Company Code (cf_855) is empty after sanitization - Project Code will NOT be generated (ID: $recordId)");
			}
			return; // Exit if sanitization resulted in empty
		}
		
		if ($log) {
			$log->debug("[ProjectCodeHandler] Company Code resolved from Account (ID: $accountId): $companyCode (Opportunity ID: $recordId)");
			}

			// 4. PROJECT_NAME: Get from vtiger_potentialscf.cf_857 or fallback to potentialname
		$rawProjectName = '';
			$projectNameResult = $adb->pquery(
				"SELECT cf_857 FROM vtiger_potentialscf WHERE potentialid = ?",
				array($recordId)
			);
			
			if ($adb->num_rows($projectNameResult) > 0) {
			$rawProjectName = $adb->query_result($projectNameResult, 0, 'cf_857');
		}
		
		if (empty($rawProjectName)) {
			$rawProjectName = $potentialRow['potentialname'];
		}
		
		// MANDATORY: Always generate code, even if project name is empty
		if (empty($rawProjectName)) {
			$rawProjectName = 'project-' . $recordId; // Fallback to record ID
			if ($log) {
				$log->debug("[ProjectCodeHandler] Project name is empty, using fallback: $rawProjectName (ID: $recordId)");
			}
		}

		// CRITICAL: Decode HTML entities to get raw UTF-8
		// Vtiger's query_result() and fetchByAssoc() apply to_html() which encodes UTF-8 to HTML entities
		// Example: "chế lá cà" → "ch&eacute; l&aacute; c&agrave;" - we need raw UTF-8
		try {
			$rawProjectName = html_entity_decode($rawProjectName, ENT_QUOTES, 'UTF-8');
		} catch (Throwable $e) {
			// If decode fails, continue with original (might already be UTF-8)
			if ($log) {
				$log->error("[ProjectCodeHandler] html_entity_decode error for Project Name: " . $e->getMessage());
			}
		}

		// REQUIREMENT: Keep original Vietnamese Project Name (UTF-8)
		// DO NOT slugify, DO NOT remove accents, DO NOT transform characters
		// Project Code is now for display, not URL usage
		$projectName = trim($rawProjectName);
		
		// Ensure project name is not empty
			if (empty($projectName)) {
			$projectName = 'project-' . $recordId;
				if ($log) {
				$log->debug("[ProjectCodeHandler] Project name is empty, using fallback: $projectName (ID: $recordId)");
			}
		}

		// Generate Project Code: {CREATE_DATE}-{ACCOUNT_NO}{INDEX_IN_YEAR}-{COMPANY_CODE}-{PROJECT_NAME}
		// Format: YYMMDD-{ACCOUNT_NO}{INDEX_IN_YEAR}-{COMPANY_CODE}-{PROJECT_NAME}
		// Example: 260108-ACC406-abc-cầu lông
		// CRITICAL: account_no comes directly from vtiger_account.account_no, NOT from accountid
		$projectCode = "$createDate-$organizationWithIndex-$companyCode-$projectName";

			// Update directly in database (no save() to avoid recursion)
		// SAFETY: Wrap database operations in try/catch
		try {
			// First ensure row exists in vtiger_potentialscf
			$checkRow = $adb->pquery(
				"SELECT potentialid FROM vtiger_potentialscf WHERE potentialid = ?",
				array($recordId)
			);
			
			if ($adb->num_rows($checkRow) == 0) {
				// Insert row if doesn't exist
				$adb->pquery(
					"INSERT INTO vtiger_potentialscf (potentialid) VALUES (?)",
					array($recordId)
				);
			}
			
			// Update Project Code
			$adb->pquery(
				"UPDATE vtiger_potentialscf SET cf_859 = ? WHERE potentialid = ?",
				array($projectCode, $recordId)
			);

			// SYNC Opportunity Name with Project Code
			// CRITICAL: Update potentialname directly using SQL to avoid recursion
			// Since we're in aftersave event, direct SQL update will NOT trigger another save event
			// Check if potentialname already equals projectCode to avoid unnecessary update
			$currentNameCheck = $adb->pquery(
				"SELECT potentialname FROM vtiger_potential WHERE potentialid = ?",
				array($recordId)
			);
			
			if ($adb->num_rows($currentNameCheck) > 0) {
				$currentName = $adb->query_result($currentNameCheck, 0, 'potentialname');
				// Only update if different (avoid unnecessary database writes)
				if ($currentName !== $projectCode) {
					$adb->pquery(
						"UPDATE vtiger_potential SET potentialname = ? WHERE potentialid = ?",
						array($projectCode, $recordId)
					);
					
					if ($log) {
						$log->debug("[ProjectCodeHandler] Synchronized Opportunity Name with Project Code: $projectCode (ID: $recordId)");
					}
				}
			}
		} catch (Throwable $e) {
			// Database error - log but don't break save flow
			if ($log) {
				$log->error("[ProjectCodeHandler] Database update error: " . $e->getMessage() . " (ID: $recordId)");
			}
			// Silent return - save flow continues
			return;
		}

		if ($log) {
			$log->debug("[ProjectCodeHandler] Generated Project Code: $projectCode for Opportunity ID: $recordId (Organization ID: $accountId, Index: $indexInYear)");
		}

		} catch (Throwable $e) {
			// CRITICAL: Catch ALL errors (Error, Exception, etc.) to prevent white screen
			// NEVER break Vtiger save flow - silent failure with logging
			if (isset($log) && $log) {
				$log->error("[ProjectCodeHandler] Fatal error prevented: " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine());
			}
			// Silent return - do NOT throw, do NOT exit, do NOT die
			// This ensures Vtiger save flow continues normally
			return;
		}
	}
}
