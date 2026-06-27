{*+**********************************************************************************
 * Project List (MANAGEMENT app): close split shell opened in ListViewPreProcess.tpl
 ************************************************************************************}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'MANAGEMENT') || (isset($smarty.get.app) && $smarty.get.app eq 'MANAGEMENT')}
	</div>
</div>
</main>
{assign var=MK_APP_FOOTER_EXTRA_CLASS value='mk-project-shell-footer'}
{include file="partials/MkAppFooter.tpl"|vtemplate_path:'Vtiger'}
</div>
</div>
{else}
{include file="ListViewPostProcess.tpl"|@vtemplate_path:'Vtiger'}
{/if}
