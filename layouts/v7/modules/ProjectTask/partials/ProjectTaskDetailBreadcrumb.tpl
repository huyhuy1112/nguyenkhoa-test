{* ProjectTask Detail breadcrumb — MANAGEMENT *}
{strip}
<nav class="mk-projecttask-detail-breadcrumb" aria-label="Breadcrumb">
	<ol class="mk-projecttask-detail-breadcrumb__list">
		<li class="mk-projecttask-detail-breadcrumb__item">
			<a href="index.php?module=ProjectTask&amp;view=List&amp;app=MANAGEMENT">{vtranslate('ProjectTask', 'ProjectTask')}</a>
		</li>
		<li class="mk-projecttask-detail-breadcrumb__sep" aria-hidden="true">&gt;</li>
		<li class="mk-projecttask-detail-breadcrumb__item">
			<a href="index.php?module=ProjectTask&amp;view=List&amp;app=MANAGEMENT">{vtranslate('LBL_ALL', $MODULE_NAME)|default:'All'}</a>
		</li>
		{assign var=PROJECT_ID value=$RECORD->get('projectid')}
		{if $PROJECT_ID}
		<li class="mk-projecttask-detail-breadcrumb__sep" aria-hidden="true">&gt;</li>
		<li class="mk-projecttask-detail-breadcrumb__item">
			<a href="index.php?module=Project&amp;view=Detail&amp;record={$PROJECT_ID}&amp;app=MANAGEMENT">{assign var=PROJECT_RECORD value=Vtiger_Record_Model::getInstanceById($PROJECT_ID, 'Project')}{$PROJECT_RECORD->getName()|escape:'html'}</a>
		</li>
		{/if}
		<li class="mk-projecttask-detail-breadcrumb__sep" aria-hidden="true">&gt;</li>
		<li class="mk-projecttask-detail-breadcrumb__item mk-projecttask-detail-breadcrumb__item--current">
			<span class="mk-projecttask-detail-breadcrumb__text textOverflowEllipsis" title="{$RECORD->getName()|escape:'html'}">{$RECORD->getName()}</span>
		</li>
	</ol>
</nav>
{/strip}
