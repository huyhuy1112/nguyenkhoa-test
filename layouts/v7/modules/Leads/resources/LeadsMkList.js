/* Leads list — SALES modern UI (dummy data + client-side filters). */
(function () {
  "use strict";

  function $(id) {
    return document.getElementById(id);
  }

  function formatVnd(n) {
    try {
      return new Intl.NumberFormat("vi-VN").format(n) + " ₫";
    } catch (e) {
      return String(n) + " ₫";
    }
  }

  function normalize(s) {
    return String(s || "")
      .toLowerCase()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .trim();
  }

  var ALL = [
    {
      id: "L004",
      name: "Phạm Quốc Dũng",
      phone: "0978 111 222",
      type: "Franchise",
      stage: "New Purchase",
      tier: "Gold",
      owner: "Hà",
      lastTouch: "Today",
      nextAction: "Ký nhượng quyền khu vực",
      value: 48000000,
      priority: null,
      stale: false,
    },
    {
      name: "Cao Thanh Tùng",
      phone: "0904 555 013",
      type: "PCTH Program",
      stage: "Repeat Purchase",
      tier: "Gold",
      owner: "Linh",
      lastTouch: "Today",
      nextAction: "Demo PCTH advanced",
      value: 28400000,
      priority: "HIGH",
      stale: false,
    },
    {
      name: "Nguyễn Văn An",
      phone: "0901 234 567",
      type: "Free Class",
      stage: "New Purchase",
      tier: "Gold",
      owner: "Linh",
      lastTouch: "1d ago",
      nextAction: "Gọi tư vấn khoá nâng cao",
      value: 12000000,
      priority: null,
      stale: false,
    },
    {
      name: "Tô Quang Long",
      phone: "0909 121 212",
      type: "PCTH Program",
      stage: "Repeat Purchase",
      tier: "Gold",
      owner: "Hà",
      lastTouch: "1d ago",
      nextAction: "Ký hợp đồng PCTH",
      value: 19800000,
      priority: "HIGH",
      stale: false,
    },
    {
      name: "Bùi Phương Mai",
      phone: "0909 555 018",
      type: "PCTH Program",
      stage: "New Purchase",
      tier: "Gold",
      owner: "Hà",
      lastTouch: "1d ago",
      nextAction: "Demo onsite",
      value: 11300000,
      priority: null,
      stale: false,
    },
    // Fill up to 21 rows (dummy, UI-only)
    {
      name: "Đỗ Thanh Giang",
      phone: "0945 678 901",
      type: "PCTH Program",
      stage: "New Purchase",
      tier: "Gold",
      owner: "Linh",
      lastTouch: "2d ago",
      nextAction: "Tư vấn MKT package",
      value: 15000000,
      priority: null,
      stale: false,
    },
    {
      name: "Lý Thiên Hương",
      phone: "0906 555 011",
      type: "Franchise",
      stage: "Not Buying",
      tier: "Silver",
      owner: "Minh",
      lastTouch: "2d ago",
      nextAction: "Tư vấn franchise",
      value: 14200000,
      priority: null,
      stale: false,
    },
    {
      name: "Lê Minh Châu",
      phone: "0987 654 321",
      type: "Free Class",
      stage: "New Purchase",
      tier: "Silver",
      owner: "Linh",
      lastTouch: "3d ago",
      nextAction: "Gửi tài liệu free",
      value: 3200000,
      priority: null,
      stale: false,
    },
    {
      name: "Hà Bảo Trân",
      phone: "0908 777 333",
      type: "Franchise",
      stage: "New Purchase",
      tier: "Gold",
      owner: "Hà",
      lastTouch: "3d ago",
      nextAction: "Chốt franchise quận 7",
      value: 22000000,
      priority: null,
      stale: false,
    },
    {
      name: "Mai Thu Hương",
      phone: "0922 555 666",
      type: "Franchise",
      stage: "Repeat Purchase",
      tier: "Gold",
      owner: "Hà",
      lastTouch: "4d ago",
      nextAction: "Lên kế hoạch nhượng quyền",
      value: 32000000,
      priority: "HIGH",
      stale: false,
    },
    {
      name: "Ngô Quỳnh Anh",
      phone: "0907 555 016",
      type: "PCTH Program",
      stage: "New Purchase",
      tier: "Silver",
      owner: "Linh",
      lastTouch: "4d ago",
      nextAction: "Tư vấn lớp vận hành",
      value: 7800000,
      priority: null,
      stale: false,
    },
    {
      name: "Vũ Hồng Phúc",
      phone: "0966 222 333",
      type: "PCTH Program",
      stage: "Repeat Purchase",
      tier: "Silver",
      owner: "Hà",
      lastTouch: "5d ago",
      nextAction: "Demo lớp vận hành",
      value: 8900000,
      priority: null,
      stale: false,
    },
    {
      name: "Phan Văn Nam",
      phone: "0901 555 010",
      type: "PCTH Program",
      stage: "New Purchase",
      tier: "Silver",
      owner: "Linh",
      lastTouch: "6d ago",
      nextAction: "Demo phần mềm vận hành",
      value: 9500000,
      priority: null,
      stale: false,
    },
    {
      name: "Ngô Việt Khôi",
      phone: "0911 777 888",
      type: "PCTH Program",
      stage: "New Purchase",
      tier: "Silver",
      owner: "Linh",
      lastTouch: "8d ago",
      nextAction: "Mời học thử PCTH",
      value: 6700000,
      priority: null,
      stale: true,
    },
    {
      name: "Đinh Khả Vy",
      phone: "0905 555 014",
      type: "Free Class",
      stage: "New Purchase",
      tier: "Bronze",
      owner: "Minh",
      lastTouch: "9d ago",
      nextAction: "Mời học miễn phí",
      value: 2100000,
      priority: null,
      stale: true,
    },
    {
      name: "Trần Thị Bình",
      phone: "0912 345 678",
      type: "PCTH Program",
      stage: "Repeat Purchase",
      tier: "Gold",
      owner: "Minh",
      lastTouch: "10d ago",
      nextAction: "Chốt hợp đồng PCTH",
      value: 25500000,
      priority: "HIGH",
      stale: true,
    },
    {
      name: "Trịnh Hoàng Sơn",
      phone: "0903 555 012",
      type: "PCTH Program",
      stage: "New Purchase",
      tier: "Bronze",
      owner: "Hà",
      lastTouch: "11d ago",
      nextAction: "Gửi báo giá MKT",
      value: 5500000,
      priority: null,
      stale: true,
    },
    {
      name: "Hoàng Thu Em",
      phone: "0934 567 890",
      type: "Free Class",
      stage: "Stopped",
      tier: "Bronze",
      owner: "Minh",
      lastTouch: "14d ago",
      nextAction: "Follow-up sau 2 tuần",
      value: 1500000,
      priority: null,
      stale: true,
    },
    {
      name: "Lê Trọng Đạt",
      phone: "0908 555 321",
      type: "PCTH Program",
      stage: "Repeat Purchase",
      tier: "Silver",
      owner: "Minh",
      lastTouch: "15d ago",
      nextAction: "Follow-up sau khóa thử",
      value: 17500000,
      priority: null,
      stale: true,
    },
    {
      name: "Bùi Khánh Hà",
      phone: "0903 333 444",
      type: "PCTH Program",
      stage: "Stopped",
      tier: "Bronze",
      owner: "Minh",
      lastTouch: "20d ago",
      nextAction: "Khảo sát lý do ngưng",
      value: 4500000,
      priority: null,
      stale: true,
    },
    {
      name: "Đặng Thảo Linh",
      phone: "0988 999 000",
      type: "Free Class",
      stage: "Not Buying",
      tier: "Bronze",
      owner: "Minh",
      lastTouch: "30d ago",
      nextAction: "Đóng lead",
      value: 0,
      priority: null,
      stale: true,
    },
  ];

  var SOURCE_POOL = ["Facebook", "TikTok", "Website", "Zalo", "Other"];

  var FILTER_DEFS = {
    source: { field: "source", label: "Source", options: ["All"].concat(SOURCE_POOL) },
    purchase: {
      field: "stage",
      label: "Purchase",
      options: ["All", "New Purchase", "Repeat Purchase", "Not Buying", "Stopped"],
    },
    tier: { field: "tier", label: "Tier", options: ["All", "Gold", "Silver", "Bronze"] },
    program: {
      field: "program",
      label: "Program",
      options: ["All", "Franchise", "PCTH Program", "Free Class"],
    },
    owner: { field: "owner", label: "Owner", options: ["All", "Hà", "Linh", "Minh"] },
  };

  var state = {
    q: "",
    staleOnly: false,
    source: "All",
    purchase: "All",
    tier: "All",
    program: "All",
    owner: "All",
  };

  function chipClass(kind, val) {
    var t = normalize(val);
    if (kind === "type") return "mk-chip mk-chip--blue";
    if (kind === "stage") return "mk-chip mk-chip--purple";
    if (kind === "tier") {
      if (t.indexOf("silver") >= 0) return "mk-chip mk-chip--silver";
      if (t.indexOf("bronze") >= 0) return "mk-chip mk-chip--bronze";
      return "mk-chip mk-chip--gold";
    }
    return "mk-chip";
  }

  ALL.forEach(function (x, i) {
    if (!x.id) {
      x.id = "L" + String(i + 1).padStart(3, "0");
    }
    if (!x.source) {
      x.source = SOURCE_POOL[i % SOURCE_POOL.length];
    }
    if (!x.program) {
      x.program = x.type;
    }
  });

  function detailUrl(id) {
    return (
      "index.php?module=Leads&view=Detail&record=" +
      encodeURIComponent(id) +
      "&app=SALES"
    );
  }

  function rowHtml(x) {
    var high = x.priority === "HIGH";
    return (
      '<div class="mk-leads-tr' +
      (high ? " mk-leads-tr--high" : "") +
      '">' +
      '<div class="mk-leads-td mk-leads-lead">' +
      (high
        ? '<div class="mk-leads-priority"><span class="mk-fire" aria-hidden="true">♨</span> HIGH</div>'
        : "") +
      '<div class="mk-leads-lead__name">' +
      '<a class="mk-leads-lead__link" href="' +
      detailUrl(x.id) +
      '">' +
      x.name +
      "</a></div>" +
      '<div class="mk-leads-lead__sub">' +
      x.phone +
      "</div>" +
      "</div>" +
      '<div class="mk-leads-td"><span class="' +
      chipClass("type", x.type) +
      '">' +
      x.type +
      "</span></div>" +
      '<div class="mk-leads-td"><span class="' +
      chipClass("stage", x.stage) +
      '">' +
      x.stage +
      "</span></div>" +
      '<div class="mk-leads-td"><span class="' +
      chipClass("tier", x.tier) +
      '">' +
      x.tier +
      "</span></div>" +
      '<div class="mk-leads-td">' +
      x.owner +
      "</div>" +
      '<div class="mk-leads-td">' +
      x.lastTouch +
      "</div>" +
      '<div class="mk-leads-td' +
      (x.nextAction && x.nextAction.length > 22 ? " mk-leads-muted" : "") +
      '">' +
      x.nextAction +
      "</div>" +
      '<div class="mk-leads-td mk-leads-td--right">' +
      formatVnd(x.value) +
      "</div>" +
      '<div class="mk-leads-td mk-leads-td--center">' +
      (x.stale ? '<span class="mk-stale">Stale</span>' : '<span class="mk-dot mk-dot--ok"></span>') +
      "</div>" +
      "</div>"
    );
  }

  function matchesFilter(key, row) {
    var def = FILTER_DEFS[key];
    var val = state[key];
    if (!def || !val || val === "All") {
      return true;
    }
    return String(row[def.field] || "") === val;
  }

  function apply() {
    var qn = normalize(state.q);
    var list = ALL.filter(function (x) {
      if (state.staleOnly && !x.stale) return false;
      if (!matchesFilter("source", x)) return false;
      if (!matchesFilter("purchase", x)) return false;
      if (!matchesFilter("tier", x)) return false;
      if (!matchesFilter("program", x)) return false;
      if (!matchesFilter("owner", x)) return false;
      if (!qn) return true;
      var hay = normalize(x.name + " " + x.phone);
      return hay.indexOf(qn) !== -1;
    });

    var host = $("mk-leads-rows");
    if (!host) return;
    host.innerHTML = list.map(rowHtml).join("");

    var count = $("mk-leads-count");
    if (count) count.textContent = list.length + " of " + ALL.length;
  }

  function setToggle(btn, on) {
    btn.setAttribute("aria-checked", on ? "true" : "false");
    btn.classList.toggle("is-on", on);
  }

  function closeAllFilterMenus(exceptWrap) {
    document.querySelectorAll(".mk-leads-filter--dropdown.is-open").forEach(function (wrap) {
      if (exceptWrap && wrap === exceptWrap) return;
      wrap.classList.remove("is-open");
      var btn = wrap.querySelector(".mk-leads-filter__btn");
      var menu = wrap.querySelector(".mk-leads-filter__menu");
      if (btn) btn.setAttribute("aria-expanded", "false");
      if (menu) menu.hidden = true;
    });
  }

  function setFilterButtonLabel(wrap, value) {
    var btn = wrap.querySelector(".mk-leads-filter__btn");
    if (!btn) return;
    var label = value === "All" ? "All" : value;
    btn.innerHTML = label + ' <span class="mk-leads-filter__chev">▾</span>';
  }

  function bindFilterDropdowns() {
    document.querySelectorAll(".mk-leads-filter--dropdown").forEach(function (wrap) {
      var key = wrap.getAttribute("data-filter");
      var def = FILTER_DEFS[key];
      if (!def) return;

      var menu = wrap.querySelector(".mk-leads-filter__menu");
      var btn = wrap.querySelector(".mk-leads-filter__btn");
      if (!menu || !btn) return;

      menu.innerHTML = def.options
        .map(function (opt) {
          var active = state[key] === opt ? " is-active" : "";
          return (
            '<li role="presentation"><button type="button" class="mk-leads-filter__option' +
            active +
            '" data-value="' +
            opt.replace(/"/g, "&quot;") +
            '">' +
            opt +
            "</button></li>"
          );
        })
        .join("");

      setFilterButtonLabel(wrap, state[key]);

      btn.addEventListener("click", function (e) {
        e.stopPropagation();
        var open = wrap.classList.contains("is-open");
        closeAllFilterMenus();
        if (!open) {
          wrap.classList.add("is-open");
          menu.hidden = false;
          btn.setAttribute("aria-expanded", "true");
        }
      });

      menu.querySelectorAll(".mk-leads-filter__option").forEach(function (optBtn) {
        optBtn.addEventListener("click", function (e) {
          e.stopPropagation();
          state[key] = optBtn.getAttribute("data-value") || "All";
          menu.querySelectorAll(".mk-leads-filter__option").forEach(function (b) {
            b.classList.toggle("is-active", b === optBtn);
          });
          setFilterButtonLabel(wrap, state[key]);
          closeAllFilterMenus();
          apply();
        });
      });
    });

    document.addEventListener("click", function () {
      closeAllFilterMenus();
    });
  }

  function resetFilters() {
    state.q = "";
    state.staleOnly = false;
    state.source = "All";
    state.purchase = "All";
    state.tier = "All";
    state.program = "All";
    state.owner = "All";
  }

  function init() {
    var search = $("mk-leads-search");
    if (search) {
      search.addEventListener("input", function () {
        state.q = search.value || "";
        apply();
      });
    }

    var stale = $("mk-leads-toggle-stale");
    if (stale) {
      stale.addEventListener("click", function () {
        state.staleOnly = !state.staleOnly;
        setToggle(stale, state.staleOnly);
        apply();
      });
    }

    var reset = $("mk-leads-reset");
    if (reset) {
      reset.addEventListener("click", function () {
        resetFilters();
        if (search) search.value = "";
        if (stale) setToggle(stale, false);
        document.querySelectorAll(".mk-leads-filter--dropdown").forEach(function (wrap) {
          var key = wrap.getAttribute("data-filter");
          if (key && FILTER_DEFS[key]) {
            setFilterButtonLabel(wrap, "All");
            var menu = wrap.querySelector(".mk-leads-filter__menu");
            if (menu) {
              menu.querySelectorAll(".mk-leads-filter__option").forEach(function (b) {
                b.classList.toggle("is-active", b.getAttribute("data-value") === "All");
              });
            }
          }
        });
        apply();
      });
    }

    bindFilterDropdowns();
    apply();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();

