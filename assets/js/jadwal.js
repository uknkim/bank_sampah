/*
========================================
JADWAL
========================================
*/

/* ===== Data ===== */
const dataJadwal = [
    {
        id_jadwal: 1,
        judul_kegiatan: "Sosialisasi Pemilahan Sampah",
        tanggal: "2026-02-10",
        waktu: "08:00",
        lokasi: "Balai Warga RT 01",
        deskripsi: "Edukasi mengenai pemilahan sampah organik dan anorganik."
    },
    {
        id_jadwal: 2,
        judul_kegiatan: "Pengumpulan Sampah Bulanan",
        tanggal: "2026-02-15",
        waktu: "09:00",
        lokasi: "Bank Sampah Metro 46",
        deskripsi: "Pengumpulan sampah dari seluruh nasabah."
    },
    {
        id_jadwal: 3,
        judul_kegiatan: "Pelatihan Daur Ulang",
        tanggal: "2026-02-22",
        waktu: "13:00",
        lokasi: "Aula Kelurahan",
        deskripsi: "Pelatihan pemanfaatan sampah menjadi produk bernilai ekonomi."
    }
];

/* ===== DOM ===== */
const tabelJadwal = document.getElementById("tabelJadwal");

const formTambah = document.getElementById("formTambahJadwal");
const formEdit = document.getElementById("formEditJadwal");

const inputJudul = document.getElementById("judul_kegiatan");
const inputTanggal = document.getElementById("tanggal");
const inputWaktu = document.getElementById("waktu");
const inputLokasi = document.getElementById("lokasi");
const inputDeskripsi = document.getElementById("deskripsi");

const editId = document.getElementById("id_jadwal");
const editJudul = document.getElementById("edit_judul_kegiatan");
const editTanggal = document.getElementById("edit_tanggal");
const editWaktu = document.getElementById("edit_waktu");
const editLokasi = document.getElementById("edit_lokasi");
const editDeskripsi = document.getElementById("edit_deskripsi");

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

function generateIdJadwal() {

    if (dataJadwal.length === 0) {

        return 1;

    }

    return Math.max(
        ...dataJadwal.map(item => item.id_jadwal)
    ) + 1;

}

/* ===== Render ===== */
function renderTable() {

    tabelJadwal.innerHTML = "";

    dataJadwal.forEach((jadwal, index) => {

        tabelJadwal.innerHTML += `
            <tr>

                <td>${index + 1}</td>

                <td>${jadwal.judul_kegiatan}</td>

                <td>${formatTanggal(jadwal.tanggal)}</td>

                <td>${jadwal.waktu}</td>

                <td>${jadwal.lokasi}</td>

                <td>

                    <button
                    class="btn btn-warning btn-sm btn-edit"
                    data-id="${jadwal.id_jadwal}"
                    data-bs-toggle="modal"
                    data-bs-target="#modalEditJadwal">

                        <i class="bi bi-pencil-square"></i>

                    </button>

                    <button
                    class="btn btn-danger btn-sm btn-hapus"
                    data-id="${jadwal.id_jadwal}">

                        <i class="bi bi-trash"></i>

                    </button>

                </td>

            </tr>
        `;

    });

}

/* ===== Tambah ===== */
function tambahJadwal(event) {

    event.preventDefault();

    const dataBaru = {

        id_jadwal: generateIdJadwal(),

        judul_kegiatan: inputJudul.value.trim(),

        tanggal: inputTanggal.value,

        waktu: inputWaktu.value,

        lokasi: inputLokasi.value.trim(),

        deskripsi: inputDeskripsi.value.trim()

    };

    dataJadwal.push(dataBaru);

    bootstrap.Modal
        .getInstance(document.getElementById("modalTambahJadwal"))
        .hide();

    refreshData();

}

/* ===== Reset ===== */
function resetFormTambah() {

    formTambah.reset();

}

/* ===== Edit ===== */
function tampilkanDataEdit(id) {

    const jadwal = dataJadwal.find(
        item => item.id_jadwal == id
    );

    if (!jadwal) return;

    editId.value = jadwal.id_jadwal;
    editJudul.value = jadwal.judul_kegiatan;
    editTanggal.value = jadwal.tanggal;
    editWaktu.value = jadwal.waktu;
    editLokasi.value = jadwal.lokasi;
    editDeskripsi.value = jadwal.deskripsi;

}

function updateJadwal(event) {

    event.preventDefault();

    const id = Number(editId.value);

    const index = dataJadwal.findIndex(
        item => item.id_jadwal === id
    );

    if (index === -1) return;

    dataJadwal[index].judul_kegiatan = editJudul.value.trim();
    dataJadwal[index].tanggal = editTanggal.value;
    dataJadwal[index].waktu = editWaktu.value;
    dataJadwal[index].lokasi = editLokasi.value.trim();
    dataJadwal[index].deskripsi = editDeskripsi.value.trim();

    bootstrap.Modal
        .getInstance(document.getElementById("modalEditJadwal"))
        .hide();

    refreshData();

}

/* ===== Hapus ===== */
function hapusJadwal(id) {

    const konfirmasi = confirm(
        "Yakin ingin menghapus jadwal kegiatan ini?"
    );

    if (!konfirmasi) return;

    const index = dataJadwal.findIndex(
        item => item.id_jadwal == id
    );

    if (index === -1) return;

    dataJadwal.splice(index, 1);

    refreshData();

}

/* ===== Event ===== */
tabelJadwal.addEventListener("click", function(event) {

    const tombolEdit = event.target.closest(".btn-edit");
    const tombolHapus = event.target.closest(".btn-hapus");

    if (tombolEdit) {

        tampilkanDataEdit(
            tombolEdit.dataset.id
        );

    }

    if (tombolHapus) {

        hapusJadwal(
            tombolHapus.dataset.id
        );

    }

});

/* ===== Refresh ===== */
function refreshData() {

    renderTable();

    resetFormTambah();

}

/* ===== Form ===== */
formTambah.addEventListener(
    "submit",
    tambahJadwal
);

formEdit.addEventListener(
    "submit",
    updateJadwal
);

document
    .getElementById("modalTambahJadwal")
    .addEventListener("hidden.bs.modal", function() {

        resetFormTambah();

    });

/* ===== Init ===== */
renderTable();
