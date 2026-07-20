/**
 * Warehouse inbound/outbound line items: quick product search (Quote/SO style).
 * GoodsReceipt → Products & Services catalog.
 * GoodsIssue → warehouse stock (SearchProducts).
 */
(function ($) {
  "use strict";
  if (!$) {
    return;
  }

  var catalogCache = null;
  var catalogPromise = null;
  var giStockOptions = null;
  var giStockPromise = null;

  function escapeHtml(str) {
    return String(str == null ? "" : str)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function normalizeSearchText(v) {
    return String(v || "")
      .toLowerCase()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .replace(/\s+/g, " ")
      .trim();
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

  function mapCatalogType(raw) {
    var v = String(raw || "")
      .trim()
      .toLowerCase();
    if (v === "hardware" || v === "product" || v === "products") {
      return "Hardware";
    }
    if (v === "software") {
      return "Software";
    }
    if (v === "service" || v === "services") {
      return "Service";
    }
    return "Other";
  }

  function loadProductCatalog(forceReload) {
    if (!forceReload && catalogCache) {
      return $.Deferred().resolve(catalogCache).promise();
    }
    if (!forceReload && catalogPromise) {
      return catalogPromise;
    }
    catalogPromise = $.Deferred();
    if (
      typeof window !== "undefined" &&
      window.MK_PRODUCT_CATALOG &&
      window.MK_PRODUCT_CATALOG.length
    ) {
      catalogCache = window.MK_PRODUCT_CATALOG;
      catalogPromise.resolve(catalogCache);
      return catalogPromise.promise();
    }
    if (typeof app === "undefined" || !app.request) {
      catalogCache = [];
      catalogPromise.resolve([]);
      return catalogPromise.promise();
    }
    app.request
      .post({ data: { module: "Inventory", action: "ProductCatalog" } })
      .then(function (err, res) {
        catalogCache = !err && res && res.products ? res.products : [];
        catalogPromise.resolve(catalogCache);
      });
    return catalogPromise.promise();
  }

  function loadGoodsIssueStockOptions(forceReload) {
    if (!forceReload && giStockOptions) {
      return $.Deferred().resolve(giStockOptions).promise();
    }
    if (!forceReload && giStockPromise) {
      return giStockPromise;
    }
    giStockPromise = $.Deferred();
    $.ajax({
      url: "index.php",
      data: { module: "GoodsIssue", action: "SearchProducts", q: "" },
      dataType: "json",
    })
      .done(function (data) {
        giStockOptions =
          data && data.result && data.result.options ? data.result.options : [];
        giStockPromise.resolve(giStockOptions);
      })
      .fail(function () {
        giStockOptions = [];
        giStockPromise.resolve([]);
      });
    return giStockPromise.promise();
  }

  function catalogMatcher(term, text, option) {
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

  function formatCatalogResult(item) {
    if (!item || !item.id) {
      return item && item.text ? escapeHtml(item.text) : "";
    }
    var $opt = $(item.element);
    var name = $opt.attr("data-name") || item.text || "";
    var sku = ($opt.attr("data-sku") || "").trim();
    var price = formatPriceVi($opt.attr("data-price") || 0);
    var skuHtml = sku
      ? '<span class="mk-wh-s2-sku">' + escapeHtml(sku) + "</span>"
      : '<span class="mk-wh-s2-sku mk-wh-s2-sku--empty">chưa có mã hàng</span>';
    return (
      '<span class="mk-wh-s2-row">' +
      '<span class="mk-wh-s2-main">' +
      '<span class="mk-wh-s2-name">' +
      escapeHtml(name) +
      "</span>" +
      skuHtml +
      "</span>" +
      '<span class="mk-wh-s2-price">' +
      escapeHtml(price) +
      "</span>" +
      "</span>"
    );
  }

  function formatGiStockResult(item) {
    if (!item || !item.id) {
      return item && item.text ? escapeHtml(item.text) : "";
    }
    var $opt = $(item.element);
    var name = $opt.attr("data-name") || item.text || "";
    var avail = formatQtyShort($opt.attr("data-available") || 0);
    var loc = ($opt.attr("data-location") || "").trim();
    var meta =
      "Tồn: <strong>" +
      escapeHtml(avail) +
      "</strong>" +
      (loc ? " · " + escapeHtml(loc) : "");
    return (
      '<span class="mk-wh-s2-row">' +
      '<span class="mk-wh-s2-main">' +
      '<span class="mk-wh-s2-name">' +
      escapeHtml(name) +
      "</span>" +
      '<span class="mk-wh-s2-meta">' +
      meta +
      "</span>" +
      "</span>" +
      "</span>"
    );
  }

  function fillCatalogSelect($sel, products) {
    $sel.empty().append('<option value=""></option>');
    (products || []).forEach(function (p) {
      var id = String(p.id || "");
      if (!id) {
        return;
      }
      var name = String(p.name || id);
      var label = name + (p.sku ? " (" + p.sku + ")" : " (chưa có mã hàng)");
      $sel.append(
        $("<option></option>")
          .attr("value", id)
          .attr("data-name", name)
          .attr("data-price", p.price || 0)
          .attr("data-sku", p.sku || "")
          .attr("data-type", p.type || "")
          .text(label),
      );
    });
  }

  function fillGiStockSelect($sel, options) {
    $sel.empty().append('<option value=""></option>');
    (options || []).forEach(function (o, idx) {
      var key =
        (o.product_key && String(o.product_key)) ||
        "stock:" + String(o.stockid || idx);
      var name = String(o.name || "");
      if (!name) {
        return;
      }
      var label = name + " · Tồn " + formatQtyShort(o.available_qty || 0);
      $sel.append(
        $("<option></option>")
          .attr("value", key)
          .attr("data-name", name)
          .attr("data-productid", o.productid || 0)
          .attr("data-product-key", o.product_key || "")
          .attr("data-identity", o.identity_type || "")
          .attr("data-type", o.type || "Other")
          .attr("data-available", o.available_qty != null ? o.available_qty : 0)
          .attr("data-location", o.stock_location || "")
          .attr("data-unit-price", o.unit_price != null ? o.unit_price : 0)
          .attr("data-description", o.description || "")
          .text(label),
      );
    });
  }

  function mountSelect2($sel, config) {
    if (!$sel.length || typeof $.fn.select2 !== "function") {
      return;
    }
    if ($sel.data("select2")) {
      try {
        $sel.select2("destroy");
      } catch (ignore) {
        /* ignore */
      }
    }
    $sel.select2({
      placeholder: config.placeholder || "Tìm hàng hoá / mã SKU…",
      allowClear: true,
      width: "100%",
      dropdownCssClass: "mk-wh-s2-drop " + (config.dropdownClass || ""),
      minimumResultsForSearch: 0,
      matcher: config.matcher || catalogMatcher,
      formatResult: config.formatResult || formatCatalogResult,
      formatSelection: function (item) {
        if (!item || !item.id) {
          return "";
        }
        var $opt = $(item.element);
        return $opt.attr("data-name") || item.text || "";
      },
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
    $sel.off("change.mkWhQuick").on("change.mkWhQuick", function () {
      var val = ($(this).val() || "").trim();
      if (!val) {
        return;
      }
      var $opt = $(this).find("option:selected").first();
      if (config.onSelect) {
        config.onSelect($opt, val);
      }
      var self = this;
      setTimeout(function () {
        try {
          $(self).select2("val", "");
        } catch (ignoreVal) {
          $(self).val("");
        }
      }, 80);
    });
  }

  function isGrInvMode() {
    return !!document.querySelector(".mk-gr-edit-form");
  }

  function isGiInvMode() {
    return !!document.querySelector(".mk-go-edit-form");
  }

  function removeEmptyLineRows($tbody, nameSelector) {
    if (!$tbody || !$tbody.length) {
      return;
    }
    $tbody.find("tr").each(function () {
      var $name = $(this).find(nameSelector).first();
      if (!$name.length) {
        return;
      }
      if (!$.trim($name.val() || "")) {
        $(this).remove();
      }
    });
  }

  function lockProductNameInputs($scope) {
    $scope
      .find('input[name="item_product_name[]"]')
      .attr("readonly", "readonly")
      .attr("autocomplete", "off")
      .removeAttr("list")
      .addClass("mk-wh-line-product-name");
  }

  /* ---------- GoodsReceipt ---------- */

  function buildGrRowHtml(invMode, preset) {
    var p = preset || {};
    var inputCls = invMode ? "mk-gr-edit-input" : "form-control mk-gr-edit-input";
    var btnCls = invMode
      ? "mk-gi-btn mk-gi-btn--filter mk-gi-btn--ghost js-remove-row"
      : "btn btn-xs btn-danger js-remove-row";
    var btnLabel = invMode ? "Remove" : "x";
    var numCls = invMode ? ' class="mk-gi-table__num"' : "";
    var actCls = invMode ? ' class="mk-gi-table__actions"' : "";
    var type = mapCatalogType(p.type || "Other");
    return (
      "<tr>" +
      '<td><input type="hidden" name="item_productid[]" value="' +
      escapeHtml(p.productid || "") +
      '" />' +
      '<input type="text" name="item_product_name[]" class="' +
      inputCls +
      ' mk-wh-line-product-name" value="' +
      escapeHtml(p.name || "") +
      '" readonly="readonly" autocomplete="off" required="required" /></td>' +
      "<td><select name=\"item_product_type[]\" class=\"" +
      inputCls +
      ' js-product-type">' +
      '<option value="Hardware"' +
      (type === "Hardware" ? ' selected="selected"' : "") +
      ">Hardware</option>" +
      '<option value="Software"' +
      (type === "Software" ? ' selected="selected"' : "") +
      ">Software</option>" +
      '<option value="Service"' +
      (type === "Service" ? ' selected="selected"' : "") +
      ">Service</option>" +
      '<option value="Other"' +
      (type === "Other" ? ' selected="selected"' : "") +
      ">Other</option>" +
      "</select></td>" +
      '<td><input type="text" name="item_serial[]" class="' +
      inputCls +
      '" value="" /></td>' +
      '<td><input type="date" name="item_expired_date[]" class="' +
      inputCls +
      '" value="" /></td>' +
      "<td" +
      numCls +
      '><input type="number" step="0.0001" min="0" name="item_quantity[]" class="' +
      inputCls +
      '" value="1" required="required" /></td>' +
      "<td" +
      numCls +
      '><input type="number" step="0.0001" min="0" name="item_unit_price[]" class="' +
      inputCls +
      '" value="' +
      escapeHtml(p.price != null ? p.price : 0) +
      '" /></td>' +
      '<td><input type="text" name="description[]" class="' +
      inputCls +
      '" value="" /></td>' +
      '<td><input type="text" name="item_line_note[]" class="' +
      inputCls +
      '" value="" /></td>' +
      "<td" +
      actCls +
      '><button type="button" class="' +
      btnCls +
      '">' +
      btnLabel +
      "</button></td>" +
      "</tr>"
    );
  }

  function addGrRowFromCatalog($opt) {
    var $form = $("#GoodsReceiptEditForm, form.mk-gr-edit-form").first();
    var $tbody = $("#inboundItemsTable tbody");
    if (!$tbody.length) {
      return;
    }
    var invMode = isGrInvMode();
    var html = buildGrRowHtml(invMode, {
      productid: $opt.attr("value") || "",
      name: $opt.attr("data-name") || $opt.text(),
      type: $opt.attr("data-type") || "Other",
      price: $opt.attr("data-price") || 0,
    });
    $tbody.append(html);
    var $row = $tbody.find("tr").last();
    $row.find('input[name="item_quantity[]"]').focus().select();
  }

  function initGoodsReceipt() {
    var $form = $("#GoodsReceiptEditForm");
    if (!$form.length) {
      $form = $("form.mk-gr-edit-form").first();
    }
    if (!$form.length) {
      return;
    }
    var $tbody = $("#inboundItemsTable tbody");
    if (!$tbody.length) {
      return;
    }

    removeEmptyLineRows($tbody, 'input[name="item_product_name[]"]');
    lockProductNameInputs($form);
    $("#addInboundRow, .mk-gr-edit-add-row").remove();

    var $wrap = $form.find(".mk-wh-line-quick-search").first();
    if (!$wrap.length) {
      return;
    }
    var $sel = $wrap.find("select.mk-wh-quick-product-search").first();
    if (!$sel.length) {
      return;
    }

    $tbody.on("click", ".js-remove-row", function () {
      $(this).closest("tr").remove();
    });

    loadProductCatalog().then(function (products) {
      fillCatalogSelect($sel, products);
      mountSelect2($sel, {
        placeholder: "Tìm hàng hoá / mã SKU…",
        dropdownClass: "mk-wh-s2-catalog",
        onSelect: function ($opt) {
          addGrRowFromCatalog($opt);
        },
      });
    });
  }

  /* ---------- GoodsIssue ---------- */

  window.GoodsIssueRecalcRow = function (row) {
    var $row = row && row.jquery ? row : $(row);
    if (!$row.length) {
      return;
    }
    var qty = parseFloat($row.find(".qty-input").val() || "0");
    var price = parseFloat($row.find(".gi-unit-price").val() || "0");
    var disc = parseFloat($row.find(".gi-discount").val() || "0");
    if (isNaN(qty)) {
      qty = 0;
    }
    if (isNaN(price)) {
      price = 0;
    }
    if (isNaN(disc)) {
      disc = 0;
    }
    if (disc < 0) {
      disc = 0;
    }
    if (disc > 100) {
      disc = 100;
    }
    $row.find(".gi-discount").val(String(disc).replace(/\.00$/, ""));
    var lineTotal = qty * price * (1 - disc / 100);
    if (lineTotal < 0) {
      lineTotal = 0;
    }
    $row.find(".gi-line-total").text(lineTotal.toFixed(0));
  };

  function goodsIssueApplySerialOptions(select, data, initial) {
    var list = [];
    if (data && Array.isArray(data.result)) {
      list = data.result;
    } else if (data && data.serials && Array.isArray(data.serials)) {
      data.serials.forEach(function (s) {
        list.push({ serial: s });
      });
    }
    select.innerHTML = '<option value="">Chọn serial</option>';
    list.forEach(function (item) {
      var s =
        item && typeof item === "object" && item.serial !== undefined
          ? String(item.serial)
          : String(item);
      if (!s) {
        return;
      }
      var opt = document.createElement("option");
      opt.value = s;
      opt.textContent = s;
      select.appendChild(opt);
    });
    if (initial) {
      select.value = initial;
      if (select.value !== initial) {
        var opt2 = document.createElement("option");
        opt2.value = initial;
        opt2.textContent = initial;
        select.appendChild(opt2);
        select.value = initial;
      }
      select.removeAttribute("data-initial-serial");
    }
  }

  function loadGiSerials(row) {
    var select = row.querySelector(".serial-select");
    if (!select) {
      return;
    }
    var pidEl = row.querySelector('[name="item_productid[]"]');
    var nameEl = row.querySelector('[name="item_product_name[]"]');
    var typeEl = row.querySelector('[name="item_product_type[]"]');
    var pid = pidEl ? parseInt(pidEl.value || "0", 10) : 0;
    if (isNaN(pid)) {
      pid = 0;
    }
    var productName = nameEl && nameEl.value ? nameEl.value.trim() : "";
    var productKey = (row.dataset.giProductKey || "").trim();
    var productType = typeEl && typeEl.value ? typeEl.value.trim() : "";
    if (pid <= 0 && !productName && !productKey) {
      select.innerHTML = '<option value="">Chọn serial</option>';
      return;
    }
    var initial = (select.getAttribute("data-initial-serial") || "").trim();
    var parts = ["module=GoodsIssue", "action=GetSerials"];
    if (pid > 0) {
      parts.push("productid=" + encodeURIComponent(String(pid)));
    }
    if (productName) {
      parts.push("product_name=" + encodeURIComponent(productName));
    }
    if (productKey) {
      parts.push("product_key=" + encodeURIComponent(productKey));
    }
    if (productType) {
      parts.push("product_type=" + encodeURIComponent(productType));
    }
    var url = "index.php?" + parts.join("&");
    if (typeof fetch === "function") {
      fetch(url)
        .then(function (res) {
          return res.json();
        })
        .then(function (data) {
          goodsIssueApplySerialOptions(select, data, initial);
        })
        .catch(function () {
          select.innerHTML = '<option value="">Chọn serial</option>';
        });
    }
  }

  function applyGiStockToRow(row, optEl) {
    if (!row || !optEl) {
      return;
    }
    var $opt = $(optEl);
    var name = ($opt.attr("data-name") || "").trim();
    var identityType = ($opt.attr("data-identity") || "").trim().toLowerCase();
    var rawPid = ($opt.attr("data-productid") || "").trim();
    var isCatalog = identityType === "catalog" && rawPid !== "" && rawPid !== "0";

    var pidEl = row.querySelector('[name="item_productid[]"]');
    var nameEl = row.querySelector('[name="item_product_name[]"]');
    var typeEl = row.querySelector('[name="item_product_type[]"]');
    var priceEl = row.querySelector('[name="item_unit_price[]"]');
    var descEl = row.querySelector('[name="description[]"]');
    var qtyEl = row.querySelector(".qty-input");
    var badge = row.querySelector(".available-badge");
    var meta = row.querySelector(".gi-stock-meta");
    var rowBadge = row.querySelector(".stock-badge");
    var warn = row.querySelector(".qty-warn");
    var keyInput = row.querySelector(".gi-product-key-input");

    if (pidEl) {
      pidEl.value = isCatalog ? rawPid : "";
    }
    if (nameEl) {
      nameEl.value = name;
    }
    if (typeEl) {
      typeEl.value = $opt.attr("data-type") || typeEl.value;
    }
    if (priceEl) {
      priceEl.value = $opt.attr("data-unit-price") || priceEl.value;
    }
    if (descEl) {
      descEl.value = $opt.attr("data-description") || "";
    }

    var available = $opt.attr("data-available") || "0";
    if (qtyEl) {
      qtyEl.dataset.available = available;
    }
    if (badge) {
      badge.innerText = isCatalog
        ? "Available: " + available
        : "Available: " + available + " (legacy)";
    }

    var form = document.getElementById("GoodsIssueEditForm");
    var headerStorageInput = form
      ? form.querySelector('input[name="storage_location"]')
      : null;
    var loc = ($opt.attr("data-location") || "").trim();
    if (
      headerStorageInput &&
      loc &&
      (!headerStorageInput.value || headerStorageInput.value.trim() === "")
    ) {
      headerStorageInput.value = loc;
    }

    if (meta) {
      meta.innerHTML =
        '<span class="available-text">Available: ' +
        available +
        "</span>" +
        '<span class="location-text">Location: ' +
        (loc || "—") +
        "</span>" +
        '<span class="type-text">Type: ' +
        ($opt.attr("data-type") || "Other") +
        "</span>";
    }
    if (rowBadge) {
      rowBadge.className = "stock-badge " + (isCatalog ? "catalog" : "legacy");
      rowBadge.innerText = isCatalog ? "✓ Catalog" : "⚠ Legacy";
    }
    row.classList.toggle("is-legacy", !isCatalog);

    var productKey = ($opt.attr("data-product-key") || "").trim();
    row.dataset.giProductKey = productKey || (name ? "N:" + name.toLowerCase() : "");
    row.dataset.giProductName = name;
    if (keyInput) {
      keyInput.value = row.dataset.giProductKey;
    }

    loadGiSerials(row);

    if (qtyEl) {
      var av = parseFloat(qtyEl.dataset.available || "");
      var qty = parseFloat(qtyEl.value || "0");
      if (!isNaN(av) && !isNaN(qty) && qty > av) {
        qtyEl.style.borderColor = "#ff4d4d";
        if (warn) {
          warn.style.display = "inline";
        }
      } else {
        qtyEl.style.borderColor = "";
        if (warn) {
          warn.style.display = "none";
        }
      }
    }
    if (typeof window.GoodsIssueRecalcRow === "function") {
      window.GoodsIssueRecalcRow(row);
    }
  }

  function buildGiRowHtml() {
    return (
      '<tr class="row-item is-legacy" data-gi-product-key="" data-gi-product-name="">' +
      "<td>" +
      '  <input type="hidden" name="item_productid[]" value="" />' +
      '  <input type="hidden" name="item_product_key[]" value="" class="gi-product-key-input" />' +
      '  <input type="text" name="item_product_name[]" value="" class="form-control product-input mk-wh-line-product-name" readonly="readonly" autocomplete="off" />' +
      '  <span class="stock-badge legacy">⚠ Legacy</span>' +
      '  <div class="stock-meta small text-muted gi-stock-meta">' +
      '    <span class="available-text">Available: —</span>' +
      '    <span class="location-text">Location: —</span>' +
      '    <span class="type-text">Type: Other</span>' +
      "  </div>" +
      '  <div class="legacy-warning text-warning small">⚠ This item is not linked to catalog (legacy stock)</div>' +
      "</td>" +
      "<td>" +
      '  <select name="item_product_type[]" class="form-control">' +
      '    <option value="Hardware">Hardware</option>' +
      '    <option value="Software">Software</option>' +
      '    <option value="Service">Service</option>' +
      '    <option value="Other" selected="selected">Other</option>' +
      "  </select>" +
      "</td>" +
      "<td>" +
      '  <select name="serial_number[]" class="form-control serial-select gi-serial-select">' +
      '    <option value="">Chọn serial</option>' +
      "  </select>" +
      "</td>" +
      "<td>" +
      '  <input type="number" step="1" min="0" name="item_quantity[]" value="1" class="form-control text-right qty-input" data-available="" />' +
      '  <div style="margin-top:4px;">' +
      '    <span class="available-badge text-muted small">Available: — (legacy)</span>' +
      "  </div>" +
      '  <small class="text-danger qty-warn" style="display:none;">Qty exceeds available</small>' +
      "</td>" +
      '<td><input type="number" step="0.0001" min="0" name="item_unit_price[]" value="0" class="form-control text-right gi-unit-price" /></td>' +
      '<td><input type="number" step="0.0001" min="0" max="100" name="item_discount[]" value="0" class="form-control text-right gi-discount" /></td>' +
      '<td class="text-right gi-line-total-cell"><span class="gi-line-total">0</span></td>' +
      '<td><textarea name="description[]" class="form-control gi-line-description" rows="2" placeholder="Inbound hint fills on product pick"></textarea></td>' +
      '<td><input type="text" name="item_line_note[]" value="" class="form-control" /></td>' +
      '<td class="text-nowrap"><button type="button" class="btn btn-xs btn-danger js-gi-remove">Remove</button></td>' +
      "</tr>"
    );
  }

  function addGiRowFromStock($opt) {
    var tbody = document.querySelector("#GoodsIssueItemsTable tbody");
    if (!tbody) {
      return;
    }
    tbody.insertAdjacentHTML("beforeend", buildGiRowHtml());
    var row = tbody.lastElementChild;
    applyGiStockToRow(row, $opt[0]);
    var qtyEl = row.querySelector(".qty-input");
    if (qtyEl) {
      try {
        qtyEl.focus();
        qtyEl.select();
      } catch (ignore) {
        /* ignore */
      }
    }
  }

  function initGoodsIssueFormHandlers() {
    var form = document.getElementById("GoodsIssueEditForm");
    if (!form) {
      return;
    }

    form.addEventListener("submit", function () {
      form.querySelectorAll("tr.row-item").forEach(function (row) {
        var keyInput = row.querySelector(".gi-product-key-input");
        if (!keyInput) {
          return;
        }
        var key = (row.dataset.giProductKey || "").trim();
        if (!key) {
          var nameInput = row.querySelector('input[name="item_product_name[]"]');
          var nameVal = nameInput ? nameInput.value.trim() : "";
          key = nameVal ? "N:" + nameVal.toLowerCase() : "";
        }
        keyInput.value = key;
      });
      if (typeof csrfMagicName !== "undefined" && typeof csrfMagicToken !== "undefined") {
        var existing = form.querySelector('input[name="' + csrfMagicName + '"]');
        if (!existing) {
          var hidden = document.createElement("input");
          hidden.type = "hidden";
          hidden.name = csrfMagicName;
          hidden.value = csrfMagicToken;
          form.appendChild(hidden);
        }
      }
    });

    document.addEventListener("input", function (e) {
      if (!e.target || !e.target.classList) {
        return;
      }
      if (e.target.classList.contains("qty-input")) {
        var qtyEl = e.target;
        var row = qtyEl.closest("tr");
        if (!row) {
          return;
        }
        var warn = row.querySelector(".qty-warn");
        var av = parseFloat(qtyEl.dataset.available || "");
        var qty = parseFloat(qtyEl.value || "0");
        if (!isNaN(av) && !isNaN(qty) && qty > av) {
          qtyEl.style.borderColor = "#ff4d4d";
          if (warn) {
            warn.style.display = "inline";
          }
        } else {
          qtyEl.style.borderColor = "";
          if (warn) {
            warn.style.display = "none";
          }
        }
      }
      if (
        e.target.classList.contains("qty-input") ||
        e.target.classList.contains("gi-unit-price") ||
        e.target.classList.contains("gi-discount")
      ) {
        var r = e.target.closest("tr");
        if (r && typeof window.GoodsIssueRecalcRow === "function") {
          window.GoodsIssueRecalcRow(r);
        }
      }
    });

    document.addEventListener("click", function (e) {
      if (e.target && e.target.classList && e.target.classList.contains("js-gi-remove")) {
        var tr = e.target.closest("tr");
        if (tr && tr.parentNode) {
          tr.parentNode.removeChild(tr);
        }
      }
    });

    document.addEventListener("change", function (e) {
      if (!e.target || e.target.name !== "item_product_type[]") {
        return;
      }
      var row = e.target.closest("tr");
      if (!row || !row.closest("#GoodsIssueItemsTable")) {
        return;
      }
      loadGiSerials(row);
    });

    document.querySelectorAll("#GoodsIssueItemsTable tbody tr").forEach(function (row) {
      loadGiSerials(row);
      if (typeof window.GoodsIssueRecalcRow === "function") {
        window.GoodsIssueRecalcRow(row);
      }
    });
  }

  function initGoodsIssue() {
    var $form = $("#GoodsIssueEditForm");
    if (!$form.length) {
      return;
    }
    var $tbody = $("#GoodsIssueItemsTable tbody");
    if (!$tbody.length) {
      return;
    }

    removeEmptyLineRows($tbody, 'input[name="item_product_name[]"]');
    lockProductNameInputs($form);
    $("#GoodsIssueAddRow, .mk-go-edit-add-row").remove();
    $("#products_list").remove();

    initGoodsIssueFormHandlers();

    var $wrap = $form.find(".mk-wh-line-quick-search").first();
    if (!$wrap.length) {
      return;
    }
    var $sel = $wrap.find("select.mk-wh-quick-product-search").first();
    if (!$sel.length) {
      return;
    }

    loadGoodsIssueStockOptions().then(function (options) {
      fillGiStockSelect($sel, options);
      mountSelect2($sel, {
        placeholder: "Tìm hàng trong kho…",
        dropdownClass: "mk-wh-s2-stock",
        matcher: catalogMatcher,
        formatResult: formatGiStockResult,
        onSelect: function ($opt) {
          addGiRowFromStock($opt);
        },
      });
    });
  }

  function bootstrap() {
    if ($("#GoodsReceiptEditForm").length || $("form.mk-gr-edit-form").length) {
      initGoodsReceipt();
    }
    if ($("#GoodsIssueEditForm").length) {
      initGoodsIssue();
    }
  }

  $(bootstrap);

  window.MkWarehouseLineEdit = {
    initGoodsReceipt: initGoodsReceipt,
    initGoodsIssue: initGoodsIssue,
  };
})(jQuery);
