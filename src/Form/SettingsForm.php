<?php

namespace Drupal\recurring_donation\Form;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\recurring_donation\DonationTypes;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Egulias\EmailValidator\EmailValidator;

/**
 * Provides the 'Recurring PayPal donations' configuration form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The email validator.
   *
   * @var \Egulias\EmailValidator\EmailValidator
   */
  protected $emailValidator;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Egulias\EmailValidator\EmailValidator $email_validator
   *   Email validator.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EmailValidator $email_validator) {
    parent::__construct($config_factory);
    $this->emailValidator = $email_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('email.validator')
    );
  }

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
  protected function customAmountFormStates() {
    return [
      'visible' => [
        ':input[name="custom"]' => ['checked' => TRUE],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function formStates($donationType, array $states = []) {
    $stateRules = [];
    foreach ($states as $a => $state) {
      if (is_array($state)) {
        foreach ($state as $b => $val) {
          $stateRules[$a] = [
            ':input[name="' . $donationType . '_enabled"]' => ['checked' => TRUE],
            ':input[name="' . $b . '"]' => ['value' => $val],
          ];
        }
      }
      else {
        $stateRules[$state] = [
          ':input[name="' . $donationType . '_enabled"]' => ['checked' => TRUE],
        ];
      }
    }
    return $stateRules;
  }

  /**
   * {@inheritdoc}
   */
  protected function recurringUnitOptions() {
    return [
      'D' => $this->t('day'),
      'W' => $this->t('week'),
      'M' => $this->t('month'),
      'Y' => $this->t('year'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function recurringDurationOptions($unit = 'D') {
    switch ($unit) {
      case 'D':
      default:
        $max = 90;
        break;

      case 'W':
        $max = 52;
        break;

      case 'M':
        $max = 24;
        break;

      case 'Y':
        $max = 5;
        break;
    }
    $options = range(1, $max);
    return array_combine($options, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('recurring_donation.settings');
    $linkAttributes = ['attributes' => ['target' => '_blank']];

    $form['mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Mode'),
      '#options' => [
        'sandbox' => $this->t('sandbox'),
        'live' => $this->t('live'),
      ],
      '#default_value' => $config->get('mode') === 'live' ? 'live' : 'sandbox',
    ];

    $form['receiver'] = [
      '#type' => 'email',
      '#title' => $this->t('PayPal receiving account'),
      '#description' => $this->t("The PayPal account's e-mail address"),
      '#default_value' => $config->get('receiver'),
      '#required' => TRUE,
    ];

    $form['return_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Return URL'),
      '#description' => $this->t('The return path for a successful payment'),
      '#default_value' => $config->get('return_path'),
    ];

    $form['cancel_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cancel return URL'),
      '#description' => $this->t('The return path for a cancelled payment'),
      '#default_value' => $config->get('cancel_path'),
    ];

    $documentationLink = Link::fromTextAndUrl('PayPal locale codes', Url::fromUri('//developer.paypal.com/docs/classic/api/locale_codes/#supported-locale-codes', $linkAttributes));

    $form['locale_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Locale code'),
      '#description' => $this->t('Locale of the checkout page. See the @link reference page.', ['@link' => $documentationLink->toString()]),
      '#default_value' => $config->get('locale_code'),
      '#size' => 5,
    ];

    $form['options'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Predefined amounts'),
      '#description' => $this->t('Enter comma separated values'),
      '#default_value' => $config->get('options'),
    ];

    $form['options_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Input style'),
      '#options' => [
        'radios' => $this->t('radios'),
        'select' => $this->t('select'),
      ],
      '#default_value' => $config->get('options_style') ?: 'radios',
    ];

    $form['custom'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow custom amount'),
      '#default_value' => $config->get('custom'),
    ];

    $form['custom_min'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum custom amount'),
      '#step' => 0.01,
      '#min' => 0,
      '#default_value' => $config->get('custom_min'),
      '#states' => $this->customAmountFormStates(),
    ];

    $form['custom_max'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum custom amount'),
      '#step' => 0.01,
      '#min' => 1,
      '#default_value' => $config->get('custom_max'),
      '#states' => $this->customAmountFormStates(),
    ];

    $documentationLink = Link::fromTextAndUrl('Currencies Supported by PayPal', Url::fromUri('//developer.paypal.com/docs/classic/api/currency_codes/#paypal', $linkAttributes));

    $form['currency_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Currency code'),
      '#description' => $this->t('ISO 4217 Currency Code. For valid values, see @link.', ['@link' => $documentationLink->toString()]),
      '#default_value' => $config->get('currency_code'),
      '#size' => 3,
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

    $form['variable'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom variable'),
      '#description' => $this->t('Pass-through variable for your own tracking purposes, which buyers do not see.'),
      '#default_value' => $config->get('variable'),
    ];

    foreach (DonationTypes::getTypes() as $key => $donationType) {

      $form[$key] = [
        '#type' => 'details',
        '#title' => $this->t('@donation_type donation settings', ['@donation_type' => Unicode::ucfirst($donationType)]),
        '#open' => TRUE,
      ];

      $form[$key][$donationType . '_enabled'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable @donation_type donations', ['@donation_type' => $donationType]),
        '#default_value' => $config->get($donationType . '.enabled'),
      ];

      $form[$key][$donationType . '_label'] = [
        '#type' => 'textfield',
        '#title' => $this->t('@donation_type donation label', ['@donation_type' => Unicode::ucfirst($donationType)]),
        '#default_value' => $config->get($donationType . '.label'),
        '#states' => $this->formStates($donationType, ['visible']),
      ];

      if ($donationType === DonationTypes::RECURRING) {

        $form[$key][$donationType . '_enabled']['#description'] = $this->t('This feature is only available to Business and Premier Accounts.');

        $recurringUnitOptions = $this->recurringUnitOptions();

        $form[$key][$donationType . '_unit'] = [
          '#type' => 'select',
          '#title' => $this->t('Recurring unit'),
          '#options' => $recurringUnitOptions,
          '#states' => $this->formStates($donationType, ['visible', 'required']),
          '#default_value' => $config->get($donationType . '.unit'),
        ];

        foreach (array_keys($recurringUnitOptions) as $recurringUnitOption) {
          // Dependent recurring duration dropdown.
          $form[$key][$donationType . '_duration_' . $recurringUnitOption] = [
            '#type' => 'select',
            '#title' => $this->t('Recurring duration'),
            '#options' => $this->recurringDurationOptions($recurringUnitOption),
            '#states' => $this->formStates($donationType, [
              'visible' => [
                $donationType . '_unit' => $recurringUnitOption,
              ],
              'required' => [
                $donationType . '_unit' => $recurringUnitOption,
              ],
            ]),
            '#default_value' => $config->get($donationType . '.duration'),
          ];
        }

      }

    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $values = $form_state->getValues();
    if (!$this->emailValidator->isValid($values['receiver'])) {
      $form_state->setErrorByName('emailAddress', $this->t('%email is an invalid email address.', [
        '%email' => $values['receiver'],
      ]));
    }
    foreach (['return_path', 'cancel_path'] as $path) {
      if (($value = $form_state->getValue($path)) && $value[0] !== '/') {
        $form_state->setErrorByName($path, $this->t("The path '%path' has to start with a slash.", [
          '%path' => $form_state->getValue($path),
        ]));
      }
    }
    if (!$values['options'] && !$values['custom']) {
      $form_state->setErrorByName('options', $this->t('Specify at least 1 predefined amount or allow custom amount.'));
      $form_state->setErrorByName('custom');
    }
    if ($values['custom_min'] && $values['custom_max'] && ($values['custom_min'] > $values['custom_max'])) {
      $form_state->setErrorByName('custom_min', $this->t('Minimum custom amount can not exceed maximum custom amount.'));
      $form_state->setErrorByName('custom_max');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('recurring_donation.settings');
    $config
      ->set('mode', $form_state->getValue('mode'))
      ->set('receiver', $form_state->getValue('receiver'))
      ->set('locale_code', $form_state->getValue('locale_code'))
      ->set('options', $form_state->getValue('options'))
      ->set('options_style', $form_state->getValue('options_style'))
      ->set('custom', (bool) $form_state->getValue('custom'))
      ->set('custom_min', $form_state->getValue('custom_min'))
      ->set('custom_max', $form_state->getValue('custom_max'))
      ->set('return_path', $form_state->getValue('return_path'))
      ->set('cancel_path', $form_state->getValue('cancel_path'))
      ->set('currency_code', $form_state->getValue('currency_code'))
      ->set('currency_sign', $form_state->getValue('currency_sign'))
      ->set('button', $form_state->getValue('donate_button_text'))
      ->set('variable', $form_state->getValue('variable'));

    foreach (DonationTypes::getTypes() as $donationType) {
      $config
        ->set($donationType . '.enabled', (bool) $form_state->getValue($donationType . '_enabled'))
        ->set($donationType . '.label', $form_state->getValue($donationType . '_label'));
      // Recurring donation type config.
      if ($donationType === DonationTypes::RECURRING) {
        $config
          ->set($donationType . '.unit', $form_state->getValue($donationType . '_unit'))
          ->set($donationType . '.duration', $form_state->getValue($donationType . '_duration_' . $form_state->getValue($donationType . '_unit')));
      }
    }

    $config->save();
    parent::submitForm($form, $form_state);
  }

}
