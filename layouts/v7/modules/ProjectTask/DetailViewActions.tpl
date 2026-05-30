{* ProjectTask Detail actions — MANAGEMENT: visible buttons (no 3-dot menu) *}
{strip}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'MANAGEMENT') || (isset($smarty.get.app) && $smarty.get.app eq 'MANAGEMENT')}
	<div class="col-lg-6 col-md-6 col-sm-6 detailViewButtoncontainer mk-projecttask-detail-actions">
		<div class="pull-right btn-toolbar mk-projecttask-detail-actions__toolbar">
			<div class="btn-group mk-projecttask-detail-actions__group">
			{assign var=STARRED value=$RECORD->get('starred')}
			{if $MODULE_MODEL->isStarredEnabled()}
				<button type="button" class="btn btn-default mk-projecttask-detail-btn mk-projecttask-detail-btn--outline markStar {if $STARRED} active {/if}" id="starToggle">
					<span class="mk-projecttask-detail-btn__ic" aria-hidden="true">{include file="partials/ProjectTaskDetailSvgIcon.tpl"|vtemplate_path:$MODULE ICON='FOLLOW'}</span>
					<div class="starredStatus hide" title="{vtranslate('LBL_STARRED', $MODULE)}">
						<div class="unfollowMessage"><span class="mk-projecttask-detail-btn__txt">{vtranslate('LBL_UNFOLLOW',$MODULE)}</span></div>
						<div class="followMessage"><span class="mk-projecttask-detail-btn__txt">{vtranslate('LBL_FOLLOWING',$MODULE)}</span></div>
					</div>
					<div class="unstarredStatus" title="{vtranslate('LBL_NOT_STARRED', $MODULE)}">
						<span class="mk-projecttask-detail-btn__txt">{vtranslate('LBL_FOLLOW',$MODULE)}</span>
					</div>
				</button>
			{/if}
			{if !empty($DETAILVIEW_LINKS['DETAILVIEWBASIC'])}
			{foreach item=DETAIL_VIEW_BASIC_LINK from=$DETAILVIEW_LINKS['DETAILVIEWBASIC']}
				{assign var=MK_BASIC_LBL value=$DETAIL_VIEW_BASIC_LINK->getLabel()}
				<button type="button" class="btn btn-default mk-projecttask-detail-btn mk-projecttask-detail-btn--outline" id="{$MODULE_NAME}_detailView_basicAction_{Vtiger_Util_Helper::replaceSpaceWithUnderScores($MK_BASIC_LBL)}"
						{if $DETAIL_VIEW_BASIC_LINK->isPageLoadLink()}
							onclick="window.location.href = '{$DETAIL_VIEW_BASIC_LINK->getUrl()}&app={$SELECTED_MENU_CATEGORY}'"
						{else}
							onclick="{$DETAIL_VIEW_BASIC_LINK->getUrl()}"
						{/if}>
					{if $MK_BASIC_LBL eq 'LBL_EDIT'}
						<span class="mk-projecttask-detail-btn__ic" aria-hidden="true">{include file="partials/ProjectTaskDetailSvgIcon.tpl"|vtemplate_path:$MODULE ICON='EDIT'}</span>
					{/if}
					<span class="mk-projecttask-detail-btn__txt">{vtranslate($MK_BASIC_LBL, $MODULE_NAME)}</span>
				</button>
			{/foreach}
			{/if}
			{if !empty($DETAILVIEW_LINKS['DETAILVIEW']) && ($DETAILVIEW_LINKS['DETAILVIEW']|@count gt 0)}
			{foreach item=DETAIL_VIEW_LINK from=$DETAILVIEW_LINKS['DETAILVIEW']}
				{if $DETAIL_VIEW_LINK->getLabel() neq ""}
					{assign var=MK_MORE_LBL value=$DETAIL_VIEW_LINK->getLabel()}
					<button type="button" class="btn btn-default mk-projecttask-detail-btn mk-projecttask-detail-btn--outline{if $MK_MORE_LBL eq 'LBL_DELETE'} mk-projecttask-detail-btn--danger{/if}" id="{$MODULE_NAME}_detailView_moreAction_{Vtiger_Util_Helper::replaceSpaceWithUnderScores($MK_MORE_LBL)}"
							{if $DETAIL_VIEW_LINK->getUrl()|strstr:"javascript"}
								onclick="{$DETAIL_VIEW_LINK->getUrl()}"
							{else}
								onclick="window.location.href = '{$DETAIL_VIEW_LINK->getUrl()}&app={$SELECTED_MENU_CATEGORY}'"
							{/if}>
						{if $MK_MORE_LBL eq 'LBL_DELETE'}
							<span class="mk-projecttask-detail-btn__ic" aria-hidden="true">{include file="partials/ProjectTaskDetailSvgIcon.tpl"|vtemplate_path:$MODULE ICON='DELETE'}</span>
						{elseif $MK_MORE_LBL eq 'LBL_DUPLICATE'}
							<span class="mk-projecttask-detail-btn__ic" aria-hidden="true">{include file="partials/ProjectTaskDetailSvgIcon.tpl"|vtemplate_path:$MODULE ICON='DETAIL'}</span>
						{/if}
						<span class="mk-projecttask-detail-btn__txt">{vtranslate($MK_MORE_LBL, $MODULE_NAME)}</span>
					</button>
				{/if}
			{/foreach}
			{/if}
			</div>
		</div>
		<input type="hidden" name="record_id" value="{$RECORD->getId()}">
	</div>
{else}
	{include file="DetailViewActions.tpl"|@vtemplate_path:'Vtiger'}
{/if}
{/strip}
