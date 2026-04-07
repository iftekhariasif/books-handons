# Brute Force Practice -- Quick Reference

## DVWA URL Path

| Module | URL Path |
|---|---|
| Brute Force | `/vulnerabilities/brute/` |

---

## Common Password List (Top 10)

Use these for manual and automated testing:

| Rank | Password |
|---|---|
| 1 | `123456` |
| 2 | `password` |
| 3 | `123456789` |
| 4 | `12345678` |
| 5 | `12345` |
| 6 | `qwerty` |
| 7 | `abc123` |
| 8 | `111111` |
| 9 | `letmein` |
| 10 | `admin` |

DVWA's correct credentials: `admin` / `password` (rank 2 on this list).

---

## Security Level Comparison

| Level | Protection | Delay | CSRF Token | Lockout | Brute Force Feasible? |
|---|---|---|---|---|---|
| Low | None | None | No | No | Yes -- trivial |
| Medium | Sleep on failure | 2 seconds | No | No | Yes -- slow but possible |
| High | Random delay + token | 0-3 seconds | Yes | No | Difficult -- requires scripting |
| Impossible | Full defense | Yes | Yes | 3 attempts then 15-min lock | No -- impractical |

---

## Book Chapter References

| Section | Topic |
|---|---|
| 5.1.1 | Login functionality and authentication flow |
| 5.1.2 | Brute force attacks and countermeasures (lockout, rate limiting) |
| 5.1.3 | Password storage (hashing with salt and stretching) |

---

## Attack Types

| Attack Type | Description | Example |
|---|---|---|
| Brute force | Try every possible combination | `aaa`, `aab`, `aac`, ... `zzz` |
| Dictionary attack | Try passwords from a common-password list | `password`, `123456`, `admin`, ... |
| Reverse brute force | Fix the password, try many usernames | password `123456` against `user1`, `user2`, `user3`, ... |
| Password spray | Try one common password against many accounts at once | `Password1` against all known accounts, then `123456`, etc. |
| Credential stuffing | Use username/password pairs leaked from other breaches | Leaked pairs from Site A tried against Site B |

---

## Book's Password Policy Recommendation

From section 5.1.2:

- **Minimum length**: 8 characters or more
- **Character types**: Require a mix of uppercase, lowercase, digits, and symbols
- **Prohibit common passwords**: Reject passwords that appear in known dictionaries
- **No password hints**: Do not provide hints that help attackers guess
- **Encourage passphrases**: Longer passwords are stronger than short complex ones

---

## Book's Password Storage: `password_hash()` with bcrypt

From section 5.1.3 -- never store passwords as plaintext or simple hashes.

**Correct approach (PHP):**

```php
// Hashing a password (during registration)
$hash = password_hash($password, PASSWORD_BCRYPT);
// Store $hash in the database

// Verifying a password (during login)
if (password_verify($input_password, $stored_hash)) {
    // Login successful
} else {
    // Login failed
}
```

**Why bcrypt:**

| Feature | Purpose |
|---|---|
| Salt | Unique random value per password; prevents rainbow table attacks |
| Stretching | Repeated hashing (cost factor); makes brute force on the hash slow |
| Adaptive | Cost factor can be increased as hardware gets faster |

**What NOT to do:**

```php
// BAD: plaintext
$stored = $password;

// BAD: simple hash without salt
$stored = md5($password);

// BAD: SHA-256 without salt or stretching
$stored = hash('sha256', $password);
```

---

## Common Mistakes

- **No account lockout**: Allowing unlimited login attempts makes brute force trivial. Always lock or throttle after a small number of failures.
- **Lockout by IP only**: Attackers can rotate IP addresses. Lock the account itself, not just the source IP.
- **Generic error messages done wrong**: The message "Username and/or password incorrect" is correct -- it does not reveal whether the username exists. Saying "Password incorrect" confirms the username is valid.
- **Storing passwords as MD5 or SHA-1**: These are fast hashes designed for integrity checks, not password storage. Use bcrypt, scrypt, or Argon2.
- **No salt**: Without a unique salt per password, identical passwords produce identical hashes, enabling rainbow table attacks.
- **Credentials in GET parameters**: DVWA's Low level sends username and password in the URL. These appear in browser history, server access logs, and proxy logs. Always use POST for login forms.
- **Missing CSRF token on login forms**: Without a token, attackers can craft pages that submit login requests on the user's behalf (login CSRF).
- **Relying only on client-side rate limiting**: Any client-side restriction can be bypassed. Rate limiting must be enforced on the server.
