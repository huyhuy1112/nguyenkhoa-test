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
{include file="partials/MkThemeStylesLast.tpl"|vtemplate_path:'Vtiger'}
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Campaigns/resources/CampaignsReferenceFix.js')}?mk_v=20260618_ref2"></script>

{else}
{include file="DetailViewPostProcess.tpl"|@vtemplate_path:'Vtiger'}
{/if}
