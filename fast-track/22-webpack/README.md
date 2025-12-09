# Chapter 22: Styling with Webpack Encore

Learn to manage CSS, JavaScript, and other assets efficiently using Symfony AssetMapper or Webpack Encore.

---

## Learning Objectives

By the end of this chapter, you will:
- Understand the difference between AssetMapper and Webpack Encore
- Configure AssetMapper for modern JavaScript and CSS
- Set up Webpack Encore for advanced asset compilation
- Import and bundle CSS files
- Work with JavaScript modules and dependencies

---

## Prerequisites

- Completed Chapter 21 (Caching)
- Node.js and npm installed (for Encore)
- Basic understanding of CSS and JavaScript
- Familiarity with package managers

---

## Concepts

### AssetMapper vs Webpack Encore

**AssetMapper** (Symfony 6.3+):
- Modern, simpler approach for asset management
- No build step required
- Uses native browser ES modules
- Perfect for most applications

**Webpack Encore**:
- Advanced asset compilation and bundling
- Supports Sass, Less, TypeScript
- Code splitting and optimization
- Better for complex frontend needs

---

## Step 1: Using AssetMapper (Recommended for New Projects)

### Install AssetMapper

```bash
composer require symfony/asset-mapper symfony/asset symfony/twig-pack
```

### Configure AssetMapper

AssetMapper is configured in `config/packages/asset_mapper.yaml`:

```yaml
# config/packages/asset_mapper.yaml
framework:
    asset_mapper:
        paths:
            - assets/
        excluded_patterns:
            - */node_modules/*
```

### Directory Structure

```
assets/
├── app.js          # Main JavaScript file
├── styles/
│   └── app.css     # Main CSS file
└── images/
    └── logo.png
```

---

## Step 2: Add CSS Styles

Create your main stylesheet:

```css
/* assets/styles/app.css */
:root {
    --primary-color: #3498db;
    --secondary-color: #2ecc71;
    --danger-color: #e74c3c;
    --text-color: #2c3e50;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    line-height: 1.6;
    color: var(--text-color);
    margin: 0;
    padding: 0;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

.btn {
    display: inline-block;
    padding: 0.75rem 1.5rem;
    background-color: var(--primary-color);
    color: white;
    text-decoration: none;
    border-radius: 4px;
    transition: background-color 0.3s;
}

.btn:hover {
    background-color: #2980b9;
}

.card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    padding: 1.5rem;
    margin-bottom: 1rem;
}
```

---

## Step 3: Add JavaScript

Create your main JavaScript file:

```javascript
// assets/app.js
import './styles/app.css';

// Example: Add interactivity
document.addEventListener('DOMContentLoaded', () => {
    console.log('App initialized!');

    // Add smooth scrolling
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });

    // Add form validation feedback
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', (e) => {
            if (!form.checkValidity()) {
                e.preventDefault();
                form.classList.add('was-validated');
            }
        });
    });
});
```

---

## Step 4: Include Assets in Templates

Update your base template to include the assets:

```twig
{# templates/base.html.twig #}
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{% block title %}Welcome!{% endblock %}</title>

        {% block stylesheets %}
            <link rel="stylesheet" href="{{ asset('styles/app.css') }}">
        {% endblock %}

        {% block javascripts %}
            {{ importmap('app') }}
        {% endblock %}
    </head>
    <body>
        {% block body %}{% endblock %}
    </body>
</html>
```

---

## Step 5: Using Webpack Encore (Alternative Approach)

### Install Encore

```bash
composer require symfony/webpack-encore-bundle
npm install
```

### Configure Encore

```javascript
// webpack.config.js
const Encore = require('@symfony/webpack-encore');

Encore
    // Directory where compiled assets will be stored
    .setOutputPath('public/build/')

    // Public path used by the web server to access the output path
    .setPublicPath('/build')

    // Entry points
    .addEntry('app', './assets/app.js')

    // Enable features
    .splitEntryChunks()
    .enableSingleRuntimeChunk()
    .cleanupOutputBeforeBuild()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())

    // Enable Sass/SCSS support
    // .enableSassLoader()

    // Enable PostCSS support
    .enablePostCssLoader()

    // Enable TypeScript
    // .enableTypeScriptLoader()
;

module.exports = Encore.getWebpackConfig();
```

### Build Assets

```bash
# Development build
npm run dev

# Watch for changes
npm run watch

# Production build
npm run build
```

---

## Step 6: Add Third-Party Libraries

### Using AssetMapper

```bash
# Install package
php bin/console importmap:require bootstrap

# Or manually add to importmap.php
```

```php
// importmap.php
return [
    'app' => [
        'path' => './assets/app.js',
        'entrypoint' => true,
    ],
    'bootstrap' => [
        'version' => '5.3.0',
    ],
    'bootstrap/dist/css/bootstrap.min.css' => [
        'version' => '5.3.0',
        'type' => 'css',
    ],
];
```

### Using Webpack Encore

```bash
# Install via npm
npm install bootstrap @popperjs/core

# Import in your JavaScript
```

```javascript
// assets/app.js
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap';
import './styles/app.css';
```

---

## Step 7: Working with Images

### Reference Images in CSS

```css
/* assets/styles/app.css */
.logo {
    background-image: url('../images/logo.png');
    width: 200px;
    height: 60px;
    background-size: contain;
    background-repeat: no-repeat;
}
```

### Reference Images in Twig

```twig
{# Using AssetMapper or Encore #}
<img src="{{ asset('images/logo.png') }}" alt="Logo">
```

### Optimize Images with Encore

```javascript
// webpack.config.js
Encore
    .copyFiles({
        from: './assets/images',
        to: 'images/[path][name].[hash:8].[ext]',
    })
;
```

---

## Step 8: Advanced CSS Techniques

### Using CSS Variables

```css
/* assets/styles/app.css */
:root {
    --spacing-unit: 8px;
    --border-radius: 4px;
    --transition-speed: 0.3s;
}

.card {
    padding: calc(var(--spacing-unit) * 2);
    border-radius: var(--border-radius);
    transition: all var(--transition-speed);
}

@media (prefers-color-scheme: dark) {
    :root {
        --text-color: #ecf0f1;
        --bg-color: #2c3e50;
    }
}
```

### Component-Based Styling

```css
/* assets/styles/components/button.css */
.btn {
    /* Base button styles */
}

.btn-primary {
    background-color: var(--primary-color);
}

.btn-secondary {
    background-color: var(--secondary-color);
}
```

Import in main CSS:

```css
/* assets/styles/app.css */
@import './components/button.css';
@import './components/card.css';
@import './components/form.css';
```

---

## Step 9: JavaScript Modules

### Create Reusable Modules

```javascript
// assets/utils/api.js
export async function fetchConferences() {
    const response = await fetch('/api/conferences');
    if (!response.ok) {
        throw new Error('Failed to fetch conferences');
    }
    return response.json();
}

export async function submitComment(conferenceId, data) {
    const response = await fetch(`/api/conferences/${conferenceId}/comments`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data),
    });
    return response.json();
}
```

### Use Modules

```javascript
// assets/app.js
import { fetchConferences } from './utils/api';

document.addEventListener('DOMContentLoaded', async () => {
    try {
        const conferences = await fetchConferences();
        console.log('Conferences loaded:', conferences);
    } catch (error) {
        console.error('Error loading conferences:', error);
    }
});
```

---

## Step 10: Performance Optimization

### Code Splitting with Encore

```javascript
// webpack.config.js
Encore
    .addEntry('app', './assets/app.js')
    .addEntry('admin', './assets/admin.js')
    .splitEntryChunks()
;
```

### Lazy Loading

```javascript
// assets/app.js
document.getElementById('load-admin').addEventListener('click', async () => {
    const { initAdmin } = await import('./admin/init.js');
    initAdmin();
});
```

### Preloading Assets

```twig
{# templates/base.html.twig #}
<link rel="preload" href="{{ asset('styles/app.css') }}" as="style">
<link rel="preload" href="{{ asset('app.js') }}" as="script">
```

---

## Key Concepts Covered

1. **AssetMapper**: Modern asset management without build step
2. **Webpack Encore**: Advanced asset compilation and bundling
3. **CSS Organization**: Variables, imports, and component-based styling
4. **JavaScript Modules**: ES6 imports/exports and code organization
5. **Third-Party Libraries**: Integration of external dependencies
6. **Performance**: Code splitting, lazy loading, and optimization
7. **Development Workflow**: Dev vs production builds, watching for changes

---

## Common Issues and Solutions

### Issue: Assets Not Loading

```bash
# Clear cache
php bin/console cache:clear

# For AssetMapper, check importmap
php bin/console debug:asset-map

# For Encore, rebuild
npm run build
```

### Issue: CSS Not Updating

```bash
# For AssetMapper - hard refresh browser (Ctrl+Shift+R)

# For Encore - ensure watch is running
npm run watch
```

### Issue: JavaScript Errors in Browser

- Check browser console for errors
- Verify import paths are correct
- Ensure all dependencies are installed

---

## Exercises

### Exercise 1: Create a Dark Mode Toggle

Create a dark mode toggle that saves user preference:

```javascript
// assets/utils/theme.js
export function initTheme() {
    const toggle = document.getElementById('theme-toggle');
    const currentTheme = localStorage.getItem('theme') || 'light';

    document.documentElement.setAttribute('data-theme', currentTheme);

    toggle?.addEventListener('click', () => {
        const theme = document.documentElement.getAttribute('data-theme');
        const newTheme = theme === 'light' ? 'dark' : 'light';

        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
    });
}
```

```css
/* assets/styles/app.css */
[data-theme="dark"] {
    --text-color: #ecf0f1;
    --bg-color: #2c3e50;
    --card-bg: #34495e;
}

[data-theme="light"] {
    --text-color: #2c3e50;
    --bg-color: #ecf0f1;
    --card-bg: white;
}

body {
    background-color: var(--bg-color);
    color: var(--text-color);
}
```

### Exercise 2: Add a Component Library

Install and configure Tailwind CSS or Bootstrap with AssetMapper:

```bash
php bin/console importmap:require bootstrap
php bin/console importmap:require @popperjs/core
```

### Exercise 3: Create an Interactive Component

Build a modal dialog using vanilla JavaScript:

```javascript
// assets/components/modal.js
export class Modal {
    constructor(elementId) {
        this.modal = document.getElementById(elementId);
        this.closeBtn = this.modal?.querySelector('.modal-close');
        this.init();
    }

    init() {
        this.closeBtn?.addEventListener('click', () => this.close());
        this.modal?.addEventListener('click', (e) => {
            if (e.target === this.modal) this.close();
        });
    }

    open() {
        this.modal?.classList.add('active');
    }

    close() {
        this.modal?.classList.remove('active');
    }
}
```

---

## Questions

1. What is the main difference between AssetMapper and Webpack Encore?
2. How do you import a CSS file in JavaScript?
3. What command builds assets for production with Encore?
4. How do you add a third-party library with AssetMapper?
5. What is code splitting and why is it useful?

### Answers

1. AssetMapper uses native browser ES modules without a build step, while Webpack Encore compiles and bundles assets. AssetMapper is simpler but Encore offers more advanced features.

2. Use ES6 import: `import './styles/app.css';`

3. `npm run build` (which runs `encore production`)

4. Use `php bin/console importmap:require package-name` or manually add to `importmap.php`

5. Code splitting divides your application into smaller chunks that can be loaded on demand, improving initial page load time and overall performance.

---

## Next Step

Proceed to [Chapter 23: Resizing Images](../23-images/README.md) to learn about image processing and optimization.
