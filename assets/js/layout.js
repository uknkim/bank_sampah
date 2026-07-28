/**
 * ============================================
 * Layout Loader
 * Memuat komponen layout admin
 * ============================================
 */

document.addEventListener("DOMContentLoaded", async () => {

    await loadComponent("sidebar", "components/sidebar.html");

    await loadComponent("navbar", "components/navbar.html");

    await loadComponent("footer", "components/footer.html");

    initializeAdmin();

});


/**
 * ============================================
 * Load HTML Component
 * ============================================
 */

async function loadComponent(id, file) {

    const container = document.getElementById(id);

    if (!container) return;

    try {

        const response = await fetch(file);

        if (!response.ok) {

            throw new Error(`Gagal memuat ${file}`);

        }

        container.innerHTML = await response.text();

    }

    catch (error) {

        console.error(error);

    }

}