{strip}
<div class="main-container clearfix">
	<div id="modnavigator" class="module-nav editViewModNavigator">
		<div class="mod-switcher-container">
			{include file="partials/Menubar.tpl"|vtemplate_path:$MODULE}
		</div>
	</div>
	<div class="editViewPageDiv viewContent">
		<div class="col-sm-12 col-xs-12 content-area {if $LEFTPANELHIDE eq '1'} full-width {/if}">
			<form class="form-horizontal recordEditView" id="EditView" name="edit" method="post" action="index.php" enctype="multipart/form-data">
				<div class="editViewHeader">
					<div class='row'>
						<div class="col-lg-12 col-md-12 col-lg-pull-0">
							{if $RECORD_ID neq ''}
								<h4 class="editHeader" style="margin-top:5px;">Edit Order - {$RECORD->getName()}</h4>
							{else}
								<h4 class="editHeader" style="margin-top:5px;">Create New Order</h4>
							{/if}
						</div>
					</div>
				</div>
				<div class="editViewBody">
					<div class="editViewContents">
						{if $TOOLS_VALIDATION_ERROR}
							<div class="alert alert-danger" style="margin-bottom: 12px;">
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

						<div class='fieldBlockContainer'>
							<h4 class='fieldBlockHeader'>Order Info</h4><hr>
							<table class="table table-borderless">
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

						<div class='fieldBlockContainer'>
							<h4 class='fieldBlockHeader'>Approval</h4><hr>
							<table class="table table-borderless">
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
								<tr>
									{assign var=FIELD_MODEL value=$FIELDS_MAP.approval_note}
									{assign var=FIELD_NAME value='approval_note'}
									{assign var=FIELD_INFO value=$FIELD_MODEL->getFieldInfo()}
									<td class="fieldLabel alignMiddle">Approval Note</td>
									<td class="fieldValue" colspan="3">{include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE)}</td>
								</tr>
							</table>
						</div>

						<div class='fieldBlockContainer'>
							<h4 class='fieldBlockHeader'>System</h4><hr>
							<table class="table table-borderless">
								<tr>
									{assign var=FIELD_MODEL value=$FIELDS_MAP.created_user_id}
									{assign var=FIELD_NAME value='created_user_id'}
									{assign var=FIELD_INFO value=$FIELD_MODEL->getFieldInfo()}
									<td class="fieldLabel alignMiddle">Ordered By</td>
									<td class="fieldValue">{include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE)}</td>
									<td class="fieldLabel alignMiddle">Created Time</td>
									<td class="fieldValue">{$RECORD->getDisplayValue('createdtime')}</td>
								</tr>
							</table>
						</div>
					</div>
				</div>

				<div class='modal-overlay-footer clearfix'>
					<div class="row clearfix">
						<div class='textAlignCenter col-lg-12 col-md-12 col-sm-12 '>
							<button type='submit' class='btn btn-success saveButton'>{vtranslate('LBL_SAVE', $MODULE)}</button>&nbsp;&nbsp;
							<a class='cancelLink' href="javascript:history.back()" type="reset">{vtranslate('LBL_CANCEL', $MODULE)}</a>
						</div>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>
{/strip}
