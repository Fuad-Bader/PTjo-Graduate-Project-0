<?php
/** PTjo — Customer: read chat messages for an engagement. */
require_once __DIR__ . '/../../config/auth.php';
$user = require_role('customer');
require __DIR__ . '/../_chat_messages_get.php';
