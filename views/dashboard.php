<?php

declare(strict_types=1);

/**
 * Dashboard - Main interface for testing Support Mail Assistant
 */

require_once __DIR__ . '/../bootstrap.php';

// Check admin key
$adminKey = Env::get('ADMIN_SHARED_KEY');
if (!$adminKey || $_GET['key'] !== $adminKey) {
    http_response_code(403);
    echo "Lỗi: Không có quyền truy cập";
    exit;
}

// Check OAuth status
$tokenPath = __DIR__ . '/../credentials/token.json';
$isAuthenticated = false;
$tokenInfo = '';

if (file_exists($tokenPath)) {
    $tokenData = json_decode(file_get_contents($tokenPath), true);
    if ($tokenData && isset($tokenData['access_token'])) {
        $isAuthenticated = true;
        $tokenInfo = "Token expires: " . date('Y-m-d H:i:s', $tokenData['created'] + ($tokenData['expires_in'] ?? 3600));
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Mail Assistant - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .status-card {
            margin-bottom: 20px;
        }

        .action-btn {
            margin: 5px;
        }

        .token-info {
            font-size: 0.9em;
            color: #666;
        }
    </style>
</head>

<body>
    <div class="container mt-4">
        <h1 class="mb-4">📧 Support Mail Assistant Dashboard</h1>

        <!-- Status Card -->
        <div class="card status-card">
            <div class="card-header">
                <h5>🔐 Authentication Status</h5>
            </div>
            <div class="card-body">
                <?php if ($isAuthenticated): ?>
                    <div class="alert alert-success">
                        <strong>✅ Authenticated</strong>
                        <div class="token-info"><?php echo htmlspecialchars($tokenInfo); ?></div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <strong>⚠️ Not Authenticated</strong>
                        <div>You need to authenticate with Google OAuth first.</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Actions Card -->
        <div class="card status-card">
            <div class="card-header">
                <h5>🚀 Actions</h5>
            </div>
            <div class="card-body">
                <?php if ($isAuthenticated): ?>
                    <a href="../oauth/start.php" class="btn btn-primary action-btn">🔄 Re-authenticate</a>
                    <a href="../endpoints/check_and_draft.php?key=<?php echo urlencode($adminKey); ?>" class="btn btn-success action-btn">📝 Create Draft</a>
                    <a href="../endpoints/list_drafts.php?key=<?php echo urlencode($adminKey); ?>" class="btn btn-info action-btn">📋 List Drafts</a>
                <?php else: ?>
                    <a href="../oauth/start.php" class="btn btn-primary action-btn">🔑 Start OAuth</a>
                <?php endif; ?>

                <button class="btn btn-danger action-btn" onclick="resetApplication()">🗑️ Reset Application</button>
            </div>
        </div>

        <!-- Test Results Card -->
        <div class="card status-card">
            <div class="card-header">
                <h5>🧪 Test Results</h5>
            </div>
            <div class="card-body">
                <div id="testResults">
                    <p class="text-muted">Click actions above to see test results here.</p>
                </div>
            </div>
        </div>

        <!-- Configuration Card -->
        <div class="card status-card">
            <div class="card-header">
                <h5>⚙️ Configuration</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Environment Variables:</h6>
                        <ul class="list-unstyled">
                            <li><strong>APP_BASE_URL:</strong> <?php echo htmlspecialchars(Env::get('APP_BASE_URL', 'Not set')); ?></li>
                            <li><strong>ADMIN_SHARED_KEY:</strong> <?php echo htmlspecialchars(Env::get('ADMIN_SHARED_KEY', 'Not set')); ?></li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Knowledge Base:</h6>
                        <ul class="list-unstyled">
                            <li><strong>Allowed Senders:</strong> <?php
                                                                    try {
                                                                        $knowledge = new Knowledge();
                                                                        $senders = $knowledge->getAllowedSenders();
                                                                        echo htmlspecialchars(implode(', ', $senders));
                                                                    } catch (Exception $e) {
                                                                        echo 'Error: ' . htmlspecialchars($e->getMessage());
                                                                    }
                                                                    ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function resetApplication() {
            if (confirm('⚠️ This will delete all OAuth tokens and reset the application to initial state.\n\nAre you sure you want to continue?')) {
                // Send reset request
                fetch('../endpoints/reset.php?key=<?php echo urlencode($adminKey); ?>', {
                        method: 'POST'
                    })
                    .then(response => response.text())
                    .then(data => {
                        alert('Application reset successfully! Please refresh the page.');
                        location.reload();
                    })
                    .catch(error => {
                        alert('Error resetting application: ' + error);
                    });
            }
        }

        // Auto-refresh status every 30 seconds
        setInterval(() => {
            // You can add AJAX calls here to refresh status
        }, 30000);
    </script>
</body>

</html>