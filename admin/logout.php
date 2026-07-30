<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
session_destroy();
redirect(BASE_URL . '/admin/login.php');
