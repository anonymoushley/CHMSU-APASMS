<?php
// Script to update schedules table for proper grouping
require_once 'config/database.php';

try {
    // Create the junction table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `schedule_applicants` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `schedule_id` int(11) NOT NULL,
        `applicant_id` int(11) NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        FOREIGN KEY (`schedule_id`) REFERENCES `schedules`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
        FOREIGN KEY (`applicant_id`) REFERENCES `personal_info`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
        UNIQUE KEY `unique_schedule_applicant` (`schedule_id`, `applicant_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    // Get all existing schedules with applicant_id
    $stmt = $pdo->query("SELECT id, applicant_id, event_date, event_time, end_time, venue FROM schedules WHERE applicant_id IS NOT NULL");
    $existingSchedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group schedules by date, time, and venue
    $groupedSchedules = [];
    foreach ($existingSchedules as $schedule) {
        $key = $schedule['event_date'] . '_' . $schedule['event_time'] . '_' . $schedule['end_time'] . '_' . $schedule['venue'];
        
        if (!isset($groupedSchedules[$key])) {
            $groupedSchedules[$key] = [
                'event_date' => $schedule['event_date'],
                'event_time' => $schedule['event_time'],
                'end_time' => $schedule['end_time'],
                'venue' => $schedule['venue'],
                'applicant_ids' => []
            ];
        }
        $groupedSchedules[$key]['applicant_ids'][] = $schedule['applicant_id'];
    }

    // Create new grouped schedules and populate junction table
    foreach ($groupedSchedules as $key => $group) {
        // Insert new schedule (without applicant_id)
        $stmt = $pdo->prepare("INSERT INTO schedules (event_date, event_time, end_time, venue, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$group['event_date'], $group['event_time'], $group['end_time'], $group['venue']]);
        $newScheduleId = $pdo->lastInsertId();

        // Insert into junction table for each applicant
        foreach ($group['applicant_ids'] as $applicantId) {
            $stmt = $pdo->prepare("INSERT INTO schedule_applicants (schedule_id, applicant_id, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$newScheduleId, $applicantId]);
        }
    }

    // Remove old schedules with applicant_id
    $pdo->exec("DELETE FROM schedules WHERE applicant_id IS NOT NULL");

    // Remove applicant_id column from schedules table
    $pdo->exec("ALTER TABLE schedules DROP COLUMN applicant_id");

    echo "Successfully updated schedules table for proper grouping!\n";
    echo "Created " . count($groupedSchedules) . " grouped schedules.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
