{* Invoice list header — align with SalesOrder POS toolbar (without Add button) *}
{strip}
{assign var=IMPORT_ACTION value=false}
{if $MODULE_BASIC_ACTIONS|@count gt 0}
	{foreach item=BASIC_ACTION from=$MODULE_BASIC_ACTIONS}
		{if $BASIC_ACTION->getLabel() == 'LBL_IMPORT'}
			{assign var=IMPORT_ACTION value=$BASIC_ACTION}
		{/if}
	{/foreach}
{/if}
<div class="mk-so-pos-toolbar" role="region" aria-label="{vtranslate('Invoice', 'Invoice')}">
	<h1 class="mk-so-pos-toolbar__title">{vtranslate('Invoice', 'Invoice')}</h1>
	<div class="mk-so-pos-toolbar__search-wrap">
		<div class="mk-so-pos-search" role="search">
			<span class="mk-so-pos-search__ic" aria-hidden="true"><i class="fa fa-search"></i></span>
			<input id="mk-so-pos-search" class="mk-so-pos-search__input" type="search" placeholder="Tìm theo mã hóa đơn, khách hàng…" autocomplete="off" />
			<div class="mk-so-pos-search__actions">
				<button type="button" class="mk-so-pos-search__clear" id="mk-so-pos-search-clear" aria-label="Xóa" hidden>
					<i class="fa fa-times"></i>
				</button>
			</div>
		</div>
	</div>
	<div class="mk-so-pos-toolbar__actions">
		{if $IMPORT_ACTION}
			<button type="button" id="{$MODULE}_basicAction_{Vtiger_Util_Helper::replaceSpaceWithUnderScores($IMPORT_ACTION->getLabel())}" class="mk-so-pos-btn mk-so-pos-btn--outline"
					{if stripos($IMPORT_ACTION->getUrl(), 'javascript:')===0}
				onclick='{$IMPORT_ACTION->getUrl()|substr:strlen("javascript:")};'
					{else}
				onclick="Vtiger_Import_Js.triggerImportAction('{$IMPORT_ACTION->getUrl()}')"
					{/if}>
				<i class="fa fa-download" aria-hidden="true"></i>
				<span>{vtranslate($IMPORT_ACTION->getLabel(), $MODULE)}</span>
			</button>
		{/if}
		<button type="button" class="mk-so-pos-btn mk-so-pos-btn--outline mk-so-pos-mass-delete-btn mk-so-pos-mass-action" id="mk-so-mass-delete-btn" disabled="disabled" aria-hidden="true"
				onclick="if (window.mkInvMassDelete) { window.mkInvMassDelete(); } return false;"
				title="Xóa các hóa đơn đã chọn">
			<i class="fa fa-trash" aria-hidden="true"></i>
			<span>Xóa</span>
		</button>
		{if $MODULE_SETTING_ACTIONS|@count gt 0}
			<div class="mk-so-pos-settings-wrap">
				<button type="button" class="mk-so-pos-icon-btn dropdown-toggle" data-toggle="dropdown" title="{vtranslate('LBL_SETTINGS', $MODULE)}" aria-label="{vtranslate('LBL_SETTINGS', $MODULE)}">
					<i class="fa fa-cog"></i>
				</button>
				<ul class="dropdown-menu dropdown-menu-right mk-so-pos-settings-menu">
					{foreach item=SETTING from=$MODULE_SETTING_ACTIONS}
						<li id="{$MODULE_NAME}_listview_advancedAction_{$SETTING->getLabel()}">
							<a href="{$SETTING->getUrl()}">{vtranslate($SETTING->getLabel(), $MODULE_NAME ,vtranslate($MODULE_NAME, $MODULE_NAME))}</a>
						</li>
					{/foreach}
				</ul>
			</div>
		{/if}
	</div>
</div>
{if $FIELDS_INFO neq null}
	<script type="text/javascript">
		var uimeta = (function () {
			var fieldInfo = {$FIELDS_INFO};
			return {
				field: {
					get: function (name, property) {
						if (name && property === undefined) {
							return fieldInfo[name];
						}
						if (name && property) {
							return fieldInfo[name][property];
						}
					}
				}
			};
		})();
	</script>
{/if}
{/strip}
