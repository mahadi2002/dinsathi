// দিনসাথী — vanilla JS, no bundler. Progressive enhancement only: every
// [data-guard] form already works as a plain POST: this file just adds
// polish (spinners, reveal animation, live countdowns) on top.
(function () {
  'use strict';

  // ── Theme toggle (cookie-driven so PHP renders it server-side, no flash) ──
  document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
      var next = isDark ? 'light' : 'dark';
      document.documentElement.setAttribute('data-theme', next);
      document.cookie = 'dinsathi_theme=' + next + ';path=/;max-age=31536000;samesite=Lax';
      btn.textContent = next === 'dark' ? '☀' : '☾';
    });
  });

  // ── Scroll-reveal ──────────────────────────────────────────────────────
  var revealTargets = document.querySelectorAll('.reveal');
  if (revealTargets.length && 'IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          io.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12 });
    revealTargets.forEach(function (el) { io.observe(el); });
  } else {
    revealTargets.forEach(function (el) { el.classList.add('is-visible'); });
  }

  // ── Confirm-before-submit (CSP forbids inline onsubmit="return confirm()") ──
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (!window.confirm(form.dataset.confirm)) {
        e.preventDefault();
      }
    });
  });

  // ── Guard: disable submit button + show a spinner label, prevents double-POST ──
  document.querySelectorAll('form[data-guard]').forEach(function (form) {
    form.addEventListener('submit', function () {
      var btn = form.querySelector('button[type="submit"]');
      if (btn && !btn.disabled) {
        btn.dataset.label = btn.textContent;
        btn.disabled = true;
        btn.textContent = '...';
        setTimeout(function () { btn.disabled = false; btn.textContent = btn.dataset.label; }, 8000);
      }
    });
  });

  // ── OTP box: auto-advance between digit inputs ─────────────────────────
  document.querySelectorAll('.otp-input').forEach(function (box) {
    var inputs = Array.prototype.slice.call(box.querySelectorAll('input'));
    var hidden = document.getElementById('otp-combined');

    function sync() {
      if (hidden) { hidden.value = inputs.map(function (i) { return i.value; }).join(''); }
    }
    inputs.forEach(function (input, idx) {
      input.addEventListener('input', function () {
        input.value = input.value.replace(/\D/g, '').slice(0, 1);
        if (input.value && inputs[idx + 1]) { inputs[idx + 1].focus(); }
        sync();
      });
      input.addEventListener('keydown', function (e) {
        if (e.key === 'Backspace' && !input.value && inputs[idx - 1]) { inputs[idx - 1].focus(); }
      });
      input.addEventListener('paste', function (e) {
        var text = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
        if (text.length > 1) {
          e.preventDefault();
          text.split('').slice(0, inputs.length).forEach(function (ch, i) { if (inputs[i]) { inputs[i].value = ch; } });
          sync();
          (inputs[Math.min(text.length, inputs.length - 1)] || inputs[inputs.length - 1]).focus();
        }
      });
    });
    if (inputs[0]) { inputs[0].focus(); }
  });

  // ── Resend OTP cooldown ─────────────────────────────────────────────────
  // Anchored to a real end-timestamp rather than decrementing a counter per
  // tick — a backgrounded tab throttles setInterval, and a plain decrement
  // then fires the queued-up ticks back-to-back on refocus, making the
  // number drop several seconds almost instantly. Recomputing from
  // Date.now() every tick self-corrects regardless of any missed ticks.
  var resendBtn = document.querySelector('[data-resend-otp]');
  if (resendBtn) {
    var label = resendBtn.textContent;
    var endsAt = Date.now() + parseInt(resendBtn.dataset.cooldown || '60', 10) * 1000;
    resendBtn.disabled = true;
    var tick = setInterval(function () {
      var remaining = Math.ceil((endsAt - Date.now()) / 1000);
      if (remaining <= 0) {
        clearInterval(tick);
        resendBtn.disabled = false;
        resendBtn.textContent = label;
      } else {
        resendBtn.textContent = 'আবার পাঠান (' + remaining + ')';
      }
    }, 250);
  }

  // ── FAB (quick-add) menu ───────────────────────────────────────────────
  var fabGroup = document.querySelector('[data-fab-group]');
  var fabToggle = document.querySelector('[data-fab-toggle]');
  var fabBackdrop = document.querySelector('[data-fab-backdrop]');
  if (fabGroup && fabToggle) {
    function closeFab() { fabGroup.dataset.open = 'false'; fabToggle.setAttribute('aria-expanded', 'false'); }
    fabToggle.addEventListener('click', function () {
      var open = fabGroup.dataset.open === 'true';
      fabGroup.dataset.open = open ? 'false' : 'true';
      fabToggle.setAttribute('aria-expanded', String(!open));
    });
    if (fabBackdrop) { fabBackdrop.addEventListener('click', closeFab); }
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { closeFab(); } });
  }

  // ── Task / subtask / habit check toggles — progressive: fetch if possible,
  //    otherwise the surrounding <form> just submits normally. ─────────────
  document.querySelectorAll('[data-toggle-url]').forEach(function (el) {
    el.addEventListener('click', function (e) {
      e.preventDefault();
      var token = document.querySelector('meta[name="csrf-token"]');
      fetch(el.dataset.toggleUrl, {
        method: 'POST',
        headers: { 'X-CSRF-Token': token ? token.content : '', 'Accept': 'application/json' },
      }).then(function () { window.location.reload(); }).catch(function () { window.location.reload(); });
    });
  });

  // ── Notification bell dropdown ─────────────────────────────────────────
  var bell = document.querySelector('[data-bell-toggle]');
  var panel = document.querySelector('[data-bell-panel]');
  if (bell && panel) {
    bell.addEventListener('click', function () {
      panel.hidden = !panel.hidden;
      var dot = bell.querySelector('.bell-dot');
      if (!panel.hidden && dot) {
        dot.remove();
        var token = document.querySelector('meta[name="csrf-token"]');
        fetch('/app/notifications/read', {
          method: 'POST',
          headers: { 'X-CSRF-Token': token ? token.content : '' },
        }).catch(function () {});
      }
    });
    document.addEventListener('click', function (e) {
      if (!panel.hidden && !panel.contains(e.target) && e.target !== bell) { panel.hidden = true; }
    });
  }

  // ── Focus timer (Pomodoro-style, client-side countdown) ────────────────
  // Anchored to a real end-timestamp for the same reason as the resend
  // cooldown above — a decrement-per-tick counter falls behind in a
  // backgrounded tab, then races to catch up (looks like it's "counting
  // faster") the moment the tab regains focus. Recomputing from Date.now()
  // every tick self-corrects regardless of any throttled/missed ticks.
  var dial = document.querySelector('[data-focus-dial]');
  if (dial) {
    var timeEl = dial.querySelector('[data-focus-time]');
    var startBtn = document.querySelector('[data-focus-start]');
    var stopBtn = document.querySelector('[data-focus-stop]');
    var minutesInput = document.querySelector('[data-focus-minutes]');
    var durationInput = document.querySelector('[data-focus-duration-field]');
    var totalSec = 0, remaining = 0, timer = null, startedAt = 0, endsAt = 0;

    function render() {
      var m = Math.floor(remaining / 60), s = remaining % 60;
      timeEl.textContent = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
      var pct = totalSec > 0 ? Math.round(((totalSec - remaining) / totalSec) * 360) : 0;
      dial.style.setProperty('--deg', pct + 'deg');
      dial.querySelector('.focus-dial').style.background = 'conic-gradient(var(--indigo) ' + pct + 'deg, var(--paper-dim) ' + pct + 'deg)';
    }

    if (startBtn) {
      startBtn.addEventListener('click', function () {
        if (timer) { clearInterval(timer); }
        var mins = parseInt((minutesInput && minutesInput.value) || '25', 10);
        totalSec = mins * 60;
        remaining = totalSec;
        startedAt = Date.now();
        endsAt = startedAt + totalSec * 1000;
        render();
        startBtn.hidden = true;
        if (stopBtn) { stopBtn.hidden = false; }
        timer = setInterval(function () {
          remaining = Math.max(0, Math.ceil((endsAt - Date.now()) / 1000));
          render();
          if (remaining <= 0) { finish(); }
        }, 250);
      });
    }

    function finish() {
      clearInterval(timer);
      var elapsed = Math.round((Date.now() - startedAt) / 1000);
      if (durationInput) { durationInput.value = String(elapsed); }
      var form = document.querySelector('[data-focus-form]');
      if (form) { form.submit(); }
    }

    if (stopBtn) {
      stopBtn.addEventListener('click', finish);
    }
  }

  // ── Web Push opt-in ─────────────────────────────────────────────────────
  var pushBtn = document.querySelector('[data-push-subscribe]');
  if (pushBtn && 'serviceWorker' in navigator && 'PushManager' in window) {
    pushBtn.hidden = false;
    pushBtn.addEventListener('click', function () {
      var configEl = document.getElementById('push-config');
      var config = configEl ? JSON.parse(configEl.textContent) : {};
      if (!config.vapidPublicKey) { return; }

      navigator.serviceWorker.register('/sw.js').then(function (reg) {
        return reg.pushManager.subscribe({
          userVisibleOnly: true,
          applicationServerKey: urlBase64ToUint8Array(config.vapidPublicKey),
        });
      }).then(function (sub) {
        var token = document.querySelector('meta[name="csrf-token"]');
        return fetch('/app/settings/push/subscribe', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': token ? token.content : '' },
          body: JSON.stringify(sub),
        });
      }).then(function () {
        pushBtn.textContent = 'Notification চালু হয়েছে ✓';
        pushBtn.disabled = true;
      }).catch(function () {
        pushBtn.textContent = 'Notification Allow করা যায়নি';
      });
    });
  }

  function urlBase64ToUint8Array(base64String) {
    var padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    var rawData = window.atob(base64);
    var outputArray = new Uint8Array(rawData.length);
    for (var i = 0; i < rawData.length; ++i) { outputArray[i] = rawData.charCodeAt(i); }
    return outputArray;
  }
})();
