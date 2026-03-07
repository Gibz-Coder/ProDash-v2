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

## 📋 Overview

**VICIS ProDash V2** is a modern Single Page Application (SPA) designed for real-time monitoring, data entry, and analytics for production operations. Built with Laravel + Inertia.js + Vue.js architecture with type-safe routing.

### Key Capabilities

- ⏳ **Endtime Forecasting** - Lot track-out prediction per cutoff
- 🛒 **Lot Request Management** - FIFO, LIPAS and urgent lot management
- 📦 **WIP Management** - Work-in-Progress visual summary
- ⚙️ **Machine Allocation** - Equipment capacity management
- 📊 **MEMS Dashboard** - Machine & Endtime Monitoring System with real-time tracking
- 🔚 **Endline Management** - Visual endline WIP management
- 🛠️ **Machine Escalation** - Machine escalation tracking
- 📋 **Data Entry** - Multiple data entry forms with Excel template downloads

---

## ✨ Features

### Fully Implemented ✅

| Module                      | Description                                                       |
| --------------------------- | ----------------------------------------------------------------- |
| **Home Dashboard**          | Hexagonal navigation grid with animated background                |
| **Endtime Dashboard**       | Production forecasting with metrics, charts, and data tables      |
| **Endtime Add/Submit**      | Lot entry and submission with validation                          |
| **Endtime Ranking**         | Performance ranking and analytics                                 |
| **Lot Request**             | Complete FIFO, LIPAS and urgent lot request management            |
| **Process WIP**             | WIP management with statistics and visualizations                 |
| **Machine Allocation**      | Equipment capacity and allocation management with CRUD operations |
| **MEMS Dashboard**          | Real-time machine utilization and endtime monitoring              |
| **Machine Entry**           | Standalone entry page for legacy IE6 machines                     |
| **Data Entry**              | 5 data entry forms with Excel template downloads                  |
| **Admin Panel**             | User management, roles, permissions, and system settings          |
| **User Settings**           | Profile, password, 2FA, and appearance customization              |
| **Authentication**          | Employee ID-based login with 2FA support                          |
| **Error Pages**             | Custom error pages (401, 403, 404, 419, 429, 500, 503)            |
| **Maintenance Mode**        | Custom maintenance page with retry information                    |

### In Development 🚧

| Module                  | Status                                    |
| ----------------------- | ----------------------------------------- |
| **Endline Management**  | UI designed, integration pending          |
| **Machine Escalation**  | Framework established, features in progress |
| **Process KPI**         | Planning phase                            |

---

## 🛠 Tech Stack

### Backend

| Technology        | Version | Purpose              |
| ----------------- | ------- | -------------------- |
| PHP               | ^8.2    | Server-side language |
| Laravel           | ^12.0   | PHP Framework        |
| Inertia.js        | ^2.1    | SPA adapter          |
| Laravel Fortify   | ^1.30   | Authentication       |
| Laravel Wayfinder | ^0.1.9  | Type-safe routing    |
| PHPSpreadsheet    | ^5.5    | Excel operations     |

### Frontend

| Technology      | Version  | Purpose              |
| --------------- | -------- | -------------------- |
| Vue.js          | ^3.5.13  | Frontend framework   |
| TypeScript      | ^5.2.2   | Type-safe JavaScript |
| Tailwind CSS    | ^4.1.11  | Styling              |
| Vite            | ^7.0.4   | Build tool           |
| ApexCharts      | ^5.3.6   | Charts               |
| ECharts         | ^6.0.0   | Visualizations       |
| Chart.js        | ^4.5.1   | Additional charts    |
| Reka UI         | ^2.4.1   | UI primitives        |
| Lucide Vue Next | ^0.468.0 | Icons                |
| VueUse          | ^12.8.2  | Composition utilities|

---

## 🚀 Installation

### Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+
- npm or yarn
- SQLite or MySQL

### Setup

1. **Clone the repository**

    ```bash
    git clone https://github.com/Gibz-Coder/ProDash-v2.git
    cd ProDash-v2
    ```

2. **Install dependencies**

    ```bash
    composer install
    npm install
    ```

3. **Environment setup**

    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

4. **Database setup**

    ```bash
    php artisan migrate
    php artisan db:seed
    ```

5. **Build assets**
    ```bash
    npm run build
    ```

---

## 💻 Usage

### Development

```bash
# Run concurrent development servers (Laravel + Queue + Vite)
composer dev

# Or run with SSR support
composer dev:ssr
```

### Production

```bash
# Build for production
npm run build

# Build with SSR
npm run build:ssr
```

### Code Quality

```bash
# Format code
npm run format

# Check formatting
npm run format:check

# Lint and fix
npm run lint

# PHP code style
composer pint
```

### Scheduled Tasks

The application includes automated tasks that run via Laravel's scheduler:

```bash
# Capture equipment snapshots every 30 minutes
php artisan schedule:work
```

Key scheduled commands:
- `equipment:capture-snapshot` - Captures equipment utilization data every 30 minutes
- `equipment:cleanup-snapshots` - Cleans up old snapshots (keeps last 7 days)

---

## 📖 Documentation

For detailed documentation, see [Documentation.md](Documentation.md).

### Key Sections

- [Architecture](Documentation.md#architecture)
- [API Endpoints](Documentation.md#api-endpoints)
- [Data Models](Documentation.md#data-models)
- [User Roles & Permissions](Documentation.md#user-roles--permissions)
- [Configuration](Documentation.md#configuration)

---

## � Project Structure

```
ProDash-v2/
├── app/
│   ├── Console/Commands/      # Artisan commands
│   ├── Http/
│   │   ├── Controllers/       # Request handlers
│   │   ├── Middleware/        # Custom middleware
│   │   └── Requests/          # Form requests
│   ├── Models/                # Eloquent models
│   ├── Enums/                 # Enumerations
│   └── Providers/             # Service providers
├── resources/
│   ├── css/                   # Stylesheets
│   ├── js/
│   │   ├── components/        # Vue components
│   │   ├── composables/       # Composition functions
│   │   ├── layouts/           # Page layouts
│   │   ├── pages/             # Page components
│   │   │   ├── Admin/         # Admin pages
│   │   │   ├── auth/          # Authentication pages
│   │   │   ├── dashboards/    # Dashboard pages
│   │   │   ├── error/         # Error pages
│   │   │   └── settings/      # Settings pages
│   │   └── types/             # TypeScript definitions
│   └── views/                 # Blade templates
├── routes/
│   ├── web.php                # Web routes
│   ├── admin.php              # Admin routes
│   ├── settings.php           # Settings routes
│   └── console.php            # Console routes
├── database/
│   ├── migrations/            # Database migrations
│   └── seeders/               # Database seeders
└── public/                    # Public assets
```

---

## 🔐 Authentication & Authorization

### Authentication Features
- Employee ID-based login (emp_no)
- Session-based authentication
- Two-factor authentication (TOTP)
- Password reset via email
- Email verification
- Remember me functionality

### User Roles
- **Super Admin** - Full system access
- **Admin** - Administrative access
- **Manager** - Management level access
- **Supervisor** - Supervisory access
- **User** - Basic user access

### Permissions
- Endtime Manage - Create and update endtime entries
- Endtime Delete - Delete endtime entries
- Role-based access control for admin features
- Permission-based middleware for route protection

---

## 📊 Key Features Detail

### Endtime Dashboard
- Real-time lot tracking and forecasting
- Equipment utilization monitoring
- Remaining lots visualization
- Export functionality
- Cutoff-based predictions

### Lot Request System
- FIFO (First In First Out) management
- LIPAS (Line In Process Allocation System)
- Urgent request handling
- Request status tracking (Pending, Accepted, Rejected)
- Size and line-based filtering

### MEMS Dashboard
- Real-time machine utilization tracking
- Equipment status monitoring
- Endtime per cutoff visualization
- Machine type and size analytics
- Lot assignment functionality
- Equipment remarks management

### Machine Allocation
- Equipment capacity management
- MC Rack tracking
- CRUD operations for allocations
- Reference data management
- Export capabilities

### Data Entry
- Update WIP
- WIP Trend
- Process Result
- Process Trackout
- Monthly Plan
- Excel template downloads for each form
- Bulk data import

---

## 🔧 Configuration

### Environment Variables

Key environment variables in `.env`:

```env
APP_NAME="VICIS ProDash V2"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost

DB_CONNECTION=sqlite
DB_DATABASE=/path/to/database.sqlite

MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
```

### Session Configuration
- Session lifetime: 120 minutes
- Automatic session keep-alive
- Idle timeout handling

---

## 📊 Build Progress

| Category                  | Progress |
| ------------------------- | -------- |
| Core Infrastructure       | 100% ✅  |
| Authentication & Security | 100% ✅  |
| Main Dashboards           | 85%      |
| Admin Features            | 100% ✅  |
| Data Entry                | 100% ✅  |
| UI Components             | 100% ✅  |
| API Endpoints             | 100% ✅  |

---

## 🐛 Error Handling

Custom error pages for:
- 401 - Unauthorized
- 403 - Forbidden
- 404 - Not Found
- 419 - Page Expired
- 429 - Too Many Requests
- 500 - Server Error
- 503 - Service Unavailable
- Maintenance Mode

---

## 📝 License

This project is proprietary software developed for internal use.

---

## 👥 Support

For issues or questions, contact the development team or refer to the internal documentation.

---

<div align="center">

**VICIS ProDash V2** • Built with ❤️ using Laravel & Vue.js

</div>
