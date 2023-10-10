<?php

require_once 'functions.php';

removeParticipant();

submitParticipants();

?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" type="text/css" href="css/styles.css">
    <title>Sample Mailgun Send Mail Using Template</title>
</head>
<body>
    <h1>Email Dashboard</h1>
    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
        <label for="template">Template Name:</label>
        <input type="text" id="template" name="template" placeholder="Enter Template Name: Happy Birthdate" required/><br>

        <label for="subject">Email Subject:</label>
        <input type="text" id="subject" name="subject" placeholder="Email Subject: Hadi" required/><br>

        <label for="fromName">From Name:</label>
        <input type="text" id="fromName" name="fromName" placeholder="Sender Name: Hadi Sales" required/><br>
        
        <label for="fromEmail">From Email:</label>
        <input type="text" id="fromEmail" name="fromEmail" placeholder="Sender Email: Mohammad Hadi R:hadi@yasna.team" required/><br>

        <label for="participants">Participants:</label>
        <textarea id="participants" name="participants" placeholder='Mohammad Hadi R:hadi@yasna.team' required></textarea><br>

        <button type="submit" name="SubmitButton" value="Submit Button">Submit Button</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>Customer Info</th>
                <th>Email Info</th>
                <th>Sender Info</th>
                <th>Requested Date</th>
                <th>Status</th>
                <th>Email ID</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <!-- PHP code to populate the table -->
            <?php emailsList(); ?>
        </tbody>
    </table>
</body>
</html>