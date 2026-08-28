<?php
/*+***********************************************************************************
 * HTML mẫu Hợp đồng nhượng quyền TUI BAO (merge {{placeholders}}).
 * Nội dung bám sát Google Doc gốc — không rút gọn.
 *************************************************************************************/

class Accounts_FranchiseContractHtml_Helper {

	public static function template() {
		// Lề mm = same as Word gốc / PDF (top-right-bottom 8.01, left 20, …)
		return <<<'HTML'
<style>
	/* Page margins = Word gốc: top/right/bottom 454 twip (~8mm), left 1134 (~20mm) */
	@page {
		size: A4;
		margin-top: 8.01mm;
		margin-right: 8.01mm;
		margin-bottom: 8.01mm;
		margin-left: 20mm;
	}
	html, body { margin: 0; padding: 0; }
	body, p, td, div, li {
		font-family: freeserif, 'Times New Roman', Times, serif;
		font-size: 12pt;
		line-height: 1.15;
		color: #000;
		text-align: justify;
	}
	p { margin: 0; padding: 0; }

	.h-nation {
		text-align: center;
		font-weight: bold;
		font-size: 12pt;
		line-height: 1.15;
		margin: 0;
		padding: 0;
	}
	.h-motto {
		text-align: center;
		font-weight: bold;
		font-size: 12pt;
		line-height: 1.15;
		margin: 0;
		padding: 0;
		text-decoration: underline;
	}
	.gap-head {
		margin: 0; padding: 0;
		height: 8pt; line-height: 8pt; font-size: 1pt;
	}
	.h-date {
		text-align: right;
		font-size: 12pt;
		font-style: italic;
		margin: 0; padding: 0;
		line-height: 1.15;
	}
	.gap-title {
		margin: 0; padding: 0;
		height: 5pt; line-height: 5pt; font-size: 1pt;
	}
	.h-title {
		font-family: freeserif, 'Times New Roman', Times, serif;
		font-size: 16pt;
		font-weight: bold;
		text-align: center;
		margin: 0; padding: 0;
		text-transform: uppercase;
		line-height: 1.15;
	}
	.h-sub {
		text-align: center;
		margin: 0 0 4pt; padding: 0;
		font-size: 12pt;
		font-weight: normal;
	}
	.gap-legal {
		margin: 0; padding: 0;
		height: 4pt; line-height: 4pt; font-size: 1pt;
	}
	/* Legal: italic + hanging (Word left 883 / hanging 162) */
	.legal {
		font-style: italic;
		font-size: 12pt;
		text-align: justify;
		margin: 0 0 2pt;
		padding-left: 15.6mm;
		text-indent: -2.9mm;
		line-height: 1.15;
	}
	.intro {
		font-style: normal;
		text-align: justify;
		margin: 8pt 0 12pt;
		padding: 0;
	}
	/* Party block: left 720 twip via padding (TCPDF ignores text-indent often) */
	.party-h {
		font-weight: bold;
		margin: 0;
		padding-left: 12.7mm;
		text-indent: 0;
		text-align: left;
		line-height: 1.0;
	}
	.party-name {
		font-weight: bold;
		margin: 0;
		padding-left: 12.7mm;
		text-indent: 0;
		text-align: left;
		text-transform: uppercase;
		line-height: 1.0;
	}
	.party-line {
		font-weight: normal;
		margin: 0;
		padding-left: 12.7mm;
		text-indent: 0;
		text-align: left;
		line-height: 1.0;
	}
	.party-split {
		margin: 0 0 0 0;
		padding: 0;
		width: 100%;
		border-collapse: collapse;
	}
	/* outer left indent for dual-field rows */
	.party-split-wrap {
		margin: 0;
		padding-left: 12.7mm;
	}
	.party-split td {
		font-size: 12pt;
		line-height: 1.0;
		vertical-align: top;
		padding: 0;
		text-align: left;
		border: 0;
	}
	.party-split .ps-left { width: 58%; }
	.party-split .ps-right { width: 42%; }
	.gap-party {
		margin: 0; padding: 0;
		height: 10pt; line-height: 10pt; font-size: 1pt;
	}
	.muted {
		margin: 4pt 0 10pt;
		padding-left: 12.9mm;
		text-align: justify;
		font-style: normal;
	}
	.recital {
		font-style: normal;
		margin: 0 0 8pt;
		padding-left: 12.9mm;
		text-align: justify;
	}
	.recital-ital {
		font-style: italic;
		margin: 0 0 8pt;
		padding-left: 12.9mm;
		text-align: justify;
	}
	.gap { margin: 0; padding: 0; height: 6pt; line-height: 6pt; font-size: 1pt; }
	.art {
		font-weight: bold;
		margin-top: 10pt;
		margin-bottom: 0;
		font-size: 12pt;
		text-align: left;
		padding: 0;
	}
	.sub-art {
		font-weight: bold;
		margin-top: 6pt;
		margin-bottom: 0;
		font-size: 12pt;
		text-align: left;
	}
	.sign-table { width: 100%; margin-top: 12pt; }
	.sign-table td { width: 50%; text-align: center; vertical-align: top; font-size: 12pt; padding: 0 10px; }
	.sign-head { font-weight: bold; line-height: 1.2; }
	.sign-note { font-style: italic; font-weight: normal; }
	.sign-gap { height: 100px; line-height: 100px; font-size: 1pt; }
	.sign-name { font-weight: bold; text-transform: uppercase; }
	.fee-line {
		margin: 0;
		padding-left: 12.7mm;
		text-align: justify;
		line-height: 1.5;
	}
	.dot-line {
		margin: 0;
		padding-left: 12.7mm;
		text-align: justify;
		line-height: 1.5;
	}
	.clause-l1 {
		margin: 0;
		padding-left: 12.7mm;
		text-indent: -12.7mm;
		text-align: justify;
	}
	.clause-l2 {
		margin: 0;
		padding-left: 25.4mm;
		text-align: justify;
	}
	.clause { margin: 0; }
	.bu { font-size: 9pt; }
	.section-cap { font-weight: bold; margin: 6pt 0 0; text-align: left; }
</style>

<p class="h-nation">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</p>
<p class="h-motto">Độc lập – Tự do – Hạnh phúc</p>
<p class="gap-head">&nbsp;</p>
<p class="h-date">TP. Hồ Chí Minh, ngày {{sign_day}} tháng {{sign_month}} năm {{sign_year}}</p>
<p class="gap-title">&nbsp;</p>

<p class="h-title">HỢP ĐỒNG NHƯỢNG QUYỀN</p>
<p class="h-sub">(Số: {{contract_no}}/HĐHTKD)</p>
<p class="gap-legal">&nbsp;</p>

<p class="legal">- Căn cứ Luật sở hữu trí tuệ của nước Cộng hòa xã hội chủ nghĩa Việt Nam số 50/2005/QH11 ban hành ngày 12/12/2005;</p>
<p class="legal">- Căn cứ Luật thương mại của Quốc hội nước Cộng hòa xã hội chủ nghĩa Việt Nam số 36/2005/QH11 ngày 14 tháng 06 năm 2005;</p>
<p class="legal">- Căn cứ năng lực và nhu cầu của các bên.</p>

<p class="intro">Hôm nay, ngày {{sign_day}} tháng {{sign_month}} năm {{sign_year}}, Chúng tôi hai bên trong hợp đồng gồm có:</p>

<p class="party-h">Bên A (BÊN NHƯỢNG QUYỀN):</p>
<p class="party-name">CÔNG TY CỔ PHẦN THƯƠNG MẠI DỊCH VỤ SẢN XUẤT NGUYÊN KHOA</p>
<p class="party-line">Địa chỉ: 9A Nguyễn Sĩ Cố, Phường Phú Định, Thành Phố Hồ Chí Minh</p>
<p class="party-line">Mã số thuế: 0318269556</p>
<div class="party-split-wrap">
<table class="party-split" cellpadding="0" cellspacing="0" border="0" width="100%">
	<tr>
		<td class="ps-left">Đại diện theo pháp luật: Ông Nguyễn Đình Quốc Dũng</td>
		<td class="ps-right">Chức vụ: Giám Đốc</td>
	</tr>
</table>
</div>
<p class="gap-party">&nbsp;</p>

<p class="party-h">Bên B (BÊN NHẬN NHƯỢNG QUYỀN):</p>
<p class="party-line"><strong>{{party_b_name}}</strong></p>
<div class="party-split-wrap">
<table class="party-split" cellpadding="0" cellspacing="0" border="0" width="100%">
	<tr>
		<td class="ps-left">CCCD số: {{party_b_cccd}}</td>
		<td class="ps-right">Ngày cấp: {{party_b_cccd_date}}</td>
	</tr>
</table>
</div>
<p class="party-line">Nơi cấp: {{party_b_cccd_place}}</p>
<p class="party-line">Nơi thường trú: {{party_b_permanent_addr}}</p>
<p class="party-line">Địa chỉ liên hệ: {{party_b_contact_addr}}</p>
<div class="party-split-wrap">
<table class="party-split" cellpadding="0" cellspacing="0" border="0" width="100%">
	<tr>
		<td class="ps-left">Điện thoại: {{party_b_phone}}</td>
		<td class="ps-right">Email: {{party_b_email}}</td>
	</tr>
</table>
</div>

<p class="muted">(“Bên A, Bên B gọi chung là “<strong>Các Bên</strong>”, gọi riêng là “<strong>Bên</strong>”)</p>

<p class="recital-ital">Bên A là đơn vị được chủ sở hữu hợp pháp của nhãn hiệu "TUI BAO" theo Giấy chứng nhận đăng ký nhãn hiệu số 595392 do Cục Sở hữu trí tuệ cấp, ủy quyền khai thác, sử dụng và phát triển hệ thống nhượng quyền thương mại theo Hợp đồng ủy quyền khai thác và sử dụng nhãn hiệu được ký kết hợp pháp giữa chủ sở hữu nhãn hiệu và Bên A. Hồ sơ pháp lý về nhãn hiệu và các tài liệu liên quan là một phần không tách rời của Hợp đồng này.</p>
<p class="recital">Bên B có khả năng về tài chính và đã tham khảo sự hoạt động cũng như sự phát triển của hệ thống chuỗi TUI BAO và mong muốn trở thành thành viên của chuỗi trà sữa TUI BAO để sử dụng nhãn hiệu TUI BAO cho hoạt động kinh doanh của mình.</p>
<p class="recital">Sau khi trao đổi, thống nhất hai bên cùng ký kết Hợp đồng sử dụng nhãn hiệu với các điều khoản sau:</p>

<p class="art">Điều 1. Đối tượng hợp đồng</p>
<p class="clause-l1">1.1 Bên A bằng Hợp đồng này cho phép Bên B được quyền tiến hành mở 01 cơ sở kinh doanh (sau đây gọi tắt là “Cửa hàng”) để kinh doanh dịch vụ cung cấp thức uống và sản phẩm trà sữa mang nhãn hiệu “TUI BAO” theo hệ thống, thiết kế mà Bên A đã xây dựng, thiết lập, vận hành và Bên B được quyền gắn nhãn hiệu “TUI BAO” các chỉ dẫn thương mại khác như: kiểu dáng của sản phẩm, khẩu hiệu kinh doanh, biểu tượng kinh doanh, bí mật kinh doanh, quảng cáo thuộc sở hữu của Bên A trong hoạt động kinh doanh tại Cửa hàng của Bên B, cụ thể như sau:</p>
<p class="clause-l2">(a) Được gắn lên các biển hiệu Cửa hàng, sản phẩm thức uống trà sữa, giấy tờ giao dịch, phương tiện kinh doanh tại Cửa hàng của Bên B.</p>
<p class="clause-l2">(b) Được quyền sử dụng kiểu dáng của sản phẩm trà sữa “TUI BAO” theo hình ảnh và logo chuẩn được cung cấp.</p>
<p class="clause-l2">(c) Được sử dụng bí mật kinh doanh của Bên A là các công thức pha chế thức uống, phương thức hoạt động và các định hướng chiến lược kinh doanh, quảng cáo do Bên A xây dựng để thực hiện hoạt động kinh doanh tại Cửa hàng của Bên B.</p>
<p class="clause-l2">(d) Được sử dụng các nội dung trong các băng rôn, biển hiệu quảng cáo của Bên A đã và đang sử dụng để quảng cáo cho Hệ thống khi Bên B được cơ quan nhà nước có thẩm quyền cho phép theo quy định của Luật Quảng Cáo.</p>
<p class="gap">&nbsp;</p>
<p class="clause-l1">1.2 Trong khi thực hiện Hợp đồng này, Bên B chịu sự kiểm soát của Bên A trong quá trình hoạt động kinh doanh tại Cửa hàng.</p>

<p class="art">Điều 2. Phạm vi sử dụng thương hiệu</p>
<p class="clause-l1">2.1 Bên B được phép sử dụng thương hiệu của Bên A tại địa điểm duy nhất: {{store_address}}</p>
<p class="clause-l1">2.2 Bên B chỉ được thay đổi địa điểm Cửa hàng trong thời gian Hợp đồng có hiệu lực khi Bên B nhận được sự đồng ý chính thức bằng văn bản từ Bên A (là pháp nhân sẽ sở hữu nhãn hiệu “TUI BAO”).</p>
<p class="clause-l1">2.3 Khai thác những lợi ích hữu hình, vô hình trên nền tảng uy tín của thương hiệu TUI BAO để giảm thiểu rủi ro trong kinh doanh đồng thời tiết kiệm thời gian và công sức cho việc quảng bá cửa hàng.</p>
<p class="clause-l1">2.4 Cửa hàng được phép sử dụng thương hiệu TUI BAO là tài sản thuộc quyền sở hữu của Bên B, khi đó bên B sẽ được hưởng các quyền lợi đồng thời phải cam kết thực hiện các nghĩa vụ phát sinh. Cửa hàng được phép sử dụng thương hiệu TUI BAO sẽ đóng vai trò như một chủ thể pháp nhân riêng biệt, có tài khoản riêng, tự mình chịu mọi rủi ro và là người chịu trách nhiệm duy nhất cho lợi nhuận cũng như những thua lỗ của cửa hàng do mình là chủ sở hữu.</p>
<p class="gap">&nbsp;</p>
<p class="art">Điều 3. Giá trị và thời hạn hợp đồng, phương thức thanh toán</p>
<p class="clause-l1">3.1 Bên B được quyền sử dụng nhãn hiệu, hệ thống nhận diện thương hiệu, bí quyết kinh doanh, quy trình vận hành và các quyền thương mại khác thuộc hệ thống TUI BAO theo phạm vi quy định tại Hợp đồng này của Bên A trong thời hạn {{term_years_display}} năm.</p>
<p class="clause-l1">3.2 Giá trị hợp đồng dịch vụ này bao gồm:</p>
<p class="fee-line">- Phí nhượng quyền: {{fee_franchise}} đồng (Bằng chữ: {{fee_franchise_words}})</p>
<p class="fee-line">- Phí marketing thương hiệu: {{fee_marketing}} đồng (Bằng chữ: {{fee_marketing_words}})</p>
<p class="fee-line">- Phí tư vấn triển khai và hỗ trợ vận hành cửa hàng: {{fee_consult}} đồng (Bằng chữ: {{fee_consult_words}})</p>
<p class="fee-line">- Phí marketing khai trương: {{fee_opening}} đồng (Bằng chữ: {{fee_opening_words}})</p>
<p class="dot-line"><span class="bu">&#8226;</span> Tổng giá trị dịch vụ: {{fee_total}} đồng (Bằng chữ: {{fee_total_words}})</p>
<p class="dot-line"><span class="bu">&#8226;</span> Số tiền trên đã bao gồm thuế GTGT.</p>
<p class="dot-line"><span class="bu">&#8226;</span> Tiền ký quỹ bảo đảm thực hiện Hợp đồng: {{fee_deposit}} đồng (Bằng chữ: {{fee_deposit_words}}). Khoản tiền này là khoản để bảo đảm việc thực hiện các nghĩa vụ theo Hợp đồng, không phải là phí nhượng quyền hoặc bất kỳ khoản phí dịch vụ nào khác và sẽ được Bên A hoàn trả cho Bên B khi Hợp đồng chấm dứt, sau khi Bên B hoàn thành đầy đủ các nghĩa vụ theo Hợp đồng và không phát sinh nghĩa vụ bồi thường, công nợ hoặc các khoản phải thanh toán khác. Trường hợp Bên B vi phạm nghĩa vụ theo Hợp đồng, Bên A có quyền khấu trừ hoặc sử dụng khoản tiền ký quỹ để bù đắp thiệt hại theo quy định của Hợp đồng.</p>
<p class="clause-l1">3.3 Phương thức thanh toán:</p>
<p class="fee-line">- Đợt 1: Thanh toán {{pay_1}} đồng tiền ký quỹ bảo đảm thực hiện Hợp đồng.</p>
<p class="fee-line">- Đợt 2: Đặt cọc hợp đồng nhượng quyền: {{pay_2}} đồng ngay sau khi Bên B chuẩn bị mặt bằng để Bên A triển khai các công việc tư vấn, khảo sát mặt bằng và hỗ trợ vận hành cửa hàng cho Bên B.</p>
<p class="fee-line">- Đợt 3: Thanh toán phần giá trị còn lại của hợp đồng này {{pay_3}} đồng ngay sau khi Bên B có và hoàn thiện mặt bằng.</p>
<p class="clause-l1">3.4 Các đơn hàng nguyên liệu tiếp theo:</p>
<p class="clause-l2">(a) Các Bên sẽ lập bảng kê chi tiết theo Phụ lục kèm theo Hợp đồng này.</p>
<p class="clause-l2">(b) Phương thức thanh toán: Theo Điều 8 Hợp đồng này.</p>
<p class="gap">&nbsp;</p>
<p class="art">Điều 4: Các tiêu chí hình thành Cửa hàng TUI BAO</p>
<p>Nhằm tạo ra chuỗi cửa hàng TUI BAO mang phong cách riêng, với mục tiêu chung trong việc chiếm lĩnh thị trường tiềm năng cho các sản phẩm và dịch vụ TUI BAO. Cửa hàng cần đảm bảo những tiêu chí sau:</p>
<p class="section-cap">4.1 ĐỊA ĐIỂM</p>
<p>4.1.1 Lựa chọn và thẩm định địa điểm</p>
<p>(a) Địa điểm đóng một vai trò rất quan trọng trong việc quyết định sự thành công trong kinh doanh của mô hình Cửa hàng TUI BAO. Vì vậy Bên B phải xem xét đánh giá cẩn thận trước khi quyết định thuê mặt bằng.</p>
<p>(b) Quy định về việc lựa chọn địa điểm: Để trở thành một Cửa hàng TUI BAO, BÊN B phải có một địa điểm thỏa mãn một trong các yêu cầu sau:</p>
<p>&#40;i&#41; Diện tích kinh doanh:</p>
<p class="dot-line"><span class="bu">&#8226;</span> Diện tích kinh doanh được hiểu là phần diện tích kinh doanh thực, không bao gồm công trình phụ (nhà vệ sinh, nhà bếp, kho…).</p>
<p class="dot-line"><span class="bu">&#8226;</span> Diện tích kinh doanh phải đáp ứng được: mặt tiền 3,5m trở lên, diện tích đối với địa điểm kinh doanh tối thiểu là 28m2</p>
<p>&#40;ii&#41; Địa điểm kinh doanh: Là địa điểm có khả năng đầu tư vào mặt bằng cao và hội đủ một trong các điều kiện sau:</p>
<p class="dot-line"><span class="bu">&#8226;</span> Là nơi tập trung đông dân cư: gần chợ, trường học, nhà thờ, khu công nghiệp, ký túc xá….</p>
<p class="dot-line"><span class="bu">&#8226;</span> Là nơi dễ thấy, dễ nhìn: không có những vật cản lớn xung quanh.</p>
<p class="dot-line"><span class="bu">&#8226;</span> Là nơi tập trung đông người có thu nhập trung bình thấp.</p>
<p>(c) Ưu tiên những người có mặt bằng là chủ sở hữu địa điểm kinh doanh. Trường hợp mặt bằng thuê thì hợp đồng thuê nhà phải có thời hạn ít nhất 03 năm.</p>
<p class="gap">&nbsp;</p>
<p>4.1.2 Tiến trình thuê mặt bằng: Ngay sau khi xác định được vị trí, địa điểm thuận tiện kinh doanh, Bên B phải thông báo bằng văn bản ngay cho Bên A biết để nhận được sự đồng ý về điều kiện địa điểm nêu tại Điều 4.1.1 (Bên B không thông báo cho bên A khảo sát và đánh giá thì bên A có quyền không tiến hành kí hợp đồng) Sau đó mới tiến hành ký kết hợp đồng thuê mặt bằng.</p>
<p>Để làm rõ, Bên A chỉ thực hiện thẩm định những yếu tố thương mại tại điểm 4.1.1 của nội dung hợp đồng này để đưa ra quyết định. Bên B có nghĩa vụ thẩm định toàn bộ các yếu tố kinh doanh theo điều 4.1</p>
<p>Sau khi hoàn thành các thủ tục thuê mặt bằng và ký hợp đồng sử dụng thương hiệu TUI BAO của Bên A. Bên B sẽ tiến hành xây dựng, sửa chữa và trang trí nội thất Cửa hàng. Việc thiết kế và thi công nội thất phải thực hiện theo đúng bản vẽ thiết kế nội thất đã được bên A phê duyệt. Tùy theo đặc thù từng địa điểm Bên B phải tiến hành sửa chữa hoặc xây dựng Cửa hàng trong vòng 45 ngày. Nếu quá thời hạn 45 ngày mà Cửa hàng vẫn chưa hoạt động kinh doanh thì Bên A có quyền xem xét chấm dứt Hợp đồng trước thời hạn. Trường hợp ngoại lệ cần phải có văn bản do Bên B gửi cho Bên A để xem xét việc kéo dài thời gian thi công nhưng tổng thời gian thi công không được kéo dài quá 90 ngày. Trường hợp kéo dài trên 90 ngày, Bên A có quyền đơn phương chấm dứt Hợp đồng trước thời hạn mà không phải hoàn trả cho Bên B bất cứ một khoản chi phí nào mà Bên B đã thanh toán cho Bên A.</p>
<p class="section-cap">4.2 CỬA HÀNG HỢP TÁC : Điều kiện sau</p>
<p>(a) Có thiện chí hợp tác.</p>
<p>(b) Ưu tiên người có kinh nghiệm kinh doanh trong ngành dịch vụ.</p>
<p>(c) Hiểu biết về thị trường sẽ kinh doanh, có khả năng quản lý.</p>
<p>(d) Biết sử dụng Mail, Facebook, Zalo…</p>
<p class="section-cap">4.3 TUÂN THỦ KHOẢNG CÁCH SO VỚI CÁC BÊN ĐƯỢC PHÉP SỬ DỤNG NHÃN HIỆU CỦA BÊN A HIỆN CÓ</p>
<p>Địa điểm Cửa hàng của Bên B phải tuân thủ khoảng cách (Được đo bằng đường đi của xe máy trên google map) so với cửa hàng TUI BAO hiện có tối thiểu 800m (nếu khác trục đường), 1km (nếu cùng trục đường) đối với khu vực nội thành Hà Nội, nội thành Hồ Chí Minh, các thành phố đông dân và tối thiểu là 2km đối với các khu vực nông thôn, cư dân thưa thớt, ngoại ô các thành phố. Việc xác định khoảng cách tối thiểu này công ty sẽ tiến hành kiểm tra mật độ dân cư và đưa ra quyết định cuối cùng nhằm mục đích không cho đối thủ mở quán để cạnh tranh hệ thống chuỗi TUI BAO.</p>

<p class="art">Điều 5. Nghĩa vụ và quyền lợi của Bên A</p>
<p>5.1 Nghĩa vụ:</p>
<p>5.1.1 Bên A có nghĩa vụ duy trì và phát triển phương thức hoạt động của hệ thống cửa hàng TUI BAO với các tiêu chí như sau:</p>
<p>(a) Tiêu chuẩn của Cửa hàng TUI BAO: Địa điểm, các biển hiệu, biển quảng cáo, thiết kế trang trí nội thất, vật dụng pha chế thức uống, đồng phục của nhân viên, menu của hệ thống, đặt hàng và phương thức thanh toán được thống nhất thiết kế theo phong cách của toàn bộ hệ thống TUI BAO.</p>
<p>(b) Tiêu chuẩn về nhân sự, hệ thống quản lý: Được tuyển chọn, đào tạo và xác lập theo quy định của hệ thống TUI BAO.</p>
<p>(c) Tiêu chuẩn về hàng hóa cung cấp cho các cửa hàng được phép sử dụng thương hiệu TUI BAO: là các sản phẩm do Bên A cung cấp hoặc chỉ định cung cấp và theo cam kết chất lượng của Bên A.</p>
<p>5.1.2 Bên A tư vấn thiết kế Cửa hàng có chỗ ngồi lại miễn phí. Việc trang trí nội ngoại thất cho cửa hàng theo quy định và phong cách của Bên A.</p>
<p>5.1.3 Quảng bá thương hiệu TUI BAO của Bên A luôn là một trong những thương hiệu trà sữa TUI BAO hàng đầu Việt Nam.</p>
<p>5.1.4 Bên A chịu trách nhiệm, hỗ trợ kịp thời cho cửa hàng. Nhằm đảm bảo chất lượng sản phẩm và dịch vụ do Bên A cung cấp. Tư vấn giải pháp kinh doanh nhằm giúp Bên B kinh doanh hiệu quả.</p>
<p>5.1.5 Bên A đảm bảo việc sở hữu quyền khai thác và sử dụng thương hiệu TUI BAO và cấp quyền sử dụng thương hiệu TUI BAO cho Bên B.</p>
<p>5.1.6 Bảo mật các thông tin liên quan đến hoạt động kinh doanh.</p>
<p>5.1.7 Bên A cung cấp đầy đủ giấy chứng nhận chất lượng sản phẩm và giấy chứng nhận VSATTP đối với các sản phẩm mà Bên A cung cấp hoặc chỉ định cung cấp cho Bên B.</p>
<p>5.1.8 Cung cấp đầy đủ tài liệu hướng dẫn vận hành về hệ thống dịch vụ và sản phẩm trà sữa mang thương hiệu TUI BAO cho Bên B.</p>
<p>5.1.9 Bên A có trách nhiệm cung cấp cho Bên B danh mục chi tiết các hạng mục cần xây dựng, các thiết bị cần mua sắm để đáp ứng yêu cầu nhận diện thương hiệu TUI BAO nói trên (bao gồm thiết kế bảng hiệu hình ảnh logo, nội ngoại thất, bàn ghế, vật dụng pha chế, menu, order, đồng phục…) và hướng dẫn thiết kế, trưng bày, trang trí Cửa hàng cho Bên B theo quy định chung của Bên A.</p>
<p class="sub-art">5.2 Quyền lợi</p>
<p>5.2.1 Quyền sở hữu về thương hiệu TUI BAO như: kiểu dáng của sản phẩm, bí mật kinh doanh, quảng cáo thuộc sở hữu của Bên A, chiến lược kinh doanh, biểu tượng kinh doanh, công thức pha chế, nguyên liệu pha chế và tất cả các tài sản sở hữu trí tuệ hữu hình và vô hình khác mang tính chất nhận diện thương hiệu TUI BAO.</p>
<p>5.2.2 Được thanh toán đầy đủ và đúng thời hạn phí sử dụng thương hiệu TUI BAO.</p>
<p>5.2.3 Được giám sát quá trình thi công cửa hàng của Bên B để đáp ứng đúng theo quy định của Bên A. Được kiểm tra định kỳ hoặc đột xuất hoạt động kinh doanh của Bên B nhằm đảm bảo đúng quy định của Bên A. Nhằm mục đích duy trì hiệu quả kinh doanh cho Bên B</p>
<p>5.2.4 Được yêu cầu Bên B báo cáo các vấn đề phát sinh trong quá trình kinh doanh và cung cấp dữ liệu kinh doanh của cửa hàng để Bên A thuận tiện trong quá trình giám sát hoạt động kinh doanh của Bên B. và chấn chỉnh kịp thời nhưng sai sót phát sinh.</p>
<p>5.2.5 Được quyền thay đổi phần mềm, hệ thống tính tiền, công thức pha chế, nguyên liệu, menu và các tiêu chuẩn vận hành khác khi Bên A nhận thấy cần thiết.</p>
<p>5.2.6 Được quyền đặt thêm địa điểm kinh doanh cách địa điểm bên B tối thiểu 800m đối với khu vực nội thành Hà Nội, nội thành Hồ Chí Minh, các thành phố đông dân và tối thiểu là 2km đối với các khu vực nông thôn, cư dân thưa thớt, ngoại ô các thành phố. Việc xác định khoảng cách tối thiểu này do Bên A tiến hành kiểm tra mật độ dân cư và quyết định.</p>

<p class="art">Điều 6. Nghĩa vụ &amp; quyền lợi của bên B</p>
<p>6.1 Nghĩa vụ:<br/>
6.1.1 Đăng ký giấy phép kinh doanh và giấy chứng nhận vệ sinh an toàn thực phẩm theo quy định pháp luật trước khi Cửa hàng đi vào hoạt động; Tự mình đầu tư các cơ sở vật chất, nguồn tài chính và nhân sự theo yêu cầu của Bên A bao gồm:<br/>
(a) Trực tiếp đứng ra ký kết hợp đồng thuê mặt bằng/ cửa hàng với bên cho thuê sau khi được Bên A phê duyệt về mặt bằng và địa điểm của Bên B;<br/>
(b) Thanh toán mọi chi phí đầu tư của cửa hàng theo chuẩn của Bên A. và thanh toán tiền lương cho nhân viên làm việc tại cửa hàng của Bên B.<br/>
6.1.2 Tham gia đầy đủ các khóa đào tạo nội bộ do Bên A tổ chức (đào tạo đại lý mới, đào tạo định kỳ, tái đào tạo, đào tạo sản phẩm mới…). Bên B cam kết hoàn thành đạt yêu cầu các khóa đào tạo do Bên A tổ chức. Sau đó mới bắt đầu triển khai hoạt động Cửa hàng.<br/>
6.1.3 Cam kết kinh doanh lành mạnh, tuân thủ đầy đủ các quy định về phòng cháy chữa cháy và vệ sinh môi trường theo quy định của Pháp luật mà không ảnh hưởng đến Bên A.<br/>
6.1.4 Có trách nhiệm xây dựng, bảo vệ uy tín, hình ảnh và logo của Bên A.<br/>
(a) Trang bị đủ các thiết bị chuẩn để thực hiện và sử dụng đúng mục đích.<br/>
(b) Không sử dụng thiết bị khác để cạnh tranh với Bên A.<br/>
(c) Thiết kế cửa hàng, trang trí nội ngoại thất và phong cách theo đúng quy định của Bên A.<br/>
(d) Với những hạng mục mà Bên B tự thực hiện theo nhu cầu thực tế cần phải có sự tham khảo ý kiến và sự đồng ý chính thức bằng văn bản của Bên A.<br/>
(e) Không trưng bày các biểu tượng, logo, hình ảnh mang tính quảng cáo của sản phẩm khác cạnh tranh với nhãn hiệu của Bên A.<br/>
(f) Đảm bảo cửa hàng luôn giữ vệ sinh sạch sẽ, sản phẩm chất lượng, có đầy đủ giấy chứng nhận VSATTP đối với sản phẩm bán cho khách hàng.<br/>
(g) Trong quá trình Bên B hoạt động kinh doanh Bên A có thay đổi về concept, nguyên liệu, công thức pha chế, thay đổi menu, chiến lược kinh doanh nhằm phát triển hệ thống thì Bên B phải tuân thủ.<br/>
6.1.5 Tuân thủ quy trình của Bên A trong suốt quá trình hoạt động: Bên B cam kết:<br/>
(a) Không khai trương, không thực hiện hoạt động kinh doanh của cửa hàng nếu chưa hoàn thành các nội dung sau:<br/>
&#40;i&#41; Chưa thực hiện và hoàn thành khóa đào tạo nội bộ do Bên A tổ chức;<br/>
&#40;ii&#41; Thiết kế thi công cửa hàng không tuân thủ về nhận diện thương hiệu TUI BAO của Bên A và cơ sở vật chất cửa hàng không đảm bảo;<br/>
&#40;iii&#41; Không đảm bảo số lượng nhân sự tại cửa hàng tối thiểu 02 nhân viên/ca. Riêng trong ngày khai trương Bên B phải huy động thêm nhân sự để đảm bảo vận hành cửa hàng đạt hiệu quả cao (số lượng nhân viên tối thiểu nêu trên phải làm toàn thời gian hoặc huy động thêm nhân sự làm bán thời gian).<br/>
&#40;iv&#41; Đảm bảo các ấn phẩm truyền thông phải hoàn thành trước ngày khai trương tối thiểu 02 ngày. Cụ thể như: tờ rơi, voucher, băng rôn, chạy fanpage cửa hàng…<br/>
(b) Không khai trương, không thực hiện hoạt động kinh doanh của Bên B. Nếu Bên A chưa phê duyệt.<br/>
6.1.6 Phối hợp thực hiện cùng Bên A tổ chức các hoạt động PR, quảng cáo, thông tin đại chúng, khuyến mãi và các hoạt động khác liên quan đến phục vụ, quản lý . . .trong từng thời điểm. Các chương trình marketing của Bên B nếu giảm giá từ 30% trở xuống có thể tùy ý triển khai, nếu giảm giá trên 30% phải thực hiện đăng ký chương trình cho công ty ít nhất 5 ngày để thông báo cho các quán gần đó cùng tham gia. Luôn xây dựng, bảo vệ uy tín, hình ảnh và logo của Bên A nhằm kinh doanh đạt hiệu quả cao.<br/>
6.1.7 Chấp nhận sự kiểm soát, giám sát và hướng dẫn của Bên A; tuân thủ các yêu cầu về thiết kế, sắp xếp địa điểm bán hàng, cung ứng dịch vụ theo yêu cầu của Bên A. Bên B có trách nhiệm phối hợp và tạo mọi điều kiện thuận lợi cho cán bộ của Bên A kiểm tra toàn bộ Cửa hàng (khu vực kinh doanh, kho,…) trong mọi trường hợp và cung cấp đầy đủ thông tin về thực trạng kinh doanh.<br/>
6.1.8 Giữ bí mật kinh doanh, công thức pha chế của sản phẩm trà sữa mang nhãn hiệu “TUI BAO” của Bên A kể từ ngày ký Hợp đồng và có trách nhiệm bảo mật thông tin ngay cả sau khi Hợp đồng chấm dứt.<br/>
6.1.9 Tháo dỡ, hoàn trả lại toàn bộ hình ảnh và logo liên quan đến thương hiệu TUI BAO của Bên A. Đồng thời, chấm dứt việc sử dụng thương hiệu TUI BAO của Bên A khi chấm dứt hợp đồng.<br/>
6.1.10 Trả đầy đủ phí sử dụng thương hiệu TUI BAO và các chi phí phát sinh do hoạt động hợp tác kinh doanh cho Bên A.<br/>
6.1.11 Bên B không được phép sang nhượng cửa hàng cho Bên thứ 3. Nếu Bên A phát hiện thì Bên A có quyền đơn phương chấm dứt hợp đồng. Đồng thời Bên B phải chịu trách nhiệm các chi phí phát sinh cho Bên A khi hậu quả do Bên thứ 3 gây ra.<br/>
6.1.12 Khi đăng ký các apps giao hàng như SHOPEEFOOD, GOFOOD, GRABFOOD, BAEMIN, BEFOOD...phải đưa đầy đủ menu cố định của Bên A lên các apps đó và giá bán là giá bán do Bên A quy định. Phải gửi hình ảnh của từng ly nước trong menu cho các apps up hình sản phẩm để tránh các apps đưa hình ảnh không đẹp làm ảnh hưởng đến kinh doanh của Bên A. Khi Bên A thay đổi menu bên B phải có nghĩa vụ gửi menu mới cho các apps liên kết.<br/>
6.1.13 Ngoài nhãn hiệu của Bên A Bên B không được mở kinh doanh, góp vốn kinh doanh thêm bất kỳ thương hiệu cạnh tranh nào khác trong lĩnh vực trà sữa.<br/>
6.1.14 Trong trường hợp Bên B muốn thay đổi chủ sở hữu của cửa hàng phải được sự đồng ý bằng văn bản của Bên A.<br/>
6.1.15 Bên B phải đảm bảo tất cả các nguyên liệu, bao bì, bao gói, trang thiết bị sử dụng tại Cửa hàng phải được nhập trực tiếp từ Bên A hoặc do bên A quy định theo phụ lục hợp đồng. Khi Bên A có thay đổi về nguyên liệu thì Bên B phải tuân theo. Bên B không được phép bán nguyên liệu do Bên A cung cấp ra thị trường.<br/>
6.1.16 Bên B phải sử dụng đồng nhất theo phần mềm bán hàng do Bên A quy định và máy bán hàng này phải kết nối với máy chủ của Bên A. Bên A có quyền quản trị cao nhất và quản lý dữ liệu bán hàng tại Cửa hàng. Mọi dữ liệu Bên B muốn thêm vào cần có sự cho phép từ Bên A.<br/>
Bên B có trách nhiệm cung cấp các tài khoản đăng nhập, password vào hệ thống phần mềm bán hàng cho Bên A để Bên A kiểm tra doanh thu cũng như camera tại Cửa hàng của Bên B. Việc kiểm tra này giúp Bên A nắm bắt được quy trình làm việc và tình hình kinh doanh của Bên B để kịp thời tư vấn và khắc phục sự cố cho Bên B.<br/>
Tại Cửa hàng của Bên B theo hợp đồng bắt buộc phải được lắp mạng internet.<br/>
6.1.17 Bên B phải trung thực trong quá trình hoạt động kinh doanh và báo cáo cho Bên A theo dõi. Bên B có nghĩa vụ nhập toàn bộ dữ liệu bán hàng vào các phần mềm tính tiền và các phần mềm khác mà Bên A yêu cầu để Bên A theo dõi và hỗ trợ hoạt động kinh doanh cho Bên B.<br/>
6.1.18 Tuân thủ nghiêm chỉnh mọi sự thay đổi của Bên A sau khi nhận được thông báo thay đổi.<br/>
6.1.19 Đảm bảo đơn hàng mỗi tháng lấy tối thiểu là 15.000.000 đồng (Mười lăm triệu đồng chẵn).<br/>
6.1.20 Khi Bên A ra sản phẩm mới hoặc công thức mới. Bên B có trách nhiệm tham gia đào tạo, cập nhật công thức triển khai thực hiện kinh doanh.<br/>
6.1.21 Phải đảm bảo luôn có 1 nhân viên mặc đồng phục của TUI BAO trong ca làm việc. Nếu không có nhân viên thì chủ quán cũng phải mặc đồng phục.<br/>
6.1.22 Phải bán đầy đủ các thức uống trong Menu chính của Bên A tại địa chỉ Cửa hàng của Bên B</p>
<p>6.2 Quyền lợi<br/>
6.2.1 Quyền khai thác khách hàng<br/>
Khai thác khách hàng của BÊN B được hiểu là chỉ nằm trong phạm vi của Bên B.<br/>
6.2.2 Hưởng lợi ích trực tiếp hoặc gián tiếp từ các chương trình quảng cáo do Bên A thực hiện.<br/>
Đây là những chương trình được Bên A thực hiện chung cho toàn hệ thống nhằm làm cho người tiêu dùng ngày càng biết đến hình ảnh và logo TUI BAO và khẳng định vị trí số 1 trong lĩnh vực trà sữa tại Việt Nam. Nhằm mục đích khuyến khích người tiêu dùng sử dụng các sản phẩm của hệ thống TUI BAO bao gồm:<br/>
(a) Tham gia các hội thảo liên quan tới lĩnh vực trà sữa.<br/>
(b) Tài trợ các chương trình hoạt động thu hút nhiều người tiêu dùng.<br/>
(c) Thực hiện các chương trình PR, quảng cáo hình ảnh và logo TUI BAO trên các phương tiện thông tin đại chúng trong và ngoài nước.</p>

<p class="art">Điều 7. Sản phẩm</p>
<p>7.1 Các sản phẩm Bên A cung cấp cho Bên B là:<br/>
(a) Các nguyên liệu do bên A cung cấp, sử dụng trong việc pha chế thức uống của TUI BAO (theo Phụ lục đính kèm).<br/>
(b) Đơn hàng đầu tiên bao gồm máy móc và nguyên liệu sẽ giao sau tối thiểu 7 ngày kể từ khi Bên B chuyển đủ tiền cho bên thứ 3 Bên A chỉ định cung cấp (trừ ngày thứ 7, chủ nhật và lễ tết)<br/>
(c) Giá nguyên liệu có thể tăng hoặc giảm, nếu tăng thì tăng không quá 10%/ năm. Sử dụng nguyên liệu khác thay thế có thể tăng hoặc giảm giá so với loại cũ, nếu tăng cũng không được vượt quá 10%/năm.</p>
<p>7.2 Bên B đảm bảo và cam kết:<br/>
(a) Tất cả các nguyên liệu, bao bì sử dụng của Bên B phải được nhập trực tiếp từ Bên A hoặc do bên A chỉ định và khi có thay đổi về nguyên liệu thì Bên B phải tuân theo.<br/>
(b) Chỉ sử dụng các sản phẩm do Bên A cung cấp hoặc do Bên A chỉ định theo phụ lục đính kèm.<br/>
(c) Bên B không được phép bán nguyên liệu Bên A cung cấp ra bên ngoài thị trường.<br/>
(d) Bảo quản nguyên liệu và trang thiết bị theo đúng tiêu chuẩn vệ sinh an toàn thực phẩm hoặc tiêu chuẩn do Bên A hướng dẫn.</p>

<p class="art">Điều 8: Phương thức đặt hàng, giao hàng và đổi trả hàng</p>
<p>8.1 Đặt hàng<br/>
a. Bên B lên đơn hàng và gửi Zalo cho Bộ phận chăm sóc đại lý (CSĐL) của Bên A. Khi nhận đơn hàng Bộ phận Chăm sóc đại lý của Bên A sẽ thống nhất thời gian có thể giao hàng. Tiền hàng phải được chuyển vào tài khoản ngân hàng của Bên A trước khi giao nhận hàng. Khi chuyển tiền cần ghi rõ tên cửa hàng chuyển tiền.<br/>
b. Đơn hàng mỗi lần lấy tối thiểu là {{order_min_free}} đồng ({{order_min_free_words}}) đơn hàng này sẽ được Bên A giao miễn phí trong nội thành TPHCM, TP Hà Nội hoặc ra các chành để chuyển đi tỉnh.<br/>
c. Nếu đơn hàng dưới {{order_min_ship}} đồng ({{order_min_ship_words}}) đơn hàng này sẽ được Bên A giao với phí ship {{order_ship_fee}} đồng trong nội thành TPHCM, TP Hà Nội hoặc ra các chành để chuyển đi tỉnh.<br/>
d. Nếu đơn hàng dưới {{order_min_pickup}} đồng ({{order_min_pickup_words}}) Bên B phải tự đến kho lấy hàng<br/>
e. Các đơn hàng ở tỉnh giáp ranh TPHCM, TP Hà Nội nếu Bên B có nhu cầu giao hàng tận nơi thì phí vận chuyển 2 bên tự thỏa thuận.</p>
<p>8.2 Giao hàng<br/>
Đơn hàng của Bên B sẽ được giao nhận theo các hạn mức như sau:<br/>
(a) Với đơn hàng trong nội thành Thành Phố Hồ Chí Minh, Thành Phố Hà Nội Bên A sẽ giao hàng trong vòng 36 tiếng kể từ ngày nhận tiền (trừ ngày thứ 7, chủ nhật, lễ tết)<br/>
(b) Với đơn hàng tỉnh Bên A sẽ chuyển hàng ra chành xe hoặc nhà xe trong vòng 36 tiếng kể từ ngày nhận tiền (trừ thứ 7, chủ nhật, lễ tết).<br/>
Việc đặt hàng và giao hàng được thực hiện trong giờ hành chính và các ngày trong tuần (trừ chủ nhật và các ngày lễ tết) Nếu không đặt đúng thời gian này bộ phận giao hàng của Bên A không chịu trách nhiệm.<br/>
Bên A sẽ thông báo cho Bên B số lượng hàng hóa Bên A có thể cung cấp tại thời điểm Bên B đặt hàng. Nếu số lượng đặt hàng của Bên B lớn hơn lượng hàng Bên A có tại kho thì hai bên sẽ thỏa thuận về thời gian chuyển hàng đầy đủ. Một số mặt hàng Bên A chuẩn bị chưa kịp thì Bên B khi đặt hàng phải chờ cho đến khi có hàng mới.<br/>
Bên B cần kiểm hàng và báo lại số lượng trong vòng 48 giờ kể từ khi nhận hàng. Nếu báo sau 48 giờ thì các bộ phận của Bên A không chịu trách nhiệm nếu đơn hàng bị thiếu.</p>
<p>8.3 Đổi trả hàng<br/>
8.3.1 Khi Bên B phát hiện nguyên liệu có vấn đề do ngoài ý muốn. Bên A sẽ đổi nguyên liệu mới cho Bên B.<br/>
8.3.2 Bên B được phép đổi trả hàng theo quy trình tiếp nhận hàng đổi trả của Bên A theo Phụ lục kèm theo Hợp đồng này.<br/>
Số lượng nguyên liệu lỗi do ngoài ý muốn của Bên A thì Bên A sẽ đổi và tiến hành kiểm tra sản phẩm đó. Nếu Bên A kiểm tra sản phẩm đó lỗi do Bên B gây ra thì Bên A sẽ không thu hồi và trả lại cho Bên B. Các chi phát sinh cụ thể như: vận chuyển, kiểm tra…. Bên B chịu.<br/>
8.3.3 Các trường hợp còn lại bên B chỉ được đổi trả hàng khi có chính sách thu hồi sản phẩm của bên A.</p>

<p class="art">Điều 9: Các điều kiện, tiêu chuẩn duy trì cửa hàng và đảm bảo chất lượng</p>
<p>Bên B phải đảm bảo các điều kiện kinh doanh, tiêu chuẩn duy trì cửa hàng và đảm bảo chất lượng theo tiêu chuẩn do Bên A quy định cụ thể như sau:<br/>
a) Tiêu chuẩn vệ sinh: Bên B phải giữ gìn vệ sinh luôn sạch sẽ tại cửa hàng trong mọi thời điểm kinh doanh trong ngày và bao gồm ở tất cả các vị trí như cửa ra vào, quầy bar, bếp, nhà vệ sinh, khu vực khách hàng, sàn nhà, trần nhà, bàn ghế, các trang thiết bị đều phải được về sinh thường xuyên đảm bảo sạch sẽ ngăn nắp.<br/>
Bên A kiểm tra và đánh giá mức độ vệ sinh sạch sẽ của cửa hàng. Bên A có trách nhiệm và chức năng lấy ý kiến khách hàng tại cửa hàng đó mà không cần có sự báo trước, không giới hạn số lần kiểm tra đột xuất hoặc định kì để có ý kiến đánh giá. Trên cơ sở những ý kiến đánh giá đó Bên A sẽ tiến hành họp định kì với các cửa hàng để yêu cầu điều chỉnh và kịp thời khắc phục nhưng thiếu sót mà khách hàng góp ý. Tùy mức độ vi phạm Bên A có thể đưa ra các biện pháp xử lý theo quy định của hợp đồng này.<br/>
b) Tiêu chuẩn về hình thức: các thiết bị, hình ảnh và logo TUI BAO luôn phải được thực hiện đầy đủ đúng quy định.<br/>
c) Tiêu chuẩn về chất lượng sản phẩm: đảm bảo pha chế đúng công thức và sử dụng đúng nguyên liệu do Bên A cung cấp. Nhân viên tác phong nhanh nhẹn, lễ phép, vui vẻ, ân cần, chu đáo, nhiệt tình, trung thực với khách hàng.</p>

<p class="art">Điều 10. Phạt vi phạm và bồi thường Hợp đồng</p>
<p>10.1 Bên B vi phạm:<br/>
10.1.1 Phạt từ 2.000.000 đồng Việt Nam (hai triệu đồng) cho một lần vi phạm do các hành vi sau:<br/>
- Tự ý thay đổi giá bán sản phẩm trong menu chính của TUI BAO mà chưa được sự chấp thuận của Bên A;<br/>
- Không tuân thủ chính sách liên kết với các apps giao hàng theo điều 6.1.12 của hợp đồng này<br/>
- Không tuân thủ quy định mặc đồng phục theo điều 6.1.21 của hợp đồng này<br/>
- Có hành vi cạnh tranh không lành mạnh khác.<br/>
- Không kịp thời báo cáo hoặc chủ động xử lý các sự cố xảy ra với khách hàng khiến sự việc để lâu và phát sinh hậu quả</p>
<p>10.1.2 Phạt từ 3.000.000 đồng Việt Nam (ba triệu đồng) cho một lần vi phạm do các hành vi sau:<br/>
- Không duy trì tiêu chuẩn duy trì và đảm bảo cửa hàng theo quy định tại Điều 6 của hợp đồng này;<br/>
- Mọi hành vi vu khống, nói xấu và phản bác không đúng gây ảnh hưởng uy tín đến Bên A<br/>
- Thiếu hợp tác cùng Bên A để giải quyết các vấn đề mâu thuẫn, sự cố xảy ra với khách hàng.</p>
<p>10.1.3 Phạt 10.000.000 đồng Việt Nam (Mười triệu đồng) Nếu phát hiện tự ý nhập nguyên liệu ngoài không do Bên A chỉ định (Bao gồm cả ly nhựa, màng ép, đồng phục...) theo khoản 6.1.15 điều 6 của hợp đồng này.</p>
<p>10.1.4 Phạt 5.000.000 đồng Việt Nam (năm triệu đồng) Nếu Bên B không cung cấp được 02 (hai) loại giấy gồm: giấy phép đăng ký kinh doanh và giấy chứng nhận vệ sinh an toàn thực phẩm theo đúng quy định của pháp luật. Bên A có quyền đình chỉ hoạt động kinh doanh của Bên B đến khi Bên B hoàn tất các thủ tục giấy tờ trên.</p>
<p>10.1.5 Đình chỉ hoạt động kinh doanh với các trường hợp sau:<br/>
(a) Nếu Bên B không tham gia các khóa đào tạo do Bên A triển khai và thông báo. Bên A có quyền đình chỉ hoạt động khai trương của Bên B đến khi tham gia đầy đủ các khóa đạo do Bên A tổ chức<br/>
(b) Nếu Bên B không tham các khóa đào tạo của Bên A tổ chức gồm: Phương thức bán hàng và quản lý. Đào tạo pha chế và bảo quản sản phẩm mới…. Nếu Bên A phát hiện hoạt động kinh doanh của Bên B đang vi phạm về quy trình pha chế, chất lượng sản phẩm…. Bên A có quyền tạm ngưng hợp đồng và yêu cầu Bên B phải dừng hoạt động kinh doanh đến khi Bên B hoàn tất và khắc phục sai phạm trên.</p>
<p>10.1.6 Vi phạm mục 6.1.3 Điều 6:<br/>
Bên B hoàn toàn chịu trách nhiệm trước pháp luật trong trường hợp này. Bên A có quyền đơn phương chấm dứt hợp đồng. Đồng thời Bên A không bồi thường và không hoàn trả bất cứ khoản phí nào cho Bên B</p>
<p>10.1.7 Vi phạm Điều 6 khoản 6.1.4, 6.1.5: Bên A có quyền đơn phương chấm dứt hợp đồng mà không bồi thường và không hoàn trả bất cứ khoản phí nào cho Bên B. Đồng thời Bên B phải bồi thường cho Bên A một khoản bằng khoản phí sử dụng thương hiệu TuiBao nêu tại Điều 3 khoản 3.1</p>
<p>10.1.8 Vi phạm Điều 6 khoản 6.1.6, 6.1.7, 6.1.9, 6.1.12, 6.1.16, 6.1.17, 6.1.18, 6.1.19, 6.1.20, 6.1.21, 6.1.22<br/>
(a) Ngay sau khi có văn bản thông báo lần 1 của Bên A. Trong vòng 3 ngày Bên B phải chấm dứt ngay hành vi vi phạm và có biện pháp khắc phục<br/>
(b) Nếu sau 03 lần thông báo bằng văn bản của Bên A. Bên B vẫn vi phạm Điều 6 một trong các khoản nêu trên Bên A có quyền đơn phương chấm dứt hợp đồng không bồi thường, không hoàn trả bất cứ một khoản chi phí nào cho Bên B. Đồng thời Bên B phải chấm dứt ngay hoạt động kinh doanh và bồi thường cho Bên A một khoản bằng khoản phí sử dụng thương hiệu Tui Bao nêu tại Điều 3 khoản 3.1</p>
<p>10.1.9 Vi phạm Điều 6 khoản 6.1.8, 6.1.14<br/>
Bên A có quyền đơn phương chấm dứt hợp đồng không bồi thường, không hoàn trả bất cứ một khoản phí nào (bao gồm cả chi phí đặt cọc) cho Bên B. Đồng thời Bên B phải bồi thường cho Bên A một khoản bằng khoản phí sử dụng thương hiệu nêu tại Điều 3 khoản 3.1</p>
<p>10.1.10 Bên A có quyền đơn phương chấm dứt hợp đồng với với các trường hợp vi phạm sau: Tự ý lập group gây chia rẽ, vu khống, nói xấu không đúng sự thật gây hậu quả nghiêm trọng đến việc kinh doanh và phát triển của Bên A.</p>
<p>10.2 Bên A vi phạm<br/>
Trường hợp Bên A vi phạm các cam kết và bảo đảm theo hợp đồng thì sẽ phải có trách nhiệm bồi thường thiệt hại cho Bên B do các hành vi sau:<br/>
(a) Không cung cấp đầy đủ hóa đơn bán hàng, chứng từ xuất xứ hàng hóa, theo quy định của pháp luật gây thiệt hại cho Bên B thì Bên A chịu trách nhiệm.<br/>
(b) Trường hợp xác định lỗi ngoài ý muốn do Bên A có thể gây thiệt hại cho Bên B. Bên A có trách nhiệm giải trình cho Bên B trong thời gian (03) ngày làm việc kể từ thời điểm xác nhận lỗi:<br/>
(c) Nếu Bên A chứng minh được việc lỗi hoặc không vi phạm hay trường hợp vi phạm đó do ngoài ý muốn theo khoản nêu trên. Hai bên cùng thỏa thuận và thương lượng để đưa ra hướng xử lý và khắc phục theo quy định.</p>
<p>10.3 Thời hạn thanh toán tiền phạt:<br/>
Tiền phạt và bồi thường thiệt hại hợp đồng thời gian chậm nhất 7 ngày kể từ ngày bên vi phạm nhận được văn bản thông báo về tiền phạt và bồi thường hợp đồng từ bên bị vi phạm.</p>
<p>10.4 Hình thức thanh toán: bằng tiền mặt hoặc chuyển khoản.</p>

<p class="art">Điều 11. Ký quỹ thực hiện hợp đồng</p>
<p>11.1.1 Ngay khi ký kết Hợp đồng, Bên B phải ký quỹ hợp đồng với số tiền là {{pay_1}} đồng ({{pay_1_words}}). Khoản tiền ký quỹ Hợp đồng này sẽ được Bên A giữ lại và hoàn trả cho Bên B sau khi kết thúc Hợp đồng {{term_years_display}} năm.<br/>
11.1.2 Khoản tiền ký quỹ này sẽ được sử dụng khi Bên B vi phạm một trong các nghĩa vụ nêu tại Hợp đồng này và các phụ lục (nếu có). Khoản tiền này cũng được sử dụng trong các trường hợp mà Bên B vi phạm nghiêm trọng đến hình ảnh thương hiệu mà cần phải đưa ra cơ quan có thẩm quyền giải quyết.<br/>
11.1.3 Trường hợp Bên B đơn phương chấm dứt Hợp đồng trước thời hạn hoặc vi phạm Hợp đồng dẫn đến việc Hợp đồng bị chấm dứt trước thời hạn, Bên B sẽ không được hoàn lại khoản tiền ký quỹ nêu trên.</p>

<p class="art">Điều 12. Hiệu lực hợp đồng</p>
<p>Hợp đồng này thời hạn {{term_years_display}} năm tại địa chỉ số: {{store_address}}</p>

<p class="art">Điều 13. Chấm dứt hợp đồng</p>
<p>Hợp đồng này có thể được chấm dứt và thanh lý theo các trường hợp sau:<br/>
13.1 Hết thời hạn mà hai Bên không tiến hành gia hạn.<br/>
13.2 Khi một trong các Bên đơn phương chấm dứt Hợp đồng do bên còn lại vi phạm điều khoản hợp đồng.<br/>
13.3 Bên B tự ý ngừng hoạt động kinh doanh liên tục trong 02 (hai) tháng mà không thông báo lý do hoặc nguyên nhân bằng văn bản cho Bên A.<br/>
13.4 Bên B vi phạm pháp luật gây hậu quả nghiêm trọng ảnh hưởng việc kinh doanh và sự phát triển của Bên A.<br/>
13.5 Hợp đồng không thể thực hiện vì sự kiện bất khả kháng theo Điều 14.</p>

<p class="art">Điều 14. Bất khả kháng</p>
<p>14.1 Hai bên cùng thống nhất không thể thực hiện hợp đồng với trường hợp bất khả kháng và theo đó các bên không phải chịu trách nhiệm về sự chậm trễ trong quá trình thực hiện hợp đồng. Cụ thể sau: Động đất, bão, lũ, lụt, lốc, sóng thần, lở đất, hoả hoạn, chiến tranh và các thảm họa khác được coi là sự kiện bất khả kháng.<br/>
14.2 Hai bên cùng thống nhất thời gian hợp đồng được thực hiện khi trường hợp bất khả kháng đã xử lý và khắc phục xong.</p>

<p class="art">Điều15: Cách nhận thông tin:</p>
<p><strong>Cách nhận thông tin:</strong> Bên A với tư cách là chủ sở hữu nhãn hiệu TUI BAO tại Việt Nam xử lý thông tin. Khi nhận thông tin Bên A có trách nhiệm thực hiện xử lý thông tin theo những nguyên tắc sau nhằm đảm bảo thông tin được xử lý khách quan, trung thực, đa chiều và công bằng:<br/>
a) Thông tin được nhận đa chiều từ phía người cung cấp, người bị phản ánh và các bên liên quan. Mọi thông tin xin phản ánh về hòm thư hotro@tuibao.vn hoặc zalo số 0984.303.432<br/>
b) Việc xử lý thông tin được quyết định sau khi có đủ bằng chứng xác thực và đầy đủ chứng từ của các bên có liên quan.<br/>
c) Bên A sẽ gửi các tài liệu hướng dẫn, thông báo và các thay đổi cho bên B qua email: {{party_b_email}} hoặc {{party_b_phone}}. Bên B phải thường xuyên check email và zalo để đảm bảo việc kinh doanh được thuận lợi và hiệu quả cao.</p>

<p class="art">Điều 16. Điều khoản chung</p>
<p>16.1 Trường hợp xảy ra mâu thuẫn hoặc tranh chấp phát sinh giữa hai bên trong quá trình thực hiện hợp đồng. Hai bên thống nhất cùng đồng ý hòa giải và thương lượng.<br/>
16.2 Trường hợp xảy ra mâu thuẫn hoặc tranh chấp phát sinh giữa hai bên trong quá trình thực hiện hợp đồng mà không thể thống nhất hòa giải và thương lượng được. Hai bên thỏa thuận, đồng ý yêu cầu các cơ quan chức năng có thẩm quyền và tòa án giải quyết theo quy định của pháp luật.<br/>
16.3 Hợp đồng này được làm thành 02 (hai) bản mỗi bản gồm 15 (mười lăm) tờ, có giá trị pháp lý như nhau, mỗi bên giữ 01 bản.</p>

<br/><br/>
<table class="sign-table" cellpadding="0" cellspacing="0" width="100%" border="0" nobr="true">
	<tr>
		<td width="50%" align="center" valign="top">
			<span class="sign-head">ĐẠI DIỆN BÊN A</span><br/>
			<span class="sign-head">BÊN NHƯỢNG QUYỀN</span><br/>
			<span class="sign-head">CÔNG TY CỔ PHẦN THƯƠNG MẠI<br/>DỊCH VỤ SẢN XUẤT NGUYÊN KHOA</span><br/>
			<span class="sign-note">(Ký, đóng dấu và ghi rõ họ tên)</span>
		</td>
		<td width="50%" align="center" valign="top">
			<span class="sign-head">ĐẠI DIỆN BÊN B</span><br/>
			<span class="sign-head">BÊN NHẬN NHƯỢNG QUYỀN</span><br/>
			<span class="sign-head">&nbsp;<br/>&nbsp;</span><br/>
			<span class="sign-note">(Ký, ghi rõ họ tên)</span>
		</td>
	</tr>
	<tr>
		<td width="50%" align="center" class="sign-gap" height="110" style="height:110px;">&nbsp;</td>
		<td width="50%" align="center" class="sign-gap" height="110" style="height:110px;">&nbsp;</td>
	</tr>
	<tr>
		<td width="50%" align="center" valign="top"><span class="sign-name">NGUYỄN ĐÌNH QUỐC DŨNG</span></td>
		<td width="50%" align="center" valign="top"><span class="sign-name">{{party_b_name}}</span></td>
	</tr>
</table>
HTML;
	}
}
