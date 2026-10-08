{{--
    Tombol Istirahat Sales. Muncul hanya saat Sales sedang bertugas (tracking aktif).
    Selama istirahat Sales tidak dihitung "diam" di Live Monitoring. Istirahat berakhir
    saat menekan "Selesai Istirahat", saat Check-in kunjungan, atau saat Return Stock.
--}}
<style>
    .brk-note{font-size:12px;color:#6b7280;margin:2px 0 10px}
    .brk-btn{width:100%;border:0;border-radius:10px;padding:12px;font-size:14px;font-weight:800;color:#fff;background:#4f46e5}
    .brk-btn.is-end{background:#059669}
    .brk-btn[disabled]{opacity:.6}
    .brk-card.is-on{border:2px solid #f59e0b;background:#fffbeb}
    .brk-card.is-over{border-color:#dc2626;background:#fef2f2}
    .brk-card.is-over .sls-card-title{color:#b91c1c}
    .brk-ov{position:fixed;inset:0;z-index:99999;display:none;flex-direction:column;align-items:center;justify-content:center;gap:14px;padding:24px;text-align:center;background:#b91c1c;color:#fff}
    .brk-ov.is-show{display:flex;animation:brkflash 1s steps(2,jump-none) infinite}
    .brk-ov h2{font-size:26px;font-weight:900;margin:0}
    .brk-ov p{font-size:15px;margin:0;max-width:320px}
    .brk-ov .brk-cd{font-size:72px;font-weight:900;line-height:1}
    .brk-ov button{margin-top:10px;border:0;border-radius:12px;padding:16px 28px;font-size:17px;font-weight:900;background:#fff;color:#b91c1c}
    .brk-warn{background:#fef3c7;border:1px solid #f59e0b;color:#92400e;border-radius:10px;padding:8px 10px;font-size:12px;font-weight:700;margin-bottom:10px}
    @keyframes brkflash{0%{background:#b91c1c}100%{background:#7f1d1d}}
    .brk-err{color:#b91c1c;font-size:12px;margin-top:8px}
</style>
<section class="sls-card sls-card-pad brk-card" id="brk-card" hidden>
    <p class="sls-card-title" id="brk-title">Istirahat</p>
    <p class="brk-note" id="brk-note">Tekan saat berhenti untuk makan atau ibadah supaya tidak dianggap diam.</p>
    <button type="button" class="brk-btn" id="brk-btn">☕ Mulai Istirahat</button>
    <p class="brk-warn" id="brk-warn" hidden>⏳ Sisa kuota istirahat kurang dari 2 menit.</p>
    <p class="brk-err" id="brk-err" hidden></p>
</section>
<div class="brk-ov" id="brk-ov" role="alertdialog" aria-live="assertive">
    <h2>⚠️ KUOTA ISTIRAHAT HABIS</h2>
    <p>Segera kembali bekerja. Istirahat akan selesai otomatis dan tercatat di Live Monitoring.</p>
    <div class="brk-cd" id="brk-cd">0</div>
    <p>detik lagi</p>
    <button type="button" id="brk-ov-btn">✅ Saya Kembali Bekerja</button>
</div>
<script>
    (function () {
        var card = document.getElementById('brk-card');
        if (!card) return;
        var btn = document.getElementById('brk-btn');
        var note = document.getElementById('brk-note');
        var title = document.getElementById('brk-title');
        var err = document.getElementById('brk-err');
        var onBreak = false;
        var busy = false;
        var ov = document.getElementById('brk-ov');
        var ovBtn = document.getElementById('brk-ov-btn');
        var cd = document.getElementById('brk-cd');
        var warn = document.getElementById('brk-warn');
        var graceLeft = 0, tickTimer = null, alertTimer = null, warned = false, lastState = null;
        var audioCtx = null;

        function unlockAudio() {
            try {
                var AC = window.AudioContext || window.webkitAudioContext;
                if (!AC) return;
                if (!audioCtx) audioCtx = new AC();
                if (audioCtx.state === 'suspended') audioCtx.resume();
            } catch (e) {}
        }

        function beep(freq, ms) {
            try {
                if (!audioCtx) return;
                var o = audioCtx.createOscillator(), g = audioCtx.createGain();
                o.type = 'square'; o.frequency.value = freq; g.gain.value = 0.25;
                o.connect(g); g.connect(audioCtx.destination);
                o.start(); o.stop(audioCtx.currentTime + ms / 1000);
            } catch (e) {}
        }

        function buzz(pattern) {
            try { if (navigator.vibrate) navigator.vibrate(pattern); } catch (e) {}
        }

        function pulse() {
            buzz([500, 150, 500, 150, 500]);
            beep(880, 300);
            setTimeout(function () { beep(660, 300); }, 400);
        }

        function stopAlert() {
            ov.classList.remove('is-show');
            if (alertTimer) { clearInterval(alertTimer); alertTimer = null; }
            if (tickTimer) { clearInterval(tickTimer); tickTimer = null; }
            buzz(0);
        }

        function startAlert(seconds) {
            graceLeft = seconds;
            cd.textContent = Math.max(0, graceLeft);
            if (ov.classList.contains('is-show')) return;
            ov.classList.add('is-show');
            pulse();
            alertTimer = setInterval(pulse, 2500);
            tickTimer = setInterval(function () {
                graceLeft = Math.max(0, graceLeft - 1);
                cd.textContent = graceLeft;
                if (graceLeft <= 0) { setTimeout(refresh, 1200); }
            }, 1000);
        }

        function csrf() { var m = document.querySelector('meta[name="csrf-token"]'); return m ? m.content : ''; }

        function render(s) {
            card.hidden = !s.tracking_active;
            onBreak = !!s.on_break;
            if (onBreak && s.overdue) {
                startAlert(s.grace_left_seconds || 0);
            } else {
                stopAlert();
                if (lastState && lastState.on_break && lastState.overdue && !onBreak) {
                    buzz([300, 100, 300]);
                }
            }
            var low = onBreak && !s.overdue && (s.remaining_seconds || 0) <= 120;
            warn.hidden = !low;
            if (low && !warned) { warned = true; buzz([400, 150, 400]); beep(740, 250); }
            if (!onBreak) warned = false;
            lastState = s;
            card.classList.toggle('is-on', onBreak);
            card.classList.toggle('is-over', onBreak && !!s.overdue);
            btn.classList.toggle('is-end', onBreak);
            var left = (s.remaining_minutes || 0);
            if (onBreak) {
                var since = s.started_at ? new Date(s.started_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) : '';
                if (s.overdue) {
                    title.textContent = '⚠️ Kuota Istirahat Habis';
                    note.textContent = 'Segera kembali bekerja. Istirahat akan selesai otomatis dalam ' + (s.grace_left_seconds || 0) + ' detik.';
                } else {
                    title.textContent = 'Sedang Istirahat';
                    note.textContent = 'Sejak ' + since + ' (' + (s.minutes || 0) + ' menit). Sisa kuota ' + left + ' menit.';
                }
                btn.textContent = '✅ Selesai Istirahat';
                btn.disabled = false;
            } else {
                title.textContent = 'Istirahat';
                if (!s.in_window) {
                    note.textContent = 'Istirahat hanya bisa dimulai pukul ' + (s.window || '') + '.';
                } else if ((s.remaining_seconds || 0) < 60) {
                    note.textContent = 'Kuota istirahat hari ini sudah habis.';
                } else {
                    note.textContent = 'Sisa kuota istirahat hari ini ' + left + ' menit (dapat dipecah), pukul ' + (s.window || '') + '.';
                }
                btn.textContent = '☕ Mulai Istirahat';
                btn.disabled = !s.can_start;
            }
        }

        function call(method, url) {
            return fetch(url, {
                method: method,
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf(), 'Content-Type': 'application/json' },
            }).then(function (res) { return res.json().then(function (j) { return { ok: res.ok, json: j }; }); });
        }

        function refresh() {
            call('GET', '/api/sales/break/status').then(function (r) { if (r.ok) render(r.json); }).catch(function () {});
        }

        ovBtn.addEventListener('click', function () {
            call('POST', '/api/sales/break/end').then(function (r) { if (r.json) render(r.json); }).catch(function () {});
        });

        btn.addEventListener('click', function () {
            unlockAudio();
            if (busy) return;
            busy = true; btn.disabled = true; err.hidden = true;
            call('POST', onBreak ? '/api/sales/break/end' : '/api/sales/break/start').then(function (r) {
                if (!r.ok) { err.textContent = r.json.message || 'Gagal. Coba lagi.'; err.hidden = false; }
                if (r.json) render(r.json);
            }).catch(function () {
                err.textContent = 'Tidak ada koneksi. Coba lagi.'; err.hidden = false;
            }).then(function () { busy = false; refresh(); });
        });

        refresh();
        setInterval(function () { if (onBreak) refresh(); }, 5000);
        setInterval(refresh, 15000);
        document.addEventListener('visibilitychange', function () { if (!document.hidden) refresh(); });
    })();
</script>
