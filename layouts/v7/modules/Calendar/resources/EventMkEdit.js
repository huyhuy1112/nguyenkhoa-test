/*+***********************************************************************************
 * Calendar Event — full-page create/edit (MANAGEMENT shell).
 *************************************************************************************/
jQuery(function () {
	var $workspace = jQuery('#mkEventCreateWorkspace, #mkCalendarCreateWorkspace');
	if (!$workspace.length) {
		return;
	}

	jQuery('#mkActivitySaveTop, #mkEventSaveTop').on('click', function () {
		var $form = jQuery('#EditView');
		if ($form.length) {
			$form.find('button.saveButton').first().trigger('click');
		}
	});
});
