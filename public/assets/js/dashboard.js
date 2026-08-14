/* =====================================================================
   DIGI-LIBRARY — Skrip Panel Admin (Vanilla JS, tanpa jQuery)
   ---------------------------------------------------------------------
   Isi file:
     1. Buka/tutup sidebar di layar kecil
     2. Sembunyikan flash message otomatis
     3. Konfirmasi sebelum menghapus data
     4. Global Search di topbar (AJAX + dropdown glassmorphism)
     5. Inisialisasi grafik "Ringkasan Peminjaman Bulanan" (Chart.js)
   ===================================================================== */

document.addEventListener('DOMContentLoaded', function () {

    /* ----------------------------------------------------------------
       1. SIDEBAR RESPONSIF
       Di layar < 992px sidebar berada di luar layar; tombol hamburger
       menambahkan kelas .show, dan backdrop menutupnya kembali.
       ---------------------------------------------------------------- */
    const sidebar  = document.getElementById('dlSidebar');
    const toggle   = document.getElementById('dlSidebarToggle');
    const backdrop = document.getElementById('dlBackdrop');

    function tutupSidebar() {
        sidebar?.classList.remove('show');
        backdrop?.classList.remove('show');
    }

    toggle?.addEventListener('click', function () {
        sidebar?.classList.toggle('show');
        backdrop?.classList.toggle('show');
    });

    backdrop?.addEventListener('click', tutupSidebar);

    // Tombol Esc juga menutup sidebar
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') tutupSidebar();
    });

    /* ----------------------------------------------------------------
       2. FLASH MESSAGE OTOMATIS HILANG (5 detik)
       ---------------------------------------------------------------- */
    document.querySelectorAll('.dl-alert').forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity .4s ease';
            el.style.opacity = '0';
            setTimeout(function () { el.remove(); }, 400);
        }, 5000);
    });

    /* ----------------------------------------------------------------
       3. KONFIRMASI HAPUS
       Dipasang pada form yang diberi atribut data-confirm="pesan".
       Mencegah data terhapus karena salah klik.
       ---------------------------------------------------------------- */
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!window.confirm(form.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });

    /* ----------------------------------------------------------------
       4. GLOBAL SEARCH (topbar)
       Mengetik di kotak pencarian memanggil endpoint /search lewat AJAX,
       lalu hasilnya dirender sebagai dropdown melayang.

       Blok ini sengaja diletakkan SEBELUM bagian grafik, karena bagian
       grafik diakhiri `return` bila halaman tidak punya kanvas Chart.js.
       ---------------------------------------------------------------- */
    (function () {
        const input   = document.getElementById('dlSearchInput');
        const kotak   = document.getElementById('search-results');
        const spinner = document.getElementById('dlSearchSpinner');
        const wrap    = document.getElementById('dlGlobalSearch');
        if (!input || !kotak) return;

        const MIN_HURUF = 2;     // sama dengan MIN_KEYWORD di Search.php
        const JEDA_MS   = 300;   // debounce: tunggu user berhenti mengetik

        let timer      = null;   // penampung setTimeout debounce
        let pengendali = null;   // AbortController permintaan terakhir
        let indeks     = -1;     // baris yang sedang disorot panah keyboard

        /** Semua <a> hasil pencarian yang sedang tampil. */
        function daftarItem() {
            return Array.prototype.slice.call(kotak.querySelectorAll('.dl-search-item'));
        }

        function buka() {
            kotak.classList.add('show');
            input.setAttribute('aria-expanded', 'true');
        }

        function tutup() {
            kotak.classList.remove('show');
            input.setAttribute('aria-expanded', 'false');
            indeks = -1;
        }

        /** Loloskan karakter khusus agar aman dipakai di dalam RegExp. */
        function amankanRegex(teks) {
            return teks.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }

        /**
         * Bungkus potongan teks yang cocok dengan keyword memakai <mark>.
         * Dibangun dengan createTextNode (bukan innerHTML), jadi data dari
         * database tidak mungkin dieksekusi sebagai HTML/skrip => aman XSS.
         */
        function sorot(teks, kataKunci) {
            const frag = document.createDocumentFragment();
            teks = String(teks == null ? '' : teks);

            if (!kataKunci.length) {
                frag.appendChild(document.createTextNode(teks));
                return frag;
            }

            const pola = new RegExp('(' + kataKunci.map(amankanRegex).join('|') + ')', 'gi');
            let posisi = 0;
            let cocok;

            while ((cocok = pola.exec(teks)) !== null) {
                if (cocok.index > posisi) {
                    frag.appendChild(document.createTextNode(teks.slice(posisi, cocok.index)));
                }
                const tanda = document.createElement('mark');
                tanda.textContent = cocok[0];
                frag.appendChild(tanda);
                posisi = cocok.index + cocok[0].length;
                if (cocok[0].length === 0) pola.lastIndex++;   // jaga-jaga dari loop tak berujung
            }

            if (posisi < teks.length) {
                frag.appendChild(document.createTextNode(teks.slice(posisi)));
            }
            return frag;
        }

        /** Bangun satu baris hasil sebagai elemen <a>. */
        function buatItem(item, ikon, kataKunci) {
            const a = document.createElement('a');
            a.className = 'dl-search-item';
            a.href = item.url;
            a.setAttribute('role', 'option');

            const kotakIkon = document.createElement('span');
            kotakIkon.className = 'dl-search-item__icon';
            kotakIkon.innerHTML = '<i class="bi ' + (ikon === 'bi-people' ? 'bi-person' : 'bi-book') + '"></i>';

            const badan = document.createElement('span');
            badan.className = 'dl-search-item__body';

            const judul = document.createElement('span');
            judul.className = 'dl-search-item__title';
            judul.appendChild(sorot(item.title, kataKunci));

            const sub = document.createElement('span');
            sub.className = 'dl-search-item__sub';
            sub.appendChild(sorot((item.meta || '') + (item.subtitle ? ' · ' + item.subtitle : ''), kataKunci));

            badan.appendChild(judul);
            badan.appendChild(sub);

            a.appendChild(kotakIkon);
            a.appendChild(badan);

            if (item.badge) {
                const badge = document.createElement('span');
                badge.className = 'dl-badge ' + (item.badgeClass || 'dl-badge--gray');
                badge.textContent = item.badge;   // textContent => aman dari XSS
                a.appendChild(badge);
            }

            return a;
        }

        /** Render seluruh balasan server ke dalam kotak hasil. */
        function render(data) {
            kotak.textContent = '';   // kosongkan hasil sebelumnya
            indeks = -1;

            const kataKunci = String(data.keyword || '').split(/\s+/).filter(Boolean);

            if (!data.total) {
                const kosong = document.createElement('div');
                kosong.className = 'dl-search-empty';
                kosong.innerHTML = '<i class="bi bi-search d-block mb-2" style="font-size:20px"></i>';
                kosong.appendChild(document.createTextNode('Tidak ada hasil untuk "' + data.keyword + '"'));
                kotak.appendChild(kosong);
                buka();
                return;
            }

            data.groups.forEach(function (grup) {
                const judulGrup = document.createElement('div');
                judulGrup.className = 'dl-search-group';

                const label = document.createElement('span');
                label.textContent = grup.label;
                judulGrup.appendChild(label);

                const semua = document.createElement('a');
                semua.href = grup.url;
                semua.textContent = 'Lihat semua';
                judulGrup.appendChild(semua);

                kotak.appendChild(judulGrup);

                grup.items.forEach(function (item) {
                    kotak.appendChild(buatItem(item, grup.icon, kataKunci));
                });
            });

            const kaki = document.createElement('div');
            kaki.className = 'dl-search-foot';
            kaki.innerHTML = '<span><kbd>&uarr;</kbd><kbd>&darr;</kbd> pilih &middot; <kbd>Enter</kbd> buka</span>'
                           + '<span><kbd>Esc</kbd> tutup</span>';
            kotak.appendChild(kaki);

            buka();
        }

        /** Panggil endpoint pencarian. */
        function cari(keyword) {
            // Batalkan permintaan sebelumnya supaya balasan lama tidak
            // menimpa hasil ketikan terbaru (race condition).
            if (pengendali) pengendali.abort();
            pengendali = new AbortController();

            if (spinner) spinner.hidden = false;

            fetch(input.dataset.url + '?q=' + encodeURIComponent(keyword), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                signal: pengendali.signal
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (spinner) spinner.hidden = true;
                    render(data);
                })
                .catch(function (err) {
                    if (err.name === 'AbortError') return;   // wajar saat mengetik cepat
                    if (spinner) spinner.hidden = true;
                    console.error('Global search gagal:', err);
                });
        }

        /* ---- Event: mengetik (dengan debounce) ---- */
        input.addEventListener('keyup', function (e) {
            // Tombol navigasi ditangani keydown, jangan picu pencarian
            if (['ArrowDown', 'ArrowUp', 'Enter', 'Escape'].indexOf(e.key) !== -1) return;

            const keyword = input.value.trim();
            clearTimeout(timer);

            if (keyword.length < MIN_HURUF) {
                tutup();
                if (spinner) spinner.hidden = true;
                return;
            }

            timer = setTimeout(function () { cari(keyword); }, JEDA_MS);
        });

        /* ---- Event: navigasi keyboard ---- */
        input.addEventListener('keydown', function (e) {
            const item = daftarItem();

            if (e.key === 'Escape') { tutup(); return; }
            if (!kotak.classList.contains('show') || !item.length) return;

            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();
                indeks += (e.key === 'ArrowDown' ? 1 : -1);
                if (indeks < 0) indeks = item.length - 1;
                if (indeks >= item.length) indeks = 0;

                item.forEach(function (el) { el.classList.remove('is-active'); });
                item[indeks].classList.add('is-active');
                item[indeks].scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'Enter' && indeks > -1) {
                // Ada baris tersorot -> buka barisnya, jangan submit form
                e.preventDefault();
                window.location.href = item[indeks].href;
            }
        });

        /* ---- Event: buka lagi saat input difokus ---- */
        input.addEventListener('focus', function () {
            if (kotak.children.length && input.value.trim().length >= MIN_HURUF) buka();
        });

        /* ---- Event: klik di luar area pencarian menutup dropdown ---- */
        document.addEventListener('click', function (e) {
            if (wrap && !wrap.contains(e.target)) tutup();
        });
    })();

    /* ----------------------------------------------------------------
       5. GRAFIK PEMINJAMAN BULANAN
       Data dikirim dari view sebagai JSON di dalam
       <script id="dlChartData" type="application/json">.
       Cara ini lebih aman daripada menempel PHP langsung di dalam JS.
       ---------------------------------------------------------------- */
    const kanvas   = document.getElementById('dlChartPeminjaman');
    const sumber   = document.getElementById('dlChartData');
    if (!kanvas || !sumber || typeof Chart === 'undefined') return;

    let data;
    try {
        data = JSON.parse(sumber.textContent);
    } catch (err) {
        console.error('Data grafik tidak valid:', err);
        return;
    }

    const ctx = kanvas.getContext('2d');

    // Gradasi biru lembut di bawah garis, seperti pada mockup
    const gradasi = ctx.createLinearGradient(0, 0, 0, 260);
    gradasi.addColorStop(0, 'rgba(59, 130, 246, .28)');
    gradasi.addColorStop(1, 'rgba(59, 130, 246, 0)');

    const grafik = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels || [],
            datasets: [
                {
                    label: 'Peminjaman',
                    data: data.peminjaman || [],
                    borderColor: '#3B82F6',
                    backgroundColor: gradasi,
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.4,               // garis melengkung halus
                    pointRadius: 3,
                    pointBackgroundColor: '#FFFFFF',
                    pointBorderColor: '#3B82F6',
                    pointBorderWidth: 2,
                    pointHoverRadius: 5
                },
                {
                    label: 'Pengembalian',
                    data: data.pengembalian || [],
                    borderColor: '#22C55E',
                    backgroundColor: 'transparent',
                    borderWidth: 2,
                    borderDash: [5, 4],
                    fill: false,
                    tension: 0.4,
                    pointRadius: 3,
                    pointBackgroundColor: '#FFFFFF',
                    pointBorderColor: '#22C55E',
                    pointBorderWidth: 2,
                    pointHoverRadius: 5
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,   // tinggi mengikuti .dl-chart-box
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    position: 'top',
                    align: 'start',
                    labels: {
                        boxWidth: 8,
                        boxHeight: 8,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        font: { size: 11 },
                        color: '#7A8699'
                    }
                },
                tooltip: {
                    backgroundColor: '#101C34',
                    padding: 10,
                    cornerRadius: 8,
                    titleFont: { size: 12 },
                    bodyFont: { size: 12 }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: { color: '#94A3B8', font: { size: 11 } }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: '#EEF2F7' },
                    border: { display: false },
                    ticks: { color: '#94A3B8', font: { size: 11 }, precision: 0 }
                }
            }
        }
    });

    /* ----------------------------------------------------------------
       5b. FILTER RENTANG BULAN
       Dropdown di header kartu memanggil endpoint AJAX
       /dashboard/chart-data?range=... lalu memperbarui grafik tanpa
       me-reload halaman.
       ---------------------------------------------------------------- */
    const filter = document.getElementById('dlChartRange');

    filter?.addEventListener('change', function () {
        fetch(filter.dataset.url + '?range=' + encodeURIComponent(filter.value), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (res) { return res.json(); })
            .then(function (baru) {
                grafik.data.labels             = baru.labels || [];
                grafik.data.datasets[0].data   = baru.peminjaman || [];
                grafik.data.datasets[1].data   = baru.pengembalian || [];
                grafik.update();
            })
            .catch(function (err) { console.error('Gagal memuat data grafik:', err); });
    });
});
