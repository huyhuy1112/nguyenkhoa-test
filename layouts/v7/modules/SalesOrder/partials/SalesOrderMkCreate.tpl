{* Create Sales Order — dashboard shell + stock Inventory #EditView (fields + line items). *}
{strip}
{assign var=MK_LIST_URL value='index.php?module=SalesOrder&view=List&app=SALES'}
{assign var=MK_IS_EDIT value=(!empty($RECORD_ID) && empty($IS_DUPLICATE))}
<div class="mk-so-create{if $MK_IS_EDIT} mk-so-create--edit{/if}" id="mkSoCreateWorkspace" data-mk-sales-order-create="1">
	<header class="mk-so-sticky-head" id="mkSoStickyHead">
		<div class="mk-so-sticky-head__inner">
			<div class="mk-so-sticky-head__left">
				<nav class="mk-so-sticky-head__crumb" aria-label="Breadcrumb">
					<a href="index.php?module=Home&view=MainPage&app=SALES">{vtranslate('LBL_HOME', 'Vtiger')}</a>
					<span aria-hidden="true">/</span>
					<a href="{$MK_LIST_URL}">{vtranslate('SalesOrder', $MODULE)}</a>
					<span aria-hidden="true">/</span>
					{if $MK_IS_EDIT}<span aria-current="page">{vtranslate('LBL_EDITING', $MODULE)}</span>{else}<span aria-current="page">{vtranslate('LBL_CREATING_NEW', $MODULE)}</span>{/if}
				</nav>
				<div class="mk-so-sticky-head__title-row">
					{if $MK_IS_EDIT}
						<h1 class="mk-so-sticky-head__title">{vtranslate('LBL_EDITING', $MODULE)} {vtranslate('SINGLE_SalesOrder', $MODULE)}</h1>
					{else}
						<h1 class="mk-so-sticky-head__title">{vtranslate('LBL_CREATING_NEW', $MODULE)} {vtranslate('SINGLE_SalesOrder', $MODULE)}</h1>
					{/if}
					<span class="mk-so-badge" id="mkSoHeadStageBadge">Draft</span>
				</div>
			</div>
			<div class="mk-so-sticky-head__actions">
				<a class="mk-so-btn mk-so-btn--ghost" href="{$MK_LIST_URL}">{vtranslate('LBL_CANCEL', $MODULE)}</a>
				<button type="button" class="mk-so-btn mk-so-btn--primary" id="mkSoSaveTop" data-action="save">
					{vtranslate('LBL_SAVE', $MODULE)}
				</button>
			</div>
		</div>
	</header>

	<div class="mk-so-form-host" id="mkSoFormHost">
		{include file="partials/SalesOrderMkInventoryForm.tpl"|vtemplate_path:$MODULE}
	</div>
	<script type="text/javascript">
	(function () {
		var host = document.getElementById('mkSoFormHost');
		if (!host) {
			return;
		}
		var icons = {
			LBL_SO_INFORMATION: 'fa-info-circle',
			LBL_ITEM_DETAILS: 'fa-cubes',
			LBL_ADDRESS_INFORMATION: 'fa-map-marker',
			LBL_DESCRIPTION_INFORMATION: 'fa-align-left',
			LBL_TERMS_INFORMATION: 'fa-file-text-o',
			'Recurring Invoice Information': 'fa-refresh'
		};
		host.querySelectorAll('.fieldBlockContainer[data-block]').forEach(function (block) {
			block.classList.add('mk-so-block');
			var key = block.getAttribute('data-block') || '';
			var header = block.querySelector('.fieldBlockHeader');
			if (header) {
				header.classList.add('mk-so-block__header');
				if (!header.querySelector('.mk-so-block__icon') && icons[key]) {
					var icon = document.createElement('span');
					icon.className = 'mk-so-block__icon';
					icon.setAttribute('aria-hidden', 'true');
					icon.innerHTML = '<i class="fa ' + icons[key] + '"></i>';
					header.insertBefore(icon, header.firstChild);
				}
			}
			block.querySelectorAll('table.table-borderless').forEach(function (table) {
				table.classList.add('mk-so-fields-table');
			});
			var hr = block.querySelector('hr');
			if (hr) {
				hr.classList.add('mk-so-hide-legacy');
			}
		});
		var lineTab = host.querySelector('#lineItemTab');
		if (lineTab) {
			var lineBlock = lineTab.closest('.fieldBlockContainer');
			if (lineBlock) {
				lineBlock.classList.add('mk-so-block', 'mk-so-block--line-items');
			}
		}
		var lineResult = host.querySelector('#lineItemResult');
		if (lineResult) {
			var totalBlock = lineResult.closest('.fieldBlockContainer');
			if (totalBlock) {
				totalBlock.classList.add('mk-so-block', 'mk-so-block--totals');
			}
		}
	})();
	</script>
</div>
{/strip}
