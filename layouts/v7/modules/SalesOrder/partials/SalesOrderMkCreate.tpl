{* Create Sales Order — Quote-like shell; require Báo giá to create. *}
{strip}
{assign var=MK_LIST_URL value='index.php?module=SalesOrder&view=List&app=SALES'}
{assign var=MK_IS_EDIT value=(!empty($RECORD_ID) && empty($IS_DUPLICATE))}
<div class="mk-so-create mk-qt-create{if $MK_IS_EDIT} mk-so-create--edit mk-qt-create--edit{/if}" id="mkSoCreateWorkspace" data-mk-sales-order-create="1">
	<header class="mk-so-sticky-head mk-qt-sticky-head" id="mkSoStickyHead">
		<div class="mk-so-sticky-head__inner mk-qt-sticky-head__inner">
			<div class="mk-so-sticky-head__left mk-qt-sticky-head__left">
				<nav class="mk-so-sticky-head__crumb mk-qt-sticky-head__crumb" aria-label="Breadcrumb">
					<a href="index.php?module=Home&view=MainPage&app=SALES">{vtranslate('LBL_HOME', 'Vtiger')}</a>
					<span aria-hidden="true">/</span>
					<a href="{$MK_LIST_URL}">{vtranslate('SalesOrder', $MODULE)}</a>
					<span aria-hidden="true">/</span>
					{if $MK_IS_EDIT}<span aria-current="page">{vtranslate('LBL_EDITING', $MODULE)}</span>{else}<span aria-current="page">{vtranslate('LBL_CREATING_NEW', $MODULE)}</span>{/if}
				</nav>
				<div class="mk-so-sticky-head__title-row mk-qt-sticky-head__title-row">
					{if $MK_IS_EDIT}
						<h1 class="mk-so-sticky-head__title mk-qt-sticky-head__title">{vtranslate('LBL_EDITING', $MODULE)} {vtranslate('SINGLE_SalesOrder', $MODULE)}</h1>
					{else}
						<h1 class="mk-so-sticky-head__title mk-qt-sticky-head__title">{vtranslate('LBL_CREATING_NEW', $MODULE)} {vtranslate('SINGLE_SalesOrder', $MODULE)}</h1>
					{/if}
					<span class="mk-so-badge mk-qt-badge mk-qt-badge--stage" id="mkSoHeadStageBadge">Draft</span>
				</div>
				<div class="mk-qt-autosave" id="mkSoAutosave" aria-live="polite">
					<span class="mk-qt-autosave__dot" aria-hidden="true"></span>
					<span class="mk-qt-autosave__text">Ready to save</span>
				</div>
			</div>
			<div class="mk-so-sticky-head__actions mk-qt-sticky-head__actions">
				<a class="mk-so-btn mk-so-btn--ghost mk-qt-btn mk-qt-btn--ghost" href="{$MK_LIST_URL}">{vtranslate('LBL_CANCEL', $MODULE)}</a>
				<button type="button" class="mk-so-btn mk-so-btn--primary mk-qt-btn mk-qt-btn--primary" id="mkSoSaveTop" data-action="save">
					{vtranslate('LBL_SAVE', $MODULE)}
				</button>
			</div>
		</div>
	</header>

	<div class="mk-qt-create__grid mk-so-create__grid">
		<div class="mk-qt-create__main mk-so-create__main">
			<div class="mk-so-form-host mk-qt-form-host" id="mkSoFormHost">
				{include file="partials/SalesOrderMkInventoryForm.tpl"|vtemplate_path:$MODULE}
			</div>
			<script type="text/javascript">
			(function () {
				var host = document.getElementById('mkSoFormHost');
				if (!host) {
					return;
				}
				var keepNames = {
					quote_id: 1,
					quote_id_display: 1,
					description: 1,
					mk_list_note: 1
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
								lab.textContent = 'Ghi chú hợp đồng';
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
					block.classList.add('mk-so-block', 'mk-qt-block');
					var key = block.getAttribute('data-block') || '';
					if (key === 'LBL_ADDRESS_INFORMATION' || key === 'LBL_TERMS_INFORMATION' || key === 'Recurring Invoice Information' || key === 'LBL_DESCRIPTION_INFORMATION') {
						block.classList.add('mk-so-hide-legacy', 'mk-qt-hide-legacy');
					}
					var header = block.querySelector('.fieldBlockHeader');
					if (header) {
						header.classList.add('mk-so-block__header', 'mk-qt-block__header');
						if (!header.querySelector('.mk-so-block__icon, .mk-qt-block__icon') && icons[key]) {
							var icon = document.createElement('span');
							icon.className = 'mk-so-block__icon mk-qt-block__icon';
							icon.setAttribute('aria-hidden', 'true');
							icon.innerHTML = '<i class="fa ' + icons[key] + '"></i>';
							header.insertBefore(icon, header.firstChild);
						}
					}
					block.querySelectorAll('table.table-borderless').forEach(function (table) {
						table.classList.add('mk-so-fields-table', 'mk-qt-fields-table');
					});
					var hr = block.querySelector('hr');
					if (hr) {
						hr.classList.add('mk-so-hide-legacy', 'mk-qt-hide-legacy');
					}
				});
				var lineTab = host.querySelector('#lineItemTab');
				if (lineTab) {
					var lineBlock = lineTab.closest('.fieldBlockContainer');
					if (lineBlock) {
						lineBlock.classList.add('mk-so-block', 'mk-so-block--line-items', 'mk-qt-block', 'mk-qt-block--line-items');
						lineBlock.classList.remove('mk-so-hide-legacy', 'mk-qt-hide-legacy');
					}
				}
				var lineResult = host.querySelector('#lineItemResult');
				if (lineResult) {
					var totalBlock = lineResult.closest('.fieldBlockContainer');
					if (totalBlock) {
						totalBlock.classList.add('mk-so-block', 'mk-so-block--totals', 'mk-qt-block', 'mk-qt-block--totals');
					}
				}
			})();
			</script>
		</div>

		<aside class="mk-qt-rail mk-so-rail" id="mkSoOrderRail" aria-label="Order summary">
			{* SO info + address moved here by SalesOrderMkEdit.js *}
		</aside>
	</div>
</div>
{/strip}
