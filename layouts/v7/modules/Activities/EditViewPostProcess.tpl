{* Activities Edit/Create (SUPPORT app): close split shell. *}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'SUPPORT') || (isset($smarty.get.app) && $smarty.get.app eq 'SUPPORT') || !isset($smarty.get.app) || $smarty.get.app eq ''}
				</div>
			</div>
		</div>
		</main>
	</div>
</div>
{else}
{include file="IndexPostProcess.tpl"|@vtemplate_path:'Vtiger'}
{/if}
