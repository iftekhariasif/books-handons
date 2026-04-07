# Chapter 4.5: CSRF (Cross-Site Request Forgery) -- Hands-On Practice

Book reference: Chapter 4.5 of "Safe Web Application Development" (Taiikuteki Web Application no Tsukurikata) by Tokumaru Hiroshi

---

## Prerequisites

- Docker running with DVWA container (`docker compose up -d`)
- DVWA logged in (admin / password) at `http://localhost:8080`
- OWASP ZAP running and intercepting traffic
- FoxyProxy enabled in Firefox, routing through ZAP

## What You'll Learn

- How CSRF attacks work -- forging requests from external pages
- Why the browser automatically attaches session cookies to cross-origin requests
- How anti-CSRF tokens prevent forged requests
- The difference between CSRF and XSS
- Defense layers: token validation and password re-entry

## DVWA Module

**CSRF** -- `http://localhost:8080/vulnerabilities/csrf/`

---

## Exercise Steps

### Low Level

1. Go to **DVWA > CSRF**. You will see a simple password change form.
2. Change the password normally to `test123` and click **Change**.
3. Observe the request in ZAP. It is a GET request with the full URL:
   ```
   /vulnerabilities/csrf/?password_new=test123&password_conf=test123&Change=Change
   ```
   Note that there is no CSRF token -- the password change relies entirely on the session cookie.
4. Now open the file `csrf-demo.html` (from this folder) in Firefox **while still logged into DVWA**.
5. The page loads and silently fires a request to DVWA via a hidden `<img>` tag. You can also click the link to trigger it manually.
6. Your DVWA password has been changed to `hacked` -- without any confirmation dialog or user interaction.
7. This is CSRF: the victim's browser sends the forged request with their valid session cookie automatically. The server cannot distinguish it from a legitimate request.
8. Reset your password back to `password` through the DVWA CSRF page.

### Medium Level

1. Set DVWA security to **Medium** (DVWA Security > Medium > Submit).
2. The application now checks the HTTP **Referer** header. The Referer must contain the server hostname.
3. Open `csrf-demo.html` directly in the browser -- the attack fails because the Referer header shows a `file://` origin or a different host.
4. In ZAP, intercept the request from `csrf-demo.html` and modify the Referer header to include `localhost`:
   ```
   Referer: http://localhost:8080/vulnerabilities/csrf/
   ```
5. With the modified Referer, the password change succeeds.
6. This demonstrates the book's warning in Chapter 4.5: **Referer checking is NOT a reliable CSRF defense**. The header can be manipulated by proxies, and some browsers/extensions suppress it entirely.

### High Level

1. Set DVWA security to **High**.
2. The application now uses **anti-CSRF tokens**.
3. Right-click the password change form and select **Inspect Element**. Look for a hidden input field:
   ```html
   <input type="hidden" name="user_token" value="abc123...">
   ```
4. Each page load generates a unique token. The server validates that the submitted token matches the one it issued.
5. Try opening `csrf-demo.html` -- the attack fails because the forged request does not include a valid `user_token`.
6. An attacker on an external page cannot read the token from the DVWA page (blocked by the Same-Origin Policy).
7. This is the book's recommended primary defense: the **secret token method**.

### Impossible Level

1. Set DVWA security to **Impossible**.
2. The form now requires the **current password** before allowing a change.
3. Even if an attacker somehow obtained the CSRF token, they still cannot complete the request without knowing the user's current password.
4. View the page source -- the form combines token validation with password re-entry. This is the strongest defense described in the book.

---

## What to Observe in ZAP

| Security Level | What to Look For |
|---|---|
| Low | No `user_token` parameter in the request. Password changes via a simple GET URL. |
| Medium | Check the `Referer` header -- the server rejects requests without the correct Referer. |
| High | A `user_token` parameter appears in the request. Each page load generates a new token value. |
| Impossible | Both `user_token` and `password_current` parameters are present in the request. |

- Compare the Referer headers across different attack attempts.
- In ZAP's History tab, filter for `/vulnerabilities/csrf/` to see all related requests.

---

## Book Connection

| Practice Element | Book Reference |
|---|---|
| `csrf-demo.html` trap page | Corresponds to the book's 45-004.html example (external attack page) |
| Secret token (user_token) | Book's primary CSRF countermeasure (Chapter 4.5.1) |
| Password re-entry requirement | Book's additional defense layer for sensitive operations |
| Referer check limitation | Book's explicit warning that Referer-based defense is insufficient |
| GET-based password change | Book's example of why sensitive actions must not use GET requests |

---

## Key Takeaways

1. **CSRF exploits the server's trust in the browser's cookies.** The browser automatically attaches cookies to every request to a domain, regardless of which page initiated the request.
2. **GET requests for state-changing operations are especially dangerous.** They can be triggered by `<img>` tags, links, or any element that loads a URL.
3. **Referer checking is fragile.** It can be bypassed, stripped, or suppressed. Do not rely on it as a sole defense.
4. **Anti-CSRF tokens are the standard defense.** A unique, unpredictable token tied to the user's session ensures that only pages served by the application can submit valid requests.
5. **Password re-entry adds a second layer.** For critical operations (password change, email change, payment), requiring the current password stops CSRF even if the token is compromised.
6. **CSRF is not XSS.** CSRF tricks the browser into making a request the user did not intend. XSS injects malicious scripts that run in the context of the target page. However, XSS can be used to steal CSRF tokens, which is why both must be addressed.
