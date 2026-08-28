{*+**********************************************************************************
 * Contacts List (Sales): close split shell opened in ListViewPreProcess.tpl
 ************************************************************************************}
{if (isset($SELECTED_MENU_CATEGORY) && ($SELECTED_MENU_CATEGORY eq 'SALES' || $SELECTED_MENU_CATEGORY eq 'MARKETING')) || (isset($smarty.get.app) && ($smarty.get.app eq 'SALES' || $smarty.get.app eq 'MARKETING'))}
			</div>
		</div>
		</main>
	</div>
</div>
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SALES') || (isset($smarty.get.app) && $smarty.get.app eq 'SALES')}
{include file="partials/MkThemeStylesLast.tpl"|vtemplate_path:'Vtiger'}
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Contacts/resources/ContactsMkListPad.css')}&mk_v=20260709_contacts_ui_fix1" />
<script type="text/javascript">document.documentElement.classList.add('mk-contacts-list-ready');</script>
{elseif (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'MARKETING') || (isset($smarty.get.app) && $smarty.get.app eq 'MARKETING')}
{include file="partials/MkThemeStylesLast.tpl"|vtemplate_path:'Vtiger'}
{/if}

{else}
{include file="ListViewPostProcess.tpl"|@vtemplate_path:'Vtiger'}
{/if}
