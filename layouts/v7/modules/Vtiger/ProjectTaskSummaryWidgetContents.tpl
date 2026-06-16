{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*
********************************************************************************/
-->*}
{strip}
	{foreach item=HEADER from=$RELATED_HEADERS}
		{if $HEADER->get('label') eq "Project Task Name"}
			{assign var=TASK_NAME_HEADER value={vtranslate($HEADER->get('label'),$MODULE_NAME)}}
		{elseif $HEADER->get('label') eq "Progress"}
			{assign var=TASK_PROGRESS_HEADER value=vtranslate($HEADER->get('label'),$MODULE_NAME)}
		{elseif $HEADER->get('label') eq "Status"}
			{assign var=TASK_STATUS_HEADER value=vtranslate($HEADER->get('label'),$MODULE_NAME)}
		{/if}
	{/foreach}
	{foreach item=RELATED_RECORD from=$RELATED_RECORDS}
		{assign var=PERMISSIONS value=Users_Privileges_Model::isPermitted($RELATED_MODULE, 'EditView', $RELATED_RECORD->get('id'))}
		{assign var=RELATED_MODULE_MODEL value=Vtiger_Module_Model::getInstance('ProjectTask')}
		<div class="recentActivitiesContainer mk-proj-task-card">
			<div class="mk-proj-task-card__name textOverflowEllipsis width27em">
				<a href="{$RELATED_RECORD->getDetailViewUrl()}" id="{$MODULE}_{$RELATED_MODULE}_Related_Record_{$RELATED_RECORD->get('id')}" title="{$RELATED_RECORD->getDisplayValue('projecttaskname')}">
					<strong>{$RELATED_RECORD->getDisplayValue('projecttaskname')}</strong>
				</a>
			</div>
			<div class="mk-proj-task-card__fields">
				{assign var=FIELD_MODEL value=$RELATED_MODULE_MODEL->getField('projecttaskprogress')}
				{if $FIELD_MODEL->isViewableInDetailView()}
					<div class="mk-proj-task-meta mk-proj-task-meta--progress">
						<span class="mk-proj-task-meta__label">{$TASK_PROGRESS_HEADER}</span>
						<div class="mk-proj-task-meta__value">
							{if $PERMISSIONS && $FIELD_MODEL->isEditable()}
								<div class="dropdown">
									<a href="#" data-toggle="dropdown" class="dropdown-toggle mk-proj-task-pill mk-proj-task-pill--progress">
										<span class="fieldValue mk-proj-task-pill__value">{$RELATED_RECORD->getDisplayValue('projecttaskprogress')}</span>
										<b class="caret"></b>
									</a>
									<ul class="dropdown-menu widgetsList" data-recordid="{$RELATED_RECORD->getId()}" data-fieldname="projecttaskprogress"
										data-old-value="{$RELATED_RECORD->getDisplayValue('projecttaskprogress')}" data-mandatory="{$FIELD_MODEL->isMandatory()}">
										{assign var=PICKLIST_VALUES value=$FIELD_MODEL->getPicklistValues()}
										<li class="editTaskDetails emptyOption"><a>{vtranslate('LBL_SELECT_OPTION',$MODULE_NAME)}</a></li>
										{foreach item=PICKLIST_VALUE key=PICKLIST_NAME from=$PICKLIST_VALUES}
											<li class="editTaskDetails"><a>{$PICKLIST_VALUE}</a></li>
										{/foreach}
									</ul>
								</div>
							{else}
								<span class="mk-proj-task-pill mk-proj-task-pill--progress">{$RELATED_RECORD->getDisplayValue('projecttaskprogress')}</span>
							{/if}
						</div>
					</div>
				{/if}
				{assign var=FIELD_MODEL value=$RELATED_MODULE_MODEL->getField('projecttaskstatus')}
				{if $FIELD_MODEL->isViewableInDetailView()}
					<div class="mk-proj-task-meta mk-proj-task-meta--status">
						<span class="mk-proj-task-meta__label">{$TASK_STATUS_HEADER}</span>
						<div class="mk-proj-task-meta__value">
							{if $PERMISSIONS && $FIELD_MODEL->isEditable()}
								<div class="dropdown">
									<a href="#" data-toggle="dropdown" class="dropdown-toggle mk-proj-task-pill mk-proj-task-pill--status">
										<span class="fieldValue mk-proj-task-pill__value">{$RELATED_RECORD->getDisplayValue('projecttaskstatus')}</span>
										<b class="caret"></b>
									</a>
									<ul class="dropdown-menu widgetsList pull-right" data-recordid="{$RELATED_RECORD->getId()}" data-fieldname="projecttaskstatus"
										data-old-value="{$RELATED_RECORD->getDisplayValue('projecttaskstatus')}" data-mandatory="{$FIELD_MODEL->isMandatory()}" style="max-height: 200px; left: -64px;">
										{assign var=PICKLIST_VALUES value=$FIELD_MODEL->getPicklistValues()}
										<li class="editTaskDetails emptyOption" value=""><a>{vtranslate('LBL_SELECT_OPTION',$MODULE_NAME)}</a></li>
										{foreach item=PICKLIST_VALUE key=PICKLIST_NAME from=$PICKLIST_VALUES}
											<li class="editTaskDetails" value="{$PICKLIST_VALUE}"><a>{$PICKLIST_VALUE}</a></li>
										{/foreach}
									</ul>
								</div>
							{else}
								<span class="mk-proj-task-pill mk-proj-task-pill--status">{$RELATED_RECORD->getDisplayValue('projecttaskstatus')}</span>
							{/if}
						</div>
					</div>
				{/if}
			</div>
		</div>
	{/foreach}
	{if isset($TOTAL_RELATED_ENTRIES)}
		{assign var=TASK_TOTAL_COUNT value=$TOTAL_RELATED_ENTRIES}
	{else}
		{assign var=TASK_TOTAL_COUNT value=php7_count($RELATED_RECORDS)}
	{/if}
	{if $TASK_TOTAL_COUNT gt 5}
		<div class="mk-proj-tasks-more-footer clearfix">
			<a class="moreRecentTasks cursorPointer">{vtranslate('LBL_MORE',$MODULE_NAME)}</a>
		</div>
	{/if}
{/strip}
