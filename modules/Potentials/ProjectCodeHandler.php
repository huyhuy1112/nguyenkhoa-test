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

/**
 * Compact org segment for Opportunity / Project Code name.
 * Drops entity prefix (KH, TC, …) and leading zeros from account_no, then appends year index.
 * Example: TC00001 + 01 => 0101 | KH00012 + 02 => 1202
 */
function compactOrganizationIndex(string $accountNo, string $indexInYear): string
{
	$accountNo = trim($accountNo);
	$indexDigits = preg_replace('/\D/', '', $indexInYear);
	$indexInYear = str_pad($indexDigits === '' ? '1' : $indexDigits, 2, '0', STR_PAD_LEFT);

	$numeric = preg_replace('/^[A-Za-z]{2}/', '', $accountNo);
	if ($numeric === $accountNo) {
		$numeric = preg_replace('/^[A-Za-z]+/', '', $accountNo);
	}
	$numeric = ltrim((string) $numeric, '0');
	if ($numeric === '') {
		$numeric = '0';
	}
	$orgSeq = str_pad($numeric, 2, '0', STR_PAD_LEFT);

	return $orgSeq . $indexInYear;
}

class ProjectCodeHandler extends VTEventHandler {

	/**
	 * Build Project Code + sync Opportunity Name.
	 * Format: YYMMDD-{ORG_SEQ}{INDEX}-{COMPANY_CODE}-{PROJECT_NAME}
	 *
	 * @param int $recordId
	 * @param array $options force => regenerate even if cf_859 exists
	 * @return string|false Generated code or false on failure
	 */
	public static function generateForPotential($recordId, $options = array()) {
		global $log, $adb;

		$recordId = (int)$recordId;
		$force = !empty($options['force']);
		if ($recordId <= 0) {
			return false;
		}

		try {
			if (!$force) {
				$codeCheck = $adb->pquery(
					"SELECT cf_859 FROM vtiger_potentialscf WHERE potentialid = ?",
					array($recordId)
				);
				if ($adb->num_rows($codeCheck) > 0) {
					$existingCode = $adb->query_result($codeCheck, 0, 'cf_859');
					if (!empty($existingCode)) {
						return $existingCode;
					}
				}
			}

			$potentialResult = $adb->pquery(
				"SELECT p.potentialid, p.potentialname, p.related_to, ce.createdtime
				 FROM vtiger_potential p
				 INNER JOIN vtiger_crmentity ce ON ce.crmid = p.potentialid
				 WHERE p.potentialid = ? AND ce.deleted = 0",
				array($recordId)
			);

			if ($adb->num_rows($potentialResult) == 0) {
				return false;
			}

			$potentialRow = $adb->fetchByAssoc($potentialResult);
			$accountId = $potentialRow['related_to'];
			$createdTime = $potentialRow['createdtime'];

			if (empty($accountId) || $accountId == 0) {
				return false;
			}

			$createDate = '';
			$userPotentialName = trim(html_entity_decode((string)$potentialRow['potentialname'], ENT_QUOTES, 'UTF-8'));
			$userDatePrefix = null;
			if (preg_match('/^(\d{6})-/', $userPotentialName, $dateMatch)) {
				$userDatePrefix = $dateMatch[1];
			}
			try {
				if (!empty($createdTime)) {
					$dateObj = new DateTime($createdTime);
					$createDate = $dateObj->format('ymd');
				} else {
					$createDate = date('ymd');
				}
			} catch (Throwable $e) {
				$createDate = date('ymd');
			}
			if ($userDatePrefix !== null) {
				$createDate = $userDatePrefix;
			}

			$organizationWithIndex = '';
			$indexInYear = '01';
			try {
				$accountResult = $adb->pquery(
					"SELECT account_no FROM vtiger_account WHERE accountid = ?",
					array($accountId)
				);

				if ($adb->num_rows($accountResult) == 0) {
					return false;
				}

				$accountNo = $adb->query_result($accountResult, 0, 'account_no');
				if (empty($accountNo)) {
					return false;
				}

				$createdYear = '';
				if (!empty($createdTime)) {
					$dateObj = new DateTime($createdTime);
					$createdYear = $dateObj->format('Y');
				} else {
					$createdYear = date('Y');
				}

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
					$indexInYear = str_pad((int)$existingCount + 1, 2, '0', STR_PAD_LEFT);
				}

				$organizationWithIndex = compactOrganizationIndex($accountNo, $indexInYear);
			} catch (Throwable $e) {
				try {
					$accountResult = $adb->pquery(
						"SELECT account_no FROM vtiger_account WHERE accountid = ?",
						array($accountId)
					);
					if ($adb->num_rows($accountResult) > 0) {
						$accountNo = $adb->query_result($accountResult, 0, 'account_no');
						if (!empty($accountNo)) {
							$organizationWithIndex = compactOrganizationIndex($accountNo, '01');
						} else {
							return false;
						}
					} else {
						return false;
					}
				} catch (Throwable $e2) {
					return false;
				}
			}

			$companyCodeResult = $adb->pquery(
				"SELECT acf.cf_855, a.account_no
				 FROM vtiger_account a
				 LEFT JOIN vtiger_accountscf acf ON acf.accountid = a.accountid
				 WHERE a.accountid = ?",
				array($accountId)
			);

			if ($adb->num_rows($companyCodeResult) == 0) {
				return false;
			}

			$accountRow = $adb->fetchByAssoc($companyCodeResult);
			$companyCode = $accountRow['cf_855'];
			if (empty($companyCode)) {
				return false;
			}

			try {
				$companyCode = html_entity_decode($companyCode, ENT_QUOTES, 'UTF-8');
			} catch (Throwable $e) {
			}

			try {
				$companyCode = slugifyVietnamese($companyCode);
			} catch (Throwable $e) {
				$companyCode = strtolower(preg_replace('/[^a-z0-9]+/', '-', $companyCode));
				$companyCode = trim($companyCode, '-');
			}

			if (empty($companyCode)) {
				return false;
			}

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

			if (empty($rawProjectName)) {
				$rawProjectName = 'project-' . $recordId;
			}

			try {
				$rawProjectName = html_entity_decode($rawProjectName, ENT_QUOTES, 'UTF-8');
			} catch (Throwable $e) {
			}

			$projectName = trim($rawProjectName);
			if (empty($projectName)) {
				$projectName = 'project-' . $recordId;
			}

			$projectCode = "$createDate-$organizationWithIndex-$companyCode-$projectName";

			$checkRow = $adb->pquery(
				"SELECT potentialid FROM vtiger_potentialscf WHERE potentialid = ?",
				array($recordId)
			);

			if ($adb->num_rows($checkRow) == 0) {
				$adb->pquery(
					"INSERT INTO vtiger_potentialscf (potentialid) VALUES (?)",
					array($recordId)
				);
			}

			$adb->pquery(
				"UPDATE vtiger_potentialscf SET cf_859 = ? WHERE potentialid = ?",
				array($projectCode, $recordId)
			);
			if (!empty($projectName)) {
				$adb->pquery(
					"UPDATE vtiger_potentialscf SET cf_857 = ? WHERE potentialid = ? AND (cf_857 IS NULL OR cf_857 = '')",
					array($projectName, $recordId)
				);
			}

			$currentNameCheck = $adb->pquery(
				"SELECT potentialname FROM vtiger_potential WHERE potentialid = ?",
				array($recordId)
			);

			if ($adb->num_rows($currentNameCheck) > 0) {
				$currentName = $adb->query_result($currentNameCheck, 0, 'potentialname');
				if ($currentName !== $projectCode) {
					$adb->pquery(
						"UPDATE vtiger_potential SET potentialname = ? WHERE potentialid = ?",
						array($projectCode, $recordId)
					);
					$adb->pquery(
						'UPDATE vtiger_crmentity SET label = ? WHERE crmid = ?',
						array($projectCode, $recordId)
					);
				}
			}

			if ($log) {
				$log->debug("[ProjectCodeHandler] Generated Project Code: $projectCode for Opportunity ID: $recordId");
			}

			return $projectCode;
		} catch (Throwable $e) {
			if (isset($log) && $log) {
				$log->error("[ProjectCodeHandler] generateForPotential error: " . $e->getMessage());
			}
			return false;
		}
	}

	function handleEvent($eventName, $entityData) {
		global $log;

		try {
			if ($eventName !== 'vtiger.entity.aftersave') {
				return;
			}

			if ($entityData->getModuleName() !== 'Potentials') {
				return;
			}

			$recordId = $entityData->getId();
			if (empty($recordId)) {
				return;
			}

			if (!$entityData->isNew()) {
				if ($log) {
					$log->debug("[ProjectCodeHandler] Skipping - not a new record (ID: $recordId)");
				}
				return;
			}

			self::generateForPotential($recordId);
		} catch (Throwable $e) {
			if (isset($log) && $log) {
				$log->error("[ProjectCodeHandler] Fatal error prevented: " . $e->getMessage());
			}
		}
	}
}
