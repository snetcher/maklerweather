<?php

namespace Drupal\maklerweather\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Contribute form.
 */
class PassConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'maklerweather_appid_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['maklerweather.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);
    $form['appid'] = [
      '#type' => 'textfield',
      '#description' => 'Get Api Key from the page <a href="https://home.openweathermap.org/api_keys" target="_blank">https://home.openweathermap.org/api_keys</a>',
      '#title' => $this->t('App Id'),
      '#required' => TRUE,
      '#default_value' => $this->config('maklerweather.settings')->get('appid'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('maklerweather.settings')
      ->set('appid', $form_state->getValue('appid'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
