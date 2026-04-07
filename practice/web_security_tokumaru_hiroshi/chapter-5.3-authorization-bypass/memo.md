# Authorization Bypass Practice -- Quick Reference

## DVWA URL

```
http://localhost:8080/vulnerabilities/authbypass/
```

---

## DVWA Default Users

| Username | Password | Role |
|---|---|---|
| admin | password | Admin |
| gordonb | abc123 | User |
| 1337 | charley | User |
| pablo | letmein | User |
| smithy | password | User |

Use admin for initial exploration. Use gordonb (or other non-admin users) to test authorization bypass.

---

## Authorization Flaw Types (from Book Section 5.3.2)

| Flaw Type | Description | Example |
|---|---|---|
| 1. URL/parameter manipulation | Changing resource IDs in URLs or POST data to access other users' resources | `/get_user.php?id=1` changed to `?id=2` |
| 2. Display/hide-only control | Menu items or links are hidden in the UI but endpoints remain accessible | Admin page hidden from nav but accessible via direct URL |
| 3. Client-side privilege storage | Role or privilege information stored in cookies or hidden fields that the user can modify | `Cookie: role=admin` or `<input type="hidden" name="role" value="user">` |
| 4. Referer-based access control | Authorization depends on which page the user came from, easily spoofed | Server checks `Referer` header instead of session role |

---

## Security Level Comparison

| Level | Authorization Check | IDOR Possible? | Vertical Escalation? | Bypass Method |
|---|---|---|---|---|
| Low | None | Yes | Yes | Change user_id parameter or access admin URLs directly |
| Medium | Partial (UI-level) | Possibly | Possibly | Direct URL access, parameter manipulation in ZAP |
| High | Server-side (most endpoints) | No (mostly) | No (mostly) | Check for unprotected API endpoints |
| Impossible | Full server-side on every request | No | No | Cannot bypass -- every request validates session role |

---

## Book Chapter Reference

- **5.3.1** -- Definition: what is authorization and how it differs from authentication
- **5.3.2** -- Typical authorization flaws (the 4 types listed above)
- **5.3.3** -- Authorization requirements and the permission matrix approach
- **5.3.4** -- Implementation: server-side role checking on every request

---

## OWASP Reference

**A01:2021 -- Broken Access Control**

The number one risk in the OWASP Top 10 (2021). Covers:
- IDOR (Insecure Direct Object References)
- Missing function-level access control
- Privilege escalation
- CORS misconfiguration
- Metadata manipulation (tampering with tokens, cookies, hidden fields)

---

## Book's Permission Matrix Example

A permission matrix maps roles to resources and allowed operations:

| Resource | Admin | User (self) | User (other) |
|---|---|---|---|
| View user list | Allow | Deny | Deny |
| View own profile | Allow | Allow | Deny |
| Edit own profile | Allow | Allow | Deny |
| View other's profile | Allow | Deny | Deny |
| Delete user | Allow | Deny | Deny |
| Change security settings | Allow | Deny | Deny |

Every endpoint must enforce this matrix on the server side. If an operation is "Deny" for a role, the server must reject the request -- not just hide the button.

---

## Common Mistakes

1. **Checking authorization only on the UI layer** -- hiding links/buttons but leaving API endpoints open.
2. **Using sequential/predictable IDs** -- makes IDOR trivial. Use UUIDs or indirect references where possible.
3. **Trusting client-supplied role data** -- never read the user's role from a cookie, hidden field, or request parameter. Always read it from the server-side session.
4. **Forgetting to check authorization on AJAX/API endpoints** -- the main page is protected but the XHR calls it makes are not.
5. **Inconsistent checks across endpoints** -- some pages check authorization, others do not. Use middleware or a centralized authorization layer.

---

## Quick Test Workflow

```bash
# 1. Start DVWA
docker compose up -d

# 2. Log in as admin, explore the Authorisation Bypass page
# 3. Note all URLs and API calls in ZAP
# 4. Log out, log in as gordonb/abc123
# 5. Replay the admin URLs -- check what is still accessible

# Check DVWA logs
docker logs dvwa 2>&1 | grep -i auth
```

---

## Key ZAP Actions

```
1. History tab > filter for /vulnerabilities/authbypass/
2. Right-click a request > Open/Resend with Request Editor
3. Modify user_id or other parameters
4. Compare responses between admin and non-admin sessions
5. Check Sites tab for discovered endpoints
```
