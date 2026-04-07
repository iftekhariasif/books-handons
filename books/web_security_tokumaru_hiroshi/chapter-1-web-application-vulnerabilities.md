# Chapter 1: What Are Web Application Vulnerabilities

> This chapter introduces the core theme of the book — **vulnerabilities (脆弱性)**. What they are, why they matter, why they occur, and how the book and security guidelines address them.

---

## 1.1 Vulnerability = "A Bug That Can Be Exploited"

A vulnerability is essentially a **bug that can be maliciously exploited** (also called a security bug).

### Examples of what attackers can do with vulnerabilities:
- Steal personal info and confidential data
- Rewrite website content
- Infect users' PCs with viruses
- Impersonate users (purchases, money transfers, etc.)
- Hijack computers for cryptocurrency mining
- Use paid services for free (e.g., online games)
- View other users' personal information

---

## 1.2 Why Vulnerabilities Are a Problem

### Economic Losses
- Compensation for financial damages
- Costs for shipping, delivery, related expenses
- **Website downtime** — lost revenue
- **Credit/trust loss** — declining sales
- **Damages claimed by business partners**

> Economic losses can range from **millions to tens of millions of yen** (tens of thousands to hundreds of thousands of USD). In some cases, even larger.

### Legal Requirements
- **Personal Information Protection Act** (個人情報保護法) requires businesses handling personal data to implement **Safety Management Measures** (安全管理措置).
- Article 20: Businesses must take necessary and appropriate measures to prevent leakage, loss, or damage of personal data.
- Related guidelines from the Personal Information Protection Commission outline technical and organizational requirements.

### Often Irreversible Damage
- Leaked personal info **cannot be un-leaked**
- Credit card exposure, reputational damage are hard to recover from
- Incidents may lead to lawsuits — settling doesn't fully resolve the issue

### Victims Beyond the Site Owner
- Vulnerable websites can turn users into victims
- Attackers can rewrite site content, plant malware, redirect users

### Attack Infrastructure — Botnets
- Vulnerable web apps can be **recruited into botnets**
- Botnets are networks of compromised machines used for:
  - Spam email
  - **DDoS attacks** (Distributed Denial of Service)
  - Other malicious activities
- Publishing a vulnerable website on the internet is a **social responsibility issue**

---

## 1.3 Why Vulnerabilities Occur

Vulnerabilities arise from **two root causes**:

### (A) Bugs
- Examples: **SQL Injection**, **Cross-Site Scripting (XSS)**
- These have enormous impact
- Development teams must learn **secure coding practices**
- Requires writing safe application code from the ground up

### (B) Lack of Security Checks / Missing Features
- Example: **Directory Traversal**
- Developers may not realize a security check is needed
- The awareness that "this needs a security review" is missing
- Can occur even in teams that otherwise write clean code

> Web application vulnerabilities are like **pitfalls and holes everywhere** — unless you learn where they are in advance, you'll keep falling into them.

---

## 1.4 Security Bugs vs. Security Features

Eliminating bugs alone is **not enough** to secure an application.

- Example: Using **HTTPS** to encrypt communication — this is a **security feature**, not just the absence of bugs.
- If HTTPS is not implemented, it's not a "bug" per se, but it's still a **vulnerability** (a narrow sense).

### Security Features (セキュリティ機能) include:
- **Authentication** (認証)
- **Account management** (アカウント管理)
- **Authorization / Access control** (認可)
- **Logging / Audit trails** (ログ)

> **Key distinction:** Security bugs need to be fixed by developers. Security features need to be **specified and decided** by the application owner/stakeholder. Both developers and stakeholders must understand this difference.

---

## 1.5 Book Structure Overview

| Chapter | Topic |
|---------|-------|
| 1 | Introduction to vulnerabilities (this chapter) |
| 2 | Practice environment setup (VirtualBox) |
| 3 | Web security basics: HTTP, cookies, session management, same-origin policy, CORS |
| 4 | **Core chapter** — Web application security patterns and countermeasures |
| 5 | Representative security features (authentication, authorization, etc.) |
| 6 | Character encoding and related vulnerabilities |
| 7 | Introduction to vulnerability assessment |
| 8 | Security beyond web applications (server, infrastructure) |
| 9 | Secure web application development management |

---

## 1.6 Security Guidelines Reference

### IPA "How to Make a Secure Website"

Published by Japan's **Information-Technology Promotion Agency (IPA)**, this guideline lists common vulnerability types.

| # | Vulnerability | Book Section |
|---|--------------|-------------|
| 1 | SQL Injection | 4.4.1 |
| 2 | OS Command Injection | 4.11.1 |
| 3 | Directory Traversal | 4.10.1 |
| 4 | Session Management Issues | 4.6 |
| 5 | Cross-Site Scripting (XSS) | 4.3.1, 4.3.2, 4.17.1 |
| 6 | CSRF (Cross-Site Request Forgery) | 4.5.1 |
| 7 | HTTP Header Injection | 4.7.2 |
| 8 | Mail Header Injection | 4.9.2 |
| 9 | Clickjacking | 4.5.2 |
| 10 | Buffer Overflow | 4.3 |
| 11 | Access Control / Authorization Issues | 5.3 |

### OWASP Top 10 (2017)

| Rank | OWASP Top 10 Category | Book Section |
|------|----------------------|-------------|
| A1 | Injection | 4.4.1, 4.11.1 |
| A2 | Broken Authentication | 5.1 |
| A3 | Sensitive Data Exposure | 4.10.2 |
| A4 | XML External Entities (XXE) | 4.14.3 |
| A5 | Broken Access Control | 5.3 |
| A6 | Security Misconfiguration | 8.1 |
| A7 | Cross-Site Scripting (XSS) | 4.3.1, 4.3.2, 4.17.1 |
| A8 | Insecure Deserialization | 4.14.2 |
| A9 | Components with Known Vulnerabilities | 8.1 |
| A10 | Insufficient Logging & Monitoring | 5.4 |

---

## Key Takeaways

1. **A vulnerability is a bug that can be exploited** — not all bugs are vulnerabilities, but all vulnerabilities stem from bugs or missing features.
2. **The impact is severe**: financial loss, legal liability, reputational damage, and harm to innocent users.
3. **Two root causes**: coding bugs (SQL injection, XSS) and missing security features (no HTTPS, no access control).
4. **Both developers and stakeholders** share responsibility — developers fix bugs, stakeholders must specify security requirements.
5. **Follow established guidelines**: IPA's Secure Website guide and OWASP Top 10 provide excellent checklists.
