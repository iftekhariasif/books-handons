# Chapter 2: Practice Environment Setup (Docker Version)

This is the Docker-based alternative to the book's VirtualBox setup. We use **DVWA** (Damn Vulnerable Web Application) instead of the book's `wasbook.ova` VM.

---

## Prerequisites

- **Docker Desktop** — [Download here](https://www.docker.com/products/docker-desktop/)
- **Firefox** — [Download here](https://www.mozilla.org/firefox/new/)

Verify Docker is installed:

```bash
docker --version
docker compose version
```

---

## Step 1: Start the Practice Environment

From this directory, run:

```bash
docker compose up -d
```

This starts two containers:
- **dvwa** — the vulnerable web app on port `8080`
- **dvwa-db** — MariaDB database backend

Check they're running:

```bash
docker compose ps
```

---

## Step 2: Set Up DVWA

1. Open Firefox and go to **http://localhost:8080**
2. Login with:
   - **Username:** `admin`
   - **Password:** `password`
3. You'll see the DVWA setup page — click **"Create / Reset Database"** at the bottom
4. After the database is created, log in again with the same credentials
5. Go to **DVWA Security** in the left menu and set security level to **"Low"** to start (you'll increase it as you learn)

---

## Step 3: Edit `/etc/hosts`

Add practice domain names so they point to your local machine.

```bash
sudo nano /etc/hosts
```

Add this line at the bottom:

```
127.0.0.1    example.jp    api.example.net    trap.example.com
```

Save with `Ctrl+X`, then `Y`, then `Enter`.

Verify it works:

```bash
ping -c 3 example.jp
```

You should see replies from `127.0.0.1`.

---

## Step 4: Install OWASP ZAP

1. Download OWASP ZAP from: **https://www.zaproxy.org/download/**
2. Install the `.dmg` (macOS) or appropriate installer for your OS
3. On first launch, macOS may block it — go to **System Settings > Privacy & Security** and click **"Open Anyway"**

### Configure OWASP ZAP

1. On the session dialog, select **"Save with current timestamp"** and click **Start**
2. Go to **Tools > Options**
3. In the left pane, select **Local Proxies**:
   - **Address:** `localhost`
   - **Port:** `8081` (we use 8081 to avoid conflict with DVWA on 8080)
4. In the left pane, select **Breakpoints**:
   - Set to **"Request and Response"**
5. Click **OK**

---

## Step 5: Install FoxyProxy in Firefox

1. Open Firefox and go to: **https://addons.mozilla.org/firefox/addon/foxyproxy-standard/**
2. Click **"Add to Firefox"** → **"Add"**

### Import Configuration

1. Click the FoxyProxy icon in Firefox toolbar
2. Select **"Options"**
3. Click the **Import** button
4. Select the `foxyproxy.json` file from this directory
5. Confirm the import

### Verify FoxyProxy Settings

After import, you should see:
- **Mode:** "Use Enabled Proxies by Patterns and Priority"
- **OWASP ZAP** proxy entry pointing to `127.0.0.1:8081`
- Patterns matching `*.example.jp/*`, `*.example.net/*`, `*.example.com/*`, and `*localhost:8080*`

---

## Step 6: Verify Everything Works

1. Make sure OWASP ZAP is running
2. Make sure Docker containers are running (`docker compose ps`)
3. Open Firefox and go to **http://localhost:8080**
4. In OWASP ZAP, you should see the HTTP requests appearing in the bottom panel
5. Click on a request to see:
   - **Request** tab — the HTTP request your browser sent
   - **Response** tab — what the server sent back

If you see requests in ZAP, your setup is complete.

---

## Step 7: Shutdown

When you're done practicing:

```bash
docker compose down
```

To also delete the database data:

```bash
docker compose down -v
```

---

## Quick Reference

### DVWA Login

| Field | Value |
|-------|-------|
| URL | http://localhost:8080 |
| Username | admin |
| Password | password |

### DVWA Security Levels

| Level | Description |
|-------|-------------|
| Low | No security — easiest to exploit, start here |
| Medium | Some protections — learn to bypass them |
| High | Strong protections — advanced techniques needed |
| Impossible | Secure code — study this to learn proper defense |

### DVWA Vulnerability Modules

| Module | Book Equivalent |
|--------|----------------|
| SQL Injection | Chapter 4.4.1 |
| SQL Injection (Blind) | Chapter 4.4.1 |
| XSS (Reflected) | Chapter 4.3.1 |
| XSS (Stored) | Chapter 4.3.2 |
| XSS (DOM) | Chapter 4.17.1 |
| CSRF | Chapter 4.5.1 |
| Command Injection | Chapter 4.11.1 |
| File Inclusion | Chapter 4.10.1 |
| File Upload | Related to Chapter 4 |
| Brute Force | Chapter 5.1 |

### Ports

| Service | Port |
|---------|------|
| DVWA | 8080 |
| OWASP ZAP Proxy | 8081 |

### Useful Commands

```bash
# Start environment
docker compose up -d

# Check status
docker compose ps

# View logs
docker compose logs -f dvwa

# Stop environment
docker compose down

# Stop and delete all data
docker compose down -v
```
