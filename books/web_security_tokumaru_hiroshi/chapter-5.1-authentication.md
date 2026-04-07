# Chapter 5.1 - Authentication

> From "Safe Web Application Development" by Tokumaru Hiroshi
> Chapter 5: Representative Security Features

---

## Overview

Authentication (Ninshō) is the process of confirming that a user is who they claim to be through some form of verification. Web applications commonly use HTTP authentication, form-based authentication (most common), client certificate authentication, and others. This chapter focuses on form-based authentication.

**Topics covered:**
- Login function
- Countermeasures against password-based attacks
- Password storage methods
- Auto-login
- Login form design
- Error message requirements
- Logout function

---

## 5.1.1 Login Function

Authentication processing centers around identity verification -- confirming that the user ID and password match what is stored in the database.

Typical SQL for login verification:

```sql
SELECT * FROM usermaster WHERE id=? AND password=?
```

---

## Attacks Against the Login Function

### SQL Injection to Bypass Login

If the login form has SQL injection vulnerabilities, an attacker can bypass authentication without knowing the password. See section 4.4 (SQL Injection) for details.

### SQL Injection to Obtain Passwords

If the application has SQL injection vulnerabilities, an attacker can extract stored IDs and passwords from the database. Even if SQL injection is eliminated, password information stored in the DB can still be misused in other ways. See section 5.1.3 (Password Storage Methods).

### Brute Force Attack on Login Forms

From the login screen, an attacker can repeatedly try different user ID and password combinations. This is called brute force attack.

**Brute force attack (exhaustive search):** Trying all possible character combinations for passwords systematically.

| Attack Type | Description |
|-------------|-------------|
| **Brute Force** | Try every possible combination of characters |
| **Dictionary Attack** | Try commonly used passwords from a wordlist |

### Social Engineering to Obtain Passwords

Attackers use social engineering techniques rather than technical attacks, such as:
- Pretending to be a server administrator and asking for passwords
- Asking users "Could you tell me the necessary password for XX?"
- Shoulder surfing: Watching where users enter passwords, or observing keyboard input

---

## Impact of Login Function Breach

If a web application is compromised through unauthorized login, the attacker gains all privileges of the target user:
- Viewing, editing, deleting information
- Purchasing products
- Transferring money, etc.

The impact is equivalent to session hijacking. If the attacker learns the password, they can also:
- Re-authenticate at will
- The impact extends beyond session hijacking

---

## Preventing Unauthorized Login

Two essential requirements for form-based (password) authentication:
1. **Eliminate SQL injection and other security vulnerabilities**
2. **Make passwords hard to guess**

### Eliminating SQL Injection and Other Vulnerabilities

Vulnerabilities that are easy to exploit in login functions:
- (A) SQL injection (section 4.4.1)
- (B) Session ID fixation (section 4.6.4)
- (C) Cookie security issues (section 4.8.2)
- (D) Open redirect vulnerability (section 4.7.1)
- (E) HTTP header injection (section 4.7.2)

Notes:
- (A): If SQL injection exists, password verification may be bypassed entirely via SQL
- (B)/(C): If session IDs are stored in cookies, the method of setting them can be vulnerable
- (D)/(E): Not directly related to login but can cause vulnerabilities when redirecting after login

---

## Making Passwords Hard to Guess

### Password Character Types and Length Requirements

The most fundamental requirement is the character types and length (number of digits) allowed for passwords.

**Formula:** Total password combinations = (Number of character types) ^ (Number of digits)

| Character Types | 4 digits | 6 digits | 8 digits |
|----------------|----------|----------|----------|
| Digits only (10) | 1 x 10^4 | 1 x 10^6 | 1 x 10^8 |
| Alphanumeric (36) | ~1.7 x 10^6 | ~2.2 x 10^9 | ~2.8 x 10^12 |
| Alphanumeric + symbols (94) | ~7.8 x 10^7 | ~6.9 x 10^11 | ~6.1 x 10^15 |

As shown, the number of character types and digits both significantly increase the total combinations.

### Password Usage in Practice

In reality, many users choose passwords from a limited set (section 5-1 shows 4-digit passwords are often just a few hundred patterns). Users tend to pick passwords that are easy to remember and type.

### Application Password Requirements

When deciding password requirements, the application operator must balance:
- User convenience (easy to remember / easy to type)
- Security strength (sufficient length and complexity)

**Typical requirements:**
- Character types: Alphanumeric (upper and lower case distinct)
- Minimum length: 8 characters

More liberal specifications (e.g., US-ASCII printable characters 0x20-0x7E, up to 128 characters) are also possible. Consider using **passphrases** instead of passwords -- passphrases are multiple words strung together (e.g., "andesutaberukusamochiisveryhappy").

### Active Password Policy Checks

Web applications should actively check that user-chosen passwords meet policy:
- **Length requirement** (e.g., minimum 8 characters)
- **Character type requirement** (e.g., must include letters, digits, and at least 1 symbol)
- **Prohibit user ID as password** (so-called "Joe accounts")
- **Reject passwords found in common dictionaries**

Example: Twitter's password change screen shows "Too short" error for weak passwords.

---

## 5.1.2 Countermeasures Against Password Authentication Attacks

### Basic Account Lockout

The most basic countermeasure against online brute force and dictionary attacks is **account lockout**.

**How it works:**
- Tell the user the number of remaining incorrect password attempts
- After exceeding the threshold, lock the account and notify the user
- Locked accounts require password reset via alternative means

**Recommended settings:**
- Lock after **10 failed attempts** (ATMs lock after just 3, but for web this may be too aggressive)
- **Re-enable the account after 30 minutes** automatically, OR:
  - Require administrator confirmation before re-enabling

### Variations of Password Attacks and Countermeasures

#### Dictionary Attack

A dictionary attack does not try every possibility, but instead uses a list of commonly used passwords. It is more efficient than brute force but covers fewer combinations.

The countermeasure is the same as for brute force: **account lockout**.

#### Joe Account Discovery

A "Joe account" is one where the user ID and password are the same (e.g., user01/user01). Simple account lockout cannot prevent this since the attacker tries a different account each time.

#### Reverse Brute Force Attack

Instead of fixing the user ID and varying the password, a **reverse brute force attack** fixes the password and varies the user ID. For example, try password "password1" against all user IDs. Simple account lockout is ineffective since each account only gets one attempt.

#### Password Spray Attack

An evolution of reverse brute force: try a small number of passwords against many accounts while varying both ID and password slowly. Also called **Password Spray Attack**.

#### Countermeasures Against These Attacks

| Attack Type | Account Lockout Effective? |
|-------------|---------------------------|
| Brute force | Yes |
| Dictionary attack | Yes |
| Joe account discovery | No (different account each time) |
| Reverse brute force | No (one attempt per account) |
| Password spray | No (distributed across accounts) |
| Password list attack | No (high success rate per attempt) |

#### Password List Attack

A **password list attack** uses ID/password pairs leaked from other sites to attempt login on the target site. The attacker:
1. Obtains leaked credentials from another site (via SQL injection, etc.)
2. Tries those credentials on the target site

Since many users reuse passwords across sites, this attack has a high success rate (sometimes exceeding 10%).

### Two-Factor Authentication (2FA)

After successful password verification, require additional secret information to strengthen authentication. This is **two-factor authentication**.

**Common second factors:**
- 6-digit codes sent via email or SMS
- 6-digit codes generated by a smartphone authenticator app (e.g., Google Authenticator using TOTP)

**When to require 2FA:**
- On first login only, then trust the device for a period
- When login is from an unusual location, time zone, or browser
- For critical operations: password changes, financial transactions, etc.

### Active Password Checks

As mentioned above, check passwords at registration time for dictionary words, common patterns, etc.

### Login Failure Rate Monitoring

If login failures increase suddenly, it may indicate an external brute force attack. Periodic monitoring of failure rates and login attempts, with alerts to administrators, is an effective countermeasure.

### Comparison of Countermeasures

| Countermeasure | Merits | Demerits |
|----------------|--------|----------|
| Two-factor authentication | Effective against all password attacks; doesn't affect legitimate users with correct passwords | Cost, maintenance burden; requires adequate user support |
| Active password policy check | Easy maintenance once rules are set; low cost | Only detects weak passwords at registration/change time |
| Login failure monitoring | Detects ongoing attacks; low cost to implement | Cannot prevent attacks in real time; only post-incident detection |

---

## 5.1.3 Password Storage Methods

### Why Passwords Need Protection

If passwords are leaked externally:
- The attacker can impersonate users on this service
- Other services may be compromised (password reuse)
- Confidential information tied to passwords is also exposed

**Uses of leaked passwords by attackers:**
- Password list attacks on other services
- Access to the victim's personal data (purchases, financial info, etc.)
- Modify/delete victim's data

Therefore, passwords should not be stored in the database as plaintext. Modern password protection uses **message digests (cryptographic hash functions)**.

### Encryption vs. Hashing for Password Storage

**Encryption** has challenges:
- Requires selecting a secure algorithm
- Secure implementation of encrypt/decrypt
- Key generation and secure storage
- Risk of key compromise or algorithm becoming obsolete

**Hashing (message digest)** is preferred because:
- It is a one-way function (cannot recover original data from hash)
- No key management required
- Easier to implement securely

### Database Encryption and Passwords (Column: TDE)

Transparent Data Encryption (TDE) encrypts data at rest but does not protect passwords if the application or database is compromised. TDE is useful for protecting backup media, but for password storage, **hashing is still required**.

---

### Message Digest (Hash) for Password Protection

A **message digest** takes arbitrary-length data and produces a fixed-length hash value using a cryptographic hash function. Security requirements:

| Property | Description |
|----------|-------------|
| **Preimage resistance (one-way)** | Given a hash, it should be computationally infeasible to find the original data |
| **Second preimage resistance** | Given data and its hash, it should be infeasible to find different data producing the same hash |
| **Collision resistance** | It should be infeasible to find any two different inputs with the same hash |

### How Password Hashing Works

1. At registration: hash the password and store the hash in the DB
2. At login: hash the submitted password and compare with stored hash
3. If they match, authentication succeeds

**Example using md5sum:**

```bash
$ echo -n password1 | md5sum
7c6a180b36896a65c4c38ff8577e615d

$ echo -n password2 | md5sum
6cb75f652a9b52798eb6cf2201057c73
```

### Hash Function Security Properties

- Hash values have **fixed length** regardless of input size
- Passwords that differ by even one character produce **completely different hashes**
- However, since the hash space is limited, **collisions theoretically exist** (but should be computationally infeasible to find)

---

### Threats to Password Hashes

#### Threat 1: Offline Brute Force Attack

An attacker who obtains hash values can attempt to crack them offline (no server interaction needed). Hash functions are designed for speed, so attackers can compute billions of hashes per second.

**Performance example:** Using NVIDIA GTX 1080 Ti:
- MD5: ~194.685 billion/sec for 8 characters
- For 8-character alphanumeric+symbol passwords: total combinations ~6.1 x 10^15
- MD5 can be cracked relatively quickly
- SHA-1 is also not sufficient

#### Threat 2: Rainbow Tables

A **rainbow table** is a precomputed lookup table mapping hash values to passwords. It trades storage space for computation time.

**Limitations of rainbow tables:**
- In 2003, rainbow tables became practical; they work well for short alphanumeric passwords
- RainbowCrack Project: covers 8-character US-ASCII with MD5 in ~257 GB
- Effective for SHA-1 as well (just larger tables)
- Adding special characters or increasing length makes rainbow tables impractical

**Countermeasure:** Use **salt** (below)

#### Threat 3: User DB Used as a Password Dictionary

If an attacker extracts the user DB, they can:
1. Register many dummy users with known passwords
2. Compare the hashes of known passwords against other users' hashes
3. If hashes match, the passwords are the same

Example: If `saburo`'s hash matches `evil2`'s hash (both `123456`), the attacker knows both passwords.

**Countermeasure:** Use **salt** (each user gets a unique salt, so identical passwords produce different hashes)

---

### Countermeasure 1: Salt

A **salt** is a random string added to the password before hashing.

**How it works:**
- Each user gets a unique salt value
- Hash is computed as: `hash(salt + password)`
- Even if two users have the same password, their hashes differ because their salts differ

**Benefits:**
- Prevents rainbow table attacks (attacker would need a separate rainbow table for each salt)
- Prevents dictionary matching within the DB (same password produces different hashes)
- The salt itself does not need to be secret (it is stored alongside the hash)

**Salt requirements:**
- Should be sufficiently long (at least 20 characters recommended)
- Must be unique per user

### Countermeasure 2: Stretching

**Stretching** means applying the hash function repeatedly (thousands or more times), making brute force attacks computationally expensive.

**Purpose:** If one hash computation takes microseconds, stretching with 10,000 iterations makes each attempt 10,000 times slower, significantly reducing the attacker's throughput.

**Recommended algorithms for password hashing with stretching:**
- **bcrypt**
- **PBKDF2**
- **Argon2**

In PHP, use `password_hash` and `password_verify` functions which handle bcrypt with automatic salting and stretching.

### Using `password_hash` in PHP

```php
// Hash a password
$hash = password_hash($password, PASSWORD_DEFAULT);

// Verify a password
$result = password_verify($password, $hash);
```

**Example output:**

```bash
$ php password_hash.php
$2y$10$aK/QUusxPpngo3Pk3NWujP2kOPu#MPp7c7b1Fv6//WdlxeK7e
```

- `$2y$` prefix indicates bcrypt
- `$10$` indicates 10 rounds of stretching (2^10 = 1024 iterations)
- The salt is automatically generated and embedded in the hash string
- `password_verify` extracts the salt and parameters automatically

---

### Password Leak Channels (Column)

Passwords can leak through multiple channels beyond SQL injection:
- **Backup media theft**: Database backups on tape, USB, CD-R taken outside
- **Hard disk theft**: Data center physical security compromise
- **Version control leaks**: Passwords committed to source code (e.g., GitHub)
- **Internal operator theft**: Insider access to plaintext data

---

## 5.1.4 Auto-Login

Auto-login is implemented when a web application has an "auto-login" or "keep me logged in" checkbox on the login form.

### Dangerous Implementation Example

A dangerous approach stores user name and auto-login flag in plaintext cookies:

```
Set-Cookie: user=yamada; expires=Wed, 27-Oct-2010 06:20:55 GMT
Set-Cookie: autologin=true; expires=Wed, 27-Oct-2010 06:20:55 GMT
```

**Problems:**
- If an attacker knows the cookie format, they can forge cookies for any user
- Storing sensitive information in cookies risks XSS theft and data exposure

### Safe Auto-Login Implementation Methods

Three recommended approaches:
1. **Extend the session lifetime**
2. **Use tokens**
3. **Use authentication tickets**

### Method 1: Extend Session Lifetime

Set the session cookie's `Expires` attribute to a longer period (e.g., 1 week) and extend the `session.gc_maxlifetime` in PHP.

```php
// Extend session cookie to 1 week
session_set_cookie_params($timeout); // Set Expires on session cookie

// In php.ini: extend gc_maxlifetime
session.gc_probability = 1
session.gc_divisor = 1000
session.gc_maxlifetime = 604800  // 7 * 24 * 60 * 60
```

**For auto-login detection:**

```php
<?php
// Session timeout check for auto-login
$autologin = ($_GET['autologin'] === 'on');
$timeout = 30 * 60;  // 30 minutes default
if ($autologin) {
    $timeout = 7 * 24 * 60 * 60;  // 1 week for auto-login
    session_set_cookie_params($timeout);
}

session_start();
session_regenerate_id(true);
$_SESSION['id'] = $id;
$_SESSION['timeout'] = time() + $timeout;  // Timeout timestamp
```

**Session timeout verification:**

```php
<?php
session_start();
function islogin() {
    if (!isset($_SESSION['id'])) {
        return false;  // Not logged in
    }
    if ($_SESSION['expires'] < time()) {
        $_SESSION = array();  // Clear session
        session_destroy();    // Session timeout
        return false;
    }
    // ... update timeout ...
    return true;
}
```

### Method 2: Token-Based Auto-Login

Use a separate `autologin` table to store tokens with expiration dates. Tokens are stored in cookies and verified against the database.

**Token data structure:**

| Column | Description |
|--------|-------------|
| token | Random token string |
| user_id | Associated user ID |
| expires | Expiration timestamp |

**Token issuance (on login):**

```php
function set_auth_token($id, $expires) {
    do {
        $token = random_token();
        // INSERT into autologin table
        $result = query('INSERT INTO autologin VALUES(?, ?, ?)');
        if ($result) {
            return $token;
        }
    } while(true);

    $timeout = 7 * 24 * 60 * 60;  // 1 week
    $expires = time() + $timeout;
    $token = set_auth_token($id, $expires);
    setcookie('token', $token, $expires);  // Set cookie
}
```

**Token verification (on page access):**

```php
function check_auth_token($token) {
    $result = query('SELECT * FROM autologin WHERE token = ?');
    $id = validate($result);
    if (!$record) {
        return false;
    }
    if ($expires < time()) {  // Token expired
        // Delete old token
        return false;
    }
    return $id;
}
```

**Logout processing:**

```php
function islogin($token) {
    // Check session first
    if (session has auth info) {
        return true;  // Already logged in via session
    }
    // Try auto-login via token
    $id = check_auth_token($token);
    if ($id) {
        // Set session, regenerate token
        return true;
    }
    return false;  // Not authenticated
}
```

### Method 3: Authentication Ticket

An authentication ticket stores authentication information (user, expiration, etc.) encrypted outside the server. This approach is used in Windows Kerberos, ASP.NET forms authentication, etc.

**Advantages:** No server-side storage needed for tokens.

### Comparison of the Three Methods

| Feature | Session Extension | Token | Auth Ticket |
|---------|------------------|-------|-------------|
| Auto-login transparent to user | No | Yes | Yes |
| Multiple device login support | Yes | Yes | Yes |
| Admin can manage login status | No | Yes | Yes |
| Secret info sent to client | No (session ID only) | No (random token) | Yes (encrypted) |

### Reducing Auto-Login Risk

Auto-login keeps the authenticated state longer, increasing the window for XSS, CSRF, and other session-based attacks.

**Mitigations:**
- For sensitive operations (viewing personal info, purchases, money transfers, password changes), **require password re-entry**
- Amazon's approach: auto-login shows general pages, but requires re-authentication for checkout and account settings

---

## 5.1.5 Login Form

Guidelines for the login form (ID and password input screen):

- **Password input field should be masked** (use `type="password"`)
- **Use HTTPS**

### Password Masking

Using `type="password"` on the input element hides the entered password as dots/asterisks, preventing shoulder surfing.

**Recent trends:** Some sites now offer a "show password" toggle (eye icon). Facebook, PayPal, and others let users click an icon to temporarily reveal the password.

### Why HTTPS is Required

1. **Prevent eavesdropping** on passwords transmitted over the network
2. **Prevent form tampering** by man-in-the-middle attacks
3. **Verify server authenticity** -- users can confirm they are on the real site, not a phishing site

Modern browsers (Chrome, Firefox) show warnings for HTTP login pages (e.g., "Not Secure" badge).

---

## 5.1.6 Error Message Requirements

### Why ID and Password Errors Should Be Indistinguishable

Error messages on login failure should not reveal whether the **ID or the password** was wrong.

**Bad examples:**
- "The specified user does not exist."
- "Password is incorrect."

These messages allow attackers to:
- Enumerate valid user IDs (try IDs until "password incorrect" appears, confirming the ID exists)
- Then brute force just the password for confirmed IDs

**Search space comparison:**

| Scenario | Combinations to Try |
|----------|-------------------|
| ID and password both unknown (independent) | ID_count x Password_count |
| ID and password both unknown (indistinguishable error) | ID_count x Password_count |
| ID confirmed first, then password | ID_count + Password_count |

When errors reveal which is wrong, the attacker only needs `ID_count + Password_count` attempts instead of `ID_count x Password_count`.

**Best practice:** Display a generic message like:
> "ID or password is incorrect, or the account is locked."

### Account Lockout and Error Messages

When account lockout is implemented, the error message should not change (to avoid revealing that an account exists). Notify the user via email instead.

### Two-Step Login (ID and Password on Separate Screens)

Many modern sites (e.g., Google) ask for the ID first, then the password on a separate screen.

- If the ID is not registered, an error is shown
- If the ID is registered, "Welcome" is shown with the password prompt

This approach accepts the trade-off of revealing valid IDs in exchange for improved usability. The rationale is that login UX improvements and reduced user frustration outweigh the ID enumeration risk, especially when combined with other protections (2FA, account lockout, etc.).

---

## 5.1.7 Logout Function

Logout must be implemented securely:

- **Use POST method** for logout (logout has side effects -- destroying the session)
- **Destroy the session** on the server side
- **Include CSRF protection** (use CSRF tokens)

### Logout Implementation

**Logout form (sends POST with CSRF token):**

```html
<form action="51-012.php" method="POST">
  <!-- CSRF token -->
  <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
  <input type="submit" value="Logout">
</form>
```

**Logout processing script:**

```php
<?php
session_start();
$p_token = filter_input(INPUT_POST, 'token');
$s_token = $_SESSION['token'];
// Token verification
if (empty($p_token) || $p_token !== $s_token) {
    die('Logout button was not clicked properly');
}
// Clear session variables
$_SESSION = array();
// Destroy session (server-side logout)
session_destroy();

// Delete auto-login token if exists
$query = 'DELETE FROM autologin WHERE id=?';
// Execute query
?>
<body>
Logged out.<br>
<a href="51-011.php">Back</a>
</body>
```

**Key points:**
- The first part generates a CSRF token at `session_start()` if one doesn't exist
- The logout script verifies the CSRF token, clears session data, destroys the session
- If auto-login is used, also clean up the auto-login token/cookie

---

## 5.1.8 Authentication Summary

| Topic | Reference |
|-------|-----------|
| Password character types and length | Section "Password Character Types and Length Requirements" |
| Active password policy checks | Section "Active Password Policy Checks" |
| Brute force countermeasures | Section 5.1.2 |
| Password storage | Section 5.1.3 |
| Login form and input screen | Section 5.1.5 |
| Error message requirements | Section 5.1.6 |
| Auto-login and logout security | Sections 5.1.4 and 5.1.7 |

---

## Key Takeaways

1. **Never store passwords in plaintext** -- use bcrypt, PBKDF2, or Argon2 with salt and stretching
2. **Account lockout** is essential but insufficient alone -- combine with 2FA and password policy checks
3. **Error messages must not reveal** whether the ID or password was wrong
4. **Auto-login** should use tokens or extended sessions, never plaintext cookies
5. **Logout must use POST** with CSRF protection and fully destroy the session
6. **HTTPS is mandatory** for all login-related pages
7. **Password list attacks** are a growing threat -- encourage unique passwords and implement 2FA
