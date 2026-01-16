<?php
// admin/edit_product.php
require_once "header.php";

$productId = $_GET["id"] ?? "";

if (empty($productId)) {
    $_SESSION["error"] = "ID produk tidak valid!";
    header("Location: products.php");
    exit();
}

// Get product details
$productResult = supabase("cust_products", "GET", null, [
    "id" => "eq." . $productId,
    "select" => "*",
]);

$product = [];
if (isset($productResult["data"][0]) && is_array($productResult["data"][0])) {
    $product = $productResult["data"][0];
} else {
    $_SESSION["error"] = "Produk tidak ditemukan!";
    header("Location: products.php");
    exit();
}

// Get categories untuk dropdown
$categoriesResult = supabase("cust_categories", "GET", null, [
    "select" => "id, name",
    "order" => "name.asc",
]);

$categories = [];
if (isset($categoriesResult["data"]) && is_array($categoriesResult["data"])) {
    $categories = $categoriesResult["data"];
}

// Prepare image data
$currentImage = $product["image_url"] ?? "";
$placeholder = "https://via.placeholder.com/300x300/007bff/ffffff?text=PRODUK";

// Prepare product images text
$productImagesText = "";
if (!empty($product["product_images"])) {
    if (is_array($product["product_images"])) {
        $productImagesText = implode(", ", $product["product_images"]);
    } else {
        $productImagesText = $product["product_images"];
    }
}
?>

<!-- Include Sidebar -->
<?php include "sidebar.php"; ?>

<!-- Main Content -->
<div class="main-content">

    <!-- Include Header Content -->
    <?php include "header-content.php"; ?>

    <!-- Content khusus halaman edit produk -->
    <div class="row">
        <div class="col-12">
            <div class="card card-custom">
                <div class="card-header-custom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Produk</h5>
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

                    <form action="process_edit_product.php" method="POST" enctype="multipart/form-data" id="editProductForm">
                        <input type="hidden" name="id" value="<?= htmlspecialchars(
                            $productId,
                        ) ?>">

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
                                                <input type="text" class="form-control" id="product_name" name="name"
                                                       value="<?= htmlspecialchars(
                                                           $product["name"] ??
                                                               "",
                                                       ) ?>" required>
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
                                                            ] ?>"
                                                                <?= ($product[
                                                                    "category_id"
                                                                ] ??
                                                                    "") ==
                                                                $category["id"]
                                                                    ? "selected"
                                                                    : "" ?>>
                                                                <?= htmlspecialchars(
                                                                    $category[
                                                                        "name"
                                                                    ],
                                                                ) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <option value="" disabled>Tidak ada kategori</option>
                                                    <?php endif; ?>
                                                </select>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="price" class="form-label">Harga (Rp) <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <span class="input-group-text">Rp</span>
                                                    <input type="number" class="form-control" id="price" name="price"
                                                           min="0" step="100" value="<?= $product[
                                                               "price"
                                                           ] ?? 0 ?>" required>
                                                </div>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="stock" class="form-label">Stok <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control" id="stock" name="stock"
                                                       min="0" value="<?= $product[
                                                           "stock"
                                                       ] ?? 0 ?>" required>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="likes_count" class="form-label">Likes</label>
                                                <input type="number" class="form-control" id="likes_count" name="likes_count"
                                                       min="0" value="<?= $product[
                                                           "likes_count"
                                                       ] ?? 0 ?>">
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="short_description" class="form-label">Deskripsi Singkat</label>
                                            <textarea class="form-control" id="short_description" name="short_description"
                                                      rows="2" maxlength="500"><?= htmlspecialchars(
                                                          $product[
                                                              "short_description"
                                                          ] ?? "",
                                                      ) ?></textarea>
                                            <div id="charCounter" class="text-muted small mt-1">
                                                <?= strlen(
                                                    $product[
                                                        "short_description"
                                                    ] ?? "",
                                                ) ?>/500 karakter
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="description" class="form-label">Deskripsi Lengkap</label>
                                            <textarea class="form-control" id="description" name="description"
                                                      rows="4"><?= htmlspecialchars(
                                                          $product[
                                                              "description"
                                                          ] ?? "",
                                                      ) ?></textarea>
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
                                        <!-- Preview Gambar Saat Ini -->
                                        <div class="mb-3">
                                            <label class="form-label d-block text-start mb-2">Gambar Saat Ini:</label>
                                            <div id="currentImagePreview" class="mb-3" style="min-height: 200px;">
                                                <?php if (
                                                    !empty($currentImage) &&
                                                    (strpos(
                                                        $currentImage,
                                                        "data:image",
                                                    ) === 0 ||
                                                        filter_var(
                                                            $currentImage,
                                                            FILTER_VALIDATE_URL,
                                                        ))
                                                ): ?>
                                                    <img id="currentImage"
                                                         src="<?= htmlspecialchars(
                                                             $currentImage,
                                                         ) ?>"
                                                         class="img-fluid rounded border"
                                                         style="max-height: 200px; max-width: 100%; object-fit: contain;"
                                                         onerror="this.src='<?= $placeholder ?>'"
                                                         alt="Gambar Produk Saat Ini">
                                                <?php else: ?>
                                                    <div class="d-flex flex-column align-items-center justify-content-center bg-light rounded border"
                                                         style="height: 200px;">
                                                        <i class="fas fa-wine-bottle fa-3x text-muted mb-2"></i>
                                                        <p class="text-muted mb-0">Belum ada gambar</p>
                                                        <small class="text-muted">Format: .jpg, .png, .gif</small>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <input type="hidden" name="current_image_url" value="<?= htmlspecialchars(
                                                $currentImage,
                                            ) ?>">
                                        </div>

                                        <!-- Upload Gambar Baru -->
                                        <div class="mb-3">
                                            <label for="image_url" class="form-label d-block text-start mb-2">Upload Gambar Baru:</label>
                                            <div class="image-upload-container">
                                                <div id="imageUploadArea"
                                                     class="border-dashed rounded p-3 text-center cursor-pointer"
                                                     style="border: 2px dashed #dee2e6; background: #f8f9fa;"
                                                     onclick="document.getElementById('image_url').click()">
                                                    <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                                                    <p class="mb-1">Klik untuk upload gambar</p>
                                                    <small class="text-muted">atau drag & drop file di sini</small>
                                                    <p class="text-muted mt-2 mb-0">
                                                        <small>Max. 2MB • JPG, PNG, GIF, WebP</small>
                                                    </p>
                                                </div>

                                                <input type="file" class="form-control d-none" id="image_url" name="image_url"
                                                       accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                                                       onchange="previewUploadedImage(this)">
                                                <div id="uploadPreview" class="mt-3"></div>
                                            </div>
                                        </div>

                                        <!-- Input URL Gambar -->
                                        <div class="mb-3">
                                            <label for="image_url_text" class="form-label d-block text-start mb-2">
                                                Atau Masukkan URL Gambar:
                                            </label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" name="image_url_text" id="image_url_text"
                                                       placeholder="https://example.com/gambar.jpg"
                                                       value="<?= htmlspecialchars(
                                                           $currentImage,
                                                       ) ?>">
                                                <button type="button" class="btn btn-outline-secondary" onclick="testImageUrl()">
                                                    <i class="fas fa-search"></i>
                                                </button>
                                            </div>
                                            <div id="urlTestResult" class="mt-2 small"></div>
                                        </div>

                                        <!-- Gambar Tambahan -->
                                        <div class="mb-3">
                                            <label for="product_images" class="form-label d-block text-start mb-2">Gambar Tambahan (URLs)</label>
                                            <textarea class="form-control" id="product_images" name="product_images"
                                                      rows="3" placeholder="Masukkan URL gambar tambahan, pisahkan dengan koma"><?= htmlspecialchars(
                                                          $productImagesText,
                                                      ) ?></textarea>
                                            <small class="text-muted d-block mt-1">
                                                Contoh: https://example.com/img1.jpg, https://example.com/img2.jpg
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tombol Aksi -->
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-grid gap-2">
                                            <button type="submit" class="btn btn-primary btn-lg">
                                                <i class="fas fa-save me-2"></i>Update Produk
                                            </button>
                                            <a href="view_product.php?id=<?= $productId ?>" class="btn btn-outline-info">
                                                <i class="fas fa-eye me-2"></i>Lihat Produk
                                            </a>
                                            <a href="products.php" class="btn btn-outline-secondary">
                                                <i class="fas fa-times me-2"></i>Batal
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Info Produk -->
                                <div class="card mt-3">
                                    <div class="card-body">
                                        <h6><i class="fas fa-info-circle me-2"></i>Info Produk</h6>
                                        <ul class="list-unstyled small">
                                            <li><strong>ID:</strong> <?= substr(
                                                $productId,
                                                0,
                                                8,
                                            ) ?>...</li>
                                            <li><strong>Dibuat:</strong>
                                                <?php if (
                                                    !empty(
                                                        $product["created_at"]
                                                    )
                                                ): ?>
                                                    <?= date(
                                                        "d-m-Y H:i",
                                                        strtotime(
                                                            $product[
                                                                "created_at"
                                                            ],
                                                        ),
                                                    ) ?>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </li>
                                            <li><strong>Diupdate:</strong>
                                                <?php if (
                                                    !empty(
                                                        $product["updated_at"]
                                                    )
                                                ): ?>
                                                    <?= date(
                                                        "d-m-Y H:i",
                                                        strtotime(
                                                            $product[
                                                                "updated_at"
                                                            ],
                                                        ),
                                                    ) ?>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </li>
                                        </ul>
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

    .border-dashed {
        border-style: dashed !important;
    }

    .cursor-pointer {
        cursor: pointer;
    }

    #imageUploadArea:hover {
        background: #e9ecef !important;
        border-color: #007bff !important;
    }

    #uploadPreview img {
        max-height: 150px;
        max-width: 100%;
        object-fit: contain;
    }
</style>

<script>
// Preview gambar yang di-upload
function previewUploadedImage(input) {
    const preview = document.getElementById('uploadPreview');
    const uploadArea = document.getElementById('imageUploadArea');

    if (input.files && input.files[0]) {
        const file = input.files[0];
        const maxSize = 2 * 1024 * 1024; // 2MB
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];

        // Validasi ukuran file
        if (file.size > maxSize) {
            alert('File terlalu besar! Maksimal 2MB.');
            input.value = '';
            return;
        }

        // Validasi tipe file
        if (!allowedTypes.includes(file.type)) {
            alert('Format file tidak didukung! Gunakan JPG, PNG, GIF, atau WebP.');
            input.value = '';
            return;
        }

        const reader = new FileReader();

        reader.onload = function(e) {
            // Update upload area
            uploadArea.innerHTML = `
                <div class="text-success">
                    <i class="fas fa-check-circle fa-2x mb-2"></i>
                    <p class="mb-1">File terpilih: ${file.name}</p>
                    <small class="text-muted">${(file.size / 1024).toFixed(2)} KB</small>
                    <p class="mt-2 mb-0">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="removeUploadedImage()">
                            <i class="fas fa-times me-1"></i>Hapus
                        </button>
                    </p>
                </div>
            `;

            // Tampilkan preview gambar
            preview.innerHTML = `
                <div class="card">
                    <div class="card-header bg-light py-1">
                        <small>Preview Gambar Baru</small>
                    </div>
                    <div class="card-body text-center p-2">
                        <img src="${e.target.result}"
                             class="img-fluid rounded"
                             style="max-height: 150px; max-width: 100%; object-fit: contain;"
                             alt="Preview">
                    </div>
                </div>
            `;

            // Set URL text input ke kosong karena menggunakan file upload
            document.getElementById('image_url_text').value = '';
        };

        reader.readAsDataURL(file);
    }
}

// Hapus gambar yang di-upload
function removeUploadedImage() {
    const input = document.getElementById('image_url');
    const uploadArea = document.getElementById('imageUploadArea');
    const preview = document.getElementById('uploadPreview');

    input.value = '';
    preview.innerHTML = '';

    uploadArea.innerHTML = `
        <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
        <p class="mb-1">Klik untuk upload gambar</p>
        <small class="text-muted">atau drag & drop file di sini</small>
        <p class="text-muted mt-2 mb-0">
            <small>Max. 2MB • JPG, PNG, GIF, WebP</small>
        </p>
    `;
}

// Test URL gambar
function testImageUrl() {
    const urlInput = document.getElementById('image_url_text');
    const testResult = document.getElementById('urlTestResult');
    const currentImage = document.getElementById('currentImage');

    if (!urlInput.value.trim()) {
        testResult.innerHTML = '<span class="text-danger">URL tidak boleh kosong</span>';
        return;
    }

    // Validasi URL
    const urlPattern = /^(https?:\/\/.*\.(?:png|jpg|jpeg|gif|webp|bmp))(?:\?.*)?$/i;
    if (!urlPattern.test(urlInput.value)) {
        testResult.innerHTML = '<span class="text-warning">URL harus berupa gambar (png, jpg, jpeg, gif, webp, bmp)</span>';
        return;
    }

    testResult.innerHTML = '<span class="text-info"><i class="fas fa-spinner fa-spin me-1"></i>Testing URL...</span>';

    // Test dengan Image object
    const img = new Image();

    img.onload = function() {
        testResult.innerHTML = '<span class="text-success"><i class="fas fa-check-circle me-1"></i>URL valid, gambar dapat diakses</span>';

        // Update preview
        if (currentImage && currentImage.tagName === 'IMG') {
            currentImage.src = urlInput.value;
        }

        // Hapus upload preview jika ada
        removeUploadedImage();
    };

    img.onerror = function() {
        testResult.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle me-1"></i>URL tidak dapat diakses atau bukan gambar</span>';
    };

    img.src = urlInput.value;
}

// Hitung karakter deskripsi singkat
document.getElementById('short_description').addEventListener('input', function() {
    const maxLength = 500;
    const currentLength = this.value.length;
    const counter = document.getElementById('charCounter');

    counter.textContent = `${currentLength}/${maxLength} karakter`;

    if (currentLength > maxLength) {
        counter.className = 'text-danger small mt-1';
    } else if (currentLength > 450) {
        counter.className = 'text-warning small mt-1';
    } else {
        counter.className = 'text-muted small mt-1';
    }
});

// Validasi form
document.getElementById('editProductForm').addEventListener('submit', function(e) {
    const name = document.getElementById('product_name').value.trim();
    const price = document.getElementById('price').value;
    const stock = document.getElementById('stock').value;
    const category = document.getElementById('category_id').value;

    // Validasi nama
    if (!name) {
        e.preventDefault();
        alert('Nama produk harus diisi!');
        document.getElementById('product_name').focus();
        return false;
    }

    // Validasi harga
    if (!price || price <= 0) {
        e.preventDefault();
        alert('Harga harus lebih dari 0!');
        document.getElementById('price').focus();
        return false;
    }

    // Validasi stok
    if (stock < 0) {
        e.preventDefault();
        alert('Stok tidak boleh negatif!');
        document.getElementById('stock').focus();
        return false;
    }

    // Validasi kategori
    if (!category) {
        e.preventDefault();
        alert('Silakan pilih kategori!');
        document.getElementById('category_id').focus();
        return false;
    }

    return true;
});

// Drag & drop untuk upload gambar
const uploadArea = document.getElementById('imageUploadArea');
if (uploadArea) {
    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.style.borderColor = '#007bff';
        this.style.background = '#e9ecef';
    });

    uploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.style.borderColor = '#dee2e6';
        this.style.background = '#f8f9fa';
    });

    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        this.style.borderColor = '#dee2e6';
        this.style.background = '#f8f9fa';

        const fileInput = document.getElementById('image_url');
        if (e.dataTransfer.files.length > 0) {
            fileInput.files = e.dataTransfer.files;
            previewUploadedImage(fileInput);
        }
    });
}

// Inisialisasi karakter counter
document.getElementById('short_description').dispatchEvent(new Event('input'));
</script>

<?php include "footer.php"; ?>
