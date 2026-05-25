{* Outbound list — breadcrumb + hero title + primary CTA *}
{strip}
<div class="mk-gi-header">
	<nav class="mk-gi-breadcrumb" aria-label="Breadcrumb">
		<ol class="mk-gi-breadcrumb__list">
			<li class="mk-gi-breadcrumb__item">
				<a href="index.php?module=Home&amp;view=DashBoard&amp;app=INVENTORY">Home</a>
			</li>
			<li class="mk-gi-breadcrumb__sep" aria-hidden="true">&gt;</li>
			<li class="mk-gi-breadcrumb__item mk-gi-breadcrumb__item--current">
				<span>Outbound</span>
			</li>
		</ol>
	</nav>
	<header class="mk-gi-action-header" role="region" aria-label="Outbound">
		<div class="mk-gi-action-header__brand">
			<span class="mk-gi-action-header__icon" aria-hidden="true">{include file="partials/GoodsIssueListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='OUTBOUND'}</span>
			<div class="mk-gi-action-header__text">
				<h1 class="mk-gi-action-header__title">Outbound</h1>
				<p class="mk-gi-action-header__subtitle">Outbound issues deduct from Storage. Editing applies deltas safely; deleting restores stock.</p>
			</div>
		</div>
		<div class="mk-gi-action-header__actions">
			<a class="mk-gi-btn mk-gi-btn--primary" href="index.php?module=GoodsIssue&amp;view=Edit&amp;app=INVENTORY">
				<span class="mk-gi-btn__ic" aria-hidden="true">{include file="partials/GoodsIssueListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='PLUS'}</span>
				<span class="mk-gi-btn__txt">New Outbound</span>
			</a>
		</div>
	</header>
</div>
{/strip}
