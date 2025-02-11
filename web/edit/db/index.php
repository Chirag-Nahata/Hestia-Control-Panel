<?php

ob_start();
$TAB = 'DB';

// Main include
include($_SERVER['DOCUMENT_ROOT'].'/inc/main.php');

// Check database id
if (empty($_GET['database'])) {
    header("Location: /list/db/");
    exit;
}

// Edit as someone else?
if (($_SESSION['userContext'] === 'admin') && (!empty($_GET['user']))) {
    $user=escapeshellarg($_GET['user']);
    $user_plain=htmlentities($_GET['user']);
}

// List datbase
$v_database = $_GET['database'];
exec(HESTIA_CMD."v-list-database ".$user." ".escapeshellarg($v_database)." 'json'", $output, $return_var);
check_return_code_redirect($return_var, $output, '/list/db/');
$data = json_decode(implode('', $output), true);
unset($output);

// Parse database
$v_username = $user;
$v_dbuser =  preg_replace("/^".$user_plain."_/", "", $data[$v_database]['DBUSER']);
$v_password = "";
$v_host = $data[$v_database]['HOST'];
$v_type = $data[$v_database]['TYPE'];
$v_charset = $data[$v_database]['CHARSET'];
$v_date = $data[$v_database]['DATE'];
$v_time = $data[$v_database]['TIME'];
$v_suspended = $data[$v_database]['SUSPENDED'];
if ($v_suspended == 'yes') {
    $v_status =  'suspended';
} else {
    $v_status =  'active';
}

// Check POST request
if (!empty($_POST['save'])) {
    $v_username = $user;

    // Check token
    verify_csrf($_POST);

    // Change database user
    if (($v_dbuser != $_POST['v_dbuser']) && (empty($_SESSION['error_msg']))) {
        $v_dbuser = escapeshellarg($v_dbuser);
        exec(HESTIA_CMD."v-change-database-user ".$user." ".escapeshellarg($v_database)." ".$v_dbuser, $output, $return_var);
        check_return_code($return_var, $output);
        unset($output);
        $v_dbuser = $_POST['v_dbuser'];
    }

    // Change database password
    if ((!empty($_POST['v_password'])) && (empty($_SESSION['error_msg']))) {
        if (!validate_password($_POST['v_password'])) {
            $_SESSION['error_msg'] = _('Password does not match the minimum requirements');
        } else {
            $v_password = tempnam("/tmp", "vst");
            $fp = fopen($v_password, "w");
            fwrite($fp, $_POST['v_password']."\n");
            fclose($fp);
            exec(HESTIA_CMD."v-change-database-password ".$user." ".escapeshellarg($v_database)." ".$v_password, $output, $return_var);
            check_return_code($return_var, $output);
            unset($output);
            unlink($v_password);
            $v_password = escapeshellarg($_POST['v_password']);
        }
    }

    // Set success message
    if (empty($_SESSION['error_msg'])) {
        $_SESSION['ok_msg'] = _('Changes has been saved.');
    }
}

// Render page
render_page($user, $TAB, 'edit_db');

// Flush session messages
unset($_SESSION['error_msg']);
unset($_SESSION['ok_msg']);
