(function () {
  'use strict';

  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var finePointer = window.matchMedia('(pointer: fine)').matches;

  // ------------------------------------------------------------------ Menu
  var toggle = document.querySelector('.menu-toggle');
  var nav = document.querySelector('.primary-nav');
  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      var open = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      document.body.classList.toggle('lp-nav-open', open);
    });
  }

  // ------------------------------------------------------------------ Header state
  var header = document.querySelector('[data-lp-header]');
  if (header) {
    var onScroll = function () { header.classList.toggle('is-scrolled', window.scrollY > 8); };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  // ------------------------------------------------------------------ ASCII fields
  // Draws characters into a <pre>. "pulse" = the homepage heartbeat line whose
  // spike follows the pointer; "field" = a drifting character field for page heroes.
  // The server-rendered text stays in place when JavaScript is off.
  var RAMP = ' .:-=+*x#%@';
  var SOFT = ' ..::-=+';

  function hash(x, y) {
    var h = (x * 374761393 + y * 668265263) | 0;
    h = (h ^ (h >>> 13)) * 1274126177;
    return ((h ^ (h >>> 16)) >>> 0) / 4294967295;
  }

  function AsciiField(pre, mode, area) {
    this.pre = pre;
    this.mode = mode;
    this.area = area || pre;
    this.mouse = { x: 0.5, y: 0.5, tx: 0.5, ty: 0.5, active: 0, tActive: 0 };
    this.visible = true;
    this.measure();
    this.bind();
  }

  AsciiField.prototype.measure = function () {
    var probe = document.createElement('span');
    probe.textContent = 'MMMMMMMMMM';
    probe.style.cssText = 'position:absolute;visibility:hidden;white-space:pre';
    this.pre.appendChild(probe);
    var cw = probe.getBoundingClientRect().width / 10 || 6;
    var ch = parseFloat(getComputedStyle(this.pre).lineHeight) || cw * 1.9;
    this.pre.removeChild(probe);
    var w = this.pre.clientWidth || this.area.clientWidth;
    this.cols = Math.max(20, Math.floor(w / cw));
    this.rows = this.mode === 'pulse' ? 13 : Math.max(6, Math.floor((this.pre.clientHeight || this.area.clientHeight) / ch));
  };

  AsciiField.prototype.bind = function () {
    var self = this;
    var move = function (e) {
      var r = self.pre.getBoundingClientRect();
      self.mouse.tx = (e.clientX - r.left) / Math.max(1, r.width);
      self.mouse.ty = (e.clientY - r.top) / Math.max(1, r.height);
      self.mouse.tActive = 1;
      if (reduceMotion) { self.draw(performance.now() / 1000); }
    };
    this.area.addEventListener('pointermove', move, { passive: true });
    this.area.addEventListener('pointerleave', function () { self.mouse.tActive = 0; if (reduceMotion) { self.draw(0); } });
    window.addEventListener('resize', function () { self.measure(); self.draw(performance.now() / 1000); });
    if ('IntersectionObserver' in window) {
      new IntersectionObserver(function (entries) { self.visible = entries[0].isIntersecting; }).observe(this.pre);
    }
  };

  AsciiField.prototype.draw = function (t) {
    var m = this.mouse;
    var ease = reduceMotion ? 1 : 0.08;
    m.x += (m.tx - m.x) * ease;
    m.y += (m.ty - m.y) * ease;
    m.active += (m.tActive - m.active) * (reduceMotion ? 1 : 0.05);
    var cols = this.cols, rows = this.rows, out = '';
    var aspect = 2.1; // characters are about twice as tall as they are wide

    if (this.mode === 'pulse') {
      var mid = (rows - 1) / 2;
      // The spike sits in the centre when idle and follows the pointer when it is over the hero.
      var idle = 0.5 + Math.sin(t * 0.35) * 0.06;
      var c = idle + (Math.min(0.85, Math.max(0.15, m.x)) - idle) * m.active;
      var beat = reduceMotion ? 1 : 0.55 + 0.45 * Math.exp(-((t % 1.6) / 0.22));
      var curve = function (u) {
        var g = function (cc, w) { return Math.exp(-Math.pow((u - cc) / w, 2)); };
        return mid - 5.6 * beat * g(c, 0.011) + 4.2 * beat * g(c + 0.023, 0.011) - 1.4 * g(c - 0.06, 0.02) - 1.0 * g(c + 0.1, 0.026) + 0.35 * Math.sin(u * 46 + t * 1.6);
      };
      var ys = [];
      for (var i = 0; i <= cols; i++) { ys.push(curve((i - 0.5) / (cols - 1))); }
      var gx = m.x * (cols - 1), gy = m.y * (rows - 1), R = 14;
      for (var r = 0; r < rows; r++) {
        var line = '';
        for (var x = 0; x < cols; x++) {
          var u = x / (cols - 1);
          var y1 = ys[x], y2 = ys[x + 1];
          var lo = Math.min(y1, y2), hi = Math.max(y1, y2);
          var dist = r < lo ? lo - r : (r > hi ? r - hi : 0);
          var edge = Math.pow(Math.sin(Math.PI * u), 0.7);
          var v = Math.pow(Math.max(0, 1 - dist / 2.4), 1.8);
          var h = hash(x, r);
          if (h < 0.045) { v += 0.1 + 0.08 * Math.sin(t * 3 + h * 400); }
          if (m.active > 0.01) {
            var dx = (x - gx), dy = (r - gy) * aspect;
            var d = Math.sqrt(dx * dx + dy * dy);
            if (d < R * 1.8) {
              v += m.active * (Math.pow(Math.max(0, 1 - d / R), 2) * 0.7 + Math.max(0, Math.sin(d * 0.55 - t * 6)) * 0.18 * (1 - d / (R * 1.8)));
            }
          }
          v = Math.min(1, v * edge);
          line += RAMP.charAt(Math.round(v * (RAMP.length - 1)));
        }
        out += line + '\n';
      }
    } else {
      var fx = m.x * (cols - 1), fy = m.y * (rows - 1), FR = 16;
      for (var rr = 0; rr < rows; rr++) {
        var row = '';
        for (var xx = 0; xx < cols; xx++) {
          var hh = hash(xx, rr);
          var wave = 0.5 + 0.5 * Math.sin(xx * 0.09 + rr * 0.35 - t * 0.9 + Math.sin(xx * 0.02 + t * 0.3) * 2);
          var val = hh < 0.6 ? wave * 0.32 * hh : 0;
          if (m.active > 0.01) {
            var ddx = xx - fx, ddy = (rr - fy) * aspect;
            var dd = Math.sqrt(ddx * ddx + ddy * ddy);
            val += m.active * Math.pow(Math.max(0, 1 - dd / FR), 1.5) * 0.95;
          }
          // Fade towards the left so the text side stays calm.
          val *= Math.min(1, (xx / cols) * 1.6);
          row += SOFT.charAt(Math.round(Math.min(1, val) * (SOFT.length - 1)));
        }
        out += row + '\n';
      }
    }
    this.pre.textContent = out;
  };

  var fields = [];
  var heroPre = document.querySelector('.lp-hero .lp-ascii');
  if (heroPre) {
    heroPre.classList.add('is-live');
    fields.push(new AsciiField(heroPre, 'pulse', heroPre.closest('.elementor-section') || heroPre));
  }
  // Inner pages: add a background field to the page hero.
  document.querySelectorAll('.lp-page-hero').forEach(function (section) {
    var pre = document.createElement('pre');
    pre.className = 'lp-ascii lp-ascii--field is-live';
    pre.setAttribute('aria-hidden', 'true');
    section.insertBefore(pre, section.firstChild);
    fields.push(new AsciiField(pre, 'field', section));
  });

  if (fields.length) {
    fields.forEach(function (f) { f.draw(0.6); });
    if (!reduceMotion) {
      var last = 0;
      var loop = function (now) {
        if (!document.hidden && now - last > 33) { // ~30 fps is plenty for text
          last = now;
          fields.forEach(function (f) { if (f.visible) { f.draw(now / 1000); } });
        }
        requestAnimationFrame(loop);
      };
      requestAnimationFrame(loop);
    }
  }

  // ------------------------------------------------------------------ Pointer spotlight on cells
  if (finePointer && !reduceMotion) {
    var spot = '.lp-card .elementor-icon-box-wrapper, .lp-step, .lp-package, .lp-work, .lp-cta > .elementor-container, .lp-contact .lp-lead-form-wrap';
    document.addEventListener('pointermove', function (e) {
      var el = e.target.closest && e.target.closest(spot);
      if (!el) { return; }
      var r = el.getBoundingClientRect();
      el.style.setProperty('--mx', (e.clientX - r.left) + 'px');
      el.style.setProperty('--my', (e.clientY - r.top) + 'px');
    }, { passive: true });
  }

  // ------------------------------------------------------------------ Tech stack marquee
  var stack = document.querySelector('.lp-stack');
  if (stack && !reduceMotion) {
    var items = Array.prototype.slice.call(stack.children);
    items.forEach(function (li) {
      var copy = li.cloneNode(true);
      copy.setAttribute('aria-hidden', 'true');
      stack.appendChild(copy);
    });
    stack.classList.add('is-marquee');
  }

  // ------------------------------------------------------------------ Works filter
  document.querySelectorAll('[data-lp-works]').forEach(function (wrap) {
    var buttons = wrap.querySelectorAll('.lp-works-filter button');
    buttons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var f = btn.getAttribute('data-filter');
        buttons.forEach(function (b) {
          var on = b === btn;
          b.classList.toggle('is-active', on);
          b.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
        var n = 0;
        wrap.querySelectorAll('.lp-work').forEach(function (card) {
          var show = f === '*' || (' ' + card.getAttribute('data-types') + ' ').indexOf(' ' + f + ' ') > -1;
          card.hidden = !show;
          if (show && !reduceMotion) {
            card.style.setProperty('--i', n++);
            card.classList.remove('is-pop');
            void card.offsetWidth;
            card.classList.add('is-pop');
          }
        });
      });
    });
  });
})();
