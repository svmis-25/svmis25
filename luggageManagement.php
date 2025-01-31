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
/*===================================
  Query: Retrieve Data from Database
=====================================*/

//SQL Query for searching with prepared statement
$sql = "SELECT 
            passengers_luggage.id AS luggage_id,
            passengers_luggage.passenger_id,
            passengers_luggage.driver_id,
            passengers_luggage.luggage_code,
            passengers_luggage.description,
            passengers_luggage.size,
            passengers_luggage.status,
            passengers_luggage.created_at,
            passengers_luggage.trip_id,
            passengers.id AS passengerID,
            passengers.firstname,
            passengers.middlename,
            passengers.lastname,
            passengers.qualifier,
            passengers.contact,
            passengers.email,
            passengers.is_active
        FROM passengers_luggage 
        LEFT JOIN passengers ON passengers_luggage.passenger_id = passengers.id";

// Check if search query exists
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = sanitizeInput($_GET['search']);
    $sql .= " WHERE passengers.firstname LIKE ? OR passengers.middlename LIKE ? OR passengers.lastname LIKE ? OR passengers.qualifier LIKE ? OR passengers.contact LIKE ? OR passengers.email LIKE ?";
    $stmt = $conn->prepare($sql);
    $searchTerm = "%$search%";  // Add wildcard for LIKE query
    $stmt->bind_param("ssssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
} else {
    $stmt = $conn->prepare($sql);
}

$stmt->execute();
$stmt->store_result();

// Bind result variables (adjusted based on SELECT query columns)
$stmt->bind_result($luggage_id, $passenger_id, $driver_id, $luggage_code, $description, $size, $status, $created_at, $trip_id, $passengerID, $firstname, $middlename, $lastname, $qualifier, $contact, $email, $is_active);

$data = []; // Initialize an empty array to hold the data

if ($stmt->num_rows > 0) {
    while ($stmt->fetch()) {
        $data[] = [
            'luggage_id' => $luggage_id,
            'passenger_id' => $passenger_id,
            'driver_id' => $driver_id,
            'luggage_code' => $luggage_code,
            'description' => $description,
            'size' => $size,
            'status' => $status,
            'created_at' => $created_at,
            'trip_id' => $trip_id,
            'name' => $firstname . ' ' . $middlename . ' ' . $lastname . ' ' . $qualifier, 
            // 'firstname' => $firstname,
            // 'middlename' => $middlename,
            // 'lastname' => $lastname,
            // 'qualifier' => $qualifier,
            'contact' => $contact,
            'email' => $email,
            'is_active' => $is_active
        ];
    }
}

// Close the statement
$stmt->close();

// Get the Total number of records
$totalRecords = count($data);

/*===================================
  Query: Insert Data into Database
=====================================*/
// Variables to hold messages
$successMessage = ""; // Initialize empty message for success submission result
$errors = []; // Initialize Array to hold error messages 

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $firstname = $_POST['firstname'] ?? '';
  $middlename = $_POST['middlename'] ?? '';
  $lastname = $_POST['lastname'] ?? '';
  $qualifier = $_POST['qualifier'] ?? '';
  $contactNo = $_POST['contactNo'] ?? '';
  $email = $_POST['email'] ?? '';
  $role = 4;
  $hashedPassword = password_hash('Svmis@202501', PASSWORD_DEFAULT);
  $errors = [];

  // Define valid qualifiers
  $valid_qualifiers = ["Jr", "JR", "II", "III", "IV", "V"];

  // // Validate and sanitize inputs
  // function sanitizeInput($data) {
  //     return htmlspecialchars(strip_tags(trim($data)));
  // }

  $firstname = sanitizeInput($firstname);
  if (empty($firstname) || !preg_match("/^[a-zA-Z-' ]*$/", $firstname)) {
      $errors['firstname'] = "Only letters and white space allowed in firstname.";
  }

  if (!empty($middlename)) {
      $middlename = sanitizeInput($middlename);
      if (!preg_match("/^[a-zA-Z-' ]*$/", $middlename)) {
          $errors['middlename'] = "Only letters and white space allowed in middlename.";
      }
  }

  $lastname = sanitizeInput($lastname);
  if (empty($lastname) || !preg_match("/^[a-zA-Z-' ]*$/", $lastname)) {
      $errors['lastname'] = "Only letters and white space allowed in lastname.";
  }

  if (!empty($qualifier) && !in_array($qualifier, $valid_qualifiers)) {
      $errors['qualifier'] = "Invalid qualifier. Valid qualifiers are: " . implode(", ", $valid_qualifiers);
  }

  $contactNo = sanitizeInput($contactNo);
  if (!empty($contactNo) && !preg_match("/^[0-9]{10,15}$/", $contactNo)) {
      $errors['contact'] = "Invalid contact number.";
  }

  if (empty($email)) {
      // Check for the last used `noemailX@gmail.com` and increment
      $email = "noemail";
      $sql = "SELECT email FROM passengers WHERE email LIKE 'noemail%' ORDER BY id DESC LIMIT 1";
      $stmt = $conn->prepare($sql);

      if ($stmt) {
          $stmt->execute();
          $stmt->store_result();

          if ($stmt->num_rows > 0) {
              $stmt->bind_result($lastEmail);
              $stmt->fetch();

              // Extract the number and increment
              if (preg_match("/noemail(\d*)@gmail\.com/", $lastEmail, $matches)) {
                  $increment = isset($matches[1]) ? (int)$matches[1] + 1 : 1;
                  $email = "noemail" . $increment . "@gmail.com";
              }
          } else {
              $email = "noemail1@gmail.com";
          }
          $stmt->close();
      } else {
          error_log("Database query error: " . $conn->error);
      }
  } else {
      $email = sanitizeInput($email);
      if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
          $errors['email'] = "Invalid email format.";
      } else {
          // Check if email already exists
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
          }
      }
  }

  // If there are no validation errors, insert the data
  if (empty($errors)) {
      $sql = "INSERT INTO passengers (firstname, middlename, lastname, qualifier, contact, email, password, role_id) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
      $stmt = $conn->prepare($sql);

      if ($stmt) {
          $stmt->bind_param("sssssssi", $firstname, $middlename, $lastname, $qualifier, $contactNo, $email, $hashedPassword, $role);
          if ($stmt->execute()) {
              require_once "activityLogsFunction.php";
              saveActivityLog($current_user_role_id, 3, $current_user_id); // Activity ID for adding a user
              $_SESSION['success'] = "Data inserted successfully!";
          } else {
              error_log("Insert error: " . $stmt->error);
          }
          $stmt->close();
      } else {
          error_log("Statement preparation failed: " . $conn->error);
      }
      header("Location: " . $_SERVER['PHP_SELF']);
      exit;
  }
}


// Check if there's a success message in session and display it
if (isset($_SESSION['success'])) {
    $successMessage = $_SESSION['success'];
    unset($_SESSION['success']); // Clear session data
}

// Check if there's a success message in session and display it
if (isset($_SESSION['success-transaction'])) {
  $successTransactionMessage = $_SESSION['success-transaction'];
  unset($_SESSION['success-transaction']); // Clear session data
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
    <li class="breadcrumb-item active" aria-current="page"><h3>Luggage Management</h3></li>
  </ol>
</nav>

<!-- Begin page content -->
<main role="main" class="flex-shrink-0">
  <div class="container-fluid">
  <?php if (!empty($successTransactionMessage)): ?>
      <div class="row mt-3">
        <div class="col-md-12">
          <div class="alert alert-success alert-dismissible fade show" role="alert">
              <strong>Administrator:</strong> <span class="success-message"><?php echo $successTransactionMessage; ?></span>
              <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
              </button>
          </div>
      </div>
      </div>
    <?php endif; ?>
  <?php if (!empty($errorMessage)): ?>
      <div class="row mt-3">
        <div class="col-md-12">
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <strong>Administrator:</strong> <span class="success-message"><?php echo $errorMessage; ?></span>
              <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
              </button>
          </div>
      </div>
      </div>
    <?php endif; ?>
    <div class="card border-primary mb-5">
      <div class="card-header" style="color: white; background-color: #007BFF;">
        <strong>Add Transaction</strong>
      </div>
      <div class="card-body">
      <div class="row justify-content-center">
        <!-- Search for Passenger -->
        <div class="col-md-4">
            <div class="form-group d-flex">
                <input type="text" name="search" id="search" placeholder="Search Passenger" class="form-control mr-2">
                <button type="button" class="btn btn-primary" id="searchBtn">Search</button>
                <span class="errors"><?php echo $errors['search'] ?? ''; ?></span> <!-- Displaying any error for 'search' -->
                <span class="errors" id="searchError"></span> <!-- Displaying any dynamic JS error -->
            </div>
        </div>
      </div>

        <!-- Form for displaying search results -->
        <form method="post" action="">
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <input type="text" name="firstname" placeholder="Firstname" class="form-control" value="<?php echo isset($firstname) ? $firstname : '';?>" readonly>
              <span class="errors"><?php echo $errors['firstname'] ?? ''; ?></span>
            </div>

            <div class="form-group">
              <input type="text" name="middlename" placeholder="Middlename" class="form-control" value="<?php echo isset($middlename) ? $middlename : '';?>" readonly>
              <span class="errors"><?php echo $errors['middlename'] ?? ''; ?></span>
            </div>

            <div class="form-group">
              <input type="text" name="lastname" placeholder="Lastname" class="form-control" value="<?php echo isset($lastname) ? $lastname : '';?>" readonly>
              <span class="errors"><?php echo $errors['lastname'] ?? ''; ?></span>
            </div>

          </div>
          <!-- /.col -->
          <div class="col-md-6">
            <div class="form-group">
              <input type="text" name="qualifier" placeholder="Qualifier" class="form-control" value="<?php echo isset($qualifier) ? $qualifier : '';?>" readonly>
            </div>

            <div class="form-group">
              <input type="text" name="contactNo" id="contactNo" placeholder="Contact No" class="form-control" value="<?php echo isset($contact) ? $contact : ''; ?>" readonly>
            </div>

            <div class="form-group">
              <input type="email" name="email" placeholder="Email" class="form-control" value="<?php echo isset($email) ? $email : '';?>" readonly>
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
        <strong>List of Luggages</strong>
      </div>
        <div class="card-body">
          <div class="table-responsive">
            <table id="tblUserManagement" class="table table-striped table-bordered table-sm table-hover">
              <thead>
                  <tr class="text-center">
                        <th class="hidden">ID</th>
                        <th>Name</th>
                        <th>Contact No</th>
                        <th>Description</th>
                        <th>Size</th>
                        <th>Driver</th>
                        <th>Trip ID</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Action</th>
                  </tr>
              </thead>
              <tbody>
                  <?php if (isset($data) && is_array($data)): ?>
                    <?php foreach ($data as $row): ?>
                        <tr>
                            <td class="hidden"><?php echo htmlspecialchars($row['id']) ?? ''; ?></td>
                            <td><?php echo htmlspecialchars($row['name']) ?? ''; ?></td>
                            <td><?php echo htmlspecialchars($row['contact']) ?? ''; ?></td>
                            <td><?php echo htmlspecialchars($row['description']) ?? ''; ?></td>
                            <td><?php echo htmlspecialchars($row['size']) ?? ''; ?></td>
                            <td><?php echo htmlspecialchars($row['driver']) ?? ''; ?></td>
                            <td><?php echo htmlspecialchars($row['trip_id']) ?? ''; ?></td>
                            <td><?php echo isset($row['created_at']) ? htmlspecialchars(date('Y-m-d', strtotime($row['created_at']))) : ''; ?></td>
                            <td class="text-center"><?php echo htmlspecialchars($row['status']) ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-danger">Inactive</span>'; ?></td>
                            <td class="text-center">
                            
                              <div class="dropdown">
                                <button class="btn btn-secondary dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false">
                                  Action
                                </button>
                                <div class="dropdown-menu">
                                  <a class="dropdown-item" href="print_QR_code?id=<?php echo urlencode($row['id']); ?>" target="_blank">Booking QR Code</a>
                                  <a class="dropdown-item" href="update?id=<?php echo urlencode($row['id']); ?>">Update</a>
                                  <?php if($row['isActive'] == 1): ?>
                                  <a class="dropdown-item" href="delete?id=<?php echo urlencode($row['id']); ?>">Delete</a>
                                  <a class="dropdown-item" href="make_transaction?id=<?php echo urlencode($row['id']); ?>">Book</a>
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
    <script>
        // Search for passengers
        document.getElementById('searchBtn').addEventListener('click', function () {
            var searchInput = document.getElementById('search').value;
            window.location.href = 'luggageManagement.php?search=' + encodeURIComponent(searchInput);
        });
    </script>
  </body>
</html>