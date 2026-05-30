{* Evaluate dashboard — breadcrumb + page title (Figma) *}
{strip}
<div class="mk-eval-header">
	<nav class="mk-eval-breadcrumb" aria-label="Breadcrumb">
		<ol class="mk-eval-breadcrumb__list">
			<li class="mk-eval-breadcrumb__item">
				<a href="index.php?module=Home&amp;view=DashBoard&amp;app=MARKETING">{vtranslate('LBL_HOME', 'Vtiger')}</a>
			</li>
			<li class="mk-eval-breadcrumb__sep" aria-hidden="true">&gt;</li>
			<li class="mk-eval-breadcrumb__item mk-eval-breadcrumb__item--current">
				<span>{vtranslate('Evaluate', 'Evaluate')}</span>
			</li>
		</ol>
	</nav>
	<header class="mk-eval-page-head" role="region" aria-label="{vtranslate('Evaluate', 'Evaluate')}">
		<div class="mk-eval-page-head__text">
			<h1 class="mk-eval-page-head__title">{vtranslate('Evaluate', 'Evaluate')}</h1>
			<p class="mk-eval-page-head__subtitle">Campaign performance — compare cost, revenue, and ROI</p>
		</div>
	</header>
</div>
{/strip}
