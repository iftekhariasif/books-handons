# Chapter 2: Setting Up the Practice Environment (実習環境のセットアップ)

**Book:** "How to Build Secure Web Applications" (安全なWebアプリケーションの作り方) by Tokumaru Hiroshi

---

## Preceding Context: OWASP Top 10 (from end of Chapter 1)

- OWASP (The Open Web Application Security Project) is an international, open community focused on resolving web application security issues.
- OWASP has been publishing the OWASP Top 10 (the most critical web application security risks) periodically since 2004.
- At the time of writing, the 2017 edition was the latest.
- OWASP Top 10 is widely used as a baseline for web application security at global enterprises; adoption is also increasing in Japan.

### Table: OWASP Top 10 - 2017 and Corresponding Book Sections

| Rank | OWASP Top 10 - 2017 | Book Section |
|------|---------------------|--------------|
| A1 | Injection | 4.4.1 SQL Injection, 4.11.1 OS Command Injection |
| A2 | Broken Authentication | 5.1 Authentication |
| A3 | Sensitive Data Exposure | 4.10.2 Insecure Redirects/Forwards |
| A4 | XML External Entities (XXE) | 4.14.3 XML External Entity Attack (XXE) |
| A5 | Broken Access Control | 5.3 Authorization |
| A6 | Security Misconfiguration | 8.1 Web Server Security Measures |
| A7 | Cross-Site Scripting (XSS) | 4.3.1/4.3.2 Cross-Site Scripting, 4.17.1 DOM Based XSS |
| A8 | Insecure Deserialization | 4.14.2 Insecure Deserialization |
| A9 | Using Components with Known Vulnerabilities | 8.1 Web Server Security Measures |
| A10 | Insufficient Logging & Monitoring | 5.4 Logging |

---

## 2.1 Overview of the Practice Environment (実習環境の概要)

### Software Stack for Vulnerability Samples

The book assumes vulnerability samples run on the following environment:

| Software | Version |
|----------|---------|
| Linux | Debian 9 |
| nginx | 1.10 |
| Apache | 2.4 |
| PHP | 5.3 / PHP 7.0 |
| Tomcat | 8.5 |
| MariaDB | 10.1 |
| Postfix | 3.1 |

### Architecture Diagram

- **Browser** connects to a **Virtual Machine** running on the local PC
- Inside the VM: Linux with nginx/Apache, PHP, Tomcat, MariaDB
- The VM simulates an internet-facing web server environment
- The Linux server on the VM is actually running on the user's own PC, but behaves as if it were an internet server
- Using a VM allows reproducing a server-like environment on a local machine

### Software to Install on the Host Machine

1. **Firefox** (browser)
2. **VirtualBox** (virtual machine runtime)
3. **Virtual Machine image** (pre-built)
4. **OWASP ZAP** (vulnerability diagnosis tool)
5. **FoxyProxy-Standard** (Firefox add-on for switching proxy settings)

### Downloading the Practice Virtual Machine

- Two VMs are provided: `wasbook` and `openvas`
- Download URL: **https://wasbook.org/download/**
- BASIC authentication credentials are listed on p.667 of the book

### License for the Book's Sample Programs

- Copyright for software included in the book belongs to the author as a general rule
- For items where a source other than the author is credited, follow the original source's license
- The practice environment and OpenVAS software bundled in the VM follow their respective licenses
- Sample programs developed by the author (including Bad Todo) are part of the book; copyright belongs to the author
- Programs may be used freely for learning purposes
- Do NOT incorporate code from the book into software intended for development of actual products (as the code intentionally contains vulnerabilities)

---

## 2.2 Installing Firefox (Firefoxのインストール)

- Firefox is a popular browser widely used among security professionals
- It is the only major browser that natively implements an XSS filter (Cross-Site Scripting filter) -- however, the book notes this is not enabled by default
- The concept of "Cross-Site Scripting" as a vulnerability is easier to understand using Firefox
- **Download URL:** https://www.mozilla.org/ja/firefox/new/
- Download and run the installer from the official site

---

## 2.3 Installing VirtualBox (VirtualBoxのインストール)

### What is VirtualBox?

- Free virtualization software provided by Oracle
- Used in this book to run a Linux server inside a VM that acts as a web server
- Both VirtualBox and OWASP ZAP are free tools

### System Requirements

| Requirement | Specification |
|-------------|--------------|
| CPU | Standard x86 compatible or x86-64 with SSE2 support (for Windows) |
| OS | Windows 7, 8, 8.1, 10; or Mac OS X 10.10 or later |
| Memory | 3 GB minimum (8 GB or more recommended) |
| Hard Disk | 15 GB or more free space (including the VM) |

### Download and Install

- **VirtualBox version used:** 5.2.10
- **Official site:** https://www.virtualbox.org/
- Go to the Downloads page and select the appropriate platform package:
  - Windows hosts
  - OS X hosts
  - Linux distributions
  - Solaris hosts
- Run the installer, click "Next" through all steps
- A warning about network temporarily disconnecting may appear -- do not perform other work during installation
- Answer all installer questions affirmatively
- On the final screen, ensure "Start Oracle VM VirtualBox..." checkbox is checked, then click "Finish"

### Configure Host-Only Network

1. After VirtualBox starts, go to **File > Host Network Manager**
2. Verify that **"VirtualBox Host-Only Ethernet Adapter"** exists with IPv4 address **192.168.56.1/24**
3. If the entry shows different numbers, or if no entry exists at all:
   - Click "Create" to add a new adapter
   - Click "Properties" to configure it
4. Set the following values:

| Setting | Value |
|---------|-------|
| IPv4 Address | 192.168.56.1 |
| IPv4 Network Mask | 255.255.255.0 |

5. Click "Apply"

---

## 2.4 Installing and Verifying the Virtual Machine (仮想マシンのインストールと動作確認)

### Importing the VM

1. Download the VM archive file (`wasbook.ova`) from the URL in Section 2.1
   - Enter the BASIC authentication ID and password listed on p.667
2. In VirtualBox, go to **File > Import Appliance**
3. Select the downloaded `wasbook.ova` file and click "Next"
4. The import details screen appears -- click "Import"
5. After import completes, `wasbook` appears in the VirtualBox Manager

### Verify Network Settings

Before starting the VM, verify network adapter settings:

1. Select `wasbook` in the VirtualBox Manager, click "Settings"
2. Go to the "Network" pane

**Adapter 1 Settings:**
- Attached to: **NAT**

**Adapter 2 Settings:**
- Attached to: **Host-Only Adapter**
- Name: **VirtualBox Host-Only Ethernet Adapter** (numbers after this may vary)

3. Click OK to confirm

### Starting the VM

1. Click the "Start" button in VirtualBox Manager
2. The VM will begin booting; close any informational messages by clicking the "X" icon
3. Wait until the `wasbook login:` prompt appears -- this indicates boot is complete

### Logging In

- **User ID:** `wasbook`
- **Password:** `wasbook`

### Verifying the IP Address

1. After logging in, run `ip a` and press Enter
2. Confirm that the IPv4 address **192.168.56.101** is displayed

### Testing Connectivity (Ping Test)

1. From the **host OS** (Windows command prompt or macOS Terminal), run:
   ```
   ping 192.168.56.101
   ```
2. On macOS, stop the ping with `Ctrl+C`
3. Successful replies confirm connectivity between host and VM

### Accessing the Web Server

1. Open Firefox and navigate to: **http://192.168.56.101**
2. If the practice environment page loads, VirtualBox and the VM installation is complete

### Shutting Down the VM

```bash
wasbook@wasbook:~$ sudo shutdown -h now
```
- Enter the password when prompted

### Linux Operation Notes

- The book does not explain Linux operation in detail
- Refer to Linux (Debian) beginner guides or websites for further reference

---

## Editing the hosts File

To make the practice smoother, add the following hostnames to your system's hosts file:

| Hostname | Purpose |
|----------|---------|
| example.jp | Vulnerable application site |
| api.example.net | Vulnerable site (separate domain API) |
| trap.example.com | Attacker-controlled malicious site |

### hosts File Content

```
# localhost name resolution is handled within DNS itself.
#    127.0.0.1       localhost
#    ::1             localhost
127.0.0.1    localhost
192.168.56.101    example.jp    api.example.net    trap.example.com
```

### Editing Instructions

**Windows:**
- hosts file location: `C:\Windows\System32\drivers\etc\hosts`
- Must be edited with administrator privileges
- Open Notepad from Start Menu > Windows Accessories > right-click "Run as administrator"
- Open the hosts file and save changes
- Ensure "All Files" is selected in the file type dropdown (not just .txt files)

**macOS:**
- Edit from Terminal:
  ```bash
  $ sudo nano /etc/hosts
  ```
- Enter the macOS password when prompted
- Use `nano` editor; when finished editing, press `Ctrl+X` to save and exit

### Verifying hosts File Changes (Ping Test)

- Run `ping example.jp` from Windows command prompt or macOS Terminal
- The VM should be running beforehand
- If `example.jp` cannot be found, possible causes:
  - IP address or hostname typo in the hosts file
  - The hosts file was not edited with administrator/root privileges, so changes were not actually saved

### Verifying Apache and PHP

- After ping verification succeeds, open Firefox and navigate to: **http://example.jp/**
- Confirm the same content as the earlier direct-IP access (from Section 2.2) is displayed

---

## 2.5 Installing OWASP ZAP (OWASP ZAPのインストール)

### What is OWASP ZAP?

- **OWASP ZAP** (OWASP Zed Attack Proxy) is a free, open-source web application vulnerability diagnosis tool
- Developed and maintained by OWASP (The Open Web Application Security Project)
- Runs on Windows, PC/Mac as a proxy to intercept, observe, and modify HTTP communications
- Similar tools include Burp Suite and Fiddler, but this book uses OWASP ZAP
- Key features:
  - Completely free to use
  - Automated scanning tools readily available
  - Available on both Windows and macOS

### Installing JRE (Java Runtime Environment)

- OWASP ZAP is written in Java, so a **Java Runtime Environment (JRE)** is required
- On macOS, OWASP ZAP bundles its own JRE, so no separate JRE installation is needed
- The following instructions are **for Windows users only**

1. Check if Java is installed by running in Command Prompt:
   ```
   java -version
   ```
2. If a "64-Bit" string appears, a 64-bit JRE is installed
3. If you have a 64-bit Windows but a 32-bit JRE is installed (or Java is not installed at all), install the JRE:
   - **JRE Download URL:** https://java.com/ja/download/manual.jsp
   - For 64-bit: download the installer labeled "64-bit"
   - For 32-bit: download the "Windows Online" installer

### Installing OWASP ZAP

- Common for both Windows and macOS
- **Official site:** https://www.owasp.org/index.php/OWASP_Zed_Attack_Proxy_Project
- Click "Download ZAP" to access the downloads page
- **Version used:** ZAP 2.7.0 Standard
- Download the appropriate installer for your platform:
  - Windows (64-bit) Installer
  - Windows (32-bit) Installer
  - Linux Installer
  - Linux Package
  - macOS Installer
  - Cross Platform Package

### OWASP ZAP Installation Steps

1. Run the downloaded installer
2. Accept the license agreement by clicking "Accept"
3. Select "Standard Install" and proceed
4. Click "Finish" when installation completes

### OWASP ZAP Configuration

**Windows Firewall:**
- On first launch, Windows Defender Firewall may show a blocking dialog -- grant access permission

**macOS Gatekeeper:**
- On first launch, macOS may display "Developer cannot be verified" -- go to **System Preferences > Security & Privacy** and click "Open Anyway"

**Initial Setup:**
1. A dialog appears; click "Close" (閉じる)
2. A license agreement dialog appears; click "Accept"
3. Session persistence method selection appears -- choose "Save with current timestamp" (recommended: the topmost option) to save OWASP ZAP session logs with date-based filenames
4. Click "Start"

### Configuring OWASP ZAP Options

1. Go to **Tools > Options** to open the Options screen

**Local Proxies Settings:**
- In the left pane, select "Local Proxies"
- Set the proxy listening address and port:
  - **Address:** `localhost`
  - **Port:** `8080` (default; if another application uses 8080, change to another port such as `50080`)

**Breakpoints:**
- In the left pane, select "Breakpoints"
- In the right pane, under "Proxy Mode," select **"Request and Response"** (to display requests and responses separately)

**Display Settings (Windows only):**
- In the left pane, select "Display"
- Set the font under the font name field to a Japanese font (e.g., Meiryo)
- Adjust size as needed

2. Click OK to close the Options screen

---

## 2.6 Installing Firefox Extension: FoxyProxy-Standard

### What is FoxyProxy?

- A Firefox extension for switching proxy settings per site
- Essential when using proxy tools like OWASP ZAP
- **FoxyProxy URL:** https://addons.mozilla.org/ja/firefox/addon/foxyproxy-standard/

### Installation Steps

1. Open the above URL in Firefox
2. Click "Add to Firefox" button
3. Click "Add" in the confirmation dialog

### Downloading and Importing FoxyProxy Configuration

1. Navigate to `http://example.jp/` in Firefox
2. At the bottom of the page, right-click the "FoxyProxy configuration file" link
3. Download the file (filename: `foxyproxy.json`)
   - On Mac: two-finger tap or Control+click to show context menu
4. From the FoxyProxy icon in Firefox, select "Options"
5. In the FoxyProxy Options page, click the "Import" icon
6. In the Import Settings dialog, click "Browse" and select the downloaded `foxyproxy.json` file
7. Click OK (settings will be overwritten)

### Verifying FoxyProxy Configuration

After import, verify these settings:

- **Proxy selection:** "Use Enabled Proxies by Patterns and Priority" should be selected
- **OWASP ZAP localhost** entry should be present and enabled

**Proxy Entry Details (Edit Proxy > OWASP ZAP):**

| Setting | Value |
|---------|-------|
| Proxy Type | HTTP |
| Title | OWASP ZAP |
| IP Address | localhost |
| Port | 50080 |

**Pattern Whitelist (via "Patterns" button):**

| Name | Pattern | Type | https On/Off |
|------|---------|------|-------------|
| White | *.example.jp/* | wildcard | On |
| White | *.example.net/* | wildcard | On |
| White | *.example.com/* | wildcard | On |

- This configuration means OWASP ZAP proxy is used only for `*.example.jp`, `*.example.net`, and `*.example.com` requests
- All other traffic bypasses the proxy, preventing unnecessary traffic from going through OWASP ZAP

---

## 2.7 Trying Out OWASP ZAP (OWASP ZAPを使ってみる)

### Basic Usage

1. With the above configuration complete, click the `phpinfo()` link from the practice environment top page in Firefox
2. OWASP ZAP will show the intercepted traffic
3. In the bottom URL list, select `phpinfo.php` and click the arrow icon to expand
4. The display shows:
   - **Left pane:** HTTP Request
   - **Right pane:** HTTP Response
5. OWASP ZAP can display HTTP messages as described here, and can also **modify** messages
6. Detailed usage is explained in the next chapter (Chapter 3)

This concludes the practice environment installation.

---

## 2.8 Web Mail Verification (Webメールの確認)

### Roundcube Webmail

- The book covers scenarios involving sending/receiving emails during vulnerability testing
- **Roundcube** webmail software is included in the practice environment
- Access Roundcube from the practice environment top page by clicking "Web Mail (Roundcube)"

### Logging In

- **Roundcube URL:** accessible from the practice environment top page
- **Username:** wasbook
- **Password:** wasbook
- Click "Login" button

### Using Roundcube

- Double-click a message subject to view the full email details
- Used throughout the book to verify email-related vulnerabilities

---

## Reference: Virtual Machine Data List (参考：仮想マシンのデータリスト)

### Pre-configured Linux Accounts

| ID | Password | Description |
|----|----------|-------------|
| root | wasbook | Linux root user |
| wasbook | wasbook | Application administrator |
| alice | wasbook | Email sender |
| bob | wasbook | Email recipient |
| carol | wasbook | Other |

### MariaDB Accounts

| ID | Password | Purpose |
|----|----------|---------|
| root | wasbook | Database administrator |
| wasbook | wasbook | Vulnerability sample application |
| todo | wasbook | Sample app "Bad Todo" |

### Installed Software

| Service | Software | Version |
|---------|----------|---------|
| OS (Linux) | Debian | 9 |
| Web Server | Apache | 2.4.25 |
| Reverse Proxy | nginx | 1.10.3 |
| PHP | PHP | 5.3.3 / 7.0.27 |
| Database | MariaDB | 10.1.26 |
| Mail Storage Server | Postfix | 3.1.8 |
| POP3/IMAP4 | Dovecot | 2.2.27 |
| Servlet Container | Tomcat | 8.5.14 |
| Web Mail | Roundcube | 1.2.3 |
| DB Management Tool | phpMyAdmin | 4.6.6 |
| XML Processing Library | Libxml2 | 2.7.8 / 2.9.4 |

### Apache Root Directory

```
/var/www/html
```

---

## Key Takeaways

1. **Isolated Environment:** The entire practice environment runs inside a VirtualBox VM, keeping vulnerability experiments safely isolated from the real network.

2. **Host-Only Networking:** The VM uses a host-only network adapter (192.168.56.1/24) so only the host machine can communicate with the vulnerable applications -- no exposure to the real network.

3. **DNS via hosts File:** Custom hostnames (`example.jp`, `api.example.net`, `trap.example.com`) are mapped to the VM's IP (192.168.56.101) via the hosts file to simulate real-world multi-domain scenarios.

4. **Proxy-Based Interception:** OWASP ZAP acts as a local proxy to intercept, inspect, and modify HTTP traffic. FoxyProxy ensures only practice-related traffic goes through the proxy.

5. **Complete Stack:** The VM includes a full web application stack (Linux, nginx, Apache, PHP, MariaDB, Tomcat, Postfix, Roundcube) to simulate realistic web application environments.

6. **Multiple User Accounts:** Pre-configured Linux and database accounts (root, wasbook, alice, bob, carol) support multi-user vulnerability testing scenarios (e.g., email interception, privilege escalation).

7. **Do Not Use Sample Code in Production:** The sample programs intentionally contain vulnerabilities for learning purposes. Never incorporate them into real software or products.

8. **Two PHP Versions:** Both PHP 5.3.3 and PHP 7.0.27 are available, allowing comparison of vulnerabilities across PHP versions.

9. **Port Configuration:** OWASP ZAP defaults to port 8080 but can be changed (e.g., to 50080) if another service occupies that port. The FoxyProxy configuration file from the practice environment uses port 50080.

10. **Session Logging:** OWASP ZAP sessions should be saved with timestamp-based filenames for organized record-keeping during practice.
