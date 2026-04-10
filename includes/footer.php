</main>

<footer class="footer-premium text-light py-5 mt-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <h5 class="d-flex align-items-center gap-2 mb-3">
                    <span class="brand-icon footer-icon d-inline-flex align-items-center justify-content-center">
                        <i class="bi bi-house-heart-fill"></i>
                    </span>
                    <?= SITE_NAME ?>
                </h5>
                <p class="text-muted small mb-0">
                    Connecting Copenhagen neighbours to share and reuse furniture —
                    because sustainability starts next door.
                </p>
            </div>

            <div class="col-md-4">
                <h6 class="text-uppercase small fw-semibold text-white-50">Quick Links</h6>
                <ul class="list-unstyled small mb-0">
                    <li><a href="<?= SITE_URL ?>/browse.php" class="footer-link">Browse Furniture</a></li>
                    <li><a href="<?= SITE_URL ?>/register.php" class="footer-link">Join Community</a></li>
                </ul>
            </div>

            <div class="col-md-4">
                <h6 class="text-uppercase small fw-semibold text-white-50">Admin Access</h6>
                <ul class="list-unstyled small mb-0">
                    <li>
                        <a href="<?= ADMIN_URL ?>/login.php" class="footer-link d-inline-flex align-items-center gap-1">
                            <i class="bi bi-shield-lock"></i> Admin Panel
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <hr class="border-secondary opacity-25 my-4">

        <p class="text-center text-muted small mb-0">
            &copy; <?= date('Y') ?> <?= SITE_NAME ?> — Copenhagen, Denmark
        </p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= SITE_URL ?>/js/app.js"></script>
<script>
(function () {
  const CURRENT_USER_ID = <?= isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0 ?>;
  if (!CURRENT_USER_ID) return;

  const WS_HOST = window.location.hostname;
  const ws = new WebSocket(`ws://${WS_HOST}:8080?user_id=${CURRENT_USER_ID}`);
  window.appWS = ws; // optional global access

  // simple browser notification (ask once)
  if ("Notification" in window && Notification.permission === "default") {
    Notification.requestPermission().catch(()=>{});
  }

  function updatePresence(userId, isOnline) {
    document.querySelectorAll(`[data-user-id="${userId}"] .presence-dot`).forEach(dot => {
      dot.classList.toggle('presence-online', !!isOnline);
      dot.classList.toggle('presence-offline', !isOnline);
    });
    document.querySelectorAll(`[data-user-status-id="${userId}"]`).forEach(el => {
      el.textContent = isOnline ? 'Online' : 'Offline';
    });
  }

  function bumpBell() {
    const b = document.getElementById('globalMessageBadge');
    if (!b) return;
    const n = parseInt(b.textContent || '0', 10) + 1;
    b.textContent = String(n);
    b.classList.remove('d-none');
  }

  function showToast(text) {
    // very simple toast
    let t = document.getElementById('msgToast');
    if (!t) {
      t = document.createElement('div');
      t.id = 'msgToast';
      t.style.cssText = 'position:fixed;right:16px;bottom:16px;background:#111;color:#fff;padding:10px 14px;border-radius:10px;z-index:9999;';
      document.body.appendChild(t);
    }
    t.textContent = text;
    t.style.display = 'block';
    setTimeout(()=> t.style.display='none', 2500);
  }

  ws.onmessage = (e) => {
    const data = JSON.parse(e.data);

    if (data.type === 'presence') {
      updatePresence(Number(data.user_id), Number(data.is_online) === 1);
      return;
    }

    if (data.type === 'new_message' && Number(data.to) === CURRENT_USER_ID) {
      bumpBell();
      showToast('New message received');

      // Browser push-like popup
      if ("Notification" in window && Notification.permission === "granted") {
        new Notification("ShareToNeighbour", { body: data.body || "You have a new message" });
      }

      // if chat thread page is open, trigger page handler
      window.dispatchEvent(new CustomEvent('app:new-message', { detail: data }));
    }
  };
})();
</script>
<!-- Optional: Add for a notifications popup -->
<script>
(function () {
  const bell = document.getElementById('notifBell');
  const list = document.getElementById('notifList');
  const badge = document.getElementById('globalMessageBadge');
  const markBtn = document.getElementById('markAllSeenBtn');
  if (!bell || !list) return;

  function esc(s='') {
    return String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
  }

  async function loadNotifications() {
    const res = await fetch('<?= SITE_URL ?>/api_notifications.php?action=list', {credentials:'same-origin'});
    const json = await res.json();
    if (!json.ok) {
      list.innerHTML = `<div class="p-3 text-danger small">Failed to load.</div>`;
      return;
    }
    const rows = json.data || [];
    if (!rows.length) {
      list.innerHTML = `<div class="p-3 text-muted small">No notifications</div>`;
      return;
    }

    list.innerHTML = rows.map(n => `
      <div class="px-3 py-2 border-bottom ${Number(n.is_seen)===0 ? 'bg-light' : ''}">
        <div class="d-flex justify-content-between">
          <div class="fw-semibold small">${esc(n.title)}</div>
          <small class="text-muted">${new Date(n.created_at.replace(' ','T')).toLocaleString()}</small>
        </div>
        ${n.body ? `<div class="small text-muted mt-1">${esc(n.body)}</div>` : ``}
      </div>
    `).join('');
  }

  async function markSeen() {
    await fetch('<?= SITE_URL ?>/api_notifications.php?action=mark_seen', {credentials:'same-origin'});
    if (badge) {
      badge.textContent = '0';
      badge.classList.add('d-none');
    }
    // remove highlight
    list.querySelectorAll('.bg-light').forEach(el => el.classList.remove('bg-light'));
  }

  bell.addEventListener('show.bs.dropdown', async () => {
    await loadNotifications();
    await markSeen(); // reset to 0 when opened
  });

  markBtn?.addEventListener('click', async (e) => {
    e.preventDefault();
    await markSeen();
  });

  // optional realtime bump handler
  window.bumpNotificationBadge = function() {
    if (!badge) return;
    let n = parseInt(badge.textContent || '0', 10);
    n++;
    badge.textContent = String(n);
    badge.classList.remove('d-none');
  };
})();
</script>

</body>
</html>