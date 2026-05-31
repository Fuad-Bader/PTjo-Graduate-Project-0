<?php
/** PTjo — Customer: send a chat message on an engagement. */
require_once __DIR__ . '/../../config/auth.php';
$user = require_role('customer');
csrf_verify();
require __DIR__ . '/../_chat_messages_send.php';
