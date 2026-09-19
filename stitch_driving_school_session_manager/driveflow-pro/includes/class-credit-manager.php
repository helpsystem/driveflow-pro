<?php
defined('ABSPATH') || exit;

/**
 * Class DriveFlow_Credit_Manager
 * Manages student driving lesson packages, remaining balance, extra session additions, and activity ledger.
 */
class DriveFlow_Credit_Manager {

    /**
     * Top-up or add extra sessions to a student's package.
     *
     * @param int    $student_id
     * @param int    $amount Positive integer (e.g. 1, 3, 5)
     * @param string $notes Reason or payment invoice reference
     * @param int    $admin_id Current admin user ID
     * @return bool|int
     */
    public static function add_extra_sessions($student_id, $amount, $notes = '', $admin_id = 0) {
        global $wpdb;
        $student_id = absint($student_id);
        $amount = intval($amount);

        if ($student_id <= 0 || $amount <= 0) {
            return false;
        }

        $now = current_time('mysql');
        $admin_id = $admin_id ? absint($admin_id) : get_current_user_id();

        // 1. Update student record
        $table_students = $wpdb->prefix . 'driveflow_students';
        $wpdb->query($wpdb->prepare(
            "UPDATE {$table_students} 
             SET total_sessions = total_sessions + %d, updated_at = %s 
             WHERE id = %d",
            $amount,
            $now,
            $student_id
        ));

        // 2. Insert into ledger table
        $table_credits = $wpdb->prefix . 'driveflow_student_credits';
        $inserted = $wpdb->insert($table_credits, array(
            'student_id'    => $student_id,
            'action_type'   => 'extra_session_add',
            'credits_delta' => $amount,
            'notes'         => sanitize_text_field($notes ?: sprintf('+%d Extra Lessons Added', $amount)),
            'created_by'    => $admin_id,
            'created_at'    => $now
        ), array('%d', '%s', '%d', '%s', '%d', '%s'));

        return $inserted ? $wpdb->insert_id : false;
    }

    /**
     * Record a deduction when a driving session completes.
     *
     * @param int $student_id
     * @param int $session_id
     */
    public static function deduct_session_credit($student_id, $session_id) {
        global $wpdb;
        $student_id = absint($student_id);
        $session_id = absint($session_id);

        if (!$student_id || !$session_id) return;

        $table_credits = $wpdb->prefix . 'driveflow_student_credits';

        // Check if already deducted for this session
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table_credits} 
             WHERE student_id = %d 
             AND action_type = 'session_deduction' 
             AND notes LIKE %s",
            $student_id,
            "%Session #{$session_id}%"
        ));

        if ($existing) return; // already deducted

        $now = current_time('mysql');

        // Increment completed_sessions count on student
        $table_students = $wpdb->prefix . 'driveflow_students';
        $wpdb->query($wpdb->prepare(
            "UPDATE {$table_students} 
             SET completed_sessions = completed_sessions + 1, updated_at = %s 
             WHERE id = %d",
            $now,
            $student_id
        ));

        // Add ledger record
        $wpdb->insert($table_credits, array(
            'student_id'    => $student_id,
            'action_type'   => 'session_deduction',
            'credits_delta' => -1,
            'notes'         => sprintf('Completed Driving Session #%d', $session_id),
            'created_by'    => get_current_user_id(),
            'created_at'    => $now
        ), array('%d', '%s', '%d', '%s', '%d', '%s'));
    }

    /**
     * Retrieve complete student profile, package progress, and activity logs.
     *
     * @param int|string $identifier Student ID, email, or phone
     * @return array|null
     */
    public static function get_student_profile($identifier) {
        global $wpdb;
        $table_students = $wpdb->prefix . 'driveflow_students';
        $table_sessions = $wpdb->prefix . 'driveflow_sessions';
        $table_credits  = $wpdb->prefix . 'driveflow_student_credits';

        $student = null;
        if (is_numeric($identifier)) {
            $student = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_students} WHERE id = %d", $identifier), ARRAY_A);
        } else {
            $id_str = sanitize_text_field($identifier);
            $student = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_students} WHERE email = %s OR phone = %s OR name = %s", $id_str, $id_str, $id_str), ARRAY_A);
        }

        if (!$student) {
            return null;
        }

        $student_id = (int)$student['id'];
        $student_name = $student['name'];

        // Calculate session numbers
        $total_sessions = (int)($student['total_sessions'] ?? 0);
        $completed_sessions = (int)($student['completed_sessions'] ?? 0);

        // Fetch actual sessions list
        $sessions = $wpdb->get_results($wpdb->prepare(
            "SELECT id, session_number, lesson_topic, scheduled_start, scheduled_end, status, instructor_name, plate_number, form_data, selfie, signature 
             FROM {$table_sessions} 
             WHERE student_id = %d OR student_name = %s 
             ORDER BY scheduled_start DESC LIMIT 100",
            $student_id,
            $student_name
        ), ARRAY_A);

        // Update real completed count if needed
        $actual_completed = 0;
        foreach ($sessions as $s) {
            if ('completed' === $s['status']) $actual_completed++;
        }
        if ($actual_completed > $completed_sessions) {
            $completed_sessions = $actual_completed;
        }

        $remaining_sessions = max(0, $total_sessions - $completed_sessions);

        // Fetch credit transaction ledger
        $ledger = $wpdb->get_results($wpdb->prepare(
            "SELECT id, action_type, credits_delta, notes, created_at 
             FROM {$table_credits} 
             WHERE student_id = %d 
             ORDER BY created_at DESC LIMIT 50",
            $student_id
        ), ARRAY_A);

        $token = self::get_or_create_token($student_id);
        $magic_link = self::get_magic_booking_url($student_id);

        return array(
            'id'                 => $student_id,
            'name'               => $student['name'],
            'phone'              => $student['phone'] ?? '',
            'email'              => $student['email'] ?? '',
            'license_number'     => $student['license_number'] ?? '',
            'package_name'       => $student['package_name'] ?: 'Standard Driving Course',
            'total_sessions'     => $total_sessions,
            'completed_sessions' => $completed_sessions,
            'remaining_sessions' => $remaining_sessions,
            'balance_notes'      => $student['balance_notes'] ?? '',
            'booking_token'      => $token,
            'magic_link'         => $magic_link,
            'sessions'           => $sessions,
            'ledger'             => $ledger
        );
    }

    /**
     * Retrieve or generate unique booking token for student.
     */
    public static function get_or_create_token($student_id) {
        global $wpdb;
        $student_id = absint($student_id);
        if (!$student_id) return '';

        $table = $wpdb->prefix . 'driveflow_students';
        $token = $wpdb->get_var($wpdb->prepare("SELECT booking_token FROM {$table} WHERE id = %d", $student_id));
        if (!empty($token)) {
            return $token;
        }

        // Generate high-entropy 32-char hex token
        $new_token = wp_generate_password(32, false, false);
        $wpdb->update($table, array('booking_token' => $new_token), array('id' => $student_id), array('%s'), array('%d'));
        return $new_token;
    }

    /**
     * Retrieve student by booking token.
     */
    public static function get_student_by_token($token) {
        global $wpdb;
        $token = sanitize_text_field(trim((string)$token));
        if (empty($token) || strlen($token) < 7) return null;

        // 1. Check expirable / single-use magic tokens first
        if (class_exists('DriveFlow_Magic_Links')) {
            $verified = DriveFlow_Magic_Links::verify_token($token);
            if (!is_wp_error($verified) && !empty($verified['target_id']) && 'student_booking' === $verified['token_type']) {
                return self::get_student_profile($verified['target_id']);
            }
        }

        // 2. Fallback to permanent student booking token
        $table = $wpdb->prefix . 'driveflow_students';
        $student = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE booking_token = %s", $token), ARRAY_A);
        if (!$student) return null;

        return self::get_student_profile($student['id']);
    }

    /**
     * Generate complete Magic Booking URL for a student.
     */
    public static function get_magic_booking_url($student_id) {
        $token = self::get_or_create_token($student_id);
        if (!$token) return '';

        $settings = get_option('driveflow_pro_settings', array());
        $page_id = absint($settings['magic_booking_page_id'] ?? 0);

        if ($page_id && 'publish' === get_post_status($page_id)) {
            $base_url = get_permalink($page_id);
        } else {
            $base_url = home_url('/book-driving-lesson/');
        }

        return add_query_arg('token', $token, $base_url);
    }

    /**
     * Execute student self-booking from their magic link.
     *
     * @param string $token
     * @param string $start_time MySQL datetime string
     * @param string $end_time   MySQL datetime string
     * @param string $instructor_name
     * @param string $lesson_topic
     * @return array
     */
    public static function self_book_session($token, $start_time, $end_time, $instructor_name = '', $lesson_topic = 'Driving Lesson') {
        global $wpdb;
        $profile = self::get_student_by_token($token);
        if (!$profile) {
            return array('success' => false, 'message' => 'Invalid or expired student booking link.');
        }

        if ($profile['remaining_sessions'] <= 0) {
            return array('success' => false, 'message' => 'You have completed all sessions in your package. Please contact academy admin to top-up extra sessions.');
        }

        // Default or sanitize instructor
        if (empty($instructor_name)) {
            $first_ins = $wpdb->get_var("SELECT name FROM {$wpdb->prefix}driveflow_instructors WHERE status = 'active' ORDER BY id ASC LIMIT 1");
            $instructor_name = $first_ins ?: 'Academy Instructor';
        }

        // Get instructor default vehicle if available
        $plate_number = '';
        $ins_row = $wpdb->get_row($wpdb->prepare("SELECT default_vehicle_id FROM {$wpdb->prefix}driveflow_instructors WHERE name = %s", $instructor_name));
        if ($ins_row && !empty($ins_row->default_vehicle_id)) {
            $veh_plate = $wpdb->get_var($wpdb->prepare("SELECT plate_number FROM {$wpdb->prefix}driveflow_vehicles WHERE id = %d", $ins_row->default_vehicle_id));
            if ($veh_plate) $plate_number = $veh_plate;
        }

        // If no default vehicle assigned, pick an available active vehicle
        if (empty($plate_number)) {
            $busy_vehicles = DriveFlow_Availability_Engine::get_busy_vehicles($start_time, $end_time);
            $all_vehicles = $wpdb->get_col("SELECT plate_number FROM {$wpdb->prefix}driveflow_vehicles WHERE status = 'active'");
            foreach ($all_vehicles as $cand_plate) {
                if (!in_array(trim($cand_plate), $busy_vehicles, true)) {
                    $plate_number = $cand_plate;
                    break;
                }
            }
        }

        // Verify collision against instructors, vehicles, and blocked slots
        $check = DriveFlow_Availability_Engine::check_collision($instructor_name, $plate_number, $start_time, $end_time);
        if ($check['conflict']) {
            return array('success' => false, 'message' => $check['reason']);
        }

        // Calculate next session number
        $next_session_number = (int)$profile['completed_sessions'] + 1;

        $table_sessions = $wpdb->prefix . 'driveflow_sessions';
        $now = current_time('mysql');

        $inserted = $wpdb->insert($table_sessions, array(
            'student_name'    => $profile['name'],
            'student_id'      => $profile['id'],
            'instructor_name' => $instructor_name,
            'plate_number'    => $plate_number,
            'session_number'  => $next_session_number,
            'lesson_topic'    => sanitize_text_field($lesson_topic ?: 'Driving Lesson #' . $next_session_number),
            'scheduled_start' => $start_time,
            'scheduled_end'   => $end_time,
            'status'          => 'upcoming',
            'form_data'       => wp_json_encode(array('source' => 'magic_link_self_book')),
            'signature'       => '',
            'selfie'          => '',
            'created_by'      => 0,
            'created_at'      => $now,
            'updated_at'      => $now,
        ), array('%s','%d','%s','%s','%d','%s','%s','%s','%s','%s','%s','%s','%d','%s','%s'));

        if (!$inserted) {
            return array('success' => false, 'message' => 'Database error while scheduling session.');
        }

        $new_session_id = (int)$wpdb->insert_id;

        // Burn single-use magic token if applicable
        if (class_exists('DriveFlow_Magic_Links')) {
            DriveFlow_Magic_Links::mark_as_used($token);
        }

        // Two-way sync: push newly booked session to Wappointment if active
        if (class_exists('DriveFlow_Wappointment_Sync')) {
            DriveFlow_Wappointment_Sync::push_session_to_wapp($new_session_id);
        }

        // Trigger automated emails
        DriveFlow_Email_Manager::send_student_booking($new_session_id);
        DriveFlow_Email_Manager::send_instructor_booking($new_session_id);

        return array(
            'success'        => true,
            'session_id'     => $new_session_id,
            'message'        => sprintf('Lesson #%d successfully scheduled for %s (%s to %s)!', $next_session_number, date_i18n('F j, Y', strtotime($start_time)), date_i18n('g:i A', strtotime($start_time)), date_i18n('g:i A', strtotime($end_time))),
            'scheduled_start'=> $start_time,
            'scheduled_end'  => $end_time,
            'instructor'     => $instructor_name,
            'plate'          => $plate_number,
        );
    }
}
