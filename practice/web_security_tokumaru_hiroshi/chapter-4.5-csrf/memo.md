# CSRF Practice -- Quick Reference

## DVWA URL

```
http://localhost:8080/vulnerabilities/csrf/
```

## CSRF Attack URL (Low Level)

```
http://localhost:8080/vulnerabilities/csrf/?password_new=hacked&password_conf=hacked&Change=Change
```

Open this URL (or load `csrf-demo.html`) while logged into DVWA to trigger the password change.

---

## Security Level Comparison

| Level | Defense | Can csrf-demo.html Succeed? | Bypass |
|---|---|---|---|
| Low | No protection | Yes | N/A |
| Medium | Referer header check | No (wrong Referer) | Modify Referer in ZAP proxy |
| High | Anti-CSRF token (user_token) | No (missing valid token) | Would need XSS to steal token |
| Impossible | Token + current password re-entry | No | Cannot bypass without knowing current password |

---

## Book Chapter Reference

- **4.5.1** -- CSRF (Cross-Site Request Forgery): attack mechanism, trap pages, token-based defense
- **4.5.2** -- Clickjacking: UI redress attacks using iframes, X-Frame-Options header defense

---

## CSRF vs XSS

| | CSRF | XSS |
|---|---|---|
| What is exploited | Server's trust in the browser's cookies | User's trust in the website |
| Attacker's goal | Force the victim to perform an unintended action | Execute arbitrary scripts in the victim's browser |
| Requires script execution? | No (a simple GET/POST request is enough) | Yes (JavaScript must run in the target page context) |
| Can read response data? | No (attacker sends a blind request) | Yes (script runs same-origin, can read page content) |
| Defense | Anti-CSRF token, SameSite cookies, password re-entry | Output escaping, CSP, input validation |
| Relationship | XSS can defeat CSRF defenses by stealing tokens | CSRF cannot be used to perform XSS |

---

## Token Generation Example (from Book)

```php
// Generate a CSRF token and store it in the session
$token = sha1(uniqid(mt_rand(), true));
$_SESSION['csrf_token'] = $token;
```

```html
<!-- Embed the token in the form as a hidden field -->
<input type="hidden" name="user_token" value="<?php echo $token; ?>">
```

```php
// Server-side validation on form submission
if ($_REQUEST['user_token'] !== $_SESSION['csrf_token']) {
    die('CSRF token validation failed.');
}
```

---

## Quick Commands

```bash
# Start DVWA
docker compose up -d

# Open csrf-demo.html in Firefox (must be logged into DVWA first)
open csrf-demo.html

# Check DVWA logs for password change requests
docker logs dvwa 2>&1 | grep csrf
```
