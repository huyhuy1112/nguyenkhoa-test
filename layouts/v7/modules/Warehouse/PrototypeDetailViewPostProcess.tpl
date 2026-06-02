{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'INVENTORY') || (isset($smarty.get.app) && $smarty.get.app eq 'INVENTORY')}
{strip}
			</div>
		</div>
		</main>
	</div>
</div>
{include file="partials/MkThemeStylesLast.tpl"|vtemplate_path:'Vtiger'}
{include file="modules/Vtiger/Footer.tpl"}
{/strip}
{else}
{include file="IndexViewPostProcess.tpl"|@vtemplate_path:'Vtiger'}
{/if}

