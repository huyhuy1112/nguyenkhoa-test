/*+**********************************************************************************
 * Schedule Quick Create Enhancements
 * Google Calendar-like UX with 15-minute intervals, All Day toggle, duration calculation
 *************************************************************************************/

(function ($) {
  "use strict";

  var ScheduleQuickCreate = {
    initialized: false,

    init: function () {
      // Prevent multiple initializations
      if (this.initialized) return;

      var $container = $("#QuickCreate");
      if ($container.length === 0) return;

      // Check if this is Schedule module
      var moduleName = $container.find('input[name="module"]').val();
      if (moduleName !== "Schedule") return;

      // Check if form is ready
      if ($container.find('input[name="time_start"]').length === 0) return;

      this.enhanceTimePickers();
      this.setupAllDayToggle();
      this.setupDurationDisplay();
      this.setupTimeSync();
      this.setupRepeatSync();

      this.initialized = true;
    },

    /** Đồng bộ dropdown Repeat với hidden recurringtype (server đọc name="recurringtype") */
    setupRepeatSync: function () {
      var $container = $("#QuickCreate");
      var $repeatSelect = $container.find(
        "#schedule_repeat_type, #calendar_repeat_type"
      );
      var $hiddenInput = $container.find(
        "#schedule_recurringtype_hidden, #calendar_recurringtype_hidden"
      );
      if ($repeatSelect.length && $hiddenInput.length) {
        $repeatSelect.on("change", function () {
          $hiddenInput.val($(this).val() || "");
        });
        $hiddenInput.val($repeatSelect.val() || "");
      }
    },

    reset: function () {
      this.initialized = false;
    },

    /**
     * Enhance time pickers: 15-minute intervals, manual typing
     */
    enhanceTimePickers: function () {
      var $container = $("#QuickCreate");
      if ($container.length === 0) return;

      // Re-initialize time fields with 15-minute step after Vtiger's initialization
      setTimeout(function () {
        var $timeFields = $container.find(
          'input[name="time_start"], input[name="time_end"]'
        );

        $timeFields.each(function () {
          var $field = $(this);

          // Destroy existing timepicker
          if ($field.data("timepicker-list")) {
            $field.timepicker("remove");
          }

          var timeFormat = $field.data("format");
          if (timeFormat == "24") {
            timeFormat = "H:i";
          } else {
            timeFormat = "h:i A";
          }

          // Re-initialize with 15-minute step
          $field.timepicker({
            timeFormat: timeFormat,
            step: 15, // 15-minute intervals
            disableTextInput: false, // Allow manual typing
            className: "timePicker schedule-enhanced-timepicker",
            change: function (time) {
              // Trigger duration update when time changes
              ScheduleQuickCreate.updateDuration();
            },
          });

          // Handle manual typing with validation
          $field.on("blur", function () {
            var input = $(this).val();
            if (input && !ScheduleQuickCreate.parseTimeInput(input)) {
              // Invalid format - try to parse and fix
              var parsed = ScheduleQuickCreate.parseAndFixTime(input);
              if (parsed) {
                $(this).val(parsed);
              }
            }
            ScheduleQuickCreate.updateDuration();
          });
        });
      }, 400);
    },

    /**
     * Parse and validate manual time input
     */
    parseTimeInput: function (input) {
      if (!input) return null;
      input = input.trim();

      // Try 24-hour format (HH:MM)
      var match24 = input.match(/^([0-1]?[0-9]|2[0-3]):([0-5][0-9])$/);
      if (match24) {
        return input; // Already valid
      }

      // Try 12-hour format (h:MM AM/PM)
      var match12 = input.match(/^([0-9]|1[0-2]):([0-5][0-9])\s?(AM|PM)$/i);
      if (match12) {
        return input; // Already valid
      }

      return null;
    },

    /**
     * Parse and fix common time input mistakes
     */
    parseAndFixTime: function (input) {
      if (!input) return null;
      input = input.trim();

      // Try to fix missing colon (e.g., "830" -> "8:30")
      var matchNoColon = input.match(
        /^([0-9]{1,2})([0-5][0-9])(\s?(AM|PM))?$/i
      );
      if (matchNoColon) {
        var hour = matchNoColon[1];
        var minute = matchNoColon[2];
        var ampm = matchNoColon[4] || "";
        return hour + ":" + minute + (ampm ? " " + ampm.toUpperCase() : "");
      }

      // Try to fix single digit hour (e.g., "8:30" -> "08:30" for 24h format)
      var matchSingle = input.match(/^([0-9]):([0-5][0-9])$/);
      if (matchSingle) {
        return "0" + input; // Add leading zero
      }

      return null;
    },

    /**
     * Setup All Day event toggle
     */
    setupAllDayToggle: function () {
      var $container = $("#QuickCreate");
      if ($container.length === 0) return;

      var $allDayCheckbox = $container.find(
        'input[name="allday"], input[data-schedule-allday="1"]'
      );
      if ($allDayCheckbox.length === 0) return;

      var $timeStartContainer = $container.find(".schedule-time-field").first();
      var $timeEndContainer = $container.find(".schedule-time-field").last();

      $allDayCheckbox
        .off("change.schedule-allday")
        .on("change.schedule-allday", function () {
          var isAllDay = $(this).is(":checked");
          var $timeStart = $container.find('input[name="time_start"]');
          var $timeEnd = $container.find('input[name="time_end"]');

          if (isAllDay) {
            // Hide time fields
            $timeStartContainer.hide();
            $timeEndContainer.hide();

            // Store previous values
            $timeStart.data("previous-value", $timeStart.val());
            $timeEnd.data("previous-value", $timeEnd.val());

            // Clear time values (all day events don't need times)
            $timeStart.val("");
            $timeEnd.val("");

            // Update duration display
            ScheduleQuickCreate.updateDuration();
          } else {
            // Show time fields
            $timeStartContainer.show();
            $timeEndContainer.show();

            // Restore previous values if available
            var prevStart = $timeStart.data("previous-value");
            var prevEnd = $timeEnd.data("previous-value");
            if (prevStart) $timeStart.val(prevStart);
            if (prevEnd) $timeEnd.val(prevEnd);

            // Update duration display
            ScheduleQuickCreate.updateDuration();
          }
        });
    },

    /**
     * Setup duration display and calculation
     */
    setupDurationDisplay: function () {
      var $container = $("#QuickCreate");
      if ($container.length === 0) return;

      // Update summary line (e.g. "Tuesday, February 3 4:23am") and/or duration
      $container
        .find(
          'input[name="date_start"], input[name="due_date"], input[name="time_start"], input[name="time_end"]'
        )
        .on("change blur", function () {
          ScheduleQuickCreate.updateDateTimeSummary();
          ScheduleQuickCreate.updateDuration();
        });

      setTimeout(function () {
        ScheduleQuickCreate.updateDateTimeSummary();
        ScheduleQuickCreate.updateDuration();
      }, 500);
    },

    /** Cập nhật dòng tóm tắt ngày giờ (Tuesday, February 3 4:23am) */
    updateDateTimeSummary: function () {
      var $container = $("#QuickCreate");
      var $summary = $container.find(".calendar-qc-datetime-summary");
      if ($summary.length === 0) return;

      var $dateStart = $container.find('input[name="date_start"]');
      var $timeStart = $container.find('input[name="time_start"]');
      var dateStr = $dateStart.val();
      var timeStr = $timeStart ? $timeStart.val() : "";

      if (!dateStr) {
        $summary.text("—");
        return;
      }

      try {
        var d = app.dateConvertToUserFormat(dateStr);
        if (typeof moment !== "undefined") {
          var m = moment(dateStr, ["YYYY-MM-DD", "DD-MM-YYYY", "MM-DD-YYYY"]);
          if (timeStr) {
            var parts = timeStr.match(/(\d{1,2}):(\d{2})\s*(AM|PM)?/i);
            if (parts) {
              var h = parseInt(parts[1], 10);
              var min = parts[2];
              if (parts[3] && parts[3].toUpperCase() === "PM" && h !== 12)
                h += 12;
              if (parts[3] && parts[3].toUpperCase() === "AM" && h === 12)
                h = 0;
              m.hour(h).minute(parseInt(min, 10));
            }
          }
          $summary.text(m.format("dddd, MMMM D h:mm a"));
        } else {
          $summary.text(dateStr + (timeStr ? " " + timeStr : ""));
        }
      } catch (e) {
        $summary.text(dateStr + (timeStr ? " " + timeStr : ""));
      }
    },

    /**
     * Calculate and display duration
     */
    updateDuration: function () {
      var $container = $("#QuickCreate");
      if ($container.length === 0) return;

      // Form mới: #schedule-duration-display chứa hint All day, không ghi đè
      var $durationDisplay = $container.find(".schedule-duration-value");
      if ($durationDisplay.length === 0)
        $durationDisplay = $("#schedule-duration-display");
      if ($durationDisplay.length === 0) return;
      if (
        $container.find(".calendar-qc-datetime-summary").length > 0 &&
        $durationDisplay.attr("id") === "schedule-duration-display"
      )
        return;

      var $allDayCheckbox = $container.find(
        'input[name="allday"], input[data-schedule-allday="1"]'
      );
      var isAllDay = $allDayCheckbox.is(":checked");

      var $dateStart = $container.find('input[name="date_start"]');
      var $dateEnd = $container.find('input[name="due_date"]');
      var $timeStart = $container.find('input[name="time_start"]');
      var $timeEnd = $container.find('input[name="time_end"]');

      if (!$dateStart.length || !$dateEnd.length) return;

      var startDateStr = $dateStart.val();
      var endDateStr = $dateEnd.val();

      if (!startDateStr || !endDateStr) {
        $durationDisplay.text("");
        return;
      }

      try {
        var startDate = app.dateConvertToUserFormat(startDateStr);
        var endDate = app.dateConvertToUserFormat(endDateStr);
        var startTime = null,
          endTime = null;
        if (!isAllDay) {
          var startTimeStr = $timeStart.val();
          var endTimeStr = $timeEnd.val();
          if (startTimeStr)
            startTime = ScheduleQuickCreate.parseTimeToMinutes(startTimeStr);
          if (endTimeStr)
            endTime = ScheduleQuickCreate.parseTimeToMinutes(endTimeStr);
        }
        var duration = ScheduleQuickCreate.calculateDuration(
          startDate,
          endDate,
          startTime,
          endTime,
          isAllDay
        );
        $durationDisplay.text(duration);
      } catch (e) {
        $durationDisplay.text("");
      }
    },

    /**
     * Parse time string to minutes since midnight
     */
    parseTimeToMinutes: function (timeStr) {
      if (!timeStr) return null;
      timeStr = timeStr.trim();

      // Try 24-hour format
      var match24 = timeStr.match(/^([0-1]?[0-9]|2[0-3]):([0-5][0-9])$/);
      if (match24) {
        return parseInt(match24[1]) * 60 + parseInt(match24[2]);
      }

      // Try 12-hour format
      var match12 = timeStr.match(/^([0-9]|1[0-2]):([0-5][0-9])\s?(AM|PM)$/i);
      if (match12) {
        var hour = parseInt(match12[1]);
        var minute = parseInt(match12[2]);
        var ampm = match12[3].toUpperCase();
        if (ampm === "PM" && hour !== 12) hour += 12;
        if (ampm === "AM" && hour === 12) hour = 0;
        return hour * 60 + minute;
      }

      return null;
    },

    /**
     * Calculate duration between start and end
     */
    calculateDuration: function (
      startDate,
      endDate,
      startTime,
      endTime,
      isAllDay
    ) {
      // Parse dates
      var start = new Date(startDate);
      var end = new Date(endDate);

      if (!isAllDay && startTime !== null && endTime !== null) {
        // Add time to dates
        start.setHours(Math.floor(startTime / 60), startTime % 60, 0, 0);
        end.setHours(Math.floor(endTime / 60), endTime % 60, 0, 0);
      } else if (!isAllDay && startTime !== null) {
        // Only start time available
        start.setHours(Math.floor(startTime / 60), startTime % 60, 0, 0);
        // Default end time to 1 hour later
        end.setHours(Math.floor(startTime / 60) + 1, startTime % 60, 0, 0);
      } else {
        // All day or no times - set to start/end of day
        start.setHours(0, 0, 0, 0);
        end.setHours(23, 59, 59, 999);
      }

      var diffMs = end - start;
      var diffMins = Math.floor(diffMs / (1000 * 60));
      var diffHours = Math.floor(diffMins / 60);
      var diffDays = Math.floor(diffHours / 24);

      // Format duration
      if (diffDays > 0) {
        var remainingHours = diffHours % 24;
        if (remainingHours > 0) {
          return (
            diffDays +
            " day" +
            (diffDays > 1 ? "s" : "") +
            ", " +
            remainingHours +
            " hour" +
            (remainingHours > 1 ? "s" : "")
          );
        }
        return diffDays + " day" + (diffDays > 1 ? "s" : "");
      } else if (diffHours > 0) {
        var remainingMins = diffMins % 60;
        if (remainingMins > 0) {
          return (
            diffHours +
            " hour" +
            (diffHours > 1 ? "s" : "") +
            ", " +
            remainingMins +
            " min" +
            (remainingMins > 1 ? "s" : "")
          );
        }
        return diffHours + " hour" + (diffHours > 1 ? "s" : "");
      } else {
        return diffMins + " min" + (diffMins > 1 ? "s" : "");
      }
    },

    /**
     * Setup time synchronization - update end time when start time changes
     */
    setupTimeSync: function () {
      var $container = $("#QuickCreate");
      if ($container.length === 0) return;

      var $timeStart = $container.find('input[name="time_start"]');
      var $timeEnd = $container.find('input[name="time_end"]');
      var $dateStart = $container.find('input[name="date_start"]');
      var $dateEnd = $container.find('input[name="due_date"]');

      // When start time changes, update end time to 1 hour later (if end time is empty or same as start)
      $timeStart.on("change blur", function () {
        var startTimeStr = $(this).val();
        if (!startTimeStr) return;

        var startTime = ScheduleQuickCreate.parseTimeToMinutes(startTimeStr);
        if (startTime === null) return;

        var endTimeStr = $timeEnd.val();
        var endTime = endTimeStr
          ? ScheduleQuickCreate.parseTimeToMinutes(endTimeStr)
          : null;

        // Only auto-update if end time is empty or same as start
        if (!endTime || endTime <= startTime) {
          var newEndTime = startTime + 60; // Add 1 hour
          var hours = Math.floor(newEndTime / 60);
          var minutes = newEndTime % 60;

          // Format based on time format
          var timeFormat = $timeEnd.data("format");
          var formattedTime;
          if (timeFormat == "24") {
            formattedTime =
              (hours < 10 ? "0" : "") +
              hours +
              ":" +
              (minutes < 10 ? "0" : "") +
              minutes;
          } else {
            var ampm = hours >= 12 ? "PM" : "AM";
            var displayHour =
              hours > 12 ? hours - 12 : hours === 0 ? 12 : hours;
            formattedTime =
              displayHour +
              ":" +
              (minutes < 10 ? "0" : "") +
              minutes +
              " " +
              ampm;
          }

          $timeEnd.val(formattedTime);
          ScheduleQuickCreate.updateDuration();
        }
      });

      // When start date changes, update end date if it's before start
      $dateStart.on("change", function () {
        var startDateStr = $(this).val();
        var endDateStr = $dateEnd.val();
        if (!startDateStr || !endDateStr) return;

        try {
          var startDate = app.dateConvertToUserFormat(startDateStr);
          var endDate = app.dateConvertToUserFormat(endDateStr);
          if (endDate < startDate) {
            $dateEnd.val(startDateStr);
            ScheduleQuickCreate.updateDuration();
          }
        } catch (e) {
          console.error("Error syncing dates:", e);
        }
      });
    },
  };

  // Initialize when QuickCreate form is shown
  jQuery(document).on("post.QuickCreateForm.show", function (e, form) {
    setTimeout(function () {
      ScheduleQuickCreate.init();
    }, 100);
  });

  // Reset on modal close
  jQuery(document).on("hidden.bs.modal", ".myModal", function () {
    ScheduleQuickCreate.reset();
  });
})(jQuery);
