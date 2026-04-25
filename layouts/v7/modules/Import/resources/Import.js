/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
if (typeof (Vtiger_Import_Js) == 'undefined') {

    Vtiger_Import_Js = {
		clearImportSuccessFlags: function () {
			try { window.sessionStorage && sessionStorage.removeItem('vtiger.CampaignsImport.success'); } catch (e1) {}
			try { window.sessionStorage && sessionStorage.removeItem('vtiger.PlansImport.success'); } catch (e2) {}
			try { window.sessionStorage && sessionStorage.removeItem('vtiger.ContactsImport.success'); } catch (e3) {}
		},
		cleanupImportOverlay: function () {
			try {
				if (app && app.helper && app.helper.hidePageContentOverlay) {
					app.helper.hidePageContentOverlay();
				}
			} catch (e1) {}
			try {
				if (app && app.helper && app.helper.hideModal) {
					app.helper.hideModal();
				}
			} catch (e2) {}
			try {
				jQuery('.modal-backdrop').remove();
				jQuery('body').removeClass('modal-open');
			} catch (e3) {}
		},

		enforceCampaignsMapping: function () {
			var currentModule = '';
			try { currentModule = app.getModuleName(); } catch (e) {}
			var isCampaigns = (currentModule === 'Campaigns' || (window.location && window.location.href && window.location.href.indexOf('module=Campaigns') !== -1));
			if (!isCampaigns) return;

			// Step 3 mapping UI only
			if (jQuery("form[name='importAdvanced']").length === 0) return;

			// Re-entry guard
			try {
				if (window.__campaignImportAutoMappingRunning === true) return;
				window.__campaignImportAutoMappingRunning = true;
			} catch (eG) {}

			var map = {
				'campaign name': 'campaignname',
				'campaign status': 'campaignstatus',
				'campaign type': 'campaigntype',
				'start date': 'start_date',
				'expected close date': 'closingdate',
				'expected revenue': 'expectedrevenue',
				'assigned to': 'assigned_user_id',
				'description': 'description'
			};

			var normalize = function (s) {
				return (s || '')
					.replace(/^\uFEFF/, '')
					.replace(/"/g, '')
					.replace(/\s+/g, ' ')
					.trim()
					.toLowerCase();
			};

			var rows = jQuery('.importMappingTable tr, table tr.fieldIdentifier, tr.fieldIdentifier');
			try { console.log('[Campaigns Import] auto-map running. rows=', rows.length); } catch (eL) {}

			rows.each(function () {
				var row = jQuery(this);
				var select = row.find('select').first();
				if (!select.length) return;

				var headerText = '';
				var headerSpan = row.find('span[name="header_name"]').first();
				if (headerSpan.length) {
					headerText = headerSpan.text();
				}
				if (!headerText) {
					headerText = row.find('td').first().text();
				}

				var header = normalize(headerText);
				var targetField = map[header];

				if (!targetField) {
					return;
				}

				var before = select.val();
				if (before !== targetField) {
					select.val(targetField);
					select.trigger('change');
					select.trigger('chosen:updated');
					select.trigger('liszt:updated');
				}
			});

			// Minimal proof: log the final mapping for the critical rows only
			try {
				var required = {'campaign name':1,'campaign status':1,'start date':1,'expected close date':1};
				var debug = [];
				rows.each(function () {
					var row = jQuery(this);
					var select = row.find('select').first();
					if (!select.length) return;
					var headerText = row.find('span[name="header_name"]').first().text() || row.find('td').first().text();
					var header = normalize(headerText);
					if (!required[header]) return;
					debug.push({header: headerText, value: select.val(), label: select.find('option:selected').text()});
				});
				console.log('[Campaigns Import] auto-map proof:', debug);
			} catch (eP) {}

			try { window.__campaignImportAutoMappingRunning = false; } catch (eF) {}
		},

		guardCampaignsMapping: function () {
			var currentModule = '';
			try { currentModule = app.getModuleName(); } catch (e) {}
			var isCampaigns = (currentModule === 'Campaigns' || (window.location && window.location.href && window.location.href.indexOf('module=Campaigns') !== -1));
			if (!isCampaigns) return true;

			try { Vtiger_Import_Js.enforceCampaignsMapping(); } catch (e0) {}

			var required = {
				'campaign name': 'campaignname',
				'campaign status': 'campaignstatus',
				'start date': 'start_date',
				'expected close date': 'closingdate'
			};

			var normalize = function (s) {
				return (s || '')
					.replace(/^\uFEFF/, '')
					.replace(/"/g, '')
					.replace(/\s+/g, ' ')
					.trim()
					.toLowerCase();
			};

			var ok = true;
			var bad = [];
			jQuery('.importMappingTable tr, table tr.fieldIdentifier, tr.fieldIdentifier').each(function () {
				var row = jQuery(this);
				var select = row.find('select').first();
				if (!select.length) return;

				var headerText = row.find('span[name="header_name"]').first().text() || row.find('td').first().text();
				var header = normalize(headerText);
				if (required[header] && select.val() !== required[header]) {
					ok = false;
					bad.push(headerText + ' should map to ' + required[header] + ' but got ' + select.val());
				}
			});

			if (!ok) {
				try { console.error('[Campaigns Import] bad mapping', bad); } catch (e1) {}
				app.helper.showErrorNotification({
					message: 'Please review Campaigns Field Mapping. Campaign Name, Campaign Status, Start Date, and Expected Close Date must map correctly.'
				});
				return false;
			}
			return true;
		},

		scheduleCampaignsAutoMap: function () {
			try {
				var currentModule = '';
				try { currentModule = app.getModuleName(); } catch (e) {}
				var isCampaigns = (currentModule === 'Campaigns' || (window.location && window.location.href && window.location.href.indexOf('module=Campaigns') !== -1));
				if (!isCampaigns) return;
				if (jQuery("form[name='importAdvanced']").length === 0) return;

				setTimeout(function () { try { Vtiger_Import_Js.enforceCampaignsMapping(); } catch (e0) {} }, 300);
				setTimeout(function () { try { Vtiger_Import_Js.enforceCampaignsMapping(); } catch (e1) {} }, 800);
				setTimeout(function () { try { Vtiger_Import_Js.enforceCampaignsMapping(); } catch (e2) {} }, 1500);
			} catch (e3) {}
		},
        triggerImportAction: function(url) {
            var params = Vtiger_Import_Js.getDefaultParams();
            //Only for contacts and Calendar show landing page.
            if(params.module != 'Contacts' && params.module != 'Calendar') {
                Vtiger_Import_Js.showImportActionStepOne();
                return false;
            }
            params['mode'] = 'landing';
            app.helper.showProgress();
            app.request.get({data: params}).then(function(err, data) {
                app.helper.loadPageContentOverlay(data).then(function() {
                    app.helper.hideProgress();
                    Vtiger_Import_Js.registerEvents();
                });
            });
            return false;
        },
        bactToStep1: function() {
            jQuery('#step2').removeClass('active');
            jQuery('#step1').addClass('active');
            jQuery('#uploadFileContainer').addClass('show');
            jQuery('#importStep2Conatiner').removeClass('show');
            jQuery('#importStep2Conatiner').addClass('hide');

            jQuery('#importStepOneButtonsDiv').removeClass('hide');
            jQuery('#importStepOneButtonsDiv').addClass('show');

            jQuery('#importStepTwoButtonsDiv').removeClass('show');
            jQuery('#importStepTwoButtonsDiv').addClass('hide');

            return false;
        },
        importActionStep2: function() {
			if(Vtiger_Import_Js.validateFilePath()){
				jQuery('#uploadFileContainer').removeClass('show');
				jQuery('#uploadFileContainer').addClass('hide');

				jQuery('#step1').removeClass('active');
				jQuery('#step2').addClass('active');

				jQuery('#importStep2Conatiner').addClass('show');

				jQuery('#importStepTwoButtonsDiv').removeClass('hide');
				jQuery('#importStepTwoButtonsDiv').addClass('show');

				jQuery('#importStepOneButtonsDiv').removeClass('show');
				jQuery('#importStepOneButtonsDiv').addClass('hide');
			}
			return false;
        },
        uploadAndParse: function(auto_merge) {
            if (Vtiger_Import_Js.validateFilePath() && Vtiger_Import_Js.validateMergeCriteria(auto_merge)) {
                jQuery("#auto_merge").val(auto_merge);
                var form = jQuery("form[name='importBasic']");
                var data = new FormData(form[0]);
                var postParams = {
                    data: data,
                    contentType: false,
                    processData: false
                };
                app.helper.showProgress();
                app.request.post(postParams).then(function(err, response) {
                    app.helper.loadPageContentOverlay(response);
					Vtiger_Import_Js.loadDefaultValueWidgetForMappedFields();
					// Campaigns: enforce deterministic mapping on Step 3 after overlay render.
					Vtiger_Import_Js.scheduleCampaignsAutoMap();
                    app.helper.hideProgress();
                });
            }
            return false;
        },
        backToLandingPage: function() {
            Vtiger_Import_Js.triggerImportAction();
            return false;
        },
        sanitizeAndSubmit: function() {
            // Campaigns: enforce deterministic mapping before submit.
            try { Vtiger_Import_Js.enforceCampaignsMapping(); } catch (e) {}
            if (Vtiger_Import_Js.guardCampaignsMapping() && Vtiger_Import_Js.sanitizeFieldMapping() && Vtiger_Import_Js.validateCustomMap()) {
                var formData = jQuery("form[name='importAdvanced']").serialize();
                app.helper.showProgress();
                app.request.post({data: formData}).then(function(err, response) {
                    app.helper.loadPageContentOverlay(response);
                    app.helper.hideProgress();
                    if(!err){
                        if (jQuery('#scheduleImportStatus').length > 0) {
                            app.event.one('post.overlayPageContent.hide', function(container) {
                                clearTimeout(Vtiger_Import_Js.timer);
                                Vtiger_Import_Js.isReloadStatusPageStopped = true;
                            });
                            Vtiger_Import_Js.isReloadStatusPageStopped = false;
                            Vtiger_Import_Js.timer = setTimeout(Vtiger_Import_Js.scheduledImportRunning, 5000);
                        } else {
							if (window.app && app.getModuleName && app.getModuleName() === 'Campaigns') {
								try { window.sessionStorage && sessionStorage.setItem('vtiger.CampaignsImport.success', '1'); } catch (e) {}
								app.helper.showSuccessNotification({message:'Campaign import succeeded.'});
							} else if (window.app && app.getModuleName && app.getModuleName() === 'Plans') {
								try { window.sessionStorage && sessionStorage.setItem('vtiger.PlansImport.success', '1'); } catch (e) {}
								app.helper.showSuccessNotification({message:'Plans import succeeded.'});
							} else if (window.app && app.getModuleName && app.getModuleName() === 'Contacts') {
								try { window.sessionStorage && sessionStorage.setItem('vtiger.ContactsImport.success', '1'); } catch (e) {}
								app.helper.showSuccessNotification({message:'Contacts import succeeded.'});
							} else {
								app.helper.showSuccessNotification({message:'Import Completed.'});
							}
                        }
                    }
                });
            }
            return false;
        },
        sanitizeFieldMapping: function() {
            var fieldsList = jQuery('.fieldIdentifier');

            var mappedFields = {};
            var errorMessage;
            var mappedDefaultValues = {};

			// Campaigns: enforce deterministic mapping right before collecting mappedFields.
			var __campaignsEnforceCalled = false;
			try { __campaignsEnforceCalled = true; Vtiger_Import_Js.enforceCampaignsMapping(); } catch (eX) {}

			// DEBUG (Campaigns only): dump mapping rows before serialization.
			try {
				var moduleNameDbg = (window.app && app.getModuleName) ? app.getModuleName() : '';
				if (moduleNameDbg === 'Campaigns') {
					var rowsDbg = [];
					fieldsList.each(function (idx, el) {
						var $row = jQuery(el);
						var rowCounter = '';
						try { rowCounter = jQuery('[name=row_counter]', $row).get(0).value; } catch (e0) {}

						var headerText = ($row.find('span[name="header_name"]').first().text() || $row.find('td').first().text() || '').trim();
						var rowText = ($row.text() || '').replace(/\s+/g, ' ').trim();

						var $select = $row.find('select').first();
						var selectMeta = {};
						var selectedVal = '';
						var selectedText = '';
						var options = [];
						if ($select.length) {
							selectMeta = {
								tag: $select.prop('tagName'),
								id: $select.attr('id') || '',
								name: $select.attr('name') || '',
								class: $select.attr('class') || ''
							};
							selectedVal = $select.val();
							selectedText = $select.find('option:selected').first().text();
							$select.find('option').each(function () {
								var $opt = jQuery(this);
								options.push({
									value: $opt.attr('value') || '',
									text: $opt.text()
								});
							});
						}

						var hiddenInputs = [];
						try {
							$row.find('input[type="hidden"]').each(function () {
								var $h = jQuery(this);
								hiddenInputs.push({
									name: $h.attr('name') || '',
									id: $h.attr('id') || '',
									value: $h.val()
								});
							});
						} catch (eH) {}

						rowsDbg.push({
							index: idx,
							row_counter: rowCounter,
							header_text: headerText,
							row_text: rowText,
							select: JSON.stringify(selectMeta),
							select_val: selectedVal,
							selected_option_text: selectedText,
							options_count: options.length,
							options: JSON.stringify(options),
							hidden_inputs: JSON.stringify(hiddenInputs)
						});
					});

					console.log('[Campaigns Import][DEBUG] enforceCampaignsMapping called before sanitizeFieldMapping =', __campaignsEnforceCalled);
					console.table(rowsDbg);
				}
			} catch (eDbg) {}

            for (var i = 0; i < fieldsList.length; ++i) {
                var fieldElement = jQuery(fieldsList.get(i));
                var rowId = jQuery('[name=row_counter]', fieldElement).get(0).value;
				// Use CSV column index (from row_counter) instead of loop index
				var columnIndex = parseInt(rowId, 10) - 1;
				if (isNaN(columnIndex) || columnIndex < 0) {
					columnIndex = i; // safe fallback
				}

                // IMPORTANT: only read the mapping dropdown (not any default-value widget selects)
				var $mapSelect = jQuery('select[name="mapped_fields"]', fieldElement).first();
				var selectedFieldElement = $mapSelect.length ? $mapSelect.find('option:selected').first() : jQuery();
				var selectedFieldName = $mapSelect.length ? $mapSelect.val() : '';
                var selectedFieldDefaultValueElement = jQuery('#' + selectedFieldName + '_defaultvalue', fieldElement);
                var defaultValue = '';
                if (selectedFieldDefaultValueElement.attr('type') == 'checkbox') {
                    defaultValue = selectedFieldDefaultValueElement.is(':checked');
                } else {
                    defaultValue = selectedFieldDefaultValueElement.val();
                }
                if (selectedFieldName != '') {
                    if (selectedFieldName in mappedFields) {
                        errorMessage = app.vtranslate('JS_FIELD_MAPPED_MORE_THAN_ONCE') + " " + selectedFieldElement.data('label');
                        app.helper.showErrorNotification({'message': errorMessage});
                        return false;
                    }
                    mappedFields[selectedFieldName] = columnIndex;
                    if (defaultValue != '') {
                        mappedDefaultValues[selectedFieldName] = defaultValue;
                    }
                }
            }

            var mandatoryFields = JSON.parse(jQuery('#mandatory_fields').val());
            var moduleName = app.getModuleName();
            if (moduleName == 'PurchaseOrder' || moduleName == 'Invoice' || moduleName == 'Quotes' || moduleName == 'SalesOrder') {
                mandatoryFields.hdnTaxType = app.vtranslate('Tax Type');
            }

			// Campaigns BA requirement: do not allow import without Campaign Status mapping/provision.
			if (moduleName === 'Campaigns') {
				var hasCampaignStatus = (mappedFields.hasOwnProperty('campaignstatus') || mappedDefaultValues.hasOwnProperty('campaignstatus'));
				if (!hasCampaignStatus) {
					errorMessage = 'Campaign Status is required. Please map Campaign Status before importing.';
					app.helper.showErrorNotification({'message': errorMessage});
					try { jQuery('.mappedFieldsSelect').closest('table').css({'outline':'2px solid rgba(185,28,28,0.35)','outline-offset':'4px'}); } catch (e) {}
					return false;
				}
			}

            var missingMandatoryFields = [];
            for (var mandatoryFieldName in mandatoryFields) {
                if (mandatoryFieldName in mappedFields) {
                    continue;
                } else {
                    missingMandatoryFields.push('"' + mandatoryFields[mandatoryFieldName] + '"');
                }
            }
            if (missingMandatoryFields.length > 0) {
                errorMessage = app.vtranslate('JS_MAP_MANDATORY_FIELDS') + missingMandatoryFields.join(',');
                app.helper.showErrorNotification({'message': errorMessage});
                return false;
            }

			// Campaigns hard sanity correction: force mapping by CSV header text -> known CSV index.
			// IMPORTANT: row_counter is not reliable here because the rendered row order can differ from CSV order.
			if (moduleName === 'Campaigns') {
				try {
					var normalizeHeader = function (s) {
						return (s || '')
							.replace(/^\uFEFF/, '')
							.replace(/"/g, '')
							.replace(/\s+/g, ' ')
							.trim()
							.toLowerCase();
					};
					var headerToIndex = {
						'campaign name': 0,
						'campaign status': 1,
						'campaign type': 2,
						'start date': 3,
						'expected close date': 4,
						'expected revenue': 5,
						'assigned to': 6,
						'description': 7
					};
					var headerToField = {
						'campaign name': 'campaignname',
						'campaign status': 'campaignstatus',
						'campaign type': 'campaigntype',
						'start date': 'start_date',
						'expected close date': 'closingdate',
						'expected revenue': 'expectedrevenue',
						'assigned to': 'assigned_user_id',
						'description': 'description'
					};
					fieldsList.each(function (idx, el) {
						var $row = jQuery(el);
						var headerText = ($row.find('span[name="header_name"]').first().text() || $row.find('td').first().text() || '');
						var hn = normalizeHeader(headerText);
						if (headerToField.hasOwnProperty(hn) && headerToIndex.hasOwnProperty(hn)) {
							mappedFields[headerToField[hn]] = headerToIndex[hn];
						}
					});
				} catch (eSan) {}
			}

			try { console.log('[FIXED] field_mapping:', JSON.stringify(mappedFields)); } catch (eFix) {}
            jQuery('#field_mapping').val(JSON.stringify(mappedFields));
            jQuery('#default_values').val(JSON.stringify(mappedDefaultValues));
			try {
				if (moduleName === 'Campaigns') {
					console.log('[Campaigns Import][DEBUG] final field_mapping JSON:', jQuery('#field_mapping').val());
				}
			} catch (eDbg3) {}
            return true;
        },
        validateCustomMap: function() {
            var errorMessage;
            var saveMap = jQuery('#save_map').is(':checked');
            if (saveMap) {
                var mapName = jQuery('#save_map_as').val();
                if (jQuery.trim(mapName) == '') {
                    errorMessage = app.vtranslate('JS_MAP_NAME_CAN_NOT_BE_EMPTY');
                    app.helper.showErrorNotification({'message': errorMessage});
                    return false;
                }
                var mapOptions = jQuery('#saved_maps option');
                for (var i = 0; i < mapOptions.length; ++i) {
                    var mapOption = jQuery(mapOptions.get(i));
                    if (mapOption.html() == mapName) {
                        errorMessage = app.vtranslate('JS_MAP_NAME_ALREADY_EXISTS');
                        app.helper.showErrorNotification({'message': errorMessage});
                        return false;
                    }
                }
            }
            return true;
        },
        getParamsFromURL: function(url) {
            var urlParams = url.slice(url.indexOf('?') + 1).split('&');
            var params = {};
            for (var i = 0; i < urlParams.length; i++) {
                var param = urlParams[i].split('=');
                params[param[0]] = param[1];
            }
            return params;
        },
        undoImport: function(url) {
            var params = Vtiger_Import_Js.getParamsFromURL(url);
            Vtiger_Import_Js.showOverLayModal(params);
        },
        loadSavedMap: function() {
            var selectedMapElement = jQuery('#saved_maps option:selected');
            var mapId = selectedMapElement.attr('id');
            var fieldsList = jQuery('.fieldIdentifier');
            var deleteMapContainer = jQuery('#delete_map_container');
            fieldsList.each(function(i, element) {
                var fieldElement = jQuery(element);
                jQuery('[name=mapped_fields]', fieldElement).val('');
            });
            if (mapId == -1) {
                deleteMapContainer.hide();
                return;
            }
            deleteMapContainer.show();
            var mappingString = selectedMapElement.val()
            if (mappingString == '')
                return;
            var mappingPairs = mappingString.split('&');
            var mapping = {};
            for (var i = 0; i < mappingPairs.length; ++i) {
                var mappingPair = mappingPairs[i].split('=');
                var header = mappingPair[0];
                header = header.replace(/\/eq\//g, '=');
                header = header.replace(/\/amp\//g, '&amp;');
				mapping[header] = mappingPair[1];
				mapping[i] = mappingPair[1]; /* To make Row based match when there is no header */
            }
            fieldsList.each(function(i, element) {
                var fieldElement = jQuery(element);
                var mappedFields = jQuery('[name=mapped_fields]', fieldElement);
                var rowId = jQuery('[name=row_counter]', fieldElement).get(0).value;
                var headerNameElement = jQuery('[name=header_name]', fieldElement).get(0);
                var headerName = jQuery(headerNameElement).html();
                if (headerName in mapping) {
                    mappedFields.select2("val", mapping[headerName]);
				} else if (rowId-1 in mapping) { /* Row based match when there is no header - but saved map is loaded. */
                	mappedFields.select2("val", mapping[rowId-1]);
				}
                Vtiger_Import_Js.loadDefaultValueWidget(fieldElement.attr('id'));
            });

			// Campaigns: visually emphasize Campaign Status row when mapping exists.
			try {
				if (window.app && app.getModuleName && app.getModuleName() === 'Campaigns') {
					jQuery('.fieldIdentifier').each(function () {
						var $tr = jQuery(this);
						var val = $tr.find('select[name="mapped_fields"]').val();
						if (val === 'campaignstatus') {
							$tr.css({'background':'rgba(254, 226, 226, 0.7)'}); // light red
							$tr.find('td').first().append(' <span style="color:#b91c1c;font-weight:800;">(Required)</span>');
						}
					});
				}
			} catch (e) {}
        },
        deleteMap: function(module) {
            if (confirm(app.vtranslate('LBL_DELETE_CONFIRMATION'))) {
                var selectedMapElement = jQuery('#saved_maps option:selected');
                var mapId = selectedMapElement.attr('id');

                var postData = {
                    "module": module,
                    "view": 'Import',
                    "mode": 'deleteMap',
                    "mapid": mapId
                }

                app.request.post({'data': postData}).then(
                        function(err, data) {
                            jQuery('#savedMapsContainer').html(data);
                            vtUtils.showSelect2ElementView(jQuery('#saved_maps'));
                        });
            }
        },
        validateMergeCriteria: function(auto_merge) {
			if (auto_merge == 1) {
				var selectedOptions = jQuery('#selected_merge_fields option');
				if (selectedOptions.length == 0) {
					var errorMessage = app.vtranslate('JS_PLEASE_SELECT_ONE_FIELD_FOR_MERGE');
					app.helper.showErrorNotification({message: errorMessage});
					return false;
				}
				Vtiger_Import_Js.convertOptionsToJSONArray('#selected_merge_fields', '#merge_fields');
			}
            return true;
        },
        //TODO move to a common file
        convertOptionsToJSONArray: function(objName, targetObjName) {
            var obj = jQuery(objName);
            var arr = [];
            if (typeof (obj) != 'undefined' && obj[0] != '') {
                for (i = 0; i < obj[0].length; ++i) {
                    arr.push(obj[0].options[i].value);
                }
            }
            if (targetObjName != 'undefined') {
                var targetObj = $(targetObjName);
                if (typeof (targetObj) != 'undefined')
                    targetObj.val(JSON.stringify(arr));
            }
            return arr;
        },
        validateFilePath: function() {
            var importFile = jQuery('#import_file');
            var fileFormats = importFile.data('fileFormats');
            var filePath = importFile.val();
            if (jQuery.trim(filePath) == '') {
                var errorMessage = app.vtranslate('JS_IMPORT_FILE_CAN_NOT_BE_EMPTY');
                app.helper.showErrorNotification({message: errorMessage});
                importFile.focus();
                return false;
            }
            if (!Vtiger_Import_Js.uploadFilter("import_file", fileFormats)) {
                return false;
            }
            if (!Vtiger_Import_Js.uploadFileSize("import_file")) {
                return false;
            }
            return true;
        },
        showPopup: function(url) {
            var params = Vtiger_Import_Js.getParamsFromURL(url);
            var popupInstance = Vtiger_Popup_Js.getInstance();
            popupInstance.showPopup(params);
            return false;
        },
        showLastImportedRecords: function(url) {
            this.showPopup(url);
        },
        showSkippedRecords: function(url) {
            this.showPopup(url);
        },
        showFailedImportRecords: function(url) {
            this.showPopup(url);
        },
        loadDefaultValueWidget: function(rowIdentifierId) {
            var affectedRow = jQuery('#' + rowIdentifierId);
            if (typeof affectedRow == 'undefined' || affectedRow == null)
                return;
            var selectedFieldElement = jQuery('[name=mapped_fields]', affectedRow).get(0);
            var selectedFieldName = jQuery(selectedFieldElement).val();
            var defaultValueContainer = jQuery(jQuery('[name=default_value_container]', affectedRow).get(0));
            var allDefaultValuesContainer = jQuery('#defaultValuesElementsContainer');
            if (defaultValueContainer.children.length > 0) {
                var copyOfDefaultValueWidget = jQuery(':first', defaultValueContainer).detach();
                copyOfDefaultValueWidget.appendTo(allDefaultValuesContainer);
            }
            selectedFieldName = app.helper.purifyContent(selectedFieldName);
            var selectedFieldDefValueContainer = jQuery('#' + selectedFieldName + '_defaultvalue_container', allDefaultValuesContainer);
            var defaultValueWidget = selectedFieldDefValueContainer.detach();
            defaultValueWidget.appendTo(defaultValueContainer);
        },
        loadDefaultValueWidgetForMappedFields: function() {
            var fieldsList = jQuery('.fieldIdentifier');
            fieldsList.each(function(i, element) {
                var fieldElement = jQuery(element);
                var mappedFieldName = jQuery('[name=mapped_fields]', fieldElement).val();
                if (mappedFieldName != '') {
                    Vtiger_Import_Js.loadDefaultValueWidget(fieldElement.attr('id'));
                }
            });

        },
        //TODO: move to a common file
        copySelectedOptions: function(source, destination) {

            var srcObj = jQuery(source);
            var destObj = jQuery(destination);

            if (typeof (srcObj) == 'undefined' || typeof (destObj) == 'undefined')
                return;

            for (i = 0; i < srcObj[0].length; i++) {
                if (srcObj[0].options[i].selected == true) {
                    var rowFound = false;
                    var existingObj = null;
                    for (j = 0; j < destObj[0].length; j++) {
                        if (destObj[0].options[j].value == srcObj[0].options[i].value) {
                            rowFound = true;
                            existingObj = destObj[0].options[j];
                            break;
                        }
                    }

                    if (rowFound != true) {
                        var opt = $('<option selected>');
                        opt.attr('value', srcObj[0].options[i].value);
                        opt.text(srcObj[0].options[i].text);
                        jQuery(destObj[0]).append(opt);
                        srcObj[0].options[i].selected = false;
                        rowFound = false;
                    } else {
                        if (existingObj != null)
                            existingObj.selected = true;
                    }
                }
            }
            return false;
        },
        //TODO move to a common file
        removeSelectedOptions: function(objName) {
            var obj = jQuery(objName);
            if (obj == null || typeof (obj) == 'undefined')
                return;

            for (i = obj[0].options.length - 1; i >= 0; i--) {
                if (obj[0].options[i].selected == true) {
                    obj[0].options[i] = null;
                }
            }
            return false;
        },
        checkFileType: function(e) {
            var filePath = jQuery('#import_file').val();
            if (filePath != '') {
                var fileExtension = filePath.split('.').pop();
                jQuery('#type').val(fileExtension);
                var fileName = e['target']['files'][0]['name'];
                jQuery('#importFileDetails').text(fileName);
                Vtiger_Import_Js.handleFileTypeChange();
            } else {
                jQuery('#importFileDetails').text('');
            }
        },
        handleFileTypeChange: function() {
            var fileType = jQuery('#type').val();
            var delimiterContainer = jQuery('#delimiter_container');
            var hasHeaderContainer = jQuery('#has_header_container');
            if (fileType != 'csv') {
                delimiterContainer.hide();
                hasHeaderContainer.hide();
            } else {
                delimiterContainer.show();
                hasHeaderContainer.show();
            }
        },
        uploadFilter: function(elementId, allowedExtensions) {
            var obj = jQuery('#' + elementId);
            if (obj) {
                var filePath = obj.val();
                var fileParts = filePath.toLowerCase().split('.');
                var fileType = fileParts[fileParts.length - 1];
                var validExtensions = allowedExtensions.toLowerCase().split('|');

                if (validExtensions.indexOf(fileType) < 0) {
                    var errorMessage = app.vtranslate('JS_SELECT_FILE_EXTENSION') + '\n' + validExtensions;
                    app.helper.showErrorNotification({message: errorMessage});
                    obj.focus();
                    return false;
                }
            }
            return true;
        },
        uploadFileSize: function(elementId) {
            var element = jQuery('#' + elementId);
            var importMaxUploadSize = element.closest('td').data('importUploadSize');
            var importMaxUploadSizeInMb = element.closest('td').data('importUploadSizeMb');
            var uploadedFileSize = element.get(0).files[0].size;
            if (uploadedFileSize > importMaxUploadSize) {
                var errorMessage = app.vtranslate('JS_UPLOADED_FILE_SIZE_EXCEEDS') + " " + importMaxUploadSizeInMb + " MB." + app.vtranslate('JS_PLEASE_SPLIT_FILE_AND_IMPORT_AGAIN');
                app.helper.showErrorNotification({message: errorMessage});
                return false;
            }
            return true;
        },
        showOverLayModal: function(params) {
            app.helper.showProgress();
            app.request.get({data: params}).then(function(err, data) {
                app.helper.loadPageContentOverlay(data);
                app.helper.hideProgress();
            });
        },

		timer : 0,
		isReloadStatusPageStopped : false,
        scheduledImportRunning: function() {
			var form = jQuery("#importStatusForm");
			var data = new FormData(form[0]);
			var postParams = {
				data: data,
				contentType: false,
				processData: false
			};
			app.request.post(postParams).then(function(err, response) {
				if(!Vtiger_Import_Js.isReloadStatusPageStopped) {
					app.helper.loadPageContentOverlay(response);
					if (jQuery('#scheduleImportStatus').length > 0) {
						if (!Vtiger_Import_Js.isReloadStatusPageStopped) {
							Vtiger_Import_Js.timer = setTimeout(Vtiger_Import_Js.scheduledImportRunning, 50000);
						}
					}
				}
			});
        },

        googleImportHandler : function() {
            var params = {
                module: 'Google',
                view: 'Setting',
                sourcemodule: app.getModuleName(),
                mode: 'googleImport'
            };
            app.helper.showProgress();
            app.request.get({data: params}).then(function(err, data) {
                app.helper.hideProgress();
                app.helper.hidePageContentOverlay().then(function(){
                    app.helper.loadPageContentOverlay(data).then(function(){
                        var container = jQuery('.googleSettings');
                        var googleSettingInstance = new Google_Settings_Js();
                        googleSettingInstance.registerSettingsEventsForContacts(container);
						
                        Vtiger_Import_Js.registerAuthorizeButton(container);
                        Vtiger_Import_Js.registerSyncNowButton(container, googleSettingInstance);
                    });    
                });
            });
        },
        
        registerImportEvents: function() {
            var importContainer = jQuery('#landingPageDiv');
            importContainer.on('click', '#csvImport', function(e) {
                Vtiger_Import_Js.showImportActionStepOne();
            });

            importContainer.on('click', '#vcfImport', function(e) {
                Vtiger_Import_Js.showImportActionStepOne('vcf');
            });

			importContainer.on('click', '#icsImport', function(e) {
                Vtiger_Import_Js.showImportActionStepOne('ics');
            });
            
            importContainer.on('click', '#googleImport', function(e) {
                Vtiger_Import_Js.googleImportHandler(e);
            });
        },
        registerAuthorizeButton: function(container) {
            container.on('click', '#authorizeButton', function(e) {
                var element = jQuery(e.currentTarget);
                var url = element.data('url');
                var win = window.open(url, '', 'height=600,width=600,channelmode=1');
                //http://stackoverflow.com/questions/1777864/how-to-run-function-of-parent-window-when-child-window-closes 
                window.sync = function() {
                    Vtiger_Import_Js.googleImportHandler();
                };
                window.startSync = function() {};
                win.onunload = function() {};
            });
        },
        registerSyncNowButton: function(container, googleSettingInstance) {
            container.find('#saveSettingsAndImport').on('click', function() {
                googleSettingInstance.validateFieldMappings(container).then(function() {
                    var form = jQuery("form[name='contactsyncsettings']");
                    var fieldMapping = googleSettingInstance.packFieldmappingsForSubmit(container);
                    form.find('#user_field_mapping').val(fieldMapping);
                    var serializedFormData = form.serialize();
                    app.helper.showProgress();
                    app.request.post({data: serializedFormData}).then(function(err, response) {
                        app.helper.hideProgress();
                        app.helper.hideModal();
                        if(err){
                            app.helper.showErrorNotification();
                        }
                        else{
                            var params = {
                                module:'Contacts',
                                view:'Extension',
                                extensionModule:'Google',
                                extensionView:'Index',
                                viewType:'modal'
                            };
                            app.helper.showProgress();
                            app.helper.hidePageContentOverlay().then(function(){
                                app.request.get({data:params}).then(function(err, data){
                                app.helper.hideProgress();
                                    app.helper.loadPageContentOverlay(data).then(function(overlayPageContent){
                                        var overlayContainer = overlayPageContent.find('.data');
                                        var extensionCommonJs = new Vtiger_ExtensionCommon_Js;
                                        extensionCommonJs.getListUrlParams = function() {
                                            var params = {
                                                'module' : app.getModuleName(),
                                                'view' : 'Extension',
                                                'extensionModule' : 'Google',
                                                'extensionView' : 'Index',
                                                'mode' : 'showLogs',
                                                'viewType' : 'modal'
                                            }

                                            return params;
                                        };
                                        extensionCommonJs.registerPaginationEvents(overlayContainer);
										extensionCommonJs.registerLogDetailClickEvent(overlayContainer);
                                    });
                                });
                            });
                        }
                    });
                });

            });
        },
        
        clearSheduledImportData: function() {
            var params = {};
            params['module'] = app.getModuleName();
            params['view'] = 'Import';
            params['mode'] =  'clearCorruptedData';
            Vtiger_Import_Js.showOverLayModal(params);
        },
        cancelImport: function(url) {
            var urlParams = url.slice(url.indexOf('?') + 1).split('&');
            var params = {};
            for (var i = 0; i < urlParams.length; i++) {
                var param = urlParams[i].split('=');
                params[param[0]] = param[1];
            }
            Vtiger_Import_Js.showOverLayModal(params);


        },
        scheduleImport: function(url) {
            var urlParams = url.slice(url.indexOf('?') + 1).split('&');
            var params = {};
            for (var i = 0; i < urlParams.length; i++) {
                var param = urlParams[i].split('=');
                params[param[0]] = param[1];
            }
            Vtiger_Import_Js.showOverLayModal(params);
        },
        showImportActionStepOne: function(format) {
            var params = Vtiger_Import_Js.getDefaultParams();
            params['mode'] = 'importBasicStep';
            if (format == 'vcf') {
                params['fileFormat'] = format;
            } else if (format == 'ics') {
				params['fileFormat'] = format;
			}
            app.helper.showProgress();
            app.request.get({data: params}).then(function(err, data) {
                app.helper.loadPageContentOverlay(data);
                app.helper.hideProgress();
				// Campaigns: if Import completed and user returns to Step 1, show a clear success toast again.
				try {
					if (window.sessionStorage && sessionStorage.getItem('vtiger.CampaignsImport.success') === '1') {
						sessionStorage.removeItem('vtiger.CampaignsImport.success');
						if (window.app && app.getModuleName && app.getModuleName() === 'Campaigns') {
							// persistent banner on Step 1 (required by BA)
							var $row = jQuery('#campaigns_import_success_banner_row');
							if ($row.length) {
								$row.removeClass('hide').addClass('show');
							}
							app.helper.showSuccessNotification({message: 'Campaign import completed successfully.'});
						}
					}
				} catch (e) {}
				try {
					if (window.sessionStorage && sessionStorage.getItem('vtiger.PlansImport.success') === '1') {
						sessionStorage.removeItem('vtiger.PlansImport.success');
						if (window.app && app.getModuleName && app.getModuleName() === 'Plans') {
							var $row = jQuery('#plans_import_success_banner_row');
							if ($row.length) {
								$row.removeClass('hide').addClass('show');
							}
							app.helper.showSuccessNotification({message: 'Plans import completed successfully.'});
						}
					}
				} catch (eP) {}

				// Contacts: if Import completed and user returns to Step 1, show a clear success toast again.
				try {
					if (window.sessionStorage && sessionStorage.getItem('vtiger.ContactsImport.success') === '1') {
						sessionStorage.removeItem('vtiger.ContactsImport.success');
						if (window.app && app.getModuleName && app.getModuleName() === 'Contacts') {
							app.helper.showSuccessNotification({message: 'Contacts import completed successfully.'});
						}
					}
				} catch (eC) {}

				// Campaigns: show valid Campaign Status values near sample CSV link (from server picklist)
				try {
					if (window.app && app.getModuleName && app.getModuleName() === 'Campaigns') {
						var $hint = jQuery('#campaigns_import_status_values_hint');
						if ($hint.length) {
							app.request.get({data: {module: 'Campaigns', action: 'ImportMeta'}}).then(function (err2, res) {
								var values = [];
								try { values = (res && res.campaignstatus) ? res.campaignstatus : []; } catch (e2) {}
								if (!values || !values.length) values = ['Planning', 'Active', 'Completed', 'Cancelled'];
								$hint.html('<strong>Valid Campaign Status:</strong> ' + values.join(', '));
							});
						}
					}
				} catch (e3) {}
				// Plans: show valid Status values near sample CSV link (from server picklist)
				try {
					if (window.app && app.getModuleName && app.getModuleName() === 'Plans') {
						var $hintP = jQuery('#plans_import_status_values_hint');
						if ($hintP.length) {
							app.request.get({data: {module: 'Plans', action: 'ImportMeta'}}).then(function (errP2, resP) {
								var valuesP = [];
								try { valuesP = (resP && resP.plan_status) ? resP.plan_status : []; } catch (eP2) {}
								if (!valuesP || !valuesP.length) valuesP = ['Planning', 'Active', 'Completed', 'Cancelled'];
								$hintP.html('<strong>Valid Status:</strong> ' + valuesP.join(', '));
							});
						}
					}
				} catch (eP3) {}
				if (jQuery('#scheduleImportStatus').length > 0) {
					app.event.one('post.overlayPageContent.hide', function(container) {
						clearTimeout(Vtiger_Import_Js.timer);
						Vtiger_Import_Js.isReloadStatusPageStopped = true;
					});

					Vtiger_Import_Js.isReloadStatusPageStopped = false;
					Vtiger_Import_Js.timer = setTimeout(Vtiger_Import_Js.scheduledImportRunning, 5000);
				}
            });
        },
        getDefaultParams: function() {
            var module = window.app.getModuleName();
            var url = "index.php?module=" + module + "&view=Import";
            var urlParams = url.slice(url.indexOf('?') + 1).split('&');

            var params = {};
            for (var i = 0; i < urlParams.length; i++) {
                var param = urlParams[i].split('=');
                params[param[0]] = param[1];
            }
            return params;
        },
        finishUndoOperation: function(){
            Vtiger_Import_Js.loadListRecords();
        },
        loadListRecords : function(){
			var listInstance;
			if(app.getModuleName() == 'Users') {
				listInstance = new Settings_Users_List_Js();
			}else { 
				listInstance = new Vtiger_List_Js();
			}
			
			var params = {'page': '1'};
			listInstance.loadListViewRecords(params);
        },
        
        registerEvents: function() {
            Vtiger_Import_Js.registerImportEvents();
        }
    }
    jQuery(document).ready(function() {
		try { console.log('[IMPORT DEBUG] Import.js loaded', new Date().toISOString()); } catch (e0) {}
        Vtiger_Import_Js.loadDefaultValueWidgetForMappedFields();
		// Campaigns: enforce deterministic mapping on Step 3 initial render.
		try { Vtiger_Import_Js.scheduleCampaignsAutoMap(); } catch (e1) {}
		// Cancel should never show success/result flow; clear stale flags and return cleanly.
		jQuery(document).off('click.ImportCancel', '.fc-overlay-modal .cancelLink')
			.on('click.ImportCancel', '.fc-overlay-modal .cancelLink', function (e) {
				try { Vtiger_Import_Js.clearImportSuccessFlags(); } catch (ex) {}
				try { Vtiger_Import_Js.cleanupImportOverlay(); } catch (ex0) {}
				// Close overlay if possible; fallback to redirect to module list (never Dashboard).
				try {
					if (app && app.helper && app.helper.hidePageContentOverlay) {
						app.helper.hidePageContentOverlay();
					} else if (app && app.helper && app.helper.hideModal) {
						app.helper.hideModal();
					}
				} catch (ex2) {}
				try {
					var m = (app && app.getModuleName) ? app.getModuleName() : '';
					if (m === 'Campaigns') {
						window.location.href = 'index.php?module=Campaigns&view=List&app=MARKETING';
					} else if (m === 'Plans') {
						window.location.href = 'index.php?module=Plans&view=List&app=MARKETING';
					} else if (m === 'Contacts') {
						window.location.href = 'index.php?module=Contacts&view=List&app=MARKETING';
					} else {
						Vtiger_Import_Js.loadListRecords();
					}
				} catch (ex3) {}
				e.preventDefault();
				return false;
			});

		// Overlay close (X button) can leave backdrop stuck on errors; clean it up.
		jQuery(document).off('click.ImportOverlayClose', '.overlayHeader [data-dismiss="modal"], .overlayHeader .close')
			.on('click.ImportOverlayClose', '.overlayHeader [data-dismiss="modal"], .overlayHeader .close', function () {
				try { Vtiger_Import_Js.cleanupImportOverlay(); } catch (e2) {}
			});
    });
}

