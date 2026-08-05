{* Invoice List (TOOLS / SUPPORT): close split shell *}
{if (isset($SELECTED_MENU_CATEGORY) && ($SELECTED_MENU_CATEGORY eq 'SUPPORT' || $SELECTED_MENU_CATEGORY eq 'TOOLS' || $SELECTED_MENU_CATEGORY eq 'SALES')) || (isset($smarty.get.app) && ($smarty.get.app eq 'SUPPORT' || $smarty.get.app eq 'TOOLS' || $smarty.get.app eq 'SALES')) || (isset($smarty.request.app) && ($smarty.request.app eq 'SUPPORT' || $smarty.request.app eq 'TOOLS' || $smarty.request.app eq 'SALES'))}
	</div>
</div>
</main>
</div>
</div>
{include file="partials/MkThemeStylesLast.tpl"|vtemplate_path:'Vtiger'}
{else}
{include file="ListViewPostProcess.tpl"|@vtemplate_path:'Vtiger'}
{/if}
