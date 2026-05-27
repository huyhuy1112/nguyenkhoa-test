{* Create Sales Order — dashboard shell + stock Inventory #EditView (fields + line items). *}
{strip}
{assign var=MK_LIST_URL value='index.php?module=SalesOrder&view=List&app=SALES'}
<div class="mk-so-create" id="mkSoCreateWorkspace" data-mk-sales-order-create="1">
	<header class="mk-so-page-head">
		<nav class="mk-so-page-head__crumb" aria-label="Breadcrumb">
			<a href="index.php?module=Home&view=MainPage&app=SALES">{vtranslate('LBL_HOME', 'Vtiger')}</a>
			<span aria-hidden="true">/</span>
			<a href="{$MK_LIST_URL}">{vtranslate('SalesOrder', $MODULE)}</a>
			<span aria-hidden="true">/</span>
			<span aria-current="page">{vtranslate('LBL_CREATING_NEW', $MODULE)}</span>
		</nav>
		<div class="mk-so-page-head__row">
			<div>
				<h1 class="mk-so-page-head__title">{vtranslate('LBL_CREATING_NEW', $MODULE)} {vtranslate('SINGLE_SalesOrder', $MODULE)}</h1>
				<p class="mk-so-page-head__sub">{vtranslate('LBL_SO_INFORMATION', $MODULE)}</p>
			</div>
			<div class="mk-so-page-head__actions">
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
</div>
{/strip}
