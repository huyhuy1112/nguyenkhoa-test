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
			<a href="{$MK_LIST_URL}">Leads</a>
			<span class="mk-td-create__crumb-sep">/</span>
			{if $MK_IS_EDIT}
				<a href="{$MK_DETAIL_URL}" id="mk-td-crumb-record">{$MK_LEAD_RECORD_ID|escape}</a>
				<span class="mk-td-create__crumb-sep">/</span>
				<span>Edit</span>
			{else}
				<span>New</span>
			{/if}
		</nav>
		<div class="mk-td-create__head-row">
			<div>
				<h1 class="mk-td-create__title">{if $MK_IS_EDIT}Edit Lead{else}Create Lead{/if}</h1>
				<p class="mk-td-create__subtitle">Mỗi lựa chọn sẽ tự động gắn tag để hệ thống chạy đúng workflow, journey &amp; script bán hàng.</p>
			</div>
			<div class="mk-td-create__head-actions">
				<a class="mk-td-btn mk-td-btn--ghost" href="{$MK_CANCEL_URL}">Cancel</a>
				<button type="button" class="mk-td-btn mk-td-btn--dark" id="mk-td-save-top">
					<span class="mk-td-btn__ic" aria-hidden="true">💾</span>
					{if $MK_IS_EDIT}Save Changes{else}Save Lead{/if}
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
						<h2 class="mk-td-card__title"><span class="mk-td-card__ico" aria-hidden="true">👤</span> Customer Type</h2>
						<p class="mk-td-card__desc">Cá nhân hay Doanh nghiệp — gắn tag <code>individual</code> / <code>company</code></p>
					</div>
				</header>
				<div class="mk-td-card__body">
					<div class="mk-td-choice-row mk-td-choice-row--2" role="group" aria-label="Customer type">
						<button type="button" class="mk-td-choice is-on" data-tag="individual" data-group="customer-type" data-value="individual">
							<span class="mk-td-choice__ico">👤</span>
							<span class="mk-td-choice__label">Individual</span>
						</button>
						<button type="button" class="mk-td-choice" data-tag="company" data-group="customer-type" data-value="company">
							<span class="mk-td-choice__ico">🏢</span>
							<span class="mk-td-choice__label">Company</span>
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
							<input type="tel" id="mk-td-phone" class="mk-td-input" placeholder="09xx xxx xxx" autocomplete="tel" />
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
							<select id="mk-td-owner" class="mk-td-select">
								<option value="Linh">Linh</option>
								<option value="Hà">Hà</option>
								<option value="Minh">Minh</option>
								<option value="{$USER_MODEL->getName()|escape:'html'}">{$USER_MODEL->getName()}</option>
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
							<select id="mk-td-district" class="mk-td-select">
								<option value="">Chọn khu vực (TP.HCM)</option>
								<option value="Quận 1">Quận 1</option>
								<option value="Quận 3">Quận 3</option>
								<option value="Quận 4">Quận 4</option>
								<option value="Quận 5">Quận 5</option>
								<option value="Quận 6">Quận 6</option>
								<option value="Quận 7">Quận 7</option>
								<option value="Quận 8">Quận 8</option>
								<option value="Quận 10">Quận 10</option>
								<option value="Quận 11">Quận 11</option>
								<option value="Quận 12">Quận 12</option>
								<option value="Quận Bình Thạnh">Quận Bình Thạnh</option>
								<option value="Quận Bình Tân">Quận Bình Tân</option>
								<option value="Quận Gò Vấp">Quận Gò Vấp</option>
								<option value="Quận Phú Nhuận">Quận Phú Nhuận</option>
								<option value="Quận Tân Bình">Quận Tân Bình</option>
								<option value="Quận Tân Phú">Quận Tân Phú</option>
								<option value="Quận Thủ Đức">Quận Thủ Đức</option>
								<option value="Huyện Bình Chánh">Huyện Bình Chánh</option>
								<option value="Huyện Cần Giờ">Huyện Cần Giờ</option>
								<option value="Huyện Củ Chi">Huyện Củ Chi</option>
								<option value="Huyện Hóc Môn">Huyện Hóc Môn</option>
								<option value="Huyện Nhà Bè">Huyện Nhà Bè</option>
							</select>
						</div>
						<div class="mk-td-field">
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
						<h2 class="mk-td-card__title"><span class="mk-td-card__ico" aria-hidden="true">◎</span> Lead Source</h2>
						<p class="mk-td-card__desc"><strong>QUAN TRỌNG NHẤT</strong> — quyết định kênh</p>
					</div>
				</header>
				<div class="mk-td-card__body">
					<div class="mk-td-choice-row mk-td-choice-row--5" role="group" aria-label="Lead source">
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

			<div class="mk-td-create__row-2">
				<section class="mk-td-card" data-section="customer-intent">
					<header class="mk-td-card__head">
						<span class="mk-td-card__num">03</span>
						<div>
							<h2 class="mk-td-card__title"><span class="mk-td-card__ico" aria-hidden="true">〰</span> Customer Intent</h2>
							<p class="mk-td-card__desc">Nhóm nhu cầu khách <span class="mk-td-optional">(không bắt buộc)</span></p>
						</div>
					</header>
					<div class="mk-td-card__body">
						<label class="mk-td-label" for="mk-td-intent">Chọn nhóm nhu cầu</label>
						<select id="mk-td-intent" class="mk-td-select" data-tag-group="intent">
							<option value="">Chọn nhóm nhu cầu</option>
							<option value="chua_hoc" data-tag="chua_hoc">Nguyên liệu chưa học</option>
							<option value="da_hoc" data-tag="da_hoc">Nguyên liệu đã học</option>
							<option value="nguyen_lieu_chuoi" data-tag="nguyen_lieu_chuoi">Nguyên liệu chuỗi</option>
						</select>
					</div>
				</section>

				<section class="mk-td-card" data-section="entry-program">
					<header class="mk-td-card__head">
						<span class="mk-td-card__num">04</span>
						<div>
							<h2 class="mk-td-card__title"><span class="mk-td-card__ico" aria-hidden="true">⎇</span> Entry Program</h2>
							<p class="mk-td-card__desc">Khách vào từ đâu trong hành trình <span class="mk-td-optional">(không bắt buộc)</span></p>
						</div>
					</header>
					<div class="mk-td-card__body">
						<label class="mk-td-label" for="mk-td-entry">Chọn entry program</label>
						<select id="mk-td-entry" class="mk-td-select" data-tag-group="entry">
							<option value="">Chọn entry program</option>
							<option value="mien_phi_online" data-tag="mien_phi_online">Pha chế miễn phí Online</option>
							<option value="mien_phi_offline" data-tag="mien_phi_offline">Pha chế miễn phí Offline</option>
							<option value="pcth" data-tag="pcth">Pha chế tổng hợp (PCTH)</option>
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
			</div>

			<section class="mk-td-card" data-section="purchase-status">
				<header class="mk-td-card__head">
					<span class="mk-td-card__num">05</span>
					<div>
						<h2 class="mk-td-card__title"><span class="mk-td-card__ico" aria-hidden="true">🛍</span> Purchase Status</h2>
						<p class="mk-td-card__desc">Xương sống của flow</p>
					</div>
				</header>
				<div class="mk-td-card__body">
					<div class="mk-td-choice-row mk-td-choice-row--4" role="group" aria-label="Purchase status">
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

			<section class="mk-td-card" data-section="customer-tier">
				<header class="mk-td-card__head">
					<span class="mk-td-card__num">06</span>
					<div>
						<h2 class="mk-td-card__title"><span class="mk-td-card__ico" aria-hidden="true">👑</span> Customer Tier</h2>
						<p class="mk-td-card__desc">Vàng / Bạc / Đồng</p>
					</div>
				</header>
				<div class="mk-td-card__body">
					<div class="mk-td-choice-row mk-td-choice-row--3" role="group" aria-label="Customer tier">
						<button type="button" class="mk-td-choice mk-td-choice--tier" data-tag="vang" data-group="customer-tier" data-value="gold">
							<span class="mk-td-tier-ic mk-td-tier-ic--gold">👑</span>
							<span class="mk-td-choice__label">Vàng</span>
							<span class="mk-td-choice__hint">tag: vang</span>
						</button>
						<button type="button" class="mk-td-choice mk-td-choice--tier" data-tag="bac" data-group="customer-tier" data-value="silver">
							<span class="mk-td-tier-ic mk-td-tier-ic--silver">👑</span>
							<span class="mk-td-choice__label">Bạc</span>
							<span class="mk-td-choice__hint">tag: bac</span>
						</button>
						<button type="button" class="mk-td-choice mk-td-choice--tier" data-tag="dong" data-group="customer-tier" data-value="bronze">
							<span class="mk-td-tier-ic mk-td-tier-ic--bronze">👑</span>
							<span class="mk-td-choice__label">Đồng</span>
							<span class="mk-td-choice__hint">tag: dong</span>
						</button>
					</div>
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
					<span class="mk-td-tag-pill" data-tag="individual">#individual</span>
				</div>
				<p class="mk-td-tags-panel__trigger" id="mk-td-tags-trigger">WORKFLOW TRIGGER: 1 tag(s) → khớp script &amp; automation tương ứng.</p>
				<button type="button" class="mk-td-btn mk-td-btn--dark mk-td-btn--block" id="mk-td-save-aside">
					<span class="mk-td-btn__ic" aria-hidden="true">💾</span>
					{if $MK_IS_EDIT}Save Changes{else}Save Lead &amp; Apply Tags{/if}
				</button>
			</div>
		</aside>
	</div>
</div>
{/strip}
