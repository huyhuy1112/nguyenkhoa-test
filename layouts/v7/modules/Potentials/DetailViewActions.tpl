{* Potentials Detail actions: Sales layout + SVG icons + primary Send Email CTA. *}
{strip}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SALES') || (isset($smarty.get.app) && $smarty.get.app eq 'SALES')}
    {assign var=MK_OPP_ACTION_COL value="detailViewButtoncontainer mk-opportunity-detail-actions"}
    <div class="{$MK_OPP_ACTION_COL}">
        <div class="pull-right btn-toolbar mk-opportunity-detail-actions__toolbar">
            <div class="btn-group mk-opportunity-detail-actions__group">
            {assign var=STARRED value=$RECORD->get('starred')}
            {if $MODULE_MODEL->isStarredEnabled()}
                <button type="button" class="btn btn-default mk-opportunity-detail-btn mk-opportunity-detail-btn--ghost markStar {if $STARRED} active {/if}" id="starToggle">
                    <span class="mk-opportunity-detail-btn__ic mk-opportunity-detail-btn__ic--follow" aria-hidden="true">{include file="partials/OpportunityDetailSvgIcon.tpl"|@vtemplate_path:$MODULE ICON='FOLLOW'}</span>
                    <div class="starredStatus hide" title="{vtranslate('LBL_STARRED', $MODULE)}">
                        <div class="unfollowMessage"><span class="mk-opportunity-detail-btn__txt">{vtranslate('LBL_UNFOLLOW',$MODULE)}</span></div>
                        <div class="followMessage"><span class="mk-opportunity-detail-btn__txt">{vtranslate('LBL_FOLLOWING',$MODULE)}</span></div>
                    </div>
                    <div class="unstarredStatus" title="{vtranslate('LBL_NOT_STARRED', $MODULE)}">
                        <span class="mk-opportunity-detail-btn__txt">{vtranslate('LBL_FOLLOW',$MODULE)}</span>
                    </div>
                </button>
            {/if}
            {foreach item=DETAIL_VIEW_BASIC_LINK from=$DETAILVIEW_LINKS['DETAILVIEWBASIC']}
                {assign var=MK_BASIC_LBL value=$DETAIL_VIEW_BASIC_LINK->getLabel()}
                {assign var=MK_BASIC_ICON value='EDIT'}
                {if $MK_BASIC_LBL eq 'LBL_SEND_EMAIL'}{assign var=MK_BASIC_ICON value='EMAIL'}{/if}
                {if $MK_BASIC_LBL eq 'LBL_CREATE_PROJECT' or $MK_BASIC_LBL eq 'LBL_VIEW_PROJECT'}{assign var=MK_BASIC_ICON value='EDIT'}{/if}
                <button type="button" class="btn btn-default mk-opportunity-detail-btn {if $MK_BASIC_LBL eq 'LBL_SEND_EMAIL'}mk-opportunity-detail-btn--primary{else}mk-opportunity-detail-btn--ghost{/if}" id="{$MODULE_NAME}_detailView_basicAction_{Vtiger_Util_Helper::replaceSpaceWithUnderScores($MK_BASIC_LBL)}"
                        {if $DETAIL_VIEW_BASIC_LINK->isPageLoadLink() && !$DETAIL_VIEW_BASIC_LINK->getUrl()|strstr:'app='}
                            onclick="window.location.href = '{$DETAIL_VIEW_BASIC_LINK->getUrl()}&app={$SELECTED_MENU_CATEGORY}'"
                        {elseif $DETAIL_VIEW_BASIC_LINK->isPageLoadLink()}
                            onclick="window.location.href = '{$DETAIL_VIEW_BASIC_LINK->getUrl()}'"
                        {else}
                            onclick="{$DETAIL_VIEW_BASIC_LINK->getUrl()}"
                        {/if}
                        {if $MODULE_NAME eq 'Documents' && $MK_BASIC_LBL eq 'LBL_VIEW_FILE'}
                            data-filelocationtype="{$DETAIL_VIEW_BASIC_LINK->get('filelocationtype')}" data-filename="{$DETAIL_VIEW_BASIC_LINK->get('filename')}"
                        {/if}>
                    {if $MK_BASIC_LBL eq 'LBL_SEND_EMAIL' or $MK_BASIC_LBL eq 'LBL_EDIT'}
                        <span class="mk-opportunity-detail-btn__ic" aria-hidden="true">{include file="partials/OpportunityDetailSvgIcon.tpl"|@vtemplate_path:$MODULE ICON=$MK_BASIC_ICON}</span>
                    {/if}
                    <span class="mk-opportunity-detail-btn__txt">{vtranslate($MK_BASIC_LBL, $MODULE_NAME)}</span>
                </button>
            {/foreach}

			<button type="button" class="btn btn-default mk-opportunity-detail-btn mk-opportunity-detail-btn--primary" id="mkOppCallCustomerBtn" title="Gọi khách hàng">
				<span class="mk-opportunity-detail-btn__ic" aria-hidden="true">📞</span>
				<span class="mk-opportunity-detail-btn__txt">Gọi khách hàng</span>
			</button>
			<button type="button" class="btn btn-default mk-opportunity-detail-btn mk-opportunity-detail-btn--ghost" id="mkOppConvertToCustomerBtn">
				<span class="mk-opportunity-detail-btn__ic" aria-hidden="true">{include file="partials/OpportunityDetailSvgIcon.tpl"|@vtemplate_path:$MODULE ICON='EDIT'}</span>
				<span class="mk-opportunity-detail-btn__txt">Chuyển sang Khách hàng</span>
			</button>
            {if !empty($DETAILVIEW_LINKS['DETAILVIEW']) && ($DETAILVIEW_LINKS['DETAILVIEW']|@count gt 0)}
                <button type="button" class="btn btn-default mk-opportunity-detail-btn mk-opportunity-detail-btn--ghost dropdown-toggle" data-toggle="dropdown" href="javascript:void(0);">
                    <span class="mk-opportunity-detail-btn__ic" aria-hidden="true">{include file="partials/OpportunityDetailSvgIcon.tpl"|@vtemplate_path:$MODULE ICON='MORE'}</span>
                    <span class="mk-opportunity-detail-btn__txt">{vtranslate('LBL_MORE', $MODULE_NAME)}</span>
                    <span class="caret"></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-right mk-opportunity-detail-menu">
                    {foreach item=DETAIL_VIEW_LINK from=$DETAILVIEW_LINKS['DETAILVIEW']}
                        {if $DETAIL_VIEW_LINK->getLabel() eq ""}
                            <li class="divider mk-opportunity-detail-menu__divider" role="separator"></li>
                        {else}
                            <li id="{$MODULE_NAME}_detailView_moreAction_{Vtiger_Util_Helper::replaceSpaceWithUnderScores($DETAIL_VIEW_LINK->getLabel())}" class="mk-opportunity-detail-menu__item">
                                {if $DETAIL_VIEW_LINK->getUrl()|strstr:"javascript"}
                                    <a href="{$DETAIL_VIEW_LINK->getUrl()}" class="mk-opportunity-detail-menu__link">{vtranslate($DETAIL_VIEW_LINK->getLabel(), $MODULE_NAME)}</a>
                                {elseif $DETAIL_VIEW_LINK->getUrl()|strstr:'app='}
                                    <a href="{$DETAIL_VIEW_LINK->getUrl()}" class="mk-opportunity-detail-menu__link">{vtranslate($DETAIL_VIEW_LINK->getLabel(), $MODULE_NAME)}</a>
                                {else}
                                    <a href="{$DETAIL_VIEW_LINK->getUrl()}&app={$SELECTED_MENU_CATEGORY}" class="mk-opportunity-detail-menu__link">{vtranslate($DETAIL_VIEW_LINK->getLabel(), $MODULE_NAME)}</a>
                                {/if}
                            </li>
                        {/if}
                    {/foreach}
                </ul>
            {/if}
            </div>
            {if !{$NO_PAGINATION}}
            <div class="btn-group pull-right mk-opportunity-detail-actions__pager">
                <button type="button" class="btn btn-default mk-opportunity-detail-btn mk-opportunity-detail-btn--ghost" id="detailViewPreviousRecordButton" {if empty($PREVIOUS_RECORD_URL)} disabled="disabled" {else} onclick="window.location.href = '{$PREVIOUS_RECORD_URL}&app={$SELECTED_MENU_CATEGORY}'" {/if}>
                    <i class="fa fa-chevron-left"></i>
                </button>
                <button type="button" class="btn btn-default mk-opportunity-detail-btn mk-opportunity-detail-btn--ghost" id="detailViewNextRecordButton" {if empty($NEXT_RECORD_URL)} disabled="disabled" {else} onclick="window.location.href = '{$NEXT_RECORD_URL}&app={$SELECTED_MENU_CATEGORY}'" {/if}>
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
