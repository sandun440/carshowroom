<?php
// register.php
require_once 'config/db.php';
require_once 'includes/auth.php';
requireLogin();

// Only Admin can access this page
if (!hasRole('admin')) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username   = trim($_POST['username']);
    $full_name  = trim($_POST['full_name']);
    $email      = trim($_POST['email']);
    $phone      = trim($_POST['phone']);
    $password   = $_POST['password'];
    $role_id    = (int)$_POST['role_id'];

    // Check if username or email exists
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        $error = "Username or email already exists.";
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            INSERT INTO users (username, full_name, email, phone, password_hash, role_id, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([$username, $full_name, $email, $phone, $password_hash, $role_id]);

        $success = "User '{$full_name}' registered successfully!";
    }
}

// Fetch roles for dropdown
$roles = $pdo->query("SELECT * FROM roles")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AutoHub - Register Staff</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
</head>
<body class="bg-zinc-950 text-zinc-100 min-h-screen">

  <div class="max-w-2xl mx-auto pt-12 px-6">
    <div class="flex items-center justify-between mb-10">
      <div class="flex items-center gap-4">
        <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-orange-500 rounded-2xl flex items-center justify-center text-3xl">A</div>
        <h1 class="text-4xl font-bold">Add New Staff</h1>
      </div>
      <a href="dashboard.php" class="text-orange-400 hover:text-orange-500 flex items-center gap-2">
        <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
      </a>
    </div>

    <?php if ($success): ?>
      <div class="bg-emerald-500/10 border border-emerald-500 text-emerald-400 px-6 py-4 rounded-2xl mb-6">
        <?= htmlspecialchars($success) ?>
      </div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="bg-red-500/10 border border-red-500 text-red-400 px-6 py-4 rounded-2xl mb-6">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <div class="bg-zinc-900 rounded-3xl p-10 border border-zinc-800">
      <form method="POST" class="space-y-6">
        
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <label class="block text-zinc-400 mb-2">Full Name</label>
        <input type="text" name="full_name" required
               class="w-full bg-zinc-800 border border-zinc-700 rounded-2xl px-5 py-4 focus:outline-none focus:border-orange-500">
      </div>
      <div>
        <label class="block text-zinc-400 mb-2">Username</label>
        <input type="text" name="username" required
               class="w-full bg-zinc-800 border border-zinc-700 rounded-2xl px-5 py-4 focus:outline-none focus:border-orange-500">
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-zinc-400 mb-2">Email</label>
            <input type="email" name="email" required
                   class="w-full bg-zinc-800 border border-zinc-700 rounded-2xl px-5 py-4 focus:outline-none focus:border-orange-500">
          </div>
          <div>
            <label class="block text-zinc-400 mb-2">Phone</label>
            <input type="text" name="phone"
                   class="w-full bg-zinc-800 border border-zinc-700 rounded-2xl px-5 py-4 focus:outline-none focus:border-orange-500">
          </div>
        </div>

        <div>
          <label class="block text-zinc-400 mb-2">Password</label>
          <input type="password" name="password" required minlength="6"
                 class="w-full bg-zinc-800 border border-zinc-700 rounded-2xl px-5 py-4 focus:outline-none focus:border-orange-500"
                 placeholder="Minimum 6 characters">
        </div>

        <div>
          <label class="block text-zinc-400 mb-2">Role</label>
          <select name="role_id" required
                  class="w-full bg-zinc-800 border border-zinc-700 rounded-2xl px-5 py-4 focus:outline-none focus:border-orange-500">
            <?php foreach ($roles as $r): ?>
              <option value="<?= $r['role_id'] ?>">
                <?= ucfirst($r['role_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <button type="submit"
                class="w-full mt-8 bg-gradient-to-r from-orange-500 to-red-500 hover:from-orange-600 hover:to-red-600 py-5 rounded-2xl font-semibold text-lg transition">
          Create Account
        </button>
      </form>
    </div>

    <p class="text-center text-zinc-500 text-sm mt-8">
      Only Administrators can register new users
    </p>
  </div>

</body>
</html>