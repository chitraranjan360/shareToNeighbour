</main>

<footer class="footer-premium text-light py-5 mt-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <h5 class="d-flex align-items-center gap-2 mb-3">
                    <span class="brand-icon footer-icon d-inline-flex align-items-center justify-content-center">
                        <i class="bi bi-house-heart-fill"></i>
                    </span>
                    <?= SITE_NAME ?>
                </h5>
                <p class="text-muted small mb-0">
                    Connecting Copenhagen neighbours to share and reuse furniture —
                    because sustainability starts next door.
                </p>
            </div>

            <div class="col-md-4">
                <h6 class="text-uppercase small fw-semibold text-white-50">Quick Links</h6>
                <ul class="list-unstyled small mb-0">
                    <li><a href="<?= SITE_URL ?>/browse.php" class="footer-link">Browse Furniture</a></li>
                    <li><a href="<?= SITE_URL ?>/register.php" class="footer-link">Join Community</a></li>
                </ul>
            </div>

            <div class="col-md-4">
                <h6 class="text-uppercase small fw-semibold text-white-50">Admin Access</h6>
                <ul class="list-unstyled small mb-0">
                    <li>
                        <a href="<?= ADMIN_URL ?>/login.php" class="footer-link d-inline-flex align-items-center gap-1">
                            <i class="bi bi-shield-lock"></i> Admin Panel
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <hr class="border-secondary opacity-25 my-4">

        <p class="text-center text-muted small mb-0">
            &copy; <?= date('Y') ?> <?= SITE_NAME ?> — Copenhagen, Denmark
        </p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= SITE_URL ?>/js/app.js"></script>
</body>
</html>