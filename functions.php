<?php

function emailsList() {
    // Read email data from email_data.txt
    $emailDataFile = 'email_data.txt';

    if (file_exists($emailDataFile)) {
        $emailData = json_decode(file_get_contents($emailDataFile), true);
    } else {
        $emailData = [];
    }

    // Read sent email data from emails_sent.txt
    $emailsSentFile = 'emails_sent.txt';

    if (file_exists($emailsSentFile)) {
        $emailsSentData = json_decode(file_get_contents($emailsSentFile), true);
    } else {
        $emailsSentData = [];
    }

    if(is_array($emailData) || is_array($emailsSentData)){
        // Combine and sort email data by requested date
        $combinedData = array_merge($emailData, $emailsSentData);

    usort($combinedData, function ($a, $b) {
        return strtotime($a['requested_date']) - strtotime($b['requested_date']);
    });

    // Display email list
    if (!empty($combinedData)) {
        foreach ($combinedData as $email) {
            $msg = isset($email['error_message']) ? $email['error_message'] : '';
            $sentDate = isset($email['sent_date']) ? 'Sent Time:' . $email['sent_date'] : '';
            
            echo "<tr>";
            echo "<td> Name: " . htmlspecialchars($email['customer_name']) . "<br> Email: ". htmlspecialchars($email['customer_email']) . "</td>";
            echo "<td> Subject: " . htmlspecialchars($email['subject']) . "<br> Template: ". htmlspecialchars($email['template']) . "</td>";
            echo "<td> Name: " . htmlspecialchars($email['from_name']) . "<br> Email: ". htmlspecialchars($email['from_email']) . "</td>";
            echo "<td>" . htmlspecialchars($email['requested_date']) . "<br>" . $sentDate . "</td>";
            echo "<td>" . htmlspecialchars($email['status']) . "<br>" . $msg . "</td>";
            echo "<td>" . htmlspecialchars($email['email_id']) . "</td>";

            // Add a button to remove queued emails
            if ($email['status'] === 'queued') {
                echo '<td><a href="?remove=' . htmlspecialchars($email['customer_email']) . '&name=' . htmlspecialchars($email['customer_name']) . '">Remove</a></td>';
            } else {
                echo "<td></td>";
            }

            echo "</tr>";
        }
        } else {
            echo "<tr><td colspan='8'>No emails found.</td></tr>";
        }
    }
    else
    {
        echo "<tr><td colspan='8'>No emails found.</td></tr>";
    }
}

// Function to validate a participant string
function validateParticipant($participant) {
    $email = explode(":", $participant)[1];
    // Use PHP's built-in filter_var function to validate the email address
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return true; // Email address is valid
    }
return false; // Invalid participant format
}

function removeParticipant() {
    if (isset($_GET['remove'])) {
        // Read current file
        $queueList = json_decode(file_get_contents('email_data.txt'), true);
    
        // Define the filter criteria
        $filterCriteria = [
            'status' => 'queued',
            'customer_email' => $_GET['remove'],
            'customer_name' => $_GET['name'],
        ];
    
        // Use array_filter to filter the data based on the criteria
        $filteredData = array_filter($queueList, function ($item) use ($filterCriteria) {
            foreach ($filterCriteria as $key => $value) {
                if ($item[$key] !== $value) {
                   return false; // This item does not match the criteria
                }
            }
            return true; // All criteria match
        });
    
        foreach ($filteredData as $key => $value) {
            unset($queueList[$key]);
        }
    
        // Append the validated email data to the file
        $jsonData = json_encode($queueList);
        file_put_contents('email_data.txt', $jsonData);
    
        // Redirect back to the dashboard
        header("Location: index.php");
        exit;
    }
}

function submitParticipants() {
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $template = $_POST["template"];
        $subject = $_POST["subject"];
        $fromName = $_POST["fromName"];
        $fromEmail = $_POST["fromEmail"];
        $participants = explode("\n", $_POST["participants"]);
    
        // Read current file
        $queueList = json_decode(file_get_contents('email_data.txt'), true);
    
        // Iterate through participants and validate each one
        foreach ($participants as $participant) {
            $participant = trim($participant);
            if (!empty($participant) && validateParticipant($participant)) {
                $queueList[] = [
                    'customer_name' => explode(":", $participant)[0],
                    'customer_email' => explode(":", $participant)[1],
                    'template' => $template,
                    'subject' => $subject,
                    'from_name' => $fromName,
                    'from_email' => $fromEmail,
                    'requested_date' => date('Y-m-d H:i:s'),
                    'status' => 'queued',
                    'email_id' => ''
                ];
            }
        }
    
        if (!empty($queueList)) {
            $dataFile = 'email_data.txt';
    
            // Append the validated email data to the file
            $jsonData = json_encode($queueList);
            file_put_contents('email_data.txt', $jsonData);
        }
    }
}

?>