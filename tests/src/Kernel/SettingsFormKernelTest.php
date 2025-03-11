<?php

namespace Drupal\Tests\metadata_hex\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\metadata_hex\Form\SettingsForm;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests backend logic triggered by settings form buttons.
 *
 * @group metadata_hex
 */
class SettingsFormTest extends BaseKernelTestHex {

  

  /**
   * Tests that the save button correctly updates configuration.
   */
  public function testSaveButton() {
    // Load the form and submit a mock request.
    $form = new SettingsForm();
    $form_state = $this->getMockFormState([
      'some_setting' => 'new_value', // Adjust based on your form fields
    ]);

    // Submit the form.
    $form->submitForm([], $form_state);

    // Assert that the configuration was saved correctly.
    $config = $this->config('metadata_hex.settings');
    $this->assertEquals('new_value', $config->get('some_setting'), 'The setting was updated.');
  }

  /**
   * Tests the reset button functionality.
   */
  public function testResetButton() {
    // Set initial configuration.
    $this->config('metadata_hex.settings')->set('some_setting', 'old_value')->save();

    // Load the form and simulate clicking the reset button.
    $form = new SettingsForm();
    $form_state = $this->getMockFormState([], 'reset'); // Simulating reset button click

    $form->submitForm([], $form_state);

    // Assert that the configuration was reset (modify expected value as needed).
    $config = $this->config('metadata_hex.settings');
    $this->assertEquals('default_value', $config->get('some_setting'), 'The setting was reset.');
  }

  /**
   * Helper function to mock form state.
   *
   * @param array $values
   *   The values to simulate in the form submission.
   * @param string $triggering_element
   *   The name of the button being clicked (optional).
   *
   * @return \Drupal\Core\Form\FormState
   *   The mocked form state.
   */
  protected function getMockFormState(array $values, $triggering_element = 'save') {
    $form_state = $this->createMock(\Drupal\Core\Form\FormStateInterface::class);

    $form_state->method('getValues')->willReturn($values);
    $form_state->method('getTriggeringElement')->willReturn(['#name' => $triggering_element]);

    return $form_state;
  }
}