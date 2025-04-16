# Setup Guide

This document describes how to setup your development environment, so that it is ready to run, develop and test the Plugin.

Suggestions are provided for the LAMP/LEMP stack and Git client are for those who prefer the UI over a command line and/or are less familiar with 
WordPress, PHP, MySQL and Git - but you're free to use your preferred software.

## Setup

### LAMP/LEMP stack

Any Apache/nginx, PHP 7.x+ and MySQL 5.8+ stack running WordPress.  For example, but not limited to:
- Local by Flywheel (recommended)
- Docker
- MAMP
- WAMP
- VVV

### Composer

If [Composer](https://getcomposer.org) is not installed on your local environment, enter the following commands at the command line to install it:

```bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('sha384', 'composer-setup.php') === '906a84df04cea2aa72f40b5f787e49f22d4c2f19492ac310e8cba5b96ac8b64115ac402c8cd292b8a03482574915d1a8') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
php composer-setup.php
php -r "unlink('composer-setup.php');"
sudo mv composer.phar /usr/local/bin/composer
```

Confirm that installation was successful by entering the `composer` command at the command line

### Clone Repository

Using your preferred Git client or command line, clone this repository into the `wp-content/plugins/` folder of your local WordPress installation.

If you prefer to clone the repository elsewhere, and them symlink it to your local WordPress installation, that will work as well.

If you're new to this, use [GitHub Desktop](https://desktop.github.com/) or [Tower](https://www.git-tower.com/mac)

### Create Test Database

Create a blank `test` database in MySQL, with a MySQL user who can read and write to it.

### Configure Testing Environment

Copy the `.env.example` file to `.env.testing` in the root of this repository, changing folder and database credentials as necessary.

Create a `codeception.yml` file in the root of the repository, with the following contents:
```yaml
params:
    - .env.testing
```

This tells Codeception to read the above `.env.testing` file when testing on the local development enviornment.

### Install Testing Suite

In the Plugin's directory, at the command line, run `composer update`.

This will install the necessary libraries used for testing, including wp-browser, Codeception, PHPUnit and PHP_CodeSniffer, which we'll cover later on.

### Configure wp-config.php

In the root of your WordPress installation, find the `wp-config.php` file.

Change the following line from (your database name itself may vary):

```php
define( 'DB_NAME', 'local' );
```

to:

```php
if( isset( $_SERVER['HTTP_X_TEST_REQUEST'] ) && $_SERVER['HTTP_X_TEST_REQUEST'] ) {
    // WPBrowser request, performed when Codeception tests are run. Connect to test DB.
    define( 'DB_NAME', 'test' );
} elseif( isset( $_SERVER['HTTP_USER_AGENT'] ) && strpos( $_SERVER['HTTP_USER_AGENT'], 'HeadlessChrome' ) !== false ) {
    // WPWebDriver request, performed when Codeception tests are run. Connect to test DB.
    define( 'DB_NAME', 'test' );
} else {
    // Connect to local DB.
    define( 'DB_NAME', 'local' );
}
```

When Codeception tests are run, they will include either:
- The `HTTP_X_TEST_REQUEST` header for tests run using WPBrowser.
- The `HeadlessChrome` HTTP User Agent for tests run using WPWebDriver.

Our change above tells WordPress to use the test database for our test requests, whilst using the local/default database for any other requests.

### Install ChromeDriver

ChromeDriver is a headless (i.e. non-GUI) browser that our test suite uses to run Acceptance tests, interacting with the
Plugin just as a user would - including full JavaScript execution, user inputs etc.

Download ChromeDriver for your Google Chrome version and OS from https://sites.google.com/chromium.org/driver/downloads?authuser=0

For Mac users, copy the unzipped executable to `/usr/local/bin`.

### Running the Test Suite

First, run the ChromeDriver in a separate Terminal window:

```bash
chromedriver --url-base=/wd/hub
```

In a second Terminal window, in the Plugin's directory, build and run the tests to make sure there are no errors and that you have 
correctly setup your environment:

```bash
vendor/bin/codecept build
vendor/bin/codecept run EndToEnd
```

Don't worry if you don't understand these commands; if your output looks similar to the above screenshot, and no test is prefixed with `E`, 
your environment is setup successfully.

### Running CodeSniffer

In the Plugin's directory, run the following command to run PHP_CodeSniffer, which will check the code meets WordPress' Coding Standards:

```bash
vendor/bin/phpcs ./ -v
```

Again, don't worry if you don't understand these commands; if your output looks similar to the above screenshot, with no errors, your environment
is setup successfully.

### Next Steps

With your development environment setup, you'll probably want to start development, which is covered in the [Development Guide](DEVELOPMENT.md)