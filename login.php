<?php
// login.php
session_start();
require_once 'config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("
        SELECT u.*, r.role_name 
        FROM users u 
        JOIN roles r ON u.role_id = r.role_id 
        WHERE u.username = ? AND u.is_active = 1
    ");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user'] = $user;
        
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid username or password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AutoHub - Login</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
  <style>
    body { background: linear-gradient(135deg, #09090b 0%, #18181b 100%); }
  </style>
</head>
<body class="min-h-screen flex items-center justify-center text-zinc-100">
  <div class="w-full max-w-md mx-4">
    <!-- Logo -->
    <div class="flex justify-center mb-10">
      <div class="flex items-center gap-4">
        <div class="w-16 h-16 bg-gradient-to-br from-red-500 via-orange-500 to-yellow-500 rounded-3xl flex items-center justify-center text-white text-5xl shadow-2xl">A</div>
        <div>
          <h1 class="text-5xl font-bold tracking-tighter">AutoHub</h1>
          <p class="text-orange-400 text-sm tracking-widest">PREMIUM SHOWROOM</p>
        </div>
      </div>
    </div>

    <div class="bg-zinc-900 rounded-3xl shadow-2xl border border-zinc-800 p-10">
      <h2 class="text-3xl font-semibold text-center mb-8">Welcome Back</h2>
      
      <?php if ($error): ?>
        <div class="bg-red-500/10 border border-red-500 text-red-400 px-4 py-3 rounded-2xl mb-6 text-sm">
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST" class="space-y-6">
        <div>
          <label class="block text-zinc-400 text-sm mb-2">Username</label>
          <div class="relative">
            <i class="fa-solid fa-user absolute left-4 top-4 text-zinc-500"></i>
            <input type="text" name="username" required
                   class="w-full bg-zinc-800 border border-zinc-700 rounded-2xl py-4 pl-11 focus:outline-none focus:border-orange-500 transition"
                   placeholder="admin or sales">
          </div>
        </div>

        <div>
          <label class="block text-zinc-400 text-sm mb-2">Password</label>
          <div class="relative">
            <i class="fa-solid fa-lock absolute left-4 top-4 text-zinc-500"></i>
            <input type="password" name="password" required
                   class="w-full bg-zinc-800 border border-zinc-700 rounded-2xl py-4 pl-11 focus:outline-none focus:border-orange-500 transition"
                   placeholder="••••••••">
          </div>
        </div>

        <button type="submit"
                class="w-full bg-gradient-to-r from-orange-500 to-red-500 hover:from-orange-600 hover:to-red-600 py-4 rounded-2xl font-semibold text-lg transition transform hover:scale-[1.02]">
          Sign In
        </button>
      </form>

      <div class="mt-8 text-center text-xs text-zinc-500">
        Demo Credentials:<br>
        <span class="text-emerald-400">admin / admin123</span> | 
        <span class="text-sky-400">sales / sales123</span>
      </div>
    </div>

    <p class="text-center text-zinc-500 text-sm mt-8">
      © 2026 AutoHub • Modern Car Showroom System
    </p>
  </div>

  <script>
    tailwind.config = { content: [] };
  </script>
</body>
</html>