# Simple Document Portal

A WordPress plugin to provide access to documents to logged-in users, with a simple interface for uploading and managing them powered by Advanced Custom Fields (ACF) Pro.

## Features

- Custom post type for documents and taxonomy for folders
- Native WordPress + ACF interface for creating single document posts
- Storage of documents in a dedicated directory outside the webroot, with custom code to check permissions before serving a file
- Customised ACF-based interface for bulk uploading documents
- Customised ACF-based interface for managing folders which limits the taxonomy hierarchy to two levels
- Automatic deletion of document file when the post is deleted
- Automatic deletion of document file when the attached file for a document post is changed
- Daily scheduled cleanup of orphaned documents (e.g., from abandoned bulk uploads)
- Ability to trigger a cleanup manually from the admin interface
- Custom capabilities mapped to appropriate built-in capabilities for default permissions
- Ability to grant the custom capabilities to additional user roles
- Custom "portal member" user role created on plugin activation, with `read_documents` capability
- Front-end template for displaying the documents to logged-in users with the `read_documents` capability, which can be overridden by a theme using the standard WordPress (classic) PHP template hierarchy.

## Prerequisites

### Documents directory

By default, WordPress stores uploaded files in the `wp-content/uploads` directory, and files are accessible to anyone with the direct URL to the file no matter how much protection you put in place on the front-end of your site. This plugin assumes that you want your documents to be accessible only to logged-in users, and attempts achieves this by storing them in a dedicated `documents` directory outside the webroot.

Another way is to have a directory within `wp-content/uploads` that is not accessible to the public, but this requires additional configuration of your web server. This option has not yet been implemented in this plugin but may be in the future if there is sufficient demand for it.

> [!CAUTION]
> The developer of this plugin does not provide support for server configuration and cannot guarantee the security of your documents under any circumstances. If you need to ensure protection of sensitive documents, please consult with your hosting provider and/or a security professional.

## Caveats

- This plugin's front-end experience has not been designed or tested for block themes.

## Installation

Installable plugin zip is available on the [GitHub releases page](https://github.com/doubleedesign/simple-document-portal/releases).

## Development

See [README.dev.md](README.dev.md) for information on developing the plugin, and [tests/README.md](tests/README.md) for detailed information on the automated testing setup.
