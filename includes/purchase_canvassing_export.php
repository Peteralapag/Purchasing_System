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
// INPUT (GET or POST)
// ==========================
$from   = $_GET['from'] ?? $_POST['from'] ?? '';
$to     = $_GET['to'] ?? $_POST['to'] ?? '';
$status = $_GET['status'] ?? $_POST['status'] ?? '';

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
        pc.canvass_no,
        pc.pr_no,
        pc.requested_by,
        pc.status,
        pc.reviewed_by,
        pc.reviewed_date,
        pc.approved_by,
        pc.approved_date,
        pc.remarks,
        SUM(pci.estimated_cost * pci.quantity) AS total_estimated
    FROM purchase_canvassing pc
    LEFT JOIN purchase_canvassing_items pci ON pci.canvass_no = pc.canvass_no
    WHERE DATE(pc.created_at) BETWEEN ? AND ?
";

$params = [$fromDate, $toDate];
$types  = "ss";

if ($status !== '') {
    $sql .= " AND pc.status = ?";
    $params[] = $status;
    $types .= "s";
}

$sql .= " GROUP BY pc.canvass_no ORDER BY pc.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// ==========================
// CREATE EXCEL
// ==========================
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Purchase Canvassing');

// HEADERS
$headers = [
	'#',
    'Canvass No.',
    'P.R. No.',
    'Requested By',
    'Status',
    'Reviewed By',
    'Reviewed Date',
    'Approved By',
    'Approved Date',
    'Total Estimated'
];

$colLetters = range('A', 'Z');
foreach ($headers as $i => $header) {
    $sheet->setCellValue($colLetters[$i] . '1', $header);
    $sheet->getStyle($colLetters[$i] . '1')->getFont()->setBold(true);
    $sheet->getColumnDimension($colLetters[$i])->setAutoSize(true);
}

// DATA ROWS
$rowNum = 2;
$counter = 1; // numbering start
$max = 15; // optional truncation for remarks if needed

while ($row = $result->fetch_assoc()) {

    $displayStatus = strtoupper(str_replace('_', ' ', $row['status']));

    $sheet->setCellValue('A' . $rowNum, $counter); // <- numbering
    $sheet->setCellValue('B' . $rowNum, $row['canvass_no']);
    $sheet->setCellValue('C' . $rowNum, $row['pr_no']);
    $sheet->setCellValue('D' . $rowNum, $row['requested_by']);
    $sheet->setCellValue('E' . $rowNum, $displayStatus);
    $sheet->setCellValue('F' . $rowNum, $row['reviewed_by']);
    $sheet->setCellValue('G' . $rowNum, $row['reviewed_date']);
    $sheet->setCellValue('H' . $rowNum, $row['approved_by']);
    $sheet->setCellValue('I' . $rowNum, $row['approved_date']);
    $sheet->setCellValue('J' . $rowNum, $row['total_estimated']);

    $rowNum++;
    $counter++; // increment numbering
}

// FORMAT TOTAL ESTIMATED COLUMN

$sheet->getStyle("J2:J" . ($rowNum - 1))
      ->getNumberFormat()
      ->setFormatCode(NumberFormat::FORMAT_NUMBER_00);


// FREEZE HEADER ROW
$sheet->freezePane('A2');

// ==========================
// DOWNLOAD AS EXCEL
// ==========================
$filename = "purchase_canvassing_{$fromDate}_to_{$toDate}.xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
