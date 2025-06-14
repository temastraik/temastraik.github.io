<?php
class Event {
    private $db;
    private $id;
    private $event_name;
    private $event_date;
    private $user_id;
    private $recurrence_pattern;
    private $recurrence_end_date;

    public function __construct($db, $id = null) {
        $this->db = $db;
        if ($id) {
            $this->load($id);
        }
    }

    public function load($id) {
        $stmt = $this->db->prepare("SELECT * FROM Events WHERE id = :id");
        $stmt->bindParam(':id', $id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $event = $result->fetchArray(SQLITE3_ASSOC);

        if ($event) {
            $this->id = $event['id'];
            $this->event_name = $event['event_name'];
            $this->event_date = $event['event_date'];
            $this->user_id = $event['user_id'];
            $this->recurrence_pattern = $event['recurrence_pattern'];
            $this->recurrence_end_date = $event['recurrence_end_date'];
            return true;
        }
        return false;
    }

    public function create($data) {
        $this->event_name = $data['event_name'];
        $this->event_date = $data['event_date'];
        $this->user_id = $data['user_id'];
        $this->recurrence_pattern = $data['recurrence'] ?? 'none';
        $this->recurrence_end_date = !empty($data['recurrence_end_date']) ? $data['recurrence_end_date'] : null;

        if (strlen($this->event_date) == 10) {
            $this->event_date .= ' 00:00';
        }

        $stmt = $this->db->prepare("INSERT INTO Events (event_name, event_date, user_id, recurrence_pattern, recurrence_end_date) 
                                  VALUES (:name, :date, :user_id, :recurrence, :end_date)");
        $stmt->bindValue(':name', $this->event_name, SQLITE3_TEXT);
        $stmt->bindValue(':date', $this->event_date, SQLITE3_TEXT);
        $stmt->bindValue(':user_id', $this->user_id, SQLITE3_INTEGER);
        $stmt->bindValue(':recurrence', $this->recurrence_pattern, SQLITE3_TEXT);
        $stmt->bindValue(':end_date', $this->recurrence_end_date, SQLITE3_TEXT);
        
        if ($stmt->execute()) {
            $this->id = $this->db->lastInsertRowID();
            return true;
        }
        return false;
    }

    public function update($data) {
        $this->event_name = $data['event_name'];
        $this->event_date = $data['event_date'];
        $this->recurrence_pattern = $data['recurrence'] ?? 'none';
        $this->recurrence_end_date = !empty($data['recurrence_end_date']) ? $data['recurrence_end_date'] : null;

        $stmt = $this->db->prepare("UPDATE Events SET 
                                  event_name = :name, 
                                  event_date = :date, 
                                  recurrence_pattern = :recurrence, 
                                  recurrence_end_date = :end_date
                                  WHERE id = :id");
        $stmt->bindValue(':name', $this->event_name, SQLITE3_TEXT);
        $stmt->bindValue(':date', $this->event_date, SQLITE3_TEXT);
        $stmt->bindValue(':recurrence', $this->recurrence_pattern, SQLITE3_TEXT);
        $stmt->bindValue(':end_date', $this->recurrence_end_date, SQLITE3_TEXT);
        $stmt->bindValue(':id', $this->id, SQLITE3_INTEGER);
        
        return $stmt->execute();
    }

    public function delete() {
        $stmt = $this->db->prepare("DELETE FROM Events WHERE id = :id");
        $stmt->bindValue(':id', $this->id, SQLITE3_INTEGER);
        return $stmt->execute();
    }

    // Геттеры
    public function getId() { return $this->id; }
    public function getName() { return $this->event_name; }
    public function getDate() { return $this->event_date; }
    public function getRecurrencePattern() { return $this->recurrence_pattern; }
    public function getRecurrenceEndDate() { return $this->recurrence_end_date; }

    // Статические методы для работы с коллекциями событий
    public static function getEventsForMonth($db, $user_id, $start_date, $end_date) {
        $events_query = "SELECT id, event_name, event_date, recurrence_pattern, recurrence_end_date FROM Events WHERE user_id = :user_id AND (
            (date(event_date) BETWEEN date(:start_date) AND date(:end_date)) OR
            (recurrence_pattern != 'none' AND date(event_date) <= date(:end_date) AND (recurrence_end_date IS NULL OR date(recurrence_end_date) >= date(:start_date)))
        ) ORDER BY event_date";
        
        $stmt = $db->prepare($events_query);
        $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
        $stmt->bindValue(':start_date', $start_date, SQLITE3_TEXT);
        $stmt->bindValue(':end_date', $end_date, SQLITE3_TEXT);
        $result = $stmt->execute();
        
        $events = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $event_day = date('j', strtotime($row['event_date']));
            $events[$event_day][] = $row;
        }
        
        return $events;
    }

    public static function processRecurringEvents($events, $start_date, $end_date) {
        $recurring_events = [];
        
        foreach ($events as $event) {
            if ($event['recurrence_pattern'] === 'none') {
                continue;
            }

            try {
                $start = new DateTime($event['event_date']);
                $end = $event['recurrence_end_date'] ? new DateTime($event['recurrence_end_date']) : new DateTime('+1 year');
                $current_month_start = new DateTime($start_date);
                $current_month_end = new DateTime($end_date);
                
                $pattern = explode(':', $event['recurrence_pattern']);
                $type = $pattern[0];
                $value = isset($pattern[1]) ? (int)$pattern[1] : 1;
                
                $interval = null;
                $byday = null;
                
                switch ($type) {
                    case 'daily':
                        $interval = new DateInterval("P{$value}D");
                        break;
                    case 'weekly':
                        $interval = new DateInterval("P{$value}W");
                        $byday = $start->format('N');
                        break;
                    case 'monthly':
                        $interval = new DateInterval("P{$value}M");
                        break;
                    case 'yearly':
                        $interval = new DateInterval("P{$value}Y");
                        break;
                    default:
                        continue 2;
                }
                
                $period = new DatePeriod($start, $interval, $end);
                
                foreach ($period as $date) {
                    if ($type === 'weekly' && $date->format('N') != $byday) {
                        continue;
                    }
                    
                    if ($date >= $current_month_start && $date <= $current_month_end) {
                        $event_day = (int)$date->format('j');
                        $event_copy = $event;
                        $event_copy['event_date'] = $date->format('Y-m-d H:i:s');
                        
                        if (!isset($recurring_events[$event_day])) {
                            $recurring_events[$event_day] = [];
                        }
                        $recurring_events[$event_day][] = $event_copy;
                    }
                }
            } catch (Exception $e) {
                error_log("Error processing recurring event: " . $e->getMessage());
                continue;
            }
        }
        
        return $recurring_events;
    }

    public static function handleAddEventRequest($db, $post_data, $user_id, $month, $year) {
        if (!isset($post_data['add_event'])) {
            return ['success' => false];
        }

        $event = new Event($db);
        $event_data = [
            'event_name' => $post_data['event_name'],
            'event_date' => $post_data['event_date'],
            'user_id' => $user_id,
            'recurrence' => $post_data['recurrence'] ?? 'none',
            'recurrence_end_date' => !empty($post_data['recurrence_end_date']) ? $post_data['recurrence_end_date'] : null
        ];
        
        if ($event->create($event_data)) {
            return [
                'success' => true,
                'redirect' => "?month=$month&year=$year"
            ];
        } else {
            return [
                'success' => false,
                'error' => "Ошибка при добавлении события"
            ];
        }
    }
}
?>
