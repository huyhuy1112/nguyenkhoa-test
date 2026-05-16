{*+**********************************************************************************
 * Accounts Detail (Sales): close split shell opened in Accounts DetailViewPreProcess.tpl
 ************************************************************************************}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SALES') || (isset($smarty.get.app) && $smarty.get.app eq 'SALES')}
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
