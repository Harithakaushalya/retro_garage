<?php
/**
 * ==============================================================
 * AJAX API: Parts Filter & Search (api/parts.php)
 * ==============================================================
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = get_db_connection();

$brand = trim($_GET['brand'] ?? '');
$search = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');
$sort = trim($_GET['sort'] ?? 'newest');

try {
    $sql = "SELECT p.*, b.name AS brand_name, b.slug AS brand_slug 
            FROM parts p 
            JOIN brands b ON p.brand_id = b.id 
            WHERE 1=1";
    $params = [];

    if (!empty($brand) && $brand !== 'all') {
        $sql .= " AND (b.slug = ? OR b.name = ?)";
        $params[] = $brand;
        $params[] = $brand;
    }

    if (!empty($category) && $category !== 'all') {
        $sql .= " AND p.category = ?";
        $params[] = $category;
    }

    if (!empty($search)) {
        $sql .= " AND (p.name LIKE ? OR p.sku LIKE ? OR p.description LIKE ?)";
        $wildcard = '%' . $search . '%';
        $params[] = $wildcard;
        $params[] = $wildcard;
        $params[] = $wildcard;
    }

    $sql .= match ($sort) {
        'price_asc'  => " ORDER BY p.part_price ASC",
        'price_desc' => " ORDER BY p.part_price DESC",
        'name_asc'   => " ORDER BY p.name ASC",
        default      => " ORDER BY p.id DESC"
    };

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $parts = $stmt->fetchAll();

    $formatted = array_map(function($p) {
        return [
            'id'              => (int)$p['id'],
            'name'            => $p['name'],
            'sku'             => $p['sku'],
            'brand_name'      => $p['brand_name'],
            'brand_slug'      => $p['brand_slug'],
            'category'        => $p['category'],
            'description'     => $p['description'],
            'stock_qty'       => (int)$p['stock_qty'],
            'part_price'      => (float)$p['part_price'],
            'install_fee'     => (float)$p['install_fee'],
            'formatted_price' => format_price($p['part_price']),
            'formatted_fee'   => format_price($p['install_fee']),
            'image_path'      => $p['image_path'] ?: 'assets/images/placeholder-part.svg',
            'stock_status'    => (int)$p['stock_qty'] <= 0 ? 'Out of Stock' : ((int)$p['stock_qty'] <= 8 ? 'Low Stock' : 'In Stock'),
            'stock_class'     => (int)$p['stock_qty'] <= 0 ? 'badge-stock-out' : ((int)$p['stock_qty'] <= 8 ? 'badge-stock-low' : 'badge-stock-in')
        ];
    }, $parts);

    json_response([
        'success' => true,
        'count'   => count($formatted),
        'parts'   => $formatted
    ]);
} catch (Exception $e) {
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
