# Chapter 4.17 - JavaScript Security Issues

> From "The Art of Secure Web Application Development" (安全なWebアプリケーションの作り方) by Tokumaru Hiroshi

## Overview

This section covers vulnerabilities caused by JavaScript implementation flaws. It covers both plain JavaScript and jQuery-based implementations, focusing on:

- DOM Based XSS
- Web Storage misuse
- postMessage vulnerabilities
- Open Redirect via JavaScript

---

## 4.17.1 DOM Based XSS

### Summary

| Attribute | Detail |
|-----------|--------|
| **Affected** | Web applications using JavaScript for DOM manipulation |
| **Impact page** | Entire web application |
| **Impact type** | JavaScript execution, information theft |
| **Severity** | Medium to High |
| **User involvement** | Clicking a link, visiting attacker's site |
| **Countermeasure** | Proper DOM manipulation, HTML-escape special characters |

### What is DOM Based XSS?

- Traditional XSS (reflected/stored) is caused by server-side code flaws
- DOM Based XSS is caused by **client-side JavaScript** flaws
- JavaScript manipulates the DOM incorrectly, allowing attacker-controlled input to be rendered as HTML/executed

---

### Attack Vector: innerHTML

URL fragment identifiers (hash values, `#`) can be used to change displayed content. If `innerHTML` is used to set content from external input, XSS occurs.

```javascript
// Vulnerable code (4h-001.html)
function chghash() {
    var hash = decodeURIComponent(window.location.hash.slice(1));
    var color = document.getElementById('color');
    color.innerHTML = hash;  // DANGEROUS
}
window.addEventListener("hashchange", chghash, false);
window.addEventListener("load", chghash, false);
```

**Attack URL**:
```
http://example.jp/4h/4h-001.html#<img src=/ onerror=alert(1)>
```

This sets `innerHTML` to an `<img>` tag with an `onerror` handler, executing JavaScript.

**Fix**: Use `textContent` instead of `innerHTML`:

```javascript
// Safe version (4h-001a.html)
function chghash() {
    var hash = window.location.hash;
    var color = document.getElementById('color');
    color.textContent = decodeURIComponent(window.location.hash.slice(1));
}
```

---

### Attack Vector: document.write

`document.write` can also generate executable JavaScript from external input:

```javascript
// Vulnerable code (4h-002.html) - Access analysis sample
var url = decodeURIComponent(location.href);
document.write('<img src="http://api.example.net/4h/4h-003.php?' + url + '">');
```

**Attack URL**:
```
http://example.jp/4h/4h-002.html#22%93%3Cscript%3Ealert(document.domain)%3C/script%3E
```

**Key difference**: With `innerHTML`, injected `<script>` tags do NOT execute. But with `document.write`, injected `<script>` tags DO execute.

**Fix**: Use HTML escaping function:

```javascript
function escape_html(s) {
    return s.replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#39;");
}

var url = decodeURIComponent(location.href);
document.write('<img src="http://api.example.net/4h/4h-003.php?' +
    escape_html(url) + '">');
```

---

### Attack Vector: XMLHttpRequest URL Not Validated

When using XMLHttpRequest with URLs constructed from fragment identifiers, the loaded content may come from an attacker-controlled source.

```javascript
// Vulnerable code (4h-004.html)
function cxhash() {
    var req = new XMLHttpRequest();
    var url = location.hash.slice(1) + '.html';
    if (url === '.html') url = 'menu_a.html';
    req.open("GET", url);
    req.onreadystatechange = function() {
        if (req.readyState == 4 && req.status == 200) {
            var div = document.getElementById('content');
            div.innerHTML = req.responseText;  // External content injected as HTML
        }
    };
    req.send(null);
}
```

**Attack URL**:
```
http://example.jp/4h/4h-004.html#//trap.example.com/4h/4h-900.php?
```

If CORS is configured to allow the requesting origin, the XMLHttpRequest succeeds and attacker-controlled HTML is injected via `innerHTML`.

**Fix**: Validate URLs against a whitelist:

```javascript
var menus = {
    menu_a: 'menu_a.html',
    menu_b: 'menu_b.html',
    menu_c: 'menu_c.html',
    menu_d: 'menu_d.html'
};
var url = menus[location.hash.slice(1)];
if (!url) url = 'menu_a.html';
```

---

### Attack Vector: jQuery Selector Dynamic Generation

jQuery's `$()` function serves dual purposes:
1. **CSS Selector**: `$('#idname')` selects elements
2. **HTML Creator**: `$('<p>Hello</p>')` creates DOM elements

| Notation | Description |
|----------|-------------|
| `$('#idname')` | Select by id |
| `$('.classname')` | Select by class |
| `$('input[name="foo"]')` | Select by input name/attribute |

If external input is used in a jQuery selector, and it contains HTML tags, jQuery will **create elements** instead of selecting them:

```javascript
// Vulnerable - external input in selector
var uri = new URI();
var color = uri.query(true).color;
$('input[name="color"][value="' + color + '"]').attr('checked', true);
```

**Attack URL**:
```
http://example.jp/4h/4h-005.html?color="]<img+src=/+onerror=alert(1)>
```

The `$()` function receives HTML-like input, creates an `<img>` element, and the `onerror` event fires.

---

### Attack Vector: javascript: Scheme XSS

Properties that accept URLs (like `location.href`, `<a href>`, `<iframe src>`) can be set to `javascript:` URLs:

```javascript
// Vulnerable code (4h-006.html)
function go() {
    var url = location.hash.slice(1);
    location.href = url;
}
```

**Attack URL**:
```
http://example.jp/4h/4h-006.html#javascript:alert(document.domain)
```

Clicking the button navigates to a `javascript:` URL, executing arbitrary code.

---

### Root Causes of DOM Based XSS

1. **DOM manipulation with externally-specified HTML tags** using dangerous APIs
2. **Externally-specified JavaScript** executed via `eval` or similar
3. **XMLHttpRequest URLs not validated** (external content loaded)
4. **`location.href`/`src` attributes** accept `javascript:` scheme

### Dangerous Sinks (APIs that can execute HTML/JS)

| Category | APIs |
|----------|------|
| **HTML rendering** | `document.write()`, `document.writeln()`, `innerHTML`, `outerHTML`, `jQuery.html()`, `jQuery()`, `$()` |
| **Code execution** | `eval()`, `setTimeout()`, `setInterval()`, `Function` constructor |

---

### Countermeasures for DOM Based XSS

#### 1. Use Proper DOM Operations / Escape Symbols

- Use `textContent` instead of `innerHTML` to prevent HTML interpretation
- For `document.write`, apply HTML escaping function

```javascript
// innerHTML replacement
element.textContent = userInput;  // Safe: no HTML parsing

// document.write with escaping
function escape_html(s) {
    return s.replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#39;");
}
```

#### 2. Never Pass External Values to eval/setTimeout/Function

```javascript
// DANGEROUS
eval(userInput);
setTimeout(userInput, 1000);

// SAFE - use function references with setTimeout
var sec = location.hash.slice(1);
setTimeout(function() { alert("finished"); }, sec * 1000);
```

For `setTimeout`/`setInterval`, pass function references instead of strings to avoid code injection.

#### 3. Restrict URL Schemes to http/https

```javascript
// Vulnerable
var url = location.hash.slice(1);
location.href = url;  // Allows javascript: scheme

// Safe
var url = location.hash.slice(1);
if (url.match(/^https?:\/\//)) {
    location.href = url;
} else {
    alert('Invalid URL');
}
```

#### 4. Do Not Dynamically Generate jQuery Selectors with $()

- `$()` with external input can create HTML elements
- Use `find` method instead to ensure only selection occurs:

```javascript
// DANGEROUS
$('input[name="color"][value="' + color + '"]').attr('checked', true);

// SAFER - use parseint or validate input
var color = parseInt(uri.query(true).color);
if (!color) color = "1";
$('input[name="color"][value="' + color + '"]').attr('checked', true);
```

#### 5. Use Latest Libraries

- Older jQuery versions (e.g., 1.8.3) have known XSS vulnerabilities in selectors
- jQuery 3.2.1+ and later have better protections
- Keep all JavaScript libraries up to date

#### 6. Validate XMLHttpRequest URLs

- Never allow external input to fully control XMLHttpRequest URLs
- Validate against a whitelist of allowed endpoints
- This prevents both XSS and open redirect issues

---

## 4.17.2 Web Storage Misuse

### What is Web Storage?

Web Storage provides client-side key-value storage accessible via JavaScript:

```javascript
sessionStorage.setItem('key1', 'data1');  // Write
var val = sessionStorage.getItem('key1'); // Read
```

### Two Types

| Feature | Cookie | localStorage | sessionStorage |
|---------|--------|-------------|----------------|
| **Data delivery** | Sent to server automatically | Not sent to server | Not sent to server |
| **Access scope** | Domain & path | Same-Origin Policy | Same-Origin Policy |
| **Expiration** | `expires` or `max-age` | Persistent (permanent) | Tab/window lifetime |
| **Server communication** | Automatic | Manual only | Manual only |
| **JavaScript access** | Yes (unless `httpOnly`) | Always accessible | Always accessible |
| **Modifiable from outside** | Possible | No | No |

### What Should You Store in Web Storage?

- Web Storage is accessible from JavaScript on the same origin
- If the application has **XSS vulnerabilities**, Web Storage data is exposed
- Unlike cookies, the `httpOnly` flag is not available
- **High-value or sensitive data should NOT be stored** in Web Storage
- Web Storage is best for non-sensitive, convenience data; sensitive data should use server-side sessions with secure cookies

### Inappropriate Uses of Web Storage

| Anti-Pattern | Risk |
|--------------|------|
| Storing **secret information** (tokens, credentials) | Theft via XSS |
| Storing **data received via XSS or postMessage** | Data corruption/injection |
| **XSS/postMessage** used to **inject data** into storage | Persistent XSS via stored payload |
| Using Web Storage as a **DOM Based XSS conduit** | Stored XSS through localStorage |

---

## 4.17.3 postMessage Vulnerabilities

### What is postMessage?

`postMessage` allows cross-origin communication between windows/iframes:

```javascript
// Sender syntax
targetWindow.postMessage(message, targetOrigin);
```

| Parameter | Description |
|-----------|-------------|
| `win` | Target window object |
| `message` | Message body (string) |
| `origin` | Target origin (URL scheme + host + port), or `"*"` for any |

### Example Setup

**Parent page** (example.jp/4h/4h-010.html):
```javascript
window.addEventListener("message", receiveMessage, false);

function receiveMessage(event) {
    var d1 = document.getElementById('d1');
    d1.textContent = "received: " + event.data;
    event.source.postMessage("confirmed: " + event.data, "http://example.jp");
}
```

**Child iframe** (api.example.net/4h/4h-011.html):
```javascript
window.addEventListener("message", receiveMessage, false);

function receiveMessage(event) {
    var d2 = document.getElementById('d2');
    d2.innerHTML = "confirmed: " + event.data;  // DANGEROUS
    window.parent.postMessage("secret data", "*");  // DANGEROUS
}
```

---

### Vulnerability 1: Unverified Message Destination

**Problem**: Using `"*"` as the target origin sends messages to **any** origin:

```javascript
// DANGEROUS - any page can receive this
window.parent.postMessage("secret data", "*");
```

**Fix**: Always specify the exact target origin:

```javascript
// SAFE - only example.jp receives this
window.parent.postMessage("secret data", "http://example.jp");
```

---

### Vulnerability 2: Unverified Message Source

**Problem**: The receiving page does not check `event.origin`, accepting messages from any origin:

```javascript
// DANGEROUS - accepts messages from any origin
window.addEventListener("message", receiveMessage, false);
function receiveMessage(event) {
    d2.innerHTML = "confirmed: " + event.data;  // XSS if data contains HTML
}
```

An attacker page at `trap.example.com` can send:
```javascript
// From attacker's page
postMessage('<img src=/ onerror=alert("cracked")>', '*');
```

If the receiver uses `innerHTML` with the message data, DOM Based XSS occurs.

**Fix**: Always verify `event.origin`:

```javascript
function receiveMessage(event) {
    if (event.origin !== "http://example.jp") {
        d2.textContent = "Origin violation";
        return;
    }
    d2.innerHTML = "confirmed: " + event.data;
}
```

### postMessage Countermeasure Summary

- **Sender**: Always specify the exact target origin (never use `"*"` for sensitive data)
- **Receiver**: Always check `event.origin` against expected origins
- **Origin check in receiver** is the application's responsibility, not the browser's
- For services accepting messages from any origin, the receiving code must sanitize appropriately
- Verify both **sender origin** (via `event.origin`) and **receiver origin** (via second parameter of `postMessage`)

---

## 4.17.4 Open Redirect via JavaScript

### Vulnerability Cause

- Open Redirect via server-side code was covered in Section 4.7
- JavaScript-based redirects using `location.href` can also be vulnerable
- When the redirect URL comes from fragment identifiers or user input, attackers can control the destination

### Attack Example

```javascript
// Vulnerable code (4h-020.html)
function go() {
    var url = location.hash.slice(1);
    if (url.match(/^https?:\/\//)) {  // DOM Based XSS check passes
        location.href = url;           // But open redirect is possible!
    }
}
```

**Attack URL**:
```
http://example.jp/4h/4h-020.html#http://trap.example.com/4h/4h-901.html
```

The user clicks "Execute", is redirected to a phishing site that looks like a login page, and enters their credentials.

### Attack Flow

1. User visits legitimate site with malicious fragment
2. Clicks a button that triggers `location.href = attackerURL`
3. Redirected to attacker's phishing site
4. Phishing site mimics login page
5. User enters ID and password, which are stolen

### Countermeasures

#### Option 1: Fix the Redirect URL

```javascript
// Redirect URL is fixed - no user control
function go() {
    var urls = {next: '4h-021.html', back: '../'};
    var url = urls[location.hash.slice(1)] || './notfound.html';
    location.href = url;
}
```

#### Option 2: Use Identifiers Instead of Direct URLs

Instead of accepting full URLs from user input, use numeric or string identifiers that map to predefined URLs:

```javascript
function go() {
    var url = urls[location.hash.slice(1)] || './notfound.html';
    location.href = url;
}
```

---

## Section 4.17 Summary / Key Takeaways

- DOM Based XSS has evolved from a niche concern to a major vulnerability class as JavaScript usage has grown
- JavaScript's expanding capabilities (Web Storage, postMessage, etc.) introduce new attack surfaces
- **JavaScript library and framework vulnerabilities** should be monitored and updated proactively

### Critical Rules

| Rule | Details |
|------|---------|
| **Never use `innerHTML` with external input** | Use `textContent` instead |
| **Never use `eval()` with external input** | Use `JSON.parse` for JSON, function references for callbacks |
| **Never use `document.write` with external input** | Apply HTML escaping if unavoidable |
| **Validate URLs** | Restrict to `http`/`https` schemes only |
| **Validate XMLHttpRequest URLs** | Use whitelists, not user input |
| **Keep libraries updated** | Older jQuery/JS libraries have known XSS bugs |
| **Never store secrets in Web Storage** | Use server-side sessions with httpOnly cookies |
| **Always verify postMessage origins** | Check `event.origin` on receive, specify origin on send |
| **Fix or whitelist redirect destinations** | Never let user input control `location.href` freely |
