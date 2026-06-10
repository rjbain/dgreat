# Sitewide Alert Domain.

Adds [Domain](https://www.drupal.org/project/domain) support to sitewide alerts
using the [Domain Access Entity](https://www.drupal.org/project/domain_entity)
module.

This submodule is experimental. Please report any issues or feedback to https://www.drupal.org/project/issues/sitewide_alert


## Prerequisites

This submodule allows alerts to show only on some domains.

The [domain access](https://www.drupal.org/project/domain) module needs to be
installed and configured first.

## Installation and configuration

1. Enable the Sitewide Alert Domain module.
2. Configure domain support for Sitewide Alert entities at /admin/config/domain/entities. See documentation for the Domain Access Entity module (https://www.drupal.org/project/domain_entity)
3. If desired, configure the new Domain Access field on the sitewide alert form at /admin/config/sitewide_alerts/form-display
