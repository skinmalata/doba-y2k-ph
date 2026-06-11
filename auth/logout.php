<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
session_destroy();
redirectWith('/index.php', 'You have been logged out.');
