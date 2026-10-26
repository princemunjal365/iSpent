<?php
session_start();

define('PASSWORD', 'YOUR_PASSWORD');

if (!isset($_SESSION['loggedin'])) {
    if (isset($_POST['password'])) {
        if ($_POST['password'] === PASSWORD) {
            $_SESSION['loggedin'] = true;
        } else {
            $error = "Invalid password!";
        }
    }
    
    if (!isset($_SESSION['loggedin'])) {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>iSpent - Login</title>
            <link rel="icon" type="image/x-icon" href="logo.png">
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                }
                
                body {
                    background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
                    height: 100vh;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    padding: 20px;
                }
                
                .login-container {
                    background: white;
                    border-radius: 15px;
                    padding: 40px;
                    width: 100%;
                    max-width: 450px;
                    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
                }
                
                .logo {
                    text-align: center;
                    margin-bottom: 30px;
                }
                
                .logo h1 {
                    font-size: 3rem;
                    background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
                    -webkit-background-clip: text;
                    -webkit-text-fill-color: transparent;
                    font-weight: bold;
                    margin-bottom: 10px;
                }
                
                .form-group {
                    margin-bottom: 25px;
                }
                
                label {
                    display: block;
                    margin-bottom: 10px;
                    font-weight: 600;
                    color: #444;
                }
                
                input {
                    width: 100%;
                    padding: 15px;
                    border: 2px solid #ddd;
                    border-radius: 8px;
                    font-size: 1rem;
                }
                
                input:focus {
                    border-color: #6a11cb;
                    outline: none;
                }
                
                button.login-btn {
                    background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
                    color: white;
                    border: none;
                    padding: 15px;
                    border-radius: 8px;
                    cursor: pointer;
                    font-size: 1.1rem;
                    font-weight: bold;
                    width: 100%;
                    transition: transform 0.3s ease;
                }
                
                button.login-btn:hover {
                    transform: translateY(-3px);
                }
                
                .error {
                    background-color: #ffebee;
                    color: #d32f2f;
                    padding: 12px;
                    border-radius: 8px;
                    margin-bottom: 20px;
                    text-align: center;
                    border-left: 4px solid #d32f2f;
                }
            </style>
        </head>
        <body>
            <div class="login-container">
                <div class="logo">
                    <h1>iSpent</h1>
                </div>
                
                <?php if (isset($error)): ?>
                    <div class="error">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <button type="submit" class="login-btn">Login to iSpent</button>
                </form>
            </div>
        </body>
        </html>
        <?php
        exit();
    }
}

define('DATA_CSV', 'data.csv');
define('DATA_DAT', 'data.dat');

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_expense':
                addNewExpense();
                break;
            case 'set_budget':
                setMonthlyBudget();
                break;
        }
    }
}

function getPreviousData() {
    $expense_id = [];
    $spent_amount = [];
    $received_amount = [];
    $current_month = date('m');
    $current_year = date('Y');
    
    if (file_exists(DATA_CSV)) {
        $file = fopen(DATA_CSV, 'r');
        $initial = true;
        
        while (($data = fgetcsv($file)) !== FALSE) {
            if ($initial) {
                $initial = false;
                continue;
            }
            
            $expense_id[] = intval($data[0]);
            
            if (!empty($data[5])) {
                $date_parts = explode('-', $data[5]);
                if (count($date_parts) >= 3) {
                    $expense_month = $date_parts[1];
                    $expense_year = $date_parts[2];
                    
                    if ($expense_month == $current_month && $expense_year == $current_year) {
                        if ($data[1] === 'spent') {
                            $spent_amount[] = floatval($data[2]);
                        } else {
                            $received_amount[] = floatval($data[2]);
                        }
                    }
                }
            }
        }
        fclose($file);
    }
    
    $last_id = empty($expense_id) ? 0 : end($expense_id);
    $total_spent = array_sum($spent_amount);
    $total_received = array_sum($received_amount);
    
    return [
        'last_id' => $last_id, 
        'monthly_spent' => $total_spent,
        'monthly_received' => $total_received
    ];
}

function addNewExpense() {
    $previous_data = getPreviousData();
    $budget = getMonthlyBudget() + $previous_data['monthly_received'] - $previous_data['monthly_spent'];
    
    $type = $_POST['type'];
    $amount = floatval($_POST['amount']);
    $category = htmlspecialchars($_POST['category']);
    $description = htmlspecialchars($_POST['description']);
    
    if ($type === 'spent' && $budget < $amount) {
        $_SESSION['message'] = 'Failed to add! Expense is out of available budget.';
        $_SESSION['message_type'] = 'error';
    } else {
        $id = $previous_data['last_id'] + 1;
        $date = date('d-m-Y');
        
        $data = [$id, $type, $amount, $category, $description, $date];
        
        $file = fopen(DATA_CSV, 'a');
        if (filesize(DATA_CSV) == 0) {
            fputcsv($file, ['ID', 'Type', 'Amount', 'Category', 'Description', 'Date']);
        }
        fputcsv($file, $data);
        fclose($file);
        
        $_SESSION['message'] = ucfirst($type) . ' entry added successfully!';
        $_SESSION['message_type'] = 'success';
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

function getAllExpenses() {
    $expenses = [];
    
    if (file_exists(DATA_CSV)) {
        $file = fopen(DATA_CSV, 'r');
        $header = fgetcsv($file);
        
        while (($data = fgetcsv($file)) !== FALSE) {
            $expenses[] = $data;
        }
        fclose($file);
    }
    
    return $expenses;
}

function setMonthlyBudget() {
    $budget = floatval($_POST['budget']);
    file_put_contents(DATA_DAT, $budget);
    
    $_SESSION['message'] = 'Monthly budget set successfully!';
    $_SESSION['message_type'] = 'success';
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

function getMonthlyBudget() {
    if (file_exists(DATA_DAT)) {
        return floatval(file_get_contents(DATA_DAT));
    }
    return 0;
}

function getExpensesByCategory($category) {
    $expenses = [];
    
    if (file_exists(DATA_CSV)) {
        $file = fopen(DATA_CSV, 'r');
        $header = fgetcsv($file);
        
        while (($data = fgetcsv($file)) !== FALSE) {
            if (strcasecmp($data[3], $category) === 0) {
                $expenses[] = $data;
            }
        }
        fclose($file);
    }
    
    return $expenses;
}

function getLastThreeMonthsExpenses() {
    $months = [];
    $current_month = date('m');
    $current_year = date('Y');
    
    for ($i = 0; $i < 3; $i++) {
        $month = $current_month - $i;
        $year = $current_year;
        
        if ($month <= 0) {
            $month += 12;
            $year--;
        }
        
        $months[] = [
            'month' => str_pad($month, 2, '0', STR_PAD_LEFT),
            'year' => $year,
            'spent' => 0,
            'received' => 0
        ];
    }
    
    if (file_exists(DATA_CSV)) {
        $file = fopen(DATA_CSV, 'r');
        $header = fgetcsv($file);
        
        while (($data = fgetcsv($file)) !== FALSE) {
            if (!empty($data[5])) {
                $date_parts = explode('-', $data[5]);
                if (count($date_parts) >= 3) {
                    $expense_month = $date_parts[1];
                    $expense_year = $date_parts[2];
                    
                    foreach ($months as &$month_data) {
                        if ($expense_month == $month_data['month'] && $expense_year == $month_data['year']) {
                            if ($data[1] === 'spent') {
                                $month_data['spent'] += floatval($data[2]);
                            } else {
                                $month_data['received'] += floatval($data[2]);
                            }
                            break;
                        }
                    }
                }
            }
        }
        fclose($file);
    }
    
    return $months;
}

$all_expenses = getAllExpenses();
$monthly_budget = getMonthlyBudget();
$previous_data = getPreviousData();
$spent_amount = $previous_data['monthly_spent'];
$received_amount = $previous_data['monthly_received'];
$remaining_budget = $monthly_budget + $received_amount - $spent_amount;
$last_three_months = getLastThreeMonthsExpenses();

$view = isset($_GET['view']) ? $_GET['view'] : 'main';
$category_expenses = [];

if ($view === 'category' && isset($_GET['category'])) {
    $category_expenses = getExpensesByCategory($_GET['category']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="logo.png">
    <title>iSpent - Expense Tracker</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white;
            padding: 20px 0;
            text-align: center;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .logo {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .budget-card {
            border-left: 5px solid #6a11cb;
        }
        
        .spent-card {
            border-left: 5px solid #ff4d4d;
        }
        
        .received-card {
            border-left: 5px solid #00c853;
        }
        
        .remaining-card {
            border-left: 5px solid #ff9800;
        }
        
        .card h3 {
            font-size: 1.2rem;
            margin-bottom: 10px;
            color: #555;
        }
        
        .card .amount {
            font-size: 2rem;
            font-weight: bold;
        }
        
        .menu {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .menu-btn {
            background: white;
            border: none;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            font-size: 1.1rem;
            font-weight: bold;
            color: #444;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .menu-btn:hover {
            background: #6a11cb;
            color: white;
            transform: translateY(-3px);
        }
        
        .content-section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        
        .close-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #999;
        }
        
        .close-btn:hover {
            color: #333;
        }
        
        .content-section h2 {
            margin-bottom: 20px;
            color: #444;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }

        .desktop-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .desktop-table th, .desktop-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            word-wrap: break-word;
        }
        
        .desktop-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #555;
        }
        
        .desktop-table tr:hover {
            background-color: #f5f5f5;
        }

        .mobile-cards {
            display: none;
            margin-top: 15px;
        }
        
        .entry-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #6a11cb;
        }
        
        .entry-card.spent {
            border-left-color: #ff4d4d;
        }
        
        .entry-card.received {
            border-left-color: #00c853;
        }
        
        .card-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .card-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .card-label {
            font-weight: bold;
            color: #666;
            min-width: 80px;
        }
        
        .card-value {
            flex: 1;
            text-align: right;
        }
        
        .amount-spent {
            color: #d32f2f;
            font-weight: bold;
        }
        
        .amount-received {
            color: #388e3c;
            font-weight: bold;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }
        
        button {
            background: #6a11cb;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: bold;
            transition: background 0.3s ease;
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-weight: bold;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .back-btn {
            background: #6c757d;
            margin-bottom: 20px;
        }
        
        .back-btn:hover {
            background: #5a6268;
        }
        
        .monthly-report {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .month-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .month-card h3 {
            font-size: 1.1rem;
            margin-bottom: 10px;
            color: #555;
        }
        
        .month-card .amount {
            font-size: 1.8rem;
            font-weight: bold;
        }
        
        .net-positive {
            color: #388e3c;
        }
        
        .net-negative {
            color: #d32f2f;
        }

        @media (max-width: 768px) {
            .dashboard {
                grid-template-columns: 1fr;
            }
            
            .menu {
                grid-template-columns: 1fr;
            }
            
            .monthly-report {
                grid-template-columns: 1fr;
            }
            
            .desktop-table {
                display: none;
            }
            
            .mobile-cards {
                display: block;
            }
            
            .card .amount {
                font-size: 1.5rem;
            }
            
            .container {
                padding: 15px;
            }
            
            .content-section {
                padding: 20px;
            }
        }
        
        @media (min-width: 769px) {
            .desktop-table {
                display: table;
            }
            
            .mobile-cards {
                display: none;
            }
        }
        
        @media (max-width: 480px) {
            .content-section {
                padding: 15px;
            }
            
            .menu-btn {
                padding: 12px;
                font-size: 1rem;
            }
            
            .card .amount {
                font-size: 1.3rem;
            }
            
            .entry-card {
                padding: 12px;
            }
            
            .card-label {
                min-width: 70px;
                font-size: 0.9rem;
            }
            
            .card-value {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">iSpent</div>
        </header>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="message <?php echo $_SESSION['message_type']; ?>" id="autoMessage">
                <?php echo $_SESSION['message']; ?>
                <?php 
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
                ?>
            </div>
        <?php endif; ?>
        
        <?php if ($view === 'main'): ?>
            <div class="dashboard">
                <div class="card budget-card">
                    <h3>Monthly Budget</h3>
                    <div class="amount">₹<?php echo number_format($monthly_budget, 2); ?></div>
                </div>
                <div class="card spent-card">
                    <h3>Spent This Month</h3>
                    <div class="amount">₹<?php echo number_format($spent_amount, 2); ?></div>
                </div>
                <div class="card received-card">
                    <h3>Received This Month</h3>
                    <div class="amount">₹<?php echo number_format($received_amount, 2); ?></div>
                </div>
                <div class="card remaining-card">
                    <h3>Available Balance</h3>
                    <div class="amount">₹<?php echo number_format($remaining_budget, 2); ?></div>
                </div>
            </div>
            
            <div class="menu">
                <button class="menu-btn" onclick="showSection('add')">Add New Entry</button>
                <button class="menu-btn" onclick="showSection('view')">View All Entries</button>
                <button class="menu-btn" onclick="showSection('report')">Monthly Report</button>
                <button class="menu-btn" onclick="showSection('budget')">Set Monthly Budget</button>
                <button class="menu-btn" onclick="window.location.href='?logout=true'">Logout</button>
            </div>
            
            <div id="add" class="content-section" style="display: none;">
                <button class="close-btn" onclick="hideSection('add')">×</button>
                <h2>Add New Entry</h2>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add_expense">
                    <div class="form-group">
                        <label for="type">Type</label>
                        <select id="type" name="type" required>
                            <option value="spent">Spent</option>
                            <option value="received">Received</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="amount">Amount (₹)</label>
                        <input type="number" step="0.01" id="amount" name="amount" required>
                    </div>
                    <div class="form-group">
                        <label for="category">Category</label>
                        <select id="category" name="category" required>
                            <option value="">Select a category</option>
                            <option value="Food">Food</option>
                            <option value="Transport">Transport</option>
                            <option value="Entertainment">Entertainment</option>
                            <option value="Utilities">Utilities</option>
                            <option value="Shopping">Shopping</option>
                            <option value="Healthcare">Healthcare</option>
                            <option value="Education">Education</option>
                            <option value="Stationary">Stationary</option>
                            <option value="PG">PG</option>
                            <option value="Restaurant">Restaurant</option>
                            <option value="Salary">Salary</option>
                            <option value="Gift">Gift</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="3"></textarea>
                    </div>
                    <button type="submit">Add Entry</button>
                </form>
            </div>
            
            <div id="view" class="content-section" style="display: none;">
                <button class="close-btn" onclick="hideSection('view')">×</button>
                <h2>All Entries</h2>
                <?php if (!empty($all_expenses)): ?>
                    
                    <div class="desktop-table-container">
                        <table class="desktop-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Amount (₹)</th>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_expenses as $expense): ?>
                                    <tr>
                                        <td><?php echo $expense[0]; ?></td>
                                        <td class="<?php echo $expense[1] === 'spent' ? 'amount-spent' : 'amount-received'; ?>">
                                            <?php echo ($expense[1] === 'spent' ? '-' : '+') . '₹' . number_format($expense[2], 2); ?>
                                        </td>
                                        <td><?php echo $expense[3]; ?></td>
                                        <td><?php echo $expense[4]; ?></td>
                                        <td><?php echo $expense[5]; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="mobile-cards">
                        <?php foreach ($all_expenses as $expense): ?>
                            <div class="entry-card <?php echo $expense[1]; ?>">
                                <div class="card-row">
                                    <span class="card-label">ID:</span>
                                    <span class="card-value"><?php echo $expense[0]; ?></span>
                                </div>
                                <div class="card-row">
                                    <span class="card-label">Amount:</span>
                                    <span class="card-value <?php echo $expense[1] === 'spent' ? 'amount-spent' : 'amount-received'; ?>">
                                        <?php echo ($expense[1] === 'spent' ? '-' : '+') . '₹' . number_format($expense[2], 2); ?>
                                    </span>
                                </div>
                                <div class="card-row">
                                    <span class="card-label">Category:</span>
                                    <span class="card-value"><?php echo $expense[3]; ?></span>
                                </div>
                                <div class="card-row">
                                    <span class="card-label">Description:</span>
                                    <span class="card-value"><?php echo $expense[4] ?: 'N/A'; ?></span>
                                </div>
                                <div class="card-row">
                                    <span class="card-label">Date:</span>
                                    <span class="card-value"><?php echo $expense[5]; ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>No entries recorded yet.</p>
                <?php endif; ?>
            </div>
            
            <div id="category" class="content-section" style="display: none;">
                <button class="close-btn" onclick="hideSection('category')">×</button>
                <h2>View Entries by Category</h2>
                <form method="GET" action="">
                    <input type="hidden" name="view" value="category">
                    <div class="form-group">
                        <label for="cat">Category</label>
                        <select id="cat" name="category" required onchange="this.form.submit()">
                            <option value="">Select a category</option>
                            <option value="Food">Food</option>
                            <option value="Transport">Transport</option>
                            <option value="Entertainment">Entertainment</option>
                            <option value="Utilities">Utilities</option>
                            <option value="Shopping">Shopping</option>
                            <option value="Healthcare">Healthcare</option>
                            <option value="Education">Education</option>
                            <option value="Stationary">Stationary</option>
                            <option value="PG">PG</option>
                            <option value="Restaurant">Restaurant</option>
                            <option value="Salary">Salary</option>
                            <option value="Gift">Gift</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </form>
            </div>
            
            <div id="budget" class="content-section" style="display: none;">
                <button class="close-btn" onclick="hideSection('budget')">×</button>
                <h2>Set Monthly Budget</h2>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="set_budget">
                    <div class="form-group">
                        <label for="budget">Monthly Budget (₹)</label>
                        <input type="number" step="0.01" id="budget" name="budget" value="<?php echo $monthly_budget; ?>" required>
                    </div>
                    <button type="submit">Set Budget</button>
                </form>
            </div>
            
            <div id="report" class="content-section" style="display: none;">
                <button class="close-btn" onclick="hideSection('report')">×</button>
                <h2>Monthly Report</h2>
                
                <center><h3 style="margin-top: 20px;">Last Three Months</h3><center>
                    <br>
                <div class="monthly-report">
                    <?php 
                    $month_names = [
                        '01' => 'January', '02' => 'February', '03' => 'March', 
                        '04' => 'April', '05' => 'May', '06' => 'June', 
                        '07' => 'July', '08' => 'August', '09' => 'September', 
                        '10' => 'October', '11' => 'November', '12' => 'December'
                    ];
                    
                    foreach ($last_three_months as $month_data): 
                        $month_name = $month_names[$month_data['month']];
                        $net_amount = $month_data['received'] - $month_data['spent'];
                    ?>
                        <div class="month-card">
                            <h3><?php echo $month_name . ' ' . $month_data['year']; ?></h3>
                            <div class="amount <?php echo $net_amount >= 0 ? 'net-positive' : 'net-negative'; ?>">
                                <?php echo ($net_amount >= 0 ? '+' : '-') . '₹' . number_format(abs($net_amount), 2); ?>
                            <div style="margin-top: 10px; font-size: 0.9rem;">
                                <div style="color: #d32f2f;">Spent: ₹<?php echo number_format($month_data['spent'], 2); ?></div>
                                <div style="color: #388e3c;">Received: ₹<?php echo number_format($month_data['received'], 2); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <h3 style="margin-top: 30px;">Current Month Entries</h3>
                <?php 
                $current_month_entries = [];
                $current_month = date('m');
                $current_year = date('Y');
                
                if (file_exists(DATA_CSV)) {
                    $file = fopen(DATA_CSV, 'r');
                    $header = fgetcsv($file);
                    
                    while (($data = fgetcsv($file)) !== FALSE) {
                        if (!empty($data[5])) {
                            $date_parts = explode('-', $data[5]);
                            if (count($date_parts) >= 3) {
                                $expense_month = $date_parts[1];
                                $expense_year = $date_parts[2];
                                
                                if ($expense_month == $current_month && $expense_year == $current_year) {
                                    $current_month_entries[] = $data;
                                }
                            }
                        }
                    }
                    fclose($file);
                }
                ?>
                
                <?php if (!empty($current_month_entries)): ?>
                    
                    <div class="desktop-table-container">
                        <table class="desktop-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Amount (₹)</th>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($current_month_entries as $entry): ?>
                                    <tr>
                                        <td><?php echo $entry[0]; ?></td>
                                        <td class="<?php echo $entry[1] === 'spent' ? 'amount-spent' : 'amount-received'; ?>">
                                            <?php echo ($entry[1] === 'spent' ? '-' : '+') . '₹' . number_format($entry[2], 2); ?>
                                        </td>
                                        <td><?php echo $entry[3]; ?></td>
                                        <td><?php echo $entry[4]; ?></td>
                                        <td><?php echo $entry[5]; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="mobile-cards">
                        <?php foreach ($current_month_entries as $entry): ?>
                            <div class="entry-card <?php echo $entry[1]; ?>">
                                <div class="card-row">
                                    <span class="card-label">ID:</span>
                                    <span class="card-value"><?php echo $entry[0]; ?></span>
                                </div>
                                <div class="card-row">
                                    <span class="card-label">Amount:</span>
                                    <span class="card-value <?php echo $entry[1] === 'spent' ? 'amount-spent' : 'amount-received'; ?>">
                                        <?php echo ($entry[1] === 'spent' ? '-' : '+') . '₹' . number_format($entry[2], 2); ?>
                                    </span>
                                </div>
                                <div class="card-row">
                                    <span class="card-label">Category:</span>
                                    <span class="card-value"><?php echo $entry[3]; ?></span>
                                </div>
                                <div class="card-row">
                                    <span class="card-label">Description:</span>
                                    <span class="card-value"><?php echo $entry[4] ?: 'N/A'; ?></span>
                                </div>
                                <div class="card-row">
                                    <span class="card-label">Date:</span>
                                    <span class="card-value"><?php echo $entry[5]; ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>No entries recorded for this month yet.</p>
                <?php endif; ?>
            </div>
            
        <?php elseif ($view === 'category' && isset($_GET['category'])): ?>
            <button class="back-btn" onclick="window.location.href='?'">Back to Main</button>
            <div class="content-section">
                <h2>Entries in Category: <?php echo $_GET['category']; ?></h2>
                <?php if (!empty($category_expenses)): ?>
                    
                    <div class="desktop-table-container">
                        <table class="desktop-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Type</th>
                                    <th>Amount (₹)</th>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($category_expenses as $expense): ?>
                                    <tr>
                                        <td><?php echo $expense[0]; ?></td>
                                        <td><?php echo ucfirst($expense[1]); ?></td>
                                        <td class="<?php echo $expense[1] === 'spent' ? 'amount-spent' : 'amount-received'; ?>">
                                            <?php echo ($expense[1] === 'spent' ? '-' : '+') . '₹' . number_format($expense[2], 2); ?>
                                        </td>
                                        <td><?php echo $expense[3]; ?></td>
                                        <td><?php echo $expense[4]; ?></td>
                                        <td><?php echo $expense[5]; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="mobile-cards">
                        <?php foreach ($category_expenses as $expense): ?>
                            <div class="entry-card <?php echo $expense[1]; ?>">
                                <div class="card-row">
                                    <span class="card-label">ID:</span>
                                    <span class="card-value"><?php echo $expense[0]; ?></span>
                                </div>
                                <div class="card-row">
                                    <span class="card-label">Type:</span>
                                    <span class="card-value"><?php echo ucfirst($expense[1]); ?></span>
                                </div>
                                <div class="card-row">
                                    <span class="card-label">Amount:</span>
                                    <span class="card-value <?php echo $expense[1] === 'spent' ? 'amount-spent' : 'amount-received'; ?>">
                                        <?php echo ($expense[1] === 'spent' ? '-' : '+') . '₹' . number_format($expense[2], 2); ?>
                                    </span>
                                </div>
                                <div class="card-row">
                                    <span class="card-label">Category:</span>
                                    <span class="card-value"><?php echo $expense[3]; ?></span>
                                </div>
                                <div class="card-row">
                                    <span class="card-label">Description:</span>
                                    <span class="card-value"><?php echo $expense[4] ?: 'N/A'; ?></span>
                                </div>
                                <div class="card-row">
                                    <span class="card-label">Date:</span>
                                    <span class="card-value"><?php echo $expense[5]; ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>No entries found in this category.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        function showSection(sectionId) {
            document.querySelectorAll('.content-section').forEach(section => {
                section.style.display = 'none';
            });
            
            document.getElementById(sectionId).style.display = 'block';
            
            document.getElementById(sectionId).scrollIntoView({ behavior: 'smooth' });
        }
        
        function hideSection(sectionId) {
            document.getElementById(sectionId).style.display = 'none';
        }
        
        setTimeout(function() {
            const message = document.getElementById('autoMessage');
            if (message) {
                message.style.display = 'none';
            }
        }, 3000);
        
        <?php if ($view === 'category' && isset($_GET['category'])): ?>
            window.addEventListener('load', function() {
                showSection('category');
            });
        <?php endif; ?>
    </script>
</body>
</html>
