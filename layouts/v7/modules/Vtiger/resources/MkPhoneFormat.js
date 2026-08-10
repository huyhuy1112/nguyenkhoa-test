/**
 * Global phone display: digits stored raw, shown as "xxxx xxx xxx" (VN preferred).
 * e.g. 0906345551 → 0906 345 551
 * 11-digit strings like 09042242511 → 0904 224 2511 (group 4-3-rest, never leave stuck).
 */
(function (global) {
	"use strict";

	function digitsOnly(value) {
		return String(value == null ? "" : value).replace(/\D+/g, "");
	}

	/**
	 * Format for display. Prefer 10-digit VN: 4-3-3.
	 * Longer numbers stay grouped 4-3-rest so they never render as a single blob.
	 */
	function formatPhoneDisplay(value) {
		var raw = String(value == null ? "" : value).trim();
		if (!raw || raw === "—" || raw === "-" || raw === "N/A" || raw === "n/a") {
			return raw;
		}
		var digits = digitsOnly(raw);
		if (!digits) {
			return "";
		}
		// +84 / 84xxxxxxxxx → 0xxxxxxxxx
		if (digits.length === 11 && digits.indexOf("84") === 0) {
			digits = "0" + digits.slice(2);
		} else if (digits.length === 12 && digits.indexOf("840") === 0) {
			digits = "0" + digits.slice(3);
		} else if (digits.length === 12 && digits.indexOf("84") === 0) {
			digits = "0" + digits.slice(2);
		}
		if (digits.length === 10) {
			return digits.slice(0, 4) + " " + digits.slice(4, 7) + " " + digits.slice(7);
		}
		if (digits.length === 9) {
			return digits.slice(0, 3) + " " + digits.slice(3, 6) + " " + digits.slice(6);
		}
		if (digits.length === 8) {
			return digits.slice(0, 4) + " " + digits.slice(4);
		}
		// 11+ (or other): keep first 10 as 4-3-3, append remaining digits after a space
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
		// 1–7 digits: light grouping
		if (digits.length >= 4) {
			return digits.slice(0, 4) + (digits.length > 4 ? " " + digits.slice(4) : "");
		}
		return digits;
	}

	function formatPhoneInput(value) {
		var digits = digitsOnly(value);
		if (digits.length > 10 && digits.indexOf("84") === 0) {
			digits = "0" + digits.slice(2);
		}
		// Input caps at 10 local digits for entry fields; list display can still format longer
		digits = digits.slice(0, 10);
		return formatPhoneDisplay(digits);
	}

	function looksLikePhoneField(el) {
		if (!el || el.nodeType !== 1) {
			return false;
		}
		if (el.getAttribute("data-field-type") === "phone") {
			return true;
		}
		if (el.classList) {
			if (
				el.classList.contains("mk-leads-td--phone") ||
				el.classList.contains("mk-qt-col-phone") ||
				el.classList.contains("mk-so-col-phone") ||
				el.classList.contains("mk-ref-phone") ||
				el.classList.contains("phoneField")
			) {
				return true;
			}
		}
		var name = String(
			el.getAttribute("name") ||
				el.getAttribute("data-field") ||
				el.getAttribute("data-name") ||
				""
		).toLowerCase();
		if (/(^|_)(phone|mobile|fax|sdt)(_|$)/.test(name) || name === "mk_list_phone" || name === "mk_customer_phone") {
			return true;
		}
		if (el.tagName === "INPUT" && String(el.getAttribute("type") || "").toLowerCase() === "tel") {
			return true;
		}
		return false;
	}

	function formatTextNodePhone(text) {
		var raw = String(text || "").trim();
		if (!raw || raw === "—" || raw === "-") {
			return null;
		}
		// Pure phone-like: digits with optional + and separators
		if (!/^\+?\d[\d\s\-().]{6,}\d$/.test(raw) && !/^\d{8,12}$/.test(raw)) {
			return null;
		}
		var formatted = formatPhoneDisplay(raw);
		return formatted && formatted !== raw ? formatted : null;
	}

	function setLeafText(el, text) {
		if (!el) {
			return;
		}
		if (el.childNodes.length === 1 && el.childNodes[0].nodeType === 3) {
			el.childNodes[0].nodeValue = text;
			return;
		}
		if (!el.children || el.children.length === 0) {
			el.textContent = text;
		}
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
		// Nested list/detail: td[data-field-type=phone] > .fieldValue > .value
		var valueEl =
			el.querySelector &&
			(el.querySelector(":scope > .fieldValue > .value") ||
				el.querySelector(".fieldValue .value") ||
				el.querySelector(".value") ||
				el.querySelector(".mk-leads-inline-edit") ||
				el.querySelector("a.phoneField") ||
				el.querySelector("a"));
		if (valueEl) {
			var leaf = valueEl;
			// Prefer deepest single text
			while (leaf.children && leaf.children.length === 1 && leaf.children[0].tagName) {
				leaf = leaf.children[0];
			}
			var t = String(leaf.textContent || "").trim();
			var formatted = formatTextNodePhone(t) || (formatPhoneDisplay(t) !== t ? formatPhoneDisplay(t) : null);
			if (formatted) {
				setLeafText(leaf, formatted);
			}
			return;
		}
		if (el.childNodes.length === 1 && el.childNodes[0].nodeType === 3) {
			var formattedPlain = formatTextNodePhone(el.textContent);
			if (formattedPlain) {
				el.textContent = formattedPlain;
			}
		}
	}

	function formatPhonesIn(root) {
		var scope = root && root.querySelectorAll ? root : document;
		var nodes = scope.querySelectorAll(
			[
				'[data-field-type="phone"]',
				'[data-name="mk_list_phone"]',
				'[data-name="mk_customer_phone"]',
				'[data-field="phone"]',
				'[data-field="mobile"]',
				".mk-leads-td--phone",
				".mk-qt-col-phone",
				".mk-so-col-phone",
				"td.listViewEntryValue[data-field-type=phone]",
				'input[type="tel"]',
				'input[name*="phone"]',
				'input[name*="mobile"]',
				'input[name="mk_customer_phone"]',
				".mk-ref-phone",
				"a.phoneField",
				".phoneField",
			].join(", ")
		);
		for (var i = 0; i < nodes.length; i++) {
			formatElementPhoneText(nodes[i]);
		}
		// Popover / free-text rows labeled SĐT
		var popRows = scope.querySelectorAll
			? scope.querySelectorAll(".mk-qt-customer-info-pop__row")
			: [];
		for (var j = 0; j < popRows.length; j++) {
			var k = popRows[j].querySelector(".mk-qt-customer-info-pop__k");
			var v = popRows[j].querySelector(".mk-qt-customer-info-pop__v");
			if (!k || !v) {
				continue;
			}
			if (!/sđt|sdt|phone|điện thoại/i.test(k.textContent || "")) {
				continue;
			}
			var pv = String(v.textContent || "").trim();
			var pf = formatPhoneDisplay(pv);
			if (pf && pf !== pv) {
				v.textContent = pf;
			}
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
