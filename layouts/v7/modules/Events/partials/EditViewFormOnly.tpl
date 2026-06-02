{* Events create/edit: stock EditView without legacy #modnavigator. *}
{strip}
	<div class="main-container clearfix">
		<div class="editViewPageDiv viewContent">
			<div class="col-sm-12 col-xs-12 content-area full-width">
				<form class="form-horizontal recordEditView" id="EditView" name="edit" method="post" action="index.php" enctype="multipart/form-data">
					<div class="editViewHeader hide">
						<div class='row'>
							<div class="col-lg-12 col-md-12 col-lg-pull-0">
								{assign var=SINGLE_MODULE_NAME value='SINGLE_'|cat:$MODULE}
								{if $RECORD_ID neq ''}
									<h4 class="editHeader">{vtranslate('LBL_EDITING', $MODULE)} {vtranslate($SINGLE_MODULE_NAME, $MODULE)}</h4>
								{else}
									<h4 class="editHeader">{vtranslate('LBL_CREATING_NEW', $MODULE)} {vtranslate($SINGLE_MODULE_NAME, $MODULE)}</h4>
								{/if}
							</div>
						</div>
					</div>
					<div class="editViewBody">
						<div class="editViewContents">
							{assign var=WIDTHTYPE value=$USER_MODEL->get('rowheight')}
							<input type="hidden" name="module" value="{$MODULE}" />
							<input type="hidden" name="action" value="Save" />
							<input type="hidden" name="record" value="{$RECORD_ID}" />
							<input type="hidden" name="defaultCallDuration" value="{$USER_MODEL->get('callduration')}" />
							<input type="hidden" name="defaultOtherEventDuration" value="{$USER_MODEL->get('othereventduration')}" />
							<input type="hidden" name="appName" value="&app={$SELECTED_MENU_CATEGORY|default:'MANAGEMENT'}" />
							{if $IS_RELATION_OPERATION }
								<input type="hidden" name="sourceModule" value="{$SOURCE_MODULE}" />
								<input type="hidden" name="sourceRecord" value="{$SOURCE_RECORD}" />
								<input type="hidden" name="relationOperation" value="{$IS_RELATION_OPERATION}" />
							{/if}
							{if $RETURN_VIEW}
								<input type="hidden" name="returnmodule" value="{$RETURN_MODULE}" />
								<input type="hidden" name="returnview" value="{$RETURN_VIEW}" />
								<input type="hidden" name="returnrecord" value="{$RETURN_RECORD}" />
								<input type="hidden" name="returntab_label" value="{$RETURN_RELATED_TAB}" />
								<input type="hidden" name="returnrelatedModule" value="{$RETURN_RELATED_MODULE}" />
								<input type="hidden" name="returnpage" value="{$RETURN_PAGE}" />
								<input type="hidden" name="returnviewname" value="{$RETURN_VIEW_NAME}" />
								<input type="hidden" name="returnsearch_params" value='{Vtiger_Functions::jsonEncode($RETURN_SEARCH_PARAMS)}' />
								<input type="hidden" name="returnsearch_key" value={$RETURN_SEARCH_KEY} />
								<input type="hidden" name="returnsearch_value" value={$RETURN_SEARCH_VALUE} />
								<input type="hidden" name="returnoperator" value={$RETURN_SEARCH_OPERATOR} />
								<input type="hidden" name="returnsortorder" value={$RETURN_SORTBY} />
								<input type="hidden" name="returnorderby" value="{$RETURN_ORDERBY}" />
								<input type="hidden" name="returnmode" value={$RETURN_MODE} />
								<input type="hidden" name="returnrelationId" value="{$RETURN_RELATION_ID}" />
								<input type="hidden" name="returnparent" value="{$RETURN_PARENT_MODULE}" />
							{/if}
							{include file="partials/EditViewContents.tpl"|@vtemplate_path:$MODULE}
						</div>
					</div>
					<div class="modal-overlay-footer clearfix mk-event-form-footer">
						<div class="row clearfix">
							<div class="textAlignCenter col-lg-12 col-md-12 col-sm-12">
								<button type="submit" class="btn btn-success saveButton">{vtranslate('LBL_SAVE', $MODULE)}</button>
								<a class="cancelLink" href="{$MK_CALENDAR_URL|default:'index.php?module=Calendar&view=Calendar&app=MANAGEMENT'}" type="reset">{vtranslate('LBL_CANCEL', $MODULE)}</a>
							</div>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
{/strip}
