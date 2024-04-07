<?php
    require_once("config.php");
    // require_once("vendor/autoload.php"); // Include the JWT library
    // use Firebase\JWT\JWT;

    Class Student {
        private $dbcon;
        private $state;
        private $errmsg;

        public function __construct(){
            $db = new Database();
            if($db->getState()){
                $this->dbcon = $db->getDb();
                $this->state = $db->getState();
                $this->errmsg = $db->getErrMsg();
            }
            else{
                $this->state = $db->getState();
                $this->errmsg = $db->getErrMsg();
            }
        }

        // R E G I S T E R
        public function registerUser($fname, $lname, $username, $degree, $password, $email, $user_type, $image, $status) {
            try {
                // Input Validation
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    return json_encode(["message" => "Invalid Email Address"]);
                }
        
                if (!ctype_alnum($username)) {
                    return json_encode(["message" => "Username should only contain alphanumeric characters"]);
                }
        
                // Check if the username or email is already taken
                $duplicateCheck = $this->dbcon->prepare("SELECT * FROM students WHERE username = :username OR email = :email");
                $duplicateCheck->bindParam(':username', $username);
                $duplicateCheck->bindParam(':email', $email);
                $duplicateCheck->execute();
        
                if ($duplicateCheck->rowCount() > 0) {
                    // return json_encode(["message" => "Username or Email Has Already Been Taken"]);
                    return ["error" => "Username or Email Has Already Been Taken"];
                } else {
                    // Generate a random salt
                    $salt = bin2hex(random_bytes(22));
        
                    // Combine the user's password with the salt
                    $passwordWithSalt = $password . $salt;
        
                    // Hash the password with the salt using bcrypt
                    $hashedPassword = password_hash($passwordWithSalt, PASSWORD_BCRYPT);
        
                    // Store the hash, salt, and current timestamp in the database
                    $query = "INSERT INTO students (fname, lname, username, email, degree, password_hash, salt, user_type, imagePath, status) 
                              VALUES (:fname, :lname, :username, :email, :degree, :password, :salt, :user_type, :imagePath, :status)";
                    $stmt = $this->dbcon->prepare($query);
                    $stmt->bindParam(':fname', $fname);
                    $stmt->bindParam(':lname', $lname);
                    $stmt->bindParam(':username', $username);
                    $stmt->bindParam(':email', $email);
                    $stmt->bindParam(':degree', $degree);
                    $stmt->bindParam(':password', $hashedPassword);
                    $stmt->bindParam(':salt', $salt);
                    $stmt->bindParam(':user_type', $user_type);
                    $stmt->bindParam(':imagePath', $image);
                    $stmt->bindParam(':status', $status);
        
                    if ($stmt->execute()) {
                        return json_encode(["message" => "Registration Successful"]);
                    } else {
                        // Log the error to a secure log file
                        error_log("Database Error: Registration Failed", 0);
                        return json_encode(["message" => "Registration Failed. Please try again later."]);
                    }
                }
            } catch (PDOException $e) {
                // Log the error to a secure log file
                error_log("Database Error: " . $e->getMessage(), 0);
                return json_encode(["message" => "Registration Failed. Please try again later."]);
            }
        }


        public function userChangePassword($user_id, $oldPassword, $newPassword) {
            try {
                // Retrieve the user's current password hash and salt from the database
                $query = "SELECT password_hash, salt FROM students WHERE user_id = :user_id";
                $stmt = $this->dbcon->prepare($query);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->execute();
        
                if ($stmt->rowCount() > 0) {
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    $currentPasswordHash = $row['password_hash'];
                    $salt = $row['salt'];
        
                    // Verify the old password
                    $oldPasswordWithSalt = $oldPassword . $salt;
                    if (password_verify($oldPasswordWithSalt, $currentPasswordHash)) {
                        // Generate a new salt for the new password
                        $newSalt = bin2hex(random_bytes(22));
        
                        // Combine the new password with the new salt
                        $newPasswordWithSalt = $newPassword . $newSalt;
        
                        // Hash the new password with the new salt using bcrypt
                        $newHashedPassword = password_hash($newPasswordWithSalt, PASSWORD_BCRYPT);
        
                        // Update the password in the database
                        $updateQuery = "UPDATE students SET password_hash = :newPassword, salt = :newSalt WHERE user_id = :user_id";
                        $updateStmt = $this->dbcon->prepare($updateQuery);
                        $updateStmt->bindParam(':newPassword', $newHashedPassword);
                        $updateStmt->bindParam(':newSalt', $newSalt);
                        $updateStmt->bindParam(':user_id', $user_id);
                        $updateStmt->execute();

                        return true; // Return true on success
                    } else {
                        return "Old password verification failed.";
                    }
                } else {
                    return "User not found.";
                }
            } catch (PDOException $e) {
                return "PDO Exception: " . $e->getMessage();
            }
        }
                          
        public function userChangeName($user_id, $newFname, $newLname) {
            try {
                $updateQuery = "UPDATE students SET fname = :newFname, lname = :newLname WHERE user_id = :user_id";
                $updateStmt = $this->dbcon->prepare($updateQuery);
                $updateStmt->bindParam(':newFname', $newFname);
                $updateStmt->bindParam(':newLname', $newLname);
                $updateStmt->bindParam(':user_id', $user_id);
                $updateStmt->execute();
        
                return true; // Return true on success
            } catch (PDOException $e) {
                return "PDO Exception: " . $e->getMessage();
            }
        }
        
         // L O G I N
      public function loginUser1($username, $password) {
            try {
                $sql = "SELECT * FROM students WHERE username = :username";
                $stmt = $this->dbcon->prepare($sql);
                $stmt->bindParam(':username', $username);
                $stmt->execute();
        
                if ($stmt->rowCount() > 0) {
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    $storedSalt = $row['salt'];
                    $storedHash = $row['password_hash'];
                    $userType = $row['user_type'];
                    $status = $row['status'];
                    $email = $row['email']; // Fetch the status from the database
        
                    $passwordToCheck = $password . $storedSalt;
                    $isPasswordCorrect = password_verify($passwordToCheck, $storedHash);
        
                    if ($isPasswordCorrect) {
                        // Password is correct
                        if ($status === 'ACTIVE' || $status === 'RESTRICT') {
                            // Account is active
                            $userDetails = [
                                'user_id' => $row["user_id"],
                                'fname' => $row["fname"],
                                'lname' => $row["lname"],
                                'username' => $row["username"],
                                'email' => $email, // Add the email field
                                'degree' => $row["degree"],
                                'name' => $row["fname"] . " " . $row["lname"],
                                'user_type' => $userType,
                                'imagePath' => $row['imagePath'],
                                'status' => $status,
                            ];
        
                            return [
                                'success' => true,
                                'userDetails' => $userDetails,
                            ];
                        } else {
                            // Account is deactivated
                            return ["error" => "Your account has been deactivated. Please contact the administrator."];
                        }
                    } else {
                        return ["error" => "Wrong Password"];
                    }
                } else {
                    return ["error" => "User Not Registered"];
                }
            } catch (PDOException $e) {
                return ["error" => "Database Error: " . $e->getMessage()];
            }
        }
        

        //  U S E R    F U N C T I O N

    public function activateUser($user) {
        try {
            // Check if $id is set and is a non-empty value
            if (!isset($user) || empty($user)) {
                return 0; // Return 0 indicating failure
            }
    
            $sql = "UPDATE students SET status = 'ACTIVE' WHERE user_id = :user_id LIMIT 1";
            $stmt = $this->dbcon->prepare($sql);
            $stmt->bindParam(':user_id', $user, PDO::PARAM_INT);
    
            if ($stmt->execute()) {
                // Check if any rows were affected
                if ($stmt->rowCount() > 0) {
                    return 1; // Return 1 indicating success
                } else {
                    return 0; // Return 0 if no rows were affected
                }
            } else {
                // Log the error to a secure log file
                error_log("Database Error: " . implode(" - ", $stmt->errorInfo()), 0);
                return 0; // Return 0 indicating failure
            }
        } catch (PDOException $e) {
            // Log the error to a secure log file
            error_log("PDOException: " . $e->getMessage(), 0);
            return 0; // Return 0 indicating failure
        }
    }

    public function deactivateUser($user) {
        try {
            // Check if $id is set and is a non-empty value
            if (!isset($user) || empty($user)) {
                return 0; // Return 0 indicating failure
            }
    
            $sql = "UPDATE students SET status = 'DEACTIVATED' WHERE user_id = :user_id LIMIT 1";
            $stmt = $this->dbcon->prepare($sql);
            $stmt->bindParam(':user_id', $user, PDO::PARAM_INT);
    
            if ($stmt->execute()) {
                // Check if any rows were affected
                if ($stmt->rowCount() > 0) {
                    return 1; // Return 1 indicating success
                } else {
                    return 0; // Return 0 if no rows were affected
                }
            } else {
                // Log the error to a secure log file
                error_log("Database Error: " . implode(" - ", $stmt->errorInfo()), 0);
                return 0; // Return 0 indicating failure
            }
        } catch (PDOException $e) {
            // Log the error to a secure log file
            error_log("PDOException: " . $e->getMessage(), 0);
            return 0; // Return 0 indicating failure
        }
    }

    public function partiallyDisableUser($user) {
        try {
            // Check if $id is set and is a non-empty value
            if (!isset($user) || empty($user)) {
                return 0; // Return 0 indicating failure
            }
    
            $sql = "UPDATE students SET status = 'RESTRICT' WHERE user_id = :user_id LIMIT 1";
            $stmt = $this->dbcon->prepare($sql);
            $stmt->bindParam(':user_id', $user, PDO::PARAM_INT);
    
            if ($stmt->execute()) {
                // Check if any rows were affected
                if ($stmt->rowCount() > 0) {
                    return 1; // Return 1 indicating success
                } else {
                    return 0; // Return 0 if no rows were affected
                }
            } else {
                // Log the error to a secure log file
                error_log("Database Error: " . implode(" - ", $stmt->errorInfo()), 0);
                return 0; // Return 0 indicating failure
            }
        } catch (PDOException $e) {
            // Log the error to a secure log file
            error_log("PDOException: " . $e->getMessage(), 0);
            return 0; // Return 0 indicating failure
        }
    }






        // I N S E R T  F U N C T I O N
    public function insertItem($item){
            $sql = "INSERT INTO items (user_id, item, imagePath, location, date_lost, description, status, date_found) 
            VALUES (:user_id, :item, :imagePath, :location, :date_lost, :description, :status, :date_found)";
            $item['status'] = "Pending";

            try{
                $stmt = $this->dbcon->prepare($sql);
                $stmt->bindParam(':user_id', $item['user_id']);
                $stmt->bindParam(':item', $item['item']);
                $stmt->bindParam(':imagePath', $item['imagePath']);
                $stmt->bindParam(':location', $item['location']);
                $stmt->bindParam(':date_lost', $item['date_lost']);
                $stmt->bindParam(':description', $item['description']);
                $stmt->bindParam(':status', $item['status']);
                $stmt->bindParam(':date_found', $item['date_found']);
                $stmt->execute();
             return true; // Return true on success
             // Return a JSON response
            // echo json_encode(["success" => true]);
            } catch (PDOException $e) {
                return "PDO Exception: " . $e->getMessage();
                // Return a JSON response with the error message
               // echo json_encode(["success" => false, "error" => "PDO Exception: " . $e->getMessage()]);
        }
        }

    public function insertFeedback($feedback) {
            $feedback['status'] = "Open";
            $feedback['time']= date("Y-m-d H:i:s");

            
            $sql = "INSERT INTO feedback (email, time, feedback_type, subject, description, rating, status) 
            VALUES (:email, :time, :feedback_type, :subject, :description, :rating, :status)";

            try {
                $stmt = $this->dbcon->prepare($sql);
              
                $stmt->bindParam(':email', $feedback['email']);
                $stmt->bindParam(':time', $feedback['time']);
                $stmt->bindParam(':feedback_type', $feedback['feedback_type']);
                $stmt->bindParam(':subject', $feedback['subject']);
                $stmt->bindParam(':description', $feedback['description']);
                $stmt->bindParam(':rating', $feedback['rating']);
                $stmt->bindParam(':status', $feedback['status']);
                $stmt->execute();
        
                return true; // Return true on success
            } catch (PDOException $e) {
                return "PDO Exception: " . $e->getMessage();
            }
        }
    
    public function AClaimRequest($item_id, $claimer_id, $claim_message) {
            try {
                // Check if item_id exists in items table
                $checkItemSQL = "SELECT COUNT(*) FROM items WHERE id = :item_id";
                $itemStmt = $this->dbcon->prepare($checkItemSQL);
                $itemStmt->bindParam(':item_id', $item_id);
                $itemStmt->execute();
        
                if ($itemStmt->fetchColumn() == 0) {
                    return "Item with ID $item_id does not exist.";
                }
        
                // Check if claimer_id exists in students table
                $checkClaimerSQL = "SELECT COUNT(*) FROM students WHERE user_id = :claimer_id";
                $claimerStmt = $this->dbcon->prepare($checkClaimerSQL);
                $claimerStmt->bindParam(':claimer_id', $claimer_id);
                $claimerStmt->execute();
        
                if ($claimerStmt->fetchColumn() == 0) {
                    return "Claimer with ID $claimer_id does not exist.";
                }
        
                // Check if the user has already claimed the item within the last 10 days
                $checkDuplicateSQL = "SELECT * FROM claims WHERE id = :item_id AND claimer_id = :claimer_id AND TIMESTAMPDIFF(DAY, timestamp, NOW()) <= 10";
                $duplicateStmt = $this->dbcon->prepare($checkDuplicateSQL);
                $duplicateStmt->bindParam(':item_id', $item_id);
                $duplicateStmt->bindParam(':claimer_id', $claimer_id);
                $duplicateStmt->execute();
        
                if ($duplicateStmt->rowCount() > 0) {
                    return "Duplicate claim. Please wait for 10 days to claim your item.";
                }
        
                // If not a duplicate, proceed with the claim submission
                $sql = "INSERT INTO claims (id, claimer_id, claim_message, status) 
                        VALUES (:id, :claimer_id, :claim_message, 'Pending')";
                $stmt = $this->dbcon->prepare($sql);
                $stmt->bindParam(':id', $item_id);
                $stmt->bindParam(':claimer_id', $claimer_id);
                $stmt->bindParam(':claim_message', $claim_message);
                $stmt->execute();
        
                // Notify admin and send a message
                // ... (your existing admin notification logic)
        
                return true; // Return true on success
            } catch (PDOException $e) {
                return "PDO Exception: " . $e->getMessage();
            }
    }
     
    public function sendAdminMessage($data) {
        $sql = "CALL insert_Messages(:sender_id, :recipient_id, :claim_id, :message)";
        $stmt = $this->dbcon->prepare($sql);
        $stmt->bindParam(':sender_id', $data['sender_id']);
          $stmt->bindParam(':recipient_id', $data['recipient_id']);
        $stmt->bindParam(':claim_id', $data['claim_id']);
        $stmt->bindParam(':message', $data['message']);
    
        try {
            $stmt->execute();
            return true; // Return true to indicate success
        } catch (PDOException $e) {
            return "PDO Exception: " . $e->getMessage();
        }

    }

    public function processNewClaim($claimer_id, $item_id, $claim_message) {
        try {
            // Check if the user already claimed this item
            if ($this->checkExistingClaim($claimer_id, $item_id)) {
                return false; // User already claimed this item
            }
    
            // Perform database insert for the new claim
            $sql = "INSERT INTO claims (user_id, id, claim_message) VALUES (:claimer_id, :id, :claim_message)";
            $stmt = $this->dbcon->prepare($sql);
            $stmt->bindParam(':claimer_id', $claimer_id, PDO::PARAM_INT);
            $stmt->bindParam(':id', $item_id, PDO::PARAM_INT);
            $stmt->bindParam(':claim_message', $claim_message, PDO::PARAM_STR);
            $success = $stmt->execute();
            $stmt->closeCursor();
    
            return $success;
        } catch (PDOException $e) {
            // Handle database errors
            // Log or report the error, and return false or handle as needed
            error_log("Database Error: " . $e->getMessage());
            return false;
        }
    }
    
    // U P D A T E       F U N C T I O N

    public function updateUser($fname, $lname, $username, $email, $degree, $user_type, $user) {
        try {
            $sql = "UPDATE students SET fname = :fname, lname = :lname, username = :username, email = :email, 
                    degree = :degree, user_type = :user_type WHERE user_id = :user_id LIMIT 1";
            $stmt = $this->dbcon->prepare($sql);
            $stmt->bindParam(':fname', $fname);
            $stmt->bindParam(':lname', $lname);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':degree', $degree);
            $stmt->bindParam(':user_type', $user_type);
            $stmt->bindParam(':user_id', $user);
            $stmt->execute();
            return 1;
        } catch (PDOException $e) {
            return 0;
        }
    }
    public function updateClaimItem($id) {
        try {
            // Check if $id is set and is a non-empty value
            if (!isset($id) || empty($id)) {
                return 0; // Return 0 indicating failure
            }
    
            $sql = "UPDATE items SET claim_status = 'CLAIMED' WHERE id = :id LIMIT 1";
            $stmt = $this->dbcon->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    
            if ($stmt->execute()) {
                // Check if any rows were affected
                if ($stmt->rowCount() > 0) {
                    return 1; // Return 1 indicating success
                } else {
                    return 0; // Return 0 if no rows were affected
                }
            } else {
                // Log the error to a secure log file
                error_log("Database Error: " . implode(" - ", $stmt->errorInfo()), 0);
                return 0; // Return 0 indicating failure
            }
        } catch (PDOException $e) {
            // Log the error to a secure log file
            error_log("PDOException: " . $e->getMessage(), 0);
            return 0; // Return 0 indicating failure
        }
    }

    public function updateUnclaimItem($id) {
        try {
            // Check if $id is set and is a non-empty value
            if (!isset($id) || empty($id)) {
                return 0; // Return 0 indicating failure
            }
    
            $sql = "UPDATE items SET claim_status = 'UNCLAIMED' WHERE id = :id LIMIT 1";
            $stmt = $this->dbcon->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    
            if ($stmt->execute()) {
                // Check if any rows were affected
                if ($stmt->rowCount() > 0) {
                    return 1; // Return 1 indicating success
                } else {
                    return 0; // Return 0 if no rows were affected
                }
            } else {
                // Log the error to a secure log file
                error_log("Database Error: " . implode(" - ", $stmt->errorInfo()), 0);
                return 0; // Return 0 indicating failure
            }
        } catch (PDOException $e) {
            // Log the error to a secure log file
            error_log("PDOException: " . $e->getMessage(), 0);
            return 0; // Return 0 indicating failure
        }
    }

    public function updateClaimStatus($itemId, $status) {
        try {
            $sql = "UPDATE items SET claim_status = :claim_status WHERE id = :id";
            $stmt = $this->dbcon->prepare($sql);
            $stmt->bindParam(':claim_status', $status);
            $stmt->bindParam(':id', $itemId);
            $stmt->execute();
    
            return true;
        } catch (PDOException $e) {
            return "PDO Exception: " . $e->getMessage();
        }
    }

    public function updateItemS($item, $location, $date_lost, $status, $dateFound, $id) {
        try {
            $sql = "UPDATE items SET item = :item, location = :location, date_lost = :date_lost, status = :status, date_found = :date_found 
                    WHERE id = :id LIMIT 1";
            $stmt = $this->dbcon->prepare($sql);
            $stmt->bindParam(':item', $item);
            $stmt->bindParam(':location', $location);
            $stmt->bindParam(':date_lost', $date_lost);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':date_found', $dateFound);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            return 1;
        } catch (PDOException $e) {
            return 0;
        }
    }

    public function updateItem($id) {
        try {
            // Check if $id is set and is a non-empty value
            if (!isset($id) || empty($id)) {
                return 0; // Return 0 indicating failure
            }
    
            $sql = "UPDATE items SET status = 'approved' WHERE id = :id LIMIT 1";
            $stmt = $this->dbcon->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    
            if ($stmt->execute()) {
                // Check if any rows were affected
                if ($stmt->rowCount() > 0) {
                    return 1; // Return 1 indicating success
                } else {
                    return 0; // Return 0 if no rows were affected
                }
            } else {
                // Log the error to a secure log file
                error_log("Database Error: " . implode(" - ", $stmt->errorInfo()), 0);
                return 0; // Return 0 indicating failure
            }
        } catch (PDOException $e) {
            // Log the error to a secure log file
            error_log("PDOException: " . $e->getMessage(), 0);
            return 0; // Return 0 indicating failure
        }
    }

    public function declineItem($id){
        try {
            // Check if $id is set and is a non-empty value
            if (!isset($id) || empty($id)) {
                return 0; // Return 0 indicating failure
            }
    
            $sql = "UPDATE items SET status = 'declined' WHERE id = :id LIMIT 1";
            $stmt = $this->dbcon->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    
            if ($stmt->execute()) {
                // Check if any rows were affected
                if ($stmt->rowCount() > 0) {
                    return 1; // Return 1 indicating success
                } else {
                    return 0; // Return 0 if no rows were affected
                }
            } else {
                // Log the error to a secure log file
                error_log("Database Error: " . implode(" - ", $stmt->errorInfo()), 0);
                return 0; // Return 0 indicating failure
            }
        } catch (PDOException $e) {
            // Log the error to a secure log file
            error_log("PDOException: " . $e->getMessage(), 0);
            return 0; // Return 0 indicating failure
        }

    }

 

    
    
   // D I S P L A Y     F U N C T I O N 

    public function displayMyItems() {
            $sql = "SELECT id, item, imagePath, location, date_lost, description, status FROM items ORDER BY id";
            $stmt = $this->dbcon->prepare($sql);
            $stmt->execute();
        
            if ($stmt->rowCount() > 0) {
                $rows = "";
                //  $data = [];
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $rows .= "<tr>";
                    $rows .= "<td>" . $row['id'] . "</td>";
                    $rows .= "<td>" . $row['item'] . "</td>";
                    $rows .= "<td><img src='uploads/" . $row['imagePath'] . "' width='200' alt='" . $row['imagePath'] . "'></td>";
                    $rows .= "<td>" . $row['location'] . "</td>";
                    $rows .= "<td>" . $row['date_lost'] . "</td>";
                    $rows .= "<td>" . $row['description'] . "</td>";
                    $rows .= "<td>" . $row['status'] . "</td>";
                  
        
                    $btnupd = "<a href='handler.php?id=" . $row['id'] . "' class='btn-upd'>Edit</a>";
                    $btndel = "<a href='.php?id=" . $row['id'] . "' class='btn-upd'>Delete</a>";
            
                     $rows .= "<td>" . $btnupd . $btndel.  "</td>";
                 
                    $rows .= "</tr>";
                    // $data[] = $row;
                }
            return $rows;
            //  return json_encode($data);
            }
    
    }
        
    public function displayClaimedItem() {
            $sql = "SELECT id, item, imagePath, location, date_lost, description, status, date_found
            FROM items
            WHERE status IN ('CLAIMED', 'UNCLAIMED');";
            $stmt = $this->dbcon->prepare($sql);
            $stmt->execute();
            
            $data = []; // Initialize an array to hold the data
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $data[] = $row;
            }
            
            return json_encode($data); // Encode the data as JSON before returning
    }

    public function displayStudent(){
            $sql = "call getUser()";

            $stmt = $this->dbcon->prepare($sql);
                 try{
                     $stmt->execute();
                     if($stmt){
                       $rows = array();
                       while($rw = $stmt->fetch(PDO::FETCH_ASSOC)){
                           $rows[] = $rw;
                       }
                       return $rows;
                     }else{  
                         return array();
                     }
                 }catch(PDOException $ex){
                     $this->state = false;
                     return $ex->getMessage();
                 }
    }

    public function displayFeedback(){
            $sql = "call getFeedback()";

            $stmt = $this->dbcon->prepare($sql);
                 try{
                     $stmt->execute();
                     if($stmt){
                       $rows = array();
                       while($rw = $stmt->fetch(PDO::FETCH_ASSOC)){
                           $rows[] = $rw;
                       }
                       return $rows;
                     }else{  
                         return array();
                     }
                 }catch(PDOException $ex){
                     $this->state = false;
                     return $ex->getMessage();
                 }
    }

    public function displayPendingItem() {
            $sql = "SELECT id, item, imagePath, location, date_lost, description, status FROM items where status ='pending'";
            $stmt = $this->dbcon->prepare($sql);
            $stmt->execute();
        
            $items = [];
        
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $items[] = $row;
            }
        
            return $items;
    }   

    public function displayApprovedItem() {
            $sql = "SELECT id, item, imagePath, location, date_lost, description FROM items where status ='approved'
                    ORDER BY id DESC";
            $stmt = $this->dbcon->prepare($sql);
            $stmt->execute();
        
            $items = [];
        
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $items[] = $row;
            }
        
            return $items;
    }


  // G E T      F U N C T I O N

  public function getAllStudents() {
    try {
        $query = "SELECT * FROM students";
        $stmt = $this->dbcon->query($query);

        // Fetch all rows as an associative array
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Check if there are any rows
        if ($result) {
            return $result;
        } else {
            return "No students found";
        }
    } catch (PDOException $e) {
        // Log the error to a secure log file
        error_log("Database Error: " . $e->getMessage(), 0);
        return "An error occurred while fetching students. Please try again later.";
    }
}

    public function getPendingClaims() {
    try {
        $sql = "SELECT claim_id, claimer_id, id, claim_message, timestamp FROM claims WHERE status = 'Pending'";
        $stmt = $this->dbcon->prepare($sql);
        $stmt->execute();

        $pendingClaims = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $pendingClaims[] = $row;
        }

        return $pendingClaims;
    } catch (PDOException $e) {
        return "PDO Exception: " . $e->getMessage();
    }
}

    public function geItemsById($id) {
    $sql = "SELECT * FROM items WHERE id = :id";
    try {
        $stmt = $this->dbcon->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        if ($stmt->rowCount() == 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row;
        } else {
            return false;
        }
    } catch(PDOException $e) {
        return false;
    }
}

    public function getClaimerID($claim_id) {
            try {
                $sql = "SELECT claimer_id FROM claims WHERE claim_id = :claim_id";
                $stmt = $this->dbcon->prepare($sql);
                $stmt->bindParam(':claim_id', $claim_id);
                $stmt->execute();
        
                if ($stmt->rowCount() > 0) {
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    return $row['claimer_id'];
                } else {
                    return null; // Claim ID not found
                }
            } catch (PDOException $e) {
                return null; // Handle the exception as needed
            }
    }
    
    public function getJoinedDatas() {
        $sql = "CALL get_joined()";
        $stmt = $this->dbcon->prepare($sql);

         try {
             $stmt->execute();

        if ($stmt) {
            $rows = array();
            while ($rw = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $rows[] = $rw;
            }
            return $rows;
          } else {
            return array();
        }
          } catch (PDOException $ex) {
               $this->state = false;
           return $ex->getMessage();
      }
    }

    public function getClaimerDetails($claimer_id) {
    try {
        $sql = "SELECT fname, lname FROM students WHERE user_id = :claimer_id";
        $stmt = $this->dbcon->prepare($sql);
        $stmt->bindParam(':claimer_id', $claimer_id, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row;
        } else {
            return null; // Claimer details not found
        }
    } catch (PDOException $e) {
        return null; // Handle the exception as needed
    }
    }

    public function getTopReportingUsers($limit = 5) {
         try {
        $sql = "SELECT students.username, students.fname, students.lname, students.degree, students.imagePath,
        COUNT(*) AS report_count
        FROM items
        JOIN students ON items.user_id = students.user_id
        WHERE items.status IN ('approved', 'CLAIMED', 'UNCLAIMED')  -- Add this condition
        GROUP BY items.user_id
        ORDER BY report_count DESC, students.lname ASC
        LIMIT :limit";


        $stmt = $this->dbcon->prepare($sql);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    } catch (PDOException $e) {
        return ['error' => 'PDO Exception: ' . $e->getMessage()];
    }
    }

    public function getAdminMessages($claimer_id) {
        try {
        $sql = "SELECT message_id, message FROM messages WHERE claimer_id = :claimer_id";
        $stmt = $this->dbcon->prepare($sql);

        $stmt->bindParam(':claimer_id', $claimer_id);
        $stmt->execute();

        $messages = [];

        if ($stmt->rowCount() > 0) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $message = "{$row['message_id']}: {$row['message']}";
                $messages[] = $message;
            }
        }

        return $messages;
    } catch (PDOException $e) {
        return ["PDO Exception: " . $e->getMessage()];
    }
}

    public function getUserDetails($user_id) {
        try {
        $sql = "SELECT user_id, fname, lname, username, email, degree, imagePath FROM students WHERE user_id = :user_id;";
        $stmt = $this->dbcon->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row;
        } else {
            return null; // User details not found
        }
        } catch (PDOException $e) {
        return null; // Handle the exception as needed
        }
    }

    public function getReportedItems($userId) {
        try {
            $sql = "SELECT * FROM items WHERE user_id = :user_id";
            $stmt = $this->dbcon->prepare($sql);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();

            $reportedItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

          return ['success' => true, 'reportedItems' => $reportedItems];
     } catch (PDOException $e) {
         return ['error' => 'Database Error: ' . $e->getMessage()];
    }
    }


    // C H E C K       F U N C T I O N
    public function checkClaimStatus($user_id, $item_id) {
        try {
        $sql = "SELECT COUNT(*) as count FROM claims WHERE claimer_id = :claimer_id 
        AND id = :id AND timestamp >= DATE_SUB(NOW(), INTERVAL 10 DAY)";
        $stmt = $this->dbcon->prepare($sql);

        $stmt->bindParam(':claimer_id', $user_id);
        $stmt->bindParam(':id', $item_id);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $hasClaimed = $result['count'] > 0;

        return [
            'success' => !$hasClaimed,
            'message' => $hasClaimed ? 'You have already claimed this item. Please wait for 10 days.' : '',
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'error' => 'PDO Exception: ' . $e->getMessage(),
        ];
    }
    }

    public function checkExistingClaim($claimer_id, $item_id) {
        try {
            // Adjust this query based on your actual table and column names
            $sql = "SELECT COUNT(*) FROM claims WHERE user_id = :claimer_id AND id = :id";
            $stmt = $this->dbcon->prepare($sql);
            $stmt->bindParam(':claimer_id', $claimer_id, PDO::PARAM_INT);
            $stmt->bindParam(':id', $item_id, PDO::PARAM_INT);
            $stmt->execute();
            $count = $stmt->fetchColumn();
            $stmt->closeCursor();
    
            return $count > 0;
        } catch (PDOException $e) {
            // Handle database errors
            // Log or report the error, and return false or handle as needed
            error_log("Database Error: " . $e->getMessage());
            return false;
        }
    }
}



        ?>