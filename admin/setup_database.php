<?php
// admin/setup_database.php
session_start();
require_once "../config.php";

// Cek login admin
if (
    !isset($_SESSION["admin_logged_in"]) ||
    $_SESSION["admin_logged_in"] !== true
) {
    header("Location: login.php");
    exit();
}

// Fungsi untuk membuat tabel via API
function createTable($tableName, $columns)
{
    // Catatan: Supabase tidak mendukung pembuatan tabel langsung via API
    // Kita perlu membuat migrasi SQL atau menggunakan dashboard Supabase
    // File ini sebagai dokumentasi struktur tabel yang diperlukan
}

// Struktur tabel yang diperlukan
$tables = [
    "categories" => [
        "columns" => [
            "id" => "SERIAL PRIMARY KEY",
            "category_name" => "VARCHAR(100) NOT NULL",
            "slug" => "VARCHAR(120) UNIQUE",
            "description" => "TEXT",
            "parent_id" => "INTEGER REFERENCES categories(id)",
            "image_url" => "VARCHAR(255)",
            "status" => 'VARCHAR(20) DEFAULT "active"',
            "sort_order" => "INTEGER DEFAULT 0",
            "meta_title" => "VARCHAR(255)",
            "meta_description" => "TEXT",
            "meta_keywords" => "TEXT",
            "created_at" => "TIMESTAMP DEFAULT NOW()",
            "updated_at" => "TIMESTAMP DEFAULT NOW()",
        ],
    ],

    "products" => [
        "columns" => [
            "id" => "SERIAL PRIMARY KEY",
            "product_name" => "VARCHAR(255) NOT NULL",
            "product_code" => "VARCHAR(50) UNIQUE NOT NULL",
            "slug" => "VARCHAR(255) UNIQUE",
            "category_id" => "INTEGER REFERENCES categories(id)",
            "brand" => "VARCHAR(100)",
            "short_description" => "TEXT",
            "full_description" => "TEXT",
            "price" => "DECIMAL(10,2) NOT NULL",
            "discount_price" => "DECIMAL(10,2)",
            "stock_quantity" => "INTEGER DEFAULT 0",
            "weight" => "INTEGER", // dalam gram
            "capacity" => "INTEGER", // dalam ml
            "main_image_url" => "VARCHAR(255)",
            "status" => 'VARCHAR(20) DEFAULT "draft"',
            "featured" => "BOOLEAN DEFAULT false",
            "meta_title" => "VARCHAR(255)",
            "meta_description" => "TEXT",
            "meta_keywords" => "TEXT",
            "created_by" => "INTEGER",
            "created_at" => "TIMESTAMP DEFAULT NOW()",
            "updated_at" => "TIMESTAMP DEFAULT NOW()",
        ],
    ],

    "product_images" => [
        "columns" => [
            "id" => "SERIAL PRIMARY KEY",
            "product_id" => "INTEGER REFERENCES products(id) ON DELETE CASCADE",
            "image_url" => "VARCHAR(255) NOT NULL",
            "alt_text" => "VARCHAR(255)",
            "sort_order" => "INTEGER DEFAULT 0",
            "created_at" => "TIMESTAMP DEFAULT NOW()",
        ],
    ],

    "brands" => [
        "columns" => [
            "id" => "SERIAL PRIMARY KEY",
            "brand_name" => "VARCHAR(100) NOT NULL",
            "slug" => "VARCHAR(120) UNIQUE",
            "description" => "TEXT",
            "logo_url" => "VARCHAR(255)",
            "status" => 'VARCHAR(20) DEFAULT "active"',
            "created_at" => "TIMESTAMP DEFAULT NOW()",
            "updated_at" => "TIMESTAMP DEFAULT NOW()",
        ],
    ],
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Database - ParfumAdmin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .code-block {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin: 10px 0;
            font-family: monospace;
            font-size: 14px;
            max-height: 400px;
            overflow-y: auto;
        }
        .table-name {
            background: #e9ecef;
            padding: 8px 15px;
            border-radius: 5px;
            font-weight: bold;
            margin: 20px 0 10px 0;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Setup Database Structure</h4>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <strong>Perhatian:</strong> Supabase tidak mendukung pembuatan tabel langsung via REST API.
                    Anda perlu menjalankan SQL berikut di SQL Editor di dashboard Supabase.
                </div>

                <?php foreach ($tables as $tableName => $tableInfo): ?>
                <div class="table-name">Tabel: <?= strtoupper(
                    $tableName,
                ) ?></div>
                <div class="code-block">
CREATE TABLE <?= $tableName ?> (
    <?php
    $columnDefs = [];
    foreach ($tableInfo["columns"] as $colName => $colType) {
        $columnDefs[] = "    $colName $colType";
    }
    echo implode(",\n", $columnDefs);
    ?>
);
                </div>
                <?php endforeach; ?>

                <div class="table-name">SQL Lengkap untuk Semua Tabel</div>
                <div class="code-block">
<?php
$fullSQL = "";
foreach ($tables as $tableName => $tableInfo) {
    $fullSQL .= "CREATE TABLE $tableName (\n";
    $columnDefs = [];
    foreach ($tableInfo["columns"] as $colName => $colType) {
        $columnDefs[] = "    $colName $colType";
    }
    $fullSQL .= implode(",\n", $columnDefs);
    $fullSQL .= "\n);\n\n";
}
echo htmlspecialchars($fullSQL);
?>
                </div>

                <div class="mt-4">
                    <a href="categories.php" class="btn btn-success">
                        <i class="fas fa-database me-2"></i>Lanjut ke Manajemen Kategori
                    </a>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-home me-2"></i>Kembali ke Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
