<?php
defined('ABSPATH') || exit;
global $wpdb;

$search = sanitize_text_field(wp_unslash($_GET['s'] ?? ''));
$search_like = '%' . $wpdb->esc_like($search) . '%';
$where = $search ? $wpdb->prepare(' WHERE name LIKE %s OR phone LIKE %s OR email LIKE %s OR license_number LIKE %s', $search_like, $search_like, $search_like, $search_like) : '';

$paged = max(1, absint($_GET['paged'] ?? 1));
$per_page = max(5, min(100, absint($_GET['per_page'] ?? 15)));
$offset = ($paged - 1) * $per_page;

$count_sql = "SELECT COUNT(*) FROM {$wpdb->prefix}driveflow_students {$where}";
$total_rows = (int) $wpdb->get_var($count_sql);
$total_pages = max(1, ceil($total_rows / $per_page));

$students = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}driveflow_students {$where} ORDER BY id DESC LIMIT {$offset}, {$per_page}", ARRAY_A);

// Stats
$total_students = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}driveflow_students");
$active_packages = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}driveflow_students WHERE (total_sessions - completed_sessions) > 0");
?>

<div class="wrap driveflow-admin-wrap" dir="ltr">
    <!-- Header Banner -->
    <div class="df-header-banner">
        <div class="df-header-title">
            <div>
                <h1>DriveFlow Pro · Student Management & Package Balances</h1>
                <p>Track learner permits, course packages, lesson credit balances, top-up extra sessions, and review full activity logs.</p>
            </div>
        </div>
        <div class="df-header-actions">
            <button type="button" class="df-btn df-btn-primary" data-df-open-modal="df-add-student-modal">
                <span class="dashicons dashicons-plus-alt2"></span> Add New Student
            </button>
            <a href="<?php echo esc_url(admin_url('admin.php?page=driveflow-pro-records')); ?>" class="df-btn df-btn-secondary">
                <span class="dashicons dashicons-calendar-alt"></span> View All Sessions
            </a>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="df-stats-grid">
        <div class="df-stat-card">
            <div class="df-stat-info">
                <h4>Total Enrolled Students</h4>
                <div class="df-stat-number"><?php echo esc_html($total_students ?: 0); ?></div>
            </div>
            <div class="df-stat-icon total">🎓</div>
        </div>
        <div class="df-stat-card">
            <div class="df-stat-info">
                <h4>Active Students With Remaining Lessons</h4>
                <div class="df-stat-number" style="color:#16a34a;"><?php echo esc_html($active_packages ?: 0); ?></div>
            </div>
            <div class="df-stat-icon active">⏳</div>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="df-toolbar">
        <form method="get" class="df-filters-form">
            <input type="hidden" name="page" value="driveflow-pro-students">
            <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search student by name, phone, email or license...">
            <button type="submit" class="df-btn df-btn-secondary">
                <span class="dashicons dashicons-search"></span> Search
            </button>
            <?php if ($search) : ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=driveflow-pro-students')); ?>" class="df-btn df-btn-secondary df-btn-sm">Show All</a>
            <?php endif; ?>
        </form>

        <div class="df-per-page-select">
            <span>Show</span>
            <select onchange="location.href=this.value;">
                <?php foreach (array(10, 15, 25, 50, 100) as $pp) : ?>
                    <option value="<?php echo esc_url(add_query_arg(array('per_page' => $pp, 'paged' => 1))); ?>" <?php selected($per_page, $pp); ?>>
                        <?php echo esc_html($pp); ?> / page
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- Students Data Table -->
    <div class="df-table-container">
        <table class="df-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Student Name</th>
                    <th>Contact Info</th>
                    <th>Permit / License #</th>
                    <th>Course Package</th>
                    <th>Progress & Balance</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($students)) : ?>
                <tr>
                    <td colspan="7" style="text-align:center;padding:40px;color:#94a3b8;">
                        No students found. Click <strong>"Add New Student"</strong> to register your first driving learner.
                    </td>
                </tr>
            <?php else : ?>
                <?php foreach ($students as $st) : 
                    $total = (int)($st['total_sessions'] ?? 0);
                    $done = (int)($st['completed_sessions'] ?? 0);
                    $remaining = max(0, $total - $done);
                    $percent = ($total > 0) ? min(100, round(($done / $total) * 100)) : 0;
                ?>
                <tr>
                    <td><strong>#<?php echo esc_html($st['id']); ?></strong></td>
                    <td>
                        <strong style="font-size:14px;"><?php echo esc_html($st['name']); ?></strong>
                        <div style="font-size:11px;color:#64748b;">Enrolled: <?php echo esc_html(date_i18n('M j, Y', strtotime($st['created_at']))); ?></div>
                    </td>
                    <td>
                        <div>📞 <?php echo esc_html($st['phone'] ?: '—'); ?></div>
                        <div style="font-size:12px;color:#64748b;">✉️ <?php echo esc_html($st['email'] ?: '—'); ?></div>
                    </td>
                    <td>
                        <code><?php echo esc_html($st['license_number'] ?: '—'); ?></code>
                    </td>
                    <td>
                        <div style="font-weight:600;color:#0f172a;"><?php echo esc_html($st['package_name'] ?: 'Standard Package'); ?></div>
                    </td>
                    <td>
                        <div style="min-width:160px;">
                            <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px;">
                                <span><strong><?php echo esc_html($done); ?></strong> / <?php echo esc_html($total); ?> Lessons</span>
                                <?php if ($remaining > 0) : ?>
                                    <span style="color:#16a34a;font-weight:700;"><?php echo esc_html($remaining); ?> Left</span>
                                <?php else : ?>
                                    <span style="color:#d97706;font-weight:600;">Completed</span>
                                <?php endif; ?>
                            </div>
                            <div style="background:#e2e8f0;border-radius:999px;height:8px;overflow:hidden;">
                                <div style="background:<?php echo ($remaining > 0) ? '#2563eb' : '#16a34a'; ?>;height:100%;width:<?php echo esc_attr($percent); ?>%;"></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="df-actions-cell" style="display:flex;flex-wrap:wrap;gap:4px;">
                            <!-- Generate Expirable / Single-Use Short Link -->
                            <button type="button" class="df-btn df-btn-outline df-btn-sm df-open-magic-modal-btn"
                                data-type="student_booking"
                                data-target-id="<?php echo esc_attr($st['id']); ?>"
                                data-name="<?php echo esc_attr($st['name']); ?>"
                                data-email="<?php echo esc_attr($st['email'] ?? ''); ?>"
                                title="Generate custom expirable or single-use short booking link">
                                ⚡ Short Link
                            </button>

                            <!-- Copy Magic Link for SMS/WhatsApp -->
                            <?php $magic_link = DriveFlow_Credit_Manager::get_magic_booking_url($st['id']); ?>
                            <button type="button" class="df-btn df-btn-secondary df-btn-sm df-copy-link-btn"
                                data-link="<?php echo esc_attr($magic_link); ?>"
                                title="Copy permanent private booking link for SMS, WhatsApp or Telegram">
                                🔗 Copy Link
                            </button>

                            <!-- Email Magic Link Directly -->
                            <?php if (!empty($st['email'])) : ?>
                            <button type="button" class="df-btn df-btn-secondary df-btn-sm df-send-link-btn"
                                data-student-id="<?php echo esc_attr($st['id']); ?>"
                                data-student-email="<?php echo esc_attr($st['email']); ?>"
                                title="Email private self-booking portal link to student">
                                ✉️ Email
                            </button>
                            <?php endif; ?>

                            <!-- View Complete Profile & Logs -->
                            <button type="button" class="df-btn df-btn-secondary df-btn-sm df-view-student-btn"
                                data-student-id="<?php echo esc_attr($st['id']); ?>"
                                title="View complete activity log, session records, and scores">
                                👁️ Profile
                            </button>

                            <!-- Edit Student Details -->
                            <button type="button" class="df-btn df-btn-secondary df-btn-sm df-edit-student-btn"
                                data-student-id="<?php echo esc_attr($st['id']); ?>"
                                data-name="<?php echo esc_attr($st['name']); ?>"
                                data-phone="<?php echo esc_attr($st['phone'] ?? ''); ?>"
                                data-email="<?php echo esc_attr($st['email'] ?? ''); ?>"
                                data-license="<?php echo esc_attr($st['license_number'] ?? ''); ?>"
                                data-package="<?php echo esc_attr($st['package_name'] ?? ''); ?>"
                                data-total="<?php echo esc_attr($total); ?>"
                                data-completed="<?php echo esc_attr($done); ?>"
                                title="Edit student profile, permit details & course balance">
                                ✏️ Edit
                            </button>

                            <!-- Add Extra Sessions Top-up -->
                            <button type="button" class="df-btn df-btn-primary df-btn-sm df-topup-btn"
                                data-student-id="<?php echo esc_attr($st['id']); ?>"
                                data-student-name="<?php echo esc_attr($st['name']); ?>"
                                data-current-total="<?php echo esc_attr($total); ?>"
                                data-remaining="<?php echo esc_attr($remaining); ?>"
                                title="Add extra paid sessions to student balance">
                                ➕ Top-up
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
            $start_item = (($paged ?? 1) - 1) * $per_page + 1;
            $end_item = min($total_rows, ($paged ?? 1) * $per_page);
            $build_url = function($p) use ($search, $per_page) {
                return add_query_arg(array(
                    'page' => 'driveflow-pro-students',
                    's' => $search,
                    'per_page' => $per_page,
                    'paged' => $p
                ), admin_url('admin.php'));
            };
        ?>
        <div class="df-pagination-bar">
            <div class="df-pagination-info">
                Showing <strong><?php echo esc_html($start_item); ?></strong> to <strong><?php echo esc_html($end_item); ?></strong> of <strong><?php echo esc_html($total_rows); ?></strong> students
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

<!-- Modal 1: Add New Student -->
<div class="df-modal-backdrop" id="df-add-student-modal">
    <div class="df-modal">
        <div class="df-modal-header">
            <h3>Register New Driving Student</h3>
            <button type="button" class="df-modal-close">&times;</button>
        </div>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="driveflow_save_student">
            <?php wp_nonce_field('driveflow_save_student'); ?>
            <div class="df-modal-body">
                <div class="df-form-grid">
                    <div class="df-form-group">
                        <label>Full Name *</label>
                        <input required name="name" type="text" placeholder="e.g. Sarah Jenkins">
                    </div>
                    <div class="df-form-group">
                        <label>Phone Number *</label>
                        <input required name="phone" type="text" placeholder="e.g. (240) 555-0199">
                    </div>
                    <div class="df-form-group">
                        <label>Email Address</label>
                        <input name="email" type="email" placeholder="e.g. sarah@example.com">
                    </div>
                    <div class="df-form-group">
                        <label>Permit / Driver License #</label>
                        <input name="license_number" type="text" placeholder="e.g. MD-D1234567">
                    </div>
                    <div class="df-form-group">
                        <label>Course Package Name</label>
                        <input name="package_name" type="text" value="Standard 10-Lesson Course" placeholder="e.g. Full License Course (10 Lessons)">
                    </div>
                    <div class="df-form-group">
                        <label>Total Lessons Included</label>
                        <input name="total_sessions" type="number" min="1" max="100" value="10">
                    </div>
                </div>
            </div>
            <div class="df-modal-footer">
                <button type="button" class="df-btn df-btn-secondary" data-df-close-modal>Cancel</button>
                <button type="submit" class="df-btn df-btn-primary">Register Student</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: Add Extra Sessions Top-Up -->
<div class="df-modal-backdrop" id="df-topup-modal">
    <div class="df-modal">
        <div class="df-modal-header">
            <h3>Add Extra Driving Sessions</h3>
            <button type="button" class="df-modal-close">&times;</button>
        </div>
        <form id="df-topup-form">
            <input type="hidden" name="student_id" id="df-topup-student-id">
            <div class="df-modal-body">
                <div style="background:#f8fafc;padding:12px 16px;border-radius:8px;margin-bottom:16px;border:1px solid #e2e8f0;">
                    <div style="font-size:15px;font-weight:700;color:#0f172a;" id="df-topup-student-name"></div>
                    <div style="font-size:13px;color:#64748b;margin-top:4px;">
                        Current Total: <span id="df-topup-cur-total" style="font-weight:bold;"></span> lessons • 
                        Remaining: <span id="df-topup-cur-remaining" style="font-weight:bold;color:#16a34a;"></span> lessons
                    </div>
                </div>

                <div class="df-form-group" style="margin-bottom:14px;">
                    <label>Number of Extra Sessions to Add *</label>
                    <div style="display:flex;gap:10px;margin-top:6px;">
                        <label style="border:1px solid #cbd5e1;padding:8px 14px;border-radius:6px;cursor:pointer;">
                            <input type="radio" name="extra_amount" value="1"> +1 Lesson
                        </label>
                        <label style="border:1px solid #cbd5e1;padding:8px 14px;border-radius:6px;cursor:pointer;">
                            <input type="radio" name="extra_amount" value="2"> +2 Lessons
                        </label>
                        <label style="border:1px solid #cbd5e1;padding:8px 14px;border-radius:6px;cursor:pointer;">
                            <input type="radio" name="extra_amount" value="3" checked> +3 Lessons
                        </label>
                        <label style="border:1px solid #cbd5e1;padding:8px 14px;border-radius:6px;cursor:pointer;">
                            <input type="radio" name="extra_amount" value="5"> +5 Lessons
                        </label>
                    </div>
                </div>

                <div class="df-form-group">
                    <label>Payment / Invoice Reference Notes</label>
                    <input type="text" name="notes" id="df-topup-notes" placeholder="e.g. Paid via POS Terminal, Invoice #1042">
                </div>
            </div>
            <div class="df-modal-footer">
                <button type="button" class="df-btn df-btn-secondary" data-df-close-modal>Cancel</button>
                <button type="submit" class="df-btn df-btn-primary" id="df-topup-submit-btn">Add Extra Sessions</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 3: Comprehensive Student Profile & Activity Log Drawer -->
<div class="df-modal-backdrop" id="df-student-profile-modal">
    <div class="df-modal" style="max-width:850px;">
        <div class="df-modal-header">
            <h3>Student Profile, Lesson Progress & Activity Ledger</h3>
            <button type="button" class="df-modal-close">&times;</button>
        </div>
        <div class="df-modal-body" id="df-student-profile-body">
            <div style="text-align:center;padding:40px;color:#94a3b8;">Loading student profile...</div>
        </div>
        <div class="df-modal-footer">
            <button type="button" class="df-btn df-btn-secondary" data-df-close-modal>Close</button>
        </div>
    </div>
</div>

<!-- Modal 4: Edit Student Profile & Course Package -->
<div class="df-modal-backdrop" id="df-edit-student-modal">
    <div class="df-modal">
        <div class="df-modal-header">
            <h3>Edit Student Profile & Package Balances</h3>
            <button type="button" class="df-modal-close">&times;</button>
        </div>
        <form id="df-edit-student-form">
            <input type="hidden" name="student_id" id="df-edit-student-id">
            <div class="df-modal-body">
                <div class="df-form-grid">
                    <div class="df-form-group">
                        <label>Full Name *</label>
                        <input required name="name" id="df-edit-st-name" type="text" placeholder="e.g. Sarah Jenkins">
                    </div>
                    <div class="df-form-group">
                        <label>Phone Number *</label>
                        <input required name="phone" id="df-edit-st-phone" type="text" placeholder="e.g. (240) 555-0199">
                    </div>
                    <div class="df-form-group">
                        <label>Email Address</label>
                        <input name="email" id="df-edit-st-email" type="email" placeholder="e.g. sarah@example.com">
                    </div>
                    <div class="df-form-group">
                        <label>Permit / Driver License #</label>
                        <input name="license_number" id="df-edit-st-license" type="text" placeholder="e.g. MD-D1234567">
                    </div>
                    <div class="df-form-group">
                        <label>Course Package Name</label>
                        <input name="package_name" id="df-edit-st-package" type="text" placeholder="e.g. Standard 10-Lesson Course">
                    </div>
                    <div class="df-form-group">
                        <label>Total Lessons Included</label>
                        <input name="total_sessions" id="df-edit-st-total" type="number" min="1" max="200">
                    </div>
                    <div class="df-form-group">
                        <label>Completed Lessons Count</label>
                        <input name="completed_sessions" id="df-edit-st-completed" type="number" min="0" max="200">
                    </div>
                </div>
            </div>
            <div class="df-modal-footer">
                <button type="button" class="df-btn df-btn-secondary" data-df-close-modal>Cancel</button>
                <button type="submit" class="df-btn df-btn-primary" id="df-edit-student-submit-btn">Save Student Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Magic Link Generator -->
<div class="df-modal-backdrop" id="df-magic-link-modal">
    <div class="df-modal" style="max-width:540px;">
        <div class="df-modal-header">
            <h3>🔗 Generate Secure Magic Link</h3>
            <button type="button" class="df-modal-close">&times;</button>
        </div>
        <form id="df-magic-link-form">
            <input type="hidden" name="type" id="df-magic-link-type" value="student_booking">
            <input type="hidden" name="target_id" id="df-magic-link-target-id" value="0">
            <div class="df-modal-body">
                <div style="background:#f0f9ff;border:1px solid #bae6fd;padding:14px 16px;border-radius:10px;margin-bottom:18px;display:flex;align-items:center;gap:12px;">
                    <span style="font-size:24px;">🛡️</span>
                    <div>
                        <div style="font-size:12px;color:#0369a1;font-weight:700;text-transform:uppercase;">Recipient Profile</div>
                        <div style="font-size:15px;color:#0f172a;font-weight:700;" id="df-magic-recipient-name">—</div>
                        <div style="font-size:12px;color:#64748b;" id="df-magic-recipient-email">—</div>
                    </div>
                </div>

                <div class="df-form-group" style="margin-bottom:16px;">
                    <label style="display:block;font-size:13px;font-weight:600;color:#334155;margin-bottom:6px;">Link Validity / Expiration Duration *</label>
                    <select name="duration_hours" id="df-magic-duration" style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;">
                        <option value="24">24 Hours (1 Day)</option>
                        <option value="48" selected>48 Hours (2 Days — Recommended)</option>
                        <option value="168">7 Days (1 Week)</option>
                        <option value="720">30 Days (1 Month)</option>
                        <option value="0">Permanent Link (No Expiration)</option>
                    </select>
                </div>

                <div class="df-form-group" style="margin-bottom:18px;">
                    <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;background:#f8fafc;padding:12px;border-radius:8px;border:1px solid #e2e8f0;">
                        <input type="checkbox" name="is_single_use" id="df-magic-single-use" value="1" checked style="margin-top:3px;">
                        <div>
                            <strong style="font-size:13px;color:#0f172a;display:block;">Single-Use Link (One-Time Token)</strong>
                            <span style="font-size:12px;color:#64748b;">Automatically burns the token immediately after the student books or the instructor completes onboarding.</span>
                        </div>
                    </label>
                </div>

                <div class="df-form-group" style="margin-bottom:18px;" id="df-magic-email-checkbox-wrap">
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                        <input type="checkbox" name="send_email" id="df-magic-send-email" value="1" checked>
                        <span style="font-size:13px;color:#334155;font-weight:600;">✉️ Automatically email invitation link to recipient</span>
                    </label>
                </div>

                <div id="df-magic-result-box" style="display:none;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:16px;margin-top:16px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                        <span style="font-size:12px;font-weight:700;color:#16a34a;">✓ SHORT MAGIC LINK GENERATED</span>
                        <span id="df-magic-expires-label" style="font-size:11px;color:#64748b;"></span>
                    </div>
                    <div style="display:flex;gap:8px;">
                        <input type="text" id="df-generated-short-url" readonly style="flex:1;background:#fff;padding:8px 12px;border:1px solid #cbd5e1;border-radius:6px;font-family:monospace;font-size:13px;color:#0f766e;font-weight:700;">
                        <button type="button" class="df-btn df-btn-primary df-btn-sm" id="df-copy-generated-url-btn">📋 Copy</button>
                    </div>
                    <div id="df-magic-email-status" style="font-size:12px;color:#15803d;font-weight:600;margin-top:8px;"></div>
                </div>
            </div>
            <div class="df-modal-footer">
                <button type="button" class="df-btn df-btn-secondary" data-df-close-modal>Close</button>
                <button type="submit" class="df-btn df-btn-primary" id="df-generate-magic-submit-btn">🚀 Generate Link</button>
            </div>
        </form>
    </div>
</div>

