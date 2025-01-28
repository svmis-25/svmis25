<?php
// reset-password.php

require_once __DIR__ . '/config.php'; // Include DB connection

$errors = "";
$success = "";

// Check if the token is provided
if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Check if the token is valid and not expired
    $sql = "SELECT * FROM users WHERE reset_token = ? AND reset_token_expiry > NOW()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        // Show password reset form
        if (isset($_POST['reset_password'])) {
            $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT); // Hash the new password

            // Update the password in the database
            $update_sql = "UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE reset_token = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ss", $new_password, $token);
            $update_stmt->execute();

            $success = 'Your password has been reset successfully.';
        }
    } else {
        $errors = 'Invalid or expired token.';
    }
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Reset Password</title>

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

    <form class="form-signin" action="reset_password.php?token=<?php echo $token; ?>" method="POST">
      <div class="text-center">
        <img src="assets/images/logo4.png" alt="SVMIS Logo">
        <h4>Set New Password</h4>
      </div>
        
        <h6 class="mb-4 text-center" style="padding-top: 5px;">Enter your new password and confirm it.</h6>

        <?php if (empty($success)): ?>
           <div class="form-group">
            <input type="password" id="new_password" name="new_password" class="form-control" placeholder="New Password" required autofocus>
            </div>

            <div class="form-group">
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Confirm Password" required>
            </div>

            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="checkPassword">
                <label class="custom-control-label" for="checkPassword">Show Password</label>
            </div>

            <button class="btn btn-lg btn-primary btn-block" type="submit" name="reset_password">Reset Password</button> 
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $errors; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <a href="index" class="btn btn-lg btn-primary btn-block mb-0">Go to Login</a>

            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <p class="text-muted text-center">&copy; 2024</p>
    </form>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script>
        const new_password = document.getElementById('new_password');
        const confirm_password = document.getElementById('confirm_password');

        const checkPassword = document.getElementById('checkPassword');

        checkPassword.onchange = function() {
            new_password.type = checkPassword.checked ? 'text' : 'password';
            confirm_password.type = checkPassword.checked ? 'text' : 'password';
        };
    </script>
</body>
</html>

