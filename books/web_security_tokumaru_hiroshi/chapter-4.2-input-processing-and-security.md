# Chapter 4.2 - Input Processing and Security

> From "The Art of Secure Web Application Development" (安全なWebアプリケーションの作り方) by Tokumaru Hiroshi

---

## Overview

This section focuses on **input processing** from a security perspective. Input validation alone cannot prevent all vulnerabilities, but it can reduce the likelihood of issues and serve as a safety net when combined with other measures.

### Web Application Input/Output Model

```
HTTP Request
    |
  Input
    |
  Processing (Application Logic)
    |
  Output
    |
HTTP Response
```

Input processing prepares data before application logic begins.

---

## Character Encoding Validation

### Why Validate Character Encoding?

- Incorrect character code handling can lead to vulnerabilities even with proper programming.
- Attackers can exploit invalid character encoding to bypass security filters.
- Input encoding validation is a **security prerequisite**, not just a usability concern.

### Three Steps of Input Processing

1. **(a)** Validate character encoding of input
2. **(b)** Convert character encoding (only if necessary)
3. **(c)** Validate input values (parameter strings)

### PHP: `mb_check_encoding()`

```php
bool mb_check_encoding(string $var, string $encoding)
```

- First argument (`$var`): the string to check
- Second argument (`$encoding`): the expected encoding
- Returns `true` if the string is valid for the specified encoding

### Character Encoding Conversion Methods by Language

| Language | Auto-detection  | Script-based                |
|----------|-----------------|-----------------------------|
| PHP      | php.ini etc.    | `mb_convert_encoding`       |
| Perl     | x               | `Encode::decode`            |
| Java     | `setCharacterEncoding` | String class           |
| ASP.NET  | Web.config      | x                           |

### PHP: `mb_convert_encoding()`

```php
string mb_convert_encoding(string $str, string $to_encoding, string $from_encoding)
```

- Takes 3 arguments: source string, target encoding, source encoding
- Returns the converted string

### Example: Encoding Check and Conversion

```php
<?php
$name = isset($_GET['name']) ? $_GET['name'] : '';
// Check character encoding (Shift_JIS)
if (!mb_check_encoding($name, 'Shift_JIS')) {
    die('Character encoding is incorrect');
}
// Convert encoding (Shift_JIS -> UTF-8)
$name = mb_convert_encoding($name, 'UTF-8', 'Shift_JIS');
?>
<body>
Name: <?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>
</body>
```

- When valid Shift_JIS input is given: displays correctly
- When invalid Shift_JIS input (e.g., `%82%21`): dies with encoding error
  - The 2nd byte of Shift_JIS 2-byte characters must be 0x40 or higher; `%21` falls outside this range

---

## Input Value Validation

### Purpose of Input Validation

Prevent issues such as:

- **Database errors** from entering alphabetic/symbol characters in numeric-only fields
- **Data inconsistency** from update processing errors
- **Internal errors** from users submitting forms with too many items
- **Unwanted actions** from missing required fields (e.g., sending emails without an email address)

Input validation catches errors early, improving **usability** and preventing **data inconsistencies** and **system reliability issues**.

### Input Validation and Security

- Input validation is **not the primary defense** against security vulnerabilities
- However, it can be useful in certain cases:
  - Preventing SQL injection when only numeric values are allowed
  - Preventing control character attacks
- **Binary-safe functions** should be used regardless of input validation
- Input validation alone is **not sufficient** for security

### Binary-Safe Concept

- A **binary-safe** function can correctly handle any byte sequence, including null bytes
- Null bytes (`\0`) are especially problematic in C, Unix/Windows APIs
- Languages like PHP, which are built on C, may have functions vulnerable to null byte attacks
- Using binary-safe functions + null byte checks provides more robust defense

### Key Principle: Input Validation Standards Come from Application Requirements

- Validation criteria are based on **application specifications**, not security requirements
- Examples: phone numbers are digits only, user IDs are alphanumeric, etc.

---

## What to Validate

### Control Character Check

- Validate that input does not contain control characters
- "Allow all characters" means allowing **printable characters**, not control characters
- Control characters: carriage return, line feed (`\r`, `\n`), ASCII codes below 0x20 except tab, and 0x7F (DELETE)
- **Exception**: Multiline input fields (`<textarea>`, `<input type="password">`) may legitimately contain newlines
- Single-line text inputs (`<input type="text">`) should reject control characters

### Character Count Check

- All parameters should have a **maximum character length** based on specs
- Databases often have fixed column sizes; exceeding them causes errors
- From a security perspective, limiting length reduces attack surface
  - e.g., SQL injection payloads in 10-character fields are limited
  - But this is **not a reliable defense** on its own

### Numeric Range (Min/Max) Check

- For numeric fields, define minimum and maximum values in the specification
- Validation ensures values stay within acceptable range
- Unchecked large numbers can cause **Denial of Service (DoS)** attacks

### Numeric Validation Steps

1. Check character type (digits only) and character count
2. Convert from string to numeric type
3. Verify the value is within min/max range

---

## Other Important Considerations

### Missing Parameters

- Handle cases where expected parameters are not sent at all
- Example: `$foo = $_GET['foo'];` causes `Undefined index` error if `foo` is missing

### Handling Missing Parameters in PHP

```php
// Traditional approach
$foo = isset($_GET['foo']) ? $_GET['foo'] : null;

// PHP 7.0+ null coalescing operator
$foo = $_GET['foo'] ?? null;
```

### Array Input Handling

- Query strings like `foo[]=bar&foo[a]=baz` produce arrays in PHP
- This is equivalent to: `$foo[0] = 'bar'; $foo['a'] = 'baz';`
- Non-scalar (array) values passed to functions expecting strings can cause bugs or vulnerabilities

### Using `filter_input()`

```php
$foo = filter_input(INPUT_GET, 'foo'); // replaces $_GET['foo']
```

| Query String         | Result  | Type             |
|----------------------|---------|------------------|
| foo=abc              | `'abc'` | String           |
| foo=                 | `''`    | Empty string     |
| foo=abc              | `null`  | (not provided)   |
| foo[]=bar&foo[]=baz  | `false` | Array (rejected) |

- `filter_input` simplifies safe input validation by returning `false` for arrays and `null` for missing parameters

### Which Parameters to Validate

- **All** input parameters: hidden fields, radio buttons, select elements
- Cookies and session IDs (except those managed by the framework)
- HTTP headers like `Referer` (when used by the application)

---

## PHP Regular Expression Functions

Three regex libraries available in PHP:

| Library     | Notes                                                    |
|-------------|----------------------------------------------------------|
| `ereg`      | **Deprecated** - Do NOT use. Vulnerable to null byte attacks |
| `preg`      | Recommended. Use with `mb_ereg` for multibyte if needed  |
| `mb_ereg`   | Supports multibyte character encodings                   |

### Danger of `ereg`: Null Byte Bypass

```php
// VULNERABLE - 42-002.php
$p = $_GET['p'];
if (ereg('[^0-9]', $p) === FALSE) {
    die('Please enter only digits');
}
echo $p;
```

Exploit URL:
```
http://example.jp/42/42-002.php?p=1&00<script>alert('XSS')</script>
```

- `ereg` stops checking at null byte (`\0`), so the `<script>` tag passes validation
- This leads to **Cross-Site Scripting (XSS)**

### Null Byte Attack Pattern

| Character | `\t` | NUL | `<` | `s` | `c` | `r` | `i` | `p` | `t` | `>` | ... |
|-----------|------|-----|-----|-----|-----|-----|-----|-----|-----|-----|-----|
| Byte value | 31 | 00 | 3c | 73 | 63 | 72 | 69 | 70 | 74 | 3e | ... |

- `ereg` sees `<script>` as end-of-string at byte `00`, so the regex check passes

---

## Regular Expression Input Validation Examples

### Example 1: Alphanumeric 1-5 Characters

```php
// 42-010.php
<?php
$p = filter_input(INPUT_GET, 'p');
if (!preg_match('/\A[a-z0-9]{1,5}\z/ui', $p) === 1) {
    die('Please enter 1-5 alphanumeric characters');
}
?>
<body>
p=<?php echo htmlspecialchars($p, ENT_QUOTES, 'UTF-8'); ?>
</body>
```

### Regex Pattern Breakdown: `/\A[a-z0-9]{1,5}\z/ui`

| Component      | Meaning                          |
|----------------|----------------------------------|
| `/`            | Regex delimiter                  |
| `\A`           | Start of string (absolute)       |
| `[a-z0-9]`     | Alphanumeric character class     |
| `{1,5}`        | 1 to 5 characters (quantifier)   |
| `\z`           | End of string (absolute)         |
| `/ui`          | `u` = UTF-8 mode, `i` = case-insensitive |

Key regex concepts:

- **`\A` and `\z`**: Use these instead of `^` and `$` to match the absolute start/end of the string (not line boundaries)
- **`u` modifier**: Specifies UTF-8 encoding; essential for Japanese environments with `preg_match`
- **`i` modifier**: Case-insensitive matching
- **Character class** `[...]`: Match any single character within brackets; `[0-9]` for digits, `[a-zA-Z]` for letters
- **Quantifier** `{n,m}`: Match between n and m occurrences; `{0,5}` allows empty strings

### Example 2: Address Field (Japanese)

For address fields with no strict character restrictions and longer text, use control character exclusion:

```php
// 42-013.php
<?php
$addr = filter_input(INPUT_GET, 'addr');
if (!preg_match('/\A[[:^cntrl:]]{1,30}\z/u', $addr) === 1) {
    die('Please enter up to 30 characters; control characters and tabs are not allowed');
}
```

- `[[:^cntrl:]]` is a **POSIX character class** meaning "not a control character"
- Alternatively, use `\P{Cc}` (Unicode property) for the same effect

### Multiline Input (textarea / comment fields)

For fields allowing newlines (e.g., comment boxes), allow carriage return and line feed:

```php
preg_match('/\A[\r\n[:^cntrl:]]{1,400}\z/u', $comment)
```

- Allows newlines (`\r\n`) while blocking other control characters
- Limits to 1-400 characters

### Using `mb_ereg` Instead of `preg_match`

```php
// 42-012.php (excerpt)
<?php
// mb_ereg requires encoding to be set explicitly
mb_regex_encoding('UTF-8');
$p = filter_input(INPUT_GET, 'p');
if (!mb_ereg('\A[a-zA-Z0-9]{1,5}\z', $p)) {
    die('Please enter 1-5 alphanumeric characters');
}
```

- `mb_regex_encoding()` specifies the character encoding for `mb_ereg`
- In `mb_ereg`, the regex is expressed as a slash-less string
- When `mb_ereg` returns `false`, behavior differs from `preg_match` (check carefully)

### `\w` Warning in `mb_ereg`

- In `mb_ereg`, `\w` matches "defined character classes" which includes **full-width letters and digits** (Unicode)
- In `preg_match`, `\w` matches only ASCII word characters by default
- For web applications, explicitly use `[a-zA-Z0-9_]` instead of `\w`

---

## Complete Sample: `getParam()` Function

```php
// 42-020.php
<?php
function getParam($key, $pattern, $error) {
    // Retrieve parameter and perform encoding check + conversion
    $val = filter_input(INPUT_GET, $key);
    // Character encoding check (Shift_JIS)
    if (!mb_check_encoding($val, 'Shift_JIS')) {
        die('Character encoding error');
    }
    // Convert encoding (Shift_JIS -> UTF-8)
    $val = mb_convert_encoding($val, 'UTF-8', 'Shift_JIS');
    // Validate against pattern
    if (!preg_match($pattern, $val) === 1) {
        die($error);
    }
    return $val;
}

// Usage
$name = getParam('name', '/\A[[:^cntrl:]]{1,20}\z/u',
    'Please enter up to 20 characters; control characters not allowed');
?>
<body>
Name: <?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>
</body>
```

This reusable `getParam()` function performs:
1. Character encoding check
2. Character encoding conversion
3. Input value validation

---

## Input Validation and Frameworks

When using web application frameworks, leverage **built-in validation features** instead of writing custom logic.

### Example: ASP.NET Validation Controls

- .NET Framework provides visual validation controls (e.g., `RangeValidator`)
- `RangeValidator` checks that a value falls within a specified numeric range
- Properties: `Type`, `MinimumValue`, `MaximumValue`, `ErrorMessage`
- Validation runs both client-side (JavaScript) and server-side

---

## Regex for "Non-Control Characters" Across Languages

### PHP (`preg_match`)

```php
if (preg_match('/\A[[:^cntrl:]]{0,100}\z/u', $s) == 1) {
    // Input validation OK
}
```

### PHP (`mb_ereg`)

```php
if (mb_ereg('\A[[:^cntrl:]]{0,100}\z', $s) !== false) {
    // Input validation OK
}
```

### Perl

```perl
if ($s =~ /\A\P{Cc}{0,100}\z/) {
    # Input validation OK
}
```

### Java

```java
if (s.matches("\\A\\P{Cc}{0,100}\\z")) {
    // OK
}
```

- Java's `matches` method performs a full-string match, so `\A`/`\z` are optional but included for clarity
- Use `\\` to escape backslashes in Java string literals

### VB.NET

```vb
If Regex.IsMatch(s, "^\A\P{Cc}{0,100}\z") Then
    ' OK
End If
```

---

## Summary / Key Takeaways

1. **Validate character encoding** at the application entry point, then convert as needed
2. **Input validation is based on application specifications**, not security requirements
3. Input validation is a **safety net**, not a primary security defense -- it helps when platforms or frameworks have latent vulnerabilities
4. **Always validate**:
   - Character encoding
   - Control characters (reject them)
   - Character/string length
   - Numeric min/max ranges
5. **Never use `ereg`** in PHP -- it is vulnerable to null byte attacks
6. Use **`preg_match`** (with `/u` flag) or **`mb_ereg`** for regex validation
7. Use **`\A` and `\z`** anchors instead of `^` and `$` for full-string matching
8. Use **`filter_input()`** to safely retrieve parameters (handles missing params and arrays)
9. Design validation at the **specification stage**: determine max lengths, allowed characters, and value ranges
10. Leverage **framework validation features** when available

### Implementation Steps

1. At design time: decide character types, max lengths, and min/max values per parameter
2. At design time: decide the input validation implementation strategy
3. At development time: implement validation according to specifications
