<?php
// ==========================
// INIT & DB
// ==========================
include '../../../init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php'; // PhpSpreadsheet autoload

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
if ($db->connect_error) {
    die('DB Connection failed');
}

// ==========================
// INPUT (GET)
// ==========================
$from   = $_GET['from'] ?? '';
$to     = $_GET['to'] ?? '';
$status = $_GET['status'] ?? '';

if (!$from || !$to) {
    die('Invalid date range');
}

$fromDate = date('Y-m-d', strtotime($from));
$toDate   = date('Y-m-d', strtotime($to));

// ==========================
// QUERY
// ==========================
$sql = "
    SELECT 
        po.po_number,
        po.pr_number,
        po.branch,
        po.order_date,
        po.expected_delivery,
        po.status,
        po.total_amount,
        po.approved_by,
        po.approved_date,
        s.name AS supplier_name
    FROM purchase_orders po
    JOIN suppliers s ON s.id = po.supplier_id
    WHERE DATE(po.order_date) BETWEEN ? AND ?
";

$params = [$fromDate, $toDate];
$types  = "ss";

if ($status !== '') {
    $sql .= " AND po.status = ?";
    $params[] = $status;
    $types .= "s";
}

$sql .= " ORDER BY po.order_date DESC";

$stmt = $db->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// ==========================
// CREATE EXCEL
// ==========================
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Purchase Orders');

// HEADERS
$headers = [
	'#',
    'PO No.',
    'P.R. No.',
    'Branch',
    'Supplier',
    'Order Date',
    'Expected Delivery',
    'Status',
    'Total Amount',
    'Approved By',
    'Approved Date'
];

// PhpSpreadsheet columns use letters
$colLetters = range('A', 'Z'); // enough for up to 26 columns
foreach ($headers as $i => $header) {
    $sheet->setCellValue($colLetters[$i] . '1', $header);
    $sheet->getStyle($colLetters[$i] . '1')->getFont()->setBold(true);
    $sheet->getColumnDimension($colLetters[$i])->setAutoSize(true);
}

// DATA ROWS
$rowNum = 2;
$counter = 1;

while ($row = $result->fetch_assoc()) {

    $displayStatus = strtoupper(str_replace('_', ' ', $row['status']));

    $sheet->setCellValue('A' . $rowNum, $counter);
    $sheet->setCellValue('B' . $rowNum, $row['po_number']);
    $sheet->setCellValue('C' . $rowNum, $row['pr_number']);
    $sheet->setCellValue('D' . $rowNum, $row['branch']);
    $sheet->setCellValue('E' . $rowNum, $row['supplier_name']);
    $sheet->setCellValue('F' . $rowNum, $row['order_date']);
    $sheet->setCellValue('G' . $rowNum, $row['expected_delivery']);
    $sheet->setCellValue('H' . $rowNum, $displayStatus);
    $sheet->setCellValue('I' . $rowNum, $row['total_amount']);
    $sheet->setCellValue('J' . $rowNum, $row['approved_by']);
    $sheet->setCellValue('K' . $rowNum, $row['approved_date']);

    $rowNum++;
    $counter++;
}

// FORMAT TOTAL AMOUNT COLUMN
$sheet->getStyle("H2:H" . ($rowNum - 1))
      ->getNumberFormat()
      ->setFormatCode(NumberFormat::FORMAT_NUMBER_00);

// FREEZE HEADER ROW
$sheet->freezePane('A2');


// ==========================
// DOWNLOAD AS EXCEL
// ==========================
$filename = "purchase_orders_{$fromDate}_to_{$toDate}.xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
