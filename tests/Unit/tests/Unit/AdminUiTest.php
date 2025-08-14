<?php
/** @noinspection PhpUnhandledExceptionInspection */

use Doubleedesign\SimpleDocumentPortal\AdminUI;
use Mockery\MockInterface;
use function Spies\{mock_object_of};

describe('ACF field instructions manipulation', function() {
    it('should call the method to add the tooltip field on the appropriate filter hook', function() {
        /** @var AdminUI&MockInterface $mock */
        $mock = Mockery::mock(AdminUI::class)->makePartial();
        $mock->shouldAllowMockingProtectedMethods();
        $mock->allows('should_render_instructions_as_tooltips')->andReturn(true);
        // Turn the mock into a Spies MockObject so we can assert on method calls
        $mock = mock_object_of($mock);
        $spy = $mock->spy_on_method('prepare_fields_that_should_have_instructions_as_tooltips');
        // Instantiate an instance so we can test that things work with the expected WordPress hooks
        $mock->__call('__construct', []);

        apply_filters('acf/prepare_field', ['key' => 'some_test_field'], $spy);

        expect($spy)->was_called()->toBeTrue();
    });

    it('should call the method to override the label rendering on the appropriate filter hook', function() {
        /** @var AdminUI&MockInterface $mock */
        $mock = Mockery::mock(AdminUI::class)->makePartial();
        $mock->shouldAllowMockingProtectedMethods();
        $mock->allows('should_render_instructions_as_tooltips')->andReturn(true);
        // Turn the mock into a Spies MockObject so we can assert on method calls
        $mock = mock_object_of($mock);
        $spy = $mock->spy_on_method('override_label_rendering_for_fields_with_tooltips');
        // Instantiate an instance so we can test that things work with the expected WordPress hooks
        $mock->__call('__construct', []);

        // FIXME: apply_filters stub doesn't work with multiple arguments, so this fails with "Too few arguments to function"
        apply_filters('acf/get_field_label', 'Test field', ['key' => 'some_test_field'], $spy);

        expect($spy)->was_called()->toBeTrue();
    });

    it('should move ACF field instructions to a "tooltip" field for specified fields', function() {
        /** @var AdminUI&MockInterface $mock */
        $mock = Mockery::mock(AdminUI::class)->makePartial();
        $mock->shouldAllowMockingProtectedMethods();
        $mock->allows('should_render_instructions_as_tooltips')->andReturn(true);

        $result = $mock->prepare_fields_that_should_have_instructions_as_tooltips(
            [
                'id'           => 1,
                'key'          => 'some_field_key',
                'label'        => 'Test field',
                'instructions' => '',
                'tooltip'      => 'Details about this field',
            ],
        );

        expect($result)->toBe([
            'id'           => 1,
            'key'          => 'some_field_key',
            'label'        => 'Test field',
            'instructions' => '',
            'tooltip'      => 'Details about this field',
        ]);
    });

    it('should render the instructions as a tooltip if it has the custom field', function() {
        /** @var AdminUI&MockInterface $mock */
        $mock = Mockery::mock(AdminUI::class)->makePartial();
        $mock->shouldAllowMockingProtectedMethods();
        $mock->allows('should_render_instructions_as_tooltips')->andReturn(true);
        $instance = $mock;

        // TODO: Test this with apply_filters (the stub for that doesn't currently work with multiple arguments)
        $result = $instance->render_some_acf_field_instructions_as_tooltips(
            'Test field',
            [
                'id'           => 1,
                'key'          => 'some_field_key',
                'label'        => 'Test field',
                'tooltip'      => 'Details about this field',
            ],
            []
        );

        expect($result)->toContainHtml('
		<button type="button" class="acf-js-tooltip" title="Details about this field">
			<span class="dashicons dashicons-editor-help"></span>
			<span class="screen-reader-text" role="tooltip">Details about this field</span>
		</button>
	</div>');
    });

    it('should not add a tooltip field or change field instructions if the field does not meet the tooltip criteria', function() {
        /** @var AdminUI&MockInterface $mock */
        $mock = Mockery::mock(AdminUI::class)->makePartial();
        $mock->shouldAllowMockingProtectedMethods();
        $mock->allows('should_render_instructions_as_tooltips')->andReturn(false);

        $result = $mock->prepare_fields_that_should_have_instructions_as_tooltips(
            [
                'id'           => 1,
                'key'          => 'some_field_key',
                'label'        => 'Test field',
                'instructions' => 'Details about this field',
            ],
        );

        expect($result)->toBe([
            'id'                => 1,
            'key'               => 'some_field_key',
            'label'             => 'Test field',
            'instructions'      => 'Details about this field',
        ]);
    });

    it('should not render an icon + tooltip if the field does not meet the tooltip criteria', function() {
        /** @var AdminUI&MockInterface $mock */
        $mock = Mockery::mock(AdminUI::class)->makePartial();
        $mock->shouldAllowMockingProtectedMethods();
        $mock->allows('should_render_instructions_as_tooltips')->andReturn(false);
        $instance = $mock;

        $result = $instance->render_some_acf_field_instructions_as_tooltips(
            'Test field',
            [
                'id'           => 1,
                'key'          => 'some_field_key',
                'label'        => 'Test field',
                'instructions' => 'Details about this field',
            ],
            []
        );

        expect($result)->toBe('Test field');
    });
});
