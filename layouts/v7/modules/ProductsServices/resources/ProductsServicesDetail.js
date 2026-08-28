/**
 * ProductsServices Detail — modern “Chi tiết” tab polish (tile fields, hide empty blocks).
 */
(function () {
	'use strict';

	function isPsDetail() {
		var b = document.body;
		if (!b) return false;
		var m = b.getAttribute('data-module');
		var v = b.getAttribute('data-view');
		var a = (b.getAttribute('data-app') || '').toUpperCase();
		return m === 'ProductsServices' && v === 'Detail' && (a === 'SALES' || a === 'INVENTORY');
	}

	function textOf(el) {
		if (!el) return '';
		return String(el.textContent || '')
			.replace(/\u00a0/g, ' ')
			.replace(/\s+/g, ' ')
			.trim();
	}

	function isEmptyValue(valEl) {
		if (!valEl) return true;
		var raw = textOf(valEl);
		if (!raw || raw === '—' || raw === '-' || raw === 'N/A' || raw === 'n/a') return true;
		// still has edit pencil only
		var clone = valEl.cloneNode(true);
		var actions = clone.querySelectorAll('.action, .hide, .edit, input, select, textarea, .editAction');
		for (var i = 0; i < actions.length; i++) {
			if (actions[i].parentNode) actions[i].parentNode.removeChild(actions[i]);
		}
		raw = textOf(clone);
		return !raw || raw === '—' || raw === '-';
	}

	function polishMoney(root) {
		if (!root) return;
		var nodes = root.querySelectorAll(
			'.fieldValue .value, .fieldValue span.value, td.fieldValue, .mk-ps-v2-df-tile__value .value, .mk-ps-v2-df-tile__value'
		);
		for (var i = 0; i < nodes.length; i++) {
			var el = nodes[i];
			// Only rewrite leafish value spans
			if (el.querySelector('input, select, textarea, img, table')) continue;
			// Prefer .value children over the wrapper (avoid double-processing)
			if (el.classList && el.classList.contains('mk-ps-v2-df-tile__value') && el.querySelector('.value')) {
				continue;
			}
			var t = el.innerHTML;
			if (!t || t.indexOf('$') === -1) continue;
			el.innerHTML = t
				.split('$')
				.join('₫')
				.split('USD')
				.join('₫')
				.split('US$')
				.join('₫');
		}
	}

	function tileifyBlock(block) {
		if (!block || block.getAttribute('data-mk-ps-tiled') === '1') return;
		var table = block.querySelector('table.detailview-table');
		if (!table) return;
		var tbody = table.tBodies && table.tBodies[0];
		if (!tbody) return;
		if (tbody.getAttribute('data-mk-ps-tiled') === '1') {
			block.setAttribute('data-mk-ps-tiled', '1');
			return;
		}

		var tiles = document.createElement('div');
		tiles.className = 'mk-ps-v2-df-grid';
		var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr'));
		var pairCount = 0;

		rows.forEach(function (tr) {
			var cells = Array.prototype.slice.call(tr.children);
			for (var i = 0; i < cells.length; i++) {
				var lab = cells[i];
				if (!lab || !lab.classList || !lab.classList.contains('fieldLabel')) continue;
				var val = cells[i + 1];
				if (!val || !val.classList || !val.classList.contains('fieldValue')) continue;
				i++;

				var tile = document.createElement('div');
				tile.className = 'mk-ps-v2-df-tile';
				if (isEmptyValue(val)) {
					tile.className += ' is-empty';
				}

				var labWrap = document.createElement('div');
				labWrap.className = 'mk-ps-v2-df-tile__label';
				labWrap.innerHTML = lab.innerHTML;

				var valWrap = document.createElement('div');
				valWrap.className = 'mk-ps-v2-df-tile__value';
				valWrap.innerHTML = val.innerHTML;

				tile.appendChild(labWrap);
				tile.appendChild(valWrap);
				tiles.appendChild(tile);
				pairCount++;
			}
		});

		if (!pairCount) return;

		// Keep grid inside tbody so Vtiger blockToggle (show/hide) still works
		while (tbody.firstChild) {
			tbody.removeChild(tbody.firstChild);
		}
		var tr = document.createElement('tr');
		var td = document.createElement('td');
		td.colSpan = 4;
		td.className = 'mk-ps-v2-df-cell';
		td.appendChild(tiles);
		tr.appendChild(td);
		tbody.appendChild(tr);
		tbody.setAttribute('data-mk-ps-tiled', '1');
		table.classList.add('mk-ps-v2-df-table');
		block.setAttribute('data-mk-ps-tiled', '1');
	}

	function restyleBlockHeader(block) {
		if (!block || block.getAttribute('data-mk-ps-head') === '1') return;
		var h4 = block.querySelector('h4');
		if (!h4) return;

		// Already polished
		if (h4.closest('.mk-ps-v2-df-head')) {
			block.setAttribute('data-mk-ps-head', '1');
			return;
		}

		var head = document.createElement('div');
		head.className = 'mk-ps-v2-df-head';
		var accent = document.createElement('span');
		accent.className = 'mk-ps-v2-df-head__accent';
		accent.setAttribute('aria-hidden', 'true');

		var title = document.createElement('div');
		title.className = 'mk-ps-v2-df-head__title';
		// strip arrow imgs from visual title but keep them for toggle handlers
		var imgs = h4.querySelectorAll('img.blockToggle');
		var toggles = document.createElement('span');
		toggles.className = 'mk-ps-v2-df-head__toggles';
		for (var i = 0; i < imgs.length; i++) {
			toggles.appendChild(imgs[i]);
		}
		title.appendChild(document.createTextNode(textOf(h4)));

		head.appendChild(accent);
		head.appendChild(title);
		head.appendChild(toggles);

		var h4Parent = h4.parentNode;
		if (h4Parent) {
			h4Parent.insertBefore(head, h4);
			h4.style.display = 'none';
			// remove legacy hr after header
			var sib = head.nextSibling;
			while (sib) {
				if (sib.nodeType === 1 && (sib.tagName === 'HR' || sib.tagName === 'hr')) {
					var n = sib.nextSibling;
					sib.parentNode.removeChild(sib);
					sib = n;
					continue;
				}
				if (sib.nodeType === 1) break;
				sib = sib.nextSibling;
			}
		}
		block.setAttribute('data-mk-ps-head', '1');
	}

	function hideEmptyBlocks(root) {
		if (!root) return;
		var blocks = root.querySelectorAll('#detailView .block, form#detailView .block');
		for (var i = 0; i < blocks.length; i++) {
			var block = blocks[i];
			var tiles = block.querySelectorAll('.mk-ps-v2-df-tile');
			if (!tiles.length) continue;
			var anyFilled = false;
			for (var j = 0; j < tiles.length; j++) {
				if (!tiles[j].classList.contains('is-empty')) {
					anyFilled = true;
					break;
				}
			}
			if (!anyFilled) {
				block.classList.add('mk-ps-v2-df-block--empty');
				block.style.display = 'none';
			}
		}
	}

	function enhance(root) {
		if (!isPsDetail()) return;
		var scope = root || document;
		var form = scope.querySelector
			? scope.querySelector('form#detailView') || scope.querySelector('#detailView')
			: null;
		// When AJAX injects HTML as form itself
		if (!form && scope.nodeType === 1 && scope.id === 'detailView') {
			form = scope;
		}
		if (!form) {
			// Try from details row
			var row = document.querySelector('.mk-ps-detail-details-row');
			if (row) form = row.querySelector('form#detailView, #detailView');
		}
		if (!form) return;

		form.classList.add('mk-ps-v2-detail-form');
		var blocks = form.querySelectorAll('.block');
		for (var i = 0; i < blocks.length; i++) {
			restyleBlockHeader(blocks[i]);
			tileifyBlock(blocks[i]);
		}
		// Money after tileify so ₫ appears inside tiles
		polishMoney(form);
		hideEmptyBlocks(form);
	}

	function boot() {
		if (!isPsDetail()) return;
		enhance(document);
	}

	// Initial
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}

	// After AJAX tab load (Vtiger injects into .details)
	if (typeof jQuery !== 'undefined') {
		jQuery(document)
			.off('pjax:complete.mkPsDetail ajaxComplete.mkPsDetail')
			.on('ajaxComplete.mkPsDetail', function (_e, xhr, settings) {
				try {
					var url = (settings && settings.url) || '';
					if (
						url.indexOf('ProductsServices') !== -1 &&
						(url.indexOf('showDetailViewByMode') !== -1 ||
							url.indexOf('requestMode=full') !== -1 ||
							url.indexOf('requestMode=summary') !== -1)
					) {
						setTimeout(function () {
							enhance(document);
						}, 40);
					}
				} catch (err) {}
			});

		// MutationObserver for details container content swaps
		var row = document.querySelector('.mk-ps-detail-details-row');
		if (row && typeof MutationObserver !== 'undefined') {
			var mo = new MutationObserver(function () {
				setTimeout(function () {
					enhance(document);
				}, 30);
			});
			mo.observe(row, { childList: true, subtree: false });
		}
	}
})();
