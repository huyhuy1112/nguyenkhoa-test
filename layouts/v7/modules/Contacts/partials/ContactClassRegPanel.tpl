{* Log đăng ký học + Đã cấp bằng / tài khoản — chỉ trên Contact Detail *}
{strip}
{if !empty($MK_CONTACT_MODERN_UI)}
{assign var=CLASS_REG value=$MK_CLASS_REG|default:[]}
{assign var=CREDS value=$MK_CREDENTIALS|default:[]}
<div class="mk-contact-class-panel" data-record-id="{$RECORD->getId()|escape}" data-mk-class-panel="1">
	<div class="mk-contact-class-panel__grid">
		<section class="mk-contact-class-panel__section mk-contact-class-panel__reg" aria-labelledby="mk-class-reg-title">
			<h3 id="mk-class-reg-title" class="mk-contact-class-panel__title">Log đăng ký học</h3>
			{if !empty($CLASS_REG.rights_label)}
				<div class="mk-contact-class-panel__rights{if !empty($CLASS_REG.retake_available)} is-active{elseif !empty($CLASS_REG.retake_used)} is-used{elseif !empty($CLASS_REG.first_on)} is-expired{/if}">
					{$CLASS_REG.rights_label|escape}
				</div>
			{/if}
			{if !empty($CLASS_REG.warning)}
				<div class="mk-contact-class-panel__warning" role="status">{$CLASS_REG.warning|escape}</div>
			{/if}
			{if !empty($CLASS_REG.hint)}
				<p class="mk-contact-class-panel__hint">{$CLASS_REG.hint|escape}</p>
			{/if}
			<ul class="mk-contact-class-panel__list">
				{if isset($CLASS_REG.logs) && $CLASS_REG.logs|@count gt 0}
					{foreach from=$CLASS_REG.logs item=REG_LOG}
						<li class="mk-contact-class-panel__item{if !empty($REG_LOG.is_retake)} is-retake{/if}" data-id="{$REG_LOG.id|escape}" data-kind="{$REG_LOG.kind|default:'register'|escape}" data-class-code="{$REG_LOG.class_code|default:'mqbb'|escape}">
							<div class="mk-contact-class-panel__item-main">
								<span class="mk-contact-class-panel__n{if !empty($REG_LOG.is_retake)} is-retake{/if}">{if !empty($REG_LOG.badge)}{$REG_LOG.badge|escape}{else}Lần {$REG_LOG.n|escape}{/if}</span>
								<span class="mk-contact-class-panel__class-tag">{$REG_LOG.class_label|default:'MQBB'|escape}</span>
								<span class="mk-contact-class-panel__text">{$REG_LOG.label|escape}</span>
							</div>
							{if !empty($REG_LOG.show_retake_btn)}
								<button type="button" class="mk-contact-class-panel__btn mk-contact-class-panel__btn--retake mk-contact-class-panel__retake-open" title="Chọn ngày học lại">
									<i class="fa fa-refresh" aria-hidden="true"></i>
									<span>Học lại</span>
								</button>
							{/if}
						</li>
					{/foreach}
				{else}
					<li class="mk-contact-class-panel__empty">Chưa có lần đăng ký nào</li>
				{/if}
			</ul>
			{if !empty($CLASS_REG.retake_available)}
				<div class="mk-contact-class-panel__retake-form hide" data-retake-form="1">
					<label class="mk-contact-class-panel__retake-label">Chọn ngày Học lại lần 1</label>
					<div class="mk-contact-class-panel__retake-row">
						<input type="date"
							class="mk-contact-class-panel__date mk-contact-class-panel__retake-date inputElement"
							{if !empty($CLASS_REG.retake_date_min)}min="{$CLASS_REG.retake_date_min|escape}"{/if}
							{if !empty($CLASS_REG.retake_date_max)}max="{$CLASS_REG.retake_date_max|escape}"{/if}
							aria-label="Chọn ngày học lại" />
						<button type="button" class="mk-contact-class-panel__btn mk-contact-class-panel__btn--retake mk-contact-class-panel__retake-save">
							<i class="fa fa-check" aria-hidden="true"></i>
							<span>Lưu học lại</span>
						</button>
						<button type="button" class="mk-contact-class-panel__btn mk-contact-class-panel__btn--outline mk-contact-class-panel__retake-cancel">
							<span>Hủy</span>
						</button>
					</div>
				</div>
			{/if}
			{if !isset($CLASS_REG.can_add) || !empty($CLASS_REG.can_add)}
				<div class="mk-contact-class-panel__add">
					<select class="mk-contact-class-panel__select mk-contact-class-panel__class-select inputElement" name="class_code" aria-label="Chọn lớp học">
						{foreach from=$CLASS_REG.class_options|default:[] item=OPT}
							<option value="{$OPT.code|escape}">{$OPT.label|escape}</option>
						{/foreach}
						{if empty($CLASS_REG.class_options)}
							<option value="mqbb">MQBB</option>
							<option value="pcth">PCTH</option>
						{/if}
					</select>
					<input type="date"
						class="mk-contact-class-panel__date mk-contact-class-panel__register-date inputElement"
						{if !empty($CLASS_REG.date_min)}min="{$CLASS_REG.date_min|escape}"{/if}
						{if !empty($CLASS_REG.date_max)}max="{$CLASS_REG.date_max|escape}"{/if}
						aria-label="Chọn ngày đăng ký học" />
					<button type="button" class="mk-contact-class-panel__btn mk-contact-class-panel__btn--outline mk-contact-class-panel__add-btn">
						<i class="fa fa-plus" aria-hidden="true"></i>
						<span>Thêm đăng ký</span>
					</button>
				</div>
			{/if}
		</section>

		<section class="mk-contact-class-panel__section mk-contact-class-panel__creds" aria-labelledby="mk-creds-title">
			<h3 id="mk-creds-title" class="mk-contact-class-panel__title">Cấp bằng &amp; tài khoản</h3>
			<div class="mk-contact-class-panel__field">
				<label class="mk-contact-class-panel__label" for="mk-da-cap-bang">Đã cấp bằng</label>
				<select id="mk-da-cap-bang" name="da_cap_bang" class="mk-contact-class-panel__select inputElement">
					{foreach from=$CREDS.bang_options item=OPT}
						<option value="{$OPT|escape}" {if isset($CREDS.da_cap_bang) && $CREDS.da_cap_bang eq $OPT}selected="selected"{/if}>{$OPT|escape}</option>
					{/foreach}
				</select>
			</div>
			<div class="mk-contact-class-panel__field">
				<label class="mk-contact-class-panel__label" for="mk-da-cap-tai-khoan">Đã cấp tài khoản</label>
				<select id="mk-da-cap-tai-khoan" name="da_cap_tai_khoan" class="mk-contact-class-panel__select inputElement">
					{foreach from=$CREDS.tk_options item=OPT}
						<option value="{$OPT|escape}" {if isset($CREDS.da_cap_tai_khoan) && $CREDS.da_cap_tai_khoan eq $OPT}selected="selected"{/if}>{$OPT|escape}</option>
					{/foreach}
				</select>
			</div>
			<button type="button" class="mk-contact-class-panel__btn mk-contact-class-panel__btn--primary mk-contact-class-panel__creds-save">
				<i class="fa fa-save" aria-hidden="true"></i>
				<span>Lưu trạng thái</span>
			</button>
		</section>
	</div>
</div>
{/if}
{/strip}
