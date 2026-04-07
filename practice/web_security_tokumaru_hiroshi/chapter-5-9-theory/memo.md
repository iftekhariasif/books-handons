# Theory Chapters 5-9 --- Quick Reference

## Summary Table

| # | Section | Key Concept | Related Hands-On |
|---|---------|-------------|------------------|
| 5.2 | Account Management | Secure user lifecycle (registration, reset, deletion) | Brute Force (5.1) --- password policy enforcement |
| 5.4 | Log Output | Security logging, compliance, audit trails | All exercises --- observe ZAP/server logs |
| 6 | Character Encoding and Security | Encoding attacks, UTF-8 exploits, charset mismatches | XSS (4.3) --- encoding-based filter bypass |
| 8 | Web Site Security | Server hardening, TLS, impersonation prevention | Nmap scanning (7), ZAP MITM demo |
| 9 | Development Management | Security in SDLC, testing methods, reporting | Vulnerability scanning and reporting (7) |

---

## Recommended Study Order

1. **Chapter 5.2 - Account Management** --- Start here because it directly extends the authentication concepts from the Brute Force hands-on (5.1). The transition from "how to break authentication" to "how to build secure account systems" is natural.

2. **Chapter 5.4 - Log Output** --- Study next because logging is the defensive counterpart to every attack you have practiced. Understanding what gets logged helps you think from the defender's perspective.

3. **Chapter 6 - Character Encoding and Security** --- This is the most technically dense topic. Study it after completing the XSS hands-on exercises so you can connect encoding theory to real bypass techniques you have already seen.

4. **Chapter 8 - Web Site Security** --- Move to infrastructure security after completing the Nmap scanning exercises (chapter 7). The scanning results will make server hardening concepts concrete.

5. **Chapter 9 - Development Management** --- Study last as a capstone. It ties everything together into a process-oriented view of security, which is most valuable after you have hands-on experience with individual vulnerability types.

---

## Key Terms Glossary

| Term | Definition |
|------|-----------|
| **Account Lockout** | Temporarily disabling an account after a set number of failed login attempts to prevent brute force attacks. |
| **Password Reset Token** | A one-time, time-limited credential sent to a verified channel (email/SMS) to allow password recovery without knowing the current password. |
| **Audit Trail** | A chronological record of system activities that enables reconstruction of events for security investigation or compliance review. |
| **PCI DSS** | Payment Card Industry Data Security Standard --- a set of requirements for organizations handling credit card data, including logging and access control mandates. |
| **J-SOX** | Japan's Financial Instruments and Exchange Act (equivalent to US Sarbanes-Oxley) --- requires internal controls and audit logging for publicly listed companies. |
| **Character Set** | A defined collection of characters (e.g., ASCII, Unicode/UCS, JIS X 0208) that maps characters to numeric code points. |
| **Character Encoding** | A scheme that maps code points to byte sequences for storage and transmission (e.g., UTF-8, Shift_JIS, EUC-JP). |
| **Non-Shortest Form** | An invalid UTF-8 encoding that uses more bytes than necessary to represent a character, historically exploited to bypass security filters. |
| **Charset Misdetection** | When a browser interprets content using a different encoding than the server intended, potentially enabling XSS via crafted byte sequences. |
| **Server Hardening** | Reducing the attack surface of a server by disabling unnecessary services, applying patches, and enforcing secure defaults. |
| **TLS (Transport Layer Security)** | Cryptographic protocol providing encrypted communication between client and server, preventing eavesdropping and tampering. |
| **HSTS** | HTTP Strict Transport Security --- a response header that forces browsers to use HTTPS for all future connections to the domain. |
| **Cipher Suite** | The combination of key exchange, authentication, encryption, and MAC algorithms negotiated during a TLS handshake. |
| **Threat Modeling** | A structured process during the design phase to identify potential threats, attack vectors, and prioritize mitigations before code is written. |
| **Static Analysis (SAST)** | Automated examination of source code without executing it, used to find vulnerabilities like injection flaws and hardcoded credentials. |
| **Dynamic Analysis (DAST)** | Testing a running application by sending crafted requests and analyzing responses to discover runtime vulnerabilities. |
| **Penetration Testing** | Authorized simulated attack against a system to evaluate its security posture and identify exploitable vulnerabilities. |
| **SDLC** | Software Development Life Cycle --- the structured process of planning, building, testing, and deploying software; security should be integrated into every phase. |
| **Defense in Depth** | Security strategy employing multiple layers of controls (application, infrastructure, process) so that failure of one layer does not compromise the system. |

---

## Cross-Reference: Theory to Hands-On Exercises

| Theory Concept | Applies To | How It Connects |
|---------------|------------|-----------------|
| Account lockout policy (5.2) | Brute Force (5.1) | Lockout is the primary defense against the brute force attacks you practice |
| Password reset security (5.2) | Brute Force (5.1) | Weak reset flows are an alternative to brute forcing the password itself |
| Security logging (5.4) | All exercises | Every attack leaves log traces; proper logging enables detection |
| Log integrity (5.4) | Command Injection (4.11) | Attackers with command execution can tamper with logs to cover tracks |
| Encoding-based XSS (6) | XSS (4.3), DOM XSS (4.17) | Encoding tricks bypass input filters that only match ASCII patterns |
| Charset misdetection (6) | XSS (4.3) | Forcing wrong charset interpretation can create XSS where none existed |
| Multi-byte character issues (6) | SQL Injection (4.4) | Shift_JIS trailing bytes can consume escape characters in SQL queries |
| Server hardening (8) | Nmap Scanning (7) | Scanning reveals the open ports and services that hardening would close |
| TLS configuration (8) | All exercises | ZAP MITM demo shows what happens without proper TLS; HSTS prevents downgrade |
| Impersonation prevention (8) | CSRF (4.5) | Phishing and impersonation are social-engineering counterparts to CSRF |
| Threat modeling (9) | All exercises | Each vulnerability type you practice should be anticipated during design |
| Security testing in SDLC (9) | Vulnerability Scanning (7) | Scanning exercises mirror the DAST phase of a real SDLC security process |
| Vulnerability reporting (9) | Vulnerability Scanning (7) | Reporting scan findings is a core SDLC deliverable |
| Defense in depth (9) | All exercises | No single defense is sufficient; combine application, infra, and process controls |

---

## The Book's Core Message

Security must be addressed at every layer:

- **Application layer** (Chapters 4-5) --- Secure coding practices, input validation, output encoding, authentication, session management, and account lifecycle.
- **Infrastructure layer** (Chapters 7-8) --- Server hardening, network security, TLS configuration, patch management, and platform-level vulnerability scanning.
- **Process layer** (Chapter 9) --- Security integrated into every SDLC phase, from threat modeling in design to penetration testing before deployment.

No single layer is sufficient on its own. A perfectly hardened server still falls to SQL injection in the application. Flawless application code is undermined by an unpatched OS. And both are meaningless without a development process that catches regressions and enforces standards over time.

The hands-on exercises teach you to exploit individual weaknesses. The theory chapters teach you to build systems where those weaknesses never reach production.
