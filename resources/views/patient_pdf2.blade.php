<?php
set_time_limit(120);

// Map answers by question_id for efficient lookups
$answers = collect($questionData)->keyBy('id');

// Generic function to process a question based on its type
function processQuestion($answers, $questionId) {
    if (!isset($answers[$questionId])) {
        return null;
    }

    $question = $answers[$questionId];
    $type = $question['type'] ?? 'string';

    switch ($type) {
        case 'multiple':
            return processMultipleAnswers($answers, $questionId);
        case 'select':
            return processSelectAnswer($answers, $questionId);
        case 'files': // New type to handle file paths
            return processFileAnswers($answers, $questionId);
        default: // string or other types
            return $question['answer'] ?? null;
    }
}

// Helper function to process "multiple" type answers
function processMultipleAnswers($answers, $questionId) {
    $question = $answers[$questionId];
    
    // Ensure we have an array to work with
    $answerData = $question['answer'] ?? [];
    $answersArray = $answerData['answers'] ?? [];
    
    // If answers is a string, convert it to an array
    if (is_string($answersArray)) {
        $answersArray = [$answersArray];
    }
    
    $filteredAnswers = array_filter($answersArray, function($answer) {
        return $answer !== "Others";
    });

    $answersText = implode(', ', $filteredAnswers);

    if (!empty(trim($answerData['other_field'] ?? ''))) {
        $answersText .= (!empty($answersText) ? ', ' : '') . $answerData['other_field'];
    }

    return $answersText;
}

// Helper function to process "select" type answers
function processSelectAnswer($answers, $questionId) {
    $question = $answers[$questionId] ?? [];
    $answerData = $question['answer'] ?? [];
    
    $answer = $answerData['answers'] ?? null;
    $otherField = $answerData['other_field'] ?? null;

    if ($answer === "Others" && !empty(trim($otherField))) {
        return $otherField;
    }

    return $answer;
}

// Helper function to process "files" type answers
function processFileAnswers($answers, $questionId) {
    $question = $answers[$questionId] ?? null;
    
    if (!$question || !isset($question['answer'])) {
        return null;
    }

    $filePaths = $question['answer'];
    
    // If it's not an array, make it one (in case single file is stored as string)
    if (!is_array($filePaths)) {
        $filePaths = [$filePaths];
    }

    return array_map(function($filePath) {
        // If the path is already a full URL, return it as-is
        if (filter_var($filePath, FILTER_VALIDATE_URL)) {
            return $filePath;
        }
        
        // Otherwise, convert storage path to URL
        return url('storage/' . str_replace('\\/', '/', $filePath));
    }, $filePaths);
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

        .Discharge-Recommendations-background {
            background-color: #d4edda;
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
                        <td>{{ processQuestion($answers, 1) }}</td>
                        <td class="Patient-Information-background">Patient ID</td>
                        <td>{{ $patient_id }}</td>
                    </tr>
                    <tr>
                        <td class="Patient-Information-background">Patient Phone</td>
                        <td>{{ processQuestion($answers, 5) }}</td>
                        <td class="Patient-Information-background">Patient Email</td>
                        <td>{{ processQuestion($answers, 6) }}</td>
                    </tr>
                    <tr>
                        <td class="Patient-Information-background">Department</td>
                        <td colspan="3">{{ processQuestion($answers, 168) }}</td>
                    </tr>
                    <tr>
                        <td class="Patient-Information-background">Age</td>
                        <td>{{ processQuestion($answers, 7) }}</td>
                        <td class="Patient-Information-background">Gender</td>
                        <td>{{ processQuestion($answers, 8) }}</td>
                    </tr>
                    <tr>
                        <td class="Patient-Information-background">Occupation</td>
                        <td>{{ processQuestion($answers, 9) }}</td>
                        <td class="Patient-Information-background">Governorate</td>
                        <td>{{ processQuestion($answers, 11) }}</td>
                    </tr>
                    <tr>
                        <td class="Patient-Information-background">Marital Status</td>
                        <td>{{ processQuestion($answers, 12) }}</td>
                        <td class="Patient-Information-background">Children</td>
                        <td>{{ processQuestion($answers, 142) }}</td>
                    </tr>
                    <tr>
                        <td class="Patient-Information-background">Special Habits</td>
                        <td colspan="3">{{ processQuestion($answers, 14) }}</td>
                    </tr>
                    <tr>
                        <td class="Patient-Information-background">DM</td>
                        <td>{{ processQuestion($answers, 16) }}</td>
                        <td class="Patient-Information-background">HTN</td>
                        <td>{{ processQuestion($answers, 18) }}</td>
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
                    <td colspan="3">{{ processQuestion($answers, 24) }}</td>
                </tr>
                <tr>
                    <td class="Complaint-background">Urine Output</td>
                    <td colspan="3">{{ processQuestion($answers, 162) }}</td>
                </tr>
                <tr>
                    <td class="Complaint-background">Provisional Diagnosis</td>
                    <td colspan="3">{{ processQuestion($answers, 166) }}</td>
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
                            <td colspan="3">{{ processQuestion($answers, 26) }}</td>
                        </tr>
                        <tr>
                            <td class="Cause-background">Pre-Renal Causes</td>
                            <td colspan="3">{{ processQuestion($answers, 27) }}</td>
                        </tr>
                        <tr>
                            <td class="Cause-background">Intrinsic Renal Causes</td>
                            <td colspan="3">{{ processQuestion($answers, 29) }}</td>
                        </tr>
                        <tr>
                            <td class="Cause-background">Post-Renal Causes</td>
                            <td colspan="3">{{ processQuestion($answers, 31) }}</td>
                        </tr>
                        <tr>
                            <td class="Cause-background">Other Causes</td>
                            <td colspan="3">{{ processQuestion($answers, 33) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- -->

    <!-- Risk Factors for AKI Section -->
    <div class="row">
        <div class="col-md-12">
            <div class="section">
                <h2>Risk Factors for AKI</h2>
                <table border="1" style="width: 100%; border-collapse: collapse;">
                    <tbody>
                        <tr>
                            <td class="Risk-background">History OF CKD</td>
                            <td>{{ processQuestion($answers, 34) }}</td>
                            <td class="Risk-background">History OF AKI</td>
                            <td>{{ processQuestion($answers, 35) }}</td>
                        </tr>
                        <tr>
                            <td class="Risk-background">History OF Cardiac Failure</td>
                            <td>{{ processQuestion($answers, 36) }}</td>
                            <td class="Risk-background">History OF LCF</td>
                            <td>{{ processQuestion($answers, 37) }}</td>
                        </tr>
                        <tr>
                            <td class="Risk-background">History OF Sepsis</td>
                            <td>{{ processQuestion($answers, 39) }}</td>
                            <td class="Risk-background">History OF Hypovolemia</td>
                            <td>{{ processQuestion($answers, 43) }}</td>
                        </tr>
                        <tr>
                            <td class="Risk-background">History OF Malignancy</td>
                            <td>{{ processQuestion($answers, 44) }}</td>
                            <td class="Risk-background">History OF Trauma</td>
                            <td>{{ processQuestion($answers, 45) }}</td>
                        </tr>
                        <tr>
                            <td class="Risk-background">History OF Autoimmune Disease</td>
                            <td colspan="3">{{ processQuestion($answers, 46) }}</td>
                        </tr>
                        <tr>
                            <td class="Risk-background">History of neurological impairment or disability</td>
                            <td colspan="3">{{ processQuestion($answers, 38) }}</td>
                        </tr>
                        <tr>
                            <td class="Risk-background">Recent use of iodinated contrast media</td>
                            <td colspan="3">{{ processQuestion($answers, 40) }}</td>
                        </tr>
                        <tr>
                            <td class="Risk-background">Current or recent use of drugs with potential nephrotoxicity</td>
                            <td colspan="3">{{ processQuestion($answers, 41) }}</td>
                        </tr>
                        <tr>
                            <td class="Risk-background">Other risk factors</td>
                            <td colspan="3">{{ processQuestion($answers, 47) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- -->

    <!-- Assessment of patient Section -->
    <div class="row">
        <div class="col-md-12">
            <div class="section">
                <h2>Assessment of patient</h2>
                <table border="1" style="width: 100%; border-collapse: collapse;">
                    <tbody>
                        <tr>
                            <td class="Assessment-background">Heart rate/minute</td>
                            <td>{{ processQuestion($answers, 48) }}</td>
                            <td class="Assessment-background">Respiratory rate/minute</td>
                            <td>{{ processQuestion($answers, 49) }}</td>
                            <td class="Assessment-background">SBP</td>
                            <td>{{ processQuestion($answers, 50) }}</td>
                        </tr>
                        <tr>
                            <td class="Assessment-background">DBP</td>
                            <td>{{ processQuestion($answers, 51) }}</td>
                            <td class="Assessment-background">GCS</td>
                            <td>{{ processQuestion($answers, 52) }}</td>
                            <td class="Assessment-background">Temperature</td>
                            <td>{{ processQuestion($answers, 54) }}</td>
                        </tr>
                        <tr>
                            <td class="Assessment-background">Oxygen saturation (%)</td>
                            <td>{{ processQuestion($answers, 53) }}</td>
                            <td class="Assessment-background">UOP (ml/hour)</td>
                            <td>{{ processQuestion($answers, 55) }}</td>
                            <td class="Assessment-background">AVP</td>
                            <td>{{ processQuestion($answers, 56) }}</td>
                        </tr>
                        <tr>
                            <td class="Assessment-background">Height/cm</td>
                            <td>{{ processQuestion($answers, 140) }}</td>
                            <td class="Assessment-background">Weight/cm</td>
                            <td colspan="3">{{ processQuestion($answers, 141) }}</td>
                        </tr>
                        <tr>
                            <td class="Assessment-background">Abdominal Examination</td>
                            <td colspan="5">{{ processQuestion($answers, 68) }}</td>
                        </tr>
                        <tr>
                            <td class="Assessment-background">Skin examination</td>
                            <td colspan="5">{{ processQuestion($answers, 57) }}</td>
                        </tr>
                        <tr>
                            <td class="Assessment-background">Eye examination</td>
                            <td colspan="5">{{ processQuestion($answers, 59) }}</td>
                        </tr>
                        <tr>
                            <td class="Assessment-background">Ear examination</td>
                            <td colspan="5">{{ processQuestion($answers, 61) }}</td>
                        </tr>
                        <tr>
                            <td class="Assessment-background">Cardiac examination</td>
                            <td colspan="5">{{ processQuestion($answers, 63) }}</td>
                        </tr>
                        <tr>
                            <td class="Assessment-background">Internal jugular vein</td>
                            <td colspan="5">{{ processQuestion($answers, 65) }}</td>
                        </tr>
                        <tr>
                            <td class="Assessment-background">Chest examination</td>
                            <td colspan="5">{{ processQuestion($answers, 66) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- -->

    <!-- Medical Decision Section -->
    <div class="row">
        <div class="col-md-12">
            <div class="section">
                <h2>Medical Decision</h2>
                <table border="1" style="width: 100%; border-collapse: collapse;">
                    <tbody>
                        <tr>
                            <td class="Medical-background">Medical decision</td>
                            <td>{{ processQuestion($answers, 77) }}</td>
                            <td class="Medical-background">Dialysis</td>
                            <td>{{ processQuestion($answers, 86) }}</td>
                        </tr>
                        <tr>
                            <td class="Medical-background">Dialysis Modality</td>
                            <td colspan="3">{{ processQuestion($answers, 87) }}</td>
                        </tr>
                        <tr>
                            <td class="Medical-background">Dialysis indication</td>
                            <td colspan="3">{{ processQuestion($answers, 88) }}</td>
                        </tr>
                        <tr>
                            <td class="Medical-background">Number of sessions</td>
                            <td colspan="3">{{ processQuestion($answers, 89) }}</td>
                        </tr>
                        <tr>
                            <td class="Medical-background">Vascular Access</td>
                            <td colspan="3">{{ processQuestion($answers, 90) }}</td>
                        </tr>
                        <tr>
                            <td class="Medical-background">Site of Access</td>
                            <td colspan="3">{{ processQuestion($answers, 232) }}</td>
                        </tr>
                        <tr>
                            <td class="Medical-background">Lines of management</td>
                            <td colspan="3">{{ processQuestion($answers, 91) }}</td>
                        </tr>
                        <tr>
                            <td class="Medical-background">Immunosuppressive types</td>
                            <td colspan="3">{{ processQuestion($answers, 233) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- -->

    <!-- Outcome Section -->
    <div class="row">
        <div class="col-md-12">
            <div class="section">
                <h2>Outcome</h2>
                <p>Outcome of the patient is <strong>{{ processQuestion($answers, 79) ?? 'Unknown' }}</strong></p>
            </div>
        </div>
    </div>
    <!-- -->

    <!-- Laboratory Section -->
    <div class="row">
        <div class="col-md-12">
            <div class="section">
                <table>
                    <thead>
                    <tr>
                        <th class="Laboratory-background">Laboratory Parameters</th>
                        <th class="Laboratory-background">On admission</th>
                        <th class="Laboratory-background">On discharge</th>
                    </tr>
                    </thead>
                    <tbody>
                    @php
                        $labParameters = [
                            'pH /mmhg' => [92, 116],
                            'HCO3 /mmhg' => [93, 117],
                            'pCO2 /mmhg' => [94, 118],
                            'K mg/dl' => [95, 119],
                            'SGOT u/l' => [96, 120],
                            'SGPT u/l' => [97, 121],
                            'Albumin gm/dl' => [98, 122],
                            'HCV Ab' => [99, null],
                            'HBs Ag' => [100, null],
                            'HIV Ab' => [101, null],
                            'Hemoglobin gm/dl' => [102, 126],
                            'WBCs count' => [103, 127],
                            'Platelets count' => [104, 128],
                            'Neutrophil count' => [105, 129],
                            'Lymphocytes count' => [106, 130],
                            'Creatinine (mg/dl)' => [71, 80],
                            'Urea mg/dl' => [107, 131],
                            'BUN mg/dl' => [108, 132],
                            'CRP mg/l' => [143, 144],
                            'Specific gravity (Urine)' => [109, 133],
                            'Clarity (Urine)' => [110, 134],
                            'Epithelial cells (Urine)' => [111, 135],
                            'Crystals types (Urine)' => [112, 136],
                            'Casts (Urine)' => [113, 137],
                            'WBCs (Urine)' => [114, 138],
                            'RBCs (Urine)' => [115, 139],
                        ];
                    @endphp
                    @foreach($labParameters as $parameter => $ids)
                        <tr>
                            <td class="Laboratory-background">{{ $parameter }}</td>
                            <td class="center-text"><strong>{{ processQuestion($answers, $ids[0]) }}</strong></td>
                            <td class="center-text"><strong>{{ $ids[1] ? processQuestion($answers, $ids[1]) : '' }}</strong></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Radiology and biopsy Results Section -->
    <div class="row">
        <div class="col-md-12">
            <div class="section">
                <h2>Radiology and biopsy Results</h2>
                <table border="1" style="width: 100%; border-collapse: collapse;">
                    <tbody>
                    <tr>
                        <td class="Radiology-background">Renal US</td>
                        <td>{{ processQuestion($answers, 73) }}</td>
                    </tr>
                    <tr>
                        <td class="Radiology-background">If renal us is abnormal</td>
                        <td>{{ processQuestion($answers, 74) }}</td>
                    </tr>
                    <tr>
                        <td class="Radiology-background">CT abdomen summary</td>
                        <td>{{ processQuestion($answers, 260) }}</td>
                    </tr>
                    <tr>
                        <td class="Radiology-background">CT chest summary</td>
                        <td>{{ processQuestion($answers, 261) }}</td>
                    </tr>
                    <tr>
                        <td class="Radiology-background">ECHO report Summary</td>
                        <td>{{ processQuestion($answers, 262) }}</td>
                    </tr>
                    <tr>
                        <td class="Radiology-background">Renal Biopsy</td>
                        <td>{{ processQuestion($answers, 252) }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- CTS-patient Section -->
    <div class="row">
        <div class="col-md-12">
            <div class="section">
                <h2>CTS-patient</h2>
                <table border="1" style="width: 100%; border-collapse: collapse;">
                    <tbody>
                    <tr>
                        <td class="CTS-patient-background">Type of surgery</td>
                        <td>{{ processQuestion($answers, 171) }}</td>
                        <td class="CTS-patient-background">Type of cardiac disease</td>
                        <td>{{ processQuestion($answers, 174) }}</td>
                    </tr>
                    <tr>
                        <td class="CTS-patient-background">Preoperative SBP</td>
                        <td>{{ processQuestion($answers, 176) }}</td>
                        <td class="CTS-patient-background">Preoperative DBP</td>
                        <td>{{ processQuestion($answers, 177) }}</td>
                    </tr>
                    <tr>
                        <td class="CTS-patient-background">Preoperative WBCs</td>
                        <td>{{ processQuestion($answers, 178) }}</td>
                        <td class="CTS-patient-background">Preoperative HB</td>
                        <td>{{ processQuestion($answers, 179) }}</td>
                    </tr>
                    <tr>
                        <td class="CTS-patient-background">Preoperative Platelets</td>
                        <td colspan="3">{{ processQuestion($answers, 180) }}</td>
                    </tr>
                    <tr>
                        <td class="CTS-patient-background">Preoperative creatinine</td>
                        <td>{{ processQuestion($answers, 181) }}</td>
                        <td class="CTS-patient-background">Preoperative urine pus cells</td>
                        <td>{{ processQuestion($answers, 182) }}</td>
                    </tr>
                    <tr>
                        <td class="CTS-patient-background">Preoperative urine RBCs</td>
                        <td>{{ processQuestion($answers, 183) }}</td>
                        <td class="CTS-patient-background">Preoperative proteinuria</td>
                        <td>{{ processQuestion($answers, 186) }}</td>
                    </tr>
                    <tr>
                        <td class="CTS-patient-background">Preoperative urine cast</td>
                        <td>{{ processQuestion($answers, 184) }}</td>
                        <td class="CTS-patient-background">Preoperative INR</td>
                        <td>{{ processQuestion($answers, 186) }}</td>
                    </tr>
                    <tr>
                        <td class="CTS-patient-background">Preoperative albumin</td>
                        <td>{{ processQuestion($answers, 187) }}</td>
                        <td class="CTS-patient-background">Preoperative bilirubin</td>
                        <td>{{ processQuestion($answers, 188) }}</td>
                    </tr>
                    <tr>
                        <td class="CTS-patient-background">Preoperative ALT</td>
                        <td>{{ processQuestion($answers, 189) }}</td>
                        <td class="CTS-patient-background">Preoperative AST</td>
                        <td>{{ processQuestion($answers, 190) }}</td>
                    </tr>
                    <tr>
                        <td class="CTS-patient-background">Preoperative Troponin</td>
                        <td>{{ processQuestion($answers, 191) }}</td>
                        <td class="CTS-patient-background">Preoperative pH</td>
                        <td>{{ processQuestion($answers, 208) }}</td>
                    </tr>
                    <tr>
                        <td class="CTS-patient-background">Preoperative Hco3</td>
                        <td>{{ processQuestion($answers, 209) }}</td>
                        <td class="CTS-patient-background">Preoperative pCo2</td>
                        <td>{{ processQuestion($answers, 210) }}</td>
                    </tr>
                    <tr>
                        <td class="CTS-patient-background">Postoperative pH</td>
                        <td>{{ processQuestion($answers, 212) }}</td>
                        <td class="CTS-patient-background">Postoperative Hco3</td>
                        <td>{{ processQuestion($answers, 211) }}</td>
                    </tr>
                    <tr>
                        <td class="CTS-patient-background">Postoperative pCo2</td>
                        <td>{{ processQuestion($answers, 213) }}</td>
                        <td class="CTS-patient-background">Postoperative SBP</td>
                        <td>{{ processQuestion($answers, 214) }}</td>
                    </tr>
                    <tr>
                        <td class="CTS-patient-background">Postoperative DBP</td>
                        <td colspan="3">{{ processQuestion($answers, 215) }}</td>
                    </tr>
                    <tr>
                        <td class="CTS-patient-background">Preoperative ejection Fraction</td>
                        <td colspan="3">{{ processQuestion($answers, 192) }}</td>
                    </tr>
                    <tr>
                        <td class="CTS-patient-background">Preoperative ECHO</td>
                        <td colspan="3">{{ processQuestion($answers, 193) }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Operative details Section -->
    <div class="row">
        <div class="col-md-12">
            <div class="section">
                <h2>Operative details</h2>
                <table border="1" style="width: 100%; border-collapse: collapse;">
                    <tbody>
                    <tr>
                        <td class="Operative-details-background">CPB duration/minutes</td>
                        <td>{{ processQuestion($answers, 194) }}</td>
                        <td class="Operative-details-background">Cross clamping times/minutes</td>
                        <td>{{ processQuestion($answers, 195) }}</td>
                    </tr>
                    <tr>
                        <td class="Operative-details-background">Core temperature/c/lowest</td>
                        <td>{{ processQuestion($answers, 196) }}</td>
                        <td class="Operative-details-background">Core temperature/c/highest</td>
                        <td>{{ processQuestion($answers, 224) }}</td>
                    </tr>
                    <tr>
                        <td class="Operative-details-background">Serum lactate during surgery</td>
                        <td>{{ processQuestion($answers, 197) }}</td>
                        <td class="Operative-details-background">Abnormal Event</td>
                        <td>{{ processQuestion($answers, 199) }}</td>
                    </tr>
                    <tr>
                        <td class="Operative-details-background">Type of cardioplegia -1</td>
                        <td>{{ processQuestion($answers, 201) }}</td>
                        <td class="Operative-details-background">Type of cardioplegia -2</td>
                        <td>{{ processQuestion($answers, 202) }}</td>
                    </tr>
                    <tr>
                        <td class="Operative-details-background">Type of cardioplegia -3</td>
                        <td>{{ processQuestion($answers, 203) }}</td>
                        <td class="Operative-details-background">Type of cardioplegia -4</td>
                        <td>{{ processQuestion($answers, 204) }}</td>
                    </tr>
                    <tr>
                        <td class="Operative-details-background">Blood transfusion</td>
                        <td>{{ processQuestion($answers, 225) }}</td>
                        <td class="Operative-details-background">Blood transfusion type</td>
                        <td>{{ processQuestion($answers, 226) }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!--  -->

    <!-- Go- Patients Section -->
    <div class="row">
        <div class="col-md-12">
            <div class="section">
                <h2>Go- Patients</h2>
                <table border="1" style="width: 100%; border-collapse: collapse;">
                    <tbody>
                    <tr>
                        <td class="Go-Patients-background">Presentation date</td>
                        <td>{{ processQuestion($answers, 234) }}</td>
                        <td class="Go-Patients-background">Gravidity number</td>
                        <td>{{ processQuestion($answers, 235) }}</td>
                    </tr>
                    <tr>
                        <td class="Go-Patients-background">Parity number</td>
                        <td colspan="3">{{ processQuestion($answers, 236) }}</td>
                    </tr>
                    <tr>
                        <td class="Go-Patients-background">First presentation</td>
                        <td>{{ processQuestion($answers, 237) }}</td>
                        <td class="Go-Patients-background">Place of medical care</td>
                        <td>{{ processQuestion($answers, 238) }}</td>
                    </tr>
                    <tr>
                        <td class="Go-Patients-background">Antenatal care</td>
                        <td>{{ processQuestion($answers, 239) }}</td>
                        <td class="Go-Patients-background">Recent Preeclampsia/eclampsia</td>
                        <td>{{ processQuestion($answers, 240) }}</td>
                    </tr>
                    <tr>
                        <td class="Go-Patients-background" style="width: 200px;">Past Preeclampsia/eclampsia</td>
                        <td colspan="3">{{ processQuestion($answers, 241) }}</td>
                    </tr>
                    <tr>
                        <td class="Go-Patients-background">Obstetric hemorrhage</td>
                        <td colspan="3">{{ processQuestion($answers, 242) }}</td>
                    </tr>
                    <tr>
                        <td class="Go-Patients-background">Organ failure</td>
                        <td>{{ processQuestion($answers, 243) }}</td>
                        <td class="Go-Patients-background">Specify</td>
                        <td>{{ processQuestion($answers, 244) }}</td>
                    </tr>
                    <tr>
                        <td class="Go-Patients-background">Past PRAKI</td>
                        <td>{{ processQuestion($answers, 245) }}</td>
                        <td class="Go-Patients-background">CS complications</td>
                        <td>{{ processQuestion($answers, 246) }}</td>
                    </tr>
                    <tr>
                        <td class="Go-Patients-background">Oliguria</td>
                        <td>{{ processQuestion($answers, 247) }}</td>
                        <td class="Go-Patients-background">Proteinuria</td>
                        <td>{{ processQuestion($answers, 249) }}</td>
                    </tr>
                    <tr>
                        <td class="Go-Patients-background">M_Outcome</td>
                        <td>{{ processQuestion($answers, 253) }}</td>
                        <td class="Go-Patients-background">F_Outcome</td>
                        <td>{{ processQuestion($answers, 254) }}</td>
                    </tr>
                    <tr>
                        <td class="Go-Patients-background">Neonatal ICU</td>
                        <td colspan="3">{{ processQuestion($answers, 255) }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!--  -->

    <!-- Discharge Recommendations Section -->
    @if(isset($recommendations) && $recommendations->count() > 0)
    <div class="row">
        <div class="col-md-12">
            <div class="section">
                <h2>Discharge Recommendations</h2>
                <table border="1" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th class="Discharge-Recommendations-background">Dose Name</th>
                            <th class="Discharge-Recommendations-background">Dose</th>
                            <th class="Discharge-Recommendations-background">Route</th>
                            <th class="Discharge-Recommendations-background">Frequency</th>
                            <th class="Discharge-Recommendations-background">Duration</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recommendations as $recommendation)
                        <tr>
                            <td>{{ $recommendation->dose_name }}</td>
                            <td>{{ $recommendation->dose }}</td>
                            <td>{{ $recommendation->route }}</td>
                            <td>{{ $recommendation->frequency }}</td>
                            <td>{{ $recommendation->duration }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

<!-- Files Section -->
<div class="row">
    <div class="col-md-12">
        <div class="section">
            <h2>Attached Files</h2>
            @foreach ([145, 146, 147, 148] as $questionId)
                @php
                    $files = processQuestion($answers, $questionId);
                    $questionName = $answers[$questionId]['question'] ?? 'File';
                @endphp
                
                @if($files)
                    <div class="file-group">
                        @foreach((array)$files as $fileUrl)
                            <div class="file-link">
                                <a href="{{ $fileUrl }}" target="_blank" title="{{ basename($fileUrl) }}">
                                    {{ $questionName }}
                                    @if(count((array)$files) > 1)
                                        (File {{ $loop->iteration }})
                                    @endif
                                </a>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endforeach
        </div>
    </div>
</div>

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
