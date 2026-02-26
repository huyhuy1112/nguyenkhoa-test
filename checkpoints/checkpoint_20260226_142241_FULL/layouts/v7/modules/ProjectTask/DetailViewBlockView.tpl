{*+**********************************************************************************
 * ProjectTask Detail Block - subtask uses taskDetailFormOuter style (detailFieldOuter, smLabelCls)
 ************************************************************************************}
{strip}
{if $MODULE_NAME eq 'ProjectTask' && $RECORD->get('parent_projecttaskid')}
{* Subtask: taskDetailPanel form style - Start/Due, Labels, Assignees, Time, Progress *}
{assign var=START_FIELD value=null}
{assign var=END_FIELD value=null}
{assign var=OWNER_FIELD value=null}
{assign var=PROGRESS_FIELD value=null}
{foreach key=BLK item=FLDS from=$RECORD_STRUCTURE}
    {if !$START_FIELD && isset($FLDS['startdate'])}{assign var=START_FIELD value=$FLDS['startdate']}{/if}
    {if !$END_FIELD && isset($FLDS['enddate'])}{assign var=END_FIELD value=$FLDS['enddate']}{/if}
    {if !$OWNER_FIELD && isset($FLDS['smownerid'])}{assign var=OWNER_FIELD value=$FLDS['smownerid']}{/if}
    {if !$PROGRESS_FIELD && isset($FLDS['projecttaskprogress'])}{assign var=PROGRESS_FIELD value=$FLDS['projecttaskprogress']}{/if}
{/foreach}
<div class="taskDetailFormOuter">
    <div class="detailFieldOuter">
        <label class="smLabelCls"><i class="far fa-calendar-alt"></i> Start/Due</label>
        <div class="borderLeft blankFields multiBlankField">
            <span class="startDateField lightGray"><i class="fal fa-calendar-alt"></i> {if $START_FIELD}{include file=vtemplate_path($START_FIELD->getUITypeModel()->getDetailViewTemplateName(),$MODULE_NAME) FIELD_MODEL=$START_FIELD USER_MODEL=$USER_MODEL MODULE=$MODULE_NAME RECORD=$RECORD}{else}-{/if}</span>
            <i class="far fa-long-arrow-right"></i>
            <span class="dueDateField lightGray"><i class="fal fa-calendar-alt"></i> {if $END_FIELD}{include file=vtemplate_path($END_FIELD->getUITypeModel()->getDetailViewTemplateName(),$MODULE_NAME) FIELD_MODEL=$END_FIELD USER_MODEL=$USER_MODEL MODULE=$MODULE_NAME RECORD=$RECORD}{else}-{/if}</span>
        </div>
    </div>
    <div class="detailFieldOuter">
        <label class="smLabelCls"><i class="far fa-tags"></i> Labels</label>
        <div class="borderLeft blankFields labelsFieldDetailMain">
            <span class="labelsFieldDetail mDash gray">{vtranslate('LBL_SELECT', 'Vtiger')}</span>
        </div>
    </div>
    <div class="detailFieldOuter">
        <label class="smLabelCls"><i class="far fa-users"></i> Assignees</label>
        <div class="borderLeft peopleTagFieldCard">
            <div class="peopleTagFieldCardOuter">
                {if $OWNER_FIELD}
                <span class="assignee-badge">{include file=vtemplate_path($OWNER_FIELD->getUITypeModel()->getDetailViewTemplateName(),$MODULE_NAME) FIELD_MODEL=$OWNER_FIELD USER_MODEL=$USER_MODEL MODULE=$MODULE_NAME RECORD=$RECORD}</span>
                {/if}
                <span class="morePeople add">+</span>
            </div>
        </div>
    </div>
    <div class="detailFieldOuter">
        <label class="smLabelCls"><i class="far fa-clock"></i> Time</label>
        <div class="borderLeft blankFields">
            <span class="loggedTime mDash">{vtranslate('LBL_ADD_LOGGED_TIME', $MODULE_NAME)}</span> / <span class="estimatedTime mDash">{vtranslate('LBL_ADD_ESTIMATED_TIME', $MODULE_NAME)}</span>
        </div>
    </div>
    <div class="detailFieldOuter">
        <label class="smLabelCls"><i class="fal fa-chart-line"></i> Progress</label>
        <div class="borderLeft blankFields">
            {if $PROGRESS_FIELD}
            <span class="progressValue">{include file=vtemplate_path($PROGRESS_FIELD->getUITypeModel()->getDetailViewTemplateName(),$MODULE_NAME) FIELD_MODEL=$PROGRESS_FIELD USER_MODEL=$USER_MODEL MODULE=$MODULE_NAME RECORD=$RECORD}</span>
            {else}-{/if}
        </div>
    </div>
    <div class="customFieldOuter addCustomFieldBtnBg">
        <a class="manageBtn addCf companyColor cursor dottedLink">{vtranslate('LBL_ADD_FIELD', $MODULE_NAME)}</a> or <a class="manageBtn manageCf companyColor cursor dottedLink">{vtranslate('LBL_MANAGE_FIELDS', $MODULE_NAME)}</a>
    </div>
</div>
<a class="btn btn-default btn-attach-files" href="#"><i class="far fa-paperclip"></i> {vtranslate('LBL_ATTACH_FILE', $MODULE_NAME)}</a>
{else}
{* Standard: use Vtiger DetailViewBlockView *}
{include file='DetailViewBlockView.tpl'|@vtemplate_path:'Vtiger' RECORD_STRUCTURE=$RECORD_STRUCTURE MODULE_NAME=$MODULE_NAME BLOCK_LIST=$BLOCK_LIST USER_MODEL=$USER_MODEL RECORD=$RECORD}
{/if}
{/strip}
