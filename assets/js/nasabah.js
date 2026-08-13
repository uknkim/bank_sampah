/*
==================================================
BANK SAMPAH METRO 46
HALAMAN DATA NASABAH
==================================================
*/

/* ==================================================
   ELEMENT DOM
================================================== */

const tabelNasabah = document.getElementById("tabelNasabah");

const formTambah = document.getElementById("formTambahNasabah");

const formEdit = document.getElementById("formEditNasabah");

const modalTambah =
    new bootstrap.Modal(
        document.getElementById("modalTambahNasabah")
    );

const modalEdit =
    new bootstrap.Modal(
        document.getElementById("modalEditNasabah")
    );

/* ==================================================
   UTILITY
================================================== */

function formatTanggal(tanggal) {

    return new Date(tanggal).toLocaleDateString("id-ID", {

        day: "2-digit",

        month: "long",

        year: "numeric"

    });

}

/* ==================================================
   GENERATE ID BARU
================================================== */

function generateIdNasabah() {

    if (dataNasabah.length === 0) {

        return 1;

    }

    return Math.max(

        ...dataNasabah.map(item => item.id_nasabah)

    ) + 1;

}

/* ==================================================
   RENDER TABLE
================================================== */

function renderTable() {

    tabelNasabah.innerHTML = "";

    dataNasabah.forEach((nasabah, index) => {

        tabelNasabah.innerHTML += `
            <tr>

                <td>${index + 1}</td>

                <td>${nasabah.nama_nasabah}</td>

                <td>${nasabah.alamat}</td>

                <td>${nasabah.no_hp}</td>

                <td>${formatTanggal(nasabah.tanggal_bergabung)}</td>

                <td>

                    <a
                        href="detail-monitoring.html?id=${nasabah.id_nasabah}"
                        class="btn btn-info btn-sm btn-detail"
                        title="Lihat Detail">

                        <i class="bi bi-eye-fill"></i>

                    </a>

                    <button
                        type="button"
                        class="btn btn-warning btn-sm btn-edit"
                        data-id="${nasabah.id_nasabah}"
                        title="Edit">

                        <i class="bi bi-pencil-square"></i>

                    </button>

                    <button
                        type="button"
                        class="btn btn-danger btn-sm btn-hapus"
                        data-id="${nasabah.id_nasabah}"
                        title="Hapus">

                        <i class="bi bi-trash-fill"></i>

                    </button>

                </td>

            </tr>
        `;

    });

}

/* ==================================================
   TAMBAH DATA
================================================== */

function tambahNasabah(event) {

    event.preventDefault();

    const nama = document
        .getElementById("nama_nasabah")
        .value
        .trim();

    const alamat = document
        .getElementById("alamat")
        .value
        .trim();

    const noHp = document
        .getElementById("no_hp")
        .value
        .trim();

    const tanggal = document
        .getElementById("tanggal_bergabung")
        .value;

    if (!nama || !alamat || !noHp || !tanggal) {

        alert("Semua data harus diisi.");

        return;

    }

    dataNasabah.push({

        id_nasabah: generateIdNasabah(),

        nama_nasabah: nama,

        alamat,

        no_hp: noHp,

        tanggal_bergabung: tanggal

    });

    refreshDataNasabah();

    modalTambah.hide();

}

/* ==================================================
   RESET FORM
================================================== */

function resetFormTambah() {

    formTambah.reset();

}

/* ==================================================
   CARI DATA
================================================== */

function cariNasabah(id) {

    return dataNasabah.find(item =>
        item.id_nasabah === Number(id)
    );

}

/* ==================================================
   EDIT DATA
================================================== */

function tampilkanDataEdit(id) {

    const data = cariNasabah(id);

    if (!data) return;

    document.getElementById("id_nasabah").value = data.id_nasabah;

    document.getElementById("edit_nama_nasabah").value = data.nama_nasabah;

    document.getElementById("edit_alamat").value = data.alamat;

    document.getElementById("edit_no_hp").value = data.no_hp;

    document.getElementById("edit_tanggal_bergabung").value = data.tanggal_bergabung;

    modalEdit.show();

}

function updateNasabah(event) {

    event.preventDefault();

    const id = Number(
        document.getElementById("id_nasabah").value
    );

    const index = dataNasabah.findIndex(item =>
        item.id_nasabah === id
    );

    if (index === -1) return;

    const nama = document
        .getElementById("edit_nama_nasabah")
        .value
        .trim();

    const alamat = document
        .getElementById("edit_alamat")
        .value
        .trim();

    const noHp = document
        .getElementById("edit_no_hp")
        .value
        .trim();

    const tanggal = document
        .getElementById("edit_tanggal_bergabung")
        .value;

    if (!nama || !alamat || !noHp || !tanggal) {

        alert("Semua data harus diisi.");

        return;

    }

    dataNasabah[index].nama_nasabah = nama;
    dataNasabah[index].alamat = alamat;
    dataNasabah[index].no_hp = noHp;
    dataNasabah[index].tanggal_bergabung = tanggal;

    refreshDataNasabah();

    modalEdit.hide();

}

/* ==================================================
   HAPUS DATA
================================================== */

function hapusNasabah(id) {

    const data = cariNasabah(id);

    if (!data) return;

    const konfirmasi = confirm(
        `Apakah Anda yakin ingin menghapus data nasabah "${data.nama_nasabah}"?`
    );

    if (!konfirmasi) return;

    const index = dataNasabah.findIndex(item =>
        item.id_nasabah === Number(id)
    );

    if (index === -1) return;

    dataNasabah.splice(index, 1);

    refreshDataNasabah();

}

/* ==================================================
   EVENT
================================================== */

tabelNasabah.addEventListener("click", function (event) {

    const tombolEdit = event.target.closest(".btn-edit");
    const tombolHapus = event.target.closest(".btn-hapus");

    if (tombolEdit) {

        tampilkanDataEdit(tombolEdit.dataset.id);

        return;

    }

    if (tombolHapus) {

        hapusNasabah(tombolHapus.dataset.id);

        return;

    }

});

/* ==================================================
   REFRESH
================================================== */

function refreshDataNasabah() {

    renderTable();

    resetFormTambah();

}

/* ==================================================
   EVENT FORM
================================================== */

formTambah.addEventListener(
    "submit",
    tambahNasabah
);

formEdit.addEventListener(
    "submit",
    updateNasabah
);

/* ==================================================
   RESET FORM
================================================== */

document
    .getElementById("modalTambahNasabah")
    .addEventListener("hidden.bs.modal", function () {

        resetFormTambah();

    });

/* ==================================================
   INIT
================================================== */

renderTable();