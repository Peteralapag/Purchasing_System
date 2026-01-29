<?php

class PurchasingController
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function savePurchasingLog($reference_type, $reference_id, $action, $action_by)
    {
        $stmt = $this->db->prepare("
            INSERT INTO purchasing_logs
            (reference_type, reference_id, action, action_by, action_date)
            VALUES (?,?,?,?,NOW())
        ");

        $stmt->bind_param(
            "ssss",
            $reference_type,
            $reference_id,
            $action,
            $action_by
        );

        $stmt->execute();
        $stmt->close();
    }

    public function getLogs($reference_type, $reference_id)
    {
        $stmt = $this->db->prepare("
            SELECT action, action_by, action_date
            FROM purchasing_logs
            WHERE reference_type = ?
              AND reference_id = ?
            ORDER BY action_date DESC
        ");

        $stmt->bind_param("ss", $reference_type, $reference_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $logs = [];
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }

        $stmt->close();
        return $logs;
    }
}
