<?php
require_once "../config/database.php"; // Adjust path if needed

header("Content-Type: application/json");

$barcode = $_GET['barcode'] ?? '';

if ($barcode === '') {
    echo json_encode(["found" => false]);
    exit;
}

// Look up product using barcode
$sql = "SELECT id, name, price FROM products WHERE barcode = ? AND is_archived = 0 LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $barcode);
$stmt->execute();
$result = $stmt->get_result();

// Product found
if ($result->num_rows > 0) {
    $p = $result->fetch_assoc();
    
    echo json_encode([
        "found" => true,
        "id"    => $p['id'],
        "name"  => $p['name'],
        "price" => $p['price']
    ]);
    exit;
}

// Product NOT found
echo json_encode(["found" => false]);
