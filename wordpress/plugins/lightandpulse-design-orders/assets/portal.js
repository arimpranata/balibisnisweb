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
    if (e.target.closest('[data-open-new-order]')) {
      var d = document.getElementById('new-order');
      if (d) { d.open = true; }
    }
  });
  if (location.hash === '#new-order' || location.search.indexOf('lpdo_form=') > -1) {
    var d = document.getElementById('new-order');
    if (d) { d.open = true; d.scrollIntoView(); }
  }
})();
