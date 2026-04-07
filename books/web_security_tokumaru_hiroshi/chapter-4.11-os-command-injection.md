# Chapter 4.11: OS Command Injection

Many programming languages used for web development allow executing OS commands via shell. When internal functions call shell commands and user input is improperly handled, unintended OS commands may be executed. This is called **OS Command Injection**.

---

## 4.11.1 OS Command Injection

### Overview

- Many web application languages provide functions to call OS commands via shell (e.g., `system()`, `exec()`, `popen()`)
- If these functions are used and user-controlled input reaches the shell without proper escaping, attackers can inject additional commands
- The shell (cmd.exe on Windows, /bin/sh or /bin/bash on Unix) interprets metacharacters that can chain or modify commands

### Summary Table

| Attribute | Details |
|-----------|---------|
| **Occurrence** | Locations that call shell-invoking functions |
| **Affected Pages** | All pages that use the vulnerable function |
| **Impact Type** | Information leaks, data tampering/deletion, attacks on other servers, system shutdown |
| **Severity** | High |
| **User Involvement** | Not required |
| **Countermeasures** | Avoid shell-invoking methods; use libraries; escape shell metacharacters |

---

### Potential Damage

- **Download attack tools** from external sources
- **Grant execution privileges** to downloaded tools
- **Exploit OS vulnerabilities** from inside to gain root/admin (Local Exploit)
- **Use the web server** as a launchpad for further attacks

Specific impacts on web servers:

- Viewing, modifying, or deleting files on the server
- Sending external emails (spam relay)
- Attacking other servers (stepping stone / pivot attacks)
- Mining cryptocurrency

---

### Attack Method and Impact

#### Example: sendmail Command via Contact Form

A contact form (`4b-001.html`) submits to a PHP script (`4b-002.php`):

```html
<body>
<form action="4b-002.php" method="POST">
  Inquiry:<br>
  Email:<input type="text" name="mail"><br>
  Body:<textarea name="inqu" cols="20" rows="3">
  </textarea><br>
  <input type="submit" value="Send">
</form>
</body>
```

The PHP script calls `sendmail` via `system()`:

```php
<?php
$mail = filter_input(INPUT_POST, 'mail');
system('/usr/sbin/sendmail -i <template.txt $mail');
// omitted
?>
```

The email template (`template.txt`):

```
From: webmaster@example.jp
Subject: =?UTF-8?B?...encoded...?=
Content-Type: text/plain; charset="UTF-8"

Content-Transfer-Encoding: 8bit
Inquiry received.
```

**Normal operation:** The form sends an email to the specified address.

**Attack:** Enter the following in the email field:

```
bob@example.jp;cat /etc/passwd
```

The semicolon (`;`) terminates the first command, and `cat /etc/passwd` executes as a second command, displaying the system's password file in the response.

---

### Adding Options to Inject Commands

- Applications that call OS commands may be vulnerable to **option injection** where attackers add command-line options
- For example, with `find` or similar commands, adding `-exec` can execute arbitrary commands
- Even when only options are added (not full command injection), this can still be dangerous

---

### Root Cause

OS commands are often invoked via shell. The shell provides:

- **Multiple command execution** using `;`, `&&`, `||`, `|` (pipe)
- **Backtick execution** using `` `command` `` for command substitution

#### Shell Multiple Command Execution

```bash
$ echo aaa ; echo bbb      # Sequential execution (both always run)
aaa
bbb

$ echo aaa && echo bbb     # Run second only if first succeeds
aaa
bbb

$ echo aaa || echo bbb     # Run second only if first fails
aaa

$ cat aaa | echo bbb       # Pipe (output of first feeds into second)
bbb
```

On Windows, `&` chains commands (equivalent to Unix `;`). Pipe `|`, `&&`, `||` work similarly. Special shell metacharacters include `(`, `)`, `<`, `>`, etc.

#### Two Conditions for OS Command Injection

1. **Shell metacharacters in the input are not escaped** when calling OS commands
2. **Shell-invoking functions are used** (e.g., `system()`, `popen()`)

---

### Using Functions That Invoke Shell

#### Perl's `open()` Function

Perl's `open()` can execute shell commands. For example:

```perl
#!/usr/bin/perl
print "Content-Type: text/plain\n\n";
open PL, '/bin/pwd' or die $!;
print <PL>;
close PL;
```

- `open()` with a pipe character appended to the filename executes the command
- If the filename is externally controlled and contains `|`, the pipe triggers command execution:

```perl
my $file = $q->param('file');
open (IN, $file) or die $!;    # Opens file
print <IN>;                     # Prints contents
close IN;                       # Closes file
```

When `file=ls+/sbin|` is passed, the `open()` call executes `ls /sbin` and displays the directory listing.

---

### Summary of Root Causes

OS Command Injection vulnerabilities arise when **all three** conditions are met:

1. **Using functions that invoke shell** (`system`, `open`, etc.)
2. **Passing external parameters** to shell-invoking functions
3. **Shell metacharacters in parameters are not escaped**

---

## Countermeasures

### Design Phase: Decide on a Strategy

| Phase | Action |
|-------|--------|
| **Requirements** | Determine functionality needs |
| **Basic Design** | Use libraries where possible; use OS commands only when unavoidable |
| **Detailed Design** | If using shell-invoking functions, ensure proper escaping; if not, select safe implementation |

### Implementation Approaches (in priority order)

1. **Choose an implementation that does not invoke shell commands**
2. **Use libraries instead of shell commands**
3. **Do not pass external input as command-line parameters**
4. **Escape parameters passed to OS commands using safe functions**

---

### 1. Choose a Shell-Free Implementation

- Prefer library functions or built-in language features over shell commands
- Example: Instead of calling `sendmail` via `system()`, use PHP's `mb_send_mail()`:

```php
<?php
$mail = filter_input(INPUT_POST, 'mail');
mb_language('Japanese');
mb_send_mail($mail, "Inquiry received",
    "From: webmaster@example.jp");
?>
```

- However, the mail header itself can be vulnerable to **mail header injection** (covered in section 4.9)

---

### 2. Use Functions with Shell-Invoking Capabilities Wisely

- **Perl:** `Perl`'s `system()` can take separate command and arguments, avoiding shell invocation:

```perl
my $rtn = system('/bin/grep', $keyword, glob('/var/data/*.txt'));
```

- When command name and parameters are passed separately, the shell is not invoked and metacharacters are not interpreted, eliminating OS Command Injection risk

---

### 3. Do Not Pass External Input to Command-Line Parameters

- Use `-t` option with `sendmail` to read recipient from headers instead of command-line arguments
- Use `popen()` and `fwrite()` to pipe the email content into sendmail, rather than passing the address on the command line:

```php
<?php
$mail = filter_input(INPUT_POST, 'mail');
$h = popen('/usr/sbin/sendmail -t -i', 'w');
if ($h === FALSE) {
    die('Cannot open pipe');
}
fwrite($h, <<<EndOfMail
To: $mail
From: webmaster@example.jp
Subject: =?UTF-8?B?...?=
Content-Type: text/plain; charset="UTF-8"
Content-Transfer-Encoding: 8bit

Inquiry received.
EndOfMail
);
pclose($h);
?>
```

#### open() Mode Specification

For Perl's `open()`, use `sysopen()` or specify the access mode as a second argument:

```perl
open(FL, '<', $file) or die 'Cannot open file';
```

| Mode | Meaning |
|------|---------|
| `<` | Read |
| `>` | Write (overwrite) |
| `>>` | Append |
| `+<` | Read + Write |
| `+>` | Create + Read/Write |
| `-\|` | Execute command and read output |

- By explicitly specifying mode `<`, shell invocation is prevented even if the filename contains pipe characters

---

### 4. Escape Shell Metacharacters

- When the above approaches are not feasible, escape parameters using safe functions
- **PHP:** `escapeshellarg()` wraps the argument in single quotes and escapes embedded quotes

```php
system('/usr/sbin/sendmail <template.txt ' . escapeshellarg($mail));
```

- However, `escapeshellarg()` is slightly more complex than simple escaping, and errors in escape processing can still introduce vulnerabilities
- The author recommends using `escapeshellarg()` as a **defense-in-depth** measure rather than the primary defense
- **Note:** PHP also has `escapeshellcmd()`, but it has known issues and is not recommended

#### Reference: Passing Parameters via Environment Variables

Instead of passing external values as command-line arguments, pass them through environment variables:

```bash
$ hello="Hello world!"
$ export hello
$ echo "$hello"
Hello world!
$
```

This approach avoids shell metacharacter interpretation entirely.

---

### Defense-in-Depth Measures

#### Parameter Validation

- Validate external input against application requirements
- For file names, restrict to alphanumeric characters
- If escape processing is applied, a successful escape means no injection even if validation is bypassed

#### Minimize Application Privileges

- Run commands under the web application's user account, not root
- External attacks often target web shells and backdoors
- Keep write permissions minimal -- prevent writing to document root or script directories
- To minimize privileges, use directory traversal countermeasures (section 4.10)

#### Keep OS and Middleware Patched

- OS Command Injection combined with **Local Exploit** (privilege escalation from inside the server) is a severe scenario
- Even if the web app user has limited permissions, a local exploit can escalate to root
- Keeping the OS and middleware updated is essential

---

### Reference: Shell-Invoking Functions by Language

| Language | Shell-Invoking Functions |
|----------|------------------------|
| **PHP** | `system()`, `exec()`, `passthru()`, `proc_open()`, `popen()` |
| **Perl** | `exec()`, `system()`, `` `...` `` (backticks), `qx/.../`, `open()` |
| **Ruby** | `exec()`, `system()`, `` `...` `` (backticks) |

- Ruby: In recent versions, `IO.popen` replaces `open()` for command execution; using `File.open()` for files avoids shell invocation

---

## Key Takeaways

- OS Command Injection is one of the **most severe** web application vulnerabilities -- it can lead to full server compromise
- The best defense is to **avoid calling shell commands entirely**; use language-native libraries instead
- When shell commands are unavoidable, **separate command from parameters** or use **safe escaping functions**
- Always apply **defense-in-depth**: validate input, minimize privileges, keep systems patched
- Be aware of shell metacharacters: `;`, `|`, `&&`, `||`, `` ` ``, `$()`, and more
