<?php
/**
 * Sync missing vi_vn labels from en_us into languages/custom/vi_vn/*.php
 * with Vietnamese overrides for common UI strings.
 *
 * Run: php tools/mk_i18n_sync_vi.php
 */

$root = dirname(__DIR__);
$enDir = $root . '/languages/en_us';
$viDir = $root . '/languages/vi_vn';
$outDir = $root . '/languages/custom/vi_vn';

/** Vietnamese overrides (preferred over English copy). */
$viOverrides = [
	'LBL_POST_YOUR_COMMENT_HERE' => 'Nhập bình luận tại đây...',
	'LBL_ADD_YOUR_COMMENT_HERE' => 'Nhập bình luận tại đây...',
	'LBL_WRITE_YOUR_COMMENT_HERE' => 'Nhập bình luận tại đây...',
	'LBL_NEW_DOCUMENT' => 'Tài liệu mới',
	'LBL_FILE_UPLOAD' => 'Tải tệp lên',
	'LBL_TO_SERVICE' => 'Lên %s',
	'LBL_VTIGER' => 'Vtiger',
	'LBL_LINK_EXTERNAL_DOCUMENT' => 'Liên kết tài liệu ngoài',
	'LBL_FROM_SERVICE' => 'Từ %s',
	'LBL_FILE_URL' => 'URL tệp',
	'LBL_VIEW_FILE' => 'Xem tệp',
	'LBL_DELETE_CONFIRMATION' => 'Bạn có chắc muốn xóa không?',
	'LBL_MASS_DELETE_CONFIRMATION' => 'Bạn có chắc muốn xóa các bản ghi đã chọn không?',
	'LBL_LIST_DELETE_CONFIRMATION' => 'Bạn có chắc muốn xóa bộ lọc này không?',
	'LBL_PERMISSION_DENIED' => 'Không có quyền truy cập',
	'LBL_WARNING' => 'Cảnh báo',
	'LBL_ERROR' => 'Lỗi',
	'LBL_UPLOAD' => 'Tải lên',
	'LBL_SCHEDULE' => 'Lịch biểu',
	'LBL_MINI_CALENDAR' => 'Lịch nhỏ',
	'LBL_SHOW_MORE' => 'Xem thêm',
	'LBL_ALL_DAY' => 'Cả ngày',
	'LBL_ALL_DAY_HINT' => 'Sự kiện kéo dài cả ngày',
	'LBL_ADD_TITLE' => 'Thêm tiêu đề',
	'LBL_ADD_DESCRIPTION' => 'Thêm mô tả',
	'LBL_START_DATE' => 'Ngày bắt đầu',
	'LBL_END_DATE' => 'Ngày kết thúc',
	'LBL_REPEAT' => 'Lặp lại',
	'LBL_DAILY' => 'Hàng ngày',
	'LBL_WEEKLY' => 'Hàng tuần',
	'LBL_MONTHLY' => 'Hàng tháng',
	'LBL_YEARLY' => 'Hàng năm',
	'LBL_DOES_NOT_REPEAT' => 'Không lặp lại',
	'LBL_LEAVE_REQUEST' => 'Xin nghỉ phép',
	'LBL_LEAVE_DELETE_CONFIRM' => 'Xóa yêu cầu nghỉ phép này?',
	'LBL_AGENDA' => 'Chương trình',
	'LBL_WEEK' => 'Tuần',
	'LBL_SUNDAY' => 'Chủ nhật',
	'LBL_MONDAY' => 'Thứ hai',
	'LBL_TUESDAY' => 'Thứ ba',
	'LBL_WEDNESDAY' => 'Thứ tư',
	'LBL_THURSDAY' => 'Thứ năm',
	'LBL_FRIDAY' => 'Thứ sáu',
	'LBL_SATURDAY' => 'Thứ bảy',
	'LBL_SUN' => 'CN',
	'LBL_MON' => 'T2',
	'LBL_TUE' => 'T3',
	'LBL_WED' => 'T4',
	'LBL_THU' => 'T5',
	'LBL_FRI' => 'T6',
	'LBL_SAT' => 'T7',
	'LBL_JANUARY' => 'Tháng 1',
	'LBL_FEBRUARY' => 'Tháng 2',
	'LBL_MARCH' => 'Tháng 3',
	'LBL_APRIL' => 'Tháng 4',
	'LBL_MAY' => 'Tháng 5',
	'LBL_JUNE' => 'Tháng 6',
	'LBL_JULY' => 'Tháng 7',
	'LBL_AUGUST' => 'Tháng 8',
	'LBL_SEPTEMBER' => 'Tháng 9',
	'LBL_OCTOBER' => 'Tháng 10',
	'LBL_NOVEMBER' => 'Tháng 11',
	'LBL_DECEMBER' => 'Tháng 12',
	'LBL_JAN' => 'Th1',
	'LBL_FEB' => 'Th2',
	'LBL_MAR' => 'Th3',
	'LBL_APR' => 'Th4',
	'LBL_JUN' => 'Th6',
	'LBL_JUL' => 'Th7',
	'LBL_AUG' => 'Th8',
	'LBL_SEP' => 'Th9',
	'LBL_OCT' => 'Th10',
	'LBL_NOV' => 'Th11',
	'LBL_DEC' => 'Th12',
	'LBL_SELECT_USERS' => 'Chọn người dùng',
	'LBL_SELECT_GROUPS' => 'Chọn nhóm',
	'LBL_SELECT_STATUS' => 'Chọn trạng thái',
	'LBL_DELETE_USER_CONFIRMATION' => 'Bạn có chắc muốn xóa người dùng này?',
	'LBL_DELETE_USER_PERMANENT_CONFIRMATION' => 'Xóa vĩnh viễn người dùng này?',
	'LBL_SIGN_IN_AS_USER' => 'Đăng nhập với tư cách người dùng này?',
	'LBL_RESTORE_CONFIRMATION' => 'Khôi phục bản ghi đã chọn?',
	'LBL_LOADING_FAILED' => 'Tải dữ liệu thất bại',
	'LBL_DASHBOARD_LAYOUT_SAVED' => 'Đã lưu bố cục bảng điều khiển',
	'LBL_DASHBOARD_TAB_ORDER_SAVED' => 'Đã lưu thứ tự tab',
	'LBL_LEFT_PANEL_SHOW_HIDE' => 'Ẩn/hiện thanh bên',
	'LBL_CANT_SELECT_CONTACT_FROM_LEADS' => 'Không thể chọn liên hệ từ khách hàng tiềm năng',
	'LBL_EXPRESSION_INVALID' => 'Biểu thức không hợp lệ',
	'LBL_WRONG_IMAGE_TYPE' => 'Định dạng ảnh không hợp lệ',
	'LBL_MAXIMUM_SIZE_EXCEEDS' => 'Kích thước tệp vượt quá giới hạn',
	'LBL_SMS_MAX_CHARACTERS_ALLOWED' => 'Vượt quá số ký tự SMS cho phép',
	'LBL_EXTENSION_STORE' => 'Cửa hàng tiện ích',
	'LBL_COMPANY_DETAILS' => 'Thông tin công ty',
	'LBL_ASSIGN_ROLE' => 'Gán vai trò',
	'LBL_COPY_PRIVILEGES_FROM' => 'Sao chép quyền từ',
	'LBL_MODULES' => 'Module',
	'LBL_EDIT_GROUP' => 'Sửa nhóm',
	'LBL_LOCATION' => 'Địa điểm',
	'LBL_ADD_CALENDAR' => 'Thêm lịch',
	'LBL_ADD_EVENTS' => 'Thêm sự kiện',
	'LBL_NO_DATE_VALUE_MSG' => 'Chưa có ngày',
	'LBL_NO_SUBTASKS' => 'Chưa có công việc con',
	'LBL_COMMENT_SHORT' => 'Ghi chú',
	'LBL_CAMPAIGN_ADD_PHASE' => 'Thêm giai đoạn',
	'LBL_CAMPAIGN_PHASES_HINT' => 'Giai đoạn đang dùng: %s (tối đa 5)',
	'LBL_WELCOME_TO_VTIGER7_SETUP_WIZARD' => 'Chào mừng đến trình cài đặt',
	'LBL_VTIGER7_SETUP_WIZARD_DESCRIPTION' => 'Thiết lập hệ thống CRM',
	'LBL_CHOOSE_LANGUAGE' => 'Chọn ngôn ngữ',
	'LBL_CUTOMER_LOGIN_DETAILS_TEMPLATE_DELETE_MESSAGE' => 'Không thể xóa mẫu thông tin đăng nhập khách hàng',
	'LBL_RELATED_PRODUCTS_AND_SERVICES' => 'Sản phẩm & dịch vụ liên quan',
	'LBL_CONVERT_POTENTIAL' => 'Chuyển đổi cơ hội',
	'LBL_POTENTIALS_FIELD_MAPPING' => 'Ánh xạ trường cơ hội',
	'LBL_CONVERT_POTENTIALS_ERROR' => 'Bật module Dự án để chuyển đổi cơ hội',
	'LBL_POTENTIALS_FIELD_MAPPING_INCOMPLETE' => 'Ánh xạ trường cơ hội chưa hoàn tất',
	'LBL_CUSTOM_FIELD_MAPPING' => 'Ánh xạ trường tùy chỉnh',
];

function loadStrings(string $file): array {
	if (!file_exists($file)) {
		return [];
	}
	$languageStrings = [];
	include $file;
	return isset($languageStrings) && is_array($languageStrings) ? $languageStrings : [];
}

if (!is_dir($outDir)) {
	mkdir($outDir, 0755, true);
}

$byModule = [];
foreach (glob($enDir . '/*.php') as $enFile) {
	$mod = basename($enFile, '.php');
	$en = loadStrings($enFile);
	$vi = loadStrings($viDir . '/' . $mod . '.php');
	foreach ($en as $k => $v) {
		if (!preg_match('/^(LBL_|SINGLE_)/', $k)) {
			continue;
		}
		if (isset($vi[$k]) && trim((string) $vi[$k]) !== '' && $vi[$k] !== $k) {
			continue;
		}
		$byModule[$mod][$k] = $viOverrides[$k] ?? $v;
	}
}

foreach ($byModule as $mod => $strings) {
	if (empty($strings)) {
		continue;
	}
	ksort($strings);
	$path = $outDir . '/' . $mod . '.php';
	$lines = ["<?php", "/** Auto-synced missing vi_vn labels — do not show raw LBL_* in UI. */", '$customStrings = array('];
	foreach ($strings as $k => $v) {
		$lines[] = "\t" . var_export($k, true) . ' => ' . var_export($v, true) . ',';
	}
	$lines[] = ');';
	$lines[] = '';
	file_put_contents($path, implode("\n", $lines));
	echo "Wrote $path (" . count($strings) . " keys)\n";
}

echo "Done.\n";
