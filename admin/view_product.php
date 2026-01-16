<?php
// admin/view_product.php
session_start();
require_once "../config.php";

// Cek login
if (
    !isset($_SESSION["admin_logged_in"]) ||
    $_SESSION["admin_logged_in"] !== true
) {
    header("Location: login.php");
    exit();
}

$productId = $_GET["id"] ?? "";

if (empty($productId)) {
    $_SESSION["error"] = "ID produk tidak valid!";
    header("Location: products.php");
    exit();
}

// Get product details
$productResult = supabase("cust_products", "GET", null, [
    "id" => "eq." . $productId,
]);

// Debug logging
error_log("View Product - ID: " . $productId);
error_log("View Product - Response Code: " . ($productResult["code"] ?? "N/A"));
error_log(
    "View Product - Success: " . ($productResult["success"] ? "true" : "false"),
);

$product = [];
if ($productResult["success"] && !empty($productResult["data"])) {
    // Supabase bisa mengembalikan array kosong jika tidak ditemukan
    if (is_array($productResult["data"]) && count($productResult["data"]) > 0) {
        $product = $productResult["data"][0];
        error_log("View Product - Found: " . ($product["name"] ?? "Unnamed"));
    } else {
        error_log("View Product - Product not found in data array");
        $_SESSION["error"] = "Produk tidak ditemukan!";
        header("Location: products.php");
        exit();
    }
} else {
    error_log(
        "View Product - API Error: " .
            ($productResult["error"] ?? "Unknown error"),
    );
    $_SESSION["error"] =
        "Gagal mengambil data produk: " .
        ($productResult["error"] ?? "Unknown error");
    header("Location: products.php");
    exit();
}

// Get category name if category_id exists
$categoryName = "Tidak ada kategori";
if (!empty($product["category_id"])) {
    $categoryResult = supabase("cust_categories", "GET", null, [
        "id" => "eq." . $product["category_id"],
    ]);

    if (
        $categoryResult["success"] &&
        !empty($categoryResult["data"]) &&
        count($categoryResult["data"]) > 0
    ) {
        $categoryName =
            $categoryResult["data"][0]["name"] ?? "Tidak ada kategori";
        error_log("View Product - Category found: " . $categoryName);
    } else {
        error_log(
            "View Product - Category not found for ID: " .
                $product["category_id"],
        );
    }
}

// Include header
require_once "header.php";
?>

<!-- Include Sidebar -->
<?php include "sidebar.php"; ?>

<!-- Main Content -->
<div class="main-content">

    <!-- Include Header Content -->
    <?php include "header-content.php"; ?>

    <!-- Pesan Error/Success -->
    <?php if (isset($_SESSION["error"])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?= $_SESSION["error"] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION["error"]); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION["success"])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?= $_SESSION["success"] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION["success"]); ?>
    <?php endif; ?>

    <!-- Debug info (hanya tampilkan di development) -->
    <?php if (
        $_SERVER["HTTP_HOST"] == "localhost" ||
        $_SERVER["HTTP_HOST"] == "127.0.0.1"
    ): ?>
        <div class="alert alert-info d-flex align-items-center" role="alert">
            <i class="fas fa-bug me-2"></i>
            <div>
                <strong>DEBUG INFO:</strong>
                Product ID: <?= htmlspecialchars($productId) ?> |
                Category ID: <?= htmlspecialchars(
                    $product["category_id"] ?? "null",
                ) ?> |
                Response Code: <?= $productResult["code"] ?? "N/A" ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Detail Produk -->
    <div class="row">
        <div class="col-12">
            <div class="card card-custom">
                <div class="card-header-custom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-eye me-2"></i>Detail Produk</h5>
                    <div>
                        <a href="products.php" class="btn btn-sm btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar
                        </a>
                        <a href="edit_product.php?id=<?= htmlspecialchars(
                            $productId,
                        ) ?>" class="btn btn-sm btn-primary me-2">
                            <i class="fas fa-edit me-1"></i> Edit Produk
                        </a>
                        <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                            <i class="fas fa-trash me-1"></i> Hapus
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Gambar Produk -->
                        <div class="col-md-4">
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fas fa-image me-2"></i>Gambar Produk</h6>
                                </div>
                                <div class="card-body text-center">
                                    <?php
                                    $imageUrl = $product["image_url"] ?? "";
                                    $placeholder =
                                        "https://via.placeholder.com/300x300/6c757d/ffffff?text=No+Image";

                                    if (!empty($imageUrl)):
                                        // Cek jika ini base64 image
                                        if (
                                            strpos($imageUrl, "data:image") ===
                                            0
                                        ): ?>
                                        <img src="<?= htmlspecialchars(
                                            $imageUrl,
                                        ) ?>"
                                             class="img-fluid rounded mb-3"
                                             style="max-height: 300px; max-width: 100%; object-fit: contain;"
                                             onerror="this.src='<?= $placeholder ?>'"
                                             alt="<?= htmlspecialchars(
                                                 $product["name"] ?? "",
                                             ) ?>">
                                    <?php // External URL

                                            else: ?>
                                        <img src="<?= htmlspecialchars(
                                            $imageUrl,
                                        ) ?>"
                                             class="img-fluid rounded mb-3"
                                             style="max-height: 300px; max-width: 100%; object-fit: contain;"
                                             onerror="this.src='<?= $placeholder ?>'"
                                             alt="<?= htmlspecialchars(
                                                 $product["name"] ?? "",
                                             ) ?>">
                                    <?php endif; ?>
                                    <?php // No image


                                    else:
                                         ?>
                                        <div class="bg-light rounded d-flex align-items-center justify-content-center mb-3"
                                             style="height: 300px;">
                                            <i class="fas fa-wine-bottle fa-5x text-muted"></i>
                                        </div>
                                        <p class="text-muted">Tidak ada gambar</p>
                                    <?php
                                    endif;
                                    ?>

                                    <?php if (
                                        !empty($product["product_images"])
                                    ):
                                        // Parse array gambar tambahan dari PostgreSQL format


                                        $additional_images = [];
                                        if (
                                            is_string(
                                                $product["product_images"],
                                            )
                                        ) {
                                            // Format: {"url1","url2"}
                                            $str = trim(
                                                $product["product_images"],
                                                "{}",
                                            );
                                            if (!empty($str)) {
                                                $additional_images = explode(
                                                    ",",
                                                    $str,
                                                );
                                                $additional_images = array_map(
                                                    function ($url) {
                                                        return trim($url, '"');
                                                    },
                                                    $additional_images,
                                                );
                                            }
                                        } elseif (
                                            is_array($product["product_images"])
                                        ) {
                                            $additional_images =
                                                $product["product_images"];
                                        }

                                        if (!empty($additional_images)): ?>
                                        <hr>
                                        <h6 class="mt-3">Gambar Tambahan</h6>
                                        <div class="row mt-2">
                                            <?php foreach (
                                                $additional_images
                                                as $index => $img_url
                                            ):
                                                if (empty($img_url)) {
                                                    continue;
                                                } ?>
                                                <div class="col-6 col-md-4 mb-2">
                                                    <img src="<?= htmlspecialchars(
                                                        $img_url,
                                                    ) ?>"
                                                         class="img-thumbnail"
                                                         style="height: 80px; width: 100%; object-fit: cover;"
                                                         onerror="this.src='https://via.placeholder.com/100x100/6c757d/ffffff?text=Image'"
                                                         alt="Gambar <?= $index +
                                                             1 ?>">
                                                </div>
                                            <?php
                                            endforeach; ?>
                                        </div>
                                    <?php endif;
                                        ?>
                                    <?php
                                    endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Informasi Produk -->
                        <div class="col-md-8">
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informasi Produk</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label text-muted">Nama Produk</label>
                                            <h4 class="text-primary"><?= htmlspecialchars(
                                                $product["name"] ?? "",
                                            ) ?></h4>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label text-muted">Kategori</label>
                                            <div>
                                                <span class="badge bg-info fs-6"><?= htmlspecialchars(
                                                    $categoryName,
                                                ) ?></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <label class="form-label text-muted">Harga</label>
                                            <h3 class="text-success">Rp <?= number_format(
                                                $product["price"] ?? 0,
                                                0,
                                                ",",
                                                ".",
                                            ) ?></h3>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label text-muted">Stok</label>
                                            <div>
                                                <?php
                                                $stock = $product["stock"] ?? 0;
                                                $stockClass = "bg-success";
                                                $stockText = "Tersedia";
                                                if ($stock <= 0) {
                                                    $stockClass = "bg-danger";
                                                    $stockText = "Habis";
                                                } elseif ($stock <= 10) {
                                                    $stockClass =
                                                        "bg-warning text-dark";
                                                    $stockText = "Terbatas";
                                                }
                                                ?>
                                                <span class="badge <?= $stockClass ?> p-3 fs-5">
                                                    <?= number_format(
                                                        $stock,
                                                        0,
                                                    ) ?> Unit
                                                </span>
                                                <small class="d-block text-muted mt-1">Status: <?= $stockText ?></small>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label text-muted">Likes</label>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-heart text-danger me-2 fs-4"></i>
                                                <h4 class="mb-0"><?= number_format(
                                                    $product["likes_count"] ??
                                                        0,
                                                    0,
                                                ) ?></h4>
                                            </div>
                                            <small class="text-muted">Jumlah suka dari pelanggan</small>
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label text-muted">Deskripsi Singkat</label>
                                        <div class="p-3 bg-light rounded">
                                            <?php if (
                                                !empty(
                                                    $product[
                                                        "short_description"
                                                    ]
                                                )
                                            ): ?>
                                                <p class="mb-0"><?= nl2br(
                                                    htmlspecialchars(
                                                        $product[
                                                            "short_description"
                                                        ],
                                                    ),
                                                ) ?></p>
                                            <?php else: ?>
                                                <p class="mb-0 text-muted"><em>Tidak ada deskripsi singkat</em></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label text-muted">Deskripsi Lengkap</label>
                                        <div class="p-3 bg-light rounded">
                                            <?php if (
                                                !empty($product["description"])
                                            ): ?>
                                                <?= nl2br(
                                                    htmlspecialchars(
                                                        $product["description"],
                                                    ),
                                                ) ?>
                                            <?php else: ?>
                                                <p class="mb-0 text-muted"><em>Tidak ada deskripsi lengkap</em></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4">
                                            <label class="form-label text-muted">ID Produk</label>
                                            <div class="font-monospace bg-dark text-white p-2 rounded small">
                                                <?= htmlspecialchars(
                                                    $product["id"] ?? "",
                                                ) ?>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label text-muted">Dibuat Pada</label>
                                            <div class="p-2 bg-light rounded">
                                                <?php if (
                                                    !empty(
                                                        $product["created_at"]
                                                    )
                                                ):

                                                    $created = strtotime(
                                                        $product["created_at"],
                                                    );
                                                    if ($created !== false): ?>
                                                    <i class="far fa-calendar me-1"></i>
                                                    <?= date(
                                                        "d-m-Y",
                                                        $created,
                                                    ) ?><br>
                                                    <i class="far fa-clock me-1"></i>
                                                    <?= date("H:i", $created) ?>
                                                <?php else: ?>
                                                    <em class="text-muted">Format tanggal tidak valid</em>
                                                <?php endif;
                                                    ?>
                                                <?php
                                                else:
                                                     ?>
                                                    <em class="text-muted">Tidak diketahui</em>
                                                <?php
                                                endif; ?>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label text-muted">Terakhir Update</label>
                                            <div class="p-2 bg-light rounded">
                                                <?php if (
                                                    !empty(
                                                        $product["updated_at"]
                                                    )
                                                ):

                                                    $updated = strtotime(
                                                        $product["updated_at"],
                                                    );
                                                    if ($updated !== false): ?>
                                                    <i class="far fa-calendar me-1"></i>
                                                    <?= date(
                                                        "d-m-Y",
                                                        $updated,
                                                    ) ?><br>
                                                    <i class="far fa-clock me-1"></i>
                                                    <?= date("H:i", $updated) ?>
                                                <?php else: ?>
                                                    <em class="text-muted">Format tanggal tidak valid</em>
                                                <?php endif;
                                                    ?>
                                                <?php
                                                else:
                                                     ?>
                                                    <em class="text-muted">Belum pernah diupdate</em>
                                                <?php
                                                endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Aksi Produk -->
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fas fa-cogs me-2"></i>Aksi Produk</h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex flex-wrap gap-2">
                                        <a href="edit_product.php?id=<?= htmlspecialchars(
                                            $productId,
                                        ) ?>" class="btn btn-primary">
                                            <i class="fas fa-edit me-2"></i> Edit Produk
                                        </a>
                                        <a href="products.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-list me-2"></i> Kembali ke Daftar
                                        </a>
                                        <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                            <i class="fas fa-trash me-2"></i> Hapus Produk
                                        </button>
                                        <?php if ($stock > 0): ?>
                                            <button class="btn btn-outline-success">
                                                <i class="fas fa-shopping-cart me-2"></i> Tersedia untuk Dijual
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-outline-danger" disabled>
                                                <i class="fas fa-ban me-2"></i> Stok Habis
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="fas fa-exclamation-triangle text-danger me-2"></i>
                    Konfirmasi Hapus Produk
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus produk ini?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>PERHATIAN:</strong> Tindakan ini tidak dapat dibatalkan! Produk <strong>"<?= htmlspecialchars(
                        $product["name"] ?? "",
                    ) ?>"</strong> akan dihapus secara permanen.
                </div>
                <p class="text-muted mb-0">ID Produk: <?= htmlspecialchars(
                    $productId,
                ) ?></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Batal
                </button>
                <form action="process_delete_product.php" method="POST" style="display: inline;">
                    <input type="hidden" name="id" value="<?= htmlspecialchars(
                        $productId,
                    ) ?>">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Ya, Hapus Produk
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>

<script>
// Auto-hide alerts setelah 5 detik
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});

// Konfirmasi sebelum menghapus
document.querySelector('form[action="process_delete_product.php"]').addEventListener('submit', function(e) {
    if (!confirm('Apakah Anda benar-benar yakin ingin menghapus produk ini? Tindakan ini tidak dapat dikembalikan!')) {
        e.preventDefault();
    }
});
</script>
