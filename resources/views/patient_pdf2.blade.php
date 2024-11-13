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
                <h2>Patient Information</h2>
                @php
                    $patientInfo = $answers;
                    $patientName = $patientInfo->get(1)['answer'] ?? 'Unknown';
                    $hospital = $patientInfo->get(2)['answer'] ?? 'Unknown';
                    $patientGender = $patientInfo->get(8)['answer'] ?? 'Unknown';
                    $patientAge = $patientInfo->get(7)['answer'] ?? 'Unknown';
                    
                    // Handle patientHabit as array or string
                    $patientHabit = isset($patientInfo->get(14)['type']) && $patientInfo->get(14)['type'] === 'other' 
                        ? '' 
                        : (is_array($patientInfo->get(14)['answer']) 
                            ? implode(', ', $patientInfo->get(14)['answer']) 
                            : ($patientInfo->get(14)['answer'] ?? 'None'));
                    
                    $patientHabitOther = isset($patientInfo->get(14)['type']) && $patientInfo->get(14)['type'] === 'other' 
                        ? $patientInfo->get(14)['answer'] 
                        : '';
                    
                    $patientDM = $patientInfo->get(16)['answer'] ?? 'None';
                    $patientHTN = $patientInfo->get(18)['answer'] ?? 'None';
                    $governorate = $patientInfo->get(11)['answer'] ?? 'Unknown';
                    $maritalStatus = $patientInfo->get(12)['answer'] ?? 'Unknown';
                @endphp

                <p>
                    <strong>{{ $patientGender }}</strong> Patient, 
                    with ID <strong>{{ $patient->id }}</strong>, Named
                    <strong>{{ $patientName }}</strong>, Aged
                    <strong>{{ $patientAge }}</strong>, From
                    <strong>{{ $governorate }}</strong>,
                    <strong>{{ $maritalStatus }}</strong>,
                    <strong>{{ $hospital }}</strong> Hospital,
                    @if(!empty($patientHabit) || !empty($patientHabitOther))
                        His special habit <strong>{{ $patientHabit }}</strong>
                        @if(!empty($patientHabitOther)), <strong>{{ $patientHabitOther }}</strong>@endif,
                    @endif
                    DM <strong>{{ $patientDM }}</strong>, HTN <strong>{{ $patientHTN }}</strong>
                </p>
            </div>
        </div>
    </div>
    <!--  -->
    
    @php
        $patientPhone = $answers[5]['answer'] ?? null;
        $patientEmail = $answers[6]['answer'] ?? null;
    @endphp

    <!-- Contact Information Section -->
    @if($patientPhone || $patientEmail)
        <div class="row">
            <div class="col-md-12">
                <div class="section">
                    <h2>Contact Information</h2>
                    @if($patientPhone) <p>Phone: <strong>{{ $patientPhone }}</strong></p> @endif
                    @if($patientEmail) <p>Email: <strong>{{ $patientEmail }}</strong></p> @endif
                </div>
            </div>
        </div>
    @endif

<!-- Dynamic Section Data -->
@php $URLprefix = "https://api.egyakin.com/storage/"; @endphp <!-- Define the URL prefix for file links -->

@foreach($sections_infos as $sections_info)
    @if($sections_info->id != 1 && $sections_info->id != 6 && $sections_info->id != 8) <!-- Skip sections with id 1, 6, and 8 initially -->
        <div class="row">
            <div class="col-md-12">
                <div class="section">
                    <h2>{{ $sections_info->section_name }}</h2>
                    @php $hasAnsweredQuestion = false; @endphp

                    @foreach($questionData as $data)
                        @if(
                            $data['section_id'] === $sections_info->id &&
                            !is_null($data['answer']) &&
                            (
                                (is_array($data['answer']['answers'] ?? null) && count($data['answer']['answers']) > 0) || 
                                !isset($data['answer']['answers'])
                            )
                        )
                            @php $hasAnsweredQuestion = true; @endphp
                            
                            <p><strong>Q:</strong> {{ $data['question'] }}</p>
                            
                            @if($data['type'] === 'multiple')
                                <p><strong>A:</strong>
                                    @if(is_array($data['answer']['answers']))
                                        @foreach($data['answer']['answers'] as $answer)
                                            @if(is_array($answer))
                                                @foreach($answer as $value)
                                                    <strong>{{ $value }}</strong>,
                                                @endforeach
                                            @else
                                                <strong>{{ $answer }}</strong>,
                                            @endif
                                        @endforeach
                                    @endif
                                </p>
                                @if(isset($data['answer']['other_field']))
                                    <p>Others: <strong>{{ $data['answer']['other_field'] }}</strong></p>
                                @endif

                            @elseif($data['type'] === 'files')
                                <!-- Decode the JSON-encoded string to get the array of file paths -->
                                @php
                                    $filePaths = json_decode($data['answer'], true);
                                @endphp
                                @if(is_array($filePaths))
                                    @foreach($filePaths as $filePath)
                                        <p>
                                            <strong><a href="{{ $URLprefix . $filePath }}" target="_blank">{{ $URLprefix . $filePath }}</a></strong>
                                        </p>
                                    @endforeach
                                @endif

                            @else
                                <p><strong>A:</strong> <strong>{{ $data['answer'] }}</strong></p>
                            @endif
                        @endif
                    @endforeach

                    <!-- Show "No information available" only if no questions with answers exist in this section -->
                    @if(!$hasAnsweredQuestion)
                        <p>No information available.</p>
                    @endif
                </div>
            </div>
        </div>
    @endif
@endforeach

<!-- Section id 8 should be displayed last -->
@php
    $section8 = $sections_infos->firstWhere('id', 8);
@endphp

@if($section8)
    <div class="row">
        <div class="col-md-12">
            <div class="section">
                <h2>{{ $section8->section_name }}</h2>
                @php $hasAnsweredQuestion = false; @endphp

                @foreach($questionData as $data)
                    @if(
                        $data['section_id'] === $section8->id &&
                        !is_null($data['answer']) &&
                        (
                            (is_array($data['answer']['answers'] ?? null) && count($data['answer']['answers']) > 0) || 
                            !isset($data['answer']['answers'])
                        )
                    )
                        @php $hasAnsweredQuestion = true; @endphp
                        
                        <p><strong>Q:</strong> {{ $data['question'] }}</p>
                        
                        @if($data['type'] === 'multiple')
                            <p><strong>A:</strong>
                                @if(is_array($data['answer']['answers']))
                                    @foreach($data['answer']['answers'] as $answer)
                                        @if(is_array($answer))
                                            @foreach($answer as $value)
                                                <strong>{{ $value }}</strong>,
                                            @endforeach
                                        @else
                                            <strong>{{ $answer }}</strong>,
                                        @endif
                                    @endforeach
                                @endif
                            </p>
                            @if(isset($data['answer']['other_field']))
                                <p>Others: <strong>{{ $data['answer']['other_field'] }}</strong></p>
                            @endif

                        @elseif($data['type'] === 'files')
                            <!-- Decode the JSON-encoded string to get the array of file paths -->
                            @php
                                $filePaths = json_decode($data['answer'], true);
                            @endphp
                            @if(is_array($filePaths))
                                @foreach($filePaths as $filePath)
                                    <p>
                                        <strong><a href="{{ $URLprefix . $filePath }}" target="_blank">{{ $URLprefix . $filePath }}</a></strong>
                                    </p>
                                @endforeach
                            @endif

                        @else
                            <p><strong>A:</strong> <strong>{{ $data['answer'] }}</strong></p>
                        @endif
                    @endif
                @endforeach

                <!-- Show "No information available" only if no questions with answers exist in this section -->
                @if(!$hasAnsweredQuestion)
                    <p>No information available.</p>
                @endif
            </div>
        </div>
    </div>
@endif




    <!-- Laboratory Section -->
    <div class="row">
        <div class="col-md-12">
            <div class="section">
                <table>
                    <thead>
                    <tr>
                        <th>Laboratory Parameters</th>
                        <th>On admission</th>
                        <th>On discharge</th>
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
                            <td>{{ $parameter }}</td>
                            <td class="center-text"><strong>{{ $answers[$ids[0]]['answer'] ?? '-' }}</strong></td>
                            <td class="center-text"><strong>{{ $ids[1] ? $answers[$ids[1]]['answer'] ?? '-' : '' }}</strong></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

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
