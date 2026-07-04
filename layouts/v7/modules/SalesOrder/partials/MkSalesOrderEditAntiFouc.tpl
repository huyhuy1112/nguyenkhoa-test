{* Anti-FOUC: hide SALES SalesOrder edit workspace until theme + module CSS are fully applied. *}
{strip}
<script type="text/javascript">document.documentElement.classList.add('mk-so-create-guard');</script>
<style type="text/css">
html.mk-so-create-guard:not(.mk-so-create-styled) #mk-dash-split-root,
html.mk-so-create-guard:not(.mk-so-create-styled) #mkSoCreateWorkspace,
html.mk-so-create-guard:not(.mk-so-create-styled) .editViewPageDiv {
	opacity: 0 !important;
	visibility: hidden !important;
	pointer-events: none !important;
}
html.mk-so-create-guard.mk-so-create-styled #mk-dash-split-root,
html.mk-so-create-guard.mk-so-create-styled #mkSoCreateWorkspace,
html.mk-so-create-guard.mk-so-create-styled .editViewPageDiv {
	visibility: visible !important;
	opacity: 1 !important;
	pointer-events: auto !important;
	transition: opacity 0.2s ease-out !important;
}
</style>
{/strip}
