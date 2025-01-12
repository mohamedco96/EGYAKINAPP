<?php
// Increase maximum execution time
set_time_limit(120);

// Map answers by question_id for efficient lookups
$answers = collect($questionData)->keyBy('id');

// Helper function to process answers
function processAnswer($answers, $questionId) {
    $answer = $answers[$questionId]['answer']['answers'] ?? null;
    $otherField = $answers[$questionId]['answer']['other_field'] ?? null;
    
    if ($answer === "Others" && !empty(trim($otherField))) {
        return $otherField;
    }
    return $answer;
}

// Helper function to handle "Yes" answers with an optional "other_field"
function processYesAnswer($answers, $questionId, $otherQuestionId) {
    $answer = $answers[$questionId]['answer']['answers'] ?? null;
    $otherField = $answers[$otherQuestionId]['answer'] ?? null;
    
    if ($answer === "Yes" && !empty(trim($otherField))) {
        return $answer . ', ' . $otherField;
    }
    return $answer;
}

// Helper function to process multiple answers with filtering of "Others"
function processMultipleAnswers($answers, $questionId) {
    $answersText = '';
    $filteredAnswers = array_filter($answers[$questionId]['answer']['answers'], function($answer) {
        return $answer !== "Others";
    });
    $answersText .= implode(', ', $filteredAnswers);
    if (!empty(trim($answers[$questionId]['answer']['other_field']))) {
        if (!empty($answersText)) {
            $answersText .= ', ';
        }
        $answersText .= $answers[$questionId]['answer']['other_field'];
    }
    return $answersText;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Report Summary</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background-image: url('https://i.ibb.co/8xJyVMt/Whats-App-Image-2024-07-15-at-11-38-02-PM-removebg-preview.png');
            background-repeat: no-repeat;
            background-attachment: fixed;
            background-size: contain;
            background-position: center;
            opacity: 0.80;
        }

        .container {
            padding: 20px;
        }

        .header {
            background-color: #6f42c1;
            color: white;
            padding: 10px;
            text-align: center;
            margin-bottom: 30px;
            border-radius: 5px;
        }

        .header p {
            font-weight: bold;
            font-size: 1.2em;
            text-shadow: 1px 1px 2px #000;
        }

        .section {
            margin-bottom: 30px;
            padding: 20px;
            border-radius: 10px;
            background-color: #f8f9fa;
        }

        .section h2 {
            font-size: 20px;
            margin-bottom: 10px;
            color: #6f42c1;
        }

        .footer {
            background-color: #6f42c1;
            color: #ffffff;
            padding: 10px;
            font-size: large;
            text-align: center;
            margin-top: 30px;
            border-radius: 5px;
        }

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
    <div class="header text-center">
        <p>Arab Republic of Egypt</p>
        <p>Egyptian Acute Kidney Injury Registry</p>
        <p>Medical Report</p>
    </div>

    <!-- General Application Data Section -->
    <div class="row">
        <div class="col-md-12">
            <div class="section">
                <h2>EGYAKIN</h2>
            </div>
        </div>
    </div>

    <!-- Patient Information Section -->
    <div class="row">
        <div class="col-md-12">
            <div class="section">
                <h2>Patient Information</h2>
                <table>
                    <tbody>
                    <tr>
                        <td class="Patient-Information-background">Patient Name</td>
                        <td>{{ $answers[1]['answer'] ?? null }}</td>
                        <td class="Patient-Information-background">Patient ID</td>
                        <td>{{ $patient_id }}</td>
                    </tr>
                    <tr>
                        <td class="Patient-Information-background">Patient Phone</td>
                        <td>{{ $answers[5]['answer'] ?? null }}</td>
                        <td class="Patient-Information-background">Patient Email</td>
                        <td>{{ $answers[6]['answer'] ?? null }}</td>
                    </tr>
                    <tr>
                        <td class="Patient-Information-background">Department</td>
                        <td colspan="3">{{ processAnswer($answers, 168) }}</td>
                    </tr>
                    <tr>
                        <td class="Patient-Information-background">Age</td>
                        <td>{{ $answers[7]['answer'] ?? null }}</td>
                        <td class="Patient-Information-background">Gender</td>
                        <td>{{ $answers[8]['answer']['answers'] ?? null }}</td>
                    </tr>
                    <tr>
                        <td class="Patient-Information-background">Occupation</td>
                        <td>{{ $answers[9]['answer']['answers'] ?? null }}</td>
                        <td class="Patient-Information-background">Governorate</td>
                        <td>{{ $answers[11]['answer']['answers'] ?? null }}</td>
                    </tr>
                    <tr>
                        <td class="Patient-Information-background">Marital Status</td>
                        <td>{{ $answers[12]['answer']['answers'] ?? null }}</td>
                        <td class="Patient-Information-background">Children</td>
                        <td>{{ $answers[142]['answer'] ?? null }}</td>
                    </tr>
                    <tr>
                        <td class="Patient-Information-background">Special Habits</td>
                        <td colspan="3">{{ processMultipleAnswers($answers, 14) }}</td>
                    </tr>
                    <tr>
                        <td class="Patient-Information-background">DM</td>
                        <td>{{ processYesAnswer($answers, 16, 17) }}</td>
                        <td class="Patient-Information-background">HTN</td>
                        <td>{{ processYesAnswer($answers, 18, 19) }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Complaint Section -->
    <div class="row">
        <div class="col-md-12">
            <div class="section">
                <h2>Complaint</h2>
            <table border="1" style="width: 100%; border-collapse: collapse;">
                <tbody>
                <tr>
                    <td class="Complaint-background">Main Complaint</td>
                    <td colspan="3">{{ processMultipleAnswers($answers, 24) }}</td>
                </tr>
                <tr>
                    <td class="Complaint-background">Urine Output</td>
                    <td colspan="3">{{ processAnswer($answers, 162) }}</td>
                </tr>
                <tr>
                    <td class="Complaint-background">Provisional Diagnosis</td>
                    <td colspan="3">{{ processMultipleAnswers($answers, 166) }}</td>
                </tr>
                </tbody>
            </table>
            </div>
        </div>
    </div>
    <!--  -->


    <!-- Cause of AKI Section -->
    <div class="row">
        <div class="col-md-12">
        <div class="section">
                <h2>Cause of AKI</h2>
            <table border="1" style="width: 100%; border-collapse: collapse;">
                <tbody>
                <tr>
                    <td class="Cause-background">Cause Of AKI</td>
                    <td colspan="3">{{ processAnswer($answers, 26) }}</td>
                </tr>
                <tr>
                    <td class="Cause-background">Pre-Renal Causes</td>
                    <td colspan="3">{{ processMultipleAnswers($answers, 27) }}</td>
                </tr>
                <tr>
                    <td class="Cause-background">Intrinsic Renal Causes</td>
                    <td colspan="3">{{ processMultipleAnswers($answers, 29) }}</td>
                </tr>
                <tr>
                    <td class="Cause-background">Post_Renal Causes</td>
                    <td colspan="3">{{ processMultipleAnswers($answers, 31) }}</td>
                </tr>
                <tr>
                    <td class="Cause-background">Other Causes</td>
                    <td colspan="3">{{ $answers[33]['answer'] ?? null }}</td>
                </tr>
                </tbody>
            </table>
            </div>
        </div>
    </div>
    <!--  -->

    <!-- Risk Factors for AKI Section -->
    <div class="row">
        <div class="col-md-12">
            <div class="section">
                    <h2>Risk Factors for AKI</h2>
                <table border="1" style="width: 100%; border-collapse: collapse;">
                    <tbody>
                    <tr>
                        <td class="Risk-background">History OF CKD</td>
                        <td>{{ processAnswer($answers, 34) }}</td>
                        <td class="Risk-background">History OF AKI</td>
                        <td>{{ processAnswer($answers, 35) }}</td>
                    </tr>
                    <tr>
                        <td class="Risk-background">History OF Cardiac Failure</td>
                        <td>{{ processAnswer($answers, 36) }}</td>
                        <td class="Risk-background">History OF LCF</td>
                        <td>{{ processAnswer($answers, 37) }}</td>
                    </tr>
                    <tr>
                        <td class="Risk-background">History OF Sepsis</td>
                        <td>{{ processAnswer($answers, 39) }}</td>
                        <td class="Risk-background">History OF Hypovolemia</td>
                        <td>{{ processAnswer($answers, 43) }}</td>
                    </tr>
                    <tr>
                        <td class="Risk-background">History OF Malignancy</td>
                        <td>{{ processAnswer($answers, 44) }}</td>
                        <td class="Risk-background">History OF Trauma</td>
                        <td>{{ processAnswer($answers, 45) }}</td>
                    </tr>
                    <tr>
                        <td class="Risk-background">History OF Autoimmune Disease</td>
                        <td colspan="3">{{ processAnswer($answers, 46) }}</td>
                    </tr>
                    <tr>
                        <td class="Risk-background">History of neurological impairment or disability</td>
                        <td colspan="3">{{ processAnswer($answers, 38) }}</td>
                    </tr>
                    <tr>
                        <td class="Risk-background">Recent use of iodinated contrast media</td>
                        <td colspan="3">{{ processAnswer($answers, 40) }}</td>
                    </tr>
                    <tr>
                        <td class="Risk-background">Current or recent use of drugs with potential nephrotoxicity</td>
                        <td colspan="3">{{ processAnswer($answers, 41) }}</td>
                    </tr>
                    <tr>
                        <td class="Risk-background">Other risk factors</td>
                        <td colspan="3">{{ processAnswer($answers, 47) }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!--  -->

    <!-- Footer -->
    <div class="footer">
        <p>&copy; {{ date('Y') }} Patient Report Summary. All rights reserved.</p>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>
