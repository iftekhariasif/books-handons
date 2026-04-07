# Chapter 4.8: Cookie Output Vulnerabilities

This section covers vulnerabilities related to cookie usage and output in web applications, including improper cookie use, missing Secure attribute, and related countermeasures.

---

## 4.8 Overview

Web applications commonly use cookies for session management. Cookie-related vulnerabilities fall into two main categories:

- **Using cookies for unintended purposes** (storing data that should not be in cookies)
- **Problems with cookie output methods**

Related vulnerability types:

- HTTP Header Injection (covered in 4.7.2)
- Cookie Secure attribute deficiency

---

## 4.8.1 Improper Use of Cookies

### What NOT to Store in Cookies

Cookies should ideally only store the **session ID**. Storing actual data in cookies creates vulnerabilities because:

- Cookie values are set **externally** (by the browser/client)
- Cookie values can be **tampered with** by users or attackers
- If tampered data is trusted, it leads to security vulnerabilities

### Common Mistakes

Examples of information that should NOT be stored in cookies:

- User IDs or credentials
- Login status/flags
- Shopping cart data that could be price-manipulated

If such data is stored in cookies, attackers can modify it to impersonate other users or alter prices.

### Why Cookies Should Not Store Data -- Comparison Table

| Aspect | Cookie | Session Variable |
|--------|--------|-----------------|
| **Data location/storage** | Browser (client-side) | Server-side |
| **Number of objects that can be stored** | Limited by browser restrictions | No practical application-level limit |
| **Size limit** | About 4KB | Practically unlimited |
| **Can store structured data (arrays, objects)?** | No | Yes |
| **Susceptible to information leakage** | Yes (via XSS, HTTP header injection, etc.) | No |
| **Susceptible to tampering** | Yes | No |
| **User tracking across servers** | Yes | No |

**Conclusion**: For almost every use case, session variables are superior to cookies for data storage. Cookies should only hold session IDs.

### Yahoo! Login Example

- Yahoo!'s "keep me logged in" checkbox stores a token in a cookie
- The token is not the user's ID/password directly, but a reference that the server validates
- This is the correct approach -- store minimal, non-sensitive references in cookies

### Padding Oracle Attack and MS10-070 (COLUMN)

- Some web application frameworks (like ASP.NET) store session state in hidden form parameters and cookies, encrypting them for security
- The **Padding Oracle attack** exploits weaknesses in encryption padding validation to decrypt or forge encrypted data
- In 2010, T. Duong and J. Rizzo presented this attack at ekoparty, demonstrating it against ASP.NET (MS10-070/CVE-2010-3332)
- This showed that even encrypted cookie values can be compromised
- Reference: http://netifera.com/research/poet/PaddingOraclesEverywhereEkoparty2010.pdf

---

## 4.8.2 Cookie Secure Attribute Deficiency

### Overview

- Cookies have a **Secure** attribute (also called the "secure flag")
- When set, the cookie is **only sent over HTTPS** connections
- Without the Secure attribute, cookies are sent over both HTTP and HTTPS
- If session cookies lack the Secure attribute, they can be intercepted on unencrypted HTTP connections

### Summary of Cookie Secure Attribute Deficiency

| Aspect | Details |
|--------|---------|
| **Origin** | Cookies output at locations that should have the Secure attribute |
| **Affected Pages** | All pages using that cookie |
| **Impact** | Session hijacking possible via cookie interception |
| **Severity** | Medium |
| **User Involvement** | Moderate -- requires HTTP-based communication |
| **Countermeasures** | Set Secure attribute on cookies; use HTTPS for pages handling cookies; use token-based approach for mixed HTTP/HTTPS sites |

---

### Attack Method and Impact

#### Demonstration Setup

A PHP script (`set_non_secure_cookie.php`) intentionally creates a session cookie **without** the Secure attribute:

```php
<?php
ini_set('session.cookie_secure', '0');    // Secure attribute OFF
ini_set('session.cookie_path', '/wasbook/');  // Path restriction
ini_set('session.name', 'PXPSESID');     // Change session ID name
session_start();
$sid = session_id();    // Get session ID
?>
<html>
<body>
Session started<br>
PXPSESID = <?php echo htmlspecialchars($sid, ENT_NOQUOTES, 'UTF-8'); ?>
</body>
</html>
```

#### Attack Flow

1. **Set cookie page**: Visit `https://wasbook.org/set_non_secure_cookie.php` over HTTPS. The cookie `PXPSESID` is set without the Secure attribute
2. **Verify in Firefox**: Open Developer Tools (Ctrl+Shift+E) Network Monitor -- observe that the cookie is set without the Secure flag
3. **Trigger HTTP request**: Visit an HTTP page on the same domain (e.g., an image tag pointing to `http://wasbook.org:443/`)
4. **Cookie leaked**: The browser sends the cookie over the unencrypted HTTP request (status 400), but the cookie value is transmitted in plaintext
5. **Attacker intercepts**: If the attacker is on the same network (e.g., public WiFi), they can sniff the unencrypted HTTP traffic and steal the session cookie

#### Attack Diagram

```
Victim (logged in via HTTPS)
    |
    |-- HTTPS --> Legitimate Site  (secure, cookie set)
    |
    |-- HTTP  --> Any request      (cookie sent in plaintext!)
    |
Attacker (network sniffing)
    |-- Intercepts cookie --> Session Hijack
```

If an attacker obtains the unencrypted cookie, they can hijack the user's session.

---

### Root Cause

Two main reasons the Secure attribute is missing:

1. **Developers do not know about the Secure attribute**
2. **The application design makes it difficult to add** (mixed HTTP/HTTPS sites)

---

### Applications Where Secure Attribute is Hard to Apply

- Many sites use both HTTP and HTTPS (e.g., shopping sites)
- Product catalog pages are HTTP; checkout/payment pages are HTTPS
- If the session cookie has the Secure attribute, it won't be sent on HTTP pages, breaking session continuity

#### Typical Shopping Site Flow (HTTP + HTTPS Mixed)

```
HTTP pages:              HTTPS pages:
Product Category  -->    Authentication
Product 1         -->    Cart Input
Product 2         -->    Purchase
Product 3                Order Confirmation
```

The session cookie must work on both HTTP and HTTPS pages, making the Secure attribute impractical for the main session cookie.

---

### Countermeasures

#### 1. Set Secure Attribute on Session ID Cookies

**PHP** (`php.ini`):
```
session.cookie_secure = On
```

**Apache Tomcat**: Automatically sets Secure attribute for cookies on HTTPS connections.

**ASP.NET** (`web.config`):
```xml
<configuration>
  <system.web>
    <httpCookies requireSSL="true" />
  </system.web>
</configuration>
```

#### 2. Token-Based Approach for Mixed HTTP/HTTPS Sites

When Secure attribute cannot be applied to the session cookie (because of mixed HTTP/HTTPS):

- Use a **separate token cookie** with the Secure attribute for HTTPS-only authentication
- This is similar to the **session ID fixation** countermeasure from section 4.6.4
- The token cookie has the Secure attribute and is only sent over HTTPS
- HTTPS pages verify both the session cookie AND the token

**Token generation (PHP)** -- `/48/48-001.php`:
```php
<?php
// Generate random token using openssl
function getToken() {
  $s = openssl_random_pseudo_bytes(24);
  return base64_encode($s);
}

session_regenerate_id(true);    // Regenerate session ID
$token = getToken();            // Generate token
// Set token cookie with Secure and HttpOnly flags
setcookie('token', $token, 0, '/', '', true, true);
$_SESSION['token'] = $token;
?>
```

**Token verification (PHP)** -- `/48/48-002.php`:
```php
<?php
session_start();
// Get token from cookie
$token = @$_COOKIE['token'];
if (empty($token) || ! hash_equals(@$_SESSION['token'], $token)) {
  die('Token error');
}
?>
<body>Token check OK. Authentication confirmed.</body>
```

**Key points**:
- Token is generated on HTTPS and stored in both a Secure cookie and the session
- Token comparison uses `hash_equals()` to prevent **timing attacks**
- `hash_equals()` was added in PHP 5.6 for constant-time string comparison
- HTTPS pages verify the token; HTTP pages work normally with just the session cookie

#### Verification Flow

1. Visit `/48/48-001.php` over HTTPS -- token is generated and set
2. Visit a regular page -- authentication state is confirmed
3. Visit `/48/48-002.php` over HTTP (non-TLS) -- token cannot be received (Secure flag), so error is displayed
4. This confirms the Secure attribute is working correctly

---

## Cookie Attributes Beyond Secure

### Domain Attribute

- Default (not specified) is the safest -- cookie only sent to the exact issuing domain
- Setting Domain attribute allows cookie sharing across subdomains
- **Do not set Domain attribute** unless there is a specific reason to share cookies across subdomains
- PHP session cookies do not need Domain attribute specification

### Path Attribute

- PHP session ID defaults to `path=/`
- Normally this is fine; only change if you need directory-level isolation
- Be aware that JavaScript from the same origin can still access cookies regardless of Path
- Path attribute is covered in section 3.2

### Expires Attribute

- Session cookies (no Expires) are deleted when the browser closes
- Setting Expires creates **persistent cookies** that survive browser restarts
- Persistent cookies can maintain login state across sessions
- For auto-login functionality details, see section 5.1.4

### HttpOnly Attribute

- Prevents JavaScript from accessing the cookie via `document.cookie`
- Important defense against **XSS-based session hijacking**
- Session ID cookies should always have HttpOnly set
- Does not prevent the cookie from being sent in HTTP requests -- only blocks JavaScript access
- Some older documentation claims HttpOnly affects XST (Cross-Site Tracing), but this is outdated

**PHP** (`php.ini`):
```
session.cookie_httponly = On
```

---

## Summary

This section covered cookie-related vulnerabilities. Key principles:

- **Cookies should only store session IDs**, not application data
- **HTTPS-only applications** must set the **Secure attribute** on session cookies
- For **mixed HTTP/HTTPS sites**, use a **token-based approach** with a Secure-flagged token cookie
- Always set **HttpOnly** on session cookies to mitigate XSS impact
- Leave **Domain attribute** unset unless cross-subdomain sharing is specifically needed

---

## Key Takeaways

- Storing sensitive data in cookies (instead of server-side sessions) enables tampering and information leakage
- The Secure attribute ensures cookies are only transmitted over HTTPS -- without it, session cookies can be sniffed on public networks
- Mixed HTTP/HTTPS sites should use a separate Secure token cookie for authentication verification on HTTPS pages
- `hash_equals()` should be used for token comparison to prevent timing attacks
- HttpOnly prevents JavaScript access to cookies, reducing XSS impact
- Cookie attributes (Domain, Path, Expires, Secure, HttpOnly) each serve specific security purposes and should be configured deliberately
