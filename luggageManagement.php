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
            passengers.is_active,
            drivers.id AS driverID,
            drivers.firstname AS driver_firstname,
            drivers.middlename AS driver_middlename,
            drivers.lastname AS driver_lastname,
            drivers.qualifier AS driver_qualifier
        FROM passengers_luggage 
        LEFT JOIN passengers ON passengers_luggage.passenger_id = passengers.id
        LEFT JOIN drivers ON passengers_luggage.driver_id = drivers.id";


// Check if search query exists
// if (isset($_GET['search']) && !empty($_GET['search'])) {
//     $search = sanitizeInput($_GET['search']);
//     $sql .= " WHERE passengers.firstname LIKE ? OR passengers.middlename LIKE ? OR passengers.lastname LIKE ? OR passengers.qualifier LIKE ? OR passengers.contact LIKE ? OR passengers.email LIKE ?";
//     $stmt = $conn->prepare($sql);
//     $searchTerm = "%$search%";  // Add wildcard for LIKE query
//     $stmt->bind_param("ssssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
// } else {
    $stmt = $conn->prepare($sql);
// }

$stmt->execute();
$stmt->store_result();

// Bind result variables (adjusted based on SELECT query columns)
$stmt->bind_result($luggage_id, $passenger_id, $driver_id, $luggage_code, $description, $size, $status, $created_at, $trip_id, $passengerID, $firstname, $middlename, $lastname, $qualifier, $contact, $email, $is_active,
$driverID, $driver_firstname, $driver_middlename, $driver_lastname, $driver_qualifier);

$data = []; // Initialize an empty array to hold the data

if ($stmt->num_rows > 0) {
    while ($stmt->fetch()) {
        $data[] = [
            'luggage_id' => $luggage_id ?? '',
            'passenger_id' => $passenger_id ?? '',
            'driver_id' => $driver_firstname . ' ' . $driver_middlename . ' ' . $driver_lastname . ' ' . $driver_qualifier ?? '',
            'luggage_code' => $luggage_code ?? '',
            'description' => $description ?? '',
            'size' => $size ?? '',
            'status' => $status ?? '',
            'created_at' => $created_at ?? '',
            'trip_id' => $trip_id ?? '',
            'name' => $firstname . ' ' . $middlename . ' ' . $lastname . ' ' . $qualifier, 
            'contact' => $contact ?? '',
            'email' => $email ?? '',
            'is_active' => $is_active ?? '',
        ];
    }
}

// Close the statement
$stmt->close();


/*===================================
  Function: Luggage Code Generator
=====================================*/
function generateLuggageCode($passenger_id) {
  $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
  $luggageCode = '';

  // Generate a random 50-character code from the $characters string
    for ($i = 0; $i < 50; $i++) {
        $luggageCode .= $characters[rand(0, strlen($characters) - 1)];
    }
  // Append Passenger_id to the luggage code
  $luggageCode = $passenger_id . "-" . $luggageCode;
  return $luggageCode;
}

/*===================================
  Query: Insert Data into Database
=====================================*/
// Variables to hold messages
$successMessage = ""; // Initialize empty message for success submission result
$errors = []; // Initialize Array to hold error messages 

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $passenger_id = sanitizeInput($_POST['passenger_id']);
    $driver_id = ''; // Driver ID is not yet implemented, this will be updated later when driver scans the luggage
    $luggage_code = generateLuggageCode($passenger_id);
    $description = sanitizeInput($_POST['description']);
    $size = sanitizeInput($_POST['size']);
    $status =  'Pending Verification'; // Default status
    $trip_id = ''; // Trip ID is not yet implemented, this will be updated later when driver scans the luggage
    
  // Initialize Array to hold error messages
  $errors = [];

    // Validate the input
    if (empty($passenger_id)) {
        $errors['passenger_id'] = "Passenger ID is required.";
    }

    if (empty($description)) {
        $errors['description'] = "Description is required.";
    }

    if (empty($size)) {
        $errors['size'] = "Size is required.";
    }


  // If there are no validation errors, insert the data
  if (empty($errors)) {
      $sql = "INSERT INTO passengers_luggage (passenger_id, driver_id, luggage_code, description, size, status, trip_id) 
              VALUES (?, ?, ?, ?, ?, ?, ?)";
      $stmt = $conn->prepare($sql);

      if ($stmt) {
          $stmt->bind_param("isssssi", $passenger_id, $driver_id, $luggage_code, $description, $size, $status, $trip_id);
          if ($stmt->execute()) {
              require_once "activityLogsFunction.php";
              saveActivityLog($current_user_role_id, 3, $current_user_id); // Activity ID for adding a user
              $_SESSION['success-transaction'] = "Data inserted successfully!";
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

<style>
  #suggestions {
      width: 100%;
      border-radius: 4px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  }

  .suggestion-item {
      padding: 10px;
      cursor: pointer;
  }

  .suggestion-item:hover {
      background-color: #f1f1f1;
  }
</style>

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
        <!-- Search for Passenger -->
        <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="form-group d-flex">
                <input type="text" name="search" id="search" placeholder="Search Passenger" class="form-control mr-2">
                <span class="errors"><?php echo $errors['search'] ?? ''; ?></span>
                <span class="errors" id="searchError"></span>
            </div>
            <div id="suggestions" style="display: none; position: absolute; z-index: 1000; background-color: white; border: 1px solid #ccc; max-height: 200px; overflow-y: auto;"></div>
        </div>
        </div>

        <!-- Form for displaying search results -->
        <form method="post" action="">
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <input type="text" name="firstname" placeholder="Firstname" class="form-control" value="" readonly>
            </div>

            <div class="form-group">
              <input type="text" name="middlename" placeholder="Middlename" class="form-control" value="" readonly>
            </div>

            <div class="form-group">
              <input type="text" name="lastname" placeholder="Lastname" class="form-control" value="" readonly>
            </div>

            <div class="form-group">
            <input type="text" name="description" id="description" placeholder="Enter Description" class="form-control" required>
            </div>

          </div>
          <!-- /.col -->
          <div class="col-md-6">
            <div class="form-group">
              <input type="text" name="qualifier" placeholder="Qualifier" class="form-control" value="" readonly>
            </div>

            <div class="form-group">
              <input type="text" name="contactNo" id="contactNo" placeholder="Contact No" class="form-control" value="" readonly>
            </div>

            <div class="form-group">
              <input type="email" name="email" placeholder="Email" class="form-control" value="" readonly>
            </div>

            <div class="form-group">
              <select name="size" id="size" class="form-control" required>
                <option value="" selected disabled>Select Size</option>
                <option value="Small">Small</option>
                <option value="Medium">Medium</option>
                <option value="Large">Large</option>
              </select>
            </div>

          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
        <div class="row">
          <div class="col-md-12 text-right">
            <input type="hidden" name="passenger_id" id="passenger_id" value="<?php echo isset($passenger_id) ? $passenger_id : ''; ?>">
            <button type="submit" class="btn btn-primary">Submit</button>
          </div>
        </div>
      </form>
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
                        <th class="hidden">Trip ID</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>View</th>
                  </tr>
              </thead>
              <tbody>
                  <?php if (isset($data) && is_array($data)): ?>
                    <?php foreach ($data as $row): ?>
                        <tr class="text-center">
                            <td class="hidden"><?php echo htmlspecialchars($row['luggage_id']) ?? ''; ?></td>
                            <td><?php echo htmlspecialchars($row['name']) ?? ''; ?></td>
                            <td><?php echo htmlspecialchars($row['contact']) ?? ''; ?></td>
                            <td><?php echo htmlspecialchars($row['description']) ?? ''; ?></td>
                            <td><?php echo htmlspecialchars($row['size']) ?? ''; ?></td>
                            <td><?php echo $row['driver_id'] == 0 ? "-" : htmlspecialchars($row['driver_id']); ?></td>
                            <td class="hidden"><?php echo $row['trip_id'] == 0 ? "-" : htmlspecialchars($row['trip_id']); ?></td>
                            <td><?php echo isset($row['created_at']) ? htmlspecialchars(date('Y-m-d', strtotime($row['created_at']))) : ''; ?></td>
                            <td>
                                <?php
                                if ($row['status'] === 'Pending Verification') {
                                    echo '<span class="badge badge-warning">Pending Verification</span>';
                                } elseif ($row['status'] === 'Checked-in') {
                                    echo '<span class="badge badge-primary">Checked-in</span>';
                                } elseif ($row['status'] === 'In-transit') {
                                    echo '<span class="badge badge-info">In-transit</span>';
                                } elseif ($row['status'] === 'Claimed') {
                                    echo '<span class="badge badge-success">Claimed</span>';
                                } elseif ($row['status'] === 'Lost') {
                                    echo '<span class="badge badge-danger">Lost</span>';
                                } elseif ($row['status'] === 'Damaged') {
                                    echo '<span class="badge badge-dark">Damaged</span>';
                                } else {
                                    echo '<span class="badge badge-secondary">Unknown</span>';
                                }
                                ?>
                            </td>
                            <!-- <td><a href="print_QR_code?id=<?php echo urlencode($row['luggage_id']); ?>" target="_blank" title="View"><i class="fas fa-qrcode"></i></a></td> -->
                            <td>
                                <a href="#" class="view-qr-code" data-id="<?php echo $row['luggage_id']; ?>" title="View QR Code">
                                    <i class="fas fa-qrcode"></i>
                                </a>
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

<!-- Modal to display the QR code -->
<div class="modal fade" id="qrCodeModal" tabindex="-1" role="dialog" aria-labelledby="qrCodeModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="qrCodeModalLabel">Luggage QR Code</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body text-center">
        <p class="text-center">
            <span style="font-size: 20px; font-weight: bold; color: green;">Ready to Scan</span><br>
            <span style="font-size: 14px;">Please have the driver scan this QR code using the Driver App to match him to the booking.</span>
        </p>
        <img id="qrCodeImage" src="" alt="QR Code" style="max-width: 100%; height: auto;" />
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" onclick="printQRCode()">Print QR Code</button>
      </div>
    </div>
  </div>
</div>

<?php include "partials/footer.php"; ?>

<!-- Bootstrap JS and any other script -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>    
    <script src="https://cdn.datatables.net/1.11.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.4/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
$(document).ready(function () {
    // Function to fetch passengers based on search input
    $('#search').on('input', function () {
        var searchQuery = $(this).val();
        if (searchQuery.length >= 2) { // Only search if 2 or more characters are entered
            $.ajax({
                url: 'searchPassengers.php', // Create this PHP file to handle the search
                type: 'GET',
                data: { search: searchQuery },
                success: function (response) {
                    var passengers = JSON.parse(response);
                    var suggestions = $('#suggestions');
                    suggestions.empty();
                    if (passengers.length > 0) {
                        passengers.forEach(function (passenger) {
                            suggestions.append('<div class="suggestion-item" data-id="' + passenger.id + '">' + passenger.name + '</div>');
                        });
                        suggestions.show();
                    } else {
                        suggestions.hide();
                    }
                }
            });
        } else {
            $('#suggestions').hide();
        }
    });

    // Function to handle selection of a passenger
    $(document).on('click', '.suggestion-item', function () {
        var passengerId = $(this).data('id');
        var passengerName = $(this).text();
        $('#search').val(passengerName);
        $('#passenger_id').val(passengerId);
        $('#suggestions').hide();

        // Fetch and display passenger details
        $.ajax({
            url: 'getPassengerDetails.php', // Create this PHP file to fetch passenger details
            type: 'GET',
            data: { id: passengerId },
            success: function (response) {
                var passenger = JSON.parse(response);
                $('input[name="passenger_id"]').val(passenger.id);
                $('input[name="firstname"]').val(passenger.firstname);
                $('input[name="middlename"]').val(passenger.middlename);
                $('input[name="lastname"]').val(passenger.lastname);
                $('input[name="qualifier"]').val(passenger.qualifier);
                $('input[name="contactNo"]').val(passenger.contact);
                $('input[name="email"]').val(passenger.email);
            }
        });
    });

    // Hide suggestions when clicking outside
    $(document).on('click', function (e) {
        if (!$(e.target).closest('#search').length) {
            $('#suggestions').hide();
        }
    });
});
</script>
<script>
$(document).ready(function () {
    // Handle click event for QR code icon
    $(document).on('click', '.view-qr-code', function (e) {
        e.preventDefault(); // Prevent default link behavior

        var luggageId = $(this).data('id'); // Get the luggage ID from the data-id attribute

        // Show the loader using SweetAlert2
        Swal.fire({
            icon: 'info',
            title: 'Processing...',
            text: 'Please wait while we fetch the QR code.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            },
        });

        // Fetch the QR code for the selected luggage
        $.ajax({
            url: 'fetch_LuggageQRCode.php', // Create this PHP file to fetch the QR code
            type: 'GET',
            data: { id: luggageId },
            success: function (response) {
              console.log(response); // Log the response to see what is returned
              Swal.close(); // Close the loader
                var qrCode = JSON.parse(response);
                console.log("Base64 QR Code: ", qrCode.qr_code); // Log the base64 QR code
                if (qrCode.success) {
                    // Update the modal with the fetched QR code
                    $('#qrCodeImage').attr('src', 'data:image/png;base64,' + qrCode.qr_code);
                    $('#qrCodeModal').modal('show'); // Show the modal
                } else {
                    alert('Failed to fetch QR code.');
                }
            },
            error: function () {
                alert('An error occurred while fetching the QR code.');
            }
        });
    });
});
</script>
<script>
function printQRCode() {
  var qrCodeImage = document.getElementById("qrCodeImage"); // Get the QR code image
  
  // Check if the QR code image exists before trying to access its src
  if (!qrCodeImage) {
    alert("QR code is not available to print.");
    return;
  }

  var qrCodeSrc = qrCodeImage.src; // Get the base64-encoded source of the QR code
  var printWindow = window.open('', '', 'height=400,width=600'); // Open a new window for printing
  
  // Wait for the print window to open and write content
  printWindow.document.write('<html><head><title>Print QR Code</title>');
  
  // Adding styles to match the modal's appearance
  printWindow.document.write('<style>');
  printWindow.document.write('body { font-family: Arial, sans-serif; margin: 20px; text-align: center; }');
  printWindow.document.write('.qr-text { font-size: 20px; font-weight: bold; color: green; }');
  printWindow.document.write('.qr-description { font-size: 14px; }');
  printWindow.document.write('.qr-image { margin-top: 20px; }');
  printWindow.document.write('</style>');

  printWindow.document.write('</head><body>');
  printWindow.document.write('<p class="qr-text">Luggage QR Code</p>');
  printWindow.document.write('<p class="qr-description">Please have the driver scan this QR code using the Driver App to match him to the booking.</p>');
  
  // Add the QR code image using the base64 source
  printWindow.document.write('<img class="qr-image" src="' + qrCodeSrc + '" />');
  printWindow.document.write('</body></html>');
  printWindow.document.close(); // Close the document to finish writing

  // Trigger the print dialog
  printWindow.print();
}
</script>
<script>
  $(document).ready(function() {
    // Listen for the modal close event
    $('#qrCodeModal').on('hidden.bs.modal', function() {
      // Redirect to the passengerManagement page
      window.location.href = 'luggageManagement'; // Adjust the URL as needed
    });
  });
</script>
</body>
</html>