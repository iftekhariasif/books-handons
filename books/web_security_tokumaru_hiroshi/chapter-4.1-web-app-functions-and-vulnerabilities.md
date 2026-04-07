# Chapter 4.1 - Web Application Functions and Vulnerabilities

> From "Safe Web Application Development" by Tokumaru Hiroshi
> Chapter 4: Security Bugs by Web Application Function

---

## Chapter 4 Overview

This chapter covers vulnerabilities in web applications by function, including:
- How they originate and the conditions that cause them
- Countermeasures and detailed explanations

**Section breakdown:**
- **4.1** - Overview of the relationship between web app functions and vulnerabilities
- **4.2** - Relationship between "input" and vulnerabilities
- **4.3 onwards** - Vulnerabilities commonly arising from each functional area (XSS, SQL Injection, and other high-impact vulnerabilities)

---

## Where Do Vulnerabilities Originate?

- A web application can be modeled as a classic **Input -> Processing -> Output** flow
- An HTTP request triggers input, various processing is performed, and an HTTP response is output
- Processing involves HTML output, database access, external command execution, mail delivery, etc.
- The "output" in this context refers to **interfaces with external systems**

### Figure 4-1: Mapping of Web App Functions to Vulnerabilities

| Input | Processing | Output | Vulnerability |
|-------|-----------|--------|---------------|
| Form/HTML | Browser | HTML output | Cross-Site Scripting (XSS), HTTP Header Injection |
| DB/SQL | RDB | SQL statement execution | SQL Injection |
| External commands | Shell | Shell command invocation | OS Command Injection |
| Mail | Mail server | Mail header & body output | Mail Header Injection |
| File | File system | Directory traversal | Directory Traversal |
| Cookie | Session management | - | Session management issues |
| Cross-site request | - | - | Cross-Site Request Forgery (CSRF) |
| - | Authentication | - | Authentication bypass |

---

## Output-Side Vulnerabilities

The following vulnerabilities are tied to specific **output interfaces**:

- **HTML output** (Cross-Site Scripting)
- **HTTP header output** (HTTP Header Injection)
- **SQL statement execution** (SQL Injection)
- **Shell command invocation** (OS Command Injection)
- **Mail header and body output** (Mail Header Injection)

---

## Vulnerability Classification

| Category | Description |
|----------|-------------|
| **Processing-related** | Vulnerabilities that arise in both processing and output |
| **Input-related** | Vulnerabilities triggered by input |
| **Neither input nor output** | Simply called "vulnerabilities" -- many fall into this simple category |

- XSS is sometimes referred to as "HTML Injection" or "JavaScript Injection"
- Vulnerabilities in Figure 4-1 that arise from output are **all injection-type vulnerabilities**
- The book organizes them by **web application function category** rather than treating them as a flat list

---

## What Are Injection-Type Vulnerabilities?

Web applications use **text-based interfaces** for output: HTML, HTTP, SQL, etc. These text-based interfaces follow defined syntax rules and contain a mix of:

- **Commands / Markup** (structural elements)
- **Data** (user-supplied or dynamic content)

Data is typically separated by:
- **Quotation marks** (single quotes `'`, double quotes `"`)
- **Delimiters** (specific characters like `;`, `,`, line breaks)

### Root Cause of Injection

**Injection vulnerabilities occur when an attacker manipulates data to alter the structure of the text itself** -- when data "breaks out" of its delimited context and gets interpreted as commands or structure.

### SQL Injection Example

Given: `SELECT * FROM users WHERE id='$id'`

Attack payload for `$id`: `';DELETE FROM users --`

Resulting SQL:
```sql
SELECT * FROM users WHERE id='';DELETE FROM users --'
```

- The single quote `'` closes the data section prematurely
- The semicolon `;` terminates the SELECT statement
- `DELETE FROM users` is injected as a new command
- `--` comments out the trailing quote

---

## Comparison Table: Injection-Type Vulnerabilities

| Vulnerability | Interface | Typical Entry Point | Data Delimiter |
|--------------|-----------|---------------------|----------------|
| **Cross-Site Scripting (XSS)** | HTML | `javascript:` or similar insertion | `<`, `"`, etc. |
| **HTTP Header Injection** | HTTP | HTTP response header construction | Line breaks |
| **SQL Injection** | SQL | SQL value insertion | `'`, etc. |
| **OS Command Injection** | Shell script | Command insertion | `;`, `|`, etc. |
| **Mail Header Injection** | sendmail command | Mail header / body modification | Line breaks |

---

## Key Takeaways

1. **Vulnerabilities map to output functions**: Each output interface (HTML, SQL, shell, mail, HTTP headers) has its own characteristic injection vulnerability.
2. **Injection is the shared root cause**: All output-side vulnerabilities share the same mechanism -- data escaping its intended context and being interpreted as structure/commands.
3. **Metacharacters are the attack vector**: Attackers exploit delimiter characters (`'`, `"`, `;`, line breaks, `<`, `>`) to break out of data context.
4. **Defense follows function**: Countermeasures must be applied at each output point with interface-appropriate escaping/encoding.
5. **The book organizes by function, not vulnerability type**: Subsequent sections (4.2+) break down vulnerabilities by the web application function that produces them, making it practical to audit real applications.
