{* Anti-FOUC for SALES dashboard list shell: hide #listViewContent until MkSalesListShared / module JS marks ready. *}
{strip}
<script type="text/javascript">document.documentElement.classList.add('mk-sales-list-guard');</script>
<style type="text/css">
html.mk-sales-list-guard:not(.mk-sales-list-ready) #listViewContent { visibility: hidden; }
html.mk-sales-list-guard.mk-sales-list-ready #listViewContent { visibility: visible; }
html.mk-sales-list-guard #listViewContent #scroller_wrapper.bottom-fixed-scroll,
html.mk-sales-list-guard #listViewContent .bottom-fixed-scroll {
	display: none !important;
	height: 0 !important;
	margin: 0 !important;
	padding: 0 !important;
	border: none !important;
	overflow: hidden !important;
	position: absolute !important;
	left: -9999px !important;
	width: 0 !important;
	pointer-events: none !important;
}
</style>
{/strip}
