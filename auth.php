<?php
/**
 * Who is logged in, and which portal they belong to.
 *
 * Patients, doctors and admins live in separate tables, so an email alone
 * does not say which portal a session belongs to. Login records the role next
 * to the email, and every portal page calls requireRole() before anything
 * else: a patient's session is then refused by /admin and /doctor pages, and
 * the current user is loaded with a prepared statement instead of pasting the
 * session email into SQL.
 */

// Per role: the table holding its accounts, that table's email column, and
// the login page (relative to the portal's own folder).
const ROLE_ACCOUNTS = [
    'patient' => ['table' => 'patients', 'email' => 'pemail', 'login' => 'usersLogin.php'],
    'doctor'  => ['table' => 'doctors',  'email' => 'demail', 'login' => 'doctorLogin.php'],
    'admin'   => ['table' => 'admin',    'email' => 'aemail', 'login' => 'adminLogin.php'],
];

function appSessionStart()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Find an account of the given role by email.
 *
 * @return array|null The account row, or null when there is none.
 */
function findAccount($con, $role, $email)
{
    $account = ROLE_ACCOUNTS[$role];
    $stmt = $con->prepare("SELECT * FROM `{$account['table']}` WHERE `{$account['email']}` = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

/**
 * Check an email/password pair for the given role.
 *
 * @return array|null The account row when the credentials are right.
 */
function verifyLogin($con, $role, $email, $password)
{
    $row = findAccount($con, $role, (string)$email);
    $hashColumn = ['patient' => 'ppassword', 'doctor' => 'dpassword', 'admin' => 'apassword'][$role];

    if ($row && password_verify((string)$password, $row[$hashColumn])) {
        return $row;
    }
    return null;
}

function loginAs($role, $email)
{
    appSessionStart();
    // A fresh id on login, so a session id planted before login is useless.
    session_regenerate_id(true);
    $_SESSION['user'] = $email;
    $_SESSION['role'] = $role;
}

/**
 * Let the page continue only for a logged-in user of $role.
 *
 * Anyone else is redirected to that portal's login page and the script stops
 * there, so nothing below the call ever runs for them.
 *
 * @return array The logged-in user's row from the role's table.
 */
function requireRole($con, $role)
{
    appSessionStart();
    $login = ROLE_ACCOUNTS[$role]['login'];

    $email = isset($_SESSION['user']) ? (string)$_SESSION['user'] : '';
    $sessionRole = isset($_SESSION['role']) ? $_SESSION['role'] : '';

    if ($email === '' || $sessionRole !== $role) {
        header("Location: $login");
        exit();
    }

    $user = findAccount($con, $role, $email);
    if (!$user) {
        // The account was deleted while this session was still open.
        session_unset();
        header("Location: $login");
        exit();
    }

    return $user;
}
