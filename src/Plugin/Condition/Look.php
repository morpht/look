<?php

namespace Drupal\look\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\look\LookConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Look' condition.
 *
 * @Condition(
 *   id = "look",
 *   label = @Translation("Looks")
 * )
 */
class Look extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * The look config.
   *
   * @var \Drupal\look\LookConfig
   */
  protected $lookConfig;

  /**
   * Constructs a new Look instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection service.
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The entity storage.
   * @param \Drupal\look\LookConfig $look_config
   *   The look config service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $connection, EntityStorageInterface $entity_storage, LookConfig $look_config) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->connection = $connection;
    $this->entityStorage = $entity_storage;
    $this->lookConfig = $look_config;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('entity_type.manager')->getStorage('look'),
      $container->get('look.config')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['looks' => []] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $query = $this->connection->select('look_field_data', 'l')
      ->fields('l', ['id', 'name'])
      ->orderBy('l.name', 'ASC');
    $result = $query->execute()->fetchAllKeyed();

    $form['looks'] = [
      '#title' => $this->t('Looks'),
      '#type' => 'checkboxes',
      '#options' => $result,
      '#default_value' => $this->configuration['looks'],
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['looks'] = array_filter($form_state->getValue('looks'));
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    if (empty($this->configuration['looks']) && !$this->isNegated()) {
      return TRUE;
    }
    $look = $this->lookConfig->get();
    return !empty($look) && !empty($this->configuration['looks'][$look['id']]);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    $looks = $this->entityStorage->loadMultiple($this->configuration['looks']);
    if (empty($looks)) {
      $applies = empty($this->configuration['negate']) ? 'all' : 'no';
      return $this->t('Applies for: @applies looks', ['@applies' => $applies]);
    }
    $look_names = [];
    /** @var \Drupal\look\Entity\Look $look */
    foreach ($looks as $look) {
      $look_names[] = $look->getName();
    }
    $applies = empty($this->configuration['negate']) ? 'Applies' : 'Not applies';
    return $this->t('@applies for: @looks', [
      '@applies' => $applies,
      '@looks' => implode(', ', $look_names),
    ]);
  }

}
