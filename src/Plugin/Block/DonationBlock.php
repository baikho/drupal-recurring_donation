<?php

namespace Drupal\recurring_donation\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\recurring_donation\DonationTypes;

/**
 * Provides a 'Recurring PayPal donations' block.
 *
 * @Block(
 *   id = "recurring_donation_block",
 *   admin_label = @Translation("Recurring PayPal donations"),
 *   category = @Translation("Forms")
 * )
 */
class DonationBlock extends BlockBase implements FormInterface, ContainerFactoryPluginInterface {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Stores the configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new PaypalDonateBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormBuilderInterface $form_builder, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $form_builder;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access recurring paypal donations');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return $this->formBuilder->getForm($this);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'recurring_donation_block_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->configFactory->get('recurring_donation.settings');

    $form['cmd'] = [
      '#type' => 'hidden',
      '#default_value' => '_donations',
    ];

    $form['business'] = [
      '#type' => 'hidden',
      '#default_value' => $config->get('receiver'),
    ];

    if ($amounts = explode(',', str_replace(' ', '', $config->get('options')))) {

      $options = [];
      foreach ($amounts as $amount) {
        $options[$amount] = $config->get('currency_sign') . ' ' . $amount;
      }

      if ($config->get('custom')) {
        $options['other'] = $this->t('Other');
      }

      $form['amount'] = [
        '#title' => $this->t('Amount'),
        '#type' => 'radios',
        '#options' => $options,
        '#required' => TRUE,
      ];
    }

    $form['custom'] = [
      '#title' => $this->t('Custom amount'),
      '#field_prefix' => $config->get('currency_sign'),
      '#type' => 'number',
      '#step' => 0.01,
      '#min' => 0.01,
      '#states' => [
        'visible' => [
          ':input[name="amount"]' => ['value' => 'other'],
        ],
        'required' => [
          ':input[name="amount"]' => ['value' => 'other'],
        ],
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $config->get('button'),
      ],
    ];

    $env = $config->get('env') !== TRUE ? 'sandbox.' : '';
    $url = 'https://www.' . $env . 'paypal.com/cgi-bin/webscr';
    $form['#action'] = Url::fromUri($url, ['external' => TRUE])->toString();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message('Donation successful.');
  }

}
