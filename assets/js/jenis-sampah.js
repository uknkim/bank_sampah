/*
========================================
JENIS SAMPAH
========================================
*/

/* ===== DOM ===== */
const tabelJenisSampah = document.getElementById("tabelJenisSampah");

const formTambah = document.getElementById("formTambahJenis");
const formEdit = document.getElementById("formEditJenis");

const inputNamaJenis = document.getElementById("nama_jenis");
const inputHarga = document.getElementById("harga_per_kg");

const editIdJenis = document.getElementById("id_jenis");
const editNamaJenis = document.getElementById("edit_nama_jenis");
const editHarga = document.getElementById("edit_harga_per_kg");

/* ===== Utility ===== */
function formatRupiah(angka) {

    return angka.toLocaleString("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0
    });

}

function generateIdJenis() {

    if (dataJenisSampah.length === 0) {

        return 1;

    }

    return Math.max(
        ...dataJenisSampah.map(item => item.id_jenis)
    ) + 1;

}

/* ===== Render ===== */
function renderTable() {

    tabelJenisSampah.innerHTML = "";

    dataJenisSampah.forEach((jenis, index) => {

        tabelJenisSampah.innerHTML += `
            <tr>

                <td>${index + 1}</td>

                <td>${jenis.nama_jenis}</td>

                <td>${formatRupiah(jenis.harga_per_kg)}</td>

                <td>

                    <button
                        class="btn btn-warning btn-sm btn-edit"
                        data-id="${jenis.id_jenis}"
                        data-bs-toggle="modal"
                        data-bs-target="#modalEditJenis">

                        <i class="bi bi-pencil-square"></i>

                    </button>

                    <button
                        class="btn btn-danger btn-sm btn-hapus"
                        data-id="${jenis.id_jenis}">

                        <i class="bi bi-trash"></i>

                    </button>

                </td>

            </tr>
        `;

    });

}

/* ===== Tambah ===== */
function tambahJenis(event) {

    event.preventDefault();

    const namaJenis = inputNamaJenis.value.trim();

    const harga = Number(inputHarga.value);

    if (!namaJenis || harga <= 0) {

        alert("Nama jenis sampah dan harga harus diisi.");

        return;

    }

    dataJenisSampah.push({

        id_jenis: generateIdJenis(),

        nama_jenis: namaJenis,

        harga_per_kg: harga

    });

    bootstrap.Modal
        .getInstance(document.getElementById("modalTambahJenis"))
        .hide();

    refreshData();

}

/* ===== Reset ===== */
function resetFormTambah() {

    formTambah.reset();

}

/* ===== Edit ===== */
function tampilkanDataEdit(id) {

    const jenis = dataJenisSampah.find(item =>
        item.id_jenis === Number(id)
    );

    if (!jenis) return;

    editIdJenis.value = jenis.id_jenis;
    editNamaJenis.value = jenis.nama_jenis;
    editHarga.value = jenis.harga_per_kg;

}

function updateJenis(event) {

    event.preventDefault();

    const id = Number(editIdJenis.value);

    const index = dataJenisSampah.findIndex(item =>
        item.id_jenis === id
    );

    if (index === -1) return;

    const namaJenis = editNamaJenis.value.trim();

    const harga = Number(editHarga.value);

    if (!namaJenis || harga <= 0) {

        alert("Nama jenis sampah dan harga harus diisi.");

        return;

    }

    dataJenisSampah[index].nama_jenis = namaJenis;
    dataJenisSampah[index].harga_per_kg = harga;

    bootstrap.Modal
        .getInstance(document.getElementById("modalEditJenis"))
        .hide();

    refreshData();

}

/* ===== Hapus ===== */
function hapusJenis(id) {

    const konfirmasi = confirm(
        "Yakin ingin menghapus data jenis sampah ini?"
    );

    if (!konfirmasi) return;

    const index = dataJenisSampah.findIndex(item =>
        item.id_jenis === Number(id)
    );

    if (index === -1) return;

    dataJenisSampah.splice(index, 1);

    refreshData();

}

/* ===== Event ===== */
tabelJenisSampah.addEventListener("click", function (event) {

    const tombolEdit = event.target.closest(".btn-edit");
    const tombolHapus = event.target.closest(".btn-hapus");

    if (tombolEdit) {

        tampilkanDataEdit(tombolEdit.dataset.id);

    }

    if (tombolHapus) {

        hapusJenis(tombolHapus.dataset.id);

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
    tambahJenis
);

formEdit.addEventListener(
    "submit",
    updateJenis
);

document
    .getElementById("modalTambahJenis")
    .addEventListener("hidden.bs.modal", function () {

        resetFormTambah();

    });

/* ===== Init ===== */
renderTable();