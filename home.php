<?php
session_start();
require_once __DIR__ . '/config.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: index");
    exit;
}

$active = "home";
include "partials/header.php";

$current_user_id = $_SESSION["userID"];
$current_user_role_id = $_SESSION["userrole"];

/*===================================
  Query: Fetch Roles from Database
=====================================*/

$roles = [];
$sql = "SELECT id, role_name FROM ref_roles";
$result = $conn->query($sql);

if ($result->num_rows > 0){
  while($row = $result->fetch_assoc()){
    $roles[] = $row;
  }
}
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item active" aria-current="page"><h3>Dashboard</h3></li>
  </ol>
</nav>

<!-- Begin page content -->
<main role="main" class="flex-shrink-0">
  <div class="container-fluid"> <!-- Changed container to container-fluid -->
    <div class="row">
      <!-- Card 1: Transactions -->
      <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 mb-4"> <!-- Adjusted column sizes for responsiveness -->
        <div class="card bg-info text-white shadow">
          <div class="card-body d-flex justify-content-between align-items-center">
            <div>
              <h3>100</h3>
              <p>Transactions</p>
            </div>
            <i class="fas fa-shopping-bag fa-3x"></i>
          </div>
          <div class="card-footer d-flex justify-content-between align-items-center">
            <a href="#" class="text-white">More info</a>
            <i class="fas fa-arrow-circle-right"></i>
          </div>
        </div>
      </div>

      <!-- Card 2: Driver Registrations -->
      <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 mb-4"> <!-- Adjusted column sizes for responsiveness -->
        <div class="card bg-success text-white shadow">
          <div class="card-body d-flex justify-content-between align-items-center">
            <div>
              <h3>49</h3>
              <p>Driver Registrations</p>
            </div>
            <i class="fas fa-chart-bar fa-3x"></i>
          </div>
          <div class="card-footer d-flex justify-content-between align-items-center">
            <a href="#" class="text-white">More info</a>
            <i class="fas fa-arrow-circle-right"></i>
          </div>
        </div>
      </div>

      <!-- Card 3: User Registrations -->
      <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 mb-4"> <!-- Adjusted column sizes for responsiveness -->
        <div class="card bg-warning text-white shadow">
          <div class="card-body d-flex justify-content-between align-items-center">
            <div>
              <h3>49</h3>
              <p>User Registrations</p>
            </div>
            <i class="fas fa-user-plus fa-3x"></i>
          </div>
          <div class="card-footer d-flex justify-content-between align-items-center">
            <a href="#" class="text-white">More info</a>
            <i class="fas fa-arrow-circle-right"></i>
          </div>
        </div>
      </div>

      <!-- Card 4: Unique Visitors -->
      <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 mb-4"> <!-- Adjusted column sizes for responsiveness -->
        <div class="card bg-danger text-white shadow">
          <div class="card-body d-flex justify-content-between align-items-center">
            <div>
              <h3>65</h3>
              <p>Unique Visitors</p>
            </div>
            <i class="fas fa-chart-pie fa-3x"></i>
          </div>
          <div class="card-footer d-flex justify-content-between align-items-center">
            <a href="#" class="text-white">More info</a>
            <i class="fas fa-arrow-circle-right"></i>
          </div>
        </div>
      </div>
    </div>
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
      $('#tblUserManagement').DataTable({
          "order": [[0, "asc"]],
          // Customize the layout to move the search box to the right
          "dom": '<"d-flex justify-content-between"lfr>t<"d-flex justify-content-between"ip>',
      });
  });
</script>
</body>
</html>
