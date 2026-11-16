<?php
/**
 * Cyber Cafe Management System (CCMS)
 * Root Landing Router
 * Modernized for Render Reverse Proxy & Aiven Cloud MySQL
 */

// Direct file routing prevents Apache trailing slash redirect issues with reverse proxies
header("Location: ccms/index.php");
exit;
?>
