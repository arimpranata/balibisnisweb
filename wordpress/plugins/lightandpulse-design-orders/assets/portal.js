(function () {
  'use strict';
  function toast(text) {
    var t = document.createElement('div');
    t.className = 'lpdo-copied';
    t.setAttribute('role', 'status');
    t.textContent = text;
    document.body.appendChild(t);
    setTimeout(function () { t.remove(); }, 1600);
  }
  document.addEventListener('click', function (e) {
    var copy = e.target.closest('[data-copy]');
    if (copy) {
      var value = copy.getAttribute('data-copy');
      var done = function () { toast('Disalin: ' + (value.length > 40 ? value.slice(0, 40) + '…' : value)); };
      if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(value).then(done, function () { window.prompt('Salin:', value); });
      } else {
        window.prompt('Salin:', value);
      }
    }
    var opener = e.target.closest('[data-dialog]');
    if (opener) {
      var dlg = document.getElementById(opener.getAttribute('data-dialog'));
      if (dlg && dlg.showModal) { dlg.showModal(); }
    }
    if (e.target.closest('[data-close]')) {
      var open = e.target.closest('dialog');
      if (open) { open.close(); }
    }
    if (e.target.matches('dialog.lpdo-drawer')) { e.target.close(); } // click on the backdrop
    if (e.target.closest('[data-open-new-order]')) {
      var d = document.getElementById('new-order');
      if (d) { d.open = true; }
    }
  });
  // Shared links: list or grid, remembered per browser.
  var view = 'list';
  try { view = localStorage.getItem('lpdo-view') || 'list'; } catch (err) { /* storage blocked */ }
  function setView(v) {
    document.querySelectorAll('[data-views]').forEach(function (ul) { ul.classList.toggle('is-grid', v === 'grid'); });
    document.querySelectorAll('[data-view]').forEach(function (b) { b.setAttribute('aria-pressed', String(b.getAttribute('data-view') === v)); });
  }
  setView(view);
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-view]');
    if (!b) { return; }
    setView(b.getAttribute('data-view'));
    try { localStorage.setItem('lpdo-view', b.getAttribute('data-view')); } catch (err) { /* storage blocked */ }
  });
  if (/[?&]lpdo=profile-error/.test(location.search)) {
    var kit = document.getElementById('lpdo-kit');
    if (kit && kit.showModal) { kit.showModal(); }
  }
  if (location.hash === '#new-order' || location.search.indexOf('lpdo_form=') > -1 || location.search.indexOf('lpdo_copy=') > -1) {
    var d = document.getElementById('new-order');
    if (d) { d.open = true; d.scrollIntoView(); }
  }
})();
