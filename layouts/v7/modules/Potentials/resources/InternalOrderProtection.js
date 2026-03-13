/*+***********************************************************************************
 * Internal Order protection for Potentials List View.
 *
 * Behavior:
 * - Potentials list shows ALL records as usual.
 * - When user clicks record link:
 *    - If Order Category == "Internal" -> ask password (verified via PHP action)
 *    - Else -> allow navigation
 *
 * IMPORTANT:
 * - Password must not be present in JS.
 *************************************************************************************/

(function () {
	if (typeof jQuery === 'undefined' || typeof app === 'undefined') return;

	console.log('InternalOrderProtection loaded');

	function showInternalPasswordModal(recordUrl) {
		if (jQuery('#internalOrderModal').length) {
			jQuery('#internalOrderModal').remove();
		}

		var modal = ''
			+ '<div id="internalOrderModal" style="'
			+ 'position:fixed;top:0;left:0;width:100%;height:100%;'
			+ 'background:rgba(0,0,0,0.4);display:flex;align-items:center;justify-content:center;z-index:9999;">'
			+ '  <div style="background:white;padding:25px;border-radius:8px;width:320px;text-align:center;box-shadow:0 10px 25px rgba(0,0,0,0.2);">'
			+ '    <h3>Internal Order Access</h3>'
			+ '    <input type="password" id="internalPassword"'
			+ '           placeholder="Enter password"'
			+ '           style="width:100%;padding:8px;margin-top:10px;">'
			+ '    <div style="margin-top:15px;">'
			+ '      <button id="internalSubmit">Submit</button>'
			+ '      <button id="internalCancel">Cancel</button>'
			+ '    </div>'
			+ '    <div id="internalError"'
			+ '         style="color:red;margin-top:10px;display:none;">'
			+ '         Wrong password'
			+ '    </div>'
			+ '  </div>'
			+ '</div>';

		jQuery('body').append(modal);

		jQuery('#internalCancel').click(function () {
			jQuery('#internalOrderModal').remove();
		});

		jQuery('#internalSubmit').click(function () {
			var password = jQuery('#internalPassword').val();

			var data = {
				module: 'Potentials',
				action: 'VerifyInternalOrder',
				password: password
			};

			if (typeof csrfMagicName !== 'undefined' && typeof csrfMagicToken !== 'undefined') {
				data[csrfMagicName] = csrfMagicToken;
			}

			jQuery.post(
				'index.php?module=Potentials&action=VerifyInternalOrder',
				data,
				function (res) {
					if (res && res.success) {
						window.location.href = recordUrl;
					} else {
						jQuery('#internalError').show();
					}
				},
				'json'
			);
		});
	}

	jQuery(function () {
		try {
			var module = app.getModuleName ? app.getModuleName() : null;
			var view = app.getViewName ? app.getViewName() : null;
			if (module !== 'Potentials' || view !== 'List') return;

			// Dynamically detect column index for "Order Category"
			var categoryIndex = -1;
			jQuery('table.listViewEntriesTable thead th').each(function (i) {
				var text = jQuery(this).text().trim().toLowerCase();
				if (text.indexOf('order') !== -1 && text.indexOf('category') !== -1) {
					categoryIndex = i;
				}
			});
			console.log('Order Category column:', categoryIndex);

			// Intercept record link click using delegation and stopImmediatePropagation
			jQuery(document).on(
				'click',
				'table.listViewEntriesTable tbody tr a[href*="view=Detail"]',
				function (e) {
					if (categoryIndex === -1) return;

					var link = jQuery(this);
					var row  = link.closest('tr');
					var cells = row.find('td');
					if (cells.length <= categoryIndex) return;

					var category = jQuery.trim(cells.eq(categoryIndex).text() || '').toLowerCase();
					console.log('Row category:', category);

					if (category !== 'internal') {
						return; // project or others
					}

					e.preventDefault();
					e.stopImmediatePropagation();

					var recordUrl = link.attr('href');
					if (!recordUrl) return;

					showInternalPasswordModal(recordUrl);
				}
			);
		} catch (e) {
			// Never break list view because of JS error
			if (window && window.console && console.error) {
				console.error('InternalOrderProtection error', e);
			}
		}
	});
})();

