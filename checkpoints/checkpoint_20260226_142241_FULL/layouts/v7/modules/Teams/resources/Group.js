console.log('[GroupManager] JS file LOADED');

(function($){
	'use strict';

	console.log('[GroupManager] JS file loaded');

	var GroupManager = {
		init: function() {
			this.bindEvents();
		},

		bindEvents: function() {
			// Edit Group is now handled via direct link (no JS needed)
			// Delete Group - AJAX using AppConnector
			$(document).on('click', '.js-delete-group', function(e) {
				e.preventDefault();
				e.stopImmediatePropagation();

				var groupId = $(this).data('groupid') || $(this).attr('data-groupid');
				if (!groupId) {
					console.error('[DeleteGroup] Missing groupid', this);
					return;
				}

				console.log('DELETE GROUP CLICKED', groupId);

				if (!confirm('Delete this group?')) return;

				AppConnector.request({
					data: {
						module: 'Teams',
						action: 'GroupAjax',
						mode: 'deleteGroup',
						record: groupId
					}
				}).done(function(response) {
					if (response && response.success) {
						location.reload();
					} else {
						app.helper.showErrorNotification({
							message: response.error?.message || 'Delete failed'
						});
					}
				}).fail(function(err) {
					console.error('[DeleteGroup] AJAX failed', err);
					app.helper.showErrorNotification({
						message: 'Delete failed: ' + (err.message || err)
					});
				});
			});
		},

	};

	$(document).ready(function(){
		console.log('[GroupManager] Document ready, initializing...');
		GroupManager.init();
		console.log('[GroupManager] Initialization complete');
	});

	// Expose for TeamsModal.js to use
	window.GroupManager = GroupManager;
})(jQuery);
