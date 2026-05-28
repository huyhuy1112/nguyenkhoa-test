{*+**********************************************************************************
 * Plans List (Marketing app): close split shell.
 ************************************************************************************}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'MARKETING') || (isset($smarty.get.app) && $smarty.get.app eq 'MARKETING')}
	</div>
</div>
</main>
</div>
</div>
{else}
{include file="ListViewPostProcess.tpl"|@vtemplate_path:'Vtiger'}
{/if}
