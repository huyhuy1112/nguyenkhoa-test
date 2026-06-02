{* Edit Lead — SALES shell + stock vtiger #EditView. *}
{strip}
{assign var=MK_LIST_URL value='index.php?module=Leads&view=List&app=SALES'}
{assign var=MK_DETAIL_URL value='index.php?module=Leads&view=Detail&record='|cat:$RECORD_ID|cat:'&app=SALES'}
<div class="mk-td-create mk-td-edit-stock" id="mkTdEditStockWorkspace">
	<header class="mk-td-create__head">
		<nav class="mk-td-create__crumb" aria-label="Breadcrumb">
			<a href="index.php?module=Home&view=MainPage&app=SALES">{vtranslate('LBL_HOME', 'Vtiger')}</a>
			<span class="mk-td-create__crumb-sep">/</span>
			<a href="{$MK_LIST_URL}">{vtranslate('Leads', $MODULE)}</a>
			<span class="mk-td-create__crumb-sep">/</span>
			<span>{vtranslate('LBL_EDITING', $MODULE)}</span>
		</nav>
		<div class="mk-td-create__head-row">
			<div>
				<h1 class="mk-td-create__title">{vtranslate('LBL_EDITING', $MODULE)} {vtranslate('SINGLE_Leads', $MODULE)}</h1>
				{if !empty($RECORD_STRUCTURE_MODEL)}
					<p class="mk-td-create__subtitle">{$RECORD_STRUCTURE_MODEL->getRecordName()|escape}</p>
				{/if}
			</div>
			<div class="mk-td-create__head-actions">
				<a class="mk-td-btn mk-td-btn--ghost" href="{$MK_DETAIL_URL|default:$MK_LIST_URL}">{vtranslate('LBL_CANCEL', $MODULE)}</a>
				<button type="button" class="mk-td-btn mk-td-btn--dark" id="mkTdStockSaveTop">{vtranslate('LBL_SAVE', $MODULE)}</button>
			</div>
		</div>
	</header>
	<div class="mk-td-form-host" id="mkTdStockFormHost">
		{include file="EditView.tpl"|@vtemplate_path:'Vtiger'}
	</div>
</div>
{/strip}
