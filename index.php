<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>S & C Pharmacy</title>
    <link rel="stylesheet" href="login.css">
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- You can replace this with your actual favicon -->
    <link rel="icon" href="images/Logo.png" type="image/png">
</head>

<body>
    <div class="login-container">
        <div class="icon-header">
            <img src="images/Logo.png" alt="Logo" class="logo">
        </div>
        <h1>Drugstore Inventory System</h1>
        <form action="login.php" method="POST">
            <?php
            session_start(); // Make sure session is started to access $_SESSION
            if (isset($_SESSION['error'])) {
                echo '<p class="error-message">' . htmlspecialchars($_SESSION['error']) . '</p>';
                unset($_SESSION['error']); // Clear the error message after displaying
            }
            ?>
            <div class="form-group">
                <label for="role">User Type</label>
                <div class="select-wrapper">
                    <select id="role" name="role" required>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-group">
<span class="input-icon"><img src="images/user.png" alt="User Icon" style="width: 20px; height: 20px;"></span>
                    <input type="text" id="username" name="username" required>
                </div>
            </div>

            <div class="form-group">
                <div class="label-container">
                    <label for="password">Password</label>
                </div>
                <div class="input-group password-group">
                    <span class="input-icon"><img src="images/lock.png" alt="Lock Icon" style="width: 20px; height: 20px;"></span>
                    <input type="password" id="password" name="password" required>
<span class="password-toggle-icon" onclick="togglePassword()">
    <img src="images/password_hide_icon.png" id="toggleIcon" alt="Toggle Password" style="width: 20px; height: 20px;">
</span>
                </div>
            </div>

            <button type="submit" class="login-btn">Sign In</button>

        </form>
    </div>

    <div class="credits-footer">
        <img src="images/Dela_Luna.jpg" alt="Lead Avatar" class="avatar">
        <div class="credits-text">
            <p class="team-name">Developed By MIS Department 2025</p>
            <p class="lead-name">Alfonso Martin I. Dela Luna</p>
        </div>
    </div>

<script>
    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('toggleIcon');
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.src = 'images/password_show_icon.png';
        } else {
            passwordInput.type = 'password';
            toggleIcon.src = 'images/password_hide_icon.png';
        }
    }
</script>
</body>

</html>
