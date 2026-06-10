{* Project Task create/edit — MANAGEMENT dashboard shell + stock vtiger #EditView. *}
{strip}
{assign var=MK_LIST_URL value='index.php?module=ProjectTask&view=List&app=MANAGEMENT'}
{assign var=MK_IS_EDIT value=($RECORD_ID neq '' && empty($IS_DUPLICATE))}
{if $MK_IS_EDIT}
	{assign var=MK_RECORD_LABEL value=$RECORD_STRUCTURE_MODEL->getRecordName()}
{else}
	{assign var=MK_RECORD_LABEL value=''}
{/if}
<div class="mk-ptask-create" id="mkPtaskCreateWorkspace" data-mk-ptask-create="1" data-mk-ptask-edit="{if $MK_IS_EDIT}1{else}0{/if}">
	<header class="mk-ptask-page-head">
		<nav class="mk-ptask-page-head__crumb" aria-label="Breadcrumb">
			<a href="index.php?module=Home&view=MainPage&app=MANAGEMENT">{vtranslate('LBL_HOME', 'Vtiger')}</a>
			<span aria-hidden="true">/</span>
			<a href="{$MK_LIST_URL}">{vtranslate('ProjectTask', $MODULE)}</a>
			<span aria-hidden="true">/</span>
			{if $MK_IS_EDIT}
				<span aria-current="page">{vtranslate('LBL_EDITING', $MODULE)}</span>
			{else}
				<span aria-current="page">{vtranslate('LBL_CREATING_NEW', $MODULE)}</span>
			{/if}
		</nav>
		<div class="mk-ptask-page-head__row">
			<div>
				{if $MK_IS_EDIT}
					<h1 class="mk-ptask-page-head__title">{vtranslate('LBL_EDITING', $MODULE)} {vtranslate('SINGLE_ProjectTask', $MODULE)} — {$MK_RECORD_LABEL|escape}</h1>
				{else}
					<h1 class="mk-ptask-page-head__title">{vtranslate('LBL_CREATING_NEW', $MODULE)} {vtranslate('SINGLE_ProjectTask', $MODULE)}</h1>
				{/if}
				<p class="mk-ptask-page-head__sub">{vtranslate('LBL_BASIC_INFORMATION', $MODULE)}</p>
			</div>
			<div class="mk-ptask-page-head__actions">
				{if $MK_IS_EDIT}
					<a class="mk-ptask-btn mk-ptask-btn--ghost" href="index.php?module=ProjectTask&amp;view=Detail&amp;record={$RECORD_ID}&amp;app=MANAGEMENT">{vtranslate('LBL_CANCEL', $MODULE)}</a>
				{else}
					<a class="mk-ptask-btn mk-ptask-btn--ghost" href="{$MK_LIST_URL}">{vtranslate('LBL_CANCEL', $MODULE)}</a>
				{/if}
				<button type="button" class="mk-ptask-btn mk-ptask-btn--primary" id="mkPtaskSaveTop" data-action="save">
					{vtranslate('LBL_SAVE', $MODULE)}
				</button>
			</div>
		</div>
	</header>

	<div class="mk-ptask-create-body">
		<div class="mk-ptask-create-main">
			<div class="mk-ptask-form-host" id="mkPtaskFormHost">
				{include file="partials/EditViewFormOnly.tpl"|vtemplate_path:$MODULE}
			</div>
		</div>
		{include file="partials/ProjectTaskMkCreateAside.tpl"|vtemplate_path:$MODULE}
	</div>
</div>
{/strip}
