<?php
defined('ABSPATH') || exit;
?>
<!-- Modal: Record Instructor Compensation Payout -->
<div class="df-modal-backdrop" id="df-record-payout-modal">
    <div class="df-modal" style="max-width:620px;">
        <div class="df-modal-header">
            <div style="display:flex;align-items:center;gap:10px;">
                <span style="font-size:24px;">💵</span>
                <div>
                    <h3 style="margin:0;font-size:17px;">Disburse & Record Instructor Payout</h3>
                    <div style="font-size:12px;color:#64748b;">Issue compensation for completed behind-the-wheel lessons</div>
                </div>
            </div>
            <button type="button" class="df-modal-close">&times;</button>
        </div>
        <form id="df-record-payout-form">
            <input type="hidden" name="instructor_id" id="df-payout-instructor-id" value="0">
            <div class="df-modal-body">
                <!-- Instructor & Financial Summary Header -->
                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:16px;margin-bottom:18px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                        <div>
                            <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;">Instructor</div>
                            <div style="font-size:16px;font-weight:800;color:#0f172a;" id="df-payout-instructor-name">—</div>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;">Hourly Rate</div>
                            <div style="font-size:15px;font-weight:800;color:#0f766e;" id="df-payout-instructor-rate">$0.00/hr</div>
                        </div>
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(3, 1fr);gap:10px;background:#fff;padding:12px;border-radius:8px;border:1px solid #cbd5e1;text-align:center;">
                        <div>
                            <div style="font-size:11px;color:#64748b;">Gross Earned</div>
                            <div style="font-size:14px;font-weight:700;color:#0f172a;" id="df-payout-gross-earned">$0.00</div>
                        </div>
                        <div>
                            <div style="font-size:11px;color:#64748b;">Total Paid to Date</div>
                            <div style="font-size:14px;font-weight:700;color:#16a34a;" id="df-payout-total-paid">$0.00</div>
                        </div>
                        <div>
                            <div style="font-size:11px;color:#b91c1c;font-weight:700;">Remaining Balance</div>
                            <div style="font-size:16px;font-weight:800;color:#b91c1c;" id="df-payout-balance-due">$0.00</div>
                        </div>
                    </div>
                </div>

                <!-- Instructor Deposit Instructions Preview Card -->
                <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:12px 16px;margin-bottom:18px;display:flex;align-items:flex-start;gap:10px;">
                    <span style="font-size:20px;">ℹ️</span>
                    <div>
                        <div style="font-size:12px;font-weight:700;color:#0369a1;text-transform:uppercase;">Instructor Preferred Banking / Deposit Details</div>
                        <div style="font-size:13px;color:#0f172a;margin-top:3px;" id="df-payout-deposit-preview">Loading deposit instructions...</div>
                    </div>
                </div>

                <!-- Form Fields -->
                <div class="df-form-grid">
                    <div class="df-form-group">
                        <label>Payout Amount ($ USD) *</label>
                        <input required type="number" step="0.01" min="0.01" name="amount" id="df-payout-amount" placeholder="0.00" style="font-size:16px;font-weight:800;color:#0f766e;">
                    </div>
                    <div class="df-form-group">
                        <label>Instructional Hours Covered</label>
                        <input type="number" step="0.5" min="0" name="hours_paid" id="df-payout-hours" placeholder="e.g. 10.0">
                    </div>
                    <div class="df-form-group">
                        <label>Payment Date *</label>
                        <input required type="date" name="payment_date" id="df-payout-date" value="<?php echo current_time('Y-m-d'); ?>">
                    </div>
                    <div class="df-form-group">
                        <label>Disbursement Method *</label>
                        <select name="payment_method" id="df-payout-method">
                            <option value="zelle">⚡ Zelle</option>
                            <option value="check">✉️ Paper Check</option>
                            <option value="direct_deposit">🏦 Direct Deposit / ACH</option>
                            <option value="cash">💵 Cash / HQ Pickup</option>
                            <option value="other">Other Method</option>
                        </select>
                    </div>
                    <div class="df-form-group" style="grid-column: span 2;">
                        <label>Reference # / Confirmation / Check Number</label>
                        <input type="text" name="reference_number" id="df-payout-reference" placeholder="e.g. Check #1042, Zelle Conf #29384729, or ACH Trace ID">
                    </div>
                    <div class="df-form-group">
                        <label>Pay Period Start</label>
                        <input type="date" name="period_start" id="df-payout-period-start">
                    </div>
                    <div class="df-form-group">
                        <label>Pay Period End</label>
                        <input type="date" name="period_end" id="df-payout-period-end">
                    </div>
                    <div class="df-form-group" style="grid-column: span 2;">
                        <label>Disbursement Notes / Memo</label>
                        <textarea name="notes" id="df-payout-notes" rows="2" placeholder="e.g. Bi-weekly settlement for highway and parking sessions..."></textarea>
                    </div>
                </div>

                <div id="df-payout-status-msg" style="display:none;margin-top:14px;padding:10px 14px;border-radius:6px;font-size:13px;font-weight:600;"></div>
            </div>
            <div class="df-modal-footer">
                <button type="button" class="df-btn df-btn-secondary" data-df-close-modal>Cancel</button>
                <button type="submit" class="df-btn df-btn-primary" id="df-submit-payout-btn">✓ Disburse & Record Payout</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Instructor Payout History & Disbursement Archive -->
<div class="df-modal-backdrop" id="df-payout-history-modal">
    <div class="df-modal" style="max-width:850px;">
        <div class="df-modal-header">
            <div style="display:flex;align-items:center;gap:10px;">
                <span style="font-size:24px;">📜</span>
                <div>
                    <h3 style="margin:0;font-size:17px;">Instructor Payout History & Archive</h3>
                    <div style="font-size:12px;color:#64748b;">Complete chronological ledger of disbursed payments and vouchers</div>
                </div>
            </div>
            <button type="button" class="df-modal-close">&times;</button>
        </div>
        <div class="df-modal-body">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;background:#f8fafc;padding:12px 16px;border-radius:8px;border:1px solid #e2e8f0;">
                <div>
                    <strong style="font-size:15px;color:#0f172a;" id="df-history-instructor-name">All Instructors</strong>
                    <span style="font-size:12px;color:#64748b;margin-left:8px;" id="df-history-count-badge"></span>
                </div>
                <button type="button" class="df-btn df-btn-secondary df-btn-sm" id="df-history-refresh-btn">🔄 Refresh</button>
            </div>

            <div style="max-height:380px;overflow-y:auto;border:1px solid #cbd5e1;border-radius:8px;">
                <table class="df-table" style="margin:0;">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Hours</th>
                            <th>Rate</th>
                            <th>Method</th>
                            <th>Reference #</th>
                            <th>Period</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="df-history-table-body">
                        <tr><td colspan="9" style="text-align:center;padding:24px;color:#94a3b8;">Loading payout records...</td></tr>
                    </tbody>
                </table>
            </div>

            <!-- Printable Voucher Area (Hidden until print) -->
            <div id="df-voucher-print-area" style="display:none;padding:24px;background:#fff;border:1px solid #000;margin-top:20px;font-family:sans-serif;">
                <div style="text-align:center;border-bottom:2px solid #000;padding-bottom:12px;margin-bottom:16px;">
                    <h2 style="margin:0;font-size:20px;"><?php echo esc_html(get_bloginfo('name') ?: "Sam's Driving School LLC"); ?></h2>
                    <p style="margin:4px 0 0 0;font-size:13px;color:#555;">Official Driving Instructor Compensation Voucher & Remittance Slip</p>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:13px;margin-bottom:16px;">
                    <div><strong>Voucher #:</strong> <span id="df-v-id">—</span></div>
                    <div><strong>Disbursement Date:</strong> <span id="df-v-date">—</span></div>
                    <div><strong>Payee Instructor:</strong> <span id="df-v-name">—</span></div>
                    <div><strong>Payment Method:</strong> <span id="df-v-method">—</span></div>
                    <div><strong>Amount Disbursed:</strong> <span id="df-v-amount" style="font-size:16px;font-weight:bold;color:#047857;">—</span></div>
                    <div><strong>Hours Covered:</strong> <span id="df-v-hours">—</span></div>
                    <div><strong>Hourly Rate:</strong> <span id="df-v-rate">—</span></div>
                    <div><strong>Reference / Check #:</strong> <span id="df-v-ref">—</span></div>
                </div>
                <div style="border-top:1px dashed #ccc;padding-top:12px;font-size:12px;color:#555;margin-bottom:24px;">
                    <strong>Memo / Notes:</strong> <span id="df-v-notes">—</span>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:40px;margin-top:30px;padding-top:20px;border-top:1px solid #000;">
                    <div>
                        <div style="border-bottom:1px solid #000;height:30px;"></div>
                        <div style="font-size:11px;margin-top:4px;">Authorized Academy Administrator Signature</div>
                    </div>
                    <div>
                        <div style="border-bottom:1px solid #000;height:30px;"></div>
                        <div style="font-size:11px;margin-top:4px;">Instructor Acknowledgment Signature</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="df-modal-footer">
            <button type="button" class="df-btn df-btn-secondary" data-df-close-modal>Close</button>
        </div>
    </div>
</div>
