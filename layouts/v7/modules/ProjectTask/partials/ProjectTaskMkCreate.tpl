{* Create Project Task — MANAGEMENT dashboard shell + stock vtiger #EditView. *}
{strip}
{assign var=MK_LIST_URL value='index.php?module=ProjectTask&view=List&app=MANAGEMENT'}
<div class="mk-ptask-create" id="mkPtaskCreateWorkspace" data-mk-ptask-create="1">
	<header class="mk-ptask-page-head">
		<nav class="mk-ptask-page-head__crumb" aria-label="Breadcrumb">
			<a href="index.php?module=Home&view=MainPage&app=MANAGEMENT">{vtranslate('LBL_HOME', 'Vtiger')}</a>
			<span aria-hidden="true">/</span>
			<a href="{$MK_LIST_URL}">{vtranslate('ProjectTask', $MODULE)}</a>
			<span aria-hidden="true">/</span>
			<span aria-current="page">{vtranslate('LBL_CREATING_NEW', $MODULE)}</span>
		</nav>
		<div class="mk-ptask-page-head__row">
			<div>
				<h1 class="mk-ptask-page-head__title">{vtranslate('LBL_CREATING_NEW', $MODULE)} {vtranslate('SINGLE_ProjectTask', $MODULE)}</h1>
				<p class="mk-ptask-page-head__sub">{vtranslate('LBL_BASIC_INFORMATION', $MODULE)}</p>
			</div>
			<div class="mk-ptask-page-head__actions">
				<a class="mk-ptask-btn mk-ptask-btn--ghost" href="{$MK_LIST_URL}">{vtranslate('LBL_CANCEL', $MODULE)}</a>
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
