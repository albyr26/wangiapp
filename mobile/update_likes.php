<?php
// update_likes.php - VERSI REAL SUPABASE
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Include config untuk Supabase credentials
require_once "../config.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $raw_input = file_get_contents("php://input");
    $data = json_decode($raw_input, true);

    if (!$data || !isset($data["product_id"])) {
        echo json_encode([
            "success" => false,
            "error" => "Data tidak lengkap. product_id diperlukan.",
            "received_data" => $data,
        ]);
        exit();
    }

    $product_id = $data["product_id"];
    $delta = isset($data["delta"]) ? (int) $data["delta"] : 1;

    try {
        // ============================================
        // BAGIAN 1: AMBIL DATA PRODUK TERKINI DARI SUPABASE
        // ============================================
        $url =
            SUPABASE_URL .
            "/rest/v1/cust_products?id=eq." .
            urlencode($product_id) .
            "&select=id,likes_count";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "apikey: " . SUPABASE_KEY,
                "Authorization: Bearer " . SUPABASE_JWT,
                "Content-Type: application/json",
                "Prefer: return=representation",
            ],
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            throw new Exception("CURL Error: " . curl_error($ch));
        }

        curl_close($ch);

        $product_data = json_decode($response, true);

        if ($http_code !== 200 || empty($product_data)) {
            throw new Exception("Produk tidak ditemukan di database");
        }

        $current_likes = (int) ($product_data[0]["likes_count"] ?? 0);

        // ============================================
        // BAGIAN 2: UPDATE LIKE COUNT DI SUPABASE
        // ============================================
        if ($delta > 0) {
            // LIKE: tambah 1
            $new_likes = $current_likes + 1;
            $update_data = json_encode([
                "likes_count" => $new_likes,
                "updated_at" => date("Y-m-d H:i:s"),
            ]);
        } else {
            // UNLIKE: kurangi 1 (minimal 0)
            $new_likes = max(0, $current_likes - 1);
            $update_data = json_encode([
                "likes_count" => $new_likes,
                "updated_at" => date("Y-m-d H:i:s"),
            ]);
        }

        $update_url =
            SUPABASE_URL .
            "/rest/v1/cust_products?id=eq." .
            urlencode($product_id);

        $ch_update = curl_init();
        curl_setopt_array($ch_update, [
            CURLOPT_URL => $update_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "PATCH",
            CURLOPT_POSTFIELDS => $update_data,
            CURLOPT_HTTPHEADER => [
                "apikey: " . SUPABASE_KEY,
                "Authorization: Bearer " . SUPABASE_JWT,
                "Content-Type: application/json",
                "Prefer: return=representation",
            ],
        ]);

        $update_response = curl_exec($ch_update);
        $update_http_code = curl_getinfo($ch_update, CURLINFO_HTTP_CODE);

        if (curl_errno($ch_update)) {
            throw new Exception("CURL Update Error: " . curl_error($ch_update));
        }

        curl_close($ch_update);

        // ============================================
        // BAGIAN 3: RESPON KE CLIENT
        // ============================================
        if ($update_http_code >= 200 && $update_http_code < 300) {
            echo json_encode([
                "success" => true,
                "new_likes_count" => $new_likes,
                "message" => "Like berhasil disimpan ke database",
                "debug" => [
                    "old_count" => $current_likes,
                    "new_count" => $new_likes,
                    "product_id" => $product_id,
                ],
            ]);
        } else {
            throw new Exception(
                "Gagal update database. HTTP Code: $update_http_code",
            );
        }
    } catch (Exception $e) {
        echo json_encode([
            "success" => false,
            "error" => "Database error: " . $e->getMessage(),
            "debug_info" => [
                "product_id" => $product_id,
                "delta" => $delta,
            ],
        ]);
    }
} else {
    echo json_encode(["success" => false, "error" => "Method tidak diizinkan"]);
}
?>
