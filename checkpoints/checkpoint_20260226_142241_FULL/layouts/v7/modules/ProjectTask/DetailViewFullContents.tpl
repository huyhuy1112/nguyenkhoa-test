{*+**********************************************************************************
 * ProjectTask Detail - layout giống taskDetailPanel (HTML mẫu)
 * Subtask: descriptionCardArea + taskDetailFormOuter (từ BlockView)
 * Task cha: block thường + taskListPanel (Subtasks)
 ************************************************************************************}
{strip}
<form id="detailView" method="POST">
    {if $RECORD->get('parent_projecttaskid')}
    <div class="descriptionCardArea taskDetailDescription">
        <div class="customEmptyText gray" data-empty-text="{vtranslate('LBL_DESCRIPTION', $MODULE_NAME)}">{if $RECORD->get('description')}{$RECORD->get('description')|nl2br}{/if}</div>
    </div>
    {/if}
    {include file='DetailViewBlockView.tpl'|@vtemplate_path:$MODULE_NAME RECORD_STRUCTURE=$RECORD_STRUCTURE MODULE_NAME=$MODULE_NAME}

    {* Subtasks - chỉ khi xem task cha, cấu trúc giống HTML: taskListPanel, tasksListToolbar, quickAddDummyField *}
    {if !$RECORD->get('parent_projecttaskid')}
    <div class="x-panel taskListPanel subtasks singleRowList" data-record-id="{$RECORD->getId()}" role="grid">
        <div class="x-panel-header x-header x-header-noborder">
            <div class="x-title x-panel-header-title">Subtasks</div>
        </div>
        <div class="tasksListToolbar x-toolbar x-docked-top">
            <div class="quickAddDummyField">
                <input type="text" class="form-control quickAddTaskInput" placeholder="Add task and hit enter/return key" />
            </div>
            <button type="button" class="btn btn-primary btn-sm taskListAddBtn">{vtranslate('LBL_ADD', $MODULE_NAME)}</button>
        </div>
        <div class="subtaskListBotToolbar x-toolbar x-docked-bottom" style="padding:6px 0 6px 12px;">
            <button type="button" class="btn btn-default btn-sm taskListCancelBtn">{vtranslate('LBL_CANCEL', $MODULE_NAME)}</button>
        </div>
        <div class="task-list-container">
            <div class="x-grid-empty task-list-empty">{vtranslate('LBL_NO_SUBTASKS', $MODULE_NAME)}</div>
            <ul class="task-list list-unstyled"></ul>
        </div>
    </div>
    {/if}
</form>
{/strip}
