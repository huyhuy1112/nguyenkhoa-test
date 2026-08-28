{* Anti-FOUC: hide SALES SalesOrder edit until Quote/Inventory shell is ready. *}
{strip}
<script type="text/javascript">document.documentElement.classList.add('mk-so-create-guard');</script>
<style type="text/css">
html.mk-so-create-guard:not(.mk-so-create-styled):not(.mk-inv-ui-ready) #mk-dash-split-root,
html.mk-so-create-guard:not(.mk-so-create-styled):not(.mk-inv-ui-ready) #mkSoCreateWorkspace,
html.mk-so-create-guard:not(.mk-so-create-styled):not(.mk-inv-ui-ready) .editViewPageDiv {
	opacity: 0 !important;
	visibility: hidden !important;
	pointer-events: none !important;
}
html.mk-so-create-guard.mk-so-create-styled #mk-dash-split-root,
html.mk-so-create-guard.mk-so-create-styled #mkSoCreateWorkspace,
html.mk-so-create-guard.mk-so-create-styled .editViewPageDiv,
html.mk-so-create-guard.mk-inv-ui-ready #mk-dash-split-root,
html.mk-so-create-guard.mk-inv-ui-ready #mkSoCreateWorkspace,
html.mk-so-create-guard.mk-inv-ui-ready .editViewPageDiv {
	visibility: visible !important;
	opacity: 1 !important;
	pointer-events: auto !important;
	transition: none !important;
}
</style>
{/strip}
