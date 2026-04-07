# File Upload Practice -- Quick Reference

## DVWA Paths

- **Upload page:** `/vulnerabilities/upload/`
- **Upload destination:** `/hackable/uploads/`
- **Shell access (after upload):** `/hackable/uploads/shell.php?cmd=whoami`

---

## Security Level Comparison

| Level | Validation | Bypass Method | Difficulty |
|---|---|---|---|
| **Low** | No check at all | Direct upload of `.php` file | Trivial |
| **Medium** | Content-Type header check | Change `Content-Type` to `image/jpeg` via proxy | Easy |
| **High** | File extension check (`.jpg`, `.jpeg`, `.png`) | Rename or combine with LFI; embed code in image | Moderate |
| **Impossible** | Extension + getimagesize() + image re-creation + rename + CSRF token | No known bypass | N/A |

---

## Book Chapter Reference

| Section | Topic |
|---|---|
| 4.12.1 | File upload vulnerability overview |
| 4.12.2 | Script execution via uploaded files |
| 4.12.3 | Countermeasures and safe upload handling |

---

## PHP Upload Settings (from Book)

These `php.ini` directives control upload behavior:

| Directive | Default | Description |
|---|---|---|
| `file_uploads` | `On` | Whether file uploads are allowed |
| `upload_max_filesize` | `2M` | Maximum size of a single uploaded file |
| `post_max_size` | `8M` | Maximum size of the entire POST body |
| `max_file_uploads` | `20` | Maximum number of files in one request |
| `upload_tmp_dir` | system default | Temporary directory for uploaded files |

If `post_max_size` is exceeded, `$_POST` and `$_FILES` become empty, which can cause unexpected behavior in validation logic.

---

## Bypass Techniques Summary

| Technique | Target Check | How It Works |
|---|---|---|
| Direct `.php` upload | No validation | Upload and access the file directly |
| Content-Type spoofing | Content-Type check | Modify header via proxy (e.g., `image/jpeg`) |
| Double extension (`shell.php.jpg`) | Extension check | May bypass naive extension parsing |
| Null byte (`shell.php%00.jpg`) | Extension check (old PHP) | Truncates filename at null byte (PHP < 5.3.4) |
| `GIF89a` prefix | Magic byte check | Prepend image magic bytes to PHP code |
| `.htaccess` upload | Extension check | Upload `.htaccess` to make `.jpg` execute as PHP |
| Polyglot image | Image validation | Embed PHP code inside valid image metadata |
| Case variation (`shell.pHp`) | Case-sensitive extension check | Bypass on case-sensitive checks |

---

## Book's Defense Checklist

- [ ] **Allowlist extensions** -- only permit known-safe extensions (`.jpg`, `.png`, `.gif`, `.pdf`)
- [ ] **Validate file content** -- use `getimagesize()`, `finfo_file()`, or equivalent to verify actual content
- [ ] **Re-create images** -- use `imagecreatefromjpeg()` / `imagecreatefrompng()` to strip embedded code
- [ ] **Rename uploaded files** -- generate random filenames to prevent direct access prediction
- [ ] **Store outside webroot** -- uploaded files should not be directly accessible via URL
- [ ] **Serve via script** -- use a download script that sets `Content-Disposition` and proper `Content-Type`
- [ ] **Set proper permissions** -- uploaded files should not have execute permission
- [ ] **Limit file size** -- configure `upload_max_filesize` and `post_max_size` appropriately
- [ ] **Use anti-CSRF tokens** -- prevent attackers from tricking users into uploading files

---

## Common Mistakes

1. **Checking only Content-Type** -- This header is sent by the client and can be set to anything.
2. **Using a denylist for extensions** -- Attackers find extensions you forgot (`.php5`, `.phtml`, `.phar`). Always use an allowlist.
3. **Not renaming files** -- Original filenames can contain path traversal (`../../evil.php`) or special characters.
4. **Storing uploads inside webroot** -- Even with extension checks, server misconfigurations can lead to execution.
5. **Trusting `$_FILES['name']`** -- This is client-controlled. Never use it for file paths without sanitization.
6. **Skipping content re-creation** -- `getimagesize()` alone can be fooled by polyglot files. Re-creating the image is the definitive defense.
