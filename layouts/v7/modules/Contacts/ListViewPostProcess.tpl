{*+**********************************************************************************
 * Contacts List (Sales): close split shell opened in ListViewPreProcess.tpl
 ************************************************************************************}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SALES') || (isset($smarty.get.app) && $smarty.get.app eq 'SALES')}
			</div>
		</div>
		</main>
	</div>
</div>

{else}
{include file="ListViewPostProcess.tpl"|@vtemplate_path:'Vtiger'}
{/if}
