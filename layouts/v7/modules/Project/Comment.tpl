{*+**********************************************************************************
 * Project MANAGEMENT — comment row (fixed avatar + layout)
 ************************************************************************************}
{strip}
	{if !isset($COMMENTS_MODULE_MODEL) && isset($COMMENT_MODULE_MODEL)}
		{assign var=COMMENTS_MODULE_MODEL value=$COMMENT_MODULE_MODEL}
	{/if}
	{assign var="PRIVATE_COMMENT_MODULES" value=Vtiger_Functions::getPrivateCommentModules()}

	<div class="commentDiv mk-proj-comment-item {if $COMMENT->get('is_private')}privateComment{/if}">
		<div class="singleComment">
			<input type="hidden" name="is_private" value="{$COMMENT->get('is_private')}">
			{assign var=PARENT_COMMENT_MODEL value=$COMMENT->getParentCommentModel()}
			{assign var=CHILD_COMMENTS_MODEL value=$COMMENT->getChildComments()}
			{assign var=CREATOR_NAME value={decode_html($COMMENT->getCommentedByName())}}
			{assign var=IMAGE_PATH value=$COMMENT->getImagePath()}

			<div class="mk-proj-comment-item__row" data-commentid="{$COMMENT->getId()}" data-parentcommentid="{$COMMENT->get('parent_comments')}" data-relatedto="{$COMMENT->get('related_to')}">
				<div class="mk-proj-comment-avatar" aria-hidden="true">
					{if !empty($IMAGE_PATH)}
						<img class="mk-proj-comment-avatar__photo" src="{$IMAGE_PATH}" alt="{$CREATOR_NAME|escape:'html'}">
					{else}
						<span class="mk-proj-comment-avatar__initials">{$CREATOR_NAME|substr:0:2|escape:"html"}</span>
					{/if}
				</div>
				<div class="mk-proj-comment-item__body">
					<div class="mk-proj-comment-item__meta">
						<span class="creatorName">{$CREATOR_NAME}</span>
						{if isset ($ROLLUP_STATUS) && $ROLLUP_STATUS and $COMMENT->get('module') ne $MODULE_NAME}
							{assign var=SINGULR_MODULE value='SINGLE_'|cat:$COMMENT->get('module')}
							{assign var=ENTITY_NAME value=getEntityName($COMMENT->get('module'), array($COMMENT->get('related_to')))}
							<span class="mk-proj-comment-item__rollup text-muted">
								{vtranslate('LBL_ON','Vtiger')}&nbsp;
								{vtranslate($SINGULR_MODULE, $COMMENT->get('module'))}&nbsp;
								<a href="index.php?module={$COMMENT->get('module')}&view=Detail&record={$COMMENT->get('related_to')}">
									{$ENTITY_NAME[$COMMENT->get('related_to')]}
								</a>
							</span>
						{/if}
						<span class="commentTime text-muted cursorDefault">
							<small title="{Vtiger_Util_Helper::formatDateTimeIntoDayString($COMMENT->getCommentedTime())}">{Vtiger_Util_Helper::formatCommentDateTime($COMMENT->getCommentedTime())}</small>
						</span>
						{if in_array($MODULE_NAME, $PRIVATE_COMMENT_MODULES)}
							<span class="mk-proj-comment-item__privacy">
								{if $COMMENT->get('is_private')}
									<i class="fa fa-lock" data-toggle="tooltip" data-placement="top" data-original-title="{vtranslate('LBL_INTERNAL_COMMENT_TOOTLTIP',$MODULE)}"></i>
								{else}
									<i class="fa fa-unlock" data-toggle="tooltip" data-placement="top" data-original-title="{vtranslate('LBL_EXTERNAL_COMMENT_TOOTLTIP',$MODULE)}"></i>
								{/if}
							</span>
						{/if}
					</div>

					<div class="commentInfoContentBlock">
						<span class="commentInfoContent">
							{$COMMENT->getCommentContentForDisplay() nofilter}
						</span>
					</div>

					<div class="commentActionsContainer">
						<span class="commentActions">
							{if isset ($CHILDS_ROOT_PARENT_MODEL) && $CHILDS_ROOT_PARENT_MODEL}
								{assign var=CHILDS_ROOT_PARENT_ID value=$CHILDS_ROOT_PARENT_MODEL->getId()}
							{/if}

							{if $COMMENTS_MODULE_MODEL->isPermitted('EditView')}
								{if isset ($CHILDS_ROOT_PARENT_MODEL) && $CHILDS_ROOT_PARENT_MODEL}
									{assign var=CHILDS_ROOT_PARENT_ID value=$CHILDS_ROOT_PARENT_MODEL->getId()}
								{/if}
								<a href="javascript:void(0);" class="cursorPointer replyComment feedback">
									{vtranslate('LBL_REPLY',$MODULE_NAME)}
								</a>
								{if $CURRENTUSER->getId() eq $COMMENT->get('userid')}
									<a href="javascript:void(0);" class="cursorPointer editComment feedback">
										{vtranslate('LBL_EDIT',$MODULE_NAME)}
									</a>
								{/if}
							{/if}
							{assign var=SHOW_COMMENT_ACTION_SEP value=($COMMENTS_MODULE_MODEL->isPermitted('EditView'))}
							{include file="partials/CommentAdminDeleteLink.tpl"|vtemplate_path:'Vtiger' COMMENT=$COMMENT MODULE_NAME=$MODULE_NAME CAN_ADMIN_DELETE_COMMENTS=$CAN_ADMIN_DELETE_COMMENTS SHOW_COMMENT_ACTION_SEP=$SHOW_COMMENT_ACTION_SEP}

							{assign var=CHILD_COMMENTS_COUNT value=$COMMENT->getChildCommentsCount()}
							{if $CHILD_COMMENTS_MODEL neq null and (isset($CHILDS_ROOT_PARENT_ID)&& $CHILDS_ROOT_PARENT_ID neq $PARENT_COMMENT_ID)}
								{if $COMMENTS_MODULE_MODEL->isPermitted('EditView')}{/if}
								<span class="viewThreadBlock" data-child-comments-count="{$CHILD_COMMENTS_COUNT}">
									<a href="javascript:void(0)" class="cursorPointer viewThread">
										<span class="childCommentsCount">{$CHILD_COMMENTS_COUNT}</span>&nbsp;{if $CHILD_COMMENTS_COUNT eq 1}{vtranslate('LBL_REPLY',$MODULE_NAME)}{else}{vtranslate('LBL_REPLIES',$MODULE_NAME)}{/if}&nbsp;
									</a>
								</span>
								<span class="hideThreadBlock" data-child-comments-count="{$CHILD_COMMENTS_COUNT}" style="display:none;">
									<a href="javascript:void(0)" class="cursorPointer hideThread">
										<span class="childCommentsCount">{$CHILD_COMMENTS_COUNT}</span>&nbsp;{if $CHILD_COMMENTS_COUNT eq 1}{vtranslate('LBL_REPLY',$MODULE_NAME)}{else}{vtranslate('LBL_REPLIES',$MODULE_NAME)}{/if}&nbsp;
									</a>
								</span>
							{elseif $CHILD_COMMENTS_MODEL neq null and (isset($CHILDS_ROOT_PARENT_ID)&& $CHILDS_ROOT_PARENT_ID eq $PARENT_COMMENT_ID)}
								<span class="viewThreadBlock" data-child-comments-count="{$CHILD_COMMENTS_COUNT}" style="display:none;">
									<a href="javascript:void(0)" class="cursorPointer viewThread">
										<span class="childCommentsCount">{$CHILD_COMMENTS_COUNT}</span>&nbsp;{if $CHILD_COMMENTS_COUNT eq 1}{vtranslate('LBL_REPLY',$MODULE_NAME)}{else}{vtranslate('LBL_REPLIES',$MODULE_NAME)}{/if}&nbsp;
									</a>
								</span>
								<span class="hideThreadBlock" data-child-comments-count="{$CHILD_COMMENTS_COUNT}">
									<a href="javascript:void(0)" class="cursorPointer hideThread">
										<span class="childCommentsCount">{$CHILD_COMMENTS_COUNT}</span>&nbsp;{if $CHILD_COMMENTS_COUNT eq 1}{vtranslate('LBL_REPLY',$MODULE_NAME)}{else}{vtranslate('LBL_REPLIES',$MODULE_NAME)}{/if}&nbsp;
									</a>
								</span>
							{/if}
						</span>
					</div>

					{assign var="REASON_TO_EDIT" value=$COMMENT->get('reasontoedit')}
					{if $COMMENT->getCommentedTime() neq $COMMENT->getModifiedTime()}
						<div class="commentEditStatus" name="editStatus">
							{if $REASON_TO_EDIT}
								<div class="text-muted">
									<small>{vtranslate('LBL_EDIT_REASON',$MODULE_NAME)} : <span name="editReason" class="textOverflowEllipsis">{nl2br($REASON_TO_EDIT)}</span></small>
								</div>
							{/if}
							<div class="mk-proj-comment-item__edited text-muted">
								<small>{vtranslate('LBL_COMMENT',$MODULE_NAME)} {strtolower(vtranslate('LBL_MODIFIED',$MODULE_NAME))}</small>&nbsp;
								<small title="{Vtiger_Util_Helper::formatDateTimeIntoDayString($COMMENT->getModifiedTime())}" class="commentModifiedTime">{Vtiger_Util_Helper::formatCommentDateTime($COMMENT->getModifiedTime())}</small>
							</div>
						</div>
					{/if}
					<div class="mk-proj-comment-item__attachments">
						{include file="partials/CommentAttachments.tpl"|vtemplate_path:'Vtiger' COMMENT=$COMMENT MODULE_NAME=$MODULE_NAME}
					</div>
				</div>
			</div>
		</div>
	</div>
{/strip}
