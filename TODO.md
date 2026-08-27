# TODO

## Content

- [ ] **A native Bangla speaker needs to read every string in `views/`
      before launch.** Every sibling app in this workspace flagged this
      independently and it's true here too — much of the copy was written
      by an AI build session, not a native speaker (empty states, error
      messages, admin labels, and the Phase 1 rebrand/auth copy alike).

## Before actually shipping this

- [ ] Real Web Push (VAPID + RFC 8291 encryption) — currently a mock that
      writes an in-app notification instead of a real browser push. Needs
      independent crypto review or a vetted library before switching
      `PUSH_GATEWAY` away from `mock` — see docs/FEATURES.md.
- [ ] Self-hosted Hind Siliguri / Inter webfonts — shipped with a system-font
      stack instead (Nirmala UI / Noto Sans Bengali fallback chain). Visually
      solid but not the originally specified typography.
- [ ] Real outbound email — `MailerService` currently writes password-reset
      links to `storage/logs/mail-*.log` instead of sending a real email
      (mirrors the old mock-gateway convention). Wire a real SMTP/API
      transport before treating password reset as done for real users.
- [x] Contact form has no dedicated table — messages log to
      `storage/logs/contact-*.log`, shown read-only in `/admin/contact`.
      Fine for low volume; add a real `contact_messages` table + states
      (new/resolved) before volume makes a log file impractical.
      Done: `database/migrations/008_addendum_contact_messages.sql` +
      `ContactMessageRepo` + `/admin/contact` resolve action.
- [x] Subscription/billing removed entirely (Phase 1) — this is now a free,
      login-only app. Both the `planner` base plan and the `sms_reminders`
      add-on are gone, along with the BDApps gateway stub, SMS gateway
      stub, and all related admin pages. Reminders are push-only.
- [x] Phone + OTP auth replaced with email + password (Phase 1) — see
      `AuthController`, `database/migrations/010_phase1_email_auth_and_billing_removal.sql`.
- [ ] Change the seeded admin password (`admin@prohor.local` /
      `ChangeMe123!`) immediately on any real deploy.
- [ ] Run the full pre-launch checklist in `docs/SECURITY.md` and
      `docs/DEPLOYMENT.md` — `.env` unreachable via HTTP, fresh encryption
      keys generated on the server.
- [ ] Load-test the cron dispatch path with a synthetic backlog of
      reminders — not done in this build; only tested with a handful of
      reminders by hand in a browser.

## Nice to have

- [x] Quantity-based habits (not just boolean check-in) — flagged as a
      fast-follow in 01-BUILD-SPEC.md §9, out of scope for v1.
      Done: `database/migrations/009_addendum_quantity_habits.sql` +
      `HabitService::logQuantity()`.
- [x] "This task only / this and future" prompt when editing a recurring
      template mid-series — the spec calls for it (§8, matching Google
      Calendar's UX), but this build's `RecurrenceService` always treats an
      edit as forward-only (past instances are never mutated, matching the
      spec's actual requirement) without surfacing the choice explicitly in
      the UI. Done: `views/app/task-show.php`'s `[data-recur-prompt]` +
      `TaskService::update()`'s `$applyScope`.
- [x] CSV export covers tasks only — habit history and focus-session logs
      aren't exportable yet.
      Done: `SettingsController::export()` now covers all three.
- [ ] Push-notification icons are a single inline SVG
      (`public/assets/img/icon.svg`) rather than the PNG sizes most
      platforms prefer for a service-worker `showNotification()` icon —
      works, but a proper 192/512 PNG set (and app icons for the
      manifest) would look better on more devices.
- [ ] Finish the real Web Push crypto implementation (VAPID/RFC 8291) —
      currently stubbed against the mock gateway.
