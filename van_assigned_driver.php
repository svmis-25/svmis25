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
  Query: Insert Data into Database
=====================================*/

$errors = []; // Initialize Array to hold error messages 

if($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id'])){    
    // Get form data
    $id = intval($_POST['id']);
    $driver = intval($_POST['driver']);
    $assigned_by = intval($current_user_id);

    // Validate driver selection
    if (empty($driver)) {
        $errors['driver'] = "Please select a driver.";
    } else {
        // Check if the driver is already assigned to another van
        $sql = "SELECT * FROM van_assigned_driver WHERE driver_id = ? AND van_id != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $driver, $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $errors['driver'] = "This driver is already assigned to another van.";
        }
        $stmt->close();
    }

    // If there are no errors, insert data into database
    if (empty($errors)) { 
        // Prepare the SQL query to insert data
        $sql = "INSERT INTO van_assigned_driver (van_id, driver_id, assigned_by)
                VALUES (?, ?, ?)";

        // Create and prepare statement 
        $stmt = $conn->prepare($sql);

        if ($stmt) {
          $stmt->bind_param("iii", $id, $driver, $assigned_by);
      
          if (!$stmt->execute()) {
              error_log("Database error: " . $stmt->error);
              // Set session error message
                $_SESSION['error'] = "Failed to insert data: " . $stmt->error;
          } else {
              //Record Activity
              require_once "activityLogsFunction.php";
              $activity_id = 21; // represents the activity ID for assigning a driver to a van
              saveActivityLog($current_user_role_id, $activity_id, $current_user_id);

              // Set session success message
              $_SESSION['success'] = "Data inserted successfully!";
          }
      
          $stmt->close();
      } else {
          error_log("Statement preparation failed: " . $conn->error);
          // Set session error message
          $_SESSION['error'] = "Failed to prepare statement: " . $conn->error;
      }
      
        // Redirect to index after form submission
        if (empty($errors)) {
          $_SESSION['success'] = "Data inserted successfully!";
          header("Location: " . $_SERVER['PHP_SELF']);
          exit;
      }
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
          <input type="hidden" name="id" value="<?php echo isset($id) ? htmlspecialchars($id) : '' ?>">
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

              <div class="form-group">
                <input type="text" name="color" placeholder="Color" class="form-control" value="<?php echo isset($van['color']) ? $van['color'] : '';?>">
                <span class="errors"><?php echo $errors['color'] ?? ''; ?></span>
              </div>

              <div class="form-group">
                <select name="company" class="form-control">
                  <option value="" selected disabled>Select Company</option>
                  <?php foreach ($companies as $company): ?>
                    <option value="<?php echo $company['id']; ?>" <?php echo isset($van['company_id']) && $van['company_id'] == $company['id'] ? "selected" : ""; ?>><?php echo $company['company_name']; ?></option>
                  <?php endforeach; ?>
                </select>
                <span class="errors"><?php echo $errors['company'] ?? ''; ?></span>
              </div>
            </div>
            <!-- /.col -->
            <div class="col-md-6">
              <div class="form-group">
                <select name="driver" id="driverSelect" class="form-control select2">
                  <option value="" selected disabled>Select Driver</option>
                  <?php foreach ($drivers as $driver): ?>
                    <option value="<?php echo $driver['id']; ?>" 
                    <?php echo isset($van['driver_id']) && $van['driver_id'] == $driver['id'] ? "selected" : ""; ?>>
                    <?php echo htmlspecialchars($driver['firstname'] . ' ' . htmlspecialchars($driver['lastname'])); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                
                <!-- Display Driver Details -->
                <div class="mt-3">
                  <h5>Driver Details:</h5>
                  <div id="driverDetails">
                    <p>Name: <span id="driverName"></span></p>
                    <p>Contact: <span id="driverContact"></span></p>
                    <p>Email: <span id="driverEmail"></span></p>
                    <p>Address: <span id="driverAddress"></span></p>
                    <p>Company: <span id="driverCompany"></span></p>
                  </div>
                </div>
                <span class="errors"><?php echo $errors['driver'] ?? ''; ?></span>
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
              <button type="submit" class="btn btn-success">Assign</button>
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
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>    
    <script src="https://cdn.datatables.net/1.11.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.4/js/dataTables.bootstrap4.min.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
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
        $(document).ready(function() {
            $('.select2').select2();
        });
    </script>
    <script>
        // Update driver details when a driver is selected
        $('#driverSelect').on('change', function() {
            var driverId = $(this).val();
            // display the driver id in console for debugging
            console.log(driverId);
            // Call the function to get driver details 
            var driverDetails = getDriverDetails(driverId);
        });

        function getDriverDetails(driverId) {
            $.ajax({
                url: 'getDriverDetails.php',
                type: 'GET',
                data: { id: driverId },
                success: function(response) {
                    // Display the driver details in the console for debugging
                    console.log(response);
                    var driver = JSON.parse(response);
                    $('#driverName').text(driver.firstname + ' ' + driver.lastname);
                    $('#driverAddress').text(driver.address);
                    $('#driverContact').text(driver.contact);
                    $('#driverEmail').text(driver.email);
                    $('#driverCompany').text(driver.company_name);
                }
            });
        }
    </script>
  </body>
</html>