{* Admin-only delete action for ModComments on detail view. *}
{strip}
{if !empty($CAN_ADMIN_DELETE_COMMENTS)}
	<span class="mk-comment-delete-wrap">
		{if !empty($SHOW_COMMENT_ACTION_SEP)}<span>&nbsp;|&nbsp;</span>{/if}
		<a href="javascript:void(0);" class="cursorPointer deleteComment feedback mk-comment-delete-link" data-commentid="{$COMMENT->getId()}" title="{vtranslate('LBL_DELETE',$MODULE_NAME)}">
			{vtranslate('LBL_DELETE',$MODULE_NAME)}
		</a>
	</span>
{/if}
{/strip}
