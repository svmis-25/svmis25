<?php
ob_start();

// Start session
session_start();

// Check if user is logged in and has the correct role
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: index.php");
    exit;
}

$current_user_id = $_SESSION["userID"];
$current_user_role_id = $_SESSION["userrole"];

// Include header
include "partials/header.php";

// Database connection
require_once __DIR__ . '/config.php';

// Function to sanitize user input
function sanitizeInput($input){
    $input = trim($input); // Remove whitespaces
    $input = strip_tags($input); // Remove tags
    $input = stripslashes($input); // Remove backslashes
    $input = htmlspecialchars($input); // Convert special characters to HTML entities
    return $input;
}

function generateRandomString($length) {
    $characters = '23456789ABCDEFGHJKLMNOPQRSTUVWXYZ';
    $randomString = '';

    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }

    return $randomString;
}

// Query to retrieve data from database
$sql = "SELECT  
          topup_codes.id AS code_id,
          topup_codes.code,
          topup_codes.amount,
          topup_codes.used,
          topup_codes.created_at,
          users.id AS user_id, 
          users.firstname, 
          users.middlename, 
          users.lastname, 
          users.qualifier 
        FROM topup_codes 
        LEFT JOIN users ON topup_codes.created_by = users.id";

$result = $conn->query($sql);

// Store Data
$data = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'id' => $row['code_id'],
            'code' => $row['code'],
            'used' => $row['used'],
            'amount' => $row['amount'],
            'created_by' => $row['firstname'] . " " . $row['middlename'] . " " . $row['lastname'] . " " . $row['qualifier'],
            'created_at' => $row['created_at'],
        ];
    }
}

// Variables to hold messages
$successMessage = "";
$errors = []; 

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $code_amount = $_POST['code_amount'];
    $code_quantity = $_POST['code_quantity'];

    // Validate code amount
    if (!isset($code_amount) || $code_amount === "") {
        $errors['code_amount'] = "Amount is required.";
    }

    // Validate code quantity
    if (isset($code_quantity)) {
        $code_quantity = sanitizeInput($code_quantity);
        if (!preg_match("/^[0-9]+$/", $code_quantity) || empty($code_quantity)) {
            $errors['code_quantity'] = "Invalid quantity.";
        }
    }

    // If there are no errors, insert data into database
    if (empty($errors)) { 
        $counter = 1;
        $insertedCodes = [];
        while ($counter <= $code_quantity) {
            $done = false;
            while (!$done) {
                // Create the random item code
                $random_code = generateRandomString(10);

                // Check if the newly created random code already exists
                $stmt = $conn->prepare("SELECT * FROM topup_codes WHERE code = ?");
                $stmt->bind_param("s", $random_code);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows == 0) {
                    $done = true;
                }
                $stmt->close();
            }

            // Prepare the SQL query to insert data
            $stmt = $conn->prepare("INSERT INTO topup_codes (code, amount, created_by) VALUES (?, ?, ?)");
            $stmt->bind_param("sii", $random_code, $code_amount, $current_user_id);

            if ($stmt->execute()) {
                $insertedCodes[] = $random_code;
            } else {
                echo "Data insertion failed: " . $stmt->error;
            }

            // Close the statement
            $stmt->close();

            $counter++;
        }

        // Record Activity Log once after all insertions
        if (!empty($insertedCodes)) {
            require_once "activityLogsFunction.php";
            $activity_id = 12; // represents the activity ID for generating topup codes
            saveActivityLog($current_user_role_id, $activity_id, $current_user_id);
        }

        // Set session success message
        $_SESSION['success'] = "Topup codes created successfully!";

        // Redirect to back after form submission
        header("Location: topupCodeManagement"); // Redirect to the same page or desired location
        exit();
    }
}

// Check if there's a success message in session and display it
if (isset($_SESSION['success'])) {
    $successMessage = $_SESSION['success'];
    unset($_SESSION['success']); // Clear session data
}

// Close the database connection
$conn->close();
ob_end_flush();
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item active" aria-current="page"><h3>Topup Code Management</h3></li>
  </ol>
</nav>

<!-- Begin page content -->
<main role="main" class="flex-shrink-0">
  <div class="container-fluid">
    <div class="card border-primary mb-5">
      <div class="card-header" style="color: white; background-color: #007BFF;">
        <strong>Add Topup Code</strong>
      </div>
      <div class="card-body">
        <form method="post" action="">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <select class="form-control" name="code_amount" id="code_amount" required>
                  <option value="">Select amount</option>
                  <option value="100">100</option>      
                  <option value="500">500</option> 
                  <option value="1000">1000</option>   
                </select>
                <span class="errors"><?php echo $errors['code_amount'] ?? ''; ?></span>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <input type="number" class="form-control" id="code_quantity" placeholder="Enter code quantity" name="code_quantity" min="1" required>
                <span class="errors"><?php echo $errors['code_quantity'] ?? ''; ?></span>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-12 text-right">
              <button type="submit" class="btn btn-primary">Submit</button>
            </div>
          </div>
          <?php if (!empty($successMessage)): ?>
            <div class="row mt-3">
              <div class="col-md-12">
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong>Administrator:</strong> <span class="success-message"><?php echo $successMessage; ?></span>
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

    <!-- List of Topup Codes -->
    <div class="card border-secondary mb-3">
      <div class="card-header" style="color: white; background-color: #6C757D;">
        <strong>Topup Codes</strong>
      </div>
        <div class="card-body">
          <div class="table-responsive">
            <table id="tblTopupManagement" class="table table-striped table-bordered table-sm table-hover">
              <thead>
                  <tr class="text-center">
                      <th class="hidden">ID</th>
                      <th>Code</th>
                      <th>Amount</th>
                      <th>Status</th>
                      <th>Date Created</th>
                      <th>Created By</th>
                  </tr>
              </thead>
              <tbody class="text-center">
                  <?php if (isset($data) && is_array($data)): ?>
                    <?php foreach ($data as $row): ?>
                        <tr>
                            <td class="hidden"><?php echo htmlspecialchars($row['id']) ?? ''; ?></td>
                            <td><?php echo htmlspecialchars($row['code']) ?? ''; ?></td>
                            <td><?php echo htmlspecialchars($row['amount']) ?? ''; ?></td>
                            <td class="text-center"><?php echo htmlspecialchars($row['used']) ? '<span class="badge badge-danger">Used</span>' : '<span class="badge badge-success">Available</span>'; ?></td>
                            <td><?php echo htmlspecialchars($row['created_at']) ?? ''; ?></td>
                            <td><?php echo htmlspecialchars($row['created_by']) ?? ''; ?></td>
                        </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
              </tbody>
            </table>
          </div>
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
          $('#tblTopupManagement').DataTable({
              "order": [[0, "desc"]],
              // Customize the layout to move the search box to the right
              "dom": '<"d-flex justify-content-between"lfr>t<"d-flex justify-content-between"ip>',
          });
      });
    </script>
  </body>
</html>
