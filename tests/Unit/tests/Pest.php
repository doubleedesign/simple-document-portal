<?php
use Brain\Monkey;
use function Brain\Monkey\Functions\when;
use Spies\Spy;
use function Spies\{finish_spying};
use function Patchwork\relay;

uses()->beforeEach(function() {
    Monkey\setUp();
    when('plugin_dir_path')->justReturn('/');

    $this->actions = [];
    $this->filters = [];

    /**
     * These patches intercept the given WordPress functions before BrainMonkey does if real instances
     * of plugin classes are instantiated, allowing us to pass in method spies as well as run the real methods,
     * which allows us to both assert that the methods were called and that the result would be correct.
     */
    when('add_action')->alias(function($hook, $callback) {
        // Store the added action in the test instance
        $this->actions[$hook][] = $callback;
        // Call the BrainMonkey mock too
        relay(func_get_args());
    });

    when('do_action')->alias(function($hook, ...$args) {
        // Run the functions registered for this hook
        if (isset($this->actions[$hook])) {
            foreach ($this->actions[$hook] as $callback) {
                call_user_func_array($callback, $args);
            }
        }

        // Call the BrainMonkey mock too
        relay(func_get_args());
    });

    when('add_filter')->alias(function($hook, $callback) {
        // Store the added filter in the test instance
        $this->filters[$hook][] = $callback;
        // Call the BrainMonkey mock too
        relay(func_get_args());
    });

    when('apply_filters')->alias(function($hook, $value, ...$extra) {
        // Run the functions registered for this hook
        if (isset($this->filters[$hook])) {
            foreach ($this->filters[$hook] as $callback) {
                // If a spy is provided as a third argument, call that as well as the registered callback
                if (isset($extra[0]) && $extra[0] instanceof Spy) {
                    $extra[0]->call($value);
                }

                $value = call_user_func($callback, $value);
            }
        }

        // Call the BrainMonkey mock too
        relay(func_get_args());

        // Return the final value after all available filters have been applied, or the default value if there were none
        return $value;
    });

})->in('Unit');

uses()->afterEach(function() {
    finish_spying(); // verifies all Spies expectations (otherwise we get "test had no assertions" errors)

    Monkey\tearDown();
})->in('Unit');
