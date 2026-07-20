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

  var MODERN_LINE_HEADER_COLUMNS = [
    { className: "mk-inv-col-drag", label: "" },
    { className: "mk-inv-col-product", label: "Tên mục", required: true },
    { className: "mk-inv-col-qty", label: "Số lượng" },
    { className: "mk-inv-col-unit-head mk-inv-col-unit", label: "Đơn vị tính" },
    { className: "mk-inv-col-tax-head mk-inv-col-tax", label: "Thuế" },
    { className: "mk-inv-col-price", label: "Bảng giá" },
    { className: "mk-inv-col-amount", label: "Tổng giá trị" },
  ];

  var MODERN_LINE_COLGROUP_WIDTHS = [
    "72px",
    "24%",
    "92px",
    "132px",
    "104px",
    "148px",
    "156px",
  ];

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
    return getLineItemsTableBody($table).children("tr").first();
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
      if (shipText || force) {
        // If ship empty but bill has value, copy bill for convenience (no checkbox UI).
        var shipValue = shipText || (force ? billText : "");
        $ship.val(shipValue).trigger("change");
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
      if (
        dotParts.length > 2 ||
        (dotParts.length === 2 && dotParts[1].length === 3)
      ) {
        text = text.replace(/\./g, "");
      }
    }

    text = text.replace(/[^\d.-]/g, "");
    var n = parseFloat(text);
    return isNaN(n) ? 0 : n;
  }

  function formatVnd(value) {
    var n = Math.round(parseMoney(value));
    return "đ " + n.toLocaleString("vi-VN");
  }

  function formatVndNumber(value) {
    var n = Math.round(parseMoney(value));
    return n.toLocaleString("vi-VN");
  }

  function sumLinePreTax($form) {
    var sum = 0;
    $form.find("tr.lineItemRow").each(function () {
      var $r = $(this);
      var qty = parseMoney($r.find(".qty").val());
      var price = parseMoney($r.find(".listPrice").val());
      sum += qty * price;
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
      $form.find('[name="mk_invoice_price_tier"]').first().val() || "auto",
    ).trim();
    if (value === "auto" || INVOICE_TIER_FIELDS[value]) {
      return value;
    }
    return "auto";
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
    var field = INVOICE_TIER_FIELDS[tierKey];
    if (field && meta[field] !== undefined && meta[field] !== null && meta[field] !== "") {
      return parseMoney(meta[field]);
    }
    return parseMoney(meta.price || 0);
  }

  function applyInvoiceTierPriceToRow($row, $form, tierKey) {
    if (!rowHasSelectedProduct($row)) {
      return false;
    }
    var price = resolveInvoiceTierPrice(getProductMetaForRow($row), tierKey);
    var $listPrice = $row.find("input.listPrice").first();
    if (!$listPrice.length) {
      return false;
    }
    $listPrice.val(price).attr("data-mk-invoice-tier", tierKey);
    syncRowAmounts($row, $form);
    return true;
  }

  function syncInvoiceTierUi($form, selectedValue, resolvedTier) {
    var $wrap = $form.find(".mk-inv-price-tier").first();
    if (!$wrap.length) {
      return;
    }
    $wrap.find(".mk-inv-tier-chip").each(function () {
      var active = $(this).attr("data-value") === selectedValue;
      $(this).toggleClass("is-active", active);
      $(this).attr("aria-pressed", active ? "true" : "false");
    });
    var detail =
      selectedValue === "auto"
        ? "Đang áp dụng: " + invoiceTierLabel(resolvedTier)
        : "Đang áp dụng cho tất cả sản phẩm";
    $wrap.find(".mk-inv-price-tier__status").text(detail);
  }

  function applyInvoiceTierPricing($form) {
    if (!$form || !$form.length || $form.data("mkInvApplyingTier")) {
      return;
    }
    var selectedValue = getInvoiceTierSelection($form);
    var resolvedTier =
      selectedValue === "auto"
        ? resolveInvoiceTierFromTotal(sumLinePreTax($form))
        : selectedValue;
    var changed = false;
    $form.data("mkInvApplyingTier", true);
    try {
      $form.find("tr.lineItemRow").each(function () {
        changed =
          applyInvoiceTierPriceToRow($(this), $form, resolvedTier) || changed;
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
        $listPrice.val(0);
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
      $listPrice.val(fallbackPrice);
    }
    var previewTier = getInvoiceTierSelection($form);
    applyInvoiceTierPriceToRow(
      $row,
      $form,
      previewTier === "auto"
        ? resolveInvoiceTierFromTotal(sumLinePreTax($form))
        : previewTier,
    );
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
        $listPrice.val(fallbackPrice > 0 ? fallbackPrice : $listPrice.val());
      }
      var fallbackTier = getInvoiceTierSelection($form);
      applyInvoiceTierPriceToRow(
        $row,
        $form,
        fallbackTier === "auto"
          ? resolveInvoiceTierFromTotal(sumLinePreTax($form))
          : fallbackTier,
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
    var price = formatPriceVi($opt.attr("data-price") || 0);
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
    $productTd
      .find(".lineItemCommentBox")
      .closest("div")
      .addClass("mk-inv-hide-legacy");
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
    { value: "10", label: "10%" },
    { value: "8", label: "8%" },
    { value: "5", label: "5%" },
    { value: "0", label: "0%" },
    { value: "exempt", label: "Miễn thuế" },
  ];

  function syncRowTaxPill($row, $form) {
    var $sel = $row.find(".mk-inv-tax-select");
    if (!$sel.length) {
      return;
    }
    if (
      document.activeElement === $sel[0] ||
      $sel.data("mkTaxOpen") ||
      $sel.data("mkUserChanged")
    ) {
      return;
    }
    var pct = clampTaxPercent($row.data("mkTaxPct"));
    $row.find(".taxPercentage").each(function () {
      var v = clampTaxPercent($(this).val());
      if (v > 0) {
        pct = v;
        return false;
      }
    });
    if (!pct) {
      pct = getPrimaryTaxPercent($form);
    }
    var strVal = String(pct);
    if ($sel.val() !== strVal) {
      $sel.val(strVal);
    }
  }

  function injectTaxDropdown($row, $form) {
    $row.find("> td.mk-inv-col-tax").not(":last-child").remove();

    var $taxTd = $row.find("> td.mk-inv-col-net-slot").first();
    if (!$taxTd.length) {
      $taxTd = $row.find("> td").has(".netPrice").first();
    }
    if (!$taxTd.length) {
      $taxTd = $row.find("> td:last-child");
    }
    if (!$taxTd.length) {
      return;
    }

    // If this cell still holds the line total, move it out — tax + amount must be separate columns.
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
      .addClass("mk-inv-col-tax")
      .removeClass(
        "mk-inv-col-net-hide mk-inv-hide-legacy mk-inv-col-net-slot mk-inv-col-amount",
      );
    // Hide legacy net/tax UI leftovers (the stray "đ 0" under the % select).
    $taxTd
      .find(
        ".netPrice, span.netPrice, .individualTax, .taxDivContainer, .mk-inv-money-wrap, .mk-inv-vnd, .productTotal",
      )
      .addClass("mk-inv-hide-legacy")
      .css({ display: "none", visibility: "hidden" });

    var $existing = $taxTd.find(".mk-inv-tax-select").first();
    if ($existing.length) {
      if (
        document.activeElement === $existing[0] ||
        $existing.data("mkTaxOpen")
      ) {
        return;
      }
      $existing
        .off("pointerdown.mkInvTaxPause")
        .on("pointerdown.mkInvTaxPause", function () {
          pauseLineItemRestyle($form, 900);
        });
      // Keep only the tax select visible in this cell.
      $taxTd
        .children()
        .not(".mk-inv-tax-select")
        .addClass("mk-inv-hide-legacy")
        .css({ display: "none", visibility: "hidden" });
      syncRowTaxPill($row, $form);
      return;
    }
    $taxTd.find(".mk-inv-tax-pill").remove();

    var $sel = $(
      '<select class="mk-inv-tax-select inputElement" title="Thuế"></select>',
    );
    TAX_RATE_OPTIONS.forEach(function (opt) {
      $sel.append(
        $("<option></option>").attr("value", opt.value).text(opt.label),
      );
    });

    var currentPct = clampTaxPercent($row.data("mkTaxPct"));
    $row.find(".taxPercentage").each(function () {
      var v = clampTaxPercent($(this).val());
      if (v > 0) {
        currentPct = v;
        return false;
      }
    });
    if (!currentPct) {
      currentPct = getPrimaryTaxPercent($form);
    }
    if (!currentPct) {
      currentPct = getDefaultTaxPercent($form);
    }
    $sel.val(String(currentPct));
    $row.data("mkTaxPct", currentPct);

    $sel
      .on(
        "pointerdown.mkInvTax mousedown.mkInvTax focus.mkInvTax click.mkInvTax",
        function () {
          $sel.data("mkTaxOpen", true);
          pauseLineItemRestyle($form, 900);
        },
      )
      .on("blur.mkInvTax", function () {
        setTimeout(function () {
          $sel.data("mkTaxOpen", false);
        }, 400);
      })
      .on("input.mkInvTax change.mkInvTax", function () {
        $sel.data("mkUserChanged", true);
        $sel.data("mkTaxOpen", false);
        var val = $(this).val();
        var pct = val === "exempt" ? 0 : clampTaxPercent(val);
        $row.data("mkTaxPct", pct);
        $row.find(".taxPercentage").each(function () {
          $(this).val(pct);
        });
        $form.find(".groupTaxPercentage").each(function (idx) {
          $(this).val(idx === 0 ? pct : 0);
        });
        var $vatPct = $form.find('[name="mk_vat_percent"]');
        if ($vatPct.length) {
          $vatPct.val(pct);
        }
        setTimeout(function () {
          syncRowAmounts($row, $form);
          syncTotalsDisplay($form);
        }, 20);
        setTimeout(function () {
          $form.data("mkInvSyncingTotals", false);
          var fn = $form.data("mkScheduleRealtimeSync");
          if (fn) {
            fn();
          }
        }, 150);
      });

    // Keep hidden inventory tax inputs for Save, but never show them.
    $taxTd
      .children()
      .not(".mk-inv-tax-select")
      .addClass("mk-inv-hide-legacy")
      .css({ display: "none", visibility: "hidden" });
    $taxTd.prepend($sel);
  }

  function getRowTaxPercent($row, $form) {
    var pct = 0;
    var $sel = $row.find(".mk-inv-tax-select");
    if ($sel.length) {
      var val = $sel.val();
      if (val === "exempt") {
        return 0;
      }
      if (val === "0" || val === 0) {
        return 0;
      }
      pct = clampTaxPercent(val);
    }
    if (!pct) {
      $row.find(".taxPercentage").each(function () {
        var v = clampTaxPercent($(this).val());
        if (v > 0) {
          pct = v;
          return false;
        }
      });
    }
    if (!pct && $form && $form.length) {
      pct = getPrimaryTaxPercent($form);
    }
    return clampTaxPercent(pct);
  }

  function calcLineRowTotal($row, $form) {
    var qty = parseMoney($row.find(".qty").val());
    var price = parseMoney($row.find(".listPrice").val());
    var preTax = qty * price;
    var taxPct = getRowTaxPercent($row, $form);
    var taxAmt = Math.round((preTax * taxPct) / 100);
    return preTax + taxAmt;
  }

  function reorderTaxBeforePriceColumns($row) {
    if (!$row || !$row.length) {
      return;
    }
    var $priceTd = $row.find("> td.mk-inv-col-price").first();
    var $taxTd = $row.find("> td.mk-inv-col-tax").last();
    if (!$priceTd.length || !$taxTd.length || $priceTd[0] === $taxTd[0]) {
      return;
    }
    if ($taxTd.index() > $priceTd.index()) {
      $taxTd.insertBefore($priceTd);
    }
  }

  function reorderTaxBeforeAmountColumns($row) {
    reorderTaxBeforePriceColumns($row);
    if (!$row || !$row.length) {
      return;
    }
    var $amountTd = $row.find("> td.mk-inv-col-amount").first();
    var $taxTd = $row.find("> td.mk-inv-col-tax").last();
    if (!$amountTd.length || !$taxTd.length || $amountTd[0] === $taxTd[0]) {
      return;
    }
    if ($amountTd.index() < $taxTd.index()) {
      $taxTd.insertBefore($amountTd);
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
      return "Bảng giá";
    }
    if ($cell.find(".productTotal").length) {
      return "Tổng giá trị";
    }
    if (
      $cell.hasClass("mk-inv-col-tax") ||
      $cell.find(".mk-inv-tax-select").length
    ) {
      return "Thuế";
    }
    return "";
  }

  function syncTaxHeaderLabel($table) {
    var $header = getLineItemHeaderRow($table);
    var $sample = getLineItemSampleRow($table);
    if (!$header.length || !$sample.length) {
      return;
    }

    $header.children("td").each(function () {
      var $td = $(this);
      var text = $.trim($td.text());
      if (/giảm|chiết khấu|net price/i.test(text)) {
        $td
          .html('<span class="mk-inv-th-label">Thuế</span>')
          .removeClass("mk-inv-col-net-hide mk-inv-hide-legacy")
          .addClass("mk-inv-col-tax-head mk-inv-col-tax");
      }
    });

    var lastIdx = -1;
    $sample.children("td").each(function (idx) {
      if (
        $(this).hasClass("mk-inv-col-tax") ||
        $(this).find(".mk-inv-tax-select").length
      ) {
        lastIdx = idx;
      }
    });
    if (lastIdx >= 0) {
      $header
        .children("td")
        .eq(lastIdx)
        .removeClass("mk-inv-col-net-hide mk-inv-hide-legacy mk-inv-col-amount")
        .addClass("mk-inv-col-tax-head mk-inv-col-tax")
        .html('<span class="mk-inv-th-label">Thuế</span>');
    }
  }

  function applyHeaderCellClasses($header, $sample) {
    $header.children("td").each(function (idx) {
      var $h = $(this);
      var $b = $sample.children("td").eq(idx);
      $h.removeClass(
        "mk-inv-col-product mk-inv-col-qty mk-inv-col-unit mk-inv-col-unit-head mk-inv-col-price mk-inv-col-amount mk-inv-col-tax mk-inv-col-tax-head mk-inv-col-drag",
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
        $b.find(".mk-inv-tax-select").length
      ) {
        $h.addClass("mk-inv-col-tax-head mk-inv-col-tax");
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
        widths.push("72px");
      } else if (
        $(this).hasClass("mk-inv-col-product") ||
        $(this).find("select.mk-inv-product-native").length
      ) {
        widths.push("24%");
      } else if ($(this).hasClass("mk-inv-col-qty")) {
        widths.push("92px");
      } else if ($(this).hasClass("mk-inv-col-unit")) {
        widths.push("132px");
      } else if ($(this).hasClass("mk-inv-col-price")) {
        widths.push("148px");
      } else if ($(this).hasClass("mk-inv-col-amount")) {
        widths.push("156px");
      } else if ($(this).hasClass("mk-inv-col-tax")) {
        widths.push("104px");
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
    buildModernLineItemHeader($header);
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
    wrapMoneyInput($row.find("input.listPrice").first());
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
    var $taxTd = $row.find(".mk-inv-tax-select").closest("td").first();
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
    $row.find(".mk-inv-tax-select").each(function () {
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
    var $tax = $row.find(".mk-inv-tax-select").closest("td").first();
    var $price = paintPriceCell($row);
    var $amount = paintAmountCell($row, $form);

    var ordered = [];
    [$drag, $product, $qty, $unit, $tax, $price, $amount].forEach(
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

    // Remove legacy extra tax column between total and net.
    $row.find("> td.mk-inv-col-tax").not(":last-child").remove();

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
    syncRowTaxPill($row, $form);
    syncProductDesc($row);
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
      refreshLineItemRow($(this), $form);
    });
    ensureOdooHeaderColumns($table);
    applyLineItemColgroup($table);
    initTotalsOdoo($form);
    syncTotalsDisplay($form);
    syncAllRowAmounts($form);
    syncLineDeleteVisibility($form);
    syncCreditTermsVisibility($form);
    syncAllProductSelectDisplays($form);
    markInventoryUiReady();
  }

  function scheduleLineItemsRestyle($form, delays) {
    if (!$form || !$form.length) {
      return;
    }
    if (!delays) {
      delays = [0, 120, 320, 650, 1100, 1800, 2800];
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
      [80, 250, 600, 1200, 2000, 3200],
    );
    setTimeout(markInventoryUiReady, 2000);
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
    var safePct = parseFloat(taxPct);
    if (isNaN(safePct) || safePct < 0) {
      safePct = 0;
    }
    var $taxRow = $result.find("#group_tax_row");
    if (!$taxRow.length) {
      return;
    }
    $taxRow
      .removeClass("mk-inv-totals-hide hide")
      .addClass("mk-inv-totals-row mk-inv-totals-row--tax");
    $taxRow
      .find("td:first")
      .html('<div class="mk-inv-totals-label">Thuế GTGT</div>');
    $taxRow.find("#tax_final").addClass("mk-inv-vnd-amount");
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

    ensureGroupTaxMode($form);

    var preTax = readAmountRaw(
      $result.find("#preTaxTotal"),
      $result.find("#pre_tax_total"),
    );
    if (preTax <= 0) {
      preTax = sumLinePreTax($form);
    }

    var taxPct = getPrimaryTaxPercent($form);
    var taxAmt = readAmountRaw($result.find("#tax_final"));
    if (taxAmt <= 0 && preTax > 0 && taxPct > 0) {
      taxAmt = Math.round((preTax * taxPct) / 100);
    }

    var grand = readAmountRaw(
      $result.find("#grandTotal, .grandTotal"),
      $result.find("#total"),
    );
    if (grand <= preTax && taxAmt > 0) {
      grand = preTax + taxAmt;
    } else if (grand <= 0 && preTax > 0) {
      grand = preTax + taxAmt;
    }

    writeAmountDisplay($result.find("#preTaxTotal"), preTax);
    writeAmountDisplay($result.find("#tax_final"), taxAmt);
    writeAmountDisplay($result.find("#grandTotal, .grandTotal"), grand);

    $result.find("#pre_tax_total").val(preTax);
    $form.find('#total, input[name="total"]').val(grand);
    $form.find(".groupTaxTotal").first().val(taxAmt);
    $result.find("#tax_final").attr("data-raw", taxAmt);

    ensureTaxTotalsRowVisible($result, taxPct);

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
        var taxSum = 0;
        $form.find("tr.lineItemRow").each(function () {
          var $r = $(this);
          var qty = parseMoney($r.find(".qty").val());
          var price = parseMoney($r.find(".listPrice").val());
          var preTax = qty * price;
          var taxPct = getRowTaxPercent($r, $form);
          var lineTax = Math.round((preTax * taxPct) / 100);
          var lineTotal = preTax + lineTax;
          preTaxSum += preTax;
          taxSum += lineTax;
          var $pt = $r.find(".productTotal");
          if ($pt.length) {
            $pt.data("mkRawAmount", lineTotal);
            writeAmountDisplay($pt, lineTotal);
          }
        });
        var grand = preTaxSum + taxSum;
        var $result = $form.find("#lineItemResult");
        if ($result.length) {
          writeAmountDisplay($result.find("#netTotal, .netTotal"), preTaxSum);
          $result.find('#subtotal, input[name="subtotal"]').val(preTaxSum);
          writeAmountDisplay($result.find("#preTaxTotal"), preTaxSum);
          $result.find("#pre_tax_total").val(preTaxSum);
          writeAmountDisplay($result.find("#tax_final"), taxSum);
          $form.find(".groupTaxTotal").first().val(taxSum);
          writeAmountDisplay($result.find("#grandTotal, .grandTotal"), grand);
          $form.find('#total, input[name="total"]').val(grand);
          var taxPct = getPrimaryTaxPercent($form);
          ensureTaxTotalsRowVisible($result, taxPct);
        }
        setTimeout(function () {
          $form.data("mkInvSyncingTotals", false);
        }, 50);
      }, 100);
    }

    $form.on(
      "focusout.mkInvTot change.mkInvTot",
      ".qty, .listPrice, .taxPercentage, .groupTaxPercentage",
      function () {
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
          // Do not rebuild rows on qty/price edit — that steals focus and blocks typing.
          syncTotalsDisplay($form);
        }, 60);
      },
    );

    $form.on(
      "input.mkInvTotRealtime keyup.mkInvTotRealtime change.mkInvTotRealtime",
      ".qty, .listPrice",
      function () {
        var $row = $(this).closest("tr.lineItemRow");
        if ($row.length && $(this).is("input.qty, .qty")) {
          enforceQtyAgainstStock($row, $form, { silent: true });
        }
        scheduleRealtimeSync();
        if (!$form.data("mkInvApplyingTier")) {
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

    var _lastPriceSnapshot = {};
    setInterval(function () {
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
      if (
        changed &&
        !isLineItemRestylePaused($form) &&
        !isAnyTaxSelectOpen($form)
      ) {
        scheduleRealtimeSync();
      }
    }, 500);

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
        "input.qty, .qty, input.listPrice, .listPrice, .mk-inv-unit-select, .mk-inv-tax-select, textarea, input[type='text'], input[type='number']",
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

  function addProductFromQuickSearch($form, productId, meta) {
    if (!$form || !$form.length || !productId) {
      return;
    }
    if ($form.data("mkInvQuickAdding")) {
      return;
    }
    $form.data("mkInvQuickAdding", true);
    var $row = findEmptyProductLineRow($form);
    if (!$row || !$row.length) {
      $row = createInventoryLineItemRow(
        $form,
        $form.find("#addProductsServices").first(),
      );
    } else {
      styleNewLineItemFast($form, $row);
    }

    function tryApply(attempt) {
      if (!$row || !$row.length || !$row.closest("body").length) {
        $form.removeData("mkInvQuickAdding");
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
        setTimeout(function () {
          var $qty = $row.find("input.qty, .qty").first();
          if ($qty.length) {
            ensureQtyEditable($row);
            try {
              $qty.focus().select();
            } catch (ignoreFocus) {
              /* ignore */
            }
          }
          if (typeof $form.data("mkRefreshQuickSearch") === "function") {
            $form.data("mkRefreshQuickSearch")();
          }
        }, 80);
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
    if (!$wrap.length) {
      return;
    }
    // Công nợ chỉ áp dụng cho dòng dịch vụ — sản phẩm thường không hiện.
    var show = hasSelectedServiceLines($form);
    $wrap.toggleClass("mk-inv-credit-terms--visible", show);
    $wrap.attr("aria-hidden", show ? "false" : "true");
    if (!show) {
      return;
    }
    var $sel = $wrap.find('[name="mk_payment_terms"]').first();
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
        : "auto";
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
    var currentValue = String($existing.val() || "auto").trim();
    if ($existing.length) {
      $existing.closest("tr").addClass("mk-inv-hide-legacy");
    }

    var $select;
    if ($existing.length && $existing.is("select")) {
      $select = $existing.detach().attr("id", "mkInvInvoicePriceTierSelect");
    } else {
      $select = $(
        '<select name="mk_invoice_price_tier" id="mkInvInvoicePriceTierSelect"></select>',
      );
    }
    currentValue = rebuildInvoiceTierSelect($select, currentValue);

    var chipsHtml = INVOICE_TIER_OPTIONS.map(function (item) {
      return (
        '<button type="button" class="mk-inv-tier-chip" data-value="' +
        $("<div>").text(item.value).html() +
        '" aria-pressed="false">' +
        $("<div>").text(item.label).html() +
        "</button>"
      );
    }).join("");

    var $wrap = $(
      '<div class="mk-inv-price-tier mk-inv-price-tier--modern"></div>',
    );
    $wrap.append(
      '<div class="mk-inv-price-tier__row">' +
        '<span class="mk-inv-price-tier__icon" aria-hidden="true"></span>' +
        '<div class="mk-inv-price-tier__content">' +
        '<div class="mk-inv-price-tier__head">' +
        '<label class="mk-inv-price-tier__label" for="mkInvInvoicePriceTierSelect">Bảng giá</label>' +
        '<span class="mk-inv-price-tier__hint">Đơn giá lấy từ Hàng hoá và cập nhật khi tổng đơn thay đổi</span>' +
        "</div>" +
        '<div class="mk-inv-price-tier__chips" role="group" aria-label="Bảng giá">' +
        chipsHtml +
        "</div>" +
        '<div class="mk-inv-price-tier__status" aria-live="polite"></div>' +
        '<div class="mk-inv-price-tier__field--sr"></div>' +
        "</div></div>",
    );
    $wrap.find(".mk-inv-price-tier__field--sr").append($select);
    $container.prepend($wrap);

    $wrap.on("click", ".mk-inv-tier-chip", function (event) {
      event.preventDefault();
      var value = $(this).attr("data-value") || "auto";
      $select.val(value).trigger("change");
    });
    $select.on("change.mkInvPriceTier", function () {
      applyInvoiceTierPricing($form);
    });

    var resolved =
      currentValue === "auto"
        ? resolveInvoiceTierFromTotal(sumLinePreTax($form))
        : currentValue;
    syncInvoiceTierUi($form, currentValue, resolved);
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

  function persistRawTotalsBeforeSubmit($form) {
    if (!$form || !$form.length) {
      return;
    }
    ensureGroupTaxMode($form);

    var preTax = sumLinePreTax($form);
    if (preTax <= 0) {
      preTax = readAmountRaw(
        $form.find("#preTaxTotal, #netTotal, .netTotal"),
        $form.find("#pre_tax_total, #subtotal"),
      );
    }

    var taxPct = clampTaxPercent(getPrimaryTaxPercent($form));
    var taxAmt = 0;
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
    // Sanity: tax must not exceed the goods amount.
    if (preTax > 0 && taxAmt > preTax) {
      taxAmt = Math.round((preTax * Math.min(taxPct, 100)) / 100);
      if (taxAmt > preTax) {
        taxAmt = Math.round(preTax * 0.08);
        taxPct = 8;
      }
    }

    var grand = preTax + taxAmt;
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

  function initTotalsOdoo($form) {
    var $result = $form.find("#lineItemResult");
    if (!$result.length || $result.data("mkInvTotalsOdoo")) {
      return;
    }
    $result.data("mkInvTotalsOdoo", true);
    var $block = $result.closest(".fieldBlockContainer");
    $block.addClass("mk-inv-totals-odoo");

    $result.find("tr").addClass("mk-inv-totals-hide");

    var $grand = $result.find("#grandTotal, .grandTotal").closest("tr");
    var $preTax = $result.find("#preTaxTotal").closest("tr");
    var $net = $result.find("#netTotal, .netTotal").closest("tr");
    var $sub = $preTax.length ? $preTax : $net;

    if ($sub.length) {
      $sub
        .removeClass("mk-inv-totals-hide")
        .addClass("mk-inv-totals-row mk-inv-totals-row--sub");
      $sub
        .find("td:first")
        .html('<div class="mk-inv-totals-label">Số tiền trước thuế</div>');
      $sub
        .find("#preTaxTotal, #netTotal, .netTotal")
        .addClass("mk-inv-vnd-amount");
    }
    var $taxRow = $result.find("#group_tax_row");
    if ($taxRow.length) {
      $taxRow
        .removeClass("hide mk-inv-totals-hide")
        .addClass("mk-inv-totals-row mk-inv-totals-row--tax");
      $taxRow.find("#tax_final").addClass("mk-inv-vnd-amount");
    }
    if ($grand.length) {
      $grand
        .removeClass("mk-inv-totals-hide")
        .addClass("mk-inv-totals-row mk-inv-totals-row--grand");
      $grand
        .find("td:first")
        .html('<div class="mk-inv-totals-label">Tổng cộng</div>');
      $grand.find("#grandTotal, .grandTotal").addClass("mk-inv-vnd-amount");
    }

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

      initOdooTabs($lineBlock);
      initInvoicePriceTier($form);
      initPaymentTerms($form);
      initAddLineButton($form);
      initLineActionLinks();
      polishLineItemsShell($form);
      ensureModernLineItemsTable($form);
      bindInventoryRestyleHooks($form);

      loadProductCatalog().always(function () {
        initQuickProductSearch($form);
        scheduleLineItemsRestyle($form, [0, 200, 500]);
        scheduleInvoiceTierPricing($form, 0);
      });

      $form
        .off("post.lineItem.New.mkInvOdoo")
        .on("post.lineItem.New.mkInvOdoo", function (e, newLineItem) {
          handleNewLineItemRow($form, newLineItem);
        });
    }

    scheduleLineItemsRestyle($form);
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
    restyleLineItemRows: restyleLineItemRows,
    scheduleLineItemsRestyle: scheduleLineItemsRestyle,
    initQuickProductSearch: initQuickProductSearch,
    syncLineDeleteVisibility: syncLineDeleteVisibility,
    syncTotalsDisplay: syncTotalsDisplay,
    refreshTotals: function ($form) {
      initTotalsOdoo($form);
      syncTotalsDisplay($form);
    },
  };
})(jQuery);
