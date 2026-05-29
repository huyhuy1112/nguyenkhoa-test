/**
 * B-ACE CRM theme manager — light (default) / dark via data-theme on <html>.
 * Persists to localStorage; syncs profile dropdown toggle.
 */
(function (window, document) {
	'use strict';

	var STORAGE_KEY = 'mk-crm-theme';
	var THEME_DARK = 'dark';
	var THEME_LIGHT = 'light';

	function getStoredTheme() {
		try {
			var t = localStorage.getItem(STORAGE_KEY);
			return t === THEME_DARK ? THEME_DARK : THEME_LIGHT;
		} catch (e) {
			return THEME_LIGHT;
		}
	}

	function setStoredTheme(theme) {
		try {
			localStorage.setItem(STORAGE_KEY, theme);
		} catch (e) { /* ignore */ }
	}

	function applyTheme(theme, options) {
		options = options || {};
		var root = document.documentElement;
		var isDark = theme === THEME_DARK;

		if (isDark) {
			root.setAttribute('data-theme', THEME_DARK);
			root.classList.add('dark-mode');
		} else {
			root.removeAttribute('data-theme');
			root.classList.remove('dark-mode');
		}

		if (document.body) {
			document.body.classList.toggle('dark-mode', isDark);
		}

		setStoredTheme(theme);
		syncToggleUi(isDark);

		if (!options.silent) {
			try {
				window.dispatchEvent(
					new CustomEvent('mk:theme-change', { detail: { theme: theme } })
				);
			} catch (e) {
				/* IE fallback omitted */
			}
			if (window.jQuery) {
				window.jQuery(document).trigger('mk:theme-change', [theme]);
			}
		}
	}

	function syncToggleUi(isDark) {
		var btn = document.getElementById('modern-profile-dark-toggle');
		if (!btn) return;
		btn.classList.toggle('modern-profile-dark-toggle--on', isDark);
		btn.setAttribute('aria-checked', isDark ? 'true' : 'false');
		btn.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
	}

	function withTransition(fn) {
		var root = document.documentElement;
		root.classList.add('mk-theme-transition');
		fn();
		window.setTimeout(function () {
			root.classList.remove('mk-theme-transition');
		}, 280);
	}

	function getTheme() {
		return document.documentElement.getAttribute('data-theme') === THEME_DARK
			? THEME_DARK
			: THEME_LIGHT;
	}

	function setTheme(theme) {
		withTransition(function () {
			applyTheme(theme === THEME_DARK ? THEME_DARK : THEME_LIGHT);
		});
	}

	function toggleTheme() {
		setTheme(getTheme() === THEME_DARK ? THEME_LIGHT : THEME_DARK);
	}

	function initFromStorage() {
		applyTheme(getStoredTheme(), { silent: true });
	}

	var toggleBound = false;

	function bindToggle() {
		if (toggleBound) return;
		toggleBound = true;
		document.addEventListener('click', function (ev) {
			var target = ev.target;
			if (!target || !target.closest) return;
			var btn = target.closest('#modern-profile-dark-toggle');
			if (!btn) return;
			ev.preventDefault();
			ev.stopPropagation();
			toggleTheme();
		}, true);
	}

	window.MkTheme = {
		STORAGE_KEY: STORAGE_KEY,
		THEME_DARK: THEME_DARK,
		THEME_LIGHT: THEME_LIGHT,
		getTheme: getTheme,
		setTheme: setTheme,
		toggleTheme: toggleTheme,
		applyTheme: applyTheme,
		init: function () {
			initFromStorage();
			bindToggle();
		},
		isDark: function () {
			return getTheme() === THEME_DARK;
		},
	};

	/* Apply before paint when script loads in <head> */
	initFromStorage();

	/**
	 * Re-append theme CSS at end of <head> when module PreProcess injects styles after Header.
	 * DashboardSidebar.tpl also includes MkThemeStylesLast.tpl; this covers edge pages.
	 */
	var THEME_CSS_PARTS = [
		'MkThemeTokens.css',
		'MkThemeDark.css',
		'MkThemeDarkModules.css',
		'MkThemeDarkGlobal.css',
		'MkThemeDarkMarketing.css',
		'MkThemeDarkSales.css',
		'MkThemeDarkEdit.css'
	];
	var THEME_CACHE_VER = 'dark_global_v18';

	function removeInjectedDarkStyles() {
		document.querySelectorAll('link[data-mk-theme-injected="1"]').forEach(function (el) {
			if (el.parentNode) {
				el.parentNode.removeChild(el);
			}
		});
	}

	function collectThemeStyleLinks() {
		var links = [];
		THEME_CSS_PARTS.forEach(function (part) {
			document.querySelectorAll('link[rel="stylesheet"][href*="' + part + '"]').forEach(function (link) {
				if (links.indexOf(link) === -1) {
					links.push(link);
				}
			});
		});
		document.querySelectorAll('link[data-mk-theme-injected="1"]').forEach(function (link) {
			if (links.indexOf(link) === -1) {
				links.push(link);
			}
		});
		return links;
	}

	function injectThemeStyleLinks() {
		var ref = document.querySelector('link[href*="layouts/v7/"]');
		if (!ref || !ref.href) {
			return [];
		}
		var root = ref.href.replace(/layouts\/v7\/.*$/, '');
		var paths = [
			'layouts/v7/modules/Vtiger/resources/MkThemeTokens.css',
			'layouts/v7/modules/Vtiger/resources/MkThemeDark.css',
			'layouts/v7/modules/Vtiger/resources/MkThemeDarkModules.css',
			'layouts/v7/modules/Vtiger/resources/MkThemeDarkGlobal.css',
			'layouts/v7/modules/Vtiger/resources/MkThemeDarkMarketing.css',
			'layouts/v7/modules/Vtiger/resources/MkThemeDarkSales.css',
			'layouts/v7/modules/Vtiger/resources/MkThemeDarkEdit.css'
		];
		var created = [];
		paths.forEach(function (path, idx) {
			var link = document.createElement('link');
			link.rel = 'stylesheet';
			link.type = 'text/css';
			link.media = 'screen';
			link.href = root + path + '?mk_v=' + THEME_CACHE_VER;
			link.setAttribute('data-mk-theme-injected', '1');
			if (idx === paths.length - 1) {
				link.id = 'mk-theme-styles-last';
			}
			created.push(link);
		});
		var anchor = null;
		document.querySelectorAll('link[rel="stylesheet"]').forEach(function (s) {
			if (created.indexOf(s) === -1) {
				anchor = s;
			}
		});
		created.forEach(function (link) {
			anchor = insertThemeLinkAfter(anchor, link);
		});
		return created;
	}

	/** Insert theme link immediately after anchor (keeps theme bundle order). */
	function insertThemeLinkAfter(anchor, link) {
		if (anchor && anchor.parentNode) {
			anchor.parentNode.insertBefore(link, anchor.nextSibling);
			return link;
		}
		document.body.appendChild(link);
		return link;
	}

	/**
	 * Move theme CSS after the last stylesheet in the document (head + body).
	 * Module PreProcess injects *List.css in <body>; appendChild to <head> leaves them winning the cascade.
	 */
	function ensureDarkStylesLast() {
		if (getTheme() !== THEME_DARK) {
			removeInjectedDarkStyles();
			return;
		}
		var links = collectThemeStyleLinks();
		if (!links.length) {
			links = injectThemeStyleLinks();
		}
		if (!links.length) {
			return;
		}
		var allStyles = document.querySelectorAll('link[rel="stylesheet"]');
		var lastNonTheme = null;
		var i;
		for (i = allStyles.length - 1; i >= 0; i -= 1) {
			if (links.indexOf(allStyles[i]) === -1) {
				lastNonTheme = allStyles[i];
				break;
			}
		}
		links.forEach(function (link) {
			link.setAttribute('data-mk-theme-injected', '1');
			if (link.href && link.href.indexOf('MkThemeDarkEdit.css') !== -1) {
				link.id = 'mk-theme-styles-last';
			}
			lastNonTheme = insertThemeLinkAfter(lastNonTheme, link);
		});
	}

	function initThemeUi() {
		applyTheme(getStoredTheme(), { silent: true });
		bindToggle();
		ensureDarkStylesLast();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initThemeUi);
	} else {
		initThemeUi();
	}

	window.addEventListener('mk:theme-change', function () {
		window.setTimeout(ensureDarkStylesLast, 0);
	});

	window.addEventListener('load', function () {
		window.setTimeout(ensureDarkStylesLast, 0);
		window.setTimeout(ensureDarkStylesLast, 150);
	});
})(window, document);
