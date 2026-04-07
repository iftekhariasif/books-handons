# Chapter 4.6: Session Management Flaws

This section covers vulnerabilities related to session management in web applications -- session hijacking, predictable session IDs, session IDs embedded in URLs, and session fixation attacks.

---

## 4.6.1 Causes and Impact of Session Hijacking

### What Is Session Hijacking?

- For some reason, an attacker learns a legitimate user's session ID and gains unauthorized access by impersonating that user
- This is called **session hijacking**

### Three Methods to Obtain a Session ID

| Method | Description | Section |
|--------|-------------|---------|
| **Predicting the session ID** | Guessing the session ID generation algorithm | 4.6.2 |
| **Stealing the session ID** | Extracting the session ID from the victim | 4.6.3 |
| **Forcing a session ID** | Making the victim use a session ID chosen by the attacker (Session Fixation) | 4.6.4 |

### Ways to Steal a Session ID

- Cookie attribute misconfiguration causing leakage (see Chapter 3)
- Network-level interception of session ID (see Section 8.3)
- Cross-site scripting (XSS) (see Section 4.3.1)
- HTTP header injection (see Section 4.7.2)
- Session ID embedded in URL leaking via Referer header (see Section 4.6.3)
- PHP or browser/platform vulnerabilities causing leakage

### Impact of Session Hijacking

When an attacker hijacks a user's session, the following damage can occur:

- Viewing the victim's sensitive information (personal data, emails, etc.)
- Performing transactions on behalf of the victim (purchases, transfers, etc.)
- Posting, modifying settings, or changing account details as the victim

---

## 4.6.2 Predictable Session IDs

### Overview

- If the session ID generation algorithm has problems, an attacker may be able to predict a user's session ID and hijack their session
- The attack proceeds in 3 steps:
  1. Collect session IDs from the target application
  2. Build a hypothesis about the session ID's regularity/pattern
  3. Test predicted session IDs against the target application

### Common (Insecure) Session ID Generation Methods

Session IDs are often composed from these **predictable** data sources:

| Source | Problem |
|--------|---------|
| User ID or email address | Directly guessable |
| Remote IP address | Easily obtained |
| Date/time (UNIX timestamp or formatted date string) | Predictable |
| Sequential number | Trivially enumerable |

These may be used raw, or combined and then encoded (Base64) or hashed -- but the underlying predictability remains.

### Countermeasures

- **Use the session management mechanism provided by your web framework** (PHP, Java/J2EE, ASP.NET, etc.) rather than creating your own
- The difficulty of building a secure session ID generator from scratch is very high

### Improving PHP Session ID Randomness

Default PHP session ID generation uses MD5 hash of:
- Remote IP address
- Current time
- Random number (cryptographically strong random, not pseudorandom)

To improve entropy, configure `php.ini`:

```ini
[Session]
;; entropy_file -- use /dev/urandom on Linux/Unix
session.entropy_file = /dev/urandom
session.entropy_length = 32
```

> Note: `/dev/urandom` is available on Linux and most Unix systems. On Windows, PHP 5.3.3+ uses `session.entropy_length` to pull from the Windows Random API. PHP 5.4+ defaults to safe settings.

### Custom Session Management Pitfalls

- If you build your own session management, you may inadvertently introduce additional vulnerabilities beyond predictable IDs, such as:
  - SQL injection
  - Directory traversal
- Always prefer well-tested, framework-provided session management

---

## 4.6.3 Session ID Embedded in URLs

### Overview

- Session IDs may be stored in cookies or embedded in URLs
- PHP and Java support both; ASP.NET also supports URL-embedded session IDs
- Early mobile (NTT DoCoMo i-mode) browsers did not support cookies, leading to URL-based sessions

**Example of URL-embedded session ID:**

```
http://example.jp/mail/1237SESSID=2F3BE9A31F093C
```

### Why URL-Embedded Session IDs Are Dangerous

- The session ID is visible in the URL, making it susceptible to leakage via the **Referer header**
- If the session ID is guessed or leaked, it becomes a stepping stone for hijacking

### PHP Configuration for Session ID Storage

| php.ini Setting | Description | Default |
|-----------------|-------------|---------|
| `session.use_cookies` | Use cookies for session ID | On |
| `session.use_only_cookies` | Use cookies ONLY (reject URL-based) | On |
| `session.use_trans_sid` | Automatically embed session ID in URLs | Off |

**Behavior matrix:**

| Session ID Storage | use_cookies | use_only_cookies |
|--------------------|-------------|------------------|
| Store in cookies only | On | On |
| Cookie preferred, fall back to URL | On | Off |
| Store in URL only | Off | Off |

### Conditions for Referer-Based Session ID Leakage

Both conditions must be met:
1. URL-embedded session IDs are used
2. External links exist on the page, or the user can create links

### Attack Scenario via Referer

1. Attacker sends a crafted email (e.g., web mail) to the victim containing a link to an external site
2. Victim clicks the link while logged in
3. The browser sends the Referer header (containing the session ID in the URL) to the attacker's site
4. Attacker extracts the session ID from the Referer and hijacks the session

### Accidental Leakage (Non-Malicious)

- Users may unknowingly share session IDs by copying URLs into SNS, bulletin boards, or blogs
- Crawlers may index URLs containing session IDs
- Multiple real-world incidents of information leakage have occurred this way

### Sample Code Demonstrating the Vulnerability

**46-001.php (Start page):**
```php
<?php
session_start();
?><body> <a href='46-002.php'>Next</a> </body>
```

**46-002.php (Page with external link):**
```php
<?php
session_start();
?><body>
  <a href="http://trap.example.com/46/46-900.cgi">External Link</a>
</body>
```

**46-900.cgi (Attacker's collection server -- Perl):**
```perl
#!/usr/bin/perl
use utf8;
use strict;
use CGI qw/-no_xhtml :standard/;
use Encode qw/encode/;

my $e_referer = escapeHTML(referer());

print encode('UTF-8', <<END_OF_HTML);
Content-Type: text/html; charset=UTF-8

<body>
Referer is: $e_referer<br>
</body>
END_OF_HTML
```

**Vulnerable `.user.ini`:**
```ini
session.use_cookies=Off
session.use_only_cookies=Off
session.use_trans_sid=On
```

### Countermeasures

- Configure session IDs to be stored in **cookies only**, not in URLs

**PHP -- recommended `php.ini` settings:**
```ini
[Session]
session.use_cookies = 1
session.use_only_cookies = 1
```

**Java Servlet (J2EE):**
- Avoid using URL rewriting (J2EE term for URL-embedded session IDs)
- Do not use `HttpServletResponse.encodeURL()` or `encodeRedirectURL()` methods, which explicitly embed session IDs in URLs

**ASP.NET:**
- Default stores session ID in cookies; configure `web.config` to enforce:

```xml
<?xml version="1.0" encoding="UTF-8" ?>
<configuration>
  <system.web>
    <sessionState cookieless="false" />
  </system.web>
</configuration>
```

### Why URL-Embedded Session IDs Persist (Historical)

- Around 2000, a "cookie phobia" arose due to privacy concerns
- NTT DoCoMo's early mobile browsers did not support cookies
- Today, mobile browsers support cookies, so there is no reason to use URL-embedded session IDs

### Impact

- Same as session hijacking: unauthorized access to user data, transactions, and account manipulation

---

## 4.6.4 Session Fixation (Session ID Fixing)

### Overview

- Instead of stealing a session ID, the attacker **forces the victim to use a session ID that the attacker already knows**
- This is called a **Session Fixation Attack**

### Attack Steps

1. Attacker obtains a session ID (e.g., by accessing the login page)
2. Attacker tricks the victim into using that session ID (e.g., via a crafted URL)
3. Victim logs in to the application using the attacker's session ID
4. Attacker uses the now-authenticated session ID to access the application as the victim

### Impact

- Same as session hijacking: identity theft, information leakage, unauthorized transactions, data modification/deletion

### Session Fixation Attack Summary

| Aspect | Details |
|--------|---------|
| **Where it occurs** | Login processing |
| **Affected pages** | All pages using sessions; pages requiring authentication with access to confidential data are most impacted |
| **Severity** | Medium to High |
| **Likelihood** | High (for URL-based sessions); Medium otherwise |
| **User involvement** | High -- requires user to visit a crafted URL and log in at the real site |
| **Countermeasure** | Change the session ID upon login |

### Sample Script Demonstrating the Vulnerability

**46-010.php (Login form):**
```php
<?php
session_start();
?><body>
<form action="46-011.php" method="POST">
  User ID: <input name="id" type="text"><br>
  <input type="submit" value="Login">
</form>
</body>
```

**46-011.php (Login handler -- VULNERABLE):**
```php
<?php
session_start();
$id = filter_input(INPUT_POST, 'id');  // must log in to proceed
$_SESSION['id'] = $id;  // store user ID in session
?>
<?php echo htmlspecialchars($id, ENT_COMPAT, 'UTF-8'); ?>, login succeeded<br>
<a href="46-012.php">Personal Info</a>
</body>
```

**46-012.php (Personal info display):**
```php
<?php
session_start();
?>User ID: <?php echo htmlspecialchars($_SESSION['id'],
  ENT_COMPAT, 'UTF-8'); ?><br>
</body>
```

### Attack Walkthrough

1. Attacker crafts a URL with a predetermined session ID:
   ```
   http://example.jp/463/46-010.php?PHPSESSID=ABC
   ```
2. Attacker sends this URL to the victim (via email, social media, etc.)
3. Victim opens the URL, enters their credentials, and logs in
4. The session ID `PHPSESSID=ABC` is now authenticated
5. Attacker visits the personal info page using the same session ID:
   ```
   http://example.jp/463/46-012.php?PHPSESSID=ABC
   ```
6. Attacker now views the victim's personal information

### Cookie-Only Sites Can Still Be Vulnerable

- Even if session IDs are stored only in cookies, session fixation may still be possible
- Attack vectors for forcing cookies on the victim include:
  - **Cookie Monster Bug** (browser vulnerabilities with domain scope, e.g., older Internet Explorer)
  - **Cross-site scripting (XSS)** (Section 4.3)
  - **HTTP header injection** (Section 4.7.2)
  - Using OWASP ZAP or similar proxy tools to set cookies at the network level
  - Even with HTTPS, cookie values can be forced via HTTP on the same domain

### Session Adoption

- **Session Adoption** is a behavior where PHP (and some other frameworks) accepts an **unknown/externally-created session ID** and creates a new session for it
- This means an attacker can invent any session ID string, and PHP will happily adopt it
- ASP.NET and Tomcat do **not** exhibit session adoption (they reject unknown session IDs)
- Applications without session adoption require the attacker to first obtain a valid session ID from the server

> Note: Session adoption is disabled in PHP 5.4+ when `php.use_strict_mode=On`.

### Pre-Login Session Fixation

- Session fixation can also occur on pages that **do not require authentication** but use session variables
- Example: a shopping cart or personal information input form that stores data in session variables before login
- Attacker monitors the URL periodically; when the victim enters data, the attacker sees it via the shared session
- Impact is limited to information leakage of data the user entered (not full account takeover, since no login occurred)

### Countermeasures

#### 1. Change the Session ID Upon Login (Primary Defense)

Use `session_regenerate_id()` in PHP:

```php
bool session_regenerate_id([bool $delete_old_session = false])
```

- The optional argument controls whether to delete the old session; set to `true` to prevent reuse

**Fixed login handler (46-011a.php):**
```php
<?php
session_start();
$id = filter_input(INPUT_POST, 'id');  // login processing
session_regenerate_id(true);  // regenerate session ID
$_SESSION['id'] = $id;  // store user ID in new session
?>
<?php echo htmlspecialchars($id, ENT_COMPAT, 'UTF-8'); ?>, login succeeded<br>
<a href="46-012.php">Personal Info</a>
</body>
```

#### 2. Use Tokens When Session ID Cannot Be Changed

- Some development languages or middleware do not support changing the session ID
- In such cases, use a **random token** stored in both a cookie and the session:
  1. At login, generate a cryptographically random token
  2. Store the token in a cookie AND in the session
  3. On each subsequent page, compare the cookie token with the session token
  4. If they do not match, reject the request (authentication error)

**Token generation (46-015.php):**
```php
// Token generation
function getToken() {
    $s = openssl_random_pseudo_byte(24);
    return base64_encode($s);
}

// After authentication is confirmed:
session_start();
$token = getToken();              // generate token
setcookie('token', $token);       // store in cookie
$_SESSION['token'] = $token;      // store in session
```

**Token verification (46-016.php):**
```php
<?php
session_start();
// User ID verification omitted
// Token verification
$token = @$_COOKIE['token'];
if (empty($token) || $token !== @$_SESSION['token']) {
    die("Authentication error");
}
?>
<body> Authentication succeeded </body>
```

> For PHP 5 and below, use `hash_equals()` for timing-safe token comparison instead of `!==`. The `hash_equals` function prevents timing attacks.

#### 3. Pre-Login Session Fixation Countermeasures

- Changing the session ID at login does not fully protect against session fixation on pre-login pages
- Use a **hidden parameter** or per-page session variable to store sensitive form data
- Alternatively, change the session ID at every critical transition, and store sensitive data in a new session variable each time
- Caveat: changing the session ID mid-response may cause the browser to fail to accept the new cookie, breaking the session

### General Countermeasures Summary

| Countermeasure | Purpose |
|----------------|---------|
| Do not embed session IDs in URLs | Prevents Referer leakage |
| Do not use browsers with Cookie Monster Bug | Prevents forced cookie setting |
| Eliminate XSS, HTTP header injection vulnerabilities | Prevents cookie theft/forcing |
| Set proper cookie domain (avoid broad JP domain, regional JP domain) | Prevents cross-subdomain attacks |
| Regenerate session ID on login | Primary defense against fixation |
| Use token-based verification as fallback | When session ID regeneration is unavailable |

---

## Key Takeaways

1. **Never roll your own session management** -- use framework-provided mechanisms (PHP sessions, J2EE HttpSession, ASP.NET session state)
2. **Always store session IDs in cookies only** -- never embed them in URLs
3. **Regenerate the session ID upon login** to prevent session fixation attacks
4. **Use cryptographically strong randomness** for session ID generation (`/dev/urandom`, `openssl_random_pseudo_bytes`)
5. **Eliminate XSS and HTTP header injection** vulnerabilities, as these enable session ID theft and cookie manipulation
6. **Be aware of session adoption** in PHP -- unknown session IDs are accepted by default in older versions; use `session.use_strict_mode=On` in PHP 5.4+
7. **Referer header leaks URLs** -- if session IDs are in URLs and pages contain external links, session IDs will leak to third-party servers
8. **Token-based verification** is a viable alternative when session ID regeneration is not possible
