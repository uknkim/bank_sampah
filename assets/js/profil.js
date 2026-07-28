/*
========================================
PROFIL
========================================
*/

/* ===== Data ===== */
const dataProfil = {
    id_profil: 1,
    nama_bank_sampah: "Bank Sampah Metro 46",
    alamat: "Jl. Contoh No. 123, Tangerang Selatan",
    no_hp: "081234567890",
    email: "banksampahmetro46@gmail.com",
    deskripsi: "Bank Sampah Metro 46 merupakan bank sampah yang berfokus pada pengelolaan sampah masyarakat melalui kegiatan pemilahan, penimbangan, pencatatan setoran, serta edukasi lingkungan.",
    logo: "../assets/img/logo.png"
};

/* ===== DOM ===== */
const formProfil = document.getElementById("formProfil");

const inputId = document.getElementById("id_profil");
const inputNama = document.getElementById("nama_bank_sampah");
const inputAlamat = document.getElementById("alamat");
const inputNoHp = document.getElementById("no_hp");
const inputEmail = document.getElementById("email");
const inputDeskripsi = document.getElementById("deskripsi");
const inputLogo = document.getElementById("logo");

const previewLogo = document.getElementById("previewLogo");

/* ===== Render ===== */
function renderProfil() {

    inputId.value = dataProfil.id_profil;

    inputNama.value = dataProfil.nama_bank_sampah;

    inputAlamat.value = dataProfil.alamat;

    inputNoHp.value = dataProfil.no_hp;

    inputEmail.value = dataProfil.email;

    inputDeskripsi.value = dataProfil.deskripsi;

    previewLogo.src = dataProfil.logo;

}

/* ===== Preview Logo ===== */
function previewGambar(event) {

    const file = event.target.files[0];

    if (!file) {

        previewLogo.src = dataProfil.logo;

        return;

    }

    const reader = new FileReader();

    reader.onload = function(e) {

        previewLogo.src = e.target.result;

    };

    reader.readAsDataURL(file);

}

/* ===== Update ===== */
function updateProfil(event) {

    event.preventDefault();

    dataProfil.nama_bank_sampah = inputNama.value.trim();

    dataProfil.alamat = inputAlamat.value.trim();

    dataProfil.no_hp = inputNoHp.value.trim();

    dataProfil.email = inputEmail.value.trim();

    dataProfil.deskripsi = inputDeskripsi.value.trim();

    if (inputLogo.files.length > 0) {

        dataProfil.logo = previewLogo.src;

    }

    alert("Profil Bank Sampah berhasil diperbarui.");

    refreshData();

}

/* ===== Refresh ===== */
function refreshData() {

    renderProfil();

    inputLogo.value = "";

}

/* ===== Event ===== */
formProfil.addEventListener(
    "submit",
    updateProfil
);

inputLogo.addEventListener(
    "change",
    previewGambar
);

/* ===== Init ===== */
renderProfil();