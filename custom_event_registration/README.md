# Custom Event Registration Module for Drupal 10

A comprehensive custom Drupal 10 module that allows administrators to create and manage events, enables users to register for events via a custom form, stores registrations in custom database tables, and sends email notifications.

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Module Enable Steps](#module-enable-steps)
- [URLs and Navigation](#urls-and-navigation)
- [Database Tables](#database-tables)
- [Validation Logic](#validation-logic)
- [Email Notification Logic](#email-notification-logic)
- [AJAX Dropdown Flow](#ajax-dropdown-flow)
- [Permissions](#permissions)
- [Configuration](#configuration)
- [Development Notes](#development-notes)
- [Troubleshooting](#troubleshooting)
- [License](#license)

---

## Features

- **Event Configuration**: Admin interface to create and manage events with categories
- **User Registration**: Public registration form with dynamic dropdowns
- **AJAX-powered Dropdowns**: Category → Event Date → Event Name cascading selection
- **Duplicate Prevention**: Validates email + event date combination
- **Input Validation**: Prevents special characters in name fields
- **Email Notifications**: Sends confirmation to users and optional admin notifications
- **Admin Listing**: View all registrations with filters
- **CSV Export**: Export registration data to CSV format
- **Clean Architecture**: Repository → Service → Controller pattern
- **Dependency Injection**: No `\Drupal::service()` in business logic

---

## Requirements

- Drupal 10.x
- PHP 8.1 or higher
- MySQL 5.7+ / MariaDB 10.3+ / PostgreSQL 12+
- No contrib modules required (core only)

---

## Installation

### Method 1: Manual Installation

1. **Download/Clone the module**:
   ```bash
   cd /path/to/drupal/modules/custom
   git clone https://github.com/fossee/custom_event_registration.git
   ```

2. **Or copy the module folder**:
   Copy the entire `custom_event_registration` folder to:
   ```
   /path/to/drupal/modules/custom/custom_event_registration
   ```

3. **Verify file structure**:
   ```
   modules/custom/custom_event_registration/
   ├── custom_event_registration.info.yml
   ├── custom_event_registration.routing.yml
   ├── custom_event_registration.permissions.yml
   ├── custom_event_registration.services.yml
   ├── custom_event_registration.install
   ├── custom_event_registration.module
   ├── composer.json
   ├── composer.lock
   ├── sql/
   │   └── tables.sql
   ├── src/
   │   ├── Controller/
   │   │   └── AdminListingController.php
   │   ├── Form/
   │   │   ├── AdminConfigForm.php
   │   │   ├── EventConfigForm.php
   │   │   ├── EventRegistrationForm.php
   │   │   └── RegistrationFilterForm.php
   │   ├── Repository/
   │   │   ├── EventRepository.php
   │   │   └── RegistrationRepository.php
   │   └── Service/
   │       ├── EventService.php
   │       ├── MailService.php
   │       └── RegistrationService.php
   └── README.md
   ```

### Method 2: Using Composer (if packaged)

```bash
composer require drupal/custom_event_registration
```

---

## Module Enable Steps

### Via Drush (Recommended)

```bash
# Enable the module
drush en custom_event_registration -y

# Clear cache
drush cr
```

### Via Admin UI

1. Navigate to **Extend** (`/admin/modules`)
2. Search for "Custom Event Registration"
3. Check the checkbox next to the module
4. Click **Install**
5. Clear cache: **Configuration → Development → Performance → Clear all caches**

### Verify Installation

After enabling, verify the database tables were created:

```bash
drush sqlq "SHOW TABLES LIKE 'event_%'"
```

Expected output:
```
event_config
event_registration
```

---

## URLs and Navigation

| Page | URL | Permission Required |
|------|-----|---------------------|
| **Event Configuration** (Admin) | `/admin/config/event-registration/event-config` | `administer event configuration` |
| **Module Settings** (Admin) | `/admin/config/event-registration/settings` | `administer event configuration` |
| **Registration Listing** (Admin) | `/admin/event-registration/registrations` | `view event registrations` |
| **CSV Export** | `/admin/event-registration/export-csv` | `view event registrations` |
| **Event Registration Form** (User) | `/event-registration` | `access event registration` |

### Admin Menu Navigation

1. **Event Configuration**: Configuration → Event Registration → Event Configuration
2. **Module Settings**: Configuration → Event Registration → Settings
3. **View Registrations**: Event Registration → Registrations

---

## Database Tables

### Table: `event_config`

Stores event configuration data.

| Field | Type | Description |
|-------|------|-------------|
| `id` | INT (PK, Auto) | Unique event configuration ID |
| `registration_start` | VARCHAR(10) | Registration start date (YYYY-MM-DD) |
| `registration_end` | VARCHAR(10) | Registration end date (YYYY-MM-DD) |
| `event_date` | VARCHAR(10) | Event date (YYYY-MM-DD) |
| `event_name` | VARCHAR(255) | Event name |
| `category` | VARCHAR(100) | Event category |

**Indexes**:
- `category` - For filtering by category
- `event_date` - For filtering by date
- `registration_dates` - For checking active registration periods

### Table: `event_registration`

Stores user registration data.

| Field | Type | Description |
|-------|------|-------------|
| `id` | INT (PK, Auto) | Unique registration ID |
| `full_name` | VARCHAR(255) | Registrant's full name |
| `email` | VARCHAR(255) | Registrant's email address |
| `college_name` | VARCHAR(255) | College/Institution name |
| `department` | VARCHAR(255) | Department name |
| `event_id` | INT (FK) | Reference to `event_config.id` |
| `created` | INT | Unix timestamp of registration |

**Indexes**:
- `event_id` - For querying registrations by event
- `email` - For email lookups
- `email_event` - For duplicate checking

**Foreign Key**: `event_id` references `event_config(id)` with CASCADE delete

---

## Validation Logic

### Registration Form Validation

1. **Full Name**:
   - Required
   - Pattern: `/^[a-zA-Z\s\-\'\.]+$/`
   - Allowed: Letters, spaces, hyphens, apostrophes, periods
   - Error: "Full name can only contain letters, spaces, hyphens, apostrophes, and periods."

2. **Email Address**:
   - Required
   - Validated using PHP's `filter_var()` with `FILTER_VALIDATE_EMAIL`
   - Error: "Please enter a valid email address."

3. **College Name**:
   - Required
   - Pattern: `/^[a-zA-Z0-9\s\-\'\.&,]+$/`
   - Allowed: Alphanumeric, spaces, basic punctuation
   - Error: "College name can only contain letters, numbers, spaces, and basic punctuation."

4. **Department**:
   - Required
   - Pattern: `/^[a-zA-Z0-9\s\-\'\.&,]+$/`
   - Allowed: Alphanumeric, spaces, basic punctuation
   - Error: "Department can only contain letters, numbers, spaces, and basic punctuation."

5. **Duplicate Registration Check**:
   - Combination: Email + Event Date
   - Error: "You have already registered for an event on this date with this email address."

### Event Configuration Validation

1. Registration end date must be >= registration start date
2. Event date must be >= registration start date
3. Event name cannot be empty (after trimming)

---

## Email Notification Logic

### Email Types

1. **User Confirmation Email** (`registration_confirmation`)
   - **Sent To**: Registrant's email address
   - **Trigger**: Successful registration submission
   - **Always Sent**: Yes

2. **Admin Notification Email** (`admin_notification`)
   - **Sent To**: Configured admin email address
   - **Trigger**: Successful registration submission
   - **Conditions**: 
     - Admin notifications must be enabled in settings
     - Admin email must be configured

### Email Content

Both emails include:
- Registrant's name
- Event name
- Event date
- Event category
- College name
- Department
- Registration timestamp (admin email only)

### Configuration

Navigate to `/admin/config/event-registration/settings`:
- **Admin Email**: Set the notification recipient
- **Enable Notifications**: Toggle admin notifications on/off

---

## AJAX Dropdown Flow

### User Registration Form

```
┌─────────────────┐
│    Category     │ ← User selects (e.g., "Online Workshop")
└────────┬────────┘
         │ AJAX Request
         ▼
┌─────────────────┐
│   Event Date    │ ← Populated with dates for selected category
└────────┬────────┘
         │ AJAX Request
         ▼
┌─────────────────┐
│   Event Name    │ ← Populated with events matching category + date
└─────────────────┘
```

**Flow Details**:

1. **Category Selection**:
   - Triggers: `updateEventDateCallback()`
   - Updates: Event Date dropdown (filtered by category)
   - Resets: Event Name dropdown to empty

2. **Event Date Selection**:
   - Triggers: `updateEventNameCallback()`
   - Updates: Event Name dropdown (filtered by category + date)

### Admin Listing Form

```
┌─────────────────┐
│   Event Date    │ ← Admin selects date filter
└────────┬────────┘
         │ AJAX Request
         ▼
┌─────────────────┐
│   Event Name    │ ← Filtered by selected date
├─────────────────┤
│ Participant Count│ ← Updated count
├─────────────────┤
│ Registrations   │ ← Updated table
│     Table       │
└─────────────────┘
```

---

## Permissions

| Permission | Description | Recommended Roles |
|------------|-------------|-------------------|
| `administer event configuration` | Create/edit events and module settings | Administrator |
| `view event registrations` | View registration list and export CSV | Administrator, Event Manager |
| `access event registration` | Submit registration form | Authenticated User, Anonymous |

### Setting Permissions

1. Navigate to **People → Permissions** (`/admin/people/permissions`)
2. Search for "Event Registration"
3. Assign permissions to appropriate roles

---

## Configuration

### Initial Setup Checklist

1. [ ] Enable the module
2. [ ] Grant permissions to appropriate roles
3. [ ] Configure admin email settings at `/admin/config/event-registration/settings`
4. [ ] Create at least one event at `/admin/config/event-registration/event-config`
5. [ ] Verify registration form works at `/event-registration`

### Event Categories

The module supports four predefined categories:
- Online Workshop
- Hackathon
- Conference
- One-day Workshop

Categories are defined in `EventService::getCategoryOptions()`.

---

## Development Notes

### Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                        Controller                            │
│  (AdminListingController - handles HTTP requests/responses)  │
└─────────────────────────┬───────────────────────────────────┘
                          │
┌─────────────────────────▼───────────────────────────────────┐
│                         Service                              │
│  (EventService, RegistrationService, MailService)            │
│  (Business logic layer)                                      │
└─────────────────────────┬───────────────────────────────────┘
                          │
┌─────────────────────────▼───────────────────────────────────┐
│                        Repository                            │
│  (EventRepository, RegistrationRepository)                   │
│  (Database access layer)                                     │
└─────────────────────────────────────────────────────────────┘
```

### Dependency Injection

All services are defined in `custom_event_registration.services.yml`:

```yaml
services:
  custom_event_registration.event_repository:
    class: Drupal\custom_event_registration\Repository\EventRepository
    arguments: ['@database']

  custom_event_registration.event_service:
    class: Drupal\custom_event_registration\Service\EventService
    arguments: ['@custom_event_registration.event_repository']
```

### PSR-4 Autoloading

Namespace: `Drupal\custom_event_registration`

File mapping:
- `src/Form/EventConfigForm.php` → `Drupal\custom_event_registration\Form\EventConfigForm`
- `src/Service/EventService.php` → `Drupal\custom_event_registration\Service\EventService`
- etc.

---

## Troubleshooting

### Common Issues

1. **"Registration is currently closed" message**
   - Ensure at least one event has `registration_start <= today <= registration_end`

2. **AJAX dropdowns not working**
   - Clear Drupal cache: `drush cr`
   - Check browser console for JavaScript errors
   - Ensure jQuery/AJAX libraries are loaded

3. **Emails not sending**
   - Verify Drupal mail system is configured
   - Check `admin/config/system/site-information` for site email
   - Review dblog for mail errors: `admin/reports/dblog`

4. **Permission denied errors**
   - Verify user has appropriate permissions
   - Clear cache after permission changes

5. **Database errors**
   - Run database updates: `drush updb`
   - Verify tables exist: `drush sqlq "SHOW TABLES LIKE 'event_%'"`

### Debug Mode

Enable verbose error reporting in `settings.php`:

```php
$config['system.logging']['error_level'] = 'verbose';
```

---

## License

This module is licensed under the GNU General Public License v2.0 or later.

---

## Support

For issues and feature requests, please create an issue in the project repository.

---

## Changelog

### Version 1.0.0
- Initial release
- Event configuration form
- User registration form with AJAX
- Email notifications
- Admin listing with filters
- CSV export functionality
