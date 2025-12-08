# Chapter 01: Setting Up Your Development Environment

Learn to configure a professional Symfony development environment.

---

## Learning Objectives

By the end of this chapter, you will:
- Install and configure PHP 8.2+
- Set up Composer for dependency management
- Install the Symfony CLI
- Configure your code editor
- Verify your environment is ready

---

## Step 1: Install PHP

### Ubuntu/Debian

```bash
# Add PHP repository
sudo add-apt-repository ppa:ondrej/php
sudo apt update

# Install PHP 8.2 with required extensions
sudo apt install php8.2 php8.2-cli php8.2-fpm php8.2-common \
    php8.2-mysql php8.2-pgsql php8.2-zip php8.2-gd php8.2-mbstring \
    php8.2-curl php8.2-xml php8.2-bcmath php8.2-intl php8.2-readline

# Verify installation
php -v
```

### macOS (with Homebrew)

```bash
# Install PHP
brew install php@8.2

# Add to PATH
echo 'export PATH="/opt/homebrew/opt/php@8.2/bin:$PATH"' >> ~/.zshrc
source ~/.zshrc

# Verify
php -v
```

### Windows

1. Download PHP from [windows.php.net](https://windows.php.net/download/)
2. Extract to `C:\php`
3. Add `C:\php` to your PATH
4. Copy `php.ini-development` to `php.ini`
5. Enable required extensions in `php.ini`

---

## Step 2: Install Composer

### Linux/macOS

```bash
# Download and install
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Verify
composer --version
```

### Windows

Download and run the installer from [getcomposer.org](https://getcomposer.org/download/).

---

## Step 3: Install Symfony CLI

### Linux

```bash
curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' | sudo -E bash
sudo apt install symfony-cli
```

### macOS

```bash
brew install symfony-cli/tap/symfony-cli
```

### Windows

```bash
scoop install symfony-cli
```

### Verify Installation

```bash
symfony check:requirements
```

Expected output:
```
Symfony Requirements Checker
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

> PHP is using the following php.ini file:
  /etc/php/8.2/cli/php.ini

> Checking Symfony requirements:

....................................

 [OK]
 Your system is ready to run Symfony projects
```

---

## Step 4: Install Database

### PostgreSQL (Recommended)

#### Ubuntu/Debian
```bash
sudo apt install postgresql postgresql-contrib
sudo systemctl start postgresql
sudo systemctl enable postgresql

# Create user and database
sudo -u postgres createuser --interactive --pwprompt
sudo -u postgres createdb guestbook
```

#### macOS
```bash
brew install postgresql@15
brew services start postgresql@15
createdb guestbook
```

### Or Use Docker

```bash
# Create docker-compose.yml
cat > docker-compose.yml << 'EOF'
version: '3.8'
services:
  database:
    image: postgres:15-alpine
    environment:
      POSTGRES_DB: guestbook
      POSTGRES_USER: app
      POSTGRES_PASSWORD: secret
    ports:
      - "5432:5432"
    volumes:
      - db_data:/var/lib/postgresql/data

volumes:
  db_data:
EOF

# Start database
docker-compose up -d
```

---

## Step 5: Configure Code Editor

### VS Code

1. Install [VS Code](https://code.visualstudio.com/)
2. Install extensions:

```bash
code --install-extension bmewburn.vscode-intelephense-client
code --install-extension whatwedo.twig
code --install-extension redhat.vscode-yaml
code --install-extension xdebug.php-debug
```

3. Create workspace settings:

```json
// .vscode/settings.json
{
    "intelephense.environment.phpVersion": "8.2.0",
    "files.associations": {
        "*.html.twig": "twig"
    },
    "emmet.includeLanguages": {
        "twig": "html"
    }
}
```

### PhpStorm

1. Download [PhpStorm](https://www.jetbrains.com/phpstorm/)
2. Install Symfony plugin: Settings → Plugins → "Symfony Support"
3. Enable plugin: Settings → PHP → Symfony → Enable

---

## Step 6: Create Your First Project

```bash
# Create new Symfony project
symfony new guestbook --webapp

# Navigate to project
cd guestbook

# Start development server
symfony server:start

# Open in browser
# https://127.0.0.1:8000
```

---

## Step 7: Initialize Git Repository

```bash
cd guestbook

# Initialize git (if not already done)
git init

# Create initial commit
git add .
git commit -m "chore: initial Symfony project setup"

# Create develop branch
git checkout -b develop
```

---

## Verification Checklist

Run through this checklist to ensure your environment is ready:

- [ ] `php -v` shows PHP 8.2+
- [ ] `composer --version` shows Composer 2.x
- [ ] `symfony check:requirements` shows all green
- [ ] Development server starts without errors
- [ ] Database connection works
- [ ] Git is configured and working

---

## Troubleshooting

### PHP Extensions Missing

```bash
# List installed extensions
php -m

# Install missing extension (Ubuntu)
sudo apt install php8.2-{extension_name}
```

### Permission Errors

```bash
# Fix var/ permissions
sudo chown -R $USER:www-data var/
sudo chmod -R 775 var/
```

### Port Already in Use

```bash
# Use different port
symfony server:start --port=8001
```

---

## Questions

1. What is the purpose of the Symfony CLI?
2. Why is PostgreSQL recommended over SQLite for Symfony projects?
3. What does `symfony check:requirements` verify?
4. What is the purpose of the `var/` directory?

---

## Next Step

Proceed to [Chapter 02: Introducing the Project](../02-project/README.md) to plan your guestbook application.
