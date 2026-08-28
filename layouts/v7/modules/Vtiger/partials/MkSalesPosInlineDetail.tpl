{* Lightweight POS inline detail dropdown (Accounts / Leads / Opp / Contacts) *}
{strip}
<div class="mk-so-inline-detail is-edit-mode" data-always-edit="1" data-record-id="{$RECORD->getId()}" data-module="{$MODULE|escape}" data-detail-url="{$INLINE_DETAIL_URL|escape}" data-edit-url="{$INLINE_EDIT_URL|escape}"{if $MODULE eq 'ServiceContracts'} data-affiliate-code="{$INLINE_SC_AFFILIATE_CODE|default:''|escape}" data-affiliate-visible="{if !empty($INLINE_SC_AFF_VISIBLE)}1{else}0{/if}"{/if}>
	<div class="mk-so-inline-detail__tabs" role="tablist">
		<button type="button" class="mk-so-inline-detail__tab is-active" role="tab" aria-selected="true">Thông tin</button>
	</div>

	<div class="mk-so-inline-detail__hero">
		<div class="mk-so-inline-detail__hero-main">
			<div class="mk-so-inline-detail__customer">
				<span class="mk-so-inline-detail__customer-name">{$INLINE_TITLE|escape}</span>
			</div>
			{if $INLINE_SUBTITLE neq ''}
				<div class="mk-so-inline-detail__order-no">{$INLINE_SUBTITLE|escape}</div>
			{/if}
			{if $MODULE eq 'ServiceContracts' && $INLINE_NOTES neq ''}
				<div class="mk-so-inline-detail__hero-note">{$INLINE_NOTES|escape}</div>
			{/if}
		</div>
	</div>

	{if isset($INLINE_INFO_FIELDS) && $INLINE_INFO_FIELDS|@count gt 0}
		<div class="mk-so-inline-detail__fields">
			<h3 class="mk-so-inline-detail__sec-title">Thông tin</h3>
			{foreach from=$INLINE_INFO_FIELDS item=INFO_FIELD}
				<div class="mk-so-inline-detail__field" data-field-name="{$INFO_FIELD.name|escape}" data-field-type="{$INFO_FIELD.data_type|default:'string'|escape}" data-editable="{if !empty($INFO_FIELD.editable)}1{else}0{/if}">
					<label class="mk-so-inline-detail__field-label">{$INFO_FIELD.label|escape}</label>
					<div class="mk-so-inline-detail__field-view">{$INFO_FIELD.value|escape|nl2br}</div>
					{if !empty($INFO_FIELD.editable)}
						<div class="mk-so-inline-detail__field-edit">
							{if $INFO_FIELD.data_type eq 'picklist'}
								<select class="mk-so-inline-detail__input mk-so-inline-detail__select-native" name="{$INFO_FIELD.name|escape}">
									{foreach from=$INFO_FIELD.picklist_values key=PK item=PL}
										<option value="{$PK|escape}" {if $INFO_FIELD.raw_value eq $PK}selected="selected"{/if}>{$PL|escape}</option>
									{/foreach}
								</select>
							{elseif $INFO_FIELD.data_type eq 'owner'}
								<select class="mk-so-inline-detail__input inputElement" name="{$INFO_FIELD.name|escape}">
									{foreach from=$INLINE_ASSIGNED_USERS key=UID item=UNAME}
										<option value="{$UID|escape}" {if $INFO_FIELD.raw_value eq $UID}selected="selected"{/if}>{$UNAME|escape}</option>
									{/foreach}
								</select>
							{elseif $INFO_FIELD.data_type eq 'date' || $INFO_FIELD.data_type eq 'datetime'}
								<input type="text" class="mk-so-inline-detail__input inputElement dateField" name="{$INFO_FIELD.name|escape}" value="{$INFO_FIELD.raw_value|escape}" data-date-format="{$USER_MODEL->get('date_format')|escape}" />
							{elseif $INFO_FIELD.data_type eq 'boolean'}
								{assign var=MK_BOOL_ON value=($INFO_FIELD.raw_value eq 1 || $INFO_FIELD.raw_value eq '1')}
								<label class="mk-so-inline-detail__bool{if $MK_BOOL_ON} is-on{/if}">
									<input type="checkbox" class="mk-so-inline-detail__bool-input" name="{$INFO_FIELD.name|escape}" value="1"{if $MK_BOOL_ON} checked="checked"{/if} />
									<span class="mk-so-inline-detail__bool-track" aria-hidden="true"><span class="mk-so-inline-detail__bool-knob"></span></span>
									<span class="mk-so-inline-detail__bool-text">{if $INFO_FIELD.name eq 'needs_qc'}Cần QC trước khi nhập tồn{else}Bật{/if}</span>
								</label>
							{elseif $INFO_FIELD.data_type eq 'currency'}
								<input type="text" class="mk-so-inline-detail__input inputElement mk-so-inline-detail__input--money" name="{$INFO_FIELD.name|escape}" value="{$INFO_FIELD.raw_value|escape}" inputmode="numeric" />
							{elseif $INFO_FIELD.data_type eq 'text'}
								<textarea class="mk-so-inline-detail__input mk-so-inline-detail__textarea inputElement" name="{$INFO_FIELD.name|escape}" rows="{if $INFO_FIELD.name eq 'business_note'}2{else}2{/if}">{$INFO_FIELD.raw_value|escape}</textarea>
							{else}
								<input type="text" class="mk-so-inline-detail__input inputElement{if $INFO_FIELD.name eq 'referral_code'} mk-so-inline-detail__input--upper{/if}" name="{$INFO_FIELD.name|escape}" value="{$INFO_FIELD.raw_value|escape}"{if $INFO_FIELD.name eq 'mk_address' || $INFO_FIELD.name eq 'address'} placeholder="Nhập địa chỉ"{elseif $INFO_FIELD.name eq 'phone'} placeholder="Nhập SĐT"{elseif !empty($INFO_FIELD.placeholder)} placeholder="{$INFO_FIELD.placeholder|escape}"{/if}{if $INFO_FIELD.name eq 'referral_code'} autocomplete="off" style="text-transform:uppercase"{/if} />
							{/if}
						</div>
					{elseif $MODULE eq 'ServiceContracts' && $INFO_FIELD.name eq 'referral_code' && !empty($INFO_FIELD.readonly_locked)}
						<div class="mk-so-inline-detail__field-edit is-locked">
							<input type="text" class="mk-so-inline-detail__input inputElement" name="referral_code" value="{$INFO_FIELD.raw_value|escape}" readonly="readonly" tabindex="-1" style="text-transform:uppercase" />
						</div>
					{/if}
				</div>
			{/foreach}
		</div>
	{/if}

	{assign var=MK_EDITABLE_TAGS value=($MODULE eq 'Leads' || $MODULE eq 'Potentials' || $MODULE eq 'Contacts')}
	{if empty($INLINE_HIDE_TAGS)}
	<div class="mk-so-inline-detail__tags{if $MK_EDITABLE_TAGS} is-editable{/if}"{if $MK_EDITABLE_TAGS} data-editable-tags="1"{/if}>
		<div class="mk-so-inline-detail__tags-head">
			<h3 class="mk-so-inline-detail__sec-title">Thẻ</h3>
			{if $MK_EDITABLE_TAGS}
			<label class="mk-so-inline-detail__field-label">
				<button type="button" class="mk-so-inline-detail__tags-toggle" data-role="tag-edit-toggle" aria-expanded="false">Sửa thẻ</button>
				<span class="mk-so-inline-detail__tags-hint">(chọn theo nhóm)</span>
			</label>
			{/if}
		</div>
		<div class="mk-so-inline-detail__tags-list" data-role="selected-tags">
			{if isset($INLINE_TAGS) && $INLINE_TAGS|@count gt 0}
				{foreach from=$INLINE_TAGS item=TAG}
					<span class="{if !empty($TAG.cls)}{$TAG.cls|escape}{else}mk-tag{/if}" data-tag="{$TAG.key|escape}" title="{$TAG.name|escape}">{$TAG.label|escape}</span>
				{/foreach}
			{else}
				<span class="mk-so-inline-detail__tags-empty">Chưa có tag</span>
			{/if}
		</div>
		{if $MK_EDITABLE_TAGS}
			<div class="mk-so-inline-detail__tags-picker" data-role="tag-picker" hidden></div>
		{/if}
	</div>
	{/if}

	{if $MODULE eq 'Leads' || $MODULE eq 'ServiceContracts' || $MODULE eq 'Potentials' || $MODULE eq 'Contacts'}
		{assign var=LT value=$INLINE_LAST_TOUCH|default:[]}
		{assign var=LT_CAN_ADD value=true}
		{if isset($LT.can_add) && empty($LT.can_add)}{assign var=LT_CAN_ADD value=false}{/if}
		{assign var=LT_NEXT value=$LT.next_n|default:1}
		{assign var=LT_COUNT value=$LT.count|default:0}
		{assign var=LT_MAX value=$LT.max_calls|default:3}
		{assign var=LT_HINT value=$LT.hint|default:''}
		{assign var=LT_CALLS value=$LT.calls|default:[]}
		<div class="mk-so-inline-detail__last-touch"
			data-role="last-touch"
			data-record-id="{$RECORD->getId()|escape}"
			data-lt-module="{$MODULE|escape}"
			data-lt-next="{$LT_NEXT|escape}"
			data-lt-hint="{$LT_HINT|escape}"
			data-lt-count="{$LT_COUNT|escape}"
			data-lt-max="{$LT_MAX|escape}"
			{if !empty($LT.reminder_at_label)} data-lt-reminder="{$LT.reminder_at_label|escape}"{/if}
			{if empty($LT_CAN_ADD)} data-lt-locked="1"{/if}>
			<div class="mk-so-inline-detail__last-touch-head">
				<div class="mk-so-inline-detail__last-touch-title-wrap">
					<h3 class="mk-so-inline-detail__sec-title">{if $MODULE eq 'ServiceContracts' || $MODULE eq 'Leads' || $MODULE eq 'Potentials'}Tương tác{else}Cuộc gọi{/if}</h3>
					<span class="mk-so-inline-detail__last-touch-badge{if empty($LT_CAN_ADD)} is-done{else} is-open{/if}" data-role="lt-badge">{$LT_COUNT|escape}/{$LT_MAX|escape}</span>
				</div>
				<button type="button"
					class="mk-so-inline-detail__action mk-so-inline-detail__action--call mk-so-inline-detail__call-btn{if empty($LT_CAN_ADD)} is-locked{/if}"
					data-record-id="{$RECORD->getId()|escape}"
					data-lt-module="{$MODULE|escape}"
					data-lt-next="{$LT_NEXT|escape}"
					data-lt-hint="{$LT_HINT|escape}"
					{if !empty($LT.reminder_at_label)} data-lt-reminder="{$LT.reminder_at_label|escape}"{/if}
					{if empty($LT_CAN_ADD)} disabled="disabled" aria-disabled="true"{/if}
					title="{if empty($LT_CAN_ADD)}{if $LT_HINT neq ''}{$LT_HINT|escape}{else}Đã đủ số lần gọi Last Touch{/if}{else}Ghi cuộc gọi Last Touch #{if $LT_NEXT}{$LT_NEXT|escape}{else}1{/if}{/if}">
					<i class="fa fa-phone" aria-hidden="true"></i>
					<span>{if empty($LT_CAN_ADD)}Đã đủ gọi{else}Ghi cuộc gọi{/if}</span>
				</button>
			</div>
			<p class="mk-so-inline-detail__last-touch-hint" data-role="lt-hint" title="{if $LT_HINT neq ''}{$LT_HINT|escape}{/if}">{if $LT_HINT neq '' && ($LT_COUNT gt 0 || empty($LT_CAN_ADD))}{$LT_HINT|escape}{elseif $MODULE eq 'ServiceContracts'}Call #1 → 5 giờ → #2 → #3. Không nghe máy: nhắc sau 5 giờ. Nghe máy → Liên hệ Đã gửi tư vấn.{elseif $MODULE eq 'Leads'}Call #1 → 5 giờ → #2 → #3. Không nghe máy: nhắc sau 5 giờ. Nghe máy → Opp.{else}Call #1 → 5 giờ → #2 → #3. Không nghe máy: nhắc sau 5 giờ. Nghe máy → dừng chuỗi gọi.{/if}</p>
			<ul class="mk-so-inline-detail__last-touch-list" data-role="lt-list">
				{if $LT_CALLS|@count gt 0}
					{foreach from=$LT_CALLS item=CALL}
						<li class="mk-so-inline-detail__last-touch-item">
							<span class="mk-so-inline-detail__last-touch-n">Call #{$CALL.n|escape}</span>
							<span class="mk-so-inline-detail__last-touch-text">{if !empty($CALL.label)}{$CALL.label|escape}{else}{$CALL.called_at_label|escape} Kết quả: {$CALL.result|escape}{if !empty($CALL.note)} Ghi chú: {$CALL.note|escape}{/if}{/if}</span>
						</li>
					{/foreach}
				{else}
					<li class="mk-so-inline-detail__last-touch-empty">Chưa có Call #1 — bấm “Ghi cuộc gọi”.</li>
				{/if}
			</ul>
		</div>
	{/if}

	<div class="mk-so-inline-detail__bottom{if $MODULE eq 'ServiceContracts' || !empty($INLINE_SHOW_NEXT_ACTION) || !empty($INLINE_SHOW_CLASS_REG)} mk-so-inline-detail__bottom--split{/if}">
		{if !empty($INLINE_SHOW_CLASS_REG)}
			{assign var=CLASS_REG value=$INLINE_CLASS_REG|default:[]}
			<div class="mk-so-inline-detail__notes mk-so-inline-detail__class-reg" data-class-reg="1">
				<label class="mk-so-inline-detail__notes-label">Log đăng ký học</label>
				<p class="mk-so-inline-detail__class-reg-hint">{if isset($CLASS_REG.hint)}{$CLASS_REG.hint|escape}{/if}</p>
				<ul class="mk-so-inline-detail__class-reg-list">
					{if isset($CLASS_REG.logs) && $CLASS_REG.logs|@count gt 0}
						{foreach from=$CLASS_REG.logs item=REG_LOG}
							<li class="mk-so-inline-detail__class-reg-item{if !empty($REG_LOG.is_retake)} is-retake{/if}" data-id="{$REG_LOG.id|escape}">
								<span class="mk-so-inline-detail__class-reg-n{if !empty($REG_LOG.is_retake)} is-retake{/if}">{if !empty($REG_LOG.badge)}{$REG_LOG.badge|escape}{else}Lần {$REG_LOG.n|escape}{/if}</span>
								<span class="mk-so-inline-detail__class-reg-text">{$REG_LOG.label|escape}</span>
							</li>
						{/foreach}
					{else}
						<li class="mk-so-inline-detail__class-reg-empty">Chưa có lần đăng ký nào</li>
					{/if}
				</ul>
				{if !isset($CLASS_REG.can_add) || !empty($CLASS_REG.can_add)}
					<div class="mk-so-inline-detail__class-reg-add">
						<input type="text" class="mk-so-inline-detail__input mk-so-inline-detail__class-reg-date inputElement" placeholder="dd/mm/yyyy" maxlength="10" />
						<button type="button" class="mk-so-inline-detail__action mk-so-inline-detail__action--outline mk-so-inline-detail__class-reg-add-btn">
							<i class="fa fa-plus" aria-hidden="true"></i>
							<span>Thêm đăng ký</span>
						</button>
					</div>
				{/if}
			</div>
		{elseif !empty($INLINE_SHOW_NEXT_ACTION)}
			<div class="mk-so-inline-detail__notes mk-so-inline-detail__next-action{if !empty($INLINE_NEXT_ACTION_LOCKED)} is-locked{/if}">
				<label class="mk-so-inline-detail__notes-label" for="mk-crm-inline-next-{$RECORD->getId()}">
					Hành động tiếp theo{if !empty($INLINE_NEXT_ACTION_LOCKED)} <span class="mk-so-inline-detail__lock-hint">(tự động — không sửa)</span>{/if}
				</label>
				<textarea id="mk-crm-inline-next-{$RECORD->getId()}" class="mk-so-inline-detail__notes-input mk-so-inline-detail__next-action-input inputElement" name="next_action" rows="2"{if !empty($INLINE_NEXT_ACTION_LOCKED)} readonly="readonly" disabled="disabled" aria-readonly="true"{/if}>{$INLINE_NEXT_ACTION|escape}</textarea>
				{if !empty($INLINE_NEXT_ACTION_TIMEFRAME)}
					<div class="mk-so-inline-detail__next-action-meta">
						<span class="mk-so-inline-detail__next-action-time{if !empty($INLINE_NEXT_ACTION_OVERDUE)} is-overdue{/if}" data-alert-days="{$INLINE_NEXT_ACTION_ALERT_DAYS|default:''|escape}">
							{if !empty($INLINE_NEXT_ACTION_OVERDUE)}
								<i class="fa fa-exclamation-circle" aria-hidden="true"></i>
							{else}
								<i class="fa fa-clock-o" aria-hidden="true"></i>
							{/if}
							<span class="mk-so-inline-detail__next-action-time-label">{$INLINE_NEXT_ACTION_TIMEFRAME|escape}</span>
							{if isset($INLINE_NEXT_ACTION_ALERT_DAYS) && $INLINE_NEXT_ACTION_ALERT_DAYS neq '' && empty($INLINE_NEXT_ACTION_OVERDUE)}
								<span class="mk-so-inline-detail__next-action-time-rule">(cảnh báo {$INLINE_NEXT_ACTION_ALERT_DAYS|escape} ngày)</span>
							{/if}
						</span>
					</div>
				{elseif isset($INLINE_NEXT_ACTION_ALERT_DAYS) && $INLINE_NEXT_ACTION_ALERT_DAYS neq ''}
					<div class="mk-so-inline-detail__next-action-meta">
						<span class="mk-so-inline-detail__next-action-time" data-alert-days="{$INLINE_NEXT_ACTION_ALERT_DAYS|escape}">
							<i class="fa fa-clock-o" aria-hidden="true"></i>
							<span class="mk-so-inline-detail__next-action-time-label">Cảnh báo {$INLINE_NEXT_ACTION_ALERT_DAYS|escape} ngày</span>
						</span>
					</div>
				{/if}
			</div>
		{/if}
		{if $MODULE eq 'ServiceContracts' && isset($INLINE_SC_MATERIALS) && $INLINE_SC_MATERIALS.name}
			{assign var=MAT value=$INLINE_SC_MATERIALS}
			<div class="mk-so-inline-detail__notes mk-so-inline-detail__notes--materials" data-field-name="{$MAT.name|escape}" data-field-type="text" data-editable="{if !empty($MAT.editable)}1{else}0{/if}">
				<label class="mk-so-inline-detail__notes-label" for="mk-crm-inline-mat-{$RECORD->getId()}">{$MAT.label|escape}</label>
				<div class="mk-so-inline-detail__field-view"{if !empty($MAT.editable)} style="display:none"{/if}>{$MAT.value|escape|nl2br}</div>
				{if !empty($MAT.editable)}
					<textarea id="mk-crm-inline-mat-{$RECORD->getId()}" class="mk-so-inline-detail__notes-input mk-so-inline-detail__input mk-so-inline-detail__textarea inputElement" name="{$MAT.name|escape}" rows="2">{$MAT.raw_value|escape}</textarea>
				{/if}
			</div>
		{/if}
		<div class="mk-so-inline-detail__notes"{if empty($INLINE_SHOW_NEXT_ACTION) && empty($INLINE_SHOW_CLASS_REG) && $MODULE neq 'ServiceContracts'} style="grid-column: 1 / -1; width: 100%;"{/if}>
			<label class="mk-so-inline-detail__notes-label" for="mk-crm-inline-note-{$RECORD->getId()}">Ghi chú</label>
			<textarea id="mk-crm-inline-note-{$RECORD->getId()}" class="mk-so-inline-detail__notes-input inputElement" name="description" rows="2">{$INLINE_NOTES|escape}</textarea>
		</div>
	</div>

	<div class="mk-so-inline-detail__actions">
		<div class="mk-so-inline-detail__actions-left">
			{if $MODULE eq 'Accounts' && !empty($INLINE_PRINT_URL)}
			<button type="button" class="mk-so-inline-detail__action mk-so-inline-detail__action--outline mk-so-inline-detail__print-btn mk-so-inline-detail__preview-btn" data-print-url="{$INLINE_PRINT_URL|escape}" data-print-download-url="{$INLINE_PRINT_DOWNLOAD_URL|default:$INLINE_WORD_DOWNLOAD_URL|escape}"{if !empty($INLINE_WORD_DOWNLOAD_URL)} data-word-download-url="{$INLINE_WORD_DOWNLOAD_URL|escape}"{/if} title="{vtranslate('LBL_PREVIEW_FRANCHISE_CONTRACT_HINT', 'Accounts')}">
				<i class="fa fa-eye" aria-hidden="true"></i>
				<span class="mk-so-inline-detail__print-label">{vtranslate('LBL_PREVIEW_FRANCHISE_CONTRACT', 'Accounts')}</span>
			</button>
			{if !empty($INLINE_WORD_DOWNLOAD_URL)}
			<button type="button" class="mk-so-inline-detail__action mk-so-inline-detail__action--outline mk-so-inline-detail__word-btn" data-word-download-url="{$INLINE_WORD_DOWNLOAD_URL|escape}" title="{vtranslate('LBL_PRINT_FRANCHISE_CONTRACT_HINT', 'Accounts')}">
				<i class="fa fa-file-word-o" aria-hidden="true"></i>
				<span>{vtranslate('LBL_PRINT_FRANCHISE_CONTRACT', 'Accounts')}</span>
			</button>
			{/if}
			{/if}
			<button type="button" class="mk-so-inline-detail__action mk-so-inline-detail__action--outline mk-so-inline-detail__save-btn" title="Lưu">
				<i class="fa fa-save" aria-hidden="true"></i>
				<span>Lưu</span>
			</button>
			<button type="button" class="mk-so-inline-detail__action mk-so-inline-detail__action--primary mk-so-inline-detail__edit-btn" title="Mở form sửa">
				<i class="fa fa-external-link" aria-hidden="true"></i>
				<span>Sửa</span>
			</button>
			{if $MODULE eq 'ServiceContracts'}
			<a class="mk-so-inline-detail__action mk-so-inline-detail__action--primary mk-so-inline-detail__create-quote-btn" href="index.php?module=Quotes&amp;view=Edit&amp;app=SALES&amp;servicecontract_id={$RECORD->getId()}" title="Tạo báo giá cho khách chuyển nhượng">
				<i class="fa fa-file-text-o" aria-hidden="true"></i>
				<span>Báo giá</span>
			</a>
			<label class="mk-so-inline-detail__aff-toggle" title="Tắt chỉ ẩn mã, không xóa">
				<input type="checkbox" class="mk-so-inline-detail__aff-visible-input"{if !empty($INLINE_SC_AFF_VISIBLE)} checked="checked"{/if} />
				<span class="mk-so-inline-detail__aff-toggle-ui" aria-hidden="true"></span>
				<span class="mk-so-inline-detail__aff-toggle-label">Cho phép giới thiệu</span>
			</label>
			<span class="mk-so-inline-detail__aff-pill"{if empty($INLINE_SC_AFF_VISIBLE) || empty($INLINE_SC_AFFILIATE_CODE)} hidden="hidden"{/if} title="Mã AFF">{$INLINE_SC_AFFILIATE_CODE|escape}</span>
			{/if}
			{if $MODULE eq 'Leads'}
				{assign var=INLINE_CAN_CONVERT value=$INLINE_CAN_CONVERT|default:true}
				<button type="button"
					class="mk-so-inline-detail__action mk-so-inline-detail__action--convert mk-so-inline-detail__convert-btn{if empty($INLINE_CAN_CONVERT)} is-converted{/if}"
					data-record-id="{$RECORD->getId()|escape}"
					{if !empty($INLINE_POTENTIAL_URL)} data-potential-url="{$INLINE_POTENTIAL_URL|escape}"{/if}
					{if empty($INLINE_CAN_CONVERT)} disabled="disabled" aria-disabled="true" hidden="hidden" style="display:none"{/if}
					title="{if empty($INLINE_CAN_CONVERT)}Đã chuyển sang Cơ hội{else}Convert{/if}">
					<i class="fa fa-exchange" aria-hidden="true"></i>
					<span>{if empty($INLINE_CAN_CONVERT)}Đã chuyển{else}Convert{/if}</span>
				</button>
			{/if}
			{if $MODULE eq 'Potentials'}
				<button type="button"
					class="mk-so-inline-detail__action mk-so-inline-detail__action--to-customer mk-so-inline-detail__to-customer-btn"
					data-record-id="{$RECORD->getId()|escape}"
					title="Chuyển sang khách hàng">
					<i class="fa fa-user" aria-hidden="true"></i>
					<span>Sang khách hàng</span>
				</button>
			{/if}
			<button type="button" class="mk-so-inline-detail__action mk-so-inline-detail__action--ghost mk-so-inline-detail__cancel-edit">
				<i class="fa fa-times" aria-hidden="true"></i>
				<span>Hủy sửa</span>
			</button>
			<button type="button" class="mk-so-inline-detail__action mk-so-inline-detail__action--ghost mk-so-inline-detail__view-full-btn" title="Xem đầy đủ">
				<i class="fa fa-expand" aria-hidden="true"></i>
				<span>Chi tiết</span>
			</button>
		</div>
		<div class="mk-so-inline-detail__actions-right">
		</div>
	</div>
</div>
{/strip}
