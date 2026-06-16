{*+**********************************************************************************
 * Project Detail (MANAGEMENT) — ModComments related tab (premium UI)
 ************************************************************************************}
{strip}
<form id="detailView" method="POST">
	{assign var="COMMENT_TEXTAREA_DEFAULT_ROWS" value="4"}
	{assign var="PRIVATE_COMMENT_MODULES" value=Vtiger_Functions::getPrivateCommentModules()}
	{assign var=IS_CREATABLE value=$COMMENTS_MODULE_MODEL->isPermitted('CreateView')}
	{assign var=IS_EDITABLE value=$COMMENTS_MODULE_MODEL->isPermitted('EditView')}

	<div class="commentContainer commentsRelatedContainer mk-proj-comments-panel container-fluid">
		{if $IS_CREATABLE}
			<div class="commentTitle row">
				<div class="addCommentBlock mk-project-comment-composer">
					<div class="mk-project-comment-composer__body">
						<div class="commentTextArea">
							<textarea name="commentcontent" class="commentcontent form-control" placeholder="{vtranslate('LBL_POST_YOUR_COMMENT_HERE', $MODULE_NAME)}" rows="{$COMMENT_TEXTAREA_DEFAULT_ROWS}"></textarea>
						</div>
					</div>
					<div class="mk-project-comment-composer__footer">
						<div class="mk-project-comment-composer__footer-left">
							{if $FIELD_MODEL->getProfileReadWritePermission()}
								{include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE_NAME) MODULE="ModComments"}
							{/if}
						</div>
						<div class="mk-project-comment-composer__footer-right">
							{if in_array($MODULE_NAME, $PRIVATE_COMMENT_MODULES)}
								<div class="mk-project-comment-composer__internal">
									<label>
										<input type="checkbox" id="is_private" checked>&nbsp;{vtranslate('LBL_INTERNAL_COMMENT')}
									</label>
									<i class="fa fa-question-circle cursorPointer" data-toggle="tooltip" data-placement="top" data-original-title="{vtranslate('LBL_INTERNAL_COMMENT_INFO')}"></i>
								</div>
							{/if}
							<button class="btn btn-success btn-sm saveComment detailViewSaveComment" type="button" data-mode="add">{vtranslate('LBL_POST', $MODULE_NAME)}</button>
						</div>
					</div>
				</div>
			</div>
		{/if}

		<div class="showcomments mk-proj-comments-body container-fluid row">
			<div class="recentCommentsHeader row mk-proj-comments-header">
				<h4 class="display-inline-block col-lg-5 textOverflowEllipsis mk-proj-comments-title" title="{vtranslate('LBL_RECENT_COMMENTS', $MODULE_NAME)}">
					{vtranslate('LBL_COMMENTS',$MODULE)}
				</h4>
				<div class="col-lg-7 mk-proj-comments-header__right">
					<div class="mk-proj-comments-filter">
						<i class="fa fa-search mk-proj-comments-filter__icon" aria-hidden="true"></i>
						<input type="text" class="mk-proj-comments-filter__input inputElement" placeholder="{vtranslate('LBL_SEARCH',$MODULE)}" autocomplete="off" />
					</div>
					{if $MODULE_NAME ne 'Leads'}
						<div class="mk-proj-comments-rollup">
							<span class="mk-proj-comments-rollup__label">{vtranslate('LBL_ROLL_UP',$QUALIFIED_MODULE)}</span>
							<i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="{vtranslate('LBL_ROLLUP_COMMENTS_INFO',$QUALIFIED_MODULE)}"></i>
							<input type="checkbox" class="bootstrap-switch" id="rollupcomments" hascomments="1" startindex="{$STARTINDEX}" data-view="relatedlist" rollupid="{$ROLLUPID}"
								rollup-status="{$ROLLUP_STATUS}" module="{$MODULE_NAME}" record="{$MODULE_RECORD}" checked data-on-color="success"/>
						</div>
					{/if}
				</div>
			</div>

			<div class="commentsList commentsBody marginBottom15 mk-proj-comments-list">
				{include file='CommentsList.tpl'|@vtemplate_path COMMENTS_MODULE_MODEL=$COMMENTS_MODULE_MODEL IS_CREATABLE=$IS_CREATABLE IS_EDITABLE=$IS_EDITABLE}
			</div>

			<div class="hide basicAddCommentBlock container-fluid">
				<div class="commentTextArea row">
					<textarea name="commentcontent" class="commentcontent" placeholder="{vtranslate('LBL_POST_YOUR_COMMENT_HERE', $MODULE_NAME)}" rows="{$COMMENT_TEXTAREA_DEFAULT_ROWS}"></textarea>
				</div>
				<div class="pull-right row">
					{if in_array($MODULE_NAME, $PRIVATE_COMMENT_MODULES)}
						<input type="checkbox" id="is_private" checked>&nbsp;&nbsp;{vtranslate('LBL_INTERNAL_COMMENT')}&nbsp;&nbsp;
					{/if}
					<button class="btn btn-success btn-sm saveComment detailViewSaveComment" type="button" data-mode="add">{vtranslate('LBL_POST', $MODULE_NAME)}</button>
					<a href="javascript:void(0);" class="cursorPointer closeCommentBlock cancelLink" type="reset">{vtranslate('LBL_CANCEL', $MODULE_NAME)}</a>
				</div>
			</div>

			<div class="hide basicEditCommentBlock container-fluid">
				<div class="row" style="padding-bottom: 10px;">
					<input style="width:100%;height:30px;" type="text" name="reasonToEdit" placeholder="{vtranslate('LBL_REASON_FOR_CHANGING_COMMENT', $MODULE_NAME)}" class="input-block-level"/>
				</div>
				<div class="row">
					<div class="commentTextArea">
						<textarea name="commentcontent" class="commentcontenthidden" placeholder="{vtranslate('LBL_ADD_YOUR_COMMENT_HERE', $MODULE_NAME)}" rows="{$COMMENT_TEXTAREA_DEFAULT_ROWS}"></textarea>
					</div>
				</div>
				<input type="hidden" name="is_private">
				<div class="pull-right row">
					<button class="btn btn-success btn-sm saveComment detailViewSaveComment" type="button" data-mode="edit">{vtranslate('LBL_POST', $MODULE_NAME)}</button>
					<a href="javascript:void(0);" class="cursorPointer closeCommentBlock cancelLink" type="reset">{vtranslate('LBL_CANCEL', $MODULE_NAME)}</a>
				</div>
			</div>
		</div>
	</div>
</form>
{/strip}
