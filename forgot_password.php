<?php
// Include the config file
require_once __DIR__ . '/config.php'; // Include DB connection
// Include the Composer autoloader
require 'vendor/autoload.php';  // Path to the Composer autoload file
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load .env file using vlucas/phpdotenv
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$baseURL = $_ENV['BASE_URL'];
$phpmailer_host = $_ENV['PHPMAILER_HOST'];
$phpmailer_port = $_ENV['PHPMAILER_PORT'];
$phpmailer_username = $_ENV['PHPMAILER_USERNAME'];
$phpmailer_password = $_ENV['PHPMAILER_PASSWORD'];

$errors = "";
$success = "";

// Check if the form has been submitted
if (isset($_POST['submit'])) {
    // Get the email from the form
    $email = $_POST['email'];

    // Check if the email exists in the database
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        // Generate a unique reset token
        $token = bin2hex(random_bytes(50)); // Generate a random token
        $expiry = date("Y-m-d H:i:s", strtotime("+1 hour")); // Token expiry time (1 hour)

        // Update the token and expiry time in the database
        $update_sql = "UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE email = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("sss", $token, $expiry, $email);
        $update_stmt->execute();

        // Create the reset link
        $reset_link = "$baseURL/reset_password.php?token=$token"; // Adjust the URL to your environment

        $phpmailer = new PHPMailer();

        try {
            // Mail Server settings
            $phpmailer->isSMTP();
            $phpmailer->Host = $phpmailer_host;
            $phpmailer->SMTPAuth = true;
            $phpmailer->Port = $phpmailer_port;
            $phpmailer->Username = $phpmailer_username;
            $phpmailer->Password = $phpmailer_password;
            $phpmailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

            // Sender and recipient
            $phpmailer->setFrom('no-reply@demomailtrap.com', 'SVMIS');
            $phpmailer->addAddress('svmis.official@gmail.com');

            // Content
            $phpmailer->isHTML(true);
            $phpmailer->Subject = 'Password Reset Requestsssss';
            // $phpmailer->Body = "Click the link to reset your password: <a href=\"$reset_link\">Reset Password</a>";
            $phpmailer->Body = "
                <p>Hello $user[firstname] $user[lastname],</p>
                <p>We received a request to reset your password. If you made this request, please click the link below to set a new password:</p>
                <p><a href=\"$reset_link\" style=\"color: #ffffff; background-color: #007bff; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;\">Reset Password</a></p>
                <p>If you did not request a password reset, please report this immediately to our support team.</p>
                <p>Thank you,</p>
                <p>SVMIS</p>
            ";

            if ($phpmailer->send()) {
                $success = 'Password reset link has been sent to your email.';
            } else {
                $errors = 'Message could not be sent. Mailer Error: ' . $phpmailer->ErrorInfo;
            }
        } catch (Exception $e) {
            $errors = 'Message could not be sent. PHPMailer Error: ' . $phpmailer->ErrorInfo;
        }
        $stmt->close();
    } else {
        $errors = 'No user found with that email address.';
    }
}

$conn->close();
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Forgot Password</title>

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

    <form class="form-signin" method="POST" action="">
      <div class="text-center">
        <img src="assets/images/logo4.png" alt="SVMIS Logo">
        <h4>Forget Password?</h4>
      </div>
        
        <h6 class="mb-4 text-center" style="padding-top: 5px;">Enter your e-mail address below to reset your password.</h6>

        <div class="form-group">
            <input type="email" id="email" name="email" class="form-control" placeholder="Email address" required autofocus>
        </div>

        <button class="btn btn-lg btn-primary btn-block" type="submit" name="submit">Submit</button>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $errors; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <a href="index" class="btn mb-0 text-primary">Go to Login</a>

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
</body>
</html>
