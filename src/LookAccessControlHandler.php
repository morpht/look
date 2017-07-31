<?php

namespace Drupal\look;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Look entity.
 *
 * @see \Drupal\look\Entity\Look.
 */
class LookAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\look\Entity\LookInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished look entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published look entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit look entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete look entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add look entities');
  }

}
