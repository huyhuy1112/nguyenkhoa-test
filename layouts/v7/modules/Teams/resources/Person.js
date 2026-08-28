console.log('[PersonManager] JS file LOADED');

(function($){
	'use strict';

	var PersonManager = {
		init: function() {
			this.bindEvents();
		},

		bindEvents: function() {
			$(document).off('click.mkTeamsDelete', '.js-delete-person');
			$(document).on('click.mkTeamsDelete', '.js-delete-person', function(e){
				e.preventDefault();
				e.stopPropagation();
				e.stopImmediatePropagation();

				var $el = $(this);
				var userId = $el.data('userid') || $el.attr('data-userid');
				if (!userId) {
					alert('Missing User ID');
					return;
				}

				if (!confirm('Bạn có chắc muốn xoá tài khoản này?\n\nTài khoản sẽ bị vô hiệu hoá (không đăng nhập được).')) {
					return;
				}

				PersonManager.deletePerson(userId);
			});
		},

		deletePerson: function(userId) {
			if (!userId) {
				alert('Missing User ID');
				return;
			}

			app.request.post({
				url: 'index.php',
				data: {
					module: 'Teams',
					action: 'SuspendUser',
					userid: userId,
					record: userId,
					app: 'Management'
				}
			}).then(function(err, response){
				if (err) {
					var errorMsg = (typeof err === 'object' && err.message) ? err.message : err;
					alert('Không xoá được tài khoản: ' + errorMsg);
					return;
				}
				if (response && response.success) {
					location.reload();
					return;
				}
				var errorMessage = (response && response.error && (response.error.message || response.error)) || 'Delete failed';
				alert('Không xoá được tài khoản: ' + errorMessage);
			}).catch(function(error){
				alert('Không xoá được tài khoản: ' + (error.message || error));
			});
		}
	};

	$(document).ready(function(){
		PersonManager.init();
	});

	window.PersonManager = PersonManager;
})(jQuery);
