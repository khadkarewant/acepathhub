<?php
require_once "src/db/db_conn.php";
require_once "src/db/session.php";
require_once "src/db/privileges.php";

if (!isset($user_id) || !is_numeric($user_id)) {
    header("Location: login.php");
    exit;
}

function e($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <?php include("src/inc/links.php"); ?>
    <style>
        .profile_img{
            width:80px;
            height:100px;
            border-radius:50%;
        }
    </style>
</head>
<body>
<?php include("src/inc/header.php"); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <img src="src/img/full_logo_wb.png" alt="profile image" class="profile_img"> <br>

            <strong><?= e(($first_name ?? '') . " " . ($middle_name ?? '') . " " . ($last_name ?? '')) ?></strong>
            <br>
            <i>@<?= e($username ?? '') ?></i>
            <br><br>

            <button class="btn" style="background: var(--primary); color:white;"
                    onclick="window.location.href='change-password.php'">
                Change Password
            </button>

            <?php if (empty($pin)): ?>
                <button class="btn" style="background: var(--primary); color:white"
                        onclick="window.location.href='set-pin.php'">
                    Set Pin
                </button>
            <?php else: ?>
                <button class="btn" style="background: var(--primary); color:white"
                        onclick="window.location.href='change-pin.php'">
                    Change Pin
                </button>
            <?php endif; ?>

            <div class="sharing_code">
                <br>
                <p>
                    Share & get free MCQ set. <br>
                    <strong>Your link:</strong>
                    <?php
                        // Build a safe referral link (only if you want to actually show it)
                        $ref = $referral_code ?? '';
                        $share_link = "https://quizmania.org/signup.php?referral_by=" . rawurlencode((string)$ref);
                    ?>
                    <input type="text" value="<?= e($share_link) ?>" class="form-control" readonly>
                </p>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div style="font-size:18px;font-weight:700">Personal Details:</div>
                    <p>
                        First Name: <?= e($first_name ?? '') ?> <br>
                        <?php if (!empty($middle_name)): ?>
                            Middle Name: <?= e($middle_name) ?> <br>
                        <?php endif; ?>
                        Last Name: <?= e($last_name ?? '') ?> <br>
                        DOB: <?= e($dob ?? '') ?> <br>
                        Gender: <?= e($gender ?? '') ?><br>

                        <button class="btn" style="background:var(--primary);color:white;"
                                onclick="window.location.href='update-personal-info.php'">
                            Update
                        </button>
                    </p>
                </div>

                <div class="col-md-4">
                    <div style="font-size:18px;font-weight:700">Contact Details:</div>
                    <p>
                        Mobile No.: <?= e($phone ?? '') ?> <br>
                        Email: <?= e($email ?? '') ?><br>
                        <button class="btn" style="background:var(--primary);color:white;"
                                onclick="window.location.href='update-contact.php'">
                            Update
                        </button>
                    </p>
                </div>

                <div class="col-md-4">
                    <div style="font-size:18px;font-weight:700">Address:</div>
                    <p>
                        Country: <?= e($country ?? '') ?> <br>
                        City: <?= e($city ?? '') ?> <br>
                        Postal Code: <?= e($postal_code ?? '') ?> <br>
                        <button class="btn" style="background:var(--primary);color:white;"
                                onclick="window.location.href='update-address.php'">
                            Update
                        </button>
                    </p>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include("src/inc/footer.php"); ?>
</body>
</html>