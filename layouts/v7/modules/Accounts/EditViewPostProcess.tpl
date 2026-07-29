			</div>
		</div>
		</main>
	</div>
</div>
<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Accounts/resources/AccountMkEdit.js')}?mk_v=20260729_ac_sc_search2"></script>
<script type="text/javascript">
(function () {
	function revealAccountsCreate() {
		var body = document.body;
		if (!body || body.getAttribute('data-module') !== 'Accounts' || body.getAttribute('data-view') !== 'Edit') {
			return;
		}
		document.documentElement.classList.add('mk-ac-create-ready');
	}
	function scheduleReveal() {
		requestAnimationFrame(function () {
			requestAnimationFrame(revealAccountsCreate);
		});
	}
	if (document.readyState === 'complete') {
		scheduleReveal();
	} else {
		window.addEventListener('load', scheduleReveal, { once: true });
	}
})();
</script>
{include file="partials/MkThemeStylesLast.tpl"|vtemplate_path:'Vtiger'}
