# Chapter 8 - Improving Web Site Security

> From "Safe Web Application Development" by Tokumaru Hiroshi

This chapter covers security measures beyond the application layer, addressing infrastructure-level vulnerabilities. It explains overall attack vectors against web sites, then covers countermeasures for each category: attacks on web servers, impersonation/spoofing, eavesdropping and tampering, and malware.

---

## 8.1 Attack Vectors and Countermeasures for Web Servers

### Overview of External Attacks on Web Sites

The following diagram (Figure 8-1) illustrates the full picture of external attacks against web sites:

| Attack Vector | Description |
|---|---|
| **Infrastructure software vulnerabilities** | Exploiting bugs in web servers, OS, middleware |
| **Unauthorized login** | Brute-force or credential-based attacks on admin interfaces |
| **Impersonation (spoofing)** | DNS attacks, ARP spoofing to redirect users to fake sites |
| **Phishing** | Social engineering via fake emails/sites |
| **Eavesdropping / Tampering** | Intercepting or modifying network traffic |
| **Malware** | Infecting servers or users via the web site |

The attack categories covered in this chapter:
- Attacks on web servers
- Impersonation/spoofing
- Eavesdropping and tampering
- Malware

### 8.1.1 Exploiting Infrastructure Software Vulnerabilities

Attacks that exploit vulnerabilities in base software (web servers, etc.) can lead to:
- **Unauthorized access** to the server
- **Cross-site scripting (XSS)** vulnerabilities in the web server itself
- **Denial of service** attacks exploiting software bugs

Vulnerabilities in related infrastructure (routers, firewalls, load balancers, network equipment) can also be exploited to alter device settings, allow unauthorized entry, or enable other attacks.

**Impact of infrastructure vulnerabilities:** Site defacement, information leakage, service disruption, hijacking to attack other servers, and more.

### 8.1.2 Unauthorized Login

Management software used for web servers includes:
- Telnet servers, FTP servers, SSH servers
- phpMyAdmin, Tomcat management console

Attackers frequently target password-based attacks on these services. Attack methods include:
- **Port scanning** to discover active ports and running services
- Checking if management software is enabled, then attempting dictionary attacks or brute-force password cracking

With cloud services, if the cloud control panel is compromised via phishing or unauthorized login, this can have significant impact. If admin passwords are stolen, the consequences include site defacement, information leakage, and more.

### 8.1.3 Countermeasures

Key countermeasures for web server attacks:

#### 1. Choose the Appropriate Server Infrastructure

| Security Aspect | IaaS / VPS / Dedicated Server | PaaS / Rental Server | SaaS |
|---|---|---|---|
| Platform patching | User responsibility | Provider responsibility | Provider responsibility |
| Application vulnerability response | User responsibility | User responsibility | Provider responsibility |
| Password management | User responsibility | User responsibility | Provider responsibility |

- For **IaaS/VPS**: Users handle all security measures
- For **PaaS/SaaS**: Provider handles platform-level security; users handle application-level concerns
- Choose based on your team's security capabilities and resource availability

#### 2. Do Not Run Unnecessary Software

- Services not needed for operation should not be running on the web server
- Each unnecessary service increases the attack surface
- Reduces the number of components needing vulnerability management

#### 3. Apply Vulnerability Patches in a Timely Manner

**At design time, confirm and decide:**
- Support lifecycle of the software
- Patch application method
- Whether operation can continue during patching

**When applying patches, follow this process:**
1. Confirm the support status of the software
2. Determine the patch application method
3. Confirm operational continuity during patching

**Patch application methods:**
- Full version upgrade (fresh install)
- Source-level patching and recompilation (make)
- Package management systems (APT, Yum, DNF, etc.)
- OS update tools (Windows Update, WSUS, etc.)

**PHP Support Lifecycle Policy (Figure 8-2):**
- Each PHP version receives approximately 2 years of active support followed by 1 year of security-only fixes (total ~3 years)
- After end-of-life, no more security patches are provided
- FLOSS (Free/Libre Open Source Software) support lifecycles vary; check each project's policy

**Example: Applying updates on Linux (Debian-based):**
```bash
sudo apt update
sudo apt upgrade
```
For Fedora/CentOS/Red Hat, use `yum` or `dnf` instead.

#### 4. Monitor Vulnerability Information

Regularly check vulnerability databases:
- **JVN (Japan Vulnerability Notes):** http://jvn.jp/
- **JVN iPedia:** http://jvndb.jvn.jp/

Both provide RSS feeds for timely vulnerability notifications.

#### 5. When a Vulnerability is Found, Assess Impact and Create a Response Plan

Steps when a vulnerability is identified:
1. Confirm whether the affected software is in use
2. Assess the impact of the vulnerability and necessity of response
3. Decide on a remediation method
4. Create a detailed execution plan

The execution plan should include:
- Detailed work items
- Detailed confirmation methods
- Work procedures and checklists

**Response options (in order of priority):**
- Apply a security patch or update (fundamental fix)
- Fix by upgrading to a patched version (fundamental fix)
- Implement workarounds (temporary measures)

#### 6. Execute Vulnerability Response

- Follow the plan and execute
- After patching, verify the application's operation
- Record all work in change management logs
- Verify system configuration and settings remain correct

#### 7. Restrict Access to Unnecessary Ports and Services

Services needed only for management (SSH, FTP, etc.) should not be publicly accessible:

| Method | Description |
|---|---|
| **VPN** | Access from external networks only through a dedicated VPN connection |
| **IP address restriction** | Allow connections only from specific IP addresses |
| **Router/Firewall rules** | Restrict at the network entry point using router or firewall configuration |
| **OS firewall** | Use Windows Firewall, iptables, firewalld, etc. |
| **Application-level restrictions** | Software's own access control features |

#### 8. Verify Access Restrictions with Port Scanning

- Use tools like **Nmap** to scan and verify which ports are accessible from outside
- Compare results against intended configuration
- Web sites should only expose necessary ports (typically 80 and 443)
- Run port scans both before going live and periodically thereafter

#### 9. Hide Software Version Information

Configure servers to not disclose version information:

**Apache (httpd.conf):**
```
ServerTokens ProductOnly
ServerSignature Off
```

**nginx (nginx.conf, inside http block):**
```
server_tokens off;
```

**PHP (php.ini):**
```
expose_php = Off
```

#### 10. Strengthen Authentication

- **Remove or disable Telnet and FTP servers**; use SSH-based services only
- **Disable password authentication for SSH**; use public key authentication only
- **Cloud service administrator accounts**: assign per person, enable two-factor authentication where possible

**Important notes on Telnet and FTP:**
- Telnet and FTP transmit credentials in plaintext, making them inherently insecure
- Even SSH should use key-based authentication, not password authentication
- Cloud administrator accounts compromised via phishing can lead to major incidents; always enable two-factor authentication

---

## 8.2 Impersonation / Spoofing Countermeasures

Impersonation threats involve attackers making users believe they are interacting with a legitimate site, when in reality they are directed to a fake one. This can lead to site defacement, information theft, and credential harvesting.

### 8.2.1 Network-Level Spoofing Techniques

#### DNS Attacks

Specific DNS attack methods include:
- **Attacks on registrars/registries** that manage domain names
- **Attacks on DNS servers** to modify DNS configuration
- **DNS cache poisoning** attacks

**Normal DNS Resolution (Figure 8-4):**
- User queries `www.example.jp` -> authoritative DNS server responds with the correct IP (e.g., 203.0.113.2)
- The JP domain's authoritative DNS servers (e.g., `a.dns.jp`) delegate to `ns.example.jp`

**Domain Name Hijacking (Figure 8-5):**
- Attacker compromises the registrar management console (e.g., via stolen credentials)
- Changes the authoritative DNS server records for `example.jp` to point to attacker-controlled DNS (`evil.example.com`)
- Users are then directed to the attacker's server, enabling phishing, malware distribution, and data theft

**DNS Cache Poisoning:**
- Attacker sends forged responses to a DNS cache server, racing against legitimate responses
- If the forged response arrives first, the cache stores the attacker's IP address
- Users connecting through that cache server are directed to the attacker's server

**DNS operation hardening references:**
- IPA: Domain registration and DNS server configuration guidelines
- DNS cache poisoning countermeasures documentation
- DNS server vulnerability notes
- Consider DNSSEC deployment

> **Column: VISA Domain Issue**
> An example of domain trust issues: VISA.CO.JP was found to be registered by an unrelated entity (E-ONTAP.COM), illustrating how domain management negligence can undermine trust. Organizations should ensure their domains are properly registered and managed.

#### ARP Spoofing

- **ARP (Address Resolution Protocol)** maps IP addresses to MAC addresses on local networks
- Attackers send forged ARP responses, associating their MAC address with the target's IP
- This allows the attacker to intercept traffic on the same network segment (man-in-the-middle)
- **Notable incident (2008):** A major hosting data center experienced ARP spoofing where a compromised server on the same LAN segment injected iframes into other servers' traffic, distributing malware to visitors

### 8.2.2 Phishing

- Phishing uses carefully crafted fake sites and emails to trick users into entering credentials or personal information
- Attackers create sites that closely mimic legitimate ones
- Phishing is a social engineering technique, and incidents continue to increase
- Phishing sites may appear at legitimate-looking SNS or temporary domains

**Key point:** Phishing is fundamentally different from technical site compromise; it relies on deception. However, there are technical countermeasures web site operators can take.

### 8.2.3 Web Site Anti-Spoofing Countermeasures

Effective countermeasures against impersonation:
- Network-level countermeasures
- TLS deployment
- Use of easily verifiable domain names

#### Network-Level Countermeasures

##### Do Not Place Critical Servers on Shared Network Segments

- ARP spoofing attacks affect servers on the same network segment
- Avoid placing important servers where other potentially compromised machines share the segment
- For hosting/rental servers, check with the provider about ARP spoofing protection measures

##### Strengthen DNS Operations

- Follow IPA and industry guidelines for DNS server configuration
- Implement DNS cache poisoning countermeasures
- DNS cache poisoning is a web site-side issue, not solely a user-side concern; the web site operator should take countermeasures

#### TLS Deployment

TLS (Transport Layer Security) is a powerful countermeasure against spoofing:
- Provides **encrypted communication** (confidentiality)
- Uses **third-party CA (Certificate Authority)** to verify domain authenticity

**How TLS counters spoofing:**
- Purchase and install a legitimate server certificate from a CA
- The CA verifies the domain's legitimacy
- If the server is spoofed, the browser displays certificate warnings (Figure 8-6), alerting users

#### Types of Server Certificates

| Certificate Type | Verification Level | Cost | Use Case |
|---|---|---|---|
| **Domain Validation (DV)** | Domain ownership only | Low (free options available) | Basic encryption, small sites |
| **Organization Validation (OV)** | Domain + organization identity | Medium | Business sites |
| **Extended Validation (EV SSL)** | Domain + organization + rigorous vetting | High | Financial, e-commerce, high-trust sites |

**EV SSL certificates:**
- Verify organizational existence through CA/Browser Forum guidelines
- When used, the browser may display the organization name in the address bar (Figure 8-7 shows IPA's EV SSL example)
- Provides stronger assurance against spoofing by confirming organizational identity

> **Column: Free Server Certificates**
> Domain validation certificates are relatively inexpensive and some are free. Notably, **Let's Encrypt** (by Internet Security Research Group / ISRG) provides free server certificates through simple command-line operations, making TLS accessible even in Japan. For sites where certificate cost is a barrier, or where self-signed certificates are currently in use, consider adopting free DV certificates as a minimum.

#### Use Easily Verifiable Domain Names

For phishing countermeasures, using recognizable, verifiable domain names is effective. **Attribute-type JP domain names** are recommended:

| Service Category | Domain Name Type |
|---|---|
| Corporate services | CO.JP |
| Government organizations | GO.JP |
| Local government bodies | LG.JP |
| Educational institutions | AC.JP or ED.JP |

**Benefits of attribute-type JP domains:**
- Applications are screened to verify the applicant meets domain acquisition requirements
- Limited to one domain per organization, making them harder to abuse
- More trustworthy than generic domains (.COM, etc.) which are easier to register
- Service operations should use attribute-type JP domain names when possible

---

## 8.3 Eavesdropping and Tampering Countermeasures

This section covers countermeasures against eavesdropping and tampering of web site access, including how to use TLS effectively.

### 8.3.1 Eavesdropping and Tampering Routes

#### Wireless LAN Eavesdropping / Tampering

- Wireless packets without proper encryption can be intercepted
- Causes: (1) no encryption, (2) WEP (which has known weaknesses), (3) shared passwords for public Wi-Fi, (4) rogue access points
- Rogue access points can be set up easily; users may connect unknowingly
- Wireless access point attacks are increasing

#### Mirror Port Exploitation

- On wired LANs, switches with mirror port functionality can be used for eavesdropping
- Involves physically connecting monitoring equipment to replicate network traffic
- Using switches with mirror ports or network hubs/repeaters can enable eavesdropping

#### Proxy Server Exploitation

- Proxy servers can intercept and monitor HTTP messages
- Attackers who set up rogue proxy servers can modify HTTP content
- OWASP ZAP, used legitimately for testing, works on this same principle

#### Fake DHCP Server

- In LAN environments using DHCP, a rogue DHCP server can provide false gateway/DNS settings
- This allows traffic redirection and eavesdropping

#### ARP Spoofing and DNS Cache Poisoning

- As covered in section 8.2, these techniques can also enable eavesdropping and tampering
- ARP spoofing redirects traffic at the network level
- DNS cache poisoning redirects at the name resolution level
- Both enable man-in-the-middle positioning

### 8.3.2 Man-in-the-Middle (MITM) Attacks

A man-in-the-middle attack occurs when an attacker positions themselves between the user and the server, intercepting and potentially modifying communication.

**MITM Attack Diagram (Figure 8-8):**
```
User <--HTTPS--> [Attacker/Proxy] <--HTTPS--> Target Web Site
```

- The attacker intercepts HTTPS requests and re-encrypts them
- When the connection between user and server uses TLS, the middle segment is also TLS
- However, the attacker can decrypt, inspect, and modify the traffic at the proxy point
- The key indicator: **during a MITM attack, the browser shows certificate errors**

#### OWASP ZAP MITM Experiment

The book demonstrates a practical MITM attack using OWASP ZAP:

1. **Configure FoxyProxy** in Firefox to route all traffic through OWASP ZAP (Figure 8-9)
2. Access a site like `https://www.ipa.go.jp`
3. **Browser shows certificate error** (Figure 8-10): "This connection is not secure"
4. Click "Error details" to see the certificate issue (Figure 8-11)
5. Adding a security exception (Figure 8-12) allows viewing the page despite the invalid certificate
6. **OWASP ZAP captures the HTTPS traffic** (Figure 8-14), showing full HTTP request/response details including headers and content
7. This demonstrates that **even TLS/HTTPS traffic can be viewed and modified through a proxy-based MITM attack**

**Key takeaway:** During a MITM attack, the browser's certificate error is the primary defense. Users should never ignore certificate warnings.

#### Installing OWASP ZAP's Root Certificate

To avoid certificate errors during legitimate testing with OWASP ZAP:

1. Export OWASP ZAP's root certificate from **Tools > Options** (Figure 8-15)
2. Select "Dynamic SSL Certificate" and save the root CA certificate
3. In Firefox, go to **Options > Privacy & Security > View Certificates** (Figure 8-16)
4. Open the **Certificate Manager** (Figure 8-17), which shows trusted CAs
5. Click **Import** and select the OWASP ZAP certificate file (Figure 8-18)
6. Select "Trust this CA to identify websites" and confirm
7. After import, the site loads without certificate errors (Figure 8-19)
8. The lock icon shows "OWASP Root CA" as the certificate authority (Figure 8-20)

> **Column: Never Install Root Certificates Carelessly**
> Installing a root certificate in the browser means that errors will no longer be shown for any site using certificates signed by that CA. This is dangerous from a security perspective -- it essentially disables MITM detection for those certificates. TLS verifies domain identity through the certificate chain; installing untrusted root CAs undermines this. Only install root certificates for legitimate testing purposes, on isolated test machines, and never on production systems. Private CAs for internal use are acceptable but require careful management (PKI).

### 8.3.3 Countermeasures

To protect against eavesdropping and tampering:
- Deploy **legitimate certificates** from trusted CAs
- Use **TLS** for all sensitive communications
- Pay attention to the following additional considerations:

#### TLS Usage Best Practices

| Practice | Rationale |
|---|---|
| **Serve input forms over HTTPS** | The form page itself must be HTTPS so users can verify encryption before entering data |
| **Set Secure attribute on cookies** | Prevents cookies from being sent over unencrypted HTTP (see section 4.8.2) |
| **Serve images and CSS/JS over HTTPS** | Mixed content (HTTP resources on HTTPS pages) can be tampered with; JavaScript loaded over HTTP can be modified by attackers |
| **Do not use frames/iframes** | If the outer frame is TLS-protected but the inner content URL is not visually verifiable, it undermines trust; also, frame content can be swapped via MITM |
| **Do not hide the address bar** | Users need to verify HTTPS in the address bar |
| **Do not hide the status bar** | Status bar provides additional security indicators |
| **Do not disable the context menu** | Users should be able to right-click to verify certificate details |

**Mixed Content Warning (Figure 8-21):**
- When HTTP and HTTPS content are mixed, browsers show warnings
- Firefox displays a shield icon; clicking it shows error details
- The browser's default settings should not be changed to suppress SSL errors

**Browser SSL Settings (Figure 8-22):**
- Browser defaults require SSL 3.0+ and show errors for certificate issues
- Do not change these defaults to suppress certificate warnings; install proper certificates instead

> **Column: TLS Verification Seals**
> Certificate vendors provide "trust seals" (site seal icons) for sites to display, indicating that a TLS certificate is in use. However, seals are just images and can be copied, so their actual security value is limited. Some seals link to verification pages, which adds marginal value. The risks of displaying seals (e.g., XSS if seal code is compromised) should be weighed against the minimal trust benefit.

---

## 8.4 Malware Countermeasures

### 8.4.1 What are Web Site Malware Countermeasures?

Web site malware countermeasures have two meanings:

**(A) Prevent the web server from being infected with malware**
**(B) Prevent the web site from distributing malware to users**

Both (A) and (B) involve malware reaching the web server, but:
- **(A)** means malware is actively running on the server
- **(B)** means the site serves malicious content that users can download

**Impact of server malware infection (A):**
- Information leakage
- Site defacement
- Unauthorized operations
- Attacks on other sites (stepping stone)

**Impact of malware distribution via site (B):**
- Users visiting the web site have their PCs infected with malware

### 8.4.2 Malware Infection Routes

Based on IPA (Information-technology Promotion Agency) data from 2017, reported virus infection routes:

**Computer Virus Infection Routes (Figure 8-23):**

| Route | Percentage |
|---|---|
| **Email** | **89.7%** |
| Downloaded files | 0.5% |
| External storage media | 0.0% |
| Network | 9.2% |
| Unknown/Other | 0.7% |

- Email is overwhelmingly the primary infection vector (~90%)
- Network-based infection accounts for ~9%
- For web servers specifically, infections often come through exploiting server vulnerabilities and executing commands (similar to OS command injection)

### 8.4.3 Overview of Web Server Malware Countermeasures

Key countermeasures from the infection route perspective:
- **Apply server vulnerability patches promptly**
- **Do not bring programs of unknown origin onto the server**
- **Avoid unnecessary operations on the server** (web browsing, email, etc.)
- **Do not connect USB or external media to servers**
- **Isolate the web server network** from workstation LANs using proper segmentation
- **Protect connected client PCs** by keeping security patches and antivirus updated
- **Windows Update** and similar tools should be used to keep client PCs current

If these basic countermeasures are insufficient (e.g., vulnerability patches cannot be applied in time), consider deploying server-side antivirus software.

### 8.4.4 Preventing Malware from Being Brought into the Web Server

Routes by which malware reaches web servers:
- Exploiting **file upload functionality** (see section 4.12)
- **Vulnerability exploitation** leading to content tampering (see section 8.1)
- **Unauthorized login** to FTP or management software (see section 8.1)
- Malware-infected **administrator PCs** connecting to the server
- Infection via compromised **legitimate software** (supply chain, see section 8.4.3)

**Countermeasures:** Focus on file upload security. The following methods should be applied based on the site's needs:
- Designate the **upload directory as a scan target**
- Clarify **who is responsible** for uploaded files (site operator, uploader, or viewer)
- Consider **responsibilities** -- the site operator generally bears responsibility for hosted content
- Use **antivirus software** and other methods to scan uploaded content

### Evaluating Whether Malware Countermeasures Are Needed

Consider the following stakeholders for malware scanning:
- **Web site operator**
- **File uploader**
- **File viewer/downloader**

If the site individually checks whether virus scanning is feasible, the following methods exist:
- Scan uploaded files for malware
- Clarify responsibility (who is liable if malware is distributed)
- Note that antivirus scanning alone is not 100% effective

### Establish and Communicate Policies to Users

After assessing the need for malware countermeasures:
- Define **policies** on content handling (whether to allow, scan, or block)
- **Publish** the policies to users
- Implement the policy-defined countermeasures
- Communicate the following to users:
  - File/malware countermeasure methods (note that scans may not catch everything)

### Antivirus Software Countermeasures

Key principles for antivirus deployment:
- **Complete virus scanning of all content is not possible** -- there will always be some malware that evades detection
- **Users should take responsibility** for their own protection (keep antivirus pattern files updated)
- **Site operators should not bear sole responsibility** for virus-free content guarantees

#### Server-Side Antivirus Implementation

For scanning user-uploaded files on the server:
- **Install antivirus software** on the server and designate the upload directory for scanning
- **Use a virus scanning gateway** product for automated scanning
- **Integrate via antivirus API** to build custom scanning workflows

Consult antivirus software vendors or their representatives for implementation details.

**Example: Google Drive (Figure 8-24)**
- Google Drive implements server-side virus scanning
- When an uploaded file is detected as infected, users see a warning: "[filename] is infected with a virus"
- The file is flagged and a cleanup option is offered

---

## 8.5 Summary

This chapter covered the following methods to improve web site security:

| Topic | Key Points |
|---|---|
| **Web server vulnerability countermeasures** | Patch management, unnecessary software removal, access restrictions, port scanning |
| **Management software unauthorized login countermeasures** | Strong authentication, SSH key-based login, two-factor authentication |
| **Impersonation/spoofing countermeasures** | TLS certificates, DNS security, domain verification, EV SSL |
| **Eavesdropping and tampering countermeasures** | HTTPS everywhere, proper TLS configuration, certificate management |
| **Malware countermeasures** | Server hardening, upload scanning, policy communication, antivirus |

These measures are **not alternatives to application-level vulnerability countermeasures** but are **complementary**. Web site security requires addressing both application vulnerabilities and infrastructure-level threats. Use this chapter's content as a reference to comprehensively improve your web site's security posture.

---

## Key Takeaways

1. **Defense in depth**: Application-level security alone is insufficient; infrastructure, network, and operational security are equally important
2. **Patch management is critical**: Establish a process for monitoring vulnerabilities, assessing impact, and applying patches promptly
3. **Minimize attack surface**: Disable unnecessary services, restrict port access, hide version information
4. **TLS is essential**: Deploy TLS with proper certificates; never ignore certificate warnings
5. **Choose appropriate certificates**: DV for basic encryption, OV for business sites, EV for high-trust sites; Let's Encrypt provides free DV certificates
6. **DNS security matters**: Protect domain registration, DNS server configuration, and consider DNSSEC
7. **Network segmentation**: Isolate critical servers from shared segments to prevent ARP spoofing and lateral movement
8. **Malware defense is multi-layered**: Patch servers, scan uploads, isolate networks, keep client PCs updated
9. **Authentication hardening**: Use SSH with key-based auth, disable Telnet/FTP, enable two-factor authentication for cloud consoles
10. **Policy and communication**: Define and publish security policies for users regarding uploaded content and malware responsibilities
