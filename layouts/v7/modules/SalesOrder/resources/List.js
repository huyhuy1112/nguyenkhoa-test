/**
 * SalesOrder list (SALES): enhance native Vtiger DOM; AJAX only swaps table body (keeps shell).
 */
(function ($) {
  "use strict";

  var STATUS_FIELD_CANDIDATES = [
    "sostatus",
    "salesorder_status",
    "invoicestatus",
    "status",
  ];
  var placeListContentsPatched = false;

  function listConfig() {
    return window.__mkSoSalesListConfig || {};
  }

  function statusCandidates() {
    var cfg = listConfig();
    return cfg.statusFieldCandidates || STATUS_FIELD_CANDIDATES;
  }

  function isSalesOrderSalesList() {
    var b = document.body;
    if (
      !b ||
      b.getAttribute("data-module") !== "SalesOrder" ||
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
      params.get("module") === "SalesOrder" &&
      params.get("view") === "List" &&
      params.get("app") === "SALES"
    ) {
      return true;
    }
    return !!document.querySelector("#listViewContent .mk-so-pos-list-enabled");
  }

  function getListViewContainer() {
    return $("#listViewContent");
  }

  function getPrimaryTable() {
    var $lv = getListViewContainer();
    var $table = $lv.find(".mk-so-table-card #listview-table").first();
    if ($table.length) {
      return $table;
    }
    return $lv.find("#listview-table").first();
  }

  function isQuoteColumnFieldName(fieldName) {
    if (!fieldName) {
      return false;
    }
    return String(fieldName).toLowerCase().indexOf("quote") >= 0;
  }

  function resolveStatusField($table) {
    var cfg = listConfig();
    var preferred = cfg.preferredStatusField;
    var candidates = statusCandidates();
    var i;

    if (
      preferred &&
      $table.find(
        'thead a[data-columnname="' +
          preferred +
          '"], td[data-name="' +
          preferred +
          '"]',
      ).length
    ) {
      return preferred;
    }
    for (i = 0; i < candidates.length; i++) {
      if (
        $table.find(
          'thead a[data-columnname="' +
            candidates[i] +
            '"], td[data-name="' +
            candidates[i] +
            '"]',
        ).length
      ) {
        return candidates[i];
      }
    }
    return null;
  }

  function hideQuoteColumns($table) {
    $table.find("thead a[data-columnname]").each(function () {
      var name = $(this).attr("data-columnname");
      if (isQuoteColumnFieldName(name)) {
        $(this).closest("th").addClass("mk-so-list-col-hidden");
      }
    });
    $table.find("tr.searchRow th").each(function () {
      var $th = $(this);
      var name = $th.attr("data-columnname");
      if (!name) {
        var $input = $th.find("input, select").first();
        name = $input.length ? $input.attr("name") : "";
        if (name) {
          name = String(name).replace(/\[\]$/, "");
        }
      }
      if (isQuoteColumnFieldName(name)) {
        $th.addClass("mk-so-list-col-hidden");
      }
    });
    $table.find("tbody td[data-name]").each(function () {
      if (isQuoteColumnFieldName($(this).attr("data-name"))) {
        $(this).addClass("mk-so-list-col-hidden");
      }
    });
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
    "accountname",
    "account_id",
    "subject",
    "contact_id",
  ];

  function fixEncodedTextCells($table) {
    TEXT_DECODE_FIELDS.forEach(function (fieldName) {
      $table.find('tbody td[data-name="' + fieldName + '"]').each(function () {
        var $td = $(this);
        if ($td.hasClass("mk-so-list-col-hidden")) {
          return;
        }
        var $targets = $td.find(".value, a.listViewContentHeaderValues, a");
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

  var STATUS_VI_LABELS = {
    Created: "Phiếu tạm",
    Approved: "Đã xác nhận",
    Delivered: "Đã giao",
    Cancelled: "Đã hủy",
    Pending: "Đang chờ",
    Paid: "Đã thanh toán",
    Sent: "Đã gửi",
    Rejected: "Từ chối",
    waiting_print: "Chờ in phiếu",
    picking: "Đang soạn",
    packed: "Đã soạn",
    shipped: "Đã giao",
    rejected: "Từ chối",
    "Chờ in phiếu": "Chờ in phiếu",
    "Đang soạn": "Đang soạn",
    "Đã soạn": "Đã soạn",
    "Đã giao": "Đã giao",
    "Đã duyệt": "Đã xác nhận",
    "Đã tạo": "Phiếu tạm",
    "Đang chờ xử lý": "Đang chờ",
    "Đang giao hàng": "Đang giao hàng",
    "Hoàn thành": "Hoàn thành",
    "Đã gửi": "Đã gửi",
    "Đã thanh toán": "Đã thanh toán",
    "Đã hủy": "Đã hủy",
    "Từ chối": "Từ chối",
  };

  var POS_COL_CLASS_BY_FIELD = {
    starred: "mk-so-col-star",
    salesorder_no: "mk-so-col-order-no",
    createdtime: "mk-so-col-time",
    customerno: "mk-so-col-customer-code",
    account_id: "mk-so-col-customer",
    contact_id: "mk-so-col-customer",
    mk_warehouse_name: "mk-so-col-warehouse",
    hdnGrandTotal: "mk-so-col-due",
    total: "mk-so-col-due",
    received: "mk-so-col-paid",
    paid: "mk-so-col-paid",
    sostatus: "mk-so-col-status",
    salesorder_status: "mk-so-col-status",
    invoicestatus: "mk-so-col-status",
    status: "mk-so-col-status",
  };

  var posSearchBound = false;
  var posSearchTimer = null;
  var lastPosSearchPayload = "";
  var livePosSearchQuery = "";
  var posFilterOpen = false;
  var posFilterStateSynced = false;
  var posFilterApplied = false;
  var posExpandedRecordId = null;
  var posInlineDetailLoading = false;

  function getTableColspan($table) {
    var count = $table.find("thead tr.listViewContentHeader th").length;
    return count > 0 ? count : 1;
  }

  function collapsePosInlineDetail($table) {
    if (!$table || !$table.length) {
      posExpandedRecordId = null;
      return;
    }
    closeInlineFullView();
    closeInlineExcelPreview();
    closeInlinePrintPreview();
    $table.find("tr.mk-so-inline-detail-row").remove();
    $table
      .find("tr.listViewEntries.mk-so-row-expanded")
      .removeClass("mk-so-row-expanded");
    posExpandedRecordId = null;
  }

  function ensureInlineDetailBackdrop() {
    var $backdrop = $(".mk-so-inline-detail-backdrop");
    if (!$backdrop.length) {
      $backdrop = $(
        '<div class="mk-so-inline-detail-backdrop" aria-hidden="true"></div>',
      );
      $("body").append($backdrop);
      $backdrop.on("click.mkSoInlineFull", function () {
        closeInlineFullView();
      });
    }
    return $backdrop;
  }

  function openInlineFullView($panel) {
    if (!$panel || !$panel.length) {
      return;
    }
    ensureInlineDetailBackdrop().addClass("is-open");
    $panel.addClass("is-fullscreen");
    $("body").addClass("mk-so-inline-full-open");
    if (!$panel.find(".mk-so-inline-detail__fullscreen-close").length) {
      $panel.prepend(
        '<button type="button" class="mk-so-inline-detail__fullscreen-close" aria-label="Thu gọn">' +
          '<i class="fa fa-compress" aria-hidden="true"></i>' +
          "</button>",
      );
    }
    $panel[0].scrollIntoView({ block: "nearest", behavior: "smooth" });
  }

  function closeInlineFullView($panel) {
    var $target =
      $panel && $panel.length
        ? $panel
        : $(".mk-so-inline-detail.is-fullscreen").first();
    $(".mk-so-inline-detail-backdrop").removeClass("is-open");
    $("body").removeClass("mk-so-inline-full-open");
    if ($target.length) {
      $target.removeClass("is-fullscreen");
    }
  }

  function ensureInlineExcelPreviewModal() {
    var $modal = $("#mk-so-inline-excel-preview");
    if ($modal.length) {
      return $modal;
    }
    $modal = $(
      '<div id="mk-so-inline-excel-preview" class="mk-so-inline-excel-preview" aria-hidden="true">' +
        '<div class="mk-so-inline-excel-preview__dialog" role="dialog" aria-labelledby="mk-so-inline-excel-title">' +
        '<div class="mk-so-inline-excel-preview__head">' +
        '<h3 id="mk-so-inline-excel-title">Xem trước Excel</h3>' +
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
      if ($(e.target).is("#mk-so-inline-excel-preview")) {
        closeInlineExcelPreview();
      }
    });
    return $modal;
  }

  function closeInlineExcelPreview() {
    var $modal = $("#mk-so-inline-excel-preview");
    $modal.removeClass("is-open").attr("aria-hidden", "true");
    $("body").removeClass("mk-so-inline-excel-open");
    $(".mk-so-inline-detail__export-btn")
      .attr("data-export-ready", "0")
      .find(".mk-so-inline-detail__export-label")
      .text("Export Excel");
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
    var orderNo = $.trim($panel.find(".mk-so-inline-detail__order-no").text());
    var customer = $.trim(
      $panel.find(".mk-so-inline-detail__customer-name").text(),
    );
    rows.push(["Mã đặt hàng", orderNo]);
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
      "SKU",
      "Tên hàng",
      "Số lượng",
      "Đơn giá",
      "Thuế",
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
    var orderNo = $.trim($panel.find(".mk-so-inline-detail__order-no").text());
    var customer = $.trim(
      $panel.find(".mk-so-inline-detail__customer-name").text(),
    );
    var notes = $.trim($panel.find(".mk-so-inline-detail__notes-input").val());
    if (
      notes &&
      (notes.toLowerCase().indexOf("unless otherwise agreed") >= 0 ||
        notes.toLowerCase().indexOf("all invoices are payable") >= 0)
    ) {
      notes = "";
    }
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
      if (
        !createdDate &&
        (label.indexOf("ngày đặt") >= 0 || label.indexOf("created") >= 0)
      ) {
        createdDate = value;
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
      var listPriceText =
        $row.attr("data-price") ||
        $.trim($cells.eq(3).text());
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
      // Skip paid row in invoice-style preview; fix broken grand totals.
      if (label.indexOf("Khách đã trả") === 0 || label.indexOf("đã trả") >= 0) {
        return;
      }
      if (label.indexOf("Giảm giá") === 0) {
        label = "Chiết khấu";
      } else if (
        label.indexOf("Tổng cộng") === 0 ||
        label.indexOf("Tổng thanh toán") === 0
      ) {
        label = "Tổng thanh toán";
        var grandRaw = parseFloat($panel.attr("data-grand-raw") || "0");
        var parsed = parseInlineMoney(value);
        if (grandRaw > 0) {
          value = formatInlineMoney(grandRaw);
        } else if (!parsed || (lineTotalSum > 0 && parsed < lineTotalSum * 0.5)) {
          value = formatInlineMoney(lineTotalSum);
        }
      } else if (label.indexOf("Tổng tiền hàng") === 0) {
        footerSubTotal = parseInlineMoney(value);
        if (
          !footerSubTotal ||
          (lineTotalSum > 0 && footerSubTotal < lineTotalSum * 0.5)
        ) {
          footerSubTotal = lineTotalSum;
          value = formatInlineMoney(lineTotalSum);
        }
      }
      totals.push({
        label: label,
        value: value,
        grand:
          $row.hasClass("mk-so-inline-detail__total-row--grand") ||
          label.indexOf("Tổng thanh toán") === 0,
      });
    });
    // Recover missing line money from footer when line cells were saved as 0.
    if (footerSubTotal > 0 && lineTotalSum <= 0 && lines.length === 1) {
      lines[0].total = footerSubTotal;
      if (lines[0].qty > 0) {
        lines[0].price = footerSubTotal / lines[0].qty;
      }
      lineTotalSum = footerSubTotal;
    } else if (footerSubTotal > 0 && lineTotalSum <= 0 && lines.length > 1) {
      var share = footerSubTotal / lines.length;
      lines.forEach(function (line) {
        line.total = share;
        line.price = line.qty > 0 ? share / line.qty : share;
      });
      lineTotalSum = footerSubTotal;
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
      var s = String(raw).trim();
      // DB / ISO: 2026-07-11 ...
      var iso = s.match(/^(\d{4})-(\d{2})-(\d{2})/);
      if (iso) {
        return "Ngày " + iso[3] + " tháng " + iso[2] + " năm " + iso[1];
      }
      // Server sends d/m/Y via data-created-date.
      var m = s.match(/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/);
      if (m) {
        var day = String(parseInt(m[1], 10)).padStart(2, "0");
        var month = String(parseInt(m[2], 10)).padStart(2, "0");
        return "Ngày " + day + " tháng " + month + " năm " + m[3];
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
      '<div class="mk-so-excel-sheet__company-meta">Địa chỉ: 6/24 Đường số 3, Cư Xá Lữ Gia, Phú Thọ, Hồ Chí Minh<br>Điện thoại: 0973969498</div>';
    html += '<div class="mk-so-excel-sheet__doc-title">HÓA ĐƠN ĐẶT HÀNG</div>';
    html +=
      '<div class="mk-so-excel-sheet__doc-meta">Mã đơn hàng: ' +
      esc(orderNo) +
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
        '<tr><td colspan="3" class="is-empty">Chưa có hàng hóa trong đơn hàng.</td></tr>';
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
        "index.php?module=SalesOrder&action=ExportExcelForSale&record=" +
        encodeURIComponent(recordId);
    }
    var iframeId = "mk-so-inline-excel-frame";
    var $frame = $("#" + iframeId);
    if (!$frame.length) {
      $frame = $("<iframe>", {
        id: iframeId,
        css: { display: "none", width: 0, height: 0, border: 0 },
      });
      $("body").append($frame);
    }
    $frame.attr("src", excelUrl);
  }

  function downloadInlineExportCsv($panel, recordId) {
    downloadInlineExportXlsx($panel, recordId);
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
      .off("click.mkSoExcelDownload")
      .on(
        "click.mkSoExcelDownload",
        ".mk-so-inline-excel-preview__download",
        function (e) {
          e.preventDefault();
          downloadInlineExportCsv($panel, recordId);
          closeInlineExcelPreview();
        },
      );
  }

  function closeInlinePrintPreview() {
    var $modal = $("#mk-so-inline-print-preview");
    $modal.removeClass("is-open").attr("aria-hidden", "true");
    $modal.find("iframe").attr("src", "about:blank");
    $("body").removeClass("mk-so-inline-print-open");
    $(".mk-so-inline-detail__print-btn")
      .attr("data-print-ready", "0")
      .find(".mk-so-inline-detail__print-label")
      .text("In");
  }

  function ensureInlinePrintPreviewModal() {
    var $modal = $("#mk-so-inline-print-preview");
    if ($modal.length) {
      return $modal;
    }
    $modal = $(
      '<div id="mk-so-inline-print-preview" class="mk-so-inline-print-preview" aria-hidden="true">' +
        '<div class="mk-so-inline-print-preview__dialog" role="dialog" aria-labelledby="mk-so-inline-print-title">' +
        '<div class="mk-so-inline-print-preview__head">' +
        '<h3 id="mk-so-inline-print-title">Xem trước bản in</h3>' +
        '<button type="button" class="mk-so-inline-print-preview__close" aria-label="Đóng">&times;</button>' +
        "</div>" +
        '<div class="mk-so-inline-print-preview__body">' +
        '<iframe class="mk-so-inline-print-preview__frame" title="Xem trước PDF đơn hàng"></iframe>' +
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
      if ($(e.target).is("#mk-so-inline-print-preview")) {
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
        "index.php?module=SalesOrder&action=ExportPDF&record=" +
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
        "index.php?module=SalesOrder&action=ExportPDF&record=" +
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
    var $frame = $("#mk-so-inline-print-download-frame");
    if (!$frame.length) {
      $frame = $(
        '<iframe id="mk-so-inline-print-download-frame" class="mk-so-inline-print-download-frame" title="Tải PDF đơn hàng"></iframe>',
      );
      $("body").append($frame);
    }
    $frame.attr("src", downloadUrl);
  }

  function executeInlinePrintFromPreview() {
    var $iframe = $("#mk-so-inline-print-preview iframe");
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

  function isPosInlineDetailInteraction(target) {
    var $target = $(target);
    if (!$target.length) {
      return false;
    }
    if (
      $target.closest(
        ".mk-so-inline-detail, .mk-so-inline-detail-row, .mk-so-pos-star-btn, .mk-so-pos-delete-btn, .mk-so-pos-dup-btn, .mk-so-pos-check, .mk-so-pos-control-td",
      ).length
    ) {
      return true;
    }
    if ($target.is('input[type="checkbox"]')) {
      return true;
    }
    return false;
  }

  function getCsrfToken() {
    if (typeof app !== "undefined" && typeof app.getCsrfToken === "function") {
      return app.getCsrfToken();
    }
    if (typeof csrfMagicToken !== "undefined") {
      return csrfMagicToken;
    }
    if (typeof csrfMagicName !== "undefined") {
      return jQuery('[name="' + csrfMagicName + '"]').val() || "";
    }
    return "";
  }

  function ensureSoConfirmWarehouseModal() {
    var id = "mkSoConfirmWarehouseModal";
    if ($("#" + id).length) {
      return $("#" + id);
    }
    var $modal = $(
      '<div class="modal fade mk-so-wh-modal" id="' +
        id +
        '" tabindex="-1" role="dialog" aria-hidden="true">' +
        '<div class="modal-dialog modal-dialog-centered">' +
        '<div class="modal-content">' +
        '<div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>' +
        '<h4 class="modal-title">Xác nhận đơn hàng</h4></div>' +
        '<div class="modal-body"><p class="mk-so-wh-modal__lead">Chọn kho để tạo phiếu xuất ở trạng thái <strong>Chờ in phiếu</strong>. Đơn hàng sẽ chuyển sang cùng trạng thái <strong>Chờ in phiếu</strong>.</p>' +
        '<div class="mk-so-wh-modal__list" id="mkSoConfirmWarehouseList"></div>' +
        '<div class="mk-so-wh-modal__error hide" id="mkSoConfirmWarehouseError"></div></div>' +
        '<div class="modal-footer">' +
        '<button type="button" class="btn btn-default" data-dismiss="modal">Hủy</button>' +
        '<button type="button" class="btn btn-success" id="mkSoConfirmWarehouseBtn" disabled>Xác nhận &amp; xuất kho</button>' +
        "</div></div></div></div>",
    );
    $("body").append($modal);
    return $modal;
  }

  function decodeWhLabel(s) {
    var text = String(s || "");
    if (/&(?:#x?[0-9a-f]+|[a-z]+);/i.test(text)) {
      var el = document.createElement("textarea");
      el.innerHTML = text;
      text = el.value;
    }
    return text;
  }

  function confirmSalesOrderWithWarehouse($panel, recordId, $btn) {
    var $modal = ensureSoConfirmWarehouseModal();
    var $err = $("#mkSoConfirmWarehouseError");
    var $list = $("#mkSoConfirmWarehouseList");
    var $confirmBtn = $("#mkSoConfirmWarehouseBtn");
    $err.addClass("hide").empty();
    $list.empty();
    $confirmBtn.prop("disabled", true).text("Xác nhận & xuất kho");

    function openConfirmModal() {
      $("body").addClass("mk-so-confirm-modal-open modal-open");
      // Keep fullscreen detail visible but don't let its overlay eat clicks.
      $(".mk-so-inline-detail-backdrop.is-open").css("pointer-events", "none");
      $modal.css("z-index", 110020);
      $modal.modal("show");
      // Bootstrap may insert backdrop after show — force it under the dialog.
      window.setTimeout(function () {
        $(".modal-backdrop")
          .last()
          .css({ "z-index": 110000, "pointer-events": "auto" });
        $modal.css({ "z-index": 110020, display: "block" });
        $modal.find(".modal-dialog").css("pointer-events", "auto");
      }, 0);
    }

    function closeConfirmModal() {
      $modal.modal("hide");
      $("body").removeClass("mk-so-confirm-modal-open");
      $(".mk-so-inline-detail-backdrop.is-open").css("pointer-events", "");
      // If no other bootstrap modal is open, clear leftover backdrops.
      window.setTimeout(function () {
        if (!$(".modal.in:visible, .modal.show:visible").length) {
          $(".modal-backdrop").remove();
          $("body").removeClass("modal-open");
        }
      }, 320);
    }

    function postConfirm(warehouseId) {
      $btn.data("mkBusy", 1).prop("disabled", true).addClass("is-busy");
      $confirmBtn.prop("disabled", true).text("Đang xử lý...");
      var postData = {
        module: "SalesOrder",
        action: "ConfirmSalesOrder",
        record: recordId,
        warehouse_id: warehouseId,
        app: "SALES",
      };
      var csrf = getCsrfToken();
      if (csrf) {
        postData.__vtrftk = csrf;
      }

      function handleFail(msg) {
        $err
          .removeClass("hide")
          .text(String(msg || "Không xác nhận được đơn hàng."));
        $btn.data("mkBusy", 0).prop("disabled", false).removeClass("is-busy");
        $confirmBtn.prop("disabled", false).text("Xác nhận & xuất kho");
      }

      function handleOk(result) {
        closeConfirmModal();
        if (
          typeof app !== "undefined" &&
          app.helper &&
          app.helper.showSuccessNotification
        ) {
          app.helper.showSuccessNotification({
            message:
              result.message || "Đã xác nhận đơn hàng và tạo phiếu xuất kho.",
          });
        }
        var targetUrl =
          result.warehouse_url ||
          result.list_url ||
          "index.php?module=SalesOrder&view=List&app=SALES";
        window.setTimeout(function () {
          window.location.href = targetUrl;
        }, 400);
      }

      if (typeof app !== "undefined" && app.request && app.request.post) {
        app.request.post({ data: postData }).then(function (err, res) {
          if (err) {
            handleFail(
              (err && (err.message || err)) || "Không xác nhận được đơn hàng.",
            );
            return;
          }
          var result = res || {};
          if (result.success === false) {
            handleFail(result.message || "Không xác nhận được đơn hàng.");
            return;
          }
          handleOk(result);
        });
        return;
      }

      $.ajax({
        url: "index.php",
        type: "POST",
        dataType: "json",
        data: postData,
      })
        .done(function (resp) {
          var result = resp && resp.result ? resp.result : resp;
          if (resp && resp.success === false) {
            handleFail(
              (resp.error && (resp.error.message || resp.error)) ||
                "Không xác nhận được đơn hàng.",
            );
            return;
          }
          if (!result || result.success === false) {
            handleFail(
              (result && result.message) || "Không xác nhận được đơn hàng.",
            );
            return;
          }
          handleOk(result);
        })
        .fail(function (xhr) {
          var msg = "Không xác nhận được đơn hàng.";
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
          handleFail(msg);
        });
    }

    function showWarehousePicker(warehouses) {
      warehouses = warehouses || [];
      if (!warehouses.length) {
        $err
          .removeClass("hide")
          .text(
            "Không có kho nào để xuất hàng. Vào Kho hàng để tạo kho trước.",
          );
        openConfirmModal();
        return;
      }
      warehouses.forEach(function (wh, idx) {
        var id =
          "mkSoConfirmWh_" + String(wh.id).replace(/[^a-zA-Z0-9_-]/g, "_");
        $list.append(
          $('<label class="mk-so-wh-option"></label>')
            .append(
              $('<input type="radio" name="mk_so_confirm_warehouse_pick" />')
                .attr("id", id)
                .attr("value", wh.id)
                .prop("checked", idx === 0),
            )
            .append(
              $('<span class="mk-so-wh-option__body"></span>')
                .append($("<strong></strong>").text(decodeWhLabel(wh.name)))
                .append(
                  $("<small></small>").text(
                    decodeWhLabel(wh.address || wh.code),
                  ),
                ),
            ),
        );
      });
      $confirmBtn.prop("disabled", false);
      $confirmBtn.off("click.mkSoConfirm").on("click.mkSoConfirm", function () {
        var warehouseId = $(
          'input[name="mk_so_confirm_warehouse_pick"]:checked',
        ).val();
        if (!warehouseId) {
          $err.removeClass("hide").text("Vui lòng chọn kho.");
          return;
        }
        postConfirm(warehouseId);
      });
      openConfirmModal();
    }

    function loadWarehouses() {
      if (typeof app !== "undefined" && app.request && app.request.get) {
        app.request
          .get({ url: "index.php?module=SalesOrder&action=WarehouseList" })
          .then(function (err, res) {
            if (err) {
              $err
                .removeClass("hide")
                .text(
                  String(
                    (err && (err.message || err)) ||
                      "Không tải được danh sách kho.",
                  ),
                );
              openConfirmModal();
              return;
            }
            showWarehousePicker((res && res.warehouses) || []);
          });
        return;
      }
      $.ajax({
        url: "index.php",
        type: "GET",
        dataType: "json",
        data: { module: "SalesOrder", action: "WarehouseList" },
      })
        .done(function (resp) {
          var res = resp && resp.result ? resp.result : resp;
          showWarehousePicker((res && res.warehouses) || []);
        })
        .fail(function () {
          $err.removeClass("hide").text("Không tải được danh sách kho.");
          openConfirmModal();
        });
    }

    $modal
      .off("hidden.bs.modal.mkSoConfirmClean")
      .on("hidden.bs.modal.mkSoConfirmClean", function () {
        $("body").removeClass("mk-so-confirm-modal-open");
        $(".mk-so-inline-detail-backdrop.is-open").css("pointer-events", "");
        if (!$(".modal.in:visible, .modal.show:visible").not($modal).length) {
          $(".modal-backdrop").remove();
          $("body").removeClass("modal-open");
        }
      });

    loadWarehouses();
  }

  function captureInlineDetailSnapshot($panel) {
    var snapshot = { fields: {}, description: "", paid: "" };
    $panel.find(".mk-so-inline-detail__field-edit :input").each(function () {
      var name = $(this).attr("name");
      if (name) {
        snapshot.fields[name] = $(this).val();
      }
    });
    snapshot.description =
      $panel.find(".mk-so-inline-detail__notes-input").val() || "";
    snapshot.paid = $panel.find(".mk-so-inline-detail__paid-input").val() || "";
    return snapshot;
  }

  function restoreInlineDetailSnapshot($panel, snapshot) {
    if (!snapshot) {
      return;
    }
    $.each(snapshot.fields || {}, function (name, value) {
      $panel
        .find('.mk-so-inline-detail__field-edit :input[name="' + name + '"]')
        .val(value);
    });
    $panel
      .find(".mk-so-inline-detail__notes-input")
      .val(snapshot.description || "");
    if (snapshot.paid !== undefined) {
      $panel.find(".mk-so-inline-detail__paid-input").val(snapshot.paid);
      $panel.find(".mk-so-inline-detail__paid-view").text(snapshot.paid || "0");
      recalcInlinePaidRemaining($panel);
    }
  }

  function updateInlineDetailViewValues($panel) {
    $panel
      .find('.mk-so-inline-detail__field[data-editable="1"]')
      .each(function () {
        var $field = $(this);
        var $input = $field
          .find(".mk-so-inline-detail__field-edit :input")
          .first();
        var $view = $field.find(".mk-so-inline-detail__field-view");
        if (!$input.length || !$view.length) {
          return;
        }
        if ($input.is("select")) {
          $view.text($input.find("option:selected").text());
        } else {
          $view.text($input.val());
        }
      });
    var paidVal = $panel.find(".mk-so-inline-detail__paid-input").val() || "0";
    $panel.find(".mk-so-inline-detail__paid-view").text(paidVal);
    recalcInlinePaidRemaining($panel);
  }

  function getInlineGrandRaw($panel) {
    var raw =
      $panel.attr("data-grand-raw") ||
      $panel
        .find(".mk-so-inline-detail__total-row--grand")
        .attr("data-grand-raw") ||
      "0";
    var n = parseFloat(raw);
    return isNaN(n) ? 0 : n;
  }

  function recalcInlinePaidRemaining($panel) {
    var grandRaw = getInlineGrandRaw($panel);
    var paidRaw = parseInlineMoney(
      $panel.find(".mk-so-inline-detail__paid-input").val(),
    );
    if (paidRaw < 0) {
      paidRaw = 0;
    }
    var remaining = grandRaw - paidRaw;
    if (remaining < 0) {
      remaining = 0;
    }
    $panel
      .find(".mk-so-inline-detail__grand-value")
      .text(formatInlineMoney(remaining));
  }

  function collectInlineDetailSaveData($panel, recordId) {
    var data = {
      record: recordId,
      module: "SalesOrder",
      action: "SaveAjax",
    };
    $panel.find(".mk-so-inline-detail__field-edit :input").each(function () {
      var name = $(this).attr("name");
      if (name) {
        data[name] = $(this).val();
      }
    });
    var $paidInput = $panel.find(".mk-so-inline-detail__paid-input");
    if ($paidInput.length) {
      var paidName =
        $paidInput.attr("name") ||
        $panel.attr("data-paid-field") ||
        "received";
      data[paidName] = $paidInput.val() || "0";
    }
    data.description =
      $panel.find(".mk-so-inline-detail__notes-input").val() || "";
    return data;
  }

  function setInlineDetailEditMode($panel, enable) {
    var isEdit = !!enable;
    $panel.toggleClass("is-edit-mode", isEdit);
    $panel
      .find(".mk-so-inline-detail__edit-toggle")
      .attr("aria-pressed", isEdit ? "true" : "false");
    $panel.find(".mk-so-inline-detail__notes-input").prop("readonly", !isEdit);
    $panel.find(".mk-so-inline-detail__paid-input").prop("readonly", !isEdit);
    if (
      isEdit &&
      typeof vtUtils !== "undefined" &&
      vtUtils.applyFieldElementsView
    ) {
      vtUtils.applyFieldElementsView(
        $panel
          .find(".mk-so-inline-detail__field-edit .dateField")
          .closest(".mk-so-inline-detail__field-edit"),
      );
    }
  }

  function syncPaidCellInList(recordId, paidDisplay) {
    if (!recordId) {
      return;
    }
    var field = paidFieldName();
    var $row = $(
      '#listview-table tbody tr.listViewEntries[data-id="' + recordId + '"]',
    );
    if (!$row.length) {
      return;
    }
    var $td = $row.find('td[data-name="' + field + '"]');
    if (!$td.length) {
      return;
    }
    var $value = $td.find(".value").first();
    if ($value.length) {
      $value.text(paidDisplay);
    } else {
      $td.text(paidDisplay);
    }
    var amount = parseInlineMoney(paidDisplay);
    $td.toggleClass("mk-so-paid-zero", amount === 0);
    $td.toggleClass("mk-so-paid-positive", amount > 0);
  }

  function saveInlineDetailPanel($panel, recordId) {
    var deferred = $.Deferred();
    var $saveBtn = $panel.find(".mk-so-inline-detail__save-btn");
    $saveBtn.prop("disabled", true);
    var postData = collectInlineDetailSaveData($panel, recordId);
    app.request.post({ data: postData }).then(function (err, response) {
      $saveBtn.prop("disabled", false);
      if (err) {
        var message =
          err && err.message ? err.message : "Không lưu được đơn hàng.";
        if (
          typeof app !== "undefined" &&
          app.helper &&
          app.helper.showErrorNotification
        ) {
          app.helper.showErrorNotification({ message: message });
        }
        deferred.reject(err);
        return;
      }
      if (response) {
        $panel
          .find(".mk-so-inline-detail__field-edit :input")
          .each(function () {
            var name = $(this).attr("name");
            if (!name || !response[name]) {
              return;
            }
            var displayValue = response[name].display_value;
            if (displayValue !== undefined && displayValue !== null) {
              if (
                name.indexOf("status") >= 0 ||
                name === "sostatus" ||
                name === "salesorder_status"
              ) {
                displayValue = translateStatusLabel(displayValue);
              }
              $panel
                .find(
                  '.mk-so-inline-detail__field[data-field-name="' +
                    name +
                    '"] .mk-so-inline-detail__field-view',
                )
                .html(displayValue);
            }
          });
        if (response.description) {
          var noteValue = response.description.value;
          if (noteValue !== undefined) {
            $panel
              .find(".mk-so-inline-detail__notes-input")
              .val(decodeHtmlEntities(noteValue));
          }
        }
        var paidName =
          $panel.find(".mk-so-inline-detail__paid-input").attr("name") ||
          $panel.attr("data-paid-field") ||
          "received";
        if (response[paidName]) {
          var paidDisplay =
            response[paidName].display_value !== undefined
              ? response[paidName].display_value
              : response[paidName].value;
          if (paidDisplay !== undefined && paidDisplay !== null) {
            $panel.find(".mk-so-inline-detail__paid-input").val(paidDisplay);
            $panel.find(".mk-so-inline-detail__paid-view").text(paidDisplay);
            syncPaidCellInList(recordId, paidDisplay);
          }
        } else {
          var localPaid =
            $panel.find(".mk-so-inline-detail__paid-input").val() || "0";
          $panel.find(".mk-so-inline-detail__paid-view").text(localPaid);
          syncPaidCellInList(recordId, localPaid);
        }
        recalcInlinePaidRemaining($panel);
      } else {
        updateInlineDetailViewValues($panel);
        syncPaidCellInList(
          recordId,
          $panel.find(".mk-so-inline-detail__paid-input").val() || "0",
        );
      }
      if (
        typeof app !== "undefined" &&
        app.helper &&
        app.helper.showSuccessNotification
      ) {
        app.helper.showSuccessNotification({
          message: app.vtranslate
            ? app.vtranslate("JS_RECORD_UPDATED")
            : "Đã lưu thay đổi.",
        });
      }
      deferred.resolve(response);
    });
    return deferred.promise();
  }

  function initPosInlineDetailPanel($container) {
    var $panel = $container.find(".mk-so-inline-detail").first();
    if (!$panel.length || $panel.data("mkSoInlineInit")) {
      return;
    }
    $panel.data("mkSoInlineInit", true);

    var recordId = String($panel.data("record-id") || "");
    var snapshot = captureInlineDetailSnapshot($panel);
    var $notes = $panel.find(".mk-so-inline-detail__notes-input");
    if ($notes.length) {
      $notes.val(decodeHtmlEntities($notes.val()));
    }

    $panel.on("click", ".mk-so-inline-detail__edit-toggle", function (e) {
      e.preventDefault();
      e.stopPropagation();
      setInlineDetailEditMode($panel, true);
      $panel.find(".mk-so-inline-detail__notes-input").focus();
    });

    $panel.on(
      "input change",
      ".mk-so-inline-detail__paid-input",
      function () {
        var val = $(this).val();
        $panel
          .find(
            '.mk-so-inline-detail__field[data-field-name="received"] .mk-so-inline-detail__field-edit :input, ' +
              '.mk-so-inline-detail__field[data-field-name="paid"] .mk-so-inline-detail__field-edit :input, ' +
              '.mk-so-inline-detail__field[data-field-name="paid_amount"] .mk-so-inline-detail__field-edit :input, ' +
              '.mk-so-inline-detail__field[data-field-name="amount_paid"] .mk-so-inline-detail__field-edit :input, ' +
              '.mk-so-inline-detail__field[data-field-name="mk_customer_paid"] .mk-so-inline-detail__field-edit :input',
          )
          .val(val);
        recalcInlinePaidRemaining($panel);
      },
    );

    $panel.on(
      "input change",
      '.mk-so-inline-detail__field[data-field-name="received"] :input, ' +
        '.mk-so-inline-detail__field[data-field-name="paid"] :input, ' +
        '.mk-so-inline-detail__field[data-field-name="paid_amount"] :input, ' +
        '.mk-so-inline-detail__field[data-field-name="amount_paid"] :input, ' +
        '.mk-so-inline-detail__field[data-field-name="mk_customer_paid"] :input',
      function () {
        var val = $(this).val();
        $panel.find(".mk-so-inline-detail__paid-input").val(val);
        $panel.find(".mk-so-inline-detail__paid-view").text(val || "0");
        recalcInlinePaidRemaining($panel);
      },
    );

    $panel.on("click", ".mk-so-inline-detail__process-btn", function (e) {
      e.preventDefault();
      e.stopPropagation();
      if (!recordId) {
        return;
      }
      window.location.href =
        "index.php?module=SalesOrder&view=Edit&record=" +
        encodeURIComponent(recordId) +
        "&app=SALES";
    });

    /* Confirm order: handled by document delegation in bindPosInlineDetailCapture */

    $panel.on("click", ".mk-so-inline-detail__cancel-edit", function (e) {
      e.preventDefault();
      e.stopPropagation();
      restoreInlineDetailSnapshot($panel, snapshot);
      setInlineDetailEditMode($panel, false);
    });

    $panel.on("click", ".mk-so-inline-detail__save-btn", function (e) {
      e.preventDefault();
      e.stopPropagation();
      if (!recordId) {
        return;
      }
      saveInlineDetailPanel($panel, recordId).then(function () {
        snapshot = captureInlineDetailSnapshot($panel);
        setInlineDetailEditMode($panel, false);
      });
    });

    $panel.on("click", ".mk-so-inline-detail__view-full-btn", function (e) {
      e.preventDefault();
      e.stopPropagation();
      var detailUrl = $panel.data("detail-url");
      if (!detailUrl) {
        detailUrl =
          "index.php?module=SalesOrder&view=Detail&record=" +
          encodeURIComponent(recordId) +
          "&app=SALES";
      }
      window.location.href = detailUrl;
    });

    $panel.on("click", ".mk-so-inline-detail__fullscreen-close", function (e) {
      e.preventDefault();
      e.stopPropagation();
      closeInlineFullView($panel);
    });

    $panel.on("click", ".mk-so-inline-detail__print-btn", function (e) {
      e.preventDefault();
      e.stopPropagation();
      if (!recordId) {
        return;
      }
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
      downloadInlineExportCsv($panel, recordId);
      closeInlineExcelPreview();
      $btn.attr("data-export-ready", "0");
      $btn.find(".mk-so-inline-detail__export-label").text("Export Excel");
    });
  }

  function loadPosInlineDetail(recordId, $row, $table) {
    var colspan = getTableColspan($table);
    var $detailRow = $(
      '<tr class="mk-so-inline-detail-row">' +
        '<td colspan="' +
        colspan +
        '">' +
        '<div class="mk-so-inline-detail mk-so-inline-detail--loading">' +
        '<span class="mk-so-inline-detail__spinner" aria-hidden="true"></span>' +
        "<span>Đang tải chi tiết đơn...</span>" +
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
        module: "SalesOrder",
        view: "Detail",
        mode: "showListInlineDetail",
        record: recordId,
        app: "SALES",
      },
    })
      .done(function (html) {
        if (posExpandedRecordId !== String(recordId)) {
          return;
        }
        $detailRow.find("td").html(html);
        if (typeof vtUtils !== "undefined" && vtUtils.applyFieldElementsView) {
          vtUtils.applyFieldElementsView($detailRow);
        }
        initPosInlineDetailPanel($detailRow);
      })
      .fail(function () {
        if (posExpandedRecordId !== String(recordId)) {
          return;
        }
        $detailRow
          .find("td")
          .html(
            '<div class="mk-so-inline-detail mk-so-inline-detail--error">' +
              "Không tải được chi tiết đơn. " +
              '<a href="' +
              ($row.data("recordurl") || "#") +
              '">Mở trang chi tiết</a>.' +
              "</div>",
          );
      })
      .always(function () {
        posInlineDetailLoading = false;
      });
  }

  function togglePosInlineDetail(recordId, $row) {
    var $table = $row.closest("#listview-table");
    if (!$table.length) {
      return;
    }
    recordId = String(recordId || "");
    if (!recordId) {
      return;
    }
    if (posExpandedRecordId === recordId) {
      collapsePosInlineDetail($table);
      return;
    }
    if (posInlineDetailLoading) {
      return;
    }
    collapsePosInlineDetail($table);
    posExpandedRecordId = recordId;
    posInlineDetailLoading = true;
    $row.addClass("mk-so-row-expanded");
    loadPosInlineDetail(recordId, $row, $table);
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

  function getListInstance() {
    return typeof Vtiger_List_Js !== "undefined" && Vtiger_List_Js.getInstance
      ? Vtiger_List_Js.getInstance()
      : null;
  }

  function syncPosRowSelectedClass() {
    var $root = getListViewContainer();
    $root.find("tr.listViewEntries").each(function () {
      var $row = $(this);
      $row.toggleClass(
        "mk-so-row-selected",
        $row.find(".listViewEntriesCheckBox:checked").length > 0,
      );
    });
  }

  function syncPosMassActionButtons() {
    var $root = getListViewContainer();
    var hasChecked = $root.find(".listViewEntriesCheckBox:checked").length > 0;
    if (!hasChecked) {
      var listInstance = getListInstance();
      if (listInstance && listInstance.getRecordSelectTrackerInstance) {
        var tracker = listInstance.getRecordSelectTrackerInstance();
        var selectedIds = tracker ? tracker.getSelectedIds() : null;
        if (jQuery.isArray(selectedIds)) {
          hasChecked = selectedIds.length > 0;
        } else if (typeof selectedIds === "string") {
          hasChecked =
            selectedIds !== "" && selectedIds.toLowerCase() !== "all";
        }
      }
    }
    var $btns = $("#mk-so-mass-delete-btn, #mk-so-mass-duplicate-btn");
    $btns.prop("disabled", !hasChecked);
    $btns.toggleClass("is-visible", hasChecked);
    $btns.attr("aria-hidden", hasChecked ? "false" : "true");
  }

  function getSelectedSalesOrderIds() {
    var ids = [];
    getListViewContainer()
      .find(".listViewEntriesCheckBox:checked")
      .each(function () {
        var id = parseInt($(this).val(), 10);
        if (id > 0) {
          ids.push(id);
        }
      });
    return ids;
  }

  function getCurrentCvIdForMassAction() {
    return (
      getListViewContainer()
        .find('[name="cvid"], #viewname, [name="viewname"]')
        .first()
        .val() || ""
    );
  }

  /** Remove deleted rows from DOM immediately (no full page reload). */
  function removeSalesOrderRowsFromList(ids) {
    ids = (ids || [])
      .map(function (id) {
        return parseInt(id, 10);
      })
      .filter(function (id) {
        return id > 0;
      });
    if (!ids.length) {
      return;
    }
    var $table = getPrimaryTable();
    var idSet = {};
    ids.forEach(function (id) {
      idSet[String(id)] = true;
    });
    $table.find("tr.listViewEntries").each(function () {
      var $row = $(this);
      var rowId =
        parseInt($row.attr("data-id"), 10) ||
        parseInt($row.data("id"), 10) ||
        parseInt($row.find(".listViewEntriesCheckBox").val(), 10) ||
        0;
      if (!idSet[String(rowId)]) {
        return;
      }
      var $next = $row.next("tr.mk-so-inline-detail-row");
      if ($next.length) {
        $next.remove();
      }
      $row.remove();
    });
    syncPosRowSelectedClass();
    syncPosMassActionButtons();
  }

  /** Soft-refresh list body so new/removed rows stay in sync without reload. */
  function refreshSalesOrdersListQuiet(extraParams) {
    var listInstance = getListInstance();
    if (listInstance && listInstance.clearList) {
      listInstance.clearList();
    }
    var params = $.extend({ nolistcache: "1" }, extraParams || {});
    if (listInstance && listInstance.loadListViewRecords) {
      listInstance.filterClick = false;
      listInstance.loadListViewRecords(params);
      return;
    }
    loadPosListRecords(params);
  }

  function runSalesOrdersMassDuplicate(ids, options) {
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
          message: "Chọn ít nhất 1 đơn hàng để nhân bản.",
        });
      }
      return;
    }
    var skipConfirm = !!options.skipConfirm;
    var message =
      ids.length === 1
        ? "Nhân bản đơn hàng đã chọn?"
        : "Nhân bản " + ids.length + " đơn hàng đã chọn?";
    var run = function () {
      var postData = {
        module: "SalesOrder",
        action: "MassDuplicate",
        selected_ids: JSON.stringify(ids),
        excluded_ids: JSON.stringify([]),
        viewname: getCurrentCvIdForMassAction(),
        app: "SALES",
      };
      if (app.helper && app.helper.showProgress) {
        app.helper.showProgress();
      }
      app.request.post({ data: postData }).then(function (err, res) {
        if (app.helper && app.helper.hideProgress) {
          app.helper.hideProgress();
        }
        if (err) {
          if (app.helper && app.helper.showErrorNotification) {
            app.helper.showErrorNotification({
              message: (err && err.message) || "Không nhân bản được.",
            });
          }
          return;
        }
        var result = res || {};
        if (result.success === false) {
          if (app.helper && app.helper.showErrorNotification) {
            app.helper.showErrorNotification({
              message: result.message || "Không nhân bản được.",
            });
          }
          return;
        }
        if (app.helper && app.helper.showSuccessNotification) {
          app.helper.showSuccessNotification({
            message: result.message || "Đã nhân bản đơn hàng.",
          });
        }
        // Stay on list — show duplicated rows without opening Detail.
        collapsePosInlineDetail(getPrimaryTable());
        refreshSalesOrdersListQuiet({ page: "1" });
      });
    };
    if (skipConfirm) {
      run();
      return;
    }
    if (app.helper && app.helper.showConfirmationBox) {
      app.helper.showConfirmationBox({ message: message }).then(run);
    } else if (window.confirm(message)) {
      run();
    }
  }

  function massDuplicateSalesOrders() {
    runSalesOrdersMassDuplicate(getSelectedSalesOrderIds());
  }

  function massDeleteSalesOrders() {
    if (!isSalesOrderSalesList()) {
      return;
    }
    var ids = getSelectedSalesOrderIds();
    if (!ids.length) {
      if (app.helper && app.helper.showErrorNotification) {
        app.helper.showErrorNotification({
          message: "Chọn ít nhất 1 đơn hàng để xóa.",
        });
      } else {
        window.alert("Chọn ít nhất 1 đơn hàng để xóa.");
      }
      return;
    }
    var message = app.vtranslate
      ? app.vtranslate("LBL_MASS_DELETE_CONFIRMATION")
      : "Xóa " + ids.length + " đơn hàng đã chọn?";
    var run = function () {
      var postData = {
        module: "SalesOrder",
        action: "MassDelete",
        selected_ids: JSON.stringify(ids),
        excluded_ids: JSON.stringify([]),
        viewname: getCurrentCvIdForMassAction(),
        app: "SALES",
      };
      if (app.helper && app.helper.showProgress) {
        app.helper.showProgress();
      }
      app.request.post({ data: postData }).then(function (err) {
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
            message: "Đã xóa " + ids.length + " đơn hàng.",
          });
        }
        removeSalesOrderRowsFromList(ids);
        refreshSalesOrdersListQuiet();
      });
    };
    if (app.helper && app.helper.showConfirmationBox) {
      app.helper.showConfirmationBox({ message: message }).then(run);
    } else if (window.confirm(message || "Xóa " + ids.length + " đơn hàng?")) {
      run();
    }
  }

  function bindInlineDuplicateButton() {
    $(document)
      .off("click.mkSoInlineDup", ".mk-so-inline-detail__dup-btn")
      .on("click.mkSoInlineDup", ".mk-so-inline-detail__dup-btn", function (e) {
        if (!isSalesOrderSalesList()) {
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
          runSalesOrdersMassDuplicate([id]);
        }
      });
  }

  function bindPosSelectionEvents() {
    var $root = getListViewContainer();
    if (!$root.length || $root.data("mkSoPosSelectionBound")) {
      return;
    }
    $root.data("mkSoPosSelectionBound", 1);

    $root
      .off(
        "change.mkSoPosRowCheck",
        ".listViewEntriesCheckBox, .listViewEntriesMainCheckBox",
      )
      .on(
        "change.mkSoPosRowCheck",
        ".listViewEntriesCheckBox, .listViewEntriesMainCheckBox",
        function (e) {
          e.stopPropagation();
          syncPosRowSelectedClass();
          setTimeout(syncPosMassActionButtons, 0);
        },
      );
  }

  function bindPosMassDuplicateButton() {
    $(document)
      .off("click.mkSoMassDup", "#mk-so-mass-duplicate-btn")
      .on("click.mkSoMassDup", "#mk-so-mass-duplicate-btn", function (e) {
        if (!isSalesOrderSalesList()) return;
        e.preventDefault();
        massDuplicateSalesOrders();
      });
  }

  function bindPosMassDeleteButton() {
    $(document)
      .off("click.mkSoMassDel", "#mk-so-mass-delete-btn")
      .on("click.mkSoMassDel", "#mk-so-mass-delete-btn", function (e) {
        if (!isSalesOrderSalesList()) return;
        e.preventDefault();
        e.stopImmediatePropagation();
        massDeleteSalesOrders();
        return false;
      });
  }

  /** Row-menu delete: drop row immediately, then soft-refresh (no full reload). */
  function patchSalesOrderDeleteRecord() {
    if (
      typeof Vtiger_List_Js === "undefined" ||
      Vtiger_List_Js.prototype.__mkSoDeletePatched
    ) {
      return;
    }
    var proto = Vtiger_List_Js.prototype;
    var origDelete = proto._deleteRecord;
    proto._deleteRecord = function (recordId, extraParams) {
      if (!isSalesOrderSalesList()) {
        return origDelete.apply(this, arguments);
      }
      var thisInstance = this;
      var postData = {
        data: {
          module: "SalesOrder",
          action: "DeleteAjax",
          record: recordId,
          parent: app.getParentModuleName(),
          viewname: this.getCurrentCvId(),
        },
      };
      if (typeof extraParams === "undefined") {
        extraParams = {};
      }
      $.extend(postData.data, extraParams);
      if (app.helper && app.helper.showProgress) {
        app.helper.showProgress();
      }
      app.request.post(postData).then(function (err) {
        if (app.helper && app.helper.hideProgress) {
          app.helper.hideProgress();
        }
        if (err == null) {
          removeSalesOrderRowsFromList([recordId]);
          if (thisInstance.clearList) {
            thisInstance.clearList();
          }
          refreshSalesOrdersListQuiet();
        } else if (app.helper && app.helper.showErrorNotification) {
          app.helper.showErrorNotification({
            message: app.vtranslate(err.message),
          });
        }
      });
    };
    var origMassDelete = proto.performMassDeleteRecords;
    proto.performMassDeleteRecords = function () {
      if (isSalesOrderSalesList()) {
        massDeleteSalesOrders();
        return;
      }
      return origMassDelete.apply(this, arguments);
    };
    proto.__mkSoDeletePatched = true;
  }

  function patchPosListViewActions() {
    if (
      typeof Vtiger_List_Js === "undefined" ||
      Vtiger_List_Js.prototype.__mkSoListActionsPatched
    ) {
      return;
    }
    var original = Vtiger_List_Js.prototype.registerPostLoadListViewActions;
    Vtiger_List_Js.prototype.registerPostLoadListViewActions = function () {
      original.apply(this, arguments);
      if (isSalesOrderSalesList()) {
        syncPosRowSelectedClass();
        syncPosMassActionButtons();
      }
    };
    Vtiger_List_Js.prototype.__mkSoListActionsPatched = true;
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

  function handlePosInlineDetailClick(e) {
    if (!isSalesOrderSalesList()) {
      return false;
    }
    var target = e.target;
    if (!target || !target.closest) {
      return false;
    }
    if (isPosInlineDetailInteraction(target)) {
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

    togglePosInlineDetail(getRowRecordId($(row)), $(row));
    return true;
  }

  function registerSalesPosRowClickEvent(listInstance) {
    var $container =
      listInstance && listInstance.getListViewContainer
        ? listInstance.getListViewContainer()
        : getListViewContainer();
    unregisterVtigerRowNavigation($container);
  }

  function patchInlineDetailRowClick() {
    if (
      typeof Vtiger_List_Js === "undefined" ||
      Vtiger_List_Js.prototype._mkSoInlineDetailPatched
    ) {
      return;
    }
    Vtiger_List_Js.prototype._mkSoInlineDetailPatched = true;
    var originalRegister = Vtiger_List_Js.prototype.registerRowClickEvent;
    Vtiger_List_Js.prototype.registerRowClickEvent = function () {
      if (!isSalesOrderSalesList()) {
        return originalRegister.call(this);
      }
      registerSalesPosRowClickEvent(this);
    };
    var originalRegisterEvents = Vtiger_List_Js.prototype.registerEvents;
    if (originalRegisterEvents) {
      Vtiger_List_Js.prototype.registerEvents = function () {
        var result = originalRegisterEvents.apply(this, arguments);
        if (isSalesOrderSalesList()) {
          registerSalesPosRowClickEvent(this);
        }
        return result;
      };
    }
  }

  function bindPosInlineDetailCapture() {
    if (
      document.documentElement.getAttribute("data-mk-so-inline-detail-bound")
    ) {
      return;
    }
    document.documentElement.setAttribute(
      "data-mk-so-inline-detail-bound",
      "1",
    );
    document.addEventListener(
      "click",
      function (e) {
        handlePosInlineDetailClick(e);
      },
      true,
    );

    /* Backup: confirm button even if panel init missed */
    $(document)
      .off("click.mkSoConfirmOrder", ".mk-so-inline-detail__confirm-order-btn")
      .on(
        "click.mkSoConfirmOrder",
        ".mk-so-inline-detail__confirm-order-btn",
        function (e) {
          e.preventDefault();
          e.stopPropagation();
          var $btn = $(this);
          if ($btn.data("mkBusy")) {
            return;
          }
          var $panel = $btn.closest(".mk-so-inline-detail");
          var recordId = String($panel.data("record-id") || "");
          if (!recordId) {
            return;
          }
          confirmSalesOrderWithWarehouse($panel, recordId, $btn);
        },
      );
  }

  function paidFieldName() {
    var cfg = listConfig();
    return cfg.paidField || "received";
  }

  function dueFieldName() {
    var cfg = listConfig();
    return cfg.dueField || "hdnGrandTotal";
  }

  function globalSearchFields() {
    var cfg = listConfig();
    return cfg.globalSearchFields || ["salesorder_no", "customerno", "subject"];
  }

  function parseMoneyText(text) {
    if (window.MkCurrency && typeof MkCurrency.parse === "function") {
      return MkCurrency.parse(text);
    }
    var raw = String(text || "").replace(/[^\d.,-]/g, "");
    if (/^\d{1,3}(\.\d{3})+(,\d+)?$/.test(raw)) {
      raw = raw.replace(/\./g, "").replace(",", ".");
    } else {
      raw = raw.replace(/,/g, "");
    }
    var num = parseFloat(raw);
    return isNaN(num) ? 0 : num;
  }

  function formatMoneyNumber(num) {
    if (window.MkCurrency && typeof MkCurrency.format === "function") {
      return MkCurrency.format(num, { decimals: 0 });
    }
    var n = Math.round(num);
    return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
  }

  function stripCurrencyFromText(text) {
    if (window.MkCurrency && typeof MkCurrency.parse === "function") {
      return String(MkCurrency.parse(text));
    }
    return String(text || "")
      .replace(/[^\d.,-]/g, "")
      .replace(/\./g, "")
      .replace(/,/g, ".");
  }

  function formatPosMoneyCells($table) {
    var dueField = dueFieldName();
    var paidField = paidFieldName();
    [dueField, paidField, "total"].forEach(function (fieldName) {
      $table
        .find('tbody td[data-name="' + fieldName + '"] .value')
        .each(function () {
          var $value = $(this);
          if ($value.data("mkPosMoney")) {
            return;
          }
          var amount = parseMoneyText($value.text());
          $value.text(formatMoneyNumber(amount));
          $value.data("mkPosMoney", 1);
        });
    });
  }

  function formatPosDateTimeCells($table) {
    $table
      .find(
        'tbody td[data-name="createdtime"] .value, tbody td.mk-so-col-time .value',
      )
      .each(function () {
        var $value = $(this);
        if ($value.data("mkPosDate")) {
          return;
        }
        var raw = $.trim($value.text());
        if (!raw || raw === "--") {
          return;
        }
        var formatted = null;
        var match = raw.match(
          /^(\d{4})-(\d{2})-(\d{2})(?:\s+(\d{1,2}):(\d{2})(?::\d{2})?)?/,
        );
        if (match) {
          formatted =
            match[3] +
            "/" +
            match[2] +
            "/" +
            match[1] +
            " " +
            ("0" + (match[4] || "0")).slice(-2) +
            ":" +
            (match[5] || "00");
        }
        if (!formatted) {
          match = raw.match(
            /^(\d{1,2})-(\d{1,2})-(\d{4})\s+(\d{1,2}):(\d{2})(?::\d{2})?\s*(AM|PM)?/i,
          );
          if (match) {
            var hour = parseInt(match[4], 10);
            var ampm = (match[6] || "").toUpperCase();
            if (ampm === "PM" && hour < 12) {
              hour += 12;
            }
            if (ampm === "AM" && hour === 12) {
              hour = 0;
            }
            formatted =
              ("0" + match[2]).slice(-2) +
              "/" +
              ("0" + match[1]).slice(-2) +
              "/" +
              match[3] +
              " " +
              ("0" + hour).slice(-2) +
              ":" +
              match[5];
          }
        }
        if (!formatted) {
          match = raw.match(
            /^(\d{1,2})\/(\d{1,2})\/(\d{4})(?:\s+(\d{1,2}):(\d{2}))?/,
          );
          if (match) {
            formatted =
              ("0" + match[1]).slice(-2) +
              "/" +
              ("0" + match[2]).slice(-2) +
              "/" +
              match[3] +
              " " +
              ("0" + (match[4] || "0")).slice(-2) +
              ":" +
              (match[5] || "00");
          }
        }
        if (formatted) {
          $value.text(formatted);
          $value.data("mkPosDate", 1);
        }
      });
  }

  function hideLegacyPosColumns($table) {
    var allowed = [
      "salesorder_no",
      "createdtime",
      "account_id",
      "contact_id",
      "mk_warehouse_name",
      "hdnGrandTotal",
      "total",
      paidFieldName(),
      resolveStatusField($table),
    ];
    allowed = allowed.filter(function (f, i, arr) {
      return f && arr.indexOf(f) === i;
    });
    statusCandidates().forEach(function (f) {
      if (allowed.indexOf(f) < 0) {
        allowed.push(f);
      }
    });
    $table
      .find("thead tr.listViewContentHeader th a[data-columnname]")
      .each(function () {
        var name = $(this).attr("data-columnname");
        if (name && allowed.indexOf(name) < 0) {
          $(this).closest("th").addClass("mk-so-list-col-hidden");
        }
      });
    $table.find("tbody td[data-name]").each(function () {
      var name = $(this).attr("data-name");
      if (name && (allowed.indexOf(name) < 0 || name === "customerno")) {
        $(this).addClass("mk-so-list-col-hidden");
      }
    });
    // Injected Kho header has no data-columnname — keep it aligned with warehouse cells.
    $table.find("thead th.mk-so-col-warehouse").removeClass("mk-so-list-col-hidden");
  }

  function filterMeta() {
    return listConfig().filterMeta || {};
  }

  function pad2(n) {
    return ("0" + n).slice(-2);
  }

  function getUserDateFormat() {
    if (typeof app !== "undefined" && app.getDateFormat) {
      return String(app.getDateFormat()).toLowerCase();
    }
    return "dd-mm-yyyy";
  }

  function formatDateByUserFormat(dateObj) {
    var fmt = getUserDateFormat();
    var y = dateObj.getFullYear();
    var m = pad2(dateObj.getMonth() + 1);
    var d = pad2(dateObj.getDate());
    if (fmt.indexOf("yyyy") === 0) {
      return y + "-" + m + "-" + d;
    }
    if (fmt.indexOf("dd") >= 0 && fmt.indexOf("dd") < fmt.indexOf("mm")) {
      return d + "-" + m + "-" + y;
    }
    return m + "-" + d + "-" + y;
  }

  function normalizeFilterDateInput(dateStr, endOfDay) {
    var raw = $.trim(dateStr || "");
    if (!raw) {
      return "";
    }
    if (raw.indexOf(" ") >= 0) {
      return raw;
    }
    return raw + " " + (endOfDay ? "23:59:59" : "00:00:00");
  }

  function buildDateBetweenValue(fromStr, toStr) {
    var from = normalizeFilterDateInput(fromStr, false);
    var to = normalizeFilterDateInput(toStr, true);
    if (from && to) {
      return from + "," + to;
    }
    if (from) {
      return from + "," + from.replace(/\d{2}:\d{2}:\d{2}$/, "23:59:59");
    }
    if (to) {
      return to + "," + to;
    }
    return "";
  }

  function getMonthRange() {
    var now = new Date();
    var start = new Date(now.getFullYear(), now.getMonth(), 1);
    var end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    return {
      start: formatDateByUserFormat(start) + " 00:00:00",
      end: formatDateByUserFormat(end) + " 23:59:59",
    };
  }

  function cellMoneyAmount($td) {
    if (!$td || !$td.length) {
      return 0;
    }
    var raw = $td.attr("data-rawvalue");
    if (raw !== undefined && raw !== null && String(raw).trim() !== "") {
      return parseMoneyText(raw);
    }
    return parseMoneyText($td.find(".value").first().text());
  }

  function readPosFilterState() {
    var $panel = $("#mk-so-pos-filter-panel");
    if (!$panel.length) {
      return null;
    }
    var meta = filterMeta();
    var state = {
      timeMode:
        $panel.find('input[name="mk_so_filter_time"]:checked').val() || "all",
      timeFrom: $.trim($("#mk-so-filter-time-from").val()),
      timeTo: $.trim($("#mk-so-filter-time-to").val()),
      dueMode:
        $panel.find('input[name="mk_so_filter_due_time"]:checked').val() ||
        "all",
      dueFrom: $.trim($("#mk-so-filter-due-from").val()),
      dueTo: $.trim($("#mk-so-filter-due-to").val()),
      statuses: [],
      carrier: $.trim($("#mk-so-filter-carrier").val()),
      region: $.trim($("#mk-so-filter-region").val()),
      payment: $.trim($("#mk-so-filter-payment").val()),
    };
    $("#mk-so-filter-status-chips .mk-so-pos-filter-chip.is-selected").each(
      function () {
        var val = $(this).attr("data-value");
        if (val) {
          state.statuses.push(val);
        }
      },
    );
    state.statusField =
      meta.statusField || resolveStatusField(getPrimaryTable()) || "sostatus";
    state.createdField = meta.createdTimeField || "createdtime";
    state.dueField = meta.dueDateField || "duedate";
    state.carrierField = meta.carrierField || "carrier";
    state.regionField = meta.shipCityField || "";
    state.paymentField = meta.paymentField || "";
    return state;
  }

  function buildPosAdvancedSearchParams(state) {
    state = state || readPosFilterState();
    if (!state) {
      return [];
    }
    var andGroup = [];
    var orGroup = [];
    var range;
    var dateValue;

    if (state.timeMode === "this_month") {
      range = getMonthRange();
      andGroup.push([state.createdField, "bw", range.start + "," + range.end]);
    } else if (state.timeMode === "custom") {
      dateValue = buildDateBetweenValue(state.timeFrom, state.timeTo);
      if (dateValue) {
        andGroup.push([state.createdField, "bw", dateValue]);
      }
    }

    if (state.dueMode === "custom" && state.dueField) {
      dateValue = buildDateBetweenValue(state.dueFrom, state.dueTo);
      if (dateValue) {
        andGroup.push([state.dueField, "bw", dateValue]);
      }
    }

    if (state.statuses.length === 1) {
      andGroup.push([state.statusField, "e", state.statuses[0]]);
    } else if (state.statuses.length > 1) {
      state.statuses.forEach(function (status) {
        orGroup.push([state.statusField, "e", status]);
      });
    }

    if (state.carrier && state.carrierField) {
      andGroup.push([state.carrierField, "e", state.carrier]);
    }
    if (state.region && state.regionField) {
      andGroup.push([state.regionField, "c", state.region]);
    }
    if (state.payment && state.paymentField) {
      andGroup.push([state.paymentField, "e", state.payment]);
    }

    if (orGroup.length && andGroup.length) {
      return [andGroup, orGroup];
    }
    if (orGroup.length) {
      return [[], orGroup];
    }
    if (andGroup.length) {
      return [andGroup];
    }
    return [];
  }

  function mergePosSearchPayloads(advParams, quickParams) {
    var andGroup = [];
    var orGroup = [];

    function absorbGroup(group, defaultGlue) {
      if (!group || !group.length) {
        return;
      }
      if (defaultGlue === "or") {
        orGroup = orGroup.concat(group);
      } else {
        andGroup = andGroup.concat(group);
      }
    }

    if (advParams && advParams.length) {
      absorbGroup(advParams[0], "and");
      if (advParams.length > 1) {
        absorbGroup(advParams[1], "or");
      }
    }
    if (quickParams && quickParams.length) {
      if (quickParams[0] && quickParams[0].length) {
        absorbGroup(quickParams[0], "and");
      }
      if (quickParams.length > 1 && quickParams[1] && quickParams[1].length) {
        absorbGroup(quickParams[1], "or");
      }
    }

    var merged = [];
    if (andGroup.length) {
      merged.push(andGroup);
    }
    if (orGroup.length) {
      merged.push(orGroup);
    }
    return merged;
  }

  function getPersistedSortParams() {
    var $lv = getListViewContainer();
    var orderBy = $.trim($lv.find("#orderBy").val() || "");
    var sortOrder = $.trim($lv.find("#sortOrder").val() || "");
    if (!orderBy) {
      return {};
    }
    return {
      orderby: orderBy,
      sortorder: sortOrder || "DESC",
    };
  }

  function loadPosListRecords(extraParams) {
    var listInstance =
      Vtiger_List_Js.getInstance && Vtiger_List_Js.getInstance();
    if (!listInstance || !listInstance.loadListViewRecords) {
      return;
    }
    var params = $.extend({}, getPersistedSortParams(), extraParams || {});
    listInstance.loadListViewRecords(params);
  }
  function runPosAdvancedFilter() {
    posFilterApplied = true;
    var advParams = buildPosAdvancedSearchParams(readPosFilterState());
    var quickParams = buildPosSearchParams(livePosSearchQuery);
    var merged = mergePosSearchPayloads(advParams, quickParams);
    var payload = JSON.stringify(merged);
    lastPosSearchPayload = payload;
    var listInstance =
      Vtiger_List_Js.getInstance && Vtiger_List_Js.getInstance();
    if (!listInstance || !listInstance.loadListViewRecords) {
      return;
    }
    listInstance.filterClick = false;
    getListViewContainer().find("#currentSearchParams").val(payload);
    try {
      if (typeof app !== "undefined" && app.helper && app.helper.showProgress) {
        app.helper.showProgress();
      }
    } catch (e) {
      /* ignore */
    }
    loadPosListRecords({
      page: "1",
      search_params: payload,
      nolistcache: "1",
    });
  }

  function clearPosAdvancedFilter() {
    posFilterApplied = false;
    var $panel = $("#mk-so-pos-filter-panel");
    $panel
      .find('input[name="mk_so_filter_time"][value="all"]')
      .prop("checked", true);
    $panel
      .find('input[name="mk_so_filter_due_time"][value="all"]')
      .prop("checked", true);
    $("#mk-so-filter-time-custom, #mk-so-filter-due-custom").prop(
      "hidden",
      true,
    );
    $panel.find(".mk-so-pos-filter-date").val("");
    $panel.find(".mk-so-pos-filter-select").val("");
    $panel
      .find(".mk-so-pos-filter-chip.is-selected")
      .removeClass("is-selected");
    refreshPosStatusChipLabels();
    livePosSearchQuery = "";
    lastPosSearchPayload = "";
    $("#mk-so-pos-search").val("");
    $("#mk-so-pos-search-clear").prop("hidden", true);
    getListViewContainer().find("#currentSearchParams").val("");
    loadPosListRecords({ page: "1", search_params: "[]", nolistcache: "1" });
  }

  function refreshPosStatusChipLabels() {
    $("#mk-so-filter-status-chips .mk-so-pos-filter-chip").each(function () {
      var $chip = $(this);
      var val = $chip.attr("data-value");
      var label = translateStatusLabel(
        $chip.attr("data-label") ||
          $chip.find(".mk-so-pos-filter-chip__text").text() ||
          val,
      );
      $chip.find(".mk-so-pos-filter-chip__text").text(label);
    });
    $("#mk-so-filter-status-pool .mk-so-pos-filter-status-option").each(
      function () {
        var $opt = $(this);
        var val = $opt.attr("data-value");
        $opt.text(translateStatusLabel($opt.text() || val));
      },
    );
    syncPosStatusPoolVisibility();
  }

  function syncPosStatusPoolVisibility() {
    $("#mk-so-filter-status-pool .mk-so-pos-filter-status-option").each(
      function () {
        var val = $(this).attr("data-value");
        var selected = $(
          '#mk-so-filter-status-chips .mk-so-pos-filter-chip[data-value="' +
            val +
            '"]',
        ).hasClass("is-selected");
        $(this).toggleClass("is-hidden", selected);
      },
    );
  }

  function initPosFilterPanel() {
    var $panel = $("#mk-so-pos-filter-panel");
    if (!$panel.length || $panel.data("mkFilterBound")) {
      return;
    }
    $panel.data("mkFilterBound", 1);
    refreshPosStatusChipLabels();

    if (typeof vtUtils !== "undefined" && vtUtils.applyFieldElementsView) {
      vtUtils.applyFieldElementsView($panel);
    }

    $panel
      .off("change.mkSoFilterTime", 'input[name="mk_so_filter_time"]')
      .on(
        "change.mkSoFilterTime",
        'input[name="mk_so_filter_time"]',
        function () {
          var custom = $(this).val() === "custom";
          $("#mk-so-filter-time-custom").prop("hidden", !custom);
        },
      )
      .off("change.mkSoFilterDue", 'input[name="mk_so_filter_due_time"]')
      .on(
        "change.mkSoFilterDue",
        'input[name="mk_so_filter_due_time"]',
        function () {
          var custom = $(this).val() === "custom";
          $("#mk-so-filter-due-custom").prop("hidden", !custom);
        },
      )
      .off(
        "click.mkSoFilterChip",
        "#mk-so-filter-status-chips .mk-so-pos-filter-chip",
      )
      .on(
        "click.mkSoFilterChip",
        "#mk-so-filter-status-chips .mk-so-pos-filter-chip",
        function (e) {
          if ($(e.target).closest(".mk-so-pos-filter-chip__remove").length) {
            $(this).removeClass("is-selected");
          } else {
            $(this).toggleClass("is-selected");
          }
          syncPosStatusPoolVisibility();
        },
      )
      .off(
        "click.mkSoFilterStatusAdd",
        "#mk-so-filter-status-pool .mk-so-pos-filter-status-option",
      )
      .on(
        "click.mkSoFilterStatusAdd",
        "#mk-so-filter-status-pool .mk-so-pos-filter-status-option",
        function () {
          var val = $(this).attr("data-value");
          var $chip = $(
            '#mk-so-filter-status-chips .mk-so-pos-filter-chip[data-value="' +
              val +
              '"]',
          );
          if ($chip.length) {
            $chip.addClass("is-selected");
            syncPosStatusPoolVisibility();
          }
        },
      )
      .off("click.mkSoFilterClose", "#mk-so-pos-filter-close")
      .on("click.mkSoFilterClose", "#mk-so-pos-filter-close", function (e) {
        e.preventDefault();
        togglePosAdvancedFilter(false);
      })
      .off("click.mkSoFilterApply", "#mk-so-pos-filter-apply")
      .on("click.mkSoFilterApply", "#mk-so-pos-filter-apply", function (e) {
        e.preventDefault();
        runPosAdvancedFilter();
      })
      .off("click.mkSoFilterClear", "#mk-so-pos-filter-clear")
      .on("click.mkSoFilterClear", "#mk-so-pos-filter-clear", function (e) {
        e.preventDefault();
        clearPosAdvancedFilter();
      });
  }

  function syncPosFilterUi() {
    var open = posFilterOpen;
    var $layout = $("#mk-so-pos-layout");
    var $main = $layout.find(".mk-so-pos-main").first();
    $layout.toggleClass("mk-so-filter-open", open);
    $("#mk-so-pos-filter-panel").attr("aria-hidden", open ? "false" : "true");
    $("#mk-so-pos-filter-btn").toggleClass("is-active", open);
    if (!open && $main.length) {
      $main.css("min-height", "");
    }
    try {
      window.dispatchEvent(new Event("resize"));
    } catch (e) {
      /* ignore */
    }
  }

  function togglePosAdvancedFilter(forceOpen) {
    posFilterOpen = typeof forceOpen === "boolean" ? forceOpen : !posFilterOpen;
    syncPosFilterUi();
    if (posFilterOpen) {
      initPosFilterPanel();
    }
  }

  function maybeOpenFilterFromState() {
    if (posFilterStateSynced) {
      return;
    }
    posFilterStateSynced = true;
    var raw = $.trim(
      getListViewContainer().find("#currentSearchParams").val() || "",
    );
    if (!raw || raw === "[]" || raw === "[[]]" || raw === "null") {
      return;
    }
    try {
      var parsed = JSON.parse(raw);
      var hasFilters =
        $.isArray(parsed) &&
        parsed.some(function (group) {
          return $.isArray(group) && group.length > 0;
        });
      if (hasFilters) {
        togglePosAdvancedFilter(true);
      }
    } catch (e) {
      /* ignore */
    }
  }

  function translateStatusLabel(text) {
    var raw = $.trim(text || "");
    if (!raw || raw === "--") {
      return "--";
    }
    if (STATUS_VI_LABELS[raw]) {
      return STATUS_VI_LABELS[raw];
    }
    var lower = raw.toLowerCase();
    var key;
    for (key in STATUS_VI_LABELS) {
      if (STATUS_VI_LABELS.hasOwnProperty(key) && key.toLowerCase() === lower) {
        return STATUS_VI_LABELS[key];
      }
    }
    return raw;
  }

  function normalizeStatusTone(text) {
    var t = String(text || "").toLowerCase();
    if (!t || t === "--") {
      return "neutral";
    }
    if (
      t.indexOf("shipped") >= 0 ||
      t.indexOf("deliver") >= 0 ||
      t.indexOf("đã giao") >= 0 ||
      t.indexOf("hoàn thành") >= 0 ||
      t.indexOf("đã soạn") >= 0 ||
      t.indexOf("packed") >= 0
    ) {
      return "success";
    }
    if (
      t.indexOf("xác nhận") >= 0 ||
      t.indexOf("approved") >= 0 ||
      t.indexOf("duyệt") >= 0 ||
      t.indexOf("đang soạn") >= 0 ||
      t.indexOf("picking") >= 0
    ) {
      return "confirmed";
    }
    if (
      t.indexOf("tạm") >= 0 ||
      t.indexOf("created") >= 0 ||
      t.indexOf("draft") >= 0 ||
      t.indexOf("đã tạo") >= 0 ||
      t.indexOf("pending") >= 0 ||
      t.indexOf("chờ") >= 0 ||
      t.indexOf("waiting_print") >= 0
    ) {
      return "draft";
    }
    if (
      t.indexOf("cancel") >= 0 ||
      t.indexOf("reject") >= 0 ||
      t.indexOf("hủy") >= 0 ||
      t.indexOf("từ chối") >= 0
    ) {
      return "danger";
    }
    if (t.indexOf("paid") >= 0 || t.indexOf("thanh toán") >= 0) {
      return "success";
    }
    return "neutral";
  }

  function assignPosColumnClasses($table) {
    var statusField = resolveStatusField($table);
    var paidField = paidFieldName();
    if (statusField && !POS_COL_CLASS_BY_FIELD[statusField]) {
      POS_COL_CLASS_BY_FIELD[statusField] = "mk-so-col-status";
    }
    if (paidField && !POS_COL_CLASS_BY_FIELD[paidField]) {
      POS_COL_CLASS_BY_FIELD[paidField] = "mk-so-col-paid";
    }
    $table
      .find("thead tr.listViewContentHeader th a[data-columnname]")
      .each(function () {
        var field = $(this).attr("data-columnname");
        if (field && POS_COL_CLASS_BY_FIELD[field]) {
          $(this).closest("th").addClass(POS_COL_CLASS_BY_FIELD[field]);
        }
      });
    $table.find("tbody tr.listViewEntries").each(function () {
      $(this)
        .children("td[data-name]")
        .each(function () {
          var field = $(this).attr("data-name");
          if (field && POS_COL_CLASS_BY_FIELD[field]) {
            $(this).addClass(POS_COL_CLASS_BY_FIELD[field]);
          }
        });
    });
  }

  function enhancePaidCells($table) {
    var field = paidFieldName();
    $table.find('tbody td[data-name="' + field + '"]').each(function () {
      var $td = $(this);
      var $value = $td.find(".value").first();
      if (!$value.length) {
        return;
      }
      var amount = parseMoneyText($value.text());
      $td.toggleClass("mk-so-paid-zero", amount === 0);
      $td.toggleClass("mk-so-paid-positive", amount > 0);
    });
  }

  function buildPosSearchParams(query) {
    query = (query || "").toString().trim();
    if (!query.length) {
      return [];
    }
    var fields = globalSearchFields();
    var conditions = [];
    var i;
    for (i = 0; i < fields.length; i++) {
      conditions.push([fields[i], "c", query]);
    }
    if (conditions.length === 1) {
      return [conditions];
    }
    return [[], conditions];
  }

  function runPosQuickSearch(query) {
    query = query != null ? String(query).trim() : livePosSearchQuery;
    livePosSearchQuery = query;
    var quickParams = buildPosSearchParams(query);
    var advParams = posFilterApplied
      ? buildPosAdvancedSearchParams(readPosFilterState())
      : [];
    var merged = mergePosSearchPayloads(advParams, quickParams);
    var payload = JSON.stringify(merged);
    if (payload === lastPosSearchPayload) {
      return;
    }
    lastPosSearchPayload = payload;
    var listInstance =
      Vtiger_List_Js.getInstance && Vtiger_List_Js.getInstance();
    if (!listInstance || !listInstance.loadListViewRecords) {
      return;
    }
    listInstance.filterClick = false;
    getListViewContainer().find("#currentSearchParams").val(payload);
    try {
      if (typeof app !== "undefined" && app.helper && app.helper.showProgress) {
        app.helper.showProgress();
      }
    } catch (e) {
      /* ignore */
    }
    loadPosListRecords({
      page: "1",
      search_params: payload,
      nolistcache: "1",
    });
  }

  function schedulePosQuickSearch(immediate) {
    if (posSearchTimer) {
      clearTimeout(posSearchTimer);
      posSearchTimer = null;
    }
    if (immediate) {
      runPosQuickSearch();
      return;
    }
    posSearchTimer = setTimeout(function () {
      posSearchTimer = null;
      runPosQuickSearch();
    }, 600);
  }

  function bindPosToolbarEvents() {
    if (posSearchBound) {
      return;
    }
    posSearchBound = true;
    var $root = getListViewContainer();
    $(document)
      .off("input.mkSoPosSearch", "#mk-so-pos-search")
      .on("input.mkSoPosSearch", "#mk-so-pos-search", function () {
        var val = $.trim($(this).val());
        livePosSearchQuery = val;
        $("#mk-so-pos-search-clear").prop("hidden", !val);
        schedulePosQuickSearch();
      })
      .off("keydown.mkSoPosSearch", "#mk-so-pos-search")
      .on("keydown.mkSoPosSearch", "#mk-so-pos-search", function (ev) {
        if (ev.key === "Enter") {
          ev.preventDefault();
          schedulePosQuickSearch(true);
        }
        if (ev.key === "Escape") {
          ev.preventDefault();
          livePosSearchQuery = "";
          lastPosSearchPayload = "";
          $(this).val("");
          $("#mk-so-pos-search-clear").prop("hidden", true);
          schedulePosQuickSearch(true);
        }
      })
      .off("click.mkSoPosSearchClear", "#mk-so-pos-search-clear")
      .on("click.mkSoPosSearchClear", "#mk-so-pos-search-clear", function (e) {
        e.preventDefault();
        livePosSearchQuery = "";
        lastPosSearchPayload = "";
        $("#mk-so-pos-search").val("").trigger("input").focus();
      })
      .off("click.mkSoPosFilter", "#mk-so-pos-filter-btn")
      .on("click.mkSoPosFilter", "#mk-so-pos-filter-btn", function (e) {
        e.preventDefault();
        togglePosAdvancedFilter();
      })
      .off("click.mkSoPosColumns", ".mk-so-pos-trigger-columns")
      .on("click.mkSoPosColumns", ".mk-so-pos-trigger-columns", function (e) {
        e.preventDefault();
        $root.find(".listColumnFilter").first().trigger("click");
      })
      .off("click.mkSoPosMerge", "#mk-so-merge-orders-btn")
      .on("click.mkSoPosMerge", "#mk-so-merge-orders-btn", function (e) {
        e.preventDefault();
        if (
          typeof app !== "undefined" &&
          app.helper &&
          app.helper.showErrorNotification
        ) {
          app.helper.showErrorNotification({
            message: "Chọn ít nhất 2 đơn hàng để gộp đơn.",
          });
        }
      });
  }

  function injectSummaryRow($table) {
    var dueField = dueFieldName();
    var paidField = paidFieldName();
    var $headerRow = $table.find("thead tr.listViewContentHeader").first();
    if (!$headerRow.length) {
      return;
    }
    $table.find("tr.mk-so-summary-row").remove();
    var $rows = $table.find("tbody tr.listViewEntries");
    if (!$rows.length) {
      return;
    }
    var sumField = null;
    if ($headerRow.find('a[data-columnname="' + dueField + '"]').length > 0) {
      sumField = dueField;
    } else if ($headerRow.find('a[data-columnname="total"]').length > 0) {
      sumField = "total";
    }
    var hasPaidColumn =
      $headerRow.find('a[data-columnname="' + paidField + '"]').length > 0;
    var dueTotal = 0;
    if (sumField) {
      $rows.each(function () {
        dueTotal += cellMoneyAmount(
          $(this).find('td[data-name="' + sumField + '"]').first(),
        );
      });
    }
    var $cells = $headerRow.children("th");
    var html = '<tr class="mk-so-summary-row">';
    $cells.each(function () {
      var $th = $(this);
      var field = $th.find("a[data-columnname]").attr("data-columnname") || "";
      var cls = "mk-so-summary-cell";
      var content = "";
      if ($th.hasClass("mk-so-pos-control-th")) {
        cls += " mk-so-summary-cell--label";
        content = '<span class="mk-so-summary-label">Tổng</span>';
      } else if (sumField && field === sumField) {
        cls += " mk-so-summary-cell--due";
        content =
          '<span class="mk-so-summary-value">' +
          formatMoneyNumber(dueTotal) +
          "</span>";
      } else if (field === paidField && hasPaidColumn) {
        // Keep paid column blank on the summary row.
        cls += " mk-so-summary-cell--paid";
        content = "";
      }
      html += '<td class="' + cls + '">' + content + "</td>";
    });
    html += "</tr>";
    var $tbody = $table.find("tbody").first();
    if ($tbody.length) {
      $tbody.append(html);
    } else {
      $headerRow.after(html);
    }
  }

  function applyPosListChrome() {
    var $root = getListViewContainer();
    $root.addClass("mk-so-pos-list-enabled");
    bindPosToolbarEvents();
    if (livePosSearchQuery) {
      $("#mk-so-pos-search").val(livePosSearchQuery);
      $("#mk-so-pos-search-clear").prop("hidden", !livePosSearchQuery);
    }
  }

  function enhanceStatusPills($table, statusField) {
    $table.find('tbody td[data-name="' + statusField + '"]').each(function () {
      var $td = $(this);
      if ($td.hasClass("mk-so-list-col-hidden")) {
        return;
      }
      $td.addClass("mk-so-status-cell");
      var $value = $td.find(".value").first();
      if (!$value.length) {
        return;
      }
      if (
        $value.find(".mk-so-status-pill").length &&
        $value.data("mkStatusDone")
      ) {
        return;
      }
      var text = $.trim($value.text()) || "--";
      var display = translateStatusLabel(text);
      var tone = normalizeStatusTone(display);
      $value.empty().append(
        $("<span>", {
          class: "mk-so-status-pill mk-so-status-pill--" + tone,
          text: display,
        }),
      );
      $value.data("mkStatusDone", 1);
    });
  }

  function applyPosColumnWidths($table) {
    var $headers = $table.find("thead tr.listViewContentHeader th");
    if (!$headers.length) {
      return;
    }
    $table.find("colgroup.mk-so-pos-cols").remove();
    var widthByClass = {
      "mk-so-pos-control-th": "76px",
      "mk-so-col-star": "76px",
      "mk-so-col-order-no": "12%",
      "mk-so-col-time": "12%",
      "mk-so-col-customer": "15%",
      "mk-so-col-warehouse": "12%",
      "mk-so-col-paid": "12%",
      "mk-so-col-status": "14%",
      "mk-so-col-due": "13%",
    };
    var html = '<colgroup class="mk-so-pos-cols">';
    $headers.each(function () {
      var $th = $(this);
      var width = "auto";
      var matchedCls = "";
      var cls;
      for (cls in widthByClass) {
        if (widthByClass.hasOwnProperty(cls) && $th.hasClass(cls)) {
          width = widthByClass[cls];
          matchedCls = cls;
          break;
        }
      }
      html +=
        '<col class="' +
        (matchedCls ? matchedCls + " " : "") +
        'mk-so-pos-col" style="width:' +
        width +
        '">';
    });
    html += "</colgroup>";
    $table.prepend(html);
  }

  function applyTableClasses($table) {
    $table.addClass("mk-so-table mk-so-table-layout");
  }

  function syncHiddenFieldsFromFragment($incoming, $scope) {
    var names = [
      "pageNumber",
      "pageLimit",
      "orderBy",
      "sortOrder",
      "list_headers",
      "totalCount",
      "noOfEntries",
      "pageStartRange",
      "pageEndRange",
      "previousPageExist",
      "nextPageExist",
      "viewname",
      "cvid",
      "currentSearchParams",
      "currentTagParams",
      "noFilterCache",
    ];
    var i;
    for (i = 0; i < names.length; i++) {
      var $src = $incoming.find('[name="' + names[i] + '"]').first();
      var $dst = $scope.find('[name="' + names[i] + '"]').first();
      if ($src.length && $dst.length) {
        $dst.val($src.val());
      }
    }
  }

  function getIncomingRoot($incoming) {
    var $page = $incoming.find(".mk-so-page.mk-so-list-sales-root").first();
    if ($page.length) {
      return $page;
    }
    return $incoming.find(".col-sm-12").first().length
      ? $incoming.find(".col-sm-12").first()
      : $incoming;
  }

  /**
   * AJAX: chỉ thay table + toolbar trong shell hiện có — không thay header / không nhồi thêm mk-so-page.
   */
  function swapListBodyInShell(contents) {
    var $lv = getListViewContainer();
    var $page = $lv.find(".mk-so-page.mk-so-list-sales-root").first();
    if (!$page.length) {
      return false;
    }

    var $incoming = $("<div>").html(contents);
    var $source = getIncomingRoot($incoming);
    if (!$source.length) {
      return false;
    }

    syncHiddenFieldsFromFragment($source, $lv);

    var $card = $page.find(".mk-so-table-card").first();
    var $newTableContent = $source.find("#table-content").first();
    if (!$newTableContent.length || !$card.length) {
      return false;
    }
    $card
      .find("#table-content")
      .replaceWith($newTableContent.clone(true, true));

    var $newActions = $source.find("#listview-actions").first();
    if (!$newActions.length) {
      $newActions = $source
        .find(".mk-so-pos-actions-src #listview-actions")
        .first();
    }
    var $oldActions = $page.find("#listview-actions").first();
    if (!$oldActions.length) {
      $oldActions = $page
        .find(".mk-so-pos-actions-src #listview-actions")
        .first();
    }
    if ($newActions.length && $oldActions.length) {
      $oldActions.replaceWith($newActions.clone(true, true));
    }

    return true;
  }

  function destroyFloatTheadArtifacts() {
    var $lv = getListViewContainer();
    if (!$lv.length) {
      return;
    }

    $lv.find(".floatThead-container").remove();

    if ($.fn.floatThead) {
      $lv.find("#listview-table").each(function () {
        try {
          $(this).floatThead("destroy");
        } catch (e) {
          /* ignore */
        }
      });
    }

    $lv.find("#table-content.table-container").css({
      position: "",
      height: "auto",
      maxHeight: "",
      width: "100%",
      overflowX: "auto",
      overflowY: "visible",
    });

    if ($.fn.perfectScrollbar) {
      try {
        $lv.find("#table-content").perfectScrollbar("destroy");
      } catch (e2) {
        /* ignore */
      }
    }
  }

  function dedupeListDom() {
    var $lv = getListViewContainer();
    if (!$lv.length) {
      return;
    }

    $lv.find(".mk-so-page.mk-so-list-sales-root").slice(1).remove();
    $lv.find(".mk-so-header").slice(1).remove();

    var $tables = $lv.find("#listview-table");
    if ($tables.length > 1) {
      var $keep = getPrimaryTable();
      $tables.each(function () {
        if (this !== $keep[0]) {
          $(this).closest("#table-content").remove();
        }
      });
    }

    $lv
      .find(".essentials-toggle, .module-action-bar")
      .addClass("mk-so-hide-legacy");
    destroyFloatTheadArtifacts();
  }

  /**
   * Vtiger gọi floatThead sau ~10ms khi load — gây header clone / layout như 2 list.
   * SALES: bỏ floatThead, chỉ enhance DOM của mình.
   */
  function patchVtigerFloatingThead() {
    if (typeof Vtiger_List_Js === "undefined") {
      return;
    }

    if (Vtiger_List_Js.prototype.__mkSoFloatTheadPatched) {
      return;
    }
    Vtiger_List_Js.prototype.__mkSoFloatTheadPatched = true;

    var originalFloat = Vtiger_List_Js.prototype.registerFloatingThead;
    var originalReflow = Vtiger_List_Js.prototype.reflowList;

    Vtiger_List_Js.prototype.registerFloatingThead = function () {
      if (isSalesOrderSalesList()) {
        destroyFloatTheadArtifacts();
        applyListEnhancements();
        return;
      }
      originalFloat.call(this);
    };

    Vtiger_List_Js.prototype.reflowList = function () {
      if (isSalesOrderSalesList()) {
        destroyFloatTheadArtifacts();
        applyListEnhancements();
        return;
      }
      originalReflow.call(this);
    };
  }

  function scheduleInitialEnhancements() {
    var delays = [0, 50, 150, 400];
    var i;
    for (i = 0; i < delays.length; i++) {
      setTimeout(applyListEnhancements, delays[i]);
    }
    $(window).off("load.mkSoList").on("load.mkSoList", applyListEnhancements);
  }

  function collectHeaderFields($table) {
    var fields = [];
    if (!$table || !$table.length) {
      return fields;
    }
    $table
      .find("thead tr.listViewContentHeader th a[data-columnname]")
      .each(function () {
        var name = $(this).attr("data-columnname");
        if (name && fields.indexOf(name) < 0) {
          fields.push(name);
        }
      });
    if (!fields.length) {
      $table.find("thead th a[data-columnname]").each(function () {
        var name = $(this).attr("data-columnname");
        if (name && fields.indexOf(name) < 0) {
          fields.push(name);
        }
      });
    }
    if (!fields.length) {
      $table.find("thead th[data-columnname]").each(function () {
        var name = $(this).attr("data-columnname");
        if (name && fields.indexOf(name) < 0) {
          fields.push(name);
        }
      });
    }
    return fields;
  }

  function collectVisibleHeaderTexts($table) {
    var texts = [];
    if (!$table || !$table.length) {
      return texts;
    }
    $table.find("thead tr.listViewContentHeader th").each(function () {
      var $th = $(this);
      if ($th.hasClass("mk-so-list-col-hidden")) {
        return;
      }
      var label = $.trim(
        $th.find("a.listViewContentHeaderValues, a.noSorting").first().text(),
      );
      if (!label) {
        label = $.trim($th.text());
      }
      label = label.replace(/\s+/g, " ");
      if (label && texts.indexOf(label) < 0) {
        texts.push(label);
      }
    });
    return texts;
  }

  function applyListEnhancements() {
    if (!isSalesOrderSalesList()) {
      return;
    }

    if (
      window.MkSalesListShared &&
      typeof window.MkSalesListShared.relocatePaginationFooter === "function"
    ) {
      window.MkSalesListShared.relocatePaginationFooter();
    }
    if (typeof window.mkSalesListAfterAjax === "function") {
      window.mkSalesListAfterAjax();
    }

    dedupeListDom();

    var $table = getPrimaryTable();
    if (!$table.length) {
      document.documentElement.classList.add("mk-sales-list-ready");
      if (
        window.MkSalesListShared &&
        typeof window.MkSalesListShared.revealSalesListUi === "function"
      ) {
        window.MkSalesListShared.revealSalesListUi();
      }
      return;
    }

    collapsePosInlineDetail($table);
    var listInstance =
      Vtiger_List_Js.getInstance && Vtiger_List_Js.getInstance();
    if (listInstance) {
      registerSalesPosRowClickEvent(listInstance);
    }

    applyTableClasses($table);
    applyPosColumnWidths($table);
    fixEncodedTextCells($table);
    applyPosListChrome();
    initPosFilterPanel();
    syncPosFilterUi();
    maybeOpenFilterFromState();
    assignPosColumnClasses($table);
    hideLegacyPosColumns($table);

    var statusField = resolveStatusField($table);
    if (statusField) {
      enhanceStatusPills($table, statusField);
      var headerFields = collectHeaderFields($table);
      var hasQuoteInHeaders = headerFields.some(function (name) {
        return isQuoteColumnFieldName(name);
      });
      if (hasQuoteInHeaders) {
        hideQuoteColumns($table);
      }
    }
    formatPosMoneyCells($table);
    formatPosDateTimeCells($table);
    enhancePaidCells($table);
    injectSummaryRow($table);
    bindPosSelectionEvents();
    bindPosMassDuplicateButton();
    bindPosMassDeleteButton();
    bindInlineDuplicateButton();
    syncPosRowSelectedClass();
    syncPosMassActionButtons();
    if (
      window.MkSalesListShared &&
      typeof window.MkSalesListShared.relocatePaginationFooter === "function"
    ) {
      window.MkSalesListShared.relocatePaginationFooter();
    }
    document.documentElement.classList.add("mk-sales-list-ready");
    if (
      window.MkSalesListShared &&
      typeof window.MkSalesListShared.revealSalesListUi === "function"
    ) {
      window.MkSalesListShared.revealSalesListUi();
    }
  }

  function patchPlaceListContents() {
    if (placeListContentsPatched || typeof Vtiger_List_Js === "undefined") {
      return;
    }
    placeListContentsPatched = true;
    var originalPlace = Vtiger_List_Js.prototype.placeListContents;
    Vtiger_List_Js.prototype.placeListContents = function (contents) {
      if (isSalesOrderSalesList() && swapListBodyInShell(contents)) {
        syncPosFilterUi();
        applyListEnhancements();
        return;
      }
      originalPlace.call(this, contents);
      if (isSalesOrderSalesList()) {
        applyListEnhancements();
      }
    };
  }

  function initDebugHelpers() {
    window.__debugSOList = function () {
      var $table = getPrimaryTable();
      var headerFields = collectHeaderFields($table);
      var bodyFields = [];
      if ($table.length) {
        $table
          .find("tbody tr.listViewEntries")
          .first()
          .find("td[data-name]")
          .each(function () {
            var name = $(this).attr("data-name");
            if (name && bodyFields.indexOf(name) < 0) {
              bodyFields.push(name);
            }
          });
      }
      var listHeadersRaw = document.querySelector(
        '#listViewContent input[name="list_headers"]',
      );
      var listHeadersParsed = null;
      if (listHeadersRaw && listHeadersRaw.value) {
        try {
          listHeadersParsed = JSON.parse(listHeadersRaw.value);
        } catch (e) {
          listHeadersParsed = listHeadersRaw.value;
        }
      }
      var candidates = statusCandidates();
      var statusCandidatesPresent = candidates.filter(function (name) {
        return headerFields.indexOf(name) >= 0 || bodyFields.indexOf(name) >= 0;
      });
      var quoteCandidatesPresent = headerFields
        .concat(bodyFields)
        .filter(function (name, idx, arr) {
          return isQuoteColumnFieldName(name) && arr.indexOf(name) === idx;
        });
      return {
        tableExists: $table.length > 0,
        headerFields: headerFields,
        bodyFields: bodyFields,
        listHeadersParsed: listHeadersParsed,
        statusCandidatesPresent: statusCandidatesPresent,
        quoteCandidatesPresent: quoteCandidatesPresent,
        visibleHeaderTexts: collectVisibleHeaderTexts($table),
        hasShell: !!document.querySelector("#listViewContent .mk-so-page"),
      };
    };
  }

  function bindListEvents() {
    if (typeof app === "undefined" || !app.event || !app.event.on) {
      return;
    }
    app.event.on("post.listViewFilter.click", applyListEnhancements);
    app.event.on("post.listViewSort.click", applyListEnhancements);
  }

  function whenVtigerListReady(callback) {
    var attempts = 0;
    function tick() {
      if (typeof Vtiger_List_Js !== "undefined") {
        callback();
        return;
      }
      attempts += 1;
      if (attempts < 120) {
        setTimeout(tick, 25);
      }
    }
    tick();
  }

  function init() {
    if (!isSalesOrderSalesList()) {
      return;
    }

    var root = getListViewContainer();
    if (!root.length) {
      return;
    }

    $(document)
      .off("click.mkSoList", ".mk-so-trigger-columns")
      .on("click.mkSoList", ".mk-so-trigger-columns", function (e) {
        e.preventDefault();
        root.find(".listColumnFilter").first().trigger("click");
      });

    /* Filter icon: MkSalesListShared scrolls to filter row (no toggle hide) */

    patchPlaceListContents();
    patchInlineDetailRowClick();
    bindListEvents();
    bindPosSelectionEvents();
    bindPosMassDuplicateButton();
    bindPosMassDeleteButton();
    bindInlineDuplicateButton();
    initDebugHelpers();
    scheduleInitialEnhancements();
  }

  window.applySalesOrderListUi = applyListEnhancements;
  window.mkSoMassDeleteSalesOrders = massDeleteSalesOrders;
  window.mkSoMassDuplicateSalesOrders = massDuplicateSalesOrders;

  bindPosInlineDetailCapture();
  if (typeof Vtiger_List_Js !== "undefined") {
    patchInlineDetailRowClick();
  }

  function boot() {
    if (!isSalesOrderSalesList()) {
      return;
    }
    whenVtigerListReady(function () {
      patchVtigerFloatingThead();
      patchInlineDetailRowClick();
      patchPosListViewActions();
      patchSalesOrderDeleteRecord();
      init();
      var listInstance =
        Vtiger_List_Js.getInstance && Vtiger_List_Js.getInstance();
      if (listInstance && isSalesOrderSalesList()) {
        registerSalesPosRowClickEvent(listInstance);
      }
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot);
  } else {
    boot();
  }
})(jQuery);
