# Smart Trip Planner (PHP) — v2

## What's new
- Two input modes:
  - Direct Mode (index.php)
  - Explore Mode (explore.php -> suggest.php -> planner.php)
- Auto transport suggestion and all options (cost + time) shown
- All prices in INR
- Offline PDF export with all transports summary

## Run
1) Ensure PHP or XAMPP is installed
2) Put folder in htdocs (XAMPP) or run:
   php -S localhost:8000
3) Install FPDF: download from http://www.fpdf.org and place at lib/fpdf/fpdf.php

## Files
- index.php          — Direct mode form
- explore.php        — Explore mode filter form
- suggest.php        — Destination suggestions grid
- planner.php        — Dijkstra + Knapsack + itinerary + transport comparison
- download_pdf.php   — PDF export
- lib/fpdf/fpdf.php  — (you add this file)
