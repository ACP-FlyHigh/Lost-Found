<?php
require_once("object.php");

    header("Access-Control-Allow-Origin: *");
    //  header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Max-Age: 3600");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
    header("Access-Control-Allow-Headers: Content-Type");
    header('Content-Type: application/json');
    header("Access-Control-Allow-Origin: http://localhost:5173");
    header("Access-Control-Allow-Headers: Authorization, Content-Type");
    

     $method = isset($_POST['method']) ? $_POST['method'] : exit();
       
     if(function_exists($method)){
         call_user_func($method);
     }else{
         exit();
     }

     function register() {
        if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["image"]) && $_FILES["image"]["error"] === UPLOAD_ERR_OK) {
            $fname = isset($_POST['fname']) ? $_POST['fname'] : '';
            $lname = isset($_POST['lname']) ? $_POST['lname'] : '';
            $username = isset($_POST['username']) ? $_POST['username'] : '';
            $email = isset($_POST['email']) ? $_POST['email'] : '';
            $degree = isset($_POST['degree']) ? $_POST['degree'] : '';
            $user_type = isset($_POST['user_type']) ? $_POST['user_type'] : '';
            $password = isset($_POST['password']) ? $_POST['password'] : '';
            $status = isset($_POST['status']) ? $_POST['status'] : '';

    
             $response = ["message" => "", "result" => false];
          
                $fileName = $_FILES['image']['name'];
                $fileSize = $_FILES['image']['size'];
                $TmpName = $_FILES['image']['tmp_name'];
                $validMimeTypes = ['image/jpeg', 'image/jpg', 'image/png'];
    
                
            // Check file type using MIME type
            if (!in_array($_FILES["image"]["type"], $validMimeTypes)) {
                $response["message"] = "Invalid Image Type";
            } elseif ($fileSize > 1000000) {
                $response["message"] = "File is too big!";
            } else {
                $newImageName = uniqid() . '.' . pathinfo($fileName, PATHINFO_EXTENSION);
    
                // Move uploaded file to the 'uploads' directory
                if (move_uploaded_file($TmpName, 'profiles/' . $newImageName)) {
                    $response["imagePath"] = 'profiles/' . $newImageName;
        
                    $report = new Student();
        
                    $result = $report->registerUser(
                        $fname,
                        $lname,
                        $username,
                        $degree,
                        $password,
                        $email,
                        $user_type,
                        $response["imagePath"],
                        $status
                    );
        
                    // Check the result of the registerUser function
                    header('Content-Type: application/json');

        echo json_encode($result);
        exit; 
                } else {
                    $response["message"] = "Error moving file";
                }
            } 
        
            // Return the result to the client (Vue.js)
            header("Content-Type: application/json");
            echo json_encode($response);
            exit;
        }
    }

    function changePassword() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405); // Method Not Allowed
            echo json_encode(["error" => "Invalid request method"]);
            return;
        }
    
        // Check if the required parameters are present in the POST request
        if (!isset($_POST['user_id'], $_POST['oldPassword'], $_POST['newPassword'])) {
            http_response_code(400); // Bad Request
            echo json_encode(["error" => "Missing parameters"]);
            return;
        }
    
        $user_id = $_POST['user_id'];
        $oldPassword = $_POST['oldPassword'];
        $newPassword = $_POST['newPassword'];
    
        // Assuming you have a Student class and userChangePassword method
        $student = new Student();
        $result = $student->userChangePassword($user_id, $oldPassword, $newPassword);
    
        header("Content-Type: application/json");
    
        if ($result === "success") {
            echo json_encode(["error" => "Failed to change password"]);
        } else {
            header("Content-Type: application/json");
            echo json_encode(["message" => "Password changed successfully"]);
            
        }
    }

    function changeName() {
        $user_id = $_POST['user_id'];
        $newFname = $_POST['newFname'];
        $newLname = $_POST['newLname'];

        $student = new Student();
        $result = $student->userChangeName($user_id, $newFname, $newLname);

        
        header('Content-Type: application/json');
        if ($result === true) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => $result]);
        }
        exit();
    }
    

    function upload() {

        if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["image"]) && $_FILES["image"]["error"] === UPLOAD_ERR_OK) {
        $Item = isset($_POST['Item']) ? $_POST['Item'] : '';
        $location = isset($_POST['location']) ? $_POST['location'] : '';
        $Ldate = isset($_POST['date_lost']) ? $_POST['date_lost'] : '';
        $description = isset($_POST['description']) ? $_POST['description'] : '';
        $status = isset($_POST['status']) ? $_POST['status'] : '';
        $user_id = isset($_POST['user_id']) ? $_POST['user_id'] : null;

         $response = ["message" => "", "result" => false];
      
            $fileName = $_FILES['image']['name'];
            $fileSize = $_FILES['image']['size'];
            $TmpName = $_FILES['image']['tmp_name'];
            $validMimeTypes = ['image/jpeg', 'image/jpg', 'image/png'];

            
        // Check file type using MIME type
        if (!in_array($_FILES["image"]["type"], $validMimeTypes)) {
            $response["message"] = "Invalid Image Type";
        } elseif ($fileSize > 1000000) {
            $response["message"] = "File is too big!";
        } else {
            $newImageName = uniqid() . '.' . pathinfo($fileName, PATHINFO_EXTENSION);

            // Move uploaded file to the 'uploads' directory
            if (move_uploaded_file($TmpName, 'uploads/' . $newImageName)) {
                $response["imagePath"] = 'uploads/' . $newImageName;

                $report = new Student();

                // Corrected function call to pass parameters as an array
                $result = $report->insertItem([
                    "user_id" => $user_id,
                    "item" => $Item,
                    "imagePath" => $response["imagePath"],
                    "location" => $location,
                    "date_lost" => $Ldate,
                    "description" => $description,
                    "status" => $status,
                    "date_found" => "",  // Assuming date_found is not set in the form
                ]);

                // Check the result of the insertItem function
                if ($result === true) {
                    $response = ["message" => "Item inserted successfully!", "result" => true, "imagePath" => "uploads/image.jpg"];
                    $response["result"] = true;
                } else {
                    $response["message"] = "Error inserting item: $result";
                }
            } else {
                $response["message"] = "Error moving file";
            }
        }
    } else {
        $response["message"] = "Error uploading file";
          // Return the result to the client (Vue.js)
             header("Content-Type: application/json");
             echo json_encode($response);
            exit;
        }
    }

    function login1() {
        $username = $_POST["username"];
        $password = $_POST["password"];

        $student = new Student();
         $result = $student->loginUser1($username, $password);

        header('Content-Type: application/json');

        if (is_array($result) && isset($result['success']) && $result['success']) {
            echo json_encode(['success' => true, 'userDetails' => $result]);
        } else {
            echo json_encode(['success' => false, 'error' => $result]);
        }
    }
    
    function handleFeedbackSubmission() {
            // Ensure the required fields are set
        $requiredFields = ['feedback_type', 'email', 'subject', 'description', 'rating'];
        foreach ($requiredFields as $field) {
        if (!isset($_POST[$field])) {
            echo json_encode(["success" => false, "error" => "Missing required field: $field"]);
            exit;
        }
    }

    // Extract feedback data from POST
    $feedbackType = $_POST["feedback_type"];
    $email = $_POST["email"];
    $subject = $_POST["subject"];
    $description = $_POST["description"];
    $rating = $_POST["rating"];

    // Set timestamp to the current date and time
    $timestamp = date("Y-m-d H:i:s");

    // Set status to 'Open' by default
    $status = "Open";

    // Construct feedback data array
    $array = [
        "email" => $email,
        "feedback_type" => $feedbackType,
        "subject" => $subject,
        "description" => $description,
        "rating" => $rating,
        "timestamp" => $timestamp,
        "status" => $status,
    ];

    // Instantiate Student class and submit feedback
    $result = new Student();
    $feedbackResult = $result->insertFeedback($array);

    // Output JSON response
    if ($feedbackResult === true) {
        echo json_encode(["success" => true, "message" => "Feedback submitted successfully!"]);
    } else {
        echo json_encode(["success" => false, "error" => "Error: " . $feedbackResult]);
    }
}

    function submitClaimRequest() {
    $requiredFields = ['id', 'claimer_id', 'claim_message'];
    
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field])) {
            echo json_encode(["success" => false, "error" => "Missing required field: $field"]);
            exit;
        }
    }

    $item_id = $_POST['id'];
    $claimer_id = $_POST['claimer_id'];
    $claim_message = $_POST['claim_message'];

    $student = new Student(); // Adjust the class name accordingly
    $result = $student->AClaimRequest($item_id, $claimer_id, $claim_message);

    header('Content-Type: application/json');

    if ($result === true) {
        echo json_encode(["success" => true, "message" => "Claim request submitted successfully!"]);
    } else {
        echo json_encode(["success" => false, "error" => $result]);
    }
}


    function sendAdminMessage() {
    // Check if the required fields are set
    $requiredFields = ['claim_id', 'message'];
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field])) {
            echo json_encode(["success" => false, "error" => "Missing required field: $field"]);
            exit;
        }
    }

    try {
        // Get data from the form
        $claim_id = $_POST['claim_id'];
        $message = $_POST['message'];
        $sender_id = $_POST['admin_id']; // Assuming the sender is the admin
        $database = new Student();
        $claimer_id = $database->getClaimerID($claim_id);

        if ($claimer_id !== null) {
            // Claimer ID found, proceed with your logic
            $result = $database->sendAdminMessage([
                'sender_id' => $sender_id,
                'claimer_id' => $claimer_id,
                'claim_id' => $claim_id,
                'message' => $message
            ]);

            header('Content-Type: application/json');

            if ($result === true) {
                echo json_encode(["success" => true, "message" => "Message sent successfully!"]);
            } else {
                echo json_encode(["success" => false, "error" => "Error sending message: " . $result]);
            }
        } else {
            // Claimer ID not found or an error occurred
            echo json_encode(["success" => false, "error" => "Error getting claimer ID."]);
        }
    } catch (Exception $e) {
        // Handle exceptions and return as JSON
        echo json_encode(["success" => false, "error" => "An error occurred: " . $e->getMessage()]);
    }
}

    function updateClaimStatus() {
    if (isset($_GET['id'])) {
        $itemId = $_GET['id'];

        // Update the claim_status in the items table to 'PENDING'
        $database = new Student(); // Replace with your actual class name
        echo json_encode($database->updateClaimStatus($itemId, 'PENDING'));
       
    }
}


    function updateItem() {
    // Check if the required fields are set
    $requiredFields = ['item', 'location', 'date_lost', 'status', 'date_found', 'id'];
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field])) {
            echo json_encode(["success" => false, "error" => "Missing required field: $field"]);
            exit;
        }
    }

    try {
        // Get data from the form
        $item = $_POST['item'];
        $location = $_POST['location'];
        $date_lost = $_POST['date_lost'];
        $status = $_POST['status'];
        $dateFound = $_POST['date_found'];
        $id = $_POST['id'];

        $database = new Student(); // Adjust the class name accordingly
        $result = $database->updateItemS($item, $location, $date_lost, $status, $dateFound, $id);

        header('Content-Type: application/json');

        if ($result === 1) {
            echo json_encode(["success" => true, "message" => "Item updated successfully!"]);
        } else {
            echo json_encode(["success" => false, "error" => "Error updating item."]);
        }
    } catch (Exception $e) {
        // Handle exceptions and return as JSON
        echo json_encode(["success" => false, "error" => "An error occurred: " . $e->getMessage()]);
    }
}


    function updateUSer() {
   

    try {
        // Get data from the form
        $fname = $_POST['fname'];
        $lname = $_POST['lname'];
        $username = $_POST['username'];
        $email = $_POST['email'];
        $degree = $_POST['degree'];
        $user_type = $_POST['user_type'];
        $user = $_POST['user_id'];

        $database = new Student(); // Adjust the class name accordingly
        $result = $database->updateUser($fname, $lname, $username, $email, $degree, $user_type, $user);

        header('Content-Type: application/json');

        if ($result === 1) {
            echo json_encode(["success" => true, "message" => "User updated successfully!"]);
        } else {
            echo json_encode(["success" => false, "error" => "Error updating item."]);
        }
    } catch (Exception $e) {
        // Handle exceptions and return as JSON
        echo json_encode(["success" => false, "error" => "An error occurred: " . $e->getMessage()]);
    }
    
}
    function claims() {
    $item_id = $_POST['id'] ?? null;
    $claimer_id = $_POST['claimer_id'] ?? null;
    $claim_message = $_POST['claim_message'] ?? null;

    if (!$item_id || !$claimer_id || !$claim_message) {
        echo json_encode(['success' => false, 'error' => 'Invalid input']);
        exit;
    }

    $database = new Student();
    // Check if the user already claimed this item
    $existingClaim = $database->checkExistingClaim($claimer_id, $item_id);

    if ($existingClaim) {
        echo json_encode(['success' => false, 'error' => 'User already claimed this item']);
        exit;
    }

    // Process the new claim
    $success = $database->processNewClaim($claimer_id, $item_id, $claim_message);

    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Claim submitted successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error submitting claim']);
    }
    exit; // Make sure to exit after sending the JSON response
}


    function claimDay(){
    try {
        // Get data from the request
        $user_id = $_POST['claimer_id'];
        $item_id = $_POST['id'];

        $student = new Student();

        // Call the new method to check claim status
        $response = $student->checkClaimStatus($user_id, $item_id);

        header('Content-Type: application/json');
        
        if ($response['success']) {
            echo json_encode($response);
        } else {
            // If not successful, return an error message
            echo json_encode(["success" => false, "error" => $response['message']]);
        }
    } catch (Exception $e) {
        // Handle exceptions and return as JSON
        echo json_encode(["success" => false, "error" => "An error occurred: " . $e->getMessage()]);
    }
} 

    function getItem() {
    $student = new Student();
    echo json_encode($student->displayMyItems());
}


    function getUser() {
    $student = new Student();
    echo json_encode($student->displayStudent());
 }

    function getFeedback() {
    $student = new Student();
    echo json_encode($student->displayFeedback());
 }

    function pendingItems() {
    $student = new Student();
    echo json_encode($student->displaypendingItem());
}

    function approveItem() {
    $id = isset($_POST['id']) ? $_POST['id'] : '';
    $student = new Student();
    echo json_encode($student->updateItem($id));
}

    function declineItem() {
    $id = isset($_POST['id']) ? $_POST['id'] : '';
    $student = new Student();
    echo json_encode($student->declineItem($id));
}

    function showApprovedItem() {
    $student = new Student();
    echo json_encode($student->displayApprovedItem());
}

    function activeUser() {
    $user = isset($_POST['user_id']) ? $_POST['user_id'] : '';
    $student = new Student();
    echo json_encode($student->activateUser($user));
}

function partiallyDisableUser() {
    $user = isset($_POST['user_id']) ? $_POST['user_id'] : '';
    $student = new Student();
    echo json_encode($student->partiallyDisableUser($user));
}

    function inactiveUser() {
    $user = isset($_POST['user_id']) ? $_POST['user_id'] : '';
    $student = new Student();
    echo json_encode($student->deactivateUser($user));
}

    function getPendingClaim() {
$student = new Student();
echo json_encode($student-> getPendingClaims());
}

    function getItemsById() {
    // Check if the required fields are set
    if (!isset($_GET['id'])) {
        echo json_encode(["success" => false, "error" => "Missing required field: id"]);
        exit;
    }

    try {
        // Get item ID from the request
        $id = $_GET['id'];

        $database = new Student(); // Adjust the class name accordingly
        $item = $database->geItemsById($id);

        header('Content-Type: application/json');

        if ($item !== false) {
            echo json_encode(["success" => true, "item" => $item]);
        } else {
            echo json_encode(["success" => false, "error" => "Item not found."]);
        }
    } catch (Exception $e) {
        // Handle exceptions and return as JSON
        echo json_encode(["success" => false, "error" => "An error occurred: " . $e->getMessage()]);
    }
}


    function claimedItems() {
    $student = new Student();
    echo $student->displayClaimedItem();
}

    function getAdminMessages() {
    $claimer_id = $_POST['claimer_id'];

    $student = new Student();
    $messages = $student->getAdminMessages($claimer_id);
    
    // Return the response as JSON
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $messages]);
}


function cD() {
    $student = new Student();
    echo json_encode($student->getJoinedDatas());
}

function getClaimerDetails(){
    $claimer_id = $_GET['claimer_id'] ?? null;
    if ($claimer_id !== null) {
        $student = new Student();
        $claimerDetails = $student->getClaimerDetails($claimer_id);
        if ($claimerDetails !== null) {
            echo json_encode(['success' => true, 'data' => $claimerDetails]);
            exit;
        } else {
            echo json_encode(['success' => false, 'error' => 'Claimer details not found']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid claimer_id']);
        exit;
    }
}

    function getTopReportingUsers() {
    $limit = isset($_GET['limit']) ? $_GET['limit'] : 5;

    $lostItemReport = new Student(); // Adjust the class name accordingly
    $result = $lostItemReport->getTopReportingUsers($limit);

    header('Content-Type: application/json');

    if (isset($result['error'])) {
        echo json_encode(['success' => false, 'error' => $result['error']]);
    } else {
        echo json_encode(['success' => true, 'data' => $result]);
    }
}

    function getUserDetails() {
    $user_id = $_POST['user_id']; // Make sure to handle validation and sanitize user input

    $student = new Student();
    $result = $student->getUserDetails($user_id);
    
    // Return the response as JSON
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $result]);
}

    function getReportedItems() {
    // Check if the user is logged in
   
        $user_id = $_POST['user_id'];

        $student = new Student();
    $result = $student->getReportedItems($user_id);
    
    // Return the response as JSON
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $result]);
}

   ?>