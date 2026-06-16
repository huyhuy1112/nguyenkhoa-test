{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}

{assign var=MK_SKIP_GLOBAL_APP_FOOTER value=false}
{if $MODULE_NAME eq 'Project' && ($VIEW eq 'Detail' || $VIEW eq 'List') && ((isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'MANAGEMENT') || (isset($smarty.get.app) && $smarty.get.app eq 'MANAGEMENT'))}
	{assign var=MK_SKIP_GLOBAL_APP_FOOTER value=true}
{/if}
{if !($MODULE_NAME eq 'Home' && $VIEW eq 'DashBoard') && !$MK_SKIP_GLOBAL_APP_FOOTER}
<footer class="app-footer">
	<p>B-ACE developed by TDB SOLUTION 2025</p>
</footer>
{/if}
</div>
<div id='overlayPage' class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
	<!-- arrow is added to point arrow to the clicked element (Ex:- TaskManagement), 
	any one can use this by adding "show" class to it -->
	<div class='arrow'></div>
	<div class='data'>
	</div>
</div>
<div id='helpPageOverlay'></div>
<div id="js_strings" class="hide noprint">{Zend_Json::encode($LANGUAGE_STRINGS)}</div>
<div id="maxListFieldsSelectionSize" class="hide noprint">{$MAX_LISTFIELDS_SELECTION_SIZE}</div>
<div class="modal myModal fade"></div>
{include file='JSResources.tpl'|@vtemplate_path}
</body>

</html>
