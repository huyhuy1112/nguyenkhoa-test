/*+***********************************************************************************
 * ProjectTask List: click row -> open task detail modal (same UI as Project TaskBoard panel)
 *************************************************************************************/

Vtiger_List_Js(
  "ProjectTask_List_Js",
  {},
  {
    _navigatingToTask: false,
    _taskModalContainer: null,
    _taskModalUsers: null,

    registerRowClickEvent: function () {
      var thisInstance = this;
      var listViewContentDiv = this.getListViewContainer();

      // In ProjectTask List, clicking the related Project should navigate to full Project Detail,
      // not trigger reference quick preview overlay (which is broken for Project in Management app).
      listViewContentDiv.on("click", "a.js-reference-display-value", function (e) {
        var link = jQuery(e.currentTarget);
        var href = link.attr("href") || "";
        // Normalize to data params; core reference preview uses this too.
        var data = app.convertUrlToDataParams(href);
        if (data && data.module === "Project" && data.record) {
          e.preventDefault();
          e.stopImmediatePropagation();
          e.stopPropagation();
          window.location.href =
            "index.php?module=Project&view=Detail&record=" +
            encodeURIComponent(data.record) +
            "&app=MANAGEMENT";
          return false;
        }
      });

      listViewContentDiv.on("click", ".listViewEntries a", function (e) {
        var target = jQuery(e.target);
        if (target.hasClass("js-reference-display-value")) return;
        e.preventDefault();
        e.stopPropagation();
        var row = jQuery(e.currentTarget).closest(".listViewEntries");
        if (!row.length) return;
        var recordId = row.data("id");
        if (typeof recordId === "undefined") return;
        thisInstance.openTaskModal(recordId, row);
      });

      listViewContentDiv.on("click", ".listViewEntries", function (e) {
        var target = jQuery(e.target);
        if (target.hasClass("js-reference-display-value")) return;
        if (target.closest("a").length) return;
        setTimeout(function () {
          if (thisInstance._navigatingToTask) return;
          var editedLength = jQuery(".listViewEntries.edited").length;
          if (editedLength === 0) {
            var selection = window.getSelection().toString();
            if (selection.length === 0) {
              var innerTarget = jQuery(e.target, jQuery(e.currentTarget));
              if (innerTarget.closest("td").is("td:first-child")) return;
              if (innerTarget.closest("tr").hasClass("edited")) return;
              if (jQuery(e.target).is('input[type="checkbox"]')) return;
              var elem = jQuery(e.currentTarget);
              var recordId = elem.data("id");
              if (typeof recordId === "undefined") return;
              e.preventDefault();
              thisInstance.openTaskModal(recordId, elem);
            }
          }
        }, 300);
      });
    },

    openTaskModal: function (recordId, rowEl) {
      var thisInstance = this;
      recordId = String(recordId).replace(/[^0-9]/g, "") || "";
      if (!recordId) return;
      if (thisInstance._navigatingToTask) return;
      thisInstance._navigatingToTask = true;

      thisInstance.ensureTaskModalInDom();
      var container = thisInstance._taskModalContainer;
      var overlay = container.closest(".projecttask-list-task-overlay");
      var panel = container.find(".task-detail-modal").get(0);

      overlay.show();
      container.find(".task-detail-modal").removeClass("hidden");
      container.find(".detail-title").text("");
      container.find(".detail-description").val("");
      container.find(".task-comments-list").empty();
      container.find(".task-history-list").empty();
      container.find(".mk-pt-subtasks__list").empty();
      container.find(".mk-pt-subtasks__empty").show();
      container.find(".task-comment-input").val("");
      container.find(".task-comment-file-input").val("");
      container.find(".task-comment-file-name").text("").addClass("hidden");
      container.find(".task-comment-emoji-picker").addClass("hidden");

      function afterLoad(task) {
        thisInstance._navigatingToTask = false;
        if (!task) {
          overlay.hide();
          return;
        }
        thisInstance.fillTaskPanel(container, task);
        thisInstance.loadTaskComments(container, task.recordid);
        thisInstance.loadSubtasks(container, task.recordid);
        thisInstance.switchTaskTab(container, "comments");
      }

      app.helper.showProgress();
      app.request
        .post({
          data: {
            module: "ProjectTask",
            action: "GetTaskDetail",
            record: recordId,
          },
        })
        .then(
          function (err, data) {
            app.helper.hideProgress();
            var res = data && data.result ? data.result : data || {};
            var task = res.task;
            afterLoad(task);
          },
          function () {
            app.helper.hideProgress();
            thisInstance._navigatingToTask = false;
            overlay.hide();
          }
        );
    },

    ensureTaskModalInDom: function () {
      var thisInstance = this;
      if (
        thisInstance._taskModalContainer &&
        thisInstance._taskModalContainer.length
      )
        return;

      var statusOpts = [
        "Open",
        "In Progress",
        "Completed",
        "Deferred",
        "Canceled",
      ];
      var statusHtml = statusOpts
        .map(function (s) {
          return (
            '<option value="' +
            s.replace(/"/g, "&quot;") +
            '">' +
            s.replace(/</g, "&lt;") +
            "</option>"
          );
        })
        .join("");

      var html =
        '<div class="projecttask-list-task-overlay mk-project-task-detail-scope" id="projecttask-list-task-overlay" style="display:none;">' +
        '<div class="project-task-board projecttask-list-task-board mk-project-task-detail-scope">' +
        '<div class="task-detail-modal mk-project-task-detail-modal">' +
        '<div class="task-detail-dialog mk-project-task-detail">' +
        '<div class="task-detail-header mk-project-task-detail__header">' +
        '<div class="header-left mk-project-task-detail__header-left"><span class="status-pill detail-status mk-project-task-detail__status">--</span><span class="detail-id mk-project-task-detail__id"></span></div>' +
        '<div class="header-right mk-project-task-detail__header-right">' +
        '<button type="button" class="tab-btn mk-project-task-detail__nav-tab active" data-tab="comments">Comments</button>' +
        '<button type="button" class="tab-btn mk-project-task-detail__nav-tab" data-tab="history">Task history</button>' +
        '<button type="button" class="panel-close mk-project-task-detail__close" aria-label="Close">&times;</button></div></div>' +
        '<div class="task-detail-content mk-project-task-detail__body">' +
        '<div class="task-detail-left mk-project-task-detail__main">' +
        '<div class="detail-back-wrap hide"><a href="javascript:void(0)" class="detail-back-link">&larr; Back to <span class="detail-back-parent-name"></span></a></div>' +
        '<div class="detail-breadcrumb"></div>' +
        '<div class="detail-title"></div>' +
        '<div class="detail-section"><div class="section-label">Description</div><textarea class="form-control detail-description" rows="3" placeholder="Write description..."></textarea></div>' +
        '<div class="detail-table">' +
        '<div class="detail-row"><span class="label">Start/Due</span><span class="value detail-dates"><input type="date" class="detail-start"><span class="date-sep">&rarr;</span><input type="date" class="detail-end"></span></div>' +
        '<div class="detail-row"><span class="label">Labels</span><span class="value"><input type="text" class="detail-labels" placeholder="Select"></span></div>' +
        '<div class="detail-row"><span class="label">Assignees</span><span class="value"><select class="detail-owner-select"></select></span></div>' +
        '<div class="detail-row"><span class="label">Time</span><span class="value"><input type="text" class="detail-time" placeholder="Add logged time / Add estimated time"></span></div>' +
        '<div class="detail-row"><span class="label">Progress</span><span class="value"><input type="number" min="0" max="100" class="detail-progress"><span class="progress-suffix">%</span></span></div>' +
        '<div class="detail-row"><span class="label">Status</span><span class="value"><select class="detail-status-select">' +
        statusHtml +
        "</select></span></div>" +
        "</div>" +
        '<div class="detail-subtasks mk-pt-subtasks board-subtasks-block"><div class="section-label mk-pt-subtasks__title">Subtasks</div>' +
        '<div class="tasksListToolbar mk-pt-subtasks__toolbar"><input type="text" class="form-control mk-pt-subtasks__input board-subtask-title-input quickAddTaskInput" placeholder="Add a subtask and press Enter">' +
        '<button type="button" class="btn btn-primary btn-sm mk-pt-subtasks__save board-subtask-save-btn">Save</button></div>' +
        '<div class="task-list-container mk-pt-subtasks__list-wrap"><div class="task-list-empty mk-pt-subtasks__empty text-muted">No subtasks exist in this task</div><ul class="task-list mk-pt-subtasks__list list-unstyled"></ul></div></div></div>' +
        '<div class="task-detail-right mk-project-task-detail__sidebar">' +
        '<div class="ann-detail-tabs mk-project-task-detail__sidebar-tabs"><button type="button" class="ann-tab task-detail-tab active" data-tab="comments">Comments <span class="badge task-comments-badge">0</span></button>' +
        '<button type="button" class="ann-tab task-detail-tab" data-tab="history">Task history</button></div>' +
        '<div id="task-panel-comments-list" class="ann-detail-panel task-detail-panel mk-project-task-detail__panel mk-project-task-detail__panel--comments"><ul class="ann-comments-list list-unstyled task-comments-list"></ul>' +
        '<div class="ann-add-comment mk-project-task-detail__composer">' +
        '<div class="task-comment-toolbar mk-project-task-detail__composer-toolbar">' +
        '<button type="button" class="btn btn-default btn-xs task-comment-upload-btn" title="Upload from computer"><span class="fa fa-paperclip"></span> Upload</button>' +
        '<input type="file" class="task-comment-file-input" accept="*" style="display:none">' +
        '<span class="task-comment-file-name text-muted small hidden"></span></div>' +
        '<textarea class="form-control task-comment-input mk-project-task-detail__composer-input" rows="3" placeholder="Write a comment"></textarea>' +
        '<button type="button" class="btn btn-primary btn-sm task-comment-add mk-project-task-detail__composer-add">Add</button></div></div>' +
        '<div id="task-panel-history" class="ann-detail-panel task-detail-panel mk-project-task-detail__panel mk-project-task-detail__panel--history hide"><ul class="task-history-list list-unstyled"></ul><div class="task-history-empty text-muted small">No history yet.</div></div></div></div>' +
        '<div class="task-detail-footer mk-project-task-detail__footer"><button type="button" class="btn btn-primary detail-save mk-project-task-detail__btn-save">Save</button><button type="button" class="btn btn-default detail-cancel mk-project-task-detail__btn-cancel">Cancel</button></div></div></div></div></div>';

      jQuery("body").append(html);
      thisInstance._taskModalContainer = jQuery(
        "#projecttask-list-task-overlay .project-task-board"
      );
      if (!jQuery("#ann-comment-lightbox").length) {
        jQuery("body").append(
          '<div id="ann-comment-lightbox" class="ann-comment-lightbox">' +
            '<div class="ann-comment-lightbox-backdrop"></div>' +
            '<div class="ann-comment-lightbox-content">' +
            '<button type="button" class="ann-comment-lightbox-close" aria-label="Close">&times;</button>' +
            '<img class="ann-comment-lightbox-img" alt="" />' +
            '<a href="#" class="ann-comment-lightbox-download" target="_blank" download><span class="fa fa-download"></span> Download</a>' +
            "</div></div>"
        );
        jQuery(document)
          .on("click", ".ann-comment-attachment a", function (e) {
            var link = jQuery(this);
            var img = link.find("img.ann-comment-img");
            if (img.length) {
              e.preventDefault();
              var src = img.attr("src");
              var href = link.attr("href");
              if (!src) return;
              var lb = jQuery("#ann-comment-lightbox");
              lb.find(".ann-comment-lightbox-img").attr("src", src);
              lb.find(".ann-comment-lightbox-download")
                .attr("href", href || src)
                .attr("download", "");
              lb.show();
            }
          })
          .on(
            "click",
            "#ann-comment-lightbox .ann-comment-lightbox-close, #ann-comment-lightbox .ann-comment-lightbox-backdrop",
            function () {
              jQuery("#ann-comment-lightbox").hide();
            }
          );
      }
      thisInstance._bindTaskModalEvents();
    },

    _bindTaskModalEvents: function () {
      var thisInstance = this;
      var container = thisInstance._taskModalContainer;
      var overlay = jQuery("#projecttask-list-task-overlay");

      container.find(".panel-close, .detail-cancel").on("click", function () {
        overlay.hide();
      });

      container.find(".detail-save").on("click", function () {
        thisInstance.saveTaskFromPanel();
      });
      jQuery(document)
        .off("click.projecttask-addcomment")
        .on(
          "click.projecttask-addcomment",
          "#projecttask-list-task-overlay .task-comment-add",
          function (e) {
            e.preventDefault();
            e.stopPropagation();
            if (jQuery("#projecttask-list-task-overlay").is(":visible")) {
              thisInstance.addCommentFromPanel();
            }
          }
        );
      jQuery(document)
        .off("click.projecttask-upload")
        .on(
          "click.projecttask-upload",
          "#projecttask-list-task-overlay .task-comment-upload-btn",
          function (e) {
            e.preventDefault();
            jQuery(
              "#projecttask-list-task-overlay .task-comment-file-input"
            ).trigger("click");
          }
        );
      jQuery(document)
        .off("change.projecttask-file")
        .on(
          "change.projecttask-file",
          "#projecttask-list-task-overlay .task-comment-file-input",
          function () {
            var input = jQuery(this);
            var file = input[0].files && input[0].files[0];
            var overlay = jQuery("#projecttask-list-task-overlay");
            var container = overlay.find(".project-task-board").first();
            overlay.data("comment-pending-file", file || null);
            if (container.length)
              container.data("comment-pending-file", file || null);
            var name = file ? file.name : "";
            var fn = container.find(".task-comment-file-name");
            fn.text(name || "");
            if (name) fn.removeClass("hidden");
            else fn.addClass("hidden");
          }
        );
      container.find(".task-comment-emoji-btn").on("click", function (e) {
        e.preventDefault();
        var picker = container.find(".task-comment-emoji-picker");
        if (picker.hasClass("hidden")) {
          thisInstance._ensureEmojiPickerContent(container);
          picker.removeClass("hidden");
        } else {
          picker.addClass("hidden");
        }
      });
      jQuery(document).on("click.projecttask-comment-emoji", function (e) {
        var container = thisInstance._taskModalContainer;
        if (!container || !container.length) return;
        var picker = container.find(".task-comment-emoji-picker");
        if (
          picker.length &&
          !picker.hasClass("hidden") &&
          !jQuery(e.target).closest(
            ".task-comment-emoji-btn, .task-comment-emoji-picker"
          ).length
        )
          picker.addClass("hidden");
      });
      container.find(".board-subtask-save-btn").on("click", function () {
        thisInstance.addSubtaskFromPanel();
      });
      container.find(".board-subtask-title-input").on("keydown", function (e) {
        if (e.keyCode === 13) {
          e.preventDefault();
          thisInstance.addSubtaskFromPanel();
        }
      });

      container
        .find(".mk-project-task-detail__nav-tab, .task-detail-tab")
        .on("click", function () {
          var tab = jQuery(this).data("tab");
          if (tab) thisInstance.switchTaskTab(container, tab);
        });

      container.find(".mk-pt-subtasks__list").on("click", function (e) {
          var row = jQuery(e.target).closest(".mk-pt-subtasks__row");
          if (!row.length) return;
          var recordId = row.attr("data-recordid");
          var wrap = jQuery(e.target).closest(".subtask-status-wrap");
          var option = jQuery(e.target).closest(".subtask-status-option");
          var trigger = jQuery(e.target).closest(".subtask-status-trigger");

          if (jQuery(e.target).closest(".task-checkbox").length) {
            var checked = jQuery(e.target).prop("checked");
            var status = checked ? "Completed" : "Open";
            var progress = checked ? "100%" : "0%";
            app.request
              .post({
                data: {
                  module: "ProjectTask",
                  action: "SaveAjax",
                  record: recordId,
                  field: "projecttaskstatus",
                  value: status,
                },
              })
              .then(function (err) {
                if (err) return;
                app.request
                  .post({
                    data: {
                      module: "ProjectTask",
                      action: "SaveAjax",
                      record: recordId,
                      field: "projecttaskprogress",
                      value: progress,
                    },
                  })
                  .then(function () {
                    row.find(".task-title").toggleClass("task-done", checked);
                    var icon = row.find(
                      ".subtask-status-trigger .subtask-status-icon"
                    );
                    if (icon.length)
                      icon.attr(
                        "class",
                        "subtask-status-icon " +
                          thisInstance._getSubtaskStatusIcon(status)
                      );
                  });
              });
            return;
          }
          if (option.length && wrap.length) {
            var dropdown = wrap.find(".subtask-status-dropdown");
            if (!dropdown.hasClass("hidden")) {
              var newStatus = option.attr("data-value");
              if (recordId && newStatus) {
                var progress = newStatus === "Completed" ? "100%" : "0%";
                app.request
                  .post({
                    data: {
                      module: "ProjectTask",
                      action: "SaveAjax",
                      record: recordId,
                      field: "projecttaskstatus",
                      value: newStatus,
                    },
                  })
                  .then(function (err) {
                    if (err) return;
                    app.request
                      .post({
                        data: {
                          module: "ProjectTask",
                          action: "SaveAjax",
                          record: recordId,
                          field: "projecttaskprogress",
                          value: progress,
                        },
                      })
                      .then(function () {
                        row
                          .find(".task-title")
                          .toggleClass("task-done", newStatus === "Completed");
                        row
                          .find(".task-checkbox")
                          .prop("checked", newStatus === "Completed");
                        var trigIcon = wrap.find(
                          ".subtask-status-trigger .subtask-status-icon"
                        );
                        if (trigIcon.length)
                          trigIcon.attr(
                            "class",
                            "subtask-status-icon " +
                              thisInstance._getSubtaskStatusIcon(newStatus)
                          );
                        wrap
                          .find(".subtask-status-option")
                          .removeClass("subtask-status-option-selected")
                          .filter('[data-value="' + newStatus + '"]')
                          .addClass("subtask-status-option-selected");
                        dropdown.addClass("hidden");
                      });
                  });
              }
            }
            e.preventDefault();
            e.stopPropagation();
            return;
          }
          if (trigger.length && wrap.length) {
            e.preventDefault();
            e.stopPropagation();
            var dropdown = wrap.find(".subtask-status-dropdown");
            container.find(".subtask-status-dropdown").addClass("hidden");
            container
              .find(".subtask-status-trigger")
              .attr("aria-expanded", "false");
            dropdown.toggleClass("hidden");
            trigger.attr(
              "aria-expanded",
              dropdown.hasClass("hidden") ? "false" : "true"
            );
            return;
          }
          if (!wrap.length) {
            e.preventDefault();
            if (recordId) thisInstance.openTaskModal(recordId, null);
          }
        });

      jQuery(document).on("click.projecttask-list-modal", function (e) {
        if (
          !jQuery(e.target).closest(
            ".projecttask-list-task-overlay .subtask-status-wrap"
          ).length
        ) {
          thisInstance._taskModalContainer
            .find(".subtask-status-dropdown")
            .addClass("hidden");
          thisInstance._taskModalContainer
            .find(".subtask-status-trigger")
            .attr("aria-expanded", "false");
        }
      });
    },

    _currentTask: null,

    fillTaskPanel: function (container, task) {
      var self = this;
      self._currentTask = task;
      container.find(".detail-title").text(task.name || "");
      container.find(".detail-description").val(task.description || "");
      container.find(".detail-start").val(task.startdate || "");
      container.find(".detail-end").val(task.enddate || "");
      var statusEl = container.find(".detail-status");
      statusEl.text(task.projecttaskstatus || "--");
      statusEl.attr(
        "class",
        "status-pill detail-status mk-project-task-detail__status"
      );
      var statusKey = (task.projecttaskstatus || "")
        .toLowerCase()
        .replace(/\s+/g, "-");
      if (statusKey) {
        statusEl.addClass("mk-project-task-detail__status--" + statusKey);
      }
      container
        .find(".detail-status-select")
        .val(task.projecttaskstatus || "Open");
      container
        .find(".detail-id")
        .text(task.recordid ? "#" + task.recordid : "");
      container
        .find(".detail-breadcrumb")
        .text((task.project_name || "") + " › Tasks");
      var progressVal = (
        task.progress != null
          ? task.progress
          : task.projecttaskprogress != null
          ? task.projecttaskprogress
          : "0"
      )
        .toString()
        .replace(/%/g, "");
      container.find(".detail-progress").val(progressVal);
      (function enhanceModalProgressBar() {
        var pct = parseInt(progressVal, 10);
        if (isNaN(pct)) {
          pct = 0;
        }
        pct = Math.min(100, Math.max(0, pct));
        var $value = container
          .find(".detail-row")
          .has(".detail-progress")
          .find(".value")
          .first();
        if (!$value.length) {
          return;
        }
        var tone = pct >= 100 ? "done" : "default";
        var label = pct > 0 ? pct + "%" : "";
        var $input = $value.find(".detail-progress");
        var $suffix = $value.find(".progress-suffix");
        if (!$value.find(".mk-projecttask-progress").length) {
          $value.prepend(
            '<div class="mk-projecttask-progress mk-projecttask-progress--' +
              tone +
              '"><div class="mk-projecttask-progress__fill" style="width:' +
              pct +
              '%"></div>' +
              (label
                ? '<span class="mk-projecttask-progress__label">' + label + "</span>"
                : "") +
              "</div>"
          );
        } else {
          $value
            .find(".mk-projecttask-progress")
            .removeClass("mk-projecttask-progress--done mk-projecttask-progress--default")
            .addClass("mk-projecttask-progress--" + tone);
          $value.find(".mk-projecttask-progress__fill").css("width", pct + "%");
          var $lbl = $value.find(".mk-projecttask-progress__label");
          if (label) {
            if ($lbl.length) {
              $lbl.text(label);
            } else {
              $value
                .find(".mk-projecttask-progress")
                .append('<span class="mk-projecttask-progress__label">' + label + "</span>");
            }
          } else {
            $lbl.remove();
          }
        }
        $input.off("input.mkModalProgress").on("input.mkModalProgress", function () {
          var v = parseInt(this.value, 10);
          if (isNaN(v)) {
            v = 0;
          }
          v = Math.min(100, Math.max(0, v));
          var t = v >= 100 ? "done" : "default";
          var l = v > 0 ? v + "%" : "";
          $value
            .find(".mk-projecttask-progress")
            .removeClass("mk-projecttask-progress--done mk-projecttask-progress--default")
            .addClass("mk-projecttask-progress--" + t);
          $value.find(".mk-projecttask-progress__fill").css("width", v + "%");
          var $l = $value.find(".mk-projecttask-progress__label");
          if (l) {
            if ($l.length) {
              $l.text(l);
            } else {
              $value
                .find(".mk-projecttask-progress")
                .append('<span class="mk-projecttask-progress__label">' + l + "</span>");
            }
          } else {
            $l.remove();
          }
        });
        $input.css({ width: "72px", display: "inline-block", marginLeft: "10px", verticalAlign: "middle" });
        $suffix.css({ marginLeft: "2px" });
      })();

      var ownerSelect = container.find(".detail-owner-select");
      function setOwnerSelect(users) {
        var opts = [];
        jQuery.each(users, function (id, name) {
          opts.push(
            '<option value="' +
              id +
              '">' +
              (name || "").replace(/</g, "&lt;") +
              "</option>"
          );
        });
        ownerSelect.html(opts.join(""));
        if (task.smownerid) ownerSelect.val(task.smownerid);
      }
      if (self._taskModalUsers) {
        setOwnerSelect(self._taskModalUsers);
      } else {
        app.request
          .post({
            data: { module: "ProjectTask", action: "GetAssignableUsers" },
          })
          .then(function (err, data) {
            var res = data && data.result ? data.result : data || {};
            self._taskModalUsers = res.users || {};
            setOwnerSelect(self._taskModalUsers);
          });
      }
    },

    _appendCommentItem: function (list, c, optBlobUrl) {
      var initial = (c.userName || "?").charAt(0).toUpperCase();
      var text = (c.comment_text || "")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;");
      var attHtml = "";
      if (optBlobUrl) {
        attHtml =
          '<div class="ann-comment-attachment"><img src="' +
          optBlobUrl.replace(/"/g, "&quot;") +
          '" alt="" class="ann-comment-img" /></div>';
      } else {
        (c.attachments || []).forEach(function (a) {
          var ext = (a.name || "").split(".").pop().toLowerCase();
          var isImg =
            /^(jpg|jpeg|png|gif|webp|bmp|tiff|tif|svg|ico|heic|heif)$/.test(
              ext
            );
          var safeName = (a.name || "file")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;");
          var url = (a.url || "#").replace(/"/g, "&quot;");
          var imgUrl = isImg
            ? url.replace("action=DownloadFile", "action=InlineFile")
            : url;
          if (isImg) {
            attHtml +=
              '<div class="ann-comment-attachment"><a href="' +
              url +
              '" target="_blank"><img src="' +
              imgUrl +
              '" alt="" class="ann-comment-img" /></a></div>';
          } else {
            attHtml +=
              '<div class="ann-comment-attachment"><a href="' +
              url +
              '" target="_blank" class="ann-comment-file-link">' +
              safeName +
              "</a></div>";
          }
        });
      }
      var commentId = c.id || "";
      var deleteBtn = commentId
        ? '<button type="button" class="ann-comment-delete" data-comment-id="' +
          String(commentId).replace(/"/g, "&quot;") +
          '" title="Delete">×</button>'
        : "";
      list.append(
        '<li class="ann-comment-item">' +
          '<span class="ann-avatar ann-avatar-user ann-avatar-sm">' +
          initial +
          "</span>" +
          '<span class="ann-comment-meta"' +
          (c.timeTitle
            ? ' title="' +
              String(c.timeTitle).replace(/"/g, "&quot;") +
              '"'
            : "") +
          ">" +
          (c.userName || "") +
          " " +
          (c.time || "") +
          "</span>" +
          (deleteBtn
            ? '<span class="ann-comment-meta-actions">' + deleteBtn + "</span>"
            : "") +
          '<div class="ann-comment-text">' +
          text +
          "</div>" +
          (attHtml
            ? '<div class="ann-comment-attachments">' + attHtml + "</div>"
            : "") +
          "</li>"
      );
    },

    _lastCommentsRequestId: 0,
    loadTaskComments: function (container, taskId) {
      if (!taskId) return;
      var requestId = ++this._lastCommentsRequestId;
      var self = this;
      app.request
        .post({
          data: {
            module: "ProjectTask",
            action: "GetComments",
            record: taskId,
            _t: Date.now(),
          },
        })
        .then(function (err, data) {
          if (err) return;
          if (requestId !== self._lastCommentsRequestId) return;
          var res = data && data.result ? data.result : data || {};
          var comments = res.comments || [];
          var list = container.find(".task-comments-list");
          list.empty().data("task-id", taskId);
          jQuery.each(comments, function (i, c) {
            self._appendCommentItem(list, c);
          });
          container.find(".task-comments-badge").text(comments.length);
          self._bindCommentDelete(container);
        });
    },

    _bindCommentDelete: function (container) {
      var list = container.find(".task-comments-list");
      list
        .off("click.anncommentdel")
        .on("click.anncommentdel", ".ann-comment-delete", function (e) {
          e.preventDefault();
          var id = jQuery(this).data("comment-id");
          if (!id) return;
          if (!confirm("Xóa comment này?")) return;
          var taskId = list.data("task-id");
          if (!taskId) return;
          app.request
            .post({
              data: {
                module: "ModComments",
                action: "Delete",
                record: id,
                ajaxDelete: 1,
              },
            })
            .then(function (err) {
              if (err) return;
              Vtiger_List_Js.getInstance().loadTaskComments(container, taskId);
            });
        });
    },

    _subtaskStatusOptions: [
      { value: "Open", label: "Backlog", icon: "backlog" },
      { value: "In Progress", label: "In progress", icon: "inprogress" },
      { value: "Completed", label: "Complete", icon: "complete" },
    ],
    _getSubtaskStatusIcon: function (status) {
      if (!status || status === "Completed") return "complete";
      if (status === "In Progress") return "inprogress";
      return "backlog";
    },

    loadSubtasks: function (container, taskId) {
      var thisInstance = this;
      if (!taskId) return;
      app.request
        .post({
          data: {
            module: "ProjectTask",
            action: "GetSubtasks",
            record: taskId,
          },
        })
        .then(function (err, data) {
          if (err) return;
          var res = data && data.result ? data.result : data || {};
          var subtasks = res.subtasks || [];
          var ul = container.find(".mk-pt-subtasks__list");
          var empty = container.find(".mk-pt-subtasks__empty");
          ul.empty();
          if (!subtasks.length) {
            empty.show();
            return;
          }
          empty.hide();
          var statusIcons = {
            Open: "backlog",
            "In Progress": "inprogress",
            Completed: "complete",
          };
          jQuery.each(subtasks, function (i, st) {
            var completed = st.completed === true || st.completed === "1";
            var icon = statusIcons[st.projecttaskstatus] || "backlog";
            var owner = (st.owner_name || "").substring(0, 2).toUpperCase();
            var statusHtml =
              '<span class="subtask-status-wrap"><button type="button" class="subtask-status-trigger" data-recordid="' +
              (st.recordid || "").replace(/</g, "&lt;") +
              '" aria-expanded="false"><span class="subtask-status-icon ' +
              icon +
              '"></span></button>';
            statusHtml += '<div class="subtask-status-dropdown hidden">';
            jQuery.each(thisInstance._subtaskStatusOptions, function (j, opt) {
              var sel =
                st.projecttaskstatus === opt.value
                  ? " subtask-status-option-selected"
                  : "";
              statusHtml +=
                '<div class="subtask-status-option' +
                sel +
                '" data-value="' +
                (opt.value || "").replace(/"/g, "&quot;") +
                '"><span class="subtask-status-icon ' +
                opt.icon +
                '"></span><span class="subtask-status-label">' +
                (opt.label || "").replace(/</g, "&lt;").replace(/>/g, "&gt;") +
                "</span></div>";
            });
            statusHtml += "</div></span>";
            ul.append(
              '<li class="mk-pt-subtasks__row task-list-row" data-recordid="' +
                (st.recordid || "") +
                '"><span class="task-check-wrap"><input type="checkbox" class="task-checkbox" ' +
                (completed ? "checked" : "") +
                "></span>" +
                statusHtml +
                '<span class="task-title' +
                (completed ? " task-done" : "") +
                '">' +
                (st.name || "").replace(/</g, "&lt;") +
                '</span><span class="task-assignee-wrap"><span class="task-assignee">' +
                owner +
                "</span></span></li>"
            );
          });
        });
    },

    switchTaskTab: function (container, tab) {
      container
        .find(".mk-project-task-detail__nav-tab, .task-detail-tab")
        .removeClass("active")
        .filter('[data-tab="' + tab + '"]')
        .addClass("active");
      container
        .find("#task-panel-comments-list")
        .toggleClass("hide", tab !== "comments");
      container
        .find("#task-panel-history, #task-panel-history-list")
        .toggleClass("hide", tab !== "history");
      if (
        tab === "history" &&
        this._currentTask &&
        this._currentTask.recordid
      ) {
        this.loadTaskHistory(container, this._currentTask.recordid);
      }
    },

    loadTaskHistory: function (container, taskId) {
      var list = container.find(".task-history-list");
      var empty = container.find(".task-history-empty");
      app.request
        .post({
          data: { module: "ProjectTask", action: "GetHistory", record: taskId },
        })
        .then(function (err, data) {
          if (err) return;
          var res = data && data.result ? data.result : data || {};
          var history = res.history || [];
          list.empty();
          if (!history.length) {
            empty.show();
            return;
          }
          empty.hide();
          jQuery.each(history, function (i, h) {
            var initial = (h.userName || "?").charAt(0).toUpperCase();
            var changes = (h.changes || [])
              .map(function (c) {
                return (
                  (c.field || "") +
                  ": " +
                  (c.pre || "-") +
                  " → " +
                  (c.post || "-")
                );
              })
              .join("<br>");
            list.append(
              '<li class="ann-comment-item task-history-item"><span class="ann-avatar ann-avatar-user ann-avatar-sm">' +
                initial +
                '</span><span class="ann-comment-meta">' +
                (h.userName || "") +
                " · " +
                (h.action || "") +
                " · " +
                (h.time || "") +
                '</span><div class="ann-comment-text">' +
                changes +
                "</div></li>"
            );
          });
        });
    },

    saveTaskFromPanel: function () {
      var container = this._taskModalContainer;
      var task = this._currentTask;
      if (!task || !app.request) return;
      var progressVal = container.find(".detail-progress").val();
      if (progressVal !== "" && String(progressVal).indexOf("%") === -1)
        progressVal = progressVal + "%";
      var payload = {
        module: "ProjectTask",
        action: "SaveTask",
        record: task.recordid,
        projecttaskname: task.name || "",
        projectid: task.projectid || "",
        startdate: container.find(".detail-start").val() || "",
        enddate: container.find(".detail-end").val() || "",
        projecttaskstatus: container.find(".detail-status-select").val() || "",
        projecttaskprogress: progressVal,
        assigned_user_id: container.find(".detail-owner-select").val() || "",
        description: container.find(".detail-description").val() || "",
      };
      app.request.post({ data: payload }).then(function (err) {
        if (err) return;
        if (app.helper && app.helper.showSuccessNotification)
          app.helper.showSuccessNotification({ message: "Task updated." });
        jQuery("#projecttask-list-task-overlay").hide();
        Vtiger_List_Js.getInstance().getListViewRecords();
      });
    },

    _ensureEmojiPickerContent: function (container) {
      var picker = container.find(".task-comment-emoji-picker");
      if (picker.data("filled")) return;
      var emojis = [
        "\uD83D\uDE00",
        "\uD83D\uDE0A",
        "\uD83D\uDC4D",
        "\u2764",
        "\uD83D\uDD25",
        "\u2705",
        "\uD83D\uDCCE",
        "\uD83D\uDE0D",
        "\uD83D\uDE02",
        "\uD83D\uDC4F",
        "\uD83D\uDC4C",
        "\uD83D\uDE4C",
        "\u263A",
        "\uD83D\uDE0E",
        "\uD83D\uDE80",
        "\u2B50",
      ];
      var html = "";
      jQuery.each(emojis, function (i, em) {
        html +=
          '<span class="task-emoji-item" data-emoji="' +
          em +
          '">' +
          em +
          "</span>";
      });
      picker.html(html).data("filled", true);
      picker
        .off("click.taskemoji")
        .on("click.taskemoji", ".task-emoji-item", function () {
          var em = jQuery(this).data("emoji");
          var ta = container.find(".task-comment-input")[0];
          if (!ta) return;
          var start = ta.selectionStart,
            end = ta.selectionEnd,
            val = container.find(".task-comment-input").val();
          container
            .find(".task-comment-input")
            .val(val.slice(0, start) + em + val.slice(end));
          ta.selectionStart = ta.selectionEnd = start + em.length;
          ta.focus();
        });
    },

    addCommentFromPanel: function () {
      var thisInstance = this;
      var overlay = jQuery("#projecttask-list-task-overlay");
      var container = overlay.find(".project-task-board").first();
      if (!container.length) container = this._taskModalContainer;
      if (!container || !container.length) return;
      var task = this._currentTask;
      if (!task) return;
      var text = (container.find(".task-comment-input").val() || "").trim();
      var file =
        overlay.data("comment-pending-file") ||
        container.data("comment-pending-file") ||
        (container.find(".task-comment-file-input")[0] &&
          container.find(".task-comment-file-input")[0].files &&
          container.find(".task-comment-file-input")[0].files[0]);
      if (!text && !file) return;

      container.find(".task-comment-input").val("");
      overlay.removeData("comment-pending-file");
      container.removeData("comment-pending-file");
      if (!file) {
        container.find(".task-comment-file-input").val("");
        container.find(".task-comment-file-name").text("").addClass("hidden");
      }

      var taskId = task.recordid;
      function onSuccess(hadFile) {
        if (hadFile) {
          container.find(".task-comment-file-input").val("");
          container.find(".task-comment-file-name").text("").addClass("hidden");
          setTimeout(function () {
            thisInstance.loadTaskComments(container, taskId);
          }, 800);
        } else {
          thisInstance.loadTaskComments(container, taskId);
        }
      }

      if (file) {
        var formData = new FormData();
        formData.append("module", "ModComments");
        formData.append("action", "SaveAjax");
        formData.append("commentcontent", text || " ");
        formData.append("related_to", taskId);
        formData.append("filename", file, file.name || "file");
        jQuery
          .ajax({
            url: "index.php",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            dataType: "json",
          })
          .done(function (data) {
            if (data && data.success === false && data.error) return;
            onSuccess(true);
          })
          .fail(function () {
            onSuccess(true);
          });
      } else {
        if (typeof app !== "undefined" && app.request && app.request.post) {
          app.request
            .post({
              data: {
                module: "ModComments",
                action: "SaveAjax",
                commentcontent: text,
                related_to: taskId,
              },
            })
            .then(function (err) {
              if (err) return;
              onSuccess(false);
            });
        } else {
          jQuery
            .ajax({
              url: "index.php",
              type: "POST",
              data: {
                module: "ModComments",
                action: "SaveAjax",
                commentcontent: text,
                related_to: taskId,
              },
              dataType: "json",
            })
            .done(function (data) {
              if (data && data.success === false && data.error) return;
              onSuccess(false);
            });
        }
      }
    },

    addSubtaskFromPanel: function () {
      var container = this._taskModalContainer;
      var task = this._currentTask;
      if (!task) return;
      var title = container.find(".board-subtask-title-input").val();
      if (!title || !title.trim()) return;
      app.request
        .post({
          data: {
            module: "ProjectTask",
            action: "SaveSubtask",
            parent_record: task.recordid,
            projecttaskname: title,
          },
        })
        .then(function (err) {
          if (err) return;
          container.find(".board-subtask-title-input").val("");
          Vtiger_List_Js.getInstance().loadSubtasks(container, task.recordid);
          if (app.helper && app.helper.showSuccessNotification)
            app.helper.showSuccessNotification({ message: "Created" });
        });
    },
  }
);

/**
 * ProjectTask List (MANAGEMENT): neutralize floatThead / bottom scroller (MkSalesListShared handles footer swap).
 */
(function ($) {
  "use strict";

  function isManagementProjectTaskList() {
    var b = document.body;
    if (!b || b.getAttribute("data-module") !== "ProjectTask" || b.getAttribute("data-view") !== "List") {
      return false;
    }
    return (b.getAttribute("data-app") || "").toUpperCase() === "MANAGEMENT";
  }

  function destroyPerfectScrollbar($tc) {
    if (!$tc || !$tc.length) {
      return;
    }
    try {
      if ($.fn.perfectScrollbar) {
        $tc.perfectScrollbar("destroy");
      }
    } catch (e) {
      /* ignore */
    }
    $tc.removeClass("ps ps--active-x ps--active-y ps--scrolling-x ps--scrolling-y");
    $tc.find(".ps__rail-x, .ps__rail-y, .ps__thumb-x, .ps__thumb-y").remove();
  }

  function fixListScrollContainer() {
    if (!isManagementProjectTaskList()) {
      return;
    }
    var $tc = $("#listViewContent #table-content");
    if (!$tc.length) {
      return;
    }

    destroyPerfectScrollbar($tc);

    $tc.css({
      position: "relative",
      width: "100%",
      height: "auto",
      maxHeight: "",
      overflowX: "auto",
      overflowY: "auto",
      WebkitOverflowScrolling: "touch",
      pointerEvents: "auto",
    });

    $("#listViewContent #scroller_wrapper.bottom-fixed-scroll, #listViewContent .bottom-fixed-scroll").css({
      display: "none",
      height: 0,
      margin: 0,
      padding: 0,
      border: "none",
      overflow: "hidden",
      pointerEvents: "none",
      position: "absolute",
      left: "-9999px",
      width: 0,
    });

    var $table = $("#listViewContent #listview-table");
    if ($table.length && $.fn.floatThead) {
      try {
        $table.floatThead("destroy");
      } catch (e2) {
        /* not initialized */
      }
    }
    $table.removeClass("floatThead-table");
    $(".floatThead-container").remove();
  }

  function patchVtigerListScrollHooks() {
    if (!window.Vtiger_List_Js || !Vtiger_List_Js.prototype || Vtiger_List_Js.prototype.__mkProjectTaskScrollPatched) {
      return !!window.Vtiger_List_Js;
    }
    var proto = Vtiger_List_Js.prototype;
    var origRegister = proto.registerFloatingThead;
    var origReflow = proto.reflowList;

    proto.registerFloatingThead = function () {
      if (isManagementProjectTaskList()) {
        fixListScrollContainer();
        return;
      }
      return origRegister.apply(this, arguments);
    };

    proto.reflowList = function () {
      if (isManagementProjectTaskList()) {
        fixListScrollContainer();
        return;
      }
      return origReflow.apply(this, arguments);
    };

    proto.__mkProjectTaskScrollPatched = true;
    return true;
  }

  function init() {
    if (!isManagementProjectTaskList()) {
      return;
    }
    patchVtigerListScrollHooks();
    fixListScrollContainer();
    setTimeout(fixListScrollContainer, 0);
    setTimeout(fixListScrollContainer, 150);
    $(document).on("mkProjectTaskListPostLoad", function () {
      setTimeout(fixListScrollContainer, 0);
    });
    if (typeof app !== "undefined" && app.event && app.event.on) {
      app.event.on("post.listViewFilter.click", function () {
        setTimeout(fixListScrollContainer, 80);
      });
      app.event.on("Vtiger.Post.MenuToggle", function () {
        setTimeout(fixListScrollContainer, 80);
      });
    }
    $(document).on(
      "click.mkProjectTaskList",
      "#listViewContent #NextPageButton, #listViewContent #PreviousPageButton, #listViewContent #pageToJumpSubmit",
      function () {
        setTimeout(fixListScrollContainer, 120);
      }
    );
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})(jQuery);
