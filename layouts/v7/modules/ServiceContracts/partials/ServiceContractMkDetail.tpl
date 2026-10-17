{* Franchise Detail summary host — filled by ServiceContractMkDetail.js *}
{strip}
<div class="mk-sc-mk-detail" id="mkScMkDetail" data-record="{$RECORD->getId()|escape:'html'}" hidden>
	<section class="mk-sc-mk-detail__card">
		<header class="mk-sc-mk-detail__head">
			<div class="mk-sc-mk-detail__identity" id="mkScMkIdentity"></div>
			<div class="mk-sc-mk-detail__aff-slot">
				<span class="mk-sc-mk-detail__aff" id="mkScMkAff" hidden></span>
				<label class="mk-sc-aff-toggle" title="Tắt: xóa mã khỏi hệ thống. Bật: tạo lại mã giới thiệu.">
					<input type="checkbox" id="mkScMkAffVisible" />
					<span class="mk-sc-aff-toggle__ui" aria-hidden="true"></span>
					<span class="mk-sc-aff-toggle__label">Cho phép giới thiệu</span>
				</label>
			</div>
		</header>
		<div class="mk-sc-mk-detail__columns" id="mkScMkFields"></div>
		<div class="mk-sc-mk-detail__interactions" id="mkScMkInteractions"></div>
	</section>
</div>
{/strip}
