# Chapter 4.15: Race Conditions and Cache-Related Information Leaks

This section covers two categories of vulnerabilities related to shared resources: race conditions arising from concurrent access to shared state, and information leaks caused by improper cache configuration.

---

## 4.15.1 Race Condition Vulnerabilities

### Overview

- **Shared resources** include variables, shared memory, files, databases, etc. that are accessed by multiple processes/threads simultaneously
- Race conditions occur when multiple requests access the same shared resource concurrently, causing unexpected behavior
- In web applications, race conditions can lead to:
  - **Other users' personal information being displayed** (privacy violations)
  - **Database inconsistency**
  - **File corruption**

### Countermeasures Summary

- Avoid shared resources when possible
- Apply proper **exclusive locking** (mutual exclusion) to shared resources

---

## Race Condition Vulnerability Summary

| Item | Details |
|------|---------|
| **Occurs in** | Functions that use shared resources |
| **Affected pages** | Pages that display/modify shared state; applications that modify shared state |
| **Impact** | Display of other users' personal information, database inconsistencies, file corruption, etc. |
| **Impact level** | High |
| **User involvement** | Not required |
| **Countermeasure** | Avoid shared resources; apply exclusive locking |

---

## Attack Methods and Impact

### Scenario: Shared Instance Variable in Java Servlet

**`C4f_001.java`** -- A servlet that stores a query parameter in an instance variable:

```java
import java.io.*;
import javax.servlet.http.*;

public class C4f_001 extends HttpServlet {
  String name;  // Instance variable (shared across requests)

  protected void doGet(HttpServletRequest req,
                       HttpServletResponse res) {
    // ...
    try {
      name = req.getParameter("name");  // Store in instance variable
      Thread.sleep(3000);  // Simulate 3-second processing delay
      out.print(escapeHTML(name));  // Display user's name
    } catch (InterruptedException e) {
      out.println(e);
    }
    // ...
  }
}
```

### The Problem: Interleaving Requests

**Timeline of two concurrent requests:**

| Time | Request 1 (yamada) | Request 2 (tanaka) | Instance variable `name` |
|------|-------------------|-------------------|--------------------------|
| t+0 | `name="yamada"` arrives | | `yamada` |
| t+1 | Processing (sleep 3s)... | `name="tanaka"` arrives | `tanaka` (overwritten!) |
| t+3 | | Processing... | `tanaka` |
| t+4 | Displays `name` -> **"tanaka"** | | `tanaka` |
| t+5 | | Displays `name` -> "tanaka" | `tanaka` |

- User "yamada" sees "tanaka" displayed -- **another user's data is leaked**
- The root cause: `name` is an **instance variable** shared across all requests in the servlet

---

## Root Causes

Two conditions must be met:

1. **The variable `name` is a shared (instance) variable**
2. **No exclusive locking (synchronization) is applied to the shared variable**

- Servlet instances are shared across requests by default, so instance variables are shared
- This is a common mistake, especially for developers unfamiliar with servlet threading

---

## Countermeasures

### 1. Avoid Shared Resources

- Use **local variables** instead of instance variables:

```java
try {
  String name = req.getParameter("name");  // Local variable
  Thread.sleep(3000);
  out.print(escapeHTML(name));  // Safe -- each request has its own copy
} catch (InterruptedException e) {
  out.println(e);
}
```

- Local variables are allocated on the thread's stack and are not shared between requests

### 2. Apply Exclusive Locking

- Use `synchronized` blocks to ensure only one thread accesses the shared resource at a time:

```java
try {
  synchronized(this) {  // Lock the servlet instance
    name = req.getParameter("name");
    Thread.sleep(3000);
    out.print(escapeHTML(name));
  }
} catch (InterruptedException e) {
  out.println(e);
}
```

- `synchronized(this)` locks the servlet instance so that only one thread can execute the block at a time
- **Drawback**: This serializes all requests, significantly degrading performance (e.g., 3 requests x 3 seconds = 9 seconds total)
- This can also become a **DoS vulnerability** -- a small number of requests can exhaust all threads

### Choosing the Right Approach

- **Prefer avoiding shared resources** whenever possible
- Exclusive locking should be used only when shared state is truly necessary
- Design for **minimal lock duration** to reduce performance impact

---

## Summary

- Shared resources (instance variables, shared files, databases) accessed by multiple threads without proper synchronization can cause race conditions
- Exclusive locking (database locks, `synchronized`, file locks) prevents race conditions but hurts performance
- Shared resources should be avoided when possible; use local variables or thread-safe designs
- Reference: Java Servlet's `SingleThreadModel` interface was deprecated (Servlet 2.4+) -- do not use it

### Reference: Other Java Servlet Notes

- Servlet instance variables are shared across requests -- always use local variables instead
- JSP also allows defining instance variables with `<%! ... %>` syntax, which has the same risk

---

## 4.15.2 Cache-Related Information Leaks

### Overview

- Web applications use **caching** at various levels (browser cache, proxy/CDN cache, reverse proxy cache) to improve performance and reduce server load
- If caching is misconfigured, private/personal information can be cached and served to other users
- This leads to **information leakage** of personal data

---

## Cache Information Leak Vulnerability Summary

| Item | Details |
|------|---------|
| **Occurs in** | Sites that use caching and display private information |
| **Affected pages** | Pages displaying private/personal information |
| **Impact** | Display of other users' personal information, etc. |
| **Impact level** | High |
| **User involvement** | Not required |
| **Countermeasure** | Configure cache properly; implement appropriate cache control |

---

## Attack Methods and Impact

### Sample Application: My Page with Caching

A login-based application where each user has a "My Page" that displays their personal information.

**`4f-010.html`** -- Menu page:

```html
<body>
  <a href="4f-011.php?user=tanaka">Login as Tanaka</a><br>
  <a href="4f-011.php?user=yamada">Login as Yamada</a>
</body>
```

**`4f-011.php`** -- Login handler:

```php
<body>
<?php
  $user = $_GET['user'];
  if ($user === 'tanaka' || $user === 'yamada') {
    session_start();
    session_regenerate_id(true);
    $_SESSION['user'] = $user;
    echo 'Logged in as: ' . htmlspecialchars($user) . '<br>';
    echo '<a href="4f-012.php">My Page (no cache)</a><br>';
    echo '<a href="4f-012a.php">My Page (with cache)</a>';
  } else {
    echo 'Invalid user';
  }
?>
</body>
```

**`4f-012.php`** -- My Page (session-based display):

```php
<body>
<?php
  session_start();
  if (empty($_SESSION['user'])) {
    die("Not logged in");
  }
  echo 'User ' . $_SESSION['user'] . ' is logged in';
?>
</body>
```

### Application-Side Cache Misconfiguration

**`4f-012a.php`** -- My Page with aggressive caching enabled:

```php
<body>
<?php
  session_cache_limiter('public');  // Added: allow public caching
  session_cache_expire(1);          // Added: cache expires in 1 minute
  session_start();
  // ... same display logic ...
?>
</body>
```

- `session_cache_limiter('public')` tells the browser and proxies that the page can be cached publicly
- This is dangerous for pages showing personal information

### Demonstration

1. **Tanaka** logs in via Firefox and visits "My Page (with cache)" -- sees "User tanaka is logged in"
2. **Yamada** logs in via Google Chrome and visits "My Page (with cache)" -- sees "User tanaka is logged in" (wrong!)
3. This happens because the cache server (reverse proxy) cached Tanaka's personalized page and served it to Yamada

---

### Cache Server Misconfiguration

The nginx reverse proxy configuration:

```nginx
location /4f3/ {
  proxy_cache zone1;
  proxy_cache_valid 200 302 1h;
  proxy_ignore_headers Cache-Control Expires Set-Cookie;
  proxy_set_header Host $host;
  proxy_pass http://localhost:88/4f/;
}
```

- `proxy_ignore_headers Cache-Control Expires Set-Cookie` -- nginx **ignores** the application's cache control headers
- This means even when the application says "do not cache" or "this response has Set-Cookie", nginx caches it anyway
- Result: Personal information from one user's session is cached and served to other users

---

## Root Causes

Cache-related information leakage occurs due to:

1. **Application-side cache control misconfiguration** -- using `public` caching for personalized content
2. **Cache server misconfiguration** -- ignoring application cache headers

---

## Countermeasures

### Application-Side: Set Proper Cache Control Headers

- For pages with personal/private content, set appropriate Cache-Control headers:

```
Cache-Control: private, no-store, no-cache, must-revalidate
Pragma: no-cache
```

- PHP's `session_cache_limiter` controls this. Use `'nocache'` (the default) for session-protected pages:

```php
session_cache_limiter('nocache');
```

- This generates response headers:

```
Expires: Thu, 19 Nov 1981 08:52:00 GMT
Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0
Pragma: no-cache
```

### Cache-Control Directives Reference

| Directive | Meaning |
|-----------|---------|
| `no-store` | Do not cache at all |
| `no-cache` | Cache but always revalidate with the server |
| `private` | Only browser can cache (not proxies/CDNs) |
| `public` | All caches (browser + proxy) may cache |
| `must-revalidate` | Cache must revalidate when stale |
| `max-age` | How long (in seconds) the cache is considered fresh |

### Cache Server-Side: Proper Configuration

- Do **not** configure cache servers to ignore application cache headers (`Cache-Control`, `Expires`, `Set-Cookie`)
- Ensure `no-store` is respected by all caching layers
- For CDN and reverse proxy configurations, always respect `private` directives

### Both Sides Working Together

The recommended approach is two-fold:

1. **Application side**: Set appropriate cache control response headers for personalized content
2. **Cache server side**: Respect those headers and configure appropriate cache policies

- Cache infrastructure (reverse proxies, CDNs, load balancers) must be configured by infrastructure teams working together with application developers
- The `Pragma: no-cache` header is a legacy HTTP/1.0 compatibility header -- while not strictly required, it is good practice to include for older software

---

## Key Takeaways

- **Race conditions**: Shared mutable state across request threads is the root cause. Use local variables instead of instance variables. If shared state is unavoidable, use proper synchronization, but be aware of the performance trade-offs
- **Cache leaks**: Never cache personalized/authenticated content publicly. Always set `Cache-Control: private, no-store, no-cache` for pages that display user-specific data. Ensure cache servers (nginx, CDN, etc.) respect application cache headers
- Both categories share the theme of **shared resources causing unintended data exposure between users**
