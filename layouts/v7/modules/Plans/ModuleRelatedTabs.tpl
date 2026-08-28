{* Plans: Marketing detail tabs (Summary / Details) *}
{strip}
	<div class="related-tabs row {if (isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY eq 'MARKETING') || (isset($smarty.get.app) && $smarty.get.app eq 'MARKETING')}mk-plan-detail-related-tabs{/if}">
		<nav class="navbar margin0" role="navigation">
			<div class="navbar-header">
				<button type="button" class="navbar-toggle btn-group-justified collapsed border0" data-toggle="collapse" data-target="#nav-tabs" aria-expanded="false">
					<i class="fa fa-ellipsis-h"></i>
				</button>
			</div>
			<div class="collapse navbar-collapse" id="nav-tabs">
				<ul class="nav nav-tabs">
					{foreach item=RELATED_LINK from=$DETAILVIEW_LINKS['DETAILVIEWTAB']}
						{assign var=RELATEDLINK_URL value=$RELATED_LINK->getUrl()}
						{assign var=RELATEDLINK_LABEL value=$RELATED_LINK->getLabel()}
						{assign var=RELATED_TAB_LABEL value={vtranslate('SINGLE_'|cat:$MODULE_NAME, $MODULE_NAME)}|cat:" "|cat:$RELATEDLINK_LABEL}
						<li class="tab-item {if $RELATED_TAB_LABEL==$SELECTED_TAB_LABEL}active{/if}" data-url="{$RELATEDLINK_URL}&tab_label={$RELATED_TAB_LABEL}&app={$SELECTED_MENU_CATEGORY}" data-label-key="{$RELATEDLINK_LABEL}" data-link-key="{$RELATED_LINK->get('linkKey')}">
							<a href="{$RELATEDLINK_URL}&tab_label={$RELATEDLINK_LABEL}&app={$SELECTED_MENU_CATEGORY}" class="textOverflowEllipsis">
								<span class="tab-label"><strong>{vtranslate($RELATEDLINK_LABEL, $MODULE_NAME)}</strong></span>
							</a>
						</li>
					{/foreach}
				</ul>
			</div>
		</nav>
	</div>
{/strip}
