<?php global $active; ?>
<!-- Fixed navbar -->
<nav class="navbar fixed-top navbar-expand-lg navbar-dark bg-dark">
<!-- <nav class="navbar navbar-expand-lg navbar-light" style="background-color: #060270;"> -->
  <a class="navbar-brand" href="#">
    <img src="assets/images/logo_van2.png" width="100" height="30" class="d-inline-block align-top" alt="" loading="lazy">
  </a>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>

  <div class="collapse navbar-collapse" id="navbarSupportedContent">
    <ul class="navbar-nav mr-auto">
      <li class="nav-item <?php echo $active === "command_center" ? "active" : ""; ?>">
        <a class="nav-link" href="command_center">Command Center</a>
      </li>
      <!-- <li class="nav-item <?php echo $active === "home" ? "active" : ""; ?>">
        <a class="nav-link" href="home">Home</a>
      </li> -->
      <li class="nav-item dropdown <?php echo $active === "modules" ? "active" : ""; ?>">
        <a class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-expanded="false">
          Management
        </a>
        <div class="dropdown-menu">
          <a class="dropdown-item" href="companyManagement">Company Management</a>
          <a class="dropdown-item" href="topupCodeManagement">Topup Management</a>
          <a class="dropdown-item" href="passengerManagement">Passenger Management</a>
          <a class="dropdown-item" href="luggageManagement">Luggage Management</a>
          <a class="dropdown-item" href="driverManagement">Driver Management</a>
          <a class="dropdown-item" href="vanManagement">Van Management</a>
          <div class="dropdown-divider"></div>
          <a class="dropdown-item" href="userManagement">User Management</a>
          <a class="dropdown-item" href="activityLogs">Activity Logs</a>
        </div>
      </li>
      <!-- <li class="nav-item <?php echo $active === "about" ? "active" : ""; ?>">
        <a class="nav-link" href="about">About</a>
      </li>
      <li class="nav-item <?php echo $active === "contact" ? "active" : ""; ?>">
        <a class="nav-link" href="contact">Contact Us</a>
      </li> -->
    </ul>
    <div class="nav-item dropdown">
      <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="color: white;">
        Welcome, <?php echo htmlspecialchars($_SESSION["userroleDesc"]) . " " . htmlspecialchars($_SESSION["userdetails"]); ?>!
      </a>
      <div class="dropdown-menu" aria-labelledby="userDropdown">
        <a class="dropdown-item" href="changePassword">Change Password</a>
        <a class="dropdown-item" href="profile">Profile</a>
        <div class="dropdown-divider"></div>
        <a class="dropdown-item" href="http://localhost/svmis/logout">Logout</a>
      </div>
    </div>

    <a href="logout" class="btn btn-outline-secondary my-2 my-sm-0">
        <i class="fas fa-sign-out-alt"></i>
    </a>
  </div>
</nav>