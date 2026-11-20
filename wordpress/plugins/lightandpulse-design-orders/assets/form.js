(function () {
  'use strict';
  // Fields that only matter for certain answers: field key -> [controlling field, value].
  var RULES = {
    sizes_custom: ['sizes', 'custom'], menu_size_custom: ['menu_size', 'custom'], card_size_custom: ['card_size', 'custom'],
    languages_other: ['languages', 'other'], menu_photo_files: ['menu_photos', 'own'],
    contact_text: ['contact_mode', 'custom'], qr_url: ['qr', 'custom'],
    backdrop_hashtag: ['backdrop_elements', 'hashtag'], backdrop_names: ['backdrop_elements', 'names'],
    backdrop_sponsors: ['backdrop_elements', 'sponsors']
  };

  function sizeLabel(b) {
    return b > 1048576 ? (b / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB';
  }

  // ---------------------------------------------------------------- tables
  // Works anywhere a [data-rep] table is printed (form and wp-admin).
  function moveRequired(rep) {
    var first = rep.querySelector('.lpdo-rep__row');
    if (!first || rep.dataset.reqCols === undefined) { return; }
    var cols = rep.dataset.reqCols ? rep.dataset.reqCols.split(',') : [];
    cols.forEach(function (c) {
      var el = first.querySelectorAll('input, select')[+c];
      if (el) { el.dataset.required = '1'; el.required = !el.disabled; }
    });
  }
  function addRow(rep, focus) {
    var tpl = rep.querySelector('template');
    var i = parseInt(rep.dataset.next, 10) || 0;
    rep.dataset.next = i + 1;
    var ol = rep.querySelector('.lpdo-rep__rows');
    ol.insertAdjacentHTML('beforeend', tpl.innerHTML.replace(/__i__/g, i));
    var first = ol.lastElementChild.querySelector('input, select');
    if (focus && first) { first.focus(); }
  }
  document.addEventListener('click', function (e) {
    var add = e.target.closest('[data-rep-add]');
    if (add) { addRow(add.closest('[data-rep]'), true); return; }
    var rm = e.target.closest('[data-rep-remove]');
    if (!rm) { return; }
    var rep = rm.closest('[data-rep]');
    var row = rm.closest('.lpdo-rep__row');
    var rows = rep.querySelectorAll('.lpdo-rep__row');
    if (row === rows[0]) {
      var req = [];
      row.querySelectorAll('input, select').forEach(function (el, idx) { if (el.dataset.required) { req.push(idx); } });
      rep.dataset.reqCols = req.join(',');
    }
    if (rows.length > 1) {
      var next = row.nextElementSibling || row.previousElementSibling;
      row.remove();
      moveRequired(rep);
      var f = next && next.querySelector('input, select');
      if (f) { f.focus(); }
    } else {
      row.querySelectorAll('input, select').forEach(function (el) { el.value = ''; });
    }
  });
  // Enter in a table cell goes to the next row (adding one at the end) instead of sending the form.
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter' || !e.target.closest('.lpdo-rep__cell') || e.target.tagName === 'TEXTAREA') { return; }
    e.preventDefault();
    var row = e.target.closest('.lpdo-rep__row');
    var idx = Array.prototype.indexOf.call(row.querySelectorAll('input, select'), e.target);
    var rep = row.closest('[data-rep]');
    if (!row.nextElementSibling) { addRow(rep, false); }
    var target = row.nextElementSibling.querySelectorAll('input, select')[idx];
    if (target) { target.focus(); }
  });

  // ---------------------------------------------------------------- files
  var canSetFiles = (function () { try { return !!new DataTransfer().files; } catch (err) { return false; } })();

  function initDrop(drop, maxBytes, maxMb) {
    var input = drop.querySelector('input[type=file]');
    var list = drop.querySelector('.lpdo-drop__list');
    var store = [];
    var urls = [];

    function render() {
      urls.forEach(function (u) { URL.revokeObjectURL(u); });
      urls = [];
      list.textContent = '';
      var files = canSetFiles ? store : Array.prototype.slice.call(input.files);
      files.forEach(function (f, i) {
        var li = document.createElement('li');
        li.className = 'lpdo-drop__item';
        var thumb;
        if (/^image\//.test(f.type)) {
          thumb = document.createElement('img');
          var u = URL.createObjectURL(f);
          urls.push(u);
          thumb.src = u;
          thumb.alt = '';
        } else {
          thumb = document.createElement('span');
          thumb.textContent = (f.name.split('.').pop() || '').slice(0, 4);
        }
        thumb.className = 'lpdo-drop__thumb';
        var name = document.createElement('span');
        name.className = 'lpdo-drop__name';
        name.textContent = f.name;
        name.title = f.name;
        var size = document.createElement('span');
        size.className = 'lpdo-drop__size';
        size.textContent = sizeLabel(f.size);
        name.appendChild(size);
        li.append(thumb, name);
        if (canSetFiles) {
          var rm = document.createElement('button');
          rm.type = 'button';
          rm.className = 'lpdo-drop__remove';
          rm.setAttribute('aria-label', 'Hapus ' + f.name);
          rm.innerHTML = '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>';
          rm.addEventListener('click', function () { store.splice(i, 1); sync(); input.focus(); });
          li.appendChild(rm);
        }
        list.appendChild(li);
      });
    }
    function sync() {
      var dt = new DataTransfer();
      store.forEach(function (f) { dt.items.add(f); });
      input.files = dt.files;
      render();
    }
    function warn(text) {
      var old = drop.querySelector('.lpdo-file-warning');
      if (old) { old.remove(); }
      if (!text) { return; }
      var w = document.createElement('small');
      w.className = 'lpdo-file-warning';
      w.setAttribute('role', 'alert');
      w.textContent = text;
      drop.appendChild(w);
    }

    input.addEventListener('change', function () {
      var picked = Array.prototype.slice.call(input.files);
      var big = picked.filter(function (f) { return f.size > maxBytes; });
      warn(big.length ? big.map(function (f) { return f.name; }).join(', ') + ' dilewati: lebih dari ' + maxMb + ' MB.' : '');
      if (!canSetFiles) {
        if (big.length) { input.value = ''; }
        render();
        return;
      }
      picked = picked.filter(function (f) { return f.size <= maxBytes; });
      if (!input.multiple) {
        store = picked.slice(0, 1).length ? picked.slice(0, 1) : store;
      } else {
        picked.forEach(function (f) {
          var dup = store.some(function (s) { return s.name === f.name && s.size === f.size && s.lastModified === f.lastModified; });
          if (!dup) { store.push(f); }
        });
        if (store.length > 20) { store = store.slice(0, 20); warn('Maks. 20 file untuk kolom ini.'); }
      }
      sync();
    });
    ['dragenter', 'dragover'].forEach(function (t) { drop.addEventListener(t, function () { drop.classList.add('is-over'); }); });
    ['dragleave', 'drop'].forEach(function (t) { drop.addEventListener(t, function () { drop.classList.remove('is-over'); }); });
  }

  // ---------------------------------------------------------------- forms
  document.querySelectorAll('.lpdo-form').forEach(function (form) {
    var maxMb = parseInt(form.dataset.maxMb, 10) || 10;
    var maxBytes = maxMb * 1048576;
    form.querySelectorAll('[data-drop]').forEach(function (d) { initDrop(d, maxBytes, maxMb); });

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

    form.addEventListener('change', function (e) {
      if (e.target.type !== 'file') { update(); }
      if (e.target.name === 'lpdo_type') {
        var details = form.querySelector('.lpdo-details');
        if (details && details.getBoundingClientRect().top > window.innerHeight * 0.75) {
          details.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      }
    });
    update();

    // The server takes a limited number of files and bytes per submission.
    form.addEventListener('submit', function (e) {
      var err = form.querySelector('.lpdo-submit-error');
      var count = 0, bytes = 0;
      form.querySelectorAll('input[type=file]').forEach(function (i) {
        if (i.disabled) { return; }
        Array.prototype.forEach.call(i.files, function (f) { count++; bytes += f.size; });
      });
      var maxFiles = parseInt(form.dataset.maxFiles, 10) || 20;
      var maxPost = parseInt(form.dataset.maxPost, 10) || 0;
      var msg = '';
      if (count > maxFiles) {
        msg = 'Terlalu banyak file (' + count + '). Maks. ' + maxFiles + ' file per order; kirim sisanya lewat WhatsApp atau setelah order masuk.';
      } else if (maxPost && bytes > maxPost * 0.95) {
        msg = 'Total file terlalu besar (' + sizeLabel(bytes) + '). Maks. ' + sizeLabel(maxPost) + ' per order.';
      }
      if (err) { err.hidden = !msg; err.textContent = msg; }
      if (msg) { e.preventDefault(); return; }
      var btn = form.querySelector('.lpdo-button');
      if (btn) { btn.disabled = true; btn.setAttribute('aria-busy', 'true'); btn.firstChild.textContent = 'Mengirim… '; }
    });
  });
})();
