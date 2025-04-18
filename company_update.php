<?php
ob_start();
// Start session to store temporary messages
session_start();

// Check if the user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    // If not logged in, redirect to login page
    header("Location: index");
    exit;
}
// Check if the user has the specific role (e.g., admin role)
elseif ($_SESSION["userrole"] != 1) {
    // If the user does not have the correct role, redirect to welcome page
    header("Location: home");
    exit;
}

$current_user_id = $_SESSION["userID"];
$current_user_role_id = $_SESSION["userrole"];

$active = "modules";
include "partials/header.php"; 

// Require config file
require_once __DIR__ . '/config.php';

/*===============================
  Function to sanitize user input
=================================*/

function sanitizeInput($input){
    $input = trim($input); //Remove whitespaces from the beginning and end of input
    $input = strip_tags($input); // Remove tags
    $input = stripslashes($input); // Remove backslashes
    $input = htmlspecialchars($input); // Convert special characters to HTML entities to prevent XSS attacks

    return $input;
}

/*===============================
  Function to fetch company data
=================================*/

	if (isset($_GET['id'])){
		$id = intval($_GET['id']);

		// Fetch the existing data for the company
		$sql = "SELECT * FROM ref_companies WHERE id = ?";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("i", $id);
		$stmt->execute();
		$result = $stmt->get_result();

		if ($result->num_rows === 1){
			$company = $result->fetch_assoc();
		} else {
			echo "company not found!";
			exit;
		}

		$stmt->close();
	} else {
		// echo "Invalid parameters!";
		// Redirect to index after form submission
		header("Location: companyManagement");
		exit;
	}

/*===================================
  Query: Update Data into Database
=====================================*/

$errors = []; // Initialize Array to hold error messages 

if($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id'])){
  $current_company_id = intval($_POST['id']);
  $company_name = $_POST['company_name'];
  $address = $_POST['address'];
  $contact = $_POST['contact'];
  $email = $_POST['email'];

  // Validate company_name
  if (isset($company_name)){
      $company_name = sanitizeInput($company_name);
      // validate company_name
      if (!preg_match("/^[a-zA-Z-' ]*$/", $company_name) || empty($company_name)) {
          $errors['company_name'] = "Only letters and white space allowed in company name.";
      }
  }

  // Validate address
  if (isset($address) && !empty($address)) {
      $address = sanitizeInput($address);
      // validate address
      if (!preg_match("/^[a-zA-Z-' ]*$/", $address)) {
          $errors['address'] = "Only letters and white space allowed in address.";
      }
  }

  // Validate contact number
  if (isset($contact)){
      $contact = sanitizeInput($contact);
      // validate contact
      if (!preg_match("/^[0-9]{10,15}$/", $contact) || empty($contact)) {
          $errors['contact'] = "Invalid contact number.";
      }
  }

    // Validate email
    if (isset($email)) {
      $email = sanitizeInput($email);
      // Validate email format
      if (!filter_var($email, FILTER_VALIDATE_EMAIL) || empty($email)) {
          $errors['email'] = "Invalid email format.";
      } else {
          // Check if email already exists in the database but ignore if it belongs to the current company
          $sql = "SELECT id FROM ref_companies WHERE email = ? AND id != ?";
          $stmt = $conn->prepare($sql);
          if ($stmt) {
              $stmt->bind_param("si", $email, $current_company_id);
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  $errors['email'] = "Email address already exists.";
              }
              $stmt->close();
          }
      }
    }

    // Update the user data
    if (empty($errors)) {
      $sql = "UPDATE ref_companies SET company_name = ?, email = ?, address = ?, contact = ? WHERE id = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ssssi", $company_name, $email, $address, $contact, $id);
      if ($stmt->execute()) {
          //Record Activity
          require_once "activityLogsFunction.php";
          $activity_id = 9; // represents the activity ID for updating a company
          saveActivityLog($current_user_role_id, $activity_id, $current_user_id);

          $_SESSION['success'] = "Data updated successfully!";
          header("Location: companyManagement");
          exit;
      } else {
          $errors['update'] = "Update failed: " . $stmt->error;
      }
      $stmt->close();
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
    <li class="breadcrumb-item active" aria-current="page"><h3>Company Details</h3></li>
  </ol>
</nav>

<!-- Begin page content -->
<main role="main" class="flex-shrink-0">
  <div class="container-fluid">
    <div class="card border-success mb-5">
      <div class="card-header" style="color: white; background-color: #28A745;">
        <strong>Update Company</strong>
      </div>
      <div class="card-body">
        <form method="post" action="">
        <input type="hidden" name="id" value="<?php echo isset($id) ? htmlspecialchars($id) : '' ?>"><br>
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <input type="text" name="company_name" placeholder="company_name" class="form-control" value="<?php echo isset($company['company_name']) ? $company['company_name'] : '';?>">
              <span class="errors"><?php echo $errors['company_name'] ?? ''; ?></span>
            </div>

            <div class="form-group">
              <input type="text" name="address" placeholder="Address" class="form-control" value="<?php echo isset($company['address']) ? $company['address'] : '';?>">
              <span class="errors"><?php echo $errors['address'] ?? ''; ?></span>
            </div>
          </div>
          <!-- /.col -->
          <div class="col-md-6">

            <div class="form-group">
              <input type="text" name="contact" id="contact" placeholder="Contact No" class="form-control" 
              value="<?php echo isset($company['contact']) ? $company['contact'] : '';?>">
              <span class="errors"><?php echo $errors['contact'] ?? ''; ?></span>
              <span class="errors" id="contactError"></span>
            </div>

            <div class="form-group">
              <input type="email" name="email" placeholder="Email" class="form-control" value="<?php echo isset($company['email']) ? $company['email'] : '';?>">
              <span class="errors"><?php echo $errors['email'] ?? ''; ?></span>
            </div>

            <?php if (!empty($successMessage)): ?>
            <div class="form-group">
              <div class="alert alert-success alert-dismissible fade show" role="alert">
                  <strong>Administrator:</strong> <span class="success-message"><?php echo $successMessage; ?></span>
                  <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                      <span aria-hidden="true">&times;</span>
                  </button>
              </div>
            </div>
            <?php endif; ?>

          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
		<div class="row">
		  <div class="col-md-6">
		    <a class="btn btn-secondary" href="companyManagement">Back to List</a>
		  </div>
		  <div class="col-md-6 text-right">
		    <button type="submit" class="btn btn-success">Update</button>
		  </div>
		</div>
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
    <script>
      document.getElementById('contactNo').addEventListener('input', function () {
        const contactInput = this.value;
        const errorSpan = document.getElementById('contactError');

        if (contactInput.trim() === '') {
          errorSpan.textContent = 'Contact number is required.';
        } else if (!/^\d*$/.test(contactInput)) {
          errorSpan.textContent = 'Contact number must contain only digits.';
        } else if (!/^0/.test(contactInput)) {
          errorSpan.textContent = 'Contact number must start with 0.';
        } else if (contactInput.length !== 11) {
          errorSpan.textContent = 'Contact number must be exactly 11 digits.';
        } else {
          errorSpan.textContent = ''; // Clear the error message if valid
        }
      });
    </script>
  </body>
</html>