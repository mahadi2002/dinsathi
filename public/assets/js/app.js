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

  // ── Task / subtask / habit check toggles — optimistic: flip the DOM
  //    immediately (checkmark/strike-through or label swap), fire the
  //    fetch() in the background, and only roll the DOM back if the
  //    request actually fails. No more full-page reload on every click. ──
  function csrfToken() {
    var token = document.querySelector('meta[name="csrf-token"]');
    return token ? token.content : '';
  }

  document.querySelectorAll('[data-toggle-url]').forEach(function (el) {
    el.addEventListener('click', function (e) {
      e.preventDefault();
      if (el.dataset.toggleBusy === '1') { return; }
      el.dataset.toggleBusy = '1';

      var mode = el.dataset.toggleMode || 'check';
      var undo;

      if (mode === 'label') {
        var doneClass = el.dataset.toggleDoneClass || 'is-done';
        var wasDone = el.classList.contains(doneClass);
        var doneText = el.dataset.toggleDoneText || '';
        var undoneText = el.dataset.toggleUndoneText || '';
        el.classList.toggle(doneClass, !wasDone);
        el.textContent = !wasDone ? doneText : undoneText;
        undo = function () {
          el.classList.toggle(doneClass, wasDone);
          el.textContent = wasDone ? doneText : undoneText;
        };
      } else {
        var wasChecked = el.classList.contains('task-check--done');
        var row = el.closest('.task-row');
        var titleEl = row ? row.querySelector('[data-toggle-title]') : null;
        el.classList.toggle('task-check--done', !wasChecked);
        el.textContent = !wasChecked ? '✓' : '';
        if (titleEl) { titleEl.classList.toggle('task-title--done', !wasChecked); }
        undo = function () {
          el.classList.toggle('task-check--done', wasChecked);
          el.textContent = wasChecked ? '✓' : '';
          if (titleEl) { titleEl.classList.toggle('task-title--done', wasChecked); }
        };
      }

      fetch(el.dataset.toggleUrl, {
        method: 'POST',
        headers: { 'X-CSRF-Token': csrfToken(), 'Accept': 'application/json' },
      }).then(function (res) {
        if (!res.ok) { undo(); }
      }).catch(function () {
        undo();
      }).finally(function () {
        el.dataset.toggleBusy = '0';
      });
    });
  });

  // ── Recurring-template edit prompt: "this task only / this and future" ──
  // Shown once, right before the edit actually saves, matching the
  // Google-Calendar-style choice the spec (01-BUILD-SPEC.md §8) calls for.
  document.querySelectorAll('form[data-recurring-form]').forEach(function (form) {
    var prompt = document.querySelector('[data-recur-prompt]');
    var scopeField = form.querySelector('[data-apply-scope]');
    if (!prompt || !scopeField) { return; }
    var confirmed = false;

    form.addEventListener('submit', function (e) {
      if (confirmed) { return; }
      e.preventDefault();
      prompt.hidden = false;
    });

    prompt.querySelectorAll('[data-recur-choice]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        scopeField.value = btn.dataset.recurChoice;
        confirmed = true;
        prompt.hidden = true;
        if (form.requestSubmit) { form.requestSubmit(); } else { form.submit(); }
      });
    });

    var cancelBtn = prompt.querySelector('[data-recur-cancel]');
    if (cancelBtn) {
      cancelBtn.addEventListener('click', function () { prompt.hidden = true; });
    }
  });

  // ── Drag-to-reschedule (calendar day/week views) ────────────────────────
  // Pointer Events (pointerdown/pointermove/pointerup/pointercancel) unify
  // mouse, touch and pen behind one code path — unlike HTML5 dragstart/
  // dragover/drop, which never fire on touch at all. Mouse picks the card
  // up as soon as it moves past a small threshold; touch/pen require a
  // brief hold first (so an ordinary scroll gesture isn't mistaken for a
  // drag), then show the same scale/shadow "picked up" cue mobile users
  // expect. Drop targets are found with elementFromPoint() under the
  // pointer, since there's no native dragover to listen for. Same
  // optimistic DOM move + CSRF-safe fetch() PATCH + rollback-on-failure as
  // before; same endpoint, untouched.
  (function () {
    var cards = document.querySelectorAll('[data-task-card]');
    if (!cards.length) { return; }

    var HOLD_MS = 300;        // touch/pen: hold this long before pick-up
    var MOVE_PX = 6;          // mouse: movement past this starts the drag
    var CANCEL_PX = 10;       // touch/pen: movement past this before pick-up cancels it (treat as scroll)

    var active = null; // { card, pointerId, pointerType, startX, startY, pickedUp, holdTimer, dropSlot }

    function clearDropHighlight() {
      document.querySelectorAll('.is-drop-target').forEach(function (el) { el.classList.remove('is-drop-target'); });
    }

    function endDrag() {
      if (!active) { return; }
      if (active.holdTimer) { clearTimeout(active.holdTimer); }
      active.card.classList.remove('is-picked-up');
      active.card.style.transform = '';
      clearDropHighlight();
      active = null;
    }

    function pickUp() {
      active.pickedUp = true;
      active.card.classList.add('is-picked-up');
      if (active.card.setPointerCapture) {
        try { active.card.setPointerCapture(active.pointerId); } catch (err) { /* capture is a nicety, not required */ }
      }
    }

    function reschedule(card, dueDate, dueTime, targetList, prevParent, prevNext) {
      fetch('/app/tasks/' + card.dataset.taskId + '/reschedule', {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-Token': csrfToken(),
        },
        body: JSON.stringify({ due_date: dueDate, due_time: dueTime }),
      }).then(function (res) {
        if (!res.ok) { throw new Error('reschedule failed'); }
        card.dataset.dueTime = dueTime;
      }).catch(function () {
        // Roll back the optimistic move.
        if (prevNext) { prevParent.insertBefore(card, prevNext); } else { prevParent.appendChild(card); }
      });
    }

    function findDropSlot(x, y) {
      var el = document.elementFromPoint(x, y);
      if (!el) { return null; }
      return el.closest('[data-hour-slot]') || el.closest('[data-day-slot]');
    }

    function drop(card, slot) {
      var prevParent = card.parentNode;
      var prevNext = card.nextSibling;

      if (slot.hasAttribute('data-hour-slot')) {
        var dayContainer = document.querySelector('[data-reschedule-date]');
        var list = slot.querySelector('.cal-day__tasks');
        if (!dayContainer || !list) { return; }

        var hour = String(slot.dataset.hourSlot).padStart(2, '0') + ':00';
        var date = dayContainer.dataset.rescheduleDate;
        list.appendChild(card);
        reschedule(card, date, hour, list, prevParent, prevNext);
      } else if (slot.hasAttribute('data-day-slot')) {
        var date2 = slot.dataset.daySlot;
        var time2 = card.dataset.dueTime || '00:00';
        slot.appendChild(card);
        reschedule(card, date2, time2, slot, prevParent, prevNext);
      }
    }

    cards.forEach(function (card) {
      card.addEventListener('pointerdown', function (e) {
        if (e.button !== undefined && e.button > 0) { return; } // ignore right/middle mouse buttons
        if (active) { return; }

        active = {
          card: card,
          pointerId: e.pointerId,
          pointerType: e.pointerType,
          startX: e.clientX,
          startY: e.clientY,
          pickedUp: false,
          holdTimer: null,
          dropSlot: null,
        };

        if (e.pointerType !== 'mouse') {
          active.holdTimer = setTimeout(function () {
            if (active && active.card === card && !active.pickedUp) { pickUp(); }
          }, HOLD_MS);
        }
      });

      // A drag that actually moved the card must not also fire the card's
      // (or its <a href> in week view) normal click/navigate behaviour.
      card.addEventListener('click', function (e) {
        if (card.dataset.justDragged === '1') {
          delete card.dataset.justDragged;
          e.preventDefault();
          e.stopPropagation();
        }
      }, true);
    });

    document.addEventListener('pointermove', function (e) {
      if (!active || active.pointerId !== e.pointerId) { return; }

      var dx = e.clientX - active.startX;
      var dy = e.clientY - active.startY;

      if (!active.pickedUp) {
        if (active.pointerType === 'mouse') {
          if (Math.hypot(dx, dy) > MOVE_PX) { pickUp(); }
        } else if (Math.hypot(dx, dy) > CANCEL_PX) {
          // Moved too far before the hold fired — this is a scroll, not a drag.
          clearTimeout(active.holdTimer);
          active = null;
        }
        return;
      }

      e.preventDefault();
      active.card.style.transform = 'translate(' + dx + 'px, ' + dy + 'px) scale(1.04)';

      clearDropHighlight();
      active.dropSlot = findDropSlot(e.clientX, e.clientY);
      if (active.dropSlot) { active.dropSlot.classList.add('is-drop-target'); }
    }, { passive: false });

    document.addEventListener('pointerup', function (e) {
      if (!active || active.pointerId !== e.pointerId) { return; }

      var card = active.card;
      var pickedUp = active.pickedUp;
      var dropSlot = active.dropSlot;
      endDrag();

      if (!pickedUp) { return; }
      card.dataset.justDragged = '1';
      if (dropSlot) { drop(card, dropSlot); }
    });

    document.addEventListener('pointercancel', function (e) {
      if (active && active.pointerId === e.pointerId) { endDrag(); }
    });
  })();

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
