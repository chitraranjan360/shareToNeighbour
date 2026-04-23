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
<script src="<?= SITE_URL ?>../public/js/app.js"></script>
<script>
  (function() {
    const CURRENT_USER_ID = <?= isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0 ?>;
    if (!CURRENT_USER_ID) return;

    const WS_HOST = window.location.hostname;
    const ws = new WebSocket(`ws://${WS_HOST}:8080?user_id=${CURRENT_USER_ID}`);
    window.appWS = ws;

    if ("Notification" in window && Notification.permission === "default") {
      Notification.requestPermission().catch(() => {});
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
      let t = document.getElementById('msgToast');
      if (!t) {
        t = document.createElement('div');
        t.id = 'msgToast';
        t.style.cssText = 'position:fixed;right:16px;bottom:16px;background:#111;color:#fff;padding:10px 14px;border-radius:10px;z-index:9999;';
        document.body.appendChild(t);
      }
      t.textContent = text;
      t.style.display = 'block';
      setTimeout(() => t.style.display = 'none', 2500);
    }

    ws.onmessage = (e) => {
      const data = JSON.parse(e.data);

      if (data.type === 'presence') {
        updatePresence(Number(data.user_id), Number(data.is_online) === 1);
        return;
      }

      if (data.type === 'new_message' && Number(data.to) === CURRENT_USER_ID) {
        const onChatPage = /\/chat_thread\.php$/i.test(window.location.pathname);
        const activeUser = Number(new URLSearchParams(window.location.search).get('user') || 0);
        const fromUser = Number(data.from || 0);

        // inside same active chat: no bell increment, no toast, no browser notification
        if (onChatPage && activeUser > 0 && fromUser === activeUser) {
          window.dispatchEvent(new CustomEvent('app:new-message', {
            detail: data
          }));
          return;
        }

        bumpBell();
        showToast('New message received');

        if ("Notification" in window && Notification.permission === "granted") {
          new Notification("ShareToNeighbour", {
            body: data.body || "You have a new message"
          });
        }

        window.dispatchEvent(new CustomEvent('app:new-message', {
          detail: data
        }));
      }
    };
  })();
</script>
<!--
Chatbot floating button
-->
<?php if (isUserLoggedIn() && defined('CHATBOT_ENABLED') && CHATBOT_ENABLED): ?>
  <style>
    #supportBotBtn {
      position: fixed;
      right: 18px;
      bottom: 18px;
      z-index: 9999;
      border-radius: 999px;
      padding: 10px 14px;
    }

    #supportBotBox {
      position: fixed;
      right: 18px;
      bottom: 72px;
      z-index: 9999;
      width: 340px;
      max-width: calc(100vw - 36px);
      display: none;
    }

    #supportBotMsgs {
      height: 260px;
      overflow: auto;
      background: #fff;
    }

    .support-msg {
      padding: .4rem .6rem;
      border-radius: .6rem;
      margin: .35rem 0;
      max-width: 90%;
    }

    .support-user {
      background: #e9f7ef;
      margin-left: auto;
    }

    .support-bot {
      background: #f1f3f5;
      margin-right: auto;
    }
  </style>

  <button id="supportBotBtn" class="btn btn-success shadow">
    <i class="bi bi-chat-dots"></i> Help
  </button>

  <div id="supportBotBox" class="card shadow">
    <div class="card-header py-2 d-flex justify-content-between align-items-center">
      <div class="fw-semibold small">Support Bot</div>
      <button type="button" class="btn btn-sm btn-light" id="supportBotClose">X</button>
    </div>

    <div class="card-body p-2">
      <div id="supportBotMsgs" class="border rounded p-2 mb-2 small"></div>

      <div class="input-group">
        <input id="supportBotInput" type="text" class="form-control" placeholder="Ask about using the site...">
        <button id="supportBotSend" class="btn btn-success">Send</button>
      </div>
    </div>
  </div>

  <script>
    (function() {
      const btn = document.getElementById('supportBotBtn');
      const box = document.getElementById('supportBotBox');
      const close = document.getElementById('supportBotClose');
      const msgs = document.getElementById('supportBotMsgs');
      const input = document.getElementById('supportBotInput');
      const send = document.getElementById('supportBotSend');

      function addMsg(text, who) {
        const d = document.createElement('div');
        d.className = 'support-msg ' + (who === 'user' ? 'support-user' : 'support-bot');
        d.textContent = text;
        msgs.appendChild(d);
        msgs.scrollTop = msgs.scrollHeight;
      }

      function toggle() {
        box.style.display = (box.style.display === 'none' || !box.style.display) ? 'block' : 'none';
        if (box.style.display === 'block' && msgs.childElementCount === 0) {
          addMsg("Hi! Ask me how to use ShareToNeighbour (posting items, requests, reviews, safety).", "bot");
        }
        if (box.style.display === 'block') input.focus();
      }

      let busy = false;
      async function ask() {
        if (busy) return;
        const text = input.value.trim();
        if (!text) return;

        //set botton disabled if bot is processing
        busy = true;
        send.disabled = true;
        input.disabled = true;

        input.value = '';
        addMsg(text, 'user');
        addMsg('Typing...', 'bot');
        const typingEl = msgs.lastChild;

        //set button enables after response
        send.disabled = false;
        input.disabled = false;
        busy = false;

        try {
          const r = await fetch('<?= SITE_URL ?>/support_bot.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({
              message: text
            })
          });
          const j = await r.json();
          typingEl.remove();
          addMsg(j.reply || j.error || 'Error', 'bot');
        } catch (e) {
          typingEl.remove();
          addMsg('Bot is not available right now.', 'bot');
        }
      }

      btn.addEventListener('click', toggle);
      close.addEventListener('click', () => box.style.display = 'none');
      send.addEventListener('click', ask);
      input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') ask();
      });
    })();
  </script>
<?php endif; ?>
</body>

</html>