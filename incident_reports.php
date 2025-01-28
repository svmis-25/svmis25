<?php
session_start();
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

/*===================================
  Query: Retrieve Data from Database
=====================================*/

$sql = "SELECT 
            incidents.id AS incidentID,
            incidents.type_of_emergency AS type_of_emergency,
            incidents.details_of_emergency AS details_of_emergency,
            incidents.report_date AS report_date,
            drivers.id AS driverID,
            drivers.firstname AS firstname,
            drivers.lastname AS lastname,
            drivers.middlename AS middlename,
            drivers.qualifier AS qualifier
        FROM
            incidents
        LEFT JOIN drivers ON incidents.driver_id = drivers.id
        ORDER BY
            incidents.id DESC
";

// Fetch data
$result = $conn->query($sql);

// Store Data
$data = []; // Initialize data
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'incidentID' => $row['incidentID'],
            'type_of_emergency' => $row['type_of_emergency'],
            'details_of_emergency' => $row['details_of_emergency'],
            'report_date' => $row['report_date'],
            'driver_name' => $row['firstname'] . ' ' . $row['middlename'] . ' ' . $row['lastname'] . ' ' . $row['qualifier'],
        ];
    }
}

// Check if there's a success message in session and display it
if (isset($_SESSION['success'])) {
    $successMessage = $_SESSION['success'];
    unset($_SESSION['success']); // Clear session data
}

// Close the database connection
$conn->close();
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item active" aria-current="page"><h3>Incident Reports</h3></li>
  </ol>
</nav>

<!-- Begin page content -->
<main role="main" class="flex-shrink-0">
  <div class="container-fluid">
    <!-- List of Users -->
    <div class="card border-primary mb-3">
      <div class="card-header" style="color: white; background-color: #007BFF;">
        <strong>List of Incident Reports</strong>
      </div>
        <div class="card-body">
          <div class="table-responsive">
            <table id="tblIncidentReports" class="table table-striped table-bordered table-sm table-hover">
              <thead>
                  <tr class="text-center">
                      <th class="hidden">ID</th>
                      <th>Type of Emergency</th>
                      <th>Details of Emergency</th>
                      <th>Report Date</th>
                      <th>Assigned Driver</th>
                  </tr>
              </thead>
              <tbody class="text-center">
                  <?php if (isset($data) && is_array($data)): ?>
                    <?php foreach ($data as $row): ?>
                        <tr>
                            <td class="hidden"><?php echo htmlspecialchars($row['incidentID']) ?? ''; ?></td>
                            <td><?php echo htmlspecialchars($row['type_of_emergency']) ?? ''; ?></td>
                            <td><?php echo htmlspecialchars($row['details_of_emergency']) ?? ''; ?></td>
                            <td><?php echo isset($row['report_date']) ? htmlspecialchars(date('F j, Y g:i A', strtotime($row['report_date']))) : ''; ?></td>
                            <td><?php echo htmlspecialchars($row['driver_name']) ?? ''; ?></td>
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
          $('#tblIncidentReports').DataTable({
              "order": [[0, "desc"]],
              // Customize the layout to move the search box to the right
              "dom": '<"d-flex justify-content-between"lfr>t<"d-flex justify-content-between"ip>',
          });
      });
    </script>
  </body>
</html>