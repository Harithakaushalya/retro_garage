<?php
/**
 * ==============================================================
 * AJAX API: Vehicle Job Tracker (api/track.php)
 * ==============================================================
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = get_db_connection();
$query = trim($_GET['query'] ?? '');

if (empty($query)) {
    json_response(['success' => false, 'message' => 'Please provide a vehicle registration number or ticket code.'], 400);
}

try {
    $cleanQuery = clean_vehicle_no($query);
    $stmt = $pdo->prepare("
        SELECT * FROM bookings 
        WHERE ticket_code = ? OR vehicle_number = ? 
        ORDER BY id DESC 
        LIMIT 1
    ");
    $stmt->execute([$query, $cleanQuery]);
    $booking = $stmt->fetch();

    if (!$booking) {
        json_response(['success' => false, 'message' => 'No service record found matching "' . e($query) . '".'], 404);
    }

    // Fetch related notifications
    $notifStmt = $pdo->prepare("
        SELECT * FROM notifications 
        WHERE booking_id = ? OR vehicle_number = ? 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $notifStmt->execute([$booking['id'], $booking['vehicle_number']]);
    $notifs = $notifStmt->fetchAll();

    json_response([
        'success'        => true,
        'booking'        => [
            'id'             => (int)$booking['id'],
            'ticket_code'    => $booking['ticket_code'],
            'customer_name'  => $booking['customer_name'],
            'vehicle_number' => $booking['vehicle_number'],
            'status'         => $booking['status'],
            'service_note'   => $booking['service_note'],
            'mechanic_notes' => $booking['mechanic_notes'],
            'created_at'     => format_date($booking['created_at']),
            'updated_at'     => format_date($booking['updated_at'])
        ],
        'notifications'  => array_map(function($n) {
            return [
                'id'         => (int)$n['id'],
                'title'      => $n['title'],
                'message'    => $n['message'],
                'created_at' => format_date($n['created_at'])
            ];
        }, $notifs)
    ]);
} catch (Exception $e) {
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
