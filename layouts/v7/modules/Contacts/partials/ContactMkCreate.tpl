{* Create Contact — dashboard shell + stock vtiger #EditView (all real fields). *}
{strip}
{assign var=MK_APP value=((isset($SELECTED_MENU_CATEGORY) && $SELECTED_MENU_CATEGORY neq '') ? $SELECTED_MENU_CATEGORY : ((isset($smarty.get.app) && $smarty.get.app neq '') ? $smarty.get.app : 'SALES'))}
{assign var=MK_LIST_URL value="index.php?module=Contacts&view=List&app={$MK_APP}"}
{assign var=MK_IS_EDIT value=(!empty($RECORD_ID) && empty($IS_DUPLICATE))}
{if !isset($MK_CONTACT_EDIT_TAGS_JSON)}{assign var=MK_CONTACT_EDIT_TAGS_JSON value='[]'}{/if}
<div class="mk-ct-create{if $MK_IS_EDIT} mk-ct-create--edit{/if}" id="mkCtCreateWorkspace" data-mk-contact-create="1">
	<script type="application/json" id="mkCtEditTagsBoot">{$MK_CONTACT_EDIT_TAGS_JSON nofilter}</script>
	<header class="mk-ct-page-head">
		<div class="mk-ct-page-head__row">
			<div>
				{if $MK_IS_EDIT}
					<h1 class="mk-ct-page-head__title">{vtranslate('LBL_EDITING', $MODULE)} {vtranslate('SINGLE_Contacts', $MODULE)}</h1>
				{else}
					<h1 class="mk-ct-page-head__title">{vtranslate('LBL_CREATING_NEW', $MODULE)} {vtranslate('SINGLE_Contacts', $MODULE)}</h1>
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
