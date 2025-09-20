<?php
$path = isset($_GET['path']) ? $_GET['path'] : '.';
$path = realpath($path);

// Handle file upload
if (isset($_FILES['upload'])) {
    $uploadPath = $path . '/' . basename($_FILES['upload']['name']);
    move_uploaded_file($_FILES['upload']['tmp_name'], $uploadPath);
    header("Location: ?path=$path");
    exit;
}

// Handle folder creation
if (isset($_POST['new_folder'])) {
    mkdir($path . '/' . $_POST['new_folder']);
    header("Location: ?path=$path");
    exit;
}

// Handle delete
if (isset($_GET['delete'])) {
    $target = realpath($path . '/' . $_GET['delete']);
    if (is_dir($target)) {
        rmdir($target);
    } else {
        unlink($target);
    }
    header("Location: ?path=$path");
    exit;
}

// Handle rename
if (isset($_POST['rename_old']) && isset($_POST['rename_new'])) {
    $old = $path . '/' . $_POST['rename_old'];
    $new = $path . '/' . $_POST['rename_new'];
    rename($old, $new);
    header("Location: ?path=$path");
    exit;
}

// Handle file save (edit)
if (isset($_POST['file_path']) && isset($_POST['file_content'])) {
    file_put_contents($_POST['file_path'], $_POST['file_content']);
    header("Location: ?path=" . dirname($_POST['file_path']));
    exit;
}

// List files and folders
$items = scandir($path);
echo "<h2>File Manager: $path</h2>";
echo "<a href='?path=" . dirname($path) . "'>⬅️ Back</a><br><br>";

echo "<form method='POST' enctype='multipart/form-data'>
    <input type='file' name='upload'>
    <button type='submit'>Upload</button>
</form>";

echo "<form method='POST'>
    <input type='text' name='new_folder' placeholder='New folder name'>
    <button type='submit'>Create Folder</button>
</form><br>";

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Name</th><th>Actions</th></tr>";
foreach ($items as $item) {
    if ($item == '.' || $item == '..') continue;
    $itemPath = $path . '/' . $item;
    echo "<tr><td>";
    if (is_dir($itemPath)) {
        echo "<a href='?path=$itemPath'>📁 $item</a>";
    } else {
        echo "📄 $item";
    }
    echo "</td><td>
        <form method='POST' style='display:inline'>
            <input type='hidden' name='rename_old' value='$item'>
            <input type='text' name='rename_new' placeholder='New name'>
            <button type='submit'>Rename</button>
        </form>
        <a href='?path=$path&delete=$item' onclick='return confirm(\"Delete $item?\")'>🗑️ Delete</a>";

    if (!is_dir($itemPath)) {
        $relativePath = str_replace($_SERVER['DOCUMENT_ROOT'], '', $itemPath);
        echo " | <a href='$relativePath' download>⬇️ Download</a>";
        echo " | <a href='?path=$path&edit=$item'>✏️ Edit</a>";
    }

    echo "</td></tr>";
}
echo "</table>";

// Show edit form if a file is selected to edit
if (isset($_GET['edit'])) {
    $editFile = $path . '/' . $_GET['edit'];
    if (is_file($editFile) && is_readable($editFile)) {
        $content = htmlspecialchars(file_get_contents($editFile));
        echo "<h3>Editing: {$_GET['edit']}</h3>";
        echo "<form method='POST'>
            <input type='hidden' name='file_path' value='" . htmlspecialchars($editFile) . "'>
            <textarea name='file_content' rows='20' cols='100'>$content</textarea><br>
            <button type='submit'>💾 Save</button>
        </form>";
    } else {
        echo "<p>❌ Unable to read file.</p>";
    }
}
?>
