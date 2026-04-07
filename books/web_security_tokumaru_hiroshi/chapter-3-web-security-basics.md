# Chapter 3: Web Security Basics -- HTTP, Session Management, Same-Origin Policy, CORS

This chapter covers the foundational knowledge for web security: how HTTP works, how sessions are managed, the browser's same-origin policy as a defense mechanism, and CORS for controlled cross-origin access.

---

## 3.1 HTTP and Session Management

### Why Learn HTTP?

- Web application vulnerabilities stem from characteristics unique to the web
- Understanding which information leaks, which can be tampered with, and how to maintain state securely all require a solid grasp of HTTP and session management
- This section explains HTTP concepts and their security implications

---

### The Simplest HTTP

- A basic PHP script (`31-001.php`) that displays the current time demonstrates the simplest HTTP exchange
- Use OWASP ZAP as a proxy (configure FoxyProxy to route traffic through it) to intercept and observe HTTP messages

#### HTTP Request and Response Flow

```
Browser  ---HTTP Request--->  Web Server
Browser  <--HTTP Response---  Web Server
```

---

### Request Message Structure

A request message consists of three parts:

| Part | Description |
|------|-------------|
| **Request Line** | Method, URL (URI), and protocol version |
| **Headers** | Key-value pairs separated by colons, from line 2 until the first blank line |
| **Body** | Content after the blank line (used in POST requests) |

#### Example Request Line

```
GET /31/31-001.php HTTP/1.1
     ^URL(URI)       ^Protocol Version
^Method
```

- **Methods**: GET (retrieve resources), POST (send data via HTML form `method` attribute), HEAD, etc.
- The **Host** header is mandatory and indicates the destination host (FQDN) and port (port 80 is the default and can be omitted)

---

### Response Message Structure

| Part | Description |
|------|-------------|
| **Status Line** | Protocol version, status code, and reason phrase |
| **Headers** | Key-value pairs from line 2 until the blank line |
| **Body** | Content after the blank line |

#### Example Response

```
HTTP/1.1 200 OK
Server: nginx/1.10.3
Date: Sat, 14 Apr 2018 04:18:36 GMT
Content-Type: text/html; charset=UTF-8
Connection: keep-alive
X-UA-Compatible: IE=edge

<body>
13:18</body>
```

---

### Status Codes

Status codes are grouped by the first digit:

| Code Range | Meaning |
|------------|---------|
| **1xx** | Informational -- processing continues |
| **2xx** | Success -- request completed |
| **3xx** | Redirect |
| **4xx** | Client error |
| **5xx** | Server error |

Common codes: `200 OK`, `301/302 Redirect`, `401 Unauthorized`, `404 Not Found`, `500 Internal Server Error`

---

### Key Response Headers

- **Content-Length**: Size of the body in bytes
- **Content-Type**: MIME type indicating the resource type

#### Common MIME Types

| MIME Type | Meaning |
|-----------|---------|
| `text/plain` | Plain text |
| `text/html` | HTML document |
| `application/xml` | XML document |
| `text/css` | CSS |
| `image/gif` | GIF image |
| `image/jpeg` | JPEG image |
| `image/png` | PNG image |
| `application/pdf` | PDF document |

- The `charset=UTF-8` parameter on Content-Type specifies character encoding; it must be set correctly to avoid security issues (see Chapter 6 for details)

---

### HTTP as a Conversation (Analogy)

If HTTP were a human conversation, it would be stateless -- each exchange is independent:

```
Customer: "What time is it?"
Clerk:    "It is 1:18 PM."
```

---

### Input-Confirm-Register Pattern

A typical web form flow consists of three screens/scripts:

| Script | Purpose |
|--------|---------|
| `31-002.php` | Input form (name, email, gender) |
| `31-003.php` | Confirmation screen (displays entered data, uses hidden fields) |
| `31-004.php` | Registration completion screen |

#### Example Input Form (31-002.php)

```html
<html>
<head><title>Personal Info Entry</title></head>
<body>
<form action="31-003.php" method="POST">
  Name: <input type="text" name="name"><br>
  Email: <input type="text" name="mail"><br>
  Gender:
  <input type="radio" name="gender" value="M">Male
  <input type="radio" name="gender" value="F">Female<br>
  <input type="submit" value="Confirm">
</form>
</body></html>
```

#### Confirmation Script (31-003.php)

```php
<?php
  $name = $_POST['name'];
  $mail = $_POST['mail'];
  $gender = $_POST['gender'];
?>
<html><head><title>Confirm</title></head>
<body>
<form action="31-004.php" method="POST">
  Name: <?php echo htmlspecialchars($name, ENT_NOQUOTES, 'UTF-8'); ?><br>
  Email: <?php echo htmlspecialchars($mail, ENT_NOQUOTES, 'UTF-8'); ?><br>
  Gender: <?php echo htmlspecialchars($gender, ENT_COMPAT, 'UTF-8'); ?><br>
  <input type="hidden" name="name" value="...">
  <input type="hidden" name="mail" value="...">
  <input type="hidden" name="gender" value="...">
  <input type="submit" value="Register">
</form>
</body></html>
```

---

### POST Method

- The request line shows `POST` instead of `GET`
- Data is sent in the **message body**, not in the URL

#### Example POST Request

```
POST /31/31-003.php HTTP/1.1
Referer: http://example.jp/31/31-002.php
Content-Type: application/x-www-form-urlencoded
Content-Length: 72
Host: example.jp

name=%E5%BE...&mail=toku%40example.jp&gender=%E7%94%B7
```

---

### Message Body

- POST requests carry data in the body
- The body is separated from headers by a blank line
- **Content-Length** specifies body size in bytes
- **Content-Type** defaults to `application/x-www-form-urlencoded` for HTML forms
- Data format: `name=value` pairs joined by `&`, with names and values percent-encoded

---

### Percent Encoding

- Used to represent non-ASCII characters and special characters in URLs and form data
- Each byte is represented as `%XX` (two hex digits)
- Spaces become `%20` or `+` depending on context
- Example: `I'm a programmer` becomes `I%27m+a+programmer`

---

### Referer Header

- Contains the URL of the page that linked to the current request
- Present on form submissions, link clicks, and resource loads (e.g., `<img>` tags)
- Can be set by the user or removed; can also be absent in certain situations

> **Security Note**: If the URL contains sensitive information, the Referer header can leak it. Session IDs in URLs can be exposed via Referer to external sites, potentially enabling session hijacking.

---

### GET vs. POST: When to Use Which

Per HTTP 1.1 (RFC 7231), the distinction is:

| Method | Purpose |
|--------|---------|
| **GET** | Retrieving resources (read-only); should have no side effects |
| **POST** | Expected to cause side effects; used for sending confidential data |

**Reasons to use POST for sensitive data:**

- URL parameters appear in the Referer header and can leak externally
- URL parameters are recorded in access logs
- URL parameters are displayed in the browser address bar and visible to others
- URLs with parameters may be shared on social networks

**Use POST when:**

- The request causes data updates or side effects
- Sending confidential information
- The data volume is large

---

### Hidden Parameters Can Be Tampered With

- On the confirmation screen, user input is stored in hidden form fields
- Although hidden fields are not visible on the page, they exist in the HTML source
- Users (or attackers) can modify hidden field values using tools like OWASP ZAP before the request is sent to the server

> **Key Point**: Data sent from the browser -- including hidden fields, radio buttons, checkboxes, and select options -- can all be rewritten by the user.

#### Hidden Parameter Tampering as a Conversation

```
Customer: "I'd like to register."
Clerk:    "Please provide name, email, gender."
Customer: "Name is Tokumaru, email is toku@example.jp, gender is male."
Clerk:    "Confirming: Tokumaru, toku@example.jp, male. Correct?"
Customer: "Actually change the email to toku@example.jp, gender female."
Clerk:    "Registered. Tokumaru, toku@example.jp, female."
```

#### Benefits of Hidden Parameters

Despite the tampering risk, hidden parameters are useful because:

- The application handles them server-side, where tampering can be detected
- For data that is not security-sensitive (like form flow state), hidden parameters work well
- For sensitive data (login state, permissions), use **sessions** instead
- Refer to Section 4.6 for detailed countermeasures

---

### Stateless HTTP Authentication

- HTTP itself is stateless -- the server does not remember previous requests
- HTTP supports authentication mechanisms: Basic, NTLM, Digest, etc.
- Since HTTP is stateless, HTTP authentication is also stateless

---

### Basic Authentication

Basic authentication flow:

```
1. Client requests a protected page
   GET /31/31-010.php HTTP/1.1

2. Server responds with 401 Unauthorized
   HTTP/1.1 401 Unauthorized
   WWW-Authenticate: Basic realm="Basic Authentication Sample"

3. Browser shows ID/password dialog

4. Client resends request with credentials
   GET /31/31-010.php HTTP/1.1
   Authorization: Basic dXNlcjE6cGFzczE=

5. Server responds with 200 OK
   HTTP/1.1 200 OK
```

#### Basic Auth PHP Example

```php
<?php
$user = @$_SERVER['PHP_AUTH_USER'];
$pass = @$_SERVER['PHP_AUTH_PW'];

if (! $user || ! $pass) {
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Basic realm="Basic Authentication Sample"');
    echo "Username and password required";
    exit;
}
?>
<body>
Authenticated<br>
User: <?php echo htmlspecialchars($user, ENT_NOQUOTES, 'UTF-8'); ?>
Password: <?php echo htmlspecialchars($pass, ENT_NOQUOTES, 'UTF-8'); ?>
</body>
```

#### Authorization Header Encoding

- The `Authorization: Basic dXNlcjE6cGFzczE=` header contains Base64-encoded `user:pass`
- Decode with OWASP ZAP's Encoder: `dXNlcjE6cGFzczE=` decodes to `user1:pass1`

> **Important**: Basic authentication is stateless. The browser automatically sends the Authorization header on subsequent requests after initial login. The authentication state appears persistent but is not stored on the server -- the browser resends credentials with every request.

---

## Cookies and Session Management

### Why Sessions?

- HTTP is stateless; the server does not retain client state between requests
- Applications need to maintain state (shopping carts, login status, etc.)
- **Session management** is the mechanism to track state across HTTP requests using cookies

### How Cookies Work

- The server sends a `Set-Cookie` header in the response to store a cookie in the browser
- The browser automatically includes the cookie in subsequent requests to the same site

#### Cookie-Based Session Flow

```
1. Server Response (on login):
   HTTP/1.1 200 OK
   Set-Cookie: PHPSESSID=gg5144avrhmdiaelvh8014lb53; path=/

2. Subsequent Client Request:
   POST /31/31-021.php HTTP/1.1
   Cookie: PHPSESSID=gg5144avrhmdiaelvh8014lb53
   ...
   ID=user1&PWD=pass1
```

### Cookie-Based Session Management

- Cookies can store small amounts of data on the browser side
- Cookie values can be viewed and modified by the user, so they are not suitable for storing sensitive information directly

> **Key Point**: Cookies store a "session ID" (a management number) rather than actual data. The real values are stored server-side.

- PHP and most web frameworks provide built-in session management mechanisms
- The session ID in the cookie (e.g., `PHPSESSID`) links to server-side session data
- After authentication, the user ID is stored in `$_SESSION['ID']`, and subsequent requests use that session to identify the user

#### Session Login Example (31-020.php / 31-021.php / 31-022.php)

```php
// 31-020.php -- Login form
<?php session_start(); ?>
<html><head><title>Please log in</title></head>
<body>
<form action="31-021.php" method="POST">
  User: <input type="text" name="ID"><br>
  Password: <input type="password" name="PWD"><br>
  <input type="submit" value="Login">
</form>
</body></html>

// 31-021.php -- Authentication
<?php
session_start();
$id = @$_POST['ID'];
$pwd = @$_POST['PWD'];
// Dummy auth: any non-empty ID/password succeeds
if ($id == '' || $pwd == '') {
    die('Login failed');
}
$_SESSION['ID'] = $id;
?>
// Redirect to profile page

// 31-022.php -- Profile (protected)
<?php
session_start();
$id = $_SESSION['ID'];
if ($id == '') {
    die('Please log in');
}
?>
<html><head><title>Profile</title></head>
<body>
User ID: <?php echo htmlspecialchars($id, ENT_NOQUOTES, 'UTF-8'); ?>
</body></html>
```

---

### Session Management Security: A Bank Analogy

Imagine session IDs as queue numbers at a bank:

```
Customer: "I'd like to make a withdrawal."
Clerk:    "Your queue number is 005. Account number and PIN please."
Customer: "Account 12345, PIN 9876. Identity verified."
Clerk:    "Balance is 50,000 yen."
Customer: "Transfer 30,000 from account 23456."
Clerk:    "Deposited. Queue 005, account 23456, balance 30,000."
Customer: "Done, thanks."
```

**The attack**: If an attacker learns the queue number (session ID), they can impersonate the customer:

```
Attacker: "My queue number is [stolen session ID]."
Clerk:    "Account and PIN please."
Attacker: "Queue [stolen ID], account 12345, PIN 9876. Transfer 30,000 to account 99999."
Clerk:    "Done."
```

This is why session IDs must be:
1. **Unpredictable** -- cannot be guessed by third parties
2. **Unextractable** -- cannot be leaked to third parties

> **Key Point**: Use the session management mechanisms provided by your development framework/tools. Do not implement your own.

---

### Causes of Session ID Leakage

- Cookie attributes misconfigured (see below)
- Session ID transmitted over unencrypted network (Section 8.3)
- Cross-site scripting (XSS) stealing cookies via injected JavaScript (Section 4.3)
- PHP or platform vulnerabilities leaking session IDs (Section 4.6.3)
- Session ID embedded in URLs leaking via Referer header

> **Key Point**: If the session ID can be leaked over the network, use **TLS (Transport Layer Security)** to encrypt the connection. Pay careful attention to cookie attributes.

---

### Cookie Attributes

| Attribute | Purpose |
|-----------|---------|
| **Domain** | Which domain(s) receive the cookie |
| **Path** | Which URL paths receive the cookie |
| **Expires** | Expiration date; if omitted, cookie is deleted when browser closes |
| **Secure** | Cookie is only sent over HTTPS connections |
| **HttpOnly** | Cookie cannot be accessed by JavaScript |

---

### Cookie Domain Attribute

- By default, cookies are sent only to the server that set them (safest)
- Setting `Domain=example.jp` sends the cookie to `a.example.jp`, `b.example.jp`, etc.
- This is necessary when multiple subdomains need to share a cookie
- However, overly broad Domain settings create security risks (e.g., on shared hosting, other tenants' sites on the same domain could read the cookie)

> **Key Point**: Do not set the Domain attribute unless necessary. The default (exact match) is the most secure.

#### Cookie Monster Bug

A historical browser bug where setting cookies at a broad domain level (e.g., `co.jp`) affected all sites under that domain. For example, a cookie set with `Domain=co.jp` would be sent to `amazon.co.jp`, `yahoo.co.jp`, etc. Internet Explorer was particularly affected with regard to geographic/municipal JP domains.

---

### Cookie Secure Attribute

- When set, the cookie is **only sent over HTTPS** connections
- Cookies without the Secure attribute are sent over both HTTP and HTTPS
- This is critical for session cookies to prevent interception on unencrypted networks (e.g., public Wi-Fi)

> See Section 4.2 for detailed discussion of cookie Secure attribute best practices.

---

### Cookie HttpOnly Attribute

- Prevents JavaScript from accessing the cookie via `document.cookie`
- Primary defense against **cross-site scripting (XSS)** attacks that attempt to steal session cookies
- Does not prevent XSS attacks entirely, but removes one attack vector

To enable in PHP:

```ini
session.cookie_httponly = On
```

> **Important**: HttpOnly protects against cookie theft via XSS but is a defense-in-depth measure, not a complete XSS fix.

---

### Section 3.1 Summary

- Covered HTTP, Basic authentication, cookies, and session management
- Modern applications use cookie-based sessions to maintain authentication state
- Session management is critical for security: protect session IDs, use proper cookie attributes
- The next section covers passive attacks and the same-origin policy

---

## 3.2 Passive Attacks and Same-Origin Policy

### Active vs. Passive Attacks

| Attack Type | Description |
|-------------|-------------|
| **Active** | Attacker directly targets the web server (e.g., SQL injection) |
| **Passive** | Attacker targets users indirectly by exploiting the web application; the user's browser executes the attack |

---

### Active Attacks

- The attacker sends malicious requests directly to the web server
- Examples: SQL injection attacks

```
Attacker  --(malicious request)-->  Target Server
Attacker  <--(stolen data)-------  Target Server
```

---

### Passive Attacks

Passive attacks come in three patterns:

#### Pattern 1: Simple Passive Attack

- The user visits a malicious ("suspicious") site
- The malicious site contains traps (malware, exploits)
- The user's browser is compromised (e.g., malware infection via browser/plugin vulnerabilities)

```
User  --(visits)-->  Malicious Site
User  <--(trap/malware)--  Malicious Site
```

#### Pattern 2: Exploiting a Legitimate Site

1. Attacker plants a trap on a legitimate site (e.g., stored XSS)
2. User visits the legitimate site and encounters the trapped content
3. The user's browser executes the malicious code

```
Attacker  --(plants trap)--> Legitimate Site
User      --(visits)-------> Legitimate Site (now contains trap)
User      <--(malicious content/action)--
```

**Why this is more dangerous:**

- Legitimate sites have more visitors, expanding the attack surface
- The attacker can abuse the legitimate site's functionality
- The attacker can steal users' personal information from the legitimate site

**Common methods to plant traps on legitimate sites:**

- Unauthorized FTP access to modify content (Section 8.1)
- Exploiting web server vulnerabilities to modify content (Section 8.1)
- SQL injection to modify database content (Section 4.4)
- XSS via user-generated content features like SNS (Section 4.3)

> The infamous **Gumblar** malware (circa 2010) used this pattern of passive attack. SQL injection-based attacks on legitimate sites have also been observed repeatedly.

#### Pattern 3: Cross-Site Attack

1. User visits a malicious site
2. Malicious site returns HTML with a trap targeting a legitimate site
3. User's browser sends a malicious request to the legitimate site (e.g., CSRF)

```
User  --(visits)-->        Malicious Site
Malicious Site  --(trap HTML)-->  User's Browser
User's Browser  --(attack request)--> Legitimate Site (CSRF)
```

Steps in this attack pattern:
1. User browses the malicious (or trap) site
2. The trap site serves HTML containing an attack payload
3. The malicious HTML causes the browser to send an attack request to the legitimate site
4. The legitimate site processes the request, and a malicious response (containing JavaScript, etc.) may be returned

**Examples of this pattern:**

- Cross-Site Request Forgery, CSRF (Section 4.5)
- Cross-Site Scripting, XSS (Section 4.3)
- HTTP Header Injection (Section 4.7)

---

### How Browsers Defend Against Passive Attacks

- Browsers must access various websites, so they cannot simply block all access
- The browser applies a **sandbox** approach: allow functionality but restrict dangerous operations

#### Sandbox Concept

- Originally from Java applets, ActiveX controls, etc.
- The idea: allow programs to run but restrict what they can do
- Even malicious code runs in a restricted sandbox and cannot cause harm outside it
- JavaScript sandboxes restrict:
  - Local file access
  - Printing and other resource use (with user consent)
  - Network access (governed by the **same-origin policy**)

---

### Same-Origin Policy

The **same-origin policy** (SOP) is the most important browser security mechanism. It restricts how JavaScript and other client-side code from one origin can interact with resources from another origin.

#### What Defines "Same Origin"?

Two URLs have the same origin if and only if all three match:

| Component | Must Match? |
|-----------|-------------|
| **Host (FQDN)** | Yes |
| **Scheme (Protocol)** | Yes |
| **Port** | Yes |

> **Note**: For cookies, the scheme and port are not considered (only the host matters), making cookie-based restrictions less strict than JavaScript's same-origin policy.

---

### JavaScript iframe Access Experiment

**Same-origin access works:**

```html
<!-- 32/32-001.html (outer page) -->
<html><head><title>Frame Content Reading</title></head>
<body>
<iframe name="iframe1" width="300" height="80"
  src="http://example.jp/32/32-002.html">
</iframe><br>
<input type="button" onclick="go()" value="Show Password">
<script>
function go() {
  try {
    var x = iframe1.document.form1.passwd.value;
    document.getElementById('out').textContent = x;
  } catch (e) {
    alert(e.message);
  }
}
</script>
<span id="out"></span>
</body></html>

<!-- 32/32-002.html (inner iframe) -->
<body>
<form name="form1">
  iframe content<br>
  Password: <input type="text" name="passwd" value="password1">
</form>
</body>
```

- When both pages are on the **same origin** (`example.jp`), clicking "Show Password" successfully reads the iframe's password field value

**Cross-origin access is blocked:**

- If the outer page is on `trap.example.com` and the iframe loads `example.jp`, attempting to read the iframe content via JavaScript produces an error:
  ```
  Permission denied to access property "document" on cross-origin object
  ```

> This is the same-origin policy in action: JavaScript from one origin cannot read the DOM of a page from a different origin.

---

### Why Same-Origin Policy Matters for Security

Without the same-origin policy:

1. An attacker's site (`trap.example.com`) embeds your banking site in an iframe
2. You are logged into the banking site, so the iframe shows your account
3. The attacker's JavaScript reads your account data from the iframe
4. Your personal/financial information is stolen

The same-origin policy prevents step 3 -- the attacker's JavaScript cannot access the iframe content because the origins differ.

---

### Application Vulnerabilities and Passive Attacks

- XSS (Cross-Site Scripting) is the most prominent attack that circumvents the same-origin policy
- XSS injects attacker-controlled JavaScript into a legitimate site's origin
- Since the injected script runs under the legitimate site's origin, it bypasses SOP and can access all data on that origin

---

### Cross-Origin Access Beyond JavaScript

#### frame and iframe Elements

- As shown above, cross-origin iframes can be displayed but their content cannot be read by JavaScript from a different origin

#### img Elements

- The `src` attribute of `<img>` tags can reference cross-origin URLs
- Images from other hosts load successfully
- However, the actual pixel data ("recognized image") cannot be read programmatically
- Cross-origin images can only be displayed, not inspected via JavaScript/Canvas

#### script Elements

- The `src` attribute of `<script>` tags **can** load cross-origin JavaScript
- The loaded script executes in the context of the **loading page's** origin
- This means cross-origin scripts can access `document.cookie` and the DOM of the page that loaded them
- This is the basis of **JSONP** (JSON with Padding) -- a pre-CORS technique for cross-origin data sharing
- JSONP works by having the server wrap data in a callback function; use it only for public data

> **Security Warning**: Loading third-party JavaScript via `<script src>` is inherently risky. The external script runs with full privileges in your origin.

#### CSS

- CSS (`@import`, `<link>`) can be loaded from cross-origin sources
- Historically, Internet Explorer had vulnerabilities where CSS could be abused to execute script (CSSXSS)
- CSS injection can still be a concern

#### form Element action Attribute

- The `action` attribute of forms can point to a cross-origin URL
- Form submission (`submit`) works cross-origin
- This is the mechanism exploited by **CSRF (Cross-Site Request Forgery)** attacks: a malicious page contains a form that submits to a legitimate site

---

### Allowing Third-Party JavaScript

There are scenarios where third-party JavaScript is intentionally allowed:

| Scenario | Description |
|----------|-------------|
| **Site operator trusts third party** | Ad networks, analytics scripts, CDN-hosted libraries |
| **Providing hosted content** | The site operator provides JavaScript to be embedded on other sites (e.g., widgets) |
| **User-installed extensions** | Browser extensions like Greasemonkey that inject custom JavaScript |

> Each scenario has different trust assumptions. The key risk is that any JavaScript running in your origin has full access to that origin's data.

---

### Section 3.2 Summary

- Passive attacks exploit users indirectly through their browsers
- The **same-origin policy** is the browser's primary defense against cross-origin data theft
- Same origin = same host + same scheme + same port
- JavaScript cannot read cross-origin iframe content, but can load cross-origin scripts (which then run in the loader's origin)
- XSS circumvents the same-origin policy by injecting script into the target origin
- CSRF exploits cross-origin form submission

---

## 3.3 CORS (Cross-Origin Resource Sharing)

### Why CORS?

- As JavaScript applications grew more sophisticated, the need to fetch data from different origins increased
- `XMLHttpRequest` was traditionally restricted by the same-origin policy
- **CORS** is a W3C specification that allows controlled cross-origin access while maintaining security
- CORS enables cross-origin `XMLHttpRequest` calls with server-side opt-in

---

### Simple Requests

A "simple request" is one that meets **all** of the following conditions (modeled after what HTML forms can already do, so no additional risk):

#### Simple Request Criteria

**Method must be one of:**
- GET
- HEAD
- POST

**Only these request headers may be set via `setRequestHeader`:**
- Accept
- Accept-Language
- Content-Language
- Content-Type

**Content-Type must be one of:**
- `application/x-www-form-urlencoded`
- `multipart/form-data`
- `text/plain`

#### Simple Request Example

JavaScript on `example.jp` calls an API on `api.example.net`:

```javascript
// 33/33-001.html (on example.jp)
var req = new XMLHttpRequest();
req.open('GET', 'http://api.example.net/33/33-002.php');
req.onreadystatechange = function() {
    if (req.readyState == 4 && req.status == 200) {
        alert(req.responseText);
    }
};
req.send(null);
```

```php
// API: http://api.example.net/33/33-002.php
<?php
header('Content-Type: application/json');
echo json_encode(['zipcode' => '100-0100', 'address' => 'Tokyo...']);
```

**Without CORS headers**: The browser blocks the response with an error: "CORS header `Access-Control-Allow-Origin` missing."

---

### Access-Control-Allow-Origin

- The server must include this header to permit cross-origin access
- It specifies which origin is allowed to read the response

```
Access-Control-Allow-Origin: http://example.jp
```

#### Updated API with CORS Header

```php
// 33/33-002a.php
<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://example.jp');
echo json_encode(['zipcode' => '100-0100', 'address' => 'Tokyo...']);
```

After adding this header, the JavaScript on `example.jp` can successfully read the cross-origin response.

---

### Preflight Requests

When a cross-origin request does **not** meet the "simple request" conditions, the browser sends a **preflight request** (an HTTP `OPTIONS` request) before the actual request.

#### Example: POST with `Content-Type: application/json`

```javascript
// 33/33-003.html
var req = new XMLHttpRequest();
req.open('POST', 'http://api.example.net/33/33-004.php');
req.setRequestHeader('content-type', 'application/json');
data = '{"zipcode": "100-0111"}';
req.onreadystatechange = function() {
    if (req.readyState == 4 && req.status == 200) {
        alert(req.responseText);
    }
};
req.send(data);
```

This triggers a preflight because `Content-Type: application/json` is not in the simple request list.

#### Preflight Request/Response Headers

| Negotiation | Request Header | Response Header |
|-------------|---------------|-----------------|
| **Method** | `Access-Control-Request-Method` | `Access-Control-Allow-Methods` |
| **Headers** | `Access-Control-Request-Headers` | `Access-Control-Allow-Headers` |
| **Origin** | `Origin` | `Access-Control-Allow-Origin` |

#### Preflight OPTIONS Request Example

```
OPTIONS /33/33-004.php HTTP/1.1
Host: api.example.net
User-Agent: Mozilla/5.0 ...
Accept: */*
Access-Control-Request-Method: POST
Access-Control-Request-Headers: content-type
Origin: http://example.jp
Connection: close
```

#### Preflight Response Example

```
HTTP/1.1 200 OK
Access-Control-Allow-Origin: http://example.jp
Access-Control-Allow-Methods: POST, GET, OPTIONS
Access-Control-Allow-Headers: Content-Type
Access-Control-Max-Age: 1728000
Content-Length: 0
Content-Type: text/plain; charset=UTF-8
```

#### Server-Side Preflight Handler

```php
// 33/33-004a.php
<?php
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    if ($_SERVER['HTTP_ORIGIN'] === 'http://example.jp') {
        header('Access-Control-Allow-Origin: http://example.jp');
        header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        header('Access-Control-Max-Age: 1728000');
        header('Content-Length: 0');
        header('Content-Type: text/plain');
    } else {
        header('HTTP/1.1 403 Access Forbidden');
        header('Content-Type: text/plain');
        echo 'This request is not allowed.';
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: http://example.jp');
    echo json_encode(['zipcode' => '100-0100', 'address' => 'Tokyo...']);
} else {
    die('Invalid Request');
}
```

After the preflight succeeds, the browser sends the actual POST request:

```
POST /33/33-004a.php HTTP/1.1
Host: api.example.net
Content-Type: application/json
Referer: http://example.jp/33/33-003.html
Content-Length: 24
Origin: http://example.jp

{"zipcode": "100-0100"}
```

---

### Requests with Credentials (Cookies)

By default, cross-origin `XMLHttpRequest` calls do **not** send cookies or HTTP authentication headers.

To include credentials:

1. Set `withCredentials = true` on the `XMLHttpRequest` object
2. The server must respond with `Access-Control-Allow-Credentials: true`

#### Client-Side: Enable Credentials

```javascript
// 33/33-005a.html
var req = new XMLHttpRequest();
req.open('GET', 'http://api.example.net/33/33-006.php');
req.withCredentials = true;  // Send cookies cross-origin
req.onreadystatechange = function() {
    if (req.readyState == 4 && req.status == 200) {
        var span = document.getElementById('counter');
        span.textContent = req.responseText;
    }
};
req.send(null);
```

#### Server-Side: Allow Credentials

```php
// 33/33-006.php
<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://example.jp');

if (empty($_SESSION['counter'])) {
    $_SESSION['counter'] = 1;
} else {
    $_SESSION['counter']++;
}
echo json_encode(array('count' => $_SESSION['counter']));
```

**Without `Access-Control-Allow-Credentials: true`** in the response, the browser sends the cookie but rejects the response (error in console).

#### Updated Server with Credentials Support

```php
// 33/33-006a.php (relevant addition)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://example.jp');
header('Access-Control-Allow-Credentials: true');
```

After both sides are configured, the cross-origin request successfully sends cookies and receives the response, enabling features like a cross-origin access counter.

---

### CORS Credential Summary

To use cookies and authentication with cross-origin requests, **both** conditions must be met:

| Side | Requirement |
|------|-------------|
| **Client (JavaScript)** | Set `XMLHttpRequest.withCredentials = true` |
| **Server (Response)** | Include `Access-Control-Allow-Credentials: true` header |

---

### Section 3.3 / Chapter Summary

- CORS provides a standardized way to relax the same-origin policy while maintaining security
- The server explicitly opts in to cross-origin access via `Access-Control-Allow-Origin`
- Simple requests (GET/HEAD/POST with standard headers) go directly; others require a preflight OPTIONS exchange
- Credentials (cookies, auth) require explicit opt-in on both client and server
- CORS maintains compatibility with the same-origin policy -- it is a controlled relaxation, not a bypass
- For secure cross-origin resource access, understand and correctly configure CORS headers

---

## Key Takeaways

1. **HTTP is stateless**: Every request-response pair is independent. Session management (via cookies) is needed to maintain state across requests.

2. **All client-side data can be tampered with**: Hidden fields, cookies, form values -- anything the browser sends can be modified by the user or an attacker. Never trust client-side data.

3. **POST for sensitive data**: Use POST (not GET) for sensitive information to avoid leaking data via Referer headers, browser history, and server logs.

4. **Session ID security is paramount**: Session IDs must be unpredictable and protected from leakage. Use framework-provided session management, not custom implementations.

5. **Cookie attributes matter**:
   - `Secure`: Only send over HTTPS
   - `HttpOnly`: Block JavaScript access (mitigates XSS cookie theft)
   - `Domain`: Keep as narrow as possible (omit for strictest scope)

6. **Same-origin policy is the browser's core defense**: It prevents scripts from one origin reading data from another origin. Same origin = same host + scheme + port.

7. **XSS bypasses same-origin policy**: By injecting script into the target origin, attackers gain full access to that origin's data. This makes XSS one of the most dangerous web vulnerabilities.

8. **Passive attacks exploit users, not servers**: Attackers use malicious or compromised sites to make the user's browser perform unintended actions against legitimate sites (CSRF, XSS).

9. **CORS is controlled relaxation of SOP**: Servers explicitly opt in to cross-origin access. Three key headers:
   - `Access-Control-Allow-Origin` -- which origins can read responses
   - `Access-Control-Allow-Credentials` -- whether cookies/auth are allowed
   - Preflight (`OPTIONS`) -- negotiates non-simple requests before they are sent

10. **Defense in depth**: No single mechanism is sufficient. Combine proper HTTP usage, session management, cookie attributes, same-origin policy, CORS configuration, and input validation for robust web security.
