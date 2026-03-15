(function () {
  // Wait for DOM ready
  function whenReady(fn) {
    if (document.readyState !== 'loading') { fn(); }
    else { document.addEventListener('DOMContentLoaded', fn, { once: true }); }
  }

  whenReady(function () {
    var root = document.querySelector('#tgconsult-root');
    if (!root) return;

    var win  = root.querySelector('#tgc-win');
    var btn  = root.querySelector('#tgc-btn');
    var input = root.querySelector('#tgc-form input[name="text"], #tgc-form textarea, #tgc-input');

    function openWin() {
      if (!win) return;
      win.removeAttribute('hidden');
      try { if (input && typeof input.focus === 'function') { input.focus(); } } catch (e) {}
      try { window.dispatchEvent(new Event('tgc:focused')); } catch (e) {}
    }
    function closeWin() { if (win) { win.setAttribute('hidden', ''); } }
    function toggleWin() { if (win) { (win.hasAttribute('hidden') ? openWin() : closeWin()); } }
    function isOpen() { return win && !win.hasAttribute('hidden'); }

    // Respect "hide_button" setting
    try {
      var cfg = window.TGCONSULT_CFG || {};
      if (cfg.hide_button && btn) { btn.style.display = 'none'; }
    } catch (e) {}

    // Public API
    window.TGConsult = window.TGConsult || {};
    window.TGConsult.open   = openWin;
    window.TGConsult.close  = closeWin;
    window.TGConsult.toggle = toggleWin;
    window.TGConsult.isOpen = isOpen;

    // Declarative triggers
    document.addEventListener('click', function (ev) {
      var el = ev.target && ev.target.closest && ev.target.closest('[data-tgc-open], .js-tgc-open');
      if (!el) return;
      if (el.tagName === 'A') { ev.preventDefault(); }
      openWin();
    });

    // Custom events
    window.addEventListener('tgc:open', openWin);
    window.addEventListener('tgc:close', closeWin);
    window.addEventListener('tgc:toggle', toggleWin);
  });
})();
