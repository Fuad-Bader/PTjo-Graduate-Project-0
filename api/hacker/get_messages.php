<?php
/** PTjo — Hacker: read chat messages for an engagement. */
require_once __DIR__ . '/../../config/auth.php';
$user = require_role('hacker');
require __DIR__ . '/../_chat_messages_get.php';
