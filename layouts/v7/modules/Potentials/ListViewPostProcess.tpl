{*+**********************************************************************************
 * Potentials List (Sales app): close split shell opened in ListViewPreProcess.tpl
 ************************************************************************************}
{if $MODULE eq 'Potentials' || (isset($SELECTED_MENU_CATEGORY) && ($SELECTED_MENU_CATEGORY|trim eq 'SALES')) || (isset($smarty.get.app) && ($smarty.get.app|trim eq 'SALES'))}
		</div>
</div>
</main>
</div>
</div>
{include file="partials/MkThemeStylesLast.tpl"|vtemplate_path:'Vtiger'}
<link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Potentials/resources/PotentialsMkListPad.css')}&mk_v=20260709_opps_pad1" />
<script type="text/javascript">document.documentElement.classList.add('mk-opp-list-ready');</script>
{else}
{include file="ListViewPostProcess.tpl"|@vtemplate_path:'Vtiger'}
{/if}
