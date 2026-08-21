<?php
header('Content-Type: text/plain');
echo "STATUS_OK:ROOT_PHP_" . phpversion() . "\n";
echo "DOCUMENT_ROOT:" . ($_SERVER['DOCUMENT_ROOT'] ?? 'unknown') . "\n";
echo "SCRIPT_FILENAME:" . ($_SERVER['SCRIPT_FILENAME'] ?? 'unknown') . "\n";
echo "CURRENT_DIR:" . __DIR__ . "\n";
