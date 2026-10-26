# iSpent

### Spend smarter, live better.

iSpent is a lightweight, PHP-based personal expense tracker for recording money spent and money received, setting a monthly budget, and reviewing recent financial activity. It stores entries in local CSV and DAT files, so it can run without a database server.

## Features

- **Password-protected entry point** using a PHP session.
- **Expense and income tracking** with amount, category, description, and date.
- **Monthly budget setting** and an available-balance calculation.
- **Dashboard summary** showing monthly budget, spending, money received, and remaining balance.
- **Entry history** with a desktop table and mobile-friendly cards.
- **Category-based filtering** for reviewing transactions.
- **Three-month summary** showing spending, received amounts, and net totals.
- **Responsive interface** built with HTML and CSS.
- **Success and error feedback** after actions.

## Tech stack

- PHP for application logic and server-rendered pages
- HTML and CSS for the interface
- JavaScript for showing and hiding dashboard sections
- CSV (`data.csv`) for transaction records
- DAT (`data.dat`) for the monthly budget

No Composer packages or database server are required by the current implementation.

## Project files

```text
iSpent/
├── index.php       # Main application, login, dashboard, forms, reports
├── logo.png        # App logo / favicon
├── data.csv        # Transaction records; created or initialized by the app
├── data.dat        # Stored monthly budget
└── README.md       # Startup guide and project documentation
```

The supplied files use `data(1).csv`, `data(1).dat`, `index(3).php`, and `logo(2).png` as upload names. Rename them to the names shown above when assembling the project folder.

## Requirements

- PHP 7.4+ recommended (PHP 8.x preferred)
- A modern browser
- Permission for PHP to write to the project directory so it can update `data.csv` and `data.dat`

The application uses PHP sessions, file operations, and standard extensions available in typical PHP installations.

## Run locally

### 1. Install PHP

Check whether PHP is installed:

```bash
php -v
```

If the command is not found, install PHP using your operating system's package manager or the official PHP downloads page.

### 2. Prepare the project directory

Put the files in one folder and rename them as follows:

- `index(3).php` → `index.php`
- `logo(2).png` → `logo.png`
- `data(1).csv` → `data.csv`
- `data(1).dat` → `data.dat`

Keep a backup of any existing transaction data before replacing files. The supplied CSV contains only its header, and the supplied DAT file contains a numeric budget value.

### 3. Start the development server

Open a terminal in the project directory and run:

```bash
php -S 127.0.0.1:8000
```

Then open:

<http://127.0.0.1:8000>

This is PHP's built-in development server. Do not expose it directly to the public internet.

### 4. Log in

Use the password configured in the `PASSWORD` constant near the beginning of `index.php`. The current source contains a hard-coded password, so change it before sharing or deploying the project. Do not commit real passwords to a public repository.

### 5. Try the main workflow

1. Log in.
2. Open **Set Monthly Budget** and enter a budget.
3. Open **Add New Entry**.
4. Choose **Spent** or **Received**, enter an amount and category, and optionally add a description.
5. Open **View All Entries** to review the saved transaction.
6. Open **Monthly Report** to see the recent three-month summary.

## How it works

### Data storage

- `data.csv` stores each transaction as a row with these columns: `ID`, `Type`, `Amount`, `Category`, `Description`, and `Date`.
- `data.dat` stores the monthly budget as a number.
- The app calculates the available balance as:

```text
Available balance = Monthly budget + current-month received - current-month spent
```

Transactions are appended to the CSV file. The application does not use a relational database.

### Main code areas

- **Authentication:** checks a password and records login state in a PHP session.
- **Transaction handling:** validates the available budget for spending entries, then appends the transaction.
- **Budget handling:** saves the configured monthly budget in `data.dat`.
- **Reporting:** aggregates current-month spending and receipts and calculates recent monthly net totals.
- **Presentation:** PHP templates render the dashboard, transaction tables/cards, and report sections.

## Important limitations and security notes

This is a small local/personal project, not a production-ready financial service.

- **Hard-coded password:** move secrets out of source code and use a secure password hash and `password_verify()`.
- **No user accounts:** all users share the same local data files.
- **Flat-file storage:** CSV/DAT storage is simple but can be vulnerable to write conflicts and data corruption with concurrent requests.
- **Input validation:** add server-side checks for positive amounts, allowed transaction types/categories, and valid budget values.
- **Output escaping:** escape all user-controlled values at output time with `htmlspecialchars()` to reduce cross-site scripting risk. Some output contexts in the current code need stronger escaping.
- **CSRF protection:** add CSRF tokens to POST forms.
- **Session hardening:** configure secure session cookie settings and regenerate the session ID after login.
- **File exposure:** ensure data files cannot be downloaded directly from a public web server, or store them outside the web root.
- **Date/report logic:** test month boundaries and the date parsing logic carefully.
- **Budget semantics:** the current code stores one budget value and calculates the balance using current-month income and expenses. Confirm whether that matches the intended budgeting rules.

Before publishing the project, rotate the hard-coded password, add validation and CSRF protection, and configure the server so the data files are not publicly accessible.

## Troubleshooting

**The page downloads PHP instead of running it**

- The server is not executing PHP. Start the PHP built-in server from the project directory or configure a PHP-capable web server.

**The app cannot save entries or budget changes**

- Check that the PHP process has write permission for `data.csv` and `data.dat`.
- Check the PHP error log for file-operation errors.

**The logo does not appear**

- Confirm the image is named `logo.png` and is in the same directory as `index.php`.

**The dashboard shows zero or unexpected totals**

- Check that `data.csv` has the expected six-column structure and dates in `DD-MM-YYYY` format.
- Check that `data.dat` contains a valid numeric budget.

## Future improvements

- Use SQLite or MySQL for more reliable data persistence.
- Add transaction editing and deletion.
- Add CSV export and backup/restore.
- Add stronger authentication and multiple-user support.
- Add charts, search, date-range filters, and category totals.
- Add automated tests for calculations and month transitions.
