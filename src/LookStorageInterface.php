<?php

namespace Drupal\look;

use Drupal\Core\Entity\ContentEntityStorageInterface;
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
interface LookStorageInterface extends ContentEntityStorageInterface {

  /**
   * Finds all ancestors of a given look ID.
   *
   * @param int $id
   *   Look ID to retrieve ancestors for.
   *
   * @return \Drupal\look\Entity\LookInterface[]
   *   An array of look objects which are the ancestors of the look $id.
   */
  public function loadParents($id);

  /**
   * Finds all children of a look ID.
   *
   * @param int $id
   *   Look ID to retrieve children for.
   *
   * @return \Drupal\look\Entity\LookInterface[]
   *   An array of look objects that are the children of the look $id.
   */
  public function loadChildren($id);

  /**
   * Gets a list of Look revision IDs for a specific Look.
   *
   * @param \Drupal\look\Entity\LookInterface $entity
   *   The Look entity.
   *
   * @return int[]
   *   Look revision IDs (in ascending order).
   */
  public function revisionIds(LookInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Look author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Look revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\look\Entity\LookInterface $entity
   *   The Look entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(LookInterface $entity);

  /**
   * Unsets the language for all Look with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
