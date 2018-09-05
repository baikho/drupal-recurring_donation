<?php

namespace Drupal\recurring_donation\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\recurring_donation\DonationTypes;
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
  public function buildForm(array $form, FormStateInterface $form_state, $donationType = DonationTypes::SINGLE) {

    if (!in_array($donationType, DonationTypes::getTypes(), FALSE)) {
      throw new InvalidDonationTypeException('Invalid donation type.');
    }

    $baseUrl = $this->getRequest()->getSchemeAndHttpHost();
    $config = $this->config('recurring_donation.settings');

    $form['title'] = [
      '#type' => 'markup',
      '#prefix' => '<h3 class="donation-title">',
      '#markup' => $config->get($donationType . '.label'),
      '#suffix' => '</h3>',
    ];

    $form['cmd'] = [
      '#type' => 'hidden',
      '#default_value' => $donationType === DonationTypes::RECURRING ? '_xclick-subscriptions' : '_donations',
    ];

    $form['lc'] = [
      '#type' => 'hidden',
      '#default_value' => $config->get('locale_code'),
    ];

    if (!empty($config->get('return_path'))) {
      $form['return'] = [
        '#type' => 'hidden',
        '#default_value' => $baseUrl . $config->get('return_path'),
      ];
    }

    if (!empty($config->get('cancel_path'))) {
      $form['cancel_return'] = [
        '#type' => 'hidden',
        '#default_value' => $baseUrl . $config->get('cancel_path'),
      ];
    }

    $form['no_note'] = [
      '#type' => 'hidden',
      '#default_value' => $donationType === DonationTypes::RECURRING ? 1 : 0,
    ];

    $form['business'] = [
      '#type' => 'hidden',
      '#default_value' => $config->get('receiver'),
    ];

    $form['currency_code'] = [
      '#type' => 'hidden',
      '#default_value' => $config->get('currency_code'),
    ];

    $form['amount'] = [
      '#type' => 'hidden',
    ];

    $amounts = array_filter(explode(',', str_replace(' ', '', $config->get('options'))));
    $custom = $config->get('custom');

    if (!empty($amounts) || $custom) {

      if (!empty($amounts)) {
        $options = [];
        foreach ($amounts as $amount) {
          $options[$amount] = $config->get('currency_sign') . ' ' . $amount;
        }

        if ($custom) {
          $options['other'] = $this->t('Other');
        }

        $form[$donationType . '_amount'] = [
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

      $form['custom_amount'] = [
        '#title' => $this->t('Custom amount'),
        '#field_prefix' => $config->get('currency_sign'),
        '#type' => 'number',
        '#step' => 0.01,
        '#min' => $config->get('custom_min') ?: 0.01,
        '#max' => $config->get('custom_max') ?: NULL,
        '#states' => [
          'visible' => [
            ':input[name="' . $donationType . '_amount"]' => ['value' => 'other'],
          ],
          'required' => [
            ':input[name="' . $donationType . '_amount"]' => ['value' => 'other'],
          ],
        ],
        '#attributes' => [
          'class' => [
            // Add classes in favor of JS.
            $donationType,
            'donation-custom-amount',
          ],
        ],
      ];
    }

    $form['custom'] = [
      '#type' => 'hidden',
      '#default_value' => $config->get('variable'),
    ];

    if ($donationType === DonationTypes::RECURRING) {
      // Set subscriptions to recur.
      $form['src'] = [
        '#type' => 'hidden',
        '#default_value' => 1,
      ];
      // Regular subscription price.
      $form['a3'] = [
        '#type' => 'hidden',
      ];
      // Subscription duration.
      $form['p3'] = [
        '#type' => 'hidden',
        '#default_value' => $config->get($donationType . '.duration'),
      ];
      // Regular subscription units of duration.
      $form['t3'] = [
        '#type' => 'hidden',
        '#default_value' => $config->get($donationType . '.unit'),
      ];
    }

    if ($config->get('ipn.enabled') !== FALSE) {
      $ipnPath = $config->get('ipn.path');
      $notifyUrl = !empty($ipnPath) ? $baseUrl . $config->get('ipn.path') : Url::fromRoute('recurring_donation.ipn')->toString();
      $form['notify_url'] = [
        '#type' => 'hidden',
        '#default_value' => $notifyUrl,
      ];
    }

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $config->get('button'),
      ],
    ];

    $mode = $config->get('mode') === 'live' ? '' : 'sandbox.';
    $url = 'https://www.' . $mode . 'paypal.com/cgi-bin/webscr';
    $form['#action'] = Url::fromUri($url, ['external' => TRUE])->toString();

    $form['#attached'] = [
      'library' => [
        'recurring_donation/recurring-donation',
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
