{* Anti-FOUC: hide SALES SalesOrder edit workspace until theme + module CSS are applied. *}
{strip}
<script type="text/javascript">document.documentElement.classList.add('mk-so-create-guard');</script>
<style type="text/css">
html.mk-so-create-guard:not(.mk-so-create-styled) body[data-module="SalesOrder"][data-view="Edit"][data-app="SALES"] #mk-dash-split-root,
html.mk-so-create-guard:not(.mk-so-create-styled) body[data-module="SalesOrder"][data-view="Edit"][data-app="SALES"] #mkSoCreateWorkspace {
	visibility: hidden !important;
}
html.mk-so-create-guard.mk-so-create-styled body[data-module="SalesOrder"][data-view="Edit"][data-app="SALES"] #mk-dash-split-root,
html.mk-so-create-guard.mk-so-create-styled body[data-module="SalesOrder"][data-view="Edit"][data-app="SALES"] #mkSoCreateWorkspace {
	visibility: visible !important;
}
</style>
{/strip}
