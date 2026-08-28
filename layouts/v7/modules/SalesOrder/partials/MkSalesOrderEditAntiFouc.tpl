{* Anti-FOUC: hide SALES SalesOrder edit workspace until theme + module CSS are applied. *}
{strip}
<script type="text/javascript">document.documentElement.classList.add('mk-so-create-guard');</script>
<style type="text/css">
/* Some entry points may miss data-app at first paint; guard by module+view only. */
html.mk-so-create-guard:not(.mk-so-create-styled) body[data-module="SalesOrder"][data-view="Edit"] #mk-dash-split-root,
html.mk-so-create-guard:not(.mk-so-create-styled) body[data-module="SalesOrder"][data-view="Edit"] #mkSoCreateWorkspace {
	visibility: hidden !important;
}
html.mk-so-create-guard.mk-so-create-styled body[data-module="SalesOrder"][data-view="Edit"] #mk-dash-split-root,
html.mk-so-create-guard.mk-so-create-styled body[data-module="SalesOrder"][data-view="Edit"] #mkSoCreateWorkspace {
	visibility: visible !important;
}
</style>
{/strip}
