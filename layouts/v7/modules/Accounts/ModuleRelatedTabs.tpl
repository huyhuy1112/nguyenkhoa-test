{*+**********************************************************************************
* Accounts: SALES Detail adds flex spacer between primary tabs and related icons; else same as Vtiger.
*************************************************************************************}

{strip}
	<div class='related-tabs row {if !empty($MK_ACCOUNTS_MODERN_UI) || (isset($SELECTED_MENU_CATEGORY) && ($SELECTED_MENU_CATEGORY eq 'SALES' || $SELECTED_MENU_CATEGORY eq 'MARKETING')) || (isset($smarty.get.app) && ($smarty.get.app eq 'SALES' || $smarty.get.app eq 'MARKETING'))}mk-acc-detail-related-tabs{/if}'>
		<nav class="navbar margin0" role="navigation">
			<div class="navbar-header">
				<button type="button" class="navbar-toggle btn-group-justified collapsed border0" data-toggle="collapse" data-target="#nav-tabs" aria-expanded="false">
					<i class="fa fa-ellipsis-h"></i>
				</button>
			</div>

			<div class="collapse navbar-collapse" id="nav-tabs">
				<ul class="nav nav-tabs">
					{foreach item=RELATED_LINK from=$DETAILVIEW_LINKS['DETAILVIEWTAB']}
						{assign var=RELATEDLINK_URL value=$RELATED_LINK->getUrl()}
						{assign var=RELATEDLINK_LABEL value=$RELATED_LINK->getLabel()}
						{assign var=RELATED_TAB_LABEL value={vtranslate('SINGLE_'|cat:$MODULE_NAME, $MODULE_NAME)}|cat:" "|cat:$RELATEDLINK_LABEL}
						<li class="tab-item {if $RELATED_TAB_LABEL==$SELECTED_TAB_LABEL}active{/if}" data-url="{$RELATEDLINK_URL}&tab_label={$RELATED_TAB_LABEL}&app={$SELECTED_MENU_CATEGORY}" data-label-key="{$RELATEDLINK_LABEL}" data-link-key="{$RELATED_LINK->get('linkKey')}" >
							<a href="{$RELATEDLINK_URL}&tab_label={$RELATEDLINK_LABEL}&app={$SELECTED_MENU_CATEGORY}" class="textOverflowEllipsis">
								<span class="tab-label"><strong>{vtranslate($RELATEDLINK_LABEL,{$MODULE_NAME})}</strong></span>
							</a>
						</li>
					{/foreach}

{if !empty($MK_ACCOUNTS_MODERN_UI) || (isset($SELECTED_MENU_CATEGORY) && ($SELECTED_MENU_CATEGORY eq 'SALES' || $SELECTED_MENU_CATEGORY eq 'MARKETING')) || (isset($smarty.get.app) && ($smarty.get.app eq 'SALES' || $smarty.get.app eq 'MARKETING'))}
					<li class="mk-acc-detail-tabs-spacer" role="presentation" aria-hidden="true"></li>
{/if}

                                        {if isset($DETAILVIEW_LINKS['DETAILVIEWRELATED'])}
					        {assign var=RELATEDTABS value=$DETAILVIEW_LINKS['DETAILVIEWRELATED']}
                                        {/if}
                                        {if !empty($RELATEDTABS)}
                                            {assign var=COUNT value=$RELATEDTABS|@count}

                                            {assign var=LIMIT value = 10}
                                            {if $COUNT gt 10}
                                                    {assign var=COUNT1 value = $LIMIT}
                                            {else}
                                                    {assign var=COUNT1 value=$COUNT}
                                            {/if}

                                            {for $i = 0 to $COUNT1-1}
                                                    {assign var=RELATED_LINK value=$RELATEDTABS[$i]}
                                                    {assign var=RELATEDMODULENAME value=$RELATED_LINK->getRelatedModuleName()}
                                                    {assign var=RELATEDFIELDNAME value=$RELATED_LINK->get('linkFieldName')}
                                                    {assign var="DETAILVIEWRELATEDLINKLBL" value= vtranslate($RELATED_LINK->getLabel(),$RELATEDMODULENAME)}
                                                    <li class="tab-item {if (trim($RELATED_LINK->getLabel())== trim($SELECTED_TAB_LABEL)) && ($RELATED_LINK->getId() == $SELECTED_RELATION_ID)}active{/if}" data-url="{$RELATED_LINK->getUrl()}&tab_label={$RELATED_LINK->getLabel()}&app={$SELECTED_MENU_CATEGORY}" data-label-key="{$RELATED_LINK->getLabel()}"
                                                            data-module="{$RELATEDMODULENAME}" data-relation-id="{$RELATED_LINK->getId()}" {if $RELATEDMODULENAME eq "ModComments"} title {else} title="{$DETAILVIEWRELATEDLINKLBL}"{/if} {if $RELATEDFIELDNAME}data-relatedfield ="{$RELATEDFIELDNAME}"{/if}>
                                                            <a href="index.php?{$RELATED_LINK->getUrl()}&tab_label={$RELATED_LINK->getLabel()}&app={$SELECTED_MENU_CATEGORY}" class="textOverflowEllipsis" displaylabel="{$DETAILVIEWRELATEDLINKLBL}" recordsCount="" >
                                                                    {if !empty($MK_ACCOUNTS_MODERN_UI) || (isset($SELECTED_MENU_CATEGORY) && ($SELECTED_MENU_CATEGORY eq 'SALES' || $SELECTED_MENU_CATEGORY eq 'MARKETING')) || (isset($smarty.get.app) && ($smarty.get.app eq 'SALES' || $smarty.get.app eq 'MARKETING'))}
                                                                    <span class="tab-icon mk-acc-tab-icon">
                                                                            {include file="partials/AccountsDetailTabSvgIcon.tpl"|@vtemplate_path:$MODULE MODULE=$RELATEDMODULENAME}
                                                                    </span>
                                                                    {else}
                                                                    {if $RELATEDMODULENAME eq "ModComments"}
                                                                            <span class="tab-icon"><i class="fa fa-comment" style="font-size: 24px"></i></span>
                                                                    {else}
                                                                            <span class="tab-icon">
                                                                                    {assign var=RELATED_MODULE_MODEL value=Vtiger_Module_Model::getInstance($RELATEDMODULENAME)}
                                                                                    {$RELATED_MODULE_MODEL->getModuleIcon()}
                                                                            </span>
                                                                    {/if}
                                                                    {/if}
                                                                    &nbsp;<span class="numberCircle hide">0</span>
                                                            </a>
                                                    </li>
                                                    {if ($RELATED_LINK->getId() == {$REQ->get('relationId')})}
                                                            {assign var=MORE_TAB_ACTIVE value='true'}
                                                    {/if}
                                            {/for}
                                            {if $MORE_TAB_ACTIVE neq 'true'}
                                                    {for $i = 0 to $COUNT-1}
                                                            {assign var=RELATED_LINK value=$RELATEDTABS[$i]}
                                                            {if ($RELATED_LINK->getId() == {$REQ->get('relationId')})}
                                                                    {assign var=RELATEDMODULENAME value=$RELATED_LINK->getRelatedModuleName()}
                                                                    {assign var=RELATEDFIELDNAME value=$RELATED_LINK->get('linkFieldName')}
                                                                    {assign var="DETAILVIEWRELATEDLINKLBL" value= vtranslate($RELATED_LINK->getLabel(),$RELATEDMODULENAME)}
                                                                    <li class="more-tab moreTabElement active"  data-url="{$RELATED_LINK->getUrl()}&tab_label={$RELATED_LINK->getLabel()}&app={$SELECTED_MENU_CATEGORY}" data-label-key="{$RELATED_LINK->getLabel()}"
                                                                            data-module="{$RELATEDMODULENAME}" data-relation-id="{$RELATED_LINK->getId()}" {if $RELATEDMODULENAME eq "ModComments"} title {else} title="{$DETAILVIEWRELATEDLINKLBL}"{/if} {if $RELATEDFIELDNAME}data-relatedfield ="{$RELATEDFIELDNAME}"{/if}>
                                                                            <a href="index.php?{$RELATED_LINK->getUrl()}&tab_label={$RELATED_LINK->getLabel()}&app={$SELECTED_MENU_CATEGORY}" class="textOverflowEllipsis" displaylabel="{$DETAILVIEWRELATEDLINKLBL}" recordsCount="" >
                                                                                    {if $RELATEDMODULENAME eq "ModComments"}
                                                                                            <span class="tab-icon"><i class="fa fa-comment" style="font-size: 24px"></i></span>
                                                                                    {else}
                                                                                            <span class="tab-icon">
                                                                                                    {assign var=RELATED_MODULE_MODEL value=Vtiger_Module_Model::getInstance($RELATEDMODULENAME)}
                                                                                                    {$RELATED_MODULE_MODEL->getModuleIcon()}
                                                                                            </span>
                                                                                    {/if}
                                                                                    &nbsp;<span class="numberCircle hide">0</span>
                                                                            </a>
                                                                    </li>
                                                                    {break}
                                                            {/if}
                                                    {/for}
                                            {/if}
                                            {if $COUNT gt $LIMIT}
                                                    <li class="dropdown related-tab-more-element">
                                                            <a href="javascript:void(0)" data-toggle="dropdown" class="dropdown-toggle">
                                                                    <span class="tab-label">
                                                                            <strong>{vtranslate("LBL_MORE",$MODULE_NAME)}</strong> &nbsp; <b class="fa fa-caret-down"></b>
                                                                    </span>
                                                            </a>
                                                            <ul class="dropdown-menu pull-right" id="relatedmenuList">
                                                                    {for $j = $COUNT1 to $COUNT-1}
                                                                            {assign var=RELATED_LINK value=$RELATEDTABS[$j]}
                                                                            {assign var=RELATEDMODULENAME value=$RELATED_LINK->getRelatedModuleName()}
                                                                            {assign var=RELATEDFIELDNAME value=$RELATED_LINK->get('linkFieldName')}
                                                                            {assign var="DETAILVIEWRELATEDLINKLBL" value= vtranslate($RELATED_LINK->getLabel(),$RELATEDMODULENAME)}
                                                                            <li class="more-tab {if (trim($RELATED_LINK->getLabel())== trim($SELECTED_TAB_LABEL)) && ($RELATED_LINK->getId() == $SELECTED_RELATION_ID)}active{/if}" data-url="{$RELATED_LINK->getUrl()}&tab_label={$RELATED_LINK->getLabel()}&app={$SELECTED_MENU_CATEGORY}" data-label-key="{$RELATED_LINK->getLabel()}"
                                                                                    data-module="{$RELATEDMODULENAME}" title="" data-relation-id="{$RELATED_LINK->getId()}" {if $RELATEDFIELDNAME}data-relatedfield ="{$RELATEDFIELDNAME}"{/if}>
                                                                                    <a href="index.php?{$RELATED_LINK->getUrl()}&tab_label={$RELATED_LINK->getLabel()}&app={$SELECTED_MENU_CATEGORY}" displaylabel="{$DETAILVIEWRELATEDLINKLBL}" recordsCount="">
                                                                                            {if !empty($MK_ACCOUNTS_MODERN_UI) || (isset($SELECTED_MENU_CATEGORY) && ($SELECTED_MENU_CATEGORY eq 'SALES' || $SELECTED_MENU_CATEGORY eq 'MARKETING')) || (isset($smarty.get.app) && ($smarty.get.app eq 'SALES' || $smarty.get.app eq 'MARKETING'))}
                                                                                            <span class="tab-icon textOverflowEllipsis mk-acc-tab-icon">
                                                                                                    {include file="partials/AccountsDetailTabSvgIcon.tpl"|@vtemplate_path:$MODULE MODULE=$RELATEDMODULENAME}
                                                                                                    <span class="content"> &nbsp;{$DETAILVIEWRELATEDLINKLBL}</span>
                                                                                            </span>
                                                                                            {else}
                                                                                            {if $RELATEDMODULENAME eq "ModComments"}
                                                                                                <span class="tab-icon textOverflowEllipsis">
                                                                                                    <i class="fa fa-comment"></i> &nbsp;<span class="content">{$DETAILVIEWRELATEDLINKLBL}</span>
                                                                                                </span>
                                                                                            {else}
                                                                                                    {assign var=RELATED_MODULE_MODEL value=Vtiger_Module_Model::getInstance($RELATEDMODULENAME)}
                                                                                                    <span class="tab-icon textOverflowEllipsis">
                                                                                                            {$RELATED_MODULE_MODEL->getModuleIcon()}
                                                                                                            <span class="content"> &nbsp;{$DETAILVIEWRELATEDLINKLBL}</span>
                                                                                                    </span>
                                                                                            {/if}
                                                                                            {/if}
                                                                                            &nbsp;<span class="numberCircle hide">0</span>
                                                                                    </a>
                                                                            </li>
                                                                    {/for}
                                                            </ul>
                                                    </li>
                                            {/if}
                                        {/if}
				</ul>
			</div>
		</nav>
	</div>
{/strip}
