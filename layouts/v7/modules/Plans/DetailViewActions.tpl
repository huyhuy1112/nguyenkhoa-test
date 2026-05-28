{* Plans Detail actions — Marketing (Figma action pill) *}
{strip}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'MARKETING') || (isset($smarty.get.app) && $smarty.get.app eq 'MARKETING')}
	<div class="detailViewButtoncontainer mk-plan-detail-actions mk-plan-detail-hero__actions">
		<div class="mk-plan-detail-actions__toolbar">
			<div class="mk-plan-detail-actions__pill">
				{foreach item=DETAIL_VIEW_BASIC_LINK from=$DETAILVIEW_LINKS['DETAILVIEWBASIC']}
					{assign var=MK_BASIC_LBL value=$DETAIL_VIEW_BASIC_LINK->getLabel()}
					{if $MK_BASIC_LBL eq 'LBL_EDIT'}
						<button type="button" class="mk-plan-detail-icon-btn" title="{vtranslate($MK_BASIC_LBL, $MODULE_NAME)}"
							onclick="window.location.href = '{$DETAIL_VIEW_BASIC_LINK->getUrl()}&app={$SELECTED_MENU_CATEGORY}'">
							<span class="mk-plan-detail-icon-btn__ic">{include file="partials/PlanDetailSvgIcon.tpl"|@vtemplate_path:$MODULE ICON='EDIT'}</span>
						</button>
					{/if}
				{/foreach}
				{if !empty($DETAILVIEW_LINKS['DETAILVIEW']) && ($DETAILVIEW_LINKS['DETAILVIEW']|@count gt 0) || !empty($DETAILVIEW_LINKS['DETAILVIEWBASIC'])}
					<div class="dropdown mk-plan-detail-more-wrap">
						<button type="button" class="mk-plan-detail-icon-btn dropdown-toggle" data-toggle="dropdown" aria-expanded="false" title="{vtranslate('LBL_MORE', $MODULE_NAME)}">
							<span class="mk-plan-detail-icon-btn__ic">{include file="partials/PlanDetailSvgIcon.tpl"|@vtemplate_path:$MODULE ICON='MORE'}</span>
						</button>
						<ul class="dropdown-menu dropdown-menu-right">
							{foreach item=DETAIL_VIEW_BASIC_LINK from=$DETAILVIEW_LINKS['DETAILVIEWBASIC']}
								{assign var=MK_BASIC_LBL value=$DETAIL_VIEW_BASIC_LINK->getLabel()}
								{if $MK_BASIC_LBL eq 'LBL_DUPLICATE' || $MK_BASIC_LBL eq 'LBL_DELETE'}
									<li>
										<a href="javascript:void(0)" {if $DETAIL_VIEW_BASIC_LINK->isPageLoadLink()}onclick="window.location.href = '{$DETAIL_VIEW_BASIC_LINK->getUrl()}&app={$SELECTED_MENU_CATEGORY}'"{else}onclick="{$DETAIL_VIEW_BASIC_LINK->getUrl()}"{/if}>{vtranslate($MK_BASIC_LBL, $MODULE_NAME)}</a>
									</li>
								{/if}
							{/foreach}
							{foreach item=DETAIL_VIEW_LINK from=$DETAILVIEW_LINKS['DETAILVIEW']}
								{if $DETAIL_VIEW_LINK->getLabel() neq ""}
									<li><a href="{$DETAIL_VIEW_LINK->getUrl()}&app={$SELECTED_MENU_CATEGORY}">{vtranslate($DETAIL_VIEW_LINK->getLabel(), $MODULE_NAME)}</a></li>
								{/if}
							{/foreach}
						</ul>
					</div>
				{/if}
			</div>
			{if !{$NO_PAGINATION}}
			<div class="mk-plan-detail-actions__pager">
				<button type="button" class="mk-plan-detail-icon-btn" id="detailViewPreviousRecordButton" {if empty($PREVIOUS_RECORD_URL)} disabled="disabled" {else} onclick="window.location.href = '{$PREVIOUS_RECORD_URL}&app={$SELECTED_MENU_CATEGORY}'" {/if} aria-label="Previous">
					<i class="fa fa-chevron-left"></i>
				</button>
				<button type="button" class="mk-plan-detail-icon-btn" id="detailViewNextRecordButton" {if empty($NEXT_RECORD_URL)} disabled="disabled" {else} onclick="window.location.href = '{$NEXT_RECORD_URL}&app={$SELECTED_MENU_CATEGORY}'" {/if} aria-label="Next">
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
