<?php
// sections/users.php
require_once __DIR__ . '/../config/db.php';

$successMessage = '';
$errorMessage = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'toggle_status') {
        $target_user_id = (int)$_POST['user_id'];
        $current_status = (int)$_POST['is_active'];
        $new_status = $current_status === 1 ? 0 : 1;
        
        if ($target_user_id === (int)$_SESSION['user_id']) {
            $errorMessage = "You cannot disable your own administrator account.";
        } else {
            $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE user_id = ?");
            $stmt->execute([$new_status, $target_user_id]);
            $successMessage = "Staff status updated successfully!";
        }
    } elseif ($_POST['action'] === 'delete_user') {
        $target_user_id = (int)$_POST['user_id'];
        if ($target_user_id === (int)$_SESSION['user_id']) {
            $errorMessage = "You cannot delete your own administrator account.";
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
                $stmt->execute([$target_user_id]);
                $successMessage = "Staff account deleted successfully!";
            } catch (PDOException $e) {
                $errorMessage = "Cannot delete this staff member because they have sales reports. Disable their account instead to restrict access.";
            }
        }
    } elseif ($_POST['action'] === 'add_user') {
        $username = trim($_POST['username']);
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $password = $_POST['password'];
        $role_id = (int)$_POST['role_id'];

        if (empty($username) || empty($full_name) || empty($email) || empty($password) || empty($role_id)) {
            $errorMessage = "Please fill in all required fields.";
        } else {
            // Check existence
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $errorMessage = "Username or Email already exists.";
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    INSERT INTO users (username, full_name, email, phone, password_hash, role_id, is_active) 
                    VALUES (?, ?, ?, ?, ?, ?, 1)
                ");
                $stmt->execute([$username, $full_name, $email, $phone, $password_hash, $role_id]);
                $successMessage = "Staff member '{$full_name}' added successfully!";
            }
        }
    }
}

// Fetch stats
$totalStaff = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$activeStaff = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn();
$adminStaff = $pdo->query("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id = r.role_id WHERE r.role_name = 'admin'")->fetchColumn();
$salesStaff = $pdo->query("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id = r.role_id WHERE r.role_name = 'sales'")->fetchColumn();

// Fetch users
$stmt = $pdo->query("
    SELECT u.*, r.role_name 
    FROM users u
    JOIN roles r ON u.role_id = r.role_id
    ORDER BY u.created_at DESC
");
$staffList = $stmt->fetchAll();

// Fetch roles for form dropdown
$roles = $pdo->query("SELECT * FROM roles")->fetchAll();
?>

<div class="space-y-8">
  <!-- Alerts -->
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

  <!-- Welcome / Title -->
  <div class="flex justify-between items-center">
    <div>
      <h1 class="text-4xl font-bold tracking-tight">Staff Management</h1>
      <p class="text-zinc-400 mt-1">Manage showroom sales executives and system administrators.</p>
    </div>
    <button onclick="showAddStaffModal()" 
            class="bg-gradient-to-r from-red-500 to-orange-500 hover:from-red-600 hover:to-orange-600 px-6 py-3 rounded-2xl font-semibold flex items-center gap-2 transition transform hover:scale-[1.02] shadow-lg">
      <i class="fa-solid fa-user-plus"></i> Add New Staff
    </button>
  </div>

  <!-- Stats Grid -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    <div class="bg-zinc-900 border border-zinc-800 rounded-3xl p-6">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-zinc-400 text-sm">Total Staff</p>
          <p class="text-4xl font-bold mt-2 text-white"><?= $totalStaff ?></p>
        </div>
        <div class="w-12 h-12 bg-zinc-800 rounded-2xl flex items-center justify-center text-2xl">👥</div>
      </div>
    </div>
    <div class="bg-zinc-900 border border-zinc-800 rounded-3xl p-6">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-zinc-400 text-sm">Active Staff</p>
          <p class="text-4xl font-bold mt-2 text-emerald-400"><?= $activeStaff ?></p>
        </div>
        <div class="w-12 h-12 bg-emerald-500/10 rounded-2xl flex items-center justify-center text-2xl">🟢</div>
      </div>
    </div>
    <div class="bg-zinc-900 border border-zinc-800 rounded-3xl p-6">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-zinc-400 text-sm">Administrators</p>
          <p class="text-4xl font-bold mt-2 text-purple-400"><?= $adminStaff ?></p>
        </div>
        <div class="w-12 h-12 bg-purple-500/10 rounded-2xl flex items-center justify-center text-2xl">🛡️</div>
      </div>
    </div>
    <div class="bg-zinc-900 border border-zinc-800 rounded-3xl p-6">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-zinc-400 text-sm">Sales Executives</p>
          <p class="text-4xl font-bold mt-2 text-blue-400"><?= $salesStaff ?></p>
        </div>
        <div class="w-12 h-12 bg-blue-500/10 rounded-2xl flex items-center justify-center text-2xl">💼</div>
      </div>
    </div>
  </div>

  <!-- Filter & Search -->
  <div class="flex gap-4">
    <div class="flex-1 relative">
      <input type="text" id="staff-search" onkeyup="filterStaff()"
             class="w-full bg-zinc-900 border border-zinc-800 rounded-3xl py-3 pl-12 pr-4 focus:outline-none focus:border-orange-500 text-zinc-100 placeholder-zinc-500"
             placeholder="Search by name, username, email...">
      <i class="fa-solid fa-magnifying-glass absolute left-5 top-3.5 text-zinc-500"></i>
    </div>
  </div>

  <!-- Staff Table -->
  <div class="bg-zinc-900 border border-zinc-800 rounded-3xl overflow-hidden shadow-2xl">
    <table class="w-full">
      <thead class="bg-zinc-950/80 border-b border-zinc-800">
        <tr class="text-zinc-400 text-sm">
          <th class="px-6 py-5 text-left">Staff Member</th>
          <th class="px-6 py-5 text-left">Username</th>
          <th class="px-6 py-5 text-left">Email & Phone</th>
          <th class="px-6 py-5 text-center">Role</th>
          <th class="px-6 py-5 text-center">Status</th>
          <th class="px-6 py-5 text-center">Actions</th>
        </tr>
      </thead>
      <tbody id="staff-table" class="divide-y divide-zinc-800/60">
        <?php foreach ($staffList as $member): ?>
        <tr class="hover:bg-zinc-800/40 transition duration-150 staff-row" 
            data-search="<?= strtolower($member['full_name'] . ' ' . $member['username'] . ' ' . $member['email']) ?>">
          
          <td class="px-6 py-5">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-zinc-800 rounded-xl flex items-center justify-center text-xl font-bold select-none">
                <?= mb_substr($member['full_name'], 0, 1) ?>
              </div>
              <div>
                <p class="font-semibold text-white"><?= htmlspecialchars($member['full_name']) ?></p>
                <p class="text-xs text-zinc-500">Registered <?= date('d M, Y', strtotime($member['created_at'])) ?></p>
              </div>
            </div>
          </td>

          <td class="px-6 py-5">
            <span class="font-mono text-zinc-300">@<?= htmlspecialchars($member['username']) ?></span>
          </td>

          <td class="px-6 py-5 text-sm text-zinc-300">
            <div><?= htmlspecialchars($member['email']) ?></div>
            <div class="text-zinc-500 mt-0.5"><?= htmlspecialchars($member['phone'] ?? 'No Phone') ?></div>
          </td>

          <td class="px-6 py-5 text-center">
            <?php if ($member['role_name'] === 'admin'): ?>
              <span class="px-3.5 py-1 text-xs font-semibold rounded-full bg-purple-500/10 border border-purple-500/20 text-purple-400">
                Administrator
              </span>
            <?php else: ?>
              <span class="px-3.5 py-1 text-xs font-semibold rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-400">
                Sales Exec
              </span>
            <?php endif; ?>
          </td>

          <td class="px-6 py-5 text-center">
            <button onclick="toggleUserStatus(<?= $member['user_id'] ?>, <?= $member['is_active'] ?>)"
                    class="px-3 py-1 rounded-full text-xs font-semibold transition border duration-200 <?= $member['is_active'] ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400 hover:bg-emerald-500/20' : 'bg-zinc-800 border-zinc-700 text-zinc-500 hover:bg-zinc-700' ?>">
              <?= $member['is_active'] ? 'Active' : 'Disabled' ?>
            </button>
          </td>

          <td class="px-6 py-5 text-center">
            <?php if ($member['user_id'] !== $_SESSION['user_id']): ?>
              <button onclick="deleteStaff(<?= $member['user_id'] ?>, '<?= htmlspecialchars($member['full_name']) ?>')"
                      class="text-red-400 hover:text-red-500 p-2 hover:bg-red-500/10 rounded-xl transition duration-150">
                <i class="fa-solid fa-trash-can"></i>
              </button>
            <?php else: ?>
              <span class="text-zinc-600 text-xs italic">Logged In</span>
            <?php endif; ?>
          </td>

        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Staff Modal -->
<div id="add-staff-modal" class="hidden fixed inset-0 bg-black/80 flex items-center justify-center z-50 p-4">
  <div class="bg-zinc-900 border border-zinc-800 rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl transition-all">
    <div class="p-6 border-b border-zinc-800 flex justify-between items-center bg-zinc-950">
      <h2 class="text-2xl font-bold text-white flex items-center gap-2">
        <i class="fa-solid fa-user-plus text-orange-400"></i> Add New Staff Member
      </h2>
      <button onclick="closeAddStaffModal()" class="text-3xl text-zinc-400 hover:text-white transition">&times;</button>
    </div>
    
    <form method="POST" class="p-6 space-y-4">
      <input type="hidden" name="action" value="add_user">
      
      <div>
        <label class="block text-zinc-400 text-sm mb-1.5">Full Name *</label>
        <input type="text" name="full_name" required placeholder="Priya Patel"
               class="w-full bg-zinc-800 border border-zinc-700 rounded-2xl px-4 py-3 text-zinc-100 focus:outline-none focus:border-orange-500 transition">
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-zinc-400 text-sm mb-1.5">Username *</label>
          <input type="text" name="username" required placeholder="priya"
                 class="w-full bg-zinc-800 border border-zinc-700 rounded-2xl px-4 py-3 text-zinc-100 focus:outline-none focus:border-orange-500 transition">
        </div>
        <div>
          <label class="block text-zinc-400 text-sm mb-1.5">Role *</label>
          <select name="role_id" required
                  class="w-full bg-zinc-800 border border-zinc-700 rounded-2xl px-4 py-3 text-zinc-100 focus:outline-none focus:border-orange-500 transition">
            <?php foreach ($roles as $role): ?>
              <option value="<?= $role['role_id'] ?>"><?= ucfirst($role['role_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-zinc-400 text-sm mb-1.5">Email *</label>
          <input type="email" name="email" required placeholder="priya@autohub.com"
                 class="w-full bg-zinc-800 border border-zinc-700 rounded-2xl px-4 py-3 text-zinc-100 focus:outline-none focus:border-orange-500 transition">
        </div>
        <div>
          <label class="block text-zinc-400 text-sm mb-1.5">Phone</label>
          <input type="text" name="phone" placeholder="9876543210"
                 class="w-full bg-zinc-800 border border-zinc-700 rounded-2xl px-4 py-3 text-zinc-100 focus:outline-none focus:border-orange-500 transition">
        </div>
      </div>

      <div>
        <label class="block text-zinc-400 text-sm mb-1.5">Password *</label>
        <input type="password" name="password" required minlength="6" placeholder="Min. 6 characters"
               class="w-full bg-zinc-800 border border-zinc-700 rounded-2xl px-4 py-3 text-zinc-100 focus:outline-none focus:border-orange-500 transition">
      </div>

      <div class="flex gap-4 pt-2">
        <button type="button" onclick="closeAddStaffModal()" 
                class="flex-1 py-3.5 rounded-2xl border border-zinc-700 hover:border-zinc-500 text-zinc-400 hover:text-white font-medium transition">
          Cancel
        </button>
        <button type="submit" 
                class="flex-1 bg-gradient-to-r from-orange-500 to-red-500 hover:from-orange-600 hover:to-red-600 text-white font-bold rounded-2xl shadow-lg transition">
          Create Account
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function filterStaff() {
  const query = document.getElementById('staff-search').value.toLowerCase();
  document.querySelectorAll('.staff-row').forEach(row => {
    const text = row.getAttribute('data-search') || '';
    row.style.display = text.includes(query) ? '' : 'none';
  });
}

function showAddStaffModal() {
  document.getElementById('add-staff-modal').classList.remove('hidden');
}

function closeAddStaffModal() {
  document.getElementById('add-staff-modal').classList.add('hidden');
}

function toggleUserStatus(userId, currentStatus) {
  if (confirm(`Are you sure you want to ${currentStatus ? 'disable' : 'enable'} this staff member?`)) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
      <input type="hidden" name="action" value="toggle_status">
      <input type="hidden" name="user_id" value="${userId}">
      <input type="hidden" name="is_active" value="${currentStatus}">
    `;
    document.body.appendChild(form);
    form.submit();
  }
}

function deleteStaff(userId, name) {
  if (confirm(`Are you sure you want to delete staff account for '${name}'? This cannot be undone.`)) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
      <input type="hidden" name="action" value="delete_user">
      <input type="hidden" name="user_id" value="${userId}">
    `;
    document.body.appendChild(form);
    form.submit();
  }
}

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    closeAddStaffModal();
  }
});
</script>
