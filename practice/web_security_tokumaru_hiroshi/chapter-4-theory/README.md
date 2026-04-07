# Chapter 4: Theory-Only Topics -- Reference Guide

These chapters cover important security concepts that don't have direct DVWA practice modules. They provide foundational knowledge that supports the hands-on exercises in other folders.

---

## 4.1 - Web Application Functions and Vulnerabilities

Maps web application functions (Input -> Processing -> Output) to specific vulnerability types. This chapter establishes the mental model for how data flows through a web application and where each class of vulnerability originates. It is the foundation for understanding ALL other chapters in this book.

- **Full cheatsheet:** [Chapter 4.1 - Web App Functions and Vulnerabilities](../../books/web_security_tokumaru_hiroshi/chapter-4.1-web-app-functions-and-vulnerabilities.md)
- **Related hands-on:** All exercises use these concepts. Every vulnerability explored in the practice folders maps back to the Input -> Processing -> Output model described here.

---

## 4.2 - Input Processing and Security

Covers character encoding validation, input value checking, and regex patterns for proper input sanitization. This chapter explains how to validate and sanitize user input before it reaches application logic, serving as a critical first line of defense against injection attacks.

- **Full cheatsheet:** [Chapter 4.2 - Input Processing and Security](../../books/web_security_tokumaru_hiroshi/chapter-4.2-input-processing-and-security.md)
- **Related hands-on:** Every exercise demonstrates what happens when input is not properly validated -- XSS (4.3), SQL Injection (4.4), Command Injection (4.11), and all other injection-type vulnerabilities.

---

## 4.6 - Session Management Flaws

Covers session ID prediction, session fixation, and session hijacking techniques. This chapter explains how sessions work under the hood, the three methods attackers use to obtain session IDs (predicting, stealing, and forcing), and how to defend against each attack vector.

- **Full cheatsheet:** [Chapter 4.6 - Session Management Flaws](../../books/web_security_tokumaru_hiroshi/chapter-4.6-session-management-flaws.md)
- **Related hands-on:** XSS cookie theft in the [chapter-4.3-xss](../chapter-4.3-xss/) exercises demonstrates session hijacking by stealing session IDs via injected JavaScript.

---

## 4.7 - Redirect and HTTP Header Injection

Covers open redirect vulnerabilities, HTTP header injection, and HTTP response splitting. This chapter explains how URL redirects specified via external parameters can be abused for phishing, and how injecting newline characters into HTTP headers enables response manipulation.

- **Full cheatsheet:** [Chapter 4.7 - Redirect and HTTP Header Injection](../../books/web_security_tokumaru_hiroshi/chapter-4.7-redirect-and-header-injection.md)
- **Related hands-on:** Observe redirect behavior and HTTP headers in ZAP while working through any exercise. Header injection concepts also connect to cookie manipulation in 4.8.

---

## 4.8 - Cookie Output Vulnerabilities

Covers cookie injection, improper cookie usage (storing sensitive data in cookies), and insecure cookie attributes such as missing HttpOnly and Secure flags. This chapter explains why cookies should only store session IDs and how missing security attributes expose applications to attack.

- **Full cheatsheet:** [Chapter 4.8 - Cookie Output Vulnerabilities](../../books/web_security_tokumaru_hiroshi/chapter-4.8-cookie-output-vulnerabilities.md)
- **Related hands-on:** XSS exercises in [chapter-4.3-xss](../chapter-4.3-xss/) show why the HttpOnly flag matters -- without it, `document.cookie` can be read by injected scripts.

---

## 4.9 - Email Sending Issues

Covers mail header injection and third-party relay abuse through web forms. This chapter explains how injecting newline characters into email header fields (To, Subject, etc.) allows attackers to modify recipients, subjects, and email bodies -- enabling spam and phishing through legitimate web applications.

- **Full cheatsheet:** [Chapter 4.9 - Email Sending Issues](../../books/web_security_tokumaru_hiroshi/chapter-4.9-email-sending-issues.md)
- **Related hands-on:** Command Injection exercises in [chapter-4.11-command-injection](../chapter-4.11-command-injection/) use similar metacharacter injection concepts -- both exploit special characters that change how the server interprets input.

---

## 4.13 - File Include Attacks

Covers PHP `include`/`require` vulnerabilities and Remote File Inclusion (RFI). This chapter explains how attackers can manipulate file paths passed to include functions to execute arbitrary scripts or disclose server files, especially when PHP's `allow_url_include` is enabled.

- **Full cheatsheet:** [Chapter 4.13 - File Include Attacks](../../books/web_security_tokumaru_hiroshi/chapter-4.13-file-include-attacks.md)
- **Related hands-on:** File Inclusion exercises in [chapter-4.10-file-inclusion](../chapter-4.10-file-inclusion/) cover the same concepts with DVWA's File Inclusion module, including both local and remote file inclusion scenarios.

---

## 4.14 - Eval Injection, Deserialization, and XXE

Covers `eval()` injection, PHP object injection via `unserialize()`, and XML External Entity (XXE) attacks. These are advanced injection techniques that go beyond SQL and OS command injection -- they target code execution through dynamic evaluation, object deserialization, and XML parsing respectively.

- **Full cheatsheet:** [Chapter 4.14 - Eval Injection, Deserialization, and XXE](../../books/web_security_tokumaru_hiroshi/chapter-4.14-eval-injection-deserialization-xxe.md)
- **Related hands-on:** Command Injection exercises in [chapter-4.11-command-injection](../chapter-4.11-command-injection/) demonstrate similar code execution concepts. The principle of "never pass untrusted input to execution functions" applies to all of these vulnerabilities.

---

## 4.15 - Race Conditions and Cache Leaks

Covers TOCTOU (Time-of-Check-to-Time-of-Use) bugs, shared resource conflicts causing data corruption, and proxy cache poisoning leading to information leaks. These timing-based vulnerabilities arise when multiple processes or requests access shared state concurrently without proper locking.

- **Full cheatsheet:** [Chapter 4.15 - Race Conditions and Cache Leaks](../../books/web_security_tokumaru_hiroshi/chapter-4.15-race-conditions-and-cache-leaks.md)
- **Related hands-on:** These are timing-based issues to be aware of in real applications. While not directly exercised in DVWA, understanding them is critical for production security -- especially database inconsistency and cache-based information leaks.

---

## 4.16 - Web API Security

Covers REST API vulnerabilities, CORS misconfigurations, JSON hijacking, JSONP security issues, and API-specific CSRF. This chapter explains how modern APIs introduce new attack surfaces through data formats (JSON, XML) and cross-origin mechanisms that differ from traditional HTML-based web applications.

- **Full cheatsheet:** [Chapter 4.16 - Web API Security](../../books/web_security_tokumaru_hiroshi/chapter-4.16-web-api-security.md)
- **Related hands-on:** Observe API calls and CORS headers in ZAP while working through exercises. CSRF concepts from [chapter-4.5-csrf](../chapter-4.5-csrf/) also apply to API endpoints.
