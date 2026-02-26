<?php
/* Smarty version 4.5.4, created on 2026-02-25 02:23:58
  from '/var/www/html/layouts/v7/modules/Reports/ListViewContents.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '4.5.4',
  'unifunc' => 'content_699e5d3e2a1106_59654146',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '0e75abdbba56d7138bb0b768fce6f94693320e76' => 
    array (
      0 => '/var/www/html/layouts/v7/modules/Reports/ListViewContents.tpl',
      1 => 1771985472,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_699e5d3e2a1106_59654146 (Smarty_Internal_Template $_smarty_tpl) {
?>
<div class="col-sm-12 col-xs-12 "><?php $_smarty_tpl->_assignInScope('LEFTPANELHIDE', $_smarty_tpl->tpl_vars['CURRENT_USER_MODEL']->value->get('leftpanelhide'));?><div class="essentials-toggle" title="<?php echo vtranslate('LBL_LEFT_PANEL_SHOW_HIDE','Vtiger');?>
"><span class="essentials-toggle-marker fa <?php if ($_smarty_tpl->tpl_vars['LEFTPANELHIDE']->value == '1') {?>fa-chevron-right<?php } else { ?>fa-chevron-left<?php }?> cursorPointer"></span></div><input type="hidden" name="view" id="view" value="<?php echo $_smarty_tpl->tpl_vars['VIEW']->value;?>
" /><input type="hidden" name="cvid" value="<?php echo (isset($_smarty_tpl->tpl_vars['VIEWID']->value)) ? $_smarty_tpl->tpl_vars['VIEWID']->value : '';?>
" /><input type="hidden" name="pageStartRange" id="pageStartRange" value="<?php echo $_smarty_tpl->tpl_vars['PAGING_MODEL']->value->getRecordStartRange();?>
" /><input type="hidden" name="pageEndRange" id="pageEndRange" value="<?php echo $_smarty_tpl->tpl_vars['PAGING_MODEL']->value->getRecordEndRange();?>
" /><input type="hidden" name="previousPageExist" id="previousPageExist" value="<?php echo $_smarty_tpl->tpl_vars['PAGING_MODEL']->value->isPrevPageExists();?>
" /><input type="hidden" name="nextPageExist" id="nextPageExist" value="<?php echo $_smarty_tpl->tpl_vars['PAGING_MODEL']->value->isNextPageExists();?>
" /><input type="hidden" name="Operator" id="Operator" value="<?php echo $_smarty_tpl->tpl_vars['OPERATOR']->value;?>
" /><input type="hidden" name="totalCount" id="totalCount" value="<?php echo $_smarty_tpl->tpl_vars['LISTVIEW_COUNT']->value;?>
" /><input type='hidden' name="pageNumber" value="<?php echo $_smarty_tpl->tpl_vars['PAGE_NUMBER']->value;?>
" id='pageNumber'><input type='hidden' name="pageLimit" value="<?php echo $_smarty_tpl->tpl_vars['PAGING_MODEL']->value->getPageLimit();?>
" id='pageLimit'><input type="hidden" name="noOfEntries" value="<?php echo $_smarty_tpl->tpl_vars['LISTVIEW_ENTRIES_COUNT']->value;?>
" id="noOfEntries"><input type="hidden" name="currentSearchParams" value="<?php echo Vtiger_Util_Helper::toSafeHTML(Zend_JSON::encode($_smarty_tpl->tpl_vars['SEARCH_DETAILS']->value));?>
" id="currentSearchParams" /><input type="hidden" name="noFilterCache" value="<?php echo (isset($_smarty_tpl->tpl_vars['NO_SEARCH_PARAMS_CACHE']->value)) ? $_smarty_tpl->tpl_vars['NO_SEARCH_PARAMS_CACHE']->value : '';?>
" id="noFilterCache" ><input type="hidden" name="orderBy" value="<?php echo $_smarty_tpl->tpl_vars['ORDER_BY']->value;?>
" id="orderBy"><input type="hidden" name="sortOrder" value="<?php echo $_smarty_tpl->tpl_vars['SORT_ORDER']->value;?>
" id="sortOrder"><input type="hidden" name="list_headers" value='<?php echo (isset($_smarty_tpl->tpl_vars['LIST_HEADER_FIELDS']->value)) ? $_smarty_tpl->tpl_vars['LIST_HEADER_FIELDS']->value : '';?>
'/><input type="hidden" name="tag" value="<?php echo (isset($_smarty_tpl->tpl_vars['CURRENT_TAG']->value)) ? $_smarty_tpl->tpl_vars['CURRENT_TAG']->value : '';?>
" /><input type="hidden" name="folder_id" value="<?php echo $_smarty_tpl->tpl_vars['FOLDER_ID']->value;?>
" /><input type="hidden" name="folder_value" value="<?php echo $_smarty_tpl->tpl_vars['FOLDER_VALUE']->value;?>
" /><input type="hidden" name="folder" value="<?php echo $_smarty_tpl->tpl_vars['VIEWNAME']->value;?>
" /><div class="management-report-spec"><div class="management-report-spec__top"><div class="management-report-spec__intro"><h2 class="management-report-spec__title">4. Management – Report</h2><div class="management-report-spec__section"><h3>Mục đích</h3><ul><li>Cung cấp báo cáo tổng hợp và chi tiết về hoạt động doanh nghiệp (Projects, Tasks, KPI, Documents, Sales, Marketing...).</li><li>Hỗ trợ quản lý / ban lãnh đạo nắm tình hình nhanh, so sánh giữa các phòng ban, kênh.</li><li>Giúp đưa ra quyết định chính xác dựa trên số liệu thực tế.</li></ul></div><div class="management-report-spec__section"><h3>Mô tả chi tiết</h3><ul><li>Người dùng: Quản lý PKD, quản lý phòng ban, ban giám đốc.</li><li>Cấp độ thông tin: Management → Report.</li><li>Thông tin cho Report: tổng quan + theo từng thành phần (Project, Task, MKT SALE, KPI).</li></ul></div><div class="management-report-spec__section"><h3>Luồng nghiệp vụ</h3><ol><li>Người dùng đăng nhập → vào Management → Report.</li><li>Chọn loại báo cáo → chọn bộ lọc (thời gian, phòng ban, người phụ trách, trạng thái).</li><li>Hệ thống hiển thị báo cáo dạng bảng/biểu đồ.</li><li>Người dùng có thể xuất báo cáo ra file (PDF, Excel, CSV).</li></ol></div></div><div class="management-report-spec__components"><table class="management-report-spec__table management-report-spec__table--components"><thead><tr><th>Thành phần</th><th>Nội dung</th></tr></thead><tbody><tr><td>Project Report</td><td>Tiến độ dự án, số lượng Task hoàn thành/chưa hoàn thành, deadline, người phụ trách.</td></tr><tr><td>Task Report</td><td>Task theo trạng thái (To Do, In Progress, Done), Task quá hạn, Task theo nhân viên.</td></tr><tr><td>MKT SALE</td><td>Doanh số, pipeline khách hàng, hiệu quả chiến dịch marketing.</td></tr><tr><td>KPI Report</td><td>KPI theo phòng ban/cá nhân, tỷ lệ hoàn thành KPI.</td></tr></tbody></table></div></div><div class="management-report-spec__bottom"><table class="management-report-spec__table management-report-spec__table--functions"><thead><tr><th>Chức năng</th><th>Mô tả</th></tr></thead><tbody><tr><td>Tạo báo cáo theo loại</td><td>Lựa chọn loại báo cáo và các dữ liệu đầu vào.</td></tr><tr><td>Hiển thị</td><td>Báo cáo được hiển thị dạng bảng và biểu đồ.</td></tr><tr><td>Bộ lọc</td><td>Lọc theo thời gian, phòng ban, người phụ trách, trạng thái,...</td></tr><tr><td>Xuất file</td><td>Xuất báo cáo ra nhiều định dạng (PDF, Excel, CSV).</td></tr><tr><td>Lưu cấu hình</td><td>Lưu cấu hình các loại báo cáo đã tạo để dùng lần sau.</td></tr></tbody></table></div></div><?php if (!$_smarty_tpl->tpl_vars['SEARCH_MODE_RESULTS']->value) {
$_smarty_tpl->_subTemplateRender(call_user_func_array($_smarty_tpl->registered_plugins[ 'modifier' ][ 'vtemplate_path' ][ 0 ], array( "ListViewActions.tpl",$_smarty_tpl->tpl_vars['MODULE']->value )), $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, true);
}?><div id="table-content" class="table-container"><form name='list' id='listedit' action='' onsubmit="return false;"><table id="listview-table" class="table <?php if ($_smarty_tpl->tpl_vars['LISTVIEW_ENTRIES_COUNT']->value == '0') {?>listview-table-norecords <?php }?> listview-table reports-listview-table"><colgroup><col class="col-actions"><?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['LISTVIEW_HEADERS']->value, 'LISTVIEW_HEADER', false, 'LISTVIEW_HEADER_KEY');
$_smarty_tpl->tpl_vars['LISTVIEW_HEADER']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['LISTVIEW_HEADER_KEY']->value => $_smarty_tpl->tpl_vars['LISTVIEW_HEADER']->value) {
$_smarty_tpl->tpl_vars['LISTVIEW_HEADER']->do_else = false;
?><col class="col-<?php echo $_smarty_tpl->tpl_vars['LISTVIEW_HEADER_KEY']->value;?>
"><?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?></colgroup><thead><tr class="listViewContentHeader"><th><?php if (!$_smarty_tpl->tpl_vars['SEARCH_MODE_RESULTS']->value) {?><div class="table-actions"><div class="dropdown" style="float:left;margin-left:6px;"><span class="input dropdown-toggle" title="<?php echo vtranslate('LBL_CLICK_HERE_TO_SELECT_ALL_RECORDS',$_smarty_tpl->tpl_vars['MODULE']->value);?>
" data-toggle="dropdown"><input class="listViewEntriesMainCheckBox" type="checkbox"></span></div></div><?php } elseif ($_smarty_tpl->tpl_vars['SEARCH_MODE_RESULTS']->value) {
echo vtranslate('LBL_ACTIONS',$_smarty_tpl->tpl_vars['MODULE']->value);
}?></th><?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['LISTVIEW_HEADERS']->value, 'LISTVIEW_HEADER', false, 'LISTVIEW_HEADER_KEY');
$_smarty_tpl->tpl_vars['LISTVIEW_HEADER']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['LISTVIEW_HEADER_KEY']->value => $_smarty_tpl->tpl_vars['LISTVIEW_HEADER']->value) {
$_smarty_tpl->tpl_vars['LISTVIEW_HEADER']->do_else = false;
?><th <?php if ($_smarty_tpl->tpl_vars['COLUMN_NAME']->value == $_smarty_tpl->tpl_vars['LISTVIEW_HEADER_KEY']->value) {?> nowrap="nowrap" <?php }?>><a href="#" class="listViewContentHeaderValues" data-nextsortorderval="<?php if ($_smarty_tpl->tpl_vars['COLUMN_NAME']->value == $_smarty_tpl->tpl_vars['LISTVIEW_HEADER_KEY']->value) {
echo $_smarty_tpl->tpl_vars['NEXT_SORT_ORDER']->value;
} else { ?>ASC<?php }?>" data-columnname="<?php echo $_smarty_tpl->tpl_vars['LISTVIEW_HEADER_KEY']->value;?>
"><?php if ($_smarty_tpl->tpl_vars['COLUMN_NAME']->value == $_smarty_tpl->tpl_vars['LISTVIEW_HEADER_KEY']->value) {?><i class="fa fa-sort <?php echo $_smarty_tpl->tpl_vars['FASORT_IMAGE']->value;?>
"></i><?php } else { ?><i class="fa fa-sort customsort"></i><?php }?>&nbsp;<?php echo vtranslate($_smarty_tpl->tpl_vars['LISTVIEW_HEADERS']->value[$_smarty_tpl->tpl_vars['LISTVIEW_HEADER_KEY']->value]['label'],$_smarty_tpl->tpl_vars['MODULE']->value);?>
&nbsp;</a><?php if ($_smarty_tpl->tpl_vars['COLUMN_NAME']->value == $_smarty_tpl->tpl_vars['LISTVIEW_HEADER_KEY']->value) {?><a href="#" class="removeSorting"><i class="fa fa-remove"></i></a><?php }?></th><?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?></tr><?php if ($_smarty_tpl->tpl_vars['MODULE_MODEL']->value->isQuickSearchEnabled() && !$_smarty_tpl->tpl_vars['SEARCH_MODE_RESULTS']->value) {?><tr class="searchRow listViewSearchContainer"><th class="inline-search-btn"><div class="table-actions"><button class="btn-sm btn btn-success <?php if (php7_count($_smarty_tpl->tpl_vars['SEARCH_DETAILS']->value) > 0) {?>hide<?php }?>" data-trigger="listSearch"><i class="fa fa-search"></i>&nbsp;<span class="s2-btn-text"><?php echo vtranslate("LBL_SEARCH",$_smarty_tpl->tpl_vars['MODULE']->value);?>
</span></button><button class="searchAndClearButton btn-sm  btn btn-danger <?php if (php7_count($_smarty_tpl->tpl_vars['SEARCH_DETAILS']->value) == 0) {?>hide<?php }?>" data-trigger="clearListSearch"><i class="fa fa-close"></i>&nbsp;<?php echo vtranslate("LBL_CLEAR",$_smarty_tpl->tpl_vars['MODULE']->value);?>
</button></div></th><?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['LISTVIEW_HEADERS']->value, 'LISTVIEW_HEADER', false, 'LISTVIEW_HEADER_KEY');
$_smarty_tpl->tpl_vars['LISTVIEW_HEADER']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['LISTVIEW_HEADER_KEY']->value => $_smarty_tpl->tpl_vars['LISTVIEW_HEADER']->value) {
$_smarty_tpl->tpl_vars['LISTVIEW_HEADER']->do_else = false;
?><th><?php $_smarty_tpl->_assignInScope('DATA_TYPE', $_smarty_tpl->tpl_vars['LISTVIEW_HEADER']->value['type']);
if ($_smarty_tpl->tpl_vars['DATA_TYPE']->value == 'string') {?><div class="row-fluid"><input type="text" name="<?php echo $_smarty_tpl->tpl_vars['LISTVIEW_HEADER_KEY']->value;?>
" class="listSearchContributor inputElement" value="<?php echo (isset($_smarty_tpl->tpl_vars['SEARCH_DETAILS']->value[$_smarty_tpl->tpl_vars['LISTVIEW_HEADER_KEY']->value]['searchValue'])) ? $_smarty_tpl->tpl_vars['SEARCH_DETAILS']->value[$_smarty_tpl->tpl_vars['LISTVIEW_HEADER_KEY']->value]['searchValue'] : '';?>
" data-fieldinfo='<?php if ((isset($_smarty_tpl->tpl_vars['FIELD_INFO']->value))) {
echo htmlspecialchars((string)$_smarty_tpl->tpl_vars['FIELD_INFO']->value, ENT_QUOTES, 'UTF-8', true);
}?>'/></div><?php } elseif ($_smarty_tpl->tpl_vars['DATA_TYPE']->value == 'picklist') {
$_smarty_tpl->_assignInScope('PICKLIST_VALUES', Reports_Field_Model::getPicklistValueByField($_smarty_tpl->tpl_vars['LISTVIEW_HEADER_KEY']->value));
$_smarty_tpl->_assignInScope('SEARCH_VALUES', explode(',',(isset($_smarty_tpl->tpl_vars['SEARCH_INFO']->value['searchValue'])) ? $_smarty_tpl->tpl_vars['SEARCH_INFO']->value['searchValue'] : ','));?><div class="row-fluid"><select class="select2 listSearchContributor report-type-select" name="<?php echo $_smarty_tpl->tpl_vars['LISTVIEW_HEADER_KEY']->value;?>
" multiple data-fieldinfo='<?php if ((isset($_smarty_tpl->tpl_vars['FIELD_INFO']->value))) {
echo htmlspecialchars((string)$_smarty_tpl->tpl_vars['FIELD_INFO']->value, ENT_QUOTES, 'UTF-8', true);
}?>'><?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['PICKLIST_VALUES']->value, 'PICKLIST_LABEL', false, 'PICKLIST_KEY');
$_smarty_tpl->tpl_vars['PICKLIST_LABEL']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['PICKLIST_KEY']->value => $_smarty_tpl->tpl_vars['PICKLIST_LABEL']->value) {
$_smarty_tpl->tpl_vars['PICKLIST_LABEL']->do_else = false;
if ($_smarty_tpl->tpl_vars['PICKLIST_LABEL']->value == 'Chart') {
$_smarty_tpl->_assignInScope('ICON_CLASS', 'fa fa-pie-chart');
} elseif ($_smarty_tpl->tpl_vars['PICKLIST_LABEL']->value == 'Detail') {
$_smarty_tpl->_assignInScope('ICON_CLASS', 'vicon-detailreport');
}?><option value="<?php echo $_smarty_tpl->tpl_vars['PICKLIST_KEY']->value;?>
" <?php if (in_array($_smarty_tpl->tpl_vars['PICKLIST_KEY']->value,$_smarty_tpl->tpl_vars['SEARCH_VALUES']->value) && ($_smarty_tpl->tpl_vars['PICKLIST_KEY']->value != '')) {?> selected<?php }?> <?php if ($_smarty_tpl->tpl_vars['LISTVIEW_HEADER_KEY']->value == 'reporttype') {?>class='<?php echo $_smarty_tpl->tpl_vars['ICON_CLASS']->value;?>
'<?php }?>><?php echo $_smarty_tpl->tpl_vars['PICKLIST_LABEL']->value;?>
</option><?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?></select></div><?php }?><input type="hidden" class="operatorValue" value="<?php echo (isset($_smarty_tpl->tpl_vars['SEARCH_DETAILS']->value[$_smarty_tpl->tpl_vars['LISTVIEW_HEADER_KEY']->value]['comparator'])) ? $_smarty_tpl->tpl_vars['SEARCH_DETAILS']->value[$_smarty_tpl->tpl_vars['LISTVIEW_HEADER_KEY']->value]['comparator'] : '';?>
"></th><?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?></tr><?php }?></thead><tbody class="overflow-y"><?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['LISTVIEW_ENTRIES']->value, 'LISTVIEW_ENTRY', false, NULL, 'listview', array (
  'index' => true,
));
$_smarty_tpl->tpl_vars['LISTVIEW_ENTRY']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['LISTVIEW_ENTRY']->value) {
$_smarty_tpl->tpl_vars['LISTVIEW_ENTRY']->do_else = false;
$_smarty_tpl->tpl_vars['__smarty_foreach_listview']->value['index']++;
?><tr class="listViewEntries" data-id='<?php echo $_smarty_tpl->tpl_vars['LISTVIEW_ENTRY']->value->getId();?>
' data-recordUrl='<?php echo $_smarty_tpl->tpl_vars['LISTVIEW_ENTRY']->value->getDetailViewUrl();?>
' id="<?php echo $_smarty_tpl->tpl_vars['MODULE']->value;?>
_listView_row_<?php echo (isset($_smarty_tpl->tpl_vars['__smarty_foreach_listview']->value['index']) ? $_smarty_tpl->tpl_vars['__smarty_foreach_listview']->value['index'] : null)+1;?>
"><td class = "listViewRecordActions"><?php $_smarty_tpl->_subTemplateRender(call_user_func_array($_smarty_tpl->registered_plugins[ 'modifier' ][ 'vtemplate_path' ][ 0 ], array( "ListViewRecordActions.tpl",$_smarty_tpl->tpl_vars['MODULE']->value )), $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, true);
?></td><?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['LISTVIEW_HEADERS']->value, 'LISTVIEW_HEADER', false, 'LISTVIEW_HEADER_KEY');
$_smarty_tpl->tpl_vars['LISTVIEW_HEADER']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['LISTVIEW_HEADER_KEY']->value => $_smarty_tpl->tpl_vars['LISTVIEW_HEADER']->value) {
$_smarty_tpl->tpl_vars['LISTVIEW_HEADER']->do_else = false;
$_smarty_tpl->_assignInScope('LISTVIEW_HEADERNAME', $_smarty_tpl->tpl_vars['LISTVIEW_HEADER_KEY']->value);
$_smarty_tpl->_assignInScope('LISTVIEW_ENTRY_RAWVALUE', $_smarty_tpl->tpl_vars['LISTVIEW_ENTRY']->value->getRaw($_smarty_tpl->tpl_vars['LISTVIEW_HEADER_KEY']->value));
$_smarty_tpl->_assignInScope('LISTVIEW_ENTRY_VALUE', $_smarty_tpl->tpl_vars['LISTVIEW_ENTRY']->value->get($_smarty_tpl->tpl_vars['LISTVIEW_HEADERNAME']->value));?><td class="listViewEntryValue" data-name="<?php echo $_smarty_tpl->tpl_vars['LISTVIEW_HEADERNAME']->value;?>
" title="<?php echo $_smarty_tpl->tpl_vars['LISTVIEW_ENTRY_RAWVALUE']->value;?>
" data-rawvalue="<?php echo $_smarty_tpl->tpl_vars['LISTVIEW_ENTRY_RAWVALUE']->value;?>
" data-field-type=""><span class="fieldValue"><span class="value textOverflowEllipsis"><?php if ((isset($_smarty_tpl->tpl_vars['LISTVIEW_HEADERNAME']->value)) && $_smarty_tpl->tpl_vars['LISTVIEW_HEADERNAME']->value == 'reporttype') {
if ($_smarty_tpl->tpl_vars['LISTVIEW_ENTRY_VALUE']->value == 'summary' || $_smarty_tpl->tpl_vars['LISTVIEW_ENTRY_VALUE']->value == 'tabular') {?><center title="<?php echo vtranslate('LBL_DETAIL_REPORT',$_smarty_tpl->tpl_vars['MODULE']->value);?>
"><span class='vicon-detailreport' style="font-size:17px;"></span></center><?php } elseif ($_smarty_tpl->tpl_vars['LISTVIEW_ENTRY_VALUE']->value == 'chart') {?><center title="<?php echo vtranslate('LBL_CHART_REPORT',$_smarty_tpl->tpl_vars['MODULE']->value);?>
"><span class='fa fa-pie-chart fa-2x' style="font-size:1.7em;"></span></center><?php }
} elseif ($_smarty_tpl->tpl_vars['LISTVIEW_HEADERNAME']->value == 'primarymodule') {
echo Vtiger_Util_Helper::tosafeHTML(decode_html(vtranslate($_smarty_tpl->tpl_vars['LISTVIEW_ENTRY_VALUE']->value,$_smarty_tpl->tpl_vars['LISTVIEW_ENTRY_VALUE']->value)));
} elseif ($_smarty_tpl->tpl_vars['LISTVIEW_HEADERNAME']->value == 'foldername') {
echo Vtiger_Util_Helper::tosafeHTML(vtranslate($_smarty_tpl->tpl_vars['LISTVIEW_ENTRY_VALUE']->value,$_smarty_tpl->tpl_vars['MODULE']->value));
} else {
echo Vtiger_Util_Helper::tosafeHTML($_smarty_tpl->tpl_vars['LISTVIEW_ENTRY_VALUE']->value);
}?></span></span></span></td><?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?></tr><?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);
if ($_smarty_tpl->tpl_vars['LISTVIEW_ENTRIES_COUNT']->value == '0') {?><tr class="emptyRecordsDiv"><?php ob_start();
echo php7_count($_smarty_tpl->tpl_vars['LISTVIEW_HEADERS']->value);
$_prefixVariable1 = ob_get_clean();
$_smarty_tpl->_assignInScope('COLSPAN_WIDTH', $_prefixVariable1+1);?><td colspan="<?php echo $_smarty_tpl->tpl_vars['COLSPAN_WIDTH']->value;?>
"><div class="emptyRecordsDiv"><div class="emptyRecordsContent"><?php $_smarty_tpl->_assignInScope('SINGLE_MODULE', "SINGLE_".((string)$_smarty_tpl->tpl_vars['MODULE']->value));
echo vtranslate('LBL_NO');?>
 <?php echo vtranslate($_smarty_tpl->tpl_vars['MODULE']->value,$_smarty_tpl->tpl_vars['MODULE']->value);?>
 <?php echo vtranslate('LBL_FOUND');?>
.<?php if ((isset($_smarty_tpl->tpl_vars['IS_MODULE_EDITABLE']->value)) && $_smarty_tpl->tpl_vars['IS_MODULE_EDITABLE']->value) {?> <a href="<?php echo $_smarty_tpl->tpl_vars['MODULE_MODEL']->value->getCreateRecordUrl();?>
"> <?php echo vtranslate('LBL_CREATE');?>
 </a> <?php if (Users_Privileges_Model::isPermitted($_smarty_tpl->tpl_vars['MODULE']->value,'Import') && $_smarty_tpl->tpl_vars['LIST_VIEW_MODEL']->value->isImportEnabled()) {?> <?php echo vtranslate('LBL_OR',$_smarty_tpl->tpl_vars['MODULE']->value);?>
 <a style="color:blue" href="#" onclick="return Vtiger_Import_Js.triggerImportAction()"> <?php echo vtranslate('LBL_IMPORT',$_smarty_tpl->tpl_vars['MODULE']->value);?>
 </a><?php echo vtranslate($_smarty_tpl->tpl_vars['MODULE']->value,$_smarty_tpl->tpl_vars['MODULE']->value);
} else {
echo vtranslate($_smarty_tpl->tpl_vars['SINGLE_MODULE']->value,$_smarty_tpl->tpl_vars['MODULE']->value);
}
}?></div></div></td></tr><?php }?></tbody></table></form></div><div id="scroller_wrapper" class="bottom-fixed-scroll"><div id="scroller" class="scroller-div"></div></div></div><?php }
}
