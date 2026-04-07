# Chapter 4.9: Email Sending Issues

This section covers vulnerabilities in web application email sending functionality, including mail header injection and third-party relay abuse.

---

## 4.9.1 Overview of Email Sending Problems

Web applications send emails for confirmations, notifications, and other purposes. Problems include:

- **Mail Header Injection vulnerability**
- **Hidden parameter tampering** of recipient addresses
- **Mail server third-party relay** (reference topic)

### Mail Header Injection

Injecting newline characters into email header fields (To, Subject, etc.) to add new headers or modify the email body. This enables:

- Changing the subject or sender
- Sending spam/unsolicited emails
- Sending virus-laden emails

### Hidden Parameter Abuse

- Some forms store the recipient email address in a `hidden` field
- Attackers can modify this to send emails to arbitrary addresses
- The recipient address should be stored server-side (in files, databases, etc.), not in client-accessible parameters

```html
<input type="hidden" name="mailaddr" value="root@example.jp">
```

If the hidden parameter is changed, the attacker can redirect emails to any address.

### Mail Server Third-Party Relay (Reference)

- **MTA (Mail Transfer Agent)** can be configured to allow relaying from any sender to any recipient
- Such "open relay" servers can be abused for sending spam
- Modern MTAs default to rejecting third-party relay
- Use vulnerability scanners (Nessus, OpenVAS) to verify your mail server configuration
- Always verify third-party relay is disabled after setting up a mail server

#### Spam Email Flow

```
Spam sender  -->  Open Relay MTA  -->  Recipient (victim)
                       |
            Third-party relay server
            accepts mail from anyone
```

---

## 4.9.2 Mail Header Injection

### Overview

Mail header injection exploits the fact that email headers are separated by newline characters. By inserting newlines into fields received from external input, attackers can:

- Add new headers (Bcc, Cc, Reply-To, Subject)
- Modify the email body
- Attach files (via MIME manipulation)

### Summary of Mail Header Injection

| Aspect | Details |
|--------|---------|
| **Origin** | Email sending functionality |
| **Affected Pages** | No direct visible impact on pages; emails are sent/modified behind the scenes |
| **Impact Types** | Spam email sending, unauthorized recipient addition, body modification, virus file attachment |
| **Severity** | Medium |
| **User Involvement** | Low -- attacker can exploit directly |
| **Countermeasures** | Use dedicated email library; do not include external parameters in headers; validate for newline characters |

---

### Attack Method and Impact

#### Sample Form -- `49-001.html`

```html
<body>
Inquiry Form<br>
<form action="49-002.php" method="POST">
  From: <input type="text" name="from"><br>
  Body: <textarea name="body">
  </textarea>
  <input type="submit" value="Send">
</form>
</body>
```

#### Email Sending Script -- `49-002.php`

```php
<?php
$from = filter_input(INPUT_POST, 'from');
$body = filter_input(INPUT_POST, 'body');

mb_language('Japanese');
mb_send_mail('wasbook@example.jp', 'Inquiry received',  $body,
  "From: " . $from);
?>
<body>
Sent successfully
</body>
```

`mb_send_mail` is a multibyte-aware email function. Its arguments are: recipient, subject, body, and additional headers. The `From` header in the 4th argument is where the injection occurs.

---

### Attack 1: Adding Recipients

The attacker modifies the `from` field in the attack form to inject a `Bcc` header:

**Malicious form** (`49-900.html`, hosted on attacker's site):
```html
<form action="http://example.jp/49/49-002.php" method="POST">
  <textarea name="from" rows="4" cols="30">
  </textarea>
  <input type="submit" value="Send">
</form>
```

The attacker enters into the `from` field:
```
trap@trap.example.com
Bcc: bob@example.jp
```

The newline after the email address creates a new header line, adding `Bcc: bob@example.jp`. Result:

- The original admin (wasbook) receives the email
- **bob** also receives it via the injected Bcc header
- Bob thinks the email is legitimate ("a spam email came") and might just delete it
- The attacker can use this to send spam through the legitimate server

---

### Attack 2: Body Modification

By injecting an empty line (two consecutive newlines) after the From header, the attacker can override the email body:

The attacker enters in the `from` field (using MIME knowledge):
```
trap@trap.example.com
Bcc: bob@example.jp

Super discount PCs 80% OFF! http://trap.example.com/
```

The message after the blank line becomes the **new email body**. The original body intended by the application is pushed down and may be ignored by email clients.

**Result**: The recipient sees the attacker's spam message as the email content.

---

### Attack 3: Attaching Files via MIME Manipulation

- Using `MIME/multipart/mixed` format, attackers can attach files to emails
- This can be used to distribute **virus files** or malware
- The attacker manipulates the `from` field to inject MIME boundaries and file attachment headers
- Demo available at the book's practice site: menu item "Mail form (mail header injection to attach files)"

---

### Root Cause

Email messages follow a specific text format where:

- **Headers** come first (To, Subject, From, etc.), each on its own line
- **Body** follows after a blank line (empty line = two newlines)
- Headers and body are separated by `\r\n` (CRLF)

```
To: wasbook@example.jp
Subject: Inquiry received
From: alice@example.jp
Content-Type: text/plain; charset=ISO-2022-JP

Body text here...
```

Since headers are delimited by newlines, if external input containing newlines is placed in a header field, the attacker can:

- Insert new headers after the newline
- Add a blank line to start the body section prematurely

The `additional_headers` parameter in PHP's `mail()` and `mb_send_mail()` is the most common injection point, as it directly appends to the message headers.

---

### Countermeasures

#### 1. Use Dedicated Email Libraries/APIs

Instead of manually constructing email messages, use libraries that handle header encoding and injection prevention:

Benefits of using email libraries:

- `sendmail` command-based email is error-prone and can introduce OS command injection (see section 4.11)
- `sendmail` command calls are prone to additional command injection vulnerabilities
- **Mail header injection should be handled by dedicated libraries**

However, many email libraries have historically been vulnerable to mail header injection themselves. Always verify and keep libraries updated.

#### 2. Do Not Include External Parameters in Mail Headers

- Design applications so external (user-supplied) parameters never end up in email header fields
- This is the most reliable countermeasure
- For example, if users provide a "from" address, validate it strictly and only use it as a value, never concatenating it directly into headers
- If possible, fix all header values server-side

#### 3. Validate Parameters for Newline Characters

If external parameters must be used in email headers:

- **Check for newline characters** (`\r`, `\n`) before using the value
- Reject or strip any input containing newlines before including it in headers
- Use dedicated email sending functions that perform this check automatically

#### Email Address Validation

- Email address format is defined in RFC5322, but the RFC is complex
- Not all mail servers/clients fully support RFC5322
- For web applications, a practical validation approach:

```php
if (preg_match('/\A([!-~]+)\z/u', $subject) !== 1) {
  die('Subject contains invalid characters');
}
```

- Check that subjects only contain printable ASCII characters
- Reject any control characters including newlines
- For UTF-8 and non-ASCII content, ensure proper encoding via the email library

#### Subject Validation

- Since subjects must follow RFC rules (section 4.2 input handling), validate with regex
- Match against printable characters only; reject control characters
- For multibyte text, the email library should handle MIME encoding (Base64/Quoted-Printable)

```php
if (preg_match('/\A([!-~]+)\z/u', $subject) !== 1) {
  die('Subject contains invalid characters');
}
```

#### `mail()` and `mb_send_mail()` Fifth Parameter Warning

- PHP's `mail()` and `mb_send_mail()` have a 5th parameter (`additional_parameter`)
- This parameter is passed directly to the sendmail command
- The `escapeshellcmd()` function is used internally to prevent command execution, but other parameters can still be manipulated
- **Never allow external input in the 5th parameter** (`additional_parameter`)
- PHP bug: https://php.net/manual/ja/function.mb-send-mail.php (fixed around 2018)

#### Defense Against Mail Header Injection (Additional)

- Email address validation per RFC5322
- Subject line: check for control characters and newlines
- Use `mb_ereg` or similar for character class validation
- Check for non-UTF-8 characters if applicable

---

## Key Takeaways

- **Mail Header Injection** exploits newline characters in email header fields to add recipients, change the body, or attach files
- Always use **dedicated email libraries** rather than raw `sendmail` or manual header construction
- **Never place user input directly into email headers** without validation
- Validate all externally-supplied header values for **newline characters** (`\r\n`)
- Store recipient addresses **server-side**, never in hidden form fields
- Verify your mail server is **not an open relay** to prevent abuse as a spam relay
- The `additional_parameter` (5th argument) of PHP's `mail()`/`mb_send_mail()` should never accept external input
- Email header injection can escalate to spam distribution, phishing, and malware delivery through legitimate mail servers
