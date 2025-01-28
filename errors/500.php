<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SVMIS</title>

    <!-- Favicon -->
    <link rel="icon" href="assets/images/banner.png" type="image/x-icon">
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet">
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Bootstrap core CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        html, body {
            background-color: #f8f9fa;
            color: #212529;
            font-family: sans-serif;
            font-weight: 400;
            height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .error-container {
            text-align: center;
            padding: 20px;
        }
        .error-code {
            font-size: 200px;
            font-weight: 700;
            margin: 0;
            color: #99A7AF;
        }
        .error-message {
            font-size: 24px;
            margin: 20px 0;
            color: #E36C5D;
        }
        .error-details {
            font-size: 16px;
            color: #6c757d;
        }
        .back-home {
            margin-top: 20px;
        }
        .back-home a {
            text-decoration: none;
            color: #007bff;
            font-size: 16px;
        }
        .back-home a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">
            500
        </div>
        <div class="error-message">
            Internal Server Error
        </div>
        <div class="error-details">
            An unexpected error occurred while processing your request. Please try again later.
            <br>
            If the problem persists, please contact the system administrator.
        </div>
        <div class="back-home">
            <a href="command_center">Back to Home</a>
        </div>
    </div>
</body>
</html>
