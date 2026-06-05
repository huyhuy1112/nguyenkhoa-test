{* Tools app — SalesOrder detail hero strip *}
{strip}
{assign var=MK_STATUS value=$RECORD->getDisplayValue('internal_order_status')}
<div class="mk-so-tools-hero">
	<div class="mk-so-tools-hero__main">
		<div class="mk-so-tools-hero__identity">
			<div class="mk-so-tools-hero__icon" aria-hidden="true">
				<span class="mk-so-tools-hero__icon-label">SO</span>
			</div>
			<div class="mk-so-tools-hero__text">
				<h1 class="mk-so-tools-hero__title" title="{$RECORD->getName()|escape:'html'}">{$RECORD->getName()|escape:'html'}</h1>
				<div class="mk-so-tools-hero__meta">
					{if $MK_STATUS}
						<span class="mk-so-tools-status-pill">{$MK_STATUS|escape:'html'}</span>
					{/if}
					<span class="mk-so-tools-hero__owner">{$RECORD->getDisplayValue('created_user_id')}</span>
				</div>
			</div>
		</div>
		<div class="mk-so-tools-hero__actions">
			<a href="index.php?module=SalesOrder&amp;view=Edit&amp;record={$RECORD->getId()}&amp;app=TOOLS" class="mk-so-tools-btn mk-so-tools-btn--primary">
				<i class="fa fa-pencil" aria-hidden="true"></i>
				<span>{vtranslate('LBL_EDIT', 'Vtiger')}</span>
			</a>
		</div>
	</div>
</div>
{/strip}
