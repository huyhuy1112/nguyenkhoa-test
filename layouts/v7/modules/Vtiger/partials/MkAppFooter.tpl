{*+**********************************************************************************
 * Unified app footer — single markup for Footer.tpl and in-shell footers (Project).
 ************************************************************************************}
<footer class="app-footer mk-app-footer{if isset($MK_APP_FOOTER_EXTRA_CLASS) && $MK_APP_FOOTER_EXTRA_CLASS} {$MK_APP_FOOTER_EXTRA_CLASS|escape}{/if}" role="contentinfo">
	<div class="mk-app-footer__accent" aria-hidden="true"></div>
	<div class="mk-app-footer__inner">
		<div class="mk-app-footer__brand">
			<span class="mk-app-footer__mark" aria-hidden="true"></span>
			<span class="mk-app-footer__logo">Nguyên Khoa</span>
		</div>
		<div class="mk-app-footer__meta">
			<span class="mk-app-footer__credit">Developed by <strong>TDB Solution</strong></span>
			<span class="mk-app-footer__dot" aria-hidden="true"></span>
			<span class="mk-app-footer__year">&copy; 2025</span>
		</div>
	</div>
</footer>
