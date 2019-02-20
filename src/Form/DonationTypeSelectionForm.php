<?php

namespace Drupal\recurring_donation\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\recurring_donation\DonationType;

/**
 * Class DonationTypeSelectionForm.
 *
 * @package Drupal\recurring_donation\Form
 */
class DonationTypeSelectionForm extends FormBase {

  /**
   * Donation type form field.
   */
  const DONATION_TYPE_FIELD = 'donation_type';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'recurring_donation_type_selection_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('recurring_donation.settings');

    $donationTypeOptions = [];
    foreach (DonationType::getAll() as $donationType) {
      // Early opt-out if donation type is not enabled.
      if ($config->get($donationType . '.enabled') !== TRUE) {
        continue;
      }
      $donationTypeOptions[$donationType] = $config->get($donationType . '.label');
    }

    if (count($donationTypeOptions) >= 2) {
      $form[self::DONATION_TYPE_FIELD] = [
        '#title' => $this->t('Type'),
        '#type' => 'radios',
        '#options' => $donationTypeOptions,
        '#default_value' => DonationType::SINGLE,
        '#required' => TRUE,
      ];
    }
    elseif (count($donationTypeOptions) === 1) {

      $donationType = array_keys($donationTypeOptions)[0];

      $form[self::DONATION_TYPE_FIELD] = [
        '#title' => $this->t('Type'),
        '#type' => 'hidden',
        '#default_value' => $donationType,
      ];

      $form[$donationType]['title'] = [
        '#type' => 'markup',
        '#prefix' => '<h3 class="donation-title">',
        '#markup' => $config->get($donationType . '.label'),
        '#suffix' => '</h3>',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // This submit handler is not used.
  }

}
