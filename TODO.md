# TODO

## Content

- [ ] **A native Bangla speaker needs to read every string in `views/`
      before launch.** Every sibling app in this workspace flagged this
      independently and it's true here too — the copy in §10 of the spec
      was followed verbatim, but the rest (empty states, error messages,
      admin labels) was written by an AI build session, not a native
      speaker.
- [ ] Confirm the exact §10 landing/OTP-box copy still reads naturally next
      to the feature list this build actually shipped (habit tracker, focus
      timer, daily review) — it was written to match, but a marketing pass
      would help.

## Billing

- [ ] Real BDApps SDP/OTP API contract — not obtained. `BdAppsGateway` is a
      stub that throws on every method (01-BUILD-SPEC.md §13).
      `SUBSCRIPTION_GATEWAY=mock` is hard-blocked in production until this
      lands.
- [ ] BTRC operator prefix map (`config/operators.php`) is unverified —
      confirm 018=Robi, 016=Airtel against the real allocation table
      before rejecting/accepting numbers in production.
- [ ] BDApps-mandated VAT/SD/SC wording — verify current requirement before
      launch; the footer/pricing copy currently says "Incl. VAT, SD & SC"
      without itemizing.
- [ ] SMS provider for Bangladesh unresolved (BDApps-bundled vs. Alpha
      SMS/Banglalink API, etc.) — cost/reliability tradeoff needs a human
      decision. `MockSmsGateway` unblocks everything else meanwhile.

## Before actually shipping this

- [ ] Real Web Push (VAPID + RFC 8291 encryption) — currently a mock that
      writes an in-app notification instead of a real browser push. Needs
      independent crypto review or a vetted library before switching
      `PUSH_GATEWAY` away from `mock` — see docs/FEATURES.md.
- [ ] Self-hosted Hind Siliguri / Inter webfonts — shipped with a system-font
      stack instead (Nirmala UI / Noto Sans Bengali fallback chain). Visually
      solid but not the originally specified typography.
- [ ] Contact form has no dedicated table — messages log to
      `storage/logs/contact-*.log`, shown read-only in `/admin/contact`.
      Fine for low volume; add a real `contact_messages` table + states
      (new/resolved) before volume makes a log file impractical.
- [ ] Change the seeded admin password (`admin@dinsathi.local` /
      `ChangeMe123!`) immediately on any real deploy.
- [ ] Run the full pre-launch checklist in `docs/SECURITY.md` and
      `docs/DEPLOYMENT.md` — `.env` unreachable via HTTP, fresh encryption
      keys generated on the server, session-death-on-unsubscribe verified
      by hand in a second browser.
- [ ] Load-test the cron dispatch path with a synthetic backlog of
      reminders (03-ENV §8's phase-14 acceptance criterion) — not done in
      this build; only tested with a handful of reminders by hand in a
      browser.

## Nice to have

- [ ] Quantity-based habits (not just boolean check-in) — flagged as a
      fast-follow in 01-BUILD-SPEC.md §9, out of scope for v1.
- [ ] "This task only / this and future" prompt when editing a recurring
      template mid-series — the spec calls for it (§8, matching Google
      Calendar's UX), but this build's `RecurrenceService` always treats an
      edit as forward-only (past instances are never mutated, matching the
      spec's actual requirement) without surfacing the choice explicitly in
      the UI. Functionally correct; the UX prompt itself wasn't built.
- [ ] CSV export covers tasks only — habit history and focus-session logs
      aren't exportable yet.
- [ ] Push-notification icons are a single inline SVG
      (`public/assets/img/icon.svg`) rather than the PNG sizes most
      platforms prefer for a service-worker `showNotification()` icon —
      works, but a proper 192/512 PNG set (and app icons for the
      manifest) would look better on more devices.
