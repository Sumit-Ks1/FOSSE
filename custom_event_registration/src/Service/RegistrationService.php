<?php

declare(strict_types=1);

namespace Drupal\custom_event_registration\Service;

use Drupal\custom_event_registration\Repository\EventRepository;
use Drupal\custom_event_registration\Repository\RegistrationRepository;

/**
 * Service for registration-related business logic.
 */
class RegistrationService {

  /**
   * The registration repository.
   *
   * @var \Drupal\custom_event_registration\Repository\RegistrationRepository
   */
  protected RegistrationRepository $registrationRepository;

  /**
   * The event repository.
   *
   * @var \Drupal\custom_event_registration\Repository\EventRepository
   */
  protected EventRepository $eventRepository;

  /**
   * The mail service.
   *
   * @var \Drupal\custom_event_registration\Service\MailService
   */
  protected MailService $mailService;

  /**
   * Constructs a RegistrationService object.
   *
   * @param \Drupal\custom_event_registration\Repository\RegistrationRepository $registrationRepository
   *   The registration repository.
   * @param \Drupal\custom_event_registration\Repository\EventRepository $eventRepository
   *   The event repository.
   * @param \Drupal\custom_event_registration\Service\MailService $mailService
   *   The mail service.
   */
  public function __construct(
    RegistrationRepository $registrationRepository,
    EventRepository $eventRepository,
    MailService $mailService
  ) {
    $this->registrationRepository = $registrationRepository;
    $this->eventRepository = $eventRepository;
    $this->mailService = $mailService;
  }

  /**
   * Creates a new registration.
   *
   * @param array $data
   *   The registration data.
   *
   * @return int|string|null
   *   The ID of the newly created registration.
   */
  public function createRegistration(array $data): int|string|null {
    $data['created'] = time();
    $registrationId = $this->registrationRepository->create($data);

    if ($registrationId) {
      // Get event details for email.
      $event = $this->eventRepository->getById((int) $data['event_id']);

      if ($event) {
        $emailData = [
          'full_name' => $data['full_name'],
          'email' => $data['email'],
          'college_name' => $data['college_name'],
          'department' => $data['department'],
          'event_name' => $event->event_name,
          'event_date' => $event->event_date,
          'category' => $event->category,
          'created' => $data['created'],
        ];

        // Send confirmation email to user.
        $this->mailService->sendUserConfirmation($emailData);

        // Send admin notification if enabled.
        $this->mailService->sendAdminNotification($emailData);
      }
    }

    return $registrationId;
  }

  /**
   * Gets a registration by ID.
   *
   * @param int $id
   *   The registration ID.
   *
   * @return object|null
   *   The registration object or null if not found.
   */
  public function getRegistration(int $id): ?object {
    return $this->registrationRepository->getById($id);
  }

  /**
   * Gets all registrations.
   *
   * @return array
   *   An array of registration objects.
   */
  public function getAllRegistrations(): array {
    return $this->registrationRepository->getAll();
  }

  /**
   * Gets registrations by event ID.
   *
   * @param int $event_id
   *   The event ID.
   *
   * @return array
   *   An array of registration objects.
   */
  public function getRegistrationsByEventId(int $event_id): array {
    return $this->registrationRepository->getByEventId($event_id);
  }

  /**
   * Gets registrations by event date.
   *
   * @param string $event_date
   *   The event date.
   *
   * @return array
   *   An array of registration objects.
   */
  public function getRegistrationsByEventDate(string $event_date): array {
    return $this->registrationRepository->getByEventDate($event_date);
  }

  /**
   * Checks if a duplicate registration exists.
   *
   * @param string $email
   *   The email address.
   * @param int $event_id
   *   The event ID.
   *
   * @return bool
   *   TRUE if duplicate exists, FALSE otherwise.
   */
  public function isDuplicateRegistration(string $email, int $event_id): bool {
    return $this->registrationRepository->isDuplicateRegistration($email, $event_id);
  }

  /**
   * Gets the count of registrations for an event.
   *
   * @param int $event_id
   *   The event ID.
   *
   * @return int
   *   The count of registrations.
   */
  public function getRegistrationCount(int $event_id): int {
    return $this->registrationRepository->getCountByEventId($event_id);
  }

  /**
   * Gets the count of registrations for an event date.
   *
   * @param string $event_date
   *   The event date.
   *
   * @return int
   *   The count of registrations.
   */
  public function getRegistrationCountByDate(string $event_date): int {
    return $this->registrationRepository->getCountByEventDate($event_date);
  }

  /**
   * Validates the full name field.
   *
   * @param string $name
   *   The full name to validate.
   *
   * @return bool
   *   TRUE if valid, FALSE otherwise.
   */
  public function validateFullName(string $name): bool {
    // Allow letters, spaces, and basic punctuation (like hyphens and apostrophes).
    return (bool) preg_match('/^[a-zA-Z\s\-\'\.]+$/', $name);
  }

  /**
   * Validates the college name field.
   *
   * @param string $collegeName
   *   The college name to validate.
   *
   * @return bool
   *   TRUE if valid, FALSE otherwise.
   */
  public function validateCollegeName(string $collegeName): bool {
    // Allow alphanumeric, spaces, and basic punctuation.
    return (bool) preg_match('/^[a-zA-Z0-9\s\-\'\.&,]+$/', $collegeName);
  }

  /**
   * Validates the department field.
   *
   * @param string $department
   *   The department to validate.
   *
   * @return bool
   *   TRUE if valid, FALSE otherwise.
   */
  public function validateDepartment(string $department): bool {
    // Allow alphanumeric, spaces, and basic punctuation.
    return (bool) preg_match('/^[a-zA-Z0-9\s\-\'\.&,]+$/', $department);
  }

  /**
   * Validates email format.
   *
   * @param string $email
   *   The email to validate.
   *
   * @return bool
   *   TRUE if valid, FALSE otherwise.
   */
  public function validateEmail(string $email): bool {
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
  }

  /**
   * Gets all registrations for CSV export.
   *
   * @param int|null $event_id
   *   Optional event ID to filter by.
   *
   * @return array
   *   An array of registration objects.
   */
  public function getRegistrationsForExport(?int $event_id = NULL): array {
    return $this->registrationRepository->getAllForExport($event_id);
  }

  /**
   * Gets filtered registrations for admin listing.
   *
   * @param string|null $event_date
   *   Optional event date filter.
   * @param int|null $event_id
   *   Optional event ID filter.
   *
   * @return array
   *   An array of registration objects.
   */
  public function getFilteredRegistrations(?string $event_date = NULL, ?int $event_id = NULL): array {
    return $this->registrationRepository->getFilteredRegistrations($event_date, $event_id);
  }

  /**
   * Deletes a registration.
   *
   * @param int $id
   *   The registration ID.
   *
   * @return int
   *   The number of rows affected.
   */
  public function deleteRegistration(int $id): int {
    return $this->registrationRepository->delete($id);
  }

}
