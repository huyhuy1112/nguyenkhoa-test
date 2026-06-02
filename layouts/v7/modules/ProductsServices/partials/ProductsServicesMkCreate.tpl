{* Create Products & Services — dashboard shell + stock vtiger #EditView. *}
{strip}
{assign var=MK_LIST_URL value='index.php?module=ProductsServices&view=List&app=SALES'}
{assign var=MK_IS_EDIT value=(!empty($RECORD_ID) && empty($IS_DUPLICATE))}
<div class="mk-ps-create{if $MK_IS_EDIT} mk-ps-create--edit{/if}" id="mkPsCreateWorkspace" data-mk-ps-create="1">
	<header class="mk-ps-page-head">
		<nav class="mk-ps-page-head__crumb" aria-label="Breadcrumb">
			<a href="index.php?module=Home&view=MainPage&app=SALES">{vtranslate('LBL_HOME', 'Vtiger')}</a>
			<span aria-hidden="true">/</span>
			<a href="{$MK_LIST_URL}">{vtranslate('ProductsServices', $MODULE)}</a>
			<span aria-hidden="true">/</span>
			{if $MK_IS_EDIT}<span aria-current="page">{vtranslate('LBL_EDITING', $MODULE)}</span>{else}<span aria-current="page">{vtranslate('LBL_CREATING_NEW', $MODULE)}</span>{/if}
		</nav>
		<div class="mk-ps-page-head__row">
			<div>
				{if $MK_IS_EDIT}
					<h1 class="mk-ps-page-head__title">{vtranslate('LBL_EDITING', $MODULE)} {vtranslate('SINGLE_ProductsServices', $MODULE)}</h1>
					{if !empty($RECORD_STRUCTURE_MODEL)}<p class="mk-ps-page-head__sub">{$RECORD_STRUCTURE_MODEL->getRecordName()|escape}</p>{else}<p class="mk-ps-page-head__sub">{vtranslate('LBL_BASIC_INFORMATION', $MODULE)}</p>{/if}
				{else}
					<h1 class="mk-ps-page-head__title">{vtranslate('LBL_CREATING_NEW', $MODULE)} {vtranslate('SINGLE_ProductsServices', $MODULE)}</h1>
					<p class="mk-ps-page-head__sub">{vtranslate('LBL_BASIC_INFORMATION', $MODULE)}</p>
				{/if}
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
