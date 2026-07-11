{* KiotViet-style advanced filter sidebar *}
{strip}
{assign var=MK_STATUS_FIELD value=$MK_SO_POS_STATUS_FIELD|default:'sostatus'}
<aside class="mk-so-pos-filter-panel" id="mk-so-pos-filter-panel" aria-label="Bộ lọc nâng cao" aria-hidden="true">
	<div class="mk-so-pos-filter-panel__head">
		<h2 class="mk-so-pos-filter-panel__title">Bộ lọc</h2>
		<button type="button" class="mk-so-pos-filter-panel__close" id="mk-so-pos-filter-close" aria-label="Đóng bộ lọc">
			<i class="fa fa-times" aria-hidden="true"></i>
		</button>
	</div>

	<div class="mk-so-pos-filter-panel__inner">
		<section class="mk-so-pos-filter-section">
			<h3 class="mk-so-pos-filter-section__title">Thời gian</h3>
			<div class="mk-so-pos-filter-options" role="radiogroup" aria-label="Thời gian">
				<label class="mk-so-pos-filter-option">
					<input type="radio" name="mk_so_filter_time" value="all" checked />
					<span class="mk-so-pos-filter-option__text">Tất cả thời gian</span>
				</label>
				<label class="mk-so-pos-filter-option">
					<input type="radio" name="mk_so_filter_time" value="this_month" />
					<span class="mk-so-pos-filter-option__text">Tháng này</span>
				</label>
				<label class="mk-so-pos-filter-option">
					<input type="radio" name="mk_so_filter_time" value="custom" />
					<span class="mk-so-pos-filter-option__text">Tùy chỉnh</span>
				</label>
			</div>
			<div class="mk-so-pos-filter-custom-range" id="mk-so-filter-time-custom" hidden>
				<div class="mk-so-pos-filter-date-wrap">
					<input type="text" class="mk-so-pos-filter-date inputElement dateField" id="mk-so-filter-time-from" placeholder="Từ ngày" data-fieldtype="date" />
					<i class="fa fa-calendar-o mk-so-pos-filter-date__ic" aria-hidden="true"></i>
				</div>
				<div class="mk-so-pos-filter-date-wrap">
					<input type="text" class="mk-so-pos-filter-date inputElement dateField" id="mk-so-filter-time-to" placeholder="Đến ngày" data-fieldtype="date" />
					<i class="fa fa-calendar-o mk-so-pos-filter-date__ic" aria-hidden="true"></i>
				</div>
			</div>
		</section>

		<section class="mk-so-pos-filter-section">
			<h3 class="mk-so-pos-filter-section__title">Trạng thái</h3>
			<div class="mk-so-pos-filter-chips" id="mk-so-filter-status-chips" data-field="{$MK_STATUS_FIELD}">
				{if isset($MK_SO_POS_FILTER_STATUS_OPTIONS) && $MK_SO_POS_FILTER_STATUS_OPTIONS|@count gt 0}
					{foreach from=$MK_SO_POS_FILTER_STATUS_OPTIONS key=STATUS_KEY item=STATUS_LABEL}
						<button type="button" class="mk-so-pos-filter-chip" data-value="{$STATUS_KEY|escape}">
							<span class="mk-so-pos-filter-chip__text">{$STATUS_LABEL|escape}</span>
							<i class="fa fa-times mk-so-pos-filter-chip__remove" aria-hidden="true"></i>
						</button>
					{/foreach}
				{else}
					<button type="button" class="mk-so-pos-filter-chip" data-value="Created"><span class="mk-so-pos-filter-chip__text">Phiếu tạm</span><i class="fa fa-times mk-so-pos-filter-chip__remove" aria-hidden="true"></i></button>
					<button type="button" class="mk-so-pos-filter-chip" data-value="waiting_print"><span class="mk-so-pos-filter-chip__text">Chờ in phiếu</span><i class="fa fa-times mk-so-pos-filter-chip__remove" aria-hidden="true"></i></button>
					<button type="button" class="mk-so-pos-filter-chip" data-value="picking"><span class="mk-so-pos-filter-chip__text">Đang soạn</span><i class="fa fa-times mk-so-pos-filter-chip__remove" aria-hidden="true"></i></button>
					<button type="button" class="mk-so-pos-filter-chip" data-value="packed"><span class="mk-so-pos-filter-chip__text">Đã soạn</span><i class="fa fa-times mk-so-pos-filter-chip__remove" aria-hidden="true"></i></button>
					<button type="button" class="mk-so-pos-filter-chip" data-value="shipped"><span class="mk-so-pos-filter-chip__text">Đã giao</span><i class="fa fa-times mk-so-pos-filter-chip__remove" aria-hidden="true"></i></button>
					<button type="button" class="mk-so-pos-filter-chip" data-value="Approved"><span class="mk-so-pos-filter-chip__text">Đã xác nhận</span><i class="fa fa-times mk-so-pos-filter-chip__remove" aria-hidden="true"></i></button>
					<button type="button" class="mk-so-pos-filter-chip" data-value="Delivered"><span class="mk-so-pos-filter-chip__text">Hoàn thành</span><i class="fa fa-times mk-so-pos-filter-chip__remove" aria-hidden="true"></i></button>
				{/if}
			</div>
			<div class="mk-so-pos-filter-status-pool" id="mk-so-filter-status-pool">
				{if isset($MK_SO_POS_FILTER_STATUS_OPTIONS) && $MK_SO_POS_FILTER_STATUS_OPTIONS|@count gt 0}
					{foreach from=$MK_SO_POS_FILTER_STATUS_OPTIONS key=STATUS_KEY item=STATUS_LABEL}
						<button type="button" class="mk-so-pos-filter-status-option" data-value="{$STATUS_KEY|escape}">{$STATUS_LABEL|escape}</button>
					{/foreach}
				{/if}
			</div>
		</section>

		{if isset($MK_SO_POS_FILTER_META.carrierField) && $MK_SO_POS_FILTER_META.carrierField neq ''}
		<section class="mk-so-pos-filter-section">
			<h3 class="mk-so-pos-filter-section__title">Đối tác giao hàng</h3>
			<div class="mk-so-pos-filter-select-wrap">
				<select class="mk-so-pos-filter-select" id="mk-so-filter-carrier" data-field="{$MK_SO_POS_FILTER_META.carrierField}">
					<option value="">Chọn đối tác giao hàng</option>
					{foreach from=$MK_SO_POS_FILTER_CARRIER_OPTIONS key=CARRIER_KEY item=CARRIER_LABEL}
						<option value="{$CARRIER_KEY|escape}">{$CARRIER_LABEL|escape}</option>
					{/foreach}
				</select>
				<i class="fa fa-chevron-down mk-so-pos-filter-select__ic" aria-hidden="true"></i>
			</div>
		</section>
		{/if}

		{if isset($MK_SO_POS_FILTER_META.dueDateField) && $MK_SO_POS_FILTER_META.dueDateField neq ''}
		<section class="mk-so-pos-filter-section">
			<h3 class="mk-so-pos-filter-section__title">Thời gian giao hàng</h3>
			<div class="mk-so-pos-filter-options" role="radiogroup" aria-label="Thời gian giao hàng">
				<label class="mk-so-pos-filter-option">
					<input type="radio" name="mk_so_filter_due_time" value="all" checked />
					<span class="mk-so-pos-filter-option__text">Toàn thời gian</span>
				</label>
				<label class="mk-so-pos-filter-option">
					<input type="radio" name="mk_so_filter_due_time" value="custom" />
					<span class="mk-so-pos-filter-option__text">Tùy chỉnh</span>
				</label>
			</div>
			<div class="mk-so-pos-filter-custom-range" id="mk-so-filter-due-custom" hidden>
				<div class="mk-so-pos-filter-date-wrap">
					<input type="text" class="mk-so-pos-filter-date inputElement dateField" id="mk-so-filter-due-from" placeholder="Từ ngày" data-fieldtype="date" />
					<i class="fa fa-calendar-o mk-so-pos-filter-date__ic" aria-hidden="true"></i>
				</div>
				<div class="mk-so-pos-filter-date-wrap">
					<input type="text" class="mk-so-pos-filter-date inputElement dateField" id="mk-so-filter-due-to" placeholder="Đến ngày" data-fieldtype="date" />
					<i class="fa fa-calendar-o mk-so-pos-filter-date__ic" aria-hidden="true"></i>
				</div>
			</div>
		</section>
		{/if}

		<section class="mk-so-pos-filter-section">
			<h3 class="mk-so-pos-filter-section__title">Khu vực giao hàng</h3>
			<div class="mk-so-pos-filter-select-wrap">
				<select class="mk-so-pos-filter-select" id="mk-so-filter-region" {if isset($MK_SO_POS_FILTER_META.shipCityField) && $MK_SO_POS_FILTER_META.shipCityField neq ''}data-field="{$MK_SO_POS_FILTER_META.shipCityField}"{else}data-field=""{/if}>
					<option value="">Chọn Tỉnh/TP - Quận/Huyện</option>
					<option value="Hà Nội">Hà Nội</option>
					<option value="Hồ Chí Minh">Hồ Chí Minh</option>
					<option value="Đà Nẵng">Đà Nẵng</option>
					<option value="Hải Phòng">Hải Phòng</option>
					<option value="Cần Thơ">Cần Thơ</option>
				</select>
				<i class="fa fa-chevron-down mk-so-pos-filter-select__ic" aria-hidden="true"></i>
			</div>
		</section>

		{if isset($MK_SO_POS_FILTER_META.paymentField) && $MK_SO_POS_FILTER_META.paymentField neq ''}
		<section class="mk-so-pos-filter-section mk-so-pos-filter-section--payment">
			<h3 class="mk-so-pos-filter-section__title">Hình thức thanh toán</h3>
			<div class="mk-so-pos-filter-select-wrap">
				<select class="mk-so-pos-filter-select" id="mk-so-filter-payment" data-field="{$MK_SO_POS_FILTER_META.paymentField}">
					<option value="">Chọn hình thức thanh toán</option>
					{foreach from=$MK_SO_POS_FILTER_PAYMENT_OPTIONS key=PAY_KEY item=PAY_LABEL}
						<option value="{$PAY_KEY|escape}" {if $MK_SO_POS_FILTER_STATE.payment eq $PAY_KEY}selected="selected"{/if}>{$PAY_LABEL|escape}</option>
					{/foreach}
				</select>
				<i class="fa fa-chevron-down mk-so-pos-filter-select__ic" aria-hidden="true"></i>
			</div>
		</section>
		{/if}
	</div>

	<div class="mk-so-pos-filter-panel__footer">
		<button type="button" class="mk-so-pos-filter-btn mk-so-pos-filter-btn--ghost" id="mk-so-pos-filter-clear">Xóa lọc</button>
		<button type="button" class="mk-so-pos-filter-btn mk-so-pos-filter-btn--primary" id="mk-so-pos-filter-apply">Áp dụng</button>
	</div>
</aside>
{/strip}
