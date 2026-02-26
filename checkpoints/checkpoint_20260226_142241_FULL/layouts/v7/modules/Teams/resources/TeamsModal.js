(function($){
	$(document).ready(function(){
		var currentModal = null;

		// Expose buildModal for Group.js to use
		window.TeamsModal = window.TeamsModal || {};
		window.TeamsModal.buildModal = buildModal;

		function buildModal(html) {
			// Prevent multiple modals
			if (currentModal) {
				closeModal();
			}

			// Disable body scroll
			$('body').css('overflow', 'hidden');

			// Create overlay with dark backdrop (like QuickCreate Event)
			var $overlay = $('<div class="teams-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1050; opacity: 0; transition: opacity 0.3s;"></div>');
			var $container = $('<div class="teams-modal-container" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%) scale(0.9); background: #fff; border-radius: 4px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto; z-index: 1051; transition: transform 0.3s;"></div>');
			var $close = $('<button type="button" class="teams-modal-close" aria-label="Close">&times;</button>');
			
			$container.append($close).append(html);
			$overlay.append($container);
			$('body').append($overlay);
			
			currentModal = $overlay;

			// Initialize Select2 for user and group selectors
			function initSelect2($selector) {
				if ($selector.length > 0) {
					if (typeof vtUtils !== 'undefined' && typeof vtUtils.showSelect2ElementView === 'function') {
						vtUtils.showSelect2ElementView($selector, {
							placeholder: $selector.data('placeholder') || 'Select...',
							allowClear: false,
							closeOnSelect: false
						});
					} else if (typeof app !== 'undefined' && typeof app.showSelect2ElementView === 'function') {
						app.showSelect2ElementView($selector, {
							placeholder: $selector.data('placeholder') || 'Select...',
							allowClear: false,
							closeOnSelect: false
						});
					} else if (typeof jQuery.fn.select2 !== 'undefined') {
						$selector.select2({
							placeholder: $selector.data('placeholder') || 'Select...',
							allowClear: false,
							closeOnSelect: false,
							width: '100%'
						});
					}
				}
			}

			// Initialize Select2 only for group selector
			initSelect2($container.find('.teams-group-selector'));

			// Function to load users and populate checkbox list
			function loadUsersIntoCheckboxList(type, groupIds, allChecked) {
				var $checkboxList = $container.find('.teams-users-checkbox-list');
				$checkboxList.html('<div class="text-muted" style="padding: 10px; text-align: center;">Loading users...</div>');

				var params = {
					module: 'Teams',
					action: 'GetGroupUsers',
					type: type
				};
				if (type === 'groups' && groupIds && groupIds.length > 0) {
					params.groupids = groupIds;
				}

				app.request.get({url: 'index.php', data: params}).then(function(err, response){
					if (err || !response || !response.success) {
						$checkboxList.html('<div class="text-danger" style="padding: 10px;">Error loading users.</div>');
						return;
					}

					var users = response.users || [];
					if (users.length === 0) {
						$checkboxList.html('<div class="text-muted" style="padding: 10px; font-style: italic; text-align: center;">No users found.</div>');
						return;
					}

					// Build checkbox list HTML
					var html = '';
					users.forEach(function(user){
						var checked = allChecked ? 'checked' : '';
						var fullName = (user.full_name || (user.first_name + ' ' + user.last_name) || user.user_name).trim();
						html += '<div class="checkbox" style="margin: 8px 0;">';
						html += '<label>';
						html += '<input type="checkbox" name="userids[]" value="' + user.id + '" ' + checked + ' />';
						html += '<strong>' + (fullName || user.user_name) + '</strong>';
						html += ' <span class="text-muted">(' + user.user_name + ')</span>';
						html += '</label>';
						html += '</div>';
					});
					
					$checkboxList.html(html);
				});
			}

			// Handle Assign Method change (radio buttons) - these are presets to populate the checkbox list
			$container.find('.teams-assign-method').on('change', function(){
				var assignMethod = $(this).val();
				var $groupsField = $container.find('.teams-assign-groups-field');
				var $checkboxList = $container.find('.teams-users-checkbox-list');
				
				if (assignMethod === 'groups') {
					// Show group selector, load users when groups are selected
					$groupsField.show();
					$checkboxList.html('<div class="text-muted" style="padding: 10px; font-style: italic; text-align: center;">Select groups above to load their users...</div>');
					
					// Load users when groups change
					$container.find('.teams-group-selector').off('change.groups').on('change.groups', function(){
						var selectedGroups = $(this).val();
						if (selectedGroups && selectedGroups.length > 0) {
							loadUsersIntoCheckboxList('groups', selectedGroups, true); // All checked by default
						} else {
							$checkboxList.html('<div class="text-muted" style="padding: 10px; font-style: italic; text-align: center;">Select groups to load their users...</div>');
						}
					});
				} else if (assignMethod === 'users') {
					// Hide group selector, show all users (none checked initially)
					$groupsField.hide();
					loadUsersIntoCheckboxList('users', [], false); // None checked initially
				} else { // all
					// Hide group selector, load all users (all checked by default)
					$groupsField.hide();
					loadUsersIntoCheckboxList('all', [], true); // All checked by default
				}
			});


			// Trigger change on modal load to set initial state
			var $checkedMethod = $container.find('.teams-assign-method:checked');
			if ($checkedMethod.length > 0) {
				var method = $checkedMethod.val();
				$checkedMethod.trigger('change');
				// If ALL, load users immediately
				if (method === 'all') {
					loadUsersIntoCheckboxList('all', [], true);
				} else if (method === 'users') {
					loadUsersIntoCheckboxList('users', [], false);
				}
			}

			// Ensure all inputs are enabled and editable
			$container.find('input[type="text"], input[type="email"], input[type="password"], textarea').each(function(){
				$(this).prop('disabled', false).prop('readonly', false);
			});

			// Trigger animation by adding active class after a brief delay
			setTimeout(function() {
				$overlay.css('opacity', '1');
				$container.css('transform', 'translate(-50%, -50%) scale(1)');
				
				// Focus first input after animation (optional, but helps UX)
				setTimeout(function(){
					var $firstInput = $container.find('input[type="text"]:not([type="hidden"]), input[type="email"]').first();
					if ($firstInput.length > 0) {
						$firstInput.focus();
					}
				}, 50);
			}, 10);

			function closeModal() {
				if (!currentModal) return;
				
				var $modal = currentModal;
				currentModal = null;
				
				// Trigger exit animation
				$modal.removeClass('teams-overlay-active');
				$modal.find('.teams-modal-container').removeClass('teams-modal-container-active');
				
				// Remove after animation completes
				setTimeout(function() {
					$modal.remove();
					// Re-enable body scroll
					$('body').css('overflow', '');
				}, 200);
			}

			// Close button click
			$close.on('click', closeModal);
			
			// Click outside modal (on overlay) - ensure clicks on modal content don't close
			$overlay.on('click', function(e){
				// Only close if clicking directly on overlay, not on modal container or its children
				if (e.target === this) {
					closeModal();
				}
			});
			
			// Prevent modal container clicks from bubbling to overlay
			$container.on('click', function(e){
				e.stopPropagation();
			});

			// ESC key handler - only prevent ESC, allow all other keys for IME
			var escHandler = function(e) {
				// Only handle ESC key, don't prevent other keys (important for IME)
				if (e.keyCode === 27) { // ESC key
					e.preventDefault();
					e.stopPropagation();
					closeModal();
					$(document).off('keydown', escHandler);
				}
				// Do NOT preventDefault for other keys - this allows IME to work
			};
			$(document).on('keydown', escHandler);

			// Form submission - handle Add Person, Add Group, and Edit Group forms
			var $form = $container.find('#EditView, .js-teams-add-person-form');
			if ($form.length > 0) {
				// Remove any existing handlers to prevent duplicates
				$form.off('submit');
				$form.on('submit', function(e) {
					e.preventDefault();
					
					// Prevent submit loop
					if ($form.data('submitting')) {
						return false;
					}
					$form.data('submitting', true);
					
					var action = $form.find('input[name="action"]').val();
					
					// Handle SavePerson
					if (action === 'SavePerson') {
						console.log('[TeamsModal] Submitting Add Person');
						
						// Use app.request.post (Vtiger standard)
						app.request.post({
							url: 'index.php',
							data: $form.serialize()
						}).then(function(err, response) {
							if (err) {
								var errorMsg = err;
								if (typeof err === 'object' && err.message) {
									errorMsg = err.message;
								}
								app.helper.showErrorNotification({message: 'Save failed: ' + errorMsg});
								$form.data('submitting', false);
								return;
							}
							
							if (response && response.success) {
								closeModal();
								// Về lại trang Teams tab People sau khi save Add Person
								window.location.href = 'index.php?module=Teams&view=List&tab=people&app=Management';
							} else {
								var errorMsg = 'Save failed';
								if (response && response.error) {
									errorMsg = response.error.message || response.error;
								}
								app.helper.showErrorNotification({message: errorMsg});
								$form.data('submitting', false);
							}
						});
						return false;
					}
					
					// Handle SaveGroup (existing logic)
					var formData = $form.serializeFormData();
					var requestData = {
						module: 'Teams',
						action: 'SaveGroup',
						app: 'Management'
					};
					
					// Copy form data but exclude assign_method and assign_type (frontend-only helpers)
					for (var key in formData) {
						if (formData.hasOwnProperty(key)) {
							// Skip assign_method and assign_type - backend only uses userids[]
							if (key !== 'assign_method' && key !== 'assign_type' && key !== 'groupids') {
								requestData[key] = formData[key];
							}
						}
					}
					
					// Collect checked and unchecked user IDs
					var checkedUserIds = [];
					var allUserCheckboxes = $container.find('input[name="userids[]"]');
					
					allUserCheckboxes.each(function(){
						var $cb = $(this);
						var userId = parseInt($cb.val(), 10);
						if (userId > 0 && $cb.is(':checked')) {
							checkedUserIds.push(userId);
						}
					});
					
					// Check if "All Users" mode was used
					var assignMethod = $container.find('input[name="assign_method"]:checked').val();
					var isAllMode = (assignMethod === 'all');
					
					if (isAllMode && allUserCheckboxes.length > 0) {
						// All users mode - send all_users flag and excluded_users
						requestData.all_users = '1';
						var excludedUserIds = [];
						allUserCheckboxes.each(function(){
							var $cb = $(this);
							if (!$cb.is(':checked')) {
								var userId = parseInt($cb.val(), 10);
								if (userId > 0) {
									excludedUserIds.push(userId);
								}
							}
						});
						if (excludedUserIds.length > 0) {
							requestData.excluded_users = excludedUserIds;
						}
						requestData.userids = checkedUserIds;
					} else {
						// Direct selection mode - send only checked userids
						requestData.userids = checkedUserIds;
					}
					
					// Ensure at least one user is selected (only for SaveGroup)
					if (!requestData.userids || !Array.isArray(requestData.userids) || requestData.userids.length === 0) {
						app.helper.showErrorNotification({message: 'Please select at least one user'});
						$form.data('submitting', false);
						return false;
					}
					
					// For edit mode, ensure groupid and mode are included
					if ($form.find('input[name="groupid"]').length > 0) {
						requestData.groupid = $form.find('input[name="groupid"]').val();
						requestData.mode = 'edit';
					}
					
					// Use AppConnector for SaveGroup
					AppConnector.request({
						type: 'POST',
						data: requestData
					}).done(function(response) {
						if (response && response.success) {
							app.hideModalWindow();
							window.location.reload();
						} else {
							alert(response.error?.message || 'Save failed');
							$form.data('submitting', false);
						}
					}).fail(function() {
						alert('Request failed');
						$form.data('submitting', false);
					}).always(function() {
						// Reset lock after a delay
						setTimeout(function() {
							$form.data('submitting', false);
						}, 1000);
					});
					return false;
				});
			}
			
			// Ensure no other event handlers interfere with input
			// Remove any keydown/keypress handlers that might block IME
			$container.find('input, textarea').off('keydown.blocking keypress.blocking');
			
			// Allow all input events to propagate normally for IME support
			$container.find('input, textarea').on('keydown keypress keyup input compositionstart compositionupdate compositionend', function(e){
				// Allow all input events to work normally - don't prevent anything except ESC
				// This ensures Vietnamese IME and other input methods work correctly
			});

			// Cancel button click
			$container.find('[data-dismiss="modal"]').on('click', closeModal);
		}

		function loadModal(url, fallbackHref) {
			app.request.get({url: url}).then(function(err, data){
				if(err || !data){
					if (fallbackHref) window.location = fallbackHref;
					return;
				}
				buildModal(data);
			});
		}

		$(document).on('click', '.js-add-person', function(e){
			e.preventDefault();
			var href = $(this).attr('href');
			loadModal('index.php?module=Teams&view=People&mode=modal&app=Management', href);
		});

		$(document).on('click', '.js-add-group', function(e){
			e.preventDefault();
			var href = $(this).attr('href');
			loadModal('index.php?module=Teams&view=AddGroup&mode=modal&app=Management', href);
		});
	});
})(jQuery);
