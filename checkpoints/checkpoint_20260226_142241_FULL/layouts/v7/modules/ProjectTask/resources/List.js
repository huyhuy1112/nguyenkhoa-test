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
      container.find(".board-subtasks-block .task-list").empty();
      container.find(".board-subtasks-block .task-list-empty").show();
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
        '<div class="projecttask-list-task-overlay" id="projecttask-list-task-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:9999;overflow:auto;">' +
        '<div class="project-task-board projecttask-list-task-board" style="max-width:920px;margin:24px auto;background:#fff;border-radius:10px;box-shadow:0 10px 40px rgba(0,0,0,0.2);min-height:400px;">' +
        '<div class="task-detail-modal">' +
        '<div class="task-detail-dialog">' +
        '<div class="task-detail-header">' +
        '<div class="header-left"><span class="status-pill detail-status">--</span><span class="detail-id"></span></div>' +
        '<div class="header-right">' +
        '<button type="button" class="tab-btn active">Comments</button>' +
        '<button type="button" class="tab-btn">Task history</button>' +
        '<span class="panel-close">&times;</span></div></div>' +
        '<div class="task-detail-content">' +
        '<div class="task-detail-left">' +
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
        '<div class="detail-row"><a class="detail-link" href="javascript:void(0)">Add field</a> <span>or</span> <a class="detail-link" href="javascript:void(0)">Manage fields</a></div></div>' +
        '<div class="detail-subtasks board-subtasks-block"><div class="section-label">Subtasks</div>' +
        '<div class="tasksListToolbar"><input type="text" class="form-control board-subtask-title-input quickAddTaskInput" placeholder="Add task and hit enter/return key">' +
        '<button type="button" class="btn btn-primary btn-sm board-subtask-save-btn">Save</button></div>' +
        '<div class="task-list-container"><div class="task-list-empty text-muted">No subtasks exist in this task</div><ul class="task-list list-unstyled"></ul></div></div></div>' +
        '<div class="task-detail-right">' +
        '<div class="ann-detail-tabs"><button type="button" class="ann-tab task-detail-tab active" data-tab="comments">Comments <span class="badge task-comments-badge">0</span></button>' +
        '<button type="button" class="ann-tab task-detail-tab" data-tab="history">Task history</button></div>' +
        '<div id="task-panel-comments-list" class="ann-detail-panel task-detail-panel"><ul class="ann-comments-list list-unstyled task-comments-list"></ul>' +
        '<div class="ann-add-comment">' +
        '<div class="task-comment-toolbar"><button type="button" class="btn btn-default btn-xs task-comment-emoji-btn" title="Emoji">&#128512;</button>' +
        '<button type="button" class="btn btn-default btn-xs task-comment-upload-btn" title="Upload from computer"><span class="fa fa-paperclip"></span> Upload</button>' +
        '<input type="file" class="task-comment-file-input" accept="*" style="display:none">' +
        '<span class="task-comment-file-name text-muted small"></span></div>' +
        '<div class="task-comment-emoji-picker hidden"></div>' +
        '<textarea class="form-control task-comment-input" rows="2" placeholder="Write a comment"></textarea>' +
        '<button type="button" class="btn btn-primary btn-sm task-comment-add">Add</button></div></div>' +
        '<div id="task-panel-history-list" class="ann-detail-panel task-detail-panel hide"><ul class="task-history-list list-unstyled"></ul><div class="task-history-empty text-muted small">No history yet.</div></div></div></div>' +
        '<div class="task-detail-footer"><button type="button" class="btn btn-primary detail-save">Save</button><button type="button" class="btn btn-default detail-cancel">Cancel</button></div></div></div></div></div>';

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

      container.find(".task-detail-tab").on("click", function () {
        var tab = jQuery(this).data("tab");
        if (tab) thisInstance.switchTaskTab(container, tab);
      });

      container
        .find(".board-subtasks-block .task-list")
        .on("click", function (e) {
          var row = jQuery(e.target).closest(".task-list-row");
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
      container.find(".detail-status").text(task.projecttaskstatus || "--");
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
          '<span class="ann-comment-meta">' +
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
          var ul = container.find(".board-subtasks-block .task-list");
          var empty = container.find(".board-subtasks-block .task-list-empty");
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
              '<li class="task-list-row" data-recordid="' +
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
        .find(".task-detail-tab")
        .removeClass("active")
        .filter("[data-tab=" + tab + "]")
        .addClass("active");
      container
        .find("#task-panel-comments-list")
        .toggleClass("hide", tab !== "comments");
      container
        .find("#task-panel-history-list")
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
