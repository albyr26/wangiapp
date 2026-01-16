<?php
// config.php - Shared configuration dengan optimasi kecepatan

// Konfigurasi Supabase
define("SUPABASE_URL", "https://iukwrvjdjdzurctlotea.supabase.co");
define(
    "SUPABASE_KEY",
    "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Iml1a3dydmpkamR6dXJjdGxvdGVhIiwicm9sZSI6ImFub24iLCJpYXQiOjE3Njc5NjA5ODIsImV4cCI6MjA4MzUzNjk4Mn0.WBdE54hxNVb_TjDU--L0NnlBUm8eCChheTyZykc0K-0",
);
define("SUPABASE_JWT", SUPABASE_KEY);

// Set default timezone
date_default_timezone_set("Asia/Jakarta");

// Start session jika belum
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting untuk development
if (
    isset($_SERVER["HTTP_HOST"]) &&
    ($_SERVER["HTTP_HOST"] == "localhost" ||
        $_SERVER["HTTP_HOST"] == "127.0.0.1")
) {
    error_reporting(E_ALL);
    ini_set("display_errors", 1);
    define("DEBUG_MODE", true);
    define("CACHE_ENABLED", false); // Nonaktifkan cache di localhost untuk development
} else {
    error_reporting(E_ALL & ~E_NOTICE);
    ini_set("display_errors", 0);
    define("DEBUG_MODE", false);
    define("CACHE_ENABLED", true); // Aktifkan cache di production
}

// OPTIMASI: CACHE CONFIG (dipindahkan ke sini)
if (!defined("CACHE_DIR")) {
    define("CACHE_DIR", __DIR__ . "/cache/");
}
if (!defined("CACHE_TTL")) {
    define("CACHE_TTL", 300); // 5 menit default
}

// ==================== CACHE HELPER FUNCTIONS ====================
function cache_get($key)
{
    if (!defined("CACHE_ENABLED") || !CACHE_ENABLED) {
        return false;
    }

    $cacheFile = CACHE_DIR . md5($key) . ".json";

    if (file_exists($cacheFile) && time() - filemtime($cacheFile) < CACHE_TTL) {
        $data = file_get_contents($cacheFile);
        return json_decode($data, true);
    }

    return false;
}

function cache_set($key, $data)
{
    if (!defined("CACHE_ENABLED") || !CACHE_ENABLED) {
        return false;
    }

    if (!is_dir(CACHE_DIR)) {
        mkdir(CACHE_DIR, 0755, true);
    }

    $cacheFile = CACHE_DIR . md5($key) . ".json";
    file_put_contents($cacheFile, json_encode($data));
    return true;
}

function cache_clear($key = null)
{
    if ($key === null) {
        array_map("unlink", glob(CACHE_DIR . "*.json"));
    } else {
        $cacheFile = CACHE_DIR . md5($key) . ".json";
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
    }
}

// ==================== OPTIMASI SUPPABASE FUNCTION ====================
function supabase($endpoint, $method = "GET", $data = null, $filters = [], $preferHeader = null)
{
    // OPTIMASI: Gunakan cache untuk GET requests
    if ($method === "GET" && defined("CACHE_ENABLED") && CACHE_ENABLED) {
        $cacheKey = "supabase_" . $endpoint . "_" . md5(json_encode($filters));
        $cached = cache_get($cacheKey);
        if ($cached !== false) {
            return $cached;
        }
    }

    $url = SUPABASE_URL . "/rest/v1/" . $endpoint;

    // OPTIMASI: Build query parameters dengan efisien
    $queryParams = [];

    // Untuk PATCH/DELETE dengan filter id
    if (
        ($method === "PATCH" || $method === "DELETE") &&
        isset($filters["id"])
    ) {
        $id_value = $filters["id"];
        if (strpos($id_value, "eq.") === 0) {
            $id_value = substr($id_value, 3);
        }
        $queryParams[] = "id=eq." . urlencode($id_value);
    }
    // Untuk GET dengan filter lainnya
    elseif ($method === "GET" && !empty($filters)) {
        foreach ($filters as $key => $value) {
            if (!empty($value)) {
                if (
                    strpos($value, "eq.") === 0 ||
                    strpos($value, "gt.") === 0 ||
                    strpos($value, "lt.") === 0 ||
                    strpos($value, "in.") === 0 ||
                    strpos($value, "like.") === 0
                ) {
                    $queryParams[] = $key . "=" . $value;
                } else {
                    $queryParams[] = $key . "=" . urlencode($value);
                }
            }
        }
    }

    if (!empty($queryParams)) {
        $url .= "?" . implode("&", $queryParams);
    }

    $headers = [
        "apikey: " . SUPABASE_KEY,
        "Authorization: Bearer " . SUPABASE_KEY,
        "Content-Type: application/json",
        // Default untuk semua method kecuali ada parameter $preferHeader
    ];

    // Set Prefer header berdasarkan parameter atau default
    if ($preferHeader !== null) {
        $headers[] = "Prefer: " . $preferHeader;
    } else {
        // Default behavior:
        // - POST/PATCH: return=representation (untuk mendapatkan data baru)
        // - GET/DELETE: return=minimal (untuk performa)
        if ($method === "POST" || $method === "PATCH") {
            $headers[] = "Prefer: return=representation";
        } else {
            $headers[] = "Prefer: return=minimal";
        }
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_ENCODING, "gzip");

    if ($method === "POST") {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    } elseif ($method === "PATCH") {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    } elseif ($method === "DELETE") {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
    curl_close($ch);

    $result = [
        "success" => $httpCode >= 200 && $httpCode < 300,
        "code" => $httpCode,
        "data" => json_decode($response, true) ?: [],
        "raw" => $response,
        "error" => $error,
        "url" => $url,
        "time" => $totalTime,
    ];

    // OPTIMASI: Cache hasil GET request
    if (
        $method === "GET" &&
        $result["success"] &&
        defined("CACHE_ENABLED") &&
        CACHE_ENABLED
    ) {
        $cacheKey = "supabase_" . $endpoint . "_" . md5(json_encode($filters));
        cache_set($cacheKey, $result);
    }

    return $result;
}

// ==================== OPTIMASI: BATCH REQUEST FUNCTION ====================
function supabase_batch($requests)
{
    /**
     * OPTIMASI: Eksekusi multiple requests sekaligus
     * Format $requests: [
     *     ['endpoint' => 'table', 'method' => 'GET', 'filters' => []],
     *     ...
     * ]
     */

    if (!is_array($requests) || count($requests) === 0) {
        return [];
    }

    $results = [];

    // Jika hanya 1 request, gunakan fungsi normal
    if (count($requests) === 1) {
        $req = $requests[0];
        $results[0] = supabase(
            $req["endpoint"] ?? "",
            $req["method"] ?? "GET",
            $req["data"] ?? null,
            $req["filters"] ?? [],
        );
        return $results;
    }

    // Untuk multiple requests, eksekusi parallel (simplified)
    foreach ($requests as $index => $request) {
        $results[$index] = supabase(
            $request["endpoint"] ?? "",
            $request["method"] ?? "GET",
            $request["data"] ?? null,
            $request["filters"] ?? [],
        );
    }

    return $results;
}

// ==================== OPTIMASI: QUICK COUNT FUNCTION ====================
function supabase_count($endpoint, $filters = [])
{
    // OPTIMASI: Fungsi khusus untuk count yang lebih cepat
    $cacheKey = "count_" . $endpoint . "_" . md5(json_encode($filters));

    if (defined("CACHE_ENABLED") && CACHE_ENABLED) {
        $cached = cache_get($cacheKey);
        if ($cached !== false) {
            return $cached;
        }
    }

    $url = SUPABASE_URL . "/rest/v1/" . $endpoint;

    // Set query parameters untuk count
    $queryParams = ["select" => "count"];

    if (!empty($filters)) {
        foreach ($filters as $key => $value) {
            if (!empty($value)) {
                $queryParams[$key] = $value;
            }
        }
    }

    $url .= "?" . http_build_query($queryParams);

    $headers = [
        "apikey: " . SUPABASE_KEY,
        "Authorization: Bearer " . SUPABASE_KEY,
        "Content-Type: application/json",
        "Prefer: count=exact",
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Timeout sangat pendek untuk count
    curl_setopt($ch, CURLOPT_HEADER, true); // Untuk membaca header

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Parse content-range header untuk count
    $count = 0;
    if (
        preg_match(
            "/content-range: \w+ \d+-(\d+)\/(\d+)/i",
            $response,
            $matches,
        )
    ) {
        $count = intval($matches[2]);
    }

    curl_close($ch);

    if (defined("CACHE_ENABLED") && CACHE_ENABLED) {
        cache_set($cacheKey, $count);
    }

    return $count;
}

// ==================== FUNGSI HELPER LAINNYA ====================
// Upload to Supabase Storage
function supabaseUpload($bucket, $filePath, $fileName)
{
    $url = SUPABASE_URL . "/storage/v1/object/" . $bucket . "/" . $fileName;

    $headers = [
        "apikey: " . SUPABASE_KEY,
        "Authorization: Bearer " . SUPABASE_KEY,
        "Content-Type: " . mime_content_type($filePath),
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents($filePath));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    curl_close($ch);

    return SUPABASE_URL .
        "/storage/v1/object/public/" .
        $bucket .
        "/" .
        $fileName;
}

// Function untuk handle image URL (SIMPLE VERSION - tanpa GD)
function handleImageUpload($image_data, $max_length = 255)
{
    if (strlen($image_data) <= $max_length) {
        return $image_data;
    }

    if (strpos($image_data, "data:image") === 0) {
        return "https://via.placeholder.com/300x300/6c757d/ffffff?text=Product+Image";
    }

    if (strlen($image_data) > $max_length) {
        return substr($image_data, 0, $max_length);
    }

    return $image_data;
}

// Function untuk mendapatkan public URL gambar
function getImageUrl($path)
{
    if (empty($path)) {
        return "https://via.placeholder.com/300x300/6c757d/ffffff?text=No+Image";
    }

    if (strpos($path, "http") === 0) {
        return $path;
    }

    if (strpos($path, "data:image") === 0) {
        return $path;
    }

    return "https://via.placeholder.com/300x300/6c757d/ffffff?text=No+Image";
}

// Function untuk validasi dan sanitasi data
function sanitizeData($data, $max_length = 255)
{
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $value = trim($value);
                $value = htmlspecialchars($value, ENT_QUOTES, "UTF-8");

                if (strlen($value) > $max_length) {
                    $value = substr($value, 0, $max_length);
                }

                $data[$key] = $value;
            }
        }
    } elseif (is_string($data)) {
        $data = trim($data);
        $data = htmlspecialchars($data, ENT_QUOTES, "UTF-8");
        if (strlen($data) > $max_length) {
            $data = substr($data, 0, $max_length);
        }
    }

    return $data;
}

// ==================== OPTIMASI: FAST DEBUG FUNCTION ====================
function debugLog($message, $data = null)
{
    // OPTIMASI: Nonaktifkan di production
    if (!defined("DEBUG_MODE") || !DEBUG_MODE) {
        return;
    }

    $log = "[" . date("Y-m-d H:i:s") . "] " . $message;

    if ($data !== null) {
        if (is_array($data) || is_object($data)) {
            // OPTIMASI: Limit data yang di-log
            if (count((array) $data) > 10) {
                $log .=
                    ": [DATA TOO LARGE, " . count((array) $data) . " items]";
            } else {
                $log .= ": " . json_encode($data);
            }
        } else {
            // OPTIMASI: Truncate string panjang
            if (strlen($data) > 500) {
                $data = substr($data, 0, 500) . "... [TRUNCATED]";
            }
            $log .= ": " . $data;
        }
    }

    error_log($log);
}

// ==================== OPTIMASI: MEMORY & PERFORMANCE SETTINGS ====================
// Set memory limit yang cukup
if (!ini_get("memory_limit") || ini_get("memory_limit") < "128M") {
    ini_set("memory_limit", "128M");
}

// Enable output compression jika tersedia
if (extension_loaded("zlib") && !ini_get("zlib.output_compression")) {
    ob_start("ob_gzhandler");
}

// OPTIMASI: Pre-clear cache lama
if (defined("CACHE_ENABLED") && CACHE_ENABLED && is_dir(CACHE_DIR)) {
    $cacheFiles = glob(CACHE_DIR . "*.json");
    $now = time();
    foreach ($cacheFiles as $file) {
        if ($now - filemtime($file) > CACHE_TTL) {
            @unlink($file);
        }
    }
}

// ==================== OPTIMASI: GZIP COMPRESSION CHECK ====================
function isGzipSupported()
{
    return isset($_SERVER["HTTP_ACCEPT_ENCODING"]) &&
        strpos($_SERVER["HTTP_ACCEPT_ENCODING"], "gzip") !== false;
}

// ==================== OPTIMASI: MINIMAL SESSION DATA ====================
// Hapus session data yang tidak perlu
function cleanSessionData()
{
    $keep = ["admin_logged_in", "admin_name", "admin_role", "success", "error"];
    foreach ($_SESSION as $key => $value) {
        if (!in_array($key, $keep)) {
            unset($_SESSION[$key]);
        }
    }
}

// Jalankan cleanup sesekali
if (rand(1, 100) === 1) {
    // 1% chance setiap request
    cleanSessionData();
}
?>
