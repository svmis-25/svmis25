<?php
ob_start();
session_start();
// Enable error reporting and logging
error_reporting(E_ALL);
ini_set('display_errors', 1); // Disable error display
ini_set('log_errors', 1); // Enable error logging
ini_set('error_log', __DIR__ . '/../error_log.txt'); // Set log file path

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: index");
    exit;
} elseif ($_SESSION["userrole"] != 1){
  header("Location: errors/403");
  exit;
}

$current_user_id = $_SESSION["userID"];
$current_user_role_id = $_SESSION["userrole"];

$active = "modules";
include "partials/header.php"; 

// require config file
require_once __DIR__ . '../config.php';

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
/*===================================
  Query: Retrieve Data from Database
=====================================*/
$sql = "SELECT 
          passengers.id AS user_id, 
          passengers.firstname, 
          passengers.middlename, 
          passengers.lastname, 
          passengers.qualifier, 
          passengers.contact, 
          passengers.email, 
          passengers.role_id, 
          passengers.is_active, 
          ref_roles.role_name
        FROM passengers 
        LEFT JOIN ref_roles ON passengers.role_id = ref_roles.id";

// Fetch data
$result = $conn->query($sql);

// Store Data
$data = []; // Initialize data
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'id' => $row['user_id'],
            'firstname' => $row['firstname'],
            'middlename' => $row['middlename'],
            'lastname' => $row['lastname'],
            'qualifier' => $row['qualifier'],
            'contactNo' => $row['contact'],
            'email' => $row['email'],
            'role' => $row['role_name'],
            'isActive' => $row['is_active'],
        ];
    }
}


// Get the Total number of records
$totalRecords = count($data);

/*===================================
  Query: Insert Data into Database
=====================================*/
// Variables to hold messages
$successMessage = ""; // Initialize empty message for success submission result
$errors = []; // Initialize Array to hold error messages 

if($_SERVER["REQUEST_METHOD"] === "POST"){
    $firstname = $_POST['firstname'];
    $middlename = $_POST['middlename'];
    $lastname = $_POST['lastname'];
    $qualifier = $_POST['qualifier'];
    $contactNo = $_POST['contactNo'];
    $email = $_POST['email'] ?? 'noemail'; // Default email for passengers without email
    $role = 4;
    $hashedPassword = password_hash('Svmis@202501', PASSWORD_DEFAULT); // Default password for passengers without phone

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
    if (isset($contactNo)){
        $contactNo = sanitizeInput($contactNo);
        // validate contact
        if (!preg_match("/^[0-9]{10,15}$/", $contactNo) || empty($contactNo)) {
            $errors['contact'] = "Invalid contact number.";
        }
    }
  
    // Validate email address if provided or set default email "noemail"
    if (isset($email)) {
        $email = sanitizeInput($email);
        // validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Invalid email format.";
        }
    } elseif (empty($email)) {
        // Check if email already exists in the database for passengers without email address if exists set default email "noemail . increment digits. @gmail.com"
        $sql = "SELECT id FROM passengers WHERE email = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $errors['email'] = "Email address already exists.";
            }
            $stmt->close();
        } else {
            // find the last passenger where email is starting with "noemail"
            $email = "noemail";
            $last_id = 0;
            $sql = "SELECT id FROM passengers WHERE email LIKE 'noemail%' ORDER BY id DESC LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $last_id = $row['id'];
                $email = "noemail" . ($last_id + 1) . "@gmail.com";
            }
        }
        
    }

    // if (isset($email)){
    //     $email = sanitizeInput($email);
    //     // validate email
    //     if (!filter_var($email, FILTER_VALIDATE_EMAIL) || empty($email)) {
    //         $errors['email'] = "Invalid email format.";
    //     } else {
    //         // Check if email already exists in the database
    //         $sql = "SELECT id FROM passengers WHERE email = ?";
    //         $stmt = $conn->prepare($sql);
    //         if ($stmt) {
    //             $stmt->bind_param("s", $email);
    //             $stmt->execute();
    //             $stmt->store_result();
    //             if ($stmt->num_rows > 0) {
    //                 $errors['email'] = "Email address already exists.";
    //             }
    //             $stmt->close();
    //         }
    //     }
    // }

    // If there are no errors, insert data into database
    if (empty($errors)) { 
        // Prepare the SQL query to insert data
        $sql = "INSERT INTO passengers (firstname, middlename, lastname, qualifier, contact, email, password, role_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        // Create and prepare statement 
        $stmt = $conn->prepare($sql);

        if ($stmt) {
          $stmt->bind_param("sssssssi", $firstname, $middlename, $lastname, $qualifier, $contactNo, $email, $hashedPassword, $role);
      
          if (!$stmt->execute()) {
              error_log("Database error: " . $stmt->error);
              echo "Database error: " . $stmt->error; // Temporarily display for debugging
          } else {
              //Record Activity
              require_once "activityLogsFunction.php";
              $activity_id = 3; // represents the activity ID for adding a new user
              saveActivityLog($current_user_role_id, $activity_id, $current_user_id);

              // Set session success message
              $_SESSION['success'] = "Data inserted successfully!";
          }
      
          $stmt->close();
      } else {
          error_log("Statement preparation failed: " . $conn->error);
          echo "Statement preparation failed: " . $conn->error; // Display preparation error
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

// Close the database connection
$conn->close();
ob_end_flush();
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item active" aria-current="page"><h3>Passenger Management</h3></li>
  </ol>
</nav>

<!-- Begin page content -->
<main role="main" class="flex-shrink-0">
  <div class="container-fluid">
    <div class="card border-primary mb-5">
      <div class="card-header" style="color: white; background-color: #007BFF;">
        <strong>Add Passenger</strong>
      </div>
      <div class="card-body">
        <form method="post" action="">
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <input type="text" name="firstname" placeholder="Firstname" class="form-control" value="<?php echo isset($firstname) ? $firstname : '';?>">
              <span class="errors"><?php echo $errors['firstname'] ?? ''; ?></span>
            </div>

            <div class="form-group">
              <input type="text" name="middlename" placeholder="Middlename" class="form-control" value="<?php echo isset($middlename) ? $middlename : '';?>">
              <span class="errors"><?php echo $errors['middlename'] ?? ''; ?></span>
            </div>

            <div class="form-group">
              <input type="text" name="lastname" placeholder="Lastname" class="form-control" value="<?php echo isset($lastname) ? $lastname : '';?>">
              <span class="errors"><?php echo $errors['lastname'] ?? ''; ?></span>
            </div>

          </div>
          <!-- /.col -->
          <div class="col-md-6">
            <div class="form-group">
              <input type="text" name="qualifier" placeholder="Qualifier" class="form-control" value="<?php echo isset($qualifier) ? $qualifier : '';?>">
              <span class="errors"><?php echo $errors['qualifier'] ?? ''; ?></span>
            </div>

            <div class="form-group">
              <input type="text" name="contactNo" id="contactNo" placeholder="Contact No" class="form-control" 
                value="<?php echo isset($contactNo) ? $contactNo : ''; ?>">
              <span class="errors"><?php echo $errors['contact'] ?? ''; ?></span>
              <span class="errors" id="contactError"></span>
            </div>

            <div class="form-group">
              <input type="email" name="email" placeholder="Email" class="form-control" value="<?php echo isset($email) ? $email : '';?>">
              <span class="errors"><?php echo $errors['email'] ?? ''; ?></span>
            </div>

          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
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

    <!-- List of Users -->
    <div class="card border-secondary mb-3">
      <div class="card-header" style="color: white; background-color: #6C757D;">
        <strong>List of Passengers</strong>
      </div>
        <div class="card-body">
          <div class="table-responsive">
            <table id="tblUserManagement" class="table table-striped table-bordered table-sm table-hover">
              <thead>
                  <tr class="text-center">
                      <!-- <th class="hidden">ID</th> -->
                      <th class="hidden">ID</th>
                      <th>Firstname</th>
                      <th>Middlename</th>
                      <th>Lastname</th>
                      <th>Qualifier</th>
                      <th>Contact No</th>
                      <th>Email</th>
                      <th>Status</th>
                      <th>Action</th>
                  </tr>
              </thead>
              <tbody>
                  <?php if (isset($data) && is_array($data)): ?>
                    <?php foreach ($data as $row): ?>
                        <tr>
                            <td class="hidden"><?php echo htmlspecialchars($row['id']) ?? ''; ?></td>
                            <td><?php echo htmlspecialchars($row['firstname']) ?? ''; ?></td>
                            <td><?php echo htmlspecialchars($row['middlename']) ?? ''; ?></td>
                            <td><?php echo htmlspecialchars($row['lastname']) ?? ''; ?></td>
                            <td><?php echo htmlspecialchars($row['qualifier']) ?? ''; ?></td>
                            <td><?php echo htmlspecialchars($row['contactNo']) ?? ''; ?></td>
                            <td><?php echo htmlspecialchars($row['email']) ?? ''; ?></td>
                            <td class="text-center"><?php echo htmlspecialchars($row['isActive']) ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-danger">Inactive</span>'; ?></td>
                            <td class="text-center">
                            
                              <div class="dropdown">
                                <button class="btn btn-secondary dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false">
                                  Action
                                </button>
                                <div class="dropdown-menu">
                                  <a class="dropdown-item" href="print_QR_code?id=<?php echo urlencode($row['id']); ?>">Print QR Code</a>
                                  <a class="dropdown-item" href="update?id=<?php echo urlencode($row['id']); ?>">Update</a>
                                  <?php if($row['isActive'] == 1): ?>
                                  <a class="dropdown-item" href="delete?id=<?php echo urlencode($row['id']); ?>">Delete</a>
                                  <?php elseif ($row['isActive'] == 0): ?>
                                  <a class="dropdown-item" href="restore?id=<?php echo urlencode($row['id']); ?>">Restore</a>
                                  <?php endif; ?>
                                </div>
                              </div>
                            
                            </td>
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