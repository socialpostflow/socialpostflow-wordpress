# Deployment Guide

This document describes the workflow for deploying a Plugin update from GitHub to WP Zinc.

## Merge Pull Requests

Merge the approved Pull Request(s) to the `main` branch.

## Generate Localization File and Action/Filter Documentation

On your local machine, switch to the `main` branch.

Run the `.scripts/build.sh` script, which will:

- Generate the `languages/social-post-flow.pot` file
- Generate the [ACTIONS-FILTERS.md](ACTIONS-FILTERS.md) file

## Update the Plugin's Version Number

We follow [Semantic Versioning](https://semver.org/).

- In `social-post-flow.php`, change the Version header to the new version number.
- In `social-post-flow.php`, change the `PLUGIN_VERSION` constant to the new version number.

## Update the Plugin's readme.txt Changelog

Provide meaningful, verbose updates to the Changelog, in the following format:

```
= x.x.x (yyyy-mm-dd) =
* Added: Grid View: Update Dropdown Category Filter when a Category is added, edited or deleted in the Tree View or when editing an Attachment
* Fix: Grid View: Update Category Dropdown Filter's Category Counts when Attachment(s) edited and deleted
```

Generic changelog items such as `Fix: Various bugfixes` or `Several edge-case bug fixes` should be avoided.  They don't tell users (or us, as developers)
what took place in this version.

Each line in the changelog should start with `Added` or `Fix`.

## Commit Changes

Commit the updated files, which should comprise of:

- `languages/social-post-flow.pot`
- `readme.txt`
- `social-post-flow.php`
- `ACTIONS-FILTERS.md`

## Create a New Release

[Create a New Release](https://github.com/n7studios/wp-social-post-flow/releases/new), completing the following:

- Choose a tag: Click this button and enter the new version number (e.g. `1.9.6`)
- Release title: The version number (e.g. `1.9.6`)
- Describe this release: The changelog entered in the `readme.txt` file for this new version:

![New Release Screen](/.github/docs/new-release.png?raw=true)

## Publish the Release

When you're happy with the above, click `Publish Release`.

This will then send a webhook to the WP Zinc site, which will fetch this new version and make it available to license holders.

The release will also be available to view on the [Releases](https://github.com/n7studios/wp-social-post-flow/releases) section of this GitHub repository.