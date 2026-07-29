{* Franchise Detail summary host — filled by ServiceContractMkDetail.js *}
{strip}
<div class="mk-sc-mk-detail" id="mkScMkDetail" data-record="{$RECORD->getId()|escape:'html'}" hidden>
	<section class="mk-sc-mk-detail__card">
		<header class="mk-sc-mk-detail__head">
			<h2>Chi tiết khách chuyển nhượng</h2>
			<span class="mk-sc-mk-detail__aff" id="mkScMkAff" hidden></span>
		</header>
		<div class="mk-sc-mk-detail__grid" id="mkScMkFields"></div>
		<div class="mk-sc-mk-detail__interactions" id="mkScMkInteractions"></div>
	</section>
</div>
{/strip}
