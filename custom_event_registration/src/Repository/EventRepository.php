<?php

declare(strict_types=1);

namespace Drupal\custom_event_registration\Repository;

use Drupal\Core\Database\Connection;

/**
 * Repository for event configuration database operations.
 */
class EventRepository {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * Constructs an EventRepository object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * Creates a new event configuration.
   *
   * @param array $data
   *   The event data.
   *
   * @return int|string|null
   *   The ID of the newly created event.
   */
  public function create(array $data): int|string|null {
    return $this->database->insert('event_config')
      ->fields([
        'registration_start' => $data['registration_start'],
        'registration_end' => $data['registration_end'],
        'event_date' => $data['event_date'],
        'event_name' => $data['event_name'],
        'category' => $data['category'],
      ])
      ->execute();
  }

  /**
   * Updates an existing event configuration.
   *
   * @param int $id
   *   The event ID.
   * @param array $data
   *   The event data to update.
   *
   * @return int
   *   The number of rows affected.
   */
  public function update(int $id, array $data): int {
    return $this->database->update('event_config')
      ->fields([
        'registration_start' => $data['registration_start'],
        'registration_end' => $data['registration_end'],
        'event_date' => $data['event_date'],
        'event_name' => $data['event_name'],
        'category' => $data['category'],
      ])
      ->condition('id', $id)
      ->execute();
  }

  /**
   * Deletes an event configuration.
   *
   * @param int $id
   *   The event ID.
   *
   * @return int
   *   The number of rows affected.
   */
  public function delete(int $id): int {
    return $this->database->delete('event_config')
      ->condition('id', $id)
      ->execute();
  }

  /**
   * Gets an event by ID.
   *
   * @param int $id
   *   The event ID.
   *
   * @return object|null
   *   The event object or null if not found.
   */
  public function getById(int $id): ?object {
    $result = $this->database->select('event_config', 'ec')
      ->fields('ec')
      ->condition('id', $id)
      ->execute()
      ->fetchObject();

    return $result ?: NULL;
  }

  /**
   * Gets all events.
   *
   * @return array
   *   An array of event objects.
   */
  public function getAll(): array {
    return $this->database->select('event_config', 'ec')
      ->fields('ec')
      ->orderBy('event_date', 'ASC')
      ->execute()
      ->fetchAll();
  }

  /**
   * Gets all unique categories.
   *
   * @return array
   *   An array of categories.
   */
  public function getCategories(): array {
    $results = $this->database->select('event_config', 'ec')
      ->fields('ec', ['category'])
      ->distinct()
      ->orderBy('category', 'ASC')
      ->execute()
      ->fetchCol();

    return $results ?: [];
  }

  /**
   * Gets events that are currently open for registration.
   *
   * @return array
   *   An array of event objects.
   */
  public function getActiveEvents(): array {
    $today = date('Y-m-d');

    return $this->database->select('event_config', 'ec')
      ->fields('ec')
      ->condition('registration_start', $today, '<=')
      ->condition('registration_end', $today, '>=')
      ->orderBy('event_date', 'ASC')
      ->execute()
      ->fetchAll();
  }

  /**
   * Gets categories from active events.
   *
   * @return array
   *   An array of categories keyed by category name.
   */
  public function getActiveCategoriesOptions(): array {
    $today = date('Y-m-d');

    $results = $this->database->select('event_config', 'ec')
      ->fields('ec', ['category'])
      ->condition('registration_start', $today, '<=')
      ->condition('registration_end', $today, '>=')
      ->distinct()
      ->orderBy('category', 'ASC')
      ->execute()
      ->fetchCol();

    $options = [];
    foreach ($results as $category) {
      $options[$category] = $category;
    }

    return $options;
  }

  /**
   * Gets event dates by category (active events only).
   *
   * @param string $category
   *   The category to filter by.
   *
   * @return array
   *   An array of event dates keyed by date.
   */
  public function getEventDatesByCategory(string $category): array {
    $today = date('Y-m-d');

    $results = $this->database->select('event_config', 'ec')
      ->fields('ec', ['event_date'])
      ->condition('category', $category)
      ->condition('registration_start', $today, '<=')
      ->condition('registration_end', $today, '>=')
      ->distinct()
      ->orderBy('event_date', 'ASC')
      ->execute()
      ->fetchCol();

    $options = [];
    foreach ($results as $date) {
      $options[$date] = $date;
    }

    return $options;
  }

  /**
   * Gets events by category and date (active events only).
   *
   * @param string $category
   *   The category to filter by.
   * @param string $event_date
   *   The event date to filter by.
   *
   * @return array
   *   An array of events keyed by ID.
   */
  public function getEventsByCategoryAndDate(string $category, string $event_date): array {
    $today = date('Y-m-d');

    $results = $this->database->select('event_config', 'ec')
      ->fields('ec', ['id', 'event_name'])
      ->condition('category', $category)
      ->condition('event_date', $event_date)
      ->condition('registration_start', $today, '<=')
      ->condition('registration_end', $today, '>=')
      ->orderBy('event_name', 'ASC')
      ->execute()
      ->fetchAllKeyed();

    return $results ?: [];
  }

  /**
   * Gets all unique event dates.
   *
   * @return array
   *   An array of event dates keyed by date.
   */
  public function getAllEventDates(): array {
    $results = $this->database->select('event_config', 'ec')
      ->fields('ec', ['event_date'])
      ->distinct()
      ->orderBy('event_date', 'DESC')
      ->execute()
      ->fetchCol();

    $options = [];
    foreach ($results as $date) {
      $options[$date] = $date;
    }

    return $options;
  }

  /**
   * Gets events by date (for admin listing).
   *
   * @param string $event_date
   *   The event date to filter by.
   *
   * @return array
   *   An array of events keyed by ID.
   */
  public function getEventsByDate(string $event_date): array {
    $results = $this->database->select('event_config', 'ec')
      ->fields('ec', ['id', 'event_name'])
      ->condition('event_date', $event_date)
      ->orderBy('event_name', 'ASC')
      ->execute()
      ->fetchAllKeyed();

    return $results ?: [];
  }

  /**
   * Checks if there are any active events.
   *
   * @return bool
   *   TRUE if there are active events, FALSE otherwise.
   */
  public function hasActiveEvents(): bool {
    $today = date('Y-m-d');

    $count = $this->database->select('event_config', 'ec')
      ->condition('registration_start', $today, '<=')
      ->condition('registration_end', $today, '>=')
      ->countQuery()
      ->execute()
      ->fetchField();

    return (int) $count > 0;
  }

  /**
   * Gets all events as options array.
   *
   * @return array
   *   An array of events keyed by ID.
   */
  public function getAllEventsOptions(): array {
    $results = $this->database->select('event_config', 'ec')
      ->fields('ec', ['id', 'event_name', 'event_date'])
      ->orderBy('event_date', 'DESC')
      ->execute()
      ->fetchAll();

    $options = [];
    foreach ($results as $event) {
      $options[$event->id] = $event->event_name . ' (' . $event->event_date . ')';
    }

    return $options;
  }

}
