{* Create / Edit Event — MANAGEMENT shell + stock vtiger #EditView fields. *}
{strip}
{assign var=MK_CAL_URL value=$MK_CALENDAR_URL|default:'index.php?module=Calendar&view=Calendar&app=MANAGEMENT'}
<div class="mk-event-create" id="mkEventCreateWorkspace" data-mk-event-create="1">
	<header class="mk-event-page-head">
		<nav class="mk-event-page-head__crumb" aria-label="Breadcrumb">
			<a href="index.php?module=Home&view=MainPage&app=MANAGEMENT">{vtranslate('LBL_HOME', 'Vtiger')}</a>
			<span aria-hidden="true">/</span>
			<a href="{$MK_CAL_URL}">{vtranslate('Calendar', 'Calendar')}</a>
			<span aria-hidden="true">/</span>
			{if !empty($RECORD_ID)}
				<span aria-current="page">{vtranslate('LBL_EDITING', $MODULE)}</span>
			{else}
				<span aria-current="page">{vtranslate('LBL_CREATING_NEW', $MODULE)}</span>
			{/if}
		</nav>
		<div class="mk-event-page-head__row">
			<div>
				{if !empty($RECORD_ID)}
					<h1 class="mk-event-page-head__title">{vtranslate('LBL_EDITING', $MODULE)} {vtranslate('SINGLE_Events', $MODULE)}</h1>
					{if !empty($RECORD_STRUCTURE_MODEL)}
						<p class="mk-event-page-head__sub">{$RECORD_STRUCTURE_MODEL->getRecordName()|escape}</p>
					{/if}
				{else}
					<h1 class="mk-event-page-head__title">{vtranslate('LBL_CREATING_NEW', $MODULE)} {vtranslate('SINGLE_Events', $MODULE)}</h1>
					<p class="mk-event-page-head__sub">{vtranslate('LBL_EVENT_INFORMATION', $MODULE)}</p>
				{/if}
			</div>
			<div class="mk-event-page-head__actions">
				<a class="mk-event-btn mk-event-btn--ghost" href="{$MK_CAL_URL}">{vtranslate('LBL_CANCEL', $MODULE)}</a>
				<button type="button" class="mk-event-btn mk-event-btn--primary" id="mkActivitySaveTop" data-action="save">
					{vtranslate('LBL_SAVE', $MODULE)}
				</button>
			</div>
		</div>
	</header>

	<div class="mk-event-create-body">
		<div class="mk-event-form-host" id="mkEventFormHost">
			{include file="partials/EditViewFormOnly.tpl"|vtemplate_path:$MODULE}
		</div>
	</div>
</div>
{/strip}
