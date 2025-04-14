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
  Query: Retrieve Data from Database
=====================================*/
$sql = "SELECT 
            v.*, 
            vad.driver_id,
            vad.trip_id,
            vad.created_at AS assignment_date,
            d.firstname AS driver_firstname,
            d.middlename AS driver_middlename,
            d.lastname AS driver_lastname,
            d.qualifier AS driver_qualifier,
            c.company_name
        FROM vans v
        LEFT JOIN van_assigned_driver vad ON v.id = vad.van_id
        LEFT JOIN drivers d ON vad.driver_id = d.id
        LEFT JOIN ref_companies c ON v.company_id = c.id
        ORDER BY v.id DESC";

// Fetch data
$result = $conn->query($sql);

// Store data in an array
$data = []; // Initialize data
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Format driver name properly
        $driverName = 'Unassigned';
        if ($row['driver_id']) {
            $firstName = $row['driver_firstname'] ?? '';
            $middleName = !empty($row['driver_middlename']) ? substr($row['driver_middlename'], 0, 1) . '. ' : '';
            $lastName = $row['driver_lastname'] ?? '';
            $qualifier = $row['driver_qualifier'] ?? '';
            
            $driverName = trim("$firstName $middleName$lastName $qualifier");
        }

        $data[] = [
            'id' => $row['id'],
            'plate_number' => $row['plate_number'],
            'model' => $row['model'],
            'color' => $row['color'],
            'status' => $row['status'],
            'driver_id' => $row['driver_id'],
            'driver_name' => $driverName,
            'trip_id' => $row['trip_id'],
            'assignment_date' => $row['assignment_date'],
            'company' => $row['company_name'],
        ];
    }
}

// console log($data); // For debugging purposes
echo "<script>console.log(" . json_encode($data) . ");</script>"; // For debugging purposes


// Get the Total number of records
$totalRecords = count($data);

/*===================================
  Query: Insert Data into Database
=====================================*/
// Variables to hold messages
$successMessage = ""; // Initialize empty message for success submission result
$errors = []; // Initialize Array to hold error messages 

if($_SERVER["REQUEST_METHOD"] === "POST"){
    // Get form data
    $plate_number = $_POST['plate_number'];
    $model = $_POST['model'];
    $color = $_POST['color'];
    $company = $_POST['company'];

    // Validate Plate Number
    if (isset($_POST['plate_number'])) {
        $plate_number = sanitizeInput($_POST['plate_number']);
        
        // Convert to uppercase and remove extra spaces
        $plate_number = strtoupper(preg_replace('/\s+/', ' ', $plate_number));
        
        // Philippine plate number validation
        if (empty($plate_number)) {
            $errors['plate_number'] = "Plate number is required.";
        } elseif (!preg_match('/^[A-Z]{2,3}[\s-]?[0-9]{3,4}$/', $plate_number)) {
            $errors['plate_number'] = "Invalid Philippine plate number format. Examples: ABC 123, ABC123, XYZ-1234, VVV 456";
        } else {
            // If valid, store the sanitized version
            $plate_number = htmlspecialchars($plate_number, ENT_QUOTES, 'UTF-8');
        }
    }

    // Validate Model
    if (isset($_POST['model'])) {
        $model = sanitizeInput($_POST['model']);
        if (!preg_match("/^[a-zA-Z-' ]*$/", $model) || empty($model)) {
            $errors['model'] = "Only letters and white space allowed in model.";
        } else {
            $model = htmlspecialchars($model, ENT_QUOTES, 'UTF-8');
        }
    }

    // Validate Color
    if (isset($_POST['color'])) {
        $color = sanitizeInput($_POST['color']);
        if (!preg_match("/^[a-zA-Z-' ]*$/", $color) || empty($color)) {
            $errors['color'] = "Only letters and white space allowed in color.";
        } else {
            $color = htmlspecialchars($color, ENT_QUOTES, 'UTF-8');
        }
    }

    //Validate Company
    if (!isset($company) || $company === ""){
        $errors['company'] = "Company is required.";
    }

    // If there are no errors, insert data into database
    if (empty($errors)) { 
        // Prepare the SQL query to insert data
        $sql = "INSERT INTO vans (plate_number, model, color, company_id) 
                VALUES (?, ?, ?, ?)";

        // Create and prepare statement 
        $stmt = $conn->prepare($sql);

        if ($stmt) {
          $stmt->bind_param("sssi", $plate_number, $model, $color, $company);
      
          if (!$stmt->execute()) {
              error_log("Database error: " . $stmt->error);
              echo "Database error: " . $stmt->error; // Temporarily display for debugging
          } else {
              //Record Activity
              require_once "activityLogsFunction.php";
              $activity_id = 17; // represents the activity ID for adding a new van
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
    <li class="breadcrumb-item active" aria-current="page"><h3>Van Management</h3></li>
  </ol>
</nav>

<!-- Begin page content -->
<main role="main" class="flex-shrink-0">
  <div class="container-fluid">
    <div class="card border-primary mb-5">
      <div class="card-header" style="color: white; background-color: #007BFF;">
        <strong>Add New Van</strong>
      </div>
      <div class="card-body">
        <form method="post" action="">
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <input type="text" name="plate_number" placeholder="Plate Number" class="form-control" value="<?php echo isset($plate_number) ? $plate_number : '';?>">
              <span class="errors"><?php echo $errors['plate_number'] ?? ''; ?></span>
            </div>

            <div class="form-group">
              <input type="text" name="model" placeholder="Model" class="form-control" value="<?php echo isset($model) ? $model : '';?>">
              <span class="errors"><?php echo $errors['model'] ?? ''; ?></span>
            </div>
          </div>
          <!-- /.col -->
          <div class="col-md-6">
            <div class="form-group">
              <input type="text" name="color" placeholder="Color" class="form-control" value="<?php echo isset($lastname) ? $color : '';?>">
              <span class="errors"><?php echo $errors['color'] ?? ''; ?></span>
            </div>

            <div class="form-group">
              <select name="company" class="form-control">
                <option value="" selected disbaled>Select Company</option>
                <?php foreach ($companies as $company): ?>
                  <option value="<?php echo $company['id']; ?>"><?php echo $company['company_name']; ?></option>
                <?php endforeach; ?>
              </select>
              <span class="errors"><?php echo $errors['company'] ?? ''; ?></span>
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
        <?php if (!empty($errorMessage)): ?>
          <div class="row mt-3">
            <div class="col-md-12">
              <div class="alert alert-danger alert-dismissible fade show" role="alert">
                  <strong>Administrator:</strong> <span class="error-message"><?php echo $errorMessage; ?></span>
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

    <!-- List of Vans -->
    <div class="card border-secondary mb-3">
      <div class="card-header" style="color: white; background-color: #6C757D;">
        <strong>List of Vans</strong>
      </div>
        <div class="card-body">
          <div class="table-responsive">
            <table id="tblVanManagement" class="table table-striped table-bordered table-sm table-hover">
              <thead>
                  <tr class="text-center">
                    <!-- <th class="hidden">ID</th> -->
                    <th class="hidden">ID</th>
                    <th>Company</th>
                    <th>Plate Number</th>
                    <th>Model</th>
                    <th>Color</th>
                    <th>Status</th>
                    <th>Assigned Driver</th>
                    <th>Assignment Date</th>
                    <th>Action</th>
                  </tr>
              </thead>
              <tbody class="text-center">
                  <?php if (isset($data) && is_array($data)): ?>
                    <?php foreach ($data as $row): ?>
                        <tr>
                            <td class="hidden"><?php echo htmlspecialchars($row['id']) ?? ''; ?></td>
                            <td><?php echo htmlspecialchars($row['company'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['plate_number'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['model'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['color'] ?? ''); ?></td>
                            <td>
                                <?php
                                    $badgeClass = match($row['status']) {
                                        'available' => 'badge badge-success',
                                        'on trip'   => 'badge badge-warning',
                                        default     => 'badge badge-danger'
                                    };
                                    echo "<span class='$badgeClass'>" . ucfirst($row['status']) . "</span>";
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['driver_name'] ?? ''); ?></td>
                            <td><?php echo isset($row['assignment_date']) ? htmlspecialchars(date('F j, Y g:i A', strtotime($row['assignment_date']))) : 'N/A'; ?></td>
                            <td class="text-center">
                              <div class="dropdown">
                                <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false">
                                  Action
                                </button>
                                <div class="dropdown-menu" aria-labelledby="vanActions">
                                    <a class="dropdown-item" href="van_update.php?id=<?= urlencode($row['id']) ?>">
                                        Update
                                    </a>
                                    <?php if($row['status'] !== "deactivated"): ?>
                                        <a class="dropdown-item" href="van_delete.php?id=<?= urlencode($row['id']) ?>">
                                            Delete
                                        </a>
                                    <?php else: ?>
                                        <a class="dropdown-item" href="van_restore.php?id=<?= urlencode($row['id']) ?>">
                                            Restore
                                        </a>
                                    <?php endif; ?>
                                    <?php if(empty($row['driver_id']) && $row['status'] !== "deactivated"): ?>
                                        <a class="dropdown-item" href="van_assigned_driver.php?id=<?= urlencode($row['id']) ?>">
                                            Assign Driver
                                        </a>
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
          $('#tblVanManagement').DataTable({
              "order": [[0, "asc"]],
              // Customize the layout to move the search box to the right
              "dom": '<"d-flex justify-content-between"lfr>t<"d-flex justify-content-between"ip>',
          });
      });
    </script>
  </body>
</html>