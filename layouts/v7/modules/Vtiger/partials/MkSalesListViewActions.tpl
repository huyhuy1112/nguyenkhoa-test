{* SALES list toolbar — Sales Order standard (mk-so-filter-row + footer below table via List.js) *}
{strip}
{if !isset($MK_SALES_LIST_COUNT_SUFFIX) || $MK_SALES_LIST_COUNT_SUFFIX eq ''}
	{assign var=MK_SALES_LIST_COUNT_SUFFIX value=' records'}
{/if}
    {assign var=PRINT_TEMPLATE value=false}
    {assign var=LISTVIEW_MASSACTIONS_1 value=array()}
    <div id="listview-actions" class="listview-actions-container mk-so-filter-row">
        {foreach item=LIST_MASSACTION from=$LISTVIEW_MASSACTIONS name=massActions}
            {if $LIST_MASSACTION->getLabel() eq 'LBL_EDIT'}
                {assign var=editAction value=$LIST_MASSACTION}
            {else if $LIST_MASSACTION->getLabel() eq 'LBL_DELETE'}
                {assign var=deleteAction value=$LIST_MASSACTION}
            {else if $LIST_MASSACTION->getLabel() eq 'LBL_ADD_COMMENT'}
                {assign var=commentAction value=$LIST_MASSACTION}
            {else}
                {$a = array_push($LISTVIEW_MASSACTIONS_1, $LIST_MASSACTION)}
            {/if}
        {/foreach}
        <div class="mk-so-filter-row__inner">
            <div class="mk-so-filter-row__start">
                <div class="mk-so-toolbar-toggles" aria-label="{vtranslate('LBL_LIST_VIEW',$MODULE)}">
                    <button type="button" class="mk-so-icon-btn mk-so-toggle-layout mk-so-toggle-layout--list is-active" title="{vtranslate('LBL_LIST_VIEW',$MODULE)}" aria-pressed="true">
                        <span class="mk-so-icon-btn__ic" aria-hidden="true">{include file="partials/DashboardTopbarSvgIcon.tpl"|@vtemplate_path:'Vtiger' ICON='LIST'}</span>
                    </button>
                    <button type="button" class="mk-so-icon-btn mk-so-toggle-layout mk-so-toggle-layout--grid" title="Grid view" aria-pressed="false">
                        <span class="mk-so-icon-btn__ic" aria-hidden="true"><span class="fa fa-th"></span></span>
                    </button>
                </div>
                <div class="mk-so-toolbar-count">
                    {assign var=RECORD_COUNT value=$LISTVIEW_ENTRIES_COUNT}
                    <span class="pageNumbers mk-so-page-numbers">
                        <span class="mk-so-page-numbers__prefix">Showing </span><span class="pageNumbersText">{if $RECORD_COUNT}{$PAGING_MODEL->getRecordStartRange()} {vtranslate('LBL_to', $MODULE)} {$PAGING_MODEL->getRecordEndRange()}{/if}</span><span class="totalNumberOfRecords cursorPointer{if !$RECORD_COUNT} hide{/if}" title="{vtranslate('LBL_SHOW_TOTAL_NUMBER_OF_RECORDS', $MODULE)}">&nbsp;{vtranslate('LBL_OF', $MODULE)} <i class="fa fa-question showTotalCountIcon"></i></span><span class="mk-so-page-numbers__suffix">{$MK_SALES_LIST_COUNT_SUFFIX}</span>
                    </span>
                </div>
            </div>

            <div class="btn-group listViewActionsContainer mk-so-mass-actions" role="group" aria-label="{vtranslate('LBL_ACTIONS',$MODULE)}">
                {if isset($editAction) && $editAction}
                    <button type="button" class="btn btn-default mk-so-mass-btn" id={$MODULE}_listView_massAction_{$editAction->getLabel()}
                            {if stripos($editAction->getUrl(), 'javascript:')===0} href="javascript:void(0);" onclick='{$editAction->getUrl()|substr:strlen("javascript:")}'{else} href='{$editAction->getUrl()}' {/if} title="{vtranslate('LBL_EDIT', $MODULE)}" disabled="disabled">
                        <i class="fa fa-pencil"></i>
                    </button>
                {/if}
                {if isset($deleteAction) && $deleteAction}
                    <button type="button" class="btn btn-default mk-so-mass-btn" id={$MODULE}_listView_massAction_{$deleteAction->getLabel()}
                            {if stripos($deleteAction->getUrl(), 'javascript:')===0} href="javascript:void(0);" onclick='{$deleteAction->getUrl()|substr:strlen("javascript:")}'{else} href='{$deleteAction->getUrl()}' {/if} title="{vtranslate('LBL_DELETE', $MODULE)}" disabled="disabled">
                        <i class="fa fa-trash"></i>
                    </button>
                {/if}
                {if isset($commentAction) && $commentAction}
                    <button type="button" class="btn btn-default mk-so-mass-btn" id="{$MODULE}_listView_massAction_{$commentAction->getLabel()}"
                            onclick="Vtiger_List_Js.triggerMassAction('{$commentAction->getUrl()}')" title="{vtranslate('LBL_COMMENT', $MODULE)}" disabled="disabled">
                        <i class="fa fa-comment"></i>
                    </button>
                {/if}
                {if php7_count($LISTVIEW_MASSACTIONS_1) gt 0 or $LISTVIEW_LINKS['LISTVIEW']|@count gt 0}
                    <div class="btn-group listViewMassActions" role="group">
                        <button type="button" class="btn btn-default btn-sm dropdown-toggle mk-so-mass-more" data-toggle="dropdown">
                            {vtranslate('LBL_MORE','Vtiger')}&nbsp;
                            <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu" role="menu">
                            {foreach item=LISTVIEW_MASSACTION from=$LISTVIEW_MASSACTIONS_1 name=advancedMassActions}
                                <li class="hide"><a id="{$MODULE}_listView_massAction_{Vtiger_Util_Helper::replaceSpaceWithUnderScores($LISTVIEW_MASSACTION->getLabel())}" {if stripos($LISTVIEW_MASSACTION->getUrl(), 'javascript:')===0} href="javascript:void(0);" onclick='{$LISTVIEW_MASSACTION->getUrl()|substr:strlen("javascript:")};'{else} href='{$LISTVIEW_MASSACTION->getUrl()}' {/if}>{vtranslate($LISTVIEW_MASSACTION->getLabel(), $MODULE)}</a></li>
                            {/foreach}
                            {if php7_count($LISTVIEW_MASSACTIONS_1) gt 0 and $LISTVIEW_LINKS['LISTVIEW']|@count gt 0}
                                <li class="divider hide"></li>
                            {/if}
                            {if $MODULE_MODEL->isStarredEnabled()}
                                <li class="hide">
                                    <a id="{$MODULE}_listView_massAction_LBL_ADD_STAR" onclick="Vtiger_List_Js.triggerAddStar()">{vtranslate('LBL_FOLLOW',$MODULE)}</a>
                                </li>
                                <li class="hide">
                                    <a id="{$MODULE}_listView_massAction_LBL_REMOVE_STAR" onclick="Vtiger_List_Js.triggerRemoveStar()">{vtranslate('LBL_UNFOLLOW',$MODULE)}</a>
                                </li>
                            {/if}
                            <li class="hide">
                                <a id="{$MODULE}_listView_massAction_LBL_ADD_TAG" onclick="Vtiger_List_Js.triggerAddTag()">{vtranslate('LBL_ADD_TAG',$MODULE)}</a>
                            </li>
                            {if $CURRENT_TAG neq ''}
                                <li class="hide">
                                    <a id="{$MODULE}_listview_massAction_LBL_REMOVE_TAG" onclick="Vtiger_List_Js.triggerRemoveTag({$CURRENT_TAG})">{vtranslate('LBL_REMOVE_TAG', $MODULE)}</a>
                                </li>
                            {/if}
                            <li class="divider hide" style="margin:9px 0px;"></li>
                            {assign var=FIND_DUPLICATES_EXITS value=false}
                            {foreach item=LISTVIEW_ADVANCEDACTIONS from=$LISTVIEW_LINKS['LISTVIEW']}
                                {if $LISTVIEW_ADVANCEDACTIONS->getLabel() == 'Print'}
                                    {assign var=PRINT_TEMPLATE value=$LISTVIEW_ADVANCEDACTIONS}
                                {else}
                                    {if $LISTVIEW_ADVANCEDACTIONS->getLabel() == 'LBL_FIND_DUPLICATES'}
                                        {assign var=FIND_DUPLICATES_EXISTS value=true}
                                    {/if}
                                {/if}
                            {/foreach}
                            {if $PRINT_TEMPLATE}
                                <li class="hide"><a id="{$MODULE}_listView_advancedAction_{Vtiger_Util_Helper::replaceSpaceWithUnderScores($PRINT_TEMPLATE->getLabel())}" {if stripos($PRINT_TEMPLATE->getUrl(), 'javascript:')===0} href="javascript:void(0);" onclick='{$PRINT_TEMPLATE->getUrl()|substr:strlen("javascript:")};'{else} href='{$PRINT_TEMPLATE->getUrl()}' {/if}>{vtranslate($PRINT_TEMPLATE->getLabel(), $MODULE)}</a></li>
                            {/if}
                            {if isset($FIND_DUPLICATES_EXISTS) && $FIND_DUPLICATES_EXISTS}
                                <li class="hide"><a id="{$MODULE}_listView_advancedAction_MERGE_RECORD" href="javascript:void(0);" onclick='Vtiger_List_Js.triggerMergeRecord()'>{vtranslate('LBL_MERGE_SELECTED_RECORDS', $MODULE)}</a></li>
                            {/if}
                            {foreach item=LISTVIEW_ADVANCEDACTIONS from=$LISTVIEW_LINKS['LISTVIEW']}
                                {if $LISTVIEW_ADVANCEDACTIONS->getLabel() == 'LBL_IMPORT'}
                                {elseif $LISTVIEW_ADVANCEDACTIONS->getLabel() == 'Print'}
                                    {assign var=PRINT_TEMPLATE value=$LISTVIEW_ADVANCEDACTIONS}
                                {else}
                                    {if $LISTVIEW_ADVANCEDACTIONS->getLabel() == 'LBL_FIND_DUPLICATES'}
                                        {assign var=FIND_DUPLICATES_EXISTS value=true}
                                    {/if}
                                    {if $LISTVIEW_ADVANCEDACTIONS->getLabel() != 'Print'}
                                        <li class="selectFreeRecords"><a id="{$MODULE}_listView_advancedAction_{Vtiger_Util_Helper::replaceSpaceWithUnderScores($LISTVIEW_ADVANCEDACTIONS->getLabel())}" {if stripos($LISTVIEW_ADVANCEDACTIONS->getUrl(), 'javascript:')===0} href="javascript:void(0);" onclick='{$LISTVIEW_ADVANCEDACTIONS->getUrl()|substr:strlen("javascript:")};'{else} href='{$LISTVIEW_ADVANCEDACTIONS->getUrl()}' {/if}>{vtranslate($LISTVIEW_ADVANCEDACTIONS->getLabel(), $MODULE)}</a></li>
                                        {if $MODULE == 'Quotes' && $LISTVIEW_ADVANCEDACTIONS->getLabel() == 'LBL_EXPORT'}
                                            <li class="selectFreeRecords"><a id="{$MODULE}_listView_advancedAction_ExportExcelForSale" href="javascript:void(0);" onclick='Vtiger_List_Js.triggerExportExcelForSale()'>{vtranslate('LBL_EXPORT_TO_EXCEL_FOR_SALE', $MODULE)}</a></li>
                                            <li class="selectFreeRecords"><a id="{$MODULE}_listView_advancedAction_ExportExcelForProject" href="javascript:void(0);" onclick='Vtiger_List_Js.triggerExportExcelForProject()'>{vtranslate('LBL_EXPORT_TO_EXCEL_FOR_PROJECT', $MODULE)}</a></li>
                                        {/if}
                                    {/if}
                                {/if}
                            {/foreach}
                        </ul>
                    </div>
                {/if}
            </div>

            <div class="mk-so-filter-row__right">
                <button type="button" class="mk-so-icon-btn mk-so-trigger-columns" title="{vtranslate('LBL_CLICK_HERE_TO_MANAGE_LIST_COLUMNS',$MODULE)}" aria-label="{vtranslate('LBL_CLICK_HERE_TO_MANAGE_LIST_COLUMNS',$MODULE)}">
                    <span class="mk-so-icon-btn__ic" aria-hidden="true"><span class="fa fa-th-large"></span></span>
                </button>
                <button type="button" class="mk-so-icon-btn mk-so-sort-hint" disabled="disabled" title="Sort" aria-hidden="true">
                    <span class="mk-so-icon-btn__ic" aria-hidden="true"><span class="fa fa-sort"></span></span>
                </button>
                <button type="button" class="mk-so-icon-btn mk-so-filter-trigger-search" title="{vtranslate('LBL_SEARCH',$MODULE)}" aria-label="{vtranslate('LBL_SEARCH',$MODULE)}">
                    <span class="mk-so-icon-btn__ic" aria-hidden="true">{include file="partials/DashboardTopbarSvgIcon.tpl"|@vtemplate_path:'Vtiger' ICON='SEARCH'}</span>
                </button>
            </div>
        </div>

        {if $LISTVIEW_ENTRIES_COUNT eq '0' and $REQUEST_INSTANCE and $REQUEST_INSTANCE->isAjax()}
            {if $smarty.session.lvs.$MODULE.viewname}
                {assign var=VIEWID value=$smarty.session.lvs.$MODULE.viewname}
            {/if}
            {if $VIEWID}
                {foreach item=FILTER_TYPES from=$CUSTOM_VIEWS}
                    {foreach item=FILTERS from=$FILTER_TYPES}
                        {if $FILTERS->get('cvid') eq $VIEWID}
                            {assign var=CVNAME value=$FILTERS->get('viewname')}
                            {break}
                        {/if}
                    {/foreach}
                {/foreach}
                {assign var=DEFAULT_FILTER_URL value=$MODULE_MODEL->getDefaultUrl()}
                {assign var=DEFAULT_FILTER_ID value=$MODULE_MODEL->getDefaultCustomFilter()}
                {if $DEFAULT_FILTER_ID}
                    {assign var=DEFAULT_FILTER_URL value=$MODULE_MODEL->getListViewUrl()|cat:"&viewname="|cat:$DEFAULT_FILTER_ID|cat:"&app="|cat:$SELECTED_MENU_CATEGORY}
                {/if}
                {if $CVNAME neq 'All'}
                    <div class="mk-so-ajax-empty-hint mk-so-ajax-empty-hint--toolbar">{vtranslate('LBL_DISPLAYING_RESULTS',$MODULE)} {vtranslate('LBL_FROM',$MODULE)} <b>{$CVNAME}</b>. <a style="color:blue" href='{$DEFAULT_FILTER_URL}'>{vtranslate('LBL_SEARCH_IN',$MODULE)} {vtranslate('All',$MODULE)} {vtranslate($MODULE, $MODULE)}</a></div>
                {/if}
            {/if}
        {/if}
        <div class="hide messageContainer mk-so-selectall-msg" style="height:30px;">
            <center><a href="#" id="selectAllMsgDiv">{vtranslate('LBL_SELECT_ALL',$MODULE)}&nbsp;{vtranslate($MODULE ,$MODULE)}&nbsp;(<span id="totalRecordsCount" value=""></span>)</a></center>
        </div>
        <div class="hide messageContainer mk-so-selectall-msg" style="height:30px;">
            <center><a href="#" id="deSelectAllMsgDiv">{vtranslate('LBL_DESELECT_ALL_RECORDS',$MODULE)}</a></center>
        </div>

        <div class="mk-so-filter-row__footer">
            <div class="mk-so-pagination">
                {include file="partials/MkSalesPaginationButtons.tpl"|vtemplate_path:'Vtiger' SHOWPAGEJUMP=true}
            </div>
        </div>
    </div>
{/strip}
