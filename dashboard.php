<?php
// dashboard.php
require_once 'config/db.php';
require_once 'includes/auth.php';
requireLogin();

$user = $_SESSION['user'];
$role = $user['role_name'];
$section = $_GET['section'] ?? ($role === 'admin' ? 'overview' : 'available');
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AutoHub - <?= ucfirst($section) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
  <style>
    body { background-color: #09090b; color: #f4f4f5; }
    .nav-link { transition: all 0.2s; }
    .nav-link.active, .nav-link:hover { background-color: #18181b; }
    .card-hover:hover { transform: translateY(-6px); }
  </style>
</head>
<body class="min-h-screen bg-zinc-950 text-zinc-100">
  <div class="lg:flex lg:min-h-screen">
    <!-- Mobile Topbar -->
    <div class="lg:hidden bg-zinc-900 border-b border-zinc-800 px-4 py-3 flex items-center justify-between">
      <button id="mobile-menu-button" class="text-zinc-100 p-2 rounded-2xl hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-orange-500">
        <i class="fa-solid fa-bars fa-lg"></i>
      </button>
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 bg-gradient-to-br from-red-500 via-orange-500 to-yellow-500 rounded-2xl flex items-center justify-center text-white font-bold text-xl">A</div>
        <div>
          <p class="text-sm font-semibold">AutoHub</p>
        </div>
      </div>
      <a href="logout.php" class="text-red-400 hover:text-red-500 p-2 rounded-2xl bg-zinc-900/80">
        <i class="fa-solid fa-right-from-bracket"></i>
      </a>
    </div>

    <!-- Sidebar -->
    <div id="sidebar-backdrop" class="fixed inset-0 z-40 bg-black/50 hidden lg:hidden"></div>
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-72 transform -translate-x-full transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-auto lg:h-screen bg-zinc-900 border-r border-zinc-800 flex flex-col">
      <div class="p-6 border-b border-zinc-800 flex items-center justify-between lg:block">
        <div class="flex items-center gap-3">
          <div class="w-11 h-11 bg-gradient-to-br from-red-500 via-orange-500 to-yellow-500 rounded-2xl flex items-center justify-center text-white font-bold text-3xl shadow-lg">A</div>
          <div class="hidden lg:block">
            <h1 class="text-3xl font-bold tracking-tighter">AutoHub</h1>
            <p class="text-sm text-zinc-500 -mt-1">Premium Motors</p>
          </div>
        </div>
        <button id="mobile-close-button" class="lg:hidden text-zinc-400 hover:text-white p-2 rounded-2xl focus:outline-none focus:ring-2 focus:ring-orange-500">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
        <?php if ($role === 'admin'): ?>
          <a href="dashboard.php?section=overview" class="nav-link flex items-center gap-3 px-5 py-3.5 rounded-2xl <?= $section==='overview'?'active':'' ?>">
            <i class="fa-solid fa-gauge w-5"></i> Overview
          </a>
          <a href="dashboard.php?section=cars" class="nav-link flex items-center gap-3 px-5 py-3.5 rounded-2xl <?= $section==='cars'?'active':'' ?>">
            <i class="fa-solid fa-car w-5"></i> Manage Cars
          </a>
          <a href="dashboard.php?section=users" class="nav-link flex items-center gap-3 px-5 py-3.5 rounded-2xl <?= $section==='users'?'active':'' ?>">
            <i class="fa-solid fa-users w-5"></i> Staff
          </a>
          <a href="dashboard.php?section=sales" class="nav-link flex items-center gap-3 px-5 py-3.5 rounded-2xl <?= $section==='sales'?'active':'' ?>">
            <i class="fa-solid fa-chart-line w-5"></i> Reports
          </a>
        <?php else: ?>
          <a href="dashboard.php?section=available" class="nav-link flex items-center gap-3 px-5 py-3.5 rounded-2xl <?= $section==='available'?'active':'' ?>">
            <i class="fa-solid fa-car-side w-5"></i> Available Cars
          </a>
          <a href="dashboard.php?section=my-sales" class="nav-link flex items-center gap-3 px-5 py-3.5 rounded-2xl <?= $section==='my-sales'?'active':'' ?>">
            <i class="fa-solid fa-handshake w-5"></i> My Sales
          </a>
        <?php endif; ?>
      </nav>

      <div class="p-4 border-t border-zinc-800">
        <div class="bg-zinc-800 rounded-3xl p-4 flex items-center gap-3">
          <div class="w-12 h-12 bg-zinc-700 rounded-2xl flex items-center justify-center text-2xl">👨‍💼</div>
          <div class="flex-1">
            <p class="font-semibold"><?= htmlspecialchars($user['full_name']) ?></p>
            <p class="text-emerald-400 text-sm"><?= ucfirst($role) ?></p>
          </div>
          <a href="logout.php" class="text-red-400 hover:text-red-500 text-xl">
            <i class="fa-solid fa-right-from-bracket"></i>
          </a>
        </div>
      </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col lg:min-h-screen">
      <header class="bg-zinc-900 border-b border-zinc-800 px-4 py-4 flex items-center justify-between gap-3 lg:px-8 lg:py-4">
        <div>
          <h2 class="text-xl font-semibold capitalize sm:text-2xl"><?= str_replace('-', ' ', $section) ?></h2>
        </div>
        <div class="flex-1 min-w-0">
          <div class="relative max-w-2xl mx-auto">
            <input type="text" id="global-search" 
                   class="w-full bg-zinc-800 border border-zinc-700 rounded-3xl py-2.5 pl-11 pr-4 text-sm focus:outline-none focus:border-orange-500"
                   placeholder="Search inventory...">
            <i class="fa-solid fa-magnifying-glass absolute left-4 top-3 text-zinc-500"></i>
          </div>
        </div>
      </header>

      <main class="flex-1 overflow-auto p-4 sm:p-6 lg:p-8">
        <?php
        $allowed = ['overview','cars','users','sales','available','my-sales'];
        if (in_array($section, $allowed)) {
            include "sections/{$section}.php";
        } else {
            include 'sections/overview.php';
        }
        ?>
      </main>
    </div>
  </div>

  <script>
    const sidebar = document.getElementById('sidebar');
    const sidebarBackdrop = document.getElementById('sidebar-backdrop');
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileCloseButton = document.getElementById('mobile-close-button');

    function openSidebar() {
      sidebar.classList.remove('-translate-x-full');
      sidebarBackdrop.classList.remove('hidden');
    }

    function closeSidebar() {
      sidebar.classList.add('-translate-x-full');
      sidebarBackdrop.classList.add('hidden');
    }

    mobileMenuButton?.addEventListener('click', openSidebar);
    mobileCloseButton?.addEventListener('click', closeSidebar);
    sidebarBackdrop?.addEventListener('click', closeSidebar);
    document.querySelectorAll('#sidebar nav a').forEach(link => {
      link.addEventListener('click', closeSidebar);
    });
  </script>

  <script>
    tailwind.config = { content: [] };
  </script>
</body>
</html>