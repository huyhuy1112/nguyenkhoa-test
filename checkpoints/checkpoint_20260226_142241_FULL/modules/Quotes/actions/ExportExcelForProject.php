<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

require_once('include/utils/AppHelpers.php');

class Quotes_ExportExcelForProject_Action extends Vtiger_Action_Controller {

    public function requiresPermission(\Vtiger_Request $request) {
        $permissions = parent::requiresPermission($request);
        $permissions[] = array('module_parameter' => 'module', 'action' => 'DetailView', 'record_parameter' => 'record');
        return $permissions;
    }

    public function process(Vtiger_Request $request) {
        $moduleName = $request->getModule();

        $recordId = $request->get('record');
        $focus = CRMEntity::getInstance($moduleName);
        $focus->retrieve_entity_info($recordId, $moduleName);
        $focus->apply_field_security();
        $focus->id = $recordId;

        // $column_fields = $focus->column_fields;
        // foreach ($column_fields as $key => $value) {
        //     if (is_array($value)) {
        //         $column_fields[$key] = implode(',', $value);
        //     }
        //     AppHelpers::writeLog('Key: ' . $key . ', Value: ' . $value);
        // }

        try {
            require_once 'libraries/PHPExcel/PHPExcel.php';
            
            // Load template file
            $templatePath = 'templates/TDB-Quote-Project.xlsx';
            $objReader = PHPExcel_IOFactory::createReader('Excel2007');
            $objPHPExcel = $objReader->load($templatePath);
            // Set default font to a Unicode-safe font to avoid Vietnamese font issues
            $objPHPExcel->getDefaultStyle()->getFont()->setName('Arial Unicode MS')->setSize(10);
            $sheet = $objPHPExcel->getActiveSheet(0);

            $accountId = $focus->column_fields['account_id'];
            $numberQuote = $focus->column_fields['quote_no'];

            $focusAccount = CRMEntity::getInstance('Accounts');
            $focusAccount->retrieve_entity_info($accountId, 'Accounts');

            // Set account related fields
            $today = date('d/m/Y'); // Fixed date format
            // $Account_No = $focusAccount->column_fields['account_no'] ?? '';
            $Account_Name = decode_html($focusAccount->column_fields['accountname']) ?? '';
            // $Phone = $focusAccount->column_fields['phone'] ?? '';
            $Email = !empty($focusAccount->column_fields['email1']) ? $focusAccount->column_fields['email1'] : ($focusAccount->column_fields['email2'] ?? '');
            // $Fax = $focusAccount->column_fields['fax'] ?? '';
            $BillAddress = $focusAccount->column_fields['bill_street'] ?? '';

            $contactId = $focus->column_fields['contact_id'];
            $focusContact = CRMEntity::getInstance('Contacts');
            $focusContact->retrieve_entity_info($contactId, 'Contacts');

            $Receiver = $focusContact->column_fields['firstname'] . ' ' . $focusContact->column_fields['lastname'] ?? '';
            $Mobile = $focusContact->column_fields['mobile'] ?? '';

            $sheet->setCellValue('M5', $numberQuote);
            $sheet->setCellValue('M6', $today);
            $sheet->setCellValue('D9', $Receiver);
            $sheet->setCellValue('D10', $Account_Name);
            $sheet->setCellValue('D11', decode_html($BillAddress));
            $sheet->setCellValue('L9', $Mobile);
            $sheet->setCellValue('L10', $Email);

            // Get associated products
            $associated_products = getAssociatedProducts($moduleName, $focus);
            
            // Starting row for products
            $row = 19;
            $productLineItemIndex = 0;
            $length = count($associated_products);

            if($length > 1) {
                $sheet->insertNewRowBefore($row, $length - 1);
            }
            
            $money = 0;

            $focusProduct = CRMEntity::getInstance('Products');
            foreach ($associated_products as $productLineItem) {
                ++$productLineItemIndex;

                // $jsonProductLineItem = json_encode($productLineItem);
                // AppHelpers::writeLog($jsonProductLineItem);

                // Get product ID and usage unit
                $productId = $productLineItem["hdnProductId{$productLineItemIndex}"]; // Correct key for product ID
                $focusProduct->retrieve_entity_info($productId, 'Products');

                $usage_unit =  $focusProduct->column_fields['usageunit'];

                // Rest of the product line item processing
                $quantity = $productLineItem["qty{$productLineItemIndex}"];
                $listPrice = $productLineItem["listPrice{$productLineItemIndex}"];
                $discount = $productLineItem["discountTotal{$productLineItemIndex}"];
                $productCode = $productLineItem["hdnProductcode{$productLineItemIndex}"];
                $productName = decode_html($productLineItem["productName{$productLineItemIndex}"]);
                $total = $quantity * $listPrice - $discount;
                $money += $total;
                
                // Insert data
                $sheet->setCellValue('B' . $row, $productLineItemIndex);
                $sheet->setCellValue('C' . $row, $productCode);
                $sheet->setCellValue('E' . $row, $productName);
                $sheet->setCellValue('K' . $row, $usage_unit); // Use translated value
                $sheet->setCellValue('L' . $row, $quantity);
                $sheet->setCellValue('M' . $row, $listPrice);
                $sheet->setCellValue('N' . $row, $total);

                $row++;
            }

            // Xóa hàng 18
            $sheet->removeRow(18);

            $row = 18 + $length;

            // AppHelpers::writeLog('\$row = ' . $row);

            // Tính thanh tien
            $sheet->setCellValue('N' . $row, '=SUM(N18:N' . ($row - 1) . ')');
            $row++;
            // AppHelpers::writeLog('\$row = ' . $row);
            // Tính tien thue
            $money += $money * 10 / 100;
            $sheet->setCellValue('N' . $row, '=N' . ($row - 1) . '*10%');
            $row++;
            // AppHelpers::writeLog('\$row = ' . $row);
            // Tính tong tien   
            $sheet->setCellValue('N' . $row, '=N' . ($row - 2) . '+N' . ($row - 1));

            // AppHelpers::writeLog('Type of \$money: ' . gettype($money));
            // AppHelpers::writeLog('Value of \$money: ' . $money);
            
            $wordMoney = AppHelpers::convert_number_to_words($money);
            // AppHelpers::writeLog($wordMoney);

            $row++;
            // AppHelpers::writeLog('\$row = ' . $row);
            // Bang chu
            $sheet->setCellValue('B' . $row, $sheet->getCell('B' . $row)->getValue() . $wordMoney . ' đồng');

            // Set headers for download
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="BaoGia_DuAn_' . $recordId . '_' . date('YmdHis') . '.xlsx"');
            header('Cache-Control: max-age=0');

            // Save file to browser
            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
            $objWriter->save('php://output');
            
        } catch (Exception $e) {
            throw new AppException($e->getMessage());
        }
    }
}
