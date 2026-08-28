{* Anti-FOUC + ẩn field thừa ngay khi parse HTML (trước khi JS chạy). *}
{strip}
<script type="text/javascript">document.documentElement.classList.add('mk-ac-create-guard');</script>
<style type="text/css">
html.mk-ac-create-guard:not(.mk-ac-create-ready) #mk-dash-split-root,
html.mk-ac-create-guard:not(.mk-ac-create-ready) #mkAcCreateWorkspace,
html.mk-ac-create-guard:not(.mk-ac-create-ready) .editViewPageDiv {
	opacity: 0 !important;
	visibility: hidden !important;
	pointer-events: none !important;
}
html.mk-ac-create-guard.mk-ac-create-ready #mk-dash-split-root,
html.mk-ac-create-guard.mk-ac-create-ready #mkAcCreateWorkspace,
html.mk-ac-create-guard.mk-ac-create-ready .editViewPageDiv {
	visibility: visible !important;
	opacity: 1 !important;
	pointer-events: auto !important;
	transition: none !important;
}
html.mk-ac-create-guard #mkAcFormHost .fieldBlockContainer[data-block="LBL_CUSTOM_INFORMATION"],
html.mk-ac-create-guard #mkAcFormHost .fieldBlockContainer[data-block="LBL_ADDRESS_INFORMATION"],
html.mk-ac-create-guard #mkAcFormHost .fieldBlockContainer[data-block="LBL_DESCRIPTION_INFORMATION"] {
	display: none !important;
}
{assign var=MK_AC_HIDE_FIELDS value=['fax','otherphone','employees','email2','emailoptout','ownership','industry','rating','accounttype','parent_id','website','annualrevenue','tickersymbol','siccode','notify_owner','account_no','description','bill_street','bill_city','bill_state','bill_code','bill_country','bill_pobox','ship_street','ship_city','ship_state','ship_code','ship_country','ship_pobox','tb_term_years','tb_party_b_name','tb_party_b_phone','tb_party_b_email','tb_party_b_contact_addr','tb_pay_1','tb_pay_2','tb_pay_3']}
{foreach from=$MK_AC_HIDE_FIELDS item=MK_F}
html.mk-ac-create-guard #mkAcFormHost td.fieldLabel:has(+ td.fieldValue [name="{$MK_F}"]),
html.mk-ac-create-guard #mkAcFormHost td.fieldValue:has([name="{$MK_F}"]),
html.mk-ac-create-guard #mkAcFormHost td.fieldLabel:has(+ td.fieldValue [data-fieldname="{$MK_F}"]),
html.mk-ac-create-guard #mkAcFormHost td.fieldValue:has([data-fieldname="{$MK_F}"]) {
	display: none !important;
}
{/foreach}
</style>
{/strip}
