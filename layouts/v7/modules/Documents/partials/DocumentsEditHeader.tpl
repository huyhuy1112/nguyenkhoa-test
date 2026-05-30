{strip}
<nav class="mk-documents-edit-breadcrumb" aria-label="Breadcrumb">
	<ol class="mk-documents-edit-breadcrumb__list">
		<li class="mk-documents-edit-breadcrumb__item">
			<a href="index.php?module=Home&amp;view=MainPage&amp;app=MANAGEMENT">{vtranslate('LBL_MANAGEMENT', 'Vtiger')}</a>
		</li>
		<li class="mk-documents-edit-breadcrumb__sep" aria-hidden="true">&gt;</li>
		<li class="mk-documents-edit-breadcrumb__item">
			<a href="index.php?module=Documents&amp;view=List&amp;app=MANAGEMENT">{vtranslate('Documents', 'Documents')}</a>
		</li>
		<li class="mk-documents-edit-breadcrumb__sep" aria-hidden="true">&gt;</li>
		<li class="mk-documents-edit-breadcrumb__item mk-documents-edit-breadcrumb__item--current">
			<span aria-current="page">
				{if !empty($RECORD_ID)}
					{vtranslate('LBL_EDITING', $MODULE)} {vtranslate('SINGLE_'|cat:$MODULE, $MODULE)}
				{else}
					{vtranslate('LBL_CREATING_NEW', $MODULE)} {vtranslate('SINGLE_'|cat:$MODULE, $MODULE)}
				{/if}
			</span>
		</li>
	</ol>
</nav>
<div class="mk-documents-edit-page-head">
	<div class="mk-documents-edit-page-head__icon" aria-hidden="true"><i class="fa fa-file-text-o"></i></div>
	<div class="mk-documents-edit-page-head__text">
		<h1 class="mk-documents-edit-page-head__title">
			{if !empty($RECORD_ID)}
				{vtranslate('LBL_EDITING', $MODULE)} {vtranslate('SINGLE_'|cat:$MODULE, $MODULE)}
				{if !empty($RECORD_STRUCTURE_MODEL) && $RECORD_STRUCTURE_MODEL->getRecordName() neq ''}
					<span class="mk-documents-edit-page-head__record-name"> — {$RECORD_STRUCTURE_MODEL->getRecordName()|escape:'html'}</span>
				{/if}
			{else}
				{vtranslate('LBL_CREATING_NEW', $MODULE)} {vtranslate('SINGLE_'|cat:$MODULE, $MODULE)}
			{/if}
		</h1>
	</div>
</div>
{/strip}
