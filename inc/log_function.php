<?php
// inc/log_function.php

/**
 * Fungsi untuk mendeteksi jenis device berdasarkan User Agent
 */
function detect_device($user_agent) {
    $user_agent = strtolower($user_agent);
    
    // Mobile devices
    $mobile_indicators = [
        'mobile', 'android', 'iphone', 'ipad', 'ipod', 'blackberry', 
        'windows phone', 'nokia', 'samsung', 'sony', 'lg', 'htc',
        'motorola', 'xiaomi', 'huawei', 'oppo', 'vivo', 'realme'
    ];
    
    foreach ($mobile_indicators as $indicator) {
        if (strpos($user_agent, $indicator) !== false) {
            return 'ponsel';
        }
    }
    
    // Tablet indicators
    $tablet_indicators = ['tablet', 'ipad'];
    foreach ($tablet_indicators as $indicator) {
        if (strpos($user_agent, $indicator) !== false) {
            return 'tablet';
        }
    }
    
    return 'komputer';
}

// Hanya deklarasikan fungsi jika belum ada
if (!function_exists('catat_log')) {
    /**
     * Fungsi untuk mencatat log aktivitas
     * 
     * @param mysqli $conn - Koneksi database
     * @param string $aktivitas - Deskripsi aktivitas
     * @param string $status - Status aktivitas (success/failed/info)
     * @param int|null $user_id - ID user (opsional)
     * @param string|null $username - Username (opsional)
     * @param string|null $detail - Detail tambahan (opsional)
     */
    function catat_log($conn, $aktivitas, $status = 'info', $user_id = null, $username = null, $detail = null) {
        // Ambil IP address
        $ip_address = get_client_ip();
        
        // Ambil User Agent
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        // Jika username tidak diberikan tapi user_id ada, ambil dari session atau database
        if ($user_id && !$username) {
            if (isset($_SESSION['username'])) {
                $username = $_SESSION['username'];
            } else {
                $query = mysqli_query($conn, "SELECT username FROM users WHERE id = $user_id");
                if ($row = mysqli_fetch_assoc($query)) {
                    $username = $row['username'];
                }
            }
        }
        
        // Jika user_id tidak diberikan tapi username ada, ambil dari session
        if (!$user_id && $username && isset($_SESSION['id'])) {
            $user_id = $_SESSION['id'];
        }
        
        // Prepare statement untuk insert log
        $query = "INSERT INTO log_aktivitas (user_id, username, aktivitas, ip_address, user_agent, status, detail) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt = mysqli_prepare($conn, $query)) {
            mysqli_stmt_bind_param($stmt, "issssss", $user_id, $username, $aktivitas, $ip_address, $user_agent, $status, $detail);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
}

if (!function_exists('get_client_ip')) {
    /**
     * Fungsi untuk mendapatkan IP address client
     */
    function get_client_ip() {
        $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Jika ada multiple IP (X-Forwarded-For), ambil yang pertama
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                // Validasi IP
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    }
}

if (!function_exists('get_log_aktivitas')) {
    /**
     * Fungsi untuk mengambil log aktivitas dengan pagination
     */
    function get_log_aktivitas($conn, $limit = 10, $offset = 0, $filter_aktivitas = '') {
        $where_clause = '';
        $params = [];
        $types = '';
        
        if (!empty($filter_aktivitas)) {
            $where_clause = "WHERE aktivitas LIKE ?";
            $params[] = "%$filter_aktivitas%";
            $types .= 's';
        }
        
        $query = "SELECT * FROM log_aktivitas $where_clause ORDER BY waktu DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        
        if ($stmt = mysqli_prepare($conn, $query)) {
            if (!empty($params)) {
                mysqli_stmt_bind_param($stmt, $types, ...$params);
            }
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $logs = mysqli_fetch_all($result, MYSQLI_ASSOC);
            mysqli_stmt_close($stmt);
            return $logs;
        }
        
        return [];
    }
}

if (!function_exists('count_log_aktivitas')) {
    /**
     * Fungsi untuk menghitung total log aktivitas
     */
    function count_log_aktivitas($conn, $filter_aktivitas = '') {
        $where_clause = '';
        $params = [];
        $types = '';
        
        if (!empty($filter_aktivitas)) {
            $where_clause = "WHERE aktivitas LIKE ?";
            $params[] = "%$filter_aktivitas%";
            $types .= 's';
        }
        
        $query = "SELECT COUNT(*) as total FROM log_aktivitas $where_clause";
        
        if ($stmt = mysqli_prepare($conn, $query)) {
            if (!empty($params)) {
                mysqli_stmt_bind_param($stmt, $types, ...$params);
            }
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $row['total'];
        }
        
        return 0;
    }
}

if (!function_exists('cleanup_old_logs')) {
    /**
     * Fungsi untuk membersihkan log lama (opsional)
     */
    function cleanup_old_logs($conn, $days = 30) {
        $query = "DELETE FROM log_aktivitas WHERE waktu < DATE_SUB(NOW(), INTERVAL ? DAY)";
        if ($stmt = mysqli_prepare($conn, $query)) {
            mysqli_stmt_bind_param($stmt, "i", $days);
            mysqli_stmt_execute($stmt);
            $affected = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            return $affected;
        }
        return 0;
    }
}
?>