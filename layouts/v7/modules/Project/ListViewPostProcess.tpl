{*+**********************************************************************************
 * Project List (MANAGEMENT app): close split shell opened in ListViewPreProcess.tpl
 ************************************************************************************}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'MANAGEMENT') || (isset($smarty.get.app) && $smarty.get.app eq 'MANAGEMENT')}
	</div>
</div>
</main>
<footer class="app-footer mk-project-shell-footer" role="contentinfo">
	<p>B-ACE developed by TDB SOLUTION 2025</p>
</footer>
</div>
</div>
{else}
{include file="ListViewPostProcess.tpl"|@vtemplate_path:'Vtiger'}
{/if}
