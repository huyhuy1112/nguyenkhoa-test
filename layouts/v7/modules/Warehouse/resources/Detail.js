/* global jQuery */
(function ($) {
	'use strict';

	function renderWarehouseMovementChart() {
		var el = document.getElementById('WarehouseMovementChart');
		if (!el) {
			return;
		}
		var jsonEl = document.getElementById('mk-wh-movement-json');
		var points = [];
		if (jsonEl && jsonEl.textContent) {
			try {
				points = JSON.parse(jsonEl.textContent.trim());
			} catch (e) {
				points = [];
			}
		}
		if (!points || !points.length) {
			el.innerHTML = '<div class="mk-wh-detail-chart-empty">No movement data yet for this stock identity.</div>';
			return;
		}

		var width = Math.max(el.clientWidth - 16, 320);
		var height = 220;
		var padL = 46;
		var padR = 16;
		var padT = 16;
		var padB = 36;
		var chartW = width - padL - padR;
		var chartH = height - padT - padB;
		var maxY = 0;
		points.forEach(function (p) {
			var inn = parseFloat(p.inbound || 0);
			var out = parseFloat(p.outbound || 0);
			if (inn > maxY) {
				maxY = inn;
			}
			if (out > maxY) {
				maxY = out;
			}
		});
		if (maxY <= 0) {
			maxY = 1;
		}
		var stepX = points.length > 1 ? (chartW / (points.length - 1)) : 0;
		function y(v) {
			return padT + chartH - (v / maxY) * chartH;
		}
		function x(i) {
			return padL + i * stepX;
		}

		function buildPath(key) {
			var d = '';
			points.forEach(function (p, i) {
				var xv = x(i);
				var yv = y(parseFloat(p[key] || 0));
				d += (i === 0 ? 'M ' : ' L ') + xv + ' ' + yv;
			});
			return d;
		}

		var labels = '';
		points.forEach(function (p, i) {
			if (i % Math.ceil(points.length / 6) !== 0 && i !== points.length - 1) {
				return;
			}
			var xv = x(i);
			var rawLabel = (p.event_time || '').toString();
			var lbl = rawLabel.length >= 16 ? rawLabel.substring(0, 16) : rawLabel;
			labels += '<text x="' + xv + '" y="' + (height - 10) + '" text-anchor="middle" fill="#94a3b8" font-size="11">' + lbl + '</text>';
		});
		var yTicks = '';
		for (var t = 0; t <= 4; t++) {
			var val = (maxY * t / 4);
			var yy = y(val);
			yTicks += '<line x1="' + padL + '" y1="' + yy + '" x2="' + (width - padR) + '" y2="' + yy + '" stroke="rgba(148,163,184,.2)" />';
			yTicks += '<text x="' + (padL - 6) + '" y="' + (yy + 4) + '" text-anchor="end" fill="#94a3b8" font-size="11">' + val.toFixed(0) + '</text>';
		}

		var svg = '' +
			'<svg width="' + width + '" height="' + height + '" viewBox="0 0 ' + width + ' ' + height + '">' +
			yTicks +
			'<line x1="' + padL + '" y1="' + (padT + chartH) + '" x2="' + (width - padR) + '" y2="' + (padT + chartH) + '" stroke="rgba(148,163,184,.35)" />' +
			'<path d="' + buildPath('inbound') + '" fill="none" stroke="#22c55e" stroke-width="2.5" />' +
			'<path d="' + buildPath('outbound') + '" fill="none" stroke="#08A045" stroke-width="2.5" />' +
			labels +
			'</svg>';
		el.innerHTML = svg;
	}

	function initWarehouseDetail() {
		var $body = $(document.body);
		if ($body.attr('data-module') !== 'Warehouse' || $body.attr('data-view') !== 'Detail') {
			return;
		}
		if ($body.attr('data-app') !== 'INVENTORY') {
			return;
		}
		$body.addClass('mk-wh-detail-ready');
		renderWarehouseMovementChart();
		$(window).on('resize.mkWhDetailChart', function () {
			renderWarehouseMovementChart();
		});
	}

	$(function () {
		initWarehouseDetail();
	});
})(jQuery);
