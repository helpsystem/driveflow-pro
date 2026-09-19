<?php
defined('ABSPATH') || exit;

/**
 * DriveFlow Pro - Reports & Analytics Template
 *
 * Variables passed from driveflow-pro.php reports_page():
 * @var string $active_tab
 * @var string $date_preset
 * @var string $start_date
 * @var string $end_date
 * @var string $search
 * @var int    $paged
 * @var int    $per_page
 * @var int    $total_rows
 * @var int    $total_pages
 * @var array  $kpis
 * @var array  $rows
 * @var array  $overview_data
 */

$tab_names = array(
    'overview'    => array('label' => 'Executive Overview', 'icon' => 'dashicons-chart-pie'),
    'payroll'     => array('label' => 'Instructor Payroll & Archive', 'icon' => 'dashicons-money-alt'),
    'daily'       => array('label' => 'Daily Breakdown', 'icon' => 'dashicons-calendar-alt'),
    'monthly'     => array('label' => 'Monthly Trends', 'icon' => 'dashicons-chart-line'),
    'instructors' => array('label' => 'By Instructor', 'icon' => 'dashicons-businessperson'),
    'students'    => array('label' => 'By Student', 'icon' => 'dashicons-welcome-learn-more'),
    'vehicles'    => array('label' => 'Fleet Utilization', 'icon' => 'dashicons-car'),
);

$presets = array(
    'today'      => 'Today',
    'yesterday'  => 'Yesterday',
    'this_week'  => 'This Week',
    'this_month' => 'This Month',
    'last_month' => 'Last Month',
    'this_year'  => 'This Year',
    'all'        => 'All Time',
);

// Helper for pagination URL building
$build_page_url = function($page_num) use ($active_tab, $date_preset, $start_date, $end_date, $search, $per_page) {
    return add_query_arg(array(
        'page'        => 'driveflow-pro-reports',
        'tab'         => $active_tab,
        'date_preset' => $date_preset,
        'start_date'  => $start_date,
        'end_date'    => $end_date,
        's'           => $search,
        'per_page'    => $per_page,
        'paged'       => $page_num,
    ), admin_url('admin.php'));
};
?>

<style>
/* Inline Critical Styles for Reports & Analytics to guarantee immediate styling */
.df-date-pills { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; margin-bottom: 16px; }
.df-date-pill { padding: 6px 14px; font-size: 12px; font-weight: 600; border-radius: 20px; border: 1px solid #cbd5e1; background: #ffffff; color: #475569; text-decoration: none; display: inline-block; transition: all 0.15s ease; }
.df-date-pill:hover { background: #f1f5f9; border-color: #94a3b8; color: #0f172a; }
.df-date-pill.active { background: #0f766e; border-color: #0f766e; color: #ffffff; box-shadow: 0 2px 4px rgba(15, 118, 110, 0.25); font-weight: 700; }

.df-kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
.df-kpi-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 20px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 2px 5px rgba(0,0,0,0.04); }
.df-kpi-card:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,0.06); transition: all 0.2s; }
.df-kpi-content h4 { margin: 0 0 6px 0; font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
.df-kpi-value { font-size: 26px; font-weight: 800; color: #0f172a; line-height: 1; margin-bottom: 4px; }
.df-kpi-sub { font-size: 11px; color: #94a3b8; font-weight: 500; }
.df-kpi-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; }
.df-kpi-icon.teal { background: #ccfbf1; color: #0f766e; }
.df-kpi-icon.blue { background: #dbeafe; color: #1d4ed8; }
.df-kpi-icon.green { background: #dcfce7; color: #15803d; }
.df-kpi-icon.purple { background: #f3e8ff; color: #7e22ce; }
.df-kpi-icon.amber { background: #fef3c7; color: #b45309; }

.df-report-nav { display: flex; align-items: center; gap: 8px; border-bottom: 2px solid #e2e8f0; margin-bottom: 24px; overflow-x: auto; padding-bottom: 2px; }
.df-report-tab { display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; font-size: 14px; font-weight: 600; color: #64748b; text-decoration: none; border-radius: 8px 8px 0 0; border-bottom: 3px solid transparent; white-space: nowrap; transition: all 0.15s; }
.df-report-tab:hover { color: #0f766e; background: rgba(15, 118, 110, 0.05); }
.df-report-tab.active { color: #0f766e; border-bottom-color: #0f766e; background: #ffffff; font-weight: 700; }

.df-pagination-bar { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; padding: 16px 20px; background: #ffffff; border-top: 1px solid #e2e8f0; border-radius: 0 0 12px 12px; }
.df-pagination-controls { display: flex; align-items: center; gap: 6px; }
.df-page-btn { display: inline-flex; align-items: center; justify-content: center; min-width: 34px; height: 34px; padding: 0 10px; border: 1px solid #cbd5e1; background: #ffffff; color: #334155; font-size: 13px; font-weight: 600; border-radius: 6px; text-decoration: none; }
.df-page-btn.active { background: #0f766e; border-color: #0f766e; color: #ffffff; }
.df-page-btn.disabled { opacity: 0.45; pointer-events: none; }
</style>

<div class="wrap driveflow-admin-wrap" dir="ltr">
    <!-- Header Banner -->
    <div class="df-header-banner">
        <div class="df-header-title">
            <div>
                <h1>DriveFlow Pro · Reports & Operational Analytics</h1>
                <p>Comprehensive academy reporting across daily & monthly trends, driving instructor metrics, student course progress, and fleet vehicle utilization.</p>
            </div>
        </div>
        <div class="df-header-actions">
            <a href="<?php echo esc_url(add_query_arg(array(
                'action'      => 'driveflow_export_report_csv',
                'tab'         => $active_tab,
                'date_preset' => $date_preset,
                'start_date'  => $start_date,
                'end_date'    => $end_date,
                's'           => $search,
                '_wpnonce'    => wp_create_nonce('driveflow_export_report_csv'),
            ), admin_url('admin-post.php'))); ?>" class="df-btn df-btn-secondary">
                <span class="dashicons dashicons-download"></span> Export Current Report (CSV)
            </a>
            <?php 
            $print_url = add_query_arg(array(
                'page'        => 'driveflow-pro-reports',
                'print_view'  => '1',
                'tab'         => $active_tab,
                'date_preset' => $date_preset,
                'start_date'  => $start_date,
                'end_date'    => $end_date,
                's'           => $search,
            ), admin_url('admin.php'));
            ?>
            <button type="button" class="df-btn df-btn-primary" onclick="window.open('<?php echo esc_url($print_url); ?>', '_blank', 'width=1100,height=900,scrollbars=yes');">
                <span class="dashicons dashicons-printer"></span> Print Official Report (MVA Letterhead)
            </button>
        </div>
    </div>

    <!-- Date Range & Preset Pills -->
    <div class="df-date-pills">
        <span style="font-size:12px;font-weight:700;color:#64748b;margin-right:6px;text-transform:uppercase;">Timeframe:</span>
        <?php foreach ($presets as $key => $label) : ?>
            <a href="<?php echo esc_url(add_query_arg(array('page' => 'driveflow-pro-reports', 'tab' => $active_tab, 'date_preset' => $key, 'paged' => 1, 's' => $search), admin_url('admin.php'))); ?>"
               class="df-date-pill <?php echo ($date_preset === $key) ? 'active' : ''; ?>">
                <?php echo esc_html($label); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Executive KPI Summary Cards -->
    <div class="df-kpi-grid">
        <div class="df-kpi-card">
            <div class="df-kpi-content">
                <h4>Total Sessions</h4>
                <div class="df-kpi-value"><?php echo esc_html(number_format_i18n($kpis['total_sessions'])); ?></div>
                <div class="df-kpi-sub"><?php echo esc_html($kpis['active_sessions']); ?> currently on road</div>
            </div>
            <div class="df-kpi-icon teal">🚗</div>
        </div>

        <div class="df-kpi-card">
            <div class="df-kpi-content">
                <h4>Hours Behind Wheel</h4>
                <div class="df-kpi-value"><?php echo esc_html(number_format_i18n($kpis['hours_driven'])); ?> <small style="font-size:14px;">hrs</small></div>
                <div class="df-kpi-sub"><?php echo esc_html($kpis['completed_sessions']); ?> completed lessons</div>
            </div>
            <div class="df-kpi-icon blue">⏱️</div>
        </div>

        <div class="df-kpi-card">
            <div class="df-kpi-content">
                <h4>Completion Rate</h4>
                <div class="df-kpi-value"><?php echo esc_html($kpis['completion_rate']); ?>%</div>
                <div class="df-kpi-sub"><?php echo esc_html($kpis['cancelled_sessions']); ?> cancellations</div>
            </div>
            <div class="df-kpi-icon green">📈</div>
        </div>

        <div class="df-kpi-card">
            <div class="df-kpi-content">
                <h4>Student Pass Rate</h4>
                <div class="df-kpi-value"><?php echo esc_html($kpis['pass_rate']); ?>%</div>
                <div class="df-kpi-sub"><?php echo esc_html($kpis['passed_count']); ?> passed / <?php echo esc_html($kpis['evaluated_count']); ?> evaluated</div>
            </div>
            <div class="df-kpi-icon purple">🏆</div>
        </div>

        <div class="df-kpi-card">
            <div class="df-kpi-content">
                <h4>Active Students</h4>
                <div class="df-kpi-value"><?php echo esc_html(number_format_i18n($kpis['unique_students'])); ?></div>
                <div class="df-kpi-sub">Served during period</div>
            </div>
            <div class="df-kpi-icon amber">🎓</div>
        </div>

        <div class="df-kpi-card">
            <div class="df-kpi-content">
                <h4>Instructors & Fleet</h4>
                <div class="df-kpi-value"><?php echo esc_html($kpis['unique_instructors']); ?> / <?php echo esc_html($kpis['unique_vehicles']); ?></div>
                <div class="df-kpi-sub">Instructors / Cars active</div>
            </div>
            <div class="df-kpi-icon teal">🛡️</div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <nav class="df-report-nav">
        <?php foreach ($tab_names as $tab_key => $info) : ?>
            <a href="<?php echo esc_url(add_query_arg(array('page' => 'driveflow-pro-reports', 'tab' => $tab_key, 'date_preset' => $date_preset, 'start_date' => $start_date, 'end_date' => $end_date, 'paged' => 1, 's' => $search), admin_url('admin.php'))); ?>"
               class="df-report-tab <?php echo ($active_tab === $tab_key) ? 'active' : ''; ?>">
                <span class="dashicons <?php echo esc_attr($info['icon']); ?>"></span>
                <?php echo esc_html($info['label']); ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- Filter & Search Toolbar -->
    <div class="df-toolbar">
        <form method="get" class="df-filters-form">
            <input type="hidden" name="page" value="driveflow-pro-reports">
            <input type="hidden" name="tab" value="<?php echo esc_attr($active_tab); ?>">
            <input type="hidden" name="date_preset" value="custom">

            <div style="display:flex;align-items:center;gap:6px;">
                <label style="font-size:12px;font-weight:700;color:#64748b;">From:</label>
                <input type="date" name="start_date" value="<?php echo esc_attr($start_date); ?>" style="padding:4px 8px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;">
            </div>

            <div style="display:flex;align-items:center;gap:6px;">
                <label style="font-size:12px;font-weight:700;color:#64748b;">To:</label>
                <input type="date" name="end_date" value="<?php echo esc_attr($end_date); ?>" style="padding:4px 8px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;">
            </div>

            <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search records, names, or plate...">

            <button type="submit" class="df-btn df-btn-secondary">
                <span class="dashicons dashicons-filter"></span> Apply Filter
            </button>

            <?php if ($search || 'custom' === $date_preset) : ?>
                <a href="<?php echo esc_url(add_query_arg(array('page' => 'driveflow-pro-reports', 'tab' => $active_tab, 'date_preset' => 'this_month'), admin_url('admin.php'))); ?>" class="df-btn df-btn-secondary df-btn-sm">
                    Reset Filter
                </a>
            <?php endif; ?>
        </form>

        <div class="df-per-page-select">
            <span>Show</span>
            <select onchange="location.href=this.value;">
                <?php foreach (array(10, 15, 25, 50, 100) as $pp) : ?>
                    <option value="<?php echo esc_url(add_query_arg(array('per_page' => $pp, 'paged' => 1))); ?>" <?php selected($per_page, $pp); ?>>
                        <?php echo esc_html($pp); ?> rows
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- Tab 1: Executive Overview -->
    <?php if ('overview' === $active_tab) : ?>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">
            <!-- Session Status Breakdown -->
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px;box-shadow:0 2px 5px rgba(0,0,0,0.03);">
                <h3 style="margin:0 0 16px 0;font-size:15px;font-weight:700;color:#1e293b;display:flex;align-items:center;gap:8px;">
                    <span class="dashicons dashicons-chart-pie" style="color:#0f766e;"></span> Session Status Breakdown
                </h3>
                <table class="df-table" style="font-size:13px;">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Count</th>
                            <th>Share %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $tot = max(1, $kpis['total_sessions']);
                        $status_rows = array(
                            array('Completed Lessons', $kpis['completed_sessions'], '#15803d', '#dcfce7'),
                            array('Upcoming Scheduled', $kpis['upcoming_sessions'], '#1d4ed8', '#dbeafe'),
                            array('Active (On Road)', $kpis['active_sessions'], '#b45309', '#fef3c7'),
                            array('Cancelled', $kpis['cancelled_sessions'], '#b91c1c', '#fee2e2'),
                        );
                        foreach ($status_rows as $sr) :
                            $pct = round(($sr[1] / $tot) * 100, 1);
                        ?>
                        <tr>
                            <td>
                                <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:<?php echo esc_attr($sr[2]); ?>;margin-right:8px;"></span>
                                <strong><?php echo esc_html($sr[0]); ?></strong>
                            </td>
                            <td><?php echo esc_html(number_format_i18n($sr[1])); ?></td>
                            <td>
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <div style="flex:1;background:#e2e8f0;height:6px;border-radius:3px;overflow:hidden;">
                                        <div style="width:<?php echo esc_attr($pct); ?>%;background:<?php echo esc_attr($sr[2]); ?>;height:100%;"></div>
                                    </div>
                                    <span style="font-size:11px;font-weight:600;color:#64748b;"><?php echo esc_html($pct); ?>%</span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Top Performing Instructors -->
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px;box-shadow:0 2px 5px rgba(0,0,0,0.03);">
                <h3 style="margin:0 0 16px 0;font-size:15px;font-weight:700;color:#1e293b;display:flex;align-items:center;gap:8px;">
                    <span class="dashicons dashicons-awards" style="color:#f59e0b;"></span> Top Instructors by Sessions Conducted
                </h3>
                <?php if (empty($overview_data['top_instructors'])) : ?>
                    <p style="color:#94a3b8;font-size:13px;text-align:center;padding:20px;">No instructor activity in this timeframe.</p>
                <?php else : ?>
                    <table class="df-table" style="font-size:13px;">
                        <thead>
                            <tr>
                                <th>Instructor</th>
                                <th>Sessions</th>
                                <th>Hours Taught</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($overview_data['top_instructors'] as $ins) : ?>
                            <tr>
                                <td><strong><?php echo esc_html($ins['instructor_name'] ?: 'Unassigned'); ?></strong></td>
                                <td><?php echo esc_html($ins['total_count']); ?> sessions</td>
                                <td><span style="color:#0f766e;font-weight:700;"><?php echo esc_html($ins['total_count'] * 2); ?> hrs</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Completed Driving Sessions Showcase -->
        <div class="df-table-container">
            <div style="padding:16px 20px;border-bottom:1px solid #e2e8f0;background:#f8fafc;display:flex;justify-content:space-between;align-items:center;">
                <h3 style="margin:0;font-size:14px;font-weight:700;color:#334155;">Recent Completed Lessons & Evaluations</h3>
                <a href="<?php echo esc_url(add_query_arg(array('page' => 'driveflow-pro-records', 'status' => 'completed'), admin_url('admin.php'))); ?>" class="df-btn df-btn-secondary df-btn-sm">
                    View In Records Log &rarr;
                </a>
            </div>
            <table class="df-table">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Student Name</th>
                        <th>Instructor</th>
                        <th>Vehicle Plate (Maryland)</th>
                        <th>Lesson</th>
                        <th>Evaluation Result</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($overview_data['recent_completed'])) : ?>
                        <tr><td colspan="6" style="text-align:center;padding:30px;color:#94a3b8;">No completed lessons recorded in this timeframe.</td></tr>
                    <?php else : ?>
                        <?php foreach ($overview_data['recent_completed'] as $rc) : 
                            $eval = 'Completed';
                            if (!empty($rc['form_data'])) {
                                $fdata = json_decode($rc['form_data'], true);
                                if (!empty($fdata['final_evaluation'])) $eval = $fdata['final_evaluation'];
                            }
                        ?>
                        <tr>
                            <td><?php echo esc_html(date_i18n('M j, Y - g:i A', strtotime($rc['scheduled_start']))); ?></td>
                            <td><strong><?php echo esc_html($rc['student_name']); ?></strong></td>
                            <td><?php echo esc_html($rc['instructor_name'] ?: 'Unassigned'); ?></td>
                            <td><?php echo DriveFlow_Pro::render_maryland_plate($rc['plate_number'], 'sm'); ?></td>
                            <td>Lesson <?php echo esc_html($rc['session_number']); ?> (<?php echo esc_html($rc['lesson_topic'] ?: 'Driving Lesson'); ?>)</td>
                            <td>
                                <?php if ('Pass' === $eval) : ?>
                                    <span class="df-badge" style="background:#dcfce7;color:#15803d;font-weight:700;">✓ PASS</span>
                                <?php elseif ('Fail' === $eval) : ?>
                                    <span class="df-badge" style="background:#fee2e2;color:#b91c1c;font-weight:700;">✕ FAIL</span>
                                <?php else : ?>
                                    <span class="df-badge df-badge-completed"><?php echo esc_html($eval); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    <!-- Tab: Instructor Payroll & Archive -->
    <?php elseif ('payroll' === $active_tab) : 
        $payroll_summary = $overview_data['payroll_summary'] ?? array(
            'total_hours'     => 0,
            'total_earned'    => 0,
            'total_paid'      => 0,
            'total_balance'   => 0,
            'payouts_archive' => array(),
        );
        $payouts_archive = $payroll_summary['payouts_archive'] ?? array();
    ?>
        <!-- Payroll KPI Summary Cards -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:16px;margin-bottom:24px;">
            <div class="df-kpi-card" style="border-left:4px solid #0284c7;">
                <div class="df-kpi-content">
                    <h4 style="font-size:12px;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;margin:0 0 6px 0;">Total Hours Taught (Period)</h4>
                    <div class="df-kpi-value" style="font-size:26px;font-weight:800;color:#0f172a;"><?php echo esc_html(number_format($payroll_summary['total_hours'], 1)); ?> <span style="font-size:14px;font-weight:600;color:#64748b;">hrs</span></div>
                    <div class="df-kpi-sub" style="font-size:12px;color:#0284c7;">Behind-the-wheel instruction</div>
                </div>
                <div class="df-kpi-icon" style="background:#e0f2fe;font-size:20px;">⏱️</div>
            </div>

            <div class="df-kpi-card" style="border-left:4px solid #7c3aed;">
                <div class="df-kpi-content">
                    <h4 style="font-size:12px;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;margin:0 0 6px 0;">Gross Payroll Incurred</h4>
                    <div class="df-kpi-value" style="font-size:26px;font-weight:800;color:#0f172a;">$<?php echo esc_html(number_format($payroll_summary['total_earned'], 2)); ?></div>
                    <div class="df-kpi-sub" style="font-size:12px;color:#7c3aed;">Based on custom contractual rates</div>
                </div>
                <div class="df-kpi-icon" style="background:#ede9fe;font-size:20px;">💼</div>
            </div>

            <div class="df-kpi-card" style="border-left:4px solid #16a34a;">
                <div class="df-kpi-content">
                    <h4 style="font-size:12px;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;margin:0 0 6px 0;">Total Paid Out (All-Time)</h4>
                    <div class="df-kpi-value" style="font-size:26px;font-weight:800;color:#16a34a;">$<?php echo esc_html(number_format($payroll_summary['total_paid'], 2)); ?></div>
                    <div class="df-kpi-sub" style="font-size:12px;color:#16a34a;">Disbursed via Zelle / Check / ACH</div>
                </div>
                <div class="df-kpi-icon" style="background:#dcfce7;font-size:20px;">💵</div>
            </div>

            <div class="df-kpi-card" style="border-left:4px solid <?php echo ($payroll_summary['total_balance'] > 0) ? '#ea580c' : '#16a34a'; ?>;">
                <div class="df-kpi-content">
                    <h4 style="font-size:12px;text-transform:uppercase;letter-spacing:0.5px;color:#64748b;margin:0 0 6px 0;">Outstanding Balance Due</h4>
                    <div class="df-kpi-value" style="font-size:26px;font-weight:800;color:<?php echo ($payroll_summary['total_balance'] > 0) ? '#ea580c' : '#16a34a'; ?>;">$<?php echo esc_html(number_format($payroll_summary['total_balance'], 2)); ?></div>
                    <div class="df-kpi-sub" style="font-size:12px;color:<?php echo ($payroll_summary['total_balance'] > 0) ? '#ea580c' : '#16a34a'; ?>;">
                        <?php echo ($payroll_summary['total_balance'] > 0) ? 'Pending administrative remittance' : 'All payroll cleared & settled ✓'; ?>
                    </div>
                </div>
                <div class="df-kpi-icon" style="background:<?php echo ($payroll_summary['total_balance'] > 0) ? '#ffedd5' : '#dcfce7'; ?>;font-size:20px;">⚖️</div>
            </div>
        </div>

        <!-- Section 1: Instructor Payroll Breakdown Table -->
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;margin-bottom:28px;overflow:hidden;box-shadow:0 2px 5px rgba(0,0,0,0.03);">
            <div style="padding:16px 20px;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;background:#f8fafc;">
                <div>
                    <h3 style="margin:0;font-size:16px;font-weight:700;color:#0f172a;display:flex;align-items:center;gap:8px;">
                        <span class="dashicons dashicons-businessperson" style="color:#0284c7;"></span> Active Instructor Payroll & Balances
                    </h3>
                    <div style="font-size:12px;color:#64748b;margin-top:2px;">Calculated dynamically from completed driving sessions and recorded disbursements</div>
                </div>
                <a href="<?php echo esc_url(admin_url('admin.php?page=driveflow-pro-instructors')); ?>" class="df-btn df-btn-secondary df-btn-sm">
                    Manage Instructors Directory &rarr;
                </a>
            </div>

            <div class="df-table-container" style="border:none;margin:0;">
                <table class="df-table">
                    <thead>
                        <tr>
                            <th>Instructor</th>
                            <th>Payout Preference</th>
                            <th>Contract Rate</th>
                            <th>Period Lessons / Hours</th>
                            <th>Period Gross</th>
                            <th>All-Time Earned</th>
                            <th>Total Paid</th>
                            <th>Balance Due</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rows)) : ?>
                            <tr><td colspan="9" style="text-align:center;padding:40px;color:#94a3b8;">No instructor records found matching filters.</td></tr>
                        <?php else : ?>
                            <?php foreach ($rows as $row) : 
                                $method = $row['payment_method'] ?: 'zelle';
                                $method_labels = array(
                                    'zelle' => array('label' => '⚡ Zelle', 'bg' => '#f3e8ff', 'color' => '#7e22ce', 'border' => '#e9d5ff'),
                                    'check' => array('label' => '✉️ Check', 'bg' => '#eff6ff', 'color' => '#1d4ed8', 'border' => '#dbeafe'),
                                    'direct_deposit' => array('label' => '🏦 ACH/Bank', 'bg' => '#ecfdf5', 'color' => '#047857', 'border' => '#a7f3d0'),
                                    'cash' => array('label' => '💵 Cash', 'bg' => '#fefce8', 'color' => '#a16207', 'border' => '#fef08a'),
                                );
                                $m_info = $method_labels[$method] ?? array('label' => strtoupper($method), 'bg' => '#f1f5f9', 'color' => '#475569', 'border' => '#cbd5e1');
                                $bal = floatval($row['balance_due']);
                                $photo = !empty($row['photo_url']) ? $row['photo_url'] : 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="%2394a3b8"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>';
                            ?>
                            <tr>
                                <td>
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        <img src="<?php echo esc_url($photo); ?>" alt="Avatar" style="width:34px;height:34px;border-radius:50%;object-fit:cover;border:2px solid #0284c7;background:#f8fafc;">
                                        <div>
                                            <strong style="font-size:13px;color:#0f172a;display:block;"><?php echo esc_html($row['name']); ?></strong>
                                            <span style="font-size:11px;color:#64748b;"><?php echo esc_html($row['license_number'] ?: 'Lic # Pending'); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="df-badge" style="background:<?php echo esc_attr($m_info['bg']); ?>;color:<?php echo esc_attr($m_info['color']); ?>;border:1px solid <?php echo esc_attr($m_info['border']); ?>;font-size:11px;font-weight:700;">
                                        <?php echo esc_html($m_info['label']); ?>
                                    </span>
                                    <?php if (!empty($row['payment_details'])) : ?>
                                        <div style="font-size:11px;color:#64748b;margin-top:3px;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo esc_attr($row['payment_details']); ?>">
                                            <?php echo esc_html($row['payment_details']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="df-badge" style="background:#ecfdf5;color:#047857;border:1px solid #a7f3d0;font-size:12px;font-weight:800;">
                                        $<?php echo number_format(floatval($row['hourly_rate']), 2); ?>/hr
                                    </span>
                                </td>
                                <td>
                                    <div style="font-size:13px;font-weight:700;color:#0f172a;"><?php echo esc_html($row['hours_taught']); ?> hrs</div>
                                    <div style="font-size:11px;color:#64748b;"><?php echo esc_html($row['comp_sessions']); ?> sessions</div>
                                </td>
                                <td>
                                    <strong style="font-size:13px;color:#0f172a;">$<?php echo number_format(floatval($row['gross_earned']), 2); ?></strong>
                                </td>
                                <td>
                                    <div style="font-size:13px;font-weight:600;color:#334155;">$<?php echo number_format(floatval($row['earned_all']), 2); ?></div>
                                    <div style="font-size:11px;color:#64748b;"><?php echo esc_html($row['hours_all']); ?> total hrs</div>
                                </td>
                                <td>
                                    <div style="font-size:13px;font-weight:600;color:#16a34a;">$<?php echo number_format(floatval($row['total_paid']), 2); ?></div>
                                </td>
                                <td>
                                    <?php if ($bal > 0) : ?>
                                        <div style="display:inline-block;padding:3px 8px;border-radius:6px;background:#fff7ed;border:1px solid #fdba74;color:#c2410c;font-weight:800;font-size:13px;">
                                            $<?php echo number_format($bal, 2); ?>
                                        </div>
                                    <?php else : ?>
                                        <span class="df-badge" style="background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;font-weight:700;font-size:11px;">
                                            ✓ Settled ($0)
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:right;white-space:nowrap;">
                                    <div style="display:flex;gap:6px;justify-content:flex-end;">
                                        <button type="button" 
                                                class="button button-small df-open-record-payout-btn"
                                                data-instructor-id="<?php echo esc_attr($row['id']); ?>"
                                                data-instructor-name="<?php echo esc_attr($row['name']); ?>"
                                                data-hourly-wage="<?php echo esc_attr($row['hourly_rate']); ?>"
                                                data-balance-due="<?php echo esc_attr($bal); ?>"
                                                data-gross-earned="<?php echo esc_attr($row['earned_all']); ?>"
                                                data-total-paid="<?php echo esc_attr($row['total_paid']); ?>"
                                                data-hours-unpaid="<?php echo esc_attr($row['hourly_rate'] > 0 ? round($bal / $row['hourly_rate'], 1) : 0); ?>"
                                                data-payment-method="<?php echo esc_attr($method); ?>"
                                                data-payment-details="<?php echo esc_attr($row['payment_details']); ?>"
                                                style="background:#0284c7;color:#fff;border-color:#0284c7;font-weight:700;">
                                            💵 Pay
                                        </button>
                                        <button type="button" 
                                                class="button button-small df-open-payout-history-btn"
                                                data-instructor-id="<?php echo esc_attr($row['id']); ?>"
                                                data-instructor-name="<?php echo esc_attr($row['name']); ?>"
                                                title="View Historical Disbursements & Receipts">
                                            📜 History
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 2: Chronological Payout Archive & Remittance Log -->
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;margin-bottom:24px;overflow:hidden;box-shadow:0 2px 5px rgba(0,0,0,0.03);">
            <div style="padding:16px 20px;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;background:#f8fafc;">
                <div>
                    <h3 style="margin:0;font-size:16px;font-weight:700;color:#0f172a;display:flex;align-items:center;gap:8px;">
                        <span class="dashicons dashicons-archive" style="color:#7c3aed;"></span> Disbursement Archive & Payment Receipts Log
                    </h3>
                    <div style="font-size:12px;color:#64748b;margin-top:2px;">Auditable history of all compensation payments made to driving instructors</div>
                </div>
            </div>

            <div class="df-table-container" style="border:none;margin:0;">
                <table class="df-table">
                    <thead>
                        <tr>
                            <th>Voucher #</th>
                            <th>Payment Date</th>
                            <th>Instructor</th>
                            <th>Method & Reference</th>
                            <th>Pay Period</th>
                            <th>Hours Paid</th>
                            <th>Rate</th>
                            <th>Amount Disbursed</th>
                            <th>Status</th>
                            <th style="text-align:right;">Receipt / Voucher</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($payouts_archive)) : ?>
                            <tr><td colspan="10" style="text-align:center;padding:40px;color:#94a3b8;">No archived payout records found.</td></tr>
                        <?php else : ?>
                            <?php foreach ($payouts_archive as $po) : 
                                $m_name = strtoupper($po['payment_method']);
                            ?>
                            <tr id="df-archive-row-<?php echo esc_attr($po['id']); ?>">
                                <td><strong style="color:#0284c7;">#PAY-<?php echo esc_html(str_pad($po['id'], 5, '0', STR_PAD_LEFT)); ?></strong></td>
                                <td><?php echo esc_html(date_i18n('M j, Y', strtotime($po['payment_date']))); ?></td>
                                <td><strong style="color:#0f172a;"><?php echo esc_html($po['instructor_name']); ?></strong></td>
                                <td>
                                    <span class="df-badge" style="background:#f1f5f9;color:#334155;border:1px solid #cbd5e1;font-weight:700;font-size:11px;">
                                        <?php echo esc_html($m_name); ?>
                                    </span>
                                    <?php if (!empty($po['reference_number'])) : ?>
                                        <div style="font-size:11px;color:#475569;margin-top:2px;font-family:monospace;">
                                            Ref: <?php echo esc_html($po['reference_number']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($po['period_start']) && !empty($po['period_end'])) : ?>
                                        <span style="font-size:12px;color:#334155;">
                                            <?php echo esc_html(date_i18n('M j', strtotime($po['period_start']))); ?> &ndash; <?php echo esc_html(date_i18n('M j, Y', strtotime($po['period_end']))); ?>
                                        </span>
                                    <?php else : ?>
                                        <span style="color:#94a3b8;font-size:12px;">Standard Cycle</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo esc_html($po['hours_paid']); ?></strong> hrs</td>
                                <td>$<?php echo number_format(floatval($po['hourly_rate']), 2); ?>/hr</td>
                                <td><strong style="font-size:14px;color:#16a34a;">$<?php echo number_format(floatval($po['amount']), 2); ?></strong></td>
                                <td><span class="df-badge df-badge-completed">PAID ✓</span></td>
                                <td style="text-align:right;">
                                    <button type="button" 
                                            class="button button-small df-print-single-voucher-btn"
                                            data-payout-id="<?php echo esc_attr($po['id']); ?>"
                                            data-payout-json="<?php echo esc_attr(wp_json_encode($po)); ?>"
                                            style="color:#0284c7;border-color:#bae6fd;background:#f0f9ff;">
                                        🖨️ Voucher Slip
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php 
        include DRIVEFLOW_PRO_DIR . 'templates/admin-payout-modals-partial.php';
        ?>

    <!-- Tab 2: Daily Breakdown -->
    <?php elseif ('daily' === $active_tab) : ?>
        <div class="df-table-container">
            <table class="df-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Total Lessons</th>
                        <th>Completed</th>
                        <th>Cancelled</th>
                        <th>Hours Behind Wheel</th>
                        <th>Students Served</th>
                        <th>Instructors Active</th>
                        <th>Completion %</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)) : ?>
                        <tr><td colspan="8" style="text-align:center;padding:40px;color:#94a3b8;">No daily records found for the selected timeframe.</td></tr>
                    <?php else : ?>
                        <?php foreach ($rows as $r) : 
                            $comp = (int)$r['completed_count'];
                            $tot = max(1, (int)$r['total_count']);
                            $rate = round(($comp / $tot) * 100);
                            $hrs = $comp * 2;
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html(date_i18n('l, F j, Y', strtotime($r['session_date']))); ?></strong></td>
                            <td><span class="df-badge df-badge-upcoming"><?php echo esc_html($r['total_count']); ?></span></td>
                            <td><span style="color:#15803d;font-weight:700;"><?php echo esc_html($r['completed_count']); ?></span></td>
                            <td><span style="color:#b91c1c;font-weight:600;"><?php echo esc_html($r['cancelled_count']); ?></span></td>
                            <td><strong><?php echo esc_html($hrs); ?> hrs</strong></td>
                            <td><?php echo esc_html($r['students_count']); ?> students</td>
                            <td><?php echo esc_html($r['instructors_count']); ?> instructors</td>
                            <td>
                                <div style="display:flex;align-items:center;gap:6px;">
                                    <div style="flex:1;max-width:80px;background:#e2e8f0;height:6px;border-radius:3px;overflow:hidden;">
                                        <div style="width:<?php echo esc_attr($rate); ?>%;background:#0f766e;height:100%;"></div>
                                    </div>
                                    <span style="font-size:12px;font-weight:700;color:#0f766e;"><?php echo esc_html($rate); ?>%</span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    <!-- Tab 3: Monthly Breakdown -->
    <?php elseif ('monthly' === $active_tab) : ?>
        <div class="df-table-container">
            <table class="df-table">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Total Lessons</th>
                        <th>Completed</th>
                        <th>Cancelled</th>
                        <th>Hours Behind Wheel</th>
                        <th>Unique Students</th>
                        <th>Unique Instructors</th>
                        <th>Completion %</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)) : ?>
                        <tr><td colspan="8" style="text-align:center;padding:40px;color:#94a3b8;">No monthly records found for the selected timeframe.</td></tr>
                    <?php else : ?>
                        <?php foreach ($rows as $r) : 
                            $comp = (int)$r['completed_count'];
                            $tot = max(1, (int)$r['total_count']);
                            $rate = round(($comp / $tot) * 100);
                            $hrs = $comp * 2;
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html(date_i18n('F Y', strtotime($r['session_month'] . '-01'))); ?></strong></td>
                            <td><span class="df-badge df-badge-upcoming"><?php echo esc_html($r['total_count']); ?></span></td>
                            <td><span style="color:#15803d;font-weight:700;"><?php echo esc_html($r['completed_count']); ?></span></td>
                            <td><span style="color:#b91c1c;font-weight:600;"><?php echo esc_html($r['cancelled_count']); ?></span></td>
                            <td><strong><?php echo esc_html(number_format_i18n($hrs)); ?> hrs</strong></td>
                            <td><?php echo esc_html($r['students_count']); ?> learners</td>
                            <td><?php echo esc_html($r['instructors_count']); ?> instructors</td>
                            <td>
                                <div style="display:flex;align-items:center;gap:6px;">
                                    <div style="flex:1;max-width:80px;background:#e2e8f0;height:6px;border-radius:3px;overflow:hidden;">
                                        <div style="width:<?php echo esc_attr($rate); ?>%;background:#0f766e;height:100%;"></div>
                                    </div>
                                    <span style="font-size:12px;font-weight:700;color:#0f766e;"><?php echo esc_html($rate); ?>%</span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    <!-- Tab 4: Instructor Performance Report -->
    <?php elseif ('instructors' === $active_tab) : ?>
        <div class="df-table-container">
            <table class="df-table">
                <thead>
                    <tr>
                        <th>Instructor Name</th>
                        <th>State / MVA License #</th>
                        <th>Total Lessons</th>
                        <th>Completed</th>
                        <th>Hours Taught</th>
                        <th>Pass Rate %</th>
                        <th>Completion Rate %</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)) : ?>
                        <tr><td colspan="7" style="text-align:center;padding:40px;color:#94a3b8;">No instructor session data found.</td></tr>
                    <?php else : ?>
                        <?php foreach ($rows as $r) : 
                            $comp = (int)$r['completed_count'];
                            $tot = max(1, (int)$r['total_count']);
                            $comp_rate = round(($comp / $tot) * 100);
                            $hrs = $comp * 2;
                            $pass_rate = (!empty($r['evaluated_count']) && $r['evaluated_count'] > 0)
                                ? round(((int)$r['passed_count'] / (int)$r['evaluated_count']) * 100)
                                : '—';
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html($r['instructor_name'] ?: 'Unassigned'); ?></strong></td>
                            <td><span style="font-family:monospace;color:#475569;font-weight:600;"><?php echo esc_html(!empty($r['license_number']) ? $r['license_number'] : 'MVA-MD'.strtoupper(substr(md5($r['instructor_name']), 0, 5))); ?></span></td>
                            <td><span class="df-badge df-badge-upcoming"><?php echo esc_html($r['total_count']); ?></span></td>
                            <td><span style="color:#15803d;font-weight:700;"><?php echo esc_html($r['completed_count']); ?></span></td>
                            <td><strong><?php echo esc_html($hrs); ?> hrs</strong></td>
                            <td>
                                <?php if ('—' !== $pass_rate) : ?>
                                    <span style="color:#15803d;font-weight:700;"><?php echo esc_html($pass_rate); ?>%</span>
                                    <small style="color:#64748b;">(<?php echo esc_html($r['passed_count']); ?>/<?php echo esc_html($r['evaluated_count']); ?>)</small>
                                <?php else : ?>
                                    <span style="color:#94a3b8;">No evaluations</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display:flex;align-items:center;gap:6px;">
                                    <div style="flex:1;max-width:70px;background:#e2e8f0;height:6px;border-radius:3px;overflow:hidden;">
                                        <div style="width:<?php echo esc_attr($comp_rate); ?>%;background:#0f766e;height:100%;"></div>
                                    </div>
                                    <span style="font-size:12px;font-weight:700;color:#0f766e;"><?php echo esc_html($comp_rate); ?>%</span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    <!-- Tab 5: Student Progress Report -->
    <?php elseif ('students' === $active_tab) : ?>
        <div class="df-table-container">
            <table class="df-table">
                <thead>
                    <tr>
                        <th>Student Legal Name</th>
                        <th>Contact / Phone</th>
                        <th>Course Package</th>
                        <th>Sessions Completed</th>
                        <th>Credits Remaining</th>
                        <th>Last Lesson Date</th>
                        <th>Progress %</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)) : ?>
                        <tr><td colspan="7" style="text-align:center;padding:40px;color:#94a3b8;">No student progress records found.</td></tr>
                    <?php else : ?>
                        <?php foreach ($rows as $st) : 
                            $tot = max(1, (int)$st['total_sessions']);
                            $comp = (int)$st['completed_sessions'];
                            $rem = max(0, $tot - $comp);
                            $pct = min(100, round(($comp / $tot) * 100));
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($st['name']); ?></strong>
                                <?php if (!empty($st['license_number'])) : ?>
                                    <div style="font-size:11px;color:#64748b;">Permit: <?php echo esc_html($st['license_number']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div><?php echo esc_html($st['phone'] ?: '—'); ?></div>
                                <small style="color:#64748b;"><?php echo esc_html($st['email'] ?: ''); ?></small>
                            </td>
                            <td><span class="df-badge" style="background:#f1f5f9;color:#334155;border:1px solid #cbd5e1;"><?php echo esc_html($st['package_name'] ?: 'Standard Package'); ?></span></td>
                            <td><span style="color:#15803d;font-weight:700;"><?php echo esc_html($comp); ?> / <?php echo esc_html($tot); ?></span></td>
                            <td>
                                <?php if ($rem > 0) : ?>
                                    <span class="df-badge df-badge-active" style="background:#dbeafe;color:#1d4ed8;font-weight:700;"><?php echo esc_html($rem); ?> remaining</span>
                                <?php else : ?>
                                    <span class="df-badge df-badge-completed" style="background:#dcfce7;color:#15803d;font-weight:700;">Package Complete</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html(!empty($st['last_session_date']) ? date_i18n('M j, Y', strtotime($st['last_session_date'])) : '—'); ?></td>
                            <td>
                                <div style="display:flex;align-items:center;gap:6px;">
                                    <div style="flex:1;max-width:70px;background:#e2e8f0;height:6px;border-radius:3px;overflow:hidden;">
                                        <div style="width:<?php echo esc_attr($pct); ?>%;background:<?php echo ($rem > 0) ? '#2563eb' : '#16a34a'; ?>;height:100%;"></div>
                                    </div>
                                    <span style="font-size:12px;font-weight:700;color:#334155;"><?php echo esc_html($pct); ?>%</span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    <!-- Tab 6: Fleet Vehicle Utilization -->
    <?php elseif ('vehicles' === $active_tab) : ?>
        <div class="df-table-container">
            <table class="df-table">
                <thead>
                    <tr>
                        <th>Maryland License Plate</th>
                        <th>Vehicle Model</th>
                        <th>Color</th>
                        <th>Lessons Conducted</th>
                        <th>Hours Behind Wheel</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)) : ?>
                        <tr><td colspan="6" style="text-align:center;padding:40px;color:#94a3b8;">No fleet vehicle records found.</td></tr>
                    <?php else : ?>
                        <?php foreach ($rows as $v) : 
                            $hrs = (int)$v['total_sessions'] * 2;
                        ?>
                        <tr>
                            <td>
                                <?php echo DriveFlow_Pro::render_maryland_plate($v['plate_number'], 'default'); ?>
                            </td>
                            <td><strong><?php echo esc_html($v['model'] ?: 'Dual-Control Training Car'); ?></strong></td>
                            <td><?php echo esc_html($v['color'] ?: 'White'); ?></td>
                            <td><span class="df-badge df-badge-upcoming"><?php echo esc_html($v['total_sessions']); ?> lessons</span></td>
                            <td><strong><?php echo esc_html($hrs); ?> hrs</strong></td>
                            <td><span class="df-badge df-badge-active">ACTIVE IN FLEET</span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Unified Pagination Bar -->
    <?php if ($total_pages > 1) : 
        $start_item = ($paged - 1) * $per_page + 1;
        $end_item = min($total_rows, $paged * $per_page);
    ?>
    <div class="df-pagination-bar">
        <div class="df-pagination-info">
            Showing <strong><?php echo esc_html($start_item); ?></strong> to <strong><?php echo esc_html($end_item); ?></strong> of <strong><?php echo esc_html($total_rows); ?></strong> records
        </div>

        <div class="df-pagination-controls">
            <!-- Previous Button -->
            <a href="<?php echo ($paged > 1) ? esc_url($build_page_url($paged - 1)) : '#'; ?>"
               class="df-page-btn <?php echo ($paged <= 1) ? 'disabled' : ''; ?>">
                &larr; Prev
            </a>

            <!-- Page Number Links -->
            <?php
            $range_start = max(1, $paged - 2);
            $range_end = min($total_pages, $paged + 2);
            if ($range_start > 1) {
                echo '<a href="' . esc_url($build_page_url(1)) . '" class="df-page-btn">1</a>';
                if ($range_start > 2) echo '<span style="color:#94a3b8;padding:0 4px;">...</span>';
            }
            for ($i = $range_start; $i <= $range_end; $i++) {
                $is_curr = ($i === $paged);
                echo '<a href="' . esc_url($build_page_url($i)) . '" class="df-page-btn ' . ($is_curr ? 'active' : '') . '">' . esc_html($i) . '</a>';
            }
            if ($range_end < $total_pages) {
                if ($range_end < $total_pages - 1) echo '<span style="color:#94a3b8;padding:0 4px;">...</span>';
                echo '<a href="' . esc_url($build_page_url($total_pages)) . '" class="df-page-btn">' . esc_html($total_pages) . '</a>';
            }
            ?>

            <!-- Next Button -->
            <a href="<?php echo ($paged < $total_pages) ? esc_url($build_page_url($paged + 1)) : '#'; ?>"
               class="df-page-btn <?php echo ($paged >= $total_pages) ? 'disabled' : ''; ?>">
                Next &rarr;
            </a>
        </div>
    </div>
    <?php endif; ?>
</div>
