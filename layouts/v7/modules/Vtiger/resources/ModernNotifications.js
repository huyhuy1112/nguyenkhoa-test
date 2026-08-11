(function () {
  "use strict";

  var ModernNotifications = {
    previousIds: [],
    lastRenderedNotificationId: 0,
    intervalId: null,
    sound: null,
    initialized: false,
    isFirstLoad: true, // Track first load to prevent sound on page reload
    selectedIds: {},
    soundEnabled: true,
    soundVolume: 0.7,
    lastListSignature: "",
    forceNextRender: false,

    init: function () {
      if (this.initialized) {
        return;
      }

      // Initialize sound with proper path
      this.initSound();

      // Combined list: load both unread/read together
      this.loadAllNotifications();

      // Poll for new unread notifications every 3 seconds
      this.intervalId = setInterval(function () {
        // Always render combined list; badge is driven by unreadCount
        ModernNotifications.loadAllNotifications();
      }, 3000);

      // Hide legacy tabs UI (single list only)
      jQuery("#modern-notifications-tab-unread, #modern-notifications-tab-read")
        .closest(".nav-tabs")
        .hide();

      this.bindActionHandlers();

      jQuery(document).on(
        "click",
        "#modern-notifications-view-all",
        function (e) {
          e.stopPropagation();
        }
      );

      /* Keep non-interactive clicks inside the panel from closing the Bootstrap dropdown. */
      jQuery("#modern-notifications-list")
        .off("click.modernNotificationsPanel")
        .on("click.modernNotificationsPanel", function (e) {
          if (
            jQuery(e.target).closest(
              "button, a, input, label, .modern-notification-checkbox-wrap, .modern-notifications-sound-toggle"
            ).length
          ) {
            return;
          }
          e.stopPropagation();
        });

      this.initialized = true;
    },

    bindActionHandlers: function () {
      var self = this;
      var $actions = jQuery(
        "#modern-notifications-mark-all-read, #modern-notifications-delete-selected, #modern-notifications-delete-all, #modern-notifications-sound-toggle"
      );
      if (!$actions.length) {
        return;
      }

      $actions
        .off("click.modernNotificationsActions")
        .on("click.modernNotificationsActions", function (e) {
          e.preventDefault();
          e.stopPropagation();
          var id = this.id;
          if (id === "modern-notifications-mark-all-read") {
            self.markAllAsRead();
          } else if (id === "modern-notifications-delete-selected") {
            self.deleteSelectedNotifications();
          } else if (id === "modern-notifications-delete-all") {
            self.deleteAllNotifications();
          } else if (id === "modern-notifications-sound-toggle") {
            self.toggleSoundPref();
          }
        });
    },

    applySoundPrefUI: function () {
      var btn = document.getElementById("modern-notifications-sound-toggle");
      var bell = document.getElementById("modern-notifications-bell");
      var on = !!this.soundEnabled;

      if (btn) {
        btn.setAttribute("aria-pressed", on ? "true" : "false");
        btn.classList.toggle("is-muted", !on);
        btn.title = on ? "Tắt âm thanh thông báo" : "Bật âm thanh thông báo";
        var ic = btn.querySelector(".modern-notifications-sound-ic");
        if (ic) {
          ic.className =
            "fa modern-notifications-sound-ic " +
            (on ? "fa-volume-up" : "fa-volume-off");
        }
      }

      // Bell icon in navbar shows muted state too.
      if (bell) {
        bell.classList.toggle("is-sound-muted", !on);
        bell.setAttribute("data-sound", on ? "on" : "off");
        var baseTitle =
          bell.getAttribute("data-title-base") ||
          bell.getAttribute("title") ||
          "Thông báo";
        if (!bell.getAttribute("data-title-base")) {
          // Strip previous mute suffix if reloading
          baseTitle = String(baseTitle).replace(/\s*\(đã tắt âm\)\s*$/i, "");
          bell.setAttribute("data-title-base", baseTitle);
        }
        var base = bell.getAttribute("data-title-base") || "Thông báo";
        bell.setAttribute(
          "title",
          on ? base : base + " (đã tắt âm)"
        );
        if (bell.getAttribute("aria-label")) {
          bell.setAttribute(
            "aria-label",
            on ? base : base + " — đã tắt âm thanh"
          );
        }
      }

      if (this.sound) {
        try {
          this.sound.volume = Math.max(
            0,
            Math.min(1, Number(this.soundVolume) || 0.7)
          );
        } catch (e) {}
      }
    },

    toggleSoundPref: function () {
      var self = this;
      var next = !this.soundEnabled;
      jQuery.ajax({
        url: "index.php?module=Vtiger&action=Notifications&mode=setSoundPref",
        type: "POST",
        dataType: "json",
        cache: false,
        xhrFields: { withCredentials: true },
        data: {
          sound_enabled: next ? "1" : "0",
          volume: self.soundVolume,
        },
        success: function (response) {
          if (response && response.success) {
            self.soundEnabled = !!response.sound_enabled;
            if (typeof response.volume !== "undefined") {
              self.soundVolume = Number(response.volume) || 0.7;
            }
            self.applySoundPrefUI();
          }
        },
      });
    },

    checkUnreadCountOnly: function () {
      var self = this;
      var url = "index.php?module=Vtiger&action=Notifications&type=all";

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
            self.updateNotificationBadge(response.unreadCount || 0);
            // Check for new notifications to play sound and shake bell
            // Only after first load to prevent sound on page reload
            if (!self.isFirstLoad && response.list && response.list.length > 0) {
              // Only consider unread notifications for "new" detection
              self.checkForNewNotifications(
                response.list.filter(function (n) {
                  return String(n.is_read) === "0";
                })
              );
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

    loadAllNotifications: function () {
      var self = this;
      var url = "index.php?module=Vtiger&action=Notifications&type=all";
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
            if (typeof response.sound_enabled !== "undefined") {
              self.soundEnabled = !!response.sound_enabled;
            }
            if (typeof response.volume !== "undefined") {
              self.soundVolume = Number(response.volume) || 0.7;
            }
            self.applySoundPrefUI();
            self.updateAllUI(response);
            var unreadList = (response.list || []).filter(function (n) {
              return String(n.is_read) === "0";
            });
              if (!self.isFirstLoad) {
              self.checkForNewNotifications(unreadList);
              } else {
              if (unreadList.length > 0) {
                for (var i = 0; i < unreadList.length; i++) {
                  self.previousIds.push(String(unreadList[i].id));
                  }
                }
                self.isFirstLoad = false;
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

    updateNotificationBadge: function (count) {
      var badge = document.getElementById("modern-notifications-count");
      if (!badge) return;

      var markAllBtn = document.getElementById(
        "modern-notifications-mark-all-read"
      );
      var headerUnread = document.getElementById(
        "modern-notifications-header-badge"
      );
      var countNum = Number(count) || 0;

      if (countNum > 0) {
        badge.textContent = countNum;
        badge.style.display = "";
        if (markAllBtn) {
          markAllBtn.style.display = "";
        }
        if (headerUnread) {
          headerUnread.textContent = countNum + " NEW";
          headerUnread.style.display = "";
        }
      } else {
        badge.textContent = "";
        badge.style.display = "none";
        if (markAllBtn) {
          markAllBtn.style.display = "none";
        }
        if (headerUnread) {
          headerUnread.textContent = "";
          headerUnread.style.display = "none";
        }
      }
    },

    buildListSignature: function (list, unreadCount) {
      var parts = [String(unreadCount || 0)];
      for (var i = 0; i < (list || []).length; i++) {
        var n = list[i] || {};
        parts.push(
          String(n.id) +
            ":" +
            String(n.is_read) +
            ":" +
            String(n.notif_type || "") +
            ":" +
            String(n.message || "").slice(0, 80)
        );
      }
      return parts.join("|");
    },

    updateAllUI: function (response) {
      var list = response.list || [];
      var itemsContainer = jQuery("#modern-notifications-items");
      var emptyMsg = jQuery("#modern-notifications-empty-unread");
      var readEmpty = jQuery("#modern-notifications-empty-read");
      var actionsContainer = jQuery("#modern-notifications-actions");

      // single list: use unread empty as the only empty state
      readEmpty.hide();
      this.updateNotificationBadge(response.unreadCount || 0);

      // Skip full re-paint when poll returns identical list — fixes flicker.
      var signature = this.buildListSignature(list, response.unreadCount);
      if (!this.forceNextRender && signature === this.lastListSignature) {
        return;
      }
      this.forceNextRender = false;
      this.lastListSignature = signature;

      itemsContainer.empty();
      if (list.length === 0) {
        emptyMsg.show();
        itemsContainer.hide();
        actionsContainer.hide();
        return;
      }
      emptyMsg.hide();
      itemsContainer.show();
      itemsContainer.css({ display: "flex", "flex-direction": "column" });
      actionsContainer.show();

      for (var i = 0; i < list.length; i++) {
        var isRead = String(list[i].is_read) === "1";
        this.renderNotificationItem(list[i], itemsContainer[0], isRead);
      }
      this.updateDeleteButtonState();
    },

    decodeHtmlEntities: function (text) {
      if (!text) return "";
      var textarea = document.createElement("textarea");
      textarea.innerHTML = text;
      return textarea.value;
    },

    /** First line vs remainder for two-line layout (no API change). */
    splitMessageForDisplay: function (message) {
      if (!message) {
        return { title: "", body: "" };
      }
      var parts = message.split(/\r?\n/);
      var title = (parts[0] || "").trim();
      var body = parts.slice(1).join("\n").trim();
      return { title: title, body: body };
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

    resolveNotifType: function (notif, message) {
      if (notif && notif.notif_type) {
        return String(notif.notif_type);
      }
      var m = message || "";
      if (String(notif && notif.id).indexOf("cskh:") === 0) return "cskh";
      if (/đã nhắc đến bạn/i.test(m)) return "mention";
      if (/được assign|được giao|assigned/i.test(m)) return "assign";
      if (/Cần CSKH|Cần chăm sóc|sắp đến hạn|sắp hết hạn/i.test(m))
        return "cskh";
      return "other";
    },

    typeMeta: function (type) {
      var map = {
        cskh: { label: "CSKH", icon: "fa-heartbeat", cls: "type-cskh" },
        mention: { label: "Mention", icon: "fa-at", cls: "type-mention" },
        assign: { label: "Assign", icon: "fa-user-plus", cls: "type-assign" },
        reminder: { label: "Nhắc", icon: "fa-clock-o", cls: "type-reminder" },
        other: { label: "TB", icon: "fa-bell-o", cls: "type-other" },
      };
      return map[type] || map.other;
    },

    renderNotificationItem: function (notif, container, isRead) {
      var self = this;
      var module = notif.module || "Vtiger";
      var recordId = notif.recordid || "";
      var message = notif.message || "";
      var createdAt = notif.created_at || "";
      var notificationId = notif.id || "";
      var detailUrl = notif.detail_url || "";

      // Decode HTML entities in message and highlight keywords
      message = this.decodeHtmlEntities(message);
      var notifType = this.resolveNotifType(notif, message);
      var typeMeta = this.typeMeta(notifType);
      var splitMsg = this.splitMessageForDisplay(message);
      var titleHtml = this.highlightKeywords(splitMsg.title);
      var bodyHtml = splitMsg.body
        ? this.highlightKeywords(splitMsg.body)
        : "";

      if (!detailUrl && recordId) {
        detailUrl =
          "index.php?module=" + module + "&view=Detail&record=" + recordId;
      }

      var li = document.createElement("li");
      li.className =
        "modern-notification-item modern-notification-item--" + notifType;
      if (isRead) {
        li.classList.add("read-notification");
      }
      li.setAttribute("data-notification-id", notificationId);
      li.setAttribute("data-notif-type", notifType);

      if (
        message.indexOf("sắp đến hạn") !== -1 ||
        message.indexOf("sắp hết hạn") !== -1 ||
        notifType === "cskh"
      ) {
        li.classList.add("deadline-notification");
      }

      // Checkbox is ONLY for delete-selection (NOT read-state)
      var checkboxWrap = document.createElement("span");
      checkboxWrap.className = "modern-notification-checkbox-wrap";
      var checkbox = document.createElement("input");
      checkbox.type = "checkbox";
      checkbox.className = "modern-notification-checkbox";
      checkbox.value = notificationId;
      checkbox.checked = !!self.selectedIds[String(notificationId)];
      checkbox.addEventListener("click", function (e) {
        e.stopPropagation();
        if (checkbox.checked) {
          self.selectedIds[String(notificationId)] = true;
        } else {
          delete self.selectedIds[String(notificationId)];
        }
        self.updateDeleteButtonState();
      });
      checkboxWrap.appendChild(checkbox);
      li.appendChild(checkboxWrap);

      var typeBadge = document.createElement("span");
      typeBadge.className =
        "modern-notification-type " + typeMeta.cls;
      typeBadge.setAttribute("aria-label", typeMeta.label);
      typeBadge.innerHTML =
        '<span class="fa ' +
        typeMeta.icon +
        '" aria-hidden="true"></span>';
      li.appendChild(typeBadge);

      var contentWrapper = document.createElement("div");
      contentWrapper.className = "modern-notification-content";

      var inner = document.createElement("div");
      inner.className = "modern-notification-inner";

      var mainRow = document.createElement("div");
      mainRow.className = "modern-notification-main-row";

      var textBlock = document.createElement("div");
      textBlock.className = "modern-notification-text-block";

      var typeLabel = document.createElement("span");
      typeLabel.className = "modern-notification-type-label " + typeMeta.cls;
      typeLabel.textContent = typeMeta.label;

      var titleDiv = document.createElement("div");
      titleDiv.className = "modern-notification-title";
      titleDiv.innerHTML = titleHtml;

      var bodyDiv = null;
      if (bodyHtml) {
        bodyDiv = document.createElement("div");
        bodyDiv.className = "modern-notification-subtitle";
        bodyDiv.innerHTML = bodyHtml;
      }

      var dateDiv = null;
      if (createdAt) {
        dateDiv = document.createElement("div");
        dateDiv.className = "modern-notification-datetime";
        dateDiv.textContent = this.formatDate(createdAt);
      }

      if (detailUrl) {
        var link = document.createElement("a");
        link.href = detailUrl;
        link.className = "modern-notification-record-link";
        link.appendChild(typeLabel);
        link.appendChild(titleDiv);
        if (bodyDiv) {
          link.appendChild(bodyDiv);
        }
        if (dateDiv) {
          link.appendChild(dateDiv);
        }
        textBlock.appendChild(link);
      } else {
        textBlock.appendChild(typeLabel);
        textBlock.appendChild(titleDiv);
        if (bodyDiv) {
          textBlock.appendChild(bodyDiv);
        }
        if (dateDiv) {
          textBlock.appendChild(dateDiv);
        }
      }

      mainRow.appendChild(textBlock);

      if (!isRead) {
        var unreadDot = document.createElement("span");
        unreadDot.className = "modern-notification-unread-dot";
        unreadDot.setAttribute("aria-hidden", "true");
        mainRow.appendChild(unreadDot);
      }

      inner.appendChild(mainRow);
      contentWrapper.appendChild(inner);

      li.addEventListener("click", function (e) {
        if (
          e.target &&
          (e.target.tagName === "INPUT" || e.target.closest("input"))
        ) {
          return;
        }
        if (!isRead) {
          self.markAsRead(notificationId, li, checkbox);
        }
      });

      li.appendChild(contentWrapper);
      container.appendChild(li);
    },

    markAsRead: function (notificationId, element, checkboxEl) {
      var self = this;
      var url = "index.php?module=Vtiger&action=MarkNotificationRead";

      if (element) {
        element.classList.add("read-notification");
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
            var unreadCount = Number(response.unreadCount) || 0;
            self.updateNotificationBadge(unreadCount);
            // CSKH virtual items disappear after dismiss
            self.forceNextRender = true;
            if (String(notificationId).indexOf("cskh:") === 0 && element) {
              jQuery(element).remove();
              self.loadAllNotifications();
            } else if (checkboxEl) {
              checkboxEl.checked = false;
            }

            var index = self.previousIds.indexOf(String(notificationId));
            if (index > -1) {
              self.previousIds.splice(index, 1);
            }
            if (element) {
              element.classList.add("read-notification");
            }
          } else {
            if (element) {
              element.classList.remove("read-notification");
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
            element.classList.remove("read-notification");
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
            var unreadCount = Number(response.unreadCount) || 0;
            self.updateNotificationBadge(unreadCount);

            self.forceNextRender = true;
            // Reload combined list to reflect new read-state
            self.loadAllNotifications();

            self.previousIds = [];
            self.lastRenderedNotificationId = 0;
            self.lastListSignature = "";
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
        this.previousIds = [];
        return;
      }

      var newIds = [];
      for (var i = 0; i < newList.length; i++) {
        newIds.push(String(newList[i].id));
      }

      var hasNew = false;
      for (var j = 0; j < newIds.length; j++) {
        if (this.previousIds.indexOf(newIds[j]) === -1) {
          hasNew = true;
          break;
        }
      }

      if (hasNew) {
        this.playSound();
        this.shakeBell();
      }

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
      if (!this.soundEnabled) {
        return;
      }
      if (!this.sound) {
        console.warn(
          "[NotificationSound] ❌ Cannot play: Audio object not initialized"
        );
        return;
      }

      try {
        this.sound.volume = Math.max(
          0,
          Math.min(1, Number(this.soundVolume) || 0.7)
        );
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
      var checkedBoxes = jQuery(
        "#modern-notifications-items .modern-notification-checkbox:checked"
      );
      var deleteSelectedBtn = jQuery("#modern-notifications-delete-selected");
      var n = checkedBoxes.length;
      if (n > 0) {
        deleteSelectedBtn.addClass("is-visible").removeAttr("style");
        var label = deleteSelectedBtn.find(
          ".modern-notifications-action-label"
        );
        if (label.length) {
          var base = label.attr("data-base-label") || "Xóa";
          label.text(base + " (" + n + ")");
        }
      } else {
        deleteSelectedBtn.removeClass("is-visible").removeAttr("style");
        var labelOff = deleteSelectedBtn.find(
          ".modern-notifications-action-label"
        );
        if (labelOff.length) {
          labelOff.text(labelOff.attr("data-base-label") || "Xóa");
        }
      }
    },

    deleteSelectedNotifications: function () {
      var self = this;
      var checkedBoxes = jQuery(
        "#modern-notifications-items .modern-notification-checkbox:checked"
      );
      if (checkedBoxes.length === 0) {
        return;
      }

      var notificationIds = [];
      checkedBoxes.each(function () {
        notificationIds.push(String(this.value));
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
            // Clear selected cache for deleted IDs
            for (var i = 0; i < notificationIds.length; i++) {
              delete self.selectedIds[String(notificationIds[i])];
            }
            self.forceNextRender = true;
            self.lastListSignature = "";
            // Reload combined list
            self.loadAllNotifications();
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
            self.selectedIds = {};
            self.forceNextRender = true;
            self.lastListSignature = "";
            // Reload combined list
            self.loadAllNotifications();
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
      jQuery("#modern-notifications-list").off("click.modernNotificationsPanel");
      jQuery(
        "#modern-notifications-mark-all-read, #modern-notifications-delete-selected, #modern-notifications-delete-all"
      ).off("click.modernNotificationsActions");
      this.initialized = false;
      this.lastRenderedNotificationId = 0;
      this.previousIds = [];
      this.selectedIds = {};
      this.isFirstLoad = true;
      this.lastListSignature = "";
      this.forceNextRender = false;
    },
  };

  jQuery(document).ready(function () {
    ModernNotifications.init();
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
