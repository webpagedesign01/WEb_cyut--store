<?php
// auth/logout.php
session_start();
session_unset();
session_destroy();
header('Location: /auth/login.php?msg=logged_out');
exit;
