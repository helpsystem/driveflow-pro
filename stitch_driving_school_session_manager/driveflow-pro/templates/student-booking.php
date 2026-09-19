<?php
defined('ABSPATH') || exit;

/**
 * DriveFlow Pro - Student Magic Self-Booking Portal
 * Accessible via [driveflow_magic_booking] with secure personal token.
 */

$token = sanitize_text_field(wp_unslash($_GET['token'] ?? ''));
$student = null;
if (!empty($token)) {
    $student = DriveFlow_Credit_Manager::get_student_by_token($token);
}

global $wpdb;
$instructors = $wpdb->get_results("SELECT name, phone FROM {$wpdb->prefix}driveflow_instructors WHERE status = 'active' ORDER BY name ASC", ARRAY_A);
$settings = get_option('driveflow_pro_settings', array());
$school_name = $settings['school_name'] ?? get_bloginfo('name') ?: 'DriveFlow Academy';
$logo_url = $settings['logo_url'] ?? '';
?>

<div class="df-magic-booking-wrap" id="df-magic-booking-app" dir="ltr">
    <!-- Header -->
    <div class="df-portal-header">
        <?php if ($logo_url) : ?>
            <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($school_name); ?>" class="df-portal-logo">
        <?php endif; ?>
        <h1 class="df-portal-school-name"><?php echo esc_html($school_name); ?></h1>
        <p class="df-portal-subtitle">Student Driving Lesson Self-Service Scheduler</p>
    </div>

    <?php if (!$student) : ?>
        <!-- Invalid or Missing Token Screen -->
        <div class="df-portal-card" style="text-align:center;padding:48px 24px;">
            <div style="font-size:48px;margin-bottom:12px;">🔒</div>
            <h2 style="font-size:22px;color:#0f172a;margin:0 0 10px 0;">Secure Booking Link Required</h2>
            <p style="color:#64748b;font-size:15px;max-width:480px;margin:0 auto 24px auto;line-height:1.6;">
                Please access this scheduling portal using the private booking link sent to you by the driving academy via SMS or email.
            </p>
            <form method="get" action="" style="max-width:400px;margin:0 auto;display:flex;gap:8px;">
                <input type="text" name="token" placeholder="Paste your booking token here..." required style="flex:1;padding:12px 14px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;">
                <button type="submit" class="df-portal-btn df-portal-btn-primary">Verify Link</button>
            </form>
        </div>
    <?php else : 
        $remaining = (int)$student['remaining_sessions'];
        $total = (int)$student['total_sessions'];
        $completed = (int)$student['completed_sessions'];
        $pct = ($total > 0) ? min(100, round(($completed / $total) * 100)) : 0;
    ?>
        <!-- Student Package & Balance Summary Card -->
        <div class="df-portal-card df-student-summary-card">
            <div class="df-summary-left">
                <span class="df-summary-badge">STUDENT REGISTRY</span>
                <h2 class="df-student-name"><?php echo esc_html($student['name']); ?></h2>
                <div class="df-student-meta">
                    <span>Course: <strong><?php echo esc_html($student['package_name']); ?></strong></span>
                    <span>•</span>
                    <span>Permit: <strong><?php echo esc_html($student['license_number'] ?: 'On File'); ?></strong></span>
                </div>
            </div>
            <div class="df-summary-right">
                <div class="df-balance-badge <?php echo ($remaining > 0) ? 'df-balance-available' : 'df-balance-empty'; ?>">
                    <span class="df-balance-num"><?php echo $remaining; ?></span>
                    <span class="df-balance-label">Remaining Lessons</span>
                </div>
                <div class="df-balance-progress-wrap">
                    <div class="df-progress-bar-bg">
                        <div class="df-progress-bar-fill" style="width: <?php echo $pct; ?>%;"></div>
                    </div>
                    <span class="df-progress-caption"><?php echo $completed; ?> of <?php echo $total; ?> completed (<?php echo $pct; ?>%)</span>
                </div>
            </div>
        </div>

        <?php if ($remaining <= 0) : ?>
            <div class="df-portal-card" style="text-align:center;padding:36px 20px;border-left:4px solid #f59e0b;">
                <h3 style="margin:0 0 8px 0;color:#b45309;">Course Package Fully Completed! 🎉</h3>
                <p style="color:#78350f;margin:0;font-size:14px;">
                    You have utilized all scheduled sessions in your current course. If you need brush-up lessons or test-day car rental, please contact academy administration to add extra sessions.
                </p>
            </div>
        <?php else : ?>
            <!-- Self-Booking Step-by-Step Wizard -->
            <div class="df-portal-card df-booking-wizard-card">
                <div class="df-wizard-header">
                    <h3 style="margin:0 0 6px 0;font-size:19px;color:#0f172a;">Schedule a 2-Hour Driving Lesson</h3>
                    <p style="margin:0;color:#64748b;font-size:14px;">
                        Operating daily from <strong>8:00 AM to 8:00 PM</strong>. Select your preferred instructor and date to view live open slots.
                    </p>
                </div>

                <div class="df-wizard-grid">
                    <!-- Step 1: Instructor & Date -->
                    <div class="df-wizard-controls">
                        <div class="df-form-group">
                            <label for="df-book-instructor">1. Choose Instructor</label>
                            <select id="df-book-instructor" class="df-portal-select">
                                <?php foreach ($instructors as $idx => $ins) : ?>
                                    <option value="<?php echo esc_attr($ins['name']); ?>" <?php selected($idx, 0); ?>>
                                        <?php echo esc_html($ins['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="df-form-group">
                            <label for="df-book-date">2. Select Lesson Date</label>
                            <input type="date" id="df-book-date" class="df-portal-input" min="<?php echo esc_attr(date('Y-m-d', strtotime('+1 day'))); ?>" value="<?php echo esc_attr(date('Y-m-d', strtotime('+1 day'))); ?>">
                        </div>

                        <div class="df-form-group">
                            <label for="df-book-topic">3. Focus / Lesson Topic (Optional)</label>
                            <input type="text" id="df-book-topic" class="df-portal-input" placeholder="e.g. Highway Driving, Parking, Road Test Prep" value="Standard Behind-The-Wheel Driving Lesson">
                        </div>
                    </div>

                    <!-- Step 2: Available 2-Hour Time Slots -->
                    <div class="df-wizard-slots-panel">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                            <label style="font-weight:700;color:#0f172a;font-size:14px;">
                                4. Select Open 2-Hour Time Slot
                            </label>
                            <span id="df-slots-loading-indicator" style="display:none;font-size:12px;color:#2563eb;">Checking availability...</span>
                        </div>

                        <div id="df-slots-container" class="df-slots-grid">
                            <!-- Populated via AJAX -->
                        </div>

                        <div id="df-selected-slot-preview" style="display:none;margin-top:16px;background:#f0fdf4;border:1px solid #bbf7d0;padding:12px 16px;border-radius:8px;">
                            <div style="font-size:13px;color:#166534;font-weight:600;">Selected Session:</div>
                            <div id="df-selected-slot-text" style="font-size:15px;color:#14532d;font-weight:800;margin-top:2px;"></div>
                        </div>

                        <button type="button" id="df-confirm-booking-btn" class="df-portal-btn df-portal-btn-primary" style="width:100%;margin-top:18px;padding:14px;font-size:16px;" disabled>
                            Confirm Driving Lesson Booking
                        </button>
                    </div>
                </div>

                <!-- Booking Feedback Alert -->
                <div id="df-booking-feedback" style="display:none;margin-top:18px;padding:14px;border-radius:8px;font-size:14px;"></div>
            </div>
        <?php endif; ?>

        <!-- Student Lessons Log & History -->
        <div class="df-portal-card" style="margin-top:24px;">
            <h3 style="margin:0 0 16px 0;font-size:18px;color:#0f172a;">Your Driving Lessons on Record</h3>
            <?php if (empty($student['sessions'])) : ?>
                <p style="color:#94a3b8;font-size:14px;text-align:center;padding:24px 0;">No lessons recorded yet. Use the scheduler above to book your first lesson!</p>
            <?php else : ?>
                <div class="df-table-responsive">
                    <table class="df-portal-table">
                        <thead>
                            <tr>
                                <th>Lesson #</th>
                                <th>Date & Time</th>
                                <th>Instructor</th>
                                <th>Fleet Car</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="df-student-sessions-tbody">
                            <?php foreach ($student['sessions'] as $s) : ?>
                                <tr>
                                    <td><strong>Lesson #<?php echo esc_html($s['session_number']); ?></strong><br><span style="color:#64748b;font-size:12px;"><?php echo esc_html($s['lesson_topic']); ?></span></td>
                                    <td><?php echo esc_html(date_i18n('l, M j, Y @ g:i A', strtotime($s['scheduled_start']))); ?></td>
                                    <td><?php echo esc_html($s['instructor_name'] ?: 'Academy Staff'); ?></td>
                                    <td><code><?php echo esc_html($s['plate_number'] ?: 'School Car'); ?></code></td>
                                    <td>
                                        <span class="df-badge df-badge-<?php echo esc_attr($s['status']); ?>">
                                            <?php echo esc_html(strtoupper($s['status'])); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
(function($) {
    'use strict';
    $(document).ready(function() {
        var token = "<?php echo esc_js($token); ?>";
        if (!token) return;

        var selectedStart = null;
        var selectedEnd = null;

        function loadAvailableSlots() {
            var date = $('#df-book-date').val();
            var instructor = $('#df-book-instructor').val();
            var $container = $('#df-slots-container');
            var $loading = $('#df-slots-loading-indicator');
            var $confirmBtn = $('#df-confirm-booking-btn');
            var $preview = $('#df-selected-slot-preview');

            selectedStart = null;
            selectedEnd = null;
            $confirmBtn.prop('disabled', true);
            $preview.hide();
            $loading.show();
            $container.html('<div style="grid-column:1/-1;text-align:center;padding:20px;color:#94a3b8;">Loading open 2-hour slots...</div>');

            var ajaxUrl = (window.DriveFlowPro && window.DriveFlowPro.ajaxurl) ? window.DriveFlowPro.ajaxurl : '/wp-admin/admin-ajax.php';

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'driveflow_get_magic_slots',
                    token: token,
                    date: date,
                    instructor: instructor
                },
                success: function(res) {
                    $loading.hide();
                    if (res && res.success && res.data) {
                        var slots = res.data.slots;
                        if (!slots || !slots.length) {
                            $container.html('<div style="grid-column:1/-1;padding:24px;text-align:center;color:#b45309;background:#fffbeb;border:1px solid #fef3c7;border-radius:8px;">No open 2-hour slots available for this instructor on ' + date + '. Please choose another date or instructor.</div>');
                            return;
                        }

                        var html = '';
                        slots.forEach(function(slot) {
                            html += '<button type="button" class="df-slot-btn" data-start="' + slot.start_iso + '" data-end="' + slot.end_iso + '" data-label="' + slot.label + '">';
                            html += '<span class="df-slot-time">' + slot.label + '</span>';
                            html += '<span class="df-slot-duration">2 Hours</span>';
                            html += '</button>';
                        });
                        $container.html(html);

                        // Attach slot click listener
                        $('.df-slot-btn').on('click', function(e) {
                            e.preventDefault();
                            $('.df-slot-btn').removeClass('is-selected');
                            $(this).addClass('is-selected');
                            selectedStart = $(this).data('start').replace('T', ' ') + ':00';
                            selectedEnd = $(this).data('end').replace('T', ' ') + ':00';
                            var label = $(this).data('label');
                            $('#df-selected-slot-text').text(date + ' @ ' + label + ' with Instructor ' + instructor);
                            $preview.fadeIn(200);
                            $confirmBtn.prop('disabled', false);
                        });
                    } else {
                        $container.html('<div style="grid-column:1/-1;padding:20px;color:#dc2626;background:#fef2f2;border-radius:8px;">' + ((res && res.data) || 'Failed to load slots.') + '</div>');
                    }
                },
                error: function() {
                    $loading.hide();
                    $container.html('<div style="grid-column:1/-1;padding:20px;color:#dc2626;">Network error while loading available slots.</div>');
                }
            });
        }

        // Trigger on change
        $('#df-book-instructor, #df-book-date').on('change', function() {
            loadAvailableSlots();
        });

        // Initial load
        if ($('#df-book-date').length) {
            loadAvailableSlots();
        }

        // Confirm Booking Submission
        $('#df-confirm-booking-btn').on('click', function(e) {
            e.preventDefault();
            if (!selectedStart || !selectedEnd) return;

            var $btn = $(this);
            var $feedback = $('#df-booking-feedback');
            var instructor = $('#df-book-instructor').val();
            var topic = $('#df-book-topic').val() || 'Behind-The-Wheel Driving Lesson';
            var ajaxUrl = (window.DriveFlowPro && window.DriveFlowPro.ajaxurl) ? window.DriveFlowPro.ajaxurl : '/wp-admin/admin-ajax.php';

            $btn.prop('disabled', true).text('Securing Booking...');
            $feedback.hide();

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'driveflow_student_self_book',
                    token: token,
                    slot_start: selectedStart,
                    slot_end: selectedEnd,
                    instructor: instructor,
                    lesson_topic: topic
                },
                success: function(res) {
                    if (res && res.success && res.data) {
                        $feedback.removeClass('df-alert-danger').addClass('df-alert-success')
                            .html('<strong>Success!</strong> ' + res.data.message + '<br><small>A confirmation notice has also been dispatched to your email.</small>')
                            .slideDown(200);
                        $btn.text('Booking Confirmed ✓');
                        setTimeout(function() {
                            window.location.reload();
                        }, 2500);
                    } else {
                        $btn.prop('disabled', false).text('Confirm Driving Lesson Booking');
                        $feedback.removeClass('df-alert-success').addClass('df-alert-danger')
                            .html('<strong>Booking Conflict:</strong> ' + ((res && res.data) || 'Selected slot is no longer available.'))
                            .slideDown(200);
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text('Confirm Driving Lesson Booking');
                    $feedback.removeClass('df-alert-success').addClass('df-alert-danger')
                        .html('<strong>Error:</strong> Server communication failed.')
                        .slideDown(200);
                }
            });
        });
    });
})(jQuery);
</script>
