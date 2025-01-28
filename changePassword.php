<?php
ob_start();
session_start();
// Check if the user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: index");
    exit;
}
$current_user_id = $_SESSION["userID"];
$current_user_role_id = $_SESSION["userrole"];

include "partials/header.php"; 
require_once __DIR__ . '/config.php';

// Fetch user ID from session
$userID = $_SESSION["userID"];

// Function to validate password complexity
function validatePassword($password){
    // Password must be at least 12 characters long and contain upper and lower case letters, numbers, and special characters
    return strlen($password) >= 12
        && preg_match('/[A-Z]/', $password)
        && preg_match('/[a-z]/', $password)
        && preg_match('/[0-9]/', $password)
        && preg_match('/[!@#$%^&()-_+]/', $password);
}

// Initialize variables for error messages and success message
$changeError = $successMessage = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    // Validate input
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $changeError = "All fields are required.";
    } elseif ($newPassword !== $confirmPassword) {
        $changeError = "New passwords do not match.";
    } elseif (!validatePassword($newPassword)) {
        $changeError = "New password must be at least 12 characters long and include upper and lower case letters, numbers, and special characters.";
    } else {
        // Check current password and update with new password
        $sql = "SELECT password FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userID);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($currentPassword, $user['password'])) {
                $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateSql = "UPDATE users SET password = ? WHERE id = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param("si", $hashedNewPassword, $userID);

                if ($updateStmt->execute()) {

                    //Record Activity
                    require_once "activityLogsFunction.php";
                    $activity_id = 7; // represents the activity ID for changing password
                    saveActivityLog($current_user_role_id, $activity_id, $current_user_id);

                    $successMessage = "Password updated successfully!";
                } else {
                    $changeError = "Failed to update password.";
                }

                $updateStmt->close();
            } else {
                $changeError = "Current password is incorrect.";
            }
        } else {
            $changeError = "User not found.";
        }

        $stmt->close();
    }
}
$conn->close();
ob_end_flush();
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item active" aria-current="page"><h3>Change Password</h3></li>
  </ol>
</nav>

<!-- Begin page content -->
<main role="main" class="flex-shrink-0">
  <div class="container-fluid">
    <div class="card border-success mb-5">
      <div class="card-header" style="color: white; background-color: #28A745;">
        <strong>Change Your Password</strong>
      </div>
      <div class="card-body">
        <form method="post" action="">
        <div class="row">
          <div class="col-md-12">
            <div class="form-group">
                <label for="current_password">Current Password:</label>
                <input type="password" id="current_password" name="current_password" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="new_password">New Password:</label>
                <input type="password" id="new_password" name="new_password" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm New Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
            </div>
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
        <div class="row">
          <div class="col-md-6">
            <a class="btn btn-secondary" href="home">Back to Home</a>
          </div>
          <div class="col-md-6 text-right">
            <button type="submit" class="btn btn-success">Submit</button>
          </div>
        </div>
        <?php if (!empty($successMessage)): ?>
          <div class="row mt-3">
            <div class="col-md-12">
              <div class="alert alert-success alert-dismissible fade show" role="alert">
                  <strong>Administrator:</strong> <span class="success-message"><?php echo htmlspecialchars($successMessage); ?></span>
                  <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                      <span aria-hidden="true">&times;</span>
                  </button>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <?php if (!empty($changeError)): ?>
          <div class="row mt-3">
            <div class="col-md-12">
              <div class="alert alert-danger alert-dismissible fade show" role="alert">
                  <strong>Administrator:</strong> <span class="danger-message"><?php echo htmlspecialchars($changeError); ?></span>
                  <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                      <span aria-hidden="true">&times;</span>
                  </button>
              </div>
            </div>
          </div>
        <?php endif; ?>
      </form>
      </div>
      <!-- /.card-body -->
    </div>
    <!-- /.card -->
  </div>
  <!-- /.container-fluid -->
</main>

<?php include "partials/footer.php"; ?>

<!-- Bootstrap JS and any other script -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>    
    <script src="https://cdn.datatables.net/1.11.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.4/js/dataTables.bootstrap4.min.js"></script>
    <script>
      $(document).ready(function () {
          $('#tblUserManagement').DataTable({
              "order": [[0, "asc"]],
              // Customize the layout to move the search box to the right
              "dom": '<"d-flex justify-content-between"lfr>t<"d-flex justify-content-between"ip>',
          });
      });
    </script>
  </body>
</html>