<?php

namespace Drupal\recurring_donation\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\recurring_donation\DonationType;
use Drupal\recurring_donation\InvalidDonationTypeException;

/**
 * Class DonationForm.
 *
 * @package Drupal\recurring_donation\Form
 */
class DonationForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'recurring_donation_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $donationType = DonationType::SINGLE) {

    if (!in_array($donationType, DonationType::getAll(), FALSE)) {
      throw new InvalidDonationTypeException('Invalid donation type.');
    }

    $baseUrl = $this->getRequest()->getSchemeAndHttpHost();
    $config = $this->config('recurring_donation.settings');

    $form[$donationType] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="' . DonationTypeSelectionForm::DONATION_TYPE_FIELD . '"]' => ['value' => $donationType],
        ],
      ],
    ];

    $form[$donationType]['cmd'] = [
      '#type' => 'hidden',
      '#default_value' => $donationType === DonationType::RECURRING ? '_xclick-subscriptions' : '_donations',
    ];

    $form[$donationType]['lc'] = [
      '#type' => 'hidden',
      '#default_value' => $config->get('locale_code'),
    ];

    if (!empty($config->get('return_path'))) {
      $form[$donationType]['return'] = [
        '#type' => 'hidden',
        '#default_value' => $baseUrl . $config->get('return_path'),
      ];
    }

    if (!empty($config->get('cancel_path'))) {
      $form[$donationType]['cancel_return'] = [
        '#type' => 'hidden',
        '#default_value' => $baseUrl . $config->get('cancel_path'),
      ];
    }

    $form[$donationType]['no_note'] = [
      '#type' => 'hidden',
      '#default_value' => $donationType === DonationType::RECURRING ? 1 : 0,
    ];

    $form[$donationType]['business'] = [
      '#type' => 'hidden',
      '#default_value' => $config->get('receiver'),
    ];

    $form[$donationType]['currency_code'] = [
      '#type' => 'hidden',
      '#default_value' => $config->get('currency_code'),
    ];

    $form[$donationType]['amount'] = [
      '#type' => 'hidden',
    ];

    $amounts = array_filter(explode(',', str_replace(' ', '', $config->get($donationType . '.options'))));
    $custom = $config->get($donationType . '.custom');

    if (!empty($amounts)) {
      $options = [];
      foreach ($amounts as $amount) {
        $options[$amount] = $config->get('currency_sign') . ' ' . $amount;
      }

      if ($custom) {
        $options['other'] = $this->t('Other');
      }

      $form[$donationType][$donationType . '_amount'] = [
        '#title' => $this->t('Amount'),
        '#type' => $config->get('options_style'),
        '#options' => $options,
        '#required' => TRUE,
        '#attributes' => [
          'class' => [
            // Add classes in favor of JS.
            $donationType,
            'donation-amount-choice',
          ],
        ],
      ];
    }

    $form[$donationType]['custom_amount'] = [
      '#title' => $config->get($donationType . '.custom_label') ?: $this->t('Custom amount'),
      '#field_prefix' => $config->get('currency_sign'),
      '#type' => 'number',
      '#step' => 0.01,
      '#min' => $config->get($donationType . '.custom_min') ?: 0.01,
      '#max' => $config->get($donationType . '.custom_max') ?: NULL,
      '#states' => [
        'visible' => [
          ':input[name="' . $donationType . '_amount"]' => ['value' => 'other'],
        ],
        'required' => [
          ':input[name="' . $donationType . '_amount"]' => ['value' => 'other'],
        ],
      ],
      '#required' => TRUE,
      '#attributes' => [
        'class' => [
          // Add classes in favor of JS.
          $donationType,
          'donation-custom-amount',
        ],
      ],
    ];

    $form[$donationType]['custom'] = [
      '#type' => 'hidden',
      '#default_value' => $config->get('variable'),
    ];

    if ($donationType === DonationType::RECURRING) {
      // Set subscriptions to recur.
      $form[$donationType]['src'] = [
        '#type' => 'hidden',
        '#default_value' => 1,
      ];
      // Regular subscription price.
      $form[$donationType]['a3'] = [
        '#type' => 'hidden',
      ];
      // Subscription duration.
      $form[$donationType]['p3'] = [
        '#type' => 'hidden',
        '#default_value' => $config->get($donationType . '.duration'),
      ];
      // Regular subscription units of duration.
      $form[$donationType]['t3'] = [
        '#type' => 'hidden',
        '#default_value' => $config->get($donationType . '.unit'),
      ];
    }

    if ($config->get('ipn.enabled') !== FALSE) {
      $ipnPath = $config->get('ipn.path');
      $notifyUrl = !empty($ipnPath) ? $baseUrl . $config->get('ipn.path') : Url::fromRoute('recurring_donation.ipn', [], ['absolute' => TRUE])->toString();
      $form[$donationType]['notify_url'] = [
        '#type' => 'hidden',
        '#default_value' => $notifyUrl,
      ];
    }

    $form[$donationType]['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $config->get('button'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="' . $donationType . '_amount"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $mode = $config->get('mode') === 'live' ? '' : 'sandbox.';
    $url = 'https://www.' . $mode . 'paypal.com/cgi-bin/webscr';
    $form['#action'] = Url::fromUri($url, ['external' => TRUE])->toString();

    $form[$donationType]['#attached'] = [
      'library' => [
        'recurring_donation/recurring_donation',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // This submit handler is not used.
  }

}
