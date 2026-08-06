<?php
/*+***********************************************************************************
 * Quotes SaveAjax: header fields + optional line comments + manual grand total.
 *************************************************************************************/

class Quotes_SaveAjax_Action extends Inventory_SaveAjax_Action {

	public function process(Vtiger_Request $request) {
		$fieldToBeSaved = $request->get('field');
		$response = new Vtiger_Response();
		try {
			vglobal('VTIGER_TIMESTAMP_NO_CHANGE_MODE', $request->get('_timeStampNoChangeMode', false));
			$recordModel = $this->saveRecord($request);
			$this->saveInlineLineExtras($request, $recordModel);
			vglobal('VTIGER_TIMESTAMP_NO_CHANGE_MODE', false);

			$fieldModelList = $recordModel->getModule()->getFields();
			$result = array();
			$picklistColorMap = array();
			foreach ($fieldModelList as $fieldName => $fieldModel) {
				if (!$fieldModel->isViewable()) {
					continue;
				}
				$recordFieldValue = $recordModel->get($fieldName);
				if (is_array($recordFieldValue) && $fieldModel->getFieldDataType() == 'multipicklist') {
					foreach ($recordFieldValue as $picklistValue) {
						$picklistColorMap[$picklistValue] = Settings_Picklist_Module_Model::getPicklistColorByValue($fieldName, $picklistValue);
					}
					$recordFieldValue = implode(' |##| ', $recordFieldValue);
				}
				if ($fieldModel->getFieldDataType() == 'picklist') {
					$picklistColorMap[$recordFieldValue] = Settings_Picklist_Module_Model::getPicklistColorByValue($fieldName, $recordFieldValue);
				}
				$fieldValue = $displayValue = Vtiger_Util_Helper::toSafeHTML($recordFieldValue);
				if ($fieldModel->getFieldDataType() !== 'currency' && $fieldModel->getFieldDataType() !== 'datetime' && $fieldModel->getFieldDataType() !== 'date' && $fieldModel->getFieldDataType() !== 'double') {
					$displayValue = $fieldModel->getDisplayValue($fieldValue, $recordModel->getId());
				}
				if ($fieldModel->getFieldDataType() == 'currency') {
					$displayValue = Vtiger_Currency_UIType::transformDisplayValue(Vtiger_Currency_UIType::convertToDBFormat($fieldValue));
				}
				if (!empty($picklistColorMap) && ($fieldModel->getFieldDataType() == 'picklist' || $fieldModel->getFieldDataType() == 'multipicklist')) {
					$result[$fieldName] = array('value' => $fieldValue, 'display_value' => $displayValue, 'colormap' => $picklistColorMap);
				} else {
					$result[$fieldName] = array('value' => $fieldValue, 'display_value' => $displayValue);
				}
			}

			$result['_recordLabel'] = decode_html($recordModel->getName());
			$result['_recordId'] = $recordModel->getId();
			if ($request->has('hdnGrandTotal_manual') || $request->has('grand_total')) {
				$grand = $this->parseMoney($request->get('hdnGrandTotal_manual'));
				if ($grand <= 0 && $request->has('grand_total')) {
					$grand = $this->parseMoney($request->get('grand_total'));
				}
				$result['hdnGrandTotal'] = array(
					'value' => $grand,
					'display_value' => number_format($grand, 0, ',', '.'),
				);
			}
			$response->setEmitType(Vtiger_Response::$EMIT_JSON);
			$response->setResult($result);
		} catch (DuplicateException $e) {
			$response->setError($e->getMessage(), $e->getDuplicationMessage(), $e->getMessage());
		} catch (Exception $e) {
			$response->setError($e->getMessage());
		}
		$response->emit();
	}

	/**
	 * Persist per-line comments + manual grand total (via adjustment) without reloading inventory lines.
	 */
	protected function saveInlineLineExtras(Vtiger_Request $request, Vtiger_Record_Model $recordModel) {
		$recordId = (int) $recordModel->getId();
		if ($recordId <= 0) {
			return;
		}
		$db = PearDatabase::getInstance();

		$comments = $request->get('line_comments');
		if (!is_array($comments) || empty($comments)) {
			// jQuery may send line_comments[1]=... or JSON blob
			$rawJson = $request->get('line_comments_json');
			if (is_string($rawJson) && $rawJson !== '') {
				$decoded = json_decode($rawJson, true);
				if (is_array($decoded)) {
					$comments = $decoded;
				}
			}
		}
		if (!is_array($comments)) {
			// Parse flat keys line_comments_1 etc.
			$comments = array();
			$rawAll = $_REQUEST;
			foreach ($rawAll as $key => $val) {
				if (preg_match('/^line_comments\[(\d+)\]$/', (string) $key, $m)
					|| preg_match('/^line_comments_(\d+)$/', (string) $key, $m)) {
					$comments[(int) $m[1]] = $val;
				}
			}
		}
		if (is_array($comments)) {
			foreach ($comments as $seq => $text) {
				$seqNo = (int) $seq;
				if ($seqNo <= 0) {
					continue;
				}
				$comment = is_string($text) ? $text : (string) $text;
				if (function_exists('decode_html')) {
					$comment = decode_html($comment);
				}
				$db->pquery(
					'UPDATE vtiger_inventoryproductrel SET comment = ? WHERE id = ? AND sequence_no = ?',
					array($comment, $recordId, $seqNo)
				);
			}
		}

		$hasManual = $request->has('hdnGrandTotal_manual') || $request->has('grand_total');
		if (!$hasManual) {
			return;
		}
		$grand = $this->parseMoney($request->get('hdnGrandTotal_manual'));
		if ($grand <= 0 && $request->has('grand_total')) {
			$grand = $this->parseMoney($request->get('grand_total'));
		}
		if ($grand < 0) {
			$grand = 0;
		}

		$rs = $db->pquery(
			'SELECT subtotal, total, adjustment, discount_amount, discount_percent, s_h_amount
			 FROM vtiger_quotes WHERE quoteid = ?',
			array($recordId)
		);
		if (!$rs || !$db->num_rows($rs)) {
			return;
		}
		$subtotal = (float) $db->query_result($rs, 0, 'subtotal');
		$discountAmount = (float) $db->query_result($rs, 0, 'discount_amount');
		$discountPercent = (float) $db->query_result($rs, 0, 'discount_percent');
		$shipping = (float) $db->query_result($rs, 0, 's_h_amount');
		$discount = $discountAmount;
		if ($discount <= 0 && $discountPercent > 0 && $subtotal > 0) {
			$discount = $subtotal * $discountPercent / 100.0;
		}
		// VAT-included flow: tax not re-added; adjustment = grand − (subtotal − discount + shipping)
		$base = $subtotal - $discount + $shipping;
		$adjustment = $grand - $base;

		$db->pquery(
			'UPDATE vtiger_quotes SET total = ?, adjustment = ?, pre_tax_total = ? WHERE quoteid = ?',
			array($grand, $adjustment, $subtotal, $recordId)
		);
	}

	protected function parseMoney($value) {
		if (is_numeric($value)) {
			return (float) $value;
		}
		$s = trim((string) $value);
		if ($s === '') {
			return 0.0;
		}
		// "7.568.640" or "7,568,640.50"
		if (preg_match('/^\d{1,3}(\.\d{3})+(,\d+)?$/', $s)) {
			$s = str_replace('.', '', $s);
			$s = str_replace(',', '.', $s);
		} else {
			$s = str_replace(array(' ', ','), array('', ''), $s);
			$s = preg_replace('/[^\d.\-]/', '', $s);
		}
		return (float) $s;
	}
}
