# DOM XSS Practice -- Quick Reference

## DVWA URL

```
http://localhost/vulnerabilities/xss_d/
```

---

## Copy-Paste Payloads

### Low Level

```
?default=<script>alert('DOM XSS')</script>
```

```
?default=<img src=x onerror=alert(document.cookie)>
```

### Medium Level

```
?default=</option></select><img src=x onerror=alert('XSS')>
```

### High Level

```
?default=English#<script>alert('XSS')</script>
```

### Impossible Level

No payload works. The application properly encodes output and avoids dangerous DOM methods.

---

## DOM XSS vs Reflected XSS vs Stored XSS

| Aspect | DOM-Based XSS | Reflected XSS | Stored XSS |
|---|---|---|---|
| **Where payload lives** | In the URL (query/fragment), never processed by server | In the URL, reflected back in server response | In the database, served to all users |
| **Server sees payload?** | No (especially with `#` fragments) | Yes, it appears in request and response | Yes, it is stored server-side |
| **Payload in server response?** | No | Yes | Yes |
| **Visible in ZAP/proxy?** | Partially (query yes, fragment no) | Yes, in both request and response | Yes, in the response |
| **Detection difficulty** | Hard -- requires JavaScript code analysis | Medium -- server-side scanning can detect | Medium -- server-side scanning can detect |
| **Server-side WAF effective?** | No | Yes | Yes |
| **Fix location** | Client-side JavaScript code | Server-side output encoding | Server-side output encoding |
| **Persistence** | None -- requires victim to click a crafted URL | None -- requires victim to click a crafted URL | Persistent -- affects all users who view the page |

---

## Dangerous JavaScript Functions (Sinks)

These functions interpret strings as HTML or code. Never pass user-controlled input to them without sanitization.

| Function | Risk | Example |
|---|---|---|
| `innerHTML` | Parses string as HTML, executes embedded scripts/event handlers | `el.innerHTML = userInput` |
| `document.write()` | Writes raw HTML to the document | `document.write(location.search)` |
| `document.writeln()` | Same as `document.write` with newline | `document.writeln(data)` |
| `eval()` | Executes string as JavaScript code | `eval(userInput)` |
| `setTimeout(string, ms)` | Executes string as code after delay | `setTimeout("alert(" + val + ")", 1000)` |
| `setInterval(string, ms)` | Executes string as code repeatedly | `setInterval("update(" + val + ")", 500)` |
| `Function()` constructor | Creates function from string | `new Function(userInput)()` |
| `jQuery.html()` | jQuery equivalent of innerHTML | `$(el).html(userInput)` |
| `jQuery.append()` | Appends parsed HTML | `$(el).append(userInput)` |

---

## Safe Alternatives

| Dangerous | Safe Alternative | Why |
|---|---|---|
| `innerHTML` | `textContent` | Treats input as plain text, never parses HTML |
| `document.write()` | `createElement` + `appendChild` | Builds DOM nodes programmatically, no HTML parsing |
| `eval()` | `JSON.parse()` (for data) | Parses data without executing arbitrary code |
| `setTimeout(string)` | `setTimeout(function, ms)` | Pass a function reference, not a string |
| `jQuery.html()` | `jQuery.text()` | jQuery equivalent of textContent |
| `jQuery.append(string)` | `jQuery.append($("<div>").text(val))` | Create element safely, then append |

---

## Sources and Sinks

DOM XSS occurs when data flows from a user-controlled **source** to a dangerous **sink** without sanitization.

### Common Sources (where attacker-controlled data enters)

| Source | Description |
|---|---|
| `location.search` | URL query string (`?key=value`) |
| `location.hash` | URL fragment (`#value`) -- never sent to server |
| `location.href` | Full URL including fragment |
| `document.referrer` | The referring page URL |
| `document.cookie` | Cookie values (if attacker can set via other vulnerability) |
| `window.name` | Window name (persists across navigations) |
| `postMessage` data | Messages received from other windows/iframes |
| `localStorage` / `sessionStorage` | Web Storage values (if attacker can write via other vulnerability) |

### Common Sinks (where data causes damage)

| Sink | Impact |
|---|---|
| `innerHTML` | HTML injection, script execution via event handlers |
| `document.write` | HTML injection, script execution |
| `eval` | Arbitrary JavaScript execution |
| `location.href` | Open redirect, `javascript:` protocol execution |
| `location.assign()` | Same as setting `location.href` |
| `element.src` | Load attacker-controlled resources |
| `element.setAttribute("onclick", ...)` | Inject event handler code |

---

## Common Mistakes

1. **Filtering `<script>` only** -- Attackers use `<img onerror>`, `<svg onload>`, `<body onload>`, `<iframe src>`, and dozens of other vectors. Blacklisting one tag is never sufficient.

2. **Server-side validation only** -- DOM XSS happens in the browser. If JavaScript reads from `location.hash` and writes to `innerHTML`, the server never sees the payload and cannot prevent the attack.

3. **Using `innerHTML` for text content** -- If you only need to display text, use `textContent`. There is no reason to parse HTML when inserting plain text.

4. **Trusting URL fragments** -- The `#` fragment is never sent to the server, so server-side filters and WAFs are completely bypassed. Client-side code must validate fragment data independently.

5. **Trusting `postMessage` without origin check** -- Any window can send a message. Always verify `event.origin` before using `event.data`.

6. **Using `jQuery.html()` as a default** -- Many developers use `.html()` when `.text()` would suffice. This creates unnecessary innerHTML-equivalent injection points throughout the codebase.

---

## Book Chapter References

| Section | Topic |
|---|---|
| 4.17.1 | DOM-Based XSS -- innerHTML, document.write, fragment-based attacks |
| 4.17.2 | Web Storage security -- localStorage/sessionStorage as XSS data sources |
| 4.17.3 | postMessage security -- cross-origin messaging vulnerabilities |

Book examples:
- `4h-001.html` -- innerHTML vulnerability demonstration
- `4h-002.html` -- document.write vulnerability demonstration
- Recommended fix: always use `textContent` for inserting user-controlled text
