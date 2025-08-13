<?php
session_start();

// SECURITY CHECK: Only allow access if the user is a logged-in admin
if (!isset($_SESSION['admin'])) {
    header('HTTP/1.1 401 Unauthorized');
    die(json_encode(['error' => 'Authentication required.']));
}

$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

if ($data === null) {
    header('HTTP/1.1 400 Bad Request');
    die(json_encode(['error' => 'Invalid data format.']));
}

$file_path = 'site_data.json';
$new_json_data = json_encode($data, JSON_PRETTY_PRINT);

if (file_put_contents($file_path, $new_json_data)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Data saved successfully.']);
} else {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Failed to write to data file.']);
}