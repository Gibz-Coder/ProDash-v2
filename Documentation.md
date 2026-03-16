# VICIS ProDash V2 - Technical Documentation

## Table of Contents

1. [Architecture](#architecture)
2. [Data Models](#data-models)
3. [API Endpoints](#api-endpoints)
4. [User Roles & Permissions](#user-roles--permissions)
5. [Configuration](#configuration)
6. [Development Guide](#development-guide)
7. [Deployment](#deployment)

---

## Architecture

### Technology Stack

ProDash V2 follows a modern SPA architecture:

```
┌─────────────────────────────────────────────────────────┐
│                     Frontend (Vue.js)                   │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐   │
│  │   Pages      │  │  Components  │  │  Composables │   │
│  └──────────────┘  └──────────────┘  └──────────────┘   │
└─────────────────────────────────────────────────────────┘
                           │
                    Inertia.js Bridge
                           │
┌─────────────────────────────────────────────────────────┐
│                   Backend (Laravel)                     │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐   │
│  │ Controllers  │  │    Models    │  │  Middleware  │   │
│  └──────────────┘  └──────────────┘  └──────────────┘   │
└─────────────────────────────────────────────────────────┘
                           │
┌─────────────────────────────────────────────────────────┐
│                  Database (SQLite/MySQL)                │
└─────────────────────────────────────────────────────────┘
```

### Key Architectural Patterns

1. **Inertia.js SPA** - Server-side routing with client-side rendering
2. **Type-Safe Routing** - Laravel Wayfinder for compile-time route checking
3. **Composition API** - Vue 3 Composition API with TypeScript
4. **Repository Pattern** - Eloquent models with business logic separation
5. **Middleware Pipeline** - Authentication, authorization, and request handling

---

## Data Models

### User Model

```php
User {
    id: integer
    emp_no: string (unique)
    name: string
    email: string (unique)
    password: string
    role: string
    two_factor_secret: string (nullable)
    two_factor_recovery_codes: text (nullable)
    two_factor_confirmed_at: timestamp (nullable)
    email_verified_at: timestamp (nullable)
    remember_token: string (nullable)
    current_team_id: integer (nullable)
    profile_photo_path: string (nullable)
    created_at: timestamp
    updated_at: timestamp
}
```

### Equipment Model

```php
Equipment {
    id: integer
    equipment_no: string (unique)
    equipment_name: string
    line: string
    machine_type: string
    machine_size: string
    status: string
    work_type: string (nullable)
    lot_no: string (nullable)
    model: string (nullable)
    quantity: integer (nullable)
    operator: string (nullable)
    remarks: string (nullable)
    est_endtime: datetime (nullable)
    mc_rack: string (nullable)
    created_at: timestamp
    updated_at: timestamp
}
```

### Endtime Model

```php
Endtime {
    id: integer
    lot_no: string
    model: string
    quantity: integer
    process: string
    equipment_no: string
    operator: string
    start_time: datetime
    est_endtime: datetime
    status: string (default: 'ongoing')
    actual_endtime: datetime (nullable)
    remarks: string (nullable)
    created_at: timestamp
    updated_at: timestamp
}
```

### LotRequest Model

```php
LotRequest {
    id: integer
    request_type: string (fifo, lipas, urgent)
    lot_no: string (nullable)
    model: string (nullable)
    request_model: string (nullable)
    quantity: integer (nullable)
    size: string
    line: string
    lot_location: string (nullable)
    lipas: string (nullable)
    priority: string (default: 'normal')
    status: string (default: 'pending')
    requested_by: string
    requested_at: datetime
    assigned_by: string (nullable)
    assigned_at: datetime (nullable)
    accepted_by: string (nullable)
    accepted_at: datetime (nullable)
    rejected_by: string (nullable)
    rejected_at: datetime (nullable)
    rejection_reason: string (nullable)
    remarks: string (nullable)
    created_at: timestamp
    updated_at: timestamp
}
```

### EquipmentSnapshot Model

```php
EquipmentSnapshot {
    id: integer
    equipment_no: string
    equipment_name: string
    line: string
    machine_type: string
    machine_size: string
    status: string
    work_type: string (nullable)
    lot_no: string (nullable)
    model: string (nullable)
    quantity: integer (nullable)
    operator: string (nullable)
    snapshot_at: datetime
    created_at: timestamp
    updated_at: timestamp
}
```

### UpdateWip Model

```php
UpdateWip {
    id: integer
    lot_no: string (unique)
    model: string
    quantity: integer
    process: string
    location: string (nullable)
    status: string (nullable)
    remarks: string (nullable)
    created_at: timestamp
    updated_at: timestamp
}
```

### QcDefectClass (Table: `qc_defect_class`)

```php
QcDefectClass {
    id: integer
    defect_code: string (unique)
    defect_name: string
    defect_class: string          // 'QC Analysis' | "Tech'l Verification"
    defect_flow: string           // 'QC Analysis' | "Tech'l Verification"
    created_by: string
    remarks: text (nullable)
    created_at: timestamp
    updated_at: timestamp
}
```

### EndlineDelay (Table: `endline_delay`)

```php
EndlineDelay {
    id: integer
    lot_id: string
    model: string (nullable)
    lot_qty: integer (nullable)
    lipas_yn: string (nullable)
    qc_result: string (nullable)  // QC NG: 'Main' | 'RR' | 'LY' | 'OK'
    qc_defect: string (nullable)  // comma-separated defect codes
    defect_class: string (nullable) // 'QC Analysis' | "Tech'l Verification"
    qc_ana_start: datetime (nullable)
    qc_ana_result: string (nullable)
    qc_ana_tat: integer (nullable)
    vi_techl_start: datetime (nullable)
    vi_techl_result: string (nullable)
    vi_techl_tat: integer (nullable)
    final_decision: string (nullable) // 'Proceed' | 'Rework' | 'Low Yield'
    total_tat: integer (nullable)
    work_type: string (nullable)
    remarks: text (nullable)
    updated_by: string
    created_at: timestamp
    updated_at: timestamp
}
```

### Role Model

```php
Role {
    id: integer
    name: string (unique)
    permissions: json
    created_at: timestamp
    updated_at: timestamp
}
```

### Authentication Endpoints

```
POST   /login                          - User login
POST   /logout                         - User logout
POST   /register                       - User registration
POST   /forgot-password                - Request password reset
POST   /reset-password                 - Reset password
POST   /email/verification-notification - Resend verification email
GET    /verify-email/{id}/{hash}       - Verify email
POST   /two-factor-challenge           - 2FA challenge
```

### Dashboard Endpoints

```
GET    /dashboard                      - Home dashboard
GET    /endtime                        - Endtime dashboard
GET    /lot-request                    - Lot request dashboard
GET    /process-wip                    - Process WIP dashboard
GET    /mc-allocation                  - Machine allocation dashboard
GET    /mems-dashboard                 - MEMS dashboard
GET    /endline                        - Endline dashboard
GET    /escalation                     - Escalation dashboard
GET    /data-entry                     - Data entry page
```

### Endtime API

```
GET    /endtime/equipment-list         - Get equipment list
GET    /endtime/remaining-lots-list    - Get remaining lots
GET    /endtime/export                 - Export endtime data
POST   /endtime/store                  - Create endtime entry
PUT    /endtime/{id}                   - Update endtime entry
DELETE /endtime/{id}                   - Delete endtime entry
POST   /endtime/{id}/submit            - Submit lot completion
GET    /api/endtime/lookup-ongoing     - Lookup ongoing lot
```

### Lot Request API

```
GET    /api/lot-request/data           - Get lot request data
GET    /api/lot-request/stats          - Get statistics
GET    /api/lot-request/by-size        - Get requests by size
GET    /api/lot-request/by-line        - Get requests by line
GET    /api/lot-request/check-pending  - Check pending requests
POST   /api/lot-request                - Create lot request
POST   /api/lot-request/{id}/assign    - Assign lot to request
POST   /api/lot-request/{id}/accept    - Accept lot request
POST   /api/lot-request/{id}/reject    - Reject lot request
```

### Machine Allocation API

```
GET    /mc-allocation                  - Get allocations
GET    /mc-allocation/export           - Export allocations
PUT    /mc-allocation/{id}             - Update allocation
DELETE /mc-allocation/{id}             - Delete allocation
GET    /mc-allocation/reference-data   - Get reference data
```

### MEMS Dashboard API

```
GET    /api/mems/utilization/trend     - Get utilization trend
GET    /api/mems/utilization/latest    - Get latest utilization
GET    /api/mems/equipment/list        - Get equipment list
POST   /api/mems/snapshot/capture      - Manual snapshot capture
DELETE /api/mems/snapshot/clear        - Clear snapshots
GET    /api/mems/snapshot/stats        - Get snapshot statistics
GET    /api/mems/filters/options       - Get filter options
GET    /api/mems/endtime/remaining     - Get endtime remaining
GET    /api/mems/endtime/raw-lots      - Get raw lots data
GET    /api/mems/available-lots        - Get available lots
POST   /api/mems/lot-request           - Create lot request
POST   /api/mems/validate-employee     - Validate employee ID
```

### Equipment Status API

```
GET    /api/equipment/status/utilization    - Get machine utilization
GET    /api/equipment/status/line           - Get line utilization
GET    /api/equipment/status/machine-type   - Get machine type status
GET    /api/equipment/status/machine-size   - Get machine size status
GET    /api/equipment/status/raw            - Get raw equipment data
GET    /api/equipment/details/{equipmentNo} - Get equipment details
PUT    /api/equipment/{equipmentNo}/remarks - Update equipment remarks
```

### Data Entry API

```
POST   /updatewip                      - Update WIP data
GET    /updatewip/download-template    - Download WIP template
GET    /api/updatewip/last-update      - Get last update time
POST   /wip-trend/update               - Update WIP trend
GET    /wip-trend/download-template    - Download WIP trend template
POST   /process-result/update          - Update process result
GET    /process-result/download-template - Download process result template
POST   /process-trackout/update        - Update process trackout
GET    /process-trackout/download-template - Download trackout template
POST   /monthly-plan/update            - Update monthly plan
GET    /monthly-plan/download-template - Download monthly plan template
```

### Lookup API

```
GET    /api/updatewip/lookup           - Lookup lot in WIP
GET    /api/equipment/search           - Search equipment
GET    /api/equipment/lookup           - Lookup equipment
GET    /api/employee/search            - Search employee
GET    /api/employee/lookup            - Lookup employee
```

### Admin API

```
GET    /admin/users                    - List users
POST   /admin/users                    - Create user
PUT    /admin/users/{id}               - Update user
DELETE /admin/users/{id}               - Delete user
GET    /admin/roles                    - List roles
POST   /admin/roles                    - Create role
PUT    /admin/roles/{id}               - Update role
DELETE /admin/roles/{id}               - Delete role
GET    /admin/settings                 - Get system settings
PUT    /admin/settings                 - Update system settings
```

### Settings API

```
GET    /settings/profile               - Get profile
PUT    /settings/profile               - Update profile
PUT    /settings/password              - Update password
POST   /settings/two-factor            - Enable 2FA
DELETE /settings/two-factor            - Disable 2FA
GET    /settings/two-factor/qr-code    - Get 2FA QR code
GET    /settings/two-factor/recovery-codes - Get recovery codes
```

### Utility API

```
GET    /api/ping                       - Session keep-alive
POST   /api/session/keep-alive         - Refresh session
GET    /api/health/scheduler           - Check scheduler health
```

### Endline Delay API

```
GET    /endline-delay                          - Endline delay dashboard (Inertia)
GET    /api/endline-delay                      - Get records (filterable)
POST   /api/endline-delay                      - Create record(s)
PUT    /api/endline-delay/{id}                 - Update record
DELETE /api/endline-delay/{id}                 - Delete record
GET    /api/endline-delay/export               - Export to CSV
GET    /api/endline-delay/lot-lookup           - Lookup lot from WIP
GET    /api/endline-delay/defect-code-search   - Search defect codes
```

**Query Parameters for GET `/api/endline-delay`:**
| Param | Description |
|---|---|
| `search` | Filter by lot_id or model |
| `date` | Filter by specific date |
| `date_from` / `date_to` | Date range (export) |
| `shift` | `DAY` (07:00–19:00) or `NIGHT` |
| `cutoff` | Time window e.g. `07:00~11:59` |
| `work_type` | `NORMAL`, `PROCESS RW`, etc. |
| `lipas_yn` | `Y` or `N` |
| `final_decision` | `Proceed`, `Rework`, `Low Yield` |

**Summary Cards (computed client-side):**

- Total — all records count + total qty
- QC Analysis — `defect_class = 'QC Analysis'`
- VI Technical — `defect_class = "Tech'l Verification"`
- Mainlot — `qc_result = 'Main'`
- R-Rework — `qc_result = 'RR'`
- L-Rework — `qc_result IN ('LY', 'OK')`

**Unit Filter:** Persistent (localStorage) toggle between `pcs`, `Kpcs`, `Mpcs` applied to all qty displays.

### QC Defect Class API

```
GET    /qc-defect-class                - QC defect class management page (Inertia)
GET    /api/qc-defect-class            - List all defect codes
POST   /api/qc-defect-class            - Create defect code
PUT    /api/qc-defect-class/{id}       - Update defect code
DELETE /api/qc-defect-class/{id}       - Delete defect code
```

---

## User Roles & Permissions

### Role Hierarchy

```
Super Admin (Full Access)
    ├── Admin (Administrative Access)
    │   ├── Manager (Management Access)
    │   │   ├── Supervisor (Supervisory Access)
    │   │   │   └── User (Basic Access)
```

### Permission Matrix

| Feature            | Super Admin | Admin | Manager | Supervisor | User |
| ------------------ | ----------- | ----- | ------- | ---------- | ---- |
| View Dashboards    | ✅          | ✅    | ✅      | ✅         | ✅   |
| Endtime View       | ✅          | ✅    | ✅      | ✅         | ✅   |
| Endtime Manage     | ✅          | ✅    | ✅      | ✅         | ❌   |
| Endtime Delete     | ✅          | ✅    | ❌      | ❌         | ❌   |
| Lot Request Create | ✅          | ✅    | ✅      | ✅         | ✅   |
| Lot Request Assign | ✅          | ✅    | ✅      | ✅         | ❌   |
| Data Entry         | ✅          | ✅    | ✅      | ✅         | ❌   |
| User Management    | ✅          | ✅    | ❌      | ❌         | ❌   |
| Role Management    | ✅          | ❌    | ❌      | ❌         | ❌   |
| System Settings    | ✅          | ✅    | ❌      | ❌         | ❌   |

### Permission Definitions

```php
'Endtime Manage' => [
    'create' => true,
    'update' => true,
    'view' => true
],
'Endtime Delete' => [
    'delete' => true
]
```

---

## Configuration

### Environment Configuration

#### Application Settings

```env
APP_NAME="VICIS ProDash V2"
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=http://localhost
```

#### Database Configuration

```env
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database.sqlite

# Or for MySQL
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=prodash
DB_USERNAME=root
DB_PASSWORD=
```

#### Session Configuration

```env
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
```

#### Mail Configuration

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@prodash.local"
MAIL_FROM_NAME="${APP_NAME}"
```

### Laravel Configuration Files

#### config/fortify.php

```php
'features' => [
    Features::registration(),
    Features::resetPasswords(),
    Features::emailVerification(),
    Features::updateProfileInformation(),
    Features::updatePasswords(),
    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]),
],
```

#### config/inertia.php

```php
'ssr' => [
    'enabled' => false,
    'url' => 'http://127.0.0.1:13714',
],
```

### Scheduled Tasks

Add to your crontab:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

Scheduled commands:

- `equipment:capture-snapshot` - Every 30 minutes
- `equipment:cleanup-snapshots` - Daily at 2:00 AM

---

## Development Guide

### Setting Up Development Environment

1. **Install Dependencies**

```bash
composer install
npm install
```

2. **Configure Environment**

```bash
cp .env.example .env
php artisan key:generate
```

3. **Setup Database**

```bash
php artisan migrate
php artisan db:seed
```

4. **Start Development Servers**

```bash
composer dev
```

This runs:

- Laravel development server (port 8000)
- Queue worker
- Vite development server (port 5173)

### Code Style

#### PHP (Laravel Pint)

```bash
composer pint
```

#### JavaScript/TypeScript (ESLint + Prettier)

```bash
npm run lint
npm run format
```

### Creating New Features

#### 1. Create Migration

```bash
php artisan make:migration create_table_name
```

#### 2. Create Model

```bash
php artisan make:model ModelName
```

#### 3. Create Controller

```bash
php artisan make:controller ControllerName
```

#### 4. Create Vue Page

```
resources/js/pages/YourPage.vue
```

#### 5. Add Route

```php
// routes/web.php
Route::get('/your-route', [YourController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('your_route');
```

### Testing

```bash
# Run all tests
php artisan test

# Run specific test
php artisan test --filter TestName
```

### Building for Production

```bash
# Build frontend assets
npm run build

# Optimize Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Deployment

### Production Checklist

- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Configure production database
- [ ] Set up mail server
- [ ] Configure session driver (database/redis)
- [ ] Set up queue worker
- [ ] Configure scheduler cron job
- [ ] Build production assets
- [ ] Cache configuration
- [ ] Set proper file permissions
- [ ] Configure web server (Nginx/Apache)
- [ ] Set up SSL certificate
- [ ] Configure backup strategy

### Web Server Configuration

#### Nginx

```nginx
server {
    listen 80;
    server_name prodash.example.com;
    root /var/www/prodash/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Queue Worker Setup

#### Supervisor Configuration

```ini
[program:prodash-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/prodash/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/prodash/storage/logs/worker.log
stopwaitsecs=3600
```

### Backup Strategy

```bash
# Database backup
php artisan backup:run --only-db

# Full backup
php artisan backup:run
```

---

## Troubleshooting

### Common Issues

#### 1. Session Timeout

**Problem**: Users getting logged out frequently
**Solution**: Increase `SESSION_LIFETIME` in `.env` or implement session keep-alive

#### 2. Queue Not Processing

**Problem**: Background jobs not running
**Solution**: Ensure queue worker is running: `php artisan queue:work`

#### 3. Scheduler Not Running

**Problem**: Snapshots not being captured
**Solution**: Verify cron job is configured correctly

#### 4. Permission Denied

**Problem**: Cannot write to storage
**Solution**: Set proper permissions: `chmod -R 775 storage bootstrap/cache`

### Debug Mode

Enable debug mode for development:

```env
APP_DEBUG=true
```

View logs:

```bash
tail -f storage/logs/laravel.log
```

---

## API Response Formats

### Success Response

```json
{
    "success": true,
    "data": { ... },
    "message": "Operation successful"
}
```

### Error Response

```json
{
    "success": false,
    "message": "Error message",
    "errors": {
        "field": ["Validation error"]
    }
}
```

### Pagination Response

```json
{
    "data": [ ... ],
    "current_page": 1,
    "last_page": 10,
    "per_page": 15,
    "total": 150
}
```

---

## Security Considerations

1. **Authentication**: Employee ID-based with optional 2FA
2. **Authorization**: Role-based access control with permissions
3. **CSRF Protection**: Enabled for all POST/PUT/DELETE requests
4. **XSS Protection**: Vue.js automatic escaping
5. **SQL Injection**: Eloquent ORM parameterized queries
6. **Session Security**: HTTP-only cookies, secure flag in production
7. **Password Hashing**: Bcrypt with cost factor 10

---

## Performance Optimization

1. **Database Indexing**: Key columns indexed for fast queries
2. **Query Optimization**: Eager loading to prevent N+1 queries
3. **Caching**: Configuration and route caching in production
4. **Asset Optimization**: Minified and bundled assets
5. **CDN**: Static assets served via CDN (optional)
6. **Database Connection Pooling**: Persistent connections

---

## Maintenance

### Regular Tasks

- Monitor disk space for database and logs
- Review and archive old snapshots
- Update dependencies regularly
- Review and optimize slow queries
- Monitor error logs
- Backup database regularly

### Updates

```bash
# Update Composer dependencies
composer update

# Update NPM dependencies
npm update

# Run migrations
php artisan migrate

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

## Support & Contact

For technical support or questions:

- Internal Documentation: [Link to internal docs]
- Development Team: [Contact information]
- Issue Tracker: [Link to issue tracker]

---

## Changelog

### March 2026

- **Endline Delay module** — Full CRUD dashboard for QC endline delay tracking with multi-row entry modal, lot lookup from WIP, defect code autocomplete, and CSV export
- **QC Defect Class module** — Management page and seeder for 80+ defect codes with `QC Analysis` / `Tech'l Verification` classification
- **Endline Delay cards** — Summary cards showing qty (primary) and lot count (secondary) for Total, QC Analysis, VI Technical, Mainlot, R-Rework, L-Rework
- **Unit filter** — Persistent `pcs` / `Kpcs` / `Mpcs` toggle in the header bar; applies to all qty displays across cards and table
- **Badge color coding** — QC NG: Main=Blue, RR=Orange, LY=Purple, OK=Green; Defect Class: QC Analysis=Green, Tech'l Verification=Yellow; Decision: Pending=Gray, Proceed=Blue, Rework=Red, Low Yield=Purple
- **DB migrations** — `qc_defect_class`, `endline_delay` tables; added `work_type` column and changed `qc_result` to string type

---

**Last Updated**: March 16, 2026
**Version**: 2.1
**Maintained By**: VICIS Development Team
