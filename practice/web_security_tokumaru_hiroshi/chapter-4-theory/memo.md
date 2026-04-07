# Theory Chapters -- Quick Reference

---

## Summary Table

| # | Chapter Title | Key Concept | Severity | Related Hands-On |
|---|--------------|-------------|----------|-----------------|
| 4.1 | Web App Functions and Vulnerabilities | Input -> Processing -> Output vulnerability mapping | Foundation | All exercises |
| 4.2 | Input Processing and Security | Character encoding validation, input sanitization | Foundation | All injection exercises |
| 4.6 | Session Management Flaws | Session hijacking, fixation, prediction | High | XSS (4.3) cookie theft |
| 4.7 | Redirect and HTTP Header Injection | Open redirect, response splitting | Medium-High | ZAP observation, all exercises |
| 4.8 | Cookie Output Vulnerabilities | Missing HttpOnly/Secure flags, cookie injection | Medium-High | XSS (4.3) exercises |
| 4.9 | Email Sending Issues | Mail header injection, spam relay | Medium | Command Injection (4.11) |
| 4.13 | File Include Attacks | RFI/LFI, PHP include vulnerabilities | High | File Inclusion (4.10) |
| 4.14 | Eval Injection, Deserialization, XXE | eval(), unserialize(), XML parsing attacks | High | Command Injection (4.11) |
| 4.15 | Race Conditions and Cache Leaks | TOCTOU, shared resource conflicts, cache poisoning | Medium | Real-world awareness |
| 4.16 | Web API Security | CORS misconfiguration, JSON hijacking, JSONP | Medium-High | CSRF (4.5), ZAP observation |

---

## Cross-Reference: Theory Concepts in Hands-On Exercises

| Hands-On Exercise | Theory Chapters That Apply | Key Concepts From Theory |
|-------------------|---------------------------|--------------------------|
| **4.3 - XSS** | 4.1, 4.2, 4.6, 4.8 | Output escaping (4.1), input validation (4.2), session hijacking via cookie theft (4.6), HttpOnly flag importance (4.8) |
| **4.4 - SQL Injection** | 4.1, 4.2 | SQL as output interface (4.1), input validation as safety net (4.2) |
| **4.5 - CSRF** | 4.1, 4.6, 4.16 | State-changing operations (4.1), session management (4.6), API CSRF (4.16) |
| **4.10 - File Inclusion** | 4.1, 4.2, 4.13 | File access as output (4.1), path validation (4.2), RFI/LFI theory (4.13) |
| **4.11 - Command Injection** | 4.1, 4.2, 4.9, 4.14 | OS command as output (4.1), metacharacter filtering (4.2), header injection parallels (4.9), eval injection parallels (4.14) |
| **4.12 - File Upload** | 4.1, 4.2, 4.15 | File handling (4.1), extension/type validation (4.2), race conditions in upload processing (4.15) |
| **4.17 - DOM-based XSS** | 4.1, 4.2, 4.16 | Client-side output (4.1), JavaScript input handling (4.2), API and CORS context (4.16) |

---

## Study Order Recommendation

Read these theory chapters in the following order, based on dependencies:

1. **4.1 - Web App Functions and Vulnerabilities** -- Read first. Establishes the Input -> Processing -> Output model that every other chapter builds on.
2. **4.2 - Input Processing and Security** -- Read second. Covers input validation principles that apply to all vulnerability types.
3. **4.6 - Session Management Flaws** -- Read before doing XSS exercises. Understanding sessions makes cookie theft attacks meaningful.
4. **4.8 - Cookie Output Vulnerabilities** -- Read alongside 4.6. Complements session management with cookie security attributes.
5. **4.7 - Redirect and HTTP Header Injection** -- Read before working with ZAP. Helps you understand HTTP headers you will observe.
6. **4.9 - Email Sending Issues** -- Read before Command Injection exercises. Introduces metacharacter injection in a different context.
7. **4.13 - File Include Attacks** -- Read before or alongside File Inclusion exercises. Provides the theory behind the DVWA module.
8. **4.14 - Eval Injection, Deserialization, XXE** -- Read after completing basic injection exercises. These are advanced injection variants.
9. **4.16 - Web API Security** -- Read after understanding XSS and CSRF. Extends those concepts to modern API contexts.
10. **4.15 - Race Conditions and Cache Leaks** -- Read last. These are advanced topics that require understanding of all previous concepts.

---

## Key Terms Glossary

| Term | Definition |
|------|-----------|
| **Input Validation** | Checking and sanitizing user-supplied data before it enters application logic, including type, length, format, and character encoding checks. |
| **Output Escaping** | Converting special characters to safe representations when data is sent to an external interface (HTML, SQL, OS command, etc.). |
| **Session Hijacking** | An attacker obtaining a legitimate user's session ID to impersonate them and gain unauthorized access. |
| **Session Fixation** | An attack where the attacker forces the victim to use a session ID chosen by the attacker, then hijacks the session after authentication. |
| **Open Redirect** | A vulnerability where a web application redirects users to an attacker-controlled URL via a manipulable parameter, enabling phishing. |
| **HTTP Header Injection** | Injecting newline characters (CR+LF) into HTTP headers to add arbitrary headers or split the response. |
| **Response Splitting** | A consequence of HTTP header injection where the attacker creates a second, fully controlled HTTP response within a single connection. |
| **Cookie HttpOnly Flag** | A cookie attribute that prevents client-side JavaScript from accessing the cookie, mitigating XSS-based session theft. |
| **Cookie Secure Flag** | A cookie attribute that ensures the cookie is only sent over HTTPS connections, preventing network-level interception. |
| **Mail Header Injection** | Injecting newline characters into email headers to add recipients, change subjects, or modify email body content. |
| **Remote File Inclusion (RFI)** | An attack where an attacker causes a server to include and execute a script from an external URL via vulnerable include functions. |
| **Local File Inclusion (LFI)** | An attack exploiting include functions to read or execute local server files by manipulating the file path parameter. |
| **eval() Injection** | Exploiting dynamic code evaluation functions (eval, Function constructor) by injecting malicious code through untrusted input. |
| **Unsafe Deserialization** | Exploiting object deserialization functions (unserialize, pickle.loads) to execute arbitrary code via crafted serialized data. |
| **XML External Entity (XXE)** | An attack exploiting XML parsers that process external entity references, enabling file disclosure, SSRF, or denial of service. |
| **TOCTOU** | Time-of-Check-to-Time-of-Use: a race condition where the state checked by the program changes before the result of that check is used. |
| **CORS** | Cross-Origin Resource Sharing: a mechanism using HTTP headers to control which origins can access resources, commonly misconfigured in APIs. |
| **JSON Hijacking** | An attack that steals JSON data by exploiting browser behavior that allows cross-origin script execution of JSON responses. |
| **JSONP** | JSON with Padding: a technique for cross-origin data access that wraps JSON in a callback function, creating XSS risks if not properly validated. |
| **Defense in Depth** | A security strategy that layers multiple independent countermeasures so that if one layer fails, others still provide protection. |

---

## Core Security Principle: Defense in Depth

The book emphasizes **Defense in Depth** as the overarching security strategy. No single countermeasure is sufficient on its own -- input validation can be bypassed, output escaping can be forgotten, and WAFs can be evaded. By layering multiple independent defenses (input validation, output escaping, parameterized queries, security headers, proper session configuration, least privilege), each layer catches what the previous one missed. The goal is to ensure that a single mistake or oversight does not lead to a complete compromise of the application.
