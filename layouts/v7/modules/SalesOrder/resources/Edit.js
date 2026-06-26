/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Inventory_Edit_Js("SalesOrder_Edit_Js",{},{

	/**
	 * When Opportunity (potential_id) is selected, sync Organization and Contact
	 * from the opportunity record. Empty opportunity fields clear the targets.
	 */
	registerAutoOrgContactFromOpportunity : function() {
		var self = this;
		var form = this.getForm();
		if (!form || !form.length) {
			return;
		}

		var accountIdEl = form.find('[name="account_id"]');
		var accountDisplayEl = form.find('[name="account_id_display"]');
		var contactIdEl = form.find('[name="contact_id"]');
		var contactDisplayEl = form.find('[name="contact_id_display"]');

		var clearReferenceField = function(idEl, displayEl) {
			if (idEl && idEl.length) {
				idEl.val('');
			}
			if (displayEl && displayEl.length) {
				displayEl.val('');
				displayEl.trigger('change');
			}
		};

		var setReferenceField = function(idEl, displayEl, recordId, sourceModule, nameResolver) {
			recordId = parseInt(recordId, 10) || 0;
			if (!recordId) {
				clearReferenceField(idEl, displayEl);
				return;
			}
			self.getRecordDetails({record: recordId, source_module: sourceModule}).then(function(data) {
				var row = data && data.data ? data.data : null;
				if (!row) {
					clearReferenceField(idEl, displayEl);
					return;
				}
				var name = nameResolver(row);
				if (typeof app !== 'undefined' && app.htmlDecode && name) {
					name = app.htmlDecode(name);
				}
				if (idEl && idEl.length) {
					idEl.val(recordId);
				}
				if (displayEl && displayEl.length) {
					displayEl.val(name || '');
					displayEl.trigger('change');
					displayEl.trigger(Vtiger_Edit_Js.postReferenceSelectionEvent);
				}
			});
		};

		var applyFromPotentialId = function(potentialId) {
			potentialId = parseInt(potentialId, 10) || 0;
			if (!potentialId) {
				return;
			}

			self.getRecordDetails({record: potentialId, source_module: 'Potentials'}).then(function(data) {
				var row = data && data.data ? data.data : null;
				if (!row) {
					return;
				}

				var accountId = parseInt(row.related_to, 10) || 0;
				var contactId = parseInt(row.contact_id, 10) || 0;

				if (accountId) {
					setReferenceField(accountIdEl, accountDisplayEl, accountId, 'Accounts', function(r) {
						return r.accountname || '';
					});
				} else {
					clearReferenceField(accountIdEl, accountDisplayEl);
				}

				if (contactId) {
					setReferenceField(contactIdEl, contactDisplayEl, contactId, 'Contacts', function(r) {
						var name = ((r.firstname || '') + ' ' + (r.lastname || '')).trim();
						return name || r.label || '';
					});
				} else {
					clearReferenceField(contactIdEl, contactDisplayEl);
				}
			});
		};

		form.on(Vtiger_Edit_Js.referenceSelectionEvent, '[name="potential_id"]', function() {
			applyFromPotentialId(form.find('[name="potential_id"]').val());
		});
		form.on('change.mkSoOppAutofill', '[name="potential_id"]', function() {
			applyFromPotentialId(jQuery(this).val());
		});

		var initialPotentialId = parseInt(form.find('[name="potential_id"]').val(), 10) || 0;
		if (initialPotentialId > 0) {
			var accountEmpty = !(parseInt(accountIdEl.val(), 10) || 0);
			var contactEmpty = !(parseInt(contactIdEl.val(), 10) || 0);
			if (accountEmpty || contactEmpty) {
				applyFromPotentialId(initialPotentialId);
			}
		}
	},

    
    /**
	 * Function to get popup params
	 */
	getPopUpParams : function(container) {
		var params = this._super(container);
        var sourceFieldElement = jQuery('input[class="sourceField"]',container);
		if(!sourceFieldElement.length) {
			sourceFieldElement = jQuery('input.sourceField',container);
		}

		if(sourceFieldElement.attr('name') == 'contact_id' || sourceFieldElement.attr('name') == 'potential_id') {
			var form = this.getForm();
			var parentIdElement  = form.find('[name="account_id"]');
			if(parentIdElement.length > 0 && parentIdElement.val().length > 0 && parentIdElement.val() != 0) {
				var closestContainer = parentIdElement.closest('td');
				params['related_parent_id'] = parentIdElement.val();
				params['related_parent_module'] = closestContainer.find('[name="popupReferenceModule"]').val();
			} else if(sourceFieldElement.attr('name') == 'potential_id') {
				parentIdElement  = form.find('[name="contact_id"]');
				if(parentIdElement.length > 0 && parentIdElement.val().length > 0) {
					closestContainer = parentIdElement.closest('td');
					params['related_parent_id'] = parentIdElement.val();
					params['related_parent_module'] = closestContainer.find('[name="popupReferenceModule"]').val();
				}
			}
        }
        return params;
    },

	/**
	 * Override submit behavior for Tools > Orders context.
	 * In TOOLS app we allow saving without inventory line items.
	 */
	registerSubmitEvent : function () {
		var self = this;
		var editViewForm = this.getForm();
		editViewForm.submit(function(e){
			var appNameValue = (editViewForm.find('[name="appName"]').val() || '').toUpperCase();
			var isToolsContext = appNameValue.indexOf('TOOLS') !== -1;

			if (!isToolsContext) {
				var deletedItemInfo = jQuery('.deletedItem', editViewForm);
				if (deletedItemInfo.length > 0) {
					e.preventDefault();
					var msg = app.vtranslate('JS_PLEASE_REMOVE_LINE_ITEM_THAT_IS_DELETED');
					app.helper.showErrorNotification({"message" : msg});
					editViewForm.removeData('submit');
					return false;
				} else if (jQuery('.lineItemRow').length <= 0) {
					e.preventDefault();
					msg = app.vtranslate('JS_NO_LINE_ITEM');
					app.helper.showErrorNotification({"message" : msg});
					editViewForm.removeData('submit');
					return false;
				}

				self.updateLineItemElementByOrder();
				self.saveProductCount();
				self.saveSubTotalValue();
				self.saveTotalValue();
				self.savePreTaxTotalValue();
			}

			return true;
		});
	},
    
    /**
	 * Function to register event for enabling recurrence
	 * When recurrence is enabled some of the fields need
	 * to be check for mandatory validation
	 */
	registerEventForEnablingRecurrence : function(){
		var thisInstance = this;
		var form = this.getForm();
		var enableRecurrenceField = form.find('[name="enable_recurring"]');
		var fieldNamesForValidation = new Array('recurring_frequency','start_period','end_period','payment_duration','invoicestatus');
        var selectors = new Array();
        for(var index in fieldNamesForValidation) {
            selectors.push('[name="'+fieldNamesForValidation[index]+'"]');
        }
        var selectorString = selectors.join(',');
        var validationToggleFields = form.find(selectorString);

		var markRecurringFieldCells = function (fieldName, cellClass) {
			var $field = form.find('[name="' + fieldName + '"]');
			if (!$field.length) {
				return;
			}
			var $valueCell = $field.closest('td.fieldValue');
			if ($valueCell.length) {
				$valueCell.addClass(cellClass);
				var $labelCell = $valueCell.prev('td.fieldLabel');
				if ($labelCell.length) {
					$labelCell.addClass(cellClass);
				}
			}
		};

		jQuery(fieldNamesForValidation).each(function (_idx, fieldName) {
			markRecurringFieldCells(fieldName, 'mk-so-recurring-dependent');
		});
		markRecurringFieldCells('enable_recurring', 'mk-so-recurring-toggle');

		var recurringBlock = enableRecurrenceField.closest('.fieldBlockContainer');
		if (recurringBlock.length) {
			recurringBlock.addClass('mk-so-recurring-block');
		}

		var syncRecurringBlockUi = function (enabled) {
			if (recurringBlock.length) {
				recurringBlock.toggleClass('mk-so-recurring-on', !!enabled);
				recurringBlock.removeClass('mk-so-recurring-off');
				var $hint = recurringBlock.find('.mk-so-recurring-hint');
				if (!$hint.length) {
					$hint = jQuery(
						'<p class="mk-so-recurring-hint" role="note">' +
						'Bật <strong>Enable Recurring</strong> để cấu hình tần suất, thời hạn và trạng thái hóa đơn định kỳ.' +
						'</p>'
					);
					recurringBlock.find('table').first().before($hint);
				}
			}
		};

		var applyRecurringDefaults = function () {
			var today = new Date();
			var pad = function (n) {
				return n < 10 ? '0' + n : String(n);
			};
			var todayStr = pad(today.getDate()) + '-' + pad(today.getMonth() + 1) + '-' + today.getFullYear();
			var end = new Date(today.getTime());
			end.setFullYear(end.getFullYear() + 1);
			var endStr = pad(end.getDate()) + '-' + pad(end.getMonth() + 1) + '-' + end.getFullYear();

			jQuery.each(fieldNamesForValidation, function (_i, fieldName) {
				var $field = form.find('[name="' + fieldName + '"]');
				if (!$field.length || jQuery.trim($field.val() || '') !== '') {
					return;
				}
				if (fieldName === 'start_period') {
					$field.val(todayStr).trigger('change');
				} else if (fieldName === 'end_period') {
					$field.val(endStr).trigger('change');
				} else if ($field.is('select')) {
					var $opt = $field.find('option[value!=""]').first();
					if ($opt.length) {
						$field.val($opt.val()).trigger('change');
					}
				}
			});
		};

		enableRecurrenceField.off('change.mkSoRecurring').on('change.mkSoRecurring', function(e){
			var element = jQuery(e.currentTarget);
			var addValidation = element.is(':checked');
			syncRecurringBlockUi(addValidation);
			if(addValidation){
				thisInstance.AddOrRemoveRequiredValidation(validationToggleFields, true);
				applyRecurringDefaults();
			}else{
				thisInstance.AddOrRemoveRequiredValidation(validationToggleFields, false);
			}
		});

		var isNewRecord = !jQuery.trim(form.find('[name="record"]').val() || '');
		if (isNewRecord && enableRecurrenceField.is(':checked')) {
			enableRecurrenceField.prop('checked', false);
		}

		if(!enableRecurrenceField.is(":checked")){
			thisInstance.AddOrRemoveRequiredValidation(validationToggleFields, false);
			syncRecurringBlockUi(false);
		}else if(enableRecurrenceField.is(":checked")){
			thisInstance.AddOrRemoveRequiredValidation(validationToggleFields, true);
			syncRecurringBlockUi(true);
		}

		form.off('submit.mkSoRecurring').on('submit.mkSoRecurring', function () {
			if (!enableRecurrenceField.is(':checked')) {
				thisInstance.AddOrRemoveRequiredValidation(validationToggleFields, false);
			}
		});
	},
	
	AddOrRemoveRequiredValidation : function(dependentFieldsForValidation, addValidation) {
		jQuery(dependentFieldsForValidation).each(function(key,value){
			var relatedField = jQuery(value);
			if(addValidation) {
				relatedField.removeClass('ignore-validation').attr('data-rule-required', 'true');
				relatedField.prop('disabled', false);
				if(relatedField.is("select") && relatedField.hasClass('select2')) {
					try {
						relatedField.select2('enable', true);
					} catch (e1) {
						/* select2 may not be initialized yet */
					}
				}
			} else if(!addValidation) {
				relatedField.addClass('ignore-validation').removeAttr('data-rule-required').removeData('rule-required');
				if(relatedField.is("select")) {
					relatedField.val('').prop('disabled', true).trigger("change");
					var select2Element = app.helper.getSelect2FromSelect(relatedField);
					if (select2Element && select2Element.length) {
						select2Element.trigger('Vtiger.Validation.Hide.Messsage');
						select2Element.find('a').removeClass('input-error');
					}
				}else {
					relatedField.val('').prop('disabled', true).trigger('Vtiger.Validation.Hide.Messsage').removeClass('input-error');
				}
			}
		});
	},
    
    /**
	 * Function to search module names
	 */
	searchModuleNames : function(params) {
        var aDeferred = jQuery.Deferred();
		if(typeof params.module == 'undefined') {
			params.module = app.getModuleName();
		}
		if(typeof params.action == 'undefined') {
			params.action = 'BasicAjax';
		}
		
		if(typeof params.base_record == 'undefined') {
			var record = jQuery('[name="record"]');
			var recordId = app.getRecordId();
			if(record.length) {
				params.base_record = record.val();
			} else if(recordId) {
				params.base_record = recordId;
			} else if(app.view() == 'List') {
				var editRecordId = jQuery('#listview-table').find('tr.listViewEntries.edited').data('id');
				if(editRecordId) {
					params.base_record = editRecordId;
				}
			}
		}
        
        // Added for overlay edit as the module is different
        if(params.search_module == 'Products' || params.search_module == 'Services') {
            params.module = 'SalesOrder';
        }

		app.request.get({'data':params}).then(
			function(error, data){
                if(error == null) {
                    aDeferred.resolve(data);
                }
			},
			function(error){
				aDeferred.reject();
			}
		)
		return aDeferred.promise();
    },
    
    /**
	 * Function which will register event for Reference Fields Selection
	 */
	registerReferenceSelectionEvent : function(container) {
		this._super(container);
		var self = this;
		
		jQuery('input[name="account_id"]', container).on(Vtiger_Edit_Js.referenceSelectionEvent, function(e, data){
			self.referenceSelectionEventHandler(data, container);
		});
	},
        registerOppCommerceRefreshFlag: function(container) {
            var form = this.getForm();
            if (!form || !form.length) {
                return;
            }
            form.off('submit.mkOppCommerceFlag').on('submit.mkOppCommerceFlag', function() {
                var src = (form.find('input[name="sourceModule"]').val() || '').trim();
                var srcId = (form.find('input[name="sourceRecord"]').val() || '').trim();
                var potId = (form.find('input[name="potential_id"], input[name="potentialid"]').val() || '').trim();
                var refreshId = '';
                if (src === 'Potentials' && srcId) {
                    refreshId = srcId;
                } else if (potId) {
                    refreshId = potId;
                }
                if (refreshId) {
                    try {
                        sessionStorage.setItem('mkOppCommerceRefresh', refreshId);
                    } catch (e) {
                        /* ignore */
                    }
                }
            });
        },

        registerBasicEvents: function(container){
            this._super(container);
            this.registerAutoOrgContactFromOpportunity();
            this.registerEventForEnablingRecurrence();
            this.registerForTogglingBillingandShippingAddress();
            this.registerEventForCopyAddress();
            this.registerAddProductsServicesButton();
            this.registerOppCommerceRefreshFlag(container);
        },

        /**
         * Unified entry button for adding a new line-item row that contains
         * BOTH Products & Services selectors (SalesOrder only).
         */
        registerAddProductsServicesButton : function() {
            var self = this;
            jQuery('#addProductsServices').on('click', function(e, data){
                var currentTarget = jQuery(e.currentTarget);
                var params = {'currentTarget' : currentTarget};
                var newLineItem = self.getNewLineItem(params);
                newLineItem = newLineItem.appendTo(self.lineItemsHolder);
                newLineItem.find('input.productName').addClass('autoComplete');
                newLineItem.find('.ignore-ui-registration').removeClass('ignore-ui-registration');
                vtUtils.applyFieldElementsView(newLineItem);
                app.event.trigger('post.lineItem.New', newLineItem);
                self.checkLineItemRow();
                self.registerLineItemAutoComplete(newLineItem);

                // When this row is created due to multi-select popup selection,
                // map selected ProductsServices record into the line item row.
                if(typeof data !== "undefined") {
                    var recordData;
                    for(var id in data) {
                        recordData = data[id];
                        break;
                    }
                    var itemType = recordData ? recordData.item_type : null;
					var underlyingType = 'Products';
					if(itemType) {
						var itemTypeLower = itemType.toLowerCase();
						if(itemTypeLower === 'product' || itemTypeLower === 'products') {
							underlyingType = 'Products';
						} else if(itemTypeLower === 'service' || itemTypeLower === 'services') {
							underlyingType = 'Services';
						}
					}
                    newLineItem.find('.lineItemType').val(underlyingType);
                    self.mapResultsToFields(newLineItem, data);
                }
            });
        },
    
});