{* POS-style list toolbar — match SalesOrder / Đơn hàng *}
{strip}
<div class="mk-so-pos-toolbar mk-qt-pos-toolbar" role="region" aria-label="{vtranslate($MODULE, $MODULE)}">
	<h1 class="mk-so-pos-toolbar__title">{vtranslate($MODULE, $MODULE)}</h1>
	<div class="mk-so-pos-toolbar__search-wrap">
		<div class="mk-so-pos-search" role="search">
			<span class="mk-so-pos-search__ic" aria-hidden="true"><i class="fa fa-search"></i></span>
			<input id="mk-qt-pos-search" class="mk-so-pos-search__input" type="search" placeholder="Theo mã báo giá" autocomplete="off" />
			<div class="mk-so-pos-search__actions">
				<button type="button" class="mk-so-pos-search__clear" id="mk-qt-pos-search-clear" aria-label="Xóa" hidden>
					<i class="fa fa-times"></i>
				</button>
			</div>
		</div>
	</div>
	<div class="mk-so-pos-toolbar__actions">
		{assign var=ADD_ACTION value=false}
		{if $MODULE_BASIC_ACTIONS|@count gt 0}
			{foreach item=BASIC_ACTION from=$MODULE_BASIC_ACTIONS}
				{if $BASIC_ACTION->getLabel() == 'LBL_ADD_RECORD'}
					{assign var=ADD_ACTION value=$BASIC_ACTION}
				{/if}
			{/foreach}
		{/if}
		{if $ADD_ACTION}
			<button type="button" id="{$MODULE}_listView_basicAction_{Vtiger_Util_Helper::replaceSpaceWithUnderScores($ADD_ACTION->getLabel())}" class="mk-so-pos-btn mk-so-pos-btn--primary"
					{if stripos($ADD_ACTION->getUrl(), 'javascript:')===0}
				onclick='{$ADD_ACTION->getUrl()|substr:strlen("javascript:")};'
					{else}
				onclick='window.location.href = "{$ADD_ACTION->getUrl()}&app={$SELECTED_MENU_CATEGORY}"'
					{/if}>
				<i class="fa fa-plus" aria-hidden="true"></i>
				<span>{vtranslate($ADD_ACTION->getLabel(), $MODULE)}</span>
			</button>
		{/if}
		<button type="button" class="mk-so-pos-icon-btn mk-qt-pos-trigger-columns" title="Cột hiển thị" aria-label="Cột hiển thị">
			<i class="fa fa-th-large"></i>
		</button>
		{if $MODULE_SETTING_ACTIONS|@count gt 0}
			<div class="mk-so-pos-settings-wrap">
				<button type="button" class="mk-so-pos-icon-btn dropdown-toggle" data-toggle="dropdown" title="Cài đặt" aria-label="Cài đặt">
					<i class="fa fa-cog"></i>
				</button>
				<ul class="dropdown-menu dropdown-menu-right mk-so-pos-settings-menu">
					{foreach item=SETTING from=$MODULE_SETTING_ACTIONS}
						<li><a href="{$SETTING->getUrl()}">{vtranslate($SETTING->getLabel(), $MODULE_NAME ,vtranslate($MODULE_NAME, $MODULE_NAME))}</a></li>
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
							return fieldInfo[name][property]
						}
					},
					isMandatory: function (name) {
						if (fieldInfo[name]) {
							return fieldInfo[name].mandatory;
						}
						return false;
					},
					getType: function (name) {
						if (fieldInfo[name]) {
							return fieldInfo[name].type
						}
						return false;
					}
				},
			};
		})();
	</script>
{/if}
{/strip}
