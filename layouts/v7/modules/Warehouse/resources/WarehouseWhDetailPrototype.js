/**
 * WhDetail Prototype Controller — UI + logic giống Prototype kho,
 * nhưng state lấy từ MkWarehouseStore (localStorage) theo warehouse_id.
 */
(function () {
	'use strict';

	var S = window.MkWarehouseStore;
	if (!S) return;

	function qs(sel, ctx) {
		return (ctx || document).querySelector(sel);
	}

	function qsa(sel, ctx) {
		return Array.prototype.slice.call((ctx || document).querySelectorAll(sel));
	}

	function escapeHtml(s) {
		return String(s || '')
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	function escText(s) {
		return escapeHtml(decodeEntities(s));
	}

	function formatSkuLabel(sku) {
		var s = decodeEntities(sku || '').trim();
		if (!s || /^PS-\d+$/i.test(s)) {
			return '—';
		}
		return s;
	}

	function showError(msg) {
		var text = String(msg || 'Đã xảy ra lỗi');
		if (typeof window !== 'undefined' && window.app && app.helper && app.helper.showErrorNotification) {
			app.helper.showErrorNotification({ message: text });
			return;
		}
		window.alert(text);
	}

	function decodeEntities(s) {
		var text = String(s || '');
		if (!text) {
			return '';
		}
		var el = document.createElement('textarea');
		var prev = null;
		var guard = 0;
		while (text !== prev && guard < 6) {
			prev = text;
			if (!/&(?:#x?[0-9a-f]+|[a-z]+);/i.test(text)) {
				break;
			}
			el.innerHTML = text;
			text = el.value;
			guard += 1;
		}
		return text;
	}

	function fmtDateTime(iso) {
		if (!iso) return '—';
		try {
			var d = new Date(iso);
			if (isNaN(d.getTime())) {
				d = new Date(String(iso).replace(' ', 'T'));
			}
			if (isNaN(d.getTime())) {
				return '—';
			}
			return d.toLocaleString('vi-VN', { dateStyle: 'short', timeStyle: 'short' });
		} catch (e) {
			return '—';
		}
	}

	function readQcNoteFromDialog(actionEl) {
		var root = (actionEl && actionEl.closest && actionEl.closest('#mkWhProtoDialog')) || qs('#mkWhProtoDialog');
		if (!root) return '';
		var cached = root.getAttribute('data-mk-qc-note-cache');
		if (cached) return String(cached).trim();
		var noteEl = root.querySelector('[data-mk-qc-note="1"]');
		return noteEl ? String(noteEl.value || '').trim() : '';
	}

	function bindQcNoteInput(root) {
		if (!root) return;
		var noteEl = root.querySelector('[data-mk-qc-note="1"]');
		if (!noteEl || noteEl.getAttribute('data-mk-qc-bound') === '1') return;
		noteEl.setAttribute('data-mk-qc-bound', '1');
		var sync = function () {
			root.setAttribute('data-mk-qc-note-cache', String(noteEl.value || ''));
		};
		noteEl.addEventListener('input', sync);
		noteEl.addEventListener('change', sync);
	}

	var QC_MAX_IMAGES = 10;

	function getQcImages(r) {
		var qc = (r && r.qc) || {};
		if (Array.isArray(qc.images) && qc.images.length) {
			return qc.images.slice();
		}
		var fromTimeline = [];
		(r.timeline || []).forEach(function (ev) {
			if (ev && ev.role === 'qc' && Array.isArray(ev.images) && ev.images.length) {
				fromTimeline = ev.images.slice();
			}
		});
		return fromTimeline;
	}

	function renderQcImagePreviewBtn(img, extraClass) {
		var url = (img && img.url) || '';
		var name = (img && img.name) || 'Ảnh QC';
		return '<button type="button" class="mk-wh-qc-preview-trigger' + (extraClass ? ' ' + extraClass : '') + '" data-mk-action="qc-image-preview" data-url="' + escapeHtml(url) + '" data-name="' + escapeHtml(name) + '">' +
			'<img src="' + escapeHtml(url) + '" alt="' + escapeHtml(name) + '" loading="lazy" /></button>';
	}

	function renderQcImagesGallery(receiptId, images, editable) {
		images = images || [];
		var count = images.length;
		var canUpload = editable && S.useDb && S.useDb();
		var html = '<div class="mk-wh-qc-attach" data-mk-qc-receipt-id="' + escapeHtml(receiptId) + '">';
		html += '<div class="mk-wh-qc-attach__head"><span class="mk-wh-qc-attach__title">Hình ảnh đính kèm</span>';
		html += '<span class="mk-wh-proto-muted mk-wh-qc-attach__count">' + count + '/' + QC_MAX_IMAGES + '</span></div>';
		html += '<div class="mk-wh-qc-attach__grid" data-mk-qc-attach-grid="1" data-mk-qc-images="' + escapeHtml(serializeQcImagesAttr(images)) + '">';
		images.forEach(function (img) {
			var url = img.url || '';
			var name = img.name || 'Ảnh QC';
			html += '<figure class="mk-wh-qc-attach__item" data-mk-qc-image-id="' + escapeHtml(img.id || '') + '">';
			html += renderQcImagePreviewBtn(img, 'mk-wh-qc-attach__link');
			if (canUpload) {
				html += '<button type="button" class="mk-wh-qc-attach__remove" data-mk-action="qc-image-delete" data-id="' + escapeHtml(receiptId) + '" data-image-id="' + escapeHtml(img.id || '') + '" title="Xóa ảnh">&times;</button>';
			}
			html += '</figure>';
		});
		html += '</div>';
		if (canUpload && count < QC_MAX_IMAGES) {
			html += '<label class="mk-wh-qc-attach__add"><input type="file" accept="image/jpeg,image/png,image/webp,image/gif" multiple data-mk-qc-file-input="1" hidden />';
			html += '<span>+ Thêm ảnh</span></label>';
		}
		if (editable && !(S.useDb && S.useDb())) {
			html += '<div class="mk-wh-proto-muted mk-wh-qc-attach__hint">Tải ảnh lên cần chế độ lưu database.</div>';
		}
		html += '</div>';
		return html;
	}

	function serializeQcImagesAttr(images) {
		try {
			return JSON.stringify((images || []).map(function (img) {
				return { url: (img && img.url) || '', name: (img && img.name) || 'Ảnh QC' };
			}));
		} catch (e) {
			return '[]';
		}
	}

	function parseQcImagesAttr(raw) {
		if (!raw) return [];
		try {
			var list = JSON.parse(raw);
			return Array.isArray(list) ? list : [];
		} catch (e) {
			return [];
		}
	}

	function renderTimelineImagesBlock(images) {
		if (!images || !images.length) return '';
		var attr = escapeHtml(serializeQcImagesAttr(images));
		var thumbs = images.slice(0, 4).map(function (img) {
			return renderQcImagePreviewBtn(img, 'mk-wh-qc-timeline-thumb');
		}).join('');
		var more = '';
		if (images.length > 4) {
			more = '<button type="button" class="mk-wh-qc-timeline-more-btn" data-mk-action="qc-image-preview" data-url="' + escapeHtml(images[4].url || '') + '" data-name="' + escapeHtml(images[4].name || 'Ảnh QC') + '">+' + (images.length - 4) + '</button>';
		}
		return '<div class="mk-wh-qc-timeline-images" data-mk-qc-images="' + attr + '" title="' + images.length + ' ảnh đính kèm">' + thumbs + more + '</div>';
	}

	var qcLightboxImages = [];
	var qcLightboxIndex = 0;
	var qcLightboxZoom = 1;
	var qcLightboxPan = { x: 0, y: 0 };
	var qcLightboxDrag = null;
	var qcLightboxLastPointer = null;

	function qcLightboxMarkup() {
		return '' +
			'<div class="mk-wh-qc-lightbox__backdrop" data-mk-action="qc-lightbox-close"></div>' +
			'<div class="mk-wh-qc-lightbox__panel" role="dialog" aria-modal="true" aria-label="Xem ảnh QC">' +
				'<div class="mk-wh-qc-lightbox__tools">' +
					'<div class="mk-wh-qc-lightbox__zoom-group" title="Kính lúp">' +
						'<button type="button" class="mk-wh-qc-lightbox__zoom-btn" data-mk-action="qc-lightbox-zoom-out" title="Thu nhỏ">−</button>' +
						'<span class="mk-wh-qc-lightbox__zoom-label" data-mk-action="qc-lightbox-zoom-reset" data-mk-qc-zoom-label="1" title="Về 100%">100%</span>' +
						'<button type="button" class="mk-wh-qc-lightbox__zoom-btn" data-mk-action="qc-lightbox-zoom-in" title="Phóng to">+</button>' +
					'</div>' +
					'<button type="button" class="mk-wh-qc-lightbox__close" data-mk-action="qc-lightbox-close" aria-label="Đóng">&times;</button>' +
				'</div>' +
				'<button type="button" class="mk-wh-qc-lightbox__nav mk-wh-qc-lightbox__nav--prev" data-mk-action="qc-lightbox-prev" aria-label="Ảnh trước">&#8249;</button>' +
				'<div class="mk-wh-qc-lightbox__stage" data-mk-qc-lightbox-stage="1">' +
					'<img class="mk-wh-qc-lightbox__img" alt="" />' +
				'</div>' +
				'<button type="button" class="mk-wh-qc-lightbox__nav mk-wh-qc-lightbox__nav--next" data-mk-action="qc-lightbox-next" aria-label="Ảnh sau">&#8250;</button>' +
				'<div class="mk-wh-qc-lightbox__caption"></div>' +
			'</div>';
	}

	function resetQcLightboxZoom() {
		qcLightboxZoom = 1;
		qcLightboxPan = { x: 0, y: 0 };
		qcLightboxDrag = null;
		applyQcLightboxTransform();
	}

	function applyQcLightboxTransform() {
		var lb = qs('#mkWhQcLightbox');
		if (!lb) return;
		var stage = lb.querySelector('[data-mk-qc-lightbox-stage="1"]');
		var imgEl = lb.querySelector('.mk-wh-qc-lightbox__img');
		var label = lb.querySelector('[data-mk-qc-zoom-label="1"]');
		if (label) label.textContent = Math.round(qcLightboxZoom * 100) + '%';
		if (stage) {
			stage.classList.toggle('is-zoomed', qcLightboxZoom > 1.01);
			stage.classList.toggle('is-dragging', !!(qcLightboxDrag && qcLightboxDrag.active));
		}
		if (imgEl) {
			imgEl.style.transform = 'translate(' + qcLightboxPan.x + 'px,' + qcLightboxPan.y + 'px) scale(' + qcLightboxZoom + ')';
		}
	}

	function setQcLightboxZoomAt(next, clientX, clientY) {
		var lb = qs('#mkWhQcLightbox');
		var stage = lb ? lb.querySelector('[data-mk-qc-lightbox-stage="1"]') : null;
		var oldZ = qcLightboxZoom || 1;
		var newZ = Math.max(1, Math.min(5, Number(next) || 1));
		newZ = Math.round(newZ * 100) / 100;
		if (Math.abs(newZ - oldZ) < 0.001) {
			applyQcLightboxTransform();
			return;
		}
		if (stage) {
			var rect = stage.getBoundingClientRect();
			var ax = (typeof clientX === 'number' && isFinite(clientX)) ? (clientX - rect.left) : (rect.width / 2);
			var ay = (typeof clientY === 'number' && isFinite(clientY)) ? (clientY - rect.top) : (rect.height / 2);
			var sx = ax - rect.width / 2;
			var sy = ay - rect.height / 2;
			qcLightboxPan = {
				x: sx - (sx - qcLightboxPan.x) * (newZ / oldZ),
				y: sy - (sy - qcLightboxPan.y) * (newZ / oldZ),
			};
		}
		qcLightboxZoom = newZ;
		if (qcLightboxZoom <= 1.01) {
			qcLightboxZoom = 1;
			qcLightboxPan = { x: 0, y: 0 };
		}
		applyQcLightboxTransform();
	}

	function setQcLightboxZoom(next) {
		var pt = qcLightboxLastPointer;
		if (pt) {
			setQcLightboxZoomAt(next, pt.x, pt.y);
		} else {
			setQcLightboxZoomAt(next);
		}
	}

	function updateQcLightboxView() {
		var lb = ensureQcLightboxEl();
		if (!lb) return;
		var imgEl = lb.querySelector('.mk-wh-qc-lightbox__img');
		var capEl = lb.querySelector('.mk-wh-qc-lightbox__caption');
		var prevBtn = lb.querySelector('[data-mk-action="qc-lightbox-prev"]');
		var nextBtn = lb.querySelector('[data-mk-action="qc-lightbox-next"]');
		var total = qcLightboxImages.length;
		if (!total || !imgEl) return;
		if (qcLightboxIndex < 0) qcLightboxIndex = 0;
		if (qcLightboxIndex >= total) qcLightboxIndex = total - 1;
		var cur = qcLightboxImages[qcLightboxIndex] || {};
		resetQcLightboxZoom();
		imgEl.src = cur.url || '';
		imgEl.alt = cur.name || 'Ảnh QC';
		if (capEl) {
			capEl.textContent = (cur.name || 'Ảnh QC') + (total > 1 ? ' (' + (qcLightboxIndex + 1) + '/' + total + ')' : '');
		}
		if (prevBtn) prevBtn.disabled = total <= 1;
		if (nextBtn) nextBtn.disabled = total <= 1;
	}

	function ensureQcLightboxEl() {
		var lb = qs('#mkWhQcLightbox');
		if (lb) {
			if (!lb.querySelector('.mk-wh-qc-lightbox__zoom-group')) {
				lb.innerHTML = qcLightboxMarkup();
				lb.removeAttribute('data-mk-qc-lb-bound');
				bindQcLightboxInteractions(lb);
			} else if (lb.getAttribute('data-mk-qc-lb-bound') !== '1') {
				bindQcLightboxInteractions(lb);
			}
			return lb;
		}
		lb = document.createElement('div');
		lb.id = 'mkWhQcLightbox';
		lb.className = 'mk-wh-qc-lightbox';
		lb.setAttribute('aria-hidden', 'true');
		lb.innerHTML = qcLightboxMarkup();
		document.body.appendChild(lb);
		bindQcLightboxInteractions(lb);
		return lb;
	}

	function bindQcLightboxInteractions(lb) {
		if (!lb || lb.getAttribute('data-mk-qc-lb-bound') === '1') return;
		lb.setAttribute('data-mk-qc-lb-bound', '1');
		var stage = lb.querySelector('[data-mk-qc-lightbox-stage="1"]');
		if (!stage) return;

		stage.addEventListener('pointermove', function (e) {
			qcLightboxLastPointer = { x: e.clientX, y: e.clientY };
			if (!qcLightboxDrag || !qcLightboxDrag.active) return;
			qcLightboxPan = {
				x: qcLightboxDrag.origX + (e.clientX - qcLightboxDrag.startX),
				y: qcLightboxDrag.origY + (e.clientY - qcLightboxDrag.startY),
			};
			applyQcLightboxTransform();
		});

		stage.addEventListener('wheel', function (e) {
			if (!lb.classList.contains('is-open')) return;
			e.preventDefault();
			qcLightboxLastPointer = { x: e.clientX, y: e.clientY };
			var delta = e.deltaY > 0 ? -0.2 : 0.2;
			setQcLightboxZoomAt(qcLightboxZoom + delta, e.clientX, e.clientY);
		}, { passive: false });

		stage.addEventListener('dblclick', function (e) {
			e.preventDefault();
			qcLightboxLastPointer = { x: e.clientX, y: e.clientY };
			if (qcLightboxZoom > 1.01) {
				setQcLightboxZoomAt(1, e.clientX, e.clientY);
			} else {
				setQcLightboxZoomAt(2, e.clientX, e.clientY);
			}
		});

		stage.addEventListener('click', function (e) {
			if (qcLightboxDrag && qcLightboxDrag.moved) return;
			if (qcLightboxZoom > 1.01) return;
			e.preventDefault();
			qcLightboxLastPointer = { x: e.clientX, y: e.clientY };
			setQcLightboxZoomAt(Math.min(qcLightboxZoom + 0.5, 3), e.clientX, e.clientY);
		});

		stage.addEventListener('pointerdown', function (e) {
			if (e.button !== 0) return;
			qcLightboxLastPointer = { x: e.clientX, y: e.clientY };
			if (qcLightboxZoom <= 1.01) return;
			qcLightboxDrag = {
				active: true,
				moved: false,
				startX: e.clientX,
				startY: e.clientY,
				origX: qcLightboxPan.x,
				origY: qcLightboxPan.y,
			};
			stage.classList.add('is-dragging');
			if (stage.setPointerCapture) {
				try { stage.setPointerCapture(e.pointerId); } catch (err) { /* ignore */ }
			}
		});

		var endDrag = function (e) {
			if (!qcLightboxDrag) return;
			if (e && typeof e.clientX === 'number') {
				var dx = Math.abs(e.clientX - qcLightboxDrag.startX);
				var dy = Math.abs(e.clientY - qcLightboxDrag.startY);
				if (dx > 4 || dy > 4) qcLightboxDrag.moved = true;
			}
			qcLightboxDrag.active = false;
			var moved = qcLightboxDrag.moved;
			setTimeout(function () { qcLightboxDrag = moved ? { moved: true } : null; }, 0);
			stage.classList.remove('is-dragging');
		};
		stage.addEventListener('pointerup', endDrag);
		stage.addEventListener('pointercancel', endDrag);
	}

	function openQcLightbox(images, startIndex) {
		qcLightboxImages = (images || []).filter(function (img) { return img && img.url; });
		if (!qcLightboxImages.length) return;
		qcLightboxIndex = typeof startIndex === 'number' ? startIndex : 0;
		var lb = ensureQcLightboxEl();
		if (!lb) return;
		updateQcLightboxView();
		lb.classList.add('is-open');
		lb.setAttribute('aria-hidden', 'false');
	}

	function closeQcLightbox() {
		var lb = qs('#mkWhQcLightbox');
		if (!lb) return;
		lb.classList.remove('is-open');
		lb.setAttribute('aria-hidden', 'true');
		resetQcLightboxZoom();
		var imgEl = lb.querySelector('.mk-wh-qc-lightbox__img');
		if (imgEl) imgEl.removeAttribute('src');
	}

	function resolveQcPreviewUrl(el) {
		if (!el) return '';
		if (el.getAttribute) {
			var dataUrl = el.getAttribute('data-url') || '';
			if (dataUrl) return dataUrl;
		}
		if (el.tagName === 'IMG' && el.getAttribute) {
			return el.getAttribute('src') || '';
		}
		if (el.tagName === 'A' && el.getAttribute) {
			return el.getAttribute('href') || '';
		}
		var img = el.querySelector ? el.querySelector('img') : null;
		if (img && img.getAttribute) return img.getAttribute('src') || '';
		return '';
	}

	function collectQcPreviewImagesFromContainer(container, clickedEl) {
		if (!container) return { images: [], index: 0 };
		var clickedUrl = resolveQcPreviewUrl(clickedEl);
		var attrImages = parseQcImagesAttr(container.getAttribute('data-mk-qc-images'));
		if (attrImages.length) {
			var index = 0;
			if (clickedUrl) {
				for (var i = 0; i < attrImages.length; i++) {
					if (attrImages[i].url === clickedUrl) {
						index = i;
						break;
					}
				}
			}
			return { images: attrImages, index: index };
		}
		var images = [];
		var index = 0;
		var nodes = container.querySelectorAll('[data-mk-action="qc-image-preview"], a.mk-wh-qc-timeline-thumb, a.mk-wh-qc-attach__link, .mk-wh-qc-timeline-thumb img, .mk-wh-qc-attach__item img');
		var seen = {};
		Array.prototype.forEach.call(nodes, function (el) {
			var u = resolveQcPreviewUrl(el);
			if (!u || seen[u]) return;
			seen[u] = true;
			images.push({
				url: u,
				name: (el.getAttribute && el.getAttribute('data-name')) || (el.getAttribute && el.getAttribute('alt')) || 'Ảnh QC',
			});
			if (el === clickedEl || (clickedUrl && u === clickedUrl)) index = images.length - 1;
		});
		if (!images.length && clickedUrl) {
			images = [{ url: clickedUrl, name: 'Ảnh QC' }];
			index = 0;
		}
		return { images: images, index: index };
	}

	function tryOpenQcImageFromEvent(e) {
		var t = e.target;
		if (!t || !t.closest) return false;

		var lightboxActionEl = t.closest('[data-mk-action="qc-lightbox-close"], [data-mk-action="qc-lightbox-prev"], [data-mk-action="qc-lightbox-next"], [data-mk-action="qc-lightbox-zoom-in"], [data-mk-action="qc-lightbox-zoom-out"], [data-mk-action="qc-lightbox-zoom-reset"]');
		if (lightboxActionEl) {
			e.preventDefault();
			e.stopPropagation();
			var lbAction = lightboxActionEl.getAttribute('data-mk-action');
			if (lbAction === 'qc-lightbox-close') {
				closeQcLightbox();
			} else if (lbAction === 'qc-lightbox-prev') {
				qcLightboxIndex -= 1;
				if (qcLightboxIndex < 0) qcLightboxIndex = Math.max(qcLightboxImages.length - 1, 0);
				updateQcLightboxView();
			} else if (lbAction === 'qc-lightbox-next') {
				qcLightboxIndex += 1;
				if (qcLightboxIndex >= qcLightboxImages.length) qcLightboxIndex = 0;
				updateQcLightboxView();
			} else if (lbAction === 'qc-lightbox-zoom-in') {
				setQcLightboxZoom(qcLightboxZoom + 0.25);
			} else if (lbAction === 'qc-lightbox-zoom-out') {
				setQcLightboxZoom(qcLightboxZoom - 0.25);
			} else if (lbAction === 'qc-lightbox-zoom-reset') {
				setQcLightboxZoomAt(1);
			}
			return true;
		}

		// Don't treat lightbox image clicks as "open another preview".
		if (t.closest && t.closest('#mkWhQcLightbox')) {
			return false;
		}

		var previewEl = t.closest('[data-mk-action="qc-image-preview"], .mk-wh-qc-timeline-thumb, .mk-wh-qc-attach__link, .mk-wh-qc-timeline-images a, .mk-wh-qc-attach a, .mk-wh-qc-attach__item img, .mk-wh-qc-timeline-images img');
		if (!previewEl) return false;

		var previewRoot = previewEl.closest('[data-mk-qc-images], .mk-wh-qc-attach__grid, .mk-wh-qc-timeline-images, .mk-wh-qc-attach, .mk-wh-proto-qc-result') || previewEl.parentElement;
		var pack = collectQcPreviewImagesFromContainer(previewRoot, previewEl);
		if (!pack.images.length) {
			var fallbackUrl = resolveQcPreviewUrl(previewEl);
			if (!fallbackUrl) return false;
			pack = { images: [{ url: fallbackUrl, name: 'Ảnh QC' }], index: 0 };
		}
		e.preventDefault();
		e.stopPropagation();
		if (typeof e.stopImmediatePropagation === 'function') e.stopImmediatePropagation();
		openQcLightbox(pack.images, pack.index);
		return true;
	}

	function defaultDialogFootHtml() {
		return '<button type="button" class="mk-wh-proto-mini-btn" data-mk-dialog-close="1">Đóng</button>';
	}

	function bindQcAttachmentHandlers(root) {
		if (!root) return;
		var attachRoot = root.querySelector('[data-mk-qc-receipt-id]');
		var receiptId = attachRoot ? attachRoot.getAttribute('data-mk-qc-receipt-id') : '';
		var fileInput = root.querySelector('[data-mk-qc-file-input="1"]');
		if (!fileInput || fileInput.getAttribute('data-mk-qc-file-bound') === '1') return;
		fileInput.setAttribute('data-mk-qc-file-bound', '1');
		fileInput.addEventListener('change', function () {
			if (!(S.useDb && S.useDb()) || !S.warehouseDataActions || typeof S.warehouseDataActions.uploadQcImage !== 'function') {
				showError('Tải ảnh lên cần chế độ lưu database.');
				fileInput.value = '';
				return;
			}
			var files = fileInput.files;
			if (!files || !files.length || !receiptId) return;
			var whId = getWhId();
			var role = getRole();
			var currentCount = attachRoot ? attachRoot.querySelectorAll('.mk-wh-qc-attach__item').length : 0;
			var queue = Array.prototype.slice.call(files);
			if (currentCount + queue.length > QC_MAX_IMAGES) {
				showError('Tối đa ' + QC_MAX_IMAGES + ' ảnh đính kèm.');
				fileInput.value = '';
				return;
			}
			var uploadNext = function () {
				if (!queue.length) {
					fileInput.value = '';
					return;
				}
				var file = queue.shift();
				S.warehouseDataActions.uploadQcImage(whId, receiptId, file, role)
					.then(function (res) {
						reopenReceiptDialog(whId, receiptId, res);
						uploadNext();
					})
					.fail(function (err) {
						showError((err && err.message) || 'Không upload được ảnh.');
						fileInput.value = '';
					});
			};
			uploadNext();
		});
	}

	function daysUntil(exp) {
		if (!exp) return 999999;
		try {
			return Math.round((new Date(exp).getTime() - Date.now()) / 86400000);
		} catch (e) {
			return 999999;
		}
	}

	function fmtPrice(n) {
		var v = Number(n);
		if (!isFinite(v) || v <= 0) return '—';
		return v.toLocaleString('vi-VN') + ' ₫';
	}

	function getWhId() {
		var root = qs('#mkWhDetailRoot');
		return root ? (root.getAttribute('data-wh-id') || '') : '';
	}

	function getWarehouse() {
		var id = getWhId();
		return (S.getState().warehouses || []).find(function (w) { return w.id === id; });
	}

	function getDestinationWarehouseOptions() {
		var currentId = getWhId();
		var opts = [{ value: '', label: '— Chọn kho đích —' }];
		(S.getState().warehouses || []).forEach(function (w) {
			if (!w || !w.id || w.id === currentId) return;
			if (w.status && String(w.status).toLowerCase() !== 'active') return;
			var label = decodeEntities(w.name || w.id);
			if (w.address) label += ' — ' + decodeEntities(w.address);
			opts.push({ value: String(w.id), label: label });
		});
		return opts;
	}

	function resolveWarehouseLabel(whId) {
		var w = (S.getState().warehouses || []).find(function (x) { return x && String(x.id) === String(whId); });
		return w ? decodeEntities(w.name || w.id) : String(whId || '');
	}

	/** Normalize date strings for <input type="date"> (YYYY-MM-DD). */
	function toDateInputValue(v) {
		var s = String(v || '').trim();
		if (!s || s === '—' || s === '0000-00-00') return '';
		var iso = s.match(/^(\d{4}-\d{2}-\d{2})/);
		if (iso) return iso[1];
		var dmy = s.match(/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})$/);
		if (dmy) {
			var dd = ('0' + dmy[1]).slice(-2);
			var mm = ('0' + dmy[2]).slice(-2);
			return dmy[3] + '-' + mm + '-' + dd;
		}
		var t = Date.parse(s);
		if (!isNaN(t)) {
			var d = new Date(t);
			var y = d.getFullYear();
			var m = ('0' + (d.getMonth() + 1)).slice(-2);
			var day = ('0' + d.getDate()).slice(-2);
			return y + '-' + m + '-' + day;
		}
		return '';
	}

	// Real CRM permissions from #mkWhDetailRoot data-* (no prototype role picker).
	function getAccess() {
		var root = qs('#mkWhDetailRoot');
		return {
			canWrite: !!(root && root.getAttribute('data-can-write') === '1'),
			canQc: !!(root && root.getAttribute('data-can-qc') === '1'),
			userName: root ? String(root.getAttribute('data-user-name') || '').trim() : '',
		};
	}

	/** API/timeline role key: manager (write/QC) | viewer */
	function getRole() {
		var a = getAccess();
		if (a.canWrite || a.canQc) return 'manager';
		return 'viewer';
	}

	function setRoleUI(roleKey) {
		/* Prototype role select removed — permissions come from CRM Profile. */
	}

	/** Ops formerly done by Thủ kho — now owned by warehouse write roles. */
	function isWarehouseOps(role) {
		if (getAccess().canWrite) return true;
		return role === 'manager' || role === 'keeper' || role === 'stock';
	}

	function canDoQc() {
		return getAccess().canQc || getAccess().canWrite;
	}

	function roleActorName(role) {
		var name = getAccess().userName;
		if (name) return name;
		if (role === 'qc') return 'QC';
		return 'Kho';
	}

	var RECEIPT_STATUS = {
		draft: { label: 'Nháp', cls: 'mk-wh-proto-pill mk-wh-proto-pill--draft' },
		pending_qc: { label: 'Chờ QC', cls: 'mk-wh-proto-pill mk-wh-proto-pill--warn' },
		qc_passed: { label: 'QC đạt', cls: 'mk-wh-proto-pill mk-wh-proto-pill--ok' },
		qc_failed: { label: 'QC không đạt', cls: 'mk-wh-proto-pill mk-wh-proto-pill--warn' },
		approved: { label: 'Đã duyệt', cls: 'mk-wh-proto-pill mk-wh-proto-pill--ok' },
		stored: { label: 'Đã nhập kho', cls: 'mk-wh-proto-pill mk-wh-proto-pill--ok' },
	};

	var ISSUE_STATUS = {
		draft: { label: 'Nháp', cls: 'mk-wh-proto-pill mk-wh-proto-pill--draft' },
		waiting_print: { label: 'Chờ soạn', cls: 'mk-wh-proto-pill mk-wh-proto-pill--issue-wait' },
		picking: { label: 'Đang soạn', cls: 'mk-wh-proto-pill mk-wh-proto-pill--issue-pick' },
		packed: { label: 'Đã soạn', cls: 'mk-wh-proto-pill mk-wh-proto-pill--issue-packed' },
		shipped: { label: 'Đã giao', cls: 'mk-wh-proto-pill mk-wh-proto-pill--ok' },
		rejected: { label: 'Từ chối', cls: 'mk-wh-proto-pill mk-wh-proto-pill--warn' },
		cancelled: { label: 'Đã huỷ', cls: 'mk-wh-proto-pill mk-wh-proto-pill--warn' },
		// legacy aliases
		pending_approval: { label: 'Chờ soạn', cls: 'mk-wh-proto-pill mk-wh-proto-pill--issue-wait' },
		approved: { label: 'Đã soạn', cls: 'mk-wh-proto-pill mk-wh-proto-pill--issue-packed' },
	};

	/** Happy-path steps shown on status chevron (xuất). */
	var ISSUE_PATH = [
		{ key: 'waiting_print', label: 'Chờ soạn' },
		{ key: 'picking', label: 'Đang soạn' },
		{ key: 'packed', label: 'Đã soạn' },
		{ key: 'shipped', label: 'Đã giao' },
	];

	/** Happy-path steps (nhập). */
	var RECEIPT_PATH = [
		{ key: 'draft', label: 'Nháp' },
		{ key: 'pending_qc', label: 'Chờ QC' },
		{ key: 'qc_passed', label: 'QC đạt' },
		{ key: 'approved', label: 'Đã duyệt' },
		{ key: 'stored', label: 'Đã nhập kho' },
	];

	function normalizeIssuePathStatus(status) {
		var s = String(status || '');
		if (s === 'pending_approval' || s === 'draft') return 'waiting_print';
		if (s === 'approved') return 'packed';
		return s;
	}

	function normalizeReceiptPathStatus(status) {
		var s = String(status || '');
		if (s === 'qc_failed') return 'pending_qc';
		return s;
	}

	function pathIndex(path, key) {
		for (var i = 0; i < path.length; i++) {
			if (path[i].key === key) return i;
		}
		return -1;
	}

	/**
	 * Chevron status path — click previous steps to revert (ops only).
	 */
	function renderStatusPath(opts) {
		opts = opts || {};
		var path = opts.path || [];
		var current = opts.current || '';
		var docId = opts.docId || '';
		var actionKey = opts.actionKey || '';
		var canRevert = !!opts.canRevert;
		var branchNote = opts.branchNote || '';
		var curIdx = pathIndex(path, current);
		if (curIdx < 0 && path.length) {
			curIdx = 0;
		}
		var html = '<div class="mk-wh-status-path" role="list" aria-label="Quy trình trạng thái">';
		path.forEach(function (step, idx) {
			var state = 'todo';
			if (idx < curIdx) state = 'done';
			else if (idx === curIdx) state = 'current';
			var clickable = canRevert && state === 'done';
			var cls = 'mk-wh-status-path__step mk-wh-status-path__step--' + state;
			if (clickable) cls += ' is-clickable';
			var attrs = ' role="listitem" class="' + cls + '"';
			if (clickable) {
				attrs +=
					' data-mk-action="' +
					escapeHtml(actionKey) +
					'" data-id="' +
					escapeHtml(docId) +
					'" data-target="' +
					escapeHtml(step.key) +
					'" title="Quay lại bước: ' +
					escapeHtml(step.label) +
					'"';
			}
			html +=
				'<button type="button"' +
				attrs +
				(clickable ? '' : ' disabled tabindex="-1"') +
				'>' +
				'<span class="mk-wh-status-path__label">' +
				escText(step.label) +
				'</span>' +
				'</button>';
		});
		html += '</div>';
		if (branchNote) {
			html += '<div class="mk-wh-status-path__branch">' + escText(branchNote) + '</div>';
		}
		if (canRevert && curIdx > 0) {
			html +=
				'<div class="mk-wh-status-path__hint">Ấn bước trước để quay lại (ghi vào lịch sử).</div>';
		}
		return html;
	}

	/** In phiếu xuất: mọi trạng thái đều in được (kể cả Đã soạn / Đã giao / Đã huỷ). */
	function canPrintOutboundIssue(status) {
		return true;
	}

	/** Huỷ xuất: Chờ in / Đang soạn (bắt đầu soạn) / Đã soạn — chưa giao. */
	function canCancelOutboundIssue(status) {
		var s = String(status || '');
		return (
			s === 'waiting_print' ||
			s === 'pending_approval' ||
			s === 'picking' ||
			s === 'packed' ||
			s === 'approved'
		);
	}

	var OUTBOUND_TYPES = {
		internal: {
			label: 'Xuất nội bộ',
			short: 'Xuất nội bộ',
			pillCls: 'mk-wh-proto-pill--out-internal',
			customerLabel: 'Bộ phận / mục đích',
			soLabel: 'Mã tham chiếu',
			soPlaceholder: 'IT-...',
		},
		transfer: {
			label: 'Xuất chuyển kho',
			short: 'Chuyển kho',
			pillCls: 'mk-wh-proto-pill--out-transfer',
			customerLabel: 'Kho đích',
			soLabel: 'Mã chuyển kho',
			soPlaceholder: 'TRF-...',
		},
		scrap: {
			label: 'Xuất huỷ',
			short: 'Xuất huỷ',
			pillCls: 'mk-wh-proto-pill--out-scrap',
			customerLabel: 'Khách hàng / đơn vị',
			soLabel: 'Mã phiếu / tham chiếu',
			soPlaceholder: 'SCR-...',
		},
		sale: {
			label: 'Xuất bán (từ invoice)',
			short: 'Xuất bán',
			pillCls: 'mk-wh-proto-pill--out-sale',
			customerLabel: 'Khách hàng',
			soLabel: 'Mã invoice / SO',
			soPlaceholder: 'INV-2026-...',
		},
	};

	var OUTBOUND_TYPE_PICKER = ['internal', 'transfer', 'scrap'];

	/** Loại xuất chi tiết trên phiếu xuất nội bộ (in ra cột Loại xuất). */
	var INTERNAL_EXPORT_TYPE_OPTIONS = [
		{ value: 'Xuất dùng nội bộ', label: 'Xuất dùng nội bộ', selected: true },
		{ value: 'Xuất phục vụ sự kiện, hội nghị nội bộ', label: 'Xuất phục vụ sự kiện, hội nghị nội bộ' },
		{ value: 'Xuất cho sản xuất / pha chế', label: 'Xuất cho sản xuất / pha chế' },
		{ value: 'Xuất kiểm nghiệm / mẫu', label: 'Xuất kiểm nghiệm / mẫu' },
		{ value: 'Xuất hao hụt / điều chỉnh', label: 'Xuất hao hụt / điều chỉnh' },
		{ value: 'Khác', label: 'Khác' },
	];

	function getOutboundTypeMeta(type) {
		return OUTBOUND_TYPES[type] || OUTBOUND_TYPES.internal;
	}

	function outboundTypePill(type) {
		var meta = getOutboundTypeMeta(type);
		var cls = meta.pillCls || 'mk-wh-proto-pill--out-internal';
		return '<span class="mk-wh-proto-pill ' + escapeHtml(cls) + '">' + escapeHtml(meta.short) + '</span>';
	}

	function issueStatusPill(status) {
		var st = ISSUE_STATUS[status] || ISSUE_STATUS.draft;
		return '<span class="' + escapeHtml(st.cls) + '">' + escapeHtml(st.label) + '</span>';
	}

	function findStockLot(whId, sku, lot) {
		var d = S.ensureData(whId);
		var wantSku = decodeEntities(sku || '').trim();
		var wantLot = decodeEntities(lot || '').replace(/^\s*lô\s+/i, '').trim();
		return (d.stock || []).find(function (s) {
			var sSku = decodeEntities(s.sku || '').trim();
			var sLot = decodeEntities(s.lot || '').replace(/^\s*lô\s+/i, '').trim();
			if (wantLot && sLot !== wantLot) return false;
			if (wantSku && sSku && sSku !== wantSku) return false;
			return !!(wantLot || (wantSku && sSku === wantSku));
		});
	}

	function nextId(prefix, existing) {
		var max = 0;
		(existing || []).forEach(function (x) {
			var id = String(x && (x.id || x.code) ? (x.id || x.code) : '');
			var m = new RegExp('^' + prefix + '-(\\d+)$').exec(id);
			if (m && m[1]) {
				var n = parseInt(m[1], 10);
				if (!isNaN(n) && n > max) max = n;
			}
		});
		return prefix + '-' + String(max + 1).padStart(4, '0');
	}

	function renderHeader() {
		var w = getWarehouse();
		var title = qs('#mkWhDetailTitle');
		var desc = qs('#mkWhDetailDesc');
		if (!w) {
			if (title) title.textContent = 'Không tìm thấy kho';
			if (desc) desc.textContent = 'Kho không tồn tại hoặc đã bị xóa.';
			return false;
		}
		if (title) title.textContent = decodeEntities(w.name);
		if (desc) desc.textContent = decodeEntities(w.code) + ' · ' + decodeEntities(w.address || '—') + ' · QL: ' + decodeEntities(w.manager || '—');
		return true;
	}

	function updateRoleBanner() {
		var role = getRole();
		var active = qs('.mk-wh-proto-tab.is-active');
		var tabKey = active ? active.getAttribute('data-tab') : 'inbound';
		var btn = qs('#mkWhProtoCreateBtn');
		var canCreate = isWarehouseOps(role) && (tabKey === 'inbound' || tabKey === 'outbound' || tabKey === 'returns');
		if (btn) {
			btn.classList.toggle('hide', !canCreate);
			btn.disabled = !canCreate;
			btn.textContent = tabKey === 'outbound' ? 'Tạo phiếu xuất' : (tabKey === 'returns' ? 'Tạo phiếu thu hồi' : 'Tạo phiếu nhập');
		}
	}

	function updateKpis() {
		var id = getWhId();
		if (!id) return;
		var d = S.ensureData(id);
		var pendingQc = (d.receipts || []).filter(function (r) { return r.status === 'pending_qc'; }).length;
		var pendingApprove = (d.issues || []).filter(function (i) {
			return i.status === 'waiting_print' || i.status === 'pending_approval' || i.status === 'picking' || i.status === 'packed' || i.status === 'approved';
		}).length;
		var skuSet = {};
		// Count SKUs still on the stock list (including negative oversell), exclude pure zero rows.
		(d.stock || []).forEach(function (s) {
			if ((Number(s.qty) || 0) !== 0) skuSet[s.sku] = true;
		});
		var expiring = (d.stock || []).filter(function (s) {
			return (Number(s.qty) || 0) !== 0 && daysUntil(s.expiry) < ((S.expiryWarnDaysFor && S.expiryWarnDaysFor(s)) || 90);
		}).length;
		var stockoutSoon = (d.stock || []).filter(function (s) {
			return S.isStockoutSoon ? S.isStockoutSoon(s) : false;
		}).length;
		var k1 = qs('#mkWhKpiPendingQc');
		var k2 = qs('#mkWhKpiPendingApprove');
		var k3 = qs('#mkWhKpiSku');
		var k4 = qs('#mkWhKpiExpiring');
		var k5 = qs('#mkWhKpiStockout');
		if (k1) k1.textContent = String(pendingQc);
		if (k2) k2.textContent = String(pendingApprove);
		if (k3) k3.textContent = String(Object.keys(skuSet).length);
		if (k4) k4.textContent = String(expiring);
		if (k5) k5.textContent = String(stockoutSoon);
	}

	function normalizeWhTabKey(raw) {
		var k = String(raw || '').toLowerCase().replace(/^#/, '');
		if (k === 'xuatkho' || k === 'outbound' || k === 'xuat') return 'outbound';
		if (k === 'nhapkho' || k === 'inbound' || k === 'nhap') return 'inbound';
		if (k === 'tonkho' || k === 'stock' || k === 'ton') return 'stock';
		if (k === 'qc') return 'qc';
		if (k === 'returns' || k === 'return' || k === 'thuhoi' || k === 'trahang') return 'returns';
		return '';
	}

	function persistWhTab(key) {
		var tab = normalizeWhTabKey(key) || 'inbound';
		try {
			var whId = getWhId() || '';
			if (whId && window.sessionStorage) {
				sessionStorage.setItem('mk_wh_detail_tab:' + whId, tab);
			}
		} catch (e1) { /* ignore */ }
		try {
			if (!window.history || !window.history.replaceState) return;
			var url = new URL(window.location.href);
			url.searchParams.set('tab', tab);
			// Bỏ hash cũ để tránh lệch với query
			url.hash = '';
			window.history.replaceState(null, '', url.pathname + url.search + (url.hash || ''));
		} catch (e2) {
			try {
				window.location.hash = tab;
			} catch (e3) { /* ignore */ }
		}
	}

	function readPersistedWhTab() {
		try {
			var params = new URLSearchParams(window.location.search || '');
			var fromQuery = normalizeWhTabKey(params.get('tab'));
			if (fromQuery) return fromQuery;
		} catch (e1) { /* ignore */ }
		try {
			var fromHash = normalizeWhTabKey(window.location.hash || '');
			if (fromHash) return fromHash;
		} catch (e2) { /* ignore */ }
		try {
			var whId = getWhId() || '';
			if (whId && window.sessionStorage) {
				var fromStore = normalizeWhTabKey(sessionStorage.getItem('mk_wh_detail_tab:' + whId));
				if (fromStore) return fromStore;
			}
		} catch (e3) { /* ignore */ }
		return 'inbound';
	}

	function setActiveTab(key) {
		var tab = normalizeWhTabKey(key) || 'inbound';
		qsa('.mk-wh-proto-tab').forEach(function (b) {
			b.classList.toggle('is-active', b.getAttribute('data-tab') === tab);
		});
		var title = qs('#mkWhProtoStageTitle');
		if (title) {
			title.textContent =
				tab === 'qc' ? 'Hàng đợi QC' :
				tab === 'stock' ? 'Tồn kho' :
				tab === 'outbound' ? 'Danh sách phiếu xuất' :
				tab === 'returns' ? 'Thu hồi / Trả hàng' :
				'Danh sách phiếu nhập';
		}
		['#mkWhProtoPaneInbound', '#mkWhProtoPaneQc', '#mkWhProtoPaneStock', '#mkWhProtoPaneOutbound', '#mkWhProtoPaneReturns'].forEach(function (sel) {
			var el = qs(sel);
			if (!el) return;
			var map = {
				'#mkWhProtoPaneQc': 'qc',
				'#mkWhProtoPaneStock': 'stock',
				'#mkWhProtoPaneOutbound': 'outbound',
				'#mkWhProtoPaneReturns': 'returns',
				'#mkWhProtoPaneInbound': 'inbound',
			};
			el.classList.toggle('hide', map[sel] !== tab);
		});
		persistWhTab(tab);
		updateRoleBanner();
		renderAll();
	}

	function inboundPrintPreviewUrl(receiptId) {
		var whId = getWhId();
		return 'index.php?module=Warehouse&action=ExportInboundPDF&warehouse=' +
			encodeURIComponent(whId || '') +
			'&record=' + encodeURIComponent(receiptId || '') +
			'&preview=1&format=html&app=INVENTORY';
	}

	function inboundPrintDownloadUrl(receiptId) {
		var whId = getWhId();
		return 'index.php?module=Warehouse&action=ExportInboundPDF&warehouse=' +
			encodeURIComponent(whId || '') +
			'&record=' + encodeURIComponent(receiptId || '') +
			'&download=1&format=pdf&app=INVENTORY';
	}

	function closeInboundPrintPreview() {
		var modal = qs('#mkWhInboundPrintPreview');
		if (!modal) return;
		modal.classList.remove('is-open');
		modal.setAttribute('aria-hidden', 'true');
		var frame = modal.querySelector('iframe');
		if (frame) frame.src = 'about:blank';
		modal.removeAttribute('data-receipt-id');
		modal.removeAttribute('data-previewed');
		document.body.classList.remove('mk-wh-inbound-print-open');
		var dl = modal.querySelector('[data-mk-print-download="1"]');
		if (dl) {
			dl.disabled = true;
			dl.setAttribute('aria-disabled', 'true');
			dl.title = 'Xem trước xong mới tải được PDF';
		}
	}

	function ensureInboundPrintPreviewModal() {
		var modal = qs('#mkWhInboundPrintPreview');
		if (modal) return modal;
		modal = document.createElement('div');
		modal.id = 'mkWhInboundPrintPreview';
		modal.className = 'mk-wh-inbound-print-preview';
		modal.setAttribute('aria-hidden', 'true');
		modal.innerHTML =
			'<div class="mk-wh-inbound-print-preview__dialog" role="dialog" aria-labelledby="mkWhInboundPrintTitle">' +
				'<div class="mk-wh-inbound-print-preview__head">' +
					'<h3 id="mkWhInboundPrintTitle">Xem trước phiếu nhập kho</h3>' +
					'<button type="button" class="mk-wh-inbound-print-preview__close" data-mk-print-close="1" aria-label="Đóng">&times;</button>' +
				'</div>' +
				'<div class="mk-wh-inbound-print-preview__body">' +
					'<div class="mk-wh-inbound-print-preview__hint">Xem trước mẫu PHIẾU NHẬP KHO (01 - VT). Tải PDF chỉ bật sau khi bản xem trước đã tải xong.</div>' +
					'<iframe class="mk-wh-inbound-print-preview__frame" title="Xem trước phiếu nhập kho" src="about:blank"></iframe>' +
				'</div>' +
				'<div class="mk-wh-inbound-print-preview__foot">' +
					'<button type="button" class="mk-wh-proto-btn mk-wh-proto-btn--ghost" data-mk-print-close="1">Đóng</button>' +
					'<button type="button" class="mk-wh-proto-btn mk-wh-proto-btn--primary" data-mk-print-download="1" disabled aria-disabled="true" title="Xem trước xong mới tải được PDF">' +
						'<i class="fa fa-download" aria-hidden="true"></i> Tải PDF' +
					'</button>' +
				'</div>' +
			'</div>';
		document.body.appendChild(modal);

		modal.addEventListener('click', function (e) {
			if (e.target === modal || (e.target && e.target.getAttribute && e.target.getAttribute('data-mk-print-close') === '1')) {
				e.preventDefault();
				closeInboundPrintPreview();
			}
		});
		var dlBtn = modal.querySelector('[data-mk-print-download="1"]');
		if (dlBtn) {
			dlBtn.addEventListener('click', function (e) {
				e.preventDefault();
				e.stopPropagation();
				if (modal.getAttribute('data-previewed') !== '1') {
					showError('Hãy xem trước phiếu trước khi tải PDF.');
					return;
				}
				var rid = modal.getAttribute('data-receipt-id') || '';
				if (!rid) return;
				var url = inboundPrintDownloadUrl(rid);
				// New tab is more reliable than hidden iframe (CSRF/frame-breaker).
				var opened = window.open(url, '_blank');
				if (!opened) {
					var a = document.createElement('a');
					a.href = url;
					a.target = '_blank';
					a.rel = 'noopener';
					document.body.appendChild(a);
					a.click();
					setTimeout(function () { a.remove(); }, 0);
				}
			});
		}
		return modal;
	}

	function openInboundPrintPreview(receiptId) {
		if (!isWarehouseOps(getRole())) {
			showError('Bạn không có quyền in phiếu nhập.');
			return;
		}
		if (!receiptId) return;
		var modal = ensureInboundPrintPreviewModal();
		var frame = modal.querySelector('iframe');
		var dl = modal.querySelector('[data-mk-print-download="1"]');
		modal.setAttribute('data-receipt-id', receiptId);
		modal.setAttribute('data-previewed', '0');
		if (dl) {
			dl.disabled = true;
			dl.setAttribute('aria-disabled', 'true');
			dl.title = 'Xem trước xong mới tải được PDF';
		}
		if (frame) {
			var markPreviewReady = function () {
				modal.setAttribute('data-previewed', '1');
				if (dl) {
					dl.disabled = false;
					dl.removeAttribute('aria-disabled');
					dl.title = 'Tải bản PDF phiếu nhập kho';
				}
			};
			frame.onload = markPreviewReady;
			// Preview only — never trigger download on open
			frame.src = inboundPrintPreviewUrl(receiptId);
			// Fallback if onload is swallowed by some browsers
			setTimeout(function () {
				if (modal.getAttribute('data-previewed') !== '1') {
					markPreviewReady();
				}
			}, 1800);
		}
		modal.classList.add('is-open');
		modal.setAttribute('aria-hidden', 'false');
		document.body.classList.add('mk-wh-inbound-print-open');
	}

	function outboundPrintPreviewUrl(issueId) {
		var whId = getWhId();
		return 'index.php?module=Warehouse&action=ExportOutboundPDF&warehouse=' +
			encodeURIComponent(whId || '') +
			'&record=' + encodeURIComponent(issueId || '') +
			'&preview=1&format=html&app=INVENTORY';
	}

	function outboundPrintDownloadUrl(issueId) {
		var whId = getWhId();
		return 'index.php?module=Warehouse&action=ExportOutboundPDF&warehouse=' +
			encodeURIComponent(whId || '') +
			'&record=' + encodeURIComponent(issueId || '') +
			'&download=1&format=pdf&app=INVENTORY';
	}

	function closeOutboundPrintPreview() {
		var modal = qs('#mkWhOutboundPrintPreview');
		if (!modal) return;
		modal.classList.remove('is-open');
		modal.setAttribute('aria-hidden', 'true');
		var frame = modal.querySelector('iframe');
		if (frame) frame.src = 'about:blank';
		modal.removeAttribute('data-issue-id');
		modal.removeAttribute('data-previewed');
		document.body.classList.remove('mk-wh-inbound-print-open');
		var dl = modal.querySelector('[data-mk-print-download="1"]');
		if (dl) {
			dl.disabled = true;
			dl.setAttribute('aria-disabled', 'true');
			dl.title = 'Xem trước xong mới tải được PDF';
		}
	}

	function ensureOutboundPrintPreviewModal() {
		var modal = qs('#mkWhOutboundPrintPreview');
		if (modal) return modal;
		modal = document.createElement('div');
		modal.id = 'mkWhOutboundPrintPreview';
		modal.className = 'mk-wh-inbound-print-preview';
		modal.setAttribute('aria-hidden', 'true');
		modal.innerHTML =
			'<div class="mk-wh-inbound-print-preview__dialog" role="dialog" aria-labelledby="mkWhOutboundPrintTitle">' +
				'<div class="mk-wh-inbound-print-preview__head">' +
					'<h3 id="mkWhOutboundPrintTitle">Xem trước bản in</h3>' +
					'<button type="button" class="mk-wh-inbound-print-preview__close" data-mk-print-close="1" aria-label="Đóng">&times;</button>' +
				'</div>' +
				'<div class="mk-wh-inbound-print-preview__body">' +
					'<div class="mk-wh-inbound-print-preview__hint" data-mk-print-hint="1">Xem trước bản in phiếu xuất. Có thể in trực tiếp hoặc tải PDF sau khi bản xem trước tải xong.</div>' +
					'<iframe class="mk-wh-inbound-print-preview__frame" title="Xem trước phiếu xuất" src="about:blank"></iframe>' +
				'</div>' +
				'<div class="mk-wh-inbound-print-preview__foot">' +
					'<button type="button" class="mk-wh-proto-btn mk-wh-proto-btn--ghost" data-mk-print-close="1">Đóng</button>' +
					'<button type="button" class="mk-wh-proto-btn mk-wh-proto-btn--outline" data-mk-print-direct="1">' +
						'<i class="fa fa-print" aria-hidden="true"></i> In ngay' +
					'</button>' +
					'<button type="button" class="mk-wh-proto-btn mk-wh-proto-btn--primary" data-mk-print-download="1" disabled aria-disabled="true" title="Xem trước xong mới tải được PDF">' +
						'<i class="fa fa-download" aria-hidden="true"></i> Tải PDF' +
					'</button>' +
				'</div>' +
			'</div>';
		document.body.appendChild(modal);

		modal.addEventListener('click', function (e) {
			if (e.target === modal || (e.target && e.target.getAttribute && e.target.getAttribute('data-mk-print-close') === '1')) {
				e.preventDefault();
				closeOutboundPrintPreview();
			}
		});
		var dlBtn = modal.querySelector('[data-mk-print-download="1"]');
		if (dlBtn) {
			dlBtn.addEventListener('click', function (e) {
				e.preventDefault();
				e.stopPropagation();
				if (modal.getAttribute('data-previewed') !== '1') {
					showError('Hãy xem trước phiếu trước khi tải PDF.');
					return;
				}
				var iid = modal.getAttribute('data-issue-id') || '';
				if (!iid) return;
				var url = outboundPrintDownloadUrl(iid);
				var opened = window.open(url, '_blank');
				if (!opened) {
					var a = document.createElement('a');
					a.href = url;
					a.target = '_blank';
					a.rel = 'noopener';
					document.body.appendChild(a);
					a.click();
					setTimeout(function () { a.remove(); }, 0);
				}
			});
		}
		var printBtn = modal.querySelector('[data-mk-print-direct="1"]');
		if (printBtn) {
			printBtn.addEventListener('click', function (e) {
				e.preventDefault();
				e.stopPropagation();
				var frame = modal.querySelector('iframe');
				try {
					if (frame && frame.contentWindow) {
						frame.contentWindow.focus();
						frame.contentWindow.print();
					}
				} catch (err) {
					/* ignore */
				}
			});
		}
		return modal;
	}

	function outboundPrintLabels(outboundType) {
		var t = String(outboundType || 'internal');
		if (t === 'transfer') {
			return {
				title: 'Xem trước bản in phiếu chuyển hàng',
				hint: 'Mẫu PHIẾU CHUYỂN HÀNG (xuất chuyển kho). Có thể in trực tiếp hoặc tải PDF sau khi xem trước xong.',
				readyTitle: 'Tải bản PDF phiếu chuyển hàng',
			};
		}
		if (t === 'sale' || t === 'scrap') {
			return {
				title: t === 'sale' ? 'Xem trước bản in hóa đơn đặt hàng' : 'Xem trước bản in phiếu xuất kho',
				hint: t === 'scrap'
					? 'Mẫu PHIẾU XUẤT KHO (02 - VT) dùng cho xuất huỷ. Có thể in trực tiếp hoặc tải PDF sau khi xem trước xong.'
					: 'Xuất bán dùng đúng form HÓA ĐƠN ĐẶT HÀNG giống màn in đơn hàng. Có thể in trực tiếp hoặc tải PDF sau khi xem trước xong.',
				readyTitle: t === 'sale' ? 'Tải bản PDF hóa đơn đặt hàng' : 'Tải bản PDF phiếu xuất kho',
			};
		}
		return {
			title: 'Xem trước bản in phiếu xuất nội bộ',
			hint: 'Mẫu Xuất dùng nội bộ. Có thể in trực tiếp hoặc tải PDF sau khi xem trước xong.',
			readyTitle: 'Tải bản PDF xuất dùng nội bộ',
		};
	}

	function openOutboundPrintPreview(issueId) {
		if (!isWarehouseOps(getRole())) {
			showError('Bạn không có quyền in phiếu xuất.');
			return;
		}
		if (!issueId) return;
		var whId = getWhId();
		var d = whId ? S.ensureData(whId) : null;
		var issue = d && (d.issues || []).find(function (x) { return x.id === issueId; });
		if (!issue) {
			showError('Không tìm thấy phiếu xuất.');
			return;
		}
		var labels = outboundPrintLabels(issue.outboundType || 'internal');

		function showPreviewModal(finalIssueId) {
			var modal = ensureOutboundPrintPreviewModal();
			var frame = modal.querySelector('iframe');
			var dl = modal.querySelector('[data-mk-print-download="1"]');
			var titleEl = modal.querySelector('#mkWhOutboundPrintTitle');
			var hintEl = modal.querySelector('[data-mk-print-hint="1"]');
			if (titleEl) titleEl.textContent = labels.title;
			if (hintEl) hintEl.textContent = labels.hint;
			modal.setAttribute('data-issue-id', finalIssueId);
			modal.setAttribute('data-previewed', '0');
			if (dl) {
				dl.disabled = true;
				dl.setAttribute('aria-disabled', 'true');
				dl.title = 'Xem trước xong mới tải được PDF';
			}
			if (frame) {
				var markPreviewReady = function () {
					modal.setAttribute('data-previewed', '1');
					if (dl) {
						dl.disabled = false;
						dl.removeAttribute('aria-disabled');
						dl.title = labels.readyTitle;
					}
				};
				frame.onload = markPreviewReady;
				frame.src = outboundPrintPreviewUrl(finalIssueId);
				setTimeout(function () {
					if (modal.getAttribute('data-previewed') !== '1') {
						markPreviewReady();
					}
				}, 1800);
			}
			modal.classList.add('is-open');
			modal.setAttribute('aria-hidden', 'false');
			document.body.classList.add('mk-wh-inbound-print-open');
		}

		// Đồng bộ phiếu local (nội bộ/chuyển kho/huỷ) lên DB trước khi in — không đụng xuất bán.
		var ot = String(issue.outboundType || 'internal');
		if (
			ot !== 'sale' &&
			S.useDb && S.useDb() &&
			S.warehouseDataActions &&
			typeof S.warehouseDataActions.saveIssue === 'function'
		) {
			S.warehouseDataActions.saveIssue(whId, issue).then(function (res) {
				var code = (res && res.code) ? res.code : issueId;
				if (code && code !== issueId && issue) {
					issue.id = code;
				}
				showPreviewModal(code || issueId);
			}).fail(function (err) {
				showError((err && err.message) || 'Không đồng bộ được phiếu xuất để in.');
			});
			return;
		}

		showPreviewModal(issueId);
	}

	function renderInbounds() {
		var id = getWhId();
		var tbody = qs('#mkWhProtoInboundTbody');
		if (!id || !tbody) return;
		var d = S.ensureData(id);
		tbody.innerHTML = (d.receipts || []).map(function (r) {
			var st = RECEIPT_STATUS[r.status] || { label: r.status, cls: 'mk-wh-proto-pill' };
			return '<tr>' +
				'<td><strong>' + escapeHtml(r.id) + '</strong></td>' +
				'<td>' + escapeHtml(decodeEntities(r.supplier)) + '</td>' +
				'<td>' + escapeHtml(decodeEntities(r.poRef)) + '</td>' +
				'<td>' + escapeHtml(fmtDateTime(r.createdAt)) + '</td>' +
				'<td><span class="' + escapeHtml(st.cls) + '">' + escapeHtml(st.label) + '</span></td>' +
				'<td class="mk-wh-proto-td-right mk-wh-proto-actions">' +
					'<button class="mk-wh-proto-mini-btn" type="button" data-mk-action="inbound-detail" data-id="' + escapeHtml(r.id) + '">Mở</button>' +
					(isWarehouseOps(getRole())
						? ' <button class="mk-wh-proto-mini-btn mk-wh-proto-mini-btn--print" type="button" data-mk-action="inbound-print" data-id="' + escapeHtml(r.id) + '">In</button>'
						: '') +
				'</td>' +
			'</tr>';
		}).join('');
	}

	function renderQcQueue() {
		var id = getWhId();
		var tbody = qs('#mkWhProtoQcTbody');
		if (!id || !tbody) return;
		var d = S.ensureData(id);
		var rows = [];
		(d.receipts || []).filter(function (r) { return r.status === 'pending_qc'; }).forEach(function (r) {
			var it = (r.lines && r.lines[0]) || {};
			rows.push('<tr>' +
				'<td><strong>' + escapeHtml(r.id) + '</strong></td>' +
				'<td>' + escText(r.supplier) + '</td>' +
				'<td>' + escText(it.name || '—') + (formatSkuLabel(it.sku) !== '—' ? ' <span class="mk-wh-proto-muted">(' + escText(formatSkuLabel(it.sku)) + ')</span>' : '') + '</td>' +
				'<td>' + escText(it.lot || '—') + '</td>' +
				'<td>' +
					(it.mfg ? '<span class="mk-wh-proto-muted">NSX: ' + escText(it.mfg) + '</span><br/>' : '') +
					'HSD: ' + escText(it.expiry || '—') +
				'</td>' +
				'<td>' + escText(it.qty || '—') + '</td>' +
				'<td class="mk-wh-proto-td-right"><button class="mk-wh-proto-mini-btn" type="button" data-mk-action="qc-record" data-id="' + escapeHtml(r.id) + '"' +
					(!canDoQc() ? ' disabled' : '') +
				'>Ghi nhận QC</button></td>' +
			'</tr>');
		});
		tbody.innerHTML = rows.join('');
	}

	function syncStockSearchClear() {
		var searchEl = qs('#mkWhProtoStockSearch');
		var clearEl = qs('#mkWhProtoStockSearchClear');
		if (!clearEl) return;
		var q = searchEl ? String(searchEl.value || '').trim() : '';
		if (q) {
			clearEl.removeAttribute('hidden');
		} else {
			clearEl.setAttribute('hidden', 'hidden');
		}
	}

	function applyStockFilters(rows) {
		var hsdEl = qs('#mkWhProtoFilterHsd');
		var searchEl = qs('#mkWhProtoStockSearch');
		var priceEl = qs('#mkWhProtoFilterPrice');
		var filters = {
			hsd: hsdEl ? hsdEl.value : 'all',
			search: searchEl ? String(searchEl.value || '').trim().toLowerCase() : '',
			price: priceEl ? priceEl.value : 'all',
		};
		// Keep zero and negative stock lines visible (oversell → tồn âm).
		var list = (rows || []).filter(function (s) {
			var days = daysUntil(s.expiry);
			var warnDays = (S.expiryWarnDaysFor && S.expiryWarnDaysFor(s)) || 90;
			if (filters.hsd === 'soon' && !(days >= 0 && days < warnDays)) return false;
			if (filters.hsd === 'valid' && !(days >= 0)) return false;
			if (filters.hsd === 'expired' && !(days < 0)) return false;
			if (filters.search) {
				var hay = [s.name, s.sku, s.lot, s.location].join(' ').toLowerCase();
				if (hay.indexOf(filters.search) < 0) return false;
			}
			return true;
		});
		list.sort(function (a, b) {
			if (filters.price === 'asc' || filters.price === 'desc') {
				var pa = Number(a.price) || 0;
				var pb = Number(b.price) || 0;
				if (pa !== pb) return filters.price === 'asc' ? pa - pb : pb - pa;
			}
			var an = String(a.name || '').toLocaleLowerCase('vi');
			var bn = String(b.name || '').toLocaleLowerCase('vi');
			return an.localeCompare(bn, 'vi');
		});
		return { rows: list, filters: filters };
	}

	function stockQtyClass(qty) {
		var n = Number(qty);
		if (!isFinite(n)) {
			n = 0;
		}
		if (n < 0) {
			return ' mk-wh-proto-qty--neg';
		}
		if (n > 0 && n < 50) {
			return ' mk-wh-proto-qty--low';
		}
		return '';
	}

	function formatStockQty(qty) {
		var n = Number(qty);
		if (!isFinite(n)) {
			return '0';
		}
		// Show integers without decimals; keep fraction if present.
		if (Math.abs(n - Math.round(n)) < 0.0000001) {
			return String(Math.round(n));
		}
		return String(n);
	}

	function renderStock() {
		var id = getWhId();
		var tbody = qs('#mkWhProtoStockTbody');
		if (!id || !tbody) return;
		var d = S.ensureData(id);
		var inStock = (d.stock || []).slice();
		var result = applyStockFilters(inStock);
		var rows = result.rows;
		var summary = qs('#mkWhProtoFilterSummary');
		if (summary) {
			if (!rows.length && inStock.length) {
				summary.textContent = 'Không có mặt hàng phù hợp bộ lọc (đang có ' + inStock.length + ' dòng tồn).';
			} else if (!inStock.length) {
				summary.textContent = 'Chưa có tồn kho.';
			} else {
				summary.textContent = 'Hiển thị ' + rows.length + ' / ' + inStock.length + ' mặt hàng';
			}
		}
		tbody.innerHTML = rows.map(function (s) {
			var days = daysUntil(s.expiry);
			var warnDays = (S.expiryWarnDaysFor && S.expiryWarnDaysFor(s)) || 90;
			var expLabel = days < 0 ? 'Quá hạn' : 'Còn ' + days + ' ngày';
			var hsdCls = 'mk-wh-proto-hsd' + (days < 0 ? ' mk-wh-proto-hsd--expired' : (days < warnDays ? ' mk-wh-proto-hsd--soon' : ''));
			var qtyCls = stockQtyClass(s.qty);
			var qtyTitle = (Number(s.qty) || 0) < 0 ? 'Tồn kho âm (đã xuất vượt tồn)' : '';
			var soLabel = S.stockoutLabel ? S.stockoutLabel(s) : 'Không đủ dữ liệu';
			var soSoon = S.isStockoutSoon ? S.isStockoutSoon(s) : false;
			return '<tr' + ((Number(s.qty) || 0) < 0 ? ' class="mk-wh-proto-stock-row--neg"' : '') + '>' +
				'<td><strong>' + escText(formatSkuLabel(s.sku)) + '</strong></td>' +
				'<td>' + escText(s.name) + '</td>' +
				'<td>' + escText(s.lot) + '</td>' +
				'<td class="' + hsdCls + '">' + escText(s.expiry || '—') + ' <span class="mk-wh-proto-muted">(' + escText(expLabel) + ')</span></td>' +
				'<td class="' + (soSoon ? 'mk-wh-proto-hsd mk-wh-proto-hsd--soon' : 'mk-wh-proto-muted') + '">' + escText(soLabel) + '</td>' +
				'<td class="mk-wh-proto-td-right">' + escText(fmtPrice(s.price)) + '</td>' +
				'<td class="mk-wh-proto-td-right">' + escText(s.location || '—') + '</td>' +
				'<td class="mk-wh-proto-td-right' + qtyCls + '"' + (qtyTitle ? ' title="' + escText(qtyTitle) + '"' : '') + '><strong>' + escText(formatStockQty(s.qty)) + '</strong></td>' +
			'</tr>';
		}).join('');
	}

	function renderOutbound() {
		var id = getWhId();
		var tbody = qs('#mkWhProtoOutboundTbody');
		if (!id || !tbody) return;
		var d = S.ensureData(id);
		tbody.innerHTML = (d.issues || []).map(function (i) {
			var st = ISSUE_STATUS[i.status] || { label: i.status, cls: 'mk-wh-proto-pill' };
			return '<tr>' +
				'<td><strong>' + escText(i.id) + '</strong></td>' +
				'<td>' + outboundTypePill(i.outboundType || 'internal') + '</td>' +
				'<td>' + escText(i.customer) + '</td>' +
				'<td>' + escText(i.soRef || '—') + '</td>' +
				'<td>' + escapeHtml(fmtDateTime(i.createdAt)) + '</td>' +
				'<td>' + issueStatusPill(i.status) + '</td>' +
				'<td class="mk-wh-proto-td-right mk-wh-proto-actions">' +
					(isWarehouseOps(getRole()) && canCancelOutboundIssue(i.status)
						? '<button class="mk-wh-proto-mini-btn mk-wh-proto-mini-btn--cancel" type="button" data-mk-action="outbound-cancel" data-id="' + escText(i.id) + '">Huỷ</button> '
						: '') +
					'<button class="mk-wh-proto-mini-btn" type="button" data-mk-action="outbound-detail" data-id="' + escText(i.id) + '">Chi tiết</button>' +
					(isWarehouseOps(getRole()) && canPrintOutboundIssue(i.status)
						? ' <button class="mk-wh-proto-mini-btn mk-wh-proto-mini-btn--print" type="button" data-mk-action="outbound-print" data-id="' + escText(i.id) + '">In</button>'
						: '') +
				'</td>' +
			'</tr>';
		}).join('');
	}

	var RETURN_STATUS = {
		draft: { label: 'Nháp', cls: 'mk-wh-proto-pill' },
		confirmed: { label: 'Đã nhập kho', cls: 'mk-wh-proto-pill mk-wh-proto-pill--ok' },
		cancelled: { label: 'Đã hủy', cls: 'mk-wh-proto-pill mk-wh-proto-pill--danger' },
	};

	function renderReturns() {
		var id = getWhId();
		var tbody = qs('#mkWhProtoReturnsTbody');
		if (!id || !tbody) return;
		var d = S.ensureData(id);
		var rows = d.returns || [];
		tbody.innerHTML = rows.map(function (r) {
			var st = RETURN_STATUS[r.status] || { label: r.status, cls: 'mk-wh-proto-pill' };
			var kind = r.docType === 'recall' ? 'Thu hồi' : 'Trả hàng';
			var src = r.sourceType === 'franchise' ? 'NQ' : 'Khách lẻ';
			return '<tr>' +
				'<td><strong>' + escText(r.code || r.id) + '</strong></td>' +
				'<td>' + escText(kind) + '</td>' +
				'<td>' + escText(src + ' · ' + (r.sourceLabel || '—')) + '</td>' +
				'<td>' + (r.refund ? ('Có · ' + escText(fmtPrice(r.refundAmount))) : 'Không') + '</td>' +
				'<td>' + escapeHtml(fmtDateTime(r.createdAt)) + '</td>' +
				'<td><span class="' + st.cls + '">' + escText(st.label) + '</span></td>' +
				'<td class="mk-wh-proto-td-right mk-wh-proto-actions">' +
					'<button class="mk-wh-proto-mini-btn" type="button" data-mk-action="return-detail" data-id="' + escText(r.code || r.id) + '">Chi tiết</button>' +
					(isWarehouseOps(getRole()) && r.status === 'draft'
						? ' <button class="mk-wh-proto-mini-btn" type="button" data-mk-action="return-confirm" data-id="' + escText(r.code || r.id) + '">Xác nhận nhập</button>' +
						  ' <button class="mk-wh-proto-mini-btn mk-wh-proto-mini-btn--cancel" type="button" data-mk-action="return-cancel" data-id="' + escText(r.code || r.id) + '">Hủy</button>'
						: '') +
					' <button class="mk-wh-proto-mini-btn mk-wh-proto-mini-btn--print" type="button" data-mk-action="return-print" data-id="' + escText(r.code || r.id) + '">In</button>' +
				'</td></tr>';
		}).join('') || '<tr><td colspan="7" class="mk-wh-mgmt-empty">Chưa có phiếu thu hồi / trả hàng.</td></tr>';
	}

	function returnPrintUrl(code, preview) {
		var whId = getWhId();
		return 'index.php?module=Warehouse&action=ExportReturnPDF&warehouse=' +
			encodeURIComponent(whId || '') +
			'&record=' + encodeURIComponent(code || '') +
			(preview ? '&preview=1&format=html' : '&download=1&format=pdf') +
			'&app=INVENTORY';
	}

	function openReturnDialog(slip) {
		if (!slip) return;
		var st = RETURN_STATUS[slip.status] || { label: slip.status, cls: 'mk-wh-proto-pill' };
		var kind = slip.docType === 'recall' ? 'Thu hồi' : 'Trả hàng';
		var lines = (slip.lines || []).map(function (l, i) {
			return '<tr><td>' + (i + 1) + '</td><td>' + escText(l.name) + '</td><td>' + escText(l.sku) +
				'</td><td>' + escText(l.lot) + '</td><td>' + escText(l.expiry || '—') +
				'</td><td class="mk-wh-proto-td-right">' + escText(l.qty) + '</td></tr>';
		}).join('');
		var body = '<p><strong>' + escText(kind) + '</strong> · ' + escText(slip.sourceLabel || '') + '</p>' +
			'<p>Hoàn tiền: ' + (slip.refund ? ('Có · ' + escText(fmtPrice(slip.refundAmount))) : 'Không') + '</p>' +
			(slip.note ? '<p>Ghi chú: ' + escText(slip.note) + '</p>' : '') +
			'<table class="mk-wh-proto-table"><thead><tr><th>STT</th><th>Hàng</th><th>SKU</th><th>Lô</th><th>HSD</th><th>SL</th></tr></thead><tbody>' +
			lines + '</tbody></table>';
		var foot = '<button type="button" class="mk-wh-proto-mini-btn" data-mk-dialog-close="1">Đóng</button>' +
			(isWarehouseOps(getRole()) && slip.status === 'draft'
				? ' <button type="button" class="mk-wh-proto-mini-btn" data-mk-action="return-confirm" data-id="' + escText(slip.code || slip.id) + '">Xác nhận nhập kho</button>'
				: '') +
			' <button type="button" class="mk-wh-proto-mini-btn mk-wh-proto-mini-btn--print" data-mk-action="return-print" data-id="' + escText(slip.code || slip.id) + '">In phiếu</button>';
		openDialog(slip.code || slip.id, '<span class="' + st.cls + '">' + escText(st.label) + '</span>', body, foot);
	}

	function getReturnModalState(modal) {
		if (!modal._mkReturn) {
			modal._mkReturn = { selected: [], catalog: [] };
		}
		return modal._mkReturn;
	}

	function returnLineKey(line) {
		return [
			line.issue_code || '',
			line.product_id || 0,
			line.sku || '',
			line.lot || '',
			line.name || '',
		].join('|');
	}

	function returnLineRowHtml(line) {
		line = line || {};
		var maxQty = parseFloat(line.max_qty != null ? line.max_qty : line.qty) || 0;
		var qty = line.return_qty != null ? parseFloat(line.return_qty) : 0;
		if (isNaN(qty) || qty < 0) qty = 0;
		return '<tr data-mk-return-line="1" data-line-key="' + escText(returnLineKey(line)) + '"' +
			' data-product-id="' + escText(line.product_id || '') + '"' +
			' data-issue-code="' + escText(line.issue_code || '') + '"' +
			' data-max-qty="' + escText(maxQty) + '"' +
			' data-price="' + escText(line.price || 0) + '">' +
			'<td class="mk-wh-return-col-name"><span class="mk-wh-return-name">' + escapeHtml(line.name || '') + '</span></td>' +
			'<td class="mk-wh-return-col-sku">' + escapeHtml(line.sku || '—') + '</td>' +
			'<td class="mk-wh-return-col-lot">' + escapeHtml(line.lot || '—') + '</td>' +
			'<td class="mk-wh-return-col-issue"><code>' + escapeHtml(line.issue_code || '') + '</code></td>' +
			'<td class="mk-wh-return-col-max mk-wh-proto-td-right">' + escapeHtml(maxQty) + '</td>' +
			'<td class="mk-wh-return-col-qty mk-wh-proto-td-right"><input type="number" min="0" max="' + escText(maxQty) + '" step="0.01" data-f="qty" value="' + escText(qty) + '" /></td>' +
			'<td class="mk-wh-return-col-act"><button type="button" class="mk-wh-proto-mini-btn" data-mk-return-take-line="1">Lấy hết</button></td>' +
			'</tr>';
	}

	function collectReturnQtyMap() {
		var map = {};
		qsa('#mkWhReturnLinesBody [data-mk-return-line="1"]').forEach(function (row) {
			var key = row.getAttribute('data-line-key') || '';
			var el = row.querySelector('[data-f="qty"]');
			map[key] = el ? parseFloat(el.value) || 0 : 0;
		});
		return map;
	}

	function collectReturnLines() {
		var out = [];
		qsa('#mkWhReturnLinesBody [data-mk-return-line="1"]').forEach(function (row) {
			var qtyEl = row.querySelector('[data-f="qty"]');
			var qty = qtyEl ? parseFloat(qtyEl.value) || 0 : 0;
			var maxQty = parseFloat(row.getAttribute('data-max-qty') || '0') || 0;
			if (qty > maxQty) qty = maxQty;
			var nameEl = row.querySelector('.mk-wh-return-name');
			var name = nameEl ? String(nameEl.textContent || '').trim() : '';
			if (!name || qty <= 0) return;
			out.push({
				product_id: parseInt(row.getAttribute('data-product-id') || '0', 10) || 0,
				name: name,
				sku: String(row.children[1] ? row.children[1].textContent : '').replace('—', '').trim(),
				lot: String(row.children[2] ? row.children[2].textContent : '').replace('—', '').trim(),
				expiry: '',
				qty: qty,
				price: parseFloat(row.getAttribute('data-price') || '0') || 0,
				issue_code: row.getAttribute('data-issue-code') || '',
			});
		});
		return out;
	}

	function selectedReturnIssues(modal) {
		return getReturnModalState(modal).selected.slice();
	}

	function syncReturnPickedUi(modal) {
		var selected = selectedReturnIssues(modal);
		var countEl = qs('#mkWhReturnPickedCount');
		var list = qs('#mkWhReturnPickedList');
		var hid = qs('#mkWhReturnSourceLabel');
		var takeAll = qs('#mkWhReturnTakeAll');
		var hint = qs('#mkWhReturnLinesHint');
		if (countEl) countEl.textContent = String(selected.length);
		if (list) {
			list.innerHTML = selected.length
				? selected.map(function (s) {
					return '<button type="button" class="mk-wh-return-chip" data-mk-return-unpick="' + escText(s.issueCode) + '">' +
						escText(s.issueCode) + ' <span aria-hidden="true">×</span></button>';
				}).join('')
				: '<span class="mk-wh-proto-muted">Chưa chọn phiếu xuất</span>';
		}
		var labels = selected.map(function (s) { return s.issueCode; });
		if (hid) hid.value = labels.join(', ');
		var soId = 0;
		selected.forEach(function (s) {
			if (!soId && s.salesorderId) soId = s.salesorderId;
		});
		if (qs('#mkWhReturnSoId')) qs('#mkWhReturnSoId').value = soId || '';
		if (takeAll) takeAll.hidden = !selected.length;
		if (hint) {
			hint.textContent = selected.length
				? 'Nhập số lượng từng dòng. Để 0 nếu không trả sản phẩm đó.'
				: 'Chọn phiếu xuất bên trái, rồi nhập số lượng từng dòng. Dòng để 0 sẽ không trả.';
		}
		qsa('#mkWhReturnSourceResults .mk-wh-return-source').forEach(function (btn) {
			var code = btn.getAttribute('data-issue-code') || '';
			btn.classList.toggle('is-active', selected.some(function (s) { return s.issueCode === code; }));
		});
	}

	function rebuildReturnLines(modal) {
		var selected = selectedReturnIssues(modal);
		var qtyMap = collectReturnQtyMap();
		var lines = [];
		selected.forEach(function (issue) {
			(issue.lines || []).forEach(function (line) {
				var row = {
					product_id: line.product_id,
					name: line.name,
					sku: line.sku,
					lot: line.lot,
					max_qty: line.max_qty != null ? line.max_qty : line.qty,
					price: line.price || 0,
					issue_code: line.issue_code || issue.issueCode,
					issue_id: line.issue_id || issue.issueId,
				};
				var key = returnLineKey(row);
				row.return_qty = qtyMap.hasOwnProperty(key) ? qtyMap[key] : 0;
				lines.push(row);
			});
		});
		var body = qs('#mkWhReturnLinesBody');
		if (!body) return;
		body.innerHTML = lines.length
			? lines.map(returnLineRowHtml).join('')
			: '<tr><td colspan="7" class="mk-wh-mgmt-empty">Chưa có sản phẩm — hãy chọn phiếu xuất.</td></tr>';
	}

	function toggleReturnIssue(modal, issue) {
		if (!issue || !issue.issueCode) return;
		var state = getReturnModalState(modal);
		var idx = -1;
		state.selected.forEach(function (s, i) {
			if (s.issueCode === issue.issueCode) idx = i;
		});
		if (idx >= 0) {
			state.selected.splice(idx, 1);
		} else {
			state.selected.push(issue);
		}
		syncReturnPickedUi(modal);
		rebuildReturnLines(modal);
	}

	function unpickReturnIssue(modal, code) {
		var state = getReturnModalState(modal);
		state.selected = state.selected.filter(function (s) { return s.issueCode !== code; });
		syncReturnPickedUi(modal);
		rebuildReturnLines(modal);
	}

	function closeReturnModal() {
		var modal = qs('#mkWhReturnModal');
		if (!modal) return;
		modal.classList.remove('is-open');
		modal.setAttribute('aria-hidden', 'true');
	}

	function openReturnModal() {
		var modal = qs('#mkWhReturnModal');
		if (!modal) return;
		modal._mkReturn = { selected: [], catalog: [] };
		if (qs('#mkWhReturnSoId')) qs('#mkWhReturnSoId').value = '';
		if (qs('#mkWhReturnScId')) qs('#mkWhReturnScId').value = '';
		if (qs('#mkWhReturnSourceLabel')) qs('#mkWhReturnSourceLabel').value = '';
		if (qs('#mkWhReturnSourceQ')) qs('#mkWhReturnSourceQ').value = '';
		if (qs('#mkWhReturnNote')) qs('#mkWhReturnNote').value = '';
		var refund = qs('#mkWhReturnRefund');
		if (refund) refund.checked = false;
		var results = qs('#mkWhReturnSourceResults');
		if (results) {
			results.innerHTML = '';
			results._sources = [];
		}
		syncReturnPickedUi(modal);
		rebuildReturnLines(modal);
		modal.classList.add('is-open');
		modal.setAttribute('aria-hidden', 'false');
		searchReturnIssues();
	}

	function renderReturnSources(issues) {
		var box = qs('#mkWhReturnSourceResults');
		var modal = qs('#mkWhReturnModal');
		if (!box) return;
		issues = issues || [];
		if (modal) getReturnModalState(modal).catalog = issues;
		if (!issues.length) {
			box.innerHTML = '<p class="mk-wh-mgmt-empty">Không tìm thấy phiếu xuất của kho này.</p>';
			box._sources = [];
			return;
		}
		box.innerHTML = issues.map(function (s, idx) {
			var n = (s.lines || []).length;
			return '<button type="button" class="mk-wh-return-source" data-mk-return-source="' + idx + '" data-issue-code="' + escText(s.issueCode || '') + '">' +
				'<strong>' + escText(s.issueCode || s.label) + '</strong>' +
				(s.customer ? '<span class="mk-wh-return-source__sub">' + escText(s.customer) + '</span>' : '') +
				'<span class="mk-wh-proto-muted">' + n + ' dòng</span></button>';
		}).join('');
		box._sources = issues;
		if (modal) syncReturnPickedUi(modal);
	}

	function searchReturnIssues() {
		if (!S.returnActions) return;
		var searchInput = qs('#mkWhReturnSourceQ');
		S.returnActions.searchSources(searchInput ? searchInput.value : '', getWhId()).then(function (res) {
			renderReturnSources((res && (res.issues || res.sources)) || []);
		}).fail(function (err) {
			showError((err && err.message) || 'Không tìm được phiếu xuất.');
		});
	}

	function bindReturnModal() {
		var modal = qs('#mkWhReturnModal');
		if (!modal || modal.getAttribute('data-bound') === '1') return;
		modal.setAttribute('data-bound', '1');
		modal.addEventListener('click', function (e) {
			var t = e.target;
			if (t.closest && t.closest('[data-mk-return-close="1"]')) {
				closeReturnModal();
				return;
			}
			var srcBtn = t.closest && t.closest('[data-mk-return-source]');
			if (srcBtn) {
				var idx = parseInt(srcBtn.getAttribute('data-mk-return-source'), 10);
				var sources = (qs('#mkWhReturnSourceResults') && qs('#mkWhReturnSourceResults')._sources) || [];
				toggleReturnIssue(modal, sources[idx]);
				return;
			}
			var unpick = t.closest && t.closest('[data-mk-return-unpick]');
			if (unpick) {
				unpickReturnIssue(modal, unpick.getAttribute('data-mk-return-unpick') || '');
				return;
			}
			var takeLine = t.closest && t.closest('[data-mk-return-take-line="1"]');
			if (takeLine) {
				var row = takeLine.closest('[data-mk-return-line="1"]');
				if (!row) return;
				var qtyEl = row.querySelector('[data-f="qty"]');
				if (qtyEl) qtyEl.value = row.getAttribute('data-max-qty') || '0';
			}
		});
		var searchBtn = qs('#mkWhReturnSourceSearchBtn');
		var searchInput = qs('#mkWhReturnSourceQ');
		if (searchBtn) searchBtn.addEventListener('click', searchReturnIssues);
		if (searchInput) {
			searchInput.addEventListener('keydown', function (e) {
				if (e.key === 'Enter') {
					e.preventDefault();
					searchReturnIssues();
				}
			});
		}
		var takeAll = qs('#mkWhReturnTakeAll');
		if (takeAll) {
			takeAll.addEventListener('click', function () {
				qsa('#mkWhReturnLinesBody [data-mk-return-line="1"]').forEach(function (row) {
					var qtyEl = row.querySelector('[data-f="qty"]');
					if (qtyEl) qtyEl.value = row.getAttribute('data-max-qty') || '0';
				});
			});
		}
		var form = qs('#mkWhReturnForm');
		if (form) {
			form.addEventListener('submit', function (e) {
				e.preventDefault();
				var whId = getWhId();
				var selected = selectedReturnIssues(modal);
				if (!selected.length) {
					showError('Chọn ít nhất một phiếu xuất.');
					return;
				}
				var lines = collectReturnLines();
				if (!lines.length) {
					showError('Chọn sản phẩm và số lượng cần trả (nhiều dòng có thể để 0).');
					return;
				}
				if (!S.returnActions) {
					showError('Chế độ lưu database chưa sẵn sàng.');
					return;
				}
				S.returnActions.save(whId, {
					docType: qs('#mkWhReturnDocType') ? qs('#mkWhReturnDocType').value : 'return',
					sourceType: qs('#mkWhReturnSourceType') ? qs('#mkWhReturnSourceType').value : 'retail',
					sourceLabel: qs('#mkWhReturnSourceLabel') ? qs('#mkWhReturnSourceLabel').value : '',
					salesorderId: parseInt(qs('#mkWhReturnSoId') ? qs('#mkWhReturnSoId').value : '0', 10) || 0,
					servicecontractId: parseInt(qs('#mkWhReturnScId') ? qs('#mkWhReturnScId').value : '0', 10) || 0,
					issueCodes: selected.map(function (s) { return s.issueCode; }),
					refund: !!(qs('#mkWhReturnRefund') && qs('#mkWhReturnRefund').checked),
					note: qs('#mkWhReturnNote') ? qs('#mkWhReturnNote').value : '',
					lines: lines,
				}).then(function () {
					closeReturnModal();
					refreshWarehouseUi();
				}).fail(function (err) {
					showError((err && err.message) || 'Không lưu được phiếu.');
				});
			});
		}
	}

	function renderAll() {
		renderInbounds();
		renderQcQueue();
		renderStock();
		renderOutbound();
		renderReturns();
		updateKpis();
	}

	function refreshWarehouseUi() {
		renderAll();
		if (typeof window.requestAnimationFrame === 'function') {
			window.requestAnimationFrame(renderAll);
		} else {
			setTimeout(renderAll, 0);
		}
	}

	function reopenIssueDialog(whId, issueId, res) {
		var d = (res && res.data) ? res.data : S.ensureData(whId);
		var issue = (d.issues || []).find(function (x) { return x.id === issueId; });
		if (!issue) return;
		var dlg = issueDialog(issue);
		openDialog(dlg.title, dlg.meta, dlg.body, dlg.foot);
	}

	function reopenReceiptDialog(whId, receiptId, res) {
		var d = (res && res.data) ? res.data : S.ensureData(whId);
		var receipt = (d.receipts || []).find(function (x) { return x.id === receiptId; });
		if (!receipt) return;
		var dlg = receiptDialog(receipt);
		openDialog(dlg.title, dlg.meta, dlg.body, dlg.foot);
	}

	/* ===== Dialog (timeline) ===== */
	function openDialog(titleText, metaHtml, bodyHtml, footHtml) {
		var dialog = qs('#mkWhProtoDialog');
		var title = qs('#mkWhProtoDialogTitle');
		var meta = qs('#mkWhProtoDialogMeta');
		var body = qs('#mkWhProtoDialogBody');
		var foot = qs('#mkWhProtoDialogFoot');
		if (!dialog || !title || !meta || !body) return;
		title.textContent = titleText || 'Phiếu';
		meta.innerHTML = metaHtml || '';
		body.innerHTML = bodyHtml || '';
		if (foot) {
			foot.innerHTML = footHtml || defaultDialogFootHtml();
		}
		dialog.removeAttribute('data-mk-qc-note-cache');
		bindQcNoteInput(dialog);
		bindQcAttachmentHandlers(dialog);
		dialog.classList.add('is-open');
		dialog.setAttribute('aria-hidden', 'false');
		document.body.classList.add('mk-wh-dialog-open');
	}

	function closeDialog() {
		var dialog = qs('#mkWhProtoDialog');
		if (!dialog) return;
		dialog.classList.remove('is-open');
		dialog.setAttribute('aria-hidden', 'true');
		document.body.classList.remove('mk-wh-dialog-open');
		closeQcLightbox();
	}

	function roleBadge(roleKey) {
		if (roleKey === 'qc') return '<span class="mk-wh-proto-tag mk-wh-proto-tag--qc">QC</span>';
		return '<span class="mk-wh-proto-tag mk-wh-proto-tag--green">Quản lý kho</span>';
	}

	function getReceiptQcInfo(r) {
		var qc = r.qc || {};
		var result = String(qc.result || '');
		var note = String(qc.note || '').trim();
		var by = String(qc.by || '').trim();
		var at = String(qc.at || '').trim();
		(r.timeline || []).forEach(function (ev) {
			if (!ev || ev.role !== 'qc') {
				return;
			}
			if (!note && ev.note) {
				note = String(ev.note || '').trim();
			}
			if (!by && ev.by) {
				by = String(ev.by || '').trim();
			}
			if (!at && ev.at) {
				at = String(ev.at || '').trim();
			}
			if (!result) {
				var action = String(ev.action || '').toLowerCase();
				if (action.indexOf('không đạt') >= 0 || action.indexOf('khong dat') >= 0) {
					result = 'fail';
				} else if (action.indexOf('đạt') >= 0 || action.indexOf('dat') >= 0) {
					result = 'pass';
				}
			}
		});
		if (!result) {
			if (r.status === 'qc_passed') result = 'pass';
			if (r.status === 'qc_failed') result = 'fail';
		}
		return { result: result, note: note, by: by, at: at, images: getQcImages(r) };
	}

	function renderQcResultPanel(qcInfo, receiptId, opts) {
		opts = opts || {};
		if (opts.hidePanel || !qcInfo || !qcInfo.result) {
			return '';
		}
		var label = qcInfo.result === 'pass' ? 'Đạt' : 'Không đạt';
		var cls = qcInfo.result === 'pass' ? 'mk-wh-proto-pill--ok' : 'mk-wh-proto-pill--warn';
		var images = qcInfo.images || [];
		var galleryHtml = (!opts.hideGallery && images.length) ? renderQcImagesGallery(receiptId || '', images, false) : '';
		return '<div class="mk-wh-proto-qc-result" style="margin-bottom:14px;padding:12px 14px;border:1px solid rgba(15,23,42,0.08);border-radius:12px;background:#f8fafc;">' +
			'<div class="mk-wh-proto-dialog-section-title" style="margin-bottom:8px;">Kết quả QC</div>' +
			'<div style="margin-bottom:8px;"><span class="mk-wh-proto-pill ' + escapeHtml(cls) + '">' + escapeHtml(label) + '</span>' +
			(images.length ? ' <span class="mk-wh-proto-muted">📷 ' + images.length + ' ảnh</span>' : '') +
			'</div>' +
			(qcInfo.note
				? ('<div class="mk-wh-proto-muted" style="margin-bottom:4px;">Ghi nhận của QC:</div>' +
					'<div class="mk-wh-proto-quote">"' + escText(qcInfo.note) + '"</div>')
				: '<div class="mk-wh-proto-muted">QC chưa ghi nhận chi tiết.</div>') +
			(galleryHtml ? '<div style="margin-top:10px;">' + galleryHtml + '</div>' : '') +
			((qcInfo.by || qcInfo.at)
				? ('<div class="mk-wh-proto-muted" style="margin-top:8px;">' +
					escText(qcInfo.by || 'QC') +
					(qcInfo.at && fmtDateTime(qcInfo.at) !== '—' ? ' · ' + escText(fmtDateTime(qcInfo.at)) : '') +
					'</div>')
				: '') +
			'</div>';
	}

	function receiptDialog(r) {
		var st = RECEIPT_STATUS[r.status] || { label: r.status, cls: 'mk-wh-proto-pill' };
		var qcInfo = getReceiptQcInfo(r);
		var linesHtml = (r.lines || []).map(function (l) {
			var qcPill = l.qcResult === 'pass'
				? '<span class="mk-wh-proto-pill mk-wh-proto-pill--ok">Đạt</span>'
				: l.qcResult === 'fail'
					? '<span class="mk-wh-proto-pill mk-wh-proto-pill--warn">Không đạt</span>'
					: (r.status === 'pending_qc'
						? '<span class="mk-wh-proto-pill mk-wh-proto-pill--warn">Chờ QC</span>'
						: (r.status === 'qc_passed'
							? '<span class="mk-wh-proto-pill mk-wh-proto-pill--ok">Đạt</span>'
							: (r.status === 'qc_failed'
								? '<span class="mk-wh-proto-pill mk-wh-proto-pill--warn">Không đạt</span>'
								: '—')));
			return '<tr><td><strong>' + escText(l.name || '') + '</strong>' + (formatSkuLabel(l.sku) !== '—' ? '<div class="mk-wh-proto-muted">' + escText(formatSkuLabel(l.sku)) + '</div>' : '') + '</td>' +
				'<td>' + escText(l.lot || '') + '<br/><span class="mk-wh-proto-muted">HSD: ' + escText(l.expiry || '—') + '</span></td>' +
				'<td>' + escText(l.qty) + '</td><td>' + escText(l.location || '—') + '</td><td>' + qcPill + '</td></tr>';
		}).join('');

		var timelineHtml =
			'<div class="mk-wh-proto-timeline">' +
			((r.timeline || []).map(function (ev) {
				var evImages = (ev && ev.role === 'qc' && Array.isArray(ev.images)) ? ev.images : [];
				return '<div class="mk-wh-proto-timeline-item">' +
					'<div class="mk-wh-proto-timeline-item__head">' +
						'<strong class="mk-wh-proto-timeline-item__action">' + escText(ev.action || '—') + '</strong>' +
						roleBadge(ev.role) +
						(evImages.length ? '<span class="mk-wh-proto-muted mk-wh-proto-timeline-item__photos">📷 ' + evImages.length + ' ảnh</span>' : '') +
					'</div>' +
					'<div class="mk-wh-proto-muted">' + escText((ev.by || '—') + ' · ' + fmtDateTime(ev.at)) + '</div>' +
					(ev.note ? '<div class="mk-wh-proto-quote">"' + escText(ev.note) + '"</div>' : '') +
					renderTimelineImagesBlock(evImages) +
				'</div>';
			}).join('')) +
			'</div>';

		var role = getRole();
		var qcEditable = (r.status === 'qc_passed' || r.status === 'qc_failed') && canDoQc();
		var onPath =
			r.status !== 'cancelled' &&
			(RECEIPT_PATH.some(function (s) {
				return s.key === r.status;
			}) ||
				r.status === 'qc_failed');
		var pathHtml = onPath
			? renderStatusPath({
					path: RECEIPT_PATH,
					current: normalizeReceiptPathStatus(r.status),
					docId: r.id,
					actionKey: 'receipt-revert',
					canRevert: isWarehouseOps(role) || canDoQc(),
					branchNote: r.status === 'qc_failed' ? 'Nhánh hiện tại: QC không đạt — có thể quay về Chờ QC.' : '',
				})
			: '';

		var actions = '';
		if (r.status === 'draft' && isWarehouseOps(role)) {
			actions = '<div style="margin-top:12px;display:flex;justify-content:flex-end;gap:10px;">' +
				'<button class="mk-wh-proto-mini-btn" type="button" data-mk-action="send-qc" data-id="' + escapeHtml(r.id) + '">Gửi QC</button>' +
				'</div>';
		}
		if (r.status === 'pending_qc' && canDoQc()) {
			actions = '<div class="mk-wh-proto-dialog-section-title" style="margin-top:12px;">Ghi nhận kết quả QC</div>' +
				'<textarea class="mk-wh-proto-textarea" data-mk-qc-note="1" placeholder="Ghi chú kiểm tra (cảm quan, chứng từ, bao bì...)"></textarea>' +
				renderQcImagesGallery(r.id, getQcImages(r), true) +
				'<div class="mk-wh-proto-cta-row">' +
				'<button class="mk-wh-proto-cta mk-wh-proto-cta--pass" type="button" data-mk-action="qc-pass" data-id="' + escapeHtml(r.id) + '"><span>✔</span> Đạt</button>' +
				'<button class="mk-wh-proto-cta mk-wh-proto-cta--fail" type="button" data-mk-action="qc-fail" data-id="' + escapeHtml(r.id) + '"><span>✕</span> Không đạt</button>' +
				'</div>';
		}
		if ((r.status === 'qc_passed' || r.status === 'qc_failed') && canDoQc()) {
			actions += '<div class="mk-wh-proto-dialog-section-title" style="margin-top:12px;">Cập nhật ghi nhận QC</div>' +
				'<textarea class="mk-wh-proto-textarea" data-mk-qc-note="1" placeholder="Ghi chú kiểm tra (cảm quan, chứng từ, bao bì...)">' + escText(qcInfo.note || '') + '</textarea>' +
				renderQcImagesGallery(r.id, getQcImages(r), true);
		}
		if (r.status === 'qc_passed' && isWarehouseOps(role)) {
			actions += '<div style="margin-top:12px;display:flex;justify-content:flex-end;gap:10px;">' +
				'<button class="mk-wh-proto-mini-btn" type="button" data-mk-action="mgr-approve" data-id="' + escapeHtml(r.id) + '">Duyệt phiếu</button>' +
				'</div>';
		}
		if (r.status === 'approved' && isWarehouseOps(role)) {
			actions += '<div style="margin-top:12px;display:flex;justify-content:flex-end;gap:10px;">' +
				'<button class="mk-wh-proto-mini-btn" type="button" data-mk-action="store" data-id="' + escapeHtml(r.id) + '">Nhập kho</button>' +
				'</div>';
		}

		var footHtml = defaultDialogFootHtml();
		if (qcEditable) {
			footHtml = '<button type="button" class="mk-wh-proto-mini-btn" data-mk-dialog-close="1">Đóng</button>' +
				'<button type="button" class="mk-wh-proto-mini-btn mk-wh-qc-save-btn" data-mk-action="qc-update" data-id="' + escapeHtml(r.id) + '">Lưu ghi nhận</button>';
		}

		return {
			title: 'Phiếu nhập ' + r.id,
			meta: 'NCC: ' + escText(r.supplier) + ' · PO: ' + escText(r.poRef),
			foot: footHtml,
			body:
				'<div style="margin-bottom:10px;"><span class="' + escapeHtml(st.cls || 'mk-wh-proto-pill') + '">' + escapeHtml(st.label) + '</span></div>' +
				pathHtml +
				renderQcResultPanel(qcInfo, r.id, { hidePanel: qcEditable }) +
				'<div class="mk-wh-proto-dialog-grid">' +
					'<div class="mk-wh-proto-dialog-main-col">' +
						'<div class="mk-wh-proto-dialog-section-title">Chi tiết hàng hóa</div>' +
						'<div class="mk-wh-proto-dialog-table-wrap">' +
						'<table class="mk-wh-proto-dialog-table"><thead><tr><th>SKU</th><th>Lô / HSD</th><th>SL</th><th>Vị trí</th><th>QC</th></tr></thead><tbody>' +
						linesHtml +
						'</tbody></table>' +
						'</div>' +
						actions +
					'</div>' +
					'<div class="mk-wh-proto-dialog-timeline-col">' +
						'<div class="mk-wh-proto-dialog-section-title">Timeline trạng thái</div>' +
						timelineHtml +
					'</div>' +
				'</div>',
		};
	}

	function issueDialog(issue) {
		var st = ISSUE_STATUS[issue.status] || { label: issue.status, cls: 'mk-wh-proto-pill' };
		var linesHtml = (issue.lines || []).map(function (l) {
			return '<tr><td><strong>' + escText(l.name) + '</strong>' + (formatSkuLabel(l.sku) !== '—' ? '<div class="mk-wh-proto-muted">' + escText(formatSkuLabel(l.sku)) + '</div>' : '') + '</td>' +
				'<td>' + escText(l.lot) + '</td><td>' + escText(l.qty) + '</td></tr>';
		}).join('');
		var timelineHtml =
			'<div class="mk-wh-proto-timeline">' +
			((issue.timeline || []).map(function (ev) {
				return '<div class="mk-wh-proto-timeline-item">' +
					'<div class="mk-wh-proto-timeline-item__head">' +
						'<strong class="mk-wh-proto-timeline-item__action">' + escText(ev.action || '—') + '</strong>' +
						roleBadge(ev.role) +
					'</div>' +
					'<div class="mk-wh-proto-muted">' + escText((ev.by || '—') + ' · ' + fmtDateTime(ev.at)) + '</div>' +
					(ev.note ? '<div class="mk-wh-proto-quote">"' + escText(ev.note) + '"</div>' : '') +
				'</div>';
			}).join('')) +
			'</div>';

		var role = getRole();
		var pathStatus = normalizeIssuePathStatus(issue.status);
		var onPath =
			issue.status !== 'cancelled' &&
			issue.status !== 'rejected' &&
			pathIndex(ISSUE_PATH, pathStatus) >= 0;
		var pathHtml = onPath
			? renderStatusPath({
					path: ISSUE_PATH,
					current: pathStatus,
					docId: issue.id,
					actionKey: 'issue-revert',
					canRevert: isWarehouseOps(role),
				})
			: '';

		var actions = '';
		if (issue.status === 'draft' && isWarehouseOps(role)) {
			actions = '<div style="margin-top:12px;display:flex;justify-content:flex-end;gap:10px;">' +
				'<button class="mk-wh-proto-mini-btn" type="button" data-mk-action="issue-submit" data-id="' + escapeHtml(issue.id) + '">Chờ soạn</button>' +
				'</div>';
		}
		if ((issue.status === 'waiting_print' || issue.status === 'pending_approval') && isWarehouseOps(role)) {
			actions = '<div style="margin-top:12px;display:flex;justify-content:flex-end;gap:10px;">' +
				'<button class="mk-wh-proto-mini-btn" type="button" data-mk-action="issue-start-pick" data-id="' + escapeHtml(issue.id) + '">Bắt đầu soạn</button>' +
				'</div>';
		}
		if (issue.status === 'picking' && isWarehouseOps(role)) {
			actions = '<div style="margin-top:12px;display:flex;justify-content:flex-end;gap:10px;">' +
				'<button class="mk-wh-proto-mini-btn" type="button" data-mk-action="issue-finish-pick" data-id="' + escapeHtml(issue.id) + '">Hoàn tất soạn hàng</button>' +
				'</div>';
		}
		if ((issue.status === 'packed' || issue.status === 'approved') && isWarehouseOps(role)) {
			actions = '<div style="margin-top:12px;display:flex;justify-content:flex-end;gap:10px;">' +
				'<button class="mk-wh-proto-mini-btn" type="button" data-mk-action="issue-ship" data-id="' + escapeHtml(issue.id) + '">Xác nhận đã giao</button>' +
				'</div>';
		}

		return {
			title: 'Phiếu xuất ' + issue.id,
			meta: outboundTypePill(issue.outboundType || 'internal') + ' · ' +
				escText(getOutboundTypeMeta(issue.outboundType).customerLabel) + ': ' + escText(issue.customer) +
				' · ' + escText(getOutboundTypeMeta(issue.outboundType).soLabel) + ': ' + escText(issue.soRef || '—'),
			body:
				'<div style="margin-bottom:10px;"><span class="' + escapeHtml(st.cls || 'mk-wh-proto-pill') + '">' + escapeHtml(st.label) + '</span></div>' +
				pathHtml +
				'<div class="mk-wh-proto-dialog-grid">' +
					'<div>' +
						'<div class="mk-wh-proto-dialog-section-title">Chi tiết xuất hàng</div>' +
						'<table class="mk-wh-proto-dialog-table"><thead><tr><th>SKU</th><th>Lô</th><th>SL xuất</th></tr></thead><tbody>' +
						linesHtml +
						'</tbody></table>' +
						actions +
					'</div>' +
					'<div>' +
						'<div class="mk-wh-proto-dialog-section-title">Timeline trạng thái</div>' +
						timelineHtml +
					'</div>' +
				'</div>',
		};
	}

	function patchReceipt(id, fn) {
		var whId = getWhId();
		if (!whId) return;
		var d = S.ensureData(whId);
		var next = (d.receipts || []).map(function (r) {
			return r.id === id ? fn(Object.assign({}, r, { lines: (r.lines || []).slice(), timeline: (r.timeline || []).slice() })) : r;
		});
		S.warehouseDataActions.setReceipts(whId, next);
	}

	function patchIssue(id, fn) {
		var whId = getWhId();
		if (!whId) return;
		var d = S.ensureData(whId);
		var next = (d.issues || []).map(function (i) {
			return i.id === id ? fn(Object.assign({}, i, { lines: (i.lines || []).slice(), timeline: (i.timeline || []).slice() })) : i;
		});
		S.warehouseDataActions.setIssues(whId, next);
	}

	function addTimeline(list, action, role, note) {
		list.push({ at: S.nowISO(), by: roleActorName(role), role: role === 'qc' ? 'qc' : 'manager', action: action, note: note || undefined });
	}

	function summarizeLines(lines) {
		var rows = lines || [];
		var totalQty = 0;
		var parts = [];
		rows.forEach(function (l) {
			var qty = Number(l.passedQty != null ? l.passedQty : l.qty) || 0;
			totalQty += qty;
			var name = decodeEntities(l.name || l.sku || 'Hàng hoá');
			var lot = decodeEntities(l.lot || '');
			parts.push({
				label: name + (lot ? ' · Lô ' + lot : ''),
				qty: qty,
				sku: formatSkuLabel(l.sku),
				location: decodeEntities(l.location || ''),
			});
		});
		return {
			count: rows.length,
			totalQty: totalQty,
			parts: parts,
			text: rows.length
				? rows.length + ' dòng · SL ' + totalQty
				: 'Không có dòng hàng',
		};
	}

	function classifyAuditAction(action) {
		var a = String(action || '').toLowerCase();
		if (/huỷ|huy|cancel|từ chối|tu choi|reject/.test(a)) return 'cancel';
		if (/quay lại|revert|rollback/.test(a)) return 'update';
		if (/tạo|tao|create/.test(a)) return 'create';
		return 'update';
	}

	function collectAuditEvents(kind) {
		var whId = getWhId();
		if (!whId) return [];
		var d = S.ensureData(whId);
		var events = [];
		var docs = kind === 'outbound' ? d.issues || [] : d.receipts || [];
		docs.forEach(function (doc) {
			var statusMap = kind === 'outbound' ? ISSUE_STATUS : RECEIPT_STATUS;
			var st = statusMap[doc.status] || { label: doc.status || '—' };
			var lineSum = summarizeLines(doc.lines || []);
			var timeline = (doc.timeline && doc.timeline.length)
				? doc.timeline
				: [{
					at: doc.createdAt || '',
					by: doc.createdBy || '—',
					role: 'manager',
					action: kind === 'outbound' ? 'Tạo phiếu xuất' : 'Tạo phiếu nhập',
				}];
			timeline.forEach(function (ev, idx) {
				events.push({
					kind: kind,
					docId: doc.id || doc.code || '—',
					docStatus: st.label || doc.status || '—',
					partner: decodeEntities(doc.supplier || doc.customer || doc.vendor || '—'),
					ref: decodeEntities(doc.poRef || doc.soRef || doc.po || '—'),
					at: ev.at || doc.createdAt || '',
					by: ev.by || doc.createdBy || '—',
					role: ev.role || 'manager',
					action: ev.action || 'Cập nhật',
					note: ev.note || '',
					lineSummary: lineSum.text,
					lines: idx === 0 || /tạo|tao|create|soạn|soan|nhập|nhap|giao/i.test(String(ev.action || ''))
						? lineSum.parts
						: [],
					className: classifyAuditAction(ev.action),
				});
			});
		});
		events.sort(function (a, b) {
			return String(b.at || '').localeCompare(String(a.at || ''));
		});
		return events;
	}

	function ensureAuditHistoryModal() {
		var modal = qs('#mkWhAuditHistoryModal');
		if (modal) return modal;
		modal = document.createElement('div');
		modal.id = 'mkWhAuditHistoryModal';
		modal.className = 'mk-wh-audit-modal';
		modal.setAttribute('aria-hidden', 'true');
		modal.innerHTML =
			'<div class="mk-wh-audit-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="mkWhAuditHistoryTitle">' +
				'<div class="mk-wh-audit-modal__head">' +
					'<div>' +
						'<h3 id="mkWhAuditHistoryTitle">Lịch sử chỉnh sửa</h3>' +
						'<p class="mk-wh-audit-modal__sub">Nhật ký tạo / cập nhật phiếu nhập &amp; xuất — theo dõi thay đổi để phòng gian lận</p>' +
					'</div>' +
					'<button type="button" class="mk-wh-audit-modal__close" data-mk-audit-close="1" aria-label="Đóng">&times;</button>' +
				'</div>' +
				'<div class="mk-wh-audit-modal__tabs" role="tablist">' +
					'<button type="button" class="mk-wh-audit-tab is-active" data-mk-audit-tab="inbound" role="tab" aria-selected="true">Nhập kho</button>' +
					'<button type="button" class="mk-wh-audit-tab" data-mk-audit-tab="outbound" role="tab" aria-selected="false">Xuất kho</button>' +
				'</div>' +
				'<div class="mk-wh-audit-modal__toolbar">' +
					'<input type="search" class="mk-wh-audit-modal__search" id="mkWhAuditSearch" placeholder="Tìm mã phiếu, hành động, người thao tác, hàng hoá..." />' +
					'<div class="mk-wh-audit-modal__meta" id="mkWhAuditCount">0 sự kiện</div>' +
				'</div>' +
				'<div class="mk-wh-audit-modal__body" id="mkWhAuditBody"></div>' +
			'</div>';
		document.body.appendChild(modal);
		modal.addEventListener('click', function (e) {
			var t = e.target;
			if (t === modal || (t && t.getAttribute && t.getAttribute('data-mk-audit-close') === '1')) {
				closeAuditHistoryModal();
			}
		});
		modal.querySelectorAll('[data-mk-audit-tab]').forEach(function (btn) {
			btn.addEventListener('click', function () {
				modal.querySelectorAll('[data-mk-audit-tab]').forEach(function (b) {
					var on = b === btn;
					b.classList.toggle('is-active', on);
					b.setAttribute('aria-selected', on ? 'true' : 'false');
				});
				renderAuditHistoryList();
			});
		});
		var search = modal.querySelector('#mkWhAuditSearch');
		if (search) {
			search.addEventListener('input', function () {
				renderAuditHistoryList();
			});
		}
		return modal;
	}

	function closeAuditHistoryModal() {
		var modal = qs('#mkWhAuditHistoryModal');
		if (!modal) return;
		modal.classList.remove('is-open');
		modal.setAttribute('aria-hidden', 'true');
		document.body.classList.remove('mk-wh-audit-open');
	}

	function renderAuditHistoryList() {
		var modal = ensureAuditHistoryModal();
		var active = modal.querySelector('.mk-wh-audit-tab.is-active');
		var kind = active ? active.getAttribute('data-mk-audit-tab') : 'inbound';
		var q = String((qs('#mkWhAuditSearch', modal) || {}).value || '').trim().toLowerCase();
		var events = collectAuditEvents(kind).filter(function (ev) {
			if (!q) return true;
			var hay = [
				ev.docId, ev.action, ev.by, ev.note, ev.partner, ev.ref, ev.docStatus, ev.lineSummary,
			].concat((ev.lines || []).map(function (l) { return l.label + ' ' + l.sku; })).join(' ').toLowerCase();
			return hay.indexOf(q) >= 0;
		});
		var countEl = qs('#mkWhAuditCount', modal);
		if (countEl) {
			countEl.textContent = events.length + ' sự kiện · ' + (kind === 'outbound' ? 'Xuất kho' : 'Nhập kho');
		}
		var body = qs('#mkWhAuditBody', modal);
		if (!body) return;
		if (!events.length) {
			body.innerHTML = '<div class="mk-wh-audit-empty">Chưa có lịch sử cho tab này' + (q ? ' (theo bộ lọc hiện tại)' : '') + '.</div>';
			return;
		}
		body.innerHTML =
			'<ul class="mk-wh-audit-list">' +
			events.map(function (ev) {
				var linesHtml = '';
				if (ev.lines && ev.lines.length) {
					linesHtml =
						'<ul class="mk-wh-audit-lines">' +
						ev.lines.map(function (l) {
							return (
								'<li><span><strong>' +
								escText(l.label) +
								'</strong>' +
								(l.sku && l.sku !== '—' ? ' <span class="mk-wh-proto-muted">(' + escText(l.sku) + ')</span>' : '') +
								(l.location ? ' · ' + escText(l.location) : '') +
								'</span><span class="mk-wh-audit-lines__qty">SL ' +
								escText(String(l.qty)) +
								'</span></li>'
							);
						}).join('') +
						'</ul>';
				}
				return (
					'<li class="mk-wh-audit-item mk-wh-audit-item--' +
					escText(ev.className) +
					'">' +
					'<span class="mk-wh-audit-item__dot" aria-hidden="true"></span>' +
					'<article class="mk-wh-audit-card">' +
					'<div class="mk-wh-audit-card__top">' +
					'<div class="mk-wh-audit-card__action">' +
					escText(ev.action) +
					'</div>' +
					'<div class="mk-wh-audit-card__time">' +
					escText(fmtDateTime(ev.at)) +
					'</div>' +
					'</div>' +
					'<div class="mk-wh-audit-card__meta">' +
					'<span class="mk-wh-audit-pill mk-wh-audit-pill--doc">' +
					escText(ev.docId) +
					'</span>' +
					'<span class="mk-wh-audit-pill">' +
					escText(ev.docStatus) +
					'</span>' +
					'<span>' +
					escText(ev.by) +
					' · ' +
					escText(roleBadgeLabel(ev.role)) +
					'</span>' +
					(ev.partner && ev.partner !== '—' ? '<span>' + escText(ev.partner) + '</span>' : '') +
					(ev.ref && ev.ref !== '—' ? '<span>Ref: ' + escText(ev.ref) + '</span>' : '') +
					'<span>' +
					escText(ev.lineSummary) +
					'</span>' +
					'</div>' +
					(ev.note
						? '<div class="mk-wh-audit-card__note">' + escText(ev.note) + '</div>'
						: '') +
					linesHtml +
					'</article></li>'
				);
			}).join('') +
			'</ul>';
	}

	function roleBadgeLabel(role) {
		if (role === 'qc') return 'QC';
		if (role === 'keeper' || role === 'stock') return 'Thủ kho';
		return 'Quản lý kho';
	}

	function openAuditHistoryModal(preferredTab) {
		var modal = ensureAuditHistoryModal();
		var tab = preferredTab === 'outbound' ? 'outbound' : 'inbound';
		modal.querySelectorAll('[data-mk-audit-tab]').forEach(function (b) {
			var on = b.getAttribute('data-mk-audit-tab') === tab;
			b.classList.toggle('is-active', on);
			b.setAttribute('aria-selected', on ? 'true' : 'false');
		});
		var search = qs('#mkWhAuditSearch', modal);
		if (search) search.value = '';
		renderAuditHistoryList();
		modal.classList.add('is-open');
		modal.setAttribute('aria-hidden', 'false');
		document.body.classList.add('mk-wh-audit-open');
	}

	function formatLocationNote(lines) {
		var locations = [];
		(lines || []).forEach(function (l) {
			var loc = (l.location || '').trim();
			if (loc && locations.indexOf(loc) < 0) {
				locations.push(loc);
			}
		});
		if (!locations.length) {
			return undefined;
		}
		return 'Vị trí: ' + locations.join(', ');
	}

	function addStockFromReceiptLines(whId, lines) {
		var d = S.ensureData(whId);
		var stock = (d.stock || []).slice();
		(lines || []).forEach(function (l) {
			var passed = l.passedQty != null ? l.passedQty : l.qty;
			if (!passed || passed <= 0) return;
			var idx = stock.findIndex(function (s) { return s.sku === l.sku && s.lot === l.lot; });
			if (idx >= 0) {
				var patch = {
					qty: (Number(stock[idx].qty) || 0) + passed,
					expiry: stock[idx].expiry || l.expiry,
					mfg: stock[idx].mfg || l.mfg || '',
					name: stock[idx].name || l.name,
				};
				if ((l.location || '').trim()) {
					patch.location = l.location.trim();
				}
				stock[idx] = Object.assign({}, stock[idx], patch);
			} else {
				stock.push({
					sku: l.sku,
					name: l.name,
					lot: l.lot,
					mfg: l.mfg || '',
					expiry: l.expiry || '—',
					qty: passed,
					location: (l.location || '').trim() || '—',
					price: l.price || 0,
				});
			}
		});
		S.warehouseDataActions.setStock(whId, stock);
	}

	function deductStockFromIssueLines(whId, lines) {
		var d = S.ensureData(whId);
		var stock = (d.stock || []).slice();
		(lines || []).forEach(function (l) {
			var qtyLeft = Number(l.qty) || 0;
			if (qtyLeft <= 0) return;
			var lot = String(l.lot || '');
			var sku = String(l.sku || '');
			var name = String(l.name || '');
			var candidates = stock.map(function (s, idx) { return { s: s, idx: idx }; }).filter(function (row) {
				if (sku && lot) return row.s.sku === sku && row.s.lot === lot;
				if (sku) return row.s.sku === sku;
				if (name) return String(row.s.name || '') === name;
				return false;
			});
			if (!candidates.length) {
				// Oversell with no existing row: create negative stock line (keep product visible).
				stock.push({
					sku: sku || 'UNK',
					name: name || sku || '—',
					lot: lot || '—',
					qty: -qtyLeft,
					expiry: l.expiry || '—',
					mfg: l.mfg || '',
					price: l.price || 0,
					location: l.location || '',
				});
				return;
			}
			// Deduct across lots; allow last lot to go negative so product stays listed.
			candidates.forEach(function (row, i) {
				if (qtyLeft <= 0) return;
				var avail = Number(row.s.qty) || 0;
				var isLast = i === candidates.length - 1;
				var deduct = isLast ? qtyLeft : Math.min(Math.max(avail, 0), qtyLeft);
				if (!isLast && deduct <= 0) return;
				stock[row.idx] = Object.assign({}, stock[row.idx], { qty: avail - deduct });
				qtyLeft -= deduct;
			});
		});
		S.warehouseDataActions.setStock(whId, stock);
	}

	/* ===== Create receipt/issue modal (Prototype UI) ===== */
	function closeModal() {
		var modal = qs('#mkWhProtoModal');
		if (!modal) return;
		qsa('[data-mk-line-product="1"], [name="lotKey"]', modal).forEach(destroyProductSelect2);
		modal.classList.remove('is-open');
		modal.setAttribute('aria-hidden', 'true');
	}

	function creditStockToWarehouse(whId, lines) {
		if (!whId) return;
		var d = S.ensureData(whId);
		var stock = (d.stock || []).slice();
		(lines || []).forEach(function (l) {
			var qtyAdd = Number(l.qty) || 0;
			if (qtyAdd <= 0) return;
			var idx = stock.findIndex(function (s) {
				return String(s.sku || '') === String(l.sku || '') && String(s.lot || '') === String(l.lot || '');
			});
			if (idx >= 0) {
				stock[idx] = Object.assign({}, stock[idx], {
					qty: (Number(stock[idx].qty) || 0) + qtyAdd,
					name: stock[idx].name || l.name,
					expiry: stock[idx].expiry || l.expiry || '—',
					mfg: stock[idx].mfg || l.mfg || '',
					price: stock[idx].price || l.price || 0,
				});
			} else {
				stock.push({
					sku: l.sku,
					name: l.name,
					lot: l.lot,
					mfg: l.mfg || '',
					expiry: l.expiry || '—',
					qty: qtyAdd,
					location: 'NEW',
					price: Number(l.price) || 0,
				});
			}
		});
		S.warehouseDataActions.setStock(whId, stock);
	}

	function modalSchema(tabKey, outboundType) {
		if (tabKey === 'outbound-type') {
			return {
				tabKey: 'outbound-type',
				title: 'Tạo phiếu xuất kho',
				submitLabel: 'Tiếp theo',
				fields: [
					{ type: 'hint', full: true, text: 'Chọn loại xuất kho. Bước tiếp theo bạn điền chi tiết phiếu xuất.' },
					{
						name: 'outboundType',
						label: 'Xuất kho loại nào?',
						type: 'select',
						required: true,
						full: true,
						options: OUTBOUND_TYPE_PICKER.map(function (key, idx) {
							return { value: key, label: OUTBOUND_TYPES[key].label, selected: idx === 0 };
						}),
					},
				],
			};
		}
		if (tabKey === 'inbound') {
			return {
				tabKey: 'inbound',
				title: 'Tạo phiếu nhập kho',
				submitLabel: 'Tạo phiếu',
				fields: [
					{ name: 'supplier', label: 'Nhà cung cấp', required: true, placeholder: 'VD: CTY Dược Hậu Giang' },
					{ name: 'po', label: 'Mã PO', required: true, placeholder: 'VD: PO-2026-0155' },
					{ type: 'lines', label: 'Danh sách hàng nhập', full: true },
				],
			};
		}
		var outMeta = getOutboundTypeMeta(outboundType || 'internal');
		var customerField = (outboundType === 'transfer')
			? {
				name: 'customer',
				label: outMeta.customerLabel,
				required: true,
				type: 'select',
				options: getDestinationWarehouseOptions(),
			}
			: { name: 'customer', label: outMeta.customerLabel, required: true, placeholder: '' };
		var fields = [
			customerField,
			{ name: 'so', label: outMeta.soLabel, required: false, placeholder: outMeta.soPlaceholder },
		];
		if (outboundType === 'internal') {
			fields.push({
				name: 'exportTypeLabel',
				label: 'Loại xuất',
				type: 'select',
				required: true,
				full: true,
				options: INTERNAL_EXPORT_TYPE_OPTIONS,
			});
			fields.push({
				name: 'notes',
				label: 'Ghi chú',
				type: 'textarea',
				required: false,
				full: true,
				placeholder: 'Ghi chú in trên phiếu (lô hàng, lý do chi tiết…)',
			});
		}
		fields.unshift({
			name: 'outboundType',
			type: 'hidden',
			value: outboundType || 'internal',
		});
		fields.push({ type: 'lines', mode: 'outbound', label: 'Danh sách hàng xuất', full: true });
		return {
			tabKey: 'outbound',
			outboundType: outboundType || 'internal',
			title: 'Tạo phiếu xuất — ' + outMeta.short,
			submitLabel: 'Tạo phiếu',
			fields: fields,
		};
	}

	function renderModalFields(fields) {
		return (fields || []).map(function (f) {
			var full = f.full ? ' mk-wh-proto-field--full' : '';
			var req = f.required ? ' *' : '';
			if (f.type === 'hint') {
				return '<p class="mk-wh-proto-form-hint' + full + '">' + escapeHtml(f.text || '') + '</p>';
			}
			if (f.type === 'lines') {
				var isOutboundLines = f.mode === 'outbound';
				var linesCls = 'mk-wh-proto-lines mk-wh-proto-lines--catalog' + (isOutboundLines ? ' mk-wh-proto-lines--outbound' : '');
				var locTh = isOutboundLines ? '' : '<th class="mk-wh-proto-col-loc">Vị trí</th>';
				var linesTitle = f.label || 'Danh sách hàng';
				return '<div class="mk-wh-proto-field mk-wh-proto-field--full mk-wh-proto-field--lines">' +
					'<div class="mk-wh-proto-lines-section">' +
					'<div class="mk-wh-proto-lines-section__head">' +
					'<div class="mk-wh-proto-lines-section__titles">' +
					'<span class="mk-wh-proto-lines-section__label">' + escapeHtml(linesTitle) + '</span>' +
					'<span class="mk-wh-proto-lines-section__hint">Tìm và thêm hàng từ danh mục — nhập lô / SL / NSX / HSD trên từng dòng</span>' +
					'</div>' +
					'</div>' +
					'<div class="' + linesCls + '" data-mk-lines="1" data-mk-lines-mode="' + (isOutboundLines ? 'outbound' : 'inbound') + '">' +
					'<div class="mk-wh-proto-lines__quick" role="search">' +
					'<label class="mk-wh-proto-lines__quick-label" for="mkWhLinesQuickSearch">Tìm hàng hoá / SKU</label>' +
					'<select id="mkWhLinesQuickSearch" class="mk-wh-proto-lines-quick-select" data-mk-lines-quick="1" title="Tìm và thêm hàng hoá"></select>' +
					'</div>' +
					'<div class="mk-wh-proto-lines__tableWrap"><table class="mk-wh-proto-lines__table" role="table"><thead><tr>' +
					'<th class="mk-wh-proto-col-name">Tên hàng *</th><th class="mk-wh-proto-col-sku">SKU</th>' +
					'<th class="mk-wh-proto-col-unit">Đơn vị</th>' +
					'<th class="mk-wh-proto-col-lot">Lô *</th><th class="mk-wh-proto-col-qty">SL *</th>' +
					'<th class="mk-wh-proto-col-date">NSX</th><th class="mk-wh-proto-col-date">HSD</th>' +
					locTh + '<th class="mk-wh-proto-col-act"></th>' +
					'</tr></thead><tbody data-mk-lines-body="1"></tbody></table></div></div></div></div>';
			}
			if (f.type === 'checkbox') {
				return '<div class="mk-wh-proto-field mk-wh-proto-field--check' + full + '"><label class="mk-wh-proto-check">' +
					'<span class="mk-wh-proto-check__box"><input class="mk-wh-proto-check__input" type="checkbox" name="' + escapeHtml(f.name) + '" value="1"' +
					(f.checked ? ' checked="checked"' : '') + ' />' +
					'<span class="mk-wh-proto-check__visual" aria-hidden="true"><svg class="mk-wh-proto-check__icon" width="12" height="12" viewBox="0 0 12 12" fill="none">' +
					'<path d="M2.2 6.1 4.8 8.7 9.8 3.3" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg></span></span>' +
					'<span class="mk-wh-proto-check__text"><span class="mk-wh-proto-check__label">' + escapeHtml(f.label || '') + '</span>' +
					(f.hint ? '<span class="mk-wh-proto-check__hint">' + escapeHtml(f.hint) + '</span>' : '') +
					'</span></label></div>';
			}
			if (f.type === 'hidden') {
				return '<input type="hidden" name="' + escapeHtml(f.name || '') + '" value="' + escapeHtml(f.value || '') + '" />';
			}
			var input;
			if (f.type === 'select') {
				input = '<select name="' + escapeHtml(f.name) + '"' + (f.required ? ' required' : '') + '>' +
					(f.options || []).map(function (o) {
						return '<option value="' + escapeHtml(o.value) + '"' + (o.selected ? ' selected="selected"' : '') + '>' + escapeHtml(o.label) + '</option>';
					}).join('') + '</select>';
			} else if (f.type === 'textarea') {
				input = '<textarea name="' + escapeHtml(f.name) + '" rows="3"' +
					(f.required ? ' required' : '') +
					' placeholder="' + escapeHtml(f.placeholder || '') + '"></textarea>';
			} else {
				input = '<input type="' + escapeHtml(f.type || 'text') + '" name="' + escapeHtml(f.name) + '"' +
					(f.required ? ' required' : '') + ' placeholder="' + escapeHtml(f.placeholder || '') + '" />';
			}
			return '<div class="mk-wh-proto-field' + full + '"><label>' + escapeHtml(f.label) + req + '</label>' + input + '</div>';
		}).join('');
	}

	function guessSkuFromName(name, idx) {
		var clean = String(name || '').trim();
		if (!clean) return 'SKU-' + String(idx).padStart(2, '0');
		var words = clean.split(/\s+/).filter(Boolean);
		var initials = words.slice(0, 2).map(function (w) { return (w[0] || '').toUpperCase(); }).join('');
		var digits = (clean.match(/\d+/) || [])[0] || '';
		return (initials || 'SP') + (digits ? '-' + digits : '') + '-' + String(idx).padStart(2, '0');
	}

	function getStockProductCatalog(whId) {
		if (typeof window !== 'undefined' && window.MK_WH_PRODUCT_CATALOG && window.MK_WH_PRODUCT_CATALOG.length) {
			return window.MK_WH_PRODUCT_CATALOG.slice();
		}
		var d = S.ensureData(whId);
		var seen = {};
		var list = [];
		(d.stock || []).forEach(function (s) {
			if (!s.sku || seen[s.sku]) return;
			seen[s.sku] = true;
			list.push({ id: 0, sku: s.sku, name: s.name || s.sku, price: s.price || 0 });
		});
		list.sort(function (a, b) {
			return String(a.name).localeCompare(String(b.name), 'vi');
		});
		return list;
	}

	function productSelectHtml(catalog, selectedId) {
		var opts = '<option value="">— Tìm / chọn sản phẩm —</option>' +
			catalog.map(function (p) {
				var id = String(p.id || '');
				var name = decodeEntities(p.name || '');
				var sku = decodeEntities(p.sku || '');
				var unit = decodeEntities(p.unit || '');
				var sel = id && id === String(selectedId) ? ' selected="selected"' : '';
				var label = name + (sku ? ' · ' + sku : ' (chưa có SKU)');
				return '<option value="' + escapeHtml(id) + '" data-sku="' + escapeHtml(sku) +
					'" data-name="' + escapeHtml(name) + '" data-price="' + escapeHtml(String(p.price || 0)) +
					'" data-unit="' + escapeHtml(unit) + '"' + sel + '>' +
					escapeHtml(label) + '</option>';
			}).join('');
		return '<select class="mk-wh-proto-product-select" data-mk-line-product="1">' + opts + '</select>';
	}

	function getJq() {
		return typeof window !== 'undefined' && window.jQuery ? window.jQuery : null;
	}

	function destroyProductSelect2(el) {
		var $ = getJq();
		if (!$ || !el) return;
		var $el = $(el);
		if (!$el.data('mkSelect2Applied')) return;
		try {
			$el.off('.mkWhProduct');
			if ($.fn.select2) $el.select2('destroy');
		} catch (e) { /* ignore */ }
		$el.removeData('mkSelect2Applied');
	}

	function applyProductSelect2(el) {
		applySearchableSelect2(el, '— Tìm / chọn sản phẩm —', 'mk-wh-proto-product-select-s2');
	}

	function applySearchableSelect2(el, placeholder, containerClass) {
		var $ = getJq();
		if (!$ || !el || !$.fn.select2) return;
		var $el = $(el);
		if ($el.data('mkSelect2Applied')) return;
		$el.data('mkSelect2Applied', true);
		try {
			$el.select2({
				placeholder: placeholder || '— Chọn —',
				allowClear: true,
				width: '100%',
				minimumResultsForSearch: 0,
				minimumInputLength: 0,
				formatNoMatches: function () { return 'Không tìm thấy sản phẩm'; },
				formatSearching: function () { return 'Đang tìm...'; },
				dropdownCssClass: 'mk-wh-proto-s2-drop mk-wh-proto-s2-quick'
			});
			var inst = $el.data('select2');
			if (inst && inst.container && containerClass) {
				inst.container.addClass(containerClass);
			}
			$el.on('open.mkWhProduct select2-open.mkWhProduct', function () {
				var instance = $el.data('select2');
				if (instance && instance.dropdown) {
					instance.dropdown.css('z-index', 1000002);
				}
			});
		} catch (e) { /* plain select fallback */ }
	}

	function loadWhProductCatalogAsync() {
		var $ = getJq();
		if (typeof window !== 'undefined' && window.MK_WH_PRODUCT_CATALOG && window.MK_WH_PRODUCT_CATALOG.length) {
			if ($ && $.Deferred) {
				return $.Deferred().resolve(window.MK_WH_PRODUCT_CATALOG.slice()).promise();
			}
			return Promise.resolve(window.MK_WH_PRODUCT_CATALOG.slice());
		}
		var catalog = getStockProductCatalog(getWhId());
		if (catalog.length) {
			if ($ && $.Deferred) {
				return $.Deferred().resolve(catalog).promise();
			}
			return Promise.resolve(catalog);
		}
		if (typeof app !== 'undefined' && app.request && $ && $.Deferred) {
			var d = $.Deferred();
			app.request.post({ data: { module: 'Inventory', action: 'ProductCatalog' } }).then(function (err, res) {
				var list = !err && res && res.products ? res.products : [];
				if (list.length && typeof window !== 'undefined') {
					window.MK_WH_PRODUCT_CATALOG = list;
				}
				d.resolve(list);
			});
			return d.promise();
		}
		if ($ && $.Deferred) {
			return $.Deferred().resolve([]).promise();
		}
		return Promise.resolve([]);
	}

	function fillInboundQuickSearchOptions(selectEl, catalog) {
		if (!selectEl) return;
		var html = '<option value=""></option>';
		(catalog || []).forEach(function (p) {
			var id = String(p.id || '');
			if (!id || id === '0') return;
			var name = decodeEntities(p.name || '');
			var sku = decodeEntities(p.sku || '');
			var unit = decodeEntities(p.unit || '');
			var needsQc = !!(p.needsQc || p.needs_qc);
			var label = name + (sku ? ' · ' + sku : ' (chưa có mã hàng)') + (needsQc ? ' · QC' : '');
			html += '<option value="' + escapeHtml(id) + '" data-sku="' + escapeHtml(sku) +
				'" data-name="' + escapeHtml(name) + '" data-price="' + escapeHtml(String(p.price || 0)) +
				'" data-unit="' + escapeHtml(unit) + '" data-needs-qc="' + (needsQc ? '1' : '0') + '">' +
				escapeHtml(label) + '</option>';
		});
		selectEl.innerHTML = html;
	}

	function fillOutboundQuickSearchOptions(selectEl, lots) {
		if (!selectEl) return;
		var html = '<option value=""></option>';
		(lots || []).forEach(function (s) {
			var name = decodeEntities(s.name || s.sku || '');
			var sku = decodeEntities(s.sku || '');
			var lot = decodeEntities(s.lot || '').replace(/^\s*lô\s+/i, '').trim();
			var key = String(sku || '') + '|' + String(lot || '');
			var qty = Number(s.qty) || 0;
			var mfg = toDateInputValue(decodeEntities(s.mfg || ''));
			var expiry = toDateInputValue(decodeEntities(s.expiry || s.exp || ''));
			var label = name + (lot ? ' · Lô ' + lot : '') + ' · còn ' + qty;
			html += '<option value="' + escapeHtml(key) + '" data-sku="' + escapeHtml(sku) +
				'" data-name="' + escapeHtml(name) + '" data-lot="' + escapeHtml(lot) +
				'" data-qty="' + escapeHtml(String(qty)) +
				'" data-unit="' + escapeHtml(lookupUnitFromCatalog(sku, name)) +
				'" data-mfg="' + escapeHtml(mfg) + '" data-expiry="' + escapeHtml(expiry) + '">' +
				escapeHtml(label) + '</option>';
		});
		selectEl.innerHTML = html;
	}

	function mountLinesQuickSearch(form, mode, options, addRowFn) {
		var quickSel = form.querySelector('[data-mk-lines-quick="1"]');
		if (!quickSel) return;
		var $ = getJq();
		var linesWrap = form.querySelector('[data-mk-lines="1"]');

		if (mode === 'outbound') {
			fillOutboundQuickSearchOptions(quickSel, options);
		} else {
			fillInboundQuickSearchOptions(quickSel, options);
		}

		if (!options || !options.length) {
			if (linesWrap) {
				var warn = document.createElement('p');
				warn.className = 'mk-wh-proto-lines__warn';
				warn.textContent = mode === 'outbound'
					? 'Chưa có tồn kho — hãy nhập kho trước để xuất.'
					: 'Chưa có sản phẩm trong Products & Services — hãy tạo sản phẩm trước.';
				quickSel.parentNode.insertBefore(warn, quickSel);
			}
			quickSel.disabled = true;
			return;
		}

		destroyProductSelect2(quickSel);
		quickSel.removeAttribute('disabled');
		applySearchableSelect2(
			quickSel,
			mode === 'outbound' ? 'Tìm lô hàng trong kho…' : 'Tìm hàng hoá / mã SKU…',
			'mk-wh-proto-lines-quick-s2'
		);

		if (!$) return;
		$(quickSel).off('change.mkWhLinesQuick').on('change.mkWhLinesQuick', function () {
			var val = String($(this).val() || '').trim();
			if (!val) return;
			var $opt = $(this).find('option:selected').first();
			if (mode === 'outbound') {
				addRowFn({
					lotKey: val,
					sku: decodeEntities($opt.attr('data-sku') || ''),
					lot: decodeEntities($opt.attr('data-lot') || ''),
					name: decodeEntities($opt.attr('data-name') || ''),
					unit: decodeEntities($opt.attr('data-unit') || ''),
					mfg: decodeEntities($opt.attr('data-mfg') || ''),
					expiry: decodeEntities($opt.attr('data-expiry') || ''),
					qty: 1
				});
			} else {
				addRowFn({
					productId: val,
					sku: decodeEntities($opt.attr('data-sku') || ''),
					name: decodeEntities($opt.attr('data-name') || ''),
					unit: decodeEntities($opt.attr('data-unit') || ''),
					price: parseFloat($opt.attr('data-price') || '0') || 0,
					needsQc: $opt.attr('data-needs-qc') === '1',
					qty: 1
				});
			}
			var self = this;
			setTimeout(function () {
				try { $(self).select2('val', ''); } catch (ignore) { self.value = ''; }
			}, 60);
			var bodyEl = form.querySelector('[data-mk-lines-body="1"]');
			if (bodyEl && bodyEl.lastElementChild) {
				var qtyInput = bodyEl.lastElementChild.querySelector('[data-mk-line-qty="1"]');
				if (qtyInput) {
					try { qtyInput.focus(); qtyInput.select(); } catch (ignoreFocus) { /* ignore */ }
				}
			}
		});
	}

	function lookupUnitFromCatalog(sku, name) {
		var cat = getStockProductCatalog(getWhId()) || [];
		var skuKey = String(sku || '').trim();
		var nameKey = String(name || '').trim();
		var i;
		for (i = 0; i < cat.length; i++) {
			if (skuKey && String(cat[i].sku || '').trim() === skuKey) {
				return decodeEntities(cat[i].unit || '');
			}
		}
		for (i = 0; i < cat.length; i++) {
			if (nameKey && String(cat[i].name || '').trim() === nameKey) {
				return decodeEntities(cat[i].unit || '');
			}
		}
		return '';
	}

	function syncLineSkuFromSelect(selectEl) {
		if (!selectEl) return;
		var row = selectEl.closest('[data-mk-line="1"]');
		if (!row) return;
		var skuEl = row.querySelector('[data-mk-line-sku="1"]');
		var unitEl = row.querySelector('[data-mk-line-unit="1"]');
		var opt = selectEl.options[selectEl.selectedIndex];
		var sku = (opt && opt.getAttribute('data-sku')) || '';
		var unit = (opt && opt.getAttribute('data-unit')) || '';
		if (skuEl) {
			skuEl.value = sku;
			skuEl.placeholder = sku ? 'SKU' : 'Chưa có SKU';
		}
		if (unitEl) {
			unitEl.value = unit;
			unitEl.placeholder = unit ? 'Đơn vị' : '—';
		}
	}

	function bindInboundLineRows(form) {
		var bodyEl = form.querySelector('[data-mk-lines-body="1"]');
		if (!bodyEl) return;

		function addRow(preset) {
			var p = preset || {};
			var productId = p.productId || p.product_id || '';
			var skuVal = p.sku || '';
			var unitVal = p.unit || '';
			var nameVal = p.name || '';
			var priceVal = p.price != null ? p.price : '';
			var needsQc = !!(p.needsQc || p.needs_qc);
			bodyEl.insertAdjacentHTML('beforeend',
				'<tr class="mk-wh-proto-lines__row" data-mk-line="1"' + (needsQc ? ' data-needs-qc="1"' : '') + '>' +
				'<td>' +
				'<input type="hidden" data-mk-line-product-id="1" value="' + escapeHtml(productId) + '" />' +
				'<input type="hidden" data-mk-line-price="1" value="' + escapeHtml(String(priceVal)) + '" />' +
				'<input type="hidden" data-mk-line-needs-qc="1" value="' + (needsQc ? '1' : '0') + '" />' +
				'<div class="mk-wh-proto-line-name">' +
				'<input type="text" data-mk-line-name="1" value="' + escapeHtml(nameVal) + '" readonly tabindex="-1" class="mk-wh-proto-sku-readonly" />' +
				(needsQc ? '<span class="mk-wh-proto-qc-badge" title="Cần QC — qua QC trước khi vào tồn">QC</span>' : '') +
				'</div>' +
				'</td>' +
				'<td class="mk-wh-proto-col-sku"><input type="text" data-mk-line-sku="1" value="' + escapeHtml(skuVal) + '" readonly tabindex="-1" class="mk-wh-proto-sku-readonly" placeholder="SKU" /></td>' +
				'<td class="mk-wh-proto-col-unit"><input type="text" data-mk-line-unit="1" value="' + escapeHtml(unitVal) + '" readonly tabindex="-1" class="mk-wh-proto-sku-readonly" placeholder="—" /></td>' +
				'<td class="mk-wh-proto-col-lot"><input type="text" data-mk-line-lot="1" value="' + escapeHtml(p.lot || '') + '" placeholder="LOT-2605A" /></td>' +
				'<td class="mk-wh-proto-col-qty"><input type="number" min="1" step="1" data-mk-line-qty="1" value="' + escapeHtml(p.qty != null ? p.qty : '1') + '" placeholder="100" /></td>' +
				'<td class="mk-wh-proto-col-date"><input type="date" data-mk-line-mfg="1" value="' + escapeHtml(p.mfg || '') + '" /></td>' +
				'<td class="mk-wh-proto-col-date"><input type="date" data-mk-line-exp="1" value="' + escapeHtml(p.expiry || '') + '" /></td>' +
				'<td class="mk-wh-proto-col-loc"><input type="text" data-mk-line-location="1" value="' + escapeHtml(p.location || '') + '" placeholder="Kệ 420" /></td>' +
				'<td><button type="button" class="mk-wh-proto-btn mk-wh-proto-btn--ghost mk-wh-proto-lines__del" data-mk-lines-del="1" title="Xóa dòng">×</button></td>' +
				'</tr>');
		}

		loadWhProductCatalogAsync().then(function (catalog) {
			mountLinesQuickSearch(form, 'inbound', catalog || [], addRow);
		});
	}

	function getOutboundStockLots(whId) {
		var d = whId ? S.ensureData(whId) : { stock: [] };
		return (d.stock || [])
			.filter(function (s) { return (Number(s.qty) || 0) > 0; })
			.slice()
			.sort(function (a, b) {
				var an = String(a.name || '');
				var bn = String(b.name || '');
				if (an !== bn) return an.localeCompare(bn, 'vi');
				return String(a.lot || '').localeCompare(String(b.lot || ''), 'vi');
			});
	}

	function outboundLotSelectHtml(lots, selectedKey) {
		var opts = '<option value="">— Tìm / chọn sản phẩm —</option>' +
			lots.map(function (s) {
				var name = decodeEntities(s.name || s.sku || '');
				var sku = decodeEntities(s.sku || '');
				var lot = decodeEntities(s.lot || '').replace(/^\s*lô\s+/i, '').trim();
				var key = String(sku || '') + '|' + String(lot || '');
				var qty = Number(s.qty) || 0;
				var mfg = toDateInputValue(decodeEntities(s.mfg || ''));
				var expiry = toDateInputValue(decodeEntities(s.expiry || s.exp || ''));
				var sel = key && key === String(selectedKey || '') ? ' selected="selected"' : '';
				var label = name + (lot ? ' · Lô ' + lot : '') + ' · còn ' + qty;
				return '<option value="' + escapeHtml(key) + '" data-sku="' + escapeHtml(sku) +
					'" data-name="' + escapeHtml(name) + '" data-lot="' + escapeHtml(lot) +
					'" data-qty="' + escapeHtml(String(qty)) +
					'" data-unit="' + escapeHtml(lookupUnitFromCatalog(sku, name)) +
					'" data-mfg="' + escapeHtml(mfg) + '" data-expiry="' + escapeHtml(expiry) + '"' + sel + '>' +
					escapeHtml(label) + '</option>';
			}).join('');
		return '<select class="mk-wh-proto-product-select" data-mk-line-lotkey="1">' + opts + '</select>';
	}

	function syncOutboundLineFromLot(selectEl) {
		if (!selectEl) return;
		var row = selectEl.closest('[data-mk-line="1"]');
		if (!row) return;
		var opt = selectEl.options[selectEl.selectedIndex];
		var skuEl = row.querySelector('[data-mk-line-sku="1"]');
		var unitEl = row.querySelector('[data-mk-line-unit="1"]');
		var lotEl = row.querySelector('[data-mk-line-lot="1"]');
		var qtyEl = row.querySelector('[data-mk-line-qty="1"]');
		var mfgEl = row.querySelector('[data-mk-line-mfg="1"]');
		var expEl = row.querySelector('[data-mk-line-exp="1"]');
		if (!opt || !selectEl.value) {
			if (skuEl) { skuEl.value = ''; skuEl.placeholder = 'SKU'; }
			if (unitEl) { unitEl.value = ''; unitEl.placeholder = '—'; }
			if (lotEl) lotEl.value = '';
			if (mfgEl) { mfgEl.value = ''; mfgEl.readOnly = true; }
			if (expEl) { expEl.value = ''; expEl.readOnly = true; }
			if (qtyEl) qtyEl.removeAttribute('max');
			return;
		}
		var lotKey = String(selectEl.value || '');
		var parts = lotKey.split('|');
		var skuKey = parts[0] || '';
		var lotKeyPart = parts.slice(1).join('|');
		var stockLot = findStockLot(getWhId(), skuKey, lotKeyPart);
		if (!stockLot && lotKeyPart) {
			// Match when sku in option key is empty/auto but stock row has resolved sku.
			stockLot = (getOutboundStockLots(getWhId()) || []).find(function (s) {
				return String(s.lot || '') === lotKeyPart || decodeEntities(s.lot || '') === decodeEntities(lotKeyPart);
			});
		}
		var sku = decodeEntities((stockLot && stockLot.sku) || opt.getAttribute('data-sku') || '');
		var lot = decodeEntities((stockLot && stockLot.lot) || opt.getAttribute('data-lot') || lotKeyPart || '');
		lot = lot.replace(/^\s*lô\s+/i, '').trim();
		var name = decodeEntities((stockLot && stockLot.name) || opt.getAttribute('data-name') || '');
		var unit = decodeEntities(opt.getAttribute('data-unit') || '') || lookupUnitFromCatalog(sku, name);
		var avail = stockLot ? (Number(stockLot.qty) || 0) : (parseInt(opt.getAttribute('data-qty') || '0', 10) || 0);
		var mfg = toDateInputValue(decodeEntities((stockLot && stockLot.mfg) || opt.getAttribute('data-mfg') || ''));
		var expiry = toDateInputValue(decodeEntities((stockLot && (stockLot.expiry || stockLot.exp)) || opt.getAttribute('data-expiry') || ''));
		if (skuEl) {
			skuEl.value = sku;
			skuEl.placeholder = sku ? 'SKU' : 'Chưa có SKU';
		}
		if (unitEl) {
			unitEl.value = unit;
			unitEl.placeholder = unit ? 'Đơn vị' : '—';
		}
		if (lotEl) lotEl.value = lot;
		if (mfgEl) {
			mfgEl.value = mfg;
			mfgEl.readOnly = !!mfg;
			mfgEl.classList.toggle('mk-wh-proto-sku-readonly', !!mfg);
			mfgEl.tabIndex = mfg ? -1 : 0;
		}
		if (expEl) {
			expEl.value = expiry;
			expEl.readOnly = !!expiry;
			expEl.classList.toggle('mk-wh-proto-sku-readonly', !!expiry);
			expEl.tabIndex = expiry ? -1 : 0;
		}
		if (qtyEl) {
			if (avail > 0) {
				qtyEl.setAttribute('max', String(avail));
				qtyEl.placeholder = String(Math.min(100, avail));
			} else {
				qtyEl.removeAttribute('max');
			}
		}
	}

	function bindOutboundLineRows(form) {
		var bodyEl = form.querySelector('[data-mk-lines-body="1"]');
		if (!bodyEl) return;
		var whId = getWhId();
		var lots = getOutboundStockLots(whId);

		function addRow(preset) {
			var p = preset || {};
			var lotKey = p.lotKey || ((p.sku && p.lot) ? (p.sku + '|' + p.lot) : '');
			var nameVal = p.name || '';
			if (nameVal && p.lot) {
				nameVal = nameVal + ' · Lô ' + p.lot;
			}
			bodyEl.insertAdjacentHTML('beforeend',
				'<tr class="mk-wh-proto-lines__row" data-mk-line="1">' +
				'<td>' +
				'<input type="hidden" data-mk-line-lotkey="1" value="' + escapeHtml(lotKey) + '" />' +
				'<input type="text" data-mk-line-name="1" value="' + escapeHtml(nameVal) + '" readonly tabindex="-1" class="mk-wh-proto-sku-readonly" />' +
				'</td>' +
				'<td class="mk-wh-proto-col-sku"><input type="text" data-mk-line-sku="1" value="' + escapeHtml(p.sku || '') + '" readonly tabindex="-1" class="mk-wh-proto-sku-readonly" placeholder="SKU" /></td>' +
				'<td class="mk-wh-proto-col-unit"><input type="text" data-mk-line-unit="1" value="' + escapeHtml(p.unit || '') + '" readonly tabindex="-1" class="mk-wh-proto-sku-readonly" placeholder="—" /></td>' +
				'<td class="mk-wh-proto-col-lot"><input type="text" data-mk-line-lot="1" value="' + escapeHtml(p.lot || '') + '" readonly tabindex="-1" class="mk-wh-proto-sku-readonly" placeholder="LOT-2605A" /></td>' +
				'<td class="mk-wh-proto-col-qty"><input type="number" min="1" step="1" data-mk-line-qty="1" value="' + escapeHtml(p.qty != null ? p.qty : '1') + '" placeholder="100" /></td>' +
				'<td class="mk-wh-proto-col-date"><input type="date" data-mk-line-mfg="1" value="' + escapeHtml(p.mfg || '') + '" readonly tabindex="-1" class="mk-wh-proto-sku-readonly" /></td>' +
				'<td class="mk-wh-proto-col-date"><input type="date" data-mk-line-exp="1" value="' + escapeHtml(p.expiry || '') + '" readonly tabindex="-1" class="mk-wh-proto-sku-readonly" /></td>' +
				'<td><button type="button" class="mk-wh-proto-btn mk-wh-proto-btn--ghost mk-wh-proto-lines__del" data-mk-lines-del="1" title="Xóa dòng">×</button></td>' +
				'</tr>');
			var rows = bodyEl.querySelectorAll('[data-mk-line="1"]');
			var newRow = rows[rows.length - 1];
			if (newRow && lotKey) {
				var qtyEl = newRow.querySelector('[data-mk-line-qty="1"]');
				var stockLot = findStockLot(whId, p.sku || '', p.lot || '');
				var avail = stockLot ? (Number(stockLot.qty) || 0) : (parseInt(p.qty, 10) || 0);
				if (qtyEl && avail > 0) {
					qtyEl.setAttribute('max', String(avail));
				}
			}
		}

		mountLinesQuickSearch(form, 'outbound', lots, addRow);
	}

	function openModal(opts) {
		var modal = qs('#mkWhProtoModal');
		var title = qs('#mkWhProtoModalTitle');
		var fields = qs('#mkWhProtoFormFields');
		var submit = qs('#mkWhProtoSubmitBtn');
		var form = qs('#mkWhProtoModalForm');
		if (!modal || !title || !fields || !form) return;

		var isDocModal = opts.tabKey === 'inbound' || opts.tabKey === 'outbound-type' || opts.tabKey === 'outbound';
		var isWideDoc = opts.tabKey === 'inbound' || opts.tabKey === 'outbound';
		var dialog = modal.querySelector('.mk-wh-proto-modal__dialog');
		if (dialog) {
			dialog.classList.toggle('mk-wh-proto-modal__dialog--compact', isDocModal);
			dialog.classList.toggle('mk-wh-proto-modal__dialog--lux', isDocModal);
			dialog.classList.toggle('mk-wh-proto-modal__dialog--workspace', isWideDoc);
			dialog.classList.toggle('mk-wh-proto-modal__dialog--inbound', opts.tabKey === 'inbound');
			dialog.classList.toggle('mk-wh-proto-modal__dialog--outbound', opts.tabKey === 'outbound' || opts.tabKey === 'outbound-type');
		}

		title.textContent = opts.title || 'Tạo phiếu';
		var eyebrow = qs('#mkWhProtoModalEyebrow');
		var sub = qs('#mkWhProtoModalSub');
		if (eyebrow) {
			if (opts.tabKey === 'inbound') {
				eyebrow.textContent = 'Nhập kho';
			} else if (opts.tabKey === 'outbound' || opts.tabKey === 'outbound-type') {
				eyebrow.textContent = 'Xuất kho';
			} else {
				eyebrow.textContent = 'Kho';
			}
		}
		if (sub) {
			var subText = opts.subtitle || '';
			if (!subText && opts.tabKey === 'inbound') {
				subText = 'Tạo phiếu nhập từ nhà cung cấp — thêm nhiều dòng hàng, lô và hạn dùng trong một lần.';
			} else if (!subText && opts.tabKey === 'outbound') {
				subText = 'Chọn hàng từ tồn kho, kiểm tra lô / số lượng khả dụng trước khi tạo phiếu xuất.';
			} else if (!subText && opts.tabKey === 'outbound-type') {
				subText = 'Chọn loại xuất kho để mở form chi tiết phù hợp.';
			}
			if (subText) {
				sub.textContent = subText;
				sub.hidden = false;
			} else {
				sub.textContent = '';
				sub.hidden = true;
			}
		}
		if (submit) submit.textContent = opts.submitLabel || 'Tạo phiếu';
		fields.innerHTML = renderModalFields(opts.fields || []);

		if (opts.tabKey === 'inbound') {
			bindInboundLineRows(form);
		} else if (opts.tabKey === 'outbound') {
			bindOutboundLineRows(form);
		}

		form.onsubmit = function (e) {
			e.preventDefault();
			var whId = getWhId();
			if (!whId) return;
			var fd = new FormData(form);
			var tabKey = opts.tabKey || 'inbound';
			var d = S.ensureData(whId);

			if (tabKey === 'outbound-type') {
				var pickedType = String(fd.get('outboundType') || '');
				if (!pickedType || OUTBOUND_TYPE_PICKER.indexOf(pickedType) < 0) return;
				openModal(modalSchema('outbound', pickedType));
				return;
			}

			if (tabKey === 'outbound') {
				var customerRaw = String(fd.get('customer') || '').trim();
				var soRef = String(fd.get('so') || '').trim() || '—';
				var outboundType = String(fd.get('outboundType') || opts.outboundType || 'internal').trim();
				if (OUTBOUND_TYPE_PICKER.indexOf(outboundType) < 0 && outboundType !== 'sale') {
					outboundType = 'internal';
				}
				var customer = customerRaw;
				var toWarehouseId = '';
				if (outboundType === 'transfer') {
					toWarehouseId = customerRaw;
					customer = resolveWarehouseLabel(customerRaw);
					if (!toWarehouseId || !customer) {
						showError('Vui lòng chọn kho đích.');
						return;
					}
				} else if (!customer) {
					showError('Vui lòng nhập ' + (getOutboundTypeMeta(outboundType).customerLabel || 'thông tin bắt buộc') + '.');
					return;
				}
				var outRows = Array.prototype.slice.call(form.querySelectorAll('[data-mk-line="1"]'));
				var outLines = [];
				var outError = '';
				outRows.forEach(function (row) {
					var lotKeyEl = row.querySelector('[data-mk-line-lotkey="1"]');
					var lotKey = lotKeyEl ? String(lotKeyEl.value || '') : '';
					var qtyOut = row.querySelector('[data-mk-line-qty="1"]')
						? (parseInt(row.querySelector('[data-mk-line-qty="1"]').value, 10) || 0)
						: 0;
					if (!lotKey && !qtyOut) return;
					if (!lotKey || qtyOut <= 0) {
						outError = 'Mỗi dòng xuất cần chọn lô hàng và số lượng hợp lệ.';
						return;
					}
					var parts = lotKey.split('|');
					var sku = parts[0];
					var lot = parts.slice(1).join('|');
					var stockLot = findStockLot(whId, sku, lot);
					if (!stockLot) {
						outError = 'Lô hàng không còn trong tồn kho.';
						return;
					}
					var avail = Number(stockLot.qty) || 0;
					if (qtyOut > avail) {
						outError = 'Số lượng xuất vượt tồn (còn ' + avail + ') cho ' + (stockLot.name || sku) + ' · ' + lot + '.';
						return;
					}
					var mfgVal = row.querySelector('[data-mk-line-mfg="1"]')
						? row.querySelector('[data-mk-line-mfg="1"]').value
						: '';
					var expVal = row.querySelector('[data-mk-line-exp="1"]')
						? row.querySelector('[data-mk-line-exp="1"]').value
						: '';
					outLines.push({
						sku: stockLot.sku,
						name: stockLot.name,
						lot: stockLot.lot,
						qty: qtyOut,
						price: Number(stockLot.price) || 0,
						unit_price: Number(stockLot.price) || 0,
						unit: (row.querySelector('[data-mk-line-unit="1"]')
							? row.querySelector('[data-mk-line-unit="1"]').value
							: '') || lookupUnitFromCatalog(stockLot.sku, stockLot.name),
						mfg: toDateInputValue(mfgVal || stockLot.mfg || ''),
						expiry: toDateInputValue(expVal || stockLot.expiry || stockLot.exp || '') || '—',
					});
				});
				if (outError) {
					showError(outError);
					return;
				}
				if (!outLines.length) {
					showError('Vui lòng thêm ít nhất một dòng hàng xuất.');
					return;
				}
				var typeMeta = getOutboundTypeMeta(outboundType);
				var exportTypeLabel = String(fd.get('exportTypeLabel') || '').trim();
				var notes = String(fd.get('notes') || '').trim();
				if (outboundType === 'internal' && !exportTypeLabel) {
					exportTypeLabel = 'Xuất dùng nội bộ';
				}
				var id = nextId('GIN', d.issues || []);
				var now = S.nowISO();
				var issue = {
					id: id,
					outboundType: outboundType,
					customer: customer,
					toWarehouseId: toWarehouseId || undefined,
					soRef: soRef,
					exportTypeLabel: exportTypeLabel || undefined,
					notes: notes || undefined,
					status: 'waiting_print',
					createdAt: now,
					createdBy: (getAccess().userName || 'User'),
					lines: outLines,
					timeline: [],
					stockDeducted: false,
				};
				addTimeline(issue.timeline, 'Tạo phiếu xuất — ' + typeMeta.short, 'manager');

				function finishCreateIssue(savedIssue) {
					closeModal();
					var dlg = issueDialog(savedIssue || issue);
					openDialog(dlg.title, dlg.meta, dlg.body);
					renderAll();
				}

				if (S.useDb && S.useDb() && S.warehouseDataActions && typeof S.warehouseDataActions.saveIssue === 'function') {
					S.warehouseDataActions.saveIssue(whId, issue).then(function (res) {
						var saved = (res && res.issue) ? res.issue : issue;
						if (res && res.code) {
							saved.id = res.code;
						}
						finishCreateIssue(saved);
					}).fail(function (err) {
						showError((err && err.message) || 'Không lưu được phiếu xuất.');
					});
					return;
				}

				var issues = (d.issues || []).slice();
				issues.unshift(issue);
				S.warehouseDataActions.setIssues(whId, issues);
				deductStockFromIssueLines(whId, outLines);
				if (outboundType === 'transfer' && toWarehouseId) {
					creditStockToWarehouse(toWarehouseId, outLines);
				}
				issue.stockDeducted = true;
				if (outboundType === 'transfer' && toWarehouseId) {
					issue.stockCreditedTo = toWarehouseId;
				}
				S.warehouseDataActions.setIssues(whId, issues);
				finishCreateIssue(issue);
				return;
			}

			var supplier = String(fd.get('supplier') || '').trim();
			var poRef = String(fd.get('po') || '').trim();
			if (!supplier || !poRef) {
				showError('Vui lòng nhập đầy đủ Nhà cung cấp và Mã PO.');
				return;
			}

			var rows = Array.prototype.slice.call(form.querySelectorAll('[data-mk-line="1"]'));
			var lines = [];
			var missingSku = false;
			rows.forEach(function (row) {
				var productIdEl = row.querySelector('[data-mk-line-product-id="1"]');
				var productId = productIdEl ? (parseInt(productIdEl.value, 10) || 0) : 0;
				var nameEl = row.querySelector('[data-mk-line-name="1"]');
				var name = nameEl ? decodeEntities(nameEl.value.trim()) : '';
				var skuEl = row.querySelector('[data-mk-line-sku="1"]');
				var sku = skuEl ? decodeEntities(skuEl.value.trim()) : '';
				var priceEl = row.querySelector('[data-mk-line-price="1"]');
				var price = priceEl ? (parseFloat(priceEl.value) || 0) : 0;
				var unit = '';
				var lot = row.querySelector('[data-mk-line-lot="1"]') ? row.querySelector('[data-mk-line-lot="1"]').value.trim() : '';
				var qty = row.querySelector('[data-mk-line-qty="1"]') ? (parseInt(row.querySelector('[data-mk-line-qty="1"]').value, 10) || 0) : 0;
				var mfg = row.querySelector('[data-mk-line-mfg="1"]') ? row.querySelector('[data-mk-line-mfg="1"]').value : '';
				var expiry = row.querySelector('[data-mk-line-exp="1"]') ? row.querySelector('[data-mk-line-exp="1"]').value : '';
				var location = row.querySelector('[data-mk-line-location="1"]') ? row.querySelector('[data-mk-line-location="1"]').value.trim() : '';
				var needsQcEl = row.querySelector('[data-mk-line-needs-qc="1"]');
				var needsQc = needsQcEl ? needsQcEl.value === '1' : false;
				if (!unit && row.querySelector('[data-mk-line-unit="1"]')) {
					unit = row.querySelector('[data-mk-line-unit="1"]').value.trim();
				}
				if (!productId && !lot && !qty) return;
				if (!productId || !name || !lot || qty <= 0) return;
				if (productId && !sku) {
					missingSku = true;
					return;
				}
				lines.push({ product_id: productId, sku: sku, name: name, lot: lot, mfg: mfg || '', expiry: expiry || '—', qty: qty, price: price, unit: unit, location: location, needsQc: needsQc });
			});
			if (missingSku) {
				showError('Một hoặc nhiều sản phẩm chưa có SKU. Hãy cập nhật SKU trong Products & Services trước khi nhập kho.');
				return;
			}
			if (!lines.length) {
				showError('Vui lòng chọn sản phẩm và nhập Lô + Số lượng cho ít nhất một dòng.');
				return;
			}

			var receiptPayload = {
				supplier: supplier,
				poRef: poRef,
				lines: lines,
			};

			if (S.useDb && S.useDb()) {
				if (submit) submit.disabled = true;
				S.warehouseDataActions.saveReceipt(whId, receiptPayload).then(function (res) {
					closeModal();
					refreshWarehouseUi();
					var saved = res && res.data && res.data.receipts && res.data.receipts[0];
					if (saved) {
						var rDlg = receiptDialog(saved);
						openDialog(rDlg.title, rDlg.meta, rDlg.body, rDlg.foot);
					}
				}).fail(function (err) {
					var msg = typeof err === 'string' ? err : (err && err.message) || (err && err.error && err.error.message) || (err && err.error) || 'Không lưu được phiếu nhập. Vui lòng thử lại.';
					showError(msg);
				}).always(function () {
					if (submit) submit.disabled = false;
				});
				return;
			}

			var anyQc = lines.some(function (l) { return !!l.needsQc; });
			var directLines = lines.filter(function (l) { return !l.needsQc; });
			var nowIn = S.nowISO();
			var rid = nextId('GRN', d.receipts || []);
			var linesTagged = lines.map(function (l) {
				return Object.assign({}, l, { stocked: !l.needsQc });
			});
			var receipt = {
				id: rid,
				supplier: supplier,
				poRef: poRef,
				createdAt: nowIn,
				createdBy: (getAccess().userName || 'User'),
				status: anyQc ? 'pending_qc' : 'stored',
				lines: linesTagged,
				timeline: [],
				stockStored: !anyQc,
			};
			addTimeline(receipt.timeline, 'Tạo phiếu nhập', 'manager');
			if (anyQc) {
				addTimeline(receipt.timeline, 'Gửi QC (theo hàng hoá)', 'manager');
				if (directLines.length) {
					addTimeline(receipt.timeline, 'Nhập thẳng tồn kho (dòng không QC)', 'manager', directLines.length + ' dòng');
				}
			} else {
				addTimeline(receipt.timeline, 'Nhập thẳng tồn kho', 'manager');
			}

			var receipts = (d.receipts || []).slice();
			receipts.unshift(receipt);
			S.warehouseDataActions.setReceipts(whId, receipts);
			if (directLines.length) addStockFromReceiptLines(whId, directLines);

			closeModal();
			var rDlg = receiptDialog(receipt);
			openDialog(rDlg.title, rDlg.meta, rDlg.body, rDlg.foot);
		};

		form.onclick = function (e) {
			var t = e.target;
			if (t && t.getAttribute && t.getAttribute('data-mk-lines-del') === '1') {
				e.preventDefault();
				var row = t.closest('[data-mk-line="1"]');
				var bodyEl = form.querySelector('[data-mk-lines-body="1"]');
				if (row && bodyEl && bodyEl.children.length > 1) {
					var productSel = row.querySelector('[data-mk-line-product="1"]');
					var lotKeySel = row.querySelector('[data-mk-line-lotkey="1"]');
					destroyProductSelect2(productSel || lotKeySel);
					row.remove();
				}
			}
		};

		modal.classList.add('is-open');
		modal.setAttribute('aria-hidden', 'false');
	}

	function openCreateInbound() {
		openModal(modalSchema('inbound'));
	}

	function openOutboundTypePicker() {
		openModal(modalSchema('outbound-type'));
	}

	function boot() {
		if (!qs('#mkWhPrototypeRoot')) return;
		S.hydrate();
		if (!renderHeader()) return;

		// Role select uses Prototype keys: qc / stock / manager
		updateRoleBanner();
		bindReturnModal();

		qsa('.mk-wh-proto-tab').forEach(function (b) {
			b.addEventListener('click', function () {
				setActiveTab(b.getAttribute('data-tab'));
			});
		});

		// Role select removed — permissions from data-can-write / data-can-qc

		var auditBtn = qs('#mkWhAuditHistoryBtn');
		if (auditBtn) {
			auditBtn.addEventListener('click', function () {
				var active = qs('.mk-wh-proto-tab.is-active');
				var tabKey = active ? active.getAttribute('data-tab') : 'inbound';
				openAuditHistoryModal(tabKey === 'outbound' ? 'outbound' : 'inbound');
			});
		}

		document.addEventListener('keydown', function (e) {
			if (e.key !== 'Escape') return;
			if (document.body.classList.contains('mk-wh-audit-open')) {
				closeAuditHistoryModal();
				return;
			}
			var retModal = qs('#mkWhReturnModal');
			if (retModal && retModal.classList.contains('is-open')) {
				closeReturnModal();
				return;
			}
			var lb = qs('#mkWhQcLightbox');
			if (lb && lb.classList.contains('is-open')) {
				closeQcLightbox();
				return;
			}
			var dlg = qs('#mkWhProtoDialog');
			if (dlg && dlg.classList.contains('is-open')) {
				closeDialog();
			}
		});

		var resetBtn = qs('#mkWhProtoFilterReset');
		if (resetBtn) resetBtn.addEventListener('click', function () {
			var h = qs('#mkWhProtoFilterHsd');
			var s = qs('#mkWhProtoStockSearch');
			var p = qs('#mkWhProtoFilterPrice');
			if (h) h.value = 'all';
			if (s) s.value = '';
			if (p) p.value = 'all';
			syncStockSearchClear();
			renderStock();
		});
		['#mkWhProtoFilterHsd', '#mkWhProtoFilterPrice'].forEach(function (sel) {
			var el = qs(sel);
			if (!el) return;
			el.addEventListener('change', renderStock);
		});
		var stockSearch = qs('#mkWhProtoStockSearch');
		var stockSearchTimer = null;
		if (stockSearch) {
			stockSearch.addEventListener('input', function () {
				syncStockSearchClear();
				if (stockSearchTimer) clearTimeout(stockSearchTimer);
				stockSearchTimer = setTimeout(renderStock, 120);
			});
			stockSearch.addEventListener('keydown', function (e) {
				if (e.key === 'Escape') {
					stockSearch.value = '';
					syncStockSearchClear();
					if (stockSearchTimer) clearTimeout(stockSearchTimer);
					renderStock();
				}
			});
		}
		var stockSearchClear = qs('#mkWhProtoStockSearchClear');
		if (stockSearchClear) {
			stockSearchClear.addEventListener('click', function () {
				if (stockSearch) stockSearch.value = '';
				syncStockSearchClear();
				if (stockSearch) stockSearch.focus();
				renderStock();
			});
		}
		syncStockSearchClear();

		var createBtn = qs('#mkWhProtoCreateBtn');
		if (createBtn) createBtn.addEventListener('click', function () {
			if (createBtn.disabled || createBtn.classList.contains('hide')) return;
			var active = qs('.mk-wh-proto-tab.is-active');
			var tabKey = active ? active.getAttribute('data-tab') : 'inbound';
			if (tabKey === 'inbound') {
				openCreateInbound();
			} else if (tabKey === 'outbound') {
				openOutboundTypePicker();
			} else if (tabKey === 'returns') {
				openReturnModal();
			}
		});

		var modal = qs('#mkWhProtoModal');
		if (modal) {
			modal.addEventListener('click', function (e) {
				var target = e.target;
				if (target && target.getAttribute && target.getAttribute('data-mk-close') === '1') {
					closeModal();
				}
			});
		}

		// Capture phase: block <a href="WhQcImage..."> navigation before default kicks in.
		document.addEventListener('click', function (e) {
			if (tryOpenQcImageFromEvent(e)) return;
		}, true);

		document.addEventListener('click', function (e) {
			var t = e.target;
			if (!t) return;

			// Support clicks on inner <span>/<svg> inside buttons.
			var actionEl = (t.closest && t.closest('[data-mk-action]')) || null;
			var closeEl = (t.closest && t.closest('[data-mk-dialog-close="1"]')) || null;
			if (closeEl) {
				closeDialog();
				return;
			}
			if (!actionEl || !actionEl.getAttribute) return;
			var action = actionEl.getAttribute('data-mk-action');
			var id = actionEl.getAttribute('data-id');
			if (!action) return;
			// Already handled in capture listener.
			if (
				action === 'qc-image-preview' ||
				action === 'qc-lightbox-close' ||
				action === 'qc-lightbox-prev' ||
				action === 'qc-lightbox-next' ||
				action === 'qc-lightbox-zoom-in' ||
				action === 'qc-lightbox-zoom-out' ||
				action === 'qc-lightbox-zoom-reset'
			) {
				return;
			}

			var whId = getWhId();
			if (!whId) return;
			var d = S.ensureData(whId);

			if (action === 'inbound-detail' && id) {
				if (S.useDb && S.useDb() && S.warehouseDataActions && typeof S.warehouseDataActions.refresh === 'function') {
					S.warehouseDataActions.refresh(whId).always(function () {
						var d0 = S.ensureData(whId);
						var r0 = (d0.receipts || []).find(function (x) { return x.id === id; });
						if (!r0) return;
						var dialog0 = receiptDialog(r0);
						openDialog(dialog0.title, dialog0.meta, dialog0.body, dialog0.foot);
					});
					return;
				}
				var r = (d.receipts || []).find(function (x) { return x.id === id; });
				if (!r) return;
				var dialog = receiptDialog(r);
				openDialog(dialog.title, dialog.meta, dialog.body, dialog.foot);
				return;
			}
			if (action === 'inbound-print' && id) {
				openInboundPrintPreview(id);
				return;
			}
			if (action === 'outbound-print' && id) {
				openOutboundPrintPreview(id);
				return;
			}
			if (action === 'return-detail' && id) {
				var slip = (d.returns || []).find(function (x) { return x.id === id || x.code === id; });
				openReturnDialog(slip);
				return;
			}
			if (action === 'return-confirm' && id) {
				if (!window.confirm('Xác nhận nhập kho phiếu ' + id + '?\nHàng sẽ cộng vào tồn kho.' + ((d.returns || []).some(function (x) { return (x.id === id || x.code === id) && x.refund; }) ? '\nPhiếu có hoàn tiền: số đã thu trên đơn sẽ giảm.' : ''))) {
					return;
				}
				if (!S.returnActions) return;
				S.returnActions.confirm(whId, id).then(function () {
					closeDialog();
					refreshWarehouseUi();
				}).fail(function (err) {
					showError((err && err.message) || 'Không xác nhận được phiếu.');
				});
				return;
			}
			if (action === 'return-cancel' && id) {
				if (!window.confirm('Hủy phiếu ' + id + '?')) return;
				if (!S.returnActions) return;
				S.returnActions.cancel(whId, id).then(function () {
					closeDialog();
					refreshWarehouseUi();
				}).fail(function (err) {
					showError((err && err.message) || 'Không hủy được phiếu.');
				});
				return;
			}
			if (action === 'return-print' && id) {
				window.open(returnPrintUrl(id, true), '_blank');
				return;
			}
			if (action === 'outbound-cancel' && id) {
				if (!isWarehouseOps(getRole())) {
					showError('Bạn không có quyền huỷ phiếu xuất.');
					return;
				}
				var cancelIssue = (d.issues || []).find(function (x) { return x.id === id; });
				if (!cancelIssue || !canCancelOutboundIssue(cancelIssue.status)) {
					showError('Chỉ huỷ được phiếu ở trạng thái Chờ soạn, Đang soạn hoặc Đã soạn.');
					return;
				}
				if (!window.confirm('Huỷ phiếu xuất ' + id + '?\nTồn kho đã trừ (nếu có) sẽ được hoàn lại.')) {
					return;
				}
				if (S.useDb && S.useDb() && S.warehouseDataActions && typeof S.warehouseDataActions.issueAction === 'function') {
					S.warehouseDataActions
						.issueAction(whId, id, 'issue-cancel', getRole(), 'Huỷ xuất kho')
						.then(function () {
							closeDialog();
							refreshWarehouseUi();
						})
						.fail(function (err) {
							showError((err && err.message) || 'Không huỷ được phiếu xuất.');
						});
					return;
				}
				patchIssue(id, function (i) {
					if (i.stockDeducted) {
						creditStockToWarehouse(whId, i.lines || []);
						i.stockDeducted = false;
					}
					if ((i.outboundType || '') === 'transfer' && i.stockCreditedTo) {
						deductStockFromIssueLines(i.stockCreditedTo, i.lines || []);
						i.stockCreditedTo = '';
					}
					i.status = 'cancelled';
					addTimeline(i.timeline, 'Huỷ phiếu xuất', getRole(), 'Huỷ xuất kho');
					return i;
				});
				refreshWarehouseUi();
				return;
			}
			if (action === 'qc-record' && id) {
				if (S.useDb && S.useDb() && S.warehouseDataActions && typeof S.warehouseDataActions.refresh === 'function') {
					S.warehouseDataActions.refresh(whId).always(function () {
						var d1 = S.ensureData(whId);
						var r1 = (d1.receipts || []).find(function (x) { return x.id === id; });
						if (!r1) return;
						var dialog1 = receiptDialog(r1);
						openDialog(dialog1.title, dialog1.meta, dialog1.body, dialog1.foot);
					});
					return;
				}
				var r2 = (d.receipts || []).find(function (x) { return x.id === id; });
				if (!r2) return;
				var dialog2 = receiptDialog(r2);
				openDialog(dialog2.title, dialog2.meta, dialog2.body, dialog2.foot);
				return;
			}
			if (action === 'outbound-detail' && id) {
				if (S.useDb && S.useDb() && S.warehouseDataActions && typeof S.warehouseDataActions.refresh === 'function') {
					S.warehouseDataActions.refresh(whId).always(function () {
						var d2 = S.ensureData(whId);
						var issue2 = (d2.issues || []).find(function (x) { return x.id === id; });
						if (!issue2) return;
						var dlg2 = issueDialog(issue2);
						openDialog(dlg2.title, dlg2.meta, dlg2.body);
					});
					return;
				}
				var issue = (d.issues || []).find(function (x) { return x.id === id; });
				if (!issue) return;
				var dlg = issueDialog(issue);
				openDialog(dlg.title, dlg.meta, dlg.body);
				return;
			}

			if (id && action === 'qc-image-delete') {
				if (!(S.useDb && S.useDb())) {
					showError('Xóa ảnh cần chế độ lưu database.');
					return;
				}
				var imageId = actionEl.getAttribute('data-image-id') || '';
				if (!imageId) return;
				if (!window.confirm('Xóa ảnh này?')) return;
				S.warehouseDataActions
					.deleteQcImage(whId, id, imageId)
					.then(function (res) {
						reopenReceiptDialog(whId, id, res);
					})
					.fail(function (err) {
						showError((err && err.message) || 'Không xóa được ảnh.');
					});
				return;
			}
			if (id && action === 'qc-update') {
				if (!canDoQc()) {
					showError('Bạn không có quyền cập nhật ghi nhận QC.');
					return;
				}
				if (!(S.useDb && S.useDb())) {
					showError('Cập nhật ghi nhận cần chế độ lưu database.');
					return;
				}
				var qcNote = readQcNoteFromDialog(actionEl);
				S.warehouseDataActions
					.updateQcRecord(whId, id, getRole(), qcNote)
					.then(function (res) {
						reopenReceiptDialog(whId, id, res);
					})
					.fail(function (err) {
						showError((err && err.message) || 'Không cập nhật được ghi nhận QC.');
					});
				return;
			}

			// Receipt actions
			if (id && (action === 'send-qc' || action === 'qc-pass' || action === 'qc-fail' || action === 'mgr-approve' || action === 'store' || action === 'receipt-revert')) {
				var role = getRole();
				var receiptTarget = actionEl.getAttribute('data-target') || '';
				if (action === 'receipt-revert') {
					if (!receiptTarget) return;
					var receiptStepLabel = '';
					RECEIPT_PATH.forEach(function (s) {
						if (s.key === receiptTarget) receiptStepLabel = s.label;
					});
					if (!window.confirm('Quay lại bước "' + (receiptStepLabel || receiptTarget) + '"?\nThao tác sẽ được ghi vào lịch sử.')) {
						return;
					}
				}
				if (S.useDb && S.useDb()) {
					var note = '';
					if (action === 'qc-pass' || action === 'qc-fail') {
						note = readQcNoteFromDialog(actionEl);
					}
					S.warehouseDataActions
						.receiptAction(whId, id, action, role, note, receiptTarget)
						.then(function (res) {
							closeDialog();
							refreshWarehouseUi();
							reopenReceiptDialog(whId, id, res);
						})
						.fail(function (err) {
							showError((err && err.message) || 'Không cập nhật được phiếu nhập.');
						});
					return;
				}
				patchReceipt(id, function (r) {
					if (action === 'send-qc') {
						r.status = 'pending_qc';
						addTimeline(r.timeline, 'Gửi QC kiểm tra', role);
					} else if (action === 'qc-pass') {
						var note = readQcNoteFromDialog(actionEl);
						r.status = 'qc_passed';
						r.qc = { result: 'pass', note: note, at: S.nowISO(), by: 'QC Minh' };
						r.lines = (r.lines || []).map(function (l) { return Object.assign({}, l, { qcResult: 'pass', passedQty: l.qty }); });
						addTimeline(r.timeline, 'QC đạt', role, note || undefined);
					} else if (action === 'qc-fail') {
						var note2 = readQcNoteFromDialog(actionEl);
						r.status = 'qc_failed';
						r.qc = { result: 'fail', note: note2, at: S.nowISO(), by: 'QC Minh' };
						r.lines = (r.lines || []).map(function (l) { return Object.assign({}, l, { qcResult: 'fail', passedQty: 0 }); });
						addTimeline(r.timeline, 'QC không đạt', role, note2 || undefined);
					} else if (action === 'mgr-approve') {
						r.status = 'approved';
						addTimeline(r.timeline, 'Duyệt phiếu', role);
					} else if (action === 'store') {
						var storeLines = (r.lines || []).filter(function (l) {
							return !l.stocked && (l.needsQc || l.qc !== 'skip');
						});
						// Fallback legacy: if no stocked flags, store all once.
						if (!storeLines.length && !(r.lines || []).some(function (l) { return l.stocked; }) && !r.stockStored) {
							storeLines = r.lines || [];
						}
						addStockFromReceiptLines(whId, storeLines);
						r.lines = (r.lines || []).map(function (l) {
							return Object.assign({}, l, { stocked: true });
						});
						r.stockStored = true;
						r.status = 'stored';
						addTimeline(r.timeline, 'Đã nhập kho', role, formatLocationNote(r.lines));
					} else if (action === 'receipt-revert' && receiptTarget) {
						var fromRaw = String(r.status || '');
						var toRaw = String(receiptTarget || '');
						if (fromRaw === 'qc_failed') {
							if (toRaw !== 'pending_qc' && toRaw !== 'draft') {
								return r;
							}
							if (toRaw === 'pending_qc') {
								delete r.qc;
							}
							r.status = toRaw;
							addTimeline(r.timeline, 'Quay lại: ' + (receiptStepLabel || toRaw), role, 'Từ QC không đạt');
							return r;
						}
						var fromN = normalizeReceiptPathStatus(fromRaw);
						var toN = normalizeReceiptPathStatus(toRaw);
						var fromI = pathIndex(RECEIPT_PATH, fromN);
						var toI = pathIndex(RECEIPT_PATH, toN);
						var storedI = pathIndex(RECEIPT_PATH, 'stored');
						var pendingQcI = pathIndex(RECEIPT_PATH, 'pending_qc');
						if (fromI >= 0 && toI >= 0 && toI < fromI) {
							if (fromI >= storedI && toI < storedI && (r.stockStored || fromN === 'stored')) {
								deductStockFromIssueLines(whId, r.lines || []);
								r.stockStored = false;
							}
							if (toI <= pendingQcI) {
								delete r.qc;
							}
							var toLabel = RECEIPT_PATH[toI] ? RECEIPT_PATH[toI].label : toN;
							var fromLabel = RECEIPT_PATH[fromI] ? RECEIPT_PATH[fromI].label : fromN;
							r.status = toN;
							addTimeline(r.timeline, 'Quay lại: ' + toLabel, role, 'Từ ' + fromLabel);
						}
					}
					return r;
				});
				refreshWarehouseUi();
				reopenReceiptDialog(whId, id);
				return;
			}

			// Issue actions
			if (id && (action === 'issue-submit' || action === 'issue-start-pick' || action === 'issue-finish-pick' || action === 'issue-ship' || action === 'issue-approve' || action === 'issue-reject' || action === 'issue-cancel' || action === 'issue-revert')) {
				var role2 = getRole();
				var targetStatus = actionEl.getAttribute('data-target') || '';
				if (action === 'issue-revert') {
					if (!targetStatus) return;
					var stepLabel = '';
					ISSUE_PATH.forEach(function (s) {
						if (s.key === targetStatus) stepLabel = s.label;
					});
					if (!window.confirm('Quay lại bước "' + (stepLabel || targetStatus) + '"?\nThao tác sẽ được ghi vào lịch sử.')) {
						return;
					}
				}
				if (S.useDb && S.useDb()) {
					var reasonDb = '';
					if (action === 'issue-reject') {
						var rsDb = qs('[data-mk-reject-reason="1"]');
						reasonDb = rsDb ? String(rsDb.value || '').trim() : '';
					}
					S.warehouseDataActions
						.issueAction(whId, id, action, role2, reasonDb, targetStatus)
						.then(function (res) {
							closeDialog();
							refreshWarehouseUi();
							reopenIssueDialog(whId, id, res);
						})
						.fail(function (err) {
							showError((err && err.message) || 'Không cập nhật được phiếu xuất.');
						});
					return;
				}
				patchIssue(id, function (i) {
					if (action === 'issue-submit') {
						i.status = 'waiting_print';
						addTimeline(i.timeline, 'Chờ soạn', role2);
					} else if (action === 'issue-start-pick' || action === 'issue-approve') {
						i.status = 'picking';
						addTimeline(i.timeline, 'Bắt đầu soạn hàng', role2);
					} else if (action === 'issue-finish-pick') {
						if (!i.stockDeducted) {
							deductStockFromIssueLines(whId, i.lines || []);
							i.stockDeducted = true;
							if ((i.outboundType || '') === 'transfer' && i.toWarehouseId && !i.stockCreditedTo) {
								creditStockToWarehouse(i.toWarehouseId, i.lines || []);
								i.stockCreditedTo = i.toWarehouseId;
							}
						}
						i.status = 'packed';
						addTimeline(i.timeline, 'Đã soạn hàng', role2);
					} else if (action === 'issue-reject') {
						var rs = qs('[data-mk-reject-reason="1"]');
						var reason = rs ? String(rs.value || '').trim() : '';
						i.status = 'rejected';
						addTimeline(i.timeline, 'Từ chối phiếu', role2, reason || 'Không nêu lý do');
					} else if (action === 'issue-cancel') {
						if (i.stockDeducted) {
							creditStockToWarehouse(whId, i.lines || []);
							i.stockDeducted = false;
						}
						if ((i.outboundType || '') === 'transfer' && i.stockCreditedTo) {
							deductStockFromIssueLines(i.stockCreditedTo, i.lines || []);
							i.stockCreditedTo = '';
						}
						i.status = 'cancelled';
						addTimeline(i.timeline, 'Huỷ phiếu xuất', role2, 'Huỷ xuất kho');
					} else if (action === 'issue-ship') {
						i.status = 'shipped';
						addTimeline(i.timeline, 'Đã giao hàng', role2);
					} else if (action === 'issue-revert' && targetStatus) {
						var fromN = normalizeIssuePathStatus(i.status);
						var toN = normalizeIssuePathStatus(targetStatus);
						var fromI = pathIndex(ISSUE_PATH, fromN);
						var toI = pathIndex(ISSUE_PATH, toN);
						var packedI = pathIndex(ISSUE_PATH, 'packed');
						if (fromI >= 0 && toI >= 0 && toI < fromI) {
							if (fromI >= packedI && toI < packedI) {
								if (i.stockDeducted) {
									creditStockToWarehouse(whId, i.lines || []);
									i.stockDeducted = false;
								}
								if ((i.outboundType || '') === 'transfer' && i.stockCreditedTo) {
									deductStockFromIssueLines(i.stockCreditedTo, i.lines || []);
									i.stockCreditedTo = '';
								}
							}
							var toLabel = ISSUE_PATH[toI] ? ISSUE_PATH[toI].label : toN;
							var fromLabel = ISSUE_PATH[fromI] ? ISSUE_PATH[fromI].label : fromN;
							i.status = toN;
							addTimeline(i.timeline, 'Quay lại: ' + toLabel, role2, 'Từ ' + fromLabel);
						}
					}
					return i;
				});
				refreshWarehouseUi();
				reopenIssueDialog(whId, id);
				return;
			}
		});

		S.subscribe(function () {
			renderHeader();
			updateRoleBanner();
			renderAll();
		});

		var initialTab = readPersistedWhTab();
		setActiveTab(initialTab);
		renderAll();
		ensureQcLightboxEl();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();

