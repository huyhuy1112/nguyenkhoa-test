{* Create Sales Order — native SO shell + stock Inventory #EditView (fields + line items). *}
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
		var keepNames = {
			subject: 1,
			potential_id: 1,
			potential_id_display: 1,
			description: 1
		};
		function baseName(name) {
			return String(name || '').replace(/\[\]$/, '').replace(/_display$/, '');
		}
		function hidePair(valueTd) {
			if (!valueTd) {
				return;
			}
			valueTd.classList.add('mk-so-hide-legacy');
			var label = valueTd.previousElementSibling;
			if (label && label.classList && label.classList.contains('fieldLabel')) {
				label.classList.add('mk-so-hide-legacy');
			}
		}
		host.querySelectorAll('td.fieldValue').forEach(function (valueTd) {
			if (valueTd.closest('#lineItemTab, #lineItemResult, .lineItemTable, .lineitemTableContainer')) {
				return;
			}
			var keep = false;
			valueTd.querySelectorAll('input[name], select[name], textarea[name]').forEach(function (el) {
				var n = baseName(el.getAttribute('name'));
				if (keepNames[n] || keepNames[el.getAttribute('name')]) {
					keep = true;
				}
			});
			if (!keep) {
				hidePair(valueTd);
			}
		});
		/* Move Ghi chú into SO info before hiding description block. */
		var desc = host.querySelector('textarea[name="description"]');
		var infoBlock = host.querySelector('.fieldBlockContainer[data-block="LBL_SO_INFORMATION"]');
		if (desc && infoBlock) {
			var descRow = desc.closest('tr');
			var infoTbody = infoBlock.querySelector('table.table-borderless > tbody');
			if (descRow && infoTbody && !infoBlock.querySelector('textarea[name="description"]')) {
				infoTbody.appendChild(descRow);
			}
			var descValue = desc.closest('td.fieldValue');
			if (descValue) {
				descValue.classList.remove('mk-so-hide-legacy');
				descValue.classList.add('fieldValueWidth80');
				var descLabel = descValue.previousElementSibling;
				if (descLabel && descLabel.classList.contains('fieldLabel')) {
					descLabel.classList.remove('mk-so-hide-legacy');
					var lab = descLabel.querySelector('label');
					if (lab) {
						lab.textContent = 'Ghi chú';
					}
				}
			}
			if (descRow) {
				descRow.classList.remove('mk-so-hide-legacy');
			}
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
			if (key === 'LBL_ADDRESS_INFORMATION' || key === 'LBL_TERMS_INFORMATION' || key === 'Recurring Invoice Information' || key === 'LBL_DESCRIPTION_INFORMATION') {
				block.classList.add('mk-so-hide-legacy');
			}
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
				lineBlock.classList.remove('mk-so-hide-legacy');
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
