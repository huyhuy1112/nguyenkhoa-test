{* Create Support FAQ — dashboard shell + stock vtiger #EditView *}
{strip}
{assign var=MK_LIST_URL value='index.php?module=SupportFAQ&view=List&app=SUPPORT'}
<div class="mk-sf-faq-page mk-sf-faq-create" id="mkSfFaqCreateWorkspace" data-mk-sf-faq-create="1">
	<div class="mk-sf-faq-suite-card">
		<header class="mk-sf-faq-page-head">
		<nav class="mk-sf-faq-page-head__crumb" aria-label="Breadcrumb">
			<a href="index.php?module=HelpDesk&amp;view=List&amp;app=SUPPORT">Support</a>
			<span aria-hidden="true">/</span>
			<a href="{$MK_LIST_URL}">{vtranslate('SupportFAQ', $MODULE)}</a>
			<span aria-hidden="true">/</span>
			<span aria-current="page">{if $MODE eq 'edit'}{vtranslate('LBL_EDITING', $MODULE)}{else}{vtranslate('LBL_CREATING_NEW', $MODULE)}{/if}</span>
		</nav>
		<div class="mk-sf-faq-page-head__row">
			<div>
				<h1 class="mk-sf-faq-page-head__title">
					{if $MODE eq 'edit'}{vtranslate('LBL_EDITING', $MODULE)}{else}{vtranslate('LBL_CREATING_NEW', $MODULE)}{/if}
					{vtranslate('SINGLE_SupportFAQ', $MODULE)}
				</h1>
				<p class="mk-sf-faq-page-head__sub">{vtranslate('LBL_SUPPORTFAQ_INFORMATION', $MODULE)}</p>
			</div>
			<div class="mk-sf-faq-page-head__actions">
				<a class="mk-sf-faq-btn mk-sf-faq-btn--outline" href="{$MK_LIST_URL}">{vtranslate('LBL_CANCEL', $MODULE)}</a>
				<button type="button" class="mk-sf-faq-btn mk-sf-faq-btn--primary" id="mkSfFaqSaveTop" data-action="save">
					{vtranslate('LBL_SAVE', $MODULE)}
				</button>
			</div>
		</div>
		</header>

		<div class="mk-sf-faq-edit-content">
			<div class="mk-sf-faq-create-body">
				<div class="mk-sf-faq-create-main">
					<div class="mk-sf-faq-form-host" id="mkSfFaqFormHost">
						{include file="layouts/v7/modules/Vtiger/EditView.tpl"}
					</div>
				</div>
				{include file="partials/FaqCreateAside.tpl"|vtemplate_path:$MODULE}
			</div>
		</div>
	</div>
</div>
{/strip}
