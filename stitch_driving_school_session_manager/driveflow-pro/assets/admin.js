(function ($) {
    'use strict';

    function dfEsc(v) {
        return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function showToast(message, isError) {
        $('.df-toast').remove();
        var $toast = $('<div class="df-toast"></div>').text(message);
        if (isError) $toast.css('background', '#ef4444');
        $('body').append($toast);
        setTimeout(function () {
            $toast.fadeOut(300, function () { $(this).remove(); });
        }, 3500);
    }

    $(document).ready(function () {
        // Media upload for branding logo
        var uploadBtn = $('#driveflow-upload-logo');
        if (uploadBtn.length) {
            uploadBtn.on('click', function (e) {
                e.preventDefault();
                var frame = wp.media({
                    title: 'Select Driving School Logo',
                    multiple: false,
                    library: { type: 'image' }
                });
                frame.on('select', function () {
                    var attachment = frame.state().get('selection').first().toJSON();
                    $('#driveflow-logo-url').val(attachment.url);
                    $('#driveflow-logo-preview').attr('src', attachment.url).show();
                });
                frame.open();
            });
        }

        // Modal triggers (Add Session Modal)
        $('[data-df-open-modal]').on('click', function () {
            var target = $(this).data('df-open-modal');
            $('#' + target).addClass('is-open');
        });

        $('.df-modal-close, [data-df-close-modal]').on('click', function () {
            $('.df-modal-backdrop').removeClass('is-open');
        });

        $('.df-modal-backdrop').on('click', function (e) {
            if ($(e.target).hasClass('df-modal-backdrop')) {
                $(this).removeClass('is-open');
            }
        });

        // Auto-calculate end time in Add Session modal (2 hours default)
        $('#df-modal-start-time').on('change', function () {
            var val = $(this).val();
            if (val && !$('#df-modal-end-time').val()) {
                var d = new Date(val);
                d.setHours(d.getHours() + 2);
                var pad = function(n) { return n < 10 ? '0' + n : n; };
                var formatted = d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
                $('#df-modal-end-time').val(formatted);
            }
        });

        // View Session Details (Selfie, Signature & Skills Logbook)
        $('.df-view-details-btn').on('click', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var sessionId = $btn.data('session-id');
            var student = $btn.data('student');
            var instructor = $btn.data('instructor');
            var plate = $btn.data('plate');
            var status = $btn.data('status');
            var date = $btn.data('date');

            $('#df-detail-student').text(student || '—');
            $('#df-detail-instructor').text(instructor || '—');
            $('#df-detail-plate').text(plate || '—');
            $('#df-detail-status').text(status || '—');
            $('#df-detail-date').text(date || '—');

            var selfie = $btn.data('selfie');
            var signature = $btn.data('signature');

            if (selfie && selfie.length > 50) {
                $('#df-detail-selfie-img').attr('src', selfie).show();
                $('#df-detail-selfie-empty').hide();
            } else {
                $('#df-detail-selfie-img').hide();
                $('#df-detail-selfie-empty').show();
            }

            if (signature && signature.length > 50) {
                $('#df-detail-signature-img').attr('src', signature).show();
                $('#df-detail-signature-empty').hide();
            } else {
                $('#df-detail-signature-img').hide();
                $('#df-detail-signature-empty').show();
            }

            // Parse and render skills evaluation and notes
            var formDataRaw = $btn.data('form-data');
            var skills = null;
            var notes = '';
            if (formDataRaw) {
                try {
                    var parsed = (typeof formDataRaw === 'string') ? JSON.parse(formDataRaw) : formDataRaw;
                    skills = parsed.skills || null;
                    notes = parsed.instructor_notes || '';
                } catch (err) {}
            }

            if (skills) {
                var stars = function (score) {
                    var out = '';
                    for (var i = 1; i <= 5; i++) {
                        out += (i <= score) ? '★' : '☆';
                    }
                    return out;
                };
                $('#df-skill-clutch').text(stars(skills.clutch || 0));
                $('#df-skill-parking').text(stars(skills.parking || 0));
                $('#df-skill-steering').text(stars(skills.steering || 0));
                $('#df-skill-rules').text(stars(skills.rules || 0));
                $('#df-skills-section').show();
            } else {
                $('#df-skills-section').hide();
            }

            if (notes) {
                $('#df-detail-notes').text(notes);
                $('#df-notes-section').show();
            } else {
                $('#df-notes-section').hide();
            }

            $('#df-session-details-modal').addClass('is-open');
        });

        // Resend notification email to student/instructor
        $('.df-resend-email-btn').on('click', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var sessionId = $btn.data('session-id');
            var nonce = window.DriveFlowAdmin ? window.DriveFlowAdmin.nonce : '';

            $btn.prop('disabled', true).text('⏳...');
            showToast('Dispatching confirmation email...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'driveflow_resend_email',
                    session_id: sessionId,
                    nonce: nonce
                },
                success: function (res) {
                    if (res && res.success) {
                        showToast(res.data.message || 'Email sent successfully ✓');
                    } else {
                        showToast((res && res.data && res.data.message) || 'Error sending email', true);
                    }
                },
                error: function () {
                    showToast('Server error while sending email', true);
                },
                complete: function () {
                    $btn.prop('disabled', false).html('✉️ Email');
                }
            });
        });

        // Send test email from settings page
        $('#df-send-test-email-btn').on('click', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var targetEmail = $('#df-test-email-target').val();
            var nonce = window.DriveFlowAdmin ? window.DriveFlowAdmin.nonce : '';

            if (!targetEmail) {
                showToast('Please enter a valid recipient email address.', true);
                return;
            }

            $btn.prop('disabled', true).text('Sending...');
            showToast('Dispatching test template email...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'driveflow_send_test_email',
                    email: targetEmail,
                    nonce: nonce
                },
                success: function (res) {
                    if (res && res.success) {
                        showToast(res.data.message || 'Test email sent successfully ✓');
                    } else {
                        showToast((res && res.data && res.data.message) || 'Delivery failed', true);
                    }
                },
                error: function () {
                    showToast('Server error while sending test email', true);
                },
                complete: function () {
                    $btn.prop('disabled', false).text('Send Test Email');
                }
            });
        });

        // Quick status update AJAX
        $('.df-quick-status-btn').on('click', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var sessionId = $btn.data('session-id');
            var newStatus = $btn.data('status');
            var nonce = window.DriveFlowAdmin ? window.DriveFlowAdmin.nonce : '';

            $btn.prop('disabled', true).text('...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'driveflow_quick_status',
                    session_id: sessionId,
                    status: newStatus,
                    nonce: nonce
                },
                success: function (res) {
                    if (res && res.success) {
                        showToast('Session #' + sessionId + ' status changed to ' + newStatus.toUpperCase() + '.');
                        var $row = $btn.closest('tr');
                        var $badge = $row.find('.df-badge');
                        $badge.removeClass('df-badge-upcoming df-badge-active df-badge-completed df-badge-cancelled')
                              .addClass('df-badge-' + newStatus)
                              .text(newStatus.toUpperCase());

                        // Update buttons
                        $row.find('.df-quick-status-btn').show();
                        if (newStatus === 'active') {
                            $row.find('[data-status="active"]').hide();
                        } else if (newStatus === 'completed' || newStatus === 'cancelled') {
                            $row.find('.df-quick-status-btn').hide();
                        }
                    } else {
                        showToast((res && res.data) || 'Update failed', true);
                    }
                },
                error: function () {
                    showToast('Server error while changing status', true);
                },
                complete: function () {
                    $btn.prop('disabled', false);
                }
            });
        });

        // Sync with Wappointment
        $('#df-sync-wappointment-btn').on('click', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var nonce = window.DriveFlowAdmin ? window.DriveFlowAdmin.nonce : '';

            $btn.prop('disabled', true).text('Syncing...');
            showToast('Reading Wappointment online bookings...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'driveflow_sync_wappointment_ajax',
                    nonce: nonce
                },
                success: function (res) {
                    if (res && res.success) {
                        showToast(res.data.message || 'Sync completed successfully!');
                        setTimeout(function () {
                            window.location.reload();
                        }, 1200);
                    } else {
                        showToast((res && res.data && res.data.message) || 'Unable to sync Wappointment', true);
                        $btn.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> Sync Wappointment');
                    }
                },
                error: function () {
                    showToast('Network error during sync request', true);
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> Sync Wappointment');
                }
            });
        });

        // Real-time Booking Collision & Busy Vehicle Check
        function checkCollision() {
            var instructor = $('#df-modal-instructor').val();
            var plate = $('#df-modal-plate').val();
            var start = $('#df-modal-start-time').val();
            var end = $('#df-modal-end-time').val();
            var nonce = window.DriveFlowAdmin ? window.DriveFlowAdmin.nonce : '';

            if (!start || !end) return;

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'driveflow_check_collision',
                    instructor_name: instructor,
                    plate_number: plate,
                    scheduled_start: start,
                    scheduled_end: end,
                    nonce: nonce
                },
                success: function (res) {
                    if (res && res.success) {
                        var data = res.data;
                        var $container = $('#df-conflict-container');
                        var $alert = $('#df-conflict-alert');

                        if (data.conflict) {
                            $alert.html('⚠️ <strong>Scheduling Conflict:</strong> ' + dfEsc(data.reason));
                            $container.slideDown(200);
                        } else {
                            $container.slideUp(200);
                        }

                        // Flag busy vehicles in dropdown
                        if (data.busy_vehicles && Array.isArray(data.busy_vehicles)) {
                            $('#df-modal-plate option').each(function () {
                                var val = $(this).val();
                                if (!val) return;
                                var baseText = $(this).text().replace(/\s*\[BUSY ON ROAD 🔴\]/, '');
                                if (data.busy_vehicles.indexOf(val) !== -1) {
                                    $(this).text(baseText + ' [BUSY ON ROAD 🔴]').prop('disabled', true);
                                } else {
                                    $(this).text(baseText).prop('disabled', false);
                                }
                            });
                        }
                    }
                }
            });
        }

        $('#df-modal-start-time, #df-modal-end-time, #df-modal-instructor, #df-modal-plate').on('change', checkCollision);

        // Find Instructor Free Slots
        $('#df-find-slots-btn').on('click', function (e) {
            e.preventDefault();
            var instructor = $('#df-modal-instructor').val();
            var startVal = $('#df-modal-start-time').val();
            var date = startVal ? startVal.split('T')[0] : '';
            var nonce = window.DriveFlowAdmin ? window.DriveFlowAdmin.nonce : '';

            if (!instructor) {
                showToast('Please choose or enter an instructor first.', true);
                return;
            }

            var $btn = $(this);
            $btn.prop('disabled', true).text('Searching...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'driveflow_get_instructor_slots',
                    instructor: instructor,
                    date: date,
                    nonce: nonce
                },
                success: function (res) {
                    $btn.prop('disabled', false).text('💡 Find Free Available Slots');
                    if (res && res.success && res.data) {
                        var slots = res.data.slots || [];
                        var $box = $('#df-slots-picker-container');
                        var $list = $('#df-slots-list');
                        $list.empty();

                        if (!slots.length) {
                            $list.html('<span style="color:#d97706;">No free 2-hour slots remaining on this date for ' + instructor + '.</span>');
                        } else {
                            slots.forEach(function (slot) {
                                var $slotBtn = $('<button type="button" class="button button-small" style="background:#fff;border:1px solid #16a34a;color:#166534;font-weight:600;"></button>')
                                    .text('🕒 ' + slot.label)
                                    .data('start', slot.start_iso)
                                    .data('end', slot.end_iso);

                                $slotBtn.on('click', function () {
                                    $('#df-modal-start-time').val($(this).data('start'));
                                    $('#df-modal-end-time').val($(this).data('end'));
                                    checkCollision();
                                    $box.slideUp(150);
                                });

                                $list.append($slotBtn);
                            });
                        }
                        $box.slideDown(200);
                    }
                },
                error: function () {
                    $btn.prop('disabled', false).text('💡 Find Free Available Slots');
                    showToast('Error retrieving available slots.', true);
                }
            });
        });

        // Top-Up Extra Sessions Modal Handler
        $('.df-topup-btn').on('click', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var studentId = $btn.data('student-id');
            var name = $btn.data('student-name');
            var curTotal = $btn.data('current-total');
            var remaining = $btn.data('remaining');

            $('#df-topup-student-id').val(studentId);
            $('#df-topup-student-name').text(name);
            $('#df-topup-cur-total').text(curTotal);
            $('#df-topup-cur-remaining').text(remaining);
            $('#df-topup-notes').val('');

            $('#df-topup-modal').addClass('is-open');
        });

        $('#df-topup-form').on('submit', function (e) {
            e.preventDefault();
            var studentId = $('#df-topup-student-id').val();
            var amount = $('input[name="extra_amount"]:checked').val() || 3;
            var notes = $('#df-topup-notes').val();
            var nonce = window.DriveFlowAdmin ? window.DriveFlowAdmin.nonce : '';
            var $submitBtn = $('#df-topup-submit-btn');

            $submitBtn.prop('disabled', true).text('Adding Lessons...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'driveflow_add_extra_sessions',
                    student_id: studentId,
                    amount: amount,
                    notes: notes,
                    nonce: nonce
                },
                success: function (res) {
                    $submitBtn.prop('disabled', false).text('Add Extra Sessions');
                    if (res && res.success) {
                        showToast(res.data.message || 'Extra lessons added successfully! ✓');
                        $('#df-topup-modal').removeClass('is-open');
                        setTimeout(function () {
                            window.location.reload();
                        }, 900);
                    } else {
                        showToast((res && res.data) || 'Failed to top-up extra sessions', true);
                    }
                },
                error: function () {
                    $submitBtn.prop('disabled', false).text('Add Extra Sessions');
                    showToast('Server error while adding sessions.', true);
                }
            });
        });

        // View Complete Student Profile & Transcript Drawer
        $('.df-view-student-btn').on('click', function (e) {
            e.preventDefault();
            var studentId = $(this).data('student-id');
            var nonce = window.DriveFlowAdmin ? window.DriveFlowAdmin.nonce : '';
            var $modal = $('#df-student-profile-modal');
            var $body = $('#df-student-profile-body');

            $body.html('<div style="text-align:center;padding:40px;color:#94a3b8;">Loading student transcript and scorecard log...</div>');
            $modal.addClass('is-open');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'driveflow_get_student_profile',
                    student_id: studentId,
                    nonce: nonce
                },
                success: function (res) {
                    if (res && res.success && res.data) {
                        var p = res.data;
                        var total = parseInt(p.total_sessions, 10) || 0;
                        var completed = parseInt(p.completed_sessions, 10) || 0;
                        var remaining = parseInt(p.remaining_sessions, 10) || 0;
                        var percent = (total > 0) ? Math.min(100, Math.round((completed / total) * 100)) : 0;

                        var html = '<div style="background:#f8fafc;padding:16px;border-radius:8px;margin-bottom:20px;border:1px solid #e2e8f0;">';
                        html += '<div style="display:flex;justify-content:space-between;align-items:center;">';
                        html += '<div><h3 style="margin:0 0 4px 0;font-size:18px;">' + dfEsc(p.name) + '</h3>';
                        html += '<div style="font-size:13px;color:#64748b;">📞 ' + dfEsc(p.phone || 'N/A') + ' • ✉️ ' + dfEsc(p.email || 'N/A') + ' • Permit: <strong>' + dfEsc(p.license_number || 'N/A') + '</strong></div></div>';
                        html += '<div style="text-align:right;"><span style="font-size:22px;font-weight:800;color:' + (remaining > 0 ? '#2563eb' : '#16a34a') + ';">' + remaining + ' Left</span>';
                        html += '<div style="font-size:12px;color:#64748b;">' + completed + ' of ' + total + ' completed</div></div>';
                        html += '</div>';

                        html += '<div style="margin-top:12px;background:#e2e8f0;border-radius:999px;height:10px;overflow:hidden;">';
                        html += '<div style="background:#2563eb;height:100%;width:' + percent + '%;"></div>';
                        html += '</div></div>';

                        // Sessions table
                        html += '<h4 style="margin:0 0 10px 0;">Driving Sessions & Scorecards</h4>';
                        if (!p.sessions || !p.sessions.length) {
                            html += '<p style="color:#94a3b8;font-size:13px;">No lessons on record yet.</p>';
                        } else {
                            html += '<table style="width:100%;font-size:13px;border-collapse:collapse;margin-bottom:24px;border:1px solid #e2e8f0;">';
                            html += '<thead style="background:#f1f5f9;"><tr><th style="padding:8px;text-align:left;">Lesson</th><th style="padding:8px;text-align:left;">Date & Time</th><th style="padding:8px;text-align:left;">Instructor</th><th style="padding:8px;text-align:left;">Plate</th><th style="padding:8px;text-align:left;">Status</th></tr></thead><tbody>';
                            p.sessions.forEach(function (s) {
                                html += '<tr style="border-bottom:1px solid #f1f5f9;">';
                                html += '<td style="padding:8px;"><strong>#' + s.session_number + '</strong> ' + dfEsc(s.lesson_topic || 'Driving Lesson') + '</td>';
                                html += '<td style="padding:8px;color:#64748b;">' + s.scheduled_start.substring(0, 16) + '</td>';
                                html += '<td style="padding:8px;">' + dfEscdfEsc(s.instructor_name || '—') + '</td>';
                                html += '<td style="padding:8px;"><code>' + dfEsc(s.plate_number || '—') + '</code></td>';
                                html += '<td style="padding:8px;"><span class="df-badge df-badge-' + s.status + '">' + s.status.toUpperCase() + '</span></td>';
                                html += '</tr>';
                            });
                            html += '</tbody></table>';
                        }

                        // Ledger History
                        if (p.ledger && p.ledger.length) {
                            html += '<h4 style="margin:0 0 10px 0;">Package Credit Transactions & Top-Ups</h4>';
                            html += '<table style="width:100%;font-size:12px;border-collapse:collapse;border:1px solid #e2e8f0;">';
                            html += '<thead style="background:#f1f5f9;"><tr><th style="padding:6px;text-align:left;">Date</th><th style="padding:6px;text-align:left;">Action</th><th style="padding:6px;text-align:left;">Delta</th><th style="padding:6px;text-align:left;">Notes</th></tr></thead><tbody>';
                            p.ledger.forEach(function (l) {
                                var isPos = parseInt(l.credits_delta, 10) > 0;
                                html += '<tr style="border-bottom:1px solid #f1f5f9;">';
                                html += '<td style="padding:6px;color:#64748b;">' + l.created_at.substring(0, 10) + '</td>';
                                html += '<td style="padding:6px;">' + l.action_type.replace('_', ' ') + '</td>';
                                html += '<td style="padding:6px;font-weight:bold;color:' + (isPos ? '#16a34a' : '#ef4444') + ';">' + (isPos ? '+' : '') + l.credits_delta + '</td>';
                                html += '<td style="padding:6px;color:#64748b;">' + (l.notes || '—') + '</td>';
                                html += '</tr>';
                            });
                            html += '</tbody></table>';
                        }

                        $body.html(html);
                    } else {
                        $body.html('<div style="color:#ef4444;padding:20px;">Could not load student profile.</div>');
                    }
                },
                error: function () {
                    $body.html('<div style="color:#ef4444;padding:20px;">Server error while loading profile.</div>');
                }
            });
        });

        /* ============================================================
           Magic Link Actions (Copy to Clipboard & One-Click Email)
           ============================================================ */
        function fallbackCopy(text) {
            var $temp = $('<input>');
            $('body').append($temp);
            $temp.val(text).select();
            document.execCommand('copy');
            $temp.remove();
            showToast('Magic booking link copied to clipboard! Send via WhatsApp or SMS.');
        }

        $(document).on('click', '.df-copy-link-btn', function (e) {
            e.preventDefault();
            var link = $(this).data('link');
            if (!link) return;
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(link).then(function () {
                    showToast('Magic booking link copied to clipboard! Send via WhatsApp or SMS.');
                }, function () {
                    fallbackCopy(link);
                });
            } else {
                fallbackCopy(link);
            }
        });

        $(document).on('click', '.df-send-link-btn', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var studentId = $btn.data('student-id');
            var email = $btn.data('student-email');
            var nonce = window.DriveFlowAdmin ? window.DriveFlowAdmin.nonce : '';

            if (!confirm('Email private booking link directly to ' + email + '?')) return;

            $btn.prop('disabled', true).text('Sending...');
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'driveflow_send_booking_link',
                    student_id: studentId,
                    nonce: nonce
                },
                success: function (res) {
                    $btn.prop('disabled', false).text('✉️ Email Link');
                    if (res && res.success) {
                        showToast((res.data && res.data.message) || 'Booking link dispatched!');
                    } else {
                        showToast((res && res.data) || 'Failed to send email.', true);
                    }
                },
                error: function () {
                    $btn.prop('disabled', false).text('✉️ Email Link');
                    showToast('Server error while sending email.', true);
                }
            });
        });

        /* ============================================================
           TV Monitor Remote Commander (Admin Hub Station)
           ============================================================ */
        var $tvCommander = $('.df-hub-tv-commander');
        if ($tvCommander.length) {
            var getNonce = function () {
                return (window.DriveFlowAdmin && window.DriveFlowAdmin.nonce) ? window.DriveFlowAdmin.nonce : '';
            };

            // 1. Remote View Mode Switcher
            $('input[name="df_tv_view_mode"]').on('change', function () {
                var selectedMode = $(this).val();
                var $radioGroup = $('#df-tv-mode-radios label');
                $radioGroup.css('borderColor', 'rgba(255,255,255,0.1)');
                $(this).closest('label').css('borderColor', '#0284c7');

                showToast('Switching TV display mode to ' + selectedMode + '...');
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'driveflow_admin_control_tv',
                        sub_action: 'set_view_mode',
                        view_mode: selectedMode,
                        nonce: getNonce()
                    },
                    success: function (res) {
                        if (res && res.success) {
                            showToast(res.data.message || 'TV display mode updated!');
                        } else {
                            showToast((res && res.data && res.data.message) || 'Failed to update TV mode.', true);
                        }
                    },
                    error: function () {
                        showToast('Error communicating with server.', true);
                    }
                });
            });

            // 2. Broadcast Alert to TV
            $('#df-tv-push-alert-btn').on('click', function () {
                var alertText = $('#df-tv-alert-input').val().trim();
                var alertLevel = $('#df-tv-alert-level').val() || 'warning';

                if (!alertText) {
                    showToast('Please type an announcement or alert message first.', true);
                    return;
                }

                showToast('Transmitting urgent broadcast alert to TV...');
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'driveflow_admin_control_tv',
                        sub_action: 'push_alert',
                        alert_text: alertText,
                        alert_level: alertLevel,
                        nonce: getNonce()
                    },
                    success: function (res) {
                        if (res && res.success) {
                            showToast(res.data.message || 'Alert successfully broadcasted to live TV screens!');
                            $('#df-tv-active-alert-status').html('<span style="color:#fbbf24;">● Currently Broadcasting:</span> <em>"' + $('<div>').text(alertText).html().substring(0, 50) + '..."</em>');
                        } else {
                            showToast((res && res.data && res.data.message) || 'Failed to broadcast alert.', true);
                        }
                    },
                    error: function () {
                        showToast('Error sending broadcast alert.', true);
                    }
                });
            });

            // 3. Clear Broadcast Alert
            $('#df-tv-clear-alert-btn').on('click', function () {
                showToast('Clearing broadcast alert from TV screens...');
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'driveflow_admin_control_tv',
                        sub_action: 'clear_alert',
                        nonce: getNonce()
                    },
                    success: function (res) {
                        if (res && res.success) {
                            $('#df-tv-alert-input').val('');
                            $('#df-tv-active-alert-status').text('No active broadcast banner currently displayed.').css('color', '#64748b');
                            showToast(res.data.message || 'Broadcast alert cleared.');
                        } else {
                            showToast((res && res.data && res.data.message) || 'Failed to clear alert.', true);
                        }
                    },
                    error: function () {
                        showToast('Error clearing broadcast alert.', true);
                    }
                });
            });

            // 4. Force Reload TV Screens
            $('#df-remote-force-reload-btn').on('click', function () {
                var $btn = $(this);
                $btn.prop('disabled', true);
                showToast('Sending force reload signal to all TV screens...');
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'driveflow_admin_control_tv',
                        sub_action: 'reload_tv',
                        nonce: getNonce()
                    },
                    success: function (res) {
                        $btn.prop('disabled', false);
                        if (res && res.success) {
                            showToast(res.data.message || 'Force reload signal transmitted successfully!');
                        } else {
                            showToast((res && res.data && res.data.message) || 'Failed to trigger reload.', true);
                        }
                    },
                    error: function () {
                        $btn.prop('disabled', false);
                        showToast('Error communicating with server.', true);
                    }
                });
            });

            // 5. Save Multi-Message Ticker Headlines
            $('#df-tv-save-ticker-btn').on('click', function () {
                var tickerText = $('#df-tv-ticker-input').val().trim();
                showToast('Saving announcement ticker headlines...');
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'driveflow_admin_control_tv',
                        sub_action: 'save_ticker_items',
                        ticker_text: tickerText,
                        nonce: getNonce()
                    },
                    success: function (res) {
                        if (res && res.success) {
                            showToast(res.data.message || 'Ticker headlines saved successfully!');
                        } else {
                            showToast((res && res.data && res.data.message) || 'Failed to save ticker headlines.', true);
                        }
                    },
                    error: function () {
                        showToast('Error saving ticker headlines.', true);
                    }
                });
            });
        }

        /* ============================================================
           Visual Drag-and-Drop Calendar Engine (Multi-Perspective)
           ============================================================ */
        var $calBoard = $('#df-calendar-board');
        if ($calBoard.length) {
            var now = new Date();
            // Start of current week (Monday)
            var currentMonday = new Date(now);
            var dayOffset = (now.getDay() + 6) % 7;
            currentMonday.setDate(now.getDate() - dayOffset);
            currentMonday.setHours(0, 0, 0, 0);

            var currentDayDate = new Date(now);
            currentDayDate.setHours(0, 0, 0, 0);

            var calPerspective = 'slots'; // 'slots' (Week), 'vehicles' (Day), 'instructors' (Day)
            var cachedCalendarData = { sessions: [], blocked: [], vehicles: [], instructors: [] };

            var timeSlots = [
                { start: '08:00', end: '10:00', label: '08:00 - 10:00 AM' },
                { start: '10:00', end: '12:00', label: '10:00 - 12:00 PM' },
                { start: '12:00', end: '14:00', label: '12:00 - 02:00 PM' },
                { start: '14:00', end: '16:00', label: '02:00 - 04:00 PM' },
                { start: '16:00', end: '18:00', label: '04:00 - 06:00 PM' },
                { start: '18:00', end: '20:00', label: '06:00 - 08:00 PM' }
            ];

            function pad(n) { return n < 10 ? '0' + n : n; }

            function formatDate(d) {
                return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
            }

            function getWeekDays(monday) {
                var days = [];
                for (var i = 0; i < 7; i++) {
                    var d = new Date(monday);
                    d.setDate(monday.getDate() + i);
                    days.push(d);
                }
                return days;
            }

            // Detect and display regional timezone in the time gutter header
            function detectAndRenderTimezone() {
                try {
                    var tz = Intl.DateTimeFormat().resolvedOptions().timeZone || 'America/New_York';
                    var offset = - (new Date().getTimezoneOffset() / 60);
                    var gmtStr = 'GMT' + (offset >= 0 ? '+' : '') + offset;
                    var shortTz = tz.split('/').pop().replace(/_/g, ' ');
                    $('#df-tz-name').text('Operating Timezone: ' + shortTz + ' (' + gmtStr + ')');
                    $('.df-cal-time-header').html('TIME (2H)<br><span style="font-size:9px;color:#0284c7;font-weight:700;">' + gmtStr + '</span>');
                } catch (e) {
                    $('#df-tz-name').text('Operating Timezone: Eastern Time (EDT / GMT-4)');
                }
            }

            // Google Calendar Live Current Time Red Marker Line
            function renderGoogleNowIndicator() {
                $('.df-cal-now-line').remove();
                var cur = new Date();
                var todayDateStr = formatDate(cur);
                var curMin = cur.getHours() * 60 + cur.getMinutes();
                var startMin = 8 * 60;  // 08:00
                var endMin = 20 * 60;   // 20:00

                if (curMin < startMin || curMin > endMin) return;

                var timeStr = cur.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                var slotIdx = Math.floor((curMin - startMin) / 120); // 0 to 5
                if (slotIdx < 0 || slotIdx >= 6) return;

                var slotStartMin = startMin + slotIdx * 120;
                var pctInSlot = ((curMin - slotStartMin) / 120) * 100;

                if (calPerspective === 'slots') {
                    // Place red indicator inside today's slot
                    var $todaySlots = $('.df-cal-slot[data-date="' + todayDateStr + '"]');
                    if ($todaySlots.length) {
                        var $slot = $todaySlots.eq(slotIdx);
                        if ($slot.length) {
                            var lineHtml = '<div class="df-cal-now-line" style="top:' + pctInSlot.toFixed(1) + '%;">';
                            lineHtml += '<span class="df-cal-now-dot"></span>';
                            lineHtml += '<span class="df-cal-now-badge">' + timeStr + '</span>';
                            lineHtml += '</div>';
                            $slot.append(lineHtml);
                        }
                    }
                } else {
                    // Day view (Vehicles or Instructors)
                    if (formatDate(currentDayDate) === todayDateStr) {
                        var slotStart = timeSlots[slotIdx].start;
                        var $slotsInRow = $('.df-cal-slot[data-time-start="' + slotStart + '"]');
                        if ($slotsInRow.length) {
                            $slotsInRow.each(function (idx) {
                                var lineHtml = '<div class="df-cal-now-line" style="top:' + pctInSlot.toFixed(1) + '%;">';
                                if (idx === 0) {
                                    lineHtml += '<span class="df-cal-now-dot"></span>';
                                }
                                if (idx === $slotsInRow.length - 1) {
                                    lineHtml += '<span class="df-cal-now-badge">' + timeStr + '</span>';
                                }
                                lineHtml += '</div>';
                                $(this).append(lineHtml);
                            });
                        }
                    }
                }
            }

            // Render calendar grid according to selected perspective
            function renderCalendar() {
                var $grid = $('#df-calendar-grid');
                $grid.empty();

                var monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                var dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                var todayStr = formatDate(new Date());

                if (calPerspective === 'slots') {
                    // ── PERSPECTIVE 1: Standard 2-Hour Slots (Week View) ──
                    $grid.css('grid-template-columns', '100px repeat(7, minmax(160px, 1fr))');
                    var days = getWeekDays(currentMonday);
                    var startDateStr = formatDate(days[0]);
                    var endDateStr = formatDate(days[6]);

                    var rangeText = monthNames[days[0].getMonth()] + ' ' + days[0].getDate() + ' – ' + monthNames[days[6].getMonth()] + ' ' + days[6].getDate() + ', ' + days[6].getFullYear();
                    $('#df-cal-range-label').text(rangeText);

                    // Nav button labels for Week
                    $('#df-cal-prev-btn').html('&larr; Previous Week');
                    $('#df-cal-today-btn').text('Current Week');
                    $('#df-cal-next-btn').html('Next Week &rarr;');

                    // Header Row
                    $grid.append('<div class="df-cal-col-header df-cal-time-header">TIME (2H)</div>');
                    days.forEach(function (day) {
                        var dateStr = formatDate(day);
                        var isToday = (dateStr === todayStr);
                        var headerHtml = '<div class="df-cal-col-header ' + (isToday ? 'is-today' : '') + '">';
                        headerHtml += '<div style="font-size:11px;color:' + (isToday ? '#2563eb' : '#64748b') + ';">' + dayNames[day.getDay()] + '</div>';
                        headerHtml += '<div style="font-size:15px;">' + monthNames[day.getMonth()] + ' ' + day.getDate() + '</div>';
                        headerHtml += '</div>';
                        $grid.append(headerHtml);
                    });

                    // Slot Rows
                    timeSlots.forEach(function (slot) {
                        var timeCell = '<div class="df-cal-time-cell">';
                        timeCell += '<span>' + slot.start + '</span>';
                        timeCell += '<span class="df-cal-time-duration">2 Hours</span>';
                        timeCell += '<span>' + slot.end + '</span>';
                        timeCell += '</div>';
                        $grid.append(timeCell);

                        days.forEach(function (day) {
                            var dateStr = formatDate(day);
                            var slotCell = '<div class="df-cal-slot" data-date="' + dateStr + '" data-time-start="' + slot.start + '" data-time-end="' + slot.end + '"></div>';
                            $grid.append(slotCell);
                        });
                    });

                    fetchCalendarEvents(startDateStr, endDateStr);

                } else if (calPerspective === 'vehicles') {
                    // ── PERSPECTIVE 2: By Fleet Vehicles (Day View) ──
                    var dateStr = formatDate(currentDayDate);
                    var rangeText = dayNames[currentDayDate.getDay()] + ', ' + monthNames[currentDayDate.getMonth()] + ' ' + currentDayDate.getDate() + ', ' + currentDayDate.getFullYear() + ' (Fleet Vehicles View)';
                    $('#df-cal-range-label').text(rangeText);

                    // Nav button labels for Day
                    $('#df-cal-prev-btn').html('&larr; Previous Day');
                    $('#df-cal-today-btn').text('Today');
                    $('#df-cal-next-btn').html('Next Day &rarr;');

                    var vList = cachedCalendarData.vehicles || [];
                    if (!vList.length) {
                        vList = [
                            { plate_number: '8WD 4931', model: 'Honda Civic (Automatic)' },
                            { plate_number: 'BAY-7892', model: 'Toyota Corolla (Automatic)' },
                            { plate_number: 'MD-CRAB-01', model: 'Honda Civic (Manual)' }
                        ];
                    }

                    $grid.css('grid-template-columns', '100px repeat(' + vList.length + ', minmax(200px, 1fr))');

                    // Header Row: Time gutter + Vehicle columns
                    $grid.append('<div class="df-cal-col-header df-cal-time-header">TIME (2H)</div>');
                    vList.forEach(function (veh) {
                        var plate = (veh.plate_number || '8WD 4931').toUpperCase();
                        var headerHtml = '<div class="df-cal-col-header df-cal-entity-header">';
                        headerHtml += '<div class="maryland-plate plate-sm" style="transform:scale(0.85);margin-bottom:2px;"><span class="plate-number">' + plate + '</span></div>';
                        headerHtml += '<div class="df-cal-entity-sub">' + (veh.model || 'Dual-Control Fleet') + '</div>';
                        headerHtml += '</div>';
                        $grid.append(headerHtml);
                    });

                    // Slot Rows
                    timeSlots.forEach(function (slot) {
                        var timeCell = '<div class="df-cal-time-cell">';
                        timeCell += '<span>' + slot.start + '</span>';
                        timeCell += '<span class="df-cal-time-duration">2 Hours</span>';
                        timeCell += '<span>' + slot.end + '</span>';
                        timeCell += '</div>';
                        $grid.append(timeCell);

                        vList.forEach(function (veh) {
                            var plate = (veh.plate_number || '').toUpperCase();
                            var slotCell = '<div class="df-cal-slot" data-date="' + dateStr + '" data-time-start="' + slot.start + '" data-time-end="' + slot.end + '" data-plate="' + plate + '"></div>';
                            $grid.append(slotCell);
                        });
                    });

                    fetchCalendarEvents(dateStr, dateStr);

                } else if (calPerspective === 'instructors') {
                    // ── PERSPECTIVE 3: By Certified Instructors (Day View) ──
                    var dateStr = formatDate(currentDayDate);
                    var rangeText = dayNames[currentDayDate.getDay()] + ', ' + monthNames[currentDayDate.getMonth()] + ' ' + currentDayDate.getDate() + ', ' + currentDayDate.getFullYear() + ' (Instructors View)';
                    $('#df-cal-range-label').text(rangeText);

                    $('#df-cal-prev-btn').html('&larr; Previous Day');
                    $('#df-cal-today-btn').text('Today');
                    $('#df-cal-next-btn').html('Next Day &rarr;');

                    var insList = cachedCalendarData.instructors || [];
                    if (!insList.length) {
                        insList = [
                            { name: 'Mr. Anderson' },
                            { name: 'Ms. Rivera' },
                            { name: 'Mr. Thompson' }
                        ];
                    }

                    $grid.css('grid-template-columns', '100px repeat(' + insList.length + ', minmax(200px, 1fr))');

                    // Header Row: Time gutter + Instructor columns
                    $grid.append('<div class="df-cal-col-header df-cal-time-header">TIME (2H)</div>');
                    insList.forEach(function (ins) {
                        var headerHtml = '<div class="df-cal-col-header df-cal-entity-header">';
                        headerHtml += '<div class="df-cal-entity-title">👨‍🏫 ' + (ins.name || 'Instructor') + '</div>';
                        headerHtml += '<div class="df-cal-entity-sub">' + (ins.license_number ? 'MVA #' + ins.license_number : 'Certified Instructor') + '</div>';
                        headerHtml += '</div>';
                        $grid.append(headerHtml);
                    });

                    // Slot Rows
                    timeSlots.forEach(function (slot) {
                        var timeCell = '<div class="df-cal-time-cell">';
                        timeCell += '<span>' + slot.start + '</span>';
                        timeCell += '<span class="df-cal-time-duration">2 Hours</span>';
                        timeCell += '<span>' + slot.end + '</span>';
                        timeCell += '</div>';
                        $grid.append(timeCell);

                        insList.forEach(function (ins) {
                            var insName = ins.name || '';
                            var slotCell = '<div class="df-cal-slot" data-date="' + dateStr + '" data-time-start="' + slot.start + '" data-time-end="' + slot.end + '" data-instructor="' + insName + '"></div>';
                            $grid.append(slotCell);
                        });
                    });

                    fetchCalendarEvents(dateStr, dateStr);
                }

                detectAndRenderTimezone();
            }

            function fetchCalendarEvents(startDateStr, endDateStr) {
                var $loading = $('#df-cal-loading');
                $loading.fadeIn(120);

                var instructor = $('#df-cal-instructor-filter').val();
                var plate = $('#df-cal-plate-filter').val();
                var nonce = window.DriveFlowAdmin ? window.DriveFlowAdmin.nonce : '';

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'driveflow_calendar_events',
                        start_date: startDateStr,
                        end_date: endDateStr,
                        instructor: instructor,
                        plate: plate,
                        nonce: nonce
                    },
                    success: function (res) {
                        $loading.fadeOut(150);
                        if (res && res.success && res.data) {
                            cachedCalendarData.sessions = res.data.sessions || [];
                            cachedCalendarData.blocked = res.data.blocked || [];
                            if (res.data.vehicles && res.data.vehicles.length) {
                                cachedCalendarData.vehicles = res.data.vehicles;
                            }
                            if (res.data.instructors && res.data.instructors.length) {
                                cachedCalendarData.instructors = res.data.instructors;
                            }
                            populateEvents(cachedCalendarData.sessions, cachedCalendarData.blocked);
                            renderGoogleNowIndicator();
                        } else {
                            showToast('Failed to load schedule events.', true);
                        }
                    },
                    error: function () {
                        $loading.fadeOut(150);
                        showToast('Error communicating with calendar API.', true);
                    }
                });
            }

            function populateEvents(sessions, blockedSlots) {
                $('.df-cal-slot').empty();

                // 1. Render Blocked Slots
                if (blockedSlots && blockedSlots.length) {
                    blockedSlots.forEach(function (blk) {
                        var bStart = blk.start_time.substring(0, 10);
                        var bStartTime = blk.start_time.substring(11, 16);
                        var bEndTime = blk.end_time.substring(11, 16);

                        $('.df-cal-slot[data-date="' + bStart + '"]').each(function () {
                            var slotStart = $(this).data('time-start');
                            var slotEnd = $(this).data('time-end');
                            var slotPlate = $(this).data('plate');
                            var slotIns = $(this).data('instructor');

                            if (slotPlate && blk.target_type === 'vehicle' && blk.target_identifier !== slotPlate) return;
                            if (slotIns && blk.target_type === 'instructor' && blk.target_identifier !== slotIns) return;

                            if (slotStart < bEndTime && slotEnd > bStartTime) {
                                var label = 'Academy Blocked';
                                if (blk.target_type === 'instructor') label = 'Instructor Off: ' + blk.target_identifier;
                                else if (blk.target_type === 'vehicle') label = 'Vehicle Out: ' + blk.target_identifier;

                                var card = '<div class="df-blocked-card" title="' + (blk.reason || 'Restricted Time Window') + '">';
                                card += '<button type="button" class="df-unblock-btn" data-slot-id="' + blk.id + '" title="Unblock this slot">&times;</button>';
                                card += '<span class="df-blocked-badge">BLOCKED</span>';
                                card += '<div class="df-blocked-reason">' + label + '</div>';
                                if (blk.reason) card += '<div style="font-size:10px;color:#881337;margin-top:2px;">' + blk.reason + '</div>';
                                card += '</div>';
                                $(this).append(card);
                            }
                        });
                    });
                }

                // 2. Render Scheduled Sessions
                if (sessions && sessions.length) {
                    sessions.forEach(function (s) {
                        var dateStr = s.scheduled_start.substring(0, 10);
                        var startTimeStr = s.scheduled_start.substring(11, 16);
                        var sPlate = (s.plate_number || '').toUpperCase();
                        var sIns = s.instructor_name || '';

                        var $targetCell = $();

                        if (calPerspective === 'slots') {
                            $targetCell = $('.df-cal-slot[data-date="' + dateStr + '"][data-time-start="' + startTimeStr + '"]');
                            if (!$targetCell.length) {
                                var startHour = parseInt(startTimeStr.split(':')[0], 10);
                                timeSlots.forEach(function (ts) {
                                    var tsH = parseInt(ts.start.split(':')[0], 10);
                                    if (startHour >= tsH && startHour < tsH + 2) {
                                        $targetCell = $('.df-cal-slot[data-date="' + dateStr + '"][data-time-start="' + ts.start + '"]');
                                    }
                                });
                            }
                        } else if (calPerspective === 'vehicles') {
                            $targetCell = $('.df-cal-slot[data-date="' + dateStr + '"][data-time-start="' + startTimeStr + '"][data-plate="' + sPlate + '"]');
                            if (!$targetCell.length) {
                                var startHour = parseInt(startTimeStr.split(':')[0], 10);
                                timeSlots.forEach(function (ts) {
                                    var tsH = parseInt(ts.start.split(':')[0], 10);
                                    if (startHour >= tsH && startHour < tsH + 2) {
                                        $targetCell = $('.df-cal-slot[data-date="' + dateStr + '"][data-time-start="' + ts.start + '"][data-plate="' + sPlate + '"]');
                                    }
                                });
                            }
                        } else if (calPerspective === 'instructors') {
                            $targetCell = $('.df-cal-slot[data-date="' + dateStr + '"][data-time-start="' + startTimeStr + '"][data-instructor="' + sIns + '"]');
                            if (!$targetCell.length) {
                                var startHour = parseInt(startTimeStr.split(':')[0], 10);
                                timeSlots.forEach(function (ts) {
                                    var tsH = parseInt(ts.start.split(':')[0], 10);
                                    if (startHour >= tsH && startHour < tsH + 2) {
                                        $targetCell = $('.df-cal-slot[data-date="' + dateStr + '"][data-time-start="' + ts.start + '"][data-instructor="' + sIns + '"]');
                                    }
                                });
                            }
                        }

                        if ($targetCell.length) {
                            var card = $('<div class="df-event-card status-' + s.status + '" draggable="true"></div>');
                            card.attr('data-session-id', s.id);
                            card.attr('data-student', s.student_name);
                            card.attr('data-instructor', s.instructor_name);
                            card.attr('data-plate', s.plate_number);
                            card.attr('data-start', s.scheduled_start);
                            card.attr('data-end', s.scheduled_end);

                            var html = '<div class="df-event-title">#' + s.session_number + ' ' + dfEsc(s.student_name || 'Student') + '</div>';
                            html += '<div class="df-event-instructor">👤 ' + dfEsc(s.instructor_name || 'Instructor') + '</div>';
                            html += '<div class="df-event-footer">';
                            html += '<span style="color:#64748b;font-size:11px;">' + startTimeStr + '</span>';
                            if (s.plate_number) {
                                html += '<div class="maryland-plate plate-sm" style="transform:scale(0.82);transform-origin:right center;" title="Maryland Plate: ' + dfEsc(s.plate_number) + '"><span class="plate-number">' + dfEsc(s.plate_number) + '</span></div>';
                            }
                            html += '</div>';

                            card.html(html);
                            $targetCell.append(card);
                        }
                    });
                }

                // 3. For Day view, render open booking buttons in empty cells
                if (calPerspective !== 'slots') {
                    $('.df-cal-slot').each(function () {
                        if (!$(this).children('.df-event-card, .df-blocked-card').length) {
                            var btn = $('<button type="button" class="df-cal-empty-slot-btn">+ Book 2h Slot</button>');
                            $(this).append(btn);
                        }
                    });
                }

                bindDragAndDrop();
                renderGoogleNowIndicator();
            }

            function bindDragAndDrop() {
                var draggedSessionId = null;
                var $draggedCard = null;

                $('.df-event-card').on('dragstart', function (e) {
                    $draggedCard = $(this);
                    draggedSessionId = $draggedCard.data('session-id');
                    $draggedCard.addClass('is-dragging');
                    e.originalEvent.dataTransfer.setData('text/plain', draggedSessionId);
                    e.originalEvent.dataTransfer.effectAllowed = 'move';
                });

                $('.df-event-card').on('dragend', function () {
                    if ($draggedCard) $draggedCard.removeClass('is-dragging');
                    $('.df-cal-slot').removeClass('is-dragover');
                });

                $('.df-cal-slot').on('dragover', function (e) {
                    e.preventDefault();
                    e.originalEvent.dataTransfer.dropEffect = 'move';
                    $(this).addClass('is-dragover');
                });

                $('.df-cal-slot').on('dragleave', function () {
                    $(this).removeClass('is-dragover');
                });

                $('.df-cal-slot').on('drop', function (e) {
                    e.preventDefault();
                    $(this).removeClass('is-dragover');
                    var $slot = $(this);
                    var newDate = $slot.data('date');
                    var newTimeStart = $slot.data('time-start');
                    var newTimeEnd = $slot.data('time-end');

                    if (!draggedSessionId || !newDate || !newTimeStart) return;

                    var newStart = newDate + ' ' + newTimeStart + ':00';
                    var newEnd = newDate + ' ' + newTimeEnd + ':00';
                    var nonce = window.DriveFlowAdmin ? window.DriveFlowAdmin.nonce : '';

                    showToast('Rescheduling session #' + draggedSessionId + '...');

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'driveflow_reschedule_session',
                            session_id: draggedSessionId,
                            new_start: newStart,
                            new_end: newEnd,
                            nonce: nonce
                        },
                        success: function (res) {
                            if (res && res.success) {
                                showToast(res.data.message || 'Session rescheduled successfully!');
                                if (typeof window.reloadCalendar === 'function') {
                                    window.reloadCalendar();
                                } else {
                                    $slot.children('.df-cal-empty-slot-btn').remove();
                                    $slot.append($draggedCard);
                                }
                            } else {
                                var msg = (res && res.data && res.data.message) || 'Collision detected! Cannot reschedule into this time slot.';
                                showToast(msg, true);
                                if (typeof window.reloadCalendar === 'function') {
                                    window.reloadCalendar();
                                }
                            }
                        },
                        error: function () {
                            showToast('Server error while rescheduling session.', true);
                            if (typeof window.reloadCalendar === 'function') {
                                window.reloadCalendar();
                            }
                        }
                    });
                });

                // ---- Drag a student / instructor / vehicle chip onto a session card to assign it (mouse + touch) ----
                var draggedChip = null;

                function chipOf($el) {
                    return { type: $el.data('type'), id: $el.data('id'), name: String($el.data('name') || '') };
                }

                function assignToSession(sessionId, chip) {
                    if (!sessionId || !chip || !chip.id) return;
                    var nonce = window.DriveFlowAdmin ? window.DriveFlowAdmin.nonce : '';
                    showToast('Assigning ' + chip.name + '...');
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: { action: 'driveflow_assign_to_session', session_id: sessionId, assign_type: chip.type, entity_id: chip.id, nonce: nonce },
                        success: function (res) {
                            if (res && res.success) {
                                showToast(res.data.message || 'Assigned.');
                            } else {
                                showToast((res && res.data && res.data.message) || 'Could not assign.', true);
                            }
                            if (typeof window.reloadCalendar === 'function') window.reloadCalendar();
                        },
                        error: function () {
                            showToast('Server error while assigning.', true);
                        }
                    });
                }

                $('.df-chip').off('.dfassign')
                    .on('dragstart.dfassign', function (e) {
                        draggedSessionId = null; // a chip drag must never be treated as a session move
                        draggedChip = chipOf($(this));
                        e.originalEvent.dataTransfer.setData('text/plain', 'df-chip');
                        e.originalEvent.dataTransfer.effectAllowed = 'copy';
                    })
                    .on('dragend.dfassign', function () {
                        draggedChip = null;
                        $('.df-event-card').removeClass('is-assign-over');
                    });

                $('.df-event-card')
                    .on('dragover', function (e) {
                        if (!draggedChip) return;
                        e.preventDefault();
                        e.stopPropagation();
                        e.originalEvent.dataTransfer.dropEffect = 'copy';
                        $(this).addClass('is-assign-over');
                    })
                    .on('dragleave', function () { $(this).removeClass('is-assign-over'); })
                    .on('drop', function (e) {
                        if (!draggedChip) return;
                        e.preventDefault();
                        e.stopPropagation(); // do not let the slot treat this as a reschedule
                        $(this).removeClass('is-assign-over');
                        var chip = draggedChip;
                        draggedChip = null;
                        assignToSession($(this).data('session-id'), chip);
                    });

                $('#df-chip-search').off('.dfassign').on('input.dfassign', function () {
                    var q = String($(this).val() || '').toLowerCase();
                    $('.df-chip').each(function () {
                        $(this).toggle(!q || String($(this).data('name')).toLowerCase().indexOf(q) !== -1);
                    });
                });

                // Touch / pen for chips: long-press a chip, drag onto a card, release. (bound once per chip element)
                $('.df-chip').each(function () {
                    if (this._dfTouch) return;
                    this._dfTouch = true;
                    var chipEl = this, timer = null, active = false, ghost = null, $over = $(), sx = 0, sy = 0;

                    function cardAt(x, y) {
                        var el = document.elementFromPoint(x, y);
                        return el ? $(el).closest('.df-event-card') : $();
                    }
                    function cleanup() {
                        clearTimeout(timer);
                        timer = null;
                        active = false;
                        if (ghost && ghost.parentNode) ghost.parentNode.removeChild(ghost);
                        ghost = null;
                        $over.removeClass('is-assign-over');
                        $over = $();
                    }

                    chipEl.addEventListener('touchstart', function (ev) {
                        if (ev.touches.length !== 1) return;
                        sx = ev.touches[0].clientX;
                        sy = ev.touches[0].clientY;
                        timer = setTimeout(function () {
                            active = true;
                            var r = chipEl.getBoundingClientRect();
                            ghost = chipEl.cloneNode(true);
                            ghost.style.cssText = 'position:fixed;z-index:100000;pointer-events:none;opacity:.9;box-shadow:0 8px 24px rgba(0,0,0,.35);left:' + r.left + 'px;top:' + r.top + 'px;';
                            document.body.appendChild(ghost);
                            if (navigator.vibrate) navigator.vibrate(15);
                        }, 250);
                    }, { passive: true });

                    chipEl.addEventListener('touchmove', function (ev) {
                        var t = ev.touches[0];
                        if (!active) {
                            if (Math.abs(t.clientX - sx) > 8 || Math.abs(t.clientY - sy) > 8) cleanup();
                            return;
                        }
                        ev.preventDefault();
                        if (ghost) {
                            ghost.style.left = (t.clientX - ghost.offsetWidth / 2) + 'px';
                            ghost.style.top = (t.clientY - 20) + 'px';
                        }
                        var $card = cardAt(t.clientX, t.clientY);
                        if (!$card.is($over)) {
                            $over.removeClass('is-assign-over');
                            $over = $card.addClass('is-assign-over');
                        }
                    }, { passive: false });

                    chipEl.addEventListener('touchend', function (ev) {
                        var wasActive = active;
                        var t = ev.changedTouches[0];
                        var $card = wasActive ? cardAt(t.clientX, t.clientY) : $();
                        cleanup();
                        if (wasActive) {
                            ev.preventDefault();
                            if ($card.length) assignToSession($card.data('session-id'), chipOf($(chipEl)));
                        }
                    }, { passive: false });

                    chipEl.addEventListener('touchcancel', cleanup, { passive: true });
                });

                // Touch / pen support: HTML5 drag & drop does not fire reliably on touch screens.
                // Long-press a session card (~250ms), drag it onto a slot, release to reschedule (reuses the drop handler above).
                $('.df-event-card').each(function () {
                    var card = this, timer = null, active = false, ghost = null, $over = $(), sx = 0, sy = 0;

                    function slotAt(x, y) {
                        var el = document.elementFromPoint(x, y);
                        return el ? $(el).closest('.df-cal-slot') : $();
                    }
                    function cleanup() {
                        clearTimeout(timer);
                        timer = null;
                        active = false;
                        if (ghost && ghost.parentNode) ghost.parentNode.removeChild(ghost);
                        ghost = null;
                        $over.removeClass('is-dragover');
                        $over = $();
                        $(card).removeClass('is-dragging');
                    }

                    card.addEventListener('touchstart', function (ev) {
                        if (ev.touches.length !== 1) return;
                        sx = ev.touches[0].clientX;
                        sy = ev.touches[0].clientY;
                        timer = setTimeout(function () {
                            active = true;
                            $draggedCard = $(card);
                            draggedSessionId = $draggedCard.data('session-id');
                            $draggedCard.addClass('is-dragging');
                            var r = card.getBoundingClientRect();
                            ghost = card.cloneNode(true);
                            ghost.removeAttribute('draggable');
                            ghost.style.cssText = 'position:fixed;z-index:100000;pointer-events:none;opacity:.9;box-shadow:0 8px 24px rgba(0,0,0,.35);' +
                                'width:' + r.width + 'px;left:' + r.left + 'px;top:' + r.top + 'px;';
                            document.body.appendChild(ghost);
                            if (navigator.vibrate) navigator.vibrate(15);
                        }, 250);
                    }, { passive: true });

                    card.addEventListener('touchmove', function (ev) {
                        var t = ev.touches[0];
                        if (!active) {
                            // finger moved before the long-press fired: the user is scrolling, not dragging
                            if (Math.abs(t.clientX - sx) > 8 || Math.abs(t.clientY - sy) > 8) cleanup();
                            return;
                        }
                        ev.preventDefault();
                        if (ghost) {
                            ghost.style.left = (t.clientX - ghost.offsetWidth / 2) + 'px';
                            ghost.style.top = (t.clientY - 20) + 'px';
                        }
                        var $slot = slotAt(t.clientX, t.clientY);
                        if (!$slot.is($over)) {
                            $over.removeClass('is-dragover');
                            $over = $slot.addClass('is-dragover');
                        }
                        // auto-scroll the calendar horizontally near its edges
                        var board = $(card).closest('.df-calendar-board')[0];
                        if (board) {
                            var br = board.getBoundingClientRect();
                            if (t.clientX > br.right - 40) board.scrollLeft += 14;
                            else if (t.clientX < br.left + 40) board.scrollLeft -= 14;
                        }
                    }, { passive: false });

                    card.addEventListener('touchend', function (ev) {
                        var wasActive = active;
                        var t = ev.changedTouches[0];
                        var $slot = wasActive ? slotAt(t.clientX, t.clientY) : $();
                        cleanup();
                        if (wasActive) {
                            ev.preventDefault(); // no synthetic click after a drag
                            if ($slot.length) $slot.trigger('drop');
                        }
                    }, { passive: false });

                    card.addEventListener('touchcancel', cleanup, { passive: true });
                });

                // Double click empty slot or click empty slot button to schedule lesson prefilled
                $(document).off('click', '.df-cal-empty-slot-btn').on('click', '.df-cal-empty-slot-btn', function () {
                    var $slot = $(this).closest('.df-cal-slot');
                    openBookingModalPrefilled($slot);
                });

                $('.df-cal-slot').off('dblclick').on('dblclick', function () {
                    openBookingModalPrefilled($(this));
                });

                function openBookingModalPrefilled($slot) {
                    var date = $slot.data('date');
                    var timeStart = $slot.data('time-start');
                    var timeEnd = $slot.data('time-end');
                    var plate = $slot.data('plate');
                    var ins = $slot.data('instructor');

                    if (date && timeStart) {
                        $('#df-modal-start-time').val(date + 'T' + timeStart);
                        $('#df-modal-end-time').val(date + 'T' + timeEnd);
                        if (plate) $('#df-modal-plate').val(plate);
                        if (ins) $('#df-modal-instructor').val(ins);
                        $('#df-add-session-modal').addClass('is-open');
                    }
                }
            }

            // Expose reloadCalendar globally
            window.reloadCalendar = function () {
                if (calPerspective === 'slots') {
                    var days = getWeekDays(currentMonday);
                    fetchCalendarEvents(formatDate(days[0]), formatDate(days[6]));
                } else {
                    var dStr = formatDate(currentDayDate);
                    fetchCalendarEvents(dStr, dStr);
                }
            };

            // Auto-refresh calendar every 20 seconds if on calendar view and no modal open
            setInterval(function () {
                if ($('#df-calendar-board').length && !$('.df-modal-backdrop.is-open').length) {
                    if (typeof window.reloadCalendar === 'function') {
                        window.reloadCalendar();
                    }
                }
            }, 20000);

            // Perspective Switcher Toolbar handlers
            $('#df-cal-view-toggle button').on('click', function () {
                var newMode = $(this).data('cal-view');
                if (newMode && newMode !== calPerspective) {
                    calPerspective = newMode;
                    $('#df-cal-view-toggle button').removeClass('df-btn-primary is-active').addClass('df-btn-secondary');
                    $(this).removeClass('df-btn-secondary').addClass('df-btn-primary is-active');
                    renderCalendar();
                }
            });

            // Navigation buttons (Week vs Day)
            $('#df-cal-prev-btn').on('click', function () {
                if (calPerspective === 'slots') {
                    currentMonday.setDate(currentMonday.getDate() - 7);
                } else {
                    currentDayDate.setDate(currentDayDate.getDate() - 1);
                }
                renderCalendar();
            });

            $('#df-cal-next-btn').on('click', function () {
                if (calPerspective === 'slots') {
                    currentMonday.setDate(currentMonday.getDate() + 7);
                } else {
                    currentDayDate.setDate(currentDayDate.getDate() + 1);
                }
                renderCalendar();
            });

            $('#df-cal-today-btn').on('click', function () {
                var d = new Date();
                var off = (d.getDay() + 6) % 7;
                currentMonday = new Date(d);
                currentMonday.setDate(d.getDate() - off);
                currentMonday.setHours(0, 0, 0, 0);

                currentDayDate = new Date(d);
                currentDayDate.setHours(0, 0, 0, 0);
                renderCalendar();
            });

            $('#df-cal-instructor-filter, #df-cal-plate-filter').on('change', function () {
                window.reloadCalendar();
            });

            $('#df-cal-refresh-btn').on('click', function () {
                window.reloadCalendar();
            });

            // Initial render
            renderCalendar();

            // Interval to keep Google live red time line updated
            window.setInterval(renderGoogleNowIndicator, 30000);
        }

        /* ============================================================
           Block Time Modal & Unblock Handlers
           ============================================================ */
        $('#df-open-block-modal-btn').on('click', function () {
            $('#df-block-modal').addClass('is-open');
        });

        $('#df-block-target-type').on('change', function () {
            var val = $(this).val();
            if (val === 'instructor') {
                $('#df-block-instructor-wrap').show();
                $('#df-block-vehicle-wrap').hide();
            } else if (val === 'vehicle') {
                $('#df-block-instructor-wrap').hide();
                $('#df-block-vehicle-wrap').show();
            } else {
                $('#df-block-instructor-wrap').hide();
                $('#df-block-vehicle-wrap').hide();
            }
        });

        $('#df-block-slot-form').on('submit', function (e) {
            e.preventDefault();
            var targetType = $('#df-block-target-type').val();
            var identifier = '';
            if (targetType === 'instructor') identifier = $('#df-block-instructor').val();
            else if (targetType === 'vehicle') identifier = $('#df-block-vehicle').val();

            var startTime = $('#df-block-start-time').val();
            var endTime = $('#df-block-end-time').val();
            var reason = $('#df-block-reason').val();
            var nonce = window.DriveFlowAdmin ? window.DriveFlowAdmin.nonce : '';
            var $btn = $('#df-save-block-btn');

            if (!startTime || !endTime) {
                showToast('Please specify valid start and end times.', true);
                return;
            }

            $btn.prop('disabled', true).text('Saving Block...');
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'driveflow_add_blocked_slot',
                    target_type: targetType,
                    target_identifier: identifier,
                    start_time: startTime.replace('T', ' ') + ':00',
                    end_time: endTime.replace('T', ' ') + ':00',
                    reason: reason,
                    nonce: nonce
                },
                success: function (res) {
                    $btn.prop('disabled', false).text('Confirm & Block Slot');
                    if (res && res.success) {
                        $('#df-block-modal').removeClass('is-open');
                        showToast('Time slot blocked successfully!');
                        if (typeof window.reloadCalendar === 'function') window.reloadCalendar();
                    } else {
                        showToast((res && res.data && res.data.message) || 'Failed to block slot.', true);
                    }
                },
                error: function () {
                    $btn.prop('disabled', false).text('Confirm & Block Slot');
                    showToast('Server error while saving blocked slot.', true);
                }
            });
        });

        // Unblock Slot
        $(document).on('click', '.df-unblock-btn', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var slotId = $(this).data('slot-id');
            var nonce = window.DriveFlowAdmin ? window.DriveFlowAdmin.nonce : '';
            if (!confirm('Unblock and open this time slot for booking?')) return;

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'driveflow_delete_blocked_slot',
                    slot_id: slotId,
                    nonce: nonce
                },
                success: function (res) {
                    if (res && res.success) {
                        showToast('Time slot unblocked successfully.');
                        if (typeof window.reloadCalendar === 'function') window.reloadCalendar();
                    } else {
                        showToast('Could not unblock slot.', true);
                    }
                }
            });
        });

        // -------------------------------------------------------------
        // Admin Manual Edit: Sessions, Students & Entities
        // -------------------------------------------------------------

        // 1. Edit Session (Populate Modal via AJAX)
        $(document).on('click', '.df-edit-session-btn', function (e) {
            e.preventDefault();
            var sessionId = $(this).data('session-id');
            var nonce = window.DriveFlowAdmin ? window.DriveFlowAdmin.nonce : '';
            showToast('Loading session details...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'driveflow_get_session_details',
                    session_id: sessionId,
                    nonce: nonce
                },
                success: function (res) {
                    if (res && res.success && res.data) {
                        var d = res.data;
                        $('#df-edit-session-id').val(d.id);
                        $('#df-edit-student-name').val(d.student_name);
                        $('#df-edit-instructor-name').val(d.instructor_name);
                        $('#df-edit-plate-number').val(d.plate_number);
                        $('#df-edit-session-number').val(d.session_number);
                        $('#df-edit-lesson-topic').val(d.lesson_topic);
                        if (d.scheduled_start) $('#df-edit-scheduled-start').val(d.scheduled_start.replace(' ', 'T').substring(0, 16));
                        if (d.scheduled_end) $('#df-edit-scheduled-end').val(d.scheduled_end.replace(' ', 'T').substring(0, 16));
                        $('#df-edit-status').val(d.status);

                        var fd = d.decoded_form_data || {};
                        $('#df-edit-final-eval').val(fd.final_evaluation || '');
                        $('#df-edit-instructor-cert').val(fd.instructor_cert_no || '');
                        $('#df-edit-instructor-notes').val(fd.instructor_notes || '');

                        $('#df-edit-session-modal').addClass('is-open');
                    } else {
                        showToast((res && res.data) || 'Failed to load session details.', true);
                    }
                },
                error: function () {
                    showToast('Server error while loading session.', true);
                }
            });
        });

        // Save Edit Session
        $('#df-edit-session-form').on('submit', function (e) {
            e.preventDefault();
            var nonce = window.DriveFlowAdmin ? window.DriveFlowAdmin.nonce : '';
            var $btn = $('#df-edit-session-submit-btn');
            $btn.prop('disabled', true).text('Saving Changes...');

            var payload = {
                action: 'driveflow_update_session',
                nonce: nonce,
                session_id: $('#df-edit-session-id').val(),
                student_name: $('#df-edit-student-name').val(),
                instructor_name: $('#df-edit-instructor-name').val(),
                plate_number: $('#df-edit-plate-number').val(),
                session_number: $('#df-edit-session-number').val(),
                lesson_topic: $('#df-edit-lesson-topic').val(),
                scheduled_start: $('#df-edit-scheduled-start').val(),
                scheduled_end: $('#df-edit-scheduled-end').val(),
                status: $('#df-edit-status').val(),
                final_evaluation: $('#df-edit-final-eval').val(),
                instructor_cert_no: $('#df-edit-instructor-cert').val(),
                instructor_notes: $('#df-edit-instructor-notes').val()
            };

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: payload,
                success: function (res) {
                    $btn.prop('disabled', false).text('Save Changes');
                    if (res && res.success) {
                        showToast((res.data && res.data.message) || 'Session updated successfully!');
                        $('#df-edit-session-modal').removeClass('is-open');
                        setTimeout(function () { location.reload(); }, 700);
                    } else {
                        showToast((res && res.data) || 'Failed to update session.', true);
                    }
                },
                error: function () {
                    $btn.prop('disabled', false).text('Save Changes');
                    showToast('Error saving session updates.', true);
                }
            });
        });

        // Delete Session Handler (Table Row Action)
        $(document).on('click', '.df-delete-session-btn', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var sessionId = $btn.data('session-id');
            var student = $btn.data('student') || 'Student';
            var nonce = window.DriveFlowAdmin ? window.DriveFlowAdmin.nonce : '';

            if (!confirm('Are you sure you want to permanently delete Session #' + sessionId + ' for ' + student + '?\n\nThis will remove the session completely from records and restore any consumed student credit.')) {
                return;
            }

            $btn.prop('disabled', true).text('⏳...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'driveflow_delete_session',
                    session_id: sessionId,
                    nonce: nonce
                },
                success: function (res) {
                    if (res && res.success) {
                        showToast((res.data && res.data.message) || 'Session permanently deleted.');
                        var $tr = $btn.closest('tr');
                        $tr.css('background', '#fee2e2').fadeOut(400, function () {
                            $(this).remove();
                        });
                    } else {
                        showToast((res && res.data) || 'Failed to delete session.', true);
                        $btn.prop('disabled', false).html('🗑️ Delete');
                    }
                },
                error: function () {
                    showToast('Server error while deleting session.', true);
                    $btn.prop('disabled', false).html('🗑️ Delete');
                }
            });
        });

        // Delete Session Handler (Inside Edit Modal)
        $('#df-edit-modal-delete-btn').on('click', function (e) {
            e.preventDefault();
            var sessionId = $('#df-edit-session-id').val();
            var student = $('#df-edit-student-name').val() || 'Student';
            var nonce = window.DriveFlowAdmin ? window.DriveFlowAdmin.nonce : '';

            if (!sessionId) return;
            if (!confirm('Are you sure you want to permanently delete Session #' + sessionId + ' for ' + student + '?\n\nThis cannot be undone.')) {
                return;
            }

            var $btn = $(this);
            $btn.prop('disabled', true).text('Deleting...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'driveflow_delete_session',
                    session_id: sessionId,
                    nonce: nonce
                },
                success: function (res) {
                    if (res && res.success) {
                        showToast('Session permanently deleted.');
                        $('#df-edit-session-modal').removeClass('is-open');
                        $('button.df-delete-session-btn[data-session-id="' + sessionId + '"]').closest('tr').css('background', '#fee2e2').fadeOut(400, function () {
                            $(this).remove();
                        });
                        setTimeout(function () { location.reload(); }, 600);
                    } else {
                        showToast((res && res.data) || 'Failed to delete session.', true);
                        $btn.prop('disabled', false).html('🗑️ Delete Session');
                    }
                },
                error: function () {
                    showToast('Server error while deleting session.', true);
                    $btn.prop('disabled', false).html('🗑️ Delete Session');
                }
            });
        });

        // 2. Edit Student (Populate Modal & Submit)
        $(document).on('click', '.df-edit-student-btn', function (e) {
            e.preventDefault();
            var $btn = $(this);
            $('#df-edit-student-id').val($btn.data('student-id'));
            $('#df-edit-st-name').val($btn.data('name'));
            $('#df-edit-st-phone').val($btn.data('phone'));
            $('#df-edit-st-email').val($btn.data('email'));
            $('#df-edit-st-license').val($btn.data('license'));
            $('#df-edit-st-package').val($btn.data('package'));
            $('#df-edit-st-total').val($btn.data('total'));
            $('#df-edit-st-completed').val($btn.data('completed'));

            $('#df-edit-student-modal').addClass('is-open');
        });

        $('#df-edit-student-form').on('submit', function (e) {
            e.preventDefault();
            var nonce = window.DriveFlowAdmin ? window.DriveFlowAdmin.nonce : '';
            var $btn = $('#df-edit-student-submit-btn');
            $btn.prop('disabled', true).text('Saving Changes...');

            var payload = {
                action: 'driveflow_update_student',
                nonce: nonce,
                student_id: $('#df-edit-student-id').val(),
                name: $('#df-edit-st-name').val(),
                phone: $('#df-edit-st-phone').val(),
                email: $('#df-edit-st-email').val(),
                license_number: $('#df-edit-st-license').val(),
                package_name: $('#df-edit-st-package').val(),
                total_sessions: $('#df-edit-st-total').val(),
                completed_sessions: $('#df-edit-st-completed').val()
            };

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: payload,
                success: function (res) {
                    $btn.prop('disabled', false).text('Save Student Changes');
                    if (res && res.success) {
                        showToast((res.data && res.data.message) || 'Student updated successfully!');
                        $('#df-edit-student-modal').removeClass('is-open');
                        setTimeout(function () { location.reload(); }, 700);
                    } else {
                        showToast((res && res.data) || 'Failed to update student.', true);
                    }
                },
                error: function () {
                    $btn.prop('disabled', false).text('Save Student Changes');
                    showToast('Error saving student updates.', true);
                }
            });
        });

        // 3. Edit Entity (Instructors & Fleet Vehicles)
        $(document).on('click', '.df-edit-entity-btn', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var entity = $btn.data('entity');
            var id = $btn.data('id');
            var rowData = $btn.data('row');

            $('#df-edit-entity-type').val(entity);
            $('#df-edit-entity-id').val(id);

            if (typeof rowData === 'string') {
                try { rowData = JSON.parse(rowData); } catch (err) {}
            }

            if (rowData) {
                for (var key in rowData) {
                    if (rowData.hasOwnProperty(key)) {
                        var $input = $('#df-edit-entity-' + key);
                        if ($input.length) {
                            $input.val(rowData[key]);
                        }
                    }
                }
                if (entity === 'instructors') {
                    $('#df-edit-instructor-photo').val(rowData.photo_url || '');
                    if (rowData.photo_url) {
                        $('#df-edit-instructor-photo-preview').attr('src', rowData.photo_url).show();
                    } else {
                        $('#df-edit-instructor-photo-preview').hide();
                    }

                    $('#df-edit-instructor-id-card').val(rowData.id_card_url || '');
                    if (rowData.id_card_url) {
                        $('#df-edit-id-card-link').attr('href', rowData.id_card_url).show();
                    } else {
                        $('#df-edit-id-card-link').hide();
                    }

                    $('#df-edit-instructor-badge').val(rowData.badge_url || '');
                    if (rowData.badge_url) {
                        $('#df-edit-badge-link').attr('href', rowData.badge_url).show();
                    } else {
                        $('#df-edit-badge-link').hide();
                    }

                    $('#df-edit-instructor-bio').val(rowData.bio || '');
                    $('#df-edit-instructor-hourly_wage').val(rowData.hourly_wage || '35.00');
                    $('#df-edit-instructor-payment_method').val(rowData.payment_method || 'zelle');
                    $('#df-edit-instructor-payment_details').val(rowData.payment_details || '');
                }
                $('#df-edit-entity-status').val(rowData.status || 'active');
            }

            $('#df-edit-entity-modal').addClass('is-open');
        });

        $('#df-edit-entity-form').on('submit', function (e) {
            e.preventDefault();
            var nonce = window.DriveFlowAdmin ? window.DriveFlowAdmin.nonce : '';
            var $btn = $('#df-edit-entity-submit-btn');
            $btn.prop('disabled', true).text('Saving Changes...');

            var formArray = $(this).serializeArray();
            var payload = {
                action: 'driveflow_update_entity',
                nonce: nonce
            };
            $.each(formArray, function (i, item) {
                payload[item.name] = item.value;
            });

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: payload,
                success: function (res) {
                    $btn.prop('disabled', false).text('Save Changes');
                    if (res && res.success) {
                        showToast((res.data && res.data.message) || 'Record updated successfully!');
                        $('#df-edit-entity-modal').removeClass('is-open');
                        setTimeout(function () { location.reload(); }, 700);
                    } else {
                        showToast((res && res.data) || 'Failed to update record.', true);
                    }
                },
                error: function () {
                    $btn.prop('disabled', false).text('Save Changes');
                    showToast('Error saving record updates.', true);
                }
            });
        });

        // Maryland Plate Showcase Preview Modal
        $(document).on('click', '.maryland-plate', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var $plate = $(this);
            var plateNum = $plate.data('plate') || $plate.find('.plate-number').text().trim();
            if (!plateNum || plateNum === '—') return;
            var model = $plate.data('model') || '';
            var color = $plate.data('color') || '';
            openMarylandPlateShowcase(plateNum, model, color);
        });

        function openMarylandPlateShowcase(plate, model, color) {
            var $modal = $('#df-plate-showcase-modal');
            if (!$modal.length) {
                var modalHtml = '<div class="df-modal-backdrop" id="df-plate-showcase-modal">' +
                    '<div class="df-modal" style="max-width:560px;">' +
                        '<div class="df-modal-header">' +
                            '<h3>State of Maryland · Vehicle Registration Showcase</h3>' +
                            '<button type="button" class="df-modal-close">&times;</button>' +
                        '</div>' +
                        '<div class="df-modal-body df-plate-modal-wrap">' +
                            '<div class="df-plate-showcase-box">' +
                                '<div class="maryland-plate plate-xl" id="df-modal-showcase-plate">' +
                                    '<span class="plate-bolt bolt-tl"></span><span class="plate-bolt bolt-tr"></span>' +
                                    '<span class="plate-number" id="df-modal-showcase-number"></span>' +
                                    '<span class="plate-bolt bolt-bl"></span><span class="plate-bolt bolt-br"></span>' +
                                    '<span class="plate-shine"></span>' +
                                '</div>' +
                                '<div class="df-plate-sticker-tag">DEC 26</div>' +
                            '</div>' +
                            '<div class="df-plate-info-grid">' +
                                '<div class="df-plate-info-item"><label>Jurisdiction</label><span>State of Maryland (MDOT MVA)</span></div>' +
                                '<div class="df-plate-info-item"><label>Registration Type</label><span>Commercial Driving School</span></div>' +
                                '<div class="df-plate-info-item"><label>Vehicle Model</label><span id="df-modal-showcase-model">—</span></div>' +
                                '<div class="df-plate-info-item"><label>Body Color</label><span id="df-modal-showcase-color">—</span></div>' +
                                '<div class="df-plate-info-item"><label>Fleet Status</label><span style="color:#16a34a;font-weight:700;">● Active & Road-Ready</span></div>' +
                                '<div class="df-plate-info-item"><label>Dual-Brake Inspection</label><span>Certified & Inspected</span></div>' +
                            '</div>' +
                        '</div>' +
                        '<div class="df-modal-footer" style="justify-content:space-between;">' +
                            '<button type="button" class="df-btn df-btn-secondary" id="df-copy-plate-btn">📋 Copy Plate</button>' +
                            '<div style="display:flex;gap:8px;">' +
                                '<a href="#" class="df-btn df-btn-secondary" id="df-filter-by-plate-btn">🔍 View Sessions</a>' +
                                '<button type="button" class="df-btn df-btn-primary" data-df-close-modal>Done</button>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                '</div>';
                $('body').append(modalHtml);
                $modal = $('#df-plate-showcase-modal');

                $modal.find('.df-modal-close, [data-df-close-modal]').on('click', function () {
                    $modal.removeClass('is-open');
                });
                $modal.on('click', function (ev) {
                    if ($(ev.target).hasClass('df-modal-backdrop')) {
                        $modal.removeClass('is-open');
                    }
                });
                $('#df-copy-plate-btn').on('click', function () {
                    var pText = $('#df-modal-showcase-number').text();
                    navigator.clipboard.writeText(pText).then(function () {
                        showToast('Plate copied: ' + pText);
                    });
                });
            }

            $('#df-modal-showcase-number').text(plate);
            $('#df-modal-showcase-model').text(model || 'Standard Training Fleet');
            $('#df-modal-showcase-color').text(color || 'Standard Silver');
            $('#df-filter-by-plate-btn').attr('href', 'admin.php?page=driveflow-pro-records&s=' + encodeURIComponent(plate));
            $modal.addClass('is-open');
        }

        // Live Real-Time Plate Generator in Forms (typing/pasting in plate input)
        $(document).on('input paste keyup blur', 'input[name="plate_number"], #df-modal-plate, #df-edit-entity-plate_number, #df-add-plate_number', function () {
            var val = $(this).val().toUpperCase().replace(/[^A-Z0-9\-\s]/g, '').slice(0, 10);
            $(this).val(val);
            var displayVal = val || 'SAMPLE';
            $('.maryland-plate-live-preview .plate-number, [data-live-plate-text]').text(displayVal);
        });

        // When opening edit entity modal for vehicles, update the live preview plate text
        $(document).on('click', '.df-edit-entity-btn', function () {
            var rowData = $(this).data('row');
            if (rowData && rowData.plate_number) {
                $('#df-edit-live-plate-text').text(rowData.plate_number);
            }
        });

        // 4. Toggle Instructor Status (Active / Inactive)
        $(document).on('click', '.df-toggle-instructor-status-btn', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var id = $btn.data('id');
            var nonce = window.DriveFlowAdmin ? window.DriveFlowAdmin.nonce : '';

            $btn.css('opacity', '0.5').prop('disabled', true);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'driveflow_toggle_instructor_status',
                    id: id,
                    nonce: nonce
                },
                success: function (res) {
                    $btn.css('opacity', '1').prop('disabled', false);
                    if (res && res.success) {
                        var newStatus = res.data.status;
                        $btn.data('status', newStatus);
                        if (newStatus === 'active') {
                            $btn.removeClass('df-badge-cancelled').addClass('df-badge-active').text('● ACTIVE');
                        } else {
                            $btn.removeClass('df-badge-active').addClass('df-badge-cancelled').text('○ INACTIVE');
                        }
                        showToast(res.data.message || 'Instructor status updated!');
                    } else {
                        showToast((res && res.data) || 'Failed to toggle status.', true);
                    }
                },
                error: function () {
                    $btn.css('opacity', '1').prop('disabled', false);
                    showToast('Server error toggling instructor status.', true);
                }
            });
        });

        // 5. Open Magic Link Generator Modal
        $(document).on('click', '.df-open-magic-modal-btn', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var type = $btn.data('type') || 'instructor_onboarding';
            var targetId = $btn.data('target-id');
            var name = $btn.data('name') || 'Recipient';
            var email = $btn.data('email') || '';

            $('#df-magic-link-type').val(type);
            $('#df-magic-link-target-id').val(targetId);
            $('#df-magic-recipient-name').text(name + ' (' + (type === 'student_booking' ? 'Student' : 'Instructor') + ')');
            $('#df-magic-recipient-email').text(email ? ('✉️ ' + email) : 'No email on file (Link must be sent manually via SMS/WhatsApp)');
            
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

        // 6. Generate Magic Link Submit
        $('#df-magic-link-form').on('submit', function (e) {
            e.preventDefault();
            var nonce = window.DriveFlowAdmin ? window.DriveFlowAdmin.nonce : '';
            var $btn = $('#df-generate-magic-submit-btn');
            $btn.prop('disabled', true).text('Generating Link...');

            var payload = {
                action: 'driveflow_generate_magic_link',
                nonce: nonce,
                type: $('#df-magic-link-type').val(),
                target_id: $('#df-magic-link-target-id').val(),
                duration_hours: $('#df-magic-duration').val(),
                is_single_use: $('#df-magic-single-use').is(':checked') ? 1 : 0,
                send_email: $('#df-magic-send-email').is(':checked') ? 1 : 0
            };

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: payload,
                success: function (res) {
                    $btn.prop('disabled', false).text('🚀 Generate Link');
                    if (res && res.success) {
                        var data = res.data;
                        $('#df-generated-short-url').val(data.short_url);
                        $('#df-magic-expires-label').text('Expires: ' + data.expires_at + (data.is_single_use ? ' (Single-use)' : ' (Multi-use)'));
                        if (data.emailed) {
                            $('#df-magic-email-status').text('✓ Email successfully delivered to ' + data.recipient_email).show();
                        } else if (payload.send_email && data.recipient_email) {
                            $('#df-magic-email-status').text('ℹ️ Email delivery attempted; you may also copy the short link below.').show();
                        } else {
                            $('#df-magic-email-status').hide();
                        }
                        $('#df-magic-result-box').slideDown(200);
                        showToast('Secure short link generated!');
                    } else {
                        showToast((res && res.data) || 'Failed to generate link.', true);
                    }
                },
                error: function () {
                    $btn.prop('disabled', false).text('🚀 Generate Link');
                    showToast('Server error generating link.', true);
                }
            });
        });

        // 7. Copy Generated Short URL
        $(document).on('click', '#df-copy-generated-url-btn', function (e) {
            e.preventDefault();
            var url = $('#df-generated-short-url').val();
            if (url) {
                navigator.clipboard.writeText(url).then(function () {
                    showToast('Short link copied: ' + url);
                });
            }
        });

        // 8. Media Uploader for Instructor Photo in Admin Edit
        $(document).on('click', '#df-upload-instructor-photo-btn', function (e) {
            e.preventDefault();
            if (typeof wp === 'undefined' || !wp.media) {
                var url = prompt('Enter Instructor Profile Photo URL:');
                if (url) {
                    $('#df-edit-instructor-photo').val(url);
                    $('#df-edit-instructor-photo-preview').attr('src', url).show();
                }
                return;
            }
            var frame = wp.media({
                title: 'Select Instructor Profile Photo',
                button: { text: 'Use this Photo' },
                multiple: false,
                library: { type: 'image' }
            });
            frame.on('select', function () {
                var attachment = frame.state().get('selection').first().toJSON();
                $('#df-edit-instructor-photo').val(attachment.url);
                $('#df-edit-instructor-photo-preview').attr('src', attachment.url).show();
            });
            frame.open();
        });

        // 9. Media Uploader for Instructor State ID Card
        $(document).on('click', '#df-upload-id-card-btn', function (e) {
            e.preventDefault();
            if (typeof wp === 'undefined' || !wp.media) {
                var url = prompt('Enter State ID / Driver License Photo URL:');
                if (url) {
                    $('#df-edit-instructor-id-card').val(url);
                    $('#df-edit-id-card-link').attr('href', url).show();
                }
                return;
            }
            var frame = wp.media({
                title: 'Select or Upload State ID / Driver License Card Photo',
                button: { text: 'Use this ID Card' },
                multiple: false,
                library: { type: ['image', 'application/pdf'] }
            });
            frame.on('select', function () {
                var attachment = frame.state().get('selection').first().toJSON();
                $('#df-edit-instructor-id-card').val(attachment.url);
                $('#df-edit-id-card-link').attr('href', attachment.url).show();
            });
            frame.open();
        });

        // 10. Media Uploader for Instructor Badge / Tag
        $(document).on('click', '#df-upload-badge-btn', function (e) {
            e.preventDefault();
            if (typeof wp === 'undefined' || !wp.media) {
                var url = prompt('Enter Instructor Certification Tag / Badge Photo URL:');
                if (url) {
                    $('#df-edit-instructor-badge').val(url);
                    $('#df-edit-badge-link').attr('href', url).show();
                }
                return;
            }
            var frame = wp.media({
                title: 'Select or Upload Instructor Badge / Tag Photo',
                button: { text: 'Use this Badge Tag' },
                multiple: false,
                library: { type: ['image', 'application/pdf'] }
            });
            frame.on('select', function () {
                var attachment = frame.state().get('selection').first().toJSON();
                $('#df-edit-instructor-badge').val(attachment.url);
                $('#df-edit-badge-link').attr('href', attachment.url).show();
            });
            frame.open();
        });

        // 11. View Executed Instructor Employment Agreement (Maryland MVA & UETA Audit Record)
        $(document).on('click', '.df-view-agreement-btn', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var instructorId = $btn.data('instructor-id');
            if (!instructorId) return;

            var origHtml = $btn.html();
            $btn.prop('disabled', true).text('Loading...');

            var ajaxUrl = (typeof DriveFlowAdmin !== 'undefined' && DriveFlowAdmin.ajaxurl) ? DriveFlowAdmin.ajaxurl : (typeof ajaxurl !== 'undefined' ? ajaxurl : '');
            var nonce = (typeof DriveFlowAdmin !== 'undefined' && DriveFlowAdmin.nonce) ? DriveFlowAdmin.nonce : '';

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'driveflow_get_signed_agreement',
                    nonce: nonce,
                    instructor_id: instructorId
                },
                success: function (res) {
                    $btn.prop('disabled', false).html(origHtml);
                    if (res && res.success && res.data) {
                        var d = res.data;
                        $('#df-agr-instructor-name').text(d.instructor_name || '—');
                        $('#df-agr-license-number').text(d.license_number || 'Pending');
                        $('#df-agr-timestamp').text(d.signed_at || 'Pending');
                        $('#df-agr-ip').text(d.ip_address || '—');
                        $('#df-agr-wage').text('$' + (d.hourly_wage || '35.00') + ' / hour');

                        if (d.school_name) $('#df-agr-school-name').text(d.school_name);
                        if (d.school_address) $('#df-agr-school-address').text(d.school_address);

                        $('#df-agr-contract-text').text(d.contract_text || '');
                        $('#df-agr-sig-name').text(d.instructor_name || '—');
                        $('#df-agr-sig-timestamp').text(d.signed_at || '—');

                        if (d.signature_data && d.signature_data.length > 50) {
                            $('#df-agr-signature-img').attr('src', d.signature_data).show();
                            $('#df-agr-no-sig').hide();
                        } else {
                            $('#df-agr-signature-img').hide();
                            $('#df-agr-no-sig').show();
                        }

                        $('#df-agreement-modal').addClass('is-open');
                    } else {
                        alert('Could not load agreement details: ' + (res.data || 'Unknown error'));
                    }
                },
                error: function () {
                    $btn.prop('disabled', false).html(origHtml);
                    alert('Server error while loading agreement record.');
                }
            });
        });

        // 12. Print / Export Agreement Record for MVA Audits
        $(document).on('click', '#df-print-agreement-btn', function (e) {
            e.preventDefault();
            var printEl = document.getElementById('df-agreement-print-area');
            if (!printEl) return;

            var printWin = window.open('', '_blank', 'width=900,height=700');
            if (printWin) {
                printWin.document.write('<!DOCTYPE html><html><head><title>Instructor Employment Agreement - MVA Compliance Record</title>');
                printWin.document.write('<style>');
                printWin.document.write('body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; font-size: 12px; line-height: 1.6; color: #1e293b; padding: 25px; }');
                printWin.document.write('#df-agr-contract-text { border: 1px solid #cbd5e1; padding: 15px; border-radius: 6px; white-space: pre-wrap; margin: 15px 0; }');
                printWin.document.write('div { box-sizing: border-box; }');
                printWin.document.write('@media print { body { padding: 0; } }');
                printWin.document.write('</style></head><body>');
                printWin.document.write(printEl.innerHTML);
                printWin.document.write('</body></html>');
                printWin.document.close();
                printWin.focus();
                setTimeout(function () {
                    printWin.print();
                    printWin.close();
                }, 350);
            } else {
                window.print();
            }
        });

        // 13. Open Record Instructor Compensation Payout Modal
        $(document).on('click', '.df-open-record-payout-btn', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var insId = $btn.data('instructor-id') || $btn.data('id');
            var insName = $btn.data('instructor-name') || $btn.data('name') || 'Instructor';
            var wage = parseFloat($btn.data('hourly-wage') || $btn.data('wage') || 35.00);
            var bal = parseFloat($btn.data('balance-due') || $btn.data('balance') || 0.0);
            var gross = parseFloat($btn.data('gross-earned') || $btn.data('earned') || 0.0);
            var paid = parseFloat($btn.data('total-paid') || $btn.data('paid') || 0.0);
            var hoursUnpaid = parseFloat($btn.data('hours-unpaid') || $btn.data('hours') || 0.0);
            var method = $btn.data('payment-method') || $btn.data('method') || 'zelle';
            var details = $btn.data('payment-details') || $btn.data('details') || '';

            if (bal > 0 && !gross) {
                gross = paid + bal;
            }

            $('#df-payout-instructor-id').val(insId);
            $('#df-payout-instructor-name').text(insName);
            $('#df-payout-instructor-rate').text('$' + wage.toFixed(2) + '/hr');
            $('#df-payout-gross-earned').text('$' + gross.toFixed(2));
            $('#df-payout-total-paid').text('$' + paid.toFixed(2));
            $('#df-payout-balance-due').text('$' + bal.toFixed(2));

            // Deposit instructions preview
            var methodNames = {
                'zelle': '⚡ Zelle',
                'check': '✉️ Paper Check',
                'direct_deposit': '🏦 Direct Deposit / ACH',
                'cash': '💵 Cash / HQ Pickup',
                'other': 'Other Method'
            };
            var methodLabel = methodNames[method] || String(method).toUpperCase();
            var depositHtml = '<strong>' + methodLabel + ':</strong> ' + (details ? $('<div>').text(details).html() : '<span style="color:#94a3b8;font-style:italic;">No custom deposit instructions on file. Contact instructor.</span>');
            $('#df-payout-deposit-preview').html(depositHtml);

            // Pre-fill amount with remaining balance if available
            $('#df-payout-amount').val(bal > 0 ? bal.toFixed(2) : '');
            if (hoursUnpaid > 0) {
                $('#df-payout-hours').val(hoursUnpaid.toFixed(1));
            } else if (wage > 0 && bal > 0) {
                $('#df-payout-hours').val((bal / wage).toFixed(1));
            } else {
                $('#df-payout-hours').val('');
            }

            $('#df-payout-method').val(method);
            $('#df-payout-reference').val('');
            $('#df-payout-notes').val('');
            $('#df-payout-status-msg').hide();
            $('#df-submit-payout-btn').prop('disabled', false).text('✓ Disburse & Record Payout');

            $('#df-record-payout-modal').addClass('is-open');
        });

        // 14. Submit Record Payout Form (AJAX)
        $('#df-record-payout-form').on('submit', function (e) {
            e.preventDefault();
            var $form = $(this);
            var $btn = $('#df-submit-payout-btn');
            var $msg = $('#df-payout-status-msg');
            var nonce = (typeof DriveFlowAdmin !== 'undefined' && DriveFlowAdmin.nonce) ? DriveFlowAdmin.nonce : '';
            var ajaxUrl = (typeof DriveFlowAdmin !== 'undefined' && DriveFlowAdmin.ajaxurl) ? DriveFlowAdmin.ajaxurl : (typeof ajaxurl !== 'undefined' ? ajaxurl : '');

            var amount = parseFloat($('#df-payout-amount').val());
            if (!amount || amount <= 0) {
                alert('Please enter a valid payout amount greater than $0.00.');
                return;
            }

            $btn.prop('disabled', true).text('Recording Disbursement...');
            $msg.hide();

            var formArray = $form.serializeArray();
            var payload = {
                action: 'driveflow_record_instructor_payout',
                nonce: nonce
            };
            $.each(formArray, function (i, item) {
                payload[item.name] = item.value;
            });

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: payload,
                success: function (res) {
                    $btn.prop('disabled', false).text('✓ Disburse & Record Payout');
                    if (res && res.success) {
                        $msg.css({ background: '#dcfce7', color: '#15803d', border: '1px solid #86efac' })
                            .text('✓ Payment recorded successfully! Voucher ID: #' + res.data.payout_id + '. Ledger updated.')
                            .show();
                        showToast('Payout recorded successfully! Ledger updated.');
                        setTimeout(function () {
                            $('#df-record-payout-modal').removeClass('is-open');
                            location.reload();
                        }, 900);
                    } else {
                        var err = (res && res.data && res.data.message) || (res && res.data) || 'Failed to record payout.';
                        $msg.css({ background: '#fee2e2', color: '#b91c1c', border: '1px solid #fca5a5' })
                            .text('Error: ' + err)
                            .show();
                    }
                },
                error: function () {
                    $btn.prop('disabled', false).text('✓ Disburse & Record Payout');
                    $msg.css({ background: '#fee2e2', color: '#b91c1c', border: '1px solid #fca5a5' })
                        .text('Server communication error while recording payout.')
                        .show();
                }
            });
        });

        // 15. Helper: Fetch and Render Payout History Table
        var currentHistoryInstructorId = 0;
        function loadPayoutHistory(instructorId, instructorName) {
            currentHistoryInstructorId = instructorId;
            var $tbody = $('#df-history-table-body');
            $tbody.html('<tr><td colspan="9" style="text-align:center;padding:24px;color:#94a3b8;">Loading disbursement history...</td></tr>');
            $('#df-history-instructor-name').text(instructorName ? (instructorName + ' — Disbursement Archive') : 'All Instructors Ledger');
            $('#df-history-count-badge').text('');

            var nonce = (typeof DriveFlowAdmin !== 'undefined' && DriveFlowAdmin.nonce) ? DriveFlowAdmin.nonce : '';
            var ajaxUrl = (typeof DriveFlowAdmin !== 'undefined' && DriveFlowAdmin.ajaxurl) ? DriveFlowAdmin.ajaxurl : (typeof ajaxurl !== 'undefined' ? ajaxurl : '');

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'driveflow_get_instructor_payout_history',
                    nonce: nonce,
                    instructor_id: instructorId || 0
                },
                success: function (res) {
                    if (res && res.success && res.data && res.data.payouts) {
                        var payouts = res.data.payouts;
                        $('#df-history-count-badge').text('(' + payouts.length + ' records)');
                        if (payouts.length === 0) {
                            $tbody.html('<tr><td colspan="9" style="text-align:center;padding:30px;color:#94a3b8;">No disbursement records found for this instructor.</td></tr>');
                            return;
                        }

                        var rowsHtml = '';
                        $.each(payouts, function (i, p) {
                            var amt = parseFloat(p.amount || 0).toFixed(2);
                            var hrs = parseFloat(p.hours_paid || 0).toFixed(1);
                            var rate = parseFloat(p.hourly_rate || 0).toFixed(2);
                            var method = (p.payment_method || 'zelle').toUpperCase();
                            var ref = p.reference_number || '—';
                            var period = (p.period_start && p.period_end) ? (p.period_start + ' to ' + p.period_end) : 'Standard';
                            var notes = p.notes || '';
                            var pJson = $('<div>').text(JSON.stringify(p)).html();

                            rowsHtml += '<tr id="df-payout-row-' + p.id + '">' +
                                '<td><strong>' + (p.payment_date || '—') + '</strong></td>' +
                                '<td><strong style="color:#059669;font-size:14px;">$' + amt + '</strong></td>' +
                                '<td>' + hrs + ' hrs</td>' +
                                '<td>$' + rate + '/hr</td>' +
                                '<td><span class="df-badge" style="background:#f1f5f9;color:#334155;border:1px solid #cbd5e1;font-size:11px;font-weight:700;">' + method + '</span></td>' +
                                '<td style="font-family:monospace;font-size:11px;">' + ref + '</td>' +
                                '<td style="font-size:11px;color:#64748b;">' + period + '</td>' +
                                '<td style="font-size:11px;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="' + $('<div>').text(notes).html() + '">' + (notes || '—') + '</td>' +
                                '<td><div style="display:flex;gap:4px;">' +
                                    '<button type="button" class="button button-small df-print-voucher-btn" data-payout-json=\'' + pJson + '\' style="color:#0284c7;border-color:#bae6fd;" title="Print Voucher Slip">🖨️ Voucher</button>' +
                                    '<button type="button" class="button button-small df-delete-payout-btn" data-payout-id="' + p.id + '" style="color:#dc2626;border-color:#fca5a5;" title="Delete / Void Transaction">🗑️</button>' +
                                '</div></td>' +
                            '</tr>';
                        });
                        $tbody.html(rowsHtml);
                    } else {
                        $tbody.html('<tr><td colspan="9" style="text-align:center;padding:24px;color:#ef4444;">Failed to load payout history.</td></tr>');
                    }
                },
                error: function () {
                    $tbody.html('<tr><td colspan="9" style="text-align:center;padding:24px;color:#ef4444;">Server error loading payout history.</td></tr>');
                }
            });
        }

        // 16. Open Payout History Modal
        $(document).on('click', '.df-open-payout-history-btn', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var insId = $btn.data('instructor-id') || $btn.data('id') || 0;
            var insName = $btn.data('instructor-name') || $btn.data('name') || '';

            loadPayoutHistory(insId, insName);
            $('#df-payout-history-modal').addClass('is-open');
        });

        // 17. Refresh History Button
        $(document).on('click', '#df-history-refresh-btn', function (e) {
            e.preventDefault();
            var currentName = $('#df-history-instructor-name').text().replace(' — Disbursement Archive', '');
            loadPayoutHistory(currentHistoryInstructorId, currentName);
        });

        // 18. Delete / Void Payout
        $(document).on('click', '.df-delete-payout-btn', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var payoutId = $btn.data('payout-id');
            if (!payoutId) return;

            if (!confirm('Are you sure you want to delete and void this payout record (#PAY-' + payoutId + ')?\n\nThis will adjust the remaining balance due in the instructor ledger.')) {
                return;
            }

            $btn.prop('disabled', true);
            var nonce = (typeof DriveFlowAdmin !== 'undefined' && DriveFlowAdmin.nonce) ? DriveFlowAdmin.nonce : '';
            var ajaxUrl = (typeof DriveFlowAdmin !== 'undefined' && DriveFlowAdmin.ajaxurl) ? DriveFlowAdmin.ajaxurl : (typeof ajaxurl !== 'undefined' ? ajaxurl : '');

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'driveflow_delete_instructor_payout',
                    nonce: nonce,
                    payout_id: payoutId
                },
                success: function (res) {
                    if (res && res.success) {
                        showToast('Payout #' + payoutId + ' voided & removed.');
                        $('#df-payout-row-' + payoutId).fadeOut(300, function () { $(this).remove(); });
                        $('#df-archive-row-' + payoutId).fadeOut(300, function () { $(this).remove(); });
                    } else {
                        $btn.prop('disabled', false);
                        alert('Could not delete payout: ' + ((res && res.data) || 'Unknown error'));
                    }
                },
                error: function () {
                    $btn.prop('disabled', false);
                    alert('Server communication error while deleting payout.');
                }
            });
        });

        // 19. Print Official Remuneration Voucher Slip
        function printPayoutVoucher(payout) {
            if (!payout) return;
            $('#df-v-id').text('#PAY-' + String(payout.id).padStart(5, '0'));
            $('#df-v-date').text(payout.payment_date || '—');
            $('#df-v-name').text(payout.instructor_name || 'Instructor');
            $('#df-v-method').text((payout.payment_method || 'ZELLE').toUpperCase());
            $('#df-v-amount').text('$' + parseFloat(payout.amount || 0).toFixed(2));
            $('#df-v-hours').text((payout.hours_paid || '0.0') + ' Instructional Hours');
            $('#df-v-rate').text('$' + parseFloat(payout.hourly_rate || 35.00).toFixed(2) + ' / hour');
            $('#df-v-ref').text(payout.reference_number || 'N/A (Cash / Direct)');
            $('#df-v-notes').text(payout.notes || 'Routine settlement for behind-the-wheel driving instruction.');

            var printEl = document.getElementById('df-voucher-print-area');
            if (printEl) {
                var printWin = window.open('', '_blank', 'width=850,height=650');
                if (printWin) {
                    printWin.document.write('<!DOCTYPE html><html><head><title>Payment Voucher - #PAY-' + payout.id + ' (' + (payout.instructor_name || 'Instructor') + ')</title>');
                    printWin.document.write('<style>');
                    printWin.document.write('body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; font-size: 13px; line-height: 1.6; color: #0f172a; padding: 30px; }');
                    printWin.document.write('@media print { body { padding: 0; } }');
                    printWin.document.write('</style></head><body>');
                    printWin.document.write(printEl.innerHTML);
                    printWin.document.write('</body></html>');
                    printWin.document.close();
                    printWin.focus();
                    setTimeout(function () {
                        printWin.print();
                        printWin.close();
                    }, 350);
                } else {
                    window.print();
                }
            }
        }

        $(document).on('click', '.df-print-voucher-btn, .df-print-single-voucher-btn', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var pData = $btn.data('payout-json');
            if (typeof pData === 'string') {
                try { pData = JSON.parse(pData); } catch (err) {}
            }
            if (pData) {
                printPayoutVoucher(pData);
            }
        });

        // Universal 1-Click Copy-to-Clipboard Handler
        $(document).on('click', '[data-df-copy-url]', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var url = $btn.attr('data-df-copy-url');
            if (!url) return;
            var origHtml = $btn.html();

            function showSuccess() {
                $btn.html('✓ Copied!').css({ 'background': '#16a34a', 'color': '#ffffff', 'border-color': '#15803d' });
                setTimeout(function () {
                    $btn.html(origHtml).removeAttr('style');
                }, 2200);
            }

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(showSuccess).catch(function () {
                    fallbackCopy(url);
                });
            } else {
                fallbackCopy(url);
            }

            function fallbackCopy(text) {
                var $temp = $('<input type="text">');
                $('body').append($temp);
                $temp.val(text).select();
                try {
                    document.execCommand('copy');
                    showSuccess();
                } catch (err) {
                    window.prompt('Copy to clipboard: Ctrl+C, Enter', text);
                }
                $temp.remove();
            }
        });

    });
})(jQuery);


