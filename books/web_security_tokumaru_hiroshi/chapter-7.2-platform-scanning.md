# Chapter 7.4-7.5: Platform Scanning with Nmap and OpenVAS

> From "Safe Web Application Development" (安全なWebアプリケーションの作り方) by Tokumaru Hiroshi

---

## 7.4 Port Scanning with Nmap

### Launching Nmap

When you launch Nmap (Windows version), the GUI frontend called **Zenmap** is displayed.

### Performing a Port Scan

Steps to run a port scan with Nmap:

1. **Enter target IP address or hostname** in the target field (e.g., `example.jp`)
2. **Select a scanning profile**: Choose the appropriate scan profile. For a quick exploration of open ports, use the default. For a thorough scan, select "Intense scan, all TCP ports"
3. **Adjust command-line options** if needed in the command bar
4. Click **Scan** to begin

The default scan profile uses "Intense scan" which scans the commonly used top 1000 ports. For the practice environment, select "Intense scan, all TCP ports" to discover all open ports. Note that "Intense scan" may miss some ports that are found only with the full TCP port range.

When the scan starts, progress is shown in the "Nmap Output" tab. When "Nmap done" appears along with the elapsed time, the scan is complete. Select the "Ports/Hosts" tab to view results.

### Reading Nmap Results (Figure 7-11)

The port/host results table shows:

| Column | Description |
|---|---|
| Port | Port number and protocol (e.g., 21/TCP) |
| State | open, closed, filtered |
| Service | Detected service name (e.g., ftp, http) |
| Version | Detected software and version |

**Example results interpretation:**
- The top row shows port `21/TCP` is open, with service `vsftpd 3.0.3` -- this means an FTP server is running

### Interpreting Results -- Manual Judgment Required

Port scanning shows the current state "as is" without judging good or bad. Results must be manually evaluated against the site's intended configuration:

**General principles for a web server:**
- Only ports needed for public-facing web services should be open (typically 80/443)
- All other ports should be closed or filtered from external access

**Example findings requiring attention:**

| Port | Issue |
|---|---|
| **21 (FTP)** | FTP should generally not be used at all; it is insecure |
| **88 (Apache)** | Apache's port is accessible externally but is proxied through nginx |
| **3306 (MariaDB)** | Database port should never accept external connections |
| **8080 (Tomcat)** | Tomcat port is accessible externally but should be proxied through nginx |

Beyond these, ports like 22 (SSH), 25 (SMTP), 110 (POP3), 143 (IMAP) should be checked to see if the server should accept external connections or if a firewall is blocking access.

To verify port accessibility through a firewall, scan from outside the firewall (e.g., from an external network). Port scanning from a standalone server versus from an internal network serves different purposes and should be chosen based on the assessment goal.

### COLUMN: Software Versions Detectable from Outside Are Vulnerable

Figure 7-11 shows the software version information detected. This reveals which software is being used on the web site and its version, which can be determined from the outside.

From a security perspective, this makes it easy for attackers to check whether a site is running a vulnerable version. Especially for major Linux distributions (RHEL, CentOS, Ubuntu, Debian), package versions can be precisely identified. Attackers can use tools to systematically scan for vulnerable versions at scale, which is why large-scale attacks exploiting known vulnerabilities occur.

**Real-world example:** A certain website using PHP 5.3.3 was targeted. Attackers specifically searched for this PHP version, found sites using it, and exploited known vulnerabilities to deface them. Specialized groups may also target specific frameworks or applications.

This is why software should be promptly updated and patches applied. Keeping versions current is a critical defense measure.

---

## 7.5 Platform Vulnerability Assessment with OpenVAS

### Using OpenVAS

1. **Start the virtual machine** containing OpenVAS
2. **Connect via Firefox** to the IP address on port 4000:
   ```
   http://192.168.56.102:4000/login/login.html
   ```
3. **Login credentials:**
   - Username: `admin`
   - Password: `wasbook`
4. Click the **Login** button

### Handling Login Issues

If the following message appears at login, the service is still starting up -- wait 2-3 minutes and try again:
```
Login failed. Waiting for OMP service to become available.
```

### OpenVAS Dashboard (Figure 7-13)

After successful login, the **Dashboard** is displayed showing an overview of the diagnostic status with charts and graphs.

### Starting a Scan

1. Click the **magic wand icon** at the top left of the screen
2. From the dropdown menu, select **Task Wizard** (Figure 7-15)
3. The **Task Wizard** dialog appears (Figure 7-16):
   - Enter the target IP address or hostname (e.g., `example.jp`)
   - Click **Start Scan** at the bottom right
4. The scan begins and progress is shown in the **Status** column

### Monitoring Scan Progress (Figure 7-17)

The Tasks view shows:
- Task name
- Status (Running, Done, etc.)
- Progress percentage
- Reports count

When the Status shows **Done**, the scan is complete (Figure 7-18).

### Viewing Results

From the Reports column, click the **Last** date link to view the scan results.

### OpenVAS Scan Results (Figure 7-19)

The results page shows "Report: Results (10 of 308)" with a table listing:

| Column | Description |
|---|---|
| Vulnerability | Name of the detected vulnerability |
| Severity | CVSS score |
| QoD | Quality of Detection percentage |
| Host | Target IP address |
| Location | Port/protocol |
| Actions | Available actions |

### Examining Vulnerability Details (Figure 7-20)

Click on a specific vulnerability (e.g., "PHP-CGI based setups vulnerability...") to see detailed information:

- **Summary**: Brief description of the vulnerability
- **Vulnerability Detection Result**: Specific findings
- **Impact**: Potential consequences
- **Solution**: Recommended fix (e.g., update to a specific version)
- **Vulnerability Insight**: Technical explanation of the vulnerability
- **References**: CVE numbers and related links

**Example finding:**
- Severity: 7.5 (High) -- indicates a serious vulnerability with remote code execution potential

### Using CVE References

For those not comfortable reading English, look up the CVE identification numbers shown in the References section and search for Japanese-language explanations:

**Example CVE-related resources:**
- CGI版PHPにリモートからスクリプト実行を許す脆弱性 (CVE-2012-1823):
  `https://blog.tokumaru.org/2012/05/php-cgi-remote-scripting-cve-2012-1823.html`
- JVN (Japan Vulnerability Notes) for detailed Japanese explanations

### Acting on Results

After understanding the findings:

1. **Triage**: Determine if the vulnerability affects your site and its severity (see section 8.1.3 for details)
2. **Create a remediation plan** and begin countermeasure implementation
3. **Download reports**: Use the dropdown at the top of the results to select format (HTML, PDF, XML, CSV, etc.) and click "Download Filtered Report" (Figure 7-21)

### Report Download Formats

Reports can be downloaded in multiple formats:

| Format | Use Case |
|---|---|
| HTML | Web-based viewing |
| PDF | Documentation/sharing |
| XML | Machine-readable processing |
| CSV | Spreadsheet import |

Select the desired format from the dropdown list and click the download icon.

---

## Key Takeaways

### Nmap Best Practices
- Always use "Intense scan, all TCP ports" for thorough assessment
- Results require manual interpretation against intended server configuration
- Only necessary public-facing ports should be open
- Software version exposure increases attack surface

### OpenVAS Best Practices
- Use the Task Wizard for quick scans
- Review each finding's severity (CVSS) score to prioritize remediation
- Cross-reference CVE numbers for detailed vulnerability information
- Download reports in appropriate formats for documentation
- Remember: automated scanning by itself is insufficient; combine with manual assessment

### Port Security Quick Reference

| Port | Service | Should Be Open Externally? |
|---|---|---|
| 22 | SSH | Only if needed, restrict by IP |
| 21 | FTP | No -- use SFTP/SCP instead |
| 80 | HTTP | Yes (redirect to HTTPS) |
| 443 | HTTPS | Yes |
| 3306 | MySQL/MariaDB | No -- internal only |
| 8080 | Tomcat/App Server | No -- proxy through nginx/Apache |
| 25 | SMTP | Only for mail servers |
