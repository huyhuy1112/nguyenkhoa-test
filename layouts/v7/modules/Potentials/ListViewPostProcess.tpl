{*+**********************************************************************************
 * Potentials List (Sales app): close split shell opened in ListViewPreProcess.tpl
 ************************************************************************************}
{if $MODULE eq 'Potentials' || (isset($SELECTED_MENU_CATEGORY) && ($SELECTED_MENU_CATEGORY|trim eq 'SALES')) || (isset($smarty.get.app) && ($smarty.get.app|trim eq 'SALES'))}
	</div>
</div>
</main>
</div>
</div>
{else}
{include file="ListViewPostProcess.tpl"|@vtemplate_path:'Vtiger'}
{/if}
