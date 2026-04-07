# Chapter 4.16 - Vulnerabilities in Web API Implementations

> From "The Art of Secure Web Application Development" (安全なWebアプリケーションの作り方) by Tokumaru Hiroshi

## Overview

Web APIs are increasingly common in modern web applications. Unlike traditional web apps, APIs handle data processing across multiple platforms (mobile, smart devices, etc.) rather than rendering display pages. Web APIs use data formats like JSON and XML, and this section covers security vulnerabilities specific to these implementations.

### Vulnerabilities Covered

- JSON escape deficiencies
- JSON direct browsing XSS
- JSONP callback function name XSS
- Web API Cross-Site Request Forgery (CSRF)
- JSON Hijacking
- Inappropriate use of JSONP
- CORS validation deficiencies

---

## 4.16.1 Overview of JSON and JSONP

### What is JSON?

- JSON (JavaScript Object Notation) originated from Ajax (Asynchronous JavaScript + XML)
- XML was verbose for data exchange; JSON uses JavaScript object literal syntax instead
- JSON is essentially a subset of JavaScript that can be parsed safely

#### Server-side JSON Conversion (PHP)

| Function | Purpose |
|----------|---------|
| `json_encode` | Convert PHP array/object to JSON string |
| `json_decode` | Convert JSON string to PHP array |

#### Client-side JSON Conversion (JavaScript)

| Function | Purpose |
|----------|---------|
| `JSON.stringify` | Convert JavaScript object to JSON string |
| `JSON.parse` | Convert JSON string to JavaScript object (safe) |
| `eval()` | Also works but **dangerous** - executes arbitrary code |

```php
// PHP - Generate JSON
$a = array('name' => 'Watanabe', 'age' => 29);
echo json_encode($a);
// Output: {"name":"Watanabe","age":29}
```

```javascript
// JavaScript - Parse JSON
var json = '{"name":"Watanabe","age":29}';
var obj = JSON.parse(json);   // Safe
var obj = eval('(' + json + ')');  // Dangerous!
```

### What is JSONP?

- JSONP bypasses the Same-Origin Policy restriction of XMLHttpRequest
- Instead of XMLHttpRequest, it uses `<script>` elements to load cross-origin JavaScript
- The server wraps JSON data in a callback function call
- The callback function name is specified as a query parameter

```php
// Server-side JSONP (PHP)
$callback = $_GET['callback'];
header('Content-Type: text/javascript; charset=utf-8');
$json = json_encode(array('time' => date('G:i')));
echo "$callback($json);";
```

```javascript
// Client-side JSONP with jQuery
$.ajax({
    url: 'http://api.example.net/4g/4g-003.php',
    dataType: "jsonp",
    jsonpCallback: "display_time"
});
```

---

## 4.16.2 JSON Escape Deficiencies

### Summary

| Attribute | Detail |
|-----------|--------|
| **Affected** | APIs that output JSON or JSONP |
| **Impact page** | Entire web application |
| **Impact type** | JavaScript execution on user's browser |
| **Severity** | Medium to High |
| **User involvement** | Clicking a link, visiting attacker's site |
| **Countermeasure** | Use safe library functions for JSON generation |

### Vulnerability Cause

Two conditions must both be present:
1. **Improper escaping** during JSON string generation (e.g., string concatenation)
2. **Using `eval()` or JSONP** to decode JSON instead of `JSON.parse`

### Attack Example

Given an API that returns error messages in JSONP format:

```php
// Vulnerable API (4g-006.php)
$zip = $_GET['zip'];
$json = '{"message":"郵便番号が見つかりません: ' . $zip . '"}';
header('Content-Type: text/javascript; charset=utf-8');
echo "callback_zip($json);";
```

An attacker can inject JavaScript via the zip parameter:

```
http://example.jp/4g/4g-007.html#1"%2balert(document.domain)%2b"
```

This generates:
```javascript
callback_zip({"message":"郵便番号が見つかりません:1"+alert(document.domain)+""});
```

The injected `alert(document.domain)` executes as JavaScript, whether through JSONP (`<script>` tag) or `eval()`.

### Countermeasures

- **Stop using string concatenation** for JSON generation; use trusted library functions
- **Never use `eval()`**; use `JSON.parse` or other safe APIs for JSON decoding
- Transition from JSONP to CORS-based Web APIs

---

## 4.16.3 JSON Direct Browsing XSS

### Summary

| Attribute | Detail |
|-----------|--------|
| **Affected** | APIs that output JSON |
| **Impact page** | Entire web application |
| **Impact type** | JavaScript execution, information theft |
| **Severity** | Medium to High |
| **User involvement** | Clicking a link, visiting attacker's site |
| **Countermeasure** | Set correct MIME type, and more |

### Vulnerability Cause

- When a JSON API is accessed directly in the browser, the response may be interpreted as HTML if the MIME type is incorrect
- If `Content-Type` is `text/html` (or not set to `application/json`), the browser renders JSON as HTML
- The `img` tag with `onerror` attribute or other HTML elements can execute JavaScript

### Attack Example

If an API returns JSON with `Content-Type: text/html`:

```
http://example.jp/4g/4g-011.php?zip=<img+src=1+onerror=alert(document.domain)>
```

The browser treats the response as HTML and executes the `onerror` JavaScript.

### Countermeasures (in order of priority)

1. **Set MIME type correctly** (mandatory):
   ```php
   header('Content-Type: application/json; charset=utf-8');
   ```

2. **Set `X-Content-Type-Options: nosniff`** (strongly recommended):
   ```
   X-Content-Type-Options: nosniff
   ```
   This prevents browsers from MIME-type sniffing.

3. **Unicode-escape special characters** like `<`, `>`, `&` (recommended):
   Use `json_encode` with appropriate flags.

4. **Use XMLHttpRequest/CORS-only endpoints** (recommended):
   Check for `X-Requested-With: XMLHttpRequest` header.

### json_encode Option Parameters for Escaping

| Option | Character | Escape Result |
|--------|-----------|---------------|
| `JSON_HEX_TAG` | `<` | `\u003C` |
| `JSON_HEX_AMP` | `&` | `\u0026` |
| `JSON_HEX_APOS` | `'` | `\u0027` |
| `JSON_HEX_QUOT` | `"` | `\u0022` |

```php
// Secure JSON output
$zip = $_GET['zip'];
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
$json = json_encode(array('message' => "郵便番号が見つかりません: " . $zip),
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
```

### IE-specific Issues

- Older versions of IE (before IE9) may ignore Content-Type and use MIME-type sniffing
- IE9+ supports `X-Content-Type-Options: nosniff`
- With `nosniff`, even IE will not execute JavaScript when Content-Type is wrong
- PATH_INFO trick: appending `.html` to URL can force IE to treat response as HTML

---

## 4.16.4 JSONP Callback Function Name XSS

### Summary

| Attribute | Detail |
|-----------|--------|
| **Affected** | APIs that generate JSONP |
| **Impact page** | Entire web application |
| **Impact type** | JavaScript execution, information theft |
| **Severity** | Medium to High |
| **User involvement** | Clicking a link, visiting attacker's site |
| **Countermeasure** | Set correct MIME type, validate callback names |

### Vulnerability Cause

- JSONP allows external callers to specify the callback function name
- If the callback name is not validated, attackers can inject arbitrary JavaScript through the callback parameter

### Attack Example

```
http://api.example.net/4g/4g-015.php?callback=%3Cscript%3Ealert(1)%3C/script%3E
```

If MIME type is `text/html`, the injected `<script>` tag executes.

### Countermeasures

1. **Set MIME type correctly** to `text/javascript` (for JSONP):
   ```php
   header('Content-Type: text/javascript; charset=utf-8');
   ```

2. **Validate callback function names** - restrict to valid JavaScript identifiers:
   ```php
   $callback = $_GET['callback'];
   if (preg_match('/\A[a-zA-Z_][a-zA-Z_0-9]{1,64}\z/', $callback) !== 1) {
       header('HTTP/1.1 403 Forbidden');
       die('コールバック関数名が不正です');
   }
   ```

3. **Restrict character types and length** of callback names (alphanumeric + underscore only)

---

## 4.16.5 Web API Cross-Site Request Forgery (CSRF)

### CSRF Attack Vectors Against Web APIs

#### GET Requests
- If state-changing operations are performed via GET, CSRF is trivially possible (e.g., `<img>` tags)
- **Rule**: Never perform state-changing operations via GET

#### HTML Form Attacks
- POST-based CSRF via HTML forms uses these MIME types:
  - `text/plain` (sent as-is, not commonly used)
  - `application/x-www-form-urlencoded` (standard form encoding)
  - `multipart/form-data` (file upload format)
- If the API does not check MIME type, HTML forms can attack it

#### Cross-Origin XMLHttpRequest (Simple Requests)
- CORS allows cross-origin simple requests without preflight
- Simple requests are limited to certain MIME types, so CSRF attacks are possible
- **However**: Many modern web apps don't check MIME type, making them vulnerable

#### Preflight Requests
- Non-simple requests (e.g., `application/json` Content-Type) trigger preflight (OPTIONS)
- If CORS is not configured to allow the requesting origin, the preflight fails
- **But**: The actual request may still be sent by some browsers before getting the preflight response

### CSRF Attack Example (Email Change)

```javascript
// Attack script on trap.example.com
var req = new XMLHttpRequest();
req.open("POST", "http://example.jp/4g/4g-021.php");
req.withCredentials = true;
req.send('{"mail":"cracked@example.com"}');
```

- `withCredentials = true` sends cookies with the cross-origin request
- The API side JavaScript cannot read the response (blocked by CORS), but the request itself is sent
- The CSRF attack succeeds because the state-changing POST request is delivered

### CSRF Countermeasures for Web APIs

| Method | Description |
|--------|-------------|
| **CSRF Token** | Store token in session, pass via JavaScript/hidden parameter |
| **Double Submit Cookie** | Token in both cookie and request body/header |
| **Custom Request Header** | Check for custom header like `X-CSRF-TOKEN` |
| **MIME type validation** | Verify `Content-Type: application/json` |
| **CORS configuration** | Properly configure allowed origins (Section 4.16.8) |

#### CSRF Token Implementation

```php
// Login - generate token
$token = bin2hex(openssl_random_pseudo_bytes(24));
$_SESSION['token'] = $token;

// API - return token with JSON response
$json = json_encode(array(
    'mail' => $_SESSION['mail'],
    'token' => $_SESSION['token']
));
```

```php
// API - verify token
$token = $_SERVER['HTTP_X_CSRF_TOKEN'];
if (empty($token) || $token !== $_SESSION['token']) {
    header('HTTP/1.1 403 Forbidden');
    die('CSRF detected');
}
```

```javascript
// Client - send token via custom header
function chgmail() {
    var req = new XMLHttpRequest();
    req.open("POST", "4g-021a.php");
    var mail = document.getElementById('mail').value;
    req.setRequestHeader('X-CSRF-TOKEN', token);
    json = JSON.stringify({"mail": mail});
    req.send(json);
}
```

#### Double Submit Cookie

```php
// Set CSRF token in cookie
if (empty($_COOKIE['CSRF_TOKEN'])) {
    $token = bin2hex(openssl_random_pseudo_bytes(24));
    setcookie('CSRF_TOKEN', $token);
}
```

```javascript
// Read cookie and send as custom header
var token = Cookies.get('CSRF_TOKEN');
req.setRequestHeader('X-CSRF-Token', token);
```

**Issues with Double Submit Cookie**:
- If attacker can set cookies on target site (via subdomain XSS), they can defeat this
- Use HTTPS to prevent cookie manipulation over the wire

#### Custom Request Header Check

```php
// Server-side check
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
    $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
    header('HTTP/1.1 403 Forbidden');
    die('CSRF detected');
}
```

- jQuery and other Ajax libraries automatically add `X-Requested-With: XMLHttpRequest`
- This header triggers a preflight request for cross-origin requests
- **Note**: Historically, Flash Player could send custom headers cross-origin (vulnerability patched in 2017)

### Recommended Approach

For maximum security, combine multiple methods:
- **CSRF Token** (most secure, stored server-side in session)
- **Double Submit Cookie** (widely adopted by frameworks)
- **Custom Request Header** (simplest with Ajax libraries)
- Plus: Validate input MIME type, configure CORS properly

---

## 4.16.6 JSON Hijacking

### Summary

| Attribute | Detail |
|-----------|--------|
| **Affected** | APIs providing information via JSON |
| **Impact page** | APIs with sensitive data |
| **Impact type** | Information theft |
| **Severity** | Low (modern browsers are patched) |
| **User involvement** | Clicking a link, visiting attacker's site |
| **Countermeasure** | `X-Content-Type-Options: nosniff`, `X-Requested-With` check |

### How it Works

- JSON data loaded via `<script>` tag can potentially be intercepted
- Attacker creates a page with `<script src="http://target/api/data.json"></script>`
- The browser sends cookies with this request, returning authenticated data
- Historical browser bugs allowed JavaScript setter functions or `__defineSetter__` to capture the data

### Attack Example (Historical - Firefox 3.0.12)

```javascript
// Attacker's page
<body onload="alert(x)">
<script>
var x = "";
Object.prototype.__defineSetter__("mail", function(v) {
    x = v + " ";
});
</script>
<script src="http://example.jp/4g/4g-030.json"></script>
</body>
```

### Browser Compatibility for JSON Read via Script Tag

| Browser | Edge | Google Chrome | Firefox | Safari |
|---------|------|---------------|---------|--------|
| Status | Error | Error | Error | Reads successfully (older) |

**Note**: Firefox 58.0.2 and later block this. Most modern browsers have fixed this vulnerability.

### Countermeasures

- **`X-Content-Type-Options: nosniff`** (strongly recommended) - prevents script tag from loading non-JavaScript MIME types
- **`X-Requested-With: XMLHttpRequest`** header check (recommended) - ensures requests come from Ajax, not script tags
- These countermeasures also help with JSON direct browsing XSS and CSRF

---

## 4.16.7 Inappropriate Use of JSONP

### JSONP for Secret Information Disclosure

- JSONP is inherently cross-origin accessible via `<script>` tags
- **Never use JSONP to serve sensitive/private data** - any site can read it
- Unlike XMLHttpRequest with CORS, JSONP has no origin verification mechanism

### Attack Scenario

1. Application at `api.example.net` serves user info via JSONP
2. Attacker creates page at `trap.example.com` that loads the JSONP
3. User visits attacker's page; their browser sends cookies to `api.example.net`
4. The JSONP callback delivers the private data to the attacker's JavaScript

### Vulnerability Root Cause

- JSONP does not verify the calling origin
- Both legitimate (`example.jp`) and malicious (`trap.example.com`) sites receive the same data
- The `Referer` header differs but the API doesn't check it

### Countermeasures

- **Use CORS instead of JSONP** whenever possible
- If JSONP is necessary, use `XMLHttpRequest` and CORS for private data
- **JSONP should only serve public information**
- **Only use JSONP with trusted providers**

### Summary Rules

- JSONP is acceptable for read-only use with CORS + JSON transition
- JSONP should only serve public information
- JSONP should only be used from trusted sources

---

## 4.16.8 CORS Validation Deficiencies

### The Problem

- CORS is well-designed, but improper configuration by developers can create vulnerabilities
- Careless use of `Access-Control-Allow-Origin` can expose sensitive data

### Dangerous: Wildcard Origin

```
Access-Control-Allow-Origin: *
```

- Acceptable for **public information** APIs (no authentication)
- **Dangerous** if the API returns private/authenticated data
- With wildcard, `withCredentials` requests are blocked by browsers, but data leakage is still possible

### Dangerous: Reflecting Request Origin

Some developers blindly reflect the request's `Origin` header:

```php
// DANGEROUS - allows any origin
function cors() {
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');
    }
    // ... handle preflight
}
```

This is equivalent to allowing any origin and is a common anti-pattern found on Q&A sites like Stack Overflow.

### Proper CORS Configuration

- Specify exact allowed origins:
  ```
  Access-Control-Allow-Origin: https://example.jp
  ```
- Validate the `Origin` header against a whitelist before reflecting it
- Only set `Access-Control-Allow-Credentials: true` when the origin is verified

---

## 4.16.9 Security-Enhancing Response Headers

### X-Frame-Options

- Prevents clickjacking by controlling iframe embedding
- Values: `DENY`, `SAMEORIGIN`

### X-Content-Type-Options

```
X-Content-Type-Options: nosniff
```
- Prevents MIME-type sniffing by browsers
- Essential for JSON APIs to prevent XSS via type confusion
- Prevents JSON hijacking attacks as well

### X-XSS-Protection

```
X-XSS-Protection: 1; mode=block
```
- Enables browser's built-in XSS filter (most modern browsers except Firefox)
- Two benefits:
  1. Overrides user's XSS filter settings
  2. Specifies filter behavior mode

### Content-Security-Policy (CSP)

```
Content-Security-Policy: default-src 'self'
```
- Controls which resources browsers can load (scripts, images, styles, etc.)
- Can significantly reduce XSS attack surface
- Inline JavaScript can be blocked, but this limits some legitimate functionality
- CSP is still evolving; adoption is growing but not yet universal

### Strict-Transport-Security (HSTS)

```
Strict-Transport-Security: max-age=31536000
```
- Forces HTTPS connections for the specified duration
- Including subdomains:
  ```
  Strict-Transport-Security: max-age=31536000; includeSubDomains
  ```
- **Caution**: Once set, the site must support HTTPS or users cannot access it
- **Important**: Requires careful planning before deployment

---

## Section 4.16 Summary / Key Takeaways

- Web API security vulnerabilities are becoming increasingly common as APIs proliferate
- Many of the countermeasures are straightforward but often overlooked
- **Always set correct MIME types** (`application/json` for JSON)
- **Always include `X-Content-Type-Options: nosniff`**
- **Migrate from JSONP to CORS** for cross-origin data access
- **Implement CSRF protection** using tokens, double-submit cookies, or custom headers
- **Configure CORS carefully** - never blindly reflect the Origin header
- **Use security-enhancing headers**: CSP, HSTS, X-Frame-Options, X-XSS-Protection
- Understand the fundamentals of CORS and related security mechanisms before rapid API deployment
