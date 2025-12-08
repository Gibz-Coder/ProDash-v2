# Error Pages

Professional error pages for VICIS ProDash v2.0 application.

## Available Error Pages

### 401 - Unauthorized
**File:** `401.vue`
- Displayed when authentication is required
- Provides sign-in and home navigation options
- Includes account creation prompt

### 403 - Access Forbidden
**File:** `403.vue`
- Shown when user lacks permissions
- Offers contact admin option
- Explains common reasons for access denial

### 404 - Page Not Found
**File:** `404.vue`
- Classic not found error
- Provides navigation to home and back
- Includes helpful links section

### 419 - Session Expired
**File:** `419.vue`
- Displayed when CSRF token expires
- Simple refresh action
- Explains session timeout reasons

### 429 - Too Many Requests
**File:** `429.vue`
- Rate limiting error page
- Interactive countdown timer
- Explains rate limiting policy

### 500 - Internal Server Error
**File:** `500.vue`
- Server-side error page
- Retry and home navigation
- Support contact information

### 503 - Service Unavailable
**File:** `503.vue`
- Service temporarily down
- Animated status indicator
- Expected resolution time display

### Maintenance Mode
**File:** `Maintenance.vue`
- Scheduled maintenance page
- Custom message support
- Estimated downtime display
- Feature cards highlighting data safety

## Usage

### In Laravel Routes
```php
// In routes/web.php or error handler
Route::get('/error/{code}', function ($code) {
    return inertia("error/{$code}");
});
```

### In Inertia Error Handler
```typescript
// In app.ts
import { createInertiaApp } from '@inertiajs/vue3'

createInertiaApp({
  resolve: (name) => {
    const pages = import.meta.glob('./pages/**/*.vue', { eager: true })
    return pages[`./pages/${name}.vue`]
  },
  // ...
})
```

### Direct Import
```typescript
import { Error404, Error500, Maintenance } from '@/pages/error';
```

## Features

- **Consistent Design**: All pages follow the same design language
- **Responsive**: Mobile-friendly layouts
- **Accessible**: Proper semantic HTML and ARIA labels
- **Animated**: Subtle animations for better UX
- **Branded**: Includes VICIS ProDash branding
- **Actionable**: Clear CTAs for user actions
- **Informative**: Helpful information boxes

## Customization

### Update Support Email
Replace `support@example.com` and `admin@example.com` with your actual support emails.

### Modify Colors
Each error page uses theme-aware colors that adapt to light/dark mode:
- 401: Orange accent
- 403: Red/Destructive accent
- 404: Primary accent
- 419: Blue accent
- 429: Amber accent
- 500: Red/Destructive accent
- 503: Yellow accent
- Maintenance: Primary accent

### Add Custom Messages
Pass props to customize messages:
```vue
<Maintenance 
  message="Custom maintenance message" 
  :retryAfter="30" 
/>
```

## Design Standards

- Gradient backgrounds for visual appeal
- Large, readable error codes
- Icon-based visual hierarchy
- Consistent spacing and typography
- Professional color palette
- Clear call-to-action buttons
- Helpful information cards
