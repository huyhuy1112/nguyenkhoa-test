{*+**********************************************************************************
 * Documents History (MANAGEMENT): close split shell.
 ************************************************************************************}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'MANAGEMENT') || (isset($smarty.get.app) && $smarty.get.app eq 'MANAGEMENT')}
		</div>
</main>
</div>
</div>
{else}
{include file="IndexPostProcess.tpl"|@vtemplate_path:'Vtiger'}
{/if}
