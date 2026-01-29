<?php
// test_email.php

require_once __DIR__ . '/path/to/sendCanvassEmail.php'; // i-adjust ang path

// Test values
$supplierEmail = 'purchasing-test@rosebakeshop.co'; // pwede imong email or laing email para test
$supplierName  = 'Test Supplier';
$itemDesc      = 'Sample Item';
$qty           = 10;
$unit          = 'pcs';
$canvassNo     = 'CANV-0001';
$token         = bin2hex(random_bytes(16)); // random token for test

$result = sendCanvassEmail($supplierEmail, $supplierName, $itemDesc, $qty, $unit, $canvassNo, $token);

if($result === true){
    echo "✅ Email sent successfully!";
} else {
    echo "❌ Failed to send email: " . $result;
}