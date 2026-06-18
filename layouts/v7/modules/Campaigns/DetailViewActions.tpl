{* Campaigns Detail actions — Marketing (Follow / Duplicate / Delete) *}
{strip}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'MARKETING') || (isset($smarty.get.app) && $smarty.get.app eq 'MARKETING')}
	<div class="detailViewButtoncontainer mk-camp-detail-actions mk-camp-detail-hero__actions">
		<div class="mk-camp-detail-actions__toolbar">
			<div class="mk-camp-detail-actions__group">
			{assign var=STARRED value=$RECORD->get('starred')}
			{if $MODULE_MODEL->isStarredEnabled()}
				<button type="button" class="btn btn-default mk-camp-detail-btn mk-camp-detail-btn--ghost markStar {if $STARRED} active {/if}" id="starToggle">
					<span class="mk-camp-detail-btn__ic" aria-hidden="true">{include file="partials/CampaignDetailSvgIcon.tpl"|@vtemplate_path:$MODULE ICON='FOLLOW'}</span>
					<div class="starredStatus hide" title="{vtranslate('LBL_STARRED', $MODULE)}">
						<div class="unfollowMessage"><span class="mk-camp-detail-btn__txt">{vtranslate('LBL_UNFOLLOW',$MODULE)}</span></div>
						<div class="followMessage"><span class="mk-camp-detail-btn__txt">{vtranslate('LBL_FOLLOWING',$MODULE)}</span></div>
					</div>
					<div class="unstarredStatus" title="{vtranslate('LBL_NOT_STARRED', $MODULE)}">
						<span class="mk-camp-detail-btn__txt">{vtranslate('LBL_FOLLOW',$MODULE)}</span>
					</div>
				</button>
			{/if}
			{foreach item=DETAIL_VIEW_BASIC_LINK from=$DETAILVIEW_LINKS['DETAILVIEWBASIC']}
				{assign var=MK_BASIC_LBL value=$DETAIL_VIEW_BASIC_LINK->getLabel()}
				{assign var=MK_BASIC_ICON value=''}
				{if $MK_BASIC_LBL eq 'LBL_DUPLICATE'}{assign var=MK_BASIC_ICON value='DUPLICATE'}{/if}
				{if $MK_BASIC_LBL eq 'LBL_DELETE'}{assign var=MK_BASIC_ICON value='DELETE'}{/if}
				{if $MK_BASIC_LBL eq 'LBL_EDIT'}{continue}{/if}
				<button type="button" class="btn btn-default mk-camp-detail-btn mk-camp-detail-btn--ghost{if $MK_BASIC_LBL eq 'LBL_DELETE'} mk-camp-detail-btn--danger{/if}" id="{$MODULE_NAME}_detailView_basicAction_{Vtiger_Util_Helper::replaceSpaceWithUnderScores($MK_BASIC_LBL)}"
						{if $DETAIL_VIEW_BASIC_LINK->isPageLoadLink()}
							onclick="window.location.href = '{$DETAIL_VIEW_BASIC_LINK->getUrl()}&app={$SELECTED_MENU_CATEGORY}'"
						{else}
							onclick="{$DETAIL_VIEW_BASIC_LINK->getUrl()}"
						{/if}>
					{if $MK_BASIC_ICON neq ''}
						<span class="mk-camp-detail-btn__ic" aria-hidden="true">{include file="partials/CampaignDetailSvgIcon.tpl"|@vtemplate_path:$MODULE ICON=$MK_BASIC_ICON}</span>
					{/if}
					<span class="mk-camp-detail-btn__txt">{vtranslate($MK_BASIC_LBL, $MODULE_NAME)}</span>
				</button>
			{/foreach}
			{if !empty($DETAILVIEW_LINKS['DETAILVIEW']) && ($DETAILVIEW_LINKS['DETAILVIEW']|@count gt 0)}
				<button type="button" class="btn btn-default mk-camp-detail-btn mk-camp-detail-btn--ghost dropdown-toggle" data-toggle="dropdown" href="javascript:void(0);">
					<span class="mk-camp-detail-btn__txt">{vtranslate('LBL_MORE', $MODULE_NAME)}</span>
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
			{if !{$NO_PAGINATION}}
			<div class="mk-camp-detail-actions__pager">
				<button type="button" class="btn btn-default mk-camp-detail-btn mk-camp-detail-btn--ghost" id="detailViewPreviousRecordButton" {if empty($PREVIOUS_RECORD_URL)} disabled="disabled" {else} onclick="window.location.href = '{$PREVIOUS_RECORD_URL}&app={$SELECTED_MENU_CATEGORY}'" {/if}>
					<i class="fa fa-chevron-left"></i>
				</button>
				<button type="button" class="btn btn-default mk-camp-detail-btn mk-camp-detail-btn--ghost" id="detailViewNextRecordButton" {if empty($NEXT_RECORD_URL)} disabled="disabled" {else} onclick="window.location.href = '{$NEXT_RECORD_URL}&app={$SELECTED_MENU_CATEGORY}'" {/if}>
					<i class="fa fa-chevron-right"></i>
				</button>
			</div>
			{/if}
		</div>
		<input type="hidden" name="record_id" value="{$RECORD->getId()}">
	</div>
{else}
	{include file="DetailViewActions.tpl"|@vtemplate_path:'Vtiger'}
{/if}
{/strip}
