{*<!--
/*********************************************************************************
** Accounts Detail actions: Sales layout column + primary CTA class on Send Email; other apps delegate column class to stock.
********************************************************************************/
-->*}
{strip}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SALES') || (isset($smarty.get.app) && $smarty.get.app eq 'SALES')}
    {assign var=MK_ACC_ACTION_COL value="detailViewButtoncontainer mk-acc-detail-actions"}
{else}
    {assign var=MK_ACC_ACTION_COL value="col-lg-6 detailViewButtoncontainer"}
{/if}
    <div class="{$MK_ACC_ACTION_COL}">
        <div class="pull-right btn-toolbar mk-acc-detail-actions__toolbar">
            <div class="btn-group mk-acc-detail-actions__group">
            {assign var=STARRED value=$RECORD->get('starred')}
            {if $MODULE_MODEL->isStarredEnabled()}
                <button class="btn btn-default mk-acc-detail-btn mk-acc-detail-btn--ghost markStar {if $STARRED} active {/if}" id="starToggle">
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
                {assign var=MK_BASIC_LBL value=$DETAIL_VIEW_BASIC_LINK->getLabel()}
                <button class="btn btn-default mk-acc-detail-btn {if $MK_BASIC_LBL eq 'LBL_SEND_EMAIL'}mk-acc-detail-btn--primary{/if}" id="{$MODULE_NAME}_detailView_basicAction_{Vtiger_Util_Helper::replaceSpaceWithUnderScores($MK_BASIC_LBL)}"
                        {if $DETAIL_VIEW_BASIC_LINK->isPageLoadLink()}
                            onclick="window.location.href = '{$DETAIL_VIEW_BASIC_LINK->getUrl()}&app={$SELECTED_MENU_CATEGORY}'"
                        {else}
                            onclick="{$DETAIL_VIEW_BASIC_LINK->getUrl()}"
                        {/if}
                        {if $MODULE_NAME eq 'Documents' && $MK_BASIC_LBL eq 'LBL_VIEW_FILE'}
                            data-filelocationtype="{$DETAIL_VIEW_BASIC_LINK->get('filelocationtype')}" data-filename="{$DETAIL_VIEW_BASIC_LINK->get('filename')}"
                        {/if}>
                    {vtranslate($MK_BASIC_LBL, $MODULE_NAME)}
                </button>
            {/foreach}
            {if !empty($DETAILVIEW_LINKS['DETAILVIEW']) && ($DETAILVIEW_LINKS['DETAILVIEW']|@count gt 0)}
                <button class="btn btn-default mk-acc-detail-btn mk-acc-detail-btn--ghost dropdown-toggle" data-toggle="dropdown" href="javascript:void(0);">
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
                                    <a href='{$DETAIL_VIEW_LINK->getUrl()}&app={$SELECTED_MENU_CATEGORY}' >{vtranslate($DETAIL_VIEW_LINK->getLabel(), $MODULE_NAME)}</a>
                                {/if}
                            </li>
                        {/if}
                    {/foreach}
                </ul>
            {/if}
            </div>
            {if !{$NO_PAGINATION}}
            <div class="btn-group pull-right mk-acc-detail-actions__pager">
                <button class="btn btn-default mk-acc-detail-btn mk-acc-detail-btn--ghost" id="detailViewPreviousRecordButton" {if empty($PREVIOUS_RECORD_URL)} disabled="disabled" {else} onclick="window.location.href = '{$PREVIOUS_RECORD_URL}&app={$SELECTED_MENU_CATEGORY}'" {/if} >
                    <i class="fa fa-chevron-left"></i>
                </button>
                <button class="btn btn-default mk-acc-detail-btn mk-acc-detail-btn--ghost" id="detailViewNextRecordButton"{if empty($NEXT_RECORD_URL)} disabled="disabled" {else} onclick="window.location.href = '{$NEXT_RECORD_URL}&app={$SELECTED_MENU_CATEGORY}'" {/if}>
                    <i class="fa fa-chevron-right"></i>
                </button>
            </div>
            {/if}
        </div>
        <input type="hidden" name="record_id" value="{$RECORD->getId()}">
    </div>
{/strip}
