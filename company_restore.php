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
  Query: Delete Data from Database
=====================================*/

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id'])) {
    $id = intval($_POST['id']);

    // Update the user data
    if ($id) {
        $sql = "UPDATE ref_companies SET is_active = 1 WHERE id = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            // Bind the ID parameter
            $stmt->bind_param("i", $id);

            // Execute the statement
            if ($stmt->execute()) {
                // Record Activity
                require_once "activityLogsFunction.php";
                $activity_id = 11; // Represents the activity ID for deleting a company
                saveActivityLog($current_user_role_id, $activity_id, $current_user_id);

                $_SESSION['success'] = "Company rerstored successfully!";
                header("Location: companyManagement");
                exit;
            } else {
                $errors['update'] = "Update failed: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $errors['prepare'] = "Failed to prepare the statement.";
        }
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
    <div class="card border-info mb-5">
      <div class="card-header" style="color: white; background-color: #17a2b8;">
        <strong>Restore Company</strong>
      </div>
      <div class="card-body">
        <form method="post" action="">
        <input type="hidden" name="id" value="<?php echo isset($id) ? htmlspecialchars($id) : '' ?>"><br>
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <input type="text" name="company_name" placeholder="company_name" class="form-control" value="<?php echo isset($company['company_name']) ? $company['company_name'] : '';?>" readonly>
            </div>

            <div class="form-group">
              <input type="text" name="address" placeholder="Address" class="form-control" value="<?php echo isset($company['address']) ? $company['address'] : '';?>" readonly>
            </div>
          </div>
          <!-- /.col -->
          <div class="col-md-6">

            <div class="form-group">
              <input type="text" name="contact" id="contact" placeholder="Contact No" class="form-control" 
              value="<?php echo isset($company['contact']) ? $company['contact'] : '';?>" readonly>
            </div>

            <div class="form-group">
              <input type="email" name="email" placeholder="Email" class="form-control" value="<?php echo isset($company['email']) ? $company['email'] : '';?>" readonly>
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
		    <button type="submit" class="btn btn-info">Restore</button>
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