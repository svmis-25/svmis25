<!doctype html>
<html lang="en" class="h-100">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>SVMIS</title>

    <!-- Favicon tab icon-->
    <link rel="icon" href="assets/images/banner.png" type="image/x-icon">

    <!-- Bootstrap core CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
      main > .container {
        padding: 10px 15px 0;
        margin-left: 10px;
      }

      .errors {
        color: red;
        font-size: 0.875em; /* 14px/16=0.875em */
      }

      .footer {
        background-color: #f5f5f5;
      }

      .footer > .container {
        padding-right: 15px;
        padding-left: 15px;
      }

      .hidden {
        display: none;
      }

      code {
        font-size: 80%;
      }
      .bd-placeholder-img {
        font-size: 1.125rem;
        text-anchor: middle;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
      }

      @media (min-width: 768px) {
        .bd-placeholder-img-lg {
          font-size: 3.5rem;
        }
      }
      .more-info {
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
      }
    </style>

    
    <!-- Custom styles for this template -->
    <!-- <link href="sticky-footer-navbar.css" rel="stylesheet"> -->
  </head>
  <body class="d-flex flex-column h-100">
    
<header>
   <?php include "partials/nav.php"; ?>

   <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item active" aria-current="page"><h3><?php echo $active; ?></h3></li>
    </ol>
  </nav>
</header>