<?php
$base_directory = '/Applications/XAMPP/xamppfiles/htdocs/testcreative';

// Define file extensions for categories
$file_types = [
    'Images' => ['jpg', 'jpeg', 'png', 'gif'],
    'Videos' => ['mp4', 'mov', 'avi', 'mkv'],
    'Audios' => ['mp3', 'wav', 'flac'],
    'Others' => [] // Everything else
];

// Function to categorize and calculate file sizes
function categorizeFiles($base_directory, $file_types) {
    $categories = array_fill_keys(array_keys($file_types), 0); // Initialize categories with 0 size
    $total_files = 0; // Initialize total file counter

    // Use `find` to scan files, output size and full path
    $command = "find " . escapeshellarg($base_directory) . " -type f -exec stat -f '%z %N' {} +";
    $output = shell_exec($command);

    if ($output) {
        $lines = explode("\n", $output);
        foreach ($lines as $line) {
            if (empty($line)) continue;

            // Split size and file path
            preg_match('/^(\d+)\s+(.+)$/', $line, $matches);
            if (count($matches) !== 3) continue;

            $file_size = (int)$matches[1]; // File size in bytes
            $file_path = $matches[2];
            $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

            $total_files++; // Increment total file counter

            // Categorize the file
            $categorized = false;
            foreach ($file_types as $type => $extensions) {
                if (in_array($extension, $extensions)) {
                    $categories[$type] += $file_size;
                    $categorized = true;
                    break;
                }
            }

            if (!$categorized) {
                $categories['Others'] += $file_size;
            }
        }
    }

    // Convert sizes to GB
    foreach ($categories as $type => $size) {
        $categories[$type] = round($size / (1024 ** 3), 2); // Convert bytes to GB
    }

    return [
        'categories' => $categories,
        'total_files' => $total_files
    ];
}

// Get disk space info
function getDiskSpace($base_directory) {
    $total_space = disk_total_space($base_directory);
    $free_space = disk_free_space($base_directory);
    $used_space = $total_space - $free_space;
    $used_percentage = ($used_space / $total_space) * 100;

    return [
        'total_space' => $total_space,
        'free_space' => $free_space,
        'used_space' => $used_space,
        'used_percentage' => $used_percentage
    ];
}

// Fetch all stats
function getStorageStats($base_directory, $file_types) {
    $disk_space = getDiskSpace($base_directory);
    $file_data = categorizeFiles($base_directory, $file_types);

    return [
        'total_space' => formatSize($disk_space['total_space']),
        'free_space' => formatSize($disk_space['free_space']),
        'used_space' => formatSize($disk_space['used_space']),
        'used_percentage' => round($disk_space['used_percentage'], 2),
        'file_categories' => $file_data['categories'],
        'total_files' => $file_data['total_files'] // Include total file count
    ];
}

// Format sizes for readability
function formatSize($bytes) {
    $sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.2f", $bytes / pow(1024, $factor)) . " " . $sizes[$factor];
}

// Return the stats as JSON
header('Content-Type: application/json');
echo json_encode(getStorageStats($base_directory, $file_types));
?>
