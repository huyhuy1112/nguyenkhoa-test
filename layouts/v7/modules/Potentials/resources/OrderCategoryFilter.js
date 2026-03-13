/*+***********************************************************************************
 * Order Category filter buttons for Potentials List View.
 *
 * Adds:
 * - "Project" button: shows rows where Order Category is "Project" or empty
 * - "Internal" button: asks password, then shows only rows where Order Category is "Internal"
 *
 * IMPORTANT:
 * - Pure client-side filtering, does not change server queries.
 *************************************************************************************/

(function () {
	if (typeof jQuery === 'undefined' || typeof app === 'undefined') return;

	console.log('OrderCategoryFilter loaded');

	function getListTable() {
		var $table = jQuery('table.listViewEntriesTable').first();
		if (!$table.length) {
			$table = jQuery('table.listview-table').first();
		}
		if (!$table.length) {
			$table = jQuery('table').filter(function () {
				return jQuery(this).find('a.listViewEntryValue, a[href*="view=Detail"]').length > 0;
			}).first();
		}
		return $table;
	}

	function ensureButtons(container) {
		if (!container || !container.length) {
			console.log('OrderCategoryFilter: no container found for buttons');
			return;
		}

		if (container.find('#filterProjectBtn').length || container.find('#filterInternalBtn').length) {
			console.log('OrderCategoryFilter: buttons already present');
			return;
		}

		var projectBtn =
			'<button id="filterProjectBtn" class="btn btn-default" type="button" style="margin-left:4px;">Project</button>';
		var internalBtn =
			'<button id="filterInternalBtn" class="btn btn-danger" type="button" style="margin-left:4px;">Internal</button>';

		container.append(projectBtn + internalBtn);
	}

	function findOrderCategoryIndex() {
		// Detect column index from data rows instead of headers,
		// by looking for cells that contain "internal" or "project".
		var $table = getListTable();

		console.log(
			'OrderCategoryFilter: table(found via tbody) =',
			$table.length,
			$table.length ? ($table.attr('class') || '') : ''
		);

		var index = -1;
		var $rows = $table.find('tbody tr');
		console.log('OrderCategoryFilter: row count =', $rows.length);

		$rows.each(function () {
			var $cells = jQuery(this).find('td');
			$cells.each(function (i) {
				var text = jQuery(this).text().trim().toLowerCase();
				if (text === 'internal' || text === 'project') {
					index = i;
					console.log('OrderCategoryFilter: detected category column at index', index, 'with value', text);
					return false; // break cells loop
				}
			});
			if (index !== -1) {
				return false; // break rows loop
			}
		});

		console.log('Order Category filter column index (from rows):', index);
		return index;
	}

	function filterRowsForProject(categoryIndex) {
		var $table = getListTable();
		var $rows = $table.find('tbody tr');
		console.log('OrderCategoryFilter: filtering Project, rows =', $rows.length, 'categoryIndex =', categoryIndex);

		$rows.each(function () {
			var $row   = jQuery(this);
			var cells  = $row.find('td');
			if (cells.length <= categoryIndex) {
				$row.show();
				return;
			}
			var value = jQuery.trim(cells.eq(categoryIndex).text() || '').toLowerCase();
			if (value === '' || value === 'project') {
				$row.show();
			} else {
				$row.hide();
			}
		});
	}

	function filterRowsForInternal(categoryIndex) {
		var $table = getListTable();
		var $rows = $table.find('tbody tr');
		console.log('OrderCategoryFilter: filtering Internal, rows =', $rows.length, 'categoryIndex =', categoryIndex);

		$rows.each(function () {
			var $row   = jQuery(this);
			var cells  = $row.find('td');
			if (cells.length <= categoryIndex) {
				$row.hide();
				return;
			}
			var value = jQuery.trim(cells.eq(categoryIndex).text() || '').toLowerCase();
			if (value === 'internal') {
				$row.show();
			} else {
				$row.hide();
			}
		});
	}

	function showPasswordModal(onSuccess) {
		if (jQuery('#orderCategoryPasswordModal').length) {
			jQuery('#orderCategoryPasswordModal').remove();
		}

		var modal =
			'<div id="orderCategoryPasswordModal" style="'
			+ 'position:fixed;top:0;left:0;width:100%;height:100%;'
			+ 'background:rgba(0,0,0,0.4);display:flex;align-items:center;justify-content:center;z-index:9999;">'
			+ '  <div style="background:white;padding:25px;border-radius:8px;width:320px;text-align:center;box-shadow:0 10px 25px rgba(0,0,0,0.2);">'
			+ '    <h3>Internal Orders Access</h3>'
			+ '    <input type="password" id="orderCategoryPasswordInput"'
			+ '           placeholder="Enter password"'
			+ '           style="width:100%;padding:8px;margin-top:10px;">'
			+ '    <div style="margin-top:15px;">'
			+ '      <button id="orderCategoryPasswordSubmit">Submit</button>'
			+ '      <button id="orderCategoryPasswordCancel">Cancel</button>'
			+ '    </div>'
			+ '    <div id="orderCategoryPasswordError"'
			+ '         style="color:red;margin-top:10px;display:none;">'
			+ '         Wrong password'
			+ '    </div>'
			+ '  </div>'
			+ '</div>';

		jQuery('body').append(modal);

		jQuery('#orderCategoryPasswordCancel').click(function () {
			jQuery('#orderCategoryPasswordModal').remove();
		});

		jQuery('#orderCategoryPasswordSubmit').click(function () {
			var pwd = jQuery('#orderCategoryPasswordInput').val();
			if (pwd === 'TDB2026') {
				jQuery('#orderCategoryPasswordModal').remove();
				if (typeof onSuccess === 'function') {
					onSuccess();
				}
			} else {
				jQuery('#orderCategoryPasswordError').show();
			}
		});
	}

	function initOrderCategoryFilter(attempt) {
		attempt = attempt || 0;
		if (attempt > 10) {
			console.log('OrderCategoryFilter: giving up after attempts =', attempt);
			return;
		}

		try {
			var module = app.getModuleName ? app.getModuleName() : null;
			var view = app.getViewName ? app.getViewName() : null;
			if (module !== 'Potentials' || view !== 'List') return;

			var container = jQuery('.listViewActions').first();

			if (!container.length) {
				console.log(
					'OrderCategoryFilter: waiting for DOM. attempt=',
					attempt,
					'containerLen=',
					container.length
				);
				setTimeout(function () {
					initOrderCategoryFilter(attempt + 1);
				}, 400);
				return;
			}

			console.log('OrderCategoryFilter: container length =', container.length);
			ensureButtons(container);

			// Apply default Project filter on initial load
			var initialIndex = findOrderCategoryIndex();
			if (initialIndex !== -1) {
				console.log('OrderCategoryFilter: applying default Project filter with index', initialIndex);
				filterRowsForProject(initialIndex);
			}

			// Bind handlers – they will compute column index and table state at click time
			jQuery('#filterProjectBtn').off('click.orderCategory').on('click.orderCategory', function () {
				console.log('OrderCategoryFilter: Project button clicked');
				var categoryIndex = findOrderCategoryIndex();
				if (categoryIndex === -1) {
					console.log('OrderCategoryFilter: no Order Category column for Project filter');
					return;
				}
				filterRowsForProject(categoryIndex);
			});

			jQuery('#filterInternalBtn').off('click.orderCategory').on('click.orderCategory', function () {
				console.log('OrderCategoryFilter: Internal button clicked');
				var categoryIndex = findOrderCategoryIndex();
				if (categoryIndex === -1) {
					console.log('OrderCategoryFilter: no Order Category column for Internal filter');
					return;
				}
				showPasswordModal(function () {
					filterRowsForInternal(categoryIndex);
				});
			});
		} catch (e) {
			if (window && window.console && console.error) {
				console.error('OrderCategoryFilter error', e);
			}
		}
	}

	jQuery(function () {
		initOrderCategoryFilter(0);
	});
})();

