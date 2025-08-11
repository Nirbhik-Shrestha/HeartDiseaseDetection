<style>
    /* General Sidebar Styling */
    .sidebar {
        width: 250px;
        background-color: #ffffff;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        padding: 20px;
        box-sizing: border-box;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        transition: width 0.3s;
    }

    .sidebar:hover {
        width: 270px;
    }

    /* Profile Section Styling */
    .profile {
        text-align: center;
        margin-bottom: 30px;
    }

    .logo {
        margin-top: 15px;
    }

    .logo img {
        height: auto;
        width: 100%;
        max-width: 180px; /* Ensure it doesn't exceed this width */
        object-fit: contain;
        transition: transform 0.3s;
    }

    .logo img:hover {
        transform: scale(1.05);
    }

    .profile-pic img {
        border-radius: 50%;
        width: 80px;
        height: 80px;
        border: 2px solid #00a99d;
    }

    .profile h2 {
        margin: 10px 0 5px;
        font-size: 18px;
        color: #333333;
    }

    .profile-id {
        color: #777777;
        margin-bottom: 10px;
    }

    .edit-btn {
        background-color: #00a99d;
        color: #ffffff;
        border: none;
        padding: 10px 20px;
        cursor: pointer;
        border-radius: 5px;
        transition: background-color 0.3s, transform 0.3s;
    }

    .edit-btn:hover {
        background-color: #008f8a;
        transform: scale(1.05);
    }

    /* Menu Section Styling */
    .menu ul {
        list-style: none;
        padding: 0;
    }

    .menu ul li {
        margin-bottom: 10px;
    }

    .menu ul li a {
        text-decoration: none;
        color: #333333;
        display: block;
        padding: 10px;
        border-radius: 5px;
        transition: background-color 0.3s, padding-left 0.3s;
    }

    .menu ul li a:hover {
        background-color: #00a99d;
        color: #ffffff;
        padding-left: 20px;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .sidebar {
            width: 100%;
            padding: 10px;
        }

        .menu ul li a {
            padding: 8px;
        }
    }
</style>

<div class="sidebar">
    <div>
        <!-- Profile Section -->
        <div class="profile">
            <div class="logo">
                <img src="../images/logo3.png" alt="Logo">
            </div>
            <div class="profile-pic">
                <img src="../images/user.png" alt="Profile Picture">
            </div>
            <h2><?php echo htmlspecialchars($username); ?></h2>
            <p class="profile-id">Doctor no. <?php echo htmlspecialchars($userid); ?></p>
            <a href='updateProfile.php'><button class="edit-btn" >Edit Profile</button></a>
        </div>

        <!-- Navigation Menu -->
        <nav class="menu">
            <ul>
                <li><a href="updateProfile.php">My Profile</a></li>
                <li><a href="#">My Consultations</a></li>
                <li><a href="patients.php">My Patients</a></li>
                <li><a href="schedule.php">My Schedules</a></li>
                <li><a href="appointment.php">My Appointments</a></li>
            </ul>
        </nav>
    </div>
</div>
