(function () {
    'use strict';
    var config = window.DriveFlowPro || {};

    function request(path, options) {
        options = options || {};
        options.headers = Object.assign({ 'Content-Type': 'application/json' }, options.headers || {});
        if (config.nonce) options.headers['X-WP-Nonce'] = config.nonce;
        return fetch((config.root || '') + path, options).then(function (response) {
            return response.json().then(function (body) {
                if (!response.ok) throw new Error(body.message || 'Network communication error');
                return body;
            });
        });
    }

    function setMessage(element, text, isError) {
        if (!element) return;
        element.textContent = text;
        element.classList.toggle('is-error', Boolean(isError));
    }

    // Modern Signature Pad
    function initSignature(form) {
        var canvas = form.querySelector('[data-signature-canvas]');
        var input = form.querySelector('[data-signature-input]');
        if (!canvas || !input) return;

        var context = canvas.getContext('2d');
        var drawing = false;

        function resize() {
            var ratio = window.devicePixelRatio || 1;
            var width = canvas.clientWidth || 600;
            var height = canvas.clientHeight || 180;
            canvas.width = width * ratio;
            canvas.height = height * ratio;
            context.scale(ratio, ratio);
            context.strokeStyle = '#0f172a';
            context.lineWidth = 2.5;
            context.lineCap = 'round';
            context.lineJoin = 'round';
        }

        function point(event) {
            var rect = canvas.getBoundingClientRect();
            return { x: event.clientX - rect.left, y: event.clientY - rect.top };
        }

        canvas.addEventListener('pointerdown', function (event) {
            drawing = true;
            canvas.setPointerCapture(event.pointerId);
            var p = point(event);
            context.beginPath();
            context.moveTo(p.x, p.y);
        });

        canvas.addEventListener('pointermove', function (event) {
            if (!drawing) return;
            var p = point(event);
            context.lineTo(p.x, p.y);
            context.stroke();
            input.value = canvas.toDataURL('image/png');
        });

        ['pointerup', 'pointercancel'].forEach(function (name) {
            canvas.addEventListener(name, function () { drawing = false; });
        });

        var clearBtn = form.querySelector('[data-clear-signature]');
        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                context.clearRect(0, 0, canvas.width, canvas.height);
                input.value = '';
            });
        }

        resize();
        window.addEventListener('resize', resize);
    }

    // Stopwatch Timer
    function initTimer(form) {
        var digits = form.querySelector('[data-timer-digits]');
        var toggleBtn = form.querySelector('[data-timer-toggle]');
        var resetBtn = form.querySelector('[data-timer-reset]');
        if (!digits || !toggleBtn) return;

        var timerInterval = null;
        var secondsElapsed = 0;
        var running = false;

        function renderDigits() {
            var h = Math.floor(secondsElapsed / 3600);
            var m = Math.floor((secondsElapsed % 3600) / 60);
            var s = secondsElapsed % 60;
            var pad = function (n) { return n < 10 ? '0' + n : n; };
            digits.textContent = pad(h) + ':' + pad(m) + ':' + pad(s);
        }

        toggleBtn.addEventListener('click', function () {
            running = !running;
            if (running) {
                toggleBtn.textContent = '⏸ Pause Timer';
                toggleBtn.style.background = '#f59e0b';
                toggleBtn.style.color = '#000';
                timerInterval = window.setInterval(function () {
                    secondsElapsed++;
                    renderDigits();
                }, 1000);
            } else {
                toggleBtn.textContent = '▶ Resume Timer';
                toggleBtn.style.background = '';
                toggleBtn.style.color = '';
                window.clearInterval(timerInterval);
            }
        });

        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                running = false;
                window.clearInterval(timerInterval);
                secondsElapsed = 0;
                renderDigits();
                toggleBtn.textContent = '▶ Start Lesson Timer';
                toggleBtn.style.background = '';
                toggleBtn.style.color = '';
            });
        }
    }

    // Interactive Star Rating Controller
    function initStarRatings(form) {
        var groups = form.querySelectorAll('.df-star-group');
        groups.forEach(function (group) {
            var input = group.querySelector('input[type="hidden"]');
            var stars = group.querySelectorAll('.df-star');
            stars.forEach(function (star) {
                star.addEventListener('click', function () {
                    var val = parseInt(star.getAttribute('data-val'), 10) || 1;
                    if (input) input.value = val;
                    stars.forEach(function (s) {
                        var sVal = parseInt(s.getAttribute('data-val'), 10) || 1;
                        s.classList.toggle('is-active', sVal <= val);
                    });
                });
            });
        });
    }

    // Instructor Form Controller
    function initForm(form) {
        var video = form.querySelector('[data-camera]');
        var canvas = form.querySelector('[data-selfie-canvas]');
        var preview = form.querySelector('[data-selfie-preview]');
        var selfieInput = form.querySelector('[data-selfie-input]');
        var cameraButton = form.querySelector('[data-start-camera]');
        var flipButton = form.querySelector('[data-flip-camera]');
        var captureButton = form.querySelector('[data-capture-selfie]');
        var submitBtn = form.querySelector('[data-submit-btn]');
        var message = form.querySelector('[data-form-message]');

        var stream = null;
        var useFrontCamera = true;

        // Auto-set current time and default +2 hours for scheduled start/end
        var now = new Date();
        var pad = function (n) { return n < 10 ? '0' + n : n; };
        var startIso = now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-' + pad(now.getDate()) + 'T' + pad(now.getHours()) + ':' + pad(now.getMinutes());
        var endD = new Date(now.getTime() + 2 * 60 * 60 * 1000);
        var endIso = endD.getFullYear() + '-' + pad(endD.getMonth() + 1) + '-' + pad(endD.getDate()) + 'T' + pad(endD.getHours()) + ':' + pad(endD.getMinutes());

        var startInput = form.querySelector('#driveflow-form-start');
        var endInput = form.querySelector('#driveflow-form-end');
        if (startInput && !startInput.value) startInput.value = startIso;
        if (endInput && !endInput.value) endInput.value = endIso;

        // Load directory (students, instructors, vehicles)
        request('/directory').then(function (dir) {
            var stDatalist = form.querySelector('#driveflow-students');
            if (stDatalist && dir.students) {
                dir.students.forEach(function (s) {
                    var opt = document.createElement('option');
                    opt.value = s.student_name;
                    stDatalist.appendChild(opt);
                });
            }

            var insDatalist = form.querySelector('#driveflow-instructors');
            if (insDatalist && dir.instructors) {
                dir.instructors.forEach(function (ins) {
                    var opt = document.createElement('option');
                    opt.value = ins.instructor_name;
                    insDatalist.appendChild(opt);
                });
            }

            var vehSelect = form.querySelector('#driveflow-vehicle-select');
            if (vehSelect && dir.vehicles) {
                vehSelect.innerHTML = '<option value="">-- Select Training Vehicle & Plate --</option>';
                dir.vehicles.forEach(function (v) {
                    var opt = document.createElement('option');
                    opt.value = v.plate_number;
                    opt.textContent = v.plate_number + (v.model ? ' (' + v.model + ')' : '');
                    vehSelect.appendChild(opt);
                });
            }
        }).catch(function () {
            var vehSelect = form.querySelector('#driveflow-vehicle-select');
            if (vehSelect) vehSelect.innerHTML = '<option value="">-- No vehicle selected --</option>';
        });

        function startCamera() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                return setMessage(message, 'Camera access is not supported on this device/browser.', true);
            }
            if (stream) {
                stream.getTracks().forEach(function (t) { t.stop(); });
            }
            var constraints = {
                video: { facingMode: useFrontCamera ? 'user' : 'environment' },
                audio: false
            };
            navigator.mediaDevices.getUserMedia(constraints).then(function (activeStream) {
                stream = activeStream;
                video.srcObject = stream;
                video.style.display = 'block';
                if (preview) preview.style.display = 'none';
                captureButton.disabled = false;
                flipButton.disabled = false;
                cameraButton.textContent = 'Camera Active ✓';
                setMessage(message, 'Camera is active. Frame yourself and tap Capture Selfie.');
            }).catch(function (err) {
                setMessage(message, 'Camera access denied: ' + err.message, true);
            });
        }

        cameraButton.addEventListener('click', startCamera);

        flipButton.addEventListener('click', function () {
            useFrontCamera = !useFrontCamera;
            startCamera();
        });

        captureButton.addEventListener('click', function () {
            canvas.width = video.videoWidth || 640;
            canvas.height = video.videoHeight || 480;
            var ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            var dataUrl = canvas.toDataURL('image/jpeg', 0.85);
            selfieInput.value = dataUrl;

            if (preview) {
                preview.src = dataUrl;
                preview.style.display = 'block';
                video.style.display = 'none';
            }
            setMessage(message, 'Instructor selfie captured successfully ✓');
        });

        // Form Submit
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            var data = Object.fromEntries(new FormData(form).entries());
            data.session_number = Number(data.session_number) || 1;
            data.scheduled_start = data.scheduled_start.replace('T', ' ') + ':00';
            data.scheduled_end = data.scheduled_end.replace('T', ' ') + ':00';

            data.form_data = {
                source: 'instructor-form',
                captured_at: new Date().toISOString(),
                plate: data.plate_number || '',
                skills: {
                    clutch: parseInt(data.skill_clutch, 10) || 4,
                    parking: parseInt(data.skill_parking, 10) || 4,
                    steering: parseInt(data.skill_steering, 10) || 5,
                    rules: parseInt(data.skill_rules, 10) || 5
                },
                instructor_notes: data.instructor_notes || ''
            };

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.querySelector('.submit-text').style.display = 'none';
                submitBtn.querySelector('.submit-spinner').style.display = 'inline';
            }

            function sendSessionPayload() {
                request('/sessions', { method: 'POST', body: JSON.stringify(data) }).then(function () {
                    setMessage(message, 'Session & scorecard recorded successfully! Confirmation emails dispatched ✓');
                    form.reset();
                    if (preview) preview.style.display = 'none';
                    if (stream) stream.getTracks().forEach(function (t) { t.stop(); });
                }).catch(function (error) {
                    setMessage(message, error.message, true);
                }).finally(function () {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.querySelector('.submit-text').style.display = 'inline';
                        submitBtn.querySelector('.submit-spinner').style.display = 'none';
                    }
                });
            }

            // Optional GPS location
            if (form.querySelector('[data-geo-checkbox]') && form.querySelector('[data-geo-checkbox]').checked && navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function (pos) {
                    data.form_data.gps = { lat: pos.coords.latitude, lng: pos.coords.longitude };
                    sendSessionPayload();
                }, function () {
                    sendSessionPayload();
                }, { timeout: 4000 });
            } else {
                sendSessionPayload();
            }
        });

        initSignature(form);
        initTimer(form);
        initStarRatings(form);
    }

    // Audio Chime synthesizer via Web Audio API
    function playChime() {
        try {
            var AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) return;
            var ctx = new AudioContext();
            var osc = ctx.createOscillator();
            var gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(587.33, ctx.currentTime);
            osc.frequency.exponentialRampToValueAtTime(880, ctx.currentTime + 0.18);
            gain.gain.setValueAtTime(0.18, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.65);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + 0.65);
        } catch (e) {}
    }

    // Speech synthesis voice announcement
    function announceVoice(text) {
        if (!window.speechSynthesis) return;
        try {
            window.speechSynthesis.cancel();
            var utterance = new SpeechSynthesisUtterance(text);
            utterance.rate = 0.95;
            utterance.pitch = 1.05;
            var voices = window.speechSynthesis.getVoices();
            for (var i = 0; i < voices.length; i++) {
                if (voices[i].lang && voices[i].lang.indexOf('en') === 0) {
                    utterance.voice = voices[i];
                    break;
                }
            }
            window.speechSynthesis.speak(utterance);
        } catch (e) {}
    }

    // ── TV Lobby Board Controller v2 ──
    function initBoard(board) {
        var grid        = board.querySelector('[data-session-list]');
        var message     = board.querySelector('[data-board-message]');
        var clock       = board.querySelector('[data-board-clock]');
        var dateDisplay = board.querySelector('[data-board-date]');
        var soundBtn    = board.querySelector('[data-toggle-sound]');
        var speechBtn   = board.querySelector('[data-toggle-speech]');
        var fsBtn       = board.querySelector('[data-toggle-fullscreen]');
        var counterEl   = board.querySelector('[data-session-count]');

        var soundEnabled    = true;
        var speechEnabled   = false;
        var previousActiveIds = {};

        // ── Animated Starfield ──
        var canvas = board.querySelector('.dfv2-starfield');
        if (canvas) {
            var ctx2 = canvas.getContext('2d');
            var stars = [];
            function resizeCanvas() {
                canvas.width  = board.offsetWidth  || window.innerWidth;
                canvas.height = board.offsetHeight || window.innerHeight;
            }
            function initStars() {
                stars = [];
                var count = Math.floor((canvas.width * canvas.height) / 5000);
                for (var i = 0; i < count; i++) {
                    stars.push({
                        x: Math.random() * canvas.width,
                        y: Math.random() * canvas.height,
                        r: Math.random() * 1.4 + 0.2,
                        a: Math.random(),
                        da: (Math.random() * 0.005 + 0.002) * (Math.random() > 0.5 ? 1 : -1)
                    });
                }
            }
            function drawStars() {
                ctx2.clearRect(0, 0, canvas.width, canvas.height);
                stars.forEach(function (s) {
                    s.a += s.da;
                    if (s.a <= 0 || s.a >= 1) s.da *= -1;
                    ctx2.beginPath();
                    ctx2.arc(s.x, s.y, s.r, 0, Math.PI * 2);
                    ctx2.fillStyle = 'rgba(255,255,255,' + s.a.toFixed(2) + ')';
                    ctx2.fill();
                });
                requestAnimationFrame(drawStars);
            }
            resizeCanvas();
            initStars();
            drawStars();
            window.addEventListener('resize', function () { resizeCanvas(); initStars(); });
        }

        // ── Controls ──
        if (soundBtn) {
            soundBtn.addEventListener('click', function () {
                soundEnabled = !soundEnabled;
                soundBtn.classList.toggle('is-on', soundEnabled);
                soundBtn.title = soundEnabled ? 'Chime sound active' : 'Chime sound muted';
            });
        }

        if (speechBtn) {
            speechBtn.addEventListener('click', function () {
                speechEnabled = !speechEnabled;
                speechBtn.classList.toggle('is-on', speechEnabled);
                speechBtn.title = speechEnabled ? 'Voice announcer active' : 'Voice announcer off';
                if (speechEnabled) announceVoice('Lobby voice announcer is now activated.');
            });
        }

        // ── Fullscreen & Kiosk Controller (Multi-Vendor + CSS Fallback) ──
        var expandIconSvg = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>';
        var compressIconSvg = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4 14 10 14 10 20"/><polyline points="20 10 14 10 14 4"/><line x1="14" y1="10" x2="21" y2="3"/><line x1="3" y1="21" x2="10" y2="14"/></svg>';

        function isNativeFs() {
            return !!(document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement);
        }

        function isFsActive() {
            return isNativeFs() || board.classList.contains('dfv2-is-fullscreen');
        }

        function syncFsUi() {
            var active = isFsActive();
            if (fsBtn) {
                fsBtn.classList.toggle('is-on', active);
                fsBtn.innerHTML = active ? compressIconSvg : expandIconSvg;
                fsBtn.title = active ? 'Exit Fullscreen (Esc)' : 'Enter Fullscreen (F)';
            }
            if (active) {
                document.body.classList.add('dfv2-kiosk-mode');
                document.documentElement.classList.add('dfv2-kiosk-mode');
            } else {
                document.body.classList.remove('dfv2-kiosk-mode');
                document.documentElement.classList.remove('dfv2-kiosk-mode');
            }
            setTimeout(function () {
                window.dispatchEvent(new Event('resize'));
            }, 80);
        }

        function toggleFullscreen() {
            if (!isFsActive()) {
                // Enter fullscreen: add CSS overlay class first (instant & reliable)
                board.classList.add('dfv2-is-fullscreen');
                var req = board.requestFullscreen || board.webkitRequestFullscreen || board.mozRequestFullScreen || board.msRequestFullscreen;
                if (req) {
                    try {
                        var p = req.call(board);
                        if (p && typeof p.catch === 'function') p.catch(function () {});
                    } catch (err) {}
                }
            } else {
                // Exit fullscreen
                board.classList.remove('dfv2-is-fullscreen');
                if (isNativeFs()) {
                    var exit = document.exitFullscreen || document.webkitExitFullscreen || document.mozCancelFullScreen || document.msExitFullscreen;
                    if (exit) {
                        try {
                            var pe = exit.call(document);
                            if (pe && typeof pe.catch === 'function') pe.catch(function () {});
                        } catch (err) {}
                    }
                }
            }
            syncFsUi();
        }

        if (fsBtn) {
            fsBtn.addEventListener('click', function (e) {
                e.preventDefault();
                toggleFullscreen();
            });
        }

        // Listen to native browser fullscreen change events
        ['fullscreenchange', 'webkitfullscreenchange', 'mozfullscreenchange', 'MSFullscreenChange'].forEach(function (evt) {
            document.addEventListener(evt, function () {
                if (!isNativeFs()) {
                    board.classList.remove('dfv2-is-fullscreen');
                }
                syncFsUi();
            });
        });

        // Keyboard shortcut: Press "F" to toggle fullscreen, "Esc" to exit
        window.addEventListener('keydown', function (e) {
            if (e.target && ['INPUT', 'TEXTAREA', 'SELECT'].indexOf(e.target.tagName) !== -1) return;
            if (e.key === 'f' || e.key === 'F') {
                toggleFullscreen();
            } else if (e.key === 'Escape' && board.classList.contains('dfv2-is-fullscreen')) {
                board.classList.remove('dfv2-is-fullscreen');
                syncFsUi();
            }
        });

        // ── Clock ──
        function tick() {
            var now = new Date();
            if (clock) {
                clock.textContent = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            }
            if (dateDisplay) {
                try {
                    dateDisplay.textContent = new Intl.DateTimeFormat('en-US', {
                        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
                    }).format(now);
                } catch (e) {
                    dateDisplay.textContent = now.toDateString();
                }
            }
        }

        // ── Time formatter ──
        function formatTimeStr(str) {
            if (!str) return '--:--';
            try {
                var d = new Date(str.replace(/-/g, '/'));
                if (isNaN(d.getTime())) return str.substring(11, 16);
                var h = d.getHours(), m = d.getMinutes();
                var ampm = h >= 12 ? 'PM' : 'AM';
                h = h % 12 || 12;
                return h + ':' + (m < 10 ? '0' : '') + m + ' ' + ampm;
            } catch (e) { return str.substring(11, 16); }
        }

        // ── Standby HTML (dfv2 version) ──
        function standbyHTML() {
            return '<div class="dfv2-standby">' +
                '<div class="dfv2-standby-inner">' +
                    '<div class="dfv2-standby-icon-ring">' +
                        '<div class="dfv2-standby-icon-ring2"></div>' +
                        '<span class="dfv2-standby-emoji">🚗</span>' +
                    '</div>' +
                    '<div class="dfv2-standby-badge"><span class="dfv2-dot-pulse"></span> Fleet Active &amp; On Standby</div>' +
                    '<h2 class="dfv2-standby-title">Certified Instructors &amp; Dual-Control Vehicles Ready</h2>' +
                    '<p class="dfv2-standby-desc">Today\'s driving sessions will appear here live in real-time as instructors activate in-car evaluations and dispatch onto the road.</p>' +
                    '<div class="dfv2-standby-tags">' +
                        '<span class="dfv2-stag">🛡️ Dual-Control Safety Certified</span>' +
                        '<span class="dfv2-stag">🏛️ Maryland MVA / COMAR 11.23</span>' +
                        '<span class="dfv2-stag">📍 Rockville, MD</span>' +
                        '<span class="dfv2-stag">✅ Fully Licensed &amp; Insured</span>' +
                    '</div>' +
                '</div>' +
            '</div>';
        }

        // ── State for Multi-View and Remote Control ──
        var currentViewMode   = config.initialViewMode || 'slots';
        var cachedSessions    = Array.isArray(config.initialSessions) ? config.initialSessions : [];
        var cachedVehicles    = Array.isArray(config.vehicles) ? config.vehicles : [];
        var cachedInstructors = Array.isArray(config.instructors) ? config.instructors : [];
        var lastReloadTs      = parseInt(config.initialReloadTs, 10) || 0;
        var renderedCards     = [];
        var carouselTimer     = null;
        var currentPage       = 0;
        var totalPages        = 1;

        // Broadcast banner elements
        var alertBannerEl  = board.querySelector('#dfv2-broadcast-banner');
        var alertTextEl    = board.querySelector('#dfv2-broadcast-text');
        var alertCloseBtn  = board.querySelector('#dfv2-broadcast-close');
        var lastAlertText  = '';

        if (alertCloseBtn && alertBannerEl) {
            alertCloseBtn.addEventListener('click', function () {
                alertBannerEl.style.display = 'none';
            });
        }

        // View switcher buttons in TV nav bar
        var viewBtns = board.querySelectorAll('[data-tv-view]');
        function updateViewSwitcherUI(mode) {
            viewBtns.forEach(function (btn) {
                btn.classList.toggle('is-active', btn.getAttribute('data-tv-view') === mode);
            });
        }

        viewBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var mode = btn.getAttribute('data-tv-view');
                if (mode && mode !== currentViewMode) {
                    currentViewMode = mode;
                    updateViewSwitcherUI(mode);
                    renderActiveView();
                }
            });
        });

        var refreshBtn = board.querySelector('[data-btn-refresh]');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function () {
                refreshBtn.style.transform = 'rotate(360deg)';
                refreshBtn.style.transition = 'transform 0.5s ease';
                refresh();
                setTimeout(function () {
                    refreshBtn.style.transform = 'none';
                    refreshBtn.style.transition = 'none';
                }, 600);
            });
        }

        // ── Live Broadcast Banner Controller ──
        function handleAlertBanner(alertData) {
            if (!alertBannerEl || !alertTextEl) return;
            if (alertData && alertData.text && alertData.text.trim()) {
                var text = alertData.text.trim();
                alertTextEl.textContent = text;
                alertBannerEl.classList.remove('is-warning', 'is-urgent', 'is-info');
                if (alertData.level) {
                    alertBannerEl.classList.add('is-' + alertData.level);
                } else {
                    alertBannerEl.classList.add('is-warning');
                }
                alertBannerEl.style.display = 'flex';

                if (text !== lastAlertText) {
                    lastAlertText = text;
                    if (soundEnabled) playChime();
                    if (speechEnabled) announceVoice('Attention: Notice from dispatch. ' + text);
                }
            } else {
                alertBannerEl.style.display = 'none';
                lastAlertText = '';
            }
        }

        // Initialize banner from config
        if (config.initialAlert) {
            handleAlertBanner(config.initialAlert);
        }

        // ── Multi-Message Announcement Ticker Controller ──
        function handleTicker(items) {
            var tickerContainer = board.querySelector('[data-ticker-content]');
            if (!tickerContainer) return;
            if (Array.isArray(items) && items.length) {
                var html = '';
                items.forEach(function (msg) {
                    var clean = (msg || '').trim();
                    if (clean) {
                        html += '<span class="dfv2-ticker-item"><span class="dfv2-ticker-bullet">◆</span> ' + clean + '</span>';
                    }
                });
                if (html) {
                    tickerContainer.innerHTML = html;
                }
            }
        }

        // How many cards fit per page
        function cardsPerPage() {
            var w = board.offsetWidth || window.innerWidth;
            if (w >= 1400) return 4;
            if (w >= 1050) return 3;
            if (w >= 700)  return 2;
            return 1;
        }

        function getPager() {
            var el = board.querySelector('.dfv2-pager');
            if (!el) {
                el = document.createElement('div');
                el.className = 'dfv2-pager';
                var wrap = board.querySelector('.dfv2-sessions-wrap');
                if (wrap) wrap.appendChild(el);
            }
            return el;
        }

        function stopCarousel() {
            if (carouselTimer) {
                window.clearInterval(carouselTimer);
                carouselTimer = null;
            }
        }

        function renderPage(cards, page) {
            var perPage = cardsPerPage();
            totalPages  = Math.ceil(cards.length / perPage);
            currentPage = Math.max(0, Math.min(page, totalPages - 1));

            var start   = currentPage * perPage;
            var pageSet = cards.slice(start, start + perPage);

            grid.style.opacity = '0';
            grid.style.transform = 'translateY(8px)';

            setTimeout(function () {
                grid.innerHTML = '';
                pageSet.forEach(function (cardEl) {
                    grid.appendChild(cardEl.cloneNode(true));
                });
                grid.style.transition = 'opacity 0.45s ease, transform 0.45s ease';
                grid.style.opacity  = '1';
                grid.style.transform = 'translateY(0)';
            }, 180);

            var pager = getPager();
            if (totalPages > 1) {
                var html = '<div class="dfv2-pager-inner">';
                html += '<span class="dfv2-page-label">Page ' + (currentPage + 1) + ' of ' + totalPages + '</span>';
                html += '<div class="dfv2-dots">';
                for (var i = 0; i < totalPages; i++) {
                    html += '<button class="dfv2-dot' + (i === currentPage ? ' is-active' : '') + '" data-page="' + i + '" aria-label="Page ' + (i+1) + '"></button>';
                }
                html += '</div>';
                html += '</div>';
                pager.innerHTML = html;
                pager.style.display = 'flex';

                pager.querySelectorAll('.dfv2-dot').forEach(function (dot) {
                    dot.addEventListener('click', function () {
                        stopCarousel();
                        renderPage(renderedCards, parseInt(dot.getAttribute('data-page'), 10));
                    });
                });
            } else {
                pager.style.display = 'none';
                pager.innerHTML = '';
            }
        }

        function startCarousel(cards) {
            stopCarousel();
            if (cards.length <= cardsPerPage()) return;
            carouselTimer = window.setInterval(function () {
                var next = (currentPage + 1) % totalPages;
                renderPage(renderedCards, next);
            }, 8500);
        }

        if (grid) {
            grid.addEventListener('mouseenter', stopCarousel);
            grid.addEventListener('mouseleave', function () {
                if (renderedCards.length > cardsPerPage()) startCarousel(renderedCards);
            });
        }

        // ── MODE 1: Standard 2-Hour Lesson Slots View ──
        function renderFuelGauge(fuel) {
            var p = typeof fuel === 'number' ? fuel : parseInt(fuel, 10);
            if (isNaN(p) || p <= 0) p = 75;
            p = Math.max(5, Math.min(100, Math.round(p)));
            var isLow = p <= 25;
            var fuelColor = isLow ? '#ff3b57' : (p <= 50 ? '#ffd60a' : '#22ff88');
            var lit = p > 0 ? Math.max(1, Math.round(p / 10)) : 0;
            var segs = '';
            for (var i = 0; i < 10; i++) {
                segs += '<i class="lb-fuel-seg' + (i < lit ? ' on' : '') + '"></i>';
            }
            return '<div class="lb-fuel' + (isLow ? ' low' : '') + '" style="--f:' + fuelColor + ';" role="meter" aria-label="Fuel level" aria-valuenow="' + p + '" title="Vehicle Fuel: ' + p + '%">' +
                '<div class="lb-fuel-top">' +
                    '<svg viewBox="0 0 24 24" class="lb-fuel-icon" fill="currentColor" width="14" height="14"><path d="M19.77 7.23l.01-.01-3.72-3.72L15 4.56l2.11 2.11c-.94.36-1.61 1.26-1.61 2.33 0 1.38 1.12 2.5 2.5 2.5.36 0 .69-.08 1-.21v7.21c0 .55-.45 1-1 1s-1-.45-1-1V14c0-1.1-.9-2-2-2h-1V5c0-1.1-.9-2-2-2H6c-1.1 0-2 .9-2 2v16h10v-7.5h1.5v5c0 1.38 1.12 2.5 2.5 2.5s2.5-1.12 2.5-2.5V9c0-.69-.28-1.32-.73-1.77zM12 10H6V5h6v5zm6 0c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1z"/></svg>' +
                    '<span class="lb-fuel-pct">' + p + '%</span>' +
                '</div>' +
                '<div class="lb-fuel-grid">' + segs + '</div>' +
                '<div class="lb-fuel-labels"><span>E</span><span>' + (isLow ? 'LOW FUEL' : 'FUEL') + '</span><span>F</span></div>' +
            '</div>';
        }

        function buildSlotCards(sessions) {
            var sorted = sessions.slice().sort(function (a, b) {
                var o = { active: 1, upcoming: 2, completed: 3 };
                var oa = o[a.status] || 4, ob = o[b.status] || 4;
                return oa !== ob ? oa - ob : (a.scheduled_start || '').localeCompare(b.scheduled_start || '');
            });

            var newActiveIds = {};
            var newlyActiveSession = null;

            var cards = sorted.map(function (s) {
                var isActive    = s.status === 'active';
                var isCompleted = s.status === 'completed';
                if (isActive) {
                    newActiveIds[s.id] = true;
                    if (!previousActiveIds[s.id]) newlyActiveSession = s;
                }

                var plate      = ((s.plate_number || '8WD 4931').toUpperCase().replace(/[^A-Z0-9\s\-]/g, '')) || '8WD 4931';
                var startTime  = formatTimeStr(s.scheduled_start);
                var endTime    = formatTimeStr(s.scheduled_end);
                var student    = s.student_name    || 'Enrolled Student';
                var instructor = s.instructor_name || 'Assigned Instructor';
                var topic      = s.lesson_topic    || 'Behind-The-Wheel Lesson';
                var lessonNum  = s.session_number  || 1;
                var cardMod    = isActive ? 'dfv2-card--active' : (isCompleted ? 'dfv2-card--completed' : 'dfv2-card--upcoming');
                var statusCls  = isActive ? 'status-active'    : (isCompleted ? 'status-completed'    : 'status-upcoming');
                var statusTxt  = isActive ? '● ACTIVE · ON ROAD' : (isCompleted ? '✓ COMPLETED' : '● UPCOMING NEXT');

                var card = document.createElement('article');
                card.className = 'dfv2-card ' + cardMod;
                card.innerHTML =
                    '<div class="dfv2-card-top">' +
                        '<div class="dfv2-card-top-row">' +
                            '<div class="maryland-plate" data-plate="' + plate + '" role="button" tabindex="0" title="Maryland Registration: ' + plate + '">' +
                                '<span class="plate-bolt bolt-tl"></span><span class="plate-bolt bolt-tr"></span>' +
                                '<span class="plate-number">' + plate + '</span>' +
                                '<span class="plate-bolt bolt-bl"></span><span class="plate-bolt bolt-br"></span>' +
                                '<span class="plate-shine"></span>' +
                            '</div>' +
                            renderFuelGauge(s.fuel) +
                        '</div>' +
                        '<div class="dfv2-status-badge ' + statusCls + '">' +
                            (isActive ? '<span class="dfv2-dot-pulse"></span>' : '') +
                            '<span>' + statusTxt + '</span>' +
                        '</div>' +
                    '</div>' +
                    '<div class="dfv2-card-mid">' +
                        '<div class="dfv2-card-cap-icon" aria-hidden="true">🎓</div>' +
                        '<h3 class="dfv2-student-name">' + student + '</h3>' +
                        '<div class="dfv2-pills-row">' +
                            '<span class="dfv2-pill dfv2-pill-instructor">' +
                                '👨‍🏫 ' + instructor +
                            '</span>' +
                            '<span class="dfv2-pill dfv2-pill-lesson">' +
                                '🚙 Lesson ' + lessonNum + ' \u00b7 ' + topic +
                            '</span>' +
                        '</div>' +
                    '</div>' +
                    '<div class="dfv2-card-time">' +
                        '<span class="dfv2-time-clock-icon">🕐</span>' +
                        '<span class="dfv2-time-text">' + startTime + ' \u2013 ' + endTime + '</span>' +
                    '</div>';

                return card;
            });

            if (newlyActiveSession) {
                if (soundEnabled) playChime();
                if (speechEnabled) announceVoice('Driving session started for ' + newlyActiveSession.student_name + ' with instructor ' + (newlyActiveSession.instructor_name || 'unassigned') + '.');
            }
            previousActiveIds = newActiveIds;

            return cards;
        }

        // ── MODE 2: By Fleet Vehicles View ──
        function buildVehicleCards(sessions, vehicles) {
            var vList = vehicles.length ? vehicles.slice() : [];
            if (!vList.length) {
                // Collect unique plates from sessions
                var platesSeen = {};
                sessions.forEach(function (s) {
                    if (s.plate_number && !platesSeen[s.plate_number]) {
                        platesSeen[s.plate_number] = true;
                        vList.push({ plate_number: s.plate_number, model: 'Dual-Control Training Car' });
                    }
                });
            }
            if (!vList.length) {
                vList = [
                    { plate_number: '8WD 4931', model: 'Honda Civic (Automatic)' },
                    { plate_number: 'BAY-7892', model: 'Toyota Corolla (Automatic)' },
                    { plate_number: 'MD-CRAB-01', model: 'Honda Civic (Manual)' }
                ];
            }

            return vList.map(function (veh) {
                var plate = (veh.plate_number || '8WD 4931').toUpperCase();
                // Find matching active or upcoming session
                var activeSess = sessions.find(function (s) { return s.plate_number && s.plate_number.toUpperCase() === plate && s.status === 'active'; });
                var upcomingSess = sessions.find(function (s) { return s.plate_number && s.plate_number.toUpperCase() === plate && s.status === 'upcoming'; });
                var completedSess = sessions.find(function (s) { return s.plate_number && s.plate_number.toUpperCase() === plate && s.status === 'completed'; });

                var curr = activeSess || upcomingSess || completedSess;
                var isActive    = !!activeSess;
                var isUpcoming  = !isActive && !!upcomingSess;
                var isCompleted = !isActive && !isUpcoming && !!completedSess;
                var isStandby   = !curr;

                var cardMod   = isActive ? 'dfv2-card--active' : (isUpcoming ? 'dfv2-card--upcoming' : 'dfv2-card--completed');
                var statusCls = isActive ? 'status-active' : (isUpcoming ? 'status-upcoming' : (isStandby ? 'status-upcoming' : 'status-completed'));
                var statusTxt = isActive ? '● ON ROAD · ACTIVE' : (isUpcoming ? '● NEXT DISPATCH' : (isStandby ? '✓ STANDBY LOT' : '✓ COMPLETED'));

                var student    = curr ? (curr.student_name || 'Enrolled Student') : 'Fleet Car Ready';
                var instructor = curr ? (curr.instructor_name || 'Assigned Instructor') : 'Available for Booking';
                var topic      = curr ? ('Lesson ' + (curr.session_number || 1) + ' \u00b7 ' + (curr.lesson_topic || 'Practice')) : (veh.model || 'Dual-Control Certified');
                var timeText   = curr ? (formatTimeStr(curr.scheduled_start) + ' \u2013 ' + formatTimeStr(curr.scheduled_end)) : 'Open 2-Hour Slot';

                var fuelVal = (curr && curr.fuel) ? curr.fuel : (veh.current_fuel_level || 75);

                var card = document.createElement('article');
                card.className = 'dfv2-card ' + cardMod;
                card.innerHTML =
                    '<div class="dfv2-card-top">' +
                        '<div class="dfv2-card-top-row">' +
                            '<div class="maryland-plate" data-plate="' + plate + '" role="button" tabindex="0" title="Maryland Plate: ' + plate + '">' +
                                '<span class="plate-bolt bolt-tl"></span><span class="plate-bolt bolt-tr"></span>' +
                                '<span class="plate-number">' + plate + '</span>' +
                                '<span class="plate-bolt bolt-bl"></span><span class="plate-bolt bolt-br"></span>' +
                                '<span class="plate-shine"></span>' +
                            '</div>' +
                            renderFuelGauge(fuelVal) +
                        '</div>' +
                        '<div class="dfv2-status-badge ' + statusCls + '">' +
                            (isActive ? '<span class="dfv2-dot-pulse"></span>' : '') +
                            '<span>' + statusTxt + '</span>' +
                        '</div>' +
                    '</div>' +
                    '<div class="dfv2-card-mid">' +
                        '<div class="dfv2-card-cap-icon" aria-hidden="true">🚘</div>' +
                        '<h3 class="dfv2-student-name">' + student + '</h3>' +
                        '<div class="dfv2-pills-row">' +
                            '<span class="dfv2-pill dfv2-pill-instructor">👨‍🏫 ' + instructor + '</span>' +
                            '<span class="dfv2-pill dfv2-pill-lesson">🚙 ' + topic + '</span>' +
                        '</div>' +
                    '</div>' +
                    '<div class="dfv2-card-time">' +
                        '<span class="dfv2-time-clock-icon">🕐</span>' +
                        '<span class="dfv2-time-text">' + timeText + '</span>' +
                    '</div>';

                return card;
            });
        }

        // ── MODE 3: By Instructors View ──
        function buildInstructorCards(sessions, instructors) {
            var insList = instructors.length ? instructors.slice() : [];
            if (!insList.length) {
                var insSeen = {};
                sessions.forEach(function (s) {
                    if (s.instructor_name && !insSeen[s.instructor_name]) {
                        insSeen[s.instructor_name] = true;
                        insList.push({ name: s.instructor_name });
                    }
                });
            }
            if (!insList.length) {
                insList = [
                    { name: 'Mr. Anderson' },
                    { name: 'Ms. Rivera' },
                    { name: 'Mr. Thompson' }
                ];
            }

            return insList.map(function (ins) {
                var insName = ins.name || 'Instructor';
                var activeSess = sessions.find(function (s) { return s.instructor_name === insName && s.status === 'active'; });
                var upcomingSess = sessions.find(function (s) { return s.instructor_name === insName && s.status === 'upcoming'; });
                var completedSess = sessions.find(function (s) { return s.instructor_name === insName && s.status === 'completed'; });

                var curr = activeSess || upcomingSess || completedSess;
                var isActive    = !!activeSess;
                var isUpcoming  = !isActive && !!upcomingSess;
                var isCompleted = !isActive && !isUpcoming && !!completedSess;
                var isStandby   = !curr;

                var cardMod   = isActive ? 'dfv2-card--active' : (isUpcoming ? 'dfv2-card--upcoming' : 'dfv2-card--completed');
                var statusCls = isActive ? 'status-active' : (isUpcoming ? 'status-upcoming' : (isStandby ? 'status-upcoming' : 'status-completed'));
                var statusTxt = isActive ? '● IN-CAR LESSON' : (isUpcoming ? '● NEXT STUDENT' : (isStandby ? '✓ ON STANDBY' : '✓ COMPLETED'));

                var student  = curr ? (curr.student_name || 'Enrolled Student') : 'Available for Booking';
                var plate    = curr ? (curr.plate_number || '8WD 4931') : 'Fleet Car Assigned';
                var topic    = curr ? ('Lesson ' + (curr.session_number || 1) + ' \u00b7 ' + (curr.lesson_topic || 'Instruction')) : 'Certified MVA Instructor';
                var timeText = curr ? (formatTimeStr(curr.scheduled_start) + ' \u2013 ' + formatTimeStr(curr.scheduled_end)) : 'Open Operating Slot';
                var fuelVal  = (curr && curr.fuel) ? curr.fuel : 75;

                var card = document.createElement('article');
                card.className = 'dfv2-card ' + cardMod;
                card.innerHTML =
                    '<div class="dfv2-card-top">' +
                        '<div class="dfv2-card-top-row">' +
                            '<div class="maryland-plate" data-plate="' + plate + '" role="button" tabindex="0" title="Maryland Registration: ' + plate + '">' +
                                '<span class="plate-bolt bolt-tl"></span><span class="plate-bolt bolt-tr"></span>' +
                                '<span class="plate-number">' + plate + '</span>' +
                                '<span class="plate-bolt bolt-bl"></span><span class="plate-bolt bolt-br"></span>' +
                                '<span class="plate-shine"></span>' +
                            '</div>' +
                            renderFuelGauge(fuelVal) +
                        '</div>' +
                        '<div class="dfv2-status-badge ' + statusCls + '">' +
                            (isActive ? '<span class="dfv2-dot-pulse"></span>' : '') +
                            '<span>' + statusTxt + '</span>' +
                        '</div>' +
                    '</div>' +
                    '<div class="dfv2-card-mid">' +
                        '<div class="dfv2-card-cap-icon" aria-hidden="true">👨‍🏫</div>' +
                        '<h3 class="dfv2-student-name">' + insName + '</h3>' +
                        '<div class="dfv2-pills-row">' +
                            '<span class="dfv2-pill dfv2-pill-instructor">🎓 ' + student + '</span>' +
                            '<span class="dfv2-pill dfv2-pill-lesson">🚙 ' + topic + '</span>' +
                        '</div>' +
                    '</div>' +
                    '<div class="dfv2-card-time">' +
                        '<span class="dfv2-time-clock-icon">🕐</span>' +
                        '<span class="dfv2-time-text">' + timeText + '</span>' +
                    '</div>';

                return card;
            });
        }

        // ── Main View Dispatcher ──
        function renderActiveView() {
            if (message) message.style.display = 'none';
            stopCarousel();

            if (counterEl) {
                counterEl.textContent = (cachedSessions && cachedSessions.length) ? cachedSessions.length : '0';
            }

            if (currentViewMode === 'vehicles') {
                renderedCards = buildVehicleCards(cachedSessions, cachedVehicles);
            } else if (currentViewMode === 'instructors') {
                renderedCards = buildInstructorCards(cachedSessions, cachedInstructors);
            } else {
                renderedCards = buildSlotCards(cachedSessions);
            }

            if (!renderedCards.length) {
                grid.innerHTML = standbyHTML();
                var pager = getPager();
                pager.style.display = 'none';
                return;
            }

            currentPage = 0;
            renderPage(renderedCards, 0);
            startCarousel(renderedCards);
        }

        // Backward compatibility render alias
        function render(sessions) {
            if (Array.isArray(sessions)) {
                cachedSessions = sessions;
            }
            renderActiveView();
        }

        // ── AJAX Polling Refresh ──
        function refresh() {
            var ajaxUrl  = config.ajaxurl || '/wp-admin/admin-ajax.php';
            var fetchUrl = ajaxUrl + '?action=driveflow_get_tv_sessions';

            fetch(fetchUrl, { method: 'GET', headers: { 'Accept': 'application/json' } })
            .then(function (res) { return res.json(); })
            .then(function (res) {
                if (res && res.success && res.data) {
                    var data = res.data;

                    // 1. Check for remote reload command
                    if (data.reload_ts && data.reload_ts > lastReloadTs && lastReloadTs > 0) {
                        window.location.reload();
                        return;
                    }
                    if (data.reload_ts && (!lastReloadTs || data.reload_ts > lastReloadTs)) {
                        lastReloadTs = data.reload_ts;
                    }

                    // 2. Process remote broadcast alert banner
                    handleAlertBanner(data.alert);

                    // 3. Process remote view mode switch
                    if (data.view_mode && data.view_mode !== currentViewMode) {
                        currentViewMode = data.view_mode;
                        updateViewSwitcherUI(currentViewMode);
                    }

                    // 4. Update multi-message ticker
                    if (data.ticker_items) {
                        handleTicker(data.ticker_items);
                    }

                    // 5. Update cached entities and render
                    if (Array.isArray(data.vehicles)) cachedVehicles = data.vehicles;
                    if (Array.isArray(data.instructors)) cachedInstructors = data.instructors;
                    if (Array.isArray(data.sessions)) {
                        cachedSessions = data.sessions;
                    }

                    renderActiveView();
                } else if (Array.isArray(res)) {
                    cachedSessions = res;
                    renderActiveView();
                } else {
                    fallbackRest();
                }
            })
            .catch(fallbackRest);

            function fallbackRest() {
                if (!config.tvApiUrl) return;
                fetch(config.tvApiUrl + (config.tvApiUrl.indexOf('?') >= 0 ? '&' : '?') + 'limit=50', {
                    headers: { 'X-DriveFlow-API-Key': config.tvApiKey || '', 'Accept': 'application/json' }
                })
                .then(function (res) { return res.json(); })
                .then(function (body) {
                    if (Array.isArray(body)) {
                        cachedSessions = body;
                        renderActiveView();
                    }
                })
                .catch(function () {});
            }
        }

        tick();
        updateViewSwitcherUI(currentViewMode);

        if (config.tickerItems) {
            handleTicker(config.tickerItems);
        }

        if (config.initialSessions && Array.isArray(config.initialSessions) && config.initialSessions.length) {
            cachedSessions = config.initialSessions;
            renderActiveView();
        } else {
            refresh();
        }

        window.setInterval(tick, 1000);
        window.setInterval(refresh, (Number(config.pollSeconds) || 12) * 1000);

        window.addEventListener('resize', function () {
            if (renderedCards.length > 0) {
                stopCarousel();
                currentPage = 0;
                renderPage(renderedCards, 0);
                startCarousel(renderedCards);
            }
        });
    }

    document.querySelectorAll('[data-session-form]').forEach(initForm);
    document.querySelectorAll('[data-driveflow-board]').forEach(initBoard);

    // Global Maryland Plate Showcase Modal for Frontend
    document.addEventListener('click', function (e) {
        var plateEl = e.target.closest('.maryland-plate');
        if (!plateEl) return;
        var plateNum = plateEl.getAttribute('data-plate') || (plateEl.querySelector('.plate-number') ? plateEl.querySelector('.plate-number').textContent.trim() : '');
        if (!plateNum || plateNum === '—') return;

        var existing = document.getElementById('df-frontend-plate-modal');
        if (!existing) {
            var modalDiv = document.createElement('div');
            modalDiv.id = 'df-frontend-plate-modal';
            modalDiv.className = 'df-modal-backdrop';
            modalDiv.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.8);z-index:99999;display:none;align-items:center;justify-content:center;padding:16px;';
            modalDiv.innerHTML =
                '<div style="background:#fff;border-radius:16px;max-width:540px;width:100%;box-shadow:0 25px 50px -12px rgba(0,0,0,0.35);overflow:hidden;font-family:sans-serif;direction:ltr;text-align:left;">' +
                    '<div style="display:flex;justify-content:space-between;align-items:center;padding:16px 20px;border-bottom:1px solid #e2e8f0;background:#f8fafc;">' +
                        '<h3 style="margin:0;font-size:16px;color:#0f172a;font-weight:700;">Maryland Vehicle Registration Showcase</h3>' +
                        '<button type="button" id="df-close-fe-modal" style="background:none;border:none;font-size:24px;cursor:pointer;color:#64748b;line-height:1;">&times;</button>' +
                    '</div>' +
                    '<div style="padding:24px;text-align:center;">' +
                        '<div class="df-plate-showcase-box" style="margin-bottom:18px;">' +
                            '<div class="maryland-plate plate-xl" id="df-fe-modal-plate">' +
                                '<span class="plate-bolt bolt-tl"></span><span class="plate-bolt bolt-tr"></span>' +
                                '<span class="plate-number" id="df-fe-modal-num"></span>' +
                                '<span class="plate-bolt bolt-bl"></span><span class="plate-bolt bolt-br"></span>' +
                                '<span class="plate-shine"></span>' +
                            '</div>' +
                            '<div class="df-plate-sticker-tag">DEC 26</div>' +
                        '</div>' +
                        '<div class="df-plate-info-grid" style="font-size:13px;margin-bottom:0;">' +
                            '<div class="df-plate-info-item"><label>Issuing State</label><span>State of Maryland (MVA)</span></div>' +
                            '<div class="df-plate-info-item"><label>Registration Class</label><span>Commercial Driver Training</span></div>' +
                            '<div class="df-plate-info-item"><label>Dual Brakes</label><span style="color:#16a34a;font-weight:700;">Certified & Verified</span></div>' +
                            '<div class="df-plate-info-item"><label>Active Status</label><span style="color:#16a34a;font-weight:700;">On Duty / In-Service</span></div>' +
                        '</div>' +
                    '</div>' +
                    '<div style="padding:14px 20px;background:#f8fafc;border-top:1px solid #e2e8f0;display:flex;justify-content:flex-end;">' +
                        '<button type="button" id="df-done-fe-modal" style="background:#0f766e;color:#fff;border:none;padding:8px 18px;border-radius:8px;font-weight:600;cursor:pointer;">Close</button>' +
                    '</div>' +
                '</div>';
            document.body.appendChild(modalDiv);
            existing = modalDiv;

            document.getElementById('df-close-fe-modal').addEventListener('click', function() { existing.style.display = 'none'; });
            document.getElementById('df-done-fe-modal').addEventListener('click', function() { existing.style.display = 'none'; });
            existing.addEventListener('click', function(ev) { if (ev.target === existing) existing.style.display = 'none'; });
        }

        document.getElementById('df-fe-modal-num').textContent = plateNum;
        existing.style.display = 'flex';
    });
})();
