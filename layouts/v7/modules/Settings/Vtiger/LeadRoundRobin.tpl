{*+***********************************************************************************
 * Settings → Round Robin phân bổ Lead
 *************************************************************************************}
{strip}
{assign var=SNAP value=$RR_SNAPSHOT}
<div class="nk-rr" id="nk-lead-round-robin">
	<div class="nk-rr__hero">
		<p class="nk-rr__eyebrow">Marketing &amp; Sales</p>
		<h1 class="nk-rr__title">{vtranslate('LBL_NK_LEAD_ROUND_ROBIN', $QUALIFIED_MODULE)}</h1>
		<p class="nk-rr__desc">{vtranslate('LBL_NK_LEAD_ROUND_ROBIN_DESC', $QUALIFIED_MODULE)}</p>
	</div>

	<section class="nk-rr__card nk-rr__stats">
		<div class="nk-rr__stat">
			<span class="nk-rr__stat-label">Số Sale trong pool</span>
			<strong class="nk-rr__stat-value">{$SNAP.count|escape}</strong>
		</div>
		<div class="nk-rr__stat">
			<span class="nk-rr__stat-label">Người nhận Lead tiếp theo</span>
			<strong class="nk-rr__stat-value">
				{if $SNAP.next_user}
					{$SNAP.next_user.name|escape}
				{else}
					—
				{/if}
			</strong>
			{if $SNAP.next_user && $SNAP.next_user.email}
				<span class="nk-rr__stat-meta">{$SNAP.next_user.email|escape}</span>
			{/if}
		</div>
		<div class="nk-rr__stat">
			<span class="nk-rr__stat-label">Cập nhật gần nhất</span>
			<strong class="nk-rr__stat-value">
				{if $SNAP.modified_at}{$SNAP.modified_at|escape}{else}Chưa chạy{/if}
			</strong>
		</div>
	</section>

	<section class="nk-rr__card">
		<div class="nk-rr__card-head">
			<h2 class="nk-rr__card-title">Danh sách Sale (role Sale)</h2>
			<button type="button" class="btn btn-default nk-rr__reset js-nk-rr-reset">Đặt lại vòng xoay</button>
		</div>
		<p class="nk-rr__hint">
			Pool tự lấy từ user <strong>Active</strong> có vai trò thuộc persona <strong>Sale</strong>.
			Lead mới (GD 1.1 / 1.2) sẽ xoay vòng theo thứ tự bên dưới. Convert Opp/Contact giữ nguyên phụ trách.
		</p>
		{if $SNAP.count eq 0}
			<div class="nk-rr__empty">Chưa có user role Sale nào đang Active. Tạo tài khoản trong Teams và chọn vai trò Sale.</div>
		{else}
			<ol class="nk-rr__list">
				{foreach from=$SNAP.users item=U name=rrloop}
					<li class="nk-rr__item {if $SNAP.next_user && $SNAP.next_user.id eq $U.id}is-next{/if}">
						<span class="nk-rr__idx">{$smarty.foreach.rrloop.iteration}</span>
						<div class="nk-rr__who">
							<strong>{$U.name|escape}</strong>
							<span>{$U.email|escape}</span>
						</div>
						{if $SNAP.next_user && $SNAP.next_user.id eq $U.id}
							<span class="nk-rr__badge">Tiếp theo</span>
						{/if}
					</li>
				{/foreach}
			</ol>
		{/if}
		<span class="nk-rr__status" id="nk-rr-status" aria-live="polite"></span>
	</section>
</div>
{/strip}
