<?php

namespace Doubleedesign\SimpleDocumentPortal;
use WP_User_Query;

class UserRoles {
    private static array $custom_roles = array(
        array(
            'key'                     => 'portal_member',
            'label'                   => 'Portal Member',
            'base_role'               => 'subscriber',
            'custom_capabilities'     => ['read_documents']
        ),
    );

    /**
     * Function to create our custom user roles
     *
     * @return void
     */
    public static function create_roles(): void {
        foreach (self::$custom_roles as $custom_role_config) {
            // Create the role based on the given template role (which should be a built-in WordPress role)
            $template_role = get_role($custom_role_config['base_role']);
            $role = add_role($custom_role_config['key'], $custom_role_config['label'], $template_role->capabilities);

            // Add custom capabilities to the new role
            if ($custom_role_config['custom_capabilities']) {
                foreach ($custom_role_config['custom_capabilities'] as $capability) {
                    $role->add_cap($capability);
                }
            }
        }
    }

    /**
     * Function to remove the roles we created
     *
     * Intended for use upon plugin deactivation, this reverts users with custom roles to the base role,
     * but upon reactivation their custom role will be reinstated (unless the plugin has been uninstalled as well)
     *
     * @return void
     */
    public static function delete_roles(): void {
        // Revert users with custom roles to the associated base roles
        self::revert_users_roles(false);

        // Remove the roles from WordPress
        foreach (self::$custom_roles as $custom_role) {
            wp_roles()->remove_role($custom_role['key']);
        }
    }

    /**
     * Function to reassign custom roles to users
     *
     * Intended for use on plugin reactivation, after revert_users_roles has been run with $permanently set to false,
     * leaving a 'dangling' capability with the same name as the role
     *
     * @return void
     */
    public static function reassign_users_roles(): void {
        foreach (self::$custom_roles as $custom_role) {
            $user_query = new WP_User_Query(array(
                'capability' => $custom_role['key']
            ));
            foreach ($user_query->get('results') as $user) {
                $user->add_role($custom_role['key']);
                $user->remove_role($custom_role['base_role']);
            }
        }
    }

    /**
     * Function to revert users' roles to a built-in one if they had one of our custom roles
     * and remove "dangling" or "leftover" capabilities ($wp_roles->remove_cap doesn't meet our need here
     * because the role is removed upon deactivation; but a capability by the same name persists)
     *
     * Intended to be run upon plugin uninstallation as a complete "cleanup",
     * i.e. restore users to how they would be if our plugin was never there
     * THIS IS A DESTRUCTIVE OPERATION, USE WITH CARE!
     *
     * @param  bool  $permanently
     *
     * @return void
     */
    public static function revert_users_roles(bool $permanently): void {
        foreach (self::$custom_roles as $custom_role) {

            // Query to get users who had this custom role
            // Even though the role is deleted upon plugin deactivation, this query still works
            // (I assume because of the dangling/leftover capability that we're about to remove if $permanently = true)
            $user_query = new WP_User_Query(array(
                'role' => $custom_role['key']
            ));

            // Loop through the found users
            foreach ($user_query->get('results') as $user) {

                // Revert them to the base role of their custom role
                $user->add_role($custom_role['base_role']);

                // Ensure additional and custom capabilities are removed
                foreach ($custom_role['additional_capabilities'] as $capability) {
                    $user->remove_cap($capability);
                }
                foreach ($custom_role['custom_capabilities'] as $capability) {
                    $user->remove_cap($capability);
                }

                // Lastly, remove what's left over from our custom role if applicable
                // (intended for plugin uninstallation)
                if ($permanently) {
                    $user->remove_role($custom_role['key']);
                    $user->remove_cap($custom_role['key']);
                }
            }
        }
    }
}
