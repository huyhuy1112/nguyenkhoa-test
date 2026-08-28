<?php
/*+***********************************************************************************
 * SalesOrder SaveAjax: paid/balance + optional line comments + manual grand total.
 *************************************************************************************/

class SalesOrder_SaveAjax_Action extends Inventory_SaveAjax_Action {

	public function process(Vtiger_Request $request) {
		$response = new Vtiger_Response();
		try {
			vglobal('VTIGER_TIMESTAMP_NO_CHANGE_MODE', $request->get('_timeStampNoChangeMode', false));
			$recordModel = $this->saveRecord($request);
			$this->saveInlineLineExtras($request, $recordModel);
			vglobal('VTIGER_TIMESTAMP_NO_CHANGE_MODE', false);

			// Refresh for response display
			$recordModel = Inventory_Record_Model::getInstanceById($recordModel->getId(), 'SalesOrder');

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
	 * Function to get the record model based on the request parameters
	 * @param Vtiger_Request $request
	 * @return Vtiger_Record_Model or Module specific Record Model instance
	 */
	public function getRecordModelFromRequest(Vtiger_Request $request) {
		$recordModel = parent::getRecordModelFromRequest($request);
		$this->syncReceivedBalance($recordModel, $request);
		// Store notes as plain UTF-8 (never as entities like &ecirc;)
		if ($request->has('mk_list_note')) {
			$recordModel->set('mk_list_note', $this->cleanPlainText($request->get('mk_list_note')));
		}
		if ($request->has('description') && !$request->has('mk_list_note')) {
			// only if description is used as list note slot
		}
		return $recordModel;
	}

	/**
	 * Keep balance = total - received when customer paid amount is updated inline.
	 */
	protected function syncReceivedBalance(Vtiger_Record_Model $recordModel, Vtiger_Request $request) {
		$paidField = '';
		foreach (array('received', 'paid_amount', 'amount_paid', 'paid', 'mk_customer_paid') as $candidate) {
			if ($request->has($candidate)) {
				$paidField = $candidate;
				break;
			}
		}
		if ($paidField === '') {
			return;
		}

		$paidRaw = $request->get($paidField);
		if (class_exists('CurrencyField')) {
			$paidAmount = (float) CurrencyField::convertToDBFormat($paidRaw, null, true);
		} else {
			$paidAmount = $this->parseMoney($paidRaw);
		}
		if ($paidAmount < 0) {
			$paidAmount = 0;
		}
		$recordModel->set($paidField, $paidAmount);

		$total = (float) $recordModel->get('total');
		if ($request->has('hdnGrandTotal_manual') || $request->has('grand_total')) {
			$manual = $this->parseMoney($request->get('hdnGrandTotal_manual'));
			if ($manual <= 0 && $request->has('grand_total')) {
				$manual = $this->parseMoney($request->get('grand_total'));
			}
			if ($manual >= 0) {
				$total = $manual;
				$recordModel->set('total', $manual);
			}
		}
		if ($total <= 0 && $recordModel->get('hdnGrandTotal') !== null && $recordModel->get('hdnGrandTotal') !== '') {
			$total = (float) $recordModel->get('hdnGrandTotal');
		}
		$balance = $total - $paidAmount;
		if ($balance < 0) {
			$balance = 0;
		}
		$balanceField = $recordModel->getModule()->getField('balance');
		if ($balanceField) {
			$recordModel->set('balance', $balance);
		}
	}

	/**
	 * Collect line comments from SaveAjax payload.
	 * Note: Vtiger_Request::get() auto-decodes JSON strings that start with "{" / "["
	 * into PHP arrays — callers that only check is_string() will drop the notes.
	 *
	 * @return array<int|string,string> map of sequence/index => comment text
	 */
	protected function extractInlineLineComments(Vtiger_Request $request) {
		$comments = array();

		$fromReq = $request->get('line_comments');
		if (is_array($fromReq) && !empty($fromReq)) {
			$comments = $fromReq;
		}

		// Prefer line_comments_json (array after Request auto-decode, or raw string)
		if (empty($comments)) {
			$rawJson = $request->get('line_comments_json');
			if (is_array($rawJson)) {
				$comments = $rawJson;
			} elseif (is_string($rawJson) && $rawJson !== '') {
				$decoded = json_decode($rawJson, true);
				if (!is_array($decoded) && function_exists('Zend_Json')) {
					try {
						$decoded = Zend_Json::decode($rawJson);
					} catch (Exception $e) {
						$decoded = null;
					}
				}
				if (is_array($decoded)) {
					$comments = $decoded;
				}
			}
		}

		// Bypass Request if it stripped payload: read raw REQUEST / POST
		if (empty($comments)) {
			$rawSources = array($_REQUEST, isset($_POST) ? $_POST : array());
			foreach ($rawSources as $bucket) {
				if (!is_array($bucket) || empty($bucket['line_comments_json'])) {
					continue;
				}
				$raw = $bucket['line_comments_json'];
				if (is_array($raw)) {
					$comments = $raw;
					break;
				}
				if (is_string($raw) && $raw !== '') {
					$decoded = json_decode(html_entity_decode($raw, ENT_QUOTES, 'UTF-8'), true);
					if (is_array($decoded)) {
						$comments = $decoded;
						break;
					}
				}
			}
		}

		// Flat keys: line_comment_1, line_comments_1, line_comments[1]
		foreach (array($_REQUEST, isset($_POST) ? $_POST : array()) as $bucket) {
			if (!is_array($bucket)) {
				continue;
			}
			foreach ($bucket as $key => $val) {
				if (preg_match('/^line_comments\[(\d+)\]$/', (string) $key, $m)
					|| preg_match('/^line_comments_(\d+)$/', (string) $key, $m)
					|| preg_match('/^line_comment_(\d+)$/', (string) $key, $m)) {
					$comments[(int) $m[1]] = $val;
				}
			}
		}

		return is_array($comments) ? $comments : array();
	}

	/**
	 * Decode HTML entities (even double-encoded) so notes are stored as plain UTF-8.
	 * Fixes display like "&ecirc; cu" instead of "ê cu" / "ghi chú".
	 */
	protected function cleanPlainText($text) {
		$comment = is_string($text) ? $text : (string) $text;
		if ($comment === '') {
			return '';
		}
		$charset = 'UTF-8';
		if (!empty($GLOBALS['default_charset'])) {
			$charset = $GLOBALS['default_charset'];
		}
		$flags = ENT_QUOTES;
		if (defined('ENT_HTML5')) {
			$flags = $flags | ENT_HTML5;
		}
		for ($i = 0; $i < 6; $i++) {
			$prev = $comment;
			$comment = html_entity_decode($comment, $flags, $charset);
			if ($comment === $prev) {
				break;
			}
		}
		if (function_exists('decode_html')) {
			$comment = decode_html($comment);
		}
		if (class_exists('Normalizer') && method_exists('Normalizer', 'normalize')) {
			$normalized = Normalizer::normalize($comment, Normalizer::FORM_C);
			if (is_string($normalized) && $normalized !== '') {
				$comment = $normalized;
			}
		}
		return $comment;
	}

	/**
	 * Map UI 1-based line index → real sequence_no from inventory lines.
	 * When key already matches a sequence_no, keep it.
	 *
	 * @param array $comments
	 * @param int $recordId
	 * @return array<int,string> sequence_no => comment
	 */
	protected function mapCommentsToSequenceNos(array $comments, $recordId) {
		$db = PearDatabase::getInstance();
		$mapped = array();
		$sequences = array();
		$rs = $db->pquery(
			'SELECT sequence_no FROM vtiger_inventoryproductrel WHERE id = ? ORDER BY sequence_no ASC',
			array((int) $recordId)
		);
		if ($rs) {
			$n = $db->num_rows($rs);
			for ($i = 0; $i < $n; $i++) {
				$sequences[] = (int) $db->query_result($rs, $i, 'sequence_no');
			}
		}
		$seqSet = array_flip($sequences);

		foreach ($comments as $key => $text) {
			$k = (int) $key;
			if ($k <= 0) {
				continue;
			}
			$comment = $this->cleanPlainText($text);
			// Prefer exact sequence_no match, else 1-based row index in ordered list
			if (isset($seqSet[$k])) {
				$seqNo = $k;
			} elseif (isset($sequences[$k - 1])) {
				$seqNo = $sequences[$k - 1];
			} else {
				$seqNo = $k;
			}
			if ($seqNo > 0) {
				$mapped[$seqNo] = $comment;
			}
		}
		return $mapped;
	}

	/**
	 * Persist per-line comments + manual grand total (via adjustment) without rewriting line items.
	 */
	protected function saveInlineLineExtras(Vtiger_Request $request, Vtiger_Record_Model $recordModel) {
		$recordId = (int) $recordModel->getId();
		if ($recordId <= 0) {
			return;
		}
		$db = PearDatabase::getInstance();

		$comments = $this->extractInlineLineComments($request);
		if (!empty($comments)) {
			$bySequence = $this->mapCommentsToSequenceNos($comments, $recordId);
			foreach ($bySequence as $seqNo => $comment) {
				$db->pquery(
					'UPDATE vtiger_inventoryproductrel SET comment = ? WHERE id = ? AND sequence_no = ?',
					array($comment, $recordId, (int) $seqNo)
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
			 FROM vtiger_salesorder WHERE salesorderid = ?',
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
		$base = $subtotal - $discount + $shipping;
		$adjustment = $grand - $base;

		// Recompute balance if paid exists
		$paidAmount = 0.0;
		foreach (array('received', 'paid_amount', 'amount_paid', 'paid', 'mk_customer_paid') as $candidate) {
			if ($request->has($candidate)) {
				$paidAmount = $this->parseMoney($request->get($candidate));
				break;
			}
		}
		$balance = $grand - $paidAmount;
		if ($balance < 0) {
			$balance = 0;
		}

		$db->pquery(
			'UPDATE vtiger_salesorder SET total = ?, adjustment = ?, pre_tax_total = ? WHERE salesorderid = ?',
			array($grand, $adjustment, $subtotal, $recordId)
		);
		// balance column may exist
		try {
			$db->pquery(
				'UPDATE vtiger_salesorder SET balance = ? WHERE salesorderid = ?',
				array($balance, $recordId)
			);
		} catch (Exception $e) {
			/* ignore if no balance column */
		}
	}

	protected function parseMoney($value) {
		if (is_numeric($value)) {
			return (float) $value;
		}
		$s = trim((string) $value);
		if ($s === '') {
			return 0.0;
		}
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
