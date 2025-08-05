# Implementation Examples

## 🚀 Core PHP Classes Implementation

### Database Connection Class
```php
<?php
// core/Database.php
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $this->connection = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function query($sql, $params = []) {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
}
```

### Simple Router Implementation
```php
<?php
// core/Router.php
class Router {
    private $routes = [];
    
    public function get($path, $handler) {
        $this->routes['GET'][$path] = $handler;
    }
    
    public function post($path, $handler) {
        $this->routes['POST'][$path] = $handler;
    }
    
    public function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Remove base path if needed
        $path = str_replace('/index.php', '', $path);
        
        // Check for exact match
        if (isset($this->routes[$method][$path])) {
            return $this->execute($this->routes[$method][$path]);
        }
        
        // Check for pattern match (e.g., /invoices/{id})
        foreach ($this->routes[$method] as $route => $handler) {
            $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $route);
            if (preg_match('#^' . $pattern . '$#', $path, $matches)) {
                array_shift($matches);
                return $this->execute($handler, $matches);
            }
        }
        
        // 404
        http_response_code(404);
        include 'views/404.php';
    }
    
    private function execute($handler, $params = []) {
        if (is_string($handler)) {
            list($controller, $method) = explode('@', $handler);
            $controller = new $controller();
            return call_user_func_array([$controller, $method], $params);
        }
        
        return call_user_func_array($handler, $params);
    }
}
```

## 📄 Invoice Model Implementation

```php
<?php
// models/Invoice.php
class Invoice {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function create($data) {
        // Begin transaction
        $this->db->query("START TRANSACTION");
        
        try {
            // Insert invoice
            $sql = "INSERT INTO invoices (
                user_id, client_id, invoice_number, invoice_date, 
                due_date, type, status, currency, subtotal, 
                vat_amount, total, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $this->db->query($sql, [
                $data['user_id'],
                $data['client_id'],
                $data['invoice_number'],
                $data['invoice_date'],
                $data['due_date'],
                $data['type'] ?? 'standard',
                $data['status'] ?? 'draft',
                $data['currency'] ?? 'EUR',
                $data['subtotal'],
                $data['vat_amount'],
                $data['total'],
                $data['notes'] ?? null
            ]);
            
            $invoiceId = $this->db->lastInsertId();
            
            // Insert invoice items
            foreach ($data['items'] as $item) {
                $this->addItem($invoiceId, $item);
            }
            
            $this->db->query("COMMIT");
            return $invoiceId;
            
        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            throw $e;
        }
    }
    
    private function addItem($invoiceId, $item) {
        $sql = "INSERT INTO invoice_items (
            invoice_id, product_id, description, quantity, 
            unit, price, discount, vat_rate, line_total, vat_amount
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $lineTotal = $item['quantity'] * $item['price'] * (1 - $item['discount'] / 100);
        $vatAmount = $lineTotal * ($item['vat_rate'] / 100);
        
        $this->db->query($sql, [
            $invoiceId,
            $item['product_id'] ?? null,
            $item['description'],
            $item['quantity'],
            $item['unit'],
            $item['price'],
            $item['discount'] ?? 0,
            $item['vat_rate'] ?? 21,
            $lineTotal,
            $vatAmount
        ]);
    }
    
    public function getById($id) {
        $sql = "SELECT i.*, c.name as client_name, c.code as client_code,
                c.vat_code as client_vat_code, c.address as client_address
                FROM invoices i
                LEFT JOIN clients c ON i.client_id = c.id
                WHERE i.id = ?";
        
        $invoice = $this->db->query($sql, [$id])->fetch();
        
        if ($invoice) {
            // Get items
            $sql = "SELECT * FROM invoice_items WHERE invoice_id = ?";
            $invoice['items'] = $this->db->query($sql, [$id])->fetchAll();
        }
        
        return $invoice;
    }
    
    public function getList($userId, $filters = []) {
        $sql = "SELECT i.*, c.name as client_name
                FROM invoices i
                LEFT JOIN clients c ON i.client_id = c.id
                WHERE i.user_id = ?";
        $params = [$userId];
        
        // Apply filters
        if (!empty($filters['year'])) {
            $sql .= " AND YEAR(i.invoice_date) = ?";
            $params[] = $filters['year'];
        }
        
        if (!empty($filters['month'])) {
            $sql .= " AND MONTH(i.invoice_date) = ?";
            $params[] = $filters['month'];
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND i.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['client_id'])) {
            $sql .= " AND i.client_id = ?";
            $params[] = $filters['client_id'];
        }
        
        $sql .= " ORDER BY i.invoice_date DESC, i.id DESC";
        
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
        }
        
        return $this->db->query($sql, $params)->fetchAll();
    }
    
    public function getNextNumber($userId, $prefix = 'INV') {
        $year = date('Y');
        
        $sql = "SELECT MAX(CAST(SUBSTRING_INDEX(invoice_number, '-', -1) AS UNSIGNED)) as max_num
                FROM invoices 
                WHERE user_id = ? 
                AND invoice_number LIKE ?
                AND YEAR(invoice_date) = ?";
        
        $result = $this->db->query($sql, [$userId, $prefix . '-' . $year . '-%', $year])->fetch();
        $nextNum = ($result['max_num'] ?? 0) + 1;
        
        return sprintf('%s-%d-%04d', $prefix, $year, $nextNum);
    }
    
    public function updateStatus($id, $status) {
        $sql = "UPDATE invoices SET status = ? WHERE id = ?";
        return $this->db->query($sql, [$status, $id]);
    }
}
```

## 🎯 Invoice Controller Implementation

```php
<?php
// controllers/InvoiceController.php
class InvoiceController {
    private $invoiceModel;
    private $clientModel;
    private $productModel;
    
    public function __construct() {
        $this->invoiceModel = new Invoice();
        $this->clientModel = new Client();
        $this->productModel = new Product();
    }
    
    public function index() {
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        
        $userId = $_SESSION['user_id'];
        $filters = [
            'year' => $_GET['year'] ?? date('Y'),
            'month' => $_GET['month'] ?? null,
            'status' => $_GET['status'] ?? null,
            'limit' => 50
        ];
        
        $invoices = $this->invoiceModel->getList($userId, $filters);
        
        // Calculate statistics
        $stats = $this->calculateStats($invoices);
        
        include 'views/invoices/index.php';
    }
    
    public function create() {
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        
        $userId = $_SESSION['user_id'];
        
        // Get data for dropdowns
        $clients = $this->clientModel->getActive($userId);
        $products = $this->productModel->getActive($userId);
        $nextNumber = $this->invoiceModel->getNextNumber($userId, 'MAN');
        
        include 'views/invoices/create.php';
    }
    
    public function store() {
        if (!Auth::check() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /login');
            exit;
        }
        
        // Validate CSRF token
        if (!Auth::validateCSRF($_POST['csrf_token'] ?? '')) {
            die('Invalid CSRF token');
        }
        
        $userId = $_SESSION['user_id'];
        
        // Validate input
        $errors = $this->validateInvoice($_POST);
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = $_POST;
            header('Location: /invoices/create');
            exit;
        }
        
        // Calculate totals
        $totals = $this->calculateTotals($_POST['items']);
        
        // Prepare data
        $invoiceData = [
            'user_id' => $userId,
            'client_id' => $_POST['client_id'],
            'invoice_number' => $_POST['invoice_number'],
            'invoice_date' => $_POST['invoice_date'],
            'due_date' => $_POST['due_date'],
            'type' => $_POST['type'] ?? 'standard',
            'status' => $_POST['save_as_draft'] ? 'draft' : 'issued',
            'currency' => $_POST['currency'] ?? 'EUR',
            'subtotal' => $totals['subtotal'],
            'vat_amount' => $totals['vat'],
            'total' => $totals['total'],
            'notes' => $_POST['notes'] ?? null,
            'items' => $_POST['items']
        ];
        
        try {
            $invoiceId = $this->invoiceModel->create($invoiceData);
            
            $_SESSION['success'] = 'Sąskaita sėkmingai sukurta!';
            
            // Send email if requested
            if (!empty($_POST['send_email'])) {
                $this->sendInvoiceEmail($invoiceId);
            }
            
            header('Location: /invoices/' . $invoiceId);
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Klaida kuriant sąskaitą: ' . $e->getMessage();
            $_SESSION['old'] = $_POST;
            header('Location: /invoices/create');
        }
    }
    
    public function show($id) {
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        
        $invoice = $this->invoiceModel->getById($id);
        
        if (!$invoice || $invoice['user_id'] != $_SESSION['user_id']) {
            http_response_code(404);
            include 'views/404.php';
            return;
        }
        
        include 'views/invoices/show.php';
    }
    
    public function pdf($id) {
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        
        $invoice = $this->invoiceModel->getById($id);
        
        if (!$invoice || $invoice['user_id'] != $_SESSION['user_id']) {
            http_response_code(404);
            die('Invoice not found');
        }
        
        // Generate PDF
        require_once 'libs/tcpdf/tcpdf.php';
        $pdf = new InvoicePDF();
        $pdf->generateInvoice($invoice, $invoice['items']);
        
        $filename = 'saskaita-' . $invoice['invoice_number'] . '.pdf';
        $pdf->Output($filename, 'D');
    }
    
    private function calculateTotals($items) {
        $subtotal = 0;
        $vat = 0;
        
        foreach ($items as $item) {
            $lineTotal = $item['quantity'] * $item['price'] * (1 - ($item['discount'] ?? 0) / 100);
            $itemVat = $lineTotal * (($item['vat_rate'] ?? 21) / 100);
            
            $subtotal += $lineTotal;
            $vat += $itemVat;
        }
        
        return [
            'subtotal' => round($subtotal, 2),
            'vat' => round($vat, 2),
            'total' => round($subtotal + $vat, 2)
        ];
    }
    
    private function validateInvoice($data) {
        $errors = [];
        
        if (empty($data['client_id'])) {
            $errors['client_id'] = 'Prašome pasirinkti klientą';
        }
        
        if (empty($data['invoice_number'])) {
            $errors['invoice_number'] = 'Sąskaitos numeris privalomas';
        }
        
        if (empty($data['invoice_date'])) {
            $errors['invoice_date'] = 'Data privaloma';
        }
        
        if (empty($data['items']) || !is_array($data['items'])) {
            $errors['items'] = 'Pridėkite bent vieną prekę/paslaugą';
        }
        
        return $errors;
    }
}
```

## 📊 Dashboard Statistics Implementation

```php
<?php
// controllers/DashboardController.php
class DashboardController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function index() {
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
        
        $userId = $_SESSION['user_id'];
        $year = $_GET['year'] ?? date('Y');
        $month = $_GET['month'] ?? null;
        
        // Get statistics
        $stats = $this->getStatistics($userId, $year, $month);
        $monthlyData = $this->getMonthlyData($userId, $year);
        $recentInvoices = $this->getRecentInvoices($userId);
        $topClients = $this->getTopClients($userId, $year);
        
        include 'views/dashboard/index.php';
    }
    
    private function getStatistics($userId, $year, $month = null) {
        $params = [$userId, $year];
        $monthCondition = "";
        
        if ($month) {
            $monthCondition = " AND MONTH(invoice_date) = ?";
            $params[] = $month;
        }
        
        // Issued invoices
        $sql = "SELECT 
                COUNT(*) as count,
                COALESCE(SUM(total), 0) as total
                FROM invoices 
                WHERE user_id = ? 
                AND YEAR(invoice_date) = ?
                AND status IN ('issued', 'paid')
                $monthCondition";
        
        $issued = $this->db->query($sql, $params)->fetch();
        
        // Paid invoices
        $sql = "SELECT 
                COUNT(*) as count,
                COALESCE(SUM(total), 0) as total
                FROM invoices 
                WHERE user_id = ? 
                AND YEAR(invoice_date) = ?
                AND status = 'paid'
                $monthCondition";
        
        $paid = $this->db->query($sql, $params)->fetch();
        
        // Unpaid invoices
        $sql = "SELECT 
                COUNT(*) as count,
                COALESCE(SUM(total), 0) as total
                FROM invoices 
                WHERE user_id = ? 
                AND YEAR(invoice_date) = ?
                AND status IN ('issued', 'overdue')
                $monthCondition";
        
        $unpaid = $this->db->query($sql, $params)->fetch();
        
        // Expenses
        $sql = "SELECT 
                COUNT(*) as count,
                COALESCE(SUM(total), 0) as total
                FROM expenses 
                WHERE user_id = ? 
                AND YEAR(document_date) = ?
                $monthCondition";
        
        $expenses = $this->db->query($sql, $params)->fetch();
        
        // Calculate trends (compared to previous period)
        $trends = $this->calculateTrends($userId, $year, $month);
        
        return [
            'issued' => [
                'count' => $issued['count'],
                'total' => $issued['total'],
                'trend' => $trends['issued']
            ],
            'paid' => [
                'count' => $paid['count'],
                'total' => $paid['total'],
                'trend' => $trends['paid']
            ],
            'unpaid' => [
                'count' => $unpaid['count'],
                'total' => $unpaid['total'],
                'trend' => $trends['unpaid']
            ],
            'expenses' => [
                'count' => $expenses['count'],
                'total' => $expenses['total'],
                'trend' => $trends['expenses']
            ],
            'profit' => [
                'total' => $paid['total'] - $expenses['total'],
                'trend' => $trends['profit']
            ]
        ];
    }
    
    private function getMonthlyData($userId, $year) {
        // Income by month
        $sql = "SELECT 
                MONTH(invoice_date) as month,
                SUM(total) as total
                FROM invoices
                WHERE user_id = ?
                AND YEAR(invoice_date) = ?
                AND status IN ('issued', 'paid')
                GROUP BY MONTH(invoice_date)
                ORDER BY month";
        
        $income = $this->db->query($sql, [$userId, $year])->fetchAll();
        
        // Expenses by month
        $sql = "SELECT 
                MONTH(document_date) as month,
                SUM(total) as total
                FROM expenses
                WHERE user_id = ?
                AND YEAR(document_date) = ?
                GROUP BY MONTH(document_date)
                ORDER BY month";
        
        $expenses = $this->db->query($sql, [$userId, $year])->fetchAll();
        
        // Format for Chart.js
        $monthlyData = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthlyData[$i] = [
                'income' => 0,
                'expenses' => 0,
                'profit' => 0
            ];
        }
        
        foreach ($income as $row) {
            $monthlyData[$row['month']]['income'] = $row['total'];
        }
        
        foreach ($expenses as $row) {
            $monthlyData[$row['month']]['expenses'] = $row['total'];
        }
        
        foreach ($monthlyData as &$data) {
            $data['profit'] = $data['income'] - $data['expenses'];
        }
        
        return $monthlyData;
    }
}
```

## 🎨 Invoice Creation View

```php
<!-- views/invoices/create.php -->
<?php include 'views/layouts/header.php'; ?>

<div class="page-header">
    <h1>Nauja sąskaita faktūra</h1>
    <div class="page-actions">
        <a href="/invoices" class="btn btn-secondary">
            <svg class="icon"><!-- Back icon --></svg>
            Grįžti
        </a>
    </div>
</div>

<form x-data="invoiceForm()" @submit.prevent="submitForm" method="POST" action="/invoices">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
    
    <div class="card">
        <div class="card-body">
            <div class="row">
                <!-- Left Column -->
                <div class="col-md-6">
                    <h3>Sąskaitos informacija</h3>
                    
                    <div class="form-group">
                        <label class="form-label">Tipas</label>
                        <select name="type" class="form-input" x-model="invoice.type">
                            <option value="standard">Standartinė PVM sąskaita</option>
                            <option value="prepayment">Išankstinė sąskaita</option>
                            <option value="credit">Kreditinė sąskaita</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Serija ir numeris</label>
                        <div class="input-group">
                            <input type="text" name="invoice_number" class="form-input" 
                                   value="<?= $nextNumber ?>" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Data</label>
                                <input type="date" name="invoice_date" class="form-input" 
                                       x-model="invoice.date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Apmokėti iki</label>
                                <input type="date" name="due_date" class="form-input" 
                                       x-model="invoice.dueDate">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column -->
                <div class="col-md-6">
                    <h3>Klientas</h3>
                    
                    <div class="form-group">
                        <label class="form-label">Pasirinkite klientą</label>
                        <div class="input-group">
                            <select name="client_id" class="form-input" 
                                    x-model="invoice.clientId" 
                                    @change="loadClient()" required>
                                <option value="">Pasirinkite...</option>
                                <?php foreach ($clients as $client): ?>
                                    <option value="<?= $client['id'] ?>" 
                                            data-info='<?= json_encode($client) ?>'>
                                        <?= htmlspecialchars($client['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="btn btn-secondary" @click="showNewClientModal()">
                                <svg class="icon"><!-- Plus icon --></svg>
                                Naujas
                            </button>
                        </div>
                    </div>
                    
                    <div x-show="selectedClient" class="client-info">
                        <p><strong x-text="selectedClient.name"></strong></p>
                        <p x-text="selectedClient.code"></p>
                        <p x-text="selectedClient.vat_code"></p>
                        <p x-text="selectedClient.address"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Invoice Items -->
    <div class="card mt-4">
        <div class="card-header">
            <h3>Prekės / Paslaugos</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 40%">Aprašymas</th>
                            <th style="width: 10%">Kiekis</th>
                            <th style="width: 10%">Matas</th>
                            <th style="width: 10%">Kaina</th>
                            <th style="width: 8%">Nuol. %</th>
                            <th style="width: 8%">PVM %</th>
                            <th style="width: 12%">Suma</th>
                            <th style="width: 2%"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, index) in invoice.items" :key="index">
                            <tr>
                                <td>
                                    <input type="text" 
                                           :name="'items[' + index + '][description]'"
                                           class="form-input" 
                                           x-model="item.description"
                                           placeholder="Prekės/paslaugos aprašymas"
                                           required>
                                </td>
                                <td>
                                    <input type="number" 
                                           :name="'items[' + index + '][quantity]'"
                                           class="form-input" 
                                           x-model.number="item.quantity"
                                           min="0.001" 
                                           step="0.001"
                                           @input="calculateTotals()"
                                           required>
                                </td>
                                <td>
                                    <input type="text" 
                                           :name="'items[' + index + '][unit]'"
                                           class="form-input" 
                                           x-model="item.unit"
                                           placeholder="vnt.">
                                </td>
                                <td>
                                    <input type="number" 
                                           :name="'items[' + index + '][price]'"
                                           class="form-input" 
                                           x-model.number="item.price"
                                           min="0" 
                                           step="0.01"
                                           @input="calculateTotals()"
                                           required>
                                </td>
                                <td>
                                    <input type="number" 
                                           :name="'items[' + index + '][discount]'"
                                           class="form-input" 
                                           x-model.number="item.discount"
                                           min="0" 
                                           max="100" 
                                           step="0.01"
                                           @input="calculateTotals()">
                                </td>
                                <td>
                                    <input type="number" 
                                           :name="'items[' + index + '][vat_rate]'"
                                           class="form-input" 
                                           x-model.number="item.vatRate"
                                           min="0" 
                                           max="100" 
                                           step="0.01"
                                           @input="calculateTotals()">
                                </td>
                                <td>
                                    <span class="item-total" x-text="formatCurrency(getItemTotal(item))"></span>
                                </td>
                                <td>
                                    <button type="button" 
                                            class="btn-icon btn-danger" 
                                            @click="removeItem(index)"
                                            x-show="invoice.items.length > 1">
                                        <svg class="icon"><!-- Trash icon --></svg>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            
            <button type="button" class="btn btn-secondary mt-3" @click="addItem()">
                <svg class="icon"><!-- Plus icon --></svg>
                Pridėti eilutę
            </button>
        </div>
    </div>
    
    <!-- Totals -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <label class="form-label">Pastabos</label>
                    <textarea name="notes" class="form-input" rows="4" 
                              placeholder="Papildoma informacija..."></textarea>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <table class="totals-table">
                        <tr>
                            <td>Suma be PVM:</td>
                            <td class="text-right" x-text="formatCurrency(totals.subtotal)"></td>
                        </tr>
                        <tr>
                            <td>PVM 21%:</td>
                            <td class="text-right" x-text="formatCurrency(totals.vat)"></td>
                        </tr>
                        <tr class="total-row">
                            <td><strong>IŠ VISO:</strong></td>
                            <td class="text-right"><strong x-text="formatCurrency(totals.total)"></strong></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Actions -->
    <div class="form-actions mt-4">
        <button type="submit" name="save_as_draft" value="1" class="btn btn-secondary">
            <svg class="icon"><!-- Save icon --></svg>
            Išsaugoti juodraštį
        </button>
        <button type="submit" class="btn btn-primary">
            <svg class="icon"><!-- Check icon --></svg>
            Išrašyti sąskaitą
        </button>
        <button type="submit" name="send_email" value="1" class="btn btn-primary">
            <svg class="icon"><!-- Email icon --></svg>
            Išrašyti ir siųsti
        </button>
    </div>
</form>

<script>
function invoiceForm() {
    return {
        invoice: {
            type: 'standard',
            date: new Date().toISOString().split('T')[0],
            dueDate: '',
            clientId: '',
            items: [{
                description: '',
                quantity: 1,
                unit: 'vnt.',
                price: 0,
                discount: 0,
                vatRate: 21
            }]
        },
        selectedClient: null,
        totals: {
            subtotal: 0,
            vat: 0,
            total: 0
        },
        
        init() {
            // Set due date to 14 days from today
            const dueDate = new Date();
            dueDate.setDate(dueDate.getDate() + 14);
            this.invoice.dueDate = dueDate.toISOString().split('T')[0];
            
            this.calculateTotals();
        },
        
        loadClient() {
            const select = document.querySelector('select[name="client_id"]');
            const option = select.options[select.selectedIndex];
            
            if (option.value) {
                this.selectedClient = JSON.parse(option.dataset.info);
            } else {
                this.selectedClient = null;
            }
        },
        
        addItem() {
            this.invoice.items.push({
                description: '',
                quantity: 1,
                unit: 'vnt.',
                price: 0,
                discount: 0,
                vatRate: 21
            });
        },
        
        removeItem(index) {
            this.invoice.items.splice(index, 1);
            this.calculateTotals();
        },
        
        getItemTotal(item) {
            const subtotal = item.quantity * item.price * (1 - item.discount / 100);
            const vat = subtotal * (item.vatRate / 100);
            return subtotal + vat;
        },
        
        calculateTotals() {
            let subtotal = 0;
            let vat = 0;
            
            this.invoice.items.forEach(item => {
                const itemSubtotal = item.quantity * item.price * (1 - item.discount / 100);
                const itemVat = itemSubtotal * (item.vatRate / 100);
                
                subtotal += itemSubtotal;
                vat += itemVat;
            });
            
            this.totals = {
                subtotal: subtotal,
                vat: vat,
                total: subtotal + vat
            };
        },
        
        formatCurrency(amount) {
            return '€' + amount.toFixed(2);
        },
        
        submitForm(event) {
            // Form will be submitted normally
            event.target.submit();
        }
    }
}
</script>

<?php include 'views/layouts/footer.php'; ?>
```

## 🔐 Authentication Implementation

```php
<!-- views/auth/login.php -->
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prisijungimas - Sąskaitos Pro</title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Sąskaitos Pro</h1>
                <p>Prisijunkite prie savo paskyros</p>
            </div>
            
            <?php if (!empty($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?= $_SESSION['error'] ?>
                    <?php unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="/login" x-data="{ showPassword: false }">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <div class="form-group">
                    <label class="form-label">El. paštas</label>
                    <input type="email" name="email" class="form-input" 
                           placeholder="jusu@email.lt" required autofocus>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Slaptažodis</label>
                    <div class="input-group">
                        <input :type="showPassword ? 'text' : 'password'" 
                               name="password" class="form-input" 
                               placeholder="••••••••" required>
                        <button type="button" class="btn-icon" @click="showPassword = !showPassword">
                            <svg class="icon" x-show="!showPassword"><!-- Eye icon --></svg>
                            <svg class="icon" x-show="showPassword"><!-- Eye off icon --></svg>
                        </button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="checkbox">
                        <input type="checkbox" name="remember">
                        <span>Prisiminti mane</span>
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    Prisijungti
                </button>
                
                <div class="auth-links">
                    <a href="/forgot-password">Pamiršote slaptažodį?</a>
                    <a href="/register">Registruotis</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
```

## 📱 Mobile-Responsive CSS

```css
/* Mobile-first responsive design */
.container {
    width: 100%;
    padding: 0 1rem;
    margin: 0 auto;
}

@media (min-width: 640px) {
    .container {
        max-width: 640px;
    }
}

@media (min-width: 768px) {
    .container {
        max-width: 768px;
        padding: 0 1.5rem;
    }
}

@media (min-width: 1024px) {
    .container {
        max-width: 1024px;
    }
}

@media (min-width: 1280px) {
    .container {
        max-width: 1280px;
    }
}

/* Responsive grid system */
.row {
    display: flex;
    flex-wrap: wrap;
    margin: -0.75rem;
}

.col {
    flex: 1 0 0%;
    padding: 0.75rem;
}

.col-auto {
    flex: 0 0 auto;
    padding: 0.75rem;
}

@media (min-width: 640px) {
    .col-sm-6 { flex: 0 0 50%; max-width: 50%; }
    .col-sm-4 { flex: 0 0 33.333333%; max-width: 33.333333%; }
    .col-sm-3 { flex: 0 0 25%; max-width: 25%; }
}

@media (min-width: 768px) {
    .col-md-6 { flex: 0 0 50%; max-width: 50%; }
    .col-md-4 { flex: 0 0 33.333333%; max-width: 33.333333%; }
    .col-md-3 { flex: 0 0 25%; max-width: 25%; }
}

@media (min-width: 1024px) {
    .col-lg-6 { flex: 0 0 50%; max-width: 50%; }
    .col-lg-4 { flex: 0 0 33.333333%; max-width: 33.333333%; }
    .col-lg-3 { flex: 0 0 25%; max-width: 25%; }
}

/* Responsive tables */
@media (max-width: 767px) {
    .table-responsive table {
        font-size: 0.875rem;
    }
    
    .table-responsive th,
    .table-responsive td {
        padding: 0.5rem;
    }
    
    /* Stack table cells on very small screens */
    @media (max-width: 480px) {
        .table-stacked thead {
            display: none;
        }
        
        .table-stacked tr {
            display: block;
            margin-bottom: 1rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 0.75rem;
        }
        
        .table-stacked td {
            display: block;
            text-align: right;
            border: none;
            padding: 0.25rem 0;
        }
        
        .table-stacked td::before {
            content: attr(data-label);
            float: left;
            font-weight: 600;
            color: var(--text-secondary);
        }
    }
}

/* Responsive navigation */
@media (max-width: 767px) {
    .sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s ease;
    }
    
    .sidebar.open {
        transform: translateX(0);
    }
    
    .main-content {
        margin-left: 0;
    }
    
    .mobile-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem;
        background: var(--card-bg);
        border-bottom: 1px solid var(--border-color);
    }
    
    .menu-toggle {
        background: none;
        border: none;
        padding: 0.5rem;
        cursor: pointer;
    }
}

@media (min-width: 768px) {
    .mobile-header {
        display: none;
    }
    
    .sidebar {
        transform: translateX(0);
    }
}
```