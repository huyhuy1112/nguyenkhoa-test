{strip}
{assign var=MK_RB_TOOLS value=false}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'TOOLS') || (isset($smarty.get.app) && $smarty.get.app eq 'TOOLS') || (isset($smarty.request.app) && $smarty.request.app eq 'TOOLS')}
	{assign var=MK_RB_TOOLS value=true}
{/if}
{if $MK_RB_TOOLS}
	{assign var=MK_SALES_LIST_COUNT_SUFFIX value=' records'}
	<div id="listview-actions" class="listview-actions-container mk-so-filter-row mk-rb-filter-row mk-opportunity-filter-row">
		<div class="mk-so-filter-row__inner mk-rb-filter-row__inner">
			<div class="mk-so-filter-row__start mk-rb-filter-row__start">
				<div class="mk-rb-toolbar-actions">
					{assign var=LISTVIEW_ACTIONS value=array_reverse($LISTVIEW_MASSACTIONS)}
					{foreach item="LISTVIEW_MASSACTION" from=$LISTVIEW_ACTIONS}
						<button type="button" class="btn btn-default mk-rb-mass-btn" id="{$MODULE}_listView_massAction_{Vtiger_Util_Helper::replaceSpaceWithUnderScores($LISTVIEW_MASSACTION->getLabel())}"
								{if stripos($LISTVIEW_MASSACTION->getUrl(), 'javascript:')===0}onclick='{$LISTVIEW_MASSACTION->getUrl()|substr:strlen("javascript:")};'{else} onclick="Vtiger_List_Js.triggerMassAction('{$LISTVIEW_MASSACTION->getUrl()}')"{/if} disabled="disabled"
								title="{if $LISTVIEW_MASSACTION->getLabel() eq 'LBL_RESTORE'}{vtranslate('LBL_RESTORE', $MODULE)}{else}{vtranslate('LBL_DELETE', $MODULE)}{/if}">
							<i class="{if $LISTVIEW_MASSACTION->getLabel() eq 'LBL_RESTORE'}fa fa-refresh{else}fa fa-trash{/if}"></i>
						</button>
					{/foreach}
					{foreach item=LISTVIEW_BASICACTION from=$LISTVIEW_LINKS['LISTVIEWBASIC']}
						<button type="button" id="{$MODULE}_listView_basicAction_{Vtiger_Util_Helper::replaceSpaceWithUnderScores($LISTVIEW_BASICACTION->getLabel())}" class="btn mk-rb-clear-btn clearRecycleBin"
								{if stripos($LISTVIEW_BASICACTION->getUrl(), 'javascript:')===0} onclick='{$LISTVIEW_BASICACTION->getUrl()|substr:strlen("javascript:")};'{else} onclick='window.location.href="{$LISTVIEW_BASICACTION->getUrl()}"'{/if}
								{if !$IS_RECORDS_DELETED} disabled="disabled" {/if}>
							{vtranslate($LISTVIEW_BASICACTION->getLabel(), $MODULE)}
						</button>
					{/foreach}
				</div>
				<div class="mk-so-toolbar-count mk-rb-toolbar-count">
					{assign var=RECORD_COUNT value=$LISTVIEW_ENTRIES_COUNT}
					<span class="pageNumbers mk-so-page-numbers">
						<span class="mk-so-page-numbers__prefix">Showing </span><span class="pageNumbersText">{if $RECORD_COUNT}{$PAGING_MODEL->getRecordStartRange()} {vtranslate('LBL_to', $MODULE)} {$PAGING_MODEL->getRecordEndRange()}{/if}</span><span class="totalNumberOfRecords{if !$RECORD_COUNT} hide{/if}">&nbsp;{vtranslate('LBL_OF', $MODULE)} <span class="mk-so-total-count">{if isset($LISTVIEW_COUNT)}{$LISTVIEW_COUNT}{else}0{/if}</span></span><span class="mk-so-page-numbers__suffix">{$MK_SALES_LIST_COUNT_SUFFIX}</span>
					</span>
				</div>
			</div>
		</div>
		<div class="hide messageContainer mk-so-selectall-msg" style="height:30px;">
			<center><a href="#" id="selectAllMsgDiv">{vtranslate('LBL_SELECT_ALL',$MODULE)}&nbsp;{vtranslate($MODULE ,$MODULE)}&nbsp;(<span id="totalRecordsCount" value=""></span>)</a></center>
		</div>
		<div class="hide messageContainer mk-so-selectall-msg" style="height:30px;">
			<center><a href="#" id="deSelectAllMsgDiv">{vtranslate('LBL_DESELECT_ALL_RECORDS',$MODULE)}</a></center>
		</div>
		<div class="mk-so-filter-row__footer">
			<div class="mk-so-pagination mk-opportunity-pagination">
				{include file="partials/MkSalesPaginationButtons.tpl"|vtemplate_path:'Vtiger' SHOWPAGEJUMP=true}
			</div>
		</div>
	</div>
{else}
	<div id="listview-actions" class="listview-actions-container">
		<div class="row">
			<div class="btn-group col-md-4" role="group" aria-label="...">
				<span class="recordDependentListActions" style="float: left;">
					{assign var=LISTVIEW_ACTIONS value=array_reverse($LISTVIEW_MASSACTIONS)}
					{foreach item="LISTVIEW_MASSACTION" from=$LISTVIEW_ACTIONS}
						<button type="button" class="btn btn-default" id="{$MODULE}_listView_massAction_{Vtiger_Util_Helper::replaceSpaceWithUnderScores($LISTVIEW_MASSACTION->getLabel())}"
								{if stripos($LISTVIEW_MASSACTION->getUrl(), 'javascript:')===0}onclick='{$LISTVIEW_MASSACTION->getUrl()|substr:strlen("javascript:")};'{else} onclick="Vtiger_List_Js.triggerMassAction('{$LISTVIEW_MASSACTION->getUrl()}')"{/if} disabled="disabled"
								title="{if $LISTVIEW_MASSACTION->getLabel() eq 'LBL_RESTORE'}{vtranslate('LBL_RESTORE', $MODULE)}{else}{vtranslate('LBL_DELETE', $MODULE)}{/if}">
							<i class="{if $LISTVIEW_MASSACTION->getLabel() eq 'LBL_RESTORE'} fa fa-refresh {else} fa fa-trash {/if}"></i>
						</button>
					{/foreach}
				</span>
				{foreach item=LISTVIEW_BASICACTION from=$LISTVIEW_LINKS['LISTVIEWBASIC']}
					<span class="btn-group" style="margin-left: 5px;">
						<button id="{$MODULE}_listView_basicAction_{Vtiger_Util_Helper::replaceSpaceWithUnderScores($LISTVIEW_BASICACTION->getLabel())}" class="btn btn-danger clearRecycleBin" {if stripos($LISTVIEW_BASICACTION->getUrl(), 'javascript:')===0} onclick='{$LISTVIEW_BASICACTION->getUrl()|substr:strlen("javascript:")};'{else}
								onclick='window.location.href="{$LISTVIEW_BASICACTION->getUrl()}"'{/if} {if !$IS_RECORDS_DELETED} disabled="disabled" {/if}>
							{vtranslate($LISTVIEW_BASICACTION->getLabel(), $MODULE)}
						</button>
					</span>
				{/foreach}
			</div>
			<div class='col-md-5'>
				<div class="hide messageContainer" style="height:30px;">
					<center><a id="selectAllMsgDiv" href="#">{vtranslate('LBL_SELECT_ALL',$MODULE)}&nbsp;{vtranslate($MODULE ,$MODULE)}&nbsp;(<span id="totalRecordsCount" value=""></span>)</a></center>
				</div>
				<div class="hide messageContainer" style="height:30px;">
					<center><a href="#" id="deSelectAllMsgDiv">{vtranslate('LBL_DESELECT_ALL_RECORDS',$MODULE)}</a></center>
				</div>
			</div>
			<div class="col-md-3">
				{assign var=RECORD_COUNT value=$LISTVIEW_ENTRIES_COUNT}
				{include file="Pagination.tpl"|vtemplate_path:$MODULE SHOWPAGEJUMP=true}
			</div>
		</div>
	</div>
{/if}
{/strip}
