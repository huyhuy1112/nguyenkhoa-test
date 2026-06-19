<?php
/*+***********************************************************************************
 * Demo seed rows for Modern Leads backend (mirrors LeadsSeedData.js RAW_LEADS).
 *************************************************************************************/

class Leads_ModernSeedData {

	public static function rawLeads() {
		return array(
			array('id' => 'L-1001', 'name' => 'Phạm Quốc Dũng', 'phone' => '0978 111 222', 'companyName' => 'Cafe Phố Cổ', 'area' => 'Quận 1, TP.HCM', 'tags' => array('zalo', 'da_hoc', 'nhuong_quyen', 'mua_lan_dau', 'vang'), 'owner' => 'Hà', 'value' => 48000000, 'last_touch_days' => 0, 'next_action' => 'Ký nhượng quyền khu vực', 'segment' => 'co_quan', 'openTickets' => 0),
			array('id' => 'L-1002', 'name' => 'Cao Thanh Tùng', 'phone' => '0904 555 013', 'tags' => array('website', 'da_hoc', 'pcth', 'van_hanh', 'mua_lai', 'vang'), 'owner' => 'Linh', 'value' => 28400000, 'last_touch_days' => 0, 'next_action' => 'Demo PCTH advanced', 'openTickets' => 0),
			array('id' => 'L-1003', 'name' => 'Nguyễn Văn An', 'phone' => '0901 234 567', 'area' => 'Quận 1, TP.HCM', 'tags' => array('facebook', 'chua_hoc', 'mien_phi_online', 'mua_lan_dau', 'vang'), 'owner' => 'Linh', 'value' => 12000000, 'last_touch_days' => 1, 'next_action' => 'Gọi tư vấn khóa nâng cao', 'segment' => 'gia_dinh', 'openTickets' => 0),
			array('id' => 'L-1004', 'name' => 'Tô Quang Long', 'phone' => '0909 121 212', 'companyName' => 'Long Châu Group', 'area' => 'Quận 5, TP.HCM', 'tags' => array('website', 'da_hoc', 'pcth', 'mua_lai', 'vang'), 'owner' => 'Hà', 'value' => 19800000, 'last_touch_days' => 1, 'next_action' => 'Ký hợp đồng PCTH', 'segment' => 'co_quan', 'openTickets' => 1),
			array('id' => 'L-1005', 'name' => 'Bùi Phương Mai', 'phone' => '0909 555 018', 'tags' => array('facebook', 'da_hoc', 'lop_khac', 'mua_lan_dau', 'vang'), 'owner' => 'Hà', 'value' => 11300000, 'last_touch_days' => 1, 'next_action' => 'Demo onsite', 'openTickets' => 0),
			array('id' => 'L-1006', 'name' => 'Đỗ Thanh Giang', 'phone' => '0945 678 901', 'area' => 'Quận 7, TP.HCM', 'tags' => array('website', 'chua_hoc', 'mkt', 'mua_lan_dau', 'vang'), 'owner' => 'Linh', 'value' => 15000000, 'last_touch_days' => 2, 'next_action' => 'Tư vấn MKT package', 'segment' => 'chuan_bi_mo', 'openTickets' => 0),
			array('id' => 'L-1007', 'name' => 'Lý Thiên Hương', 'phone' => '0902 555 011', 'tags' => array('zalo', 'da_hoc', 'nhuong_quyen', 'khong_mua', 'bac'), 'owner' => 'Minh', 'value' => 14200000, 'last_touch_days' => 2, 'next_action' => 'Tư vấn franchise', 'openTickets' => 0),
			array('id' => 'L-1008', 'name' => 'Lê Minh Châu', 'phone' => '0987 654 321', 'area' => 'Quận Bình Thạnh, TP.HCM', 'tags' => array('website', 'chua_hoc', 'mien_phi_offline', 'khong_mua', 'bac'), 'owner' => 'Linh', 'value' => 3200000, 'last_touch_days' => 3, 'next_action' => 'Gửi tài liệu free', 'segment' => 'chuan_bi_mo', 'openTickets' => 0),
			array('id' => 'L-1009', 'name' => 'Hà Bảo Trân', 'phone' => '0906 555 015', 'companyName' => 'Saigon Pearl F&B', 'area' => 'Quận 7, TP.HCM', 'tags' => array('zalo', 'da_hoc', 'nhuong_quyen', 'mua_lan_dau', 'vang'), 'owner' => 'Hà', 'value' => 22000000, 'last_touch_days' => 3, 'next_action' => 'Chốt franchise quận 7', 'segment' => 'co_quan', 'openTickets' => 1),
			array('id' => 'L-1010', 'name' => 'Mai Thu Hương', 'phone' => '0922 555 666', 'companyName' => 'Highland Brew JSC', 'area' => 'Quận 10, TP.HCM', 'tags' => array('facebook', 'da_hoc', 'nhuong_quyen', 'mua_lai', 'vang'), 'owner' => 'Hà', 'value' => 32000000, 'last_touch_days' => 4, 'next_action' => 'Lên kế hoạch nhượng quyền', 'segment' => 'co_quan', 'openTickets' => 1),
			array('id' => 'L-1011', 'name' => 'Ngô Quỳnh Anh', 'phone' => '0907 555 016', 'tags' => array('tiktok', 'chua_hoc', 'van_hanh', 'mua_lan_dau', 'bac'), 'owner' => 'Linh', 'value' => 7800000, 'last_touch_days' => 4, 'next_action' => 'Tư vấn lớp vận hành', 'openTickets' => 0),
			array('id' => 'L-1012', 'name' => 'Vũ Hồng Phúc', 'phone' => '0966 222 333', 'area' => 'Đà Nẵng', 'tags' => array('tiktok', 'da_hoc', 'van_hanh', 'mua_lai', 'bac'), 'owner' => 'Hà', 'value' => 8900000, 'last_touch_days' => 5, 'next_action' => 'Demo lớp vận hành', 'segment' => 'chuan_bi_mo', 'openTickets' => 0),
			array('id' => 'L-1013', 'name' => 'Phan Văn Nam', 'phone' => '0901 555 010', 'tags' => array('facebook', 'chua_hoc', 'van_hanh', 'mua_lan_dau', 'bac'), 'owner' => 'Linh', 'value' => 9500000, 'last_touch_days' => 6, 'next_action' => 'Demo phần mềm vận hành', 'openTickets' => 0),
			array('id' => 'L-1014', 'name' => 'Ngô Việt Khôi', 'phone' => '0911 777 888', 'tags' => array('tiktok', 'chua_hoc', 'pcth', 'mua_lan_dau', 'bac'), 'owner' => 'Linh', 'value' => 6700000, 'last_touch_days' => 8, 'next_action' => 'Mời học thử PCTH', 'openTickets' => 0),
			array('id' => 'L-1015', 'name' => 'Đinh Khả Vy', 'phone' => '0905 555 014', 'tags' => array('facebook', 'chua_hoc', 'mien_phi_online', 'mua_lan_dau', 'dong'), 'owner' => 'Minh', 'value' => 2100000, 'last_touch_days' => 9, 'next_action' => 'Mời học miễn phí', 'openTickets' => 0),
			array('id' => 'L-1016', 'name' => 'Trần Thị Bình', 'phone' => '0912 345 678', 'tags' => array('zalo', 'da_hoc', 'pcth', 'mua_lai', 'vang'), 'owner' => 'Minh', 'value' => 25500000, 'last_touch_days' => 10, 'next_action' => 'Chốt hợp đồng PCTH', 'openTickets' => 0),
			array('id' => 'L-1017', 'name' => 'Trịnh Hoàng Sơn', 'phone' => '0903 555 012', 'tags' => array('facebook', 'chua_hoc', 'mkt', 'mua_lan_dau', 'dong'), 'owner' => 'Hà', 'value' => 5500000, 'last_touch_days' => 11, 'next_action' => 'Gửi báo giá MKT', 'openTickets' => 0),
			array('id' => 'L-1018', 'name' => 'Hoàng Thu Em', 'phone' => '0934 567 890', 'tags' => array('website', 'chua_hoc', 'mien_phi_online', 'ngung_mua', 'dong'), 'owner' => 'Minh', 'value' => 1500000, 'last_touch_days' => 14, 'next_action' => 'Follow-up sau 2 tuần', 'openTickets' => 0),
			array('id' => 'L-1019', 'name' => 'Lê Trọng Đạt', 'phone' => '0908 555 321', 'tags' => array('zalo', 'da_hoc', 'pcth', 'mua_lai', 'bac'), 'owner' => 'Minh', 'value' => 17500000, 'last_touch_days' => 15, 'next_action' => 'Follow-up sau khóa thử', 'openTickets' => 0),
			array('id' => 'L-1020', 'name' => 'Bùi Khánh Hà', 'phone' => '0903 333 444', 'tags' => array('facebook', 'chua_hoc', 'pcth', 'ngung_mua', 'dong'), 'owner' => 'Minh', 'value' => 4500000, 'last_touch_days' => 20, 'next_action' => 'Khảo sát lý do ngưng', 'openTickets' => 0),
			array('id' => 'L-1021', 'name' => 'Đặng Thảo Linh', 'phone' => '0988 999 000', 'tags' => array('tiktok', 'chua_hoc', 'mien_phi_online', 'khong_mua', 'dong'), 'owner' => 'Minh', 'value' => 0, 'last_touch_days' => 30, 'next_action' => 'Đóng lead', 'openTickets' => 0),
		);
	}

	public static function enrichLead(array $lead, $idx) {
		$daysAgo = isset($lead['last_touch_days']) ? (int)$lead['last_touch_days'] : 0;
		$lead['last_touch'] = date('c', time() - ($daysAgo * 86400));
		unset($lead['last_touch_days']);

		$lead = self::splitAreaFields($lead);
		$lead['purchases'] = self::buildPurchases($lead, $idx);
		$lead['calendarTasks'] = self::buildCalendarTasks($lead, $idx);
		$lead['activities'] = array();
		$lead['email'] = isset($lead['email']) ? $lead['email'] : '';
		$lead['cccd'] = isset($lead['cccd']) ? $lead['cccd'] : '';
		$lead['notes'] = isset($lead['notes']) ? $lead['notes'] : '';
		$lead['companyName'] = isset($lead['companyName']) ? $lead['companyName'] : '';
		$lead['segment'] = isset($lead['segment']) ? $lead['segment'] : '';
		$lead['openTickets'] = isset($lead['openTickets']) ? (int)$lead['openTickets'] : 0;
		return $lead;
	}

	protected static function splitAreaFields(array $lead) {
		if (!empty($lead['district']) || !empty($lead['address'])) {
			return $lead;
		}
		if (empty($lead['area'])) {
			return $lead;
		}
		if (preg_match('/^(Quận [^,]+|Huyện [^,]+)/u', $lead['area'], $m)) {
			$lead['district'] = $m[1];
			$lead['address'] = trim(preg_replace('/^' . preg_quote($m[1], '/') . ',?\s*/u', '', $lead['area']));
			$lead['address'] = preg_replace('/^,\s*TP\.?HCM\s*$/ui', '', $lead['address']);
		} else {
			$lead['address'] = $lead['area'];
		}
		return $lead;
	}

	protected static function buildPurchases(array $lead, $idx) {
		$purchases = array();
		$value = isset($lead['value']) ? (float)$lead['value'] : 0;
		if ($value <= 0 || ($idx % 6) === 5) {
			return $purchases;
		}
		$baseId = str_replace('L-', '', (string)$lead['id']);
		$monthLines = 1 + ($idx % 2);
		$historyLines = 1 + ($idx % 2);
		$products = array(
			'Syrup Caramel B-ACE 750ml',
			'Bột trà sữa B-ACE 1kg',
			'Gói PCTH Advanced',
			'Ly nhựa B-ACE 500ml',
			'Combo nguyên liệu khởi nghiệp',
		);
		$monthNames = array('Đơn tháng — nguyên liệu mới', 'Đơn tháng — bổ sung syrup', 'Đơn tháng — PCTH trial');
		$histNames = array('Đơn cũ — combo khởi nghiệp', 'Đơn cũ — franchise đợt 1', 'Đơn cũ — vận hành quán', 'Đơn cũ — nguyên liệu bulk');

		for ($i = 0; $i < $monthLines; $i++) {
			$daysAgo = $i * 5 + ($idx % 10);
			$qty = 4 + (($idx + $i) % 9);
			$purchases[] = array(
				'orderId' => 'SO-' . $baseId . '-M' . ($i + 1),
				'orderName' => $monthNames[($idx + $i) % count($monthNames)],
				'product' => $products[($idx + $i) % count($products)],
				'qty' => $qty,
				'value' => max(500000, round(($value / ($monthLines + $historyLines)) * (0.7 + $i * 0.15))),
				'date' => self::purchaseDate($daysAgo),
			);
		}
		for ($i = 0; $i < $historyLines; $i++) {
			$histDays = 35 + $i * 25 + ($idx % 20);
			$histQty = 2 + (($idx + $i) % 6);
			$purchases[] = array(
				'orderId' => 'SO-' . $baseId . '-H' . ($i + 1),
				'orderName' => $histNames[($idx + $i) % count($histNames)],
				'product' => $products[($idx + $i + 2) % count($products)],
				'qty' => $histQty,
				'value' => max(400000, round(($value / ($monthLines + $historyLines)) * 0.5)),
				'date' => self::purchaseDate($histDays),
			);
		}
		return $purchases;
	}

	protected static function buildCalendarTasks(array $lead, $idx) {
		if (empty($lead['next_action'])) {
			return array();
		}
		$types = array('task', 'call', 'meeting');
		$mod = $idx % 4;
		return array(array(
			'type' => $types[$idx % 3],
			'subject' => $lead['next_action'],
			'status' => 'open',
			'dueAt' => date('c', time() - ($mod * 86400)),
			'dueLabel' => $mod === 0 ? 'Today' : ($mod . 'd ago'),
		));
	}

	protected static function purchaseDate($daysAgo) {
		$ts = time() - ((int)$daysAgo * 86400);
		return date('d/m/Y', $ts);
	}

	public static function allEnriched() {
		$rows = array();
		foreach (self::rawLeads() as $idx => $lead) {
			$rows[] = self::enrichLead($lead, $idx);
		}
		return $rows;
	}
}
