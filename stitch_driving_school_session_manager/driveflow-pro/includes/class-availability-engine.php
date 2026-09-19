<?php
defined('ABSPATH') || exit;

/**
 * Class DriveFlow_Availability_Engine
 * Intelligent schedule calculations, instructor availability slots, and vehicle conflict prevention.
 */
class DriveFlow_Availability_Engine {

    /**
     * Check for instructor, vehicle, or blocked slot conflicts within a given time range.
     *
     * @param string $instructor_name
     * @param string $plate_number
     * @param string $start_time MySQL datetime string
     * @param string $end_time   MySQL datetime string
     * @param int    $exclude_id Optional session ID to exclude (for editing / rescheduling)
     * @return array
     */
    public static function check_collision($instructor_name, $plate_number, $start_time, $end_time, $exclude_id = 0) {
        global $wpdb;
        $table = $wpdb->prefix . 'driveflow_sessions';
        $table_blocked = $wpdb->prefix . 'driveflow_blocked_slots';

        $instructor_name = trim($instructor_name);
        $plate_number = trim($plate_number);
        $exclude_id = absint($exclude_id);

        if (empty($start_time) || empty($end_time)) {
            return array('conflict' => false, 'message' => 'Valid start and end dates required.');
        }

        // 0. Verify instructor active status
        if (!empty($instructor_name)) {
            $ins_status = $wpdb->get_var($wpdb->prepare("SELECT status FROM {$wpdb->prefix}driveflow_instructors WHERE name = %s", $instructor_name));
            if ($ins_status && 'inactive' === $ins_status) {
                return array(
                    'conflict' => true,
                    'type' => 'instructor_inactive',
                    'reason' => sprintf("Instructor '%s' is currently inactive and cannot be scheduled.", esc_html($instructor_name))
                );
            }
        }

        // 0b. Verify vehicle active status
        if (!empty($plate_number)) {
            $veh_status = $wpdb->get_var($wpdb->prepare("SELECT status FROM {$wpdb->prefix}driveflow_vehicles WHERE plate_number = %s", $plate_number));
            if ($veh_status && 'inactive' === $veh_status) {
                return array(
                    'conflict' => true,
                    'type' => 'vehicle_inactive',
                    'reason' => sprintf("Vehicle [%s] is currently set to inactive and cannot be scheduled.", esc_html($plate_number))
                );
            }
        }

        // 1. Check Blocked Slots Table first (school blackout, instructor time-off, car maintenance)
        $blocked_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_blocked));
        if ($blocked_exists) {
            $blocked_slots = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$table_blocked} 
                 WHERE start_time < %s AND end_time > %s",
                $end_time,
                $start_time
            ));

            if (!empty($blocked_slots)) {
                foreach ($blocked_slots as $bs) {
                    $b_start = date_i18n('M j, g:i A', strtotime($bs->start_time));
                    $b_end = date_i18n('g:i A', strtotime($bs->end_time));
                    $reason_text = !empty($bs->reason) ? " Reason: {$bs->reason}." : '';

                    // School-wide blackout
                    if ('all' === $bs->target_type) {
                        return array(
                            'conflict' => true,
                            'type' => 'blocked_school',
                            'reason' => sprintf("This time window (%s to %s) is disabled by academy administration.%s", $b_start, $b_end, $reason_text)
                        );
                    }

                    // Instructor blackout
                    if ('instructor' === $bs->target_type && !empty($instructor_name)) {
                        if (strcasecmp(trim($bs->target_identifier), $instructor_name) === 0) {
                            return array(
                                'conflict' => true,
                                'type' => 'blocked_instructor',
                                'reason' => sprintf("Instructor '%s' is unavailable from %s to %s.%s", esc_html($instructor_name), $b_start, $b_end, $reason_text)
                            );
                        }
                    }

                    // Vehicle blackout
                    if ('vehicle' === $bs->target_type && !empty($plate_number)) {
                        if (strcasecmp(trim($bs->target_identifier), $plate_number) === 0) {
                            return array(
                                'conflict' => true,
                                'type' => 'blocked_vehicle',
                                'reason' => sprintf("Vehicle [%s] is blocked (maintenance/inspection) from %s to %s.%s", esc_html($plate_number), $b_start, $b_end, $reason_text)
                            );
                        }
                    }
                }
            }
        }

        // 2. Check Overlapping Scheduled Sessions: (StartA < EndB) and (EndA > StartB)
        $query = "SELECT id, student_name, instructor_name, plate_number, scheduled_start, scheduled_end, status 
                  FROM {$table} 
                  WHERE status IN ('upcoming', 'active')
                  AND scheduled_start < %s 
                  AND scheduled_end > %s";
        
        $params = array($end_time, $start_time);

        if ($exclude_id > 0) {
            $query .= " AND id != %d";
            $params[] = $exclude_id;
        }

        $conflicts = $wpdb->get_results($wpdb->prepare($query, $params));

        if (!empty($conflicts)) {
            foreach ($conflicts as $c) {
                // Check vehicle clash
                if (!empty($plate_number) && strcasecmp(trim($c->plate_number), $plate_number) === 0) {
                    $start_fmt = date_i18n('g:i A', strtotime($c->scheduled_start));
                    $end_fmt = date_i18n('g:i A', strtotime($c->scheduled_end));
                    return array(
                        'conflict' => true,
                        'type' => 'vehicle',
                        'session_id' => $c->id,
                        'reason' => sprintf(
                            "Vehicle [%s] is already booked for Session #%d (%s) from %s to %s.",
                            esc_html($plate_number),
                            $c->id,
                            esc_html($c->student_name),
                            $start_fmt,
                            $end_fmt
                        )
                    );
                }

                // Check instructor clash
                if (!empty($instructor_name) && strcasecmp(trim($c->instructor_name), $instructor_name) === 0) {
                    $start_fmt = date_i18n('g:i A', strtotime($c->scheduled_start));
                    $end_fmt = date_i18n('g:i A', strtotime($c->scheduled_end));
                    return array(
                        'conflict' => true,
                        'type' => 'instructor',
                        'session_id' => $c->id,
                        'reason' => sprintf(
                            "Instructor '%s' is already conducting Session #%d (%s) from %s to %s.",
                            esc_html($instructor_name),
                            $c->id,
                            esc_html($c->student_name),
                            $start_fmt,
                            $end_fmt
                        )
                    );
                }
            }
        }

        return array(
            'conflict' => false,
            'message' => 'Vehicle and instructor are completely free during this time slot.'
        );
    }

    /**
     * Add a blocked time slot (school blackout, instructor off, or vehicle maintenance).
     */
    public static function add_blocked_slot($target_type, $target_identifier, $start_time, $end_time, $reason = '', $user_id = 0) {
        global $wpdb;
        $table = $wpdb->prefix . 'driveflow_blocked_slots';
        $target_type = in_array($target_type, array('all', 'instructor', 'vehicle'), true) ? $target_type : 'all';
        $now = current_time('mysql');

        $inserted = $wpdb->insert($table, array(
            'target_type' => $target_type,
            'target_identifier' => sanitize_text_field($target_identifier),
            'start_time' => $start_time,
            'end_time' => $end_time,
            'reason' => sanitize_text_field($reason),
            'created_by' => $user_id ? absint($user_id) : get_current_user_id(),
            'created_at' => $now
        ), array('%s', '%s', '%s', '%s', '%s', '%d', '%s'));

        $inserted_id = $inserted ? (int) $wpdb->insert_id : false;
        if ($inserted_id && class_exists('DriveFlow_Wappointment_Sync')) {
            DriveFlow_Wappointment_Sync::push_blocked_slot_to_wapp($inserted_id);
        }

        return $inserted_id;
    }

    /**
     * Delete a blocked time slot.
     */
    public static function delete_blocked_slot($id) {
        global $wpdb;
        $id = absint($id);
        if (class_exists('DriveFlow_Wappointment_Sync')) {
            DriveFlow_Wappointment_Sync::remove_blocked_slot_from_wapp($id);
        }
        $table = $wpdb->prefix . 'driveflow_blocked_slots';
        return $wpdb->delete($table, array('id' => $id), array('%d'));
    }

    /**
     * Get all blocked time slots overlapping a given range.
     */
    public static function get_blocked_slots($start_time, $end_time, $target_type = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'driveflow_blocked_slots';
        $query = "SELECT * FROM {$table} WHERE start_time < %s AND end_time > %s";
        $params = array($end_time, $start_time);

        if (!empty($target_type)) {
            $query .= " AND target_type = %s";
            $params[] = $target_type;
        }

        $query .= " ORDER BY start_time ASC";
        return $wpdb->get_results($wpdb->prepare($query, $params), ARRAY_A);
    }

    /**
     * Get list of vehicles busy during a specific time range.
     */
    public static function get_busy_vehicles($start_time, $end_time, $exclude_id = 0) {
        global $wpdb;
        $table = $wpdb->prefix . 'driveflow_sessions';
        $table_blocked = $wpdb->prefix . 'driveflow_blocked_slots';

        $query = "SELECT DISTINCT plate_number 
                  FROM {$table} 
                  WHERE status IN ('upcoming', 'active') 
                  AND plate_number != ''
                  AND scheduled_start < %s 
                  AND scheduled_end > %s";

        $params = array($end_time, $start_time);
        if ($exclude_id > 0) {
            $query .= " AND id != %d";
            $params[] = $exclude_id;
        }

        $results = $wpdb->get_col($wpdb->prepare($query, $params));
        $busy = is_array($results) ? array_map('trim', $results) : array();

        // Also check blocked vehicles
        $blocked_plates = $wpdb->get_col($wpdb->prepare(
            "SELECT target_identifier FROM {$table_blocked} 
             WHERE target_type = 'vehicle' AND target_identifier != ''
             AND start_time < %s AND end_time > %s",
            $end_time,
            $start_time
        ));

        if (!empty($blocked_plates)) {
            $busy = array_unique(array_merge($busy, array_map('trim', $blocked_plates)));
        }

        return array_values($busy);
    }

    /**
     * Generate free 2-hour time slots (between 08:00 and 20:00) for an instructor on a date.
     *
     * @param string|int $instructor Name or ID
     * @param string     $date YYYY-MM-DD
     * @param int        $slot_duration_mins Default 120 (2 hours)
     * @return array
     */
    public static function get_instructor_free_slots($instructor, $date, $slot_duration_mins = 120) {
        global $wpdb;
        $table_sessions = $wpdb->prefix . 'driveflow_sessions';
        $table_instructors = $wpdb->prefix . 'driveflow_instructors';
        $table_blocked = $wpdb->prefix . 'driveflow_blocked_slots';

        $date = sanitize_text_field($date);
        if (!$date || !strtotime($date)) {
            $date = current_time('Y-m-d');
        }

        // Fetch instructor schedule profile
        $instructor_row = null;
        if (is_numeric($instructor)) {
            $instructor_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_instructors} WHERE id = %d", $instructor));
        } else {
            $instructor_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_instructors} WHERE name = %s", $instructor));
        }

        $instructor_name = $instructor_row ? $instructor_row->name : (string)$instructor;

        if ($instructor_row && 'active' !== $instructor_row->status) {
            return array('slots' => array(), 'message' => "Instructor '{$instructor_name}' is currently inactive.");
        }

        // Determine working window for this day of week (Default: 08:00 - 20:00)
        $day_of_week = strtolower(date('D', strtotime($date))); // mon, tue, wed...
        $start_hour = 8;  // 8:00 AM (08:00)
        $end_hour = 20;   // 8:00 PM (20:00)

        if ($instructor_row && !empty($instructor_row->working_hours)) {
            $hours_data = json_decode($instructor_row->working_hours, true);
            if (is_array($hours_data) && !empty($hours_data[$day_of_week])) {
                $day_config = $hours_data[$day_of_week];
                if (!empty($day_config['closed'])) {
                    return array('slots' => array(), 'message' => "Instructor is off on {$day_of_week}.");
                }
                if (!empty($day_config['start'])) $start_hour = (int) explode(':', $day_config['start'])[0];
                if (!empty($day_config['end'])) $end_hour = (int) explode(':', $day_config['end'])[0];
            }
        }

        // Fetch booked sessions for instructor on this date
        $booked = $wpdb->get_results($wpdb->prepare(
            "SELECT scheduled_start, scheduled_end FROM {$table_sessions} 
             WHERE status IN ('upcoming', 'active') 
             AND instructor_name = %s 
             AND DATE(scheduled_start) = %s",
            $instructor_name,
            $date
        ));

        // Fetch blocked slots for this date (school-wide or instructor-specific)
        $blocked = array();
        $blocked_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_blocked));
        if ($blocked_exists) {
            $blocked = $wpdb->get_results($wpdb->prepare(
                "SELECT start_time, end_time, reason FROM {$table_blocked} 
                 WHERE (target_type = 'all' OR (target_type = 'instructor' AND target_identifier = %s))
                 AND DATE(start_time) <= %s AND DATE(end_time) >= %s",
                $instructor_name,
                $date,
                $date
            ));
        }

        // Look up assigned vehicle for this instructor
        $assigned_vehicle_plate = '';
        if ($instructor_row && !empty($instructor_row->default_vehicle_id)) {
            $assigned_vehicle_plate = $wpdb->get_var($wpdb->prepare(
                "SELECT plate_number FROM {$wpdb->prefix}driveflow_vehicles WHERE id = %d AND status = 'active'",
                $instructor_row->default_vehicle_id
            ));
        }

        // Total active fleet vehicles count
        $all_active_vehicles = $wpdb->get_col("SELECT plate_number FROM {$wpdb->prefix}driveflow_vehicles WHERE status = 'active'");
        $total_active_vehicles = is_array($all_active_vehicles) ? count($all_active_vehicles) : 0;

        // Generate candidate 2-hour slots
        $slots = array();
        $slot_sec = $slot_duration_mins * 60;
        $day_start_ts = strtotime("{$date} {$start_hour}:00:00");
        $day_end_ts = strtotime("{$date} {$end_hour}:00:00");

        for ($t = $day_start_ts; $t + $slot_sec <= $day_end_ts; $t += $slot_sec) {
            $slot_start = date('Y-m-d H:i:s', $t);
            $slot_end = date('Y-m-d H:i:s', $t + $slot_sec);

            // Skip past slots for today
            if (strtotime($slot_start) <= current_time('timestamp')) {
                continue;
            }

            // Check if slot overlaps with booked sessions
            $is_free = true;
            if (!empty($booked)) {
                foreach ($booked as $b) {
                    if ($slot_start < $b->scheduled_end && $slot_end > $b->scheduled_start) {
                        $is_free = false;
                        break;
                    }
                }
            }

            // Check if slot overlaps with blocked windows
            if ($is_free && !empty($blocked)) {
                foreach ($blocked as $blk) {
                    if ($slot_start < $blk->end_time && $slot_end > $blk->start_time) {
                        $is_free = false;
                        break;
                    }
                }
            }

            // Check fleet vehicle availability for this slot
            if ($is_free) {
                $busy_vehicles = self::get_busy_vehicles($slot_start, $slot_end);
                if (!empty($assigned_vehicle_plate)) {
                    // Dedicated instructor vehicle must not be busy or under maintenance
                    if (in_array(trim($assigned_vehicle_plate), $busy_vehicles, true)) {
                        $is_free = false;
                    }
                } elseif ($total_active_vehicles > 0) {
                    // Check that at least one academy fleet car is free
                    if (count($busy_vehicles) >= $total_active_vehicles) {
                        $is_free = false;
                    }
                }
            }

            if ($is_free) {
                $slots[] = array(
                    'start_iso' => date('Y-m-d\TH:i', $t),
                    'end_iso'   => date('Y-m-d\TH:i', $t + $slot_sec),
                    'start_fmt' => date_i18n('g:i A', $t),
                    'end_fmt'   => date_i18n('g:i A', $t + $slot_sec),
                    'label'     => date_i18n('g:i A', $t) . ' - ' . date_i18n('g:i A', $t + $slot_sec),
                );
            }
        }

        return array(
            'instructor' => $instructor_name,
            'date' => $date,
            'slots' => $slots,
            'total_available' => count($slots)
        );
    }
}
