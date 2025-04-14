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
    return $input;
}

/*===================================
  Query: Fetch Companies from Database
=====================================*/

$companies = [];
$sql = "SELECT * FROM ref_companies";
$result = $conn->query($sql);

if ($result->num_rows > 0){
  while($row = $result->fetch_assoc()){
    $companies[] = $row;
  }
}

/*===================================
  Query: Fetch Drivers from Database
=====================================*/
$drivers = [];
$sql = "SELECT * FROM drivers WHERE `status` = 'available'";
$sql .= " AND id NOT IN (SELECT driver_id FROM van_assigned_driver)"; // Exclude drivers already assigned to a van
$result = $conn->query($sql);

if ($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
        $drivers[] = $row;
    }
}


/*===============================
  Function to fetch van data
=================================*/

	if (isset($_GET['id'])){
		$id = intval($_GET['id']);

		// Fetch the existing data for the van
		$sql = "SELECT * FROM vans WHERE id = ?";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("i", $id);
		$stmt->execute();
		$result = $stmt->get_result();

		if ($result->num_rows === 1){
			$van = $result->fetch_assoc();
		} else {
			// set session error message and redirect to vanManagement page
            $_SESSION['error'] = "Van not found!";
            header("Location: vanManagement");
			exit;
		}

		$stmt->close();
	} else {
		// Redirect to index after form submission
		header("Location: vanManagement");
		exit;
	}

/*===================================
  Query: Update Data into Database
=====================================*/

$errors = []; // Initialize Array to hold error messages 

if($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id'])){
    // Get form data
    $id = intval($_POST['id']);
    $plate_number = $_POST['plate_number'];
    $model = $_POST['model'];
    $color = $_POST['color'];
    $company = intval($_POST['company']);

    // Update the van data
    if (empty($errors)) {
      $sql = "UPDATE vans SET plate_number = ?, model = ?, color = ?, company_id = ? WHERE id = ?";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("sssii", $plate_number, $model, $color, $company, $id);
      if ($stmt->execute()) {
          //Record Activity
          require_once "activityLogsFunction.php";
          $activity_id = 18; // represents the activity ID for updating a van
          saveActivityLog($current_user_role_id, $activity_id, $current_user_id);

          $_SESSION['success'] = "Data updated successfully!";
          header("Location: vanManagement");
          exit;
      } else {
          $errors['error'] = "Update failed: " . $stmt->error;
      }
      $stmt->close();
  }
}

// Check if there's a success message in session and display it
if (isset($_SESSION['success'])) {
  $successMessage = $_SESSION['success'];
  unset($_SESSION['success']); // Clear session data
}

// Check if there's an error message in session and display it
if (isset($_SESSION['error'])) {
  $errorMessage = $_SESSION['error'];
  unset($_SESSION['error']); // Clear session data
}

// Close the database connection
$conn->close();
ob_end_flush();
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item active" aria-current="page"><h3>Van Details</h3></li>
  </ol>
</nav>

<!-- Begin page content -->
<main role="main" class="flex-shrink-0">
  <div class="container-fluid">
    <div class="card border-success mb-5">
      <div class="card-header" style="color: white; background-color: #28A745;">
        <strong>Update Van</strong>
      </div>
      <div class="card-body">
        <form method="post" action="">
        <input type="hidden" name="id" value="<?php echo isset($id) ? htmlspecialchars($id) : '' ?>"><br>
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <input type="text" name="plate_number" placeholder="Plate Number" class="form-control" value="<?php echo isset($van['plate_number']) ? $van['plate_number'] : '';?>">
              <span class="errors"><?php echo $errors['plate_number'] ?? ''; ?></span>
            </div>

            <div class="form-group">
              <input type="text" name="model" placeholder="Model" class="form-control" value="<?php echo isset($van['model']) ? $van['model'] : '';?>">
              <span class="errors"><?php echo $errors['model'] ?? ''; ?></span>
            </div>
            
          </div>
          <!-- /.col -->
          <div class="col-md-6">
            <div class="form-group">
              <input type="text" name="color" placeholder="Color" class="form-control" value="<?php echo isset($van['color']) ? $van['color'] : '';?>">
              <span class="errors"><?php echo $errors['color'] ?? ''; ?></span>
            </div>

            <div class="form-group">
              <select name="company" class="form-control">
                <option value="" selected disbaled>Select Company</option>
                <?php foreach ($companies as $company): ?>
                  <option value="<?php echo $company['id']; ?>" <?php echo isset($van['company_id']) && $van['company_id'] == $company['id'] ? "selected" : ""; ?>><?php echo $company['company_name']; ?></option>
                <?php endforeach; ?>
              </select>
              <span class="errors"><?php echo $errors['company'] ?? ''; ?></span>
            </div>
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
		<div class="row">
		  <div class="col-md-6">
		    <a class="btn btn-secondary" href="vanManagement">Back to List</a>
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
  </body>
</html>