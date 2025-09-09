<?php

/**
 * Adds a new task to the task list
 * 
 * @param string $task_name The name of the task to add.
 * @return bool True on success, false on failure.
 */
function addTask(string $task_name): bool {
    $file = __DIR__ . '/tasks.txt';

    // Read current tasks
    $tasks = [];
    if (file_exists($file)) {
        $data = file_get_contents($file);
        $tasks = json_decode($data, true) ?? [];
    }

    // Prevent duplicate tasks
    foreach ($tasks as $task) {
        if (strcasecmp($task['name'], $task_name) === 0) {
            return false; // Duplicate task
        }
    }

    // Create new task
    $new_task = [
        'id' => uniqid(),
        'name' => $task_name,
        'completed' => false
    ];
    $tasks[] = $new_task;

    // Save updated tasks
    return file_put_contents($file, json_encode($tasks, JSON_PRETTY_PRINT)) !== false;
}


/**
 * Retrieves all tasks from the tasks.txt file
 * 
 * @return array Array of tasks. -- Format [ id, name, completed ]
 */
function getAllTasks(): array {
    $file = __DIR__ . '/tasks.txt';

    if (!file_exists($file)) {
        return []; // No tasks yet
    }

    $data = file_get_contents($file);
    $tasks = json_decode($data, true);

    if (!is_array($tasks)) {
        return []; // Malformed or empty JSON
    }

    return $tasks;
}


/**
 * Marks a task as completed or uncompleted
 * 
 * @param string  $task_id The ID of the task to mark.
 * @param bool $is_completed True to mark as completed, false to mark as uncompleted.
 * @return bool True on success, false on failure
 */
function markTaskAsCompleted(string $task_id, bool $is_completed): bool {
    $file = __DIR__ . '/tasks.txt';

    if (!file_exists($file)) {
        return false;
    }

    $data = file_get_contents($file);
    $tasks = json_decode($data, true);

    if (!is_array($tasks)) {
        return false;
    }

    $found = false;
    foreach ($tasks as &$task) {
        if ($task['id'] === $task_id) {
            $task['completed'] = $is_completed;
            $found = true;
            break;
        }
    }

    if (!$found) {
        return false; // Task not found
    }

    return file_put_contents($file, json_encode($tasks, JSON_PRETTY_PRINT)) !== false;
}


/**
 * Deletes a task from the task list
 * 
 * @param string $task_id The ID of the task to delete.
 * @return bool True on success, false on failure.
 */
function deleteTask(string $task_id): bool {
    $file = __DIR__ . '/tasks.txt';

    if (!file_exists($file)) {
        return false;
    }

    $data = file_get_contents($file);
    $tasks = json_decode($data, true);

    if (!is_array($tasks)) {
        return false;
    }

    $original_count = count($tasks);

    // Filter out the task to be deleted
    $tasks = array_filter($tasks, function($task) use ($task_id) {
        return $task['id'] !== $task_id;
    });

    // Check if any task was actually removed
    if (count($tasks) === $original_count) {
        return false; // Task not found
    }

    return file_put_contents($file, json_encode(array_values($tasks), JSON_PRETTY_PRINT)) !== false;
}


/**
 * Generates a 6-digit verification code
 * 
 * @return string The generated verification code.
 */
function generateVerificationCode(): string {
    return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}


/**
 * Subscribe an email address to task notifications.
 *
 * Generates a verification code, stores the pending subscription,
 * and sends a verification email to the subscriber.
 *
 * @param string $email The email address to subscribe.
 * @return bool True if verification email sent successfully, false otherwise.
 */
function subscribeEmail(string $email): bool {
    $pending_file = __DIR__ . '/pending_subscriptions.txt';
    $subscribers_file = __DIR__ . '/subscribers.txt';

    // Check if already subscribed
    if (file_exists($subscribers_file)) {
        $subscribers = json_decode(file_get_contents($subscribers_file), true) ?? [];
        if (in_array($email, $subscribers)) {
            return false; // Already subscribed
        }
    }

    // Generate code
    $code = generateVerificationCode();

    // Load or initialize pending list
    $pending = file_exists($pending_file) ? json_decode(file_get_contents($pending_file), true) ?? [] : [];

    // Update
    $pending[$email] = [
        'code' => $code,
        'timestamp' => time()
    ];

    file_put_contents($pending_file, json_encode($pending, JSON_PRETTY_PRINT));

    // Prepare email
    $verification_link = "http://localhost:8000/verify.php?email=" . urlencode($email) . "&code=" . urlencode($code);
    $subject = "Verify subscription to Task Planner";
    $message = '
    <p>Click the link below to verify your subscription to Task Planner:</p>
    <p><a id="verification-link" href="' . $verification_link . '">Verify Subscription</a></p>
    ';
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: no-reply@example.com\r\n";

    return mail($email, $subject, $message, $headers);
}


/**
 * Verifies an email subscription
 * 
 * @param string $email The email address to verify.
 * @param string $code The verification code.
 * @return bool True on success, false on failure.
 */
function verifySubscription(string $email, string $code): bool {
    $pending_file     = __DIR__ . '/pending_subscriptions.txt';
    $subscribers_file = __DIR__ . '/subscribers.txt';

    // 1. Load pending subscriptions
    $pending = [];
    if (file_exists($pending_file)) {
        $pending = json_decode(file_get_contents($pending_file), true) ?? [];
    }

    // 2. Check if email exists and code matches
    if (!isset($pending[$email]) || $pending[$email]['code'] !== $code) {
        return false;
    }

    // 3. Remove from pending
    unset($pending[$email]);
    file_put_contents($pending_file, json_encode($pending, JSON_PRETTY_PRINT));

    // 4. Load subscribers
    $subscribers = [];
    if (file_exists($subscribers_file)) {
        $subscribers = json_decode(file_get_contents($subscribers_file), true) ?? [];
    }

    // 5. Add to subscribers if not already there
    if (!in_array($email, $subscribers)) {
        $subscribers[] = $email;
    }

    // 6. Save updated subscribers
    return file_put_contents($subscribers_file, json_encode($subscribers, JSON_PRETTY_PRINT)) !== false;
}


/**
 * Unsubscribes an email from the subscribers list
 * 
 * @param string $email The email address to unsubscribe.
 * @return bool True on success, false on failure.
 */
function unsubscribeEmail(string $email): bool {
    $subscribers_file = __DIR__ . '/subscribers.txt';

    if (!file_exists($subscribers_file)) {
        return false;
    }

    $subscribers = json_decode(file_get_contents($subscribers_file), true);

    if (!is_array($subscribers)) {
        return false;
    }

    // Check if email exists
    if (!in_array($email, $subscribers)) {
        return false;
    }

    // Remove email
    $subscribers = array_filter($subscribers, function ($e) use ($email) {
        return $e !== $email;
    });

    return file_put_contents($subscribers_file, json_encode(array_values($subscribers), JSON_PRETTY_PRINT)) !== false;
}


/**
 * Sends task reminders to all subscribers
 * Internally calls  sendTaskEmail() for each subscriber
 */
function sendTaskReminders(): void {
    $subscribers_file = __DIR__ . '/subscribers.txt';
    $tasks_file = __DIR__ . '/tasks.txt';

    // 1. Load subscribers
    if (!file_exists($subscribers_file)) return;
    $subscribers = json_decode(file_get_contents($subscribers_file), true);
    if (!is_array($subscribers) || empty($subscribers)) return;

    // 2. Load all tasks
    $tasks = [];
    if (file_exists($tasks_file)) {
        $tasks = json_decode(file_get_contents($tasks_file), true) ?? [];
    }

    // 3. Filter only pending (incomplete) tasks
    $pending_tasks = array_filter($tasks, function($task) {
        return isset($task['completed']) && $task['completed'] === false;
    });

    if (empty($pending_tasks)) return; // No pending tasks to remind

    // 4. Send reminder email to each subscriber
    foreach ($subscribers as $email) {
        sendTaskEmail($email, $pending_tasks);
    }
}


/**
 * Sends a task reminder email to a subscriber with pending tasks.
 *
 * @param string $email The email address of the subscriber.
 * @param array $pending_tasks Array of pending tasks to include in the email.
 * @return bool True if email was sent successfully, false otherwise.
 */
function sendTaskEmail(string $email, array $pending_tasks): bool {
    $subject = 'Task Planner - Pending Tasks Reminder';

    // 1. Build HTML list of tasks
    $task_list_html = '';
    foreach ($pending_tasks as $task) {
        $task_list_html .= '<li>' . htmlspecialchars($task['name']) . '</li>';
    }

    // 2. Build unsubscribe link
    $unsubscribe_link = "http://localhost:8000/src/unsubscribe.php?email=" . urlencode($email);

    // 3. Build HTML email
    $message = <<<HTML
    <h2>Pending Tasks Reminder</h2>
    <p>Here are the current pending tasks:</p>
    <ul>
        {$task_list_html}
    </ul>
    <p><a id="unsubscribe-link" href="{$unsubscribe_link}">Unsubscribe from notifications</a></p>
    HTML;


    // 4. Headers
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: no-reply@example.com\r\n";

    // 5. Send email
    return mail($email, $subject, $message, $headers);
}

