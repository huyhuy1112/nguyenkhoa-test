{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*************************************************************************************}

{assign var="topMenus" value=$MENU_STRUCTURE->getTop()}
{assign var="moreMenus" value=$MENU_STRUCTURE->getMore()}

<div id="modules-menu" class="modules-menu">
	<ul>
		{foreach item=SIDE_BAR_LINK from=$QUICK_LINKS['SIDEBARLINK']}
			{assign var=CURRENT_LINK_NAME value="List"}
			{assign var=VIEW_ICON_CLASS value="vicon-calendarlist"}
			{if $SIDE_BAR_LINK->get('linklabel') eq 'LBL_CALENDAR_VIEW'}
				{assign var=CURRENT_LINK_NAME value="Calendar"}
				{assign var=VIEW_ICON_CLASS value="vicon-mycalendar"}
			{else if $SIDE_BAR_LINK->get('linklabel') eq 'LBL_SHARED_CALENDAR'}
				{assign var=CURRENT_LINK_NAME value="SharedCalendar"}
				{assign var=VIEW_ICON_CLASS value="vicon-sharedcalendar"}
			{/if}
			{* MARKETING-only menu cleanup *}
			{if $smarty.get.app eq 'MARKETING' && ($CURRENT_LINK_NAME eq 'SharedCalendar' || $CURRENT_LINK_NAME eq 'List')}
				{continue}
			{/if}
			<li class="module-qtip {if $CURRENT_LINK_NAME eq $CURRENT_VIEW}active{/if}" title="{vtranslate($SIDE_BAR_LINK->get('linklabel'),'Calendar')}">
				<a href="{$SIDE_BAR_LINK->get('linkurl')}{if $smarty.get.app neq ''}&app={$smarty.get.app}{/if}">
					<i class="{$VIEW_ICON_CLASS}"></i>
					<span>
						{if $smarty.get.app eq 'MARKETING' && $SIDE_BAR_LINK->get('linklabel') eq 'LBL_CALENDAR_VIEW'}
							Calendar
						{else}
							{vtranslate($SIDE_BAR_LINK->get('linklabel'),'Calendar')}
						{/if}
					</span>
				</a>
			</li>
		{/foreach}
	</ul>
</div>