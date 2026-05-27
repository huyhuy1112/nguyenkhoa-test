{* Create Contact — dashboard shell + stock vtiger #EditView (all real fields). *}
{strip}
{assign var=MK_LIST_URL value='index.php?module=Contacts&view=List&app=SALES'}
<div class="mk-ct-create" id="mkCtCreateWorkspace" data-mk-contact-create="1">
	<header class="mk-ct-page-head">
		<nav class="mk-ct-page-head__crumb" aria-label="Breadcrumb">
			<a href="index.php?module=Home&view=MainPage&app=SALES">{vtranslate('LBL_HOME', 'Vtiger')}</a>
			<span aria-hidden="true">/</span>
			<a href="{$MK_LIST_URL}">{vtranslate('Contacts', $MODULE)}</a>
			<span aria-hidden="true">/</span>
			<span aria-current="page">{vtranslate('LBL_CREATING_NEW', $MODULE)}</span>
		</nav>
		<div class="mk-ct-page-head__row">
			<div>
				<h1 class="mk-ct-page-head__title">{vtranslate('LBL_CREATING_NEW', $MODULE)} {vtranslate('SINGLE_Contacts', $MODULE)}</h1>
				<p class="mk-ct-page-head__sub">{vtranslate('LBL_CONTACT_INFORMATION', $MODULE)}</p>
			</div>
			<div class="mk-ct-page-head__actions">
				<a class="mk-ct-btn mk-ct-btn--ghost" href="{$MK_LIST_URL}">{vtranslate('LBL_CANCEL', $MODULE)}</a>
				<button type="button" class="mk-ct-btn mk-ct-btn--primary" id="mkCtSaveTop" data-action="save">
					{vtranslate('LBL_SAVE', $MODULE)}
				</button>
			</div>
		</div>
	</header>

	<div class="mk-ct-form-host" id="mkCtFormHost">
		{include file="layouts/v7/modules/Vtiger/EditView.tpl"}
	</div>
</div>
{/strip}
