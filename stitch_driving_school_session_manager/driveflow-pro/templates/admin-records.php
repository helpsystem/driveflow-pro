<?php
defined('ABSPATH') || exit;

// Retrieve stats for the top cards
global $wpdb;
$table = $this->table();
$stats = $wpdb->get_row("SELECT 
    COUNT(*) as total,
    SUM(status = 'upcoming') as upcoming,
    SUM(status = 'active') as active,
    SUM(status = 'completed') as completed,
    SUM(wappointment_id > 0) as wapp_total
FROM {$table}", ARRAY_A);

// Fetch students, instructors, and vehicles for the Add Session modal dropdowns
$students_list = $wpdb->get_results("SELECT id, name, phone, package_name, total_sessions, completed_sessions FROM {$wpdb->prefix}driveflow_students WHERE status = 'active' ORDER BY name ASC LIMIT 200", ARRAY_A);
$instructors_list = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}driveflow_instructors WHERE status = 'active' ORDER BY name ASC LIMIT 100", ARRAY_A);
$vehicles_list = $wpdb->get_results("SELECT id, plate_number, model FROM {$wpdb->prefix}driveflow_vehicles WHERE status = 'active' ORDER BY plate_number ASC LIMIT 100", ARRAY_A);

$current_status = sanitize_key($_GET['status'] ?? '');
$current_date_filter = sanitize_key($_GET['date_filter'] ?? '');
?>

<div class="wrap driveflow-admin-wrap" dir="ltr">
    <!-- Header Banner -->
    <div class="df-header-banner">
        <div class="df-header-title">
            <div>
                <h1>DriveFlow Pro · Driving Sessions & Student Scorecards</h1>
                <p>Enterprise driving school session tracking, skill evaluations, automated email dispatch, and online booking synchronization.</p>
            </div>
        </div>
        <div class="df-header-actions">
            <button type="button" class="df-btn df-btn-primary" data-df-open-modal="df-add-session-modal">
                <span class="dashicons dashicons-plus-alt2"></span> Add New Session
            </button>
            <button type="button" class="df-btn df-btn-wapp" id="df-sync-wappointment-btn">
                <span class="dashicons dashicons-update"></span> Sync Wappointment
            </button>
            <?php 
            $export_url = add_query_arg(array(
                'action'      => 'driveflow_export_csv',
                's'           => $search,
                'status'      => $current_status,
                'date_filter' => $current_date_filter
            ), admin_url('admin-post.php'));
            ?>
            <a href="<?php echo esc_url(wp_nonce_url($export_url, 'driveflow_export_csv')); ?>" class="df-btn df-btn-secondary">
                <span class="dashicons dashicons-download"></span> Export CSV / Excel
            </a>
            <button type="button" class="df-btn df-btn-secondary" onclick="window.print();">
                <span class="dashicons dashicons-printer"></span> Print Log
            </button>
        </div>
    </div>

    <!-- Live Statistics Cards -->
    <div class="df-stats-grid">
        <div class="df-stat-card">
            <div class="df-stat-info">
                <h4>Total Sessions</h4>
                <div class="df-stat-number"><?php echo esc_html($stats['total'] ?: 0); ?></div>
            </div>
            <div class="df-stat-icon total">🚗</div>
        </div>
        <div class="df-stat-card">
            <div class="df-stat-info">
                <h4>Active On Road</h4>
                <div class="df-stat-number" style="color:#16a34a;"><?php echo esc_html($stats['active'] ?: 0); ?></div>
            </div>
            <div class="df-stat-icon active">⏱️</div>
        </div>
        <div class="df-stat-card">
            <div class="df-stat-info">
                <h4>Upcoming Scheduled</h4>
                <div class="df-stat-number" style="color:#d97706;"><?php echo esc_html($stats['upcoming'] ?: 0); ?></div>
            </div>
            <div class="df-stat-icon upcoming">📅</div>
        </div>
        <div class="df-stat-card">
            <div class="df-stat-info">
                <h4>Wappointment Bookings</h4>
                <div class="df-stat-number" style="color:#9333ea;"><?php echo esc_html($stats['wapp_total'] ?: 0); ?></div>
            </div>
            <div class="df-stat-icon wapp">⚡</div>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="df-toolbar">
        <form method="get" class="df-filters-form">
            <input type="hidden" name="page" value="driveflow-pro-records">
            <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search student, instructor, plate, or lesson...">
            <select name="status">
                <option value="">All Statuses</option>
                <option value="upcoming" <?php selected($current_status, 'upcoming'); ?>>Upcoming</option>
                <option value="active" <?php selected($current_status, 'active'); ?>>Active (On Road)</option>
                <option value="completed" <?php selected($current_status, 'completed'); ?>>Completed</option>
                <option value="cancelled" <?php selected($current_status, 'cancelled'); ?>>Cancelled</option>
            </select>
            <select name="date_filter">
                <option value="">All Dates</option>
                <option value="today" <?php selected($current_date_filter, 'today'); ?>>Today</option>
                <option value="this_week" <?php selected($current_date_filter, 'this_week'); ?>>This Week</option>
            </select>
            <button type="submit" class="df-btn df-btn-secondary">
                <span class="dashicons dashicons-filter"></span> Apply Filter
            </button>
            <?php if ($search || $current_status || $current_date_filter) : ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=driveflow-pro-records')); ?>" class="df-btn df-btn-secondary df-btn-sm">Reset Filter</a>
            <?php endif; ?>
        </form>

        <div style="display:flex;align-items:center;gap:12px;">
            <div class="df-per-page-select">
                <span>Show</span>
                <select onchange="location.href=this.value;">
                    <?php foreach (array(10, 15, 25, 50, 100) as $pp) : ?>
                        <option value="<?php echo esc_url(add_query_arg(array('per_page' => $pp, 'paged' => 1))); ?>" <?php selected($per_page ?? 15, $pp); ?>>
                            <?php echo esc_html($pp); ?> / page
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <a class="button button-small" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=driveflow_seed_demo'), 'driveflow_seed_demo')); ?>">Seed Demo Data</a>
                <a class="button button-small" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=driveflow_clear_demo'), 'driveflow_clear_demo')); ?>" onclick="return confirm('Are you sure you want to clear all demo records?');">Clear Demo</a>
            </div>
        </div>
    </div>

    <!-- Sessions Data Table -->
    <div class="df-table-container">
        <table class="df-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Student</th>
                    <th>Instructor</th>
                    <th>Vehicle Plate</th>
                    <th>Topic & Lesson #</th>
                    <th>Schedule</th>
                    <th>Status</th>
                    <th>Actions & Proof</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows) : ?>
                <tr>
                    <td colspan="8" style="text-align:center;padding:40px 20px;color:#94a3b8;">
                        No driving sessions found. Click <strong>"Add New Session"</strong> or <strong>"Sync Wappointment"</strong> to populate your schedule.
                    </td>
                </tr>
            <?php else : ?>
                <?php foreach ($rows as $row) : 
                    $status_class = in_array($row['status'], array('upcoming','active','completed','cancelled'), true) ? $row['status'] : 'upcoming';
                    $is_wapp = !empty($row['wappointment_id']);
                ?>
                <tr>
                    <td><strong>#<?php echo esc_html($row['id']); ?></strong></td>
                    <td>
                        <strong><?php echo esc_html($row['student_name']); ?></strong>
                        <?php if ($is_wapp) : ?>
                            <span class="df-badge-wapp" title="Booked online via Wappointment">Wappointment #<?php echo esc_html($row['wappointment_id']); ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo esc_html($row['instructor_name'] ?: 'Unassigned'); ?></td>
                    <td>
                        <?php echo DriveFlow_Pro::render_maryland_plate($row['plate_number'], 'sm'); ?>
                    </td>
                    <td>
                        <div><?php echo esc_html($row['lesson_topic'] ?: 'Driving Lesson'); ?></div>
                        <small style="color:#64748b;">Lesson <?php echo esc_html($row['session_number']); ?></small>
                    </td>
                    <td>
                        <div><strong><?php echo esc_html(date_i18n('M j, Y - g:i A', strtotime($row['scheduled_start']))); ?></strong></div>
                        <small style="color:#64748b;">to <?php echo esc_html(date_i18n('g:i A', strtotime($row['scheduled_end']))); ?></small>
                    </td>
                    <td>
                        <span class="df-badge df-badge-<?php echo esc_attr($status_class); ?>">
                            <?php echo esc_html(strtoupper($row['status'])); ?>
                        </span>
                    </td>
                    <td>
                        <div class="df-actions-cell">
                            <!-- View Details / Proof (Selfie + Signature + Skills) -->
                            <button type="button" class="df-btn df-btn-secondary df-btn-sm df-view-details-btn"
                                data-session-id="<?php echo esc_attr($row['id']); ?>"
                                data-student="<?php echo esc_attr($row['student_name']); ?>"
                                data-instructor="<?php echo esc_attr($row['instructor_name']); ?>"
                                data-plate="<?php echo esc_attr($row['plate_number']); ?>"
                                data-status="<?php echo esc_attr($row['status']); ?>"
                                data-date="<?php echo esc_attr($row['scheduled_start']); ?>"
                                data-selfie="<?php echo esc_attr($row['selfie'] ?? ''); ?>"
                                data-signature="<?php echo esc_attr($row['signature'] ?? ''); ?>"
                                data-form-data="<?php echo esc_attr($row['form_data'] ?? '{}'); ?>"
                                title="View lesson proof, skills evaluation, selfie, and signature">
                                👁️ Proof & Skills
                            </button>

                            <!-- Manual Edit Button (Admin Override) -->
                            <button type="button" class="df-btn df-btn-secondary df-btn-sm df-edit-session-btn"
                                data-session-id="<?php echo esc_attr($row['id']); ?>"
                                title="Edit session details, reschedule, override instructor grading and notes">
                                ✏️ Edit
                            </button>

                            <!-- Resend Email Button -->
                            <button type="button" class="df-btn df-btn-email df-btn-sm df-resend-email-btn"
                                data-session-id="<?php echo esc_attr($row['id']); ?>"
                                title="Resend confirmation or scorecard email to student & instructor">
                                ✉️ Email
                            </button>

                            <!-- Quick Action Buttons -->
                            <?php if ('upcoming' === $row['status']) : ?>
                                <button type="button" class="df-btn df-btn-success-sm df-btn-sm df-quick-status-btn" data-session-id="<?php echo esc_attr($row['id']); ?>" data-status="active" title="Start Lesson (On Road)">
                                    ▶ Start
                                </button>
                            <?php elseif ('active' === $row['status']) : ?>
                                <button type="button" class="df-btn df-btn-primary df-btn-sm df-quick-status-btn" data-session-id="<?php echo esc_attr($row['id']); ?>" data-status="completed" title="Complete & Issue Scorecard">
                                    ✓ Complete
                                </button>
                            <?php endif; ?>

                            <?php if ('completed' !== $row['status'] && 'cancelled' !== $row['status']) : ?>
                                <button type="button" class="df-btn df-btn-danger-sm df-btn-sm df-quick-status-btn" data-session-id="<?php echo esc_attr($row['id']); ?>" data-status="cancelled" title="Cancel Session">
                                    ✕
                                </button>
                            <?php endif; ?>

                            <!-- Permanent Delete Button -->
                            <button type="button" class="df-btn df-btn-danger-sm df-btn-sm df-delete-session-btn"
                                data-session-id="<?php echo esc_attr($row['id']); ?>"
                                data-student="<?php echo esc_attr($row['student_name']); ?>"
                                title="Permanently Delete Session">
                                🗑️ Delete
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>

        <!-- Unified Pagination Bar -->
        <?php if (!empty($total_pages) && $total_pages > 1) : 
            $start_item = (($paged ?? 1) - 1) * ($per_page ?? 15) + 1;
            $end_item = min($total_rows ?? 0, ($paged ?? 1) * ($per_page ?? 15));
            $build_url = function($p) use ($search, $current_status, $current_date_filter, $per_page) {
                return add_query_arg(array(
                    'page' => 'driveflow-pro-records',
                    's' => $search,
                    'status' => $current_status,
                    'date_filter' => $current_date_filter,
                    'per_page' => $per_page ?? 15,
                    'paged' => $p
                ), admin_url('admin.php'));
            };
        ?>
        <div class="df-pagination-bar">
            <div class="df-pagination-info">
                Showing <strong><?php echo esc_html($start_item); ?></strong> to <strong><?php echo esc_html($end_item); ?></strong> of <strong><?php echo esc_html($total_rows); ?></strong> sessions
            </div>
            <div class="df-pagination-controls">
                <a href="<?php echo (($paged ?? 1) > 1) ? esc_url($build_url(($paged ?? 1) - 1)) : '#'; ?>"
                   class="df-page-btn <?php echo (($paged ?? 1) <= 1) ? 'disabled' : ''; ?>">
                    &larr; Prev
                </a>
                <?php
                $r_start = max(1, ($paged ?? 1) - 2);
                $r_end = min($total_pages, ($paged ?? 1) + 2);
                if ($r_start > 1) {
                    echo '<a href="' . esc_url($build_url(1)) . '" class="df-page-btn">1</a>';
                    if ($r_start > 2) echo '<span style="color:#94a3b8;padding:0 4px;">...</span>';
                }
                for ($i = $r_start; $i <= $r_end; $i++) {
                    $is_c = ($i === ($paged ?? 1));
                    echo '<a href="' . esc_url($build_url($i)) . '" class="df-page-btn ' . ($is_c ? 'active' : '') . '">' . esc_html($i) . '</a>';
                }
                if ($r_end < $total_pages) {
                    if ($r_end < $total_pages - 1) echo '<span style="color:#94a3b8;padding:0 4px;">...</span>';
                    echo '<a href="' . esc_url($build_url($total_pages)) . '" class="df-page-btn">' . esc_html($total_pages) . '</a>';
                }
                ?>
                <a href="<?php echo (($paged ?? 1) < $total_pages) ? esc_url($build_url(($paged ?? 1) + 1)) : '#'; ?>"
                   class="df-page-btn <?php echo (($paged ?? 1) >= $total_pages) ? 'disabled' : ''; ?>">
                    Next &rarr;
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal 1: Add New Session -->
<div class="df-modal-backdrop" id="df-add-session-modal">
    <div class="df-modal">
        <div class="df-modal-header">
            <h3>Schedule New Driving Session</h3>
            <button type="button" class="df-modal-close">&times;</button>
        </div>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="driveflow_add_session">
            <?php wp_nonce_field('driveflow_add_session'); ?>
            <div class="df-modal-body">
                <div class="df-form-grid">
                    <!-- Student Selection -->
                    <div class="df-form-group">
                        <label>Student Name *</label>
                        <input list="df-student-options" name="student_name" id="df-modal-student" required placeholder="Select or type student name">
                        <datalist id="df-student-options">
                            <?php foreach ($students_list as $st) : 
                                $rem = max(0, (int)($st['total_sessions'] ?? 0) - (int)($st['completed_sessions'] ?? 0));
                                $pkg = $st['package_name'] ?: 'Standard';
                            ?>
                                <option value="<?php echo esc_attr($st['name']); ?>"><?php echo esc_html(($st['phone'] ? "({$st['phone']}) • " : '') . "{$rem} Lessons Left • {$pkg}"); ?></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>

                    <!-- Instructor Selection -->
                    <div class="df-form-group">
                        <label>Assigned Instructor</label>
                        <input list="df-instructor-options" name="instructor_name" id="df-modal-instructor" placeholder="Select instructor">
                        <datalist id="df-instructor-options">
                            <?php foreach ($instructors_list as $ins) : ?>
                                <option value="<?php echo esc_attr($ins['name']); ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                        <button type="button" class="button button-small" id="df-find-slots-btn" style="margin-top:5px;font-size:11px;">
                            💡 Find Free Available Slots
                        </button>
                    </div>

                    <!-- Available Slots Picker Container -->
                    <div class="df-form-group df-form-full" id="df-slots-picker-container" style="display:none;background:#f0fdf4;border:1px solid #bbf7d0;padding:10px;border-radius:6px;">
                        <strong style="color:#166534;font-size:12px;">Available Instructor Time Slots:</strong>
                        <div id="df-slots-list" style="display:flex;flex-wrap:wrap;gap:6px;margin-top:6px;"></div>
                    </div>

                    <!-- Vehicle Selection -->
                    <div class="df-form-group">
                        <label>Training Vehicle / Plate</label>
                        <select name="plate_number" id="df-modal-plate">
                            <option value="">-- No vehicle assigned --</option>
                            <?php foreach ($vehicles_list as $veh) : ?>
                                <option value="<?php echo esc_attr($veh['plate_number']); ?>"><?php echo esc_html($veh['plate_number'] . ' (' . $veh['model'] . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Session Number -->
                    <div class="df-form-group">
                        <label>Lesson Number</label>
                        <input type="number" name="session_number" min="1" value="1">
                    </div>

                    <!-- Lesson Topic -->
                    <div class="df-form-group df-form-full">
                        <label>Lesson Curriculum Topic</label>
                        <input type="text" name="lesson_topic" placeholder="e.g. Parallel Parking, Highway Driving, Clutch & Hill Starts...">
                    </div>

                    <!-- Start & End Date Time -->
                    <div class="df-form-group">
                        <label>Start Time *</label>
                        <input type="datetime-local" name="scheduled_start" id="df-modal-start-time" required>
                    </div>
                    <div class="df-form-group">
                        <label>End Time *</label>
                        <input type="datetime-local" name="scheduled_end" id="df-modal-end-time" required>
                    </div>

                    <!-- Initial Status -->
                    <div class="df-form-group df-form-full">
                        <label>Initial Status</label>
                        <select name="status">
                            <option value="upcoming">Upcoming (Scheduled)</option>
                            <option value="active">Active (On Road)</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>

                    <!-- Real-time Collision Alert Container -->
                    <div class="df-form-group df-form-full" id="df-conflict-container" style="display:none;">
                        <div id="df-conflict-alert" style="background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;padding:10px 14px;border-radius:6px;font-size:13px;font-weight:600;"></div>
                    </div>
                </div>
            </div>
            <div class="df-modal-footer">
                <button type="button" class="df-btn df-btn-secondary" data-df-close-modal>Cancel</button>
                <button type="submit" class="df-btn df-btn-primary">Schedule & Dispatch Emails</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: View Session Details, Proof & Skills Evaluation -->
<div class="df-modal-backdrop" id="df-session-details-modal">
    <div class="df-modal">
        <div class="df-modal-header">
            <h3>Session Verification, Proof & Student Scorecard</h3>
            <button type="button" class="df-modal-close">&times;</button>
        </div>
        <div class="df-modal-body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;background:#f8fafc;padding:12px;border-radius:8px;">
                <div><strong>Student:</strong> <span id="df-detail-student"></span></div>
                <div><strong>Instructor:</strong> <span id="df-detail-instructor"></span></div>
                <div><strong>Vehicle Plate:</strong> <span id="df-detail-plate"></span></div>
                <div><strong>Status:</strong> <span id="df-detail-status"></span></div>
                <div style="grid-column:1/-1;"><strong>Date & Time:</strong> <span id="df-detail-date"></span></div>
            </div>

            <!-- Skills Evaluation Section -->
            <div id="df-skills-section" class="df-skills-summary-box" style="display:none;">
                <h4 style="margin:0 0 8px 0;color:#0f172a;">📊 Driving Skills Evaluation Scorecard</h4>
                <div class="df-skills-grid">
                    <div class="df-skill-badge"><span>Clutch & Speed Control:</span> <span id="df-skill-clutch" class="df-skill-stars"></span></div>
                    <div class="df-skill-badge"><span>Parallel Parking & Reversing:</span> <span id="df-skill-parking" class="df-skill-stars"></span></div>
                    <div class="df-skill-badge"><span>Steering & Intersections:</span> <span id="df-skill-steering" class="df-skill-stars"></span></div>
                    <div class="df-skill-badge"><span>Traffic Rules & Right-of-Way:</span> <span id="df-skill-rules" class="df-skill-stars"></span></div>
                </div>
            </div>

            <!-- Notes Section -->
            <div id="df-notes-section" style="margin-top:12px;background:#fff;border:1px solid #cbd5e1;padding:12px;border-radius:8px;display:none;">
                <strong style="color:#0f172a;">📝 Instructor Notes & Advice:</strong>
                <p id="df-detail-notes" style="margin:6px 0 0 0;color:#334155;font-size:13px;line-height:1.6;"></p>
            </div>

            <!-- Images Preview (Selfie + Signature) -->
            <div class="df-preview-images">
                <div class="df-preview-box">
                    <h4 style="margin:0 0 8px 0;">📸 Instructor In-Car Selfie</h4>
                    <img id="df-detail-selfie-img" src="" alt="Instructor Selfie">
                    <p id="df-detail-selfie-empty" style="color:#94a3b8;font-size:13px;display:none;">No selfie recorded.</p>
                </div>
                <div class="df-preview-box">
                    <h4 style="margin:0 0 8px 0;">✍️ Digital Signature</h4>
                    <img id="df-detail-signature-img" src="" alt="Digital Signature">
                    <p id="df-detail-signature-empty" style="color:#94a3b8;font-size:13px;display:none;">No signature recorded.</p>
                </div>
            </div>
        </div>
        <div class="df-modal-footer">
            <button type="button" class="df-btn df-btn-secondary" data-df-close-modal>Close</button>
        </div>
    </div>
</div>

<!-- Modal 3: Edit Session Details & Override Instructor Evaluations -->
<div class="df-modal-backdrop" id="df-edit-session-modal">
    <div class="df-modal" style="max-width:720px;">
        <div class="df-modal-header">
            <h3>Edit Driving Session & Admin Evaluation Override</h3>
            <button type="button" class="df-modal-close">&times;</button>
        </div>
        <form id="df-edit-session-form">
            <input type="hidden" name="session_id" id="df-edit-session-id">
            <div class="df-modal-body">
                <div class="df-form-grid">
                    <div class="df-form-group">
                        <label>Student Name *</label>
                        <input type="text" name="student_name" id="df-edit-student-name" required>
                    </div>
                    <div class="df-form-group">
                        <label>Assigned Instructor</label>
                        <select name="instructor_name" id="df-edit-instructor-name">
                            <option value="">-- Unassigned --</option>
                            <?php foreach ($instructors_list as $ins) : ?>
                                <option value="<?php echo esc_attr($ins['name']); ?>"><?php echo esc_html($ins['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="df-form-group">
                        <label>Training Vehicle / Plate</label>
                        <select name="plate_number" id="df-edit-plate-number">
                            <option value="">-- No vehicle assigned --</option>
                            <?php foreach ($vehicles_list as $veh) : ?>
                                <option value="<?php echo esc_attr($veh['plate_number']); ?>"><?php echo esc_html($veh['plate_number'] . ' (' . $veh['model'] . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="df-form-group">
                        <label>Lesson Number</label>
                        <input type="number" name="session_number" id="df-edit-session-number" min="1">
                    </div>
                    <div class="df-form-group df-form-full">
                        <label>Curriculum Topic</label>
                        <input type="text" name="lesson_topic" id="df-edit-lesson-topic" placeholder="e.g. Parallel Parking, Highway Driving...">
                    </div>
                    <div class="df-form-group">
                        <label>Start Time *</label>
                        <input type="datetime-local" name="scheduled_start" id="df-edit-scheduled-start" required>
                    </div>
                    <div class="df-form-group">
                        <label>End Time *</label>
                        <input type="datetime-local" name="scheduled_end" id="df-edit-scheduled-end" required>
                    </div>
                    <div class="df-form-group">
                        <label>Session Status</label>
                        <select name="status" id="df-edit-status">
                            <option value="upcoming">Upcoming</option>
                            <option value="active">Active (On Road)</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="df-form-group">
                        <label>Final Result (Pass / Fail Override)</label>
                        <select name="final_evaluation" id="df-edit-final-eval">
                            <option value="">-- None / In Progress --</option>
                            <option value="Pass">Pass (Satisfactory)</option>
                            <option value="Fail">Fail (Needs Improvement)</option>
                        </select>
                    </div>
                    <div class="df-form-group df-form-full">
                        <label>Instructor Certification / License #</label>
                        <input type="text" name="instructor_cert_no" id="df-edit-instructor-cert" placeholder="e.g. MVA-INS-88412">
                    </div>
                    <div class="df-form-group df-form-full">
                        <label>Instructor Notes & Evaluation Feedback (Admin Override)</label>
                        <textarea name="instructor_notes" id="df-edit-instructor-notes" rows="3" placeholder="Instructor commentary, performance notes, or driving advice..."></textarea>
                    </div>
                </div>
            </div>
            <div class="df-modal-footer" style="display:flex;justify-content:space-between;align-items:center;">
                <button type="button" class="df-btn df-btn-danger df-modal-delete-session-btn" id="df-edit-modal-delete-btn" style="background:#dc2626;color:#fff;border-color:#dc2626;">
                    🗑️ Delete Session
                </button>
                <div style="display:flex;gap:8px;">
                    <button type="button" class="df-btn df-btn-secondary" data-df-close-modal>Cancel</button>
                    <button type="submit" class="df-btn df-btn-primary" id="df-edit-session-submit-btn">Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>

