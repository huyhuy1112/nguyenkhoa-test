{* Create Opportunity — dashboard shell + stock vtiger #EditView (all real fields). *}
{strip}
{assign var=MK_LIST_URL value='index.php?module=Potentials&view=List&app=SALES'}
{assign var=MK_IS_EDIT value=(!empty($RECORD_ID) && empty($IS_DUPLICATE))}
<div class="mk-opp-create{if $MK_IS_EDIT} mk-opp-create--edit{/if}" id="mkOppCreateWorkspace" data-mk-opp-create="1">
	<header class="mk-opp-page-head">
		<nav class="mk-opp-page-head__crumb" aria-label="Breadcrumb">
			<a href="index.php?module=Home&view=MainPage&app=SALES">{vtranslate('LBL_HOME', 'Vtiger')}</a>
			<span aria-hidden="true">/</span>
			<a href="{$MK_LIST_URL}">{vtranslate('Potentials', $MODULE)}</a>
			<span aria-hidden="true">/</span>
			{if $MK_IS_EDIT}
				<span aria-current="page">{vtranslate('LBL_EDITING', $MODULE)}</span>
			{else}
				<span aria-current="page">{vtranslate('LBL_CREATING_NEW', $MODULE)}</span>
			{/if}
		</nav>
		<div class="mk-opp-page-head__row">
			<div>
				{if $MK_IS_EDIT}
					<h1 class="mk-opp-page-head__title">{vtranslate('LBL_EDITING', $MODULE)} {vtranslate('SINGLE_Potentials', $MODULE)}</h1>
					{if !empty($RECORD_STRUCTURE_MODEL)}
						<p class="mk-opp-page-head__sub">{$RECORD_STRUCTURE_MODEL->getRecordName()|escape}</p>
					{else}
						<p class="mk-opp-page-head__sub">{vtranslate('LBL_OPPORTUNITY_INFORMATION', $MODULE)}</p>
					{/if}
				{else}
					<h1 class="mk-opp-page-head__title">{vtranslate('LBL_CREATING_NEW', $MODULE)} {vtranslate('SINGLE_Potentials', $MODULE)}</h1>
					<p class="mk-opp-page-head__sub">{vtranslate('LBL_BASIC_INFORMATION', $MODULE)}</p>
				{/if}
			</div>
			<div class="mk-opp-page-head__actions">
				<a class="mk-opp-btn mk-opp-btn--ghost" href="{$MK_LIST_URL}">{vtranslate('LBL_CANCEL', $MODULE)}</a>
				<button type="button" class="mk-opp-btn mk-opp-btn--primary" id="mkOppSaveTop" data-action="save">
					{vtranslate('LBL_SAVE', $MODULE)}
				</button>
			</div>
		</div>
	</header>

	<div class="mk-opp-create-body{if $MK_IS_EDIT} mk-opp-create-body--edit{/if}">
		<div class="mk-opp-create-main">
			<div class="mk-opp-form-host" id="mkOppFormHost">
				{include file="partials/EditViewFormOnly.tpl"|vtemplate_path:$MODULE}
			</div>
		</div>
		{if !$MK_IS_EDIT}
		{include file="partials/OpportunityMkCreateAside.tpl"|vtemplate_path:$MODULE}
		{/if}
	</div>
</div>
{/strip}
