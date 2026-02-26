/*+**********************************************************************************
 * Calendar Quick Create Enhancements
 * Safe client-side enhancements only - no form submission changes
 * Google Calendar-like UX with 15-minute intervals, All Day toggle, duration calculation
 *************************************************************************************/

(function($){
	'use strict';

	var CalendarQuickCreate = {
		initialized: false,
		
		init: function() {
			// Prevent multiple initializations
			if (this.initialized) return;
			
			var $container = $('#QuickCreate');
			if ($container.length === 0) return;
			
			// Check if this is Calendar/Events module
			var moduleName = $container.find('input[name="module"]').val();
			if (!moduleName || (moduleName !== 'Calendar' && moduleName !== 'Events')) return;
			
			// Check if form is ready
			if ($container.find('input[name="time_start"]').length === 0) return;
			
			this.enhanceTimePickers();
			this.setupAllDayToggle();
			this.setupDurationDisplay();
			this.setupTimeSync();
			this.setupRepeatDropdown();
			if (moduleName === 'Calendar') {
				this.setupTaskDatetimeSummary();
			}
			
			this.initialized = true;
		},
		
		reset: function() {
			this.initialized = false;
		},

		/**
		 * Enhance time pickers: 15-minute intervals, manual typing
		 * SAFE: Only updates UI, does not change form submission
		 */
		enhanceTimePickers: function() {
			var $container = $('#QuickCreate');
			if ($container.length === 0) return;

			// Re-initialize time fields with 15-minute step after Vtiger's initialization
			setTimeout(function() {
				var $timeFields = $container.find('input[name="time_start"], input[name="time_end"]');
				
				$timeFields.each(function() {
					var $field = $(this);
					
					// Destroy existing timepicker if it exists
					if ($field.data('timepicker-list')) {
						try {
							$field.timepicker('remove');
						} catch(e) {
							// Ignore errors
						}
					}
					
					var timeFormat = $field.data('format');
					if (timeFormat == '24') {
						timeFormat = 'H:i';
					} else {
						timeFormat = 'h:i A';
					}

					// Re-initialize with 15-minute step
					try {
						$field.timepicker({
							timeFormat: timeFormat,
							step: 15, // 15-minute intervals
							disableTextInput: false, // Allow manual typing
							className: 'timePicker calendar-enhanced-timepicker',
							change: function(time) {
								// Trigger duration update when time changes
								CalendarQuickCreate.updateDuration();
							}
						});
					} catch(e) {
						// If timepicker fails, continue without enhancement
						console.warn('CalendarQuickCreate: Timepicker enhancement failed', e);
					}

					// Handle manual typing with validation
					$field.on('blur.calendar-quickcreate', function() {
						var input = $(this).val();
						if (input && !CalendarQuickCreate.parseTimeInput(input)) {
							// Invalid format - try to parse and fix
							var parsed = CalendarQuickCreate.parseAndFixTime(input);
							if (parsed) {
								$(this).val(parsed);
							}
						}
						CalendarQuickCreate.updateDuration();
					});
				});
			}, 500);
		},

		/**
		 * Parse and validate manual time input
		 */
		parseTimeInput: function(input) {
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
		parseAndFixTime: function(input) {
			if (!input) return null;
			input = input.trim();
			
			// Try to fix missing colon (e.g., "830" -> "8:30")
			var matchNoColon = input.match(/^([0-9]{1,2})([0-5][0-9])(\s?(AM|PM))?$/i);
			if (matchNoColon) {
				var hour = matchNoColon[1];
				var minute = matchNoColon[2];
				var ampm = matchNoColon[4] || '';
				return hour + ':' + minute + (ampm ? ' ' + ampm.toUpperCase() : '');
			}
			
			return null;
		},

		/**
		 * Setup All Day event toggle
		 * SAFE: Only hides/shows time inputs, does not remove from DOM
		 */
		setupAllDayToggle: function() {
			var $container = $('#QuickCreate');
			if ($container.length === 0) return;

			var $allDayCheckbox = $container.find('input[name="allday"], #calendar_allday');
			if ($allDayCheckbox.length === 0) return;

			var $timeStart = $container.find('input[name="time_start"]');
			var $timeEnd = $container.find('input[name="time_end"]');
			
			// Find time field containers (they might be in different structures)
			var $timeStartContainer = $timeStart.closest('.input-group, .fieldValue, .calendar-date-time-wrapper').parent();
			var $timeEndContainer = $timeEnd.closest('.input-group, .fieldValue, .calendar-date-time-wrapper').parent();
			
			// If containers not found, wrap time fields
			if ($timeStartContainer.length === 0) {
				$timeStartContainer = $timeStart.closest('div').parent();
			}
			if ($timeEndContainer.length === 0) {
				$timeEndContainer = $timeEnd.closest('div').parent();
			}

			$allDayCheckbox.off('change.calendar-allday').on('change.calendar-allday', function() {
				var isAllDay = $(this).is(':checked');
				
				if (isAllDay) {
					// Hide time fields (but keep in DOM)
					$timeStartContainer.hide();
					$timeEndContainer.hide();
					
					// Store previous values
					$timeStart.data('calendar-previous-value', $timeStart.val());
					$timeEnd.data('calendar-previous-value', $timeEnd.val());
				} else {
					// Show time fields
					$timeStartContainer.show();
					$timeEndContainer.show();
					
					// Restore previous values if available
					var prevStart = $timeStart.data('calendar-previous-value');
					var prevEnd = $timeEnd.data('calendar-previous-value');
					if (prevStart) $timeStart.val(prevStart);
					if (prevEnd) $timeEnd.val(prevEnd);
				}
				
				CalendarQuickCreate.updateDuration();
			});
		},

		/**
		 * Task form: cập nhật dòng "Thursday, January 1 8:45am - 4:45pm" khi đổi ngày/giờ
		 */
		setupTaskDatetimeSummary: function() {
			var $container = $('#QuickCreate');
			var $summary = $container.find('.calendar-qc-datetime-summary');
			if (!$summary.length) return;

			var update = function() {
				CalendarQuickCreate.updateTaskDatetimeSummary();
			};

			$container.find('input[name="date_start"], input[name="time_start"], input[name="due_date"], input[name="time_end"]')
				.on('change.calendar-qc-summary blur.calendar-qc-summary', update);

			setTimeout(update, 800);
		},

		updateTaskDatetimeSummary: function() {
			var $container = $('#QuickCreate');
			var $summary = $container.find('.calendar-qc-datetime-summary');
			if (!$summary.length) return;

			var $dateStart = $container.find('input[name="date_start"]');
			var $timeStart = $container.find('input[name="time_start"]');
			var $dateEnd = $container.find('input[name="due_date"]');
			var $timeEnd = $container.find('input[name="time_end"]');

			var dateStartStr = $dateStart.val();
			var timeStartStr = $timeStart.val();

			if (!dateStartStr) {
				$summary.text('—');
				return;
			}

			var fmt = 'dddd, MMMM D h:mma';
			try {
				var mStart = null;
				if (typeof moment !== 'undefined') {
					var userFmt = $dateStart.data('date-format') || 'yyyy-mm-dd';
					var mFmt = userFmt.replace(/yyyy/i, 'YYYY').replace(/dd/i, 'DD').replace(/mm/i, 'MM');
					mStart = moment(dateStartStr + (timeStartStr ? ' ' + timeStartStr : ' 00:00'), mFmt + (timeStartStr ? ' HH:mm' : ''));
					if (!mStart.isValid()) mStart = moment(dateStartStr + (timeStartStr ? ' ' + timeStartStr : ''));
				}
				if (!mStart || !mStart.isValid()) {
					$summary.text(dateStartStr + (timeStartStr ? ' ' + timeStartStr : ''));
					return;
				}
				var startText = mStart.format(fmt);
				var dateEndStr = $dateEnd && $dateEnd.val ? $dateEnd.val() : '';
				var timeEndStr = $timeEnd && $timeEnd.val ? $timeEnd.val() : '';
				if (dateEndStr) {
					var mEndFmt = ($dateEnd.data('date-format') || 'yyyy-mm-dd').replace(/yyyy/i, 'YYYY').replace(/dd/i, 'DD').replace(/mm/i, 'MM');
					var mEnd = timeEndStr ? moment(dateEndStr + ' ' + timeEndStr, mEndFmt + ' HH:mm') : moment(dateEndStr, mEndFmt);
					if (mEnd.isValid()) startText += ' – ' + (timeEndStr ? mEnd.format('h:mma') : mEnd.format('MMMM D'));
				}
				$summary.text(startText);
			} catch (e) {
				$summary.text(dateStartStr + (timeStartStr ? ' ' + timeStartStr : ''));
			}
		},

		/**
		 * Setup duration display and calculation
		 * SAFE: Only displays information, does not change form values
		 */
		setupDurationDisplay: function() {
			var $container = $('#QuickCreate');
			if ($container.length === 0) return;

			// Update duration when dates/times change
			$container.find('input[name="date_start"], input[name="due_date"], input[name="time_start"], input[name="time_end"]')
				.on('change.calendar-duration blur.calendar-duration', function() {
					CalendarQuickCreate.updateDuration();
				});

			// Initial duration calculation
			setTimeout(function() {
				CalendarQuickCreate.updateDuration();
			}, 600);
		},

		/**
		 * Calculate and display duration
		 * SAFE: Only displays, does not modify form
		 */
		updateDuration: function() {
			var $container = $('#QuickCreate');
			if ($container.length === 0) return;

			var $durationDisplay = $('#calendar-duration-display');
			if ($durationDisplay.length === 0) return;

			var $allDayCheckbox = $container.find('input[name="allday"], #calendar_allday');
			var isAllDay = $allDayCheckbox.is(':checked');

			var $dateStart = $container.find('input[name="date_start"]');
			var $dateEnd = $container.find('input[name="due_date"]');
			var $timeStart = $container.find('input[name="time_start"]');
			var $timeEnd = $container.find('input[name="time_end"]');

			if (!$dateStart.length || !$dateEnd.length) {
				$durationDisplay.text('');
				return;
			}

			var startDateStr = $dateStart.val();
			var endDateStr = $dateEnd.val();

			if (!startDateStr || !endDateStr) {
				$durationDisplay.text('');
				return;
			}

			if (isAllDay) {
				try {
					var startDate = app.dateConvertToUserFormat(startDateStr);
					var endDate = app.dateConvertToUserFormat(endDateStr);
					var diffDays = Math.ceil((new Date(endDate) - new Date(startDate)) / (1000 * 60 * 60 * 24)) + 1;
					if (diffDays === 1) {
						$durationDisplay.text('All Day');
					} else {
						$durationDisplay.text('All Day (' + diffDays + ' days)');
					}
				} catch (e) {
					$durationDisplay.text('All Day');
				}
				return;
			}

			var startTimeStr = $timeStart.val();
			var endTimeStr = $timeEnd.val();

			if (!startTimeStr || !endTimeStr) {
				$durationDisplay.text('');
				return;
			}

			try {
				// Parse dates (Vtiger format)
				var startDate = app.dateConvertToUserFormat(startDateStr);
				var endDate = app.dateConvertToUserFormat(endDateStr);

				// Parse times
				var startTime = CalendarQuickCreate.parseTimeToMinutes(startTimeStr);
				var endTime = CalendarQuickCreate.parseTimeToMinutes(endTimeStr);

				if (startTime === null || endTime === null) {
					$durationDisplay.text('');
					return;
				}

				// Calculate duration
				var duration = CalendarQuickCreate.calculateDuration(startDate, endDate, startTime, endTime, false);
				$durationDisplay.text(duration);
			} catch (e) {
				// Silently fail - don't break form
				$durationDisplay.text('');
			}
		},

		/**
		 * Parse time string to minutes since midnight
		 */
		parseTimeToMinutes: function(timeStr) {
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
				if (ampm === 'PM' && hour !== 12) hour += 12;
				if (ampm === 'AM' && hour === 12) hour = 0;
				return hour * 60 + minute;
			}

			return null;
		},

		/**
		 * Calculate duration between start and end
		 */
		calculateDuration: function(startDate, endDate, startTime, endTime, isAllDay) {
			// Parse dates
			var start = new Date(startDate);
			var end = new Date(endDate);

			if (!isAllDay && startTime !== null && endTime !== null) {
				// Add time to dates
				start.setHours(Math.floor(startTime / 60), startTime % 60, 0, 0);
				end.setHours(Math.floor(endTime / 60), endTime % 60, 0, 0);
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
					return diffDays + ' day' + (diffDays > 1 ? 's' : '') + ', ' + remainingHours + ' hour' + (remainingHours > 1 ? 's' : '');
				}
				return diffDays + ' day' + (diffDays > 1 ? 's' : '');
			} else if (diffHours > 0) {
				var remainingMins = diffMins % 60;
				if (remainingMins > 0) {
					return diffHours + ' hour' + (diffHours > 1 ? 's' : '') + ', ' + remainingMins + ' min' + (remainingMins > 1 ? 's' : '');
				}
				return diffHours + ' hour' + (diffHours > 1 ? 's' : '');
			} else {
				return diffMins + ' min' + (diffMins > 1 ? 's' : '');
			}
		},

		/**
		 * Setup time synchronization - update end time when start time changes
		 * SAFE: Only updates input values, does not change form submission logic
		 */
		setupTimeSync: function() {
			var $container = $('#QuickCreate');
			if ($container.length === 0) return;

			var $timeStart = $container.find('input[name="time_start"]');
			var $timeEnd = $container.find('input[name="time_end"]');
			var $dateStart = $container.find('input[name="date_start"]');
			var $dateEnd = $container.find('input[name="due_date"]');

			// When start time changes, update end time to 1 hour later (if end time is empty or same as start)
			$timeStart.on('change.calendar-sync blur.calendar-sync', function() {
				var startTimeStr = $(this).val();
				if (!startTimeStr) return;

				var startTime = CalendarQuickCreate.parseTimeToMinutes(startTimeStr);
				if (startTime === null) return;

				var endTimeStr = $timeEnd.val();
				var endTime = endTimeStr ? CalendarQuickCreate.parseTimeToMinutes(endTimeStr) : null;

				// Only auto-update if end time is empty or same as start
				if (!endTime || endTime <= startTime) {
					var newEndTime = startTime + 60; // Add 1 hour
					var hours = Math.floor(newEndTime / 60);
					var minutes = newEndTime % 60;

					// Format based on time format
					var timeFormat = $timeEnd.data('format');
					var formattedTime;
					if (timeFormat == '24') {
						formattedTime = (hours < 10 ? '0' : '') + hours + ':' + (minutes < 10 ? '0' : '') + minutes;
					} else {
						var ampm = hours >= 12 ? 'PM' : 'AM';
						var displayHour = hours > 12 ? hours - 12 : (hours === 0 ? 12 : hours);
						formattedTime = displayHour + ':' + (minutes < 10 ? '0' : '') + minutes + ' ' + ampm;
					}

					$timeEnd.val(formattedTime);
					CalendarQuickCreate.updateDuration();
				}
			});

			// When start date changes, update end date if it's before start
			$dateStart.on('change.calendar-sync', function() {
				var startDateStr = $(this).val();
				var endDateStr = $dateEnd.val();
				if (!startDateStr || !endDateStr) return;

				try {
					var startDate = app.dateConvertToUserFormat(startDateStr);
					var endDate = app.dateConvertToUserFormat(endDateStr);
					if (endDate < startDate) {
						$dateEnd.val(startDateStr);
						CalendarQuickCreate.updateDuration();
					}
				} catch (e) {
					// Silently fail
				}
			});
		},

		/**
		 * Setup Repeat dropdown (UI Only - Phase 1)
		 * SAFE: Only stores value in hidden input, does not affect form submission
		 */
		setupRepeatDropdown: function() {
			var $container = $('#QuickCreate');
			if ($container.length === 0) return;

			var $repeatSelect = $container.find('#calendar_repeat_type');
			var $hiddenInput = $container.find('#calendar_recurringtype_hidden');
			
			if ($repeatSelect.length === 0) return;

			// Sync dropdown value to hidden input
			$repeatSelect.on('change.calendar-repeat', function() {
				var value = $(this).val();
				if ($hiddenInput.length > 0) {
					$hiddenInput.val(value);
				}
			});
		}
	};

	// Initialize when QuickCreate form is shown
	jQuery(document).on('post.QuickCreateForm.show', function(e, form) {
		setTimeout(function() {
			CalendarQuickCreate.init();
		}, 100);
	});

	// Reset on modal close
	jQuery(document).on('hidden.bs.modal', '.myModal', function() {
		CalendarQuickCreate.reset();
	});

	// Fallback initialization for direct page loads
	jQuery(document).ready(function() {
		setTimeout(function() {
			var $container = jQuery('#QuickCreate');
			if ($container.length > 0 && $container.find('input[name="time_start"]').length > 0) {
				CalendarQuickCreate.init();
			}
		}, 500);
	});

	// Expose globally (optional, for debugging)
	window.CalendarQuickCreate = CalendarQuickCreate;

})(jQuery);
