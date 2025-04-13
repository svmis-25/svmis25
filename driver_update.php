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

/*=========================================
  Function to validate password complexity
===========================================*/

function validatePassword($password){
    // Password must be at least 12 characters long and contain upper and lower case letters, numbers, and special characters
    return strlen($password) >= 12
        && preg_match('/[A-Z]/', $password)
        && preg_match('/[a-z]/', $password)
        && preg_match('/[0-9]/', $password)
        && preg_match('/[!@#$%^&()-_+]/', $password);
}

/*===================================
  Query: Fetch Roles from Database
=====================================*/

$roles = [];
$sql = "SELECT id, role_name FROM ref_roles WHERE id NOT IN (1, 2, 4)";
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
  Function to fetch user data
=================================*/

	if (isset($_GET['id'])){
		$id = intval($_GET['id']);

		// Fetch the existing data for the user
		$sql = "SELECT firstname, middlename, lastname, qualifier, contact, age, `address`, email, company_id, role_id FROM drivers WHERE id = ?";
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
		// echo "Invalid parameters!";
		// Redirect to index after form submission
		header("Location: userManagement");
		exit;
	}

/*===================================
  Query: Update Data into Database
=====================================*/

$errors = []; // Initialize Array to hold error messages 

if($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id'])){
	$firstname = $_POST['firstname'];
	$middlename = $_POST['middlename'];
	$lastname = $_POST['lastname'];
	$qualifier = $_POST['qualifier'];
  $age = $_POST['age'];
	$contact = $_POST['contactNo'];
  $address = $_POST['address'];
	$email = $_POST['email'];
	$company = $_POST['company'];
	$role = $_POST['role'];

	// Define valid qualifiers
    $valid_qualifiers = array("Jr", "JR", "II", "III", "IV", "V");

    // Validate firstname
    if (isset($firstname)){
        $firstname = sanitizeInput($firstname);
        // validate firstname
        if (!preg_match("/^[a-zA-Z-' ]*$/", $firstname) || empty($firstname)) {
            $errors['firstname'] = "Only letters and white space allowed in firstname.";
        }
    }

	// Validate middlename
	if (isset($middlename) && !empty($middlename)) {
	    $middlename = sanitizeInput($middlename);
	    // validate middlename
	    if (!preg_match("/^[a-zA-Z-' ]*$/", $middlename)) {
	        $errors['middlename'] = "Only letters and white space allowed in middlename.";
	    }
	}


    // Validate lastname
    if (isset($lastname)){
        $lastname = sanitizeInput($lastname);
        // validate lastname
        if (!preg_match("/^[a-zA-Z-' ]*$/", $lastname) || empty($lastname)) {
            $errors['lastname'] = "Only letters and white space allowed in lastname.";
        }
    }

    // Validate qualifier (optional)
    if (!empty($qualifier) && !in_array($qualifier, $valid_qualifiers)) {
        $errors['qualifier'] = "Invalid qualifier. Valid qualifiers are: " . implode(", ", $valid_qualifiers);
    }

    // Validate address
    if (isset($address)){
      $address = sanitizeInput($address);
      if (!preg_match("/^[a-zA-Z0-9-',. ]*$/", $address) || empty($address)) {
          $errors['address'] = "Only letters, numbers and white space allowed in address.";
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
    if (isset($email)){
        $email = sanitizeInput($email);
        // validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || empty($email)) {
            $errors['email'] = "Invalid email format.";
        }
    }

    // Validate age
    if (isset($age)){
        $age = sanitizeInput($age);
        if (!preg_match("/^[0-9]{1,3}$/", $age) || empty($age)) {
            $errors['age'] = "Invalid age.";
        }
    }

    //Validate Role
    if (!isset($role) || $role === ""){
        $errors['role'] = "Role is required.";
    }

    // Validate company
    if (!isset($company) || $company === ""){
        $errors['company'] = "Company is required.";
    }

    // If there are no errors, display data
    if (empty($errors)) {
    	// Prepare the SQL query to insert data
		$sql = "UPDATE drivers SET firstname = ?, middlename = ?, lastname = ?, qualifier = ?, contact = ?, email = ?, age = ?, `address` = ?, company_id = ?, role_id = ? WHERE id = ?";

		//Create and prepare statement 
		$stmt = $conn->prepare($sql);

		if($stmt){
			//Bind parameters
			$stmt->bind_param("ssssssisiii", $firstname, $middlename, $lastname, $qualifier, $contact, $email, $age, $address, $company, $role, $id);

			// check if the statement is executed
			if($stmt->execute()){

        //Record Activity
        require_once "activityLogsFunction.php";
        $activity_id = 14; // represents the activity ID for updating driver record
        saveActivityLog($current_user_role_id, $activity_id, $current_user_id);

				// echo "Data updated successfully!";
				 $_SESSION['success'] = "Data updated successfully!";

			} else {
				echo "Data updating failed: $stmt->error";
			}

			// Close the statement
			$stmt->close();
		}
		// Redirect to index after form submission
		header("Location: driverManagement");
	 }
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
    <div class="card border-success mb-5">
      <div class="card-header" style="color: white; background-color: #28A745;">
        <strong>Update Driver Data</strong>
      </div>
      <div class="card-body">
        <form method="post" action="">
        <input type="hidden" name="id" value="<?php echo isset($id) ? htmlspecialchars($id) : '' ?>"><br>
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <input type="text" name="firstname" placeholder="Firstname" class="form-control" value="<?php echo isset($user['firstname']) ? $user['firstname'] : '';?>">
              <span class="errors"><?php echo $errors['firstname'] ?? ''; ?></span>
            </div>

            <div class="form-group">
              <input type="text" name="middlename" placeholder="Middlename" class="form-control" value="<?php echo isset($user['middlename']) ? $user['middlename'] : '';?>">
              <span class="errors"><?php echo $errors['middlename'] ?? ''; ?></span>
            </div>

            <div class="form-group">
              <input type="text" name="lastname" placeholder="Lastname" class="form-control" value="<?php echo isset($user['lastname']) ? $user['lastname'] : '';?>">
              <span class="errors"><?php echo $errors['lastname'] ?? ''; ?></span>
            </div>

            <div class="form-group">
              <input type="text" name="qualifier" placeholder="Qualifier" class="form-control" value="<?php echo isset($user['qualifier']) ? $user['qualifier'] : '';?>">
              <span class="errors"><?php echo $errors['qualifier'] ?? ''; ?></span>
            </div>

            <div class="form-group">
              <input type="number" name="age" placeholder="Age" class="form-control" value="<?php echo isset($user['age']) ? $user['age'] : '';?>" min="1" max="100">
              <span class="errors"><?php echo $errors['age'] ?? ''; ?></span>
            </div>
          </div>
          <!-- /.col -->
          <div class="col-md-6">
            <div class="form-group">
              <input type="text" name="contactNo" id="contactNo" placeholder="Contact No" class="form-control" 
              value="<?php echo isset($user['contact']) ? $user['contact'] : '';?>">
              <span class="errors"><?php echo $errors['contact'] ?? ''; ?></span>
              <span class="errors" id="contactError"></span>
            </div>

            <div class="form-group">
              <input type="text" name="address" placeholder="Address" class="form-control" value="<?php echo isset($user['address']) ? $user['address'] : '';?>">
              <span class="errors"><?php echo $errors['address'] ?? ''; ?></span>
            </div>

            <div class="form-group">
              <input type="email" name="email" placeholder="Email" class="form-control" value="<?php echo isset($user['email']) ? $user['email'] : '';?>">
              <span class="errors"><?php echo $errors['email'] ?? ''; ?></span>
            </div>

            <div class="form-group">
              <select name="company" class="form-control">
                <option value="" selected disbaled>Select Company</option>
                <?php foreach ($companies as $company): ?>
                  <option value="<?php echo $company['id']; ?>" <?php echo isset($user['company_id']) && $user['company_id'] == $company['id'] ? "selected" : ""; ?>><?php echo $company['company_name']; ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <select name="role" class="form-control">
                <option value="" disabled>Select Role</option>
                <?php foreach ($roles as $role): ?>
                  <option value="<?php echo $role['id']; ?>" 
                    <?php echo ($role['id'] == 3) ? 'selected' : ''; ?>
                  ><?php echo $role['role_name']; ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <span class="errors"><?php echo $errors['role'] ?? ''; ?></span>
            </div>
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
		<div class="row">
		  <div class="col-md-6">
		    <a class="btn btn-secondary" href="driverManagement">Back to List</a>
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