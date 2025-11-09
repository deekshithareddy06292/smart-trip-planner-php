<?php
// Load environment variables
$env = parse_ini_file(__DIR__ . '/.env', true);
$OPENAI_API_KEY = $env['OPENAI_API_KEY'] ?? null;
