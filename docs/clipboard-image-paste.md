# Clipboard Image Paste to Database (Base64)

## Overview

This guide covers how to capture a screenshot from the clipboard (e.g. Windows Ctrl+Shift+S),
paste it into a Vue modal form, compress it, and store it as a base64 string in a MySQL database.

---

## Why Base64?

- No file upload required (bypasses network/browser file upload restrictions)
- Image is stored directly in the database as a string
- Easy to render back with `<img :src="screenshot" />`

**Overhead:** Base64 adds ~33% to the original file size.

| Screenshot size | Base64 in DB |
| --------------- | ------------ |
| 100 KB          | ~133 KB      |
| 500 KB          | ~665 KB      |
| 1 MB            | ~1.33 MB     |
| 2 MB            | ~2.66 MB     |

Use JPEG compression on the frontend to reduce this significantly (see below).

---

## Frontend (Vue)

### Template

```vue
<template>
    <div>
        <div
            class="paste-zone"
            tabindex="0"
            @paste="handlePaste"
            style="border: 2px dashed #ccc; padding: 20px; cursor: pointer;"
        >
            Click here then paste your screenshot (Ctrl+V)
        </div>

        <img
            v-if="imageBase64"
            :src="imageBase64"
            style="max-width: 300px; margin-top: 10px;"
        />
    </div>
</template>
```

### Script (with JPEG compression)

```vue
<script setup>
import { ref } from 'vue';

const imageBase64 = ref(null);

function handlePaste(event) {
    const items = event.clipboardData?.items;
    if (!items) return;

    for (const item of items) {
        if (item.type.startsWith('image/')) {
            const file = item.getAsFile();
            const reader = new FileReader();

            reader.onload = (e) => {
                const img = new Image();
                img.onload = () => {
                    const canvas = document.createElement('canvas');
                    canvas.width = img.width;
                    canvas.height = img.height;
                    canvas.getContext('2d').drawImage(img, 0, 0);
                    // Convert to JPEG at 70% quality — reduces size to ~20-40% of original PNG
                    imageBase64.value = canvas.toDataURL('image/jpeg', 0.7);
                };
                img.src = e.target.result;
            };

            reader.readAsDataURL(file);
            break;
        }
    }
}
</script>
```

### Submitting the Form

Include the base64 string in your Axios/Inertia payload:

```js
axios.post('/your-route', {
    // ...other fields
    screenshot: imageBase64.value,
});
```

---

## Backend (Laravel)

### Migration

```php
$table->longText('screenshot')->nullable();
```

> Use `LONGTEXT` — holds up to 4GB, safe for base64 images.

### Controller

```php
// Store as-is (includes data URI prefix: "data:image/jpeg;base64,...")
$model->screenshot = $request->input('screenshot');
$model->save();

// OR strip the prefix before saving
$base64 = preg_replace('/^data:image\/\w+;base64,/', '', $request->input('screenshot'));
$model->screenshot = $base64;
$model->save();
```

### Validation

```php
public function rules(): array
{
    return [
        'screenshot' => ['nullable', 'string'],
    ];
}
```

---

## Rendering the Image

If you stored the full data URI:

```vue
<img :src="record.screenshot" />
```

If you stripped the prefix before saving, reconstruct it:

```vue
<img :src="`data:image/jpeg;base64,${record.screenshot}`" />
```

---

## Storage Tip

If many records will have screenshots, monitor DB size growth.
JPEG at 70% quality typically reduces a PNG screenshot by 60–80%,
making base64 storage practical for moderate usage.

All 4 migration files created. Here's a quick summary of where LONGTEXT was applied:

qc_analysis — 10 image columns (mold, reli, dipping, reflow, measure — before/after pairs)
vi_technical — 10 defect image columns (defect_image1 through defect_image10)
qc_inspection and qc_ok — no image columns, so no LONGTEXT needed there

Run them with:
php artisan migrate --path=database/migrations/2026_04_03_000001_create_qc_inspection_table.php
php artisan migrate --path=database/migrations/2026_04_03_000002_create_qc_analysis_table.php
php artisan migrate --path=database/migrations/2026_04_03_000003_create_vi_technical_table.php
php artisan migrate --path=database/migrations/2026_04_03_000004_create_qc_ok_table.php
