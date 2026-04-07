# Chapter 4.14: Eval Injection, Unsafe Deserialization, and XXE

This section covers vulnerabilities that arise from reading structured data: eval injection from dynamically executing code strings, unsafe deserialization from converting serialized data back to objects, and XML External Entity (XXE) attacks from parsing untrusted XML.

---

## 4.14.1 Eval Injection

### Overview

- As an example of structured data, programs sometimes use source code as data (e.g., JSON is a subset of JavaScript source code)
- When a program receives structured data, parses it, and executes it, the function performing this is generally called `eval`
- If external input flows into `eval` without proper checking, arbitrary code can be executed -- this is an **eval injection vulnerability**

### Impact

- Information leakage
- Server tampering
- Unauthorized function execution
- Attacks on other sites (stepping stone)
- Cryptocurrency mining

### Countermeasures Summary

- Do not use `eval` or equivalent functions
- If `eval` must be used, do not include external parameters in its arguments
- Restrict external parameters passed to `eval` to alphanumeric characters

---

## Eval Injection Vulnerability Summary

| Item | Details |
|------|---------|
| **Occurs in** | Pages that use `eval` or similar functions to parse and execute scripts |
| **Affected pages** | All pages can be affected |
| **Impact** | Information leakage, server tampering, unauthorized execution, other site attacks, mining |
| **Impact level** | High |
| **User involvement** | Not required |

---

## Attack Methods and Impact

### Vulnerable Sample Application

**`4e-001.php`** -- Serializes data using `var_export` and sends it via hidden form field:

```php
<?php
  $a = array(1, 2, 3);   // Data to pass
  $ex = var_export($a, true);   // Serialize
  $b64 = base64_encode($ex);    // Base64 encode
?>
<body>
<form action="4e-002.php" method="GET">
  <input type="hidden" name="data"
   value="<?php echo htmlspecialchars($b64); ?>">
  <input type="submit" value="go">
</form>
</body>
```

**`4e-002.php`** -- Receives data, Base64-decodes, and uses `eval` to deserialize:

```php
<?php
  $data = $_GET['data'];
  $str = base64_decode($data);
  eval('$a = ' . $str . ';');
?>
<body>
<?php var_dump($a); ?>
</body>
```

**Execution result (normal):**
```
array (
  0 => 1,
  1 => 2,
  2 => 3,
)
```

### Attack Description

- `4e-002.php` does not check external parameters before passing them to `eval`, allowing arbitrary code injection
- The attacker can append additional statements to the eval expression:

```php
$a = 0; phpinfo();
```

- Base64-encode the attack payload using OWASP ZAP's encoder tool, then submit:

```
http://example.jp/4e/4e-002.php?data=MDsgcGhwaW5mbygpOyA/Pg==
```

- Result: `phpinfo()` is executed, confirming arbitrary code execution

### Root Causes

- **Using `eval` is inherently dangerous** -- it executes arbitrary PHP scripts
- `4e-002.php` uses `eval` without checking external parameters, allowing arbitrary script injection

---

## Countermeasures

### Do Not Use `eval`

- First, consider whether `eval` (or equivalent functions) can be avoided entirely
- For serialization purposes, alternatives to `eval` include:
  - `implode` / `explode` (for simple arrays)
  - `json_encode` / `json_decode` (recommended for most cases)
  - `serialize` / `unserialize` (PHP-specific, has its own risks)

### PHP Functions That Parse and Execute Strings

| Function | Description |
|----------|-------------|
| `create_function()` | Creates an anonymous function from a string |
| `mb_ereg_replace()` | Regex replace with `e` modifier |
| `preg_replace()` | Regex replace with `e` modifier (deprecated) |
| `mb_ereg_replace()` | Same as above with multibyte support |

### PHP Functions That Accept Callback Functions

Functions that accept a callback (function name) as an argument can also be dangerous if the function name is externally specified:

```
call_user_func()    call_user_func_array()    array_map()    array_walk()
array_filter()      usort()                    uksort()
```

### Do Not Include External Parameters in `eval` Arguments

- If `eval` must be used, ensure external input is never included
- In PHP, hidden parameters can easily be set to arbitrary values via session tampering or proxy tools, so they are not safe

### Restrict External Parameters to Alphanumeric Characters

- If external parameters must be passed to `eval`, restrict them to numeric characters only
- Scripts cannot be injected using only alphanumeric/numeric values (no semicolons, commas, parentheses)

### Reference: Perl's eval Block Syntax

- Perl's `eval` comes in two forms: `eval` with a string argument, and `eval` block syntax
- The block syntax does not have eval injection risk because the code inside the block is fixed at compile time:

```perl
eval {
  $c = $a / $b;    # Zero division possible
};
if ($@) {    # Error occurred
  # Error handling
}
```

---

## Summary

- `eval` is a powerful but dangerous function that can execute arbitrary source code
- Avoid `eval` entirely when possible; use `json_encode`/`json_decode` for serialization
- If `eval` must be used, never include external parameters, or restrict them to numeric values

---

## 4.14.2 Unsafe Deserialization

### Overview

- **Serialization** is converting an application's internal data structure to a byte string for storage or transmission
- **Deserialization** is the reverse: converting a serialized byte string back into data
- PHP uses `serialize`/`unserialize`; these are commonly used
- When an application deserializes data from an untrusted source, an attacker can craft malicious serialized data to execute arbitrary code

### Impact

- Information leakage
- Server tampering
- Unauthorized function execution
- Attacks on other sites (stepping stone)
- Cryptocurrency mining

---

## Unsafe Deserialization Vulnerability Summary

| Item | Details |
|------|---------|
| **Occurs in** | Pages that deserialize data from external sources |
| **Affected pages** | All pages can be affected |
| **Impact** | Information leakage, server tampering, unauthorized execution, other site attacks, mining |
| **Impact level** | High |
| **User involvement** | Not required |

---

## Attack Methods and Impact

### Vulnerable Application Description

A web application stores "favorite colors" in a cookie using PHP's `serialize`:

**`4e-010.php`** -- Sets cookie with serialized color array:

```php
<?php
  $colors = array('red', 'green', 'blue');
  setcookie('COLORS', serialize($colors));
  echo 'Cookie set';
?>
```

**Serialized format:**
```
a:3:{i:0;s:3:"red";i:1;s:5:"green";i:2;s:4:"blue";}
```

**`4e-011.php`** -- Reads and deserializes the cookie:

```php
<?php
  require '4e-012.php';
  $colors = unserialize($_COOKIE['COLORS']);
```

### The Logger Class Exploit

A `Logger` class (`4e-012.php`) is included via `require`. It logs messages to a file and writes the buffer to disk in the destructor:

```php
<?php
class Logger {
  const LOGDIR = '/tmp/';
  private $filename = '';    // Log file name
  private $log = '';         // Log buffer

  public function __construct($filename) {
    $this->filename = basename($filename);  // Directory traversal prevention
    $this->log = '';
  }

  public function add($log) {    // Add to log
    $this->log .= $log . "\n";  // Append to buffer
  }

  public function __destruct() {
    $path = self::LOGDIR . $this->filename;  // Build file path
    $fp = fopen($path, 'a');
    // ... file locking and writing ...
    fwrite($fp, $this->log);    // Write log
    fclose($fp);
  }
}
```

### Attack Mechanism

1. The attacker crafts a malicious serialized `Logger` object and sets it as the `COLORS` cookie
2. When `unserialize()` processes this cookie, a `Logger` object is created in memory with attacker-controlled properties
3. The `__destruct()` method is called when the script ends (or the object goes out of scope)
4. Since the attacker controls `$this->filename` and `$this->log`, they can write arbitrary content to arbitrary files

**Attack payload (Logger object):**
```
Logger {
  filename: ../var/www/html/xinfo.php
  log: <?php phpinfo(); ?>
}
```

5. The destructor writes `<?php phpinfo(); ?>` to `/var/www/html/xinfo.php`
6. Accessing `xinfo.php` confirms the PHP script was successfully injected

### Methods Exploitable via Deserialization

| Method | Description |
|--------|-------------|
| `__destruct()` | Called when the object is destroyed (end of script) |
| `__wakeup()` | Called immediately when the object is deserialized |
| `__toString()` | Called when the object is cast to string |

- In Java, `readObject` method of `ObjectInputStream` is called during deserialization

### Conditions for Exploitable Classes

- In PHP, for a class to be exploitable, its class definition must already be loaded (via `require`/`include`) or autoloaded via `spl_autoload_register`
- In Java, any class in the classpath can be targeted -- the attack surface is much larger

### Root Causes

- **Using `serialize`/`unserialize` with external data is dangerous**
- The vulnerability exists because deserialization processes external data, and exploitable class definitions are available

---

## Countermeasures

- **Do not use `serialize`/`unserialize`** for external-facing data
- **Do not include external parameters in deserialization processing**
- If cookies or hidden parameters must store structured data, use **JSON format** (`json_encode`/`json_decode`) instead of serialize
- Ensure cookies and session variables cannot be tampered with using HMAC or similar integrity checks
- Verify that data received is JSON (check `Content-Type`)

**Safe alternative -- using JSON:**

```php
<?php
  $colors = array('red', 'green', 'blue');
  setcookie('COLORS', json_encode($colors));
```

```php
<?php
  require '4e-012.php';
  $colors = json_decode($_COOKIE['COLORS']);
  echo 'Your colors: ';
  foreach ($colors as $color) {
    echo htmlspecialchars($color, ENT_COMPAT, 'UTF-8') . ' ';
  }
  echo 'done';
```

---

## 4.14.3 XML External Entity (XXE) Attacks

### Overview

- XML has a feature called **external entity references** that can load content from external files
- Programs that accept XML from external sources can be exploited to read files on the web server or access internal network resources
- This attack is called **XML External Entity Reference Attack (XXE)**
- The broader vulnerability category is **XML External Entity Vulnerability**

### Impact

- Information leakage
- Attacks on other sites (stepping stone)

---

## XXE Vulnerability Summary

| Item | Details |
|------|---------|
| **Occurs in** | Pages that receive and parse XML from external sources |
| **Affected pages** | All pages can be affected |
| **Impact** | Information leakage, other site attacks |
| **Impact level** | High |
| **User involvement** | Not required |
| **Countermeasure** | Use JSON instead of XML; if XML is needed, disable external entity references |

---

## What Are External Entity References?

- An XML **entity** is declared in the DTD (Document Type Definition) and expanded within the document
- An **external entity** references content from an external file or URL:

```xml
<!DOCTYPE foo [
  <!ENTITY greeting "Hello">
  <!ENTITY external-file SYSTEM "external.txt">
]>
```

- When `external.txt` contains "Hello World", the entity `&external-file;` expands to "Hello World":

```xml
<foo>
  <hello>&greeting;</hello>
  <ext>Hello World</ext>
</foo>
```

- External entities can also reference URLs, making them usable for SSRF-like attacks

---

## Attack Methods and Impact

### Sample Application

**`4e-020.html`** -- HTML form for uploading XML files:

```html
<body>
  Upload XML file:<br>
  <form action="4e-021.php" method="post" enctype="multipart/form-data">
    <input type="file" name="user" />
    <input type="submit"/>
  </form>
</body>
```

**`4e-021.php`** -- Parses uploaded XML and displays personal information:

```php
<?php
  $doc = new DOMDocument();
  $doc->load($_FILES['user']['tmp_name']);
  $name = $doc->getElementsByTagName('name')->item(0)->textContent;
  $addr = $doc->getElementsByTagName('address')->item(0)->textContent;
?>
<body>
  Registered:<br>
  Name: <?php echo htmlspecialchars($name); ?><br>
  Address: <?php echo htmlspecialchars($addr); ?><br>
</body>
```

**Normal XML input (`xxe-00.xml`):**
```xml
<?xml version="1.0" encoding="utf-8" ?>
<user>
  <name>Taro Anzen</name>
  <address>Tokyo, Shinagawa</address>
</user>
```

### File Access via External Entity

**Attack XML (`xxe-01.xml`):**
```xml
<?xml version="1.0" encoding="utf-8" ?>
<!DOCTYPE foo [
  <!ENTITY hosts SYSTEM "/etc/hosts">
]>
<user>
  <name>Taro Anzen</name>
  <address>&hosts;</address>
</user>
```

- Result: The contents of `/etc/hosts` are displayed in the address field

### HTTP Access via URL-Specified Entities

**Attack XML (`xxe-02.xml`):**
```xml
<?xml version="1.0" encoding="utf-8" ?>
<!DOCTYPE foo [
  <!ENTITY schedule SYSTEM "http://internal.example.jp/">
]>
<user>
  <name>Taro Tanaka</name>
  <address>&schedule;</address>
</user>
```

- Result: The response from `http://internal.example.jp/` is included in the output
- This enables access to internal network resources (SSRF)
- Note: This attack fails if the XML response is not well-formed

### PHP Filter-Based Attack

- Even when the XML result is not well-formed, PHP filter wrappers can be used:

```xml
<!ENTITY schedule SYSTEM "php://filter/read=convert.base64-encode/
resource=http://internal.example.jp/">
```

- This Base64-encodes the response, making it valid XML content that can be decoded by the attacker

### Java Language Example

**`4e-022.html`** -- HTML form where XML data is entered in a textarea:

```html
<body>
  Please specify XML data:<br>
  <form action="/4e3/C4e_023" method="post">
    <textarea name="xml" cols="40" rows="5"></textarea>
    <input type="submit"/>
  </form>
</body>
```

**`C4e_023.java`** -- Java servlet that parses XML:

```java
import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;
// ...
public class C4e_023 extends HttpServlet {
  public void service(HttpServletRequest request, HttpServletResponse response)
    throws ServletException, IOException {
    // ...
    DocumentBuilderFactory factory = DocumentBuilderFactory.newInstance();
    DocumentBuilder builder = factory.newDocumentBuilder();
    String xml = request.getParameter("xml");
    Document doc = builder.parse(new InputSource(new StringReader(xml)));
    // ...
  }
}
```

- The same XXE attacks (file reading, SSRF) work in Java
- Submitting attack XML with `/etc/hosts` entity reference successfully reads the file

---

## Root Causes

- XML inherently supports external entity references -- loading external files is a built-in feature, not a bug
- The vulnerability arises because this powerful feature is enabled by default in many XML parsers

---

## Countermeasures

### PHP: XXE Prevention

1. **Use libxml2 version 2.9 or later** -- defaults to disabling external entities
2. **Call `libxml_disable_entity_loader(true)`** -- explicitly disables external entity loading:

```php
$doc = new DOMDocument();
$doc->substituteEntities = true;    // Disable external entity expansion
$doc->load($_FILES['user']['tmp_name']);
```

3. **Use JSON instead of XML** -- if possible, avoid XML entirely
4. **Use libxml2 version 2.9+** -- external entities are disabled by default

```php
$doc = new DOMDocument();
$doc->substituteEntities = true;  // XXE protection enabled
$doc->load($_FILES['user']['tmp_name']);
```

### Java: XXE Prevention

- Disable DTD processing entirely:

```java
DocumentBuilderFactory factory = DocumentBuilderFactory.newInstance();
factory.setFeature("http://apache.org/xml/features/disallow-doctype-decl", true);
DocumentBuilder builder = factory.newDocumentBuilder();
Document doc = builder.parse(/* input */);
```

- Note: In Java, external entity references are **enabled by default** in many XML parsers, and the attack surface is broad. Application-level countermeasures are essential.
- OWASP provides detailed guidance for various Java XML libraries

### Use JSON Instead of XML

- If XML is not required by the protocol (e.g., SOAP), use JSON instead
- JSON does not have entity reference functionality, so XXE is not possible
- When using JSON, verify that received data is actually JSON (check `Content-Type`)

---

## Summary

- XXE was first reported around 2002 but was not widely known until recently
- It entered OWASP Top 10 in the 2017 edition (4th place)
- JSON and XML can both be used for data exchange, but JSON is safer regarding XXE
- Always verify `Content-Type` to confirm received data format
- PHP users should ensure libxml2 2.9+ is used; Java users must explicitly disable DTD/external entities

---

## Key Takeaways

- **eval injection**: Never use `eval` with external input. Use `json_encode`/`json_decode` for data serialization instead
- **Unsafe deserialization**: Never call `unserialize()` on untrusted data. Use JSON format for external data exchange
- **XXE**: Disable external entity references in XML parsers. Prefer JSON over XML when possible. Always validate input format
- All three vulnerabilities share a common theme: **never trust structured data from external sources without proper validation and restriction**
