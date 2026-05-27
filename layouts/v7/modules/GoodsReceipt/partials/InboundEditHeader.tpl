{strip}
<div class="mk-gi-header mk-gr-edit-header">
	<nav class="mk-gi-breadcrumb" aria-label="Breadcrumb">
		<ol class="mk-gi-breadcrumb__list">
			<li class="mk-gi-breadcrumb__item">
				<a href="index.php?module=Home&amp;view=DashBoard&amp;app=INVENTORY">Home</a>
			</li>
			<li class="mk-gi-breadcrumb__sep" aria-hidden="true">&gt;</li>
			<li class="mk-gi-breadcrumb__item">
				<a href="index.php?module=GoodsReceipt&amp;view=List&amp;app=INVENTORY">Inbound</a>
			</li>
			<li class="mk-gi-breadcrumb__sep" aria-hidden="true">&gt;</li>
			<li class="mk-gi-breadcrumb__item mk-gi-breadcrumb__item--current">
				<span>{if $MODE eq 'edit'}Edit{else}New{/if}</span>
			</li>
		</ol>
	</nav>
	<header class="mk-gi-action-header" role="region" aria-label="Inbound form">
		<div class="mk-gi-action-header__brand">
			<span class="mk-gi-action-header__icon" aria-hidden="true">{include file="partials/GoodsReceiptListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='INBOUND'}</span>
			<div class="mk-gi-action-header__text">
				<h1 class="mk-gi-action-header__title">{if $MODE eq 'edit'}Edit Inbound{else}New Inbound{/if}</h1>
				<p class="mk-gi-action-header__subtitle">Inbound goods receipt workflow. Line items update Storage on save.</p>
				{if $RECORD.code}
					<div class="mk-gr-edit-header__code"><span class="mk-gi-chip">{$RECORD.code|escape:'html'}</span></div>
				{/if}
			</div>
		</div>
		<div class="mk-gi-action-header__actions">
			<a class="mk-gi-btn mk-gi-btn--filter mk-gi-btn--ghost" href="index.php?module=GoodsReceipt&amp;view=List&amp;app=INVENTORY">
				<span class="mk-gi-btn__txt">Back to list</span>
			</a>
		</div>
	</header>
</div>
{/strip}
