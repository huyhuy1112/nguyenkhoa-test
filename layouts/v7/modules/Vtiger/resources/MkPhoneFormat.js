/**
 * Global phone display: digits stored raw, shown as "xxxx xxx xxx" (10-digit VN).
 */
(function (global) {
	"use strict";

	function digitsOnly(value) {
		return String(value == null ? "" : value).replace(/\D+/g, "");
	}

	/**
	 * Format for display. Prefer 10-digit VN: 4-3-3.
	 * Other lengths get light grouping without inventing digits.
	 */
	function formatPhoneDisplay(value) {
		var digits = digitsOnly(value);
		if (!digits) {
			return "";
		}
		if (digits.length === 10) {
			return digits.slice(0, 4) + " " + digits.slice(4, 7) + " " + digits.slice(7);
		}
		if (digits.length === 11 && digits.charAt(0) === "0") {
			return digits.slice(0, 4) + " " + digits.slice(4, 7) + " " + digits.slice(7);
		}
		if (digits.length === 9) {
			return digits.slice(0, 3) + " " + digits.slice(3, 6) + " " + digits.slice(6);
		}
		if (digits.length > 10) {
			return (
				digits.slice(0, 4) +
				" " +
				digits.slice(4, 7) +
				" " +
				digits.slice(7, 10) +
				(digits.length > 10 ? " " + digits.slice(10) : "")
			);
		}
		return digits;
	}

	function formatPhoneInput(value) {
		var digits = digitsOnly(value).slice(0, 11);
		return formatPhoneDisplay(digits);
	}

	function looksLikePhoneField(el) {
		if (!el || el.nodeType !== 1) {
			return false;
		}
		if (el.getAttribute("data-field-type") === "phone") {
			return true;
		}
		if (el.classList && el.classList.contains("mk-leads-td--phone")) {
			return true;
		}
		var name = String(el.getAttribute("name") || el.getAttribute("data-field") || "").toLowerCase();
		if (/(^|_)(phone|mobile|fax)(_|$)/.test(name)) {
			return true;
		}
		if (el.tagName === "INPUT" && String(el.getAttribute("type") || "").toLowerCase() === "tel") {
			return true;
		}
		return false;
	}

	function formatTextNodePhone(text) {
		var raw = String(text || "").trim();
		if (!/^\+?\d[\d\s\-.]{7,}\d$/.test(raw)) {
			return null;
		}
		var formatted = formatPhoneDisplay(raw);
		return formatted && formatted !== raw ? formatted : null;
	}

	function formatElementPhoneText(el) {
		if (!el || !looksLikePhoneField(el)) {
			return;
		}
		if (el.tagName === "INPUT" || el.tagName === "TEXTAREA") {
			var next = formatPhoneInput(el.value);
			if (next && next !== el.value) {
				el.value = next;
			}
			return;
		}
		if (el.children && el.children.length) {
			var btn = el.querySelector(".mk-leads-inline-edit, a, button, span");
			if (btn && btn.childNodes.length === 1 && btn.childNodes[0].nodeType === 3) {
				var formattedChild = formatTextNodePhone(btn.textContent);
				if (formattedChild) {
					btn.textContent = formattedChild;
				}
				return;
			}
		}
		if (el.childNodes.length === 1 && el.childNodes[0].nodeType === 3) {
			var formatted = formatTextNodePhone(el.textContent);
			if (formatted) {
				el.textContent = formatted;
			}
		}
	}

	function formatPhonesIn(root) {
		var scope = root && root.querySelectorAll ? root : document;
		var nodes = scope.querySelectorAll(
			'[data-field-type="phone"], .mk-leads-td--phone, td.listViewEntryValue[data-field-type="phone"], input[type="tel"], input[name*="phone"], input[name*="mobile"], .mk-ref-phone'
		);
		for (var i = 0; i < nodes.length; i++) {
			formatElementPhoneText(nodes[i]);
		}
	}

	function bindLiveInputFormatting(root) {
		var scope = root || document;
		if (!scope.addEventListener) {
			return;
		}
		scope.addEventListener(
			"blur",
			function (e) {
				var t = e.target;
				if (!t || !looksLikePhoneField(t)) {
					return;
				}
				if (t.tagName === "INPUT" || t.tagName === "TEXTAREA") {
					t.value = formatPhoneInput(t.value);
				}
			},
			true
		);
		scope.addEventListener(
			"input",
			function (e) {
				var t = e.target;
				if (!t || !looksLikePhoneField(t)) {
					return;
				}
				if (t.tagName !== "INPUT" && t.tagName !== "TEXTAREA") {
					return;
				}
				var start = t.selectionStart;
				var before = t.value;
				var next = formatPhoneInput(before);
				if (next === before) {
					return;
				}
				t.value = next;
				if (typeof start === "number") {
					var delta = next.length - before.length;
					var pos = Math.max(0, start + delta);
					try {
						t.setSelectionRange(pos, pos);
					} catch (ignore) {}
				}
			},
			true
		);
	}

	function observeDom() {
		if (!global.MutationObserver || !document.body) {
			return;
		}
		var timer = null;
		var obs = new MutationObserver(function () {
			if (timer) {
				clearTimeout(timer);
			}
			timer = setTimeout(function () {
				formatPhonesIn(document);
			}, 80);
		});
		obs.observe(document.body, { childList: true, subtree: true });
	}

	function boot() {
		formatPhonesIn(document);
		bindLiveInputFormatting(document);
		observeDom();
	}

	var api = {
		digitsOnly: digitsOnly,
		format: formatPhoneDisplay,
		formatInput: formatPhoneInput,
		formatIn: formatPhonesIn,
		boot: boot,
	};

	global.MkPhoneFormat = api;

	if (document.readyState === "loading") {
		document.addEventListener("DOMContentLoaded", boot);
	} else {
		boot();
	}
})(window);
