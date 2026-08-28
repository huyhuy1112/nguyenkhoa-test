{* Create / Edit Lead — Tag-Driven CRM (UI only, no save). *}
{strip}
{assign var=MK_LIST_URL value='index.php?module=Leads&view=List&app=SALES'}
{assign var=MK_IS_EDIT value=!empty($MK_LEADS_EDIT_MODE) && !empty($MK_LEAD_RECORD_ID)}
{assign var=MK_DETAIL_URL value='index.php?module=Leads&view=Detail&record='|cat:$MK_LEAD_RECORD_ID|cat:'&app=SALES'}
{if $MK_IS_EDIT}
	{assign var=MK_CANCEL_URL value=$MK_DETAIL_URL}
{else}
	{assign var=MK_CANCEL_URL value=$MK_LIST_URL}
{/if}
<div class="mk-td-create" id="mk-td-create" data-record-id="{$MK_LEAD_RECORD_ID|escape:'html'}"{if $MK_IS_EDIT} data-mode="edit"{/if}>
	<header class="mk-td-create__head">
		<nav class="mk-td-create__crumb" aria-label="Breadcrumb">
			<a href="{$MK_LIST_URL}">Khách hàng tiềm năng</a>
			<span class="mk-td-create__crumb-sep">/</span>
			{if $MK_IS_EDIT}
				<a href="{$MK_DETAIL_URL}" id="mk-td-crumb-record">{$MK_LEAD_RECORD_ID|escape}</a>
				<span class="mk-td-create__crumb-sep">/</span>
				<span>Chỉnh sửa</span>
			{else}
				<span>Mới</span>
			{/if}
		</nav>
		<div class="mk-td-create__head-row">
			<div>
				<h1 class="mk-td-create__title">{if $MK_IS_EDIT}Chỉnh sửa khách hàng tiềm năng{else}Tạo khách hàng tiềm năng{/if}</h1>
				<p class="mk-td-create__subtitle">Mỗi lựa chọn sẽ tự động gắn tag để hệ thống chạy đúng workflow, journey &amp; script bán hàng.</p>
			</div>
			<div class="mk-td-create__head-actions">
				<a class="mk-td-btn mk-td-btn--ghost" href="{$MK_CANCEL_URL}">Hủy</a>
				<button type="button" class="mk-td-btn mk-td-btn--dark" id="mk-td-save-top">
					<span class="mk-td-btn__ic" aria-hidden="true">💾</span>
					{if $MK_IS_EDIT}Lưu thay đổi{else}Lưu khách hàng tiềm năng{/if}
				</button>
			</div>
		</div>
	</header>

	<div class="mk-td-create__layout">
		<div class="mk-td-create__main">
			<section class="mk-td-card" data-section="customer-type">
				<header class="mk-td-card__head">
					<span class="mk-td-card__num">00</span>
					<div>
						<h2 class="mk-td-card__title"><span class="mk-td-card__ico" aria-hidden="true">👤</span> Loại khách</h2>
						<p class="mk-td-card__desc">Cá nhân hay Doanh nghiệp — gắn tag <code>individual</code> / <code>company</code></p>
					</div>
				</header>
				<div class="mk-td-card__body">
					<div class="mk-td-choice-row mk-td-choice-row--2" role="group" aria-label="Loại khách">
						<button type="button" class="mk-td-choice is-on" data-tag="individual" data-group="customer-type" data-value="individual">
							<span class="mk-td-choice__ico">👤</span>
							<span class="mk-td-choice__label">Cá nhân</span>
						</button>
						<button type="button" class="mk-td-choice" data-tag="company" data-group="customer-type" data-value="company">
							<span class="mk-td-choice__ico">🏢</span>
							<span class="mk-td-choice__label">Doanh nghiệp</span>
						</button>
					</div>
					<div id="mk-td-company-panel" class="mk-td-company-panel" hidden>
						<p class="mk-td-company-panel__title">Thông tin doanh nghiệp</p>
						<div class="mk-td-fields">
							<div class="mk-td-field mk-td-field--full">
								<label for="mk-td-company-name">Tên công ty / doanh nghiệp <span class="mk-td-req">*</span></label>
								<input type="text" id="mk-td-company-name" class="mk-td-input" placeholder="Công ty TNHH …" />
							</div>
							<div class="mk-td-field">
								<label for="mk-td-company-tax">Mã số thuế</label>
								<input type="text" id="mk-td-company-tax" class="mk-td-input" placeholder="0123456789" />
							</div>
							<div class="mk-td-field">
								<label for="mk-td-company-rep">Người đại diện</label>
								<input type="text" id="mk-td-company-rep" class="mk-td-input" placeholder="Họ tên người liên hệ" />
							</div>
						</div>
					</div>
				</div>
			</section>

			<section class="mk-td-card mk-td-card--basic" data-section="basic-info">
				<header class="mk-td-card__head">
					<span class="mk-td-card__num">01</span>
					<div>
						<h2 class="mk-td-card__title"><span class="mk-td-card__ico" aria-hidden="true">📋</span> Thông tin cơ bản</h2>
						<p class="mk-td-card__desc">Họ tên, liên hệ &amp; địa chỉ — dữ liệu lõi của lead</p>
					</div>
				</header>
				<div class="mk-td-card__body">
					<div class="mk-td-fields mk-td-fields--basic">
						<div class="mk-td-field">
							<label for="mk-td-name">Họ tên <span class="mk-td-req">*</span></label>
							<input type="text" id="mk-td-name" class="mk-td-input" placeholder="Nguyễn Văn A" autocomplete="name" />
						</div>
						<div class="mk-td-field">
							<label for="mk-td-phone">Số điện thoại <span class="mk-td-req">*</span></label>
							<input type="tel" id="mk-td-phone" class="mk-td-input" placeholder="09xxxxxxxx" autocomplete="tel" inputmode="numeric" maxlength="10" pattern="[0-9]{10}" />
						</div>
						<div class="mk-td-field">
							<label for="mk-td-cccd">CCCD</label>
							<input type="text" id="mk-td-cccd" class="mk-td-input" placeholder="Số CCCD / CMND" inputmode="numeric" maxlength="12" />
						</div>
						<div class="mk-td-field">
							<label for="mk-td-email">Email</label>
							<input type="email" id="mk-td-email" class="mk-td-input" placeholder="name@email.com" autocomplete="email" />
						</div>
						<div class="mk-td-field">
							<label for="mk-td-owner">Phụ trách</label>
							<select id="mk-td-owner" class="mk-td-select" data-default-user="{$MK_LEADS_CURRENT_USER_NAME|escape:'html'}">
								{foreach from=$MK_LEADS_ASSIGNABLE_USERS item=MK_USER}
									<option value="{$MK_USER.user_name|escape:'html'}" data-user-id="{$MK_USER.id|escape:'html'}"{if $MK_USER.user_name eq $MK_LEADS_CURRENT_USER_NAME} selected="selected"{/if}>{$MK_USER.label|escape:'html'}</option>
								{/foreach}
							</select>
						</div>
						<div class="mk-td-field mk-td-field--full">
							<label class="mk-td-label mk-td-label--block">Trạng thái khách</label>
							<div class="mk-td-choice-row mk-td-choice-row--3 mk-td-choice-row--status" role="group" aria-label="Trạng thái khách">
								<button type="button" class="mk-td-choice mk-td-choice--wide" data-group="customer-status" data-value="co_quan" data-segment="co_quan">Đã có quán</button>
								<button type="button" class="mk-td-choice mk-td-choice--wide" data-group="customer-status" data-value="chuan_bi_mo" data-segment="chuan_bi_mo">Chưa có quán</button>
								<button type="button" class="mk-td-choice mk-td-choice--wide" data-group="customer-status" data-value="gia_dinh" data-segment="gia_dinh">Gia đình</button>
							</div>
						</div>
						<div class="mk-td-field">
							<label for="mk-td-district">Khu vực</label>
							<select id="mk-td-district" class="mk-td-select" data-tag-group="region">
								<option value="">Chọn khu vực</option>
								<option value="Khu vực 1" data-tag="KV1">Khu vực 1</option>
								<option value="Khu vực 2" data-tag="KV2">Khu vực 2</option>
								<option value="Khu vực 3" data-tag="KV3">Khu vực 3</option>
							</select>
						</div>
						<div class="mk-td-field">
							<label for="mk-td-business-model">Mô hình kinh doanh</label>
							<select id="mk-td-business-model" class="mk-td-select">
								<option value="">Chọn mô hình</option>
								<option value="TS Topping">TS Topping</option>
								<option value="Xe đẩy">Xe đẩy</option>
								<option value="Cà phê máy lạnh">Cà phê máy lạnh</option>
								<option value="Cà phê sân vườn">Cà phê sân vườn</option>
								<option value="TS Pha máy">TS Pha máy</option>
								<option value="Cà phê không gian mở">Cà phê không gian mở</option>
							</select>
						</div>
						<div class="mk-td-field mk-td-field--full">
							<label for="mk-td-address">Địa chỉ</label>
							<input type="text" id="mk-td-address" class="mk-td-input" placeholder="Số nhà, đường, phường…" autocomplete="street-address" />
						</div>
						<div class="mk-td-field mk-td-field--full">
							<label for="mk-td-notes">Ghi chú nội bộ</label>
							<textarea id="mk-td-notes" class="mk-td-textarea" rows="3" placeholder="Ghi chú cho team sales / CS…"></textarea>
						</div>
					</div>
				</div>
			</section>

			<section class="mk-td-card mk-td-card--highlight" data-section="lead-source">
				<header class="mk-td-card__head">
					<span class="mk-td-card__num">02</span>
					<div>
						<h2 class="mk-td-card__title"><span class="mk-td-card__ico" aria-hidden="true">◎</span> Nguồn lead</h2>
						<p class="mk-td-card__desc"><strong>QUAN TRỌNG NHẤT</strong> — quyết định kênh</p>
					</div>
				</header>
				<div class="mk-td-card__body">
					<div class="mk-td-choice-row mk-td-choice-row--5" role="group" aria-label="Nguồn lead">
						<button type="button" class="mk-td-choice mk-td-choice--tile" data-tag="facebook" data-group="lead-source" data-value="facebook">
							<span class="mk-td-choice__ico">f</span>
							<span class="mk-td-choice__label">Facebook</span>
						</button>
						<button type="button" class="mk-td-choice mk-td-choice--tile" data-tag="tiktok" data-group="lead-source" data-value="tiktok">
							<span class="mk-td-choice__ico">♪</span>
							<span class="mk-td-choice__label">TikTok</span>
						</button>
						<button type="button" class="mk-td-choice mk-td-choice--tile" data-tag="website" data-group="lead-source" data-value="website">
							<span class="mk-td-choice__ico">🌐</span>
							<span class="mk-td-choice__label">Website</span>
						</button>
						<button type="button" class="mk-td-choice mk-td-choice--tile" data-tag="zalo" data-group="lead-source" data-value="zalo">
							<span class="mk-td-choice__ico">Z</span>
							<span class="mk-td-choice__label">Zalo</span>
						</button>
						<button type="button" class="mk-td-choice mk-td-choice--tile" data-tag="other_source" data-group="lead-source" data-value="other">
							<span class="mk-td-choice__ico">⋯</span>
							<span class="mk-td-choice__label">Khác</span>
						</button>
					</div>
				</div>
			</section>

			<div class="mk-td-create__row-2" id="mk-td-create-tag-groups">
				{if !empty($MK_LEAD_CREATE_TAG_GROUPS)}
					{assign var=MK_CREATE_NUM value=3}
					{foreach from=$MK_LEAD_CREATE_TAG_GROUPS item=MK_GROUP}
						{assign var=MK_GID value=$MK_GROUP.id}
						{assign var=MK_SEL_ID value='mk-td-g-'|cat:$MK_GID}
						{assign var=MK_FOOT_ID value='mk-td-g-'|cat:$MK_GID|cat:'-tag-foot'}
						{assign var=MK_SECTION value='group-'|cat:$MK_GID}
						{assign var=MK_TAG_GROUP value=$MK_GID}
						{assign var=MK_CARD_NUM value=$MK_CREATE_NUM|string_format:"%02d"}
						{if $MK_GID eq 'nguyen_lieu'}
							{assign var=MK_SEL_ID value='mk-td-intent'}
							{assign var=MK_FOOT_ID value='mk-td-intent-tag-foot'}
							{assign var=MK_SECTION value='customer-intent'}
							{assign var=MK_TAG_GROUP value='intent'}
							{assign var=MK_CARD_NUM value='03'}
						{elseif $MK_GID eq 'nhuong_quyen_group'}
							{assign var=MK_SEL_ID value='mk-td-franchise'}
							{assign var=MK_FOOT_ID value='mk-td-franchise-tag-foot'}
							{assign var=MK_SECTION value='franchise-tag'}
							{assign var=MK_TAG_GROUP value='franchise'}
							{assign var=MK_CARD_NUM value='04B'}
						{elseif $MK_GID eq 'lop_hoc'}
							{assign var=MK_SEL_ID value='mk-td-entry'}
							{assign var=MK_FOOT_ID value='mk-td-entry-tag-foot'}
							{assign var=MK_SECTION value='entry-program'}
							{assign var=MK_TAG_GROUP value='entry'}
							{assign var=MK_CARD_NUM value='04'}
						{/if}
						<section class="mk-td-card" data-section="{$MK_SECTION|escape:'html'}" data-group-id="{$MK_GID|escape:'html'}">
							<header class="mk-td-card__head">
								<span class="mk-td-card__num">{$MK_CARD_NUM|escape:'html'}</span>
								<div>
									<h2 class="mk-td-card__title">{$MK_GROUP.name|escape:'html'}</h2>
								</div>
							</header>
							<div class="mk-td-card__body">
								<label class="mk-td-label" for="{$MK_SEL_ID|escape:'html'}">Chọn {$MK_GROUP.name|escape:'html'|lower}</label>
								<select id="{$MK_SEL_ID|escape:'html'}" class="mk-td-select js-mk-create-group-select" data-tag-group="{$MK_TAG_GROUP|escape:'html'}" data-group-id="{$MK_GID|escape:'html'}">
									<option value="">Chọn tag</option>
									{foreach from=$MK_GROUP.children item=MK_CHILD}
										<option value="{$MK_CHILD.id|escape:'html'}" data-tag="{$MK_CHILD.id|escape:'html'}">{$MK_CHILD.name|escape:'html'}</option>
									{/foreach}
								</select>
								{if $MK_GID eq 'lop_hoc'}
									<div id="mk-td-entry-pcth-wrap" class="mk-td-nested-box" hidden>
										<label class="mk-td-label mk-td-label--nested" for="mk-td-entry-branch">Nhánh lớp PCTH</label>
										<select id="mk-td-entry-branch" class="mk-td-select" data-tag-group="entry-branch">
											<option value="">Chọn nhánh lớp</option>
											<option value="van_hanh" data-tag="van_hanh">Vận hành</option>
											<option value="mkt" data-tag="mkt">Marketing</option>
											<option value="lop_khac" data-tag="lop_khac">Lớp học khác</option>
											<option value="nhuong_quyen" data-tag="nhuong_quyen">Nhượng quyền</option>
										</select>
									</div>
								{/if}
								<div id="{$MK_FOOT_ID|escape:'html'}" class="mk-td-entry-tags" hidden></div>
							</div>
						</section>
						{assign var=MK_CREATE_NUM value=$MK_CREATE_NUM+1}
					{/foreach}
				{else}
				<section class="mk-td-card" data-section="customer-intent">
					<header class="mk-td-card__head">
						<span class="mk-td-card__num">03</span>
						<div>
							<h2 class="mk-td-card__title"><span class="mk-td-card__ico" aria-hidden="true">〰</span> Nguyên liệu</h2>
						</div>
					</header>
					<div class="mk-td-card__body">
						<label class="mk-td-label" for="mk-td-intent">Chọn nguyên liệu</label>
						<select id="mk-td-intent" class="mk-td-select" data-tag-group="intent" data-group-id="nguyen_lieu">
							<option value="">Chọn tag nguyên liệu</option>
							<option value="dang_tu_van" data-tag="dang_tu_van">Đang tư vấn</option>
							<option value="dung_cham_soc" data-tag="dung_cham_soc">Dừng chăm sóc</option>
							<option value="kh_can_nhac" data-tag="kh_can_nhac">KH Cân Nhắc</option>
							<option value="mua_it_lai" data-tag="mua_it_lai">Mua ít lại</option>
							<option value="nguyen_lieu_chuoi" data-tag="nguyen_lieu_chuoi">NL chuỗi</option>
						</select>
						<div id="mk-td-intent-tag-foot" class="mk-td-entry-tags" hidden></div>
					</div>
				</section>

				<section class="mk-td-card" data-section="franchise-tag">
					<header class="mk-td-card__head">
						<span class="mk-td-card__num">04B</span>
						<div>
							<h2 class="mk-td-card__title">Nhượng quyền</h2>
						</div>
					</header>
					<div class="mk-td-card__body">
						<label class="mk-td-label" for="mk-td-franchise">Chọn tag nhượng quyền</label>
						<select id="mk-td-franchise" class="mk-td-select" data-tag-group="franchise" data-group-id="nhuong_quyen_group">
							<option value="">Chọn tag</option>
							<option value="dang_tu_van" data-tag="dang_tu_van">Đang tư vấn</option>
							<option value="khong_nghe_may" data-tag="khong_nghe_may">Không nghe máy</option>
							<option value="thue_bao" data-tag="thue_bao">Thuê Bao</option>
							<option value="tiem_nang" data-tag="tiem_nang">Tiềm năng</option>
							<option value="tham_khao" data-tag="tham_khao">Tham Khảo</option>
							<option value="dung_cham_soc" data-tag="dung_cham_soc">Dừng Chăm Sóc</option>
							<option value="khong_du_tai_chinh" data-tag="khong_du_tai_chinh">Không đủ tài chính</option>
							<option value="da_ky_quy" data-tag="da_ky_quy">Đã Ký Quỹ</option>
							<option value="mien_bac" data-tag="mien_bac">Miền Bắc</option>
						</select>
						<div id="mk-td-franchise-tag-foot" class="mk-td-entry-tags" hidden></div>
					</div>
				</section>

				<section class="mk-td-card" data-section="entry-program">
					<header class="mk-td-card__head">
						<span class="mk-td-card__num">04</span>
						<div>
							<h2 class="mk-td-card__title"><span class="mk-td-card__ico" aria-hidden="true">⎇</span> Lớp học</h2>
						</div>
					</header>
					<div class="mk-td-card__body">
						<label class="mk-td-label" for="mk-td-entry">Chọn lớp học</label>
						<select id="mk-td-entry" class="mk-td-select" data-tag-group="entry" data-group-id="lop_hoc">
							<option value="">Chọn lớp học</option>
							<option value="thu_3" data-tag="thu_3">THỨ 3</option>
							<option value="lop_online" data-tag="lop_online">lớp online</option>
							<option value="moi_lai" data-tag="moi_lai">Mời lại</option>
							<option value="da_tg_free" data-tag="da_tg_free">Đã TG FREE</option>
							<option value="doi_lich" data-tag="doi_lich">Dời lịch</option>
							<option value="l1" data-tag="L1">L1</option>
							<option value="l2" data-tag="L2">L2</option>
							<option value="khong_hoc" data-tag="khong_hoc">Không học</option>
							<option value="thue_bao" data-tag="thue_bao">thuê bao</option>
							<option value="trung_so" data-tag="trung_so">trùng số</option>
							<option value="khong_nghe_may" data-tag="khong_nghe_may">không nghe máy</option>
							<option value="ngung_cham_soc" data-tag="ngung_cham_soc">Ngừng chăm sóc</option>
							<option value="chua_mqbb_chua_pcth" data-tag="chua_MQBB_chua_PCTH">Chưa MQBB + Chưa PCTH</option>
							<option value="chua_mqbb_da_pcth" data-tag="chua_MQBB_da_PCTH">Chưa MQBB + Đã PCTH</option>
							<option value="da_mqbb_chua_pcth" data-tag="da_MQBB_chua_PCTH">Đã MQBB + Chưa PCTH</option>
							<option value="da_mqbb_da_pcth" data-tag="da_MQBB_da_PCTH">Đã MQBB + Đã PCTH</option>
							<option value="da_mqbb" data-tag="da_MQBB">Đã MQBB</option>
							<option value="chua_mqbb" data-tag="chua_MQBB">Chưa MQBB</option>
							<option value="da_pcth" data-tag="da_PCTH">Đã PCTH</option>
							<option value="chua_pcth" data-tag="chua_PCTH">Chưa PCTH</option>
							<option value="da_990k" data-tag="da_990k">Đã 990k</option>
							<option value="chua_990k" data-tag="chua_990k">Chưa 990k</option>
							<option value="hoan_tien_lop_hoc" data-tag="hoan_tien_lop_hoc">Hoàn tiền lớp học</option>
						</select>
						<div id="mk-td-entry-pcth-wrap" class="mk-td-nested-box" hidden>
							<label class="mk-td-label mk-td-label--nested" for="mk-td-entry-branch">Nhánh lớp PCTH</label>
							<select id="mk-td-entry-branch" class="mk-td-select" data-tag-group="entry-branch">
								<option value="">Chọn nhánh lớp</option>
								<option value="van_hanh" data-tag="van_hanh">Vận hành</option>
								<option value="mkt" data-tag="mkt">Marketing</option>
								<option value="lop_khac" data-tag="lop_khac">Lớp học khác</option>
								<option value="nhuong_quyen" data-tag="nhuong_quyen">Nhượng quyền</option>
							</select>
						</div>
						<div id="mk-td-entry-tag-foot" class="mk-td-entry-tags" hidden></div>
					</div>
				</section>
				{/if}
			</div>
			<script type="text/javascript">
				window.MK_LEAD_CREATE_TAG_GROUPS = {$MK_LEAD_CREATE_TAG_GROUPS_JSON|default:'[]' nofilter};
			</script>

			<section class="mk-td-card" data-section="purchase-status">
				<header class="mk-td-card__head">
					<span class="mk-td-card__num">05</span>
					<div>
						<h2 class="mk-td-card__title"><span class="mk-td-card__ico" aria-hidden="true">🛍</span> Tình trạng mua</h2>
						<p class="mk-td-card__desc">Xương sống của flow</p>
					</div>
				</header>
				<div class="mk-td-card__body">
					<div class="mk-td-choice-row mk-td-choice-row--4" role="group" aria-label="Tình trạng mua">
						<button type="button" class="mk-td-choice mk-td-choice--wide" data-tag="mua_lan_dau" data-group="purchase-status" data-value="first">Mua lần đầu</button>
						<button type="button" class="mk-td-choice mk-td-choice--wide" data-tag="mua_lai" data-group="purchase-status" data-value="repeat">Mua lại</button>
						<button type="button" class="mk-td-choice mk-td-choice--wide" data-tag="khong_mua" data-group="purchase-status" data-value="not" data-needs-reason="1">Không mua</button>
						<button type="button" class="mk-td-choice mk-td-choice--wide" data-tag="ngung_mua" data-group="purchase-status" data-value="stopped" data-needs-reason="1">Ngừng mua</button>
					</div>
					<div id="mk-td-purchase-reason" class="mk-td-reason-panel" hidden>
						<label class="mk-td-label" for="mk-td-purchase-reason-text">Lý do không mua <span class="mk-td-req">*</span></label>
						<textarea id="mk-td-purchase-reason-text" class="mk-td-textarea mk-td-reason-textarea" rows="4" placeholder="Mô tả lý do để team chăm sóc có script phù hợp..."></textarea>
					</div>
					<div id="mk-td-purchase-tag-foot" class="mk-td-card-tag-foot" hidden></div>
				</div>
			</section>

		</div>

		<aside class="mk-td-create__aside" aria-label="Tags preview">
			<div class="mk-td-tags-panel">
				<header class="mk-td-tags-panel__head">
					<span class="mk-td-tags-panel__ico" aria-hidden="true">🏷</span>
					<h3 class="mk-td-tags-panel__title">Tags sẽ được gắn</h3>
				</header>
				<p class="mk-td-tags-panel__desc">Tự động sinh từ các lựa chọn ở form. Đây là đầu vào duy nhất của workflow CRM.</p>
				<div class="mk-td-tags-panel__list" id="mk-td-tags-list">
					<span class="mk-td-tag-pill" data-tag="individual">#cá nhân</span>
				</div>
			</div>
		</aside>
	</div>
</div>
{/strip}
