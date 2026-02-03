<?php

declare(strict_types=1);

namespace Drupal\custom_event_registration\Repository;

use Drupal\Core\Database\Connection;

/**
 * Repository for event registration database operations.
 */
class RegistrationRepository {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * Constructs a RegistrationRepository object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
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
  public function create(array $data): int|string|null {
    return $this->database->insert('event_registration')
      ->fields([
        'full_name' => $data['full_name'],
        'email' => strtolower($data['email']),
        'college_name' => $data['college_name'],
        'department' => $data['department'],
        'event_id' => $data['event_id'],
        'created' => $data['created'] ?? time(),
      ])
      ->execute();
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
  public function getById(int $id): ?object {
    $result = $this->database->select('event_registration', 'er')
      ->fields('er')
      ->condition('id', $id)
      ->execute()
      ->fetchObject();

    return $result ?: NULL;
  }

  /**
   * Gets all registrations.
   *
   * @return array
   *   An array of registration objects.
   */
  public function getAll(): array {
    $query = $this->database->select('event_registration', 'er');
    $query->join('event_config', 'ec', 'er.event_id = ec.id');
    $query->fields('er', [
      'id',
      'full_name',
      'email',
      'college_name',
      'department',
      'event_id',
      'created',
    ]);
    $query->fields('ec', [
      'event_name',
      'event_date',
      'category',
    ]);
    $query->orderBy('er.created', 'DESC');

    return $query->execute()->fetchAll();
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
  public function getByEventId(int $event_id): array {
    $query = $this->database->select('event_registration', 'er');
    $query->join('event_config', 'ec', 'er.event_id = ec.id');
    $query->fields('er', [
      'id',
      'full_name',
      'email',
      'college_name',
      'department',
      'event_id',
      'created',
    ]);
    $query->fields('ec', [
      'event_name',
      'event_date',
      'category',
    ]);
    $query->condition('er.event_id', $event_id);
    $query->orderBy('er.created', 'DESC');

    return $query->execute()->fetchAll();
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
  public function getByEventDate(string $event_date): array {
    $query = $this->database->select('event_registration', 'er');
    $query->join('event_config', 'ec', 'er.event_id = ec.id');
    $query->fields('er', [
      'id',
      'full_name',
      'email',
      'college_name',
      'department',
      'event_id',
      'created',
    ]);
    $query->fields('ec', [
      'event_name',
      'event_date',
      'category',
    ]);
    $query->condition('ec.event_date', $event_date);
    $query->orderBy('er.created', 'DESC');

    return $query->execute()->fetchAll();
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
    // Get the event date for the given event_id.
    $event_date = $this->database->select('event_config', 'ec')
      ->fields('ec', ['event_date'])
      ->condition('id', $event_id)
      ->execute()
      ->fetchField();

    if (!$event_date) {
      return FALSE;
    }

    // Check for duplicate based on email + event_date combination.
    // Use case-insensitive email comparison.
    $query = $this->database->select('event_registration', 'er');
    $query->join('event_config', 'ec', 'er.event_id = ec.id');
    $query->condition('er.email', strtolower($email));
    $query->condition('ec.event_date', $event_date);
    $count = $query->countQuery()->execute()->fetchField();

    return (int) $count > 0;
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
  public function getCountByEventId(int $event_id): int {
    $count = $this->database->select('event_registration', 'er')
      ->condition('event_id', $event_id)
      ->countQuery()
      ->execute()
      ->fetchField();

    return (int) $count;
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
  public function getCountByEventDate(string $event_date): int {
    $query = $this->database->select('event_registration', 'er');
    $query->join('event_config', 'ec', 'er.event_id = ec.id');
    $query->condition('ec.event_date', $event_date);
    $count = $query->countQuery()->execute()->fetchField();

    return (int) $count;
  }

  /**
   * Deletes a registration by ID.
   *
   * @param int $id
   *   The registration ID.
   *
   * @return int
   *   The number of rows affected.
   */
  public function delete(int $id): int {
    return $this->database->delete('event_registration')
      ->condition('id', $id)
      ->execute();
  }

  /**
   * Deletes all registrations for an event.
   *
   * @param int $event_id
   *   The event ID.
   *
   * @return int
   *   The number of rows affected.
   */
  public function deleteByEventId(int $event_id): int {
    return $this->database->delete('event_registration')
      ->condition('event_id', $event_id)
      ->execute();
  }

  /**
   * Gets all registrations with event details for CSV export.
   *
   * @param int|null $event_id
   *   Optional event ID to filter by.
   *
   * @return array
   *   An array of registration objects with event details.
   */
  public function getAllForExport(?int $event_id = NULL): array {
    $query = $this->database->select('event_registration', 'er');
    $query->join('event_config', 'ec', 'er.event_id = ec.id');
    $query->fields('er', [
      'id',
      'full_name',
      'email',
      'college_name',
      'department',
      'created',
    ]);
    $query->fields('ec', [
      'event_name',
      'event_date',
      'category',
    ]);

    if ($event_id !== NULL) {
      $query->condition('er.event_id', $event_id);
    }

    $query->orderBy('er.created', 'DESC');

    return $query->execute()->fetchAll();
  }

  /**
   * Gets registrations with optional filters for admin listing.
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
    $query = $this->database->select('event_registration', 'er');
    $query->join('event_config', 'ec', 'er.event_id = ec.id');
    $query->fields('er', [
      'id',
      'full_name',
      'email',
      'college_name',
      'department',
      'event_id',
      'created',
    ]);
    $query->fields('ec', [
      'event_name',
      'event_date',
      'category',
    ]);

    if ($event_id !== NULL) {
      $query->condition('er.event_id', $event_id);
    }
    elseif ($event_date !== NULL) {
      $query->condition('ec.event_date', $event_date);
    }

    $query->orderBy('er.created', 'DESC');

    return $query->execute()->fetchAll();
  }

}
