{* Teams List (MANAGEMENT): close split shell *}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'MANAGEMENT') || (isset($smarty.get.app) && ($smarty.get.app eq 'MANAGEMENT' || $smarty.get.app eq 'Management'))}
			</div>
		</div>
		</main>
	</div>
</div>
{else}
{include file="ListViewPostProcess.tpl"|@vtemplate_path:'Vtiger'}
{/if}
