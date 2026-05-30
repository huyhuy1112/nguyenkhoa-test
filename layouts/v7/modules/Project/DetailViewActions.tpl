{* Project Detail actions — MANAGEMENT modern toolbar *}
{strip}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'MANAGEMENT') || (isset($smarty.get.app) && $smarty.get.app eq 'MANAGEMENT')}
	<div class="col-lg-6 col-md-6 col-sm-6 detailViewButtoncontainer mk-project-detail-actions">
		<div class="pull-right btn-toolbar mk-project-detail-actions__toolbar">
			<div class="btn-group mk-project-detail-actions__group">
			{assign var=STARRED value=$RECORD->get('starred')}
			{if $MODULE_MODEL->isStarredEnabled()}
				<button type="button" class="btn btn-default mk-project-detail-btn mk-project-detail-btn--outline markStar {if $STARRED} active {/if}" id="starToggle">
					<span class="mk-project-detail-btn__ic" aria-hidden="true">{include file="partials/ProjectDetailSvgIcon.tpl"|vtemplate_path:$MODULE ICON='FOLLOW'}</span>
					<div class="starredStatus hide" title="{vtranslate('LBL_STARRED', $MODULE)}">
						<div class="unfollowMessage"><span class="mk-project-detail-btn__txt">{vtranslate('LBL_UNFOLLOW',$MODULE)}</span></div>
						<div class="followMessage"><span class="mk-project-detail-btn__txt">{vtranslate('LBL_FOLLOWING',$MODULE)}</span></div>
					</div>
					<div class="unstarredStatus" title="{vtranslate('LBL_NOT_STARRED', $MODULE)}">
						<span class="mk-project-detail-btn__txt">{vtranslate('LBL_FOLLOW',$MODULE)}</span>
					</div>
				</button>
			{/if}
			{foreach item=DETAIL_VIEW_BASIC_LINK from=$DETAILVIEW_LINKS['DETAILVIEWBASIC']}
				{assign var=MK_BASIC_LBL value=$DETAIL_VIEW_BASIC_LINK->getLabel()}
				<button type="button" class="btn btn-default mk-project-detail-btn mk-project-detail-btn--outline" id="{$MODULE_NAME}_detailView_basicAction_{Vtiger_Util_Helper::replaceSpaceWithUnderScores($MK_BASIC_LBL)}"
						{if $DETAIL_VIEW_BASIC_LINK->isPageLoadLink()}
							onclick="window.location.href = '{$DETAIL_VIEW_BASIC_LINK->getUrl()}&app={$SELECTED_MENU_CATEGORY}'"
						{else}
							onclick="{$DETAIL_VIEW_BASIC_LINK->getUrl()}"
						{/if}>
					{if $MK_BASIC_LBL eq 'LBL_EDIT'}
						<span class="mk-project-detail-btn__ic" aria-hidden="true">{include file="partials/ProjectDetailSvgIcon.tpl"|vtemplate_path:$MODULE ICON='EDIT'}</span>
					{/if}
					<span class="mk-project-detail-btn__txt">{vtranslate($MK_BASIC_LBL, $MODULE_NAME)}</span>
				</button>
			{/foreach}
			{if !empty($DETAILVIEW_LINKS['DETAILVIEW']) && ($DETAILVIEW_LINKS['DETAILVIEW']|@count gt 0)}
				<button type="button" class="btn btn-default mk-project-detail-btn mk-project-detail-btn--outline dropdown-toggle" data-toggle="dropdown" href="javascript:void(0);">
					<span class="mk-project-detail-btn__ic" aria-hidden="true">{include file="partials/ProjectDetailSvgIcon.tpl"|vtemplate_path:$MODULE ICON='MORE'}</span>
					<span class="mk-project-detail-btn__txt">{vtranslate('LBL_MORE', $MODULE_NAME)}</span>
					<span class="caret"></span>
				</button>
				<ul class="dropdown-menu dropdown-menu-right">
					{foreach item=DETAIL_VIEW_LINK from=$DETAILVIEW_LINKS['DETAILVIEW']}
						{if $DETAIL_VIEW_LINK->getLabel() eq ""}
							<li class="divider"></li>
						{else}
							<li id="{$MODULE_NAME}_detailView_moreAction_{Vtiger_Util_Helper::replaceSpaceWithUnderScores($DETAIL_VIEW_LINK->getLabel())}">
								{if $DETAIL_VIEW_LINK->getUrl()|strstr:"javascript"}
									<a href="{$DETAIL_VIEW_LINK->getUrl()}">{vtranslate($DETAIL_VIEW_LINK->getLabel(), $MODULE_NAME)}</a>
								{else}
									<a href="{$DETAIL_VIEW_LINK->getUrl()}&app={$SELECTED_MENU_CATEGORY}">{vtranslate($DETAIL_VIEW_LINK->getLabel(), $MODULE_NAME)}</a>
								{/if}
							</li>
						{/if}
					{/foreach}
				</ul>
			{/if}
			</div>
		</div>
		<input type="hidden" name="record_id" value="{$RECORD->getId()}">
	</div>
{else}
	{include file="DetailViewActions.tpl"|@vtemplate_path:'Vtiger'}
{/if}
{/strip}
