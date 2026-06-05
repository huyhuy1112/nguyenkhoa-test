{* SalesOrder List: close SALES or TOOLS split shell *}
{assign var=MK_SO_DASH_LIST value=false}
{if (isset($SELECTED_MENU_CATEGORY) && ($SELECTED_MENU_CATEGORY eq 'SALES' || $SELECTED_MENU_CATEGORY eq 'TOOLS')) || (isset($smarty.get.app) && ($smarty.get.app eq 'SALES' || $smarty.get.app eq 'TOOLS')) || (isset($smarty.request.app) && ($smarty.request.app eq 'SALES' || $smarty.request.app eq 'TOOLS'))}
	{assign var=MK_SO_DASH_LIST value=true}
{/if}
{if $MK_SO_DASH_LIST}
	</div>
</div>
</main>
</div>
</div>
{include file="partials/MkThemeStylesLast.tpl"|vtemplate_path:'Vtiger'}
{else}
{include file="ListViewPostProcess.tpl"|@vtemplate_path:'Vtiger'}
{/if}
