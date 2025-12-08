# VICIS PRODASH V2 - Complete Documentation

## Table of Contents
1. [Overview](#overview)
2. [Technology Stack](#technology-stack)
3. [Architecture](#architecture)
4. [Features](#features)
5. [User Roles & Permissions](#user-roles--permissions)
6. [Dashboard Modules](#dashboard-modules)
7. [Data Models](#data-models)
8. [API Endpoints](#api-endpoints)
9. [Authentication & Security](#authentication--security)
10. [Configuration](#configuration)

---

## Overview

VICIS PRODASH V2 is a comprehensive production dashboard web application designed for manufacturing process management. It provides real-time monitoring, data entry, and analytics for production operations including:

- **Endtime Forecasting** - Lot track-out prediction per cutoff ✅ **IMPLEMENTED**
- **WIP Management** - Work-in-Progress visual summary ✅ **IMPLEMENTED**
- **Machine Allocation** - Equipment capacity management ✅ **IMPLEMENTED**
- **Process KPI** - Real-time process performance metrics 🚧 **IN PROGRESS**
- **Lot Request** - FIFO, LIPAS and urgent lot management 🚧 **IN PROGRESS**
- **Endline Management** - Visual endline WIP management 🚧 **IN PROGRESS**
- **Escalation Management** - Machine escalation request/response tracking 🚧 **IN PROGRESS**

The application is built as a Single Page Application (SPA) using Laravel + Inertia.js + Vue.js architecture with Laravel Wayfinder for type-safe routing.

---

## Technology Stack

### Backend
| Technology | Version | Purpose |
|------------|---------|---------|
| PHP | ^8.2 | Server-side language |
| Laravel | ^12.0 | PHP Framework |
| Inertia.js | ^2.1 | SPA adapter |
| Laravel Fortify | ^1.30 | Authentication scaffolding |
| Laravel Wayfinder | ^0.1.9 | Type-safe routing |
| SQLite/MySQL | - | Database |

### Frontend
| Technology | Version | Purpose |
|------------|---------|---------|
| Vue.js | ^3.5.13 | Frontend framework |
| TypeScript | ^5.2.2 | Type-safe JavaScript |
| Tailwind CSS | ^4.1.11 | Utility-first CSS |
| Vite | ^7.0.4 | Build tool |
| ApexCharts | ^5.3.6 | Interactive charts |
| ECharts | ^6.0.0 | Advanced visualizations |
| Lucide Vue Next | ^0.468.0 | Icon library |
| Reka UI | ^2.4.1 | UI component primitives |
| VueUse | ^12.8.2 | Vue composition utilities |
| Class Variance Authority | ^0.7.1 | Component variants |

### Development Tools
- ESLint + Prettier for code formatting
- Vue TSC for TypeScript checking
- Laravel Pint for PHP code style

---

## Architecture

### Directory Structure

```
ProDash-v2/
├── app/
│   ├── Actions/Fortify/          # Authentication actions
│   ├── Console/Commands/         # Artisan commands
│   ├── Enums/                    # PHP enumerations
│   ├── Http/
│   │   ├── Controllers/          # Request handlers
│   │   │   ├── Admin/            # Admin controllers
│   │   │   └── Settings/         # User settings controllers
│   │   ├── Middleware/           # Request middleware
│   │   └── Requests/             # Form requests
│   ├── Models/                   # Eloquent models
│   └── Providers/                # Service providers
├── resources/
│   ├── css/                      # Stylesheets
│   ├── js/
│   │   ├── actions/              # Wayfinder actions
│   │   ├── components/           # Vue components
│   │   │   └── ui/               # UI component library
│   │   ├── composables/          # Vue composables
│   │   ├── layouts/              # Page layouts
│   │   ├── pages/                # Page components
│   │   │   ├── Admin/            # Admin pages
│   │   │   ├── auth/             # Authentication pages
│   │   │   ├── dashboards/       # Dashboard pages
│   │   │   ├── error/            # Error pages
│   │   │   └── settings/         # Settings pages
│   │   ├── routes/               # Frontend route definitions
│   │   └── types/                # TypeScript definitions
│   └── views/                    # Blade templates
├── routes/
│   ├── web.php                   # Main routes
│   ├── admin.php                 # Admin routes
│   └── settings.php              # Settings routes
└── database/
    ├── migrations/               # Database migrations
    └── seeders/                  # Database seeders
```

### Request Flow

```
Browser Request
    ↓
Laravel Router (routes/web.php)
    ↓
Middleware (Auth, Permission, etc.)
    ↓
Controller
    ↓
Inertia::render()
    ↓
Vue.js Page Component
    ↓
HTML Response
```

---

## Features

### 1. Home Dashboard ✅ **IMPLEMENTED**
- Hexagonal navigation grid with animated background
- Quick access to all major modules
- Visual honeycomb design with hover effects
- 7 dashboard modules accessible from home:
  - Endtime Dashboard
  - Lot Request
  - WIP Management
  - Process KPI
  - Machine Allocation
  - Endline Management
  - Machine Escalation

### 2. Endtime Dashboard ✅ **IMPLEMENTED**
The main production forecasting dashboard with:

**Metrics Cards:**
- Target Capacity - Equipment capacity based on OEE
- Total Endtime - Forecasted lot completion quantity
- Submitted Lots - Completed/submitted lots
- Remaining Lots - Ongoing lots pending completion

**Filters:**
- Date picker
- Shift selector (DAY/NIGHT/ALL)
- Cutoff selector (1ST/2ND/3RD/ALL)
- Work Type (NORMAL/REWORK/ALL)
- Line filter (A-K/ALL)
- Auto-refresh toggle with configurable interval

**Charts:**
- Production Overview - Mixed bar/area chart by line
- Machine Utilization - Radial gauge chart

**Data Tables:**
- Per Line Summary (Target, Endtime, Submitted, percentages)
- Per Size Summary (0603, 1005, 1608, 2012, 3216, 3225)

**Modals:**
- Equipment List (with/without ongoing lots)
- Remaining Lots List
- Lot Submission Form

### 3. Process WIP Dashboard ✅ **IMPLEMENTED**
Work-in-Progress management with:
- Statistics cards (Normal Lots, Process Rework, Warehouse, Outgoing NG)
- WIP breakdown bar chart by size
- WIP category donut chart (Standby, Ongoing, Endline)
- Lot list data table with search and filtering

### 4. Machine Allocation Dashboard ✅ **IMPLEMENTED**
Equipment capacity and allocation management with:
- Equipment list with status tracking
- OEE capacity management
- Work type allocation (NORMAL/REWORK)
- Line-based filtering (A-K)
- Size-based filtering (0603-3225)
- Equipment edit and delete functionality
- Export to Excel capability
- Real-time ongoing lot tracking

### 5. Data Entry Module ✅ **IMPLEMENTED**
Restricted to non-user roles, provides:
- WIP Trend Entry
- Result Entry
- Trackout Entry
- Monthly Plan Entry
- Derive Lot Entry
- Excel template downloads

### 6. Lot Request Dashboard 🚧 **IN PROGRESS**
FIFO, LIPAS and urgent lot management (placeholder implemented)

### 7. Process KPI Dashboard 🚧 **IN PROGRESS**
Real-time process KPI monitoring (placeholder implemented)

### 8. Endline Management Dashboard 🚧 **IN PROGRESS**
Visual endline WIP management (placeholder implemented)

### 9. Machine Escalation Dashboard 🚧 **IN PROGRESS**
Machine escalation request and response tracking (placeholder implemented)

### 10. Admin Panel ✅ **IMPLEMENTED**

**User Management:**
- Create/Edit/Delete users
- Role assignment
- Account activation/deactivation
- Search and filter users
- Pagination

**Role Management:**
- Create custom roles
- Permission assignment
- System roles (Admin, Manager, Moderator, User)

**System Settings:**
- Maintenance mode toggle
- Cache management
- Application optimization
- Log viewer and management
- Session timeout configuration
- Database backup management

### 11. User Settings ✅ **IMPLEMENTED**
- Profile information update
- Password change
- Two-factor authentication (TOTP)
- Appearance/theme settings (Light/Dark/System)
- Account deletion

---

## User Roles & Permissions

### Default Roles

| Role | Description |
|------|-------------|
| Admin | Full system access |
| Manager | Management functions |
| Moderator | Content moderation |
| User | Basic access (restricted from Data Entry) |

### Available Permissions

| Permission | Description |
|------------|-------------|
| Employees Create | Create new employees |
| Employees Read | View employee data |
| Employees Update | Modify employee data |
| Employees Delete | Remove employees |
| Employees Manage | Full employee management |
| Roles Manage | Manage roles & permissions |
| Reports View | View reports |
| Reports Generate | Generate reports |
| Settings Manage | System settings access |
| Audit View | View audit logs |
| Departments Manage | Manage departments |
| Positions Manage | Manage positions |

---

## Dashboard Modules

### Main Dashboards (`/resources/js/pages/dashboards/main/`)

| File | Route | Status | Description |
|------|-------|--------|-------------|
| `endtime.vue` | `/endtime` | ✅ | Production endtime forecasting |
| `process-wip.vue` | `/process-wip` | ✅ | WIP management |
| `mc-allocation.vue` | `/mc-allocation` | ✅ | Machine allocation |
| `data-entry.vue` | `/data-entry` | ✅ | Data entry portal |
| `dashboard-1.vue` | `/dashboard-1` | 🚧 | Lot Request (placeholder) |
| `dashboard-5.vue` | `/dashboard-5` | 🚧 | Process KPI (placeholder) |
| `endline.vue` | `/endline` | 🚧 | Endline management (placeholder) |
| `escalation.vue` | `/escalation` | 🚧 | Machine escalation (placeholder) |
| `dashboard-2.vue` | `/dashboard-2` | 🚧 | Reserved |
| `dashboard-3.vue` | `/dashboard-3` | 🚧 | Reserved |
| `dashboard-7.vue` | `/dashboard-7` | 🚧 | Reserved |
| `template.vue` | - | - | Template for new dashboards |

### Sub-components (`/resources/js/pages/dashboards/subs/`)

| Component | Status | Purpose |
|-----------|--------|---------|
| `endtime-add.vue` | ✅ | Add new endtime entry page |
| `endtime-add-modal.vue` | ✅ | Modal for adding entries |
| `endtime-submit.vue` | ✅ | Submit lot completion page |
| `endtime-submit-modal.vue` | ✅ | Submission modal |
| `endtime-ranking.vue` | ✅ | Performance ranking |
| `endtime-wipupdate-modal.vue` | ✅ | WIP update modal |
| `equipment-modal.vue` | ✅ | Equipment list modal |
| `remaining-lots-modal.vue` | ✅ | Remaining lots display |
| `mc-allocation-edit-modal.vue` | ✅ | Machine allocation edit modal |
| `mc-allocation-view-modal.vue` | ✅ | Machine allocation view modal |
| `wip-trend-update-modal.vue` | ✅ | WIP trend data entry |
| `process-result-modal.vue` | ✅ | Process result entry |
| `process-trackout-modal.vue` | ✅ | Trackout data entry |
| `monthly-plan-modal.vue` | ✅ | Monthly plan entry |

---

## Data Models

### User Model
```php
// Fields
- emp_no (Employee Number - Primary identifier)
- emp_name (Full Name)
- role (Role slug)
- position
- title_class
- rank
- hr_job_name
- job_assigned
- emp_verified_at
- password
- avatar
```

### Endtime Model
```php
// Core Fields
- lot_id (Lot identifier)
- lot_qty (Quantity)
- lot_size (03, 05, 10, 21, 31, 32)
- lot_type (MAIN, WL/RW, RL/LY)
- model_15 (Model code)
- lipas_yn (Y/N flag)
- est_endtime (Estimated completion)
- actual_endtime (Actual completion)
- actual_submitted_at (Submission timestamp)
- work_type (NORMAL, REWORK)
- eqp_line (A-K)
- eqp_area
- status (Ongoing, Submitted)
- remarks (OK, EARLY, LATE)
- submission_notes

// Equipment Fields (1-10)
- eqp_1 to eqp_10
- ng_percent_1 to ng_percent_10
- start_time_1 to start_time_10

// Tracking
- no_rl_enabled
- no_rl_minutes
- modified_by
```

### Equipment Model ✅
```php
- eqp_no (Equipment number)
- eqp_status (OPERATIONAL, etc.)
- eqp_line (A-K)
- eqp_area
- size (03, 05, 10, 21, 31, 32)
- alloc_type (Work type allocation)
- oee_capa (OEE capacity)
- ongoing_lot (Current lot being processed)
- modified_by
```

### UpdateWip Model
```php
- lot_id
- model_15
- lot_size
- lot_qty
- stagnant_tat
- qty_class
- work_type
- wip_status
- lot_status
- hold (Y/N)
- auto_yn
- lipas_yn
- eqp_type
- eqp_class
- lot_location
- lot_code
- modified_by
```

### Role Model
```php
- name
- slug
- description
- permissions (JSON array)
```

---

## API Endpoints

### Authentication Routes
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/login` | Login page |
| POST | `/login` | Authenticate |
| POST | `/logout` | Logout |
| GET | `/register` | Registration page |
| POST | `/register` | Create account |
| GET | `/forgot-password` | Password reset request |
| POST | `/forgot-password` | Send reset link |
| GET | `/reset-password/{token}` | Reset password form |
| POST | `/reset-password` | Update password |

### Dashboard Routes
| Method | Endpoint | Status | Description |
|--------|----------|--------|-------------|
| GET | `/dashboard` | ✅ | Home dashboard |
| GET | `/endtime` | ✅ | Endtime dashboard |
| GET | `/process-wip` | ✅ | Process WIP dashboard |
| GET | `/mc-allocation` | ✅ | Machine allocation dashboard |
| GET | `/data-entry` | ✅ | Data entry portal (role-restricted) |
| GET | `/dashboard-1` | 🚧 | Lot Request (placeholder) |
| GET | `/dashboard-5` | 🚧 | Process KPI (placeholder) |
| GET | `/endline` | 🚧 | Endline management (placeholder) |
| GET | `/escalation` | 🚧 | Escalation dashboard (placeholder) |
| GET | `/endtime-ranking` | ✅ | Endtime ranking page |
| GET | `/endtime-add` | ✅ | Endtime lot entry page |
| GET | `/endtime-submit` | ✅ | Endtime lot submission page |

### Endtime API Routes ✅
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/endtime/equipment-list` | Get equipment list |
| GET | `/endtime/remaining-lots-list` | Get remaining lots |
| GET | `/endtime/export` | Export data to Excel |
| POST | `/endtime/store` | Create entry |
| PUT | `/endtime/{id}` | Update entry |
| DELETE | `/endtime/{id}` | Delete entry |
| POST | `/endtime/{id}/submit` | Submit lot |
| GET | `/keep-alive` | Session keep-alive |

### Machine Allocation API Routes ✅
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/mc-allocation` | Get machine allocation list |
| GET | `/mc-allocation/export` | Export data to Excel |
| PUT | `/mc-allocation/{id}` | Update allocation |
| DELETE | `/mc-allocation/{id}` | Delete allocation |
| GET | `/mc-allocation/reference-data` | Get reference data |

### Process WIP API Routes ✅
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/process-wip` | Get WIP data |
| GET | `/process-wip/export` | Export data to Excel |

### Lookup API Routes
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/updatewip/lookup` | Lookup lot info |
| GET | `/api/equipment/search` | Search equipment |
| GET | `/api/equipment/lookup` | Lookup equipment |
| GET | `/api/employee/search` | Search employee |
| GET | `/api/employee/lookup` | Lookup employee |
| GET | `/api/endtime/lookup-ongoing` | Lookup ongoing lot |

### Data Entry Routes ✅
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/updatewip` | Store WIP data |
| GET | `/updatewip/download-template` | Download Excel template |
| POST | `/wip-trend/update` | Update WIP trend |
| GET | `/wip-trend/download-template` | Download Excel template |
| POST | `/process-result/update` | Update process result |
| GET | `/process-result/download-template` | Download Excel template |
| POST | `/process-trackout/update` | Update trackout |
| GET | `/process-trackout/download-template` | Download Excel template |
| POST | `/monthly-plan/update` | Update monthly plan |
| GET | `/monthly-plan/download-template` | Download Excel template |

### Admin API Routes ✅
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/api/users` | List users |
| POST | `/admin/api/users` | Create user |
| GET | `/admin/api/users/{user}` | Get user |
| PUT | `/admin/api/users/{user}` | Update user |
| PATCH | `/admin/api/users/{user}/toggle-status` | Toggle status |
| DELETE | `/admin/api/users/{user}` | Delete user |
| GET | `/admin/api/roles` | List roles |
| POST | `/admin/api/roles` | Create role |
| PUT | `/admin/api/roles/{role}` | Update role |
| DELETE | `/admin/api/roles/{role}` | Delete role |
| GET | `/admin/api/settings` | Get settings |
| POST | `/admin/api/settings/maintenance` | Toggle maintenance |
| POST | `/admin/api/settings/cache-clear` | Clear cache |
| POST | `/admin/api/settings/optimize` | Optimize app |
| GET | `/admin/api/settings/logs` | Get log files |
| POST | `/admin/api/settings/backup` | Create backup |

### Test Routes (Local Environment Only)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/test-errors/401` | Test 401 error page |
| GET | `/test-errors/403` | Test 403 error page |
| GET | `/test-errors/404` | Test 404 error page |
| GET | `/test-errors/419` | Test 419 error page |
| GET | `/test-errors/429` | Test 429 error page |
| GET | `/test-errors/500` | Test 500 error page |
| GET | `/test-errors/503` | Test 503 error page |
| GET | `/test-errors/maintenance` | Test maintenance page |

### Settings Routes ✅
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/settings/profile` | Profile settings |
| PATCH | `/settings/profile` | Update profile |
| DELETE | `/settings/profile` | Delete account |
| GET | `/settings/password` | Password settings |
| PUT | `/settings/password` | Update password |
| GET | `/settings/appearance` | Appearance settings |
| GET | `/settings/two-factor` | 2FA settings |
| POST | `/settings/two-factor` | Enable 2FA |
| DELETE | `/settings/two-factor` | Disable 2FA |

---

## Authentication & Security

### Authentication Features
- Employee ID-based login (emp_no)
- Password hashing with Laravel's built-in hasher
- Session-based authentication
- Two-factor authentication (TOTP)
- Password reset via email
- Account verification

### Middleware
| Middleware | Purpose |
|------------|---------|
| `auth` | Requires authentication |
| `verified` | Requires email verification |
| `permission:{name}` | Requires specific permission |
| `role:{name}` | Requires specific role |

### Security Features
- CSRF protection
- XSS prevention
- SQL injection protection via Eloquent ORM
- Rate limiting on sensitive endpoints
- Session timeout configuration
- Maintenance mode for system updates

---

## Configuration

### Environment Variables (.env)
```env
APP_NAME="VICIS PRODASH V2"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=sqlite
# or
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=prodash
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
SESSION_LIFETIME=120
```

### Shift & Cutoff Schedule

| Shift | Cutoff | Time Range |
|-------|--------|------------|
| DAY | 1ST | 07:00 - 11:59 |
| DAY | 2ND | 12:00 - 15:59 |
| DAY | 3RD | 16:00 - 18:59 |
| NIGHT | 1ST | 19:00 - 23:59 |
| NIGHT | 2ND | 00:00 - 03:59 |
| NIGHT | 3RD | 04:00 - 06:59 |

### Size Codes

| Code | Display Name |
|------|--------------|
| 03 | 0603 |
| 05 | 1005 |
| 10 | 1608 |
| 21 | 2012 |
| 31 | 3216 |
| 32 | 3225 |

### Production Lines
Lines A through K (11 production lines)

---

## Development Commands

```bash
# Install dependencies
composer install
npm install

# Initial setup (includes key generation, migration, and build)
composer setup

# Run development server (concurrent: Laravel + Queue + Vite)
composer dev

# Run development server with SSR
composer dev:ssr

# Build for production
npm run build

# Build with SSR support
npm run build:ssr

# Run tests
composer test

# Code formatting
npm run format          # Format code
npm run format:check    # Check formatting
npm run lint           # Lint and fix

# PHP code style
composer pint

# Database
php artisan migrate
php artisan db:seed

# Wayfinder (generate type-safe routes)
php artisan wayfinder:generate
```

---

## UI Component Library ✅

The application uses a custom UI component library based on Reka UI primitives with Tailwind CSS styling. All components are fully implemented and type-safe:

| Component | Location |
|-----------|----------|
| Alert | `components/ui/alert/` |
| Avatar | `components/ui/avatar/` |
| Badge | `components/ui/badge/` |
| Breadcrumb | `components/ui/breadcrumb/` |
| Button | `components/ui/button/` |
| Card | `components/ui/card/` |
| Checkbox | `components/ui/checkbox/` |
| Collapsible | `components/ui/collapsible/` |
| Dialog | `components/ui/dialog/` |
| Dropdown Menu | `components/ui/dropdown-menu/` |
| Input | `components/ui/input/` |
| Label | `components/ui/label/` |
| Navigation Menu | `components/ui/navigation-menu/` |
| Pin Input | `components/ui/pin-input/` |
| Separator | `components/ui/separator/` |
| Sheet | `components/ui/sheet/` |
| Sidebar | `components/ui/sidebar/` |
| Skeleton | `components/ui/skeleton/` |
| Spinner | `components/ui/spinner/` |
| Tooltip | `components/ui/tooltip/` |

---

## Error Handling ✅

Custom error pages are fully implemented for:
- 401 Unauthorized
- 403 Forbidden
- 404 Not Found
- 419 Page Expired
- 429 Too Many Requests
- 500 Server Error
- 503 Service Unavailable
- Maintenance Mode

All error pages include:
- Consistent branding and styling
- Clear error messages
- Navigation back to home
- Appropriate HTTP status codes

---

## Build Progress Summary

### ✅ Completed Features
1. **Authentication System** - Full Laravel Fortify implementation with 2FA
2. **Home Dashboard** - Hexagonal navigation with animated background
3. **Endtime Dashboard** - Complete forecasting system with charts and data tables
4. **Process WIP Dashboard** - WIP management with visualizations
5. **Machine Allocation Dashboard** - Equipment capacity management
6. **Data Entry Module** - All 5 data entry forms with Excel templates
7. **Admin Panel** - User, role, and system settings management
8. **User Settings** - Profile, password, 2FA, and appearance
9. **UI Component Library** - 20+ reusable components
10. **Error Pages** - All HTTP error pages with custom designs
11. **Type-Safe Routing** - Laravel Wayfinder integration
12. **Database Models** - User, Role, Endtime, Equipment, UpdateWip

### 🚧 In Progress / Placeholder
1. **Lot Request Dashboard** (dashboard-1) - Placeholder implemented
2. **Process KPI Dashboard** (dashboard-5) - Placeholder implemented
3. **Endline Management** (endline) - Placeholder implemented
4. **Machine Escalation** (escalation) - Placeholder implemented
5. **Additional Dashboards** (dashboard-2, dashboard-3, dashboard-7) - Reserved

### 📊 Overall Progress
- **Core Infrastructure**: 100% ✅
- **Authentication & Security**: 100% ✅
- **Main Dashboards**: 60% (3/5 complete)
- **Admin Features**: 100% ✅
- **Data Entry**: 100% ✅
- **UI Components**: 100% ✅

### 🎯 Next Steps
1. Implement Lot Request Dashboard functionality
2. Implement Process KPI Dashboard with real-time metrics
3. Implement Endline Management features
4. Implement Machine Escalation request/response system
5. Add comprehensive testing suite
6. Performance optimization and caching strategies
7. Documentation for API endpoints
8. User training materials

---

## Support

For issues or questions, contact the development team or refer to the internal documentation.

---

*Documentation generated for VICIS PRODASH V2*
*Last updated: December 6, 2025*
*Build Status: Core Features Complete, Additional Dashboards In Progress*
