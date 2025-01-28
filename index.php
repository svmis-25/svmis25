<?php
session_start();
session_unset();  // Clear all session data
session_regenerate_id(true); // Regenerate session ID for security

// require_once 'config.php';
require_once __DIR__ . '/config.php';

// Check if the user is already logged in
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("Location: command_center");
    exit;
}

$loginError = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $loginError = "Please enter username and password.";
    } else {
        $sql = "SELECT 
            users.id as user_id, 
            users.email as email, 
            users.firstname as firstname, 
            users.lastname as lastname, 
            users.role_id as role_id, 
            users.failed_login_attempts as failed_login_attempts,
            users.password as password,
            users.is_active as user_is_active,
            ref_roles.role_name as role_name,
            ref_roles.is_active as role_is_active
        FROM users 
        LEFT JOIN ref_roles ON users.role_id = ref_roles.id
        WHERE users.email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if ($user['user_is_active'] == 0) {
                $loginError = "Your account has been disabled. Please contact the administrator.";
            } else {
                if (password_verify($password, $user['password'])) {
                    $updateSql = "UPDATE users SET failed_login_attempts = 0 WHERE email = ?";
                    $updateStmt = $conn->prepare($updateSql);
                    $updateStmt->bind_param("s", $email);
                    $updateStmt->execute();
                    $updateStmt->close();

                    // echo "Fetched user ID: " . $user['user_id']; // Debugging line
                    // echo "Fetched user Email: " . $user['email']; // Debugging line

                    $_SESSION["useremail"] = $email;
                    $_SESSION["loggedin"] = true;
                    $_SESSION["userdetails"] = $user['firstname'] . " " . $user['lastname'];
                    $_SESSION["userrole"] = $user['role_id'];
                    $_SESSION["userroleDesc"] = $user['role_name'];
                    $_SESSION["userID"] = $user['user_id'];

                    require_once "activityLogsFunction.php";
                    $activity_id = 1;
                    saveActivityLog($user['role_id'], $activity_id, $user['user_id']);

                    header("Location: command_center");
                    exit();
                } else {
                    $failedAttempts = $user['failed_login_attempts'] + 1;

                    if ($failedAttempts >= 3) {
                        $updateSql = "UPDATE users SET is_active = 0, failed_login_attempts = ? WHERE email = ?";
                        $loginError = "Your account has been disabled due to multiple failed login attempts.";
                    } else {
                        $updateSql = "UPDATE users SET failed_login_attempts = ? WHERE email = ?";
                        $loginError = "Invalid credentials! Attempt $failedAttempts of 3.";
                    }

                    $updateStmt = $conn->prepare($updateSql);
                    $updateStmt->bind_param("is", $failedAttempts, $email);
                    $updateStmt->execute();
                    $updateStmt->close();
                }
            }
        } else {
            $loginError = "Invalid credentials!";
        }

        $stmt->close();
    }
}
$conn->close();
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Login</title>

    <!-- Favicon -->
    <link rel="icon" href="assets/images/banner.png" type="image/x-icon">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- FontAwesome CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">


    <style>
        html, body {
            height: 100%;
            background-color: #f1f1f1;
        }

        body {
          display: flex;
          justify-content: center;
          align-items: center;
          background-color: #f5f5f5;
          padding: 0;
          margin: 0;
        }

        .form-signin {
            width: 100%;
            max-width: 400px;
            padding: 20px;
            margin: auto;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .form-signin img {
          width: 200px; /* Adjust the logo size */
          margin-bottom: 15px;
        }
        .form-signin h1 {
          margin-bottom: 20px;
        }

        .form-signin .form-control {
            height: 45px;
            border-radius: 5px;
            padding: 10px;
        }

        .form-signin input[type="email"],
        .form-signin input[type="password"] {
            margin-bottom: 15px;
        }

        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            transition: background-color 0.3s, border-color 0.3s;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #004494;
        }

        .custom-checkbox {
            margin-bottom: 10px;
        }

        .text-muted {
            margin-top: 10px;
        }

        .alert {
            margin-top: 20px;
        }

        @media (max-width: 576px) {
            .form-signin {
                padding: 15px;
            }
        }
    </style>
</head>
<body>

    <form class="form-signin" method="POST">
      <div class="text-center">
        <img src="assets/images/logo4.png" alt="SVMIS Logo">
        <h4>Smart Van Management Information System <br>(SVMIS ver 2.0)</h4>
      </div>
        
        <h6 class="mb-4 text-center" style="padding-top: 5px;">Sign in to start your session</h6>

        <div class="form-group">
            <input type="email" id="email" name="email" class="form-control" placeholder="Email address" required autofocus>
        </div>

        <div class="form-group">
            <input type="password" id="password" name="password" class="form-control" placeholder="Password" required>
        </div>

        <div class="row mb-3">
            <div class="col-md-6 d-flex align-items-center">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="checkPassword">
                    <label class="custom-control-label" for="checkPassword">Show Password</label>
                </div>
            </div>
            <div class="col-md-6 d-flex align-items-center justify-content-end">
                <a href="forgot_password" class="btn mb-0">Forgot Password</a>
            </div>
        </div>

        <button class="btn btn-lg btn-primary btn-block" type="submit">Sign in</button>

        <?php if (!empty($loginError)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $loginError; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <p class="text-muted text-center">&copy; <span id="currentYear"></span></p>
    </form>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script>
        const password = document.getElementById('password');
        const checkPassword = document.getElementById('checkPassword');

        checkPassword.onchange = function() {
            password.type = checkPassword.checked ? 'text' : 'password';
        };
    </script>
    <script>
        // Get the current year dynamically and set it in the span
        document.getElementById('currentYear').textContent = new Date().getFullYear();
    </script>
</body>
</html>
