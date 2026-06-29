<?php

ini_set('display_errors', '1');
error_reporting(E_ALL);

echo "Step 1: loading config...\n";
$config = require __DIR__ . '/../config/config.php';
echo "Step 1 OK\n";

echo "Step 2: requiring WhatsAppClient...\n";
require_once __DIR__ . '/../app/services/WhatsAppClient.php';
echo "Step 2 OK\n";

echo "Step 3: requiring payment clients...\n";
require_once __DIR__ . '/../app/services/payments/ZenoPayClient.php';
require_once __DIR__ . '/../app/services/payments/SnippeClient.php';
require_once __DIR__ . '/../app/services/payments/HarakaPayClient.php';
echo "Step 3 OK\n";

echo "Step 4: requiring DeepSeekClient...\n";
require_once __DIR__ . '/../app/services/DeepSeekClient.php';
echo "Step 4 OK\n";

echo "Step 5: requiring models...\n";
require_once __DIR__ . '/../app/models/Session.php';
require_once __DIR__ . '/../app/models/Service.php';
require_once __DIR__ . '/../app/models/Order.php';
require_once __DIR__ . '/../app/models/Payment.php';
require_once __DIR__ . '/../app/models/Customer.php';
echo "Step 5 OK\n";

echo "Step 6: requiring controller + instantiating...\n";
require_once __DIR__ . '/../app/services/OrderFulfillment.php';
require_once __DIR__ . '/../app/services/AdminNotifier.php';
require_once __DIR__ . '/../app/helpers/MainMenu.php';
require_once __DIR__ . '/../app/controllers/WebhookController.php';
$controller = new WebhookController($config);
echo "Step 6 OK\n";

echo "ALL GOOD\n";
