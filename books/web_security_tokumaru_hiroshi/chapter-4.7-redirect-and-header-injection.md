# Chapter 4.7: Redirect Vulnerabilities & HTTP Header Injection

This section covers vulnerabilities related to redirect processing in web applications, including open redirect and HTTP header injection attacks.

---

## 4.7 Overview: Redirect Processing Vulnerabilities

Web applications sometimes redirect users to URLs specified externally via parameters. A common example is redirecting to a saved URL after login:

```
https://www.google.com/accounts/ServiceLogin?continue=https://mail.google.com/mail/
```

Two main vulnerability types arise from redirect processing:

- **Open Redirect Vulnerability**
- **HTTP Header Injection Vulnerability**

---

## 4.7.1 Open Redirect

### Overview

- Some web applications allow redirect destinations to be specified by external parameters
- If the redirect destination can be set to any domain, it is called an **Open Redirect vulnerability**
- Attackers can abuse this to redirect users to phishing sites without their knowledge
- This can be exploited for **phishing attacks** -- users trust the original domain but end up on a malicious site

### Open Redirect Example

```
http://example.jp/?continue=http://trap.example.com/
```
The user visits a trusted URL, but gets redirected to `http://trap.example.com/`.

---

### Summary of Open Redirect Vulnerability

| Aspect | Details |
|--------|---------|
| **Origin** | External URL specified in redirect-capable location |
| **Affected Pages** | Pages that redirect based on externally-provided URLs; phishing can lead users to believe they are on a trusted site |
| **Impact Types** | Redirected to phishing site; tricked into entering credentials; device driver/malware download |
| **Severity** | Medium |
| **User Involvement** | Moderate -- requires clicking a link |
| **Countermeasures** | Fix redirect destination; do not directly specify URLs; only allow redirects to pre-approved domains |

---

### Attack Method and Impact

A sample login application with redirect functionality demonstrates the attack:

#### Vulnerable Code (PHP)

**47-001.php** (entry point):
```php
<?php
  $url = filter_input(INPUT_GET, 'url');
  if (empty($url)) {
    $url = 'http://example.jp/47/47-003.php';
  }
?>
<html>
<head><title>Please Login</title></head>
<body>
<form action="47-002.php" method="POST">
  User: <input type="text" name="id"><br>
  Password: <input type="password" name="pwd"><br>
  <input type="hidden" name="url"
   value="<?php echo htmlspecialchars($url, ENT_COMPAT, 'UTF-8') ?>">
  <input type="submit" value="Login">
</form>
</body>
</html>
```

**47-002.php** (authentication handler):
```php
<?php
  $id = filter_input(INPUT_POST, 'id');
  $pwd = filter_input(INPUT_POST, 'pwd');
  $url = filter_input(INPUT_POST, 'url');
  // Login check (ID and password not validated in demo)
  if (! empty($id) && ! empty($pwd)) {
    header('Location: ' . $url);
    exit();
  }
?>
<body>
  ID or password is wrong
  <a href="47-001.php">Re-login</a>
</body>
```

**47-003.php** (success page):
```html
<html>
<head><title>Login Successful</title></head>
<body>
Login completed.
</body>
</html>
```

#### The Attack Flow

1. Attacker creates a fake login page at `http://trap.example.com/47/47-900.php`
2. Attacker crafts a URL: `http://example.jp/47/47-001.php?url=http://trap.example.com/47/47-900.php`
3. User sees a legitimate domain, enters credentials
4. After "successful" login, user is redirected to the **trap site's fake login page** showing "ID or password is wrong, please re-enter"
5. User re-enters credentials on the **attacker's site**, unknowingly giving away their information

**Key insight**: The domain name is real, and if HTTPS is used, no certificate errors appear, so users trust it completely.

---

### Root Cause

Open redirect vulnerability occurs when **both** of the following conditions are met:

- Redirect destination URL can be specified externally
- Redirect destination domain is not validated

**Note**: Even if a site intentionally redirects to external domains (e.g., banner ads), this is not a vulnerability if it is by design.

---

### Countermeasures

Apply one or more of the following:

1. **Fix the redirect destination URL** -- do not accept external URLs at all
2. **Use indirect references** (page numbers/IDs) instead of direct URL specification
3. **Validate the redirect destination domain name**

---

### Domain Name Validation -- Common Mistakes

#### Failure Example 1: Substring match
```php
if (preg_match('/example\.jp/', $url) === 1) {
  // Check OK
}
```
**Problem**: Only checks if "example.jp" is contained anywhere in the URL. An attacker can bypass with:
```
http://trap.example.com/example.jp.php
```

#### Failure Example 2: Slash-based check
```php
if (preg_match('/^\//', $url) === 1) {
  // Check OK
}
```
**Problem**: Checks that URL starts with `/`, meaning it's a relative path. However, this URL bypasses it:
```
//trap.example.com/47/47-900.php
```
URLs starting with `//` are "network-path references" that specify host (FQDN) and below, allowing external domain redirects.

#### Failure Example 3: Prefix match
```php
if (preg_match('/^"http:\/\/example\.jp\//', $url) === 1) {
  // Check OK
}
```
**Problem**: Uses prefix-based regex matching, but URL `http://example.jp` could still be vulnerable to HTTP header injection attacks. Also, this method cannot handle the case where both HTTP and HTTPS should be allowed.

#### Recommended Approach
```php
if (preg_match('/\Ahttps?:\/\/example\.jp[\/$]/', $url) === 1) {
  // Check OK
}
```
- Uses `\A` for start-of-string anchor (not `^` which can be affected by multiline mode)
- `\s` is used for whitespace matching, `\z` for end
- `https?` matches both `http` and `https`
- The `[\/$]` ensures the URL actually belongs to the domain

---

### Cushion Pages

- Auction sites, SNS, and other sites where users post links use **cushion pages** instead of direct redirects
- A cushion page warns users: "You are about to leave this site. The link you are trying to access is: ..."
- Example: Yahoo! Auctions shows a cushion page before redirecting to external domains
- This helps prevent phishing by making the user aware of the domain transition

---

## 4.7.2 HTTP Header Injection

### Overview

HTTP header injection allows attackers to manipulate HTTP response headers by injecting newline characters into parameters that are used to construct headers. This affects:

- Redirect processing
- Cookie output
- Any HTTP response header output

Depending on the browser, the attack may result in one or both of:

- **Adding arbitrary response headers**
- **Creating a fake response body**

---

### Summary of HTTP Header Injection

| Aspect | Details |
|--------|---------|
| **Origin** | External parameters used to construct HTTP response headers |
| **Affected Pages** | Pages with redirect functionality; all pages in worst case |
| **Impact Types** | Arbitrary cookie creation, redirect to arbitrary URL, display alteration, arbitrary JavaScript execution (same as XSS) |
| **Severity** | Medium to High |
| **User Involvement** | Moderate -- requires visiting a crafted URL or clicking a link |
| **Countermeasures** | Do not directly output external parameters as HTTP response headers; check parameters for newline characters |

---

### Attack Method and Impact

#### Sample CGI (Perl) -- 47-020.cgi

```perl
#!/usr/bin/perl
use utf8;
use strict;
use CGI qw/-no_xhtml :standard/;   # CGI module usage
use Encode qw(encode decode);

my $cgi = new CGI;
my $url = $cgi->param('url');       # Get URL from query parameter

# URL prefix check for open redirect countermeasure (incomplete)
if ($url =~ /^http:\/\/example\.jp\//) {
  print "Location: $url\n\n";
  exit 0;
}

## If URL is incorrect, show error message
print <<END_OF_HTML;
Content-Type: text/html; charset=UTF-8

<body>
Bad URL
</body>
END_OF_HTML
```

#### Redirect to External Domain Attack

By injecting a newline (`%0D%0A`) into the URL parameter, an attacker can add a second `Location` header:

```
http://example.jp/47/47-020.cgi?url=http://example.jp/%0D%0ALocation:+http://trap.example.com/47/47-900.php
```

This produces two Location headers in the response:

```
Location: http://example.jp/
Location: http://trap.example.com/47/47-900.php
```

Apache takes the **last** `Location` header, so the user is redirected to the trap site.

---

### Arbitrary Cookie Creation

Using HTTP header injection, attackers can inject `Set-Cookie` headers:

```
http://example.jp/47/47-020.cgi?url=http://example.jp/47/47-003.php%0D%0ASet-Cookie:+SESSID=ABCD123
```

This adds a `Set-Cookie` header to the response:

```
Set-Cookie: SESSID=ABCD123
Location: http://example.jp/47/47-003.php
```

This enables **session fixation attacks** -- the attacker can set a known session ID on the victim's browser.

---

### Fake Page Display

Using a separate CGI (47-021.cgi) that outputs cookies based on a `pageid` parameter:

```perl
#!/usr/bin/perl
use utf8;
use strict;
use CGI qw/-no_xhtml :standard/;
use Encode qw(encode decode);

my $cgi = new CGI;
my $pageid = $cgi->param('pageid');

# Output with UTF-8 encoding
print encode('UTF-8', <<END_OF_HTML);
Content-Type: text/html; charset=UTF-8
Set-Cookie: PAGEID=$pageid

<body>
Cookie value has been set
</body>
END_OF_HTML
```

By injecting multiple newlines followed by HTML content after the `Set-Cookie` header, an attacker can inject an entirely **fake response body**. This is similar to XSS -- the attacker can display any content or execute JavaScript on the victim's browser.

---

### HTTP Response Splitting Attack (COLUMN)

- **HTTP Response Splitting Attack** uses HTTP header injection to poison caches on proxy servers
- HTTP/1.1 allows multiple requests to be sent together; the attacker uses header injection to add a complete fake response
- The proxy caches this fake response and serves it to other users
- This is called **CrLf injection** or **HTTP response splitting** in some contexts
- Cache poisoning can affect many users over a long period
- Reference: https://www.ipa.go.jp/security/vuln/websecurity.html

---

### HTTP Headers and Newlines (COLUMN)

- URLs and cookie values should not originally contain newline characters
- URL encoding should encode newlines as `%0D%0A`
- Cookie values should be percent-encoded as well
- If percent-encoded properly, newlines become `%0D%0A` in the output, preventing injection
- However, HTTP header injection vulnerabilities can still occur if encoding is not done properly

---

### Root Cause

- HTTP response headers are defined in text format, one header per line
- Headers are delimited by newline characters (`\r\n`)
- If external parameters containing newline characters are inserted into redirect URLs, cookie values, or other headers, the newlines become part of the HTTP response, creating new headers or body content

---

### Countermeasures

#### Strategy 1: Do not output external parameters as HTTP response headers

- Design the application so external parameters are never directly used in response headers
- Use indirect references (page numbers, IDs stored in databases) instead of direct URL specification
- Use web application framework session variables to store URLs

#### Strategy 2: Use dedicated APIs for redirect and cookie output

In CGI programs, using `print` can directly write to HTTP response headers, which is dangerous. Use dedicated library/framework functions instead:

| Language | Cookie Creation | Redirect | Response Header |
|----------|----------------|----------|-----------------|
| **PHP** | `setcookie` / `setrawcookie` | `header` (via `header` function) | `header` |
| **Perl (CGI.pm)** | `CGI::Cookie` | `redirect` | `header` |
| **Java Servlet** | `HttpServletResponse#addCookie` | `HttpServletResponse#sendRedirect` | `HttpServletResponse#setHeader` |
| **ASP.NET** | `Response.Cookies.Add` | `Response.Redirect` | `Response.AppendHeader` |

These library functions should handle header injection countermeasures automatically. However, always verify that the library actually checks for newline characters.

#### Strategy 3: Check parameters for newline characters

- Many APIs for generating HTTP response headers do not check for newline characters
- Some reasons: the API authors believe the responsibility lies with the application developer
- Two approaches:
  - **Treat newlines in URLs as errors**
  - **Percent-encode newlines in cookie values**

#### Recommended Redirect Function (PHP)

```php
<?php
function redirect($url) {
  // Check URL characters, treat invalid ones as error
  if (! mb_ereg("^[-_.!~*'();\/?:@&=+\$,%#a-zA-Z0-9]+$", $url)) {
    die('Bad URL');
  }
  header('Location: ' . $url);
}

// Usage example
$url = filter_input(INPUT_GET, 'url');
redirect($url);
?>
```

- The `redirect` function validates URL characters before issuing the `Location` header
- Only allows URL-safe characters (per RFC3986)
- The `mb_ereg` character class check also prevents newline injection
- IPv6 IP addresses with `[` and `]` may need additional handling

---

### PHP's header() Function Newline Check (COLUMN)

- PHP's `header()` function added newline checking in PHP 5.1.2 (for PHP 4.4.2+)
- In PHP 5.1.2-5.3, it rejected the `\n` (0x0A) linefeed character but not `\r` (0x0D) carriage return
- PHP 5.4.38, 5.5.22, 5.6.6 and later correctly check for both characters
- Red Hat Enterprise Linux (RHEL) and CentOS backported the fix to their packages
- As of the latest versions, PHP's `header()` function only checks for newlines in redirect operations
- It remains a good practice to implement your own validation as well

---

## 4.7.3 Summary of Redirect Processing Vulnerabilities

The two representative vulnerabilities from redirect processing are **Open Redirect** and **HTTP Header Injection**.

### Key Countermeasures

| # | Countermeasure |
|---|---------------|
| 1 | Use dedicated APIs (library functions) for redirect processing wherever possible |
| 2 | Apply **one or more** of the following: |
|   | -- Fix redirect destination (preferred) |
|   | -- When redirect URLs come from external input, always validate character set and domain name |

---

## Key Takeaways

- **Open Redirect** is exploited for phishing -- users trust the original domain but end up on attacker-controlled sites
- **HTTP Header Injection** is caused by newline characters in parameters being output as HTTP headers, allowing arbitrary header/body injection
- Always use framework/library functions for redirects and cookie output rather than manual header construction
- Validate redirect URLs: check the domain, use allowlists, or avoid accepting external URLs altogether
- Cushion pages are an effective secondary defense for sites that must link to external domains
- HTTP Response Splitting is an advanced form of header injection that poisons proxy caches
- PHP's `header()` function has historically had incomplete newline checks -- do not rely solely on it
