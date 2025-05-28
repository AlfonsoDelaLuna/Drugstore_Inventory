<?php
session_start();

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Color;

// Check admin role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Database connection
$host = 'localhost';
$dbname = 'inventory_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die("An error occurred. Please try again later.");
}

// Fetch inventory data
try {
    $stmt = $pdo->query("SELECT name, selling_price, unit_cost, quantity, remaining_items, expiration_date FROM inventory ORDER BY name ASC");
    $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Error fetching inventory data: " . $e->getMessage();
    header("Location: admin_inventory.php");
    exit();
}

// Create new Spreadsheet object
$spreadsheet = new Spreadsheet();

// Set document properties
$spreadsheet->getProperties()->setCreator('STI Clinic System')
    ->setLastModifiedBy('STI Clinic System')
    ->setTitle('Inventory List')
    ->setSubject('Clinic Inventory')
    ->setDescription('Inventory list for the clinic.')
    ->setKeywords('inventory, clinic')
    ->setCategory('Inventory');

// Add header row
$sheet = $spreadsheet->getActiveSheet();
$sheet->setCellValue('A1', 'Name');
$sheet->setCellValue('B1', 'Selling Price');
$sheet->setCellValue('C1', 'Unit Cost');
$sheet->setCellValue('D1', 'Quantity');
$sheet->setCellValue('E1', 'Remaining Items');
$sheet->setCellValue('F1', 'Expiration Date');

// Style header row
$headerStyle = [
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '012365'],
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
    ],
];
$sheet->getStyle('A1:F1')->applyFromArray($headerStyle);

// Add data rows
$row = 2;
foreach ($inventory as $item) {
    $sheet->setCellValue('A' . $row, $item['name']);
    $sheet->setCellValue('B' . $row, number_format($item['selling_price'], 2));
    $sheet->setCellValue('C' . $row, number_format($item['unit_cost'], 2));
    $sheet->setCellValue('D' . $row, $item['quantity']);
    $sheet->setCellValue('E' . $row, $item['remaining_items']);

    // Format expiration date as mm/dd/yyyy
    $expirationDate = $item['expiration_date'];
    $expirationColor = '000000'; // Black by default
    if ($expirationDate) {
        $date = new DateTime($expirationDate);
        $month = $date->format("F");
        $day = $date->format("d");
        $year = $date->format("Y");
        $expirationDate = $month . " " . $day . ", " . $year;

        // Calculate expiration status
        $now = new DateTime('now', new DateTimeZone('UTC'));
        $expiration = new DateTime($item['expiration_date'], new DateTimeZone('UTC'));
        $interval = $now->diff($expiration);
        $months = $interval->m + ($interval->y * 12);

        if ($expiration < $now) {
            $expirationColor = 'FF0000'; // Red - Expired
        } elseif ($months < 2) {
            $expirationColor = 'FF0000'; // Red - Less than 3 months
        } elseif ($months <= 6) {
            $expirationColor = '0000FF'; // Blue - Between 3-6 months
        }
    }
    $sheet->setCellValue('F' . $row, $expirationDate);

    // Set text color for expiration date
    $sheet->getStyle('F' . $row)->getFont()->setColor(new Color($expirationColor));

    // Center text in data rows
    $sheet->getStyle('A' . $row . ':F' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $row++;
}

// Set column widths
$sheet->getColumnDimension('A')->setWidth(40);
$sheet->getColumnDimension('B')->setWidth(20);
$sheet->getColumnDimension('C')->setWidth(20);
$sheet->getColumnDimension('D')->setWidth(15);
$sheet->getColumnDimension('E')->setWidth(20);
$sheet->getColumnDimension('F')->setWidth(20);

// Set number format for selling price and unit cost
$sheet->getStyle('B2:C' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0.00');

// Rename worksheet
$sheet->setTitle('Inventory');

// Set active sheet index to the first sheet, so Excel opens this as the first sheet
$spreadsheet->setActiveSheetIndex(0);

// Redirect output to a client’s web browser (Xlsx)
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="inventory-' . date('Y-m-d') . '.xlsx"');
header('Cache-Control: max-age=0');
// If you're serving to IE over SSL, then the following may be needed
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
header('Pragma: public'); // HTTP/1.0

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
