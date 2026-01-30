<?php

declare(strict_types=1);

namespace Drupal\custom_event_registration\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;

/**
 * Service for handling email notifications.
 */
class MailService {

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected MailManagerInterface $mailManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * Constructs a MailService object.
   *
   * @param \Drupal\Core\Mail\MailManagerInterface $mailManager
   *   The mail manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   */
  public function __construct(
    MailManagerInterface $mailManager,
    ConfigFactoryInterface $configFactory,
    LanguageManagerInterface $languageManager
  ) {
    $this->mailManager = $mailManager;
    $this->configFactory = $configFactory;
    $this->languageManager = $languageManager;
  }

  /**
   * Sends confirmation email to the user.
   *
   * @param array $data
   *   The registration data.
   *
   * @return bool
   *   TRUE if the email was sent successfully, FALSE otherwise.
   */
  public function sendUserConfirmation(array $data): bool {
    $module = 'custom_event_registration';
    $key = 'registration_confirmation';
    $to = $data['email'];
    $langcode = $this->languageManager->getDefaultLanguage()->getId();

    $params = [
      'full_name' => $data['full_name'],
      'email' => $data['email'],
      'college_name' => $data['college_name'],
      'department' => $data['department'],
      'event_name' => $data['event_name'],
      'event_date' => $data['event_date'],
      'category' => $data['category'],
      'created' => $data['created'],
    ];

    $result = $this->mailManager->mail($module, $key, $to, $langcode, $params, NULL, TRUE);

    return $result['result'] ?? FALSE;
  }

  /**
   * Sends notification email to admin if enabled.
   *
   * @param array $data
   *   The registration data.
   *
   * @return bool
   *   TRUE if the email was sent successfully, FALSE otherwise.
   */
  public function sendAdminNotification(array $data): bool {
    $config = $this->configFactory->get('custom_event_registration.settings');
    $adminNotificationEnabled = $config->get('admin_notification_enabled');
    $adminEmail = $config->get('admin_email');

    // Only send if admin notification is enabled and email is configured.
    if (!$adminNotificationEnabled || empty($adminEmail)) {
      return FALSE;
    }

    $module = 'custom_event_registration';
    $key = 'admin_notification';
    $langcode = $this->languageManager->getDefaultLanguage()->getId();

    $params = [
      'full_name' => $data['full_name'],
      'email' => $data['email'],
      'college_name' => $data['college_name'],
      'department' => $data['department'],
      'event_name' => $data['event_name'],
      'event_date' => $data['event_date'],
      'category' => $data['category'],
      'created' => $data['created'],
    ];

    $result = $this->mailManager->mail($module, $key, $adminEmail, $langcode, $params, NULL, TRUE);

    return $result['result'] ?? FALSE;
  }

  /**
   * Gets the admin email from configuration.
   *
   * @return string
   *   The admin email address.
   */
  public function getAdminEmail(): string {
    $config = $this->configFactory->get('custom_event_registration.settings');
    return $config->get('admin_email') ?? '';
  }

  /**
   * Checks if admin notifications are enabled.
   *
   * @return bool
   *   TRUE if enabled, FALSE otherwise.
   */
  public function isAdminNotificationEnabled(): bool {
    $config = $this->configFactory->get('custom_event_registration.settings');
    return (bool) $config->get('admin_notification_enabled');
  }

}
