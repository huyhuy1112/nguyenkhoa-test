(function () {
  "use strict";

  var ModernNotifications = {
    previousIds: [],
    lastRenderedNotificationId: 0,
    intervalId: null,
    sound: null,
    initialized: false,
    currentTab: "unread",
    isFirstLoad: true, // Track first load to prevent sound on page reload

    init: function () {
      if (this.initialized) {
        return;
      }

      // Initialize sound with proper path
      this.initSound();

      // Ensure we start on unread tab
      this.currentTab = "unread";
      this.loadUnreadNotifications();

      // Poll for new unread notifications every 3 seconds
      this.intervalId = setInterval(function () {
        // Always update badge count, but only render if on unread tab
        if (ModernNotifications.currentTab === "unread") {
          ModernNotifications.loadUnreadNotifications();
        } else {
          // Still check for new notifications to update badge
          ModernNotifications.checkUnreadCountOnly();
        }
      }, 3000);

      this.setupTabHandlers();

      this.initialized = true;
    },

    checkUnreadCountOnly: function () {
      var self = this;
      var url = "index.php?module=Vtiger&action=Notifications&type=unread";

      jQuery.ajax({
        url: url,
        type: "GET",
        dataType: "json",
        cache: false, // Prevent Chrome cache issues
        xhrFields: {
          withCredentials: true, // Ensure cookies are sent (required for Chrome)
        },
        success: function (response) {
          if (response && response.success) {
            // Only update badge, don't render list
            self.updateNotificationBadge(response.count || 0);
            // Check for new notifications to play sound and shake bell
            // Only after first load to prevent sound on page reload
            if (!self.isFirstLoad && response.list && response.list.length > 0) {
              self.checkForNewNotifications(response.list);
            } else if (self.isFirstLoad && response.list && response.list.length > 0) {
              // On first load, initialize previousIds without playing sound
              for (var i = 0; i < response.list.length; i++) {
                if (self.previousIds.indexOf(response.list[i].id) === -1) {
                  self.previousIds.push(response.list[i].id);
                }
              }
              self.isFirstLoad = false;
            }
          }
        },
        error: function (xhr, status, error) {
          // Log error for debugging
          console.warn(
            "[ModernNotifications] Error loading unread count:",
            status,
            error
          );
        },
      });
    },

    verifyNotificationSound: function (soundPath) {
      var self = this;
      // Use relative path for fetch (works better with same-origin)
      // CRITICAL: Use _v2 to bust browser cache of old invalid file
      var relativePath = "layouts/v7/modules/Vtiger/resources/sounds/notification_v2.mp3";

      // Check if fetch API is available
      if (typeof fetch === "undefined") {
        console.warn(
          "[NotificationSound] ❌ Fetch API not available (old browser)"
        );
        return;
      }

      fetch(relativePath, { method: "HEAD" })
        .then(function (res) {
          // Log HTTP status for debugging
          console.log(
            "[NotificationSound] 📡 HTTP Status:",
            res.status,
            res.statusText
          );

          if (!res.ok) {
            console.warn(
              "[NotificationSound] ❌ File not found (HTTP " +
                res.status +
                "):",
              relativePath
            );
            console.warn(
              "[NotificationSound] 💡 Check: File exists at",
              soundPath
            );
            return;
          }

          // Log Content-Type header
          var contentType = res.headers.get("content-type") || "unknown";
          console.log(
            "[NotificationSound] 📄 Content-Type:",
            contentType
          );

          // Log Content-Length header
          var size = parseInt(res.headers.get("content-length") || "0", 10);
          console.log(
            "[NotificationSound] 📦 Content-Length:",
            size,
            "bytes"
          );

          if (size === 0) {
            console.warn(
              "[NotificationSound] ❌ Sound file is empty (0 bytes)"
            );
            console.warn(
              "[NotificationSound] 💡 This indicates a server configuration issue or invalid file"
            );
          } else if (size < 1024) {
            console.warn(
              "[NotificationSound] ⚠️ Sound file very small (likely invalid):",
              size,
              "bytes (expected > 1KB)"
            );
            console.warn(
              "[NotificationSound] 💡 File may be corrupted or placeholder. Valid MP3 should be > 1KB"
            );
          } else {
            console.log(
              "[NotificationSound] ✅ Sound file detected:",
              size,
              "bytes (" +
                (size / 1024).toFixed(2) +
                " KB)"
            );
            if (contentType.indexOf("audio") === -1 && contentType !== "unknown") {
              console.warn(
                "[NotificationSound] ⚠️ Content-Type is not audio/*:",
                contentType
              );
            }
          }
        })
        .catch(function (err) {
          console.warn(
            "[NotificationSound] ❌ Unable to access sound file:",
            err.message
          );
          console.warn(
            "[NotificationSound] 💡 Possible causes: CORS issue, path incorrect, or file missing"
          );
      });
    },

    initSound: function () {
      try {
        // Get base URL - try multiple methods
        var baseUrl = "";
        if (typeof _META !== "undefined" && _META.notifier) {
          // Try to extract base URL from Vtiger's _META
          var notifierUrl = _META.notifier;
          baseUrl = notifierUrl.substring(0, notifierUrl.lastIndexOf("/"));
        } else {
          // Fallback: construct from current location
          var pathParts = window.location.pathname.split("/");
          pathParts = pathParts.filter(function (part) {
            return part && part !== "index.php";
          });
          baseUrl = window.location.origin;
          if (pathParts.length > 0 && pathParts[0] !== "") {
            baseUrl += "/" + pathParts[0];
          }
        }

        // Remove trailing slash
        if (baseUrl.endsWith("/")) {
          baseUrl = baseUrl.slice(0, -1);
        }

        // CRITICAL: Use _v2 to bust browser cache of old invalid 169-byte file
        var soundPath =
          baseUrl +
          "/layouts/v7/modules/Vtiger/resources/sounds/notification_v2.mp3";

        console.log("[NotificationSound] 🔍 Initializing sound from:", soundPath);

        // Create Audio object
        this.sound = new Audio(soundPath);
        this.sound.volume = 0.7;
        this.sound.preload = "auto";

        var self = this;

        // Enhanced error handling for Audio object
        this.sound.onerror = function (e) {
          console.warn(
            "[NotificationSound] ❌ Audio failed to load or decode"
          );
          console.warn(
            "[NotificationSound] 💡 Check: File format (MP3), file corruption, or path issue"
          );
          if (self.sound.error) {
            var errorMsg = "";
            switch (self.sound.error.code) {
              case 1:
                errorMsg = "MEDIA_ERR_ABORTED";
                break;
              case 2:
                errorMsg = "MEDIA_ERR_NETWORK";
                break;
              case 3:
                errorMsg = "MEDIA_ERR_DECODE";
                break;
              case 4:
                errorMsg = "MEDIA_ERR_SRC_NOT_SUPPORTED";
                break;
              default:
                errorMsg = "Unknown error";
            }
            console.warn(
              "[NotificationSound] Error code:",
              self.sound.error.code,
              "(" + errorMsg + ")"
            );
          }
        };

        this.sound.oncanplaythrough = function () {
          console.log(
            "[NotificationSound] ✅ Audio loaded and ready to play"
          );
        };

        // Verify sound file exists and has valid size
        this.verifyNotificationSound(soundPath);

        // Preload sound on user interaction (required by browsers for autoplay)
        // CRITICAL: preloadSound() must NEVER throw - wrap all operations in try/catch
        var preloadSound = function () {
          try {
            if (!self.sound) {
              console.warn(
                "[NotificationSound] ⚠️ Cannot preload: Audio object not initialized"
              );
              return;
            }

            // CRITICAL: load() may not return a Promise in all browsers
            // Never call .then() directly - always check if Promise exists
            var loadResult = self.sound.load();
            if (loadResult && typeof loadResult.then === "function") {
              loadResult
                .then(function () {
                  console.log(
                    "[NotificationSound] ✅ Sound preloaded successfully"
                  );
                })
                .catch(function (e) {
                  console.warn(
                    "[NotificationSound] ❌ Sound preload failed:",
                    e.message
                  );
                });
            } else {
              // load() returned undefined or non-Promise - this is OK, just log
              console.log(
                "[NotificationSound] ℹ️ Sound load() called (non-Promise return)"
              );
            }
          } catch (e) {
            // CRITICAL: Never let preload crash the system
            console.warn(
              "[NotificationSound] ❌ Preload exception (non-fatal):",
              e.message
            );
          } finally {
            // Always clean up event listeners
          document.removeEventListener("click", preloadSound);
          document.removeEventListener("touchstart", preloadSound);
          document.removeEventListener("keydown", preloadSound);
            document.removeEventListener("mousedown", preloadSound);
          }
        };
        document.addEventListener("click", preloadSound, { once: true });
        document.addEventListener("touchstart", preloadSound, { once: true });
        document.addEventListener("keydown", preloadSound, { once: true });
        document.addEventListener("mousedown", preloadSound, { once: true });
      } catch (e) {
        console.warn(
          "[NotificationSound] ❌ Sound initialization failed:",
          e.message
        );
        console.warn("[NotificationSound] Stack:", e.stack);
      }
    },

    setupTabHandlers: function () {
      var self = this;
      jQuery("#modern-notifications-tab-unread-link").on("click", function (e) {
        e.preventDefault();
        e.stopPropagation();
        self.switchTab("unread");
      });

      jQuery("#modern-notifications-tab-read-link").on("click", function (e) {
        e.preventDefault();
        e.stopPropagation();
        self.switchTab("read");
      });
    },

    switchTab: function (tab) {
      if (this.currentTab === tab) {
        return;
      }

      // CRITICAL: Set currentTab BEFORE any async operations
      this.currentTab = tab;

      var unreadTab = jQuery("#modern-notifications-tab-unread");
      var readTab = jQuery("#modern-notifications-tab-read");
      var unreadEmpty = jQuery("#modern-notifications-empty-unread");
      var readEmpty = jQuery("#modern-notifications-empty-read");
      var itemsContainer = jQuery("#modern-notifications-items");
      var actionsContainer = jQuery("#modern-notifications-actions");

      // Always clear items container first
      itemsContainer.empty();
      itemsContainer.hide();
      actionsContainer.hide();

      if (tab === "unread") {
        unreadTab.addClass("active");
        readTab.removeClass("active");
        // Hide read empty, show unread empty (will be hidden if we have data)
        readEmpty.hide();
        unreadEmpty.show();
        // CRITICAL: Load unread notifications AFTER setting currentTab
        var self = this;
        // Use setTimeout to ensure currentTab is set before AJAX callback
        setTimeout(function () {
          // Double-check we're still on unread tab before loading
          if (self.currentTab === "unread") {
            self.loadUnreadNotifications();
          }
        }, 0);
      } else {
        readTab.addClass("active");
        unreadTab.removeClass("active");
        // Hide unread empty, show read empty (will be hidden if we have data)
        unreadEmpty.hide();
        readEmpty.show();
        // CRITICAL: Load read notifications AFTER setting currentTab
        var self = this;
        // Use setTimeout to ensure currentTab is set before AJAX callback
        setTimeout(function () {
          // Double-check we're still on read tab before loading
          if (self.currentTab === "read") {
            self.loadReadNotifications();
          }
        }, 0);
      }
    },

    loadUnreadNotifications: function () {
      var self = this;
      var url = "index.php?module=Vtiger&action=Notifications&type=unread";

      // CRITICAL: Double-check we're still on unread tab before making request
      if (this.currentTab !== "unread") {
        return;
      }

      jQuery.ajax({
        url: url,
        type: "GET",
        dataType: "json",
        cache: false, // Prevent Chrome cache issues
        xhrFields: {
          withCredentials: true, // Ensure cookies are sent (required for Chrome)
        },
        success: function (response) {
          if (response && response.success) {
            // CRITICAL: Check again after AJAX completes
            if (self.currentTab === "unread") {
              self.updateUnreadUI(response);
              // Only check for new notifications after first load
              if (!self.isFirstLoad) {
              self.checkForNewNotifications(response.list);
              } else {
                // On first load, initialize previousIds with current notifications
                // This prevents sound from playing for existing notifications
                if (response.list && response.list.length > 0) {
                  for (var i = 0; i < response.list.length; i++) {
                    self.previousIds.push(response.list[i].id);
                  }
                }
                self.isFirstLoad = false;
              }
            }
          }
        },
        error: function (xhr, status, error) {
          // Log error for debugging
          console.warn(
            "[ModernNotifications] Error loading unread notifications:",
            status,
            error,
            xhr.status
          );
        },
      });
    },

    loadReadNotifications: function () {
      var self = this;
      var url = "index.php?module=Vtiger&action=Notifications&type=read";

      // CRITICAL: Double-check we're still on read tab before making request
      if (this.currentTab !== "read") {
        return;
      }

      jQuery.ajax({
        url: url,
        type: "GET",
        dataType: "json",
        cache: false, // Prevent Chrome cache issues
        xhrFields: {
          withCredentials: true, // Ensure cookies are sent (required for Chrome)
        },
        success: function (response) {
          if (response && response.success) {
            // CRITICAL: Check again after AJAX completes
            if (self.currentTab === "read") {
              self.updateReadUI(response);
            }
          }
        },
        error: function (xhr, status, error) {
          // Log error for debugging
          console.warn(
            "[ModernNotifications] Error loading read notifications:",
            status,
            error,
            xhr.status
          );
        },
      });
    },

    updateNotificationBadge: function (count) {
      var badge = document.getElementById("modern-notifications-count");
      if (!badge) return;

      var markAllBtn = document.getElementById(
        "modern-notifications-mark-all-read"
      );
      var countNum = Number(count) || 0;

      if (countNum > 0) {
        badge.textContent = countNum;
        badge.style.display = "";
        if (markAllBtn) {
          markAllBtn.style.display = "";
        }
      } else {
        badge.textContent = "";
        badge.style.display = "none";
        if (markAllBtn) {
          markAllBtn.style.display = "none";
        }
      }
    },

    updateUnreadUI: function (response) {
      // CRITICAL: Only update UI if we're on the unread tab
      if (this.currentTab !== "unread") {
        // Just update badge count, don't render list
        this.updateNotificationBadge(response.count || 0);
        return;
      }

      var count = response.count || 0;
      var list = response.list || [];
      var emptyMsg = jQuery("#modern-notifications-empty-unread");
      var readEmpty = jQuery("#modern-notifications-empty-read");
      var itemsContainer = jQuery("#modern-notifications-items");
      var actionsContainer = jQuery("#modern-notifications-actions");

      this.updateNotificationBadge(count);

      // CRITICAL: Ensure empty messages are shown/hidden correctly for unread tab
      readEmpty.hide(); // Always hide read empty when on unread tab

      // CRITICAL: Always clear items container first to remove any old data
      itemsContainer.empty();

      if (list.length === 0) {
        emptyMsg.show();
        itemsContainer.hide();
        actionsContainer.hide();
        this.lastRenderedNotificationId = 0;
        return;
      }

      // We have unread notifications - hide empty message and show items
      emptyMsg.hide();
      itemsContainer.show();
      actionsContainer.show();

      // CRITICAL: Always clear and re-render all unread notifications
      // (already cleared above, but ensure it's empty)
      itemsContainer.empty();
      var maxId = 0;
      for (var j = 0; j < list.length; j++) {
        // CRITICAL: Always render as unread (false) for unread tab
        this.renderNotificationItem(list[j], itemsContainer[0], false);
        var notifId = parseInt(list[j].id) || 0;
        if (notifId > maxId) {
          maxId = notifId;
        }
      }
      this.lastRenderedNotificationId = maxId;
    },

    updateReadUI: function (response) {
      // CRITICAL: Only update UI if we're on the read tab
      if (this.currentTab !== "read") {
        return;
      }

      var list = response.list || [];
      var emptyMsg = jQuery("#modern-notifications-empty-read");
      var unreadEmpty = jQuery("#modern-notifications-empty-unread");
      var itemsContainer = jQuery("#modern-notifications-items");
      var actionsContainer = jQuery("#modern-notifications-actions");

      // CRITICAL: Ensure empty messages are shown/hidden correctly for read tab
      unreadEmpty.hide(); // Always hide unread empty when on read tab

      // CRITICAL: Always clear items container first to remove any old data
      itemsContainer.empty();

      if (list.length === 0) {
        emptyMsg.show();
        itemsContainer.hide();
        actionsContainer.hide();
        return;
      }

      // We have read notifications - hide empty message and show items
      emptyMsg.hide();
      itemsContainer.show();
      actionsContainer.show();

      // CRITICAL: Always clear and re-render all read notifications
      // (already cleared above, but ensure it's empty)
      itemsContainer.empty();
      for (var i = 0; i < list.length; i++) {
        this.renderNotificationItem(list[i], itemsContainer[0], true);
      }
    },

    decodeHtmlEntities: function (text) {
      if (!text) return "";
      var textarea = document.createElement("textarea");
      textarea.innerHTML = text;
      return textarea.value;
    },

    highlightKeywords: function (text) {
      if (!text) return "";

      // Keywords to highlight with their colors
      // IMPORTANT: Longer patterns must come first to avoid partial matches
      // Each keyword has a UNIQUE color for easy identification
      var keywords = [
        { pattern: /Project Task:/gi, color: "#f39c12", fontWeight: "bold" }, // Vàng
        { pattern: /Organization:/gi, color: "#8e44ad", fontWeight: "bold" }, // Tím
        { pattern: /Ticket:/gi, color: "#e67e22", fontWeight: "bold" }, // Cam
        { pattern: /Contact:/gi, color: "#27ae60", fontWeight: "bold" }, // Xanh lá đậm
        { pattern: /Opportunity:/gi, color: "#1abc9c", fontWeight: "bold" }, // Xanh ngọc
        { pattern: /Task:/gi, color: "#3498db", fontWeight: "bold" }, // Xanh dương
        { pattern: /Event:/gi, color: "#e74c3c", fontWeight: "bold" }, // Đỏ
        { pattern: /Project:/gi, color: "#9b59b6", fontWeight: "bold" }, // Tím đậm
      ];

      var result = text;

      // Apply highlights in order (longer patterns first to avoid conflicts)
      keywords.forEach(function (keyword) {
        // Use a more robust replacement that preserves the matched text
        result = result.replace(keyword.pattern, function (match) {
          return (
            '<span class="notification-keyword" data-keyword="' +
            match.toLowerCase().replace(":", "") +
            '" style="color: ' +
            keyword.color +
            " !important; font-weight: " +
            keyword.fontWeight +
            ' !important; display: inline;">' +
            match +
            "</span>"
          );
        });
      });

      return result;
    },

    renderNotificationItem: function (notif, container, isRead) {
      var self = this;
      var module = notif.module || "Vtiger";
      var recordId = notif.recordid || "";
      var message = notif.message || "";
      var createdAt = notif.created_at || "";
      var notificationId = notif.id || "";
      var detailUrl = "";

      // Decode HTML entities in message and highlight keywords
      message = this.decodeHtmlEntities(message);
      var highlightedMessage = this.highlightKeywords(message);

      if (recordId) {
        detailUrl =
          "index.php?module=" + module + "&view=Detail&record=" + recordId;
      }

      var li = document.createElement("li");
      li.className = "modern-notification-item";
      // Add read-notification class only if isRead is true
      if (isRead) {
        li.classList.add("read-notification");
      }
      li.style.padding = "10px";
      li.style.borderBottom = "1px solid #eee";
      li.style.position = "relative";
      li.setAttribute("data-notification-id", notificationId);

      // Check if this is a deadline reminder notification
      var isDeadlineReminder =
        message.indexOf("sắp đến hạn") !== -1 ||
        message.indexOf("sắp hết hạn") !== -1;
      if (isDeadlineReminder) {
        li.classList.add("deadline-notification");
      }

      // Add checkbox - make it bolder and more visible
      var checkbox = document.createElement("input");
      checkbox.type = "checkbox";
      checkbox.className = "modern-notification-checkbox";
      checkbox.value = notificationId;
      checkbox.style.position = "absolute";
      // Move checkbox to the right if it's a deadline notification (to avoid warning icon)
      checkbox.style.left = isDeadlineReminder ? "35px" : "10px";
      checkbox.style.top = "15px";
      checkbox.style.cursor = "pointer";
      // Make checkbox bolder and more visible
      checkbox.style.width = "18px";
      checkbox.style.height = "18px";
      checkbox.style.border = "2px solid #333";
      checkbox.style.borderRadius = "3px";
      checkbox.style.accentColor = "#4CAF50";
      checkbox.addEventListener("click", function (e) {
        e.stopPropagation();
        self.updateDeleteButtonState();
      });
      li.appendChild(checkbox);

      // Content wrapper with left margin for checkbox and warning icon
      var contentWrapper = document.createElement("div");
      // Adjust margin based on whether it's a deadline notification
      contentWrapper.style.marginLeft = isDeadlineReminder ? "60px" : "25px";

      // Create link first if needed
      var link = null;
      if (detailUrl) {
        link = document.createElement("a");
        link.href = detailUrl;
        link.style.textDecoration = "none";
        link.style.color = "#333";
        link.style.display = "block";
      }

      if (isRead) {
        // Make read notifications more faded
        li.style.opacity = "0.5";
        li.style.cursor = "default";
        li.style.backgroundColor = "#f9f9f9";
        // Make text lighter
        if (link) {
          link.style.color = "#999";
        }
      } else {
        // Unread notifications - full opacity, bold, clickable
        li.style.opacity = "1";
        li.style.cursor = "pointer";
        li.style.backgroundColor = "";
        if (link) {
          link.style.color = "#333";
        }
        li.addEventListener("click", function (e) {
          if (
            e.target.tagName !== "INPUT" &&
            e.target.tagName !== "A" &&
            e.target.closest("a") === null
          ) {
            self.markAsRead(notificationId, li);
          }
        });
      }

      if (link) {
        var messageDiv = document.createElement("div");
        messageDiv.style.marginBottom = "5px";
        messageDiv.innerHTML = highlightedMessage;
        // Unread notifications are bold, read notifications are normal
        if (!isRead) {
          messageDiv.style.fontWeight = "bold";
          messageDiv.style.color = "#2c3e50";
        } else {
          messageDiv.style.fontWeight = "normal";
          messageDiv.style.color = "#999";
        }

        if (createdAt) {
          var dateDiv = document.createElement("div");
          dateDiv.style.fontSize = "11px";
          dateDiv.style.color = isRead ? "#bbb" : "#999";
          dateDiv.textContent = this.formatDate(createdAt);

          link.appendChild(messageDiv);
          link.appendChild(dateDiv);
          contentWrapper.appendChild(link);
        } else {
          link.appendChild(messageDiv);
          contentWrapper.appendChild(link);
        }
      } else {
        var messageDiv = document.createElement("div");
        messageDiv.style.marginBottom = "5px";
        messageDiv.innerHTML = highlightedMessage;
        // Unread notifications are bold, read notifications are normal
        if (!isRead) {
          messageDiv.style.fontWeight = "bold";
          messageDiv.style.color = "#2c3e50";
        } else {
          messageDiv.style.fontWeight = "normal";
          messageDiv.style.color = "#999";
        }

        if (createdAt) {
          var dateDiv = document.createElement("div");
          dateDiv.style.fontSize = "11px";
          dateDiv.style.color = "#999";
          dateDiv.textContent = this.formatDate(createdAt);

          contentWrapper.appendChild(messageDiv);
          contentWrapper.appendChild(dateDiv);
        } else {
          contentWrapper.appendChild(messageDiv);
        }
      }

      li.appendChild(contentWrapper);
      container.appendChild(li);
    },

    markAsRead: function (notificationId, element) {
      var self = this;
      var url = "index.php?module=Vtiger&action=MarkNotificationRead";

      if (element) {
        element.style.opacity = "0.5";
      }

      jQuery.ajax({
        url: url,
        type: "POST",
        dataType: "json",
        cache: false, // Prevent Chrome cache issues
        xhrFields: {
          withCredentials: true, // Ensure cookies are sent (required for Chrome)
        },
        data: {
          notification_id: notificationId,
        },
        success: function (response) {
          if (
            response &&
            response.success &&
            typeof response.unreadCount !== "undefined"
          ) {
            if (element && element.parentNode) {
              element.parentNode.removeChild(element);
            }

            var unreadCount = Number(response.unreadCount) || 0;
            self.updateNotificationBadge(unreadCount);

            var itemsContainer = jQuery("#modern-notifications-items");
            var emptyMsg = jQuery("#modern-notifications-empty-unread");

            if (unreadCount > 0) {
              itemsContainer.show();
              emptyMsg.hide();
            } else {
              itemsContainer.hide();
              emptyMsg.show();
            }

            var index = self.previousIds.indexOf(parseInt(notificationId));
            if (index > -1) {
              self.previousIds.splice(index, 1);
            }
          } else {
            if (element) {
              element.style.opacity = "1";
            }
          }
        },
        error: function (xhr, status, error) {
          // Log error for debugging
          console.warn(
            "[ModernNotifications] Error marking notification as read:",
            status,
            error,
            xhr.status
          );
          if (element) {
            element.style.opacity = "1";
          }
        },
      });
    },

    markAllAsRead: function () {
      var self = this;
      var url =
        "index.php?module=Vtiger&action=MarkNotificationRead&mode=markAll";

      var itemsContainer = jQuery("#modern-notifications-items");
      itemsContainer.css("opacity", "0.5");

      jQuery.ajax({
        url: url,
        type: "POST",
        dataType: "json",
        cache: false, // Prevent Chrome cache issues
        xhrFields: {
          withCredentials: true, // Ensure cookies are sent (required for Chrome)
        },
        data: {
          mark_all: "true",
        },
        success: function (response) {
          if (
            response &&
            response.success &&
            typeof response.unreadCount !== "undefined"
          ) {
            // Clear unread list
            itemsContainer.empty();
            itemsContainer.hide();

            var unreadCount = Number(response.unreadCount) || 0;
            self.updateNotificationBadge(unreadCount);

            // Show empty message for unread tab
            var emptyMsgUnread = jQuery("#modern-notifications-empty-unread");
            emptyMsgUnread.show();

            // Switch to "Đã đọc" tab and load read notifications
            self.switchTab("read");
            self.loadReadNotifications();

            self.previousIds = [];
            self.lastRenderedNotificationId = 0;
          } else {
            itemsContainer.css("opacity", "1");
          }
        },
        error: function (xhr, status, error) {
          // Log error for debugging
          console.warn(
            "[ModernNotifications] Error marking all as read:",
            status,
            error,
            xhr.status
          );
          itemsContainer.css("opacity", "1");
        },
      });
    },

    checkForNewNotifications: function (newList) {
      if (!newList || newList.length === 0) {
        return;
      }

      var newIds = [];
      for (var i = 0; i < newList.length; i++) {
        newIds.push(newList[i].id);
      }

      // Check if there are any truly NEW notifications (not in previousIds)
      var hasNew = false;
      for (var j = 0; j < newIds.length; j++) {
        if (this.previousIds.indexOf(newIds[j]) === -1) {
          hasNew = true;
          break;
        }
      }

      // Only play sound and shake bell if there are NEW notifications
      // This prevents sound from playing when:
      // - Page reloads (isFirstLoad handled separately)
      // - User marks notifications as read (count decreases, but no new IDs)
      // - Opening notification list (no new notifications)
      if (hasNew) {
          this.playSound();
          // Add shake animation to bell icon
          this.shakeBell();
      }

      // Update previousIds to current list (for next comparison)
      this.previousIds = newIds;
    },

    shakeBell: function () {
      var bell = document.getElementById("modern-notifications-bell");
      if (!bell) return;

      // Remove existing shake class if any
      bell.classList.remove("bell-shake");

      // Force reflow
      void bell.offsetWidth;

      // Add shake animation
      bell.classList.add("bell-shake");

      // Remove after animation completes
      setTimeout(function () {
        bell.classList.remove("bell-shake");
      }, 1000);
    },

    playSound: function () {
      if (!this.sound) {
        console.warn(
          "[NotificationSound] ❌ Cannot play: Audio object not initialized"
        );
        return;
      }

      try {
        // Check if sound is ready
        if (this.sound.readyState < 2) {
          console.warn(
            "[NotificationSound] ⚠️ Sound not ready (readyState:",
            this.sound.readyState,
            "). Attempting to play anyway..."
          );
        }

        // Reset sound to beginning
        this.sound.currentTime = 0;

        // CRITICAL FIX: audio.play() does NOT always return a Promise
        // In many browsers it returns undefined → calling .then() crashes JS
        // Must safely check if Promise exists before calling .then()
        var playResult = this.sound.play();

        // Check if play() returned a Promise (modern browsers) or undefined (old browsers)
        if (playResult && typeof playResult.then === "function") {
          // Modern browser: play() returned a Promise
          playResult
            .then(function () {
              console.log("[NotificationSound] 🔊 Sound played successfully");
            })
            .catch(function (error) {
              // Autoplay was prevented or sound failed
              console.warn(
                "[NotificationSound] ❌ Sound play failed:",
                error.message
              );
              if (error.name === "NotAllowedError") {
                console.warn(
                  "[NotificationSound] 💡 Autoplay blocked by browser. User interaction required."
                );
              } else if (error.name === "NotSupportedError") {
                console.warn(
                  "[NotificationSound] 💡 Audio format not supported by browser."
                );
              } else {
                console.warn(
                  "[NotificationSound] 💡 Error type:",
                  error.name
                );
              }

              // Try to create a new Audio instance as fallback
              try {
                var baseUrl = "";
                if (typeof _META !== "undefined" && _META.notifier) {
                  var notifierUrl = _META.notifier;
                  baseUrl = notifierUrl.substring(
                    0,
                    notifierUrl.lastIndexOf("/")
                  );
                } else {
                  var pathParts = window.location.pathname.split("/");
                  pathParts = pathParts.filter(function (part) {
                    return part && part !== "index.php";
                  });
                  baseUrl = window.location.origin;
                  if (pathParts.length > 0 && pathParts[0] !== "") {
                    baseUrl += "/" + pathParts[0];
                  }
                }
                if (baseUrl.endsWith("/")) {
                  baseUrl = baseUrl.slice(0, -1);
                }
                // CRITICAL: Use _v2 for fallback too
                var soundPath =
                  baseUrl +
                  "/layouts/v7/modules/Vtiger/resources/sounds/notification_v2.mp3";
                console.log(
                  "[NotificationSound] 🔄 Attempting fallback Audio instance..."
                );
                var fallbackSound = new Audio(soundPath);
                fallbackSound.volume = 0.7;

                // CRITICAL: Fallback play() must also be Promise-safe
                var fallbackPlayResult = fallbackSound.play();
                if (
                  fallbackPlayResult &&
                  typeof fallbackPlayResult.then === "function"
                ) {
                  fallbackPlayResult
                    .then(function () {
                      console.log(
                        "[NotificationSound] ✅ Fallback sound played successfully"
                      );
                    })
                    .catch(function (e) {
                  console.warn(
                        "[NotificationSound] ❌ Fallback sound also failed:",
                        e.message
                  );
                });
                } else {
                  // Fallback play() returned undefined - assume it worked (old browser)
                  console.log(
                    "[NotificationSound] ℹ️ Fallback sound play() called (non-Promise return)"
                  );
                }
              } catch (e) {
                console.warn(
                  "[NotificationSound] ❌ Fallback sound creation failed:",
                  e.message
                );
              }
            });
        } else {
          // Old browser: play() returned undefined - assume it worked
          // This is the safe fallback for browsers that don't return Promises
          console.log(
            "[NotificationSound] ℹ️ Sound play() called (non-Promise return - old browser)"
          );
        }
      } catch (e) {
        console.warn(
          "[NotificationSound] ❌ Sound play error:",
          e.message
        );
        console.warn("[NotificationSound] Stack:", e.stack);
      }
    },

    formatDate: function (dateString) {
      if (!dateString) return "";
      try {
        var date = new Date(dateString);
        var now = new Date();
        var diff = Math.floor((now - date) / 1000);

        if (diff < 60) {
          return "Vừa xong";
        } else if (diff < 3600) {
          return Math.floor(diff / 60) + " phút trước";
        } else if (diff < 86400) {
          return Math.floor(diff / 3600) + " giờ trước";
        } else {
          return date.toLocaleDateString("vi-VN");
        }
      } catch (e) {
        return dateString;
      }
    },

    updateDeleteButtonState: function () {
      var checkedBoxes = jQuery(".modern-notification-checkbox:checked");
      var deleteSelectedBtn = jQuery("#modern-notifications-delete-selected");
      if (checkedBoxes.length > 0) {
        deleteSelectedBtn.show();
      } else {
        deleteSelectedBtn.hide();
      }
    },

    deleteSelectedNotifications: function () {
      var self = this;
      var checkedBoxes = jQuery(".modern-notification-checkbox:checked");
      if (checkedBoxes.length === 0) {
        return;
      }

      var notificationIds = [];
      checkedBoxes.each(function () {
        notificationIds.push(parseInt(this.value));
      });

      var url = "index.php?module=Vtiger&action=DeleteNotification";
      jQuery.ajax({
        url: url,
        type: "POST",
        dataType: "json",
        cache: false, // Prevent Chrome cache issues
        xhrFields: {
          withCredentials: true, // Ensure cookies are sent (required for Chrome)
        },
        data: {
          mode: "deleteSelected",
          notification_ids: notificationIds,
        },
        success: function (response) {
          if (response && response.success) {
            // Reload current tab
            if (self.currentTab === "unread") {
              self.loadUnreadNotifications();
            } else {
              self.loadReadNotifications();
            }
            self.updateDeleteButtonState();
          }
        },
        error: function (xhr, status, error) {
          // Log error for debugging
          console.warn(
            "[ModernNotifications] Error deleting selected notifications:",
            status,
            error,
            xhr.status
          );
        },
      });
    },

    deleteAllNotifications: function () {
      var self = this;
      if (!confirm("Bạn có chắc chắn muốn xóa tất cả thông báo?")) {
        return;
      }

      var url = "index.php?module=Vtiger&action=DeleteNotification";
      jQuery.ajax({
        url: url,
        type: "POST",
        dataType: "json",
        cache: false, // Prevent Chrome cache issues
        xhrFields: {
          withCredentials: true, // Ensure cookies are sent (required for Chrome)
        },
        data: {
          mode: "deleteAll",
        },
        success: function (response) {
          if (response && response.success) {
            // Reload current tab
            if (self.currentTab === "unread") {
              self.loadUnreadNotifications();
            } else {
              self.loadReadNotifications();
            }
            self.updateDeleteButtonState();
          }
        },
        error: function (xhr, status, error) {
          // Log error for debugging
          console.warn(
            "[ModernNotifications] Error deleting all notifications:",
            status,
            error,
            xhr.status
          );
        },
      });
    },

    destroy: function () {
      if (this.intervalId) {
        clearInterval(this.intervalId);
        this.intervalId = null;
      }
      this.initialized = false;
      this.lastRenderedNotificationId = 0;
      this.previousIds = [];
      this.currentTab = "unread";
      this.isFirstLoad = true;
    },
  };

  jQuery(document).ready(function () {
    ModernNotifications.init();

    jQuery(document).on(
      "click",
      "#modern-notifications-mark-all-read",
      function (e) {
        e.preventDefault();
        e.stopPropagation();
        ModernNotifications.markAllAsRead();
      }
    );

    jQuery(document).on(
      "click",
      "#modern-notifications-delete-selected",
      function (e) {
        e.preventDefault();
        e.stopPropagation();
        ModernNotifications.deleteSelectedNotifications();
      }
    );

    jQuery(document).on(
      "click",
      "#modern-notifications-delete-all",
      function (e) {
        e.preventDefault();
        e.stopPropagation();
        ModernNotifications.deleteAllNotifications();
      }
    );
  });

  window.ModernNotifications = ModernNotifications;

  // Global manual test function for console testing
  window.__testNotificationSound = function () {
    console.log("[NotificationSound] 🧪 Manual test initiated...");
    try {
      // Get base URL same way as initSound
      var baseUrl = "";
      if (typeof _META !== "undefined" && _META.notifier) {
        var notifierUrl = _META.notifier;
        baseUrl = notifierUrl.substring(0, notifierUrl.lastIndexOf("/"));
      } else {
        var pathParts = window.location.pathname.split("/");
        pathParts = pathParts.filter(function (part) {
          return part && part !== "index.php";
        });
        baseUrl = window.location.origin;
        if (pathParts.length > 0 && pathParts[0] !== "") {
          baseUrl += "/" + pathParts[0];
        }
      }
      if (baseUrl.endsWith("/")) {
        baseUrl = baseUrl.slice(0, -1);
      }
      // CRITICAL: Use _v2 to bust browser cache
      var soundPath =
        baseUrl +
        "/layouts/v7/modules/Vtiger/resources/sounds/notification_v2.mp3";

      console.log("[NotificationSound] 🧪 Using path:", soundPath);

      var a = new Audio(soundPath);
      a.volume = 1.0;

      // Add error handlers
      a.onerror = function () {
        console.warn(
          "[NotificationSound] ❌ Manual test: Audio failed to load"
        );
        if (a.error) {
          var errorMsg = "";
          switch (a.error.code) {
            case 1:
              errorMsg = "MEDIA_ERR_ABORTED";
              break;
            case 2:
              errorMsg = "MEDIA_ERR_NETWORK";
              break;
            case 3:
              errorMsg = "MEDIA_ERR_DECODE";
              break;
            case 4:
              errorMsg = "MEDIA_ERR_SRC_NOT_SUPPORTED";
              break;
            default:
              errorMsg = "Unknown error";
          }
          console.warn(
            "[NotificationSound] Error code:",
            a.error.code,
            "(" + errorMsg + ")"
          );
        }
      };

      a.oncanplaythrough = function () {
        console.log(
          "[NotificationSound] ✅ Manual test: Audio ready to play"
        );
      };

      // CRITICAL FIX: audio.play() does NOT always return a Promise
      // Must safely check if Promise exists before calling .then()
      var playResult = a.play();
      if (playResult && typeof playResult.then === "function") {
        // Modern browser: play() returned a Promise
        playResult
          .then(function () {
            console.log(
              "[NotificationSound] 🔊 Manual test: Sound played successfully"
            );
          })
          .catch(function (err) {
            console.warn(
              "[NotificationSound] ❌ Manual test failed:",
              err.message
            );
            if (err.name === "NotAllowedError") {
              console.warn(
                "[NotificationSound] 💡 Autoplay blocked. Try clicking on the page first."
              );
            }
          });
      } else {
        // Old browser: play() returned undefined - assume it worked
        console.log(
          "[NotificationSound] ℹ️ Manual test: play() called (non-Promise return)"
        );
      }
    } catch (e) {
      console.warn(
        "[NotificationSound] ❌ Manual test: Audio API exception:",
        e.message
      );
      console.warn("[NotificationSound] Stack:", e.stack);
    }
  };

  console.log(
    "[NotificationSound] 💡 Manual test available: Run __testNotificationSound() in console"
  );
})();
