<?php
session_start();
require_once __DIR__ . '/config.php';

// Ensure the user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: index");
    exit;
}

$active = "command_center";
include "partials/header.php";

$current_user_id = $_SESSION["userID"];
$current_user_role_id = $_SESSION["userrole"];

/*===========================================
  Query: Fetch Drivers and Vans from Database
=============================================*/

$drivers = [];
$vans = [];
$incidents = [];
$trip = [];

$sqlDrivers = "SELECT * FROM drivers";
$resultDrivers = $conn->query($sqlDrivers);

if ($resultDrivers->num_rows > 0) {
    while ($row = $resultDrivers->fetch_assoc()) {
        $drivers[] = $row;
    }
}

$sqlVans = "SELECT * FROM vans";
$resultVans = $conn->query($sqlVans);

if ($resultVans->num_rows > 0) {
    while ($row = $resultVans->fetch_assoc()) {
        $vans[] = $row;
    }
}

$sqlIncidents = "SELECT * FROM incidents";
$resultIncidents = $conn->query($sqlIncidents);

if ($resultIncidents->num_rows > 0) {
    while ($row = $resultIncidents->fetch_assoc()) {
        $incidents[] = $row;
    }
}

$sqlTrip = "SELECT * FROM transactions WHERE is_complete = 1";
$resultTrip = $conn->query($sqlTrip);

if ($resultTrip->num_rows > 0) {
    while ($row = $resultTrip->fetch_assoc()) {
        $trips[] = $row;
    }
}

// Function to fetch driver name
function getDriverName($conn, $driverId) {
    if ($driverId) {
        $sqlDriverName = "SELECT * FROM drivers WHERE id = ?";
        $stmt = $conn->prepare($sqlDriverName);
        $stmt->bind_param("i", $driverId); // Assuming driver_id is an integer
        $stmt->execute();
        $resultDriverName = $stmt->get_result();
        
        if ($resultDriverName->num_rows > 0) {
            $driver = $resultDriverName->fetch_assoc();
            // Display only the first letter of the middlename, if it's not empty
            $middlename = !empty($driver['middlename']) ? substr($driver['middlename'], 0, 1) . '' : '';
            return htmlspecialchars($driver['firstname'] . ' ' . $middlename . ' ' . $driver['lastname'] . ' ' . $driver['qualifier'] ?? '');
        }
    }
    return "Unassigned";
}

// Function to fetch company name
function getCompanyName($conn, $companyId) {
    if ($companyId) {
        $sqlCompanyName = "SELECT * FROM ref_companies WHERE id = ?";
        $stmt = $conn->prepare($sqlCompanyName);
        $stmt->bind_param("i", $companyId); // Assuming company_id is an integer
        $stmt->execute();
        $resultCompanyName = $stmt->get_result();
        
        if ($resultCompanyName->num_rows > 0) {
            $company = $resultCompanyName->fetch_assoc();
            return htmlspecialchars($company['company_name'] ?? '');
        }
    }
    return "Unassigned";
}

// function to fetch incidents
function getIncidents($conn) {
    $sqlIncidents = "SELECT * FROM incidents";
    $resultIncidents = $conn->query($sqlIncidents);
    return $resultIncidents;
}

// function to fetch available vans
function getAvailableVans($conn) {
    $sqlAvailableVans = "SELECT * FROM vans WHERE status = 'available'";
    $resultAvailableVans = $conn->query($sqlAvailableVans);
    return $resultAvailableVans;
}

// function to fetch ongoing vans
function getOngoingVans($conn) {
    $sqlOngoingVans = "SELECT * FROM vans WHERE status = 'ongoing'";
    $resultOngoingVans = $conn->query($sqlOngoingVans);
    return $resultOngoingVans;
}

// function to get trip history
function getTripHistory($conn) {
    $sqlTripHistory = "SELECT trip_id FROM transactions WHERE is_complete = 1";
    $resultTripHistory = $conn->query($sqlTripHistory);
    return $resultTripHistory;
}
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item active" aria-current="page"><h3>Command Center</h3></li>
  </ol>
</nav>

<!-- Begin page content -->
<main role="main" class="flex-shrink-0">
  <div class="container-fluid"> <!-- Changed container to container-fluid -->
    <div class="row mt-3">
      <!-- Card: Incident Reports -->
      <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 mb-4"> <!-- Modified column sizes -->
        <div class="card bg-info text-white shadow">
          <div class="card-body d-flex justify-content-between align-items-center">
            <div>
              <h3><?php echo getIncidents($conn)->num_rows; ?></h3>
              <p>Incident Reports</p>
            </div>
            <i class="fas fa-exclamation-triangle fa-3x"></i>
          </div>
          <div class="card-footer d-flex justify-content-center align-items-center">
            <a href="#incidents" class="more-info text-white">More info &nbsp;
              <i class="fas fa-arrow-circle-right"></i>
            </a>
          </div>
        </div>
      </div>

      <!-- Card: Available Vans -->
      <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 mb-4"> <!-- Modified column sizes -->
        <div class="card bg-success text-white shadow">
          <div class="card-body d-flex justify-content-between align-items-center">
            <div>
              <h3><?php echo getAvailableVans($conn)->num_rows; ?></h3>
              <p>Available Vans</p>
            </div>
            <i class="fas fa-bus-alt fa-3x"></i>
          </div>
          <div class="card-footer d-flex justify-content-center align-items-center">
            <a href="#vans" class="more-info text-white">More info &nbsp;
              <i class="fas fa-arrow-circle-right"></i>
            </a>
          </div>
        </div>
      </div>

      <!-- Card: Ongoing Vans -->
      <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 mb-4"> <!-- Modified column sizes -->
        <div class="card bg-warning text-white shadow">
          <div class="card-body d-flex justify-content-between align-items-center">
            <div>
              <h3><?php echo getOngoingVans($conn)->num_rows; ?></h3>
              <p>Ongoing Vans</p>
            </div>
            <i class="fas fa-bus fa-3x"></i>
          </div>
          <div class="card-footer d-flex justify-content-center align-items-center">
            <a href="#vans" class="more-info text-white">More info &nbsp;
              <i class="fas fa-arrow-circle-right"></i>
            </a>
          </div>
        </div>
      </div>

      <!-- Card: Trip History -->
      <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 mb-4"> <!-- Modified column sizes -->
        <div class="card bg-danger text-white shadow">
          <div class="card-body d-flex justify-content-between align-items-center">
            <div>
              <h3><?php echo getTripHistory($conn)->num_rows; ?></h3>
              <p>Trip History</p>
            </div>
            <i class="fas fa-history fa-3x"></i>
          </div>
          <div class="card-footer d-flex justify-content-center align-items-center">
            <a href="#trips" class="more-info text-white">More info &nbsp;
              <i class="fas fa-arrow-circle-right"></i>
            </a>
          </div>
        </div>
      </div>
    </div>

  <hr>

  <!-- Section: Incident Reports -->
  <section id="incidents">
    <div class="card border-primary mb-3">
      <div class="card-header" style="color: white; background-color: #007BFF;">
        <div class="row">
            <div class="col-md-8">
                <strong>Incident Reports</strong>
            </div>
            <div class="col-md-4 text-right">
                <a href="" class="btn btn-primary" style="color: #007BFF;">Incidents</a>
            </div>
        </div>
      </div>
        <div class="card-body">
          <div class="table-responsive">
            <table id="tblIncidentReports" class="table table-striped table-bordered table-sm table-hover">
              <thead>
                  <tr class="text-center">
                      <th class="hidden">ID</th>
                      <th>Incident</th>
                      <th>Details</th>
                      <th>Report Date</th>
                      <th>Assigned Driver</th>
                  </tr>
              </thead>
              <tbody class="text-center">
                  <?php if (isset($incidents) && is_array($incidents)): ?>
                    <?php foreach ($incidents as $incident): ?>
                        <tr>
                            <td class="hidden"><?php echo htmlspecialchars($incident['id']) ?? ''; ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($incident['type_of_emergency'])) ?? ''; ?></td>
                            <td><?php echo htmlspecialchars($incident['details_of_emergency']) ?? ''; ?></td>
                            <td><?php echo isset($incident['report_date']) ? htmlspecialchars(date('F j, Y g:i A', strtotime($incident['report_date']))) : ''; ?></td>
                            <td><?php echo getDriverName($conn, $incident['driver_id']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
    </div>
  </section>

  <hr>

  <!-- Section: Manage Drivers -->
  <section id="drivers">
    <div class="card border-info mb-3 mt-3">
        <div class="card-header" style="color: white; background-color: #17a2b8;">
            <div class="row">
                <div class="col-md-8">
                    <strong>Manage Drivers</strong>
                </div>
                <div class="col-md-4 text-right">
                    <a href="add_driver.php" class="btn btn-primary"><i class="fas fa-plus"></i> New Driver</a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="tblDriverManagement" class="table table-striped table-bordered table-sm table-hover">
                    <thead class="text-center">
                        <tr>
                            <th class="hidden">ID</th>
                            <th style="width: 20%;">Driver Name</th>
                            <th style="width: 20%;">Company</th>
                            <th style="width: 10%;">Work Status</th>
                            <th style="width: 10%;">Driving Status</th>
                            <th style="width: 10%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-center">
                        <?php if (isset($drivers)): ?>
                            <?php foreach ($drivers as $driver): ?>
                                <tr>
                                    <td class="hidden"><?php echo htmlspecialchars($driver['id']); ?></td>
                                    <td><?php echo getDriverName($conn, $driver['id']); ?></td>
                                    <td><?php echo getCompanyName($conn, $driver['company_id']); ?></td>
                                    <td><?php echo $driver['is_active'] ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-danger">Inactive</span>'; ?></td>
                                    <td>
                                        <?php
                                            // Determine badge class based on status
                                            $badgeClass = ($driver['status'] === 'available') ? 'badge badge-success' :
                                                        ($driver['status'] === 'unavailable' ? 'badge badge-danger' :
                                                        'badge badge-secondary'); // Default for other statuses

                                            // Display the badge with the status
                                            echo "<span class='$badgeClass'>" . ucfirst($driver['status']) . "</span>";
                                        ?>
                                    </td>
                                    <td>
                                    <div class="dropdown">
                                        <button class="btn btn-primary dropdown-toggle" type="button" id="driverActions" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        Actions
                                        </button>
                                        <div class="dropdown-menu" aria-labelledby="driverActions">
                                            <a class="dropdown-item" href="update_driver.php?id=<?php echo urlencode($driver['id']); ?>">Update</a>
                                            <a class="dropdown-item" href="delete_driver.php?id=<?php echo urlencode($driver['id']); ?>">Delete</a>
                                            <a class="dropdown-item" href="restore_driver.php?id=<?php echo urlencode($driver['id']); ?>">Restore</a>
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
    </div>
  </section>

  <hr>

  <!-- Section: Manage Vans -->
  <section id="vans">
    <div class="card border-secondary mb-3 mt-3">
        <div class="card-header" style="color: white; background-color: #6C757D;">
            <div class="row">
                <div class="col-md-8">
                    <strong>Manage Vans</strong>
                </div>
                <div class="col-md-4 text-right">
                    <a href="add_driver.php" class="btn btn-info"><i class="fas fa-plus"></i> New Van</a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="tblVanManagement" class="table table-striped table-bordered table-sm table-hover">
                    <thead class="text-center">
                        <tr>
                            <th class="hidden">ID</th>
                            <th>Plate Number</th>
                            <th>Model</th>
                            <th>Status</th>
                            <th>Assigned Driver</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-center">
                        <?php if (isset($vans)): ?>
                            <?php foreach ($vans as $van): ?>
                                <tr>
                                    <td class="hidden"><?php echo htmlspecialchars($van['id']); ?></td>
                                    <td><?php echo htmlspecialchars($van['plate_number']); ?></td>
                                    <td><?php echo htmlspecialchars($van['model']); ?></td>
                                    <td>
                                        <?php
                                            // Determine badge class based on status
                                            $badgeClass = ($van['status'] === 'available') ? 'badge badge-success' :
                                                        (($van['status'] === 'ongoing') ? 'badge badge-warning' :
                                                        'badge badge-danger'); // Assuming 'unavailable' or other statuses as default

                                            // Display the badge with the status
                                            echo "<span class='$badgeClass'>" . ucfirst($van['status']) . "</span>";
                                        ?>
                                    </td>
                                    <td><?php echo getDriverName($conn, $van['driver_id']); ?></td>
                                    <td>
                                    <div class="dropdown">
                                        <button class="btn btn-primary dropdown-toggle" type="button" id="vanActions" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        Actions
                                        </button>
                                        <div class="dropdown-menu" aria-labelledby="vanActions">
                                            <a class="dropdown-item" href="update_van.php?id=<?php echo urlencode($van['id']); ?>">Update</a>
                                            <a class="dropdown-item" href="delete_van.php?id=<?php echo urlencode($van['id']); ?>">Delete</a>
                                            <?php if($van['driver_id'] == null): ?>
                                            <a class="dropdown-item" href="assign_driver.php?van_id=<?php echo urlencode($van['id']); ?>">Assign Driver</a>
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
    </div>
  </section>

  <hr>

  <!-- Section: Trip History -->
  <section id="trips">
    <div class="card border-danger mb-3 mt-3">
        <div class="card-header" style="color: white; background-color: #dc3545;">
            <div class="row">
                <div class="col-md-8">
                    <strong>Trip History</strong>
                </div>
                <div class="col-md-4 text-right">
                    <a href="" class="btn btn-danger" style="color: #dc3545;">Trips</a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="tblTripHistory" class="table table-striped table-bordered table-sm table-hover">
                    <thead class="text-center">
                        <tr>
                            <th class="hidden">ID</th>
                            <th style="width: 10%;">Date</th>
                            <th style="width: 10%;">Assigned Driver</th>
                            <th style="width: 10%;">Departure</th>
                            <th style="width: 10%;">Arrival</th>
                            <th>Origin</th>
                            <th>Destination</th>
                        </tr>
                    </thead>
                    <tbody class="text-center">
                        <?php if (isset($trips)): ?>
                            <?php foreach ($trips as $trip): ?>
                              <tr>
                                  <td class="hidden"><?php echo htmlspecialchars($trip['id']); ?></td>
                                  <td><?php echo isset($trip['created_at']) ? htmlspecialchars(date('F j, Y g:i A', strtotime($trip['created_at']))) : ''; ?></td>
                                  <td><?php echo getDriverName($conn, $trip['driver_id']); ?></td>
                                  <td><?php echo isset($trip['departure_time']) ? htmlspecialchars(date('g:i A', strtotime($trip['departure_time']))) : ''; ?></td>
                                  <td><?php echo isset($trip['arrival_time']) ? htmlspecialchars(date('g:i A', strtotime($trip['arrival_time']))) : ''; ?></td>
                                  <td><?php echo htmlspecialchars($trip['origin']); ?></td>
                                  <td><?php echo htmlspecialchars($trip['destination']); ?></td>
                              </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
  </section>
  </div>
</main>

<?php include "partials/footer.php"; ?>

<!-- Bootstrap JS and any other script -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.4/js/dataTables.bootstrap4.min.js"></script>
<script>
  $(document).ready(function () {
      $('#tblDriverManagement').DataTable({
          "order": [[0, "asc"]],
          // Customize the layout to move the search box to the right
          "dom": '<"d-flex justify-content-between"lfr>t<"d-flex justify-content-between"ip>',
      });
      $('#tblVanManagement').DataTable({
          "order": [[0, "asc"]],
          // Customize the layout to move the search box to the right
          "dom": '<"d-flex justify-content-between"lfr>t<"d-flex justify-content-between"ip>',
      });
      $(document).ready(function () {
          $('#tblIncidentReports').DataTable({
              "order": [[0, "desc"]],
              // Customize the layout to move the search box to the right
              "dom": '<"d-flex justify-content-between"lfr>t<"d-flex justify-content-between"ip>',
          });
      });
      $(document).ready(function () {
          $('#tblTripHistory').DataTable({
              "order": [[0, "desc"]],
              // Customize the layout to move the search box to the right
              "dom": '<"d-flex justify-content-between"lfr>t<"d-flex justify-content-between"ip>',
          });
      });
  });
</script>
<!-- <script>
    // Reload the page every n seconds
    let n = 5000;
    setInterval(function() {
        location.reload();
    }, n);
</script> -->
</body>
</html>
