# Testing Guide

This document describes how to:
- create and run tests for your development work,
- ensure code meets PHP and WordPress Coding Standards, for best practices and security,
- ensure code passes static analysis, to catch potential errors that tests might miss

If you're new to creating and running tests, this guide will walk you through how to do this.

For those more experienced with creating and running tests, our tests are written in PHP using [wp-browser](https://wpbrowser.wptestkit.dev/) 
and [Codeception](https://codeception.com/docs/01-Introduction).

## Prerequisites

If you haven't yet set up your local development environment with the Kit Plugin repository installed, refer to the [Setup Guide](SETUP.md).

If you haven't yet created a branch and made any code changes to the Plugin, refer to the [Development Guide](DEVELOPMENT.md)

## Write (or modify) a test

If your work creates new functionality, write a test.

If your work fixes existing functionality, check if a test exists. Either update that test, or create a new test if one doesn't exist.

Tests are written in PHP using [wp-browser](https://wpbrowser.wptestkit.dev/) and [Codeception](https://codeception.com/docs/01-Introduction).

## Types of Test

There are different types of tests that can be written:
- End to End Tests: Test the UI as a non-technical user in the web browser.
- Integration Tests: Test code modules in the context of a WordPress web site, and test single PHP classes or functions in isolation, with WordPress functions and classes loaded.

## Writing an End to End Test

To create a new End to End Test, at the command line in the Plugin's folder, enter the following command, replacing:
- `general` with the subfolder name to place the test within at `tests/EndToEnd`,
- `ActivatePlugin` with a meaningful name of what the test will perform.

End to End tests are placed in groups within subfolders at `tests/EndToEnd` so that they can be run in isolation, and the GitHub Action can run each folder's End to End tests in parallel for speed.

For example, to generate an `ActivatePlugin` End to End test in the `tests/EndtoEnd/general` folder:

```bash
php vendor/bin/codecept generate:cest EndToEnd general/ActivatePlugin
```
This will create a PHP test file in the `tests/EndToEnd/general` directory called `ActivatePluginCest.php`

In a Terminal window, run the ChromeDriver.  This is used by our test to mimic user behaviour, and will execute JavaScript
and other elements just as a user would see them:

```bash
chromedriver --url-base=/wd/hub
```

In a second Terminal window, run the test to confirm it works:
```bash
vendor/bin/codecept build
vendor/bin/codecept run EndToEnd general/ActivatePluginCest
```

The console will show the successful result

To run all End to End tests, use:
```bash
vendor/bin/codecept run EndToEnd
```

To run End to End tests in a specific folder (for example, `general`), use:
```bash
vendor/bin/codecept run EndToEnd general
```

To run a specific End to End test in a specific folder (for example, `ActivateDeactivatePluginCest` in the `general` folder), use:
```bash
vendor/bin/codecept run EndtoEnd general/ActivateDeactivatePluginCest
```

For a full list of available wp-browser and Codeception functions that can be used for testing, see:
- [wp-browser](https://wpbrowser.wptestkit.dev/modules)
- [Codeception](https://codeception.com/docs/AcceptanceTests)

## Required Test Format

Tests can be run in isolation, as part of a suite of tests, sequentially and/or in parralel across different environments.
It's therefore required that every Cest contain both `_before()` and `_passed()` functions, which handle:
- `_before()`: Performing prerequisite steps (such as Plugin activation, third party Plugin activation and setup) prior to each test,
- `_passed()`: Performing cleanup steps (such as Plugin deactivation, removal of Plugin data from the database) after each passing test.

## Using Helpers

Helpers extend testing by registering functions that we might want to use across multiple tests, which are not provided by wp-browser, 
Codeception or PHPUnit.  This helps achieve the principle of DRY code (Don't Repeat Yourself).

For example, in the `tests/Support/Helper` directory, our `Xdebug.php` helper contains the `checkNoWarningsAndNoticesOnScreen()` function,
which checks that
- the <body> class does not contain the `php-error` class, which WordPress adds if a PHP error is detected
- no Xdebug errors were output
- no PHP Warnings or Notices were output

## Writing a WordPress Unit Test

WordPress Unit tests provide testing of Plugin specific functions and/or classes, typically to assert that they perform as expected
by a developer.  This is primarily useful for testing our API class, and confirming that any Plugin registered filters return
the correct data.

To create a new WordPress Unit Test, at the command line in the Plugin's folder, enter the following command, replacing `APITest`
with a meaningful name of what the test will perform:

```bash
php vendor/bin/codecept generate:wpunit Integration APITest
```

This will create a PHP test file in the `tests/Integration` directory called `APITest.php`

Helpers can be used for WordPress Unit Tests, the same as how they can be used for End To End tests.
To register your own helper function, add it to the `tests/Support/Helper/Wpunit.php` file.

## Run Tests

Once you have written your code and test(s), run the tests to make sure there are no errors.

If ChromeDriver isn't running, open a new Terminal window and enter the following command:

```bash
chromedriver --url-base=/wd/hub
```

To run the tests, enter the following commands in a separate Terminal window:

```bash
vendor/bin/codecept build
vendor/bin/codecept run EndToEnd
vendor/bin/codecept run Integration
```

If a test fails, you can inspect the output and screenshot at `tests/_output`.

Any errors should be corrected by making applicable code or test changes.

## Run PHP CodeSniffer

[PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer) checks that all Plugin code meets the 
[WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/).

In the Plugin's directory, run the following command to run PHP_CodeSniffer, which will check the code meets WordPress' Coding Standards
as defined in the `phpcs.xml` configuration:

```bash
vendor/bin/phpcs ./ --standard=phpcs.xml -v -s
```

`--standard=phpcs.tests.xml` tells PHP CodeSniffer to use the Coding Standards rules / configuration defined in `phpcs.tests.xml`.
These differ slightly from WordPress' Coding Standards, to ensure that writing tests isn't a laborious task, whilst maintaing consistency
in test coding style. 
`-v` produces verbose output
`-s` specifies the precise rule that failed

Any errors should be corrected by either:
- making applicable code changes
- (Experimental) running `vendor/bin/phpcbf ./ --standard=phpcs.xml -v -s` to automatically fix coding standards

Need to change the PHP or WordPress coding standard rules applied?  Either:
- ignore a rule in the affected code, by adding `phpcs:ignore {rule}`, where {rule} is the given rule that failed in the above output.
- edit the [phpcs.xml](phpcs.xml) file.

**Rules should be ignored with caution**, particularly when sanitizing and escaping data.

## Run PHPStan

[PHPStan](https://phpstan.org) performs static analysis on the Plugin's PHP code.  This ensures:

- DocBlocks declarations are valid and uniform
- DocBlocks declarations for WordPress `do_action()` and `apply_filters()` calls are valid
- Typehinting variables and return types declared in DocBlocks are correctly cast
- Any unused functions are detected
- Unnecessary checks / code is highlighted for possible removal
- Conditions that do not evaluate can be fixed/removed as necessary

In the Plugin's directory, run the following command to run PHPStan:

```bash
vendor/bin/phpstan --memory-limit=1G
```

Any errors should be corrected by making applicable code changes.

False positives [can be excluded by configuring](https://phpstan.org/user-guide/ignoring-errors) the `phpstan.neon` file.

## Run PHP CodeSniffer for Tests

In the Plugin's directory, run the following command to run PHP_CodeSniffer, which will check the code meets Coding Standards
as defined in the `phpcs.tests.xml` configuration:

```bash
vendor/bin/phpcs ./tests --standard=phpcs.tests.xml -v -s 
```

`--standard=phpcs.tests.xml` tells PHP CodeSniffer to use the Coding Standards rules / configuration defined in `phpcs.tests.xml`.
These differ slightly from WordPress' Coding Standards, to ensure that writing tests isn't a laborious task, whilst maintaing consistency
in test coding style. 
`-v` produces verbose output
`-s` specifies the precise rule that failed

Any errors should be corrected by either:
- making applicable code changes
- (Experimental) running `vendor/bin/phpcbf ./tests --standard=phpcs.tests.xml -v -s ` to automatically fix coding standards

Need to change the PHP or WordPress coding standard rules applied?  Either:
- ignore a rule in the affected code, by adding `phpcs:ignore {rule}`, where {rule} is the given rule that failed in the above output.
- edit the [phpcs.tests.xml](phpcs.tests.xml) file.

**Rules can be ignored with caution**, but it's essential that rules relating to coding style and inline code commenting / docblocks remain.

## Next Steps

Once your test(s) are written and successfully run locally, submit your branch via a new Pull Request

It's best to create a Pull Request in draft mode, as this will trigger all tests to run as a GitHub Action, allowing you to
double check all tests pass.

If the PR tests fail, you can make code changes as necessary, pushing to the same branch.  This will trigger the tests to run again.

If the PR tests pass, you can publish the PR, assigning some reviewers.