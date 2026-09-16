# Dynamic Task Form Builder

A responsive PHP and MySQL task management application with a JSON-driven form builder. Administrators can define the task form, and users can create, view, edit, and delete tasks from the generated form.

## Features

- Detail and Review form sections
- Up to three builder columns on larger screens
- Active/inactive sections and fields
- Text, textarea, radio, date, and attachment fields
- Required-field validation and radio-option validation
- Configurable attachment extensions with a 5 MB upload limit
- Automatic task ID generation
- AJAX task save, update, view, and delete operations
- Historical form snapshots for reliable task viewing and edit compatibility checks
- Image previews and PDF/file links in task details
- Paginated, searchable, sortable task history table
- Responsive layouts for desktop, tablet, and mobile

## Requirements

- PHP 8.1 or newer
- MySQL 8 or MariaDB 10.4+
- Apache with PHP enabled
- PDO MySQL extension
- WAMP, XAMPP, or another equivalent local PHP environment

## Local Setup With WAMP

1. Copy the project into `C:\wamp64\www\php\practical\dynamic_task_form_builder`.
2. Start Apache and MySQL from the WAMP control panel.
3. Import [`database/schema.sql`](database/schema.sql) using phpMyAdmin or the MySQL client.
4. Check the database values in [`config/config.php`](config/config.php).
5. Confirm that the `uploads/` directory is writable by Apache.
6. Open:

	`http://localhost/php/practical/dynamic_task_form_builder/`

The application starts with a default form configuration if no settings have been saved yet.

## Database

The schema creates the `dynamic_task_form_builder` database and these tables:

- `task_form_settings`: stores form-builder configuration as JSON
- `tasks`: stores task information and the form snapshot used when the task was created
- `task_values`: stores values entered into dynamic fields
- `task_attachments`: stores uploaded-file metadata

Task records and their values/attachments are separate from form settings. Deleting a task does not delete the form configuration.

## Form Settings Workflow

1. Open **Form Settings** from the Task Information page.
2. Add Detail or Review sections.
3. Configure each section's title, ID, type, and active state.
4. Add fields and configure their type, label, ID, active state, required state, options, or extensions.
5. Select **Save Settings**.
6. The application saves the configuration and redirects to the Task Information page.

## Task Workflow

1. Fill in the task information and any active dynamic fields.
2. Upload files only through configured Attachment fields.
3. Select **Save Task** to create a task and generate its task ID.
4. Use the History table to search, sort, paginate, view, edit, or delete tasks.

View always uses the task's historical form snapshot. Edit is allowed only when the sections and fields that were active at submission are still available and compatible with the current form settings.

## Upload Security

- Uploaded files are assigned generated storage names.
- File extension, upload status, and file size are validated server-side.
- The upload directory blocks PHP and other executable script extensions through `.htaccess`.
- Runtime uploads are excluded from Git by `.gitignore`.

Do not commit production credentials, private uploads, or database exports.

## Project Structure

```text
api/          AJAX request handlers
assets/       CSS and browser JavaScript
config/       Database and application configuration
database/     MySQL schema
includes/     Shared PHP helpers and layout
uploads/      Runtime attachment storage
index.php     Task form and task history
settings.php  Form-builder screen
```

## GitHub Publication

From the project directory:

```bash
git init
git add .
git commit -m "Initial dynamic task form builder"
git branch -M main
git remote add origin https://github.com/YOUR_USERNAME/YOUR_REPOSITORY.git
git push -u origin main
```

Create the GitHub repository first and replace the placeholder remote URL. Authentication may require a GitHub personal access token or GitHub CLI login.
