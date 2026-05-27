{* Modern Accounts (Organizations) detail UI for Sales, Marketing, and Support apps. *}
{if (isset($SELECTED_MENU_CATEGORY) && ($SELECTED_MENU_CATEGORY eq 'SALES' || $SELECTED_MENU_CATEGORY eq 'MARKETING' || $SELECTED_MENU_CATEGORY eq 'SUPPORT')) || (isset($smarty.get.app) && ($smarty.get.app eq 'SALES' || $smarty.get.app eq 'MARKETING' || $smarty.get.app eq 'SUPPORT'))}
{assign var=MK_ACCOUNTS_MODERN_UI value=true}
{/if}
