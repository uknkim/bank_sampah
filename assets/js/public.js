/*
====================================
PUBLIC
====================================
*/

/* ===== Utility ===== */
function formatTanggal(tanggal) {

    return new Date(tanggal).toLocaleDateString(
        "id-ID",
        {
            day: "2-digit",
            month: "long",
            year: "numeric"
        }
    );

}

function formatRupiah(angka) {

    return Number(angka).toLocaleString(
        "id-ID",
        {
            style: "currency",
            currency: "IDR",
            minimumFractionDigits: 0
        }
    );

}

function getJenisSampah(idJenis) {

    return dataJenisSampah.find(
        item => item.id_jenis === Number(idJenis)
    );

}

function getNasabah(idNasabah) {

    return dataNasabah.find(
        item => item.id_nasabah === Number(idNasabah)
    );

}

function hitungTotalBerat(idNasabah) {

    return dataMonitoring
        .filter(
            item => item.id_nasabah === Number(idNasabah)
        )
        .reduce(
            (total, item) => total + item.berat,
            0
        );

}

function hitungTotalSaldo(idNasabah) {

    return dataMonitoring
        .filter(
            item => item.id_nasabah === Number(idNasabah)
        )
        .reduce(
            (total, item) =>
                total + (item.berat * item.harga_per_kg),
            0
        );

}

function hitungJumlahSetoran(idNasabah) {

    return dataMonitoring
        .filter(
            item => item.id_nasabah === Number(idNasabah)
        )
        .length;

}

function hitungTotalBeratSemua() {

    return dataMonitoring.reduce(
        (total, item) => total + item.berat,
        0
    );

}

function getParameterURL(namaParameter) {

    const parameter = new URLSearchParams(
        window.location.search
    );

    return parameter.get(namaParameter);

}

/* ===== State Detail Monitoring ===== */
const monitoringState = {

    halamanAktif: 1,

    dataPerHalaman: 10,

    chartSetoran: null,

    chartAkumulasi: null

};

function kelompokkanTransaksi(idNasabah) {

    return dataMonitoring
        .filter(
            item => item.id_nasabah === Number(idNasabah)
        )
        .sort(
            (a, b) =>
                new Date(a.tanggal_setoran) -
                new Date(b.tanggal_setoran)
        );

}

function getSepuluhSetoranTerakhir(data) {

    return [...data].slice(-10);

}

function getAkumulasiBerat(data) {

    let total = 0;

    return data.map(item => {

        total += item.berat;

        return {

            tanggal: formatTanggal(item.tanggal_setoran),

            total

        };

    });

}

/* ===== Beranda ===== */
function renderBeranda() {

    const totalNasabah = document.getElementById("totalNasabah");
    const totalJenis = document.getElementById("totalJenis");
    const totalSetoran = document.getElementById("totalSetoran");
    const totalBerat = document.getElementById("totalBerat");

    if (
        !totalNasabah ||
        !totalJenis ||
        !totalSetoran ||
        !totalBerat
    ) {
        return;
    }

    totalNasabah.textContent = dataNasabah.length;

    totalJenis.textContent = dataJenisSampah.length;

    totalSetoran.textContent = dataMonitoring.length;

    totalBerat.textContent =
        `${hitungTotalBeratSemua()} Kg`;

}

/* ===== Profil ===== */
function renderProfil() {

    const nama = document.getElementById("nama_bank_sampah");

    if (!nama) return;

    document.getElementById("nama_bank_sampah").textContent =
        dataProfil.nama_bank_sampah;

    document.getElementById("alamat").textContent =
        dataProfil.alamat;

    const telepon = document.getElementById("no_hp");

    if (telepon) {

        telepon.textContent =
            dataProfil.telepon ||
            dataProfil.no_hp ||
            "-";

    }

    document.getElementById("email").textContent =
        dataProfil.email;

    document.getElementById("deskripsi").textContent =
        dataProfil.deskripsi;

    const logo = document.getElementById("logo");

    if (
        logo &&
        dataProfil.logo
    ) {

        logo.src = dataProfil.logo;

    }

    const daftarJam =
        document.getElementById("jam_operasional");

    if (
        daftarJam &&
        Array.isArray(dataProfil.jam_operasional)
    ) {

        daftarJam.innerHTML = "";

        dataProfil.jam_operasional.forEach(item => {

            daftarJam.insertAdjacentHTML(
                "beforeend",
                `
                    <li>

                        ${item}

                    </li>
                `
            );

        });

    }

}

/* ===== Jenis Sampah ===== */
function renderJenisSampah() {

    const tabel =
        document.getElementById("tabelJenisSampah");

    if (!tabel) return;

    tabel.innerHTML = "";

    dataJenisSampah.forEach((item, index) => {

        tabel.insertAdjacentHTML(
            "beforeend",
            `
                <tr>

                    <td class="text-center">

                        ${index + 1}

                    </td>

                    <td>

                        ${item.nama_jenis}

                    </td>

                    <td class="text-end">

                        ${formatRupiah(
                            item.harga_per_kg
                        )}

                    </td>

                </tr>
            `
        );

    });

}

/* ===== Jadwal ===== */
function renderJadwal() {

    const container =
        document.getElementById("daftarJadwal");

    if (!container) return;

    container.innerHTML = "";

    dataJadwal.forEach(item => {

        container.insertAdjacentHTML(
            "beforeend",
            `
                <div class="card shadow-sm border-0 mb-3">

                    <div class="card-body">

                        <h5 class="card-title fw-bold mb-3">

                            ${item.judul_kegiatan}

                        </h5>

                        <div class="mb-2">

                            <i class="bi bi-calendar-event me-2 text-primary"></i>

                            ${formatTanggal(
                                item.tanggal
                            )}

                        </div>

                        <div class="mb-2">

                            <i class="bi bi-clock me-2 text-success"></i>

                            ${item.waktu} WIB

                        </div>

                        <div class="mb-3">

                            <i class="bi bi-geo-alt me-2 text-danger"></i>

                            ${item.lokasi}

                        </div>

                        <p class="card-text text-muted mb-0">

                            ${item.deskripsi}

                        </p>

                    </div>

                </div>
            `
        );

    });

}

/* ===== Data Nasabah ===== */
function renderDataNasabah() {

    const tabel =
        document.getElementById("tabelNasabah");

    if (!tabel) return;

    tabel.innerHTML = "";

    dataNasabah.forEach((item, index) => {

        tabel.insertAdjacentHTML(
            "beforeend",
            `
                <tr>

                    <td class="text-center">

                        ${index + 1}

                    </td>

                    <td>

                        ${item.nama_nasabah}

                    </td>

                    <td>

                        ${formatTanggal(
                            item.tanggal_bergabung
                        )}

                    </td>

                    <td class="text-center">

                        <a
                        href="detail-monitoring.html?id=${item.id_nasabah}"
                        class="btn btn-primary btn-sm">

                            <i class="bi bi-eye me-1"></i>

                            Detail

                        </a>

                    </td>

                </tr>
            `
        );

    });

}

/* ===== Detail Monitoring ===== */

function kelompokkanTransaksi(idNasabah) {

    const transaksi = {};

    dataMonitoring
        .filter(
            item => item.id_nasabah === Number(idNasabah)
        )
        .forEach(item => {

            if (!transaksi[item.id_transaksi]) {

                transaksi[item.id_transaksi] = {

                    id_transaksi: item.id_transaksi,

                    tanggal_setoran: item.tanggal_setoran,

                    total_berat: 0,

                    total_saldo: 0,

                    detail: []

                };

            }

            transaksi[item.id_transaksi].total_berat += item.berat;

            transaksi[item.id_transaksi].total_saldo +=
                item.berat * item.harga_per_kg;

            transaksi[item.id_transaksi].detail.push(item);

        });

    return Object.values(transaksi)
        .sort(
            (a, b) =>
                new Date(a.tanggal_setoran) -
                new Date(b.tanggal_setoran)
        );

}

function getSepuluhSetoranTerakhir(transaksi) {

    return [...transaksi].slice(-10);

}

function getTotalBeratPerJenis(transaksi) {

    const hasil = {};

    transaksi.forEach(item => {

        item.detail.forEach(detail => {

            const jenis = getJenisSampah(detail.id_jenis);

            const namaJenis = jenis
                ? jenis.nama_jenis
                : "-";

            if (!hasil[namaJenis]) {

                hasil[namaJenis] = 0;

            }

            hasil[namaJenis] += detail.berat;

        });

    });

    return Object.keys(hasil).map(nama => ({

        jenis: nama,

        total: hasil[nama]

    }));

}

/* ===== Chart ===== */

function renderChartSetoran(data) {

    const canvas =
        document.getElementById("chartSetoran");

    if (!canvas) return;

    if (monitoringState.chartSetoran) {

        monitoringState.chartSetoran.destroy();

    }

    monitoringState.chartSetoran =
        new Chart(canvas, {

            type: "line",

            data: {

                labels: data.map(item =>
                    formatTanggal(
                        item.tanggal_setoran
                    )
                ),

                datasets: [

                    {

                        label: "Berat Setoran (Kg)",

                        data: data.map(
                            item => item.total_berat
                        ),

                        tension: 0.3,

                        fill: false

                    }

                ]

            },

            options: {

                responsive: true,

                maintainAspectRatio: false

            }

        });

}

function renderChartAkumulasi(data) {

    const canvas =
        document.getElementById("chartAkumulasi");

    if (!canvas) return;

    if (monitoringState.chartAkumulasi) {

        monitoringState.chartAkumulasi.destroy();

    }

    monitoringState.chartAkumulasi =
        new Chart(canvas, {

            type: "bar",

            data: {

                labels: data.map(
                    item => item.jenis
                ),

                datasets: [

                    {

                        label: "Total Berat (Kg)",

                        data: data.map(
                            item => item.total
                        )

                    }

                ]

            },

            options: {

                responsive: true,

                maintainAspectRatio: false

            }

        });

}

/* ===== Pagination ===== */

function renderPagination(totalData) {

    const pagination =
        document.getElementById(
            "paginationMonitoring"
        );

    if (!pagination) return;

    pagination.innerHTML = "";

    const totalHalaman = Math.ceil(
        totalData /
        monitoringState.dataPerHalaman
    );

    if (totalHalaman <= 1) {

        return;

    }

    for (
        let halaman = 1;
        halaman <= totalHalaman;
        halaman++
    ) {

        pagination.insertAdjacentHTML(
            "beforeend",
            `
                <li class="page-item ${halaman === monitoringState.halamanAktif ? "active" : ""}">

                    <button
                        class="page-link"
                        onclick="changePage(${halaman})">

                        ${halaman}

                    </button>

                </li>
            `
        );

    }

}

function changePage(halaman) {

    monitoringState.halamanAktif = halaman;

    renderDetailMonitoring();

}

/* ===== Render ===== */

function renderInformasiNasabah(nasabah, transaksi) {

    document.getElementById("nama_nasabah").textContent =
        nasabah.nama_nasabah;

    document.getElementById("alamat").textContent =
        nasabah.alamat;

    document.getElementById("tanggal_bergabung").textContent =
        formatTanggal(nasabah.tanggal_bergabung);

    document.getElementById("jumlah_setoran").textContent =
        transaksi.length;

    const totalBerat = transaksi.reduce(
        (total, item) => total + item.total_berat,
        0
    );

    document.getElementById("total_berat").textContent =
        `${totalBerat} Kg`;

    const totalSaldo = transaksi.reduce(
        (total, item) => total + item.total_saldo,
        0
    );

    document.getElementById("total_saldo").textContent =
        formatRupiah(totalSaldo);

}

function renderRiwayatMonitoring(transaksi) {

    const tbody =
        document.getElementById("tabel_monitoring");

    if (!tbody) return;

    tbody.innerHTML = "";

    const mulai =
        (monitoringState.halamanAktif - 1) *
        monitoringState.dataPerHalaman;

    const selesai =
        mulai +
        monitoringState.dataPerHalaman;

    const dataHalaman =
        transaksi.slice(mulai, selesai);

    dataHalaman.forEach((item, index) => {

        const jenis =
            item.detail
                .map(detail => {

                    const dataJenis =
                        getJenisSampah(detail.id_jenis);

                    return dataJenis
                        ? dataJenis.nama_jenis
                        : "-";

                })
                .join(", ");

        const harga =
            item.detail.reduce(
                (total, detail) =>
                    total + detail.harga_per_kg,
                0
            ) / item.detail.length;

        tbody.insertAdjacentHTML(
            "beforeend",
            `
                <tr>

                    <td>

                        ${mulai + index + 1}

                    </td>

                    <td>

                        ${formatTanggal(
                            item.tanggal_setoran
                        )}

                    </td>

                    <td>

                        ${jenis}

                    </td>

                    <td class="text-center">

                        ${item.total_berat} Kg

                    </td>

                    <td class="text-end">

                        ${formatRupiah(harga)}

                    </td>

                    <td class="text-end">

                        ${formatRupiah(
                            item.total_saldo
                        )}

                    </td>

                </tr>
            `
        );

    });

    const info =
        document.getElementById("infoRiwayat");

    if (info) {

        const akhir =
            Math.min(
                selesai,
                transaksi.length
            );

        info.textContent =
            `Menampilkan ${mulai + 1} - ${akhir} dari ${transaksi.length} transaksi`;

    }

}

function renderDetailMonitoring() {

    const idNasabah =
        getParameterURL("id");

    if (!idNasabah) return;

    const nasabah =
        getNasabah(idNasabah);

    if (!nasabah) return;

    const transaksi =
        kelompokkanTransaksi(idNasabah);

    renderInformasiNasabah(
        nasabah,
        transaksi
    );

    renderRiwayatMonitoring(
        transaksi
    );

    renderPagination(
        transaksi.length
    );

    renderChartSetoran(
        getSepuluhSetoranTerakhir(
            transaksi
        )
    );

    renderChartAkumulasi(
    getTotalBeratPerJenis(
        transaksi
    )
    );

}

/* ===== Init ===== */

function init() {

    renderBeranda();

    renderProfil();

    renderJenisSampah();

    renderJadwal();

    renderDataNasabah();

    renderDetailMonitoring();

}

document.addEventListener(
    "DOMContentLoaded",
    init
);