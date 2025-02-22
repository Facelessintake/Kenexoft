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

// Run view.php and combined.py on page load
include 'view.php';
$output = shell_exec('python combined.py 2>&1'); // Ensure Python is installed and configured correctly

// Log types for the dashboard
$log_types = ['application', 'dns', 'email', 'endpoint', 'firewall', 'network'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Logs Analysis Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {
      background-color: #f8f9fa;
      font-family: Arial, sans-serif;
    }
    
    .navbar {
      background-color: #343a40;
    }
    .card {
      border: none;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      border-radius: 10px;
    }
    .logo {
            height: 50px;
            margin: 0 auto;
        }
    img {
      max-width: 100%;
      height: auto;
      border: 2px solid #000;
    }
    .carousel {
      max-width: 800px;
      margin: auto;
      position: relative;
    }
    .carousel-inner {
      text-align: center;
    }
    .carousel-control-prev, .carousel-control-next {
      width: 50px;
      height: 50px;
      background-color: grey;
      border-radius: 50%;
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
    }
    .carousel-control-prev { left: -60px; }
    .carousel-control-next { right: -60px; }
  </style>
</head>
<body>
<?php
if (isset($_SESSION['script_status'])) {
    echo "<div class='alert alert-info'>" . $_SESSION['script_status'] . "</div>";
    unset($_SESSION['script_status']); // Remove message after showing it once
}
?>

  <nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
      <a class="navbar-brand" href="#">
        <img src="Knxt_Shield_Logo_003C_T.png" alt="Logo" class="logo">
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav">
          <li class="nav-item"><a class="nav-link active" href="#">Dashboard</a></li>
          <li class="nav-item"><a class="nav-link" href="upload_logs.php">Upload Logs</a></li>
          <li class="nav-item"><a class="nav-link" href="view_profile.php">User Profile</a></li>
        </ul>
        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <form method="POST" action="login.php">
              <button type="submit" class="btn btn-danger">Logout</button>
            </form>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="container py-4">
    <h1 class="text-center mb-4">Logs Analysis Dashboard</h1>

    <form method="GET">
      <button type="submit" name="generate_graphs" class="btn btn-success d-block mx-auto">Generate Graphs</button>
    </form>

    <?php if (isset($_GET['generate_graphs'])): ?>
      <div id="logCarousel" class="carousel slide mt-4" data-bs-ride="carousel">
        <div class="carousel-inner">
          <?php foreach ($log_types as $index => $log_type): ?>
            <?php $imagePath = "static/$log_type/anomalies.png"; ?>
            <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
              <div class="card">
                <div class="card-body">
                  <h5 class="card-title"><?php echo ucfirst($log_type); ?> Anomaly Detection</h5>
                  <?php if (file_exists($imagePath)): ?>
                    <img src="<?php echo $imagePath . '?t=' . time(); ?>" alt="<?php echo ucfirst($log_type); ?> Anomalies">
                  <?php else: ?>
                    <p style="color: red;">Error: Anomaly graph not found for <?php echo ucfirst($log_type); ?>.</p>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#logCarousel" data-bs-slide="prev">
          <span class="carousel-control-prev-icon" aria-hidden="true"></span>
          <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#logCarousel" data-bs-slide="next">
          <span class="carousel-control-next-icon" aria-hidden="true"></span>
          <span class="visually-hidden">Next</span>
        </button>
      </div>
    <?php endif; ?>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
