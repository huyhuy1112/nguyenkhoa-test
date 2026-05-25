{* Warehouse Storage Detail (Inventory): close split shell. *}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'INVENTORY') || (isset($smarty.get.app) && $smarty.get.app eq 'INVENTORY')}
					</div>
				</div>
			</div>
		</div>
		</main>
	</div>
</div>
{else}
{include file="DetailViewPostProcess.tpl"|@vtemplate_path:'Vtiger'}
{/if}
