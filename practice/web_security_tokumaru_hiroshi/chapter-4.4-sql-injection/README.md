# Chapter 4.4: SQL Injection -- Hands-On Practice

**Book Reference:** Chapter 4.4 of *Safe Web Application Development* by Tokumaru Hiroshi

---

## Prerequisites

Before starting, make sure the following are ready:

- **Docker running** -- `docker compose up -d` from the `chapter-2` folder
- **DVWA logged in** -- credentials: `admin` / `password`
- **OWASP ZAP running** on port `8081`
- **FoxyProxy active** -- browser traffic routed through ZAP

---

## What You'll Learn

- SQL injection detection techniques
- UNION-based data extraction
- Authentication bypass concepts
- Blind SQL injection (boolean-based)
- Prepared statements as the primary defense

---

## DVWA Modules Covered

| Module | Path |
|---|---|
| SQL Injection | `/vulnerabilities/sqli/` |
| SQL Injection (Blind) | `/vulnerabilities/sqli_blind/` |

---

## Exercises

### Low Level -- SQL Injection

Go to **DVWA > SQL Injection** and set the security level to **Low**.

#### Step 1: Normal Input

Enter `1` in the User ID field and submit.

You should see a normal result displaying the user's First name and Surname. This confirms the feature works and returns data from the database.

#### Step 2: Authentication Bypass (OR-based)

Enter the following payload:

```
1' OR '1'='1
```

This dumps **all users** in the table. The injected `OR '1'='1'` condition is always true, so every row is returned. This is the foundation of authentication bypass -- if a login query uses this pattern, any user can log in without a valid password.

#### Step 3: UNION-based Extraction

Enter the following payload:

```
1' UNION SELECT user, password FROM users#
```

This extracts **all usernames and their password hashes** from the `users` table. The `UNION SELECT` merges our malicious query results with the original query output. The `#` comments out the rest of the original SQL statement.

#### Step 4: Error-based Extraction

Enter the following payload:

```
1' AND EXTRACTVALUE(0,CONCAT('$',(SELECT database())))#
```

This forces MySQL to return an error message that **reveals the database name**. The error output includes the result of `SELECT database()` embedded in the error string.

#### Step 5: Column Count Discovery

Try the following payloads one by one:

```
1' ORDER BY 1#
1' ORDER BY 2#
1' ORDER BY 3#
```

`ORDER BY 1` and `ORDER BY 2` should succeed. `ORDER BY 3` should produce an error. This tells you the original query returns **2 columns** -- information you need before constructing a valid UNION attack.

---

### Low Level -- Blind SQL Injection

Go to **DVWA > SQL Injection (Blind)** and keep the security level at **Low**.

#### Step 1: Normal Input

Enter `1` and submit.

You should see: **"User ID exists in the database."**

Note that unlike the regular SQL Injection page, this one does NOT display the actual data. It only tells you whether the user exists or not.

#### Step 2: True Condition

Enter the following payload:

```
1' AND 1=1#
```

Result: **"User ID exists in the database."** -- the condition `1=1` is true, so the original query still returns a result.

#### Step 3: False Condition

Enter the following payload:

```
1' AND 1=2#
```

Result: **"User ID is MISSING from the database."** -- the condition `1=2` is false, so the original query returns no rows.

#### Step 4: Understanding the Boolean Difference

The difference between Step 2 and Step 3 is the key. You can now ask the database yes/no questions by injecting conditions and observing which response you get.

#### Step 5: Extracting Data Character by Character

Enter the following payload:

```
1' AND substring(database(),1,1)='d'#
```

If the result says "exists", the first character of the database name is `d`. If it says "MISSING", try another letter. By repeating this for each character position, you can extract the entire database name (and any other data) one character at a time.

Try position 2:

```
1' AND substring(database(),2,1)='v'#
```

This is slow but works even when the application reveals no data at all -- only a binary exists/missing response.

---

### Medium Level

Set the DVWA security level to **Medium**.

#### Step 1: Observe the Change

The text input field is gone. It has been replaced with a **dropdown menu** that only offers numeric options. You cannot type arbitrary input directly.

#### Step 2: Intercept with ZAP

Use OWASP ZAP (or browser Developer Tools > Network tab) to intercept the POST request when you submit the form.

Find the `id` parameter in the request body.

#### Step 3: Modify the Request

Change the `id` parameter value to:

```
1 OR 1=1
```

Note: **no quotes are needed** here. The underlying query treats the input as a numeric value, so the injection works without string delimiters.

#### Step 4: Understand Why Escaping Fails

View the source code. The application uses `mysql_real_escape_string()` to sanitize input. This function escapes special characters like single quotes. However, since the parameter is **numeric** and is not wrapped in quotes in the SQL query, `mysql_real_escape_string()` provides no protection. The injection does not need quotes, so there is nothing to escape.

This is a critical lesson from the book: **escaping alone is not sufficient for numeric parameters**.

---

### High Level

Set the DVWA security level to **High**.

#### Step 1: Observe the Change

The input now comes from a **separate popup window**. The value is stored in the session, and the main page reads it from there. This is an attempt to make automated tools harder to use (the input and output are on different pages).

#### Step 2: Inject via the Popup

In the popup window, enter:

```
1' OR '1'='1'#
```

Submit and observe the result on the main page.

#### Step 3: Note the LIMIT Clause

The query now includes `LIMIT 1`, which restricts output to one row. Try:

```
1' OR 1=1#
```

The `LIMIT 1` means only the first matching row is returned, but the injection itself still works. The application is still vulnerable -- the defense is cosmetic, not structural.

---

### Impossible Level

Set the DVWA security level to **Impossible**.

#### Step 1: Try Any Injection

Try all of the previous payloads:

```
1' OR '1'='1
1' UNION SELECT user, password FROM users#
1 OR 1=1
```

**Nothing works.** Every injection attempt is blocked.

#### Step 2: View Source

Click "View Source" to see the code. The Impossible level uses **PDO prepared statements** with parameterized queries:

```php
$data = $db->prepare('SELECT first_name, last_name FROM users WHERE user_id = (:id) LIMIT 1;');
$data->bindParam(':id', $id, PDO::PARAM_INT, 11);
$data->execute();
```

The user input is bound as a parameter with a specified type (`PDO::PARAM_INT`). It is never concatenated into the SQL string. The database engine treats the bound value purely as data, never as executable SQL. This completely eliminates SQL injection.

**This is the book's primary recommendation (Chapter 4.4).**

---

## What to Observe in ZAP

While working through the exercises, keep ZAP open and examine the HTTP history:

- **See the SQL payload** in the request parameter (GET parameter at Low/High, POST body at Medium)
- **Compare responses** across security levels -- the same payload produces different behavior at each level
- **Notice error messages** at Low level that reveal database details vs. suppressed/generic errors at higher levels
- **Track the session cookie** -- at High level, the input and output travel through the session, not the URL

---

## Book Connection

| Exercise | Book Reference |
|---|---|
| UNION SELECT attack | Book's example 44-002 |
| Error-based extraction (EXTRACTVALUE) | Book's EXTRACTVALUE technique |
| PDO prepared statements (Impossible level) | Book's primary countermeasure |
| `mysql_real_escape_string` limitation (Medium) | Book's warning about numeric parameters |
| Blind SQL injection (boolean-based) | Character-by-character extraction technique |

---

## Key Takeaways

1. **SQL injection is caused by string concatenation** -- when user input is directly embedded in SQL queries, the database cannot distinguish data from commands.

2. **Input type matters** -- string parameters and numeric parameters require different handling. Escaping quotes only protects string parameters.

3. **Hiding input fields is not a defense** -- dropdowns (Medium) and separate pages (High) can be bypassed with proxy tools like ZAP.

4. **Prepared statements are the real fix** -- PDO with parameterized queries (Impossible level) eliminates the vulnerability entirely by separating SQL structure from data.

5. **Blind injection is slower but equally dangerous** -- even when an application shows no data, a single boolean difference (exists vs. missing) is enough to extract the entire database.

6. **Defense in depth** -- combine prepared statements with input validation, least-privilege database accounts, and error message suppression.
