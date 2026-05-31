<?php
require_once "src/db/db_conn.php";
require_once "src/db/session.php";
require_role(ROLE_ADMIN, ROLE_STUDENT, ROLE_DATA_ENTRY);

$u = $user; // session.php sets $user array
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <?php include "inc/links.php"; ?>
</head>
<body>
<?php include "inc/header.php"; ?>

<div class="container py-4">

    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success mb-3">Profile updated successfully.</div>
    <?php elseif (isset($_GET['err'])): ?>
        <div class="alert alert-danger mb-3">
            <?= $_GET['err'] === 'server' ? 'Server error. Try again.' : 'Update failed. Try again.' ?>
        </div>
    <?php endif; ?>


    <!-- Avatar + Name -->
    <div class="profile-hero">
        <div class="profile-avatar">
            <?= strtoupper(substr($u['first_name'], 0, 1) . substr($u['last_name'], 0, 1)) ?>
        </div>
        <div class="profile-hero-info">
            <div class="profile-fullname">
                <?= htmlspecialchars(trim($u['first_name'] . ' ' . ($u['middle_name'] ? $u['middle_name'] . ' ' : '') . $u['last_name']), ENT_QUOTES, 'UTF-8') ?>
            </div>
            <div class="profile-username">@<?= htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8') ?></div>
            <div class="profile-role-badge">
                <?= $role === ROLE_ADMIN ? 'Admin' : ($role === ROLE_DATA_ENTRY ? 'Data Entry' : 'Student') ?>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="profile-actions">
        <a href="profile-edit.php" class="btn-qs-gold">Edit Profile</a>
        <a href="change-password.php" class="btn-qs-sm">Change Password</a>
        <?php if (empty($u['pin'])): ?>
            <a href="set-pin.php" class="btn-qs-sm">Set PIN</a>
        <?php else: ?>
            <a href="change-pin.php" class="btn-qs-sm">Change PIN</a>
        <?php endif; ?>
    </div>

    <!-- Info Cards -->
    <div class="profile-cards">

        <!-- Personal -->
        <div class="profile-card">
            <div class="profile-card-title">Personal Details</div>
            <div class="profile-card-row">
                <span class="profile-label">First Name</span>
                <span><?= htmlspecialchars($u['first_name'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <?php if (!empty($u['middle_name'])): ?>
            <div class="profile-card-row">
                <span class="profile-label">Middle Name</span>
                <span><?= htmlspecialchars($u['middle_name'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <?php endif; ?>
            <div class="profile-card-row">
                <span class="profile-label">Last Name</span>
                <span><?= htmlspecialchars($u['last_name'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="profile-card-row">
                <span class="profile-label">Date of Birth</span>
                <span><?= $u['dob'] ? htmlspecialchars($u['dob'], ENT_QUOTES, 'UTF-8') : '—' ?></span>
            </div>
            <div class="profile-card-row">
                <span class="profile-label">Gender</span>
                <span><?= $u['gender'] ? htmlspecialchars($u['gender'], ENT_QUOTES, 'UTF-8') : '—' ?></span>
            </div>
            <div class="profile-card-footer">
                <a href="profile-edit.php" class="btn-qs-sm">Update</a>
            </div>
        </div>

        <!-- Contact -->
        <div class="profile-card">
            <div class="profile-card-title">Contact Details</div>
            <div class="profile-card-row">
                <span class="profile-label">Mobile</span>
                <span><?= htmlspecialchars($u['phone'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="profile-card-row">
                <span class="profile-label">Email</span>
                <span><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="profile-card-footer">
                <a href="profile-edit.php" class="btn-qs-sm">Update</a>
            </div>
        </div>

        <!-- Address -->
        <div class="profile-card">
            <div class="profile-card-title">Address</div>
            <div class="profile-card-row">
                <span class="profile-label">Country</span>
                <span><?= $u['country'] ? htmlspecialchars($u['country'], ENT_QUOTES, 'UTF-8') : '—' ?></span>
            </div>
            <div class="profile-card-row">
                <span class="profile-label">City</span>
                <span><?= $u['city'] ? htmlspecialchars($u['city'], ENT_QUOTES, 'UTF-8') : '—' ?></span>
            </div>
            <div class="profile-card-row">
                <span class="profile-label">Postal Code</span>
                <span><?= $u['postal_code'] ? htmlspecialchars($u['postal_code'], ENT_QUOTES, 'UTF-8') : '—' ?></span>
            </div>
            <div class="profile-card-footer">
                <a href="profile-edit.php" class="btn-qs-sm">Update</a>
            </div>
        </div>

    </div>

    <!-- Referral -->
    <div class="profile-referral">
        <div class="profile-card-title">Referral Link</div>
        <p class="profile-referral-hint">Share your link and earn free MCQ sets.</p>
        <?php
            $share_link = 'https://acepathhub.com/signup.php?referral_by=' . rawurlencode((string)($u['referral_code'] ?? ''));
        ?>
        <div class="profile-referral-row">
            <input type="text" id="referral-input" value="<?= htmlspecialchars($share_link, ENT_QUOTES, 'UTF-8') ?>"
                   class="form-control profile-referral-input" readonly>
            <button class="btn-qs-sm" onclick="copyReferral()">Copy</button>
        </div>
    </div>

</div>

<script>
function copyReferral() {
    const input = document.getElementById('referral-input');
    input.select();
    input.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(input.value).catch(() => {
        document.execCommand('copy');
    });
}
</script>

<?php include "inc/footer.php"; ?>
</body>
</html>