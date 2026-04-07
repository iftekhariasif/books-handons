# Chapter 5.4 - Log Output

> From "Safe Web Application Development" by Tokumaru Hiroshi
> Chapter 5: Representative Security Features

---

## Overview

Application-generated logs are critical from a security perspective. This section covers the principles and practices of logging in web applications.

---

## 5.4.1 Purpose of Log Output

Application logs are important for security for three main reasons:

1. **Detect attacks and incidents early** -- identify and respond to threats promptly
2. **Investigate incidents after they occur** -- gather evidence for forensics
3. **Monitor application operation** -- ensure the system runs as expected

### Example: Using Logs to Investigate Attacks

As discussed in section 5.1 (Authentication), login attempt counts and login failure rates can be monitored through logs. If login failures spike, this may indicate:
- A brute force attack from outside
- A password list attack

Conversely, if an attack is confirmed but logs are insufficient (e.g., no user ID recorded, no IP address, no timestamp), investigation becomes impossible.

---

## 5.4.2 Types of Logs

Web application-related logs include:

| Log Type | Source |
|----------|--------|
| **Web server logs** | Apache, nginx, IIS, etc. |
| **Application logs** | Custom application code |
| **Database logs** | Database server audit logs |

All are important, but this section focuses on **application logs** since they are under the developer's direct control.

### Application Log Categories

| Category | Description |
|----------|-------------|
| **Error log** | Records application errors and exceptions |
| **Access log** | Records information retrieval and functional usage |
| **Debug log** | Records detailed diagnostic information for development |

---

### Error Log

Error logs record various application errors. In web applications, if an error occurs, the user-facing message should be generic (e.g., "Please try again later"), while the detailed error information goes to the log.

**Purpose:**
- Help identify errors that users report
- Detect security incidents (SQL injection attempts, directory traversal attempts, SQL errors, etc.)
- Track errors that happen silently without user reports

**Security relevance:**
- SQL injection attacks often trigger SQL errors
- SQLi error-based attacks rely on SQL errors being visible
- These errors may occur silently (user doesn't see them) but indicate an attack
- Monitoring error logs for unusual spikes can help detect ongoing attacks
- Recommended: Review error logs regularly and fix root causes proactively

### Access Log

Access logs record the application's information retrieval and functional usage. Unlike error logs, these record **normal operations**.

**Key distinction from web server access logs:**
- Web server access logs started in the early web era (around 2004) primarily for marketing/analytics
- Application access logs focus on **security-critical operations**

**What to log (refer to section 5.4.3 for details):**
- Authentication events (login/logout)
- Account management operations
- Financial transactions
- Access to sensitive data

**Guidelines:**
- Follow the access log requirements from relevant security guidelines
- See the "Reference: Guidelines Requiring Access Logs" section below

### Debug Log

Debug logs provide detailed diagnostic information. While useful during development, they pose risks in production:

- **Excessive volume**: Can fill storage and affect performance
- **Sensitive data exposure**: May contain personal information, credit card numbers, or credentials
- Debug logs should **not** be output in production environments

---

## 5.4.3 Log Output Requirements

### Events to Record

Decide which events to log based on the application's purpose. Generally, log events related to:

- **Login and logout** (including failures)
- **Account lockout**
- **User registration and deletion**
- **Password changes**
- **Viewing sensitive information**
- **Critical operations** (purchases, transfers, mail sending)

### Log Output Fields

Each log entry should follow the **4W1H** principle: Who, When, Where, What, How (result).

| Field | Description |
|-------|-------------|
| **Access timestamp** | When the event occurred |
| **Remote IP address** | Source IP of the request |
| **User ID** | Who performed the action |
| **Access target** | URL, page number, script ID, etc. |
| **Operation details** | View, modify, delete, etc. |
| **Operation target** | Resource ID, record ID |
| **Operation result** | Success, failure, error count, etc. |

### Log Protection

Logs must be protected from tampering and unauthorized access:

- Logs that are altered or deleted cannot be used for investigation
- Logs should be **write-only** from the application's perspective (append-only)
- Protect logs with proper file permissions
- Consider separate log storage (different server or partition from the web/DB server)
- Keep logs in a location that is not web-accessible

### Log Retention Period

- Determine retention period based on the application's security requirements and legal obligations
- Short-term retention may miss long-running attacks or delayed incident discovery
- If log files contain personal information, retention also falls under privacy regulations
- **Balance:** Longer retention provides better forensic capability but increases exposure risk if logs are compromised
- Periodically archive and rotate logs; store archives on secure, separate media

### Log Output Destination

Logs should be stored in appropriate locations:

| Destination | Description |
|-------------|-------------|
| **File** | Most common; simple to implement |
| **Database** | Structured storage; easier to query |
| **syslog** | System-level logging facility (Unix/Linux) |
| **Windows Event Log** | NTEVENT on Windows systems |

### Log Format and Consistency

When dealing with multiple servers (web, application, database, mail), ensure:
- **Consistent log format** across all sources for easier correlation
- **Unified log analysis** is possible when formats match

### Log Levels

Standard log levels (from most to least severe):

| Level | Description |
|-------|-------------|
| **fatal** | Unrecoverable error |
| **error** | Error condition |
| **warn** | Warning |
| **info** | Informational |
| **debug** | Debug-level detail |
| **trace** | Fine-grained trace (method entry/exit) |

**Recommendation:**
- Production: Set log level to `info` (captures important events without excessive volume)
- Development/debugging: Use `debug` level
- Configure levels via settings to avoid code changes

### Server Time Synchronization

Logs from multiple servers must use synchronized clocks:
- Use **NTP (Network Time Protocol)** to synchronize all servers
- Consistent timestamps are essential for correlating events across web servers, application servers, database servers, and mail servers

---

## 5.4.4 Log Output Implementation

### Using Logging Frameworks

Use established logging libraries rather than custom implementations:

**Benefits of logging frameworks (e.g., log4j, logphp, log4net):**
- Log destination is abstracted (change via configuration, not code)
- Multiple output destinations can be configured simultaneously
- Log format is customizable via layout/pattern configuration
- Log levels are configurable without code changes
- Source code changes are minimal when adjusting logging behavior

**Examples:**
- Java: `log4j` (also integrated into many Java frameworks)
- PHP: `logphp` (similar API to log4j)
- .NET: `log4net`

---

## 5.4.5 Summary

**Logging requirements:**
- From a security perspective, logs serve for attack detection, post-incident investigation, and operational monitoring
- Useful logs require the **4W1H fields** (who, when, where, what, result)
- Protect log integrity and ensure safe storage
- Synchronize server clocks via NTP

---

## Reference: Guidelines Requiring Access Logs

Several regulations and security standards require access log collection:

### 1. Personal Information Protection Law (Japan)

Japan's Act on the Protection of Personal Information (amended 2017, unified guidelines from the Personal Information Protection Commission) requires organizations handling personal data to maintain "safe management measures," which include logging access to personal data.

**Article 20 (Safety Management Measures):**
> "A personal information handling business operator shall take necessary and appropriate measures for the security control of personal data including prevention against leakage, loss, or damage."

### 2. Financial Product Trading Law Internal Controls Report

Japan's Financial Instruments and Exchange Act (J-SOX, effective 2007) requires:
- Listed companies to evaluate and report on internal controls for financial reporting
- IT internal controls include access logging

**Key requirements for IT controls:**
- Unauthorized access detection and monitoring
- Periodic log review for anomalies
- System safety and reliability verification

### 3. PCI DSS (Payment Card Industry Data Security Standard)

PCI DSS is a security standard for organizations handling credit card data. It has 12 requirements, including:

**Requirement 10:** Track and monitor all access to network resources and cardholder data.

| Risk Example | Logging Requirement | Remediation Action |
|-------------|--------------------|--------------------|
| Unauthorized access to personal information | Log authentication events (login/logout), access timestamps, user IDs | Review logs for anomalies; respond to incidents |
| System compromise via external intrusion | Log all data access, system changes | Use logs to detect intrusions; perform forensic analysis |
| Data corruption or tampering | Log all modifications with before/after values | Verify data integrity; use logs to identify responsible parties |

### Security Incident Investigation and Log Utilization

The table above from the book (Table 5-6) shows how logs support security incident investigations:

| Risk | Log Purpose | Remediation |
|------|-------------|-------------|
| Information leakage | Identify who accessed what and when | Logs enable forensic tracing of the breach |
| System intrusion | Detect unauthorized access patterns | Logs reveal attack vectors and compromised accounts |
| Data tampering | Audit trail of modifications | Logs help establish timeline and scope of damage |

---

## Key Takeaways

1. **Log the right events**: Focus on authentication, authorization, critical operations, and errors
2. **Include 4W1H fields**: Timestamp, IP, user ID, target, operation, and result in every log entry
3. **Protect log integrity**: Append-only access, separate storage, proper permissions
4. **Synchronize clocks**: Use NTP across all servers for consistent timestamps
5. **Use logging frameworks**: log4j, logphp, etc. -- avoid custom implementations
6. **Set appropriate log levels**: `info` in production, `debug` in development
7. **Never log sensitive data**: Passwords, credit card numbers, and personal info should not appear in logs
8. **Retain logs appropriately**: Balance forensic needs with privacy regulations
9. **Comply with regulations**: PCI DSS, personal information protection laws, and financial regulations all require access logging
