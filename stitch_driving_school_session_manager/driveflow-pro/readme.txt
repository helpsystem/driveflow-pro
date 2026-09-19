=== DriveFlow Pro - Driving School Session Manager ===
Contributors: driveflow
Tags: driving school, session manager, appointment booking, wappointment, tv board, instructor form, student portal, scheduling
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.6.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Comprehensive driving school operations platform featuring native appointment availability, vehicle conflict prevention, student lesson package balances, live Smart TV lobby display boards, in-car instructor mobile & tablet app with selfie and digital signature verification, student self-service portal, driving skills evaluation scorecard, and automated HTML email dispatch.

== Description ==

**DriveFlow Pro** is an enterprise-grade WordPress solution built specifically for driving schools, instructors, and academies. It connects course packages and student credits with in-car lesson delivery, student evaluation, vehicle conflict prevention, and real-time waiting room TV displays.

### Key Features:

* **Native Scheduling & Conflict Detection Engine:**
  * Calculates free instructor time slots based on weekly working hours.
  * Real-time vehicle conflict detection: checks whether training cars are on the road and flags them as busy to prevent double-booking.
  * Instant scheduling collision warning alerts in the booking modal.
* **Student Package & Balance Management:**
  * Enrolls students with custom driving packages (e.g. 10-Lesson Course, Intensive 12-Lesson Course).
  * Automatically calculates remaining lessons and completed sessions.
  * Instant Top-Up tool (`+ Add Extra Sessions`) with payment/invoice notes.
  * Detailed credit ledger tracking all package additions and session deductions.
* **Student Self-Service Portal:**
  * Shortcode: `[driveflow_student_portal]`
  * Students can look up their course progress, remaining lesson balances, scheduled sessions, and complete skills scorecards.
* **Smart TV & Monitor Lobby Display Board:**
  * Shortcode: `[driveflow_tv_board]`
  * Real-time session monitoring with 3D embossed vehicle license plate styling.
  * Live digital clock and date display.
  * Built-in Web Speech voice announcer (announces student & instructor arrivals).
  * Web Audio melodic chime alert for newly active lessons.
  * One-click fullscreen mode for wall-mounted Smart TVs and tablets.
  * Announcement ticker marquee for school notices and news.
* **Instructor In-Car Mobile & Tablet App:**
  * Shortcode: `[driveflow_instructor_form]`
  * Clean, touch-optimized responsive layout designed for phones and tablets inside training vehicles.
  * Stopwatch lesson timer with start, pause, and reset controls.
  * Front and rear camera switching (flip camera) for in-car instructor selfie verification.
  * Touch signature canvas with instant clearing and high-DPI scaling.
  * 4-Category driving skills evaluation (Clutch & Speed, Parallel Parking, Steering & Intersections, Traffic Rules).
  * Instructor feedback notes field included in student's scorecard email.
  * Optional GPS location stamping and legal compliance confirmation.
* **Automated Responsive HTML Email Manager:**
  * Booking confirmation dispatch to students with lesson details, instructor name, and vehicle plate.
  * Instructor dispatch notifications.
  * Session completion certificate & skills scorecard email sent automatically when lesson finishes.
  * Cancellation notices.
  * In-admin email testing suite and one-click resend buttons.
* **Modern Executive Admin Dashboard:**
  * Real-time metrics (Total, Active On Road, Upcoming, Wappointment Bookings).
  * Proof modal displaying instructor selfie, student signature, and skills evaluation stars.
  * Instant AJAX status controls (Start, Complete, Cancel) without page reloads.
  * Clean CSV / Excel export.
  * Fleet vehicle, instructor, and student management directories.
* **Two-Way Wappointment Booking Integration (Optional):**
  * Automatically pulls student bookings from Wappointment into scheduled driving sessions and syncs changes seamlessly.

== Installation ==

1. Upload the `driveflow-pro.zip` file via **Plugins > Add New > Upload Plugin** in your WordPress dashboard.
2. Click **Activate Plugin**.
3. DriveFlow Pro automatically provisions the required pages:
   * **Instructor In-Car App:** Contains shortcode `[driveflow_instructor_form]`
   * **Smart TV Lobby Board:** Contains shortcode `[driveflow_tv_board]`
   * **Student Driving Portal:** Contains shortcode `[driveflow_student_portal]`
4. Navigate to **DriveFlow Pro > Settings & Email** to configure school branding, logo, and email templates.

== Shortcodes ==

* `[driveflow_instructor_form]` - In-car mobile and tablet interface for driving instructors.
* `[driveflow_tv_board]` - Smart TV and monitor lobby board for waiting rooms.
* `[driveflow_student_portal]` - Student self-service course progress, remaining lessons, and scorecard logbook.

== Changelog ==

= 1.6.0 =
* Integrated native availability calculation engine and vehicle conflict detection.
* Added student package tracking, remaining lesson balance, and extra session top-ups.
* Added student activity ledger and dedicated `[driveflow_student_portal]` shortcode.
* Added dedicated student management screen with progress bars and transcript drawers.

= 1.5.0 =
* Complete English interface across all admin views, forms, TV display, emails, and alerts.
* Enhanced Smart TV board with English voice announcer and chime alerts.
* Optimized in-car instructor app for mobile and tablet touchscreens with camera flip.

= 1.0.0 =
* Initial release of DriveFlow Pro.
