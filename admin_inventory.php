<?php
session_start();
session_regenerate_id(true);

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

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

// Handle item deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['item_id'])) {
    $item_id = (int) $_POST['item_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM inventory WHERE id = ?");
        $stmt->execute([$item_id]);
        $_SESSION['message'] = "Item removed successfully.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error removing item: " . $e->getMessage();
    }
    header("Location: admin_inventory.php");
    exit();
}

// Handle form submission for adding/updating an item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['item-name'])) {
    $itemName = trim($_POST['item-name']);
    $sellingPrice = (float) $_POST['selling-price'];
    $unitCost = (float) $_POST['unit-cost'];
    $remainingItems = (int) $_POST['remaining-items'];

    // Handle expiration date from dropdowns
    $expirationDate = null;
    if (!empty($_POST['exp-month']) && !empty($_POST['exp-day']) && !empty($_POST['exp-year'])) {
        $month = str_pad((int) $_POST['exp-month'], 2, '0', STR_PAD_LEFT);
        $day = str_pad((int) $_POST['exp-day'], 2, '0', STR_PAD_LEFT);
        $year = (int) $_POST['exp-year'];
        $dateStr = "$month/$day/$year";
        try {
            $expirationDate = DateTime::createFromFormat('m/d/Y', $dateStr);
            if ($expirationDate) {
                $expirationDate = $expirationDate->format('Y-m-d');
            } else {
                $_SESSION['error'] = 'Invalid expiration date.';
                header("Location: admin_inventory.php");
                exit();
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Invalid expiration date.';
            header("Location: admin_inventory.php");
            exit();
        }
    }

    $itemId = isset($_POST['item_id']) ? (int) $_POST['item_id'] : null;

    // Validation
    if (empty($itemName)) {
        $_SESSION['error'] = 'Item name cannot be empty.';
    } elseif ($sellingPrice < 0) {
        $_SESSION['error'] = 'Selling price must be non-negative.';
    } elseif ($unitCost < 0) {
        $_SESSION['error'] = 'Unit cost must be non-negative.';
    } elseif ($remainingItems < 0) {
        $_SESSION['error'] = 'Remaining items must be non-negative.';
    } elseif ($remainingItems > 1000) {
        $_SESSION['error'] = "Remaining items for '$itemName' cannot exceed 1000.";
    } else {
        try {
            if ($itemId) {
                // Editing an existing item
                $checkStmt = $pdo->prepare("SELECT id FROM inventory WHERE name = ? AND id != ?");
                $checkStmt->execute([$itemName, $itemId]);
                if ($checkStmt->fetch()) {
                    $_SESSION['error'] = "Another item with the name '$itemName' already exists.";
                    header("Location: admin_inventory.php");
                    exit();
                }
                $updateStmt = $pdo->prepare("UPDATE inventory SET name = ?, selling_price = ?, unit_cost = ?, remaining_items = ?, expiration_date = ? WHERE id = ?");
                $updateStmt->execute([$itemName, $sellingPrice, $unitCost, $remainingItems, $expirationDate, $itemId]);
                $_SESSION['message'] = "Item updated successfully.";
            } else {
                // Adding a new item
                $stmt = $pdo->prepare("SELECT * FROM inventory WHERE name = ? AND expiration_date = ?");
                $stmt->execute([$itemName, $expirationDate]);
                $existingItem = $stmt->fetch();
                if ($existingItem) {
                    // Item with same name and expiration date exists, update quantity
                    $updateStmt = $pdo->prepare("UPDATE inventory SET remaining_items = remaining_items + ? WHERE name = ? AND expiration_date = ?");
                    $updateStmt->execute([$remainingItems, $itemName, $expirationDate]);
                    $_SESSION['message'] = "Item quantity updated successfully.";
                    header("Location: admin_inventory.php");
                    exit();
                } else {
                    $insertStmt = $pdo->prepare("INSERT INTO inventory (name, selling_price, unit_cost, quantity, remaining_items, expiration_date) VALUES (?, ?, ?, 0, ?, ?)");
                    $insertStmt->execute([$itemName, $sellingPrice, $unitCost, $remainingItems, $expirationDate]);
                    $_SESSION['message'] = "Item added successfully.";
                }
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Database error: " . $e->getMessage();
        }
    }
    header("Location: admin_inventory.php");
    exit();
}

// Handle form submission for clearing the inventory
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear'])) {
    try {
        $clearStmt = $pdo->prepare("DELETE FROM inventory");
        $clearStmt->execute();
        $_SESSION['message'] = "Inventory cleared successfully.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error clearing inventory: " . $e->getMessage();
    }
    header("Location: admin_inventory.php");
    exit();
}

// Handle form submission for importing from Excel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel-file'])) {
    if ($_FILES['excel-file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['excel-file']['tmp_name'];
        $fileName = $_FILES['excel-file']['name'];
        $uploadFileDir = './uploaded_files/';
        $destPath = $uploadFileDir . $fileName;
        if (!is_dir($uploadFileDir)) {
            mkdir($uploadFileDir, 0755, true);
        }
        if (move_uploaded_file($fileTmpPath, $destPath)) {
            try {
                $reader = IOFactory::createReader('Xlsx');
                $spreadsheet = $reader->load($destPath);
                $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

                $itemsToProcess = [];
                $errors = [];
                $processedCount = 0;

                foreach ($sheetData as $rowIndex => $row) {
                    if ($rowIndex == 1 || empty(trim($row['A'] ?? ''))) {
                        continue;
                    }
                    $itemName = trim($row['A']);
                    $sellingPrice = (float) trim($row['B'] ?? 0);
                    $unitCost = (float) trim($row['C'] ?? 0);
                    $remainingItems = (int) trim($row['E'] ?? 0);
                    $expirationDateStr = trim($row['F'] ?? '');
                    $expirationDate = null;

                    if (empty($itemName)) {
                        $errors[] = "Row $rowIndex: Item name is empty.";
                        continue;
                    }
                    if ($sellingPrice < 0) {
                        $errors[] = "Row $rowIndex: Selling price for '$itemName' must be non-negative.";
                        continue;
                    }
                    if ($unitCost < 0) {
                        $errors[] = "Row $rowIndex: Unit cost for '$itemName' must be non-negative.";
                        continue;
                    }
                    if ($remainingItems < 0) {
                        $errors[] = "Row $rowIndex: Remaining items for '$itemName' must be non-negative.";
                        continue;
                    }
                    if ($remainingItems > 1000) {
                        $errors[] = "Row $rowIndex: Remaining items for '$itemName' ($remainingItems) exceeds limit (1000). Skipped.";
                        continue;
                    }

                    if (!empty($expirationDateStr)) {
                        try {
                            if (is_numeric($expirationDateStr)) {
                                $unixTimestamp = ($expirationDateStr - 25569) * 86400;
                                $dt = new DateTime("@$unixTimestamp");
                                $expirationDate = $dt->format('Y-m-d');
                            } else {
                                $dt = DateTime::createFromFormat('m/d/Y', $expirationDateStr) ?: new DateTime($expirationDateStr);
                                $expirationDate = $dt->format('Y-m-d');
                            }
                        } catch (Exception $e) {
                            $errors[] = "Row $rowIndex: Invalid expiration date for '$itemName' ('$expirationDateStr'). Using NULL.";
                            $expirationDate = null;
                        }
                    }

                    $itemsToProcess[] = [
                        'name' => $itemName,
                        'selling_price' => $sellingPrice,
                        'unit_cost' => $unitCost,
                        'remaining' => $remainingItems,
                        'expiration' => $expirationDate,
                        'row' => $rowIndex
                    ];
                }

                $pdo->beginTransaction();
                try {
                    foreach ($itemsToProcess as $item) {
                        $itemName = $item['name'];
                        $sellingPrice = $item['selling_price'];
                        $unitCost = $item['unit_cost'];
                        $remainingItems = $item['remaining'];
                        $expirationDate = $item['expiration'];
                        $rowIndex = $item['row'];

                        $stmt = $pdo->prepare("SELECT id FROM inventory WHERE name = ?");
                        $stmt->execute([$itemName]);
                        $existing = $stmt->fetch();

                        if ($existing) {
                            $updateStmt = $pdo->prepare("UPDATE inventory SET selling_price = ?, unit_cost = ?, remaining_items = ?, expiration_date = ? WHERE id = ?");
                            $updateStmt->execute([$sellingPrice, $unitCost, $remainingItems, $expirationDate, $existing['id']]);
                            $processedCount++;
                        } else {
                            $insertStmt = $pdo->prepare("INSERT INTO inventory (name, selling_price, unit_cost, quantity, remaining_items, expiration_date) VALUES (?, ?, ?, 0, ?, ?)");
                            $insertStmt->execute([$itemName, $sellingPrice, $unitCost, $remainingItems, $expirationDate]);
                            $processedCount++;
                        }
                    }
                    $pdo->commit();
                    $_SESSION['message'] = "Excel file processed. $processedCount items added/updated.";
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $_SESSION['error'] = "Database error during import: " . $e->getMessage();
                    $errors[] = "Import failed due to database error.";
                }

                if (!empty($errors)) {
                    $_SESSION['error'] = (isset($_SESSION['error']) ? $_SESSION['error'] . "<br>" : "") . "Import issues:<br>" . implode("<br>", $errors);
                }
            } catch (Exception $e) {
                $_SESSION['error'] = "Error importing Excel file: " . $e->getMessage();
            } finally {
                if (isset($destPath) && file_exists($destPath)) {
                    unlink($destPath);
                }
            }
        } else {
            $_SESSION['error'] = "Error moving uploaded file.";
        }
    } else {
        $_SESSION['error'] = "Error uploading file. Code: " . $_FILES['excel-file']['error'];
    }
    header("Location: admin_inventory.php");
    exit();
}

// Handle form submission for dispensing quantity
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dispense'])) {
    $itemName = trim($_POST['item_name']);
    $dispenseQuantity = (int) $_POST['dispense_quantity'];

    if (empty($itemName) || $dispenseQuantity <= 0) {
        $_SESSION['error'] = 'Invalid input for dispensing.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT remaining_items, expiration_date, quantity FROM inventory WHERE name = ?");
            $stmt->execute([$itemName]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($item) {
                $currentRemaining = $item['remaining_items'];
                $currentQuantity = $item['quantity'];
                if ($dispenseQuantity > $currentRemaining) {
                    $_SESSION['error'] = "Cannot dispense more than remaining items ($currentRemaining).";
                } else {
                    $newRemaining = $currentRemaining - $dispenseQuantity;
                    $newQuantity = $currentQuantity + $dispenseQuantity;
                    $updateStmt = $pdo->prepare("UPDATE inventory SET remaining_items = ?, quantity = ? WHERE name = ?");
                    $updateStmt->execute([$newRemaining, $newQuantity, $itemName]);
                    $_SESSION['message'] = "Dispensed $dispenseQuantity items from '$itemName'. Remaining: $newRemaining, Total Dispensed: $newQuantity";
                }
            } else {
                $_SESSION['error'] = 'Item not found.';
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Database error: " . $e->getMessage();
        }
    }
    header("Location: admin_inventory.php");
    exit();
}

// Handle AJAX request for getting inventory item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['get_item']) && isset($_POST['item_name'])) {
    $itemName = trim($_POST['item_name']);
    $stmt = $pdo->prepare("SELECT remaining_items, expiration_date FROM inventory WHERE name = ?");
    $stmt->execute([$itemName]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($item) {
        echo json_encode([
            'success' => true,
            'remaining_items' => $item['remaining_items'],
            'expiration_date' => $item['expiration_date']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Item not found']);
    }
    exit();
}

// Pagination logic
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$itemsPerPage = 5;
$offset = ($page - 1) * $itemsPerPage;
$searchTerm = "%" . (isset($_GET['search']) ? $_GET['search'] : "") . "%";

$stmt = $pdo->prepare("SELECT id, name, selling_price, unit_cost, quantity, remaining_items, expiration_date FROM inventory WHERE name LIKE ? ORDER BY name ASC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $searchTerm, PDO::PARAM_STR);
$stmt->bindValue(2, $itemsPerPage, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM inventory WHERE name LIKE ?");
$totalStmt->execute([$searchTerm]);
$totalCount = $totalStmt->fetchColumn();
$totalPages = ceil($totalCount / $itemsPerPage);

$stmt = $pdo->query("SELECT name, selling_price, unit_cost, quantity, remaining_items, expiration_date FROM inventory ORDER BY name ASC");
$inventoryForPdf = $stmt->fetchAll(PDO::FETCH_NUM);

// Fetch distinct item names for dropdown
$stmt = $pdo->query("SELECT DISTINCT name FROM inventory ORDER BY name ASC");
$allItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Function to determine expiration status and color
function getExpirationStatus($expirationDateStr)
{
    if (empty($expirationDateStr)) {
        return ['text' => 'N/A', 'color' => 'inherit'];
    }
    try {
        $now = new DateTime('now', new DateTimeZone('UTC'));
        $now->setTime(0, 0, 0);
        $expirationDate = new DateTime($expirationDateStr, new DateTimeZone('UTC'));
        $expirationDate->setTime(0, 0, 0);

        // Calculate dates for comparison
        $threeMonthsFromNow = clone $now;
        $threeMonthsFromNow->modify('+3 months');
        $sixMonthsFromNow = clone $now;
        $sixMonthsFromNow->modify('+6 months');

        $formattedDate = $expirationDate->format('F j, Y');

        if ($expirationDate < $now) {
            return ['text' => $formattedDate, 'color' => 'red']; // Expired
        } elseif ($expirationDate <= $threeMonthsFromNow) {
            return ['text' => $formattedDate, 'color' => 'red']; // Less than 3 months
        } elseif ($expirationDate <= $sixMonthsFromNow) {
            return ['text' => $formattedDate, 'color' => 'blue']; // Between 3-6 months
        } else {
            return ['text' => $formattedDate, 'color' => 'inherit']; // More than 6 months
        }
    } catch (Exception $e) {
        return ['text' => 'Invalid Date', 'color' => 'orange'];
    }
}

function generatePagination($page, $totalPages, $searchParam)
{
    $pagination = '';
    if ($page > 1) {
        $pagination .= '<a href="admin_inventory.php?' . $searchParam . 'page=' . ($page - 1) . '" class="btn previous">PREVIOUS</a>';
    }
    if ($totalPages <= 5) {
        for ($i = 1; $i <= $totalPages; $i++) {
            $pagination .= '<a href="admin_inventory.php?' . $searchParam . 'page=' . $i . '" class="btn' . ($i == $page ? ' active' : '') . '">' . $i . '</a>';
        }
    } else {
        $pagination .= '<a href="admin_inventory.php?' . $searchParam . 'page=1" class="btn' . (1 == $page ? ' active' : '') . '">1</a>';
        if ($page > 3) {
            $pagination .= '<span class="ellipsis">...</span>';
        }
        $start = max(2, $page - 2);
        $end = min($totalPages - 1, $page + 2);
        for ($i = $start; $i <= $end; $i++) {
            $pagination .= '<a href="admin_inventory.php?' . $searchParam . 'page=' . $i . '" class="btn' . ($i == $page ? ' active' : '') . '">' . $i . '</a>';
        }
        if ($page < $totalPages - 2) {
            $pagination .= '<span class="ellipsis">...</span>';
        }
        $pagination .= '<a href="admin_inventory.php?' . $searchParam . 'page=' . $totalPages . '" class="btn' . ($totalPages == $page ? ' active' : '') . '">' . $totalPages . '</a>';
    }
    if ($page < $totalPages) {
        $pagination .= '<a href="admin_inventory.php?' . $searchParam . 'page=' . ($page + 1) . '" class="btn next">NEXT</a>';
    }
    return $pagination;
}

$searchParam = isset($_GET['search']) && $_GET['search'] !== '' ? 'search=' . urlencode($_GET['search']) . '&' : '';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin: Inventory Management</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="images/sti_logo.png" type="image/png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.24/jspdf.plugin.autotable.min.js"></script>
    <style>
        .forms-wrapper {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: space-between;
            margin-top: 20px;
        }

        .inventory-form {
            flex: 1;
            min-width: 300px;
            max-width: 48%;
            box-sizing: border-box;
        }

        @media (max-width: 768px) {
            .inventory-form {
                max-width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <h2>Drugstore Inventory</h2>
            <ul class="menu">
                <li><a href="admin_inventory.php" class="active">Inventory</a></li>
                <li><a href="logout.php" class="logout">Logout</a></li>
            </ul>
            <div class="theme-switch-wrapper">
                <label class="theme-switch" for="checkbox">
                    <input type="checkbox" id="checkbox" />
                    <div class="slider round"></div>
                </label>
                <em>Toggle Mode</em>
            </div>
        </div>
        <div class="main-content">
            <h1>Admin: Inventory Management</h1>
            <?php if (isset($_SESSION['message'])): ?>
                <div class="success-message">
                    <?= htmlspecialchars($_SESSION['message']) ?>
                </div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="error-message">
                    <?= nl2br(htmlspecialchars($_SESSION['error'])) ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            <div class="header-actions">
                <div class="search-bar">
                    <form method="GET" action="admin_inventory.php">
                        <input type="text" name="search" placeholder="Search item..."
                            value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                        <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
                            <a href="admin_inventory.php">Reset Search</a>
                        <?php endif; ?>
                    </form>
                </div>
                <div class="table-actions">
                    <form id="clear-table-form" method="POST" action="admin_inventory.php" style="display: inline;">
                        <input type="hidden" name="clear" value="1">
                        <button type="submit" class="clear-btn">Clear Inventory</button>
                    </form>
                    <button id="download-inventory-excel" class="download-excel-btn">Download Excel</button>
                    <button id="download-inventory-pdf" class="download-pdf-btn">Download PDF</button>
                </div>
            </div>
            <table id="table-inventory">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Selling Price</th>
                        <th>Unit Cost</th>
                        <th>Quantity</th>
                        <th>Remaining Items</th>
                        <th>Expiration</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($inventory)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 20px;">No items available.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($inventory as $item): ?>
                            <?php $status = getExpirationStatus($item['expiration_date']); ?>
                            <tr>
                                <td><?= htmlspecialchars($item['name']) ?></td>
                                <td><?= number_format($item['selling_price'], 2) ?></td>
                                <td><?= number_format($item['unit_cost'], 2) ?></td>
                                <td><?= $item['quantity'] ?></td>
                                <td><?= $item['remaining_items'] ?></td>
                                <td class="expiration-date" style="color: <?= $status['color'] ?>;">
                                    <?= htmlspecialchars($status['text']) ?>
                                </td>
                                <td>
                                    <button class="action-btn edit-btn"
                                        onclick="populateEditForm(<?= htmlspecialchars(json_encode($item), ENT_QUOTES, 'UTF-8') ?>)">Edit</button>
                                    <form class="remove-form" method="POST" action="admin_inventory.php"
                                        style="display: inline;">
                                        <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="action-btn remove-btn">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php if ($totalCount > 0 && $totalPages > 1): ?>
                <div class="pagination">
                    <?= generatePagination($page, $totalPages, $searchParam) ?>
                </div>
            <?php endif; ?>
            <div class="forms-wrapper">
                <div class="inventory-form">
                    <h3 id="form-title">Add Inventory Item</h3>
                    <form id="add-item-form" action="admin_inventory.php" method="POST"
                        onsubmit="return validateAddItemForm()">
                        <input type="hidden" id="item-id" name="item_id" value="">
                        <div class="form-group">
                            <label for="item-name">Item Name</label>
                            <input type="text" id="item-name" name="item-name" required>
                        </div>
                        <div class="form-group">
                            <label for="selling-price">Selling Price</label>
                            <input type="number" id="selling-price" name="selling-price" step="0.01" min="0" required>
                        </div>
                        <div class="form-group">
                            <label for="unit-cost">Unit Cost</label>
                            <input type="number" id="unit-cost" name="unit-cost" step="0.01" min="0" required>
                        </div>
                        <div class="form-group">
                            <label for="remaining-items">Remaining Items</label>
                            <input type="number" id="remaining-items" name="remaining-items" min="0" required>
                        </div>
                        <div class="form-group">
                            <label>Expiration Date</label>
                            <div class="date-dropdowns">
                                <select id="exp-month" name="exp-month" required>
                                    <option value="">Month</option>
                                    <option value="1">January</option>
                                    <option value="2">February</option>
                                    <option value="3">March</option>
                                    <option value="4">April</option>
                                    <option value="5">May</option>
                                    <option value="6">June</option>
                                    <option value="7">July</option>
                                    <option value="8">August</option>
                                    <option value="9">September</option>
                                    <option value="10">October</option>
                                    <option value="11">November</option>
                                    <option value="12">December</option>
                                </select>
                                <select id="exp-day" name="exp-day" required>
                                    <option value="">Day</option>
                                    <?php for ($d = 1; $d <= 31; $d++): ?>
                                        <option value="<?= $d ?>"><?= str_pad($d, 2, '0', STR_PAD_LEFT) ?></option>
                                    <?php endfor; ?>
                                </select>
                                <select id="exp-year" name="exp-year" required>
                                    <option value="">Year</option>
                                    <?php for ($y = 2010; $y <= date('Y') + 10; $y++): ?>
                                        <option value="<?= $y ?>"><?= $y ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        <button type="submit" id="form-submit-btn" name="add_item" class="btn btn-primary w-100">Add
                            Item</button>
                        <button type="button" id="cancel-edit-btn" class="btn-secondary"
                            style="display: none; margin-top: 5px;" onclick="resetForm()">Cancel Edit</button>
                    </form>
                </div>
                <div class="inventory-form">
                    <h3>Get Inventory Item</h3>
                    <form id="get-item-form" method="POST" action="admin_inventory.php">
                        <div class="form-group">
                            <label for="get-item-name">Item Name</label>
                            <select id="get-item-name" name="item_name">
                                <option value="">Select an item</option>
                                <?php foreach ($allItems as $item): ?>
                                    <option value="<?= htmlspecialchars($item['name']) ?>">
                                        <?= htmlspecialchars($item['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="get-remaining">Remaining Items</label>
                            <input type="number" id="get-remaining" name="remaining_items" readonly>
                        </div>
                        <div class="form-group">
                            <label for="get-expiration">Expiration Date</label>
                            <input type="text" id="get-expiration" name="expiration_date" readonly>
                        </div>
                        <div class="form-group">
                            <label for="dispense-quantity">Quantity to Dispense</label>
                            <input type="number" id="dispense-quantity" name="dispense_quantity" min="0">
                        </div>
                        <button type="submit" name="dispense" class="btn btn-primary w-100">Dispense Quantity</button>
                    </form>
                </div>
            </div>
            <div class="excel-import">
                <h3>Import from Excel</h3>
                <form method="POST" enctype="multipart/form-data" action="admin_inventory.php">
                    <label for="excel-file">Choose File (.xlsx)</label>
                    <input type="file" name="excel-file" id="excel-file" accept=".xlsx" required>
                    <button type="submit" class="btn btn-primary w-100">Upload Excel</button>
                </form>
            </div>
        </div>
    </div>
    <script>
        const themeToggle = document.getElementById('checkbox');
        const currentTheme = localStorage.getItem('theme');
        if (currentTheme === 'dark') {
            document.body.classList.add('dark-mode');
            themeToggle.checked = true;
        }
        themeToggle.addEventListener('change', function () {
            document.body.classList.toggle('dark-mode', this.checked);
            localStorage.setItem('theme', this.checked ? 'dark' : 'light');
        });

        const clearForm = document.getElementById('clear-table-form');
        if (clearForm) {
            clearForm.addEventListener('submit', function (event) {
                if (!confirm('Are you sure you want to clear the ENTIRE inventory? This action cannot be undone.')) {
                    event.preventDefault();
                }
            });
        }

        const removeForms = document.querySelectorAll('.remove-form');
        removeForms.forEach(form => {
            form.addEventListener('submit', function (event) {
                const row = form.closest('tr');
                const itemName = row ? row.cells[0].textContent : 'this item';
                if (!confirm(`Are you sure you want to remove ${itemName}?`)) {
                    event.preventDefault();
                }
            });
        });

        function validateAddItemForm() {
            const itemName = document.getElementById('item-name').value.trim();
            const sellingPrice = parseFloat(document.getElementById('selling-price').value);
            const unitCost = parseFloat(document.getElementById('unit-cost').value);
            const remainingItems = parseInt(document.getElementById('remaining-items').value);
            const expMonth = document.getElementById('exp-month').value;
            const expDay = document.getElementById('exp-day').value;
            const expYear = document.getElementById('exp-year').value;

            if (itemName === '') {
                alert('Item name cannot be empty.');
                return false;
            }
            if (isNaN(sellingPrice) || sellingPrice < 0) {
                alert('Selling price must be non-negative.');
                return false;
            }
            if (isNaN(unitCost) || unitCost < 0) {
                alert('Unit cost must be non-negative.');
                return false;
            }
            if (isNaN(remainingItems) || remainingItems < 0 || !Number.isInteger(remainingItems)) {
                alert('Remaining items must be a whole number (0 or greater).');
                return false;
            }
            if (remainingItems > 1000) {
                alert('Remaining items cannot exceed 1000.');
                return false;
            }
            if (!expMonth || !expDay || !expYear) {
                alert('Expiration date must be complete.');
                return false;
            }

            const month = parseInt(expMonth);
            const day = parseInt(expDay);
            const year = parseInt(expYear);
            const date = new Date(year, month - 1, day);
            if (date.getFullYear() !== year || date.getMonth() + 1 !== month || date.getDate() !== day) {
                alert('Invalid expiration date.');
                return false;
            }

            return true;
        }

        const logoutLink = document.querySelector('.logout');
        if (logoutLink) {
            logoutLink.addEventListener('click', function (event) {
                event.preventDefault();
                if (confirm('Are you sure you want to logout?')) {
                    window.location.href = this.href;
                }
            });
        }

        function populateEditForm(item) {
            document.getElementById('form-title').textContent = 'Edit Inventory Item';
            document.getElementById('item-id').value = item.id;
            document.getElementById('item-name').value = item.name;
            document.getElementById('selling-price').value = item.selling_price;
            document.getElementById('unit-cost').value = item.unit_cost;
            document.getElementById('remaining-items').value = item.remaining_items;

            const expMonth = document.getElementById('exp-month');
            const expDay = document.getElementById('exp-day');
            const expYear = document.getElementById('exp-year');
            if (item.expiration_date) {
                const date = new Date(item.expiration_date);
                expMonth.value = date.getMonth() + 1;
                expDay.value = date.getDate();
                expYear.value = date.getFullYear();
            } else {
                expMonth.value = '';
                expDay.value = '';
                expYear.value = '';
            }

            document.getElementById('form-submit-btn').textContent = 'Update Item';
            document.getElementById('cancel-edit-btn').style.display = 'block';
            document.getElementById('add-item-form').scrollIntoView({
                behavior: 'smooth'
            });
        }

        function resetForm() {
            document.getElementById('form-title').textContent = 'Add Inventory Item';
            document.getElementById('add-item-form').reset();
            document.getElementById('item-id').value = '';
            document.getElementById('form-submit-btn').textContent = 'Add Item';
            document.getElementById('cancel-edit-btn').style.display = 'none';
            document.getElementById('exp-month').value = '';
            document.getElementById('exp-day').value = '';
            document.getElementById('exp-year').value = '';
        }

        function getExpirationStatus(dateStr) {
            if (!dateStr) return {
                text: 'N/A',
                color: 'inherit'
            };
            try {
                const now = new Date();
                now.setHours(0, 0, 0, 0);
                const expiration = new Date(dateStr);
                expiration.setHours(0, 0, 0, 0);
                const oneMonthFromNow = new Date(now);
                oneMonthFromNow.setMonth(now.getMonth() + 1);
                oneMonthFromNow.setHours(0, 0, 0, 0);

                const formattedDate = expiration.toLocaleDateString('en-US', {
                    month: 'long',
                    day: 'numeric',
                    year: 'numeric'
                });

                if (expiration < now) {
                    return {
                        text: formattedDate,
                        color: 'red'
                    };
                } else if (expiration <= oneMonthFromNow) {
                    return {
                        text: formattedDate,
                        color: 'blue'
                    };
                } else {
                    return {
                        text: formattedDate,
                        color: 'inherit'
                    };
                }
            } catch (e) {
                return {
                    text: 'Invalid Date',
                    color: 'orange'
                };
            }
        }

        document.getElementById('download-inventory-excel').addEventListener('click', function (e) {
            e.preventDefault();
            window.location.href = 'export_inventory.php';
        });

        document.getElementById('download-inventory-pdf').addEventListener('click', function (e) {
            e.preventDefault();
            const {
                jsPDF
            } = window.jspdf;
            const pdf = new jsPDF('portrait', 'mm', 'letter');
            pdf.setProperties({
                title: 'Inventory List',
                subject: 'Clinic Inventory',
                author: 'STI Clinic System'
            });
            pdf.setFontSize(14);
            pdf.setFont("helvetica", "bold");
            pdf.text("STI Clinic Management System - Inventory List", pdf.internal.pageSize.getWidth() / 2, 15, {
                align: 'center'
            });
            pdf.setFontSize(10);
            pdf.setFont("helvetica", "normal");
            const inventoryData = <?php echo json_encode($inventoryForPdf); ?>.map(item => {
                const status = getExpirationStatus(item[5]);
                return [
                    item[0],
                    item[1],
                    item[2],
                    item[3],
                    item[4],
                    status.text
                ];
            });
            pdf.autoTable({
                startY: 25,
                head: [
                    ['Name', 'Selling Price', 'Unit Cost', 'Quantity', 'Remaining Items', 'Expiration Date']
                ],
                body: inventoryData,
                theme: 'grid',
                styles: {
                    font: 'helvetica',
                    fontSize: 9,
                    cellPadding: 2,
                    overflow: 'linebreak'
                },
                headStyles: {
                    fillColor: [1, 35, 101],
                    textColor: 255,
                    fontStyle: 'bold',
                    fontSize: 9
                },
                columnStyles: {
                    0: {
                        cellWidth: 'auto'
                    },
                    1: {
                        cellWidth: 25
                    },
                    2: {
                        cellWidth: 25
                    },
                    3: {
                        cellWidth: 25
                    },
                    4: {
                        cellWidth: 25
                    },
                    5: {
                        cellWidth: 35
                    }
                },
                didDrawPage: function (data) {
                    const pageCount = pdf.internal.getNumberOfPages();
                    pdf.setFontSize(8);
                    pdf.text(`Page ${data.pageNumber} of ${pageCount} - STI Clinic Management System`, pdf.internal.pageSize.getWidth() / 2, pdf.internal.pageSize.getHeight() - 10, {
                        align: 'center'
                    });
                },
                margin: {
                    top: 10,
                    bottom: 20,
                    left: 10,
                    right: 10
                }
            });
            pdf.save(`inventory-${new Date().toISOString().slice(0, 10)}.pdf`);
        });

        document.getElementById('get-item-name').addEventListener('change', function () {
            const itemName = this.value;
            if (itemName) {
                fetch('admin_inventory.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `get_item=true&item_name=${encodeURIComponent(itemName)}`
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('get-remaining').value = data.remaining_items;
                            document.getElementById('get-expiration').value = data.expiration_date ?
                                new Date(data.expiration_date).toLocaleDateString('en-US', {
                                    month: 'long',
                                    day: 'numeric',
                                    year: 'numeric'
                                }) : 'N/A';
                        } else {
                            document.getElementById('get-remaining').value = '';
                            document.getElementById('get-expiration').value = '';
                            alert(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Failed to fetch item details.');
                    });
            } else {
                document.getElementById('get-remaining').value = '';
                document.getElementById('get-expiration').value = '';
            }
        });
    </script>
</body>

</html>