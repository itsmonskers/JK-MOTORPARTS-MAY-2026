<?php
require_once '../config/config.php';
requireCustomer();

$conn = getDBConnection();

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate inputs
        if (!isset($_POST['cancel_request_id']) || !isset($_POST['cancel_ticket_number']) || !isset($_POST['cancel_reason'])) {
            throw new Exception('Missing required fields');
        }

        $requestId = intval($_POST['cancel_request_id']);
        $ticketNumber = sanitizeInput($_POST['cancel_ticket_number']);
        $reason = sanitizeInput($_POST['cancel_reason']);
        $note = sanitizeInput($_POST['cancel_note'] ?? '');
        
        // Validate request ID
        if ($requestId <= 0) {
            throw new Exception('Invalid request ID');
        }
        
        // Check if request exists and belongs to current user
        $checkRequest = $conn->prepare("
            SELECT id, status, ticket_number 
            FROM rsa_requests 
            WHERE id = ? AND user_id = ? AND status IN ('pending', 'in_progress')
        ");
        $checkRequest->bind_param("ii", $requestId, $_SESSION['user_id']);
        $checkRequest->execute();
        $request = $checkRequest->get_result()->fetch_assoc();
        
        if (!$request) {
            throw new Exception('Request not found or cannot be cancelled. It may have already been cancelled, completed, or does not belong to you.');
        }
        
        // Verify ticket number matches
        if ($request['ticket_number'] !== $ticketNumber) {
            throw new Exception('Ticket number mismatch');
        }
        
        // Update request status to cancelled
        $updateRequest = $conn->prepare("
            UPDATE rsa_requests 
            SET status = 'cancelled', 
                cancellation_reason = ?, 
                cancellation_note = ?, 
                date_cancelled = NOW()
            WHERE id = ?
        ");
        $updateRequest->bind_param("ssi", $reason, $note, $requestId);
        
        if (!$updateRequest->execute()) {
            throw new Exception('Failed to update request status');
        }
        
        // Log the cancellation
        logActivity($conn, $_SESSION['user_id'], 'rsa_cancel', "Cancelled RSA request: $ticketNumber");
        
        // Return success response
        echo json_encode([
            'success' => true, 
            'message' => 'Request cancelled successfully.',
            'ticket_number' => $ticketNumber
        ]);
        
    } catch (Exception $e) {
        // Log error for debugging
        error_log('Cancellation error: ' . $e->getMessage());
        
        // Return error response
        echo json_encode([
            'success' => false, 
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

closeDBConnection($conn);
?>