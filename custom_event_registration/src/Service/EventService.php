<?php

declare(strict_types=1);

namespace Drupal\custom_event_registration\Service;

use Drupal\custom_event_registration\Repository\EventRepository;

/**
 * Service for event-related business logic.
 */
class EventService {

  /**
   * The event repository.
   *
   * @var \Drupal\custom_event_registration\Repository\EventRepository
   */
  protected EventRepository $eventRepository;

  /**
   * Constructs an EventService object.
   *
   * @param \Drupal\custom_event_registration\Repository\EventRepository $eventRepository
   *   The event repository.
   */
  public function __construct(EventRepository $eventRepository) {
    $this->eventRepository = $eventRepository;
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
  public function createEvent(array $data): int|string|null {
    return $this->eventRepository->create($data);
  }

  /**
   * Updates an existing event configuration.
   *
   * @param int $id
   *   The event ID.
   * @param array $data
   *   The event data.
   *
   * @return int
   *   The number of rows affected.
   */
  public function updateEvent(int $id, array $data): int {
    return $this->eventRepository->update($id, $data);
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
  public function deleteEvent(int $id): int {
    return $this->eventRepository->delete($id);
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
  public function getEvent(int $id): ?object {
    return $this->eventRepository->getById($id);
  }

  /**
   * Gets all events.
   *
   * @return array
   *   An array of event objects.
   */
  public function getAllEvents(): array {
    return $this->eventRepository->getAll();
  }

  /**
   * Gets events that are currently open for registration.
   *
   * @return array
   *   An array of event objects.
   */
  public function getActiveEvents(): array {
    return $this->eventRepository->getActiveEvents();
  }

  /**
   * Gets categories from active events as options.
   *
   * @return array
   *   An array of categories keyed by category name.
   */
  public function getActiveCategoriesOptions(): array {
    return $this->eventRepository->getActiveCategoriesOptions();
  }

  /**
   * Gets event dates by category.
   *
   * @param string $category
   *   The category to filter by.
   *
   * @return array
   *   An array of event dates keyed by date.
   */
  public function getEventDatesByCategory(string $category): array {
    return $this->eventRepository->getEventDatesByCategory($category);
  }

  /**
   * Gets events by category and date.
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
    return $this->eventRepository->getEventsByCategoryAndDate($category, $event_date);
  }

  /**
   * Gets all unique event dates.
   *
   * @return array
   *   An array of event dates keyed by date.
   */
  public function getAllEventDates(): array {
    return $this->eventRepository->getAllEventDates();
  }

  /**
   * Gets events by date.
   *
   * @param string $event_date
   *   The event date to filter by.
   *
   * @return array
   *   An array of events keyed by ID.
   */
  public function getEventsByDate(string $event_date): array {
    return $this->eventRepository->getEventsByDate($event_date);
  }

  /**
   * Checks if there are any active events.
   *
   * @return bool
   *   TRUE if there are active events, FALSE otherwise.
   */
  public function hasActiveEvents(): bool {
    return $this->eventRepository->hasActiveEvents();
  }

  /**
   * Gets all events as options.
   *
   * @return array
   *   An array of events keyed by ID.
   */
  public function getAllEventsOptions(): array {
    return $this->eventRepository->getAllEventsOptions();
  }

  /**
   * Gets the predefined category options.
   *
   * @return array
   *   An array of category options.
   */
  public function getCategoryOptions(): array {
    return [
      'Online Workshop' => 'Online Workshop',
      'Hackathon' => 'Hackathon',
      'Conference' => 'Conference',
      'One-day Workshop' => 'One-day Workshop',
    ];
  }

  /**
   * Validates that the registration period is open for at least one event.
   *
   * @return bool
   *   TRUE if registration is open, FALSE otherwise.
   */
  public function isRegistrationOpen(): bool {
    return $this->hasActiveEvents();
  }

}
