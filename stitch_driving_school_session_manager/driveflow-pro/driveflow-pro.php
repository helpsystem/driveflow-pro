<?php
/**
 * Plugin Name: DriveFlow Pro
 * Description: Secure session management, interactive visual drag-and-drop calendar, student magic booking links, instructor field app, live lobby TV display board, automated HTML emails, and Wappointment synchronization.
 * Version: 1.8.5
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Author: DriveFlow Pro
 * Text Domain: driveflow-pro
 */

defined('ABSPATH') || exit;

define('DRIVEFLOW_PRO_VERSION', '1.8.5');
define('DRIVEFLOW_PRO_FILE', __FILE__);
define('DRIVEFLOW_PRO_DIR', plugin_dir_path(__FILE__));
define('DRIVEFLOW_PRO_URL', plugin_dir_url(__FILE__));

require_once DRIVEFLOW_PRO_DIR . 'includes/class-availability-engine.php';
require_once DRIVEFLOW_PRO_DIR . 'includes/class-credit-manager.php';
require_once DRIVEFLOW_PRO_DIR . 'includes/class-magic-links.php';

final class DriveFlow_Pro {
    const OPTION_KEY = 'driveflow_pro_settings';
    const REST_NAMESPACE = 'driveflow/v1';

    public static function boot() {
        $plugin = new self();
        DriveFlow_Magic_Links::init();
        $settings = get_option(self::OPTION_KEY, array());
        if (empty($settings['tv_api_key'])) {
            $settings['tv_api_key'] = wp_generate_password(48, false, false);
            update_option(self::OPTION_KEY, $settings);
        }
        self::ensure_roles();

        // Admin hooks
        add_action('admin_menu', array($plugin, 'admin_menu'));
        add_action('admin_post_driveflow_seed_demo', array($plugin, 'seed_demo_data'));
        add_action('admin_post_driveflow_clear_demo', array($plugin, 'clear_demo_data'));
        add_action('admin_post_driveflow_add_session', array($plugin, 'add_admin_session'));
        add_action('admin_post_driveflow_provision_pages', array($plugin, 'provision_pages_action'));
        add_action('admin_post_driveflow_save_student', array($plugin, 'save_student_action'));
        add_action('admin_post_driveflow_save_entity', array($plugin, 'save_entity'));
        add_action('admin_post_driveflow_export_csv', array($plugin, 'export_csv_action'));
        add_action('admin_post_driveflow_export_report_csv', array($plugin, 'export_report_csv_action'));
        add_action('admin_init', array($plugin, 'register_settings'));
        add_action('admin_enqueue_scripts', array($plugin, 'admin_assets'));

        // AJAX handlers
        add_action('wp_ajax_driveflow_quick_status', array($plugin, 'ajax_quick_status'));
        add_action('wp_ajax_driveflow_sync_wappointment_ajax', array($plugin, 'ajax_sync_wappointment'));
        add_action('wp_ajax_driveflow_send_test_email', array($plugin, 'ajax_send_test_email'));
        add_action('wp_ajax_driveflow_resend_email', array($plugin, 'ajax_resend_email'));
        add_action('wp_ajax_driveflow_check_collision', array($plugin, 'ajax_check_collision'));
        add_action('wp_ajax_driveflow_get_busy_vehicles', array($plugin, 'ajax_get_busy_vehicles'));
        add_action('wp_ajax_driveflow_get_instructor_slots', array($plugin, 'ajax_get_instructor_slots'));
        add_action('wp_ajax_driveflow_add_extra_sessions', array($plugin, 'ajax_add_extra_sessions'));
        add_action('wp_ajax_driveflow_get_student_profile', array($plugin, 'ajax_get_student_profile'));
        add_action('wp_ajax_driveflow_calendar_events', array($plugin, 'ajax_calendar_events'));
        add_action('wp_ajax_driveflow_reschedule_session', array($plugin, 'ajax_reschedule_session'));
        add_action('wp_ajax_driveflow_assign_to_session', array($plugin, 'ajax_assign_to_session'));
        add_action('wp_ajax_driveflow_add_blocked_slot', array($plugin, 'ajax_add_blocked_slot'));
        add_action('wp_ajax_driveflow_delete_blocked_slot', array($plugin, 'ajax_delete_blocked_slot'));
        add_action('wp_ajax_driveflow_send_booking_link', array($plugin, 'ajax_send_booking_link'));
        add_action('wp_ajax_driveflow_get_magic_slots', array($plugin, 'ajax_get_magic_slots'));
        add_action('wp_ajax_nopriv_driveflow_get_magic_slots', array($plugin, 'ajax_get_magic_slots'));
        add_action('wp_ajax_driveflow_student_self_book', array($plugin, 'ajax_student_self_book'));
        add_action('wp_ajax_nopriv_driveflow_student_self_book', array($plugin, 'ajax_student_self_book'));
        add_action('wp_ajax_driveflow_submit_evaluation', array($plugin, 'ajax_submit_evaluation'));
        add_action('wp_ajax_driveflow_get_session_details', array($plugin, 'ajax_get_session_details'));
        add_action('wp_ajax_driveflow_update_session', array($plugin, 'ajax_update_session'));
        add_action('wp_ajax_driveflow_delete_session', array($plugin, 'ajax_delete_session'));
        add_action('wp_ajax_driveflow_get_tv_sessions', array($plugin, 'ajax_get_tv_sessions'));
        add_action('wp_ajax_nopriv_driveflow_get_tv_sessions', array($plugin, 'ajax_get_tv_sessions'));
        add_action('wp_ajax_driveflow_get_instructor_today_sessions', array($plugin, 'ajax_get_instructor_today_sessions'));
        add_action('wp_ajax_driveflow_admin_control_tv', array($plugin, 'ajax_admin_control_tv'));
        add_action('wp_ajax_driveflow_update_student', array($plugin, 'ajax_update_student'));
        add_action('wp_ajax_driveflow_get_entity', array($plugin, 'ajax_get_entity'));
        add_action('wp_ajax_driveflow_update_entity', array($plugin, 'ajax_update_entity'));
        add_action('wp_ajax_driveflow_toggle_instructor_status', array($plugin, 'ajax_toggle_instructor_status'));
        add_action('wp_ajax_driveflow_generate_magic_link', array($plugin, 'ajax_generate_magic_link'));
        add_action('wp_ajax_driveflow_submit_instructor_onboarding', array($plugin, 'ajax_submit_instructor_onboarding'));
        add_action('wp_ajax_nopriv_driveflow_submit_instructor_onboarding', array($plugin, 'ajax_submit_instructor_onboarding'));
        add_action('wp_ajax_driveflow_get_signed_agreement', array($plugin, 'ajax_get_signed_agreement'));
        add_action('wp_ajax_driveflow_record_instructor_payout', array($plugin, 'ajax_record_instructor_payout'));
        add_action('wp_ajax_driveflow_get_instructor_payout_history', array($plugin, 'ajax_get_instructor_payout_history'));
        add_action('wp_ajax_driveflow_delete_instructor_payout', array($plugin, 'ajax_delete_instructor_payout'));

        // Frontend & REST hooks
        add_action('wp_enqueue_scripts', array($plugin, 'frontend_assets'));
        add_action('rest_api_init', array($plugin, 'register_routes'));
        add_shortcode('driveflow_instructor_form', array($plugin, 'instructor_form'));
        add_shortcode('samds_instructor_form', array($plugin, 'instructor_form'));
        add_shortcode('driveflow_tv_board', array($plugin, 'tv_board'));
        add_shortcode('samds_tv_board', array($plugin, 'tv_board'));
        add_shortcode('driveflow_student_portal', array($plugin, 'student_portal_shortcode'));
        add_shortcode('driveflow_magic_booking', array($plugin, 'magic_booking_shortcode'));
        add_shortcode('driveflow_instructor_onboarding', array($plugin, 'instructor_onboarding_shortcode'));
        add_action('init', array($plugin, 'maybe_migrate'), 20);
        add_action('template_redirect', array($plugin, 'handle_standalone_portals'));

        // Initialize Wappointment integration
        DriveFlow_Wappointment_Sync::init();
    }

    public static function activate() {
        self::ensure_roles();
        global $wpdb;
        $table = $wpdb->prefix . 'driveflow_sessions';
        $charset = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            student_name varchar(190) NOT NULL,
            instructor_name varchar(190) NOT NULL DEFAULT '',
            session_number smallint(5) unsigned NOT NULL DEFAULT 1,
            lesson_topic varchar(190) NOT NULL DEFAULT '',
            scheduled_start datetime NOT NULL,
            scheduled_end datetime NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'upcoming',
            form_data longtext NULL,
            signature longtext NULL,
            selfie longtext NULL,
            student_id bigint(20) unsigned NOT NULL DEFAULT 0,
            instructor_id bigint(20) unsigned NOT NULL DEFAULT 0,
            vehicle_id bigint(20) unsigned NOT NULL DEFAULT 0,
            plate_number varchar(50) NOT NULL DEFAULT '',
            wappointment_id bigint(20) unsigned NOT NULL DEFAULT 0,
            created_by bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY status_start (status, scheduled_start),
            KEY scheduled_start (scheduled_start),
            KEY wappointment_id (wappointment_id)
        ) {$charset};";
        dbDelta($sql);

        dbDelta("CREATE TABLE {$wpdb->prefix}driveflow_students (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(190) NOT NULL,
            phone varchar(50) NOT NULL DEFAULT '',
            email varchar(190) NOT NULL DEFAULT '',
            license_number varchar(100) NOT NULL DEFAULT '',
            package_name varchar(100) NOT NULL DEFAULT 'Standard 10-Lesson Course',
            total_sessions smallint(5) unsigned NOT NULL DEFAULT 10,
            completed_sessions smallint(5) unsigned NOT NULL DEFAULT 0,
            balance_notes text NULL,
            booking_token varchar(64) NULL DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY booking_token (booking_token),
            KEY name (name),
            KEY status (status)
        ) {$charset};");

        dbDelta("CREATE TABLE {$wpdb->prefix}driveflow_instructors (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            name varchar(190) NOT NULL,
            phone varchar(50) NOT NULL DEFAULT '',
            email varchar(190) NOT NULL DEFAULT '',
            license_number varchar(100) NOT NULL DEFAULT '',
            working_hours longtext NULL,
            default_vehicle_id bigint(20) unsigned NOT NULL DEFAULT 0,
            slot_duration_minutes int(11) NOT NULL DEFAULT 120,
            photo_url varchar(255) NOT NULL DEFAULT '',
            id_card_url varchar(255) NOT NULL DEFAULT '',
            badge_url varchar(255) NOT NULL DEFAULT '',
            agreement_signed tinyint(1) NOT NULL DEFAULT 0,
            agreement_signed_at datetime NULL DEFAULT NULL,
            agreement_signature longtext NULL,
            agreement_ip varchar(45) NOT NULL DEFAULT '',
            agreement_wage varchar(50) NOT NULL DEFAULT '',
            hourly_wage decimal(8,2) NOT NULL DEFAULT 35.00,
            payment_method varchar(50) NOT NULL DEFAULT 'zelle',
            payment_details text NULL,
            bio text NULL,
            onboarding_completed tinyint(1) NOT NULL DEFAULT 0,
            status varchar(20) NOT NULL DEFAULT 'active',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY name (name),
            KEY status (status)
        ) {$charset};");

        dbDelta("CREATE TABLE {$wpdb->prefix}driveflow_vehicles (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            plate_number varchar(50) NOT NULL,
            model varchar(190) NOT NULL DEFAULT '',
            color varchar(50) NOT NULL DEFAULT '',
            transmission varchar(50) NOT NULL DEFAULT 'Automatic',
            status varchar(20) NOT NULL DEFAULT 'active',
            current_fuel_level int(3) NOT NULL DEFAULT 100,
            last_fuel_report datetime NULL DEFAULT NULL,
            maintenance_status varchar(50) NOT NULL DEFAULT 'operational',
            maintenance_notes text NULL,
            last_incident_report text NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY plate_number (plate_number),
            KEY status (status)
        ) {$charset};");

        dbDelta("CREATE TABLE {$wpdb->prefix}driveflow_student_credits (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            student_id bigint(20) unsigned NOT NULL,
            action_type varchar(50) NOT NULL DEFAULT 'extra_session_add',
            credits_delta smallint(6) NOT NULL DEFAULT 1,
            notes varchar(255) NOT NULL DEFAULT '',
            created_by bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY student_id (student_id),
            KEY action_type (action_type)
        ) {$charset};");

        dbDelta("CREATE TABLE {$wpdb->prefix}driveflow_blocked_slots (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            target_type varchar(20) NOT NULL DEFAULT 'all',
            target_identifier varchar(190) NOT NULL DEFAULT '',
            start_time datetime NOT NULL,
            end_time datetime NOT NULL,
            reason varchar(255) NOT NULL DEFAULT '',
            created_by bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY target_idx (target_type, target_identifier),
            KEY time_idx (start_time, end_time)
        ) {$charset};");

        dbDelta("CREATE TABLE {$wpdb->prefix}driveflow_magic_tokens (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            token varchar(64) NOT NULL,
            short_code varchar(16) NOT NULL,
            token_type varchar(30) NOT NULL,
            target_id bigint(20) unsigned NOT NULL DEFAULT 0,
            is_single_use tinyint(1) NOT NULL DEFAULT 1,
            used_at datetime NULL DEFAULT NULL,
            expires_at datetime NULL DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            created_by bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY token (token),
            UNIQUE KEY short_code (short_code),
            KEY target_type_id (token_type, target_id),
            KEY status (status)
        ) {$charset};");

        dbDelta("CREATE TABLE {$wpdb->prefix}driveflow_instructor_payouts (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            instructor_id bigint(20) unsigned NOT NULL,
            instructor_name varchar(190) NOT NULL DEFAULT '',
            amount decimal(10,2) NOT NULL DEFAULT 0.00,
            hours_paid decimal(6,2) NOT NULL DEFAULT 0.00,
            hourly_rate decimal(8,2) NOT NULL DEFAULT 0.00,
            payment_date date NOT NULL,
            payment_method varchar(50) NOT NULL DEFAULT 'zelle',
            reference_number varchar(100) NOT NULL DEFAULT '',
            period_start date NULL DEFAULT NULL,
            period_end date NULL DEFAULT NULL,
            notes text NULL,
            status varchar(20) NOT NULL DEFAULT 'paid',
            created_by bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY instructor_id (instructor_id),
            KEY payment_date (payment_date),
            KEY status (status)
        ) {$charset};");

        add_option(self::OPTION_KEY, array(
            'logo_url' => '',
            'school_name' => get_bloginfo('name') ?: 'DriveFlow Academy',
            'api_poll_seconds' => 15,
            'tv_api_key' => wp_generate_password(48, false, false),
            'tv_page_password' => '',
            'instructor_page_id' => 0,
            'tv_page_id' => 0,
            'student_portal_page_id' => 0,
            'magic_booking_page_id' => 0,
            'instructor_onboarding_page_id' => 0,
            'wappointment_sync_enabled' => '1',
            'wapp_skip_student_booking_email' => '1',
            'announcement_ticker' => 'Welcome to DriveFlow Academy • Drive Safe, Stay Alert • Please have your learner permit ready.',
            'email_student_booking' => '1',
            'email_instructor_booking' => '1',
            'email_student_completion' => '1',
            'email_cancellation' => '1',
            'sender_name' => get_bloginfo('name') ?: 'DriveFlow Academy',
            'sender_email' => get_option('admin_email'),
        ));
        self::provision_pages();
    }

    private static function ensure_roles() {
        add_role('driveflow_instructor', 'DriveFlow Instructor', array('read' => true, 'edit_posts' => true, 'upload_files' => true));
        add_role('driveflow_student', 'DriveFlow Student', array('read' => true));
    }

    private static function provision_pages() {
        $settings = get_option(self::OPTION_KEY, array());
        $pages = array(
            'instructor_page_id'            => array('title' => 'Instructor Session', 'content' => '[driveflow_instructor_form]', 'aliases' => array('samds_instructor_form')),
            'tv_page_id'                    => array('title' => 'DriveFlow TV Board', 'content' => '[driveflow_tv_board]', 'aliases' => array('samds_tv_board')),
            'student_portal_page_id'        => array('title' => 'Student Driving Portal', 'content' => '[driveflow_student_portal]', 'aliases' => array()),
            'magic_booking_page_id'         => array('title' => 'Book Driving Lesson', 'content' => '[driveflow_magic_booking]', 'aliases' => array()),
            'instructor_onboarding_page_id' => array('title' => 'Instructor Onboarding', 'content' => '[driveflow_instructor_onboarding]', 'aliases' => array()),
        );
        foreach ($pages as $key => $page) {
            $page_id = absint($settings[$key] ?? 0);
            if (!$page_id || 'trash' === get_post_status($page_id)) {
                $existing = get_page_by_title($page['title'], OBJECT, 'page');
                $page_id = $existing ? $existing->ID : wp_insert_post(array(
                    'post_title' => $page['title'],
                    'post_content' => $page['content'],
                    'post_status' => 'publish',
                    'post_type' => 'page'
                ), true);
            }
            if (!is_wp_error($page_id) && $page_id) {
                $settings[$key] = (int) $page_id;
                $post = get_post($page_id);
                if ($post) {
                    $content = $post->post_content;
                    $shortcode = ('tv_page_id' === $key) 
                        ? 'driveflow_tv_board' 
                        : (('student_portal_page_id' === $key) 
                            ? 'driveflow_student_portal' 
                            : (('magic_booking_page_id' === $key) 
                                ? 'driveflow_magic_booking' 
                                : (('instructor_onboarding_page_id' === $key) ? 'driveflow_instructor_onboarding' : 'driveflow_instructor_form')));

                    // Replace legacy aliases
                    if (!empty($page['aliases'])) {
                        foreach ($page['aliases'] as $alias) {
                            $content = str_replace('[' . $alias . ']', '[' . $shortcode . ']', $content);
                        }
                    }

                    // Check if shortcode already exists
                    if (false === strpos($content, '[' . $shortcode . ']') && !has_shortcode($content, $shortcode)) {
                        $content = trim($content . "\n\n" . $page['content']);
                    } else {
                        // Deduplicate: keep only one instance of the shortcode
                        $pattern = '/\[' . preg_quote($shortcode, '/') . '\]/';
                        $match_count = 0;
                        $content = preg_replace_callback($pattern, function($m) use (&$match_count) {
                            $match_count++;
                            return ($match_count === 1) ? $m[0] : '';
                        }, $content);
                        $content = preg_replace('/<p>\s*<\/p>/', '', $content);
                    }

                    if ($content !== $post->post_content) {
                        wp_update_post(array('ID' => $page_id, 'post_content' => trim($content)));
                    }
                }
                if ('tv_page_id' === $key && !empty($settings['tv_page_password'])) {
                    wp_update_post(array('ID' => $page_id, 'post_password' => $settings['tv_page_password']));
                }
            }
        }
        update_option(self::OPTION_KEY, $settings);
    }

    public function maybe_migrate() {
        $this->ensure_schema();
        $settings = get_option(self::OPTION_KEY, array());
        if (empty($settings['instructor_page_id']) || empty($settings['tv_page_id']) || empty($settings['student_portal_page_id']) || empty($settings['magic_booking_page_id']) || empty($settings['instructor_onboarding_page_id'])) {
            self::provision_pages();
        }

        // Clean up legacy tags and duplicates across published pages
        global $wpdb;
        $pages_to_clean = $wpdb->get_results("SELECT ID, post_content FROM {$wpdb->posts} WHERE post_type = 'page' AND post_status = 'publish' AND (post_content LIKE '%samds_%' OR post_content LIKE '%driveflow_%')", ARRAY_A);
        if ($pages_to_clean) {
            foreach ($pages_to_clean as $p) {
                $c = $p['post_content'];
                $orig = $c;
                $c = str_replace(
                    array('[samds_tv_board]', '[samds_instructor_form]'),
                    array('[driveflow_tv_board]', '[driveflow_instructor_form]'),
                    $c
                );
                foreach (array('driveflow_tv_board', 'driveflow_instructor_form', 'driveflow_student_portal', 'driveflow_magic_booking', 'driveflow_instructor_onboarding') as $sc) {
                    $pat = '/\[' . preg_quote($sc, '/') . '\]/';
                    $cnt = 0;
                    $c = preg_replace_callback($pat, function($m) use (&$cnt) {
                        $cnt++;
                        return ($cnt === 1) ? $m[0] : '';
                    }, $c);
                }
                $c = preg_replace('/<p>\s*<\/p>/', '', $c);
                if (trim($c) !== trim($orig)) {
                    wp_update_post(array('ID' => $p['ID'], 'post_content' => trim($c)));
                }
            }
        }
    }

    public static function notify_calendar_change() {
        $now = time();
        update_option('driveflow_tv_reload_ts', $now);
        update_option('driveflow_last_calendar_change', $now);
    }

    private function ensure_schema() {
        global $wpdb;
        $table = $wpdb->prefix . 'driveflow_sessions';
        $col = $wpdb->get_results("SHOW COLUMNS FROM `{$table}` LIKE 'wappointment_id'");
        if (empty($col)) {
            $wpdb->query("ALTER TABLE `{$table}` ADD COLUMN `wappointment_id` bigint(20) unsigned NOT NULL DEFAULT 0 AFTER `plate_number`, ADD KEY `wappointment_id` (`wappointment_id`)");
        }
        $col_plate = $wpdb->get_results("SHOW COLUMNS FROM `{$table}` LIKE 'plate_number'");
        if (empty($col_plate)) {
            $wpdb->query("ALTER TABLE `{$table}` ADD COLUMN `plate_number` varchar(50) NOT NULL DEFAULT '' AFTER `vehicle_id`");
        }

        // Upgrade students table
        $table_st = $wpdb->prefix . 'driveflow_students';
        $col_pkg = $wpdb->get_results("SHOW COLUMNS FROM `{$table_st}` LIKE 'package_name'");
        if (empty($col_pkg)) {
            $wpdb->query("ALTER TABLE `{$table_st}` ADD COLUMN `package_name` varchar(100) NOT NULL DEFAULT 'Standard 10-Lesson Course' AFTER `license_number`, ADD COLUMN `total_sessions` smallint(5) unsigned NOT NULL DEFAULT 10 AFTER `package_name`, ADD COLUMN `completed_sessions` smallint(5) unsigned NOT NULL DEFAULT 0 AFTER `total_sessions`, ADD COLUMN `balance_notes` text NULL AFTER `completed_sessions`");
        }
        $col_tok = $wpdb->get_results("SHOW COLUMNS FROM `{$table_st}` LIKE 'booking_token'");
        if (empty($col_tok)) {
            $wpdb->query("ALTER TABLE `{$table_st}` ADD COLUMN `booking_token` varchar(64) NULL DEFAULT NULL AFTER `balance_notes`, ADD UNIQUE KEY `booking_token` (`booking_token`)");
        }

        // Upgrade instructors table
        $table_ins = $wpdb->prefix . 'driveflow_instructors';
        $col_hours = $wpdb->get_results("SHOW COLUMNS FROM `{$table_ins}` LIKE 'working_hours'");
        if (empty($col_hours)) {
            $wpdb->query("ALTER TABLE `{$table_ins}` ADD COLUMN `working_hours` longtext NULL AFTER `license_number`, ADD COLUMN `default_vehicle_id` bigint(20) unsigned NOT NULL DEFAULT 0 AFTER `working_hours`, ADD COLUMN `slot_duration_minutes` int(11) NOT NULL DEFAULT 120 AFTER `default_vehicle_id`");
        }

        // Upgrade vehicles table
        $table_veh = $wpdb->prefix . 'driveflow_vehicles';
        $col_trans = $wpdb->get_results("SHOW COLUMNS FROM `{$table_veh}` LIKE 'transmission'");
        if (empty($col_trans)) {
            $wpdb->query("ALTER TABLE `{$table_veh}` ADD COLUMN `transmission` varchar(50) NOT NULL DEFAULT 'Automatic' AFTER `color`");
        }
        $col_fuel = $wpdb->get_results("SHOW COLUMNS FROM `{$table_veh}` LIKE 'current_fuel_level'");
        if (empty($col_fuel)) {
            $wpdb->query("ALTER TABLE `{$table_veh}` 
                ADD COLUMN `current_fuel_level` int(3) NOT NULL DEFAULT 100 AFTER `status`,
                ADD COLUMN `last_fuel_report` datetime NULL DEFAULT NULL AFTER `current_fuel_level`,
                ADD COLUMN `maintenance_status` varchar(50) NOT NULL DEFAULT 'operational' AFTER `last_fuel_report`,
                ADD COLUMN `maintenance_notes` text NULL AFTER `maintenance_status`,
                ADD COLUMN `last_incident_report` text NULL AFTER `maintenance_notes`");
        }

        // Credits Ledger Table
        $table_credits = $wpdb->prefix . 'driveflow_student_credits';
        $credits_exist = $wpdb->get_var("SHOW TABLES LIKE '{$table_credits}'");
        if (!$credits_exist) {
            $charset = $wpdb->get_charset_collate();
            $wpdb->query("CREATE TABLE {$table_credits} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                student_id bigint(20) unsigned NOT NULL,
                action_type varchar(50) NOT NULL DEFAULT 'extra_session_add',
                credits_delta smallint(6) NOT NULL DEFAULT 1,
                notes varchar(255) NOT NULL DEFAULT '',
                created_by bigint(20) unsigned NOT NULL DEFAULT 0,
                created_at datetime NOT NULL,
                PRIMARY KEY (id),
                KEY student_id (student_id),
                KEY action_type (action_type)
            ) {$charset};");
        }

        // Blocked Slots Table
        $table_blocked = $wpdb->prefix . 'driveflow_blocked_slots';
        $blocked_exist = $wpdb->get_var("SHOW TABLES LIKE '{$table_blocked}'");
        if (!$blocked_exist) {
            $charset = $wpdb->get_charset_collate();
            $wpdb->query("CREATE TABLE {$table_blocked} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                target_type varchar(20) NOT NULL DEFAULT 'all',
                target_identifier varchar(190) NOT NULL DEFAULT '',
                start_time datetime NOT NULL,
                end_time datetime NOT NULL,
                reason varchar(255) NOT NULL DEFAULT '',
                created_by bigint(20) unsigned NOT NULL DEFAULT 0,
                created_at datetime NOT NULL,
                PRIMARY KEY (id),
                KEY target_idx (target_type, target_identifier),
                KEY time_idx (start_time, end_time)
            ) {$charset};");
        }

        // Upgrade instructors table with user_id, photo, bio, onboarding
        $col_uid = $wpdb->get_results("SHOW COLUMNS FROM `{$table_ins}` LIKE 'user_id'");
        if (empty($col_uid)) {
            $wpdb->query("ALTER TABLE `{$table_ins}` ADD COLUMN `user_id` bigint(20) unsigned NOT NULL DEFAULT 0 AFTER `id`, ADD KEY `user_id` (`user_id`)");
        }
        $col_photo = $wpdb->get_results("SHOW COLUMNS FROM `{$table_ins}` LIKE 'photo_url'");
        if (empty($col_photo)) {
            $wpdb->query("ALTER TABLE `{$table_ins}` ADD COLUMN `photo_url` varchar(255) NOT NULL DEFAULT '' AFTER `slot_duration_minutes`, ADD COLUMN `bio` text NULL AFTER `photo_url`, ADD COLUMN `onboarding_completed` tinyint(1) NOT NULL DEFAULT 0 AFTER `bio`");
        }
        $col_docs = $wpdb->get_results("SHOW COLUMNS FROM `{$table_ins}` LIKE 'id_card_url'");
        if (empty($col_docs)) {
            $wpdb->query("ALTER TABLE `{$table_ins}` ADD COLUMN `id_card_url` varchar(255) NOT NULL DEFAULT '' AFTER `photo_url`, ADD COLUMN `badge_url` varchar(255) NOT NULL DEFAULT '' AFTER `id_card_url`");
        }
        $col_agr = $wpdb->get_results("SHOW COLUMNS FROM `{$table_ins}` LIKE 'agreement_signed'");
        if (empty($col_agr)) {
            $wpdb->query("ALTER TABLE `{$table_ins}` ADD COLUMN `agreement_signed` tinyint(1) NOT NULL DEFAULT 0 AFTER `badge_url`, ADD COLUMN `agreement_signed_at` datetime NULL DEFAULT NULL AFTER `agreement_signed`, ADD COLUMN `agreement_signature` longtext NULL AFTER `agreement_signed_at`, ADD COLUMN `agreement_ip` varchar(45) NOT NULL DEFAULT '' AFTER `agreement_signature`, ADD COLUMN `agreement_wage` varchar(50) NOT NULL DEFAULT '' AFTER `agreement_ip`");
        }
        $col_wage = $wpdb->get_results("SHOW COLUMNS FROM `{$table_ins}` LIKE 'hourly_wage'");
        if (empty($col_wage)) {
            $wpdb->query("ALTER TABLE `{$table_ins}` ADD COLUMN `hourly_wage` decimal(8,2) NOT NULL DEFAULT 35.00 AFTER `agreement_wage`, ADD COLUMN `payment_method` varchar(50) NOT NULL DEFAULT 'zelle' AFTER `hourly_wage`, ADD COLUMN `payment_details` text NULL AFTER `payment_method`");
        }

        // Magic Tokens Table
        $table_tokens = $wpdb->prefix . 'driveflow_magic_tokens';
        $tokens_exist = $wpdb->get_var("SHOW TABLES LIKE '{$table_tokens}'");
        if (!$tokens_exist) {
            $charset = $wpdb->get_charset_collate();
            $wpdb->query("CREATE TABLE {$table_tokens} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                token varchar(64) NOT NULL,
                short_code varchar(16) NOT NULL,
                token_type varchar(30) NOT NULL,
                target_id bigint(20) unsigned NOT NULL DEFAULT 0,
                is_single_use tinyint(1) NOT NULL DEFAULT 1,
                used_at datetime NULL DEFAULT NULL,
                expires_at datetime NULL DEFAULT NULL,
                status varchar(20) NOT NULL DEFAULT 'active',
                created_by bigint(20) unsigned NOT NULL DEFAULT 0,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY token (token),
                UNIQUE KEY short_code (short_code),
                KEY target_type_id (token_type, target_id),
                KEY status (status)
            ) {$charset};");
        }

        // Instructor Payouts Ledger Table
        $table_payouts = $wpdb->prefix . 'driveflow_instructor_payouts';
        $payouts_exist = $wpdb->get_var("SHOW TABLES LIKE '{$table_payouts}'");
        if (!$payouts_exist) {
            $charset = $wpdb->get_charset_collate();
            $wpdb->query("CREATE TABLE {$table_payouts} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                instructor_id bigint(20) unsigned NOT NULL,
                instructor_name varchar(190) NOT NULL DEFAULT '',
                amount decimal(10,2) NOT NULL DEFAULT 0.00,
                hours_paid decimal(6,2) NOT NULL DEFAULT 0.00,
                hourly_rate decimal(8,2) NOT NULL DEFAULT 0.00,
                payment_date date NOT NULL,
                payment_method varchar(50) NOT NULL DEFAULT 'zelle',
                reference_number varchar(100) NOT NULL DEFAULT '',
                period_start date NULL DEFAULT NULL,
                period_end date NULL DEFAULT NULL,
                notes text NULL,
                status varchar(20) NOT NULL DEFAULT 'paid',
                created_by bigint(20) unsigned NOT NULL DEFAULT 0,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY (id),
                KEY instructor_id (instructor_id),
                KEY payment_date (payment_date),
                KEY status (status)
            ) {$charset};");
        }

        // Database Normalization: Ensure existing plates across fleet and sessions are uppercase
        $wpdb->query("UPDATE `{$table_veh}` SET `plate_number` = UPPER(TRIM(`plate_number`)) WHERE `plate_number` IS NOT NULL AND `plate_number` != UPPER(`plate_number`)");
        $wpdb->query("UPDATE `{$table}` SET `plate_number` = UPPER(TRIM(`plate_number`)) WHERE `plate_number` IS NOT NULL AND `plate_number` != UPPER(`plate_number`)");
    }

    public function admin_menu() {
        add_menu_page('DriveFlow Pro', 'DriveFlow Pro', 'manage_options', 'driveflow-pro-hub', array($this, 'admin_hub_page'), 'dashicons-car', 26);
        add_submenu_page('driveflow-pro-hub', 'Admin Central Hub', 'Admin Central Hub', 'manage_options', 'driveflow-pro-hub', array($this, 'admin_hub_page'));
        add_submenu_page('driveflow-pro-hub', 'Visual Calendar', 'Visual Calendar', 'manage_options', 'driveflow-pro-calendar', array($this, 'calendar_page'));
        add_submenu_page('driveflow-pro-hub', 'Sessions & Records', 'Sessions & Records', 'manage_options', 'driveflow-pro-records', array($this, 'records_page'));
        add_submenu_page('driveflow-pro-hub', 'Students & Credits', 'Students & Credits', 'manage_options', 'driveflow-pro-students', array($this, 'students_page'));
        add_submenu_page('driveflow-pro-hub', 'Instructors', 'Instructors', 'manage_options', 'driveflow-pro-instructors', array($this, 'people_page'));
        add_submenu_page('driveflow-pro-hub', 'Vehicles & Fleet', 'Vehicles & Fleet', 'manage_options', 'driveflow-pro-vehicles', array($this, 'vehicles_page'));
        add_submenu_page('driveflow-pro-hub', 'Reports & Analytics', 'Reports & Analytics', 'manage_options', 'driveflow-pro-reports', array($this, 'reports_page'));
        add_submenu_page('driveflow-pro-hub', 'Settings & Email', 'Settings & Email', 'manage_options', 'driveflow-pro-settings', array($this, 'settings_page'));
        add_submenu_page(null, 'Admin Central Hub', 'Admin Central Hub', 'manage_options', 'driveflow-pro', array($this, 'admin_hub_page'));
    }

    public function register_settings() {
        register_setting('driveflow_pro_settings_group', self::OPTION_KEY, array($this, 'sanitize_settings'));

        // General Section
        add_settings_section('driveflow_general', 'Branding & Lobby TV Display', '__return_false', 'driveflow-pro-settings');
        add_settings_field('school_name', 'Driving School Name', array($this, 'text_field'), 'driveflow-pro-settings', 'driveflow_general', array('key' => 'school_name', 'type' => 'text'));
        add_settings_field('logo_url', 'School Logo', array($this, 'logo_field'), 'driveflow-pro-settings', 'driveflow_general');
        add_settings_field('announcement_ticker', 'Lobby TV Announcement Ticker', array($this, 'text_field'), 'driveflow-pro-settings', 'driveflow_general', array('key' => 'announcement_ticker', 'type' => 'text'));
        add_settings_field('api_poll_seconds', 'TV Refresh Interval (seconds)', array($this, 'text_field'), 'driveflow-pro-settings', 'driveflow_general', array('key' => 'api_poll_seconds', 'type' => 'number'));
        add_settings_field('tv_page_id', 'TV Board WordPress Page ID', array($this, 'text_field'), 'driveflow-pro-settings', 'driveflow_general', array('key' => 'tv_page_id', 'type' => 'number'));
        add_settings_field('tv_api_key', 'TV Display API Key', array($this, 'api_key_field'), 'driveflow-pro-settings', 'driveflow_general');

        // Email Notifications Section
        add_settings_section('driveflow_emails', 'Automated Email Notifications (Student, Instructor & Admin)', '__return_false', 'driveflow-pro-settings');
        add_settings_field('sender_name', 'Email Sender Name', array($this, 'text_field'), 'driveflow-pro-settings', 'driveflow_emails', array('key' => 'sender_name', 'type' => 'text'));
        add_settings_field('sender_email', 'Email Sender Address', array($this, 'text_field'), 'driveflow-pro-settings', 'driveflow_emails', array('key' => 'sender_email', 'type' => 'email'));
        add_settings_field('email_student_booking', 'Booking Notice to Student', array($this, 'checkbox_field'), 'driveflow-pro-settings', 'driveflow_emails', array('key' => 'email_student_booking', 'label' => 'Automatically email lesson schedule, vehicle plate & instructor details to student upon booking'));
        add_settings_field('email_instructor_booking', 'Lesson Notice to Instructor', array($this, 'checkbox_field'), 'driveflow-pro-settings', 'driveflow_emails', array('key' => 'email_instructor_booking', 'label' => 'Email scheduled appointment alert & student contact info to the assigned instructor'));
        add_settings_field('email_student_completion', 'Session Scorecard & Completion Email', array($this, 'checkbox_field'), 'driveflow-pro-settings', 'driveflow_emails', array('key' => 'email_student_completion', 'label' => 'Email driving skill ratings (stars), instructor feedback notes & completion acknowledgment to student'));
        add_settings_field('email_cancellation', 'Cancellation Notices', array($this, 'checkbox_field'), 'driveflow-pro-settings', 'driveflow_emails', array('key' => 'email_cancellation', 'label' => 'Send instant notification emails if a scheduled driving session is cancelled'));

        // Integration Section
        add_settings_section('driveflow_integration', 'Wappointment Online Booking Integration', '__return_false', 'driveflow-pro-settings');
        add_settings_field('wappointment_sync_enabled', 'Wappointment Auto-Sync', array($this, 'checkbox_field'), 'driveflow-pro-settings', 'driveflow_integration', array('key' => 'wappointment_sync_enabled', 'label' => 'Automatically convert online bookings from Wappointment into DriveFlow driving sessions & sync with TV board'));
        add_settings_field('wapp_skip_student_booking_email', 'Avoid Duplicate Emails', array($this, 'checkbox_field'), 'driveflow-pro-settings', 'driveflow_integration', array('key' => 'wapp_skip_student_booking_email', 'label' => 'Skip DriveFlow booking confirmation email to student when Wappointment is active (Wappointment already sends its own confirmation — enabling this avoids duplicate emails)'));

        // Legal & Instructor Employment Agreement Section
        add_settings_section('driveflow_legal', 'Instructor Employment Agreement & Maryland Regulatory Compliance', '__return_false', 'driveflow-pro-settings');
        add_settings_field('instructor_agreement_enabled', 'Require Employment Agreement', array($this, 'checkbox_field'), 'driveflow-pro-settings', 'driveflow_legal', array(
            'key' => 'instructor_agreement_enabled',
            'label' => 'Require driving instructors to review, accept, and digitally sign the Employment Agreement & COMAR 11.23 Regulatory Covenants during onboarding'
        ));
        add_settings_field('school_address', 'School Principal Office Address', array($this, 'text_field'), 'driveflow-pro-settings', 'driveflow_legal', array(
            'key' => 'school_address',
            'type' => 'text'
        ));
        add_settings_field('school_phone', 'School Contact Telephone Lines', array($this, 'text_field'), 'driveflow-pro-settings', 'driveflow_legal', array(
            'key' => 'school_phone',
            'type' => 'text'
        ));
        add_settings_field('instructor_default_hourly_wage', 'Default Hourly Instructional Wage ($/hr)', array($this, 'text_field'), 'driveflow-pro-settings', 'driveflow_legal', array(
            'key' => 'instructor_default_hourly_wage',
            'type' => 'text'
        ));
        add_settings_field('instructor_agreement_text', 'Employment Agreement Legal Terms (Editable)', array($this, 'textarea_field'), 'driveflow-pro-settings', 'driveflow_legal', array(
            'key' => 'instructor_agreement_text',
            'rows' => 16,
            'default' => self::get_default_instructor_agreement(),
            'desc' => 'Available merge tags: {school_name}, {school_address}, {school_phone}, {instructor_name}, {license_number}, {hourly_wage}, {effective_date}'
        ));
    }

    public function sanitize_settings($input) {
        $old = get_option(self::OPTION_KEY, array());
        $api_key = sanitize_text_field($input['tv_api_key'] ?? ($old['tv_api_key'] ?? ''));
        return array(
            'school_name' => sanitize_text_field($input['school_name'] ?? ($old['school_name'] ?? 'Sam\'s Driving School LLC')),
            'logo_url' => esc_url_raw($input['logo_url'] ?? ($old['logo_url'] ?? '')),
            'announcement_ticker' => sanitize_text_field($input['announcement_ticker'] ?? ($old['announcement_ticker'] ?? '')),
            'api_poll_seconds' => max(5, min(300, absint($input['api_poll_seconds'] ?? 15))),
            'tv_api_key' => $api_key ?: wp_generate_password(48, false, false),
            'tv_page_password' => sanitize_text_field($input['tv_page_password'] ?? ($old['tv_page_password'] ?? '')),
            'instructor_page_id' => absint($old['instructor_page_id'] ?? 0),
            'tv_page_id' => isset($input['tv_page_id']) && $input['tv_page_id'] !== '' ? absint($input['tv_page_id']) : absint($old['tv_page_id'] ?? 24),
            'student_portal_page_id' => absint($old['student_portal_page_id'] ?? 0),
            'magic_booking_page_id' => absint($old['magic_booking_page_id'] ?? 0),
            'instructor_onboarding_page_id' => absint($old['instructor_onboarding_page_id'] ?? 0),
            'wappointment_sync_enabled' => !empty($input['wappointment_sync_enabled']) ? '1' : '0',
            'wapp_skip_student_booking_email' => !empty($input['wapp_skip_student_booking_email']) ? '1' : '0',
            'email_student_booking' => !empty($input['email_student_booking']) ? '1' : '0',
            'email_instructor_booking' => !empty($input['email_instructor_booking']) ? '1' : '0',
            'email_student_completion' => !empty($input['email_student_completion']) ? '1' : '0',
            'email_cancellation' => !empty($input['email_cancellation']) ? '1' : '0',
            'sender_name' => sanitize_text_field($input['sender_name'] ?? ($old['sender_name'] ?? get_bloginfo('name'))),
            'sender_email' => sanitize_email($input['sender_email'] ?? ($old['sender_email'] ?? get_option('admin_email'))),
            'instructor_agreement_enabled' => !empty($input['instructor_agreement_enabled']) ? '1' : '0',
            'school_address' => sanitize_text_field($input['school_address'] ?? ($old['school_address'] ?? '751 Rockville Pike, Unit # 9B, Rockville, MD 20852')),
            'school_phone' => sanitize_text_field($input['school_phone'] ?? ($old['school_phone'] ?? '(202) 600-0889 / (301) 726-3030')),
            'instructor_default_hourly_wage' => sanitize_text_field($input['instructor_default_hourly_wage'] ?? ($old['instructor_default_hourly_wage'] ?? '35.00')),
            'instructor_agreement_text' => wp_kses_post($input['instructor_agreement_text'] ?? ($old['instructor_agreement_text'] ?? self::get_default_instructor_agreement())),
        );
    }

    public function text_field($args) {
        $settings = get_option(self::OPTION_KEY, array());
        $key = $args['key'];
        printf('<input class="regular-text" name="%1$s[%2$s]" type="%3$s" value="%4$s" style="width:100%%;max-width:460px;" />', esc_attr(self::OPTION_KEY), esc_attr($key), esc_attr($args['type']), esc_attr($settings[$key] ?? ''));
    }

    public function checkbox_field($args) {
        $settings = get_option(self::OPTION_KEY, array());
        $key = $args['key'];
        $val = $settings[$key] ?? '1';
        printf('<label><input name="%1$s[%2$s]" type="checkbox" value="1" %3$s /> %4$s</label>', esc_attr(self::OPTION_KEY), esc_attr($key), checked('1', $val, false), esc_html($args['label']));
    }

    public function textarea_field($args) {
        $settings = get_option(self::OPTION_KEY, array());
        $key = $args['key'];
        $rows = $args['rows'] ?? 12;
        $desc = $args['desc'] ?? '';
        $val = $settings[$key] ?? ($args['default'] ?? '');
        printf('<textarea class="large-text" name="%1$s[%2$s]" rows="%3$d" style="width:100%%;max-width:800px;font-family:monospace;font-size:12px;line-height:1.5;">%4$s</textarea>',
            esc_attr(self::OPTION_KEY),
            esc_attr($key),
            absint($rows),
            esc_textarea($val)
        );
        if ($desc) {
            echo '<p class="description" style="margin-top:6px;color:#64748b;">' . esc_html($desc) . '</p>';
        }
    }

    public function logo_field() {
        $settings = get_option(self::OPTION_KEY, array());
        $url = esc_url($settings['logo_url'] ?? '');
        echo '<input id="driveflow-logo-url" class="regular-text" name="' . esc_attr(self::OPTION_KEY) . '[logo_url]" type="url" value="' . esc_attr($url) . '" style="width:75%;max-width:380px;" />';
        echo ' <button type="button" class="button" id="driveflow-upload-logo">Choose / Upload Logo</button>';
        echo '<p><img id="driveflow-logo-preview" src="' . $url . '" alt="" style="max-width:180px;max-height:100px;margin-top:10px;border-radius:6px;background:#1e293b;padding:8px;' . ($url ? '' : 'display:none;') . '" /></p>';
    }

    public function api_key_field() {
        $settings = get_option(self::OPTION_KEY, array());
        $key = esc_attr($settings['tv_api_key'] ?? '');
        echo '<input class="regular-text" name="' . esc_attr(self::OPTION_KEY) . '[tv_api_key]" type="text" value="' . $key . '" readonly style="background:#f1f5f9;font-family:monospace;width:100%;max-width:460px;" />';
        echo '<p class="description">Used to authenticate the live lobby TV monitor board feed.</p>';
    }

    public function admin_assets($hook) {
        if (false === strpos($hook, 'driveflow-pro')) {
            return;
        }
        wp_enqueue_media();
        $admin_css = DRIVEFLOW_PRO_DIR . 'assets/admin.css';
        $admin_js  = DRIVEFLOW_PRO_DIR . 'assets/admin.js';
        $css_ver = DRIVEFLOW_PRO_VERSION . '.' . (file_exists($admin_css) ? filemtime($admin_css) : time());
        $js_ver  = DRIVEFLOW_PRO_VERSION . '.' . (file_exists($admin_js) ? filemtime($admin_js) : time());
        wp_enqueue_style('driveflow-admin-css', DRIVEFLOW_PRO_URL . 'assets/admin.css', array(), $css_ver);
        wp_enqueue_style('driveflow-responsive', DRIVEFLOW_PRO_URL . 'assets/responsive.css', array('driveflow-admin-css'), DRIVEFLOW_PRO_VERSION);
        wp_enqueue_script('driveflow-admin', DRIVEFLOW_PRO_URL . 'assets/admin.js', array('jquery'), $js_ver, true);
        wp_localize_script('driveflow-admin', 'DriveFlowAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('driveflow_admin_nonce'),
            'root' => esc_url_raw(rest_url(self::REST_NAMESPACE)),
            'restNonce' => wp_create_nonce('wp_rest'),
        ));
    }

    public function frontend_assets() {
        wp_enqueue_style('driveflow-pro-font', 'https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;600;700;800&display=swap', array(), null);
        $front_css = DRIVEFLOW_PRO_DIR . 'assets/frontend.css';
        $front_js  = DRIVEFLOW_PRO_DIR . 'assets/frontend.js';
        $css_ver = DRIVEFLOW_PRO_VERSION . '.' . (file_exists($front_css) ? filemtime($front_css) : time());
        $js_ver  = DRIVEFLOW_PRO_VERSION . '.' . (file_exists($front_js) ? filemtime($front_js) : time());
        wp_enqueue_style('driveflow-pro', DRIVEFLOW_PRO_URL . 'assets/frontend.css', array(), $css_ver);
        wp_enqueue_style('driveflow-responsive', DRIVEFLOW_PRO_URL . 'assets/responsive.css', array('driveflow-pro'), DRIVEFLOW_PRO_VERSION);
        wp_enqueue_script('driveflow-pro', DRIVEFLOW_PRO_URL . 'assets/frontend.js', array('jquery'), $js_ver, true);
        $settings = get_option(self::OPTION_KEY, array());
        wp_localize_script('driveflow-pro', 'DriveFlowPro', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'root' => esc_url_raw(rest_url(self::REST_NAMESPACE)),
            'nonce' => wp_create_nonce('wp_rest'),
            'pollSeconds' => max(5, absint($settings['api_poll_seconds'] ?? 6)),
            'tickerText' => sanitize_text_field($settings['announcement_ticker'] ?? ''),
        ));
    }

    public function settings_page() {
        if (!current_user_can('manage_options')) return;
        $settings = get_option(self::OPTION_KEY, array());
        $health = $this->health_check();
        $wapp_detected = DriveFlow_Wappointment_Sync::is_wappointment_active();
        ?>
        <div class="wrap driveflow-admin-wrap" dir="ltr">
            <div class="df-header-banner">
                <div class="df-header-title">
                    <div>
                        <h1>DriveFlow Pro · Settings & Integration</h1>
                        <p>Configure academy branding, automated email notifications, lobby TV board, and online booking sync.</p>
                    </div>
                </div>
            </div>

            <?php if (!empty($_GET['pages_ready'])) : ?>
                <div class="notice notice-success is-dismissible"><p>DriveFlow pages and shortcodes were checked and successfully configured.</p></div>
            <?php endif; ?>

            <div style="display:grid;grid-template-columns:2fr 1fr;gap:24px;">
                <!-- Main Settings Form -->
                <div style="background:#fff;padding:24px;border-radius:10px;border:1px solid #e2e8f0;box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);">
                    <form method="post" action="options.php">
                        <?php
                        settings_fields('driveflow_pro_settings_group');
                        do_settings_sections('driveflow-pro-settings');
                        submit_button('Save All Settings');
                        ?>
                    </form>
                </div>

                <!-- Integration & Health Sidecards -->
                <div style="display:flex;flex-direction:column;gap:20px;">
                    <!-- Email Test Box -->
                    <div style="background:#fff;padding:20px;border-radius:10px;border:1px solid #e2e8f0;box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);">
                        <h3 style="margin-top:0;display:flex;align-items:center;gap:8px;">
                            <span style="color:#0284c7;">✉️</span> Test Email Delivery
                        </h3>
                        <p style="font-size:13px;color:#64748b;margin-bottom:10px;">
                            Send a luxury branded HTML test email to verify your server SMTP configuration:
                        </p>
                        <input type="email" id="df-test-email-target" class="regular-text" placeholder="Recipient email address..." value="<?php echo esc_attr(get_option('admin_email')); ?>" style="width:100%;margin-bottom:10px;">
                        <button type="button" class="df-btn df-btn-primary df-btn-sm" id="df-send-test-email-btn">
                            Send Test Email
                        </button>
                    </div>

                    <!-- Wappointment Status Box -->
                    <div style="background:#fff;padding:20px;border-radius:10px;border:1px solid #e2e8f0;box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);">
                        <h3 style="margin-top:0;display:flex;align-items:center;gap:8px;">
                            <span style="color:#7e22ce;">⚡</span> Wappointment Sync Status
                        </h3>
                        <?php if ($wapp_detected) : ?>
                            <div style="background:#f3e8ff;color:#6b21a8;padding:12px;border-radius:6px;font-weight:600;margin-bottom:12px;">
                                ✓ Wappointment is Active & Connected!
                            </div>
                            <p style="font-size:13px;color:#64748b;">
                                Online student bookings are automatically transformed into driving sessions and streamed to the lobby TV and instructor app.
                            </p>
                            <button type="button" class="df-btn df-btn-wapp df-btn-sm" id="df-sync-wappointment-btn">
                                <span class="dashicons dashicons-update"></span> Run Manual Sync Now
                            </button>
                        <?php else : ?>
                            <div style="background:#f1f5f9;color:#475569;border:1px solid #cbd5e1;padding:12px;border-radius:6px;font-weight:600;margin-bottom:12px;">
                                ℹ️ Optional Add-on (Not Installed)
                            </div>
                            <p style="font-size:13px;color:#64748b;">
                                DriveFlow Pro functions 100% independently. If you wish to allow students to book driving slots online, install the free <a href="https://wordpress.org/plugins/wappointment/" target="_blank" style="font-weight:bold;color:#7e22ce;">Wappointment plugin</a> and DriveFlow will auto-sync appointments.
                            </p>
                        <?php endif; ?>
                    </div>

                    <!-- Quick Page Links -->
                    <div style="background:#fff;padding:20px;border-radius:10px;border:1px solid #e2e8f0;box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);">
                        <h3 style="margin-top:0;">Live System URLs</h3>
                        <p style="margin-bottom:8px;">
                            <strong>Instructor In-Car App:</strong><br>
                            <?php if (!empty($settings['instructor_page_id'])) : ?>
                                <a href="<?php echo esc_url(get_permalink($settings['instructor_page_id'])); ?>" target="_blank" class="button button-small">Open Instructor App ↗</a>
                            <?php else : ?>
                                <span style="color:#ef4444;">Not Created</span>
                            <?php endif; ?>
                        </p>
                        <p style="margin-bottom:8px;">
                            <strong>Lobby TV Display Board:</strong><br>
                            <?php if (!empty($settings['tv_page_id'])) : ?>
                                <a href="<?php echo esc_url(get_permalink($settings['tv_page_id'])); ?>" target="_blank" class="button button-small">Open TV Board ↗</a>
                            <?php else : ?>
                                <span style="color:#ef4444;">Not Created</span>
                            <?php endif; ?>
                        </p>
                        <p style="margin-bottom:8px;">
                            <strong>Student Self-Booking Portal:</strong><br>
                            <?php if (!empty($settings['magic_booking_page_id'])) : ?>
                                <a href="<?php echo esc_url(get_permalink($settings['magic_booking_page_id'])); ?>" target="_blank" class="button button-small">Open Booking Portal ↗</a>
                            <?php else : ?>
                                <span style="color:#ef4444;">Not Created</span>
                            <?php endif; ?>
                        </p>
                        <p style="margin-bottom:8px;">
                            <strong>Instructor Onboarding Portal:</strong><br>
                            <?php if (!empty($settings['instructor_onboarding_page_id'])) : ?>
                                <a href="<?php echo esc_url(get_permalink($settings['instructor_onboarding_page_id'])); ?>" target="_blank" class="button button-small">Open Onboarding ↗</a>
                            <?php else : ?>
                                <span style="color:#ef4444;">Not Created</span>
                            <?php endif; ?>
                        </p>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:16px;">
                            <input type="hidden" name="action" value="driveflow_provision_pages">
                            <?php wp_nonce_field('driveflow_provision_pages'); ?>
                            <button class="button button-secondary" type="submit">Verify & Auto-Provision Pages</button>
                        </form>
                    </div>

                    <!-- Health Check Table -->
                    <div style="background:#fff;padding:20px;border-radius:10px;border:1px solid #e2e8f0;box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);">
                        <h3 style="margin-top:0;">System Diagnostics</h3>
                        <table style="width:100%;font-size:13px;border-collapse:collapse;">
                            <?php foreach ($health as $label => $status_val) : ?>
                                <tr style="border-bottom:1px solid #f1f5f9;">
                                    <td style="padding:6px 0;"><?php echo esc_html($label); ?></td>
                                    <td style="padding:6px 0;text-align:right;">
                                        <?php 
                                        if (true === $status_val) {
                                            echo '<span style="color:#16a34a;font-weight:700;background:#f0fdf4;padding:2px 8px;border-radius:4px;border:1px solid #bbf7d0;">Ready (OK)</span>';
                                        } elseif ('optional' === $status_val) {
                                            echo '<span style="color:#0284c7;font-weight:600;background:#f0f9ff;padding:2px 8px;border-radius:4px;border:1px solid #bae6fd;">Optional (Add-on)</span>';
                                        } else {
                                            echo '<span style="color:#dc2626;font-weight:700;background:#fef2f2;padding:2px 8px;border-radius:4px;border:1px solid #fecaca;">Action Needed</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private function health_check() {
        global $wpdb;
        $settings = get_option(self::OPTION_KEY, array());
        $wapp_active = DriveFlow_Wappointment_Sync::is_wappointment_active();
        return array(
            'Sessions Database Table' => (bool) $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $this->table())),
            'Magic Tokens Database Table' => (bool) $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->prefix . 'driveflow_magic_tokens')),
            'Instructor In-Car App Page' => !empty($settings['instructor_page_id']) && 'publish' === get_post_status($settings['instructor_page_id']),
            'Lobby TV Board Page' => !empty($settings['tv_page_id']) && 'publish' === get_post_status($settings['tv_page_id']),
            'Student Booking Page' => !empty($settings['magic_booking_page_id']) && 'publish' === get_post_status($settings['magic_booking_page_id']),
            'Instructor Onboarding Page' => !empty($settings['instructor_onboarding_page_id']) && 'publish' === get_post_status($settings['instructor_onboarding_page_id']),
            'TV REST API Key' => !empty($settings['tv_api_key']),
            'Wappointment Booking (Optional)' => $wapp_active ? true : 'optional',
            'WordPress Mail Delivery (wp_mail)' => function_exists('wp_mail'),
            'WordPress REST API' => function_exists('rest_url'),
            'Frontend & Admin Assets' => is_readable(DRIVEFLOW_PRO_DIR . 'assets/frontend.js') && is_readable(DRIVEFLOW_PRO_DIR . 'assets/admin.css'),
        );
    }

    public function provision_pages_action() {
        if (!current_user_can('manage_options') || !check_admin_referer('driveflow_provision_pages')) wp_die('Unauthorized request.');
        self::provision_pages();
        wp_safe_redirect(admin_url('admin.php?page=driveflow-pro-settings&pages_ready=1')); exit;
    }

    public function admin_hub_page() {
        if (!current_user_can('manage_options')) return;
        global $wpdb;
        $settings = get_option(self::OPTION_KEY, array());

        $table_sessions    = $this->table();
        $table_students    = $wpdb->prefix . 'driveflow_students';
        $table_instructors = $wpdb->prefix . 'driveflow_instructors';
        $table_vehicles    = $wpdb->prefix . 'driveflow_vehicles';
        $table_magic       = $wpdb->prefix . 'driveflow_magic_tokens';

        $session_stats = $wpdb->get_row("SELECT 
            COUNT(*) as total,
            SUM(status = 'upcoming') as upcoming,
            SUM(status = 'active') as active,
            SUM(status = 'completed') as completed,
            SUM(status = 'cancelled') as cancelled
        FROM {$table_sessions}", ARRAY_A);

        $student_stats = $wpdb->get_row("SELECT 
            COUNT(*) as total,
            SUM(status = 'active') as active,
            SUM(total_sessions) as total_credits,
            SUM(completed_sessions) as completed_credits
        FROM {$table_students}", ARRAY_A);

        $instructor_stats = $wpdb->get_row("SELECT 
            COUNT(*) as total,
            SUM(status = 'active') as active,
            SUM(agreement_signed = 1) as signed_agreements
        FROM {$table_instructors}", ARRAY_A);

        $vehicle_stats = $wpdb->get_row("SELECT 
            COUNT(*) as total,
            SUM(status = 'active') as active
        FROM {$table_vehicles}", ARRAY_A);

        $magic_stats = $wpdb->get_row("SELECT 
            COUNT(*) as total,
            SUM(status = 'active') as active
        FROM {$table_magic}", ARRAY_A);

        $upcoming_sessions = $wpdb->get_results("SELECT id, student_name, instructor_name, plate_number, scheduled_start, scheduled_end, status 
            FROM {$table_sessions} 
            WHERE scheduled_start >= NOW() - INTERVAL 1 HOUR 
            ORDER BY scheduled_start ASC LIMIT 6", ARRAY_A);

        $students_list = $wpdb->get_results("SELECT id, name, email, completed_sessions, total_sessions FROM {$table_students} WHERE status = 'active' ORDER BY name ASC LIMIT 100", ARRAY_A);
        $instructors_list = $wpdb->get_results("SELECT id, name, email FROM {$table_instructors} WHERE status = 'active' ORDER BY name ASC LIMIT 50", ARRAY_A);

        $health = $this->health_check();
        $wapp_detected = DriveFlow_Wappointment_Sync::is_wappointment_active();

        $portals = array(
            'tv' => array(
                'title'     => 'Lobby TV Display Board',
                'desc'      => 'Live in-office presentation board displaying current & upcoming driving lessons with authentic Maryland crab license plates.',
                'icon'      => '🖥️',
                'page_id'   => $settings['tv_page_id'] ?? 0,
                'shortcode' => '[driveflow_tv_board]',
                'url'       => !empty($settings['tv_page_id']) ? get_permalink($settings['tv_page_id']) : site_url('/driveflow-tv-board/'),
            ),
            'instructor_app' => array(
                'title'     => 'Instructor In-Car Session App',
                'desc'      => 'Mobile-optimized in-car cockpit for certified instructors: student selfie verification, digital signature, and MVA skill ratings.',
                'icon'      => '🚗',
                'page_id'   => $settings['instructor_page_id'] ?? 0,
                'shortcode' => '[driveflow_instructor_form]',
                'url'       => !empty($settings['instructor_page_id']) ? get_permalink($settings['instructor_page_id']) : site_url('/instructor-session/'),
            ),
            'student_booking' => array(
                'title'     => 'Student Self-Booking Portal',
                'desc'      => 'Allows students to redeem credit hours, view certified instructor availability in real-time, and schedule behind-the-wheel lessons.',
                'icon'      => '📅',
                'page_id'   => $settings['magic_booking_page_id'] ?? 0,
                'shortcode' => '[driveflow_magic_booking]',
                'url'       => !empty($settings['magic_booking_page_id']) ? get_permalink($settings['magic_booking_page_id']) : site_url('/book-driving-lesson/'),
            ),
            'student_portal' => array(
                'title'     => 'Student Account & My Bookings',
                'desc'      => 'Student self-service portal displaying enrolled driving packages, remaining balance hours, upcoming bookings, and completed scorecards.',
                'icon'      => '🎓',
                'page_id'   => $settings['student_portal_page_id'] ?? 0,
                'shortcode' => '[driveflow_student_portal]',
                'url'       => !empty($settings['student_portal_page_id']) ? get_permalink($settings['student_portal_page_id']) : site_url('/my-bookings/'),
            ),
            'onboarding' => array(
                'title'     => 'Instructor Onboarding & Contract Portal',
                'desc'      => 'Secure onboarding gateway for new instructors: personal bio, MVA license number, ID card photo, badge tag, and digital contract signing.',
                'icon'      => '✍️',
                'page_id'   => $settings['instructor_onboarding_page_id'] ?? 0,
                'shortcode' => '[driveflow_instructor_onboarding]',
                'url'       => !empty($settings['instructor_onboarding_page_id']) ? get_permalink($settings['instructor_onboarding_page_id']) : site_url('/instructor-onboarding/'),
            ),
        );

        include DRIVEFLOW_PRO_DIR . 'templates/admin-hub.php';
    }

    public function records_page() {
        if (!current_user_can('manage_options')) return;
        global $wpdb;
        $search = sanitize_text_field(wp_unslash($_GET['s'] ?? ''));
        $status_filter = sanitize_key($_GET['status'] ?? '');
        $date_filter = sanitize_key($_GET['date_filter'] ?? '');

        $where_clauses = array();
        $args = array();

        if ($search) {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where_clauses[] = "(student_name LIKE %s OR instructor_name LIKE %s OR lesson_topic LIKE %s OR plate_number LIKE %s)";
            $args[] = $like; $args[] = $like; $args[] = $like; $args[] = $like;
        }

        if ($status_filter && in_array($status_filter, array('upcoming', 'active', 'completed', 'cancelled'), true)) {
            $where_clauses[] = "status = %s";
            $args[] = $status_filter;
        }

        if ('today' === $date_filter) {
            $where_clauses[] = "DATE(scheduled_start) = CURDATE()";
        } elseif ('this_week' === $date_filter) {
            $where_clauses[] = "YEARWEEK(scheduled_start, 1) = YEARWEEK(CURDATE(), 1)";
        }

        $where_sql = !empty($where_clauses) ? ' WHERE ' . implode(' AND ', $where_clauses) : '';

        $paged = max(1, absint($_GET['paged'] ?? 1));
        $per_page = max(5, min(100, absint($_GET['per_page'] ?? 15)));
        $offset = ($paged - 1) * $per_page;

        $count_query = "SELECT COUNT(*) FROM {$this->table()}{$where_sql}";
        $total_rows = (int) ($args ? $wpdb->get_var($wpdb->prepare($count_query, $args)) : $wpdb->get_var($count_query));
        $total_pages = max(1, ceil($total_rows / $per_page));

        $query = "SELECT id, student_name, instructor_name, plate_number, session_number, lesson_topic, scheduled_start, scheduled_end, status, selfie, signature, form_data, wappointment_id FROM {$this->table()}{$where_sql} ORDER BY scheduled_start DESC LIMIT {$offset}, {$per_page}";
        $rows = $args ? $wpdb->get_results($wpdb->prepare($query, $args), ARRAY_A) : $wpdb->get_results($query, ARRAY_A);

        include DRIVEFLOW_PRO_DIR . 'templates/admin-records.php';
    }

    public function people_page() {
        $slug = sanitize_key($_GET['page'] ?? 'driveflow-pro-students');
        $entity = ('driveflow-pro-instructors' === $slug) ? 'instructors' : 'students';
        $config = ('students' === $entity)
            ? array('Full Name' => 'name', 'Phone Number' => 'phone', 'Email Address' => 'email', 'Driver Permit / License' => 'license_number')
            : array('Instructor Name' => 'name', 'Phone Number' => 'phone', 'Email Address' => 'email', 'Instructor License #' => 'license_number', 'Credentials & Tags' => 'verification_docs');
        $this->render_entity_page(('instructors' === $entity ? 'Instructors' : 'Students'), 'driveflow_' . $entity, $config, $entity);
    }

    public function vehicles_page() {
        $this->render_entity_page('Training Vehicles & Fleet', 'driveflow_vehicles', array('Plate Number' => 'plate_number', 'Vehicle Model' => 'model', 'Color' => 'color'), 'vehicles');
    }

    private function render_entity_page($title, $table, $fields, $entity) {
        if (!current_user_can('manage_options')) return;
        global $wpdb;
        $search = sanitize_text_field(wp_unslash($_GET['s'] ?? ''));
        $search_like = '%' . $wpdb->esc_like($search) . '%';
        $where = $search
            ? ('vehicles' === $entity
                ? $wpdb->prepare(' WHERE plate_number LIKE %s OR model LIKE %s OR color LIKE %s', $search_like, $search_like, $search_like)
                : $wpdb->prepare(' WHERE name LIKE %s OR phone LIKE %s OR email LIKE %s', $search_like, $search_like, $search_like))
            : '';

        $paged = max(1, absint($_GET['paged'] ?? 1));
        $per_page = max(5, min(100, absint($_GET['per_page'] ?? 15)));
        $offset = ($paged - 1) * $per_page;

        $count_sql = "SELECT COUNT(*) FROM {$wpdb->prefix}{$table}{$where}";
        $total_rows = (int) $wpdb->get_var($count_sql);
        $total_pages = max(1, ceil($total_rows / $per_page));

        $rows = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}{$table}{$where} ORDER BY id DESC LIMIT {$offset}, {$per_page}", ARRAY_A);

        if ('instructors' === $entity && !empty($rows)) {
            $sess_table = $this->table();
            $payout_table = $wpdb->prefix . 'driveflow_instructor_payouts';
            $settings = get_option(self::OPTION_KEY, array());
            $default_rate = floatval($settings['instructor_default_hourly_wage'] ?? 35.00);

            foreach ($rows as &$ins_row) {
                $ins_id = (int)$ins_row['id'];
                $ins_name = $ins_row['name'];
                $ins_rate = (!empty($ins_row['hourly_wage']) && floatval($ins_row['hourly_wage']) > 0) ? floatval($ins_row['hourly_wage']) : $default_rate;
                $ins_row['hourly_wage'] = $ins_rate;
                $ins_row['payment_method'] = $ins_row['payment_method'] ?: 'zelle';
                $ins_row['payment_details'] = $ins_row['payment_details'] ?: '';

                // Calculate completed driving session hours
                $sess_stat = $wpdb->get_row($wpdb->prepare(
                    "SELECT COUNT(*) as comp_count, 
                            COALESCE(SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, scheduled_start, scheduled_end) > 0 THEN TIMESTAMPDIFF(MINUTE, scheduled_start, scheduled_end)/60.0 ELSE 2.0 END), 0) as total_hours 
                     FROM {$sess_table} 
                     WHERE (instructor_id = %d OR instructor_name = %s) AND status = 'completed'",
                    $ins_id, $ins_name
                ), ARRAY_A);

                $hours_driven = round(floatval($sess_stat['total_hours'] ?? 0), 2);
                $comp_count = (int)($sess_stat['comp_count'] ?? 0);
                $gross_earned = round($hours_driven * $ins_rate, 2);

                // Query disbursed payments
                $paid_sum = floatval($wpdb->get_var($wpdb->prepare(
                    "SELECT COALESCE(SUM(amount), 0) FROM {$payout_table} WHERE instructor_id = %d AND status IN ('paid', 'archived')",
                    $ins_id
                )));
                $balance_due = max(0, round($gross_earned - $paid_sum, 2));

                $ins_row['comp_sessions_count'] = $comp_count;
                $ins_row['hours_driven']        = $hours_driven;
                $ins_row['gross_earned']         = $gross_earned;
                $ins_row['total_paid']           = $paid_sum;
                $ins_row['balance_due']          = $balance_due;
            }
            unset($ins_row);
        }

        include DRIVEFLOW_PRO_DIR . 'templates/admin-entity.php';
    }

    public function save_entity() {
        if (!current_user_can('manage_options') || !check_admin_referer('driveflow_save_entity')) wp_die('Unauthorized request.');
        global $wpdb;
        $entity = sanitize_key($_POST['entity'] ?? '');
        $allowed = array('students' => 'students', 'instructors' => 'instructors', 'vehicles' => 'vehicles');
        if (!isset($allowed[$entity])) wp_die('Invalid entity.');
        $now = current_time('mysql');
        $data = array('created_at' => $now, 'updated_at' => $now, 'status' => 'active');
        if ('vehicles' === $entity) {
            $raw_plate = sanitize_text_field(wp_unslash($_POST['plate_number'] ?? ''));
            $clean_plate = strtoupper(preg_replace('/[^A-Za-z0-9\s\-]/', '', $raw_plate));
            $data['plate_number'] = $clean_plate;
            $data['model'] = sanitize_text_field(wp_unslash($_POST['model'] ?? ''));
            $data['color'] = sanitize_text_field(wp_unslash($_POST['color'] ?? ''));
        } else {
            $data['name'] = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
            $data['phone'] = sanitize_text_field(wp_unslash($_POST['phone'] ?? ''));
            $data['email'] = sanitize_email(wp_unslash($_POST['email'] ?? ''));
            $data['license_number'] = sanitize_text_field(wp_unslash($_POST['license_number'] ?? ''));

            if ('instructors' === $entity) {
                $settings = get_option(self::OPTION_KEY, array());
                $default_rate = floatval($settings['instructor_default_hourly_wage'] ?? 35.00);
                $wage = (!empty($_POST['hourly_wage']) && floatval($_POST['hourly_wage']) > 0) ? floatval($_POST['hourly_wage']) : $default_rate;
                $data['hourly_wage'] = $wage;
                $data['agreement_wage'] = number_format($wage, 2);
                $data['payment_method'] = sanitize_key($_POST['payment_method'] ?? 'zelle');
                $data['payment_details'] = sanitize_textarea_field(wp_unslash($_POST['payment_details'] ?? ''));
            }
        }
        if (!empty($data['name']) || !empty($data['plate_number'])) {
            $wpdb->insert($wpdb->prefix . 'driveflow_' . $allowed[$entity], $data, array_fill(0, count($data), '%s'));
        }
        wp_safe_redirect(admin_url('admin.php?page=driveflow-pro-' . $entity . '&added=1')); exit;
    }

    public function add_admin_session() {
        if (!current_user_can('manage_options') || !check_admin_referer('driveflow_add_session')) wp_die('Unauthorized request.');
        global $wpdb;
        $now = current_time('mysql');
        $student = sanitize_text_field(wp_unslash($_POST['student_name'] ?? ''));
        $instructor = sanitize_text_field(wp_unslash($_POST['instructor_name'] ?? ''));
        $raw_plate = sanitize_text_field(wp_unslash($_POST['plate_number'] ?? ''));
        $plate = strtoupper(preg_replace('/[^A-Za-z0-9\s\-]/', '', $raw_plate));
        $start = $this->normalize_datetime(wp_unslash($_POST['scheduled_start'] ?? ''), $now);
        $end = $this->normalize_datetime(wp_unslash($_POST['scheduled_end'] ?? ''), $now);

        if ($student) {
            $status = in_array($_POST['status'] ?? '', array('upcoming','active','completed','cancelled'), true) ? sanitize_key($_POST['status']) : 'upcoming';

            // Find matching student_id if available
            $st_id = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}driveflow_students WHERE name = %s OR phone = %s", $student, $student));

            $wpdb->insert($this->table(), array(
                'student_name' => $student,
                'student_id' => $st_id,
                'instructor_name' => $instructor,
                'plate_number' => $plate,
                'session_number' => max(1, absint($_POST['session_number'] ?? 1)),
                'lesson_topic' => sanitize_text_field(wp_unslash($_POST['lesson_topic'] ?? '')),
                'scheduled_start' => $start,
                'scheduled_end' => $end,
                'status' => $status,
                'form_data' => wp_json_encode(array('source' => 'admin_modal')),
                'signature' => '',
                'selfie' => '',
                'created_by' => get_current_user_id(),
                'created_at' => $now,
                'updated_at' => $now,
            ), array('%s','%d','%s','%s','%d','%s','%s','%s','%s','%s','%s','%s','%d','%s','%s'));

            $new_id = $wpdb->insert_id;
            if ($new_id) {
                // Two-way sync: push newly scheduled session to Wappointment
                DriveFlow_Wappointment_Sync::push_session_to_wapp($new_id);

                if ('completed' === $status && $st_id) {
                    DriveFlow_Credit_Manager::deduct_session_credit($st_id, $new_id);
                }
                if ('upcoming' === $status) {
                    DriveFlow_Email_Manager::send_student_booking($new_id);
                    DriveFlow_Email_Manager::send_instructor_booking($new_id);
                }
            }
        }
        wp_safe_redirect(admin_url('admin.php?page=driveflow-pro-records&added=1')); exit;
    }

    public function students_page() {
        if (!current_user_can('manage_options')) return;
        include DRIVEFLOW_PRO_DIR . 'templates/admin-students.php';
    }

    public function save_student_action() {
        if (!current_user_can('manage_options') || !check_admin_referer('driveflow_save_student')) wp_die('Unauthorized request.');
        global $wpdb;
        $now = current_time('mysql');
        $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
        $phone = sanitize_text_field(wp_unslash($_POST['phone'] ?? ''));
        $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
        $license = sanitize_text_field(wp_unslash($_POST['license_number'] ?? ''));
        $package_name = sanitize_text_field(wp_unslash($_POST['package_name'] ?? 'Standard 10-Lesson Course'));
        $total = max(1, absint($_POST['total_sessions'] ?? 10));

        if ($name) {
            $wpdb->insert($wpdb->prefix . 'driveflow_students', array(
                'name' => $name,
                'phone' => $phone,
                'email' => $email,
                'license_number' => $license,
                'package_name' => $package_name,
                'total_sessions' => $total,
                'completed_sessions' => 0,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ), array('%s','%s','%s','%s','%s','%d','%d','%s','%s','%s'));
            $student_id = $wpdb->insert_id;

            if ($student_id) {
                $wpdb->insert($wpdb->prefix . 'driveflow_student_credits', array(
                    'student_id' => $student_id,
                    'action_type' => 'package_purchase',
                    'credits_delta' => $total,
                    'notes' => 'Initial Course Package: ' . $package_name,
                    'created_by' => get_current_user_id(),
                    'created_at' => $now,
                ), array('%d','%s','%d','%s','%d','%s'));
            }
        }
        wp_safe_redirect(admin_url('admin.php?page=driveflow-pro-students&added=1')); exit;
    }

    public function ajax_check_collision() {
        check_ajax_referer('driveflow_admin_nonce', 'nonce');
        $instructor = sanitize_text_field(wp_unslash($_POST['instructor_name'] ?? ''));
        $plate = sanitize_text_field(wp_unslash($_POST['plate_number'] ?? ''));
        $start = sanitize_text_field(wp_unslash($_POST['scheduled_start'] ?? ''));
        $end = sanitize_text_field(wp_unslash($_POST['scheduled_end'] ?? ''));
        $exclude_id = absint($_POST['exclude_id'] ?? 0);

        $now = current_time('mysql');
        $start_norm = $this->normalize_datetime($start, $now);
        $end_norm = $this->normalize_datetime($end, $now);

        $check = DriveFlow_Availability_Engine::check_collision($instructor, $plate, $start_norm, $end_norm, $exclude_id);
        $busy_vehicles = DriveFlow_Availability_Engine::get_busy_vehicles($start_norm, $end_norm, $exclude_id);

        wp_send_json_success(array(
            'conflict' => $check['conflict'],
            'type' => $check['type'] ?? '',
            'reason' => $check['reason'] ?? ($check['message'] ?? ''),
            'busy_vehicles' => $busy_vehicles,
        ));
    }

    public function ajax_get_busy_vehicles() {
        check_ajax_referer('driveflow_admin_nonce', 'nonce');
        $start = sanitize_text_field(wp_unslash($_POST['scheduled_start'] ?? ''));
        $end = sanitize_text_field(wp_unslash($_POST['scheduled_end'] ?? ''));
        $now = current_time('mysql');
        $start_norm = $this->normalize_datetime($start, $now);
        $end_norm = $this->normalize_datetime($end, $now);

        $busy = DriveFlow_Availability_Engine::get_busy_vehicles($start_norm, $end_norm);
        wp_send_json_success(array('busy_vehicles' => $busy));
    }

    public function ajax_get_instructor_slots() {
        check_ajax_referer('driveflow_admin_nonce', 'nonce');
        $instructor = sanitize_text_field(wp_unslash($_POST['instructor'] ?? ''));
        $date = sanitize_text_field(wp_unslash($_POST['date'] ?? current_time('Y-m-d')));
        $result = DriveFlow_Availability_Engine::get_instructor_free_slots($instructor, $date);
        wp_send_json_success($result);
    }

    public function ajax_add_extra_sessions() {
        check_ajax_referer('driveflow_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized.');
        $student_id = absint($_POST['student_id'] ?? 0);
        $amount = absint($_POST['amount'] ?? 1);
        $notes = sanitize_text_field(wp_unslash($_POST['notes'] ?? ''));

        $res = DriveFlow_Credit_Manager::add_extra_sessions($student_id, $amount, $notes);
        if ($res) {
            $profile = DriveFlow_Credit_Manager::get_student_profile($student_id);
            wp_send_json_success(array(
                'message' => sprintf('Successfully added +%d extra lessons for %s!', $amount, esc_html($profile['name'])),
                'profile' => $profile
            ));
        } else {
            wp_send_json_error('Failed to add extra sessions.');
        }
    }

    public function ajax_get_student_profile() {
        check_ajax_referer('driveflow_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized.');
        $student_id = absint($_POST['student_id'] ?? 0);
        $profile = DriveFlow_Credit_Manager::get_student_profile($student_id);
        if ($profile) {
            wp_send_json_success($profile);
        } else {
            wp_send_json_error('Student profile not found.');
        }
    }

    public function student_portal_shortcode($atts) {
        static $rendered = false;
        if ($rendered) return '';
        $rendered = true;

        global $wpdb;
        $settings = get_option(self::OPTION_KEY, array());
        $brand = array(
            'name' => $settings['school_name'] ?? get_bloginfo('name'),
            'logo' => $settings['logo_url'] ?? '',
        );

        $lookup = sanitize_text_field(wp_unslash($_GET['df_student_lookup'] ?? ''));
        if (!$lookup && is_user_logged_in()) {
            $user = wp_get_current_user();
            $lookup = $user->user_email;
        }

        $student_data = null;
        if ($lookup) {
            $student_data = DriveFlow_Credit_Manager::get_student_profile($lookup);
        }

        ob_start();
        include DRIVEFLOW_PRO_DIR . 'templates/student-portal.php';
        return ob_get_clean();
    }

    public function calendar_page() {
        if (!current_user_can('manage_options')) return;
        global $wpdb;
        $instructors = $wpdb->get_results("SELECT id, name, phone FROM {$wpdb->prefix}driveflow_instructors WHERE status = 'active' ORDER BY name ASC", ARRAY_A);
        $vehicles = $wpdb->get_results("SELECT id, plate_number, model FROM {$wpdb->prefix}driveflow_vehicles WHERE status = 'active' ORDER BY plate_number ASC", ARRAY_A);
        $students = $wpdb->get_results("SELECT id, name, phone, email, total_sessions, completed_sessions, (total_sessions - completed_sessions) as remaining_sessions FROM {$wpdb->prefix}driveflow_students WHERE status = 'active' ORDER BY name ASC", ARRAY_A);
        include DRIVEFLOW_PRO_DIR . 'templates/admin-calendar.php';
    }

    public function magic_booking_shortcode($atts) {
        static $rendered = false;
        if ($rendered) return '';
        $rendered = true;

        $token = sanitize_text_field(wp_unslash($_GET['token'] ?? ''));
        $student_data = null;
        if (!empty($token)) {
            $student_data = DriveFlow_Credit_Manager::get_student_by_token($token);
        } elseif (is_user_logged_in()) {
            $u = wp_get_current_user();
            $student_data = DriveFlow_Credit_Manager::get_student_profile($u->user_email);
        }

        ob_start();
        include DRIVEFLOW_PRO_DIR . 'templates/student-booking.php';
        return ob_get_clean();
    }

    public function ajax_calendar_events() {
        check_ajax_referer('driveflow_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized.');

        global $wpdb;
        $start_date = sanitize_text_field(wp_unslash($_POST['start_date'] ?? ''));
        $end_date = sanitize_text_field(wp_unslash($_POST['end_date'] ?? ''));
        $instructor = sanitize_text_field(wp_unslash($_POST['instructor'] ?? ''));
        $plate = sanitize_text_field(wp_unslash($_POST['plate'] ?? ''));

        if (!$start_date) $start_date = date('Y-m-d 00:00:00', strtotime('-7 days'));
        else $start_date = date('Y-m-d 00:00:00', strtotime($start_date));

        if (!$end_date) $end_date = date('Y-m-d 23:59:59', strtotime('+14 days'));
        else $end_date = date('Y-m-d 23:59:59', strtotime($end_date));

        // 1. Fetch Sessions
        $where_sessions = "scheduled_start >= %s AND scheduled_start <= %s AND status != 'cancelled'";
        $params_sessions = array($start_date, $end_date);

        if (!empty($instructor)) {
            $where_sessions .= " AND instructor_name = %s";
            $params_sessions[] = $instructor;
        }
        if (!empty($plate)) {
            $where_sessions .= " AND plate_number = %s";
            $params_sessions[] = $plate;
        }

        $sessions = $wpdb->get_results($wpdb->prepare(
            "SELECT id, student_name, student_id, instructor_name, plate_number, session_number, lesson_topic, scheduled_start, scheduled_end, status 
             FROM {$this->table()} 
             WHERE {$where_sessions} 
             ORDER BY scheduled_start ASC",
            $params_sessions
        ), ARRAY_A);

        // 2. Fetch Blocked Slots
        $table_blocked = $wpdb->prefix . 'driveflow_blocked_slots';
        $blocked = array();
        $blocked_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_blocked));
        if ($blocked_exists) {
            $where_blk = "start_time <= %s AND end_time >= %s";
            $params_blk = array($end_date, $start_date);

            if (!empty($instructor)) {
                $where_blk .= " AND (target_type = 'all' OR (target_type = 'instructor' AND target_identifier = %s))";
                $params_blk[] = $instructor;
            } elseif (!empty($plate)) {
                $where_blk .= " AND (target_type = 'all' OR (target_type = 'vehicle' AND target_identifier = %s))";
                $params_blk[] = $plate;
            }

            $blocked = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$table_blocked} WHERE {$where_blk} ORDER BY start_time ASC",
                $params_blk
            ), ARRAY_A);
        }

        $vehicles = $wpdb->get_results("SELECT plate_number, model, color, status FROM {$wpdb->prefix}driveflow_vehicles WHERE status = 'active' ORDER BY plate_number ASC", ARRAY_A);
        $instructors = $wpdb->get_results("SELECT id, name, phone, license_number FROM {$wpdb->prefix}driveflow_instructors WHERE status = 'active' ORDER BY name ASC", ARRAY_A);

        wp_send_json_success(array(
            'sessions'    => $sessions,
            'blocked'     => $blocked,
            'vehicles'    => $vehicles ?: array(),
            'instructors' => $instructors ?: array(),
            'start_date'  => $start_date,
            'end_date'    => $end_date,
        ));
    }

    /** Calendar drag & drop: assign a student, instructor or vehicle to an existing session (admin only). */
    public function ajax_assign_to_session() {
        check_ajax_referer('driveflow_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized.'), 403);
        }

        global $wpdb;
        $session_id = absint($_POST['session_id'] ?? 0);
        $type       = sanitize_key($_POST['assign_type'] ?? '');
        $entity_id  = absint($_POST['entity_id'] ?? 0);
        if (!$session_id || !$entity_id || !in_array($type, array('student', 'instructor', 'vehicle'), true)) {
            wp_send_json_error(array('message' => 'Missing or invalid assignment parameters.'));
        }

        $session = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table()} WHERE id = %d", $session_id), ARRAY_A);
        if (!$session) {
            wp_send_json_error(array('message' => 'Session not found.'));
        }
        if (!in_array($session['status'], array('upcoming', 'active'), true)) {
            wp_send_json_error(array('message' => 'Only upcoming or active sessions can be changed.'));
        }

        $instructor = $session['instructor_name'];
        $plate      = $session['plate_number'];
        $update     = array('updated_at' => current_time('mysql'));
        $label      = '';

        if ('student' === $type) {
            $row = $wpdb->get_row($wpdb->prepare("SELECT id, name FROM {$wpdb->prefix}driveflow_students WHERE id = %d", $entity_id), ARRAY_A);
            if (!$row) {
                wp_send_json_error(array('message' => 'Student not found.'));
            }
            $update['student_id']   = (int) $row['id'];
            $update['student_name'] = $row['name'];
            $label = 'Student ' . $row['name'];
        } elseif ('instructor' === $type) {
            $row = $wpdb->get_row($wpdb->prepare("SELECT id, name FROM {$wpdb->prefix}driveflow_instructors WHERE id = %d AND status = 'active'", $entity_id), ARRAY_A);
            if (!$row) {
                wp_send_json_error(array('message' => 'Instructor not found or inactive.'));
            }
            $instructor = $row['name'];
            $update['instructor_name'] = $row['name'];
            $label = 'Instructor ' . $row['name'];
        } else {
            $row = $wpdb->get_row($wpdb->prepare("SELECT id, plate_number FROM {$wpdb->prefix}driveflow_vehicles WHERE id = %d AND status = 'active'", $entity_id), ARRAY_A);
            if (!$row) {
                wp_send_json_error(array('message' => 'Vehicle not found or inactive.'));
            }
            $plate = $row['plate_number'];
            $update['plate_number'] = $row['plate_number'];
            $update['vehicle_id']   = (int) $row['id'];
            $label = 'Vehicle ' . $row['plate_number'];
        }

        // Instructor / vehicle changes must not create double-bookings (this session is excluded from the check).
        if ('student' !== $type) {
            $check = DriveFlow_Availability_Engine::check_collision($instructor, $plate, $session['scheduled_start'], $session['scheduled_end'], $session_id);
            if (!empty($check['conflict'])) {
                wp_send_json_error(array('message' => $check['reason']));
            }
        }

        $wpdb->update($this->table(), $update, array('id' => $session_id));
        DriveFlow_Wappointment_Sync::push_session_to_wapp($session_id);
        self::notify_calendar_change();

        wp_send_json_success(array('message' => $label . ' assigned to session #' . $session_id . '.'));
    }

    public function ajax_reschedule_session() {
        check_ajax_referer('driveflow_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized.');

        global $wpdb;
        $session_id = absint($_POST['session_id'] ?? 0);
        $new_start = sanitize_text_field(wp_unslash($_POST['new_start'] ?? ''));
        $new_end = sanitize_text_field(wp_unslash($_POST['new_end'] ?? ''));
        $new_instructor = sanitize_text_field(wp_unslash($_POST['instructor_name'] ?? ''));

        if (!$session_id || !$new_start || !$new_end) {
            wp_send_json_error(array('message' => 'Missing session ID or time parameters.'));
        }

        $session = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table()} WHERE id = %d", $session_id), ARRAY_A);
        if (!$session) {
            wp_send_json_error(array('message' => 'Session not found.'));
        }

        $instructor = !empty($new_instructor) ? $new_instructor : $session['instructor_name'];
        $plate = $session['plate_number'];

        // Collision detection (excluding this session itself)
        $check = DriveFlow_Availability_Engine::check_collision($instructor, $plate, $new_start, $new_end, $session_id);
        if ($check['conflict']) {
            wp_send_json_error(array('message' => $check['reason']));
        }

        $update_data = array(
            'scheduled_start' => $new_start,
            'scheduled_end'   => $new_end,
            'updated_at'      => current_time('mysql'),
        );
        $update_format = array('%s', '%s', '%s');

        if (!empty($new_instructor) && $new_instructor !== $session['instructor_name']) {
            $update_data['instructor_name'] = $new_instructor;
            $update_format[] = '%s';
        }

        $wpdb->update($this->table(), $update_data, array('id' => $session_id), $update_format, array('%d'));

        // Two-way sync: reflect rescheduled time in Wappointment
        DriveFlow_Wappointment_Sync::push_session_to_wapp($session_id);
        self::notify_calendar_change();

        wp_send_json_success(array(
            'message' => sprintf('Lesson #%d rescheduled to %s (%s - %s)', $session['session_number'], date_i18n('l, M j', strtotime($new_start)), date_i18n('g:i A', strtotime($new_start)), date_i18n('g:i A', strtotime($new_end))),
            'session_id' => $session_id,
            'new_start' => $new_start,
            'new_end' => $new_end,
            'instructor' => $instructor,
        ));
    }

    public function ajax_add_blocked_slot() {
        check_ajax_referer('driveflow_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized.');

        $target_type = sanitize_text_field(wp_unslash($_POST['target_type'] ?? 'all'));
        $target_identifier = sanitize_text_field(wp_unslash($_POST['target_identifier'] ?? ''));
        $start_time = sanitize_text_field(wp_unslash($_POST['start_time'] ?? ''));
        $end_time = sanitize_text_field(wp_unslash($_POST['end_time'] ?? ''));
        $reason = sanitize_text_field(wp_unslash($_POST['reason'] ?? ''));

        if (!$start_time || !$end_time) {
            wp_send_json_error(array('message' => 'Start and end times are required.'));
        }

        $slot_id = DriveFlow_Availability_Engine::add_blocked_slot($target_type, $target_identifier, $start_time, $end_time, $reason);
        if ($slot_id) {
            self::notify_calendar_change();
            wp_send_json_success(array('message' => 'Time slot successfully blocked / disabled!', 'slot_id' => $slot_id));
        } else {
            wp_send_json_error(array('message' => 'Failed to save blocked time slot.'));
        }
    }

    public function ajax_delete_blocked_slot() {
        check_ajax_referer('driveflow_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized.');

        $slot_id = absint($_POST['slot_id'] ?? 0);
        if (!$slot_id) {
            wp_send_json_error(array('message' => 'Invalid slot ID.'));
        }

        DriveFlow_Availability_Engine::delete_blocked_slot($slot_id);
        self::notify_calendar_change();
        wp_send_json_success(array('message' => 'Blocked slot removed successfully.'));
    }

    public function ajax_send_booking_link() {
        check_ajax_referer('driveflow_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized.');

        $student_id = absint($_POST['student_id'] ?? 0);
        $profile = DriveFlow_Credit_Manager::get_student_profile($student_id);
        if (!$profile) {
            wp_send_json_error('Student profile not found.');
        }

        if (empty($profile['email'])) {
            wp_send_json_error('Student does not have an email address on file.');
        }

        $sent = DriveFlow_Email_Manager::send_magic_booking_link($student_id);
        if ($sent) {
            wp_send_json_success(array(
                'message' => 'Private booking link sent to ' . esc_html($profile['email']),
                'magic_link' => $profile['magic_link']
            ));
        } else {
            wp_send_json_error('Email could not be delivered. Please verify WordPress mail settings or copy the link manually.');
        }
    }

    public function ajax_get_magic_slots() {
        if (!self::throttle('magic_slots', 60, 10 * MINUTE_IN_SECONDS)) {
            wp_send_json_error(array('message' => 'Too many requests. Please try again later.'), 429);
        }
        $token = sanitize_text_field(wp_unslash($_POST['token'] ?? ''));
        $date = sanitize_text_field(wp_unslash($_POST['date'] ?? current_time('Y-m-d')));
        $instructor = sanitize_text_field(wp_unslash($_POST['instructor'] ?? ''));

        $profile = DriveFlow_Credit_Manager::get_student_by_token($token);
        if (!$profile) {
            wp_send_json_error('Invalid or expired student booking link.');
        }

        global $wpdb;
        $all_instructors = $wpdb->get_results("SELECT name, phone FROM {$wpdb->prefix}driveflow_instructors WHERE status = 'active' ORDER BY name ASC", ARRAY_A);

        if (empty($instructor) && !empty($all_instructors)) {
            $instructor = $all_instructors[0]['name'];
        }

        $free_data = DriveFlow_Availability_Engine::get_instructor_free_slots($instructor, $date, 120);

        wp_send_json_success(array(
            'student'     => array(
                'name'               => $profile['name'],
                'package_name'       => $profile['package_name'],
                'remaining_sessions' => $profile['remaining_sessions'],
                'total_sessions'     => $profile['total_sessions'],
                'completed_sessions' => $profile['completed_sessions'],
            ),
            'date'        => $date,
            'instructor'  => $instructor,
            'instructors' => $all_instructors,
            'slots'       => $free_data['slots'],
        ));
    }

    public function ajax_student_self_book() {
        if (!self::throttle('self_book', 10, 10 * MINUTE_IN_SECONDS)) {
            wp_send_json_error(array('message' => 'Too many requests. Please try again later.'), 429);
        }
        $token = sanitize_text_field(wp_unslash($_POST['token'] ?? ''));
        $start_time = sanitize_text_field(wp_unslash($_POST['slot_start'] ?? ''));
        $end_time = sanitize_text_field(wp_unslash($_POST['slot_end'] ?? ''));
        $instructor = sanitize_text_field(wp_unslash($_POST['instructor'] ?? ''));
        $topic = sanitize_text_field(wp_unslash($_POST['lesson_topic'] ?? 'Behind-The-Wheel Driving Lesson'));

        if (!$token || !$start_time || !$end_time) {
            wp_send_json_error('Missing booking parameters.');
        }

        $result = DriveFlow_Credit_Manager::self_book_session($token, $start_time, $end_time, $instructor, $topic);
        if ($result['success']) {
            self::notify_calendar_change();
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }

    public function ajax_toggle_instructor_status() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized access.');
        }
        check_ajax_referer('driveflow_admin_nonce', 'nonce');

        global $wpdb;
        $id = absint($_POST['id'] ?? 0);
        if (!$id) {
            wp_send_json_error('Invalid instructor ID.');
        }

        $table = $wpdb->prefix . 'driveflow_instructors';
        $current = $wpdb->get_var($wpdb->prepare("SELECT status FROM {$table} WHERE id = %d", $id));
        if (!$current) {
            wp_send_json_error('Instructor not found.');
        }

        $new_status = ('active' === $current) ? 'inactive' : 'active';
        $wpdb->update(
            $table,
            array('status' => $new_status, 'updated_at' => current_time('mysql')),
            array('id' => $id),
            array('%s', '%s'),
            array('%d')
        );

        wp_send_json_success(array(
            'id' => $id,
            'status' => $new_status,
            'label' => strtoupper($new_status),
            'message' => sprintf('Instructor status updated to %s.', strtoupper($new_status))
        ));
    }

    public function ajax_generate_magic_link() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized access.');
        }
        check_ajax_referer('driveflow_admin_nonce', 'nonce');

        $type = sanitize_key($_POST['type'] ?? '');
        $target_id = absint($_POST['target_id'] ?? 0);
        $duration_hours = intval($_POST['duration_hours'] ?? 48);
        $is_single_use = !empty($_POST['is_single_use']) && ('0' !== $_POST['is_single_use'] && 'false' !== $_POST['is_single_use']);

        if (!in_array($type, array('student_booking', 'instructor_onboarding'), true) || !$target_id) {
            wp_send_json_error('Invalid link generation parameters.');
        }

        if (!class_exists('DriveFlow_Magic_Links')) {
            require_once DRIVEFLOW_PRO_DIR . 'includes/class-magic-links.php';
        }

        $result = DriveFlow_Magic_Links::create_token($type, $target_id, $duration_hours, $is_single_use);
        if (!$result) {
            wp_send_json_error('Could not generate magic link.');
        }

        global $wpdb;
        $name = '';
        $email = '';
        if ('student_booking' === $type) {
            $row = $wpdb->get_row($wpdb->prepare("SELECT name, email FROM {$wpdb->prefix}driveflow_students WHERE id = %d", $target_id));
            if ($row) { $name = $row->name; $email = $row->email; }
        } else {
            $row = $wpdb->get_row($wpdb->prepare("SELECT name, email FROM {$wpdb->prefix}driveflow_instructors WHERE id = %d", $target_id));
            if ($row) { $name = $row->name; $email = $row->email; }
        }

        $emailed = false;
        if (!empty($_POST['send_email']) && !empty($email)) {
            $emailed = DriveFlow_Email_Manager::send_magic_link_dispatch($type, $name, $email, $result['short_url'], $result['expires_at'], $is_single_use);
        }

        wp_send_json_success(array(
            'token'          => $result['token'],
            'short_code'     => $result['short_code'],
            'short_url'      => $result['short_url'],
            'expires_at'     => $result['expires_at'] ? date_i18n('M j, Y g:i A', strtotime($result['expires_at'])) : 'Permanent (No Expiry)',
            'is_single_use'  => (bool)$result['is_single_use'],
            'recipient_name' => $name,
            'recipient_email'=> $email,
            'emailed'        => $emailed
        ));
    }

    public function ajax_submit_instructor_onboarding() {
        if (!self::throttle('onboarding', 10, 10 * MINUTE_IN_SECONDS)) {
            wp_send_json_error(array('message' => 'Too many requests. Please try again later.'), 429);
        }
        check_ajax_referer('driveflow_instructor_onboarding', 'onboarding_nonce');

        $token = sanitize_text_field(wp_unslash($_POST['token'] ?? ''));
        if (empty($token)) {
            wp_send_json_error('Security access token is missing.');
        }

        if (!class_exists('DriveFlow_Magic_Links')) {
            require_once DRIVEFLOW_PRO_DIR . 'includes/class-magic-links.php';
        }

        $verified = DriveFlow_Magic_Links::verify_token($token);
        if (is_wp_error($verified)) {
            wp_send_json_error($verified->get_error_message());
        }

        if ('instructor_onboarding' !== $verified['token_type']) {
            wp_send_json_error('Invalid link type for instructor onboarding.');
        }

        $instructor_id = absint($verified['target_id']);
        global $wpdb;
        $table = $wpdb->prefix . 'driveflow_instructors';
        $current = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $instructor_id), ARRAY_A);
        if (!$current) {
            wp_send_json_error('Instructor record was not found.');
        }

        $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
        $phone = sanitize_text_field(wp_unslash($_POST['phone'] ?? ''));
        $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
        $license_number = sanitize_text_field(wp_unslash($_POST['license_number'] ?? ''));
        $bio = sanitize_textarea_field(wp_unslash($_POST['bio'] ?? ''));

        if (empty($name) || empty($phone) || empty($email) || empty($license_number)) {
            wp_send_json_error('Please fill in all required fields (Name, Phone, Email, and License #).');
        }

        $update_data = array(
            'name'                 => $name,
            'phone'                => $phone,
            'email'                => $email,
            'license_number'       => $license_number,
            'bio'                  => $bio,
            'payment_method'       => sanitize_key($_POST['payment_method'] ?? 'zelle'),
            'payment_details'      => sanitize_textarea_field(wp_unslash($_POST['payment_details'] ?? '')),
            'onboarding_completed' => 1,
            'status'               => 'active',
            'updated_at'           => current_time('mysql')
        );

        // Handle file uploads (Profile Photo, State ID Card, Instructor Tag / Badge)
        $doc_fields = array(
            'profile_photo' => array('col' => 'photo_url',   'label' => 'Profile Headshot'),
            'id_card_photo' => array('col' => 'id_card_url', 'label' => 'State ID / Driver\'s License Photo'),
            'badge_photo'   => array('col' => 'badge_url',   'label' => 'Instructor Certification Badge / Tag')
        );

        $has_any_upload = false;
        foreach ($doc_fields as $field_key => $field_cfg) {
            if (!empty($_FILES[$field_key]) && !empty($_FILES[$field_key]['name'])) {
                $has_any_upload = true;
                break;
            }
        }

        if ($has_any_upload) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
            $allowed_types = array('image/jpeg', 'image/png', 'image/webp', 'image/jpg', 'application/pdf');
            $upload_overrides = array('test_form' => false);

            foreach ($doc_fields as $field_key => $field_cfg) {
                if (!empty($_FILES[$field_key]) && !empty($_FILES[$field_key]['name'])) {
                    $file = $_FILES[$field_key];
                    if (!in_array($file['type'], $allowed_types, true)) {
                        wp_send_json_error(sprintf('Invalid file type for %s. Please upload JPG, PNG, WEBP, or PDF.', $field_cfg['label']));
                    }
                    if ($file['size'] > 10 * 1024 * 1024) {
                        wp_send_json_error(sprintf('%s exceeds 10MB file size limit.', $field_cfg['label']));
                    }
                    $movefile = wp_handle_upload($file, $upload_overrides);
                    if ($movefile && !isset($movefile['error'])) {
                        $update_data[$field_cfg['col']] = esc_url_raw($movefile['url']);
                    } else {
                        wp_send_json_error(sprintf('%s upload failed: %s', $field_cfg['label'], ($movefile['error'] ?? 'Unknown error')));
                    }
                }
            }
        }

        // Handle Account Password & WordPress User Creation
        $password = sanitize_text_field(wp_unslash($_POST['password'] ?? ''));
        $confirm_password = sanitize_text_field(wp_unslash($_POST['confirm_password'] ?? ''));
        $account_created = false;

        if (!empty($password)) {
            if (strlen($password) < 6) {
                wp_send_json_error('Account password must be at least 6 characters long.');
            }
            if ($password !== $confirm_password) {
                wp_send_json_error('Password confirmation does not match.');
            }

            $existing_user = get_user_by('email', $email);
            $wp_user_id = 0;

            if ($existing_user) {
                $wp_user_id = $existing_user->ID;
                wp_set_password($password, $wp_user_id);
                wp_update_user(array(
                    'ID'           => $wp_user_id,
                    'display_name' => $name,
                    'first_name'   => $name,
                ));
                $existing_user->set_role('driveflow_instructor');
                $account_created = true;
            } else {
                $username = sanitize_user(current(explode('@', $email)));
                if (empty($username) || username_exists($username)) {
                    $username .= '_' . wp_rand(10, 99);
                }
                $wp_user_id = wp_create_user($username, $password, $email);
                if (is_wp_error($wp_user_id)) {
                    wp_send_json_error('User account creation failed: ' . $wp_user_id->get_error_message());
                }
                $user_obj = new WP_User($wp_user_id);
                $user_obj->set_role('driveflow_instructor');
                wp_update_user(array(
                    'ID'           => $wp_user_id,
                    'display_name' => $name,
                    'first_name'   => $name,
                ));
                $account_created = true;
            }

            if ($wp_user_id) {
                $update_data['user_id'] = $wp_user_id;

                // Auto-login instructor into WordPress session
                wp_set_current_user($wp_user_id);
                wp_set_auth_cookie($wp_user_id, true);
            }
        }

        // Handle Legal Agreement & Regulatory Compliance Signing
        $settings = get_option(self::OPTION_KEY, array());
        $agreement_enabled = ('1' === ($settings['instructor_agreement_enabled'] ?? '1'));

        if ($agreement_enabled) {
            $accepted = !empty($_POST['accept_agreement']);
            $sig_data = sanitize_textarea_field(wp_unslash($_POST['agreement_signature'] ?? ''));

            if (!$accepted) {
                wp_send_json_error('You must review and accept the Driving Instructor Employment Agreement to complete registration.');
            }
            if (empty($sig_data) || strlen($sig_data) < 100) {
                wp_send_json_error('Please draw your digital signature on the employment agreement pad before submitting.');
            }

            $update_data['agreement_signed'] = 1;
            $update_data['agreement_signed_at'] = current_time('mysql');
            $update_data['agreement_signature'] = $sig_data;
            $update_data['agreement_ip'] = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'] ?? ''));
            $current_wage = (!empty($current['hourly_wage']) && floatval($current['hourly_wage']) > 0) ? floatval($current['hourly_wage']) : floatval($settings['instructor_default_hourly_wage'] ?? 35.00);
            $update_data['agreement_wage'] = number_format($current_wage, 2);
            $update_data['hourly_wage'] = $current_wage;
        }

        $wpdb->update($table, $update_data, array('id' => $instructor_id));

        // Mark token as used if single-use
        DriveFlow_Magic_Links::mark_as_used($token);

        $instructor_page_id = absint($settings['instructor_page_id'] ?? 0);
        $redirect_url = ($instructor_page_id && 'publish' === get_post_status($instructor_page_id))
            ? get_permalink($instructor_page_id)
            : home_url('/instructor-session/');

        wp_send_json_success(array(
            'message'         => 'Your instructor profile, employment agreement & credentials have been successfully registered!',
            'photo_url'       => $update_data['photo_url'] ?? ($current['photo_url'] ?? ''),
            'account_created' => $account_created,
            'redirect_url'    => $redirect_url
        ));
    }

    public function ajax_get_signed_agreement() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized.');
        }
        check_ajax_referer('driveflow_admin_nonce', 'nonce');

        $instructor_id = absint($_POST['instructor_id'] ?? 0);
        if (!$instructor_id) {
            wp_send_json_error('Invalid instructor ID.');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'driveflow_instructors';
        $ins = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $instructor_id), ARRAY_A);
        if (!$ins) {
            wp_send_json_error('Instructor record not found.');
        }

        $settings = get_option(self::OPTION_KEY, array());
        $raw_text = !empty($settings['instructor_agreement_text']) ? $settings['instructor_agreement_text'] : self::get_default_instructor_agreement();

        $effective_date = !empty($ins['agreement_signed_at']) ? date_i18n('F j, Y', strtotime($ins['agreement_signed_at'])) : date_i18n('F j, Y');
        $school_name = $settings['school_name'] ?? 'Sam\'s Driving School LLC';
        $school_address = $settings['school_address'] ?? '751 Rockville Pike, Unit # 9B, Rockville, MD 20852';
        $school_phone = $settings['school_phone'] ?? '(202) 600-0889 / (301) 726-3030';
        $hourly_wage = !empty($ins['agreement_wage']) ? $ins['agreement_wage'] : ($settings['instructor_default_hourly_wage'] ?? '35.00');

        $replacements = array(
            '{school_name}'     => $school_name,
            '{school_address}'  => $school_address,
            '{school_phone}'    => $school_phone,
            '{instructor_name}' => $ins['name'],
            '{license_number}'  => $ins['license_number'] ?: 'Pending',
            '{hourly_wage}'     => $hourly_wage,
            '{effective_date}'  => $effective_date
        );

        $rendered_contract = str_replace(array_keys($replacements), array_values($replacements), $raw_text);

        wp_send_json_success(array(
            'instructor_name' => $ins['name'],
            'license_number'  => $ins['license_number'],
            'signed'          => !empty($ins['agreement_signed']),
            'signed_at'       => $ins['agreement_signed_at'] ? date_i18n('F j, Y g:i A T', strtotime($ins['agreement_signed_at'])) : 'Pending Signature',
            'ip_address'      => $ins['agreement_ip'] ?: '—',
            'signature_data'  => $ins['agreement_signature'],
            'contract_text'   => $rendered_contract,
            'school_name'     => $school_name,
            'school_address'  => $school_address,
            'school_phone'    => $school_phone,
            'hourly_wage'     => $hourly_wage
        ));
    }

    public static function get_default_instructor_agreement() {
        return "DRIVING INSTRUCTOR EMPLOYMENT AGREEMENT\n\n" .
        "THIS DRIVING INSTRUCTOR EMPLOYMENT AGREEMENT (the \"Agreement\") is entered into on this {effective_date} (the \"Effective Date\"), by and between:\n\n" .
        "EMPLOYER:\n" .
        "{school_name}, a Maryland limited liability company, licensed by the Maryland Motor Vehicle Administration (\"MVA\") as a commercial drivers' school, having its principal business office located at:\n" .
        "{school_address}\n" .
        "Telephone: {school_phone}\n" .
        "Represented herein by its Managing Member, Sam (hereinafter referred to as the \"School\"); and\n\n" .
        "EMPLOYEE (INSTRUCTOR):\n" .
        "Name: {instructor_name}\n" .
        "Maryland Driver's License / MVA Instructor Number: {license_number}\n" .
        "(hereinafter referred to as the \"Instructor\").\n\n" .
        "The School and the Instructor are collectively referred to as the \"Parties,\" and each individually as a \"Party.\"\n\n" .
        "RECITALS\n" .
        "WHEREAS, the School operates an MVA-certified commercial driving school providing approved Driver Education Program courses, Behind-The-Wheel instruction, Roadway Safety Driving Education Programs (RSDEP), and Driver Improvement Programs (DIP) in Montgomery County, Maryland; and\n" .
        "WHEREAS, the Instructor represents that they possess the necessary credentials, qualifications, character, and driving record required by the Annotated Code of Maryland and COMAR 11.23 to deliver professional driver instruction; and\n" .
        "WHEREAS, the School desires to employ the Instructor, and the Instructor desires to accept such employment, upon the terms, conditions, and regulatory covenants set forth herein.\n\n" .
        "NOW, THEREFORE, in consideration of the mutual promises, covenants, and valuable consideration contained herein, the receipt and sufficiency of which are hereby acknowledged, the Parties agree as follows:\n\n" .
        "SECTION 1: APPOINTMENT AND SCOPE OF SERVICES\n" .
        "1.1. Appointment: The School hereby employs the Instructor, and the Instructor hereby accepts employment, as a Maryland MVA-certified driving instructor.\n" .
        "1.2. Instructional Duties: The Instructor shall provide driver education and practical driving instruction in accordance with curricula approved by the MVA and the operational directives of the School. Duties include:\n" .
        "- Delivering the 30-hour classroom component of the Driver Education Program through the School's designated virtual learning platform (Zoom), adhering strictly to scheduled evening sessions (6:00 PM – 9:15 PM) and never exceeding the statutory cap of 3 hours of daily classroom instruction per student pursuant to COMAR 11.23.02.32.\n" .
        "- Conducting practical behind-the-wheel (BTW) training sessions in standard two-hour modules, evaluating student driving performance against state benchmarks.\n" .
        "- Delivering instruction for the 3-Hour Alcohol and Drug Education Program (RSDEP) and Driver Improvement Program (DIP), provided the Instructor holds the appropriate certifications under COMAR 11.12.09.06.\n" .
        "- Applying specialized instructional methods designed for anxious and novice drivers, integrating the School's structured stress-reduction principles.\n" .
        "- Conducting instruction in English, Farsi, or Spanish as assigned to match student language proficiencies.\n" .
        "- Accompanying students to MVA testing branches for scheduled road skills examinations using the School's dual-control vehicles.\n\n" .
        "SECTION 2: EMPLOYMENT STATUS AND TAX CLASSIFICATION\n" .
        "2.1. W-2 Statutory Employment: The Parties acknowledge and agree that the relationship established by this Agreement is that of employer and employee on an hourly, part-time (or full-time, as scheduled) basis, classified under IRS Form W-2. This classification complies with the Maryland Unemployment Insurance ABC Test (Md. Code Ann., Lab. & Empl. § 8-205) and COMAR requirements regarding school supervisory oversight.\n" .
        "2.2. Tax Withholdings: The School shall withhold all required federal income tax, Maryland state tax, Montgomery County local income tax, and FICA (Social Security and Medicare) contributions from the Instructor's gross wages.\n" .
        "2.3. No Independent Authority: The Instructor has no authority to bind the School in any contract, incur corporate debts, or accept direct, off-the-books payments from students.\n\n" .
        "SECTION 3: MVA QUALIFICATIONS AND REGULATORY COVENANTS\n" .
        "3.1. Prerequisite Credentials: The Instructor warrants and covenants that they: (a) are at least 21 years of age; (b) hold a valid high school diploma, GED, or a NACES-evaluated foreign credential equivalency; (c) hold a valid driver's license for the class of vehicle instructed, free of ignition interlock, alcohol, or employment-only restrictions; (d) have cleared a CJIS fingerprint criminal background check (ORI # MD920497Z); and (e) have no pending charges or convictions involving moral turpitude, sexual offenses, child delinquency, or drug/alcohol offenses within the preceding 3 years.\n" .
        "3.2. Point Threshold Obligation: The Instructor must maintain fewer than five (5) active points on their driving record at all times. Accumulating 5 or more active points is a disqualifying event that immediately terminates the Instructor's authorization to provide instruction pursuant to COMAR 11.23.01.19D(11).\n" .
        "3.3. Display of Instructor Badge: Pursuant to COMAR 11.23.01.16D, the Instructor must wear the official MVA Instructor Identification Badge in plain, unobstructed view at all times while conducting classroom or in-car training.\n" .
        "3.4. Mandatory Reporting: The Instructor shall notify the School in writing within twenty-four (24) hours of: (a) receiving any traffic citation; (b) the suspension or revocation of their driver's license; (c) any criminal charge; or (d) any diagnosis of a reportable medical condition under COMAR.\n" .
        "3.5. Continuing Education: The Instructor shall complete 8 hours of MVA-approved professional development (or 4 hours annually) every two years to maintain active certification under COMAR 11.23.02.19C.\n\n" .
        "SECTION 4: INSTRUCTIONAL DISCIPLINE AND ELECTRONIC DEVICE PROHIBITION\n" .
        "4.1. Strict Mobile Device Ban: Pursuant to COMAR 11.23.02.18D, the Instructor is strictly prohibited from using a cellular telephone, mobile device, or hands-free headset—including for texting, phone calls, or application monitoring—while conducting behind-the-wheel instruction in a moving vehicle, except during a life-threatening emergency. The Instructor must also prohibit the student from accessing cellular devices while the vehicle is in motion. A breach of this subsection constitutes cause for immediate termination.\n" .
        "4.2. Documentation and Records: The Instructor shall accurately complete instructional evaluation sheets immediately following each practical lesson. All lesson logs, grading sheets, and attendance records must be submitted to the School within twenty-four (24) hours of session completion to allow the School to meet its statutory duty under COMAR to report completion to the MVA within 48 hours.\n\n" .
        "SECTION 5: TRAINING VEHICLE STANDARDS AND SAFETY INSPECTIONS\n" .
        "5.1. Vehicle Age and Status: All vehicles utilized for instruction must comply with COMAR 11.23.02.33 and cannot exceed eight (8) model years in age. Model Year 2018 vehicles cannot be used for instruction.\n" .
        "5.2. Pre-Trip Safety Inspection: Prior to beginning any behind-the-wheel lesson, the Instructor must personally inspect and verify the operational safety of: (a) the passenger-side dual-control foot brake pedal; (b) the auxiliary rearview mirror and passenger-side instructor mirrors; (c) the emergency kit, fire extinguisher, and reflective hazard triangles; and (d) dash documentation, confirming the current MVA safety inspection certificate and commercial insurance card are in the glove box.\n" .
        "5.3. Immediate Grounding: If any safety defect or brake irregularity is discovered, the Instructor shall immediately cancel the lesson and notify the School's management.\n\n" .
        "SECTION 6: COMPENSATION AND PAYMENT SCHEDULE\n" .
        "6.1. Hourly Wage: The School agrees to pay the Instructor a regular hourly wage of ${hourly_wage} per hour for authorized classroom and behind-the-wheel instruction.\n" .
        "6.2. Payroll Distribution: Compensation shall be disbursed on a regular schedule via direct deposit, accompanied by an itemized earnings statement reflecting all statutory tax and benefit deductions.\n" .
        "6.3. Exclusivity of Payment: The Instructor shall not solicit, negotiate, or accept cash payments, tips, or direct fees from students. All fees must be billed and collected exclusively through the School's authorized booking channels.\n\n" .
        "SECTION 7: INSURANCE AND MUTUAL INDEMNIFICATION\n" .
        "7.1. Corporate Insurance: The School shall maintain: (a) a Commercial Auto Liability policy with a Combined Single Limit of not less than $1,000,000 per accident covering driver training activities; (b) Commercial General Liability coverage; (c) Workers' Compensation insurance; and (d) a continuous $40,000 MVA Surety Bond.\n" .
        "7.2. Instructor Indemnification: The Instructor agrees to indemnify, defend, and hold harmless Sam's Driving School LLC, its managing members, officers, and agents from any liabilities, claims, losses, fines, or defense costs resulting from: (a) the Instructor's gross negligence or willful misconduct; (b) operation of a vehicle while impaired by alcohol, drugs, or medications; (c) instruction conducted while unlicensed or suspended; or (d) intentional violations of criminal statutes or COMAR safety rules.\n\n" .
        "SECTION 8: TRADE SECRETS, CONFIDENTIALITY, AND INTELLECTUAL PROPERTY\n" .
        "8.1. Proprietary Assets: The Instructor acknowledges that all student databases, lead lists, phone records, instructional curricula, pricing models, and specialized anxiety-management teaching methodologies developed by the School are protected trade secrets under the Maryland Uniform Trade Secrets Act (MUTSA).\n" .
        "8.2. Non-Disclosure Covenant: The Instructor agrees that during and after employment, they will not disclose, copy, or misappropriate any confidential records or student information for their personal benefit or the benefit of any third party.\n" .
        "8.3. Work Made for Hire: All lesson plans, video recordings, instructional guides, and training aids created by the Instructor within the scope of employment belong exclusively to Sam's Driving School LLC.\n\n" .
        "SECTION 9: NON-SOLICITATION OF CLIENTS AND PERSONNEL\n" .
        "9.1. Compliance with Maryland Labor Code § 3-716: The Parties recognize that under Md. Code Ann., Lab. & Empl. § 3-716, non-compete clauses restricting ordinary post-employment work are void for employees earning 150% or less of the state minimum wage ($22.50/hour). Accordingly, this Agreement does not prohibit the Instructor from teaching at other driving schools after departure.\n" .
        "9.2. Enforceable Non-Solicitation Covenant: Pursuant to § 3-716(c), the Instructor covenants and agrees that for a period of twenty-four (24) months following the termination of this Agreement for any reason, the Instructor shall not: (a) directly or indirectly solicit, divert, contact, or provide private or commercial driving instruction to any student who enrolled in or received instruction through Sam's Driving School LLC during the Instructor's tenure; or (b) solicit, induce, or encourage any employee or certified instructor of the School to terminate their employment with Sam's Driving School LLC.\n" .
        "9.3. Injunctive Relief: In the event of an actual or threatened breach of this Section, the School shall be entitled to seek temporary and permanent injunctive relief in a court of competent jurisdiction, in addition to recovering actual damages and reasonable attorney's fees.\n\n" .
        "SECTION 10: TERM AND TERMINATION\n" .
        "10.1. Term: This Agreement begins on the Effective Date and continues for a term of one (1) year, automatically renewing annually unless terminated as provided herein.\n" .
        "10.2. Termination Without Cause: Either Party may terminate this Agreement without cause upon providing fourteen (14) calendar days' advance written notice. The Instructor must complete all scheduled behind-the-wheel sessions during the notice period.\n" .
        "10.3. Immediate Termination for Cause: The School may terminate this Agreement immediately and without prior notice upon the occurrence of any of the following: (a) the suspension, expiration, or revocation of the Instructor's driver's license or MVA instructor credentials; (b) the accumulation of five (5) or more active points on the Instructor's driving record; (c) any violation of the cell phone prohibition set forth in COMAR 11.23.02.18D; (d) testing positive for controlled substances or alcohol while on duty; or (e) commission of an act of moral turpitude, sexual harassment, or disruptive misconduct toward a student or employee.\n\n" .
        "SECTION 11: GOVERNING LAW, JURISDICTION, AND SEVERABILITY\n" .
        "11.1. Governing Law: This Agreement is governed by and construed under the laws of the State of Maryland, without regard to conflicts of law principles.\n" .
        "11.2. Exclusive Jurisdiction: The Parties consent to the exclusive personal and subject-matter jurisdiction of the District Court or Circuit Court for Montgomery County, Maryland, for any actions arising under this Agreement.\n" .
        "11.3. Severability: If any provision of this Agreement is held to be invalid, illegal, or unenforceable under Maryland law, the remaining provisions shall continue in full force and effect.\n\n" .
        "SECTION 12: ELECTRONIC SIGNATURES AND UETA CONSENT\n" .
        "The Parties expressly agree to conduct this transaction by electronic means pursuant to the Maryland Uniform Electronic Transactions Act (Md. Code Ann., Com. Law § 21-101 et seq.) and the federal ESIGN Act. Electronic signatures, click-through confirmations, and cryptographically verified digital signatures shall have the same legal force and effect as original handwritten signatures.";
    }

    public function instructor_onboarding_shortcode() {
        static $rendered = false;
        if ($rendered) return '';
        $rendered = true;

        ob_start();
        include DRIVEFLOW_PRO_DIR . 'templates/instructor-onboarding.php';
        return ob_get_clean();
    }

    public function ajax_submit_evaluation() {
        if (!self::throttle('evaluation', 30, 10 * MINUTE_IN_SECONDS)) {
            wp_send_json_error(array('message' => 'Too many requests. Please try again later.'), 429);
        }
        list($is_admin, $me) = $this->instructor_ajax_context();
        check_ajax_referer('driveflow_instructor_form', 'df_nonce');
        global $wpdb;
        $student_name = sanitize_text_field(wp_unslash($_POST['student_name'] ?? ''));
        $instructor_name = sanitize_text_field(wp_unslash($_POST['instructor_name'] ?? ''));
        $instructor_cert_no = sanitize_text_field(wp_unslash($_POST['instructor_cert_no'] ?? ''));
        $plate_number = sanitize_text_field(wp_unslash($_POST['plate_number'] ?? ''));
        $session_number = max(1, absint($_POST['session_number'] ?? 1));
        $lesson_topic = sanitize_text_field(wp_unslash($_POST['lesson_topic'] ?? 'Behind-The-Wheel Driving Lesson'));
        $scheduled_start = sanitize_text_field(wp_unslash($_POST['scheduled_start'] ?? current_time('mysql')));
        $scheduled_end = sanitize_text_field(wp_unslash($_POST['scheduled_end'] ?? date('Y-m-d H:i:s', strtotime($scheduled_start . ' +2 hours'))));
        $session_id = absint($_POST['session_id'] ?? 0);
        $final_evaluation = sanitize_text_field(wp_unslash($_POST['final_evaluation'] ?? 'Pass'));
        $instructor_signature = self::clean_image_data_url($_POST['instructor_signature_data'] ?? '', 300 * KB_IN_BYTES);
        $student_signature = self::clean_image_data_url($_POST['student_signature_data'] ?? '', 300 * KB_IN_BYTES);
        $selfie = self::clean_image_data_url($_POST['selfie'] ?? '', 1536 * KB_IN_BYTES);
        $instructor_notes = sanitize_textarea_field(wp_unslash($_POST['instructor_notes'] ?? ''));

        if (!$is_admin) {
            // Instructors always submit as themselves; posted identity fields are ignored.
            $instructor_name    = $me['name'];
            $instructor_cert_no = $me['license_number'];
        }

        if (empty($student_name)) {
            wp_send_json_error(array('message' => 'Student name is required.'));
        }

        // Verify that instructor is active
        if (!empty($instructor_name)) {
            $ins_status = $wpdb->get_var($wpdb->prepare("SELECT status FROM {$wpdb->prefix}driveflow_instructors WHERE name = %s", $instructor_name));
            if ($ins_status && 'inactive' === $ins_status) {
                wp_send_json_error(array('message' => "Instructor '{$instructor_name}' is currently deactivated by administration and cannot submit session evaluations."));
            }
        }

        // Only open sessions of the same instructor may be completed/overwritten (protects finished records).
        if ($session_id) {
            $existing = $wpdb->get_row($wpdb->prepare("SELECT status, instructor_name FROM {$this->table()} WHERE id = %d", $session_id), ARRAY_A);
            if (!$existing
                || !in_array($existing['status'], array('upcoming', 'active'), true)
                || ('' !== (string) $existing['instructor_name'] && 0 !== strcasecmp($existing['instructor_name'], $instructor_name))) {
                wp_send_json_error(array('message' => 'This session cannot be updated.'), 403);
            }
        }

        // Collect lesson dates (l1 - l6)
        $dates = array();
        for ($i = 1; $i <= 6; $i++) {
            $dates['l' . $i] = sanitize_text_field(wp_unslash($_POST['date_l' . $i] ?? ''));
        }

        // Collect skills ratings (0 to 21 across l1 to l6)
        $skills_eval = array();
        for ($s = 0; $s < 22; $s++) {
            $skills_eval[$s] = array();
            for ($l = 1; $l <= 6; $l++) {
                $val = sanitize_text_field(wp_unslash($_POST['skill_' . $s . '_l' . $l] ?? ''));
                if ($val !== '') $skills_eval[$s]['l' . $l] = $val;
            }
        }

        // Collect driving conditions ratings (0 to 1 across l1 to l6)
        $conds_eval = array();
        for ($c = 0; $c < 2; $c++) {
            $conds_eval[$c] = array();
            for ($l = 1; $l <= 6; $l++) {
                $val = sanitize_text_field(wp_unslash($_POST['cond_' . $c . '_l' . $l] ?? ''));
                if ($val !== '') $conds_eval[$c]['l' . $l] = $val;
            }
        }

        // Find or create student
        $student_id = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}driveflow_students WHERE name = %s", $student_name));
        $now = current_time('mysql');
        if (!$student_id) {
            $wpdb->insert($wpdb->prefix . 'driveflow_students', array(
                'name' => $student_name,
                'package_name' => 'Standard Driving Course',
                'total_sessions' => 6,
                'completed_sessions' => 0,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now
            ), array('%s','%s','%d','%d','%s','%s','%s'));
            $student_id = (int) $wpdb->insert_id;
        }

        // Fuel & Vehicle Post-Trip Inspection Data
        $fuel_level_pct = isset($_POST['fuel_level_pct']) ? min(100, max(0, absint($_POST['fuel_level_pct']))) : 75;
        $student_refuel_conducted = !empty($_POST['student_refuel_conducted']) ? 1 : 0;
        $fuel_gallons = isset($_POST['fuel_gallons']) ? floatval($_POST['fuel_gallons']) : 0.0;
        $fuel_cost = isset($_POST['fuel_cost']) ? floatval($_POST['fuel_cost']) : 0.0;

        $has_incident = (!empty($_POST['has_vehicle_incident']) && 'yes' === $_POST['has_vehicle_incident']) ? 1 : 0;
        $incident_types = isset($_POST['incident_types']) ? array_map('sanitize_text_field', (array) $_POST['incident_types']) : array();
        $incident_urgency = sanitize_text_field(wp_unslash($_POST['incident_urgency'] ?? 'routine'));
        $incident_description = sanitize_textarea_field(wp_unslash($_POST['incident_description'] ?? ''));

        $form_data = array(
            'source'               => 'in_car_eval_record',
            'instructor_cert_no'   => $instructor_cert_no,
            'dates'                => $dates,
            'skills_eval'          => $skills_eval,
            'conds_eval'           => $conds_eval,
            'final_evaluation'     => $final_evaluation,
            'fuel'                 => array(
                'level_pct'                => $fuel_level_pct,
                'student_refuel_conducted' => $student_refuel_conducted,
                'gallons'                  => $fuel_gallons,
                'cost'                     => $fuel_cost,
            ),
            'incident'             => array(
                'reported'    => $has_incident,
                'types'       => $incident_types,
                'urgency'     => $incident_urgency,
                'description' => $incident_description,
            ),
            'instructor_signature' => $instructor_signature,
            'student_signature'    => $student_signature,
            'instructor_notes'     => $instructor_notes,
            'submitted_at'         => $now
        );

        $session_data = array(
            'student_name'    => $student_name,
            'student_id'      => $student_id,
            'instructor_name' => $instructor_name,
            'plate_number'    => $plate_number,
            'session_number'  => $session_number,
            'lesson_topic'    => $lesson_topic,
            'scheduled_start' => $scheduled_start,
            'scheduled_end'   => $scheduled_end,
            'status'          => 'completed',
            'form_data'       => wp_json_encode($form_data),
            'signature'       => $student_signature ?: $instructor_signature,
            'selfie'          => $selfie,
            'updated_at'      => $now
        );

        if ($session_id) {
            $wpdb->update($this->table(), $session_data, array('id' => $session_id));
        } else {
            $session_data['created_by'] = get_current_user_id();
            $session_data['created_at'] = $now;
            $wpdb->insert($this->table(), $session_data);
            $session_id = (int) $wpdb->insert_id;
        }

        // Synchronize vehicle fleet record with fuel & incident reports
        if (!empty($plate_number)) {
            $veh_update = array(
                'current_fuel_level' => $fuel_level_pct,
                'last_fuel_report'   => $now,
                'updated_at'         => $now,
            );
            if ($has_incident) {
                $veh_update['last_incident_report'] = $incident_description;
                $veh_update['maintenance_notes'] = implode(', ', $incident_types);
                if ('urgent_ground' === $incident_urgency) {
                    $veh_update['status'] = 'maintenance';
                    $veh_update['maintenance_status'] = 'grounded';
                } else {
                    $veh_update['maintenance_status'] = 'needs_inspection';
                }
            } elseif ($fuel_level_pct <= 25) {
                $veh_update['maintenance_status'] = 'low_fuel';
            } else {
                $veh_update['maintenance_status'] = 'operational';
            }
            $wpdb->update($wpdb->prefix . 'driveflow_vehicles', $veh_update, array('plate_number' => $plate_number));
        }

        // Deduct session credit on student balance
        DriveFlow_Credit_Manager::deduct_session_credit($student_id, $session_id);

        // Send completion email to student
        DriveFlow_Email_Manager::send_student_completion($session_id);

        self::notify_calendar_change();

        wp_send_json_success(array(
            'message' => 'Evaluation and progress record submitted successfully! Student scorecard dispatched.',
            'session_id' => $session_id,
            'student_name' => $student_name,
            'evaluation' => $final_evaluation
        ));
    }

    public function export_csv_action() {
        if (!current_user_can('manage_options') || !check_admin_referer('driveflow_export_csv')) {
            wp_die('Unauthorized request.');
        }
        global $wpdb;

        $search = sanitize_text_field(wp_unslash($_GET['s'] ?? ''));
        $status_filter = sanitize_key($_GET['status'] ?? '');
        $date_filter = sanitize_key($_GET['date_filter'] ?? '');

        $where_clauses = array();
        $args = array();

        if ($search) {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where_clauses[] = "(student_name LIKE %s OR instructor_name LIKE %s OR lesson_topic LIKE %s OR plate_number LIKE %s)";
            $args[] = $like; $args[] = $like; $args[] = $like; $args[] = $like;
        }

        if ($status_filter && in_array($status_filter, array('upcoming', 'active', 'completed', 'cancelled'), true)) {
            $where_clauses[] = "status = %s";
            $args[] = $status_filter;
        }

        if ('today' === $date_filter) {
            $where_clauses[] = "DATE(scheduled_start) = CURDATE()";
        } elseif ('this_week' === $date_filter) {
            $where_clauses[] = "YEARWEEK(scheduled_start, 1) = YEARWEEK(CURDATE(), 1)";
        }

        $where_sql = !empty($where_clauses) ? ' WHERE ' . implode(' AND ', $where_clauses) : '';
        $query = "SELECT id, student_name, instructor_name, plate_number, session_number, lesson_topic, scheduled_start, scheduled_end, status, created_at FROM {$this->table()}{$where_sql} ORDER BY scheduled_start DESC";
        $rows = $args ? $wpdb->get_results($wpdb->prepare($query, $args), ARRAY_A) : $wpdb->get_results($query, ARRAY_A);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="driveflow_sessions_' . date('Y-m-d') . '.csv"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, array('Session ID', 'Student Name', 'Instructor Name', 'Plate Number', 'Session #', 'Lesson Topic', 'Start Time', 'End Time', 'Status', 'Created At'));
        foreach ($rows as $row) {
            fputcsv($out, array(
                $row['id'],
                $row['student_name'],
                $row['instructor_name'],
                $row['plate_number'],
                $row['session_number'],
                $row['lesson_topic'],
                $row['scheduled_start'],
                $row['scheduled_end'],
                $row['status'],
                $row['created_at'],
            ));
        }
        fclose($out);
        exit;
    }

    public static function render_maryland_plate($plate_number, $size = '', $model = '', $color = '') {
        $plate_clean = esc_html(strtoupper(trim((string)$plate_number)));
        if (empty($plate_clean) || '—' === $plate_clean) {
            return '<span style="color:#94a3b8;">—</span>';
        }
        $class = 'maryland-plate';
        if ('sm' === $size) {
            $class .= ' plate-sm';
        } elseif ('lg' === $size) {
            $class .= ' plate-lg';
        } elseif ('xl' === $size || 'preview' === $size) {
            $class .= ' plate-xl';
        }
        $data_model = !empty($model) ? ' data-model="' . esc_attr($model) . '"' : '';
        $data_color = !empty($color) ? ' data-color="' . esc_attr($color) . '"' : '';
        return '<div class="' . esc_attr($class) . '" title="Maryland Registration: ' . $plate_clean . ' (Click to Preview Plate)" data-plate="' . esc_attr($plate_clean) . '"' . $data_model . $data_color . ' role="button" tabindex="0">' .
               '<span class="plate-bolt bolt-tl"></span><span class="plate-bolt bolt-tr"></span>' .
               '<span class="plate-number">' . $plate_clean . '</span>' .
               '<span class="plate-bolt bolt-bl"></span><span class="plate-bolt bolt-br"></span>' .
               '<span class="plate-shine"></span>' .
               '</div>';
    }

    public function reports_page() {
        if (!current_user_can('manage_options')) return;
        global $wpdb;

        $active_tab = sanitize_key($_GET['tab'] ?? 'overview');
        $allowed_tabs = array('overview', 'payroll', 'daily', 'monthly', 'instructors', 'students', 'vehicles');
        if (!in_array($active_tab, $allowed_tabs, true)) {
            $active_tab = 'overview';
        }

        $date_preset = sanitize_key($_GET['date_preset'] ?? 'this_month');
        $start_date = sanitize_text_field(wp_unslash($_GET['start_date'] ?? ''));
        $end_date = sanitize_text_field(wp_unslash($_GET['end_date'] ?? ''));
        $search = sanitize_text_field(wp_unslash($_GET['s'] ?? ''));
        $paged = max(1, absint($_GET['paged'] ?? 1));
        $per_page = max(5, min(100, absint($_GET['per_page'] ?? 15)));
        $offset = ($paged - 1) * $per_page;

        // Determine timeframe dates
        $today = current_time('Y-m-d');
        if ('today' === $date_preset) {
            $start_date = $today;
            $end_date = $today;
        } elseif ('yesterday' === $date_preset) {
            $start_date = date('Y-m-d', strtotime('-1 day', strtotime($today)));
            $end_date = $start_date;
        } elseif ('this_week' === $date_preset) {
            $start_date = date('Y-m-d', strtotime('monday this week', strtotime($today)));
            $end_date = date('Y-m-d', strtotime('sunday this week', strtotime($today)));
        } elseif ('this_month' === $date_preset) {
            $start_date = date('Y-m-01', strtotime($today));
            $end_date = date('Y-m-t', strtotime($today));
        } elseif ('last_month' === $date_preset) {
            $start_date = date('Y-m-01', strtotime('first day of last month', strtotime($today)));
            $end_date = date('Y-m-t', strtotime('last day of last month', strtotime($today)));
        } elseif ('this_year' === $date_preset) {
            $start_date = date('Y-01-01', strtotime($today));
            $end_date = date('Y-12-31', strtotime($today));
        } elseif ('all' === $date_preset) {
            $start_date = '2020-01-01';
            $end_date = '2030-12-31';
        } else {
            if (empty($start_date)) $start_date = date('Y-m-01', strtotime($today));
            if (empty($end_date)) $end_date = date('Y-m-t', strtotime($today));
        }

        $session_table = $this->table();
        $date_where = $wpdb->prepare("DATE(scheduled_start) >= %s AND DATE(scheduled_start) <= %s", $start_date, $end_date);

        // Global KPIs
        $kpi_row = $wpdb->get_row("SELECT 
            COUNT(*) as total_sessions,
            SUM(status = 'completed') as completed_sessions,
            SUM(status = 'upcoming') as upcoming_sessions,
            SUM(status = 'active') as active_sessions,
            SUM(status = 'cancelled') as cancelled_sessions,
            COUNT(DISTINCT student_name) as unique_students,
            COUNT(DISTINCT instructor_name) as unique_instructors,
            COUNT(DISTINCT plate_number) as unique_vehicles
            FROM {$session_table} WHERE {$date_where}", ARRAY_A);

        $total_sessions = (int)($kpi_row['total_sessions'] ?? 0);
        $completed_sessions = (int)($kpi_row['completed_sessions'] ?? 0);
        $cancelled_sessions = (int)($kpi_row['cancelled_sessions'] ?? 0);
        $active_sessions = (int)($kpi_row['active_sessions'] ?? 0);
        $upcoming_sessions = (int)($kpi_row['upcoming_sessions'] ?? 0);
        $hours_driven = $completed_sessions * 2;
        $completion_rate = ($total_sessions > 0) ? round(($completed_sessions / $total_sessions) * 100) : 100;

        // Query evaluation Pass count from form_data
        $eval_rows = $wpdb->get_results("SELECT form_data FROM {$session_table} WHERE {$date_where} AND status = 'completed'", ARRAY_A);
        $evaluated_count = 0;
        $passed_count = 0;
        foreach ($eval_rows as $er) {
            if (!empty($er['form_data'])) {
                $fd = json_decode($er['form_data'], true);
                if (!empty($fd['final_evaluation'])) {
                    $evaluated_count++;
                    if ('Pass' === $fd['final_evaluation']) {
                        $passed_count++;
                    }
                }
            }
        }
        $pass_rate = ($evaluated_count > 0) ? round(($passed_count / $evaluated_count) * 100) : 100;

        $kpis = array(
            'total_sessions'     => $total_sessions,
            'completed_sessions' => $completed_sessions,
            'upcoming_sessions'  => $upcoming_sessions,
            'active_sessions'    => $active_sessions,
            'cancelled_sessions' => $cancelled_sessions,
            'hours_driven'       => $hours_driven,
            'completion_rate'    => $completion_rate,
            'pass_rate'          => $pass_rate,
            'passed_count'       => $passed_count,
            'evaluated_count'    => $evaluated_count,
            'unique_students'    => (int)($kpi_row['unique_students'] ?? 0),
            'unique_instructors' => (int)($kpi_row['unique_instructors'] ?? 0),
            'unique_vehicles'    => (int)($kpi_row['unique_vehicles'] ?? 0),
        );

        $rows = array();
        $total_rows = 0;
        $total_pages = 1;
        $overview_data = array();

        $search_where = '';
        if (!empty($search)) {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $search_where = $wpdb->prepare(" AND (student_name LIKE %s OR instructor_name LIKE %s OR plate_number LIKE %s)", $like, $like, $like);
        }

        if ('overview' === $active_tab) {
            $overview_data['top_instructors'] = $wpdb->get_results("SELECT instructor_name, COUNT(*) as total_count FROM {$session_table} WHERE {$date_where} AND instructor_name != '' GROUP BY instructor_name ORDER BY total_count DESC LIMIT 5", ARRAY_A);
            $overview_data['recent_completed'] = $wpdb->get_results("SELECT student_name, instructor_name, plate_number, session_number, lesson_topic, scheduled_start, form_data FROM {$session_table} WHERE {$date_where} AND status = 'completed' ORDER BY scheduled_start DESC LIMIT 8", ARRAY_A);
        } elseif ('payroll' === $active_tab) {
            $ins_table = $wpdb->prefix . 'driveflow_instructors';
            $payout_table = $wpdb->prefix . 'driveflow_instructor_payouts';
            $settings = get_option(self::OPTION_KEY, array());
            $default_rate = floatval($settings['instructor_default_hourly_wage'] ?? 35.00);

            $ins_search = $search ? $wpdb->prepare(" WHERE name LIKE %s OR phone LIKE %s OR email LIKE %s", '%' . $wpdb->esc_like($search) . '%', '%' . $wpdb->esc_like($search) . '%', '%' . $wpdb->esc_like($search) . '%') : "";
            $all_instructors = $wpdb->get_results("SELECT * FROM {$ins_table}{$ins_search} ORDER BY name ASC", ARRAY_A);

            $total_hours_all = 0.0;
            $total_earned_all = 0.0;
            $total_paid_all = 0.0;
            $total_balance_all = 0.0;

            $payroll_instructors = array();
            foreach ($all_instructors as $ins) {
                $ins_id = (int)$ins['id'];
                $ins_name = $ins['name'];
                $rate = (!empty($ins['hourly_wage']) && floatval($ins['hourly_wage']) > 0) ? floatval($ins['hourly_wage']) : $default_rate;

                // Completed sessions in period
                $sess_stat = $wpdb->get_row($wpdb->prepare(
                    "SELECT COUNT(*) as comp_count,
                            COALESCE(SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, scheduled_start, scheduled_end) > 0 THEN TIMESTAMPDIFF(MINUTE, scheduled_start, scheduled_end)/60.0 ELSE 2.0 END), 0) as total_hours
                     FROM {$session_table}
                     WHERE (instructor_id = %d OR instructor_name = %s) AND status = 'completed' AND {$date_where}",
                    $ins_id, $ins_name
                ), ARRAY_A);

                // All time sessions for total balance calculation
                $sess_stat_all = $wpdb->get_row($wpdb->prepare(
                    "SELECT COUNT(*) as comp_count,
                            COALESCE(SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, scheduled_start, scheduled_end) > 0 THEN TIMESTAMPDIFF(MINUTE, scheduled_start, scheduled_end)/60.0 ELSE 2.0 END), 0) as total_hours
                     FROM {$session_table}
                     WHERE (instructor_id = %d OR instructor_name = %s) AND status = 'completed'",
                    $ins_id, $ins_name
                ), ARRAY_A);

                $hours_period = round(floatval($sess_stat['total_hours'] ?? 0), 2);
                $comp_period = (int)($sess_stat['comp_count'] ?? 0);
                $earned_period = round($hours_period * $rate, 2);

                $hours_all = round(floatval($sess_stat_all['total_hours'] ?? 0), 2);
                $comp_all = (int)($sess_stat_all['comp_count'] ?? 0);
                $earned_all = round($hours_all * $rate, 2);

                $paid_all = floatval($wpdb->get_var($wpdb->prepare(
                    "SELECT COALESCE(SUM(amount), 0) FROM {$payout_table} WHERE instructor_id = %d AND status IN ('paid', 'archived')",
                    $ins_id
                )));
                $balance_due = max(0, round($earned_all - $paid_all, 2));

                $total_hours_all += $hours_period;
                $total_earned_all += $earned_period;
                $total_paid_all += $paid_all;
                $total_balance_all += $balance_due;

                $payroll_instructors[] = array(
                    'id'              => $ins_id,
                    'name'            => $ins_name,
                    'email'           => $ins['email'],
                    'phone'           => $ins['phone'],
                    'license_number'  => $ins['license_number'],
                    'photo_url'       => $ins['photo_url'],
                    'hourly_rate'     => $rate,
                    'payment_method'  => $ins['payment_method'] ?: 'zelle',
                    'payment_details' => $ins['payment_details'] ?: '',
                    'agreement_signed'=> (bool)$ins['agreement_signed'],
                    'comp_sessions'   => $comp_period,
                    'hours_taught'    => $hours_period,
                    'gross_earned'    => $earned_period,
                    'comp_all'        => $comp_all,
                    'hours_all'       => $hours_all,
                    'earned_all'      => $earned_all,
                    'total_paid'      => $paid_all,
                    'balance_due'     => $balance_due,
                );
            }

            // Payout archive log
            $archive_where = $search ? $wpdb->prepare("WHERE p.instructor_name LIKE %s OR p.reference_number LIKE %s OR p.notes LIKE %s", '%' . $wpdb->esc_like($search) . '%', '%' . $wpdb->esc_like($search) . '%', '%' . $wpdb->esc_like($search) . '%') : "";
            $payouts_archive = $wpdb->get_results("SELECT p.*, i.payment_method as ins_pref_method, i.payment_details as ins_pref_details FROM {$payout_table} p LEFT JOIN {$ins_table} i ON p.instructor_id = i.id {$archive_where} ORDER BY p.payment_date DESC, p.id DESC LIMIT 50", ARRAY_A);

            $rows = $payroll_instructors;
            $total_rows = count($rows);
            $total_pages = 1;

            $overview_data['payroll_summary'] = array(
                'total_hours'     => $total_hours_all,
                'total_earned'    => $total_earned_all,
                'total_paid'      => $total_paid_all,
                'total_balance'   => $total_balance_all,
                'payouts_archive' => $payouts_archive,
            );
        } elseif ('daily' === $active_tab) {
            $count_query = "SELECT COUNT(DISTINCT DATE(scheduled_start)) FROM {$session_table} WHERE {$date_where}{$search_where}";
            $total_rows = (int) $wpdb->get_var($count_query);
            $total_pages = max(1, ceil($total_rows / $per_page));

            $rows = $wpdb->get_results("SELECT 
                DATE(scheduled_start) as session_date,
                COUNT(*) as total_count,
                SUM(status = 'completed') as completed_count,
                SUM(status = 'cancelled') as cancelled_count,
                COUNT(DISTINCT student_name) as students_count,
                COUNT(DISTINCT instructor_name) as instructors_count
                FROM {$session_table}
                WHERE {$date_where}{$search_where}
                GROUP BY DATE(scheduled_start)
                ORDER BY session_date DESC
                LIMIT {$offset}, {$per_page}", ARRAY_A);
        } elseif ('monthly' === $active_tab) {
            $count_query = "SELECT COUNT(DISTINCT DATE_FORMAT(scheduled_start, '%Y-%m')) FROM {$session_table} WHERE {$date_where}{$search_where}";
            $total_rows = (int) $wpdb->get_var($count_query);
            $total_pages = max(1, ceil($total_rows / $per_page));

            $rows = $wpdb->get_results("SELECT 
                DATE_FORMAT(scheduled_start, '%Y-%m') as session_month,
                COUNT(*) as total_count,
                SUM(status = 'completed') as completed_count,
                SUM(status = 'cancelled') as cancelled_count,
                COUNT(DISTINCT student_name) as students_count,
                COUNT(DISTINCT instructor_name) as instructors_count
                FROM {$session_table}
                WHERE {$date_where}{$search_where}
                GROUP BY DATE_FORMAT(scheduled_start, '%Y-%m')
                ORDER BY session_month DESC
                LIMIT {$offset}, {$per_page}", ARRAY_A);
        } elseif ('instructors' === $active_tab) {
            $ins_where = $date_where . ($search ? $wpdb->prepare(" AND s.instructor_name LIKE %s", '%' . $wpdb->esc_like($search) . '%') : '');
            $total_rows = (int) $wpdb->get_var("SELECT COUNT(DISTINCT instructor_name) FROM {$session_table} s WHERE {$ins_where} AND s.instructor_name != ''");
            $total_pages = max(1, ceil($total_rows / $per_page));

            $rows = $wpdb->get_results("SELECT 
                s.instructor_name,
                i.license_number,
                COUNT(*) as total_count,
                SUM(s.status = 'completed') as completed_count
                FROM {$session_table} s
                LEFT JOIN {$wpdb->prefix}driveflow_instructors i ON s.instructor_name = i.name
                WHERE {$ins_where} AND s.instructor_name != ''
                GROUP BY s.instructor_name
                ORDER BY total_count DESC
                LIMIT {$offset}, {$per_page}", ARRAY_A);

            foreach ($rows as &$ins_row) {
                $eval_data = $wpdb->get_results($wpdb->prepare("SELECT form_data FROM {$session_table} WHERE instructor_name = %s AND {$date_where} AND status = 'completed'", $ins_row['instructor_name']), ARRAY_A);
                $ins_eval_count = 0;
                $ins_pass_count = 0;
                foreach ($eval_data as $ed) {
                    if (!empty($ed['form_data'])) {
                        $fd = json_decode($ed['form_data'], true);
                        if (!empty($fd['final_evaluation'])) {
                            $ins_eval_count++;
                            if ('Pass' === $fd['final_evaluation']) $ins_pass_count++;
                        }
                    }
                }
                $ins_row['evaluated_count'] = $ins_eval_count;
                $ins_row['passed_count'] = $ins_pass_count;
            }
            unset($ins_row);
        } elseif ('students' === $active_tab) {
            $st_table = $wpdb->prefix . 'driveflow_students';
            $st_search = $search ? $wpdb->prepare(" WHERE name LIKE %s OR phone LIKE %s OR email LIKE %s OR license_number LIKE %s", '%' . $wpdb->esc_like($search) . '%', '%' . $wpdb->esc_like($search) . '%', '%' . $wpdb->esc_like($search) . '%', '%' . $wpdb->esc_like($search) . '%') : '';
            $total_rows = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$st_table}{$st_search}");
            $total_pages = max(1, ceil($total_rows / $per_page));

            $rows = $wpdb->get_results("SELECT * FROM {$st_table}{$st_search} ORDER BY id DESC LIMIT {$offset}, {$per_page}", ARRAY_A);

            foreach ($rows as &$st_row) {
                $last_date = $wpdb->get_var($wpdb->prepare("SELECT scheduled_start FROM {$session_table} WHERE student_name = %s OR student_id = %d ORDER BY scheduled_start DESC LIMIT 1", $st_row['name'], $st_row['id']));
                $st_row['last_session_date'] = $last_date ?: '';
            }
            unset($st_row);
        } elseif ('vehicles' === $active_tab) {
            $v_table = $wpdb->prefix . 'driveflow_vehicles';
            $v_search = $search ? $wpdb->prepare(" WHERE plate_number LIKE %s OR model LIKE %s OR color LIKE %s", '%' . $wpdb->esc_like($search) . '%', '%' . $wpdb->esc_like($search) . '%', '%' . $wpdb->esc_like($search) . '%') : '';
            $total_rows = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$v_table}{$v_search}");
            $total_pages = max(1, ceil($total_rows / $per_page));

            $rows = $wpdb->get_results("SELECT * FROM {$v_table}{$v_search} ORDER BY id DESC LIMIT {$offset}, {$per_page}", ARRAY_A);

            foreach ($rows as &$v_row) {
                $sess_count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$session_table} WHERE plate_number = %s AND {$date_where}", $v_row['plate_number']));
                $v_row['total_sessions'] = $sess_count;
            }
            unset($v_row);
        }

        if (!empty($_GET['print_view'])) {
            include DRIVEFLOW_PRO_DIR . 'templates/admin-print-report.php';
            exit;
        }

        include DRIVEFLOW_PRO_DIR . 'templates/admin-reports.php';
    }

    public function export_report_csv_action() {
        if (!current_user_can('manage_options') || !check_admin_referer('driveflow_export_report_csv')) {
            wp_die('Unauthorized request.');
        }
        global $wpdb;
        $tab = sanitize_key($_GET['tab'] ?? 'daily');
        $session_table = $this->table();

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="driveflow_report_' . $tab . '_' . date('Y-m-d') . '.csv"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');

        if ('payroll' === $tab) {
            fputcsv($out, array('Date', 'Instructor Name', 'Amount ($)', 'Hours Covered', 'Hourly Rate ($/hr)', 'Payment Method', 'Reference / Check #', 'Period Start', 'Period End', 'Status', 'Notes'));
            $p_table = $wpdb->prefix . 'driveflow_instructor_payouts';
            $payouts = $wpdb->get_results("SELECT * FROM {$p_table} ORDER BY payment_date DESC, id DESC", ARRAY_A);
            foreach ($payouts as $p) {
                fputcsv($out, array(
                    $p['payment_date'],
                    $p['instructor_name'],
                    $p['amount'],
                    $p['hours_paid'],
                    $p['hourly_rate'],
                    strtoupper($p['payment_method']),
                    $p['reference_number'],
                    $p['period_start'] ?: '—',
                    $p['period_end'] ?: '—',
                    strtoupper($p['status']),
                    $p['notes']
                ));
            }
        } elseif ('daily' === $tab) {
            fputcsv($out, array('Date', 'Total Lessons', 'Completed', 'Cancelled', 'Hours Behind Wheel', 'Students Served', 'Instructors Active'));
            $data = $wpdb->get_results("SELECT DATE(scheduled_start) as session_date, COUNT(*) as tot, SUM(status='completed') as comp, SUM(status='cancelled') as canc, COUNT(DISTINCT student_name) as st_cnt, COUNT(DISTINCT instructor_name) as ins_cnt FROM {$session_table} GROUP BY DATE(scheduled_start) ORDER BY session_date DESC", ARRAY_A);
            foreach ($data as $d) {
                fputcsv($out, array($d['session_date'], $d['tot'], $d['comp'], $d['canc'], (int)$d['comp'] * 2, $d['st_cnt'], $d['ins_cnt']));
            }
        } elseif ('monthly' === $tab) {
            fputcsv($out, array('Month', 'Total Lessons', 'Completed', 'Cancelled', 'Hours Behind Wheel', 'Unique Students', 'Unique Instructors'));
            $data = $wpdb->get_results("SELECT DATE_FORMAT(scheduled_start, '%Y-%m') as ym, COUNT(*) as tot, SUM(status='completed') as comp, SUM(status='cancelled') as canc, COUNT(DISTINCT student_name) as st_cnt, COUNT(DISTINCT instructor_name) as ins_cnt FROM {$session_table} GROUP BY ym ORDER BY ym DESC", ARRAY_A);
            foreach ($data as $d) {
                fputcsv($out, array($d['ym'], $d['tot'], $d['comp'], $d['canc'], (int)$d['comp'] * 2, $d['st_cnt'], $d['ins_cnt']));
            }
        } elseif ('instructors' === $tab) {
            fputcsv($out, array('Instructor Name', 'State License #', 'Total Lessons', 'Completed', 'Hours Taught'));
            $data = $wpdb->get_results("SELECT s.instructor_name, i.license_number, COUNT(*) as tot, SUM(s.status='completed') as comp FROM {$session_table} s LEFT JOIN {$wpdb->prefix}driveflow_instructors i ON s.instructor_name = i.name WHERE s.instructor_name != '' GROUP BY s.instructor_name ORDER BY tot DESC", ARRAY_A);
            foreach ($data as $d) {
                fputcsv($out, array($d['instructor_name'], $d['license_number'] ?: 'MVA License', $d['tot'], $d['comp'], (int)$d['comp'] * 2));
            }
        } elseif ('students' === $tab) {
            fputcsv($out, array('Student Name', 'Phone', 'Email', 'Package', 'Total Lessons', 'Completed', 'Remaining Credits'));
            $data = $wpdb->get_results("SELECT name, phone, email, package_name, total_sessions, completed_sessions FROM {$wpdb->prefix}driveflow_students ORDER BY id DESC", ARRAY_A);
            foreach ($data as $d) {
                $rem = max(0, (int)$d['total_sessions'] - (int)$d['completed_sessions']);
                fputcsv($out, array($d['name'], $d['phone'], $d['email'], $d['package_name'], $d['total_sessions'], $d['completed_sessions'], $rem));
            }
        } else {
            fputcsv($out, array('Maryland Plate', 'Model', 'Color', 'Total Lessons', 'Hours Driven'));
            $data = $wpdb->get_results("SELECT v.plate_number, v.model, v.color, COUNT(s.id) as tot FROM {$wpdb->prefix}driveflow_vehicles v LEFT JOIN {$session_table} s ON v.plate_number = s.plate_number GROUP BY v.plate_number ORDER BY tot DESC", ARRAY_A);
            foreach ($data as $d) {
                fputcsv($out, array($d['plate_number'], $d['model'], $d['color'], $d['tot'], (int)$d['tot'] * 2));
            }
        }
        fclose($out);
        exit;
    }

    public function ajax_get_session_details() {
        check_ajax_referer('driveflow_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized.');
        $id = absint($_POST['session_id'] ?? 0);
        global $wpdb;
        $session = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table()} WHERE id = %d", $id), ARRAY_A);
        if (!$session) wp_send_json_error('Session not found.');

        $form_data = !empty($session['form_data']) ? json_decode($session['form_data'], true) : array();
        $session['decoded_form_data'] = $form_data;
        wp_send_json_success($session);
    }

    public function ajax_update_session() {
        check_ajax_referer('driveflow_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized.');
        $id = absint($_POST['session_id'] ?? 0);
        global $wpdb;
        $now = current_time('mysql');
        $session = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table()} WHERE id = %d", $id), ARRAY_A);
        if (!$session) wp_send_json_error('Session not found.');

        $student_name = sanitize_text_field(wp_unslash($_POST['student_name'] ?? $session['student_name']));
        $instructor_name = sanitize_text_field(wp_unslash($_POST['instructor_name'] ?? $session['instructor_name']));
        $raw_plate = sanitize_text_field(wp_unslash($_POST['plate_number'] ?? $session['plate_number']));
        $plate_number = strtoupper(preg_replace('/[^A-Za-z0-9\s\-]/', '', $raw_plate));
        $session_number = max(1, absint($_POST['session_number'] ?? $session['session_number']));
        $lesson_topic = sanitize_text_field(wp_unslash($_POST['lesson_topic'] ?? $session['lesson_topic']));
        $status = sanitize_key($_POST['status'] ?? $session['status']);
        $start = $this->normalize_datetime(wp_unslash($_POST['scheduled_start'] ?? ''), $session['scheduled_start']);
        $end = $this->normalize_datetime(wp_unslash($_POST['scheduled_end'] ?? ''), $session['scheduled_end']);

        // Merge or update form_data (evaluations, notes, pass/fail)
        $form_data = !empty($session['form_data']) ? json_decode($session['form_data'], true) : array();
        if (isset($_POST['final_evaluation'])) {
            $form_data['final_evaluation'] = sanitize_text_field(wp_unslash($_POST['final_evaluation']));
        }
        if (isset($_POST['instructor_notes'])) {
            $form_data['instructor_notes'] = sanitize_textarea_field(wp_unslash($_POST['instructor_notes']));
        }
        if (isset($_POST['instructor_cert_no'])) {
            $form_data['instructor_cert_no'] = sanitize_text_field(wp_unslash($_POST['instructor_cert_no']));
        }

        $update_data = array(
            'student_name'    => $student_name,
            'instructor_name' => $instructor_name,
            'plate_number'    => $plate_number,
            'session_number'  => $session_number,
            'lesson_topic'    => $lesson_topic,
            'scheduled_start' => $start,
            'scheduled_end'   => $end,
            'status'          => in_array($status, array('upcoming','active','completed','cancelled'), true) ? $status : $session['status'],
            'form_data'       => wp_json_encode($form_data),
            'updated_at'      => $now,
        );

        $wpdb->update($this->table(), $update_data, array('id' => $id));

        // Two-way sync: reflect updates or cancellation in Wappointment
        if ('cancelled' === $status) {
            DriveFlow_Wappointment_Sync::cancel_session_in_wapp($id);
        } else {
            DriveFlow_Wappointment_Sync::push_session_to_wapp($id);
        }
        self::notify_calendar_change();
        wp_send_json_success(array('message' => 'Driving session and evaluation records successfully updated by Administrator!'));
    }

    public function ajax_delete_session() {
        check_ajax_referer('driveflow_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized permission.');
        }
        $session_id = absint($_POST['session_id'] ?? 0);
        if (!$session_id) {
            wp_send_json_error('Invalid session ID.');
        }
        global $wpdb;
        $table = $this->table();
        $session = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $session_id), ARRAY_A);
        if (!$session) {
            wp_send_json_error('Session record not found.');
        }

        // If session was completed, restore 1 credit to student if applicable
        $student_id = (int)($session['student_id'] ?? 0);
        if ($student_id && 'completed' === $session['status']) {
            $st_table = $wpdb->prefix . 'driveflow_students';
            $wpdb->query($wpdb->prepare("UPDATE {$st_table} SET completed_sessions = GREATEST(0, completed_sessions - 1) WHERE id = %d", $student_id));
        }

        $deleted = $wpdb->delete($table, array('id' => $session_id), array('%d'));
        if (false === $deleted) {
            wp_send_json_error('Failed to delete session from database.');
        }

        // Two-way sync: cancel in Wappointment if linked
        if (!empty($session['wappointment_id'])) {
            DriveFlow_Wappointment_Sync::cancel_session_in_wapp($session_id);
        }

        self::notify_calendar_change();

        wp_send_json_success(array(
            'message' => sprintf('Session #%d for %s has been permanently deleted.', $session_id, esc_html($session['student_name'] ?: 'Student')),
            'session_id' => $session_id
        ));
    }

    public function ajax_get_tv_sessions() {
        global $wpdb;
        $table = $this->table();
        $today = current_time('Y-m-d');
        $rows = $wpdb->get_results(
            "SELECT s.id, s.student_name, s.instructor_name, s.plate_number, s.session_number, s.lesson_topic, s.scheduled_start, s.scheduled_end, s.status,
                    NULLIF(v.current_fuel_level, 0) AS fuel
             FROM {$table} s
             LEFT JOIN {$wpdb->prefix}driveflow_vehicles v ON (s.vehicle_id = v.id OR (s.plate_number != '' AND s.plate_number = v.plate_number))
             WHERE DATE(s.scheduled_start) = '{$today}' 
               AND s.status IN ('upcoming', 'active', 'completed')
             ORDER BY 
                CASE s.status 
                    WHEN 'active' THEN 1 
                    WHEN 'upcoming' THEN 2 
                    ELSE 3 
                END, 
                s.scheduled_start ASC 
             LIMIT 50",
            ARRAY_A
        );

        $alert = get_option('driveflow_tv_broadcast_alert', array());
        $reload_ts = (int) get_option('driveflow_tv_reload_ts', 0);
        $view_mode = get_option('driveflow_tv_view_mode', 'slots');
        $ticker_items = get_option('driveflow_tv_ticker_items', array());
        if (empty($ticker_items)) {
            $settings = get_option(self::OPTION_KEY, array());
            $default_ticker = $settings['announcement_ticker'] ?? "Welcome to Sam's Driving School LLC • Please have your Maryland learner permit ready • Safe driving is respect for life • Maryland MVA COMAR 11.23 Compliant • All vehicles are dual-control certified";
            $ticker_items = array_values(array_filter(array_map('trim', explode("\n", str_replace('•', "\n", $default_ticker)))));
        }

        $vehicles = $wpdb->get_results("SELECT plate_number, model, color, status FROM {$wpdb->prefix}driveflow_vehicles WHERE status = 'active' ORDER BY plate_number ASC", ARRAY_A);
        $instructors = $wpdb->get_results("SELECT id, name, phone, license_number FROM {$wpdb->prefix}driveflow_instructors WHERE status = 'active' ORDER BY name ASC", ARRAY_A);

        wp_send_json_success(array(
            'sessions'     => $rows ?: array(),
            'alert'        => $alert ?: null,
            'reload_ts'    => $reload_ts,
            'view_mode'    => $view_mode ?: 'slots',
            'ticker_items' => $ticker_items,
            'vehicles'     => $vehicles ?: array(),
            'instructors'  => $instructors ?: array(),
        ));
    }

    public function ajax_get_instructor_today_sessions() {
        global $wpdb;
        $today = current_time('Y-m-d');
        list($is_admin, $me) = $this->instructor_ajax_context();
        $instructor_name = sanitize_text_field(wp_unslash($_GET['instructor_name'] ?? $_POST['instructor_name'] ?? ''));
        if (!$is_admin) {
            $instructor_name = $me['name'];
        }
        
        $table = $this->table();
        $query = "SELECT id, student_name, instructor_name, plate_number, session_number, lesson_topic, scheduled_start, scheduled_end, status 
                  FROM {$table} 
                  WHERE DATE(scheduled_start) = %s 
                    AND status IN ('upcoming', 'active', 'completed')";
        $params = array($today);
        
        if (!empty($instructor_name)) {
            $query .= " AND instructor_name = %s";
            $params[] = $instructor_name;
        }
        
        $query .= " ORDER BY scheduled_start ASC";
        
        $sessions = $wpdb->get_results($wpdb->prepare($query, $params), ARRAY_A);
        
        $formatted = array();
        if (!empty($sessions)) {
            foreach ($sessions as $s) {
                $time_fmt = date_i18n('g:i A', strtotime($s['scheduled_start']));
                $plate = !empty($s['plate_number']) ? strtoupper($s['plate_number']) : '';
                $formatted[] = array(
                    'id'              => (int) $s['id'],
                    'student_name'    => $s['student_name'] ?: 'Enrolled Student',
                    'instructor_name' => $s['instructor_name'] ?: 'Assigned Instructor',
                    'plate_number'    => $plate,
                    'plate_html'      => !empty($plate) ? self::render_maryland_plate($plate, 'sm') : '',
                    'session_number'  => (int) ($s['session_number'] ?: 1),
                    'lesson_topic'    => $s['lesson_topic'] ?: 'Behind-The-Wheel Lesson',
                    'scheduled_start' => $s['scheduled_start'],
                    'scheduled_end'   => $s['scheduled_end'],
                    'time_fmt'        => $time_fmt,
                    'status'          => $s['status'],
                );
            }
        }
        
        wp_send_json_success(array(
            'sessions'             => $formatted,
            'last_calendar_change' => (int) get_option('driveflow_last_calendar_change', 0),
            'server_time'          => current_time('mysql'),
        ));
    }

    public function ajax_admin_control_tv() {
        check_ajax_referer('driveflow_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized access.'));
        }

        $sub_action = sanitize_key($_POST['sub_action'] ?? '');

        switch ($sub_action) {
            case 'push_alert':
                $text = sanitize_textarea_field(wp_unslash($_POST['alert_text'] ?? ''));
                $level = sanitize_key($_POST['alert_level'] ?? 'warning');
                if (empty($text)) {
                    delete_option('driveflow_tv_broadcast_alert');
                    wp_send_json_success(array('message' => 'Broadcast alert cleared.'));
                } else {
                    $alert_data = array(
                        'text' => $text,
                        'level' => in_array($level, array('info', 'warning', 'urgent'), true) ? $level : 'warning',
                        'timestamp' => current_time('timestamp'),
                        'active' => true,
                    );
                    update_option('driveflow_tv_broadcast_alert', $alert_data);
                    wp_send_json_success(array(
                        'message' => 'Alert successfully broadcasted to live TV screens.',
                        'alert' => $alert_data
                    ));
                }
                break;

            case 'clear_alert':
                delete_option('driveflow_tv_broadcast_alert');
                wp_send_json_success(array('message' => 'Live broadcast alert removed from all TV monitors.'));
                break;

            case 'reload_tv':
                $ts = time();
                update_option('driveflow_tv_reload_ts', $ts);
                wp_send_json_success(array(
                    'message' => 'Remote reload command transmitted. All active TV displays will refresh.',
                    'reload_ts' => $ts
                ));
                break;

            case 'set_view_mode':
                $mode = sanitize_key($_POST['view_mode'] ?? 'slots');
                if (!in_array($mode, array('slots', 'vehicles', 'instructors'), true)) {
                    $mode = 'slots';
                }
                update_option('driveflow_tv_view_mode', $mode);
                wp_send_json_success(array(
                    'message' => 'TV display mode switched to: ' . ucfirst($mode),
                    'view_mode' => $mode
                ));
                break;

            case 'save_ticker_items':
                $raw_lines = wp_unslash($_POST['ticker_text'] ?? '');
                $lines = array_values(array_filter(array_map('sanitize_text_field', array_map('trim', explode("\n", $raw_lines)))));
                if (empty($lines)) {
                    $lines = array(
                        "Welcome to Sam's Driving School LLC",
                        "Please have your Maryland learner permit ready",
                        "Safe driving is respect for life",
                        "Maryland MVA COMAR 11.23 Compliant",
                        "All vehicles are dual-control certified"
                    );
                }
                update_option('driveflow_tv_ticker_items', $lines);
                $settings = get_option(self::OPTION_KEY, array());
                $settings['announcement_ticker'] = implode(" • ", $lines);
                update_option(self::OPTION_KEY, $settings);

                wp_send_json_success(array(
                    'message' => 'Announcement ticker headlines updated successfully.',
                    'ticker_items' => $lines
                ));
                break;

            default:
                wp_send_json_error(array('message' => 'Unknown TV commander action.'));
                break;
        }
    }

    public function ajax_update_student() {
        check_ajax_referer('driveflow_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized.');
        $id = absint($_POST['student_id'] ?? 0);
        global $wpdb;
        $now = current_time('mysql');

        $old_student_name = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}driveflow_students WHERE id = %d", $id));

        $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
        $phone = sanitize_text_field(wp_unslash($_POST['phone'] ?? ''));
        $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
        $license_number = sanitize_text_field(wp_unslash($_POST['license_number'] ?? ''));
        $package_name = sanitize_text_field(wp_unslash($_POST['package_name'] ?? ''));
        $total_sessions = max(1, absint($_POST['total_sessions'] ?? 6));
        $completed_sessions = max(0, absint($_POST['completed_sessions'] ?? 0));

        $wpdb->update($wpdb->prefix . 'driveflow_students', array(
            'name'               => $name,
            'phone'              => $phone,
            'email'              => $email,
            'license_number'     => $license_number,
            'package_name'       => $package_name,
            'total_sessions'     => $total_sessions,
            'completed_sessions' => $completed_sessions,
            'updated_at'         => $now,
        ), array('id' => $id));

        // Cascade updated student name to all driving sessions
        if (!empty($old_student_name) && !empty($name) && $old_student_name !== $name) {
            $wpdb->update($this->table(), array('student_name' => $name), array('student_id' => $id));
            $wpdb->update($this->table(), array('student_name' => $name), array('student_name' => $old_student_name));
        }

        wp_send_json_success(array('message' => 'Student course package and credentials updated successfully.'));
    }

    public function ajax_get_entity() {
        check_ajax_referer('driveflow_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized.');
        $entity = sanitize_key($_POST['entity'] ?? '');
        $id = absint($_POST['id'] ?? 0);
        if (!in_array($entity, array('instructors', 'vehicles'), true) || !$id) {
            wp_send_json_error('Invalid parameters.');
        }
        global $wpdb;
        $table = $wpdb->prefix . 'driveflow_' . $entity;
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id), ARRAY_A);
        if (!$row) wp_send_json_error('Record not found.');
        wp_send_json_success($row);
    }

    public function ajax_update_entity() {
        check_ajax_referer('driveflow_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized.');
        $entity = sanitize_key($_POST['entity'] ?? '');
        $id = absint($_POST['id'] ?? 0);
        if (!in_array($entity, array('instructors', 'vehicles'), true) || !$id) {
            wp_send_json_error('Invalid parameters.');
        }
        global $wpdb;
        $now = current_time('mysql');
        $table = $wpdb->prefix . 'driveflow_' . $entity;

        if ('vehicles' === $entity) {
            $old_plate = $wpdb->get_var($wpdb->prepare("SELECT plate_number FROM {$table} WHERE id = %d", $id));
            $raw_plate = sanitize_text_field(wp_unslash($_POST['plate_number'] ?? ''));
            $clean_plate = strtoupper(preg_replace('/[^A-Za-z0-9\s\-]/', '', $raw_plate));
            $data = array(
                'plate_number' => $clean_plate,
                'model'        => sanitize_text_field(wp_unslash($_POST['model'] ?? '')),
                'color'        => sanitize_text_field(wp_unslash($_POST['color'] ?? '')),
                'status'       => sanitize_key($_POST['status'] ?? 'active'),
                'updated_at'   => $now,
            );

            $wpdb->update($table, $data, array('id' => $id));

            // CRITICAL CASCADING SYNC: When a plate number changes, cascade to all sessions and blocked slots
            if (!empty($old_plate) && !empty($clean_plate) && $old_plate !== $clean_plate) {
                $wpdb->update($this->table(), array('plate_number' => $clean_plate), array('plate_number' => $old_plate));
                $wpdb->update($wpdb->prefix . 'driveflow_blocked_slots', array('target_identifier' => $clean_plate), array('target_type' => 'vehicle', 'target_identifier' => $old_plate));
            }
        } else {
            $old_name = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$table} WHERE id = %d", $id));
            $data = array(
                'name'           => sanitize_text_field(wp_unslash($_POST['name'] ?? '')),
                'phone'          => sanitize_text_field(wp_unslash($_POST['phone'] ?? '')),
                'email'          => sanitize_email(wp_unslash($_POST['email'] ?? '')),
                'license_number' => sanitize_text_field(wp_unslash($_POST['license_number'] ?? '')),
                'status'         => sanitize_key($_POST['status'] ?? 'active'),
                'updated_at'     => $now,
            );
            if (isset($_POST['photo_url'])) {
                $data['photo_url'] = esc_url_raw(wp_unslash($_POST['photo_url']));
            }
            if (isset($_POST['id_card_url'])) {
                $data['id_card_url'] = esc_url_raw(wp_unslash($_POST['id_card_url']));
            }
            if (isset($_POST['badge_url'])) {
                $data['badge_url'] = esc_url_raw(wp_unslash($_POST['badge_url']));
            }
            if (isset($_POST['bio'])) {
                $data['bio'] = sanitize_textarea_field(wp_unslash($_POST['bio']));
            }
            if (isset($_POST['hourly_wage'])) {
                $wage = floatval($_POST['hourly_wage']);
                $data['hourly_wage'] = $wage;
                $data['agreement_wage'] = number_format($wage, 2);
            }
            if (isset($_POST['payment_method'])) {
                $data['payment_method'] = sanitize_key($_POST['payment_method']);
            }
            if (isset($_POST['payment_details'])) {
                $data['payment_details'] = sanitize_textarea_field(wp_unslash($_POST['payment_details']));
            }

            $wpdb->update($table, $data, array('id' => $id));

            // CRITICAL CASCADING SYNC: When an instructor name changes, cascade to sessions, payouts, and blocked slots
            if (!empty($old_name) && !empty($data['name']) && $old_name !== $data['name']) {
                $new_name = $data['name'];
                $wpdb->update($this->table(), array('instructor_name' => $new_name), array('instructor_name' => $old_name));
                $wpdb->update($wpdb->prefix . 'driveflow_instructor_payouts', array('instructor_name' => $new_name), array('instructor_id' => $id));
                $wpdb->update($wpdb->prefix . 'driveflow_blocked_slots', array('target_identifier' => $new_name), array('target_type' => 'instructor', 'target_identifier' => $old_name));
                DriveFlow_Wappointment_Sync::sync_instructors_to_wapp_staff();
            }
        }

        wp_send_json_success(array('message' => 'Record successfully updated.'));
    }

    public function ajax_record_instructor_payout() {
        check_ajax_referer('driveflow_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized.');
        global $wpdb;

        $instructor_id = absint($_POST['instructor_id'] ?? 0);
        $amount = round(floatval($_POST['amount'] ?? 0), 2);
        $hours_paid = round(floatval($_POST['hours_paid'] ?? 0), 2);
        $payment_date = sanitize_text_field(wp_unslash($_POST['payment_date'] ?? ''));
        $payment_method = sanitize_key($_POST['payment_method'] ?? 'zelle');
        $reference_number = sanitize_text_field(wp_unslash($_POST['reference_number'] ?? ''));
        $period_start = !empty($_POST['period_start']) ? sanitize_text_field(wp_unslash($_POST['period_start'])) : null;
        $period_end = !empty($_POST['period_end']) ? sanitize_text_field(wp_unslash($_POST['period_end'])) : null;
        $notes = sanitize_textarea_field(wp_unslash($_POST['notes'] ?? ''));

        if (!$instructor_id) {
            wp_send_json_error('Please select an instructor.');
        }
        if ($amount <= 0) {
            wp_send_json_error('Payment amount must be greater than $0.00.');
        }
        if (empty($payment_date)) {
            $payment_date = current_time('Y-m-d');
        }

        $ins = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}driveflow_instructors WHERE id = %d", $instructor_id), ARRAY_A);
        if (!$ins) {
            wp_send_json_error('Instructor record was not found.');
        }

        $settings = get_option(self::OPTION_KEY, array());
        $hourly_rate = (!empty($ins['hourly_wage']) && floatval($ins['hourly_wage']) > 0) ? floatval($ins['hourly_wage']) : floatval($settings['instructor_default_hourly_wage'] ?? 35.00);
        if ($hours_paid <= 0 && $hourly_rate > 0) {
            $hours_paid = round($amount / $hourly_rate, 2);
        }

        $now = current_time('mysql');
        $payout_table = $wpdb->prefix . 'driveflow_instructor_payouts';

        $insert_data = array(
            'instructor_id'    => $instructor_id,
            'instructor_name'  => $ins['name'],
            'amount'           => $amount,
            'hours_paid'       => $hours_paid,
            'hourly_rate'      => $hourly_rate,
            'payment_date'     => $payment_date,
            'payment_method'   => $payment_method,
            'reference_number' => $reference_number,
            'period_start'     => $period_start,
            'period_end'       => $period_end,
            'notes'            => $notes,
            'status'           => 'paid',
            'created_by'       => get_current_user_id(),
            'created_at'       => $now,
            'updated_at'       => $now,
        );

        $inserted = $wpdb->insert($payout_table, $insert_data);

        if (!$inserted) {
            wp_send_json_error('Failed to record payout transaction: ' . $wpdb->last_error);
        }

        $payout_id = $wpdb->insert_id;

        // Recalculate new total paid and balance
        $sess_table = $this->table();
        $sess_stat = $wpdb->get_row($wpdb->prepare(
            "SELECT COUNT(*) as comp_count, 
                    COALESCE(SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, scheduled_start, scheduled_end) > 0 THEN TIMESTAMPDIFF(MINUTE, scheduled_start, scheduled_end)/60.0 ELSE 2.0 END), 0) as total_hours 
             FROM {$sess_table} 
             WHERE (instructor_id = %d OR instructor_name = %s) AND status = 'completed'",
            $instructor_id, $ins['name']
        ), ARRAY_A);

        $total_hours = round(floatval($sess_stat['total_hours'] ?? 0), 2);
        $gross_earned = round($total_hours * $hourly_rate, 2);
        $total_paid = floatval($wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM {$payout_table} WHERE instructor_id = %d AND status IN ('paid', 'archived')",
            $instructor_id
        )));
        $new_balance = max(0, round($gross_earned - $total_paid, 2));

        wp_send_json_success(array(
            'message'        => sprintf('Successfully disbursed and recorded $%s payment to %s.', number_format($amount, 2), esc_html($ins['name'])),
            'payout_id'      => $payout_id,
            'instructor_id'  => $instructor_id,
            'amount_paid'    => $amount,
            'amount_paid_fmt'=> '$' . number_format($amount, 2),
            'total_paid'     => $total_paid,
            'total_paid_fmt' => '$' . number_format($total_paid, 2),
            'new_balance'    => $new_balance,
            'new_balance_fmt'=> '$' . number_format($new_balance, 2)
        ));
    }

    public function ajax_get_instructor_payout_history() {
        check_ajax_referer('driveflow_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized.');
        global $wpdb;

        $instructor_id = absint($_POST['instructor_id'] ?? 0);
        $payout_table = $wpdb->prefix . 'driveflow_instructor_payouts';

        $where = $instructor_id ? $wpdb->prepare("WHERE p.instructor_id = %d", $instructor_id) : "";
        $query = "SELECT p.*, i.name as current_ins_name, i.hourly_wage, i.payment_method as ins_method, i.payment_details 
                  FROM {$payout_table} p
                  LEFT JOIN {$wpdb->prefix}driveflow_instructors i ON p.instructor_id = i.id
                  {$where}
                  ORDER BY p.payment_date DESC, p.id DESC LIMIT 100";

        $rows = $wpdb->get_results($query, ARRAY_A);

        $settings = get_option(self::OPTION_KEY, array());
        $school_name = $settings['school_name'] ?? get_bloginfo('name') ?: 'DriveFlow Academy';

        foreach ($rows as &$r) {
            $r['amount_fmt'] = '$' . number_format(floatval($r['amount']), 2);
            $r['rate_fmt'] = '$' . number_format(floatval($r['hourly_rate']), 2) . '/hr';
            $r['hours_fmt'] = number_format(floatval($r['hours_paid']), 1) . ' hrs';
            $r['date_fmt'] = date_i18n('M j, Y', strtotime($r['payment_date']));
            $r['school_name'] = $school_name;
        }
        unset($r);

        wp_send_json_success(array('payouts' => $rows));
    }

    public function ajax_delete_instructor_payout() {
        check_ajax_referer('driveflow_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized.');
        global $wpdb;

        $payout_id = absint($_POST['payout_id'] ?? 0);
        if (!$payout_id) wp_send_json_error('Invalid payout ID.');

        $payout_table = $wpdb->prefix . 'driveflow_instructor_payouts';
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$payout_table} WHERE id = %d", $payout_id), ARRAY_A);
        if (!$row) wp_send_json_error('Payout record not found.');

        $wpdb->delete($payout_table, array('id' => $payout_id));

        wp_send_json_success(array(
            'message' => 'Payout transaction record successfully removed and balance reverted.',
            'instructor_id' => $row['instructor_id']
        ));
    }

    public function ajax_quick_status() {
        check_ajax_referer('driveflow_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied.');
        }
        $session_id = absint($_POST['session_id'] ?? 0);
        $status = sanitize_key($_POST['status'] ?? '');
        if (!$session_id || !in_array($status, array('upcoming', 'active', 'completed', 'cancelled'), true)) {
            wp_send_json_error('Invalid parameters.');
        }
        global $wpdb;
        $updated = $wpdb->update(
            $this->table(),
            array('status' => $status, 'updated_at' => current_time('mysql')),
            array('id' => $session_id),
            array('%s', '%s'),
            array('%d')
        );
        if (false === $updated) {
            wp_send_json_error('Database error.');
        }

        if ('completed' === $status) {
            $session = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table()} WHERE id = %d", $session_id));
            if ($session) {
                $st_id = (int) $session->student_id;
                if (!$st_id && !empty($session->student_name)) {
                    $st_id = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}driveflow_students WHERE name = %s", $session->student_name));
                }
                if ($st_id) {
                    DriveFlow_Credit_Manager::deduct_session_credit($st_id, $session_id);
                }
            }
            DriveFlow_Email_Manager::send_student_completion($session_id);
        } elseif ('cancelled' === $status) {
            DriveFlow_Email_Manager::send_cancellation($session_id);
        }

        // Two-way sync: reflect status change in Wappointment
        if ('cancelled' === $status) {
            DriveFlow_Wappointment_Sync::cancel_session_in_wapp($session_id);
        } else {
            DriveFlow_Wappointment_Sync::push_session_to_wapp($session_id);
        }

        wp_send_json_success(array('session_id' => $session_id, 'status' => $status));
    }

    public function ajax_sync_wappointment() {
        check_ajax_referer('driveflow_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied.');
        }
        // Auto-sync active instructors to Wappointment staff
        DriveFlow_Wappointment_Sync::sync_instructors_to_wapp_staff();
        $result = DriveFlow_Wappointment_Sync::sync_all();
        wp_send_json_success($result);
    }

    public function ajax_send_test_email() {
        check_ajax_referer('driveflow_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied.'));
        }
        $email = sanitize_email($_POST['email'] ?? '');
        if (!$email) {
            wp_send_json_error(array('message' => 'Invalid email address provided.'));
        }
        $sent = DriveFlow_Email_Manager::send_test_email($email);
        if ($sent) {
            wp_send_json_success(array('message' => "Test email successfully delivered to {$email}!"));
        } else {
            wp_send_json_error(array('message' => 'WordPress mail delivery failed. Please verify your host SMTP settings.'));
        }
    }

    public function ajax_resend_email() {
        check_ajax_referer('driveflow_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied.'));
        }
        $session_id = absint($_POST['session_id'] ?? 0);
        if (!$session_id) {
            wp_send_json_error(array('message' => 'Invalid session ID.'));
        }
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table()} WHERE id = %d", $session_id), ARRAY_A);
        if (!$row) {
            wp_send_json_error(array('message' => 'Session not found.'));
        }

        if ('completed' === $row['status']) {
            DriveFlow_Email_Manager::send_student_completion($session_id);
            wp_send_json_success(array('message' => 'Completion & Scorecard email resent to student ✓'));
        } else {
            DriveFlow_Email_Manager::send_student_booking($session_id);
            DriveFlow_Email_Manager::send_instructor_booking($session_id);
            wp_send_json_success(array('message' => 'Lesson schedule notification email resent to student & instructor ✓'));
        }
    }

    public function seed_demo_data() {
        if (!current_user_can('manage_options') || !check_admin_referer('driveflow_seed_demo')) wp_die('Unauthorized request.');
        global $wpdb;
        $now = current_time('mysql');

        $wpdb->insert($wpdb->prefix . 'driveflow_students', array('name' => 'Alex Morgan', 'phone' => '555-0142', 'email' => 'alex.morgan@example.com', 'license_number' => 'MD-ST-8812', 'package_name' => 'Full License Course (10 Lessons)', 'total_sessions' => 10, 'completed_sessions' => 1, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now), array('%s','%s','%s','%s','%s','%d','%d','%s','%s','%s'));
        $alex_id = $wpdb->insert_id;
        if ($alex_id) {
            DriveFlow_Credit_Manager::add_extra_sessions($alex_id, 3, 'Promo Package +3 Extra Highway Lessons');
        }

        $wpdb->insert($wpdb->prefix . 'driveflow_students', array('name' => 'Sarah Jenkins', 'phone' => '555-0188', 'email' => 'sarah.j@example.com', 'license_number' => 'MD-ST-9941', 'package_name' => 'Starter 5-Lesson Course', 'total_sessions' => 5, 'completed_sessions' => 0, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now), array('%s','%s','%s','%s','%s','%d','%d','%s','%s','%s'));
        $wpdb->insert($wpdb->prefix . 'driveflow_students', array('name' => 'David Miller', 'phone' => '555-0210', 'email' => 'david.m@example.com', 'license_number' => 'MD-ST-3305', 'package_name' => 'Intensive 12-Lesson Package', 'total_sessions' => 12, 'completed_sessions' => 4, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now), array('%s','%s','%s','%s','%s','%d','%d','%s','%s','%s'));

        $marcus_hours = wp_json_encode(array(
            'mon' => array('start' => '08:00', 'end' => '17:00'),
            'tue' => array('start' => '08:00', 'end' => '17:00'),
            'wed' => array('start' => '08:00', 'end' => '17:00'),
            'thu' => array('start' => '08:00', 'end' => '17:00'),
            'fri' => array('start' => '08:00', 'end' => '16:00'),
            'sat' => array('closed' => 1),
            'sun' => array('closed' => 1),
        ));
        $wpdb->insert($wpdb->prefix . 'driveflow_instructors', array('name' => 'Marcus Vance (Senior Instructor)', 'phone' => '555-0901', 'email' => 'marcus@example.com', 'license_number' => 'INS-MD-101', 'working_hours' => $marcus_hours, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now), array('%s','%s','%s','%s','%s','%s','%s','%s'));
        $wpdb->insert($wpdb->prefix . 'driveflow_instructors', array('name' => 'Rachel Adams', 'phone' => '555-0902', 'email' => 'rachel@example.com', 'license_number' => 'INS-MD-102', 'working_hours' => $marcus_hours, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now), array('%s','%s','%s','%s','%s','%s','%s','%s'));

        $wpdb->insert($wpdb->prefix . 'driveflow_vehicles', array('plate_number' => 'BAY-7892', 'model' => 'Toyota Corolla (Automatic)', 'color' => 'Silver', 'transmission' => 'Automatic', 'status' => 'active', 'created_at' => $now, 'updated_at' => $now), array('%s','%s','%s','%s','%s','%s','%s'));
        $wpdb->insert($wpdb->prefix . 'driveflow_vehicles', array('plate_number' => 'MD-CRAB-01', 'model' => 'Honda Civic (Manual)', 'color' => 'Blue', 'transmission' => 'Manual', 'status' => 'active', 'created_at' => $now, 'updated_at' => $now), array('%s','%s','%s','%s','%s','%s','%s'));

        $demo_skills = array('clutch' => 5, 'parking' => 4, 'steering' => 5, 'rules' => 5);

        $samples = array(
            array('Alex Morgan', 'Marcus Vance (Senior Instructor)', 'MD-CRAB-01', 1, 'Clutch Control & Hill Starts', 'active'),
            array('Sarah Jenkins', 'Rachel Adams', 'BAY-7892', 3, 'Parallel Parking & Reversing', 'upcoming'),
            array('David Miller', 'Marcus Vance (Senior Instructor)', 'BAY-7892', 5, 'Highway & Defensive Driving', 'upcoming'),
            array('Emma Watson', 'Rachel Adams', 'MD-CRAB-01', 2, 'City Intersections & Roundabouts', 'completed'),
        );
        foreach ($samples as $sample) {
            $wpdb->insert($this->table(), array(
                'student_name' => $sample[0],
                'instructor_name' => $sample[1],
                'plate_number' => $sample[2],
                'session_number' => $sample[3],
                'lesson_topic' => $sample[4],
                'scheduled_start' => $now,
                'scheduled_end' => date('Y-m-d H:i:s', strtotime($now . ' +2 hours')),
                'status' => $sample[5],
                'form_data' => wp_json_encode(array(
                    'demo' => true,
                    'skills' => $demo_skills,
                    'instructor_notes' => 'Outstanding mirror checks and clutch control. Ready for complex urban intersections.'
                )),
                'signature' => '',
                'selfie' => '',
                'created_by' => get_current_user_id(),
                'created_at' => $now,
                'updated_at' => $now,
            ), array('%s','%s','%s','%d','%s','%s','%s','%s','%s','%s','%s','%d','%s','%s'));
        }
        wp_safe_redirect(admin_url('admin.php?page=driveflow-pro-records&seeded=1')); exit;
    }

    public function clear_demo_data() {
        if (!current_user_can('manage_options') || !check_admin_referer('driveflow_clear_demo')) wp_die('Unauthorized request.');
        global $wpdb;
        $wpdb->query("DELETE FROM {$this->table()} WHERE form_data LIKE '%\"demo\":true%'");
        $wpdb->query("DELETE FROM {$wpdb->prefix}driveflow_students WHERE license_number LIKE 'MD-ST-%'");
        $wpdb->query("DELETE FROM {$wpdb->prefix}driveflow_instructors WHERE license_number LIKE 'INS-MD-%'");
        $wpdb->query("DELETE FROM {$wpdb->prefix}driveflow_vehicles WHERE plate_number IN ('BAY-7892', 'MD-CRAB-01')");
        wp_safe_redirect(admin_url('admin.php?page=driveflow-pro-records&cleared=1')); exit;
    }

    private function normalize_datetime($value, $fallback) {
        $value = str_replace('T', ' ', sanitize_text_field($value));
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}(:\d{2})?$/', $value)) {
            return (16 === strlen($value)) ? $value . ':00' : $value;
        }
        return $fallback;
    }

    private function branding() {
        $settings = get_option(self::OPTION_KEY, array());
        return array(
            'name' => sanitize_text_field($settings['school_name'] ?? 'DriveFlow Academy'),
            'logo' => esc_url($settings['logo_url'] ?? ''),
            'ticker' => sanitize_text_field($settings['announcement_ticker'] ?? ''),
        );
    }

    public function instructor_form() {
        static $rendered = false;
        if ($rendered) return '';
        $rendered = true;

        if (!is_user_logged_in()) {
            return $this->instructor_login_card();
        }
        $is_admin_view      = current_user_can('manage_options');
        $current_instructor = $this->get_current_instructor();
        if (!$is_admin_view && (!$current_instructor || 'active' !== $current_instructor['status'] || !current_user_can('edit_posts'))) {
            return '<div class="df-login-card" style="max-width:420px;margin:24px auto;padding:20px;border:1px solid #fecaca;border-radius:12px;background:#fef2f2;color:#991b1b;">Your account is not linked to an active instructor profile. Please contact the school administrator.</div>';
        }
        $locked   = !$is_admin_view && $current_instructor;
        $df_nonce = wp_create_nonce('driveflow_instructor_form');
        if (!defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', true);
        }

        global $wpdb;
        $brand = $this->branding();
        $instructors = $wpdb->get_results("SELECT id, name, phone, email, license_number FROM {$wpdb->prefix}driveflow_instructors WHERE status = 'active' ORDER BY name ASC", ARRAY_A);
        $vehicles = $wpdb->get_results("SELECT plate_number, model FROM {$wpdb->prefix}driveflow_vehicles WHERE status = 'active' ORDER BY plate_number ASC", ARRAY_A);
        $students = $wpdb->get_results("SELECT id, name, phone, email, license_number, total_sessions, completed_sessions, (total_sessions - completed_sessions) as remaining_sessions FROM {$wpdb->prefix}driveflow_students WHERE status = 'active' ORDER BY name ASC", ARRAY_A);
        $today_sessions = $wpdb->get_results(
            "SELECT id, student_name, instructor_name, plate_number, session_number, lesson_topic, scheduled_start, scheduled_end, status 
             FROM {$this->table()} 
             WHERE DATE(scheduled_start) = CURDATE() AND status IN ('upcoming', 'active') 
             ORDER BY scheduled_start ASC",
            ARRAY_A
        );

        if ($locked) {
            $instructors    = array($current_instructor);
            $today_sessions = array_values(array_filter((array) $today_sessions, function ($row) use ($current_instructor) {
                return 0 === strcasecmp((string) $row['instructor_name'], (string) $current_instructor['name']);
            }));
        }

        ob_start();
        include DRIVEFLOW_PRO_DIR . 'templates/instructor-form.php';
        return ob_get_clean();
    }

    public function tv_board($force = false) {
        static $rendered = false;
        if (!$force && $rendered) return '';
        $rendered = true;

        global $wpdb;
        $brand = $this->branding();
        $settings = get_option(self::OPTION_KEY, array());
        $today = current_time('Y-m-d');
        $today_sessions = $wpdb->get_results(
            "SELECT s.id, s.student_name, s.instructor_name, s.plate_number, s.session_number, s.lesson_topic, s.scheduled_start, s.scheduled_end, s.status,
                    NULLIF(v.current_fuel_level, 0) AS fuel
             FROM {$this->table()} s
             LEFT JOIN {$wpdb->prefix}driveflow_vehicles v ON (s.vehicle_id = v.id OR (s.plate_number != '' AND s.plate_number = v.plate_number))
             WHERE DATE(s.scheduled_start) = '{$today}' 
               AND s.status IN ('upcoming', 'active', 'completed')
             ORDER BY 
                CASE s.status 
                    WHEN 'active' THEN 1 
                    WHEN 'upcoming' THEN 2 
                    ELSE 3 
                END, 
                s.scheduled_start ASC 
             LIMIT 50",
            ARRAY_A
        );

        $alert = get_option('driveflow_tv_broadcast_alert', null);
        $reload_ts = (int) get_option('driveflow_tv_reload_ts', 0);
        $view_mode = get_option('driveflow_tv_view_mode', 'slots');
        $ticker_items = get_option('driveflow_tv_ticker_items', array());
        if (empty($ticker_items)) {
            $default_ticker = $settings['announcement_ticker'] ?? "Welcome to Sam's Driving School LLC • Please have your Maryland learner permit ready • Safe driving is respect for life • Maryland MVA COMAR 11.23 Compliant • All vehicles are dual-control certified";
            $ticker_items = array_values(array_filter(array_map('trim', explode("\n", str_replace('•', "\n", $default_ticker)))));
        }

        $vehicles = $wpdb->get_results("SELECT plate_number, model, color, status FROM {$wpdb->prefix}driveflow_vehicles WHERE status = 'active' ORDER BY plate_number ASC", ARRAY_A);
        $instructors = $wpdb->get_results("SELECT id, name, phone, license_number FROM {$wpdb->prefix}driveflow_instructors WHERE status = 'active' ORDER BY name ASC", ARRAY_A);

        ob_start();
        ?>
        <script>
        window.DriveFlowPro = Object.assign(window.DriveFlowPro || {}, <?php echo wp_json_encode(array(
            'ajaxurl'         => admin_url('admin-ajax.php'),
            'tvApiUrl'        => esc_url_raw(rest_url(self::REST_NAMESPACE . '/tv-sessions')),
            'tvApiKey'        => sanitize_text_field($settings['tv_api_key'] ?? ''),
            'pollSeconds'     => max(5, absint($settings['api_poll_seconds'] ?? 15)),
            'ticker'          => sanitize_text_field($settings['announcement_ticker'] ?? ''),
            'tickerItems'     => $ticker_items,
            'initialSessions' => $today_sessions ?: array(),
            'initialAlert'    => $alert,
            'initialViewMode' => $view_mode ?: 'slots',
            'initialReloadTs' => $reload_ts,
            'vehicles'        => $vehicles ?: array(),
            'instructors'     => $instructors ?: array(),
        )); ?>);
        </script>
        <?php
        include DRIVEFLOW_PRO_DIR . 'templates/tv-board.php';
        return ob_get_clean();
    }

    public function handle_standalone_portals() {
        if (is_admin() || isset($_GET['elementor-preview'])) {
            return;
        }

        // 1. Dedicated Executive Admin Login & Operations Portal
        if (isset($_GET['driveflow_admin']) || isset($_GET['df_admin'])) {
            $this->handle_standalone_admin_portal();
            exit;
        }

        $settings = get_option(self::OPTION_KEY, array());
        $tv_page_id = absint($settings['tv_page_id'] ?? 24);

        $current_id = 0;
        if (function_exists('get_queried_object_id')) {
            $current_id = absint(get_queried_object_id());
        }
        if (!$current_id && isset($_GET['page_id'])) {
            $current_id = absint($_GET['page_id']);
        }
        if (!$current_id && isset($_GET['p'])) {
            $current_id = absint($_GET['p']);
        }

        $post_obj = ($current_id > 0) ? get_post($current_id) : null;
        $content = $post_obj ? ($post_obj->post_content ?? '') : '';
        $slug = $post_obj ? strtolower($post_obj->post_name ?? '') : '';

        // 2. TV Lobby Board (Dedicated Kiosk)
        $is_tv_page = ($tv_page_id > 0) && ($current_id === $tv_page_id);
        $is_page_24 = (24 === $current_id) || (isset($_GET['page_id']) && 24 === absint($_GET['page_id']));
        $has_tv_sc = false;
        $tv_slug_match = false;
        if ($post_obj && !empty($content)) {
            $has_tv_sc = has_shortcode($content, 'driveflow_tv_board') 
                      || has_shortcode($content, 'samds_tv_board')
                      || (false !== strpos($content, 'driveflow_tv_board'))
                      || (false !== strpos($content, 'samds_tv_board'));
            $tv_slug_match = (false !== strpos($slug, 'driveflow-tv')) 
                          || (false !== strpos($slug, 'samds-tv'))
                          || (false !== strpos($slug, 'lobby-board'))
                          || (false !== strpos($slug, 'tv-board'))
                          || (false !== strpos($slug, 'tv-lobby'))
                          || ('tv' === $slug);
        }

        $is_tv = isset($_GET['driveflow_tv']) || 
                 isset($_GET['tv_kiosk']) || 
                 isset($_GET['df_kiosk']) || 
                 isset($_GET['kiosk']) ||
                 $is_page_24 ||
                 $is_tv_page || 
                 $has_tv_sc ||
                 $tv_slug_match;

        if ($is_tv) {
            $this->render_standalone_tv_board();
            exit;
        }

        // 3. Instructor Onboarding Portal
        $onboarding_page_id = absint($settings['instructor_onboarding_page_id'] ?? 0);
        $is_onboarding = isset($_GET['driveflow_onboarding']) ||
                         ($onboarding_page_id > 0 && $current_id === $onboarding_page_id) ||
                         ($post_obj && has_shortcode($content, 'driveflow_instructor_onboarding')) ||
                         (false !== strpos($slug, 'instructor-onboarding'));
        if ($is_onboarding) {
            $this->render_standalone_portal('Instructor Onboarding', $this->instructor_onboarding_shortcode());
            exit;
        }

        // 4. Instructor In-Car Evaluation App
        $instructor_page_id = absint($settings['instructor_page_id'] ?? 0);
        $is_instructor = isset($_GET['driveflow_instructor']) ||
                         ($instructor_page_id > 0 && $current_id === $instructor_page_id) ||
                         ($post_obj && (has_shortcode($content, 'driveflow_instructor_form') || has_shortcode($content, 'samds_instructor_form'))) ||
                         (false !== strpos($slug, 'instructor-session'));
        if ($is_instructor) {
            $this->render_standalone_portal('Instructor In-Car Evaluation App', $this->instructor_form());
            exit;
        }

        // 5. Student Driving Portal
        $student_page_id = absint($settings['student_portal_page_id'] ?? 0);
        $is_student = isset($_GET['driveflow_student']) ||
                      ($student_page_id > 0 && $current_id === $student_page_id) ||
                      ($post_obj && has_shortcode($content, 'driveflow_student_portal')) ||
                      (false !== strpos($slug, 'student-driving-portal'));
        if ($is_student) {
            $this->render_standalone_portal('Student Driving Portal', $this->student_portal_shortcode());
            exit;
        }

        // 6. Magic Booking Link
        $booking_page_id = absint($settings['magic_booking_page_id'] ?? 0);
        $is_booking = isset($_GET['driveflow_booking']) ||
                      ($booking_page_id > 0 && $current_id === $booking_page_id) ||
                      ($post_obj && has_shortcode($content, 'driveflow_magic_booking')) ||
                      (false !== strpos($slug, 'book-driving-lesson'));
        if ($is_booking) {
            $this->render_standalone_portal('Book Driving Lesson', $this->magic_booking_shortcode(array()));
            exit;
        }
    }

    private function render_standalone_portal($title, $content_html) {
        show_admin_bar(false);
        $brand = $this->branding();
        $front_css = DRIVEFLOW_PRO_URL . 'assets/frontend.css?ver=' . DRIVEFLOW_PRO_VERSION . '.' . time();
        $front_js  = DRIVEFLOW_PRO_URL . 'assets/frontend.js?ver=' . DRIVEFLOW_PRO_VERSION . '.' . time();
        ?>
<!DOCTYPE html>
<html lang="en" dir="ltr" class="driveflow-standalone-portal">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?php echo esc_html($title); ?> — <?php echo esc_html($brand['name']); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&family=Barlow+Semi+Condensed:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo esc_url($front_css); ?>">
    <link rel="stylesheet" href="<?php echo esc_url(DRIVEFLOW_PRO_URL . 'assets/responsive.css?ver=' . DRIVEFLOW_PRO_VERSION); ?>">
    <style>
        html, body {
            margin: 0 !important;
            padding: 0 !important;
            background: #0f172a !important;
            min-height: 100vh !important;
            font-family: 'Be Vietnam Pro', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
        }
        #wpadminbar { display: none !important; }
        .driveflow-portal-outer {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            padding: 24px 12px;
            box-sizing: border-box;
            background: radial-gradient(circle at 50% 0%, #1e293b 0%, #0f172a 75%, #020617 100%);
        }
        .maryland-plate {
            background: #ffffff url('<?php echo esc_url(DRIVEFLOW_PRO_URL . 'assets/images/maryland-plate-authentic.svg'); ?>') no-repeat center center !important;
            background-size: 100% 100% !important;
        }
    </style>
</head>
<body class="driveflow-standalone-portal-body">
    <div class="driveflow-portal-outer">
        <div style="width: 100%; max-width: 1020px;">
            <?php echo $content_html; ?>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="<?php echo esc_url($front_js); ?>"></script>
</body>
</html>
        <?php
    }

    private function render_standalone_tv_board() {
        show_admin_bar(false);
        $brand = $this->branding();
        $front_css = DRIVEFLOW_PRO_URL . 'assets/frontend.css?ver=' . DRIVEFLOW_PRO_VERSION . '.' . time();
        $front_js  = DRIVEFLOW_PRO_URL . 'assets/frontend.js?ver=' . DRIVEFLOW_PRO_VERSION . '.' . time();
        $board_html = $this->tv_board(true);
        ?>
<!DOCTYPE html>
<html lang="en" dir="ltr" class="dfv2-kiosk-mode">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?php echo esc_html($brand['name']); ?> — Live TV Lobby Board</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;600;700;800&family=Barlow+Semi+Condensed:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo esc_url($front_css); ?>">
    <link rel="stylesheet" href="<?php echo esc_url(DRIVEFLOW_PRO_URL . 'assets/responsive.css?ver=' . DRIVEFLOW_PRO_VERSION); ?>">
    <style>
        html, body {
            margin: 0 !important;
            padding: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            overflow: hidden !important;
            background: #020810 !important;
        }
        #wpadminbar { display: none !important; }
        .dfv2-tv-wrap {
            margin-left: 0 !important;
            margin-right: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            max-width: 100vw !important;
            max-height: 100vh !important;
        }
        /* Fix Maryland plate background image with authentic SVG */
        .maryland-plate {
            background: #ffffff url('<?php echo esc_url(DRIVEFLOW_PRO_URL . 'assets/images/maryland-plate-authentic.svg'); ?>') no-repeat center center !important;
            background-size: 100% 100% !important;
        }
    </style>
</head>
<body class="dfv2-kiosk-mode">
    <?php echo $board_html; ?>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="<?php echo esc_url($front_js); ?>"></script>
</body>
</html>
        <?php
    }

    public function handle_standalone_admin_portal() {
        show_admin_bar(false);
        $brand = $this->branding();
        $login_error = '';

        // Handle login submission
        if ('POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['driveflow_admin_login'])) {
            check_admin_referer('driveflow_admin_login_action', 'driveflow_admin_nonce');
            $creds = array(
                'user_login'    => sanitize_text_field(wp_unslash($_POST['log'] ?? '')),
                'user_password' => $_POST['pwd'] ?? '',
                'remember'      => !empty($_POST['rememberme']),
            );
            $user = self::throttle('admin_login', 10, 15 * MINUTE_IN_SECONDS)
                ? wp_signon($creds, is_ssl())
                : new WP_Error('too_many_attempts', 'Too many login attempts. Please try again in 15 minutes.');
            if (is_wp_error($user)) {
                $login_error = $user->get_error_message();
            } else {
                wp_set_current_user($user->ID);
                wp_safe_redirect(admin_url('admin.php?page=driveflow-pro-hub'));
                exit;
            }
        }

        // If already logged in with admin privileges
        if (is_user_logged_in() && current_user_can('edit_posts')) {
            wp_safe_redirect(admin_url('admin.php?page=driveflow-pro-hub'));
            exit;
        }

        // Render Dedicated Executive Login Page
        $front_css = DRIVEFLOW_PRO_URL . 'assets/frontend.css?ver=' . DRIVEFLOW_PRO_VERSION . '.' . time();
        ?>
<!DOCTYPE html>
<html lang="en" dir="ltr" class="driveflow-admin-portal-login">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?php echo esc_html($brand['name']); ?> — Executive Admin Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&family=Barlow+Semi+Condensed:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo esc_url($front_css); ?>">
    <link rel="stylesheet" href="<?php echo esc_url(DRIVEFLOW_PRO_URL . 'assets/responsive.css?ver=' . DRIVEFLOW_PRO_VERSION); ?>">
    <style>
        html, body {
            margin: 0; padding: 0; min-height: 100vh;
            background: #020810;
            font-family: 'Be Vietnam Pro', -apple-system, sans-serif;
            display: flex; align-items: center; justify-content: center;
        }
        #wpadminbar { display: none !important; }
        .df-admin-login-card {
            width: 100%; max-width: 440px; margin: 20px;
            background: rgba(15, 23, 42, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 16px; padding: 36px 32px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6), 0 0 30px rgba(2, 132, 199, 0.2);
            color: #f8fafc; text-align: left;
        }
    </style>
</head>
<body>
    <div class="df-admin-login-card">
        <div style="text-align: center; margin-bottom: 24px;">
            <div style="width: 56px; height: 56px; background: linear-gradient(135deg, #0284c7, #0d9488); border-radius: 14px; display: inline-flex; align-items: center; justify-content: center; font-size: 28px; box-shadow: 0 8px 16px rgba(2, 132, 199, 0.4); margin-bottom: 12px;">
                🚗
            </div>
            <h1 style="font-size: 20px; font-weight: 800; margin: 0; color: #fff; letter-spacing: -0.3px;">
                <?php echo esc_html($brand['name']); ?>
            </h1>
            <p style="font-size: 12px; color: #38bdf8; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin: 4px 0 0 0;">
                Executive Command & Operations Portal
            </p>
        </div>

        <?php if (!empty($login_error)) : ?>
            <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #fca5a5; padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 18px; line-height: 1.4;">
                ⚠️ <?php echo wp_strip_all_tags($login_error); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url(site_url('/?driveflow_admin=1')); ?>">
            <?php wp_nonce_field('driveflow_admin_login_action', 'driveflow_admin_nonce'); ?>
            <input type="hidden" name="driveflow_admin_login" value="1">

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">
                    Username or Academy Email
                </label>
                <input type="text" name="log" required style="width: 100%; box-sizing: border-box; background: rgba(30, 41, 59, 0.8); border: 1px solid #334155; border-radius: 8px; padding: 12px 14px; color: #fff; font-size: 14px; outline: none;" placeholder="admin@samsdriving.com">
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">
                    Password
                </label>
                <input type="password" name="pwd" required style="width: 100%; box-sizing: border-box; background: rgba(30, 41, 59, 0.8); border: 1px solid #334155; border-radius: 8px; padding: 12px 14px; color: #fff; font-size: 14px; outline: none;" placeholder="••••••••••••">
            </div>

            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 22px; font-size: 13px;">
                <label style="display: flex; align-items: center; gap: 6px; color: #94a3b8; cursor: pointer;">
                    <input type="checkbox" name="rememberme" value="forever" checked>
                    <span>Keep me logged in</span>
                </label>
                <a href="<?php echo esc_url(wp_lostpassword_url()); ?>" target="_blank" style="color: #38bdf8; text-decoration: none;">Forgot password?</a>
            </div>

            <button type="submit" style="width: 100%; background: linear-gradient(135deg, #0284c7, #0369a1); border: none; border-radius: 8px; color: #fff; font-size: 15px; font-weight: 800; padding: 14px; cursor: pointer; box-shadow: 0 4px 14px rgba(2, 132, 199, 0.4); letter-spacing: 0.3px;">
                Sign In to Command Center →
            </button>
        </form>

        <div style="margin-top: 24px; padding-top: 18px; border-top: 1px solid rgba(255, 255, 255, 0.08); text-align: center; font-size: 12px; color: #64748b;">
            <span>Authorized Academy Administrators Only</span><br>
            <span style="font-size: 11px;">Maryland MVA / COMAR 11.23 Regulated System</span>
        </div>
    </div>
</body>
</html>
        <?php
    }

    public function register_routes() {
        register_rest_route(self::REST_NAMESPACE, '/sessions', array(
            array('methods' => WP_REST_Server::READABLE, 'callback' => array($this, 'get_sessions'), 'permission_callback' => array($this, 'can_write')),
            array('methods' => WP_REST_Server::CREATABLE, 'callback' => array($this, 'create_session'), 'permission_callback' => array($this, 'can_write')),
        ));
        register_rest_route(self::REST_NAMESPACE, '/tv-sessions', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_tv_sessions'),
            'permission_callback' => array($this, 'can_read_tv'),
        ));
        register_rest_route(self::REST_NAMESPACE, '/directory', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_directory'),
            'permission_callback' => array($this, 'can_write'),
        ));
        register_rest_route(self::REST_NAMESPACE, '/sessions/(?P<id>\d+)', array(
            array('methods' => WP_REST_Server::EDITABLE, 'callback' => array($this, 'update_session'), 'permission_callback' => array($this, 'can_write')),
            array('methods' => WP_REST_Server::DELETABLE, 'callback' => array($this, 'delete_session'), 'permission_callback' => array($this, 'can_delete')),
        ));
    }

    public function can_write($request) {
        // A wp_rest nonce is public for anonymous visitors; never accept it as authorization on its own.
        return is_user_logged_in() && current_user_can('edit_posts');
    }

    public function can_delete($request) {
        return current_user_can('manage_options') && wp_verify_nonce($request->get_header('X-WP-Nonce'), 'wp_rest');
    }

    public function can_read_tv($request) {
        $settings = get_option(self::OPTION_KEY, array());
        $client_key = (string) ($request->get_header('X-DriveFlow-API-Key') ?: $request->get_param('key'));
        $server_key = (string) ($settings['tv_api_key'] ?? '');
        if (current_user_can('manage_options')) {
            return true;
        }
        if ($server_key && $client_key && hash_equals($server_key, $client_key)) {
            return true;
        }
        // Allow public read of active TV board schedule
        return true;
    }

    /** Instructor profile linked to the logged-in WP user (user_id first, then account e-mail). */
    public function get_current_instructor() {
        if (!is_user_logged_in()) {
            return null;
        }
        global $wpdb;
        $user  = wp_get_current_user();
        $table = $wpdb->prefix . 'driveflow_instructors';
        $cols  = 'id, name, license_number, email, phone, photo_url, status';
        $row   = $wpdb->get_row($wpdb->prepare("SELECT {$cols} FROM {$table} WHERE user_id = %d LIMIT 1", $user->ID), ARRAY_A);
        if (!$row && $user->user_email && current_user_can('edit_posts')) {
            $row = $wpdb->get_row($wpdb->prepare("SELECT {$cols} FROM {$table} WHERE email = %s LIMIT 1", $user->user_email), ARRAY_A);
        }
        return $row ?: null;
    }

    /** Auth gate for the instructor field app AJAX calls. Returns array(is_admin, instructor) or ends with a JSON error. */
    private function instructor_ajax_context() {
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Please log in to continue.', 'code' => 'login_required'), 401);
        }
        $is_admin   = current_user_can('manage_options');
        $instructor = $this->get_current_instructor();
        if (!$is_admin && (!$instructor || 'active' !== $instructor['status'] || !current_user_can('edit_posts'))) {
            wp_send_json_error(array('message' => 'Your account is not linked to an active instructor profile.'), 403);
        }
        return array($is_admin, $instructor);
    }

    /** Login card shown instead of the instructor app to visitors who are not logged in. */
    private function instructor_login_card() {
        if (!defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', true);
        }
        $form = wp_login_form(array(
            'echo'           => false,
            'redirect'       => home_url(add_query_arg(array())),
            'label_username' => 'Username or Email',
            'label_log_in'   => 'Log In',
            'remember'       => true,
        ));
        return '<style>.df-login-card{box-sizing:border-box;width:100%;max-width:420px;margin:24px auto;padding:24px 20px;border:1px solid #cbd5e1;border-radius:14px;background:#fff;font-family:inherit}'
            . '.df-login-card h3{margin:0 0 6px;font-size:20px}.df-login-card p{margin:0 0 16px;color:#64748b;font-size:14px}'
            . '.df-login-card input[type=text],.df-login-card input[type=password]{box-sizing:border-box;width:100%;min-height:44px;padding:10px 12px;font-size:16px;border:1px solid #cbd5e1;border-radius:8px}'
            . '.df-login-card input[type=submit]{width:100%;min-height:48px;font-size:16px;border:0;border-radius:8px;background:#0f766e;color:#fff;cursor:pointer}'
            . '.df-login-card .login-username,.df-login-card .login-password,.df-login-card .login-remember,.df-login-card .login-submit{margin:0 0 12px}.df-login-card label{display:block;margin-bottom:4px;font-size:14px}</style>'
            . '<div class="df-login-card"><h3>Instructor Login</h3><p>Sign in to open the in-car evaluation form. Your instructor details are filled in automatically.</p>' . $form . '</div>';
    }

    /** Per-IP throttle for public endpoints (REMOTE_ADDR only). Returns false once the limit is exceeded. */
    public static function throttle($bucket, $max, $window) {
        $ip   = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : 'unknown';
        $key  = 'df_thr_' . md5($bucket . '|' . $ip);
        $hits = (int) get_transient($key);
        if ($hits >= $max) {
            return false;
        }
        set_transient($key, $hits + 1, $window);
        return true;
    }

    /** Public forms may only send bounded base64 image data-URLs; anything else is rejected. */
    private static function clean_image_data_url($raw, $max_bytes) {
        $raw = is_string($raw) ? wp_unslash($raw) : '';
        if ('' === $raw) {
            return '';
        }
        if (strlen($raw) > $max_bytes || !preg_match('#^data:image/(png|jpe?g|webp);base64,[A-Za-z0-9+/=]+$#', $raw)) {
            wp_send_json_error(array('message' => 'Image data is invalid or too large.'), 413);
        }
        return $raw;
    }

    public function table() {
        global $wpdb;
        return $wpdb->prefix . 'driveflow_sessions';
    }

    private function clean_session($params, $existing = array()) {
        $start = sanitize_text_field($params['scheduled_start'] ?? ($existing['scheduled_start'] ?? current_time('mysql')));
        $end = sanitize_text_field($params['scheduled_end'] ?? ($existing['scheduled_end'] ?? $start));
        $status = sanitize_key($params['status'] ?? ($existing['status'] ?? 'upcoming'));
        if (!in_array($status, array('upcoming', 'active', 'completed', 'cancelled'), true)) {
            $status = 'upcoming';
        }
        $form_data = $params['form_data'] ?? ($existing['form_data'] ?? array());
        if (is_string($form_data)) {
            $form_data = json_decode(wp_unslash($form_data), true);
        }
        $signature = sanitize_textarea_field($params['signature'] ?? ($existing['signature'] ?? ''));
        $selfie = sanitize_textarea_field($params['selfie'] ?? ($existing['selfie'] ?? ''));
        if (strlen($signature) > 2000000) $signature = '';
        if (strlen($selfie) > 5000000) $selfie = '';

            $raw_plate = sanitize_text_field($params['plate_number'] ?? ($existing['plate_number'] ?? ''));
            $clean_plate = strtoupper(preg_replace('/[^A-Za-z0-9\s\-]/', '', $raw_plate));
            return array(
                'student_name' => sanitize_text_field($params['student_name'] ?? ($existing['student_name'] ?? '')),
                'instructor_name' => sanitize_text_field($params['instructor_name'] ?? ($existing['instructor_name'] ?? '')),
                'plate_number' => $clean_plate,
            'session_number' => max(1, absint($params['session_number'] ?? ($existing['session_number'] ?? 1))),
            'lesson_topic' => sanitize_text_field($params['lesson_topic'] ?? ($existing['lesson_topic'] ?? '')),
            'scheduled_start' => preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $start) ? $start : current_time('mysql'),
            'scheduled_end' => preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $end) ? $end : current_time('mysql'),
            'status' => $status,
            'form_data' => wp_json_encode(is_array($form_data) ? $form_data : array()),
            'signature' => $signature,
            'selfie' => $selfie,
        );
    }

    public function get_sessions($request) {
        global $wpdb;
        $limit = min(100, max(1, absint($request->get_param('limit') ?: 50)));
        $status = sanitize_key($request->get_param('status'));
        $where = $status ? $wpdb->prepare(' WHERE status = %s', $status) : '';
        $rows = $wpdb->get_results("SELECT id, student_name, instructor_name, plate_number, session_number, lesson_topic, scheduled_start, scheduled_end, status, form_data, created_at, updated_at FROM {$this->table()}{$where} ORDER BY scheduled_start ASC LIMIT {$limit}", ARRAY_A);
        foreach ($rows as &$row) {
            $row['form_data'] = json_decode($row['form_data'] ?: '{}', true);
        }
        return rest_ensure_response($rows);
    }

    public function get_tv_sessions($request) {
        global $wpdb;
        $limit = min(50, max(1, absint($request->get_param('limit') ?: 25)));
        $rows = $wpdb->get_results("SELECT id, student_name, instructor_name, plate_number, session_number, lesson_topic, scheduled_start, scheduled_end, status FROM {$this->table()} WHERE status IN ('upcoming', 'active') ORDER BY scheduled_start ASC LIMIT {$limit}", ARRAY_A);
        return rest_ensure_response($rows);
    }

    public function get_directory($request) {
        global $wpdb;
        $students = $wpdb->get_results("SELECT name AS student_name FROM {$wpdb->prefix}driveflow_students WHERE status = 'active' ORDER BY name ASC LIMIT 200", ARRAY_A);
        $instructors = $wpdb->get_results("SELECT name AS instructor_name FROM {$wpdb->prefix}driveflow_instructors WHERE status = 'active' ORDER BY name ASC LIMIT 100", ARRAY_A);
        $vehicles = $wpdb->get_results("SELECT plate_number, model FROM {$wpdb->prefix}driveflow_vehicles WHERE status = 'active' ORDER BY plate_number ASC LIMIT 100", ARRAY_A);

        return rest_ensure_response(array(
            'students' => $students,
            'instructors' => $instructors,
            'vehicles' => $vehicles,
        ));
    }

    public function create_session($request) {
        global $wpdb;
        $data = $this->clean_session($request->get_json_params() ?: array());
        if (!$data['student_name']) {
            return new WP_Error('missing_student', 'Student name is required.', array('status' => 400));
        }
        $now = current_time('mysql');
        $data['created_by'] = get_current_user_id();
        $data['created_at'] = $now;
        $data['updated_at'] = $now;

        $formats = array('%s','%s','%s','%d','%s','%s','%s','%s','%s','%s','%s','%d','%s','%s');
        $wpdb->insert($this->table(), $data, $formats);
        if (!$wpdb->insert_id) {
            return new WP_Error('db_insert_failed', 'Session could not be saved.', array('status' => 500));
        }

        $session_id = (int) $wpdb->insert_id;
        DriveFlow_Email_Manager::send_student_booking($session_id);
        DriveFlow_Email_Manager::send_instructor_booking($session_id);

        return new WP_REST_Response(array('id' => $session_id), 201);
    }

    public function update_session($request) {
        global $wpdb;
        $id = absint($request['id']);
        $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table()} WHERE id = %d", $id), ARRAY_A);
        if (!$existing) {
            return new WP_Error('not_found', 'Session not found.', array('status' => 404));
        }
        $data = $this->clean_session($request->get_json_params() ?: array(), $existing);
        $data['updated_at'] = current_time('mysql');
        $wpdb->update($this->table(), $data, array('id' => $id), array('%s','%s','%s','%d','%s','%s','%s','%s','%s','%s','%s'), array('%d'));

        if ('completed' === $data['status'] && 'completed' !== $existing['status']) {
            DriveFlow_Email_Manager::send_student_completion($id);
        } elseif ('cancelled' === $data['status'] && 'cancelled' !== $existing['status']) {
            DriveFlow_Email_Manager::send_cancellation($id);
        }

        return rest_ensure_response(array('id' => $id, 'updated' => true));
    }

    public function delete_session($request) {
        global $wpdb;
        $id = absint($request['id']);
        $deleted = $wpdb->delete($this->table(), array('id' => $id), array('%d'));
        return $deleted ? rest_ensure_response(array('deleted' => true)) : new WP_Error('not_found', 'Session not found.', array('status' => 404));
    }
}

/**
 * Class DriveFlow_Email_Manager
 * Pure English, luxury responsive HTML email notifications.
 */
class DriveFlow_Email_Manager {

    private static function get_settings() {
        return get_option(DriveFlow_Pro::OPTION_KEY, array());
    }

    private static function get_headers() {
        $settings = self::get_settings();
        $sender_name = !empty($settings['sender_name']) ? $settings['sender_name'] : (get_bloginfo('name') ?: 'DriveFlow Academy');
        $sender_email = !empty($settings['sender_email']) ? $settings['sender_email'] : get_option('admin_email');
        return array(
            'Content-Type: text/html; charset=UTF-8',
            "From: {$sender_name} <{$sender_email}>",
        );
    }

    private static function build_html_template($title, $badge, $badge_color, $greeting, $message_html, $details_array, $footer_note = '') {
        $settings = self::get_settings();
        $school_name = !empty($settings['school_name']) ? $settings['school_name'] : (get_bloginfo('name') ?: 'DriveFlow Academy');
        $logo_url = !empty($settings['logo_url']) ? $settings['logo_url'] : '';

        $rows_html = '';
        foreach ($details_array as $label => $val) {
            $rows_html .= '<tr>
                <td style="padding:11px 16px;border-bottom:1px solid #e2e8f0;color:#64748b;font-size:13px;width:38%;font-weight:600;">' . esc_html($label) . '</td>
                <td style="padding:11px 16px;border-bottom:1px solid #e2e8f0;color:#0f172a;font-size:14px;font-weight:700;">' . $val . '</td>
            </tr>';
        }

        $logo_markup = $logo_url ? '<img src="' . esc_url($logo_url) . '" alt="" style="max-height:48px;max-width:180px;margin-bottom:10px;">' : '';

        return '<!DOCTYPE html>
        <html dir="ltr" lang="en">
        <head><meta charset="utf-8"></head>
        <body style="margin:0;padding:0;background-color:#0f172a;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Helvetica,Arial,sans-serif;">
            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color:#0f172a;padding:32px 12px;">
                <tr>
                    <td align="center">
                        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width:600px;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 20px 25px -5px rgba(0,0,0,0.3);">
                            <!-- Header Banner -->
                            <tr>
                                <td style="background:linear-gradient(135deg,#0b1326 0%,#1e293b 60%,#0f766e 100%);padding:30px;text-align:center;color:#ffffff;">
                                    ' . $logo_markup . '
                                    <h1 style="margin:0;font-size:22px;font-weight:800;color:#ffffff;letter-spacing:0.5px;">' . esc_html($school_name) . '</h1>
                                    <p style="margin:6px 0 0 0;color:#94a3b8;font-size:13px;">Official Driving Academy Session Management</p>
                                </td>
                            </tr>
                            <!-- Status & Title -->
                            <tr>
                                <td style="padding:26px 32px 12px 32px;text-align:left;">
                                    <span style="display:inline-block;background:' . esc_attr($badge_color) . ';color:#ffffff;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;letter-spacing:0.3px;">' . esc_html($badge) . '</span>
                                    <h2 style="margin:14px 0 8px 0;font-size:20px;color:#0f172a;font-weight:800;">' . esc_html($title) . '</h2>
                                    <p style="margin:0;color:#475569;font-size:14px;line-height:1.6;">' . esc_html($greeting) . '</p>
                                </td>
                            </tr>
                            <!-- Alert or Message Content -->
                            ' . ($message_html ? '<tr><td style="padding:10px 32px;font-size:14px;color:#334155;line-height:1.7;">' . $message_html . '</td></tr>' : '') . '
                            <!-- Details Table -->
                            <tr>
                                <td style="padding:15px 32px;">
                                    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;">
                                        ' . $rows_html . '
                                    </table>
                                </td>
                            </tr>
                            <!-- Footer Instructions -->
                            ' . ($footer_note ? '<tr><td style="padding:10px 32px 24px 32px;font-size:13px;color:#64748b;line-height:1.6;">' . $footer_note . '</td></tr>' : '') . '
                            <!-- Bottom Bar -->
                            <tr>
                                <td style="background:#f1f5f9;border-top:1px solid #e2e8f0;padding:18px 32px;text-align:center;color:#94a3b8;font-size:12px;">
                                    Sent automatically by DriveFlow Pro Driving School Manager • Drive Safe, Stay Alert.
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>';
    }

    private static function find_email($name, $table_suffix) {
        global $wpdb;
        $table = $wpdb->prefix . 'driveflow_' . $table_suffix;
        $email = $wpdb->get_var($wpdb->prepare("SELECT email FROM {$table} WHERE name = %s AND email != '' LIMIT 1", $name));
        return $email ? sanitize_email($email) : '';
    }

    public static function send_student_booking($session_id) {
        $settings = self::get_settings();
        if (isset($settings['email_student_booking']) && '0' === $settings['email_student_booking']) return false;

        global $wpdb;
        $session = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}driveflow_sessions WHERE id = %d", $session_id), ARRAY_A);
        if (!$session) return false;

        $student_email = self::find_email($session['student_name'], 'students');
        if (!$student_email) return false;

        $title = "Driving Lesson Confirmed (Session #{$session['session_number']})";
        $badge = "CONFIRMED ✓";
        $badge_color = "#10b981";
        $greeting = "Hello " . esc_html($session['student_name']) . ", your upcoming driving lesson has been confirmed and registered in our academy schedule.";
        $message_html = "<div style='background:#ecfdf5;border-left:4px solid #10b981;padding:12px 16px;border-radius:4px;color:#065f46;'>
            <strong>Important Preparation:</strong> Please arrive at least 10 minutes before your scheduled start time. Remember to bring your valid learner permit and suitable driving footwear.
        </div>";

        $details = array(
            'Student Name' => esc_html($session['student_name']),
            'Instructor' => esc_html($session['instructor_name'] ?: 'Duty Instructor'),
            'Vehicle / Plate' => '<span style="background:#fef08a;color:#854d0e;padding:2px 8px;border-radius:4px;font-family:monospace;font-weight:bold;">' . esc_html($session['plate_number'] ?: 'Assigned at Depot') . '</span>',
            'Session Number' => 'Lesson #' . esc_html($session['session_number']),
            'Lesson Topic' => esc_html($session['lesson_topic'] ?: 'Behind-The-Wheel Driving Lesson'),
            'Scheduled Start' => esc_html(date_i18n('l, F j, Y @ g:i A', strtotime($session['scheduled_start']))),
            'Scheduled End' => esc_html(date_i18n('g:i A', strtotime($session['scheduled_end']))),
        );

        $body = self::build_html_template($title, $badge, $badge_color, $greeting, $message_html, $details, 'If you need to reschedule or have questions, please notify the driving academy at least 24 hours in advance.');
        return wp_mail($student_email, "[DriveFlow] {$title}", $body, self::get_headers());
    }

    public static function send_instructor_booking($session_id) {
        $settings = self::get_settings();
        if (isset($settings['email_instructor_booking']) && '0' === $settings['email_instructor_booking']) return false;

        global $wpdb;
        $session = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}driveflow_sessions WHERE id = %d", $session_id), ARRAY_A);
        if (!$session || empty($session['instructor_name'])) return false;

        $instructor_email = self::find_email($session['instructor_name'], 'instructors');
        if (!$instructor_email) return false;

        $title = "New Driving Lesson Scheduled";
        $badge = "CALENDAR DISPATCH";
        $badge_color = "#0284c7";
        $greeting = "Hello Instructor " . esc_html($session['instructor_name']) . ", a new driving session has been added to your field schedule.";

        $details = array(
            'Student Name' => esc_html($session['student_name']),
            'Session Number' => 'Lesson #' . esc_html($session['session_number']),
            'Curriculum Topic' => esc_html($session['lesson_topic'] ?: 'Road Maneuvers'),
            'Vehicle Plate' => esc_html($session['plate_number'] ?: 'School Fleet Car'),
            'Start Time' => esc_html(date_i18n('l, F j, Y @ g:i A', strtotime($session['scheduled_start']))),
            'End Time' => esc_html(date_i18n('g:i A', strtotime($session['scheduled_end']))),
        );

        $body = self::build_html_template($title, $badge, $badge_color, $greeting, '', $details, 'Please log into the Instructor Field App before vehicle departure to capture verification photo and student signature.');
        return wp_mail($instructor_email, "[DriveFlow] Lesson Dispatch: {$session['student_name']}", $body, self::get_headers());
    }

    public static function send_student_completion($session_id) {
        $settings = self::get_settings();
        if (isset($settings['email_student_completion']) && '0' === $settings['email_student_completion']) return false;

        global $wpdb;
        $session = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}driveflow_sessions WHERE id = %d", $session_id), ARRAY_A);
        if (!$session) return false;

        $student_email = self::find_email($session['student_name'], 'students');
        if (!$student_email) return false;

        $formData = json_decode($session['form_data'] ?: '{}', true);
        $skills = $formData['skills'] ?? null;
        $notes = $formData['instructor_notes'] ?? '';

        $title = "Lesson Completed & Skill Scorecard (Session #{$session['session_number']})";
        $badge = "COMPLETED ✓";
        $badge_color = "#059669";
        $greeting = "Congratulations " . esc_html($session['student_name']) . "! Your driving lesson has been verified and logged into your official driving logbook.";

        $stars_fn = function ($val) {
            $out = '';
            for ($i = 1; $i <= 5; $i++) {
                $out .= ($i <= $val) ? '<span style="color:#f59e0b;font-size:16px;">★</span>' : '<span style="color:#cbd5e1;font-size:16px;">☆</span>';
            }
            return $out;
        };

        $skills_html = '';
        if ($skills) {
            $skills_html = "<div style='background:#f8fafc;border:1px solid #e2e8f0;padding:16px;border-radius:8px;margin:12px 0;'>
                <h4 style='margin:0 0 12px 0;color:#0f172a;font-size:15px;'>📊 Instructor Skill Evaluation:</h4>
                <div style='display:grid;grid-template-columns:1fr 1fr;gap:10px;font-size:13px;'>
                    <div><strong>Clutch & Throttle:</strong><br>" . $stars_fn($skills['clutch'] ?? 4) . "</div>
                    <div><strong>Parallel Parking:</strong><br>" . $stars_fn($skills['parking'] ?? 4) . "</div>
                    <div><strong>Steering & Turns:</strong><br>" . $stars_fn($skills['steering'] ?? 5) . "</div>
                    <div><strong>Road Rules & Safety:</strong><br>" . $stars_fn($skills['rules'] ?? 5) . "</div>
                </div>
            </div>";
        }

        if ($notes) {
            $skills_html .= "<div style='background:#fefce8;border-left:4px solid #facc15;padding:12px 16px;border-radius:4px;color:#713f12;margin-top:10px;'>
                <strong>Instructor Feedback & Recommendations:</strong><br>" . nl2br(esc_html($notes)) . "
            </div>";
        }

        $details = array(
            'Session Number' => 'Lesson #' . esc_html($session['session_number']),
            'Instructor' => esc_html($session['instructor_name']),
            'Topic Covered' => esc_html($session['lesson_topic']),
            'Date Completed' => esc_html(date_i18n('F j, Y', strtotime($session['scheduled_start']))),
            'Hours Credited' => '2 Driving Hours',
        );

        $body = self::build_html_template($title, $badge, $badge_color, $greeting, $skills_html, $details, 'Your instructor selfie photo and digital signature verification have been safely archived in your student registry.');
        return wp_mail($student_email, "[DriveFlow] Scorecard for Lesson #{$session['session_number']}: {$session['student_name']}", $body, self::get_headers());
    }

    public static function send_cancellation($session_id) {
        $settings = self::get_settings();
        if (isset($settings['email_cancellation']) && '0' === $settings['email_cancellation']) return false;

        global $wpdb;
        $session = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}driveflow_sessions WHERE id = %d", $session_id), ARRAY_A);
        if (!$session) return false;

        $student_email = self::find_email($session['student_name'], 'students');
        if (!$student_email) return false;

        $title = "Notice of Driving Lesson Cancellation";
        $badge = "CANCELLED ✕";
        $badge_color = "#ef4444";
        $greeting = "Dear student, the following driving lesson has been cancelled in our academy schedule.";

        $details = array(
            'Student Name' => esc_html($session['student_name']),
            'Instructor' => esc_html($session['instructor_name']),
            'Original Time' => esc_html(date_i18n('l, F j, Y @ g:i A', strtotime($session['scheduled_start']))),
            'Status' => '<span style="color:#ef4444;font-weight:bold;">Cancelled</span>',
        );

        $body = self::build_html_template($title, $badge, $badge_color, $greeting, '', $details, 'Please contact the driving academy or your instructor to schedule your replacement lesson.');
        return wp_mail($student_email, "[DriveFlow] Lesson Cancelled: {$session['student_name']}", $body, self::get_headers());
    }

    public static function send_test_email($target_email) {
        $title = "DriveFlow Pro Email Delivery Verification";
        $badge = "TEST SUCCESSFUL ✓";
        $badge_color = "#0f766e";
        $greeting = "This test email confirms that your WordPress email subsystem is properly configured and delivering luxury HTML notifications.";
        $message = "<p style='color:#0f766e;font-weight:bold;'>All automated driving school notifications (student booking, instructor dispatch & scorecards) are operational.</p>";
        $details = array(
            'Test Timestamp' => current_time('mysql'),
            'Plugin Version' => DRIVEFLOW_PRO_VERSION,
            'Mail Subsystem' => 'WordPress wp_mail',
        );

        $body = self::build_html_template($title, $badge, $badge_color, $greeting, $message, $details, 'This verification message was initiated from the DriveFlow Pro Settings screen.');
        return wp_mail($target_email, "[DriveFlow] Driving School Email System Test", $body, self::get_headers());
    }

    public static function send_magic_booking_link($student_id) {
        $profile = DriveFlow_Credit_Manager::get_student_profile($student_id);
        if (!$profile || empty($profile['email'])) {
            return false;
        }

        $student_email = $profile['email'];
        $magic_link = $profile['magic_link'];
        $remaining = (int)$profile['remaining_sessions'];
        $total = (int)$profile['total_sessions'];

        $title = "Your Private Driving Lesson Booking Link";
        $badge = "SELF-BOOKING PORTAL 🚗";
        $badge_color = "#2563eb";
        $greeting = "Hello " . esc_html($profile['name']) . ", you can now schedule your driving lesson sessions online at your convenience.";

        $btn_html = "<div style='text-align:center;margin:24px 0 16px 0;'>
            <a href='" . esc_url($magic_link) . "' style='background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#ffffff;text-decoration:none;padding:14px 28px;border-radius:8px;font-weight:700;font-size:15px;display:inline-block;box-shadow:0 4px 6px -1px rgba(37,99,235,0.3);'>
                Schedule Your Next Driving Lesson &rarr;
            </a>
            <p style='color:#64748b;font-size:12px;margin-top:12px;'>Or open this link directly in your phone or computer browser:<br><a href='" . esc_url($magic_link) . "' style='color:#2563eb;word-break:break-all;'>" . esc_html($magic_link) . "</a></p>
        </div>";

        $details = array(
            'Student Name' => esc_html($profile['name']),
            'Course Package' => esc_html($profile['package_name']),
            'Remaining Lessons' => '<span style="color:#2563eb;font-weight:bold;font-size:15px;">' . $remaining . ' of ' . $total . ' Lessons</span>',
            'Lesson Duration' => '2 Hours per Session (8:00 AM – 8:00 PM)',
            'Booking Security' => 'Personal Token Protected (No Password Needed)',
        );

        $body = self::build_html_template($title, $badge, $badge_color, $greeting, $btn_html, $details, 'Keep this link safe. You can use it anytime to book your remaining course sessions without needing to log in.');
        return wp_mail($student_email, "[DriveFlow] Schedule Your Driving Lessons: {$profile['name']}", $body, self::get_headers());
    }

    public static function send_magic_link_dispatch($type, $recipient_name, $recipient_email, $short_url, $expires_at, $is_single_use) {
        if (empty($recipient_email)) return false;

        $settings = self::get_settings();
        $school_name = !empty($settings['school_name']) ? $settings['school_name'] : (get_bloginfo('name') ?: 'DriveFlow Academy');

        $is_student = ('student_booking' === $type);
        $title = $is_student ? "Your Driving Lesson Self-Booking Link" : "Instructor Profile & Credentials Onboarding Link";
        $badge = $is_student ? "SELF-BOOKING LINK 🚗" : "INSTRUCTOR ONBOARDING 🛡️";
        $badge_color = $is_student ? "#2563eb" : "#0f766e";
        $greeting = "Hello " . esc_html($recipient_name) . ", your personalized secure access link is ready.";

        $btn_text = $is_student ? "Schedule Your Driving Lesson &rarr;" : "Complete Instructor Profile &rarr;";
        $desc = $is_student
            ? "Use this private link to choose your preferred driving slot from available instructor schedules."
            : "Please complete your personnel profile, verify your contact details, upload your instructor headshot photo, and enter your state instructor certification number.";

        $exp_str = $expires_at ? date_i18n('F j, Y @ g:i A', strtotime($expires_at)) : 'No Expiration (Permanent)';
        $use_str = $is_single_use ? 'Single-Use (Redeemed upon first completion)' : 'Multi-Use (Reusable)';

        $btn_html = "<div style='text-align:center;margin:24px 0 16px 0;'>
            <p style='color:#334155;font-size:14px;margin-bottom:18px;line-height:1.5;'>" . esc_html($desc) . "</p>
            <a href='" . esc_url($short_url) . "' style='background:linear-gradient(135deg," . esc_attr($badge_color) . ",#0f172a);color:#ffffff;text-decoration:none;padding:14px 28px;border-radius:8px;font-weight:700;font-size:15px;display:inline-block;box-shadow:0 4px 6px -1px rgba(0,0,0,0.2);'>
                " . esc_html($btn_text) . "
            </a>
            <p style='color:#64748b;font-size:12px;margin-top:14px;'>Or open your short link directly:<br><a href='" . esc_url($short_url) . "' style='color:" . esc_attr($badge_color) . ";word-break:break-all;font-weight:600;'>" . esc_html($short_url) . "</a></p>
        </div>";

        $details = array(
            'Recipient' => esc_html($recipient_name),
            'Link Type' => $is_student ? 'Student Self-Booking' : 'Instructor Onboarding',
            'Expires On' => esc_html($exp_str),
            'Usage Policy' => esc_html($use_str),
            'Security' => 'Encrypted Token Verified',
        );

        $body = self::build_html_template($title, $badge, $badge_color, $greeting, $btn_html, $details, 'Please do not forward this private link to anyone else.');
        return wp_mail($recipient_email, "[{$school_name}] " . $title, $body, self::get_headers());
    }
}

/**
 * Class DriveFlow_Wappointment_Sync
 *
 * Handles bidirectional synchronization between Wappointment booking plugin
 * and DriveFlow Pro sessions + student profiles.
 *
 * Security: All data from Wappointment is sanitized before storage.
 * No raw Wappointment data is passed directly to queries — all values
 * pass through sanitize_text_field / sanitize_email / absint etc.
 */
class DriveFlow_Wappointment_Sync {

    /** Cache for discovered Wappointment table names */
    private static $wapp_tables_cache = null;

    // ----------------------------------------------------------------
    // Bootstrap
    // ----------------------------------------------------------------

    public static function init() {
        // Real-time hooks fired by Wappointment
        add_action('wappointment_appointment_created',        array(__CLASS__, 'on_appointment_created'),  10, 2);
        add_action('wappointment_booking_completed',          array(__CLASS__, 'on_booking_completed'),    10, 1);
        add_action('wappointment_appointment_status_changed', array(__CLASS__, 'on_status_changed'),       10, 3);
        add_action('wappointment_appointment_updated',        array(__CLASS__, 'on_appointment_updated'),  10, 2);
        add_action('wappointment_appointment_cancelled',      array(__CLASS__, 'on_appointment_cancelled'),10, 2);
        add_action('wappointment_appointment_rescheduled',    array(__CLASS__, 'on_appointment_updated'),  10, 2);
    }

    // ----------------------------------------------------------------
    // Detection helpers
    // ----------------------------------------------------------------

    /**
     * Returns true if Wappointment plugin is active/installed.
     */
    public static function is_wappointment_active() {
        if (defined('WAPPOINTMENT_VERSION') || class_exists('Wappointment\\WP\\WpApp') || class_exists('Wappointment')) {
            return true;
        }
        if (function_exists('is_plugin_active') && is_plugin_active('wappointment/wappointment.php')) {
            return true;
        }
        global $wpdb;
        $tables = $wpdb->get_col("SHOW TABLES LIKE '%wappo%'");
        return !empty($tables);
    }

    /** Cache for dynamic table column inspections */
    private static $table_columns_cache = array();

    /**
     * Inspect and cache column names for any discovered table.
     *
     * @param string $table
     * @return array
     */
    private static function get_table_columns($table) {
        if (empty($table)) return array();
        if (isset(self::$table_columns_cache[$table])) {
            return self::$table_columns_cache[$table];
        }
        global $wpdb;
        $cols = $wpdb->get_col("DESCRIBE `{$table}`");
        self::$table_columns_cache[$table] = is_array($cols) ? $cols : array();
        return self::$table_columns_cache[$table];
    }

    /**
     * Discover the actual Wappointment DB table names — handles both
     * old (wappo_*) and new (wappointment_*) table naming conventions.
     * Results are cached per request.
     *
     * @return array  ['appointments' => '...', 'clients' => '...', 'staff' => '...', 'services' => '...']
     */
    private static function discover_tables() {
        if (self::$wapp_tables_cache !== null) {
            return self::$wapp_tables_cache;
        }
        global $wpdb;

        $candidates_appt = array(
            $wpdb->prefix . 'wappo_appointments',
            $wpdb->prefix . 'wappointment_appointments',
        );
        $candidates_client = array(
            $wpdb->prefix . 'wappo_clients',
            $wpdb->prefix . 'wappointment_clients',
        );
        $candidates_staff = array(
            $wpdb->prefix . 'wappo_staff',
            $wpdb->prefix . 'wappointment_staff',
        );
        $candidates_service = array(
            $wpdb->prefix . 'wappo_services',
            $wpdb->prefix . 'wappointment_services',
        );

        $appt_table    = null;
        $client_table  = null;
        $staff_table   = null;
        $service_table = null;

        foreach ($candidates_appt as $t) {
            if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $t)) === $t) {
                $appt_table = $t;
                break;
            }
        }
        foreach ($candidates_client as $t) {
            if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $t)) === $t) {
                $client_table = $t;
                break;
            }
        }
        foreach ($candidates_staff as $t) {
            if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $t)) === $t) {
                $staff_table = $t;
                break;
            }
        }
        foreach ($candidates_service as $t) {
            if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $t)) === $t) {
                $service_table = $t;
                break;
            }
        }

        self::$wapp_tables_cache = array(
            'appointments' => $appt_table,
            'clients'      => $client_table,
            'staff'        => $staff_table,
            'services'     => $service_table,
        );
        return self::$wapp_tables_cache;
    }

    // ----------------------------------------------------------------
    // Raw Wappointment data retrieval + sanitization
    // ----------------------------------------------------------------

    /**
     * Fetch and sanitize a single Wappointment appointment row.
     * Returns an associative array of sanitized strings, or null.
     */
    private static function get_wapp_appointment($appointment_id) {
        global $wpdb;
        $tables = self::discover_tables();
        if (empty($tables['appointments'])) {
            return null;
        }

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM `{$tables['appointments']}` WHERE id = %d", absint($appointment_id)),
            ARRAY_A
        );
        if (empty($row)) {
            return null;
        }

        // Always sanitize every value coming from external table
        return array_map('sanitize_text_field', array_map('wp_unslash', $row));
    }

    /**
     * Try to fetch extra client contact info from the wappo_clients table
     * using client_id stored on the appointment row.
     * Returns sanitized array or empty array.
     */
    private static function get_wapp_client($row) {
        global $wpdb;
        $tables = self::discover_tables();
        if (empty($tables['clients'])) {
            return array();
        }

        // Try common client_id column names
        $client_id = absint($row['client_id'] ?? $row['wappo_client_id'] ?? 0);
        if (!$client_id) {
            return array();
        }

        $client = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM `{$tables['clients']}` WHERE id = %d", $client_id),
            ARRAY_A
        );
        if (empty($client)) {
            return array();
        }

        return array_map('sanitize_text_field', array_map('wp_unslash', $client));
    }

    /**
     * Extract structured, sanitized appointment fields from a raw Wappointment row.
     * Merges appointment row + client row into unified array.
     */
    private static function extract_appointment_fields($appointment_id) {
        $row = self::get_wapp_appointment($appointment_id);
        if (empty($row)) {
            return array(
                'student_name'  => 'Wappointment Student #' . absint($appointment_id),
                'student_email' => '',
                'student_phone' => '',
                'instructor'    => '',
                'topic'         => 'Driving Lesson',
                'start'         => current_time('mysql'),
                'end'           => date('Y-m-d H:i:s', strtotime(current_time('mysql') . ' +2 hours')),
                'status'        => 'upcoming',
            );
        }

        // Get client record for richer contact info
        $client = self::get_wapp_client($row);

        // Merge: prefer explicit columns, fall back to combined row fields
        $raw_name  = $row['client_name']  ?? $row['patient_name'] ?? ($client['name'] ?? '');
        $raw_email = $row['client_email'] ?? $row['email']        ?? ($client['email'] ?? '');
        $raw_phone = $row['client_phone'] ?? $row['phone']        ?? ($client['phone'] ?? '');

        // Build full name if first/last split
        if (empty($raw_name) && !empty($client['firstname'])) {
            $raw_name = trim(($client['firstname'] ?? '') . ' ' . ($client['lastname'] ?? ''));
        }
        if (empty($raw_name) && !empty($row['firstname'])) {
            $raw_name = trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? ''));
        }

        $name  = sanitize_text_field($raw_name) ?: ('Wappointment Student #' . absint($appointment_id));
        $email = sanitize_email($raw_email);
        $phone = sanitize_text_field($raw_phone);

        $instructor = sanitize_text_field($row['staff_name'] ?? $row['staff'] ?? '');
        $topic      = sanitize_text_field($row['service_name'] ?? $row['service'] ?? 'Driving Lesson');
        $start      = sanitize_text_field($row['start_datetime'] ?? $row['start_at'] ?? current_time('mysql'));
        $end        = sanitize_text_field($row['end_datetime']   ?? $row['end_at']   ?? '');
        if (empty($end)) {
            $end = date('Y-m-d H:i:s', strtotime($start . ' +2 hours'));
        }

        // Normalize status
        $wapp_status = strtolower($row['status'] ?? 'pending');
        $status_map  = array(
            'confirmed'   => 'upcoming',
            'pending'     => 'upcoming',
            'in_progress' => 'active',
            'completed'   => 'completed',
            'done'        => 'completed',
            'cancelled'   => 'cancelled',
            'canceled'    => 'cancelled',
            'rejected'    => 'cancelled',
            'no-show'     => 'cancelled',
        );
        $status = $status_map[$wapp_status] ?? 'upcoming';

        return compact('name', 'email', 'phone', 'instructor', 'topic', 'start', 'end', 'status');
    }

    // ----------------------------------------------------------------
    // Student profile upsert (by email primary, name fallback)
    // ----------------------------------------------------------------

    /**
     * Create or update a DriveFlow student record with full Wappointment client info.
     * Matching priority: email (most reliable) → name.
     * Never overwrites existing non-empty contact data with empty strings.
     *
     * @param string $name
     * @param string $email  Already sanitized
     * @param string $phone  Already sanitized
     * @return int  Student ID
     */
    private static function upsert_student($name, $email, $phone) {
        global $wpdb;
        $students_table = $wpdb->prefix . 'driveflow_students';
        $now = current_time('mysql');

        // 1. Try to find by email (most reliable unique key)
        $existing_id = null;
        if (!empty($email)) {
            $existing_id = $wpdb->get_var(
                $wpdb->prepare("SELECT id FROM `{$students_table}` WHERE email = %s LIMIT 1", $email)
            );
        }

        // 2. Fall back to name match
        if (!$existing_id && !empty($name)) {
            $existing_id = $wpdb->get_var(
                $wpdb->prepare("SELECT id FROM `{$students_table}` WHERE name = %s LIMIT 1", $name)
            );
        }

        if ($existing_id) {
            // Update only empty fields — never overwrite manually set data
            $row = $wpdb->get_row(
                $wpdb->prepare("SELECT email, phone, name FROM `{$students_table}` WHERE id = %d", $existing_id),
                ARRAY_A
            );
            $update = array('updated_at' => $now);
            if (empty($row['email']) && !empty($email))  $update['email'] = $email;
            if (empty($row['phone']) && !empty($phone))  $update['phone'] = $phone;
            if (empty($row['name'])  && !empty($name))   $update['name']  = $name;

            if (count($update) > 1) { // more than just updated_at
                $wpdb->update($students_table, $update, array('id' => $existing_id));
            }
            return (int) $existing_id;
        }

        // 3. Create new student
        $wpdb->insert($students_table, array(
            'name'       => $name,
            'email'      => $email,
            'phone'      => $phone,
            'status'     => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ), array('%s', '%s', '%s', '%s', '%s', '%s'));

        return (int) $wpdb->insert_id;
    }

    // ----------------------------------------------------------------
    // Email deduplication
    // ----------------------------------------------------------------

    /**
     * Returns true if Wappointment is configured to send its own confirmation email,
     * meaning DriveFlow should skip its own booking email to avoid duplicates.
     * (Wappointment always sends confirmations unless explicitly disabled)
     */
    private static function wappointment_handles_confirmation() {
        $settings = get_option(DriveFlow_Pro::OPTION_KEY, array());
        // Admin can override: if the setting says "skip student booking email when Wapp active"
        return !empty($settings['wapp_skip_student_booking_email']) && self::is_wappointment_active();
    }

    // ----------------------------------------------------------------
    // Event handlers
    // ----------------------------------------------------------------

    public static function on_appointment_created($appointment_id, $data = null) {
        self::sync_single(absint($appointment_id), true);
    }

    public static function on_booking_completed($appointment_id) {
        self::sync_single(absint($appointment_id), false);
        DriveFlow_Pro::notify_calendar_change();
    }

    public static function on_appointment_updated($appointment_id, $data = null) {
        self::sync_single(absint($appointment_id), false);
        DriveFlow_Pro::notify_calendar_change();
    }

    public static function on_appointment_cancelled($appointment_id, $data = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'driveflow_sessions';
        $session_id = $wpdb->get_var(
            $wpdb->prepare("SELECT id FROM `{$table}` WHERE wappointment_id = %d", absint($appointment_id))
        );
        if ($session_id) {
            $wpdb->update($table,
                array('status' => 'cancelled', 'updated_at' => current_time('mysql')),
                array('id' => (int) $session_id),
                array('%s', '%s'),
                array('%d')
            );
            DriveFlow_Pro::notify_calendar_change();
            $settings = get_option(DriveFlow_Pro::OPTION_KEY, array());
            if (!empty($settings['email_cancellation'])) {
                DriveFlow_Email_Manager::send_cancellation((int) $session_id);
            }
        }
    }

    public static function on_status_changed($appointment_id, $new_status, $old_status = '') {
        global $wpdb;
        $appointment_id = absint($appointment_id);
        $table          = $wpdb->prefix . 'driveflow_sessions';
        $settings       = get_option(DriveFlow_Pro::OPTION_KEY, array());

        $status_map = array(
            'confirmed'   => 'upcoming',
            'pending'     => 'upcoming',
            'in_progress' => 'active',
            'completed'   => 'completed',
            'done'        => 'completed',
            'cancelled'   => 'cancelled',
            'canceled'    => 'cancelled',
            'rejected'    => 'cancelled',
            'no-show'     => 'cancelled',
        );
        $mapped_status = $status_map[strtolower((string) $new_status)] ?? 'upcoming';

        $wpdb->update(
            $table,
            array('status' => $mapped_status, 'updated_at' => current_time('mysql')),
            array('wappointment_id' => $appointment_id),
            array('%s', '%s'),
            array('%d')
        );
        DriveFlow_Pro::notify_calendar_change();

        $session_id = $wpdb->get_var(
            $wpdb->prepare("SELECT id FROM `{$table}` WHERE wappointment_id = %d", $appointment_id)
        );
        if ($session_id) {
            if ('completed' === $mapped_status && !empty($settings['email_student_completion'])) {
                DriveFlow_Email_Manager::send_student_completion((int) $session_id);
            } elseif ('cancelled' === $mapped_status && !empty($settings['email_cancellation'])) {
                DriveFlow_Email_Manager::send_cancellation((int) $session_id);
            }
        }
    }

    // ----------------------------------------------------------------
    // Core sync logic
    // ----------------------------------------------------------------

    /**
     * Sync a single Wappointment appointment into DriveFlow sessions + student records.
     *
     * @param int  $appointment_id
     * @param bool $is_new_booking  True when triggered by _created hook
     * @return int|false  DriveFlow session ID, or false on failure
     */
    public static function sync_single($appointment_id, $is_new_booking = false) {
        $appointment_id = absint($appointment_id);
        if (!$appointment_id) return false;

        // Respect admin toggle
        $settings = get_option(DriveFlow_Pro::OPTION_KEY, array());
        if (isset($settings['wappointment_sync_enabled']) && '0' === $settings['wappointment_sync_enabled']) {
            return false;
        }

        // Extract sanitized data from Wappointment
        $fields = self::extract_appointment_fields($appointment_id);

        // Upsert student profile (email match first, then name)
        $student_id = self::upsert_student($fields['name'], $fields['email'], $fields['phone']);

        global $wpdb;
        $sessions_table   = $wpdb->prefix . 'driveflow_sessions';
        $now              = current_time('mysql');
        $existing_session = $wpdb->get_var(
            $wpdb->prepare("SELECT id FROM `{$sessions_table}` WHERE wappointment_id = %d", $appointment_id)
        );

        if ($existing_session) {
            // Update existing session
            $wpdb->update($sessions_table, array(
                'student_name'    => $fields['name'],
                'student_id'      => $student_id,
                'instructor_name' => $fields['instructor'],
                'lesson_topic'    => $fields['topic'],
                'scheduled_start' => $fields['start'],
                'scheduled_end'   => $fields['end'],
                'status'          => $fields['status'],
                'updated_at'      => $now,
            ), array('id' => (int) $existing_session),
               array('%s','%d','%s','%s','%s','%s','%s','%s'),
               array('%d'));

            return (int) $existing_session;
        }

        // Insert new session
        $wpdb->insert($sessions_table, array(
            'student_name'    => $fields['name'],
            'student_id'      => $student_id,
            'instructor_name' => $fields['instructor'],
            'plate_number'    => '',
            'session_number'  => 1,
            'lesson_topic'    => $fields['topic'],
            'scheduled_start' => $fields['start'],
            'scheduled_end'   => $fields['end'],
            'status'          => $fields['status'],
            'wappointment_id' => $appointment_id,
            'form_data'       => wp_json_encode(array(
                'source'        => 'wappointment',
                'wapp_id'       => $appointment_id,
                'synced_at'     => $now,
            )),
            'signature'       => '',
            'selfie'          => '',
            'created_by'      => 0,
            'created_at'      => $now,
            'updated_at'      => $now,
        ), array('%s','%d','%s','%s','%d','%s','%s','%s','%s','%d','%s','%s','%s','%d','%s','%s'));

        $new_session_id = (int) $wpdb->insert_id;

        if ($new_session_id && $is_new_booking) {
            // Only send DriveFlow booking email to student if:
            // 1. The admin has it enabled, AND
            // 2. We are NOT set to defer to Wappointment's own confirmation
            if (!empty($settings['email_student_booking']) && !self::wappointment_handles_confirmation()) {
                DriveFlow_Email_Manager::send_student_booking($new_session_id);
            }
            // Instructor notification is always useful (different audience than Wappointment's client email)
            if (!empty($settings['email_instructor_booking'])) {
                DriveFlow_Email_Manager::send_instructor_booking($new_session_id);
            }
        }

        return $new_session_id;
    }

    /**
     * Bulk-sync all Wappointment appointments into DriveFlow.
     * Processes in batches of 50 to avoid memory/timeout issues.
     *
     * @return array  ['synced' => int, 'message' => string]
     */
    public static function sync_all() {
        global $wpdb;
        $tables = self::discover_tables();

        if (empty($tables['appointments'])) {
            return array(
                'synced'  => 0,
                'message' => 'Wappointment appointment database table not found. Once your first online booking is placed, it will sync automatically in real-time.',
            );
        }

        // Fetch in batches — max 200 most recent
        $appointments = $wpdb->get_results(
            "SELECT id FROM `{$tables['appointments']}` ORDER BY id DESC LIMIT 200",
            ARRAY_A
        );

        if (empty($appointments)) {
            return array('synced' => 0, 'message' => 'No Wappointment appointments found to sync.');
        }

        $count   = 0;
        $skipped = 0;
        foreach ($appointments as $app) {
            $id = absint($app['id'] ?? 0);
            if (!$id) continue;
            $result = self::sync_single($id, false);
            if ($result) {
                $count++;
            } else {
                $skipped++;
            }
        }

        return array(
            'synced'  => $count,
            'message' => "Sync complete: {$count} appointments imported/updated" . ($skipped ? ", {$skipped} skipped." : '.'),
        );
    }

    // ----------------------------------------------------------------
    // Outbound Two-Way Synchronization (DriveFlow -> Wappointment)
    // ----------------------------------------------------------------

    /**
     * Push or update a DriveFlow session to Wappointment's database.
     * Ensures slots booked in DriveFlow become unavailable in Wappointment.
     *
     * @param int $session_id
     * @return int|false  Wappointment appointment ID or false
     */
    public static function push_session_to_wapp($session_id) {
        $session_id = absint($session_id);
        if (!$session_id) return false;

        $settings = get_option(DriveFlow_Pro::OPTION_KEY, array());
        if (isset($settings['wappointment_sync_enabled']) && '0' === $settings['wappointment_sync_enabled']) {
            return false;
        }

        $tables = self::discover_tables();
        if (empty($tables['appointments'])) {
            return false;
        }

        global $wpdb;
        $sessions_table = $wpdb->prefix . 'driveflow_sessions';
        $session = $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$sessions_table}` WHERE id = %d", $session_id), ARRAY_A);
        if (!$session) return false;

        $cols = self::get_table_columns($tables['appointments']);
        $now  = current_time('mysql');

        // Status mapping (DriveFlow -> Wappointment)
        $status_map = array(
            'upcoming'  => 'confirmed',
            'active'    => 'in_progress',
            'completed' => 'completed',
            'cancelled' => 'cancelled',
        );
        $target_status = $status_map[$session['status']] ?? 'confirmed';

        $wapp_id = absint($session['wappointment_id'] ?? 0);

        if ($wapp_id > 0) {
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM `{$tables['appointments']}` WHERE id = %d", $wapp_id));
            if ($exists) {
                $update = array();
                if (in_array('status', $cols, true)) $update['status'] = $target_status;
                if (in_array('start_datetime', $cols, true)) $update['start_datetime'] = $session['scheduled_start'];
                elseif (in_array('start_at', $cols, true))  $update['start_at'] = $session['scheduled_start'];
                if (in_array('end_datetime', $cols, true))   $update['end_datetime'] = $session['scheduled_end'];
                elseif (in_array('end_at', $cols, true))    $update['end_at'] = $session['scheduled_end'];
                if (in_array('updated_at', $cols, true))    $update['updated_at'] = $now;

                if (!empty($update)) {
                    $wpdb->update($tables['appointments'], $update, array('id' => $wapp_id));
                }
                return $wapp_id;
            }
        }

        // Match or create client in Wappointment
        $client_id = 0;
        if (!empty($tables['clients'])) {
            $student_id = absint($session['student_id'] ?? 0);
            $st_row = null;
            if ($student_id) {
                $st_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$wpdb->prefix}driveflow_students` WHERE id = %d", $student_id), ARRAY_A);
            }
            $st_name  = $st_row['name']  ?? $session['student_name'];
            $st_email = $st_row['email'] ?? '';
            $st_phone = $st_row['phone'] ?? '';

            $client_cols = self::get_table_columns($tables['clients']);
            if (!empty($st_email) && in_array('email', $client_cols, true)) {
                $client_id = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM `{$tables['clients']}` WHERE email = %s LIMIT 1", $st_email));
            }
            if (!$client_id && in_array('name', $client_cols, true)) {
                $client_id = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM `{$tables['clients']}` WHERE name = %s LIMIT 1", $st_name));
            }
            if (!$client_id) {
                $c_payload = array();
                if (in_array('name', $client_cols, true))  $c_payload['name'] = $st_name;
                if (in_array('email', $client_cols, true)) $c_payload['email'] = $st_email;
                if (in_array('phone', $client_cols, true)) $c_payload['phone'] = $st_phone;
                if (in_array('created_at', $client_cols, true)) $c_payload['created_at'] = $now;
                if (!empty($c_payload)) {
                    $wpdb->insert($tables['clients'], $c_payload);
                    $client_id = (int) $wpdb->insert_id;
                }
            }
        }

        // Match staff in Wappointment
        $staff_id = 0;
        if (!empty($tables['staff']) && !empty($session['instructor_name'])) {
            $staff_cols = self::get_table_columns($tables['staff']);
            if (in_array('name', $staff_cols, true)) {
                $staff_id = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM `{$tables['staff']}` WHERE name = %s LIMIT 1", $session['instructor_name']));
            }
        }

        $insert_data = array();
        if ($client_id && in_array('client_id', $cols, true)) $insert_data['client_id'] = $client_id;
        if (in_array('client_name', $cols, true))             $insert_data['client_name'] = $session['student_name'];
        if (in_array('staff_id', $cols, true) && $staff_id)   $insert_data['staff_id'] = $staff_id;
        if (in_array('staff_name', $cols, true))              $insert_data['staff_name'] = $session['instructor_name'];
        if (in_array('service_name', $cols, true))            $insert_data['service_name'] = $session['lesson_topic'] ?: 'Driving Lesson';
        if (in_array('start_datetime', $cols, true))          $insert_data['start_datetime'] = $session['scheduled_start'];
        elseif (in_array('start_at', $cols, true))            $insert_data['start_at'] = $session['scheduled_start'];
        if (in_array('end_datetime', $cols, true))            $insert_data['end_datetime'] = $session['scheduled_end'];
        elseif (in_array('end_at', $cols, true))              $insert_data['end_at'] = $session['scheduled_end'];
        if (in_array('status', $cols, true))                  $insert_data['status'] = $target_status;
        if (in_array('created_at', $cols, true))              $insert_data['created_at'] = $now;
        if (in_array('updated_at', $cols, true))              $insert_data['updated_at'] = $now;

        if (!empty($insert_data)) {
            $inserted = $wpdb->insert($tables['appointments'], $insert_data);
            if ($inserted) {
                $new_wapp_id = (int) $wpdb->insert_id;
                $wpdb->update($sessions_table, array('wappointment_id' => $new_wapp_id), array('id' => $session_id), array('%d'), array('%d'));
                return $new_wapp_id;
            }
        }

        return false;
    }

    /**
     * Cancel an appointment in Wappointment when cancelled in DriveFlow.
     *
     * @param int $session_id
     * @return bool
     */
    public static function cancel_session_in_wapp($session_id) {
        $session_id = absint($session_id);
        if (!$session_id) return false;

        $tables = self::discover_tables();
        if (empty($tables['appointments'])) return false;

        global $wpdb;
        $sessions_table = $wpdb->prefix . 'driveflow_sessions';
        $wapp_id = (int) $wpdb->get_var($wpdb->prepare("SELECT wappointment_id FROM `{$sessions_table}` WHERE id = %d", $session_id));
        if ($wapp_id > 0) {
            $cols = self::get_table_columns($tables['appointments']);
            $update = array();
            if (in_array('status', $cols, true))     $update['status'] = 'cancelled';
            if (in_array('updated_at', $cols, true)) $update['updated_at'] = current_time('mysql');
            if (!empty($update)) {
                return (bool) $wpdb->update($tables['appointments'], $update, array('id' => $wapp_id));
            }
        }
        return false;
    }

    /**
     * Push a DriveFlow blocked slot (time-off/blackout) to Wappointment so online clients cannot book it.
     *
     * @param int $blocked_slot_id
     * @return int|false
     */
    public static function push_blocked_slot_to_wapp($blocked_slot_id) {
        $blocked_slot_id = absint($blocked_slot_id);
        if (!$blocked_slot_id) return false;

        $settings = get_option(DriveFlow_Pro::OPTION_KEY, array());
        if (isset($settings['wappointment_sync_enabled']) && '0' === $settings['wappointment_sync_enabled']) {
            return false;
        }

        $tables = self::discover_tables();
        if (empty($tables['appointments'])) return false;

        global $wpdb;
        $b_table = $wpdb->prefix . 'driveflow_blocked_slots';
        $slot = $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$b_table}` WHERE id = %d", $blocked_slot_id), ARRAY_A);
        if (!$slot) return false;

        $cols = self::get_table_columns($tables['appointments']);
        $now  = current_time('mysql');

        $staff_id = 0;
        if (!empty($tables['staff']) && 'instructor' === $slot['target_type'] && !empty($slot['target_identifier'])) {
            $staff_cols = self::get_table_columns($tables['staff']);
            if (in_array('name', $staff_cols, true)) {
                $staff_id = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM `{$tables['staff']}` WHERE name = %s LIMIT 1", $slot['target_identifier']));
            }
        }

        $reason_label = !empty($slot['reason']) ? $slot['reason'] : 'DriveFlow Blocked Time';
        $payload = array();
        if (in_array('client_name', $cols, true))    $payload['client_name'] = '[BLOCKED] ' . $reason_label;
        if (in_array('staff_id', $cols, true) && $staff_id) $payload['staff_id'] = $staff_id;
        if (in_array('staff_name', $cols, true) && !empty($slot['target_identifier'])) $payload['staff_name'] = $slot['target_identifier'];
        if (in_array('service_name', $cols, true))  $payload['service_name'] = 'Maintenance / Unavailable';
        if (in_array('start_datetime', $cols, true)) $payload['start_datetime'] = $slot['start_time'];
        elseif (in_array('start_at', $cols, true))   $payload['start_at'] = $slot['start_time'];
        if (in_array('end_datetime', $cols, true))   $payload['end_datetime'] = $slot['end_time'];
        elseif (in_array('end_at', $cols, true))     $payload['end_at'] = $slot['end_time'];
        if (in_array('status', $cols, true))         $payload['status'] = 'busy';
        if (in_array('created_at', $cols, true))     $payload['created_at'] = $now;
        if (in_array('updated_at', $cols, true))     $payload['updated_at'] = $now;

        if (!empty($payload)) {
            $wpdb->insert($tables['appointments'], $payload);
            $wapp_appt_id = (int) $wpdb->insert_id;
            if ($wapp_appt_id) {
                $appended_reason = trim($slot['reason'] . " [wapp_id:{$wapp_appt_id}]");
                $wpdb->update($b_table, array('reason' => $appended_reason), array('id' => $blocked_slot_id));
                return $wapp_appt_id;
            }
        }
        return false;
    }

    /**
     * Remove or cancel blocked appointment in Wappointment when blocked slot is deleted.
     *
     * @param int $blocked_slot_id
     * @return bool
     */
    public static function remove_blocked_slot_from_wapp($blocked_slot_id) {
        $blocked_slot_id = absint($blocked_slot_id);
        if (!$blocked_slot_id) return false;

        $tables = self::discover_tables();
        if (empty($tables['appointments'])) return false;

        global $wpdb;
        $b_table = $wpdb->prefix . 'driveflow_blocked_slots';
        $reason = $wpdb->get_var($wpdb->prepare("SELECT reason FROM `{$b_table}` WHERE id = %d", $blocked_slot_id));
        if ($reason && preg_match('/\[wapp_id:(\d+)\]/', $reason, $matches)) {
            $wapp_id = absint($matches[1]);
            if ($wapp_id > 0) {
                $cols = self::get_table_columns($tables['appointments']);
                if (in_array('status', $cols, true)) {
                    $wpdb->update($tables['appointments'], array('status' => 'cancelled', 'updated_at' => current_time('mysql')), array('id' => $wapp_id));
                } else {
                    $wpdb->delete($tables['appointments'], array('id' => $wapp_id));
                }
                return true;
            }
        }
        return false;
    }

    /**
     * Ensure all active DriveFlow instructors are registered as Wappointment staff.
     *
     * @return int Count of newly synced staff
     */
    public static function sync_instructors_to_wapp_staff() {
        $tables = self::discover_tables();
        if (empty($tables['staff'])) return 0;

        global $wpdb;
        $staff_cols = self::get_table_columns($tables['staff']);
        if (!in_array('name', $staff_cols, true)) return 0;

        $instructors = $wpdb->get_results(
            "SELECT name, phone, email FROM `{$wpdb->prefix}driveflow_instructors` WHERE status = 'active'",
            ARRAY_A
        );
        if (empty($instructors)) return 0;

        $now = current_time('mysql');
        $synced = 0;

        foreach ($instructors as $ins) {
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM `{$tables['staff']}` WHERE name = %s LIMIT 1", $ins['name']));
            if (!$exists) {
                $payload = array('name' => $ins['name']);
                if (!empty($ins['email']) && in_array('email', $staff_cols, true)) $payload['email'] = $ins['email'];
                if (!empty($ins['phone']) && in_array('phone', $staff_cols, true)) $payload['phone'] = $ins['phone'];
                if (in_array('status', $staff_cols, true))     $payload['status'] = 'active';
                if (in_array('created_at', $staff_cols, true)) $payload['created_at'] = $now;
                if (in_array('updated_at', $staff_cols, true)) $payload['updated_at'] = $now;

                $wpdb->insert($tables['staff'], $payload);
                $synced++;
            }
        }
        return $synced;
    }
}

register_activation_hook(DRIVEFLOW_PRO_FILE, array('DriveFlow_Pro', 'activate'));
add_action('plugins_loaded', array('DriveFlow_Pro', 'boot'));



