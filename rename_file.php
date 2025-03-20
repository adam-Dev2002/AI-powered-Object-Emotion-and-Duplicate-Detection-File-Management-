<?php
require_once 'config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['filePath']) && isset($data['newName'])) {
        $filePath = trim($data['filePath']); // ✅ Absolute path
        $newName = trim($data['newName']);

        if (!file_exists($filePath)) {
            echo json_encode(['success' => false, 'error' => 'Path does not exist.', 'debug' => $filePath]);
            exit();
        }

        $directory = dirname($filePath);
        $originalExtension = pathinfo($filePath, PATHINFO_EXTENSION);

        // ✅ Check if newName has an extension, if not, append the original extension
        if (pathinfo($newName, PATHINFO_EXTENSION) == '') {
            $newName .= '.' . $originalExtension;
        }

        $newPath = $directory . '/' . $newName;

        if (rename($filePath, $newPath)) {
            if (is_dir($newPath)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Folder renamed successfully.',
                    'newFilePath' => $newPath,
                    'newFileName' => $newName
                ]);
            } else {
                // ✅ Update the database
                $stmtFiles = $conn->prepare("UPDATE files SET filepath = ?, filename = ? WHERE filepath = ?");
                $stmtFiles->bind_param("sss", $newPath, $newName, $filePath);
                $stmtFiles->execute();
                $stmtFiles->close();

                $stmtAlbumFiles = $conn->prepare("UPDATE album_files SET filepath = ?, filename = ? WHERE filepath = ?");
                $stmtAlbumFiles->bind_param("sss", $newPath, $newName, $filePath);
                $stmtAlbumFiles->execute();
                $stmtAlbumFiles->close();

                $stmtFetch = $conn->prepare("SELECT filename FROM files WHERE filepath = ?");
                $stmtFetch->bind_param("s", $newPath);
                $stmtFetch->execute();
                $stmtFetch->bind_result($latestFileName);
                $stmtFetch->fetch();
                $stmtFetch->close();

                echo json_encode([
                    'success' => true,
                    'message' => 'File renamed successfully in both tables.',
                    'newFilePath' => $newPath,
                    'newFileName' => $latestFileName
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Failed to rename.',
                'debug' => [
                    'oldPath' => $filePath,
                    'newPath' => $newPath,
                    'permissions' => substr(sprintf('%o', fileperms($filePath)), -4)
                ]
            ]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid input parameters.']);
    }
}
$conn->close();
?>
