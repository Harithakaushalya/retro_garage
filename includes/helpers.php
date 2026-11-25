<?php
/**
 * ==============================================================
 * Global Helper Functions & Utilities
 * ==============================================================
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/db.php';

/**
 * Escape HTML output securely
 */
function e(?string $string): string {
    return htmlspecialchars((string)($string ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Format currency amount with symbol
 */
function format_price(float|int|string $amount): string {
    return CURRENCY_SYMBOL . ' ' . number_format((float)$amount, 2);
}

/**
 * Format readable dates
 */
function format_date(?string $datetime, string $format = 'd M Y, h:i A'): string {
    if (empty($datetime)) {
        return '—';
    }
    $ts = strtotime($datetime);
    return $ts ? date($format, $ts) : '—';
}

/**
 * Generate a retro-styled unique ticket code (e.g., GRG-92-0104)
 */
function generate_ticket_code(PDO $pdo): string {
    $prefix = 'GRG-92-';
    for ($i = 0; $i < 10; $i++) {
        $num = mt_rand(1000, 9999);
        $code = $prefix . $num;
        $stmt = $pdo->prepare("SELECT id FROM bookings WHERE ticket_code = ? LIMIT 1");
        $stmt->execute([$code]);
        if (!$stmt->fetch()) {
            return $code;
        }
    }
    return $prefix . time();
}

/**
 * Generate a retro-styled unique order code (e.g., ORD-92-5001)
 */
function generate_order_code(PDO $pdo): string {
    $prefix = 'ORD-92-';
    for ($i = 0; $i < 10; $i++) {
        $num = mt_rand(5000, 9999);
        $code = $prefix . $num;
        $stmt = $pdo->prepare("SELECT id FROM orders WHERE order_code = ? LIMIT 1");
        $stmt->execute([$code]);
        if (!$stmt->fetch()) {
            return $code;
        }
    }
    return $prefix . time();
}

/**
 * Set flash message
 */
function set_flash(string $type, string $message): void {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = [
        'type'    => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

/**
 * Retrieve and clear flash messages
 */
function get_flashes(): array {
    $flashes = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $flashes;
}

/**
 * Render flash message badges/alerts with retro design
 */
function render_flashes(): string {
    $flashes = get_flashes();
    if (empty($flashes)) {
        return '';
    }

    $html = '<div class="flash-messages-container">';
    foreach ($flashes as $flash) {
        $type = e($flash['type']);
        $msg = e($flash['message']);
        $html .= "<div class=\"retro-alert retro-alert-{$type}\">
                    <span class=\"alert-tag\">[SYSTEM NOTIFICATION]</span>
                    <span class=\"alert-body\">{$msg}</span>
                  </div>";
    }
    $html .= '</div>';
    return $html;
}

/**
 * Send JSON API response
 */
function json_response(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Insert a notification into the notifications table
 */
function create_notification(
    ?int $userId,
    ?int $bookingId,
    ?string $vehicleNumber,
    string $title,
    string $message
): bool {
    try {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, booking_id, vehicle_number, title, message, is_read, created_at) VALUES (?, ?, ?, ?, ?, 0, NOW())");
        return $stmt->execute([$userId ?: null, $bookingId ?: null, $vehicleNumber ?: null, $title, $message]);
    } catch (Exception $e) {
        error_log("Failed to create notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Build 1-click WhatsApp message link
 */
function build_whatsapp_url(?string $phone, string $message): string {
    if (empty($phone)) {
        return '#';
    }
    // Clean phone number: keep only digits
    $cleaned = preg_replace('/[^0-9]/', '', $phone);
    // If it starts with 0 and is Sri Lankan (9 digits after 0), convert 077 -> 9477
    if (str_starts_with($cleaned, '0') && strlen($cleaned) === 10) {
        $cleaned = '94' . substr($cleaned, 1);
    }
    return 'https://wa.me/' . $cleaned . '?text=' . urlencode($message);
}

/**
 * Clean and normalize vehicle registration number
 */
function clean_vehicle_no(?string $veh): string {
    return strtoupper(trim((string)$veh));
}

// --------------------------------------------------------------
// CART SESSION HELPERS
// --------------------------------------------------------------

/**
 * Get raw cart from session
 */
function get_cart(): array {
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    return $_SESSION['cart'];
}

/**
 * Add or update part in cart
 */
function add_to_cart(int $partId, int $quantity = 1, bool $includeInstall = false): void {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    $quantity = max(1, $quantity);

    if (isset($_SESSION['cart'][$partId])) {
        $_SESSION['cart'][$partId]['quantity'] += $quantity;
        $_SESSION['cart'][$partId]['include_install'] = $includeInstall;
    } else {
        $_SESSION['cart'][$partId] = [
            'part_id'         => $partId,
            'quantity'        => $quantity,
            'include_install' => $includeInstall
        ];
    }
}

/**
 * Update cart item quantity and installation preference
 */
function update_cart_item(int $partId, int $quantity, ?bool $includeInstall = null): void {
    if (isset($_SESSION['cart'][$partId])) {
        if ($quantity <= 0) {
            unset($_SESSION['cart'][$partId]);
        } else {
            $_SESSION['cart'][$partId]['quantity'] = $quantity;
            if ($includeInstall !== null) {
                $_SESSION['cart'][$partId]['include_install'] = $includeInstall;
            }
        }
    }
}

/**
 * Remove an item from the cart
 */
function remove_from_cart(int $partId): void {
    if (isset($_SESSION['cart'][$partId])) {
        unset($_SESSION['cart'][$partId]);
    }
}

/**
 * Empty the cart
 */
function clear_cart(): void {
    $_SESSION['cart'] = [];
}

/**
 * Calculate totals and load full part details from database for current cart
 */
function get_cart_details(PDO $pdo): array {
    $cart = get_cart();
    $items = [];
    $totalParts = 0.0;
    $totalInstall = 0.0;
    $totalCount = 0;

    if (!empty($cart)) {
        $partIds = array_keys($cart);
        $placeholders = implode(',', array_fill(0, count($partIds), '?'));
        
        $sql = "SELECT p.*, b.name AS brand_name 
                FROM parts p 
                JOIN brands b ON p.brand_id = b.id 
                WHERE p.id IN ($placeholders)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($partIds);
        $partsMap = [];
        while ($row = $stmt->fetch()) {
            $partsMap[$row['id']] = $row;
        }

        foreach ($cart as $pId => $item) {
            if (!isset($partsMap[$pId])) {
                continue;
            }
            $part = $partsMap[$pId];
            $qty = (int)$item['quantity'];
            $includeInstall = (bool)$item['include_install'];
            $unitPartPrice = (float)$part['part_price'];
            $unitInstallFee = (float)$part['install_fee'];

            $partsSubtotal = $unitPartPrice * $qty;
            $installSubtotal = $includeInstall ? ($unitInstallFee * $qty) : 0.0;
            $lineTotal = $partsSubtotal + $installSubtotal;

            $totalParts += $partsSubtotal;
            $totalInstall += $installSubtotal;
            $totalCount += $qty;

            $items[] = [
                'part_id'          => $part['id'],
                'name'             => $part['name'],
                'sku'              => $part['sku'],
                'brand_name'       => $part['brand_name'],
                'image_path'       => $part['image_path'] ?: 'assets/images/placeholder-part.svg',
                'stock_qty'        => (int)$part['stock_qty'],
                'unit_part_price'  => $unitPartPrice,
                'unit_install_fee' => $unitInstallFee,
                'quantity'         => $qty,
                'include_install'  => $includeInstall,
                'parts_subtotal'   => $partsSubtotal,
                'install_subtotal' => $installSubtotal,
                'line_total'       => $lineTotal
            ];
        }
    }

    return [
        'items'         => $items,
        'item_count'    => $totalCount,
        'total_parts'   => $totalParts,
        'total_install' => $totalInstall,
        'grand_total'   => $totalParts + $totalInstall
    ];
}

/**
 * Handle image upload safely with validation
 */
function handle_part_image_upload(array $file): ?string {
    if (empty($file['name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!in_array($mimeType, $allowedMimes, true)) {
        throw new InvalidArgumentException('Invalid file type. Allowed: JPG, PNG, WEBP, SVG.');
    }

    // Max 3MB
    if ($file['size'] > 3 * 1024 * 1024) {
        throw new InvalidArgumentException('File is too large. Maximum size is 3MB.');
    }

    $ext = match ($mimeType) {
        'image/jpeg'    => 'jpg',
        'image/png'     => 'png',
        'image/webp'    => 'webp',
        'image/svg+xml' => 'svg',
        default         => 'jpg'
    };

    $targetDir = BASE_PATH . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'parts';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $filename = 'part_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $destination = $targetDir . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Failed to save uploaded image.');
    }

    return 'uploads/parts/' . $filename;
}
