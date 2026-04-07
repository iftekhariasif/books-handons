# Chapter 5.2 - Account Management

> From "Safe Web Application Development" by Tokumaru Hiroshi
> Chapter 5: Representative Security Features

---

## Overview

This section covers security considerations for account management functions, including user registration, password management, email address management, and account lifecycle. These functions are directly linked to security issues.

**Topics covered:**
- User registration
- Password change
- Email address change
- Password reset
- Account suspension
- Account deletion

---

## 5.2.1 User Registration

User registration typically involves registering a user ID (login ID), password, and email address. Security considerations include:

- **Email address verification**
- **User ID uniqueness prevention**
- **Handling automated (bot) registration**
- **Password-related notes** (see section 5.1)

### Common Vulnerabilities in User Registration

- SQL injection (section 4.4.1)
- Mail header injection (section 4.9.2)

---

### Email Address Verification

Email notifications are essential for many functions: password reset, account lockout notification, password change alerts, etc. Therefore, the registered email address must be verified as actually belonging to the user.

**Two methods for email verification:**

#### Method A: Token-containing URL

1. User enters their email address
2. Application sends an email with a URL containing a token
3. User clicks the URL to continue registration
4. The URL is a one-time link that verifies ownership

#### Method B: Token sent separately

1. User enters their email address
2. Application generates a token and sends it via email
3. User enters the token (confirmation number) on the registration page
4. Application verifies the token matches

**Comparison of Methods A and B:**

| Aspect | Method A (URL with token) | Method B (Token entry) |
|--------|--------------------------|----------------------|
| **Merit** | Simple UX; user just clicks a link | Token is only sent via email, reducing redirect risks |
| **Demerit** | URL may be intercepted; hidden parameter leaks possible | Requires user to manually enter the token; smartphone users may find it cumbersome |

Currently, Method A is more commonly used, but since email URLs can be previewed by users without clicking (potential phishing risk), the book recommends Method B.

---

### User ID Uniqueness Prevention

User IDs should be unique, but if not carefully implemented, security issues can arise.

#### Case 1: Same ID with Different Password

If a site allows registration with the same user ID but a different password (due to a bug), the original user's profile may be overwritten or exposed.

#### Case 2: No Uniqueness Constraint on User ID

If the application does not enforce a UNIQUE constraint on the user ID column in the database, special operations may allow duplicate IDs. This can lead to one user logging in as another. The author has encountered real-world cases of this vulnerability.

**Prevention:**
- Always use a UNIQUE constraint on the user ID in the database
- Check for the SQL Truncation vulnerability (Column SQL Truncation)

---

### CAPTCHA for Automated Registration Prevention

**CAPTCHA** (Completely Automated Public Turing test to tell Computers and Humans Apart) is used to prevent automated bot registration.

**How CAPTCHA works:**
- Display distorted text or images that are easy for humans but hard for bots to read
- User must correctly type the displayed text to proceed

**Modern alternatives:**
- **reCAPTCHA** (by Google): Uses risk analysis; may show a simple checkbox ("I'm not a robot") or image selection challenges
- **Audio CAPTCHA**: For accessibility
- **Puzzle-based CAPTCHA**: Newer alternatives focusing on usability

**When to use CAPTCHA:**
- When automated account creation poses a risk (spam accounts, abuse)
- When read-aloud software accessibility is not a concern (or provide audio alternative)
- Consider the trade-off: CAPTCHA can hurt accessibility and user experience

---

## 5.2.2 Password Change

Security considerations for the password change function:

### Requirements

- **Verify the current password** before allowing a change
- **Notify via email** when a password is changed
- **For managed/IoT passwords set by administrators**, force users to change on first login

### Vulnerabilities Common in Password Change Functions

- **SQL injection**
- **CSRF vulnerability**

### Verifying the Current Password

The password change screen should require entering the **current password** along with the new password. This prevents:
- An attacker who has hijacked a session from changing the password
- Unauthorized password changes via CSRF

### Email Notification on Password Change

When a password change is processed, send a notification email to the user. This ensures:
- If a third party changed the password, the legitimate user is alerted
- The user can take action (contact support, reset password)

### Vulnerabilities in Password Change

#### SQL Injection

If the password change form has SQL injection vulnerability, it may allow:
- Requesting re-authentication and changing passwords of all users at once
- Changing one specific user's password

**Mitigation:** Use parameterized queries.

#### CSRF

If the password change form has CSRF vulnerability, a third party could:
1. Force the victim to change their password via a forged request
2. Then log in with the new password

However, if re-authentication (current password) is required, CSRF is mitigated since the attacker doesn't know the current password.

---

## 5.2.3 Email Address Change

Email address changes are security-sensitive because:
- Password reset links are sent to the registered email
- Account lockout notifications go to the registered email

If an attacker changes the email, they can use password reset to take over the account.

### Threats

- Session hijacking
- CSRF attack
- SQL injection attack

### Functional Countermeasures for Email Change

- **Verify the new email address** (send confirmation to new address; see "Email Address Verification" above)
- **Re-authenticate** before allowing change
- **Send notification to the old email address** (so the original owner is alerted)

### Email Address Change Countermeasures Summary

| Category | Measures |
|----------|----------|
| **Functional** | Email verification, re-authentication |
| **Anti-exploit** | Prevent SQL injection, CSRF (re-authentication helps mitigate CSRF automatically) |
| **Notification** | Send email to old address after change |

---

## 5.2.4 Password Reset

When users forget their password, a reset mechanism is needed. There are two types:

### Administrator-Initiated Password Reset

When a user contacts support after forgetting their password:

1. **Verify the requester's identity** (ask security questions, verify via phone, etc.)
2. **Administrator sets a temporary password**
3. **User logs in with the temporary password** and is immediately prompted to change it
4. **Temporary password is single-use** and expires after a set period

**Implementation guidelines:**
- The temporary password should be displayed to the administrator **only once** (not via email)
- The temporary password should only allow password changes, not full account access
- Auto-expire: If the temporary password is not used within 30 minutes, automatically invalidate it
- Require administrator to verify identity before issuing the reset

### User-Initiated Password Reset

Users can reset their own password without contacting support.

#### Identity Verification Methods

The user must prove their identity. Common approaches:
- Send a verification email to the registered email address
- Use 2FA (SMS code, authenticator app)

#### Password Notification Methods

After identity verification, there are four approaches:

| Method | Description | Assessment |
|--------|-------------|------------|
| **(A)** Send current password via email | Requires storing password in recoverable form | **Not recommended** -- insecure |
| **(B)** Send password change URL via email | Email contains a link to a password change page | Somewhat secure |
| **(C)** Issue a temporary password via email | Temporary password is sent; only allows password change | Somewhat secure |
| **(D)** Issue a temporary password without email | Temporary password shown on screen after 2FA verification | **Recommended** -- most secure |

**Method (D) flow:**
1. User enters their registered email address
2. Application sends a confirmation code to the email (or via SMS)
3. User enters the code on the verification page
4. A new temporary password is generated and displayed on screen
5. User must immediately change the password
6. Notification email is sent to confirm the reset occurred

**Method (C) flow:**
1. User enters their registered email address
2. Application generates a token and sends it to the email
3. User clicks the link or enters the token
4. Application verifies the token and issues a temporary password
5. The temporary password is sent via a separate email
6. User logs in with the temporary password and changes it

**Important security notes:**
- Temporary passwords should have a short expiration (e.g., 30 minutes)
- After use, the temporary password is invalidated
- If unused and expired, the account is locked and a reset notification email is sent

---

## 5.2.5 Account Suspension

Accounts should be suspended when security issues arise:

**Reasons for suspension:**
- User request (e.g., lost device, stolen phone, unwanted email received)
- Unauthorized access detected
- Violation of terms of service

**Suspension should:**
- Be handled by administrators with appropriate privilege
- Require identity verification before processing user requests
- Be reversible (with proper re-verification)

---

## 5.2.6 Account Deletion

Account deletion should:
- Be performed after identity verification (password re-entry)
- Include CSRF protection
- Handle associated data properly

**Additional vulnerability concerns:**
- SQL injection in the deletion process

---

## 5.2.7 Account Management Summary

**Key principles:**
- Users must verify their email address upon registration
- Always perform email verification before accepting addresses
- Send email notifications for all critical operations
- Protect all management functions against SQL injection, CSRF, and mail header injection

| Vulnerability | Applicable Functions |
|---------------|---------------------|
| SQL injection | Registration, password change, email change, deletion |
| CSRF | Password change, email change, deletion |
| Mail header injection | Registration, email change |

---

## Key Takeaways

1. **Always verify email addresses** during registration and changes -- use token-based confirmation
2. **Require current password** for sensitive changes (password change, email change, account deletion)
3. **Send notifications** to the registered email for all critical account operations
4. **Password reset** should use temporary passwords with short expiration, ideally shown on screen (not emailed)
5. **CAPTCHA** helps prevent automated account creation but must be balanced with accessibility
6. **Database constraints** (UNIQUE on user ID) are essential -- do not rely solely on application logic
7. **CSRF protection** is required for all state-changing account operations
