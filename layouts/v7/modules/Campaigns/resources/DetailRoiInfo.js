/* Campaigns Detail: ROI formula info (read-only) next to Expected / Actual ROI values. */

jQuery(function () {
	function tr(k) {
		return app.vtranslate(k, 'Campaigns');
	}

	function openRoiModal() {
		var html = '' +
			'<div class="modal fade campaigns-roi-modal" tabindex="-1">' +
			'  <div class="modal-dialog">' +
			'    <div class="modal-content">' +
			'      <div class="modal-header">' +
			'        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>' +
			'        <h4 class="modal-title">' + tr('LBL_CAMPAIGN_ROI_MODAL_TITLE') + '</h4>' +
			'      </div>' +
			'      <div class="modal-body">' +
			'        <p>' + tr('LBL_CAMPAIGN_ROI_FORMULA_EXPLAIN') + '</p>' +
			'        <pre style="background:#f8fafc;padding:10px;border-radius:8px;">ROI = (Revenue - Cost) / Cost x 100 (%)</pre>' +
			'        <p class="text-muted small">' + tr('LBL_CAMPAIGN_ROI_EXPECTED_FIELDS') + '</p>' +
			'        <p class="text-muted small">' + tr('LBL_CAMPAIGN_ROI_ACTUAL_HINT') + '</p>' +
			'        <p class="text-muted small">' + tr('LBL_CAMPAIGN_ROI_FIELDS_READONLY') + '</p>' +
			'      </div>' +
			'      <div class="modal-footer">' +
			'        <button type="button" class="btn btn-default" data-dismiss="modal">' + app.vtranslate('LBL_CLOSE', 'Vtiger') + '</button>' +
			'      </div>' +
			'    </div>' +
			'  </div>' +
			'</div>';
		var $m = jQuery(html).appendTo('body');
		$m.modal('show');
		$m.on('hidden.bs.modal', function () {
			$m.remove();
		});
	}

	jQuery.each(['expectedroi', 'actualroi'], function (idx, name) {
		var $cell = jQuery('#Campaigns_detailView_fieldValue_' + name);
		if (!$cell.length) {
			return;
		}
		if ($cell.find('.campaigns-detail-roi-info').length) {
			return;
		}
		var $wrap = jQuery('<span class="campaigns-detail-roi-actions" style="margin-left:6px;white-space:nowrap;"/>');
		var $btn = jQuery(
			'<button type="button" class="btn btn-xs btn-default campaigns-detail-roi-info" title="ROI">' +
				'<i class="fa fa-info-circle"></i>' +
			'</button>'
		);
		$wrap.append($btn);
		$cell.find('.value').first().after($wrap);
	});

	jQuery(document).on('click', '.campaigns-detail-roi-info', function (e) {
		e.preventDefault();
		openRoiModal();
	});
});
