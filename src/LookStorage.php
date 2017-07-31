<?php

namespace Drupal\look;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\look\Entity\LookInterface;

/**
 * Defines the storage handler class for Look entities.
 *
 * This extends the base storage class, adding required special handling for
 * Look entities.
 *
 * @ingroup look
 */
class LookStorage extends SqlContentEntityStorage implements LookStorageInterface {

  /**
   * Array of all ancestors keyed by child look ID.
   *
   * @var array
   */
  protected $parents = [];

  /**
   * Array of all children keyed by parent look ID.
   *
   * @var array
   */
  protected $children = [];

  /**
   * {@inheritdoc}
   */
  public function loadParents($id) {
    if (!isset($this->parents[$id])) {
      $parents = [];
      /** @var \Drupal\look\Entity\Look $look */
      if ($look = $this->load($id)) {
        $parents[$look->id()] = $look;

        while ($parent_id = $look->getParent()) {
          if ($look = $this->load($parent_id)) {
            $parents[$look->id()] = $look;
          }
        }
      }
      $this->parents[$id] = $parents;
    }
    return $this->parents[$id];
  }

  /**
   * {@inheritdoc}
   */
  public function loadChildren($id) {
    if (!isset($this->children[$id])) {
      $children = [];
      $query = $this->database->select('look_field_data', 'l');
      $query->addField('l', 'id');
      $query->condition('l.parent', $id);
      $query->orderBy('l.weight');
      $query->orderBy('l.name');
      if ($ids = $query->execute()->fetchCol()) {
        $children = $this->loadMultiple($ids);
      }
      $this->children[$id] = $children;
    }
    return $this->children[$id];
  }

  /**
   * {@inheritdoc}
   */
  public function revisionIds(LookInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {look_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {look_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(LookInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {look_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('look_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
