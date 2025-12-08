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

- 📊 **Endtime Forecasting** - Lot track-out prediction per cutoff
- 📦 **WIP Management** - Work-in-Progress visual summary
- ⚙️ **Machine Allocation** - Equipment capacity management
- 📈 **Process KPI** - Real-time process performance metrics
- 📋 **Lot Request** - FIFO, LIPAS and urgent lot management
- 🏭 **Endline Management** - Visual endline WIP management
- 🚨 **Escalation Management** - Machine escalation tracking

---

## ✨ Features

### Implemented ✅

| Module                 | Description                                                  |
| ---------------------- | ------------------------------------------------------------ |
| **Home Dashboard**     | Hexagonal navigation grid with animated background           |
| **Endtime Dashboard**  | Production forecasting with metrics, charts, and data tables |
| **Process WIP**        | WIP management with statistics and visualizations            |
| **Machine Allocation** | Equipment capacity and allocation management                 |
| **Data Entry**         | 5 data entry forms with Excel template downloads             |
| **Admin Panel**        | User management, roles, permissions, and system settings     |
| **User Settings**      | Profile, password, 2FA, and appearance customization         |
| **Error Pages**        | Custom error pages (401, 403, 404, 419, 429, 500, 503)       |

### In Progress 🚧

- Lot Request Dashboard
- Process KPI Dashboard
- Endline Management
- Machine Escalation

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

### Frontend

| Technology      | Version  | Purpose              |
| --------------- | -------- | -------------------- |
| Vue.js          | ^3.5.13  | Frontend framework   |
| TypeScript      | ^5.2.2   | Type-safe JavaScript |
| Tailwind CSS    | ^4.1.11  | Styling              |
| Vite            | ^7.0.4   | Build tool           |
| ApexCharts      | ^5.3.6   | Charts               |
| ECharts         | ^6.0.0   | Visualizations       |
| Reka UI         | ^2.4.1   | UI primitives        |
| Lucide Vue Next | ^0.468.0 | Icons                |

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

# Lint and fix
npm run lint

# PHP code style
composer pint
```

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

## 📁 Project Structure

```
ProDash-v2/
├── app/                    # Laravel backend
│   ├── Http/Controllers/   # Request handlers
│   ├── Models/             # Eloquent models
│   └── Providers/          # Service providers
├── resources/
│   ├── css/                # Stylesheets
│   └── js/
│       ├── components/     # Vue components
│       ├── layouts/        # Page layouts
│       ├── pages/          # Page components
│       └── types/          # TypeScript definitions
├── routes/                 # Route definitions
└── database/               # Migrations & seeders
```

---

## 🔐 Authentication

- Employee ID-based login (emp_no)
- Session-based authentication
- Two-factor authentication (TOTP)
- Password reset via email
- Role-based access control

---

## 📊 Build Progress

| Category                  | Progress |
| ------------------------- | -------- |
| Core Infrastructure       | 100% ✅  |
| Authentication & Security | 100% ✅  |
| Main Dashboards           | 60%      |
| Admin Features            | 100% ✅  |
| Data Entry                | 100% ✅  |
| UI Components             | 100% ✅  |

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
