/*
========================================
DASHBOARD
========================================
*/

/* ===== DOM ===== */
const totalNasabah = document.getElementById("totalNasabah");
const totalJenis = document.getElementById("totalJenis");
const totalSetoran = document.getElementById("totalSetoran");
const totalBerat = document.getElementById("totalBerat");

const tabelTransaksiTerbaru = document.getElementById("tabelTransaksiTerbaru");
const jadwalTerdekat = document.getElementById("jadwalTerdekat");

/* ===== Utility ===== */
function formatTanggal(tanggal) {

    return new Date(tanggal).toLocaleDateString("id-ID", {
        day: "2-digit",
        month: "long",
        year: "numeric"
    });

}

function formatRupiah(angka) {

    return angka.toLocaleString("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0
    });

}

/* ===== Render Statistik ===== */
function renderStatistik() {

    totalNasabah.textContent = dataNasabah.length;

    totalJenis.textContent = dataJenisSampah.length;

    totalSetoran.textContent = dataMonitoring.length;

    const totalKg = dataMonitoring.reduce((total, item) => {

        return total + item.berat;

    }, 0);

    totalBerat.textContent = `${totalKg} Kg`;

}

/* ===== Render Transaksi Terbaru ===== */
function renderTransaksiTerbaru() {

    tabelTransaksiTerbaru.innerHTML = "";

    const transaksiTerbaru = [...dataMonitoring]
        .sort((a, b) =>
            new Date(b.tanggal_setoran) - new Date(a.tanggal_setoran)
        )
        .slice(0, 5);

    transaksiTerbaru.forEach((transaksi, index) => {

        const nasabah = dataNasabah.find(item =>
            item.id_nasabah === transaksi.id_nasabah
        );

        const total = transaksi.berat * transaksi.harga_per_kg;

        tabelTransaksiTerbaru.innerHTML += `
            <tr>

                <td>${index + 1}</td>

                <td>${nasabah ? nasabah.nama_nasabah : "-"}</td>

                <td>${formatTanggal(transaksi.tanggal_setoran)}</td>

                <td>${transaksi.berat} Kg</td>

                <td>${formatRupiah(total)}</td>

            </tr>
        `;

    });

}

/* ===== Render Jadwal Terdekat ===== */
function renderJadwalTerdekat() {

    jadwalTerdekat.innerHTML = "";

    const jadwal = [...dataJadwal]
        .sort((a, b) =>
            new Date(a.tanggal) - new Date(b.tanggal)
        )
        .slice(0, 3);

    jadwal.forEach(item => {

        jadwalTerdekat.innerHTML += `
            <div class="border-bottom pb-3 mb-3">

                <h6 class="mb-1">
                    ${item.judul_kegiatan}
                </h6>

                <small class="text-muted d-block">
                    <i class="bi bi-calendar-event me-1"></i>
                    ${formatTanggal(item.tanggal)}
                </small>

                <small class="text-muted d-block">
                    <i class="bi bi-clock me-1"></i>
                    ${item.waktu} WIB
                </small>

                <small class="text-muted d-block">
                    <i class="bi bi-geo-alt me-1"></i>
                    ${item.lokasi}
                </small>

            </div>
        `;

    });

}

/* ===== Init ===== */
renderStatistik();

renderTransaksiTerbaru();

renderJadwalTerdekat();