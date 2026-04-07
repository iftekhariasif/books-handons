# Chapter 4.5 -- CSRF & Clickjacking

> From "The Art of Secure Web Application Development" by Tokumaru Hiroshi

---

## 4.5 Vulnerabilities That Creep Into "Important Processing"

Web applications allow logged-in users to perform irreversible "important processing" (e.g., money transfers, purchases, password changes). Two major vulnerability classes target this flow:

- **CSRF (Cross-Site Request Forgery)** -- Section 4.5.1
- **Clickjacking** -- Section 4.5.2

---

## 4.5.1 Cross-Site Request Forgery (CSRF)

### Overview

When accepting "important processing" requests, the application must confirm the request genuinely originated from the user. If this verification is missing, an attacker can craft a page that tricks the victim's browser into performing the "important processing" on the target site.

### Impact of CSRF

| Impact Area | Examples |
|---|---|
| Account actions | Purchases, money transfers |
| User data changes | Password changes, email address changes |
| Social/content actions | SNS posts, forum posts, inquiry form submissions |

> CSRF itself does **not** leak personal information. However, if the attacker changes the password, they can subsequently log in and steal data.

### CSRF Summary Table

| Aspect | Detail |
|---|---|
| **Affected pages** | Any page performing "important processing" |
| **Affected sites** | Sites using cookies or HTTP authentication for session management; sites using TLS client certificates without additional verification |
| **Pages that receive attacks** | Only pages with CSRF vulnerabilities |
| **Severity** | Medium to High |
| **User involvement** | User must click a link or visit a malicious page |
| **Countermeasure** | Before executing "important processing," confirm it is a legitimate request from the authorized user |

---

### Attack Methods and Impact

#### Simple (Input-Execution) Pattern CSRF Attack

A password-change flow with no confirmation screen:

**Login script** (`45-001.php`):
```php
<?php
session_start();
$id = filter_input(INPUT_GET, 'id');
if (empty($id)) $id = 'yamada';
$_SESSION['id'] = $id;
?>
```

**Password input form** (`45-002.php`):
```php
<?php
session_start();
// Login check omitted
?>
<body>
<form action="45-003.php" method="POST">
  New password: <input name="pwd" type="password"><br>
  <input type="submit" value="Change Password">
</form>
</body>
```

**Password change execution** (`45-003.php`):
```php
<?php
function ex($s) { // XSS-safe HTML escape for display
  echo htmlspecialchars($s, ENT_COMPAT, 'UTF-8');
}
session_start();
$id = $_SESSION['id'];    // Get user ID
// Login check omitted
$pwd = filter_input(INPUT_POST, 'pwd'); // Get password
// Change password -- set user $id's password to $pwd
?>
<body>
<?php ex($id); ?>'s password was changed to <?php ex($pwd); ?>
</body>
```

For `45-003.php` to actually change the password, these conditions must be met:

- Request is sent via **POST** to `45-003.php`
- User is **logged in** (valid session)
- The POST parameter `pwd` contains the **new password**

#### The Trap HTML (attacker's page)

```html
<!-- http://trap.example.com/45/45-900.html -->
<body onload="document.forms[0].submit()">
<form action="http://example.jp/45/45-003.php" method="POST">
  <input type="hidden" name="pwd" value="cracked">
</form>
</body>
```

#### Attack Flow

1. The victim is already logged in to `example.jp`
2. The attacker prepares the trap page
3. The victim visits the trap page
4. The trap JavaScript causes the victim's browser to POST to the target site with the new password `cracked` and the victim's session cookie attached
5. The victim's password is changed to `cracked`

> The attacker hides the attack using an **invisible iframe** so the victim sees camouflage content and does not notice.

---

### CSRF vs. Reflected XSS -- Comparison

| | CSRF | Reflected XSS |
|---|---|---|
| **What is abused** | Server-side processing | Client-side script execution |
| **Malicious content placement** | Trap site (different origin) | Injected into the legitimate site's response |
| **Capabilities** | Can only trigger server-side functions | Can also read data, use JavaScript freely (more powerful) |
| **Developer awareness** | Requires countermeasures at design stage | More well-known, but both need proactive defense |

> XSS is generally more powerful than CSRF. CSRF can only invoke server-side actions; XSS can do everything CSRF can **plus** read responses, steal data, etc.

---

### CSRF When a Confirmation Screen Exists

When there is an input screen -> confirmation screen -> execution screen flow, the method of passing data from confirmation to execution matters:

#### Method 1: Hidden Parameters

- Data entered on the input screen is embedded as `<input type="hidden">` in the confirmation form
- The execution screen receives it via POST
- This is equivalent to having **no confirmation screen** from a CSRF perspective -- the attacker can directly POST to the execution endpoint

#### Method 2: Session Variables

- Data from the confirmation screen is stored in a **session variable**
- The execution screen reads from the session
- The attacker needs a **two-stage attack** using two iframes:
  1. `iframe1`: POSTs the malicious email address to the confirmation screen (setting the session variable)
  2. `iframe2`: Calls the execution screen after a delay (e.g., 10 seconds)
- Both iframes load the trap site simultaneously; the confirmation iframe fires first, and after a delay, the execution iframe triggers

---

### CSRF via File Upload Forms

CSRF attacks also work against file upload forms. An attacker can use **XMLHttpRequest** to send cross-origin requests:

```html
<body>
<script>
var data = '\n' +
  '----BNDRY\n' +
  'Content-Disposition: form-data; name="imgfile"; filename="a.php"\n' +
  'Content-Type: text/plain\n' +
  '\n' +
  '<?php phpinfo();\n' +
  '\n' +
  '----BNDRY--\n';

var req = new XMLHttpRequest();
req.open('POST', 'http://example.jp/45/45-005.php');
req.setRequestHeader('Content-Type',
  'multipart/form-data; boundary=--BNDRY');
req.withCredentials = true;  // Send cookies
req.send(data);
</script>
</body>
```

- `Content-Type` is set to `multipart/form-data` with a boundary
- `withCredentials = true` ensures cookies are sent
- The browser's same-origin policy blocks **reading** the response, but the request itself is sent and processed by the server
- If the uploaded file is accessible, the attacker can then visit it (e.g., `phpinfo()` executes)

> The CORS policy prevents reading responses from cross-origin requests, but the **request itself still reaches the server**. The attack succeeds even though the response is blocked.

---

### CSRF Against Internal Networks (Column)

CSRF attacks are not limited to internet-facing sites:

- **Routers/firewalls**: Attackers can change configuration settings
- **Intranet applications**: Employees visiting external malicious pages can trigger actions on internal systems

> Even internal-network-only web systems should implement CSRF protections. XSS on internal systems is also possible.

### CSRF on Sites Without Authentication (Column)

Even sites without login can be vulnerable to CSRF:

- Example: A 2017 incident where an attacker manipulated access logs by planting crafted IP addresses
- Any site that accepts state-changing POST requests can be targeted

---

### Root Cause of CSRF

CSRF vulnerabilities arise from two properties of the web:

1. The **`action` attribute** of a `<form>` element can point to **any domain's URL**
2. **Cookies** (including session IDs) are **automatically sent** by the browser to the target site

Combined: the browser cannot distinguish between a request intentionally made by the user and one triggered by a malicious page.

---

### Countermeasures

#### Step 1: Identify Pages That Need CSRF Protection

Not every page needs protection. Focus on pages that perform **important processing** (state changes). Read-only pages (product catalogs, search results) do **not** need CSRF protection.

Example -- EC site page flow:

| Page Type | CSRF Protection Needed? |
|---|---|
| Category / Product listing | No |
| Cart operations | No (usually) |
| Purchase confirmation | No |
| **Purchase execution** | **Yes** |
| **Personal info edit** | **Yes** |
| **Password change** | **Yes** |

#### Step 2: Verify the Request Is Intentional

Three methods to confirm a request is legitimate:

| Method | Token Embedding | Password Re-entry | Referer Check |
|---|---|---|---|
| **Assurance level** | High | High | Medium |
| **User burden** | None | Must re-enter password | None |
| **Recommended for** | Best general-purpose method | Use for critical actions (password change, payment) and as double authentication | Simpler but less reliable; some browsers/firewalls strip Referer |

---

#### Method A: Secret Token (Recommended)

Embed a **cryptographically random token** in the form as a hidden field, and verify it on the server when the form is submitted.

**Token generation** (PHP examples):

```php
// Using /dev/urandom
$token1 = bin2hex(file_get_contents('/dev/urandom', false, NULL, 0, 24));

// Using openssl_random_pseudo_bytes
$token2 = bin2hex(openssl_random_pseudo_bytes(24));

// Using random_bytes (PHP 7.0+)
$token3 = bin2hex(random_bytes(24));
```

**Form with token** (`45-002a.php`):
```php
<?php
session_start();
if (empty($_SESSION['token'])) {  // Generate token if empty
  $token = bin2hex(random_bytes(24));
  $_SESSION['token'] = $token;
} else {  // Reuse existing token
  $token = $_SESSION['token'];
}
?>
<form action="45-003a.php" method="POST">
  New password: <input name="pwd" type="password"><br>
  <input type="hidden" name="token" value="<?php
    echo htmlspecialchars($token, ENT_COMPAT, 'UTF-8'); ?>">
  <input type="submit" value="Change Password">
</form>
```

**Token verification** (`45-003a.php`):
```php
session_start();
$token = filter_input(INPUT_POST, 'token');
if (empty($_SESSION['token']) || $token !== $_SESSION['token']) {
  die('Token mismatch. Please try again.');
}
// Proceed with "important processing"...
```

Key points:
- Use `empty()` to check that the session token is actually set (prevents bypass if session variable is not initialized)
- Use `hash_equals` (PHP 5.6+) for token comparison to prevent timing attacks
- Token must be sent via **POST** (not GET) to avoid leaking in Referer headers or browser history

---

#### Method B: Password Re-entry

Ask the user to re-enter their password before executing the action:

- Serves dual purpose: CSRF protection **and** re-authentication
- Useful for purchases, password changes, and other high-value operations
- Also confirms the current user is the account owner (not just someone who found an unlocked computer)

---

#### Method C: Referer Check

Verify the `Referer` header to ensure the request came from the expected page:

```php
// Simple Referer check
if (preg_match('#\Ahttp://example\.jp/45/45-002A\.php\z#',
    $_SERVER['HTTP_REFERER']) !== 1) {
  die('Invalid request. Referer mismatch.');
}
```

Limitations:
- Some users/browsers/firewalls/proxies **strip the Referer header**
- Cannot reliably distinguish missing Referer (legitimate) from absent Referer (attack)
- Less robust than token-based protection

---

### CSRF Countermeasure Comparison Table

| | Token Embedding | Password Re-entry | Referer Check |
|---|---|---|---|
| **Assurance** | High | High | Medium |
| **User burden** | None | Password input required | None |
| **Recommended scenario** | Most secure general-purpose solution; works for any important processing | Critical operations needing double-check (payments, password changes) | Simpler alternative when other methods are difficult to implement |
| **Weakness** | Implementation effort (application/framework level) | UX friction | Referer may be stripped; cannot fully rely on it |

---

### Supplementary CSRF Protection

After executing "important processing," send a **notification email** to the user's registered email address:

- Does **not prevent** the CSRF attack itself
- Allows users to **detect** the attack quickly and respond
- Acts as a safety net alongside primary countermeasures

---

## 4.5.2 Clickjacking

### Overview

Clickjacking tricks users into clicking buttons or links on a target site **without their knowledge**. The attacker:

1. Uses an **iframe** to load the target site
2. Overlays it with a decoy page using **CSS** (opacity/positioning)
3. Makes the target site's iframe **transparent**
4. The user thinks they are clicking on the decoy, but they are actually clicking on the real (hidden) target

### Clickjacking Summary Table

| Aspect | Detail |
|---|---|
| **Affected pages** | Pages performing "important processing" on mouse click only |
| **Affected sites** | Only pages with clickjacking vulnerabilities |
| **Impact** | Unintended posts, settings changes, purchases |
| **Severity** | Medium to High |
| **User involvement** | Must click on the trap page |
| **Countermeasure** | Output `X-Frame-Options` header on "important processing" pages |

---

### Attack Example: Twitter Web Intent Abuse

**Twitter Web Intents** allow pre-filling tweet content via query parameters. The attacker:

1. Creates a page with an iframe loading Twitter's tweet form (with pre-filled content like a crime threat)
2. Overlays a decoy page (e.g., a campaign "Enter" button) on top
3. Sets the iframe opacity so the Twitter form is invisible
4. When the user clicks the visible "Enter" button, they actually click Twitter's "Tweet" button

#### Attack Sample Walkthrough

The attacker hosts a page at `http://trap.example.com/45/45-902.html`:

- At **0% opacity**: Only the decoy ("Click here for a smartphone giveaway!" with an "Apply" button) is visible
- At **25% opacity**: The target page (Twitter / bulletin board) starts to become faintly visible
- At **50% opacity**: Both pages are partially visible -- the "Apply" button and the real "Post" button are aligned
- At **75% opacity**: The target posting form is clearly visible, showing the pre-filled malicious content
- At **100% opacity**: The target page is fully visible; the attack mechanism is completely exposed

---

### Root Cause

Clickjacking is **not** caused by an application bug. It exploits the HTML/CSS specification that allows:

- Loading any page in an iframe
- Making iframes transparent with CSS `opacity`
- Overlaying content with CSS positioning

It is classified alongside CSRF as a specification-level vulnerability.

---

### Countermeasure: X-Frame-Options Header

The `X-Frame-Options` HTTP response header controls whether a page can be loaded in a frame/iframe.

#### Values

| Value | Behavior |
|---|---|
| `DENY` | Page cannot be displayed in any frame/iframe |
| `SAMEORIGIN` | Page can only be framed by pages from the **same origin** |

- Use `DENY` for sites that never use frames
- Use `SAMEORIGIN` if the site itself uses frames but wants to prevent external framing

#### Setting X-Frame-Options

**PHP:**
```php
header('X-Frame-Options: SAMEORIGIN');
```

**Apache** (requires `mod_headers`):
```
Header always append X-Frame-Options SAMEORIGIN
```

**nginx:**
```
add_header X-Frame-Options SAMEORIGIN;
```

#### How It Works

- **DENY**: The browser refuses to render the page inside any iframe, regardless of origin
- **SAMEORIGIN**: The browser only renders the page in a frame if the framing page is from the same origin

> Supported in all major browsers (Firefox, Google Chrome, Safari, Opera, and Edge).

---

### Supplementary Clickjacking Protection

- After executing "important processing," send a **notification email** to the registered address
- This does not prevent the attack but helps users detect it quickly

---

### Clickjacking -- Summary

Clickjacking is a relatively recent attack technique targeting web applications. Since `X-Frame-Options` provides a straightforward defense, always implement it on pages that perform important processing.

---

## Key Takeaways

1. **CSRF** forces authenticated users to unknowingly execute state-changing actions. **Clickjacking** tricks users into physically clicking on hidden target-site elements.
2. **Always use anti-CSRF tokens** (cryptographically random, stored in session, verified on POST) for any state-changing endpoint.
3. **Password re-entry** provides the strongest assurance for high-value actions (payment, credential changes).
4. **Referer checking** is a simpler but less reliable alternative due to header stripping by browsers/proxies.
5. **X-Frame-Options: DENY or SAMEORIGIN** is the primary defense against clickjacking -- simple to implement, widely supported.
6. **Notification emails** after important actions serve as a detection mechanism, not prevention.
7. Even **internal/intranet** applications and **unauthenticated** sites can be CSRF targets -- do not assume safety based on network boundaries.
8. XSS is strictly more powerful than CSRF (XSS can do everything CSRF can, plus more). Both require proactive defense at the design stage.
