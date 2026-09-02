<?php
$key = bin2hex(random_bytes(24));
echo "LEAD_CAPTURE_KEY={$key}\n";
echo "Add this value to your server environment or config.php.\n";
