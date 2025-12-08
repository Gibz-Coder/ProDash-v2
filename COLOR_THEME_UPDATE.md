# VICIS ProDash v2 - Color Theme Update

## Applied: Plano/Visor Color Scheme

### Date: November 21, 2025

---

## Color Mapping from Plano/Visor to ProDash v2

### Light Mode

| Element | Old Value | New Value (from Plano/Visor) | Color |
|---------|-----------|------------------------------|-------|
| **Primary** | `hsl(0 0% 9%)` Black | `hsl(267 68% 68%)` | Purple #985FFD |
| **Secondary** | `hsl(0 0% 92.1%)` Gray | `hsl(320 100% 64%)` | Pink #FF49CD |
| **Background** | `hsl(0 0% 95%)` | `hsl(240 50% 98%)` | Light Purple-tinted |
| **Foreground/Text** | `hsl(0 0% 3.9%)` | `hsl(222 84% 16%)` | Dark Blue #011A42 |
| **Accent** | `hsl(0 0% 96.1%)` | `hsl(267 68% 68%)` | Purple #985FFD |
| **Destructive** | `hsl(0 84.2% 60.2%)` | `hsl(4 90% 67%)` | Red #FF6757 |
| **Border** | `hsl(0 0% 92.8%)` | `hsl(220 13% 91%)` | Light Gray #E2E8EE |
| **Muted** | `hsl(0 0% 45.1%)` | `hsl(220 13% 36%)` | Muted Blue-Gray |
| **Sidebar Primary** | `hsl(0 0% 10%)` | `hsl(267 68% 68%)` | Purple #985FFD |
| **Sidebar Ring** | `hsl(217.2 91.2% 59.8%)` | `hsl(267 68% 68%)` | Purple #985FFD |

### Dark Mode

| Element | Old Value | New Value (from Plano/Visor) | Color |
|---------|-----------|------------------------------|-------|
| **Primary** | `hsl(0 0% 98%)` White | `hsl(267 68% 68%)` | Purple #985FFD |
| **Secondary** | `hsl(0 0% 14.9%)` | `hsl(320 100% 64%)` | Pink #FF49CD |
| **Background** | `hsl(0 0% 3.9%)` | `hsl(240 10% 9%)` | Dark Blue-Gray #161623 |
| **Foreground** | `hsl(0 0% 98%)` | `hsl(0 0% 95%)` | Off-White |
| **Accent** | `hsl(0 0% 14.9%)` | `hsl(267 68% 68%)` | Purple #985FFD |
| **Destructive** | `hsl(0 84% 60%)` | `hsl(4 90% 67%)` | Red #FF6757 |
| **Sidebar Background** | `hsl(0 0% 7%)` | `hsl(240 10% 7%)` | Dark Blue-Gray |
| **Sidebar Primary** | `hsl(360 100% 100%)` | `hsl(267 68% 68%)` | Purple #985FFD |

---

## Color Palette Reference

### From Plano/Visor Template

```css
/* Primary Colors */
--primary-rgb: 152, 95, 253;      /* Purple #985FFD */
--secondary-rgb: 255, 73, 205;    /* Pink #FF49CD */

/* Status Colors */
--success-rgb: 50, 212, 132;      /* Green #32D484 */
--warning-rgb: 253, 175, 34;      /* Orange #FDAF22 */
--danger-rgb: 255, 103, 87;       /* Red #FF6757 */
--info-rgb: 0, 201, 255;          /* Cyan #00C9FF */

/* Accent Colors */
--teal-rgb: 53, 181, 170;         /* Teal #35B5AA */
--orange-rgb: 250, 129, 40;       /* Orange #FA8128 */
--purple-rgb: 190, 43, 235;       /* Purple #BE2BEB */
--green-rgb: 0, 201, 167;         /* Green #00C9A7 */
--pink-rgb: 255, 105, 180;        /* Pink #FF69B4 */
```

---

## Visual Changes

### Before (Original ProDash v2)
- ⚫ Black primary color
- ⚪ Gray/neutral secondary
- 🎨 Minimal, professional aesthetic
- 📊 Neutral color scheme

### After (Plano/Visor Applied)
- 🟣 Purple primary color (#985FFD)
- 🩷 Pink secondary color (#FF49CD)
- 🎨 Vibrant, modern aesthetic
- 📊 Colorful, energetic design

---

## Component Impact

### Buttons
- Primary buttons: Now purple instead of black
- Secondary buttons: Now pink instead of gray
- Hover states: Purple/pink gradients

### Navigation
- Sidebar active items: Purple highlight
- Menu icons: Purple when active
- Links: Purple color

### Forms
- Focus rings: Purple instead of black
- Active inputs: Purple border
- Checkboxes/radios: Purple when checked

### Cards & Panels
- Accent borders: Purple
- Header backgrounds: Can use purple/pink gradients
- Active states: Purple highlights

### Charts
- Primary chart color: Purple
- Color palette includes vibrant colors from template

---

## Files Modified

- `resources/css/app.css` - Updated CSS variables for light and dark modes

---

## Additional Colors Available

You can now use these Tailwind-compatible colors in your Vue components:

```vue
<!-- Primary Purple -->
<div class="bg-primary text-primary-foreground">Purple Button</div>

<!-- Secondary Pink -->
<div class="bg-secondary text-secondary-foreground">Pink Button</div>

<!-- Accent Purple -->
<div class="bg-accent text-accent-foreground">Accent Element</div>

<!-- Destructive Red -->
<div class="bg-destructive text-destructive-foreground">Error Message</div>
```

---

## Recommendations

1. **Update existing components** to use the new color scheme
2. **Review contrast ratios** for accessibility
3. **Test dark mode** thoroughly
4. **Update brand assets** (logos, icons) if needed
5. **Consider gradients** - the template uses purple-to-pink gradients

### Example Gradient Usage

```vue
<div class="bg-gradient-to-r from-primary to-secondary">
  Vibrant Gradient Header
</div>
```

---

## Rollback

If you need to revert to the original black/gray theme, restore from git:

```bash
git checkout resources/css/app.css
```

Or manually change:
- Primary: `hsl(267 68% 68%)` → `hsl(0 0% 9%)`
- Secondary: `hsl(320 100% 64%)` → `hsl(0 0% 92.1%)`
