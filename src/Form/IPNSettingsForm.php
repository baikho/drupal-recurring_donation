<?php

namespace Drupal\recurring_donation\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class IPNSettingsForm.
 *
 * @package Drupal\recurring_donation\Form
 */
class IPNSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'recurring_donation_ipn_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'recurring_donation.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('recurring_donation.settings');

    $form['ipn_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable IPN'),
      '#default_value' => $config->get('ipn.enabled'),
    ];

    $form['ipn_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('IPN Listener'),
      '#description' => $this->t('IPN Listener path for PayPal IPN messages. Defaults to "/paypal/payment/ipn" if left bank.'),
      '#default_value' => $config->get('ipn.path'),
      '#placeholder' => '/paypal/payment/ipn',
      '#states' => [
        'visible' => [
          ':input[name="ipn_enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['ipn_logging'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable IPN logging'),
      '#default_value' => $config->get('ipn.logging'),
      '#states' => [
        'visible' => [
          ':input[name="ipn_enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    // Validate notify path.
    if (($value = $form_state->getValue('ipn_path')) && $value[0] !== '/') {
      $form_state->setErrorByName('ipn_path', $this->t("The path '%path' has to start with a slash.", [
        '%path' => $form_state->getValue('ipn_path'),
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this
      ->config('recurring_donation.settings')
      ->set('ipn.enabled', (bool) $form_state->getValue('ipn_enabled'))
      ->set('ipn.path', $form_state->getValue('ipn_path'))
      ->set('ipn.logging', (bool) $form_state->getValue('ipn_logging'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
