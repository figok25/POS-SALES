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
    .brk-err{color:#b91c1c;font-size:12px;margin-top:8px}
</style>
<section class="sls-card sls-card-pad brk-card" id="brk-card" hidden>
    <p class="sls-card-title" id="brk-title">Istirahat</p>
    <p class="brk-note" id="brk-note">Tekan saat berhenti untuk makan atau ibadah supaya tidak dianggap diam.</p>
    <button type="button" class="brk-btn" id="brk-btn">☕ Mulai Istirahat</button>
    <p class="brk-err" id="brk-err" hidden></p>
</section>
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

        function csrf() { var m = document.querySelector('meta[name="csrf-token"]'); return m ? m.content : ''; }

        function render(s) {
            card.hidden = !s.tracking_active;
            onBreak = !!s.on_break;
            card.classList.toggle('is-on', onBreak);
            btn.classList.toggle('is-end', onBreak);
            if (onBreak) {
                var since = s.started_at ? new Date(s.started_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) : '';
                title.textContent = 'Sedang Istirahat';
                note.textContent = 'Sejak ' + since + ' (' + (s.minutes || 0) + ' menit, maks. ' + (s.max_minutes || 30) + ' menit). Tekan saat sudah siap bekerja lagi.';
                btn.textContent = '✅ Selesai Istirahat';
            } else {
                title.textContent = 'Istirahat';
                note.textContent = 'Tekan saat berhenti untuk makan atau ibadah supaya tidak dianggap diam (maks. ' + (s.max_minutes || 30) + ' menit, lalu otomatis selesai).';
                btn.textContent = '☕ Mulai Istirahat';
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

        btn.addEventListener('click', function () {
            if (busy) return;
            busy = true; btn.disabled = true; err.hidden = true;
            call('POST', onBreak ? '/api/sales/break/end' : '/api/sales/break/start').then(function (r) {
                if (!r.ok) { err.textContent = r.json.message || 'Gagal. Coba lagi.'; err.hidden = false; }
                if (r.json) render(r.json);
            }).catch(function () {
                err.textContent = 'Tidak ada koneksi. Coba lagi.'; err.hidden = false;
            }).then(function () { busy = false; btn.disabled = false; });
        });

        refresh();
        setInterval(refresh, 30000);
        document.addEventListener('visibilitychange', function () { if (!document.hidden) refresh(); });
    })();
</script>
