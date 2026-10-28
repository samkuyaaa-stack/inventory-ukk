<?php
declare(strict_types=1);

$fieldError = static function (string $key) use ($errors): string {
    return (string) ($errors[$key] ?? '');
};
?>
<section class="app-card">
            <div class="card-head">
                <div>
                    <h2 class="card-title"><?= $isEdit ? 'Informasi barang' : 'Formulir barang baru' ?></h2>
                    <p class="card-subtitle">Field bertanda <span class="required-mark">*</span> wajib diisi.</p>
                </div>
                <?php if ($isEdit && !empty($formData['kode_barang'])): ?>
                    <span class="code-badge"><?= e($formData['kode_barang']) ?></span>
                <?php endif; ?>
            </div>

            <?php if ($errors): ?>
                <div class="form-error-box" role="alert">
                    <strong>Data belum dapat disimpan. Periksa bagian berikut:</strong>
                    <ul>
                        <?php foreach ($errors as $message): ?>
                            <li><?= e($message) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= e($formAction) ?>" data-submit-once>
                <?= csrf_field() ?>
                <?php if ($isEdit): ?>
                    <input type="hidden" name="id" value="<?= (int) $formData['id'] ?>">
                <?php endif; ?>

                <div class="form-body">
                    <div class="form-section">
                        <h3 class="form-section-title">Identitas barang</h3>
                        <p class="form-section-desc">Kode barang dibuat otomatis dari nomor data (primary key).</p>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="kode_barang" class="form-label">Kode barang</label>
                                <input type="text"
                                       class="form-control"
                                       id="kode_barang"
                                       value="<?= $isEdit ? e($formData['kode_barang']) : 'Otomatis' ?>"
                                       readonly>
                                <p class="form-hint">
                                    <?= $isEdit
                                        ? 'Kode dibuat otomatis dari nomor data dan tidak dapat diubah.'
                                        : 'Kode dibuat otomatis dari nomor data setelah barang disimpan.' ?>
                                </p>
                            </div>

                            <div class="col-md-8">
                                <label for="nama_barang" class="form-label">
                                    Nama barang <span class="required-mark">*</span>
                                </label>
                                <input type="text"
                                       class="form-control <?= $fieldError('nama_barang') ? 'is-invalid' : '' ?>"
                                       id="nama_barang"
                                       name="nama_barang"
                                       value="<?= e($formData['nama_barang']) ?>"
                                       placeholder="Contoh: Monitor Dell 24 Inch"
                                       minlength="2"
                                       maxlength="150"
                                       autocomplete="off"
                                       required>
                                <?php if ($fieldError('nama_barang')): ?>
                                    <div class="invalid-feedback d-block"><?= e($fieldError('nama_barang')) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6">
                                <label for="category_id" class="form-label">
                                    Kategori <span class="required-mark">*</span>
                                </label>
                                <select class="form-select <?= $fieldError('category_id') ? 'is-invalid' : '' ?>"
                                        id="category_id"
                                        name="category_id"
                                        required>
                                    <option value="">Pilih kategori</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= (int) $category['id'] ?>"
                                            <?= (string) $formData['category_id'] === (string) $category['id'] ? 'selected' : '' ?>>
                                            <?= e($category['nama_kategori']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($fieldError('category_id')): ?>
                                    <div class="invalid-feedback d-block"><?= e($fieldError('category_id')) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="form-section-title">Harga dan stok</h3>
                        <p class="form-section-desc">Nilai persediaan dihitung otomatis dari harga dikali stok.</p>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="harga" class="form-label">
                                    Harga satuan <span class="required-mark">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number"
                                           class="form-control <?= $fieldError('harga') ? 'is-invalid' : '' ?>"
                                           id="harga"
                                           name="harga"
                                           value="<?= e($formData['harga']) ?>"
                                           placeholder="0"
                                           min="0"
                                           max="9999999999999.99"
                                           step="0.01"
                                           inputmode="decimal"
                                           required>
                                </div>
                                <?php if ($fieldError('harga')): ?>
                                    <div class="invalid-feedback d-block"><?= e($fieldError('harga')) ?></div>
                                <?php endif; ?>
                                <p class="form-hint">Masukkan angka tanpa pemisah ribuan. Harga tidak boleh negatif.</p>
                            </div>

                            <div class="col-md-6">
                                <label for="stok" class="form-label">
                                    Stok tersedia <span class="required-mark">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="number"
                                           class="form-control <?= $fieldError('stok') ? 'is-invalid' : '' ?>"
                                           id="stok"
                                           name="stok"
                                           value="<?= e($formData['stok']) ?>"
                                           placeholder="0"
                                           min="0"
                                           max="4294967295"
                                           step="1"
                                           inputmode="numeric"
                                           required>
                                    <span class="input-group-text">unit</span>
                                </div>
                                <?php if ($fieldError('stok')): ?>
                                    <div class="invalid-feedback d-block"><?= e($fieldError('stok')) ?></div>
                                <?php endif; ?>
                                <p class="form-hint">Isi 0 jika stok sedang habis.</p>
                            </div>
                        </div>
                    </div>

                <p class="form-note">
                    Barang dengan stok <?= (int) LOW_STOCK_THRESHOLD ?> unit atau kurang akan ditandai
                    sebagai stok rendah pada dashboard dan tabel barang.
                </p>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><?= e($submitLabel) ?></button>
                    <a href="<?= e($cancelUrl) ?>" class="btn btn-outline-secondary">Batal</a>
                </div>
                </div>
            </form>
</section>
