<?php
require_once 'functions.php';

$verified = false;

if (isset($_GET['email']) && isset($_GET['code'])) {
	$email = $_GET['email'];
	$code = $_GET['code'];

	$verified = verifySubscription($email, $code);
}
?>

<!DOCTYPE html>
<html>
<head>
	<title>Subscription Verification</title>
</head>
<body>
	<!-- Do not modify the ID of the heading -->
	<h2 id="verification-heading">Subscription Verification</h2>

	<?php if ($verified): ?>
		<p>Your email has been successfully verified. 🎉</p>
		<p>You will now receive hourly task reminders.</p>
	<?php else: ?>
		<p>Invalid or expired verification link. 😢</p>
		<p>Please subscribe again or check your email for the correct link.</p>
	<?php endif; ?>
</body>
</html>
