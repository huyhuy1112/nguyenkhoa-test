{* Create Organization — dashboard shell + stock vtiger #EditView (all real fields). *}
{strip}
{assign var=MK_APP value=$SELECTED_MENU_CATEGORY|default:$smarty.get.app|default:'SALES'}
{assign var=MK_LIST_URL value="index.php?module=Accounts&view=List&app=`$MK_APP`"}
{assign var=MK_IS_EDIT value=(!empty($RECORD_ID) && empty($IS_DUPLICATE))}
<div class="mk-ac-create{if $MK_IS_EDIT} mk-ac-create--edit{/if}" id="mkAcCreateWorkspace" data-mk-org-create="1">
	<header class="mk-ac-page-head">
		<nav class="mk-ac-page-head__crumb" aria-label="Breadcrumb">
			<a href="index.php?module=Home&view=MainPage&app={$MK_APP}">{vtranslate('LBL_HOME', 'Vtiger')}</a>
			<span aria-hidden="true">/</span>
			<a href="{$MK_LIST_URL}">{vtranslate('Accounts', $MODULE)}</a>
			<span aria-hidden="true">/</span>
			{if $MK_IS_EDIT}<span aria-current="page">{vtranslate('LBL_EDITING', $MODULE)}</span>{else}<span aria-current="page">{vtranslate('LBL_CREATING_NEW', $MODULE)}</span>{/if}
		</nav>
		<div class="mk-ac-page-head__row">
			<div>
				{if $MK_IS_EDIT}
					<h1 class="mk-ac-page-head__title">{vtranslate('LBL_EDITING', $MODULE)} {vtranslate('SINGLE_Accounts', $MODULE)}</h1>
					{if !empty($RECORD_STRUCTURE_MODEL)}<p class="mk-ac-page-head__sub">{$RECORD_STRUCTURE_MODEL->getRecordName()|escape}</p>{else}<p class="mk-ac-page-head__sub">{vtranslate('LBL_ACCOUNT_INFORMATION', $MODULE)}</p>{/if}
				{else}
					<h1 class="mk-ac-page-head__title">Tạo Tuibao — Hợp đồng nhượng quyền</h1>
					<p class="mk-ac-page-head__sub">Nhập đủ thông tin Bên B + phí để in hợp đồng TUI BAO hoàn chỉnh</p>
				{/if}
			</div>
			<div class="mk-ac-page-head__actions">
				<a class="mk-ac-btn mk-ac-btn--ghost" href="{$MK_LIST_URL}">{vtranslate('LBL_CANCEL', $MODULE)}</a>
				<button type="button" class="mk-ac-btn mk-ac-btn--primary" id="mkAcSaveTop" data-action="save">
					{vtranslate('LBL_SAVE', $MODULE)}
				</button>
			</div>
		</div>
	</header>

	<div class="mk-ac-form-host" id="mkAcFormHost">
		{include file="layouts/v7/modules/Vtiger/EditView.tpl"}
	</div>
</div>
{/strip}

