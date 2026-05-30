{* Create/Edit Document — MANAGEMENT shell + stock vtiger #EditView. *}
{strip}
{assign var=MK_LIST_URL value='index.php?module=Documents&view=List&app=MANAGEMENT'}
<div class="mk-doc-create" id="mkDocCreateWorkspace" data-mk-doc-create="1">
	<header class="mk-doc-page-head">
		<nav class="mk-doc-page-head__crumb" aria-label="Breadcrumb">
			<a href="index.php?module=Home&view=MainPage&app=MANAGEMENT">{vtranslate('LBL_HOME', 'Vtiger')}</a>
			<span aria-hidden="true">/</span>
			<a href="{$MK_LIST_URL}">{vtranslate('Documents', $MODULE)}</a>
			<span aria-hidden="true">/</span>
			<span aria-current="page">
				{if !empty($RECORD_ID)}
					{vtranslate('LBL_EDITING', $MODULE)}
				{else}
					{vtranslate('LBL_CREATING_NEW', $MODULE)}
				{/if}
			</span>
		</nav>
		<div class="mk-doc-page-head__row">
			<div>
				<h1 class="mk-doc-page-head__title">
					{if !empty($RECORD_ID)}
						{vtranslate('LBL_EDITING', $MODULE)} {vtranslate('SINGLE_'|cat:$MODULE, $MODULE)}
						{if !empty($RECORD_STRUCTURE_MODEL) && $RECORD_STRUCTURE_MODEL->getRecordName() neq ''}
							<span class="mk-doc-page-head__record"> — {$RECORD_STRUCTURE_MODEL->getRecordName()|escape:'html'}</span>
						{/if}
					{else}
						{vtranslate('LBL_CREATING_NEW', $MODULE)} {vtranslate('SINGLE_'|cat:$MODULE, $MODULE)}
					{/if}
				</h1>
				<p class="mk-doc-page-head__sub">{vtranslate('LBL_BASIC_INFORMATION', $MODULE)}</p>
			</div>
			<div class="mk-doc-page-head__actions">
				<a class="mk-doc-btn mk-doc-btn--ghost" href="{$MK_LIST_URL}">{vtranslate('LBL_CANCEL', $MODULE)}</a>
				<button type="button" class="mk-doc-btn mk-doc-btn--primary" id="mkDocSaveTop" data-action="save">
					{vtranslate('LBL_SAVE', $MODULE)}
				</button>
			</div>
		</div>
	</header>

	<div class="mk-doc-create-body">
		<div class="mk-doc-create-main">
			<div class="mk-doc-form-host" id="mkDocFormHost">
				{include file="partials/EditViewFormOnly.tpl"|vtemplate_path:$MODULE}
			</div>
		</div>
		{include file="partials/DocumentsMkCreateAside.tpl"|vtemplate_path:$MODULE}
	</div>
</div>
{/strip}
