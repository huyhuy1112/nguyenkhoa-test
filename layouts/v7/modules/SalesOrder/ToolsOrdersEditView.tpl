{* Tools Order Create/Edit — dashboard shell + custom fields *}
{strip}
{assign var=MK_LIST_URL value='index.php?module=SalesOrder&view=List&app=TOOLS'}
{assign var=MK_IS_EDIT value=(!empty($RECORD_ID))}
<div class="mk-so-tools-create{if $MK_IS_EDIT} mk-so-tools-create--edit{/if}" id="mkSoToolsCreateWorkspace" data-mk-tools-order-edit="1">
	<header class="mk-so-tools-page-head">
		<nav class="mk-so-tools-page-head__crumb" aria-label="Breadcrumb">
			<a href="index.php?module=Home&view=MainPage&app=TOOLS">{vtranslate('LBL_HOME', 'Vtiger')}</a>
			<span aria-hidden="true">/</span>
			<a href="{$MK_LIST_URL}">{vtranslate('SalesOrder', $MODULE)}</a>
			<span aria-hidden="true">/</span>
			{if $MK_IS_EDIT}<span aria-current="page">Edit Order</span>{else}<span aria-current="page">Create New Order</span>{/if}
		</nav>
		<div class="mk-so-tools-page-head__panel">
			<div class="mk-so-tools-page-head__row">
				<div>
					<p class="mk-so-tools-page-head__eyebrow">{vtranslate('SalesOrder', $MODULE)}</p>
					{if $MK_IS_EDIT}
						<h1 class="mk-so-tools-page-head__title">Edit Order</h1>
						<p class="mk-so-tools-page-head__sub">{$RECORD->getName()|escape}</p>
					{else}
						<h1 class="mk-so-tools-page-head__title">Create New Order</h1>
						<p class="mk-so-tools-page-head__sub">Internal order request — fill in details and submit for approval.</p>
					{/if}
				</div>
				<div class="mk-so-tools-page-head__actions">
					<a class="mk-so-tools-btn mk-so-tools-btn--ghost" href="{$MK_LIST_URL}">{vtranslate('LBL_CANCEL', $MODULE)}</a>
					<button type="button" class="mk-so-tools-btn mk-so-tools-btn--primary" id="mkSoToolsSaveTop" data-action="save">
						{vtranslate('LBL_SAVE', $MODULE)}
					</button>
				</div>
			</div>
		</div>
	</header>

	<div class="mk-so-tools-form-host" id="mkSoToolsFormHost">
		<form class="form-horizontal recordEditView" id="EditView" name="edit" method="post" action="index.php" enctype="multipart/form-data">
			{if $TOOLS_VALIDATION_ERROR}
				<div class="mk-so-tools-alert mk-so-tools-alert--error" role="alert">
					{$TOOLS_VALIDATION_ERROR|escape:'html'}
				</div>
			{/if}
			<input type="hidden" name="module" value="{$MODULE}" />
			<input type="hidden" name="action" value="Save" />
			<input type="hidden" name="record" value="{$RECORD_ID}" />
			<input type="hidden" name="appName" value="&app=TOOLS" />
			{if $IS_RELATION_OPERATION}
				<input type="hidden" name="sourceModule" value="{$SOURCE_MODULE}" />
				<input type="hidden" name="sourceRecord" value="{$SOURCE_RECORD}" />
				<input type="hidden" name="relationOperation" value="{$IS_RELATION_OPERATION}" />
			{/if}

			<div class="fieldBlockContainer mk-so-tools-block" data-block="order_info">
				<h4 class="fieldBlockHeader mk-so-tools-block__header">Order Info</h4>
				<table class="table table-borderless mk-so-tools-fields-table">
					<tr>
						{assign var=FIELD_MODEL value=$FIELDS_MAP.subject}
						{assign var=FIELD_NAME value='subject'}
						{assign var=FIELD_INFO value=$FIELD_MODEL->getFieldInfo()}
						<td class="fieldLabel alignMiddle">Order Name <span class="redColor">*</span></td>
						<td class="fieldValue">{include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE)}</td>
						{assign var=FIELD_MODEL value=$FIELDS_MAP.team_group}
						{assign var=FIELD_NAME value='team_group'}
						{assign var=FIELD_INFO value=$FIELD_MODEL->getFieldInfo()}
						<td class="fieldLabel alignMiddle">Team Group</td>
						<td class="fieldValue">{include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE)}</td>
					</tr>
					<tr>
						{assign var=FIELD_MODEL value=$FIELDS_MAP.purpose}
						{assign var=FIELD_NAME value='purpose'}
						{assign var=FIELD_INFO value=$FIELD_MODEL->getFieldInfo()}
						<td class="fieldLabel alignMiddle">Purpose</td>
						<td class="fieldValue">{include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE)}</td>
						{assign var=FIELD_MODEL value=$FIELDS_MAP.internal_cost}
						{assign var=FIELD_NAME value='internal_cost'}
						{assign var=FIELD_INFO value=$FIELD_MODEL->getFieldInfo()}
						<td class="fieldLabel alignMiddle">Cost</td>
						<td class="fieldValue">{include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE)}</td>
					</tr>
					<tr>
						{assign var=FIELD_MODEL value=$FIELDS_MAP.needed_time}
						{assign var=FIELD_NAME value='needed_time'}
						{assign var=FIELD_INFO value=$FIELD_MODEL->getFieldInfo()}
						<td class="fieldLabel alignMiddle">Needed Time</td>
						<td class="fieldValue">{include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE)}</td>
						<td class="fieldLabel"></td>
						<td class="fieldValue"></td>
					</tr>
				</table>
			</div>

			<div class="fieldBlockContainer mk-so-tools-block" data-block="approval">
				<h4 class="fieldBlockHeader mk-so-tools-block__header">Approval</h4>
				<table class="table table-borderless mk-so-tools-fields-table">
					<tr>
						{assign var=FIELD_MODEL value=$FIELDS_MAP.internal_order_status}
						{assign var=FIELD_NAME value='internal_order_status'}
						{assign var=FIELD_INFO value=$FIELD_MODEL->getFieldInfo()}
						<td class="fieldLabel alignMiddle">Status</td>
						<td class="fieldValue">{include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE)}</td>
						{assign var=FIELD_MODEL value=$FIELDS_MAP.approved_by}
						{assign var=FIELD_NAME value='approved_by'}
						{assign var=FIELD_INFO value=$FIELD_MODEL->getFieldInfo()}
						<td class="fieldLabel alignMiddle">Approved By</td>
						<td class="fieldValue">{include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE)}</td>
					</tr>
					<tr class="mk-so-tools-row--full">
						{assign var=FIELD_MODEL value=$FIELDS_MAP.approval_note}
						{assign var=FIELD_NAME value='approval_note'}
						{assign var=FIELD_INFO value=$FIELD_MODEL->getFieldInfo()}
						<td class="fieldLabel alignMiddle">Approval Note</td>
						<td class="fieldValue" colspan="3">{include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE)}</td>
					</tr>
				</table>
			</div>

			<div class="fieldBlockContainer mk-so-tools-block" data-block="system">
				<h4 class="fieldBlockHeader mk-so-tools-block__header">System</h4>
				<table class="table table-borderless mk-so-tools-fields-table">
					<tr>
						{assign var=FIELD_MODEL value=$FIELDS_MAP.created_user_id}
						{assign var=FIELD_NAME value='created_user_id'}
						{assign var=FIELD_INFO value=$FIELD_MODEL->getFieldInfo()}
						<td class="fieldLabel alignMiddle">Ordered By</td>
						<td class="fieldValue">{include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE)}</td>
						<td class="fieldLabel alignMiddle">Created Time</td>
						<td class="fieldValue mk-so-tools-readonly">{$RECORD->getDisplayValue('createdtime')}</td>
					</tr>
				</table>
			</div>

			<div class="modal-overlay-footer mk-so-tools-form-footer">
				<button type="submit" class="btn btn-success saveButton">{vtranslate('LBL_SAVE', $MODULE)}</button>
				<a class="cancelLink" href="{$MK_LIST_URL}">{vtranslate('LBL_CANCEL', $MODULE)}</a>
			</div>
		</form>
	</div>
</div>
{/strip}
