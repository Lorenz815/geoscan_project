<?php
// register_fingerprint.php
require 'config.php';

$data = json_decode(file_get_contents('php://input'), true);
$student_id = $data['student_id'] ?? null;
$credential = $data['credential'] ?? null;

if (!$student_id || !$credential) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid input data.']);
    exit;
}

$credential_id = $credential['id'] ?? null;
$attestation_object = $credential['response']['attestationObject'] ?? null;
$client_data_json = $credential['response']['clientDataJSON'] ?? null;

if (!$credential_id || !$attestation_object || !$client_data_json) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Incomplete credential data.']);
    exit;
}

// Store the credential
try {
    $sql = "INSERT INTO users (student_id, credential_id, attestation_object, client_data_json) VALUES (:student_id, :credential_id, :attestation_object, :client_data_json)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':student_id' => $student_id,
        ':credential_id' => $credential_id,
        ':attestation_object' => $attestation_object,
        ':client_data_json' => $client_data_json
    ]);

    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

?>