<?php
defined('ABSPATH') || exit;

/**
 * DriveFlow Pro - Official Printable Report Document
 * Pure English, High-Contrast Official Letterhead for Printing & PDF Generation
 */
$settings = get_option(DriveFlow_Pro::OPTION_KEY, array());
$school_name = !empty($settings['school_name']) ? $settings['school_name'] : 'DriveFlow Driving Academy';
$logo_url = !empty($settings['logo_url']) ? $settings['logo_url'] : '';
$tab_title = ucfirst($active_tab) . ' Report';
if ('instructors' === $active_tab) $tab_title = 'Instructor Performance Audit';
elseif ('students' === $active_tab) $tab_title = 'Student Course Progress & Credits Log';
elseif ('vehicles' === $active_tab) $tab_title = 'Fleet Vehicle Utilization Log';
elseif ('daily' === $active_tab) $tab_title = 'Daily Driving Session Breakdown';
elseif ('monthly' === $active_tab) $tab_title = 'Monthly Historical Operational Summary';
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title><?php echo esc_html($school_name); ?> - Official <?php echo esc_html($tab_title); ?></title>
    <style>
        @page {
            size: letter portrait;
            margin: 15mm 15mm 20mm 15mm;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #111827;
            background: #ffffff;
            margin: 0;
            padding: 20px 30px;
            font-size: 12px;
            line-height: 1.4;
        }
        .no-print-bar {
            background: #0f172a;
            color: #ffffff;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .print-btn {
            background: #10b981;
            color: #ffffff;
            border: none;
            padding: 8px 18px;
            font-weight: 700;
            font-size: 13px;
            border-radius: 6px;
            cursor: pointer;
        }
        .close-btn {
            background: #475569;
            color: #ffffff;
            border: none;
            padding: 8px 16px;
            font-size: 13px;
            border-radius: 6px;
            cursor: pointer;
            margin-left: 8px;
        }
        /* Letterhead Header */
        .doc-header {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 14px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .doc-brand h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .doc-brand p {
            margin: 4px 0 0 0;
            font-size: 11px;
            color: #475569;
            font-weight: 600;
        }
        .doc-meta {
            text-align: right;
            font-size: 11px;
            color: #334155;
        }
        .doc-meta strong {
            color: #0f172a;
        }
        .doc-title-badge {
            display: inline-block;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 4px 10px;
            border-radius: 4px;
            font-weight: 700;
            font-size: 12px;
            color: #0f172a;
            text-transform: uppercase;
            margin-bottom: 15px;
        }
        /* KPI summary row */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }
        .summary-box {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 10px;
            background: #f8fafc;
            text-align: center;
        }
        .summary-box .title {
            font-size: 10px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .summary-box .num {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
        }
        /* Table Styles */
        table.doc-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            font-size: 11px;
        }
        table.doc-table th, table.doc-table td {
            border: 1px solid #cbd5e1;
            padding: 8px 10px;
            text-align: left;
        }
        table.doc-table th {
            background: #f1f5f9;
            color: #0f172a;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.3px;
        }
        table.doc-table tr:nth-child(even) td {
            background: #fafafa;
        }
        table.doc-table tr {
            page-break-inside: avoid;
        }
        /* Sign-off footer */
        .doc-sign-off {
            page-break-inside: avoid;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #cbd5e1;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 30px;
        }
        .sign-line {
            border-bottom: 1px solid #334155;
            height: 40px;
            margin-bottom: 6px;
        }
        .sign-label {
            font-size: 10px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
        }
        .seal-box {
            border: 1px dashed #94a3b8;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        @media print {
            .no-print-bar {
                display: none !important;
            }
            body {
                padding: 0 !important;
            }
        }
    </style>
</head>
<body>

    <!-- Non-print control bar -->
    <div class="no-print-bar">
        <div>
            <strong>Official Document Ready for Print</strong>
            <span style="font-size:12px;opacity:0.8;margin-left:8px;">(Configured with official MVA academy letterhead & signature seals)</span>
        </div>
        <div>
            <button type="button" class="print-btn" onclick="window.print();">🖨️ Print Document</button>
            <button type="button" class="close-btn" onclick="window.close();">✕ Close Window</button>
        </div>
    </div>

    <!-- Official Header Letterhead -->
    <header class="doc-header">
        <div class="doc-brand">
            <h1><?php echo esc_html($school_name); ?></h1>
            <p>State Certified Commercial Driving School • Driver Education & In-Car Behind-The-Wheel Programs</p>
            <p style="color:#0f766e;margin-top:2px;">State MVA License #: MD-MVA-EDU-78401 • MVA Certified Instructors</p>
        </div>
        <div class="doc-meta">
            <div><strong>Report Document:</strong> <?php echo esc_html($tab_title); ?></div>
            <div><strong>Date Generated:</strong> <?php echo esc_html(date_i18n('F j, Y - g:i A', current_time('timestamp'))); ?></div>
            <div><strong>Operating Window:</strong> <?php echo esc_html($start_date); ?> to <?php echo esc_html($end_date); ?></div>
            <div><strong>Authorized By:</strong> <?php echo esc_html(wp_get_current_user()->display_name); ?> (Administrator)</div>
        </div>
    </header>

    <div class="doc-title-badge">
        Official Record • <?php echo esc_html($tab_title); ?> (Timeframe: <?php echo esc_html(ucwords(str_replace('_', ' ', $date_preset))); ?>)
    </div>

    <!-- Summary Box Row -->
    <div class="summary-grid">
        <div class="summary-box">
            <div class="title">Total Sessions</div>
            <div class="num"><?php echo esc_html($kpis['total_sessions']); ?></div>
        </div>
        <div class="summary-box">
            <div class="title">Completed</div>
            <div class="num" style="color:#15803d;"><?php echo esc_html($kpis['completed_sessions']); ?></div>
        </div>
        <div class="summary-box">
            <div class="title">Hours Driven</div>
            <div class="num"><?php echo esc_html($kpis['hours_driven']); ?> hrs</div>
        </div>
        <div class="summary-box">
            <div class="title">Completion Rate</div>
            <div class="num" style="color:#0f766e;"><?php echo esc_html($kpis['completion_rate']); ?>%</div>
        </div>
        <div class="summary-box">
            <div class="title">Student Pass Rate</div>
            <div class="num" style="color:#7e22ce;"><?php echo esc_html($kpis['pass_rate']); ?>%</div>
        </div>
        <div class="summary-box">
            <div class="title">Active Students</div>
            <div class="num"><?php echo esc_html($kpis['unique_students']); ?></div>
        </div>
    </div>

    <!-- Data Table by Active Tab -->
    <?php if ('daily' === $active_tab) : ?>
        <table class="doc-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Total Lessons</th>
                    <th>Completed</th>
                    <th>Cancelled</th>
                    <th>Hours Behind Wheel</th>
                    <th>Students Served</th>
                    <th>Instructors On Duty</th>
                    <th>Completion %</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)) : ?>
                    <tr><td colspan="8" style="text-align:center;padding:20px;">No records recorded for this timeframe.</td></tr>
                <?php else : ?>
                    <?php foreach ($rows as $r) : 
                        $comp = (int)$r['completed_count'];
                        $tot = max(1, (int)$r['total_count']);
                        $rate = round(($comp / $tot) * 100);
                        $hrs = $comp * 2;
                    ?>
                    <tr>
                        <td><strong><?php echo esc_html(date_i18n('l, F j, Y', strtotime($r['session_date']))); ?></strong></td>
                        <td><?php echo esc_html($r['total_count']); ?></td>
                        <td><?php echo esc_html($r['completed_count']); ?></td>
                        <td><?php echo esc_html($r['cancelled_count']); ?></td>
                        <td><strong><?php echo esc_html($hrs); ?> hrs</strong></td>
                        <td><?php echo esc_html($r['students_count']); ?></td>
                        <td><?php echo esc_html($r['instructors_count']); ?></td>
                        <td><strong><?php echo esc_html($rate); ?>%</strong></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

    <?php elseif ('monthly' === $active_tab) : ?>
        <table class="doc-table">
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Total Lessons</th>
                    <th>Completed</th>
                    <th>Cancelled</th>
                    <th>Hours Behind Wheel</th>
                    <th>Unique Students</th>
                    <th>Unique Instructors</th>
                    <th>Completion Rate %</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)) : ?>
                    <tr><td colspan="8" style="text-align:center;padding:20px;">No records recorded for this timeframe.</td></tr>
                <?php else : ?>
                    <?php foreach ($rows as $r) : 
                        $comp = (int)$r['completed_count'];
                        $tot = max(1, (int)$r['total_count']);
                        $rate = round(($comp / $tot) * 100);
                        $hrs = $comp * 2;
                    ?>
                    <tr>
                        <td><strong><?php echo esc_html(date_i18n('F Y', strtotime($r['session_month'] . '-01'))); ?></strong></td>
                        <td><?php echo esc_html($r['total_count']); ?></td>
                        <td><?php echo esc_html($r['completed_count']); ?></td>
                        <td><?php echo esc_html($r['cancelled_count']); ?></td>
                        <td><strong><?php echo esc_html($hrs); ?> hrs</strong></td>
                        <td><?php echo esc_html($r['students_count']); ?></td>
                        <td><?php echo esc_html($r['instructors_count']); ?></td>
                        <td><strong><?php echo esc_html($rate); ?>%</strong></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

    <?php elseif ('instructors' === $active_tab) : ?>
        <table class="doc-table">
            <thead>
                <tr>
                    <th>Instructor Legal Name</th>
                    <th>MVA License / Cert #</th>
                    <th>Sessions Conducted</th>
                    <th>Completed</th>
                    <th>Hours Taught</th>
                    <th>Passed Students</th>
                    <th>Pass Rate %</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)) : ?>
                    <tr><td colspan="7" style="text-align:center;padding:20px;">No instructor activity recorded.</td></tr>
                <?php else : ?>
                    <?php foreach ($rows as $r) : 
                        $comp = (int)$r['completed_count'];
                        $hrs = $comp * 2;
                        $pass_rate = (!empty($r['evaluated_count']) && $r['evaluated_count'] > 0)
                            ? round(((int)$r['passed_count'] / (int)$r['evaluated_count']) * 100) . '%'
                            : '—';
                    ?>
                    <tr>
                        <td><strong><?php echo esc_html($r['instructor_name'] ?: 'Unassigned'); ?></strong></td>
                        <td><?php echo esc_html(!empty($r['license_number']) ? $r['license_number'] : 'MD-MVA-'.strtoupper(substr(md5($r['instructor_name']), 0, 5))); ?></td>
                        <td><?php echo esc_html($r['total_count']); ?></td>
                        <td><?php echo esc_html($r['completed_count']); ?></td>
                        <td><strong><?php echo esc_html($hrs); ?> hrs</strong></td>
                        <td><?php echo esc_html($r['passed_count'] ?? 0); ?></td>
                        <td><strong><?php echo esc_html($pass_rate); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

    <?php elseif ('students' === $active_tab) : ?>
        <table class="doc-table">
            <thead>
                <tr>
                    <th>Student Legal Name</th>
                    <th>Learner Permit #</th>
                    <th>Contact Phone</th>
                    <th>Course Package</th>
                    <th>Completed Sessions</th>
                    <th>Remaining Sessions</th>
                    <th>Last Lesson Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)) : ?>
                    <tr><td colspan="7" style="text-align:center;padding:20px;">No student records found.</td></tr>
                <?php else : ?>
                    <?php foreach ($rows as $st) : 
                        $tot = max(1, (int)$st['total_sessions']);
                        $comp = (int)$st['completed_sessions'];
                        $rem = max(0, $tot - $comp);
                    ?>
                    <tr>
                        <td><strong><?php echo esc_html($st['name']); ?></strong></td>
                        <td><?php echo esc_html($st['license_number'] ?: '—'); ?></td>
                        <td><?php echo esc_html($st['phone'] ?: '—'); ?></td>
                        <td><?php echo esc_html($st['package_name'] ?: 'Standard Package'); ?></td>
                        <td><strong><?php echo esc_html($comp); ?> / <?php echo esc_html($tot); ?></strong></td>
                        <td><?php echo esc_html($rem); ?> remaining</td>
                        <td><?php echo esc_html(!empty($st['last_session_date']) ? date_i18n('M j, Y', strtotime($st['last_session_date'])) : '—'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

    <?php elseif ('vehicles' === $active_tab) : ?>
        <table class="doc-table">
            <thead>
                <tr>
                    <th>Maryland License Plate</th>
                    <th>Vehicle Model & Dual Controls</th>
                    <th>Color</th>
                    <th>Sessions Conducted</th>
                    <th>Hours Behind Wheel</th>
                    <th>Fleet Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)) : ?>
                    <tr><td colspan="6" style="text-align:center;padding:20px;">No vehicles recorded.</td></tr>
                <?php else : ?>
                    <?php foreach ($rows as $v) : ?>
                    <tr>
                        <td><strong><?php echo esc_html($v['plate_number']); ?></strong></td>
                        <td><?php echo esc_html($v['model'] ?: 'Dual-Control Training Vehicle'); ?></td>
                        <td><?php echo esc_html($v['color'] ?: 'White'); ?></td>
                        <td><?php echo esc_html($v['total_sessions']); ?> lessons</td>
                        <td><strong><?php echo esc_html((int)$v['total_sessions'] * 2); ?> hrs</strong></td>
                        <td>ACTIVE IN FLEET</td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

    <?php else : ?>
        <!-- Executive Overview Default Table -->
        <table class="doc-table">
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>Student Name</th>
                    <th>Instructor</th>
                    <th>Vehicle Plate</th>
                    <th>Curriculum Lesson</th>
                    <th>Evaluation Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($overview_data['recent_completed'])) : ?>
                    <tr><td colspan="6" style="text-align:center;padding:20px;">No recent completed driving sessions found.</td></tr>
                <?php else : ?>
                    <?php foreach ($overview_data['recent_completed'] as $rc) : 
                        $eval = 'Completed';
                        if (!empty($rc['form_data'])) {
                            $fd = json_decode($rc['form_data'], true);
                            if (!empty($fd['final_evaluation'])) $eval = $fd['final_evaluation'];
                        }
                    ?>
                    <tr>
                        <td><?php echo esc_html(date_i18n('M j, Y - g:i A', strtotime($rc['scheduled_start']))); ?></td>
                        <td><strong><?php echo esc_html($rc['student_name']); ?></strong></td>
                        <td><?php echo esc_html($rc['instructor_name'] ?: 'Unassigned'); ?></td>
                        <td><strong><?php echo esc_html($rc['plate_number']); ?></strong></td>
                        <td>Lesson <?php echo esc_html($rc['session_number']); ?> (<?php echo esc_html($rc['lesson_topic']); ?>)</td>
                        <td><strong><?php echo esc_html($eval); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- Official Sign-Off Block -->
    <footer class="doc-sign-off">
        <div>
            <div class="sign-line"></div>
            <div class="sign-label">School Director / Authorized Admin Signature</div>
            <div style="font-size:10px;color:#64748b;margin-top:2px;">Date: <?php echo esc_html(date_i18n('F j, Y', current_time('timestamp'))); ?></div>
        </div>

        <div>
            <div class="sign-line"></div>
            <div class="sign-label">Chief Driving Instructor / Examiner Signature</div>
            <div style="font-size:10px;color:#64748b;margin-top:2px;">MVA Instructor Lic. Verified</div>
        </div>

        <div class="seal-box">
            OFFICIAL ACADEMY SEAL / STAMP
        </div>
    </footer>

    <script>
        window.addEventListener('load', function() {
            setTimeout(function() {
                window.print();
            }, 500);
        });
    </script>
</body>
</html>
