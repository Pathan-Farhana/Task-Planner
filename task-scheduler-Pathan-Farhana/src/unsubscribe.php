<?php
require_once 'functions.php';

$unsubscribed = false;

if (isset($_GET['email'])) {
	$email = $_GET['email'];
	$unsubscribed = unsubscribeEmail($email);
}
?>

<!DOCTYPE html>
<html>
<head>
	<title>Unsubscribe</title>
</head>
<body>
	<!-- Do not modify the ID of the heading -->
	<h2 id="unsubscription-heading">Unsubscribe from Task Updates</h2>

	<?php if ($unsubscribed): ?>
		<p>You have been successfully unsubscribed. 😢</p>
	<?php else: ?>
		<p>Unsubscription failed. This email may not be subscribed. ❌</p>
	<?php endif; ?>
</body>
</html>
