<?php

namespace Drupal\recurring_donation\Form;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\recurring_donation\DonationTypes;

/**
 * Provides the 'Recurring PayPal donations' configuration form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'recurring_donation_configure_form';
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
  protected function recurringFormStates() {
    return [
      'visible' => [
        ':input[name="recurring_enabled"]' => ['checked' => TRUE],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('recurring_donation.settings');

    $form['env'] = [
      '#type' => 'select',
      '#title' => $this->t('Environment'),
      '#options' => [
        0 => $this->t('sandbox'),
        1 => $this->t('production'),
      ],
      '#default_value' => $config->get('env') ?: 0,
    ];

    $form['receiver'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PayPal receiving account'),
      '#description' => $this->t("The PayPal account's e-mail address"),
      '#required' => TRUE,
      '#default_value' => $config->get('receiver'),
    ];

    $form['options'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pre-defined amounts'),
      '#default_value' => $config->get('options'),
    ];

    $form['custom'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow custom amount'),
      '#default_value' => $config->get('custom'),
    ];

    $form['return'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Return URL'),
      '#description' => $this->t('The return URL upon successful payment'),
      '#default_value' => $config->get('return'),
    ];

    $form['currency_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Currency code'),
      '#description' => $this->t('ISO 4217 Currency Code'),
      '#default_value' => $config->get('currency_code'),
    ];

    $form['currency_sign'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Currency sign'),
      '#description' => $this->t('The currency sign'),
      '#default_value' => $config->get('currency_sign'),
    ];

    $form['donate_button_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Donate button text'),
      '#default_value' => $config->get('button'),
    ];

    foreach (DonationTypes::getTypes() as $key => $donationType) {

      $form[$key] = [
        '#type' => 'details',
        '#title' => $this->t('@donation_type donation settings', ['@donation_type' => Unicode::ucfirst($donationType)]),
        '#open' => TRUE,
      ];

      if ($donationType === DonationTypes::RECURRING) {

        $form[$key][$donationType . '_enabled'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Enable recurring donations'),
          '#default_value' => $config->get($donationType . '.enabled'),
        ];

        $form[$key][$donationType . '_unit'] = [
          '#type' => 'select',
          '#title' => $this->t('Recurring unit'),
          '#options' => [
            'D' => $this->t('day'),
            'W' => $this->t('week'),
            'M' => $this->t('month'),
            'Y' => $this->t('year'),
          ],
          '#states' => $this->recurringFormStates(),
          '#default_value' => $config->get($donationType . '.unit'),
        ];

        $form[$key][$donationType . '_duration'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Recurring duration'),
          '#states' => $this->recurringFormStates(),
          '#default_value' => $config->get($donationType . '.duration'),
        ];
      }

      $form[$key][$donationType . '_label'] = [
        '#type' => 'textfield',
        '#title' => $this->t('@donation_type donation label', ['@donation_type' => Unicode::ucfirst($donationType)]),
        '#default_value' => $config->get($donationType . '.label'),
      ];

      if ($donationType === DonationTypes::RECURRING) {
        $form[$key][$donationType . '_label']['#states'] = $this->recurringFormStates();
      }

    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('recurring_donation.settings');
    $config
      ->set('env', $form_state->getValue('env'))
      ->set('receiver', $form_state->getValue('receiver'))
      ->set('options', $form_state->getValue('options'))
      ->set('custom', $form_state->getValue('custom'))
      ->set('return', $form_state->getValue('return'))
      ->set('currency_code', $form_state->getValue('currency_code'))
      ->set('currency_sign', $form_state->getValue('currency_sign'))
      ->set('button', $form_state->getValue('donate_button_text'));

    foreach (DonationTypes::getTypes() as $donationType) {
      // Recurring donation type config.
      if ($donationType === DonationTypes::RECURRING) {
        $config
          ->set($donationType . '.enabled', $form_state->getValue($donationType . '_enabled'))
          ->set($donationType . '.unit', $form_state->getValue($donationType . '_unit'))
          ->set($donationType . '.duration', $form_state->getValue($donationType . '_duration'));
      }
      $config->set($donationType . '.label', $form_state->getValue($donationType . '_label'));
    }

    $config->save(TRUE);
  }

}
