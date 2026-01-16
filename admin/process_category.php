<?php
// admin/process_category.php
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

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $_SESSION["error"] = "Metode request tidak valid!";
    header("Location: categories.php");
    exit();
}

$action = $_POST["action"] ?? "";
$id = $_POST["id"] ?? "";

switch ($action) {
    case "add":
        $name = $_POST["name"] ?? "";
        $description = $_POST["description"] ?? "";

        if (empty($name)) {
            $_SESSION["error"] = "Nama kategori wajib diisi!";
            header("Location: categories.php");
            exit();
        }

        $data = [
            "name" => $name,
            "description" => $description,
        ];

        $result = supabase("cust_categories", "POST", $data);

        if ($result["success"]) {
            $_SESSION["success"] = "Kategori berhasil ditambahkan!";
        } else {
            $_SESSION["error"] =
                "Gagal menambahkan kategori: " .
                ($result["error"] ?? "Unknown error");
        }
        break;

    case "edit":
        $name = $_POST["name"] ?? "";
        $description = $_POST["description"] ?? "";

        if (empty($id) || empty($name)) {
            $_SESSION["error"] = "Data tidak valid!";
            header("Location: categories.php");
            exit();
        }

        $data = [
            "name" => $name,
            "description" => $description,
        ];

        $result = supabase("cust_categories", "PATCH", $data, [
            "id" => "eq." . $id,
        ]);

        if ($result["success"]) {
            $_SESSION["success"] = "Kategori berhasil diperbarui!";
        } else {
            $_SESSION["error"] =
                "Gagal memperbarui kategori: " .
                ($result["error"] ?? "Unknown error");
        }
        break;

    case "delete":
        if (empty($id)) {
            $_SESSION["error"] = "ID kategori tidak valid!";
            header("Location: categories.php");
            exit();
        }

        $result = supabase("cust_categories", "DELETE", null, [
            "id" => "eq." . $id,
        ]);

        if ($result["success"]) {
            $_SESSION["success"] = "Kategori berhasil dihapus!";
        } else {
            $_SESSION["error"] =
                "Gagal menghapus kategori: " .
                ($result["error"] ?? "Unknown error");
        }
        break;

    default:
        $_SESSION["error"] = "Aksi tidak valid!";
        break;
}

header("Location: categories.php");
exit();
?>
