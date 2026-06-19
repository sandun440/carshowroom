<?php
// sections/available.php
require_once __DIR__ . '/../config/db.php';

$successMessage = '';
$errorMessage = '';

// Handle recording a sale
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'record_sale') {
    $car_id = (int)$_POST['car_id'];
    $customer_name = trim($_POST['customer_name']);
    $customer_email = trim($_POST['customer_email']);
    $customer_phone = trim($_POST['customer_phone']);
    $customer_address = trim($_POST['customer_address']);
    $sale_price = (float)$_POST['sale_price'];
    $payment_method = $_POST['payment_method'];
    $notes = trim($_POST['notes']);
    $salesperson_id = $_SESSION['user_id'];

    if (empty($customer_name) || empty($customer_phone) || empty($sale_price) || empty($payment_method)) {
        $errorMessage = "Please fill in all required fields.";
    } else {
        try {
            $pdo->beginTransaction();

            // Check if customer exists by phone or email
            $custStmt = $pdo->prepare("SELECT customer_id FROM customers WHERE phone = ? OR (email = ? AND email != '') LIMIT 1");
            $custStmt->execute([$customer_phone, $customer_email]);
            $customer = $custStmt->fetch();

            if ($customer) {
                $customer_id = $customer['customer_id'];
            } else {
                // Insert new customer
                $insertCust = $pdo->prepare("INSERT INTO customers (full_name, email, phone, address) VALUES (?, ?, ?, ?)");
                $insertCust->execute([$customer_name, $customer_email, $customer_phone, $customer_address]);
                $customer_id = $pdo->lastInsertId();
            }

            // Insert sale record
            $insertSale = $pdo->prepare("
                INSERT INTO sales (car_id, customer_id, salesperson_id, sale_price, sale_date, payment_method, notes) 
                VALUES (?, ?, ?, ?, CURDATE(), ?, ?)
            ");
            $insertSale->execute([$car_id, $customer_id, $salesperson_id, $sale_price, $payment_method, $notes]);

            // Update car status to sold
            $updateCar = $pdo->prepare("UPDATE cars SET status = 'sold' WHERE car_id = ?");
            $updateCar->execute([$car_id]);

            $pdo->commit();
            $successMessage = "Deal successfully closed! The car has been marked as sold.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $errorMessage = "Failed to record sale: " . $e->getMessage();
        }
    }
}

// Fetch only available cars
$stmt = $pdo->prepare("
    SELECT * FROM cars 
    WHERE status = 'available' 
    ORDER BY created_at DESC
");
$stmt->execute();
$availableCars = $stmt->fetchAll();
?>

<div class="space-y-8">
  
  <!-- Alert Messages -->
  <?php if ($successMessage): ?>
    <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 px-6 py-4 rounded-3xl mb-6 flex items-center gap-3">
      <i class="fa-solid fa-circle-check text-xl"></i>
      <span><?= htmlspecialchars($successMessage) ?></span>
    </div>
  <?php endif; ?>
  <?php if ($errorMessage): ?>
    <div class="bg-red-500/10 border border-red-500/30 text-red-400 px-6 py-4 rounded-3xl mb-6 flex items-center gap-3">
      <i class="fa-solid fa-circle-exclamation text-xl"></i>
      <span><?= htmlspecialchars($errorMessage) ?></span>
    </div>
  <?php endif; ?>

  <!-- Header -->
  <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
    <div>
      <h1 class="text-4xl font-bold tracking-tight">Available Cars</h1>
      <p class="text-zinc-400 mt-1"><?= count($availableCars) ?> premium vehicles ready for sale</p>
    </div>
    
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 w-full sm:w-auto">
      <div class="relative flex-1 min-w-0">
        <input type="text" id="search-cars" 
               onkeyup="filterAvailableCars()"
               class="bg-zinc-900 border border-zinc-800 rounded-3xl py-3 pl-12 pr-4 w-full sm:w-80 focus:outline-none focus:border-emerald-500 text-zinc-100"
               placeholder="Search make or model...">
        <i class="fa-solid fa-magnifying-glass absolute left-5 top-3.5 text-zinc-500"></i>
      </div>

      <select id="filter-fuel" onchange="filterAvailableCars()" 
              class="bg-zinc-900 border border-zinc-800 rounded-3xl px-6 py-3 w-full sm:w-56 focus:outline-none text-zinc-100">
        <option value="">All Fuel Types</option>
        <option value="Petrol">Petrol</option>
        <option value="Diesel">Diesel</option>
        <option value="Electric">Electric</option>
        <option value="Hybrid">Hybrid</option>
      </select>
    </div>
  </div>

  <!-- Cars Grid -->
  <div id="cars-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
    <?php if (empty($availableCars)): ?>
      <div class="col-span-full text-center py-20 bg-zinc-900 rounded-3xl border border-zinc-800/50">
        <div class="text-6xl mb-4">🚗</div>
        <p class="text-xl text-zinc-400">No cars available at the moment.</p>
      </div>
    <?php endif; ?>

    <?php foreach ($availableCars as $car): ?>
    <div class="car-card bg-zinc-900 rounded-3xl overflow-hidden border border-zinc-800/80 hover:border-emerald-500/50 transition-all duration-300 group" 
         data-make="<?= strtolower($car['make']) ?>" 
         data-model="<?= strtolower($car['model']) ?>" 
         data-fuel="<?= strtolower($car['fuel_type']) ?>">
      
      <!-- Car Image Placeholder / Gradient -->
      <div class="h-52 bg-gradient-to-br from-zinc-800 to-zinc-900 relative flex items-center justify-center overflow-hidden">
        <div class="text-8xl transition-transform group-hover:scale-110 duration-500 select-none">🚗</div>
        <div class="absolute top-4 right-4 bg-black/70 text-xs px-3 py-1 rounded-2xl backdrop-blur select-none">
          <?= $car['year'] ?>
        </div>
      </div>

      <div class="p-6">
        <div class="flex justify-between items-start">
          <div>
            <h3 class="text-xl font-bold"><?= htmlspecialchars($car['make']) ?> <?= htmlspecialchars($car['model']) ?></h3>
            <p class="text-zinc-400 text-sm mt-0.5"><?= htmlspecialchars($car['color'] ?? '') ?></p>
          </div>
          <div class="text-right">
            <p class="text-2xl font-bold text-emerald-400">LKR <?= number_format($car['price']) ?></p>
          </div>
        </div>

        <div class="mt-6 grid grid-cols-3 gap-2 text-center text-xs">
          <div class="bg-zinc-800/50 rounded-2xl py-2 border border-zinc-800">
            <i class="fa-solid fa-road text-zinc-500 mb-1"></i><br>
            <span class="font-medium"><?= number_format($car['mileage'] ?? 0) ?> km</span>
          </div>
          <div class="bg-zinc-800/50 rounded-2xl py-2 border border-zinc-800">
            <i class="fa-solid fa-gas-pump text-zinc-500 mb-1"></i><br>
            <span class="font-medium"><?= $car['fuel_type'] ?></span>
          </div>
          <div class="bg-zinc-800/50 rounded-2xl py-2 border border-zinc-800">
            <i class="fa-solid fa-gear text-zinc-500 mb-1"></i><br>
            <span class="font-medium"><?= $car['transmission'] ?></span>
          </div>
        </div>

        <div class="mt-6 flex gap-3">
          <button onclick="viewCarDetails(<?= htmlspecialchars(json_encode($car)) ?>)" 
                  class="flex-1 py-3 text-sm font-medium border border-zinc-800 hover:border-zinc-700 rounded-2xl transition text-zinc-300">
            Details
          </button>
          <button onclick="recordSale(<?= $car['car_id'] ?>, '<?= htmlspecialchars($car['make'] . ' ' . $car['model']) ?>', <?= $car['price'] ?>)" 
                  class="flex-1 py-3 bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white rounded-2xl font-semibold shadow-lg transition transform hover:scale-[1.02]">
            Sell Now
          </button>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Car Detail Modal -->
<div id="detail-modal" class="hidden fixed inset-0 bg-black/80 flex items-center justify-center z-50 p-4">
  <div class="bg-zinc-900 border border-zinc-800 rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl transition-all">
    <div class="p-6 border-b border-zinc-800 flex justify-between items-center bg-zinc-950">
      <h2 id="modal-car-title" class="text-2xl font-bold text-white">Car Details</h2>
      <button onclick="closeDetailModal()" class="text-3xl text-zinc-400 hover:text-white transition">&times;</button>
    </div>
    <div id="modal-content" class="p-6"></div>
    <div class="p-6 border-t border-zinc-800 bg-zinc-950/50 flex justify-end">
      <button onclick="closeDetailModal()" class="px-6 py-2.5 bg-zinc-800 hover:bg-zinc-700 text-zinc-300 hover:text-white rounded-2xl font-medium transition">
        Close
      </button>
    </div>
  </div>
</div>

<!-- Record Sale Modal -->
<div id="sale-modal" class="hidden fixed inset-0 bg-black/80 flex items-center justify-center z-50 p-4">
  <div class="bg-zinc-900 border border-zinc-800 rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl transition-all">
    <div class="p-6 border-b border-zinc-800 flex justify-between items-center bg-zinc-950">
      <h2 class="text-2xl font-bold flex items-center gap-2">
        <span class="text-emerald-400">🤝</span> Record Sale
      </h2>
      <button onclick="closeSaleModal()" class="text-3xl text-zinc-400 hover:text-white transition">&times;</button>
    </div>
    
    <form method="POST" class="p-6 space-y-4">
      <input type="hidden" name="action" value="record_sale">
      <input type="hidden" name="car_id" id="sale-car-id">
      
      <div>
        <label class="block text-zinc-400 text-sm mb-1.5">Car Selected</label>
        <input type="text" id="sale-car-name" readonly
               class="w-full bg-zinc-800/50 border border-zinc-800/80 rounded-2xl px-4 py-3 font-semibold text-zinc-400 focus:outline-none">
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-zinc-400 text-sm mb-1.5">Customer Name *</label>
          <input type="text" name="customer_name" required placeholder="John Doe"
                 class="w-full bg-zinc-800 border border-zinc-700 rounded-2xl px-4 py-3 text-zinc-100 focus:outline-none focus:border-emerald-500 transition">
        </div>
        <div>
          <label class="block text-zinc-400 text-sm mb-1.5">Customer Phone *</label>
          <input type="text" name="customer_phone" required placeholder="9876543210"
                 class="w-full bg-zinc-800 border border-zinc-700 rounded-2xl px-4 py-3 text-zinc-100 focus:outline-none focus:border-emerald-500 transition">
        </div>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-zinc-400 text-sm mb-1.5">Customer Email</label>
          <input type="email" name="customer_email" placeholder="john@example.com"
                 class="w-full bg-zinc-800 border border-zinc-700 rounded-2xl px-4 py-3 text-zinc-100 focus:outline-none focus:border-emerald-500 transition">
        </div>
        <div>
          <label class="block text-zinc-400 text-sm mb-1.5">Sale Price (LKR) *</label>
          <input type="number" name="sale_price" id="sale-price-input" required
                 class="w-full bg-zinc-800 border border-zinc-700 rounded-2xl px-4 py-3 text-zinc-100 font-bold focus:outline-none focus:border-emerald-500 transition">
        </div>
      </div>

      <div>
        <label class="block text-zinc-400 text-sm mb-1.5">Payment Method *</label>
        <select name="payment_method" required
                class="w-full bg-zinc-800 border border-zinc-700 rounded-2xl px-4 py-3 text-zinc-100 focus:outline-none focus:border-emerald-500 transition">
          <option value="Cash">Cash</option>
          <option value="Bank Transfer">Bank Transfer</option>
          <option value="Finance">Finance</option>
          <option value="Card">Card</option>
        </select>
      </div>

      <div>
        <label class="block text-zinc-400 text-sm mb-1.5">Customer Address</label>
        <textarea name="customer_address" rows="2" placeholder="Enter customer address..."
                  class="w-full bg-zinc-800 border border-zinc-700 rounded-2xl px-4 py-3 text-zinc-100 focus:outline-none focus:border-emerald-500 transition"></textarea>
      </div>

      <div>
        <label class="block text-zinc-400 text-sm mb-1.5">Notes / Deal details</label>
        <textarea name="notes" rows="2" placeholder="e.g. Free insurance included, delivered..."
                  class="w-full bg-zinc-800 border border-zinc-700 rounded-2xl px-4 py-3 text-zinc-100 focus:outline-none focus:border-emerald-500 transition"></textarea>
      </div>

      <div class="flex gap-4 pt-2">
        <button type="button" onclick="closeSaleModal()" 
                class="flex-1 py-3.5 rounded-2xl border border-zinc-700 hover:border-zinc-500 text-zinc-400 hover:text-white font-medium transition">
          Cancel
        </button>
        <button type="submit" 
                class="flex-1 bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white font-bold rounded-2xl shadow-lg transition">
          Confirm Deal
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function filterAvailableCars() {
  const search = document.getElementById('search-cars').value.toLowerCase();
  const fuelFilter = document.getElementById('filter-fuel').value.toLowerCase();
  
  document.querySelectorAll('.car-card').forEach(card => {
    const make = card.getAttribute('data-make');
    const model = card.getAttribute('data-model');
    const fuel = card.getAttribute('data-fuel');
    
    const matchesSearch = (make + ' ' + model).includes(search);
    const matchesFuel = !fuelFilter || fuel === fuelFilter;
    
    card.style.display = (matchesSearch && matchesFuel) ? 'block' : 'none';
  });
}

function viewCarDetails(car) {
  document.getElementById('modal-car-title').textContent = car.make + ' ' + car.model;
  
  let detailsHtml = `
    <div class="space-y-4 text-zinc-300">
      <div class="grid grid-cols-2 gap-4">
        <div>
          <span class="text-zinc-500 text-xs uppercase tracking-wider block mb-0.5">Year</span>
          <p class="font-semibold text-white text-base">${car.year}</p>
        </div>
        <div>
          <span class="text-zinc-500 text-xs uppercase tracking-wider block mb-0.5">Price</span>
          <p class="font-bold text-emerald-400 text-lg">LKR ${parseInt(car.price).toLocaleString()}</p>
        </div>
        <div>
          <span class="text-zinc-500 text-xs uppercase tracking-wider block mb-0.5">Fuel Type</span>
          <p class="font-semibold text-white text-base">${car.fuel_type}</p>
        </div>
        <div>
          <span class="text-zinc-500 text-xs uppercase tracking-wider block mb-0.5">Transmission</span>
          <p class="font-semibold text-white text-base">${car.transmission}</p>
        </div>
        <div>
          <span class="text-zinc-500 text-xs uppercase tracking-wider block mb-0.5">Mileage</span>
          <p class="font-semibold text-white text-base">${parseInt(car.mileage || 0).toLocaleString()} km</p>
        </div>
        <div>
          <span class="text-zinc-500 text-xs uppercase tracking-wider block mb-0.5">Color</span>
          <p class="font-semibold text-white text-base">${car.color || 'N/A'}</p>
        </div>
      </div>
      <div class="pt-2 border-t border-zinc-800">
        <span class="text-zinc-500 text-xs uppercase tracking-wider block mb-0.5">VIN</span>
        <p class="font-mono text-sm text-white tracking-wider">${car.vin || 'N/A'}</p>
      </div>
      <div class="pt-2 border-t border-zinc-800">
        <span class="text-zinc-500 text-xs uppercase tracking-wider block mb-1">Description</span>
        <p class="text-sm leading-relaxed bg-zinc-800/30 p-4 rounded-2xl border border-zinc-800 text-zinc-300">
          ${car.description || 'No description available for this premium vehicle.'}
        </p>
      </div>
    </div>
  `;
  document.getElementById('modal-content').innerHTML = detailsHtml;
  document.getElementById('detail-modal').classList.remove('hidden');
}

function closeDetailModal() {
  document.getElementById('detail-modal').classList.add('hidden');
}

function recordSale(carId, carName, price) {
  document.getElementById('sale-car-id').value = carId;
  document.getElementById('sale-car-name').value = carName;
  document.getElementById('sale-price-input').value = price;
  document.getElementById('sale-modal').classList.remove('hidden');
}

function closeSaleModal() {
  document.getElementById('sale-modal').classList.add('hidden');
}

// Keyboard shortcut support
document.addEventListener('keydown', function(e) {
  if (e.key === '/' && document.getElementById('search-cars')) {
    e.preventDefault();
    document.getElementById('search-cars').focus();
  }
  if (e.key === 'Escape') {
    closeDetailModal();
    closeSaleModal();
  }
});
</script>