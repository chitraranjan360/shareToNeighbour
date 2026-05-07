<?php
$pageTitle = 'Privacy Policy — ShareToNeighbour';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="py-3">
  <div class="row justify-content-center">
    <div class="col-lg-9">
      <div class="d-flex flex-column gap-2 mb-4">
        <h1 class="h3 fw-bold mb-0">Privacy Policy</h1>
        <p class="text-muted mb-0">Last updated: May 2026</p>
      </div>

      <div class="card shadow-sm border-0">
        <div class="card-body p-4 p-md-5">
          <p class="lead text-muted mb-4">
            ShareToNeighbour is committed to handling your personal data responsibly and transparently.
            This policy explains what we collect, why, and how long we keep it.
          </p>

          <section class="mb-4">
            <h2 class="h5 fw-semibold mb-3">1. Data We Collect</h2>

            <div class="mb-3">
              <h3 class="h6 fw-semibold">Account Data</h3>
              <ul class="list-group list-group-flush small">
                <li class="list-group-item px-0">Full name, email address, password (stored as a secure hash - never in plain text), and your saved address including latitude and longitude coordinates.</li>
              </ul>
            </div>

            <div class="mb-3">
              <h3 class="h6 fw-semibold">Listing Content</h3>
              <ul class="list-group list-group-flush small">
                <li class="list-group-item px-0">Item title, description, category, condition, status, and up to 3 uploaded photos per listing.</li>
              </ul>
            </div>

            <div class="mb-3">
              <h3 class="h6 fw-semibold">Requests & Transactions</h3>
              <ul class="list-group list-group-flush small">
                <li class="list-group-item px-0">Records of requests you send or receive, including acceptance status and transaction timestamps.</li>
              </ul>
            </div>

            <div class="mb-3">
              <h3 class="h6 fw-semibold">Reviews</h3>
              <ul class="list-group list-group-flush small">
                <li class="list-group-item px-0">Star rating (1-5), optional written comment, and optional photo submitted after a completed exchange.</li>
              </ul>
            </div>

            <div>
              <h3 class="h6 fw-semibold">Usage Logs</h3>
              <ul class="list-group list-group-flush small">
                <li class="list-group-item px-0">Basic server logs (IP address, browser type, page visits) for security and platform maintenance.</li>
              </ul>
            </div>
          </section>

          <section class="border-top pt-4 mb-4">
            <h2 class="h5 fw-semibold mb-3">2. Why We Collect It and Our Lawful Basis</h2>
            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Purpose</th>
                    <th>Lawful Basis</th>
                  </tr>
                </thead>
                <tbody>
                  <tr><td>Creating and managing your account</td><td>Contract performance</td></tr>
                  <tr><td>Showing nearby listings (~1 km radius)</td><td>Contract performance</td></tr>
                  <tr><td>Enabling messaging, requests, and reviews</td><td>Contract performance</td></tr>
                  <tr><td>Preventing abuse, spam, and fraud</td><td>Legitimate interests</td></tr>
                  <tr><td>Complying with legal obligations</td><td>Legal obligation</td></tr>
                </tbody>
              </table>
            </div>
          </section>

          <section class="border-top pt-4 mb-4">
            <h2 class="h5 fw-semibold mb-3">3. How Long We Keep Your Data</h2>
            <ul class="list-group list-group-flush small">
              <li class="list-group-item px-0"><strong>Active account data</strong> - kept for as long as your account exists.</li>
              <li class="list-group-item px-0"><strong>Listings marked as "taken"</strong> - soft-deleted and hidden from public view, but retained internally to preserve transaction history, reviews, and admin audit records.</li>
              <li class="list-group-item px-0"><strong>Reviews</strong> - retained permanently as part of the transaction record.</li>
              <li class="list-group-item px-0"><strong>Usage logs</strong> - kept for up to 90 days, then deleted.</li>
              <li class="list-group-item px-0">If you delete your account, your personal details are removed. Transaction records and reviews may be retained in anonymised form to maintain platform integrity.</li>
            </ul>
          </section>

          <section class="border-top pt-4 mb-4">
            <h2 class="h5 fw-semibold mb-3">4. Security Measures</h2>
            <ul class="list-group list-group-flush small">
              <li class="list-group-item px-0">Passwords are stored using secure one-way hashing (bcrypt).</li>
              <li class="list-group-item px-0">All form inputs are validated and sanitised to prevent injection attacks.</li>
              <li class="list-group-item px-0">Uploaded files are type-checked and size-restricted.</li>
              <li class="list-group-item px-0">Access to admin functions is protected by separate privileged accounts.</li>
            </ul>
          </section>

          <section class="border-top pt-4 mb-4">
            <h2 class="h5 fw-semibold mb-2">5. Your Rights</h2>
            <p class="text-muted mb-0">
              You have the right to access, correct, or request deletion of your personal data. To exercise any of these rights,
              contact us at the address below. We will respond within 30 days.
            </p>
          </section>

          <section class="border-top pt-4">
            <h2 class="h5 fw-semibold mb-2">6. Contact</h2>
            <p class="text-muted mb-0">
              For privacy-related questions: <a href="mailto:support@sharetoneighbour.dk">support@sharetoneighbour.dk</a>
            </p>
          </section>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>