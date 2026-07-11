/**
 * Quotes list (SALES): scroll/port, quote stage pill, creator chips, stable toolbar (no MutationObserver).
 * Scope: body[data-module="Quotes"][data-view="List"][data-app="SALES"]
 */
(function ($) {
  "use strict";

  var SEARCH_PLACEHOLDERS = {
    subject: "Subject",
    quotestage: "Quote stage",
    account_id: "Organization",
    potential_id: "Opportunity",
    contact_id: "Contact",
    total: "Total",
    hdnGrandTotal: "Total",
    assigned_user_id: "Created by",
    created_user_id: "Created by",
    smcreatorid: "Created by",
  };

  var CREATOR_SELECTORS = [
    'td[data-name="created_user_id"] .value',
    'td[data-name="smcreatorid"] .value',
    'td[data-name="assigned_user_id"] .value',
  ];

  function isQuotesSalesList() {
    var b = document.body;
    if (
      !b ||
      b.getAttribute("data-module") !== "Quotes" ||
      b.getAttribute("data-view") !== "List"
    ) {
      return false;
    }
    var appName = (b.getAttribute("data-app") || "").toUpperCase();
    if (appName === "SALES") {
      return true;
    }
    var params = new URLSearchParams(window.location.search || "");
    if (
      params.get("module") === "Quotes" &&
      params.get("view") === "List" &&
      (params.get("app") || "").toUpperCase() === "SALES"
    ) {
      return true;
    }
    /* POS shell rendered by PHP even when body data-app is empty */
    if (
      document.querySelector(
        ".mk-qt-pos-toolbar, #mk-dash-split-root[data-mk-quotes-list], .mk-so-pos-list-enabled.mk-qt-page",
      )
    ) {
      if (!appName) {
        b.setAttribute("data-app", "SALES");
      }
      return true;
    }
    return false;
  }

  function getSalesTableRoot() {
    var $root = $("#listViewContent");
    if ($root.length) {
      return $root;
    }
    return $(document.body);
  }

  function syncQtMassActionButtons() {
    var $scope = getSalesTableRoot();
    var checkedCount = $scope.find(".listViewEntriesCheckBox:checked").length;
    if (!checkedCount) {
      checkedCount = $(document).find(
        "#listview-table .listViewEntriesCheckBox:checked",
      ).length;
    }
    var hasChecked = checkedCount > 0;
    var $btns = $("#mk-qt-mass-duplicate-btn, #mk-qt-mass-delete-btn");
    if (!$btns.length) {
      ensureQtMassActionButtons();
      $btns = $("#mk-qt-mass-duplicate-btn, #mk-qt-mass-delete-btn");
    }
    $btns.toggleClass("is-visible", hasChecked);
    $btns.attr("aria-hidden", hasChecked ? "false" : "true");
    if (hasChecked) {
      $btns
        .prop("disabled", false)
        .removeAttr("disabled")
        .css({
          display: "inline-flex",
          "pointer-events": "auto",
          cursor: "pointer",
        });
    } else {
      $btns
        .prop("disabled", true)
        .attr("disabled", "disabled")
        .css("display", "none");
    }
  }

  function ensureQtMassActionButtons() {
    var $actions = $(
      ".mk-qt-pos-toolbar .mk-so-pos-toolbar__actions, .mk-so-pos-toolbar.mk-qt-pos-toolbar .mk-so-pos-toolbar__actions",
    ).first();
    if (!$actions.length) {
      return;
    }
    if (!$("#mk-qt-mass-duplicate-btn").length) {
      $actions
        .find(".mk-so-pos-btn--primary")
        .first()
        .after(
          '<button type="button" class="mk-so-pos-btn mk-so-pos-btn--outline mk-qt-pos-mass-dup-btn mk-so-pos-mass-action" id="mk-qt-mass-duplicate-btn" disabled="disabled" aria-hidden="true" title="Nhân bản các báo giá đã chọn" style="display:none"' +
            ' onclick="if (window.mkQtMassDuplicateQuotes) window.mkQtMassDuplicateQuotes(); return false;">' +
            '<i class="fa fa-copy" aria-hidden="true"></i><span>Nhân bản</span>' +
            "</button>",
        );
    }
    if (!$("#mk-qt-mass-delete-btn").length) {
      var $after = $("#mk-qt-mass-duplicate-btn");
      var $del = $(
        '<button type="button" class="mk-so-pos-btn mk-so-pos-btn--outline mk-qt-pos-mass-delete-btn mk-so-pos-mass-action" id="mk-qt-mass-delete-btn" disabled="disabled" aria-hidden="true" title="Xóa các báo giá đã chọn" style="display:none"' +
          ' onclick="if (window.mkQtMassDeleteQuotes) window.mkQtMassDeleteQuotes(); return false;">' +
          '<i class="fa fa-trash" aria-hidden="true"></i><span>Xóa</span>' +
          "</button>",
      );
      if ($after.length) {
        $after.after($del);
      } else {
        $actions.find(".mk-so-pos-btn--primary").first().after($del);
      }
    }
  }

  function bindQtSelectionEvents() {
    if ($(document).data("mkQtSelectionBound")) {
      return;
    }
    $(document).data("mkQtSelectionBound", 1);
    $(document)
      .off(
        "change.mkQtRowCheck click.mkQtRowCheck",
        ".listViewEntriesCheckBox, .listViewEntriesMainCheckBox",
      )
      .on(
        "change.mkQtRowCheck click.mkQtRowCheck",
        ".listViewEntriesCheckBox, .listViewEntriesMainCheckBox",
        function () {
          if (!isQuotesSalesList()) {
            return;
          }
          syncRowSelectedIfNeeded();
          setTimeout(syncQtMassActionButtons, 0);
          setTimeout(syncQtMassActionButtons, 50);
        },
      );
  }

  function isPosListEnabled() {
    return !!document.querySelector(
      "#listViewContent .mk-so-pos-list-enabled, .mk-so-pos-page",
    );
  }

  var qtPosSearchTimer = null;
  var qtLastSearchPayload = "";
  var qtLiveSearchQuery = "";
  var qtPosSearchBound = false;
  var qtExpandedRecordId = null;
  var qtInlineLoading = false;

  function getListViewContainer() {
    return $("#listViewContent");
  }

  function decodeHtmlEntities(text) {
    var value = String(text || "");
    if (!value) {
      return "";
    }
    var el = document.createElement("textarea");
    var prev = null;
    var guard = 0;
    while (value !== prev && guard < 6) {
      prev = value;
      if (!/&(?:#x?[0-9a-f]+|[a-z]+);/i.test(value)) {
        break;
      }
      el.innerHTML = value;
      value = el.value;
      guard += 1;
    }
    return value;
  }

  var TEXT_DECODE_FIELDS = [
    "account_id",
    "accountname",
    "potential_id",
    "subject",
    "contact_id",
  ];

  function fixEncodedTextCells($table) {
    if (!$table || !$table.length) {
      return;
    }
    TEXT_DECODE_FIELDS.forEach(function (fieldName) {
      $table.find('tbody td[data-name="' + fieldName + '"]').each(function () {
        var $td = $(this);
        var title = $td.attr("title");
        if (title && /&(?:#x?[0-9a-f]+|[a-z]+);/i.test(title)) {
          $td.attr("title", decodeHtmlEntities(title));
        }
        var $targets = $td.find(".value, a");
        if (!$targets.length) {
          $targets = $td;
        }
        $targets.each(function () {
          var $node = $(this);
          if ($node.data("mkDecoded")) {
            return;
          }
          var raw = $.trim($node.text());
          if (!raw || !/&(?:#x?[0-9a-f]+|[a-z]+);/i.test(raw)) {
            return;
          }
          var decoded = decodeHtmlEntities(raw);
          if (decoded !== raw) {
            $node.text(decoded);
            $node.data("mkDecoded", 1);
          }
        });
      });
    });
  }

  function fixCurrencySpacing($table) {
    if (!$table || !$table.length) {
      return;
    }
    $table
      .find(
        'tbody td[data-name="total"] .value, tbody td[data-name="hdnGrandTotal"] .value',
      )
      .each(function () {
        var $node = $(this);
        if ($node.data("mkCurrencyFixed")) {
          return;
        }
        var raw = String($node.text() || "");
        if (!raw) {
          return;
        }
        // Insert a thin gap between ₫ (or other currency symbol) and the amount.
        var fixed = raw
          .replace(/([₫$€£¥₩₹]|VND)\s*(?=[\d.,])/gi, "$1\u2009")
          .replace(/([\d.,])\s*([₫$€£¥₩₹]|VND)/gi, "$1\u2009$2");
        if (fixed !== raw) {
          $node.text(fixed);
        }
        $node.data("mkCurrencyFixed", 1);
      });
  }

  function getTableColspan($table) {
    var count = $table.find("thead tr.listViewContentHeader th").length;
    return count > 0 ? count : 1;
  }

  function collapseInlineDetail($table) {
    if (!$table || !$table.length) {
      qtExpandedRecordId = null;
      return;
    }
    $table.find("tr.mk-so-inline-detail-row").remove();
    $table
      .find("tr.listViewEntries.mk-so-row-expanded")
      .removeClass("mk-so-row-expanded");
    qtExpandedRecordId = null;
  }

  function getRowRecordId($row) {
    if (!$row || !$row.length) {
      return "";
    }
    var id = $row.data("id");
    if (id === undefined || id === null || id === "") {
      id = $row.attr("data-id");
    }
    return id != null ? String(id) : "";
  }

  function unregisterVtigerRowNavigation($container) {
    if (!$container || !$container.length) {
      return;
    }
    $container.off("click", ".listViewEntries");
    $container.off("click", ".listViewEntries a");
    $container.find(".listViewEntries a").each(function () {
      var $link = $(this);
      var timer = $link.data("timer");
      if (timer) {
        clearTimeout(timer);
        $link.removeData("timer");
      }
    });
  }

  function isInlineDetailInteraction(target) {
    var $target = $(target);
    if (!$target.length) {
      return false;
    }
    if (
      $target.closest(
        ".mk-so-inline-detail, .mk-so-inline-detail-row, .mk-so-pos-star-btn, .mk-so-pos-dup-btn, .mk-so-pos-check, .mk-so-pos-control-td, .listViewEntriesCheckBox, .listViewEntriesMainCheckBox",
      ).length
    ) {
      return true;
    }
    if ($target.is('input[type="checkbox"]')) {
      return true;
    }
    return false;
  }

  function closeInlineExcelPreview() {
    var $modal = $("#mk-qt-inline-excel-preview");
    $modal.removeClass("is-open").attr("aria-hidden", "true");
    $("body").removeClass("mk-so-inline-excel-open");
    $(".mk-so-inline-detail__export-btn")
      .attr("data-export-ready", "0")
      .find(".mk-so-inline-detail__export-label")
      .text("Export Excel");
  }

  function ensureInlineExcelPreviewModal() {
    var $modal = $("#mk-qt-inline-excel-preview");
    if ($modal.length) {
      return $modal;
    }
    $modal = $(
      '<div id="mk-qt-inline-excel-preview" class="mk-so-inline-excel-preview" aria-hidden="true">' +
        '<div class="mk-so-inline-excel-preview__dialog" role="dialog" aria-labelledby="mk-qt-inline-excel-title">' +
        '<div class="mk-so-inline-excel-preview__head">' +
        '<h3 id="mk-qt-inline-excel-title">Xem trước Excel</h3>' +
        '<button type="button" class="mk-so-inline-excel-preview__close" aria-label="Đóng">&times;</button>' +
        "</div>" +
        '<div class="mk-so-inline-excel-preview__body"></div>' +
        '<div class="mk-so-inline-excel-preview__foot">' +
        '<button type="button" class="mk-so-inline-excel-preview__cancel">Đóng</button>' +
        '<button type="button" class="mk-so-inline-excel-preview__download">Tải Excel</button>' +
        "</div>" +
        "</div>" +
        "</div>",
    );
    $("body").append($modal);
    $modal.on(
      "click",
      ".mk-so-inline-excel-preview__close, .mk-so-inline-excel-preview__cancel",
      function (e) {
        e.preventDefault();
        closeInlineExcelPreview();
      },
    );
    $modal.on("click", function (e) {
      if ($(e.target).is("#mk-qt-inline-excel-preview")) {
        closeInlineExcelPreview();
      }
    });
    return $modal;
  }

  function csvEscape(value) {
    var text = String(value == null ? "" : value).replace(/"/g, '""');
    if (/[",\n\r]/.test(text)) {
      return '"' + text + '"';
    }
    return text;
  }

  function buildInlineExportRows($panel) {
    var rows = [];
    var quoteNo = $.trim($panel.find(".mk-so-inline-detail__order-no").text());
    var customer = $.trim(
      $panel.find(".mk-so-inline-detail__customer-name").text(),
    );
    rows.push(["Mã báo giá", quoteNo]);
    rows.push(["Khách hàng", customer]);
    $panel.find(".mk-so-inline-detail__field").each(function () {
      var $field = $(this);
      var label = $.trim(
        $field.find(".mk-so-inline-detail__field-label").text(),
      );
      var value = $.trim(
        $field.find(".mk-so-inline-detail__field-view").text(),
      );
      if (label) {
        rows.push([label, value]);
      }
    });
    rows.push([]);
    rows.push([
      "Mã hàng",
      "Tên hàng",
      "Số lượng",
      "Đơn giá",
      "Giảm giá",
      "Giá bán",
      "Thành tiền",
    ]);
    $panel.find(".mk-so-inline-detail__lines tbody tr").each(function () {
      var $cells = $(this).find("td");
      if (
        $cells.length < 7 ||
        $cells.first().hasClass("mk-so-inline-detail__empty-lines")
      ) {
        return;
      }
      rows.push([
        $.trim($cells.eq(0).text()),
        $.trim($cells.eq(1).text()),
        $.trim($cells.eq(2).text()),
        $.trim($cells.eq(3).text()),
        $.trim($cells.eq(4).text()),
        $.trim($cells.eq(5).text()),
        $.trim($cells.eq(6).text()),
      ]);
    });
    rows.push([]);
    $panel.find(".mk-so-inline-detail__total-row").each(function () {
      var $row = $(this);
      rows.push([
        $.trim($row.find("span").first().text()),
        $.trim($row.find("strong").text()),
      ]);
    });
    rows.push([
      "Ghi chú",
      $.trim($panel.find(".mk-so-inline-detail__notes-input").val()),
    ]);
    return rows;
  }

  function parseInlineMoney(text) {
    if (window.MkCurrency && typeof MkCurrency.parse === "function") {
      var parsed = MkCurrency.parse(text);
      return isNaN(parsed) ? 0 : parsed;
    }
    var n = parseFloat(
      String(text || "")
        .replace(/[^\d,.-]/g, "")
        .replace(/\./g, "")
        .replace(/,/g, "."),
    );
    return isNaN(n) ? 0 : n;
  }

  function formatInlineMoney(amount) {
    if (window.MkCurrency && typeof MkCurrency.format === "function") {
      return MkCurrency.format(amount, { decimals: 0 });
    }
    return String(Math.round(amount || 0)).replace(/\B(?=(\d{3})+(?!\d))/g, ".");
  }

  function buildInlineExportPreviewHtml($panel) {
    var quoteNo = $.trim($panel.find(".mk-so-inline-detail__order-no").text());
    var customer = $.trim(
      $panel.find(".mk-so-inline-detail__customer-name").text(),
    );
    var notes = $.trim($panel.find(".mk-so-inline-detail__notes-input").val());
    var amountWords = $.trim($panel.attr("data-amount-words") || "");
    var createdDate = $.trim($panel.attr("data-created-date") || "");
    var phone = "";
    var address = "";
    $panel.find(".mk-so-inline-detail__field").each(function () {
      var label = $.trim(
        $(this).find(".mk-so-inline-detail__field-label").text(),
      ).toLowerCase();
      var value = $.trim(
        $(this).find(".mk-so-inline-detail__field-view").text(),
      );
      if (
        !phone &&
        (label.indexOf("sđt") >= 0 ||
          label.indexOf("phone") >= 0 ||
          label.indexOf("điện thoại") >= 0)
      ) {
        phone = value;
      }
      if (
        !address &&
        (label.indexOf("địa chỉ") >= 0 || label.indexOf("address") >= 0)
      ) {
        address = value;
      }
    });
    var lines = [];
    $panel.find(".mk-so-inline-detail__lines tbody tr").each(function () {
      var $row = $(this);
      var $cells = $row.find("td");
      if (
        $cells.length < 7 ||
        $cells.first().hasClass("mk-so-inline-detail__empty-lines")
      ) {
        return;
      }
      var qtyText =
        $row.attr("data-qty") ||
        $.trim($cells.filter(".is-num").eq(0).text()) ||
        $.trim($cells.eq(2).text());
      var listPriceText = $row.attr("data-price") || $.trim($cells.eq(3).text());
      var salePriceText = $.trim($cells.eq(5).text());
      var totalText =
        $row.attr("data-total") ||
        $.trim($cells.filter(".is-total").text()) ||
        $.trim($cells.eq(6).text());
      var qtyNum = parseInlineMoney(qtyText);
      if (qtyNum <= 0) {
        qtyNum = 1;
      }
      var priceNum =
        parseInlineMoney(listPriceText) || parseInlineMoney(salePriceText);
      var totalNum = parseInlineMoney(totalText);
      if (totalNum <= 0 && priceNum > 0) {
        totalNum = priceNum * qtyNum;
      }
      if (priceNum <= 0 && totalNum > 0) {
        priceNum = totalNum / qtyNum;
      }
      lines.push({
        name: $.trim($cells.eq(1).text()),
        unit: $.trim($row.attr("data-unit") || ""),
        qty: qtyNum,
        price: priceNum,
        total: totalNum,
      });
    });
    var totals = [];
    var footerSubTotal = 0;
    var lineTotalSum = 0;
    lines.forEach(function (line) {
      lineTotalSum += line.total || 0;
    });
    $panel.find(".mk-so-inline-detail__total-row").each(function () {
      var $row = $(this);
      var label = $.trim(
        $row.find(".mk-so-inline-detail__total-label, span").first().text(),
      );
      var value = $.trim(
        $row.find(".mk-so-inline-detail__total-value, strong").first().text(),
      );
      if (!label) {
        return;
      }
      if (label.indexOf("Giảm giá") === 0) {
        label = "Chiết khấu";
      } else if (
        label.indexOf("Tổng cộng") === 0 ||
        label.indexOf("Tổng thanh toán") === 0
      ) {
        label = "Tổng thanh toán";
      } else if (label.indexOf("Tổng tiền hàng") === 0) {
        footerSubTotal = parseInlineMoney(value);
      }
      totals.push({
        label: label,
        value: value,
        grand:
          $row.hasClass("mk-so-inline-detail__total-row--grand") ||
          label.indexOf("Tổng thanh toán") === 0,
      });
    });
    if (footerSubTotal > 0 && lineTotalSum <= 0 && lines.length === 1) {
      lines[0].total = footerSubTotal;
      if (lines[0].qty > 0) {
        lines[0].price = footerSubTotal / lines[0].qty;
      }
    } else if (footerSubTotal > 0 && lineTotalSum <= 0 && lines.length > 1) {
      var share = footerSubTotal / lines.length;
      lines.forEach(function (line) {
        line.total = share;
        line.price = line.qty > 0 ? share / line.qty : share;
      });
    }
    var esc = function (text) {
      return $("<div>")
        .text(text || "")
        .html();
    };
    var formatDateVi = function (raw) {
      if (!raw) {
        return "";
      }
      var m = String(raw).match(/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/);
      if (m) {
        return "Ngày " + m[1] + " tháng " + m[2] + " năm " + m[3];
      }
      return raw;
    };
    var formatProductLabel = function (line) {
      var name = line.name || "";
      var unit = $.trim(line.unit || "");
      if (unit && name.toLowerCase().indexOf("(" + unit.toLowerCase() + ")") < 0) {
        return name + " (" + unit + ")";
      }
      return name;
    };

    var html = '<div class="mk-so-excel-sheet">';
    html += '<div class="mk-so-excel-sheet__header">';
    html +=
      '<div class="mk-so-excel-sheet__logo"><img src="layouts/v7/modules/Quotes/resources/images/nguyenkhoa-excel-logo.png" alt="Nguyên Khoa" /></div>';
    html += '<div class="mk-so-excel-sheet__company">nguyenlieuphachemt</div>';
    html +=
      '<div class="mk-so-excel-sheet__company-meta">6/24 Đường số 3, Cư Xá Lữ Gia, Phú Thọ, Hồ Chí Minh<br>0973969498</div>';
    html += '<div class="mk-so-excel-sheet__doc-title">BÁO GIÁ</div>';
    html +=
      '<div class="mk-so-excel-sheet__doc-meta">Mã báo giá: ' +
      esc(quoteNo) +
      "</div>";
    if (createdDate) {
      html +=
        '<div class="mk-so-excel-sheet__doc-meta">' +
        esc(formatDateVi(createdDate)) +
        "</div>";
    }
    html += "</div>";
    html += '<div class="mk-so-excel-sheet__customer">';
    html +=
      "<div><strong>Khách hàng:</strong> " + esc(customer || "—") + "</div>";
    html += "<div><strong>SĐT:</strong> " + esc(phone || "—") + "</div>";
    html += "<div><strong>Địa chỉ:</strong> " + esc(address || "—") + "</div>";
    html += "<div><strong>Ghi chú:</strong> " + esc(notes || "—") + "</div>";
    html += "</div>";
    html += '<table class="mk-so-excel-sheet__invoice-table">';
    html +=
      '<thead><tr><th class="is-item">Đơn giá</th><th class="is-qty">SL</th><th class="is-money">T.Tiền</th></tr></thead><tbody>';
    if (!lines.length) {
      html +=
        '<tr><td colspan="3" class="is-empty">Chưa có hàng hóa trong báo giá.</td></tr>';
    } else {
      lines.forEach(function (line) {
        html += "<tr>";
        html +=
          '<td class="is-item-name"><div class="mk-so-excel-sheet__item-title">' +
          esc(formatProductLabel(line)) +
          '</div><div class="mk-so-excel-sheet__item-price">' +
          esc(formatInlineMoney(line.price)) +
          "</div></td>";
        html +=
          '<td class="is-qty">' +
          esc(String(line.qty % 1 === 0 ? Math.round(line.qty) : line.qty)) +
          "</td>";
        html +=
          '<td class="is-money">' + esc(formatInlineMoney(line.total)) + "</td>";
        html += "</tr>";
      });
    }
    html += "</tbody></table>";
    html += '<hr class="mk-so-excel-sheet__divider" />';
    html += '<div class="mk-so-excel-sheet__bottom">';
    html +=
      '<p class="mk-so-excel-sheet__words">' +
      (amountWords ? "(" + esc(amountWords) + ")" : "") +
      "</p>";
    html += '<div class="mk-so-excel-sheet__totals">';
    totals.forEach(function (item) {
      html +=
        '<div class="mk-so-excel-sheet__total-row' +
        (item.grand ? " is-grand" : "") +
        '">';
      html +=
        "<span>" +
        esc(item.label) +
        ":</span><strong>" +
        esc(item.value) +
        "</strong></div>";
    });
    html += "</div></div>";
    html += '<p class="mk-so-excel-sheet__thanks">Cảm ơn và hẹn gặp lại!</p>';
    html += "</div>";
    return html;
  }

  function downloadInlineExportXlsx($panel, recordId) {
    var excelUrl = $.trim($panel.attr("data-excel-url") || "");
    if (!excelUrl) {
      excelUrl =
        "index.php?module=Quotes&action=ExportExcelForSale&record=" +
        encodeURIComponent(recordId);
    }
    var $frame = $("#mk-qt-inline-excel-download-frame");
    if (!$frame.length) {
      $frame = $(
        '<iframe id="mk-qt-inline-excel-download-frame" class="mk-so-inline-print-download-frame" title="Tải Excel báo giá"></iframe>',
      );
      $("body").append($frame);
    }
    $frame.attr("src", "about:blank");
    setTimeout(function () {
      $frame.attr("src", excelUrl);
    }, 0);
  }

  function openInlineExcelPreview($panel, recordId) {
    var $modal = ensureInlineExcelPreviewModal();
    $modal
      .find(".mk-so-inline-excel-preview__body")
      .html(buildInlineExportPreviewHtml($panel));
    $modal.data("mkExportRecordId", recordId);
    $modal.data("mkExportPanel", $panel);
    $modal.addClass("is-open").attr("aria-hidden", "false");
    $("body").addClass("mk-so-inline-excel-open");
    $modal
      .off("click.mkQtExcelDownload")
      .on(
        "click.mkQtExcelDownload",
        ".mk-so-inline-excel-preview__download",
        function (e) {
          e.preventDefault();
          downloadInlineExportXlsx($panel, recordId);
          closeInlineExcelPreview();
        },
      );
  }

  function closeInlinePrintPreview() {
    var $modal = $("#mk-qt-inline-print-preview");
    $modal.removeClass("is-open").attr("aria-hidden", "true");
    $modal.find("iframe").attr("src", "about:blank");
    $("body").removeClass("mk-so-inline-print-open");
    $(".mk-so-inline-detail__print-btn")
      .attr("data-print-ready", "0")
      .find(".mk-so-inline-detail__print-label")
      .text("In");
  }

  function ensureInlinePrintPreviewModal() {
    var $modal = $("#mk-qt-inline-print-preview");
    if ($modal.length) {
      return $modal;
    }
    $modal = $(
      '<div id="mk-qt-inline-print-preview" class="mk-so-inline-print-preview" aria-hidden="true">' +
        '<div class="mk-so-inline-print-preview__dialog" role="dialog" aria-labelledby="mk-qt-inline-print-title">' +
        '<div class="mk-so-inline-print-preview__head">' +
        '<h3 id="mk-qt-inline-print-title">Xem trước bản in</h3>' +
        '<button type="button" class="mk-so-inline-print-preview__close" aria-label="Đóng">&times;</button>' +
        "</div>" +
        '<div class="mk-so-inline-print-preview__body">' +
        '<iframe class="mk-so-inline-print-preview__frame" title="Xem trước PDF báo giá"></iframe>' +
        "</div>" +
        '<div class="mk-so-inline-print-preview__foot">' +
        '<button type="button" class="mk-so-inline-print-preview__cancel">Đóng</button>' +
        '<button type="button" class="mk-so-inline-print-preview__print"><i class="fa fa-print" aria-hidden="true"></i> In ngay</button>' +
        '<button type="button" class="mk-so-inline-print-preview__download"><i class="fa fa-download" aria-hidden="true"></i> Tải PDF</button>' +
        "</div>" +
        "</div>" +
        "</div>",
    );
    $("body").append($modal);
    $modal.on(
      "click",
      ".mk-so-inline-print-preview__close, .mk-so-inline-print-preview__cancel",
      function (e) {
        e.preventDefault();
        closeInlinePrintPreview();
      },
    );
    $modal.on("click", function (e) {
      if ($(e.target).is("#mk-qt-inline-print-preview")) {
        closeInlinePrintPreview();
      }
    });
    $modal.on("click", ".mk-so-inline-print-preview__print", function (e) {
      e.preventDefault();
      executeInlinePrintFromPreview();
    });
    $modal.on("click", ".mk-so-inline-print-preview__download", function (e) {
      e.preventDefault();
      var $panel = $modal.data("mkPrintPanel");
      var recordId = $modal.data("mkPrintRecordId");
      if ($panel && recordId) {
        downloadInlinePrintPdf($panel, recordId);
      }
      closeInlinePrintPreview();
    });
    return $modal;
  }

  function getInlinePrintPreviewUrl($panel, recordId) {
    var printUrl =
      $panel.data("print-url") ||
      $panel.find(".mk-so-inline-detail__print-btn").data("print-url");
    if (!printUrl) {
      printUrl =
        "index.php?module=Quotes&action=ExportPDF&record=" +
        encodeURIComponent(recordId) +
        "&preview=1";
    }
    return printUrl;
  }

  function getInlinePrintDownloadUrl($panel, recordId) {
    var downloadUrl =
      $panel.data("print-download-url") ||
      $panel.find(".mk-so-inline-detail__print-btn").data("print-download-url");
    if (!downloadUrl) {
      downloadUrl =
        "index.php?module=Quotes&action=ExportPDF&record=" +
        encodeURIComponent(recordId);
    }
    return downloadUrl;
  }

  function openInlinePrintPreview($panel, recordId) {
    var printUrl = getInlinePrintPreviewUrl($panel, recordId);
    var $modal = ensureInlinePrintPreviewModal();
    $modal.data("mkPrintPanel", $panel);
    $modal.data("mkPrintRecordId", recordId);
    $modal.find("iframe").attr("src", printUrl);
    $modal.addClass("is-open").attr("aria-hidden", "false");
    $("body").addClass("mk-so-inline-print-open");
  }

  function downloadInlinePrintPdf($panel, recordId) {
    var downloadUrl = getInlinePrintDownloadUrl($panel, recordId);
    var $frame = $("#mk-qt-inline-print-download-frame");
    if (!$frame.length) {
      $frame = $(
        '<iframe id="mk-qt-inline-print-download-frame" class="mk-so-inline-print-download-frame" title="Tải PDF báo giá"></iframe>',
      );
      $("body").append($frame);
    }
    $frame.attr("src", downloadUrl);
  }

  function executeInlinePrintFromPreview() {
    var $iframe = $("#mk-qt-inline-print-preview iframe");
    if (!$iframe.length) {
      return;
    }
    try {
      var frameWindow = $iframe[0].contentWindow;
      if (frameWindow) {
        frameWindow.focus();
        frameWindow.print();
      }
    } catch (err) {
      var src = $iframe.attr("src");
      if (src && src !== "about:blank") {
        window.open(src, "_blank");
      }
    }
  }

  function triggerInlinePrint($panel, recordId, $btn) {
    var ready = $btn && String($btn.attr("data-print-ready")) === "1";
    if (!ready) {
      openInlinePrintPreview($panel, recordId);
      if ($btn) {
        $btn.attr("data-print-ready", "1");
        $btn.find(".mk-so-inline-detail__print-label").text("Tải PDF");
      }
      return;
    }
    downloadInlinePrintPdf($panel, recordId);
    closeInlinePrintPreview();
    if ($btn) {
      $btn.attr("data-print-ready", "0");
      $btn.find(".mk-so-inline-detail__print-label").text("In");
    }
  }

  function initInlineDetailPanel($container) {
    var $panel = $container.find(".mk-so-inline-detail").first();
    if (!$panel.length || $panel.data("mkQtInlineInit")) {
      return;
    }
    $panel.data("mkQtInlineInit", true);

    var recordId = String($panel.data("record-id") || "");

    $panel.on("click", ".mk-so-inline-detail__view-full-btn", function (e) {
      e.preventDefault();
      e.stopPropagation();
      var detailUrl = $panel.data("detail-url");
      if (!detailUrl) {
        detailUrl =
          "index.php?module=Quotes&view=Detail&record=" +
          encodeURIComponent(recordId) +
          "&app=SALES";
      }
      window.location.href = detailUrl;
    });

    $panel.on("click", ".mk-so-inline-detail__process-btn", function (e) {
      e.preventDefault();
      e.stopPropagation();
      if (!recordId) {
        return;
      }
      var editUrl = $panel.data("edit-url");
      if (!editUrl) {
        editUrl =
          "index.php?module=Quotes&view=Edit&record=" +
          encodeURIComponent(recordId) +
          "&app=SALES";
      }
      window.location.href = editUrl;
    });

    $panel.on("click", ".mk-so-inline-detail__confirm-order-btn", function (e) {
      e.preventDefault();
      e.stopPropagation();
      if (!recordId) {
        return;
      }
      var $btn = $(this);
      if ($btn.data("mkBusy") || window.__mkQuoteConfirmBusy) {
        return;
      }
      if (
        !window.confirm(
          "Xác nhận chuyển báo giá thành đơn hàng?",
        )
      ) {
        return;
      }
      window.__mkQuoteConfirmBusy = true;
      $btn.data("mkBusy", 1).prop("disabled", true).addClass("is-busy");
      $panel
        .find(".mk-so-inline-detail__confirm-order-btn")
        .prop("disabled", true);
      var csrf = "";
      if (typeof app !== "undefined") {
        if (typeof app.getCsrfToken === "function") {
          csrf = app.getCsrfToken();
        } else if (typeof csrfMagicToken !== "undefined") {
          csrf = csrfMagicToken;
        } else if (typeof csrfMagicName !== "undefined") {
          csrf = jQuery('[name="' + csrfMagicName + '"]').val() || "";
        }
      }
      var postData = {
        module: "Quotes",
        action: "ConfirmSalesOrder",
        record: recordId,
      };
      if (csrf) {
        postData.__vtrftk = csrf;
      }
      $.ajax({
        url: "index.php",
        type: "POST",
        dataType: "json",
        data: postData,
      })
        .done(function (resp) {
          var result = resp && resp.result ? resp.result : resp;
          var errMsg = "";
          if (resp && resp.success === false) {
            errMsg =
              (resp.error && (resp.error.message || resp.error)) ||
              "Không tạo được đơn hàng từ báo giá.";
          } else if (!result || result.success === false) {
            errMsg =
              result && result.message
                ? result.message
                : resp && resp.error && resp.error.message
                  ? resp.error.message
                  : "Không tạo được đơn hàng từ báo giá.";
          }
          if (errMsg) {
            if (
              typeof app !== "undefined" &&
              app.helper &&
              app.helper.showErrorNotification
            ) {
              app.helper.showErrorNotification({ message: String(errMsg) });
            } else {
              window.alert(String(errMsg));
            }
            window.__mkQuoteConfirmBusy = false;
            $btn
              .data("mkBusy", 0)
              .prop("disabled", false)
              .removeClass("is-busy");
            $panel
              .find(".mk-so-inline-detail__confirm-order-btn")
              .prop("disabled", false);
            return;
          }
          var soUrl =
            result.list_url ||
            "index.php?module=SalesOrder&view=List&app=SALES";
          if (
            typeof app !== "undefined" &&
            app.helper &&
            app.helper.showSuccessNotification
          ) {
            app.helper.showSuccessNotification({
              message: result.already_exists
                ? "Đơn hàng đã tồn tại. Đang mở danh sách đơn hàng..."
                : "Đã tạo đơn hàng (Phiếu tạm). Đang mở danh sách đơn hàng...",
            });
          }
          window.location.href = soUrl;
        })
        .fail(function (xhr) {
          var msg = "Không tạo được đơn hàng từ báo giá.";
          try {
            var parsed = JSON.parse(xhr.responseText || "{}");
            if (parsed && parsed.error) {
              msg = parsed.error.message || parsed.error || msg;
            } else if (parsed && parsed.result && parsed.result.message) {
              msg = parsed.result.message;
            }
          } catch (ignore) {
            /* ignore */
          }
          if (
            typeof app !== "undefined" &&
            app.helper &&
            app.helper.showErrorNotification
          ) {
            app.helper.showErrorNotification({ message: String(msg) });
          } else {
            window.alert(String(msg));
          }
          window.__mkQuoteConfirmBusy = false;
          $btn.data("mkBusy", 0).prop("disabled", false).removeClass("is-busy");
          $panel
            .find(".mk-so-inline-detail__confirm-order-btn")
            .prop("disabled", false);
        });
    });

    $panel.on("click", ".mk-so-inline-detail__print-btn", function (e) {
      e.preventDefault();
      e.stopPropagation();
      triggerInlinePrint($panel, recordId, $(this));
    });

    $panel.on("click", ".mk-so-inline-detail__export-btn", function (e) {
      e.preventDefault();
      e.stopPropagation();
      if (!recordId) {
        return;
      }
      var $btn = $(this);
      var ready = String($btn.attr("data-export-ready")) === "1";
      if (!ready) {
        openInlineExcelPreview($panel, recordId);
        $btn.attr("data-export-ready", "1");
        $btn.find(".mk-so-inline-detail__export-label").text("Tải Excel");
        return;
      }
      downloadInlineExportXlsx($panel, recordId);
      closeInlineExcelPreview();
      $btn.attr("data-export-ready", "0");
      $btn.find(".mk-so-inline-detail__export-label").text("Export Excel");
    });
  }

  function loadInlineDetail(recordId, $row, $table) {
    var colspan = getTableColspan($table);
    var $detailRow = $(
      '<tr class="mk-so-inline-detail-row">' +
        '<td colspan="' +
        colspan +
        '">' +
        '<div class="mk-so-inline-detail mk-so-inline-detail--loading">' +
        '<span class="mk-so-inline-detail__spinner" aria-hidden="true"></span>' +
        "<span>Đang tải chi tiết báo giá...</span>" +
        "</div>" +
        "</td>" +
        "</tr>",
    );
    $row.after($detailRow);

    $.ajax({
      url: "index.php",
      type: "GET",
      dataType: "html",
      data: {
        module: "Quotes",
        view: "Detail",
        mode: "showListInlineDetail",
        record: recordId,
        app: "SALES",
      },
    })
      .done(function (html) {
        if (qtExpandedRecordId !== String(recordId)) {
          return;
        }
        $detailRow.find("td").html(html);
        initInlineDetailPanel($detailRow);
      })
      .fail(function () {
        if (qtExpandedRecordId !== String(recordId)) {
          return;
        }
        $detailRow
          .find("td")
          .html(
            '<div class="mk-so-inline-detail mk-so-inline-detail--error">' +
              "Không tải được chi tiết báo giá. " +
              '<a href="' +
              ($row.data("recordurl") || "#") +
              '">Mở trang chi tiết</a>.' +
              "</div>",
          );
      })
      .always(function () {
        qtInlineLoading = false;
      });
  }

  function toggleInlineDetail(recordId, $row) {
    var $table = $row.closest("#listview-table");
    if (!$table.length) {
      return;
    }
    recordId = String(recordId || "");
    if (!recordId) {
      return;
    }
    if (qtExpandedRecordId === recordId) {
      collapseInlineDetail($table);
      return;
    }
    if (qtInlineLoading) {
      return;
    }
    collapseInlineDetail($table);
    qtExpandedRecordId = recordId;
    qtInlineLoading = true;
    $row.addClass("mk-so-row-expanded");
    loadInlineDetail(recordId, $row, $table);
  }

  function handleInlineDetailClick(e) {
    if (!isQuotesSalesList() || !isPosListEnabled()) {
      return false;
    }
    var target = e.target;
    if (!target || !target.closest) {
      return false;
    }
    if (isInlineDetailInteraction(target)) {
      return false;
    }
    var row = target.closest("#listview-table tr.listViewEntries");
    if (!row) {
      return false;
    }
    if (target.closest(".mk-so-inline-detail")) {
      return false;
    }
    if (target.closest("td:first-child")) {
      return false;
    }
    if (
      window.getSelection &&
      String(window.getSelection()).trim().length > 0
    ) {
      return false;
    }
    if ($(row).hasClass("edited")) {
      return false;
    }

    unregisterVtigerRowNavigation(getListViewContainer());

    if (e && typeof e.preventDefault === "function") {
      e.preventDefault();
    }
    if (e && typeof e.stopPropagation === "function") {
      e.stopPropagation();
    }
    if (e && typeof e.stopImmediatePropagation === "function") {
      e.stopImmediatePropagation();
    }

    toggleInlineDetail(getRowRecordId($(row)), $(row));
    return true;
  }

  function registerQuotesPosRowClickEvent(listInstance) {
    var $container =
      listInstance && listInstance.getListViewContainer
        ? listInstance.getListViewContainer()
        : getListViewContainer();
    unregisterVtigerRowNavigation($container);
  }

  function patchInlineDetailRowClick() {
    if (
      typeof Vtiger_List_Js === "undefined" ||
      Vtiger_List_Js.prototype._mkQtInlineDetailPatched
    ) {
      return;
    }
    Vtiger_List_Js.prototype._mkQtInlineDetailPatched = true;
    var originalRegister = Vtiger_List_Js.prototype.registerRowClickEvent;
    Vtiger_List_Js.prototype.registerRowClickEvent = function () {
      if (!isQuotesSalesList()) {
        return originalRegister.call(this);
      }
      registerQuotesPosRowClickEvent(this);
    };
    var originalRegisterEvents = Vtiger_List_Js.prototype.registerEvents;
    if (originalRegisterEvents) {
      Vtiger_List_Js.prototype.registerEvents = function () {
        var result = originalRegisterEvents.apply(this, arguments);
        if (isQuotesSalesList()) {
          registerQuotesPosRowClickEvent(this);
        }
        return result;
      };
    }
  }

  function bindInlineDetailCapture() {
    if (
      document.documentElement.getAttribute("data-mk-qt-inline-detail-bound")
    ) {
      return;
    }
    document.documentElement.setAttribute(
      "data-mk-qt-inline-detail-bound",
      "1",
    );
    document.addEventListener(
      "click",
      function (e) {
        handleInlineDetailClick(e);
      },
      true,
    );
  }

  function loadPosLikeList(extraParams) {
    var listInstance =
      Vtiger_List_Js.getInstance && Vtiger_List_Js.getInstance();
    if (!listInstance || !listInstance.loadListViewRecords) {
      return;
    }
    listInstance.loadListViewRecords(extraParams || {});
  }

  function buildPosSearchParams(query) {
    query = (query || "").toString().trim();
    if (!query.length) {
      return [];
    }
    var fields = ["quote_no", "subject", "account_id", "potential_id"];
    var conditions = [];
    for (var i = 0; i < fields.length; i++) {
      conditions.push([fields[i], "c", query]);
    }
    return [[], conditions];
  }

  function runPosQuickSearch(query) {
    query = query != null ? String(query).trim() : qtLiveSearchQuery;
    qtLiveSearchQuery = query;
    var quickParams = buildPosSearchParams(query);
    var payload = JSON.stringify(quickParams);
    if (payload === qtLastSearchPayload) {
      return;
    }
    qtLastSearchPayload = payload;
    var listInstance =
      Vtiger_List_Js.getInstance && Vtiger_List_Js.getInstance();
    if (!listInstance || !listInstance.loadListViewRecords) {
      return;
    }
    listInstance.filterClick = false;
    $("#listViewContent").find("#currentSearchParams").val(payload);
    loadPosLikeList({ page: "1", search_params: payload, nolistcache: "1" });
  }

  function schedulePosQuickSearch(immediate) {
    if (qtPosSearchTimer) {
      clearTimeout(qtPosSearchTimer);
      qtPosSearchTimer = null;
    }
    if (immediate) {
      runPosQuickSearch();
      return;
    }
    qtPosSearchTimer = setTimeout(function () {
      qtPosSearchTimer = null;
      runPosQuickSearch();
    }, 600);
  }

  function getSelectedQuoteIds() {
    var ids = [];
    var pushId = function (raw) {
      var id = parseInt(raw, 10);
      if (id > 0 && ids.indexOf(id) < 0) {
        ids.push(id);
      }
    };
    getSalesTableRoot()
      .find(".listViewEntriesCheckBox:checked")
      .each(function () {
        pushId($(this).val());
      });
    if (!ids.length) {
      $("#listview-table .listViewEntriesCheckBox:checked").each(function () {
        pushId($(this).val());
      });
    }
    if (!ids.length) {
      try {
        var listInstance =
          typeof Vtiger_List_Js !== "undefined" && Vtiger_List_Js.getInstance
            ? Vtiger_List_Js.getInstance()
            : null;
        if (
          listInstance &&
          typeof listInstance.readSelectedIds === "function"
        ) {
          var selected = listInstance.readSelectedIds(true);
          if (jQuery.isArray(selected)) {
            selected.forEach(pushId);
          }
        }
      } catch (e) {
        /* ignore */
      }
    }
    if (!ids.length) {
      getSalesTableRoot()
        .find("tbody tr.listViewEntries.mk-sales-row-selected")
        .each(function () {
          pushId($(this).data("id") || $(this).attr("data-id"));
        });
    }
    return ids;
  }

  var massDupInFlight = false;
  var massDupClickLock = false;

  function reloadQuotesListAfterMassAction() {
    // POS Quotes list soft-refresh often leaves stale rows; hard reload is reliable.
    window.location.reload();
  }

  function massDeleteQuotes() {
    if (!isQuotesSalesList() || massDupInFlight || massDupClickLock) {
      return;
    }
    massDupClickLock = true;
    setTimeout(function () {
      massDupClickLock = false;
    }, 600);
    var ids = getSelectedQuoteIds();
    if (!ids.length) {
      if (app.helper && app.helper.showErrorNotification) {
        app.helper.showErrorNotification({
          message: "Chọn ít nhất 1 báo giá để xóa.",
        });
      } else {
        window.alert("Chọn ít nhất 1 báo giá để xóa.");
      }
      return;
    }
    var message = app.vtranslate
      ? app.vtranslate("LBL_MASS_DELETE_CONFIRMATION")
      : "Xóa " + ids.length + " báo giá đã chọn?";
    var run = function () {
      massDupInFlight = true;
      var postData = {
        module: "Quotes",
        action: "MassDelete",
        selected_ids: JSON.stringify(ids),
        excluded_ids: JSON.stringify([]),
        viewname:
          $("#listViewContent")
            .find('[name="cvid"], #viewname, [name="viewname"]')
            .first()
            .val() || "",
        app: "SALES",
      };
      if (app.helper && app.helper.showProgress) {
        app.helper.showProgress();
      }
      app.request.post({ data: postData }).then(function (err) {
        massDupInFlight = false;
        if (app.helper && app.helper.hideProgress) {
          app.helper.hideProgress();
        }
        if (err) {
          if (app.helper && app.helper.showErrorNotification) {
            app.helper.showErrorNotification({
              message: (err && err.message) || "Không xóa được.",
            });
          } else {
            window.alert((err && err.message) || "Không xóa được.");
          }
          return;
        }
        if (app.helper && app.helper.showSuccessNotification) {
          app.helper.showSuccessNotification({
            message: "Đã xóa " + ids.length + " báo giá.",
          });
        }
        reloadQuotesListAfterMassAction();
      });
    };
    try {
      if (app.helper && app.helper.showConfirmationBox) {
        app.helper
          .showConfirmationBox({ message: message })
          .then(run, function () {
            /* cancel */
          });
        return;
      }
    } catch (e) {
      /* fall through */
    }
    if (window.confirm(message || "Xóa " + ids.length + " báo giá?")) {
      run();
    }
  }

  function runQuotesMassDuplicate(ids, options) {
    options = options || {};
    ids = (ids || [])
      .map(function (id) {
        return parseInt(id, 10);
      })
      .filter(function (id) {
        return id > 0;
      });
    if (!ids.length) {
      if (app.helper && app.helper.showErrorNotification) {
        app.helper.showErrorNotification({
          message: "Chọn ít nhất 1 báo giá để nhân bản.",
        });
      } else {
        window.alert("Chọn ít nhất 1 báo giá để nhân bản.");
      }
      return;
    }
    if (massDupInFlight) {
      return;
    }
    var skipConfirm = !!options.skipConfirm;
    var message =
      ids.length === 1
        ? "Nhân bản báo giá đã chọn?"
        : "Nhân bản " + ids.length + " báo giá đã chọn?";
    var run = function () {
      massDupInFlight = true;
      var postData = {
        module: "Quotes",
        action: "MassDuplicate",
        selected_ids: JSON.stringify(ids),
        excluded_ids: JSON.stringify([]),
        viewname:
          $("#listViewContent")
            .find('[name="cvid"], #viewname, [name="viewname"]')
            .first()
            .val() || "",
        app: "SALES",
      };
      if (app.helper && app.helper.showProgress) {
        app.helper.showProgress();
      }
      app.request.post({ data: postData }).then(function (err, res) {
        massDupInFlight = false;
        if (app.helper && app.helper.hideProgress) {
          app.helper.hideProgress();
        }
        if (err) {
          if (app.helper && app.helper.showErrorNotification) {
            app.helper.showErrorNotification({
              message: (err && err.message) || "Không nhân bản được.",
            });
          } else {
            window.alert((err && err.message) || "Không nhân bản được.");
          }
          return;
        }
        var result = res || {};
        if (result.success === false) {
          if (app.helper && app.helper.showErrorNotification) {
            app.helper.showErrorNotification({
              message: result.message || "Không nhân bản được.",
            });
          } else {
            window.alert(result.message || "Không nhân bản được.");
          }
          return;
        }
        if (app.helper && app.helper.showSuccessNotification) {
          app.helper.showSuccessNotification({
            message: result.message || "Đã nhân bản báo giá.",
          });
        }
        // Single copy: open the new record so user can verify the clone.
        var created = result.created || [];
        if (ids.length === 1 && created.length === 1) {
          window.location.href =
            "index.php?module=Quotes&view=Detail&record=" +
            created[0] +
            "&app=SALES";
          return;
        }
        reloadQuotesListAfterMassAction();
      });
    };
    if (skipConfirm) {
      run();
      return;
    }
    try {
      if (app.helper && app.helper.showConfirmationBox) {
        app.helper
          .showConfirmationBox({ message: message })
          .then(run, function () {
            /* cancel */
          });
        return;
      }
    } catch (e) {
      /* fall through to native confirm */
    }
    if (window.confirm(message)) {
      run();
    }
  }

  function massDuplicateQuotes() {
    if (!isQuotesSalesList() || massDupInFlight || massDupClickLock) {
      return;
    }
    massDupClickLock = true;
    setTimeout(function () {
      massDupClickLock = false;
    }, 600);
    runQuotesMassDuplicate(getSelectedQuoteIds());
  }

  function bindInlineDuplicateButton() {
    $(document)
      .off("click.mkQtInlineDup", ".mk-so-inline-detail__dup-btn")
      .on("click.mkQtInlineDup", ".mk-so-inline-detail__dup-btn", function (e) {
        if (!isQuotesSalesList()) {
          return;
        }
        e.preventDefault();
        e.stopPropagation();
        var id =
          parseInt($(this).attr("data-record-id"), 10) ||
          parseInt($(this).data("recordId"), 10) ||
          0;
        if (id <= 0) {
          var href = $(this).attr("href") || "";
          var m = href.match(/[?&]record=(\d+)/);
          if (m) {
            id = parseInt(m[1], 10) || 0;
          }
        }
        if (id > 0) {
          runQuotesMassDuplicate([id]);
        }
      });
  }

  function patchQtListViewActions() {
    if (
      typeof Vtiger_List_Js === "undefined" ||
      Vtiger_List_Js.prototype.__mkQtListActionsPatched
    ) {
      return;
    }
    var original = Vtiger_List_Js.prototype.registerPostLoadListViewActions;
    Vtiger_List_Js.prototype.registerPostLoadListViewActions = function () {
      original.apply(this, arguments);
      if (isQuotesSalesList()) {
        syncRowSelectedIfNeeded();
        syncQtMassActionButtons();
      }
    };
    Vtiger_List_Js.prototype.__mkQtListActionsPatched = true;
  }

  function bindMassActionButtons() {
    $(document)
      .off("click.mkQtMassDup", "#mk-qt-mass-duplicate-btn")
      .on("click.mkQtMassDup", "#mk-qt-mass-duplicate-btn", function (e) {
        if (!isQuotesSalesList()) return;
        e.preventDefault();
        massDuplicateQuotes();
      })
      .off("click.mkQtMassDel", "#mk-qt-mass-delete-btn")
      .on("click.mkQtMassDel", "#mk-qt-mass-delete-btn", function (e) {
        if (!isQuotesSalesList()) return;
        e.preventDefault();
        e.stopPropagation();
        massDeleteQuotes();
      });
  }

  function bindPosToolbarEvents() {
    bindMassActionButtons();
    if (qtPosSearchBound) {
      return;
    }
    qtPosSearchBound = true;
    $(document)
      .off("input.mkQtPosSearch", "#mk-qt-pos-search")
      .on("input.mkQtPosSearch", "#mk-qt-pos-search", function () {
        if (!isQuotesSalesList()) return;
        var val = $.trim($(this).val());
        qtLiveSearchQuery = val;
        $("#mk-qt-pos-search-clear").prop("hidden", !val);
        schedulePosQuickSearch();
      })
      .off("keydown.mkQtPosSearch", "#mk-qt-pos-search")
      .on("keydown.mkQtPosSearch", "#mk-qt-pos-search", function (ev) {
        if (!isQuotesSalesList()) return;
        if (ev.key === "Enter") {
          ev.preventDefault();
          schedulePosQuickSearch(true);
        }
        if (ev.key === "Escape") {
          ev.preventDefault();
          qtLiveSearchQuery = "";
          qtLastSearchPayload = "";
          $(this).val("");
          $("#mk-qt-pos-search-clear").prop("hidden", true);
          schedulePosQuickSearch(true);
        }
      })
      .off("click.mkQtPosSearchClear", "#mk-qt-pos-search-clear")
      .on("click.mkQtPosSearchClear", "#mk-qt-pos-search-clear", function (e) {
        if (!isQuotesSalesList()) return;
        e.preventDefault();
        qtLiveSearchQuery = "";
        qtLastSearchPayload = "";
        $("#mk-qt-pos-search").val("").trigger("input").focus();
      })
      .off("click.mkQtPosColumns", ".mk-qt-pos-trigger-columns")
      .on("click.mkQtPosColumns", ".mk-qt-pos-trigger-columns", function (e) {
        if (!isQuotesSalesList()) return;
        e.preventDefault();
        $("#listViewContent")
          .find(".listColumnFilter")
          .first()
          .trigger("click");
      });
  }

  function parseMoneyText(text) {
    if (window.MkCurrency && typeof MkCurrency.parse === "function") {
      return MkCurrency.parse(text);
    }
    if (text == null) return 0;
    var s = String(text).replace(/[^\d,.-]/g, "");
    if (/^\d{1,3}(\.\d{3})+(,\d+)?$/.test(s)) {
      s = s.replace(/\./g, "").replace(",", ".");
    } else {
      s = s.replace(/\./g, "").replace(/,/g, "");
    }
    var n = parseFloat(s);
    return isNaN(n) ? 0 : n;
  }

  function cellMoneyAmount($td) {
    if (!$td || !$td.length) return 0;
    var raw = $td.attr("data-rawvalue");
    if (raw != null && String(raw).length) {
      var fromRaw = parseMoneyText(raw);
      if (fromRaw) return fromRaw;
    }
    return parseMoneyText($td.find(".value").text() || $td.text());
  }

  function formatMoneyNumber(n) {
    if (window.MkCurrency && typeof MkCurrency.format === "function") {
      return MkCurrency.format(n, { decimals: 0 });
    }
    try {
      return Math.round(n).toLocaleString("vi-VN");
    } catch (e) {
      return String(Math.round(n)).replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }
  }

  function injectSummaryRow($table) {
    if (!$table || !$table.length) return;
    var dueField = $table.find('thead a[data-columnname="hdnGrandTotal"]')
      .length
      ? "hdnGrandTotal"
      : $table.find('thead a[data-columnname="total"]').length
        ? "total"
        : "";
    var $headerRow = $table.find("thead tr.listViewContentHeader").first();
    if (!$headerRow.length) return;
    $table.find("thead tr.mk-so-summary-row").remove();
    var $rows = $table.find("tbody tr.listViewEntries");
    if (!$rows.length) return;
    var hasDueColumn =
      dueField &&
      $headerRow.find('a[data-columnname="' + dueField + '"]').length > 0;
    var dueTotal = 0;
    if (hasDueColumn) {
      $rows.each(function () {
        dueTotal += cellMoneyAmount(
          $(this)
            .find('td[data-name="' + dueField + '"]')
            .first(),
        );
      });
    }
    var html = '<tr class="mk-so-summary-row">';
    $headerRow.children("th").each(function () {
      var $th = $(this);
      var field = $th.find("a[data-columnname]").attr("data-columnname") || "";
      var cls = "mk-so-summary-cell";
      var content = "";
      if ($th.hasClass("mk-so-pos-control-th")) {
        cls += " mk-so-summary-cell--label";
        content = '<span class="mk-so-summary-label">Tổng</span>';
      } else if (field === dueField && hasDueColumn) {
        cls += " mk-so-summary-cell--due";
        content =
          '<span class="mk-so-summary-value">' +
          formatMoneyNumber(dueTotal) +
          "</span>";
      }
      html += '<th class="' + cls + '">' + content + "</th>";
    });
    html += "</tr>";
    $headerRow.after(html);
  }

  function applyPosListChrome() {
    if (!isPosListEnabled()) return;
    bindPosToolbarEvents();
    bindInlineDetailCapture();
    patchInlineDetailRowClick();
    if (qtLiveSearchQuery) {
      $("#mk-qt-pos-search").val(qtLiveSearchQuery);
      $("#mk-qt-pos-search-clear").prop("hidden", !qtLiveSearchQuery);
    }
    injectSummaryRow($("#listViewContent #listview-table"));
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
    $tc.removeClass(
      "ps ps--active-x ps--active-y ps--scrolling-x ps--scrolling-y",
    );
    $tc.find(".ps__rail-x, .ps__rail-y, .ps__thumb-x, .ps__thumb-y").remove();
  }

  function fixListScrollContainer() {
    if (!isQuotesSalesList()) {
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

    $(
      "#listViewContent #scroller_wrapper.bottom-fixed-scroll, #listViewContent .bottom-fixed-scroll",
    ).css({
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
  }

  function relocatePagination() {
    if (!isQuotesSalesList()) {
      return;
    }
    var footer = document.querySelector(
      "#listViewContent .mk-so-filter-row__footer",
    );
    var table = document.getElementById("table-content");
    if (!footer || !table || !table.parentNode) {
      return;
    }
    if (table.nextSibling === footer) {
      return;
    }
    table.parentNode.insertBefore(footer, table.nextSibling);
  }

  function markTable() {
    if (!isQuotesSalesList()) {
      return;
    }
    $("#listViewContent #listview-table").addClass("mk-qt-table");
  }

  function initialsFromName(name) {
    var parts = (name || "").trim().split(/\s+/).filter(Boolean);
    if (!parts.length) {
      return "?";
    }
    if (parts.length === 1) {
      return parts[0].substring(0, 2).toUpperCase();
    }
    return (
      parts[0].charAt(0) + parts[parts.length - 1].charAt(0)
    ).toUpperCase();
  }

  function enhanceQuoteStage(context) {
    var STAGE_LABELS = {
      Created: "Nháp",
      Draft: "Nháp",
      "Đã tạo": "Nháp",
      Nháp: "Nháp",
    };
    $(context)
      .find('td[data-name="quotestage"] .value')
      .each(function () {
        var $value = $(this);
        if ($value.find(".mk-qt-stage-pill").length) {
          return;
        }
        var $link = $value.find("a").first();
        var text = $.trim($link.length ? $link.text() : $value.text());
        if (!text) {
          return;
        }
        var label = STAGE_LABELS[text] || text;
        if ($link.length) {
          $link.addClass("mk-qt-stage-pill").text(label);
          return;
        }
        $value
          .empty()
          .append($("<span>", { class: "mk-qt-stage-pill", text: label }));
      });
  }

  function enhanceCreatedBy(context) {
    $(context)
      .find(CREATOR_SELECTORS.join(","))
      .each(function () {
        var $value = $(this);
        if ($value.find(".mk-qt-avatar").length) {
          return;
        }
        var text = $.trim($value.text());
        if (!text) {
          return;
        }
        var initials = initialsFromName(text);
        $value
          .addClass("mk-qt-has-creator")
          .empty()
          .append(
            $("<span>", { class: "mk-qt-avatar", text: initials }),
            $("<span>", { class: "mk-qt-avatar__label", text: text }),
          );
      });
  }

  function applySearchPlaceholders(context) {
    $(context)
      .find("tr.searchRow th")
      .each(function () {
        var $th = $(this);
        var name = $th.attr("data-columnname") || $th.data("columnname");
        if (!name) {
          var $input = $th
            .find('input.listSearchContributor, input[type="text"]')
            .first();
          if ($input.length) {
            name = $input.attr("name") || $input.data("columnname");
          }
        }
        if (!name || !SEARCH_PLACEHOLDERS[name]) {
          return;
        }
        $th
          .find('input.listSearchContributor, input[type="text"]')
          .each(function () {
            var $inp = $(this);
            if (!$inp.attr("placeholder")) {
              $inp.attr("placeholder", SEARCH_PLACEHOLDERS[name]);
            }
          });
      });
  }

  function setReadyState() {
    if (!isQuotesSalesList()) {
      return;
    }
    document.body.classList.remove("mk-quotes-list-ui-loading");
    document.body.classList.add("mk-quotes-list-ui-ready");
    document.documentElement.classList.add("mk-quotes-list-ui-ready");
    document.documentElement.classList.add("mk-sales-list-ready");
    if (
      window.MkSalesListShared &&
      typeof window.MkSalesListShared.revealSalesListUi === "function"
    ) {
      window.MkSalesListShared.revealSalesListUi();
    }
  }

  function getSavedLayoutMode() {
    try {
      return window.localStorage.getItem(LAYOUT_STORAGE_KEY) === "grid"
        ? "grid"
        : "list";
    } catch (e) {
      return "list";
    }
  }

  function applyLayoutMode(mode) {
    if (!isQuotesSalesList()) {
      return;
    }
    var isGrid = mode === "grid";
    var $lv = $("#listViewContent");
    $lv.toggleClass("mk-qt-is-view-grid", isGrid);
    document.body.classList.toggle("mk-qt-is-view-grid", isGrid);

    var $listBtn = $(".mk-so-toggle-layout--list");
    var $gridBtn = $(".mk-so-toggle-layout--grid");
    $listBtn
      .toggleClass("is-active", !isGrid)
      .attr("aria-pressed", !isGrid ? "true" : "false");
    $gridBtn
      .toggleClass("is-active", isGrid)
      .attr("aria-pressed", isGrid ? "true" : "false");

    try {
      window.localStorage.setItem(LAYOUT_STORAGE_KEY, isGrid ? "grid" : "list");
    } catch (e2) {
      /* ignore */
    }
  }

  function bindViewLayoutToggle() {
    $(document).off(
      "click.mkQtLayout",
      ".mk-so-toggle-layout--list, .mk-so-toggle-layout--grid",
    );
    $(document).on(
      "click.mkQtLayout",
      ".mk-so-toggle-layout--list",
      function (e) {
        if (!isQuotesSalesList()) {
          return;
        }
        e.preventDefault();
        e.stopPropagation();
        applyLayoutMode("list");
        fixListScrollContainer();
      },
    );
    $(document).on(
      "click.mkQtLayout",
      ".mk-so-toggle-layout--grid",
      function (e) {
        if (!isQuotesSalesList()) {
          return;
        }
        e.preventDefault();
        e.stopPropagation();
        applyLayoutMode("grid");
        fixListScrollContainer();
      },
    );
    applyLayoutMode(getSavedLayoutMode());
  }

  function refreshListRowsOnly() {
    if (!isQuotesSalesList()) {
      return;
    }
    var $table = $("#listViewContent #listview-table");
    collapseInlineDetail($table);
    markTable();
    fixEncodedTextCells($table);
    fixCurrencySpacing($table);
    enhanceQuoteStage(document);
    enhanceCreatedBy(document);
    syncRowSelectedIfNeeded();
    applyPosListChrome();
  }

  function syncRowSelectedIfNeeded() {
    getSalesTableRoot()
      .find("tbody tr.listViewEntries")
      .each(function () {
        var $row = $(this);
        $row.toggleClass(
          "mk-sales-row-selected",
          $row.find(".listViewEntriesCheckBox:checked").length > 0,
        );
      });
  }

  function afterListLayout() {
    if (!isQuotesSalesList()) {
      return;
    }
    if (typeof window.mkSalesListAfterAjax === "function") {
      window.mkSalesListAfterAjax();
    }
    var $table = $("#listViewContent #listview-table");
    collapseInlineDetail($table);
    relocatePagination();
    markTable();
    fixEncodedTextCells($table);
    fixCurrencySpacing($table);
    enhanceQuoteStage(document);
    enhanceCreatedBy(document);
    applySearchPlaceholders(document);
    syncQuotesLayoutMode();
    fixListScrollContainer();
    applyPosListChrome();
    ensureQtMassActionButtons();
    bindQtSelectionEvents();
    patchQtListViewActions();
    syncRowSelectedIfNeeded();
    syncQtMassActionButtons();
    setReadyState();
  }

  function init() {
    if (!isQuotesSalesList()) {
      return;
    }
    document.body.classList.add("mk-quotes-list-ui-loading");
    ensureQtMassActionButtons();
    bindMassActionButtons();
    bindInlineDuplicateButton();
    bindQtSelectionEvents();
    bindInlineDetailCapture();
    patchInlineDetailRowClick();

    var root = $("#listViewContent");
    if (!root.length) {
      setReadyState();
      return;
    }

    $(document).on("click.mkQtList", ".mk-qt-trigger-columns", function (e) {
      e.preventDefault();
      var col = root.find(".listColumnFilter").first();
      if (col.length) {
        col.trigger("click");
      }
    });

    $(document).on(
      "click.mkQtList",
      ".mk-qt-filter-trigger-search",
      function (e) {
        e.preventDefault();
        if (typeof window.mkSalesListAfterAjax === "function") {
          window.mkSalesListAfterAjax();
        }
        var $row = root.find("tr.searchRow.listViewSearchContainer").first();
        if ($row.length && $row[0].scrollIntoView) {
          $row[0].scrollIntoView({ behavior: "smooth", block: "nearest" });
        }
      },
    );

    if (
      window.MkSalesListShared &&
      window.MkSalesListShared.bindViewLayoutToggle
    ) {
      window.MkSalesListShared.bindViewLayoutToggle();
    }

    if (typeof app !== "undefined" && app.event && app.event.on) {
      app.event.on("post.listViewFilter.click", function () {
        setTimeout(afterListLayout, 200);
      });
      app.event.on("Vtiger.Post.MenuToggle", function () {
        setTimeout(fixListScrollContainer, 80);
      });
    }

    var resizeTimer;
    $(window).on("resize.mkQuotesSalesList", function () {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(function () {
        if (isQuotesSalesList()) {
          fixListScrollContainer();
        }
      }, 150);
    });

    setTimeout(afterListLayout, 200);
  }

  window.__mkQuotesListUi = {
    isQuotesSalesList: isQuotesSalesList,
    afterListLayout: afterListLayout,
    refreshListRowsOnly: refreshListRowsOnly,
    bindViewLayoutToggle: bindViewLayoutToggle,
    applyLayoutMode: applyLayoutMode,
    massDuplicateQuotes: massDuplicateQuotes,
    massDeleteQuotes: massDeleteQuotes,
    syncQtMassActionButtons: syncQtMassActionButtons,
  };
  window.mkQtMassDuplicateQuotes = massDuplicateQuotes;
  window.mkQtMassDeleteQuotes = massDeleteQuotes;

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})(jQuery);

Inventory_List_Js(
  "Quotes_List_Js",
  {},
  {
    postLoadListViewRecords: function () {
      this._super();
      if (window.__mkQuotesListUi && window.__mkQuotesListUi.afterListLayout) {
        setTimeout(window.__mkQuotesListUi.afterListLayout, 100);
      }
    },
  },
);
