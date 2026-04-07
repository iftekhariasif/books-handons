# Chapter 5.3: Authorization Bypass -- Hands-On Practice

Book reference: Chapter 5.3 of "Safe Web Application Development" (Taiikuteki Web Application no Tsukurikata) by Tokumaru Hiroshi

---

## Prerequisites

- Docker running with DVWA container (`docker compose up -d`)
- DVWA logged in (admin / password) at `http://localhost:8080`
- OWASP ZAP running and intercepting traffic
- FoxyProxy enabled in Firefox, routing through ZAP

## What You'll Learn

- Insecure Direct Object References (IDOR) -- accessing resources by manipulating identifiers
- Horizontal privilege escalation -- accessing another user's data at the same privilege level
- Vertical privilege escalation -- accessing functionality reserved for higher-privilege roles
- Why client-side access control (hiding menus, disabling buttons) is insufficient
- Proper server-side authorization checks on every request

## DVWA Module

**Authorisation Bypass** -- `http://localhost:8080/vulnerabilities/authbypass/`

Also uses **Insecure CAPTCHA** (`/vulnerabilities/captcha/`) for related client-side bypass concepts.

---

## Exercise Steps

### Low Level

1. Go to **DVWA > Authorisation Bypass**. As the admin user, you can see all user data displayed on the page.
2. Open ZAP and observe the API calls being made as the page loads. Look at the URLs in ZAP's History tab.
3. Note the URL pattern -- it may include user IDs or resource identifiers in the path or query parameters. For example:
   ```
   /vulnerabilities/authbypass/get_user_data.php?user_id=1
   ```
4. Try changing the user ID parameter to access other users' data. Increment or decrement the ID:
   ```
   /vulnerabilities/authbypass/get_user_data.php?user_id=2
   /vulnerabilities/authbypass/get_user_data.php?user_id=3
   ```
5. You can view and potentially modify other users' information. This is **IDOR (Insecure Direct Object Reference)** -- the book's most typical authorization flaw example.
6. Now try a vertical escalation test: while logged in as admin, copy a URL that only admin should be able to access.
7. Log out of DVWA.
8. Log in as a lower-privilege user: **gordonb / abc123**.
9. Paste the admin-only URL into the browser. Can you still access it? At Low level, the answer is yes -- there is no server-side authorization check.

### Medium Level

1. Set DVWA security to **Medium** (DVWA Security > Medium > Submit).
2. Some access control checks are now in place, but they may be incomplete.
3. Try accessing admin pages directly via URL while logged in as a non-admin user (gordonb).
4. Check if the application only hides menu items in the UI but does not enforce server-side authorization. Navigate directly to:
   ```
   http://localhost:8080/vulnerabilities/authbypass/
   ```
5. Try manipulating request parameters in ZAP -- change user IDs, modify POST body values.
6. This matches the book's **"menu display/hide-only" authorization flaw** -- the UI removes the link, but the endpoint remains unprotected.

### High Level

1. Set DVWA security to **High**.
2. Better server-side checks are now in place.
3. Try the same URL manipulation techniques -- access admin-only URLs as gordonb, change user IDs in requests.
4. You should get blocked or redirected on most attempts.
5. But check carefully: are **ALL** endpoints protected, or just some? Test every API endpoint you discovered in ZAP at Lower levels.
6. Sometimes developers protect the main page but forget to protect the underlying API calls.

### Impossible Level

1. Set DVWA security to **Impossible**.
2. Full server-side authorization is enforced on every request.
3. Click **View Source** -- examine the code. Each action verifies the user's role via the session before processing the request:
   ```php
   // Every request checks: does this session belong to an authorized user?
   if ($_SESSION['role'] !== 'admin') {
       die('Unauthorized');
   }
   ```
4. This is the book's recommended approach: **always verify authorization on the server for every request**, regardless of what the client-side UI shows or hides.

---

## Also Try: DVWA > Insecure CAPTCHA

This module demonstrates how client-side validation can be bypassed, which is conceptually related to authorization bypass.

1. Go to **DVWA > Insecure CAPTCHA** at Low level.
2. The CAPTCHA check happens client-side -- the form submission is a two-step process.
3. In ZAP, intercept the request and observe the parameters. You can skip the CAPTCHA step entirely by modifying the request to go straight to step 2:
   ```
   step=2&password_new=hacked&password_conf=hacked&Change=Change
   ```
4. This demonstrates the same principle as authorization bypass: **any check that only happens on the client side can be bypassed by an attacker who controls the request**.

---

## What to Observe in ZAP

| What to Look For | Where in ZAP |
|---|---|
| Resource IDs in URLs (`user_id=1`, `/users/3`) | History tab -- examine URL paths and query strings |
| Resource IDs in POST parameters | Request tab -- look at POST body content |
| Authorization headers or session cookies | Request tab -- check Cookie and Authorization headers |
| Different responses for different users | Response tab -- compare response bodies when accessing the same URL as different users |
| Hidden API endpoints | Sites tab -- expand the site tree to find all discovered endpoints |

- Try modifying resource IDs to access other users' data (horizontal escalation).
- Try accessing admin-only endpoints as a regular user (vertical escalation).
- Compare the session cookies between admin and non-admin users -- does the server actually validate the session's role?

---

## Book Connection

| Practice Element | Book Reference |
|---|---|
| Changing user IDs to access other users' data (IDOR) | Section 5.3.2 -- "changing resource IDs" flaw |
| Menu items hidden but endpoints still accessible | Section 5.3.2 -- "display/hide-only" flaw |
| Hidden form fields or cookies holding privilege info | Section 5.3.2 -- "cookies holding privilege info" flaw |
| Full server-side role check on every request | Section 5.3.3 -- permission matrix and authorization requirements |
| Comparing Low vs Impossible source code | Section 5.3.4 -- implementation of proper authorization |

---

## Key Takeaways

1. **Authorization must be enforced on the server for every request.** Hiding a menu item or disabling a button on the client side is not access control. An attacker can craft requests directly.
2. **IDOR is the most common authorization flaw.** If a resource is identified by a predictable ID (sequential numbers, usernames), any authenticated user can try to access any other user's resources by changing the ID.
3. **Horizontal and vertical escalation are both critical.** Horizontal means accessing another user's data at your own privilege level. Vertical means accessing functionality reserved for a higher role (e.g., admin functions as a regular user).
4. **Every endpoint needs authorization checks, not just the main page.** Developers often protect the UI page but forget to protect the underlying API calls that the page makes.
5. **A permission matrix is essential for design.** The book recommends listing all resources, all roles, and all operations in a matrix to ensure nothing is missed during implementation.
6. **Client-side validation (CAPTCHA, JavaScript checks) is not security.** It improves user experience but provides zero protection against an attacker who controls the HTTP request.
