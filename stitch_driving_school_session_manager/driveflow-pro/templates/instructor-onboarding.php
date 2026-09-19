<?php
defined('ABSPATH') || exit;

/**
 * DriveFlow Pro - Instructor Personnel Profile & Onboarding Portal
 * Accessible via [driveflow_instructor_onboarding] with a secure magic token.
 */

$token = sanitize_text_field(wp_unslash($_GET['token'] ?? ''));
$instructor = null;
$error_msg = '';

if (!empty($token)) {
    if (class_exists('DriveFlow_Magic_Links')) {
        $verified = DriveFlow_Magic_Links::verify_token($token);
        if (is_wp_error($verified)) {
            $error_msg = $verified->get_error_message();
        } elseif ('instructor_onboarding' !== $verified['token_type']) {
            $error_msg = 'This security link is not intended for instructor onboarding.';
        } else {
            global $wpdb;
            $instructor = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}driveflow_instructors WHERE id = %d",
                $verified['target_id']
            ), ARRAY_A);
            if (!$instructor) {
                $error_msg = 'Instructor profile record was not found.';
            }
        }
    }
}

$settings = get_option('driveflow_pro_settings', array());
$school_name = $settings['school_name'] ?? get_bloginfo('name') ?: 'DriveFlow Academy';
$logo_url = $settings['logo_url'] ?? '';
?>

<div class="df-magic-booking-wrap" id="df-instructor-onboarding-app" dir="ltr">
    <!-- Header -->
    <div class="df-portal-header">
        <?php if ($logo_url) : ?>
            <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($school_name); ?>" class="df-portal-logo">
        <?php endif; ?>
        <h1 class="df-portal-school-name"><?php echo esc_html($school_name); ?></h1>
        <p class="df-portal-subtitle">Instructor Personnel Profile & Registration Portal</p>
    </div>

    <?php if (!$instructor) : ?>
        <!-- Invalid or Missing Token Screen -->
        <div class="df-portal-card" style="text-align:center;padding:48px 24px;">
            <div style="font-size:48px;margin-bottom:12px;">🔒</div>
            <h2 style="font-size:22px;color:#0f172a;margin:0 0 10px 0;">Secure Instructor Link Required</h2>
            <p style="color:#64748b;font-size:15px;max-width:480px;margin:0 auto 24px auto;line-height:1.6;">
                <?php if ($error_msg) : ?>
                    <span style="color:#dc2626;font-weight:600;display:block;margin-bottom:8px;"><?php echo esc_html($error_msg); ?></span>
                <?php endif; ?>
                Please access this portal using the personal, time-limited onboarding link provided by academy administration.
            </p>
            <form method="get" action="" style="max-width:400px;margin:0 auto;display:flex;gap:8px;">
                <input type="text" name="token" placeholder="Paste your onboarding token or code here..." required style="flex:1;padding:12px 14px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;">
                <button type="submit" class="df-portal-btn df-portal-btn-primary">Verify Link</button>
            </form>
        </div>
    <?php else : 
        $photo_url = $instructor['photo_url'] ?? '';
        $default_avatar = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="120" height="120" viewBox="0 0 24 24" fill="%2394a3b8"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>';
    ?>
        <div class="df-portal-card df-student-summary-card" style="margin-bottom:24px;">
            <div class="df-summary-left">
                <span class="df-summary-badge" style="background:#0284c7;color:#fff;">INSTRUCTOR ONBOARDING</span>
                <h2 class="df-student-name"><?php echo esc_html($instructor['name']); ?></h2>
                <div class="df-student-meta">
                    <span>Status: <strong style="color:<?php echo ('active' === $instructor['status']) ? '#16a34a' : '#d97706'; ?>;"><?php echo esc_html(strtoupper($instructor['status'])); ?></strong></span>
                    <span>•</span>
                    <span>License: <strong><?php echo esc_html($instructor['license_number'] ?: 'Pending Entry'); ?></strong></span>
                </div>
            </div>
            <div class="df-summary-right">
                <div style="width:72px;height:72px;border-radius:50%;overflow:hidden;border:3px solid #0284c7;background:#f8fafc;box-shadow:0 4px 6px -1px rgba(0,0,0,0.1);">
                    <img id="df-current-avatar-preview" src="<?php echo esc_url($photo_url ?: $default_avatar); ?>" alt="Instructor Photo" style="width:100%;height:100%;object-fit:cover;">
                </div>
            </div>
        </div>

        <div class="df-portal-card" style="padding:32px 28px;">
            <form id="df-instructor-onboarding-form" enctype="multipart/form-data">
                <input type="hidden" name="action" value="driveflow_submit_instructor_onboarding">
                <input type="hidden" name="token" value="<?php echo esc_attr($token); ?>">
                <?php wp_nonce_field('driveflow_instructor_onboarding', 'onboarding_nonce'); ?>

                <div style="margin-bottom:28px;border-bottom:1px solid #e2e8f0;padding-bottom:16px;">
                    <h3 style="margin:0 0 6px 0;color:#0f172a;font-size:18px;">Personnel & Credentials Form</h3>
                    <p style="margin:0;color:#64748b;font-size:13px;">Please verify your contact details, enter your official MVA/DMV instructor certification number, upload your professional headshot, and provide a short bio for student lesson sheets.</p>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:#334155;margin-bottom:6px;">Full Legal Name *</label>
                        <input type="text" name="name" value="<?php echo esc_attr($instructor['name']); ?>" required style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:#334155;margin-bottom:6px;">Mobile Phone (SMS Alerts) *</label>
                        <input type="tel" name="phone" value="<?php echo esc_attr($instructor['phone']); ?>" required placeholder="(301) 555-0199" style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:#334155;margin-bottom:6px;">Email Address *</label>
                        <input type="email" name="email" value="<?php echo esc_attr($instructor['email']); ?>" required placeholder="instructor@domain.com" style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:#334155;margin-bottom:6px;">MVA/DMV Instructor Certification # *</label>
                        <input type="text" name="license_number" value="<?php echo esc_attr($instructor['license_number']); ?>" required placeholder="e.g. MD-INS-98241" style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;box-sizing:border-box;">
                    </div>
                </div>

                <!-- Profile Photo Upload Card -->
                <div style="background:#f8fafc;border:1px dashed #cbd5e1;border-radius:12px;padding:24px;text-align:center;margin-bottom:24px;">
                    <div style="width:96px;height:96px;border-radius:50%;overflow:hidden;margin:0 auto 14px auto;border:3px solid #0284c7;background:#fff;box-shadow:0 4px 6px -1px rgba(0,0,0,0.1);">
                        <img id="df-upload-preview" src="<?php echo esc_url($photo_url ?: $default_avatar); ?>" alt="Preview" style="width:100%;height:100%;object-fit:cover;">
                    </div>
                    <h4 style="margin:0 0 6px 0;font-size:15px;color:#0f172a;">Instructor Profile Photo (Headshot)</h4>
                    <p style="margin:0 0 14px 0;font-size:12px;color:#64748b;">Upload a clear professional headshot (JPG, PNG, or WEBP, max 10MB). Displayed on student booking cards and instructor profile.</p>
                    <input type="file" name="profile_photo" id="df-instructor-photo-input" accept="image/*" style="display:none;">
                    <button type="button" class="df-portal-btn df-portal-btn-secondary" onclick="document.getElementById('df-instructor-photo-input').click();" style="font-size:13px;padding:8px 18px;">
                        📷 Select / Change Photo
                    </button>
                    <span id="df-photo-filename" style="display:block;font-size:12px;color:#0284c7;font-weight:600;margin-top:8px;"></span>
                </div>

                <!-- Official Verification Documents (ID Card & Instructor Badge) -->
                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:24px;margin-bottom:24px;">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
                        <span style="font-size:22px;">🪪</span>
                        <h4 style="margin:0;font-size:16px;color:#0f172a;">Official Instructor Verification Documents</h4>
                    </div>
                    <p style="margin:0 0 18px 0;font-size:13px;color:#64748b;line-height:1.5;">
                        Please take or upload clear photos of your <strong>Driver's License / State ID Card</strong> and your <strong>Official Instructor Certification Tag / Badge</strong>. These documents are securely attached to your personnel record for MVA/DMV compliance and administrative approval.
                    </p>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                        <!-- State ID Card / Driver's License Card -->
                        <div style="background:#fff;border:1px dashed #cbd5e1;border-radius:10px;padding:18px;text-align:center;box-shadow:0 1px 3px rgba(0,0,0,0.03);">
                            <div style="font-size:32px;margin-bottom:6px;">🪪</div>
                            <h5 style="margin:0 0 4px 0;font-size:14px;color:#1e293b;">State ID / Driver's License</h5>
                            <p style="margin:0 0 12px 0;font-size:11px;color:#64748b;">Front of valid Driver's License or Government ID Card.</p>
                            
                            <div id="df-id-card-preview-wrap" style="width:100%;height:130px;border-radius:8px;overflow:hidden;background:#f8fafc;border:1px solid #e2e8f0;margin-bottom:12px;display:flex;align-items:center;justify-content:center;">
                                <?php if (!empty($instructor['id_card_url'])) : ?>
                                    <img id="df-id-card-preview" src="<?php echo esc_url($instructor['id_card_url']); ?>" alt="ID Card" style="width:100%;height:100%;object-fit:cover;">
                                    <span id="df-id-card-empty-label" style="display:none;font-size:12px;color:#94a3b8;">No photo attached</span>
                                <?php else : ?>
                                    <img id="df-id-card-preview" src="" alt="ID Card Preview" style="width:100%;height:100%;object-fit:cover;display:none;">
                                    <span id="df-id-card-empty-label" style="font-size:12px;color:#94a3b8;">📷 No ID card photo attached</span>
                                <?php endif; ?>
                            </div>

                            <input type="file" name="id_card_photo" id="df-id-card-input" accept="image/*" style="display:none;">
                            <button type="button" class="df-portal-btn df-portal-btn-secondary" onclick="document.getElementById('df-id-card-input').click();" style="font-size:12px;padding:8px 14px;width:100%;">
                                📷 Take Photo / Upload ID Card
                            </button>
                            <span id="df-id-card-filename" style="display:block;font-size:11px;color:#0284c7;font-weight:600;margin-top:6px;word-break:break-all;"></span>
                        </div>

                        <!-- Instructor Badge / Certification Tag Card -->
                        <div style="background:#fff;border:1px dashed #cbd5e1;border-radius:10px;padding:18px;text-align:center;box-shadow:0 1px 3px rgba(0,0,0,0.03);">
                            <div style="font-size:32px;margin-bottom:6px;">🏷️</div>
                            <h5 style="margin:0 0 4px 0;font-size:14px;color:#1e293b;">Instructor Tag / Badge</h5>
                            <p style="margin:0 0 12px 0;font-size:11px;color:#64748b;">Official Instructor Certificate or MVA Tag Badge.</p>

                            <div id="df-badge-preview-wrap" style="width:100%;height:130px;border-radius:8px;overflow:hidden;background:#f8fafc;border:1px solid #e2e8f0;margin-bottom:12px;display:flex;align-items:center;justify-content:center;">
                                <?php if (!empty($instructor['badge_url'])) : ?>
                                    <img id="df-badge-preview" src="<?php echo esc_url($instructor['badge_url']); ?>" alt="Badge Tag" style="width:100%;height:100%;object-fit:cover;">
                                    <span id="df-badge-empty-label" style="display:none;font-size:12px;color:#94a3b8;">No photo attached</span>
                                <?php else : ?>
                                    <img id="df-badge-preview" src="" alt="Badge Preview" style="width:100%;height:100%;object-fit:cover;display:none;">
                                    <span id="df-badge-empty-label" style="font-size:12px;color:#94a3b8;">📷 No badge tag attached</span>
                                <?php endif; ?>
                            </div>

                            <input type="file" name="badge_photo" id="df-badge-input" accept="image/*" style="display:none;">
                            <button type="button" class="df-portal-btn df-portal-btn-secondary" onclick="document.getElementById('df-badge-input').click();" style="font-size:12px;padding:8px 14px;width:100%;">
                                📷 Take Photo / Upload Tag
                            </button>
                            <span id="df-badge-filename" style="display:block;font-size:11px;color:#0284c7;font-weight:600;margin-top:6px;word-break:break-all;"></span>
                        </div>
                    </div>
                </div>

                <!-- Bio / Notes -->
                <div style="margin-bottom:24px;">
                    <label style="display:block;font-size:13px;font-weight:600;color:#334155;margin-bottom:6px;">Instructor Biography & Experience Summary</label>
                    <textarea name="bio" rows="4" placeholder="Briefly describe your driving instruction experience, languages spoken (e.g. English, Spanish, Persian), specializations (highway driving, parallel parking, first-time nervous learners)..." style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;box-sizing:border-box;font-family:inherit;"><?php echo esc_textarea($instructor['bio'] ?? ''); ?></textarea>
                </div>

                <!-- Account Security & Password Setup -->
                <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:12px;padding:24px;margin-bottom:24px;">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                        <span style="font-size:20px;">🔐</span>
                        <h4 style="margin:0;font-size:16px;color:#0369a1;">Instructor Account & Login Password</h4>
                    </div>
                    <p style="margin:0 0 16px 0;font-size:13px;color:#475569;">Create or update your private password to log into the instructor in-car field app (using your email as username).</p>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                        <div>
                            <label style="display:block;font-size:13px;font-weight:600;color:#334155;margin-bottom:6px;">Choose Password *</label>
                            <input type="password" name="password" id="df-onboarding-pwd" minlength="6" placeholder="Minimum 6 characters" style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;box-sizing:border-box;">
                        </div>
                        <div>
                            <label style="display:block;font-size:13px;font-weight:600;color:#334155;margin-bottom:6px;">Confirm Password *</label>
                            <input type="password" name="confirm_password" id="df-onboarding-pwd-confirm" minlength="6" placeholder="Re-type your password" style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;box-sizing:border-box;">
                        </div>
                    </div>
                </div>

                <!-- Instructor Compensation & Payout / Banking Preferences -->
                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:24px;margin-bottom:24px;">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                        <span style="font-size:22px;">💳</span>
                        <div>
                            <h4 style="margin:0;font-size:16px;color:#0f172a;">Payout & Direct Deposit Preferences</h4>
                            <span style="font-size:12px;color:#64748b;">Specify how you would like academy administration to disburse your bi-weekly instructional compensation.</span>
                        </div>
                    </div>

                    <?php 
                    $pref_method = !empty($instructor['payment_method']) ? $instructor['payment_method'] : 'zelle';
                    $pref_details = !empty($instructor['payment_details']) ? $instructor['payment_details'] : '';
                    ?>

                    <div style="margin:16px 0 14px 0;">
                        <label style="display:block;font-size:13px;font-weight:700;color:#334155;margin-bottom:8px;">Preferred Disbursement Method *</label>
                        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(140px, 1fr));gap:12px;">
                            <label style="display:flex;align-items:center;gap:8px;padding:12px;background:#fff;border:1px solid #cbd5e1;border-radius:8px;cursor:pointer;" class="df-pay-method-opt">
                                <input type="radio" name="payment_method" value="zelle" <?php checked($pref_method, 'zelle'); ?>>
                                <span style="font-size:13px;font-weight:600;color:#0f172a;">⚡ Zelle</span>
                            </label>
                            <label style="display:flex;align-items:center;gap:8px;padding:12px;background:#fff;border:1px solid #cbd5e1;border-radius:8px;cursor:pointer;" class="df-pay-method-opt">
                                <input type="radio" name="payment_method" value="check" <?php checked($pref_method, 'check'); ?>>
                                <span style="font-size:13px;font-weight:600;color:#0f172a;">✉️ Paper Check</span>
                            </label>
                            <label style="display:flex;align-items:center;gap:8px;padding:12px;background:#fff;border:1px solid #cbd5e1;border-radius:8px;cursor:pointer;" class="df-pay-method-opt">
                                <input type="radio" name="payment_method" value="direct_deposit" <?php checked($pref_method, 'direct_deposit'); ?>>
                                <span style="font-size:13px;font-weight:600;color:#0f172a;">🏦 Direct Deposit</span>
                            </label>
                            <label style="display:flex;align-items:center;gap:8px;padding:12px;background:#fff;border:1px solid #cbd5e1;border-radius:8px;cursor:pointer;" class="df-pay-method-opt">
                                <input type="radio" name="payment_method" value="cash" <?php checked($pref_method, 'cash'); ?>>
                                <span style="font-size:13px;font-weight:600;color:#0f172a;">💵 Cash / Other</span>
                            </label>
                        </div>
                    </div>

                    <!-- Dynamic Details Form -->
                    <div id="df-method-fields-zelle" class="df-pay-details-group" style="<?php echo ('zelle' === $pref_method) ? '' : 'display:none;'; ?>background:#fff;padding:16px;border-radius:8px;border:1px solid #cbd5e1;margin-top:12px;">
                        <div style="font-size:12px;font-weight:700;color:#0284c7;margin-bottom:10px;text-transform:uppercase;">⚡ Zelle Instant Electronic Transfer Details</div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                            <div>
                                <label style="display:block;font-size:12px;font-weight:600;color:#334155;margin-bottom:4px;">Zelle Registered Mobile Phone or Email *</label>
                                <input type="text" id="df-zelle-id" placeholder="e.g. (301) 555-0199 or name@domain.com" style="width:100%;padding:8px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;box-sizing:border-box;">
                            </div>
                            <div>
                                <label style="display:block;font-size:12px;font-weight:600;color:#334155;margin-bottom:4px;">Zelle Account Holder Full Name *</label>
                                <input type="text" id="df-zelle-name" value="<?php echo esc_attr($instructor['name']); ?>" placeholder="Full Name on Bank Account" style="width:100%;padding:8px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;box-sizing:border-box;">
                            </div>
                        </div>
                    </div>

                    <div id="df-method-fields-check" class="df-pay-details-group" style="<?php echo ('check' === $pref_method) ? '' : 'display:none;'; ?>background:#fff;padding:16px;border-radius:8px;border:1px solid #cbd5e1;margin-top:12px;">
                        <div style="font-size:12px;font-weight:700;color:#0f766e;margin-bottom:10px;text-transform:uppercase;">✉️ Physical Paper Check Mailing Address</div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:10px;">
                            <div>
                                <label style="display:block;font-size:12px;font-weight:600;color:#334155;margin-bottom:4px;">Payee Legal Full Name *</label>
                                <input type="text" id="df-check-name" value="<?php echo esc_attr($instructor['name']); ?>" placeholder="Payee Name on Check" style="width:100%;padding:8px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;box-sizing:border-box;">
                            </div>
                            <div>
                                <label style="display:block;font-size:12px;font-weight:600;color:#334155;margin-bottom:4px;">Mailing Street Address (Unit/Apt) *</label>
                                <input type="text" id="df-check-address" placeholder="e.g. 123 Main St, Apt 4B" style="width:100%;padding:8px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;box-sizing:border-box;">
                            </div>
                        </div>
                        <div style="display:grid;grid-template-columns:2fr 1fr 1fr;gap:14px;">
                            <div>
                                <label style="display:block;font-size:12px;font-weight:600;color:#334155;margin-bottom:4px;">City *</label>
                                <input type="text" id="df-check-city" placeholder="e.g. Rockville" style="width:100%;padding:8px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;box-sizing:border-box;">
                            </div>
                            <div>
                                <label style="display:block;font-size:12px;font-weight:600;color:#334155;margin-bottom:4px;">State *</label>
                                <input type="text" id="df-check-state" value="MD" maxlength="2" style="width:100%;padding:8px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;box-sizing:border-box;text-transform:uppercase;">
                            </div>
                            <div>
                                <label style="display:block;font-size:12px;font-weight:600;color:#334155;margin-bottom:4px;">ZIP Code *</label>
                                <input type="text" id="df-check-zip" placeholder="20852" maxlength="10" style="width:100%;padding:8px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;box-sizing:border-box;">
                            </div>
                        </div>
                    </div>

                    <div id="df-method-fields-direct_deposit" class="df-pay-details-group" style="<?php echo ('direct_deposit' === $pref_method) ? '' : 'display:none;'; ?>background:#fff;padding:16px;border-radius:8px;border:1px solid #cbd5e1;margin-top:12px;">
                        <div style="font-size:12px;font-weight:700;color:#7e22ce;margin-bottom:10px;text-transform:uppercase;">🏦 Direct Deposit / Bank ACH Information</div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:10px;">
                            <div>
                                <label style="display:block;font-size:12px;font-weight:600;color:#334155;margin-bottom:4px;">Financial Institution / Bank Name *</label>
                                <input type="text" id="df-ach-bank" placeholder="e.g. Bank of America / Chase / M&T" style="width:100%;padding:8px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;box-sizing:border-box;">
                            </div>
                            <div>
                                <label style="display:block;font-size:12px;font-weight:600;color:#334155;margin-bottom:4px;">Account Type *</label>
                                <select id="df-ach-type" style="width:100%;padding:8px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;box-sizing:border-box;">
                                    <option value="Checking">Checking Account</option>
                                    <option value="Savings">Savings Account</option>
                                </select>
                            </div>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                            <div>
                                <label style="display:block;font-size:12px;font-weight:600;color:#334155;margin-bottom:4px;">Routing Transit Number (9 digits) *</label>
                                <input type="text" id="df-ach-routing" placeholder="9-digit routing #" maxlength="9" style="width:100%;padding:8px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;box-sizing:border-box;font-family:monospace;">
                            </div>
                            <div>
                                <label style="display:block;font-size:12px;font-weight:600;color:#334155;margin-bottom:4px;">Account Number *</label>
                                <input type="text" id="df-ach-account" placeholder="Account #" style="width:100%;padding:8px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;box-sizing:border-box;font-family:monospace;">
                            </div>
                        </div>
                    </div>

                    <div id="df-method-fields-cash" class="df-pay-details-group" style="<?php echo ('cash' === $pref_method) ? '' : 'display:none;'; ?>background:#fff;padding:16px;border-radius:8px;border:1px solid #cbd5e1;margin-top:12px;">
                        <div style="font-size:12px;font-weight:700;color:#b45309;margin-bottom:6px;text-transform:uppercase;">💵 Cash / Direct Academy Pickup</div>
                        <p style="font-size:13px;color:#64748b;margin:0 0 10px 0;">Disbursements made in person at academy headquarters with formal signed voucher.</p>
                        <input type="text" id="df-cash-notes" placeholder="Optional notes for disbursement..." style="width:100%;padding:8px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:13px;box-sizing:border-box;">
                    </div>

                    <!-- Hidden Compiled Details Input -->
                    <input type="hidden" name="payment_details" id="df-compiled-payment-details" value="<?php echo esc_attr($pref_details); ?>">
                </div>

                <?php 
                $agreement_enabled = ('1' === ($settings['instructor_agreement_enabled'] ?? '1'));
                if ($agreement_enabled) :
                    $raw_agreement = !empty($settings['instructor_agreement_text']) ? $settings['instructor_agreement_text'] : DriveFlow_Pro::get_default_instructor_agreement();
                    $effective_date = date_i18n('F j, Y');
                    $school_addr = $settings['school_address'] ?? '751 Rockville Pike, Unit # 9B, Rockville, MD 20852';
                    $school_ph = $settings['school_phone'] ?? '(202) 600-0889 / (301) 726-3030';
                    
                    // Honor individual instructor hourly wage if configured, else default
                    $custom_rate = (!empty($instructor['hourly_wage']) && floatval($instructor['hourly_wage']) > 0)
                        ? floatval($instructor['hourly_wage'])
                        : floatval($settings['instructor_default_hourly_wage'] ?? 35.00);
                    $wage_rate = number_format($custom_rate, 2);

                    $rendered_terms = str_replace(
                        array('{school_name}', '{school_address}', '{school_phone}', '{instructor_name}', '{license_number}', '{hourly_wage}', '{effective_date}'),
                        array($school_name, $school_addr, $school_ph, $instructor['name'], $instructor['license_number'] ?: 'Pending Entry', '$' . $wage_rate, $effective_date),
                        $raw_agreement
                    );
                ?>
                <!-- Legal Framework & Employment Agreement Section -->
                <div style="background:#fff;border:1px solid #cbd5e1;border-radius:12px;padding:24px;margin-bottom:24px;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;flex-wrap:wrap;gap:10px;">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <span style="font-size:24px;">📜</span>
                            <div>
                                <h4 style="margin:0;font-size:16px;color:#0f172a;">Driving Instructor Employment Agreement & Regulatory Compliance</h4>
                                <span style="font-size:12px;color:#64748b;">Maryland MVA · COMAR 11.23 Standards · Electronic Execution under UETA</span>
                            </div>
                        </div>
                        <span class="df-summary-badge" style="background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;">MVA COMPLIANCE</span>
                    </div>

                    <!-- Individual Wage Rate Banner -->
                    <div style="background:#ecfdf5;border:1px solid #a7f3d0;border-radius:8px;padding:12px 16px;margin-bottom:14px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span style="font-size:18px;">💵</span>
                            <span style="font-size:13px;color:#065f46;font-weight:600;">Your Individual Contractual Instructional Rate:</span>
                        </div>
                        <span style="font-size:16px;color:#047857;font-weight:800;background:#fff;padding:4px 14px;border-radius:6px;border:1px solid #6ee7b7;box-shadow:0 1px 2px rgba(0,0,0,0.05);">$<?php echo esc_html($wage_rate); ?> / hour</span>
                    </div>

                    <p style="font-size:13px;color:#475569;margin:0 0 14px 0;line-height:1.5;">
                        Please review your formal instructional employment agreement below. By signing digitally below, you acknowledge your regulatory obligations under COMAR 11.23, cell phone prohibitions, vehicle safety checks, non-solicitation covenants, and hourly wage terms ($<?php echo esc_html($wage_rate); ?>/hr).
                    </p>

                    <!-- Scrollable Agreement Text Viewer -->
                    <div style="max-height:280px;overflow-y:auto;background:#f8fafc;border:1px solid #e2e8f0;padding:16px 20px;border-radius:8px;font-size:12px;line-height:1.65;color:#334155;white-space:pre-wrap;font-family:inherit;margin-bottom:18px;">
<?php echo esc_html($rendered_terms); ?>
                    </div>

                    <!-- Digital Signature Pad -->
                    <div style="background:#f8fafc;border:1px dashed #94a3b8;border-radius:10px;padding:16px;margin-bottom:16px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                            <label style="font-size:13px;font-weight:700;color:#0f172a;display:flex;align-items:center;gap:6px;">
                                <span>✍️</span> Digital Signature Pad (Draw with Finger or Mouse) *
                            </label>
                            <button type="button" id="df-clear-sig-btn" style="background:#fff;border:1px solid #cbd5e1;padding:4px 10px;font-size:11px;border-radius:4px;cursor:pointer;color:#475569;font-weight:600;">
                                ✕ Clear Signature
                            </button>
                        </div>
                        <div style="border:1px solid #cbd5e1;border-radius:6px;background:#fff;overflow:hidden;position:relative;">
                            <canvas id="df-signature-pad" width="600" height="130" style="width:100%;height:130px;display:block;cursor:crosshair;touch-action:none;"></canvas>
                            <div id="df-sig-hint" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);color:#94a3b8;font-size:13px;pointer-events:none;">
                                Sign inside this box
                            </div>
                        </div>
                        <input type="hidden" name="agreement_signature" id="df-agreement-signature-data">
                    </div>

                    <!-- Consent Checkbox -->
                    <label style="display:flex;align-items:flex-start;gap:12px;cursor:pointer;background:#f0f9ff;border:1px solid #bae6fd;padding:14px;border-radius:8px;">
                        <input type="checkbox" name="accept_agreement" id="df-accept-agreement-cb" value="1" required style="margin-top:3px;">
                        <span style="font-size:13px;color:#0369a1;line-height:1.5;">
                            <strong>Electronic Signature & Contractual Consent:</strong> I, <strong><?php echo esc_html($instructor['name']); ?></strong>, have read, understood, and accept all terms, conditions, and COMAR 11.23 regulatory covenants set forth in this Driving Instructor Employment Agreement. Pursuant to the Maryland Uniform Electronic Transactions Act (Md. Code Ann., Com. Law § 21-101 et seq.) and the federal ESIGN Act, I agree that my electronic signature and acceptance constitute a legally valid and binding execution.
                        </span>
                    </label>
                </div>
                <?php endif; ?>

                <div id="df-onboarding-msg" style="display:none;margin-bottom:16px;padding:12px 16px;border-radius:8px;font-size:14px;font-weight:600;"></div>

                <div style="text-align:right;">
                    <button type="submit" class="df-portal-btn df-portal-btn-primary" id="df-submit-onboarding-btn" style="padding:12px 28px;font-size:15px;">
                        ✓ Save & Complete Onboarding
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var photoInput = document.getElementById('df-instructor-photo-input');
    var uploadPreview = document.getElementById('df-upload-preview');
    var currentAvatarPreview = document.getElementById('df-current-avatar-preview');
    var filenameLabel = document.getElementById('df-photo-filename');
    var idCardInput = document.getElementById('df-id-card-input');
    var idCardPreview = document.getElementById('df-id-card-preview');
    var idCardEmptyLabel = document.getElementById('df-id-card-empty-label');
    var idCardFilename = document.getElementById('df-id-card-filename');
    var badgeInput = document.getElementById('df-badge-input');
    var badgePreview = document.getElementById('df-badge-preview');
    var badgeEmptyLabel = document.getElementById('df-badge-empty-label');
    var badgeFilename = document.getElementById('df-badge-filename');
    var form = document.getElementById('df-instructor-onboarding-form');
    var msgBox = document.getElementById('df-onboarding-msg');
    var submitBtn = document.getElementById('df-submit-onboarding-btn');

    if (photoInput) {
        photoInput.addEventListener('change', function(e) {
            var file = e.target.files[0];
            if (file) {
                filenameLabel.textContent = 'Selected: ' + file.name + ' (' + (file.size / 1024).toFixed(0) + ' KB)';
                var reader = new FileReader();
                reader.onload = function(evt) {
                    if (uploadPreview) uploadPreview.src = evt.target.result;
                    if (currentAvatarPreview) currentAvatarPreview.src = evt.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    }

    if (idCardInput) {
        idCardInput.addEventListener('change', function(e) {
            var file = e.target.files[0];
            if (file) {
                idCardFilename.textContent = 'Selected: ' + file.name + ' (' + (file.size / 1024).toFixed(0) + ' KB)';
                var reader = new FileReader();
                reader.onload = function(evt) {
                    if (idCardPreview) {
                        idCardPreview.src = evt.target.result;
                        idCardPreview.style.display = 'block';
                    }
                    if (idCardEmptyLabel) idCardEmptyLabel.style.display = 'none';
                };
                reader.readAsDataURL(file);
            }
        });
    }

    if (badgeInput) {
        badgeInput.addEventListener('change', function(e) {
            var file = e.target.files[0];
            if (file) {
                badgeFilename.textContent = 'Selected: ' + file.name + ' (' + (file.size / 1024).toFixed(0) + ' KB)';
                var reader = new FileReader();
                reader.onload = function(evt) {
                    if (badgePreview) {
                        badgePreview.src = evt.target.result;
                        badgePreview.style.display = 'block';
                    }
                    if (badgeEmptyLabel) badgeEmptyLabel.style.display = 'none';
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Signature Pad logic
    var canvas = document.getElementById('df-signature-pad');
    var clearBtn = document.getElementById('df-clear-sig-btn');
    var sigHint = document.getElementById('df-sig-hint');
    var sigDataInput = document.getElementById('df-agreement-signature-data');
    var acceptCb = document.getElementById('df-accept-agreement-cb');
    var hasSignature = false;

    if (canvas) {
        var ctx = canvas.getContext('2d');
        var isDrawing = false;

        function getPos(e) {
            var rect = canvas.getBoundingClientRect();
            var scaleX = canvas.width / rect.width;
            var scaleY = canvas.height / rect.height;
            var clientX = e.clientX;
            var clientY = e.clientY;

            if (e.touches && e.touches.length > 0) {
                clientX = e.touches[0].clientX;
                clientY = e.touches[0].clientY;
            }

            return {
                x: (clientX - rect.left) * scaleX,
                y: (clientY - rect.top) * scaleY
            };
        }

        function startDraw(e) {
            isDrawing = true;
            hasSignature = true;
            if (sigHint) sigHint.style.display = 'none';
            ctx.beginPath();
            ctx.lineWidth = 2.5;
            ctx.lineCap = 'round';
            ctx.strokeStyle = '#0f172a';
            var pos = getPos(e);
            ctx.moveTo(pos.x, pos.y);
            if (e.type.indexOf('touch') !== -1) e.preventDefault();
        }

        function draw(e) {
            if (!isDrawing) return;
            var pos = getPos(e);
            ctx.lineTo(pos.x, pos.y);
            ctx.stroke();
            if (e.type.indexOf('touch') !== -1) e.preventDefault();
        }

        function stopDraw() {
            isDrawing = false;
        }

        canvas.addEventListener('mousedown', startDraw);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseup', stopDraw);
        canvas.addEventListener('mouseleave', stopDraw);

        canvas.addEventListener('touchstart', startDraw, { passive: false });
        canvas.addEventListener('touchmove', draw, { passive: false });
        canvas.addEventListener('touchend', stopDraw);

        if (clearBtn) {
            clearBtn.addEventListener('click', function(e) {
                e.preventDefault();
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                hasSignature = false;
                if (sigHint) sigHint.style.display = 'block';
                if (sigDataInput) sigDataInput.value = '';
            });
        }
    }

    // Payment Method Toggler & Compiler
    var methodRadios = document.querySelectorAll('input[name="payment_method"]');
    var detailsGroups = document.querySelectorAll('.df-pay-details-group');
    var compiledDetails = document.getElementById('df-compiled-payment-details');

    methodRadios.forEach(function(radio) {
        radio.addEventListener('change', function() {
            detailsGroups.forEach(function(g) { g.style.display = 'none'; });
            var targetGroup = document.getElementById('df-method-fields-' + this.value);
            if (targetGroup) targetGroup.style.display = 'block';
        });
    });

    function compilePaymentDetails() {
        var selectedMethod = document.querySelector('input[name="payment_method"]:checked');
        var method = selectedMethod ? selectedMethod.value : 'zelle';
        var detailsText = '';

        if ('zelle' === method) {
            var zId = document.getElementById('df-zelle-id') ? document.getElementById('df-zelle-id').value.trim() : '';
            var zName = document.getElementById('df-zelle-name') ? document.getElementById('df-zelle-name').value.trim() : '';
            detailsText = 'Zelle: ' + (zId || '—') + ' (Recipient: ' + (zName || '—') + ')';
        } else if ('check' === method) {
            var cName = document.getElementById('df-check-name') ? document.getElementById('df-check-name').value.trim() : '';
            var cAddr = document.getElementById('df-check-address') ? document.getElementById('df-check-address').value.trim() : '';
            var cCity = document.getElementById('df-check-city') ? document.getElementById('df-check-city').value.trim() : '';
            var cState = document.getElementById('df-check-state') ? document.getElementById('df-check-state').value.trim() : '';
            var cZip = document.getElementById('df-check-zip') ? document.getElementById('df-check-zip').value.trim() : '';
            detailsText = 'Check Payable To: ' + (cName || '—') + ', Address: ' + (cAddr || '—') + ', ' + (cCity || '—') + ' ' + (cState || 'MD') + ' ' + (cZip || '—');
        } else if ('direct_deposit' === method) {
            var aBank = document.getElementById('df-ach-bank') ? document.getElementById('df-ach-bank').value.trim() : '';
            var aType = document.getElementById('df-ach-type') ? document.getElementById('df-ach-type').value : 'Checking';
            var aRouting = document.getElementById('df-ach-routing') ? document.getElementById('df-ach-routing').value.trim() : '';
            var aAccount = document.getElementById('df-ach-account') ? document.getElementById('df-ach-account').value.trim() : '';
            detailsText = 'Bank: ' + (aBank || '—') + ' (' + aType + '), Routing: ' + (aRouting || '—') + ', Account: ' + (aAccount || '—');
        } else if ('cash' === method) {
            var cNotes = document.getElementById('df-cash-notes') ? document.getElementById('df-cash-notes').value.trim() : '';
            detailsText = 'Cash Pickup at Academy HQ' + (cNotes ? ' (' + cNotes + ')' : '');
        }

        if (compiledDetails) compiledDetails.value = detailsText;
    }

    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            compilePaymentDetails();
            var pwd = document.getElementById('df-onboarding-pwd').value;
            var confirmPwd = document.getElementById('df-onboarding-pwd-confirm').value;

            if (pwd && pwd !== confirmPwd) {
                msgBox.style.display = 'block';
                msgBox.style.background = '#fef2f2';
                msgBox.style.color = '#b91c1c';
                msgBox.style.border = '1px solid #fecaca';
                msgBox.textContent = 'Passwords do not match. Please re-enter your password.';
                return;
            }

            if (acceptCb) {
                if (!acceptCb.checked) {
                    msgBox.style.display = 'block';
                    msgBox.style.background = '#fef2f2';
                    msgBox.style.color = '#b91c1c';
                    msgBox.style.border = '1px solid #fecaca';
                    msgBox.textContent = 'Please read and check the agreement acceptance box.';
                    acceptCb.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return;
                }
                if (!hasSignature) {
                    msgBox.style.display = 'block';
                    msgBox.style.background = '#fef2f2';
                    msgBox.style.color = '#b91c1c';
                    msgBox.style.border = '1px solid #fecaca';
                    msgBox.textContent = 'Please draw your digital signature on the signature pad before submitting.';
                    canvas.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return;
                }
                if (sigDataInput && canvas) {
                    sigDataInput.value = canvas.toDataURL('image/png');
                }
            }

            submitBtn.disabled = true;
            submitBtn.textContent = 'Registering Account & Profile...';
            msgBox.style.display = 'none';

            var formData = new FormData(form);
            var ajaxUrl = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>';

            fetch(ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                submitBtn.disabled = false;
                submitBtn.textContent = '✓ Save & Complete Onboarding';
                msgBox.style.display = 'block';

                if (data.success) {
                    msgBox.style.background = '#f0fdf4';
                    msgBox.style.color = '#15803d';
                    msgBox.style.border = '1px solid #bbf7d0';
                    msgBox.textContent = data.data && data.data.message ? data.data.message : 'Your instructor account & credentials have been successfully registered!';
                    
                    if (data.data && data.data.redirect_url) {
                        setTimeout(function() {
                            window.location.href = data.data.redirect_url;
                        }, 1600);
                    }
                } else {
                    msgBox.style.background = '#fef2f2';
                    msgBox.style.color = '#b91c1c';
                    msgBox.style.border = '1px solid #fecaca';
                    msgBox.textContent = data.data || 'Failed to save profile. Please check your fields and try again.';
                }
            })
            .catch(function(err) {
                submitBtn.disabled = false;
                submitBtn.textContent = '✓ Save & Complete Onboarding';
                msgBox.style.display = 'block';
                msgBox.style.background = '#fef2f2';
                msgBox.style.color = '#b91c1c';
                msgBox.style.border = '1px solid #fecaca';
                msgBox.textContent = 'Network or server error. Please try again.';
            });
        });
    }
});
</script>
