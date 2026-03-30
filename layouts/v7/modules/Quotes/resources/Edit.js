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

	  // Document Template selector (runtime injection).
	  this.initDocumentTemplateSelector('Quotes');
    },

	initDocumentTemplateSelector : function(targetModuleName) {
		var form = this.getForm();
		if (!form || !form.length) return;
		if (form.find('[name="document_template_id"]').length) return; // already injected
		var container = form.find('.editViewContents');
		if (!container || !container.length) return;

		var recordId = (typeof app !== 'undefined' && app.getRecordId) ? app.getRecordId() : 0;
		recordId = parseInt(recordId, 10) || 0;

		var dbg = jQuery('<div/>', {
			'class': 'dt-selector-debug',
			'html': '<strong>DOCUMENT TEMPLATE UI LOADED</strong><br/>Module: ' + targetModuleName + '<br/>Record id: ' + recordId
		}).css({
			'margin': '10px 0 0 0',
			'padding': '10px',
			'background': '#fffae6',
			'border': '1px solid #f0c36d',
			'border-radius': '6px',
			'font-size': '13px'
		});
		container.prepend(dbg);

		var params = {
			module: 'DocumentTemplate',
			action: 'GetSelectorData',
			targetModule: targetModuleName,
			targetRecord: recordId
		};

		if (typeof app === 'undefined' || !app.request || !app.request.get) {
			dbg.append('<br/>Error: app.request.get not found.');
			return;
		}

		app.request.get({data: params}).then(
			function(error, data) {
				if (error) {
					dbg.append('<br/>Error loading template options.');
					return;
				}
				var options = (data && data.options) ? data.options : [];
				var selectedId = (data && data.selectedId) ? parseInt(data.selectedId, 10) : 0;
				dbg.append('<br/>Option count: ' + options.length);
				dbg.remove();

				var wrap = jQuery('<div/>').addClass('well').css({'margin-top': '12px'});
				var row = jQuery('<div/>').addClass('row');
				var left = jQuery('<div/>').addClass('col-sm-4').append(jQuery('<label/>').text('Document Template'));
				var right = jQuery('<div/>').addClass('col-sm-8');

				var select = jQuery('<select/>', {'class': 'inputElement', 'name': 'document_template_id'});
				if (!options.length) {
					select.append(jQuery('<option/>', {value: ''}).text('-- No matching template available --'));
				} else {
					select.append(jQuery('<option/>', {value: ''}).text('-- Select ' + targetModuleName + ' template --'));
					options.forEach(function(o) {
						var txt = o.templatename + ' (v' + o.version + ')';
						if (parseInt(o.isdefault, 10) === 1) txt += ' [Default]';
						var opt = jQuery('<option/>', {value: o.templateid}).text(txt);
						if (parseInt(o.templateid, 10) === selectedId) opt.prop('selected', true);
						select.append(opt);
					});
				}

				right.append(select);
				right.append(jQuery('<div/>', {'class': 'text-muted small'}).css({'margin-top': '6px'}).text('Templates are filtered by feature.'));
				row.append(left).append(right);
				wrap.append(row);
				container.prepend(wrap);
			},
			function(error) {
				dbg.append('<br/>Request failed.');
			}
		);
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
    
    /**
     * Unified "Add Products & Services" row + ProductsServices popup (same pattern as Invoice).
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
        registerBasicEvents: function(container){
            this._super(container);
            this.registerForTogglingBillingandShippingAddress();
            this.registerEventForCopyAddress();
            this.registerAddProductsServicesButton();
        },
});