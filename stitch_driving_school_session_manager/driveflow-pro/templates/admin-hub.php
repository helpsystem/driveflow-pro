<?php
defined('ABSPATH') || exit;

/**
 * DriveFlow Pro — Admin Central Hub & Master Control Center
 * 
 * Provides a unified, executive command dashboard where the administrator can
 * monitor and access 100% of the plugin's features, backend modules, frontend portals,
 * MVA compliance status, quick actions, and system diagnostics from one dedicated page.
 */
$current_tv_mode = get_option('driveflow_tv_view_mode', 'slots');
$current_tv_alert = get_option('driveflow_tv_broadcast_alert', array());
$current_ticker_items = get_option('driveflow_tv_ticker_items', array());
if (empty($current_ticker_items)) {
    $def_ticker = $settings['announcement_ticker'] ?? "Welcome to Sam's Driving School LLC • Please have your Maryland learner permit ready • Safe driving is respect for life • Maryland MVA COMAR 11.23 Compliant • All vehicles are dual-control certified";
    $current_ticker_items = array_values(array_filter(array_map('trim', explode("\n", str_replace('•', "\n", $def_ticker)))));
}
$tv_kiosk_url = site_url('/?page_id=' . absint($settings['tv_page_id'] ?? 24));
?>

<div class="wrap driveflow-admin-wrap" dir="ltr">
    <!-- Hero Header & Global School Identity -->
    <div class="df-header-banner" style="background: linear-gradient(135deg, #091e3a 0%, #0f2b48 45%, #0d5a52 100%); padding: 26px 32px; border-radius: 12px; box-shadow: 0 8px 24px -4px rgba(15, 23, 42, 0.25); margin-bottom: 24px;">
        <div class="df-header-title">
            <div style="background: rgba(255, 255, 255, 0.12); backdrop-filter: blur(8px); padding: 12px; border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.2); display: flex; align-items: center; justify-content: center; font-size: 32px; width: 56px; height: 56px;">
                🚗
            </div>
            <div>
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    <h1 style="font-size: 26px; font-weight: 800; letter-spacing: -0.5px; color: #fff; margin: 0;">
                        <?php echo esc_html($settings['school_name'] ?? "Sam's Driving School LLC"); ?> · Master Control Center
                    </h1>
                    <span style="background: #10b981; color: #fff; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 999px; text-transform: uppercase; letter-spacing: 0.5px;">Live & Operational</span>
                </div>
                <p style="color: #cbd5e1; font-size: 13px; margin: 5px 0 0 0;">
                    📍 <?php echo esc_html($settings['school_address'] ?? "751 Rockville Pike, Unit # 9B, Rockville, MD 20852"); ?> &nbsp;|&nbsp; 
                    📞 <?php echo esc_html($settings['school_phone'] ?? "(202) 600-0889 / (301) 726-3030"); ?> &nbsp;|&nbsp; 
                    🏛️ Maryland MVA / COMAR 11.23 Regulatory Compliant
                </p>
            </div>
        </div>
        <div class="df-header-actions" style="gap: 8px; flex-wrap: wrap;">
            <button type="button" class="df-btn" id="df-hub-open-guide-btn" style="background:#f59e0b;color:#fff;border:1px solid #d97706;font-weight:700;">
                📖 Operations Guide
            </button>
            <a href="<?php echo esc_url(site_url('/?driveflow_admin=1')); ?>" target="_blank" class="df-btn" style="background:#10b981;color:#fff;border:1px solid #059669;text-decoration:none;font-weight:700;" title="Open Dedicated Executive Admin Portal">
                🖥️ Dedicated Portal ↗
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=driveflow-pro-calendar')); ?>" class="df-btn" style="background:rgba(255,255,255,0.15);color:#fff;border:1px solid rgba(255,255,255,0.3);text-decoration:none;">
                <span class="dashicons dashicons-calendar-alt"></span> Visual Calendar
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=driveflow-pro-records')); ?>" class="df-btn" style="background:rgba(255,255,255,0.15);color:#fff;border:1px solid rgba(255,255,255,0.3);text-decoration:none;">
                <span class="dashicons dashicons-list-view"></span> Sessions Log
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=driveflow-pro-settings')); ?>" class="df-btn" style="background:rgba(255,255,255,0.15);color:#fff;border:1px solid rgba(255,255,255,0.3);text-decoration:none;">
                <span class="dashicons dashicons-admin-generic"></span> Global Settings
            </a>
            <button type="button" class="df-btn df-btn-primary" id="df-hub-open-quick-magic" style="background:#0284c7;border-color:#0284c7;">
                ⚡ Issue Magic Link
            </button>
        </div>
    </div>

    <!-- Live KPI Metrics Grid -->
    <div class="df-stats-grid" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(210px, 1fr));gap:16px;margin-bottom:26px;">
        <!-- Card 1: Driving Sessions -->
        <div class="df-stat-card" style="border-left: 4px solid #0284c7;">
            <div class="df-stat-info">
                <h4>Driving Sessions</h4>
                <div class="df-stat-number"><?php echo number_format_i18n((int)($session_stats['total'] ?? 0)); ?></div>
                <div style="font-size:12px;color:#64748b;margin-top:4px;">
                    <span style="color:#0284c7;font-weight:700;"><?php echo (int)($session_stats['upcoming'] ?? 0); ?></span> upcoming · 
                    <span style="color:#16a34a;font-weight:700;"><?php echo (int)($session_stats['completed'] ?? 0); ?></span> completed
                </div>
            </div>
            <div class="df-stat-icon total" style="background:#e0f2fe;color:#0284c7;">🚗</div>
        </div>

        <!-- Card 2: Enrolled Students -->
        <div class="df-stat-card" style="border-left: 4px solid #f59e0b;">
            <div class="df-stat-info">
                <h4>Enrolled Students</h4>
                <div class="df-stat-number"><?php echo number_format_i18n((int)($student_stats['total'] ?? 0)); ?></div>
                <div style="font-size:12px;color:#64748b;margin-top:4px;">
                    <span style="color:#16a34a;font-weight:700;"><?php echo (int)($student_stats['active'] ?? 0); ?> active</span> · 
                    <span><?php echo (int)($student_stats['total_credits'] ?? 0); ?> total credits</span>
                </div>
            </div>
            <div class="df-stat-icon" style="background:#fef3c7;color:#d97706;">🎓</div>
        </div>

        <!-- Card 3: Certified Instructors -->
        <div class="df-stat-card" style="border-left: 4px solid #10b981;">
            <div class="df-stat-info">
                <h4>Certified Instructors</h4>
                <div class="df-stat-number"><?php echo number_format_i18n((int)($instructor_stats['total'] ?? 0)); ?></div>
                <div style="font-size:12px;color:#64748b;margin-top:4px;">
                    <span style="color:#16a34a;font-weight:700;"><?php echo (int)($instructor_stats['active'] ?? 0); ?> active</span> · 
                    <span style="color:#0369a1;font-weight:700;"><?php echo (int)($instructor_stats['signed_agreements'] ?? 0); ?> signed MVA</span>
                </div>
            </div>
            <div class="df-stat-icon" style="background:#dcfce7;color:#16a34a;">👨‍🏫</div>
        </div>

        <!-- Card 4: Training Fleet -->
        <div class="df-stat-card" style="border-left: 4px solid #8b5cf6;">
            <div class="df-stat-info">
                <h4>Training Vehicles</h4>
                <div class="df-stat-number"><?php echo number_format_i18n((int)($vehicle_stats['total'] ?? 0)); ?></div>
                <div style="font-size:12px;color:#64748b;margin-top:4px;">
                    <span style="color:#16a34a;font-weight:700;"><?php echo (int)($vehicle_stats['active'] ?? 0); ?> ready</span> · 
                    <span>Dual-Control Approved</span>
                </div>
            </div>
            <div class="df-stat-icon" style="background:#f3e8ff;color:#7e22ce;">🚘</div>
        </div>

        <!-- Card 5: Magic Short Links -->
        <div class="df-stat-card" style="border-left: 4px solid #ec4899;">
            <div class="df-stat-info">
                <h4>Magic Short Links</h4>
                <div class="df-stat-number"><?php echo number_format_i18n((int)($magic_stats['total'] ?? 0)); ?></div>
                <div style="font-size:12px;color:#64748b;margin-top:4px;">
                    <span style="color:#16a34a;font-weight:700;"><?php echo (int)($magic_stats['active'] ?? 0); ?> active</span> · 
                    <span>Single-Use & Expirable</span>
                </div>
            </div>
            <div class="df-stat-icon" style="background:#fce7f3;color:#db2777;">⚡</div>
        </div>
    </div>

    <!-- Quick Issuance & Instant Command Bar -->
    <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;padding:18px 24px;margin-bottom:26px;box-shadow:0 2px 6px rgba(0,0,0,0.04);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
        <div style="display:flex;align-items:center;gap:12px;">
            <span style="font-size:22px;">⚡</span>
            <div>
                <strong style="font-size:14px;color:#0f172a;display:block;">Instant Dispatch Station</strong>
                <span style="font-size:12px;color:#64748b;">Quickly issue single-use booking invitations to students or onboarding links to instructors without navigating away.</span>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <!-- Select Student -->
            <select id="df-hub-student-select" style="max-width:210px;padding:6px 10px;border-radius:6px;border:1px solid #cbd5e1;font-size:13px;">
                <option value="">-- Pick Student for Booking --</option>
                <?php foreach ($students_list as $st) : ?>
                    <option value="<?php echo esc_attr($st['id']); ?>" data-name="<?php echo esc_attr($st['name']); ?>" data-email="<?php echo esc_attr($st['email'] ?? ''); ?>">
                        <?php echo esc_html($st['name']); ?> (<?php echo (int)$st['completed_sessions']; ?>/<?php echo (int)$st['total_sessions']; ?> hrs)
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="button" class="df-btn df-btn-primary df-btn-sm" id="df-hub-issue-student-btn">
                📅 Issue Student Booking Link
            </button>

            <!-- Select Instructor -->
            <select id="df-hub-instructor-select" style="max-width:210px;padding:6px 10px;border-radius:6px;border:1px solid #cbd5e1;font-size:13px;margin-left:8px;">
                <option value="">-- Pick Instructor for Onboarding --</option>
                <?php foreach ($instructors_list as $ins) : ?>
                    <option value="<?php echo esc_attr($ins['id']); ?>" data-name="<?php echo esc_attr($ins['name']); ?>" data-email="<?php echo esc_attr($ins['email'] ?? ''); ?>">
                        <?php echo esc_html($ins['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="button" class="df-btn df-btn-sm" id="df-hub-issue-instructor-btn" style="background:#0f766e;color:#fff;border-color:#0f766e;">
                ✍️ Issue Instructor Onboarding Link
            </button>
        </div>
    </div>

    <!-- ══════════════════ TV MONITOR REMOTE COMMANDER & DISPATCH CONTROLLER ══════════════════ -->
    <div class="df-hub-tv-commander" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border: 1px solid #334155; border-radius: 12px; padding: 24px; margin-bottom: 30px; box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.4); color: #f8fafc;">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;border-bottom:1px solid rgba(255,255,255,0.1);padding-bottom:16px;margin-bottom:20px;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="background: #0284c7; width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 24px; box-shadow: 0 0 16px rgba(2, 132, 199, 0.4);">
                    📺
                </div>
                <div>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <h2 style="font-size:18px;font-weight:800;color:#fff;margin:0;">Live TV Monitor Remote Commander</h2>
                        <span style="background:rgba(16, 185, 129, 0.2);color:#34d399;border:1px solid rgba(16, 185, 129, 0.4);font-size:11px;font-weight:700;padding:2px 8px;border-radius:999px;display:inline-flex;align-items:center;gap:5px;">
                            <span style="width:6px;height:6px;border-radius:50%;background:#10b981;display:inline-block;"></span> Active Dispatch
                        </span>
                    </div>
                    <p style="font-size:12px;color:#94a3b8;margin:4px 0 0 0;">
                        Remotely command and control the Smart Lobby TV display screen on your second monitor in real time.
                    </p>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                <a href="<?php echo esc_url($tv_kiosk_url); ?>" target="_blank" class="df-btn" style="background:#0284c7;color:#fff;border:none;font-weight:700;display:inline-flex;align-items:center;gap:6px;text-decoration:none;padding:7px 16px;">
                    <span>📺 Launch Monitor 2 Screen ↗</span>
                </a>
                <button type="button" class="df-btn df-btn-secondary" id="df-remote-force-reload-btn" style="background:rgba(255,255,255,0.08);color:#e2e8f0;border-color:rgba(255,255,255,0.2);" title="Send force reload signal to all open TV screens">
                    <span>🔄 Remote Refresh TV</span>
                </button>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(320px, 1fr));gap:20px;">
            <!-- Column 1: Remote View Mode Switcher -->
            <div style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:10px;padding:18px;">
                <label style="font-size:13px;font-weight:700;color:#38bdf8;text-transform:uppercase;letter-spacing:0.5px;display:block;margin-bottom:8px;">
                    1. Live TV Display Mode
                </label>
                <p style="font-size:12px;color:#94a3b8;margin:0 0 14px 0;">
                    Select how sessions and resources appear on the lobby TV monitor:
                </p>
                <div style="display:flex;flex-direction:column;gap:8px;" id="df-tv-mode-radios">
                    <label style="display:flex;align-items:center;gap:10px;background:rgba(255,255,255,0.05);padding:10px 14px;border-radius:8px;border:1px solid <?php echo ('slots' === $current_tv_mode) ? '#0284c7' : 'rgba(255,255,255,0.1)'; ?>;cursor:pointer;">
                        <input type="radio" name="df_tv_view_mode" value="slots" <?php checked($current_tv_mode, 'slots'); ?>>
                        <div>
                            <strong style="color:#fff;font-size:13px;display:block;">⏱ 2-Hour Time Slots (Standard Dispatch)</strong>
                            <span style="color:#94a3b8;font-size:11px;">Displays Active, Upcoming, and Completed lessons with full student cards and MD plates.</span>
                        </div>
                    </label>
                    <label style="display:flex;align-items:center;gap:10px;background:rgba(255,255,255,0.05);padding:10px 14px;border-radius:8px;border:1px solid <?php echo ('vehicles' === $current_tv_mode) ? '#0284c7' : 'rgba(255,255,255,0.1)'; ?>;cursor:pointer;">
                        <input type="radio" name="df_tv_view_mode" value="vehicles" <?php checked($current_tv_mode, 'vehicles'); ?>>
                        <div>
                            <strong style="color:#fff;font-size:13px;display:block;">🚗 Fleet Vehicles Overview</strong>
                            <span style="color:#94a3b8;font-size:11px;">Groups by Maryland vehicle license plates, tracking which car is on road, driver, and standby lot status.</span>
                        </div>
                    </label>
                    <label style="display:flex;align-items:center;gap:10px;background:rgba(255,255,255,0.05);padding:10px 14px;border-radius:8px;border:1px solid <?php echo ('instructors' === $current_tv_mode) ? '#0284c7' : 'rgba(255,255,255,0.1)'; ?>;cursor:pointer;">
                        <input type="radio" name="df_tv_view_mode" value="instructors" <?php checked($current_tv_mode, 'instructors'); ?>>
                        <div>
                            <strong style="color:#fff;font-size:13px;display:block;">👨‍🏫 Instructors Workload Overview</strong>
                            <span style="color:#94a3b8;font-size:11px;">Groups by certified instructors, active student assignments, and lesson topics.</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Column 2: Live Emergency & Urgent Alert Broadcast -->
            <div style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:10px;padding:18px;">
                <label style="font-size:13px;font-weight:700;color:#f87171;text-transform:uppercase;letter-spacing:0.5px;display:block;margin-bottom:8px;">
                    2. Urgent Announcement / Alert Broadcast
                </label>
                <p style="font-size:12px;color:#94a3b8;margin:0 0 12px 0;">
                    Instantly project a glowing urgent notification banner at the top of all connected TV monitors:
                </p>
                <div style="margin-bottom:10px;">
                    <textarea id="df-tv-alert-input" rows="2" style="width:100%;background:rgba(15,23,42,0.8);color:#fff;border:1px solid #475569;border-radius:8px;padding:10px;font-size:13px;resize:vertical;" placeholder="e.g., Weather Advisory: Rain showers in Montgomery County. Reduce speeds and keep headlights on."><?php echo esc_textarea($current_tv_alert['text'] ?? ''); ?></textarea>
                </div>
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <select id="df-tv-alert-level" style="background:#1e293b;color:#e2e8f0;border:1px solid #475569;border-radius:6px;padding:6px 10px;font-size:12px;">
                        <option value="warning" <?php selected(($current_tv_alert['level'] ?? ''), 'warning'); ?>>⚠️ Warning Alert (Amber)</option>
                        <option value="urgent" <?php selected(($current_tv_alert['level'] ?? ''), 'urgent'); ?>>🚨 Urgent Emergency (Red)</option>
                        <option value="info" <?php selected(($current_tv_alert['level'] ?? ''), 'info'); ?>>📢 Notice (Blue)</option>
                    </select>
                    <button type="button" class="df-btn df-btn-sm df-btn-danger" id="df-tv-push-alert-btn" style="padding:6px 14px;">
                        🔴 Broadcast to TV
                    </button>
                    <button type="button" class="df-btn df-btn-sm" id="df-tv-clear-alert-btn" style="background:rgba(255,255,255,0.08);color:#cbd5e1;border-color:rgba(255,255,255,0.2);padding:6px 12px;">
                        ✖ Clear
                    </button>
                </div>
                <?php if (!empty($current_tv_alert['text'])) : ?>
                    <div id="df-tv-active-alert-status" style="margin-top:10px;font-size:11px;color:#fbbf24;display:flex;align-items:center;gap:6px;">
                        <span>● Currently Broadcasting:</span>
                        <em>"<?php echo esc_html(wp_trim_words($current_tv_alert['text'], 10)); ?>"</em>
                    </div>
                <?php else : ?>
                    <div id="df-tv-active-alert-status" style="margin-top:10px;font-size:11px;color:#64748b;">
                        No active broadcast banner currently displayed.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Column 3: Multi-Message Announcement Ticker Manager -->
            <div style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:10px;padding:18px;">
                <label style="font-size:13px;font-weight:700;color:#34d399;text-transform:uppercase;letter-spacing:0.5px;display:block;margin-bottom:8px;">
                    3. Multi-Message Scrolling Ticker Headlines
                </label>
                <p style="font-size:12px;color:#94a3b8;margin:0 0 10px 0;">
                    Enter multiple announcements (one per line). All headlines cycle continuously on the TV bottom ticker:
                </p>
                <div style="margin-bottom:10px;">
                    <textarea id="df-tv-ticker-input" rows="4" style="width:100%;background:rgba(15,23,42,0.8);color:#fff;border:1px solid #475569;border-radius:8px;padding:10px;font-size:12px;font-family:inherit;line-height:1.5;resize:vertical;" placeholder="Welcome to Sam's Driving School LLC&#10;Please have your Maryland learner permit ready&#10;Safe driving is respect for life"><?php echo esc_textarea(implode("\n", $current_ticker_items)); ?></textarea>
                </div>
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <span style="font-size:11px;color:#64748b;">Separated automatically by ◆ bullets</span>
                    <button type="button" class="df-btn df-btn-sm" id="df-tv-save-ticker-btn" style="background:#10b981;color:#fff;border-color:#10b981;font-weight:700;padding:6px 16px;">
                        💾 Save Ticker Headlines
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 1: ALL ADMIN BACKEND MODULES -->
    <div style="margin-bottom:30px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
            <h2 style="font-size:18px;font-weight:700;color:#0f172a;margin:0;display:flex;align-items:center;gap:8px;">
                <span>🛠️</span> Core Admin Management Modules
            </h2>
            <span style="font-size:12px;color:#64748b;">Direct 1-click access to all backend operations</span>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(320px, 1fr));gap:16px;">
            <!-- Module 1: Calendar & Scheduling -->
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:20px;box-shadow:0 2px 4px rgba(0,0,0,0.04);display:flex;flex-direction:column;justify-content:space-between;transition:transform 0.15s,box-shadow 0.15s;" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 16px rgba(0,0,0,0.06)';" onmouseout="this.style.transform='none';this.style.boxShadow='0 2px 4px rgba(0,0,0,0.04)';">
                <div>
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                        <span style="background:#e0f2fe;color:#0284c7;font-size:20px;width:40px;height:40px;border-radius:8px;display:flex;align-items:center;justify-content:center;">📅</span>
                        <span style="background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;font-size:11px;font-weight:700;padding:2px 8px;border-radius:999px;">
                            <?php echo (int)($session_stats['upcoming'] ?? 0); ?> Upcoming Sessions
                        </span>
                    </div>
                    <h3 style="margin:0 0 6px 0;font-size:16px;color:#0f172a;">Visual Schedule & Calendar</h3>
                    <p style="font-size:13px;color:#64748b;margin:0 0 16px 0;line-height:1.5;">
                        Interactive drag-and-drop driving calendar, daily instructor schedules, blackout dates, room planning, and session booking.
                    </p>
                </div>
                <div style="display:flex;gap:8px;border-top:1px solid #f1f5f9;padding-top:14px;">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=driveflow-pro-calendar')); ?>" class="df-btn df-btn-primary df-btn-sm" style="flex:1;text-align:center;text-decoration:none;">
                        Open Calendar ↗
                    </a>
                </div>
            </div>

            <!-- Module 2: Driving Sessions & Logbook -->
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:20px;box-shadow:0 2px 4px rgba(0,0,0,0.04);display:flex;flex-direction:column;justify-content:space-between;transition:transform 0.15s,box-shadow 0.15s;" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 16px rgba(0,0,0,0.06)';" onmouseout="this.style.transform='none';this.style.boxShadow='0 2px 4px rgba(0,0,0,0.04)';">
                <div>
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                        <span style="background:#e0e7ff;color:#4338ca;font-size:20px;width:40px;height:40px;border-radius:8px;display:flex;align-items:center;justify-content:center;">📋</span>
                        <span style="background:#e0e7ff;color:#4338ca;border:1px solid #c7d2fe;font-size:11px;font-weight:700;padding:2px 8px;border-radius:999px;">
                            <?php echo (int)($session_stats['total'] ?? 0); ?> Total Records
                        </span>
                    </div>
                    <h3 style="margin:0 0 6px 0;font-size:16px;color:#0f172a;">Sessions & Student Scorecards</h3>
                    <p style="font-size:13px;color:#64748b;margin:0 0 16px 0;line-height:1.5;">
                        Complete behind-the-wheel logbook, student selfie verification, digital in-car signature pads, MVA ratings, and status auditing.
                    </p>
                </div>
                <div style="display:flex;gap:8px;border-top:1px solid #f1f5f9;padding-top:14px;">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=driveflow-pro-records')); ?>" class="df-btn df-btn-primary df-btn-sm" style="flex:1;text-align:center;text-decoration:none;">
                        Manage Sessions ↗
                    </a>
                </div>
            </div>

            <!-- Module 3: Students & Credits -->
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:20px;box-shadow:0 2px 4px rgba(0,0,0,0.04);display:flex;flex-direction:column;justify-content:space-between;transition:transform 0.15s,box-shadow 0.15s;" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 16px rgba(0,0,0,0.06)';" onmouseout="this.style.transform='none';this.style.boxShadow='0 2px 4px rgba(0,0,0,0.04)';">
                <div>
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                        <span style="background:#fef3c7;color:#b45309;font-size:20px;width:40px;height:40px;border-radius:8px;display:flex;align-items:center;justify-content:center;">🎓</span>
                        <span style="background:#fef3c7;color:#b45309;border:1px solid #fde68a;font-size:11px;font-weight:700;padding:2px 8px;border-radius:999px;">
                            <?php echo (int)($student_stats['active'] ?? 0); ?> Active Students
                        </span>
                    </div>
                    <h3 style="margin:0 0 6px 0;font-size:16px;color:#0f172a;">Students & Credit Hours</h3>
                    <p style="font-size:13px;color:#64748b;margin:0 0 16px 0;line-height:1.5;">
                        Enroll student profiles, manage driver education packages (36-hr, RSDEP, DIP), credit top-ups, remaining balances, and magic booking links.
                    </p>
                </div>
                <div style="display:flex;gap:8px;border-top:1px solid #f1f5f9;padding-top:14px;">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=driveflow-pro-students')); ?>" class="df-btn df-btn-primary df-btn-sm" style="flex:1;text-align:center;text-decoration:none;">
                        Manage Students ↗
                    </a>
                </div>
            </div>

            <!-- Module 4: Instructors & Staff -->
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:20px;box-shadow:0 2px 4px rgba(0,0,0,0.04);display:flex;flex-direction:column;justify-content:space-between;transition:transform 0.15s,box-shadow 0.15s;" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 16px rgba(0,0,0,0.06)';" onmouseout="this.style.transform='none';this.style.boxShadow='0 2px 4px rgba(0,0,0,0.04)';">
                <div>
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                        <span style="background:#dcfce7;color:#15803d;font-size:20px;width:40px;height:40px;border-radius:8px;display:flex;align-items:center;justify-content:center;">👨‍🏫</span>
                        <span style="background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;font-size:11px;font-weight:700;padding:2px 8px;border-radius:999px;">
                            <?php echo (int)($instructor_stats['signed_agreements'] ?? 0); ?> / <?php echo (int)($instructor_stats['total'] ?? 0); ?> Signed MVA
                        </span>
                    </div>
                    <h3 style="margin:0 0 6px 0;font-size:16px;color:#0f172a;">Instructors & Staff Roster</h3>
                    <p style="font-size:13px;color:#64748b;margin:0 0 16px 0;line-height:1.5;">
                        Active/Inactive toggle switches, state MVA licensing numbers, ID card & badge photo attachments, wage configurations, and signed contracts.
                    </p>
                </div>
                <div style="display:flex;gap:8px;border-top:1px solid #f1f5f9;padding-top:14px;">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=driveflow-pro-instructors')); ?>" class="df-btn df-btn-primary df-btn-sm" style="flex:1;text-align:center;text-decoration:none;">
                        Manage Instructors ↗
                    </a>
                </div>
            </div>

            <!-- Module 5: Vehicles & Training Fleet -->
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:20px;box-shadow:0 2px 4px rgba(0,0,0,0.04);display:flex;flex-direction:column;justify-content:space-between;transition:transform 0.15s,box-shadow 0.15s;" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 16px rgba(0,0,0,0.06)';" onmouseout="this.style.transform='none';this.style.boxShadow='0 2px 4px rgba(0,0,0,0.04)';">
                <div>
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                        <span style="background:#f3e8ff;color:#7e22ce;font-size:20px;width:40px;height:40px;border-radius:8px;display:flex;align-items:center;justify-content:center;">🚘</span>
                        <span style="background:#f3e8ff;color:#7e22ce;border:1px solid #d8b4fe;font-size:11px;font-weight:700;padding:2px 8px;border-radius:999px;">
                            <?php echo (int)($vehicle_stats['active'] ?? 0); ?> Active Cars
                        </span>
                    </div>
                    <h3 style="margin:0 0 6px 0;font-size:16px;color:#0f172a;">Vehicles & Fleet Management</h3>
                    <p style="font-size:13px;color:#64748b;margin:0 0 16px 0;line-height:1.5;">
                        Dual-control auxiliary brake verification, Maryland crab license plate live rendering, annual state safety inspection tracking, and 8-year age compliance.
                    </p>
                </div>
                <div style="display:flex;gap:8px;border-top:1px solid #f1f5f9;padding-top:14px;">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=driveflow-pro-vehicles')); ?>" class="df-btn df-btn-primary df-btn-sm" style="flex:1;text-align:center;text-decoration:none;">
                        Manage Fleet ↗
                    </a>
                </div>
            </div>

            <!-- Module 6: Reports & MVA Audits -->
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:20px;box-shadow:0 2px 4px rgba(0,0,0,0.04);display:flex;flex-direction:column;justify-content:space-between;transition:transform 0.15s,box-shadow 0.15s;" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 16px rgba(0,0,0,0.06)';" onmouseout="this.style.transform='none';this.style.boxShadow='0 2px 4px rgba(0,0,0,0.04)';">
                <div>
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                        <span style="background:#f1f5f9;color:#475569;font-size:20px;width:40px;height:40px;border-radius:8px;display:flex;align-items:center;justify-content:center;">📊</span>
                        <span style="background:#f1f5f9;color:#475569;border:1px solid #cbd5e1;font-size:11px;font-weight:700;padding:2px 8px;border-radius:999px;">
                            COMAR 11.23 Audits
                        </span>
                    </div>
                    <h3 style="margin:0 0 6px 0;font-size:16px;color:#0f172a;">Reports & Compliance Analytics</h3>
                    <p style="font-size:13px;color:#64748b;margin:0 0 16px 0;line-height:1.5;">
                        Official 3-year record retention audits, behind-the-wheel instruction hour exports, instructor payroll wage breakdowns, and printable PDF student logs.
                    </p>
                </div>
                <div style="display:flex;gap:8px;border-top:1px solid #f1f5f9;padding-top:14px;">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=driveflow-pro-reports')); ?>" class="df-btn df-btn-primary df-btn-sm" style="flex:1;text-align:center;text-decoration:none;">
                        View Reports & Audits ↗
                    </a>
                </div>
            </div>

            <!-- Module 7: Settings & Email Automations -->
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:20px;box-shadow:0 2px 4px rgba(0,0,0,0.04);display:flex;flex-direction:column;justify-content:space-between;transition:transform 0.15s,box-shadow 0.15s;" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 16px rgba(0,0,0,0.06)';" onmouseout="this.style.transform='none';this.style.boxShadow='0 2px 4px rgba(0,0,0,0.04)';">
                <div>
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                        <span style="background:#ccfbf1;color:#0f766e;font-size:20px;width:40px;height:40px;border-radius:8px;display:flex;align-items:center;justify-content:center;">⚙️</span>
                        <span style="background:#ccfbf1;color:#0f766e;border:1px solid #99f6e4;font-size:11px;font-weight:700;padding:2px 8px;border-radius:999px;">
                            Email, Sync & Legal
                        </span>
                    </div>
                    <h3 style="margin:0 0 6px 0;font-size:16px;color:#0f172a;">Settings & Legal Framework</h3>
                    <p style="font-size:13px;color:#64748b;margin:0 0 16px 0;line-height:1.5;">
                        Branding, school address/phones, automated notification emails, Wappointment 2-way sync, and editable Maryland MVA Employment Agreement text.
                    </p>
                </div>
                <div style="display:flex;gap:8px;border-top:1px solid #f1f5f9;padding-top:14px;">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=driveflow-pro-settings')); ?>" class="df-btn df-btn-primary df-btn-sm" style="flex:1;text-align:center;text-decoration:none;">
                        Configure Settings ↗
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 2: FRONT-END PORTALS & LIVE PUBLIC DISPLAYS MATRIX -->
    <div style="margin-bottom:30px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
            <h2 style="font-size:18px;font-weight:700;color:#0f172a;margin:0;display:flex;align-items:center;gap:8px;">
                <span>🌐</span> Front-End Portals & Live Displays Directory
            </h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="driveflow_provision_pages">
                <?php wp_nonce_field('driveflow_provision_pages'); ?>
                <button type="submit" class="button button-small">
                    🔄 Verify & Re-Provision All Pages
                </button>
            </form>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(340px, 1fr));gap:16px;">
            <?php foreach ($portals as $key => $p) : 
                $has_page = !empty($p['page_id']) && 'publish' === get_post_status($p['page_id']);
            ?>
                <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:10px;padding:20px;box-shadow:0 2px 4px rgba(0,0,0,0.04);display:flex;flex-direction:column;justify-content:space-between;">
                    <div>
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <span style="font-size:24px;"><?php echo esc_html($p['icon']); ?></span>
                                <h3 style="margin:0;font-size:15px;color:#0f172a;font-weight:700;"><?php echo esc_html($p['title']); ?></h3>
                            </div>
                            <?php if ($has_page) : ?>
                                <span style="background:#dcfce7;color:#15803d;font-size:11px;font-weight:700;padding:2px 8px;border-radius:999px;border:1px solid #bbf7d0;">✓ Published</span>
                            <?php else : ?>
                                <span style="background:#fee2e2;color:#b91c1c;font-size:11px;font-weight:700;padding:2px 8px;border-radius:999px;border:1px solid #fecaca;">Page Missing</span>
                            <?php endif; ?>
                        </div>
                        <p style="font-size:12px;color:#64748b;margin:0 0 12px 0;line-height:1.5;">
                            <?php echo esc_html($p['desc']); ?>
                        </p>
                        <div style="background:#f8fafc;padding:6px 10px;border-radius:6px;border:1px solid #e2e8f0;font-family:monospace;font-size:11px;color:#0f766e;margin-bottom:8px;display:flex;justify-content:space-between;align-items:center;">
                            <span>Shortcode: <strong><?php echo esc_html($p['shortcode']); ?></strong></span>
                        </div>
                        <div style="background:#f1f5f9;padding:6px 10px;border-radius:6px;border:1px solid #cbd5e1;margin-bottom:14px;display:flex;align-items:center;justify-content:space-between;gap:8px;">
                            <span style="font-family:monospace;font-size:11px;color:#334155;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo esc_attr($p['url']); ?>">
                                🔗 <?php echo esc_html($p['url']); ?>
                            </span>
                            <button type="button" class="df-btn df-btn-secondary df-btn-xs" data-df-copy-url="<?php echo esc_attr($p['url']); ?>" style="padding:3px 10px;font-size:11px;white-space:nowrap;font-weight:700;">
                                📋 Copy Link
                            </button>
                        </div>
                    </div>
                    <div style="display:flex;gap:8px;border-top:1px solid #f1f5f9;padding-top:12px;flex-wrap:wrap;">
                        <a href="<?php echo esc_url($p['url']); ?>" target="_blank" class="df-btn df-btn-primary df-btn-sm" style="flex:1;text-align:center;text-decoration:none;min-width:120px;">
                            Launch Portal ↗
                        </a>
                        <?php if ('tv' === $key) : 
                            $kiosk_url = add_query_arg('kiosk', '1', $p['url']);
                        ?>
                            <a href="<?php echo esc_url($kiosk_url); ?>" target="_blank" class="df-btn df-btn-sm" style="background:#059669;color:#fff;border:none;text-decoration:none;font-weight:700;" title="Open dedicated full-screen TV Kiosk mode (No WP headers/footers)">
                                📺 Full Kiosk ↗
                            </a>
                        <?php endif; ?>
                        <button type="button" class="df-btn df-btn-secondary df-btn-sm" data-df-copy-url="<?php echo esc_attr('tv' === $key ? add_query_arg('kiosk', '1', $p['url']) : $p['url']); ?>" title="Copy Link to Clipboard">
                            📋 Copy
                        </button>
                        <?php if ($has_page) : ?>
                            <a href="<?php echo esc_url(get_edit_post_link($p['page_id'])); ?>" class="df-btn df-btn-secondary df-btn-sm" title="Edit WordPress Page">
                                Edit Page
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Master Portals & Quick Access Summary Box -->
        <div style="background:linear-gradient(135deg, #f8fafc 0%, #edf2f7 100%);border:1px solid #cbd5e1;border-radius:12px;padding:20px 24px;margin-top:20px;box-shadow:0 2px 6px rgba(0,0,0,0.03);">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:12px;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <span style="font-size:22px;">🔑</span>
                    <div>
                        <strong style="font-size:15px;color:#0f172a;display:block;">System Entry Point Reference Guide</strong>
                        <span style="font-size:12px;color:#64748b;">Clear breakdown of how Admin, Instructors, and Students access their respective systems.</span>
                    </div>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));gap:16px;font-size:13px;">
                <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:8px;padding:14px;">
                    <div style="font-weight:700;color:#0369a1;margin-bottom:4px;display:flex;align-items:center;justify-content:space-between;">
                        <span>👑 Administrator Access</span>
                        <span style="background:#e0f2fe;color:#0284c7;font-size:10px;padding:2px 6px;border-radius:4px;">Backend</span>
                    </div>
                    <p style="color:#64748b;font-size:12px;margin:0 0 8px 0;">Admin signs into WordPress at <code>/wp-admin/</code> and opens <strong>DriveFlow Pro</strong> from the sidebar.</p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=driveflow-pro-hub')); ?>" class="df-btn df-btn-secondary df-btn-xs" style="text-decoration:none;font-size:11px;">
                        Open Admin Hub: <code>/wp-admin/admin.php?page=driveflow-pro-hub</code>
                    </a>
                </div>
                <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:8px;padding:14px;">
                    <div style="font-weight:700;color:#15803d;margin-bottom:4px;display:flex;align-items:center;justify-content:space-between;">
                        <span>🚗 Instructor In-Car App</span>
                        <span style="background:#dcfce7;color:#15803d;font-size:10px;padding:2px 6px;border-radius:4px;">Frontend</span>
                    </div>
                    <p style="color:#64748b;font-size:12px;margin:0 0 8px 0;">Instructors do <strong>not</strong> need a WP login. They simply bookmark the In-Car Terminal page on their phone/tablet.</p>
                    <?php $ins_url = $portals['instructor_app']['url'] ?? ''; ?>
                    <button type="button" class="df-btn df-btn-secondary df-btn-xs" data-df-copy-url="<?php echo esc_attr($ins_url); ?>" style="font-size:11px;">
                        📋 Copy In-Car App Link
                    </button>
                </div>
                <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:8px;padding:14px;">
                    <div style="font-weight:700;color:#7e22ce;margin-bottom:4px;display:flex;align-items:center;justify-content:space-between;">
                        <span>✍️ Instructor Onboarding & Contract</span>
                        <span style="background:#f3e8ff;color:#7e22ce;font-size:10px;padding:2px 6px;border-radius:4px;">Onboarding</span>
                    </div>
                    <p style="color:#64748b;font-size:12px;margin:0 0 8px 0;">Send this link to new instructors to upload ID/Badge photos and sign the Maryland MVA contract.</p>
                    <?php $onb_url = $portals['onboarding']['url'] ?? ''; ?>
                    <button type="button" class="df-btn df-btn-secondary df-btn-xs" data-df-copy-url="<?php echo esc_attr($onb_url); ?>" style="font-size:11px;">
                        📋 Copy Onboarding Link
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 3: LEGAL FRAMEWORK & MARYLAND REGULATORY COMPLIANCE CENTER -->
    <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;padding:24px;margin-bottom:30px;box-shadow:0 2px 6px rgba(0,0,0,0.04);">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:12px;">
            <div style="display:flex;align-items:center;gap:10px;">
                <span style="font-size:24px;">🏛️</span>
                <div>
                    <h2 style="font-size:17px;font-weight:700;color:#0f172a;margin:0;">
                        Legal Framework & Maryland MVA Regulatory Compliance
                    </h2>
                    <span style="font-size:12px;color:#64748b;">
                        Governed by Annotated Code of Maryland Title 15 & COMAR Title 11 (Subtitles 23 & 12)
                    </span>
                </div>
            </div>
            <div style="display:flex;gap:10px;">
                <a href="<?php echo esc_url(admin_url('admin.php?page=driveflow-pro-settings#driveflow_legal')); ?>" class="df-btn df-btn-secondary df-btn-sm">
                    ✏️ Edit Agreement Text in Settings
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=driveflow-pro-instructors')); ?>" class="df-btn df-btn-primary df-btn-sm">
                    📜 Inspect Signed Contracts
                </a>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));gap:16px;">
            <!-- Compliance Item 1: Agreement Status -->
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:16px;">
                <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:6px;">Instructor Agreement Requirement</div>
                <?php if (!empty($settings['instructor_agreement_enabled']) && '1' === (string)$settings['instructor_agreement_enabled']) : ?>
                    <div style="font-size:15px;font-weight:700;color:#16a34a;margin-bottom:4px;">✓ Enforced & Mandatory</div>
                    <p style="font-size:12px;color:#64748b;margin:0;">Instructors must review, accept, and sign via touch/mouse canvas pad before onboarding.</p>
                <?php else : ?>
                    <div style="font-size:15px;font-weight:700;color:#d97706;margin-bottom:4px;">○ Optional / Disabled</div>
                    <p style="font-size:12px;color:#64748b;margin:0;">Agreements are currently bypassed. Enable in Settings to enforce MVA statutory covenants.</p>
                <?php endif; ?>
            </div>

            <!-- Compliance Item 2: Worker Classification -->
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:16px;">
                <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:6px;">Worker Classification</div>
                <div style="font-size:15px;font-weight:700;color:#0369a1;margin-bottom:4px;">W-2 Statutory Employee Mode</div>
                <p style="font-size:12px;color:#64748b;margin:0;">Complies with Maryland Labor & Employment § 8-205 "ABC Test" and COMAR school supervision rules.</p>
            </div>

            <!-- Compliance Item 3: 8-Year Fleet Age Limit -->
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:16px;">
                <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:6px;">Fleet Age Standard (COMAR 11.23.02)</div>
                <div style="font-size:15px;font-weight:700;color:#16a34a;margin-bottom:4px;">8-Year Operational Cap</div>
                <p style="font-size:12px;color:#64748b;margin:0;">Model Year 2018 vehicles are barred. Model Years 2019–2026 are active and compliant.</p>
            </div>

            <!-- Compliance Item 4: Digital Signatures & UETA -->
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:16px;">
                <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:6px;">Electronic Signatures & Audit Trail</div>
                <div style="font-size:15px;font-weight:700;color:#7e22ce;margin-bottom:4px;">Maryland UETA Certified</div>
                <p style="font-size:12px;color:#64748b;margin:0;">Captures cryptographic timestamp, signer IP address, and raw signature vectors for audit records.</p>
            </div>
        </div>
    </div>

    <!-- SECTION 4: RECENT / UPCOMING SCHEDULE FEED & SYSTEM DIAGNOSTICS -->
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:30px;">
        <!-- Live Upcoming Sessions Feed -->
        <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;padding:22px;box-shadow:0 2px 6px rgba(0,0,0,0.04);">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                <h3 style="font-size:16px;font-weight:700;color:#0f172a;margin:0;display:flex;align-items:center;gap:8px;">
                    <span>⏱️</span> Upcoming Scheduled Driving Sessions
                </h3>
                <a href="<?php echo esc_url(admin_url('admin.php?page=driveflow-pro-records')); ?>" style="font-size:12px;color:#0284c7;text-decoration:none;font-weight:600;">
                    View All Sessions →
                </a>
            </div>

            <?php if (!empty($upcoming_sessions)) : ?>
                <table style="width:100%;font-size:13px;border-collapse:collapse;">
                    <thead>
                        <tr style="border-bottom:2px solid #f1f5f9;text-align:left;color:#64748b;font-size:11px;text-transform:uppercase;">
                            <th style="padding:8px 6px;">Student</th>
                            <th style="padding:8px 6px;">Instructor</th>
                            <th style="padding:8px 6px;">Vehicle Plate</th>
                            <th style="padding:8px 6px;">Scheduled Time</th>
                            <th style="padding:8px 6px;text-align:right;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upcoming_sessions as $sess) : ?>
                            <tr style="border-bottom:1px solid #f8fafc;">
                                <td style="padding:10px 6px;font-weight:600;color:#0f172a;"><?php echo esc_html($sess['student_name'] ?: '—'); ?></td>
                                <td style="padding:10px 6px;color:#475569;"><?php echo esc_html($sess['instructor_name'] ?: '—'); ?></td>
                                <td style="padding:10px 6px;">
                                    <span style="font-family:monospace;font-weight:700;color:#0f766e;background:#f0fdf4;padding:2px 6px;border-radius:4px;border:1px solid #bbf7d0;">
                                        <?php echo esc_html($sess['plate_number'] ?: 'Dual-Control'); ?>
                                    </span>
                                </td>
                                <td style="padding:10px 6px;color:#64748b;font-size:12px;">
                                    <?php echo date_i18n('M j, g:i A', strtotime($sess['scheduled_start'])); ?>
                                </td>
                                <td style="padding:10px 6px;text-align:right;">
                                    <span style="font-size:11px;font-weight:700;padding:2px 8px;border-radius:999px;text-transform:uppercase;<?php 
                                        echo ('completed' === $sess['status']) ? 'background:#dcfce7;color:#15803d;' : (('active' === $sess['status']) ? 'background:#fef3c7;color:#b45309;' : 'background:#e0f2fe;color:#0369a1;');
                                    ?>">
                                        <?php echo esc_html($sess['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <div style="text-align:center;padding:30px 10px;color:#94a3b8;">
                    <div style="font-size:32px;margin-bottom:8px;">📅</div>
                    <p style="margin:0;font-size:13px;">No upcoming sessions scheduled for today yet.</p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=driveflow-pro-calendar')); ?>" class="df-btn df-btn-primary df-btn-sm" style="margin-top:12px;display:inline-block;text-decoration:none;">
                        Schedule Session on Calendar
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- System Health Diagnostics Box -->
        <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;padding:22px;box-shadow:0 2px 6px rgba(0,0,0,0.04);">
            <h3 style="font-size:16px;font-weight:700;color:#0f172a;margin:0 0 14px 0;display:flex;align-items:center;gap:8px;">
                <span>🩺</span> System Diagnostics
            </h3>
            <table style="width:100%;font-size:12px;border-collapse:collapse;">
                <?php foreach ($health as $label => $status_val) : ?>
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:8px 0;color:#334155;"><?php echo esc_html($label); ?></td>
                        <td style="padding:8px 0;text-align:right;">
                            <?php 
                            if (true === $status_val) {
                                echo '<span style="color:#16a34a;font-weight:700;background:#f0fdf4;padding:2px 7px;border-radius:4px;border:1px solid #bbf7d0;">Ready (OK)</span>';
                            } elseif ('optional' === $status_val) {
                                echo '<span style="color:#0284c7;font-weight:600;background:#f0f9ff;padding:2px 7px;border-radius:4px;border:1px solid #bae6fd;">Optional</span>';
                            } else {
                                echo '<span style="color:#dc2626;font-weight:700;background:#fef2f2;padding:2px 7px;border-radius:4px;border:1px solid #fecaca;">Action Needed</span>';
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr style="border-bottom:1px solid #f1f5f9;">
                    <td style="padding:8px 0;color:#334155;">Wappointment Integration</td>
                    <td style="padding:8px 0;text-align:right;">
                        <?php if ($wapp_detected) : ?>
                            <span style="color:#7e22ce;font-weight:700;background:#f3e8ff;padding:2px 7px;border-radius:4px;border:1px solid #d8b4fe;">Connected</span>
                        <?php else : ?>
                            <span style="color:#64748b;font-weight:600;background:#f1f5f9;padding:2px 7px;border-radius:4px;">Standalone</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <div style="margin-top:16px;padding-top:14px;border-top:1px solid #f1f5f9;text-align:center;">
                <span style="font-size:11px;color:#94a3b8;">DriveFlow Pro v<?php echo esc_html(DRIVEFLOW_PRO_VERSION); ?> · High-Performance Build</span>
            </div>
        </div>
    </div>
</div>

<!-- Reusable Modal: Magic Link Generator -->
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

<!-- ══════════════════ MASTER OPERATIONS MANUAL & GUIDE MODAL ══════════════════ -->
<div class="df-modal-backdrop" id="df-operations-guide-modal" style="display:none;align-items:center;justify-content:center;z-index:999999;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(15,23,42,0.8);backdrop-filter:blur(4px);">
    <div class="df-modal-dialog" style="max-width:880px;width:95%;max-height:90vh;display:flex;flex-direction:column;background:#ffffff;border-radius:14px;box-shadow:0 25px 50px -12px rgba(0,0,0,0.35);overflow:hidden;">
        <div class="df-modal-header" style="background:linear-gradient(135deg, #091e3a 0%, #0f2b48 100%);color:#fff;padding:20px 24px;display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:12px;">
                <span style="font-size:26px;">📖</span>
                <div>
                    <h3 style="margin:0;font-size:17px;font-weight:800;color:#fff;letter-spacing:-0.3px;">DriveFlow Pro · Master Operations Manual</h3>
                    <span style="font-size:12px;color:#93c5fd;">Official Administrative Guide & System Architecture</span>
                </div>
            </div>
            <button type="button" class="df-btn-close" id="df-close-guide-modal-btn" style="background:rgba(255,255,255,0.15);border:none;color:#fff;font-size:18px;width:32px;height:32px;border-radius:8px;cursor:pointer;display:flex;align-items:center;justify-content:center;">✕</button>
        </div>

        <div class="df-modal-body" style="padding:24px;overflow-y:auto;flex:1;background:#f8fafc;">
            <!-- Grid of Guide Cards -->
            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(360px, 1fr));gap:16px;">
                <!-- Card 1: Onboarding Token & Screenshot Explanation -->
                <div style="background:#fff;border:1px solid #e2e8f0;border-left:4px solid #0284c7;border-radius:10px;padding:18px;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                        <span style="font-size:20px;">🔒</span>
                        <h4 style="margin:0;font-size:14px;font-weight:800;color:#0f172a;">1. Instructor Onboarding & Token Security (Screenshot Explained)</h4>
                    </div>
                    <p style="font-size:12px;color:#475569;line-height:1.6;margin:0 0 8px 0;">
                        <strong>Why the lock screen appears:</strong> When opening the <em>Instructor Onboarding</em> page directly without a token, this protective screen ensures unauthorized public visitors cannot register themselves as academy driving instructors.
                    </p>
                    <p style="font-size:12px;color:#475569;line-height:1.6;margin:0;">
                        <strong>How to issue an onboarding link:</strong> Go to <strong>Certified Instructors</strong> or click <strong>"⚡ Issue Magic Link"</strong> in the top header, select the new instructor profile, and copy the generated link. When the instructor clicks that personalized link, the lock screen vanishes and opens their complete profile registration, MVA license upload, banking/Zelle payout setup, and digital agreement contract!
                    </p>
                </div>

                <!-- Card 2: Dedicated Admin Portal & Login -->
                <div style="background:#fff;border:1px solid #e2e8f0;border-left:4px solid #10b981;border-radius:10px;padding:18px;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                        <span style="font-size:20px;">🖥️</span>
                        <h4 style="margin:0;font-size:14px;font-weight:800;color:#0f172a;">2. Dedicated Executive Admin Portal</h4>
                    </div>
                    <p style="font-size:12px;color:#475569;line-height:1.6;margin:0 0 8px 0;">
                        <strong>Access URL:</strong> <a href="<?php echo esc_url(site_url('/?driveflow_admin=1')); ?>" target="_blank" style="color:#0284c7;font-weight:700;"><?php echo esc_html(site_url('/?driveflow_admin=1')); ?></a>
                    </p>
                    <p style="font-size:12px;color:#475569;line-height:1.6;margin:0;">
                        Provides a standalone, ultra-luxurious dark mode executive login portal. All WordPress theme headers, navigation bars, and footers are automatically suppressed, giving you a clean, dedicated application environment on any office computer or tablet.
                    </p>
                </div>

                <!-- Card 3: Lobby TV Screen Remote Commander -->
                <div style="background:#fff;border:1px solid #e2e8f0;border-left:4px solid #f59e0b;border-radius:10px;padding:18px;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                        <span style="font-size:20px;">📺</span>
                        <h4 style="margin:0;font-size:14px;font-weight:800;color:#0f172a;">3. Lobby TV Screen Remote Commander</h4>
                    </div>
                    <p style="font-size:12px;color:#475569;line-height:1.6;margin:0 0 8px 0;">
                        <strong>Remote View Modes:</strong> Change the second-monitor TV display between <em>2-Hour Slots</em>, <em>Fleet Vehicles</em>, and <em>Instructors</em> directly from the Admin Hub without touching the TV screen.
                    </p>
                    <p style="font-size:12px;color:#475569;line-height:1.6;margin:0;">
                        <strong>Urgent Audio-Visual Banners:</strong> Broadcast weather warnings, cancellations, or announcements instantly with an audible chime, and manage multiple scrolling headlines in the bottom ticker.
                    </p>
                </div>

                <!-- Card 4: Fuel Gauge & Vehicle Maintenance -->
                <div style="background:#fff;border:1px solid #e2e8f0;border-left:4px solid #ef4444;border-radius:10px;padding:18px;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                        <span style="font-size:20px;">⛽</span>
                        <h4 style="margin:0;font-size:14px;font-weight:800;color:#0f172a;">4. Vehicle Fuel Gauge & Maintenance Protocol</h4>
                    </div>
                    <p style="font-size:12px;color:#475569;line-height:1.6;margin:0 0 8px 0;">
                        <strong>Mandatory Fuel Logging:</strong> Instructors must record the fuel gauge at the end of each session. If fuel is ≤ 25% (1/4 tank), an emergency alert requires the instructor to conduct the <em>Maryland MVA Practical Student Refueling Module</em>.
                    </p>
                    <p style="font-size:12px;color:#475569;line-height:1.6;margin:0;">
                        <strong>Incident & Grounding Reports:</strong> Instructors can report flat tires, damaged wiper blades, or brake squeal. Urgent issues immediately ground the vehicle from future student dispatch until cleared by maintenance.
                    </p>
                </div>

                <!-- Card 5: Interactive Visual Calendar -->
                <div style="background:#fff;border:1px solid #e2e8f0;border-left:4px solid #8b5cf6;border-radius:10px;padding:18px;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                        <span style="font-size:20px;">📅</span>
                        <h4 style="margin:0;font-size:14px;font-weight:800;color:#0f172a;">5. Visual Calendar & Google Live Time Line</h4>
                    </div>
                    <p style="font-size:12px;color:#475569;line-height:1.6;margin:0 0 8px 0;">
                        <strong>Google-Calendar Real-Time Line:</strong> A live glowing red indicator line moves across today's schedule every 30 seconds showing the exact current minute.
                    </p>
                    <p style="font-size:12px;color:#475569;line-height:1.6;margin:0;">
                        <strong>Multi-Perspective Dispatch:</strong> View the 7-day schedule by 2-hour lesson blocks, or view the daily grid organized by Maryland training fleet cars or certified instructors.
                    </p>
                </div>

                <!-- Card 6: Student Credit Hours & Self-Booking -->
                <div style="background:#fff;border:1px solid #e2e8f0;border-left:4px solid #ec4899;border-radius:10px;padding:18px;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                        <span style="font-size:20px;">🎓</span>
                        <h4 style="margin:0;font-size:14px;font-weight:800;color:#0f172a;">6. Student Credits & Magic Booking Links</h4>
                    </div>
                    <p style="font-size:12px;color:#475569;line-height:1.6;margin:0 0 8px 0;">
                        <strong>Automated Credits:</strong> Manage 36-Hour Driver Education packages, DIP courses, and extra sessions. Every completed session automatically deducts a credit from the student's ledger.
                    </p>
                    <p style="font-size:12px;color:#475569;line-height:1.6;margin:0;">
                        <strong>Mobile Self-Booking:</strong> Send magic booking links directly to students via SMS or email so they can choose available slots from their smartphone without needing WordPress logins.
                    </p>
                </div>
            </div>
        </div>

        <div class="df-modal-footer" style="background:#f1f5f9;padding:14px 24px;display:flex;align-items:center;justify-content:flex-end;border-top:1px solid #e2e8f0;">
            <button type="button" class="df-btn df-btn-secondary" id="df-close-guide-modal-btn-2">Close Guide</button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function ($) {
    // Open Operations Guide Modal
    $('#df-hub-open-guide-btn').on('click', function(e) {
        e.preventDefault();
        $('#df-operations-guide-modal').css('display', 'flex').addClass('is-open');
    });

    // Close Operations Guide Modal
    $('#df-close-guide-modal-btn, #df-close-guide-modal-btn-2').on('click', function(e) {
        e.preventDefault();
        $('#df-operations-guide-modal').css('display', 'none').removeClass('is-open');
    });

    $('#df-operations-guide-modal').on('click', function(e) {
        if ($(e.target).is('#df-operations-guide-modal')) {
            $(this).css('display', 'none').removeClass('is-open');
        }
    });

    // Hub Quick Student Magic Link
    $('#df-hub-issue-student-btn').on('click', function (e) {
        e.preventDefault();
        var $sel = $('#df-hub-student-select option:selected');
        var id = $sel.val();
        if (!id) {
            alert('Please select a student from the dropdown list first.');
            return;
        }
        var name = $sel.data('name') || 'Student';
        var email = $sel.data('email') || '';

        $('#df-magic-link-type').val('student_booking');
        $('#df-magic-link-target-id').val(id);
        $('#df-magic-recipient-name').text(name + ' (Student)');
        $('#df-magic-recipient-email').text(email ? ('✉️ ' + email) : 'No email on file (Send via SMS/WhatsApp)');
        
        if (!email) {
            $('#df-magic-email-checkbox-wrap').hide();
            $('#df-magic-send-email').prop('checked', false);
        } else {
            $('#df-magic-email-checkbox-wrap').show();
            $('#df-magic-send-email').prop('checked', true);
        }

        $('#df-magic-result-box').hide();
        $('#df-generate-magic-submit-btn').prop('disabled', false).text('🚀 Generate Link');
        $('#df-magic-link-modal').addClass('is-open');
    });

    // Hub Quick Instructor Magic Link
    $('#df-hub-issue-instructor-btn').on('click', function (e) {
        e.preventDefault();
        var $sel = $('#df-hub-instructor-select option:selected');
        var id = $sel.val();
        if (!id) {
            alert('Please select an instructor from the dropdown list first.');
            return;
        }
        var name = $sel.data('name') || 'Instructor';
        var email = $sel.data('email') || '';

        $('#df-magic-link-type').val('instructor_onboarding');
        $('#df-magic-link-target-id').val(id);
        $('#df-magic-recipient-name').text(name + ' (Instructor)');
        $('#df-magic-recipient-email').text(email ? ('✉️ ' + email) : 'No email on file (Send via SMS/WhatsApp)');
        
        if (!email) {
            $('#df-magic-email-checkbox-wrap').hide();
            $('#df-magic-send-email').prop('checked', false);
        } else {
            $('#df-magic-email-checkbox-wrap').show();
            $('#df-magic-send-email').prop('checked', true);
        }

        $('#df-magic-result-box').hide();
        $('#df-generate-magic-submit-btn').prop('disabled', false).text('🚀 Generate Link');
        $('#df-magic-link-modal').addClass('is-open');
    });

    // Open General Quick Magic modal
    $('#df-hub-open-quick-magic').on('click', function (e) {
        e.preventDefault();
        $('#df-hub-issue-student-btn').trigger('click');
    });
});
</script>
