{* Nhập kho — breadcrumb + tiêu đề *}
{strip}
<div class="mk-gi-header">
	<nav class="mk-gi-breadcrumb" aria-label="Breadcrumb">
		<ol class="mk-gi-breadcrumb__list">
			<li class="mk-gi-breadcrumb__item">
				<a href="index.php?module=Home&amp;view=DashBoard&amp;app=INVENTORY">{vtranslate('LBL_HOME','Vtiger')}</a>
			</li>
			<li class="mk-gi-breadcrumb__sep" aria-hidden="true">&gt;</li>
			<li class="mk-gi-breadcrumb__item mk-gi-breadcrumb__item--current">
				<span>{vtranslate('GoodsReceipt','GoodsReceipt')}</span>
			</li>
		</ol>
	</nav>
	<header class="mk-gi-action-header" role="region" aria-label="{vtranslate('GoodsReceipt','GoodsReceipt')}">
		<div class="mk-gi-action-header__brand">
			<span class="mk-gi-action-header__icon" aria-hidden="true">{include file="partials/GoodsReceiptListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='INBOUND'}</span>
			<div class="mk-gi-action-header__text">
				<h1 class="mk-gi-action-header__title">{vtranslate('GoodsReceipt','GoodsReceipt')}</h1>
				<p class="mk-gi-action-header__subtitle">Phiếu nhập kho và lịch sử lô hàng.</p>
			</div>
		</div>
		<div class="mk-gi-action-header__actions">
			<a class="mk-gi-btn mk-gi-btn--primary" href="index.php?module=GoodsReceipt&amp;view=Edit&amp;app=INVENTORY">
				<span class="mk-gi-btn__ic" aria-hidden="true">{include file="partials/GoodsReceiptListSvgIcon.tpl"|vtemplate_path:$MODULE ICON='PLUS'}</span>
				<span class="mk-gi-btn__txt">{vtranslate('LBL_ADD_RECORD','GoodsReceipt')}</span>
			</a>
		</div>
	</header>
</div>
{/strip}
