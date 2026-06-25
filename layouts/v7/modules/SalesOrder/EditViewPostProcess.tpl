			</div>
		</div>
		</main>
	</div>
</div>
{include file="partials/MkThemeStylesLast.tpl"|vtemplate_path:'Vtiger'}
<script type="text/javascript">
(function () {
	function revealSalesOrderEdit() {
		var body = document.body;
		if (!body || body.getAttribute('data-module') !== 'SalesOrder' || body.getAttribute('data-view') !== 'Edit') {
			return;
		}
		var app = (body.getAttribute('data-app') || '').toUpperCase();
		if (app !== 'SALES' && app !== '') {
			return;
		}
		document.documentElement.classList.add('mk-so-create-styled');
	}
	function scheduleReveal() {
		requestAnimationFrame(function () {
			requestAnimationFrame(revealSalesOrderEdit);
		});
	}
	if (document.readyState === 'complete') {
		scheduleReveal();
	} else {
		window.addEventListener('load', scheduleReveal, { once: true });
	}
})();
</script>
