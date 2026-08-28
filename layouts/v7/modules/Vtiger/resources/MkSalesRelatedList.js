/**
 * Shared luxury related-list UI for SALES detail pages (Accounts, Contacts, SalesOrder, etc.)
 * and Project MANAGEMENT detail related tabs (milestones, calendar, documents, …).
 * Quotes and Potentials use module-specific Detail.js — skipped here to avoid double init.
 */
(function ($) {
	'use strict';

	var SKIP_MODULES = { Quotes: true, Potentials: true };

	function isLuxuryRelatedDetailUi() {
		var b = document.body;
		if (!b || b.getAttribute('data-view') !== 'Detail') {
			return false;
		}
		var appName = b.getAttribute('data-app');
		if (appName === 'SALES') {
			return true;
		}
		if (appName === 'MANAGEMENT' && b.getAttribute('data-module') === 'Project') {
			return document.documentElement.classList.contains('mk-project-detail-management');
		}
		return false;
	}

	function shouldRun() {
		if (!isLuxuryRelatedDetailUi()) {
			return false;
		}
		if (typeof app !== 'undefined' && app && typeof app.getModuleName === 'function') {
			var mod = app.getModuleName();
			if (SKIP_MODULES[mod]) {
				return false;
			}
		}
		return true;
	}

	function isListRelatedContainer($related) {
		if (!$related || !$related.length) {
			return false;
		}
		if ($related.find('.listview-table').length) {
			return true;
		}
		return false;
	}

	function getContainers(container) {
		var $nodes = $();
		if (container) {
			var $c = $(container);
			if ($c.hasClass('relatedContainer')) {
				$nodes = $c;
			} else {
				$nodes = $c.find('.relatedContainer');
			}
		}
		if (!$nodes.length) {
			$nodes = $('.detailViewContainer .relatedContainer, .detailview-content .relatedContainer, #detailView .relatedContainer, .details .relatedContainer');
		}
		return $nodes.filter(function () {
			return isListRelatedContainer($(this));
		});
	}

	function wrapLuxuryRelatedDateInputs($searchRow) {
		if (!$searchRow || !$searchRow.length) {
			return;
		}
		var wrapDateInput = function (input, placeholder) {
			if (!input || !input.length) {
				return;
			}
			if (input.closest('.potentials-single-date-group').length) {
				return;
			}
			if (input.data('mkRelDateApplied') === true) {
				return;
			}

			var clone = input.clone(false);
			clone.attr('placeholder', placeholder || '');
			clone.removeAttr('data-calendar-type');
			clone.removeClass('dateRange daterange dateRangePicker');
			clone.data('mkRelDateApplied', true);
			var rowFluid = input.closest('.row-fluid');
			input.replaceWith(clone);
			if (!clone.closest('.input-group.potentials-single-date-group').length) {
				clone.wrap('<div class="input-group potentials-single-date-group"></div>');
				clone.before('<span class="input-group-addon"><i class="fa fa-calendar"></i></span>');
			}
			var dateGroup = clone.closest('.potentials-single-date-group');
			if (rowFluid.length && dateGroup.length) {
				rowFluid.empty().append(dateGroup);
			}
			try {
				var fmt = (typeof app !== 'undefined' && typeof app.getDateFormat === 'function') ? app.getDateFormat() : 'mm-dd-yyyy';
				fmt = (fmt || 'mm-dd-yyyy').toString().toLowerCase();
				if (typeof $.fn.datepicker === 'function') {
					clone.datepicker('remove');
					clone.datepicker({ autoclose: true, todayHighlight: true, format: fmt, clearBtn: true });
				}
			} catch (e) {}
		};

		$searchRow.find('input.dateField.listSearchContributor').each(function () {
			var $in = $(this);
			var name = ($in.attr('name') || '').toString();
			var ph = 'Select date';
			if (name.indexOf('modified') > -1) {
				ph = 'Select modified date';
			}
			if (name.indexOf('created') > -1) {
				ph = 'Select created date';
			}
			wrapDateInput($in, ph);
		});
	}

	function ensureMissingRelatedFilters(container) {
		getContainers(container).each(function () {
			var $related = $(this);
			var $headerCells = $related.find('thead tr.listViewHeaders th').not(':first').not('.mk-rel-spacer-col');
			var $searchCells = $related.find('thead tr.searchRow th').not('.inline-search-btn').not('.mk-rel-spacer-col');
			$headerCells.each(function (idx) {
				var $searchTh = $searchCells.eq(idx);
				if (!$searchTh.length) {
					return;
				}
				if ($searchTh.find('.listSearchContributor, .select2-container').length) {
					return;
				}
				if ($searchTh.find('input:not(.operatorValue), select, textarea').length) {
					return;
				}
				var fieldName = $(this).find('a.listViewContentHeaderValues').data('fieldname');
				if (!fieldName) {
					return;
				}
				var $input = $('<input type="text" class="listSearchContributor inputElement" />').attr({
					name: fieldName,
					'data-field-type': 'string'
				});
				$searchTh.empty().append($input).append('<input type="hidden" class="operatorValue" value="">');
			});
		});
	}

	function centerLuxuryRelatedFilters(container) {
		getContainers(container).each(function () {
			var $searchRow = $(this).find('.searchRow').first();
			if (!$searchRow.length) {
				return;
			}
			$searchRow.addClass('mk-rel-filter-row-centered');

			ensureMissingRelatedFilters(container);

			if (typeof vtUtils !== 'undefined' && typeof vtUtils.applyFieldElementsView === 'function') {
				vtUtils.applyFieldElementsView($searchRow);
			}
			wrapLuxuryRelatedDateInputs($searchRow);

			$searchRow.find('input.listSearchContributor:not(.select2_input_element)').each(function () {
				var $input = $(this);
				$input.css('text-align', 'center');
				if ($input.closest('.potentials-single-date-group').length) {
					$input.css('padding-right', '40px');
				}
			});

			$searchRow.find('.select2-container .select2-choice').css({
				'text-align': 'center',
				'padding-left': '30px',
				'padding-right': '30px'
			});
			$searchRow.find('.select2-container .select2-choice > span, .select2-container .select2-chosen').css({
				'text-align': 'center',
				'margin-right': '0',
				'margin-left': '0',
				'width': '100%',
				'display': 'block',
				'float': 'none'
			});

			$searchRow.find('.select2-container-multi .select2-choices > li').css('float', 'none');
			$searchRow.find('.select2-container-multi .select2-search-field').css({
				'float': 'none',
				'width': '100%',
				'display': 'flex',
				'justify-content': 'center'
			});
			$searchRow.find('.select2-container-multi .select2-search-field input').css({
				'text-align': 'center',
				'width': '100%'
			});
		});
	}

	function decorateLuxuryRelatedPanel(container) {
		getContainers(container).each(function () {
			var $related = $(this);
			if (!isListRelatedContainer($related)) {
				return;
			}
			$related.addClass('mk-luxury-related-panel');
			$related.find('#PageJump, #PageJumpDropDown').remove();
			$related.find('.relatedViewActions').remove();
		});
		centerLuxuryRelatedFilters(container);
	}

	function syncLuxuryRelatedListEmptyState(container) {
		decorateLuxuryRelatedPanel(container);
		getContainers(container).each(function () {
			var $related = $(this);
			var $contents = $related.find('.relatedContents').first();
			if (!$contents.length) {
				return;
			}
			var $scroll = $contents.find('.bottomscroll-div').first();
			if (!$scroll.length) {
				$scroll = $contents;
			}
			var hasRows = $contents.find('tr.listViewEntries').length > 0;
			$contents.find('.mk-luxury-related-empty').remove();
			$contents.find('.emptyRecordsDiv, .noData').remove();

			if (hasRows) {
				$contents.removeClass('mk-luxury-related--empty');
				$related.removeClass('mk-luxury-related-panel--empty');
				var dataColCount = $contents.find('thead tr.listViewHeaders th').not('.mk-rel-spacer-col').length - 1;
				if (dataColCount > 0 && dataColCount <= 5) {
					$related.addClass('mk-luxury-related-panel--compact-cols');
				} else {
					$related.removeClass('mk-luxury-related-panel--compact-cols');
				}
				return;
			}
			$related.removeClass('mk-luxury-related-panel--compact-cols');
			$contents.addClass('mk-luxury-related--empty');
			$related.addClass('mk-luxury-related-panel--empty');
			var $table = $scroll.find('.listview-table').first();
			var title = 'Chưa có dữ liệu';
			var hint = 'Danh sách đang trống. Nhấn nút thêm phía trên hoặc dùng bộ lọc để tìm kiếm.';
			if (typeof app !== 'undefined' && app && typeof app.vtranslate === 'function') {
				var noData = app.vtranslate('JS_NO_DATA_AVAILABLE');
				if (noData && noData !== 'JS_NO_DATA_AVAILABLE') {
					title = noData;
				}
			}
			var $box = $(
				'<div class="mk-luxury-related-empty" role="status">' +
					'<p class="mk-luxury-related-empty__title">' + title + '</p>' +
					'<p class="mk-luxury-related-empty__hint">' + hint + '</p>' +
				'</div>'
			);
			if ($table.length) {
				$table.after($box);
			} else {
				$scroll.append($box);
			}
		});
	}

	function isCalendarRelated(container) {
		var relatedContainer = $(container).find('.relatedContainer:visible').first();
		if (!relatedContainer.length) {
			relatedContainer = getContainers(container).filter(':visible').first();
		}
		if (!relatedContainer.length) {
			return false;
		}
		return relatedContainer.find('input.relatedModuleName').val() === 'Calendar';
	}

	function getSearchCellIndexByField(relatedContainer, fieldName) {
		var headerRow = relatedContainer.find('.listViewHeaders');
		var searchRow = relatedContainer.find('.searchRow');
		if (!headerRow.length || !searchRow.length) {
			return -1;
		}
		var a = headerRow.find('a.listViewContentHeaderValues[data-fieldname="' + fieldName + '"]').first();
		if (!a.length) {
			return -1;
		}
		return a.closest('th').index();
	}

	function registerCalendarSingleDateFiltersUi() {
		var normalizeValue = function (input) {
			if (!input || !input.length) {
				return;
			}
			var v = (input.val() || '').toString();
			if (v.indexOf(',') > -1) {
				input.val(v.split(',')[0].trim());
			}
		};

		var syncStartDateForSearch = function (el) {
			if (!el || !el.length) {
				return;
			}
			var v = (el.val() || '').toString().trim();
			var op = el.closest('th').find('.operatorValue').first();
			if (!v) {
				if (op.length) {
					op.val('');
				}
				return;
			}
			var single = v.indexOf(',') > -1 ? v.split(',')[0].trim() : v;
			el.val(single + ',' + single);
			if (op.length) {
				op.val('bw');
			}
		};

		var syncDueDateOperatorOnly = function (el) {
			if (!el || !el.length) {
				return;
			}
			var v = (el.val() || '').toString().trim();
			var op = el.closest('th').find('.operatorValue').first();
			if (!v) {
				if (op.length) {
					op.val('');
				}
				return;
			}
			if (op.length) {
				op.val('bw');
			}
		};

		var forceSingleDateInput = function (input, placeholder, fieldRole) {
			if (!input || !input.length) {
				return;
			}
			if ($('.datepicker-dropdown:visible').length) {
				return;
			}
			var looksRange = (input.attr('data-calendar-type') === 'range' || input.data('dateRangePicker') || input.hasClass('dateRange') || input.hasClass('daterange'));
			if (input.data('potentialsSingleDateApplied') === true && !looksRange) {
				return;
			}

			var v = (input.val() || '').toString().trim();
			if (v.indexOf(',') > -1) {
				v = v.split(',')[0].trim();
			}

			var clone = input.clone(false);
			clone.val(v);
			clone.attr('placeholder', placeholder);
			clone.removeAttr('data-calendar-type');
			clone.removeAttr('data-field-type');
			clone.removeClass('dateRange daterange dateRangePicker');
			if (!clone.hasClass('listSearchContributor')) {
				clone.addClass('listSearchContributor');
			}
			clone.data('potentialsSingleDateApplied', true);
			var rowFluid = input.closest('.row-fluid');
			input.replaceWith(clone);

			if (!clone.closest('.input-group.potentials-single-date-group').length) {
				clone.wrap('<div class="input-group potentials-single-date-group"></div>');
				clone.before('<span class="input-group-addon"><i class="fa fa-calendar"></i></span>');
			}

			var dateGroup = clone.closest('.potentials-single-date-group');
			if (rowFluid.length && dateGroup.length) {
				rowFluid.empty().append(dateGroup);
			}

			$('.date-picker-wrapper:visible').remove();

			try {
				var fmt = (typeof app !== 'undefined' && typeof app.getDateFormat === 'function') ? app.getDateFormat() : 'mm-dd-yyyy';
				fmt = (fmt || 'mm-dd-yyyy').toString().toLowerCase();
				if (typeof $.fn.datepicker === 'function') {
					clone.datepicker('remove');
					clone.datepicker({ autoclose: true, todayHighlight: true, format: fmt, clearBtn: true });
					if (fieldRole === 'date_start') {
						clone.on('changeDate.mkCalStart', function () {
							try { clone.datepicker('hide'); } catch (e) {}
							syncStartDateForSearch(clone);
						});
						clone.on('change.mkCalStart blur.mkCalStart', function () {
							syncStartDateForSearch(clone);
						});
					} else if (fieldRole === 'due_date' || fieldRole === 'recurringtype') {
						clone.on('changeDate.mkCalDue', function () {
							try { clone.datepicker('hide'); } catch (e) {}
							syncDueDateOperatorOnly(clone);
						});
						clone.on('change.mkCalDue blur.mkCalDue', function () {
							syncDueDateOperatorOnly(clone);
						});
					} else {
						clone.on('changeDate', function () {
							try { clone.datepicker('hide'); } catch (e) {}
						});
					}
				}
			} catch (e) {}

			clone.on('focus click', function () {
				setTimeout(function () { $('.date-picker-wrapper:visible').remove(); }, 0);
			});
		};

		var applyCalendarFilters = function (container) {
			container = container ? $(container) : $('div.details');
			if (!isCalendarRelated(container)) {
				return;
			}
			var relatedContainer = container.find('.relatedContainer:visible').first();
			var searchRow = relatedContainer.find('.searchRow');
			if (!searchRow.length) {
				return;
			}

			var startIdx = getSearchCellIndexByField(relatedContainer, 'date_start');
			var dueIdx = getSearchCellIndexByField(relatedContainer, 'due_date');
			var recurringIdx = getSearchCellIndexByField(relatedContainer, 'recurringtype');

			var startInput = (startIdx >= 0) ? searchRow.find('th').eq(startIdx).find('input[name="date_start"]').first() : searchRow.find('input[name="date_start"]').first();
			var dueInput = (dueIdx >= 0) ? searchRow.find('th').eq(dueIdx).find('input[name="due_date"]').first() : searchRow.find('input[name="due_date"]').first();
			var recurringInput = (recurringIdx >= 0) ? searchRow.find('th').eq(recurringIdx).find('input[name="recurringtype"]').first() : searchRow.find('input[name="recurringtype"]').first();

			normalizeValue(startInput);
			normalizeValue(dueInput);
			normalizeValue(recurringInput);
			forceSingleDateInput(startInput, 'Select start date', 'date_start');
			forceSingleDateInput(dueInput, 'Select due date', 'due_date');
			forceSingleDateInput(recurringInput, 'Select repeat date', 'recurringtype');
			centerLuxuryRelatedFilters(container);
		};

		if (typeof app !== 'undefined' && app.event && typeof app.event.on === 'function') {
			app.event.off('post.relatedListLoad.click.MkSalesCalendarDate');
			app.event.on('post.relatedListLoad.click.MkSalesCalendarDate', function (event, container) {
				setTimeout(function () { applyCalendarFilters(container); }, 0);
				setTimeout(function () { applyCalendarFilters(container); }, 150);
				setTimeout(function () { applyCalendarFilters(container); }, 400);
				setTimeout(function () { applyCalendarFilters(container); }, 800);
			});
		}

		$(document).off('ajaxComplete.MkSalesCalendarDate');
		$(document).on('ajaxComplete.MkSalesCalendarDate', function () {
			setTimeout(function () { applyCalendarFilters($('div.details')); }, 0);
		});

		var detailViewContainer = $('.detailViewContainer');
		detailViewContainer.off('mousedown.mkSalesCalendarSearchPrep', '[data-trigger="relatedListSearch"]');
		detailViewContainer.on('mousedown.mkSalesCalendarSearchPrep', '[data-trigger="relatedListSearch"]', function () {
			var relatedContainer = $(this).closest('.relatedContainer');
			if (!relatedContainer.length) {
				return;
			}
			if (relatedContainer.find('input.relatedModuleName').val() !== 'Calendar') {
				return;
			}
			var searchRow = relatedContainer.find('.searchRow').first();
			if (!searchRow.length) {
				return;
			}
			var startInput = searchRow.find('input[name="date_start"].listSearchContributor').first();
			if (!startInput.length) {
				startInput = searchRow.find('input[name="date_start"]').first();
			}
			if (startInput.length) {
				var sv = (startInput.val() || '').toString().trim();
				var opS = startInput.closest('th').find('.operatorValue').first();
				if (!sv) {
					if (opS.length) {
						opS.val('');
					}
				} else {
					var sub = sv.indexOf(',') === -1 ? sv + ',' + sv : sv;
					startInput.val(sub);
					if (opS.length) {
						opS.val('bw');
					}
				}
			}
			var dueInput = searchRow.find('input[name="due_date"].listSearchContributor').first();
			if (!dueInput.length) {
				dueInput = searchRow.find('input[name="due_date"]').first();
			}
			if (dueInput.length) {
				var dv = (dueInput.val() || '').toString().trim();
				var opD = dueInput.closest('th').find('.operatorValue').first();
				if (!dv) {
					if (opD.length) {
						opD.val('');
					}
				} else if (opD.length) {
					opD.val('bw');
				}
			}
			var recurringInput = searchRow.find('input[name="recurringtype"].listSearchContributor').first();
			if (!recurringInput.length) {
				recurringInput = searchRow.find('input[name="recurringtype"]').first();
			}
			if (recurringInput.length) {
				var rv = (recurringInput.val() || '').toString().trim();
				var opR = recurringInput.closest('th').find('.operatorValue').first();
				if (!rv) {
					if (opR.length) {
						opR.val('');
					}
				} else if (opR.length) {
					opR.val('bw');
				}
			}
		});

		$(function () {
			setTimeout(function () { applyCalendarFilters($('div.details')); }, 0);
		});
	}

	function registerEvents() {
		if (!shouldRun()) {
			return;
		}

		var run = function (container) {
			syncLuxuryRelatedListEmptyState(container);
			centerLuxuryRelatedFilters(container);
		};

		run();

		if (typeof app !== 'undefined' && app.event && typeof app.event.on === 'function') {
			app.event.off('post.relatedListLoad.click.MkSalesRelatedList');
			app.event.on('post.relatedListLoad.click.MkSalesRelatedList', function (event, container) {
				run(container);
				setTimeout(function () { centerLuxuryRelatedFilters(container); }, 200);
				setTimeout(function () { centerLuxuryRelatedFilters(container); }, 600);
			});

			app.event.off('post.QuickCreateForm.save.MkSalesRelatedList');
			app.event.on('post.QuickCreateForm.save.MkSalesRelatedList', function () {
				setTimeout(function () { syncLuxuryRelatedListEmptyState(); }, 350);
			});

			app.event.off('post.relatedListLoad.click.MkSalesRelatedDate');
			app.event.on('post.relatedListLoad.click.MkSalesRelatedDate', function (event, container) {
				setTimeout(function () { centerLuxuryRelatedFilters(container); }, 0);
				setTimeout(function () { centerLuxuryRelatedFilters(container); }, 300);
			});
		}

		setTimeout(function () { run(); }, 120);
		setTimeout(function () { run(); }, 600);

		registerCalendarSingleDateFiltersUi();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', registerEvents);
	} else {
		registerEvents();
	}
})(jQuery);
