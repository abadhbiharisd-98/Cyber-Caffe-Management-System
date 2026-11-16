<?php
/**
 * CCMS Admin Direct Router
 * Prevents directory slash reverse proxy port leaks
 */
header("Location: ../ccms/index.php");
exit;
?>
