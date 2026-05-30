/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

Vtiger_List_Js(
  "Documents_List_Js",
  {
    massMove: function (url) {
        var self = new Documents_List_Js();
        self.massMove(url);
    },
  },
  {
    getDefaultParams: function () {
      var params = this._super.apply(this, arguments);
      var container = this.getListViewContainer();
      var folderId = container.find('[name="folder_id"]').val();
      var folderValue = container.find('[name="folder_value"]').val();
      // Fallback: tìm trong toàn trang hoặc từ URL (khi container không chứa hidden input)
      if (folderId === undefined || folderId === "" || folderId === null) {
        folderId = jQuery('[name="folder_id"]').val();
      }
      if (
        (folderId === undefined || folderId === "" || folderId === null) &&
        typeof window !== "undefined" &&
        window.location &&
        window.location.search
      ) {
        var match = window.location.search.match(/[?&]folder_id=([^&]*)/);
        if (match) folderId = decodeURIComponent(match[1]);
      }
      if (
        folderValue === undefined ||
        folderValue === "" ||
        folderValue === null
      ) {
        folderValue = jQuery('[name="folder_value"]').val();
      }
      if (
        (folderValue === undefined ||
          folderValue === "" ||
          folderValue === null) &&
        typeof window !== "undefined" &&
        window.location &&
        window.location.search
      ) {
        var matchVal = window.location.search.match(/[?&]folder_value=([^&]*)/);
        if (matchVal) folderValue = decodeURIComponent(matchVal[1]);
      }
      if (
        (folderId === undefined || folderId === "" || folderId === null) &&
        jQuery(".doc-management-view").length
      ) {
        var $docRoot = jQuery(".doc-management-view");
        var dataId = $docRoot.data("folder-id");
        if (dataId !== undefined && dataId !== "") folderId = dataId;
      }
      if (
        (folderValue === undefined || folderValue === "") &&
        jQuery(".doc-management-view").length
      ) {
        folderValue = jQuery(".doc-management-view").data("folder-value");
      }
      if (folderId !== undefined && folderId !== "" && folderId !== null)
        params["folder_id"] = folderId;
      if (
        folderValue !== undefined &&
        folderValue !== "" &&
        folderValue !== null
      )
        params["folder_value"] = folderValue;
      return params;
    },

    registerSearchEvent: function (container) {
      container.find("#searchFolders").on("keydown", function (e) {
        if (e.keyCode === 13) {
                e.preventDefault();
            }
        });
        
      container.find("#searchFolders").on("keyup", function () {
            var searchKey = jQuery(this).val();
            searchKey = searchKey.toLowerCase();
        jQuery(".folder", container).removeClass("selectedFolder");
        container.find("#foldersList").find(".folder").removeClass("hide");
        container
          .find("#foldersList")
          .find(".folder")
          .filter(function () {
                var currentElement = jQuery(this);
            var folderName = currentElement.find(".foldername").text();
                folderName = folderName.toLowerCase();
                var status = folderName.indexOf(searchKey);
            if (status === -1) return true;
                return false;
          })
          .addClass("hide");
        });
    },
    
    registerFolderSelectionEvent: function (container) {
      jQuery(".folder", container).on("click", function () {
        jQuery(".folder", container).removeClass("selectedFolder");
            var currentSelection = jQuery(this);
        currentSelection.addClass("selectedFolder");
        var folderId = currentSelection.data("folderId");
            jQuery('input[name="folderid"]').val(folderId);
        });
    },
    
    registerMoveDocumentsEvent: function (container) {
        var self = this;
      container.find("#moveDocuments").on("submit", function (e) {
            e.preventDefault();
        if (container.find(".folder").filter(".selectedFolder").length) {
                var formData = jQuery(e.currentTarget).serializeFormData();
                app.helper.showProgress();
          app.request.post({ data: formData }).then(function (e, res) {
                    app.helper.hideProgress();
            if (!e) {
                        app.helper.showSuccessNotification({
                message: res.message,
                        });
                    } else {
                        app.helper.showErrorNotification({
                message: app.vtranslate("JS_OPERATION_DENIED"),
                        });
                    }
                    app.helper.hideModal();
                    self.loadListViewRecords();
                });
            } else {
                app.helper.showAlertNotification({
            message: app.vtranslate("JS_SELECT_A_FOLDER"),
                });
            }
        });
    },
    
    registerMoveDocumentsEvents: function (container) {
        this.registerSearchEvent(container);
        this.registerFolderSelectionEvent(container);
        this.registerMoveDocumentsEvent(container);
    },
    
    massMove: function (url) {
        var self = this;
        var listInstance = Vtiger_List_Js.getInstance();
		var validationResult = listInstance.checkListRecordSelected();
      if (!validationResult) {
			var selectedIds = listInstance.readSelectedIds(true);
			var excludedIds = listInstance.readExcludedIds(true);
			var cvId = listInstance.getCurrentCvId();
			var postData = {
          selected_ids: selectedIds,
          excluded_ids: excludedIds,
          viewname: cvId,
        };

        if (app.getModuleName() === "Documents") {
                var defaultparams = listInstance.getDefaultParams();
          postData["folder_id"] = defaultparams["folder_id"];
          postData["folder_value"] = defaultparams["folder_value"];
            }
			var params = {
          url: url,
          data: postData,
			};
            
            app.helper.showProgress();
        app.request.get(params).then(function (e, res) {
                app.helper.hideProgress();
          if (!e && res) {
                    app.helper.showModal(res, {
              cb: function (modalContainer) {
                            self.registerMoveDocumentsEvents(modalContainer);
              },
                    });
                }
            });
      } else {
			listInstance.noRecordSelectedAlert();
		}
    },
    
    unMarkAllFilters: function () {
      jQuery(".listViewFilter").removeClass("active");
    },

    unMarkAllTags: function () {
      var container = jQuery("#listViewTagContainer");
      container
        .find(".tag")
        .removeClass("active")
        .find("i.activeToggleIcon")
        .removeClass("fa-circle-o")
        .addClass("fa-circle");
    },

    unMarkAllFolders: function () {
      jQuery(".documentFolder").removeClass("active");
      jQuery(".documentFolder")
        .find("i")
        .removeClass("fa-folder-open")
        .addClass("fa-folder");
    },

    registerFoldersClickEvent: function () {
        var self = this;
      var filters = jQuery("#module-filters");
      filters.on("click", ".documentFolder", function (e) {
            var targetElement = jQuery(e.target);
        if (
          targetElement.is(".dropdown-toggle") ||
          targetElement.closest("ul").hasClass("dropdown-menu")
        )
          return;
            var element = jQuery(e.currentTarget);
        var el = jQuery("a[data-filter-id]", element);
            self.resetData();
            self.unMarkAllFilters();
            self.unMarkAllTags();
            self.unMarkAllFolders();
        el.closest("li").addClass("active");
        el.closest("li")
          .find("i")
          .removeClass("fa-folder")
          .addClass("fa-folder-open");

        var folderId = el.data("filter-id") || el.data("filterId");
        var folderName = el.data("folder-name") || el.data("folderName");
            self.loadFilter(jQuery('input[name="allCvId"]').val(), {
          folder_id: folderId,
          folder_value: folderName,
        });

        var filtername = jQuery('a[class="filterName"]', element).text();
        jQuery(".module-action-content")
          .find(".filter-name")
          .html(
            '&nbsp;&nbsp;<span class="fa fa-chevron-right" aria-hidden="true"></span> '
          )
          .text(filtername);
        });
    },
    
    registerFiltersClickEvent: function () {
        var self = this;
      var filters = jQuery("#module-filters");
      filters.on("click", ".listViewFilter", function () {
            self.unMarkAllFolders();
        });
    },
    
    addFolderToList: function (folderDetails) {
      var html =
        "" +
        '<li style="font-size:12px;" class="documentFolder">' +
        '<a class="filterName" href="javascript:void(0);" data-filter-id="' +
        folderDetails.folderid +
        '" data-folder-name="' +
        folderDetails.folderName +
        '" title="' +
        folderDetails.folderDesc +
        '">' +
        '<i class="fa fa-folder"></i> ' +
        '<span class="foldername">' +
        folderDetails.folderName +
        "</span>" +
        "</a>" +
        '<div class="pull-right" style="margin-left:4px;">' +
        '<span class="fa fa-pencil-square-o editFolder cursorPointer" data-folder-id="' +
        folderDetails.folderid +
        '" title="Edit" style="margin-right:6px;"></span>' +
        '<span class="fa fa-trash deleteFolder cursorPointer" data-deletable="1" data-folder-id="' +
        folderDetails.folderid +
        '" title="Delete"></span>' +
        "</div>" +
        "</li>";
      jQuery("#folders-list")
        .append(html)
        .find(".documentFolder:last")
        .find(".foldername")
        .text(folderDetails.folderName);
    },

    registerAddFolderModalEvents: function (container) {
        var self = this;
      if (typeof app.helper === "undefined" || !container || !container.length)
        return;
      container.find("select.select2").each(function () {
        var el = jQuery(this);
        if (!el.data("select2")) el.select2({ width: "100%" });
      });
      var addFolderForm = jQuery("#addDocumentsFolder");
        addFolderForm.vtValidate({
        submitHandler: function (form) {
                var formData = addFolderForm.serializeFormData();
                app.helper.showProgress();
          app.request.post({ data: formData }).then(function (e, res) {
                    app.helper.hideProgress();
                    if (!e) {
                        app.helper.hideModal();
                        app.helper.showSuccessNotification({
                message: res.message,
                        });
                        var folderDetails = res.info;
                        self.addFolderToList(folderDetails);
                    }
                    if (e) {
                        app.helper.showErrorNotification({
                message: e,
                });
            }
          });
        },
        });
    },
    
    registerAddFolderEvent: function () {
        var self = this;
      var filters = jQuery("#module-filters");
      filters.find("#createFolder").on("click", function () {
            var params = {
          module: app.getModuleName(),
          view: "AddFolder",
            };
            app.helper.showProgress();
        app.request.get({ data: params }).then(function (e, res) {
                app.helper.hideProgress();
          if (!e) {
                    app.helper.showModal(res, {
              cb: function (modalContainer) {
                            self.registerAddFolderModalEvents(modalContainer);
              },
                    });
                }
            });
        });
    },
    
    registerFoldersSearchEvent: function () {
      var filters = jQuery("#module-filters");
      filters.find(".search-folders").on("keyup", function (e) {
            var element = jQuery(e.currentTarget);
            var val = element.val().toLowerCase();
        jQuery("li.documentFolder", filters).each(function () {
                var filterEle = jQuery(this);
          var folderName = filterEle.find(".foldername").text();
                folderName = folderName.toLowerCase();
          if (folderName.indexOf(val) === -1) {
            filterEle.addClass("hide");
          } else {
            filterEle.removeClass("hide");
          }
        });

        if (jQuery("li.documentFolder", filters).not(".hide").length > 0) {
          jQuery("#folders-list", filters).find(".noFolderText").hide();
        } else {
          jQuery("#folders-list", filters).find(".noFolderText").show();
        }
      });
    },

    registerDocManagementFolderLinkClick: function () {
      // Đảm bảo click folder link cập nhật URL (folder_id, folder_value) – full page navigation
      jQuery(document).on(
        "click",
        "a.doc-management-folder-item",
        function (e) {
          var href = jQuery(this).attr("href");
          if (href && href.indexOf("folder_id=") !== -1) {
            e.preventDefault();
            window.location.href = href;
          }
        }
      );
    },

    registerDocManagementFolderEvents: function () {
      var self = this;
      jQuery(document).on("click", ".doc-delete-folder", function (e) {
        e.preventDefault();
        e.stopPropagation();
        var el = jQuery(e.currentTarget);
        var deletable = el.data("deletable");
        var folderId = el.data("folder-id") || el.data("folderId");
        if (deletable != "1") {
          app.helper.showAlertNotification({
            message: app.vtranslate("JS_FOLDER_IS_NOT_EMPTY"),
          });
          return;
        }
        app.helper
          .showConfirmationBox({
            message: app.vtranslate("JS_LBL_ARE_YOU_SURE_YOU_WANT_TO_DELETE"),
          })
          .then(function () {
            app.helper.showProgress();
            app.request
              .post({
                data: {
                  module: app.getModuleName(),
                  mode: "delete",
                  action: "Folder",
                  folderid: folderId,
                },
              })
              .then(function (err, res) {
                app.helper.hideProgress();
                if (!err && res && res.success) {
                  el.closest(".doc-management-folder-row").remove();
                  app.helper.showSuccessNotification({ message: res.message });
                  var listInstance = Vtiger_List_Js.getInstance();
                  if (listInstance && listInstance.loadListViewRecords)
                    listInstance.loadListViewRecords();
                } else {
                  app.helper.showErrorNotification({
                    message:
                      (res && res.message) ||
                      app.vtranslate("JS_OPERATION_DENIED"),
                  });
                }
              });
          });
      });
      jQuery(document).on("click", ".doc-edit-folder", function (e) {
        e.preventDefault();
        e.stopPropagation();
        var folderId =
          jQuery(e.currentTarget).data("folder-id") ||
          jQuery(e.currentTarget).data("folderId");
        var params = {
          module: app.getModuleName(),
          view: "AddFolder",
          folderid: folderId,
          mode: "edit",
        };
        app.helper.showProgress();
        app.request.get({ data: params }).then(function (err, res) {
          app.helper.hideProgress();
          if (!err) {
            app.helper.showModal(res, {
              cb: function (modalContainer) {
                self.registerEditFolderModalEvents(modalContainer);
              },
            });
          }
        });
        });
    },
    
    registerDeleteFolderEvent: function () {
      var filters = jQuery("#module-filters");
      filters.on("click", ".deleteFolder", function (e) {
        e.preventDefault();
        e.stopPropagation();
            var element = jQuery(e.currentTarget);
            
        var deletable = element.data("deletable");
        if (deletable == "1") {
          app.helper
            .showConfirmationBox({
              message: app.vtranslate("JS_LBL_ARE_YOU_SURE_YOU_WANT_TO_DELETE"),
            })
            .then(function () {
              var folderId = element.data("folderId");
                    var params = {
                module: app.getModuleName(),
                mode: "delete",
                action: "Folder",
                folderid: folderId,
                    };
                    app.helper.showProgress();
              app.request.post({ data: params }).then(function (e, res) {
                        app.helper.hideProgress();
                if (!e && res && res.success) {
                  element.closest("li.documentFolder").remove();
                            app.helper.showSuccessNotification({
                    message: res.message,
                  });
                  var listInstance = Vtiger_List_Js.getInstance();
                  if (listInstance && listInstance.loadListViewRecords) {
                    listInstance.loadListViewRecords();
                  }
                } else {
                  var msg =
                    res && res.message
                      ? res.message
                      : app.vtranslate("JS_OPERATION_DENIED");
                  app.helper.showErrorNotification({ message: msg });
                        }
                    });
                });
            } else {
                app.helper.showAlertNotification({
            message: app.vtranslate("JS_FOLDER_IS_NOT_EMPTY"),
                });
            }
        });
    },
    
    updateFolderInList: function (folderDetails) {
      jQuery("#folders-list")
        .find('a.filterName[data-filter-id="' + folderDetails.folderid + '"]')
        .attr("title", folderDetails.folderDesc)
        .find(".foldername")
        .text(folderDetails.folderName);
    },

    registerEditFolderModalEvents: function (container) {
        var self = this;
      if (container && container.length) {
        container.find("select.select2").each(function () {
          var el = jQuery(this);
          if (!el.data("select2")) el.select2({ width: "100%" });
        });
      }
      container.find("#addDocumentsFolder").on("submit", function (e) {
            e.preventDefault();
            var formData = jQuery(this).serializeFormData();
            app.helper.showProgress();
        app.request.post({ data: formData }).then(function (err, res) {
                app.helper.hideProgress();
          if (!err) {
                    app.helper.hideModal();
            app.helper.showSuccessNotification({ message: res.message });
                    var folderDetails = res.info;
            if (folderDetails && jQuery("#folders-list").length) {
                    self.updateFolderInList(folderDetails);
            }
            var listInstance = Vtiger_List_Js.getInstance();
            if (listInstance && listInstance.loadListViewRecords)
              listInstance.loadListViewRecords();
                } else {
            app.helper.showAlertNotification({ message: err });
                }
            });
        });
    },
    
    registerFolderEditEvent: function () {
        var self = this;
      var filters = jQuery("#module-filters");
      filters.on("click", ".editFolder", function (e) {
            var element = jQuery(e.currentTarget);
        var folderId = element.data("folderId");
            var params = {
          module: app.getModuleName(),
          view: "AddFolder",
          folderid: folderId,
          mode: "edit",
            };
            app.helper.showProgress();
        app.request.get({ data: params }).then(function (e, res) {
                app.helper.hideProgress();
          if (!e) {
                    app.helper.showModal(res, {
              cb: function (modalContainer) {
                            self.registerEditFolderModalEvents(modalContainer);
              },
                    });
                }
            });
        });
    },
    
    registerRowDoubleClickEvent: function () {
        return true;
    },

    registerEvents: function () {
        this._super();
        
        this.registerFoldersClickEvent();
        this.registerAddFolderEvent();
        this.registerFoldersSearchEvent();
        this.registerFolderEditEvent();
        this.registerDeleteFolderEvent();
      this.registerDocManagementFolderLinkClick();
      this.registerDocManagementFolderEvents();
        this.registerFiltersClickEvent();
		
		//To make folder non-deletable if a document is uploaded
      app.event.on("post.documents.save", function (event, data) {
			var folderid = data.folderid;
        var folder = jQuery("#folders-list")
          .find('[data-folder-id="' + folderid + '"]')
          .filter(".deleteFolder");
        if (folder.length) {
          folder.attr("data-deletable", "0");
        }
      });
    },
  }
);
