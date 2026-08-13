<style>
.admin-footer {
    background-color: #ffffff;
    border-top: 1px solid rgba(0, 0, 0, 0.06);
    padding: 16px 24px;
    margin-top: auto;
    border-radius: 12px;
    box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.02);
}

.footer-text {
    font-size: 0.825rem;
    color: #64748b;
    margin: 0;
}

.footer-brand-highlight {
    font-weight: 600;
    color: #0f5132; /* Hijau Emerald */
}

.system-badge {
    background-color: #e8f5e9;
    color: #1b5e20;
    font-weight: 600;
    font-size: 0.75rem;
    padding: 4px 10px;
    border-radius: 20px;
    border: 1px solid #c8e6c9;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.system-badge i {
    font-size: 0.7rem;
    color: #2e7d32;
}
</style>

<footer class="admin-footer my-3">
    <div class="container-fluid p-0">
        <div class="row align-items-center gy-2">
            
            <!-- Copyright & Brand -->
            <div class="col-md-6 text-center text-md-start">
                <p class="footer-text">
                    &copy; <?= date('Y'); ?> <span class="footer-brand-highlight">Bank Sampah Metro 46</span>
                </p>
            </div>

            <!-- Deskripsi Sistem / Version Badge -->
            <div class="col-md-6 text-center text-md-end">
                <div class="d-inline-flex align-items-center gap-2">
                    <span class="system-badge">
                        <i class="bi bi-circle-fill"></i> Sistem Informasi Monitoring 
                    </span>
                </div>
            </div>

        </div>
    </div>
</footer>