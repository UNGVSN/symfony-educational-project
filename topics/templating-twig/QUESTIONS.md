# Twig Practice Questions

Test your knowledge of Twig templating with these practice questions. Answers are provided at the bottom.

---

## Questions

### Question 1: Basic Syntax

What is the output of this template?

```twig
{% set name = 'Alice' %}
{% set age = 25 %}
{{ name }} is {{ age }} years old
{# This is a comment #}
```

**a)** `Alice is 25 years old`
**b)** `Alice is 25 years old {# This is a comment #}`
**c)** Error: undefined variable
**d)** `name is age years old`

---

### Question 2: String Concatenation

Which of the following correctly concatenates strings in Twig?

```twig
{% set firstName = 'John' %}
{% set lastName = 'Doe' %}
```

**a)** `{{ firstName + ' ' + lastName }}`
**b)** `{{ firstName ~ ' ' ~ lastName }}`
**c)** `{{ concat(firstName, ' ', lastName) }}`
**d)** `{{ firstName . ' ' . lastName }}`

---

### Question 3: Filters

What will this output if `price = 1234.567`?

```twig
{{ price|number_format(2, '.', ',') }}
```

**a)** `1234.57`
**b)** `1,234.57`
**c)** `1.234,57`
**d)** `1234.567`

---

### Question 4: Date Filter

Given `createdAt` is a DateTime object with value `2025-12-08 14:30:00`, what does this output?

```twig
{{ createdAt|date('Y-m-d') }}
```

**a)** `2025-12-08`
**b)** `12-08-2025`
**c)** `08/12/2025`
**d)** `Y-m-d`

---

### Question 5: Default Filter

What is the output if `username` is `null`?

```twig
{{ username|default('Guest') }}
```

**a)** `null`
**b)** `Guest`
**c)** Empty string
**d)** Error

---

### Question 6: Loop Variable

What does `loop.index0` represent in a Twig for loop?

```twig
{% for item in items %}
    {{ loop.index0 }}
{% endfor %}
```

**a)** Current iteration starting from 1
**b)** Current iteration starting from 0
**c)** Remaining iterations from end
**d)** Total number of items

---

### Question 7: Template Inheritance

In this template structure, what gets rendered?

```twig
{# base.html.twig #}
<html>
<title>{% block title %}Default{% endblock %}</title>
</html>

{# page.html.twig #}
{% extends 'base.html.twig' %}
{% block title %}My Page - {{ parent() }}{% endblock %}
```

**a)** `<title>My Page</title>`
**b)** `<title>Default</title>`
**c)** `<title>My Page - Default</title>`
**d)** Error: parent() not defined

---

### Question 8: Auto-Escaping

What is the output if `userInput = '<script>alert("xss")</script>'`?

```twig
{{ userInput }}
```

**a)** `<script>alert("xss")</script>` (executes JavaScript)
**b)** `&lt;script&gt;alert("xss")&lt;/script&gt;` (escaped HTML)
**c)** Empty string
**d)** Error

---

### Question 9: Raw Filter

When should you use the `|raw` filter?

**a)** Always, to improve performance
**b)** Only with trusted HTML content
**c)** With all user input
**d)** Never, it's deprecated

---

### Question 10: Include vs Embed

What's the difference between `{% include %}` and `{% embed %}`?

**a)** No difference, they're aliases
**b)** `include` inserts content; `embed` allows block overrides
**c)** `embed` is faster than `include`
**d)** `include` is for templates, `embed` is for macros

---

### Question 11: Path Function

What does the `path()` function generate?

```twig
<a href="{{ path('blog_show', {id: 5}) }}">View Post</a>
```

**a)** Absolute URL: `https://example.com/blog/5`
**b)** Relative path: `/blog/5`
**c)** Route name: `blog_show`
**d)** Error: missing parameters

---

### Question 12: Array Filter

Given `items = [1, 2, 3, 4, 5]`, what is the output?

```twig
{{ items|slice(1, 3)|join(', ') }}
```

**a)** `1, 2, 3`
**b)** `2, 3, 4`
**c)** `2, 3`
**d)** `3, 4, 5`

---

### Question 13: Null-Safe Operator

What happens if `user` is `null`?

```twig
{# Twig 3.2+ #}
{{ user?.profile?.email ?? 'no-email@example.com' }}
```

**a)** Error: trying to access property of null
**b)** Output: `no-email@example.com`
**c)** Output: `null`
**d)** Output: empty string

---

### Question 14: For-Else

What is the output if `items = []` (empty array)?

```twig
{% for item in items %}
    <li>{{ item }}</li>
{% else %}
    <li>No items found</li>
{% endfor %}
```

**a)** Nothing
**b)** `<li></li>`
**c)** `<li>No items found</li>`
**d)** Error

---

### Question 15: Macro Definition

What's wrong with this macro usage?

```twig
{% macro button(text) %}
    <button>{{ text }}</button>
{% endmacro %}

{{ button('Click me') }}
```

**a)** Nothing, it works correctly
**b)** Macros must be imported before use
**c)** Macro parameters must have default values
**d)** Macros can't output HTML

---

### Question 16: Global Variables

Which global variable provides access to the current user in Symfony?

**a)** `{{ session.user }}`
**b)** `{{ request.user }}`
**c)** `{{ app.user }}`
**d)** `{{ global.user }}`

---

### Question 17: Form Rendering

What does `form_row()` render?

```twig
{{ form_row(form.email) }}
```

**a)** Only the input field
**b)** Only the label
**c)** Label + input + errors + help
**d)** The entire form

---

### Question 18: Conditional Filter

What is the output if `status = 'active'`?

```twig
{{ status == 'active' ? 'Online' : 'Offline' }}
```

**a)** `active`
**b)** `Online`
**c)** `Offline`
**d)** `true`

---

### Question 19: Block Function

What does this output in a child template?

```twig
{# base.html.twig #}
{% block content %}Default content{% endblock %}

{# child.html.twig #}
{% extends 'base.html.twig' %}
{% block content %}
    New content
    {{ parent() }}
{% endblock %}
```

**a)** `Default content`
**b)** `New content`
**c)** `New content Default content`
**d)** Error

---

### Question 20: Whitespace Control

What is the output?

```twig
<div>
    {%- if true -%}
        content
    {%- endif -%}
</div>
```

**a)** `<div> content </div>`
**b)** `<div>content</div>`
**c)** `<div>\n    content\n</div>`
**d)** `<div> content</div>`

---

## Advanced Questions

### Question 21: Complex Template Code

What is the output of this template?

```twig
{% set items = ['apple', 'banana', 'cherry'] %}
{% for item in items %}
    {{ loop.index }}. {{ item|upper }}{% if not loop.last %}, {% endif %}
{% endfor %}
```

**a)** `1. APPLE, 2. BANANA, 3. CHERRY,`
**b)** `1. APPLE, 2. BANANA, 3. CHERRY`
**c)** `0. apple, 1. banana, 2. cherry`
**d)** `APPLE, BANANA, CHERRY`

---

### Question 22: Filter Chaining

Given `text = '  HELLO WORLD  '`, what is the output?

```twig
{{ text|trim|lower|capitalize }}
```

**a)** `  HELLO WORLD  `
**b)** `Hello world`
**c)** `HELLO WORLD`
**d)** `hello world`

---

### Question 23: Escaping Context

What's the correct way to escape in JavaScript context?

```twig
<script>
    var message = "{{ userMessage }}";
</script>
```

**a)** `var message = "{{ userMessage }}";` (as shown)
**b)** `var message = "{{ userMessage|escape('js') }}";`
**c)** `var message = "{{ userMessage|raw }}";`
**d)** `var message = {{ userMessage|json_encode }};`

---

### Question 24: Include with Context

What variables are available in `partial.html.twig`?

```twig
{% set globalVar = 'global' %}
{% set anotherVar = 'another' %}
{% include 'partial.html.twig' with {localVar: 'local'} only %}
```

**a)** `globalVar`, `anotherVar`, and `localVar`
**b)** Only `localVar`
**c)** Only `globalVar` and `anotherVar`
**d)** All template variables plus `localVar`

---

### Question 25: Macro with Named Arguments

How do you call this macro with named arguments?

```twig
{% macro alert(message, type = 'info', dismissible = false) %}
    <div class="alert alert-{{ type }}">{{ message }}</div>
{% endmacro %}

{% import _self as ui %}
```

**a)** `{{ ui.alert('Hello', 'success', true) }}`
**b)** `{{ ui.alert(message='Hello', type='success', dismissible=true) }}`
**c)** `{{ ui.alert('Hello', type: 'success', dismissible: true) }}`
**d)** Both a and b are correct

---

### Question 26: Loop Performance

Which approach is more efficient?

```twig
{# Approach A #}
{% for user in users %}
    {{ user.name|upper }}
{% endfor %}

{# Approach B #}
{% set processedUsers = users|map(u => u.name|upper) %}
{% for name in processedUsers %}
    {{ name }}
{% endfor %}
```

**a)** Approach A - simpler code
**b)** Approach B - filter applied once
**c)** No difference
**d)** Both are bad; should process in controller

---

### Question 27: Template Embedding

What does this render?

```twig
{# card.html.twig #}
<div class="card">
    {% block card_content %}Default{% endblock %}
</div>

{# usage #}
{% embed 'card.html.twig' %}
    {% block card_content %}Custom{% endblock %}
{% endembed %}
```

**a)** `<div class="card">Default</div>`
**b)** `<div class="card">Custom</div>`
**c)** `<div class="card">Default Custom</div>`
**d)** Error: can't override blocks in embed

---

### Question 28: Asset Function

What's the purpose of the `asset()` function?

```twig
<img src="{{ asset('images/logo.png') }}">
```

**a)** Optimize image size
**b)** Generate correct public path with versioning
**c)** Validate file exists
**d)** Convert image format

---

### Question 29: Form Theme

What does `{% form_theme form 'bootstrap_5_layout.html.twig' %}` do?

**a)** Changes form validation rules
**b)** Changes form rendering templates
**c)** Changes form action URL
**d)** Changes form field types

---

### Question 30: Custom Test

Given this custom test:

```php
// In extension
new TwigTest('even', fn($num) => $num % 2 === 0)
```

How do you use it in Twig?

**a)** `{{ even(number) }}`
**b)** `{{ number|even }}`
**c)** `{% if number is even %}`
**d)** `{% if even number %}`

---

## Code Analysis Questions

### Question 31: Debug This Template

What's wrong with this template?

```twig
{% extends 'base.html.twig' %}

{% set pageTitle = 'My Page' %}

{% block content %}
    <h1>{{ pageTitle }}</h1>
{% endblock %}

{% block title %}{{ pageTitle }}{% endblock %}
```

**a)** Nothing, it's correct
**b)** `set` must be inside a block
**c)** Can't use variables in `title` block
**d)** Blocks are in wrong order

---

### Question 32: Security Issue

Identify the security vulnerability:

```twig
{# Display user comment #}
<div class="comment">
    {{ comment.text|nl2br|raw }}
</div>
```

**a)** No vulnerability
**b)** XSS vulnerability - unescaped user input
**c)** SQL injection
**d)** CSRF vulnerability

---

### Question 33: Performance Problem

What's inefficient about this code?

```twig
{% for product in products %}
    <h3>{{ product.name }}</h3>
    <p>Category: {{ product.category.name }}</p>
    <p>Total products in category: {{ product.category.products|length }}</p>
{% endfor %}
```

**a)** Nothing, it's optimal
**b)** N+1 query problem if not properly loaded
**c)** Too many filters
**d)** Should use macros instead

---

### Question 34: Logic Error

What's the logical error?

```twig
{% if user and user.isActive and user.role == 'admin' or user.role == 'moderator' %}
    Admin panel
{% endif %}
```

**a)** No error
**b)** Operator precedence - moderators always see panel
**c)** Can't use multiple `and` operators
**d)** Should use `===` instead of `==`

---

### Question 35: Macro Scope Issue

Why doesn't this work?

```twig
{% set globalVar = 'global value' %}

{% macro show_global() %}
    {{ globalVar }}
{% endmacro %}

{% import _self as m %}
{{ m.show_global() }}
```

**a)** Works correctly
**b)** Macros don't have access to template variables
**c)** Must use `{% use %}` instead of `{% import %}`
**d)** Need to call with `{{ m.show_global(globalVar) }}`

---

## Scenario-Based Questions

### Question 36: E-commerce Product List

You need to display products in a 3-column grid. Which approach is best?

```twig
{# Approach A #}
{% for product in products %}
    {% if loop.index0 % 3 == 0 %}<div class="row">{% endif %}
        <div class="col">{{ product.name }}</div>
    {% if loop.index0 % 3 == 2 %}</div>{% endif %}
{% endfor %}

{# Approach B #}
{% for row in products|batch(3) %}
    <div class="row">
        {% for product in row %}
            <div class="col">{{ product.name }}</div>
        {% endfor %}
    </div>
{% endfor %}
```

**a)** Approach A - more control
**b)** Approach B - cleaner and safer
**c)** Both are equally good
**d)** Neither, should use JavaScript

---

### Question 37: Multi-Language Site

How do you make this template translation-ready?

```twig
<h1>Welcome to our site</h1>
<p>Please log in to continue</p>
```

**a)** Keep as-is, manually translate files
**b)** `<h1>{{ trans('welcome.title') }}</h1>`
**c)** `<h1>{% trans %}welcome.title{% endtrans %}</h1>`
**d)** Both b and c work

---

### Question 38: Dynamic Navigation

Build a navigation menu with active state:

```twig
{% set currentRoute = app.request.get('_route') %}
{% set menuItems = [
    {route: 'home', label: 'Home'},
    {route: 'about', label: 'About'},
    {route: 'contact', label: 'Contact'}
] %}
```

What's the best implementation?

**a)**
```twig
{% for item in menuItems %}
    <a href="{{ path(item.route) }}"
       class="{% if currentRoute == item.route %}active{% endif %}">
        {{ item.label }}
    </a>
{% endfor %}
```

**b)**
```twig
{% for item in menuItems %}
    <a href="{{ path(item.route) }}"
       class="{{ currentRoute == item.route ? 'active' : '' }}">
        {{ item.label }}
    </a>
{% endfor %}
```

**c)** Both a and b are correct
**d)** Should use JavaScript for active state

---

### Question 39: Form with CSRF

Complete this delete form with CSRF protection:

```twig
<form method="post" action="{{ path('post_delete', {id: post.id}) }}">
    {# What goes here? #}
    <button type="submit">Delete</button>
</form>
```

**a)** `<input type="hidden" name="_csrf" value="{{ csrf_token() }}">`
**b)** `<input type="hidden" name="_token" value="{{ csrf_token('delete-post-' ~ post.id) }}">`
**c)** `{{ form_csrf(post.id) }}`
**d)** CSRF not needed for delete operations

---

### Question 40: Email Template

Which is correct for an email template that needs absolute URLs?

```twig
{# For links in email #}
<a href="{{ path('user_profile') }}">View Profile</a>

{# For images in email #}
<img src="{{ asset('images/logo.png') }}">
```

**a)** Correct as-is
**b)** Should use `url()` and `absolute_url(asset())`
**c)** Should use `external_url()` function
**d)** Email templates can't have links

---

## Answers

### Answers 1-10

1. **a)** `Alice is 25 years old` - Comments are not rendered in output.

2. **b)** `{{ firstName ~ ' ' ~ lastName }}` - Twig uses `~` for string concatenation.

3. **b)** `1,234.57` - `number_format(2, '.', ',')` means 2 decimals, `.` as decimal point, `,` as thousands separator.

4. **a)** `2025-12-08` - The date filter formats DateTime objects using PHP date format.

5. **b)** `Guest` - The default filter provides fallback value for null/undefined variables.

6. **b)** Current iteration starting from 0 - `index0` is zero-based, `index` is one-based.

7. **c)** `<title>My Page - Default</title>` - `parent()` includes the parent block's content.

8. **b)** `&lt;script&gt;alert("xss")&lt;/script&gt;` - Twig auto-escapes output by default.

9. **b)** Only with trusted HTML content - Using `|raw` with user input creates XSS vulnerabilities.

10. **b)** `include` inserts content; `embed` allows block overrides - `embed` is like `include` but lets you customize blocks.

### Answers 11-20

11. **b)** Relative path: `/blog/5` - `path()` generates relative URLs. Use `url()` for absolute URLs.

12. **b)** `2, 3, 4` - `slice(1, 3)` takes 3 elements starting from index 1.

13. **b)** Output: `no-email@example.com` - Null-safe operator returns null if any part is null, then `??` provides default.

14. **c)** `<li>No items found</li>` - The `else` clause executes when array is empty.

15. **b)** Macros must be imported before use - Need `{% import _self as macros %}` then `{{ macros.button('Click me') }}`.

16. **c)** `{{ app.user }}` - The `app` global variable provides access to user, request, session, etc.

17. **c)** Label + input + errors + help - `form_row()` renders the complete field.

18. **b)** `Online` - Ternary operator: condition ? true_value : false_value.

19. **c)** `New content Default content` - Child content is rendered first, then `parent()` includes base content.

20. **b)** `<div>content</div>` - The `-` removes whitespace before/after tags.

### Answers 21-30

21. **b)** `1. APPLE, 2. BANANA, 3. CHERRY` - `loop.index` is 1-based, `if not loop.last` prevents trailing comma.

22. **b)** `Hello world` - Filters chain: trim removes spaces → lower converts to lowercase → capitalize capitalizes first letter.

23. **b)** `var message = "{{ userMessage|escape('js') }}";` - JavaScript context requires JS escaping. Option d with json_encode is also acceptable without quotes.

24. **b)** Only `localVar` - The `only` keyword restricts context to specified variables only.

25. **d)** Both a and b are correct - Can use positional or named arguments.

26. **d)** Both are bad; should process in controller - Keep business logic out of templates for better performance and maintainability.

27. **b)** `<div class="card">Custom</div>` - Embed allows overriding blocks in the embedded template.

28. **b)** Generate correct public path with versioning - The `asset()` function handles public directory paths and asset versioning.

29. **b)** Changes form rendering templates - Form themes control how form fields are rendered.

30. **c)** `{% if number is even %}` - Custom tests are used with the `is` operator.

### Answers 31-35

31. **a)** Nothing, it's correct - Variables set outside blocks are available in all blocks.

32. **b)** XSS vulnerability - unescaped user input - User comments should never use `|raw`. Use `{{ comment.text|nl2br }}` (escaped by default) instead.

33. **b)** N+1 query problem if not properly loaded - If `category.products` isn't eager-loaded, this creates a query for each product. Solution: eager load in repository or do calculation in controller.

34. **b)** Operator precedence - moderators always see panel - Due to operator precedence, this is evaluated as: `(user and user.isActive and user.role == 'admin') or user.role == 'moderator'`. Should be: `user and user.isActive and (user.role == 'admin' or user.role == 'moderator')`.

35. **b)** Macros don't have access to template variables - Macros have isolated scope. Must pass variables as parameters: `{{ m.show_global(globalVar) }}`.

### Answers 36-40

36. **b)** Approach B - cleaner and safer - The `batch` filter is designed for this use case and handles edge cases better.

37. **d)** Both b and c work - Twig provides both `{{ 'key'|trans }}` filter and `{% trans %}key{% endtrans %}` tag for translations.

38. **c)** Both a and b are correct - Both syntax variants work correctly. Choose based on team preference.

39. **b)** `<input type="hidden" name="_token" value="{{ csrf_token('delete-post-' ~ post.id) }}">` - CSRF token requires unique identifier matching the validation in controller.

40. **b)** Should use `url()` and `absolute_url(asset())` - Email clients need absolute URLs:
```twig
<a href="{{ url('user_profile') }}">View Profile</a>
<img src="{{ absolute_url(asset('images/logo.png')) }}">
```

---

## Scoring Guide

- **36-40 correct**: Expert level - You master Twig!
- **30-35 correct**: Advanced level - Very strong knowledge
- **24-29 correct**: Intermediate level - Good understanding
- **18-23 correct**: Basic level - Keep practicing
- **Below 18**: Review the concepts and try again

---

## Key Takeaways

1. **Auto-escaping is default** - Protect against XSS by default
2. **Use `~` for concatenation** - Not `+` or `.`
3. **Template inheritance is powerful** - Use `extends`, `block`, and `parent()`
4. **Macros have isolated scope** - Must pass variables explicitly
5. **`path()` vs `url()`** - Relative vs absolute URLs
6. **Keep logic in controllers** - Templates for presentation only
7. **`|raw` is dangerous** - Only use with trusted content
8. **Form functions** - `form_start`, `form_row`, `form_end`
9. **Global variables** - `app.user`, `app.request`, etc.
10. **Filters chain left to right** - Each processes previous result
