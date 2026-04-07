# Practice Environment Setup — Memo

## Chapter 2: Book vs Our Setup

| Step | Book (VirtualBox) | Our Setup (Docker) | Purpose |
|------|---|---|---|
| 1. Browser | Firefox | Firefox | Browser to access vulnerable web apps and test attacks |
| 2. Virtualization | VirtualBox (VM) | Docker Desktop (containers) | Run isolated server environment on your Mac |
| 3. Vulnerable App | wasbook.ova (custom VM with Debian, Apache, PHP, MariaDB, Postfix, Roundcube) | DVWA via `docker compose up -d` | Deliberately vulnerable web app to practice attacks against safely |
| 4. Network Config | Host-only adapter, VM IP: 192.168.56.101 | Docker networking, access via localhost:8080 | Connect your browser to the vulnerable app without exposing it to the internet |
| 5. Hosts File | Map `example.jp`, `api.example.net`, `trap.example.com` → 192.168.56.101 | Map same hostnames → 127.0.0.1 | Simulate real domain names for multi-domain attack scenarios (e.g., cross-site attacks) |
| 6. Proxy Tool | OWASP ZAP on port 8080 | OWASP ZAP on port 8081 (8080 taken by DVWA) | Intercept, inspect, and modify HTTP traffic between browser and server |
| 7. Proxy Routing | FoxyProxy with book's foxyproxy.json | FoxyProxy configured manually | Route only practice traffic through ZAP, keep normal browsing untouched |
| 8. Firefox Config | Not mentioned in book | `network.proxy.allow_hijacking_localhost` = true | Firefox skips proxy for localhost by default — this override was needed for our Docker setup |

## Key Differences

- **Book uses a full VM** (VirtualBox) — heavier, slower, but matches book screenshots exactly
- **We use Docker** — lighter, faster startup, works on Apple Silicon (M-series Macs)
- **Book's vulnerable app** (wasbook/Bad Todo) is different from DVWA — but both cover the same vulnerability types
- **Port changed** from 8080 to 8081 for ZAP because DVWA already uses port 8080

## Credentials

| Service | Username | Password |
|---------|----------|----------|
| DVWA | admin | password |

## Quick Commands

```bash
# Start practice environment
docker compose up -d

# Stop practice environment
docker compose down

# Stop and delete all data
docker compose down -v
```

## What's Coming Next

| Chapter | Attack | What you'll do in DVWA |
|---------|--------|------------------------|
| 4.3 | XSS (Cross-Site Scripting) | Inject JavaScript into a page to steal cookies |
| 4.4 | SQL Injection | Bypass login or dump database with malicious SQL |
| 4.5 | CSRF | Trick a logged-in user into performing unwanted actions |
| 4.11 | Command Injection | Execute OS commands through a web form |
| 4.10 | File Inclusion | Access files on the server you shouldn't see |
| 5.1 | Brute Force | Crack passwords by trying many combinations |
