/**
 * 21 demo leads — localStorage cache only (no DB).
 * Enriched with purchases + calendarTasks for commerce / Next Action spec.
 */
(function (root) {
  "use strict";

  function ago(days) {
    return new Date(Date.now() - days * 86400000).toISOString();
  }

  function purchaseDate(daysAgo) {
    var d = new Date(Date.now() - daysAgo * 86400000);
    var day = String(d.getDate()).padStart(2, "0");
    var mon = String(d.getMonth() + 1).padStart(2, "0");
    return day + "/" + mon + "/" + d.getFullYear();
  }

  var PRODUCTS = [
    "Syrup Caramel B-ACE 750ml",
    "Bột trà sữa B-ACE 1kg",
    "Gói PCTH Advanced",
    "Ly nhựa B-ACE 500ml",
    "Combo nguyên liệu khởi nghiệp",
  ];

  function enrichLead(lead, idx) {
    var purchases = [];
    var baseId = String(lead.id || "L").replace("L-", "");
    if ((lead.value || 0) > 0 && idx % 6 !== 5) {
      var orderLines = 1 + (idx % 3);
      var i;
      for (i = 0; i < orderLines; i++) {
        var daysAgo = i * 4 + (idx % 12);
        var qty = 4 + ((idx + i) % 9);
        purchases.push({
          orderId: "SO-" + baseId + "-" + (i + 1),
          product: PRODUCTS[(idx + i) % PRODUCTS.length],
          qty: qty,
          value: Math.max(500000, Math.round(((lead.value || 0) / orderLines) * (0.6 + i * 0.2))),
          date: purchaseDate(daysAgo),
        });
      }
    }

    var activityTypes = ["task", "call", "meeting"];
    var calendarTasks = [];
    if (lead.next_action) {
      calendarTasks.push({
        type: activityTypes[idx % 3],
        subject: lead.next_action,
        status: "open",
        dueAt: ago(idx % 4),
        dueLabel: idx % 4 === 0 ? "Today" : idx % 4 + "d ago",
      });
    }

    return Object.assign({}, lead, {
      purchases: purchases,
      calendarTasks: calendarTasks,
    });
  }

  var RAW_LEADS = [
    { id: "L-1001", name: "Phạm Quốc Dũng", phone: "0978 111 222", companyName: "Cafe Phố Cổ", area: "Quận 1, TP.HCM", tags: ["zalo", "da_hoc", "nhuong_quyen", "mua_lan_dau", "vang"], owner: "Hà", value: 48000000, last_touch: ago(0), next_action: "Ký nhượng quyền khu vực", segment: "co_quan", openTickets: 0 },
    { id: "L-1002", name: "Cao Thanh Tùng", phone: "0904 555 013", tags: ["website", "da_hoc", "pcth", "van_hanh", "mua_lai", "vang"], owner: "Linh", value: 28400000, last_touch: ago(0), next_action: "Demo PCTH advanced", openTickets: 0 },
    { id: "L-1003", name: "Nguyễn Văn An", phone: "0901 234 567", area: "Quận 1, TP.HCM", tags: ["facebook", "chua_hoc", "mien_phi_online", "mua_lan_dau", "vang"], owner: "Linh", value: 12000000, last_touch: ago(1), next_action: "Gọi tư vấn khóa nâng cao", segment: "gia_dinh", openTickets: 0 },
    { id: "L-1004", name: "Tô Quang Long", phone: "0909 121 212", companyName: "Long Châu Group", area: "Quận 5, TP.HCM", tags: ["website", "da_hoc", "pcth", "mua_lai", "vang"], owner: "Hà", value: 19800000, last_touch: ago(1), next_action: "Ký hợp đồng PCTH", segment: "co_quan", openTickets: 1 },
    { id: "L-1005", name: "Bùi Phương Mai", phone: "0909 555 018", tags: ["facebook", "da_hoc", "lop_khac", "mua_lan_dau", "vang"], owner: "Hà", value: 11300000, last_touch: ago(1), next_action: "Demo onsite", openTickets: 0 },
    { id: "L-1006", name: "Đỗ Thanh Giang", phone: "0945 678 901", area: "Quận 7, TP.HCM", tags: ["website", "chua_hoc", "mkt", "mua_lan_dau", "vang"], owner: "Linh", value: 15000000, last_touch: ago(2), next_action: "Tư vấn MKT package", segment: "chuan_bi_mo", openTickets: 0 },
    { id: "L-1007", name: "Lý Thiên Hương", phone: "0902 555 011", tags: ["zalo", "da_hoc", "nhuong_quyen", "khong_mua", "bac"], owner: "Minh", value: 14200000, last_touch: ago(2), next_action: "Tư vấn franchise", openTickets: 0 },
    { id: "L-1008", name: "Lê Minh Châu", phone: "0987 654 321", area: "Quận Bình Thạnh, TP.HCM", tags: ["website", "chua_hoc", "mien_phi_offline", "khong_mua", "bac"], owner: "Linh", value: 3200000, last_touch: ago(3), next_action: "Gửi tài liệu free", segment: "chuan_bi_mo", openTickets: 0 },
    { id: "L-1009", name: "Hà Bảo Trân", phone: "0906 555 015", companyName: "Saigon Pearl F&B", area: "Quận 7, TP.HCM", tags: ["zalo", "da_hoc", "nhuong_quyen", "mua_lan_dau", "vang"], owner: "Hà", value: 22000000, last_touch: ago(3), next_action: "Chốt franchise quận 7", segment: "co_quan", openTickets: 1 },
    { id: "L-1010", name: "Mai Thu Hương", phone: "0922 555 666", companyName: "Highland Brew JSC", area: "Quận 10, TP.HCM", tags: ["facebook", "da_hoc", "nhuong_quyen", "mua_lai", "vang"], owner: "Hà", value: 32000000, last_touch: ago(4), next_action: "Lên kế hoạch nhượng quyền", segment: "co_quan", openTickets: 1 },
    { id: "L-1011", name: "Ngô Quỳnh Anh", phone: "0907 555 016", tags: ["tiktok", "chua_hoc", "van_hanh", "mua_lan_dau", "bac"], owner: "Linh", value: 7800000, last_touch: ago(4), next_action: "Tư vấn lớp vận hành", openTickets: 0 },
    { id: "L-1012", name: "Vũ Hồng Phúc", phone: "0966 222 333", area: "Đà Nẵng", tags: ["tiktok", "da_hoc", "van_hanh", "mua_lai", "bac"], owner: "Hà", value: 8900000, last_touch: ago(5), next_action: "Demo lớp vận hành", segment: "chuan_bi_mo", openTickets: 0 },
    { id: "L-1013", name: "Phan Văn Nam", phone: "0901 555 010", tags: ["facebook", "chua_hoc", "van_hanh", "mua_lan_dau", "bac"], owner: "Linh", value: 9500000, last_touch: ago(6), next_action: "Demo phần mềm vận hành", openTickets: 0 },
    { id: "L-1014", name: "Ngô Việt Khôi", phone: "0911 777 888", tags: ["tiktok", "chua_hoc", "pcth", "mua_lan_dau", "bac"], owner: "Linh", value: 6700000, last_touch: ago(8), next_action: "Mời học thử PCTH", openTickets: 0 },
    { id: "L-1015", name: "Đinh Khả Vy", phone: "0905 555 014", tags: ["facebook", "chua_hoc", "mien_phi_online", "mua_lan_dau", "dong"], owner: "Minh", value: 2100000, last_touch: ago(9), next_action: "Mời học miễn phí", openTickets: 0 },
    { id: "L-1016", name: "Trần Thị Bình", phone: "0912 345 678", tags: ["zalo", "da_hoc", "pcth", "mua_lai", "vang"], owner: "Minh", value: 25500000, last_touch: ago(10), next_action: "Chốt hợp đồng PCTH", openTickets: 0 },
    { id: "L-1017", name: "Trịnh Hoàng Sơn", phone: "0903 555 012", tags: ["facebook", "chua_hoc", "mkt", "mua_lan_dau", "dong"], owner: "Hà", value: 5500000, last_touch: ago(11), next_action: "Gửi báo giá MKT", openTickets: 0 },
    { id: "L-1018", name: "Hoàng Thu Em", phone: "0934 567 890", tags: ["website", "chua_hoc", "mien_phi_online", "ngung_mua", "dong"], owner: "Minh", value: 1500000, last_touch: ago(14), next_action: "Follow-up sau 2 tuần", openTickets: 0 },
    { id: "L-1019", name: "Lê Trọng Đạt", phone: "0908 555 321", tags: ["zalo", "da_hoc", "pcth", "mua_lai", "bac"], owner: "Minh", value: 17500000, last_touch: ago(15), next_action: "Follow-up sau khóa thử", openTickets: 0 },
    { id: "L-1020", name: "Bùi Khánh Hà", phone: "0903 333 444", tags: ["facebook", "chua_hoc", "pcth", "ngung_mua", "dong"], owner: "Minh", value: 4500000, last_touch: ago(20), next_action: "Khảo sát lý do ngưng", openTickets: 0 },
    { id: "L-1021", name: "Đặng Thảo Linh", phone: "0988 999 000", tags: ["tiktok", "chua_hoc", "mien_phi_online", "khong_mua", "dong"], owner: "Minh", value: 0, last_touch: ago(30), next_action: "Đóng lead", openTickets: 0 },
  ];

  root.LeadsSeedData = {
    VERSION: "20260611_leads_spec_v1",
    leads: RAW_LEADS.map(enrichLead),
  };
})(typeof window !== "undefined" ? window : this);
