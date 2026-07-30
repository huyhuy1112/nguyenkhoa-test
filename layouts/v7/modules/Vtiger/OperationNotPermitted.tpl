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
<div style="margin:0 auto;width: 50em;">
	<table border='0' cellpadding='5' cellspacing='0' height='600px' width="700px">
	<tr><td align='center'>
		<div style='border: 3px solid rgb(153, 153, 153); background-color: rgb(255, 255, 255); width: 80%; position: relative; z-index: 100000020;'>

		<table border='0' cellpadding='5' cellspacing='0' width='98%'>
		<tr>
			<td rowspan='2' width='11%'><img src="{vimage_path('denied.gif')}" ></td>
			<td style='border-bottom: 1px solid rgb(204, 204, 204);' nowrap='nowrap' width='70%'>
				<span class='genHeaderSmall'>{if !empty($ERROR_MESSAGE)}{$ERROR_MESSAGE|escape}{else}{vtranslate($MESSAGE)}{/if}</span></td>
		</tr>
		<tr>
			<td class='small' align='right' nowrap='nowrap'>
				<a href='javascript:window.history.back();'>{vtranslate('LBL_GO_BACK')}</a><br>
			</td>
		</tr>
		</table>
		</div>
	</td></tr>
	</table>
</div>
<script type="text/javascript">
(function () {
	try {
		var msgNode = document.querySelector('.genHeaderSmall');
		var message = msgNode ? (msgNode.textContent || msgNode.innerText || '') : '';
		message = String(message || '').trim();
		if (!message) {
			return;
		}
		if (window.app && app.helper && typeof app.helper.showErrorNotification === 'function') {
			app.helper.showErrorNotification({ message: message });
		} else if (window.parent && window.parent.app && window.parent.app.helper && typeof window.parent.app.helper.showErrorNotification === 'function') {
			window.parent.app.helper.showErrorNotification({ message: message });
		} else {
			window.alert(message);
		}
		if (window.history && window.history.length > 1) {
			setTimeout(function () {
				window.history.back();
			}, 150);
		}
	} catch (e) {
		/* ignore */
	}
})();
</script>