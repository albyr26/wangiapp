<?php
// admin/header.php
require_once "../config.php";

// Check login
if (!isset($_SESSION["admin_logged_in"])) {
    header("Location: login.php");
    exit();
}

// Get stats for sidebar
$products = supabase("products", "GET", null, ["select" => "count"]);
$orders = supabase("orders", "GET", null, ["select" => "count"]);
$customers = supabase("cust_customers", "GET", null, ["select" => "count"]);

$orderCount = $orders["data"][0]["count"] ?? 0;
$customerCount = $customers["data"][0]["count"] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Parfum Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3a0ca3;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #ff9e00;
            --dark: #2b2d42;
            --light: #f8f9fa;
        }

        body {
            background-color: #f5f7fb;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        .sidebar {
            background: linear-gradient(180deg, var(--dark) 0%, #1a1b2e 100%);
            min-height: 100vh;
            position: fixed;
            width: 260px;
            box-shadow: 3px 0 20px rgba(0,0,0,0.1);
            z-index: 1000;
        }

        .sidebar-logo {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-logo h4 {
            color: white;
            font-weight: 700;
            margin: 0;
        }

        .sidebar-logo .badge {
            background: var(--primary);
            font-size: 10px;
            padding: 3px 8px;
        }

        .nav-link {
            color: rgba(255,255,255,0.7);
            padding: 14px 20px;
            margin: 5px 15px;
            border-radius: 10px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .nav-link:hover,
        .nav-link.active {
            background: rgba(67, 97, 238, 0.2);
            color: white;
            border-left: 4px solid var(--primary);
        }

        .nav-link i {
            width: 20px;
            text-align: center;
        }

        .main-content {
            margin-left: 260px;
            padding: 30px;
        }

        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            transition: transform 0.3s;
            border-top: 4px solid var(--primary);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--dark);
            margin: 10px 0 5px;
        }

        .stat-label {
            color: #6c757d;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .card-custom {
            background: white;
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }

        .card-header-custom {
            background: white;
            border-bottom: 2px solid #f0f0f0;
            padding: 20px 25px;
            border-radius: 15px 15px 0 0 !important;
        }

        .table-custom {
            margin: 0;
        }

        .table-custom thead th {
            border-bottom: 2px solid #f0f0f0;
            font-weight: 600;
            color: var(--dark);
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 1px;
        }

        .table-custom tbody tr:hover {
            background-color: #f8f9ff;
        }

        .btn-primary {
            background: var(--primary);
            border: none;
            padding: 10px 25px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            background: var(--secondary);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }

        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--success));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .badge-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: relative;
                min-height: auto;
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
    <!-- Di header.php dalam tag <style> -->
    <style>
    /* Animasi untuk auto-hide alerts */
    .alert-auto-hide {
        animation: fadeOut 0.5s ease 5s forwards;
        opacity: 1;
    }

    @keyframes fadeOut {
        from {
            opacity: 1;
            transform: translateY(0);
        }
        to {
            opacity: 0;
            transform: translateY(-10px);
            max-height: 0;
            padding: 0;
            margin: 0;
            overflow: hidden;
        }
    }

    /* Untuk alert yang ingin permanen (jangan auto-hide) */
    .alert-permanent {
        animation: none !important;
    }
    </style>
</head>
<body>
