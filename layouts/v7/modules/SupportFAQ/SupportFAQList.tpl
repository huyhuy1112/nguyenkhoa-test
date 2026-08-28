{* SupportFAQ list → Cảnh báo hành động (Tag Rule Engine) *}
{strip}
<div class="helpdesk-rules-page mk-hd-rules mk-tag-rule-engine-mount mk-sf-alerts-mount">
	<div id="mk-tag-rule-alerts" class="mk-tag-rule-engine" aria-live="polite"></div>
</div>
{if isset($MK_TAG_RULE_BOOTSTRAP_JSON)}
<script type="text/javascript">
window.MK_TAG_RULE_STATE = {$MK_TAG_RULE_BOOTSTRAP_JSON nofilter};
</script>
{/if}
<script type="text/javascript">
(function () {
	function boot() {
		if (window.MkTagRuleAlerts && typeof window.MkTagRuleAlerts.init === 'function') {
			window.MkTagRuleAlerts.init();
		}
	}
	if (window.jQuery) {
		jQuery(boot);
		window.setTimeout(boot, 50);
		window.setTimeout(boot, 300);
	} else {
		document.addEventListener('DOMContentLoaded', boot);
	}
})();
</script>
{/strip}
