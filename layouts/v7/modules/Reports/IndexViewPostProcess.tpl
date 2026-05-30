{*+**********************************************************************************
 * Reports Management (MANAGEMENT app): close split shell opened in IndexViewPreProcess.tpl
 ************************************************************************************}
{if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'MANAGEMENT') || (isset($smarty.get.app) && $smarty.get.app eq 'MANAGEMENT')}
			</div>
		</div>
		</main>
	</div>
</div>
{else}
        </div>
    </div>
</div>
{/if}
