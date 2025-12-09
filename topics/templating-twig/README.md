# Templating with Twig

Master Twig templating for building dynamic, secure, and maintainable templates in Symfony applications.

---

## Learning Objectives

After completing this topic, you will be able to:

- Write Twig templates using proper syntax and conventions
- Use template inheritance to create reusable layouts
- Apply filters and functions to transform and format data
- Implement control structures for conditional and iterative rendering
- Create and use macros for reusable template components
- Access global variables and understand the Twig context
- Implement auto-escaping for secure output
- Integrate forms seamlessly with Twig templates
- Create custom Twig extensions for application-specific functionality
- Optimize template performance and maintainability

---

## Prerequisites

- Symfony Architecture basics
- Controllers and routing fundamentals
- Basic HTML and CSS knowledge
- Understanding of PHP syntax
- HTTP protocol basics

---

## Topics Covered

1. [Twig Syntax Basics](#1-twig-syntax-basics)
2. [Variables and Expressions](#2-variables-and-expressions)
3. [Filters](#3-filters)
4. [Functions](#4-functions)
5. [Control Structures](#5-control-structures)
6. [Template Inheritance](#6-template-inheritance)
7. [Including Templates](#7-including-templates)
8. [Macros](#8-macros)
9. [Global Variables](#9-global-variables)
10. [Auto-Escaping and Security](#10-auto-escaping-and-security)
11. [Forms Integration](#11-forms-integration)
12. [Creating Twig Extensions](#12-creating-twig-extensions)
13. [Best Practices](#13-best-practices)

---

## 1. Twig Syntax Basics

### Three Basic Delimiters

```twig
{# Comments - not included in rendered output #}
{# This is a single-line comment #}

{#
   Multi-line comment
   Useful for documentation
#}

{{ ... }}  {# Output: prints variable or expression #}
{{ user.name }}
{{ 'Hello ' ~ user.name }}
{{ product.price * 1.2 }}

{% ... %}  {# Tags: execute statements #}
{% if user.isActive %}
    Active user
{% endif %}

{% for item in items %}
    {{ item.name }}
{% endfor %}
```

### Whitespace Control

```twig
{# Remove whitespace before tag #}
{%- if true -%}
    content
{%- endif -%}

{# Remove whitespace after tag #}
{{- variable -}}

{# Practical example #}
<ul>
    {% for item in items %}
        <li>{{- item -}}</li>
    {% endfor %}
</ul>
{# Output: <ul><li>item1</li><li>item2</li></ul> #}
```

---

## 2. Variables and Expressions

### Accessing Variables

```twig
{# Simple variable #}
{{ name }}

{# Array access #}
{{ items[0] }}
{{ items['key'] }}

{# Object properties (multiple ways) #}
{{ user.name }}        {# Recommended #}
{{ user['name'] }}
{{ attribute(user, 'name') }}

{# Method calls #}
{{ user.getName() }}
{{ user.isActive() }}

{# Chaining #}
{{ user.profile.address.city }}

{# Null-safe access (Twig 3.2+) #}
{{ user?.profile?.address?.city }}
```

### Setting Variables

```twig
{% set name = 'John' %}
{% set age = 30 %}

{# Multiple variables #}
{% set foo, bar = 'Hello', 'World' %}

{# Expressions #}
{% set total = price * quantity %}
{% set fullName = firstName ~ ' ' ~ lastName %}

{# Arrays #}
{% set items = ['apple', 'banana', 'orange'] %}
{% set user = {
    'name': 'John',
    'email': 'john@example.com',
    'age': 30
} %}

{# Block assignment #}
{% set message %}
    <p>This is a longer message
    that spans multiple lines.</p>
{% endset %}
```

### Operators

```twig
{# Arithmetic #}
{{ 10 + 5 }}    {# 15 #}
{{ 10 - 5 }}    {# 5 #}
{{ 10 * 5 }}    {# 50 #}
{{ 10 / 5 }}    {# 2 #}
{{ 10 % 3 }}    {# 1 #}
{{ 10 ** 2 }}   {# 100 (power) #}

{# Comparison #}
{{ age == 18 }}
{{ age != 18 }}
{{ age > 18 }}
{{ age >= 18 }}
{{ age < 18 }}
{{ age <= 18 }}

{# Logical #}
{{ user and user.isActive }}
{{ user or guest }}
{{ not user.isBlocked }}

{# String concatenation #}
{{ 'Hello ' ~ name }}
{{ firstName ~ ' ' ~ lastName }}

{# Ternary operator #}
{{ user ? user.name : 'Guest' }}
{{ status == 'active' ? 'Active' : 'Inactive' }}

{# Null coalescing #}
{{ user.name ?? 'Anonymous' }}

{# Contains #}
{{ 'admin' in user.roles }}
{{ user.role starts with 'ROLE_' }}
{{ filename ends with '.pdf' }}

{# Matches (regex) #}
{{ email matches '/^[^@]+@[^@]+\.[^@]+$/' }}
```

---

## 3. Filters

Filters transform variables using the pipe (|) operator.

### Common String Filters

```twig
{# Format #}
{{ text|upper }}           {# HELLO WORLD #}
{{ text|lower }}           {# hello world #}
{{ text|capitalize }}      {# Hello world #}
{{ text|title }}           {# Hello World #}

{# Truncate #}
{{ description|length }}                    {# 150 #}
{{ description|slice(0, 100) }}            {# First 100 chars #}
{{ description|truncate(100) }}            {# Truncate with ... #}
{{ description|truncate(100, true) }}      {# Preserve whole words #}
{{ description|truncate(100, false, '...') }}

{# Trim and format #}
{{ text|trim }}            {# Remove whitespace #}
{{ text|trim('.,') }}      {# Remove specific chars #}
{{ slug|replace({'-': ' '})|title }}

{# String manipulation #}
{{ text|reverse }}
{{ url|url_encode }}
{{ html|striptags }}       {# Remove HTML tags #}
{{ text|nl2br }}           {# Convert newlines to <br> #}

{# Formatting #}
{{ text|format('Hello %s!') }}              {# sprintf-style #}
{{ 'I have %d apple'|format(count) }}
```

### Number Filters

```twig
{# Number formatting #}
{{ 1234.5678|number_format }}              {# 1,235 #}
{{ 1234.5678|number_format(2) }}           {# 1,234.57 #}
{{ 1234.5678|number_format(2, ',', ' ') }} {# 1 234,57 #}

{# Rounding #}
{{ 42.7|round }}           {# 43 #}
{{ 42.3|round }}           {# 42 #}
{{ 42.7|round(0, 'floor') }}  {# 42 #}
{{ 42.3|round(0, 'ceil') }}   {# 43 #}

{# Absolute value #}
{{ -42|abs }}              {# 42 #}
```

### Date and Time Filters

```twig
{# Format dates #}
{{ post.createdAt|date }}                    {# Dec 08, 2025 #}
{{ post.createdAt|date('Y-m-d') }}          {# 2025-12-08 #}
{{ post.createdAt|date('F j, Y') }}         {# December 8, 2025 #}
{{ post.createdAt|date('Y-m-d H:i:s') }}    {# 2025-12-08 14:30:00 #}

{# Format with timezone #}
{{ post.createdAt|date('Y-m-d H:i', 'Europe/Paris') }}

{# Relative dates (requires symfony/twig-bridge) #}
{{ post.createdAt|ago }}                     {# 2 hours ago #}
{{ post.createdAt|date_modify('+1 day')|date('Y-m-d') }}
```

### Array and Object Filters

```twig
{# Array operations #}
{{ items|length }}                {# Count #}
{{ items|first }}                 {# First element #}
{{ items|last }}                  {# Last element #}
{{ items|reverse }}               {# Reverse array #}
{{ items|sort }}                  {# Sort ascending #}
{{ items|slice(0, 5) }}          {# Get subset #}

{# Join #}
{{ tags|join(', ') }}            {# tag1, tag2, tag3 #}
{{ ['one', 'two']|join(' and ') }}  {# one and two #}

{# Unique values #}
{{ items|unique }}

{# Merge arrays #}
{{ items|merge(moreItems) }}

{# Keys and values #}
{{ user|keys }}                   {# ['name', 'email', 'age'] #}
{{ user|values }}                 {# ['John', 'john@example.com', 30] #}

{# Column (extract property from objects) #}
{{ users|column('email') }}       {# ['user1@example.com', 'user2@example.com'] #}

{# Filter array #}
{% set activeUsers = users|filter(u => u.active) %}

{# Map array (Twig 3.x) #}
{% set names = users|map(u => u.name) %}

{# Reduce array (Twig 3.x) #}
{% set total = items|reduce((carry, item) => carry + item.price, 0) %}
```

### HTML and Escaping Filters

```twig
{# Escaping #}
{{ userInput|escape }}            {# HTML escaping (default) #}
{{ userInput|e }}                 {# Shorthand #}
{{ userInput|escape('html') }}    {# Explicit HTML #}
{{ userInput|escape('js') }}      {# JavaScript escaping #}
{{ userInput|escape('css') }}     {# CSS escaping #}
{{ userInput|escape('url') }}     {# URL escaping #}
{{ userInput|escape('html_attr') }} {# HTML attribute escaping #}

{# Raw (disable escaping) #}
{{ trustedHtml|raw }}

{# Spaceless (remove whitespace between HTML tags) #}
{{ html|spaceless }}
```

### JSON and Serialization

```twig
{# JSON encoding #}
{{ data|json_encode }}
{{ data|json_encode(constant('JSON_PRETTY_PRINT')) }}

{# Example: Pass data to JavaScript #}
<script>
    const userData = {{ user|json_encode|raw }};
</script>
```

### Other Useful Filters

```twig
{# Default value #}
{{ user.name|default('Guest') }}
{{ user.email|default('no-email@example.com') }}

{# Batch (group items) #}
{% for row in items|batch(3) %}
    <div class="row">
        {% for item in row %}
            <div class="col">{{ item }}</div>
        {% endfor %}
    </div>
{% endfor %}

{# Split #}
{% set parts = 'one,two,three'|split(',') %}

{# Convert encoding #}
{{ text|convert_encoding('UTF-8', 'ISO-8859-1') }}
```

### Chaining Filters

```twig
{# Multiple filters applied left to right #}
{{ description|striptags|truncate(100)|upper }}
{{ user.name|default('Guest')|upper }}
{{ price|number_format(2)|format('$%s') }}

{# Complex example #}
{{ post.content|striptags|truncate(200, true)|nl2br|raw }}
```

---

## 4. Functions

Functions are called to generate content.

### Path and URL Functions

```twig
{# Generate path (relative) #}
<a href="{{ path('blog_show', {id: post.id}) }}">Read more</a>
<a href="{{ path('blog_index') }}">Blog</a>

{# Generate absolute URL #}
<a href="{{ url('blog_show', {id: post.id}) }}">{{ post.title }}</a>

{# Useful for emails, RSS feeds, etc. #}
{{ url('homepage') }}  {# https://example.com/ #}
```

### Asset Functions

```twig
{# Link to assets #}
<link href="{{ asset('css/style.css') }}" rel="stylesheet">
<script src="{{ asset('js/app.js') }}"></script>
<img src="{{ asset('images/logo.png') }}" alt="Logo">

{# With asset versioning #}
{{ asset('css/style.css', 'v1') }}

{# Absolute asset URL #}
{{ absolute_url(asset('images/banner.jpg')) }}
```

### Template Functions

```twig
{# Include template #}
{{ include('partials/_header.html.twig') }}
{{ include('partials/_item.html.twig', {item: product}) }}

{# Include with variables only (no global context) #}
{{ include('partials/_item.html.twig', {item: product}, with_context = false) }}

{# Ignore missing templates #}
{{ include('optional.html.twig', ignore_missing = true) }}

{# Source (get template content as string) #}
{{ source('email/template.txt.twig') }}
```

### Rendering Controllers

```twig
{# Render controller #}
{{ render(controller('App\\Controller\\SidebarController::recent')) }}
{{ render(controller('App\\Controller\\SidebarController::recent', {max: 5})) }}

{# Render with different strategies #}
{{ render_esi(controller('App\\Controller\\SidebarController::recent')) }}
{{ render_hinclude(controller('App\\Controller\\SidebarController::recent')) }}
```

### Block Functions

```twig
{# Check if block is defined #}
{% if block('sidebar') is defined %}
    {{ block('sidebar') }}
{% endif %}

{# Display block #}
{{ block('title') }}

{# Get parent block content #}
{{ parent() }}
```

### Random Function

```twig
{# Random number #}
{{ random() }}              {# Random integer #}
{{ random(100) }}           {# 0 to 100 #}
{{ random(10, 20) }}        {# 10 to 20 #}

{# Random item from array #}
{{ random(colors) }}        {# Random color #}
{{ random(['red', 'green', 'blue']) }}

{# Random character from string #}
{{ random('ABCDEFGHIJ') }}
```

### Range Function

```twig
{# Create array of numbers #}
{% for i in range(1, 5) %}
    {{ i }}  {# 1 2 3 4 5 #}
{% endfor %}

{# With step #}
{% for i in range(0, 10, 2) %}
    {{ i }}  {# 0 2 4 6 8 10 #}
{% endfor %}

{# Letters #}
{% for letter in range('a', 'z') %}
    {{ letter }}
{% endfor %}
```

### Cycle Function

```twig
{# Cycle through values #}
{% for item in items %}
    <div class="{{ cycle(['odd', 'even'], loop.index0) }}">
        {{ item }}
    </div>
{% endfor %}

{# Cycle colors #}
{% for user in users %}
    <div style="background: {{ cycle(['red', 'green', 'blue'], loop.index0) }}">
        {{ user.name }}
    </div>
{% endfor %}
```

### Date Function

```twig
{# Create date #}
{{ date() }}                    {# Current date/time #}
{{ date('now') }}              {# Same as above #}
{{ date('+1 day') }}           {# Tomorrow #}
{{ date('-1 week') }}          {# Last week #}
{{ date('2025-12-31') }}       {# Specific date #}

{# Format created date #}
{{ date('now')|date('Y-m-d H:i:s') }}
```

### Dump Function (Debug)

```twig
{# Debug variable (dev environment only) #}
{{ dump(user) }}
{{ dump(user, post, comments) }}

{# Dump everything #}
{{ dump() }}
```

### Constant Function

```twig
{# Access PHP constants #}
{{ constant('App\\Entity\\Post::STATUS_PUBLISHED') }}
{{ constant('DATE_W3C') }}

{# Class constants #}
{{ constant('STATUS_ACTIVE', post) }}
```

### Max and Min Functions

```twig
{# Find maximum #}
{{ max(1, 3, 2) }}              {# 3 #}
{{ max(prices) }}               {# Highest price #}

{# Find minimum #}
{{ min(1, 3, 2) }}              {# 1 #}
{{ min(prices) }}               {# Lowest price #}
```

---

## 5. Control Structures

### If Statements

```twig
{# Basic if #}
{% if user %}
    Hello {{ user.name }}!
{% endif %}

{# If-else #}
{% if user.isAdmin %}
    <a href="{{ path('admin_dashboard') }}">Admin Panel</a>
{% else %}
    <a href="{{ path('user_dashboard') }}">Dashboard</a>
{% endif %}

{# If-elseif-else #}
{% if user.role == 'admin' %}
    Admin Access
{% elseif user.role == 'moderator' %}
    Moderator Access
{% elseif user.role == 'member' %}
    Member Access
{% else %}
    Guest Access
{% endif %}

{# Complex conditions #}
{% if user and user.isActive and user.emailVerified %}
    Full access granted
{% endif %}

{# Inline if #}
<div class="{{ user.isActive ? 'active' : 'inactive' }}">
```

### For Loops

```twig
{# Basic loop #}
{% for item in items %}
    <li>{{ item }}</li>
{% endfor %}

{# Loop with key #}
{% for key, value in data %}
    <dt>{{ key }}</dt>
    <dd>{{ value }}</dd>
{% endfor %}

{# Empty collections #}
{% for user in users %}
    <li>{{ user.name }}</li>
{% else %}
    <li>No users found</li>
{% endfor %}

{# Loop variable #}
{% for item in items %}
    {{ loop.index }}      {# Current iteration (1-indexed) #}
    {{ loop.index0 }}     {# Current iteration (0-indexed) #}
    {{ loop.revindex }}   {# Iterations from end (1-indexed) #}
    {{ loop.revindex0 }}  {# Iterations from end (0-indexed) #}
    {{ loop.first }}      {# True if first iteration #}
    {{ loop.last }}       {# True if last iteration #}
    {{ loop.length }}     {# Total number of items #}
    {{ loop.parent }}     {# Parent loop context #}
{% endfor %}

{# Practical example with loop variable #}
{% for item in items %}
    <li class="{{ loop.first ? 'first' : '' }} {{ loop.last ? 'last' : '' }}">
        {{ loop.index }}. {{ item }}
    </li>
{% endfor %}

{# Filtered loop #}
{% for user in users if user.active %}
    {{ user.name }}
{% endfor %}

{# Slice loop #}
{% for user in users|slice(0, 10) %}
    {{ user.name }}
{% endfor %}
```

### Set (Variables)

```twig
{# Simple assignment #}
{% set total = 0 %}

{# Inside loop #}
{% set total = 0 %}
{% for item in items %}
    {% set total = total + item.price %}
{% endfor %}
Total: {{ total }}

{# Capture block #}
{% set greeting %}
    <h1>Hello, {{ user.name }}!</h1>
    <p>Welcome back.</p>
{% endset %}

{{ greeting }}
```

### Spaceless

```twig
{# Remove whitespace between HTML tags #}
{% spaceless %}
    <div>
        <strong>Hello</strong>
    </div>
{% endspaceless %}

{# Output: <div><strong>Hello</strong></div> #}
```

### Apply (Filter Block)

```twig
{# Apply filter to entire block #}
{% apply upper %}
    This text will be uppercase
{% endapply %}

{# Multiple filters #}
{% apply lower|escape %}
    <p>This HTML will be escaped and lowercased</p>
{% endapply %}

{# Practical example #}
{% apply markdown_to_html|striptags %}
# Markdown Title

This is **markdown** content.
{% endapply %}
```

### Verbatim (Raw Twig)

```twig
{# Don't process Twig syntax #}
{% verbatim %}
    {{ This will be output as-is }}
    {% for item in items %}
        Not processed
    {% endfor %}
{% endverbatim %}

{# Useful for documentation or JavaScript templates #}
```

---

## 6. Template Inheritance

Template inheritance allows you to build a base "skeleton" template that child templates extend.

### Base Layout

```twig
{# templates/base.html.twig #}
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{% block title %}My Application{% endblock %}</title>

    {% block stylesheets %}
        <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    {% endblock %}

    {% block javascripts %}
        <script src="{{ asset('js/app.js') }}"></script>
    {% endblock %}
</head>
<body>
    <header>
        {% block header %}
            <nav>
                <a href="{{ path('homepage') }}">Home</a>
                <a href="{{ path('blog_index') }}">Blog</a>
            </nav>
        {% endblock %}
    </header>

    <main>
        {% block body %}{% endblock %}
    </main>

    <footer>
        {% block footer %}
            <p>&copy; {{ 'now'|date('Y') }} My Company</p>
        {% endblock %}
    </footer>
</body>
</html>
```

### Child Template

```twig
{# templates/blog/index.html.twig #}
{% extends 'base.html.twig' %}

{% block title %}
    Blog - {{ parent() }}
{% endblock %}

{% block stylesheets %}
    {{ parent() }}
    <link rel="stylesheet" href="{{ asset('css/blog.css') }}">
{% endblock %}

{% block body %}
    <h1>Blog Posts</h1>

    {% for post in posts %}
        <article>
            <h2>{{ post.title }}</h2>
            <p>{{ post.excerpt }}</p>
            <a href="{{ path('blog_show', {id: post.id}) }}">Read more</a>
        </article>
    {% endfor %}
{% endblock %}
```

### Three-Level Inheritance

```twig
{# templates/base.html.twig - Main layout #}
<!DOCTYPE html>
<html>
<head>
    <title>{% block title %}{% endblock %}</title>
</head>
<body>
    {% block body %}{% endblock %}
</body>
</html>

{# templates/layout/two_column.html.twig - Two-column layout #}
{% extends 'base.html.twig' %}

{% block body %}
    <div class="container">
        <aside class="sidebar">
            {% block sidebar %}{% endblock %}
        </aside>
        <main class="content">
            {% block content %}{% endblock %}
        </main>
    </div>
{% endblock %}

{# templates/blog/show.html.twig - Actual page #}
{% extends 'layout/two_column.html.twig' %}

{% block title %}{{ post.title }}{% endblock %}

{% block sidebar %}
    <h3>Recent Posts</h3>
    {# ... #}
{% endblock %}

{% block content %}
    <h1>{{ post.title }}</h1>
    <div>{{ post.content|raw }}</div>
{% endblock %}
```

### Horizontal Reuse (Use)

```twig
{# templates/blocks/_alert.html.twig #}
{% block alert %}
    <div class="alert alert-{{ type }}">
        {{ message }}
    </div>
{% endblock %}

{# Use in template #}
{% use 'blocks/_alert.html.twig' %}

{{ block('alert', {type: 'success', message: 'Operation successful!'}) }}
```

---

## 7. Including Templates

### Basic Include

```twig
{# Include partial template #}
{% include 'partials/_header.html.twig' %}

{# Include with variables #}
{% include 'partials/_user_card.html.twig' with {user: currentUser} %}

{# Include with only specified variables (no global context) #}
{% include 'partials/_user_card.html.twig' with {user: currentUser} only %}

{# Ignore if template doesn't exist #}
{% include 'optional_section.html.twig' ignore missing %}
```

### Dynamic Includes

```twig
{# Include based on variable #}
{% include 'sections/' ~ sectionType ~ '.html.twig' %}

{# Include with array of possible templates (first found is used) #}
{% include ['partials/special.html.twig', 'partials/default.html.twig'] %}
```

### Embed

Embed allows including a template and overriding blocks within it.

```twig
{# templates/components/_card.html.twig #}
<div class="card">
    <div class="card-header">
        {% block card_header %}Default Header{% endblock %}
    </div>
    <div class="card-body">
        {% block card_body %}{% endblock %}
    </div>
    <div class="card-footer">
        {% block card_footer %}{% endblock %}
    </div>
</div>

{# Use with embed #}
{% embed 'components/_card.html.twig' %}
    {% block card_header %}
        <h3>{{ product.name }}</h3>
    {% endblock %}

    {% block card_body %}
        <p>{{ product.description }}</p>
        <p class="price">${{ product.price }}</p>
    {% endblock %}

    {% block card_footer %}
        <button>Add to Cart</button>
    {% endblock %}
{% endembed %}
```

---

## 8. Macros

Macros are reusable template fragments, similar to functions.

### Defining Macros

```twig
{# templates/macros/forms.html.twig #}

{% macro input(name, value, type = 'text') %}
    <input type="{{ type }}" name="{{ name }}" value="{{ value|e }}" />
{% endmacro %}

{% macro textarea(name, value, rows = 10) %}
    <textarea name="{{ name }}" rows="{{ rows }}">{{ value|e }}</textarea>
{% endmacro %}

{% macro button(text, type = 'submit', class = 'btn') %}
    <button type="{{ type }}" class="{{ class }}">{{ text }}</button>
{% endmacro %}
```

### Using Macros

```twig
{# Import all macros #}
{% import 'macros/forms.html.twig' as forms %}

{{ forms.input('username', user.username) }}
{{ forms.input('password', '', 'password') }}
{{ forms.textarea('bio', user.bio, 5) }}
{{ forms.button('Save', 'submit', 'btn btn-primary') }}

{# Import specific macros #}
{% from 'macros/forms.html.twig' import input, button %}

{{ input('email', user.email) }}
{{ button('Submit') }}
```

### Macros with Named Arguments

```twig
{# Define macro #}
{% macro alert(message, type = 'info', dismissible = false) %}
    <div class="alert alert-{{ type }} {{ dismissible ? 'alert-dismissible' : '' }}">
        {{ message }}
        {% if dismissible %}
            <button type="button" class="close">&times;</button>
        {% endif %}
    </div>
{% endmacro %}

{# Use with named arguments #}
{% import _self as macros %}

{{ macros.alert('Success!', type='success', dismissible=true) }}
{{ macros.alert(message='Warning!', type='warning') }}
```

### Self-Contained Macros

```twig
{# Define and use in same file #}
{% macro render_item(item) %}
    <div class="item">
        <h3>{{ item.title }}</h3>
        <p>{{ item.description }}</p>
    </div>
{% endmacro %}

{% import _self as macros %}

{% for item in items %}
    {{ macros.render_item(item) }}
{% endfor %}
```

### Recursive Macros

```twig
{# templates/macros/menu.html.twig #}
{% macro render_menu(items) %}
    <ul>
    {% for item in items %}
        <li>
            <a href="{{ item.url }}">{{ item.title }}</a>
            {% if item.children %}
                {{ _self.render_menu(item.children) }}
            {% endif %}
        </li>
    {% endfor %}
    </ul>
{% endmacro %}

{# Usage #}
{% import 'macros/menu.html.twig' as menu %}
{{ menu.render_menu(menuItems) }}
```

---

## 9. Global Variables

Twig provides access to global variables in Symfony.

### The `app` Variable

```twig
{# Current user #}
{% if app.user %}
    Hello, {{ app.user.username }}!

    {% if is_granted('ROLE_ADMIN') %}
        <a href="{{ path('admin') }}">Admin Panel</a>
    {% endif %}
{% else %}
    <a href="{{ path('app_login') }}">Login</a>
{% endif %}

{# Request #}
{{ app.request.method }}              {# GET, POST, etc. #}
{{ app.request.pathInfo }}            {# /blog/post/123 #}
{{ app.request.query.get('page') }}   {# Query parameter #}
{{ app.request.locale }}              {# Current locale #}

{# Session #}
{{ app.session.get('cart_count') }}
{% if app.session.started %}
    Session ID: {{ app.session.id }}
{% endif %}

{# Flash messages #}
{% for message in app.flashes('success') %}
    <div class="alert alert-success">{{ message }}</div>
{% endfor %}

{% for type, messages in app.flashes %}
    {% for message in messages %}
        <div class="alert alert-{{ type }}">{{ message }}</div>
    {% endfor %}
{% endfor %}

{# Environment #}
{{ app.environment }}        {# dev, prod, test #}
{{ app.debug }}             {# true/false #}

{# Current route #}
{{ app.request.attributes.get('_route') }}
{{ app.request.attributes.get('_route_params') }}

{# Token (CSRF) #}
{{ app.request.session.get('_csrf/token-id') }}
```

### Custom Global Variables

```yaml
# config/packages/twig.yaml
twig:
    globals:
        site_name: 'My Application'
        admin_email: 'admin@example.com'
        ga_tracking: '%env(GA_TRACKING_ID)%'
        app_version: '2.0.1'
```

```twig
{# Use custom globals #}
<title>{{ site_name }}</title>
<a href="mailto:{{ admin_email }}">Contact</a>

<script>
    ga('create', '{{ ga_tracking }}', 'auto');
</script>

<footer>Version {{ app_version }}</footer>
```

### Service as Global

```yaml
# config/packages/twig.yaml
twig:
    globals:
        settings_service: '@App\Service\SettingsService'
```

```twig
{# Access service methods #}
{{ settings_service.getSiteName() }}
{{ settings_service.getMaintenanceMode() }}
```

---

## 10. Auto-Escaping and Security

### Auto-Escaping

Twig automatically escapes all output by default.

```twig
{# This is automatically escaped #}
{{ user.name }}  {# If name is "<script>alert('xss')</script>" #}
{# Output: &lt;script&gt;alert(&#039;xss&#039;)&lt;/script&gt; #}

{# Explicitly escape #}
{{ userInput|e }}
{{ userInput|escape }}
{{ userInput|escape('html') }}

{# Different escaping strategies #}
{{ value|escape('js') }}
{{ value|escape('css') }}
{{ value|escape('url') }}
{{ value|escape('html_attr') }}
```

### Raw Output (Disable Escaping)

```twig
{# Output without escaping - DANGEROUS! #}
{{ trustedHtml|raw }}

{# Only use for trusted content #}
{{ post.content|raw }}  {# Content from WYSIWYG editor #}
{{ htmlFromService|raw }}
```

### Auto-Escape Context

```twig
{# Change autoescape strategy for block #}
{% autoescape 'html' %}
    {{ var }}  {# HTML escaped #}
{% endautoescape %}

{% autoescape 'js' %}
    const name = "{{ name }}";  {# JavaScript escaped #}
{% endautoescape %}

{% autoescape false %}
    {{ var }}  {# Not escaped - be careful! #}
{% endautoescape %}
```

### Safe HTML in Attributes

```twig
{# WRONG - XSS vulnerability #}
<div class="{{ userInput }}">

{# CORRECT - escaped by default #}
<div class="{{ userInput|e('html_attr') }}">

{# URL parameters #}
<a href="{{ path('user_profile', {id: userId}) }}">Profile</a>

{# External URLs - validate first! #}
<a href="{{ externalUrl|escape('html_attr') }}">Link</a>
```

### JavaScript Context

```twig
<script>
    {# WRONG #}
    const username = "{{ user.name }}";

    {# CORRECT #}
    const username = "{{ user.name|escape('js') }}";

    {# BETTER - JSON encode #}
    const user = {{ user|json_encode|raw }};
</script>
```

### CSS Context

```twig
<style>
    .user-color {
        {# Escape CSS values #}
        color: {{ userColor|escape('css') }};
    }
</style>
```

---

## 11. Forms Integration

### Rendering Complete Forms

```twig
{# Render entire form #}
{{ form(form) }}

{# With custom attributes #}
{{ form(form, {'attr': {'class': 'my-form', 'novalidate': 'novalidate'}}) }}
```

### Rendering Form Parts

```twig
{{ form_start(form) }}
    {# All fields #}
    {{ form_widget(form) }}

    {# Or individual fields #}
    {{ form_row(form.name) }}
    {{ form_row(form.email) }}
    {{ form_row(form.message) }}

    <button type="submit">Submit</button>
{{ form_end(form) }}
```

### Custom Form Layout

```twig
{{ form_start(form, {'attr': {'class': 'user-form'}}) }}

    {# Field label #}
    {{ form_label(form.email) }}

    {# Field widget (input) #}
    {{ form_widget(form.email, {'attr': {'class': 'form-control'}}) }}

    {# Field errors #}
    {{ form_errors(form.email) }}

    {# Complete row (label + widget + errors) #}
    {{ form_row(form.password, {
        'label': 'Your Password',
        'attr': {'placeholder': 'Enter password'}
    }) }}

    {# Help text #}
    {{ form_help(form.username) }}

    {# Rest of fields (hidden, CSRF, etc.) #}
    {{ form_rest(form) }}

    <button type="submit" class="btn btn-primary">Submit</button>

{{ form_end(form) }}
```

### Form Variables

```twig
{# Access form field properties #}
{% if form.email.vars.required %}
    <span class="required">*</span>
{% endif %}

{{ form.email.vars.label }}      {# Field label #}
{{ form.email.vars.value }}      {# Field value #}
{{ form.email.vars.id }}         {# Field ID #}
{{ form.email.vars.full_name }}  {# Field full name #}
{{ form.email.vars.errors }}     {# Field errors #}
{{ form.email.vars.valid }}      {# Is field valid? #}
```

### Iterating Form Fields

```twig
{{ form_start(form) }}
    {% for field in form %}
        {{ form_row(field) }}
    {% endfor %}

    <button type="submit">Submit</button>
{{ form_end(form) }}
```

### Form Themes

```twig
{# Use Bootstrap 5 theme #}
{% form_theme form 'bootstrap_5_layout.html.twig' %}
{{ form(form) }}

{# Multiple themes #}
{% form_theme form 'bootstrap_5_layout.html.twig' 'custom_form_theme.html.twig' %}

{# Apply theme to specific field #}
{% form_theme form.email 'custom_email_field.html.twig' %}
```

### Collection Fields

```twig
{# Render collection of forms (e.g., tags) #}
<ul class="tags" data-prototype="{{ form_widget(form.tags.vars.prototype)|e('html_attr') }}">
    {% for tag in form.tags %}
        <li>{{ form_row(tag) }}</li>
    {% endfor %}
</ul>
```

---

## 12. Creating Twig Extensions

### Simple Extension

```php
// src/Twig/AppExtension.php
namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('price', [$this, 'formatPrice']),
            new TwigFilter('excerpt', [$this, 'excerpt']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('area', [$this, 'calculateArea']),
        ];
    }

    public function formatPrice(float $price, string $currency = 'USD'): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
        ];

        $symbol = $symbols[$currency] ?? $currency;

        return $symbol . number_format($price, 2);
    }

    public function excerpt(string $text, int $length = 100): string
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return mb_substr($text, 0, $length) . '...';
    }

    public function calculateArea(float $width, float $height): float
    {
        return $width * $height;
    }
}
```

```twig
{# Use custom filter #}
{{ product.price|price('EUR') }}  {# €99.99 #}
{{ post.content|excerpt(200) }}

{# Use custom function #}
Area: {{ area(5, 10) }} {# 50 #}
```

### Extension with Dependencies

```php
namespace App\Twig;

use App\Service\MarkdownParser;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class MarkdownExtension extends AbstractExtension
{
    public function __construct(
        private MarkdownParser $markdownParser,
    ) {}

    public function getFilters(): array
    {
        return [
            new TwigFilter('markdown', [$this, 'parseMarkdown'], [
                'is_safe' => ['html']
            ]),
        ];
    }

    public function parseMarkdown(string $content): string
    {
        return $this->markdownParser->parse($content);
    }
}
```

```twig
{# Use markdown filter #}
{{ post.content|markdown }}
```

### Runtime Extension (Lazy Loading)

```php
// src/Twig/AppRuntime.php
namespace App\Twig\Runtime;

use App\Repository\UserRepository;
use Twig\Extension\RuntimeExtensionInterface;

class AppRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private UserRepository $userRepository,
    ) {}

    public function getUserCount(): int
    {
        return $this->userRepository->count([]);
    }
}

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Twig\Runtime\AppRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('user_count', [AppRuntime::class, 'getUserCount']),
        ];
    }
}
```

```twig
{# Service is only instantiated if function is called #}
Total users: {{ user_count() }}
```

### Test Extension

```php
namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigTest;

class AppExtension extends AbstractExtension
{
    public function getTests(): array
    {
        return [
            new TwigTest('email', [$this, 'isEmail']),
            new TwigTest('even number', [$this, 'isEvenNumber']),
        ];
    }

    public function isEmail(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function isEvenNumber(int $value): bool
    {
        return $value % 2 === 0;
    }
}
```

```twig
{% if userInput is email %}
    Valid email address
{% endif %}

{% if number is even number %}
    {{ number }} is even
{% endif %}
```

---

## 13. Best Practices

### 1. Template Organization

```
templates/
├── base.html.twig              # Base layout
├── layout/
│   ├── default.html.twig       # Default page layout
│   ├── two_column.html.twig    # Two-column layout
│   └── admin.html.twig         # Admin layout
├── components/                  # Reusable components
│   ├── _alert.html.twig
│   ├── _pagination.html.twig
│   └── _modal.html.twig
├── macros/                      # Macro files
│   ├── forms.html.twig
│   └── ui.html.twig
├── blog/                        # Blog templates
│   ├── index.html.twig
│   ├── show.html.twig
│   └── _post_item.html.twig    # Partial (prefix with _)
└── email/                       # Email templates
    ├── registration.html.twig
    └── registration.txt.twig
```

### 2. Naming Conventions

```twig
{# Partials start with underscore #}
{% include 'components/_header.html.twig' %}

{# Layouts in layout/ directory #}
{% extends 'layout/two_column.html.twig' %}

{# Email templates have .html.twig and .txt.twig versions #}
templates/email/welcome.html.twig
templates/email/welcome.txt.twig
```

### 3. Use Includes for Reusable Parts

```twig
{# Bad: Repeated code #}
<div class="user-card">
    <h3>{{ user1.name }}</h3>
    <p>{{ user1.email }}</p>
</div>

<div class="user-card">
    <h3>{{ user2.name }}</h3>
    <p>{{ user2.email }}</p>
</div>

{# Good: Reusable component #}
{# templates/components/_user_card.html.twig #}
<div class="user-card">
    <h3>{{ user.name }}</h3>
    <p>{{ user.email }}</p>
</div>

{# Usage #}
{% include 'components/_user_card.html.twig' with {user: user1} %}
{% include 'components/_user_card.html.twig' with {user: user2} %}
```

### 4. Keep Logic Out of Templates

```twig
{# Bad: Business logic in template #}
{% set total = 0 %}
{% for item in cart.items %}
    {% set total = total + (item.price * item.quantity * (1 - item.discount)) %}
{% endfor %}

{# Good: Logic in controller/service #}
{{ cart.total }}
```

### 5. Use Meaningful Block Names

```twig
{# Bad #}
{% block content1 %}{% endblock %}
{% block content2 %}{% endblock %}

{# Good #}
{% block page_title %}{% endblock %}
{% block main_content %}{% endblock %}
{% block sidebar %}{% endblock %}
```

### 6. Comment Complex Logic

```twig
{# Bad: No explanation #}
{% if user and user.isActive and not user.isBanned and user.emailVerified %}

{# Good: Documented #}
{#
    Show admin panel if user:
    - Is logged in
    - Has active account
    - Is not banned
    - Has verified email
#}
{% if user and user.isActive and not user.isBanned and user.emailVerified %}
```

### 7. Escape User Content

```twig
{# Bad: XSS vulnerability #}
{{ comment.text|raw }}

{# Good: Escaped by default #}
{{ comment.text }}

{# Good: Sanitized HTML #}
{{ comment.text|striptags|nl2br }}
```

### 8. Use Path Names, Not URLs

```twig
{# Bad: Hardcoded URL #}
<a href="/blog/post/123">Read more</a>

{# Good: Route name #}
<a href="{{ path('blog_show', {id: post.id}) }}">Read more</a>
```

### 9. Cache Expensive Operations

```yaml
# config/packages/twig.yaml
twig:
    cache: '%kernel.cache_dir%/twig'
    auto_reload: '%kernel.debug%'
```

### 10. Use Proper Localization

```twig
{# Bad: Hardcoded text #}
<h1>Welcome</h1>

{# Good: Translatable #}
<h1>{% trans %}welcome.title{% endtrans %}</h1>

{# Or with filter #}
<h1>{{ 'welcome.title'|trans }}</h1>
```

---

## Resources

- [Twig Documentation](https://twig.symfony.com/)
- [Symfony Twig Integration](https://symfony.com/doc/current/templates.html)
- [Twig for Template Designers](https://twig.symfony.com/doc/3.x/templates.html)
- [Twig Extensions](https://twig.symfony.com/doc/3.x/advanced.html)
- [Form Theming](https://symfony.com/doc/current/form/form_themes.html)
