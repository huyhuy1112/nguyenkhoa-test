{*+**********************************************************************************
 * Project Detail (MANAGEMENT): close split shell opened in DetailViewPreProcess.tpl
 ************************************************************************************}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'MANAGEMENT') || (isset($smarty.get.app) && $smarty.get.app eq 'MANAGEMENT')}
					</div>
				</div>
			</div>
		</div>
		</main>
		<footer class="app-footer mk-project-shell-footer" role="contentinfo">
			<p>B-ACE developed by TDB SOLUTION 2025</p>
		</footer>
	</div>
</div>
{else}
{include file="DetailViewPostProcess.tpl"|@vtemplate_path:'Vtiger'}
{/if}
