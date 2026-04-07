# Chapter 5.3 - Authorization

> From "Safe Web Application Development" by Tokumaru Hiroshi
> Chapter 5: Representative Security Features

---

## Overview

This section covers authorization (access control) in web applications -- how to properly restrict which users can access which resources and perform which operations.

---

## 5.3.1 What is Authorization?

**Authorization** is the process of granting specific privileges to authenticated users. Examples of privilege levels:

### Privileges Granted to Authenticated Users

| Category | Examples |
|----------|----------|
| **Operations permitted only for that user** | Payment processing, deposits/transfers, new user creation (admin only), etc. |
| **Information viewable only by that user** | Personal information (address, phone, email, etc.), non-public information about the organization, other users' data (for admins) |
| **Editing permitted only for that user** | SNS posts and edits, personal settings (password, profile, display settings), other users' settings (admin only), web email, etc. |

---

## 5.3.2 Typical Authorization Flaws

### Flaw 1: Knowing the Resource URL Allows Unauthorized Access

If a resource is protected only by keeping its URL secret, anyone who discovers the URL can access it.

**Example:**
- A user logs in and views their profile at `http://example.jp/profile.php?id=yamada`
- By simply changing the `id` parameter to `id=sato`, they can view another user's profile

**The same applies to:**
- POST parameters
- Hidden form fields
- Cookie values

This is a common and easily overlooked vulnerability.

### Flaw 2: Menu Display/Hide as the Only Access Control

If authorization is enforced only by showing or hiding menu links (not checking permissions server-side), an attacker can:
- Directly access restricted URLs
- Guess administrative URLs

**Example flow of exploitation:**

1. A general user logs in and sees only general menu items
2. The user discovers that the admin panel URL is `http://example.jp/b001.php`
3. By directly entering this URL in the browser, the admin page loads with full functionality

**How attackers discover admin URLs:**
- Guess common patterns: `/admin`, `/manage`, `/a001.php`
- Try words commonly used in admin menus: `admin`, `root`, `manage`
- Check URL patterns from previous pages
- Look at Referer headers, SNS posts, or address bar screenshots
- Search through open-source software documentation or source code

### Flaw 3: Hidden Parameters or Cookies Store Authorization Info

If authorization decisions are based on hidden form fields or cookie values, attackers can tamper with them.

**Example:**
- A cookie contains `userkind=admin` which grants admin access
- An attacker modifies their cookie to include this value

Even if the parameter name seems hard to guess, this is security through obscurity and is unreliable.

---

### Authorization Flaws Summary

All the above patterns share a common problem: **URL, hidden parameters, or cookies are used to enforce authorization, and these can be manipulated by the client.**

**Correct approach:** Store authorization information in **server-side session variables** and verify permissions **before every operation**.

---

### Embedding Secret Information in URLs (Column)

When authorization or secret information must be embedded in URLs (e.g., password reset links), follow these three guidelines:

1. **Use sufficiently long random strings** for the URL token
2. **Make URLs time-limited** (short expiration)
3. **Make URLs single-use** (invalidate after first access)

**How attackers might discover secret URLs:**
- **Referer header leaks**: If a secret URL page links to external resources, the Referer header exposes it
- **Shared via SNS or email**: Users may accidentally share URLs
- **Browser address bar**: Visible to shoulder surfers
- **Server logs**: URLs are recorded in access logs

Even with these measures, if a POST method is used with the secret embedded in hidden fields, the URL itself remains clean, but the form data can still leak.

---

## 5.3.3 Authorization Requirement Definition

To implement authorization correctly, you must first define the requirements clearly as part of the design phase.

**This is not just a technical task** -- it requires domain knowledge and understanding of the business rules. The design document should explicitly specify "what should/shouldn't be possible" rather than just "what the system does."

**Common mistake:** Assuming "it shouldn't be like that" without writing it down explicitly. Developers may not share the same assumptions as designers.

### Permission Matrix

Define a **permission matrix** that maps roles to operations.

**Example: SaaS Application Permissions**

A SaaS product serving multiple companies might have three roles:
- **System Administrator** (manages all companies)
- **Company Administrator** (manages their own company)
- **General User** (regular employee)

| Operation | System Admin | Company Admin | General User |
|-----------|:----------:|:-------------:|:----------:|
| Company creation | Yes | No | No |
| Company admin creation/deletion | Yes | No | No |
| Company user creation/deletion | Yes | Yes | No |
| Own password change | Yes | Yes | Yes |
| Others' password change | Yes | No | No |

By defining such a matrix explicitly during design, development and testing can be done accurately.

### Roles (Column)

In the permission matrix above, "System Administrator", "Company Administrator", and "General User" are called **roles**. A role groups permissions together for assignment to users.

**Benefits of roles:**
- When administrators change, permissions transfer seamlessly
- Multiple admins sharing a role prevents single-point-of-failure

**Best practice:** Assign one role per person (1:1 mapping of ID to role). Avoid giving the same account to multiple people (e.g., shared `admin` or `root`), because:
- If multiple people share an account, you cannot determine who performed a specific action
- Shared passwords increase the risk of accidental exposure

---

## 5.3.4 Correct Authorization Implementation

Most authorization flaws stem from implementing checks only at the **UI level** (showing/hiding menus) rather than at the **server-side logic level**.

### Correct Implementation Principles

1. **Check permissions at the point of operation** -- verify the user's role/permissions before executing any action (view, modify, delete)
2. **Store user information in session variables** -- do not rely on external data from cookies, hidden parameters, or URL parameters that can be manipulated
3. **Use the user ID from the session** to determine which resources can be accessed
4. **Store permission information in session variables** to avoid re-querying the database on every request
5. **Never store permission data in cookies or hidden parameters**

---

## 5.3.5 Summary

**Authorization flaws are common** and stem from:
- Using URL or hidden parameters for access control
- Using cookies to store permission levels
- Only hiding/showing menu items without server-side enforcement

**Correct approach:**
- Store permission information in **session variables** (which are server-side and cannot be tampered with by the client)
- Check permissions **before every operation** that requires authorization
- Define a clear **permission matrix** during the design phase

---

## Key Takeaways

1. **Never rely on URL parameters, cookies, or hidden fields** for authorization -- these are client-controllable
2. **Always enforce authorization server-side** using session-stored role/permission data
3. **Create a permission matrix** during design that maps roles to allowed operations
4. **Menu visibility is not security** -- hiding links does not prevent direct URL access
5. **Secret URLs** must use long random tokens, be time-limited, and be single-use
6. **Use roles** to group permissions and assign them to users (one role per person)
7. **Explicitly document** what users should and should not be able to do -- do not rely on assumptions
