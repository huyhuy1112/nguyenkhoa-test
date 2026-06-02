{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}

{strip}
	<div id="taskManagementContainer" class="{if !empty($TASK_MGMT_EMBED_OVERLAY)}fc-overlay-modal modal-content{else}task-management-page panel panel-default mk-task-mgmt-panel{/if}" style="{if !empty($TASK_MGMT_EMBED_OVERLAY)}height:100%;{else}height:auto;min-height:420px;{/if}">
		<input type="hidden" name="colors" value='{json_encode($COLORS)}'>
		<div class="overlayHeader">
			{assign var=HEADER_TITLE value="TASK MANAGEMENT"}
			{if !empty($TASK_MGMT_EMBED_OVERLAY)}
				{include file="ModalHeader.tpl"|vtemplate_path:$MODULE TITLE=$HEADER_TITLE}
			{else}
				<div class="modal-header task-mgmt-fullpage-header">
					<div class="clearfix">
						<div class="pull-right">
							<a href="{if !empty($TASK_RETURN_URL)}{$TASK_RETURN_URL|escape:'html'}{else}index.php?module=Home&amp;view=MainPage&amp;app={$SELECTED_MENU_CATEGORY|default:'MANAGEMENT'}{/if}" class="close task-mgmt-fullpage-close" title="{vtranslate('LBL_CLOSE', $MODULE)}" aria-label="Close">
								<span aria-hidden="true" class="fa fa-close"></span>
							</a>
						</div>
						<h4 class="pull-left">{$HEADER_TITLE}</h4>
					</div>
				</div>
			{/if}
		</div>
		<hr style="margin:0px;">
		<div class='modal-body overflowYAuto'>
			<div class='datacontent'>
				<div class="data-header clearfix">
					<div class="btn-group dateFilters pull-left" role="group" aria-label="...">
						<button type="button" class="btn btn-default {if $TASK_FILTERS['date'] eq "all"}active{/if}" data-filtermode="all">{vtranslate('LBL_ALL', $MODULE)}</button>
						<button type="button" class="btn btn-default {if $TASK_FILTERS['date'] eq "today"}active{/if}" data-filtermode="today">{vtranslate('LBL_TODAY', $MODULE)}</button>
						<button type="button" class="btn btn-default {if $TASK_FILTERS['date'] eq "thisweek"}active{/if}" data-filtermode="thisweek">{vtranslate('LBL_THIS_WEEK', $MODULE)}</button>
						<button type="button" class="btn btn-default dateRange dateField" data-calendar-type="range" data-filtermode="range"><i class="fa fa-calendar"></i></button>
						<button type="button" class="btn btn-default hide rangeDisplay">
							<span class="selectedRange"></span>&nbsp;
							<i class="fa fa-times clearRange"></i>
						</button>
					</div>

					<div id="taskManagementOtherFilters" class="otherFilters pull-right task-mgmt-filters mk-task-mgmt-toolbar-filters">
						<div class="mk-task-mgmt-filter-item field">
							<label class="mk-task-mgmt-filter-label" for="taskMgmtFilterOwner">{vtranslate($OWNER_FIELD->get('label'), $MODULE)}</label>
							<div class="mk-task-mgmt-filter-control" id="taskMgmtFilterOwner">
								{include file="modules/Calendar/uitypes/OwnerFieldTaskSearchView.tpl" FIELD_MODEL=$OWNER_FIELD}
							</div>
						</div>
						<div class="mk-task-mgmt-filter-item field">
							{assign var=FIELD_MODEL value=$STATUS_FIELD}
							{assign var=FIELD_INFO value=$FIELD_MODEL->getFieldInfo()}
							{assign var=PICKLIST_VALUES value=$FIELD_INFO['picklistvalues']}
							{assign var=FIELD_INFO value=Vtiger_Util_Helper::toSafeHTML(Zend_Json::encode($FIELD_INFO))}
							<label class="mk-task-mgmt-filter-label" for="taskMgmtFilterStatusSelect">{vtranslate($FIELD_MODEL->get('label'), $MODULE)}</label>
							<div class="mk-task-mgmt-filter-control">
								<select class="select2 listSearchContributor mk-task-mgmt-status-select" id="taskMgmtFilterStatusSelect" name="{$FIELD_MODEL->get('name')}" multiple data-fieldinfo='{$FIELD_INFO|escape}' data-placeholder="{vtranslate('LBL_SELECT_STATUS', $MODULE)}">
									{foreach item=PICKLIST_LABEL key=PICKLIST_KEY from=$PICKLIST_VALUES}
										<option {if $PICKLIST_KEY|in_array:$TASK_FILTERS['status']}selected{/if} value="{$PICKLIST_KEY}">{$PICKLIST_LABEL}</option>
									{/foreach}
								</select>
							</div>
						</div>
						<div class="mk-task-mgmt-filter-item mk-task-mgmt-filter-item--search">
							<label class="mk-task-mgmt-filter-label mk-task-mgmt-filter-label--hidden" aria-hidden="true">&nbsp;</label>
							<button type="button" class="btn btn-success search" title="{vtranslate('LBL_SEARCH', $MODULE)}"><span class="fa fa-search"></span></button>
						</div>
					</div>
				</div>

				<hr>

				<div class="data-body row">
					{assign var=MODULE_MODEL value= Vtiger_Module_Model::getInstance($MODULE)}
                    {assign var=USER_PRIVILEGES_MODEL value= Users_Privileges_Model::getCurrentUserPrivilegesModel()}
					{foreach item=PRIORITY from=$PRIORITIES}
						<div class="col-lg-4 contentsBlock {strtolower($PRIORITY)} ui-droppable" data-priority='{$PRIORITY}' data-page="{$PAGE}">
							<div class="{strtolower($PRIORITY)}-header" style="border-bottom: 2px solid {$COLORS[$PRIORITY]}">
								<div class="title" style="background:{$COLORS[$PRIORITY]}"><span>{$PRIORITY}</span></div>
							</div>
							<br>
							<div class="{strtolower($PRIORITY)}-content content" data-priority='{$PRIORITY}' style="border-bottom: 1px solid {$COLORS[$PRIORITY]};padding-bottom: 10px">
								{if $USER_PRIVILEGES_MODEL->hasModuleActionPermission($MODULE_MODEL->getId(), 'CreateView')}
									<div class="input-group">
										<input type="text" class="form-control taskSubject {$PRIORITY}" placeholder="{vtranslate('LBL_ADD_TASK_AND_PRESS_ENTER', $MODULE)}" aria-describedby="basic-addon1" style="width: 99%">
										<span class="quickTask input-group-addon js-task-popover-container more cursorPointer" id="basic-addon1" style="border: 1px solid #ddd; padding: 0 13px;"> 
											<a href="#" id="taskPopover" priority='{$PRIORITY}'><i class="fa fa-plus icon"></i></a>
										</span>
									</div>
								{/if}
								<br>
								<div class='{strtolower($PRIORITY)}-entries container-fluid scrollable dataEntries padding20 task-mgmt-column-scroll' style="height:400px;overflow:auto;">

								</div>
							</div>
						</div>
					{/foreach}
				</div>
				<div class="editTaskContent hide"> 
					{include file="TaskManagementEdit.tpl"|vtemplate_path:$MODULE} 
				</div> 
			</div>
		</div>
	</div>
{/strip}
