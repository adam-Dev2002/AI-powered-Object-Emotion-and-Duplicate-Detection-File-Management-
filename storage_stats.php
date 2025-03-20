<?php
$base_directory = '/Applications/XAMPP/xamppfiles/htdocs/testcreative';
$log_file = __DIR__ . '/storage_log.json'; // Log file for historical storage data

$file_types = [
    'Images' => ['jpg', 'jpeg', 'png', 'gif'],
    'Videos' => ['mp4', 'mov', 'avi', 'mkv'],
    'Audios' => ['mp3', 'wav', 'flac'],
    'Others' => []
];

// Function to categorize and calculate file sizes
function categorizeFiles($base_directory, $file_types) {
    $categories = array_fill_keys(array_keys($file_types), 0);
    $total_files = 0;

    if (!is_dir($base_directory)) {
        return ['error' => "Directory not found: $base_directory"];
    }

    $command = "find " . escapeshellarg($base_directory) . " -type f -exec stat -f '%z %N' {} +";
    $output = shell_exec($command);

    if ($output) {
        $lines = explode("\n", $output);
        foreach ($lines as $line) {
            if (empty($line)) continue;

            if (!preg_match('/^(\d+)\s+(.+)$/', $line, $matches)) continue;

            $file_size = (int)$matches[1];
            $file_path = $matches[2];
            $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

            $total_files++;

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

    foreach ($categories as $type => $size) {
        $categories[$type] = round($size / (1024 ** 3), 2);
    }

    return [
        'categories' => $categories,
        'total_files' => $total_files
    ];
}

// Get disk space info
function getDiskSpace($base_directory) {
    if (!is_dir($base_directory)) {
        return ['error' => "Invalid path: $base_directory"];
    }

    $total_space = @disk_total_space($base_directory);
    $free_space = @disk_free_space($base_directory);

    if ($total_space === false || $free_space === false) {
        return ['error' => "Failed to retrieve disk space"];
    }

    $used_space = $total_space - $free_space;
    return [
        'total_space' => $total_space,
        'free_space' => $free_space,
        'used_space' => $used_space
    ];
}

// Store monthly storage usage for historical tracking
function logStorageUsage($log_file, $used_space) {
    $year = date('Y');
    $month = date('F');

    if (!file_exists($log_file)) {
        file_put_contents($log_file, json_encode([])); // Initialize if missing
    }

    $data = json_decode(file_get_contents($log_file), true) ?? [];

    if (!isset($data[$year])) {
        $data[$year] = [];
    }

    $data[$year][$month] = round($used_space / (1024 ** 3), 2);

    file_put_contents($log_file, json_encode($data, JSON_PRETTY_PRINT));
}

// Fetch all stats
function getStorageStats($base_directory, $file_types, $log_file) {
    $disk_space = getDiskSpace($base_directory);
    if (isset($disk_space['error'])) {
        return ['error' => $disk_space['error']];
    }

    $file_data = categorizeFiles($base_directory, $file_types);
    if (isset($file_data['error'])) {
        return ['error' => $file_data['error']];
    }

    logStorageUsage($log_file, $disk_space['used_space']);

    return [
        'total_space' => formatSize($disk_space['total_space']),
        'free_space' => formatSize($disk_space['free_space']),
        'used_space' => formatSize($disk_space['used_space']),
        'used_percentage' => round(($disk_space['used_space'] / $disk_space['total_space']) * 100, 2),
        'file_categories' => $file_data['categories'],
        'total_files' => $file_data['total_files'],
        'history' => file_exists($log_file) ? json_decode(file_get_contents($log_file), true) : []
    ];
}

// Format sizes for readability
function formatSize($bytes) {
    $sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.2f", $bytes / pow(1024, $factor)) . " " . $sizes[$factor];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode(getStorageStats($base_directory, $file_types, $log_file));
?>
