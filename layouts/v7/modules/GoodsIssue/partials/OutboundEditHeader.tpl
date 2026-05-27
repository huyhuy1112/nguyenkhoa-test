{strip}
<div class="mk-gi-header mk-go-edit-header">
	<nav class="mk-gi-breadcrumb" aria-label="Breadcrumb">
		<ol class="mk-gi-breadcrumb__list">
			<li class="mk-gi-breadcrumb__item">
				<a href="index.php?module=Home&amp;view=DashBoard&amp;app=INVENTORY">Home</a>
			</li>
			<li class="mk-gi-breadcrumb__sep" aria-hidden="true">&gt;</li>
			<li class="mk-gi-breadcrumb__item">
				<a href="index.php?module=GoodsIssue&amp;view=List&amp;app=INVENTORY">Outbound</a>
			</li>
			<li class="mk-gi-breadcrumb__sep" aria-hidden="true">&gt;</li>
			<li class="mk-gi-breadcrumb__item mk-gi-breadcrumb__item--current">
				<span>{if $MODE eq 'edit'}Edit{else}New{/if}</span>
			</li>
		</ol>
	</nav>
	<header class="mk-gi-action-header" role="region" aria-label="Outbound form">
		<div class="mk-gi-action-header__brand">
			<span class="mk-gi-action-header__icon" aria-hidden="true">{include file="partials/GoodsIssueListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='OUTBOUND'}</span>
			<div class="mk-gi-action-header__text">
				<h1 class="mk-gi-action-header__title">{if $MODE eq 'edit'}Edit Outbound{else}Create Outbound{/if}</h1>
				<p class="mk-gi-action-header__subtitle">Outbound deducts stock from Storage. If stock is missing or insufficient, save will be blocked safely.</p>
				{if $ISSUE.code}
					<div class="mk-go-edit-header__code"><span class="mk-gi-chip">{$ISSUE.code|escape:'html'}</span></div>
				{/if}
			</div>
		</div>
		<div class="mk-gi-action-header__actions">
			<a class="mk-gi-btn mk-gi-btn--filter mk-gi-btn--ghost" href="index.php?module=GoodsIssue&amp;view=List&amp;app=INVENTORY">
				<span class="mk-gi-btn__txt">Back to list</span>
			</a>
		</div>
	</header>
</div>
{/strip}
