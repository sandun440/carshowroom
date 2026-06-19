<?php
// sections/cars.php
require_once __DIR__ . '/../config/db.php';

// Handle Actions (Add/Edit/Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {
            $car_id = $_POST['car_id'] ?? null;
            $data = [
                'make' => $_POST['make'],
                'model' => $_POST['model'],
                'year' => $_POST['year'],
                'price' => $_POST['price'],
                'color' => $_POST['color'],
                'mileage' => $_POST['mileage'],
                'fuel_type' => $_POST['fuel_type'],
                'transmission' => $_POST['transmission'],
                'description' => $_POST['description'],
                'status' => $_POST['status'],
                'vin' => $_POST['vin'] ?? null
            ];

            if ($car_id) {
                // Update
                $sql = "UPDATE cars SET make=:make, model=:model, year=:year, price=:price, 
                        color=:color, mileage=:mileage, fuel_type=:fuel_type, 
                        transmission=:transmission, description=:description, 
                        status=:status, vin=:vin WHERE car_id = :car_id";
                $stmt = $pdo->prepare($sql);
                $data['car_id'] = $car_id;
            } else {
                // Insert
                $sql = "INSERT INTO cars (make, model, year, price, color, mileage, fuel_type, 
                        transmission, description, status, vin, added_by) 
                        VALUES (:make, :model, :year, :price, :color, :mileage, :fuel_type, 
                        :transmission, :description, :status, :vin, :added_by)";
                $stmt = $pdo->prepare($sql);
                $data['added_by'] = $_SESSION['user_id'];
            }
            $stmt->execute($data);
        } 
        elseif ($_POST['action'] === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM cars WHERE car_id = ?");
            $stmt->execute([$_POST['car_id']]);
        }
    }
}

// Fetch All Cars
$stmt = $pdo->query("
    SELECT * FROM cars 
    ORDER BY created_at DESC
");
$cars = $stmt->fetchAll();
?>

<div class="space-y-6">
  <div class="flex justify-between items-center">
    <div>
      <h1 class="text-3xl font-bold">Manage Cars</h1>
      <p class="text-zinc-400">Total Cars: <span class="font-semibold text-white"><?= count($cars) ?></span></p>
    </div>
    <button onclick="showAddModal()" 
            class="bg-gradient-to-r from-red-500 to-orange-500 hover:from-red-600 hover:to-orange-600 px-6 py-3 rounded-2xl font-medium flex items-center gap-2 transition">
      <i class="fa-solid fa-plus"></i> Add New Car
    </button>
  </div>

  <!-- Search & Filter -->
  <div class="flex flex-col gap-4">
    <div class="flex-1 relative">
      <input type="text" id="car-search" 
             onkeyup="filterCars()"
             class="w-full bg-zinc-900 border border-zinc-700 rounded-3xl py-3 pl-12 pr-4 focus:outline-none focus:border-orange-500"
             placeholder="Search by make, model or VIN...">
      <i class="fa-solid fa-magnifying-glass absolute left-5 top-3.5 text-zinc-500"></i>
    </div>
  </div>

  <!-- Cars Table -->
  <div class="bg-zinc-900 rounded-3xl overflow-x-auto border border-zinc-800">
    <table class="min-w-full">
      <thead class="bg-zinc-950">
        <tr>
          <th class="px-6 py-5 text-left">Car</th>
          <th class="px-6 py-5 text-left">Price</th>
          <th class="px-6 py-5 text-left">Details</th>
          <th class="px-6 py-5 text-center">Status</th>
          <th class="px-6 py-5 text-center">Actions</th>
        </tr>
      </thead>
      <tbody id="cars-table" class="divide-y divide-zinc-800">
        <?php foreach ($cars as $car): ?>
        <tr class="hover:bg-zinc-800/70 transition group" data-search="<?= strtolower($car['make'].' '.$car['model'].' '.$car['vin']) ?>">
          <td class="px-6 py-5">
            <div class="flex items-center gap-4">
              <div class="w-14 h-14 bg-zinc-800 rounded-2xl flex items-center justify-center text-3xl">
                🚗
              </div>
              <div>
                <p class="font-semibold"><?= htmlspecialchars($car['make']) ?> <?= htmlspecialchars($car['model']) ?></p>
                <p class="text-sm text-zinc-400"><?= $car['year'] ?> • <?= htmlspecialchars($car['color'] ?? 'N/A') ?></p>
              </div>
            </div>
          </td>
          <td class="px-6 py-5">
            <p class="font-bold text-lg">₹<?= number_format($car['price']) ?></p>
          </td>
          <td class="px-6 py-5 text-sm text-zinc-400">
            <?= $car['mileage'] ?> km • <?= $car['fuel_type'] ?> • <?= $car['transmission'] ?>
          </td>
          <td class="px-6 py-5 text-center">
            <?php 
            $statusClass = $car['status'] === 'available' ? 'bg-emerald-500/20 text-emerald-400' : 
                          ($car['status'] === 'sold' ? 'bg-red-500/20 text-red-400' : 'bg-yellow-500/20 text-yellow-400');
            ?>
            <span class="px-4 py-1.5 text-xs font-medium rounded-2xl <?= $statusClass ?>">
              <?= ucfirst($car['status']) ?>
            </span>
          </td>
          <td class="px-6 py-5 text-center">
            <button onclick="editCar(<?= $car['car_id'] ?>, <?= htmlspecialchars(json_encode($car)) ?>)" 
                    class="text-blue-400 hover:text-blue-500 mx-2">
              <i class="fa-solid fa-edit"></i>
            </button>
            <button onclick="deleteCar(<?= $car['car_id'] ?>)" 
                    class="text-red-400 hover:text-red-500 mx-2">
              <i class="fa-solid fa-trash"></i>
            </button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add/Edit Modal -->
<div id="car-modal" class="hidden fixed inset-0 bg-black/70 flex items-center justify-center z-50">
  <div class="bg-zinc-900 rounded-3xl w-full max-w-2xl mx-4 overflow-hidden">
    <div class="p-6 border-b border-zinc-800 flex justify-between items-center">
      <h2 id="modal-title" class="text-2xl font-bold">Add New Car</h2>
      <button onclick="closeModal()" class="text-3xl text-zinc-400 hover:text-white">&times;</button>
    </div>
    
    <form id="car-form" method="POST" class="p-6 space-y-5">
      <input type="hidden" name="action" id="form-action" value="add">
      <input type="hidden" name="car_id" id="form-car-id">

      <div class="grid grid-cols-2 gap-5">
        <div>
          <label class="block text-sm text-zinc-400 mb-1">Make</label>
          <input type="text" name="make" id="make" required
                 class="w-full bg-zinc-800 border border-zinc-700 rounded-2xl px-4 py-3 focus:outline-none focus:border-orange-500">
        </div>
        <div>
          <label class="block text-sm text-zinc-400 mb-1">Model</label>
          <input type="text" name="model" id="model" required
                 class="w-full bg-zinc-800 border border-zinc-700 rounded-2xl px-4 py-3 focus:outline-none focus:border-orange-500">
        </div>
      </div>

      <div class="grid grid-cols-3 gap-5">
        <div>
          <label class="block text-sm text-zinc-400 mb-1">Year</label>
          <input type="number" name="year" id="year" required
                 class="w-full bg-zinc-800 border border-zinc-700 rounded-2xl px-4 py-3">
        </div>
        <div>
          <label class="block text-sm text-zinc-400 mb-1">Price (₹)</label>
          <input type="number" name="price" id="price" required
                 class="w-full bg-zinc-800 border border-zinc-700 rounded-2xl px-4 py-3">
        </div>
        <div>
          <label class="block text-sm text-zinc-400 mb-1">VIN</label>
          <input type="text" name="vin" id="vin"
                 class="w-full bg-zinc-800 border border-zinc-700 rounded-2xl px-4 py-3">
        </div>
      </div>

      <div class="grid grid-cols-2 gap-5">
        <div>
          <label class="block text-sm text-zinc-400 mb-1">Color</label>
          <input type="text" name="color" id="color"
                 class="w-full bg-zinc-800 border border-zinc-700 rounded-2xl px-4 py-3">
        </div>
        <div>
          <label class="block text-sm text-zinc-400 mb-1">Mileage (km)</label>
          <input type="number" name="mileage" id="mileage"
                 class="w-full bg-zinc-800 border border-zinc-700 rounded-2xl px-4 py-3">
        </div>
      </div>

      <div class="grid grid-cols-2 gap-5">
        <div>
          <label class="block text-sm text-zinc-400 mb-1">Fuel Type</label>
          <select name="fuel_type" id="fuel_type" class="w-full bg-zinc-800 border border-zinc-700 rounded-2xl px-4 py-3">
            <option value="Petrol">Petrol</option>
            <option value="Diesel">Diesel</option>
            <option value="Electric">Electric</option>
            <option value="Hybrid">Hybrid</option>
          </select>
        </div>
        <div>
          <label class="block text-sm text-zinc-400 mb-1">Transmission</label>
          <select name="transmission" id="transmission" class="w-full bg-zinc-800 border border-zinc-700 rounded-2xl px-4 py-3">
            <option value="Automatic">Automatic</option>
            <option value="Manual">Manual</option>
            <option value="CVT">CVT</option>
          </select>
        </div>
      </div>

      <div>
        <label class="block text-sm text-zinc-400 mb-1">Status</label>
        <select name="status" id="status" class="w-full bg-zinc-800 border border-zinc-700 rounded-2xl px-4 py-3">
          <option value="available">Available</option>
          <option value="sold">Sold</option>
          <option value="reserved">Reserved</option>
          <option value="maintenance">Maintenance</option>
        </select>
      </div>

      <div>
        <label class="block text-sm text-zinc-400 mb-1">Description</label>
        <textarea name="description" id="description" rows="3"
                  class="w-full bg-zinc-800 border border-zinc-700 rounded-3xl px-4 py-3"></textarea>
      </div>

      <div class="flex gap-4 pt-4">
        <button type="button" onclick="closeModal()" 
                class="flex-1 py-4 rounded-2xl border border-zinc-700 font-medium">Cancel</button>
        <button type="submit" 
                class="flex-1 bg-gradient-to-r from-orange-500 to-red-500 py-4 rounded-2xl font-semibold">Save Car</button>
      </div>
    </form>
  </div>
</div>

<script>
function showAddModal() {
  document.getElementById('modal-title').textContent = 'Add New Car';
  document.getElementById('form-action').value = 'add';
  document.getElementById('form-car-id').value = '';
  document.getElementById('car-form').reset();
  document.getElementById('car-modal').classList.remove('hidden');
}

function editCar(id, car) {
  document.getElementById('modal-title').textContent = 'Edit Car';
  document.getElementById('form-action').value = 'edit';
  document.getElementById('form-car-id').value = id;
  
  document.getElementById('make').value = car.make;
  document.getElementById('model').value = car.model;
  document.getElementById('year').value = car.year;
  document.getElementById('price').value = car.price;
  document.getElementById('vin').value = car.vin || '';
  document.getElementById('color').value = car.color || '';
  document.getElementById('mileage').value = car.mileage || '';
  document.getElementById('fuel_type').value = car.fuel_type;
  document.getElementById('transmission').value = car.transmission;
  document.getElementById('status').value = car.status;
  document.getElementById('description').value = car.description || '';
  
  document.getElementById('car-modal').classList.remove('hidden');
}

function closeModal() {
  document.getElementById('car-modal').classList.add('hidden');
}

function deleteCar(id) {
  if (confirm('Are you sure you want to delete this car?')) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="car_id" value="${id}">
    `;
    document.body.appendChild(form);
    form.submit();
  }
}

function filterCars() {
  const search = document.getElementById('car-search').value.toLowerCase();
  const rows = document.querySelectorAll('#cars-table tr');
  
  rows.forEach(row => {
    const text = row.getAttribute('data-search') || '';
    row.style.display = text.includes(search) ? '' : 'none';
  });
}
</script>