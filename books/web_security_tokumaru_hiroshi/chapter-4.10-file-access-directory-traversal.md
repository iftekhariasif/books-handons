# Chapter 4.10: File Access / Directory Traversal

Web applications use files in many ways. This section covers vulnerabilities related to file handling, specifically unauthorized file access (directory traversal) and unintended file disclosure.

---

## 4.10.1 Directory Traversal

### Overview

- When an application allows specifying filenames from external parameters (e.g., query strings), and uses those to reference server-side files or templates, an attacker can manipulate the parameter to access files outside the intended directory
- This is called **directory traversal** (also known as **path traversal**)

### Impact

- **Viewing files on the web server** -- leaking sensitive information
- **Modifying or deleting web server files** -- defacement, writing malicious content
- **Overwriting web content** -- injecting false information
- **Writing to scripts or config files on the server** -- causing server malfunction or stopping the server
- **Executing arbitrary server-side scripts** -- full server compromise

---

### Countermeasures (Summary)

| Approach | Description |
|----------|-------------|
| Avoid external filename specification | Do not allow users to specify filenames directly |
| Ensure no directory traversal characters | Strip or reject `../`, `..\\`, etc. |
| Restrict filenames to alphanumeric only | Eliminate special characters entirely |

---

### Attack Method and Impact

#### Example: Vulnerable PHP Script

A sample script (`4a-001.php`) allows specifying a template file via the `template` query parameter:

```php
<?php
define('TMPLDIR', '/var/www/html/4a/tmpl/');
$tmpl = filter_input(INPUT_GET, 'template');
?>
<body>
<?php readfile(TMPLDIR . $tmpl . '.html'); ?>
Menu (omitted)
</body>
```

**Normal usage:**

```
http://example.jp/4a/4a-001.php?template=spring
```

This resolves to: `/var/www/html/4a/tmpl/spring.html`

**Attack URL:**

```
http://example.jp/4a/4a-001.php?template=../../../../etc/hosts%00
```

- The `../` sequences traverse up the directory tree
- The null byte `%00` truncates the `.html` extension
- The resolved path after normalization becomes: `/etc/hosts`
- This exposes the Linux system configuration file in the browser

---

### Column: Information Leaks from Script Sources

- Directory traversal to access files on a web server typically requires knowing the filename
- However, `.htaccess` and similar well-known filenames are easy to guess
- Attackers can also use directory traversal via error messages to discover script source files
- While source code viewed in the browser typically shows only the output (PHP is server-processed), reading the raw file via traversal bypasses execution and reveals source code

---

### Root Cause

Directory traversal vulnerabilities arise when **all three** of the following conditions are met:

1. **Filenames can be specified from external input**
2. **Absolute paths or relative paths containing directory traversal sequences** (`../`) can be used to specify directories
3. **The constructed filename is not validated** against access permissions for the intended scope

> Even if a developer considers "allowing directory specification" unnecessary, failing to explicitly prevent `../` in filenames is enough to create the vulnerability.

---

### Countermeasures

#### 1. Avoid External Filename Specification

- The most reliable approach: do not let users specify filenames at all
- Use fixed filenames, session variables, or indirect references (e.g., numeric IDs mapped to files)

#### 2. Ensure Filenames Do Not Contain Directory Traversal Characters

- Strip directory components using functions like PHP's `basename()`
- `basename()` extracts only the filename portion, discarding any path components

```php
<?php
define('TMPLDIR', '/var/www/html/4a/tmpl/');
$tmpl = basename(filter_input(INPUT_GET, 'template'));
?>
<body>
<?php readfile(TMPLDIR . $tmpl . '.html'); ?>
</body>
```

- `basename('../../../../etc/hosts')` returns just `hosts`, neutralizing the attack

##### Column: basename() and Null Bytes

- PHP's `basename()` does not strip null bytes in older versions
- Even with `basename()`, the null byte could truncate the `.html` extension
- On Windows and Unix, the null byte (`%00`) can act as a string terminator, allowing the attacker to control the exact filename accessed
- Null byte issues were fixed in PHP 5.3.4 / PHP 7.2.4+

| Character | Hex Code |
|-----------|----------|
| a | 61 |
| . | 2e |
| p | 70 |
| h | 68 |
| % | 70 |
| 0 | 2e |
| 0 | 74 |
| NUL (0x00) | 78, 74 |

#### 3. Restrict Filenames to Alphanumeric Characters Only

- If filenames are restricted to alphanumeric characters, directory traversal attacks become impossible (no `/`, `\`, `.` characters allowed)
- Use regex validation:

```php
<?php
define('TMPLDIR', '/var/www/html/4a/tmpl/');
$tmpl = filter_input(INPUT_GET, 'template');
if (preg_match('/\A[a-z0-9]+\z/ui', $tmpl) !== 1) {
    die('template must be alphanumeric');
}
?>
<body>
<?php readfile(TMPLDIR . $tmpl . '.html'); ?>
</body>
```

- `preg_match` ensures `$tmpl` contains only alphanumeric characters
- Note: Do not use `ereg()` as it is null-byte unsafe (binary-unsafe)

---

## 4.10.2 Unintended File Disclosure (Directory Listing)

### Overview

- Files placed in the web server's public directory can sometimes be browsed when a URL points to a directory rather than a specific file
- The web server may display a **directory listing** -- showing all files in that directory

### Impact

- **Leaking sensitive information** -- exposing data files, configuration files, and user data

### Example

Accessing a directory URL:

```
http://example.jp/4a/data/
```

Displays a directory listing (Apache's "Index of /4a/data") showing:

| File | Last Modified | Size |
|------|--------------|------|
| company.txt | 2017-08-23 15:38 | 52 |
| users.txt | 2017-08-23 15:38 | 114 |

- In this example, `users.txt` contained user information (names, emails, phone numbers)
- Many personal data breach incidents in the 2000s were caused by this pattern

---

### Root Cause

Unintended file disclosure occurs when:

- **Files are placed in a public directory**
- **The URL to the files can be known** (guessable or discoverable)
- **Access restrictions are not configured**

Discovery vectors:

- Directory listing is enabled
- Filenames are guessable (e.g., `user.csv`, `data.txt`)
- Error messages or other pages reveal file paths
- External sites link to the files
- Search engines index the listings

---

### Countermeasures

1. **Do not place non-public files in the public directory**
2. **When designing the application, decide on safe file storage locations**
3. **If using a rental server, verify that non-public directories are available**
4. **Disable directory listing** as a precaution

#### Apache Configuration to Disable Directory Listing

In `httpd.conf`:

```apache
<Directory /path>
  Options -Indexes
  # other options
</Directory>
```

For rental servers where `httpd.conf` is not accessible, use `.htaccess`:

```
Options -Indexes
```

> Note: Check with your hosting provider whether `.htaccess`-based configuration changes are supported.

---

### Reference: Hiding Specific Files in Apache HTTP Server

- Even after moving non-public files out of the public directory, existing websites may still have the issue
- File migration may introduce broken links
- Apache HTTP Server's `.htaccess` can be used to deny access to specific file patterns

Example `.htaccess` to deny access to all `.txt` files:

```apache
<Files "*.txt">
  deny from all
</Files>
```

- File-level access control via `httpd.conf` or `.htaccess` can help, but be cautious:
  - Configuration errors can accidentally break access
  - During server migration, access restrictions may be lost
  - Historical incidents show that files previously restricted became exposed after migrations

---

## Key Takeaways

- **Directory traversal** is a high-impact vulnerability; always validate and sanitize file paths
- The best defense is to **not allow external filename specification**
- If filenames must be user-specified, use `basename()` or restrict to alphanumeric characters
- **Never place sensitive files in the web server's public directory**
- **Always disable directory listing** as a defense-in-depth measure
- Design your file storage at the architecture phase, not as an afterthought
