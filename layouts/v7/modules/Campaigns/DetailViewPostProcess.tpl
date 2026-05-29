{*+**********************************************************************************
 * Campaigns Detail (Marketing): close split shell opened in DetailViewPreProcess.tpl.
 ************************************************************************************}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'MARKETING') || (isset($smarty.get.app) && $smarty.get.app eq 'MARKETING')}
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
