{*+**********************************************************************************
 * ProjectTask Detail Header - subtask style like taskDetailToolbar (Complete, #, ellipsis)
 ************************************************************************************}
{strip}
<div class="detailview-header-block projecttask-detail-header {if $RECORD->get('parent_projecttaskid')}projecttask-subtask-header taskDetailToolbar{/if}">
    <div class="detailview-header">
        <div class="row">
            {if $RECORD->get('parent_projecttaskid')}
            {* taskDetailToolbar style: taskCurrentStage, taskIdContainer, ellipsis *}
            <div class="projecttask-subtask-header-content taskDetailToolbar-inner">
                <div class="taskCurrentStage taskCompleteTrigger">
                    <div class="taskCheckBox"><span class="taskComplete {if $RECORD->get('projecttaskstatus') == 'Completed' || $RECORD->get('projecttaskprogress') >= 100}checked{/if}"></span></div>
                    <input type="checkbox" class="subtask-complete-checkbox hide" {if $RECORD->get('projecttaskstatus') == 'Completed' || $RECORD->get('projecttaskprogress') >= 100}checked{/if} />
                    <span class="stageName inDetailSec">{vtranslate('Completed', $MODULE_NAME)}</span>
                </div>
                <div class="taskIdContainer cursor">
                    <div class="taskIdinDetail">
                        <span>#{$RECORD->get('projecttask_no')|default:$RECORD->getId()}</span>
                        <i class="fa fa-angle-down"></i>
                    </div>
                </div>
                <div class="taskDetailToolbar-fill"></div>
                <div class="taskDetailToolbar-actions">{include file="DetailViewActions.tpl"|vtemplate_path:$MODULE}</div>
            </div>
            {* Breadcrumb: tdProjectName, tdListName, breadcrumb (parent link) *}
            <div class="taskDetailBreadcrumbWrap">
                <div class="tdProjectName"><i class="fa fas fa-circle" style="color:#3F9843"></i> {assign var=PROJECT_ID value=$RECORD->get('projectid')}{if $PROJECT_ID}{assign var=PROJECT_RECORD value=Vtiger_Record_Model::getInstanceById($PROJECT_ID, 'Project')}{$PROJECT_RECORD->getName()|default:vtranslate('LBL_PROJECT', $MODULE_NAME)}{else}{vtranslate('LBL_PROJECT', $MODULE_NAME)}{/if} </div>
                <div class="tdListName"><i class="far fa-angle-right"></i> {vtranslate('LBL_TASKS_LIST', $MODULE_NAME)}</div>
                {assign var=PARENT_ID value=$RECORD->get('parent_projecttaskid')}
                <div class="breadcrumb"><i class="far fa-angle-right"></i> {if $PARENT_ID}{assign var=PARENT_RECORD value=Vtiger_Record_Model::getInstanceById($PARENT_ID, 'ProjectTask')}<a class="taskBack" href="index.php?module=ProjectTask&view=Detail&record={$PARENT_ID}">{$PARENT_RECORD->getName()}</a>{else}<span class="taskBack">{$RECORD->getName()}</span>{/if}</div>
            </div>
            <h4 class="titleCardArea subtask-title">{$RECORD->getName()}</h4>
            {else}
            {* Standard task: use default header *}
            {include file="DetailViewHeaderTitle.tpl"|vtemplate_path:'Vtiger' MODULE_MODEL=$MODULE_MODEL RECORD=$RECORD}
            {include file="DetailViewActions.tpl"|vtemplate_path:$MODULE}
            {/if}
        </div>
    </div>
</div>
{/strip}
