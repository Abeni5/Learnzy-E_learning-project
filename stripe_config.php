<?php
// Stripe configuration
require_once 'vendor/autoload.php';

// Replace with your actual Stripe secret key
$stripeSecretKey = 'your_stripe_secret_key';
$stripePublicKey = 'your_stripe_public_key';

\Stripe\Stripe::setApiKey($stripeSecretKey);
?>
