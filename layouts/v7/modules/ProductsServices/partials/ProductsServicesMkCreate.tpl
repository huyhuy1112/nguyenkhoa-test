{* Create Products & Services — dashboard shell + stock vtiger #EditView. *}
{strip}
{assign var=MK_LIST_URL value='index.php?module=ProductsServices&view=List&app=SALES'}
<div class="mk-ps-create" id="mkPsCreateWorkspace" data-mk-ps-create="1">
	<header class="mk-ps-page-head">
		<nav class="mk-ps-page-head__crumb" aria-label="Breadcrumb">
			<a href="index.php?module=Home&view=MainPage&app=SALES">{vtranslate('LBL_HOME', 'Vtiger')}</a>
			<span aria-hidden="true">/</span>
			<a href="{$MK_LIST_URL}">{vtranslate('ProductsServices', $MODULE)}</a>
			<span aria-hidden="true">/</span>
			<span aria-current="page">{vtranslate('LBL_CREATING_NEW', $MODULE)}</span>
		</nav>
		<div class="mk-ps-page-head__row">
			<div>
				<h1 class="mk-ps-page-head__title">{vtranslate('LBL_CREATING_NEW', $MODULE)} {vtranslate('SINGLE_ProductsServices', $MODULE)}</h1>
				<p class="mk-ps-page-head__sub">{vtranslate('LBL_BASIC_INFORMATION', $MODULE)}</p>
			</div>
			<div class="mk-ps-page-head__actions">
				<a class="mk-ps-btn mk-ps-btn--ghost" href="{$MK_LIST_URL}">{vtranslate('LBL_CANCEL', $MODULE)}</a>
				<button type="button" class="mk-ps-btn mk-ps-btn--primary" id="mkPsSaveTop" data-action="save">
					{vtranslate('LBL_SAVE', $MODULE)}
				</button>
			</div>
		</div>
	</header>

	<div class="mk-ps-create-body">
		<div class="mk-ps-create-main">
			<div class="mk-ps-form-host" id="mkPsFormHost">
				{include file="partials/EditViewFormOnly.tpl"|vtemplate_path:$MODULE}
			</div>
		</div>
	</div>
</div>
{/strip}
