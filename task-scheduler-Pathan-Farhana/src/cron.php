<?php
require_once 'functions.php';

// Optional logging (can help with debugging)
file_put_contents(__DIR__ . '/cron.log', "[" . date('Y-m-d H:i:s') . "] Running cron.php\n", FILE_APPEND);

// Call the function to send emails
sendTaskReminders();
