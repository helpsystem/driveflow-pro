<?php
defined('ABSPATH') || exit;

// Student Portal View
$student = $student_data ?? null;
?>

<div class="driveflow-app driveflow-portal-wrap" dir="ltr" data-driveflow-student-portal>
    <!-- Header -->
    <div class="df-portal-header">
        <div class="df-portal-brand">
            <?php if (!empty($brand['logo'])) : ?>
                <img src="<?php echo esc_url($brand['logo']); ?>" alt="Logo">
            <?php endif; ?>
            <div>
                <h2><?php echo esc_html($brand['name'] ?: 'DriveFlow Academy'); ?></h2>
                <span>Student Driving Lesson Portal & Scorecard</span>
            </div>
        </div>
    </div>

    <!-- Student Lookup Form (if not loaded) -->
    <?php if (!$student) : ?>
        <div class="df-portal-lookup-card">
            <h3>Access Your Driving Lessons & Scorecard</h3>
            <p>Enter your registered phone number or email address to view your course progress and lesson logbook.</p>
            <form method="get" class="df-portal-search-form">
                <input type="text" name="df_student_lookup" required placeholder="Your Phone Number or Email..." value="<?php echo esc_attr($_GET['df_student_lookup'] ?? ''); ?>">
                <button type="submit" class="driveflow-button">Look Up My Profile</button>
            </form>
            <?php if (!empty($_GET['df_student_lookup'])) : ?>
                <div style="margin-top:14px;color:#ef4444;font-size:13px;">
                    ⚠️ No student record found matching this phone number or email. Please check with academy administration.
                </div>
            <?php endif; ?>
        </div>
    <?php else : 
        $total = (int)($student['total_sessions'] ?? 0);
        $done = (int)($student['completed_sessions'] ?? 0);
        $remaining = (int)($student['remaining_sessions'] ?? 0);
        $percent = ($total > 0) ? min(100, round(($done / $total) * 100)) : 0;
    ?>
        <!-- Student Dashboard -->
        <div class="df-portal-profile-card">
            <div class="df-portal-user-row">
                <div class="df-portal-avatar">🎓</div>
                <div>
                    <h2><?php echo esc_html($student['name']); ?></h2>
                    <div style="font-size:13px;color:#64748b;">
                        Permit: <strong><?php echo esc_html($student['license_number'] ?: 'N/A'); ?></strong> • 
                        Phone: <strong><?php echo esc_html($student['phone']); ?></strong>
                    </div>
                </div>
            </div>

            <!-- Progress Meter -->
            <div class="df-portal-meter-box">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                    <div>
                        <strong style="color:#0f172a;font-size:15px;"><?php echo esc_html($student['package_name']); ?></strong>
                        <span style="font-size:12px;color:#64748b;display:block;">Driving School Course Package</span>
                    </div>
                    <div style="text-align:right;">
                        <span style="font-size:18px;font-weight:800;color:<?php echo ($remaining > 0) ? '#2563eb' : '#16a34a'; ?>;">
                            <?php echo esc_html($remaining); ?> Lessons Remaining
                        </span>
                        <div style="font-size:12px;color:#64748b;">
                            <?php echo esc_html($done); ?> of <?php echo esc_html($total); ?> completed
                        </div>
                    </div>
                </div>

                <div style="background:#e2e8f0;border-radius:999px;height:12px;overflow:hidden;">
                    <div style="background:linear-gradient(90deg, #2563eb, #3b82f6);height:100%;width:<?php echo esc_attr($percent); ?>%;"></div>
                </div>
            </div>
        </div>

        <!-- Lessons History -->
        <div class="df-portal-sessions-section">
            <h3>📅 Your Driving Sessions & Scorecards</h3>
            <?php if (empty($student['sessions'])) : ?>
                <p style="color:#94a3b8;font-size:14px;">No driving lessons logged yet. Your scheduled lessons will appear here.</p>
            <?php else : ?>
                <div class="df-portal-timeline">
                    <?php foreach ($student['sessions'] as $s) : 
                        $status_class = in_array($s['status'], array('upcoming','active','completed','cancelled'), true) ? $s['status'] : 'upcoming';
                        $form_data = !empty($s['form_data']) ? json_decode($s['form_data'], true) : array();
                        $skills = $form_data['skills'] ?? null;
                        $notes = $form_data['instructor_notes'] ?? '';
                    ?>
                        <article class="df-timeline-card">
                            <div class="df-timeline-header">
                                <div>
                                    <h4>Lesson #<?php echo esc_html($s['session_number']); ?>: <?php echo esc_html($s['lesson_topic'] ?: 'Driving Lesson'); ?></h4>
                                    <span style="font-size:12px;color:#64748b;">
                                        🕒 <?php echo esc_html(date_i18n('l, F j, Y - g:i A', strtotime($s['scheduled_start']))); ?>
                                    </span>
                                </div>
                                <span class="df-badge df-badge-<?php echo esc_attr($status_class); ?>">
                                    <?php echo esc_html(strtoupper($s['status'])); ?>
                                </span>
                            </div>

                            <div style="display:flex;gap:20px;margin:10px 0;font-size:13px;color:#334155;">
                                <div>🚗 <strong>Instructor:</strong> <?php echo esc_html($s['instructor_name'] ?: 'Unassigned'); ?></div>
                                <?php if (!empty($s['plate_number'])) : ?>
                                    <div>🚘 <strong>Vehicle:</strong> <span class="df-plate-tag"><?php echo esc_html($s['plate_number']); ?></span></div>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($skills)) : 
                                $render_stars = function($score) {
                                    $out = '';
                                    for ($i = 1; $i <= 5; $i++) {
                                        $out .= ($i <= $score) ? '★' : '☆';
                                    }
                                    return $out;
                                };
                            ?>
                                <div class="df-portal-skills-box">
                                    <strong>Skills Scorecard:</strong>
                                    <div class="df-portal-skills-grid">
                                        <div>Clutch & Speed: <span class="df-star-color"><?php echo $render_stars($skills['clutch'] ?? 0); ?></span></div>
                                        <div>Parking: <span class="df-star-color"><?php echo $render_stars($skills['parking'] ?? 0); ?></span></div>
                                        <div>Steering: <span class="df-star-color"><?php echo $render_stars($skills['steering'] ?? 0); ?></span></div>
                                        <div>Traffic Rules: <span class="df-star-color"><?php echo $render_stars($skills['rules'] ?? 0); ?></span></div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($notes)) : ?>
                                <div style="margin-top:8px;background:#f8fafc;padding:8px 12px;border-radius:6px;border-left:3px solid #3b82f6;font-size:13px;">
                                    <strong>Instructor Advice:</strong> <?php echo esc_html($notes); ?>
                                </div>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Package Top-Up Ledger History -->
        <?php if (!empty($student['ledger'])) : ?>
            <div style="margin-top:30px;">
                <h4>💳 Package & Extra Sessions History</h4>
                <table style="width:100%;font-size:12px;background:#fff;border-radius:8px;border-collapse:collapse;border:1px solid #e2e8f0;">
                    <thead>
                        <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;text-align:left;">
                            <th style="padding:8px 12px;">Date</th>
                            <th style="padding:8px 12px;">Action</th>
                            <th style="padding:8px 12px;">Change</th>
                            <th style="padding:8px 12px;">Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($student['ledger'] as $entry) : ?>
                            <tr style="border-bottom:1px solid #f1f5f9;">
                                <td style="padding:8px 12px;color:#64748b;"><?php echo esc_html(date_i18n('M j, Y', strtotime($entry['created_at']))); ?></td>
                                <td style="padding:8px 12px;"><strong><?php echo esc_html(str_replace('_', ' ', ucwords($entry['action_type']))); ?></strong></td>
                                <td style="padding:8px 12px;font-weight:bold;color:<?php echo ($entry['credits_delta'] > 0) ? '#16a34a' : '#ef4444'; ?>;">
                                    <?php echo esc_html(($entry['credits_delta'] > 0 ? '+' : '') . $entry['credits_delta']); ?>
                                </td>
                                <td style="padding:8px 12px;color:#64748b;"><?php echo esc_html($entry['notes']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div style="margin-top:20px;text-align:center;">
            <a href="<?php echo esc_url(remove_query_arg('df_student_lookup')); ?>" class="driveflow-button secondary" style="font-size:12px;padding:6px 14px;">Switch Student Account</a>
        </div>
    <?php endif; ?>
</div>
