{*+**********************************************************************************
 * GoodsIssue Outbound List (Inventory app): close split shell.
 ************************************************************************************}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'INVENTORY') || (isset($smarty.get.app) && $smarty.get.app eq 'INVENTORY')}
			</div>
		</div>
		</main>
	</div>
</div>
{else}
{include file="IndexPostProcess.tpl"|@vtemplate_path:'Vtiger'}
{/if}
