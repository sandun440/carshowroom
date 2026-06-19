<?php
// sections/my-sales.php
require_once __DIR__ . '/../config/db.php';

$salesperson_id = $_SESSION['user_id'];

// Stats for current salesperson
$stmtStats = $pdo->prepare("
    SELECT 
        COALESCE(SUM(sale_price), 0) as total_revenue,
        COUNT(*) as total_deals
    FROM sales 
    WHERE salesperson_id = ?
");
$stmtStats->execute([$salesperson_id]);
$stats = $stmtStats->fetch();

$revenue = (float)$stats['total_revenue'];
$deals = (int)$stats['total_deals'];
$commissionRate = 0.025; // 2.5% commission
$commission = $revenue * $commissionRate;

// Fetch sales list
$stmtList = $pdo->prepare("
    SELECT s.sale_id, s.sale_price, s.sale_date, s.payment_method, s.notes,
           c.make, c.model, c.year, c.vin, c.price as original_price,
           cust.full_name as customer_name, cust.phone as customer_phone, cust.email as customer_email, cust.address as customer_address
    FROM sales s
    JOIN cars c ON s.car_id = c.car_id
    JOIN customers cust ON s.customer_id = cust.customer_id
    WHERE s.salesperson_id = ?
    ORDER BY s.sale_date DESC, s.created_at DESC
");
$stmtList->execute([$salesperson_id]);
$mySales = $stmtList->fetchAll();
?>

<div class="space-y-8">
  <!-- Header -->
  <div class="flex justify-between items-center">
    <div>
      <h1 class="text-4xl font-bold tracking-tight">My Sales Dashboard</h1>
      <p class="text-zinc-400 mt-1">Track your personal closed deals, sales performance, and commission earnings.</p>
    </div>
    <a href="dashboard.php?section=available" 
       class="bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 px-6 py-3 rounded-2xl font-semibold flex items-center gap-2 transition transform hover:scale-[1.02] shadow-lg text-white">
      <i class="fa-solid fa-car-side"></i> Browse Available Inventory
    </a>
  </div>

  <!-- Stats Grid -->
  <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <!-- Revenue -->
    <div class="bg-zinc-900 border border-zinc-800 rounded-3xl p-6">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-zinc-400 text-sm">Revenue Generated</p>
          <p class="text-3xl font-extrabold mt-2 text-white">LKR <?= number_format($revenue) ?></p>
        <div class="w-12 h-12 bg-emerald-500/10 rounded-2xl flex items-center justify-center text-2xl">💰</div>
      </div>
    </div>

    <!-- Deals Closed -->
    <div class="bg-zinc-900 border border-zinc-800 rounded-3xl p-6">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-zinc-400 text-sm">Deals Closed</p>
          <p class="text-3xl font-extrabold mt-2 text-white"><?= $deals ?></p>
          <p class="text-xs text-zinc-500 mt-1">Cars sold by you</p>
        </div>
        <div class="w-12 h-12 bg-blue-500/10 rounded-2xl flex items-center justify-center text-2xl">🤝</div>
      </div>
    </div>

    <!-- Commission -->
    <div class="bg-zinc-900 border border-zinc-800 rounded-3xl p-6">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-zinc-400 text-sm">Commission Earned (2.5%)</p>
          <p class="text-3xl font-extrabold mt-2 text-emerald-400">LKR <?= number_format($commission) ?></p>
          <p class="text-xs text-zinc-500 mt-1">Payable this cycle</p>
        </div>
        <div class="w-12 h-12 bg-purple-500/10 rounded-2xl flex items-center justify-center text-2xl">⚡</div>
      </div>
    </div>
  </div>

  <!-- Search & Filter -->
  <div class="flex gap-4">
    <div class="flex-1 relative">
      <input type="text" id="my-sales-search" onkeyup="filterMySales()"
             class="w-full bg-zinc-900 border border-zinc-800 rounded-3xl py-3 pl-12 pr-4 focus:outline-none focus:border-emerald-500 text-zinc-100 placeholder-zinc-500"
             placeholder="Search by customer name or vehicle make/model...">
      <i class="fa-solid fa-magnifying-glass absolute left-5 top-3.5 text-zinc-500"></i>
    </div>
  </div>

  <!-- Sales Table -->
  <div class="bg-zinc-900 border border-zinc-800 rounded-3xl overflow-x-auto shadow-2xl">
    <table class="min-w-full text-left">
      <thead class="bg-zinc-950/80 border-b border-zinc-800">
        <tr class="text-zinc-400 text-sm">
          <th class="px-6 py-5">Car Details</th>
          <th class="px-6 py-5">Customer Name</th>
          <th class="px-6 py-5">Customer Phone</th>
          <th class="px-6 py-5 text-right">Selling Price</th>
          <th class="px-6 py-5 text-center">Payment</th>
          <th class="px-6 py-5 text-center">Sale Date</th>
          <th class="px-6 py-5 text-center">Actions</th>
        </tr>
      </thead>
      <tbody id="my-sales-table" class="divide-y divide-zinc-800/60">
        <?php foreach ($mySales as $sale): ?>
        <tr class="hover:bg-zinc-800/40 transition duration-150 my-sale-row"
            data-search="<?= strtolower($sale['make'].' '.$sale['model'].' '.$sale['customer_name']) ?>">
          
          <td class="px-6 py-5">
            <div class="font-semibold text-white"><?= htmlspecialchars($sale['make'] . ' ' . $sale['model']) ?></div>
            <div class="text-xs text-zinc-500 mt-0.5"><?= $sale['year'] ?> • VIN: <?= htmlspecialchars($sale['vin'] ?? 'N/A') ?></div>
          </td>

          <td class="px-6 py-5">
            <span class="font-medium text-zinc-200"><?= htmlspecialchars($sale['customer_name']) ?></span>
          </td>

          <td class="px-6 py-5 text-zinc-300 text-sm">
            <?= htmlspecialchars($sale['customer_phone']) ?>
          </td>

          <td class="px-6 py-5 text-right font-bold text-emerald-400">
            LKR <?= number_format($sale['sale_price']) ?>
          </td>

          <td class="px-6 py-5 text-center">
            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-zinc-850 border border-zinc-800 text-zinc-300">
              <?= $sale['payment_method'] ?>
            </span>
          </td>

          <td class="px-6 py-5 text-center text-sm text-zinc-400">
            <?= date('d M, Y', strtotime($sale['sale_date'])) ?>
          </td>

          <td class="px-6 py-5 text-center">
            <button onclick="viewSaleDetails(<?= htmlspecialchars(json_encode($sale)) ?>)"
                    class="px-4 py-2 bg-zinc-800 hover:bg-zinc-700 text-zinc-300 hover:text-white rounded-xl text-xs font-semibold transition border border-zinc-800">
              View Deal
            </button>
          </td>

        </tr>
        <?php endforeach; ?>

        <?php if (empty($mySales)): ?>
        <tr>
          <td colspan="7" class="py-16 text-center text-zinc-500">
            <div class="text-5xl mb-4">💼</div>
            <p class="text-zinc-400 text-lg font-medium">No sales recorded yet.</p>
            <p class="text-zinc-500 text-sm mt-1 mb-6">Start browsing inventory and log your first sale deal!</p>
            <a href="dashboard.php?section=available" 
               class="px-5 py-2.5 bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white rounded-2xl font-semibold shadow transition">
              Go to Inventory
            </a>
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Details Modal -->
<div id="my-sale-detail-modal" class="hidden fixed inset-0 bg-black/80 flex items-center justify-center z-50 p-4">
  <div class="bg-zinc-900 border border-zinc-800 rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl transition-all">
    <div class="p-6 border-b border-zinc-800 flex justify-between items-center bg-zinc-950">
      <h2 class="text-2xl font-bold text-white flex items-center gap-2">
        <span>📄</span> Personal Sales Record
      </h2>
      <button onclick="closeSaleDetailModal()" class="text-3xl text-zinc-400 hover:text-white transition">&times;</button>
    </div>
    <div id="my-sale-modal-content" class="p-6 space-y-4"></div>
    <div class="p-6 border-t border-zinc-800 bg-zinc-950/50 flex justify-end">
      <button onclick="closeSaleDetailModal()" class="px-6 py-2.5 bg-zinc-800 hover:bg-zinc-700 text-zinc-300 hover:text-white rounded-2xl font-medium transition">
        Close
      </button>
    </div>
  </div>
</div>

<script>
function filterMySales() {
  const query = document.getElementById('my-sales-search').value.toLowerCase();
  document.querySelectorAll('.my-sale-row').forEach(row => {
    const text = row.getAttribute('data-search') || '';
    row.style.display = text.includes(query) ? '' : 'none';
  });
}

function viewSaleDetails(sale) {
  let discountBlock = '';
  const discount = sale.original_price - sale.sale_price;
  if (discount > 0) {
    discountBlock = `
      <div class="flex justify-between text-orange-400 text-xs">
        <span>Sticker Discount Allowed:</span>
            <span>- LKR ${parseInt(discount).toLocaleString()}</span>
    <div class="space-y-4">
      
      <!-- Deal Header -->
      <div class="bg-zinc-950/40 p-4 border border-zinc-800/80 rounded-2xl flex justify-between items-center">
        <div>
          <span class="text-xs uppercase tracking-wider text-zinc-500">Sold Car</span>
          <p class="font-bold text-white text-lg">${sale.make} ${sale.model}</p>
          <p class="text-xs text-zinc-500 font-mono">VIN: ${sale.vin || 'N/A'}</p>
        </div>
        <div class="text-right">
          <span class="text-xs uppercase tracking-wider text-zinc-500">Closing Amount</span>
          <p class="font-extrabold text-emerald-400 text-xl">LKR ${parseInt(sale.sale_price).toLocaleString()}</p>

      <!-- Customer Section -->
      <div class="bg-zinc-850 p-4 border border-zinc-800/40 rounded-2xl space-y-2">
        <p class="text-xs font-bold text-zinc-400 uppercase tracking-wider">Customer Details</p>
        <div class="grid grid-cols-2 gap-2 text-sm text-zinc-300">
          <div>
            <span class="text-zinc-500 text-xs block">Full Name</span>
            <span class="font-semibold text-white">${sale.customer_name}</span>
          </div>
          <div>
            <span class="text-zinc-500 text-xs block">Phone</span>
            <span>${sale.customer_phone}</span>
          </div>
          <div class="col-span-2">
            <span class="text-zinc-500 text-xs block">Email</span>
            <span>${sale.customer_email || 'No email provided'}</span>
          </div>
          <div class="col-span-2">
            <span class="text-zinc-500 text-xs block">Address</span>
            <span class="text-xs leading-relaxed text-zinc-400">${sale.customer_address || 'No address provided'}</span>
          </div>
        </div>
      </div>

      <!-- Transaction details -->
      <div class="bg-zinc-850 p-4 border border-zinc-800/40 rounded-2xl space-y-2">
        <p class="text-xs font-bold text-zinc-400 uppercase tracking-wider">Transaction Settings</p>
        <div class="grid grid-cols-2 gap-2 text-sm text-zinc-300">
          <div>
            <span class="text-zinc-500 text-xs block">Payment Mode</span>
            <span>${sale.payment_method}</span>
          </div>
          <div>
            <span class="text-zinc-500 text-xs block">Date Recorded</span>
            <span>${new Date(sale.sale_date).toLocaleDateString('en-IN', {day: 'numeric', month: 'short', year: 'numeric'})}</span>
          </div>
          <div>
            <span class="text-zinc-500 text-xs block">Original Sticker Price</span>
            <span>LKR ${parseInt(sale.original_price).toLocaleString()}</span>
          </div>
          <div>
            <span class="text-zinc-500 text-xs block">Closing Status</span>
            <span class="text-emerald-400 font-semibold flex items-center gap-1">Closed Deal</span>
          </div>
        </div>
        ${discountBlock}
      </div>

      <!-- Comments / Notes -->
      <div class="bg-zinc-850 p-4 border border-zinc-800/40 rounded-2xl">
        <span class="text-zinc-500 text-xs uppercase tracking-wider block mb-1">Deal Log Notes</span>
        <p class="text-sm italic text-zinc-400 leading-relaxed bg-zinc-900/60 p-3 rounded-xl border border-zinc-850">
          "${sale.notes || 'No custom notes logged for this deal.'}"
        </p>
      </div>

    </div>
  `;
  document.getElementById('my-sale-modal-content').innerHTML = contentHtml;
  document.getElementById('my-sale-detail-modal').classList.remove('hidden');
}

function closeSaleDetailModal() {
  document.getElementById('my-sale-detail-modal').classList.add('hidden');
}

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    closeSaleDetailModal();
  }
});
</script>
