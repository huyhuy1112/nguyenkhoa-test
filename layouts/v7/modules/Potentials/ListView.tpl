{* Potentials ListView wrapper to ensure custom JS is loaded *}
{include file="layouts/v7/modules/Vtiger/ListView.tpl"}
{if isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SALES'}
	<link rel="stylesheet" type="text/css" href="layouts/v7/modules/Potentials/resources/OpportunityList.css" />
{/if}
<script type="text/javascript" src="layouts/v7/modules/Potentials/resources/InternalOrderProtection.js"></script>
{* Removed Project/Internal toggle UI + forced client-side filtering (Option: show all by default) *}

