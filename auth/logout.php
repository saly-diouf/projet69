<?php
require_once __DIR__ . '/../config/config.php';
logoutUser();
redirect(APP_URL . '/auth/login.php');
