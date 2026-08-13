<!-- Footer Component -->
<footer class="bg-dark text-white py-4 mt-auto">
    <div class="container text-center">
        <h5 class="fw-bold mb-2"><?= htmlspecialchars($namaBank ?? 'Bank Sampah Metro 46'); ?></h5>
        <p class="mb-2 text-white-50">Sistem Informasi Pengelolaan dan Monitoring Bank Sampah Berbasis Web.</p>
        <small class="text-white-50">&copy; <?= date('Y'); ?> <?= htmlspecialchars($namaBank ?? 'Bank Sampah Metro 46'); ?>. All Rights Reserved.</small>
    </div>
</footer>

<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>