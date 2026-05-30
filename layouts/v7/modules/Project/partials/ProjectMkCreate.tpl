{* Create Project — MANAGEMENT dashboard shell + stock vtiger #EditView. *}
{strip}
{assign var=MK_LIST_URL value='index.php?module=Project&view=List&app=MANAGEMENT'}
<div class="mk-proj-create" id="mkProjCreateWorkspace" data-mk-proj-create="1">
	<header class="mk-proj-page-head">
		<nav class="mk-proj-page-head__crumb" aria-label="Breadcrumb">
			<a href="index.php?module=Home&view=MainPage&app=MANAGEMENT">{vtranslate('LBL_HOME', 'Vtiger')}</a>
			<span aria-hidden="true">/</span>
			<a href="{$MK_LIST_URL}">{vtranslate('Project', $MODULE)}</a>
			<span aria-hidden="true">/</span>
			<span aria-current="page">{vtranslate('LBL_CREATING_NEW', $MODULE)}</span>
		</nav>
		<div class="mk-proj-page-head__row">
			<div>
				<h1 class="mk-proj-page-head__title">{vtranslate('LBL_CREATING_NEW', $MODULE)} {vtranslate('SINGLE_Project', $MODULE)}</h1>
				<p class="mk-proj-page-head__sub">{vtranslate('LBL_BASIC_INFORMATION', $MODULE)}</p>
			</div>
			<div class="mk-proj-page-head__actions">
				<a class="mk-proj-btn mk-proj-btn--ghost" href="{$MK_LIST_URL}">{vtranslate('LBL_CANCEL', $MODULE)}</a>
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
