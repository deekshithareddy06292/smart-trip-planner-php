<?php
// index.php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Smart Trip Planner (PHP)</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <style>
    .navbar {
      padding-top: 0.8rem;
      padding-bottom: 0.8rem;
    }
    .navbar .btn {
      font-weight: 500;
    }
    .navbar-brand {
      font-size: 1.1rem;
      font-weight: bold;
      color: #fff !important;
    }
    .btn-active {
      background-color: #0d6efd !important;
      color: #fff !important;
      border: none !important;
    }
  </style>
</head>

<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-dark bg-primary mb-4">
  <div class="container d-flex justify-content-between">

    <!-- Left side: Mode buttons -->
    <div>
      <a href="index.php" class="btn btn-sm me-2 <?= $currentPage == 'index.php' ? 'btn-active' : 'btn-light' ?>">Direct Mode</a>
      <a href="explore.php" class="btn btn-sm <?= $currentPage == 'explore.php' ? 'btn-active' : 'btn-warning' ?>">Explore Mode</a>
    </div>

    <!-- Right side: App title -->
    <span class="navbar-brand">Smart Trip Planner (PHP)</span>
  </div>
</nav>

<!-- Main Form -->
<div class="container">
  <h3 class="mb-3">Direct Trip Planner</h3>

  <form action="planner.php" method="POST" class="row g-3 bg-white p-4 shadow rounded">

    <div class="col-md-6">
      <label class="form-label">From City</label>
      <input type="text" name="from" class="form-control" required placeholder="e.g., Delhi">
    </div>

    <div class="col-md-6">
      <label class="form-label">Destination City</label>
      <input type="text" name="to" class="form-control" required placeholder="e.g., Goa">
    </div>

    <div class="col-md-4">
      <label class="form-label">Days</label>
      <input type="number" name="days" class="form-control" min="1" required value="4">
    </div>

    <div class="col-md-4">
      <label class="form-label">People</label>
      <input type="number" name="people" class="form-control" min="1" required value="2">
    </div>

    <div class="col-md-4">
      <label class="form-label">Budget (INR)</label>
      <input type="number" name="budget" class="form-control" min="1000" required value="30000">
    </div>

    <div class="col-12">
      <label class="form-label">Trip Type</label>
      <select name="type" class="form-select">
        <option value="Adventure">Adventure</option>
        <option value="Family" selected>Family</option>
        <option value="Romantic">Romantic</option>
        <option value="Historical">Historical</option>
      </select>
    </div>

    <div class="col-12">
      <label class="form-label">Transport Preference</label>
      <select name="mode" class="form-select">
        <option value="auto" selected>Auto (suggest best)</option>
        <option value="train">Train</option>
        <option value="bus">Bus</option>
        <option value="flight">Flight</option>
      </select>
    </div>

    <div class="col-12">
      <button class="btn btn-primary w-100">Generate Trip Plan</button>
    </div>

  </form>
</div>

</body>
</html>
