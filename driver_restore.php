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

/*===================================
  Query: Fetch Roles from Database
=====================================*/

$roles = [];
$sql = "SELECT id, role_name FROM ref_roles";
$result = $conn->query($sql);

if ($result->num_rows > 0){
  while($row = $result->fetch_assoc()){
    $roles[] = $row;
  }
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


/*===============================
  Fetch User to be deleted
=================================*/

	if (isset($_GET['id'])){
		$id = intval($_GET['id']);

		// Fetch the existing data for the user
		$sql = "SELECT 
            firstname,
            middlename,
            lastname,
            qualifier,
            role_id,
            company_id,
            drivers.contact AS contact,
            drivers.email AS email,
            ref_companies.company_name AS company_name,
            ref_roles.role_name AS `role`
            FROM drivers
            LEFT JOIN ref_companies ON drivers.company_id = ref_companies.id
            LEFT JOIN ref_roles ON drivers.role_id = ref_roles.id
            WHERE drivers.id = ?";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("i", $id);
		$stmt->execute();
		$result = $stmt->get_result();

		if ($result->num_rows === 1){
			$user = $result->fetch_assoc();
		} else {
			echo "User not found!";
			exit;
		}

		$stmt->close();
	} else {
		echo "Invalid parameters!";
		// Redirect to index after form submission
		header("Location: userManagement");
		exit;
	}

/*===================================
  Query: Update Data for Soft Delete
=====================================*/

if($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id'])){
	$id = intval($_POST['id']);
	
    	// Prepare the SQL query to insert data
		$sql = "UPDATE drivers SET is_active = 1, `status` = 'available' WHERE id = ?";

		//Create and prepare statement 
		$stmt = $conn->prepare($sql);

		if($stmt){
			//Bind parameters
			$stmt->bind_param("i", $id);

			// check if the statement is executed
			if($stmt->execute()){

        //Record Activity
        require_once "activityLogsFunction.php";
        $activity_id = 16; // represents the activity ID for restoring a driver
        saveActivityLog($current_user_role_id, $activity_id, $current_user_id);

				// echo "Data restored successfully!";
				 $_SESSION['success'] = "Data restored successfully!";

			} else {
				echo "Data restoration failed: $stmt->error";
			}

			// Close the statement
			$stmt->close();
		}
		// Redirect to index after form submission
		header("Location: driverManagement");
}

// Close the database connection
$conn->close();
ob_end_flush();
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item active" aria-current="page"><h3>Driver Management</h3></li>
  </ol>
</nav>

<!-- Begin page content -->
<main role="main" class="flex-shrink-0">
  <div class="container-fluid">
    <div class="card border-warning mb-5">
    <div class="card-header" style="color: white; background-color: #FFC107;">
        <strong>Restore Driver</strong>
      </div>
      <div class="card-body">
        <form method="post" action="">
        <input type="hidden" name="id" value="<?php echo isset($id) ? htmlspecialchars($id) : '' ?>"><br>
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <input type="text" name="firstname" placeholder="Firstname" class="form-control" value="<?php echo isset($user['firstname']) ? $user['firstname'] : '';?>" disabled>
            </div>

            <div class="form-group">
              <input type="text" name="middlename" placeholder="Middlename" class="form-control" value="<?php echo isset($user['middlename']) ? $user['middlename'] : '';?>" disabled>
            </div>

            <div class="form-group">
              <input type="text" name="lastname" placeholder="Lastname" class="form-control" value="<?php echo isset($user['lastname']) ? $user['lastname'] : '';?>" disabled>
              <span class="errors"><?php echo $errors['lastname'] ?? ''; ?></span>
            </div>

            <div class="form-group">
              <input type="text" name="qualifier" placeholder="Qualifier" class="form-control" value="<?php echo isset($user['qualifier']) ? $user['qualifier'] : '';?>" disabled>
              <span class="errors"><?php echo $errors['qualifier'] ?? ''; ?></span>
            </div>
          </div>
          <!-- /.col -->
          <div class="col-md-6">
            <div class="form-group">
              <input type="text" name="contact" placeholder="Contact No" class="form-control" value="<?php echo isset($user['contact']) ? $user['contact'] : '';?>" disabled>
            </div>

            <div class="form-group">
              <input type="email" name="email" placeholder="Email" class="form-control" value="<?php echo isset($user['email']) ? $user['email'] : '';?>" disabled>
            </div>

            <div class="form-group">
              <input type="text" name="company" placeholder="Company" class="form-control" value="<?php echo isset($user['company_name']) ? $user['company_name'] : '';?>" disabled>
            </div>

            <div class="form-group">
              <input type="text" name="role" placeholder="Role" class="form-control" value="<?php echo isset($user['role']) ? $user['role'] : '';?>" disabled>
            </div>
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
		<div class="row">
		  <div class="col-md-6">
		    <a class="btn btn-secondary" href="userManagement">Back to List</a>
		  </div>
		  <div class="col-md-6 text-right">
		    <button type="submit" class="btn btn-warning">Restore</button>
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