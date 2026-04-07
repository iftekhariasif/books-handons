# Chapters 5-9: Theory Topics --- Reference Guide

These sections cover important security concepts that don't have direct DVWA practice modules but are essential knowledge for web security. Understanding these topics provides the broader context that makes hands-on vulnerability exercises meaningful --- security is not just about finding bugs, but about building secure systems end to end.

---

## 5.2 - Account Management

**Summary:** Covers the full user account lifecycle --- registration, password change and reset, email address change, account suspension, and account deletion. Each stage introduces unique security risks such as mass registration abuse, insecure password reset flows, and incomplete account deletion that leaves residual data. The chapter provides concrete countermeasures for each lifecycle operation.

**Full cheatsheet:** [Chapter 5.2 - Account Management](../../books/web_security_tokumaru_hiroshi/chapter-5.2-account-management.md)

**Connection to hands-on exercises:** The Brute Force module (chapter 5.1) demonstrates why strong password policies and account lockout mechanisms matter. When practicing brute force attacks, consider how proper account management (lockout after failed attempts, secure password reset tokens) would mitigate the attack. Weak account management is often the root cause that makes brute force viable.

---

## 5.4 - Log Output

**Summary:** Explains the purpose and practice of security logging, including what to log, where to store logs, and how to protect log integrity. Covers log types such as access logs, error logs, authentication logs, and application-level audit trails. Also addresses regulatory requirements including PCI DSS and J-SOX compliance standards for log retention and monitoring.

**Full cheatsheet:** [Chapter 5.4 - Log Output](../../books/web_security_tokumaru_hiroshi/chapter-5.4-log-output.md)

**Connection to hands-on exercises:** During every hands-on exercise, observe the ZAP proxy logs and any server-side logs available. In the vulnerability scanning exercises (chapter 7), log output is what allows defenders to detect and investigate attacks. Every exploit you practice --- XSS, SQLi, command injection --- should leave traces in properly configured logs.

---

## Chapter 6 - Character Encoding and Security

**Summary:** Covers character sets (ASCII, Unicode/UCS, JIS) and encoding schemes (UTF-8, Shift_JIS, EUC-JP) with a focus on how encoding mismatches create security vulnerabilities. Explains attacks that exploit encoding issues, including XSS via invalid encoding sequences, non-shortest form UTF-8 attacks that bypass input validation, and problems caused by charset misdetection between server and browser.

**Full cheatsheet:** [Chapter 6 - Character Encoding and Security](../../books/web_security_tokumaru_hiroshi/chapter-6-character-encoding-and-security.md)

**Connection to hands-on exercises:** The XSS exercises (chapter 4.3) are directly relevant --- encoding manipulation is a classic technique for bypassing XSS filters and WAF rules. When practicing XSS payloads, consider how alternative encodings or multi-byte character sequences could be used to slip past input validation that only checks for ASCII patterns.

---

## Chapter 8 - Web Site Security

**Summary:** Addresses infrastructure-level security that complements application-layer defenses. Topics include server hardening (disabling unnecessary services, patching, secure defaults), protection against impersonation and phishing, proper TLS/HTTPS configuration (certificate management, cipher suite selection, HSTS), and malware prevention strategies for both server and client sides.

**Full cheatsheet:** [Chapter 8 - Web Site Security](../../books/web_security_tokumaru_hiroshi/chapter-8-web-site-security.md)

**Connection to hands-on exercises:** The Nmap scanning exercises in chapter 7 directly demonstrate why server hardening matters --- open ports and exposed services are what scanners detect. The ZAP MITM (Man-in-the-Middle) demo shows why TLS configuration is critical. Application-level vulnerabilities become even more dangerous when the underlying infrastructure is poorly secured.

---

## Chapter 9 - Development Management

**Summary:** Integrates security into the software development lifecycle (SDLC) across all phases --- planning, requirements, design, implementation, testing, and deployment. Covers security-focused development standards, threat modeling during design, code review practices, and security testing methods including static analysis, dynamic analysis, and penetration testing. Also discusses agile security practices and how to write effective vulnerability reports.

**Full cheatsheet:** [Chapter 9 - Development Management](../../books/web_security_tokumaru_hiroshi/chapter-9-development-management.md)

**Connection to hands-on exercises:** The vulnerability scanning and reporting exercises in chapter 7 are a practical application of the testing phase described here. The skills practiced throughout all hands-on modules (identifying, exploiting, and documenting vulnerabilities) map directly to the security testing and reporting activities described in this chapter's SDLC framework.

---

## How These Theory Chapters Fit Together

These five sections form three layers of security knowledge:

1. **Application features** (5.2, 5.4) --- Secure account management and logging are features every application needs but that are rarely practiced in CTF-style labs.
2. **Foundational knowledge** (6) --- Character encoding is the kind of deep technical knowledge that separates surface-level understanding from real expertise.
3. **Operational and process security** (8, 9) --- Even perfect application code is vulnerable without proper infrastructure and development processes.

The book's core message is that security must be addressed at every layer. Hands-on exercises teach you to find and exploit vulnerabilities; these theory chapters teach you to prevent them systematically.
