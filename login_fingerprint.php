<?php
require 'config.php';
session_start();

$data = json_decode(file_get_contents('php://input'), true);
$credential = $data['credential'] ?? null;
$longitude = $data['longitude'] ?? null;
$latitude = $data['latitude'] ?? null;

if (!$credential || !$longitude || !$latitude) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid input data.']);
    exit;
}

$credential_id = $credential['id'] ?? null;
$authenticator_data = $credential['response']['authenticatorData'] ?? null;
$client_data_json = $credential['response']['clientDataJSON'] ?? null;
$signature = $credential['response']['signature'] ?? null;

if (!$credential_id || !$authenticator_data || !$client_data_json || !$signature) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Incomplete assertion data.']);
    exit;
}

// Validate the credential
try {
    $sql = "SELECT * FROM tbl_users WHERE credential_id = :credential_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':credential_id' => $credential_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Check if the user has already timed in today
        $check_sql = "SELECT * FROM tbl_timelogs WHERE student_id = :student_id AND DATE(time_in) = CURDATE()";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([':student_id' => $user['student_id']]);
        $existing_log = $check_stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing_log) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'You already timed in today.']);
        } else {
            // Insert log into tbl_timelogs
            $log_sql = "INSERT INTO tbl_timelogs (student_id, longitude, latitude, time_in) VALUES (:student_id, :longitude, :latitude, NOW())";
            $log_stmt = $pdo->prepare($log_sql);
            $log_stmt->execute([
                ':student_id' => $user['student_id'],
                ':longitude' => $longitude,
                ':latitude' => $latitude
            ]);

            // Set session variables
            $_SESSION['student_id'] = $user['student_id'];
            $_SESSION['firstname'] = $user['firstname'];
            $_SESSION['lastname'] = $user['lastname'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['phone'] = $user['phone'];
            $_SESSION['address'] = $user['address'];

            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Authentication failed.']);
    }
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
