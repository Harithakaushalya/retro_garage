# 🏎️ SPEEDWELL RETRO GARAGE & AUTO PARTS SPEED SHOP
### *1980s–1990s Classic Retro Garage Management & Auto Parts E-Commerce Web Application*

A production-ready full-stack web application designed for vintage workshops, modern auto garages, and speed shops. Built with a distinctive **1980s–1990s Classic Retro Garage & Analog Dashboard aesthetic**, ready to present to prospective clients and deploy instantly to standard cPanel shared hosting or local XAMPP environments.

---

## 📸 Key Design & Architecture Highlights

- **Visual Design System:**
  - **Color Palette:** Deep Asphalt Charcoal (`#161618`), Oil-Slate (`#222226`), Aged Workshop Manual Cream (`#F3ECD8`), Classic Racing Red (`#D93829`), Analog Dashboard Amber (`#E8A317`), and Vintage Gauge Teal (`#2A9D8F`).
  - **Typography:** Display Condensed Headers (`Teko` / `Oswald`), Monospace (`IBM Plex Mono`), and Body Text (`Space Grotesk`).
  - **Retro Accents:** Crisp 2px solid borders, hard offset shadows (`box-shadow: 4px 4px 0px #000`), racing tricolor stripes, perforated workshop service tickets with tear cutouts, analog gauge cluster KPI dials, and rubber stamp watermarks.
- **Tech Stack:**
  - **Backend:** PHP 8+ with PDO (strictly prepared statements, XSS escaping, CSRF verification, password hashing with bcrypt, secure cookie sessions).
  - **Database:** MySQL 5.7+ / 8.0+ / MariaDB 10.3+ with relational foreign keys and indexes (`database.sql`).
  - **Frontend:** HTML5, Custom Modular CSS3, and Vanilla JavaScript (ES6+). Zero heavy third-party framework dependencies.
  - **Configuration:** Rebrandable in seconds via `config/config.php` with auto-detected `BASE_URL`.

---

## 🛠️ Default Demo Accounts & Credentials

| Role | Email | Password | Access Area |
| :--- | :--- | :--- | :--- |
| **Workshop Chief Officer (Admin)** | `admin@retrogarage.local` | `admin123` | [`/admin/login.php`](http://localhost/admin/login.php) |
| **Demo Customer Account** | `customer@demo.local` | `customer123` | [`/login.php`](http://localhost/login.php) or at Checkout |

---

## 🚀 Quick Setup Instructions (XAMPP / Localhost)

### 1. Place Project Files
Copy or clone the project folder into your web root:
- **XAMPP (Windows):** `C:\xampp\htdocs\retro_garage` (or directly inside `htdocs`)
- **cPanel / Apache (Linux):** `/home/username/public_html`

### 2. Import Database
1. Open **phpMyAdmin** in your browser: `http://localhost/phpmyadmin`
2. Create a new database named **`retro_garage`** with collation **`utf8mb4_unicode_ci`**.
3. Select the `retro_garage` database, go to the **Import** tab, choose `database.sql` from the project root, and click **Go**.
4. All tables, foreign key constraints, and seed data (brands, 12 auto parts with blueprint SVGs, bookings, sample orders, and notifications) will be imported automatically.

### 3. Configure Database Credentials (Optional)
Open `config/config.php`:
```php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'retro_garage');
define('DB_USER', 'root');
define('DB_PASS', ''); // Adjust if your MySQL has a password
```
> **Note:** `BASE_URL` is detected automatically whether the app is placed in the web server root or in subdirectories!

### 4. Launch Application
Visit in your web browser:
- **Customer Storefront:** `http://localhost/retro_garage/index.php`
- **Admin Command Center:** `http://localhost/retro_garage/admin/index.php`

---

## ⚙️ Core Functional Walkthrough

### 1. Express Guest Service Booking (No Login Required)
- Located prominently on the homepage (`index.php#book-service`).
- **Required fields ONLY:** `Customer Name` and `Vehicle Registration Number` (e.g. `WP CAB-4412`).
- Optional fields: Phone number, Preferred Service Date, Service Note/Issue.
- Upon submission, instantly generates a retro **Service Ticket ID** (e.g., `GRG-92-0104`), records an automated notification, and opens a perforated **Printable Workshop Job Ticket** (`booking-success.php`).

### 2. Dynamic Spare Parts Store & Interactive Installation Toggle
- Browse and filter auto parts dynamically by Brand (Honda, Toyota, Suzuki, Nissan, Yamaha) and live keyword search.
- Each part features:
  - High-resolution SVG blueprint artwork.
  - Live stock status badge (`In Stock`, `Low Stock`, `Out of Stock`).
  - **Part Price** (purchase part only).
  - **Garage Installation Fee** (labor cost for garage bay replacement).
- **Interactive Installation Switch:**
  - `Buy Part Only` $\rightarrow$ charges Part Price $\times$ Qty.
  - `+ Garage Installation` $\rightarrow$ automatically calculates and itemizes the labor fee in real time.

### 3. Conditional Customer Profile (Checkout Gate)
- Customers can freely browse, filter, and add items to their workshop cart without logging in.
- When clicking **"Proceed to Payment"**, if the customer is not logged in, the system presents a sleek retro **Quick Profile & Checkout Gate**:
  - **Create Profile:** Enters Name, Email, Phone, and Password once in 10 seconds.
  - **Sign In:** Existing customers log in immediately.
- **Payment Simulation:**
  - Card Simulation (Demo prefilled card)
  - Pay at Garage Counter / Bay Appointment
  - Direct Bank Transfer (Slip / Account notes)
- On confirmation, the system:
  1. Atomically deducts purchased quantities from `parts.stock_qty`.
  2. Generates an official Order Code (e.g. `ORD-92-5002`).
  3. Records itemized part + labor fees in `order_items`.
  4. Generates an automated customer alert in `notifications`.
  5. Displays a perforated retro **Printable Invoice** (`order-success.php`).

### 4. Customer Notification System
- **Logged-in Customers:** Live notification bell in the navigation bar with an unread badge counter and real-time dropdown alert list.
- **Guest Booking Customers:** Prominent **"Track My Vehicle / Check Alerts"** search bar (`track.php`) where entering a Vehicle Number or Ticket Code displays:
  - 5-stage analog progress timeline: `Pending` $\rightarrow$ `Diagnosing` $\rightarrow$ `In Progress` $\rightarrow$ `Ready for Pickup` $\rightarrow$ `Completed`.
  - Mechanic Telemetry & Diagnostic CRT Terminal notes.
  - Chronological notification history for the vehicle.
- **Admin 1-Click WhatsApp Notify:** Next to every booking and order with a customer phone, the Admin panel displays a 1-click WhatsApp button that opens a pre-filled `https://wa.me/` alert with customer name, vehicle plate, and latest status update without requiring any paid SMS APIs.

### 5. Owner / Admin Command Center (`/admin`)
- **Analog Dashboard (`admin/index.php`):**
  - **Tachometer Gauge:** Today's Service Bay Intake.
  - **Boost Gauge:** Active Pending Jobs in queue.
  - **Fuel Gauge:** Low Stock Parts Alert.
  - **Odometer Meter:** Total Parts & Labor Revenue in LKR.
- **Brand Manager (`admin/brands.php`):** Add, edit, and delete vehicle brands dynamically.
- **Parts & Inventory Manager (`admin/parts.php`):** Add, edit, delete parts with brand selection, SKU, category, stock counts, part price, installation fee, and safe image uploads with fallback blueprints.
- **Bookings Manager (`admin/bookings.php`):** Update repair milestone status, enter mechanic diagnostic notes, and dispatch automated alerts.
- **Orders Manager (`admin/orders.php`):** Inspect customer purchases, view itemized installation flags, and update dispatch status.

---

## 📂 Directory Structure

```text
├── config/
│   └── config.php               # Central configuration (DB, currency, branding, paths)
├── includes/
│   ├── db.php                   # PDO database connection wrapper
│   ├── auth.php                 # Session authentication & role authorization
│   ├── csrf.php                 # CSRF token generation & validation
│   ├── helpers.php              # Formatting, ticket generators, image upload, cart helpers
│   ├── header.php               # Global retro navbar, notification bell, cart badge
│   └── footer.php               # Global footer & Quick Profile modal gate
├── assets/
│   ├── css/
│   │   ├── retro-main.css       # Main design system, colors, typography, buttons, grids
│   │   ├── dashboard.css        # Admin analog gauge clusters, data tables, toolbars
│   │   └── tickets.css          # Perforated tickets, invoice stubs, print stylesheets
│   ├── js/
│   │   ├── retro-app.js         # Mobile nav, notifications dropdown, tabs, toast alerts
│   │   ├── cart.js              # Live AJAX cart updates, installation toggles & recalculations
│   │   └── admin.js             # WhatsApp pre-filled link generator, status modals
│   └── images/
│       ├── logo.svg             # Speedwell 1988 retro garage emblem
│       └── placeholder-part.svg # Technical blueprint fallback diagram
├── uploads/
│   └── parts/                   # 12 seed blueprint SVGs & user-uploaded part images
├── api/
│   ├── cart.php                 # AJAX Cart API (add, update, remove, get)
│   ├── parts.php                # AJAX Parts Live Search & Filter API
│   ├── track.php                # AJAX Vehicle Tracking Telemetry API
│   └── notifications.php        # AJAX Notification List & Mark Read API
├── admin/
│   ├── index.php                # Admin Command Center Dashboard & Analog Gauges
│   ├── login.php                # Admin Security Gate
│   ├── logout.php               # Admin Logout
│   ├── brands.php               # Vehicle Brand CRUD Manager
│   ├── parts.php                # Parts Inventory & Image Upload Manager
│   ├── bookings.php             # Service Bookings, Mechanic Notes & WhatsApp Alerts
│   ├── orders.php               # Customer Orders, Installation Flags & Status Updates
│   └── includes/
│       ├── admin-header.php     # Admin Sidebar & Topbar
│       └── admin-footer.php     # Admin Footer Scripts
├── index.php                    # Homepage with Express Booking & Vehicle Tracker
├── parts.php                    # Full Spare Parts Store with dynamic filters
├── part-detail.php              # Part specs & live installation fee calculator
├── cart.php                     # Interactive Cart with itemized installation breakdown
├── checkout.php                 # Conditional Profile Gate, Payment Simulation & Stock Deduction
├── booking-success.php          # Perforated Retro Service Ticket (Printable)
├── order-success.php            # Perforated Retro Parts Invoice (Printable)
├── track.php                    # Live Vehicle & Service Ticket Telemetry Tracker
├── login.php                    # Customer Login & Profile Registration
├── logout.php                   # Customer Logout
├── profile.php                  # Customer Account & Past Orders History
├── database.sql                 # MySQL Schema with all 7 tables & realistic seed data
├── .htaccess                    # Apache web server configuration
└── README.md                    # Project documentation & setup guide
```

---

## 🔒 Security Best Practices Implemented

1. **SQL Injection Prevention:** 100% prepared PDO statements with parameterized inputs. No raw SQL concatenation.
2. **XSS Escaping:** Comprehensive output sanitation via `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` through helper function `e()`.
3. **CSRF Protection:** Cryptographic random token verification on all POST forms via `csrf_field()` and `require_csrf()`.
4. **Password Security:** One-way salted hashing using PHP's native `password_hash($pass, PASSWORD_BCRYPT)`.
5. **Secure Sessions:** Sessions initialized with `httponly`, `samesite => Lax`, and `secure` flag when HTTPS is active; session IDs regenerated upon login to prevent session fixation.
6. **Safe File Uploads:** Uploaded part images are validated by MIME type via `finfo`, size capped at 3MB, sanitized with randomized filenames, and placed outside executable scripts.

---

## 🏁 Rebranding & Customization in Seconds

To rebrand this application for any prospective garage or auto parts client, edit **`config/config.php`**:
- `APP_NAME`: Name of the garage (e.g. `'REV-MATCH RETRO GARAGE'`)
- `APP_TAGLINE`: Slogan or workshop specialty
- `CURRENCY_SYMBOL`: Default `'Rs.'` or `'LKR'` (can be changed to `'$'`, `'€'`, `'£'`)
- `GARAGE_PHONE`: Main landline or service desk phone
- `GARAGE_WHATSAPP`: WhatsApp number for automated 1-click dispatch alerts
- `GARAGE_ADDRESS`: Workshop bay address and city
- `GARAGE_HOURS`: Operating days and working hours

---
*Speedwell Retro Garage Management System • Engineered for Classic & Modern JDM Works • Est. 1988*
