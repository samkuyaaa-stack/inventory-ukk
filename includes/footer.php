<?php
declare(strict_types=1);
?>
<?php if (($layout ?? 'app') === 'guest'): ?>
            </main>

            <p class="auth-copyright">
                &copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. Dibuat untuk latihan UKK Junior Web Developer.
            </p>
        </div>
    </div>
<?php else: ?>
                </div>
            </main>

            <footer class="app-footer">
                <div class="app-container footer-inner">
                    <span>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?></span>
                    <span>Latihan UKK Junior Web Developer</span>
                </div>
            </footer>
        </div>
    </div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(app_url('assets/js/script.js')) ?>"></script>
</body>
</html>
