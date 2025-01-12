<?php
// Increase maximum execution time
set_time_limit(120);

// Map answers by question_id for efficient lookups
$answers = collect($patient->answers)->keyBy('question_id');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Report Summary</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        /* General styling and watermark */
        body {
            background-image: url('https://i.ibb.co/8xJyVMt/Whats-App-Image-2024-07-15-at-11-38-02-PM-removebg-preview.png'); /* Path to the image in the public directory */
            background-repeat: no-repeat; /* No repeat */
            background-attachment: fixed; /* Fixed position */
            background-size: contain; /* Adjust size as needed */
            background-position: center; /* Centered positioning */
            opacity: 0.80; /* Adjust opacity as needed */
        }

        /* General styling */
        .container {
            padding: 20px;
        }

        .header {
            background-color: #6f42c1; /* Purple header */
            color: white;
            padding: 10px;
            text-align: center;
            margin-bottom: 30px;
            position: relative;
            border-radius: 5px;
        }

        .header h1 {
            margin-bottom: 0;
        }

        .section {
            margin-bottom: 30px;
            padding: 20px;
            border-radius: 10px;
            background-color: #f8f9fa; /* Light gray background */
            transition: background-color 0.3s;
        }

        .section:hover {
            /*background-color: #e9ecef; !* Darker gray background on hover *!*/
        }

        .section h2 {
            font-size: 20px;
            margin-bottom: 10px;
            color: #6f42c1; /* Purple heading */
        }

        .section p {
            font-size: 16px;
            margin-bottom: 5px;
            /*color: #6c757d; !* Gray text *!*/
        }

        .footer {
            background-color: #6f42c1; /* Purple footer */
            color: #ffffff;
            padding: 10px;
            font-size: large;
            text-align: center;
            margin-top: 30px;
            border-radius: 5px;
        }

        /* Table styling */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        th, td {
            border: 1px solid black;
            text-align: left;
            padding: 8px;
        }


        th {
            background-color: #f2f2f2;
        }

        .center-text {
            text-align: center;
        }

        .Patient-Information-background {
            background-color: #e5dfec;
            font-weight: bold;
        }

        .Complaint-background {
            background-color: #fbd5b5;
            font-weight: bold;
        }

        .Cause-background {
            background-color: #dbe5f1;
            font-weight: bold;
        }

        .Risk-background {
            background-color: #eeece1;
            font-weight: bold;
        }

        .Assessment-background {
            background-color: #fbd5b5;
            font-weight: bold;
        }

        .Medical-background {
            background-color: #b8cce4;
            font-weight: bold;
        }

        .Laboratory-background {
            background-color: #fdeada;
            font-weight: bold;
        }

        .Radiology-background {
            background-color: #fdeada;
            font-weight: bold;
        }

        .CTS-patient-background {
            background-color: #dbeef3;
            font-weight: bold;
        }

        .Operative-details-background {
            background-color: #e5dfec;
            font-weight: bold;
        }

        .Go-Patients-background {
            background-color: #f5cfee;
            font-weight: bold;
        }
    </style>
</head>

<body>
<div class="container">

    <!-- Header -->
    <div class="header" text-center>
        <!--<img src="https://via.placeholder.com/150" alt="Logo" class="logo">-->
        <!-- <h1>Patient Report Summary</h1> -->
        <p style="font-weight: bold; font-size: 1.2em; text-shadow: 1px 1px 2px #000; color: white;">Arab Republic of Egypt</p>
        <p style="font-weight: bold; font-size: 1.2em; text-shadow: 1px 1px 2px #000; color: white;">Egyptian Acute Kidney Injury Registry</p>
        <p style="font-weight: bold; font-size: 1.2em; text-shadow: 1px 1px 2px #000; color: white;">Medical Report</p>
    </div>

    <!-- General Application Data Section -->
    <div class="row">
        <div class="col-md-12">
            <div class="section">
                <h2>EGYAKIN</h2>
                <p>Report generated for Dr.<strong>{{ $patient->doctor->name ?? 'Unknown' }}</strong></p>
            </div>
        </div>
    </div>

    <!-- Patient Information Section -->
    <div class="row">
        <div class="col-md-12">
            <div class="section">
                <!-- Patient History Table -->
                <h2>Patient Information</h2>

                <table border="1" style="width: 100%; border-collapse: collapse;">
                    <thead>
                    </thead>
                    <tbody>
                    <tr>
                        <td class="Patient-Information-background">Patient Name</td>
                        <!-- <td>{{ $questionData[1]['answer'] ?? null }}</td> -->
                        <td class="Patient-Information-background">Patient ID</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td class="Patient-Information-background">Patient Phone</td>
                        <td></td>
                        <td class="Patient-Information-background">Patient Email</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td class="Patient-Information-background">Department</td>
                        <td colspan="3"></td>
                    </tr>
                    <tr>
                        <td class="Patient-Information-background">Age</td>
                        <td></td>
                        <td class="Patient-Information-background">Gender</td>
                        <!-- <td>{{ collect($questionData)->where('id', 8)->first()['answer']['answers'] ?? 'Not Provided' }}</td> -->
                    </tr>
                    <tr>
                        <td class="Patient-Information-background">Occupation</td>
                        <td></td>
                        <td class="Patient-Information-background">Governorate</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td class="Patient-Information-background">Marital Status</td>
                        <td></td>
                        <td class="Patient-Information-background">Children</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td class="Patient-Information-background">Special Habits</td>
                        <td colspan="3">
                        </td>
                    </tr>
                    <tr>
                        <td class="Patient-Information-background">DM</td>
                        <td></td>
                        <td class="Patient-Information-background">HTN</td>
                        <td></td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!--  -->


    <!-- Footer -->
    <div class="footer">
        <p>&copy; <?php echo date('Y'); ?> Patient Report Summary. All rights reserved.</p>
    </div>
</div>
<!-- Bootstrap JS (optional) -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>
