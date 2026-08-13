/*
========================================
DATA SETORAN
BANK SAMPAH METRO 46
========================================
*/

/* ===== DOM ===== */
const tabelSetoran = document.getElementById("tabelSetoran");
const infoPagination = document.getElementById("infoPaginationSetoran");
const pagination = document.getElementById("paginationSetoran");

const modalTambah = document.getElementById("modalTambahSetoran");
const modalDetail = document.getElementById("modalDetailSetoran");
const modalEdit = document.getElementById("modalEditSetoran");

const formTambah = document.getElementById("formTambahSetoran");
const formEdit = document.getElementById("formEditSetoran");

const selectNasabah = document.getElementById("id_nasabah");
const inputTanggal = document.getElementById("tanggal_setoran");
const tbodyTambah = document.getElementById("detailSetoran");
const grandTotalTambah = document.getElementById("grand_total");
const btnTambahBaris = document.getElementById("btnTambahBaris");

const editIdTransaksi = document.getElementById("edit_id_transaksi");
const editNasabah = document.getElementById("edit_id_nasabah");
const editTanggal = document.getElementById("edit_tanggal_setoran");
const tbodyEdit = document.getElementById("detailSetoranEdit");
const grandTotalEdit = document.getElementById("edit_grand_total");
const btnTambahBarisEdit = document.getElementById("btnTambahBarisEdit");

const detailNama = document.getElementById("detailNamaNasabah");
const detailTanggal = document.getElementById("detailTanggalSetoran");
const detailList = document.getElementById("detailSetoranList");
const detailGrandTotal = document.getElementById("detailGrandTotal");

/* ===== Pagination ===== */
const ITEM_PER_PAGE = 10;
let currentPage = 1;

/* ===== Utility ===== */
function formatTanggal(tanggal) {
    return new Date(tanggal).toLocaleDateString("id-ID", {
        day: "numeric",
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

function generateIdTransaksi() {
    if (dataMonitoring.length === 0) return 1;
    return Math.max(...dataMonitoring.map(item => item.id_transaksi)) + 1;
}

function getNamaNasabah(idNasabah) {
    const nasabah = dataNasabah.find(item => item.id_nasabah === Number(idNasabah));
    return nasabah ? nasabah.nama_nasabah : "-";
}

function getJenisSampah(idJenis) {
    return dataJenisSampah.find(item => item.id_jenis === Number(idJenis));
}

function getDetailTransaksi(idTransaksi) {
    return dataMonitoring.filter(item => item.id_transaksi === Number(idTransaksi));
}

function hitungGrandTotal(detail) {
    return detail.reduce((total, item) => {
        return total + (item.berat * item.harga_per_kg);
    }, 0);
}

function hitungTotalBerat(detail) {
    return detail.reduce((total, item) => {
        return total + item.berat;
    }, 0);
}

function kelompokkanTransaksi() {
    const transaksiMap = new Map();

    dataMonitoring.forEach(item => {
        if (!transaksiMap.has(item.id_transaksi)) {
            transaksiMap.set(item.id_transaksi, []);
        }
        transaksiMap.get(item.id_transaksi).push(item);
    });

    return Array.from(transaksiMap.values()).sort((a, b) => {
        return b[0].id_transaksi - a[0].id_transaksi;
    });
}

/* ===== Select Nasabah ===== */
function renderSelectNasabah() {
    const option = `
        <option value="">
            -- Pilih Nasabah --
        </option>
        ${dataNasabah.map(item => `
            <option value="${item.id_nasabah}">
                ${item.nama_nasabah}
            </option>
        `).join("")}
    `;

    selectNasabah.innerHTML = option;
    editNasabah.innerHTML = option;
}

/* ===== Option Jenis Sampah ===== */
function optionJenis(selected = "") {
    return `
        <option value="">
            -- Pilih Jenis --
        </option>
        ${dataJenisSampah.map(item => `
            <option
                value="${item.id_jenis}"
                ${Number(selected) === item.id_jenis ? "selected" : ""}>
                ${item.nama_jenis}
            </option>
        `).join("")}
    `;
}

/* ===== Pagination ===== */
function getPaginationData(data) {
    const totalData = data.length;
    const totalPage = Math.ceil(totalData / ITEM_PER_PAGE);

    if (currentPage > totalPage && totalPage > 0) {
        currentPage = totalPage;
    }

    const start = (currentPage - 1) * ITEM_PER_PAGE;
    const end = start + ITEM_PER_PAGE;

    return {
        totalData,
        totalPage,
        start,
        end,
        data: data.slice(start, end)
    };
}

function renderPagination(totalData, totalPage) {

    if (totalData === 0) {
        infoPagination.textContent = "Tidak ada data.";
        pagination.innerHTML = "";
        return;
    }

    const awal = ((currentPage - 1) * ITEM_PER_PAGE) + 1;
    const akhir = Math.min(currentPage * ITEM_PER_PAGE, totalData);

    infoPagination.textContent =
        `Menampilkan ${awal}-${akhir} dari ${totalData} data`;

    let html = "";

    html += `
        <li class="page-item ${currentPage === 1 ? "disabled" : ""}">
            <button class="page-link btn-prev">
                <i class="bi bi-chevron-left"></i>
            </button>
        </li>
    `;

    for (let i = 1; i <= totalPage; i++) {
        html += `
            <li class="page-item ${i === currentPage ? "active" : ""}">
                <button
                    class="page-link btn-page"
                    data-page="${i}">
                    ${i}
                </button>
            </li>
        `;
    }

    html += `
        <li class="page-item ${currentPage === totalPage ? "disabled" : ""}">
            <button class="page-link btn-next">
                <i class="bi bi-chevron-right"></i>
            </button>
        </li>
    `;

    pagination.innerHTML = html;
}

/* ===== Render Baris ===== */
function renderBaris(container, data = null) {

    const harga = data
        ? formatRupiah(data.harga_per_kg)
        : formatRupiah(0);

    const subtotal = data
        ? formatRupiah(data.berat * data.harga_per_kg)
        : formatRupiah(0);

    const berat = data
        ? data.berat
        : 1;

    container.insertAdjacentHTML("beforeend", `
        <tr>

            <td>
                <select
                    class="form-select jenis-sampah"
                    required>
                    ${optionJenis(data?.id_jenis)}
                </select>
            </td>

            <td>
                <input
                    type="number"
                    class="form-control berat"
                    min="0.1"
                    step="0.1"
                    value="${berat}"
                    required>
            </td>

            <td>
                <input
                    type="text"
                    class="form-control harga"
                    value="${harga}"
                    readonly>
            </td>

            <td>
                <input
                    type="text"
                    class="form-control subtotal"
                    value="${subtotal}"
                    readonly>
            </td>

            <td class="text-center">
                <button
                    type="button"
                    class="btn btn-danger btn-sm btn-hapus-baris">
                    <i class="bi bi-trash"></i>
                </button>
            </td>

        </tr>
    `);
}

function tambahBarisTambah() {
    renderBaris(tbodyTambah);
}

function tambahBarisEdit() {
    renderBaris(tbodyEdit);
}

/* ===== Hitung Baris ===== */
function hitungBaris(baris, inputGrandTotal) {

    const selectJenis = baris.querySelector(".jenis-sampah");
    const inputBerat = baris.querySelector(".berat");
    const inputHarga = baris.querySelector(".harga");
    const inputSubtotal = baris.querySelector(".subtotal");

    const jenis = getJenisSampah(selectJenis.value);

    const harga = jenis ? jenis.harga_per_kg : 0;
    const berat = Number(inputBerat.value) || 0;
    const subtotal = harga * berat;

    inputHarga.value = formatRupiah(harga);
    inputSubtotal.value = formatRupiah(subtotal);

    hitungGrandTotalForm(inputGrandTotal);
}

/* ===== Grand Total Form ===== */
function hitungGrandTotalForm(inputGrandTotal) {

    const tbody = inputGrandTotal === grandTotalTambah
        ? tbodyTambah
        : tbodyEdit;

    let total = 0;

    tbody.querySelectorAll("tr").forEach(baris => {

        const jenis = getJenisSampah(
            baris.querySelector(".jenis-sampah").value
        );

        if (!jenis) return;

        const berat = Number(
            baris.querySelector(".berat").value
        ) || 0;

        total += berat * jenis.harga_per_kg;

    });

    inputGrandTotal.value = formatRupiah(total);

}

/* ===== Refresh Semua Baris ===== */
function refreshBaris(container, inputGrandTotal) {

    container.querySelectorAll("tr").forEach(baris => {
        hitungBaris(baris, inputGrandTotal);
    });

}

/* ===== Hapus Baris ===== */
function hapusBaris(button, container, inputGrandTotal) {

    if (container.children.length === 1) {
        alert("Minimal harus ada satu jenis sampah.");
        return;
    }

    button.closest("tr").remove();

    hitungGrandTotalForm(inputGrandTotal);

}

/* ===== Reset Form Tambah ===== */
function resetFormTambah() {

    formTambah.reset();

    tbodyTambah.innerHTML = "";

    renderBaris(tbodyTambah);

    grandTotalTambah.value = formatRupiah(0);

}

/* ===== Reset Form Edit ===== */
function resetFormEdit() {

    formEdit.reset();

    editIdTransaksi.value = "";

    tbodyEdit.innerHTML = "";

    renderBaris(tbodyEdit);

    grandTotalEdit.value = formatRupiah(0);

}

/* ===== Data Transaksi ===== */
function getDataTransaksi() {

    return kelompokkanTransaksi().map(detail => {

        const transaksi = detail[0];

        return {
            id_transaksi: transaksi.id_transaksi,
            id_nasabah: transaksi.id_nasabah,
            nama_nasabah: getNamaNasabah(transaksi.id_nasabah),
            tanggal_setoran: transaksi.tanggal_setoran,
            total_berat: hitungTotalBerat(detail),
            grand_total: hitungGrandTotal(detail),
            detail
        };

    });

}

/* ===== Render Table ===== */
function renderTable() {

    const semuaTransaksi = getDataTransaksi();

    const paginationData = getPaginationData(
        semuaTransaksi
    );

    renderPagination(
        paginationData.totalData,
        paginationData.totalPage
    );

    tabelSetoran.innerHTML = "";

    if (paginationData.totalData === 0) {

        tabelSetoran.innerHTML = `
            <tr>

                <td
                    colspan="6"
                    class="text-center text-muted py-4">

                    Belum ada data setoran.

                </td>

            </tr>
        `;

        return;

    }

    paginationData.data.forEach((item, index) => {

        tabelSetoran.insertAdjacentHTML("beforeend", `
            <tr>

                <td>
                    ${(paginationData.start + index) + 1}
                </td>

                <td>
                    ${item.nama_nasabah}
                </td>

                <td>
                    ${formatTanggal(item.tanggal_setoran)}
                </td>

                <td>
                    ${item.total_berat} Kg
                </td>

                <td class="fw-semibold text-success">
                    ${formatRupiah(item.grand_total)}
                </td>

                <td>

                    <div
                        class="d-flex justify-content-center gap-2">

                        <button
                            class="btn btn-info btn-sm btn-detail"
                            data-id="${item.id_transaksi}"
                            title="Detail">

                            <i class="bi bi-eye"></i>

                        </button>

                        <button
                            class="btn btn-warning btn-sm btn-edit"
                            data-id="${item.id_transaksi}"
                            title="Edit">

                            <i class="bi bi-pencil-square"></i>

                        </button>

                        <button
                            class="btn btn-danger btn-sm btn-hapus"
                            data-id="${item.id_transaksi}"
                            title="Hapus">

                            <i class="bi bi-trash"></i>

                        </button>

                    </div>

                </td>

            </tr>
        `);

    });

}

/* ===== Modal Detail ===== */
function renderDetailItems(detail) {

    return detail.map(item => {

        const jenis = getJenisSampah(item.id_jenis);

        return `
            <div class="card border-0 shadow-sm mb-3">

                <div class="card-body">

                    <h6 class="fw-bold text-primary mb-3">
                        ${jenis ? jenis.nama_jenis : "-"}
                    </h6>

                    <div class="row g-2">

                        <div class="col-6 text-muted">
                            Berat
                        </div>

                        <div class="col-6 text-end fw-semibold">
                            ${item.berat} Kg
                        </div>

                        <div class="col-6 text-muted">
                            Harga / Kg
                        </div>

                        <div class="col-6 text-end fw-semibold">
                            ${formatRupiah(item.harga_per_kg)}
                        </div>

                        <div class="col-6 text-muted">
                            Subtotal
                        </div>

                        <div class="col-6 text-end fw-bold text-success">
                            ${formatRupiah(item.berat * item.harga_per_kg)}
                        </div>

                    </div>

                </div>

            </div>
        `;

    }).join("");

}

function bukaModalDetail(idTransaksi) {

    const transaksi = getDataTransaksi().find(item =>
        item.id_transaksi === Number(idTransaksi)
    );

    if (!transaksi) return;

    detailNama.textContent = transaksi.nama_nasabah;

    detailTanggal.textContent =
        formatTanggal(transaksi.tanggal_setoran);

    detailList.innerHTML =
        renderDetailItems(transaksi.detail);

    detailGrandTotal.textContent =
        formatRupiah(transaksi.grand_total);

    const modal = new bootstrap.Modal(modalDetail);

    modal.show();

}

/* ===== Modal Edit ===== */
function bukaModalEdit(idTransaksi) {

    const transaksi = getDataTransaksi().find(item =>
        item.id_transaksi === Number(idTransaksi)
    );

    if (!transaksi) return;

    resetFormEdit();

    editIdTransaksi.value = transaksi.id_transaksi;
    editNasabah.value = transaksi.id_nasabah;
    editTanggal.value = transaksi.tanggal_setoran;

    tbodyEdit.innerHTML = "";

    transaksi.detail.forEach(item => {
        renderBaris(tbodyEdit, item);
    });

    refreshBaris(
        tbodyEdit,
        grandTotalEdit
    );

    const modal = new bootstrap.Modal(modalEdit);

    modal.show();

}

/* ===== Update Setoran ===== */
function updateSetoran(event) {

    event.preventDefault();

    const idTransaksi = Number(
        editIdTransaksi.value
    );

    for (let i = dataMonitoring.length - 1; i >= 0; i--) {

        if (dataMonitoring[i].id_transaksi === idTransaksi) {
            dataMonitoring.splice(i, 1);
        }

    }

    tbodyEdit.querySelectorAll("tr").forEach(baris => {

        const idJenis = Number(
            baris.querySelector(".jenis-sampah").value
        );

        const berat = Number(
            baris.querySelector(".berat").value
        );

        const jenis = getJenisSampah(idJenis);

        if (!jenis) return;

        dataMonitoring.push({

            id_transaksi: idTransaksi,

            id_nasabah: Number(
                editNasabah.value
            ),

            id_jenis: idJenis,

            tanggal_setoran: editTanggal.value,

            berat,

            harga_per_kg: jenis.harga_per_kg

        });

    });

    bootstrap.Modal
        .getInstance(modalEdit)
        .hide();

    refreshData();

}

/* ===== Tambah Setoran ===== */
function tambahSetoran(event) {

    event.preventDefault();

    const idNasabah = Number(selectNasabah.value);
    const tanggal = inputTanggal.value;

    if (!idNasabah) {
        alert("Silakan pilih nasabah.");
        return;
    }

    if (!tanggal) {
        alert("Silakan pilih tanggal setoran.");
        return;
    }

    const idTransaksi = generateIdTransaksi();

    tbodyTambah.querySelectorAll("tr").forEach(baris => {

        const idJenis = Number(
            baris.querySelector(".jenis-sampah").value
        );

        const berat = Number(
            baris.querySelector(".berat").value
        );

        const jenis = getJenisSampah(idJenis);

        if (!jenis) return;

        dataMonitoring.push({

            id_transaksi: idTransaksi,

            id_nasabah: idNasabah,

            id_jenis: idJenis,

            tanggal_setoran: tanggal,

            berat,

            harga_per_kg: jenis.harga_per_kg

        });

    });

    bootstrap.Modal
        .getInstance(modalTambah)
        .hide();

    refreshData();

}

/* ===== Hapus Setoran ===== */
function hapusSetoran(idTransaksi) {

    const transaksi = getDataTransaksi().find(item =>
        item.id_transaksi === Number(idTransaksi)
    );

    if (!transaksi) return;

    const konfirmasi = confirm(
        `Hapus seluruh data setoran milik ${transaksi.nama_nasabah}?\n\nTindakan ini tidak dapat dibatalkan.`
    );

    if (!konfirmasi) return;

    for (let i = dataMonitoring.length - 1; i >= 0; i--) {

        if (dataMonitoring[i].id_transaksi === Number(idTransaksi)) {
            dataMonitoring.splice(i, 1);
        }

    }

    const totalPage = Math.ceil(
        getDataTransaksi().length / ITEM_PER_PAGE
    );

    if (currentPage > totalPage && currentPage > 1) {
        currentPage--;
    }

    refreshData();

}

/* ===== Refresh ===== */
function refreshData() {

    renderTable();

    resetFormTambah();

    resetFormEdit();

}

/* ===== Reset Semua Modal ===== */
function resetSemuaModal() {

    resetFormTambah();

    resetFormEdit();

    detailNama.textContent = "";

    detailTanggal.textContent = "";

    detailList.innerHTML = "";

    detailGrandTotal.textContent = formatRupiah(0);

}

/* ===== Event ===== */
btnTambahBaris.addEventListener("click", () => {
    renderBaris(tbodyTambah);
});

btnTambahBarisEdit.addEventListener("click", () => {
    renderBaris(tbodyEdit);
});

formTambah.addEventListener("submit", tambahSetoran);

formEdit.addEventListener("submit", updateSetoran);

tabelSetoran.addEventListener("click", (e) => {

    const button = e.target.closest("button");

    if (!button) return;

    const id = Number(button.dataset.id);

    if (button.classList.contains("btn-detail")) {
        bukaModalDetail(id);
        return;
    }

    if (button.classList.contains("btn-edit")) {
        bukaModalEdit(id);
        return;
    }

    if (button.classList.contains("btn-hapus")) {
        hapusSetoran(id);
    }

});

pagination.addEventListener("click", (e) => {

    e.preventDefault();

    const button = e.target.closest("[data-page]");

    if (!button) return;

    const page = Number(button.dataset.page);

    if (page === currentPage) return;

    currentPage = page;

    renderTable();

});

document.addEventListener("change", (e) => {

    const baris = e.target.closest("tr");

    if (!baris) return;

    if (
        e.target.classList.contains("jenis-sampah") ||
        e.target.classList.contains("berat")
    ) {

        const tbody = baris.closest("tbody");

        if (tbody === tbodyTambah) {
            hitungBaris(baris, grandTotalTambah);
        } else if (tbody === tbodyEdit) {
            hitungBaris(baris, grandTotalEdit);
        }

    }

});

document.addEventListener("click", (e) => {

    const button = e.target.closest(".btn-hapus-baris");

    if (!button) return;

    const tbody = button.closest("tbody");

    if (tbody === tbodyTambah) {
        hapusBaris(button, tbodyTambah, grandTotalTambah);
    } else if (tbody === tbodyEdit) {
        hapusBaris(button, tbodyEdit, grandTotalEdit);
    }

});

modalTambah.addEventListener("hidden.bs.modal", () => {
    resetFormTambah();
});

modalEdit.addEventListener("hidden.bs.modal", () => {
    resetFormEdit();
});

modalDetail.addEventListener("hidden.bs.modal", () => {

    detailNama.textContent = "";
    detailTanggal.textContent = "";
    detailList.innerHTML = "";
    detailGrandTotal.textContent = formatRupiah(0);

});

/* ===== Init ===== */
function init() {

    renderSelectNasabah();

    resetFormTambah();

    resetFormEdit();

    renderTable();

}

document.addEventListener("DOMContentLoaded", init);