# Chapter 4.11: OS Command Injection -- Hands-On Practice

## Book Reference

Chapter 4.11 of **"Safe Web Application Development"** (体系的に学ぶ 安全なWebアプリケーションの作り方) by **Tokumaru Hiroshi**.

## Prerequisites

- Docker running with DVWA container
- Logged in to DVWA (`admin` / `password`)
- OWASP ZAP running and intercepting traffic
- FoxyProxy active and routing through ZAP

## What You'll Learn

- How shell metacharacters (`;`, `|`, `&&`, `||`) enable command chaining
- Techniques for extracting system information via injected commands
- Why shell-invoking functions (`system()`, `exec()`, etc.) are inherently dangerous
- How filters can be bypassed and why they are insufficient as a sole defense
- The proper countermeasure: `escapeshellarg()` and input validation

## DVWA Module

**Command Injection** -- found at `DVWA > Command Injection`

The application takes an IP address as input and runs a `ping` command on the server. The vulnerability exists because user input is passed directly into a shell command without adequate sanitization.

---

## Exercises

### Low Level

Set DVWA Security to **Low**.

1. Navigate to **DVWA > Command Injection**.

2. Enter a normal IP address and observe the expected behavior:

   ```
   127.0.0.1
   ```

   You should see standard `ping` output showing ICMP responses. This is the intended functionality.

3. Inject a second command using the semicolon metacharacter:

   ```
   127.0.0.1; whoami
   ```

   After the ping output, you will see the username of the web server process (typically `www-data`). The semicolon tells the shell to execute the next command sequentially regardless of whether the first command succeeds.

4. Read the system password file:

   ```
   127.0.0.1; cat /etc/passwd
   ```

   This dumps the entire `/etc/passwd` file, exposing usernames, home directories, and default shells for every account on the system.

5. List the web server document root:

   ```
   127.0.0.1; ls -la /var/www/html
   ```

   This reveals all files served by the web application, including source code, configuration files, and potentially sensitive data.

6. Use the AND operator:

   ```
   127.0.0.1 && id
   ```

   The `id` command shows the user ID, group ID, and group memberships of the process. The `&&` operator runs the second command only if the first succeeds.

7. Use the pipe operator:

   ```
   127.0.0.1 | uname -a
   ```

   This shows the full OS kernel version and architecture. The `|` operator sends the output of `ping` as input to `uname -a`, but `uname` ignores stdin and simply prints system info.

8. **Key takeaway:** The semicolon (`;`), ampersand (`&&`), and pipe (`|`) are shell metacharacters that allow command chaining. At Low level, none of these are filtered.

---

### Medium Level

Set DVWA Security to **Medium**.

1. The application now filters `&&` and `;` from user input. Try previous payloads and notice they no longer work:

   ```
   127.0.0.1; whoami       --> filtered, no injection
   127.0.0.1 && id         --> filtered, no injection
   ```

2. The pipe operator is NOT filtered. Try:

   ```
   127.0.0.1 | whoami
   ```

   This still works and returns the username.

3. The OR operator is also not filtered:

   ```
   127.0.0.1 || whoami
   ```

   The `||` operator runs the second command only if the first fails. Since `ping 127.0.0.1` succeeds, try with an invalid address to trigger the second command, or note that depending on the server configuration it may still execute.

4. Use just the pipe with no valid IP:

   ```
   | whoami
   ```

   This works because the shell interprets the empty left side of the pipe and still executes the right side.

**Key takeaway:** Blacklist filtering is fragile. Missing even one metacharacter leaves the application vulnerable.

---

### High Level

Set DVWA Security to **High**.

1. The application now filters more metacharacters, including `|`, `&`, and `;`. However, there is a flaw in the filter implementation.

2. Try this payload with no spaces around the pipe:

   ```
   127.0.0.1|whoami
   ```

   This works because the filter checks for `"| "` (pipe followed by a space), not the bare pipe character `"|"`. Removing the space bypasses the filter entirely.

3. This is a common real-world filtering mistake. Developers test with the obvious payloads but miss edge cases in their pattern matching.

**Key takeaway:** Blacklist approaches are doomed to fail. There are always creative ways to bypass character filters.

---

### Impossible Level

Set DVWA Security to **Impossible**.

1. Try any injection payload -- nothing works:

   ```
   127.0.0.1; whoami       --> fails
   127.0.0.1 | whoami      --> fails
   127.0.0.1|whoami         --> fails
   ```

2. Click **View Source** and examine the code. The Impossible level uses two defenses:

   - **`escapeshellarg()`** -- wraps the entire input in single quotes and escapes any embedded single quotes, neutralizing all shell metacharacters.
   - **IP format validation** -- splits the input by `.` and verifies that exactly 4 numeric octets are present.

3. This matches the book's recommendation: if you must invoke a shell command, use `escapeshellarg()` to properly escape user input. Better yet, avoid shell-invoking functions entirely and use language-native libraries (e.g., PHP's socket functions for network operations).

---

## What to Observe in ZAP

While performing these exercises, watch ZAP's History tab:

- **Request parameters:** Look at the `ip` parameter in the POST request body. You will see your injected command as part of the parameter value (e.g., `ip=127.0.0.1%3B+whoami` where `%3B` is the URL-encoded semicolon).
- **Response body:** At Low level, the response HTML contains both the ping output AND the injected command output. Search for the injected command's result in the response.
- **Response size:** Compare response sizes across security levels for the same payload. At Low level with `cat /etc/passwd`, the response will be significantly larger due to the dumped file contents. At Impossible level, the response size stays consistent because no injection occurs.

---

## Book Connection

- **Shell metacharacters** (`;`, `|`, `&&`, `||`, backticks, `$()`) correspond to the book's table of dangerous characters in Section 4.11. These characters have special meaning in shell interpreters and enable command injection when user input is passed to shell commands unescaped.

- **`escapeshellarg()`** is the book's recommended PHP escaping function (Section 4.11). It wraps the argument in single quotes and escapes any existing single quotes, making it safe to pass to shell commands.

- **The book emphasizes:** the best defense is to NOT use shell-invoking functions at all. Use language-native libraries or APIs that do not involve spawning a shell process. Shell invocation should be the last resort, and when unavoidable, `escapeshellarg()` must be applied to every external input.

---

## Key Takeaways

1. **OS command injection is catastrophic.** An attacker who can execute arbitrary commands has full control of the server process and can read files, modify data, install backdoors, or pivot to other systems.

2. **Blacklist filtering is insufficient.** There are too many shell metacharacters and encoding tricks to reliably block them all. The Medium and High levels demonstrate how easily blacklists are bypassed.

3. **Whitelist validation is effective.** The Impossible level validates that the input matches the expected format (an IP address) before using it. This approach rejects anything unexpected rather than trying to block known-bad patterns.

4. **Avoid shell-invoking functions.** The root cause is passing user input to a shell. Eliminate the shell entirely by using language-native libraries, and the vulnerability class disappears.

5. **`escapeshellarg()` is the minimum safeguard.** When shell invocation is unavoidable, this function neutralizes metacharacters. Never build shell commands through string concatenation alone.
