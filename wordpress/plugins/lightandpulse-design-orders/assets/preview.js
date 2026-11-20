/* Lightandpulse Design Orders: live preview.
   A rough mock-up of the chosen design type, built from what the client has
   typed so far and their brand colours and logo. Text only goes in through
   textContent, never as HTML. */
(function () {
  'use strict';

  var TYPE_LABELS = {
    poster: 'Poster', flyer: 'Flyer', menu: 'Menu', banner: 'Banner', backdrop: 'Backdrop',
    business_card: 'Kartu nama', greeting: 'Kartu ucapan', digital: 'TV & media sosial', directory: 'Room directory'
  };

  function h(tag, cls, text) {
    var el = document.createElement(tag);
    if (cls) { el.className = cls; }
    if (text !== undefined && text !== null && text !== '') { el.textContent = text; }
    return el;
  }
  // A frame is an outer box (size, colours) around the content; appending a
  // frame's content node appends its outer box.
  function add(parent) {
    for (var i = 1; i < arguments.length; i++) { var n = arguments[i]; if (n) { parent.appendChild(n.pvOuter || n); } }
    return parent;
  }
  function hex(v, fallback) { return /^#[0-9a-f]{6}$/i.test(v || '') ? v : fallback; }
  function lum(c) {
    var n = parseInt(c.slice(1), 16), r = (n >> 16) / 255, g = (n >> 8 & 255) / 255, b = (n & 255) / 255;
    var f = function (x) { return x <= 0.03928 ? x / 12.92 : Math.pow((x + 0.055) / 1.055, 2.4); };
    return 0.2126 * f(r) + 0.7152 * f(g) + 0.0722 * f(b);
  }
  function on(c) { return lum(c) > 0.45 ? '#141414' : '#ffffff'; }
  function lines(text, max) {
    return (text || '').split(/\n+/).map(function (s) { return s.trim(); }).filter(Boolean).slice(0, max || 99);
  }

  function initPreview(root) {
    var form = root.querySelector('.lpdo-form');
    var aside = root.querySelector('.lpdo-preview');
    if (!form || !aside) { return; }
    var stage = aside.querySelector('.lpdo-preview__stage');
    var chips = aside.querySelector('.lpdo-preview__chips');
    var stored = {};
    try { stored = JSON.parse(aside.getAttribute('data-brand') || '{}'); } catch (e) { stored = {}; }
    var logoUrl = stored.logo || '';
    var logoLight = !!stored.logo_light;
    var logoFile = null;
    var logoLum = null; // average lightness of the logo's visible pixels, once measured
    var measured = '';
    function measureLogo(url) {
      if (!url || measured === url) { return; }
      measured = url;
      logoLum = null;
      var img = new Image();
      img.onload = function () {
        try {
          var c = document.createElement('canvas');
          c.width = c.height = 32;
          var ctx = c.getContext('2d');
          ctx.drawImage(img, 0, 0, 32, 32);
          var d = ctx.getImageData(0, 0, 32, 32).data, sum = 0, n = 0;
          for (var i = 0; i < d.length; i += 4) {
            if (d[i + 3] < 128) { continue; }
            sum += (0.2126 * d[i] + 0.7152 * d[i + 1] + 0.0722 * d[i + 2]) / 255;
            n++;
          }
          logoLum = n ? sum / n : null;
        } catch (err) { logoLum = null; }
        queue();
      };
      img.src = url;
    }

    // ---- reading the form (disabled = hidden, so ignored)
    function field(name) {
      var el = form.querySelector('[name="' + name + '"]');
      return el && !el.disabled ? el : null;
    }
    function v(key) {
      var radios = form.querySelectorAll('input[type=radio][name="lpdo[' + key + ']"]');
      if (radios.length) {
        for (var i = 0; i < radios.length; i++) { if (radios[i].checked && !radios[i].disabled) { return radios[i].value; } }
        return '';
      }
      var el = field('lpdo[' + key + ']');
      return el ? el.value.trim() : '';
    }
    function label(key) {
      var el = field('lpdo[' + key + ']');
      if (el && el.tagName === 'SELECT' && el.value) { return el.options[el.selectedIndex].text; }
      var r = form.querySelector('input[type=radio][name="lpdo[' + key + ']"]:checked:not(:disabled)');
      return r ? r.parentNode.textContent.trim() : '';
    }
    function checks(key) {
      return Array.prototype.filter.call(form.querySelectorAll('input[name="lpdo[' + key + '][]"]'), function (c) { return c.checked && !c.disabled; })
        .map(function (c) { return { value: c.value, label: c.parentNode.textContent.trim() }; });
    }
    function rows(key) {
      var rep = form.querySelector('#lpdo-' + key);
      if (!rep || rep.closest('.is-hidden')) { return []; }
      var out = [];
      rep.querySelectorAll('.lpdo-rep__row').forEach(function (row) {
        var o = {}, any = false;
        row.querySelectorAll('input, select').forEach(function (el) {
          var m = el.name.match(/\]\[([a-z0-9_]+)\]$/);
          if (!m) { return; }
          o[m[1]] = el.tagName === 'SELECT' ? (el.value ? el.options[el.selectedIndex].text : '') : el.value.trim();
          if (o[m[1]]) { any = true; }
        });
        if (any) { out.push(o); }
      });
      return out;
    }
    function client(key) {
      var el = form.querySelector('[name="lpdo_client[' + key + ']"]');
      return el ? el.value.trim() : (stored[key] || '');
    }

    function brand() {
      var p = hex(client('color_primary'), '#1f2937');
      var s = hex(client('color_secondary'), '#f5f5f4');
      var a = hex(client('color_accent'), '#ff6a3d');
      var logoInput = form.querySelector('input[type=file][name="lpdo_files[logo]"]');
      var file = logoInput && logoInput.files && logoInput.files[0];
      if (file && file !== logoFile && /^image\//.test(file.type)) {
        if (logoUrl.indexOf('blob:') === 0) { URL.revokeObjectURL(logoUrl); }
        logoFile = file;
        logoUrl = URL.createObjectURL(file);
        logoLight = false;
      }
      var mode = v('contact_mode');
      return {
        p: p, s: s, a: a, onP: on(p), onS: on(s), onA: on(a),
        name: client('business_name') || stored.name || 'Nama bisnis',
        contact: mode === 'none' ? '' : (mode === 'custom' ? v('contact_text') : client('contact_print'))
      };
    }

    // ---- building blocks
    function logo(b, cls) {
      if (logoUrl) {
        var img = h('img', 'pv-logo' + (cls ? ' ' + cls : ''));
        img.src = logoUrl;
        img.alt = '';
        return img;
      }
      var initials = b.name.replace(/[^\p{L}\p{N} ]/gu, '').split(/\s+/).map(function (w) { return w.charAt(0); }).join('').slice(0, 2).toUpperCase();
      return h('span', 'pv-mono' + (cls ? ' ' + cls : ''), initials || 'LP');
    }
    function text(cls, value, placeholder) {
      return h('div', cls + (value ? '' : ' is-ph'), value || placeholder);
    }
    function price(row) {
      var wrap = h('span', 'pv-price');
      if (row.promo) {
        add(wrap, row.price ? h('s', '', row.price) : null, h('b', '', ' ' + row.promo));
      } else {
        wrap.textContent = row.price || '';
      }
      return wrap;
    }
    function itemList(items, max) {
      if (!items.length) { return null; }
      var ul = h('ul', 'pv-items');
      items.slice(0, max).forEach(function (r) {
        var li = h('li');
        add(li, add(h('div', 'pv-item__top'), h('span', 'pv-item__name', r.name || ''), h('span', 'pv-dots'), price(r)));
        var desc = r.desc || r.ingredients || '';
        if (desc) { add(li, h('div', 'pv-item__desc', desc)); }
        ul.appendChild(li);
      });
      if (items.length > max) { ul.appendChild(h('li', 'pv-more', '+' + (items.length - max) + ' lagi')); }
      return ul;
    }
    function qrOn() { var q = v('qr'); return q !== '' && q !== 'none'; }
    function footer(b) {
      var q = qrOn();
      if (!b.contact && !q) { return null; }
      var f = h('div', 'pv-foot');
      if (b.contact) { f.appendChild(h('div', 'pv-foot__text', lines(b.contact, 3).join(' · '))); }
      if (q) { f.appendChild(h('span', 'pv-qr')); }
      return f;
    }
    function cta(b, value) {
      if (!value) { return null; }
      var c = h('div', 'pv-cta', value);
      c.style.background = b.a;
      c.style.color = b.onA;
      return c;
    }
    function frame(ratio, bg, color) {
      var outer = h('div', 'pv-frame');
      outer.setAttribute('data-bg', bg);
      outer.style.aspectRatio = String(Math.max(0.3, Math.min(3.2, ratio)));
      outer.style.background = bg;
      outer.style.color = color;
      var el = h('div', 'pv');
      outer.appendChild(el);
      el.pvOuter = outer;
      return el;
    }
    function placeholderRows(parent, n) {
      for (var i = 0; i < n; i++) { add(parent, add(h('div', 'pv-item__top'), h('span', 'pv-bar'), h('span', 'pv-dots'), h('span', 'pv-bar pv-bar--s'))); }
    }

    // ---- one mock-up per design type
    var RENDER = {
      poster: function (b) {
        var sizes = checks('sizes').map(function (c) { return c.value; });
        var orient = v('orientation');
        var ratio = orient === 'landscape' ? 1.414 : orient === 'square' ? 1 : (sizes.length === 1 && sizes[0] === 'dl' ? 0.476 : 0.707);
        var el = frame(ratio, b.p, b.onP);
        el.classList.add('pv-poster');
        add(el, add(h('div', 'pv-top'), logo(b), v('outlet') ? h('div', 'pv-eyebrow', v('outlet')) : null));
        add(el, text('pv-title', v('headline') || v('promo_title') || v('title'), 'Judul utama'));
        if (v('subheadline')) { add(el, h('div', 'pv-sub', v('subheadline'))); }
        var events = rows('events');
        if (events.length) {
          var ev = h('div', 'pv-events');
          events.slice(0, 3).forEach(function (e) {
            add(ev, add(h('div', 'pv-event'), h('div', 'pv-event__name', e.name), h('div', 'pv-event__meta', [e.date, e.time, e.venue].filter(Boolean).join(' · ')), e.price ? h('div', 'pv-event__price', e.price) : null));
          });
          add(el, ev);
        }
        var items = rows('items');
        add(el, itemList(items, events.length ? 3 : 5));
        if (!events.length && !items.length && v('body_text')) { add(el, h('div', 'pv-body', v('body_text'))); }
        var inc = lines(v('inclusions'), 3);
        if (inc.length) { var ul = h('ul', 'pv-bullets'); inc.forEach(function (i) { ul.appendChild(h('li', '', i)); }); add(el, ul); }
        add(el, cta(b, v('cta')), footer(b));
        return el;
      },
      menu: function (b) {
        var ratio = /^(15x30|16x30|dl)$/.test(v('menu_size')) ? 0.5 : 0.707;
        var bg = lum(b.s) > 0.6 ? b.s : '#faf7f2';
        var el = frame(ratio, bg, '#1c1c1e');
        el.classList.add('pv-menu');
        var head = add(h('div', 'pv-menu__head'), logo(b, logoLight ? 'on-dark' : ''), text('pv-menu__title', v('outlet') || b.name, 'Nama outlet'));
        head.style.borderColor = b.p;
        add(el, head, h('div', 'pv-menu__kind', label('menu_kind') || 'Menu'));
        var items = rows('menu_items');
        if (!items.length) {
          var ph = h('div', 'pv-menu__group');
          add(ph, h('div', 'pv-menu__cat is-ph', 'Kategori'));
          placeholderRows(ph, 4);
          add(el, ph);
        }
        var groups = {}, order = [];
        items.forEach(function (r) { var k = r.category || ''; if (!groups[k]) { groups[k] = []; order.push(k); } groups[k].push(r); });
        var shown = 0;
        order.forEach(function (k) {
          if (shown >= 9) { return; }
          var g = h('div', 'pv-menu__group');
          if (k) { var cat = h('div', 'pv-menu__cat', k); cat.style.color = b.p; g.appendChild(cat); }
          groups[k].forEach(function (r) {
            if (shown++ >= 9) { return; }
            var li = h('div', 'pv-menu__item');
            add(li, add(h('div', 'pv-item__top'), h('span', 'pv-item__name', r.name), r.label ? h('span', 'pv-tag', r.label) : null, h('span', 'pv-dots'), h('span', 'pv-price', [r.price, r.price2].filter(Boolean).join(' / '))));
            if (r.ingredients) { add(li, h('div', 'pv-item__desc', r.ingredients)); }
            g.appendChild(li);
          });
          el.appendChild(g);
        });
        if (items.length > 9) { add(el, h('div', 'pv-more', '+' + (items.length - 9) + ' item lagi')); }
        if (v('menu_price_note') === 'plus') { add(el, h('div', 'pv-menu__note', 'Harga belum termasuk pajak & service' + (v('menu_tax') ? ' ' + v('menu_tax') + '%' : '') + ' (++)')); }
        add(el, footer(b));
        return el;
      },
      banner: function (b) {
        var piece = rows('banner_pieces')[0] || {};
        var w = parseFloat(piece.w) || 60, hgt = parseFloat(piece.h) || 160;
        if (!piece.w && /roll-up/i.test(piece.kind || '')) { w = 80; hgt = 200; }
        var wide = w > hgt;
        var el = frame(w / hgt, b.p, b.onP);
        el.classList.add('pv-banner', wide ? 'is-wide' : 'is-tall');
        add(el, logo(b), text('pv-title', v('headline') || v('promo_title') || v('title'), 'Judul banner'));
        if (v('subheadline')) { add(el, h('div', 'pv-sub', v('subheadline'))); }
        add(el, itemList(rows('items'), wide ? 3 : 6), cta(b, v('cta')), footer(b));
        return el;
      },
      backdrop: function (b) {
        var w = parseFloat(v('backdrop_width')) || 3, hgt = parseFloat(v('backdrop_height')) || 2;
        var el = frame(w / hgt, b.p, b.onP);
        el.classList.add('pv-backdrop');
        var elements = checks('backdrop_elements').map(function (c) { return c.value; });
        if (elements.indexOf('repeat') > -1) {
          var pat = h('div', 'pv-pattern');
          for (var i = 0; i < 24; i++) { pat.appendChild(h('span', '', b.name)); }
          el.appendChild(pat);
        }
        var center = add(h('div', 'pv-center'), logo(b), text('pv-title', v('backdrop_headline') || v('backdrop_event') || v('title'), 'Teks utama'));
        if (v('backdrop_names')) { add(center, h('div', 'pv-script', v('backdrop_names'))); }
        var date = v('backdrop_date');
        var meta = [date ? new Date(date + 'T00:00:00').toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : '', v('backdrop_venue')].filter(Boolean).join(' · ');
        if (meta) { add(center, h('div', 'pv-sub', meta)); }
        if (v('backdrop_hashtag')) { add(center, h('div', 'pv-hashtag', v('backdrop_hashtag'))); }
        el.appendChild(center);
        var sponsors = rows('backdrop_sponsors');
        if (sponsors.length) {
          var strip = h('div', 'pv-sponsors');
          sponsors.slice(0, 6).forEach(function (s) { strip.appendChild(h('span', '', s.name)); });
          el.appendChild(strip);
        }
        return el;
      },
      business_card: function (b) {
        var people = rows('card_people');
        var person = people[0] || {};
        var portrait = v('card_orientation') === 'portrait';
        var ratio = portrait ? 0.61 : 1.636;
        var wrap = h('div', 'pv-cards');
        var front = frame(ratio, '#ffffff', '#1c1c1e');
        front.classList.add('pv-card', portrait ? 'is-portrait' : 'is-landscape');
        var bar = h('span', 'pv-card__bar');
        bar.style.background = b.a;
        var name = text('pv-card__name', person.name, 'Nama lengkap');
        name.style.color = b.p;
        var shared = lines(v('card_shared'), 1)[0];
        add(front, logo(b, 'pv-card__logo' + (logoLight ? ' on-dark' : '')),
          add(h('div', 'pv-card__who'), name, text('pv-card__role', person.title, 'Jabatan')),
          add(h('div', 'pv-card__lines'), person.phone ? h('div', '', person.phone) : null, person.email ? h('div', '', person.email) : null, shared ? h('div', '', shared) : null),
          bar);
        add(wrap, front);
        if (v('card_sides') !== 'one') {
          var back = frame(ratio, b.p, b.onP);
          back.classList.add('pv-card', 'pv-card--back');
          add(back, logo(b), v('card_back') ? h('div', 'pv-card__backtext', v('card_back')) : null);
          var q = v('card_qr');
          if (q && q !== 'none') { back.appendChild(h('span', 'pv-qr')); }
          add(wrap, back);
        }
        if (people.length > 1) { wrap.appendChild(h('div', 'pv-more', '+' + (people.length - 1) + ' orang lagi, desain sama')); }
        return wrap;
      },
      greeting: function (b) {
        var formats = checks('greeting_formats').map(function (c) { return c.value; });
        var only = function (list) { return formats.length && formats.every(function (f) { return list.indexOf(f) > -1; }); };
        var ratio = only(['ig_post']) ? 0.8 : only(['ig_story', 'wa']) ? 0.5625 : 0.707;
        var light = lum(b.s) > 0.6;
        var el = frame(ratio, light ? b.s : b.p, light ? '#1c1c1e' : b.onP);
        el.classList.add('pv-greeting');
        var orn = h('div', 'pv-orn');
        orn.style.background = b.a;
        var title = text('pv-title pv-serif', v('greeting_headline'), 'Ucapan utama');
        if (light) { title.style.color = b.p; }
        add(el, orn, text('pv-occasion', label('greeting_occasion'), 'Momen'), title, text('pv-body', v('greeting_message'), 'Isi pesan untuk tamu…'));
        add(el, add(h('div', 'pv-sign'), text('', v('greeting_signoff'), 'Warm regards, ' + b.name), v('greeting_signature') ? h('div', 'pv-sign__sub', v('greeting_signature')) : null));
        add(el, logo(b, 'pv-greeting__logo' + (light && logoLight ? ' on-dark' : '')));
        return el;
      },
      digital: function (b) {
        var formats = checks('digital_formats').map(function (c) { return c.value; });
        var only = function (list) { return formats.length && formats.every(function (f) { return list.indexOf(f) > -1; }); };
        var portrait = only(['tv_p', 'ig_story', 'wa']);
        var square = only(['ig_post']);
        var slides = rows('digital_slides');
        var s = slides[0] || {};
        var el = frame(portrait ? 0.5625 : square ? 0.8 : 1.778, b.p, b.onP);
        el.classList.add('pv-digital', portrait || square ? 'is-tall' : 'is-wide');
        var photo = h('div', 'pv-photo');
        photo.style.background = 'linear-gradient(135deg,' + b.a + ',' + b.s + ')';
        var body = add(h('div', 'pv-digital__body'), logo(b), text('pv-title', s.title || v('headline') || v('title'), 'Judul slide'), (s.text || v('subheadline')) ? h('div', 'pv-sub', s.text || v('subheadline')) : null, cta(b, s.price));
        add(el, photo, body);
        if (slides.length > 1) {
          var dots = h('div', 'pv-dots-nav');
          slides.slice(0, 8).forEach(function (x, i) { dots.appendChild(h('span', i === 0 ? 'is-on' : '')); });
          el.appendChild(dots);
        }
        if (v('digital_motion') === 'animated') { el.appendChild(h('span', 'pv-badge', '▶ animasi')); }
        return el;
      },
      directory: function (b) {
        var wrap = h('div', 'pv-spread');
        var cover = frame(0.707, b.p, b.onP);
        cover.classList.add('pv-dir-cover');
        add(cover, logo(b), h('div', 'pv-title pv-serif', 'Room Directory'), h('div', 'pv-sub', b.name));
        var page = frame(0.707, '#ffffff', '#1c1c1e');
        page.classList.add('pv-dir-page');
        var hours = rows('dir_hours'), phones = rows('dir_phones');
        var t1 = h('div', 'pv-menu__cat', 'Fasilitas & jam buka');
        t1.style.color = b.p;
        add(page, t1);
        if (hours.length) {
          hours.slice(0, 5).forEach(function (r) { add(page, add(h('div', 'pv-item__top'), h('span', 'pv-item__name', r.name), h('span', 'pv-dots'), h('span', 'pv-price', r.hours))); });
        } else {
          placeholderRows(page, 3);
        }
        var t2 = h('div', 'pv-menu__cat', 'Telepon');
        t2.style.color = b.p;
        add(page, t2);
        phones.slice(0, 5).forEach(function (r) { add(page, add(h('div', 'pv-item__top'), h('span', 'pv-item__name', r.service), h('span', 'pv-dots'), h('span', 'pv-price', r.ext))); });
        if (v('dir_wifi')) { add(page, h('div', 'pv-menu__note', 'Wi-Fi: ' + v('dir_wifi'))); }
        return add(wrap, cover, page);
      }
    };
    RENDER.flyer = RENDER.poster;

    function sizeChips() {
      var list = [];
      ['sizes', 'greeting_formats', 'digital_formats'].forEach(function (k) { checks(k).forEach(function (c) { list.push(c.label); }); });
      rows('banner_pieces').forEach(function (r) { if (r.kind) { list.push(r.kind + (r.w && r.h ? ' (' + r.w + '×' + r.h + ')' : '')); } });
      ['menu_size', 'menu_format', 'card_size', 'dir_format'].forEach(function (k) { var l = label(k); if (l) { list.push(l); } });
      if (v('backdrop_width') && v('backdrop_height')) { list.push(v('backdrop_width') + ' × ' + v('backdrop_height') + ' m'); }
      return list;
    }

    var queued = false;
    function render() {
      queued = false;
      var checked = form.querySelector('input[name="lpdo_type"]:checked');
      var type = checked ? checked.value : '';
      stage.textContent = '';
      chips.textContent = '';
      if (!type || !RENDER[type]) {
        stage.appendChild(add(h('div', 'pv-empty'), h('div', 'pv-empty__icon', '◳'), h('p', '', 'Pilih jenis desain untuk melihat pratinjau.')));
        return;
      }
      stage.appendChild(h('div', 'lpdo-preview__type', TYPE_LABELS[type]));
      add(stage, RENDER[type](brand()));
      // Logo too close to its background colour? Give it a contrasting chip.
      measureLogo(logoUrl);
      if (logoLum !== null) {
        stage.querySelectorAll('.pv-frame').forEach(function (f) {
          var bg = f.getAttribute('data-bg') || '#ffffff';
          var bgLum = /^#[0-9a-f]{6}$/i.test(bg) ? Math.pow(lum(bg), 1 / 2.2) : 1;
          if (Math.abs(bgLum - logoLum) < 0.28) {
            f.querySelectorAll('img.pv-logo').forEach(function (img) { img.classList.remove('on-dark'); img.classList.add('pv-chip', logoLum > 0.72 ? 'is-dark' : 'is-light'); });
          }
        });
      }
      sizeChips().slice(0, 8).forEach(function (s) { chips.appendChild(h('span', '', s)); });
    }
    function queue() { if (!queued) { queued = true; requestAnimationFrame(render); } }
    form.addEventListener('input', queue);
    form.addEventListener('change', queue);
    form.addEventListener('click', function (e) { if (e.target.closest('[data-rep-add], [data-rep-remove], .lpdo-drop__remove')) { setTimeout(queue, 0); } });
    render();

    // Wide enough for a side column? Measured on the form's own width, so it
    // works inside any theme layout. Otherwise a floating button opens a sheet.
    function measure() { root.classList.toggle('is-wide', root.clientWidth >= 940); }
    if (window.ResizeObserver) { new ResizeObserver(measure).observe(root); } else { window.addEventListener('resize', measure); }
    measure();
    // Show the floating button only while the form is on screen.
    if (window.IntersectionObserver) {
      new IntersectionObserver(function (en) { root.classList.toggle('is-visible', en[0].isIntersecting); }).observe(form);
    } else {
      root.classList.add('is-visible');
    }
    root.addEventListener('click', function (e) {
      if (e.target.closest('[data-preview-open]')) { render(); root.classList.add('is-preview-open'); }
      if (e.target.closest('[data-preview-close]') || e.target === aside) { root.classList.remove('is-preview-open'); }
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { root.classList.remove('is-preview-open'); } });
  }

  document.querySelectorAll('.lpdo--preview').forEach(initPreview);
})();
