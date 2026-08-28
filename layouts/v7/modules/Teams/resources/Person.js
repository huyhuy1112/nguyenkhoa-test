console.log('[PersonManager] JS file LOADED');

(function($){
	'use strict';

	console.log('[PersonManager] JS file loaded');

	var PersonManager = {
		init: function() {
			this.bindEvents();
		},

		bindEvents: function() {
			// Delete person - use AJAX (same pattern as Group delete)
			$(document).on('click', '.js-delete-person', function(e){
				console.log('[PersonManager] CLICK FIRED - Delete Person');
				e.preventDefault();
				e.stopPropagation();
				e.stopImmediatePropagation();
				
				var $el = $(this);
				var userId = $el.data('userid') || $el.attr('data-userid');
				
				console.log('[PersonManager] Delete person clicked, User ID:', userId);
				console.log('[PersonManager] Element:', $el);
				console.log('[PersonManager] Data attributes:', $el.data());
				
				if (!userId) {
					console.error('[PersonManager] No user ID found!');
					alert('Missing User ID');
					return;
				}
				
				// Use native confirm for simplicity
				if (!confirm('Are you sure you want to delete this user from team?\n\nThis will remove the user from all team groups, but will NOT delete the user account.')) {
					return;
				}
				
				PersonManager.deletePerson(userId);
			});

		},

		deletePerson: function(userId) {
			console.log('[PersonManager] deletePerson called - User ID:', userId);
			
			if (!userId) {
				alert('Missing User ID');
				return;
			}
			
			// Try both DeletePerson and DeleteUser actions
			var requestData = {
				module: 'Teams',
				action: 'DeletePerson',
				record: userId,
				userid: userId,
				app: 'Management'
			};
			
			console.log('[PersonManager] Delete person request data:', requestData);
			
			app.request.post({
				url: 'index.php',
				data: requestData
			}).then(function(err, response){
				console.log('[PersonManager] Delete person response - err:', err, 'response:', response);
				
				if (err) {
					var errorMsg = err;
					if (typeof err === 'object' && err.message) {
						errorMsg = err.message;
					}
					console.error('[PersonManager] Delete person failed:', errorMsg);
					alert('Failed to delete user: ' + errorMsg);
					return;
				}
				
				// Check for success response
				if (!err && response && response.success) {
					alert('User deleted from team successfully');
					// Reload page to show updated list
					location.reload();
				} else {
					var errorMessage = 'Delete failed';
					if (response && response.error) {
						errorMessage = response.error.message || response.error;
					}
					console.error('[PersonManager] Delete person failed - unexpected response:', response);
					alert('Delete failed: ' + errorMessage);
				}
			}).catch(function(error){
				console.error('[PersonManager] Delete promise error:', error);
				alert('Failed to delete user: ' + (error.message || error));
			});
		}

	};

	// Initialize immediately when DOM is ready
	$(document).ready(function(){
		console.log('[PersonManager] READY');
		console.log('[PersonManager] Document ready, initializing...');
		PersonManager.init();
		console.log('[PersonManager] Initialization complete');
	});

	// Expose for reuse
	window.PersonManager = PersonManager;
})(jQuery);
