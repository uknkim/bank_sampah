/*
==================================================
DATA DUMMY
SISTEM INFORMASI BANK SAMPAH METRO 46
==================================================
*/

/* ===== Data Jenis Sampah ===== */
const dataJenisSampah = [

    {
        id_jenis: 1,
        nama_jenis: "Botol Plastik",
        harga_per_kg: 5000
    },
    {
        id_jenis: 2,
        nama_jenis: "Kardus",
        harga_per_kg: 2000
    },
    {
        id_jenis: 3,
        nama_jenis: "Kaleng",
        harga_per_kg: 7000
    },
    {
        id_jenis: 4,
        nama_jenis: "Kertas",
        harga_per_kg: 1800
    },
    {
        id_jenis: 5,
        nama_jenis: "Besi",
        harga_per_kg: 4500
    }

];

/* ===== Data Nasabah ===== */
const dataNasabah = [

    {
        id_nasabah: 1,
        nama_nasabah: "Ahmad Fauzi",
        alamat: "Jl. Mawar No.12, Kel. Cibogo, Kec. Cisauk",
        no_hp: "081234567890",
        tanggal_bergabung: "2025-01-10"
    },
    {
        id_nasabah: 2,
        nama_nasabah: "Siti Aminah",
        alamat: "Jl. Melati No.5, Kel. Cibogo, Kec. Cisauk",
        no_hp: "081345678901",
        tanggal_bergabung: "2025-02-15"
    },
    {
        id_nasabah: 3,
        nama_nasabah: "Andi Saputra",
        alamat: "Jl. Kenanga No.8, Kel. Cibogo, Kec. Cisauk",
        no_hp: "081456789012",
        tanggal_bergabung: "2025-03-08"
    },
    {
        id_nasabah: 4,
        nama_nasabah: "Dewi Lestari",
        alamat: "Jl. Anggrek No.3, Kel. Cibogo, Kec. Cisauk",
        no_hp: "081567890123",
        tanggal_bergabung: "2025-03-20"
    },
    {
        id_nasabah: 5,
        nama_nasabah: "Budi Santoso",
        alamat: "Jl. Dahlia No.9, Kel. Cibogo, Kec. Cisauk",
        no_hp: "081678901234",
        tanggal_bergabung: "2025-04-05"
    }

];

/* ===== Data Monitoring ===== */
const dataMonitoring = [

    // Ahmad Fauzi (15 transaksi)

    {
        id_transaksi: 1,
        id_nasabah: 1,
        id_jenis: 1,
        tanggal_setoran: "2025-04-05",
        berat: 2.5,
        harga_per_kg: 5000
    },
    {
        id_transaksi: 2,
        id_nasabah: 1,
        id_jenis: 2,
        tanggal_setoran: "2025-04-12",
        berat: 3,
        harga_per_kg: 2000
    },
    {
        id_transaksi: 3,
        id_nasabah: 1,
        id_jenis: 3,
        tanggal_setoran: "2025-04-20",
        berat: 1.5,
        harga_per_kg: 7000
    },
    {
        id_transaksi: 4,
        id_nasabah: 1,
        id_jenis: 1,
        tanggal_setoran: "2025-05-04",
        berat: 4,
        harga_per_kg: 5000
    },
    {
        id_transaksi: 5,
        id_nasabah: 1,
        id_jenis: 4,
        tanggal_setoran: "2025-05-18",
        berat: 2,
        harga_per_kg: 1800
    },
    {
        id_transaksi: 6,
        id_nasabah: 1,
        id_jenis: 1,
        tanggal_setoran: "2025-06-02",
        berat: 5,
        harga_per_kg: 5000
    },
    {
        id_transaksi: 7,
        id_nasabah: 1,
        id_jenis: 5,
        tanggal_setoran: "2025-06-15",
        berat: 4,
        harga_per_kg: 4500
    },
    {
        id_transaksi: 8,
        id_nasabah: 1,
        id_jenis: 2,
        tanggal_setoran: "2025-06-29",
        berat: 3.5,
        harga_per_kg: 2000
    },
    {
        id_transaksi: 9,
        id_nasabah: 1,
        id_jenis: 1,
        tanggal_setoran: "2025-07-13",
        berat: 6,
        harga_per_kg: 5000
    },
    {
        id_transaksi: 10,
        id_nasabah: 1,
        id_jenis: 3,
        tanggal_setoran: "2025-07-27",
        berat: 2,
        harga_per_kg: 7000
    },
    {
        id_transaksi: 11,
        id_nasabah: 1,
        id_jenis: 1,
        tanggal_setoran: "2025-08-10",
        berat: 5.5,
        harga_per_kg: 5000
    },
    {
        id_transaksi: 12,
        id_nasabah: 1,
        id_jenis: 4,
        tanggal_setoran: "2025-08-24",
        berat: 3,
        harga_per_kg: 1800
    },
    {
        id_transaksi: 13,
        id_nasabah: 1,
        id_jenis: 1,
        tanggal_setoran: "2025-09-07",
        berat: 6.5,
        harga_per_kg: 5000
    },
    {
        id_transaksi: 14,
        id_nasabah: 1,
        id_jenis: 5,
        tanggal_setoran: "2025-09-21",
        berat: 4.5,
        harga_per_kg: 4500
    },
    {
        id_transaksi: 15,
        id_nasabah: 1,
        id_jenis: 1,
        tanggal_setoran: "2025-10-05",
        berat: 7,
        harga_per_kg: 5000
    },

    // Siti Aminah

    {
        id_transaksi: 16,
        id_nasabah: 2,
        id_jenis: 1,
        tanggal_setoran: "2025-04-08",
        berat: 4,
        harga_per_kg: 5000
    },
    {
        id_transaksi: 17,
        id_nasabah: 2,
        id_jenis: 4,
        tanggal_setoran: "2025-04-15",
        berat: 6,
        harga_per_kg: 1800
    },
    {
        id_transaksi: 18,
        id_nasabah: 2,
        id_jenis: 5,
        tanggal_setoran: "2025-05-09",
        berat: 5,
        harga_per_kg: 4500
    },
    {
        id_transaksi: 19,
        id_nasabah: 2,
        id_jenis: 2,
        tanggal_setoran: "2025-06-12",
        berat: 2,
        harga_per_kg: 2000
    },

    // Andi Saputra

    {
        id_transaksi: 20,
        id_nasabah: 3,
        id_jenis: 3,
        tanggal_setoran: "2025-05-05",
        berat: 3,
        harga_per_kg: 7000
    },
    {
        id_transaksi: 21,
        id_nasabah: 3,
        id_jenis: 1,
        tanggal_setoran: "2025-06-18",
        berat: 4.5,
        harga_per_kg: 5000
    },

    // Dewi Lestari

    {
        id_transaksi: 22,
        id_nasabah: 4,
        id_jenis: 2,
        tanggal_setoran: "2025-07-10",
        berat: 5,
        harga_per_kg: 2000
    },
    {
        id_transaksi: 23,
        id_nasabah: 4,
        id_jenis: 4,
        tanggal_setoran: "2025-08-05",
        berat: 3.5,
        harga_per_kg: 1800
    },

    // Budi Santoso

    {
        id_transaksi: 24,
        id_nasabah: 5,
        id_jenis: 5,
        tanggal_setoran: "2025-09-12",
        berat: 6,
        harga_per_kg: 4500
    }

];

/* ===== Data Jadwal ===== */
const dataJadwal = [

    {
        id_jadwal: 1,
        judul_kegiatan: "Sosialisasi Pengelolaan Sampah Rumah Tangga",
        tanggal: "2025-08-10",
        waktu: "09:00",
        lokasi: "Balai Warga RW 04",
        deskripsi: "Edukasi kepada masyarakat mengenai pentingnya pemilahan sampah organik dan anorganik."
    },
    {
        id_jadwal: 2,
        judul_kegiatan: "Penimbangan Sampah Rutin",
        tanggal: "2025-08-17",
        waktu: "08:00",
        lokasi: "Bank Sampah Metro 46",
        deskripsi: "Kegiatan penimbangan dan pencatatan setoran sampah nasabah."
    },
    {
        id_jadwal: 3,
        judul_kegiatan: "Pelatihan Daur Ulang Sampah",
        tanggal: "2025-08-24",
        waktu: "09:30",
        lokasi: "Aula Kelurahan Cibogo",
        deskripsi: "Pelatihan pemanfaatan sampah anorganik menjadi produk bernilai ekonomi."
    },
    {
        id_jadwal: 4,
        judul_kegiatan: "Kerja Bakti Lingkungan",
        tanggal: "2025-09-07",
        waktu: "07:30",
        lokasi: "Lingkungan RW 04",
        deskripsi: "Kegiatan gotong royong membersihkan lingkungan bersama masyarakat."
    }

];

/* ===== Data Profil ===== */
const dataProfil = {

    nama_bank_sampah: "Bank Sampah Metro 46",

    alamat:
        "Jl. Contoh No.46, Kelurahan Cibogo, Kecamatan Cisauk, Kabupaten Tangerang",

    telepon:
        "(021) 12345678",

    email:
        "banksampahmetro46@gmail.com",

    jam_operasional: [
        "Senin - Jumat : 08.00 - 16.00 WIB",
        "Sabtu : 08.00 - 12.00 WIB",
        "Minggu : Libur"
    ],

    deskripsi:
        "Bank Sampah Metro 46 merupakan organisasi masyarakat yang bergerak di bidang pengelolaan sampah melalui kegiatan pemilahan, penimbangan, pencatatan, dan edukasi lingkungan. Kehadiran Bank Sampah Metro 46 bertujuan untuk meningkatkan kepedulian masyarakat terhadap pengelolaan sampah sekaligus memberikan nilai ekonomi dari sampah yang masih memiliki nilai jual."

};

