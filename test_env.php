<?php
include "ai.php";
global $OPENAI_API_KEY;

if (empty($OPENAI_API_KEY)) {
  echo "<h3 style='color:red'>❌ API key not loaded</h3>";
} else {
  echo "<h3 style='color:green'>✅ API key loaded: " . substr($OPENAI_API_KEY, 0, 8) . "********</h3>";
}
