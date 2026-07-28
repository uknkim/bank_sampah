/*
========================================
DETAIL MONITORING NASABAH
========================================
*/

/* ===== DOM ===== */
const namaNasabah = document.getElementById("nama_nasabah");
const alamat = document.getElementById("alamat");
const noHp = document.getElementById("no_hp");
const tanggalBergabung = document.getElementById("tanggal_bergabung");

const jumlahSetoran = document.getElementById("jumlah_setoran");
const totalBerat = document.getElementById("total_berat");
const totalSaldo = document.getElementById("total_saldo");
const grandTotal = document.getElementById("grand_total");

const jumlahSetoranBadge = document.getElementById("jumlah_setoran_badge");

const tabelMonitoring = document.getElementById("tabel_monitoring");

const grafikPerkembangan = document.getElementById("grafikPerkembangan");
const grafikJenisSampah = document.getElementById("grafikJenisSampah");

const infoPagination = document.getElementById("info_pagination");
const paginationMonitoring = document.getElementById("pagination_monitoring");

/* ===== URL ===== */
const parameterURL = new URLSearchParams(window.location.search);

const idNasabah = Number(parameterURL.get("id"));

/* ===== Utility ===== */
function formatTanggal(tanggal) {

    return new Date(tanggal).toLocaleDateString("id-ID", {
        day: "2-digit",
        month: "short",
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

/* ===== Data Monitoring Nasabah ===== */
const nasabah = dataNasabah.find(item => {

    return item.id_nasabah === idNasabah;

});

const monitoringNasabah = dataMonitoring
    .filter(item => {

        return item.id_nasabah === idNasabah;

    })
    .sort((a, b) => {

        return new Date(a.tanggal_setoran) - new Date(b.tanggal_setoran);

    });

/* ===== Konfigurasi Pagination ===== */
const DATA_PER_HALAMAN = 10;

const totalHalaman = Math.max(
    1,
    Math.ceil(monitoringNasabah.length / DATA_PER_HALAMAN)
);

let halamanAktif = totalHalaman;

/* ===== Render Informasi Nasabah ===== */
function renderInformasiNasabah() {

    if (!nasabah) {

        alert("Data nasabah tidak ditemukan.");

        window.location.href = "data-nasabah.html";

        return;

    }

    namaNasabah.textContent = nasabah.nama_nasabah;
    alamat.textContent = nasabah.alamat;
    noHp.textContent = nasabah.no_hp;

    tanggalBergabung.textContent = formatTanggal(
        nasabah.tanggal_bergabung
    );

}

/* ===== Render Ringkasan ===== */
function renderRingkasan() {

    const jumlah = monitoringNasabah.length;

    const totalBeratKg = monitoringNasabah.reduce((total, item) => {

        return total + item.berat;

    }, 0);

    const totalSaldoRp = monitoringNasabah.reduce((total, item) => {

        return total + (item.berat * item.harga_per_kg);

    }, 0);

    jumlahSetoran.textContent = jumlah;

    jumlahSetoranBadge.textContent = `${jumlah} Setoran`;

    totalBerat.textContent = `${totalBeratKg.toFixed(1)} Kg`;

    totalSaldo.textContent = formatRupiah(totalSaldoRp);

    grandTotal.textContent = formatRupiah(totalSaldoRp);

}

/* ===== Render Grafik Perkembangan ===== */
function renderGrafikPerkembangan() {

    const dataGrafik = monitoringNasabah.slice(-10);

    new Chart(grafikPerkembangan, {

        type: "line",

        data: {

            labels: dataGrafik.map(item =>
                formatTanggal(item.tanggal_setoran)
            ),

            datasets: [{

                label: "Total Berat (Kg)",

                data: dataGrafik.map(item =>
                    item.berat
                ),

                borderWidth: 2,

                tension: 0.3,

                fill: false

            }]

        },

        options: {

            responsive: true,

            maintainAspectRatio: false,

            plugins: {

                legend: {

                    display: true

                }

            },

            scales: {

                y: {

                    beginAtZero: true,

                    title: {

                        display: true,

                        text: "Berat (Kg)"

                    }

                },

                x: {

                    title: {

                        display: true,

                        text: "Tanggal Setoran"

                    }

                }

            }

        }

    });

}

/* ===== Render Grafik Jenis Sampah ===== */
function renderGrafikJenisSampah() {

    const totalPerJenis = {};

    monitoringNasabah.forEach(item => {

        const jenis = dataJenisSampah.find(jenis => {

            return jenis.id_jenis === item.id_jenis;

        });

        if (!jenis) return;

        if (!totalPerJenis[jenis.nama_jenis]) {

            totalPerJenis[jenis.nama_jenis] = 0;

        }

        totalPerJenis[jenis.nama_jenis] += item.berat;

    });

    new Chart(grafikJenisSampah, {

        type: "bar",

        data: {

            labels: Object.keys(totalPerJenis),

            datasets: [{

                label: "Total Berat (Kg)",

                data: Object.values(totalPerJenis),

                borderWidth: 1

            }]

        },

        options: {

            responsive: true,

            maintainAspectRatio: false,

            plugins: {

                legend: {

                    display: true

                }

            },

            scales: {

                y: {

                    beginAtZero: true,

                    title: {

                        display: true,

                        text: "Berat (Kg)"

                    }

                },

                x: {

                    title: {

                        display: true,

                        text: "Jenis Sampah"

                    }

                }

            }

        }

    });

}

/* ===== Render Tabel Monitoring ===== */
function renderTabelMonitoring() {

    const awal = (halamanAktif - 1) * DATA_PER_HALAMAN;
    const akhir = awal + DATA_PER_HALAMAN;

    const dataHalaman = monitoringNasabah.slice(awal, akhir);

    tabelMonitoring.innerHTML = "";

    dataHalaman.forEach((item, index) => {

        const jenis = dataJenisSampah.find(jenis => {

            return jenis.id_jenis === item.id_jenis;

        });

        const total = item.berat * item.harga_per_kg;

        tabelMonitoring.innerHTML += `
            <tr>
                <td>${awal + index + 1}</td>
                <td>${formatTanggal(item.tanggal_setoran)}</td>
                <td>${jenis ? jenis.nama_jenis : "-"}</td>
                <td>${item.berat} Kg</td>
                <td>${formatRupiah(item.harga_per_kg)}</td>
                <td>${formatRupiah(total)}</td>
            </tr>
        `;

    });

}

/* ===== Render Info Pagination ===== */
function renderInfoPagination() {

    if (monitoringNasabah.length === 0) {

        infoPagination.textContent = "Menampilkan 0–0 dari 0 transaksi";

        return;

    }

    const awal = (halamanAktif - 1) * DATA_PER_HALAMAN + 1;

    const akhir = Math.min(
        halamanAktif * DATA_PER_HALAMAN,
        monitoringNasabah.length
    );

    infoPagination.textContent =
        `Menampilkan ${awal}–${akhir} dari ${monitoringNasabah.length} transaksi`;

}

/* ===== Render Pagination ===== */
function renderPagination() {

    paginationMonitoring.innerHTML = "";

    // Previous
    paginationMonitoring.innerHTML += `
        <li class="page-item ${halamanAktif === 1 ? "disabled" : ""}">
            <a class="page-link"
               href="#"
               data-page="${halamanAktif - 1}">
                &laquo;
            </a>
        </li>
    `;

    // Nomor Halaman
    for (let i = 1; i <= totalHalaman; i++) {

        paginationMonitoring.innerHTML += `
            <li class="page-item ${i === halamanAktif ? "active" : ""}">
                <a class="page-link"
                   href="#"
                   data-page="${i}">
                    ${i}
                </a>
            </li>
        `;

    }

    // Next
    paginationMonitoring.innerHTML += `
        <li class="page-item ${halamanAktif === totalHalaman ? "disabled" : ""}">
            <a class="page-link"
               href="#"
               data-page="${halamanAktif + 1}">
                &raquo;
            </a>
        </li>
    `;

    document
        .querySelectorAll("#pagination_monitoring .page-link")
        .forEach(button => {

            button.addEventListener("click", function (e) {

                e.preventDefault();

                const halaman = Number(this.dataset.page);

                if (
                    halaman < 1 ||
                    halaman > totalHalaman ||
                    halaman === halamanAktif
                ) {

                    return;

                }

                halamanAktif = halaman;

                renderTabelMonitoring();
                renderInfoPagination();
                renderPagination();

            });

        });

}

/* ===== Init ===== */
renderInformasiNasabah();

renderRingkasan();

renderGrafikPerkembangan();

renderGrafikJenisSampah();

renderTabelMonitoring();

renderInfoPagination();

renderPagination();