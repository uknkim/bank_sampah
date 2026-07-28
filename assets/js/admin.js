/**
 * ============================================
 * Inisialisasi Admin
 * Dipanggil setelah layout selesai dimuat
 * ============================================
 */

function initializeAdmin() {

    setCurrentDate();

    setActiveMenu();

}


/**
 * ============================================
 * Menampilkan tanggal hari ini
 * ============================================
 */

function setCurrentDate() {

    const dateElement = document.getElementById("current-date");

    if (!dateElement) return;

    const options = {

        weekday: "long",

        day: "numeric",

        month: "long",

        year: "numeric"

    };

    const today = new Date();

    dateElement.textContent = today.toLocaleDateString("id-ID", options);

}


/**
 * ============================================
 * Menentukan menu aktif
 * ============================================
 */

function setActiveMenu() {

    const currentPage = window.location.pathname

        .split("/")

        .pop()

        .replace(".html", "");

    const menus = document.querySelectorAll(".sidebar .nav-link");

    menus.forEach(menu => {

        if (menu.dataset.page === currentPage) {

            menu.classList.add("active");

        }

    });

}