<?php
// admin/categories.php
require_once "header.php";

// Cek dulu apakah cache enabled
$useCache = defined("CACHE_ENABLED") && CACHE_ENABLED;

if ($useCache) {
    $cacheKey = "categories_data_" . date("YmdH");
    $cachedData = cache_get($cacheKey);
    if ($cachedData !== false) {
        // Gunakan data cache
        $categories = $cachedData["categories"];
        $productCounts = $cachedData["productCounts"];
    } else {
        // Ambil dari database
        // ...
        // Simpan ke cache
        cache_set($cacheKey, [
            "categories" => $categories,
            "productCounts" => $productCounts,
        ]);
    }
} else {
    // Tanpa cache
    // Ambil langsung dari database
}

// Get all categories dari tabel cust_categories
$categoriesResult = supabase("cust_categories", "GET", null, [
    "select" => "*",
    "order" => "created_at.desc",
]);

// Periksa apakah data ada dan valid
$categories = [];
$error_message = "";

if (isset($categoriesResult["success"]) && $categoriesResult["success"]) {
    if (
        isset($categoriesResult["data"]) &&
        is_array($categoriesResult["data"])
    ) {
        $categories = $categoriesResult["data"];
    }
} else {
    // Hanya tampilkan error jika benar-benar ada error
    if (isset($categoriesResult["error"])) {
        $error_message = $categoriesResult["error"];
    }
}
?>

<!-- Include Sidebar -->
<?php include "sidebar.php"; ?>

<!-- Main Content -->
<div class="main-content">

    <!-- Include Header Content -->
    <?php include "header-content.php"; ?>

    <!-- Content khusus halaman kategori -->
    <div class="row">
        <div class="col-12">
            <div class="card card-custom">
                <div class="card-header-custom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-tags me-2"></i>Manajemen Kategori</h5>
                    <!-- Button Tambah Kategori -->
                    <button type="button" class="btn btn-primary btn-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#addCategoryModal">
                        <i class="fas fa-plus me-1"></i> Tambah Kategori
                    </button>
                </div>
                <div class="card-body">
                    <!-- Container untuk alerts -->
                    <div id="alert-container">
                        <?php
                        // Hanya tampilkan jika ada pesan yang bermakna
                        $showError =
                            !empty($error_message) &&
                            trim($error_message) !== "Error";
                        $showSuccess =
                            isset($_SESSION["success"]) &&
                            !empty(trim($_SESSION["success"]));
                        $showSessionError =
                            isset($_SESSION["error"]) &&
                            !empty(trim($_SESSION["error"]));

                        if ($showError): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?= htmlspecialchars($error_message) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif;
                        ?>

                        <?php if ($showSuccess): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <?= htmlspecialchars($_SESSION["success"]) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <?php unset($_SESSION["success"]); ?>
                        <?php endif; ?>

                        <?php if ($showSessionError): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?= htmlspecialchars($_SESSION["error"]) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <?php unset($_SESSION["error"]); ?>
                        <?php endif; ?>
                    </div>

                    <!-- Statistik Kategori -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h5 class="mb-0"><?= count(
                                                $categories,
                                            ) ?></h5>
                                            <small class="text-muted">Total Kategori</small>
                                        </div>
                                        <div class="text-primary">
                                            <i class="fas fa-tags fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body p-3">
                                    <?php
                                    $totalProducts = 0;
                                    $emptyCategories = 0;
                                    foreach ($categories as $category) {
                                        $productCount = 0;
                                        if (isset($category["id"])) {
                                            $productResult = supabase(
                                                "cust_products",
                                                "GET",
                                                null,
                                                [
                                                    "category_id" =>
                                                        "eq." . $category["id"],
                                                    "select" => "count",
                                                ],
                                            );
                                            if (
                                                isset(
                                                    $productResult["data"][0][
                                                        "count"
                                                    ],
                                                )
                                            ) {
                                                $productCount = intval(
                                                    $productResult["data"][0][
                                                        "count"
                                                    ],
                                                );
                                            }
                                        }
                                        $totalProducts += $productCount;
                                        if ($productCount == 0) {
                                            $emptyCategories++;
                                        }
                                    }
                                    ?>
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h5 class="mb-0"><?= number_format(
                                                $totalProducts,
                                            ) ?></h5>
                                            <small class="text-muted">Total Produk</small>
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
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h5 class="mb-0 text-<?= $emptyCategories >
                                            0
                                                ? "warning"
                                                : "success" ?>"><?= $emptyCategories ?></h5>
                                            <small class="text-muted">Kategori Kosong</small>
                                        </div>
                                        <div class="text-<?= $emptyCategories >
                                        0
                                            ? "warning"
                                            : "success" ?>">
                                            <i class="fas fa-box fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabel Kategori -->
                    <div class="table-responsive">
                        <table class="table table-custom table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th width="50">No</th>
                                    <th>Nama Kategori</th>
                                    <th>Jumlah Produk</th>
                                    <th>Dibuat</th>
                                    <th width="120">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="categoryTableBody">
                                <?php if (
                                    !empty($categories) &&
                                    is_array($categories)
                                ):
                                    $no = 1;
                                    foreach ($categories as $category):

                                        if (!is_array($category)) {
                                            continue;
                                        }

                                        // Get product count untuk kategori ini
                                        $productCount = 0;
                                        if (isset($category["id"])) {
                                            $productResult = supabase(
                                                "cust_products",
                                                "GET",
                                                null,
                                                [
                                                    "category_id" =>
                                                        "eq." . $category["id"],
                                                    "select" => "count",
                                                ],
                                            );
                                            if (
                                                isset(
                                                    $productResult["data"][0][
                                                        "count"
                                                    ],
                                                )
                                            ) {
                                                $productCount = intval(
                                                    $productResult["data"][0][
                                                        "count"
                                                    ],
                                                );
                                            }
                                        }

                                        // Format tanggal
                                        $created_at = isset(
                                            $category["created_at"],
                                        )
                                            ? date(
                                                "d-m-Y H:i",
                                                strtotime(
                                                    $category["created_at"],
                                                ),
                                            )
                                            : "-";

                                        // Tentukan warna badge berdasarkan jumlah produk
                                        $badgeClass = "bg-info";
                                        if ($productCount > 10) {
                                            $badgeClass = "bg-success";
                                        } elseif ($productCount == 0) {
                                            $badgeClass = "bg-secondary";
                                        }
                                        ?>
                                <tr>
                                    <td class="fw-bold"><?= $no++ ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="me-2">
                                                <i class="fas fa-tag text-primary"></i>
                                            </div>
                                            <div>
                                                <strong class="d-block"><?= htmlspecialchars(
                                                    $category["name"] ?? "N/A",
                                                ) ?></strong>
                                                <?php if (
                                                    isset(
                                                        $category[
                                                            "description"
                                                        ],
                                                    ) &&
                                                    !empty(
                                                        trim(
                                                            $category[
                                                                "description"
                                                            ],
                                                        )
                                                    )
                                                ): ?>
                                                <small class="text-muted d-block"><?= htmlspecialchars(
                                                    substr(
                                                        trim(
                                                            $category[
                                                                "description"
                                                            ],
                                                        ),
                                                        0,
                                                        50,
                                                    ),
                                                ) ?>...</small>
                                                <?php endif; ?>
                                                <?php if (
                                                    isset($category["id"])
                                                ): ?>
                                                <small class="text-muted">ID: <?= substr(
                                                    $category["id"],
                                                    0,
                                                    8,
                                                ) ?>...</small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge <?= $badgeClass ?> p-2">
                                            <?= $productCount ?> produk
                                        </span>
                                    </td>
                                    <td>
                                        <small><i class="far fa-calendar me-1"></i><?= $created_at ?></small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button class="btn btn-outline-primary edit-category"
                                                    data-id="<?= $category[
                                                        "id"
                                                    ] ?? "" ?>"
                                                    data-name="<?= htmlspecialchars(
                                                        $category["name"] ?? "",
                                                    ) ?>"
                                                    data-description="<?= htmlspecialchars(
                                                        $category[
                                                            "description"
                                                        ] ?? "",
                                                    ) ?>"
                                                    title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-outline-danger delete-category"
                                                    data-id="<?= $category[
                                                        "id"
                                                    ] ?? "" ?>"
                                                    data-name="<?= htmlspecialchars(
                                                        $category["name"] ?? "",
                                                    ) ?>"
                                                    data-productcount="<?= $productCount ?>"
                                                    title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                                    endforeach;
                                else:
                                     ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="fas fa-tags fa-3x mb-3"></i>
                                            <?php if (empty($error_message)): ?>
                                            <h5 class="mb-2">Belum ada kategori</h5>
                                            <p class="mb-3">Silakan tambahkan kategori pertama Anda</p>
                                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                                <i class="fas fa-plus me-2"></i> Tambah Kategori Pertama
                                            </button>
                                            <?php else: ?>
                                            <h5 class="mb-2 text-danger">Gagal memuat data</h5>
                                            <p class="text-muted"><?= htmlspecialchars(
                                                $error_message,
                                            ) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                                endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Kategori -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="process_category.php" method="POST" id="addCategoryForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCategoryModalLabel">
                        <i class="fas fa-plus-circle me-2"></i>Tambah Kategori Baru
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="category_name" class="form-label">Nama Kategori <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="category_name" name="name" required
                               placeholder="Contoh: Parfum Wanita, Parfum Pria, dll">
                        <div class="form-text">Nama kategori harus unik</div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Deskripsi (Opsional)</label>
                        <textarea class="form-control" id="description" name="description" rows="3"
                                  placeholder="Deskripsi singkat tentang kategori..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-primary" name="action" value="add">
                        <i class="fas fa-save me-1"></i> Simpan Kategori
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Kategori -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="process_category.php" method="POST" id="editCategoryForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCategoryModalLabel">
                        <i class="fas fa-edit me-2"></i>Edit Kategori
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit_id" name="id">
                    <div class="mb-3">
                        <label for="edit_category_name" class="form-label">Nama Kategori <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_category_name" name="name" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Deskripsi (Opsional)</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-primary" name="action" value="edit">
                        <i class="fas fa-save me-1"></i> Update Kategori
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Hapus Kategori -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-labelledby="deleteCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteCategoryModalLabel">
                    <i class="fas fa-exclamation-triangle text-danger me-2"></i>Konfirmasi Hapus
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus kategori <strong id="delete_category_name"></strong>?</p>
                <div class="alert alert-warning" id="deleteWarning">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <div id="warningMessage">
                        <strong>PERHATIAN:</strong> Tindakan ini tidak dapat dibatalkan!
                    </div>
                </div>
                <p class="text-muted mb-0">
                    <i class="fas fa-info-circle me-1"></i>
                    ID Kategori: <span id="delete_category_id"></span>
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Batal
                </button>
                <form action="process_category.php" method="POST" style="display: inline;">
                    <input type="hidden" id="delete_id" name="id">
                    <button type="submit" class="btn btn-danger" name="action" value="delete">
                        <i class="fas fa-trash me-1"></i> Hapus Kategori
                    </button>
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
        border-radius: 4px !important;
    }

    .card-custom {
        border: none;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        border-radius: 10px;
    }

    .card-header-custom {
        background: #f8f9fa;
        border-bottom: 1px solid #eee;
        padding: 1rem 1.5rem;
        border-radius: 10px 10px 0 0 !important;
    }

    /* Animasi untuk auto-hide alerts */
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
// ========== EVENT LISTENERS ==========
document.addEventListener('DOMContentLoaded', function() {
    console.log("DOM Loaded - Initializing category buttons...");

    // 1. EDIT CATEGORY BUTTONS
    const editButtons = document.querySelectorAll('.edit-category');
    console.log("Found edit buttons:", editButtons.length);

    editButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            console.log("Edit button clicked");

            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            const description = this.getAttribute('data-description');

            console.log("Category data - ID:", id, "Name:", name);

            // Set values to edit form
            if (document.getElementById('edit_id')) {
                document.getElementById('edit_id').value = id || '';
            }
            if (document.getElementById('edit_category_name')) {
                document.getElementById('edit_category_name').value = name || '';
            }
            if (document.getElementById('edit_description')) {
                document.getElementById('edit_description').value = description || '';
            }

            // Show modal
            const editModalElement = document.getElementById('editCategoryModal');
            if (editModalElement) {
                const editModal = new bootstrap.Modal(editModalElement);
                editModal.show();
            } else {
                console.error("Edit modal element not found!");
            }
        });
    });

    // 2. DELETE CATEGORY BUTTONS
    const deleteButtons = document.querySelectorAll('.delete-category');
    console.log("Found delete buttons:", deleteButtons.length);

    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            console.log("Delete button clicked");

            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            const productCount = parseInt(this.getAttribute('data-productcount') || 0);

            console.log("Category to delete - ID:", id, "Name:", name, "Product Count:", productCount);

            // Set values to delete modal
            if (document.getElementById('delete_id')) {
                document.getElementById('delete_id').value = id || '';
            }
            if (document.getElementById('delete_category_name')) {
                document.getElementById('delete_category_name').textContent = name || '';
            }
            if (document.getElementById('delete_category_id')) {
                document.getElementById('delete_category_id').textContent = id || '';
            }

            // Update warning message based on product count
            const warningDiv = document.getElementById('warningMessage');
            const deleteWarning = document.getElementById('deleteWarning');

            if (warningDiv && deleteWarning) {
                if (productCount > 0) {
                    warningDiv.innerHTML = `
                        <strong>PERHATIAN:</strong> Kategori ini memiliki <span class="text-danger fw-bold">${productCount} produk</span>!<br>
                        Semua produk akan kehilangan kategorinya jika kategori ini dihapus.
                    `;
                    deleteWarning.classList.remove('alert-warning');
                    deleteWarning.classList.add('alert-danger');
                } else {
                    warningDiv.innerHTML = `
                        <strong>PERHATIAN:</strong> Tindakan ini tidak dapat dibatalkan!
                    `;
                    deleteWarning.classList.remove('alert-danger');
                    deleteWarning.classList.add('alert-warning');
                }
            }

            // Show modal
            const deleteModalElement = document.getElementById('deleteCategoryModal');
            if (deleteModalElement) {
                const deleteModal = new bootstrap.Modal(deleteModalElement);
                deleteModal.show();
            } else {
                console.error("Delete modal element not found!");
            }
        });
    });

    // 3. FORM VALIDATION
    const addCategoryForm = document.getElementById('addCategoryForm');
    if (addCategoryForm) {
        addCategoryForm.addEventListener('submit', function(e) {
            const categoryName = document.getElementById('category_name').value.trim();
            if (!categoryName) {
                e.preventDefault();
                alert('Nama kategori wajib diisi!');
                document.getElementById('category_name').focus();
                return false;
            }
            return true;
        });
    }

    const editCategoryForm = document.getElementById('editCategoryForm');
    if (editCategoryForm) {
        editCategoryForm.addEventListener('submit', function(e) {
            const categoryName = document.getElementById('edit_category_name').value.trim();
            if (!categoryName) {
                e.preventDefault();
                alert('Nama kategori wajib diisi!');
                document.getElementById('edit_category_name').focus();
                return false;
            }
            return true;
        });
    }

    // 4. CLEAR FORM WHEN ADD MODAL IS CLOSED
    const addModal = document.getElementById('addCategoryModal');
    if (addModal) {
        addModal.addEventListener('hidden.bs.modal', function() {
            if (document.getElementById('category_name')) {
                document.getElementById('category_name').value = '';
            }
            if (document.getElementById('description')) {
                document.getElementById('description').value = '';
            }
        });
    }

    // ========== AUTO-HIDE NOTIFICATION FUNCTION ==========
    function setupAutoHideAlerts() {
        const alerts = document.querySelectorAll('#alert-container .alert');
        console.log("Setting up auto-hide for alerts:", alerts.length);

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
                            console.log("Alert auto-removed");
                        }
                    }, 500);
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
                            console.log("Alert manually removed");
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

    console.log("Category page initialization complete!");
});

// ========== DEBUG FUNCTION ==========
function debugButtons() {
    console.log("=== DEBUG BUTTONS ===");
    console.log("Edit buttons:", document.querySelectorAll('.edit-category').length);
    console.log("Delete buttons:", document.querySelectorAll('.delete-category').length);
}
</script>

<?php include "footer.php"; ?>
