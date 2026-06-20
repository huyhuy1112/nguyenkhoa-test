{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}



<div class="mk-import-section">
    <h4 class="mk-import-section-title">{'LBL_IMPORT_MAP_FIELDS'|@vtranslate:$MODULE}</h4>
    <p class="mk-import-section-sub">Chọn trường CRM tương ứng với từng cột trong file. Cột không cần thiết có thể để trống.</p>
</div>
<div id="savedMapsContainer">{include file="Import_Saved_Maps.tpl"|@vtemplate_path:'Import'}</div>
<div class="mk-import-map-wrap">{include file="Import_Mapping.tpl"|@vtemplate_path:'Import'}</div>
<div class="form-inline" style="padding-bottom: 10%;">
    <input type="checkbox" name="save_map" id="save_map">&nbsp;&nbsp;<label for="save_map">{'LBL_SAVE_AS_CUSTOM_MAPPING'|@vtranslate:$MODULE}</label>
    &nbsp;&nbsp;<input type="text" name="save_map_as" id="save_map_as" class = "form-control">
</div>
{if !isset($IMPORTABLE_FIELDS) || !$IMPORTABLE_FIELDS}
	{assign var=IMPORTABLE_FIELDS value=$AVAILABLE_FIELDS}
{/if}
{include file="Import_Default_Values_Widget.tpl"|@vtemplate_path:'Import' IMPORTABLE_FIELDS=$IMPORTABLE_FIELDS}
