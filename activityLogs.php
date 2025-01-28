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

// Prepare the SQL statement
$stmt = $conn->prepare("
    SELECT 
        ref_activitylogs.id AS logsID, 
        ref_activitylogs.activityID AS activityID,
        ref_activitylogs.roleID AS roleID,
        ref_activitylogs.createdBy AS createdBy,
        ref_activitylogs.createdAt AS createdAt,
        ref_activitytypes.activityTitle AS activityTitle,
        ref_roles.role_name AS roleDescription,
        users.firstname AS firstname,
        users.middlename AS middlename,
        users.lastname AS lastname,
        users.qualifier AS qualifier
    FROM ref_activitylogs
    LEFT JOIN ref_activitytypes ON ref_activitylogs.activityID = ref_activitytypes.id
    LEFT JOIN ref_roles ON ref_activitylogs.roleID = ref_roles.id
    LEFT JOIN users ON ref_activitylogs.createdBy = users.id
    ORDER BY ref_activitylogs.id DESC
");

// Execute the statement
$stmt->execute();

// Get the result
$result = $stmt->get_result();

// Store data
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        'logsID' => $row['logsID'],
        'activityID' => $row['activityID'],
        'roleID' => $row['roleID'],
        'createdBy' => $row['createdBy'],
        'createdAt' => $row['createdAt'],
        'activityTitle' => $row['activityTitle'],
        'roleDescription' => $row['roleDescription'],
        'firstname' => $row["firstname"],
        'middlename' => $row["middlename"],
        'lastname' => $row["lastname"],
        'qualifier' => $row["qualifier"],
    ];
}

// Check if there's a success message in session and display it
if (isset($_SESSION['success'])) {
    $successMessage = $_SESSION['success'];
    unset($_SESSION['success']); // Clear session data
}

// Close the database connection
$stmt->close();
$conn->close();
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item active" aria-current="page"><h3>Activity Logs</h3></li>
  </ol>
</nav>

<!-- Begin page content -->
<main role="main" class="flex-shrink-0">
  <div class="container-fluid">
    <!-- List of Users -->
    <div class="card border-primary mb-3">
      <div class="card-header" style="color: white; background-color: #007BFF;">
        <strong>List of User Activities</strong>
      </div>
        <div class="card-body">
          <div class="table-responsive">
            <table id="tblActivityLogs" class="table table-striped table-bordered table-sm table-hover">
              <thead>
                  <tr class="text-center">
                      <th class="hidden">ID</th>
                      <th>Date</th>
                      <th>Time</th>
                      <th>User Role</th>
                      <th>Name</th>
                      <th>Activity</th>
                  </tr>
              </thead>
              <tbody class="text-center">
                  <?php if (isset($data) && is_array($data)): ?>
                    <?php foreach ($data as $row): ?>
                        <tr>
                            <td class="hidden"><?php echo htmlspecialchars($row['logsID']) ?? ''; ?></td>
                            <td><?php echo isset($row['createdAt']) ? htmlspecialchars(date('Y-m-d', strtotime($row['createdAt']))) : ''; ?></td>
                            <td><?php echo isset($row['createdAt']) ? htmlspecialchars(date('H:i:s', strtotime($row['createdAt']))) : ''; ?></td>
                            <td><?php echo htmlspecialchars($row['roleDescription']) ?? ''; ?></td>
                            <td><?php echo htmlspecialchars($row["firstname"] . " " . $row["middlename"] . " " . $row["lastname"] . " " . $row["qualifier"]) ?? ''; ?></td>
                            <td><?php echo htmlspecialchars($row['activityTitle']) ?? ''; ?></td>
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
          $('#tblActivityLogs').DataTable({
              "order": [[0, "desc"]],
              // Customize the layout to move the search box to the right
              "dom": '<"d-flex justify-content-between"lfr>t<"d-flex justify-content-between"ip>',
          });
      });
    </script>
  </body>
</html>