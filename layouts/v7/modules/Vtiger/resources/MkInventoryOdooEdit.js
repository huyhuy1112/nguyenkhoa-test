/**
 * Quote / Sales Order — Odoo-style address, line items, payment terms (SALES app).
 */
(function ($) {
  "use strict";

  if (typeof document !== "undefined" && document.documentElement) {
    document.documentElement.classList.add("mk-inv-odoo-active");
  }

  var REF_SEL = "Vtiger.Reference.Selection";
  var POST_REF = "Vtiger.PostReference.Selection";

  var PAYMENT_METHOD_OPTIONS = [
    { value: "Tiền mặt", label: "Tiền mặt" },
    { value: "Chuyển khoản", label: "Chuyển khoản" },
    { value: "Thẻ", label: "Thẻ" },
    { value: "Ví", label: "Ví" },
  ];
  var DEFAULT_PAYMENT_METHOD = "Chuyển khoản";

  var DEFAULT_INVOICE_TIER = "lt_1m";

  var INVOICE_TIER_OPTIONS = [
    { value: "auto", label: "Tự động theo tổng đơn" },
    { value: "lt_1m", label: "Giá < 1 triệu" },
    { value: "gte_1m", label: "Giá ≥ 1 triệu" },
    { value: "gte_3m", label: "Giá ≥ 3 triệu" },
    { value: "gte_5m", label: "Giá ≥ 5 triệu" },
    { value: "gte_7m", label: "Giá ≥ 7 triệu" },
  ];
  var INVOICE_TIER_FIELDS = {
    lt_1m: "price_lt_1m",
    gte_1m: "price_gte_1m",
    gte_3m: "price_gte_3m",
    gte_5m: "price_gte_5m",
    gte_7m: "price_gte_7m",
  };

  function getPriceChannel() {
    var ch = String(window.MK_PRICE_CHANNEL || "retail").toLowerCase().trim();
    if (ch === "tuibao" || ch === "franchise" || ch === "chain") {
      return "tuibao";
    }
    // Prefill from ServiceContracts implies franchise channel
    if (window.MK_SC_PREFILL && (window.MK_SC_PREFILL.id || window.MK_SC_PREFILL.account_id)) {
      return "tuibao";
    }
    return "retail";
  }

  function setPriceChannel(channel, opts) {
    opts = opts || {};
    var next = channel === "tuibao" || channel === "franchise" || channel === "chain"
      ? "tuibao"
      : "retail";
    window.MK_PRICE_CHANNEL = next;
    if (next === "tuibao" && opts.scPrefill) {
      window.MK_SC_PREFILL = opts.scPrefill;
    }
    if (next === "retail" && opts.clearScPrefill !== false) {
      // Leaving franchise source → retail prices (Opp / Leads / Contacts).
      try {
        window.MK_SC_PREFILL = null;
      } catch (e) {}
    }
  }

  var CREDIT_TERMS_OPTIONS = [
    { value: "Thanh toán ngay", label: "Thanh toán ngay" },
    { value: "15 ngày", label: "15 ngày" },
    { value: "21 ngày", label: "21 ngày" },
    { value: "30 ngày", label: "30 ngày" },
    { value: "45 ngày", label: "45 ngày" },
    { value: "Cuối tháng kế tiếp", label: "Cuối tháng kế tiếp" },
    {
      value: "10 ngày sau ngày cuối tháng kế tiếp",
      label: "10 ngày sau ngày cuối tháng kế tiếp",
    },
    {
      value: "30% trả ngay, còn lại trả trong 60 ngày",
      label: "30% trả ngay, còn lại trả trong 60 ngày",
    },
  ];
  var DEFAULT_CREDIT_TERM = "Thanh toán ngay";

  // BA: Đơn giá → Chiết khấu % → Thành tiền → Sau CK → Ghi chú (VAT-included prices)
  var MODERN_LINE_HEADER_COLUMNS = [
    { className: "mk-inv-col-drag", label: "" },
    { className: "mk-inv-col-product", label: "Tên mục", required: true },
    { className: "mk-inv-col-qty", label: "Số lượng" },
    { className: "mk-inv-col-unit-head mk-inv-col-unit", label: "Đơn vị tính" },
    { className: "mk-inv-col-price", label: "Đơn giá" },
    { className: "mk-inv-col-tax-head mk-inv-col-tax mk-inv-col-discount", label: "Chiết khấu (%)" },
    { className: "mk-inv-col-amount", label: "Thành tiền" },
    { className: "mk-inv-col-afterck", label: "Sau CK" },
    { className: "mk-inv-col-note", label: "Ghi chú" },
  ];

  var MODERN_LINE_COLGROUP_WIDTHS = [
    "48px",
    "18%",
    "68px",
    "88px",
    "120px",
    "100px",
    "120px",
    "120px",
    "140px",
  ];

  /** BA mode: unit prices include VAT; line tax column is discount % instead. */
  var MK_BA_VAT_INCLUDED = true;

  var UNIT_OPTIONS = [
    { value: "Cái", label: "Cái" },
    { value: "Hộp", label: "Hộp" },
    { value: "Tá", label: "Tá" },
    { value: "Thùng", label: "Thùng" },
    { value: "Kg", label: "Kg" },
    { value: "Mét", label: "Mét" },
  ];

  var ADDRESS_DETAIL_FIELDS = [
    "bill_pobox",
    "bill_city",
    "bill_state",
    "bill_code",
    "bill_country",
    "ship_pobox",
    "ship_city",
    "ship_state",
    "ship_code",
    "ship_country",
  ];

  function getLineItemsTableBody($table) {
    if (!$table || !$table.length) {
      return $();
    }
    var $tbody = $table.children("tbody");
    return $tbody.length ? $tbody.first() : $table;
  }

  function getLineItemHeaderRow($table) {
    if (!$table || !$table.length) {
      return $();
    }
    var $body = getLineItemsTableBody($table);
    var $existing = $body.children("tr.mk-inv-header-row").first();
    if ($existing.length) {
      return $existing;
    }
    // Prefer a non-product first row (legacy label row). Never convert #row0 or lineItemRow.
    var $first = $body.children("tr").first();
    if (
      $first.length &&
      !$first.hasClass("lineItemRow") &&
      !$first.hasClass("lineItemCloneCopy") &&
      $first.attr("id") !== "row0"
    ) {
      return $first;
    }
    var $header = $(
      '<tr class="mk-inv-header-row end-section" data-mk-header="1"></tr>',
    );
    if ($first.length) {
      $header.insertBefore($first);
    } else {
      $body.prepend($header);
    }
    return $header;
  }

  function getLineItemTemplateRow($table) {
    return $table.find("#row0.lineItemCloneCopy, tr.lineItemCloneCopy").first();
  }

  function getLineItemSampleRow($table) {
    var $row = $table
      .find("tr.lineItemRow")
      .not(".hide, .lineItemCloneCopy")
      .first();
    if (!$row.length) {
      $row = getLineItemTemplateRow($table);
    }
    return $row;
  }

  function decodeText(value) {
    if (value === null || value === undefined) {
      return "";
    }
    var text = String(value);
    if (typeof app !== "undefined" && app.htmlDecode) {
      text = app.htmlDecode(text);
    }
    if (/&(?:#x?[0-9a-f]+|[a-z]+);/i.test(text)) {
      var el = document.createElement("textarea");
      el.innerHTML = text;
      text = el.value;
    }
    return text;
  }

  function getRecordDetails(recordId, sourceModule) {
    var deferred = $.Deferred();
    recordId = parseInt(recordId, 10) || 0;
    if (!recordId || typeof app === "undefined" || !app.request) {
      deferred.reject();
      return deferred.promise();
    }
    var moduleName = app.getModuleName ? app.getModuleName() : "Vtiger";
    var url =
      "index.php?module=" +
      moduleName +
      "&action=GetData&record=" +
      recordId +
      "&source_module=" +
      sourceModule;
    app.request.get({ url: url }).then(function (error, data) {
      if (error === null && data) {
        deferred.resolve(data);
      } else {
        deferred.reject(error || data);
      }
    });
    return deferred.promise();
  }

  function formatAccountAddress(row, kind) {
    var prefix = kind === "ship" ? "ship" : "bill";
    var parts = [];
    var street = decodeText(row[prefix + "_street"]);
    if (street) {
      parts.push(street);
    }
    var pobox = decodeText(row[prefix + "_pobox"]);
    if (pobox) {
      parts.push(pobox);
    }
    var cityLine = [
      decodeText(row[prefix + "_city"]),
      decodeText(row[prefix + "_state"]),
      decodeText(row[prefix + "_code"]),
    ]
      .filter(Boolean)
      .join(", ");
    if (cityLine) {
      parts.push(cityLine);
    }
    var country = decodeText(row[prefix + "_country"]);
    if (country) {
      parts.push(country);
    }
    return parts.join("\n");
  }

  function formatContactAddress(row, kind) {
    var map =
      kind === "ship"
        ? {
            street: "otherstreet",
            pobox: "otherpobox",
            city: "othercity",
            state: "otherstate",
            code: "otherzip",
            country: "othercountry",
          }
        : {
            street: "mailingstreet",
            pobox: "mailingpobox",
            city: "mailingcity",
            state: "mailingstate",
            code: "mailingzip",
            country: "mailingcountry",
          };
    var parts = [];
    var street = decodeText(row[map.street]);
    if (street) {
      parts.push(street);
    }
    var pobox = decodeText(row[map.pobox]);
    if (pobox) {
      parts.push(pobox);
    }
    var cityLine = [
      decodeText(row[map.city]),
      decodeText(row[map.state]),
      decodeText(row[map.code]),
    ]
      .filter(Boolean)
      .join(", ");
    if (cityLine) {
      parts.push(cityLine);
    }
    var country = decodeText(row[map.country]);
    if (country) {
      parts.push(country);
    }
    return parts.join("\n");
  }

  function syncHiddenAddressFields($form, row, module, kind) {
    var maps = {
      Accounts: {
        bill: {
          bill_street: "bill_street",
          bill_pobox: "bill_pobox",
          bill_city: "bill_city",
          bill_state: "bill_state",
          bill_code: "bill_code",
          bill_country: "bill_country",
        },
        ship: {
          ship_street: "ship_street",
          ship_pobox: "ship_pobox",
          ship_city: "ship_city",
          ship_state: "ship_state",
          ship_code: "ship_code",
          ship_country: "ship_country",
        },
      },
      Contacts: {
        bill: {
          bill_street: "mailingstreet",
          bill_pobox: "mailingpobox",
          bill_city: "mailingcity",
          bill_state: "mailingstate",
          bill_code: "mailingzip",
          bill_country: "mailingcountry",
        },
        ship: {
          ship_street: "otherstreet",
          ship_pobox: "otherpobox",
          ship_city: "othercity",
          ship_state: "otherstate",
          ship_code: "otherzip",
          ship_country: "othercountry",
        },
      },
    };
    var fieldMap = maps[module] && maps[module][kind];
    if (!fieldMap) {
      return;
    }
    Object.keys(fieldMap).forEach(function (target) {
      var source = fieldMap[target];
      var val = decodeText(row[source]);
      var $el = $form.find('[name="' + target + '"]');
      if ($el.length) {
        $el.val(val);
      }
    });
  }

  function setFormAddresses(
    $form,
    billText,
    shipText,
    sourceRow,
    sourceModule,
    options,
  ) {
    options = options || {};
    var force = !!options.force;
    var $bill = $form.find('[name="bill_street"]');
    var $ship = $form.find('[name="ship_street"]');
    billText = billText || "";
    shipText = shipText || "";

    if ($bill.length) {
      if (billText || force) {
        $bill.val(billText).trigger("change");
      }
    }
    if ($ship.length) {
      var currentShip = $.trim($ship.val() || "");
      if (shipText) {
        $ship.val(shipText).trigger("change");
      } else if (force || !currentShip) {
        var shipValue = billText || "";
        if (shipValue || force) {
          $ship.val(shipValue).trigger("change");
        }
      }
    }
    if (sourceRow && sourceModule && (billText || shipText)) {
      syncHiddenAddressFields($form, sourceRow, sourceModule, "bill");
      syncHiddenAddressFields($form, sourceRow, sourceModule, "ship");
    }
  }

  function fillAddressFromAccount($form, accountId, options) {
    accountId = parseInt(accountId, 10) || 0;
    options = options || {};
    if (!accountId) {
      return $.Deferred().reject().promise();
    }
    return getRecordDetails(accountId, "Accounts").then(function (data) {
      var row = data && data.data;
      if (!row) {
        if (options.force) {
          setFormAddresses($form, "", "", null, null, { force: true });
        }
        return false;
      }
      var billText = formatAccountAddress(row, "bill");
      var shipText = formatAccountAddress(row, "ship");
      if (!billText && !shipText) {
        if (options.force) {
          setFormAddresses($form, "", "", null, null, { force: true });
        }
        return false;
      }
      setFormAddresses($form, billText, shipText, row, "Accounts", options);
      return true;
    });
  }

  function fillAddressFromContact($form, contactId, options) {
    contactId = parseInt(contactId, 10) || 0;
    options = options || {};
    if (!contactId) {
      return $.Deferred().reject().promise();
    }
    return getRecordDetails(contactId, "Contacts").then(function (data) {
      var row = data && data.data;
      if (!row) {
        if (options.force) {
          setFormAddresses($form, "", "", null, null, { force: true });
        }
        return false;
      }
      var billText = formatContactAddress(row, "bill");
      var shipText = formatContactAddress(row, "ship");
      if (!billText && !shipText) {
        if (options.force) {
          setFormAddresses($form, "", "", null, null, { force: true });
        }
        return false;
      }
      setFormAddresses($form, billText, shipText, row, "Contacts", options);
      return true;
    });
  }

  function fillAddressFromPotential($form, options) {
    options = options || {};
    var potId = parseInt($form.find('[name="potential_id"]').val(), 10) || 0;
    if (!potId) {
      if (options.force) {
        setFormAddresses($form, "", "", null, null, { force: true });
      }
      return;
    }
    getRecordDetails(potId, "Potentials").then(function (data) {
      var row = data && data.data;
      if (!row) {
        if (options.force) {
          setFormAddresses($form, "", "", null, null, { force: true });
        }
        return;
      }
      var accountId = parseInt(row.related_to, 10) || 0;
      var contactId = parseInt(row.contact_id, 10) || 0;
      var fillOpts = { force: !!options.force };
      if (accountId) {
        fillAddressFromAccount($form, accountId, fillOpts).then(function (ok) {
          if (!ok && contactId) {
            fillAddressFromContact($form, contactId, fillOpts);
          }
        });
      } else if (contactId) {
        fillAddressFromContact($form, contactId, fillOpts);
      } else if (fillOpts.force) {
        setFormAddresses($form, "", "", null, null, { force: true });
      }
    });
  }

  function syncShipSameAsBill($form) {
    var $cb = $form.find("#mkInvShipSameAsBill");
    if (!$cb.length || !$cb.is(":checked")) {
      return;
    }
    var bill = $form.find('[name="bill_street"]').val() || "";
    $form.find('[name="ship_street"]').val(bill).prop("readonly", true);
  }

  function hideAddressDetailRows($form) {
    ADDRESS_DETAIL_FIELDS.forEach(function (name) {
      $form
        .find('[name="' + name + '"]')
        .closest("tr")
        .addClass("mk-inv-hide-legacy");
    });
    $form
      .find(".addressBlock > tbody > tr:first-child")
      .addClass("mk-inv-hide-legacy");
  }

  function localizeAddressBlock($form) {
    var $block = $form.find(".mk-inv-address-odoo");
    $block.find(".fieldBlockHeader").text("Địa chỉ");
    var $billLabel = $form
      .find('[name="bill_street"]')
      .closest("tr")
      .find("td.fieldLabel label")
      .first();
    var $shipLabel = $form
      .find('[name="ship_street"]')
      .closest("tr")
      .find("td.fieldLabel label")
      .first();
    if ($billLabel.length) {
      $billLabel.html('<span class="redColor">*</span> Địa chỉ');
    }
    if ($shipLabel.length) {
      $shipLabel.html('<span class="redColor">*</span> Địa chỉ vận chuyển');
    }
  }

  function injectShipSameCheckbox($form) {
    // Quotes uses the rail address editor without "same as billing" checkbox.
    var moduleName =
      ($form.find('[name="module"]').val() || $("body").attr("data-module") || "").toString();
    if (moduleName === "Quotes") {
      $form.find("#mkInvShipSameAsBill").closest("label.mk-inv-ship-same").remove();
      return;
    }
    if ($form.find("#mkInvShipSameAsBill").length) {
      return;
    }
    var $shipLabel = $form
      .find('[name="ship_street"]')
      .closest("tr")
      .find("td.fieldLabel")
      .first();
    if (!$shipLabel.length) {
      return;
    }
    $shipLabel.append(
      '<label class="mk-inv-ship-same">' +
        '<input type="checkbox" id="mkInvShipSameAsBill" /> Giống địa chỉ lập hóa đơn' +
        "</label>",
    );
    $form.find("#mkInvShipSameAsBill").on("change", function () {
      var $ship = $form.find('[name="ship_street"]');
      if (this.checked) {
        $ship
          .val($form.find('[name="bill_street"]').val() || "")
          .prop("readonly", true);
      } else {
        $ship.prop("readonly", false);
      }
    });
    $form.find('[name="bill_street"]').on("input.mkInvShipSame", function () {
      syncShipSameAsBill($form);
    });
  }

  function registerAddressAutofill($form) {
    if ($form.data("mkInvAddrAutofill")) {
      return;
    }
    $form.data("mkInvAddrAutofill", true);

    var onOpp = function () {
      setTimeout(function () {
        fillAddressFromPotential($form, { force: true });
      }, 120);
    };
    var onAccount = function () {
      var accountId =
        parseInt($form.find('[name="account_id"]').val(), 10) || 0;
      if (accountId) {
        fillAddressFromAccount($form, accountId, { force: false });
      }
    };
    var onContact = function () {
      var contactId =
        parseInt($form.find('[name="contact_id"]').val(), 10) || 0;
      if (contactId) {
        fillAddressFromContact($form, contactId, { force: false });
      }
    };

    $form.on(REF_SEL, '[name="potential_id"]', onOpp);
    $form.on("change.mkInvAddr", '[name="potential_id"]', onOpp);
    $form.on(POST_REF, '[name="account_id"]', onAccount);
    $form.on(REF_SEL, '[name="account_id"]', onAccount);
    $form.on(POST_REF, '[name="contact_id"]', onContact);
    $form.on(REF_SEL, '[name="contact_id"]', onContact);

    var initialPot =
      parseInt($form.find('[name="potential_id"]').val(), 10) || 0;
    if (initialPot) {
      setTimeout(function () {
        // On edit load: fill only when address fields are still empty.
        var bill = $.trim($form.find('[name="bill_street"]').val() || "");
        fillAddressFromPotential($form, { force: !bill });
      }, 400);
    }
  }

  function restructureAddressHorizontal($form) {
    var $block = $form.find(".mk-inv-address-odoo");
    if (!$block.length || $block.data("mkInvAddrHoriz")) {
      return;
    }

    var $billTa = $form.find('textarea[name="bill_street"]');
    var $shipTa = $form.find('textarea[name="ship_street"]');
    if (!$billTa.length || !$shipTa.length) {
      return;
    }
    $block.data("mkInvAddrHoriz", true);

    var $table = $block.find("table.addressBlock");
    if (!$table.length) {
      $table = $block.find("table").first();
    }
    if (!$table.length) {
      return;
    }

    $table.addClass("mk-inv-hide-legacy");
    $block.find("> hr").addClass("mk-inv-hide-legacy");

    var $wrap = $('<div class="mk-inv-addr-horiz"></div>');

    var $colLeft = $('<div class="mk-inv-addr-col"></div>');
    $colLeft.append(
      '<div class="mk-inv-addr-label-wrap"><span class="redColor">*</span> Địa chỉ</div>',
    );
    $colLeft.append($billTa.detach());

    var $colRight = $('<div class="mk-inv-addr-col"></div>');
    var $shipLabelHtml =
      '<div class="mk-inv-addr-label-wrap"><span class="redColor">*</span> Địa chỉ vận chuyển</div>';
    $colRight.append($shipLabelHtml);
    var $shipSame = $form.find("#mkInvShipSameAsBill").closest("label");
    if ($shipSame.length) {
      $colRight
        .find(".mk-inv-addr-label-wrap")
        .append(" ")
        .append($shipSame.detach());
    }
    $colRight.append($shipTa.detach());

    $wrap.append($colLeft).append($colRight);
    $block.find(".fieldBlockHeader").after($wrap);
  }

  function initAddressOdoo($form) {
    var $block = $form.find(
      '.fieldBlockContainer[data-block="LBL_ADDRESS_INFORMATION"]',
    );
    if (!$block.length || $block.data("mkInvAddrOdoo")) {
      return;
    }
    $block.data("mkInvAddrOdoo", true);
    $block.addClass("mk-inv-address-odoo");
    hideAddressDetailRows($form);
    localizeAddressBlock($form);
    injectShipSameCheckbox($form);
    restructureAddressHorizontal($form);
    registerAddressAutofill($form);
  }

  function parseMoney(value) {
    if (value === null || value === undefined) {
      return 0;
    }
    if (typeof value === "number") {
      return isNaN(value) ? 0 : value;
    }

    var text = String(value)
      .replace(/\u00a0/g, " ")
      .replace(/đ/gi, "")
      .replace(/₫/g, "")
      .trim()
      .replace(/\s/g, "");

    if (text === "" || text === "-") {
      return 0;
    }

    var hasComma = text.indexOf(",") >= 0;
    var hasDot = text.indexOf(".") >= 0;

    if (hasComma && hasDot) {
      var lastComma = text.lastIndexOf(",");
      var lastDot = text.lastIndexOf(".");
      if (lastComma > lastDot) {
        text = text.replace(/\./g, "").replace(",", ".");
      } else {
        text = text.replace(/,/g, "");
      }
    } else if (hasComma) {
      var commaParts = text.split(",");
      if (commaParts.length === 2 && commaParts[1].length <= 2) {
        text = commaParts[0].replace(/\./g, "") + "." + commaParts[1];
      } else {
        text = text.replace(/,/g, "");
      }
    } else if (hasDot) {
      var dotParts = text.split(".");
      // Vietnamese thousand-sep: 1.000 / 1.000.000 / 10.000.000
      // Only treat as decimal when exactly one dot and 1–2 fraction digits (e.g. 1.5, 10.25).
      if (dotParts.length === 2 && dotParts[1].length > 0 && dotParts[1].length <= 2 && !/^\d{3}$/.test(dotParts[1])) {
        // keep as decimal: 12.5
      } else {
        text = text.replace(/\./g, "");
      }
    }

    text = text.replace(/[^\d.-]/g, "");
    var n = parseFloat(text);
    return isNaN(n) ? 0 : n;
  }

  function formatVnd(value) {
    var n = Math.round(parseMoney(value));
    return "đ " + formatVndNumber(n);
  }

  /**
   * Always thousand-sep with '.' (45.000 / 100.000.000), no trailing decimals for VND.
   * Avoid locale quirks / non-breaking spaces from toLocaleString.
   */
  function formatVndNumber(value) {
    var n = Math.round(parseMoney(value));
    if (!isFinite(n)) {
      n = 0;
    }
    var neg = n < 0;
    var abs = Math.abs(n);
    var s = String(abs);
    // integer only — insert dots every 3 digits from right
    s = s.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    return neg ? "-" + s : s;
  }

  /**
   * Format unit price for display (4.000.000). Skip while the input is focused and being typed.
   */
  function formatListPriceInput($input, force) {
    if (!$input || !$input.length) {
      return;
    }
    if (!force && document.activeElement === $input[0] && $input.data("mkPriceTyping")) {
      return;
    }
    if (!force && document.activeElement === $input[0]) {
      // Still format when focused but idle (after tab-in) so dots never stay stripped.
      // Only skip when user is mid-keystroke (mkPriceTyping).
    }
    var n = Math.round(parseMoney($input.val()));
    if (!isFinite(n)) {
      n = 0;
    }
    var formatted = formatVndNumber(n);
    if (String($input.val()) !== formatted) {
      $input.val(formatted);
    }
    $input.attr("data-mk-raw-price", String(n));
  }

  function formatAllListPrices($form, force) {
    if (!$form || !$form.length) {
      return;
    }
    $form.find("tr.lineItemRow input.listPrice").each(function () {
      formatListPriceInput($(this), !!force);
    });
  }

  function setListPriceValue($input, value, opts) {
    opts = opts || {};
    if (!$input || !$input.length) {
      return;
    }
    var n = Math.round(parseMoney(value));
    if (!isFinite(n)) {
      n = 0;
    }
    $input.attr("data-mk-raw-price", String(n));
    // Always show dotted VND unless caller explicitly wants raw AND input is focused for submit.
    if (opts.raw === true && opts.forSubmit) {
      $input.val(n ? String(n) : "0");
      return;
    }
    if (document.activeElement === $input[0] && $input.data("mkPriceTyping")) {
      // keep current keystrokes during typing; raw stored in data attr
      return;
    }
    $input.val(formatVndNumber(n));
  }

  function bindListPriceFormatting($form) {
    if (!$form || !$form.length || $form.data("mkInvPriceFmtBound")) {
      return;
    }
    $form.data("mkInvPriceFmtBound", true);

    // Do NOT strip thousand separators on focus — keep 45.000 visible always.
    $form.on("focus.mkInvPriceFmt", "input.listPrice", function () {
      var $el = $(this);
      formatListPriceInput($el, true);
      try {
        this.select();
      } catch (eSel) {
        /* ignore */
      }
    });

    $form.on("keydown.mkInvPriceFmt", "input.listPrice", function () {
      $(this).data("mkPriceTyping", 1);
    });

    $form.on(
      "input.mkInvPriceFmt",
      "input.listPrice",
      function () {
        var $el = $(this);
        $el.data("mkPriceTyping", 1);
        var n = Math.round(parseMoney($el.val()));
        $el.attr("data-mk-raw-price", String(isFinite(n) ? n : 0));
        // Soft live-group digits when value looks complete (no trailing incomplete parts)
        var rawTxt = String($el.val() || "").replace(/[^\d]/g, "");
        if (rawTxt.length >= 4 && document.activeElement === $el[0]) {
          // Optional: keep raw while typing long numbers is fine; reformat on blur.
        }
      },
    );

    $form.on(
      "focusout.mkInvPriceFmt change.mkInvPriceFmt",
      "input.listPrice",
      function () {
        var $el = $(this);
        $el.removeData("mkPriceTyping");
        formatListPriceInput($el, true);
      },
    );

      // Re-apply dots only when fields are idle (avoid flicker while focused / typing ≥1.000.000).
      if (!$form.data("mkInvPriceWatch")) {
        $form.data("mkInvPriceWatch", true);
        var watchTimer = null;
        var watch = function () {
          if (!$form.closest("body").length) {
            return;
          }
          $form.find("tr.lineItemRow input.listPrice").each(function () {
            var $el = $(this);
            if (document.activeElement === $el[0] || $el.data("mkPriceTyping")) {
              return;
            }
            formatListPriceInput($el, false);
          });
          watchTimer = setTimeout(watch, 1200);
        };
        watchTimer = setTimeout(watch, 600);
        $form.on("remove.mkInvPriceWatch destroy.mkInvPriceWatch", function () {
          if (watchTimer) {
            clearTimeout(watchTimer);
          }
        });
      }
  }

  function sumLinePreTax($form) {
    var sum = 0;
    $form.find("tr.lineItemRow").each(function () {
      sum += getRowAfterCk($(this), $form);
    });
    return sum;
  }

  function sumLineGrossTotal($form) {
    var sum = 0;
    $form.find("tr.lineItemRow").each(function () {
      sum += calcLineRowTotal($(this), $form);
    });
    return sum;
  }

  function resolveInvoiceTierFromTotal(total) {
    total = parseMoney(total);
    if (total >= 7000000) {
      return "gte_7m";
    }
    if (total >= 5000000) {
      return "gte_5m";
    }
    if (total >= 3000000) {
      return "gte_3m";
    }
    if (total >= 1000000) {
      return "gte_1m";
    }
    return "lt_1m";
  }

  function invoiceTierLabel(tierKey) {
    var label = tierKey || "";
    INVOICE_TIER_OPTIONS.some(function (item) {
      if (item.value === tierKey) {
        label = item.label;
        return true;
      }
      return false;
    });
    return label;
  }

  function getInvoiceTierSelection($form) {
    var value = String(
      $form.find('[name="mk_invoice_price_tier"]').first().val() ||
        DEFAULT_INVOICE_TIER,
    ).trim();
    if (value === "auto" || INVOICE_TIER_FIELDS[value]) {
      return value;
    }
    return DEFAULT_INVOICE_TIER;
  }

  function findCatalogProduct(productId) {
    var id = String(productId || "");
    var found = null;
    (productCatalogCache || []).some(function (item) {
      if (String(item.id || "") === id) {
        found = item;
        return true;
      }
      return false;
    });
    return found;
  }

  function getProductMetaForRow($row) {
    var $opt = $row.find(".mk-inv-product-select option:selected").first();
    var meta = readProductMetaFromOption($opt);
    var productId = String(
      $row.find("input.selectedModuleId").first().val() ||
        $row.find(".mk-inv-product-select").first().val() ||
        "",
    );
    var catalogMeta = findCatalogProduct(productId);
    if (catalogMeta) {
      $.each(catalogMeta, function (key, value) {
        if (meta[key] == null || meta[key] === "") {
          meta[key] = value;
        }
      });
    }
    return meta;
  }

  function resolveInvoiceTierPrice(meta, tierKey) {
    meta = meta || {};
    if (getPriceChannel() === "tuibao") {
      if (meta.price_tuibao !== undefined && meta.price_tuibao !== null && meta.price_tuibao !== "") {
        return parseMoney(meta.price_tuibao);
      }
      return parseMoney(meta.price || 0);
    }
    var field = INVOICE_TIER_FIELDS[tierKey];
    if (field && meta[field] !== undefined && meta[field] !== null && meta[field] !== "") {
      return parseMoney(meta[field]);
    }
    return parseMoney(meta.price || 0);
  }

  function applyInvoiceTierPriceToRow($row, $form, tierKey, opts) {
    opts = opts || {};
    if (!rowHasSelectedProduct($row)) {
      return false;
    }
    var $listPrice = $row.find("input.listPrice").first();
    if (!$listPrice.length) {
      return false;
    }
    var tierChanged = !!opts.tierChange || !!opts.productPick;
    // Keep user-typed / already-saved unit price unless forced (tier dropdown / product pick).
    if (!opts.force && !tierChanged && String($listPrice.attr("data-mk-price-manual") || "") === "1") {
      return false;
    }
    var existingPrice = parseMoney($listPrice.val());
    // Soft re-apply must never clobber an existing unit price (catalog restyle after edit load).
    if (!opts.force && !tierChanged && existingPrice > 0) {
      return false;
    }
    // First paint on Edit only: keep DB price when not an explicit product/tier change.
    // (Previously force-without-productPick blocked Bảng giá change handlers.)
    if (opts.force && !tierChanged && existingPrice > 0) {
      $listPrice.attr("data-mk-price-manual", "1");
      return false;
    }
    var price = resolveInvoiceTierPrice(getProductMetaForRow($row), tierKey);
    // Don't wipe a good price with a zero catalog/tier default.
    if (price <= 0 && existingPrice > 0) {
      return false;
    }
    setListPriceValue($listPrice, price);
    $listPrice.attr("data-mk-invoice-tier", tierKey);
    if (opts.force && tierChanged) {
      $listPrice.removeAttr("data-mk-price-manual");
    }
    syncRowAmounts($row, $form);
    return true;
  }

  /** User changed Bảng giá — always reprice line items from catalog tiers. */
  function onInvoicePriceTierUserChange($form) {
    applyInvoiceTierPricing($form, { force: true, productPick: true, tierChange: true });
  }

  function syncInvoiceTierUi($form, selectedValue, resolvedTier) {
    var $wrap = $form.find(".mk-inv-price-tier").first();
    var $select = $form
      .find("#mkInvInvoicePriceTierSelect, [name='mk_invoice_price_tier']")
      .first();
    var isTuibao = getPriceChannel() === "tuibao";
    if (isTuibao) {
      if ($select.length) {
        $select.prop("disabled", true);
      }
      if ($wrap.length) {
        $wrap
          .addClass("is-tuibao")
          .find(".mk-inv-price-tier__label")
          .text("Bảng giá: Tuibao");
        $wrap
          .find(".mk-inv-price-tier__hint")
          .text("Đơn giá theo giá Tuibao (khách nhượng quyền / chuỗi)");
        $wrap.find(".mk-inv-price-tier__status").text("Đang áp dụng: Giá Tuibao");
      } else if ($select.length) {
        $select.attr("title", "Bảng giá Tuibao");
      }
      return;
    }
    if ($select.length) {
      $select.prop("disabled", false);
    }
    if ($wrap.length) {
      $wrap.removeClass("is-tuibao");
      $wrap.find(".mk-inv-price-tier__label").text("Bảng giá");
      $wrap
        .find(".mk-inv-price-tier__hint")
        .text("Đơn giá lấy từ Hàng hoá và cập nhật khi tổng đơn thay đổi");
    }
    if ($select.length && String($select.val() || "") !== String(selectedValue || "")) {
      $select.val(selectedValue);
    }
    var detail =
      selectedValue === "auto"
        ? "Đang áp dụng: " + invoiceTierLabel(resolvedTier)
        : "Đang áp dụng: " + invoiceTierLabel(selectedValue);
    if ($wrap.length) {
      $wrap.find(".mk-inv-price-tier__status").text(detail);
    } else if ($select.length) {
      $select.attr("title", detail);
    }
  }

  function applyInvoiceTierPricing($form, opts) {
    opts = opts || {};
    if (!$form || !$form.length || $form.data("mkInvApplyingTier")) {
      return;
    }
    var selectedValue = getInvoiceTierSelection($form);
    var resolvedTier =
      selectedValue === "auto"
        ? resolveInvoiceTierFromTotal(sumLinePreTax($form))
        : selectedValue;
    if (getPriceChannel() === "tuibao") {
      resolvedTier = "tuibao";
    }
    var changed = false;
    $form.data("mkInvApplyingTier", true);
    try {
      $form.find("tr.lineItemRow").each(function () {
        changed =
          applyInvoiceTierPriceToRow($(this), $form, resolvedTier, opts) || changed;
      });
      syncInvoiceTierUi($form, selectedValue, resolvedTier);
      if (changed) {
        var $firstRow = $form
          .find("tr.lineItemRow")
          .filter(function () {
            return rowHasSelectedProduct($(this));
          })
          .first();
        if ($firstRow.length) {
          triggerLineRecalc($firstRow, $form);
        }
        syncTotalsDisplay($form);
      }
    } finally {
      $form.data("mkInvApplyingTier", false);
      formatAllListPrices($form, true);
    }
  }

  function scheduleInvoiceTierPricing($form, delay) {
    if (!$form || !$form.length || $form.data("mkInvApplyingTier")) {
      return;
    }
    var prior = $form.data("mkInvTierTimer");
    if (prior) {
      clearTimeout(prior);
    }
    var timer = setTimeout(function () {
      $form.removeData("mkInvTierTimer");
      applyInvoiceTierPricing($form);
    }, delay == null ? 160 : delay);
    $form.data("mkInvTierTimer", timer);
  }

  function ensureGroupTaxMode($form) {
    var $taxType = $form.find("#taxtype");
    if ($taxType.length && $taxType.val() !== "group") {
      $taxType.val("group").trigger("change");
    }
    var taxPct = getPrimaryTaxPercent($form);
    $form.find(".groupTaxPercentage").each(function (idx) {
      if (idx === 0 && (!$(this).val() || parseFloat($(this).val()) <= 0)) {
        $(this).val(taxPct);
      }
    });
    $form.find("tr.lineItemRow .taxPercentage").each(function () {
      if (!$(this).val() || parseFloat($(this).val()) <= 0) {
        $(this).val(taxPct);
      }
    });
  }

  var productCatalogPromise = null;
  var productCatalogCache = null;

  if (
    typeof window !== "undefined" &&
    window.MK_PRODUCT_CATALOG &&
    window.MK_PRODUCT_CATALOG.length
  ) {
    productCatalogCache = window.MK_PRODUCT_CATALOG;
  } else if (
    typeof window !== "undefined" &&
    window.MK_WH_PRODUCT_CATALOG &&
    window.MK_WH_PRODUCT_CATALOG.length
  ) {
    productCatalogCache = window.MK_WH_PRODUCT_CATALOG;
  }

  function loadProductCatalog(forceReload) {
    if (!forceReload && productCatalogCache) {
      return $.Deferred().resolve(productCatalogCache).promise();
    }
    if (!forceReload && productCatalogPromise) {
      return productCatalogPromise;
    }
    productCatalogPromise = $.Deferred();
    if (
      typeof window !== "undefined" &&
      window.MK_PRODUCT_CATALOG &&
      window.MK_PRODUCT_CATALOG.length
    ) {
      productCatalogCache = window.MK_PRODUCT_CATALOG;
      productCatalogPromise.resolve(productCatalogCache);
      return productCatalogPromise.promise();
    }
    if (
      typeof window !== "undefined" &&
      window.MK_WH_PRODUCT_CATALOG &&
      window.MK_WH_PRODUCT_CATALOG.length
    ) {
      productCatalogCache = window.MK_WH_PRODUCT_CATALOG;
      productCatalogPromise.resolve(productCatalogCache);
      return productCatalogPromise.promise();
    }
    if (typeof app === "undefined" || !app.request) {
      productCatalogCache = [];
      productCatalogPromise.resolve([]);
      return productCatalogPromise.promise();
    }
    app.request
      .post({ data: { module: "Inventory", action: "ProductCatalog" } })
      .then(function (err, res) {
        var list = !err && res && res.products ? res.products : [];
        if (list.length) {
          productCatalogCache = list;
          productCatalogPromise.resolve(list);
          return;
        }
        app.request
          .post({
            data: {
              module: "Warehouse",
              action: "WhMgmtApi",
              mode: "product_catalog",
            },
          })
          .then(function (err2, res2) {
            var fallback = !err2 && res2 && res2.products ? res2.products : [];
            productCatalogCache = fallback;
            productCatalogPromise.resolve(fallback);
          });
      });
    setTimeout(function () {
      if (
        productCatalogPromise &&
        productCatalogPromise.state() === "pending"
      ) {
        productCatalogCache = productCatalogCache || [];
        productCatalogPromise.resolve(productCatalogCache);
      }
    }, 8000);
    return productCatalogPromise.promise();
  }

  function fillProductSelect($sel, products) {
    buildProductSelectOptions($sel, products || []);
    $sel.prop("disabled", false);
    $sel.data("mkCatalogReady", true);
    $sel.removeData("mkLoading");
  }

  function getInventoryEditInstance($form) {
    if (typeof Inventory_Edit_Js === "undefined") {
      return null;
    }
    try {
      var moduleName = $form.find('[name="module"]').val();
      return Inventory_Edit_Js.getInstanceByModuleName(moduleName);
    } catch (ignore) {
      return null;
    }
  }

  function rowHasSelectedProduct($row) {
    if (!$row || !$row.length) {
      return false;
    }
    var productId = $.trim(
      $row.find("input.selectedModuleId").first().val() || "",
    );
    if (productId && productId !== "0") {
      return true;
    }
    return !!$.trim($row.find(".mk-inv-product-select").first().val() || "");
  }

  function countVisibleLineItemRows($form) {
    if (!$form || !$form.length) {
      return 0;
    }
    return $form
      .find("#lineItemTab tr.lineItemRow")
      .not(".hide, .lineItemCloneCopy, #row0")
      .length;
  }

  function shouldShowLineDelete($row, $form) {
    // Always allow deleting a real line — including the last remaining row.
    return !isTemplateLineItemRow($row);
  }

  function patchLegacyLineDeleteGuard() {
    if (typeof Inventory_Edit_Js === "undefined") {
      return;
    }
    if (Inventory_Edit_Js.prototype.__mkInvAlwaysShowDelete) {
      return;
    }
    Inventory_Edit_Js.prototype.__mkInvAlwaysShowDelete = true;
    Inventory_Edit_Js.prototype.checkLineItemRow = function () {
      this.showLineItemsDeleteIcon();
      try {
        var $form = this.getForm ? this.getForm() : jQuery("#EditView");
        if ($form && $form.length) {
          syncLineDeleteVisibility($form);
        }
      } catch (ignore) {
        /* ignore */
      }
    };
    Inventory_Edit_Js.prototype.hideLineItemsDeleteIcon = function () {
      // Keep delete visible even when only one line remains.
      this.showLineItemsDeleteIcon();
      try {
        var $form = this.getForm ? this.getForm() : jQuery("#EditView");
        if ($form && $form.length) {
          syncLineDeleteVisibility($form);
        }
      } catch (ignore2) {
        /* ignore */
      }
    };
  }

  function normalizeItemTypeKey(type) {
    var t = String(type || "")
      .trim()
      .toLowerCase();
    if (
      t === "service" ||
      t === "services" ||
      t === "dịch vụ" ||
      t === "dich vu"
    ) {
      return "service";
    }
    return "product";
  }

  function formatItemTypeLabel(type) {
    return normalizeItemTypeKey(type) === "service" ? "Dịch vụ" : "Sản phẩm";
  }

  function itemTypeIconClass(type) {
    return normalizeItemTypeKey(type) === "service"
      ? "fa fa-wrench"
      : "fa fa-cube";
  }

  function pauseLineItemRestyle($form, ms) {
    if (!$form || !$form.length) {
      return;
    }
    ms = ms || 700;
    $form.data("mkInvRestylePausedUntil", Date.now() + ms);
  }

  function isLineItemRestylePaused($form) {
    if (!$form || !$form.length) {
      return false;
    }
    var until = $form.data("mkInvRestylePausedUntil") || 0;
    return Date.now() < until;
  }

  function performLineItemDelete($form, $trigger) {
    if (!$form || !$form.length || !$trigger || !$trigger.length) {
      return;
    }
    var $row = $trigger.closest("tr.lineItemRow");
    if (!$row.length || isTemplateLineItemRow($row)) {
      return;
    }
    pauseLineItemRestyle($form, 900);
    var inst = getInventoryEditInstance($form);
    if (inst && typeof inst.getClosestLineItemRow === "function") {
      $row = inst.getClosestLineItemRow($trigger);
    }
    if (!$row || !$row.length || !$row.closest("body").length) {
      return;
    }
    $row.remove();
    if (inst) {
      if (typeof inst.checkLineItemRow === "function") {
        inst.checkLineItemRow();
      }
      if (typeof inst.lineItemDeleteActions === "function") {
        inst.lineItemDeleteActions();
      }
    }
    syncLineDeleteVisibility($form);
    syncCreditTermsVisibility($form);
    scheduleInvoiceTierPricing($form, 0);
    var syncFn = $form.data("mkScheduleRealtimeSync");
    if (syncFn) {
      syncFn();
    }
  }

  function ensureLineDeleteButton($row, $form) {
    if (isTemplateLineItemRow($row)) {
      return;
    }
    if (!$form || !$form.length) {
      $form = $row.closest("form");
    }

    var $tools = $row.find("> td:first-child");
    if (!$tools.length) {
      return;
    }
    $tools.addClass("mk-inv-col-drag");

    $row.find("> td.mk-inv-col-amount .mk-inv-line-del").remove();

    var $lineDel = $tools.find(".mk-inv-line-del").first();
    var $btnWrap = $lineDel.find(".mk-inv-del-btn").first();
    var $del = $btnWrap.find(".mk-inv-del-icon").first();

    if (
      $row.data("mkDelMounted") &&
      $lineDel.length &&
      $btnWrap.length &&
      $del.length &&
      $btnWrap.closest(".mk-inv-line-del")[0] === $lineDel[0]
    ) {
      if (shouldShowLineDelete($row, $form)) {
        $lineDel.show();
        $btnWrap.show().removeClass("mk-inv-hide-legacy");
        $del.show().removeClass("mk-inv-hide-legacy");
      } else {
        $lineDel.hide();
        $btnWrap.hide();
        $del.hide();
      }
      return;
    }

    var $legacyDel = $row.find(".deleteRow").not(".mk-inv-del-btn").first();
    if ($legacyDel.length && !$del.length) {
      $del = $legacyDel;
    }
    if (!$del.length) {
      $del = $(
        '<i class="fa fa-trash mk-inv-del-icon cursorPointer" title="Xóa dòng"></i>',
      );
    } else {
      $del
        .removeClass("deleteRow")
        .addClass("mk-inv-del-icon")
        .attr("title", "Xóa dòng");
    }

    if (!$lineDel.length) {
      $lineDel = $(
        '<span class="mk-inv-line-del mk-inv-line-del--left" title="Xóa dòng"></span>',
      );
      $tools.prepend($lineDel);
    }

    if (!$btnWrap.length) {
      $btnWrap = $(
        '<span class="mk-inv-del-btn cursorPointer" title="Xóa dòng"></span>',
      );
      $lineDel.empty().append($btnWrap);
    } else {
      $btnWrap.addClass("cursorPointer").attr("title", "Xóa dòng");
      if ($btnWrap.closest(".mk-inv-line-del")[0] !== $lineDel[0]) {
        $btnWrap.detach().appendTo($lineDel);
      }
    }

    if (!$del.parent().is($btnWrap)) {
      $btnWrap.empty().append($del);
    }

    $row.data("mkDelMounted", true);

    if (shouldShowLineDelete($row, $form)) {
      $lineDel.show();
      $btnWrap.show().removeClass("mk-inv-hide-legacy");
      $del.show().removeClass("mk-inv-hide-legacy");
    } else {
      $lineDel.hide();
      $btnWrap.hide();
      $del.hide();
    }
  }

  function syncLineDeleteVisibility($form) {
    if (!$form || !$form.length) {
      return;
    }
    $form.find("#lineItemTab tr.lineItemRow").each(function () {
      ensureLineDeleteButton($(this), $form);
    });
  }

  function applyProductSelection($row, $form, productId) {
    var $nameInput = $row.find("input.productName").first();
    var $hiddenId = $row.find("input.selectedModuleId").first();
    var $listPrice = $row.find("input.listPrice").first();
    var $entityType = $row
      .find('input[name^="lineItemType"], input.lineItemType')
      .first();

    if (!productId) {
      $hiddenId.val("");
      $nameInput.val("").removeAttr("disabled");
      if ($listPrice.length) {
        setListPriceValue($listPrice, 0);
      }
      $row.removeData("mkAvailableStock mkStockCacheKey mkStockCachedHtml");
      $row.find(".mk-inv-stock-hint").remove();
      $row.removeClass("mk-inv-row--stock-warn");
      triggerLineRecalc($row, $form);
      ensureLineDeleteButton($row, $form);
      syncCreditTermsVisibility($form);
      scheduleInvoiceTierPricing($form, 0);
      syncProductSelectDisplay($row, $form);
      return;
    }

    var fallbackName = commitProductSelectionImmediate($row, $form, productId);
    var $opt = $row.find(".mk-inv-product-select option:selected");
    if (!$opt.length || !String($opt.val() || "").length) {
      $opt = findProductOption(
        $row.find("select.mk-inv-product-select").first(),
        productId,
      );
    }
    if (!fallbackName) {
      fallbackName = decodeText($opt.attr("data-name") || $opt.text());
    }
    var fallbackPrice = parseMoney($opt.attr("data-price") || 0);
    var catalogStock = parseFloat($opt.attr("data-stock"));
    if (isFinite(catalogStock)) {
      $row.data("mkAvailableStock", Math.max(0, catalogStock));
      $row.removeData("mkStockCacheKey mkStockCachedHtml");
    }

    if ($listPrice.length && fallbackPrice > 0) {
      setListPriceValue($listPrice, fallbackPrice);
      $listPrice.removeAttr("data-mk-price-manual");
    }
    var previewTier = getInvoiceTierSelection($form);
    applyInvoiceTierPriceToRow(
      $row,
      $form,
      previewTier === "auto"
        ? resolveInvoiceTierFromTotal(sumLinePreTax($form))
        : previewTier,
      { force: true, productPick: true },
    );
    // Đảm bảo có SL > 0 để Tổng giá trị / Số tiền trước thuế hiện ra.
    var $qty = $row.find("input.qty, .qty").first();
    if ($qty.length && !(parseMoney($qty.val()) > 0)) {
      $qty.val(1);
    }
    syncRowUnitFromProduct($row);
    enforceQtyAgainstStock($row, $form);
    syncRowStockHint($row, $form);
    ensureLineDeleteButton($row, $form);
    syncCreditTermsVisibility($form);
    syncRowAmounts($row, $form);
    syncTotalsDisplay($form);

    var moduleName = $form.find('[name="module"]').val() || "Quotes";
    var currencyId = $form.find("#currency_id").val() || "";

    if (typeof app === "undefined" || !app.request) {
      triggerLineRecalc($row, $form);
      scheduleInvoiceTierPricing($form, 0);
      return;
    }

    var url =
      "index.php?module=Inventory&action=GetTaxes&record=" +
      encodeURIComponent(productId) +
      "&currency_id=" +
      encodeURIComponent(currencyId) +
      "&sourceModule=" +
      encodeURIComponent(moduleName);

    app.request.get({ url: url }).then(function (err, data) {
      var inst = getInventoryEditInstance($form);
      if (!err && data && data[0] && inst && inst.mapResultsToFields) {
        $row
          .find('input.lineItemType, input[name^="lineItemType"]')
          .val("ProductsServices");
        inst.mapResultsToFields($row, data[0]);
        var responseTier = getInvoiceTierSelection($form);
        applyInvoiceTierPriceToRow(
          $row,
          $form,
          responseTier === "auto"
            ? resolveInvoiceTierFromTotal(sumLinePreTax($form))
            : responseTier,
          { force: true, productPick: true },
        );
        syncRowUnitFromProduct($row);
        syncRowTaxPill($row, $form);
        syncRowAmounts($row, $form);
        enforceQtyAgainstStock($row, $form);
        triggerLineRecalc($row, $form);
        syncRowStockHint($row, $form);
        ensureLineDeleteButton($row, $form);
        syncCreditTermsVisibility($form);
        scheduleInvoiceTierPricing($form, 0);
        paintProductSelectLabel(
          $row.find("select.mk-inv-product-select").first(),
          $row.find("input.productName").val(),
        );
        return;
      }
      $hiddenId.val(productId);
      $nameInput.val(fallbackName).attr("disabled", "disabled");
      if ($listPrice.length) {
        if (fallbackPrice > 0) {
          setListPriceValue($listPrice, fallbackPrice);
        }
      }
      var fallbackTier = getInvoiceTierSelection($form);
      applyInvoiceTierPriceToRow(
        $row,
        $form,
        fallbackTier === "auto"
          ? resolveInvoiceTierFromTotal(sumLinePreTax($form))
          : fallbackTier,
        { force: true, productPick: true },
      );
      syncRowUnitFromProduct($row);
      enforceQtyAgainstStock($row, $form);
      triggerLineRecalc($row, $form);
      syncRowStockHint($row, $form);
      ensureLineDeleteButton($row, $form);
      syncCreditTermsVisibility($form);
      scheduleInvoiceTierPricing($form, 0);
      paintProductSelectLabel(
        $row.find("select.mk-inv-product-select").first(),
        $row.find("input.productName").val(),
      );
    });
  }

  function triggerLineRecalc($row, $form) {
    $row.find(".qty, .listPrice").first().trigger("focusout");
    if (typeof Inventory_Edit_Js !== "undefined") {
      try {
        var inst = Inventory_Edit_Js.getInstanceByModuleName(
          $form.find('[name="module"]').val(),
        );
        if (inst && inst.lineItemRowHolder) {
          inst.lineItemRowHolder.trigger("focusout");
        }
      } catch (e) {
        /* ignore */
      }
    }
    setTimeout(function () {
      syncRowAmounts($row, $form);
      syncTotalsDisplay($form);
    }, 80);
  }

  function isTemplateLineItemRow($row) {
    return (
      !$row ||
      !$row.length ||
      $row.hasClass("hide") ||
      $row.hasClass("lineItemCloneCopy") ||
      $row.is("#row0")
    );
  }

  function isProductDropdownHealthy($sel) {
    if (
      !$sel ||
      !$sel.length ||
      !$sel.hasClass("mk-inv-product-native") ||
      !$.contains(document.documentElement, $sel[0])
    ) {
      return false;
    }
    if (typeof $.fn.select2 === "function") {
      return (
        !!$sel.data("select2") &&
        $sel.siblings(".select2-container").filter(":visible").length > 0
      );
    }
    return !!$sel.data("mkCatalogReady");
  }

  function destroyProductSelect2($sel) {
    if (!$sel || !$sel.length) {
      return;
    }
    if ($sel.data("select2")) {
      try {
        $sel.select2("close");
        $sel.select2("destroy");
      } catch (ignore) {
        /* ignore */
      }
    }
    $sel.siblings(".select2-container").remove();
    $sel.removeClass("select2-offscreen");
  }

  function escapeHtml(text) {
    return String(text == null ? "" : text)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function normalizeSearchText(text) {
    var s = String(text || "").toLowerCase();
    if (typeof s.normalize === "function") {
      s = s.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    }
    return s.replace(/đ/g, "d").replace(/\s+/g, " ").trim();
  }

  function isAnyProductSelectOpen($scope) {
    var open = false;
    var $root = $scope && $scope.length ? $scope : $(document);
    $root
      .find("select.mk-inv-product-select, select.mk-inv-quick-product-search")
      .each(function () {
        var $sel = $(this);
        if (!$sel.data("select2")) {
          return true;
        }
        try {
          if ($sel.select2("opened")) {
            open = true;
            return false;
          }
        } catch (ignore) {
          /* ignore */
        }
        return true;
      });
    return open;
  }

  function productSelectMatcher(term, text, option) {
    var q = normalizeSearchText(term);
    if (!q) {
      return true;
    }
    var hay = normalizeSearchText(text);
    if (hay.indexOf(q) >= 0) {
      return true;
    }
    if (option) {
      var name = normalizeSearchText($(option).attr("data-name") || "");
      var sku = normalizeSearchText($(option).attr("data-sku") || "");
      if (name.indexOf(q) >= 0 || sku.indexOf(q) >= 0) {
        return true;
      }
    }
    return false;
  }

  function formatPriceVi(n) {
    var x = Math.round(Number(n) || 0);
    try {
      return new Intl.NumberFormat("vi-VN").format(x);
    } catch (e) {
      return String(x).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
  }

  /** Giá hiển thị trong dropdown tìm SP — ưu tiên bảng giá đang chọn, không chỉ unit_price. */
  function resolveOptionListPrice($opt, $form) {
    if (!$opt || !$opt.length) {
      return 0;
    }
    if (!$form || !$form.length) {
      $form = $opt.closest("form");
    }
    if (!$form || !$form.length) {
      $form = $("form#EditView, form[name='EditView']").first();
    }
    var meta = readProductMetaFromOption($opt);
    var tier = DEFAULT_INVOICE_TIER;
    if ($form && $form.length) {
      tier = getInvoiceTierSelection($form);
      if (tier === "auto") {
        tier = resolveInvoiceTierFromTotal(sumLinePreTax($form)) || DEFAULT_INVOICE_TIER;
      }
    }
    var price = resolveInvoiceTierPrice(meta, tier);
    if (!(price > 0)) {
      ["lt_1m", "gte_1m", "gte_3m", "gte_5m", "gte_7m"].some(function (k) {
        var p = resolveInvoiceTierPrice(meta, k);
        if (p > 0) {
          price = p;
          return true;
        }
        return false;
      });
    }
    if (!(price > 0)) {
      price = parseMoney(meta.price || 0);
    }
    return price;
  }

  function formatQtyShort(n) {
    var x = Number(n);
    if (!isFinite(x)) {
      return "0";
    }
    if (Math.abs(x - Math.round(x)) < 0.001) {
      return String(Math.round(x));
    }
    return String(Math.round(x * 100) / 100);
  }

  function formatProductSelectResult(item) {
    if (!item || !item.id) {
      return item && item.text ? escapeHtml(item.text) : "";
    }
    var $opt = $(item.element);
    var name = $opt.attr("data-name") || item.text || "";
    var sku = ($opt.attr("data-sku") || "").trim();
    var unit = ($opt.attr("data-unit") || "").trim();
    var itemType = $opt.attr("data-type") || "";
    var typeKey = normalizeItemTypeKey(itemType);
    var typeLabel = formatItemTypeLabel(itemType);
    var stock = formatQtyShort($opt.attr("data-stock") || 0);
    var qtyPo = formatQtyShort($opt.attr("data-qty-po") || 0);
    var qtySo = formatQtyShort($opt.attr("data-qty-so") || 0);
    var price = formatPriceVi(resolveOptionListPrice($opt));
    var unitHtml = unit
      ? '<span class="mk-inv-s2-unit">' + escapeHtml(unit) + "</span>"
      : "";
    var typeHtml =
      '<span class="mk-inv-s2-type mk-inv-s2-type--' +
      typeKey +
      '">' +
      escapeHtml(typeLabel) +
      "</span>";
    var skuHtml = sku
      ? '<span class="mk-inv-s2-sku-code">' + escapeHtml(sku) + "</span>"
      : '<span class="mk-inv-s2-sku-code mk-inv-s2-sku-code--empty">chưa có mã hàng</span>';
    return (
      '<span class="mk-inv-s2-kiot">' +
      '<span class="mk-inv-s2-kiot__thumb" aria-hidden="true"><i class="' +
      itemTypeIconClass(itemType) +
      '"></i></span>' +
      '<span class="mk-inv-s2-kiot__main">' +
      '<span class="mk-inv-s2-kiot__title">' +
      '<span class="mk-inv-s2-name">' +
      escapeHtml(name) +
      "</span>" +
      typeHtml +
      unitHtml +
      "</span>" +
      skuHtml +
      '<span class="mk-inv-s2-stockline">' +
      "Tồn: <strong>" +
      escapeHtml(stock) +
      "</strong> | Đặt NCC: " +
      escapeHtml(qtyPo) +
      " | KH đặt: " +
      escapeHtml(qtySo) +
      "</span>" +
      "</span>" +
      '<span class="mk-inv-s2-price">' +
      escapeHtml(price) +
      "</span>" +
      "</span>"
    );
  }

  function formatProductSelectSelection(item) {
    if (!item || !item.id) {
      return item && item.text ? item.text : "";
    }
    var $opt = $(item.element);
    return $opt.attr("data-name") || item.text || "";
  }

  function paintProductSelectLabel($sel, label) {
    if (!$sel || !$sel.length) {
      return;
    }
    label = decodeText(label || "");
    if (!label) {
      return;
    }
    var $chosen = $sel
      .siblings(".select2-container")
      .find(".select2-chosen")
      .first();
    if ($chosen.length && $.trim($chosen.text()) !== label) {
      $chosen.text(label);
    }
  }

  function commitProductSelectionImmediate($row, $form, productId) {
    if (!$row || !$row.length || !productId) {
      return "";
    }
    var $sel = $row.find("select.mk-inv-product-select").first();
    var $nameInput = $row.find("input.productName").first();
    var $hiddenId = $row.find("input.selectedModuleId").first();
    var $entityType = $row
      .find('input[name^="lineItemType"], input.lineItemType')
      .first();
    var $opt = findProductOption($sel, productId);
    if (!$opt.length) {
      $opt = $row.find(".mk-inv-product-select option:selected");
    }
    var meta = readProductMetaFromOption($opt);
    var displayName = decodeText(
      meta.name || $opt.attr("data-name") || $opt.text() || $nameInput.val() || "",
    );
    if (!displayName && productCatalogCache && productCatalogCache.length) {
      for (var i = 0; i < productCatalogCache.length; i++) {
        if (String(productCatalogCache[i].id) === String(productId)) {
          displayName = decodeText(productCatalogCache[i].name || "");
          meta = jQuery.extend({}, productCatalogCache[i], meta);
          break;
        }
      }
    }
    if (!displayName) {
      displayName = String(productId);
    }

    pauseLineItemRestyle($form, 1200);
    $hiddenId.val(productId);
    $nameInput.val(displayName).attr("disabled", "disabled");
    if ($entityType.length) {
      $entityType.val("ProductsServices");
    }
    if ($sel.length) {
      ensureProductOptionOnSelect($sel, productId, jQuery.extend({}, meta, { name: displayName }));
      $sel.val(String(productId));
      paintProductSelectLabel($sel, displayName);
      if ($sel.data("select2")) {
        var needsValUpdate = true;
        try {
          needsValUpdate =
            String($sel.select2("val") || "") !== String(productId);
        } catch (ignoreVal) {
          needsValUpdate = true;
        }
        if (needsValUpdate) {
          try {
            $sel.select2("val", String(productId));
          } catch (ignoreSelect2) {
            /* ignore */
          }
        }
        paintProductSelectLabel($sel, displayName);
      }
    }
    return displayName;
  }

  function initOrRefreshProductSelect2($sel) {
    if (!$sel || !$sel.length || typeof $.fn.select2 !== "function") {
      return;
    }
    if ($sel.prop("disabled") || $sel.data("mkLoading")) {
      return;
    }
    // Already healthy — refresh chosen label without destroying Select2.
    if ($sel.data("select2") && $sel.siblings(".select2-container").length) {
      $sel
        .siblings(".select2-container")
        .removeClass("mk-inv-hide-legacy")
        .css({ display: "", visibility: "" });
      $sel.addClass("select2-offscreen");
      var healthyVal = ($sel.val() || "").toString().trim();
      if (healthyVal) {
        var $healthyOpt = findProductOption($sel, healthyVal);
        paintProductSelectLabel(
          $sel,
          $healthyOpt.attr("data-name") || $healthyOpt.text() || "",
        );
      }
      return;
    }
    var currentVal = $sel.val() || "";
    destroyProductSelect2($sel);
    $sel.select2({
      placeholder: "— Tìm / chọn sản phẩm —",
      allowClear: true,
      width: "100%",
      dropdownCssClass: "mk-inv-s2-drop mk-inv-s2-search",
      minimumResultsForSearch: 0,
      matcher: function (term, text, opt) {
        return productSelectMatcher(term, text, opt);
      },
      formatResult: formatProductSelectResult,
      formatSelection: formatProductSelectSelection,
      formatNoMatches: function () {
        return "Không tìm thấy sản phẩm";
      },
      formatSearching: function () {
        return "Đang tìm…";
      },
      escapeMarkup: function (m) {
        return m;
      },
    });
    if (currentVal) {
      $sel.select2("val", currentVal);
      var $bootOpt = findProductOption($sel, currentVal);
      paintProductSelectLabel(
        $sel,
        $bootOpt.attr("data-name") || $bootOpt.text() || "",
      );
    } else {
      $sel.select2("val", "");
    }
    $sel.addClass("select2-offscreen");
    $sel
      .off("select2-open.mkInvS2 select2-close.mkInvS2")
      .on("select2-open.mkInvS2", function () {
        var $drop = $(".select2-drop.mk-inv-s2-drop.select2-drop-active");
        $drop.css("z-index", 2147483640);
        var $search = $drop.find(".select2-search input.select2-input");
        if ($search.length) {
          $search.attr("placeholder", "Tìm tên sản phẩm hoặc mã hàng…");
          // Focus search immediately so typing feels instant.
          setTimeout(function () {
            $search.focus();
          }, 0);
        }
      });
  }

  function destroyProductDropdownInRow($row) {
    var $productTd = $row.find("input.productName").closest("td");
    if (!$productTd.length) {
      return;
    }
    var $sel = $productTd.find(".mk-inv-product-select");
    destroyProductSelect2($sel);
    $sel.remove();
    $productTd.find(".itemNameDiv .select2-container").remove();
  }

  function neutralizeLegacyProductInput($row) {
    var $nameInput = $row.find("input.productName").first();
    if (!$nameInput.length) {
      return;
    }
    if ($nameInput.data("ui-autocomplete")) {
      try {
        $nameInput.autocomplete("destroy");
      } catch (ignore) {
        /* ignore */
      }
    }
    $nameInput.removeClass("autoComplete").off(".autocomplete");
  }

  function cleanupLegacyProductCell($row, $nameInput, $productTd) {
    neutralizeLegacyProductInput($row);
    $nameInput
      .removeClass("autoComplete")
      .addClass("mk-inv-hide-legacy")
      .attr({ type: "hidden", tabindex: "-1" });
    // Comment moves to dedicated "Ghi chú" column — do not hide the textarea permanently.
    $productTd
      .find(".itemNameDiv .col-lg-10 > .input-group")
      .addClass("mk-inv-hide-legacy");
    $productTd.find(".itemNameDiv .col-lg-2").addClass("mk-inv-hide-legacy");
    // Hide only legacy Select2 widgets — never our product picker.
    $productTd
      .find(".itemNameDiv .select2-container")
      .not(".mk-inv-product-select + .select2-container")
      .addClass("mk-inv-hide-legacy");
  }

  function buildProductSelectOptions($sel, products) {
    // Empty label required for Select2 placeholder (avoids double "—" text flicker).
    $sel.empty().append('<option value=""></option>');
    (products || []).forEach(function (p) {
      var id = String(p.id || "");
      var displayName = decodeText(p.name || id);
      var label = displayName;
      if (p.sku) {
        label += " (" + decodeText(p.sku) + ")";
      } else {
        label += " (chưa có mã hàng)";
      }
      $sel.append(
        $("<option></option>")
          .attr("value", id)
          .attr("data-name", displayName)
          .attr("data-price", p.price || 0)
          .attr("data-price-lt-1m", p.price_lt_1m)
          .attr("data-price-gte-1m", p.price_gte_1m)
          .attr("data-price-gte-3m", p.price_gte_3m)
          .attr("data-price-gte-5m", p.price_gte_5m)
          .attr("data-price-gte-7m", p.price_gte_7m)
          .attr("data-price-tuibao", p.price_tuibao)
          .attr("data-product-group", p.product_group || "")
          .attr("data-sku", p.sku || "")
          .attr("data-unit", p.unit || "")
          .attr("data-type", p.type || "")
          .attr("data-stock", p.stock != null ? p.stock : 0)
          .attr("data-qty-po", p.qty_po != null ? p.qty_po : 0)
          .attr("data-qty-so", p.qty_so != null ? p.qty_so : 0)
          .text(label),
      );
    });
  }

  function injectProductDropdown($row, $form) {
    if (isTemplateLineItemRow($row)) {
      return;
    }
    var $productTd = $row.find("input.productName").closest("td");
    if (!$productTd.length) {
      return;
    }
    var $existing = $productTd.find(".mk-inv-product-select").first();
    if (
      $existing.length &&
      typeof $.fn.select2 === "function" &&
      $existing.data("select2")
    ) {
      try {
        if ($existing.select2("opened")) {
          return;
        }
      } catch (ignore) {
        /* ignore */
      }
    }
    if ($existing.length && isProductDropdownHealthy($existing)) {
      $existing
        .siblings(".select2-container")
        .removeClass("mk-inv-hide-legacy")
        .css({ display: "", visibility: "" });
      $existing.addClass("select2-offscreen");
      // Backfill options quietly without destroying Select2 UI.
      if (
        $existing.find("option").length <= 1 &&
        productCatalogCache &&
        productCatalogCache.length
      ) {
        var keepVal = $existing.val();
        buildProductSelectOptions($existing, productCatalogCache);
        $existing.data("mkCatalogReady", true);
        if (keepVal) {
          $existing.val(keepVal);
          var $keepOpt = findProductOption($existing, keepVal);
          paintProductSelectLabel(
            $existing,
            $keepOpt.attr("data-name") || $keepOpt.text() || "",
          );
        }
      }
      syncProductSelectDisplay($row, $form);
      return;
    }
    if ($existing.length && $existing.data("mkLoading")) {
      return;
    }
    if ($existing.length) {
      destroyProductDropdownInRow($row);
    }
    var $nameInput = $row.find("input.productName");
    var $hiddenId = $row.find("input.selectedModuleId");

    var $sel = $(
      '<select class="mk-inv-product-select mk-inv-product-native" title="Hàng hoá" data-mk-inv-product="1"></select>',
    );
    $sel.prop("disabled", true);
    $sel.data("mkLoading", true);
    $sel.append('<option value="">Đang tải hàng hoá…</option>');

    cleanupLegacyProductCell($row, $nameInput, $productTd);
    $row.find(".lineItemPopup").addClass("mk-inv-hide-legacy");
    var $host = $productTd.find(".itemNameDiv .col-lg-10").first();
    if (!$host.length) {
      $host = $productTd.find(".itemNameDiv").first();
    }
    if (!$host.length) {
      $host = $productTd;
    }
    $host.prepend($sel);

    function applyCurrentSelection() {
      var currentId = ($hiddenId.val() || "").trim();
      if (!currentId) {
        return;
      }
      if (
        !$sel.find('option[value="' + currentId.replace(/"/g, "") + '"]').length
      ) {
        var currentName = decodeText($nameInput.val() || currentId);
        $sel.append(
          $("<option></option>")
            .attr("value", currentId)
            .attr("data-name", currentName)
            .text(currentName),
        );
      }
      $sel.val(currentId);
    }

    if (productCatalogCache && productCatalogCache.length) {
      fillProductSelect($sel, productCatalogCache);
      applyCurrentSelection();
      initOrRefreshProductSelect2($sel);
      syncProductSelectDisplay($row, $form);
    } else {
      loadProductCatalog().then(function (products) {
        if (!$sel.closest("tr").length) {
          return;
        }
        fillProductSelect($sel, products || []);
        applyCurrentSelection();
        initOrRefreshProductSelect2($sel);
        syncProductSelectDisplay($row, $form);
      });
    }

    $sel.off("change.mkInvProduct select2-selecting.mkInvProduct")
      .on("select2-selecting.mkInvProduct", function (e) {
        var choice = e.object;
        if (!choice || !choice.id) {
          return;
        }
        var label = "";
        if (choice.element) {
          label =
            $(choice.element).attr("data-name") || choice.text || "";
        }
        if (label) {
          paintProductSelectLabel($sel, label);
        }
      })
      .on("change.mkInvProduct", function () {
        applyProductSelection($row, $form, $(this).val());
      });
  }

  function readAmountRaw($el, $hiddenFallback) {
    if (!$el || !$el.length) {
      return 0;
    }
    var text = ($el.text() || "").trim();
    if (!/đ/i.test(text)) {
      var plain = parseMoney(text);
      if (plain > 0 || text === "0") {
        return plain;
      }
    }
    if ($hiddenFallback && $hiddenFallback.length) {
      var hiddenVal = parseMoney($hiddenFallback.val());
      if (hiddenVal > 0) {
        return hiddenVal;
      }
    }
    return parseMoney(text);
  }

  function writeAmountDisplay($el, raw) {
    if (!$el || !$el.length) {
      return;
    }
    $el.data("mkRawAmount", raw);
    // Inside money wrap (price/total cells): prefix "đ" is a sibling — only write the number.
    if ($el.closest(".mk-inv-money-wrap").length) {
      var numOnly = formatVndNumber(raw);
      if ($el.text() !== numOnly) {
        $el.text(numOnly);
      }
      return;
    }
    if (
      $el.hasClass("mk-inv-vnd-amount") ||
      $el.closest(".mk-inv-totals-odoo").length
    ) {
      var html =
        '<span class="mk-inv-vnd" aria-hidden="false">' +
        '<span class="mk-inv-vnd__cur">đ</span>' +
        '<span class="mk-inv-vnd__num">' +
        formatVndNumber(raw) +
        "</span>" +
        "</span>";
      if ($el.html() !== html) {
        $el.html(html);
      }
      return;
    }
    var formatted = formatVnd(raw);
    if ($el.text() !== formatted) {
      $el.text(formatted);
    }
  }

  function clampTaxPercent(pct) {
    var n = parseFloat(pct);
    if (isNaN(n) || n < 0) {
      return 0;
    }
    // Guard against money-formatted values leaking into % fields (e.g. 8.000 → 8000).
    if (n > 100) {
      return 0;
    }
    return n;
  }

  function getDefaultTaxPercent($form) {
    var fromVat = clampTaxPercent(
      $form && $form.length ? $form.find('[name="mk_vat_percent"]').val() : 0,
    );
    if (fromVat > 0) {
      return fromVat;
    }
    if (
      window.__MK_QUOTE_BA_CONFIG &&
      window.__MK_QUOTE_BA_CONFIG.vat_percent_default
    ) {
      var cfgPct = clampTaxPercent(
        window.__MK_QUOTE_BA_CONFIG.vat_percent_default,
      );
      if (cfgPct > 0) {
        return cfgPct;
      }
    }
    return 8;
  }

  function getPrimaryTaxPercent($form) {
    var $taxSel = $form.find(".mk-inv-tax-select").first();
    if ($taxSel.length) {
      var selVal = $taxSel.val();
      if (selVal === "exempt") {
        return 0;
      }
      if ($taxSel.data("mkUserChanged") || selVal === "0") {
        var forced = clampTaxPercent(selVal);
        if (selVal === "0" || selVal === 0) {
          return 0;
        }
        if (forced > 0 || $taxSel.data("mkUserChanged")) {
          return forced;
        }
      }
      var parsed = clampTaxPercent(selVal);
      if (parsed > 0) {
        return parsed;
      }
    }
    var pct = 0;
    $form.find(".groupTaxPercentage").each(function () {
      var v = clampTaxPercent($(this).val());
      if (v > 0) {
        pct = v;
        return false;
      }
    });
    if (!pct) {
      $form.find("tr.lineItemRow .taxPercentage").each(function () {
        var v = clampTaxPercent($(this).val());
        if (v > 0) {
          pct = v;
          return false;
        }
      });
    }
    if (!pct) {
      pct = getDefaultTaxPercent($form);
    }
    return pct;
  }

  function syncProductDesc($row) {
    var $productTd = $row.find("input.productName").closest("td");
    var desc = ($row.find(".lineItemCommentBox").val() || "").trim();
    var $desc = $productTd.find(".mk-inv-product-desc");
    if (!$desc.length) {
      $desc = $('<div class="mk-inv-product-desc" aria-hidden="true"></div>');
      $productTd.find(".itemNameDiv").after($desc);
    }
    if (desc) {
      $desc.html(desc.replace(/\n/g, "<br>")).show();
      $row.addClass("mk-inv-has-desc");
    } else {
      $desc.empty().hide();
      $row.removeClass("mk-inv-has-desc");
    }
  }

  var TAX_RATE_OPTIONS = [
    { value: "0", label: "0%" },
  ];

  /** Preset chiết khấu % — custom = click the number field to type */
  var DISCOUNT_RATE_OPTIONS = [
    { value: "0", label: "0%" },
    { value: "5", label: "5%" },
    { value: "8", label: "8%" },
    { value: "10", label: "10%" },
    { value: "15", label: "15%" },
    { value: "20", label: "20%" },
  ];

  function clampDiscountPercent(v) {
    var n = parseFloat(String(v).replace(",", "."));
    if (isNaN(n) || n < 0) {
      return 0;
    }
    if (n > 100) {
      return 100;
    }
    return Math.round(n * 100) / 100;
  }

  function isDiscountPresetValue(pct) {
    pct = clampDiscountPercent(pct);
    var key = String(pct);
    // normalize 10 vs 10.0
    if (Math.abs(pct - Math.round(pct)) < 0.001) {
      key = String(Math.round(pct));
    }
    return DISCOUNT_RATE_OPTIONS.some(function (opt) {
      return opt.value !== "custom" && opt.value === key;
    });
  }

  function applyLineTaxZero($row, $form) {
    $row.data("mkTaxPct", 0);
    $row.find(".taxPercentage").each(function () {
      $(this).val(0);
    });
    if ($form && $form.length) {
      $form.find(".groupTaxPercentage").each(function () {
        $(this).val(0);
      });
      var $vatPct = $form.find('[name="mk_vat_percent"]');
      if ($vatPct.length) {
        $vatPct.val(0);
      }
    }
  }

  /**
   * Recover discount % from line totals when hidden discount fields are missing.
   */
  function readDiscountFromLineTotals($row) {
    if (!$row || !$row.length) {
      return 0;
    }
    var $discEl = $row.find(".discountTotal").first();
    var discTotal = parseMoney(
      $discEl.length
        ? $discEl.attr("data-mk-raw") ||
            $discEl.data("mkRawAmount") ||
            $discEl.text() ||
            $discEl.val()
        : 0,
    );
    var $pt = $row.find(".productTotal").first();
    var productTotal = parseMoney(
      $pt.length
        ? $pt.attr("data-mk-raw") ||
            $pt.data("mkRawAmount") ||
            $pt.text() ||
            $pt.val()
        : 0,
    );
    if (productTotal <= 0 && discTotal <= 0) {
      return 0;
    }
    if (productTotal > 0 && discTotal > 0) {
      return clampDiscountPercent((discTotal / productTotal) * 100);
    }
    // Sometimes netPrice is after discount and productTotal is gross
    var tad = parseMoney(
      $row.find(".totalAfterDiscount").first().text() ||
        $row.find(".totalAfterDiscount").first().val() ||
        0,
    );
    if (productTotal > 0 && tad > 0 && tad < productTotal) {
      return clampDiscountPercent(
        ((productTotal - tad) / productTotal) * 100,
      );
    }
    return 0;
  }

  /**
   * Read discount % from Vtiger hidden fields / radios (source of truth from DB on load).
   */
  function readLegacyDiscountPercent($row) {
    if (!$row || !$row.length) {
      return 0;
    }
    var rowNo = getRowNumberValue($row) || "";
    var $discUI = $row.find("div.discountUI").first();
    var $scope = $discUI.length ? $discUI : $row;
    var $type = $scope
      .find(
        "#discount_type" +
          rowNo +
          ', input.discount_type, input[name="discount_type' +
          rowNo +
          '"]',
      )
      .first();
    if (!$type.length) {
      $type = $row
        .find(
          "#discount_type" +
            rowNo +
            ', input.discount_type, input[name="discount_type' +
            rowNo +
            '"]',
        )
        .first();
    }
    var type = String(($type.val() || "")).toLowerCase();
    var $pct = $scope
      .find(
        "#discount_percentage" +
          rowNo +
          ", .discount_percentage, input[name='discount_percentage" +
          rowNo +
          "']",
      )
      .first();
    if (!$pct.length) {
      $pct = $row
        .find(
          "#discount_percentage" +
            rowNo +
            ", .discount_percentage, input[name='discount_percentage" +
            rowNo +
            "']",
        )
        .first();
    }
    var pct = clampDiscountPercent($pct.val());
    if (type === "percentage" || type === "percent") {
      return pct > 0 ? pct : readDiscountFromLineTotals($row);
    }
    if (type === "amount") {
      var amount = parseMoney(
        $scope
          .find("#discount_amount" + rowNo + ", .discount_amount")
          .add($row.find("#discount_amount" + rowNo + ", .discount_amount"))
          .first()
          .val(),
      );
      var base =
        parseMoney($row.find(".qty").val()) *
        parseMoney($row.find(".listPrice").val());
      if (base > 0 && amount > 0) {
        return clampDiscountPercent((amount / base) * 100);
      }
      return readDiscountFromLineTotals($row);
    }
    // type zero/blank — still honour a stored percentage value if present
    if (pct > 0) {
      return pct;
    }
    var $checked = $scope.find("input.discounts").filter(":checked").first();
    if (!$checked.length) {
      $checked = $row.find("input.discounts").filter(":checked").first();
    }
    if ($checked.length) {
      var dType = String(
        $checked.attr("data-discount-type") ||
          $checked.data("discountType") ||
          "",
      ).toLowerCase();
      if (dType === "percentage" || dType === "percent") {
        return pct > 0 ? pct : readDiscountFromLineTotals($row);
      }
    }
    // Modern layout formerly omitted discountUI — recover from line totals.
    return readDiscountFromLineTotals($row);
  }

  function readDiscountFromUiControls($row) {
    if (!$row || !$row.length) {
      return 0;
    }
    // Typed % in the combo field wins over the hidden preset <select> (which may still be "0").
    var $inp = $row.find(".mk-inv-discount-custom, .mk-inv-discount-pct").first();
    if ($inp.length) {
      var raw = $inp.val();
      if (raw !== "" && raw != null) {
        return clampDiscountPercent(raw);
      }
    }
    var $sel = $row.find(".mk-inv-discount-select").first();
    if ($sel.length) {
      var sv = $sel.val();
      if (sv === "custom") {
        var cachedCustom = $row.data("mkDiscountPct");
        if (cachedCustom != null && cachedCustom !== "") {
          return clampDiscountPercent(cachedCustom);
        }
        return 0;
      }
      if (sv != null && sv !== "") {
        return clampDiscountPercent(sv);
      }
    }
    return 0;
  }

  /**
   * Write % into Vtiger fields the save path expects (discount_type / discount_percentage).
   * Scopes into discountUI so Inventory recalculate cannot leave radios out of sync.
   */
  function applyLineDiscountFields($row, pct) {
    pct = clampDiscountPercent(pct);
    $row.data("mkDiscountPct", pct);
    var rowNo = getRowNumberValue($row) || "";
    var $discUI = $row.find("div.discountUI").first();
    var $scope = $discUI.length ? $discUI : $row;
    var typeVal = pct > 0 ? "percentage" : "zero";

    var $type = $scope
      .find(
        "#discount_type" +
          rowNo +
          ', input.discount_type, input[name="discount_type' +
          rowNo +
          '"]',
      )
      .first();
    if (!$type.length) {
      $type = $row
        .find(
          "#discount_type" +
            rowNo +
            ', input.discount_type, input[name="discount_type' +
            rowNo +
            '"]',
        )
        .first();
    }
    if ($type.length) {
      $type.val(typeVal).prop("disabled", false).removeAttr("disabled");
    } else if (rowNo) {
      $type = $(
        '<input type="hidden" class="discount_type" />',
      )
        .attr("id", "discount_type" + rowNo)
        .attr("name", "discount_type" + rowNo)
        .val(typeVal);
      ($discUI.length ? $discUI : $row).append($type);
    }

    var $pct = $scope
      .find(
        "#discount_percentage" +
          rowNo +
          ", .discount_percentage, input[name='discount_percentage" +
          rowNo +
          "']",
      )
      .first();
    if (!$pct.length) {
      $pct = $row
        .find(
          "#discount_percentage" +
            rowNo +
            ", .discount_percentage, input[name='discount_percentage" +
            rowNo +
            "']",
        )
        .first();
    }
    if ($pct.length) {
      // Always send a numeric value so PHP/DB persist is reliable
      $pct
        .val(pct > 0 ? pct : "0")
        .removeClass("hide")
        .prop("disabled", false)
        .removeAttr("disabled");
    } else if (rowNo) {
      $pct = $(
        '<input type="hidden" class="discount_percentage discountVal" />',
      )
        .attr("id", "discount_percentage" + rowNo)
        .attr("name", "discount_percentage" + rowNo)
        .val(pct > 0 ? pct : "0");
      ($discUI.length ? $discUI : $row).append($pct);
    }

    // Clear amount mode so save path does not prefer a stale amount
    var $amt = $scope
      .find("#discount_amount" + rowNo + ", .discount_amount")
      .add($row.find("#discount_amount" + rowNo + ", .discount_amount"));
    if ($amt.length && pct > 0) {
      $amt.val("").addClass("hide");
    }

    // Radios must match — Inventory_Edit_Js.calculateDiscountForLineItem reads the checked one
    var $radios = $scope.find("input.discounts");
    if (rowNo && $radios.length) {
      $radios.attr("name", "discount" + rowNo);
    }
    var $zeroRadio = $scope.find(
      'input.discounts[data-discount-type="zero"]',
    );
    var $pctRadio = $scope.find(
      'input.discounts[data-discount-type="percentage"]',
    );
    var $amtRadio = $scope.find(
      'input.discounts[data-discount-type="amount"]',
    );
    if (pct > 0) {
      $pctRadio.prop("checked", true);
      $zeroRadio.prop("checked", false);
      $amtRadio.prop("checked", false);
    } else {
      $zeroRadio.prop("checked", true);
      $pctRadio.prop("checked", false);
      $amtRadio.prop("checked", false);
    }

    var qty = parseMoney($row.find(".qty").val());
    var price = parseMoney($row.find(".listPrice").val());
    var base = qty * price;
    var discAmt = Math.round((base * pct) / 100);
    var after = base - discAmt;
    var $discTotal = $row.find(".discountTotal");
    if ($discTotal.length) {
      $discTotal.text(discAmt);
    }
    var $tad = $row.find(".totalAfterDiscount");
    if ($tad.length) {
      writeAmountDisplay($tad, after);
    }
    return after;
  }

  function getRowDiscountPercent($row) {
    if (!$row || !$row.length) {
      return 0;
    }
    // User changed the dropdown/custom input — trust UI controls first
    if ($row.data("mkDiscUserSet")) {
      var fromUi = readDiscountFromUiControls($row);
      var cachedUser = $row.data("mkDiscountPct");
      if (fromUi > 0) {
        return fromUi;
      }
      var $custom = $row.find(".mk-inv-discount-custom, .mk-inv-discount-pct").first();
      if ($custom.length && String($custom.val() || "").trim() !== "") {
        return fromUi;
      }
      if (cachedUser != null && cachedUser !== "") {
        return clampDiscountPercent(cachedUser);
      }
      return fromUi;
    }
    // Load / restyle: prefer DB-backed legacy fields so a default "0%" select
    // cannot wipe a saved discount before paint.
    var legacy = readLegacyDiscountPercent($row);
    if (legacy > 0) {
      return legacy;
    }
    var cached = $row.data("mkDiscountPct");
    if (cached != null && cached !== "") {
      return clampDiscountPercent(cached);
    }
    var fromControls = readDiscountFromUiControls($row);
    if (fromControls > 0) {
      return fromControls;
    }
    return legacy || fromControls || 0;
  }

  function paintDiscountUi($row, pct) {
    pct = clampDiscountPercent(pct);
    var $sel = $row.find(".mk-inv-discount-select").first();
    var $custom = $row.find(".mk-inv-discount-custom").first();
    var $suffix = $row.find(".mk-inv-discount-suffix").first();
    if (!$custom.length && !$sel.length) {
      return;
    }
    $custom
      .removeClass("mk-inv-hide-legacy hide")
      .css({ display: "", visibility: "" })
      .prop("disabled", false)
      .prop("readonly", false);
    $suffix.css({ display: "", visibility: "" });
    if (document.activeElement !== $custom[0]) {
      $custom.val(String(pct));
    }
    var key =
      Math.abs(pct - Math.round(pct)) < 0.001
        ? String(Math.round(pct))
        : String(pct);
    if ($sel.length && $sel.find('option[value="' + key + '"]').length) {
      $sel.val(key);
    }
    $row.removeClass("mk-inv-discount--custom");
    if (!isDiscountPresetValue(pct)) {
      $row.addClass("mk-inv-discount--custom");
    }
  }

  function commitRowDiscount($row, $form, pct) {
    pct = clampDiscountPercent(pct);
    $row.data("mkDiscUserSet", true);
    $row.removeData("mkAfterCkManual");
    clearManualGrandTotal($form);
    applyLineDiscountFields($row, pct);
    applyLineTaxZero($row, $form);
    paintDiscountUi($row, pct);
    setTimeout(function () {
      syncRowAmounts($row, $form);
      syncTotalsDisplay($form);
      var fn = $form.data("mkScheduleRealtimeSync");
      if (fn) {
        fn();
      }
    }, 20);
  }

  function syncRowTaxPill($row, $form) {
    // BA: tax column is discount — keep inventory tax at 0
    if (MK_BA_VAT_INCLUDED) {
      applyLineTaxZero($row, $form);
      return;
    }
  }

  function injectTaxDropdown($row, $form) {
    // BA: chiết khấu dropdown (preset % + Tự nhập)
    injectDiscountInput($row, $form);
  }

  function injectDiscountInput($row, $form) {
    // Drop duplicate tax columns (keep one discount cell)
    var $taxCells = $row.find("> td.mk-inv-col-tax, > td.mk-inv-col-discount");
    if ($taxCells.length > 1) {
      $taxCells.slice(1).remove();
    }

    var $taxTd = $row
      .find("> td.mk-inv-col-tax, > td.mk-inv-col-discount")
      .first();
    if (!$taxTd.length) {
      $taxTd = $row.find("> td.mk-inv-col-net-slot").first();
    }
    if (!$taxTd.length) {
      $taxTd = $row.find("> td").has(".netPrice").first();
    }
    if (!$taxTd.length) {
      var $priceTdNew = $row
        .find("> td.mk-inv-col-price, td")
        .has("input.listPrice")
        .first();
      var $amountTdNew = $row.find("> td.mk-inv-col-amount").first();
      if ($priceTdNew.length || $amountTdNew.length) {
        $taxTd = $('<td class="mk-inv-col-tax mk-inv-col-discount"></td>');
        // Order: Đơn giá → Chiết khấu → Thành tiền
        if ($amountTdNew.length) {
          $amountTdNew.before($taxTd);
        } else {
          $priceTdNew.after($taxTd);
        }
      }
    }
    if (!$taxTd.length) {
      return;
    }

    // If this cell still holds the line total, move it out
    var $productTotal = $taxTd.find(".productTotal").first();
    if ($productTotal.length) {
      var $amountHost = $productTotal.closest(".mk-inv-money-wrap").length
        ? $productTotal.closest(".mk-inv-money-wrap")
        : $productTotal;
      var $amountTd = $row.find("> td.mk-inv-col-amount").first();
      if (!$amountTd.length || $amountTd[0] === $taxTd[0]) {
        $amountTd = $('<td class="mk-inv-col-amount"></td>');
        $taxTd.after($amountTd);
      }
      $amountTd.append($amountHost.detach());
    }

    $taxTd
      .addClass("mk-inv-col-tax mk-inv-col-discount")
      .removeClass(
        "mk-inv-col-net-hide mk-inv-hide-legacy mk-inv-col-net-slot mk-inv-col-amount mk-inv-col-price",
      );
    $taxTd
      .find(
        ".netPrice, span.netPrice, .individualTax, .taxDivContainer, .mk-inv-money-wrap, .mk-inv-vnd, .productTotal, .mk-inv-tax-select",
      )
      .addClass("mk-inv-hide-legacy")
      .css({ display: "none", visibility: "hidden" });

    applyLineTaxZero($row, $form);

    // Prefer server/legacy % on (re)paint so restyle cannot zero a saved discount
    var currentPct = getRowDiscountPercent($row);

    var $existingWrap = $taxTd.find(".mk-inv-discount-wrap").first();
    var $existingSel = $taxTd.find(".mk-inv-discount-select").first();
    // Rebuild if still using old split UI (two boxes).
    if ($existingWrap.length && !$existingWrap.hasClass("mk-inv-discount-wrap--combo")) {
      $existingWrap.remove();
      $existingSel = $();
    }
    if ($existingSel.length) {
      if (
        document.activeElement === $existingSel[0] ||
        document.activeElement ===
          $taxTd.find(".mk-inv-discount-custom")[0]
      ) {
        return;
      }
      $taxTd
        .children()
        .not(".mk-inv-discount-wrap")
        .addClass("mk-inv-hide-legacy")
        .css({ display: "none", visibility: "hidden" });
      if (!$row.data("mkDiscUserSet")) {
        currentPct = readLegacyDiscountPercent($row);
        if (
          currentPct <= 0 &&
          $row.data("mkDiscountPct") != null &&
          $row.data("mkDiscountPct") !== ""
        ) {
          currentPct = clampDiscountPercent($row.data("mkDiscountPct"));
        }
      } else {
        currentPct = getRowDiscountPercent($row);
      }
      paintDiscountUi($row, currentPct);
      applyLineDiscountFields($row, currentPct);
      return;
    }

    // Single combobox: type % freely + open menu for presets (no 2 side-by-side boxes).
    $taxTd.find(".mk-inv-discount-wrap").remove();

    var $wrap = $(
      '<div class="mk-inv-discount-wrap mk-inv-discount-wrap--combo">' +
        '<input type="text" inputmode="decimal" class="mk-inv-discount-custom mk-inv-discount-pct inputElement" title="Nhập % chiết khấu hoặc chọn từ menu" placeholder="0" autocomplete="off" />' +
        '<button type="button" class="mk-inv-discount-caret" tabindex="-1" title="Chọn % nhanh" aria-label="Chọn % nhanh">' +
        '<i class="fa fa-caret-down" aria-hidden="true"></i></button>' +
        '<select class="mk-inv-discount-select inputElement mk-inv-hide-legacy" title="Chọn % chiết khấu" tabindex="-1" aria-hidden="true"></select>' +
        '<span class="mk-inv-discount-suffix">%</span>' +
        "</div>",
    );
    var $sel = $wrap.find(".mk-inv-discount-select");
    DISCOUNT_RATE_OPTIONS.forEach(function (opt) {
      $sel.append(
        $("<option></option>").attr("value", opt.value).text(opt.label),
      );
    });
    var $custom = $wrap.find(".mk-inv-discount-custom");
    var $caret = $wrap.find(".mk-inv-discount-caret");

    // Seed from DB before DOM defaults to 0%
    if (!$row.data("mkDiscUserSet")) {
      currentPct = readLegacyDiscountPercent($row);
      if (
        currentPct <= 0 &&
        $row.data("mkDiscountPct") != null &&
        $row.data("mkDiscountPct") !== ""
      ) {
        currentPct = clampDiscountPercent($row.data("mkDiscountPct"));
      }
    }

    applyLineDiscountFields($row, currentPct);

    function openDiscountSelectMenu() {
      try {
        // Native select: focus + keyboard/space to open on some browsers.
        $sel.css({
          position: "absolute",
          left: 0,
          top: 0,
          width: "100%",
          height: "100%",
          opacity: 0.01,
          "z-index": 5,
          display: "block",
          "pointer-events": "auto",
        });
        $sel.focus();
        if (typeof $sel[0].showPicker === "function") {
          $sel[0].showPicker();
        }
      } catch (eOpen) {
        /* ignore */
      }
    }

    $caret.on("mousedown.mkInvDisc click.mkInvDisc", function (e) {
      e.preventDefault();
      e.stopPropagation();
      openDiscountSelectMenu();
    });
    $custom.on("keydown.mkInvDisc", function (e) {
      if (e.key === "ArrowDown" || e.which === 40) {
        e.preventDefault();
        openDiscountSelectMenu();
      }
    });
    $sel.on("change.mkInvDisc", function () {
      $sel.data("mkTaxOpen", false);
      $row.data("mkDiscUserSet", true);
      var v = $sel.val();
      if (v === "" || v == null) {
        return;
      }
      commitRowDiscount($row, $form, v);
      $sel.css({ opacity: 0, "pointer-events": "none", "z-index": 0 });
    });
    $sel.on("blur.mkInvDisc", function () {
      $sel.data("mkTaxOpen", false);
      $sel.css({ opacity: 0, "pointer-events": "none", "z-index": 0 });
    });

    $custom.on("focus.mkInvDisc", function () {
      try {
        this.select();
      } catch (e0) {
        /* ignore */
      }
    });
    $custom.on(
      "input.mkInvDisc change.mkInvDisc focusout.mkInvDisc",
      function () {
        var pct = clampDiscountPercent($custom.val());
        if (document.activeElement !== $custom[0]) {
          $custom.val(String(pct));
        }
        if (isDiscountPresetValue(pct)) {
          var key =
            Math.abs(pct - Math.round(pct)) < 0.001
              ? String(Math.round(pct))
              : String(pct);
          $sel.val(key);
        }
        commitRowDiscount($row, $form, pct);
      },
    );

    $taxTd
      .children()
      .not(".mk-inv-discount-wrap")
      .addClass("mk-inv-hide-legacy")
      .css({ display: "none", visibility: "hidden" });
    $taxTd.prepend($wrap);
    // repaint now that DOM is under $row
    paintDiscountUi($row, currentPct);
    applyLineDiscountFields($row, currentPct);
  }

  function injectNoteColumn($row, $form) {
    var rowNo = getRowNumberValue($row) || "";
    var $comment = $row.find("textarea.lineItemCommentBox").first();
    if (!$comment.length) {
      $comment = $(
        '<textarea class="lineItemCommentBox inputElement mk-inv-note-input" name="comment' +
          rowNo +
          '" id="comment' +
          rowNo +
          '" rows="1" placeholder="Ghi chú..."></textarea>',
      );
    } else {
      // Ensure name/id for postback if cloned without them
      if (!$comment.attr("name") && rowNo) {
        $comment.attr("name", "comment" + rowNo);
      }
      if (!$comment.attr("id") && rowNo) {
        $comment.attr("id", "comment" + rowNo);
      }
    }
    var $noteTd = $row.find("> td.mk-inv-col-note").first();
    if (!$noteTd.length) {
      $noteTd = $('<td class="mk-inv-col-note"></td>');
      var $amountTd = $row.find("> td.mk-inv-col-amount").first();
      if ($amountTd.length) {
        $amountTd.after($noteTd);
      } else {
        $row.append($noteTd);
      }
    }
    $noteTd
      .removeClass("mk-inv-hide-legacy mk-inv-col-net-hide")
      .css({ display: "table-cell", visibility: "visible" });
    // unwrap from hidden product-cell wrappers
    if ($comment.parent().is("div") && $comment.siblings().length === 0) {
      // keep simple structure
    }
    if ($comment.closest("td")[0] !== $noteTd[0]) {
      $noteTd.empty().append($comment.detach());
    } else if (!$noteTd.find($comment).length) {
      $noteTd.append($comment);
    }
    $comment
      .removeClass("mk-inv-hide-legacy hide")
      .addClass("mk-inv-note-input inputElement")
      .attr({ placeholder: "Ghi chú...", rows: 1 })
      .prop("disabled", false)
      .css({
        display: "block",
        visibility: "visible",
        width: "100%",
        opacity: 1,
        height: "",
        maxHeight: "",
      });
    $row.addClass("mk-inv-line-row--show-note");
    // hide under-product read-only desc mirror if empty note UI covers storage
    $row.find(".mk-inv-product-desc").addClass("mk-inv-hide-legacy").hide();
  }

  function getRowTaxPercent($row, $form) {
    // VAT-included BA: no per-line tax
    if (MK_BA_VAT_INCLUDED) {
      return 0;
    }
    return 0;
  }

  function calcLineRowTotal($row, $form) {
    var qty = parseMoney($row.find(".qty").val());
    var price = parseMoney($row.find(".listPrice").val());
    return qty * price;
  }

  function calcLineAfterDiscount($row, $form) {
    var base = calcLineRowTotal($row, $form);
    var disc = getRowDiscountPercent($row);
    var after = base - Math.round((base * disc) / 100);
    return after < 0 ? 0 : after;
  }

  function clearManualGrandTotal($form) {
    if (!$form || !$form.length) {
      return;
    }
    $form.removeData("mkGrandManual");
    $form.removeAttr("data-mk-grand-manual");
  }

  function isManualGrandTotal($form) {
    return !!(
      $form &&
      $form.length &&
      ($form.data("mkGrandManual") ||
        $form.attr("data-mk-grand-manual") === "1")
    );
  }

  function setManualGrandTotal($form, amount) {
    if (!$form || !$form.length) {
      return;
    }
    amount = parseMoney(amount);
    if (amount < 0) {
      amount = 0;
    }
    $form.data("mkGrandManual", true);
    $form.attr("data-mk-grand-manual", "1");
    var auto = sumLinePreTax($form);
    var adj = amount - auto;
    var $adj = $form.find("#adjustment, input[name='adjustment']");
    if ($adj.length) {
      $adj.val(adj);
    }
    var $result = $form.find("#lineItemResult");
    writeAmountDisplay($result.find("#grandTotal, .grandTotal"), amount);
    $form.find('#total, input[name="total"]').val(amount);
    var $manualInput = $result.find(".mk-inv-grand-manual-input");
    if ($manualInput.length) {
      $manualInput.val(formatVndNumber(amount));
    }
  }

  function reorderTaxBeforePriceColumns($row) {
    // BA order: Đơn giá → Chiết khấu (tax col) → Thành tiền
    if (!$row || !$row.length) {
      return;
    }
    var $priceTd = $row.find("> td.mk-inv-col-price").first();
    var $taxTd = $row
      .find("> td.mk-inv-col-tax, > td.mk-inv-col-discount")
      .first();
    if (!$priceTd.length || !$taxTd.length || $priceTd[0] === $taxTd[0]) {
      return;
    }
    if ($taxTd.index() < $priceTd.index()) {
      $taxTd.insertAfter($priceTd);
    }
  }

  function reorderTaxBeforeAmountColumns($row) {
    reorderTaxBeforePriceColumns($row);
    if (!$row || !$row.length) {
      return;
    }
    var $amountTd = $row.find("> td.mk-inv-col-amount").first();
    var $taxTd = $row
      .find("> td.mk-inv-col-tax, > td.mk-inv-col-discount")
      .first();
    if (!$amountTd.length || !$taxTd.length || $amountTd[0] === $taxTd[0]) {
      return;
    }
    // Chiết khấu immediately before Thành tiền (after Đơn giá)
    if ($taxTd.index() > $amountTd.index() || $taxTd.index() < $amountTd.index() - 1) {
      $taxTd.insertBefore($amountTd);
      // ensure still after price
      reorderTaxBeforePriceColumns($row);
    }
  }

  function syncRowAmounts($row, $form) {
    var $total = $row.find(".productTotal");
    if (!$total.length) {
      return;
    }
    if (!$form || !$form.length) {
      $form = $row.closest("form");
    }
    var lineTotal = calcLineRowTotal($row, $form);
    writeAmountDisplay($total, lineTotal);
    $total.data("mkRawAmount", lineTotal);
    var $amountTd = $total.closest("td");
    $amountTd.addClass("mk-inv-col-amount");
    $amountTd
      .children()
      .not(
        ".productTotal, .mk-inv-money-wrap, .mk-inv-line-del, .mk-inv-amount-wrap",
      )
      .addClass("mk-inv-hide-legacy");

    ensureAfterCkCell($row, $form);
  }

  function ensureAfterCkCell($row, $form) {
    var $amountTd = $row.find("> td.mk-inv-col-amount").first();
    var $noteTd = $row.find("> td.mk-inv-col-note").first();
    var $ckTd = $row.find("> td.mk-inv-col-afterck").first();
    if (!$ckTd.length) {
      $ckTd = $('<td class="mk-inv-col-afterck"></td>');
      if ($noteTd.length) {
        $ckTd.insertBefore($noteTd);
      } else if ($amountTd.length) {
        $ckTd.insertAfter($amountTd);
      } else {
        $row.append($ckTd);
      }
    }
    var afterCk = calcLineAfterDiscount($row, $form);
    var $inp = $ckTd.find(".mk-inv-afterck-input");
    if (!$inp.length) {
      $inp = $('<input type="text" class="mk-inv-afterck-input inputElement" inputmode="numeric" />');
      $ckTd.empty().append(
        $('<div class="mk-inv-money-wrap"></div>')
          .append('<span class="mk-inv-currency-prefix">đ</span>')
          .append($inp)
      );
      $inp.on("change blur", function () {
        var typed = parseMoney($(this).val());
        if (typed >= 0) {
          $row.data("mkAfterCkManual", typed);
        }
        syncTotalsDisplay($form || $row.closest("form"));
      });
    }
    if (document.activeElement !== $inp[0]) {
      var manual = $row.data("mkAfterCkManual");
      if (manual != null && manual !== "") {
        $inp.val(formatVndNumber(parseMoney(manual)));
      } else {
        $inp.val(formatVndNumber(afterCk));
      }
    }
  }

  function getRowAfterCk($row, $form) {
    var manual = $row.data("mkAfterCkManual");
    if (manual != null && manual !== "") {
      return parseMoney(manual);
    }
    return calcLineAfterDiscount($row, $form);
  }

  function syncAllRowAmounts($form) {
    if (!$form || !$form.length) {
      return;
    }
    $form.find("tr.lineItemRow").each(function () {
      syncRowAmounts($(this), $form);
    });
  }

  function headerLabelForCell($sampleRow, index) {
    var $cell = $sampleRow.children("td").eq(index);
    if (!$cell.length) {
      return "";
    }
    if (index === 0) {
      return "";
    }
    if ($cell.hasClass("mk-inv-col-net-hide")) {
      return "__hide__";
    }
    if ($cell.find("input.productName").length) {
      return "Tên mục";
    }
    if ($cell.hasClass("mk-inv-col-unit")) {
      return "Đơn vị tính";
    }
    if ($cell.find("input.qty, .qty").length) {
      return "Số lượng";
    }
    if ($cell.find("input.listPrice").length) {
      return "Đơn giá";
    }
    if ($cell.find(".productTotal").length) {
      return "Thành tiền";
    }
    if (
      $cell.hasClass("mk-inv-col-tax") ||
      $cell.hasClass("mk-inv-col-discount") ||
      $cell.find(
        ".mk-inv-discount-select, .mk-inv-discount-pct, .mk-inv-tax-select",
      ).length
    ) {
      return "Chiết khấu (%)";
    }
    if (
      $cell.hasClass("mk-inv-col-note") ||
      $cell.find(".lineItemCommentBox, .mk-inv-note-input").length
    ) {
      return "Ghi chú";
    }
    return "";
  }

  function syncTaxHeaderLabel($table) {
    var $header = getLineItemHeaderRow($table);
    var $sample = getLineItemSampleRow($table);
    if (!$header.length || !$sample.length) {
      return;
    }

    // BA: force discount/unit-price/note labels (never rewrite discount back to "Thuế")
    $header.children("td").each(function () {
      var $td = $(this);
      var text = $.trim($td.text());
      if (/thuế|tax/i.test(text) && !/gtgt/i.test(text)) {
        $td
          .html('<span class="mk-inv-th-label">Chiết khấu (%)</span>')
          .removeClass("mk-inv-col-net-hide mk-inv-hide-legacy")
          .addClass("mk-inv-col-tax-head mk-inv-col-tax mk-inv-col-discount");
      }
      if (/bảng giá/i.test(text)) {
        $td
          .html('<span class="mk-inv-th-label">Đơn giá</span>')
          .addClass("mk-inv-col-price");
      }
      if (/tổng giá trị/i.test(text)) {
        $td
          .html('<span class="mk-inv-th-label">Thành tiền</span>')
          .addClass("mk-inv-col-amount");
      }
    });

    var lastIdx = -1;
    $sample.children("td").each(function (idx) {
      if (
        $(this).hasClass("mk-inv-col-tax") ||
        $(this).find(
          ".mk-inv-discount-select, .mk-inv-discount-pct, .mk-inv-tax-select",
        ).length
      ) {
        lastIdx = idx;
      }
    });
    if (lastIdx >= 0) {
      $header
        .children("td")
        .eq(lastIdx)
        .removeClass("mk-inv-col-net-hide mk-inv-hide-legacy mk-inv-col-amount")
        .addClass("mk-inv-col-tax-head mk-inv-col-tax mk-inv-col-discount")
        .html('<span class="mk-inv-th-label">Chiết khấu (%)</span>');
    }
  }

  function applyHeaderCellClasses($header, $sample) {
    $header.children("td").each(function (idx) {
      var $h = $(this);
      var $b = $sample.children("td").eq(idx);
      $h.removeClass(
        "mk-inv-col-product mk-inv-col-qty mk-inv-col-unit mk-inv-col-unit-head mk-inv-col-price mk-inv-col-amount mk-inv-col-tax mk-inv-col-tax-head mk-inv-col-discount mk-inv-col-note mk-inv-col-drag",
      );
      if (idx === 0) {
        $h.addClass("mk-inv-col-drag");
        return;
      }
      if (!$b.length) {
        return;
      }
      if (
        $b.hasClass("mk-inv-col-unit") ||
        $b.find(".mk-inv-unit-select").length
      ) {
        $h.addClass("mk-inv-col-unit-head mk-inv-col-unit");
      }
      if (
        $b.hasClass("mk-inv-col-product") ||
        $b.find("input.productName, select.mk-inv-product-native").length
      ) {
        $h.addClass("mk-inv-col-product");
      }
      if ($b.hasClass("mk-inv-col-qty") || $b.find("input.qty, .qty").length) {
        $h.addClass("mk-inv-col-qty");
      }
      if (
        $b.hasClass("mk-inv-col-price") ||
        $b.find("input.listPrice").length
      ) {
        $h.addClass("mk-inv-col-price");
      }
      if ($b.hasClass("mk-inv-col-amount") || $b.find(".productTotal").length) {
        $h.addClass("mk-inv-col-amount");
      }
      if (
        $b.hasClass("mk-inv-col-tax") ||
        $b.hasClass("mk-inv-col-discount") ||
        $b.find(
          ".mk-inv-discount-select, .mk-inv-discount-pct, .mk-inv-tax-select",
        ).length
      ) {
        $h.addClass("mk-inv-col-tax-head mk-inv-col-tax mk-inv-col-discount");
      }
      if (
        $b.hasClass("mk-inv-col-note") ||
        $b.find(".lineItemCommentBox, .mk-inv-note-input").length
      ) {
        $h.addClass("mk-inv-col-note");
      }
    });
  }

  function applyModernLineItemColgroup($table) {
    if (!$table || !$table.length) {
      return;
    }
    $table.find("colgroup.mk-inv-colgroup").remove();
    var $colgroup = $('<colgroup class="mk-inv-colgroup"></colgroup>');
    MODERN_LINE_COLGROUP_WIDTHS.forEach(function (w) {
      $colgroup.append($("<col>").attr("style", "width:" + w));
    });
    $table.prepend($colgroup);
  }

  function buildModernLineItemHeader($header) {
    if (!$header || !$header.length) {
      return false;
    }
    $header.empty().addClass("mk-inv-header-row");
    MODERN_LINE_HEADER_COLUMNS.forEach(function (spec) {
      var $td = $("<td></td>").addClass(spec.className);
      if (spec.label) {
        $td.html(renderHeaderLabelHtml(spec.label));
      }
      $header.append($td);
    });
    $header.data("mkOdooHeader", true);
    return true;
  }

  function applyLineItemColgroup($table) {
    if (
      $table.hasClass("mk-inv-luxury-lines") ||
      $table.hasClass("mk-inv-odoo-lines-table")
    ) {
      applyModernLineItemColgroup($table);
      return;
    }
    var $sample = getLineItemSampleRow($table);
    if (!$sample.length) {
      applyModernLineItemColgroup($table);
      return;
    }
    var widths = [];
    $sample.children("td").each(function (idx) {
      if (idx === 0) {
        widths.push("48px");
      } else if (
        $(this).hasClass("mk-inv-col-product") ||
        $(this).find("select.mk-inv-product-native").length
      ) {
        widths.push("26%");
      } else if ($(this).hasClass("mk-inv-col-qty")) {
        widths.push("84px");
      } else if ($(this).hasClass("mk-inv-col-unit")) {
        widths.push("112px");
      } else if ($(this).hasClass("mk-inv-col-price")) {
        widths.push("132px");
      } else if ($(this).hasClass("mk-inv-col-amount")) {
        widths.push("140px");
      } else if ($(this).hasClass("mk-inv-col-tax")) {
        widths.push("96px");
      } else {
        widths.push("auto");
      }
    });
    $table.find("colgroup.mk-inv-colgroup").remove();
    var $colgroup = $('<colgroup class="mk-inv-colgroup"></colgroup>');
    widths.forEach(function (w) {
      $colgroup.append($("<col>").attr("style", "width:" + w));
    });
    $table.prepend($colgroup);
  }

  function renderHeaderLabelHtml(label) {
    if (!label || label === "__hide__") {
      return "";
    }
    var required =
      label === "Tên mục"
        ? '<span class="mk-inv-required" aria-hidden="true">*</span>'
        : "";
    return '<span class="mk-inv-th-label">' + required + label + "</span>";
  }

  function rebuildLineItemHeaderRow($table, $form) {
    var $header = getLineItemHeaderRow($table);
    if (!$header.length) {
      return false;
    }
    // Skip DOM wipe if modern labels already present (avoids thrash during restyles).
    var alreadyModern =
      $header.hasClass("mk-inv-header-row") &&
      $header.find(".mk-inv-th-label").length >= 4 &&
      $header.children("td").length >= MODERN_LINE_HEADER_COLUMNS.length;
    if (!alreadyModern) {
      buildModernLineItemHeader($header);
    } else {
      $header.removeClass("hide").show();
    }
    applyLineItemColgroup($table);
    return true;
  }

  function ensureOdooHeaderColumns($table) {
    rebuildLineItemHeaderRow($table);
  }

  function ensureModernLineItemsTable($form) {
    var $table = $form.find("#lineItemTab");
    if (!$table.length) {
      return;
    }
    $table.addClass("mk-inv-odoo-lines-table mk-inv-luxury-lines");
    ensureOdooHeaderColumns($table);
    applyModernLineItemColgroup($table);
  }

  function wrapMoneyInput($input) {
    if (
      !$input ||
      !$input.length ||
      $input.closest(".mk-inv-money-wrap").length
    ) {
      return;
    }
    $input.addClass("mk-inv-money-input");
    $input.wrap('<div class="mk-inv-money-wrap"></div>');
    var $wrap = $input.parent();
    $wrap.find(".mk-inv-money-suffix").remove();
    if (!$wrap.find(".mk-inv-money-prefix").length) {
      $input.before(
        '<span class="mk-inv-money-prefix" aria-hidden="true">đ</span>',
      );
    }
  }

  function enhanceMoneyCells($row) {
    var $lp = $row.find("input.listPrice").first();
    wrapMoneyInput($lp);
    formatListPriceInput($lp);
    var $total = $row.find(".productTotal").first();
    if ($total.length && !$total.closest(".mk-inv-money-wrap").length) {
      var $wrap = $(
        '<div class="mk-inv-money-wrap mk-inv-money-wrap--total"></div>',
      );
      $total.wrap($wrap);
      $wrap = $total.parent();
      $wrap.find(".mk-inv-money-suffix").remove();
      if (!$wrap.find(".mk-inv-money-prefix").length) {
        $total.before(
          '<span class="mk-inv-money-prefix" aria-hidden="true">đ</span>',
        );
      }
    } else if ($total.length) {
      var $existing = $total.closest(".mk-inv-money-wrap");
      $existing.find(".mk-inv-money-suffix").remove();
      if (!$existing.find(".mk-inv-money-prefix").length) {
        $total.before(
          '<span class="mk-inv-money-prefix" aria-hidden="true">đ</span>',
        );
      }
    }
  }

  function injectUnitSelect($row, $unitTd) {
    if ($unitTd.find(".mk-inv-unit-select").length) {
      return;
    }
    var $sel = $(
      '<select class="mk-inv-unit-select inputElement" title="Đơn vị tính"></select>',
    );
    $sel.append($("<option></option>").attr("value", "").text("— ĐVT —"));
    UNIT_OPTIONS.forEach(function (opt) {
      $sel.append(
        $("<option></option>").attr("value", opt.value).text(opt.label),
      );
    });
    $sel.append(
      $("<option></option>")
        .attr("value", "__search__")
        .text("Tìm kiếm thêm..."),
    );
    $unitTd.empty().append($sel);
    $sel.on("change.mkInvUnit", function () {
      if (this.value !== "__search__") {
        return;
      }
      var custom = window.prompt("Nhập đơn vị:", "Đơn vị");
      if (custom && String(custom).trim()) {
        custom = String(custom).trim();
        ensureUnitOptionOnSelect($sel, custom);
        $sel.val(custom);
      } else {
        $sel.val("");
      }
    });
  }

  function normalizeUnitToken(value) {
    return String(value || "")
      .trim()
      .toLowerCase()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "");
  }

  function ensureUnitOptionOnSelect($sel, unit) {
    unit = String(unit || "").trim();
    if (!$sel || !$sel.length || !unit) {
      return "";
    }
    var token = normalizeUnitToken(unit);
    var resolved = "";
    $sel.find("option").each(function () {
      var val = String($(this).attr("value") || "");
      if (!val || val === "__search__") {
        return;
      }
      var valToken = normalizeUnitToken(val);
      var labelToken = normalizeUnitToken($(this).text());
      if (valToken === token || labelToken.indexOf(token) >= 0) {
        resolved = val;
        return false;
      }
    });
    if (!resolved) {
      $sel
        .find('option[value="__search__"]')
        .before($("<option></option>").attr("value", unit).text(unit));
      resolved = unit;
    }
    return resolved;
  }

  function syncRowUnitFromProduct($row) {
    if (!$row || !$row.length) {
      return;
    }
    var meta = getProductMetaForRow($row);
    var unit = String(meta.unit || "").trim();
    if (!unit) {
      return;
    }
    var $sel = $row.find(".mk-inv-unit-select").first();
    if (!$sel.length) {
      return;
    }
    var resolved = ensureUnitOptionOnSelect($sel, unit);
    if (resolved) {
      $sel.val(resolved);
    }
  }

  function syncRowStockHint($row, $form) {
    var warehouseId =
      $form.find('input[name="mk_warehouse_id"]').val() ||
      $form.data("mkWarehouseId");
    if (!warehouseId || typeof app === "undefined" || !app.request) {
      // No warehouse API — still cap from catalog stock on the selected product.
      var catalogStock = readCatalogStockForRow($row);
      if (catalogStock != null) {
        $row.data("mkAvailableStock", catalogStock);
        paintStockHint($row, catalogStock, true);
        enforceQtyAgainstStock($row, $form, { silent: true });
      }
      return;
    }
    var productId =
      parseInt($row.find("input.selectedModuleId").val(), 10) || 0;
    var productName = ($row.find("input.productName").val() || "").trim();
    var qty = parseMoney($row.find(".qty").val());
    if (!productId && !productName) {
      return;
    }
    var cacheKey = warehouseId + ":" + productId + ":" + productName;
    var $hint = $row.find(".mk-inv-stock-hint");
    if (!$hint.length) {
      $hint = $('<div class="mk-inv-stock-hint"></div>');
      $row.find("input.qty").closest("td").append($hint);
    }
    if (
      $row.data("mkStockCacheKey") === cacheKey &&
      $row.data("mkAvailableStock") != null
    ) {
      paintStockHint(
        $row,
        Number($row.data("mkAvailableStock")),
        parseMoney($row.find(".qty").val()) <=
          Number($row.data("mkAvailableStock")),
      );
      enforceQtyAgainstStock($row, $form, { silent: true });
      return;
    }
    app.request
      .post({
        data: {
          module: "SalesOrder",
          action: "CheckWarehouseStock",
          warehouse_id: warehouseId,
          product_id: [productId],
          product_name: [productName],
          quantity: [qty || 1],
        },
      })
      .then(function (err, res) {
        if (err || !res || !res.lines || !res.lines.length) {
          var fallback = readCatalogStockForRow($row);
          if (fallback != null) {
            $row.data("mkAvailableStock", fallback);
            paintStockHint($row, fallback, true);
            enforceQtyAgainstStock($row, $form);
          }
          return;
        }
        var line = res.lines[0];
        var available = parseMoney(line.available);
        $row.data("mkStockCacheKey", cacheKey);
        $row.data("mkAvailableStock", available);
        enforceQtyAgainstStock($row, $form);
        var qtyNow = parseMoney($row.find(".qty").val());
        paintStockHint($row, available, qtyNow <= available);
      });
  }

  function readCatalogStockForRow($row) {
    var stored = $row.data("mkAvailableStock");
    if (stored != null && stored !== "" && isFinite(Number(stored))) {
      return Math.max(0, Number(stored));
    }
    var $opt = $row.find(".mk-inv-product-select option:selected").first();
    if ($opt.length && $opt.attr("data-stock") != null && $opt.attr("data-stock") !== "") {
      var n = Number($opt.attr("data-stock"));
      if (isFinite(n)) {
        return Math.max(0, n);
      }
    }
    return null;
  }

  function paintStockHint($row, available, ok) {
    var $hint = $row.find(".mk-inv-stock-hint");
    if (!$hint.length) {
      $hint = $('<div class="mk-inv-stock-hint"></div>');
      $row.find("input.qty").closest("td").append($hint);
    }
    var stockLabel = formatQtyShort(available);
    var html =
      "Tồn kho: <strong>" +
      escapeHtml(stockLabel) +
      "</strong>" +
      (ok
        ? ""
        : ' <span class="mk-inv-stock-warn">(tối đa ' +
          escapeHtml(stockLabel) +
          ")</span>");
    $row.data("mkStockCachedHtml", html);
    $hint.html(html);
    $row.toggleClass("mk-inv-row--stock-warn", !ok);
  }

  function getRowAvailableStock($row) {
    return readCatalogStockForRow($row);
  }

  function enforceQtyAgainstStock($row, $form, opts) {
    opts = opts || {};
    if (!$row || !$row.length) {
      return true;
    }
    var max = getRowAvailableStock($row);
    if (max == null || !isFinite(max)) {
      return true;
    }
    max = Math.max(0, max);
    var $qty = $row.find("input.qty, .qty").first();
    if (!$qty.length) {
      return true;
    }
    var qty = parseMoney($qty.val());
    if (!(qty > max)) {
      if (!opts.silent) {
        paintStockHint($row, max, true);
      }
      return true;
    }
    // Báo giá / Đơn hàng: chỉ cảnh báo tồn, không ép SL về 0 (mất tiền dòng).
    var moduleName = "";
    if ($form && $form.length) {
      moduleName = String($form.find('[name="module"]').val() || "");
    }
    if (moduleName === "Quotes" || moduleName === "SalesOrder") {
      paintStockHint($row, max, false);
      return true;
    }
    var clamped = max;
    if (Math.abs(clamped - Math.round(clamped)) < 0.001) {
      clamped = Math.round(clamped);
    }
    $qty.val(clamped);
    paintStockHint($row, max, false);
    if (!opts.silent) {
      triggerLineRecalc($row, $form);
    }
    return false;
  }

  function injectPriceColumn($row) {
    var $existing = $row.find("input.listPrice").first();
    if ($existing.length) {
      return $existing.closest("td");
    }
    var $amountTd = $row.find(".productTotal").closest("td");
    if (!$amountTd.length) {
      return $();
    }
    var rowNo =
      $row.find(".rowNumber").val() || $row.attr("data-row-num") || "";
    var $priceTd = $('<td class="mk-inv-col-price"></td>');
    var $input = $(
      '<input type="text" class="listPrice smallInputBox inputElement" data-rule-required="true" data-rule-positive="true" value="0" />',
    );
    if (rowNo !== "") {
      $input.attr("id", "listPrice" + rowNo).attr("name", "listPrice" + rowNo);
    }
    $priceTd.append($input);
    $priceTd.insertBefore($amountTd);
    return $priceTd;
  }

  function ensureModernPriceColumn($row) {
    var $priceTd = injectPriceColumn($row);
    if ($priceTd.length) {
      $priceTd.removeClass("mk-inv-hide-legacy mk-inv-col-net-hide");
    }
    return $priceTd;
  }

  function getRowNumberValue($row) {
    return String(
      $row.find(".rowNumber").val() || $row.attr("data-row-num") || "",
    ).trim();
  }

  function paintPriceCell($row) {
    var $priceTd = $row.find("input.listPrice").closest("td").first();
    if (!$priceTd.length) {
      $priceTd = ensureModernPriceColumn($row);
    }
    if (!$priceTd.length) {
      return $();
    }
    $priceTd
      .addClass("mk-inv-col-price")
      .removeClass("mk-inv-hide-legacy mk-inv-col-net-hide")
      .css({ display: "", visibility: "", opacity: "" });

    var $input = $priceTd.find("input.listPrice").first();
    if (!$input.length) {
      var rowNo = getRowNumberValue($row);
      $input = $(
        '<input type="text" class="listPrice smallInputBox inputElement mk-inv-money-input" data-rule-required="true" data-rule-positive="true" value="0" />',
      );
      if (rowNo) {
        $input
          .attr("id", "listPrice" + rowNo)
          .attr("name", "listPrice" + rowNo);
      }
      $priceTd.empty().append($input);
    }
    $input
      .removeClass("mk-inv-hide-legacy")
      .css({ display: "", visibility: "", opacity: "" });
    $priceTd
      .find(".individualTaxContainer, .taxDivContainer, .individualDiscount")
      .addClass("mk-inv-hide-legacy");
    return $priceTd;
  }

  function paintAmountCell($row, $form) {
    var $taxTd = $row
      .find(".mk-inv-discount-select, .mk-inv-tax-select")
      .closest("td")
      .first();
    var $amountTd = $row.find("> td.mk-inv-col-amount").first();
    if ($amountTd.length && $taxTd.length && $amountTd[0] === $taxTd[0]) {
      $amountTd = $();
    }
    if (!$amountTd.length) {
      var $pt = $row.find(".productTotal").first();
      if ($pt.length) {
        $amountTd = $pt.closest("td").first();
        if ($taxTd.length && $amountTd[0] === $taxTd[0]) {
          $amountTd = $();
        }
      }
    }
    if (!$amountTd.length) {
      var rowNo = getRowNumberValue($row);
      $amountTd = $('<td class="mk-inv-col-amount"></td>');
      var $total = $('<div class="productTotal">0</div>');
      if (rowNo) {
        $total.attr("id", "productTotal" + rowNo);
      }
      $amountTd.append($total);
      if ($taxTd.length) {
        // Keep visual order: ... tax | price | amount
        var $priceTd = $row.find("input.listPrice").closest("td").first();
        if ($priceTd.length) {
          $amountTd.insertAfter($priceTd);
        } else {
          $amountTd.insertAfter($taxTd);
        }
      } else {
        $row.append($amountTd);
      }
    }
    $amountTd
      .addClass("mk-inv-col-amount")
      .removeClass("mk-inv-hide-legacy mk-inv-col-net-hide mk-inv-col-tax")
      .css({ display: "", visibility: "", opacity: "" });
    $amountTd
      .children()
      .not(
        ".productTotal, .mk-inv-money-wrap, .mk-inv-line-del, .mk-inv-amount-wrap",
      )
      .addClass("mk-inv-hide-legacy");

    var $total = $amountTd.find(".productTotal").first();
    if (!$total.length) {
      // Recover productTotal that may still sit in another cell.
      $total = $row.find(".productTotal").first();
      if ($total.length && $total.closest("td")[0] !== $amountTd[0]) {
        var $host = $total.closest(".mk-inv-money-wrap").length
          ? $total.closest(".mk-inv-money-wrap")
          : $total;
        $amountTd.prepend($host.detach());
        $total = $amountTd.find(".productTotal").first();
      }
    }
    if (!$total.length) {
      var rowNo2 = getRowNumberValue($row);
      $total = $('<div class="productTotal">0</div>');
      if (rowNo2) {
        $total.attr("id", "productTotal" + rowNo2);
      }
      $amountTd.prepend($total);
    }
    $total
      .removeClass("mk-inv-hide-legacy")
      .css({ display: "", visibility: "", opacity: "" });
    $amountTd.find(".mk-inv-money-wrap").css({ display: "", visibility: "" });
    syncRowAmounts($row, $form);
    return $amountTd;
  }

  function rowTaxSelectIsActive($row) {
    if (!$row || !$row.length) {
      return false;
    }
    var active = false;
    $row
      .find(
        ".mk-inv-tax-select, .mk-inv-discount-select, .mk-inv-discount-custom, .mk-inv-discount-pct, .mk-inv-note-input, .lineItemCommentBox",
      )
      .each(function () {
        var $sel = $(this);
        if (
          document.activeElement === this ||
          $sel.data("mkTaxOpen") ||
          $sel.is(":focus")
        ) {
          active = true;
          return false;
        }
      });
    return active;
  }

  function normalizeModernLineItemRow($row, $form) {
    if (isTemplateLineItemRow($row)) {
      return;
    }
    if (rowTaxSelectIsActive($row)) {
      return;
    }

    var $drag = $row.children("td").first();
    var $product = $row
      .find("input.productName, select.mk-inv-product-native")
      .closest("td")
      .first();
    var $qty = $row.find("input.qty, .qty").closest("td").first();
    var $unit = $row.find("> td.mk-inv-col-unit").first();
    if (!$unit.length) {
      $unit = $row.find(".mk-inv-unit-select").closest("td").first();
    }
    var $price = paintPriceCell($row);
    var $tax = $row
      .find(".mk-inv-discount-select, .mk-inv-discount-pct, .mk-inv-tax-select")
      .closest("td")
      .first();
    if (!$tax.length) {
      $tax = $row.find("> td.mk-inv-col-tax, > td.mk-inv-col-discount").first();
    }
    var $amount = paintAmountCell($row, $form);
    var $note = $row.find("> td.mk-inv-col-note").first();
    if (!$note.length) {
      $note = $row
        .find(".lineItemCommentBox, .mk-inv-note-input")
        .closest("td")
        .first();
    }

    var ordered = [];
    // Đơn giá → Chiết khấu → Thành tiền → Ghi chú
    [$drag, $product, $qty, $unit, $price, $tax, $amount, $note].forEach(
      function ($td) {
        if (!$td || !$td.length) {
          return;
        }
        var el = $td[0];
        if (ordered.indexOf(el) === -1) {
          ordered.push(el);
        }
      },
    );

    ordered.forEach(function (el) {
      $row.append(el);
    });

    $row.children("td").each(function () {
      var $td = $(this);
      if (ordered.indexOf(this) === -1) {
        $td.addClass("mk-inv-col-net-hide mk-inv-hide-legacy");
      }
    });

    tagLineItemColumnClasses($row);
    enhanceMoneyCells($row);
    syncRowAmounts($row, $form);
  }

  function tagLineItemColumnClasses($row) {
    $row.find("input.productName").closest("td").addClass("mk-inv-col-product");
    $row.find("input.qty, .qty").closest("td").addClass("mk-inv-col-qty");
    $row.find("input.listPrice").closest("td").addClass("mk-inv-col-price");
    $row.find(".productTotal").closest("td").addClass("mk-inv-col-amount");
    $row
      .find(
        ".mk-inv-discount-select, .mk-inv-discount-pct, .mk-inv-tax-select",
      )
      .closest("td")
      .addClass("mk-inv-col-tax mk-inv-col-discount");
    $row
      .find(".lineItemCommentBox, .mk-inv-note-input")
      .closest("td")
      .addClass("mk-inv-col-note");
  }

  function ensureOdooRowColumns($row, $form) {
    if (isTemplateLineItemRow($row) || $row.hasClass("mk-inv-section-row")) {
      return;
    }
    if (!$form || !$form.length) {
      $form = $row.closest("form");
    }
    if (isLineItemRestylePaused($form) || rowTaxSelectIsActive($row)) {
      return;
    }

    $row.removeData("mkTaxAmountReordered");

    // Remove legacy extra tax columns (keep first discount/tax cell)
    var $taxCols = $row.find("> td.mk-inv-col-tax, > td.mk-inv-col-discount");
    if ($taxCols.length > 1) {
      $taxCols.slice(1).remove();
    }

    var $qtyTd = $row.find("input.qty, .qty").first().closest("td");
    var $unitTd = $row.find("> td.mk-inv-col-unit").first();
    if ($qtyTd.length && !$unitTd.length) {
      $qtyTd.after('<td class="mk-inv-col-unit"></td>');
      $unitTd = $row.find("> td.mk-inv-col-unit").first();
    }
    if ($unitTd.length) {
      $unitTd.removeClass("mk-inv-hide-legacy");
      injectUnitSelect($row, $unitTd);
      syncRowUnitFromProduct($row);
    }

    ensureModernPriceColumn($row);

    injectTaxDropdown($row, $form);
    injectNoteColumn($row, $form);
    syncRowTaxPill($row, $form);
    normalizeModernLineItemRow($row, $form);
    injectProductDropdown($row, $form);

    var $tools = $row.find("> td:first-child");
    $tools.addClass("mk-inv-col-drag");
    $tools
      .find('img[src*="drag"]')
      .closest("a, span")
      .removeClass("mk-inv-hide-legacy");

    ensureLineDeleteButton($row, $form);

    $row
      .find("input.productName")
      .attr("placeholder", "Chọn sản phẩm từ dropdown");
    $row.find(".priceBookPopup").addClass("mk-inv-hide-legacy");
    $row.find(".itemNameDiv .lineItemPopup").addClass("mk-inv-hide-legacy");
    syncRowStockHint($row, $form);
  }

  function refreshLineItemRow($row, $form) {
    if (!$row || !$row.length || isTemplateLineItemRow($row)) {
      return;
    }
    if (!$form || !$form.length) {
      $form = $row.closest("form");
    }
    if (isLineItemRestylePaused($form) || rowTaxSelectIsActive($row)) {
      return;
    }
    neutralizeLegacyProductInput($row);
    ensureOdooRowColumns($row, $form);
    ensureQtyEditable($row);
  }

  function markInventoryUiReady() {
    if (typeof document === "undefined" || !document.documentElement) {
      return;
    }
    var root = document.documentElement;
    if (root.classList.contains("mk-inv-ui-ready")) {
      return;
    }
    root.classList.add("mk-inv-ui-ready");
    root.classList.add("mk-quote-create-enhanced");
    root.classList.add("mk-so-create-styled");
  }

  function isAnyTaxSelectOpen($scope) {
    var $root = $scope && $scope.length ? $scope : $(document);
    var open = false;
    $root.find("select.mk-inv-tax-select").each(function () {
      var $sel = $(this);
      if (
        document.activeElement === this ||
        $sel.data("mkTaxOpen") ||
        $sel.is(":focus")
      ) {
        open = true;
        return false;
      }
      return true;
    });
    return open;
  }

  function restyleLineItemRows($form) {
    if (!$form || !$form.length) {
      return;
    }
    // Never restyle while user is searching/selecting a product or tax — prevents stutter.
    if (
      isLineItemRestylePaused($form) ||
      isAnyProductSelectOpen($form) ||
      isAnyProductSelectOpen($(document.body)) ||
      isAnyTaxSelectOpen($form) ||
      isAnyTaxSelectOpen($(document.body)) ||
      isEditingLineField($form)
    ) {
      return;
    }
    var $table = $form.find("#lineItemTab");
    if (!$table.length) {
      return;
    }
    $table.addClass("mk-inv-odoo-lines-table mk-inv-luxury-lines");
    $table.find("tr.lineItemRow").each(function () {
      var $row = $(this);
      var $lp = $row.find("input.listPrice").first();
      // Protect prices loaded from DB so catalog/tier integrate cannot zero them.
      if ($lp.length && parseMoney($lp.val()) > 0) {
        $lp.attr("data-mk-price-manual", "1");
      }
      // Seed discount cache from DB/totals before UI inject paints "0%"
      if (!$row.data("mkDiscUserSet")) {
        var seeded = readLegacyDiscountPercent($row);
        if (seeded > 0) {
          $row.data("mkDiscountPct", seeded);
        }
      }
      refreshLineItemRow($row, $form);
      formatListPriceInput($row.find("input.listPrice").first(), true);
    });
    ensureOdooHeaderColumns($table);
    applyLineItemColgroup($table);
    initTotalsOdoo($form);
    syncTotalsDisplay($form);
    syncAllRowAmounts($form);
    syncLineDeleteVisibility($form);
    syncCreditTermsVisibility($form);
    syncAllProductSelectDisplays($form);
    formatAllListPrices($form, true);
    markInventoryUiReady();
  }

  function scheduleLineItemsRestyle($form, delays) {
    if (!$form || !$form.length) {
      return;
    }
    if (!delays) {
      // Keep light: heavy multi-delay chains caused SO Edit lag.
      delays = [0, 350, 1000];
    }
    delays.forEach(function (ms) {
      setTimeout(function () {
        if (
          isLineItemRestylePaused($form) ||
          !$form.closest("body").length ||
          !$form.find("#lineItemTab").length
        ) {
          return;
        }
        restyleLineItemRows($form);
      }, ms);
    });
  }

  function bindLineDeleteSync($form) {
    if (!$form || !$form.length || $form.data("mkInvDelSync")) {
      return;
    }
    $form.data("mkInvDelSync", true);
    $form.on(
      "pointerdown.mkInvDel",
      ".mk-inv-line-del, .mk-inv-del-btn",
      function () {
        pauseLineItemRestyle($form, 900);
      },
    );
    $form.on(
      "click.mkInvDelSync",
      ".mk-inv-line-del, .mk-inv-del-btn, .mk-inv-del-icon",
      function (e) {
        e.preventDefault();
        e.stopPropagation();
        performLineItemDelete($form, $(this));
      },
    );
  }

  function bindInventoryRestyleHooks($form) {
    if (typeof app === "undefined" || !app.event) {
      return;
    }

    registerPostEditViewRestyleHook();

    if ($form.data("mkInvRestyleHooks")) {
      return;
    }
    $form.data("mkInvRestyleHooks", true);
    bindLineDeleteSync($form);

    app.event.on("post.lineItem.New", function (event, newLineItem) {
      var $targetForm = $form;
      if (!$targetForm.find("#lineItemTab").length) {
        $targetForm = detectInventoryEditForm();
      }
      if (!$targetForm.length || !$targetForm.find("#lineItemTab").length) {
        return;
      }
      if (
        $targetForm.data("mkInvSkipPostLineHook") ||
        $targetForm.data("mkInvAddingLine")
      ) {
        return;
      }
      handleNewLineItemRow($targetForm, newLineItem);
    });
  }

  function registerPostEditViewRestyleHook() {
    if (
      window.__mkInvRestyleHooksBound ||
      typeof app === "undefined" ||
      !app.event
    ) {
      return;
    }
    window.__mkInvRestyleHooksBound = true;
    app.event.on("post.editView.load", function (event, container) {
      var $targetForm = $(container).closest("form");
      if (!$targetForm.length) {
        $targetForm = $(container)
          .find('form#EditView, form[name="EditView"]')
          .first();
      }
      if (!$targetForm.length && $("#mkQtFormHost").length) {
        $targetForm = $("#mkQtFormHost")
          .find('form#EditView, form[name="EditView"]')
          .first();
      }
      if (!$targetForm.length && $("#mkSoFormHost").length) {
        $targetForm = $("#mkSoFormHost")
          .find('form#EditView, form[name="EditView"]')
          .first();
      }
      if ($targetForm.length && $targetForm.find("#lineItemTab").length) {
        scheduleLineItemsRestyle($targetForm, [0, 150, 450, 900]);
      }
    });
  }

  function detectInventoryEditForm() {
    var module = $("body").attr("data-module");
    if (module !== "Quotes" && module !== "SalesOrder") {
      return $();
    }
    var $form = $("#mkQtFormHost, #mkSoFormHost")
      .find('form#EditView, form[name="EditView"]')
      .first();
    if (!$form.length) {
      $form = $(
        'form#EditView.recordEditView, form[name="edit"].recordEditView',
      ).first();
    }
    if ($form.length && $form.find("#lineItemTab").length) {
      return $form;
    }
    return $();
  }

  function autoBootstrapInventoryOdooUi() {
    var $form = detectInventoryEditForm();
    if (!$form.length) {
      return;
    }
    if (!$form.hasClass("mk-inv-form-odoo")) {
      init($form, { hideDescriptionBlock: true });
    } else {
      scheduleLineItemsRestyle($form);
    }
  }

  $(function () {
    registerPostEditViewRestyleHook();
    autoBootstrapInventoryOdooUi();
    scheduleLineItemsRestyle(
      detectInventoryEditForm(),
      [0, 60, 180, 360],
    );
    // Reveal early; restyle continues after show so first paint isn't blank 2–3s.
    setTimeout(markInventoryUiReady, 180);
    setTimeout(markInventoryUiReady, 600);
    if (!window.__mkInvAddLineDocBound) {
      window.__mkInvAddLineDocBound = true;
      $(document).on(
        "click.mkInvAddLine",
        "#addProductsServices",
        function (e) {
          var $btn = $(this);
          // Button-level handler owns creation once initialized.
          if ($btn.data("mkInvOdooAddBound")) {
            return;
          }
          e.preventDefault();
          e.stopPropagation();
          var $f = detectInventoryEditForm();
          if (!$f.length) {
            $f = $btn.closest("form");
          }
          createInventoryLineItemRow($f, $btn);
        },
      );
    }
  });

  function createInventoryLineItemRow($form, $btn) {
    if (!$form || !$form.length) {
      return null;
    }
    if ($form.data("mkInvAddingLine")) {
      return null;
    }
    $form.data("mkInvAddingLine", true);
    if ($btn && $btn.length) {
      $btn.addClass("is-busy").prop("disabled", true);
    }
    var newLineItem = null;
    try {
      var moduleName =
        $form.find('[name="module"]').val() ||
        $("body").attr("data-module") ||
        "SalesOrder";
      var inst = null;
      if (typeof Inventory_Edit_Js !== "undefined") {
        try {
          inst = Inventory_Edit_Js.getInstanceByModuleName(moduleName);
        } catch (ignore) {
          inst = null;
        }
      }
      if (!inst || typeof inst.getNewLineItem !== "function") {
        var $legacy = $form.find("#addProduct").first();
        if ($legacy.length) {
          $legacy.trigger("click");
          setTimeout(function () {
            var $last = $form
              .find("#lineItemTab tr.lineItemRow")
              .not(".hide, .lineItemCloneCopy")
              .last();
            styleNewLineItemFast($form, $last);
            unlockAddLineButton($form, $btn);
          }, 40);
          return null;
        }
        unlockAddLineButton($form, $btn);
        return null;
      }
      if (!$btn || !$btn.length) {
        $btn = $form.find("#addProductsServices").first();
      }
      if (!$btn.attr("data-module-name")) {
        $btn.attr("data-module-name", "ProductsServices");
      }
      newLineItem = inst.getNewLineItem({ currentTarget: $btn });
      if (!newLineItem || !newLineItem.length) {
        unlockAddLineButton($form, $btn);
        return null;
      }
      var $holder =
        inst.lineItemsHolder && inst.lineItemsHolder.length
          ? inst.lineItemsHolder
          : $form.find("#lineItemTab");
      newLineItem.appendTo($holder);
      newLineItem.find("input.productName").addClass("autoComplete");
      newLineItem
        .find(".ignore-ui-registration")
        .removeClass("ignore-ui-registration");

      // Style the new row immediately (snappy). Skip full-table restyle loops.
      styleNewLineItemFast($form, newLineItem);

      // Let other listeners know, but skip our own heavy post.lineItem.New handler.
      $form.data("mkInvSkipPostLineHook", true);
      if (typeof app !== "undefined" && app.event) {
        app.event.trigger("post.lineItem.New", newLineItem);
      }
      $form.removeData("mkInvSkipPostLineHook");

      if (typeof inst.checkLineItemRow === "function") {
        inst.checkLineItemRow();
      }
      // Blur any focus that Inventory core put on the new row (causes jump-to-bottom).
      try {
        var ae = document.activeElement;
        if (
          ae &&
          newLineItem[0] &&
          newLineItem[0].contains(ae) &&
          typeof ae.blur === "function"
        ) {
          ae.blur();
        }
      } catch (ignoreBlur) {
        /* ignore */
      }
      // Do NOT registerLineItemAutoComplete — product picker is Select2; autocomplete adds lag.
    } catch (err) {
      if (window.console && console.warn) {
        console.warn("[MkInventoryOdooEdit] add line failed", err);
      }
    }
    unlockAddLineButton($form, $btn);
    return newLineItem;
  }

  function unlockAddLineButton($form, $btn) {
    setTimeout(function () {
      if ($form && $form.length) {
        $form.removeData("mkInvAddingLine");
      }
      if ($btn && $btn.length) {
        $btn.removeClass("is-busy").prop("disabled", false);
      }
    }, 120);
  }

  /** Fast path: only touch the new row + totals once (no multi-timeout full restyle). */
  function styleNewLineItemFast($form, newLineItem) {
    if (!$form || !$form.length) {
      return;
    }
    var $row =
      newLineItem && $(newLineItem).length
        ? $(newLineItem)
        : $form
            .find("#lineItemTab tr.lineItemRow")
            .not(".hide, .lineItemCloneCopy")
            .last();
    if (!$row.length || isTemplateLineItemRow($row)) {
      return;
    }
    var $table = $form.find("#lineItemTab");
    $table.addClass("mk-inv-odoo-lines-table mk-inv-luxury-lines");
    refreshLineItemRow($row, $form);
    syncTotalsDisplay($form);
    // One short follow-up only if product Select2 did not mount yet.
    setTimeout(function () {
      if (!$row.closest("body").length) {
        return;
      }
      var $sel = $row.find("select.mk-inv-product-select").first();
      if (!$sel.length || !$sel.data("select2")) {
        refreshLineItemRow($row, $form);
      }
      if (rowHasSelectedProduct($row)) {
        syncProductSelectDisplay($row, $form);
      }
    }, 80);
  }

  function handleNewLineItemRow($form, newLineItem) {
    if (!$form || !$form.length) {
      return;
    }
    if ($form.data("mkInvSkipPostLineHook")) {
      return;
    }
    // Debounce overlapping handlers from Inventory + our add button.
    var token = ($form.data("mkInvNewLineToken") || 0) + 1;
    $form.data("mkInvNewLineToken", token);
    styleNewLineItemFast($form, newLineItem);
    setTimeout(function () {
      if ($form.data("mkInvNewLineToken") !== token) {
        return;
      }
      var $row =
        newLineItem && $(newLineItem).length
          ? $(newLineItem)
          : $form
              .find("#lineItemTab tr.lineItemRow")
              .not(".hide, .lineItemCloneCopy")
              .last();
      if (
        $row.length &&
        !$row.find("select.mk-inv-product-select").data("select2")
      ) {
        refreshLineItemRow($row, $form);
      }
    }, 100);
  }

  function setFormattedText($el, formatted) {
    if ($el.length && $el.text() !== formatted) {
      $el.text(formatted);
    }
  }

  function ensureTaxTotalsRowVisible($result, taxPct) {
    if (!$result || !$result.length) {
      return;
    }
    // BA VAT-included: always hide group tax row
    var $taxRow = $result.find("#group_tax_row");
    if ($taxRow.length) {
      $taxRow.addClass("mk-inv-totals-hide hide").hide();
    }
  }

  function ensureEditableGrandTotal($form, $result) {
    if (!$result || !$result.length) {
      return;
    }
    var $grandCell = $result.find("#grandTotal, .grandTotal").first();
    if (!$grandCell.length) {
      return;
    }
    var $tr = $grandCell.closest("tr");
    if ($tr.find(".mk-inv-grand-manual-input").length) {
      return;
    }
    var $input = $(
      '<input type="text" class="mk-inv-grand-manual-input inputElement" inputmode="numeric" title="Có thể sửa tay tổng cộng" aria-label="Tổng cộng" />',
    );
    var current = readAmountRaw($grandCell, $form.find("#total"));
    $input.val(formatVndNumber(current));
    $grandCell.css({ display: "none" });
    $grandCell.after($input);
    $input.on("focus.mkInvGrand", function () {
      $(this).val(String(Math.round(parseMoney($(this).val())) || ""));
    });
    $input.on("change.mkInvGrand focusout.mkInvGrand", function () {
      var val = parseMoney($(this).val());
      setManualGrandTotal($form, val);
      $(this).val(formatVndNumber(val));
    });
  }

  function syncTotalsDisplay($form) {
    if ($form.data("mkInvSyncingTotals")) {
      return;
    }
    $form.data("mkInvSyncingTotals", true);

    var $result = $form.find("#lineItemResult");
    if (!$result.length) {
      $form.data("mkInvSyncingTotals", false);
      return;
    }

    revealCoreTotalsRows($result);
    ensureEditableGrandTotal($form, $result);
    ensureGroupTaxMode($form);

    var grossTotal = sumLineGrossTotal($form);
    var afterCkTotal = sumLinePreTax($form);
    var discountTotal = grossTotal - afterCkTotal;
    var grand = afterCkTotal;

    $form.find("#adjustment, input[name='adjustment']").val(0);

    // Tổng Cộng (SL × Đơn giá)
    writeAmountDisplay($result.find("#preTaxTotal"), grossTotal);
    // Chiết Khấu
    writeAmountDisplay($result.find("#mk_discount_total_display"), discountTotal);
    writeAmountDisplay($result.find("#tax_final"), 0);
    // Tổng Thanh Toán
    writeAmountDisplay($result.find("#grandTotal, .grandTotal"), grand);

    $result.find("#pre_tax_total").val(grossTotal);
    $form.find('#total, input[name="total"]').val(grand);
    $form.find('#subtotal, input[name="subtotal"]').val(grossTotal);
    $form.find(".groupTaxTotal").first().val(0);
    $result.find("#tax_final").attr("data-raw", 0);

    $form.data("mkInvSyncingTotals", false);
  }

  function watchTotalsAndLines($form) {
    if ($form.data("mkInvTotalsWatch")) {
      return;
    }
    $form.data("mkInvTotalsWatch", true);

    var $result = $form.find("#lineItemResult");
    var _mutTimer = null;
    ["#preTaxTotal", "#tax_final", "#grandTotal"].forEach(function (sel) {
      var el = $result.find(sel)[0];
      if (!el || typeof MutationObserver === "undefined") {
        return;
      }
      var obs = new MutationObserver(function () {
        if (_mutTimer) {
          clearTimeout(_mutTimer);
        }
        _mutTimer = setTimeout(function () {
          _mutTimer = null;
          syncTotalsDisplay($form);
          if (isAnyTaxSelectOpen($form)) {
            return;
          }
          $form.find("tr.lineItemRow").each(function () {
            var $r = $(this);
            syncRowAmounts($r, $form);
            // Keep dropdown stable even if legacy DOM updates happen.
            syncRowTaxPill($r, $form);
          });
        }, 120);
      });
      obs.observe(el, { childList: true, characterData: true, subtree: true });
    });

    var _realtimeTimer = null;
    function scheduleRealtimeSync() {
      if (_realtimeTimer) {
        clearTimeout(_realtimeTimer);
      }
      _realtimeTimer = setTimeout(function () {
        _realtimeTimer = null;
        $form.data("mkInvSyncingTotals", true);
        var preTaxSum = 0;
        $form.find("tr.lineItemRow").each(function () {
          var $r = $(this);
          applyLineTaxZero($r, $form);
          var lineTotal = calcLineRowTotal($r, $form);
          preTaxSum += lineTotal;
          var $pt = $r.find(".productTotal");
          if ($pt.length) {
            $pt.data("mkRawAmount", lineTotal);
            writeAmountDisplay($pt, lineTotal);
          }
        });
        var grand = preTaxSum;
        if (isManualGrandTotal($form)) {
          grand = readAmountRaw(
            $form.find("#grandTotal, .grandTotal"),
            $form.find("#total"),
          );
          var $mi = $form.find(".mk-inv-grand-manual-input");
          if ($mi.length) {
            grand = parseMoney($mi.val());
          }
          $form.find("#adjustment, input[name='adjustment']").val(grand - preTaxSum);
        } else {
          $form.find("#adjustment, input[name='adjustment']").val(0);
        }
        var $result = $form.find("#lineItemResult");
        if ($result.length) {
          revealCoreTotalsRows($result);
          writeAmountDisplay($result.find("#netTotal, .netTotal"), preTaxSum);
          $result.find('#subtotal, input[name="subtotal"]').val(preTaxSum);
          writeAmountDisplay($result.find("#preTaxTotal"), preTaxSum);
          $result.find("#pre_tax_total").val(preTaxSum);
          writeAmountDisplay($result.find("#tax_final"), 0);
          $form.find(".groupTaxTotal").first().val(0);
          writeAmountDisplay($result.find("#grandTotal, .grandTotal"), grand);
          $form.find('#total, input[name="total"]').val(grand);
          var $manual = $result.find(".mk-inv-grand-manual-input");
          if ($manual.length && document.activeElement !== $manual[0] && !isManualGrandTotal($form)) {
            $manual.val(formatVndNumber(grand));
          }
          ensureTaxTotalsRowVisible($result, 0);
        }
        setTimeout(function () {
          $form.data("mkInvSyncingTotals", false);
        }, 50);
      }, 100);
    }

    $form.on(
      "focusout.mkInvTot change.mkInvTot",
      ".qty, .listPrice, .taxPercentage, .groupTaxPercentage, .mk-inv-discount-select, .mk-inv-discount-custom, .mk-inv-discount-pct, .discount_percentage",
      function () {
        clearManualGrandTotal($form);
        var $row = $(this).closest("tr.lineItemRow");
        if ($row.length) {
          ensureQtyEditable($row);
          syncRowAmounts($row, $form);
        }
        setTimeout(function () {
          if (
            isLineItemRestylePaused($form) ||
            isAnyTaxSelectOpen($form) ||
            isEditingLineField($form)
          ) {
            syncTotalsDisplay($form);
            return;
          }
          syncTotalsDisplay($form);
        }, 60);
      },
    );

    $form.on(
      "input.mkInvTotRealtime keyup.mkInvTotRealtime change.mkInvTotRealtime",
      ".qty, .listPrice, .mk-inv-discount-select, .mk-inv-discount-custom, .mk-inv-discount-pct",
      function () {
        clearManualGrandTotal($form);
        var $el = $(this);
        var $row = $el.closest("tr.lineItemRow");
        var isPrice = $el.is("input.listPrice, .listPrice");
        if (isPrice) {
          // Preserve typed unit price — do not let auto-tier overwrite mid-edit.
          $el.attr("data-mk-price-manual", "1");
          if ($row.length) {
            var $qty = $row.find("input.qty, .qty").first();
            if ($qty.length && !(parseMoney($qty.val()) > 0) && parseMoney($el.val()) > 0) {
              $qty.val(1);
            }
          }
        }
        if ($row.length) {
          syncRowAmounts($row, $form);
        }
        scheduleRealtimeSync();
        // Retier only when qty changes (auto bracket from total), never while typing price.
        if (!isPrice && !$form.data("mkInvApplyingTier")) {
          scheduleInvoiceTierPricing($form, 220);
        }
      },
    );

    $form.on(
      "focusout.mkInvStockCap change.mkInvStockCap",
      "input.qty, .qty",
      function () {
        var $row = $(this).closest("tr.lineItemRow");
        if (!$row.length) {
          return;
        }
        enforceQtyAgainstStock($row, $form);
        syncRowStockHint($row, $form);
      },
    );

    function bindDirectPriceEvents() {
      $form.find("input.listPrice").each(function () {
        var $el = $(this);
        if ($el.data("mkDirectPriceBound")) {
          return;
        }
        $el.data("mkDirectPriceBound", true);
        $el.on(
          "input.mkPriceDirect keyup.mkPriceDirect change.mkPriceDirect",
          function () {
            scheduleRealtimeSync();
          },
        );
        $el.on("focusout.mkPriceDirect", function () {
          var $row = $el.closest("tr.lineItemRow");
          if ($row.length) {
            triggerLineRecalc($row, $form);
            setTimeout(function () {
              scheduleRealtimeSync();
            }, 200);
          }
        });
      });
    }
    bindDirectPriceEvents();
    $form.data("mkBindDirectPriceEvents", bindDirectPriceEvents);
    $form.data("mkScheduleRealtimeSync", scheduleRealtimeSync);

    // Event-driven sync only (input/change). Avoid 500ms full-table polls — major SO Edit lag source.
    if (!$form.data("mkInvIdleTotalsTick")) {
      $form.data("mkInvIdleTotalsTick", true);
      var _lastPriceSnapshot = {};
      var pollMs = 2500;
      setInterval(function () {
        if (
          typeof document !== "undefined" &&
          document.hidden
        ) {
          return;
        }
        if (
          isLineItemRestylePaused($form) ||
          isAnyTaxSelectOpen($form) ||
          isEditingLineField($form)
        ) {
          return;
        }
        var changed = false;
        $form.find("tr.lineItemRow").each(function () {
          var $r = $(this);
          var rowId = $r.attr("id") || $r.index();
          var curPrice = $r.find(".listPrice").val() || "";
          var curQty = $r.find(".qty").val() || "";
          var key = curQty + "|" + curPrice;
          if (_lastPriceSnapshot[rowId] !== key) {
            _lastPriceSnapshot[rowId] = key;
            changed = true;
          }
        });
        if (changed) {
          scheduleRealtimeSync();
        }
      }, pollMs);
    }

    $form.on("mkSoWarehouseSelected.mkInv", function (_e, warehouse) {
      if (warehouse && warehouse.id) {
        $form.data("mkWarehouseId", warehouse.id);
      }
      $form.find("tr.lineItemRow").each(function () {
        $(this).removeData(
          "mkStockCacheKey mkStockCachedHtml mkAvailableStock",
        );
        syncRowStockHint($(this), $form);
      });
    });

    $form.on("input.mkInvDesc", ".lineItemCommentBox", function () {
      syncProductDesc($(this).closest("tr"));
    });

    $form.on("change.mkInvTaxDd", ".mk-inv-tax-select", function () {
      // Amount sync is handled by injectTaxDropdown's change handler.
      // Do not restyle — rebuilding rows closes the native <select> immediately.
      setTimeout(function () {
        $form.data("mkInvSyncingTotals", false);
        scheduleRealtimeSync();
      }, 80);
    });

    if (typeof app !== "undefined" && app.event) {
      app.event.on("post.LineItemPopupSelection.click", function () {
        setTimeout(function () {
          restyleLineItemRows($form);
          syncTotalsDisplay($form);
          var fn = $form.data("mkBindDirectPriceEvents");
          if (fn) {
            fn();
          }
        }, 200);
      });
    }
  }

  function polishLineItemsShell($form) {
    var $lineBlock = $form.find(".mk-inv-lineitems-odoo");
    var $container = $lineBlock.find(".lineitemTableContainer");
    if (
      $container.length &&
      !$container.parent().hasClass("mk-inv-lines-card")
    ) {
      $container.wrap('<div class="mk-inv-lines-card"></div>');
    }
    // Keep header actions (pinned add button); only remove legacy footer action bars.
    $form
      .find(".mk-inv-line-actions")
      .not(".mk-inv-line-header-actions")
      .remove();
  }

  function initOdooTabs($lineBlock) {
    if ($lineBlock.find(".mk-inv-odoo-tabs").length) {
      return;
    }
    var $tabs = $(
      '<div class="mk-inv-odoo-tabs" role="tablist">' +
        '<button type="button" class="mk-inv-odoo-tab is-active" role="tab">Chi tiết đơn hàng</button>' +
        "</div>",
    );
    $lineBlock.find(".lineitemTableContainer").before($tabs);
  }

  function initLineActionLinks() {
    /* Footer links removed per UX request — only "Thêm sản phẩm" button remains. */
  }

  function initAddLineButton($form) {
    var $addBtn = $form.find("#addProductsServices");
    if (!$addBtn.length) {
      return;
    }
    if (!$addBtn.attr("data-module-name")) {
      $addBtn.attr("data-module-name", "ProductsServices");
    }
    $addBtn.removeClass("btn btn-default").addClass("mk-inv-add-line-btn");
    if (!$addBtn.data("mkInvOdooAddStyled")) {
      $addBtn.data("mkInvOdooAddStyled", true);
      $addBtn
        .empty()
        .append(
          '<span class="mk-inv-add-line-btn__plus" aria-hidden="true">+</span> Thêm hàng hoá',
        );
    }
    if (!$addBtn.data("mkInvOdooAddBound")) {
      $addBtn.data("mkInvOdooAddBound", true);
      // Replace legacy direct binds (may be missing after shell move) with reliable create.
      $addBtn.off("click");
      $addBtn.on("click.mkInvOdooAdd", function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        createInventoryLineItemRow($form, $(this));
      });
    }
    pinAddLineButtonToTabs($form);
    initQuickProductSearch($form);
  }

  function pinAddLineButtonToTabs($form) {
    var $lineBlock = $form
      .find(".mk-inv-lineitems-odoo, #lineItemTab")
      .first()
      .closest(".fieldBlockContainer");
    if (!$lineBlock.length) {
      $lineBlock = $form.find("#lineItemTab").closest(".fieldBlockContainer");
    }
    var $tabs = $lineBlock.find(".mk-inv-odoo-tabs").first();
    var $addBtn = $form.find("#addProductsServices").first();
    if (!$tabs.length || !$addBtn.length) {
      return;
    }
    var $actions = $tabs
      .find(".mk-inv-line-header-actions, .mk-qt-line-actions")
      .first();
    if (!$actions.length) {
      $actions = $(
        '<div class="mk-inv-line-header-actions mk-qt-line-actions" aria-label="Thao tác dòng sản phẩm"></div>',
      );
      $tabs.append($actions);
    } else {
      $actions.addClass("mk-inv-line-header-actions mk-qt-line-actions");
    }
    if (!$addBtn.closest(".mk-inv-line-header-actions, .mk-qt-line-actions").length) {
      $actions.append($addBtn.detach());
    }
    // Keep button for programmatic line create; UI uses quick search instead.
    $addBtn.addClass("mk-inv-add-line-btn--hidden").attr("aria-hidden", "true");
  }

  function getSelectedProductIds($form) {
    var used = {};
    if (!$form || !$form.length) {
      return used;
    }
    $form
      .find("#lineItemTab tr.lineItemRow")
      .not(".hide, .lineItemCloneCopy")
      .each(function () {
        var $row = $(this);
        if (isTemplateLineItemRow($row)) {
          return true;
        }
        var id = ($row.find("input.selectedModuleId").val() || "").trim();
        if (id) {
          used[String(id)] = true;
        }
        return true;
      });
    return used;
  }

  function filterCatalogExcludingSelected(products, $form) {
    var used = getSelectedProductIds($form);
    return (products || []).filter(function (p) {
      var id = String(p && p.id != null ? p.id : "");
      return id && !used[id];
    });
  }

  function ensureQtyEditable($row) {
    if (!$row || !$row.length) {
      return;
    }
    $row
      .find("input.qty, .qty")
      .prop("readonly", false)
      .prop("disabled", false)
      .removeAttr("readonly")
      .removeAttr("disabled")
      .css({
        "pointer-events": "auto",
        "user-select": "text",
        cursor: "text",
      });
  }

  function isEditingLineField($form) {
    var el = document.activeElement;
    if (!el) {
      return false;
    }
    var $el = $(el);
    if (!$el.closest("#lineItemTab").length) {
      return false;
    }
    return (
      $el.is(
        "input.qty, .qty, input.listPrice, .listPrice, .mk-inv-unit-select, .mk-inv-discount-select, .mk-inv-discount-custom, .mk-inv-tax-select, textarea, input[type='text'], input[type='number']",
      ) || $el.hasClass("qty")
    );
  }

  function findEmptyProductLineRow($form) {
    var $empty = null;
    $form
      .find("#lineItemTab tr.lineItemRow")
      .not(".hide, .lineItemCloneCopy")
      .each(function () {
        var $row = $(this);
        if (isTemplateLineItemRow($row)) {
          return true;
        }
        var id = ($row.find("input.selectedModuleId").val() || "").trim();
        var name = ($row.find("input.productName").val() || "").trim();
        if (!id && !name) {
          $empty = $row;
          return false;
        }
        return true;
      });
    return $empty;
  }

  function ensureProductOptionOnSelect($sel, productId, meta) {
    if (!$sel || !$sel.length || !productId) {
      return;
    }
    var id = String(productId);
    var $opt = $sel.find('option[value="' + id.replace(/"/g, "") + '"]');
    meta = meta || {};
    if (!$opt.length) {
      var displayName = decodeText(meta.name || id);
      var label = displayName;
      if (meta.sku) {
        label += " (" + decodeText(meta.sku) + ")";
      }
      $opt = $("<option></option>")
        .attr("value", id)
        .attr("data-name", displayName)
        .attr("data-price", meta.price || 0)
        .attr("data-price-lt-1m", meta.price_lt_1m)
        .attr("data-price-gte-1m", meta.price_gte_1m)
        .attr("data-price-gte-3m", meta.price_gte_3m)
        .attr("data-price-gte-5m", meta.price_gte_5m)
        .attr("data-price-gte-7m", meta.price_gte_7m)
        .attr("data-price-tuibao", meta.price_tuibao)
        .attr("data-product-group", meta.product_group || "")
        .attr("data-sku", meta.sku || "")
        .attr("data-unit", meta.unit || "")
        .attr("data-type", meta.type || "")
        .attr("data-stock", meta.stock != null ? meta.stock : 0)
        .attr("data-qty-po", meta.qty_po != null ? meta.qty_po : 0)
        .attr("data-qty-so", meta.qty_so != null ? meta.qty_so : 0)
        .text(label);
      $sel.append($opt);
    } else if (meta.name) {
      $opt
        .attr("data-name", decodeText(meta.name))
        .attr("data-price", meta.price || $opt.attr("data-price") || 0)
        .attr(
          "data-price-lt-1m",
          meta.price_lt_1m != null
            ? meta.price_lt_1m
            : $opt.attr("data-price-lt-1m"),
        )
        .attr(
          "data-price-gte-1m",
          meta.price_gte_1m != null
            ? meta.price_gte_1m
            : $opt.attr("data-price-gte-1m"),
        )
        .attr(
          "data-price-gte-3m",
          meta.price_gte_3m != null
            ? meta.price_gte_3m
            : $opt.attr("data-price-gte-3m"),
        )
        .attr(
          "data-price-gte-5m",
          meta.price_gte_5m != null
            ? meta.price_gte_5m
            : $opt.attr("data-price-gte-5m"),
        )
        .attr(
          "data-price-gte-7m",
          meta.price_gte_7m != null
            ? meta.price_gte_7m
            : $opt.attr("data-price-gte-7m"),
        )
        .attr(
          "data-price-tuibao",
          meta.price_tuibao != null
            ? meta.price_tuibao
            : $opt.attr("data-price-tuibao"),
        )
        .attr(
          "data-product-group",
          meta.product_group || $opt.attr("data-product-group") || "",
        )
        .attr("data-sku", meta.sku || $opt.attr("data-sku") || "")
        .attr("data-unit", meta.unit || $opt.attr("data-unit") || "")
        .attr("data-type", meta.type || $opt.attr("data-type") || "")
        .attr(
          "data-stock",
          meta.stock != null ? meta.stock : $opt.attr("data-stock") || 0,
        )
        .attr(
          "data-qty-po",
          meta.qty_po != null ? meta.qty_po : $opt.attr("data-qty-po") || 0,
        )
        .attr(
          "data-qty-so",
          meta.qty_so != null ? meta.qty_so : $opt.attr("data-qty-so") || 0,
        );
    }
  }

  function findProductOption($sel, productId) {
    if (!$sel || !$sel.length || productId == null || productId === "") {
      return $();
    }
    var id = String(productId);
    var $match = $();
    $sel.find("option").each(function () {
      if (String($(this).val()) === id) {
        $match = $(this);
        return false;
      }
    });
    return $match;
  }

  function syncProductSelectDisplay($row, $form) {
    if (!$row || !$row.length) {
      return;
    }
    var $sel = $row.find("select.mk-inv-product-select").first();
    if (!$sel.length || $sel.data("mkLoading")) {
      return;
    }
    var productId = (
      $row.find("input.selectedModuleId").val() ||
      $sel.val() ||
      ""
    )
      .toString()
      .trim();
    if (!productId) {
      $sel.val("");
      if ($sel.data("select2")) {
        try {
          $sel.select2("val", "");
        } catch (ignoreClear) {
          /* ignore */
        }
      }
      return;
    }

    var $nameInput = $row.find("input.productName").first();
    var displayName = decodeText($nameInput.val() || "");
    var $opt = findProductOption($sel, productId);
    var meta = readProductMetaFromOption($opt);
    if (!meta.name) {
      meta.name = displayName;
    }
    if (!meta.name && productCatalogCache && productCatalogCache.length) {
      for (var i = 0; i < productCatalogCache.length; i++) {
        if (String(productCatalogCache[i].id) === productId) {
          meta = jQuery.extend({}, productCatalogCache[i], meta);
          break;
        }
      }
    }
    if (!meta.name) {
      meta.name = productId;
    }

    ensureProductOptionOnSelect($sel, productId, meta);
    var label = decodeText(meta.name || "");
    if ($sel.val() !== String(productId)) {
      $sel.val(String(productId));
    }
    if ($sel.data("select2")) {
      var needsValUpdate = true;
      try {
        needsValUpdate =
          String($sel.select2("val") || "") !== String(productId);
      } catch (ignoreValCheck) {
        needsValUpdate = true;
      }
      if (needsValUpdate) {
        try {
          $sel.select2("val", String(productId));
        } catch (ignoreSelect2) {
          /* ignore */
        }
      }
      paintProductSelectLabel($sel, label);
    }
  }

  function syncAllProductSelectDisplays($form) {
    if (!$form || !$form.length) {
      return;
    }
    $form.find("#lineItemTab tr.lineItemRow").each(function () {
      var $row = $(this);
      if (isTemplateLineItemRow($row) || !rowHasSelectedProduct($row)) {
        return;
      }
      syncProductSelectDisplay($row, $form);
    });
  }

  function readProductMetaFromOption($opt) {
    if (!$opt || !$opt.length) {
      return {};
    }
    return {
      name: $opt.attr("data-name") || $opt.text() || "",
      price: $opt.attr("data-price") || 0,
      price_lt_1m: $opt.attr("data-price-lt-1m"),
      price_gte_1m: $opt.attr("data-price-gte-1m"),
      price_gte_3m: $opt.attr("data-price-gte-3m"),
      price_gte_5m: $opt.attr("data-price-gte-5m"),
      price_gte_7m: $opt.attr("data-price-gte-7m"),
      price_tuibao: $opt.attr("data-price-tuibao"),
      product_group: $opt.attr("data-product-group") || "",
      sku: $opt.attr("data-sku") || "",
      unit: $opt.attr("data-unit") || "",
      type: $opt.attr("data-type") || "",
      stock: $opt.attr("data-stock"),
      qty_po: $opt.attr("data-qty-po"),
      qty_so: $opt.attr("data-qty-so"),
    };
  }

  function applyProductToLineRow($row, $form, productId, meta) {
    if (!$row || !$row.length || !productId) {
      return;
    }
    if (meta && meta.stock != null && meta.stock !== "") {
      var stockNum = Number(meta.stock);
      if (isFinite(stockNum)) {
        $row.data("mkAvailableStock", Math.max(0, stockNum));
        $row.removeData("mkStockCacheKey mkStockCachedHtml");
      }
    }
    var $sel = $row.find("select.mk-inv-product-select").first();
    if ($sel.length) {
      ensureProductOptionOnSelect($sel, productId, meta);
      $sel.val(String(productId));
      try {
        if ($sel.data("select2")) {
          $sel.select2("val", String(productId));
        }
      } catch (ignore) {
        /* ignore */
      }
    }
    applyProductSelection($row, $form, String(productId));
    syncProductSelectDisplay($row, $form);
    enforceQtyAgainstStock($row, $form);
    syncCreditTermsVisibility($form);
  }

  function captureScrollLock() {
    var nodes = [];
    try {
      document
        .querySelectorAll(
          ".mk-app-shell, .main-container, #page, .mk-inv-lines-card, .lineitemTableContainer, .mk-so-form-host, .fieldBlockContainer, body, html",
        )
        .forEach(function (el) {
          if (!el) {
            return;
          }
          nodes.push({ el: el, t: el.scrollTop || 0, l: el.scrollLeft || 0 });
        });
    } catch (ignoreQ) {
      /* ignore */
    }
    return {
      x: window.pageXOffset || document.documentElement.scrollLeft || 0,
      y: window.pageYOffset || document.documentElement.scrollTop || 0,
      nodes: nodes,
    };
  }

  function restoreScrollLock(lock) {
    if (!lock) {
      return;
    }
    try {
      window.scrollTo(lock.x, lock.y);
    } catch (ignoreW) {
      /* ignore */
    }
    (lock.nodes || []).forEach(function (n) {
      try {
        if (n.el) {
          n.el.scrollTop = n.t;
          n.el.scrollLeft = n.l;
        }
      } catch (ignoreN) {
        /* ignore */
      }
    });
  }

  /** Keep viewport frozen while adding lines — no auto jump to bottom. */
  function holdScrollLock(ms) {
    var lock = captureScrollLock();
    var until = Date.now() + (ms || 600);
    function tick() {
      restoreScrollLock(lock);
      if (Date.now() < until) {
        if (typeof requestAnimationFrame === "function") {
          requestAnimationFrame(tick);
        } else {
          setTimeout(tick, 16);
        }
      }
    }
    tick();
    [0, 30, 80, 150, 280, 450, 700].forEach(function (d) {
      setTimeout(function () {
        restoreScrollLock(lock);
      }, d);
    });
    return lock;
  }

  function addProductFromQuickSearch($form, productId, meta) {
    if (!$form || !$form.length || !productId) {
      return;
    }
    if ($form.data("mkInvQuickAdding")) {
      return;
    }
    $form.data("mkInvQuickAdding", true);
    // Lock scroll BEFORE DOM changes so new rows never pull the page down.
    var scrollLock = holdScrollLock(900);

    var $row = findEmptyProductLineRow($form);
    if (!$row || !$row.length) {
      $row = createInventoryLineItemRow(
        $form,
        $form.find("#addProductsServices").first(),
      );
    } else {
      styleNewLineItemFast($form, $row);
    }
    restoreScrollLock(scrollLock);

    function refocusQuickSearchNoScroll() {
      restoreScrollLock(scrollLock);
      try {
        var $qs = $form.find("select.mk-inv-quick-product-search").first();
        if (!$qs.length) {
          return;
        }
        var $cont = $qs.siblings(".select2-container").first();
        var focusEl =
          $cont.find(".select2-focusser, .select2-input, a.select2-choice").get(0) ||
          null;
        if (focusEl && typeof focusEl.focus === "function") {
          try {
            focusEl.focus({ preventScroll: true });
          } catch (e1) {
            focusEl.focus();
          }
        }
      } catch (ignoreF) {
        /* ignore */
      }
      restoreScrollLock(scrollLock);
    }

    function tryApply(attempt) {
      if (!$row || !$row.length || !$row.closest("body").length) {
        $form.removeData("mkInvQuickAdding");
        restoreScrollLock(scrollLock);
        return;
      }
      var $sel = $row.find("select.mk-inv-product-select").first();
      var ready =
        $sel.length &&
        ($sel.data("select2") ||
          $sel.data("mkCatalogReady") ||
          !$sel.prop("disabled"));
      if (ready || attempt >= 24) {
        applyProductToLineRow($row, $form, productId, meta);
        ensureQtyEditable($row);
        $form.removeData("mkInvQuickAdding");
        restoreScrollLock(scrollLock);
        // Do NOT focus qty on the new row — that auto-scrolls to the bottom.
        setTimeout(function () {
          restoreScrollLock(scrollLock);
          if (typeof $form.data("mkRefreshQuickSearch") === "function") {
            $form.data("mkRefreshQuickSearch")();
          }
          refocusQuickSearchNoScroll();
          restoreScrollLock(scrollLock);
        }, 40);
        setTimeout(function () {
          restoreScrollLock(scrollLock);
        }, 200);
        return;
      }
      setTimeout(function () {
        tryApply(attempt + 1);
      }, 40);
    }
    tryApply(0);
  }

  function initQuickProductSearch($form) {
    var $lineBlock = $form
      .find(".mk-inv-lineitems-odoo, #lineItemTab")
      .first()
      .closest(".fieldBlockContainer");
    if (!$lineBlock.length) {
      $lineBlock = $form.find("#lineItemTab").closest(".fieldBlockContainer");
    }
    var $tabs = $lineBlock.find(".mk-inv-odoo-tabs").first();
    if (!$tabs.length) {
      return;
    }
    var $actions = $tabs
      .find(".mk-inv-line-header-actions, .mk-qt-line-actions")
      .first();
    if (!$actions.length) {
      $actions = $(
        '<div class="mk-inv-line-header-actions mk-qt-line-actions" aria-label="Thao tác dòng sản phẩm"></div>',
      );
      $tabs.append($actions);
    } else {
      $actions.addClass("mk-inv-line-header-actions mk-qt-line-actions");
    }

    var $wrap = $actions.find(".mk-inv-quick-search").first();
    if (!$wrap.length) {
      $wrap = $(
        '<div class="mk-inv-quick-search" role="search">' +
          '<label class="mk-inv-quick-search__label" for="mkInvQuickProductSearch">Tìm hàng hoá</label>' +
          '<select id="mkInvQuickProductSearch" class="mk-inv-quick-product-search" title="Tìm và thêm hàng hoá"></select>' +
          "</div>",
      );
      $actions.prepend($wrap);
    }

    var $sel = $wrap.find("select.mk-inv-quick-product-search").first();
    if (!$sel.length) {
      return;
    }

    function refreshQuickSearchOptions() {
      if (!$sel.closest("body").length) {
        return;
      }
      try {
        if ($sel.data("select2") && $sel.select2("opened")) {
          return;
        }
      } catch (ignoreOpenCheck) {
        /* ignore */
      }
      var source = productCatalogCache || [];
      var available = filterCatalogExcludingSelected(source, $form);
      fillProductSelect($sel, available);
      try {
        if ($sel.data("select2")) {
          $sel.select2("val", "");
        } else {
          $sel.val("");
        }
      } catch (ignoreVal) {
        $sel.val("");
      }
    }

    $form.data("mkRefreshQuickSearch", refreshQuickSearchOptions);

    function mountQuickSelect(products) {
      if (!$sel.closest("body").length) {
        return;
      }
      var wasOpen = false;
      try {
        wasOpen = !!($sel.data("select2") && $sel.select2("opened"));
      } catch (ignoreOpen) {
        wasOpen = false;
      }
      if (wasOpen) {
        return;
      }
      var available = filterCatalogExcludingSelected(
        products || productCatalogCache || [],
        $form,
      );
      // Already mounted — just refresh available options (hide already-added products).
      if (
        $sel.data("mkQuickReady") &&
        $sel.data("select2") &&
        $sel.siblings(".select2-container").length
      ) {
        refreshQuickSearchOptions();
        return;
      }
      fillProductSelect($sel, available);
      destroyProductSelect2($sel);
      if (typeof $.fn.select2 !== "function") {
        return;
      }
      $sel.select2({
        placeholder: "Tìm hàng hoá / mã SKU…",
        allowClear: true,
        width: "100%",
        dropdownCssClass: "mk-inv-s2-drop mk-inv-s2-search mk-inv-s2-quick",
        minimumResultsForSearch: 0,
        matcher: function (term, text, opt) {
          var id = opt ? String($(opt).val() || "") : "";
          if (id && getSelectedProductIds($form)[id]) {
            return false;
          }
          return productSelectMatcher(term, text, opt);
        },
        formatResult: formatProductSelectResult,
        formatSelection: formatProductSelectSelection,
        formatNoMatches: function () {
          return "Không tìm thấy sản phẩm";
        },
        formatSearching: function () {
          return "Đang tìm…";
        },
        escapeMarkup: function (m) {
          return m;
        },
      });
      $sel.data("mkQuickReady", true);
      $sel.off("change.mkInvQuick").on("change.mkInvQuick", function () {
        var id = ($(this).val() || "").trim();
        if (!id) {
          return;
        }
        var meta = readProductMetaFromOption(
          $sel.find("option:selected").first(),
        );
        addProductFromQuickSearch($form, id, meta);
        // Reset search so user can keep adding more products.
        setTimeout(function () {
          refreshQuickSearchOptions();
        }, 120);
      });
    }

    if (productCatalogCache && productCatalogCache.length) {
      mountQuickSelect(productCatalogCache);
    } else {
      $sel.prop("disabled", true);
      $sel.empty().append('<option value="">Đang tải hàng hoá…</option>');
      loadProductCatalog().then(function (products) {
        mountQuickSelect(products || []);
      });
    }
  }

  function isPaymentMethodValue(val) {
    var v = (val || "").trim();
    if (!v) {
      return false;
    }
    return PAYMENT_METHOD_OPTIONS.some(function (item) {
      return item.value === v;
    });
  }

  function hasSelectedServiceLines($form) {
    if (!$form || !$form.length) {
      return false;
    }
    var found = false;
    $form
      .find("#lineItemTab tr.lineItemRow")
      .not(".hide, .lineItemCloneCopy, #row0")
      .each(function () {
        var $row = $(this);
        if (!rowHasSelectedProduct($row)) {
          return;
        }
        var meta = getProductMetaForRow($row);
        if (normalizeItemTypeKey(meta.type) === "service") {
          found = true;
          return false;
        }
      });
    return found;
  }

  function collectCreditTermsOptions($existingSelect) {
    var options = [];
    if ($existingSelect && $existingSelect.length && $existingSelect.is("select")) {
      $existingSelect.find("option").each(function () {
        var val = ($(this).val() || "").trim();
        var label = ($(this).text() || "").trim();
        if (!val || isPaymentMethodValue(val)) {
          return;
        }
        options.push({ value: val, label: label || val });
      });
    }
    return options.length ? options : CREDIT_TERMS_OPTIONS;
  }

  function rebuildCreditTermsSelect($select, currentVal, options) {
    options = options || CREDIT_TERMS_OPTIONS;
    $select.empty().append('<option value=""></option>');
    options.forEach(function (item) {
      $select.append(
        $("<option></option>").attr("value", item.value).text(item.label),
      );
    });
    var resolved = (currentVal || "").trim();
    if (!resolved || isPaymentMethodValue(resolved)) {
      resolved = DEFAULT_CREDIT_TERM;
    }
    if (resolved) {
      var hasOption = false;
      $select.find("option").each(function () {
        if ($(this).val() === resolved) {
          hasOption = true;
          return false;
        }
      });
      if (!hasOption) {
        $select.append(
          $("<option></option>").attr("value", resolved).text(resolved),
        );
      }
      $select.val(resolved);
    }
    return resolved;
  }

  function initCreditTermsSelect2($select) {
    if (!$select || !$select.length || typeof $.fn.select2 !== "function") {
      return;
    }
    if ($select.data("select2")) {
      try {
        $select.select2("destroy");
      } catch (ignoreDestroy) {
        /* ignore */
      }
    }
    $select.select2({
      placeholder: "Ngay lập tức",
      allowClear: false,
      width: "100%",
      minimumResultsForSearch: 0,
      dropdownCssClass: "mk-inv-s2-drop mk-inv-s2-search mk-inv-credit-drop",
      formatNoMatches: function () {
        return "Không tìm thấy công nợ";
      },
      formatSearching: function () {
        return "Đang tìm…";
      },
    });
    $select
      .off("select2-open.mkInvCredit select2-close.mkInvCredit")
      .on("select2-open.mkInvCredit", function () {
        var $drop = $(".select2-drop.mk-inv-credit-drop.select2-drop-active");
        $drop.css("z-index", 2147483640);
        var $search = $drop.find(".select2-search input.select2-input");
        if ($search.length) {
          $search.attr("placeholder", "Tìm thời hạn thanh toán…");
          setTimeout(function () {
            $search.focus();
          }, 0);
        }
      });
  }

  function syncCreditTermsVisibility($form) {
    if (!$form || !$form.length) {
      return;
    }
    var $wrap = $form.find(".mk-inv-credit-terms").first();
    var $row = $form.find("tr.mk-inv-credit-row").first();
    var show = hasSelectedServiceLines($form);
    if ($wrap.length) {
      $wrap.toggleClass("mk-inv-credit-terms--visible", show);
      $wrap.attr("aria-hidden", show ? "false" : "true");
    }
    if ($row.length) {
      $row.toggleClass("mk-inv-hide-legacy", !show);
    }
    if (!show) {
      return;
    }
    var $sel = $wrap.length
      ? $wrap.find('[name="mk_payment_terms"]').first()
      : $form.find("#mkInvCreditTermsSelect, [name='mk_payment_terms']").first();
    if (!$sel.length) {
      return;
    }
    var current = ($sel.val() || "").trim();
    if (!current || isPaymentMethodValue(current)) {
      var resolved = rebuildCreditTermsSelect($sel, DEFAULT_CREDIT_TERM);
      try {
        if ($sel.data("select2")) {
          $sel.select2("val", resolved);
        }
      } catch (ignoreSel) {
        /* ignore */
      }
    }
  }

  function rebuildPaymentMethodSelect($select, currentVal) {
    $select.empty().append('<option value="">— Chọn hình thức —</option>');
    PAYMENT_METHOD_OPTIONS.forEach(function (item) {
      $select.append(
        $("<option></option>").attr("value", item.value).text(item.label),
      );
    });
    var resolved = (currentVal || "").trim();
    if (!resolved) {
      resolved = DEFAULT_PAYMENT_METHOD;
    }
    if (resolved) {
      var hasOption = false;
      $select.find("option").each(function () {
        if ($(this).val() === resolved) {
          hasOption = true;
          return false;
        }
      });
      if (!hasOption) {
        $select.append(
          $("<option></option>").attr("value", resolved).text(resolved),
        );
      }
      $select.val(resolved);
    }
    return resolved;
  }

  function syncPaymentMethodChips($wrap, value) {
    if (!$wrap || !$wrap.length) {
      return;
    }
    $wrap.find(".mk-inv-pay-chip").each(function () {
      var $chip = $(this);
      $chip.toggleClass("is-active", $chip.attr("data-value") === value);
      $chip.attr("aria-pressed", $chip.attr("data-value") === value ? "true" : "false");
    });
  }

  function rebuildInvoiceTierSelect($select, currentValue) {
    $select.empty();
    INVOICE_TIER_OPTIONS.forEach(function (item) {
      $select.append(
        $("<option></option>").attr("value", item.value).text(item.label),
      );
    });
    var resolved =
      currentValue === "auto" || INVOICE_TIER_FIELDS[currentValue]
        ? currentValue
        : DEFAULT_INVOICE_TIER;
    $select.val(resolved);
    return resolved;
  }

  function initInvoicePriceTier($form) {
    var $lineBlock = $form.find(".mk-inv-lineitems-odoo");
    if (!$lineBlock.length || $lineBlock.data("mkInvPriceTier")) {
      return;
    }
    $lineBlock.data("mkInvPriceTier", true);

    var $container = $lineBlock.find(".lineitemTableContainer");
    if (!$container.length || $container.find(".mk-inv-price-tier").length) {
      return;
    }

    var $existing = $form.find('[name="mk_invoice_price_tier"]').first();
    var currentValue = String($existing.val() || "").trim();
    var isCreate = !String($form.find('input[name="record"]').val() || "").trim();
    // New documents default to Giá < 1 triệu (not auto / empty).
    if (!currentValue || (isCreate && (currentValue === "auto" || !INVOICE_TIER_FIELDS[currentValue] && currentValue !== "auto"))) {
      currentValue = DEFAULT_INVOICE_TIER;
    }
    if (currentValue !== "auto" && !INVOICE_TIER_FIELDS[currentValue]) {
      currentValue = DEFAULT_INVOICE_TIER;
    }
    if ($existing.length) {
      $existing.closest("tr").addClass("mk-inv-hide-legacy");
      if ($existing.is("select") || $existing.is("input")) {
        $existing.val(currentValue);
      }
    }

    var $select;
    if ($existing.length && $existing.is("select")) {
      $select = $existing.detach().attr("id", "mkInvInvoicePriceTierSelect");
    } else {
      $select = $(
        '<select name="mk_invoice_price_tier" id="mkInvInvoicePriceTierSelect" class="mk-inv-price-tier__select"></select>',
      );
    }
    $select.addClass("mk-inv-price-tier__select");
    currentValue = rebuildInvoiceTierSelect($select, currentValue);

    var $wrap = $(
      '<div class="mk-inv-price-tier mk-inv-price-tier--modern mk-inv-price-tier--dropdown"></div>',
    );
    $wrap.append(
      '<div class="mk-inv-price-tier__row">' +
        '<span class="mk-inv-price-tier__icon" aria-hidden="true"></span>' +
        '<div class="mk-inv-price-tier__content">' +
        '<div class="mk-inv-price-tier__head">' +
        '<div class="mk-inv-price-tier__titles">' +
        '<label class="mk-inv-price-tier__label" for="mkInvInvoicePriceTierSelect">Bảng giá</label>' +
        '<span class="mk-inv-price-tier__hint">Đơn giá lấy từ Hàng hoá và cập nhật khi tổng đơn thay đổi</span>' +
        "</div>" +
        '<div class="mk-inv-price-tier__control"></div>' +
        "</div>" +
        '<div class="mk-inv-price-tier__status" aria-live="polite"></div>' +
        "</div></div>",
    );
    $wrap.find(".mk-inv-price-tier__control").append($select);
    $container.prepend($wrap);

    $select.off("change.mkInvPriceTier").on("change.mkInvPriceTier", function () {
      onInvoicePriceTierUserChange($form);
    });

    var resolved =
      currentValue === "auto"
        ? resolveInvoiceTierFromTotal(sumLinePreTax($form))
        : currentValue;
    syncInvoiceTierUi($form, currentValue, resolved);
    // Ensure pricing applies with the default tier on first paint.
    applyInvoiceTierPricing($form);
  }

  function initPaymentTerms($form) {
    var $lineBlock = $form.find(".mk-inv-lineitems-odoo");
    if (!$lineBlock.length || $lineBlock.data("mkInvPaymentTerms")) {
      return;
    }
    $lineBlock.data("mkInvPaymentTerms", true);

    $lineBlock.find(".mk-inv-line-toolbar").addClass("mk-inv-hide-legacy");
    $form
      .find("#currency_id")
      .closest(".col-sm-4")
      .addClass("mk-inv-hide-legacy");
    $form.find("#taxtype").closest(".col-sm-4").addClass("mk-inv-hide-legacy");
    $form
      .find('[name="region_id"]')
      .closest(".col-sm-4")
      .addClass("mk-inv-hide-legacy");

    var $container = $lineBlock.find(".lineitemTableContainer");
    if ($container.find(".mk-inv-payment-terms").length) {
      return;
    }

    var $existingTerms = $form.find('[name="mk_payment_terms"]');
    var $existingMethod = $form.find('[name="mk_payment_method"]');
    if ($existingTerms.length) {
      $existingTerms.closest("tr").addClass("mk-inv-hide-legacy");
    }
    if ($existingMethod.length) {
      $existingMethod.closest("tr").addClass("mk-inv-hide-legacy");
    }

    var termsVal = "";
    var methodVal = "";
    if ($existingTerms.length) {
      termsVal = ($existingTerms.val() || "").trim();
    }
    if ($existingMethod.length) {
      methodVal = ($existingMethod.val() || "").trim();
    }
    if (!methodVal && isPaymentMethodValue(termsVal)) {
      methodVal = termsVal;
      termsVal = "";
    }

    var creditOptions = collectCreditTermsOptions(
      $existingTerms.filter("select").first(),
    );

    var chipsHtml = PAYMENT_METHOD_OPTIONS.map(function (item) {
      return (
        '<button type="button" class="mk-inv-pay-chip" data-value="' +
        $("<div>").text(item.value).html() +
        '" aria-pressed="false">' +
        $("<div>").text(item.label).html() +
        "</button>"
      );
    }).join("");

    var $payWrap = $(
      '<div class="mk-inv-payment-terms mk-inv-payment-terms--modern"></div>',
    );
    $payWrap.append(
      '<div class="mk-inv-payment-terms__row">' +
        '<span class="mk-inv-payment-terms__icon" aria-hidden="true"></span>' +
        '<div class="mk-inv-payment-terms__content">' +
        '<div class="mk-inv-payment-terms__head">' +
        '<label class="mk-inv-payment-terms__label" for="mkInvPaymentMethodSelect">Hình thức thanh toán</label>' +
        '<span class="mk-inv-payment-terms__hint">Chọn cách khách thanh toán</span>' +
        "</div>" +
        '<div class="mk-inv-payment-terms__chips" role="group" aria-label="Hình thức thanh toán">' +
        chipsHtml +
        "</div>" +
        '<div class="mk-inv-payment-terms__field mk-inv-payment-terms__field--sr"></div>' +
        "</div></div>",
    );

    var $methodSelect;
    if ($existingMethod.length && $existingMethod.is("select")) {
      $methodSelect = $existingMethod
        .detach()
        .attr("id", "mkInvPaymentMethodSelect");
    } else {
      $methodSelect = $(
        '<select class="inputElement" name="mk_payment_method" id="mkInvPaymentMethodSelect"></select>',
      );
    }
    var resolvedMethod = rebuildPaymentMethodSelect($methodSelect, methodVal);
    $payWrap.find(".mk-inv-payment-terms__field").append($methodSelect);

    var $creditWrap = $(
      '<div class="mk-inv-credit-terms mk-inv-credit-terms--modern" aria-hidden="true"></div>',
    );
    $creditWrap.append(
      '<div class="mk-inv-payment-terms__row">' +
        '<span class="mk-inv-payment-terms__icon mk-inv-payment-terms__icon--credit" aria-hidden="true"></span>' +
        '<div class="mk-inv-payment-terms__content">' +
        '<div class="mk-inv-payment-terms__head mk-inv-credit-terms__head">' +
        '<label class="mk-inv-payment-terms__label" for="mkInvCreditTermsSelect">Công nợ</label>' +
        '<span class="mk-inv-payment-terms__hint">Chọn thời hạn thanh toán cho đơn hàng</span>' +
        "</div>" +
        '<div class="mk-inv-credit-terms__field"></div>' +
        "</div></div>",
    );

    var $creditSelect;
    if ($existingTerms.length && $existingTerms.is("select")) {
      $creditSelect = $existingTerms.detach().attr("id", "mkInvCreditTermsSelect");
    } else {
      $creditSelect = $(
        '<select class="inputElement" name="mk_payment_terms" id="mkInvCreditTermsSelect"></select>',
      );
      if ($existingTerms.length) {
        termsVal = ($existingTerms.val() || "").trim();
      }
    }
    var resolvedCredit = rebuildCreditTermsSelect(
      $creditSelect,
      termsVal,
      creditOptions,
    );
    $creditWrap.find(".mk-inv-credit-terms__field").append($creditSelect);

    $container.prepend($creditWrap);
    $container.prepend($payWrap);
    syncPaymentMethodChips($payWrap, resolvedMethod);
    initCreditTermsSelect2($creditSelect);
    try {
      if ($creditSelect.data("select2")) {
        $creditSelect.select2("val", resolvedCredit);
      }
    } catch (ignoreCreditSel) {
      /* ignore */
    }

    $payWrap.on("click", ".mk-inv-pay-chip", function (e) {
      e.preventDefault();
      var val = $(this).attr("data-value") || "";
      $methodSelect.val(val).trigger("change");
      syncPaymentMethodChips($payWrap, val);
      try {
        if ($methodSelect.data("select2")) {
          $methodSelect.select2("val", val);
        }
      } catch (ignoreSel) {
        /* ignore */
      }
    });

    $methodSelect.on("change.mkInvPay", function () {
      syncPaymentMethodChips($payWrap, ($methodSelect.val() || "").trim());
    });

    if (typeof vtUtils !== "undefined" && vtUtils.applyFieldElementsView) {
      vtUtils.applyFieldElementsView($payWrap);
    }

    syncCreditTermsVisibility($form);
  }

  /**
   * Compact payment + price tier as dropdown rows inside Chi tiết báo giá.
   */
  function buildQuoteInfoSelectRow(fieldName, label, $select) {
    $select
      .addClass("inputElement mk-inv-commerce-select")
      .removeClass("mk-inv-price-tier__select");
    return $(
      '<tr class="mk-inv-quote-field-row" data-mk-field="' +
        fieldName +
        '">' +
        '<td class="fieldLabel"><label class="muted">' +
        label +
        "</label></td>" +
        '<td class="fieldValue"></td></tr>',
    )
      .find(".fieldValue")
      .append($select)
      .end();
  }

  function findQuoteInfoTableBody($form) {
    var $info = $form
      .find(
        ".mk-qt-rail-quote-info, .mk-so-rail-info, .fieldBlockContainer[data-block='LBL_QUOTE_INFORMATION'], .fieldBlockContainer[data-block='LBL_SO_INFORMATION']",
      )
      .first();
    if (!$info.length) {
      $info = $("#mkQtQuoteRail, #mkSoOrderRail")
        .find(
          ".mk-qt-rail-quote-info, .mk-so-rail-info, .fieldBlockContainer[data-block='LBL_QUOTE_INFORMATION'], .fieldBlockContainer[data-block='LBL_SO_INFORMATION']",
        )
        .first();
    }
    if (!$info.length) {
      return $();
    }
    return $info.find(".mk-qt-fields-table tbody, .mk-so-fields-table tbody, table tbody").first();
  }

  function integrateCommerceIntoQuoteInfo($form) {
    if (!$form || !$form.length) {
      $form = $("form#EditView, form[name='EditView']").first();
    }
    var $tbody = findQuoteInfoTableBody($form);
    if (!$tbody.length) {
      return false;
    }
    var $methodSelect = $form.find("#mkInvPaymentMethodSelect, [name='mk_payment_method']").first();
    var $tierSelect = $form.find("#mkInvInvoicePriceTierSelect, [name='mk_invoice_price_tier']").first();
    var $creditSelect = $form.find("#mkInvCreditTermsSelect, [name='mk_payment_terms']").first();

    var hasMethodRow = $tbody.find('tr[data-mk-field="mk_payment_method"]').length > 0;
    var hasTierRow = $tbody.find('tr[data-mk-field="mk_invoice_price_tier"]').length > 0;
    var hasCreditRow = $tbody.find("tr.mk-inv-credit-row").length > 0;
    var $tierPlaceholderTd = $tbody.find('tr.mk-qt-customer-row td.mk-qt-tier-field').first();
    var hasTierInPlaceholder =
      $tierPlaceholderTd.length &&
      $tierPlaceholderTd.find("#mkInvInvoicePriceTierSelect, select[name='mk_invoice_price_tier']").length > 0;
    var tierHasUi = hasTierRow || hasTierInPlaceholder;
    if ($tbody.data("mkInvCommerceIntegrated")) {
      var methodSynced = !$methodSelect.length || hasMethodRow;
      // "Bảng giá" được xem là đã UI khi có select trong placeholder cạnh "Khách hàng"
      // hoặc đã chèn thành dòng riêng.
      var tierSynced = tierHasUi;
      var creditSynced = !$creditSelect.length || hasCreditRow;
      if (methodSynced && tierSynced && creditSynced) {
        return true;
      }
      $tbody.removeData("mkInvCommerceIntegrated");
    }

    if (!$methodSelect.length && !$tierSelect.length) {
      return false;
    }

    $form.find(".mk-inv-rail-commerce").remove();
    $form.find(".mk-inv-payment-terms, .mk-inv-price-tier, .mk-inv-credit-terms").remove();

    var $anchor = $tbody.find("tr.mk-qt-customer-row").first();
    var insertAfter = function ($row) {
      if ($anchor.length) {
        $anchor.after($row);
        $anchor = $row;
      } else {
        $tbody.prepend($row);
        $anchor = $row;
      }
    };

    if ($methodSelect.length && !$tbody.find('tr[data-mk-field="mk_payment_method"]').length) {
      insertAfter(buildQuoteInfoSelectRow("mk_payment_method", "Hình thức thanh toán", $methodSelect.detach()));
    }
    var tierHasUiNow =
      $tbody.find('tr[data-mk-field="mk_invoice_price_tier"]').length > 0 ||
      ($tierPlaceholderTd.length &&
        $tierPlaceholderTd.find("#mkInvInvoicePriceTierSelect, select[name='mk_invoice_price_tier']").length > 0);
    if ($tierSelect.length && !tierHasUiNow) {
      $tierPlaceholderTd = $tierPlaceholderTd.length
        ? $tierPlaceholderTd
        : $tbody.find('tr.mk-qt-customer-row td.mk-qt-tier-field').first();
      if ($tierPlaceholderTd.length) {
        var $detachedTier = $tierSelect.detach();
        $detachedTier
          .addClass("inputElement mk-inv-commerce-select")
          .removeClass("mk-inv-price-tier__select");
        $tierPlaceholderTd.empty().append($detachedTier);
        $detachedTier.off("change.mkInvPriceTier").on("change.mkInvPriceTier", function () {
          onInvoicePriceTierUserChange($form);
        });
      } else {
        insertAfter(buildQuoteInfoSelectRow("mk_invoice_price_tier", "Bảng giá", $tierSelect.detach()));
        $tierSelect.off("change.mkInvPriceTier").on("change.mkInvPriceTier", function () {
          onInvoicePriceTierUserChange($form);
        });
      }
    }
    // Hard fallback: if tier select is still missing, build one directly in placeholder.
    if (!$tierSelect.length) {
      $tierPlaceholderTd = $tierPlaceholderTd.length
        ? $tierPlaceholderTd
        : $tbody.find('tr.mk-qt-customer-row td.mk-qt-tier-field').first();
      if ($tierPlaceholderTd.length &&
        !$tierPlaceholderTd.find("#mkInvInvoicePriceTierSelect, [name='mk_invoice_price_tier']").length) {
        var currentTier =
          String($form.find('[name="mk_invoice_price_tier"]').first().val() || "").trim() ||
          DEFAULT_INVOICE_TIER;
        if (currentTier !== "auto" && !INVOICE_TIER_FIELDS[currentTier]) {
          currentTier = DEFAULT_INVOICE_TIER;
        }
        var $fallbackTier = $(
          '<select name="mk_invoice_price_tier" id="mkInvInvoicePriceTierSelect" class="inputElement mk-inv-commerce-select"></select>',
        );
        rebuildInvoiceTierSelect($fallbackTier, currentTier);
        $fallbackTier.off("change.mkInvPriceTier").on("change.mkInvPriceTier", function () {
          onInvoicePriceTierUserChange($form);
        });
        $tierPlaceholderTd.empty().append($fallbackTier);
      }
    }
    if ($creditSelect.length && !$tbody.find("tr.mk-inv-credit-row").length) {
      var $creditRow = buildQuoteInfoSelectRow("mk_payment_terms", "Công nợ", $creditSelect.detach());
      $creditRow.addClass("mk-inv-credit-row mk-inv-hide-legacy");
      insertAfter($creditRow);
      initCreditTermsSelect2($creditSelect);
    }

    // Fallback: sometimes "Bảng giá" select is rendered slightly later than "Hình thức thanh toán".
    // Retry to insert "Bảng giá" under the payment row if it is still missing.
    var hasTierRow = $tbody.find('tr[data-mk-field="mk_invoice_price_tier"]').length > 0;
    var $tierPlaceholderTdNow = $tbody.find('tr.mk-qt-customer-row td.mk-qt-tier-field').first();
    var hasTierInPlaceholderNow =
      $tierPlaceholderTdNow.length &&
      $tierPlaceholderTdNow.find("#mkInvInvoicePriceTierSelect, select[name='mk_invoice_price_tier']").length > 0;
    if (!hasTierRow && !hasTierInPlaceholderNow && $tbody.data("mkInvTierRowRetrying") !== true) {
      $tbody.data("mkInvTierRowRetrying", true);
      var attempts = 0;
      var maxAttempts = 8;
      var intervalMs = 120;
      var timer = setInterval(function () {
        attempts += 1;
        if (attempts > maxAttempts) {
          clearInterval(timer);
          $tbody.removeData("mkInvTierRowRetrying");
          return;
        }
        var $methodRow = $tbody.find('tr[data-mk-field="mk_payment_method"]').first();
        if (!$methodRow.length) {
          return;
        }

        var $tierSelectNow = $form
          .find("#mkInvInvoicePriceTierSelect, [name='mk_invoice_price_tier']")
          .first();
        var hasTierRowNow = $tbody.find('tr[data-mk-field="mk_invoice_price_tier"]').length > 0;
        var $tierPlaceholderTdAttempt = $tbody
          .find('tr.mk-qt-customer-row td.mk-qt-tier-field')
          .first();
        var hasTierInPlaceholderNowAttempt =
          $tierPlaceholderTdAttempt.length &&
          $tierPlaceholderTdAttempt.find("#mkInvInvoicePriceTierSelect, select[name='mk_invoice_price_tier']").length > 0;

        if ($tierSelectNow.length && !hasTierRowNow && !hasTierInPlaceholderNowAttempt) {
          if ($tierPlaceholderTdAttempt.length) {
            var $detachedTier = $tierSelectNow.detach();
            $detachedTier
              .addClass("inputElement mk-inv-commerce-select")
              .removeClass("mk-inv-price-tier__select");
            $tierPlaceholderTdAttempt.empty().append($detachedTier);
            $detachedTier.off("change.mkInvPriceTier").on("change.mkInvPriceTier", function () {
              onInvoicePriceTierUserChange($form);
            });
          } else {
            var $detachedTier = $tierSelectNow.detach();
            var $tierRow = buildQuoteInfoSelectRow(
              "mk_invoice_price_tier",
              "Bảng giá",
              $detachedTier,
            );
            $methodRow.after($tierRow);
            $detachedTier.off("change.mkInvPriceTier").on("change.mkInvPriceTier", function () {
              onInvoicePriceTierUserChange($form);
            });
          }

          clearInterval(timer);
          $tbody.removeData("mkInvTierRowRetrying");
        }
        if (!$tierSelectNow.length && $tierPlaceholderTdAttempt.length && !hasTierInPlaceholderNowAttempt) {
          var currentTierRetry =
            String($form.find('[name="mk_invoice_price_tier"]').first().val() || "").trim() ||
            DEFAULT_INVOICE_TIER;
          if (currentTierRetry !== "auto" && !INVOICE_TIER_FIELDS[currentTierRetry]) {
            currentTierRetry = DEFAULT_INVOICE_TIER;
          }
          var $fallbackTierRetry = $(
            '<select name="mk_invoice_price_tier" id="mkInvInvoicePriceTierSelect" class="inputElement mk-inv-commerce-select"></select>',
          );
          rebuildInvoiceTierSelect($fallbackTierRetry, currentTierRetry);
          $fallbackTierRetry.off("change.mkInvPriceTier").on("change.mkInvPriceTier", function () {
            onInvoicePriceTierUserChange($form);
          });
          $tierPlaceholderTdAttempt.empty().append($fallbackTierRetry);
          clearInterval(timer);
          $tbody.removeData("mkInvTierRowRetrying");
        }
      }, intervalMs);
    }

    $tbody.data("mkInvCommerceIntegrated", true);
    syncCreditTermsVisibility($form);
    if (typeof vtUtils !== "undefined" && vtUtils.applyFieldElementsView) {
      vtUtils.applyFieldElementsView($tbody.closest(".mk-qt-rail-quote-info, .mk-so-rail-info, .fieldBlockContainer"));
    }
    return true;
  }

  function relocateCommerceToRail($form) {
    return integrateCommerceIntoQuoteInfo($form);
  }

  function persistRawTotalsBeforeSubmit($form) {
    if (!$form || !$form.length) {
      return;
    }
    ensureGroupTaxMode($form);

    // Normalize VN-formatted unit prices (100.000 → 100000) before POST.
    // MySQL DECIMAL treats "100.000" as one hundred if left with thousand dots.
    $form.find("tr.lineItemRow").not(".lineItemCloneCopy, .hide").each(function () {
      var $r = $(this);
      var deleted =
        $r.find('input[name^="deleted"]').filter(function () {
          return String($(this).val()) === "1";
        }).length > 0;
      if (deleted) {
        return;
      }
      var $price = $r.find("input.listPrice").first();
      if ($price.length) {
        var rawPrice = Math.round(parseMoney($price.val()));
        if (!isFinite(rawPrice)) {
          rawPrice = 0;
        }
        // forSubmit: plain integer for DB (restore UI after is handled on fail paths by watch)
        setListPriceValue($price, rawPrice, { raw: true, forSubmit: true });
      }
      var $qty = $r.find("input.qty, .qty").first();
      if ($qty.length) {
        var rawQty = parseMoney($qty.val());
        if (isFinite(rawQty)) {
          $qty.val(String(rawQty));
        }
      }
      var pct = getRowDiscountPercent($r);
      applyLineDiscountFields($r, pct);
      applyLineTaxZero($r, $form);
    });

    var preTax = sumLinePreTax($form);
    if (preTax <= 0) {
      preTax = readAmountRaw(
        $form.find("#preTaxTotal, #netTotal, .netTotal"),
        $form.find("#pre_tax_total, #subtotal"),
      );
    }

    // BA: unit prices already include VAT — never re-add group tax on submit.
    // (Previously taxPct default / leftover values inflated total back to old “+8%”.)
    var taxPct = 0;
    var taxAmt = 0;
    if (!MK_BA_VAT_INCLUDED) {
      taxPct = clampTaxPercent(getPrimaryTaxPercent($form));
      $form.find("tr.lineItemRow").each(function () {
        var $r = $(this);
        var qty = parseMoney($r.find(".qty").val());
        var price = parseMoney($r.find(".listPrice").val());
        var rowPct = clampTaxPercent(getRowTaxPercent($r, $form));
        taxAmt += Math.round((qty * price * rowPct) / 100);
      });
      if (taxAmt <= 0 && preTax > 0 && taxPct > 0) {
        taxAmt = Math.round((preTax * taxPct) / 100);
      }
      if (preTax > 0 && taxAmt > preTax) {
        taxAmt = Math.round((preTax * Math.min(taxPct, 100)) / 100);
        if (taxAmt > preTax) {
          taxAmt = Math.round(preTax * 0.08);
          taxPct = 8;
        }
      }
    }

    var grand = preTax + taxAmt;
    // Honour manual grand total via adjustment when user edited TỔNG CỘNG
    if (isManualGrandTotal($form)) {
      var manualGrand = parseMoney(
        $form.find(".mk-inv-grand-manual-input").val() ||
          $form.find('#total, input[name="total"]').val(),
      );
      if (manualGrand > 0 || String($form.find(".mk-inv-grand-manual-input").val() || "") === "0") {
        grand = manualGrand;
        var $adj = $form.find("#adjustment, input[name='adjustment']");
        if ($adj.length) {
          $adj.val(grand - preTax);
        }
      }
    } else {
      $form.find("#adjustment, input[name='adjustment']").val(0);
    }
    $form.find('#subtotal, input[name="subtotal"]').val(preTax);
    $form.find('#pre_tax_total, input[name="pre_tax_total"]').val(preTax);
    $form.find('#total, input[name="total"]').val(grand);

    $form.find(".groupTaxPercentage").each(function (idx) {
      $(this).val(idx === 0 ? taxPct : 0);
    });
    $form.find(".groupTaxTotal").each(function (idx) {
      $(this).val(idx === 0 ? taxAmt : 0);
    });

    var $vatPct = $form.find('[name="mk_vat_percent"]');
    if ($vatPct.length) {
      $vatPct.val(taxPct);
    }
    var $vatAmt = $form.find('[name="mk_vat_amount"]');
    if ($vatAmt.length) {
      $vatAmt.val(taxAmt);
    }

    var $result = $form.find("#lineItemResult");
    if ($result.length) {
      writeAmountDisplay($result.find("#preTaxTotal, #netTotal, .netTotal"), preTax);
      writeAmountDisplay($result.find("#tax_final"), taxAmt);
      writeAmountDisplay($result.find("#grandTotal, .grandTotal"), grand);
      $result.find("#tax_final").attr("data-raw", taxAmt);
      var $manual = $result.find(".mk-inv-grand-manual-input");
      if ($manual.length && document.activeElement !== $manual[0] && !isManualGrandTotal($form)) {
        $manual.val(formatVndNumber(grand));
      }
    }
  }

  function patchInventoryMoneyReaders() {
    if (typeof Inventory_Edit_Js === "undefined" || !Inventory_Edit_Js.prototype) {
      return;
    }
    var proto = Inventory_Edit_Js.prototype;
    if (proto.__mkOdooMoneyPatched) {
      return;
    }
    proto.__mkOdooMoneyPatched = true;

    function readPatchedAmount($el, fallbackTextFn) {
      if ($el && $el.length) {
        var raw = $el.data("mkRawAmount");
        if (raw !== undefined && raw !== null && raw !== "") {
          var n = parseMoney(raw);
          if (!isNaN(n)) {
            return n;
          }
        }
      }
      return fallbackTextFn ? fallbackTextFn.call(this) : 0;
    }

    var origGetGrandTotal = proto.getGrandTotal;
    proto.getGrandTotal = function () {
      var $total = jQuery("#total");
      if ($total.length) {
        var hidden = parseMoney($total.val());
        if (hidden > 0) {
          return hidden;
        }
      }
      return readPatchedAmount.call(this, this.grandTotal, function () {
        return parseMoney((this.grandTotal && this.grandTotal.text()) || "") ||
          (origGetGrandTotal ? origGetGrandTotal.call(this) : 0);
      });
    };

    var origGetPreTaxTotal = proto.getPreTaxTotal;
    proto.getPreTaxTotal = function () {
      var $pre = jQuery("#pre_tax_total");
      if ($pre.length) {
        var hidden = parseMoney($pre.val());
        if (hidden > 0 || $pre.val() === "0") {
          return hidden;
        }
      }
      return readPatchedAmount.call(this, this.preTaxTotalEle, function () {
        return parseMoney((this.preTaxTotalEle && this.preTaxTotalEle.text()) || "") ||
          (origGetPreTaxTotal ? origGetPreTaxTotal.call(this) : 0);
      });
    };

    var origGetNetTotal = proto.getNetTotal;
    proto.getNetTotal = function () {
      var $sub = jQuery("#subtotal");
      if ($sub.length) {
        var hidden = parseMoney($sub.val());
        if (hidden > 0 || $sub.val() === "0") {
          return hidden;
        }
      }
      return readPatchedAmount.call(this, this.netTotalEle, function () {
        return parseMoney((this.netTotalEle && this.netTotalEle.text()) || "") ||
          (origGetNetTotal ? origGetNetTotal.call(this) : 0);
      });
    };

    var origSaveTotalValue = proto.saveTotalValue;
    proto.saveTotalValue = function () {
      var $odooForm = jQuery("form.mk-inv-form-odoo").first();
      if ($odooForm.length) {
        persistRawTotalsBeforeSubmit($odooForm);
        return;
      }
      if (origSaveTotalValue) {
        return origSaveTotalValue.call(this);
      }
    };

    var origSaveSubTotalValue = proto.saveSubTotalValue;
    proto.saveSubTotalValue = function () {
      var $odooForm = jQuery("form.mk-inv-form-odoo").first();
      if ($odooForm.length) {
        persistRawTotalsBeforeSubmit($odooForm);
        return;
      }
      if (origSaveSubTotalValue) {
        return origSaveSubTotalValue.call(this);
      }
    };

    var origSavePreTaxTotalValue = proto.savePreTaxTotalValue;
    proto.savePreTaxTotalValue = function () {
      var $odooForm = jQuery("form.mk-inv-form-odoo").first();
      if ($odooForm.length) {
        persistRawTotalsBeforeSubmit($odooForm);
        return;
      }
      if (origSavePreTaxTotalValue) {
        return origSavePreTaxTotalValue.call(this);
      }
    };

    // Keep radio + discount_type in sync before Vtiger re-reads them for net price
    var origCalcDisc = proto.calculateDiscountForLineItem;
    if (origCalcDisc && !proto.__mkOdooDiscCalcPatched) {
      proto.__mkOdooDiscCalcPatched = true;
      proto.calculateDiscountForLineItem = function (lineItemRow) {
        var $row = jQuery(lineItemRow);
        var $odooForm = $row.closest("form.mk-inv-form-odoo");
        if (!$odooForm.length) {
          $odooForm = jQuery("form.mk-inv-form-odoo").first();
        }
        if ($odooForm.length && $row.length) {
          try {
            applyLineDiscountFields($row, getRowDiscountPercent($row));
          } catch (e) {
            /* ignore */
          }
        }
        if (origCalcDisc) {
          return origCalcDisc.call(this, lineItemRow);
        }
      };
    }

    // Unit price: read VN dots correctly; write always as 45.000
    var origGetListPrice = proto.getListPriceValue;
    proto.getListPriceValue = function (lineItemRow) {
      var $lp = jQuery(lineItemRow).find("input.listPrice").first();
      if ($lp.length) {
        var fromData = $lp.attr("data-mk-raw-price");
        if (fromData !== undefined && fromData !== null && fromData !== "") {
          var dn = parseMoney(fromData);
          if (isFinite(dn)) {
            return dn;
          }
        }
        return parseMoney($lp.val());
      }
      return origGetListPrice ? origGetListPrice.call(this, lineItemRow) : 0;
    };

    var origSetListPrice = proto.setListPriceValue;
    proto.setListPriceValue = function (lineItemRow, listPriceValue) {
      var $lp = jQuery(lineItemRow).find("input.listPrice").first();
      if ($lp.length) {
        setListPriceValue($lp, listPriceValue);
        return this;
      }
      if (origSetListPrice) {
        return origSetListPrice.call(this, lineItemRow, listPriceValue);
      }
      return this;
    };

    var origFormatListPrice = proto.formatListPrice;
    proto.formatListPrice = function (lineItemRow, listPriceValue) {
      var $lp = jQuery(lineItemRow).find("input.listPrice").first();
      if ($lp.length) {
        setListPriceValue($lp, listPriceValue);
        return this;
      }
      if (origFormatListPrice) {
        return origFormatListPrice.call(this, lineItemRow, listPriceValue);
      }
      return this;
    };

    // After stock line calculations, re-apply VN thousand separators on unit price.
    var origLineItemRowCalculations = proto.lineItemRowCalculations;
    if (origLineItemRowCalculations && !proto.__mkOdooLineCalcFmt) {
      proto.__mkOdooLineCalcFmt = true;
      proto.lineItemRowCalculations = function (lineItemRow) {
        var result = origLineItemRowCalculations.call(this, lineItemRow);
        try {
          var $lp = jQuery(lineItemRow).find("input.listPrice").first();
          if ($lp.length) {
            formatListPriceInput($lp, true);
          }
        } catch (eFmt) {
          /* ignore */
        }
        return result;
      };
    }

    // mapResultsToFields writes raw unitPrice — reformat after product load.
    var origMapResults = proto.mapResultsToFields;
    if (origMapResults && !proto.__mkOdooMapFmt) {
      proto.__mkOdooMapFmt = true;
      proto.mapResultsToFields = function (parentRow, responseData) {
        var result = origMapResults.call(this, parentRow, responseData);
        try {
          var $lp = jQuery(parentRow).find("input.listPrice").first();
          if ($lp.length) {
            formatListPriceInput($lp, true);
          }
        } catch (eMap) {
          /* ignore */
        }
        return result;
      };
    }
  }

  function bindSubmitTotalsGuard($form) {
    if (!$form || !$form.length || $form.data("mkInvSubmitGuard")) {
      return;
    }
    $form.data("mkInvSubmitGuard", true);
    patchInventoryMoneyReaders();

    $form.on("submit.mkInvTaxPersist", function () {
      persistRawTotalsBeforeSubmit($form);
    });

    // Inventory validation submit runs before native submit — persist there too.
    jQuery(document).on(
      "Vtiger.Validation.Validate.BeforeSubmit.mkInvTaxPersist",
      function (_e, params) {
        var $target = params && params.form ? jQuery(params.form) : $form;
        if (!$target.is($form) && !$form.has($target).length) {
          if (!$target.hasClass("mk-inv-form-odoo")) {
            return;
          }
        }
        persistRawTotalsBeforeSubmit($form);
      },
    );

    // Last chance: after Inventory saveTotalValue overwrites with parseFloat.
    var _submitBtnBound = false;
    function bindSaveButtons() {
      if (_submitBtnBound) {
        return;
      }
      var $btns = $form.find('button[type="submit"], button.saveButton, .saveButton');
      if (!$btns.length) {
        return;
      }
      _submitBtnBound = true;
      $btns.on("click.mkInvTaxPersist", function () {
        persistRawTotalsBeforeSubmit($form);
        setTimeout(function () {
          persistRawTotalsBeforeSubmit($form);
        }, 0);
      });
    }
    bindSaveButtons();
    setTimeout(bindSaveButtons, 500);
  }

  function ensurePreTaxTotalRow($result) {
    if (!$result || !$result.length) {
      return $();
    }
    var $pre = $result.find("#preTaxTotal").first();
    if ($pre.length) {
      var $tr = $pre.closest("tr");
      $tr
        .removeClass("hide mk-inv-totals-hide")
        .addClass("mk-inv-totals-row mk-inv-totals-row--sub")
        .attr("data-mk-totals-row", "pre-tax")
        .show()
        .css({ display: "table-row", visibility: "visible" });
      if (!$tr.find(".mk-inv-totals-label").length) {
        $tr
          .find("td:first")
          .html('<div class="mk-inv-totals-label">Số tiền trước thuế</div>');
      } else {
        $tr.find(".mk-inv-totals-label").first().text("Số tiền trước thuế");
      }
      $pre.addClass("mk-inv-vnd-amount");
      return $tr;
    }
    var $anchor = $result.find("#group_tax_row").first();
    if (!$anchor.length) {
      $anchor = $result.find("#grandTotal, .grandTotal").closest("tr").first();
    }
    var $new = $(
      '<tr class="mk-inv-totals-row mk-inv-totals-row--sub" data-mk-totals-row="pre-tax">' +
        '<td><div class="mk-inv-totals-label">Số tiền trước thuế</div></td>' +
        '<td><span class="pull-right mk-inv-vnd-amount" id="preTaxTotal">0</span>' +
        '<input type="hidden" id="pre_tax_total" name="pre_tax_total" value="0" /></td>' +
        "</tr>",
    );
    if ($anchor.length) {
      $new.insertBefore($anchor);
    } else {
      $result.prepend($new);
    }
    return $new;
  }

  /** BA: Tổng Cộng (SL×ĐG, readonly) / Chiết Khấu / Tổng Thanh Toán */
  function revealCoreTotalsRows($result) {
    if (!$result || !$result.length) {
      return;
    }
    var $sub = ensurePreTaxTotalRow($result);
    var $taxRow = $result.find("#group_tax_row").first();
    var $grand = $result.find("#grandTotal, .grandTotal").closest("tr").first();
    var $net = $result.find("#netTotal, .netTotal").closest("tr");

    if ($net.length) {
      $net.addClass("mk-inv-totals-hide").hide();
    }

    // "Số tiền trước thuế" row → repurpose as "Tổng Cộng" (gross, readonly)
    if ($sub.length) {
      $sub
        .removeClass("mk-inv-totals-hide hide")
        .addClass("mk-inv-totals-row mk-inv-totals-row--sub")
        .show()
        .css({ display: "table-row", visibility: "visible" });
      $sub.find(".mk-inv-totals-label").first().text("Tổng Cộng");
      $sub.find(".mk-inv-vat-included-hint").remove();
    }

    // Tax row → repurpose as "Chiết Khấu"
    if ($taxRow.length) {
      $taxRow
        .removeClass("hide mk-inv-totals-hide")
        .addClass("mk-inv-totals-row mk-inv-totals-row--discount")
        .attr("data-mk-totals-row", "discount")
        .show()
        .css({ display: "table-row", visibility: "visible" });
      if (!$taxRow.find(".mk-inv-totals-label").length) {
        $taxRow.find("td:first").html('<div class="mk-inv-totals-label">Chiết Khấu</div>');
      } else {
        $taxRow.find(".mk-inv-totals-label").first().text("Chiết Khấu");
      }
      $taxRow.find(".taxPercentage, .individualTax, .taxDivContainer, .groupTaxTotal, .mk-inv-tax-select, .mk-inv-hide-legacy")
        .addClass("mk-inv-hide-legacy").hide();
      if (!$taxRow.find("#mk_discount_total_display").length) {
        $taxRow.find("td:last").append('<span class="pull-right mk-inv-vnd-amount" id="mk_discount_total_display">0</span>');
      }
    }

    // Grand total → "Tổng Thanh Toán" (readonly)
    if ($grand.length) {
      $grand
        .removeClass("mk-inv-totals-hide hide")
        .addClass("mk-inv-totals-row mk-inv-totals-row--grand")
        .show()
        .css({ display: "table-row", visibility: "visible" });
      if (!$grand.find(".mk-inv-totals-label").length) {
        $grand
          .find("td:first")
          .html('<div class="mk-inv-totals-label">Tổng Thanh Toán</div>');
      } else {
        $grand.find(".mk-inv-totals-label").first().text("Tổng Thanh Toán");
      }
      $grand.find("#grandTotal, .grandTotal").addClass("mk-inv-vnd-amount");
      $grand.find(".mk-inv-vat-included-hint").remove();
      $grand.find(".mk-inv-grand-manual-input").remove();
    }
  }

  function initTotalsOdoo($form) {
    var $result = $form.find("#lineItemResult");
    if (!$result.length) {
      return;
    }
    if ($result.data("mkInvTotalsOdoo")) {
      revealCoreTotalsRows($result);
      return;
    }
    $result.data("mkInvTotalsOdoo", true);
    var $block = $result.closest(".fieldBlockContainer");
    $block.addClass("mk-inv-totals-odoo");

    $result.find("> tbody > tr, > tr").addClass("mk-inv-totals-hide");
    revealCoreTotalsRows($result);

    syncTotalsDisplay($form);
    watchTotalsAndLines($form);
    bindSubmitTotalsGuard($form);
  }

  function initLineItemsOdoo($form) {
    var $lineBlock = $form.find("#lineItemTab").closest(".fieldBlockContainer");
    if (!$lineBlock.length) {
      scheduleLineItemsRestyle($form);
      return;
    }

    if (!$lineBlock.data("mkInvLineOdoo")) {
      $lineBlock
        .data("mkInvLineOdoo", true)
        .attr("data-block", "LBL_ITEM_DETAILS")
        .addClass("mk-inv-lineitems-odoo");

      $lineBlock.find("> .row").first().addClass("mk-inv-hide-legacy");
      $lineBlock
        .find("#region_id, #currency_id, #taxtype")
        .closest(".row")
        .addClass("mk-inv-hide-legacy");
      $lineBlock.find(".well").closest(".row").addClass("mk-inv-hide-legacy");
      $lineBlock
        .find("> .row > .col-sm-3 h4.fieldBlockHeader")
        .addClass("mk-inv-hide-legacy");
      $lineBlock.find("> br").addClass("mk-inv-hide-legacy");

      bindListPriceFormatting($form);
      initOdooTabs($lineBlock);
      initInvoicePriceTier($form);
      initPaymentTerms($form);
      relocateCommerceToRail($form);
      initAddLineButton($form);
      initLineActionLinks();
      polishLineItemsShell($form);
      ensureModernLineItemsTable($form);
      bindInventoryRestyleHooks($form);
      // Immediate paint so headers show before catalog AJAX returns
      scheduleLineItemsRestyle($form, [0, 200]);

      loadProductCatalog().always(function () {
        initQuickProductSearch($form);
        scheduleLineItemsRestyle($form, [0, 400]);
        scheduleInvoiceTierPricing($form, 0);
      });

      $form
        .off("post.lineItem.New.mkInvOdoo")
        .on("post.lineItem.New.mkInvOdoo", function (e, newLineItem) {
          handleNewLineItemRow($form, newLineItem);
        });
      return;
    }

    // Subsequent init() calls: light restyle only, do not stack timeouts.
    scheduleLineItemsRestyle($form, [0]);
  }

  function hideDescriptionBlock($form) {
    $form
      .find('.fieldBlockContainer[data-block="LBL_DESCRIPTION_INFORMATION"]')
      .addClass("mk-inv-hide-legacy");
  }

  function init($form, options) {
    if (!$form || !$form.length) {
      return;
    }
    options = options || {};
    // Global: Bảng giá change always reprices (covers detaches / QuoteMk UI builds).
    if (!window.__mkInvPriceTierUserBound) {
      window.__mkInvPriceTierUserBound = true;
      jQuery(document)
        .off("change.mkInvPriceTierUser")
        .on(
          "change.mkInvPriceTierUser",
          "#mkInvInvoicePriceTierSelect, select[name='mk_invoice_price_tier']",
          function () {
            var $f = jQuery(this).closest("form#EditView, form[name='EditView']");
            if (!$f.length) {
              $f = jQuery(this).closest("form");
            }
            onInvoicePriceTierUserChange($f);
          },
        );
    }
    $form.addClass("mk-inv-form-odoo");
    if (typeof document !== "undefined" && document.documentElement) {
      document.documentElement.classList.add("mk-inv-odoo-active");
    }
    var $body = $("body");
    var moduleName = $body.attr("data-module");
    if (
      (moduleName === "Quotes" || moduleName === "SalesOrder") &&
      $body.attr("data-app") !== "SALES"
    ) {
      $body.attr("data-app", "SALES");
    }
    initAddressOdoo($form);
    initLineItemsOdoo($form);
    patchLegacyLineDeleteGuard();
    syncLineDeleteVisibility($form);
    bindSubmitTotalsGuard($form);
    if (options.hideDescriptionBlock !== false) {
      hideDescriptionBlock($form);
    }
  }

  window.MkInventoryOdooEdit = {
    init: init,
    autoBootstrap: autoBootstrapInventoryOdooUi,
    fillAddressFromPotential: fillAddressFromPotential,
    fillAddressFromAccount: fillAddressFromAccount,
    setFormAddresses: setFormAddresses,
    restyleLineItemRows: restyleLineItemRows,
    scheduleLineItemsRestyle: scheduleLineItemsRestyle,
    initQuickProductSearch: initQuickProductSearch,
    syncLineDeleteVisibility: syncLineDeleteVisibility,
    syncTotalsDisplay: syncTotalsDisplay,
    relocateCommerceToRail: relocateCommerceToRail,
    integrateCommerceIntoQuoteInfo: integrateCommerceIntoQuoteInfo,
    getPriceChannel: getPriceChannel,
    setPriceChannel: setPriceChannel,
    applyInvoiceTierPricing: applyInvoiceTierPricing,
    onInvoicePriceTierUserChange: onInvoicePriceTierUserChange,
    refreshTotals: function ($form) {
      initTotalsOdoo($form);
      syncTotalsDisplay($form);
    },
  };
})(jQuery);
