<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Quotes_ExportExcelForSale_Action extends Vtiger_Action_Controller {

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

        try {
            require_once 'libraries/PHPExcel/PHPExcel.php';
            
            // Load template file
            $templatePath = 'templates/TDB-Quote-Sale.xlsx';
            $objReader = PHPExcel_IOFactory::createReader('Excel2007');
            $objPHPExcel = $objReader->load($templatePath);
            // Set default font to a Unicode-safe font to avoid Vietnamese font issues
            $objPHPExcel->getDefaultStyle()->getFont()->setName('Arial Unicode MS')->setSize(10);
            $sheet = $objPHPExcel->getActiveSheet(0);

            $accountId = $focus->column_fields['account_id'];
            $focusAccount = CRMEntity::getInstance('Accounts');
            $focusAccount->retrieve_entity_info($accountId, 'Accounts');

            // Set account related fields
            $today = date('d/m/Y'); // Fixed date format
            $Account_No = $focusAccount->column_fields['account_no'] ?? '';
            $Account_Name = decode_html($focusAccount->column_fields['accountname']) ?? '';
            $Phone = $focusAccount->column_fields['phone'] ?? '';
            $Email = !empty($focusAccount->column_fields['email1']) ? $focusAccount->column_fields['email1'] : ($focusAccount->column_fields['email2'] ?? '');
            $Fax = $focusAccount->column_fields['fax'] ?? '';
            $ShippingAddress = $focusAccount->column_fields['ship_street'] ?? '';

            $contactId = $focus->column_fields['contact_id'];
            $focusContact = CRMEntity::getInstance('Contacts');
            $focusContact->retrieve_entity_info($contactId, 'Contacts');

            $Receiver = $focusContact->column_fields['firstname'] . ' ' . $focusContact->column_fields['lastname'] ?? '';
            // $Mobile = $focusContact->column_fields['mobile'] ?? '';

            $sheet->setCellValue('B7', 'Số: ' . $today .'_TDB_' . $Account_No);
            $sheet->setCellValue('B8', $sheet->getCell('B8')->getValue() . $Account_Name);
            $sheet->setCellValue('B9', $sheet->getCell('B9')->getValue() . $Phone);
            $sheet->setCellValue('F9', $sheet->getCell('F9')->getValue() . $Fax);
            $sheet->setCellValue('B10', $sheet->getCell('B10')->getValue() . $Receiver);
            $sheet->setCellValue('F10', $sheet->getCell('F10')->getValue() . ' ' . $Email);
            $sheet->setCellValue('B30', $sheet->getCell('B30')->getValue() . decode_html($ShippingAddress));

            // Get associated products
            $associated_products = getAssociatedProducts($moduleName, $focus);
            
            // Starting row for products
            $row = 16;
            $productLineItemIndex = 0;
            $length = count($associated_products);

            if($length > 1) {
                $sheet->insertNewRowBefore($row, $length - 1);
            }
            
            $focusProduct = CRMEntity::getInstance('Products');
            foreach ($associated_products as $productLineItem) {
                ++$productLineItemIndex;
                
                // Get product ID and usage unit
                $productId = $productLineItem["hdnProductId{$productLineItemIndex}"]; // Correct key for product ID
                $focusProduct->retrieve_entity_info($productId, 'Products');

                $usage_unit =  $focusProduct->column_fields['usageunit'];

                // Rest of the product line item processing
                $quantity = $productLineItem["qty{$productLineItemIndex}"];
                $listPrice = $productLineItem["listPrice{$productLineItemIndex}"];
                $discount = $productLineItem["discountTotal{$productLineItemIndex}"];
                $productName = decode_html($productLineItem["productName{$productLineItemIndex}"]);
                $total = $quantity * $listPrice - $discount;
                
                // Insert data
                $sheet->setCellValue('B' . $row, $productLineItemIndex);
                $sheet->setCellValue('C' . $row, $productName);
                $sheet->setCellValue('D' . $row, $usage_unit); // Use translated value
                $sheet->setCellValue('E' . $row, $quantity);
                $sheet->setCellValue('F' . $row, $listPrice);
                $sheet->setCellValue('G' . $row, $total);

                $row++;
            }

            // Xóa hàng 15
            $sheet->removeRow(15);

            $row = 15 + $length;
            // Tính thanh tien
            $sheet->setCellValue('F' . $row, '=SUM(G15:G' . $row . ')');
            $row++;
            // Tính tien thue
            $sheet->setCellValue('F' . $row, '=F' . ($row - 1) . '*10%');
            $row++;
            // Tính tong tien 
            $sheet->setCellValue('F' . $row, '=F' . ($row - 2) . '+F' . ($row - 1));
            // Tính so luong   
            $sheet->setCellValue('E' . $row, '=SUM(E15:E' . ($row - 3) . ')');


            // Set headers for download
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="BaoGia_BanHang_' . $recordId . '_' . date('YmdHis') . '.xlsx"');
            header('Cache-Control: max-age=0');

            // Save file to browser
            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
            $objWriter->save('php://output');
            
        } catch (Exception $e) {
            throw new AppException($e->getMessage());
        }
    }
}
