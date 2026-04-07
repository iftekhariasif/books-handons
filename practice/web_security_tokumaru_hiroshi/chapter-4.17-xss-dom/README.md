# Chapter 4.17: DOM-Based XSS (JavaScript Issues) -- Hands-On Practice

## Book Reference

Chapter 4.17 of **"Safe Web Application Development"** (体系的に学ぶ 安全なWebアプリケーションの作り方) by **Tokumaru Hiroshi** (徳丸 浩).

This chapter covers JavaScript-specific security issues, focusing on how client-side code can introduce vulnerabilities that are invisible to server-side defenses.

---

## Prerequisites

Before starting, ensure the following are ready:

- **Docker** is running and DVWA container is up
- **DVWA** is accessible in the browser and you are logged in (admin / password)
- **OWASP ZAP** is running and configured as a proxy
- **FoxyProxy** browser extension is active, routing traffic through ZAP
- DVWA Security Level can be changed at: `DVWA Security` in the left menu

---

## What You'll Learn

- How **DOM-based XSS** differs from reflected XSS and stored XSS
- Why `innerHTML` is dangerous when used with user-controlled input
- Why `document.write` is dangerous when used with URL parameters
- How JavaScript manipulates the DOM unsafely by reading from sources like `location.search` and `location.hash`
- How `textContent` and proper encoding serve as safe alternatives
- Why DOM XSS is invisible to server-side WAFs, logging, and proxy tools like ZAP

---

## DVWA Module: XSS (DOM)

Navigate to: **DVWA > XSS (DOM)** (left sidebar)

URL path: `http://localhost/vulnerabilities/xss_d/`

---

## Exercise Steps

### Low Level

Set DVWA Security to **Low**.

1. Go to **DVWA > XSS (DOM)**.

2. Notice the **language selector dropdown**. Select a language (e.g., English) and observe the URL. It changes to include a query parameter:
   ```
   http://localhost/vulnerabilities/xss_d/?default=English
   ```

3. Now manually change the URL parameter to inject a script tag:
   ```
   ?default=<script>alert('DOM XSS')</script>
   ```
   Full URL:
   ```
   http://localhost/vulnerabilities/xss_d/?default=<script>alert('DOM XSS')</script>
   ```

4. **The alert fires.** This is DOM-based XSS. The browser executed your injected script.

5. **Key difference from reflected XSS:** the payload never reaches the server. Open ZAP and inspect the HTTP response body for this request. The server response does **NOT** contain `<script>alert('DOM XSS')</script>`. The server returned the same page as always -- it is the client-side JavaScript that read the URL and wrote the payload into the DOM.

6. Examine the page source (View Source in DVWA or browser). Look for JavaScript that reads from `document.location` and writes to the page using `document.write` or `innerHTML`. This is the vulnerable pattern.

7. Try a different payload that does not rely on `<script>` tags:
   ```
   ?default=<img src=x onerror=alert(document.cookie)>
   ```
   This also fires because the DOM insertion does not sanitize HTML elements or event handlers.

8. This class of vulnerability is **harder to detect server-side** because the malicious payload stays entirely within the browser. The server sees only the raw URL parameter string but never processes or reflects it in the response.

---

### Medium Level

Set DVWA Security to **Medium**.

1. The application now **filters `<script>` from the URL**. Try the Low-level payload:
   ```
   ?default=<script>alert('XSS')</script>
   ```
   It no longer works. The string `<script>` is stripped or blocked.

2. However, the filter is incomplete. Try breaking out of the existing HTML structure:
   ```
   ?default=</option></select><img src=x onerror=alert('XSS')>
   ```
   This works because:
   - `</option></select>` closes the existing `<option>` and `<select>` elements in the DOM
   - `<img src=x onerror=alert('XSS')>` injects a new element with an event handler
   - The filter blocks `<script>` but does **not** block other HTML elements like `<img>`

3. The lesson: **blacklist-based filtering is always incomplete**. Blocking one tag while allowing arbitrary HTML insertion is not a defense.

---

### High Level

Set DVWA Security to **High**.

1. The application now uses a **whitelist**. It only allows specific language values: `English`, `French`, `Spanish`, `German`. Any other value in the `default` parameter is rejected.

2. Direct injection via the query parameter no longer works. Try any payload in `?default=...` and it will be rejected.

3. However, try using a **URL fragment** (the `#` hash portion):
   ```
   ?default=English#<script>alert('XSS')</script>
   ```
   Full URL:
   ```
   http://localhost/vulnerabilities/xss_d/?default=English#<script>alert('XSS')</script>
   ```

4. Why this works:
   - The `#` fragment is **NOT sent to the server** in the HTTP request. The server only sees `?default=English`, which passes the whitelist check.
   - But the client-side JavaScript may still read `location.hash` or the full `location.href` and use the fragment value unsafely when writing to the DOM.

5. Check ZAP: the payload does **not appear anywhere** in the ZAP request or response. The fragment never left the browser. This makes fragment-based DOM XSS completely invisible to any server-side defense or network-level monitoring.

---

### Impossible Level

Set DVWA Security to **Impossible**.

1. Try any payload at any injection point -- nothing works. No alert fires, no HTML is injected.

2. Click **View Source** in DVWA to examine the code.

3. The secure implementation:
   - The value is **URL-encoded** before being used
   - The page does **not** use `innerHTML` or `document.write` with raw user input
   - Instead, it uses safe DOM manipulation methods that treat input as text, not HTML
   - Proper encoding ensures that `<script>` becomes `&lt;script&gt;` and is rendered as visible text, not executed as code

---

## What to Observe in ZAP

This is the most important observation of this entire exercise:

- **Low Level:** The XSS payload appears in the **URL** (in ZAP's request log) but does **NOT** appear in the server **response body**. The server returned a clean page. The XSS happened entirely in the browser when client-side JavaScript processed the URL and wrote it to the DOM. Compare this to reflected XSS (Chapter 4.3), where the payload appears in both the request and the response.

- **Medium Level:** Same pattern. The payload is in the URL, not in the response. The server-side filter strips `<script>` from the URL, but the alternative payload bypasses it.

- **High Level (with fragment):** The payload does **not even appear in ZAP at all**. The `#` fragment is never sent over the network. ZAP shows a clean request (`?default=English`) and a clean response. The attack is completely invisible at the network layer.

- **KEY INSIGHT:** This is the fundamental difference between DOM-based XSS and reflected/stored XSS. DOM XSS happens entirely within the browser. Server-side WAFs, server logs, and proxy tools may never see the attack payload.

---

## Book Connection

| DVWA Observation | Book Reference |
|---|---|
| `innerHTML` used with user input to inject HTML | Book example `4h-001.html` -- demonstrates innerHTML vulnerability |
| `document.write` used with URL parameter | Book example `4h-002.html` -- demonstrates document.write vulnerability |
| Impossible level uses safe DOM methods | Book's recommended fix: use `textContent` instead of `innerHTML` |
| Fragment (`#`) based attack invisible to server | Book's discussion of hash-based DOM XSS in Section 4.17.1 |
| Server-side filters fail to catch DOM XSS | Book emphasizes: DOM XSS is invisible to server-side WAFs and logging |

---

## Key Takeaways

1. **DOM XSS is a client-side vulnerability.** The server may be completely secure, but if client-side JavaScript handles user input unsafely, XSS can still occur.

2. **Server-side defenses are blind to DOM XSS.** WAFs, server-side input validation, output encoding on the server -- none of these help if the vulnerability is in the client-side code.

3. **URL fragments (`#`) bypass all server-side controls.** Fragments are never sent to the server. If JavaScript reads `location.hash` and uses it unsafely, no server-side defense can prevent the attack.

4. **The fix is in the JavaScript code itself.** Use `textContent` instead of `innerHTML`. Avoid `document.write`. Never pass user-controlled strings to `eval()`, `setTimeout()`, or `setInterval()` as code.

5. **Blacklist filtering is insufficient.** Blocking `<script>` while allowing other HTML elements leaves the door open for `<img>`, `<svg>`, `<iframe>`, and dozens of other vectors.

6. **Code review of JavaScript is essential.** Trace every path from user-controlled sources (URL, hash, referrer, postMessage, Web Storage) to dangerous sinks (innerHTML, document.write, eval). Every unvalidated source-to-sink path is a potential DOM XSS vulnerability.
