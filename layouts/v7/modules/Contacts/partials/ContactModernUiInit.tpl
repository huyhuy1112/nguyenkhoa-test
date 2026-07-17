{if (isset($SELECTED_MENU_CATEGORY) && ($SELECTED_MENU_CATEGORY eq 'SALES' || $SELECTED_MENU_CATEGORY eq 'MARKETING')) || (isset($smarty.get.app) && ($smarty.get.app eq 'SALES' || $smarty.get.app eq 'MARKETING'))}
{assign var=MK_CONTACT_MODERN_UI value=true}
<script type="text/javascript">window.MK_CONTACT_CCCD = {if isset($MK_CONTACT_CCCD)}{Zend_Json::encode($MK_CONTACT_CCCD)}{else}""{/if};</script>
{/if}
