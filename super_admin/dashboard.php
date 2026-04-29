<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/super_admin_auth.php';
requireSuperAdminLogin();

// list admins
$stmt = $conn->prepare("SELECT id, username, full_name, is_active, created_at FROM admins ORDER BY id DESC");
$stmt->execute();
$admins = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Super Admin Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <link rel="stylesheet" href="<?= SITE_URL ?>/public/css/admin.css">
</head>
<body class="bg-light">
<div class="container py-4 py-md-5">
  <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Super Admin Dashboard</h1>
    <div class="d-flex align-items-center gap-2">
      <span class="badge text-bg-dark-subtle border text-dark-emphasis px-3 py-2">
        Logged in as <?= htmlspecialchars($_SESSION['super_admin_username'] ?? '') ?>
      </span>
      <a class="btn btn-outline-danger btn-sm" href="<?= Super_admin_URL ?>/logout.php">Logout</a>
    </div>
  </div>

  <div class="row g-4 mb-4">
    <div class="col-12 col-xl-5">
      <div class="card shadow-sm border-0">
        <div class="card-body p-4">
          <h2 class="h5 mb-3">Create Admin</h2>
          <form method="post" action="<?= Super_admin_URL ?>/create_admin.php" class="vstack gap-3">
            <div>
              <label for="username" class="form-label">Username</label>
              <input id="username" class="form-control" name="username" placeholder="Enter username" required>
            </div>
            <div>
              <label for="full_name" class="form-label">Full name</label>
              <input id="full_name" class="form-control" name="full_name" placeholder="Enter full name" required>
            </div>
            <div>
              <label for="password" class="form-label">Temporary password</label>
              <input id="password" class="form-control" name="password" type="password" placeholder="Minimum 8 characters" required minlength="8">
            </div>
            <button type="submit" class="btn btn-primary">Create Admin</button>
          </form>
        </div>
      </div>
    </div>

    <div class="col-12 col-xl-7">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-body p-4">
          <h2 class="h5 mb-3">Admins</h2>
          <div class="table-responsive">
            <table class="table align-middle admin-table mb-0">
              <thead>
              <tr>
                <th scope="col">ID</th>
                <th scope="col">Username</th>
                <th scope="col">Name</th>
                <th scope="col">Status</th>
                <th scope="col" class="text-end">Actions</th>
              </tr>
              </thead>
              <tbody>
              <?php foreach ($admins as $a): ?>
                <tr>
                  <td><?= (int)$a['id'] ?></td>
                  <td><?= htmlspecialchars($a['username']) ?></td>
                  <td><?= htmlspecialchars($a['full_name']) ?></td>
                  <td>
                    <?php if ((int)$a['is_active']): ?>
                      <span class="badge text-bg-success">Active</span>
                    <?php else: ?>
                      <span class="badge text-bg-secondary">Disabled</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="d-flex justify-content-end gap-2 flex-wrap">
                      <form method="post" action="<?= Super_admin_URL ?>/toggle_admin.php">
                        <input type="hidden" name="admin_id" value="<?= (int)$a['id'] ?>">
                        <input type="hidden" name="set_active" value="<?= (int)$a['is_active'] ? 0 : 1 ?>">
                        <button type="submit" class="btn btn-sm <?= (int)$a['is_active'] ? 'btn-outline-warning' : 'btn-outline-success' ?>">
                          <?= (int)$a['is_active'] ? 'Disable' : 'Enable' ?>
                        </button>
                      </form>

                      <form method="post" action="<?= Super_admin_URL ?>/generate_reset_link.php">
                        <input type="hidden" name="admin_id" value="<?= (int)$a['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-primary">Generate Reset Link</button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>