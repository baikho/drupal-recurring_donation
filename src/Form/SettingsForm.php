<?php

namespace Drupal\recurring_donation\Form;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the 'Recurring PayPal donations' configuration form.
 */
class SettingsForm extends ConfigFormBase {

  const SINGLE = 'single';
  const RECURRING = 'recurring';

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
  protected function getDonationTypes() {
    return [
      self::SINGLE,
      self::RECURRING,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function recurringFormStates() {
    return [
      'visible' => [
        ':input[name="enabled"]' => ['checked' => TRUE],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('recurring_donation.settings');

    $form['service'] = [
      '#type' => 'select',
      '#title' => $this->t('Service'),
      '#options' => [
        0 => $this->t('sandbox'),
        1 => $this->t('production'),
      ],
      '#default_value' => $config->get('service') ?: 0,
    ];

    $form['receiver'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PayPal receiving account'),
      '#description' => $this->t("The PayPal account's e-mail address"),
      '#required' => TRUE,
    ];

    $form['options'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pre-defined amounts'),
    ];

    $form['custom'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow custom amount'),
    ];

    $form['return'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Return URL'),
      '#description' => $this->t('The return URL upon successful payment'),
    ];

    $form['currency_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Currency code'),
      '#description' => $this->t('ISO 4217 Currency Code'),
    ];

    $form['currency_sign'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Currency sign'),
      '#description' => $this->t('The currency sign'),
    ];

    $form['donate_button_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Donate button text'),
      '#default_value' => $config->get('button'),
    ];

    foreach ($this->getDonationTypes() as $donationType) {

      $form[$donationType] = [
        '#type' => 'details',
        '#title' => $this->t('@donation_type donation settings', ['@donation_type' => Unicode::ucfirst($donationType)]),
        '#open' => TRUE,
      ];

      if ($donationType === self::RECURRING) {

        $form[$donationType]['enabled'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Enable recurring donations'),
        ];

        $form[$donationType]['unit'] = [
          '#type' => 'select',
          '#title' => $this->t('Recurring unit'),
          '#options' => [
            'D' => $this->t('day'),
            'W' => $this->t('week'),
            'M' => $this->t('month'),
            'Y' => $this->t('year'),
          ],
          '#states' => $this->recurringFormStates(),
        ];

        $form[$donationType]['duration'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Recurring duration'),
          '#states' => $this->recurringFormStates(),
        ];

      }

      $form[$donationType]['label'] = [
        '#type' => 'textfield',
        '#title' => $this->t('@donation_type donation label', ['@donation_type' => Unicode::ucfirst($donationType)]),
        '#default_value' => $config->get('label.' . $donationType),
      ];

      if ($donationType === self::RECURRING) {
        $form[$donationType]['label']['#states'] = $this->recurringFormStates();
      }

    }

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @todo: cache clear or re-save PayPal block instances after submit.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('recurring_donation.settings')
      ->set('label.single', (string) $form_state->getValue('single_label'))
      ->set('label.recurring', (string) $form_state->getValue('recurring_label'))
      ->set('button', (string) $form_state->getValue('donate_button_text'))
      ->save(TRUE);
  }

}
