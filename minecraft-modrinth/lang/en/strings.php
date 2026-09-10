<?php

return [
    'plugin_name' => 'Modrinth',
    'minecraft_mods' => 'Minecraft Mods',
    'minecraft_plugins' => 'Minecraft Plugins',

    'settings' => [
        'latest_minecraft_version' => 'Latest Minecraft Version',
        'settings_saved' => 'Settings saved',
    ],

    'page' => [
        'open_folder' => 'Open :folder folder',
        'minecraft_version' => 'Minecraft Version',
        'loader' => 'Loader',
        'installed' => 'Installed :type',
        'unknown' => 'Unknown',
        'view_all' => 'All',
        'view_installed' => 'Installed',
        'mod_unavailable' => 'This mod/plugin is no longer available on Modrinth',
    ],

    'table' => [
        'columns' => [
            'title' => 'Title',
            'author' => 'Author',
            'downloads' => 'Downloads',
            'date_modified' => 'Modified',
        ],
    ],

    'version' => [
        'type' => 'Type',
        'downloads' => 'Downloads',
        'published' => 'Published',
        'changelog' => 'Changelog',
        'no_file_found' => 'No file found',
    ],

    'actions' => [
        'install_latest' => 'Install latest version',
        'install' => 'Install',
        'installed' => 'Installed',
        'update' => 'Update',
        'uninstall' => 'Uninstall',
        'versions' => 'Version Selection',
    ],

    'modals' => [
        'update_heading' => 'Update Mod/Plugin',
        'update_description' => 'This will replace version :old_version with version :new_version. The old file will be deleted.',
        'uninstall_heading' => 'Uninstall Mod/Plugin',
        'uninstall_description' => 'Are you sure you want to uninstall :name? This will permanently delete the file from your server.',
    ],

    'notifications' => [
        'install_success' => 'Installation completed',
        'install_success_body' => 'Successfully installed :name version :version',
        'install_failed' => 'Installation failed',
        'install_failed_body' => 'An error occurred during installation. Please try again or contact support if the issue persists.',
        'update_success' => 'Update completed',
        'update_success_body' => 'Successfully updated to version :version',
        'update_failed' => 'Update failed',
        'update_failed_body' => 'An error occurred during the update. Please try again or contact support if the issue persists.',
        'uninstall_success' => 'Uninstall completed',
        'uninstall_success_body' => 'Successfully uninstalled :name',
        'uninstall_partial' => 'Uninstall incomplete',
        'uninstall_partial_body' => 'The file for :name was deleted, but it could not be removed from the installed list. It may still appear as installed.',
        'uninstall_failed' => 'Uninstall failed',
        'uninstall_failed_body' => 'An error occurred during uninstallation. Please try again or contact support if the issue persists.',
    ],
];
