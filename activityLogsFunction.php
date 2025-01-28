<?php
require_once __DIR__ . '/config.php';

function saveActivityLog($current_user_role_id, $activity_id, $current_user_id) {
    global $conn; // Access the global database connection

    // Prepare the INSERT statement
    $sql = "INSERT INTO ref_activitylogs (roleID, activityID, createdBy) VALUES (?, ?, ?)";

    // Create a prepared statement
    if ($stmt = $conn->prepare($sql)) {
        // Bind the parameters to the prepared statement
        $stmt->bind_param("iii", $current_user_role_id, $activity_id, $current_user_id);

        // Execute the prepared statement
        if ($stmt->execute()) {
            $stmt->close(); // Close the statement
            return true; // Activity log saved successfully
        } else {
            $stmt->close(); // Close the statement
            return false; // Error occurred while saving the activity log
        }
    } else {
        // Error occurred while preparing the statement
        return false;
    }
}
?>
