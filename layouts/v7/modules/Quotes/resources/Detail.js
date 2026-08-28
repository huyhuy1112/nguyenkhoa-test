/*+***********************************************************************************
 * Quotes Detail (SALES): luxury related-list panel — aligned with Order detail UX.
 *************************************************************************************/

Inventory_Detail_Js("Quotes_Detail_Js",{},{

	init : function() {
		this._super();
		Quotes_Detail_Js.detailCurrentInstance = this;
		if (this.isSalesQuotesDetailUi()) {
			document.body.classList.add('mk-quotes-detail-sales');
			document.body.classList.remove('mk-quotes-detail-ui-loading');
			document.body.classList.add('mk-quotes-detail-ui-ready');
		}
	},

	detailCurrentInstance : false,

	isSalesQuotesDetailUi : function() {
		var body = document.body;
		return !!(
			body
			&& body.getAttribute('data-module') === 'Quotes'
			&& body.getAttribute('data-view') === 'Detail'
			&& body.getAttribute('data-app') === 'SALES'
		);
	},

	refreshRelatedTabBadges : function() {
		if (!this.isSalesQuotesDetailUi()) {
			return;
		}
		var nodes = document.querySelectorAll(
			'.mk-qt-detail-related-tabs li[data-module] > a .numberCircle'
		);
		for (var i = 0; i < nodes.length; i++) {
			var el = nodes[i];
			el.classList.remove('hide');
			var raw = (el.textContent || '').trim();
			var count = parseInt(raw, 10);
			if (isNaN(count)) {
				count = 0;
			}
			el.setAttribute('data-count', String(count));
			if (raw === '') {
				el.textContent = '0';
			}
		}
	},

	/**
	 * Related list panels inside Quotes detail (Sales).
	 */
	getLuxuryRelatedContainers : function(container) {
		var $nodes = jQuery();
		if (container) {
			var $c = jQuery(container);
			if ($c.hasClass('relatedContainer')) {
				$nodes = $c;
			} else {
				var $closest = $c.closest('.relatedContainer');
				if ($closest.length) {
					$nodes = $closest;
				} else {
					$nodes = $c.find('.relatedContainer');
				}
			}
		}
		if (!$nodes.length) {
			$nodes = jQuery('.mk-qt-detail-inner .relatedContainer, .mk-qt-detail-details-row .relatedContainer, #detailView .relatedContainer');
		}
		return $nodes;
	},

	decorateLuxuryRelatedPanel : function(container) {
		if (!this.isSalesQuotesDetailUi()) {
			return;
		}
		this.getLuxuryRelatedContainers(container).each(function() {
			var $related = jQuery(this);
			$related.addClass('mk-luxury-related-panel');
			$related.find('#PageJump, #PageJumpDropDown').remove();
			$related.find('.relatedViewActions').remove();
		});
		this.centerLuxuryRelatedFilters(container);
	},

	/**
	 * Wrap date/datetime related-list filters in luxury single-date group (Documents modifiedtime, etc.).
	 */
	wrapLuxuryRelatedDateInputs : function($searchRow) {
		if (!$searchRow || !$searchRow.length) {
			return;
		}
		var wrapDateInput = function(input, placeholder) {
			if (!input || !input.length) return;
			if (input.closest('.potentials-single-date-group').length) return;
			if (input.data('quotesRelDateApplied') === true) return;

			var clone = input.clone(false);
			clone.attr('placeholder', placeholder || '');
			clone.removeAttr('data-calendar-type');
			clone.removeClass('dateRange daterange dateRangePicker');
			clone.data('quotesRelDateApplied', true);
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
				if (typeof jQuery.fn.datepicker === 'function') {
					clone.datepicker('remove');
					clone.datepicker({ autoclose: true, todayHighlight: true, format: fmt, clearBtn: true });
				}
			} catch (e) {}
		};

		$searchRow.find('input.dateField.listSearchContributor').each(function() {
			var $in = jQuery(this);
			var name = ($in.attr('name') || '').toString();
			var ph = 'Select date';
			if (name.indexOf('modified') > -1) ph = 'Select modified date';
			if (name.indexOf('created') > -1) ph = 'Select created date';
			wrapDateInput($in, ph);
		});
	},

	/**
	 * Center placeholder/value text inside related-list filter cells (after select2/date init).
	 */
	centerLuxuryRelatedFilters : function(container) {
		if (!this.isSalesQuotesDetailUi()) {
			return;
		}
		var thisInstance = this;
		this.getLuxuryRelatedContainers(container).each(function() {
			var $searchRow = jQuery(this).find('.searchRow').first();
			if (!$searchRow.length) {
				return;
			}
			$searchRow.addClass('mk-rel-filter-row-centered');

			if (typeof vtUtils !== 'undefined' && typeof vtUtils.applyFieldElementsView === 'function') {
				vtUtils.applyFieldElementsView($searchRow);
			}
			thisInstance.wrapLuxuryRelatedDateInputs($searchRow);

			$searchRow.find('input.listSearchContributor:not(.select2_input_element)').each(function() {
				var $input = jQuery(this);
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
	},

	syncLuxuryRelatedListEmptyState : function(container) {
		if (!this.isSalesQuotesDetailUi()) {
			return;
		}
		this.decorateLuxuryRelatedPanel(container);
		this.getLuxuryRelatedContainers(container).each(function() {
			var $related = jQuery(this);
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
			var $box = jQuery(
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
	},

	registerLuxuryRelatedListEmptyState : function() {
		var thisInstance = this;
		if (!this.isSalesQuotesDetailUi()) {
			return;
		}
		var run = function(container) {
			thisInstance.syncLuxuryRelatedListEmptyState(container);
			thisInstance.centerLuxuryRelatedFilters(container);
		};
		run();
		if (typeof app !== 'undefined' && app && app.event && typeof app.event.on === 'function') {
			app.event.on('post.relatedListLoad.click', function(event, container) {
				run(container);
				setTimeout(function() { thisInstance.centerLuxuryRelatedFilters(container); }, 200);
				setTimeout(function() { thisInstance.centerLuxuryRelatedFilters(container); }, 600);
			});
		}
		setTimeout(function() { run(); }, 120);
		setTimeout(function() { run(); }, 600);
	},
	registerQuotesActivitiesSingleDateFiltersUi : function() {
		var thisInstance = this;

		var isCalendarRelated = function(container) {
			var relatedContainer = container.find('.relatedContainer:visible').first();
			if (!relatedContainer.length) return false;
			return relatedContainer.find('input.relatedModuleName').val() === 'Calendar';
		};

		var normalizeValue = function(input) {
			if (!input || !input.length) return;
			var v = (input.val() || '').toString();
			if (v.indexOf(',') > -1) input.val(v.split(',')[0].trim());
		};

		var getSearchCellIndexByField = function(relatedContainer, fieldName) {
			var headerRow = relatedContainer.find('.listViewHeaders');
			var searchRow = relatedContainer.find('.searchRow');
			if (!headerRow.length || !searchRow.length) return -1;
			var a = headerRow.find('a.listViewContentHeaderValues[data-fieldname="' + fieldName + '"]').first();
			if (!a.length) return -1;
			return a.closest('th').index();
		};

		var syncStartDateForSearch = function(el) {
			if (!el || !el.length) return;
			var v = (el.val() || '').toString().trim();
			var op = el.closest('th').find('.operatorValue').first();
			if (!v) {
				if (op.length) op.val('');
				return;
			}
			var single = v.indexOf(',') > -1 ? v.split(',')[0].trim() : v;
			el.val(single + ',' + single);
			if (op.length) op.val('bw');
		};

		var syncDueDateOperatorOnly = function(el) {
			if (!el || !el.length) return;
			var v = (el.val() || '').toString().trim();
			var op = el.closest('th').find('.operatorValue').first();
			if (!v) {
				if (op.length) op.val('');
				return;
			}
			if (op.length) op.val('bw');
		};

		/**
		 * @param {string} fieldRole 'date_start' | 'due_date'
		 */
		var forceSingleDateInput = function(input, placeholder, fieldRole) {
			if (!input || !input.length) return;
			if (jQuery('.datepicker-dropdown:visible').length) return;
			var looksRange = (input.attr('data-calendar-type') === 'range' || input.data('dateRangePicker') || input.hasClass('dateRange') || input.hasClass('daterange'));
			if (input.data('potentialsSingleDateApplied') === true && !looksRange) return;

			var v = (input.val() || '').toString().trim();
			if (v.indexOf(',') > -1) v = v.split(',')[0].trim();

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

			jQuery('.date-picker-wrapper:visible').remove();

			try {
				var fmt = (typeof app !== 'undefined' && typeof app.getDateFormat === 'function') ? app.getDateFormat() : 'mm-dd-yyyy';
				fmt = (fmt || 'mm-dd-yyyy').toString().toLowerCase();
				if (typeof jQuery.fn.datepicker === 'function') {
					clone.datepicker('remove');
					clone.datepicker({ autoclose: true, todayHighlight: true, format: fmt, clearBtn: true });
					if (fieldRole === 'date_start') {
						clone.on('changeDate.potentialsCalStart', function() {
							try { clone.datepicker('hide'); } catch(e) {}
							syncStartDateForSearch(clone);
						});
						clone.on('change.potentialsCalStart blur.potentialsCalStart', function() {
							syncStartDateForSearch(clone);
						});
					} else if (fieldRole === 'due_date' || fieldRole === 'recurringtype') {
						clone.on('changeDate.potentialsCalDue', function() {
							try { clone.datepicker('hide'); } catch(e) {}
							syncDueDateOperatorOnly(clone);
						});
						clone.on('change.potentialsCalDue blur.potentialsCalDue', function() {
							syncDueDateOperatorOnly(clone);
						});
					} else {
						clone.on('changeDate', function() { try { clone.datepicker('hide'); } catch(e) {} });
					}
				}
			} catch (e) {}

			clone.on('focus click', function() {
				setTimeout(function() { jQuery('.date-picker-wrapper:visible').remove(); }, 0);
			});
		};

		var apply = function(container) {
			container = container ? jQuery(container) : jQuery('div.details');
			if (!isCalendarRelated(container)) return;
			var relatedContainer = container.find('.relatedContainer:visible').first();
			var searchRow = relatedContainer.find('.searchRow');
			if (!searchRow.length) return;

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
			thisInstance.centerLuxuryRelatedFilters(container);
		};

		app.event.off('post.relatedListLoad.click.QuotesActivitiesSingleDate');
		app.event.on('post.relatedListLoad.click.QuotesActivitiesSingleDate', function(event, container) {
			setTimeout(function() { apply(container); }, 0);
			setTimeout(function() { apply(container); }, 150);
			setTimeout(function() { apply(container); }, 400);
			setTimeout(function() { apply(container); }, 800);
		});

		jQuery(document).off('ajaxComplete.QuotesActivitiesSingleDate');
		jQuery(document).on('ajaxComplete.QuotesActivitiesSingleDate', function() {
			setTimeout(function() { apply(jQuery('div.details')); }, 0);
		});

		/* mousedown runs before click so DOM is ready for getRelatedListSearchParams(); core handler still runs loadRelatedList. */
		var detailViewContainer = this.getDetailViewContainer ? this.getDetailViewContainer() : jQuery('.detailViewContainer');
		detailViewContainer.off('mousedown.quotesActivitiesSearchPrep', '[data-trigger="relatedListSearch"]');
		detailViewContainer.on('mousedown.quotesActivitiesSearchPrep', '[data-trigger="relatedListSearch"]', function() {
			var relatedContainer = jQuery(this).closest('.relatedContainer');
			if (!relatedContainer.length) return;
			if (relatedContainer.find('input.relatedModuleName').val() !== 'Calendar') return;
			if (typeof app === 'undefined' || app.getModuleName() !== 'Quotes') return;
			var searchRow = relatedContainer.find('.searchRow').first();
			if (!searchRow.length) return;
			var startInput = searchRow.find('input[name="date_start"].listSearchContributor').first();
			if (!startInput.length) startInput = searchRow.find('input[name="date_start"]').first();
			if (startInput.length) {
				var sv = (startInput.val() || '').toString().trim();
				var opS = startInput.closest('th').find('.operatorValue').first();
				if (!sv) {
					if (opS.length) opS.val('');
				} else {
					var sub = sv.indexOf(',') === -1 ? sv + ',' + sv : sv;
					startInput.val(sub);
					if (opS.length) opS.val('bw');
				}
			}
			var dueInput = searchRow.find('input[name="due_date"].listSearchContributor').first();
			if (!dueInput.length) dueInput = searchRow.find('input[name="due_date"]').first();
			if (dueInput.length) {
				var dv = (dueInput.val() || '').toString().trim();
				var opD = dueInput.closest('th').find('.operatorValue').first();
				if (!dv) {
					if (opD.length) opD.val('');
				} else {
					if (opD.length) opD.val('bw');
				}
			}
			var recurringInput = searchRow.find('input[name="recurringtype"].listSearchContributor').first();
			if (!recurringInput.length) recurringInput = searchRow.find('input[name="recurringtype"]').first();
			if (recurringInput.length) {
				var rv = (recurringInput.val() || '').toString().trim();
				var opR = recurringInput.closest('th').find('.operatorValue').first();
				if (!rv) {
					if (opR.length) opR.val('');
				} else {
					if (opR.length) opR.val('bw');
				}
			}
		});

		jQuery(function() { setTimeout(function() { apply(jQuery('div.details')); }, 0); });
	},

	registerEvents : function() {
		this._super();
		var thisInstance = this;
		if (!this.isSalesQuotesDetailUi()) {
			return;
		}
		this.refreshRelatedTabBadges();
		this.registerLuxuryRelatedListEmptyState();
		if (typeof app !== 'undefined' && app && app.event && typeof app.event.on === 'function') {
			app.event.on('post.summaryview.load', function() {
				thisInstance.refreshRelatedTabBadges();
			});
			app.event.on('post.detailedview.load', function() {
				thisInstance.refreshRelatedTabBadges();
			});
			app.event.on('post.relatedListLoad.click', function() {
				thisInstance.refreshRelatedTabBadges();
			});
			app.event.on('post.QuickCreateForm.save', function() {
				setTimeout(function() {
					thisInstance.syncLuxuryRelatedListEmptyState();
					thisInstance.refreshRelatedTabBadges();
				}, 350);
			});
		}
		this.registerQuotesActivitiesSingleDateFiltersUi();
		this.registerQuotesRelatedDateFiltersUi();
	},

	/**
	 * Style datetime filters on Documents and other related lists (modifiedtime, etc.).
	 */
	registerQuotesRelatedDateFiltersUi : function() {
		var thisInstance = this;
		var apply = function(container) {
			if (!thisInstance.isSalesQuotesDetailUi()) return;
			thisInstance.centerLuxuryRelatedFilters(container);
		};

		app.event.off('post.relatedListLoad.click.QuotesRelatedDateFilters');
		app.event.on('post.relatedListLoad.click.QuotesRelatedDateFilters', function(event, container) {
			setTimeout(function() { apply(container); }, 0);
			setTimeout(function() { apply(container); }, 300);
		});
		jQuery(function() { setTimeout(function() { apply(); }, 0); });
	}

});
