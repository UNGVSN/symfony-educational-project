# Twig Core Concepts

Deep dive into essential Twig concepts for Symfony templating.

---

## Table of Contents

1. [Twig Syntax and Delimiters](#twig-syntax-and-delimiters)
2. [Variables and Data Types](#variables-and-data-types)
3. [Expressions and Operators](#expressions-and-operators)
4. [Filters Deep Dive](#filters-deep-dive)
5. [Functions Deep Dive](#functions-deep-dive)
6. [Control Flow](#control-flow)
7. [Template Inheritance Strategy](#template-inheritance-strategy)
8. [Template Inclusion Patterns](#template-inclusion-patterns)
9. [Macros as Reusable Components](#macros-as-reusable-components)
10. [The Twig Context](#the-twig-context)
11. [Auto-Escaping Mechanisms](#auto-escaping-mechanisms)
12. [Form Rendering Architecture](#form-rendering-architecture)
13. [Extension System](#extension-system)

---

## Twig Syntax and Delimiters

### Three Core Delimiters

Twig uses three types of delimiters for different purposes:

```twig
{# 1. Comments - Never rendered #}
{# This is a comment and won't appear in output #}

{#
   Multi-line comments
   for documentation
   and notes
#}

{# 2. Output - Prints values #}
{{ variable }}
{{ expression }}
{{ function() }}

{# 3. Tags - Control flow and logic #}
{% if condition %}
{% for item in items %}
{% set variable = value %}
```

### Whitespace Control

Twig provides fine-grained control over whitespace:

```twig
{# Normal - whitespace preserved #}
<div>
    {% if true %}
        content
    {% endif %}
</div>
{# Output:
<div>

        content

</div>
#}

{# Strip whitespace before (-) #}
<div>
    {%- if true %}
        content
    {% endif -%}
</div>
{# Output: <div>content</div> #}

{# Practical use case: clean lists #}
<ul>
    {%- for item in items %}
    <li>{{ item }}</li>
    {%- endfor %}
</ul>
```

The `-` symbol can be used on either side:
- `{%-` removes whitespace before the tag
- `-%}` removes whitespace after the tag
- `{{-` and `-}}` work similarly for output delimiters

---

## Variables and Data Types

### Accessing Variables

Twig provides multiple ways to access data:

```twig
{# Simple variables #}
{{ username }}
{{ count }}

{# Array access - two syntaxes #}
{{ items[0] }}
{{ items['key'] }}

{# Object properties - multiple methods #}
{{ user.name }}          {# Most common #}
{{ user['name'] }}       {# Array-like access #}
{{ user.getName() }}     {# Method call #}
{{ attribute(user, 'name') }}  {# Dynamic access #}
```

### Property Access Order

When you use `{{ user.name }}`, Twig attempts in this order:
1. Array key: `$user['name']`
2. Object property: `$user->name`
3. Object method: `$user->name()`
4. Object getter: `$user->getName()`
5. Object isser: `$user->isName()`
6. Object hasser: `$user->hasName()`

### Null-Safe Operator (Twig 3.2+)

```twig
{# Old way - causes error if user is null #}
{{ user.profile.address.city }}

{# New way - returns null if any part is null #}
{{ user?.profile?.address?.city }}

{# With default value #}
{{ user?.profile?.address?.city ?? 'Unknown' }}
```

### Variable Scope

```twig
{# Global scope #}
{% set global = 'accessible everywhere' %}

{# Block scope #}
{% if condition %}
    {% set local = 'only inside if' %}
    {{ global }}  {# Works #}
    {{ local }}   {# Works #}
{% endif %}
{{ local }}  {# Error - undefined #}

{# Loop scope #}
{% for item in items %}
    {% set loopVar = item.name %}
    {{ loopVar }}  {# Works inside loop #}
{% endfor %}
{{ loopVar }}  {# Error - undefined #}

{# Include scope #}
{% include 'partial.html.twig' %}
{# Variables from partial are NOT available here #}
```

---

## Expressions and Operators

### String Operators

```twig
{# Concatenation with ~ #}
{{ 'Hello' ~ ' ' ~ 'World' }}  {# Hello World #}
{{ firstName ~ ' ' ~ lastName }}

{# Why ~ instead of +? #}
{# In Twig, + is only for numbers, ~ is for strings #}
{{ 'Number: ' ~ 42 }}  {# Number: 42 #}
{{ '10' ~ '20' }}      {# 1020 #}
{{ 10 + 20 }}          {# 30 #}
```

### Comparison Operators

```twig
{# Equality #}
{{ value == 10 }}
{{ value != 10 }}
{{ value === 10 }}  {# Strict equality (Twig 3.x) #}

{# Relational #}
{{ age > 18 }}
{{ age >= 18 }}
{{ age < 65 }}
{{ age <= 65 }}

{# Spaceship operator (Twig 3.x) #}
{{ 1 <=> 2 }}   {# -1 (less than) #}
{{ 2 <=> 2 }}   {# 0 (equal) #}
{{ 3 <=> 2 }}   {# 1 (greater than) #}
```

### Logical Operators

```twig
{# AND #}
{{ user and user.isActive }}
{{ condition1 and condition2 }}

{# OR #}
{{ isAdmin or isModerator }}
{{ value1 or value2 }}

{# NOT #}
{{ not user.isBanned }}
{{ not (condition1 and condition2) }}

{# Operator precedence #}
{{ a or b and c }}  {# Same as: a or (b and c) #}
{{ (a or b) and c }}  {# Explicit grouping #}
```

### Containment Operators

```twig
{# in - check if value is in collection #}
{{ 'admin' in user.roles }}
{{ 5 in [1, 2, 3, 4, 5] }}
{{ 'key' in {key: 'value'} }}

{# starts with #}
{{ filename starts with 'IMG_' }}
{{ user.role starts with 'ROLE_' }}

{# ends with #}
{{ filename ends with '.jpg' }}
{{ email ends with '@example.com' }}
```

### Ternary Operator

```twig
{# Basic ternary #}
{{ user ? user.name : 'Guest' }}
{{ status == 'active' ? 'Active User' : 'Inactive' }}

{# Shortened version (if truthy, show value, else alternative) #}
{{ user.name ?: 'Anonymous' }}

{# Null coalescing (if defined and not null) #}
{{ user.name ?? 'Default Name' }}
```

### Math Operators

```twig
{# Basic arithmetic #}
{{ 10 + 5 }}    {# 15 #}
{{ 10 - 5 }}    {# 5 #}
{{ 10 * 5 }}    {# 50 #}
{{ 10 / 5 }}    {# 2 #}
{{ 10 % 3 }}    {# 1 (modulo) #}
{{ 2 ** 8 }}    {# 256 (power) #}

{# Floor division #}
{{ 10 // 3 }}   {# 3 #}

{# Complex expressions #}
{{ (price * quantity) * (1 + taxRate) }}
{{ total - (total * discountPercent / 100) }}
```

---

## Filters Deep Dive

### Filter Chaining

Filters are applied left to right:

```twig
{# Each filter processes the output of the previous one #}
{{ name|lower|capitalize }}
{# 1. name = "JOHN DOE" #}
{# 2. |lower = "john doe" #}
{# 3. |capitalize = "John doe" #}

{# Complex chain #}
{{ description|striptags|truncate(100)|nl2br|raw }}
{# 1. Remove HTML tags #}
{# 2. Truncate to 100 chars #}
{# 3. Convert newlines to <br> #}
{# 4. Output as raw HTML #}
```

### Filter Arguments

```twig
{# Single argument #}
{{ text|truncate(100) }}

{# Multiple arguments #}
{{ number|number_format(2, ',', ' ') }}

{# Named arguments #}
{{ text|truncate(length=100, preserve=true, separator='...') }}

{# Mix of both #}
{{ text|truncate(100, true, '...') }}
```

### Custom Context Filters

Some filters depend on context:

```twig
{# date filter uses configured timezone #}
{{ post.createdAt|date('Y-m-d H:i:s') }}

{# trans filter uses current locale #}
{{ 'hello.message'|trans }}

{# asset filter uses asset base URL #}
{{ 'images/logo.png'|asset }}
```

### Performance Considerations

```twig
{# Bad: Filter in loop #}
{% for user in users %}
    {{ user.bio|markdown|striptags }}  {# Expensive operation repeated #}
{% endfor %}

{# Better: Filter once before passing to template #}
{# In controller: #}
{# $users = array_map(fn($u) => [ #}
{#     'name' => $u->getName(), #}
{#     'bio' => $markdown->parse($u->getBio()) #}
{# ], $users); #}

{# Or: Cache in entity #}
{% for user in users %}
    {{ user.bioHtml|raw }}  {# Pre-processed #}
{% endfor %}
```

---

## Functions Deep Dive

### Path vs URL Functions

```twig
{# path() - relative URL #}
{{ path('blog_show', {id: 1}) }}
{# Output: /blog/1 #}

{# url() - absolute URL #}
{{ url('blog_show', {id: 1}) }}
{# Output: https://example.com/blog/1 #}

{# When to use which? #}
{# path() - Internal links, forms, redirects #}
<a href="{{ path('blog_index') }}">Blog</a>

{# url() - External contexts (emails, RSS, API responses) #}
{# email template: #}
Visit your profile: {{ url('user_profile') }}
```

### Include Function vs Tag

```twig
{# include tag - clearer syntax #}
{% include 'partial.html.twig' %}

{# include function - can be used in expressions #}
{{ include('partial.html.twig') }}

{# Conditional include with function #}
{{ condition ? include('template1.html.twig') : include('template2.html.twig') }}

{# Array of templates (first found wins) #}
{{ include(['custom.html.twig', 'default.html.twig']) }}
```

### Asset Function

```twig
{# Basic asset #}
<link rel="stylesheet" href="{{ asset('css/app.css') }}">

{# With package (versioning strategy) #}
{{ asset('css/app.css', 'v1') }}

{# Absolute URL for assets #}
{{ absolute_url(asset('images/logo.png')) }}

{# In email templates #}
<img src="{{ absolute_url(asset('images/header.jpg')) }}" alt="Header">
```

### Range Function

```twig
{# Numeric ranges #}
{% for i in range(1, 10) %}
    {{ i }}  {# 1, 2, 3, ..., 10 #}
{% endfor %}

{# With step #}
{% for i in range(0, 100, 10) %}
    {{ i }}  {# 0, 10, 20, ..., 100 #}
{% endfor %}

{# Character ranges #}
{% for letter in range('a', 'z') %}
    {{ letter }}
{% endfor %}

{# Reverse range #}
{% for i in range(10, 1) %}
    {{ i }}  {# 10, 9, 8, ..., 1 #}
{% endfor %}

{# Creating arrays #}
{% set numbers = range(1, 5) %}  {# [1, 2, 3, 4, 5] #}
```

### Constant Function

```twig
{# Access class constants #}
{{ constant('App\\Entity\\Post::STATUS_PUBLISHED') }}

{# Access global constants #}
{{ constant('PHP_VERSION') }}
{{ constant('DATE_W3C') }}

{# Use in comparisons #}
{% if post.status == constant('App\\Entity\\Post::STATUS_PUBLISHED') %}
    Published
{% endif %}

{# Dynamic constant access #}
{{ constant('STATUS_' ~ statusName, post) }}
```

---

## Control Flow

### If Statement Patterns

```twig
{# Guard clause pattern #}
{% if not user %}
    <p>Please log in</p>
    {% return %}
{% endif %}

{# Rest of template only executes for logged-in users #}

{# Multiple conditions #}
{% if user.isActive and user.emailVerified and not user.isBanned %}
    Full access
{% elseif user.isActive %}
    Partial access - please verify email
{% else %}
    No access
{% endif %}

{# Negation #}
{% if not user.isBlocked %}
    Welcome!
{% endif %}
```

### For Loop Advanced Usage

```twig
{# Loop with filter #}
{% for user in users if user.isActive %}
    {{ user.name }}
{% endfor %}

{# Loop with key #}
{% for key, value in config %}
    {{ key }}: {{ value }}
{% endfor %}

{# Nested loops with parent reference #}
{% for category in categories %}
    <h2>{{ category.name }}</h2>
    {% for product in category.products %}
        {{ loop.parent.loop.index }}.{{ loop.index }} - {{ product.name }}
    {% endfor %}
{% endfor %}

{# Loop else (for empty collections) #}
{% for item in items %}
    <li>{{ item }}</li>
{% else %}
    <li>No items found</li>
{% endfor %}
```

### Loop Variable Properties

```twig
{% for user in users %}
    {# Index (1-based) #}
    {{ loop.index }}       {# 1, 2, 3, ... #}

    {# Index (0-based) #}
    {{ loop.index0 }}      {# 0, 1, 2, ... #}

    {# Remaining iterations (1-based) #}
    {{ loop.revindex }}    {# N, N-1, N-2, ..., 1 #}

    {# Remaining iterations (0-based) #}
    {{ loop.revindex0 }}   {# N-1, N-2, ..., 1, 0 #}

    {# Boolean flags #}
    {{ loop.first }}       {# true on first iteration #}
    {{ loop.last }}        {# true on last iteration #}

    {# Collection length #}
    {{ loop.length }}      {# Total items #}

    {# Parent loop context #}
    {{ loop.parent }}
{% endfor %}

{# Practical examples #}
{% for item in items %}
    <li class="{{ loop.first ? 'first' }} {{ loop.last ? 'last' }}">
        Item {{ loop.index }} of {{ loop.length }}
    </li>
{% endfor %}

{# Add separator between items #}
{% for tag in tags %}
    {{ tag }}{% if not loop.last %}, {% endif %}
{% endfor %}
```

### Apply Block

```twig
{# Apply filter to entire block #}
{% apply upper %}
    This entire block will be uppercase.
    Multiple lines are supported.
{% endapply %}

{# Multiple filters #}
{% apply upper|escape %}
    Content here
{% endapply %}

{# Practical example: markdown #}
{% apply markdown_to_html %}
# Heading

This is **markdown** content.

- Item 1
- Item 2
{% endapply %}

{# Spaceless #}
{% apply spaceless %}
    <div>
        <strong>No whitespace</strong>
    </div>
{% endapply %}
{# Output: <div><strong>No whitespace</strong></div> #}
```

---

## Template Inheritance Strategy

### Single Inheritance Chain

```twig
{# templates/base.html.twig - Root layout #}
<!DOCTYPE html>
<html>
<head>
    <title>{% block title %}Default Title{% endblock %}</title>
    {% block stylesheets %}{% endblock %}
</head>
<body>
    {% block body %}{% endblock %}
</body>
</html>

{# templates/blog/base.html.twig - Blog section layout #}
{% extends 'base.html.twig' %}

{% block title %}Blog - {{ parent() }}{% endblock %}

{% block body %}
    <div class="blog-container">
        {% block blog_content %}{% endblock %}
    </div>
{% endblock %}

{# templates/blog/show.html.twig - Specific page #}
{% extends 'blog/base.html.twig' %}

{% block title %}{{ post.title }} - {{ parent() }}{% endblock %}

{% block blog_content %}
    <article>
        <h1>{{ post.title }}</h1>
        {{ post.content|raw }}
    </article>
{% endblock %}
```

### Multiple Layout Strategy

```twig
{# templates/base.html.twig - Base #}
<!DOCTYPE html>
<html>
<head>
    <title>{% block title %}{% endblock %}</title>
</head>
<body>
    {% block body %}{% endblock %}
</body>
</html>

{# templates/layout/sidebar.html.twig - Sidebar layout #}
{% extends 'base.html.twig' %}

{% block body %}
    <div class="container">
        <aside>{% block sidebar %}{% endblock %}</aside>
        <main>{% block content %}{% endblock %}</main>
    </div>
{% endblock %}

{# templates/layout/full_width.html.twig - Full width layout #}
{% extends 'base.html.twig' %}

{% block body %}
    <div class="container-fluid">
        {% block content %}{% endblock %}
    </div>
{% endblock %}

{# Usage - choose layout per page #}
{% extends 'layout/sidebar.html.twig' %}
{# or #}
{% extends 'layout/full_width.html.twig' %}
```

### Parent Block Reference

```twig
{# Base template #}
{% block stylesheets %}
    <link rel="stylesheet" href="{{ asset('css/base.css') }}">
{% endblock %}

{# Child template - ADD to parent #}
{% block stylesheets %}
    {{ parent() }}  {# Include parent's stylesheets #}
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
{% endblock %}

{# Child template - REPLACE parent #}
{% block stylesheets %}
    <link rel="stylesheet" href="{{ asset('css/custom-only.css') }}">
{% endblock %}
```

### Dynamic Inheritance

```twig
{# Choose layout based on variable #}
{% extends layout|default('base.html.twig') %}

{# Controller passes layout variable #}
return $this->render('page/show.html.twig', [
    'layout' => 'layout/admin.html.twig',
]);

{# Conditional layout #}
{% extends user.isAdmin ? 'layout/admin.html.twig' : 'layout/user.html.twig' %}
```

---

## Template Inclusion Patterns

### Include vs Embed vs Use

```twig
{# INCLUDE - Simple insertion #}
{% include 'header.html.twig' %}
{# Renders header as-is, can pass variables #}

{# EMBED - Include with block overrides #}
{% embed 'card.html.twig' %}
    {% block card_title %}Custom Title{% endblock %}
{% endembed %}
{# Can customize blocks within included template #}

{# USE - Import blocks #}
{% use 'blocks.html.twig' %}
{# Makes blocks available, doesn't render anything #}
```

### Include Patterns

```twig
{# Basic include #}
{% include 'partials/menu.html.twig' %}

{# Include with variables #}
{% include 'partials/user_card.html.twig' with {user: currentUser} %}

{# Include with ONLY specified variables (no global context) #}
{% include 'partials/user_card.html.twig' with {user: currentUser} only %}

{# Ignore if missing #}
{% include 'optional.html.twig' ignore missing %}

{# Dynamic include #}
{% include 'components/' ~ componentType ~ '.html.twig' %}

{# First found from array #}
{% include [
    'theme/custom_header.html.twig',
    'theme/header.html.twig',
    'default/header.html.twig'
] %}
```

### Embed Pattern

```twig
{# templates/components/panel.html.twig #}
<div class="panel">
    <div class="panel-header">
        {% block panel_header %}Default Header{% endblock %}
    </div>
    <div class="panel-body">
        {% block panel_body %}{% endblock %}
    </div>
    <div class="panel-footer">
        {% block panel_footer %}{% endblock %}
    </div>
</div>

{# Usage with embed #}
{% embed 'components/panel.html.twig' %}
    {% block panel_header %}
        <h3>{{ product.name }}</h3>
    {% endblock %}

    {% block panel_body %}
        <p>{{ product.description }}</p>
        <p class="price">${{ product.price }}</p>
    {% endblock %}

    {% block panel_footer %}
        <button>Add to Cart</button>
    {% endblock %}
{% endembed %}
```

### Use Pattern (Horizontal Reuse)

```twig
{# templates/blocks/alerts.html.twig #}
{% block success_alert %}
    <div class="alert alert-success">{{ message }}</div>
{% endblock %}

{% block error_alert %}
    <div class="alert alert-danger">{{ message }}</div>
{% endblock %}

{# templates/page.html.twig #}
{% extends 'base.html.twig' %}
{% use 'blocks/alerts.html.twig' %}

{% block content %}
    {{ block('success_alert', {message: 'Success!'}) }}
    {{ block('error_alert', {message: 'Error occurred'}) }}
{% endblock %}
```

---

## Macros as Reusable Components

### Macro Definition

```twig
{# templates/macros/forms.html.twig #}

{# Simple macro #}
{% macro input(name, value = '', type = 'text') %}
    <input type="{{ type }}" name="{{ name }}" value="{{ value|e }}" />
{% endmacro %}

{# Macro with complex logic #}
{% macro form_field(field, options = {}) %}
    {% set options = {
        'class': 'form-control',
        'required': false,
        'help': null
    }|merge(options) %}

    <div class="form-group">
        <label for="{{ field.name }}">
            {{ field.label }}
            {% if options.required %}<span class="required">*</span>{% endif %}
        </label>

        <input
            type="{{ field.type }}"
            id="{{ field.name }}"
            name="{{ field.name }}"
            class="{{ options.class }}"
            {% if options.required %}required{% endif %}
        />

        {% if options.help %}
            <small class="form-text">{{ options.help }}</small>
        {% endif %}
    </div>
{% endmacro %}
```

### Macro Import Strategies

```twig
{# Strategy 1: Import all as namespace #}
{% import 'macros/forms.html.twig' as forms %}
{{ forms.input('username') }}
{{ forms.input('password', '', 'password') }}

{# Strategy 2: Import specific macros #}
{% from 'macros/forms.html.twig' import input, button %}
{{ input('email') }}
{{ button('Submit') }}

{# Strategy 3: Import from self (same file) #}
{% macro local_macro() %}...{% endmacro %}
{% import _self as local %}
{{ local.local_macro() }}

{# Strategy 4: Import in parent, use in children #}
{# base.html.twig #}
{% import 'macros/ui.html.twig' as ui %}
{% block content %}{% endblock %}

{# child.html.twig #}
{% extends 'base.html.twig' %}
{% block content %}
    {{ ui.alert('Hello!') }}  {# Works! #}
{% endblock %}
```

### Macro Best Practices

```twig
{# Use named arguments for clarity #}
{{ forms.input(name='email', type='email', placeholder='Enter email') }}

{# Default values for flexibility #}
{% macro alert(message, type = 'info', dismissible = false) %}
    <div class="alert alert-{{ type }} {{ dismissible ? 'alert-dismissible' : '' }}">
        {{ message }}
        {% if dismissible %}
            <button type="button" class="close">&times;</button>
        {% endif %}
    </div>
{% endmacro %}

{# Merge defaults pattern #}
{% macro component(options = {}) %}
    {% set options = {
        'class': '',
        'id': '',
        'data': {}
    }|merge(options) %}

    <div
        {% if options.id %}id="{{ options.id }}"{% endif %}
        class="component {{ options.class }}"
        {% for key, value in options.data %}
            data-{{ key }}="{{ value }}"
        {% endfor %}
    >
        {# Component content #}
    </div>
{% endmacro %}
```

### Recursive Macros

```twig
{# templates/macros/menu.html.twig #}
{% macro render_menu(items, level = 0) %}
    <ul class="menu-level-{{ level }}">
    {% for item in items %}
        <li>
            <a href="{{ item.url }}">{{ item.title }}</a>

            {# Recursive call for children #}
            {% if item.children %}
                {{ _self.render_menu(item.children, level + 1) }}
            {% endif %}
        </li>
    {% endfor %}
    </ul>
{% endmacro %}

{# Usage #}
{% import 'macros/menu.html.twig' as menu %}
{{ menu.render_menu(menuTree) }}
```

---

## The Twig Context

### Understanding Context

The Twig context is the set of variables available in a template:

```twig
{# Variables passed from controller #}
{{ post }}      {# From render() call #}
{{ user }}      {# From render() call #}

{# Global variables #}
{{ app.user }}      {# Symfony app variable #}
{{ app.request }}   {# Always available #}

{# Variables set in template #}
{% set local = 'value' %}
{{ local }}
```

### Context Inheritance

```twig
{# Parent template #}
{% set parentVar = 'from parent' %}
{% block content %}
    {{ parentVar }}  {# Available in parent #}
{% endblock %}

{# Child template #}
{% extends 'parent.html.twig' %}
{% block content %}
    {{ parentVar }}  {# Also available in child! #}
    {% set childVar = 'from child' %}
{% endblock %}
```

### Include Context

```twig
{# Default: include gets all variables #}
{% set globalVar = 'available' %}
{% include 'partial.html.twig' %}
{# partial.html.twig can access globalVar #}

{# With specific variables #}
{% include 'partial.html.twig' with {specific: 'value'} %}
{# partial.html.twig has globalVar AND specific #}

{# Only specific variables #}
{% include 'partial.html.twig' with {specific: 'value'} only %}
{# partial.html.twig ONLY has 'specific', not globalVar #}
```

### Macro Context

```twig
{# Macros don't have access to template context #}
{% set templateVar = 'from template' %}

{% macro show_var() %}
    {{ templateVar }}  {# ERROR: undefined #}
{% endmacro %}

{# Must pass variables explicitly #}
{% macro show_var(var) %}
    {{ var }}  {# Works #}
{% endmacro %}

{% import _self as macros %}
{{ macros.show_var(templateVar) }}  {# Pass explicitly #}
```

---

## Auto-Escaping Mechanisms

### Escaping Contexts

Twig uses different escaping strategies for different contexts:

```twig
{# HTML context (default) #}
{{ userInput }}
{# Escapes: < > & " ' #}
{# Input: <script>alert('xss')</script> #}
{# Output: &lt;script&gt;alert(&#039;xss&#039;)&lt;/script&gt; #}

{# JavaScript context #}
<script>
    var name = "{{ userName|escape('js') }}";
    {# Escapes: quotes, backslashes, newlines, etc. #}
</script>

{# CSS context #}
<style>
    .user-color { color: {{ userColor|escape('css') }}; }
</style>

{# URL context #}
<a href="{{ baseUrl|escape('url') }}">Link</a>

{# HTML attribute context #}
<div title="{{ userTitle|escape('html_attr') }}">Content</div>
```

### Auto-Escape Blocks

```twig
{# Change strategy for block #}
{% autoescape 'js' %}
    var data = "{{ userData }}";  {# JS-escaped #}
{% endautoescape %}

{% autoescape 'html' %}
    <p>{{ content }}</p>  {# HTML-escaped (default anyway) #}
{% endautoescape %}

{% autoescape false %}
    {{ trustedContent }}  {# NOT escaped - dangerous! #}
{% endautoescape %}
```

### Raw Filter Usage

```twig
{# When to use raw #}

{# 1. Trusted HTML from database (e.g., CMS content) #}
{{ page.content|raw }}

{# 2. HTML from markdown parser #}
{{ post.body|markdown|raw }}

{# 3. Pre-escaped content #}
{{ alreadyEscapedHtml|raw }}

{# NEVER use raw with user input! #}
{# DANGEROUS: #}
{{ comment.text|raw }}  {# XSS vulnerability! #}

{# SAFE: #}
{{ comment.text }}  {# Escaped by default #}
```

### Escaping in Different Contexts

```twig
{# HTML content #}
<div>{{ userContent }}</div>  {# Auto-escaped #}

{# HTML attributes #}
<div class="{{ userClass }}">  {# Auto-escaped for attributes #}
<div data-value="{{ userValue|e('html_attr') }}">  {# Explicit #}

{# JavaScript strings #}
<script>
    const message = "{{ userMessage|e('js') }}";
    const data = {{ userData|json_encode|raw }};  {# JSON is safe #}
</script>

{# URLs #}
<a href="/search?q={{ searchQuery|url_encode }}">Search</a>

{# CSS #}
<style>
    .custom { color: {{ userColor|e('css') }}; }
</style>
```

---

## Form Rendering Architecture

### Form Rendering Layers

Twig provides multiple layers for form rendering:

```twig
{# Layer 1: Complete form #}
{{ form(form) }}
{# Renders: form_start + form_widget + form_end #}

{# Layer 2: Form parts #}
{{ form_start(form) }}
{{ form_widget(form) }}    {# All fields #}
{{ form_end(form) }}

{# Layer 3: Individual fields #}
{{ form_start(form) }}
{{ form_row(form.name) }}     {# label + widget + errors #}
{{ form_row(form.email) }}
{{ form_end(form) }}

{# Layer 4: Field components #}
{{ form_start(form) }}
    {{ form_label(form.name) }}
    {{ form_widget(form.name) }}
    {{ form_errors(form.name) }}
    {{ form_help(form.name) }}
{{ form_end(form) }}
```

### Form Functions

```twig
{# form_start - opening tag with attributes #}
{{ form_start(form, {
    'attr': {'class': 'my-form', 'novalidate': 'novalidate'},
    'action': path('form_submit'),
    'method': 'POST'
}) }}

{# form_widget - render input #}
{{ form_widget(form.email, {
    'attr': {'class': 'form-control', 'placeholder': 'Email'}
}) }}

{# form_label - render label #}
{{ form_label(form.name, 'Your Name', {
    'label_attr': {'class': 'required'}
}) }}

{# form_errors - render errors #}
{{ form_errors(form.email) }}

{# form_help - render help text #}
{{ form_help(form.password) }}

{# form_row - complete field #}
{{ form_row(form.username, {
    'label': 'Username',
    'attr': {'class': 'form-control'},
    'help': 'Choose a unique username'
}) }}

{# form_rest - remaining fields (CSRF, hidden) #}
{{ form_rest(form) }}

{# form_end - closing tag #}
{{ form_end(form) }}
```

### Form Themes

```twig
{# Apply theme to entire form #}
{% form_theme form 'bootstrap_5_layout.html.twig' %}
{{ form(form) }}

{# Multiple themes (first found widget template wins) #}
{% form_theme form 'custom_theme.html.twig' 'bootstrap_5_layout.html.twig' %}

{# Apply theme to specific field #}
{% form_theme form.email 'custom_email.html.twig' %}

{# Inline theme customization #}
{% form_theme form _self %}

{% block _user_email_widget %}
    <div class="email-widget-wrapper">
        {{ form_widget(form) }}
        <span class="icon">@</span>
    </div>
{% endblock %}

{{ form(form) }}
```

### Form Variables

```twig
{# Access form field properties #}
{{ form.email.vars.id }}           {# Field ID #}
{{ form.email.vars.full_name }}    {# user[email] #}
{{ form.email.vars.value }}        {# Current value #}
{{ form.email.vars.label }}        {# Label text #}
{{ form.email.vars.required }}     {# Is required? #}
{{ form.email.vars.disabled }}     {# Is disabled? #}
{{ form.email.vars.errors }}       {# Error messages #}
{{ form.email.vars.valid }}        {# Is valid? #}
{{ form.email.vars.attr }}         {# HTML attributes #}

{# Conditional rendering #}
{% if form.username.vars.required %}
    <span class="required">*</span>
{% endif %}

{% if not form.email.vars.valid %}
    <div class="error">Please fix errors</div>
{% endif %}
```

---

## Extension System

### Extension Components

A Twig extension can provide:

1. **Filters** - Transform values
2. **Functions** - Generate content
3. **Tests** - Boolean checks
4. **Operators** - Custom operators
5. **Tags** - Custom syntax

### Creating a Filter Extension

```php
// src/Twig/Extension/TextExtension.php
namespace App\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TextExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            // Basic filter
            new TwigFilter('rot13', [$this, 'rot13']),

            // Filter that outputs HTML
            new TwigFilter('highlight', [$this, 'highlight'], [
                'is_safe' => ['html']
            ]),

            // Filter that needs environment
            new TwigFilter('custom_escape', [$this, 'customEscape'], [
                'needs_environment' => true
            ]),
        ];
    }

    public function rot13(string $text): string
    {
        return str_rot13($text);
    }

    public function highlight(string $text, string $term): string
    {
        return str_replace(
            $term,
            "<mark>{$term}</mark>",
            $text
        );
    }

    public function customEscape($env, $string): string
    {
        return twig_escape_filter($env, $string, 'html');
    }
}
```

### Creating a Function Extension

```php
namespace App\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class UtilityExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            // Simple function
            new TwigFunction('random_color', [$this, 'randomColor']),

            // Function that outputs HTML
            new TwigFunction('icon', [$this, 'icon'], [
                'is_safe' => ['html']
            ]),

            // Function that needs context
            new TwigFunction('current_user_name', [$this, 'getUserName'], [
                'needs_context' => true
            ]),
        ];
    }

    public function randomColor(): string
    {
        return sprintf('#%06X', mt_rand(0, 0xFFFFFF));
    }

    public function icon(string $name, string $class = ''): string
    {
        return sprintf(
            '<i class="icon icon-%s %s"></i>',
            $name,
            $class
        );
    }

    public function getUserName(array $context): string
    {
        return $context['app']->getUser()?->getName() ?? 'Guest';
    }
}
```

### Creating a Test Extension

```php
namespace App\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigTest;

class TestExtension extends AbstractExtension
{
    public function getTests(): array
    {
        return [
            new TwigTest('email', [$this, 'isEmail']),
            new TwigTest('url', [$this, 'isUrl']),
            new TwigTest('json', [$this, 'isJson']),
        ];
    }

    public function isEmail($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function isUrl($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    public function isJson($value): bool
    {
        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
```

```twig
{# Usage #}
{% if email is email %}
    Valid email
{% endif %}

{% if website is url %}
    Valid URL
{% endif %}
```

### Runtime Extensions (Lazy Loading)

```php
// src/Twig/Runtime/HeavyOperationRuntime.php
namespace App\Twig\Runtime;

use App\Service\HeavyService;
use Twig\Extension\RuntimeExtensionInterface;

class HeavyOperationRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private HeavyService $heavyService,
    ) {}

    public function processData($data): string
    {
        // This service is only instantiated if the filter is actually used
        return $this->heavyService->process($data);
    }
}

// src/Twig/Extension/HeavyExtension.php
namespace App\Twig\Extension;

use App\Twig\Runtime\HeavyOperationRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class HeavyExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('heavy_process', [HeavyOperationRuntime::class, 'processData']),
        ];
    }
}
```

This completes the comprehensive concepts guide for Twig templating in Symfony.
