# Chapter 5.1: Authentication & Brute Force -- Hands-On Practice

## Book Reference

Chapter 5.1 of **"Safe Web Application Development"** (体系的に学ぶ 安全なWebアプリケーションの作り方) by Tokumaru Hiroshi (徳丸浩).

## Prerequisites

- Docker is running (`docker compose up -d` from the `chapter-2` setup folder)
- DVWA is accessible and you are logged in (default credentials: `admin` / `password`)
- OWASP ZAP is running on port `8081`
- FoxyProxy is active and routing browser traffic through ZAP

## What You'll Learn

- How brute force attacks work against login forms
- Dictionary attacks and why common passwords are dangerous
- Credential stuffing and password spraying techniques
- How account lockout policies defend against brute force
- Why strong passwords matter and how attackers prioritize guesses
- Password hashing concepts (salt, stretching, bcrypt)

## DVWA Module Covered

| Module | Path |
|---|---|
| Brute Force | `/vulnerabilities/brute/` |

---

## Exercise Steps

### Low Level

Set DVWA Security to **Low** via `DVWA Security` in the left menu.

1. Navigate to **DVWA > Brute Force**. You will see a simple login form with username and password fields.

2. Enter wrong credentials: username `admin`, password `wrongpass`. Click Login.
   - You will see the message: **"Username and/or password incorrect."**

3. Note the critical flaw: there is no lockout, no rate limiting, and no delay. You can try an unlimited number of passwords without any penalty.

4. Try common passwords manually, one at a time:

   | Attempt | Username | Password | Result |
   |---|---|---|---|
   | 1 | admin | admin | Incorrect |
   | 2 | admin | password | **Success** |
   | 3 | admin | 123456 | (would be incorrect) |

5. `admin` / `password` works. You just performed a **dictionary attack** -- guessing passwords from a list of commonly used ones rather than trying every possible combination.

6. Open OWASP ZAP and examine the traffic. Notice that each login attempt is a **GET request** with the username and password visible directly in the URL:
   ```
   GET /vulnerabilities/brute/?username=admin&password=wrongpass&Login=Login
   ```
   This is a security issue on its own -- credentials should never appear in URLs because they get logged in browser history, server logs, and proxy logs.

7. Now try an automated attack using ZAP's Fuzzer:
   - In ZAP's History tab, find one of the login GET requests.
   - Right-click the request and select **Attack > Fuzz**.
   - In the Fuzzer dialog, highlight the password value in the URL (e.g., `wrongpass`).
   - Click **Add** to add a payload. Select **Type: Strings** and enter common passwords, one per line:
     ```
     password
     123456
     admin
     letmein
     welcome
     monkey
     dragon
     master
     qwerty
     login
     ```
   - Click **Start Fuzzer**.
   - When the fuzzer finishes, look at the results table. Sort by **Size Resp. Body** -- the successful login will have a **different response size** compared to all the failed attempts.

8. This demonstrates automated brute force. What took multiple manual attempts can be done in seconds with a tool and a password list.

---

### Medium Level

Set DVWA Security to **Medium**.

1. Go to **DVWA > Brute Force** and try a wrong password: `admin` / `wrongpass`.
   - You still get "Username and/or password incorrect."
   - But notice the response is **slower**. The application now adds a **2-second delay** on every failed login attempt.

2. Try a few more wrong passwords. Each failed attempt takes noticeably longer than at the Low level.

3. Brute force still works -- the correct password (`admin` / `password`) is still accepted. The delay only slows down the attack; it does not prevent it.

4. In ZAP, compare the response times between Low and Medium:
   - **Low level**: Responses arrive almost instantly (milliseconds).
   - **Medium level**: Failed login responses take approximately 2 seconds each.
   - A password list of 10,000 entries would take about 5.5 hours at Medium versus seconds at Low.

5. Click **"View Source"** to see the code. Note the `sleep(2)` call on failed login -- this is a basic throttling mechanism.

---

### High Level

Set DVWA Security to **High**.

1. Go to **DVWA > Brute Force** and try a wrong password. The response is slow again, but this time the delay is **random** (0 to 3 seconds), making it harder to detect timing patterns.

2. Right-click the login page and **View Page Source** in the browser. Look for a hidden input field:
   ```html
   <input type='hidden' name='user_token' value='abc123def456...' />
   ```
   This is an **anti-CSRF token**. Each time the form loads, a new unique token is generated.

3. Try running ZAP's Fuzzer the same way you did at Low level. It will fail -- every request needs a valid `user_token`, but the fuzzer sends the same stale token for every attempt.

4. To brute force at this level, you would need to:
   - Send a GET request to load the login page.
   - Extract the `user_token` value from the response HTML.
   - Include that fresh token in your next login attempt.
   - Repeat for each password guess.

5. This makes automated attacks **significantly harder**. Simple fuzzing tools cannot handle the token rotation without custom scripting.

---

### Impossible Level

Set DVWA Security to **Impossible**.

1. Try three wrong passwords in a row:

   | Attempt | Password | Result |
   |---|---|---|
   | 1 | wrong1 | Incorrect |
   | 2 | wrong2 | Incorrect |
   | 3 | wrong3 | **Account locked** |

2. Now try the correct password: `admin` / `password`. The login is **still rejected** -- the account is locked for 15 minutes regardless of whether the correct password is provided.

3. Click **"View Source"** to examine the secure implementation. Note the defenses:
   - **PDO prepared statements**: Prevents SQL injection in the login query.
   - **CSRF token validation**: Each request must include a valid token.
   - **Account lockout**: After 3 failed attempts, the account is locked for 15 minutes.
   - **Timing-safe comparison**: Uses constant-time string comparison to prevent timing attacks.

4. This matches the book's recommended defense strategy from **Chapter 5.1.2** -- account lockout is the primary countermeasure against brute force attacks.

---

## What to Observe in ZAP

While performing the exercises, switch to OWASP ZAP and examine the traffic at each security level:

- **Low level**: Rapid-fire requests with usernames and passwords visible in the URL query string. No delays, no tokens, no limits.
- **Medium level**: Same request structure as Low, but responses for failed logins take approximately 2 seconds due to server-side `sleep()`.
- **High level**: Each request includes a unique `user_token` parameter. Replaying a request with a stale token will fail.
- **Across all levels**: Compare response body sizes. A successful login produces a different response size than a failed login. This size difference is how automated tools identify the correct password.

---

## Book Connection

| Practice exercise | Book equivalent |
|---|---|
| Manual dictionary attack at Low level | Book's dictionary attack description (section 5.1.2) |
| Account lockout at Impossible level | Book's primary recommended countermeasure (section 5.1.2) |
| Password hashing in source code | Book's password storage guidance -- salt + stretching (section 5.1.3) |
| CSRF token at High level | Book's defense-in-depth approach |
| Two-factor authentication (not in DVWA) | Book's enhanced defense recommendation |

---

## Key Takeaways

- Brute force attacks succeed when there are no limits on login attempts. Even a moderately strong password will fall given enough time and no lockout.
- Dictionary attacks are far more efficient than exhaustive brute force because most users choose common passwords. A list of the top 1,000 passwords covers a surprising percentage of real accounts.
- Time delays slow down attacks but do not prevent them. A determined attacker with enough time can still succeed.
- Anti-CSRF tokens make automated attacks harder by requiring a fresh token for each attempt, but they are a secondary defense, not a primary one.
- Account lockout is the book's recommended primary defense. Locking the account after a small number of failed attempts (e.g., 3 to 5) makes brute force impractical.
- Passwords must be stored as salted, stretched hashes (e.g., bcrypt via `password_hash()`), never as plaintext or simple hashes. If the database is compromised, proper hashing prevents attackers from recovering the original passwords.
- Defense in depth combines multiple layers: strong password policies, account lockout, CSRF tokens, rate limiting, and optionally two-factor authentication.
