<?php



// Get the raw JSON POST data
$jsonData = file_get_contents("php://input");

// Check if JSON data was received
if (!empty($jsonData)) {

     // Extract relevant headers
        // Decode the JSON data
        $requestData = json_decode($jsonData, true);
         // Check if the JSON was decoded successfully
    if ($requestData !== null) {
        // Extract relevant data from the JSON
        $type = $requestData['data']['type'];
        $id = $requestData['data']['id'];
        $attributes = $requestData['data']['attributes'];
        $callbackUrl = $attributes['callback_url'];
        $status = $attributes['status'];
        // Add more variables as needed



        // Sending a response (optional)
        $response = [
            'message' => 'Received and processed the callback data successfully',
            'type' => $type,
            'id' => $id,
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
    }
     else {
    // No JSON data received
    http_response_code(400);
    echo json_encode(['error' => 'No JSON data received']);
}
?>
