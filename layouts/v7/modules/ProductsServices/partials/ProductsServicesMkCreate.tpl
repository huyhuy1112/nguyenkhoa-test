{* Create / Edit Hàng hoá — modern inventory workspace shell. *}
{strip}
{assign var=MK_LIST_URL value='index.php?module=ProductsServices&view=List&app=INVENTORY'}
{assign var=MK_IS_EDIT value=(!empty($RECORD_ID) && empty($IS_DUPLICATE))}
<div class="mk-ps-create mk-ps-create--v4{if $MK_IS_EDIT} mk-ps-create--edit{/if}" id="mkPsCreateWorkspace" data-mk-ps-create="1">
	<header class="mk-ps-page-head">
		<div class="mk-ps-page-head__glow" aria-hidden="true"></div>
		<div class="mk-ps-page-head__top">
			<nav class="mk-ps-page-head__crumb" aria-label="Breadcrumb">
				<a href="index.php?module=Home&amp;view=DashBoard&amp;app=INVENTORY">{vtranslate('LBL_INVENTORY', 'Vtiger')}</a>
				<span aria-hidden="true">/</span>
				<a href="{$MK_LIST_URL}">Hàng hoá</a>
				<span aria-hidden="true">/</span>
				{if $MK_IS_EDIT}<span aria-current="page">Chỉnh sửa</span>{else}<span aria-current="page">Thêm mới</span>{/if}
			</nav>
			<div class="mk-ps-page-head__actions">
				<a class="mk-ps-btn mk-ps-btn--ghost" href="{$MK_LIST_URL}">{vtranslate('LBL_CANCEL', $MODULE)}</a>
				<button type="button" class="mk-ps-btn mk-ps-btn--primary mk-ps-btn--save" id="mkPsSaveTop" data-action="save">
					{vtranslate('LBL_SAVE', $MODULE)}
				</button>
			</div>
		</div>
		<div class="mk-ps-page-head__hero">
			<div class="mk-ps-page-head__copy">
				<p class="mk-ps-page-head__eyebrow">Kho · Catalog</p>
				{if $MK_IS_EDIT}
					<h1 class="mk-ps-page-head__title">Chỉnh sửa hàng hoá</h1>
					{if !empty($RECORD_STRUCTURE_MODEL)}
						<p class="mk-ps-page-head__sub">{$RECORD_STRUCTURE_MODEL->getRecordName()|escape}</p>
					{/if}
				{else}
					<h1 class="mk-ps-page-head__title">Thêm hàng hoá mới</h1>
					<p class="mk-ps-page-head__sub">Nhập SKU, loại, giá và tồn — gọn một màn hình.</p>
				{/if}
			</div>
			<ul class="mk-ps-page-head__chips" aria-hidden="true">
				<li>SKU</li>
				<li>Loại</li>
				<li>Giá</li>
				<li>Tồn</li>
			</ul>
		</div>
	</header>

	<div class="mk-ps-create-body">
		<div class="mk-ps-create-main">
			<div class="mk-ps-form-host" id="mkPsFormHost">
				{include file="partials/EditViewFormOnly.tpl"|vtemplate_path:$MODULE}
			</div>
		</div>
	</div>
</div>
{/strip}
