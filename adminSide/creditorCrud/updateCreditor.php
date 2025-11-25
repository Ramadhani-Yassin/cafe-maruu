<?php
session_start(); // Ensure session is started
?>
<?php
// Include config file
require_once "../config.php";

// Initialize variables for form validation and creditor data
$id = $name = $due_amount = $telephone = $nida = $passport = $voters_id = $driver_license = $tin_number = "";
$id_err = "";

// Check if ID is provided in the URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];

    // Retrieve creditor details based on ID
    $sql = "SELECT * FROM creditors WHERE ID = ?";
    
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $param_id);
        $param_id = $id;
        
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);

            if (mysqli_num_rows($result) == 1) {
                $row = mysqli_fetch_assoc($result);
                $name = $row['Name'];
                $due_amount = $row['Due_Amount'];
                $telephone = $row['Telephone'];
                $nida = $row['NIDA'] ?? '';
                $passport = $row['Passport'] ?? '';
                $voters_id = $row['VotersID'] ?? '';
                $driver_license = $row['DriverLicense'] ?? '';
                $tin_number = $row['TIN_Number'] ?? '';
            } else {
                echo "Creditor not found.";
                exit();
            }
        } else {
            echo "Error retrieving creditor details.";
            exit();
        }
    }
}

// Process form submission when the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize input
    $name = trim($_POST["name"]);
    $due_amount = floatval($_POST["due_amount"]); // Convert to float
    $telephone = trim($_POST["telephone"]);
    $nida = !empty($_POST["nida"]) ? trim($_POST["nida"]) : NULL;
    $passport = !empty($_POST["passport"]) ? trim($_POST["passport"]) : NULL;
    $voters_id = !empty($_POST["voters_id"]) ? trim($_POST["voters_id"]) : NULL;
    $driver_license = !empty($_POST["driver_license"]) ? trim($_POST["driver_license"]) : NULL;
    $tin_number = !empty($_POST["tin_number"]) ? trim($_POST["tin_number"]) : NULL;

    // Validate that at least one ID is provided
    $has_personal_id = !empty($nida) || !empty($passport) || !empty($voters_id) || !empty($driver_license);
    $has_tin = !empty($tin_number);
    
    if (!$has_personal_id && !$has_tin) {
        echo '<script>alert("Please provide at least one ID (NIDA, Passport, Voters ID, Driver License) OR TIN Number for companies."); window.history.back();</script>';
        exit();
    }

    // Automatically set the current date and time
    $currentDate = date('Y-m-d H:i:s');

    // Update the creditor in the database using prepared statement
    $update_sql = "UPDATE creditors SET Name=?, Due_Amount=?, Date=?, Telephone=?, NIDA=?, Passport=?, VotersID=?, DriverLicense=?, TIN_Number=? WHERE ID=?";
    $stmt = mysqli_prepare($link, $update_sql);
    mysqli_stmt_bind_param($stmt, "sdssssssi", $name, $due_amount, $currentDate, $telephone, $nida, $passport, $voters_id, $driver_license, $tin_number, $id);
    $resultCreditor = mysqli_stmt_execute($stmt);
    
    if ($resultCreditor) {
        // Creditor updated successfully
        mysqli_stmt_close($stmt);
        header("Location: ../panel/creditors-panel.php");
        exit();
    } else {
        echo "Error updating creditor: " . mysqli_error($link);
    }
}
?>

<!-- Create your HTML form for updating the creditor details -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">  
    <title>Update Creditor</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: black;
            color: white;
        }

        .login-container {
            padding: 50px; /* Adjust the padding as needed */
            border-radius: 10px; /* Add rounded corners */
            margin: 100px auto; /* Center the container horizontally */
            max-width: 500px; /* Set a maximum width for the container */
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login_wrapper">
            <div class="wrapper">
                <h2 style="text-align: center;">Update Creditor</h2>
                <h5>Admin Credentials needed to Edit Creditor</h5>
                <form action="" method="post">
                    <!-- Name -->
                    <div class="form-group">
                        <label for="name" class="form-label">Creditor Name:</label>
                        <input type="text" name="name" id="name" class="form-control" placeholder="Enter creditor's name" value="<?php echo htmlspecialchars($name); ?>" required>
                    </div>

                    <!-- Due Amount -->
                    <div class="form-group">
                        <label for="due_amount" class="form-label">Due Amount:</label>
                        <input type="number" min="0.0" step="0.0" name="due_amount" id="due_amount" class="form-control" placeholder="Enter due amount" value="<?php echo htmlspecialchars($due_amount); ?>" required>
                    </div>

                    <!-- Telephone -->
                    <div class="form-group">
                        <label for="telephone" class="form-label">Telephone:</label>
                        <input type="text" name="telephone" id="telephone" class="form-control" placeholder="Enter telephone number" value="<?php echo htmlspecialchars($telephone); ?>" required>
                    </div>

                    <h5 class="mt-4 mb-3" style="color: white;">Creditor Identification <span class="text-danger">*</span></h5>
                    <p style="color: #ccc; font-size: 0.9em;">Fill at least ONE Personal ID (NIDA, Passport, Voters, Driver) OR TIN Number for Companies</p>
                    
                    <h6 style="color: white; margin-top: 15px;">Personal ID Documents (Choose One):</h6>
                    <!-- NIDA -->
                    <div class="form-group">
                        <label for="nida" class="form-label">NIDA Number:</label>
                        <input type="text" name="nida" id="nida" class="form-control" placeholder="Enter NIDA number" value="<?php echo htmlspecialchars($nida); ?>">
                    </div>

                    <!-- Passport -->
                    <div class="form-group">
                        <label for="passport" class="form-label">Passport Number:</label>
                        <input type="text" name="passport" id="passport" class="form-control" placeholder="Enter passport number" value="<?php echo htmlspecialchars($passport); ?>">
                    </div>

                    <!-- Voters ID -->
                    <div class="form-group">
                        <label for="voters_id" class="form-label">Voters ID:</label>
                        <input type="text" name="voters_id" id="voters_id" class="form-control" placeholder="Enter voters ID" value="<?php echo htmlspecialchars($voters_id); ?>">
                    </div>

                    <!-- Driver License -->
                    <div class="form-group">
                        <label for="driver_license" class="form-label">Driver License:</label>
                        <input type="text" name="driver_license" id="driver_license" class="form-control" placeholder="Enter driver license" value="<?php echo htmlspecialchars($driver_license); ?>">
                    </div>

                    <h6 style="color: white; margin-top: 15px;">OR for Companies:</h6>
                    <!-- TIN Number -->
                    <div class="form-group">
                        <label for="tin_number" class="form-label">TIN Number:</label>
                        <input type="text" name="tin_number" id="tin_number" class="form-control" placeholder="Enter TIN number for companies" value="<?php echo htmlspecialchars($tin_number); ?>">
                    </div>

                    <!-- Hidden ID Field -->
                    <input type="hidden" name="id" value="<?php echo $id; ?>">

                    <!-- Buttons -->
                    <div class="form-group">
                        <button class="btn btn-light" type="submit" name="submit" value="submit">Update</button>
                        <a class="btn btn-danger" href="../panel/creditors-panel.php">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>