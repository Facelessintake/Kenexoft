<?php
session_start();

// Check if user is logged in and session timeout
$session_timeout = 1800; // 30 minutes
if (!isset($_SESSION['username']) || !isset($_SESSION['user_plan']) || 
    !isset($_SESSION['last_activity']) || 
    (time() - $_SESSION['last_activity'] > $session_timeout)) {
    session_destroy();
    header("Location: login.php");
    exit();
}
$_SESSION['last_activity'] = time();

// Database configuration
$host = 'localhost';
$dbname = 'shield_integration_db';
$db_username = 'data_base';
$db_password = 'Shield_123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch(PDOException $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    die("Database connection error. Please try again later.");
}

// Verify user's plan and active status
$stmt = $pdo->prepare("
    SELECT p.PlanName, p.EndDate
    FROM Plan p 
    INNER JOIN UserDetails u ON p.UserID = u.UserID 
    WHERE u.Username = :username 
    AND p.Status = 'active' 
    AND p.EndDate >= CURRENT_DATE
    ORDER BY p.EndDate DESC
    LIMIT 1
");

try {
    $stmt->execute([':username' => $_SESSION['username']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        session_destroy();
        header("Location: login.php");
        exit();
    }

    // Update session plan if it has changed
    $_SESSION['user_plan'] = $user['PlanName'];
} catch(PDOException $e) {
    error_log("Plan verification error: " . $e->getMessage());
    die("Error verifying user plan");
}

// Define allowed pages for each plan
$allowedPages = [
    'Essential' => ['dashboard.php', 'essential.php'],
    'Professional' => ['dashboard.php', 'Professional.html'],
    'Enterprise' => ['dashboard.php', 'Enterprise.html']
];

// Set up target pages for navigation

$plan = $_SESSION['user_plan'];
$upload_logs_page = isset($target_pages[$plan]) ? $target_pages[$plan] : 'upload_logs.php';

// Fetch actual metrics from database
try {
    $metricsStmt = $pdo->prepare("
        SELECT 
            COUNT(CASE WHEN type = 'anomaly' THEN 1 END) as anomalies_count,
            COUNT(*) as total_logs
        FROM Logs 
        WHERE UserID = :user_id
    ");
    $metricsStmt->execute([':user_id' => $_SESSION['user_id']]);
    $metrics = $metricsStmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Metrics fetch error: " . $e->getMessage());
    $metrics = ['anomalies_count' => 0, 'total_logs' => 0];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Logs Analysis Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa; /* Light Grey */
      font-family: Arial, sans-serif;
    }

    h1 {
      color: #343a40;
    }

    .card {
      border: none;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      border-radius: 10px;
    }

    .card-title {
      color: #495057;
      font-weight: bold;
    }

    .card-text {
      color: #6c757d;
    }

    canvas {
      background-color: #ffffff;
      border-radius: 8px;
    }

    .navbar {
      background-color: #343a40; /* Darker grey */
    }

    .navbar-brand img {
      height: 40px;
    }

    .navbar-nav .nav-link {
      color: #e9ecef;
      font-weight: bold;
    }

    .navbar-nav .nav-link.active {
      color: #ffffff; /* Highlight active page with white */
      font-weight: bolder;
    }
  </style>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
      <a class="navbar-brand" href="#">
        <img src="logo.png" alt="Logo">
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav">
          <li class="nav-item">
            <a class="nav-link active" href="#">Dashboard</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="<?php echo htmlspecialchars($upload_logs_page); ?>" >Upload Logs</a> <!-- Added option to redirect to Upload Logs -->
           
          </li>
          <li class="nav-item">
            <a class="nav-link" href="user-profile.html">User Profile</a>
          </li>
        </ul>
        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <a class="nav-link" href="login.php">Logout</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="container py-4">
    <h1 class="text-center mb-4">Logs Analysis Dashboard</h1>

    <div class="row">
      <!-- Box for Anomalies and Logs -->
      <div class="col-lg-4 col-md-12 mb-4">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">System Metrics</h5>
            <p class="card-text">
              <strong>Anomalies:</strong> <span id="anomalies-count">42</span><br>
              <strong>Logs:</strong> <span id="logs-count">1024</span>
            </p>
          </div>
        </div>
      </div>

      <!-- Graph 1 -->
      <div class="col-lg-8 col-md-12 mb-4">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">Trend Analysis</h5>
            <canvas id="graph1"></canvas>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <!-- Graph 2 -->
      <div class="col-lg-6 col-md-12 mb-4">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">Error Categories</h5>
            <canvas id="graph2"></canvas>
          </div>
        </div>
      </div>

      <!-- Graph 3 -->
      <div class="col-lg-6 col-md-12 mb-4">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">Proportions</h5>
            <canvas id="graph3"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Graph 1: Line Chart for Trend Analysis
    new Chart(document.getElementById('graph1').getContext('2d'), {
      type: 'line',
      data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
        datasets: [{
          label: 'Log Count',
          data: [150, 200, 180, 220, 170, 210],
          borderColor: 'rgba(75, 192, 192, 1)',
          borderWidth: 2,
          fill: false
        }]
      }
    });

    // Graph 2: Line Chart for Error Categories
    new Chart(document.getElementById('graph2').getContext('2d'), {
      type: 'line',
      data: {
        labels: ['Critical', 'Warning', 'Info', 'Debug'],
        datasets: [{
          label: 'Occurrences',
          data: [50, 120, 300, 80],
          borderColor: 'rgba(255, 99, 132, 1)',
          borderWidth: 2,
          fill: false
        }]
      }
    });

    // Graph 3: Line Chart for Proportions
    new Chart(document.getElementById('graph3').getContext('2d'), {
      type: 'line',
      data: {
        labels: ['Processed', 'Pending', 'Failed'],
        datasets: [{
          label: 'Logs',
          data: [60, 25, 15],
          borderColor: '#4caf50',
          borderWidth: 2,
          fill: false
        }]
      }
    });
  </script>
</body>

</html>
