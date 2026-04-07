# Chapter 4.12: File Upload Vulnerabilities

Web applications often allow users to upload images, PDFs, and other files. This section covers vulnerabilities that arise from file upload and download functionality.

---

## 4.12.1 Overview of File Upload Vulnerabilities

### Types of Attacks

| Attack Type | Description |
|-------------|-------------|
| **DoS via upload** | Uploading excessively large files to exhaust server resources |
| **Server-side script execution** | Uploading a script file that the server executes |
| **Tricking users into downloading malicious files** | Uploading files containing malware or exploits |
| **Unrestricted file download** | Downloading files without proper access control |

---

### DoS Attacks Against Upload Functionality

- Attackers send large amounts of data via the upload mechanism, causing server slowdown or crash (Denial of Service)
- In PHP, upload limits are controlled via `php.ini`:

| Setting | Description | Default |
|---------|-------------|---------|
| `file_uploads` | Enable/disable file uploads | On |
| `upload_max_filesize` | Max size per uploaded file | 2M |
| `max_file_uploads` | Max number of files per upload | 20 |
| `post_max_size` | Max size of entire POST body | 8M |
| `memory_limit` | Max memory a script can consume | 128M |

- Apache can also limit request body size:

```apache
LimitRequestBody 102400
```

- Set these values to reasonable limits for your application
- If file upload is not needed, set `file_uploads` to `Off`

#### Column: Check Memory and CPU Usage Too

- Beyond file size, check other parameters: compression ratio (zip bombs), image dimensions vs. memory, etc.
- CPU-intensive operations on uploaded files (virus scanning, image processing) should also be resource-limited

---

### Uploaded Files Executed as Server-Side Scripts

- If an uploaded file is saved in the web server's public directory **and** the server treats it as an executable script, the attacker gains **server-side code execution**
- This is equivalent to OS Command Injection in impact: file viewing/modification/deletion, email sending, pivoting to other servers, cryptocurrency mining

### Tricking Users into Downloading Malicious Files

- Attackers upload files containing JavaScript or malware
- When other users download and open these files, the malicious code executes on their machines
- This can lead to client-side XSS, malware infection, etc.
- See section 4.12.3 for details on file download XSS

### Unrestricted File Download

- File download functionality without proper access control can expose files to unauthorized users
- Even if the file URL itself uses random names, URL guessing or lack of authentication can allow access
- See section 5.3 for authorization-related countermeasures

---

## 4.12.2 Uploaded Files Executed as Server-Side Scripts

### Overview

- If uploaded files are stored in the public web directory, and the file extension indicates an executable script (`.php`, `.asp`, `.aspx`, `.jsp`), the web server will execute the file when accessed via URL

### Summary Table

| Attribute | Details |
|-----------|---------|
| **Occurrence** | File upload features that store to public directories |
| **Affected Pages** | All pages can be affected |
| **Impact Type** | Information leaks, data tampering/deletion, DoS, system compromise |
| **Severity** | High |
| **User Involvement** | Not required |
| **Countermeasures** | Store outside public directory; restrict executable extensions |

---

### Attack Method and Impact

#### Sample Script Explanation

An upload form (`4c-001.php`) and handler (`4c-002.php`):

```html
<body>
<form action="4c-002.php" method="POST"
  enctype="multipart/form-data">
  File:<input type="file" name="imgfile" size="20"><br>
  <input type="submit" value="Upload">
</form>
</body>
```

The PHP handler saves to the `/4c/img/` directory and displays the uploaded file:

```php
<?php
$tmpfile = $_FILES['imgfile']['tmp_name'];
$tofile = $_FILES['imgfile']['name'];

if (is_uploaded_file($tmpfile)) {
    // Check if uploaded
    if (!move_uploaded_file($tmpfile, 'img/' . $tofile)) {
        die('Cannot upload file');
    }
}
$imgurl = 'img/' . urlencode($tofile);
?>
<body>
<a href="<?php echo htmlspecialchars($imgurl); ?>">
  <?php echo htmlspecialchars($tofile, ENT_NOQUOTES, 'UTF-8'); ?>
</a>
uploaded.<br>
<img src="<?php echo htmlspecialchars($imgurl); ?>">
</body>
```

**Normal usage:** Upload an image (e.g., `elephant.png`) and it displays correctly.

#### Uploading a PHP Script

Instead of an image, upload a PHP file (`4c-900.php`):

```php
<pre>
<?php
system('/bin/cat /etc/passwd');
?>
</pre>
```

- When this file is accessed via the browser, the PHP script executes on the server
- The `system()` call runs `cat /etc/passwd`, displaying the system password file
- Impact is equivalent to OS Command Injection -- `system()`, `passthru()`, and similar functions can execute any OS command under the web server's account

#### Column: XSS via Filenames

- When building URLs from uploaded filenames, XSS can occur if the filename contains special characters
- Always use `htmlspecialchars()` and `urlencode()` when outputting filenames

---

### Root Cause

The vulnerability exists when **both** conditions are met:

1. **Uploaded files are saved in a publicly accessible directory**
2. **The file extension allows server-side script execution** (`.php`, `.asp`, `.aspx`, `.jsp`, etc.)

In typical setups, if either condition is removed, the vulnerability is eliminated.

---

### Countermeasures

#### 1. Do Not Store Uploaded Files in the Public Directory

- Store files outside the document root (e.g., `/var/upload/`)
- Use a **download script** to serve files to users:

**Upload handler (`4c-002a.php`):**

```php
<?php
function get_upload_file_name($tofile) {
    // Extension check
    $info = pathinfo($tofile);
    $ext = strtolower($info['extension']);
    if ($ext != 'gif' && $ext != 'jpg' && $ext != 'png') {
        die('Only gif, jpg, png allowed');
    }
    // Generate unique filename
    $count = 0;
    do {
        // Build filename
        $file = sprintf('%s/%08x.%s', UPLOADPATH, mt_rand(), $ext);
        $fp = @fopen($file, 'x');   // 'x' = create only, fail if exists
    } while ($fp === FALSE && ++$count < 10);
    if ($fp === FALSE) {
        die('Cannot create file');
    }
    fclose($fp);
    return $file;
}
```

- Validates extension (whitelist: gif, jpg, png only)
- Generates a random filename to prevent conflicts
- Uses `fopen()` with `'x'` mode to avoid race conditions

**Download script (`4c-003.php`):**

```php
<?php
// Note: This download script has XSS vulnerabilities (see 4.12.3)
define('UPLOADPATH', '/var/upload');
$mimes = array(
    'gif' => 'image/gif',
    'jpg' => 'image/jpeg',
    'png' => 'image/png'
);
$file = $_GET['file'];
$info = pathinfo($file);
$ext = strtolower($info['extension']);
$content_type = $mimes[$ext];
if (!$content_type) {
    die('Only gif, jpg, png files');
}
header('Content-Type: ' . $content_type);
readfile(UPLOADPATH . '/' . basename($file));
?>
```

- Determines the correct Content-Type from the extension
- Uses `basename()` to prevent directory traversal (see section 4.10)
- Files stored outside the public directory cannot be directly executed by the web server

#### 2. Restrict File Extensions

- Even if files must be stored in the public directory, ensure only safe extensions are allowed
- Validate file extensions against a whitelist

#### Column: Checking Extensions at the Right Time

- Extension checks should happen both at **upload time** and at **download time**
- The Server Side Includes (SSI) directive `.shtml` can also execute server-side commands
- On Apache, multiple-extension files like `foo.php.png` may be interpreted based on any extension, not just the last one (depends on Apache configuration)
- The most reliable approach: check extensions, but also store outside the public directory

---

## 4.12.3 Cross-Site Scripting via File Download

### Overview

- When users download files, the browser may misinterpret the file type
- If the browser treats a file as HTML (despite being a PDF or image), embedded JavaScript can execute
- This leads to **XSS via file download**

### Examples

- A PDF file containing HTML tags
- An application serving PDF files but setting the wrong Content-Type
- The browser (especially older IE) may "sniff" the content and treat it as HTML

### Summary Table

| Attribute | Details |
|-----------|---------|
| **Occurrence** | File upload and download features |
| **Affected Pages** | Session management and authentication pages are at risk |
| **Impact Type** | Cookie theft, session hijacking |
| **Severity** | Medium to High |
| **User Involvement** | Required (user must click a link) |
| **Countermeasures** | Set correct Content-Type; use `nosniff`; use `Content-Disposition` |

---

### Attack Method: PDF Download XSS

A download service experiment using a PDF-only download script (`4c-012.php`):

```php
<?php
define('UPLOADPATH', '/var/upload');
$mimes = array('pdf' => 'application/x-pdf');
$file = $_GET['file'];
$info = pathinfo($file);
$ext = strtolower($info['extension']);
$content_type = $mimes[$ext];
if (!$content_type) {
    die('Only pdf files allowed');
}
header('Content-Type: ' . $content_type);
readfile(UPLOADPATH . '/' . basename($file));
?>
```

**Attack:** Upload an HTML file disguised as a PDF:

```html
<script>alert('XSS');</script>
```

Saved as `4c-902.pdf`, when accessed via the download script, the browser may execute the JavaScript.

#### PATH_INFO Exploitation

- The attacker manipulates the URL using PATH_INFO to change the apparent file extension:

```
http://example.jp/4c/4c-013.php/a.html?file=5124c24e.pdf
```

- PATH_INFO (`/a.html`) is appended after the script name but before the query string
- Some browsers use the URL's apparent extension (`.html`) to determine file type, ignoring the Content-Type header
- This triggers JavaScript execution in the browser

---

### Content-Type and IE Behavior

- When IE downloads content, it determines the file type based on multiple signals:
  - The Content-Type header
  - The URL's file extension
  - The actual file content (content sniffing)
- IE's Content-Type handling uses the registry (`HKEY_CLASSES_ROOT\MIME\Database\Content Type`)
- A wrong Content-Type (e.g., `application/x-pdf` instead of `application/pdf`) can cause IE to fall back to content sniffing
- Content sniffing may identify HTML content and render it, executing embedded scripts

---

### Root Cause

- The **incorrect Content-Type** setting is the primary cause
- The correct Content-Type for PDF is `application/pdf`, not `application/x-pdf`
- Even with the correct Content-Type, IE may ignore it in some cases
- Browsers without `X-Content-Type-Options: nosniff` may perform content sniffing

---

### Countermeasures

#### At Upload Time

- **Check that file extensions are allowed** (whitelist approach, as described in section 4.12.2)

#### At Download Time

1. **Set Content-Type correctly** (required)
2. **Set `X-Content-Type-Options: nosniff`** response header (required)
3. **Set `Content-Disposition` header** as needed
4. **For PDFs, combine with section 4.12.4 countermeasures**

#### 1. Set Content-Type Correctly

- Use the standard MIME types (e.g., `application/pdf` not `application/x-pdf`)
- For download-only scripts, look up the Content-Type from the file extension:
  - Apache: configure `mime.types`
  - Application code: maintain a MIME type mapping

#### 2. Set X-Content-Type-Options: nosniff

- This header tells the browser: "Do not guess the Content-Type; trust the header"
- Prevents content sniffing attacks
- Historically IE-specific, but now supported by all major browsers

**Apache:**

```apache
Header always append X-Content-Type-Options: nosniff
```

**nginx:**

```nginx
add_header X-Content-Type-Options: nosniff;
```

- Recommended to set this on **all HTTP responses**, not just downloads

#### 3. Set Content-Disposition Header

- For files that should be downloaded (not displayed in browser), use:

```
Content-Type: application/octet-stream
Content-Disposition: attachment; filename="hogehoge.pdf"
```

- `Content-Disposition: attachment` forces the browser to download rather than display the file
- The `filename` parameter specifies the default download filename

#### Other Checks

- Beyond XSS protection, perform **additional validation** on uploaded files:
  - File size (beyond the maximum)
  - Image dimensions and color depth
  - Whether the file can be read as an image
  - Virus/malware scanning (see section 8.4)
  - Content appropriateness (manual or automated)
  - Copyright compliance
  - Legal/regulatory compliance (obscenity, etc.)

---

## 4.12.4 PDF FormCalc Content Hijacking

### Overview

- PDF supports a scripting language called **FormCalc** (PDF 1.5+)
- FormCalc has a **URL function** that can make HTTP requests
- Adobe Acrobat Reader executes FormCalc, enabling attacks where:
  - A malicious PDF is uploaded to a target site
  - When a victim opens the PDF, FormCalc sends HTTP requests to the target site
  - The victim's browser attaches cookies for the target site to these requests
  - The attacker can exfiltrate the response data (stealing authenticated content)

### Summary Table

| Attribute | Details |
|-----------|---------|
| **Occurrence** | Pages that allow uploading and downloading PDF files |
| **Affected Pages** | Session management and authentication pages |
| **Impact Type** | Cookie theft, session hijacking, content exfiltration |
| **Severity** | Medium to High |
| **User Involvement** | Required (user must click a link) |
| **Countermeasures** | Force PDF download; use `object`/`embed` restrictions; block POST requests to PDF endpoints |

---

### Attack Flow

1. **Attacker** uploads a PDF containing FormCalc script to the target site (example.jp)
2. **Attacker** also uploads a malicious HTML page to a trap site (trap.example.com)
3. The HTML page embeds the uploaded PDF from example.jp using `<object>` or `<embed>`
4. **Victim** (logged into example.jp) visits the trap page
5. Adobe Acrobat Reader loads the PDF and executes FormCalc
6. FormCalc sends HTTP requests to example.jp -- the browser includes the victim's cookies
7. The response (containing the victim's private data) is sent back to the attacker's trap site

**PoC available at:** `https://github.com/nccgroup/CrossSiteContentHijacking`

### Demonstration Steps

1. Victim logs into example.jp
2. Navigate to the FormCalc content hijacking page
3. Click the attack link / download the malicious PDF
4. The PDF's FormCalc script fetches private data from example.jp
5. Data (e.g., user ID) is exfiltrated to the attacker

### HTTP Request from Adobe Reader

```
GET http://example.jp/31/31-022.php HTTP/1.1
Accept: */*
User-Agent: Mozilla/3.0 (compatible; Spider 1.0; Windows)
Proxy-Connection: Keep-Alive
Cookie: PHPSESSID=4aage527dbubc9d088ekf68rj0
Host: example.jp
```

- The User-Agent reveals it is Adobe Reader (not a normal browser)
- Cookies for example.jp are attached, giving the request authenticated access

---

### Root Cause

- Adobe Acrobat Reader's security model does not adequately restrict FormCalc's network access
- The fundamental issue is that Acrobat Reader allows PDF scripts to make cross-origin HTTP requests while attaching the browser's cookies
- Web applications that accept PDF uploads and serve them from the same origin are vulnerable

---

### Countermeasures

#### 1. Force PDF Download (Do Not Open in Browser)

- If PDFs are opened in-browser, Acrobat Reader's plugin can execute FormCalc
- Force download by setting response headers:

```
Content-Type: application/octet-stream
Content-Disposition: attachment; filename="hogehoge.pdf"
X-Download-Options: noopen
```

- `X-Download-Options: noopen` is IE-specific; it disables the "Open" button in the download dialog, forcing the user to save first

#### 2. Prevent PDF from Being Opened via object/embed

- Even if you force downloads on your own pages, attackers can embed the PDF URL in their own pages using `<object>` or `<embed>`
- The most effective server-side defense: **reject non-POST requests** to the PDF download endpoint

```php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 400 Bad Request');
    die('POST method required');
}
```

- Since `<object>` and `<embed>` use GET requests, this blocks the attack
- Legitimate downloads via form submission (POST) continue to work

---

## Summary

- File upload and download functionality introduces multiple vulnerability categories
- **Always store uploaded files outside the public web directory** -- this prevents server-side script execution
- **Validate file extensions** using a whitelist approach at both upload and download time
- **Set correct Content-Type, X-Content-Type-Options: nosniff, and Content-Disposition headers** on all file downloads
- **For PDF files**, force download mode and block GET-based access to prevent FormCalc content hijacking
- Resource limits (file size, memory, CPU) should be configured to prevent DoS via upload
- Defense-in-depth: combine multiple countermeasures rather than relying on a single check
