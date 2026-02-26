/*+***********************************************************************************
 * Project Team Group Assignment Handler
 * Captures team group selection and sends _team_group_id field
 *************************************************************************************/

// Global interceptor - runs immediately, before DOM ready
(function () {
  // Prevent missing perf markers from breaking page
  if (typeof window._PAGEREADYAT === "undefined") {
    window._PAGEREADYAT = new Date();
  }

  // Intercept ALL AJAX requests globally (before jQuery ready)
  if (typeof jQuery !== "undefined") {
    var originalAjax = jQuery.ajax;
    jQuery.ajax = function (options) {
      // Chỉ xử lý khi data là object (JSON). Không parse string form (key=value&...) để tránh lỗi save.
      if (options && options.data && typeof options.data === "object") {
        var data = options.data;
        var isProjectSave =
          (data.module === "Project" || data.module === "ProjectTask") &&
          (data.action === "SaveAjax" || data.action === "Save");
        if (isProjectSave) {
          var teamGroupId = null;
          if (data.assigned_user_id) {
            var intVal = parseInt(data.assigned_user_id, 10);
            if (intVal < 0) teamGroupId = Math.abs(intVal);
          }
          if (
            teamGroupId == null &&
            data.field === "assigned_user_id" &&
            data.value
          ) {
            var v = parseInt(data.value, 10);
            if (v < 0) teamGroupId = Math.abs(v);
          }
          if (teamGroupId != null) {
            data._team_group_id = teamGroupId;
          }
        }
      }
      return originalAjax.apply(this, arguments);
    };
  }
})();

jQuery(document).ready(function ($) {
  console.log("[ProjectTeamGroup] Script loaded");

  // Only run for Project module
  var currentModule = typeof app !== "undefined" ? app.module() : "";
  console.log("[ProjectTeamGroup] Current module:", currentModule);

  // Also check URL for Project module
  if (currentModule !== "Project") {
    var url = window.location.href;
    if (
      url.indexOf("module=Project") !== -1 ||
      url.indexOf("module/Project") !== -1
    ) {
      currentModule = "Project";
      console.log("[ProjectTeamGroup] Project module detected from URL");
    }
  }

  if (currentModule === "Project") {
    console.log("[ProjectTeamGroup] Project module detected, initializing...");

    // Function to handle assigned_user_id change
    function handleAssignedUserChange() {
      var $assignedField = $('select[name="assigned_user_id"]');
      console.log(
        "[ProjectTeamGroup] Looking for assigned_user_id field, found:",
        $assignedField.length
      );

      if ($assignedField.length === 0) {
        // Try alternative selectors
        $assignedField = $('select[data-name="assigned_user_id"]');
        console.log(
          "[ProjectTeamGroup] Trying alternative selector, found:",
          $assignedField.length
        );
      }

      if ($assignedField.length === 0) {
        console.log("[ProjectTeamGroup] assigned_user_id field not found");
        return;
      }

      // Remove existing hidden field
      $('input[name="_team_group_id"]').remove();

      // Get selected value
      var selectedValue = $assignedField.val();
      console.log(
        "[ProjectTeamGroup] Selected value:",
        selectedValue,
        "Type:",
        typeof selectedValue
      );

      // Also check all options to see if any have negative values
      var foundNegativeOptions = [];
      var allOptions = [];
      $assignedField.find("option").each(function () {
        var optValue = $(this).val();
        var optText = $(this).text();
        allOptions.push({ value: optValue, text: optText });
        if (optValue && parseInt(optValue) < 0) {
          foundNegativeOptions.push({ value: optValue, text: optText });
          console.log(
            "[ProjectTeamGroup] ✅ Found negative option value:",
            optValue,
            "Text:",
            optText
          );
        }
      });

      if (foundNegativeOptions.length === 0) {
        console.log(
          "[ProjectTeamGroup] ⚠️ NO NEGATIVE OPTIONS FOUND IN DROPDOWN!"
        );
        console.log("[ProjectTeamGroup] All options:", allOptions);
        console.log(
          "[ProjectTeamGroup] This means team groups are NOT in the dropdown or have positive IDs"
        );
      } else {
        console.log(
          "[ProjectTeamGroup] ✅ Found",
          foundNegativeOptions.length,
          "team group(s) in dropdown:",
          foundNegativeOptions
        );
      }

      // Check if it's a negative ID (team group)
      // Team groups have negative IDs (e.g., -1, -2, etc.)
      if (selectedValue) {
        var intValue = parseInt(selectedValue);
        console.log("[ProjectTeamGroup] Parsed integer value:", intValue);

        if (intValue < 0) {
          var teamGroupId = Math.abs(intValue);

          // Add hidden field to store team group ID
          var $hiddenField = $("<input>", {
            type: "hidden",
            name: "_team_group_id",
            value: teamGroupId,
          });

          $assignedField.after($hiddenField);

          console.log(
            "[ProjectTeamGroup] ✅ Detected team group ID:",
            teamGroupId
          );
          console.log(
            "[ProjectTeamGroup] Hidden field added:",
            $hiddenField[0]
          );
        } else {
          console.log(
            "[ProjectTeamGroup] ⚠️ Positive ID (not a team group):",
            intValue
          );
        }
      } else {
        console.log("[ProjectTeamGroup] ⚠️ No value selected");
      }
    }

    // Listen for change event on assigned_user_id field (multiple selectors for compatibility)
    $(document).on(
      "change",
      'select[name="assigned_user_id"], select[data-name="assigned_user_id"], select[name="assigned_user_id[]"]',
      function () {
        console.log(
          "[ProjectTeamGroup] Change event triggered on assigned_user_id"
        );
        handleAssignedUserChange();
      }
    );

    // Also listen for input event (for programmatic changes)
    $(document).on(
      "input",
      'select[name="assigned_user_id"], select[data-name="assigned_user_id"]',
      function () {
        console.log(
          "[ProjectTeamGroup] Input event triggered on assigned_user_id"
        );
        setTimeout(handleAssignedUserChange, 100);
      }
    );

    // Also check on form load (for edit mode) - with multiple attempts
    var checkAttempts = 0;
    var checkInterval = setInterval(function () {
      checkAttempts++;
      var $assignedField = $(
        'select[name="assigned_user_id"], select[data-name="assigned_user_id"]'
      );
      if ($assignedField.length > 0 || checkAttempts >= 20) {
        clearInterval(checkInterval);
        if ($assignedField.length > 0) {
          console.log(
            "[ProjectTeamGroup] Form loaded, checking assigned_user_id"
          );
          handleAssignedUserChange();

          // Also check if current value is negative (for edit mode with existing team group)
          var currentValue = $assignedField.val();
          if (currentValue && parseInt(currentValue) < 0) {
            console.log(
              "[ProjectTeamGroup] ✅ Edit mode: Current value is negative (team group):",
              currentValue
            );
          }
        } else {
          console.log(
            "[ProjectTeamGroup] ⚠️ Form loaded but assigned_user_id field not found after",
            checkAttempts,
            "attempts"
          );
        }
      }
    }, 500);

    // Intercept form submit to ensure _team_group_id is included
    $(document).on(
      "submit",
      'form[name="EditView"], form[name="QuickCreate"], form.recordEditView',
      function (e) {
        console.log("[ProjectTeamGroup] 🔵 Form submit detected");
        var $assignedField = $(
          'select[name="assigned_user_id"], select[data-name="assigned_user_id"]'
        );
        console.log(
          "[ProjectTeamGroup] Form submit - assigned field found:",
          $assignedField.length
        );

        if ($assignedField.length > 0) {
          var selectedValue = $assignedField.val();
          console.log(
            "[ProjectTeamGroup] Form submit - selected value:",
            selectedValue
          );

          if (selectedValue) {
            var intValue = parseInt(selectedValue);
            if (intValue < 0) {
              var teamGroupId = Math.abs(intValue);

              // Remove existing hidden field and create new one
              $('input[name="_team_group_id"]').remove();
              var $hiddenField = $("<input>", {
                type: "hidden",
                name: "_team_group_id",
                value: teamGroupId,
              });
              $(this).append($hiddenField);
              console.log(
                "[ProjectTeamGroup] ✅ Added _team_group_id on submit:",
                teamGroupId
              );

              // Also log form data
              var formData = $(this).serialize();
              console.log(
                "[ProjectTeamGroup] Form data includes _team_group_id:",
                formData.indexOf("_team_group_id") !== -1
              );
              console.log("[ProjectTeamGroup] Full form data:", formData);
            } else {
              console.log(
                "[ProjectTeamGroup] ⚠️ Form submit - positive ID, not a team group"
              );
              // Remove hidden field if exists (switched to single user)
              $('input[name="_team_group_id"]').remove();
            }
          }
        } else {
          console.log(
            "[ProjectTeamGroup] ⚠️ Form submit - assigned_user_id field not found"
          );
        }
      }
    );

    // Also intercept AJAX save (Vtiger uses AJAX for saves)
    $(document).on(
      "click",
      'button[type="submit"], .saveButton, button.save',
      function () {
        console.log("[ProjectTeamGroup] Save button clicked");
        setTimeout(function () {
          handleAssignedUserChange();
        }, 100);
      }
    );

    // Intercept AJAX requests for SaveAjax (inline edit and full form save)
    if (typeof app !== "undefined" && app.request) {
      // Intercept app.request.post
      var originalPost = app.request.post;
      if (originalPost) {
        app.request.post = function (options) {
          if (options && options.data) {
            var data = options.data;

            // Check if this is a SaveAjax request for Project or ProjectTask
            if (
              (data.module === "Project" || data.module === "ProjectTask") &&
              (data.action === "SaveAjax" || data.action === "Save")
            ) {
              console.log(
                "[ProjectTeamGroup] 🔵 Intercepting SaveAjax/Save request"
              );
              console.log(
                "[ProjectTeamGroup] Original data:",
                JSON.stringify(data)
              );

              // PRIORITY 1: Check if _team_group_id is already in form (hidden field)
              var $hiddenField = $('input[name="_team_group_id"]');
              if ($hiddenField.length > 0) {
                var hiddenValue = $hiddenField.val();
                if (hiddenValue) {
                  data._team_group_id = hiddenValue;
                  console.log(
                    "[ProjectTeamGroup] ✅ Added _team_group_id from hidden field:",
                    hiddenValue
                  );
                }
              }

              // PRIORITY 2: Check current selected value in dropdown (may have changed)
              var $assignedField = $(
                'select[name="assigned_user_id"], select[data-name="assigned_user_id"]'
              );
              if ($assignedField.length > 0) {
                var currentSelectedValue = $assignedField.val();
                console.log(
                  "[ProjectTeamGroup] Current selected value in dropdown:",
                  currentSelectedValue
                );

                if (currentSelectedValue) {
                  var intValue = parseInt(currentSelectedValue);
                  if (intValue < 0) {
                    var teamGroupId = Math.abs(intValue);
                    data._team_group_id = teamGroupId;
                    console.log(
                      "[ProjectTeamGroup] ✅ Added _team_group_id from current dropdown selection:",
                      teamGroupId
                    );

                    // Also ensure hidden field exists
                    if ($hiddenField.length === 0) {
                      var $newHidden = $("<input>", {
                        type: "hidden",
                        name: "_team_group_id",
                        value: teamGroupId,
                      });
                      $assignedField.after($newHidden);
                      console.log(
                        "[ProjectTeamGroup] ✅ Created hidden field with team group ID:",
                        teamGroupId
                      );
                    }
                  }
                }
              }

              // PRIORITY 3: Check if assigned_user_id in data is negative
              if (data.assigned_user_id && !data._team_group_id) {
                var assignedUserId = data.assigned_user_id;
                var intValue = parseInt(assignedUserId);

                if (intValue < 0) {
                  var teamGroupId = Math.abs(intValue);
                  data._team_group_id = teamGroupId;
                  console.log(
                    "[ProjectTeamGroup] ✅ Added _team_group_id from data.assigned_user_id:",
                    teamGroupId
                  );
                } else {
                  console.log(
                    "[ProjectTeamGroup] ⚠️ assigned_user_id in data is positive:",
                    intValue
                  );
                }
              }

              // PRIORITY 4: Check if field is assigned_user_id (for inline edit)
              if (
                data.field === "assigned_user_id" &&
                data.value &&
                !data._team_group_id
              ) {
                var fieldValue = parseInt(data.value);
                if (fieldValue < 0) {
                  var teamGroupId = Math.abs(fieldValue);
                  data._team_group_id = teamGroupId;
                  console.log(
                    "[ProjectTeamGroup] ✅ Added _team_group_id from inline edit:",
                    teamGroupId
                  );
                }
              }

              console.log(
                "[ProjectTeamGroup] 🔵 Modified data:",
                JSON.stringify(data)
              );
            }
          }

          // Call original function
          return originalPost.call(this, options);
        };
      }

      // Also intercept saveFieldValues for inline edit
      if (
        typeof Vtiger_Detail_Js !== "undefined" &&
        Vtiger_Detail_Js.prototype
      ) {
        var originalSaveFieldValues =
          Vtiger_Detail_Js.prototype.saveFieldValues;
        if (originalSaveFieldValues) {
          Vtiger_Detail_Js.prototype.saveFieldValues = function (
            fieldDetailList
          ) {
            // Check if this is assigned_user_id field
            if (
              fieldDetailList &&
              fieldDetailList.field === "assigned_user_id" &&
              fieldDetailList.value
            ) {
              var fieldValue = parseInt(fieldDetailList.value);
              if (fieldValue < 0) {
                var teamGroupId = Math.abs(fieldValue);
                fieldDetailList._team_group_id = teamGroupId;
                console.log(
                  "[ProjectTeamGroup] ✅ Added _team_group_id to saveFieldValues:",
                  teamGroupId
                );
              }
            }

            // Call original function
            return originalSaveFieldValues.call(this, fieldDetailList);
          };
        }
      } else {
        console.log(
          "[ProjectTeamGroup] Vtiger_Detail_Js not available, skip inline edit hook"
        );
      }
    }

    // Also intercept inline edit save (Detail view)
    $(document).on("click", ".inlineAjaxSave", function () {
      console.log("[ProjectTeamGroup] Inline edit save clicked");
      setTimeout(function () {
        handleAssignedUserChange();
      }, 100);
    });
  } else {
    console.log("[ProjectTeamGroup] Not Project/ProjectTask module, skipping");
  }
});
