{*+**********************************************************************************
 * Potentials Detail (Sales): close the split shell opened in DetailViewPreProcess.tpl.
 ************************************************************************************}
{if $MODULE eq 'Potentials' || (isset($SELECTED_MENU_CATEGORY) && ($SELECTED_MENU_CATEGORY|trim eq 'SALES')) || (isset($smarty.get.app) && ($smarty.get.app|trim eq 'SALES'))}
					</div>
				</div>
			</div>
		</div>
		</main>
	</div>
</div>
{include file="partials/MkThemeStylesLast.tpl"|vtemplate_path:'Vtiger'}
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Potentials/resources/PotentialsMkTagsModalPad.css')}&mk_v=20260709_opp_tags_ui3" />

{else}
{include file="DetailViewPostProcess.tpl"|@vtemplate_path:'Vtiger'}
{/if}
