{* Create Opportunity — dashboard shell + stock vtiger #EditView (all real fields). *}
{strip}
{assign var=MK_LIST_URL value='index.php?module=Potentials&view=List&app=SALES'}
<div class="mk-opp-create" id="mkOppCreateWorkspace" data-mk-opp-create="1">
	<header class="mk-opp-page-head">
		<nav class="mk-opp-page-head__crumb" aria-label="Breadcrumb">
			<a href="index.php?module=Home&view=MainPage&app=SALES">{vtranslate('LBL_HOME', 'Vtiger')}</a>
			<span aria-hidden="true">/</span>
			<a href="{$MK_LIST_URL}">{vtranslate('Potentials', $MODULE)}</a>
			<span aria-hidden="true">/</span>
			<span aria-current="page">{vtranslate('LBL_CREATING_NEW', $MODULE)}</span>
		</nav>
		<div class="mk-opp-page-head__row">
			<div>
				<h1 class="mk-opp-page-head__title">{vtranslate('LBL_CREATING_NEW', $MODULE)} {vtranslate('SINGLE_Potentials', $MODULE)}</h1>
				<p class="mk-opp-page-head__sub">{vtranslate('LBL_BASIC_INFORMATION', $MODULE)}</p>
			</div>
			<div class="mk-opp-page-head__actions">
				<a class="mk-opp-btn mk-opp-btn--ghost" href="{$MK_LIST_URL}">{vtranslate('LBL_CANCEL', $MODULE)}</a>
				<button type="button" class="mk-opp-btn mk-opp-btn--primary" id="mkOppSaveTop" data-action="save">
					{vtranslate('LBL_SAVE', $MODULE)}
				</button>
			</div>
		</div>
	</header>

	<div class="mk-opp-form-host" id="mkOppFormHost">
		{include file="layouts/v7/modules/Vtiger/EditView.tpl"}
	</div>
</div>
{/strip}
