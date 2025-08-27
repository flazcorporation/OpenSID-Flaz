<?php
// Debug installer issues
// Access: http://kelurahansepang.id/debug-install.php

echo "<h2>Debug OpenSID Installer</h2>";

// Check session
echo "<h3>Session Info:</h3>";
session_start();
echo "Session ID: " . session_id() . "<br>";
echo "Session Path: " . session_save_path() . "<br>";
echo "Session Cookie Params: ";
print_r(session_get_cookie_params());

// Check CSRF
echo "<h3>CSRF Info:</h3>";
if (isset($_COOKIE['sidcsrf'])) {
    echo "CSRF Cookie: " . $_COOKIE['sidcsrf'] . "<br>";
} else {
    echo "No CSRF cookie found<br>";
}

// Check POST data
echo "<h3>POST Data:</h3>";
if (!empty($_POST)) {
    print_r($_POST);
} else {
    echo "No POST data<br>";
}

// Check server info
echo "<h3>Server Info:</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "<br>";
echo "HTTP Host: " . $_SERVER['HTTP_HOST'] . "<br>";

// Check file permissions
echo "<h3>File Permissions:</h3>";
$check_files = [
    'donjo-app/config/config.php',
    'storage',
    'storage/logs'
];

foreach ($check_files as $file) {
    if (file_exists($file)) {
        $perms = substr(sprintf('%o', fileperms($file)), -4);
        echo "$file: $perms<br>";
    } else {
        echo "$file: NOT FOUND<br>";
    }
}
?>