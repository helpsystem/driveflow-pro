# DriveFlow Pro – Changelog

All notable changes to this project follow [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [1.8.6] – Unreleased
### Security
- REST `can_write` permission callback no longer accepts an anonymous `wp_rest` nonce by itself; a logged-in user with `edit_posts` is required.
- Hardened public `ajax_submit_evaluation` endpoint: image data-URLs are type- and size-capped (PNG/JPEG/WebP, 300 KB signature / 1.5 MB selfie); updating a session is restricted to the same instructor and only `upcoming`/`active` sessions.
- Added per-IP throttle (`df_throttle`) to all public AJAX endpoints and the standalone admin login.
- Added `dfEsc()` HTML-escape helper around all record-field concatenations in `admin.js` calendar card and profile popup; explicit `esc_attr`, `esc_html`, and `(int)` casts added in four templates.
- Removed `nopriv` registrations for the instructor evaluation and today-sessions endpoints.
- Added `check_ajax_referer` nonce to the instructor evaluation endpoint.

### Added
- **Instructor field app** now requires a WordPress login; the logged-in instructor's name and licence number are auto-filled server-side and set to read-only in the form. Admins retain full edit access.
- New login card shown to unauthenticated visitors on the `[driveflow_instructor_form]` shortcode page.
- `ajax_assign_to_session` AJAX endpoint + UI tray: admin can drag-and-drop a student, instructor, or vehicle chip onto a calendar session card to assign it (mouse and touch, with collision detection).
- Touch/pen long-press drag for calendar session reschedule (works alongside existing mouse drag; auto-scrolls horizontally).
- `assets/responsive.css` responsive layer: `viewport-fit=cover`, 44 px touch targets, 16 px inputs (stops iOS Safari zoom), scrollable admin tables and modals, kiosk scaling for 1440p/4K monitors via CSS `zoom` and `--df-zoom`.

### Fixed
- TV board fuel gauge no longer shows a fake 75 % when the vehicle fuel level is unknown; the gauge is hidden instead.
- All three standalone HTML documents now use `viewport-fit=cover` and allow pinch-zoom (`user-scalable=no` removed).

### Removed (from version 1.8.6)
- `nopriv` hooks for `driveflow_submit_evaluation` and `driveflow_get_instructor_today_sessions`.

---

## [1.8.5] – (previous release, see plugin header)
