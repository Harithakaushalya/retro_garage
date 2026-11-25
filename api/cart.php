<?php
/**
 * ==============================================================
 * AJAX API: Cart Controller (api/cart.php)
 * ==============================================================
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = get_db_connection();
$action = $_POST['action'] ?? $_GET['action'] ?? 'get';

try {
    switch ($action) {
        case 'add':
            $partId = (int)($_POST['part_id'] ?? 0);
            $qty = max(1, (int)($_POST['quantity'] ?? 1));
            $includeInstall = !empty($_POST['include_install']) && ($_POST['include_install'] === '1' || $_POST['include_install'] === 'true');

            if ($partId <= 0) {
                json_response(['success' => false, 'message' => 'Invalid part selected.'], 400);
            }

            // Check part exists and has stock
            $stmt = $pdo->prepare("SELECT id, name, stock_qty, part_price, install_fee FROM parts WHERE id = ?");
            $stmt->execute([$partId]);
            $part = $stmt->fetch();

            if (!$part) {
                json_response(['success' => false, 'message' => 'Part not found in inventory.'], 404);
            }

            if ((int)$part['stock_qty'] <= 0) {
                json_response(['success' => false, 'message' => 'This part is currently out of stock.'], 400);
            }

            add_to_cart($partId, $qty, $includeInstall);
            $cartDetails = get_cart_details($pdo);

            json_response([
                'success'         => true,
                'message'         => 'Part added to cart successfully.',
                'part_name'       => $part['name'],
                'cart_count'      => $cartDetails['item_count'],
                'total_parts'     => $cartDetails['total_parts'],
                'total_install'   => $cartDetails['total_install'],
                'grand_total'     => $cartDetails['grand_total'],
                'currency'        => CURRENCY_SYMBOL
            ]);
            break;

        case 'update':
            $partId = (int)($_POST['part_id'] ?? 0);
            $qty = (int)($_POST['quantity'] ?? 1);
            $includeInstall = isset($_POST['include_install'])
                ? ($_POST['include_install'] === '1' || $_POST['include_install'] === 'true')
                : null;

            if ($partId <= 0) {
                json_response(['success' => false, 'message' => 'Invalid part ID.'], 400);
            }

            update_cart_item($partId, $qty, $includeInstall);
            $cartDetails = get_cart_details($pdo);

            json_response([
                'success'       => true,
                'cart_count'    => $cartDetails['item_count'],
                'total_parts'   => $cartDetails['total_parts'],
                'total_install' => $cartDetails['total_install'],
                'grand_total'   => $cartDetails['grand_total'],
                'items'         => $cartDetails['items'],
                'currency'      => CURRENCY_SYMBOL
            ]);
            break;

        case 'remove':
            $partId = (int)($_POST['part_id'] ?? 0);
            remove_from_cart($partId);
            $cartDetails = get_cart_details($pdo);

            json_response([
                'success'       => true,
                'cart_count'    => $cartDetails['item_count'],
                'total_parts'   => $cartDetails['total_parts'],
                'total_install' => $cartDetails['total_install'],
                'grand_total'   => $cartDetails['grand_total'],
                'items'         => $cartDetails['items'],
                'currency'      => CURRENCY_SYMBOL
            ]);
            break;

        case 'get':
        default:
            $cartDetails = get_cart_details($pdo);
            json_response([
                'success'       => true,
                'cart_count'    => $cartDetails['item_count'],
                'total_parts'   => $cartDetails['total_parts'],
                'total_install' => $cartDetails['total_install'],
                'grand_total'   => $cartDetails['grand_total'],
                'items'         => $cartDetails['items'],
                'currency'      => CURRENCY_SYMBOL
            ]);
            break;
    }
} catch (Exception $e) {
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
