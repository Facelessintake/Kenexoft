<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'rehmanshareef');
define('DB_PASS', 'Shareef@1');
define('DB_NAME', 'kenefinal');
define('ENCRYPTION_KEY', 'AES-256-CBC');

// echo "<p>✅ view.php is running...</p>"; // Debugging output

// Enable error reporting (for debugging)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to decrypt data
function decryptData($encryptedData) {
    try {
        $rawData = base64_decode($encryptedData);
        if ($rawData === false) {
            throw new Exception('Failed to decode base64 data');
        }

        $ivlen = openssl_cipher_iv_length($cipher = "AES-256-CBC");
        $iv = substr($rawData, 0, $ivlen);
        $encrypted = substr($rawData, $ivlen);
        
        $decrypted = openssl_decrypt(
            $encrypted,
            $cipher,
            ENCRYPTION_KEY,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($decrypted === false) {
            throw new Exception('Decryption failed');
        }

        $decompressed = gzuncompress($decrypted);
        
        if ($decompressed === false) {
            throw new Exception('Decompression failed');
        }

        return $decompressed;
    } catch (Exception $e) {
        die("<p>❌ Error: " . $e->getMessage() . "</p>");
    }
}

// Function to get the latest file for each log type and save it
function downloadLatestFiles() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            throw new Exception('Database connection failed: ' . $conn->connect_error);
        }

        $stmt = $conn->prepare("SELECT id, filedata, LogType, timestamp FROM UploadLogs WHERE (LogType, timestamp) IN (SELECT LogType, MAX(timestamp) FROM UploadLogs GROUP BY LogType)");
        if (!$stmt->execute()) {
            throw new Exception('Failed to retrieve data from database');
        }

        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            throw new Exception('No files found');
        }

        // Define folder paths
        $logDirectories = [
            'firewall logs' => 'logs/firewall_logs',
            'dns query logs' => 'logs/dns_logs',
            'user activity logs' => 'logs/activity',
            'network traffic logs' => 'logs/network',
            'email security logs' => 'logs/email_logs',
            'application logs' => 'logs/application',
            'endpoint security logs' => 'logs/endpoint'
        ];

        while ($file = $result->fetch_assoc()) {
            $decryptedContent = decryptData($file['filedata']);
            $logType = strtolower(trim($file['LogType']));

            $logFolder = isset($logDirectories[$logType]) ? $logDirectories[$logType] : 'logs/misc';

            if (!file_exists($logFolder)) {
                mkdir($logFolder, 0777, true);
            }

            $filename = $logFolder . '/log_' . date('Ymd_His') . '.csv';
            file_put_contents($filename, $decryptedContent);

            // echo "<p>✅ Saved log to: $filename</p>"; // Debugging output
        }

        // Close the database connection
        $stmt->close();
        $conn->close();

    } catch (Exception $e) {
        // die("<p>❌ Error saving file: " . $e->getMessage() . "</p>");
    }
}

// Run function
downloadLatestFiles();

// Print completion message
// echo "<p>✅ view.php execution completed.</p>";
?>
