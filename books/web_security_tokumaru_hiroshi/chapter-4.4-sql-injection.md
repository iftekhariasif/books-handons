# Chapter 4.4 -- SQL Injection

> Source: "Anzen na Web Application no Tsukurikata" (Safe Web Application Development) by Tokumaru Hiroshi, Section 4.4

---

## Overview

- **User involvement required:** Not required (attacker can exploit directly)
- **Countermeasure:** Use placeholders (bind variables) when calling SQL

---

## Attack Techniques and Impact

### Sample Vulnerable Application

A PHP script that searches a MySQL database for book information. The script constructs SQL queries by directly concatenating user input.

**Vulnerable code pattern** (`44/44-001.php`):

```php
$author = $_GET['author'];
$db = new PDO('mysql:host=127.0.0.1;dbname=wasbook', 'root', 'wasbook');
$db->query('Set names utf8');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$sql = "SELECT * FROM books WHERE author ='$author' ORDER BY id";
$ps = $db->query($sql);
```

**Normal usage:**
```
http://example.jp/44/44-001.php?author=Shakespeare
```

---

### Attack 1: Information Leakage via Error Messages

**Attack URL:**
```
http://example.jp/44/44-001.php?author='+AND+EXTRACTVALUE(0,(SELECT+CONCAT('$',id,':',pwd)+FROM+users+LIMIT+0,1))+%23
```

- The error message reveals user IDs and passwords (e.g., `$yamada:pass1`)
- The underlying query used:
  ```sql
  (SELECT CONCAT('$',id,':',pwd) FROM users LIMIT 0,1)
  ```
- This extracts data from the `users` table by exploiting `EXTRACTVALUE` to force an XPATH error that includes the query result

---

### Attack 2: Information Leakage via UNION SELECT

**Attack URL:**
```
http://example.jp/44/44-001.php?author='+UNION+SELECT+id,pwd,name,addr,NULL,NULL,NULL+FROM+users--
```

- `UNION SELECT` combines results of two SQL queries
- Allows extraction of arbitrary data (user IDs, passwords, names, addresses) from any table
- A single attack can leak large volumes of data

---

### Attack 3: Authentication Bypass via SQL Injection

Exploiting a login form that constructs SQL like:

```sql
SELECT * FROM users WHERE id ='$id' AND PWD = '$pwd';
```

**Attack payload (password field):**
```
' or 'a'='a
```

**Resulting SQL:**
```sql
SELECT * FROM users WHERE id = 'yamada' and pwd = '' OR 'a'='a'
```

- The `WHERE` clause always evaluates to true
- Attacker logs in without knowing the password

---

### Attack 4: Data Tampering

**Attack URL:**
```
http://example.jp/44/44-001.php?author=';UPDATE+books+SET+TITLE%3D'<i>cracked</i>'+WHERE+id%3d'1001'--
```

- Modifies database records (e.g., changes a book title to "cracked" with italic HTML)
- Demonstrates that SQL injection can alter data, not just read it

---

### Attack 5: Other Attacks

Depending on the database engine, SQL injection can also enable:

| Attack Type | Description |
|---|---|
| **OS command execution** | Execute system commands on the DB server |
| **File read** | Read arbitrary files from the server filesystem |
| **File write** | Write arbitrary files to the server |
| **HTTP requests to other servers** | Use the DB server as a proxy to attack internal systems |

**Example -- Reading `/etc/passwd` via `LOAD DATA INFILE` (MySQL):**

```
http://example.jp/44/44-001.php?author=';LOAD+DATA+INFILE+'/etc/passwd'+INTO+TABLE+books+(title)--
```

```sql
LOAD DATA INFILE '/etc/passwd' INTO TABLE books (title)
```

- Reads `/etc/passwd` and inserts its contents into the `books` table
- Requires MySQL's `FILE` privilege and direct connection to the database

---

### Discovering Table and Column Names

Attackers can query the database's metadata using `INFORMATION_SCHEMA`:

```
http://example.jp/44/44-001.php?author='+UNION+SELECT+table_name,column_name,data_type,NULL,NULL,NULL,NULL+FROM+information_schema.columns+ORDER+BY+1--
```

- Returns all table names, column names, and data types in the database

---

## Root Cause of SQL Injection

SQL injection occurs when a developer's intended **literal** (data value) is altered so that SQL syntax is modified.

### The String Literal Problem

- SQL string literals are enclosed in single quotes
- Example: `author='O'Reilly'` -- the apostrophe in "O'Reilly" prematurely terminates the string literal
- The remaining text (`Reilly'`) is interpreted as SQL syntax, causing errors or injection

**Key visual concept:**
- A "safe" literal stays within its quotes and does not change SQL structure
- A "dangerous" input breaks out of the literal boundary, altering SQL meaning

### Numeric Literal Injection

- Numeric parameters (e.g., `WHERE age < $age`) are not quoted
- Injecting `1;DELETE FROM employees` results in:
  ```sql
  SELECT * FROM employees WHERE age < 1;DELETE FROM employees
  ```
- The semicolon terminates the first statement and executes a destructive second one

---

## Countermeasures

### Primary Defense: Placeholders (Bind Variables)

Replace direct string concatenation with parameterized queries:

```sql
SELECT * FROM books WHERE author = ? ORDER BY id
```

- The `?` is a placeholder for variable parameters
- The placeholder ensures user input is always treated as data, never as SQL syntax

**Secure PHP code** (`44/44-004.php`):

```php
$author = $_GET['author'];
$opt = array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,
    PDO::ATTR_EMULATE_PREPARES => false
);
$db = new PDO('mysql:host=127.0.0.1;dbname=wasbook;charset=utf8', 'root', 'wasbook', $opt);
$sql = 'SELECT * FROM books WHERE author = ? ORDER BY id';
$ps = $db->prepare($sql);
$ps->bindValue(1, $author, PDO::PARAM_STR);
$ps->execute();
```

### PDO Options (Important Settings)

| Option | Meaning | Recommended Value |
|---|---|---|
| `PDO::ATTR_ERRMODE` | Error mode | `PDO::ERRMODE_EXCEPTION` (throw exceptions) |
| `PDO::MYSQL_ATTR_MULTI_STATEMENTS` | Allow multiple SQL statements | `false` (disable) |
| `PDO::ATTR_EMULATE_PREPARES` | Use emulated prepared statements | `false` (use native/static) |

- **`ERRMODE_EXCEPTION`**: Ensures errors are thrown as exceptions so they can be handled properly, rather than silently ignored
- **`MYSQL_ATTR_MULTI_STATEMENTS = false`**: Prevents stacked queries (e.g., `; DELETE FROM ...`), reducing attack surface. Note: data tampering and file read attacks are blocked, but information leakage via error-based or UNION-based techniques may still be possible
- **`ATTR_EMULATE_PREPARES = false`**: Forces use of native (static) prepared statements for stronger security

---

### Static vs. Dynamic Placeholders

| Type | How It Works | Security |
|---|---|---|
| **Static (native)** | SQL is sent to the DB engine with `?` markers; values are bound separately. The DB compiles the SQL structure first, then substitutes values. | Strongest -- SQL structure cannot be altered by input |
| **Dynamic (emulated)** | The library replaces `?` with escaped values in the SQL string before sending to the DB. | Good, but relies on correct escaping by the library |

- Static placeholders are preferred because the SQL structure is fixed at the database engine level
- Dynamic placeholders can have edge-case vulnerabilities if the library's escaping is flawed (e.g., character encoding mismatches -- see JVN#59748723)

---

### LIKE Clauses and Wildcard Escaping

LIKE queries use special wildcard characters that need separate escaping:

| Character | Meaning |
|---|---|
| `_` | Matches any single character |
| `%` | Matches zero or more characters |

**Wildcard characters that need escaping per database:**

| Database | Escape Characters | Notes |
|---|---|---|
| MySQL | `%`, `_` | |
| PostgreSQL | `%`, `_` | |
| Oracle | `%`, `_` | |
| MS SQL Server | `%`, `_`, `[` | |
| IBM DB2 | `%`, `_` | |

**PHP escaping function for LIKE wildcards:**

```php
function escape_wildcard($s) {
    return mb_ereg_replace('([_%#])', '#\\1', $s);
}
```

**Usage with `ESCAPE` clause:**
```sql
WHERE name LIKE '%#%%' ESCAPE '#'
```

- Wildcard escaping is not directly related to SQL injection prevention but is necessary for correct LIKE behavior

---

### Using Placeholders with Dynamic Search Conditions

When search conditions vary based on user input, build the SQL dynamically but still use placeholders:

```php
$conditions = array();
$ph_type = array();
$ph_value = array();

// Build SQL base
$sql = 'SELECT id, title, author, publisher, date, price FROM books';

// Conditionally add LIKE clause
if (!empty($title)) {
    $conditions[] = "title LIKE ? ESCAPE '#'";
    $ph_type[] = PDO::PARAM_STR;
    $ph_value[] = '%' . escape_wildcard($title) . '%';
}

// Conditionally add price comparison
if (!empty($price)) {
    $conditions[] = 'price <= ?';
    $ph_type[] = PDO::PARAM_INT;
    $ph_value[] = $price;
}

// Append WHERE clause if conditions exist
if (count($conditions) > 0) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}

$sth = $db->prepare($sql);
for ($i = 0; $i < count($conditions); $i++) {
    $sth->bindValue($i + 1, $ph_value[$i], $ph_type[$i]);
}
$sth->execute();
```

---

### Sorting by Dynamic Columns

When allowing user-selected sort columns via `ORDER BY`:

```php
$sort_columns = array('id', 'author', 'title', 'price');
$sort_key = $_GET['sort'];
if (array_search($sort_key, $sort_columns) !== false) {
    $sql .= ' ORDER BY ' . $sort_key;
}
```

- **Never** insert user input directly into `ORDER BY`
- Validate against a whitelist of allowed column names
- Semicolons or additional SQL (like `UPDATE`) could be appended after `ORDER BY`

---

## Defense-in-Depth (Secondary Measures)

### 1. Suppress Detailed Error Messages

- Error messages can reveal database structure, query details, and data
- In PHP, set in `php.ini`:
  ```ini
  display_errors = off
  ```

### 2. Input Validation

- Validate input according to application requirements
- Example: if a field should only accept digits, enforce that constraint
- This may incidentally prevent some SQL injection, but is not sufficient on its own

### 3. Database Privilege Restriction

- Follow the principle of least privilege
- For read-only applications: grant only `SELECT` on necessary tables
- Restrict `INSERT`, `UPDATE`, `DELETE` to only the tables that need them
- Avoid using MySQL's `FILE` privilege to prevent `LOAD DATA INFILE` attacks
- Even with SQL injection, limited privileges reduce the blast radius

---

## When Placeholders Are Not Available

If placeholders cannot be used (rare), follow these rules to construct SQL safely:

1. **Escape special characters** within string literals (e.g., single quotes)
2. **Ensure numeric literals** contain only numeric values (cast/validate)
3. The goal is to ensure that literals are correctly formed so they cannot break out of their boundaries

---

## Summary (Key Takeaways)

- SQL injection allows attackers to **read, modify, and delete** arbitrary data in the database, and potentially **execute OS commands** or **read/write server files**
- The root cause is constructing SQL by string concatenation with unsanitized user input
- **Always use static (native) placeholders** as the primary defense
- Configure PDO with:
  - `ERRMODE_EXCEPTION` for proper error handling
  - `MULTI_STATEMENTS = false` to block stacked queries
  - `EMULATE_PREPARES = false` to use native prepared statements
- Apply defense-in-depth: suppress error messages, validate input, restrict DB privileges
- For dynamic queries (variable WHERE clauses, ORDER BY), still use placeholders and whitelist validation

---

## Reference: Secure Database Connection Methods

### Perl + MySQL
```perl
my $db = DBI->connect('DBI:mysql:books:localhost;
    mysql_server_prepare=1;mysql_enable_utf8=1',
    'username', 'password')
    || die $DBI::errstr;
```

### PHP 5.3.5 and Earlier (Safe Connection)
```php
$dbh = new PDO('mysql:host=localhost;dbname=wasbook',
    'username', 'password', array(
    PDO::MYSQL_ATTR_READ_DEFAULT_FILE => '/etc/mysql/my.cnf',
    PDO::MYSQL_ATTR_READ_DEFAULT_GROUP => 'client',
    PDO::ATTR_EMULATE_PREPARES => false,
));
```

Set in `/etc/mysql/my.cnf`:
```ini
[client]
default-character-set=utf8
```

### Java + MySQL
- Uses JDBC driver (`MySQL Connector/J`)
- Default is static placeholders (safe by default)
