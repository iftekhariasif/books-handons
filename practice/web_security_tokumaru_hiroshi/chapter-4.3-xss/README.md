# Chapter 4.3: XSS (Cross-Site Scripting) -- Hands-On Practice

## Book Reference

Chapter 4.3 of **"Safe Web Application Development"** (体系的に学ぶ 安全なWebアプリケーションの作り方) by Tokumaru Hiroshi (徳丸浩).

## Prerequisites

- Docker is running (`docker compose up -d` from the `chapter-2` setup folder)
- DVWA is accessible and you are logged in (default credentials: `admin` / `password`)
- OWASP ZAP is running on port `8081`
- FoxyProxy is active and routing browser traffic through ZAP

## What You'll Learn

- Cookie theft via JavaScript injection
- JavaScript injection into HTML pages
- The difference between reflected XSS and stored XSS
- Page defacement through XSS
- Proper HTML escaping as the primary countermeasure

## DVWA Modules Covered

| Module | Path |
|---|---|
| XSS (Reflected) | `/vulnerabilities/xss_r/` |
| XSS (Stored) | `/vulnerabilities/xss_s/` |

---

## Exercise Steps

### Low Level

Set DVWA Security to **Low** via `DVWA Security` in the left menu.

**Reflected XSS:**

1. Navigate to **DVWA > XSS (Reflected)**.
2. In the "What's your name?" field, enter the following payload and submit:
   ```
   <script>alert('XSS')</script>
   ```
3. Observe the alert popup. This confirms that the JavaScript you entered executed in the browser -- the application did not sanitize your input at all.
4. Now try cookie theft. Enter the following payload:
   ```
   <script>alert(document.cookie)</script>
   ```
5. The alert popup now displays your session cookie. In a real attack, this cookie would be sent to an attacker-controlled server, enabling session hijacking.

**Stored XSS:**

6. Navigate to **DVWA > XSS (Stored)**.
7. In the "Name" field, enter `Hacker`. In the "Message" field, enter:
   ```
   <script>alert('Stored XSS')</script>
   ```
8. Submit the form. The alert fires immediately.
9. Navigate away from the page, then come back. Notice the alert fires again -- the payload is stored in the database and executes every time anyone visits the page.

---

### Medium Level

Set DVWA Security to **Medium**.

1. Go to **DVWA > XSS (Reflected)** and try the basic payload again:
   ```
   <script>alert('XSS')</script>
   ```
   Nothing happens. The application now strips `<script>` tags from the input.

2. Try a **mixed case bypass**:
   ```
   <Script>alert('XSS')</Script>
   ```
   The alert fires. The filter only matches the exact lowercase string `<script>` and misses case variations.

3. Try a **nested tag bypass**:
   ```
   <scr<script>ipt>alert('XSS')</scr</script>ipt>
   ```
   The alert fires. When the filter removes `<script>` from the middle, the remaining characters reassemble into a valid `<script>` tag.

4. Try an **event handler bypass**:
   ```
   <img src=x onerror=alert('XSS')>
   ```
   The alert fires. The filter only targets `<script>` tags and ignores other HTML elements with JavaScript event handlers entirely.

---

### High Level

Set DVWA Security to **High**.

1. Try the basic `<script>` payload -- it does not work. Try mixed case and nested variations -- they also do not work. The application uses a more robust filter (likely a regex with the `i` flag) that blocks `<script>` in all forms.

2. Try an **event handler** payload:
   ```
   <img src=x onerror=alert('XSS')>
   ```
   The alert fires. The filter focuses exclusively on the `<script>` tag and does not account for other vectors.

3. Try another event handler variant:
   ```
   <svg onload=alert('XSS')>
   ```
   This also fires. Any HTML element with an event handler attribute bypasses the script-tag-only filter.

---

### Impossible Level

Set DVWA Security to **Impossible**.

1. Try every payload from the previous levels -- none of them work. The output is rendered as plain text, not as HTML.

2. Click **"View Source"** at the bottom of the page to see the secure code.

3. Note the key function call:
   ```php
   htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
   ```
   This is the book's recommended fix (Chapter 4.3). It converts special characters (`<`, `>`, `"`, `'`, `&`) into their HTML entity equivalents, preventing any injected content from being interpreted as HTML or JavaScript.

---

## What to Observe in ZAP

While performing the exercises above, switch to OWASP ZAP and examine the traffic:

- **Request parameters**: Look at the URL query string (for reflected XSS) or the POST request body (for stored XSS). You will see your XSS payload transmitted as-is.
- **Response body**: Inspect the HTML response. At the Low level, you will see your payload embedded directly in the HTML without any escaping. At the Impossible level, you will see the payload converted to HTML entities (e.g., `&lt;script&gt;`).
- **Compare across security levels**: Submit the same payload at each level and compare how the response HTML differs. This makes the filtering behavior visible.

---

## Book Connection

| Practice exercise | Book equivalent |
|---|---|
| Reflected XSS in the name field | Book's `43-001.php` example (section 4.3.1) |
| `htmlspecialchars()` in Impossible level | Book's primary recommended countermeasure (section 4.3.2) |
| Cookie theft via `document.cookie` | Book's session hijacking scenario |
| Stored XSS persisting across visits | Book's persistent XSS discussion |

---

## Key Takeaways

- XSS occurs when user input is embedded into HTML output without proper escaping.
- Reflected XSS appears in the immediate response to a crafted request. Stored XSS persists in the database and affects all visitors.
- Blacklist-based filtering (blocking specific tags) is unreliable. Attackers can bypass it with case variations, nested tags, and alternative HTML elements.
- The correct defense is output escaping using `htmlspecialchars()` with `ENT_QUOTES` and explicit charset (`UTF-8`). This is a whitelist approach -- only safe characters pass through unmodified.
- Cookie theft via XSS enables session hijacking. This is why the `HttpOnly` cookie flag exists as a defense-in-depth measure (covered in Chapter 4.6).
- Always validate and escape at the point of output, not at the point of input.
