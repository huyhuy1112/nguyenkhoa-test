/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Inventory_Edit_Js("Quotes_Edit_Js",{},{
    
    accountsReferenceField : false,
    contactsReferenceField : false,
    
    initializeVariables : function() {
      this._super();
      var form = this.getForm();
      this.accountsReferenceField = form.find('[name="account_id"]');
      this.contactsReferenceField = form.find('[name="contact_id"]');

      // Auto Subject from Opportunity (potential_id)
      this.registerAutoSubjectFromOpportunity();

      // Auto-fill Organization + Contact from Opportunity (potential_id)
      this.registerAutoOrgContactFromOpportunity();
    },

    /**
     * Unified "Add Products & Services" row behavior (match Invoice).
     * Creates a new line-item row compatible with ProductsServices popup selection.
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
            if (window.MkInventoryOdooEdit && window.MkInventoryOdooEdit.scheduleLineItemsRestyle) {
                window.MkInventoryOdooEdit.scheduleLineItemsRestyle(self.getForm(), [0, 120, 400]);
            }
            self.checkLineItemRow();
            self.registerLineItemAutoComplete(newLineItem);

            // If invoked from multi-select popup flow, map the selected record into the row.
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

    /**
     * Subject is auto-filled from Opportunity name (potential_id) and is not required.
     */
    registerAutoSubjectFromOpportunity : function() {
        var form = this.getForm();
        if (!form || !form.length) return;

        var normalizeOppName = function(name) {
            var s = (name || '').toString().trim();
            // remove leading YYMMDD- (e.g. 260313-)
            s = s.replace(/^\d{6}-/, '');
            if (!s.length) return '';
            if (!/^TDB Quo-/i.test(s)) {
                s = 'TDB Quo-' + s;
            }
            return s;
        };

        var subjectEl = form.find('[name="subject"]');
        var userEditedSubject = false;
        if (subjectEl && subjectEl.length) {
            // Remove required validation (UI only). Backend will still set if empty.
            subjectEl.removeAttr('data-rule-required');
            subjectEl.removeClass('required');
            subjectEl.prop('readonly', false);
            if ((subjectEl.val() || '').toString().trim().length) {
                userEditedSubject = true;
            }
            subjectEl.on('input', function() {
                userEditedSubject = true;
            });
        }

        var potentialNameEl = form.find('[name="potential_id_display"]');
        var apply = function() {
            if (!subjectEl || !subjectEl.length || userEditedSubject) return;
            if (potentialNameEl && potentialNameEl.length) {
                var oppName = potentialNameEl.val();
                if (typeof app !== 'undefined' && app.htmlDecode && oppName) {
                    oppName = app.htmlDecode(oppName);
                }
                if (oppName && oppName.trim().length) {
                    subjectEl.val(normalizeOppName(oppName));
                }
            }
        };

        // Apply on load (e.g. preselected opportunity)
        apply();

        // Apply whenever opportunity changes (via popup selection or manual clear/reselect)
        form.on('change', '[name="potential_id"], [name="potential_id_display"]', function() {
            apply();
        });

        // Also hook vtiger reference selection event
        form.on(Vtiger_Edit_Js.referenceSelectionEvent, '[name="potential_id"]', function() {
            apply();
        });
    },

    /**
     * When selecting Opportunity (potential_id), auto-fill Organization (account_id)
     * and Contact (contact_id) from Opportunity fields (related_to/contact_id) if present.
     *
     * If Opportunity doesn't have these values, we do not overwrite existing selections.
     */
    registerAutoOrgContactFromOpportunity : function() {
        var self = this;
        var form = this.getForm();
        if (!form || !form.length) return;

        var accountIdEl = form.find('[name="account_id"]');
        var accountDisplayEl = form.find('[name="account_id_display"]');
        var contactIdEl = form.find('[name="contact_id"]');
        var contactDisplayEl = form.find('[name="contact_id_display"]');

        var setAccount = function(accountId) {
            accountId = parseInt(accountId, 10) || 0;
            if (!accountId) return;
            if (accountIdEl && accountIdEl.length && parseInt(accountIdEl.val(), 10)) return; // don't overwrite

            self.getRecordDetails({record: accountId, source_module: 'Accounts'}).then(function(data) {
                var row = data && data.data ? data.data : null;
                if (!row) return;
                var name = row.accountname || '';
                name = (typeof app !== 'undefined' && app.htmlDecode) ? app.htmlDecode(name) : name;
                if (accountIdEl && accountIdEl.length) accountIdEl.val(accountId);
                if (accountDisplayEl && accountDisplayEl.length) accountDisplayEl.val(name);
                if (accountDisplayEl && accountDisplayEl.length) {
                    accountDisplayEl.trigger('change');
                    accountDisplayEl.trigger(Vtiger_Edit_Js.postReferenceSelectionEvent);
                }
            });
        };

        var setContact = function(contactId) {
            contactId = parseInt(contactId, 10) || 0;
            if (!contactId) return;
            if (contactIdEl && contactIdEl.length && parseInt(contactIdEl.val(), 10)) return; // don't overwrite

            self.getRecordDetails({record: contactId, source_module: 'Contacts'}).then(function(data) {
                var row = data && data.data ? data.data : null;
                if (!row) return;
                var name = ((row.firstname || '') + ' ' + (row.lastname || '')).trim();
                if (!name) name = row.label || '';
                if (typeof app !== 'undefined' && app.htmlDecode) {
                    name = app.htmlDecode(name);
                }
                if (contactIdEl && contactIdEl.length) contactIdEl.val(contactId);
                if (contactDisplayEl && contactDisplayEl.length) contactDisplayEl.val(name);
                if (contactDisplayEl && contactDisplayEl.length) {
                    contactDisplayEl.trigger('change');
                    contactDisplayEl.trigger(Vtiger_Edit_Js.postReferenceSelectionEvent);
                }
            });
        };

        var applyFromPotentialId = function(potentialId) {
            potentialId = parseInt(potentialId, 10) || 0;
            if (!potentialId) return;

            self.getRecordDetails({record: potentialId, source_module: 'Potentials'}).then(function(data) {
                var row = data && data.data ? data.data : null;
                if (!row) return;

                // Potentials: related_to => Account, contact_id => Contact
                if (row.related_to) {
                    setAccount(row.related_to);
                }
                if (row.contact_id) {
                    setContact(row.contact_id);
                }
            });
        };

        // On reference selection event (popup selection)
        form.on(Vtiger_Edit_Js.referenceSelectionEvent, '[name="potential_id"]', function() {
            applyFromPotentialId(form.find('[name="potential_id"]').val());
        });

        // Also on change (in case of programmatic updates)
        form.on('change', '[name="potential_id"]', function() {
            applyFromPotentialId(jQuery(this).val());
        });
    },
    
    /**
	 * Function to get popup params
	 */
	getPopUpParams : function(container) {
		var params = this._super(container);
        var sourceFieldElement = jQuery('input[class="sourceField"]',container);
		var referenceModule = jQuery('input[name=popupReferenceModule]', container).val();
		if(!sourceFieldElement.length) {
			sourceFieldElement = jQuery('input.sourceField',container);
		}
		
		if((sourceFieldElement.attr('name') == 'contact_id' || sourceFieldElement.attr('name') == 'potential_id') && referenceModule != 'Leads') {
			var form = this.getForm();
			var parentIdElement  = form.find('[name="account_id"]');
			if(parentIdElement.length > 0 && parentIdElement.val().length > 0 && parentIdElement.val() != 0) {
				var closestContainer = parentIdElement.closest('td');
				params['related_parent_id'] = parentIdElement.val();
				params['related_parent_module'] = closestContainer.find('[name="popupReferenceModule"]').val();
			} else if(sourceFieldElement.attr('name') == 'potential_id') {
				parentIdElement  = form.find('[name="contact_id"]');
				var relatedParentModule = parentIdElement.closest('td').find('input[name="popupReferenceModule"]').val()
				if(parentIdElement.length > 0 && parentIdElement.val().length > 0 && relatedParentModule != 'Leads') {
					closestContainer = parentIdElement.closest('td');
					params['related_parent_id'] = parentIdElement.val();
					params['related_parent_module'] = closestContainer.find('[name="popupReferenceModule"]').val();
				}
			}
        }
        return params;
    },
    
    /**
	 * Function which will register event for Reference Fields Selection
	 */
	registerReferenceSelectionEvent : function(container) {
		this._super(container);
		var self = this;
		
		this.accountsReferenceField.on(Vtiger_Edit_Js.referenceSelectionEvent, function(e, data){
			self.referenceSelectionEventHandler(data, container);
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

		if (params.search_module == 'Contacts' || params.search_module == 'Potentials') {
			var form = this.getForm();
			if(this.accountsReferenceField.length > 0 && this.accountsReferenceField.val().length > 0) {
				var closestContainer = this.accountsReferenceField.closest('td');
				params.parent_id = this.accountsReferenceField.val();
				params.parent_module = closestContainer.find('[name="popupReferenceModule"]').val();
			} else if(params.search_module == 'Potentials') {
				
				if(this.contactsReferenceField.length > 0 && this.contactsReferenceField.val().length > 0) {
					closestContainer = this.contactsReferenceField.closest('td');
					params.parent_id = this.contactsReferenceField.val();
					params.parent_module = closestContainer.find('[name="popupReferenceModule"]').val();
				}
			}
		}
        
        // Added for overlay edit as the module is different
        if(params.search_module == 'Products' || params.search_module == 'Services' || params.search_module == 'ProductsServices') {
            params.module = 'Quotes';
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
        registerBasicEvents: function(container){
            this._super(container);
            this.registerForTogglingBillingandShippingAddress();
            this.registerEventForCopyAddress();
            this.registerAddProductsServicesButton();
        },
});