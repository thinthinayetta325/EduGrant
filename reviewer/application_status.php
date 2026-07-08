<?php
session_start();
// Check if the student just arrived from a successful submission
if (isset($_GET['status']) && $_GET['status'] == 'success') {
    echo "<div style='background-color: #d4edda; color: #155724; padding: 15px; margin: 20px 0; border: 1px solid #c3e6cb; border-radius: 4px;'>";
    echo "<strong>Success!</strong> Your application has been submitted safely. A reviewer will evaluate it shortly.";
    echo "</div>";
}
?>