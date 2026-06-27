{*+**********************************************************************************
 * Project Detail (MANAGEMENT): close split shell opened in DetailViewPreProcess.tpl
 ************************************************************************************}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'MANAGEMENT') || (isset($smarty.get.app) && $smarty.get.app eq 'MANAGEMENT')}
					</div>
				</div>
			</div>
		</div>
		</main>
		{assign var=MK_APP_FOOTER_EXTRA_CLASS value='mk-project-shell-footer'}
		{include file="partials/MkAppFooter.tpl"|vtemplate_path:'Vtiger'}
	</div>
</div>
{else}
{include file="DetailViewPostProcess.tpl"|@vtemplate_path:'Vtiger'}
{/if}
