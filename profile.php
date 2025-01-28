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
  Function to fetch user data
=================================*/
// Check if the user ID is provided
if (isset($current_user_id) && !empty($current_user_id)) {
    $id = intval($current_user_id); // Ensure the ID is an integer

    // Fetch the existing data for the user
    $sql = "SELECT firstname, middlename, lastname, qualifier, contact, email, address FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        // Error preparing the SQL statement
        echo "Error preparing the query!";
        exit;
    }

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
    } else {
        // Handle the case where the user is not found
        echo "User not found!";
        exit;
    }

    $stmt->close();
} else {
    // If the user ID is not provided, redirect to profile page
    header("Location: profile");
    exit;
}


/*===================================
  Query: Update Data into Database
=====================================*/

$errors = []; // Initialize Array to hold error messages 

if($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id'])){
  $id = intval($_POST['id']);
  if ($id <= 0) {
      echo "Invalid user ID.";
      exit;
  }

  $firstname = $_POST['firstname'] ?? '';
  $middlename = $_POST['middlename'] ?? '';
  $lastname = $_POST['lastname'] ?? '';
  $qualifier = $_POST['qualifier'] ?? '';
  $contact = $_POST['contact'] ?? '';
  $email = $_POST['email'] ?? '';
  $address = $_POST['address'] ?? '';

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

    // Validate contact number
    if (isset($contact)) {
      $contact = sanitizeInput($contact);
      // Validate contact number
      if (!preg_match("/^0[0-9]{10}$/", $contact)) { // Starts with 0 and exactly 11 digits
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

    // Validate address
    if (isset($address)) {
      $address = sanitizeInput($address);
      // Allow letters, numbers, spaces, hyphens, apostrophes, and commas
      if (!preg_match("/^[a-zA-Z0-9-' ,]*$/", $address) || empty($address)) {
          $errors['address'] = "Only letters, numbers, white space, hyphens, apostrophes, and commas are allowed in address.";
      }
    }

    // Update the user data
    if (empty($errors)) {
        $sql = "UPDATE users SET firstname = ?, middlename = ?, lastname = ?, qualifier = ?, contact = ?, email = ?, address = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssi", $firstname, $middlename, $lastname, $qualifier, $contact, $email, $address, $id);
        if ($stmt->execute()) {
            //Record Activity
            require_once "activityLogsFunction.php";
            $activity_id = 4; // represents the activity ID for updating user record
            saveActivityLog($current_user_role_id, $activity_id, $current_user_id);

            $_SESSION['success'] = "Data updated successfully!";
            header("Location: profile");
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
    <li class="breadcrumb-item active" aria-current="page"><h3>Profile</h3></li>
  </ol>
</nav>

<!-- Begin page content -->
<main role="main" class="flex-shrink-0">
  <div class="container-fluid">
    <div class="card border-success mb-5">
      <div class="card-header" style="color: white; background-color: #28A745;">
        <strong>Update information</strong>
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

          </div>
          <!-- /.col -->
          <div class="col-md-6">
            <div class="form-group">
              <input type="text" name="contact" id="contact" placeholder="Contact No" class="form-control" 
              value="<?php echo isset($user['contact']) ? $user['contact'] : '';?>">
              <span class="errors"><?php echo $errors['contact'] ?? ''; ?></span>
              <span class="errors" id="contactError"></span>
            </div>

            <div class="form-group">
              <input type="email" name="email" placeholder="Email" class="form-control" value="<?php echo isset($user['email']) ? $user['email'] : '';?>">
              <span class="errors"><?php echo $errors['email'] ?? ''; ?></span>
            </div>

            <div class="form-group">
                <input type="text" name="address" placeholder="Address" class="form-control" value="<?php echo isset($user['address']) ? $user['address'] : '';?>">
                <span class="errors"><?php echo $errors['address'] ?? ''; ?></span>
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
		    <a class="btn btn-secondary" href="command_center">Back to Command Center</a>
		  </div>
		  <div class="col-md-6 text-right">
            <a class="btn btn-secondary" href="command_center">Cancel</a>
            <input type="hidden" name="id" value="<?php echo isset($current_user_id) ? $current_user_id : '';?>">
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
      document.getElementById('contact').addEventListener('input', function () {
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