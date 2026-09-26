/**
 * Turn leftover HTML codes (&atilde;, &amp;acirc;, &#259;) into real letters
 * on every CRM page, then keep later renders clean.
 */
(function (global) {
  "use strict";

  var ENTITY = /&(?:#\d+|#x[\da-f]+|[a-z][a-z0-9]+);/i;
  var skipTag = { SCRIPT: 1, STYLE: 1, NOSCRIPT: 1, CODE: 1, PRE: 1 };

  function decode(s) {
    var str = String(s == null ? "" : s);
    if (!str || str.indexOf("&") < 0 || !ENTITY.test(str)) return str;
    var el = document.createElement("textarea");
    var i;
    for (i = 0; i < 5; i++) {
      el.innerHTML = str;
      var next = el.value;
      if (next === str) break;
      str = next;
      if (str.indexOf("&") < 0 || !ENTITY.test(str)) break;
    }
    return str;
  }

  function esc(s) {
    return decode(s)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function shouldSkip(el) {
    if (!el || !el.nodeName) return true;
    if (skipTag[el.nodeName]) return true;
    if (el.isContentEditable && el === document.activeElement) return true;
    return false;
  }

  function fixText(node) {
    if (!node || node.nodeType !== 3) return;
    var parent = node.parentNode;
    if (shouldSkip(parent)) return;
    var raw = node.nodeValue;
    if (!raw || raw.indexOf("&") < 0 || !ENTITY.test(raw)) return;
    var next = decode(raw);
    if (next !== raw) node.nodeValue = next;
  }

  function fixControl(el) {
    if (!el || (el.nodeName !== "INPUT" && el.nodeName !== "TEXTAREA")) return;
    if (el === document.activeElement) return;
    var type = (el.type || "").toLowerCase();
    if (type === "hidden" || type === "password" || type === "file" || type === "checkbox" || type === "radio") return;
    var raw = el.value;
    if (!raw || raw.indexOf("&") < 0 || !ENTITY.test(raw)) return;
    var next = decode(raw);
    if (next !== raw) el.value = next;
  }

  function walk(root) {
    if (!root) return;
    if (root.nodeType === 3) {
      fixText(root);
      return;
    }
    if (root.nodeType !== 1) return;
    if (shouldSkip(root)) return;
    fixControl(root);
    var nodes = root.childNodes;
    var i;
    for (i = 0; i < nodes.length; i++) walk(nodes[i]);
  }

  var queued = false;
  function schedule(root) {
    var target = root && root.nodeType ? root : document.body;
    if (!target) return;
    if (queued) return;
    queued = true;
    var run = function () {
      queued = false;
      if (document.body) walk(document.body);
    };
    if (global.requestAnimationFrame) global.requestAnimationFrame(run);
    else setTimeout(run, 16);
  }

  function start() {
    if (!document.body) return;
    walk(document.body);
    var obs = new MutationObserver(function (muts) {
      var i;
      var j;
      for (i = 0; i < muts.length; i++) {
        var m = muts[i];
        if (m.type === "characterData") fixText(m.target);
        else if (m.addedNodes) {
          for (j = 0; j < m.addedNodes.length; j++) walk(m.addedNodes[j]);
        }
      }
    });
    obs.observe(document.body, { subtree: true, childList: true, characterData: true });
  }

  global.mkDecodeHtml = decode;
  global.mkEscHtml = esc;

  if (document.body) start();
  else document.addEventListener("DOMContentLoaded", start);
})(window);
