{strip}
<div class="mk-reports-mgmt-root management-report-wrap">
	<header class="mk-reports-mgmt-header">
		<div class="mk-reports-mgmt-breadcrumb" aria-label="Breadcrumb">
			<span>{vtranslate('LBL_MANAGEMENT', 'Vtiger')}</span>
			<span class="mk-reports-mgmt-breadcrumb__sep">&gt;</span>
			<span>{vtranslate('Reports', 'Reports')}</span>
		</div>
		<h1 class="mk-reports-mgmt-title">Báo cáo</h1>
	</header>

	<div class="mk-reports-mgmt-body mgmt-report-body">
		<section class="mk-admin-kpi-panel" id="mkRoleReports" aria-label="Báo cáo theo vai">
			<div class="mk-admin-kpi-panel-head">
				<h2 class="mk-admin-kpi-panel-title">Báo cáo theo vai</h2>
				<span class="mk-admin-kpi-pill">Cùng giao diện bảng điều khiển</span>
			</div>
			<div id="mkRoleReportsBody"><div class="mk-admin-kpi-detail-loading">Đang tải…</div></div>
		</section>
		<script type="text/javascript" src="{vresource_url('layouts/v7/modules/Reports/resources/RoleBoards.js')}?mk_v=20261017_roleclick1"></script>
	</div>
</div>
{/strip}
