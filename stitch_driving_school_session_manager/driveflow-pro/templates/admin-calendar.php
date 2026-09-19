<?php
defined('ABSPATH') || exit;

/**
 * DriveFlow Pro - Interactive Drag-and-Drop Visual Calendar & Scheduler
 */
$now = current_time('timestamp');
?>
<div class="wrap driveflow-admin-wrap" dir="ltr">
    <!-- Top Header Banner -->
    <div class="df-header-banner">
        <div class="df-header-title">
            <span class="df-header-badge">LIVE SCHEDULER</span>
            <h1>Interactive Visual Calendar</h1>
            <p>Drag and drop sessions to reschedule, synchronize instructors & fleet, and block out restricted time slots.</p>
        </div>
        <div style="display:flex;gap:10px;align-items:center;">
            <button type="button" class="df-btn df-btn-secondary" id="df-open-block-modal-btn">
                <span class="dashicons dashicons-calendar-alt" style="margin-top:2px;"></span> Block / Disable Time
            </button>
            <button type="button" class="df-btn df-btn-primary" data-df-open-modal="df-add-session-modal">
                <span class="dashicons dashicons-plus-alt2" style="margin-top:2px;"></span> + Schedule Lesson
            </button>
        </div>
    </div>

    <!-- Calendar View Dimension Switcher & Regional Timezone Gutter -->
    <div class="df-cal-view-modes-bar" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:14px;background:#ffffff;border:1px solid #e2e8f0;border-radius:10px;padding:12px 18px;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
            <span style="font-size:12px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:0.5px;">Schedule Perspective:</span>
            <div class="df-btn-group" role="group" id="df-cal-view-toggle">
                <button type="button" class="df-btn df-btn-sm df-btn-primary is-active" data-cal-view="slots" title="View 7-day schedule organized by standard 2-hour lesson blocks">
                    📅 2-Hour Slots (Week)
                </button>
                <button type="button" class="df-btn df-btn-sm df-btn-secondary" data-cal-view="vehicles" title="View daily grid organized by Maryland training fleet vehicles">
                    🚗 Fleet Vehicles (Day)
                </button>
                <button type="button" class="df-btn df-btn-sm df-btn-secondary" data-cal-view="instructors" title="View daily grid organized by certified driving instructors">
                    👨‍🏫 Instructors (Day)
                </button>
            </div>
        </div>

        <div style="display:flex;align-items:center;gap:10px;">
            <div class="df-tz-badge-pill" id="df-tz-pill" style="background:#f8fafc;border:1px solid #cbd5e1;color:#1e293b;font-size:12px;font-weight:700;padding:5px 12px;border-radius:6px;display:inline-flex;align-items:center;gap:6px;" title="Official academy operating timezone">
                <span style="color:#0284c7;font-size:14px;">🌐</span>
                <span id="df-tz-name">Timezone: America/New_York (EDT / GMT-4)</span>
            </div>
        </div>
    </div>

    <!-- Calendar Controls Bar -->
    <div class="df-calendar-toolbar">
        <div class="df-cal-nav-group">
            <button type="button" class="df-btn df-btn-sm df-btn-secondary" id="df-cal-prev-btn">&larr; Previous Week</button>
            <button type="button" class="df-btn df-btn-sm df-btn-secondary" id="df-cal-today-btn">Current Week</button>
            <button type="button" class="df-btn df-btn-sm df-btn-secondary" id="df-cal-next-btn">Next Week &rarr;</button>
            <span class="df-cal-current-range" id="df-cal-range-label">Loading schedule...</span>
        </div>

        <div class="df-cal-filters-group">
            <label>
                <span style="font-size:12px;font-weight:600;color:#64748b;margin-right:6px;">Instructor:</span>
                <select id="df-cal-instructor-filter" class="df-select-sm">
                    <option value="">All Instructors</option>
                    <?php foreach ($instructors as $ins) : ?>
                        <option value="<?php echo esc_attr($ins['name']); ?>"><?php echo esc_html($ins['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                <span style="font-size:12px;font-weight:600;color:#64748b;margin-right:6px;">Vehicle:</span>
                <select id="df-cal-plate-filter" class="df-select-sm">
                    <option value="">All Fleet</option>
                    <?php foreach ($vehicles as $veh) : ?>
                        <option value="<?php echo esc_attr($veh['plate_number']); ?>"><?php echo esc_html($veh['plate_number']); ?> (<?php echo esc_html($veh['model']); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </label>

            <button type="button" class="df-btn df-btn-sm df-btn-outline" id="df-cal-refresh-btn">
                <span class="dashicons dashicons-update" style="font-size:15px;line-height:1.2;"></span> Sync
            </button>
        </div>
    </div>

    <!-- Legend Bar -->
    <div class="df-calendar-legend">
        <div class="df-legend-item"><span class="df-legend-dot df-dot-upcoming"></span> Upcoming Lesson</div>
        <div class="df-legend-item"><span class="df-legend-dot df-dot-active"></span> Active / In-Car</div>
        <div class="df-legend-item"><span class="df-legend-dot df-dot-completed"></span> Completed</div>
        <div class="df-legend-item"><span class="df-legend-dot df-dot-blocked"></span> Disabled / Blocked Slot</div>
        <div class="df-legend-hint">💡 <em>Tip: Drag any lesson card into a new 2-hour slot to instantly reschedule. Double click an open slot to book.</em></div>
    </div>

    <!-- Visual Calendar Grid Container -->
    <?php if (!empty($students) || !empty($instructors) || !empty($vehicles)) : ?>
    <details class="df-assign-tray" id="df-assign-tray" open>
        <summary>🧲 Assign by drag &amp; drop — drag a chip onto a lesson card</summary>
        <input type="search" id="df-chip-search" class="df-select-sm" placeholder="Filter chips…" aria-label="Filter chips">
        <div class="df-chip-groups">
            <?php if (!empty($instructors)) : ?>
            <div class="df-chip-group"><strong>Instructors</strong>
                <?php foreach ($instructors as $ins) : if (empty($ins['id'])) continue; ?>
                    <span class="df-chip df-chip--instructor" draggable="true" role="button" tabindex="0" data-type="instructor" data-id="<?php echo (int) $ins['id']; ?>" data-name="<?php echo esc_attr($ins['name']); ?>">👤 <?php echo esc_html($ins['name']); ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($vehicles)) : ?>
            <div class="df-chip-group"><strong>Vehicles</strong>
                <?php foreach ($vehicles as $veh) : if (empty($veh['id'])) continue; ?>
                    <span class="df-chip df-chip--vehicle" draggable="true" role="button" tabindex="0" data-type="vehicle" data-id="<?php echo (int) $veh['id']; ?>" data-name="<?php echo esc_attr($veh['plate_number']); ?>">🚗 <?php echo esc_html($veh['plate_number']); ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($students)) : ?>
            <div class="df-chip-group"><strong>Students</strong>
                <?php foreach (array_slice($students, 0, 300) as $stu) : ?>
                    <span class="df-chip df-chip--student" draggable="true" role="button" tabindex="0" data-type="student" data-id="<?php echo (int) $stu['id']; ?>" data-name="<?php echo esc_attr($stu['name']); ?>">🎓 <?php echo esc_html($stu['name']); ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </details>
    <?php endif; ?>

    <div class="df-calendar-board" id="df-calendar-board">
        <div class="df-cal-loading-overlay" id="df-cal-loading">
            <div class="df-spinner"></div>
            <span>Synchronizing live schedule & collisions...</span>
        </div>

        <div class="df-calendar-grid" id="df-calendar-grid">
            <!-- Rendered dynamically by JavaScript -->
        </div>
    </div>
</div>

<!-- Modal: Block / Disable Time Slot -->
<div class="df-modal-backdrop" id="df-block-modal">
    <div class="df-modal-dialog" style="max-width:520px;">
        <div class="df-modal-header">
            <h3>🚫 Block / Disable Operating Time</h3>
            <button type="button" class="df-modal-close" data-df-close-modal>&times;</button>
        </div>
        <div class="df-modal-body">
            <p style="font-size:13px;color:#64748b;margin-top:0;">
                Prevent lessons from being booked during specific windows for vehicle maintenance, instructor time off, or holiday closures.
            </p>
            <form id="df-block-slot-form">
                <div class="df-form-row">
                    <label>Block Target Type</label>
                    <select name="target_type" id="df-block-target-type" required style="width:100%;">
                        <option value="all">Entire Academy (School-Wide Blackout)</option>
                        <option value="instructor">Specific Instructor (Time Off / Medical)</option>
                        <option value="vehicle">Specific Vehicle (Maintenance / Inspection)</option>
                    </select>
                </div>

                <div class="df-form-row" id="df-block-instructor-wrap" style="display:none;">
                    <label>Select Instructor</label>
                    <select name="target_instructor" id="df-block-instructor" style="width:100%;">
                        <?php foreach ($instructors as $ins) : ?>
                            <option value="<?php echo esc_attr($ins['name']); ?>"><?php echo esc_html($ins['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="df-form-row" id="df-block-vehicle-wrap" style="display:none;">
                    <label>Select Vehicle Plate</label>
                    <select name="target_vehicle" id="df-block-vehicle" style="width:100%;">
                        <?php foreach ($vehicles as $veh) : ?>
                            <option value="<?php echo esc_attr($veh['plate_number']); ?>"><?php echo esc_html($veh['plate_number']); ?> - <?php echo esc_html($veh['model']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="df-form-row">
                        <label>Start Date & Time</label>
                        <input type="datetime-local" name="start_time" id="df-block-start-time" required style="width:100%;">
                    </div>
                    <div class="df-form-row">
                        <label>End Date & Time</label>
                        <input type="datetime-local" name="end_time" id="df-block-end-time" required style="width:100%;">
                    </div>
                </div>

                <div class="df-form-row">
                    <label>Reason / Admin Notes</label>
                    <input type="text" name="reason" id="df-block-reason" placeholder="e.g., Brake service & inspection, Doctor appt, Holiday" style="width:100%;">
                </div>

                <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:20px;">
                    <button type="button" class="df-btn df-btn-secondary" data-df-close-modal>Cancel</button>
                    <button type="submit" class="df-btn df-btn-danger" id="df-save-block-btn">Confirm & Block Slot</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Add / Schedule New Lesson -->
<div class="df-modal-backdrop" id="df-add-session-modal">
    <div class="df-modal-dialog">
        <div class="df-modal-header">
            <h3>Schedule Driving Lesson (2-Hour Slot)</h3>
            <button type="button" class="df-modal-close" data-df-close-modal>&times;</button>
        </div>
        <div class="df-modal-body">
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="driveflow_add_session">
                <?php wp_nonce_field('driveflow_add_session'); ?>

                <div class="df-form-row">
                    <label>Student Learner</label>
                    <input list="df-students-datalist" name="student_name" id="df-modal-student-name" class="regular-text" placeholder="Select or type student name" required style="width:100%;">
                    <datalist id="df-students-datalist">
                        <?php foreach ($students as $st) : ?>
                            <option value="<?php echo esc_attr($st['name']); ?>"><?php echo esc_html($st['name']); ?> (<?php echo esc_html($st['remaining_sessions']); ?> lessons remaining)</option>
                        <?php endforeach; ?>
                    </datalist>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="df-form-row">
                        <label>Assigned Instructor</label>
                        <select name="instructor_name" id="df-modal-instructor" style="width:100%;">
                            <option value="">Select Instructor...</option>
                            <?php foreach ($instructors as $ins) : ?>
                                <option value="<?php echo esc_attr($ins['name']); ?>"><?php echo esc_html($ins['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="df-form-row">
                        <label>Fleet Vehicle Plate</label>
                        <select name="plate_number" id="df-modal-plate" style="width:100%;">
                            <option value="">Select Vehicle...</option>
                            <?php foreach ($vehicles as $veh) : ?>
                                <option value="<?php echo esc_attr($veh['plate_number']); ?>"><?php echo esc_html($veh['plate_number']); ?> (<?php echo esc_html($veh['model']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="df-form-row">
                        <label>Start Time (08:00 – 20:00)</label>
                        <input type="datetime-local" name="scheduled_start" id="df-modal-start-time" required style="width:100%;">
                    </div>
                    <div class="df-form-row">
                        <label>End Time (2 Hours Default)</label>
                        <input type="datetime-local" name="scheduled_end" id="df-modal-end-time" required style="width:100%;">
                    </div>
                </div>

                <!-- Live Collision Check Indicator Banner -->
                <div id="df-collision-alert" style="display:none;margin-bottom:12px;padding:10px 14px;border-radius:6px;font-size:13px;"></div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="df-form-row">
                        <label>Lesson Topic / Focus</label>
                        <input type="text" name="lesson_topic" placeholder="e.g. Parallel Parking & Highway Merging" style="width:100%;">
                    </div>
                    <div class="df-form-row">
                        <label>Session Number</label>
                        <input type="number" name="session_number" value="1" min="1" max="50" style="width:100%;">
                    </div>
                </div>

                <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:20px;">
                    <button type="button" class="df-btn df-btn-secondary" data-df-close-modal>Cancel</button>
                    <button type="submit" class="df-btn df-btn-primary" id="df-modal-submit-btn">Schedule Session</button>
                </div>
            </form>
        </div>
    </div>
</div>
