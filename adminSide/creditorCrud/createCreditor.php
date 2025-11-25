<?php
session_start(); // Ensure session is started
?>
<?php include '../inc/dashHeader.php'; ?>
<?php
// Include config file
require_once "../config.php";

// Define variables and initialize with empty values
$name = $due_amount = $telephone = $nida = $passport = $voters_id = $driver_license = $tin_number = "";
$name_err = $due_amount_err = $telephone_err = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate Name
    if (empty(trim($_POST["name"]))) {
        $name_err = "Please enter the creditor's name.";
    } else {
        $name = trim($_POST["name"]);
    }

    // Validate Due Amount
    if (empty(trim($_POST["due_amount"]))) {
        $due_amount_err = "Please enter the due amount.";
    } elseif (!is_numeric(trim($_POST["due_amount"]))) {
        $due_amount_err = "Due amount must be a numeric value.";
    } else {
        $due_amount = trim($_POST["due_amount"]);
    }

    // Validate Telephone
    if (empty(trim($_POST["telephone"]))) {
        $telephone_err = "Please enter the telephone number.";
    } else {
        $telephone = trim($_POST["telephone"]);
    }

    // Get ID fields
    $nida = !empty($_POST["nida"]) ? trim($_POST["nida"]) : NULL;
    $passport = !empty($_POST["passport"]) ? trim($_POST["passport"]) : NULL;
    $voters_id = !empty($_POST["voters_id"]) ? trim($_POST["voters_id"]) : NULL;
    $driver_license = !empty($_POST["driver_license"]) ? trim($_POST["driver_license"]) : NULL;
    $tin_number = !empty($_POST["tin_number"]) ? trim($_POST["tin_number"]) : NULL;

    // Validate that at least one ID is provided
    $has_personal_id = !empty($nida) || !empty($passport) || !empty($voters_id) || !empty($driver_license);
    $has_tin = !empty($tin_number);
    
    if (!$has_personal_id && !$has_tin) {
        $name_err = "Please provide at least one ID (NIDA, Passport, Voters ID, Driver License) OR TIN Number for companies.";
    }

    // Check input errors before inserting into database
    if (empty($name_err) && empty($due_amount_err) && empty($telephone_err)) {
        // Get the current date and time
        $currentDate = date('Y-m-d H:i:s'); // Automatically set the current date and time

        // Prepare an insert statement
        $sql = "INSERT INTO creditors (Name, Due_Amount, Date, Telephone, NIDA, Passport, VotersID, DriverLicense, TIN_Number) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        if ($stmt = mysqli_prepare($link, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "sdsssssss", $param_name, $param_due_amount, $param_date, $param_telephone, 
                                   $param_nida, $param_passport, $param_voters, $param_driver, $param_tin);

            // Set parameters
            $param_name = $name;
            $param_due_amount = $due_amount;
            $param_date = $currentDate; // Automatically set the current date and time
            $param_telephone = $telephone;
            $param_nida = $nida;
            $param_passport = $passport;
            $param_voters = $voters_id;
            $param_driver = $driver_license;
            $param_tin = $tin_number;

            // Attempt to execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {
                // Redirect to success page
                header("location: success_create.php");
                exit();
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }

    // Close connection
    mysqli_close($link);
}
?>
<head>
    <meta charset="UTF-8">
    <title>Create New Creditor</title>
    <style>
        .wrapper { width: 1300px; padding-left: 200px; padding-top: 80px; }
    </style>
</head>

<div class="wrapper">
    <h3>Create New Creditor</h3>
    <p>Please fill in the creditor's information properly.</p>

    <form method="POST" action="success_createCreditor.php" class="ht-600 w-50">
        <!-- Name -->
        <div class="form-group">
            <label for="name">Creditor Name:</label>
            <input type="text" name="name" id="name" class="form-control <?php echo (!empty($name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $name; ?>" placeholder="Enter creditor's name">
            <span class="invalid-feedback"><?php echo $name_err; ?></span>
        </div>
        <br>
        <!-- Due Amount -->
        <div class="form-group">
            <label for="due_amount">Due Amount:</label>
            <input type="number" name="due_amount" id="due_amount" class="form-control <?php echo (!empty($due_amount_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $due_amount; ?>" placeholder="Enter due amount" step="0.01">
            <span class="invalid-feedback"><?php echo $due_amount_err; ?></span>
        </div>
        <br>
        <!-- Telephone -->
        <div class="form-group">
            <label for="telephone">Telephone:</label>
            <input type="text" name="telephone" id="telephone" class="form-control <?php echo (!empty($telephone_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $telephone; ?>" placeholder="Enter telephone number">
            <span class="invalid-feedback"><?php echo $telephone_err; ?></span>
        </div>
        <br>
        
        <h5 class="mt-4 mb-3">Creditor Identification <span class="text-danger">*</span></h5>
        <p class="text-muted small">Fill at least ONE Personal ID (NIDA, Passport, Voters, Driver) OR TIN Number for Companies</p>
        
        <h6 class="mt-3">Personal ID Documents (Choose One):</h6>
        <!-- NIDA -->
        <div class="form-group">
            <label for="nida">NIDA Number:</label>
            <input type="text" name="nida" id="nida" class="form-control" value="<?php echo $nida; ?>" placeholder="Enter NIDA number">
        </div>
        <br>
        <!-- Passport -->
        <div class="form-group">
            <label for="passport">Passport Number:</label>
            <input type="text" name="passport" id="passport" class="form-control" value="<?php echo $passport; ?>" placeholder="Enter passport number">
        </div>
        <br>
        <!-- Voters ID -->
        <div class="form-group">
            <label for="voters_id">Voters ID:</label>
            <input type="text" name="voters_id" id="voters_id" class="form-control" value="<?php echo $voters_id; ?>" placeholder="Enter voters ID">
        </div>
        <br>
        <!-- Driver License -->
        <div class="form-group">
            <label for="driver_license">Driver License:</label>
            <input type="text" name="driver_license" id="driver_license" class="form-control" value="<?php echo $driver_license; ?>" placeholder="Enter driver license">
        </div>
        <br>
        
        <h6 class="mt-3">OR for Companies:</h6>
        <!-- TIN Number -->
        <div class="form-group">
            <label for="tin_number">TIN Number:</label>
            <input type="text" name="tin_number" id="tin_number" class="form-control" value="<?php echo $tin_number; ?>" placeholder="Enter TIN number for companies">
        </div>
        <br>
        <!-- Submit Button -->
        <div class="form-group">
            <input type="submit" class="btn btn-dark" value="Create Creditor">
        </div>
    </form>
</div>

<?php include '../inc/dashFooter.php'; ?>