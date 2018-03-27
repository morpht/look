<?php

namespace Drupal\look;

use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Condition\ConditionManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\modifiers\Modifiers;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides utility functions for looks.
 */
class LookConfig {

  /**
   * The field holding theme name.
   */
  const THEME_FIELD = 'field_look_theme';

  /**
   * The field holding look paths.
   */
  const PATH_FIELD = 'field_look_path';

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The admin context.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $adminContext;

  /**
   * The condition manager.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $conditionManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The modifiers service.
   *
   * @var \Drupal\modifiers\Modifiers
   */
  protected $modifiers;

  /**
   * Constructs a new LookConfig instance.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack service.
   * @param \Drupal\Core\Routing\AdminContext $admin_context
   *   The admin context service.
   * @param \Drupal\Core\Condition\ConditionManager $condition_manager
   *   The condition manager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\modifiers\Modifiers $modifiers
   *   The modifiers service.
   */
  public function __construct(Connection $connection, ConfigFactoryInterface $config_factory, CacheBackendInterface $cache, RequestStack $request_stack, AdminContext $admin_context, ConditionManager $condition_manager, EntityTypeManagerInterface $entity_type_manager, Modifiers $modifiers) {
    $this->connection = $connection;
    $this->configFactory = $config_factory;
    $this->cache = $cache;
    $this->request = $request_stack->getCurrentRequest();
    $this->adminContext = $admin_context;
    $this->conditionManager = $condition_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->modifiers = $modifiers;
  }

  /**
   * Gets current look configuration.
   *
   * @return array
   *   The configuration array.
   */
  public function get() {

    $config = &drupal_static('look_config');

    if (isset($config)) {
      return $config;
    }

    $look_id = $this->getLookId();

    $config = !empty($look_id) ? $this->getConfig($look_id) : [];

    return $config;
  }

  /**
   * Gets look ID using recognition rules.
   *
   * @return int|null
   *   The look ID or null if empty.
   */
  private function getLookId() {

    if ($look_id = $this->getLookIdFromUrl()) {
      return $look_id;
    }
    if ($look_id = $this->getLookIdFromPost()) {
      return $look_id;
    }
    if ($look_id = $this->getLookIdFromCookie()) {
      return $look_id;
    }
    if ($look_id = $this->getLookIdFromEntity()) {
      return $look_id;
    }
    if ($look_id = $this->getLookIdFromPath()) {
      return $look_id;
    }
    if ($look_id = $this->getDefaultLookId()) {
      return $look_id;
    }

    return NULL;
  }

  /**
   * Gets look ID from request URL parameter.
   *
   * @return int|null
   *   The look ID or null if empty.
   */
  private function getLookIdFromUrl() {

    $look_name = Html::escape($this->request->query->get('look'));

    return $this->getLookByName($look_name);
  }

  /**
   * Gets look ID from request POST variable.
   *
   * @return int|null
   *   The look ID or null if empty.
   */
  private function getLookIdFromPost() {

    $look_name = Html::escape($this->request->request->get('look'));

    return $this->getLookByName($look_name);
  }

  /**
   * Gets look ID from request cookie.
   *
   * @return int|null
   *   The look ID or null if empty.
   */
  private function getLookIdFromCookie() {

    $look_name = Html::escape($this->request->cookies->get('look'));

    return $this->getLookByName($look_name);
  }

  /**
   * Gets look ID from current page entity.
   *
   * @return int|null
   *   The look ID or null if empty.
   */
  private function getLookIdFromEntity() {

    $attributes = $this->request->attributes;
    $route_name = $attributes->get('_route');
    $pattern = '/^entity\.([^.]+)\.(canonical|latest_version)$/';
    preg_match($pattern, $route_name, $matches);

    if (!empty($matches[1])) {
      $entity = $attributes->get($matches[1]);

      if (!empty($entity) && $entity instanceof FieldableEntityInterface) {

        if ($entity->hasField('field_look')) {
          $look_id = $entity->get('field_look')->target_id;
        }
      }
    }

    return !empty($look_id) ? intval($look_id) : NULL;
  }

  /**
   * Gets look ID from request path.
   *
   * @return int|null
   *   The look ID or null if empty.
   */
  private function getLookIdFromPath() {

    if ($this->connection->schema()
      ->tableExists('look__' . $this::PATH_FIELD)
    ) {
      $query = $this->connection->select('look__' . $this::PATH_FIELD, 'l');
      $query->fields('l', ['entity_id', $this::PATH_FIELD . '_value'])
        ->condition('l.' . $this::PATH_FIELD . '_value', '', '!=');
      $paths = $query->execute()->fetchAllKeyed();

      if (!empty($paths)) {
        /** @var \Drupal\system\Plugin\Condition\RequestPath $path_check */
        $path_check = $this->conditionManager->createInstance('request_path');

        foreach ($paths as $id => $path) {
          $path_check->setConfig('pages', $path);

          if ($path_check->execute()) {
            return $id;
          }
        }
      }
    }

    return NULL;
  }

  /**
   * Gets look ID of default look.
   *
   * @return int|null
   *   The look ID or null if empty.
   */
  private function getDefaultLookId() {

    $config = $this->configFactory->get('look.settings');
    $key = $this->adminContext->isAdminRoute() ? 'default_admin' : 'default';
    $look_id = $config->get($key);

    return !empty($look_id) ? intval($look_id) : NULL;
  }

  /**
   * Gets look ID by its name.
   *
   * @param string $name
   *   The look name.
   *
   * @return int|null
   *   The look ID or null if empty.
   */
  private function getLookByName($name) {

    if (!empty($name)) {
      $query = $this->connection->select('look_field_data', 'l');
      $query->fields('l', ['id'])
        ->condition('l.name', $name, 'LIKE BINARY');
      $look_id = $query->execute()->fetchField();
    }

    return !empty($look_id) ? intval($look_id) : NULL;
  }

  /**
   * Gets configuration of provided look.
   *
   * @param int $look_id
   *   The look ID.
   *
   * @return array
   *   The configuration array.
   */
  private function getConfig($look_id) {

    $config = [];
    $cid = 'look:' . $look_id;

    if ($cache = $this->cache->get($cid)) {
      $config = $cache->data;
    }
    else {
      /** @var \Drupal\look\LookStorageInterface $storage */
      $storage = $this->entityTypeManager->getStorage('look');
      $parents = $storage->loadParents($look_id);

      if (!empty($parents)) {
        $config = [
          'id' => $look_id,
          'name' => $parents[$look_id]->getName(),
          'config' => $this->extractConfig($parents),
        ];

        $tags = [];
        /** @var \Drupal\look\Entity\Look $look */
        foreach ($parents as $look) {
          $tags[] = 'look:' . $look->id();
        }
        $this->cache->set($cid, $config, Cache::PERMANENT, $tags);
      }
    }

    return $config;
  }

  /**
   * Extracts configuration from look hierarchy.
   *
   * @param array|\Drupal\look\Entity\LookInterface[] $parents
   *   The set of looks.
   *
   * @return array
   *   The configuration array.
   */
  private function extractConfig(array $parents) {

    $config = [];

    if (!empty($parents)) {
      $fields = reset($parents)->getFields();

      foreach ($fields as $field_name => $field) {
        /** @var \Drupal\Core\Field\FieldItemListInterface $field */
        $storage = $field->getFieldDefinition()->getFieldStorageDefinition();

        if (!$storage->isBaseField()) {

          foreach ($parents as $look) {
            $config = $this->modifiers->extractEntityConfig($look, $field_name, $config);
          }
        }
      }
    }

    return $config;
  }

}
