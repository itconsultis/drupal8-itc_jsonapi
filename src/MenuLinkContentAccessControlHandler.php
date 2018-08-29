<?php

namespace Drupal\itc_jsonapi;

use Drupal\menu_link_content\MenuLinkContentAccessControlHandler as MenuLinkContentAccessControlHandlerBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the user entity type.
 */
class MenuLinkContentAccessControlHandler extends MenuLinkContentAccessControlHandlerBase {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view menu link content');

      default:
        return parent::checkAccess($entity, $operation, $account);
    }
  }

}
