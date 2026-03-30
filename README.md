# VICIS ProDash V2

<div align="center">

![Laravel](https://img.shields.io/badge/Laravel-12.0-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![Vue.js](https://img.shields.io/badge/Vue.js-3.5-4FC08D?style=for-the-badge&logo=vue.js&logoColor=white)
![TypeScript](https://img.shields.io/badge/TypeScript-5.2-3178C6?style=for-the-badge&logo=typescript&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/Tailwind-4.1-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white)

A comprehensive production dashboard web application for manufacturing process management.

[Features](#-features) • [Tech Stack](#-tech-stack) • [Installation](#-installation) • [Usage](#-usage) • [Documentation](#-documentation)

</div>

---

## Overview

**VICIS ProDash V2** is a modern Single Page Application (SPA) for real-time monitoring, data entry, and analytics for production operations. Built with Laravel + Inertia.js + Vue.js with type-safe routing.

---

## Features

### Fully Implemented

| Module                         | Description                                                             |
| ------------------------------ | ----------------------------------------------------------------------- |
| **Home Dashboard**             | Hexagonal navigation grid with animated background                      |
| **Endtime Dashboard**          | Production forecasting with metrics, charts, and data tables            |
| **Lot Request**                | FIFO, LIPAS and urgent lot request management                           |
| **Process WIP**                | WIP management with statistics and visualizations                       |
| **Machine Allocation**         | Equipment capacity and allocation management                            |
| **MEMS Dashboard**             | Real-time machine utilization and endtime monitoring                    |
| **Machine Entry**              | Standalone entry page for legacy IE6 machines                           |
| **Data Entry**                 | 5 data entry forms with Excel template downloads                        |
| **Endline Delay (Data Entry)** | Multi-row lot entry with MES lookup, defect code autocomplete           |
| **QC Analysis Monitoring**     | Real-time QC analysis delay monitoring with charts and status tracking  |
| **VI Technical Monitoring**    | VI technical verification monitoring with flow-through from QC Analysis |
| **QC OK Monitoring**           | QC OK result monitoring with production flow status cards               |
| **QC Defect Class**            | Defect code master data management                                      |
| **Admin Panel**                | User management, roles, permissions, system settings                    |
| **User Settings**              | Profile, password, 2FA, appearance                                      |
| **Authentication**             | Employee ID-based login with 2FA support                                |
| **Error Pages**                | Custom error pages (401, 403, 404, 419, 429, 500, 503)                  |

### Endline Monitoring System (Completed)

The Endline Monitoring System tracks lots through the QC inspection flow:

```
LOT WEIGHING
    └── QC INSPECTION
            ├── QC NG → QC ANALYSIS (Mold, Reli, Dipping, etc.)
            │       ├── Proceed → OUTPUT
            │       └── Rework / DRB Approval → VI TECHNICAL
            │               ├── Proceed → OUTPUT
            │               └── Rework / DRB Approval → MC REWORK
            └── QC OK → REAL NG / Yield → OUTPUT / MC REWORK
```

**QC Analysis page** — monitors lots pending QC analysis decision.
**VI Technical page** — monitors lots pending VI technical verification (including lots handed off from QC Analysis via Rework/DRB Approval).
**QC OK page** — monitors lots with QC OK result, tracking production flow status.

---

## Tech Stack

### Backend

| Technology        | Version | Purpose              |
| ----------------- | ------- | -------------------- |
| PHP               | ^8.2    | Server-side language |
| Laravel           | ^12.0   | PHP Framework        |
| Inertia.js        | ^2.1    | SPA adapter          |
| Laravel Fortify   | ^1.30   | Authentication       |
| Laravel Wayfinder | ^0.1.9  | Type-safe routing    |

### Frontend

| Technology      | Version  | Purpose              |
| --------------- | -------- | -------------------- |
| Vue.js          | ^3.5.13  | Frontend framework   |
| TypeScript      | ^5.2.2   | Type-safe JavaScript |
| Tailwind CSS    | ^4.1.11  | Styling              |
| Vite            | ^7.0.4   | Build tool           |
| ApexCharts      | ^5.3.6   | Charts               |
| Lucide Vue Next | ^0.468.0 | Icons                |

---

## Installation

### Prerequisites

- PHP 8.2+, Composer, Node.js 18+, npm, SQLite or MySQL

### Setup

```bash
git clone https://github.com/Gibz-Coder/ProDash-v2.git
cd ProDash-v2
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate
npm run build
```

---

## Usage

```bash
# Development
composer dev

# Production build
npm run build

# Run specific migration
php artisan migrate --path=database/migrations/<filename>.php

# Scheduled tasks
php artisan schedule:work
```

---

## User Roles & Permissions

| Role      | Key Permissions                                                      |
| --------- | -------------------------------------------------------------------- |
| Admin     | All permissions including Delete Endline                             |
| Manager   | Manage Endline, Delete Endline                                       |
| Moderator | Manage Endline, Delete Endline                                       |
| QC Part   | Manage Endline (access to Endline, QC Analysis, VI Technical, QC OK) |
| User      | Manage Endline, Endtime Manage                                       |

### Endline-specific Permissions

- `Manage Endline` — access to all endline pages and API
- `Delete Endline` — delete endline records (Admin/Manager/Moderator only)

---

## Build Progress

| Category                            | Progress |
| ----------------------------------- | -------- |
| Core Infrastructure                 | 100%     |
| Authentication & Security           | 100%     |
| Endtime / Lot / WIP / MC Dashboards | 100%     |
| Endline Monitoring System           | 100%     |
| Admin Features                      | 100%     |
| Data Entry                          | 100%     |
| UI Components                       | 100%     |
| API Endpoints                       | 100%     |

---

## Recent Changes (March 2026)

- Endline Monitoring System fully implemented (QC Analysis, VI Technical, QC OK pages)
- Lot flow: QC Analysis → VI Technical handoff via Rework/DRB Approval
- Auto-refresh control with countdown ring in all monitor pages
- Clickable status cards (Pending/In Progress/Completed/Prev Day) as filters
- Column sort on all raw data tables
- Search across all dates (drops date filter when searching)
- `Delete Endline` permission added and gated on delete route
- QC Part role access fixed for QC Analysis, VI Technical, QC OK pages
- Chart bucketing by `qc_result` content (Main/RR/LY) for both pages
- Column chart shows only defect codes with actual data
- VI Technical includes QC Analysis lots handed off via Rework

---

<div align="center">
VICIS ProDash V2 — Built with Laravel & Vue.js
</div>
