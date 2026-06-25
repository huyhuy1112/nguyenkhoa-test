{*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************}
{* modules/Users/views/Detail.php *}

{strip}
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Users/resources/UsersPrefDetailContent.css')}&mk_v=20260625_users_detail_ui2" />
<div class="detailViewContainer bace-users-settings-detail">
    <div class="col-sm-12 col-xs-12">
        <div class="mk-users-pref-hero" id="userPageHeader">
            <div class="mk-users-pref-hero__main">
                <div class="mk-users-pref-hero__avatar" aria-hidden="true">
                    {assign var=MK_USER_HAS_AVATAR value=false}
                    {foreach key=ITER item=IMAGE_INFO from=$RECORD->getImageDetails()}
                        {if !empty($IMAGE_INFO.url)}
                            {assign var=MK_USER_HAS_AVATAR value=true}
                            <img class="mk-users-pref-hero__avatar-img" src="{$IMAGE_INFO.url}" alt="{$IMAGE_INFO.orgname|escape:'html'}" title="{$IMAGE_INFO.orgname|escape:'html'}" data-image-id="{$IMAGE_INFO.id}" onerror="this.style.display='none';var f=this.nextElementSibling;if(f)f.style.display='flex';">
                            <span class="mk-users-pref-hero__initials mk-users-pref-hero__initials--fallback" style="display:none;">{$RECORD->getName()|substr:0:2}</span>
                        {/if}
                    {/foreach}
                    {if !$MK_USER_HAS_AVATAR}
                        <span class="mk-users-pref-hero__initials">{$RECORD->getName()|substr:0:2}</span>
                    {/if}
                </div>
                <div class="mk-users-pref-hero__text">
                    <h2 class="mk-users-pref-hero__title">{$RECORD->getName()|escape}</h2>
                    <p class="mk-users-pref-hero__subtitle">{vtranslate('LBL_USERDETAIL_INFO', $MODULE)}</p>
                </div>
            </div>
            <div class="mk-users-pref-hero__actions detailViewButtoncontainer">
                <div class="btn-group">
                {if isset($DETAILVIEW_LINKS)}
                    {foreach item=DETAIL_VIEW_BASIC_LINK from=$DETAILVIEW_LINKS['DETAILVIEWBASIC']}
                        <button class="btn btn-default" id="{$MODULE}_detailView_basicAction_{Vtiger_Util_Helper::replaceSpaceWithUnderScores($DETAIL_VIEW_BASIC_LINK->getLabel())}"
                                {if $DETAIL_VIEW_BASIC_LINK->isPageLoadLink()}
                                    onclick="window.location.href='{$DETAIL_VIEW_BASIC_LINK->getUrl()}'"
                                {else}
                                    onclick="{$DETAIL_VIEW_BASIC_LINK->getUrl()}"
                                {/if}>
                            {vtranslate($DETAIL_VIEW_BASIC_LINK->getLabel(), $MODULE)}
                        </button>
                    {/foreach}
                    {if $DETAILVIEW_LINKS['DETAILVIEW']|@count gt 0}
                        <button class="btn btn-default" data-toggle="dropdown" href="javascript:void(0);">
                            {vtranslate('LBL_MORE', $MODULE)}&nbsp;<i class="caret"></i>
                        </button>
                        <ul class="dropdown-menu pull-right">
                            {foreach item=DETAIL_VIEW_LINK from=$DETAILVIEW_LINKS['DETAILVIEW']}
                                {if $DETAIL_VIEW_LINK->getLabel() eq "Delete"}
                                    {if $CURRENT_USER_MODEL->isAdminUser() && $CURRENT_USER_MODEL->getId() neq $RECORD->getId()}
                                        <li id="{$MODULE}_detailView_moreAction_{Vtiger_Util_Helper::replaceSpaceWithUnderScores($DETAIL_VIEW_LINK->getLabel())}">
                                        <a href={$DETAIL_VIEW_LINK->getUrl()} >{vtranslate($DETAIL_VIEW_LINK->getLabel(), $MODULE)}</a>
                                    {/if}
                                {else}
                                    <li id="{$MODULE}_detailView_moreAction_{Vtiger_Util_Helper::replaceSpaceWithUnderScores($DETAIL_VIEW_LINK->getLabel())}">
                                        <a href={$DETAIL_VIEW_LINK->getUrl()} >{vtranslate($DETAIL_VIEW_LINK->getLabel(), $MODULE)}</a>
                                    </li>
                                {/if}
                            {/foreach}
                        </ul>
                    {/if}
                {/if}
                </div>
            </div>
        </div>
        <div class="detailview-content userPreferences container-fluid">
            {assign var="MODULE_NAME" value=$MODULE_MODEL->get('name')}
            <input id="recordId" type="hidden" value="{$RECORD->getId()}" />
            <div class="details row">
{/strip}
