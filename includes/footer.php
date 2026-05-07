</main>

<footer class="footer-premium text-light py-5 mt-5" role="contentinfo" aria-label="Site footer">
  <div class="container">
    <div class="row gy-4">
      <div class="col-md-4">
        <a href="<?= SITE_URL ?>" class="d-inline-flex align-items-center text-decoration-none text-light mb-2">
          <span class="brand-icon footer-icon d-inline-flex align-items-center justify-content-center me-2"><i class="bi bi-house-heart-fill"></i></span>
          <span class="fs-5 fw-semibold"><?= SITE_NAME ?></span>
        </a>
        <p class="text-muted small mb-2">A local platform to share and reuse furniture in Copenhagen.</p>
        <p class="text-muted small mb-0">Listings use location to show nearby items. </p>
      </div>

      <div class="col-md-2">
        <h6 class="text-uppercase small fw-semibold text-white-50">Explore</h6>
        <ul class="list-unstyled small mb-0">
          <li><a href="<?= SITE_URL ?>/browse.php" class="footer-link">Browse</a></li>
          <li><a href="<?= SITE_URL ?>/register.php" class="footer-link">Register</a></li>
          <li><a href="<?= ADMIN_URL ?>/login.php" class="footer-link">Admin</a></li>
        </ul>
      </div>

      <div class="col-md-3">
        <h6 class="text-uppercase small fw-semibold text-white-50">Support & Safety</h6>
        <ul class="list-unstyled small mb-0">
          <li><a href="<?= SITE_URL ?>/safety.php" class="footer-link">Safety tips</a></li>
          <li><a href="<?= SITE_URL ?>/community-guidelines.php" class="footer-link">Community guidelines</a></li>
          <li><a href="<?= SITE_URL ?>/contact.php" class="footer-link">Contact us</a></li>
          
        </ul>
      </div>

      <div class="col-md-3">
        <h6 class="text-uppercase small fw-semibold text-white-50">Legal</h6>
        <ul class="list-unstyled small mb-2">
          <li><a href="<?= SITE_URL ?>/privacy.php" class="footer-link">Privacy</a></li>
          <li><a href="<?= SITE_URL ?>/terms.php" class="footer-link">Terms</a></li>
         
        </ul>
      </div>
    </div>

    <hr class="border-secondary opacity-25 my-4">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center small text-muted">
      <div>&copy; <?= date('Y') ?> <?= SITE_NAME ?> — Copenhagen, Denmark</div>
      <div>Meet in public places. Never share passwords or OTPs.</div>
    </div>
  </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= SITE_URL ?>/js/app.js"></script>
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

    .support-msg p,
    .support-msg ul,
    .support-msg ol {
      margin-bottom: .4rem;
    }

    .support-msg p:last-child,
    .support-msg ul:last-child,
    .support-msg ol:last-child {
      margin-bottom: 0;
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

      function buildBotContent(text) {
        const container = document.createElement('div');
        const blocks = String(text || '').split(/\n\s*\n/);

        blocks.forEach((block) => {
          const lines = block.split(/\n/).map(l => l.trim()).filter(Boolean);
          if (!lines.length) return;

          const isBullet = lines.every(l => /^[-*]\s+/.test(l));
          const isNumbered = lines.every(l => /^\d+[.)]\s+/.test(l));

          if (isBullet || isNumbered) {
            const list = document.createElement(isNumbered ? 'ol' : 'ul');
            list.className = 'ps-3 mb-2';
            lines.forEach((line) => {
              const li = document.createElement('li');
              li.textContent = line.replace(/^[-*]\s+/, '').replace(/^\d+[.)]\s+/, '');
              list.appendChild(li);
            });
            container.appendChild(list);
            return;
          }

          const p = document.createElement('p');
          p.className = 'mb-2';
          p.textContent = lines.join(' ');
          container.appendChild(p);
        });

        return container;
      }

      function addMsg(text, who) {
        const d = document.createElement('div');
        d.className = 'support-msg ' + (who === 'user' ? 'support-user' : 'support-bot');
        if (who === 'bot') {
          d.appendChild(buildBotContent(text));
        } else {
          d.textContent = text;
        }
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