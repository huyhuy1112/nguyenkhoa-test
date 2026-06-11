{* Expose field metadata for inline edit on SALES dashboard-shell pages (no stock ModuleHeader). *}
{strip}
{if isset($FIELDS_INFO) && $FIELDS_INFO neq null && $FIELDS_INFO neq ''}
	<script type="text/javascript">
		var uimeta = (function () {
			var fieldInfo = {$FIELDS_INFO};
			return {
				field: {
					get: function (name, property) {
						if (name && property === undefined) {
							return fieldInfo[name];
						}
						if (name && property) {
							return fieldInfo[name][property];
						}
					},
					isMandatory: function (name) {
						if (fieldInfo[name]) {
							return fieldInfo[name].mandatory;
						}
						return false;
					},
					getType: function (name) {
						if (fieldInfo[name]) {
							return fieldInfo[name].type;
						}
						return false;
					}
				}
			};
		})();
	</script>
{/if}
{/strip}
