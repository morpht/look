<?php

namespace Drupal\look\Entity;

use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Look entities.
 *
 * @ingroup look
 */
interface LookInterface extends RevisionableInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Look name.
   *
   * @return string
   *   Name of the Look.
   */
  public function getName();

  /**
   * Sets the Look name.
   *
   * @param string $name
   *   The Look name.
   *
   * @return $this
   */
  public function setName($name);

  /**
   * Gets the Look parent.
   *
   * @return int
   *   Parent of the Look.
   */
  public function getParent();

  /**
   * Sets the Look parent.
   *
   * @param int $parent
   *   The Look parent.
   *
   * @return $this
   */
  public function setParent($parent);

  /**
   * Gets the Look weight.
   *
   * @return int
   *   Weight of the Look.
   */
  public function getWeight();

  /**
   * Sets the Look weight.
   *
   * @param int $weight
   *   The Look weight.
   *
   * @return $this
   */
  public function setWeight($weight);

  /**
   * Gets the Look creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Look.
   */
  public function getCreatedTime();

  /**
   * Sets the Look creation timestamp.
   *
   * @param int $timestamp
   *   The Look creation timestamp.
   *
   * @return $this
   *   The called Look entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Look published status indicator.
   *
   * Unpublished Look are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Look is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Look.
   *
   * @param bool $published
   *   TRUE to set this Look to published, FALSE to set it to unpublished.
   *
   * @return $this
   *   The called Look entity.
   */
  public function setPublished($published);

  /**
   * Gets the Look revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Look revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return $this
   *   The called Look entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Look revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Look revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return $this
   *   The called Look entity.
   */
  public function setRevisionUserId($uid);

}
