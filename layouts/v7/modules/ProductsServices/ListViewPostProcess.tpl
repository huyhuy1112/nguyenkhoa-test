{*+**********************************************************************************
 * ProductsServices List (Sales app): close split shell.
 ************************************************************************************}
{if (isset($SELECTED_MENU_CATEGORY) && ($SELECTED_MENU_CATEGORY eq 'SALES' || $SELECTED_MENU_CATEGORY eq 'INVENTORY')) || (isset($smarty.get.app) && ($smarty.get.app eq 'SALES' || $smarty.get.app eq 'INVENTORY'))}
	</div>
</div>
</main>
</div>
</div>
{include file="partials/MkThemeStylesLast.tpl"|vtemplate_path:'Vtiger'}
{else}
{include file="ListViewPostProcess.tpl"|@vtemplate_path:'Vtiger'}
{/if}
