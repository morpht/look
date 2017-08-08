<?php

namespace Drupal\look\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Component\Utility\UrlHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a 'Look Switcher' block.
 *
 * @Block(
 *   id = "look_switcher_block",
 *   admin_label = @Translation("Look Switcher"),
 *   category = @Translation("Modifiers")
 * )
 */
class LookSwitcher extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Constructs a new LookSwitcher instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection service.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $connection, Request $request) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->connection = $connection;
    $this->request = $request;
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
      $container->get('request_stack')->getCurrentRequest()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'exclude' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->configuration;

    $query = $this->connection->select('look_field_data', 'l')
      ->fields('l', ['id', 'name'])
      ->orderBy('l.name', 'ASC');
    $result = $query->execute()->fetchAllKeyed();

    $form['exclude'] = [
      '#type' => 'select',
      '#title' => $this->t('Excluded looks'),
      '#description' => $this->t('Select all looks which will not be offered.'),
      '#options' => $result,
      '#default_value' => $config['exclude'],
      '#multiple' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['exclude'] = $form_state->getValue('exclude');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    // CSS Class.
    $build['#attributes']['class'][] = 'look-switcher';

    // Don't cache.
    $build['#cache']['max-age'] = 0;

    // Get current url with params.
    $current_path = $this->request->getRequestUri();

    // Get looks.
    $query = $this->connection->select('look_field_data', 'l')
      ->fields('l', ['id', 'name'])
      ->orderBy('l.name', 'ASC');
    $result = $query->execute()->fetchAllKeyed();

    $exclude = $this->configuration['exclude'];

    // Build links.
    $links = [];
    foreach ($result as $id => $look) {
      // Skip excluded looks.
      if (in_array($id, $exclude)) {
        continue;
      }
      // Build URL.
      $current_url = Url::fromUserInput($current_path);
      // Remove existing 'look' param if present.
      $options = $current_url->getOptions();
      $options = UrlHelper::filterQueryParameters($options, ['query[look]']);
      // Add look name.
      $options['query']['look'] = $look;
      // Save options.
      $current_url->setOptions($options);
      // Create link.
      $link = Link::fromTextAndUrl($look, $current_url);
      $links[] = $link;
    }

    // Theme item list.
    $build[] = [
      '#theme' => 'item_list',
      '#items' => $links,
      '#type' => 'ul',
      '#context' => [
        'type' => 'look-switcher',
      ],
    ];

    return $build;
  }

}
