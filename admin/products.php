<?php
// admin/products.php
require_once "header.php";

// Get products dari tabel cust_products dengan join ke cust_categories
$productsResult = supabase("cust_products", "GET", null, [
    "select" =>
        "id,name,price,stock,likes_count,category_id,cust_categories(name)",
    "order" => "created_at.desc",
]);

$products = [];
if (isset($productsResult["data"]) && is_array($productsResult["data"])) {
    $products = $productsResult["data"];
}

$error_message = "";
if (isset($productsResult["error"])) {
    $error_message = "Error: " . $productsResult["error"];
}
?>

<!-- Include Sidebar -->
<?php include "sidebar.php"; ?>

<!-- Main Content -->
<div class="main-content">

    <!-- Include Header Content -->
    <?php include "header-content.php"; ?>

    <!-- Content khusus halaman produk -->
    <div class="row">
        <div class="col-12">
            <div class="card card-custom">
                <div class="card-header-custom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-wine-bottle me-2"></i>Manajemen Produk</h5>
                    <div>
                        <a href="add_product.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus me-1"></i> Tambah Produk
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Container untuk alerts -->
                    <div id="alert-container">
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?= htmlspecialchars($error_message) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($_SESSION["success"])): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <?= htmlspecialchars($_SESSION["success"]) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <?php unset($_SESSION["success"]); ?>
                        <?php endif; ?>

                        <?php if (isset($_SESSION["error"])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?= htmlspecialchars($_SESSION["error"]) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <?php unset($_SESSION["error"]); ?>
                        <?php endif; ?>
                    </div>

                    <!-- Statistik Produk -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h5 class="mb-0"><?= count(
                                                $products,
                                            ) ?></h5>
                                            <small class="text-muted">Total Produk</small>
                                        </div>
                                        <div class="text-primary">
                                            <i class="fas fa-wine-bottle fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body p-3">
                                    <?php
                                    $totalStock = 0;
                                    foreach ($products as $product) {
                                        $totalStock += $product["stock"] ?? 0;
                                    }
                                    ?>
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h5 class="mb-0"><?= number_format(
                                                $totalStock,
                                            ) ?></h5>
                                            <small class="text-muted">Total Stok</small>
                                        </div>
                                        <div class="text-success">
                                            <i class="fas fa-boxes fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body p-3">
                                    <?php
                                    $lowStockCount = 0;
                                    foreach ($products as $product) {
                                        if (($product["stock"] ?? 0) <= 5) {
                                            $lowStockCount++;
                                        }
                                    }
                                    ?>
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h5 class="mb-0 text-<?= $lowStockCount >
                                            0
                                                ? "warning"
                                                : "success" ?>"><?= $lowStockCount ?></h5>
                                            <small class="text-muted">Stok Rendah</small>
                                        </div>
                                        <div class="text-<?= $lowStockCount > 0
                                            ? "warning"
                                            : "success" ?>">
                                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body p-3">
                                    <?php
                                    $totalLikes = 0;
                                    foreach ($products as $product) {
                                        $totalLikes +=
                                            $product["likes_count"] ?? 0;
                                    }
                                    ?>
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h5 class="mb-0"><?= number_format(
                                                $totalLikes,
                                            ) ?></h5>
                                            <small class="text-muted">Total Likes</small>
                                        </div>
                                        <div class="text-danger">
                                            <i class="fas fa-heart fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Search dan Filter -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="text" id="searchProduct" class="form-control" placeholder="Cari produk...">
                                <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6 text-end">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-outline-secondary active filter-btn" data-filter="all">
                                    Semua
                                </button>
                                <button type="button" class="btn btn-outline-secondary filter-btn" data-filter="low-stock">
                                    Stok Rendah
                                </button>
                                <button type="button" class="btn btn-outline-secondary filter-btn" data-filter="no-stock">
                                    Stok Habis
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Tabel Produk -->
                    <div class="table-responsive">
                        <table class="table table-custom table-hover table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th width="50">No</th>
                                    <th>Produk</th>
                                    <th>Kategori</th>
                                    <th>Harga (Rp)</th>
                                    <th>Stok</th>
                                    <th>Likes</th>
                                    <th width="120">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="productTableBody">
                                <?php if (
                                    !empty($products) &&
                                    is_array($products)
                                ):

                                    $no = 1;
                                    foreach ($products as $product):

                                        if (!is_array($product)) {
                                            continue;
                                        }

                                        $productId = $product["id"] ?? "";
                                        $productName =
                                            $product["name"] ??
                                            "Nama tidak tersedia";
                                        $price = $product["price"] ?? 0;
                                        $stock = $product["stock"] ?? 0;
                                        $likesCount =
                                            $product["likes_count"] ?? 0;

                                        // Get category name
                                        $categoryName = "Tidak ada kategori";
                                        if (
                                            isset(
                                                $product["cust_categories"],
                                            ) &&
                                            is_array(
                                                $product["cust_categories"],
                                            )
                                        ) {
                                            $categoryName =
                                                $product["cust_categories"][
                                                    "name"
                                                ] ?? "Tidak ada kategori";
                                        } elseif (
                                            isset($product["category_name"])
                                        ) {
                                            $categoryName =
                                                $product["category_name"];
                                        }

                                        // Tentukan kelas untuk stock
                                        $stockClass = "";
                                        if ($stock <= 0) {
                                            $stockClass = "no-stock";
                                            $badgeClass = "bg-danger";
                                        } elseif ($stock <= 5) {
                                            $stockClass = "low-stock";
                                            $badgeClass =
                                                "bg-warning text-dark";
                                        } else {
                                            $badgeClass = "bg-success";
                                        }
                                        ?>
                                <tr class="product-row <?= $stockClass ?>" data-name="<?= htmlspecialchars(
    strtolower($productName),
) ?>" data-category="<?= htmlspecialchars(strtolower($categoryName)) ?>">
                                    <td class="fw-bold"><?= $no++ ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if (
                                                !empty($product["image_url"])
                                            ): ?>
                                            <img src="<?= htmlspecialchars(
                                                $product["image_url"],
                                            ) ?>"
                                                 class="rounded me-2"
                                                 style="width: 50px; height: 50px; object-fit: cover;"
                                                 onerror="this.src='https://via.placeholder.com/50x50/6c757d/ffffff?text=No+Image'"
                                                 alt="<?= htmlspecialchars(
                                                     $productName,
                                                 ) ?>">
                                            <?php else: ?>
                                            <div class="rounded me-2 bg-light d-flex align-items-center justify-content-center"
                                                 style="width: 50px; height: 50px;">
                                                <i class="fas fa-wine-bottle text-muted"></i>
                                            </div>
                                            <?php endif; ?>
                                            <div>
                                                <strong class="d-block"><?= htmlspecialchars(
                                                    $productName,
                                                ) ?></strong>
                                                <?php if (
                                                    !empty(
                                                        $product[
                                                            "short_description"
                                                        ]
                                                    )
                                                ): ?>
                                                <small class="text-muted d-block"><?= htmlspecialchars(
                                                    substr(
                                                        $product[
                                                            "short_description"
                                                        ],
                                                        0,
                                                        50,
                                                    ),
                                                ) ?>...</small>
                                                <?php endif; ?>
                                                <small class="text-muted">ID: <?= substr(
                                                    $productId,
                                                    0,
                                                    8,
                                                ) ?>...</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?= htmlspecialchars(
                                            $categoryName,
                                        ) ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-primary">Rp <?= number_format(
                                            $price,
                                            0,
                                            ",",
                                            ".",
                                        ) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge <?= $badgeClass ?> p-2">
                                            <?= number_format($stock, 0) ?>
                                            <?php if ($stock <= 5): ?>
                                            <i class="fas fa-exclamation-triangle ms-1"></i>
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-heart text-danger me-1"></i>
                                            <span class="fw-bold"><?= number_format(
                                                $likesCount,
                                                0,
                                            ) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="edit_product.php?id=<?= $productId ?>"
                                               class="btn btn-outline-primary"
                                               title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="view_product.php?id=<?= $productId ?>"
                                               class="btn btn-outline-info"
                                               title="Lihat">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button type="button"
                                                    class="btn btn-outline-danger delete-product"
                                                    data-id="<?= $productId ?>"
                                                    data-name="<?= htmlspecialchars(
                                                        $productName,
                                                    ) ?>"
                                                    title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                                    endforeach;
                                    ?>
                                <?php
                                else:
                                     ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="fas fa-wine-bottle fa-3x mb-3"></i>
                                            <h5 class="mb-2">Belum ada produk</h5>
                                            <p class="mb-3">Silakan tambahkan produk pertama Anda</p>
                                            <a href="add_product.php" class="btn btn-primary">
                                                <i class="fas fa-plus me-2"></i>Tambah Produk Pertama
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                                endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination (jika diperlukan) -->
                    <?php if (count($products) > 0): ?>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-muted">
                            Menampilkan <?= count($products) ?> produk
                        </div>
                        <nav>
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item disabled">
                                    <a class="page-link" href="#" tabindex="-1">Sebelumnya</a>
                                </li>
                                <li class="page-item active">
                                    <a class="page-link" href="#">1</a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="#">2</a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="#">3</a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="#">Selanjutnya</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Hapus Produk -->
<div class="modal fade" id="deleteProductModal" tabindex="-1" aria-labelledby="deleteProductModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteProductModalLabel">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus produk <strong id="delete_product_name"></strong>?</p>
                <p class="text-danger">
                    <small>
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        <strong>Perhatian:</strong> Tindakan ini tidak dapat dibatalkan!
                    </small>
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form action="process_delete_product.php" method="POST" style="display: inline;">
                    <input type="hidden" id="delete_product_id" name="id">
                    <button type="submit" class="btn btn-danger" name="action" value="delete">Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .table-custom th {
        font-weight: 600;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .table-custom td {
        vertical-align: middle;
    }

    .btn-group-sm > .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }

    /* Animasi untuk auto-hide alerts */
    .alert-auto-hide {
        position: relative;
        overflow: hidden;
    }

    .alert-fade-out {
        animation: fadeOutAlert 0.5s ease forwards !important;
    }

    @keyframes fadeOutAlert {
        0% {
            opacity: 1;
            max-height: 200px;
            margin-bottom: 1rem;
        }
        100% {
            opacity: 0;
            max-height: 0;
            margin-bottom: 0;
            padding-top: 0;
            padding-bottom: 0;
            border: 0;
            overflow: hidden;
        }
    }
</style>

<script>
// Handle delete button click
document.addEventListener('DOMContentLoaded', function() {
    // Delete product
    document.querySelectorAll('.delete-product').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');

            document.getElementById('delete_product_id').value = id;
            document.getElementById('delete_product_name').textContent = name;

            const modal = new bootstrap.Modal(document.getElementById('deleteProductModal'));
            modal.show();
        });
    });

    // Search produk
    const searchInput = document.getElementById('searchProduct');
    const clearSearchBtn = document.getElementById('clearSearch');
    const productRows = document.querySelectorAll('.product-row');

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();

            productRows.forEach(row => {
                const productName = row.getAttribute('data-name');
                const categoryName = row.getAttribute('data-category');

                if (productName.includes(searchTerm) || categoryName.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', function() {
            searchInput.value = '';
            productRows.forEach(row => {
                row.style.display = '';
            });
        });
    }

    // Filter produk
    const filterBtns = document.querySelectorAll('.filter-btn');
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Remove active class dari semua tombol
            filterBtns.forEach(b => b.classList.remove('active'));
            // Add active class ke tombol yang diklik
            this.classList.add('active');

            const filter = this.getAttribute('data-filter');

            productRows.forEach(row => {
                switch(filter) {
                    case 'all':
                        row.style.display = '';
                        break;
                    case 'low-stock':
                        if (row.classList.contains('low-stock')) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                        break;
                    case 'no-stock':
                        if (row.classList.contains('no-stock')) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                        break;
                }
            });
        });
    });

    // ========== AUTO-HIDE NOTIFICATION FUNCTION ==========
    function setupAutoHideAlerts() {
        const alerts = document.querySelectorAll('#alert-container .alert');

        alerts.forEach(function(alert) {
            // Skip jika alert sudah di-close manual
            if (alert.classList.contains('manually-closed')) {
                return;
            }

            // Set timeout untuk auto-hide setelah 5 detik
            setTimeout(function() {
                if (alert.parentNode) {
                    // Tambahkan class untuk animasi fade out
                    alert.classList.add('alert-fade-out');

                    // Hapus dari DOM setelah animasi selesai
                    setTimeout(function() {
                        if (alert.parentNode) {
                            alert.remove();
                        }
                    }, 500); // Sesuaikan dengan durasi animasi
                }
            }, 5000); // 5 detik

            // Tambahkan event untuk manual close dengan tombol
            const closeBtn = alert.querySelector('.btn-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', function() {
                    // Tandai alert sebagai manually closed
                    alert.classList.add('manually-closed');
                    // Langsung hapus tanpa animasi
                    setTimeout(function() {
                        if (alert.parentNode) {
                            alert.remove();
                        }
                    }, 300);
                });
            }
        });
    }

    // Jalankan auto-hide saat halaman dimuat
    setupAutoHideAlerts();

    // Juga jalankan untuk alert yang mungkin ditambahkan nanti
    const alertObserver = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length) {
                // Cek jika ada alert baru yang ditambahkan
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1 &&
                        node.classList &&
                        node.classList.contains('alert')) {
                        // Jalankan auto-hide untuk alert baru
                        setTimeout(setupAutoHideAlerts, 100);
                    }
                });
            }
        });
    });

    // Observe container alert untuk perubahan
    const alertContainer = document.getElementById('alert-container');
    if (alertContainer) {
        alertObserver.observe(alertContainer, {
            childList: true,
            subtree: true
        });
    }
});
</script>

<?php include "footer.php"; ?>
