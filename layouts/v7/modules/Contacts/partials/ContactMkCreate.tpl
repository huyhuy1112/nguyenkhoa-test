{* Create Contact — dashboard shell + stock vtiger #EditView (all real fields). *}
{strip}
{assign var=MK_APP value=((isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY neq '') ? $SELECTED_MENU_CATEGORY : ((isset($smarty.get.app) && $smarty.get.app neq '') ? $smarty.get.app : 'SALES'))}
{assign var=MK_LIST_URL value="index.php?module=Contacts&view=List&app={$MK_APP}"}
{assign var=MK_IS_EDIT value=(!empty($RECORD_ID) && empty($IS_DUPLICATE))}
<div class="mk-ct-create{if $MK_IS_EDIT} mk-ct-create--edit{/if}" id="mkCtCreateWorkspace" data-mk-contact-create="1">
	<header class="mk-ct-page-head">
		<nav class="mk-ct-page-head__crumb" aria-label="Breadcrumb">
			<a href="index.php?module=Home&view=MainPage&app={$MK_APP}">{vtranslate('LBL_HOME', 'Vtiger')}</a>
			<span aria-hidden="true">/</span>
			<a href="{$MK_LIST_URL}">{vtranslate('Contacts', $MODULE)}</a>
			<span aria-hidden="true">/</span>
			{if $MK_IS_EDIT}<span aria-current="page">{vtranslate('LBL_EDITING', $MODULE)}</span>{else}<span aria-current="page">{vtranslate('LBL_CREATING_NEW', $MODULE)}</span>{/if}
		</nav>
		<div class="mk-ct-page-head__row">
			<div>
				{if $MK_IS_EDIT}
					<h1 class="mk-ct-page-head__title">{vtranslate('LBL_EDITING', $MODULE)} {vtranslate('SINGLE_Contacts', $MODULE)}</h1>
					{if !empty($RECORD_STRUCTURE_MODEL)}<p class="mk-ct-page-head__sub">{$RECORD_STRUCTURE_MODEL->getRecordName()|escape}</p>{else}<p class="mk-ct-page-head__sub">{vtranslate('LBL_CONTACT_INFORMATION', $MODULE)}</p>{/if}
				{else}
					<h1 class="mk-ct-page-head__title">{vtranslate('LBL_CREATING_NEW', $MODULE)} {vtranslate('SINGLE_Contacts', $MODULE)}</h1>
					<p class="mk-ct-page-head__sub">{vtranslate('LBL_CONTACT_INFORMATION', $MODULE)}</p>
				{/if}
			</div>
			<div class="mk-ct-page-head__actions">
				<a class="mk-ct-btn mk-ct-btn--ghost" href="{$MK_LIST_URL}">{vtranslate('LBL_CANCEL', $MODULE)}</a>
				<button type="button" class="mk-ct-btn mk-ct-btn--primary" id="mkCtSaveTop" data-action="save">
					{vtranslate('LBL_SAVE', $MODULE)}
				</button>
			</div>
		</div>
	</header>

	<div class="mk-ct-form-host" id="mkCtFormHost">
		{include file="layouts/v7/modules/Vtiger/EditView.tpl"}
	</div>
</div>
{/strip}
