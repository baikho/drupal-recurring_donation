<?php

namespace Drupal\recurring_donation\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\recurring_donation\DonationTypes;

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

    $form['no_note'] = [
      '#type' => 'hidden',
      '#default_value' => $donationType === DonationTypes::RECURRING,
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
          '#type' => 'radios',
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

      $form['custom'] = [
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

    if ($donationType === DonationTypes::RECURRING) {
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

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $config->get('button'),
      ],
    ];

    $env = (bool) $config->get('env') !== TRUE ? 'sandbox.' : '';
    $url = 'https://www.' . $env . 'paypal.com/cgi-bin/webscr';
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
