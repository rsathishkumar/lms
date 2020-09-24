<?php

namespace Drupal\lms\TwigExtension;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\group\Entity\Group;
use Drupal\opigno_learning_path\Controller\LearningPathController;
use Drupal\opigno_learning_path\Entity\LPStatus;
use Drupal\opigno_learning_path\LearningPathAccess;
use Drupal\opigno_learning_path\Progress;

/**
 * Class DefaultTwigExtension.
 */
class DefaultTwigExtension extends \Twig_Extension {

  /**
   * {@inheritdoc}
   */
  public function getTokenParsers() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getNodeVisitors() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getTests() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      new \Twig_SimpleFunction(
        'modified_get_start_link',
        [$this, 'modified_get_start_link']
      )
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getOperators() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'lms.twig.extension';
  }

  /**
   * Returns group start link.
   *
   * @param mixed $group
   *   Group.
   * @param array $attributes
   *   Attributes.
   *
   * @return array|mixed|null
   *   Group start link or empty.
   */
  public function modified_get_start_link($group = NULL, array $attributes = []) {
    if (!$group) {
      $group = \Drupal::routeMatch()->getParameter('group');
    }

    if (filter_var($group, FILTER_VALIDATE_INT) !== FALSE) {
      $group = Group::load($group);
    }

    if (empty($group)) {
      return[];
    }

    $current_route = \Drupal::routeMatch()->getRouteName();
    $visibility = $group->field_learning_path_visibility->value;
    $validation = $group->field_requires_validation->value;
    $account = \Drupal::currentUser();
    $is_anonymous = $account->id() === 0;

    if ($is_anonymous && $visibility != 'public') {
      return[];
    }

    $member_pending = $visibility === 'semiprivate' && $validation
      && !LearningPathAccess::statusGroupValidation($group, $account);
    $module_commerce_enabled = \Drupal::moduleHandler()->moduleExists('opigno_commerce');
    $required_trainings = LearningPathAccess::hasUncompletedRequiredTrainings($group, $account);

    $completed = opigno_learning_path_completed_on($group->id(), $account->id());
    $progress = $this->getProgress($group->id(), $account->id());

    if (
      $module_commerce_enabled
      && $group->hasField('field_lp_price')
      && $group->get('field_lp_price')->value != 0
      && !$group->getMember($account)) {
      // Get currency code.
      $cs = \Drupal::service('commerce_store.current_store');
      $store_default = $cs->getStore();
      $default_currency = $store_default ? $store_default->getDefaultCurrencyCode() : '';

      $text = t('Add to cart') . ' / ' . $group->get('field_lp_price')->value . ' ' . $default_currency;
      $route = 'opigno_commerce.subscribe_with_payment';
    }
    elseif ($visibility === 'public' && $is_anonymous) {
      $text = t('Start');
      $route = 'opigno_learning_path.steps.start';
      $attributes['class'][] = 'use-ajax';
      $attributes['class'][] = 'start-link';
    }
    elseif ($completed) {
      $text =t('Completed');
    //  return $text;
      $route = 'opigno_learning_path.steps.start';
      $attributes['class'][] = 'use-ajax';
    }
    elseif (!$group->getMember($account)) {
      if ($group->hasPermission('join group', $account)) {
        $text = ($current_route == 'entity.group.canonical') ? t('Subscribe to training') : t('Learn more');
        $route = ($current_route == 'entity.group.canonical') ? 'entity.group.join' : 'entity.group.canonical';
        if ($current_route == 'entity.group.canonical') {
          $attributes['class'][] = 'join-link';
        }
      }
      else {
        return '';
      }
    }
    elseif ($member_pending || $required_trainings) {
      $text = $required_trainings ? t('Prerequisites Pending') : t('Approval Pending');
      $route = 'entity.group.canonical';
      $attributes['class'][] = 'approval-pending-link';
    }
    else {
      $text = opigno_learning_path_started($group, $account) ? t('Resume') : t('Start');
      if ($progress == 0) {
        $text = t('Start');
      }
      $route = 'opigno_learning_path.steps.start';
      $attributes['class'][] = 'use-ajax';

      if (opigno_learning_path_started($group, $account)) {
        $attributes['class'][] = 'continue-link';
      }
      else {
        $attributes['class'][] = 'start-link';
      }
    }

    $args = ['group' => $group->id()];
    $url = Url::fromRoute($route, $args, ['attributes' => $attributes]);
    $l = Link::fromTextAndUrl($text, $url)->toRenderable();

    return render($l);
  }

  public function getProgress($group_id, $account_id) {
    $group = Group::load($group_id);
    $latest_cert_date = LPStatus::getTrainingStartDate($group, $account_id);

    $activities = opigno_learning_path_get_activities($group_id, $account_id, $latest_cert_date);

    $total = count($activities);
    $attempted = count(array_filter($activities, function ($activity) {
      return $activity['answers'] > 0;
    }));

    return $total > 0 ? $attempted / $total : 0;
  }

}
