<?php
// admin/add_product.php
require_once "header.php";

// Get categories dari tabel cust_categories untuk dropdown
$categoriesResult = supabase("cust_categories", "GET", null, [
    "select" => "id, name",
    "order" => "name.asc",
]);

$categories = [];
if (isset($categoriesResult["data"]) && is_array($categoriesResult["data"])) {
    $categories = $categoriesResult["data"];
}
?>

<!-- Include Sidebar -->
<?php include "sidebar.php"; ?>

<!-- Main Content -->
<div class="main-content">

    <!-- Include Header Content -->
    <?php include "header-content.php"; ?>

    <!-- Content khusus halaman tambah produk -->
    <div class="row">
        <div class="col-12">
            <div class="card card-custom">
                <div class="card-header-custom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Tambah Produk Baru</h5>
                    <a href="products.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Kembali ke Produk
                    </a>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION["error"])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($_SESSION["error"]) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION["error"]); ?>
                    <?php endif; ?>

                    <?php if (isset($_SESSION["success"])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($_SESSION["success"]) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION["success"]); ?>
                    <?php endif; ?>

                    <form action="process_add_product.php" method="POST" enctype="multipart/form-data" id="productForm">
                        <div class="row">
                            <div class="col-md-8">
                                <!-- Informasi Dasar -->
                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informasi Dasar</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-12 mb-3">
                                                <label for="product_name" class="form-label">Nama Produk <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="product_name" name="name" required>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="category_id" class="form-label">Kategori <span class="text-danger">*</span></label>
                                                <select class="form-select" id="category_id" name="category_id" required>
                                                    <option value="">Pilih Kategori</option>
                                                    <?php if (
                                                        !empty($categories) &&
                                                        is_array($categories)
                                                    ): ?>
                                                        <?php foreach (
                                                            $categories
                                                            as $category
                                                        ): ?>
                                                            <option value="<?= $category[
                                                                "id"
                                                            ] ?>"><?= htmlspecialchars(
    $category["name"],
) ?></option>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <option value="" disabled>Tidak ada kategori. Silakan buat kategori terlebih dahulu.</option>
                                                    <?php endif; ?>
                                                </select>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="price" class="form-label">Harga (Rp) <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <span class="input-group-text">Rp</span>
                                                    <input type="number" class="form-control" id="price" name="price" min="0" step="100" required>
                                                </div>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="stock" class="form-label">Stok <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control" id="stock" name="stock" min="0" value="0" required>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="short_description" class="form-label">Deskripsi Singkat</label>
                                            <textarea class="form-control" id="short_description" name="short_description" rows="2" maxlength="500"></textarea>
                                            <small class="text-muted">Maksimal 500 karakter</small>
                                        </div>

                                        <div class="mb-3">
                                            <label for="description" class="form-label">Deskripsi Lengkap</label>
                                            <textarea class="form-control" id="description" name="description" rows="4"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <!-- Gambar Produk -->
                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-image me-2"></i>Gambar Produk</h6>
                                    </div>
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <div id="imagePreview" style="width: 100%; height: 200px; border: 2px dashed #ddd; border-radius: 10px; display: flex; align-items: center; justify-content: center; background: #f8f9fa; margin-bottom: 15px; cursor: pointer;" onclick="document.getElementById('image_url').click()">
                                                <div class="text-center">
                                                    <i class="fas fa-cloud-upload-alt fa-3x text-muted"></i>
                                                    <p class="text-muted mt-2">Klik untuk Upload Gambar</p>
                                                </div>
                                            </div>
                                            <input type="file" class="form-control d-none" id="image_url" name="image_url" accept="image/*" onchange="previewImage(this)">
                                            <input type="text" class="form-control mt-2" name="image_url_text" placeholder="Atau masukkan URL gambar" id="image_url_text">
                                            <small class="text-muted">Upload gambar atau masukkan URL gambar produk</small>
                                        </div>

                                        <div class="mb-3">
                                            <label for="product_images" class="form-label">Gambar Tambahan (URLs)</label>
                                            <textarea class="form-control" id="product_images" name="product_images" rows="3" placeholder="Masukkan URL gambar tambahan, pisahkan dengan koma"></textarea>
                                            <small class="text-muted">Contoh: https://example.com/img1.jpg, https://example.com/img2.jpg</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tombol Aksi -->
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-grid gap-2">
                                            <button type="submit" class="btn btn-primary btn-lg">
                                                <i class="fas fa-save me-2"></i>Simpan Produk
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary" onclick="window.history.back()">
                                                <i class="fas fa-times me-2"></i>Batal
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .form-label {
        font-weight: 600;
        margin-bottom: 5px;
    }

    .card {
        border: 1px solid #e3e6f0;
        border-radius: 10px;
    }

    .card-header.bg-light {
        background-color: #f8f9fc !important;
        border-bottom: 1px solid #e3e6f0;
    }
</style>

<script>
// Preview gambar sebelum upload
function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    const urlText = document.getElementById('image_url_text');

    if (input.files && input.files[0]) {
        const reader = new FileReader();

        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" style="width: 100%; height: 100%; object-fit: contain; border-radius: 8px;">`;
            urlText.value = e.target.result; // Data URL
        }

        reader.readAsDataURL(input.files[0]);
    }
}

// Handle klik pada preview untuk pilih file
document.getElementById('imagePreview').addEventListener('click', function() {
    document.getElementById('image_url').click();
});

// Validasi form sebelum submit
document.getElementById('productForm').addEventListener('submit', function(e) {
    const price = document.getElementById('price').value;
    const stock = document.getElementById('stock').value;

    if (price < 0) {
        e.preventDefault();
        alert('Harga tidak boleh negatif!');
        document.getElementById('price').focus();
        return false;
    }

    if (stock < 0) {
        e.preventDefault();
        alert('Stok tidak boleh negatif!');
        document.getElementById('stock').focus();
        return false;
    }

    // Cek apakah ada kategori
    const category = document.getElementById('category_id').value;
    if (!category) {
        e.preventDefault();
        alert('Silakan pilih kategori!');
        return false;
    }

    return true;
});
</script>

<?php include "footer.php"; ?>
