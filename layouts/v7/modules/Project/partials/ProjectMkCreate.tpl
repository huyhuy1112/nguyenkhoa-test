{* Project create/edit — MANAGEMENT dashboard shell + stock vtiger #EditView. *}
{strip}
{assign var=MK_LIST_URL value='index.php?module=Project&view=List&app=MANAGEMENT'}
{assign var=MK_IS_EDIT value=($RECORD_ID neq '' && empty($IS_DUPLICATE))}
{if $MK_IS_EDIT}
	{assign var=MK_RECORD_LABEL value=$RECORD_STRUCTURE_MODEL->getRecordName()}
{else}
	{assign var=MK_RECORD_LABEL value=''}
{/if}
<div class="mk-proj-create" id="mkProjCreateWorkspace" data-mk-proj-create="1" data-mk-proj-edit="{if $MK_IS_EDIT}1{else}0{/if}">
	<header class="mk-proj-page-head">
		<nav class="mk-proj-page-head__crumb" aria-label="Breadcrumb">
			<a href="index.php?module=Home&view=MainPage&app=MANAGEMENT">{vtranslate('LBL_HOME', 'Vtiger')}</a>
			<span aria-hidden="true">/</span>
			<a href="{$MK_LIST_URL}">{vtranslate('Project', $MODULE)}</a>
			<span aria-hidden="true">/</span>
			{if $MK_IS_EDIT}
				<span aria-current="page">{vtranslate('LBL_EDITING', $MODULE)}</span>
			{else}
				<span aria-current="page">{vtranslate('LBL_CREATING_NEW', $MODULE)}</span>
			{/if}
		</nav>
		<div class="mk-proj-page-head__row">
			<div>
				{if $MK_IS_EDIT}
					<h1 class="mk-proj-page-head__title">{vtranslate('LBL_EDITING', $MODULE)} {vtranslate('SINGLE_Project', $MODULE)} — {$MK_RECORD_LABEL|escape}</h1>
				{else}
					<h1 class="mk-proj-page-head__title">{vtranslate('LBL_CREATING_NEW', $MODULE)} {vtranslate('SINGLE_Project', $MODULE)}</h1>
				{/if}
				<p class="mk-proj-page-head__sub">{vtranslate('LBL_BASIC_INFORMATION', $MODULE)}</p>
			</div>
			<div class="mk-proj-page-head__actions">
				{if $MK_IS_EDIT}
					<a class="mk-proj-btn mk-proj-btn--ghost" href="index.php?module=Project&amp;view=Detail&amp;record={$RECORD_ID}&amp;app=MANAGEMENT">{vtranslate('LBL_CANCEL', $MODULE)}</a>
				{else}
					<a class="mk-proj-btn mk-proj-btn--ghost" href="{$MK_LIST_URL}">{vtranslate('LBL_CANCEL', $MODULE)}</a>
				{/if}
				<button type="button" class="mk-proj-btn mk-proj-btn--primary" id="mkProjSaveTop" data-action="save">
					{vtranslate('LBL_SAVE', $MODULE)}
				</button>
			</div>
		</div>
	</header>

	<div class="mk-proj-create-body">
		<div class="mk-proj-create-main">
			<div class="mk-proj-form-host" id="mkProjFormHost">
				{include file="partials/EditViewFormOnly.tpl"|vtemplate_path:$MODULE}
			</div>
		</div>
		{include file="partials/ProjectMkCreateAside.tpl"|vtemplate_path:$MODULE}
	</div>
</div>
{/strip}
