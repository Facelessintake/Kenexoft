<?php
session_start();
$host = 'localhost';
$dbname = 'kenefinal';
$db_username = 'rehmanshareef';
$db_password = 'Shareef@1';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch(PDOException $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    die("Database connection error. Please try again later.");
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Update the form handling section
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $update_query = "UPDATE UserDetails SET 
            FirstName = :firstName,
            LastName = :lastName,
            Email = :email,
            CompanyName = :companyName,
            Address = :address,
            PhoneNumber = :phoneNumber,
            AreaCode = :areaCode,
            CountryCode = :countryCode
            WHERE UserID = :userId";

        $stmt = $pdo->prepare($update_query);
        
        $stmt->execute([
            ':firstName' => $_POST['firstName'],
            ':lastName' => $_POST['lastName'],
            ':email' => $_POST['email'],
            ':companyName' => $_POST['companyName'],
            ':address' => $_POST['address'],
            ':phoneNumber' => $_POST['phoneNumber'],
            ':areaCode' => $_POST['areaCode'],
            ':countryCode' => $_POST['countryCode'],
            ':userId' => $userId
        ]);

        $_SESSION['success_message'] = "Profile updated successfully!";
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    } catch(PDOException $e) {
        $error_message = "Error updating profile: " . $e->getMessage();
    }
}

// Display success message from session
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Fetch current user data
try {
    $query = "SELECT * FROM UserDetails WHERE UserID = :userId";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':userId' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_message = "Error fetching user data: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .disabled-input {
            background-color: #e9ecef;
            pointer-events: none;
        }
        .edit-button {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        .phone-input-group {
            display: flex;
            gap: 10px;
        }
        .phone-input-group input {
            flex: 1;
        }
        .phone-input-group input:first-child {
            max-width: 100px;
        }
        .phone-input-group input:nth-child(2) {
            max-width: 100px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #343a40;">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <img src="logo.png" alt="Logo" style="height: 40px;">
            </a>
            <!-- ...existing navbar code... -->
        </div>
    </nav>

    <div class="container mt-5 position-relative">
        <a href="dashboard.php" class="btn btn-secondary back-button">Back to Dashboard</a>
        <button id="editButton" class="btn btn-primary edit-button" onclick="enableEditing()">Edit</button>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="p-3 py-5">
            <h4 class="mb-4">Profile Settings</h4>
            <form method="POST" action="" id="profileForm" name="profileForm">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">First Name *</label>
                        <input type="text" class="form-control disabled-input" name="firstName" id="firstName" value="<?php echo htmlspecialchars($user['FirstName']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Last Name *</label>
                        <input type="text" class="form-control disabled-input" name="lastName" id="lastName" value="<?php echo htmlspecialchars($user['LastName']); ?>" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Username *</label>
                        <input type="text" class="form-control disabled-input" name="username" id="username" value="<?php echo htmlspecialchars($user['Username']); ?>" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Email *</label>
                        <input type="email" class="form-control disabled-input" name="email" id="email" value="<?php echo htmlspecialchars($user['Email']); ?>" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Phone Number *</label>
                        <div class="phone-input-group">
                            <input type="text" class="form-control disabled-input" name="countryCode" id="country-code" value="<?php echo htmlspecialchars($user['CountryCode']); ?>" placeholder="Country Code">
                            <input type="text" class="form-control disabled-input" name="areaCode" id="area-code" value="<?php echo htmlspecialchars($user['AreaCode']); ?>" placeholder="Area Code">
                            <input type="tel" class="form-control disabled-input" name="phoneNumber" id="phone" value="<?php echo htmlspecialchars($user['PhoneNumber']); ?>" placeholder="Phone Number">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Company Name</label>
                        <input type="text" class="form-control disabled-input" name="companyName" id="company" value="<?php echo htmlspecialchars($user['CompanyName']); ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Address</label>
                        <textarea class="form-control disabled-input" name="address" id="address" rows="3"><?php echo htmlspecialchars($user['Address']); ?></textarea>
                    </div>

                    <div class="col-12 mt-4 text-center">
                        <button type="submit" class="btn btn-primary" id="saveButton" style="display: none;">Save Changes</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function enableEditing() {
            const inputs = document.querySelectorAll('#profileForm input:not([readonly]), #profileForm textarea');
            inputs.forEach(input => {
                input.classList.remove('disabled-input');
            });
            document.getElementById('editButton').style.display = 'none';
            document.getElementById('saveButton').style.display = 'block';
        }

        // Initialize form fields
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('#profileForm input:not([readonly]), #profileForm textarea');
            inputs.forEach(input => {
                input.classList.add('disabled-input');
            });

            // Add form submit handler
            document.getElementById('profileForm').addEventListener('submit', function(e) {
                const inputs = document.querySelectorAll('#profileForm input:not([readonly]), #profileForm textarea');
                inputs.forEach(input => {
                    input.removeAttribute('disabled');
                });
            });
        });

        // Keep username field readonly
        document.getElementById('username').setAttribute('readonly', 'readonly');
    </script>
</body>
</html>
