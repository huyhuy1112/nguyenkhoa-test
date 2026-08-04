{* Organizations Detail actions: Sales + Marketing modern toolbar with SVG icons. *}
{strip}
{if !empty($MK_ACCOUNTS_MODERN_UI) || (isset($SELECTED_MENU_CATEGORY) && ($SELECTED_MENU_CATEGORY eq 'SALES' || $SELECTED_MENU_CATEGORY eq 'MARKETING')) || (isset($smarty.get.app) && ($smarty.get.app eq 'SALES' || $smarty.get.app eq 'MARKETING'))}
	{assign var=MK_ACC_ACTION_COL value="detailViewButtoncontainer mk-acc-detail-actions"}
	<div class="{$MK_ACC_ACTION_COL}">
		<div class="pull-right btn-toolbar mk-acc-detail-actions__toolbar">
			<div class="btn-group mk-acc-detail-actions__group">
			{assign var=STARRED value=$RECORD->get('starred')}
			{if $MODULE_MODEL->isStarredEnabled()}
				<button type="button" class="btn btn-default mk-acc-detail-btn mk-acc-detail-btn--ghost markStar {if $STARRED} active {/if}" id="starToggle">
					<span class="mk-acc-detail-btn__ic mk-acc-detail-btn__ic--follow" aria-hidden="true">{include file="partials/AccountsDetailSvgIcon.tpl"|@vtemplate_path:$MODULE ICON='FOLLOW'}</span>
					<div class="starredStatus hide" title="{vtranslate('LBL_STARRED', $MODULE)}">
						<div class="unfollowMessage"><span class="mk-acc-detail-btn__txt">{vtranslate('LBL_UNFOLLOW',$MODULE)}</span></div>
						<div class="followMessage"><span class="mk-acc-detail-btn__txt">{vtranslate('LBL_FOLLOWING',$MODULE)}</span></div>
					</div>
					<div class="unstarredStatus" title="{vtranslate('LBL_NOT_STARRED', $MODULE)}">
						<span class="mk-acc-detail-btn__txt">{vtranslate('LBL_FOLLOW',$MODULE)}</span>
					</div>
				</button>
			{/if}
			{if !empty($RECORD)}
			<button type="button" class="btn btn-default mk-acc-detail-btn mk-acc-detail-btn--ghost" id="Accounts_detailView_previewFranchiseContract"
				data-record-id="{$RECORD->getId()}"
				data-preview-url="index.php?module=Accounts&amp;action=ExportFranchiseWord&amp;record={$RECORD->getId()}&amp;preview=1"
				data-word-url="index.php?module=Accounts&amp;action=ExportFranchiseWord&amp;record={$RECORD->getId()}"
				title="{vtranslate('LBL_PREVIEW_FRANCHISE_CONTRACT_HINT', $MODULE_NAME)}">
				<span class="mk-acc-detail-btn__ic" aria-hidden="true"><i class="fa fa-eye"></i></span>
				<span class="mk-acc-detail-btn__txt">{vtranslate('LBL_PREVIEW_FRANCHISE_CONTRACT', $MODULE_NAME)}</span>
			</button>
			<button type="button" class="btn btn-default mk-acc-detail-btn mk-acc-detail-btn--ghost" id="Accounts_detailView_printFranchiseContract"
				data-record-id="{$RECORD->getId()}"
				data-word-url="index.php?module=Accounts&amp;action=ExportFranchiseWord&amp;record={$RECORD->getId()}"
				title="{vtranslate('LBL_PRINT_FRANCHISE_CONTRACT_HINT', $MODULE_NAME)}">
				<span class="mk-acc-detail-btn__ic" aria-hidden="true"><i class="fa fa-file-word-o"></i></span>
				<span class="mk-acc-detail-btn__txt">{vtranslate('LBL_PRINT_FRANCHISE_CONTRACT', $MODULE_NAME)}</span>
			</button>
			{/if}
			{foreach item=DETAIL_VIEW_BASIC_LINK from=$DETAILVIEW_LINKS['DETAILVIEWBASIC']}
				{assign var=MK_BASIC_LBL value=$DETAIL_VIEW_BASIC_LINK->getLabel()}
				{if $MK_BASIC_LBL eq 'LBL_PRINT_FRANCHISE_CONTRACT' || $MK_BASIC_LBL eq 'LBL_EXPORT_FRANCHISE_CONTRACT_WORD'}{continue}{/if}
				{assign var=MK_BASIC_ICON value='EDIT'}
				{if $MK_BASIC_LBL eq 'LBL_SEND_EMAIL'}{assign var=MK_BASIC_ICON value='EMAIL'}{/if}
				<button type="button" class="btn btn-default mk-acc-detail-btn {if $MK_BASIC_LBL eq 'LBL_SEND_EMAIL'}mk-acc-detail-btn--primary{else}mk-acc-detail-btn--ghost{/if}" id="{$MODULE_NAME}_detailView_basicAction_{Vtiger_Util_Helper::replaceSpaceWithUnderScores($MK_BASIC_LBL)}"
						{if $DETAIL_VIEW_BASIC_LINK->isPageLoadLink()}
							onclick="window.location.href = '{$DETAIL_VIEW_BASIC_LINK->getUrl()}&app={$SELECTED_MENU_CATEGORY}'"
						{else}
							onclick="{$DETAIL_VIEW_BASIC_LINK->getUrl()}"
						{/if}
						{if $MODULE_NAME eq 'Documents' && $MK_BASIC_LBL eq 'LBL_VIEW_FILE'}
							data-filelocationtype="{$DETAIL_VIEW_BASIC_LINK->get('filelocationtype')}" data-filename="{$DETAIL_VIEW_BASIC_LINK->get('filename')}"
						{/if}>
					{if $MK_BASIC_LBL eq 'LBL_SEND_EMAIL' or $MK_BASIC_LBL eq 'LBL_EDIT'}
						<span class="mk-acc-detail-btn__ic" aria-hidden="true">{include file="partials/AccountsDetailSvgIcon.tpl"|@vtemplate_path:$MODULE ICON=$MK_BASIC_ICON}</span>
					{/if}
					<span class="mk-acc-detail-btn__txt">{vtranslate($MK_BASIC_LBL, $MODULE_NAME)}</span>
				</button>
			{/foreach}
			{if !empty($DETAILVIEW_LINKS['DETAILVIEW']) && ($DETAILVIEW_LINKS['DETAILVIEW']|@count gt 0)}
				<button type="button" class="btn btn-default mk-acc-detail-btn mk-acc-detail-btn--ghost mk-acc-detail-btn--icon-only dropdown-toggle" data-toggle="dropdown" title="{vtranslate('LBL_MORE', $MODULE_NAME)}" aria-label="{vtranslate('LBL_MORE', $MODULE_NAME)}" aria-haspopup="true" aria-expanded="false">
					<span class="mk-acc-detail-btn__ic" aria-hidden="true">{include file="partials/AccountsDetailSvgIcon.tpl"|@vtemplate_path:$MODULE ICON='MORE'}</span>
				</button>
				<ul class="dropdown-menu dropdown-menu-right" role="menu">
					{foreach item=DETAIL_VIEW_LINK from=$DETAILVIEW_LINKS['DETAILVIEW']}
						{if $DETAIL_VIEW_LINK->getLabel() eq ""}
							<li class="divider"></li>
						{else}
							<li id="{$MODULE_NAME}_detailView_moreAction_{Vtiger_Util_Helper::replaceSpaceWithUnderScores($DETAIL_VIEW_LINK->getLabel())}" role="presentation">
								{if $DETAIL_VIEW_LINK->getUrl()|strstr:"javascript"}
									<a href="{$DETAIL_VIEW_LINK->getUrl()}" role="menuitem">{vtranslate($DETAIL_VIEW_LINK->getLabel(), $MODULE_NAME)}</a>
								{else}
									<a href="{$DETAIL_VIEW_LINK->getUrl()}&app={$SELECTED_MENU_CATEGORY}" role="menuitem">{vtranslate($DETAIL_VIEW_LINK->getLabel(), $MODULE_NAME)}</a>
								{/if}
							</li>
						{/if}
					{/foreach}
				</ul>
			{/if}
			</div>
			{if !{$NO_PAGINATION}}
			<div class="btn-group pull-right mk-acc-detail-actions__pager">
				<button type="button" class="btn btn-default mk-acc-detail-btn mk-acc-detail-btn--ghost" id="detailViewPreviousRecordButton" {if empty($PREVIOUS_RECORD_URL)} disabled="disabled" {else} onclick="window.location.href = '{$PREVIOUS_RECORD_URL}&app={$SELECTED_MENU_CATEGORY}'" {/if}>
					<i class="fa fa-chevron-left"></i>
				</button>
				<button type="button" class="btn btn-default mk-acc-detail-btn mk-acc-detail-btn--ghost" id="detailViewNextRecordButton" {if empty($NEXT_RECORD_URL)} disabled="disabled" {else} onclick="window.location.href = '{$NEXT_RECORD_URL}&app={$SELECTED_MENU_CATEGORY}'" {/if}>
					<i class="fa fa-chevron-right"></i>
				</button>
			</div>
			{/if}
		</div>
		<input type="hidden" name="record_id" value="{$RECORD->getId()}">
	</div>
{else}
	{assign var=MK_ACC_ACTION_COL value="col-lg-6 detailViewButtoncontainer"}
	<div class="{$MK_ACC_ACTION_COL}">
		<div class="pull-right btn-toolbar">
			<div class="btn-group">
			{assign var=STARRED value=$RECORD->get('starred')}
			{if $MODULE_MODEL->isStarredEnabled()}
				<button class="btn btn-default markStar {if $STARRED} active {/if}" id="starToggle">
					<div class='starredStatus' title="{vtranslate('LBL_STARRED', $MODULE)}">
						<div class='unfollowMessage'>
							<i class="fa fa-star-o"></i> &nbsp;{vtranslate('LBL_UNFOLLOW',$MODULE)}
						</div>
						<div class='followMessage'>
							<i class="fa fa-star active"></i> &nbsp;{vtranslate('LBL_FOLLOWING',$MODULE)}
						</div>
					</div>
					<div class='unstarredStatus' title="{vtranslate('LBL_NOT_STARRED', $MODULE)}">
						{vtranslate('LBL_FOLLOW',$MODULE)}
					</div>
				</button>
			{/if}
			{foreach item=DETAIL_VIEW_BASIC_LINK from=$DETAILVIEW_LINKS['DETAILVIEWBASIC']}
				<button class="btn btn-default" id="{$MODULE_NAME}_detailView_basicAction_{Vtiger_Util_Helper::replaceSpaceWithUnderScores($DETAIL_VIEW_BASIC_LINK->getLabel())}"
						{if $DETAIL_VIEW_BASIC_LINK->isPageLoadLink()}
							onclick="window.location.href = '{$DETAIL_VIEW_BASIC_LINK->getUrl()}&app={$SELECTED_MENU_CATEGORY}'"
						{else}
							onclick="{$DETAIL_VIEW_BASIC_LINK->getUrl()}"
						{/if}>
					{vtranslate($DETAIL_VIEW_BASIC_LINK->getLabel(), $MODULE_NAME)}
				</button>
			{/foreach}
			{if !empty($DETAILVIEW_LINKS['DETAILVIEW']) && ($DETAILVIEW_LINKS['DETAILVIEW']|@count gt 0)}
				<button class="btn btn-default dropdown-toggle" data-toggle="dropdown" href="javascript:void(0);">
					{vtranslate('LBL_MORE', $MODULE_NAME)}&nbsp;&nbsp;<i class="caret"></i>
				</button>
				<ul class="dropdown-menu dropdown-menu-right">
					{foreach item=DETAIL_VIEW_LINK from=$DETAILVIEW_LINKS['DETAILVIEW']}
						{if $DETAIL_VIEW_LINK->getLabel() eq ""}
							<li class="divider"></li>
						{else}
							<li id="{$MODULE_NAME}_detailView_moreAction_{Vtiger_Util_Helper::replaceSpaceWithUnderScores($DETAIL_VIEW_LINK->getLabel())}">
								{if $DETAIL_VIEW_LINK->getUrl()|strstr:"javascript"}
									<a href='{$DETAIL_VIEW_LINK->getUrl()}'>{vtranslate($DETAIL_VIEW_LINK->getLabel(), $MODULE_NAME)}</a>
								{else}
									<a href='{$DETAIL_VIEW_LINK->getUrl()}&app={$SELECTED_MENU_CATEGORY}'>{vtranslate($DETAIL_VIEW_LINK->getLabel(), $MODULE_NAME)}</a>
								{/if}
							</li>
						{/if}
					{/foreach}
				</ul>
			{/if}
			</div>
			{if !{$NO_PAGINATION}}
			<div class="btn-group pull-right">
				<button class="btn btn-default" id="detailViewPreviousRecordButton" {if empty($PREVIOUS_RECORD_URL)} disabled="disabled" {else} onclick="window.location.href = '{$PREVIOUS_RECORD_URL}&app={$SELECTED_MENU_CATEGORY}'" {/if}>
					<i class="fa fa-chevron-left"></i>
				</button>
				<button class="btn btn-default" id="detailViewNextRecordButton" {if empty($NEXT_RECORD_URL)} disabled="disabled" {else} onclick="window.location.href = '{$NEXT_RECORD_URL}&app={$SELECTED_MENU_CATEGORY}'" {/if}>
					<i class="fa fa-chevron-right"></i>
				</button>
			</div>
			{/if}
		</div>
		<input type="hidden" name="record_id" value="{$RECORD->getId()}">
	</div>
{/if}
{/strip}
