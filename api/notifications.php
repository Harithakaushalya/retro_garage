<?php
/**
 * ==============================================================
 * AJAX API: Notifications Controller (api/notifications.php)
 * ==============================================================
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = get_db_connection();
$user = current_user();
$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

try {
    switch ($action) {
        case 'mark_read':
            if ($user) {
                $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? OR (user_id IS NULL AND vehicle_number IS NULL)");
                $stmt->execute([$user['id']]);
            }
            json_response(['success' => true, 'message' => 'Notifications marked as read.']);
            break;

        case 'list':
        default:
            if ($user) {
                $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? OR (user_id IS NULL AND vehicle_number IS NULL) ORDER BY created_at DESC LIMIT 10");
                $stmt->execute([$user['id']]);
            } else {
                $stmt = $pdo->query("SELECT * FROM notifications WHERE user_id IS NULL ORDER BY created_at DESC LIMIT 10");
            }
            $items = $stmt->fetchAll();

            $formatted = array_map(function($n) {
                return [
                    'id'             => (int)$n['id'],
                    'title'          => $n['title'],
                    'message'        => $n['message'],
                    'vehicle_number' => $n['vehicle_number'],
                    'is_read'        => (bool)$n['is_read'],
                    'created_at'     => format_date($n['created_at'])
                ];
            }, $items);

            json_response([
                'success'       => true,
                'count'         => count($formatted),
                'notifications' => $formatted
            ]);
            break;
    }
} catch (Exception $e) {
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
