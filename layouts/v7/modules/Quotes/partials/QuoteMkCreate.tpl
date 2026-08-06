{* Create Quote — dashboard shell + stock Inventory #EditView (all fields + line items). *}
{strip}
{assign var=MK_LIST_URL value='index.php?module=Quotes&view=List&app=SALES'}
{assign var=MK_IS_EDIT value=(!empty($RECORD_ID) && empty($IS_DUPLICATE))}
<div class="mk-qt-create{if $MK_IS_EDIT} mk-qt-create--edit{/if}" id="mkQtCreateWorkspace" data-mk-quote-create="1">
	<header class="mk-qt-sticky-head" id="mkQtStickyHead">
		<div class="mk-qt-sticky-head__inner">
			<div class="mk-qt-sticky-head__left">
				<nav class="mk-qt-sticky-head__crumb" aria-label="Breadcrumb">
					<a href="index.php?module=Home&view=MainPage&app=SALES">{vtranslate('LBL_HOME', 'Vtiger')}</a>
					<span aria-hidden="true">/</span>
					<a href="{$MK_LIST_URL}">{vtranslate('Quotes', $MODULE)}</a>
					<span aria-hidden="true">/</span>
					{if $MK_IS_EDIT}<span aria-current="page">{vtranslate('LBL_EDITING', $MODULE)}</span>{else}<span aria-current="page">{vtranslate('LBL_CREATING_NEW', $MODULE)}</span>{/if}
				</nav>
				<div class="mk-qt-sticky-head__title-row">
					{if $MK_IS_EDIT}
						<h1 class="mk-qt-sticky-head__title">{vtranslate('LBL_EDITING', $MODULE)} {vtranslate('SINGLE_Quotes', $MODULE)}</h1>
					{else}
						<h1 class="mk-qt-sticky-head__title">{vtranslate('LBL_CREATING_NEW', $MODULE)} {vtranslate('SINGLE_Quotes', $MODULE)}</h1>
					{/if}
					<span class="mk-qt-badge mk-qt-badge--stage" id="mkQtHeadStageBadge">Nháp</span>
				</div>
				<div class="mk-qt-autosave" id="mkQtAutosave" aria-live="polite">
					<span class="mk-qt-autosave__dot" aria-hidden="true"></span>
					<span class="mk-qt-autosave__text">Ready to save</span>
				</div>
			</div>
			<div class="mk-qt-sticky-head__actions">
				<a class="mk-qt-btn mk-qt-btn--ghost" href="{$MK_LIST_URL}">{vtranslate('LBL_CANCEL', $MODULE)}</a>
				<button type="button" class="mk-qt-btn mk-qt-btn--secondary mk-qt-preview-print-btn" id="mkQtPreviewPrintBtn" title="Xem bản in báo giá">
					<i class="fa fa-print" aria-hidden="true"></i> In
				</button>
				<button type="button" class="mk-qt-btn mk-qt-btn--secondary" id="mkQtSaveSendTop" title="Save the quote first to send by email">
					Save &amp; Send
				</button>
				<button type="button" class="mk-qt-btn mk-qt-btn--primary" id="mkQtSaveTop" data-action="save">
					{vtranslate('LBL_SAVE', $MODULE)}
				</button>
			</div>
		</div>
	</header>

	<div class="mk-qt-create__grid">
		<div class="mk-qt-create__main">
			<div class="mk-qt-form-host" id="mkQtFormHost">
				{include file="partials/QuoteMkInventoryForm.tpl"|vtemplate_path:$MODULE}
			</div>
			<script type="text/javascript">{literal}
			(function () {
				var host = document.getElementById('mkQtFormHost');
				if (!host) {
					return;
				}
				var icons = {
					LBL_QUOTE_INFORMATION: 'fa-info-circle',
					LBL_ADDRESS_INFORMATION: 'fa-map-marker',
					LBL_ITEM_DETAILS: 'fa-cubes',
					LBL_DESCRIPTION_INFORMATION: 'fa-align-left',
					LBL_TERMS_INFORMATION: 'fa-file-text-o',
					LBL_MK_QUOTE_VAT: 'fa-calculator'
				};
				host.querySelectorAll('.fieldBlockContainer[data-block]').forEach(function (block) {
					block.classList.add('mk-qt-block');
					var key = block.getAttribute('data-block') || '';
					var header = block.querySelector('.fieldBlockHeader');
					if (header) {
						header.classList.add('mk-qt-block__header');
						if (!header.querySelector('.mk-qt-block__icon') && icons[key]) {
							var icon = document.createElement('span');
							icon.className = 'mk-qt-block__icon';
							icon.setAttribute('aria-hidden', 'true');
							icon.innerHTML = '<i class="fa ' + icons[key] + '"></i>';
							header.insertBefore(icon, header.firstChild);
						}
					}
					block.querySelectorAll('table.table-borderless').forEach(function (table) {
						table.classList.add('mk-qt-fields-table');
					});
					var hr = block.querySelector('hr');
					if (hr) {
						hr.classList.add('mk-qt-hide-legacy');
					}
				});
				var lineTab = host.querySelector('#lineItemTab');
				if (lineTab) {
					var lineBlock = lineTab.closest('.fieldBlockContainer');
					if (lineBlock) {
						lineBlock.classList.add('mk-qt-block', 'mk-qt-block--line-items');
					}
				}
				var lineResult = host.querySelector('#lineItemResult');
				if (lineResult) {
					var totalBlock = lineResult.closest('.fieldBlockContainer');
					if (totalBlock) {
						totalBlock.classList.add('mk-qt-block', 'mk-qt-block--totals');
					}
				}
				var hideFieldNames = [
					'carrier', 'shipping', 'inventorymanager', 'assigned_user_id1', 'description', 'quotestage', 'validtill',
					'bill_pobox', 'bill_city', 'bill_state', 'bill_code', 'bill_country',
					'ship_pobox', 'ship_city', 'ship_state', 'ship_code', 'ship_country'
				];
				hideFieldNames.forEach(function (name) {
					host.querySelectorAll('[name="' + name + '"], [name="' + name + '_display"]').forEach(function (field) {
						var valueTd = field.closest('td.fieldValue');
						if (valueTd) {
							valueTd.classList.add('mk-qt-hide-legacy');
							var labelTd = valueTd.previousElementSibling;
							if (labelTd && labelTd.classList && labelTd.classList.contains('fieldLabel')) {
								labelTd.classList.add('mk-qt-hide-legacy');
							}
							return;
						}
						var row = field.closest('tr');
						if (row) {
							row.classList.add('mk-qt-hide-legacy');
						}
					});
				});
				var contact = host.querySelector('[name="contact_id"], [name="contact_id_display"]');
				if (contact) {
					var cValue = contact.closest('td.fieldValue');
					if (cValue) {
						cValue.classList.remove('mk-qt-hide-legacy');
						var cLabel = cValue.previousElementSibling;
						if (cLabel && cLabel.classList && cLabel.classList.contains('fieldLabel')) {
							cLabel.classList.remove('mk-qt-hide-legacy');
							var lab = cLabel.querySelector('label');
							if (lab) {
								lab.textContent = 'Người liên hệ';
							}
						}
						var cRow = cValue.closest('tr');
						if (cRow) {
							cRow.classList.remove('mk-qt-hide-legacy');
						}
					}
				}
				var addrBlock = host.querySelector('.fieldBlockContainer[data-block="LBL_ADDRESS_INFORMATION"]');
				if (addrBlock) {
					addrBlock.classList.add('mk-qt-address-simplified');
				}
			})();
			{/literal}</script>
		</div>

		<aside class="mk-qt-rail" id="mkQtQuoteRail" aria-label="Quote summary">
			{* Quote info (Khách hàng / Ghi chú) is moved here by QuoteMkEdit.js *}
			{* Address card is injected by QuoteMkBa.js *}
		</aside>
	</div>
</div>
{if !empty($MK_SC_PREFILL_JSON)}
<script type="text/javascript">window.MK_SC_PREFILL = {$MK_SC_PREFILL_JSON};</script>
{/if}
{/strip}
