<?php
// sections/overview.php
require_once __DIR__ . '/../config/db.php';

// Fetch Stats
$totalCars = $pdo->query("SELECT COUNT(*) FROM cars")->fetchColumn();
$availableCars = $pdo->query("SELECT COUNT(*) FROM cars WHERE status = 'available'")->fetchColumn();
$soldCars = $pdo->query("SELECT COUNT(*) FROM cars WHERE status = 'sold'")->fetchColumn();

$totalRevenue = $pdo->query("
    SELECT COALESCE(SUM(sale_price), 0) FROM sales 
    WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
")->fetchColumn();

$monthlySales = $pdo->query("
    SELECT COUNT(*) FROM sales 
    WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
")->fetchColumn();

// Recent Sales
$stmt = $pdo->query("
    SELECT s.sale_id, s.sale_price, s.sale_date, 
           c.make, c.model, c.year, 
           u.full_name as salesperson
    FROM sales s
    JOIN cars c ON s.car_id = c.car_id
    JOIN users u ON s.salesperson_id = u.user_id
    ORDER BY s.sale_date DESC LIMIT 5
");
$recentSales = $stmt->fetchAll();
?>

<div class="space-y-8">
  <!-- Welcome Header -->
  <div>
    <h1 class="text-4xl font-bold tracking-tight">Good morning, <?= htmlspecialchars($_SESSION['user']['full_name'] ?? 'Admin') ?> 👋</h1>
    <p class="text-zinc-400 mt-1">Here's what's happening in your showroom today.</p>
  </div>

  <!-- Stats Grid -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    
    <!-- Card 1 -->
    <div class="bg-zinc-900 rounded-3xl p-6 card-hover border border-zinc-800">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-zinc-400 text-sm">Total Inventory</p>
          <p class="text-5xl font-bold mt-3"><?= number_format($totalCars) ?></p>
        </div>
        <div class="w-14 h-14 bg-blue-500/10 rounded-2xl flex items-center justify-center text-3xl">🚗</div>
      </div>
      <div class="mt-4 text-emerald-400 text-sm flex items-center gap-1">
        <i class="fa-solid fa-arrow-trend-up"></i>
        <span>+12 this month</span>
      </div>
    </div>

    <!-- Card 2 -->
    <div class="bg-zinc-900 rounded-3xl p-6 card-hover border border-zinc-800">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-zinc-400 text-sm">Available Cars</p>
          <p class="text-5xl font-bold mt-3 text-emerald-400"><?= number_format($availableCars) ?></p>
        </div>
        <div class="w-14 h-14 bg-emerald-500/10 rounded-2xl flex items-center justify-center text-3xl">🔑</div>
      </div>
      <div class="mt-4 text-emerald-400 text-sm">Ready for sale</div>
    </div>

    <!-- Card 3 -->
    <div class="bg-zinc-900 rounded-3xl p-6 card-hover border border-zinc-800">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-zinc-400 text-sm">Cars Sold (30d)</p>
          <p class="text-5xl font-bold mt-3"><?= number_format($monthlySales) ?></p>
        </div>
        <div class="w-14 h-14 bg-orange-500/10 rounded-2xl flex items-center justify-center text-3xl">💰</div>
      </div>
      <div class="mt-4 text-orange-400 text-sm flex items-center gap-1">
        <i class="fa-solid fa-arrow-trend-up"></i>
        <span><?= $monthlySales > 0 ? '+' . round(($monthlySales / 10), 1) . '%' : '' ?></span>
      </div>
    </div>

    <!-- Card 4 -->
    <div class="bg-zinc-900 rounded-3xl p-6 card-hover border border-zinc-800">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-zinc-400 text-sm">Revenue (30d)</p>
          <p class="text-5xl font-bold mt-3">LKR <?= number_format($totalRevenue) ?></p>
        </div>
        <div class="w-14 h-14 bg-purple-500/10 rounded-2xl flex items-center justify-center text-3xl">📈</div>
      </div>
      <div class="mt-4 text-purple-400 text-sm">This month</div>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    <!-- Recent Sales -->
    <div class="lg:col-span-2 bg-zinc-900 rounded-3xl p-6 border border-zinc-800">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-semibold">Recent Sales</h2>
        <a href="dashboard.php?section=sales" class="text-orange-400 hover:text-orange-500 text-sm flex items-center gap-1">
          View All <i class="fa-solid fa-arrow-right"></i>
        </a>
      </div>
      
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b border-zinc-800 text-zinc-400 text-sm">
              <th class="text-left py-4">Car</th>
              <th class="text-left py-4">Salesperson</th>
              <th class="text-right py-4">Amount</th>
              <th class="text-right py-4">Date</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-zinc-800">
            <?php foreach ($recentSales as $sale): ?>
            <tr class="hover:bg-zinc-800/50 transition">
              <td class="py-4">
                <div class="font-medium"><?= htmlspecialchars($sale['make'] . ' ' . $sale['model']) ?></div>
                <div class="text-sm text-zinc-500"><?= $sale['year'] ?></div>
              </td>
              <td class="py-4 text-zinc-300"><?= htmlspecialchars($sale['salesperson']) ?></td>
              <td class="py-4 text-right font-semibold">LKR <?= number_format($sale['sale_price']) ?></td>
              <td class="py-4 text-right text-sm text-zinc-400"><?= date('d M, Y', strtotime($sale['sale_date'])) ?></td>
            </tr>
            <?php endforeach; ?>
            
            <?php if (empty($recentSales)): ?>
            <tr>
              <td colspan="4" class="py-12 text-center text-zinc-500">No sales yet. Start selling!</td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-zinc-900 rounded-3xl p-6 border border-zinc-800 flex flex-col">
      <h2 class="text-xl font-semibold mb-6">Quick Actions</h2>
      
      <div class="space-y-3 flex-1">
        <a href="dashboard.php?section=cars" 
           class="flex items-center gap-4 p-5 bg-zinc-800 hover:bg-zinc-700 rounded-2xl transition group">
          <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-orange-500 rounded-2xl flex items-center justify-center text-2xl">➕</div>
          <div>
            <p class="font-medium group-hover:text-orange-400">Add New Car</p>
            <p class="text-sm text-zinc-400">Expand your inventory</p>
          </div>
        </a>

        <a href="#" onclick="alert('Sales module coming soon!')" 
           class="flex items-center gap-4 p-5 bg-zinc-800 hover:bg-zinc-700 rounded-2xl transition group">
          <div class="w-12 h-12 bg-gradient-to-br from-emerald-500 to-teal-500 rounded-2xl flex items-center justify-center text-2xl">🤝</div>
          <div>
            <p class="font-medium group-hover:text-emerald-400">Record New Sale</p>
            <p class="text-sm text-zinc-400">Close a deal</p>
          </div>
        </a>

        <a href="dashboard.php?section=users" 
           class="flex items-center gap-4 p-5 bg-zinc-800 hover:bg-zinc-700 rounded-2xl transition group">
          <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-2xl flex items-center justify-center text-2xl">👥</div>
          <div>
            <p class="font-medium group-hover:text-blue-400">Manage Staff</p>
            <p class="text-sm text-zinc-400">Add sales executives</p>
          </div>
        </a>
      </div>

      <div class="mt-auto pt-6 border-t border-zinc-800 text-center">
        <p class="text-xs text-zinc-500">AutoHub v1.0 • Premium Experience</p>
      </div>
    </div>
  </div>
</div>