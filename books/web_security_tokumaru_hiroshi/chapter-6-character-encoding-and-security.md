# Chapter 6 - Character Encoding and Security

> From "Safe Web Application Development" by Tokumaru Hiroshi
> Chapter 6: Character Codes and Security

---

## 6.1 Overview of Character Codes and Security

This chapter explains vulnerabilities that arise from improper handling of character codes. Web applications frequently process strings, and when character code handling is flawed, string processing bugs occur, which can become the root cause of security vulnerabilities.

As an introduction to character codes, this chapter covers:
1. **Character sets** and **character encodings** (character code formats)
2. Vulnerabilities arising from character set and encoding issues
3. How to handle character codes correctly

**Key recommendation:** As a simple and effective approach to avoiding character-code-related issues, unify the character encoding throughout the application to **UTF-8**. While this does not resolve every character code problem, it is a highly efficient and effective method.

---

## 6.2 Character Sets

### What Is a Character Set?

A **character set** (charset) is a collection of characters used on a computer. Alphabets (A, B, C, ...), digits (0, 1, 2, ...), etc., are examples of character sets. When handling character sets on a computer, you need not only collect characters but also assign a **code point** (numeric value) to each character. This formalized character collection is called a **coded character set**, though in this chapter the term "character set" is used broadly to cover both concepts.

### Table 6-1: Representative Character Sets

| Character Set | Bit Length | Language | Description |
|---|---|---|---|
| US-ASCII | 7-bit | English | Standardized for ASCII + French/German/Latin accent marks |
| ISO-8859-1 | 8-bit | Western European | US-ASCII + accented characters for European languages |
| JIS X 0201 | 8-bit | Japanese (partial) | US-ASCII + half-width katakana for Japanese |
| JIS X 0208 | 16-bit | Japanese | Approximately 6,800 characters including kanji |
| Microsoft Standard Character Set | 16-bit | Japanese | JIS X 0201 + JIS X 0208 + NEC/IBM extended characters |
| JIS X 0213 | 21-bit | Japanese | Extension of JIS X 0208 with 4th-level kanji |
| Unicode | 21-bit | Worldwide | All world languages in a unified character set |

### US-ASCII and ISO-8859-1

- **US-ASCII** (US-American Standard Code for Information Interchange) was standardized in 1963 in the US, using 7 bits to represent 128 characters for English text including upper/lowercase letters, digits, and control characters.
- US-ASCII became the foundation and has greatly influenced subsequent character sets.
- **ISO-8859-1** extends US-ASCII to 8 bits, adding French, German, and other Western European accented characters and symbols. Also known as **Latin-1**, it replaced US-ASCII for broader use.

### JIS Character Sets

- **JIS X 0201** extends US-ASCII to 8 bits, adding half-width katakana and Japanese-specific symbols. Notably, the backslash position (`0x5C`) is assigned to the yen sign (`\`), and `0x7E` (tilde `~`) is changed to overline. This backslash/yen issue is a source of security problems.
- **JIS X 0208** is a 2-byte character set covering approximately 6,800 characters including kanji. It uses a different encoding system from US-ASCII/JIS X 0201. Characters from JIS X 0208 are called **full-width characters** (zenkaku), while those from JIS X 0201 are called **half-width characters** (hankaku).
- **JIS X 0213** extends JIS X 0208, adding third and fourth level kanji (approximately 3,600 additional characters).

### Figure 6-2: Relationship Between 1-Byte Character Sets

```
+----------------------------------+
|          JIS X 0201             |
|   +------------------------+    |
|   |      US-ASCII          |    |
|   |  +------------------+  |    |
|   |  |   ISO-8859-1     |  |    |
|   |  |   (overlapping)  |  |    |
|   |  +------------------+  |    |
|   +------------------------+    |
+----------------------------------+
```

ISO-8859-1 and JIS X 0201 overlap in the ASCII range but differ in the 0x80-0xFF region.

### Microsoft Standard Character Set

Microsoft Corporation (then Microsoft KK) defined a unified character set in 1993 for Windows 3.1 Japanese, combining character sets from various vendors. This allows a common character set across PCs regardless of vendor. It includes JIS X 0201 + JIS X 0208, plus **NEC extended characters** and **IBM extended characters** (so-called "round numbers" like (1), (2), etc., and IBM kanji variants).

Many of these characters from the Microsoft Standard Character Set have been incorporated into **JIS X 0213** and **Unicode**.

### Figure 6-3: Multi-Byte Character Set Inclusion Relationships

```
+--------------------------------------------------+
|                    Unicode                        |
|   +------------------------------------------+   |
|   |        JIS X 0213                        |   |
|   |   +----------------------------------+   |   |
|   |   | Microsoft Standard Character Set |   |   |
|   |   |   +----------+  +----------+    |   |   |
|   |   |   | JIS X    |  | JIS X    |    |   |   |
|   |   |   | 0201     |  | 0208     |    |   |   |
|   |   |   +----------+  +----------+    |   |   |
|   |   +----------------------------------+   |   |
|   +------------------------------------------+   |
+--------------------------------------------------+
```

### Unicode

Until now, each country developed its own independent character sets, which created problems for international information exchange. **Unicode** was created by a software industry consortium to be a **unified, worldwide character set**.

- Version 1.0 was published in 1993.
- Originally intended to fit within 16 bits, it has since been expanded to **21 bits** (the Basic Multilingual Plane, BMP, plus supplementary planes).
- Code points are written as **U+XXXX** (XXXX is 4-6 hex digits, e.g., U+0041 = 'A').
- Unicode encompasses US-ASCII, ISO-8859-1, JIS X 0201, JIS X 0208, JIS X 0213, and the Microsoft Standard Character Set.
- See Figure 6-3 for the inclusion relationships.

### Table 6-2: Character Assignment Differences Across Character Sets (0x5C and 0xA5)

| Character Set | 0x5C | 0xA5 |
|---|---|---|
| ASCII | `\` (backslash) | - |
| JIS X 0201 | `\` (yen sign) | - |
| ISO-8859-1 | `\` (backslash) | `\` (yen sign) |
| Unicode | `\` (backslash) | `\` (yen sign) |

**Problem:** In JIS X 0201, the yen sign (`\`) occupies the same position as the backslash in ASCII. Depending on the processing system, the yen sign `\` (U+00A5) can be converted to a backslash `\` (0x5C in JIS X 0201), which then becomes a backslash escape character. This can lead to **escape processing bypass**, which is a source of vulnerabilities (e.g., SQL injection, XSS).

### Vulnerability from Character Set Conversion

When converting Unicode's yen sign `\` (U+00A5) to a JIS-based character set, the processing system may convert it to `0x5C` (backslash in JIS X 0201). If this happens *after* escape processing, the escape is effectively bypassed, becoming a vulnerability. (See Section 4.4 for SQL injection via this route, and JVN#89379547 for a concrete example.)

---

## 6.3 Character Encoding

### What Is Character Encoding?

While a character set (coded character set) assigns code points to characters, these can be used directly on computers -- but in practice this is only simple for single-byte character sets like US-ASCII.

For multi-byte characters such as JIS X 0208 (kanji), **character encodings** define how code points are serialized into byte sequences. Historically, US-ASCII's dominance means that backward-compatible, multi-byte-aware encodings are essential.

For Japanese web applications, the main character encodings are:
- **Shift_JIS** and **EUC-JP** (based on JIS character sets)
- **UTF-16** and **UTF-8** (based on Unicode)

---

### Shift_JIS

Shift_JIS was developed around 1982 to meet the need for a new character encoding for PCs. It maps JIS X 0201 to single-byte ranges and JIS X 0208 to double-byte ranges.

#### Figure 6-4: Shift_JIS Byte Distribution

| Byte Type | Byte Range |
|---|---|
| 1-byte character | `0x00-0x7F`, `0xA0-0xDF` |
| 2-byte character lead byte | `0x81-0x9F`, `0xE0-0xFC` |
| 2-byte character trail byte | `0x40-0x7E`, `0x80-0xFC` |

**Key issue:** The lead byte and trail byte ranges overlap. Extracting a byte from a Shift_JIS-encoded string does not tell you whether it is a lead byte or trail byte without context. This overlap causes the infamous **"5C problem"**.

#### String Matching Mismatch (the 5C Problem)

Since the lead byte and trail byte ranges overlap, byte-oriented string functions can produce incorrect results.

**Example:** Searching for the character "\" (0x83) in the string "kurirunero" (a sequence of specific Japanese characters):

```
Bytes: 83 89 83 8A 83 8C 83 63 83 AD
```

The character "\" has byte value `0x83`. A naive `strpos()` would find `0x83` at position 0 -- but `0x83` there is actually the lead byte of the 2-byte character, not a standalone match.

#### Figure 6-5: Trail Byte Matching Issue

The string "kurirunero" is a sequence of 2-byte characters where each lead byte is `0x83`. A simple byte search for `0x83` will match every lead byte, producing false positives.

#### Figure 6-6: Matching "\" in the 2nd Byte

Similarly, searching for "\" (0x97 0xA0) may false-match when `0xA0` appears as a trail byte in another character.

**Solution:** Use multi-byte-aware string functions (`mb_strpos` instead of `strpos`). Set the internal encoding to `Shift_JIS` for `mb_strpos`, or set `mbstring.internal_encoding` to `Shift_JIS`.

```php
<?php
$p = strpos('kurirunero', 'neko');  // WRONG: byte-level match
var_dump($p);
```

This problem also extends to the lead byte `0x5C` matching -- when `0x5C` (backslash) appears as a trail byte, escape processing may be tricked. This is a root cause of vulnerabilities.

### Invalid Shift_JIS

Shift_JIS can be exploited when invalid data sequences are crafted as attack input. Invalid Shift_JIS data includes:

- A Shift_JIS lead byte followed by **no data byte** (value: `0x81`)
- A Shift_JIS lead byte followed by a byte **outside the valid trail byte range** (value: `0x81 0x21`)

### XSS via Invalid Shift_JIS Encoding

Invalid Shift_JIS encoding can lead to XSS vulnerabilities. Consider the following PHP script:

```php
// Listing 63/63-001.php
<?php
  session_start();
  header('Content-Type: text/html; charset=Shift_JIS');
?>
<body>
<form action="">
Name: <input name=name value="<?php echo
  htmlspecialchars($_GET['name'], ENT_QUOTES); ?>"><br>
Email: <input name=mail value="<?php echo
  htmlspecialchars($_GET['mail'], ENT_QUOTES); ?>"><br>
<input type="submit">
</form>
</body>
```

**Attack URL:**
```
http://example.jp/63/63-001.php?name=a&mail=onmouseover%3dalert(document.cookie)//
```

#### Figure 6-7 / 6-8 / 6-9: Attack Sequence

1. The page displays normally with two input fields.
2. The attacker crafts a URL with a special byte (`0x82`) injected before the closing quote.
3. The mouse cursor over the input field triggers JavaScript execution -- `alert(document.cookie)` runs.

#### How the Attack Works

The generated HTML source looks like:

```html
<input name=name value="1"><BR>
<input name=mail value="onmouseover=alert(document.cookie)//">
```

#### Figure 6-10: Application-Generated Attribute Values

| Character | 1 | 0x82 | > | ... |
|---|---|---|---|---|
| Bytes | `22` `31` | `82` | `22` `3e` `42` `52` `3e` |

The byte `0x82` is a Shift_JIS lead byte. In old browser versions (Internet Explorer, old Firefox, etc.), `0x82` combined with the following `"` (0x22) would be interpreted as a single 2-byte character.

#### Figure 6-11: 0x82 and `"` Combined as One Character

| Character | 1 | (invalid 2-byte char) | > | B | R | > |
|---|---|---|---|---|---|---|
| Bytes | `22` `31` `82` `22` `3e` `42` `52` `3e` |

As a result, the `"` (closing quote of `value=`) is **consumed** as the trail byte of the 2-byte character. The subsequent `input` element's `value=` attribute boundary is broken, and the `onmouseover=alert(document.cookie)//` part becomes an **event handler attribute** instead of text content. JavaScript then executes.

**Fix:** Specify the correct character encoding in `htmlspecialchars()`:

```php
// Listing 63/63-002.php (fixed)
Name: <input name="name" value="<?php echo
  htmlspecialchars($_GET['name'], ENT_QUOTES, 'Shift_JIS'); ?>"><br>
Email: <input name="mail" value="<?php echo
  htmlspecialchars($_GET['mail'], ENT_QUOTES, 'Shift_JIS'); ?>"><br>
```

With the encoding parameter specified, `htmlspecialchars` rejects the invalid byte sequence, and the `onmouseover` event handler becomes plain text in the input box rather than executable JavaScript.

---

### EUC-JP

EUC-JP was created for use on Unix systems for Japanese data. The US-ASCII range is preserved as single bytes. Japanese characters from JIS X 0208 use 2 bytes in the range `0xA1-0xFE`.

#### Figure 6-13: EUC-JP Byte Distribution

| Byte Type | Byte Range |
|---|---|
| 1-byte character | `0x00-0x7F` (same as ASCII) |
| 2-byte character lead byte | `0xA1-0xFE` |
| 2-byte character trail byte | `0xA1-0xFE` |

**Advantage:** Unlike Shift_JIS, the 2-byte character lead byte and trail byte ranges (`0xA1-0xFE`) do **not** overlap with the single-byte ASCII range (`0x00-0x7F`). This means:
- The `0x5C` (backslash) problem does **not** occur.
- Shift_JIS's "SC" problem and byte-overlap matching issues do not arise.

However, **invalid EUC-JP data** can still produce vulnerabilities under the same conditions as described for invalid Shift_JIS.

#### Figure 6-14: EUC-JP Trail Byte Matching

Example: Searching for "\" in "kurirunero" bytes:

```
Bytes: A5 EB A5 EA A5 ED EC A5 ED
```

In EUC-JP, the byte `0xA5` only appears as a lead byte, never as an ASCII character, so there are no false matches with single-byte characters. The result is correctly `13` (byte-level `strpos` counts from 0).

---

### ISO-2022-JP

**ISO-2022-JP** is a 7-bit encoding that uses **escape sequences** to switch between character sets (US-ASCII and JIS X 0208). Also called **"JIS code"** (JIS encoding).

#### Figure 6-15: ISO-2022-JP Character Sequence Example

| A | B | C | ESC $ B | ... | ESC ( B | 1 |
|---|---|---|---|---|---|---|
| `41` `42` `43` | `1B` `24` `42` | `1B` `48` `3A` `41` `38` | `7A` | `1B` `28` `42` | `21` |

- `ESC $ B` = switch to JIS X 0208 mode
- `ESC ( B` = switch back to US-ASCII mode

ISO-2022-JP is primarily used for email transmission (historical reasons), not for web content or internal processing. In Shift_JIS, EUC-JP, and ISO-2022-JP, these are collectively the main **JIS-based character encodings**.

---

### UTF-16

UTF-16 was designed with the assumption that Unicode would fit within 16 bits, directly using 16-bit code point values.

- Characters in the **BMP** (Basic Multilingual Plane, U+0000 to U+FFFF) are represented as a single 16-bit value.
- Characters outside the BMP (U+10000 to U+10FFFF) use **surrogate pairs** -- two 16-bit values (high surrogate + low surrogate).

#### Figure 6-16: UTF-16 Encoding Example

Character "face" (U+20BB7) encoded in UTF-16:

| Surrogate pair |
|---|
| `D842` `DFB7` |

Character "old" (U+7530):

| Direct encoding |
|---|
| `7530` |

---

### UTF-8

UTF-8 is one of the Unicode encodings. It is **backward-compatible with US-ASCII** and uses variable-length encoding (1 to 4 bytes per character) based on the Unicode scalar value range.

#### Table 6-3: UTF-8 Bit Patterns

| Scalar Value Range | UTF-8 Bit Pattern | Byte Length |
|---|---|---|
| `U+0000` - `U+007F` | `0xxxxxxx` | 1 byte (7 bits) |
| `U+0080` - `U+07FF` | `110xxxxx 10xxxxxx` | 2 bytes (11 bits) |
| `U+0800` - `U+FFFF` | `1110xxxx 10xxxxxx 10xxxxxx` | 3 bytes (16 bits) |
| `U+10000` - `U+10FFFF` | `11110xxx 10xxxxxx 10xxxxxx 10xxxxxx` | 4 bytes (21 bits) |

#### Figure 6-17: UTF-8 Byte Distribution

| Byte Type | Byte Range |
|---|---|
| 1-byte character | `0x00-0x7F` |
| Multi-byte lead byte | `0xC2-0xDF` (2-byte), `0xE0-0xEF` (3-byte), `0xF0-0xF4` (4-byte) |
| Multi-byte continuation byte | `0x80-0xBF` |

**Key advantages:**
- No overlap between single-byte and multi-byte byte ranges (unlike Shift_JIS).
- The `0x5C` problem and Shift_JIS/EUC-JP "SC" matching issues **do not occur** in UTF-8.
- Backward compatible with ASCII.

#### Figure 6-18: UTF-8 Encoding Example

Character "face" (U+20BB7) in UTF-8:
```
F0 A0 AE B7
```

Character "old" (U+7530) in UTF-8:
```
E7 94 B0
```

UTF-8 is generally considered the safest and most interoperable character encoding for web applications.

---

### UTF-8 Non-Shortest Form Problem

#### What Is the Non-Shortest Form?

Looking at Table 6-3, the character `/` (U+002F) should be encoded as the 1-byte sequence `0x2F`. However, it is *technically possible* to represent it using 2, 3, or 4 bytes:

#### Table 6-4: Encoding of `/` (U+002F)

| Bytes | Encoding | Form |
|---|---|---|
| 1 byte | `0x2F` | **Shortest form** (correct) |
| 2 bytes | `0xC0 0xAF` | **Non-shortest form** (invalid) |
| 3 bytes | `0xE0 0x80 0xAF` | **Non-shortest form** (invalid) |
| 4 bytes | `0xF0 0x80 0x80 0xAF` | **Non-shortest form** (invalid) |

#### Security Vulnerability from Non-Shortest Form

Non-shortest form encoding can cause vulnerabilities in the following scenarios:

1. **Security check bypass:** The non-shortest form `0xC0 0xAF` bypasses slash checks because it does not match the byte `0x2F`. When this byte sequence is later converted to a canonical form (Shift_JIS, UTF-16, etc.), the `0xC0 0xAF` is decoded back to a regular slash `/` (`0x2F`).

2. **Directory traversal:** When using non-shortest form `0xC0 0xAF` as a file name component, it may bypass path validation but be interpreted as `/` (slash) when opening the file -- enabling directory traversal attacks.

#### Figure 6-19: Non-Shortest Form Check Bypass Flow

```
Input: 0xC0 0xAF
         |
    [Slash check?] --> No (not 0x2F)
         |
    [Convert to Shift_JIS]
         |
    0xC0 0xAF --> "/" (0x2F)
         |
    [File Open] --> Treats as slash!
         |
       ERROR: Directory traversal!
```

The latest UTF-8 specification (**RFC 3629**) defines non-shortest form as **invalid** and mandates rejection. However, older processing systems may still accept it:

- **Java SE 6 Update 10 and earlier** JRE (Java Runtime Environment)
- **PHP 5.3.1 and earlier** `htmlspecialchars` function

**Always use the latest versions of processing libraries.**

#### PHP's `mb_check_encoding` Function

PHP's `mb_check_encoding` function validates UTF-8:
- **PHP 5.3 and later:** Correctly identifies non-shortest form as invalid UTF-8.
- **PHP 4 and earlier:** Incorrectly accepts non-shortest form as valid.
- **PHP 5.3 specific note:** Surrogate pair ranges (`0xD800-0xDFFF`) and beyond-range (`0xDC00-0xDFFF`) bytes are also checked.

When using `mb_check_encoding`, be aware of surrogate-related edge cases, and verify behavior with your specific PHP version.

#### Other Non-Standard UTF-8

Unicode/ISO/IEC 10646 was revised in 2006 to expand beyond 31 bits; prior to this, UTF-8 could be up to 6 bytes. After the 2006 revision, ISO/IEC 10646 aligned with Unicode, and UTF-8's maximum encoding is now **4 bytes**.

**CESU-8** is a non-standard variant that encodes surrogate pairs separately. It is used internally by some software (e.g., Oracle databases) but should not be used for interchange.

---

## 6.4 Summary of Vulnerability Causes from Character Codes

Three main categories of character-code-related vulnerabilities:

### 1. Invalid Byte Sequences in Character Encoding

Treating invalid byte sequences as valid character encoding is the most common issue. Representative examples:
- **Half-width character lead byte XSS** (Shift_JIS)
- **UTF-8 non-shortest form** attacks
- **IIS/Tomcat directory traversal** via non-shortest form (MS00-057; Tomcat CVE-2008-2938)
- **Nimda worm** (2001) exploited MS00-057

### 2. Improper Handling of Character Encoding

Character encoding mishandling causes the "5C problem" as a representative example. Other issues include:
- Incomplete multi-byte character sequences being misinterpreted
- The "5C" issue (backslash appearing as a trail byte in Shift_JIS)
- XSS attacks using UTF-7 encoding mismatch

### 3. Character Set Conversion Issues

Converting Unicode's yen sign `\` (U+00A5) to other character sets (Microsoft Standard Character Set, etc.) where it becomes backslash (0x5C). This can bypass escape processing and lead to:
- SQL injection (JVN#89379547)
- Other injection attacks

---

## 6.5 Handling Character Codes Correctly

### Four Key Points for Correct Character Code Handling

#### Figure 6-20: Points for Handling Character Codes Correctly

```
  Input              Processing              Output
    |                    |                      |
    v                    v                      v
[Validate        [Use correct          [Specify encoding
 encoding]        encoding in           in output:
                  processing:           - HTTP headers
                  - Multi-byte          - DB settings
                    aware functions     - Email headers]
                  - Correct
                    function params]
```

1. **Unify character sets across the entire application** -- use Unicode
2. **Reject invalid character encoding at input** -- make it an error
3. **Use correct character encoding within processing**
4. **Correctly specify character encoding at output**

---

### 1. Unify Character Sets Across the Application

Use a single character set (preferably **Unicode**) throughout all parts of the application: input validation, processing, storage, and output.

Currently, OS, language runtimes, databases, and base software all support Unicode, so using Unicode/UTF-8 across the entire web application is recommended. This alone provides a significant security benefit.

### 2. Reject Invalid Character Encoding at Input

As explained in Section 4.2 (Input Processing and Security), invalid character encodings should be checked at input time and **rejected as errors**.

Current web application frameworks (Java, ASP.NET, C#, VB.NET) typically check character encoding automatically at input. If the framework does not, explicitly validate encoding:

- PHP: Use `mb_check_encoding()` to validate input
- Perl (5.8+): `decode` function validates and converts; invalid encodings are checked automatically
- PHP: If using `mbstring.internal_encoding`, the encoding is automatically checked

### 3. Use Correct Character Encoding in Processing

#### Multi-Byte-Aware Functions and Libraries

To handle character codes correctly, always use **multi-byte-aware** string functions:
- Java, .NET, Perl (5.8+): Built-in multi-byte support, no special action needed
- PHP: Use `mbstring` functions (e.g., `mb_strpos`, `mb_strlen`)
  - **Source code should be saved as UTF-8**
  - Set `php.ini`: `default_charset` (PHP 5.6+) or `mbstring.internal_encoding` (PHP 5.5 and earlier) to **UTF-8**
  - String processing should use `mbstring` family functions by default (avoid non-Japanese-aware functions for Japanese text)

#### Specify Character Encoding in Functions

When using PHP's `mbstring` functions, the default character encoding is determined by `mbstring.internal_encoding`. However, for functions like `htmlspecialchars` that are **not** part of `mbstring`, you **must** explicitly specify the character encoding parameter:

```php
// REQUIRED: specify encoding explicitly
htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
```

In PHP 5.6+, `htmlspecialchars` uses `default_charset` as the default. In PHP 5.4+, the default encoding for `htmlspecialchars` was changed to UTF-8. In earlier versions, the default was ISO-8859-1 (Latin-1), which could cause security issues.

> **COLUMN: htmlspecialchars encoding specification is mandatory**
>
> In years past, many PHP tutorials omitted the character encoding parameter for `htmlspecialchars`. The default was `ISO-8859-1`, and with `EUC-JP` or `Shift_JIS` content, the function could not properly validate multi-byte sequences. Always specify the encoding explicitly. In recent PHP versions, the default is UTF-8, and the function performs encoding validation before character conversion, which is a significant security improvement.

### 4. Correctly Specify Character Encoding at Output

Specify the character encoding at every output boundary:

#### HTTP Response Header Content-Type

Set the `Content-Type` header to explicitly declare the character encoding:

```
Content-Type: text/html; charset=UTF-8
```

If not specified, the browser may guess the encoding based on content, leading to **XSS via encoding mismatch** (e.g., UTF-7 injection). Always specify the charset. Recommended encodings for HTTP output:

- **UTF-8** (preferred)
- **Shift_JIS** (for legacy mobile/feature phone sites only)
- **EUC-JP**

#### Database Character Encoding

Database character encoding settings affect both security and functionality. Configure:

- **Storage encoding** (per column, per table, or per database)
- **Internal processing encoding** of the database engine
- **Connection encoding** between the application and database

Recommended: Use **Unicode** (specifically **UTF-8** or **utf8mb4** for MySQL). This ensures:
- No character conversion issues
- No truncation of characters outside the configured encoding
- Proper handling of supplementary plane characters

For MySQL, specifying `utf8` only supports 3-byte UTF-8 (BMP only). Use **`utf8mb4`** to support the full Unicode range including 4-byte characters (emoji, rare kanji).

#### Verifying Database Character Set Configuration

Use **"skeleton tests"** (gakotsukentei test) and **"tsuchiiyoshi tests"** to verify:

- If characters like "bone" (骨), "bone+radical" display correctly in the database, Unicode support is working.
- If characters appear garbled or as "?" substitution characters, the encoding configuration needs fixing (likely Shift_JIS or EUC-JP).
- Test with the character "tsuchiiyoshi" (U+9A156) -- a 4-byte UTF-8 character in JIS X 0213 Level 3, not in JIS X 0208. If this test character stores and displays correctly, the database handles the full UTF-8 range.
- Alternatively, verify with the character "old/face" (U+20BB7) -- requires 4-byte UTF-8.

#### Other Output Points

Depending on the language/library, file I/O, email transmission, and other output channels also need explicit encoding specification. Always confirm and specify the character encoding used by the output interface.

### Additional Defense: Avoid Automatic Encoding Detection

Some languages/frameworks offer **automatic character encoding detection** for HTTP requests (PHP, Java, Perl, etc.). However:

- Automatic detection is **not reliable** -- it is heuristic-based and can be wrong
- **Shift_JIS** characters in input may be misdetected, causing Unicode's yen sign to be converted to Shift_JIS backslash (`0x5C`), which in turn may cause escape bypass vulnerabilities
- Encoding auto-detection is not a clear/explicit specification

**Always explicitly specify** the character encoding rather than relying on automatic detection.

---

## 6.6 Summary

This chapter explained how character code handling affects security.

In web application development, "mojibake" (garbled characters) is a frequent FAQ topic. But garbled characters signal that character code configuration or processing is incorrect -- and this can be a vulnerability.

Character-code-related vulnerabilities are not rare or exotic. They are a natural consequence of incorrect configuration and should be verified early.

### Key Verification Checklist

- Invalid character encodings either cause an error or are replaced with the **replacement character** (U+FFFD)
- Characters like "face" (U+20BB7), etc., are correctly stored, registered, and displayed
- "Skeleton tests" and "tsuchiiyoshi tests" pass (database correctly handles full Unicode)

Character-code-related vulnerabilities are also exploited in the wild. The **Nimda worm** (which exploited MS00-057 via non-shortest form UTF-8 directory traversal) is a notable example.

### Countermeasure Summary

| Action | Description |
|---|---|
| Unify character sets | Use **Unicode** throughout the application |
| Reject invalid encoding at input | Validate encoding, return error on invalid input |
| Use correct encoding in processing | Use multi-byte-aware functions; specify encoding explicitly |
| Specify encoding at output | Set `Content-Type: charset=UTF-8`, database encoding, etc. |

### Recommended Reading

- [1] Yazaki Yuichi (2019), "Character Code Technology Introduction for Programmers", Gijutsu-Hyoron-sha
- [2] Yano Keiji (2000), "History of Characters in the Computer Age", Bunshun-Shinsho
