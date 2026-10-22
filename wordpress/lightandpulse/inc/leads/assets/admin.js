(function () {
  'use strict';

  // Submit on change (stage picker)
  document.querySelectorAll('[data-autosubmit]').forEach(function (el) {
    el.addEventListener('change', function () { el.form.submit(); });
  });

  // Confirm destructive forms
  document.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      if (!confirm(f.dataset.confirm)) e.preventDefault();
    });
  });

  // Loading label on slow actions (website audits)
  document.querySelectorAll('[data-loading]').forEach(function (btn) {
    btn.form && btn.form.addEventListener('submit', function () {
      setTimeout(function () { btn.disabled = true; btn.textContent = btn.dataset.loading; }, 0);
    });
  });

  document.querySelectorAll('[data-loading-link]').forEach(function (a) {
    a.addEventListener('click', function () { a.textContent = a.dataset.loadingLink; });
  });

  // Select-all checkboxes
  document.querySelectorAll('[data-checkall]').forEach(function (master) {
    master.addEventListener('change', function () {
      document.querySelectorAll('input[name="' + master.dataset.checkall + '"]').forEach(function (c) { c.checked = master.checked; });
    });
  });

  // Copy to clipboard
  document.querySelectorAll('[data-copy]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var el = document.querySelector(btn.dataset.copy);
      if (!el) return;
      var done = function () { var t = btn.textContent; btn.textContent = 'Copied ✓'; setTimeout(function () { btn.textContent = t; }, 1500); };
      if (navigator.clipboard) navigator.clipboard.writeText(el.value).then(done);
      else { el.select(); document.execCommand('copy'); done(); }
    });
  });

  // Message composer: template picker fills subject + body
  var dataEl = document.getElementById('tpl-data');
  var select = document.getElementById('tpl-select');
  if (dataEl && select) {
    var tpls = JSON.parse(dataEl.textContent);
    var body = document.getElementById('msg-body');
    var subject = document.getElementById('msg-subject');
    var subjectRow = subject.closest('.subject-row');
    var apply = function () {
      var t = tpls.find(function (x) { return String(x.id) === select.value; });
      if (!t) { subjectRow.classList.remove('show'); return; }
      body.value = t.body;
      subject.value = t.subject;
      subjectRow.classList.toggle('show', t.channel === 'email');
    };
    select.addEventListener('change', apply);
    apply();
  }

  // Kanban drag & drop
  var stageForm = document.getElementById('stage-form');
  if (stageForm) {
    var dragged = null;
    document.querySelectorAll('.kcard').forEach(function (c) {
      c.addEventListener('dragstart', function () { dragged = c; c.classList.add('dragging'); });
      c.addEventListener('dragend', function () { c.classList.remove('dragging'); });
    });
    document.querySelectorAll('.board .col').forEach(function (col) {
      col.addEventListener('dragover', function (e) { e.preventDefault(); col.classList.add('drop'); });
      col.addEventListener('dragleave', function () { col.classList.remove('drop'); });
      col.addEventListener('drop', function (e) {
        e.preventDefault();
        col.classList.remove('drop');
        if (!dragged || dragged.parentElement === col) return;
        col.appendChild(dragged);
        stageForm.querySelector('[name=id]').value = dragged.dataset.id;
        stageForm.querySelector('[name=stage]').value = col.dataset.stage;
        stageForm.submit();
      });
    });
  }
})();
