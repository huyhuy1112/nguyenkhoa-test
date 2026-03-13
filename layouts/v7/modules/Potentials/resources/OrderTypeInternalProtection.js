/*+***********************************************************************************
 * OrderType Internal Orders protection for Opportunities list view.
 * - Không sửa core files.
 * - JS chỉ load cho module Potentials (HEADERSCRIPT link).
 * - Khi filter hiện tại là "Internal Orders", hỏi password trước khi cho xem.
 *************************************************************************************/

(function () {
	if (typeof app === 'undefined' || typeof jQuery === 'undefined') return;

	jQuery(function () {
		try {
			var module = app.getModuleName ? app.getModuleName() : null;
			var view = app.getViewName ? app.getViewName() : null;

			if (module !== 'Potentials' || view !== 'List') {
				return;
			}

			var $filterSelect = jQuery('#customFilter');
			if (!$filterSelect.length) {
				return;
			}

			var currentText = jQuery.trim($filterSelect.find('option:selected').text() || '');
			if (!currentText) {
				return;
			}

			var cvidAllOrders = null;
			var cvidInternal = null;
			$filterSelect.find('option').each(function () {
				var $opt = jQuery(this);
				var txt = jQuery.trim($opt.text() || '');
				if (txt === 'All Orders') {
					cvidAllOrders = $opt.val();
				} else if (txt === 'Internal Orders') {
					cvidInternal = $opt.val();
				}
			});

			if (currentText !== 'Internal Orders') {
				return;
			}

			if (!cvidInternal) {
				return;
			}

			var SESSION_KEY = 'opportunities_internal_orders_ok';
			try {
				if (window.sessionStorage && window.sessionStorage.getItem(SESSION_KEY) === '1') {
					return;
				}
			} catch (e) {
			}

			var overlay = document.createElement('div');
			overlay.style.position = 'fixed';
			overlay.style.zIndex = 9999;
			overlay.style.left = 0;
			overlay.style.top = 0;
			overlay.style.right = 0;
			overlay.style.bottom = 0;
			overlay.style.background = 'rgba(255,255,255,0.95)';
			overlay.style.display = 'flex';
			overlay.style.alignItems = 'center';
			overlay.style.justifyContent = 'center';
			overlay.innerHTML = '' +
				'<div style="padding:20px 30px;border:1px solid #ddd;border-radius:4px;background:#fff;max-width:420px;width:90%;box-shadow:0 2px 10px rgba(0,0,0,.1);font-family:Arial, sans-serif;">' +
				'  <h4 style="margin-top:0;margin-bottom:10px;">Internal Orders</h4>' +
				'  <p style="margin-bottom:10px;">Please enter password to view Internal Orders.</p>' +
				'  <input type="password" id="internal-orders-password" class="input-sm form-control" style="width:100%;margin-bottom:10px;" />' +
				'  <div style="text-align:right;">' +
				'    <button type="button" class="btn btn-default btn-sm" id="internal-orders-cancel">Cancel</button> ' +
				'    <button type="button" class="btn btn-primary btn-sm" id="internal-orders-ok">OK</button>' +
				'  </div>' +
				'</div>';
			document.body.appendChild(overlay);

			function goToAllOrders() {
				if (!cvidAllOrders) {
					if ($filterSelect.length) {
						$filterSelect.val($filterSelect.find('option:first').val()).trigger('change');
					}
					return;
				}
				try {
					var url = new URL(window.location.href);
					url.searchParams.set('viewname', cvidAllOrders);
					window.location.href = url.toString();
				} catch (e) {
					var href = window.location.href;
					if (href.indexOf('viewname=') === -1) {
						href += (href.indexOf('?') === -1 ? '?' : '&') + 'viewname=' + encodeURIComponent(cvidAllOrders);
					} else {
						href = href.replace(/viewname=[^&]*/g, 'viewname=' + encodeURIComponent(cvidAllOrders));
					}
					window.location.href = href;
				}
			}

			function allowInternal() {
				try {
					if (window.sessionStorage) {
						window.sessionStorage.setItem(SESSION_KEY, '1');
					}
				} catch (e) {}
				if (overlay && overlay.parentNode) {
					overlay.parentNode.removeChild(overlay);
				}
			}

			jQuery(overlay).on('click', '#internal-orders-cancel', function (e) {
				e.preventDefault();
				goToAllOrders();
			});

			jQuery(overlay).on('click', '#internal-orders-ok', function (e) {
				e.preventDefault();
				var val = jQuery('#internal-orders-password').val() || '';
				if (val === 'internal123') {
					allowInternal();
				} else {
					alert('Incorrect password.');
					goToAllOrders();
				}
			});

			jQuery(overlay).on('keypress', '#internal-orders-password', function (e) {
				if (e.which === 13) {
					jQuery('#internal-orders-ok').click();
				}
			});

		} catch (err) {
			if (window && window.console && console.error) {
				console.error('OrderType Internal Protection error', err);
			}
		}
	});
})();

