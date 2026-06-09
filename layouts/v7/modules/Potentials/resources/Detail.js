/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Vtiger_Detail_Js("Potentials_Detail_Js",{

	//cache will store the convert Potential data(Model)
	cache : {},

	//Holds detail view instance
	detailCurrentInstance : false,

	/*
	 * function to trigger Convert Potential action
	 * @param: Convert Potential url, currentElement.
	 */
	convertPotential : function(convertPotentialUrl, buttonElement) {
		var instance = Potentials_Detail_Js.detailCurrentInstance;
		//Initially clear the elements to overwtite earliear cache
		instance.convertPotentialContainer = false;
		instance.convertPotentialForm = false;
		instance.convertPotentialModules = false;
		if(jQuery.isEmptyObject(Potentials_Detail_Js.cache)) {
			app.request.get({"url": convertPotentialUrl}).then(function (err, data) {
					if(data) {
						Potentials_Detail_Js.cache = data;
						instance.displayConvertPotentialModel(data, buttonElement);
					}
				},
				function(error,err){

				}
			);
		} else {
			instance.displayConvertPotentialModel(Potentials_Detail_Js.cache, buttonElement);
		}
	}
},{

	//Contains the convert Potential form
	convertPotentialForm : false,

	//contains the convert Potential container
	convertPotentialContainer : false,

	//contains all the checkbox elements of modules
	convertPotentialModules : false,

	detailViewRecentContactsLabel : 'Contacts',
	detailViewRecentProductsTabLabel : 'Products',

	//constructor
	init : function() {
		this._super();
		Potentials_Detail_Js.detailCurrentInstance = this;
		if (this.isSalesOpportunityDetailUi()) {
			document.body.classList.add('mk-opportunity-detail-sales');
			document.body.classList.remove('mk-opportunity-detail-ui-loading');
			document.body.classList.add('mk-opportunity-detail-ui-ready');
		}
	},

	isSalesOpportunityDetailUi : function() {
		var body = document.body;
		return !!(
			body
			&& body.getAttribute('data-module') === 'Potentials'
			&& body.getAttribute('data-view') === 'Detail'
			&& body.getAttribute('data-app') === 'SALES'
		);
	},

	refreshRelatedTabBadges : function() {
		if (!this.isSalesOpportunityDetailUi()) {
			return;
		}
		var nodes = document.querySelectorAll(
			'.mk-opportunity-detail-related-tabs li[data-module] > a .numberCircle'
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
	 * Related list panels inside Opportunity detail (Sales).
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
			$nodes = jQuery('.mk-opportunity-detail-inner .relatedContainer, .mk-opportunity-detail-details-row .relatedContainer, #detailView .relatedContainer');
		}
		return $nodes;
	},

	decorateLuxuryRelatedPanel : function(container) {
		if (!this.isSalesOpportunityDetailUi()) {
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
			if (input.data('potentialsRelDateApplied') === true) return;

			var clone = input.clone(false);
			clone.attr('placeholder', placeholder || '');
			clone.removeAttr('data-calendar-type');
			clone.removeClass('dateRange daterange dateRangePicker');
			clone.data('potentialsRelDateApplied', true);
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
		if (!this.isSalesOpportunityDetailUi()) {
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
		if (!this.isSalesOpportunityDetailUi()) {
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
		if (!this.isSalesOpportunityDetailUi()) {
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

	/*
	 * function to get Convert Potential Form
	 */
	getConvertPotentialForm : function() {
		if(this.convertPotentialForm == false) {
			this.convertPotentialForm = jQuery('#convertPotentialForm');
		}
		return this.convertPotentialForm;
	},

	/*
	 * function to get Convert Potential Container
	 */
	getConvertPotentialContainer : function() {
		if(this.convertPotentialContainer == false) {
			this.convertPotentialContainer = jQuery('#potentialAccordion');
		}
		return this.convertPotentialContainer;
	},

	/*
	 * function to get all the checkboxes which are representing the modules selection
	 */
	getConvertPotentialModules : function() {
		var container = this.getConvertPotentialContainer();
		if(this.convertPotentialModules == false) {
			this.convertPotentialModules = jQuery('.convertPotentialModuleSelection', container);
		}
		return this.convertPotentialModules;
	},

	/*
	 * function to disable the Convert Potential button
	 */
	disableConvertPotentialButton : function(button) {
		jQuery(button).attr('disabled','disabled');
	},

	/*
	 * function to enable the Convert Potential button
	 */
	enableConvertPotentialButton : function(button) {
		jQuery(button).removeAttr('disabled');
	},

	/*
	 * function to enable all the input and textarea elements
	 */
	removeDisableAttr : function(moduleBlock) {
		moduleBlock.find('input,textarea,select').removeAttr('disabled');
	},

	/*
	 * function to disable all the input and textarea elements
	 */
	addDisableAttr : function(moduleBlock) {
		moduleBlock.find('input,textarea,select').attr('disabled', 'disabled');
	},

	/*
	 * function to display the convert Potential model
	 * @param: data used to show the model, currentElement.
	 */
	displayConvertPotentialModel : function(data, buttonElement) {
		var instance = this;
		var errorElement = jQuery(data).find('#convertPotentialError');
		if(errorElement.length != '0') {

		} else {
			var callBackFunction = function(data){
				var editViewObj = Vtiger_Edit_Js.getInstance();
				jQuery(data).find('.fieldInfo').collapse({
					'parent': '#potentialAccordion',
					'toggle' : false
				});
				app.helper.showVerticalScroll(jQuery(data).find('#potentialAccordion'), {'setHeight': '350px'});
				editViewObj.registerBasicEvents(data);
				var checkBoxElements = instance.getConvertPotentialModules();
				jQuery.each(checkBoxElements, function(index, element){
					instance.checkingModuleSelection(element);
				});
				instance.registerForReferenceField();
				instance.registerConvertPotentialEvents();
				instance.registerConvertPotentialSubmit();
			}
			app.helper.showModal(data, {"cb": callBackFunction});
		}
	},

	/*
	 * function to check which module is selected 
	 * to disable or enable all the elements with in the block
	 */
	checkingModuleSelection : function(element) {
		var instance = this;
		var module = jQuery(element).val();
		var moduleBlock = jQuery(element).closest('.accordion-group').find('#'+module+'_FieldInfo');
		if(jQuery(element).is(':checked')) {
			instance.removeDisableAttr(moduleBlock);
		} else {
			instance.addDisableAttr(moduleBlock);
		}
	},

	registerForReferenceField : function() {
		var container = this.getConvertPotentialContainer();
		var referenceField = jQuery('.reference', container);
		if(referenceField.length > 0) {
			jQuery('#ProjectModule').attr('readonly', 'readonly');
		}
	},

	/*
	 * function to register Convert Potential Events
	 */
	registerConvertPotentialEvents : function() {
		var container = this.getConvertPotentialContainer();
		var instance = this;

		//Trigger Event to change the icon while shown and hidden the accordion body 
		container.on('click', '.accordion-group', function (e) { 
			var currentElement = jQuery(e.currentTarget).find('.Project_faAngle');
			if (jQuery('.Project_FieldInfo').hasClass('in')) {
				currentElement.removeClass('fa-angle-up');
				currentElement.addClass('fa-angle-down');
			} else {
				currentElement.removeClass('fa-angle-down');
				currentElement.addClass('fa-angle-up');
			}
		});

		//Trigger Event on click of the Modules selection to convert the lead 
		container.on('click','.convertPotentialModuleSelection', function(e){
			var currentTarget = jQuery(e.currentTarget);
			var currentModuleName = currentTarget.val();
			var moduleBlock = currentTarget.closest('.accordion-group').find('#'+currentModuleName+'_FieldInfo');

			if(currentTarget.is(':checked')) {
				moduleBlock.collapse('show');
				instance.removeDisableAttr(moduleBlock);
			} else {
				moduleBlock.collapse('hide');
				instance.addDisableAttr(moduleBlock);
			}
			e.stopImmediatePropagation();
		});
	},

	/*
	 * function to register Convert Potential Submit Event
	 */
	registerConvertPotentialSubmit : function() {
		var thisInstance = this;
		var formElement = this.getConvertPotentialForm();
		var params = {
			"ignore": "disabled",
			submitHandler: function (form) {
			   var convertPotentialModuleElements = thisInstance.getConvertPotentialModules();
			   var moduleArray = [];
			   var projectModel = formElement.find('#ProjectModule');

			   jQuery.each(convertPotentialModuleElements, function(index, element) {
				   if(jQuery(element).is(':checked')) {
					   moduleArray.push(jQuery(element).val());
				   }
			   });
			   formElement.find('input[name="modules"]').val(JSON.stringify(moduleArray));

			   var projectElement = projectModel.length;

			   if(projectElement != '0') {
				   if(jQuery.inArray('Project',moduleArray) == -1) {
					   app.helper.showErrorNotification({message:app.vtranslate('JS_SELECT_PROJECT_TO_CONVERT_LEAD')});
					   return false;
				   } 
			   }
			   return true;
			}
		 }
		formElement.vtValidate(params);
	},

	/**
	 * Function which will register all the events
	 */
	registerEvents : function() {
		this._super();
		var detailContentsHolder = this.getContentHolder();
		var thisInstance = this;

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
		}

		this.registerPotentialsSummaryProductsServicesPopup();
		this.registerPotentialsContactsOrganizationFilterUi();
		this.registerPotentialsQuotesReferenceFiltersUi();
		this.registerPotentialsActivitiesSingleDateFiltersUi();
		this.registerPotentialsRelatedDateFiltersUi();

		detailContentsHolder.on('click','.moreRecentContacts', function(){ 
			var recentContactsTab = thisInstance.getTabByLabel(thisInstance.detailViewRecentContactsLabel); 
			recentContactsTab.trigger('click'); 
		}); 

		detailContentsHolder.on('click','.moreRecentProducts', function(){ 
			var recentProductsTab = thisInstance.getTabByLabel(thisInstance.detailViewRecentProductsTabLabel); 
			recentProductsTab.trigger('click'); 
		});
	},

	/**
	 * Restore Organization filter input in Potentials → Contacts related list
	 * without overriding RelatedList.tpl (avoids duplicated tables/rows).
	 *
	 * Backend filtering is handled in modules/Potentials/models/RelationListView.php (account_id -> accountname).
	 */
	registerPotentialsContactsOrganizationFilterUi : function() {
		var STORAGE_KEY = function() {
			var rid = (typeof app !== 'undefined' && typeof app.getRecordId === 'function') ? app.getRecordId() : '';
			return 'Potentials.Contacts.OrgFilter.' + rid;
		};

		var sanitizeOrgValue = function(v) {
			v = (v === null || v === undefined) ? '' : (v + '').trim();
			// Never treat placeholder/label-like strings as actual search values
			if (v.toLowerCase() === 'organization name' || v.toLowerCase() === 'organization') return '';
			if (v.toLowerCase() === 'organisation name' || v.toLowerCase() === 'organisation') return '';
			return v;
		};

		var clearIfLabelValue = function(input) {
			if (!input || !input.length) return;
			var cur = (input.val() || '').toString().trim();
			var lc = cur.toLowerCase();
			if (
				lc === 'organization' ||
				lc === 'organization name' ||
				lc === 'organisation' ||
				lc === 'organisation name'
			) {
				input.val('');
			}
		};

		var enforceOrgFilterValueAfterRender = function(container) {
			container = container ? jQuery(container) : jQuery('div.details');
			var relatedContainer = container.find('.relatedContainer:visible').first();
			if (!relatedContainer.length) return;
			if (relatedContainer.find('input.relatedModuleName').val() !== 'Contacts') return;

			var headerRow = relatedContainer.find('.listViewHeaders');
			var searchRow = relatedContainer.find('.searchRow');
			if (!headerRow.length || !searchRow.length) return;

			// Find Organization column by data-fieldname=account_id, fallback by header text.
			var headerAnchor = headerRow.find('a.listViewContentHeaderValues[data-fieldname="account_id"]').first();
			var headerTh = headerAnchor.length ? headerAnchor.closest('th') : headerRow.find('th').filter(function() {
				var t = (jQuery(this).text() || '').toString().toLowerCase();
				return (t.indexOf('organisation') > -1 || t.indexOf('organization') > -1) && t.indexOf('name') > -1;
			}).first();
			if (!headerTh.length) return;

			var idx = headerTh.index();
			if (idx < 0) return;

			var cell = searchRow.find('th').eq(idx);
			if (!cell.length) return;

			var input = cell.find('input.listSearchContributor[name="account_id"]').first();
			if (!input.length) return;

			input.attr('placeholder', '');
			clearIfLabelValue(input);
		};

		var getPrefill = function(relatedContainer) {
			// 1) Prefer currentSearchParams (after search)
			try {
				var csp = relatedContainer.find('#currentSearchParams').val();
				if (csp) {
					var parsed = JSON.parse(csp);
					// Sometimes stored as array of objects, sometimes as object keyed by fieldName
					if (Array.isArray(parsed)) {
						for (var i = 0; i < parsed.length; i++) {
							if (parsed[i] && parsed[i].fieldName === 'account_id') {
								return { value: sanitizeOrgValue(parsed[i].searchValue || ''), comparator: parsed[i].comparator || '' };
							}
						}
					} else if (parsed && parsed['account_id']) {
						return {
							value: sanitizeOrgValue(parsed['account_id']['searchValue'] || ''),
							comparator: parsed['account_id']['comparator'] || ''
						};
					}
				}
			} catch (e) {}

			// 2) Fall back to last typed value in storage
			try {
				if (typeof app !== 'undefined' && app.storage && typeof app.storage.get === 'function') {
					var v = app.storage.get(STORAGE_KEY());
					v = sanitizeOrgValue(v);
					if (v) return { value: v, comparator: '' };
				}
			} catch (e2) {}
			return { value: '', comparator: '' };
		};

		var apply = function(container) {
			container = container ? jQuery(container) : jQuery('div.details');
			var relatedContainer = container.find('.relatedContainer:visible').first();
			if (!relatedContainer.length) return;

			var relatedModuleName = relatedContainer.find('input.relatedModuleName').val();
			if (relatedModuleName !== 'Contacts') return;

			var headerRow = relatedContainer.find('.listViewHeaders');
			var searchRow = relatedContainer.find('.searchRow');
			if (!headerRow.length || !searchRow.length) return;

			// Find Organization column by data-fieldname=account_id, fallback by header text.
			var headerAnchor = headerRow.find('a.listViewContentHeaderValues[data-fieldname="account_id"]').first();
			var headerTh = headerAnchor.length ? headerAnchor.closest('th') : headerRow.find('th').filter(function() {
				var t = (jQuery(this).text() || '').toString().toLowerCase();
				return (t.indexOf('organisation') > -1 || t.indexOf('organization') > -1) && t.indexOf('name') > -1;
			}).first();
			if (!headerTh.length) return;

			var idx = headerTh.index();
			if (idx < 0) return;

			var cell = searchRow.find('th').eq(idx);
			if (!cell.length) return;

			var prefill = getPrefill(relatedContainer);

			// If already has an input (some configs may render it), do nothing.
			var existing = cell.find('input[name="account_id"].listSearchContributor');
			if (existing.length) {
				// Keep empty unless a real filter value exists
				existing.val(prefill.value || '');
				existing.attr('placeholder', '');
				var exOp = existing.closest('th').find('.operatorValue');
				if (exOp.length) exOp.val(prefill.comparator || '');
				// Vtiger sometimes forces label text into value after reload; clear it.
				clearIfLabelValue(existing);
				return;
			}

			cell.empty().append(
				'<input type="text" name="account_id" class="listSearchContributor inputElement potentials-org-filter" placeholder="" autocomplete="off" />' +
				'<input type="hidden" class="operatorValue" value="" />'
			);
			var input = cell.find('input[name="account_id"].listSearchContributor');
			input.val(prefill.value || '');
			input.attr('placeholder', '');
			cell.find('.operatorValue').val(prefill.comparator || '');
			clearIfLabelValue(input);

			// Persist user typing so value doesn't revert to placeholder on next reload.
			input.off('input.potentialsOrgFilter change.potentialsOrgFilter')
				.on('input.potentialsOrgFilter change.potentialsOrgFilter', function() {
					try {
						if (typeof app !== 'undefined' && app.storage && typeof app.storage.set === 'function') {
							app.storage.set(STORAGE_KEY(), sanitizeOrgValue(jQuery(this).val()));
						}
					} catch (e3) {}
				});
		};

		// After any related list load.
		app.event.off('post.relatedListLoad.click.PotentialsContactsOrgFilterUi');
		app.event.on('post.relatedListLoad.click.PotentialsContactsOrgFilterUi', function(event, container) {
			setTimeout(function() { apply(container); }, 0);
			setTimeout(function() { enforceOrgFilterValueAfterRender(container); }, 0);
		});

		// Fallback: when tab is clicked / ajax completes, re-apply if Contacts is visible.
		jQuery(document).off('ajaxComplete.PotentialsContactsOrgFilterUi');
		jQuery(document).on('ajaxComplete.PotentialsContactsOrgFilterUi', function() {
			setTimeout(function() { apply(jQuery('div.details')); }, 0);
			setTimeout(function() { enforceOrgFilterValueAfterRender(jQuery('div.details')); }, 0);
		});

		// Direct URL / initial render.
		jQuery(function() {
			setTimeout(function() { apply(jQuery('div.details')); }, 0);
			setTimeout(function() { enforceOrgFilterValueAfterRender(jQuery('div.details')); }, 0);
		});
	},

	/**
	 * Quotes related list: inject missing reference-field filters.
	 *
	 * Vtiger skips generating search inputs for fields with datatype "reference".
	 * We inject text inputs for:
	 * - Contact Name (contact_id)
	 * - Organisation/Organization Name (account_id)
	 * - Opportunity Name (potential_id)
	 *
	 * Backend filtering is handled by core QueryGenerator with reference-field label search.
	 */
	registerPotentialsQuotesReferenceFiltersUi : function() {
		var normalize = function(v) {
			return (v === null || v === undefined) ? '' : (v + '').trim();
		};

		var isLabelLike = function(v) {
			var val = normalize(v).toLowerCase();
			if (!val) return false;
			var labels = [
				'contact',
				'contact name',
				'organization',
				'organisation',
				'organization name',
				'organisation name',
				'opportunity',
				'opportunity name'
			];
			return labels.indexOf(val) !== -1;
		};

		var rid = (typeof app !== 'undefined' && typeof app.getRecordId === 'function') ? app.getRecordId() : '';
		var storageKey = function(fieldName) {
			return 'Potentials.Quotes.Filter.' + rid + '.' + fieldName;
		};
		var readStored = function(fieldName) {
			try {
				if (typeof app !== 'undefined' && app.storage && typeof app.storage.get === 'function') {
					return normalize(app.storage.get(storageKey(fieldName)));
				}
			} catch (e) {}
			return '';
		};
		var writeStored = function(fieldName, value) {
			try {
				if (typeof app !== 'undefined' && app.storage && typeof app.storage.set === 'function') {
					var v = normalize(value);
					// Never store label-like values; only store real user search terms.
					if (isLabelLike(v)) v = '';
					app.storage.set(storageKey(fieldName), v);
				}
			} catch (e) {}
		};

		var getHeaderIndexByVariants = function(headerRow, variants) {
			var idx = -1;
			headerRow.find('th').each(function(i) {
				var t = normalize(jQuery(this).text()).toLowerCase();
				for (var j = 0; j < variants.length; j++) {
					if (t.indexOf(variants[j]) !== -1) {
						idx = i;
						return false;
					}
				}
			});
			return idx;
		};

		var getCellInput = function(searchRow, idx, fieldName) {
			if (idx < 0) return jQuery();
			var cell = searchRow.find('th').eq(idx);
			if (!cell.length) return jQuery();
			return cell.find('input.listSearchContributor[name="' + fieldName + '"]').first();
		};

		var ensureInputInCell = function(cell, fieldName, placeholder, initialValue) {
			if (!cell || !cell.length) return;
			var input = cell.find('input.listSearchContributor[name="' + fieldName + '"]').first();
			if (!input.length) {
				cell.empty().append(
					'<input type="text" class="inputElement listSearchContributor" name="' + fieldName + '" placeholder="" autocomplete="off" />' +
					'<input type="hidden" class="operatorValue" value="c" />'
				);
				input = cell.find('input.listSearchContributor[name="' + fieldName + '"]').first();
			} else {
				// Ensure hidden operator exists
				if (!cell.find('input.operatorValue').length) {
					cell.append('<input type="hidden" class="operatorValue" value="c" />');
				}
			}

			// Preserve user-typed value if it looks like a real value (avoid overwriting after reload).
			var curValue = normalize(input.val());
			var finalValue = normalize(initialValue);
			if (isLabelLike(finalValue)) finalValue = '';

			if (curValue && !isLabelLike(curValue)) {
				// User has a real value already; don't override.
				input.attr('placeholder', '');
				cell.find('input.operatorValue').val('c');
				return;
			}

			input.attr('placeholder', '');
			input.val(finalValue);
			cell.find('input.operatorValue').val('c');
		};

		// Store values just before core related-list Search builds search_params.
		// This avoids relying on #currentSearchParams which may not exist for related lists.
		var detailViewContainer = this.getDetailViewContainer ? this.getDetailViewContainer() : jQuery('.detailViewContainer');
		if (typeof Potentials_Detail_Js._quotesFilterStoreBound === 'undefined') {
			Potentials_Detail_Js._quotesFilterStoreBound = true;
			detailViewContainer.off('mousedown.PotentialsQuotesFilterStore click.PotentialsQuotesFilterStore', '[data-trigger="relatedListSearch"]');
			detailViewContainer.on('mousedown.PotentialsQuotesFilterStore click.PotentialsQuotesFilterStore', '[data-trigger="relatedListSearch"]', function() {
				var relatedContainer = jQuery(this).closest('.relatedContainer');
				if (!relatedContainer.length) return;
				if (normalize(relatedContainer.find('input.relatedModuleName').val()) !== 'Quotes') return;

				var headerRow = relatedContainer.find('.listViewHeaders');
				var searchRow = relatedContainer.find('.searchRow');
				if (!headerRow.length || !searchRow.length) return;

				var contactIdx = getHeaderIndexByVariants(headerRow, ['contact name', 'contact']);
				var orgIdx = getHeaderIndexByVariants(headerRow, ['organization name', 'organisation name', 'organization', 'organisation']);
				var oppIdx = getHeaderIndexByVariants(headerRow, ['opportunity name', 'opportunity']);

				var cInput = getCellInput(searchRow, contactIdx, 'contact_id');
				var aInput = getCellInput(searchRow, orgIdx, 'account_id');
				var pInput = getCellInput(searchRow, oppIdx, 'potential_id');

				// Save only real values (label-like is ignored in writeStored).
				if (cInput.length) writeStored('contact_id', cInput.val());
				if (aInput.length) writeStored('account_id', aInput.val());
				if (pInput.length) writeStored('potential_id', pInput.val());
			});
		}

		var apply = function(container) {
			container = container ? jQuery(container) : jQuery('div.details');
			var relatedContainer = container.find('.relatedContainer:visible').first();
			if (!relatedContainer.length) return;

			var relatedModuleName = normalize(relatedContainer.find('input.relatedModuleName').val());
			var headerRow = relatedContainer.find('.listViewHeaders');
			var searchRow = relatedContainer.find('.searchRow');
			if (!headerRow.length || !searchRow.length) return;

			// Detect Quotes table by URL+headers OR by relatedModuleName.
			var url = (typeof window !== 'undefined' && window.location) ? (window.location.search || '') : '';
			var urlLooksQuotes = (url.indexOf('relatedModule=Quotes') > -1 || url.indexOf('tab_label=Quotes') > -1);

			var headerText = normalize(headerRow.text()).toLowerCase();
			var domLooksQuotes = headerText.indexOf('quote stage') !== -1 && (headerText.indexOf('total') !== -1 || headerText.indexOf('quote no') !== -1 || headerText.indexOf('quote number') !== -1);

			if (!(relatedModuleName === 'Quotes' || urlLooksQuotes || domLooksQuotes)) return;

			var contactIdx = getHeaderIndexByVariants(headerRow, ['contact name', 'contact']);
			var orgIdx = getHeaderIndexByVariants(headerRow, ['organization name', 'organisation name', 'organization', 'organisation']);
			var oppIdx = getHeaderIndexByVariants(headerRow, ['opportunity name', 'opportunity']);

			// Restore last typed values after reload (from storage).
			var contactVal = readStored('contact_id');
			var accountVal = readStored('account_id');
			var potentialVal = readStored('potential_id');

			if (contactIdx > -1) {
				ensureInputInCell(searchRow.find('th').eq(contactIdx), 'contact_id', '', contactVal);
				var ci = getCellInput(searchRow, contactIdx, 'contact_id');
				if (ci.length) {
					ci.attr('placeholder', '');
					var cv = normalize(ci.val());
					if (isLabelLike(cv)) ci.val('');
					// If we have a stored real value and the current value is empty/label-like, restore it.
					if (contactVal && (!normalize(ci.val()) || isLabelLike(normalize(ci.val())))) ci.val(contactVal);
				}
			}
			if (orgIdx > -1) {
				ensureInputInCell(searchRow.find('th').eq(orgIdx), 'account_id', '', accountVal);
				var ai = getCellInput(searchRow, orgIdx, 'account_id');
				if (ai.length) {
					ai.attr('placeholder', '');
					var av = normalize(ai.val());
					if (isLabelLike(av)) ai.val('');
					if (accountVal && (!normalize(ai.val()) || isLabelLike(normalize(ai.val())))) ai.val(accountVal);
				}
			}
			if (oppIdx > -1) {
				ensureInputInCell(searchRow.find('th').eq(oppIdx), 'potential_id', '', potentialVal);
				var pi = getCellInput(searchRow, oppIdx, 'potential_id');
				if (pi.length) {
					pi.attr('placeholder', '');
					var pv = normalize(pi.val());
					if (isLabelLike(pv)) pi.val('');
					if (potentialVal && (!normalize(pi.val()) || isLabelLike(normalize(pi.val())))) pi.val(potentialVal);
				}
			}
		};

		app.event.off('post.relatedListLoad.click.PotentialsQuotesReferenceFiltersUi');
		app.event.on('post.relatedListLoad.click.PotentialsQuotesReferenceFiltersUi', function(event, container) {
			setTimeout(function() { apply(container); }, 0);
		});

		jQuery(document).off('ajaxComplete.PotentialsQuotesReferenceFiltersUi');
		jQuery(document).on('ajaxComplete.PotentialsQuotesReferenceFiltersUi', function() {
			setTimeout(function() { apply(jQuery('div.details')); }, 0);
		});

		jQuery(function() {
			setTimeout(function() { apply(jQuery('div.details')); }, 0);
		});
	},

	/**
	 * Restore Activities Start/Due date filters to single-date UI (Potentials → Calendar related list).
	 * Start Date: same-day range (d,d) + bw before search (Vtiger QueryGenerator).
	 * Due Date: single display date only; Potentials_RelationListView_Model applies DATE(vtiger_activity.due_date) server-side.
	 */
	registerPotentialsActivitiesSingleDateFiltersUi : function() {
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

		app.event.off('post.relatedListLoad.click.PotentialsActivitiesSingleDate');
		app.event.on('post.relatedListLoad.click.PotentialsActivitiesSingleDate', function(event, container) {
			setTimeout(function() { apply(container); }, 0);
			setTimeout(function() { apply(container); }, 150);
			setTimeout(function() { apply(container); }, 400);
			setTimeout(function() { apply(container); }, 800);
		});

		jQuery(document).off('ajaxComplete.PotentialsActivitiesSingleDate');
		jQuery(document).on('ajaxComplete.PotentialsActivitiesSingleDate', function() {
			setTimeout(function() { apply(jQuery('div.details')); }, 0);
		});

		/* mousedown runs before click so DOM is ready for getRelatedListSearchParams(); core handler still runs loadRelatedList. */
		var detailViewContainer = this.getDetailViewContainer ? this.getDetailViewContainer() : jQuery('.detailViewContainer');
		detailViewContainer.off('mousedown.potentialsActivitiesSearchPrep', '[data-trigger="relatedListSearch"]');
		detailViewContainer.on('mousedown.potentialsActivitiesSearchPrep', '[data-trigger="relatedListSearch"]', function() {
			var relatedContainer = jQuery(this).closest('.relatedContainer');
			if (!relatedContainer.length) return;
			if (relatedContainer.find('input.relatedModuleName').val() !== 'Calendar') return;
			if (typeof app === 'undefined' || app.getModuleName() !== 'Potentials') return;
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

	/**
	 * Style datetime filters on Documents and other related lists (modifiedtime, etc.).
	 */
	registerPotentialsRelatedDateFiltersUi : function() {
		var thisInstance = this;
		var apply = function(container) {
			if (!thisInstance.isSalesOpportunityDetailUi()) return;
			thisInstance.centerLuxuryRelatedFilters(container);
		};

		app.event.off('post.relatedListLoad.click.PotentialsRelatedDateFilters');
		app.event.on('post.relatedListLoad.click.PotentialsRelatedDateFilters', function(event, container) {
			setTimeout(function() { apply(container); }, 0);
			setTimeout(function() { apply(container); }, 300);
		});
		jQuery(function() { setTimeout(function() { apply(); }, 0); });
	},

	/**
	 * Summary widget: select existing ProductsServices records (popup) and relate to this Opportunity.
	 */
	registerPotentialsSummaryProductsServicesPopup : function() {
		var self = this;
		var eventName = 'post.RecordList.click.PotentialsSummaryProductsServices';
		app.event.off(eventName);
		app.event.on(eventName, function(event, data) {
			var responseData = JSON.parse(data);
			var idList = [];
			for (var id in responseData) {
				if (responseData.hasOwnProperty(id)) {
					idList.push(id);
				}
			}
			app.helper.hideModal();
			if (!idList.length) {
				return;
			}
			app.helper.showProgress();
			app.request.post({
				data: {
					mode: 'addRelation',
					module: app.getModuleName(),
					action: 'RelationAjax',
					related_module: 'ProductsServices',
					src_record: self.getRecordId(),
					related_record_list: JSON.stringify(idList)
				}
			}).then(function(err) {
				app.helper.hideProgress();
				if (err !== null) {
					app.event.trigger('post.save.failed', err);
					return;
				}
				var widgetContainer = jQuery('.widgetContainer_products');
				if (widgetContainer.length) {
					self.loadWidget(widgetContainer);
				}
			});
		});

		this.getContentHolder().on('click', '.potentialsSummaryProductsServicesAdd', function(e) {
			e.preventDefault();
			e.stopPropagation();
			var popupParams = {
				module: 'ProductsServices',
				src_module: app.getModuleName(),
				src_record: self.getRecordId(),
				multi_select: 1,
				view: 'Popup'
			};
			var popupInstance = Vtiger_Popup_Js.getInstance('ProductsServices');
			popupInstance.showPopup(popupParams, eventName);
		});
	}
})