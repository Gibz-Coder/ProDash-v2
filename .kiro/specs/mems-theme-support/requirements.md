# MEMS Dashboard Theme Support - Requirements

## Overview
Adopt the ProDash v2 theme system (light, dark, and system modes) for the MEMS Dashboard to provide consistent theming across the application.

## User Stories

### 1. Theme Consistency
**As a** user  
**I want** the MEMS dashboard to respect my theme preference (light/dark/system)  
**So that** I have a consistent visual experience across the entire ProDash v2 application

### 2. Automatic Theme Detection
**As a** user  
**I want** the MEMS dashboard to automatically detect and apply my system theme preference  
**So that** the dashboard matches my operating system's appearance settings

### 3. Theme Persistence
**As a** user  
**I want** my theme preference to persist across sessions  
**So that** I don't have to reconfigure the theme every time I visit the dashboard

### 4. Smooth Theme Transitions
**As a** user  
**I want** smooth transitions when switching between themes  
**So that** the theme change doesn't feel jarring or disruptive

## Acceptance Criteria

### 1.1 Theme Integration
- [ ] MEMS dashboard reads theme preference from the existing ProDash v2 theme system
- [ ] Dashboard applies the correct theme (light/dark) based on user preference
- [ ] System theme preference is detected and applied automatically
- [ ] Theme changes in the Appearance settings immediately affect the MEMS dashboard

### 1.2 Light Theme Support
- [ ] All MEMS dashboard components render correctly in light mode
- [ ] Text remains readable with appropriate contrast ratios
- [ ] Charts (ECharts and Chart.js) adapt to light theme colors
- [ ] Tables, panels, and UI elements use light theme color palette
- [ ] Borders, shadows, and accents are visible in light mode

### 1.3 Dark Theme Support
- [ ] All MEMS dashboard components render correctly in dark mode (current state)
- [ ] Current dark theme styling is preserved
- [ ] Charts maintain visibility and readability in dark mode
- [ ] All existing dark mode features continue to work

### 1.4 System Theme Support
- [ ] Dashboard detects system theme preference on initial load
- [ ] Dashboard responds to system theme changes in real-time
- [ ] Correct theme is applied when user selects "System" mode

### 1.5 CSS Variable Architecture
- [ ] MEMS dashboard uses CSS custom properties for theme colors
- [ ] Color variables are defined for both light and dark modes
- [ ] Variables follow ProDash v2 naming conventions
- [ ] Chart colors adapt based on theme variables

### 1.6 Chart Theme Adaptation
- [ ] ECharts gauge chart adapts colors for light/dark themes
- [ ] Chart.js trend chart adapts colors for light/dark themes
- [ ] Chart backgrounds are transparent or theme-aware
- [ ] Chart text colors provide sufficient contrast in both themes
- [ ] Chart tooltips match the current theme

### 1.7 No Flash of Unstyled Content
- [ ] Theme is applied before page render (no FOUC)
- [ ] Inline script in app.blade.php handles initial theme
- [ ] Theme transitions are smooth without flickering

## Technical Requirements

### 2.1 Use Existing Theme Infrastructure
- Leverage `useAppearance` composable from `@/composables/useAppearance`
- Utilize existing `HandleAppearance` middleware
- Respect `appearance` cookie and localStorage values
- Use Tailwind's `dark:` variant system where applicable

### 2.2 CSS Architecture
- Convert hardcoded colors in `mems-dashboard.css` to CSS custom properties
- Define theme-specific color values using `:root` and `.dark` selectors
- Maintain existing visual design in dark mode
- Create complementary light mode design

### 2.3 Chart Library Integration
- Update ECharts configuration to use theme-aware colors
- Update Chart.js configuration to use theme-aware colors
- Ensure chart legends, labels, and tooltips adapt to theme
- Test chart readability in both themes

### 2.4 Component Updates
- Update `mems-dashboard.vue` to be theme-aware
- Ensure all inline styles respect theme variables
- Update composables if needed for theme reactivity

## Out of Scope
- Creating a separate theme toggle for MEMS dashboard (use existing Appearance settings)
- Custom theme colors beyond light/dark/system
- Per-dashboard theme preferences (global theme applies to all)
- Theme customization or color picker features

## Dependencies
- Existing ProDash v2 theme system (`useAppearance` composable)
- Tailwind CSS dark mode configuration
- ECharts and Chart.js libraries
- Current MEMS dashboard implementation

## Success Metrics
- MEMS dashboard correctly displays in light, dark, and system modes
- No visual regressions in dark mode (current state)
- All text maintains WCAG AA contrast ratios in both themes
- Theme switches apply instantly without page reload
- No console errors or warnings related to theming

## Design Considerations

### Color Palette Strategy
**Dark Mode (Current):**
- Background: `#0a1929`, `#0d2137`, `#132f4c`
- Accent: `#00d4ff` (cyan)
- Text: `#ffffff`, `#b0bec5`

**Light Mode (New):**
- Background: Light grays/whites
- Accent: Adjusted cyan for light backgrounds
- Text: Dark grays/blacks
- Maintain visual hierarchy and contrast

### Chart Color Strategy
- Use theme-aware color functions
- Ensure data visualization remains clear in both themes
- Maintain color coding consistency (e.g., Run=green, Wait=orange)
- Adjust opacity/saturation for theme compatibility

## Notes
- The MEMS dashboard currently uses a custom dark theme with specific colors
- ProDash v2 uses Tailwind CSS with dark mode support
- Theme preference is stored in both cookie (SSR) and localStorage (client)
- System theme detection uses `prefers-color-scheme` media query
