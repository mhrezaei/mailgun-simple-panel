<?php
require 'vendor/autoload.php';
use Mailgun\Mailgun;

# Load email sending interval from config
require 'config.php';

// Read domain name and Mailgun token from config
$domain = MAILGUN_DOMAIN;
$apiKey = MAILGUN_API_KEY;

# Function to send an email using Mailgun
function sendEmail($email, $domain, $apiKey) {
    try {    
        // Extract email data
        $fromName = $email['from_name'];
        $fromEmail = $email['from_email'];
        $subject = $email['subject'];
        $template = $email['template'];
        $customerName = $email['customer_name'];
        $customerEmail = $email['customer_email'];

        $hXMailgunVariables = json_encode(['firstname' => $customerName]);

        // Mailgun API endpoint URL
        $url = 'https://api.mailgun.net/v3/' . $domain . '/messages';

        // Email data
        $data = array(
            'from' => $fromName . '<' . $fromEmail . '>',
            'to' => $customerName . '<' . $customerEmail . '>',
            'subject' => $subject,
            'template' => $template,
            'h:X-Mailgun-Variables' => $hXMailgunVariables
        );

        // Initialize cURL session
        $ch = curl_init();

        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, "api:" . $apiKey);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        // Execute cURL session and store the response
        $response = curl_exec($ch);
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Check for cURL errors
        if (curl_errno($ch)) {
            $email['status'] = 'queued';
            $email['error_message'] = 'cURL Error: ' . curl_error($ch);
        } else {
            // Check for a successful response (HTTP status 200)
            if ($httpStatus === 200) {
                $responseData = json_decode($response, true);

                // Check if the JSON response was successfully decoded
                if ($responseData !== null) {
                    // Output the ID key from the response
                    $email['status'] = 'sent';
                    $email['email_id'] = $responseData['id'];
                    $email['sent_date'] = date('Y-m-d H:i:s');
                } else {
                    $email['status'] = 'failed';
                    $email['error_message'] = 'Failed to decode JSON response.';
                }
            } else {
                $email['status'] = 'failed';
                $email['error_message'] = 'Mailgun API Error: HTTP Status ' . $httpStatus . ' - ' . $response;
            }
        }

    } catch (Exception $e) {
        // Exception occurred during email sending
        $email['status'] = 'failed';
        $email['error_message'] = $e->getMessage();
    }

    return $email;
}

// Read email data from email_data.txt
$emailDataFile = 'email_data.txt';

while (true) {
    
    // Read email data from email_data.txt
    if (file_exists($emailDataFile)) {
        $emailData = json_decode(file_get_contents($emailDataFile), true);
    } else {
        $emailData = [];
    }

    if (!empty($emailData)) {
        // Find the first queued email
        $queuedEmailKey = null;
        foreach ($emailData as $key => $email) {
            if ($email['status'] === 'queued') {
                $queuedEmailKey = $key;
                break;
            }
        }

        if ($queuedEmailKey !== null) {
            // Send the email with extracted data
            $queuedEmail = $emailData[$queuedEmailKey];
            $emailData[$queuedEmailKey] = sendEmail($queuedEmail, $domain, $apiKey);

            if ($emailData[$queuedEmailKey]['status'] === 'sent')
            {
                // Move the email to emails_sent.txt
                $sentEmailsFile = 'emails_sent.txt';
                $sentEmails = [];

                if (file_exists($sentEmailsFile)) {
                    $sentEmails = json_decode(file_get_contents($sentEmailsFile), true);
                }

                $sentEmails[] = $emailData[$queuedEmailKey];
                file_put_contents($sentEmailsFile, json_encode($sentEmails, JSON_PRETTY_PRINT));
                
                // Remove the email from email_data.txt
                unset($emailData[$queuedEmailKey]);

                // Update email data file with the updated status
                file_put_contents($emailDataFile, json_encode($emailData, JSON_PRETTY_PRINT));
            }
            else {
                // Update the email status
                $emailData[$queuedEmailKey]['status'] = $queuedEmail['status'];

                // Update email data file with the updated status
                file_put_contents($emailDataFile, json_encode($emailData, JSON_PRETTY_PRINT));
            }

            // Sleep for the specified interval (20 seconds)
            sleep(EMAIL_SENDING_INTERVAL);

            // Read email data from email_data.txt
            if (file_exists($emailDataFile)) {
                $emailData = json_decode(file_get_contents($emailDataFile), true);
            } else {
                $emailData = [];
            }

        } else {
            // No queued emails found, break out of the loop
            break;
        }
    }
}
?>
