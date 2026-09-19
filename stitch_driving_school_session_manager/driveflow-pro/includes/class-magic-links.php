<?php
/**
 * DriveFlow Pro - Magic Links & Short URL Engine
 *
 * Handles expirable, one-time secure tokens and short link routing
 * for students self-booking and instructor profile onboarding.
 */

defined('ABSPATH') || exit;

class DriveFlow_Magic_Links {

    const TABLE = 'driveflow_magic_tokens';

    public static function init() {
        add_filter('query_vars', array(__CLASS__, 'register_query_vars'));
        add_action('init', array(__CLASS__, 'add_rewrite_rules'), 10);
        add_action('template_redirect', array(__CLASS__, 'intercept_short_link'), 5);
    }

    public static function register_query_vars($vars) {
        $vars[] = 'df_code';
        return $vars;
    }

    public static function add_rewrite_rules() {
        add_rewrite_rule('^df/([a-zA-Z0-9_-]+)/?$', 'index.php?df_code=$matches[1]', 'top');
    }

    /**
     * Generate a new secure magic token with a short code.
     *
     * @param string $type           'student_booking' or 'instructor_onboarding'
     * @param int    $target_id      Student ID or Instructor ID
     * @param int    $duration_hours 0 for permanent, or 24, 48, 168 (7d), 720 (30d)
     * @param bool   $is_single_use  Whether to burn immediately upon successful action
     * @return array
     */
    public static function create_token($type, $target_id, $duration_hours = 48, $is_single_use = true) {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;

        $target_id = absint($target_id);
        $duration_hours = intval($duration_hours);
        $is_single_use = $is_single_use ? 1 : 0;

        // Generate high-entropy 48-char hex token
        $token = wp_generate_password(48, false, false);

        // Generate unique 7-char alphanumeric short code
        $short_code = self::generate_unique_short_code();

        $now = current_time('mysql');
        $expires_at = null;
        if ($duration_hours > 0) {
            $expires_at = date('Y-m-d H:i:s', strtotime($now) + ($duration_hours * 3600));
        }

        $inserted = $wpdb->insert(
            $table,
            array(
                'token'         => $token,
                'short_code'    => $short_code,
                'token_type'    => sanitize_key($type),
                'target_id'     => $target_id,
                'is_single_use' => $is_single_use,
                'used_at'       => null,
                'expires_at'    => $expires_at,
                'status'        => 'active',
                'created_by'    => get_current_user_id(),
                'created_at'    => $now,
                'updated_at'    => $now
            ),
            array('%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s')
        );

        if (!$inserted) {
            return false;
        }

        $token_id = (int) $wpdb->insert_id;
        $short_url = self::get_short_url($short_code);

        return array(
            'id'             => $token_id,
            'token'          => $token,
            'short_code'     => $short_code,
            'short_url'      => $short_url,
            'type'           => $type,
            'target_id'      => $target_id,
            'is_single_use'  => (bool) $is_single_use,
            'expires_at'     => $expires_at,
            'duration_hours' => $duration_hours
        );
    }

    /**
     * Generate an unambiguous 7-char alphanumeric code.
     */
    private static function generate_unique_short_code() {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;
        $chars = '23456789abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
        $max_tries = 10;

        for ($i = 0; $i < $max_tries; $i++) {
            $code = '';
            for ($j = 0; $j < 7; $j++) {
                $code .= $chars[wp_rand(0, strlen($chars) - 1)];
            }
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE short_code = %s", $code));
            if (!$exists) {
                return $code;
            }
        }
        return 'df' . wp_rand(10000, 99999);
    }

    /**
     * Return public short URL for a code.
     */
    public static function get_short_url($short_code) {
        $using_permalinks = (bool) get_option('permalink_structure');
        if ($using_permalinks) {
            return home_url('/df/' . rawurlencode($short_code));
        }
        return add_query_arg('df', rawurlencode($short_code), home_url('/'));
    }

    /**
     * Verify token or short code validity.
     *
     * @param string $code_or_token Short code or full 48-char token
     * @return array|WP_Error
     */
    public static function verify_token($code_or_token) {
        global $wpdb;
        $clean = sanitize_text_field(trim((string)$code_or_token));
        if (empty($clean)) {
            return new WP_Error('empty_token', 'Security access token was not provided.');
        }

        $table = $wpdb->prefix . self::TABLE;
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE short_code = %s OR token = %s LIMIT 1",
            $clean, $clean
        ), ARRAY_A);

        if (!$row) {
            return new WP_Error('not_found', 'This security link is invalid or unrecognized.');
        }

        // Check revoked
        if ('revoked' === $row['status']) {
            return new WP_Error('revoked', 'This security link has been revoked by administration.');
        }

        // Check if already used
        if ('used' === $row['status'] || (!empty($row['is_single_use']) && !empty($row['used_at']))) {
            $used_time = !empty($row['used_at']) ? date('M j, Y g:i A', strtotime($row['used_at'])) : 'previously';
            return new WP_Error('already_used', 'This link was single-use and was already redeemed on ' . esc_html($used_time) . '.');
        }

        // Check expiration
        if (!empty($row['expires_at'])) {
            $expire_ts = strtotime($row['expires_at']);
            if ($expire_ts && $expire_ts < time()) {
                $wpdb->update($table, array('status' => 'expired'), array('id' => $row['id']));
                return new WP_Error('expired', 'This security link expired on ' . date('M j, Y g:i A', $expire_ts) . '. Please request a new link.');
            }
        }

        return $row;
    }

    /**
     * Mark a token as redeemed / burned.
     */
    public static function mark_as_used($token_id_or_string) {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;
        $now = current_time('mysql');

        if (is_numeric($token_id_or_string)) {
            $wpdb->update(
                $table,
                array('status' => 'used', 'used_at' => $now, 'updated_at' => $now),
                array('id' => absint($token_id_or_string))
            );
        } else {
            $clean = sanitize_text_field($token_id_or_string);
            $wpdb->query($wpdb->prepare(
                "UPDATE {$table} SET status = 'used', used_at = %s, updated_at = %s WHERE (token = %s OR short_code = %s) AND is_single_use = 1",
                $now, $now, $clean, $clean
            ));
        }
    }

    /**
     * Intercept and resolve incoming short link requests.
     */
    public static function intercept_short_link() {
        $code = get_query_var('df_code');
        if (empty($code) && !empty($_GET['df'])) {
            $code = sanitize_text_field(wp_unslash($_GET['df']));
        }

        if (empty($code)) {
            return;
        }

        $verified = self::verify_token($code);

        if (is_wp_error($verified)) {
            self::render_token_error_page($verified->get_error_message());
            exit;
        }

        $settings = get_option('driveflow_pro_settings', array());
        $token = $verified['token'];

        if ('student_booking' === $verified['token_type']) {
            $page_id = absint($settings['magic_booking_page_id'] ?? 0);
            $target_url = ($page_id && 'publish' === get_post_status($page_id))
                ? get_permalink($page_id)
                : home_url('/book-driving-lesson/');
            wp_safe_redirect(add_query_arg('token', $token, $target_url));
            exit;
        }

        if ('instructor_onboarding' === $verified['token_type']) {
            $page_id = absint($settings['instructor_onboarding_page_id'] ?? 0);
            $target_url = ($page_id && 'publish' === get_post_status($page_id))
                ? get_permalink($page_id)
                : home_url('/instructor-onboarding/');
            wp_safe_redirect(add_query_arg('token', $token, $target_url));
            exit;
        }

        wp_safe_redirect(home_url('/'));
        exit;
    }

    /**
     * Display a friendly, branded error card for expired or invalid magic links.
     */
    private static function render_token_error_page($error_message) {
        $settings = get_option('driveflow_pro_settings', array());
        $school_name = esc_html($settings['school_name'] ?? 'Sam Driving School');
        $logo_url = esc_url($settings['logo_url'] ?? '');
        status_header(403);
        nocache_headers();
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Link Notice · <?php echo $school_name; ?></title>
            <style>
                body {
                    margin: 0; padding: 40px 20px;
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                    background: #0f172a; color: #334155;
                    display: flex; align-items: center; justify-content: center; min-height: 80vh;
                }
                .df-card {
                    background: #ffffff; border-radius: 16px;
                    box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
                    max-width: 480px; width: 100%; padding: 40px 32px;
                    text-align: center; box-sizing: border-box;
                }
                .df-logo { max-height: 70px; margin-bottom: 20px; object-fit: contain; }
                .df-icon { font-size: 48px; margin-bottom: 12px; }
                h1 { font-size: 22px; color: #0f172a; margin: 0 0 12px 0; }
                p { font-size: 15px; color: #64748b; line-height: 1.6; margin: 0 0 24px 0; }
                .df-alert-box {
                    background: #fef2f2; border: 1px solid #fee2e2;
                    color: #991b1b; padding: 14px 16px; border-radius: 8px;
                    font-size: 14px; font-weight: 500; margin-bottom: 24px;
                }
                .df-btn {
                    display: inline-block; background: #0f766e; color: #ffffff;
                    text-decoration: none; padding: 12px 24px; border-radius: 8px;
                    font-weight: 600; font-size: 14px; transition: background 0.2s;
                }
                .df-btn:hover { background: #115e59; }
            </style>
        </head>
        <body>
            <div class="df-card">
                <?php if ($logo_url) : ?>
                    <img src="<?php echo $logo_url; ?>" alt="<?php echo $school_name; ?>" class="df-logo">
                <?php endif; ?>
                <div class="df-icon">⏳</div>
                <h1>Security Link Notice</h1>
                <div class="df-alert-box"><?php echo esc_html($error_message); ?></div>
                <p>If you believe this is an error or require a new personalized access link, please reach out to the academy administration.</p>
                <a href="<?php echo esc_url(home_url('/')); ?>" class="df-btn">Return to Academy Home</a>
            </div>
        </body>
        </html>
        <?php
    }
}
