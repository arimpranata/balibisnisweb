(function () {
  'use strict';
  // Fields that only matter for certain answers: field key -> [controlling field, value].
  var RULES = {
    poster_size_custom: ['poster_size', 'custom'], menu_size_custom: ['menu_size', 'custom'],
    flyer_size_custom: ['flyer_size', 'custom'], card_size_custom: ['card_size', 'custom'],
    languages_other: ['languages', 'other'], poster_qr_url: ['poster_qr', 'yes'],
    backdrop_hashtag: ['backdrop_elements', 'hashtag'], backdrop_sponsor_logos: ['backdrop_elements', 'sponsors']
  };

  document.querySelectorAll('.lpdo-form').forEach(function (form) {
    // Hidden parts are disabled, so they are neither validated nor sent.
    function setActive(el, on) {
      el.classList.toggle('is-hidden', !on);
      el.querySelectorAll('input, select, textarea').forEach(function (i) {
        i.disabled = !on;
        if (i.dataset.required) { i.required = on; }
      });
      if (el.matches('input, select, textarea')) { el.disabled = !on; }
    }

    function valueOf(key) {
      var els = form.querySelectorAll('[name="lpdo[' + key + ']"], [name="lpdo[' + key + '][]"]');
      var vals = [];
      els.forEach(function (e) {
        if ((e.type === 'checkbox' || e.type === 'radio') ? e.checked : e.value) { vals.push(e.value); }
      });
      return vals;
    }

    function update() {
      var checked = form.querySelector('input[name="lpdo_type"]:checked');
      var type = checked ? checked.value : '';
      form.querySelectorAll('[data-types]').forEach(function (el) {
        setActive(el, type !== '' && (' ' + el.dataset.types + ' ').indexOf(' ' + type + ' ') > -1);
      });
      var pick = form.querySelector('.lpdo-pick-first');
      if (pick) { pick.hidden = type !== ''; }
      Object.keys(RULES).forEach(function (key) {
        var field = form.querySelector('#lpdo-' + key);
        var wrap = field && field.closest('.lpdo-field');
        if (!wrap || wrap.closest('.is-hidden') && !wrap.classList.contains('is-hidden')) { return; }
        setActive(wrap, valueOf(RULES[key][0]).indexOf(RULES[key][1]) > -1);
      });
    }

    form.addEventListener('change', update);
    update();

    // Warn about files that are too big before uploading them.
    var maxBytes = (parseInt(form.dataset.maxMb, 10) || 10) * 1048576;
    form.addEventListener('change', function (e) {
      if (e.target.type !== 'file') { return; }
      var old = e.target.parentNode.querySelector('.lpdo-file-warning');
      if (old) { old.remove(); }
      var big = Array.prototype.filter.call(e.target.files, function (f) { return f.size > maxBytes; });
      if (big.length) {
        var w = document.createElement('small');
        w.className = 'lpdo-file-warning';
        w.textContent = big.map(function (f) { return f.name; }).join(', ') + ' — maks. ' + form.dataset.maxMb + ' MB';
        e.target.after(w);
        e.target.value = '';
      }
    });

    form.addEventListener('submit', function () {
      var btn = form.querySelector('.lpdo-button');
      if (btn) { btn.disabled = true; btn.textContent = btn.textContent + '…'; }
    });
  });
})();
