<?php
require_once 'functions.php';

// Handle Add Task
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task-name'])) {
	addTask(trim($_POST['task-name']));
	header("Location: index.php");
	exit;
}

// Handle Task Completion Toggle
if (isset($_GET['toggle']) && isset($_GET['id'])) {
	markTaskAsCompleted($_GET['id'], $_GET['toggle'] === '1');
	header("Location: index.php");
	exit;
}

// Handle Task Deletion
if (isset($_GET['delete']) && isset($_GET['id'])) {
	deleteTask($_GET['id']);
	header("Location: index.php");
	exit;
}

// Handle Email Subscription
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
	subscribeEmail(trim($_POST['email']));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Task Scheduler</title>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #7b2cbf;
      --accent: #9d4edd;
      --background: #f9f5ff;
      --sidebar: #f3eaff;
      --card-bg: #ffffff;
      --success: #06d6a0;
      --danger: #ff6b6b;
      --input-bg: #f0eaff;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Nunito', sans-serif;
      background: var(--background);
      display: flex;
      height: 100vh;
      overflow: hidden;
    }

    /* Toast Message */
    .toast {
      position: fixed;
      top: 20px;
      right: 20px;
      background: #6c5ce7;
      color: white;
      padding: 12px 20px;
      border-radius: 12px;
      font-weight: bold;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
      animation: slideIn 0.4s ease, fadeOut 1s ease 2.5s forwards;
      z-index: 999;
    }

    @keyframes slideIn {
      from { transform: translateX(200px); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }

    @keyframes fadeOut {
      to { opacity: 0; transform: translateY(-20px); }
    }

    /* Sidebar */
    .sidebar {
      background: var(--sidebar);
      width: 220px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      padding: 30px 20px;
    }

    .nav-buttons {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .nav-buttons a {
      text-decoration: none;
      color: var(--primary);
      font-weight: bold;
      padding: 10px 14px;
      background: white;
      border-radius: 8px;
      text-align: center;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
      transition: 0.3s;
    }

    .nav-buttons a:hover {
      background: var(--accent);
      color: white;
    }

    .subscribe {
      background: white;
      padding: 16px;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      margin-top: 40px;
      text-align: center;
    }

    .subscribe h3 {
      font-size: 14px;
      color: #555;
      margin-bottom: 8px;
    }

    .subscribe form {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .subscribe input {
      padding: 8px;
      border-radius: 8px;
      border: none;
      background: var(--input-bg);
    }

    .subscribe button {
      background: var(--accent);
      border: none;
      color: white;
      padding: 8px;
      border-radius: 8px;
      font-weight: bold;
      cursor: pointer;
    }

    .subscribe button:hover {
      background: #7a1fd8;
    }

    /* Main content */
    .main {
      flex: 1;
      padding: 40px;
      overflow-y: auto;
    }

    h1 {
      font-size: 2rem;
      color: var(--primary);
      margin-bottom: 20px;
    }

    .add-task-form {
      display: flex;
      gap: 10px;
      margin-bottom: 30px;
    }

    .add-task-form input {
      flex: 1;
      padding: 12px 16px;
      border-radius: 10px;
      border: none;
      background: var(--input-bg);
    }

    .add-task-form button {
      background: var(--success);
      color: white;
      border: none;
      padding: 12px 18px;
      font-size: 18px;
      border-radius: 50%;
      cursor: pointer;
    }

    .add-task-form button:hover {
      background: #05c495;
    }

    .tasks-list {
      list-style: none;
      padding: 0;
    }

    .task-item {
      background: white;
      border-radius: 12px;
      padding: 14px 18px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 14px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    .task-left {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .task-left input[type="checkbox"] {
      transform: scale(1.2);
      accent-color: var(--primary);
    }

    .task-name {
      font-size: 16px;
    }

    .completed-text {
      text-decoration: line-through;
      color: #999;
    }

    .delete-task {
      background: var(--danger);
      color: white;
      border: none;
      padding: 8px 10px;
      border-radius: 10px;
      cursor: pointer;
      font-size: 14px;
    }

    .delete-task:hover {
      background: #e83f3f;
    }
  </style>
</head>

<body>
<?php
  $message = '';
  if (isset($_POST['email'])) $message = "📬 Sent Email Successfully!";
?>
<?php if ($message): ?>
  <div class="toast"><?= $message ?></div>
<?php endif; ?>

<!-- Sidebar -->
<div class="sidebar">
  <div class="nav-buttons">
    <a href="?">Home</a>
    <a href="?filter=pending">🕓 Pending Tasks</a>
    <a href="?filter=completed">✅ Finished Tasks</a>
  </div>

  <!-- Subscribe -->
  <div class="subscribe">
    <h3>🔔 Stay Notified</h3>
    <form method="POST" action="">
      <input type="email" name="email" placeholder="Enter email" required>
      <button id="submit-email">Subscribe</button>
    </form>
  </div>
</div>

<!-- Main Content -->
<div class="main">
  <h1>🌟 Your Tasks</h1>

  <!-- Add Task Form -->
  <form class="add-task-form" method="POST" action="">
    <input type="text" name="task-name" id="task-name" placeholder="Enter new task" required>
    <button type="submit" id="add-task">＋</button>
  </form>

  <!-- Tasks List -->
  <ul class="tasks-list">
    <?php
      $tasks = getAllTasks();
      $filter = $_GET['filter'] ?? 'all';

      foreach ($tasks as $task):
        $is_completed = $task['completed'] ? 'checked' : '';
        $completed_class = $task['completed'] ? 'completed-text' : '';

        if ($filter === 'completed' && !$task['completed']) continue;
        if ($filter === 'pending' && $task['completed']) continue;
    ?>
      <li class="task-item">
        <div class="task-left">
          <form method="GET" action="" style="display:flex; align-items:center;">
            <input type="hidden" name="id" value="<?= $task['id'] ?>">
            <input type="hidden" name="toggle" value="<?= $task['completed'] ? '0' : '1' ?>">
            <input type="checkbox" onchange="this.form.submit()" <?= $is_completed ?>>
          </form>
          <span class="task-name <?= $completed_class ?>">
            <?= htmlspecialchars($task['name']) ?>
          </span>
        </div>
        <form method="GET" action="">
          <input type="hidden" name="id" value="<?= $task['id'] ?>">
          <input type="hidden" name="delete" value="1">
          <button type="submit" class="delete-task">✖</button>
        </form>
      </li>
    <?php endforeach; ?>
  </ul>
</div>

</body>
</html>
