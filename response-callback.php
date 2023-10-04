<?php

// Get the raw JSON POST data
$jsonData = file_get_contents("php://input");

// Check if JSON data was received
if (!empty($jsonData)) {

    // Decode the JSON data
    $requestData = json_decode($jsonData, true);

    // Check if the JSON was decoded successfully
    if ($requestData !== null) {
        // Extract relevant data from the JSON
        $type = $requestData['data']['type'];
        $referenceId = $requestData['data']['attributes']['reference_id']; // Use reference_id as the order ID
        $attributes = $requestData['data']['attributes'];
        $status = $attributes['status'];
        
        // Update WooCommerce order status based on received status
        if ($status === 'processed') {
            // Update order status to "completed" (or any other desired status for "processed" orders)
            update_order_status($referenceId, 'completed');
        } elseif ($status === 'process_failed') {
            // Update order status to "cancelled" (or any other desired status for "failed" orders)
            update_order_status($referenceId, 'cancelled');
        }

        // Sending a response (optional)
        $response = [
            'message' => 'Received and processed the callback data successfully',
            'type' => $type,
            'reference_id' => $referenceId,
            'status' => $status,
        ];

        // Convert the response to JSON and send it back
        header('Content-Type: application/json');
        echo json_encode($response);
    } else {
        // JSON decoding failed
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON data']);
    }
} else {
    // No JSON data received
    http_response_code(400);
    echo json_encode(['error' => 'No JSON data received']);
}

// Function to update WooCommerce order status
function update_order_status($order_id, $new_status) {
    // Include WooCommerce functions
    $wp_load_path = dirname(__FILE__,'4') . '/wp-load.php';
    
    if (file_exists($wp_load_path)) {
        include_once($wp_load_path);
        
        $order = wc_get_order($order_id);
        
        if ($order) {
            // Order found
            $order->update_status($new_status);
        } else {
            // Handle the case where the order is not found
            echo "Order not found for ID: $order_id";
        }
    } else {
        // Handle the case where wp-load.php cannot be found
        die('wp-load.php not found');
    }
}

?>
