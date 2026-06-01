{* Settings accordion submenu — shown inside DashboardSidebar when parent=Settings *}
{strip}
{if isset($SETTINGS_MENUS) && $USER_MODEL->isAdminUser()}
	<div class="mk-settings-subnav" id="mk-settings-subnav" aria-label="{vtranslate('LBL_SETTINGS', $QUALIFIED_MODULE|default:'Settings:Vtiger')}">
		<p class="mk-settings-subnav-kicker">{vtranslate('LBL_SETTINGS', 'Vtiger')}</p>
		<div class="mk-settings-subnav-search">
			<span class="mk-settings-subnav-search-ic fa fa-search" aria-hidden="true"></span>
			<input type="text" placeholder="{vtranslate('LBL_SEARCH_FOR_SETTINGS', $QUALIFIED_MODULE|default:'Settings:Vtiger')}" class="search-list mk-settings-subnav-input" id="settingsMenuSearch" autocomplete="off">
		</div>
		<div class="mk-settings-subnav-groups settingsgroup" id="accordion">
			{foreach item=BLOCK_MENUS from=$SETTINGS_MENUS}
				{assign var=BLOCK_NAME value=$BLOCK_MENUS->getLabel()}
				{assign var=BLOCK_MENU_ITEMS value=$BLOCK_MENUS->getMenuItems()}
				{assign var=NUM_OF_MENU_ITEMS value=$BLOCK_MENU_ITEMS|@php7_sizeof}
				{if $NUM_OF_MENU_ITEMS gt 0}
					{assign var=_blockOpen value=(isset($ACTIVE_BLOCK.block) && $ACTIVE_BLOCK.block eq $BLOCK_NAME)}
					<div class="mk-settings-subnav-group instaSearch settingsgroup-panel">
						<button type="button" class="mk-settings-subnav-group-toggle{if $_blockOpen} is-open{/if}" data-toggle="collapse" data-target="#mk-settings-block-{$BLOCK_NAME}" aria-expanded="{if $_blockOpen}true{else}false{/if}">
							<span class="mk-settings-subnav-group-label">{vtranslate($BLOCK_NAME, $QUALIFIED_MODULE|default:'Settings:Vtiger')}</span>
							<span class="mk-settings-subnav-group-chevron" aria-hidden="true">{include file="dashboards/DashboardSidebarSvgIcon.tpl"|@vtemplate_path:'Vtiger' ICON='CHEVRON'}</span>
						</button>
						<div id="mk-settings-block-{$BLOCK_NAME}" class="mk-settings-subnav-panel collapse{if $_blockOpen} in{/if}">
							<ul class="mk-settings-subnav-links list-group widgetContainer">
								{foreach item=MENUITEM from=$BLOCK_MENU_ITEMS}
									{assign var=MENU value=$MENUITEM->get('name')}
									{assign var=MENU_LABEL value=$MENU}
									{if $MENU eq 'LBL_EDIT_FIELDS'}
										{assign var=MENU_LABEL value='LBL_MODULE_CUSTOMIZATION'}
									{elseif $MENU eq 'LBL_TAX_SETTINGS'}
										{assign var=MENU_LABEL value='LBL_TAX_MANAGEMENT'}
									{elseif $MENU eq 'INVENTORYTERMSANDCONDITIONS'}
										{assign var=MENU_LABEL value='LBL_TERMS_AND_CONDITIONS'}
									{/if}
									{assign var=MENU_URL value=$MENUITEM->getUrl()}
									{if $MENU eq 'My Preferences'}
										{assign var=MENU_URL value=$USER_MODEL->getPreferenceDetailViewUrl()}
									{elseif $MENU eq 'Calendar Settings'}
										{assign var=MENU_URL value=$USER_MODEL->getCalendarSettingsDetailViewUrl()}
									{/if}
									{assign var=_menuActive value=(isset($ACTIVE_BLOCK.menu) && $ACTIVE_BLOCK.menu eq $MENU)}
									<li>
										<a data-name="{$MENU}" href="{$MENU_URL}" class="menuItemLabel mk-settings-subnav-link{if $_menuActive} mk-settings-subnav-link--active settingsgroup-menu-color{/if}">
											{vtranslate($MENU_LABEL, $QUALIFIED_MODULE|default:'Settings:Vtiger')}
										</a>
									</li>
								{/foreach}
							</ul>
						</div>
					</div>
				{/if}
			{/foreach}
		</div>
		<a class="mk-settings-help-link" href="https://wiki.vtiger.com" target="_blank" rel="noopener noreferrer">
			<span class="mk-settings-help-ic fa fa-question-circle" aria-hidden="true"></span>
			<span>{vtranslate('LBL_HELP', 'Vtiger')|default:'Help Center'}</span>
		</a>
	</div>
{/if}
{/strip}
