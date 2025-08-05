# Invoice & Expense Management Platform Design

## 🏗️ System Architecture

### Technology Stack
- **Backend**: PHP 7.4+ (vanilla PHP with minimal dependencies)
- **Frontend**: HTML5, CSS3, JavaScript (Alpine.js for reactivity)
- **Database**: MySQL/MariaDB
- **PDF Generation**: TCPDF (pure PHP library)
- **Charts**: Chart.js (lightweight, no dependencies)
- **Icons**: Tabler Icons (SVG-based)
- **CSS Framework**: Custom lightweight CSS with CSS Grid/Flexbox

### Directory Structure
```
/
├── index.php                 # Main entry point
├── config/
│   ├── config.php           # Database and app configuration
│   └── constants.php        # Application constants
├── core/
│   ├── Database.php         # Database connection class
│   ├── Auth.php            # Authentication handler
│   ├── Router.php          # Simple routing system
│   └── Validator.php       # Input validation
├── models/
│   ├── Invoice.php         # Invoice model
│   ├── Expense.php         # Expense model
│   ├── Client.php          # Client model
│   ├── Product.php         # Product/Service model
│   └── User.php            # User model
├── controllers/
│   ├── DashboardController.php
│   ├── InvoiceController.php
│   ├── ExpenseController.php
│   └── SettingsController.php
├── views/
│   ├── layouts/
│   │   ├── header.php
│   │   ├── sidebar.php
│   │   └── footer.php
│   ├── dashboard/
│   ├── invoices/
│   └── expenses/
├── assets/
│   ├── css/
│   ├── js/
│   └── img/
├── uploads/
├── lang/
│   ├── lt.php              # Lithuanian translations
│   └── en.php              # English translations
└── .htaccess               # URL rewriting rules
```

## 📊 Database Schema

### Core Tables

```sql
-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    company_name VARCHAR(255),
    company_code VARCHAR(50),
    vat_code VARCHAR(50),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Clients table
CREATE TABLE clients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50),
    vat_code VARCHAR(50),
    address TEXT,
    email VARCHAR(255),
    phone VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Products/Services table
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(100),
    unit VARCHAR(50) DEFAULT 'vnt.',
    price DECIMAL(10,2),
    vat_rate DECIMAL(5,2) DEFAULT 21.00,
    type ENUM('product', 'service') DEFAULT 'service',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Invoices table
CREATE TABLE invoices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    client_id INT NOT NULL,
    invoice_number VARCHAR(50) NOT NULL,
    invoice_date DATE NOT NULL,
    due_date DATE,
    type ENUM('standard', 'prepayment', 'credit') DEFAULT 'standard',
    status ENUM('draft', 'issued', 'paid', 'overdue', 'cancelled') DEFAULT 'draft',
    currency VARCHAR(3) DEFAULT 'EUR',
    subtotal DECIMAL(10,2),
    vat_amount DECIMAL(10,2),
    total DECIMAL(10,2),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (client_id) REFERENCES clients(id)
);

-- Invoice items table
CREATE TABLE invoice_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    invoice_id INT NOT NULL,
    product_id INT,
    description TEXT NOT NULL,
    quantity DECIMAL(10,3),
    unit VARCHAR(50),
    price DECIMAL(10,2),
    discount DECIMAL(5,2) DEFAULT 0,
    vat_rate DECIMAL(5,2),
    line_total DECIMAL(10,2),
    vat_amount DECIMAL(10,2),
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Expenses table
CREATE TABLE expenses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    vendor_name VARCHAR(255) NOT NULL,
    document_number VARCHAR(100),
    document_date DATE NOT NULL,
    type ENUM('vat_invoice', 'standard_invoice', 'credit_invoice', 'foreign_invoice', 'receipt', 'other') DEFAULT 'vat_invoice',
    category VARCHAR(100),
    subtotal DECIMAL(10,2),
    vat_amount DECIMAL(10,2),
    total DECIMAL(10,2),
    notes TEXT,
    attachment_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

## 🎨 User Interface Design

### Color Scheme
```css
:root {
    --primary-color: #2563eb;      /* Blue */
    --secondary-color: #64748b;    /* Slate */
    --success-color: #10b981;      /* Green */
    --warning-color: #f59e0b;      /* Amber */
    --danger-color: #ef4444;       /* Red */
    --background: #f8fafc;
    --card-bg: #ffffff;
    --text-primary: #1e293b;
    --text-secondary: #64748b;
    --border-color: #e2e8f0;
}
```

### Layout Structure

```html
<!-- Main Layout -->
<div class="app-container">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="logo">
            <h2>Sąskaitos Pro</h2>
        </div>
        <nav class="nav-menu">
            <a href="/dashboard" class="nav-item active">
                <svg><!-- Dashboard icon --></svg>
                <span>Statistika</span>
            </a>
            <a href="/invoices" class="nav-item">
                <svg><!-- Invoice icon --></svg>
                <span>Sąskaitos faktūros</span>
            </a>
            <!-- More menu items -->
        </nav>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content">
        <header class="top-bar">
            <h1 class="page-title">Statistika</h1>
            <div class="user-menu">
                <!-- User dropdown -->
            </div>
        </header>
        
        <div class="content-area">
            <!-- Dynamic content -->
        </div>
    </main>
</div>
```

## 📊 Dashboard Module

### Key Metrics Cards
```php
// Dashboard statistics structure
$statistics = [
    'issued_invoices' => [
        'label' => 'Išrašytos sąskaitos',
        'value' => 15420.50,
        'count' => 23,
        'trend' => '+12%',
        'color' => 'primary'
    ],
    'received_payments' => [
        'label' => 'Gauti apmokėjimai',
        'value' => 12350.00,
        'count' => 18,
        'trend' => '+8%',
        'color' => 'success'
    ],
    'unpaid_invoices' => [
        'label' => 'Neapmokėtos sąskaitos',
        'value' => 3070.50,
        'count' => 5,
        'trend' => '-23%',
        'color' => 'warning'
    ],
    'expenses' => [
        'label' => 'Išlaidos',
        'value' => 8920.30,
        'count' => 42,
        'trend' => '+5%',
        'color' => 'danger'
    ]
];
```

### Interactive Charts
```javascript
// Chart.js configuration for income/expense visualization
const monthlyChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Sausis', 'Vasaris', 'Kovas', /* ... */],
        datasets: [{
            label: 'Pajamos',
            data: [5420, 6230, 4890, /* ... */],
            backgroundColor: '#10b981'
        }, {
            label: 'Išlaidos',
            data: [3210, 2890, 3450, /* ... */],
            backgroundColor: '#ef4444'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            tooltip: {
                callbacks: {
                    label: (context) => `${context.dataset.label}: €${context.parsed.y.toFixed(2)}`
                }
            }
        }
    }
});
```

## 🧾 Invoice Creation Module

### Dynamic Form Fields
```javascript
// Alpine.js component for invoice form
Alpine.data('invoiceForm', () => ({
    invoice: {
        client_id: null,
        invoice_date: new Date().toISOString().split('T')[0],
        due_date: null,
        items: [{
            description: '',
            quantity: 1,
            unit: 'vnt.',
            price: 0,
            vat_rate: 21,
            discount: 0
        }]
    },
    
    addItem() {
        this.invoice.items.push({
            description: '',
            quantity: 1,
            unit: 'vnt.',
            price: 0,
            vat_rate: 21,
            discount: 0
        });
    },
    
    removeItem(index) {
        if (this.invoice.items.length > 1) {
            this.invoice.items.splice(index, 1);
        }
    },
    
    calculateItemTotal(item) {
        const subtotal = item.quantity * item.price * (1 - item.discount / 100);
        const vat = subtotal * (item.vat_rate / 100);
        return {
            subtotal: subtotal,
            vat: vat,
            total: subtotal + vat
        };
    },
    
    get totals() {
        let subtotal = 0;
        let vat = 0;
        
        this.invoice.items.forEach(item => {
            const itemTotals = this.calculateItemTotal(item);
            subtotal += itemTotals.subtotal;
            vat += itemTotals.vat;
        });
        
        return {
            subtotal: subtotal,
            vat: vat,
            total: subtotal + vat
        };
    }
}));
```

## 🌍 Localization System

```php
// Language helper class
class Language {
    private static $translations = [];
    private static $currentLang = 'lt';
    
    public static function load($lang = 'lt') {
        self::$currentLang = $lang;
        $langFile = __DIR__ . "/../lang/{$lang}.php";
        if (file_exists($langFile)) {
            self::$translations = include $langFile;
        }
    }
    
    public static function get($key, $params = []) {
        $translation = self::$translations[$key] ?? $key;
        
        foreach ($params as $param => $value) {
            $translation = str_replace(":{$param}", $value, $translation);
        }
        
        return $translation;
    }
}

// Lithuanian language file (lang/lt.php)
return [
    'dashboard' => 'Statistika',
    'invoices' => 'Sąskaitos faktūros',
    'invoice.create' => 'Sukurti sąskaitą',
    'invoice.number' => 'Sąskaitos numeris',
    'client' => 'Klientas',
    'date' => 'Data',
    'due_date' => 'Apmokėti iki',
    'description' => 'Aprašymas',
    'quantity' => 'Kiekis',
    'unit' => 'Mato vnt.',
    'price' => 'Kaina',
    'vat' => 'PVM',
    'total' => 'Iš viso',
    'status.paid' => 'Apmokėta',
    'status.pending' => 'Laukiama',
    'status.overdue' => 'Vėluoja',
];
```

## 🔒 Security Considerations

### Authentication System
```php
class Auth {
    public static function login($email, $password) {
        $db = Database::getInstance();
        $user = $db->query("SELECT * FROM users WHERE email = ?", [$email])->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            return true;
        }
        
        return false;
    }
    
    public static function check() {
        return isset($_SESSION['user_id']);
    }
    
    public static function validateCSRF($token) {
        return isset($_SESSION['csrf_token']) && 
               hash_equals($_SESSION['csrf_token'], $token);
    }
}
```

### Input Validation
```php
class Validator {
    public static function validate($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            
            if (strpos($rule, 'required') !== false && empty($value)) {
                $errors[$field] = "Laukas '$field' yra privalomas";
            }
            
            if (strpos($rule, 'email') !== false && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[$field] = "Neteisingas el. pašto formatas";
            }
            
            if (preg_match('/min:(\d+)/', $rule, $matches) && strlen($value) < $matches[1]) {
                $errors[$field] = "Minimumas {$matches[1]} simboliai";
            }
        }
        
        return $errors;
    }
}
```

## 📱 Responsive Design

### Mobile-First CSS
```css
/* Base mobile styles */
.app-container {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

.sidebar {
    position: fixed;
    top: 0;
    left: -280px;
    width: 280px;
    height: 100vh;
    background: var(--card-bg);
    transition: left 0.3s ease;
    z-index: 1000;
}

.sidebar.active {
    left: 0;
}

/* Tablet and up */
@media (min-width: 768px) {
    .app-container {
        flex-direction: row;
    }
    
    .sidebar {
        position: static;
        left: 0;
    }
    
    .main-content {
        flex: 1;
        margin-left: 0;
    }
}

/* Desktop */
@media (min-width: 1024px) {
    .content-area {
        padding: 2rem;
    }
    
    .stats-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}
```

## 🚀 Performance Optimization

### Caching Strategy
```php
class Cache {
    private static $cacheDir = __DIR__ . '/../cache/';
    
    public static function get($key) {
        $file = self::$cacheDir . md5($key) . '.cache';
        
        if (file_exists($file)) {
            $data = unserialize(file_get_contents($file));
            if ($data['expires'] > time()) {
                return $data['value'];
            }
            unlink($file);
        }
        
        return null;
    }
    
    public static function set($key, $value, $ttl = 3600) {
        $file = self::$cacheDir . md5($key) . '.cache';
        $data = [
            'value' => $value,
            'expires' => time() + $ttl
        ];
        
        file_put_contents($file, serialize($data));
    }
}
```

### Database Query Optimization
```php
// Use prepared statements and fetch only needed columns
$invoices = $db->query("
    SELECT 
        i.id, 
        i.invoice_number, 
        i.invoice_date,
        i.total,
        i.status,
        c.name as client_name
    FROM invoices i
    LEFT JOIN clients c ON i.client_id = c.id
    WHERE i.user_id = ? 
    AND YEAR(i.invoice_date) = ?
    ORDER BY i.invoice_date DESC
    LIMIT 50
", [$userId, $year])->fetchAll();
```

## 📄 PDF Generation

```php
// Using TCPDF for invoice PDF generation
class InvoicePDF extends TCPDF {
    public function generateInvoice($invoice, $items) {
        $this->AddPage();
        
        // Company header
        $this->SetFont('dejavusans', 'B', 16);
        $this->Cell(0, 10, $invoice['company_name'], 0, 1);
        
        // Invoice details
        $this->SetFont('dejavusans', '', 10);
        $this->Cell(0, 5, 'Sąskaita faktūra Nr. ' . $invoice['invoice_number'], 0, 1);
        $this->Cell(0, 5, 'Data: ' . $invoice['invoice_date'], 0, 1);
        
        // Items table
        $this->Ln(10);
        $this->SetFillColor(240, 240, 240);
        $this->Cell(80, 7, 'Aprašymas', 1, 0, 'L', true);
        $this->Cell(20, 7, 'Kiekis', 1, 0, 'C', true);
        $this->Cell(25, 7, 'Kaina', 1, 0, 'R', true);
        $this->Cell(20, 7, 'PVM', 1, 0, 'R', true);
        $this->Cell(30, 7, 'Suma', 1, 1, 'R', true);
        
        // Add items...
    }
}
```

## 🔧 Installation & Deployment

### Installation Script
```php
// install.php
<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $config = [
        'db_host' => $_POST['db_host'],
        'db_name' => $_POST['db_name'],
        'db_user' => $_POST['db_user'],
        'db_pass' => $_POST['db_pass'],
    ];
    
    try {
        // Test database connection
        $pdo = new PDO(
            "mysql:host={$config['db_host']};dbname={$config['db_name']}", 
            $config['db_user'], 
            $config['db_pass']
        );
        
        // Run SQL schema
        $sql = file_get_contents(__DIR__ . '/schema.sql');
        $pdo->exec($sql);
        
        // Create config file
        $configContent = "<?php\n";
        $configContent .= "define('DB_HOST', '{$config['db_host']}');\n";
        $configContent .= "define('DB_NAME', '{$config['db_name']}');\n";
        $configContent .= "define('DB_USER', '{$config['db_user']}');\n";
        $configContent .= "define('DB_PASS', '{$config['db_pass']}');\n";
        
        file_put_contents(__DIR__ . '/config/config.php', $configContent);
        
        // Create admin user
        $email = $_POST['admin_email'];
        $password = password_hash($_POST['admin_password'], PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
        $stmt->execute([$email, $password]);
        
        echo "Installation completed successfully!";
        
    } catch (PDOException $e) {
        echo "Installation failed: " . $e->getMessage();
    }
}
?>
```

### .htaccess Configuration
```apache
RewriteEngine On

# Redirect to HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [R=301,L]

# Prevent directory listing
Options -Indexes

# Route all requests through index.php
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?route=$1 [QSA,L]

# Security headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
</IfModule>

# Compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/css text/javascript application/javascript
</IfModule>
```

## 📈 Future Enhancements

1. **API Integration**: RESTful API for third-party integrations
2. **Multi-company Support**: Manage multiple businesses from one account
3. **Advanced Reporting**: Custom report builder with export options
4. **Email Integration**: Automatic invoice sending and payment reminders
5. **Bank Integration**: Import bank statements for automatic reconciliation
6. **Mobile App**: Progressive Web App (PWA) for offline access
7. **Recurring Invoices**: Automated monthly/yearly invoice generation
8. **Inventory Management**: Stock tracking for products
9. **Time Tracking**: Project-based time tracking and billing
10. **Multi-language**: Full support for EN, RU, PL languages

## 🎯 Key Features Summary

- ✅ Lightweight and fast - optimized for shared hosting
- ✅ No heavy dependencies - pure PHP with minimal libraries
- ✅ Responsive design - works on all devices
- ✅ Lithuanian-first with i18n support
- ✅ EU VAT compliant
- ✅ Real-time calculations and updates
- ✅ Secure authentication and CSRF protection
- ✅ PDF generation for invoices
- ✅ Comprehensive financial statistics
- ✅ Client and product management
- ✅ Easy installation process