# Development

## Conventions

- Controllers stay thin — parse input, call one `Service` method, return a
  `Response`. If a controller method is doing more than that, the logic
  belongs in a `Service`.
- All SQL lives in `Repositories/`, nowhere else.
- Every echoed value goes through `e()`. Every dynamic "this would be an
  inline style" goes through a pre-generated CSS utility class instead —
  see the top of `public/assets/css/app.css` for the token/utility system,
  and docs/SECURITY.md for why.
- `due_at`/`fire_at`/`created_at`/etc. are UTC in the database, always.
  When displaying one in a view, use `bn_date_utc($col, $withTime)`
  (`app/Core/Helpers.php`) — **not** `bn_date()` directly, which assumes
  its input is already Dhaka-local and will silently show the wrong hour
  on a raw UTC column. See docs/ARCHITECTURE.md for the story of how this
  actually shipped once during this build and was caught by testing a real
  task card in a browser, not by reading the code.

## Local dev

See STARTING.md for the two-terminal setup. Quick reference:

```bash
cd public && C:\xampp\php\php.exe -S 127.0.0.1:8030 router-dev.php
```

- Rate-limiter reset while testing: `TRUNCATE TABLE rate_limits;`
- `php -l <file>` on every touched file before calling anything done —
  cheap, catches typos that would otherwise only surface as a 500 on the
  one request path that hits them.

## Testing

- `php tests/smoke.php` — Crypto round-trip, blind-index stability, CSRF
  comparison, msisdn validation, RRuleLite recurrence math (including the
  MONTHLY short-month clamp), DateBD UTC↔Dhaka round-trip. Run after
  touching `Crypto`, `Csrf`, `Validator`, `RRuleLite`, or `DateBD`. Not full
  coverage — the "did I just break something load-bearing" gate.
- **Actually drive changed features in a browser before calling anything
  done.** This build's own history is the argument for this: `php -l`
  passed on every file, but the register→OTP→dashboard flow first threw
  `Call to undefined method Request::body()` (Validator call sites assumed
  a method that didn't exist yet), and task due-times displayed 6 hours off
  until a real task card was inspected in a browser and compared against
  what was actually typed into the form. Neither would have been caught by
  static review alone.

## Known gaps to fill before treating this as "done"

- Self-hosted `Hind Siliguri` / `Inter` webfonts — see docs/FEATURES.md.
  `app.css`'s `--font-body`/`--font-display` tokens are already the single
  place to swap them in once the `.woff2` files are vendored into
  `public/assets/fonts/`.
- A native Bangla speaker should read every string in `views/` before
  launch — flagged independently by every sibling app's TODO.md in this
  workspace, every time, without fail. Put it on the pre-launch checklist
  from day one instead of rediscovering it at the end (see PLAYBOOK.txt
  §7).
- `tests/smoke.php` doesn't cover streak math (`HabitService::streak()`)
  directly, since that needs seeded historical `habit_logs` fixtures rather
  than pure functions. If streak logic changes, test it by hand-seeding a
  few `habit_logs` rows and checking `/app/habits` in a browser.
