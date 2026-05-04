/* Campaign Detail: image preview modal; other types use Download link */

jQuery(function () {
	var $box = jQuery('#campaign-detail-files-box');
	if (!$box.length) {
		return;
	}

	function closeModal($m) {
		if ($m && $m.length) {
			$m.modal('hide');
			$m.remove();
		}
	}

	$box.on('click', '.js-campaign-file-preview', function (e) {
		e.preventDefault();
		var url = jQuery(this).data('preview-url');
		if (!url) {
			return;
		}
		var title = app.vtranslate('LBL_CAMPAIGN_FILE_PREVIEW', 'Campaigns');
		var html =
			'<div class="modal fade campaign-file-preview-modal" tabindex="-1">' +
			'  <div class="modal-dialog modal-lg">' +
			'    <div class="modal-content">' +
			'      <div class="modal-header">' +
			'        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>' +
			'        <h4 class="modal-title">' + title + '</h4>' +
			'      </div>' +
			'      <div class="modal-body text-center js-campaign-preview-body" style="background:#f8fafc;"></div>' +
			'      <div class="modal-footer">' +
			'        <button type="button" class="btn btn-default" data-dismiss="modal">' +
			app.vtranslate('LBL_CLOSE', 'Vtiger') +
			'</button>' +
			'      </div>' +
			'    </div>' +
			'  </div>' +
			'</div>';
		var $m = jQuery(html).appendTo('body');
		jQuery('<img alt="" style="max-width:100%;max-height:70vh;"/>').attr('src', url).appendTo($m.find('.js-campaign-preview-body'));
		$m.modal('show');
		$m.on('hidden.bs.modal', function () {
			closeModal($m);
		});
	});
});
