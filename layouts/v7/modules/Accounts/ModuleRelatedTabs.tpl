{* Accounts: SALES/MARKETING Detail — primary tabs + collapsible related icon row. *}
{strip}
	{assign var=MK_ACC_SALES_TABS value=(!empty($MK_ACCOUNTS_MODERN_UI) || (isset($SELECTED_MENU_CATEGORY) && ($SELECTED_MENU_CATEGORY eq 'SALES' || $SELECTED_MENU_CATEGORY eq 'MARKETING')) || (isset($smarty.get.app) && ($smarty.get.app eq 'SALES' || $smarty.get.app eq 'MARKETING')))}
	<div class='related-tabs row {if $MK_ACC_SALES_TABS}mk-acc-detail-related-tabs{/if}'>
		<nav class="navbar margin0" role="navigation">
			<div class="navbar-header">
				<button type="button" class="navbar-toggle btn-group-justified collapsed border0" data-toggle="collapse" data-target="#nav-tabs" aria-expanded="false">
					{if $MK_ACC_SALES_TABS}
					<span class="mk-acc-tab-more-svg mk-acc-tab-more-svg--dots" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><circle cx="5" cy="12" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="19" cy="12" r="2"/></svg></span>
					{else}<i class="fa fa-ellipsis-h"></i>{/if}
				</button>
			</div>

			<div class="collapse navbar-collapse" id="nav-tabs">
				<ul class="nav nav-tabs mk-sales-primary-tabs">
					{foreach item=RELATED_LINK from=$DETAILVIEW_LINKS['DETAILVIEWTAB']}
						{assign var=RELATEDLINK_URL value=$RELATED_LINK->getUrl()}
						{assign var=RELATEDLINK_LABEL value=$RELATED_LINK->getLabel()}
						{assign var=RELATED_TAB_LABEL value={vtranslate('SINGLE_'|cat:$MODULE_NAME, $MODULE_NAME)}|cat:" "|cat:$RELATEDLINK_LABEL}
						<li class="tab-item {if $RELATED_TAB_LABEL==$SELECTED_TAB_LABEL}active{/if}" data-url="{$RELATEDLINK_URL}&tab_label={$RELATED_TAB_LABEL}&app={$SELECTED_MENU_CATEGORY}" data-label-key="{$RELATEDLINK_LABEL}" data-link-key="{$RELATED_LINK->get('linkKey')}" >
							<a href="{$RELATEDLINK_URL}&tab_label={$RELATEDLINK_LABEL}&app={$SELECTED_MENU_CATEGORY}" class="{if !$MK_ACC_SALES_TABS}textOverflowEllipsis{/if} mk-acc-detail-primary-tab-link mk-sales-primary-tab-link">
								<span class="tab-label"><strong>{vtranslate($RELATEDLINK_LABEL,{$MODULE_NAME})}</strong></span>
							</a>
						</li>
					{/foreach}

					{if $MK_ACC_SALES_TABS}
						{include file="partials/MkSalesRelatedToggleBtn.tpl"|@vtemplate_path:'Vtiger'}
					{else}
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
									<a href="javascript:void(0)" data-toggle="dropdown" class="dropdown-toggle" title="{vtranslate('LBL_MORE',$MODULE_NAME)}" aria-label="{vtranslate('LBL_MORE',$MODULE_NAME)}" aria-haspopup="true" aria-expanded="false">
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
													&nbsp;<span class="numberCircle hide">0</span>
												</a>
											</li>
										{/for}
									</ul>
								</li>
							{/if}
						{/if}
					{/if}
				</ul>

				{if $MK_ACC_SALES_TABS}
				{if isset($DETAILVIEW_LINKS['DETAILVIEWRELATED'])}
					{assign var=RELATEDTABS value=$DETAILVIEW_LINKS['DETAILVIEWRELATED']}
				{/if}
				{if !empty($RELATEDTABS)}
				<div class="mk-sales-related-icons-panel" id="mk-sales-related-icons-panel" aria-hidden="true">
					<ul class="nav nav-tabs mk-sales-related-icons-row">
						{assign var=COUNT value=$RELATEDTABS|@count}
						{for $i = 0 to $COUNT-1}
							{assign var=RELATED_LINK value=$RELATEDTABS[$i]}
							{assign var=RELATEDMODULENAME value=$RELATED_LINK->getRelatedModuleName()}
							{if $RELATEDMODULENAME eq 'Products'}{continue}{/if}
							{assign var=RELATEDFIELDNAME value=$RELATED_LINK->get('linkFieldName')}
							{assign var="DETAILVIEWRELATEDLINKLBL" value= vtranslate($RELATED_LINK->getLabel(),$RELATEDMODULENAME)}
							<li class="tab-item {if (trim($RELATED_LINK->getLabel())== trim($SELECTED_TAB_LABEL)) && ($RELATED_LINK->getId() == $SELECTED_RELATION_ID)}active{/if}" data-url="{$RELATED_LINK->getUrl()}&tab_label={$RELATED_LINK->getLabel()}&app={$SELECTED_MENU_CATEGORY}" data-label-key="{$RELATED_LINK->getLabel()}"
									data-module="{$RELATEDMODULENAME}" data-relation-id="{$RELATED_LINK->getId()}" {if $RELATEDMODULENAME eq "ModComments"} title {else} title="{$DETAILVIEWRELATEDLINKLBL}"{/if} {if $RELATEDFIELDNAME}data-relatedfield ="{$RELATEDFIELDNAME}"{/if}>
								<a href="index.php?{$RELATED_LINK->getUrl()}&tab_label={$RELATED_LINK->getLabel()}&app={$SELECTED_MENU_CATEGORY}" class="textOverflowEllipsis" displaylabel="{$DETAILVIEWRELATEDLINKLBL}" recordsCount="" >
									<span class="tab-icon mk-acc-tab-icon-svg" aria-hidden="true">{include file="partials/AccountsDetailTabSvgIcon.tpl"|@vtemplate_path:$MODULE MODULE=$RELATEDMODULENAME}</span>
									&nbsp;<span class="numberCircle hide">0</span>
								</a>
							</li>
						{/for}
					</ul>
				</div>
				{/if}
				{/if}
			</div>
		</nav>
	</div>
{/strip}
