# Exercise 01: Build a Reusable Component Library with Macros

Learn to create reusable UI components using Twig macros.

---

## Objective

Create a comprehensive library of reusable UI components using Twig macros that can be used across your application. This exercise will teach you how to build modular, maintainable templates.

---

## Learning Goals

- Understand macro syntax and parameters
- Create reusable UI components
- Use named arguments for flexibility
- Implement recursive macros
- Organize macro libraries effectively
- Apply best practices for component design

---

## Prerequisites

- Basic Twig syntax knowledge
- Understanding of template inclusion
- Familiarity with HTML/CSS
- Basic Symfony project setup

---

## Tasks

### Task 1: Create Form Component Macros

Create a file `templates/macros/forms.html.twig` with the following macros:

#### 1.1 Input Field Macro
Create a macro that renders an input field with:
- Customizable input type (text, email, password, etc.)
- Label support
- Placeholder text
- CSS classes
- Required indicator
- Help text
- Error display

```twig
{% macro input(name, options = {}) %}
    {# Your implementation here #}
{% endmacro %}
```

**Expected usage:**
```twig
{{ forms.input('email', {
    label: 'Email Address',
    type: 'email',
    required: true,
    placeholder: 'Enter your email',
    help: 'We will never share your email',
    class: 'form-control'
}) }}
```

#### 1.2 Textarea Macro
Create a textarea macro with:
- Rows configuration
- Character counter (optional)
- All features from input macro

```twig
{% macro textarea(name, options = {}) %}
    {# Your implementation here #}
{% endmacro %}
```

#### 1.3 Select Dropdown Macro
Create a select macro that:
- Accepts array of options
- Supports option groups
- Handles selected value
- Allows multiple selection

```twig
{% macro select(name, choices, options = {}) %}
    {# Your implementation here #}
{% endmacro %}
```

**Expected usage:**
```twig
{{ forms.select('country', countries, {
    label: 'Country',
    selected: 'US',
    placeholder: 'Choose a country'
}) }}
```

#### 1.4 Button Macro
Create a button macro with:
- Different types (submit, button, reset)
- Various styles (primary, secondary, danger, etc.)
- Size options (small, medium, large)
- Icon support
- Disabled state

```twig
{% macro button(text, options = {}) %}
    {# Your implementation here #}
{% endmacro %}
```

---

### Task 2: Create UI Component Macros

Create a file `templates/macros/ui.html.twig` with these macros:

#### 2.1 Alert Component
Create an alert macro with:
- Different types (success, error, warning, info)
- Dismissible option
- Icon support
- Custom message

```twig
{% macro alert(message, options = {}) %}
    {# Your implementation here #}
{% endmacro %}
```

**Expected output:**
```html
<div class="alert alert-success alert-dismissible">
    <i class="icon-check"></i>
    <span>Operation successful!</span>
    <button type="button" class="close">&times;</button>
</div>
```

#### 2.2 Card Component
Create a card macro with:
- Header section
- Body content
- Footer section
- Image support
- Different variants (bordered, shadow, etc.)

```twig
{% macro card(options = {}) %}
    {# Your implementation here #}
{% endmacro %}
```

#### 2.3 Badge Component
Create a badge macro for:
- Different colors
- Pills or rounded badges
- Size variants

```twig
{% macro badge(text, options = {}) %}
    {# Your implementation here #}
{% endmacro %}
```

#### 2.4 Pagination Component
Create a pagination macro that:
- Displays page numbers
- Shows previous/next buttons
- Highlights current page
- Handles edge cases (first/last page)

```twig
{% macro pagination(currentPage, totalPages, route, options = {}) %}
    {# Your implementation here #}
{% endmacro %}
```

**Expected usage:**
```twig
{{ ui.pagination(3, 10, 'blog_index') }}
```

---

### Task 3: Create Navigation Macros

Create a file `templates/macros/navigation.html.twig`:

#### 3.1 Breadcrumb Macro
Create a breadcrumb navigation:
- Accept array of items (label, url)
- Highlight current page
- Support icons
- Handle home link

```twig
{% macro breadcrumb(items, options = {}) %}
    {# Your implementation here #}
{% endmacro %}
```

**Expected usage:**
```twig
{{ nav.breadcrumb([
    {label: 'Home', url: path('homepage')},
    {label: 'Blog', url: path('blog_index')},
    {label: 'Post Title', url: null}
]) }}
```

#### 3.2 Menu Macro (Recursive)
Create a recursive menu macro for nested navigation:
- Support multiple levels
- Active state highlighting
- Dropdown support
- Icons per item

```twig
{% macro menu(items, currentRoute, level = 0) %}
    {# Your implementation here #}
{% endmacro %}
```

**Expected usage:**
```twig
{% set menuItems = [
    {
        label: 'Products',
        route: 'products',
        icon: 'box',
        children: [
            {label: 'Electronics', route: 'products_electronics'},
            {label: 'Clothing', route: 'products_clothing'}
        ]
    },
    {label: 'About', route: 'about'}
] %}

{{ nav.menu(menuItems, app.request.get('_route')) }}
```

---

### Task 4: Create Table Macros

Create a file `templates/macros/table.html.twig`:

#### 4.1 Data Table Macro
Create a table macro that:
- Renders table headers
- Loops through data rows
- Supports sortable columns
- Handles empty data
- Allows custom cell rendering

```twig
{% macro dataTable(columns, data, options = {}) %}
    {# Your implementation here #}
{% endmacro %}
```

**Expected usage:**
```twig
{% set columns = [
    {key: 'name', label: 'Name', sortable: true},
    {key: 'email', label: 'Email'},
    {key: 'created', label: 'Registered', sortable: true}
] %}

{{ table.dataTable(columns, users, {
    striped: true,
    hoverable: true
}) }}
```

---

### Task 5: Create Modal Macro

Create a file `templates/macros/modal.html.twig`:

#### 5.1 Modal Component
Create a modal macro with:
- Header with title
- Body content
- Footer with actions
- Size options (small, large, full)
- Closable option

```twig
{% macro modal(id, options = {}) %}
    {# Your implementation here #}
{% endmacro %}
```

---

### Task 6: Integration and Testing

#### 6.1 Create Demo Page
Create a template `templates/demo/components.html.twig` that:
- Imports all macro libraries
- Demonstrates each component
- Shows different variations
- Documents usage examples

```twig
{% extends 'base.html.twig' %}

{% import 'macros/forms.html.twig' as forms %}
{% import 'macros/ui.html.twig' as ui %}
{% import 'macros/navigation.html.twig' as nav %}
{% import 'macros/table.html.twig' as table %}

{% block content %}
    <h1>Component Library Demo</h1>

    {# Demonstrate each component #}
{% endblock %}
```

#### 6.2 Create Controller
Create a controller to render the demo page:

```php
// src/Controller/ComponentDemoController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ComponentDemoController extends AbstractController
{
    #[Route('/demo/components', name: 'demo_components')]
    public function index(): Response
    {
        return $this->render('demo/components.html.twig', [
            // Sample data for demonstrations
        ]);
    }
}
```

---

## Requirements

### Macro Design Principles

1. **Default Values**: All parameters should have sensible defaults
   ```twig
   {% macro button(text, options = {}) %}
       {% set options = {
           type: 'button',
           style: 'primary',
           size: 'medium'
       }|merge(options) %}
   {% endmacro %}
   ```

2. **Escaping**: Always escape user input properly
   ```twig
   <label>{{ label|e }}</label>
   ```

3. **Flexibility**: Use options hash for maximum flexibility
   ```twig
   {% macro component(required, options = {}) %}
       {# Named parameters in options hash #}
   {% endmacro %}
   ```

4. **Documentation**: Add comments explaining usage
   ```twig
   {#
       Renders an alert message

       Parameters:
       - message: The alert message
       - options: {
           type: 'success'|'error'|'warning'|'info' (default: 'info')
           dismissible: boolean (default: false)
           icon: string|null
       }
   #}
   {% macro alert(message, options = {}) %}
   ```

5. **Consistency**: Follow consistent naming and structure

---

## Expected Output

### Example 1: Form Components
```html
<div class="form-group">
    <label for="email">
        Email Address
        <span class="required">*</span>
    </label>
    <input
        type="email"
        id="email"
        name="email"
        class="form-control"
        placeholder="Enter your email"
        required
    />
    <small class="form-text text-muted">
        We will never share your email
    </small>
</div>
```

### Example 2: Alert Component
```html
<div class="alert alert-success alert-dismissible" role="alert">
    <i class="icon-check-circle"></i>
    <span>Your changes have been saved!</span>
    <button type="button" class="close" data-dismiss="alert">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
```

### Example 3: Pagination
```html
<nav aria-label="Page navigation">
    <ul class="pagination">
        <li class="page-item disabled">
            <a class="page-link" href="#" tabindex="-1">Previous</a>
        </li>
        <li class="page-item"><a class="page-link" href="/blog?page=1">1</a></li>
        <li class="page-item"><a class="page-link" href="/blog?page=2">2</a></li>
        <li class="page-item active">
            <a class="page-link" href="/blog?page=3">3</a>
        </li>
        <li class="page-item"><a class="page-link" href="/blog?page=4">4</a></li>
        <li class="page-item"><a class="page-link" href="/blog?page=5">5</a></li>
        <li class="page-item">
            <a class="page-link" href="/blog?page=4">Next</a>
        </li>
    </ul>
</nav>
```

---

## Testing Checklist

- [ ] All macros have default values for optional parameters
- [ ] Output is properly escaped (no XSS vulnerabilities)
- [ ] Components work with different variations
- [ ] Edge cases are handled (empty arrays, null values, etc.)
- [ ] Recursive macros don't cause infinite loops
- [ ] CSS classes are customizable
- [ ] Components are accessible (ARIA attributes, etc.)
- [ ] Documentation is clear and comprehensive
- [ ] Demo page shows all component variations
- [ ] Code follows Twig best practices

---

## Bonus Challenges

### Challenge 1: Timeline Component
Create a vertical timeline macro that displays events chronologically.

### Challenge 2: Tabs Component
Create a tabs macro that:
- Supports multiple tab panels
- Handles active state
- Works with JavaScript for switching

### Challenge 3: Progress Bar
Create a progress bar macro with:
- Percentage display
- Color variants
- Striped/animated options
- Stacked progress bars

### Challenge 4: Tree View
Create a recursive tree macro for hierarchical data:
- Expandable/collapsible nodes
- Icons for folders/files
- Selection support

---

## Solution Guidelines

### Macro Structure Template
```twig
{#
    Component Description

    @param type name - Description
    @param array options {
        @option type key - Description (default: value)
    }

    Usage:
    {{ macro_name(param, {option: value}) }}
#}
{% macro macro_name(required_param, options = {}) %}
    {# Set defaults #}
    {% set defaults = {
        option1: 'default',
        option2: false
    } %}
    {% set options = defaults|merge(options) %}

    {# Validation if needed #}
    {% if required_param is empty %}
        {# Handle error #}
    {% endif %}

    {# Component HTML #}
    <div class="{{ options.option1 }}">
        {{ required_param|e }}
    </div>
{% endmacro %}
```

---

## Resources

- [Twig Macros Documentation](https://twig.symfony.com/doc/3.x/tags/macro.html)
- [Bootstrap Components](https://getbootstrap.com/docs/5.3/components/)
- [Tailwind UI Components](https://tailwindui.com/components)
- [Material Design Components](https://material.io/components)

---

## Evaluation Criteria

Your solution will be evaluated on:
1. **Functionality** (40%) - All components work as expected
2. **Code Quality** (30%) - Clean, well-organized, follows best practices
3. **Flexibility** (15%) - Components are configurable and reusable
4. **Documentation** (10%) - Clear comments and usage examples
5. **Security** (5%) - Proper escaping and input handling

---

Good luck! This exercise will give you practical experience in building maintainable, reusable Twig components.
