<?php
ob_start();
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: index");
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

$sql = "SELECT * FROM ref_companies";

// Fetch data
$result = $conn->query($sql);

// Store Data
$data = []; // Initialize data
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'id' => $row['id'],
            'company_name' => $row['company_name'],
            'address' => $row['address'],
            'contactNo' => $row['contact'],
            'email' => $row['email'],
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
    $company_name = $_POST['company_name'];
    $address = $_POST['address'];
    $contactNo = $_POST['contactNo'];
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
    if (isset($contactNo)){
        $contactNo = sanitizeInput($contactNo);
        // validate contact
        if (!preg_match("/^[0-9]{10,15}$/", $contactNo) || empty($contactNo)) {
            $errors['contact'] = "Invalid contact number.";
        }
    }

    // Validate email
    if (isset($email)){
        $email = sanitizeInput($email);
        // validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || empty($email)) {
            $errors['email'] = "Invalid email format.";
        } else {
            // Check if email already exists in the database
            $sql = "SELECT id FROM ref_companies WHERE email = ?";
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

    // If there are no errors, insert data into database
    if (empty($errors)) { 
        // Prepare the SQL query to insert data
        $sql = "INSERT INTO ref_companies (company_name, email, address, contact) VALUES (?, ?, ?, ?)";

        // Create and prepare statement 
        $stmt = $conn->prepare($sql);

        if($stmt){
            // Bind parameters
            $stmt->bind_param("ssss", $company_name, $email, $address, $contactNo);

            // check if the statement is executed
            if($stmt->execute()){

              //Record Activity
              require_once "activityLogsFunction.php";
              $activity_id = 8; // represents the activity ID for adding a new company
              saveActivityLog($current_user_role_id, $activity_id, $current_user_id);


                // Set session success message
                $_SESSION['success'] = "Data inserted successfully!";
            } else {
                echo "Data insertion failed: $stmt->error";
            }

            // Close the statement
            $stmt->close();
        }
        // Redirect to index after form submission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
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
    <li class="breadcrumb-item active" aria-current="page"><h3>Company Management</h3></li>
  </ol>
</nav>

<!-- Begin page content -->
<main role="main" class="flex-shrink-0">
  <div class="container-fluid">
    <div class="card border-primary mb-5">
      <div class="card-header" style="color: white; background-color: #007BFF;">
        <strong>Add Company</strong>
      </div>
      <div class="card-body">
        <form method="post" action="">

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <input type="text" name="company_name" placeholder="Company Name" class="form-control" value="<?php echo isset($company_name) ? $company_name : '';?>">
                <span class="errors"><?php echo $errors['company_name'] ?? ''; ?></span>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <input type="text" name="address" placeholder="Address" class="form-control" value="<?php echo isset($address) ? $address : '';?>">
                <span class="errors"><?php echo $errors['address'] ?? ''; ?></span>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <input type="text" name="contactNo" placeholder="Contact No" class="form-control" value="<?php echo isset($contactNo) ? $contactNo : '';?>">
                <span class="errors"><?php echo $errors['contact'] ?? ''; ?></span>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <input type="email" name="email" placeholder="Email" class="form-control" value="<?php echo isset($email) ? $email : '';?>">
                <span class="errors"><?php echo $errors['email'] ?? ''; ?></span>
              </div>
            </div>
          </div>

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
        <strong>List of Users</strong>
      </div>
        <div class="card-body">
          <div class="table-responsive">
            <table id="tblUserManagement" class="table table-striped table-bordered table-sm table-hover">
              <thead>
                  <tr class="text-center">
                      <th class="hidden">ID</th>
                      <th>Name</th>
                      <th>Address</th>
                      <th>Contact No</th>
                      <th>Email</th>
                      <th>Status</th>
                      <th>Action</th>
                  </tr>
              </thead>
              <tbody class="text-center">
                  <?php if (isset($data) && is_array($data)): ?>
                    <?php foreach ($data as $row): ?>
                        <tr>
                            <td class="hidden"><?php echo htmlspecialchars($row['id']) ?? ''; ?></td>
                            <td><?php echo htmlspecialchars($row['company_name']) ?? ''; ?></td>
                            <td><?php echo htmlspecialchars($row['address']) ?? ''; ?></td>
                            <td><?php echo htmlspecialchars($row['contactNo']) ?? ''; ?></td>
                            <td><?php echo htmlspecialchars($row['email']) ?? ''; ?></td>
                            <td class="text-center"><?php echo htmlspecialchars($row['isActive']) ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-danger">Inactive</span>'; ?></td>
                            <td class="text-center">
                              <div class="dropdown">
                                <button class="btn btn-secondary dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false">
                                  Action
                                </button>
                                <div class="dropdown-menu">
                                  <a class="dropdown-item" href="company_update?id=<?php echo urlencode($row['id']); ?>" onclick="checkUrl(event, this.href)">Update</a>
                                  <?php if($row['isActive'] == 1): ?>
                                      <a class="dropdown-item" href="company_delete?id=<?php echo urlencode($row['id']); ?>" onclick="checkUrl(event, this.href)">Delete</a>
                                  <?php elseif ($row['isActive'] == 0): ?>
                                      <a class="dropdown-item" href="company_restore?id=<?php echo urlencode($row['id']); ?>" onclick="checkUrl(event, this.href)">Restore</a>
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
function checkUrl(event, url) {
    event.preventDefault(); // Prevent the default link behavior

    // Use fetch to send a HEAD request to the URL
    fetch(url, { method: 'HEAD' })
        .then(response => {
            if (response.ok) {
                // If the URL is valid, navigate to it
                window.location.href = url;
            } else {
                // Redirect to 404 page if URL is not valid
                window.location.href = "errors/404.php";
            }
        })
        .catch(() => {
            // Handle network or other fetch errors
            window.location.href = "errors/404.php";
        });
}
</script>

  </body>
</html>