<?php

namespace Doubleedesign\SimpleDocumentPortal;

class UserPermissions {
    private static array $capability_map = [
        'read_documents'           => 'read_private_posts',
        'edit_documents'           => 'edit_private_posts',
        'delete_documents'         => 'delete_private_posts',
        'publish_documents'        => 'publish_posts',
        'manage_documents_options' => 'manage_options',
    ];

    /**
     * Getter utility function so other classes can access the capability map without being able to modify it directly.
     *
     * @return array|string[]
     */
    final public static function get_capability_map(): array {
        return self::$capability_map;
    }

    /**
     * Function to map custom capabilities to existing WordPress ones for a sensible default setup.
     * To be run on plugin activation to ensure all roles have the necessary capabilities,
     * without re-running the function every time the plugin is loaded.
     *
     * @return void
     */
    public static function map_permissions_to_existing_roles(): void {
        $wp_roles_instance = wp_roles();

        foreach (self::$capability_map as $custom_cap => $existing_cap) {
            $roles_with_permission = self::get_roles_with_permission($existing_cap);
            foreach ($roles_with_permission as $role_name => $role) {
                $wp_roles_instance->add_cap($role_name, $custom_cap);
            }
        }
    }

    /**
     * Utility function to reset custom capabilities to their default state.
     * To be run on plugin deactivation to ensure no custom capabilities are left behind.
     * Also, useful for debugging or changing functionality without having to manually remove capabilities from each role.
     *
     * @return void
     */
    public static function reset_default_capabilities(): void {
        $wp_roles_instance = wp_roles();

        foreach (self::$capability_map as $custom_cap => $existing_cap) {
            foreach ($wp_roles_instance->roles as $role_name => $role) {
                $wp_roles_instance->remove_cap($role_name, $custom_cap);
            }
        }
    }

    /**
     * Get user roles that have the given capability, whether built-in or custom.
     *
     * @param  string  $capability
     *
     * @return array - associative array of role keys and their details
     */
    public static function get_roles_with_permission(string $capability): array {
        $wp_roles_instance = wp_roles();

        return array_filter($wp_roles_instance->roles, function($role) use ($capability) {
            return in_array($capability, array_keys($role['capabilities']), true);
        });
    }

    /**
     * Get user roles that should not be able to have their default permissions changed
     * because they have the mapped built-in capability as well.
     *
     * @param  string  $custom_capability
     *
     * @return array - associative array of role keys and their details
     */
    public static function get_roles_with_matching_default_permission(string $custom_capability): array {
        $wp_roles_instance = wp_roles();

        return array_filter($wp_roles_instance->roles, function($role) use ($custom_capability) {
            $built_in_capability = self::$capability_map[$custom_capability] ?? null;

            return in_array($built_in_capability, array_keys($role['capabilities']), true);
        });
    }

    /**
     * Get the mapped built-in capability for a custom capability.
     *
     * @param  string  $custom_capability
     *
     * @return string|null
     */
    public static function get_mapped_built_in_capability(string $custom_capability): ?string {
        return self::$capability_map[$custom_capability] ?? null;
    }

    /**
     * Utility function to filter all available roles to only those that are currently in use.
     * Used to simplify role-related settings in the admin interface.
     *
     * @return array - single-dimensional indexed array of role keys
     */
    public static function get_roles_currently_in_use(): array {
        $wp_roles_instance = wp_roles();

        $roles = array_filter($wp_roles_instance->roles, function($role) {
            return count(get_users(['role' => $role['name']])) > 0;
        });

        return array_keys($roles);
    }
}
