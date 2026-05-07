<?php
$pageTitle = 'Contact Us — ShareToNeighbour';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="py-3">
  <div class="row justify-content-center">
    <div class="col-lg-9">
      <div class="d-flex flex-column gap-2 mb-4">
        <h1 class="h3 fw-bold mb-0">Contact Us</h1>
        <p class="text-muted mb-0">Have a question, problem, or feedback? We are here to help.</p>
      </div>

      <div class="card shadow-sm border-0">
        <div class="card-body p-4 p-md-5">
          <section class="mb-4">
            <h2 class="h5 fw-semibold mb-2">General Support</h2>
            <p class="text-muted mb-0">
              For questions about listings, your account, or how the platform works:<br>
              <a href="mailto:sharetoneighbour360@gmail.com">support@sharetoneighbour360@gmail.com</a><br>
              <span class="small">We aim to respond within 2 business days.</span>
            </p>
          </section>

          <section class="border-top pt-4 mb-4">
            <h2 class="h5 fw-semibold mb-2">Reporting Abuse or Safety Issues</h2>
            <p class="text-muted mb-3">
              If you need to report a user, a listing, or a safety incident, email us with:
              username, listing link (if applicable), and a short description of what happened.
              We treat reports confidentially.
            </p>

            <div class="alert alert-warning mb-0 small">
              <strong>If you are in immediate danger</strong>, do not wait for our response.
              Contact Danish emergency services: dial <strong>112</strong> (emergency) or <strong>114</strong> (non-emergency police).
            </div>
          </section>

          <section class="border-top pt-4">
            <h2 class="h5 fw-semibold mb-2">Privacy Enquiries</h2>
            <p class="text-muted mb-0">
              For data access/deletion requests or privacy questions:<br>
              <a href="mailto:sharetoneighbour360@gmail.com">sharetoneighbour360@gmail.com</a><br>
              <span class="small">Suggested subject: <em>Privacy Request - [Your Username]</em>. We respond within 30 days.</span>
            </p>
          </section>

          <div class="border-top pt-4 mt-4">
            <p class="text-muted small mb-0">ShareToNeighbour - Copenhagen, Denmark</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>