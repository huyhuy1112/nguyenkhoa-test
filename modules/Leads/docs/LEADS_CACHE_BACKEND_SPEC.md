# Leads SALES UI — Cache contract for backend integration

This document describes the **front-end cache model** used by the Leads List/Detail UI (`app=SALES`).  
No database schema changes are required for the current prototype. Backend developers should implement the same shapes when replacing `localStorage` with API responses.

## Storage

| Key | Location | Notes |
|-----|----------|-------|
| `bace_leads_cache_v1` | `localStorage` | Array of lead rows |
| `bace_leads_cache_version` | `localStorage` | Bumped when `LeadsSeedData.VERSION` changes |

Files: `layouts/v7/modules/Leads/resources/LeadsLocalStore.js`, `LeadsSeedData.js`

## Lead row (cache)

```json
{
  "id": "L-1001",
  "name": "Phạm Quốc Dũng",
  "phone": "0978 111 222",
  "companyName": "Cafe Phố Cổ",
  "tags": ["zalo", "mua_lan_dau"],
  "owner": "Hà",
  "value": 48000000,
  "last_touch": "2026-06-11T10:00:00.000Z",
  "next_action": "legacy fallback string",
  "purchases": [
    {
      "orderId": "SO-1001-1",
      "product": "Syrup Caramel B-ACE 750ml",
      "qty": 12,
      "value": 1440000,
      "date": "06/11/2026"
    }
  ],
  "calendarTasks": [
    {
      "type": "task",
      "subject": "Ký nhượng quyền khu vực",
      "status": "open",
      "dueAt": "2026-06-11T10:00:00.000Z",
      "dueLabel": "Today"
    }
  ]
}
```

### Field rules

| UI label | Cache / logic | Backend source (suggested) |
|----------|---------------|----------------------------|
| **Tổng đơn hàng 1 tháng** | `LeadsLeadsLogic.monthlyOrderCount(lead)` — distinct `orderId` in `purchases` where `date` within 30 days | Count SalesOrder (or Invoice) linked to Lead/Contact/Account in rolling 30 days |
| **Tổng sản phẩm đã mua** | `LeadsLeadsLogic.totalProductsPurchased(lead)` — `SUM(purchases[].qty)` | Sum line-item quantities from related orders |
| **Giá trị đơn gần nhất** | `LeadsLeadsLogic.recentOrderValue(lead)` — `value` of latest `purchases[]` by `date` | Grand total of most recent order |
| **Next action** (List column) | `LeadsLeadsLogic.deriveNextAction(lead)` — earliest **open** `calendarTasks` where `type` ∈ `task\|call\|meeting`; fallback `next_action` | Query Calendar (Tasks/Events/Calls) related to Lead; pick earliest open activity subject |
| **Khóa học / nguyên liệu** | Optional tags on create/edit (`chua_hoc`, `da_hoc`, `pcth`, …) — **not required** | Map to custom fields or tag module; do not enforce NOT NULL |

## UI entry points

| Feature | Template | JavaScript |
|---------|----------|------------|
| Detail KEY FIELDS metrics | `partials/LeadsDetailUiDemo.tpl` | `LeadsDetailUiDemo.js` → `renderKeyFields()` |
| Detail tab metrics | Same template → commerce sub-tabs | `renderCommerceDetail()` |
| List **Next action** column | `ListViewContents.tpl` | `LeadsMkList.js` → `deriveNextAction()` |
| Create — optional course/material | `partials/LeadsMkEdit.tpl` | `LeadsMkEdit.js` — no validation on intent/entry |

## Replacing cache with API

1. Add `modules/Leads/actions/GetLeadCommerceMetrics.php` (or extend Detail API) returning `{ monthlyOrders, totalProducts, recentOrderValue, calendarTasks }`.
2. In `LeadsDetailUiDemo.js` / `LeadsMkList.js`, replace `LeadsLocalStore.getLeads()` with `app.request.post` when `MK_LEADS_API_READY` flag is set.
3. Keep `LeadsLeadsLogic` pure functions — pass API payload in the same shape as cache rows.

## Demo reset

Users with stale localStorage after seed updates: open List → devtools → `LeadsLocalStore.resetDemo()` or clear `bace_leads_cache_*` keys.
