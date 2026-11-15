<!DOCTYPE html>
<html lang="en">
<?= $this->include('components/head', ['title' => 'Account created — COCOIR']) ?>
<body class="site-store">
<?= $this->include('components/header') ?>
<main class="confirmation-page">
    <div class="confirmation-card" role="status">
        <span class="confirmation-check" aria-hidden="true">✓</span>
        <p class="store-eyebrow">Welcome to COCOIR</p>
        <h1>Account created successfully!</h1>
        <p>You’re all set. Log in with your username or email and password to start shopping and manage your orders.</p>
        <a href="<?= site_url('login') ?>" class="store-button">Proceed to login <span aria-hidden="true">&nbsp;→</span></a>
    </div>
</main>
<?= $this->include('components/footer') ?>
</body>
</html>
