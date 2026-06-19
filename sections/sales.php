<?php
// sections/sales.php
require_once __DIR__ . '/../config/db.php';

// Fetch overall reports/stats
$totalRevenue = $pdo->query("SELECT COALESCE(SUM(sale_price), 0) FROM sales")->fetchColumn();
$totalSalesCount = $pdo->query("SELECT COUNT(*) FROM sales")->fetchColumn();
$avgPrice = $totalSalesCount > 0 ? ($totalRevenue / $totalSalesCount) : 0;

// Payment method counts
$cashDeals = $pdo->query("SELECT COUNT(*) FROM sales WHERE payment_method = 'Cash'")->fetchColumn();
$financeDeals = $pdo->query("SELECT COUNT(*) FROM sales WHERE payment_method = 'Finance'")->fetchColumn();
$bankTransferDeals = $pdo->query("SELECT COUNT(*) FROM sales WHERE payment_method = 'Bank Transfer'")->fetchColumn();
$cardDeals = $pdo->query("SELECT COUNT(*) FROM sales WHERE payment_method = 'Card'")->fetchColumn();

// Fetch sales records
$stmt = $pdo->query("
    SELECT s.sale_id, s.sale_price, s.sale_date, s.payment_method, s.notes,
           c.make, c.model, c.year, c.vin, c.price as original_price,
           u.full_name as salesperson_name,
           cust.full_name as customer_name, cust.phone as customer_phone, cust.email as customer_email, cust.address as customer_address
    FROM sales s
    JOIN cars c ON s.car_id = c.car_id
    JOIN users u ON s.salesperson_id = u.user_id
    JOIN customers cust ON s.customer_id = cust.customer_id
    ORDER BY s.sale_date DESC, s.created_at DESC
");
$salesList = $stmt->fetchAll();
?>

<div class="space-y-8">
  
  <!-- Header -->
  <div class="flex justify-between items-center">
    <div>
      <h1 class="text-4xl font-bold tracking-tight">Sales & Reports</h1>
      <p class="text-zinc-400 mt-1">Full transaction overview, revenue analysis, and dealership performance.</p>
    </div>
  </div>

  <!-- KPI Stats Cards -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    <div class="bg-zinc-900 border border-zinc-800 rounded-3xl p-6">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-zinc-400 text-sm">Total Revenue</p>
          <p class="text-3xl font-extrabold mt-2 text-white">LKR <?= number_format($totalRevenue) ?></p>
          <p class="text-xs text-zinc-500 mt-1">Total value in LKR</p>
        </div>
        <div class="w-12 h-12 bg-emerald-500/10 rounded-2xl flex items-center justify-center text-2xl">💰</div>
      </div>
    </div>
    
    <div class="bg-zinc-900 border border-zinc-800 rounded-3xl p-6">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-zinc-400 text-sm">Deals Closed</p>
          <p class="text-3xl font-extrabold mt-2 text-white"><?= $totalSalesCount ?></p>
          <p class="text-xs text-zinc-500 mt-1">Across all staff</p>
        </div>
        <div class="w-12 h-12 bg-blue-500/10 rounded-2xl flex items-center justify-center text-2xl">🤝</div>
      </div>
    </div>

    <div class="bg-zinc-900 border border-zinc-800 rounded-3xl p-6">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-zinc-400 text-sm">Average Deal Value</p>
          <p class="text-3xl font-extrabold mt-2 text-white">LKR <?= number_format($avgPrice) ?></p>
          <p class="text-xs text-zinc-500 mt-1">Average value in LKR</p>
        </div>
        <div class="w-12 h-12 bg-purple-500/10 rounded-2xl flex items-center justify-center text-2xl">📈</div>
      </div>
    </div>

    <div class="bg-zinc-900 border border-zinc-800 rounded-3xl p-6">
      <p class="text-zinc-400 text-sm mb-3">Payment Methods</p>
      <div class="grid grid-cols-2 gap-2 text-xs">
        <div class="bg-zinc-950/60 p-2 rounded-xl border border-zinc-800 flex justify-between">
          <span class="text-zinc-500">Cash:</span>
          <span class="font-bold text-white"><?= $cashDeals ?></span>
        </div>
        <div class="bg-zinc-950/60 p-2 rounded-xl border border-zinc-800 flex justify-between">
          <span class="text-zinc-500">Bank:</span>
          <span class="font-bold text-white"><?= $bankTransferDeals ?></span>
        </div>
        <div class="bg-zinc-950/60 p-2 rounded-xl border border-zinc-800 flex justify-between">
          <span class="text-zinc-500">Finance:</span>
          <span class="font-bold text-white"><?= $financeDeals ?></span>
        </div>
        <div class="bg-zinc-950/60 p-2 rounded-xl border border-zinc-800 flex justify-between">
          <span class="text-zinc-500">Card:</span>
          <span class="font-bold text-white"><?= $cardDeals ?></span>
        </div>
      </div>
    </div>
  </div>

  <!-- Search & Filter -->
  <div class="flex gap-4">
    <div class="flex-1 relative">
      <input type="text" id="sales-search" onkeyup="filterSales()"
             class="w-full bg-zinc-900 border border-zinc-800 rounded-3xl py-3 pl-12 pr-4 focus:outline-none focus:border-orange-500 text-zinc-100 placeholder-zinc-500"
             placeholder="Search by customer, salesperson, or vehicle make/model...">
      <i class="fa-solid fa-magnifying-glass absolute left-5 top-3.5 text-zinc-500"></i>
    </div>
  </div>

  <!-- Sales Records Table -->
  <div class="bg-zinc-900 border border-zinc-800 rounded-3xl overflow-x-auto shadow-2xl">
    <table class="min-w-full text-left">
      <thead class="bg-zinc-950/80 border-b border-zinc-800">
        <tr class="text-zinc-400 text-sm">
          <th class="px-6 py-5">Car Details</th>
          <th class="px-6 py-5">Customer info</th>
          <th class="px-6 py-5">Salesperson</th>
          <th class="px-6 py-5 text-right">Selling Price</th>
          <th class="px-6 py-5 text-center">Payment</th>
          <th class="px-6 py-5 text-center">Sale Date</th>
          <th class="px-6 py-5 text-center">Actions</th>
        </tr>
      </thead>
      <tbody id="sales-table" class="divide-y divide-zinc-800/60">
        <?php foreach ($salesList as $sale): ?>
        <tr class="hover:bg-zinc-800/40 transition duration-150 sale-row"
            data-search="<?= strtolower($sale['make'].' '.$sale['model'].' '.$sale['customer_name'].' '.$sale['salesperson_name']) ?>">
          
          <td class="px-6 py-5">
            <div class="font-semibold text-white"><?= htmlspecialchars($sale['make'] . ' ' . $sale['model']) ?></div>
            <div class="text-xs text-zinc-500 mt-0.5"><?= $sale['year'] ?> • VIN: <?= htmlspecialchars($sale['vin'] ?? 'N/A') ?></div>
          </td>

          <td class="px-6 py-5">
            <div class="font-medium text-zinc-200"><?= htmlspecialchars($sale['customer_name']) ?></div>
            <div class="text-xs text-zinc-500 mt-0.5"><?= htmlspecialchars($sale['customer_phone']) ?></div>
          </td>

          <td class="px-6 py-5">
            <span class="text-sm font-medium text-zinc-300"><?= htmlspecialchars($sale['salesperson_name']) ?></span>
          </td>

          <td class="px-6 py-5 text-right">
            <span class="font-bold text-emerald-400 text-base">LKR <?= number_format($sale['sale_price']) ?></span>
            <?php 
              $discount = $sale['original_price'] - $sale['sale_price'];
              if ($discount > 0): 
            ?>
              <div class="text-[10px] text-orange-400">Discounted LKR <?= number_format($discount) ?></div>
            <?php endif; ?>
          </td>

          <td class="px-6 py-5 text-center">
            <?php
              $pmClass = $sale['payment_method'] === 'Finance' ? 'bg-purple-500/10 border-purple-500/20 text-purple-400' :
                         ($sale['payment_method'] === 'Bank Transfer' ? 'bg-blue-500/10 border-blue-500/20 text-blue-400' :
                         ($sale['payment_method'] === 'Cash' ? 'bg-amber-500/10 border-amber-500/20 text-amber-400' : 'bg-pink-500/10 border-pink-500/20 text-pink-400'));
            ?>
            <span class="px-3 py-1 rounded-full text-xs font-semibold border <?= $pmClass ?>">
              <?= $sale['payment_method'] ?>
            </span>
          </td>

          <td class="px-6 py-5 text-center text-sm text-zinc-400">
            <?= date('d M, Y', strtotime($sale['sale_date'])) ?>
          </td>

          <td class="px-6 py-5 text-center">
            <button onclick="viewSaleDetails(<?= htmlspecialchars(json_encode($sale)) ?>)"
                    class="px-4 py-2 bg-zinc-800 hover:bg-zinc-700 text-zinc-300 hover:text-white rounded-xl text-xs font-semibold transition border border-zinc-800">
              Details
            </button>
          </td>

        </tr>
        <?php endforeach; ?>
        
        <?php if (empty($salesList)): ?>
        <tr>
          <td colspan="7" class="py-16 text-center text-zinc-500">
            <div class="text-5xl mb-4">🤝</div>
            <p>No sales recorded in the system yet.</p>
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Sale Details Modal -->
<div id="sale-detail-modal" class="hidden fixed inset-0 bg-black/80 flex items-center justify-center z-50 p-4">
  <div class="bg-zinc-900 border border-zinc-800 rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl transition-all">
    <div class="p-6 border-b border-zinc-800 flex justify-between items-center bg-zinc-950">
      <h2 class="text-2xl font-bold text-white flex items-center gap-2">
        <span>📄</span> Deal Receipt Summary
      </h2>
      <button onclick="closeSaleDetailModal()" class="text-3xl text-zinc-400 hover:text-white transition">&times;</button>
    </div>
    <div id="sale-modal-content" class="p-6 space-y-4"></div>
    <div class="p-6 border-t border-zinc-800 bg-zinc-950/50 flex justify-end">
      <button onclick="closeSaleDetailModal()" class="px-6 py-2.5 bg-zinc-800 hover:bg-zinc-700 text-zinc-300 hover:text-white rounded-2xl font-medium transition">
        Close
      </button>
    </div>
  </div>
</div>

<script>
function filterSales() {
  const query = document.getElementById('sales-search').value.toLowerCase();
  document.querySelectorAll('.sale-row').forEach(row => {
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
        <span>Discount Allowed:</span>
            <span>- LKR ${parseInt(discount).toLocaleString()}</span>
  let contentHtml = `
    <div class="space-y-4">
      
      <!-- Deal Header -->
      <div class="bg-zinc-950/40 p-4 border border-zinc-800/80 rounded-2xl flex justify-between items-center">
        <div>
          <span class="text-xs uppercase tracking-wider text-zinc-500">Sold Vehicle</span>
          <p class="font-bold text-white text-lg">${sale.make} ${sale.model}</p>
          <p class="text-xs text-zinc-500 font-mono">VIN: ${sale.vin || 'N/A'}</p>
        </div>
        <div class="text-right">
          <span class="text-xs uppercase tracking-wider text-zinc-500">Closed Deal</span>
          <p class="font-extrabold text-emerald-400 text-xl">LKR ${parseInt(sale.sale_price).toLocaleString()}</p>
        </div>
      </div>

      <!-- Customer Section -->
      <div class="bg-zinc-850 p-4 border border-zinc-800/40 rounded-2xl space-y-2">
        <p class="text-xs font-bold text-zinc-400 uppercase tracking-wider">Buyer Information</p>
        <div class="grid grid-cols-2 gap-2 text-sm text-zinc-300">
          <div>
            <span class="text-zinc-500 text-xs block">Full Name</span>
            <span class="font-semibold text-white">${sale.customer_name}</span>
          </div>
          <div>
            <span class="text-zinc-500 text-xs block">Phone Contact</span>
            <span>${sale.customer_phone}</span>
          </div>
          <div class="col-span-2">
            <span class="text-zinc-500 text-xs block">Email Address</span>
            <span>${sale.customer_email || 'No email provided'}</span>
          </div>
          <div class="col-span-2">
            <span class="text-zinc-500 text-xs block">Mailing Address</span>
            <span class="text-xs leading-relaxed text-zinc-400">${sale.customer_address || 'No address provided'}</span>
          </div>
        </div>
      </div>

      <!-- Transaction details -->
      <div class="bg-zinc-850 p-4 border border-zinc-800/40 rounded-2xl space-y-2">
        <p class="text-xs font-bold text-zinc-400 uppercase tracking-wider">Deal parameters</p>
        <div class="grid grid-cols-2 gap-2 text-sm text-zinc-300">
          <div>
            <span class="text-zinc-500 text-xs block">Sales Consultant</span>
            <span class="font-semibold text-white">${sale.salesperson_name}</span>
          </div>
          <div>
            <span class="text-zinc-500 text-xs block">Payment Mode</span>
            <span>${sale.payment_method}</span>
          </div>
          <div>
            <span class="text-zinc-500 text-xs block">Original Sticker Price</span>
            <span>LKR ${parseInt(sale.original_price).toLocaleString()}</span>
          </div>
          <div>
            <span class="text-zinc-500 text-xs block">Closing Date</span>
            <span>${new Date(sale.sale_date).toLocaleDateString('en-IN', {day: 'numeric', month: 'short', year: 'numeric'})}</span>
          </div>
        </div>
        ${discountBlock}
      </div>

      <!-- Comments / Notes -->
      <div class="bg-zinc-850 p-4 border border-zinc-800/40 rounded-2xl">
        <span class="text-zinc-500 text-xs uppercase tracking-wider block mb-1">Deal Notes</span>
        <p class="text-sm italic text-zinc-400 leading-relaxed bg-zinc-900/60 p-3 rounded-xl border border-zinc-850">
          "${sale.notes || 'No notes were recorded for this transaction.'}"
        </p>
      </div>

    </div>
  `;
  document.getElementById('sale-modal-content').innerHTML = contentHtml;
  document.getElementById('sale-detail-modal').classList.remove('hidden');
}

function closeSaleDetailModal() {
  document.getElementById('sale-detail-modal').classList.add('hidden');
}

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    closeSaleDetailModal();
  }
});
</script>
