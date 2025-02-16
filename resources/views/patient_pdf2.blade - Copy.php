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
                @php
                    $patientInfo = $answers;
                    $patientName = $patientInfo->get(1)['answer'] ?? 'Unknown';
                    $hospital = $patientInfo->get(2)['answer'] ?? null;
                    $patientGender = $patientInfo->get(8)['answer'] ?? null;
                    $patientAge = $patientInfo->get(7)['answer'] ?? null;
                    $patientOccupation = $patientInfo->get(9)['answer'] ?? null;
                    $patientChildren = $patientInfo->get(142)['answer'] ?? null;

                    // Handle patientHabit as array or string
                    $patientHabit = isset($patientInfo->get(14)['type']) && $patientInfo->get(14)['type'] === 'other'
                        ? ''
                        : (is_array($patientInfo->get(14)['answer'])
                            ? implode(', ', $patientInfo->get(14)['answer'])
                            : ($patientInfo->get(14)['answer'] ?? 'None'));

                    $patientHabitOther = isset($patientInfo->get(14)['type']) && $patientInfo->get(14)['type'] === 'other'
                        ? $patientInfo->get(14)['answer']
                        : '';

                    $patientDM = $patientInfo->get(16)['answer'] ?? null;
                    $patientHTN = $patientInfo->get(18)['answer'] ?? null;
                    $governorate = $patientInfo->get(11)['answer'] ?? null;
                    $maritalStatus = $patientInfo->get(12)['answer'] ?? null;
                @endphp

                @php
                    $patientPhone = $answers[5]['answer'] ?? null;
                    $patientEmail = $answers[6]['answer'] ?? null;
                 @endphp
                <!-- Patient History Table -->
                <h2>Patient Information</h2>

                <table border="1" style="width: 100%; border-collapse: collapse;">
                    <thead>
                    </thead>
                    <tbody>
                    <tr>
                        <td class="Patient-Information-background">Patient Name</td>
                        <td>{{ $patientName }}</td>
                        <td class="Patient-Information-background">Patient ID</td>
                        <td>{{ $patient->id }}</td>
                    </tr>
                    <tr>
                        <td class="Patient-Information-background">Patient Phone</td>
                        <td>{{ $patientPhone }}</td>
                        <td class="Patient-Information-background">Patient Email</td>
                        <td>{{ $patientEmail }}</td>
                    </tr>
                    <tr>
                        <td class="Patient-Information-background">Department</td>
                        <td colspan="3">{{ $hospital }}</td>
                    </tr>
                    <tr>
                        <td class="Patient-Information-background">Age</td>
                        <td>{{ $patientAge }}</td>
                        <td class="Patient-Information-background">Gender</td>
                        {{ collect($questionData)->where('id', 8)->first()['answer']['answers'] ?? 'Not Provided' }}
                        </tr>
                    <tr>
                        <td class="Patient-Information-background">Occupation</td>
                        <td>{{ $patientOccupation }}</td>
                        <td class="Patient-Information-background">Governorate</td>
                        <td>{{ $governorate }}</td>
                    </tr>
                    <tr>
                        <td class="Patient-Information-background">Marital Status</td>
                        <td>{{ $maritalStatus }}</td>
                        <td class="Patient-Information-background">Children</td>
                        <td>{{ $patientChildren }}</td>
                    </tr>
                    <tr>
                        <td class="Patient-Information-background">Special Habits</td>
                        <td colspan="3">
                             @if(!empty($patientHabit))
                                {{ $patientHabit }}
                                    @if(!empty($patientHabitOther))
                                        {{ $patientHabitOther }}
                                    @endif
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="Patient-Information-background">DM</td>
                        <td>{{ $patientDM }}</td>
                        <td class="Patient-Information-background">HTN</td>
                        <td>{{ $patientHTN }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!--  -->

    <!-- Complaint Section -->
    <div class="row">
        <div class="col-md-12">
            <div class="section">
                 @php
                    $main_Complaint = $answers[24]['answer'] ?? null;
                    $urine_Output = $answers[162]['answer'] ?? null;
                    $provisional_Diagnosis = $answers[166]['answer'] ?? null;
                 @endphp
                <h2>Complaint</h2>
            <table border="1" style="width: 100%; border-collapse: collapse;">
                <tbody>
                <tr>
                    <td class="Complaint-background">Main Complaint</td>
                    <td colspan="3">{{ $main_Complaint }}</td>
                </tr>
                <tr>
                    <td class="Complaint-background">Urine Output</td>
                    <td colspan="3">{{ $urine_Output }}</td>
                </tr>
                <tr>
                    <td class="Complaint-background">Provisional Diagnosis</td>
                    <td colspan="3">{{ $provisional_Diagnosis }}</td>
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
                 @php
                    $Cause_Of_AKI = $answers[26]['answer'] ?? null;
                    $Pre_Renal_Causes = $answers[27]['answer'] ?? null;
                    $Intrinsic_Renal_Causes = $answers[29]['answer'] ?? null;
                    $Post_Renal_Causes = $answers[31]['answer'] ?? null;
                    $Other_Causes = $answers[33]['answer'] ?? null;
                 @endphp
                <h2>Cause of AKI</h2>
            <table border="1" style="width: 100%; border-collapse: collapse;">
                <tbody>
                <tr>
                    <td class="Cause-background">Cause Of AKI</td>
                    <td colspan="3">{{ $Cause_Of_AKI }}</td>
                </tr>
                <tr>
                    <td class="Cause-background">Pre-Renal Causes</td>
                    <td colspan="3">{{ $Pre_Renal_Causes }}</td>
                </tr>
                <tr>
                    <td class="Cause-background">Intrinsic Renal Causes</td>
                    <td colspan="3">{{ $Intrinsic_Renal_Causes }}</td>
                </tr>
                <tr>
                    <td class="Cause-background">Post_Renal Causes</td>
                    <td colspan="3">{{ $Post_Renal_Causes }}</td>
                </tr>
                <tr>
                    <td class="Cause-background">Other Causes</td>
                    <td colspan="3">{{ $Other_Causes }}</td>
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
                    @php
                        $History_OF_CKD = $answers[34]['answer'] ?? null;
                        $History_OF_AKI = $answers[35]['answer'] ?? null;
                        $History_OF_Cardiac_Failure = $answers[36]['answer'] ?? null;
                        $History_OF_LCF = $answers[37]['answer'] ?? null;
                        $History_OF_Sepsis = $answers[39]['answer'] ?? null;
                        $History_OF_Hypovolemia = $answers[43]['answer'] ?? null;
                        $History_OF_Malignancy = $answers[44]['answer'] ?? null;
                        $History_OF_Trauma = $answers[45]['answer'] ?? null;
                        $History_OF_Autoimmune_Disease  = $answers[46]['answer'] ?? null;
                        $History_of_neurological_impairment_or_disability = $answers[38]['answer'] ?? null;
                        $Recent_use_of_iodinated_contrast_media = $answers[40]['answer'] ?? null;
                        $Current_or_recent_use_of_drugs_with_potential_nephrotoxicity = $answers[41]['answer'] ?? null;
                        $Other_risk_factors = $answers[47]['answer'] ?? null;
                    @endphp
                    <h2>Risk Factors for AKI</h2>
                <table border="1" style="width: 100%; border-collapse: collapse;">
                    <tbody>
                    <tr>
                        <td class="Risk-background">History OF CKD</td>
                        <td>{{ $History_OF_CKD }}</td>
                        <td class="Risk-background">History OF AKI</td>
                        <td>{{ $History_OF_AKI }}</td>
                    </tr>
                    <tr>
                        <td class="Risk-background">History OF Cardiac Failure</td>
                        <td>{{ $History_OF_Cardiac_Failure }}</td>
                        <td class="Risk-background">History OF LCF</td>
                        <td>{{ $History_OF_LCF }}</td>
                    </tr>
                    <tr>
                        <td class="Risk-background">History OF Sepsis</td>
                        <td>{{ $History_OF_Sepsis }}</td>
                        <td class="Risk-background">History OF Hypovolemia</td>
                        <td>{{ $History_OF_Hypovolemia }}</td>
                    </tr>
                    <tr>
                        <td class="Risk-background">History OF Malignancy</td>
                        <td>{{ $History_OF_Malignancy }}</td>
                        <td class="Risk-background">History OF Trauma</td>
                        <td>{{ $History_OF_Trauma }}</td>
                    </tr>
                    <tr>
                        <td class="Risk-background">History OF Autoimmune Disease</td>
                        <td colspan="3">{{ $History_OF_Autoimmune_Disease }}</td>
                    </tr>
                    <tr>
                        <td class="Risk-background">History of neurological impairment or disability</td>
                        <td colspan="3">{{ $History_of_neurological_impairment_or_disability }}</td>
                    </tr>
                    <tr>
                        <td class="Risk-background">Recent use of iodinated contrast media</td>
                        <td colspan="3">{{ $Recent_use_of_iodinated_contrast_media }}</td>
                    </tr>
                    <tr>
                        <td class="Risk-background">Current or recent use of drugs with potential nephrotoxicity</td>
                        <td colspan="3">{{ $Current_or_recent_use_of_drugs_with_potential_nephrotoxicity }}</td>
                    </tr>
                    <tr>
                        <td class="Risk-background">Other risk factors</td>
                        <td colspan="3">{{ $Other_risk_factors }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!--  -->


    <!-- Assessment of patient Section -->
    <div class="row">
        <div class="col-md-12">
            <div class="section">
                    @php
                        $History_OF_CKD = $answers[34]['answer'] ?? null;
                        $History_OF_AKI = $answers[35]['answer'] ?? null;
                        $History_OF_Cardiac_Failure = $answers[36]['answer'] ?? null;
                        $History_OF_LCF = $answers[37]['answer'] ?? null;
                        $History_OF_Sepsis = $answers[39]['answer'] ?? null;
                        $History_OF_Hypovolemia = $answers[43]['answer'] ?? null;
                        $History_OF_Malignancy = $answers[44]['answer'] ?? null;
                        $History_OF_Trauma = $answers[45]['answer'] ?? null;
                        $History_OF_Autoimmune_Disease  = $answers[46]['answer'] ?? null;
                        $History_of_neurological_impairment_or_disability = $answers[38]['answer'] ?? null;
                        $Recent_use_of_iodinated_contrast_media = $answers[40]['answer'] ?? null;
                        $Current_or_recent_use_of_drugs_with_potential_nephrotoxicity = $answers[41]['answer'] ?? null;
                        $Other_risk_factors = $answers[47]['answer'] ?? null;
                    @endphp
                    <h2>Assessment of patient</h2>
                <table border="1" style="width: 100%; border-collapse: collapse;">
                    <tbody>
                    <tr>
                        <td class="Assessment-background">Heart rate/minute</td>
                        <td>{{$answers[48]['answer'] ?? null}}</td>
                        <td class="Assessment-background">Respiratory rate/minute</td>
                        <td>{{$answers[49]['answer'] ?? null}}</td>
                        <td class="Assessment-background">SBP</td>
                        <td>{{$answers[50]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="Assessment-background">DBP</td>
                        <td>{{$answers[51]['answer'] ?? null}}</td>
                        <td class="Assessment-background">GCS</td>
                        <td>{{$answers[52]['answer'] ?? null}}</td>
                        <td class="Assessment-background">Temperature</td>
                        <td>{{$answers[54]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="Assessment-background">Oxygen saturation (%)</td>
                        <td>{{$answers[53]['answer'] ?? null}}</td>
                        <td class="Assessment-background">UOP (ml/hour)</td>
                        <td>{{$answers[55]['answer'] ?? null}}</td>
                        <td class="Assessment-background">AVP</td>
                        <td>{{$answers[56]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="Assessment-background">Height/cm</td>
                        <td>{{$answers[140]['answer'] ?? null}}</td>
                        <td class="Assessment-background">Weight/cm</td>
                        <td colspan="3">{{$answers[141]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="Assessment-background">Abdominal Examination</td>
                        <td colspan="5">{{$answers[68]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="Assessment-background">Skin examination</td>
                        <td colspan="5">{{$answers[57]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="Assessment-background">Eye examination</td>
                        <td colspan="5">{{$answers[59]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="Assessment-background">Ear examination</td>
                        <td colspan="5">{{$answers[61]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="Assessment-background">Cardiac examination</td>
                        <td colspan="5">{{$answers[63]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="Assessment-background">Internal jugular vein</td>
                        <td colspan="5">{{$answers[65]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="Assessment-background">Chest examination</td>
                        <td colspan="5">{{$answers[66]['answer'] ?? null}}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!--  -->


    <!-- Medical Decision Section -->
    <div class="row">
        <div class="col-md-12">
            <div class="section">
                    <h2>Medical Decision</h2>
                <table border="1" style="width: 100%; border-collapse: collapse;">
                    <tbody>
                    <tr>
                        <td class="Medical-background">Medical decision</td>
                        <td>{{$answers[77]['answer'] ?? null}}</td>
                        <td class="Medical-background">Dialysis</td>
                        <td>{{$answers[86]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="Medical-background">Dialysis Modality</td>
                        <td colspan="3">{{$answers[87]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="Medical-background">Dialysis indication</td>
                        <td colspan="3">{{$answers[88]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="Medical-background">Number of sessions</td>
                        <td colspan="3">{{$answers[89]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="Medical-background">Vascular Access</td>
                        <td colspan="3">{{$answers[90]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="Medical-background">Site of Access</td>
                        <td colspan="3">{{$answers[232]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="Medical-background">Lines of management</td>
                        <td colspan="3">{{$answers[91]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="Medical-background">Immunosuppressive types</td>
                        <td colspan="3">{{$answers[233]['answer'] ?? null}}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!--  -->


    <!-- Outcome Section -->
    <div class="row">
        <div class="col-md-12">
            <div class="section">
                    @php
                        $Outcome_of_the_patient = $answers[79]['answer'] ?? 'Unknown';
                    @endphp
                    <h2>Outcome</h2>
                    <p>Outcome of the patient is <strong>{{ $Outcome_of_the_patient }}</strong></p>
            </div>
        </div>
    </div>
    <!--  -->


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
                            <td class="center-text"><strong>{{ $answers[$ids[0]]['answer'] ?? '-' }}</strong></td>
                            <td class="center-text"><strong>{{ $ids[1] ? $answers[$ids[1]]['answer'] ?? '-' : '' }}</strong></td>
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
                        <td>{{$answers[73]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="Radiology-background">If renal us is abnormal</td>
                        <td>{{$answers[74]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="Radiology-background">CT abdomen summary</td>
                        <td>{{$answers[260]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="Radiology-background">CT chest summary</td>
                        <td>{{$answers[261]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="Radiology-background">ECHO report Summary</td>
                        <td>{{$answers[262]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="Radiology-background">Renal Biopsy</td>
                        <td>{{$answers[252]['answer'] ?? null}}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!--  -->


    <!-- CTS-patient Section -->
    <div class="row">
        <div class="col-md-12">
            <div class="section">
                    <h2>CTS-patient</h2>
                <table border="1" style="width: 100%; border-collapse: collapse;">
                    <tbody>
                    <tr>
                        <td class="CTS-patient-background">Type of surgery</td>
                        <td>{{$answers[171]['answer'] ?? null}}</td>
                        <td class="CTS-patient-background">Type of cardiac disease</td>
                        <td>{{$answers[174]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="CTS-patient-background">Preoperative SBP</td>
                        <td>{{$answers[176]['answer'] ?? null}}</td>
                        <td class="CTS-patient-background">Preoperative DBP</td>
                        <td>{{$answers[177]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="CTS-patient-background">Preoperative WBCs</td>
                        <td>{{$answers[178]['answer'] ?? null}}</td>
                        <td class="CTS-patient-background">Preoperative HB</td>
                        <td>{{$answers[179]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="CTS-patient-background">Preoperative Platelets</td>
                        <td colspan="3">{{$answers[180]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="CTS-patient-background">Preoperative creatinine</td>
                        <td>{{$answers[181]['answer'] ?? null}}</td>
                        <td class="CTS-patient-background">Preoperative urine pus cells</td>
                        <td>{{$answers[182]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="CTS-patient-background">Preoperative urine RBCs</td>
                        <td>{{$answers[183]['answer'] ?? null}}</td>
                        <td class="CTS-patient-background">Preoperative proteinuria</td>
                        <td>{{$answers[186]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="CTS-patient-background">Preoperative urine cast</td>
                        <td>{{$answers[184]['answer'] ?? null}}</td>
                        <td class="CTS-patient-background">Preoperative INR</td>
                        <td>{{$answers[186]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="CTS-patient-background">Preoperative albumin</td>
                        <td>{{$answers[187]['answer'] ?? null}}</td>
                        <td class="CTS-patient-background">Preoperative bilirubin</td>
                        <td>{{$answers[188]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="CTS-patient-background">Preoperative ALT</td>
                        <td>{{$answers[189]['answer'] ?? null}}</td>
                        <td class="CTS-patient-background">Preoperative AST</td>
                        <td>{{$answers[190]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="CTS-patient-background">Preoperative Troponin</td>
                        <td>{{$answers[191]['answer'] ?? null}}</td>
                        <td class="CTS-patient-background">Preoperative pH</td>
                        <td>{{$answers[208]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="CTS-patient-background">Preoperative Hco3</td>
                        <td>{{$answers[209]['answer'] ?? null}}</td>
                        <td class="CTS-patient-background">Preoperative pCo2</td>
                        <td>{{$answers[210]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="CTS-patient-background">Postoperative pH</td>
                        <td>{{$answers[212]['answer'] ?? null}}</td>
                        <td class="CTS-patient-background">Postoperative Hco3</td>
                        <td>{{$answers[211]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="CTS-patient-background">Postoperative pCo2</td>
                        <td>{{$answers[213]['answer'] ?? null}}</td>
                        <td class="CTS-patient-background">Postoperative SBP</td>
                        <td>{{$answers[214]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="CTS-patient-background">Postoperative DBP</td>
                        <td colspan="3">{{$answers[215]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="CTS-patient-background">Preoperative ejection Fraction</td>
                        <td colspan="3">{{$answers[192]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="CTS-patient-background">Preoperative ECHO</td>
                        <td colspan="3">{{$answers[193]['answer'] ?? null}}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!--  -->


    <!-- Operative details Section -->
    <div class="row">
        <div class="col-md-12">
            <div class="section">
                <h2>Operative details</h2>
                <table border="1" style="width: 100%; border-collapse: collapse;">
                    <tbody>
                    <tr>
                        <td class="Operative-details-background">CPB duration/minutes</td>
                        <td>{{$answers[194]['answer'] ?? null}}</td>
                        <td class="Operative-details-background">Cross clamping times/minutes</td>
                        <td>{{$answers[195]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="Operative-details-background">Core temperature/c/lowest</td>
                        <td>{{$answers[196]['answer'] ?? null}}</td>
                        <td class="Operative-details-background">Core temperature/c/highest</td>
                        <td>{{$answers[224]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="Operative-details-background">Serum lactate during surgery</td>
                        <td>{{$answers[197]['answer'] ?? null}}</td>
                        <td class="Operative-details-background">Abnormal Event</td>
                        <td>{{$answers[199]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="Operative-details-background">Type of cardioplegia -1</td>
                        <td>{{$answers[201]['answer'] ?? null}}</td>
                        <td class="Operative-details-background">Type of cardioplegia -2</td>
                        <td>{{$answers[202]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="Operative-details-background">Type of cardioplegia -3</td>
                        <td>{{$answers[203]['answer'] ?? null}}</td>
                        <td class="Operative-details-background">Type of cardioplegia -4</td>
                        <td>{{$answers[204]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="Operative-details-background">Blood transfusion</td>
                        <td>{{$answers[225]['answer'] ?? null}}</td>
                        <td class="Operative-details-background">Blood transfusion type</td>
                        <td>{{$answers[226]['answer'] ?? null}}</td>
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
                        <td>{{$answers[234]['answer'] ?? null}}</td>
                        <td class="Go-Patients-background">Gravidity number</td>
                        <td>{{$answers[235]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="Go-Patients-background">Parity number</td>
                        <td colspan="3">{{$answers[236]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="Go-Patients-background">First presentation</td>
                        <td>{{$answers[237]['answer'] ?? null}}</td>
                        <td class="Go-Patients-background">Place of medical care</td>
                        <td>{{$answers[238]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="Go-Patients-background">Antenatal care</td>
                        <td>{{$answers[239]['answer'] ?? null}}</td>
                        <td class="Go-Patients-background">Recent Preeclampsia/eclampsia</td>
                        <td>{{$answers[240]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="Go-Patients-background" style="width: 200px;">Past Preeclampsia/eclampsia</td>
                        <td colspan="3">{{$answers[241]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="Go-Patients-background">Obstetric hemorrhage</td>
                        <td colspan="3">{{$answers[242]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="Go-Patients-background">Organ failure</td>
                        <td>{{$answers[243]['answer'] ?? null}}</td>
                        <td class="Go-Patients-background">Specify</td>
                        <td>{{$answers[244]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="Go-Patients-background">Past PRAKI</td>
                        <td>{{$answers[245]['answer'] ?? null}}</td>
                        <td class="Go-Patients-background">CS complications</td>
                        <td>{{$answers[246]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="Go-Patients-background">Oliguria</td>
                        <td>{{$answers[247]['answer'] ?? null}}</td>
                        <td class="Go-Patients-background">Proteinuria</td>
                        <td>{{$answers[249]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="Go-Patients-background">M_Outcome</td>
                        <td>{{$answers[253]['answer'] ?? null}}</td>
                        <td class="Go-Patients-background">F_Outcome</td>
                        <td>{{$answers[254]['answer'] ?? null}}</td>
                    </tr>
                    <tr>
                        <td class="Go-Patients-background">Neonatal ICU</td>
                        <td colspan="3">{{$answers[255]['answer'] ?? null}}</td>
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
