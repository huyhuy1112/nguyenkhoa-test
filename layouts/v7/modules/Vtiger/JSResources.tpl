{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*
********************************************************************************/
-->*}
{strip}
    <script type="text/javascript" src="{vresource_url('layouts/v7/lib/jquery/purl.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/lib/jquery/select2/select2.min.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/lib/jquery/jquery.class.min.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/lib/jquery/jquery-ui-1.12.0.custom/jquery-ui.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/lib/todc/js/popper.min.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/lib/todc/js/bootstrap.min.js')}"></script>
    <script type="text/javascript" src="{vresource_url('libraries/jquery/jstorage.min.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/lib/jquery/jquery-validation/jquery.validate.min.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/lib/jquery/jquery.slimscroll.min.js')}"></script>
    <script type="text/javascript" src="{vresource_url('libraries/jquery/jquery.ba-outside-events.min.js')}"></script>
	<script type="text/javascript" src="{vresource_url('libraries/jquery/defunkt-jquery-pjax/jquery.pjax.js')}"></script>
    <script type="text/javascript" src="{vresource_url('libraries/jquery/multiplefileupload/jquery_MultiFile.js')}"></script>
    <script type="text/javascript" src="{vresource_url('resources/jquery.additions.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/lib/bootstrap-notify/bootstrap-notify.min.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/lib/jquery/websockets/reconnecting-websocket.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/lib/jquery/jquery-play-sound/jquery.playSound.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/lib/jquery/malihu-custom-scrollbar/jquery.mousewheel.min.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/lib/jquery/malihu-custom-scrollbar/jquery.mCustomScrollbar.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/lib/jquery/autoComplete/jquery.textcomplete.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/lib/jquery/jquery.qtip.custom/jquery.qtip.js')}"></script>
    <script type="text/javascript" src="{vresource_url('libraries/jquery/jquery-visibility.min.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/lib/momentjs/moment.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/lib/jquery/daterangepicker/moment.min.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/lib/jquery/daterangepicker/jquery.daterangepicker.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/lib/jquery/jquery.timeago.js')}"></script>
    <script type="text/javascript" src="{vresource_url('libraries/jquery/ckeditor/ckeditor.js')}"></script>
    <script type="text/javascript" src="{vresource_url('libraries/jquery/ckeditor/adapters/jquery.js')}"></script>
	<script type='text/javascript' src="{vresource_url('layouts/v7/lib/anchorme_js/anchorme.min.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/Class.js')}"></script>
    <script type='text/javascript' src="{vresource_url('layouts/v7/resources/helper.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/resources/application.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/Utils.js')}"></script>
    <script type='text/javascript' src="{vresource_url('layouts/v7/modules/Vtiger/resources/validation.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/lib/bootbox/bootbox.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/Base.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/Vtiger.js')}"></script>
    {*
      Failsafe: after cache clear / JS errors, a stuck backdrop can block all clicks (even Login).
      This snippet runs very early on every page to ensure the UI remains interactive.
    *}
    <script type="text/javascript">
      (function () {
        function cleanupStuckOverlays() {
          try {
            var hasActiveModal = document.querySelector('.modal.in, .modal.show, .bootbox.modal, .fc-overlay-modal, .overlayDetail, .overlayEdit');
            var hasOverlayPage = document.querySelector('#overlayPage.in, #overlayPageContent.in, #overlayPageContent.fade.in');
            if (hasActiveModal || hasOverlayPage) return;

            document.querySelectorAll('.modal-backdrop').forEach(function (n) { n.parentNode && n.parentNode.removeChild(n); });
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';

            // Drawer/menu backdrops (modern shell)
            document.body.classList.remove('mk-dash-drawer-open');
            document.querySelectorAll('.mk-dash-drawer-backdrop').forEach(function (n) { n.parentNode && n.parentNode.removeChild(n); });

            var om = document.getElementById('app-menu');
            if (om) { om.classList.add('hide'); om.style.display = 'none'; om.style.visibility = 'hidden'; }

            var opc = document.getElementById('overlayPageContent');
            if (opc) { opc.classList.remove('in'); opc.style.display = ''; opc.style.visibility = ''; opc.style.opacity = ''; }
            var op = document.getElementById('overlayPage');
            if (op) { op.classList.remove('in'); op.style.display = ''; op.style.visibility = ''; op.style.opacity = ''; }
          } catch (e) {}
        }

        // Run immediately and keep trying briefly during boot.
        cleanupStuckOverlays();
        document.addEventListener('DOMContentLoaded', cleanupStuckOverlays);
        window.addEventListener('load', cleanupStuckOverlays);
        var tries = 0;
        var t = setInterval(function () {
          cleanupStuckOverlays();
          tries++;
          if (tries >= 12) clearInterval(t);
        }, 250);
      })();
    </script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/modules/Calendar/resources/TaskManagement.js')}"></script>
    <link rel="stylesheet" type="text/css" href="{vresource_url('layouts/v7/modules/Import/resources/ImportMkModern.css')}?mk_v=20260620_import_lux7" />
    <script type="text/javascript" src="{vresource_url('layouts/v7/modules/Import/resources/Import.js')}?v=20260620_import_lux19"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/modules/Emails/resources/EmailPreview.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/Base.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/modules/Google/resources/Settings.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/CkEditor.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/modules/Documents/resources/Documents.js')}"></script>
    <script type="text/javascript" src="{vresource_url('libraries/DOMPurify/dist/purify.min.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/ModernNotifications.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/ModernProfileDropdown.js')}"></script>

    {foreach key=index item=jsModel from=$SCRIPTS}
        <script type="{$jsModel->getType()}" src="{vresource_url($jsModel->getSrc())}"></script>
    {/foreach}

    <script type="text/javascript" src="{vresource_url('layouts/v7/resources/v7_client_compat.js')}"></script>
    <script type="text/javascript" src="{vresource_url('layouts/v7/modules/Vtiger/resources/MkReferencePopup.js')}?mk_v=20260617_refpopup4"></script>
    <!-- Added in the end since it should be after less file loaded -->
    <script type="text/javascript" src="{vresource_url('libraries/bootstrap/js/less.min.js')}"></script>

    <!-- Enable tracking pageload time -->
	<script type="text/javascript">
		var _REQSTARTTIME = "{$smarty.server.REQUEST_TIME}";
		{literal}jQuery(document).ready(function() { window._PAGEREADYAT = new Date(); });
		jQuery(window).load(function() {
			window._PAGELOADAT = new Date();
			window._PAGELOADREQSENT = false;
			// Transmit the information to server about page render time now.
			if (typeof _REQSTARTTIME != 'undefined') {
				// Work with time converting it to GMT (assuming _REQSTARTTIME set by server is also in GMT)
				var _PAGEREADYTIME = _PAGEREADYAT.getTime() / 1000.0; // seconds
				var _PAGELOADTIME = _PAGELOADAT.getTime() / 1000.0;    // seconds
				var data = { page_request: _REQSTARTTIME, page_ready: _PAGEREADYTIME, page_load: _PAGELOADTIME };
				data['page_xfer'] = (_PAGELOADTIME - _REQSTARTTIME).toFixed(3);
				data['client_tzoffset']= -1*_PAGELOADAT.getTimezoneOffset()*60;
				data['client_now'] = JSON.parse(JSON.stringify(new Date()));
				if (!window._PAGELOADREQSENT) {
					// To overcome duplicate firing on Chrome
					window._PAGELOADREQSENT = true;
				}
			}
		});
		// Online = đăng nhập: cập nhật last_seen mọi trang (không cần vào Teams) để hiển thị Online
		(function() {
			if (typeof _USERMETA !== 'undefined' && _USERMETA && _USERMETA.id) {
				function pingHeartbeat() {
					jQuery.ajax({ url: 'index.php', data: { module: 'Users', action: 'Heartbeat' }, type: 'GET', global: false });
				}
				pingHeartbeat();
				setInterval(pingHeartbeat, 60000);
			}
		})();{/literal}
	</script>
{/strip}
