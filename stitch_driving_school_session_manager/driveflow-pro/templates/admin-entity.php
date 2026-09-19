<?php defined('ABSPATH') || exit; ?>
<div class="wrap driveflow-admin-wrap" dir="ltr">
    <div class="df-header-banner">
        <div class="df-header-title">
            <div>
                <h1>DriveFlow Pro · Manage <?php echo esc_html($title); ?></h1>
                <p>Manage driving school directory records, instructors, students, and fleet vehicles.</p>
            </div>
        </div>
        <div class="df-header-actions">
            <button type="button" class="df-btn df-btn-primary" data-df-open-modal="df-add-entity-modal">
                <span class="dashicons dashicons-plus-alt2"></span> Add New <?php echo esc_html(rtrim($title, 's')); ?>
            </button>
        </div>
    </div>

    <?php if (!empty($_GET['added'])) : ?>
        <div class="notice notice-success is-dismissible" style="border-radius:6px;margin:0 0 20px 0;"><p>Record saved successfully.</p></div>
    <?php endif; ?>

    <!-- Filter & Search Toolbar -->
    <div class="df-toolbar">
        <form method="get" class="df-filters-form">
            <input type="hidden" name="page" value="driveflow-pro-<?php echo esc_attr($entity); ?>">
            <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search records...">
            <button class="df-btn df-btn-secondary">
                <span class="dashicons dashicons-search"></span> Search
            </button>
            <?php if ($search) : ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=driveflow-pro-' . $entity)); ?>" class="df-btn df-btn-secondary df-btn-sm">Show All</a>
            <?php endif; ?>
        </form>

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
    </div>

    <?php if ('vehicles' === $entity) : ?>
        <div class="df-live-plate-studio">
            <div class="studio-preview">
                <div class="maryland-plate plate-lg maryland-plate-live-preview" title="Live Maryland Registration Preview" data-plate="MARYLAND">
                    <span class="plate-bolt bolt-tl"></span><span class="plate-bolt bolt-tr"></span>
                    <span class="plate-number" data-live-plate-text>MARYLAND</span>
                    <span class="plate-bolt bolt-bl"></span><span class="plate-bolt bolt-br"></span>
                    <span class="plate-shine"></span>
                </div>
            </div>
            <div class="studio-details">
                <h3 style="margin:0 0 6px 0;font-size:18px;color:#0f172a;display:flex;align-items:center;gap:8px;">
                    <span>🛡️ State of Maryland · Fleet Plate Studio</span>
                    <span class="df-badge df-badge-active" style="font-size:11px;">MVA APPROVED</span>
                </h3>
                <p style="margin:0 0 12px 0;font-size:13px;color:#64748b;line-height:1.5;">
                    Live stamped embossed preview for driving academy fleet. Click on any plate below to inspect full DMV specifications, registration expiration, and session logs.
                </p>
                <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                    <span style="font-size:12px;color:#0f172a;font-weight:600;">🚗 Total Fleet: <?php echo esc_html($total_rows ?? 0); ?> Cars</span>
                    <span style="font-size:12px;color:#16a34a;font-weight:600;">✓ Dual-Control Brake Inspected</span>
                    <button type="button" class="df-btn df-btn-secondary df-btn-sm" data-df-open-modal="df-add-entity-modal">
                        + Add Training Vehicle
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ('instructors' === $entity) : 
        $settings = get_option('driveflow_pro_settings', array());
        $ins_app_url = !empty($settings['instructor_page_id']) ? get_permalink($settings['instructor_page_id']) : site_url('/instructor-session/');
        $ins_onb_url = !empty($settings['instructor_onboarding_page_id']) ? get_permalink($settings['instructor_onboarding_page_id']) : site_url('/instructor-onboarding/');
    ?>
        <div style="background:linear-gradient(135deg, #091e3a 0%, #0d3859 100%);color:#fff;border-radius:12px;padding:20px 24px;margin-bottom:20px;box-shadow:0 4px 12px rgba(0,0,0,0.08);">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:14px;">
                <div style="display:flex;align-items:center;gap:12px;">
                    <span style="background:rgba(255,255,255,0.12);font-size:24px;width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;border:1px solid rgba(255,255,255,0.2);">👨‍🏫</span>
                    <div>
                        <h3 style="margin:0;font-size:17px;font-weight:800;color:#fff;">Instructor Portals & Direct Access Station</h3>
                        <p style="margin:2px 0 0 0;font-size:12px;color:#cbd5e1;">Instructors do not need a WordPress login. Provide them these secure direct links below for mobile in-car evaluations or new hire onboarding.</p>
                    </div>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(320px, 1fr));gap:14px;">
                <!-- Link 1: In-Car App -->
                <div style="background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.15);border-radius:8px;padding:14px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                        <strong style="color:#38bdf8;font-size:13px;display:flex;align-items:center;gap:6px;">
                            <span>🚗</span> Instructor In-Car Evaluation App
                        </strong>
                        <span style="background:#0284c7;color:#fff;font-size:10px;padding:1px 6px;border-radius:4px;font-weight:700;">Live Terminal</span>
                    </div>
                    <p style="font-size:11px;color:#94a3b8;margin:0 0 8px 0;line-height:1.4;">Used by instructors on mobile/tablet to view today's sessions, record student selfie, grade MVA skills, and collect digital signatures.</p>
                    <div style="display:flex;gap:6px;align-items:center;">
                        <input type="text" readonly value="<?php echo esc_attr($ins_app_url); ?>" style="flex:1;background:rgba(0,0,0,0.25);border:1px solid rgba(255,255,255,0.2);color:#f8fafc;font-size:11px;padding:4px 8px;border-radius:4px;font-family:monospace;" />
                        <button type="button" class="df-btn df-btn-secondary df-btn-xs" data-df-copy-url="<?php echo esc_attr($ins_app_url); ?>" style="font-size:11px;white-space:nowrap;">
                            📋 Copy Link
                        </button>
                        <a href="<?php echo esc_url($ins_app_url); ?>" target="_blank" class="df-btn df-btn-primary df-btn-xs" style="font-size:11px;white-space:nowrap;text-decoration:none;background:#0284c7;border-color:#0284c7;">
                            Launch ↗
                        </a>
                    </div>
                </div>

                <!-- Link 2: Onboarding -->
                <div style="background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.15);border-radius:8px;padding:14px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                        <strong style="color:#a78bfa;font-size:13px;display:flex;align-items:center;gap:6px;">
                            <span>✍️</span> New Instructor Onboarding Portal
                        </strong>
                        <span style="background:#7c3aed;color:#fff;font-size:10px;padding:1px 6px;border-radius:4px;font-weight:700;">MVA Contract</span>
                    </div>
                    <p style="font-size:11px;color:#94a3b8;margin:0 0 8px 0;line-height:1.4;">Send this link to new instructors to submit documents, upload ID Card & MVA Badge photos, and electronically sign the employment agreement.</p>
                    <div style="display:flex;gap:6px;align-items:center;">
                        <input type="text" readonly value="<?php echo esc_attr($ins_onb_url); ?>" style="flex:1;background:rgba(0,0,0,0.25);border:1px solid rgba(255,255,255,0.2);color:#f8fafc;font-size:11px;padding:4px 8px;border-radius:4px;font-family:monospace;" />
                        <button type="button" class="df-btn df-btn-secondary df-btn-xs" data-df-copy-url="<?php echo esc_attr($ins_onb_url); ?>" style="font-size:11px;white-space:nowrap;">
                            📋 Copy Link
                        </button>
                        <a href="<?php echo esc_url($ins_onb_url); ?>" target="_blank" class="df-btn df-btn-primary df-btn-xs" style="font-size:11px;white-space:nowrap;text-decoration:none;background:#7c3aed;border-color:#7c3aed;">
                            Launch ↗
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Table -->
    <div class="df-table-container">
        <table class="df-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <?php if ('instructors' === $entity) : ?>
                        <th>Instructor</th>
                        <th>Contact</th>
                        <th>Credentials & Tag</th>
                        <th>Hourly Rate</th>
                        <th>Disbursement / Banking</th>
                        <th>Hours & Compensation</th>
                        <th>Status</th>
                        <th>Actions</th>
                    <?php else : ?>
                        <?php foreach ($fields as $label => $field) : ?>
                            <th><?php echo esc_html($label); ?></th>
                        <?php endforeach; ?>
                        <th>Status</th>
                        <th>Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows) : ?>
                <tr>
                    <td colspan="<?php echo esc_attr('instructors' === $entity ? 9 : (count($fields) + 3)); ?>" style="text-align:center;padding:30px;color:#94a3b8;">
                        No records found.
                    </td>
                </tr>
            <?php else : ?>
                <?php foreach ($rows as $row) : ?>
                    <tr>
                        <td><strong>#<?php echo esc_html($row['id']); ?></strong></td>
                        <?php if ('instructors' === $entity) : 
                            $photo = !empty($row['photo_url']) ? $row['photo_url'] : 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="%2394a3b8"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>';
                        ?>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <img src="<?php echo esc_url($photo); ?>" alt="Avatar" style="width:38px;height:38px;border-radius:50%;object-fit:cover;border:2px solid #0284c7;background:#f8fafc;">
                                    <div>
                                        <strong style="font-size:14px;color:#0f172a;display:block;"><?php echo esc_html($row['name']); ?></strong>
                                        <?php if (!empty($row['onboarding_completed'])) : ?>
                                            <div style="font-size:11px;color:#16a34a;font-weight:600;">✓ Profile Complete</div>
                                        <?php else : ?>
                                            <div style="font-size:11px;color:#d97706;font-weight:600;">⏳ Onboarding Pending</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="font-size:13px;color:#0f172a;"><?php echo esc_html($row['phone'] ?: '—'); ?></div>
                                <div style="font-size:12px;color:#64748b;"><?php echo esc_html($row['email'] ?: '—'); ?></div>
                            </td>
                            <td>
                                <div style="font-size:12px;font-weight:600;color:#334155;margin-bottom:4px;">
                                    <?php echo esc_html($row['license_number'] ?: 'Lic # Pending'); ?>
                                </div>
                                <div style="display:flex;gap:4px;flex-wrap:wrap;align-items:center;">
                                    <?php if (!empty($row['id_card_url'])) : ?>
                                        <a href="<?php echo esc_url($row['id_card_url']); ?>" target="_blank" class="button button-small" style="font-size:11px;padding:1px 6px;background:#f0f9ff;color:#0284c7;border-color:#bae6fd;" title="State ID Card">🪪 ID</a>
                                    <?php endif; ?>
                                    <?php if (!empty($row['badge_url'])) : ?>
                                        <a href="<?php echo esc_url($row['badge_url']); ?>" target="_blank" class="button button-small" style="font-size:11px;padding:1px 6px;background:#faf5ff;color:#7e22ce;border-color:#e9d5ff;" title="Instructor Tag">🏷️ Tag</a>
                                    <?php endif; ?>
                                    <?php if (!empty($row['agreement_signed'])) : ?>
                                        <button type="button" class="button button-small df-view-agreement-btn" data-instructor-id="<?php echo esc_attr($row['id']); ?>" style="font-size:11px;padding:1px 6px;background:#f0fdf4;color:#15803d;border-color:#bbf7d0;" title="Signed Contract">📜 Signed</button>
                                    <?php else : ?>
                                        <span style="font-size:10px;color:#d97706;background:#fffbeb;border:1px solid #fde68a;padding:1px 5px;border-radius:3px;">⏳ No Agmt</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="df-badge" style="background:#ecfdf5;color:#047857;border:1px solid #a7f3d0;font-size:13px;font-weight:800;padding:4px 8px;">
                                    $<?php echo number_format(floatval($row['hourly_wage'] ?? 35.00), 2); ?>/hr
                                </span>
                            </td>
                            <td>
                                <?php 
                                $method = $row['payment_method'] ?: 'zelle';
                                $details = $row['payment_details'] ?: '';
                                $method_labels = array(
                                    'zelle' => array('label' => '⚡ Zelle', 'bg' => '#f0f9ff', 'col' => '#0369a1', 'bdr' => '#bae6fd'),
                                    'check' => array('label' => '✉️ Check', 'bg' => '#f0fdf4', 'col' => '#15803d', 'bdr' => '#bbf7d0'),
                                    'direct_deposit' => array('label' => '🏦 Direct Deposit', 'bg' => '#faf5ff', 'col' => '#7e22ce', 'bdr' => '#e9d5ff'),
                                    'cash' => array('label' => '💵 Cash', 'bg' => '#fffbeb', 'col' => '#b45309', 'bdr' => '#fde68a'),
                                );
                                $cfg = $method_labels[$method] ?? $method_labels['zelle'];
                                ?>
                                <div style="display:flex;flex-direction:column;gap:4px;max-width:210px;">
                                    <span class="df-badge" style="background:<?php echo $cfg['bg']; ?>;color:<?php echo $cfg['col']; ?>;border:1px solid <?php echo $cfg['bdr']; ?>;font-weight:700;font-size:11px;align-self:flex-start;">
                                        <?php echo esc_html($cfg['label']); ?>
                                    </span>
                                    <?php if (!empty($details)) : ?>
                                        <span style="font-size:11px;color:#475569;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block;" title="<?php echo esc_attr($details); ?>">
                                            <?php echo esc_html($details); ?>
                                        </span>
                                    <?php else : ?>
                                        <span style="font-size:11px;color:#94a3b8;font-style:italic;">No deposit details saved</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-size:12px;color:#0f172a;line-height:1.4;">
                                    <div><strong><?php echo esc_html($row['hours_driven'] ?? 0); ?> hrs</strong> (<?php echo esc_html($row['comp_sessions_count'] ?? 0); ?> lessons)</div>
                                    <div style="color:#64748b;font-size:11px;">Gross Earned: <strong>$<?php echo number_format(floatval($row['gross_earned'] ?? 0), 2); ?></strong></div>
                                    <div style="color:#16a34a;font-size:11px;">Paid to Date: <strong>$<?php echo number_format(floatval($row['total_paid'] ?? 0), 2); ?></strong></div>
                                    <div style="margin-top:3px;">
                                        <?php 
                                        $bal = floatval($row['balance_due'] ?? 0);
                                        if ($bal > 0) : ?>
                                            <span class="df-badge" style="background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;font-weight:800;font-size:11px;" title="Outstanding balance due">
                                                Balance Due: $<?php echo number_format($bal, 2); ?>
                                            </span>
                                        <?php else : ?>
                                            <span class="df-badge" style="background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;font-weight:700;font-size:11px;">
                                                ✓ Settled / $0.00 Due
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php $is_act = ('active' === ($row['status'] ?? 'active')); ?>
                                <button type="button" 
                                    class="df-toggle-instructor-status-btn df-badge <?php echo $is_act ? 'df-badge-active' : 'df-badge-cancelled'; ?>"
                                    data-id="<?php echo esc_attr($row['id']); ?>"
                                    data-status="<?php echo esc_attr($row['status'] ?? 'active'); ?>"
                                    title="Click to toggle Active / Inactive"
                                    style="cursor:pointer;border:1px solid currentColor;font-weight:700;padding:4px 10px;border-radius:20px;">
                                    <?php echo $is_act ? '● ACTIVE' : '○ INACTIVE'; ?>
                                </button>
                            </td>
                            <td>
                                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                    <button type="button" class="df-btn df-btn-primary df-btn-sm df-open-record-payout-btn"
                                        data-id="<?php echo esc_attr($row['id']); ?>"
                                        data-name="<?php echo esc_attr($row['name']); ?>"
                                        data-rate="<?php echo esc_attr($row['hourly_wage'] ?? 35.00); ?>"
                                        data-hours="<?php echo esc_attr($row['hours_driven'] ?? 0); ?>"
                                        data-earned="<?php echo esc_attr($row['gross_earned'] ?? 0); ?>"
                                        data-paid="<?php echo esc_attr($row['total_paid'] ?? 0); ?>"
                                        data-balance="<?php echo esc_attr($row['balance_due'] ?? 0); ?>"
                                        data-method="<?php echo esc_attr($row['payment_method'] ?? 'zelle'); ?>"
                                        data-details="<?php echo esc_attr($row['payment_details'] ?? ''); ?>"
                                        title="Record compensation payout">
                                        💵 Pay
                                    </button>
                                    <button type="button" class="df-btn df-btn-secondary df-btn-sm df-open-payout-history-btn"
                                        data-id="<?php echo esc_attr($row['id']); ?>"
                                        data-name="<?php echo esc_attr($row['name']); ?>"
                                        title="View payout archive">
                                        📜 History
                                    </button>
                                    <button type="button" class="df-btn df-btn-secondary df-btn-sm df-edit-entity-btn"
                                        data-entity="<?php echo esc_attr($entity); ?>"
                                        data-id="<?php echo esc_attr($row['id']); ?>"
                                        data-row='<?php echo esc_attr(wp_json_encode($row)); ?>'
                                        title="Edit instructor details">
                                        ✏️ Edit
                                    </button>
                                    <button type="button" class="df-btn df-btn-outline df-btn-sm df-open-magic-modal-btn"
                                        data-type="instructor_onboarding"
                                        data-target-id="<?php echo esc_attr($row['id']); ?>"
                                        data-name="<?php echo esc_attr($row['name']); ?>"
                                        data-email="<?php echo esc_attr($row['email'] ?? ''); ?>"
                                        title="Send onboarding link">
                                        🔗 Link
                                    </button>
                                </div>
                            </td>
                        <?php else : ?>
                            <?php foreach ($fields as $field) : ?>
                                <td>
                                    <?php if ('plate_number' === $field) : ?>
                                        <?php echo DriveFlow_Pro::render_maryland_plate($row[$field], 'default', $row['model'] ?? '', $row['color'] ?? ''); ?>
                                    <?php else : ?>
                                        <?php echo esc_html($row[$field] ?? '—'); ?>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                            <td>
                                <span class="df-badge df-badge-active"><?php echo esc_html(strtoupper($row['status'] ?? 'active')); ?></span>
                            </td>
                            <td>
                                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                    <button type="button" class="df-btn df-btn-secondary df-btn-sm df-edit-entity-btn"
                                        data-entity="<?php echo esc_attr($entity); ?>"
                                        data-id="<?php echo esc_attr($row['id']); ?>"
                                        data-row='<?php echo esc_attr(wp_json_encode($row)); ?>'
                                        title="Edit <?php echo esc_attr(rtrim($title, 's')); ?> details">
                                        ✏️ Edit
                                    </button>
                                </div>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>

        <!-- Unified Pagination Bar -->
        <?php if (!empty($total_pages) && $total_pages > 1) : 
            $start_item = (($paged ?? 1) - 1) * ($per_page ?? 15) + 1;
            $end_item = min($total_rows ?? 0, ($paged ?? 1) * ($per_page ?? 15));
            $build_url = function($p) use ($entity, $search, $per_page) {
                return add_query_arg(array(
                    'page' => 'driveflow-pro-' . $entity,
                    's' => $search,
                    'per_page' => $per_page ?? 15,
                    'paged' => $p
                ), admin_url('admin.php'));
            };
        ?>
        <div class="df-pagination-bar">
            <div class="df-pagination-info">
                Showing <strong><?php echo esc_html($start_item); ?></strong> to <strong><?php echo esc_html($end_item); ?></strong> of <strong><?php echo esc_html($total_rows); ?></strong> records
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

<!-- Modal: Add Entity -->
<div class="df-modal-backdrop" id="df-add-entity-modal">
    <div class="df-modal">
        <div class="df-modal-header">
            <h3>Add New <?php echo esc_html(rtrim($title, 's')); ?></h3>
            <button type="button" class="df-modal-close">&times;</button>
        </div>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="driveflow_save_entity">
            <input type="hidden" name="entity" value="<?php echo esc_attr($entity); ?>">
            <?php wp_nonce_field('driveflow_save_entity'); ?>
            <div class="df-modal-body">
                <?php if ('vehicles' === $entity) : ?>
                    <div style="text-align:center;margin-bottom:18px;padding:16px;background:#f8fafc;border-radius:10px;border:1px dashed #cbd5e1;">
                        <div class="maryland-plate plate-lg maryland-plate-live-preview" title="Maryland Registration Live Preview">
                            <span class="plate-bolt bolt-tl"></span><span class="plate-bolt bolt-tr"></span>
                            <span class="plate-number" data-live-plate-text>NEW CAR</span>
                            <span class="plate-bolt bolt-bl"></span><span class="plate-bolt bolt-br"></span>
                            <span class="plate-shine"></span>
                        </div>
                        <div style="font-size:11px;color:#64748b;margin-top:8px;">Live Stamped Maryland Plate Preview (Auto-uppercase)</div>
                    </div>
                <?php endif; ?>
                <div class="df-form-grid">
                    <?php foreach ($fields as $label => $field) : ?>
                        <?php if ('verification_docs' === $field) continue; ?>
                        <div class="df-form-group">
                            <label><?php echo esc_html($label); ?> *</label>
                            <?php if ('plate_number' === $field) : ?>
                                <input required name="plate_number" id="df-add-plate_number" type="text" placeholder="e.g. 1EA2345 or BAY-7892" style="text-transform:uppercase;font-family:monospace;font-weight:700;letter-spacing:1.5px;" pattern="[A-Za-z0-9\s\-]+" title="Plate Number must be alphanumeric (letters and numbers) in capital uppercase." autocomplete="off" oninput="this.value = this.value.toUpperCase().replace(/[^A-Z0-9\s\-]/g, '');">
                                <small style="display:block;font-size:11px;color:#64748b;margin-top:4px;">Must consist of letters and numbers (uppercase capital letters).</small>
                            <?php else : ?>
                                <input required name="<?php echo esc_attr($field); ?>" type="<?php echo ('email' === $field) ? 'email' : 'text'; ?>" placeholder="<?php echo esc_attr($label); ?>">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <?php if ('instructors' === $entity) : ?>
                        <div class="df-form-group">
                            <label>Hourly Wage ($/hr) *</label>
                            <input required name="hourly_wage" type="number" step="0.50" min="10" max="250" value="35.00" placeholder="35.00">
                            <small style="display:block;font-size:11px;color:#64748b;margin-top:4px;">Contractual instructional rate per hour for this instructor.</small>
                        </div>
                        <div class="df-form-group">
                            <label>Preferred Payment Method</label>
                            <select name="payment_method">
                                <option value="zelle">⚡ Zelle</option>
                                <option value="check">✉️ Paper Check</option>
                                <option value="direct_deposit">🏦 Direct Deposit / ACH</option>
                                <option value="cash">💵 Cash / Other</option>
                            </select>
                        </div>
                        <div class="df-form-group" style="grid-column: span 2;">
                            <label>Deposit Information (Zelle Phone/Email, Check Mailing Address, or Bank Details)</label>
                            <textarea name="payment_details" rows="2" placeholder="e.g. Zelle: (301) 555-0199 (Recipient: Instructor Name) or Mailing Address"></textarea>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="df-modal-footer">
                <button type="button" class="df-btn df-btn-secondary" data-df-close-modal>Cancel</button>
                <button type="submit" class="df-btn df-btn-primary">Save Record</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Entity -->
<div class="df-modal-backdrop" id="df-edit-entity-modal">
    <div class="df-modal">
        <div class="df-modal-header">
            <h3>Edit <?php echo esc_html(rtrim($title, 's')); ?></h3>
            <button type="button" class="df-modal-close">&times;</button>
        </div>
        <form id="df-edit-entity-form">
            <input type="hidden" name="entity" id="df-edit-entity-type" value="<?php echo esc_attr($entity); ?>">
            <input type="hidden" name="id" id="df-edit-entity-id">
            <div class="df-modal-body">
                <?php if ('vehicles' === $entity) : ?>
                    <div style="text-align:center;margin-bottom:18px;padding:16px;background:#f8fafc;border-radius:10px;border:1px dashed #cbd5e1;">
                        <div class="maryland-plate plate-lg maryland-plate-live-preview" title="Maryland Registration Live Preview">
                            <span class="plate-bolt bolt-tl"></span><span class="plate-bolt bolt-tr"></span>
                            <span class="plate-number" id="df-edit-live-plate-text" data-live-plate-text>PLATE</span>
                            <span class="plate-bolt bolt-bl"></span><span class="plate-bolt bolt-br"></span>
                            <span class="plate-shine"></span>
                        </div>
                        <div style="font-size:11px;color:#64748b;margin-top:8px;">Live Stamped Maryland Plate Preview</div>
                    </div>
                <?php endif; ?>
                <div class="df-form-grid">
                    <?php foreach ($fields as $label => $field) : ?>
                        <?php if ('verification_docs' === $field) continue; ?>
                        <div class="df-form-group">
                            <label><?php echo esc_html($label); ?> *</label>
                            <?php if ('plate_number' === $field) : ?>
                                <input required name="plate_number" id="df-edit-entity-plate_number" type="text" placeholder="e.g. 1EA2345 or BAY-7892" style="text-transform:uppercase;font-family:monospace;font-weight:700;letter-spacing:1.5px;" pattern="[A-Za-z0-9\s\-]+" title="Plate Number must be alphanumeric (letters and numbers) in capital uppercase." autocomplete="off" oninput="this.value = this.value.toUpperCase().replace(/[^A-Z0-9\s\-]/g, '');">
                                <small style="display:block;font-size:11px;color:#64748b;margin-top:4px;">Must consist of letters and numbers (uppercase capital letters).</small>
                            <?php else : ?>
                                <input required name="<?php echo esc_attr($field); ?>" id="df-edit-entity-<?php echo esc_attr($field); ?>" type="<?php echo ('email' === $field) ? 'email' : 'text'; ?>" placeholder="<?php echo esc_attr($label); ?>">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <?php if ('instructors' === $entity) : ?>
                        <div class="df-form-group" style="grid-column: span 2;">
                            <label>Profile Photo URL</label>
                            <div style="display:flex;gap:8px;">
                                <input name="photo_url" id="df-edit-instructor-photo" type="url" placeholder="https://domain.com/photo.jpg" style="flex:1;">
                                <button type="button" class="df-btn df-btn-secondary df-btn-sm" id="df-upload-instructor-photo-btn">Upload / Choose</button>
                            </div>
                            <div style="margin-top:8px;">
                                <img id="df-edit-instructor-photo-preview" src="" alt="Preview" style="max-width:64px;max-height:64px;border-radius:50%;object-fit:cover;display:none;border:2px solid #0284c7;">
                            </div>
                        </div>
                        <div class="df-form-group" style="grid-column: span 1;">
                            <label>State ID / Driver's License Card</label>
                            <div style="display:flex;gap:8px;">
                                <input name="id_card_url" id="df-edit-instructor-id-card" type="url" placeholder="https://domain.com/id-card.jpg" style="flex:1;">
                                <button type="button" class="df-btn df-btn-secondary df-btn-sm" id="df-upload-id-card-btn">Upload / Choose</button>
                            </div>
                            <div style="margin-top:8px;">
                                <a id="df-edit-id-card-link" href="#" target="_blank" style="display:none;font-size:12px;font-weight:600;color:#0284c7;">🔍 View Uploaded ID Card</a>
                            </div>
                        </div>
                        <div class="df-form-group" style="grid-column: span 1;">
                            <label>Instructor Badge / Tag Photo</label>
                            <div style="display:flex;gap:8px;">
                                <input name="badge_url" id="df-edit-instructor-badge" type="url" placeholder="https://domain.com/badge.jpg" style="flex:1;">
                                <button type="button" class="df-btn df-btn-secondary df-btn-sm" id="df-upload-badge-btn">Upload / Choose</button>
                            </div>
                            <div style="margin-top:8px;">
                                <a id="df-edit-badge-link" href="#" target="_blank" style="display:none;font-size:12px;font-weight:600;color:#7e22ce;">🔍 View Uploaded Badge</a>
                            </div>
                        </div>
                        <div class="df-form-group" style="grid-column: span 2;">
                            <label>Instructor Biography</label>
                            <textarea name="bio" id="df-edit-instructor-bio" rows="3" placeholder="Instructor bio and specialties..."></textarea>
                        </div>
                        <div class="df-form-group">
                            <label>Hourly Wage ($/hr) *</label>
                            <input required name="hourly_wage" id="df-edit-instructor-hourly_wage" type="number" step="0.50" min="10" max="250" placeholder="35.00">
                            <small style="display:block;font-size:11px;color:#64748b;margin-top:4px;">Contractual instructional compensation rate per hour.</small>
                        </div>
                        <div class="df-form-group">
                            <label>Preferred Payment Method</label>
                            <select name="payment_method" id="df-edit-instructor-payment_method">
                                <option value="zelle">⚡ Zelle</option>
                                <option value="check">✉️ Paper Check</option>
                                <option value="direct_deposit">🏦 Direct Deposit / ACH</option>
                                <option value="cash">💵 Cash / Other</option>
                            </select>
                        </div>
                        <div class="df-form-group" style="grid-column: span 2;">
                            <label>Deposit Information (Zelle Phone/Email, Check Mailing Address, or Bank Details)</label>
                            <textarea name="payment_details" id="df-edit-instructor-payment_details" rows="2" placeholder="e.g. Zelle: (301) 555-0199 (Recipient: Name) or Mailing Address"></textarea>
                        </div>
                    <?php endif; ?>
                    <div class="df-form-group">
                        <label>Status</label>
                        <select name="status" id="df-edit-entity-status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <?php if ('vehicles' === $entity) : ?>
                                <option value="maintenance">Maintenance</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="df-modal-footer">
                <button type="button" class="df-btn df-btn-secondary" data-df-close-modal>Cancel</button>
                <button type="submit" class="df-btn df-btn-primary" id="df-edit-entity-submit-btn">Save Changes</button>
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
            <input type="hidden" name="type" id="df-magic-link-type" value="instructor_onboarding">
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

<!-- Modal: Maryland Registration Showcase Modal -->
<div class="df-modal-backdrop" id="df-plate-showcase-modal">
    <div class="df-modal" style="max-width:560px;">
        <div class="df-modal-header">
            <h3>State of Maryland · Vehicle Registration Showcase</h3>
            <button type="button" class="df-modal-close">&times;</button>
        </div>
        <div class="df-modal-body df-plate-modal-wrap">
            <div class="df-plate-showcase-box">
                <div class="maryland-plate plate-xl" id="df-modal-showcase-plate">
                    <span class="plate-bolt bolt-tl"></span><span class="plate-bolt bolt-tr"></span>
                    <span class="plate-number" id="df-modal-showcase-number">MD-7821</span>
                    <span class="plate-bolt bolt-bl"></span><span class="plate-bolt bolt-br"></span>
                    <span class="plate-shine"></span>
                </div>
                <div class="df-plate-sticker-tag">DEC 26</div>
            </div>
            <div class="df-plate-info-grid">
                <div class="df-plate-info-item"><label>Jurisdiction</label><span>State of Maryland (MDOT MVA)</span></div>
                <div class="df-plate-info-item"><label>Registration Type</label><span>Commercial Driving School</span></div>
                <div class="df-plate-info-item"><label>Vehicle Model</label><span id="df-modal-showcase-model">—</span></div>
                <div class="df-plate-info-item"><label>Body Color</label><span id="df-modal-showcase-color">—</span></div>
                <div class="df-plate-info-item"><label>Fleet Status</label><span style="color:#16a34a;font-weight:700;">● Active & Road-Ready</span></div>
                <div class="df-plate-info-item"><label>Dual-Brake Inspection</label><span>Certified & Inspected</span></div>
            </div>
        </div>
        <div class="df-modal-footer" style="justify-content:space-between;">
            <button type="button" class="df-btn df-btn-secondary" id="df-copy-plate-btn">📋 Copy Plate</button>
            <div style="display:flex;gap:8px;">
                <a href="#" class="df-btn df-btn-secondary" id="df-filter-by-plate-btn">🔍 View Sessions</a>
                <button type="button" class="df-btn df-btn-primary" data-df-close-modal>Done</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Executed Instructor Employment Agreement (Maryland MVA & UETA Audit Record) -->
<div class="df-modal-backdrop" id="df-agreement-modal">
    <div class="df-modal" style="max-width:820px;">
        <div class="df-modal-header">
            <div style="display:flex;align-items:center;gap:8px;">
                <span style="font-size:22px;">📜</span>
                <div>
                    <h3 style="margin:0;font-size:16px;">Driving Instructor Employment Agreement</h3>
                    <div style="font-size:11px;color:#64748b;">Maryland MVA Regulatory Compliance & UETA Audit Certificate</div>
                </div>
            </div>
            <button type="button" class="df-modal-close">&times;</button>
        </div>
        <div class="df-modal-body" id="df-agreement-print-area" style="padding:20px 24px;">
            <!-- Audit Metadata Header -->
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:16px;margin-bottom:16px;display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:12px;">
                <div><strong>Instructor Name:</strong> <span id="df-agr-instructor-name">—</span></div>
                <div><strong>MVA License / Perm:</strong> <span id="df-agr-license-number">—</span></div>
                <div><strong>Execution Timestamp:</strong> <span id="df-agr-timestamp">—</span></div>
                <div><strong>Signer IP Address:</strong> <span id="df-agr-ip">—</span></div>
                <div><strong>Agreed Hourly Wage:</strong> <span id="df-agr-wage">—</span></div>
                <div><strong>Legal Status:</strong> <span style="color:#16a34a;font-weight:700;">✓ Executed under Maryland UETA</span></div>
            </div>

            <!-- Agreement Terms Scrollbox -->
            <div id="df-agr-contract-text" style="max-height:340px;overflow-y:auto;background:#fff;border:1px solid #cbd5e1;padding:16px 20px;border-radius:8px;font-size:12px;line-height:1.65;white-space:pre-wrap;font-family:inherit;color:#1e293b;margin-bottom:18px;"></div>

            <!-- Execution Signatures Grid -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:18px;font-size:12px;">
                <div>
                    <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:8px;">Employer Execution</div>
                    <div style="font-weight:700;font-size:14px;color:#0f172a;" id="df-agr-school-name">Sam's Driving School LLC</div>
                    <div style="margin:12px 0 6px 0;font-family:cursive;font-size:20px;color:#0369a1;border-bottom:1px solid #94a3b8;padding-bottom:4px;display:inline-block;">Sam</div>
                    <div style="color:#64748b;">Managing Member · Sam's Driving School LLC</div>
                    <div style="color:#64748b;font-size:11px;" id="df-agr-school-address">751 Rockville Pike, Unit # 9B, Rockville, MD 20852</div>
                </div>
                <div>
                    <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:8px;">Instructor Digital Execution</div>
                    <div style="margin-bottom:6px;">
                        <img id="df-agr-signature-img" src="" alt="Digital Signature" style="max-height:60px;max-width:220px;border-bottom:2px solid #0f172a;display:none;background:#fff;padding:2px;">
                        <span id="df-agr-no-sig" style="color:#d97706;font-style:italic;">No digital signature on file</span>
                    </div>
                    <div><strong id="df-agr-sig-name">—</strong></div>
                    <div style="color:#64748b;font-size:11px;">Signed Electronically pursuant to Maryland UETA</div>
                    <div style="color:#64748b;font-size:11px;" id="df-agr-sig-timestamp">—</div>
                </div>
            </div>
        </div>
        <div class="df-modal-footer" style="justify-content:space-between;">
            <button type="button" class="df-btn df-btn-secondary" id="df-print-agreement-btn">🖨️ Print / Save Agreement Record</button>
            <button type="button" class="df-btn df-btn-primary" data-df-close-modal>Close</button>
        </div>
    </div>
</div>

<?php 
if ('instructors' === $entity) {
    include DRIVEFLOW_PRO_DIR . 'templates/admin-payout-modals-partial.php';
}
?>


