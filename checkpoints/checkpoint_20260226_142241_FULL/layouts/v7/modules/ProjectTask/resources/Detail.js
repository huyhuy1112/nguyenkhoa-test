/*+***********************************************************************************
 * ProjectTask Detail - Subtasks (taskListPanel, task-list-row) + subtask header Complete
 *************************************************************************************/

Vtiger_Detail_Js(
  "ProjectTask_Detail_Js",
  {},
  {
    init: function () {
      this._super();
      this.registerTaskListEvents();
      this.registerSubtaskHeaderEvents();
      this.loadTaskList();
    },

    registerSubtaskHeaderEvents: function () {
      var self = this;
      var header = jQuery(".projecttask-subtask-header");
      if (!header.length) return;
      var cb = header.find(".subtask-complete-checkbox");
      cb.on("change", function () {
        var recordId = self.getRecordId();
        var checked = jQuery(this).is(":checked");
        if (recordId) self.toggleTaskComplete(recordId, checked);
        header.find(".taskComplete").toggleClass("checked", checked);
      });
      header
        .find(".taskCompleteTrigger, .taskCheckBox, .taskComplete")
        .on("click", function (e) {
          e.preventDefault();
          var newVal = !cb.is(":checked");
          cb.prop("checked", newVal).trigger("change");
        });
    },

    registerTaskListEvents: function () {
      var self = this;
      var panel = jQuery(".taskListPanel");
      if (!panel.length) return;

      panel.find(".quickAddTaskInput").on("keydown", function (e) {
        if (e.keyCode === 13) {
          e.preventDefault();
          self.addTaskFromList();
        }
      });
      panel.find(".taskListAddBtn").on("click", function () {
        self.addTaskFromList();
      });
      panel.find(".taskListCancelBtn").on("click", function () {
        panel.find(".quickAddTaskInput").val("");
      });
      panel.on("click", ".task-list-row .task-checkbox", function (e) {
        e.stopPropagation();
        var recordId = jQuery(this).closest(".task-list-row").data("recordid");
        var checked = jQuery(this).is(":checked");
        self.toggleTaskComplete(recordId, checked);
      });
      panel.on("click", ".task-list-row", function (e) {
        if (
          jQuery(e.target).closest(".task-checkbox, .task-add-assignee").length
        )
          return;
        var recordId = jQuery(this).data("recordid");
        if (recordId) self.openTaskDetail(recordId);
      });
    },

    loadTaskList: function () {
      var self = this;
      var panel = jQuery(".taskListPanel");
      var recordId = this.getRecordId();
      if (!panel.length || !recordId) return;

      app.request
        .post({
          data: {
            module: "ProjectTask",
            action: "GetSubtasks",
            record: recordId,
          },
        })
        .then(function (err, data) {
          if (err) return;
          var res = data && data.result ? data.result : data || {};
          var list = res.subtasks || [];
          self.renderTaskList(list);
        });
    },

    renderTaskList: function (subtasks) {
      var panel = jQuery(".taskListPanel");
      if (!panel.length) return;
      var listEl = panel.find(".task-list");
      var emptyEl = panel.find(".task-list-empty");
      listEl.empty();

      if (!subtasks || subtasks.length === 0) {
        emptyEl.show();
        return;
      }
      emptyEl.hide();

      subtasks.forEach(function (st) {
        var completed = st.completed === true || st.completed === "1";
        var duration = st.duration || st.projecttaskhours || "";
        var owner = st.owner_name || "";
        var item = jQuery(
          '<li class="task-list-row" data-recordid="' +
            st.recordid +
            '">' +
            '<span class="task-check-wrap"><input type="checkbox" class="task-checkbox" ' +
            (completed ? "checked" : "") +
            " /></span>" +
            '<span class="task-title' +
            (completed ? " task-done" : "") +
            '">' +
            (st.name || "").replace(/</g, "&lt;").replace(/>/g, "&gt;") +
            "</span>" +
            (duration
              ? '<span class="task-duration">' + duration + "</span>"
              : "") +
            '<span class="task-assignee-wrap"><span class="task-assignee">' +
            (owner ? owner.substring(0, 2).toUpperCase() : "") +
            '</span><i class="fa fa-plus task-add-assignee"></i></span>' +
            "</li>"
        );
        listEl.append(item);
      });
    },

    addTaskFromList: function () {
      var self = this;
      var panel = jQuery(".taskListPanel");
      var title = jQuery.trim(panel.find(".quickAddTaskInput").val());
      if (!title) return;

      var recordId = this.getRecordId();
      var btn = panel.find(".taskListAddBtn");
      btn.prop("disabled", true);

      app.request
        .post({
          data: {
            module: "ProjectTask",
            action: "SaveSubtask",
            parent_record: recordId,
            projecttaskname: title,
            description: "",
          },
        })
        .then(function (err, data) {
          btn.prop("disabled", false);
          if (err) {
            if (app.helper && app.helper.showAlert)
              app.helper.showAlert({ title: "Error", text: err.message });
            return;
          }
          panel.find(".quickAddTaskInput").val("");
          self.loadTaskList();
          if (app.helper && app.helper.showSuccessNotification) {
            app.helper.showSuccessNotification({
              message: app.vtranslate("JS_RECORD_CREATED") || "Created",
            });
          }
        });
    },

    openTaskDetail: function (recordId) {
      window.location.href =
        "index.php?module=ProjectTask&view=Detail&record=" + recordId;
    },

    toggleTaskComplete: function (recordId, completed) {
      var self = this;
      var status = completed ? "Completed" : "Open";
      var progress = completed ? "100%" : "0%";

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
              var row = jQuery(
                '.task-list-row[data-recordid="' + recordId + '"]'
              );
              row.find(".task-title").toggleClass("task-done", completed);
            });
        });
    },
  }
);
