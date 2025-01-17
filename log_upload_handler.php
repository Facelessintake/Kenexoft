<?php
// Database Configuration
$servername = "localhost";
$username = "karma1";
$password = "sanchitreddy";
$dbname = "upload_logs";

// Connect to the database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Plan-specific table and directory configurations
$planConfig = [
    "Essential" => ["table" => "essential_logs", "limit" => 3],
    "Standard" => ["table" => "standard_logs", "limit" => 5],
    "Premium" => ["table" => "premium_logs", "limit" => 8]
];

// Get the selected plan from the form
$selectedPlan = $_POST['plan'] ?? "Essential";
$tableName = $planConfig[$selectedPlan]['table'];
$fileLimit = $planConfig[$selectedPlan]['limit'];

// Ensure the directory for uploaded files exists
$uploadDir = "uploads/$selectedPlan/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Create plan-specific table if not exists
$createTableQuery = "CREATE TABLE IF NOT EXISTS `$tableName` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    log_name VARCHAR(255) NOT NULL,
    upload_path VARCHAR(255) NOT NULL,
    upload_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($createTableQuery);

// Process file uploads
$uploadedFiles = [];
for ($i = 0; $i < $fileLimit; $i++) {
    $fileKey = "logFile$i";
    if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
        $fileName = basename($_FILES[$fileKey]['name']);
        $targetFilePath = $uploadDir . $fileName;

        // Move file to upload directory
        if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $targetFilePath)) {
            $uploadedFiles[] = $fileName;

            // Insert upload details into the database
            $stmt = $conn->prepare("INSERT INTO `$tableName` (log_name, upload_path) VALUES (?, ?)");
            $stmt->bind_param("ss", $fileName, $targetFilePath);
            $stmt->execute();
            $stmt->close();
        } else {
            echo "Error uploading file: $fileName<br>";
        }
    }
}

// Display success message
if (!empty($uploadedFiles)) {
    echo "<h3>Files uploaded successfully for $selectedPlan Plan:</h3><ul>";
    foreach ($uploadedFiles as $file) {
        echo "<li>$file</li>";
    }
    echo "</ul>";
} else {
    echo "No files were uploaded.";
}

$conn->close();
?>
