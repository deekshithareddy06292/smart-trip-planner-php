<?php
// explore.php — AI-based trip discovery
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Explore Destinations — Smart Trip Planner</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-primary mb-4">
  <div class="container d-flex justify-content-between align-items-center">
    <a class="navbar-brand fw-bold" href="index.php">Smart Trip Planner</a>
    <div>
      <!-- 🔹 Add Direct Mode link -->
      <a href="index.php" class="btn btn-light btn-sm me-2">Direct Mode</a>
    </div>
  </div>
</nav>

<div class="container">
  <div class="card shadow p-4 mx-auto" style="max-width: 750px;">
    <h3 class="mb-3">Explore Mode</h3>
    <form action="suggest.php" method="POST" class="row g-3">
      <div class="col-md-6">
        <label class="form-label">From City</label>
        <input type="text" name="from" class="form-control" placeholder="Enter your city (e.g., Delhi)" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">Budget (INR)</label>
        <input type="number" name="budget" class="form-control" min="5000" value="40000" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">Days</label>
        <input type="number" name="days" class="form-control" min="2" value="4" required>
      </div>

      <div class="col-md-3">
        <label class="form-label">People</label>
        <input type="number" name="people" class="form-control" min="1" value="2" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">Trip Type</label>
        <select name="type" class="form-select">
          <option value="Family" selected>Family</option>
          <option value="Romantic">Romantic</option>
          <option value="Adventure">Adventure</option>
          <option value="Historical">Historical</option>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Region</label>
        <select name="region" class="form-select">
          <option value="India" selected>India</option>
          <option value="South Asia">South Asia</option>
          <option value="SE Asia">South-East Asia</option>
        </select>
      </div>

      <div class="col-12">
        <button class="btn btn-warning w-100 fw-semibold">Show Suggested Destinations</button>
      </div>
    </form>
  </div>
</div>

</body>
</html>
