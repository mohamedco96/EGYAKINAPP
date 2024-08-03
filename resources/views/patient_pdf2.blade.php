<?php
// Increase maximum execution time
set_time_limit(120);

// Example data processing optimization
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
        /* Watermark */
        body {
            {{--background-image: url('{{ asset('images/logo.png') }}'); /* Path to the image in the public directory */--}}
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
    <div class="header">
        <!--<img src="https://via.placeholder.com/150" alt="Logo" class="logo">-->
        <h1>Patient Report Summary</h1>
    </div>

    <!-- General Application Data Section -->
    <div class="row">
        <div class="col-md-12">
            <div class="section">
                <h2>EGYAKIN</h2>
                <p>Report is generated for Dr.<strong>{{ $patient->doctor->name }}</strong></p>
            </div>
        </div>
    </div>

<!-- Patient Information Section -->
    <div class="row">
        <div class="col-md-12">
            <div class="section">
                <h2>Patient Information</h2>
                @if(is_array($patient->answers) || is_object($patient->answers))
                    <strong>Test 1</strong>
                    @foreach($patient->answers as $answer)
                        <strong>Test 2</strong>
                        @if($answer['question_id'] === 1)
                            <p>Patient ID: <strong>{{ $patient->id }}</strong></p>
                            @php $patientName = $answer['answer']; @endphp
                        @endif
                            @if($answer['question_id'] === 2)
                                @php $hospital = $answer['answer']; @endphp
                            @endif
                        @if($answer['question_id'] === 8)
                            @php $patientGender = $answer['answer']; @endphp
                        @endif
                        @if($answer['question_id'] === 7)
                            @php $patientAge = $answer['answer']; @endphp
                        @endif
                        @if($answer['question_id'] === 14 && !isset($answer['type']))
                            @php $patientHabit = is_array($answer['answer']) ? implode(', ', $answer['answer']) : $answer['answer']; @endphp
                        @endif
                        @if($answer['question_id'] === 14 && isset($answer['type']) && $answer['type'] === 'other')
                            @php $patientHabitOther = $answer['answer']; @endphp
                        @endif
                        @if($answer['question_id'] === 16)
                            @php $patientDM = $answer['answer']; @endphp
                        @endif
                        @if($answer['question_id'] === 18)
                            @php $patientHTN = $answer['answer']; @endphp
                        @endif
                        @if($answer['question_id'] === 11)
                            @php $governorate = $answer['answer']; @endphp
                        @endif
                        @if($answer['question_id'] === 12)
                            @php $maritalStatus = $answer['answer']; @endphp
                        @endif
                    @endforeach
                @endif

                <p>
                    <strong>{{ $patientGender ?? 'Unknown' }}</strong> Patient Named
                    <strong>{{ $patientName ?? 'Unknown' }}</strong> Aged
                    <strong>{{ $patientAge ?? 'Unknown' }}</strong> From
                    <strong>{{ $governorate ?? 'Unknown' }}</strong> --
                    <strong>{{ $maritalStatus ?? 'Unknown' }}</strong>
                </p>
                <p>Hospital: <strong>{{ $hospital ?? 'Unknown' }}</strong></p>
                <p>His special habit <strong>{{ $patientHabit ?? 'None' }}</strong> and
                    <strong>{{ $patientHabitOther ?? '' }}</strong></p>
                <p>DM, <strong>{{ $patientDM ?? 'None' }}</strong></p>
                <p>HTN, <strong>{{ $patientHTN ?? 'None' }}</strong></p>
            </div>
        </div>
    </div>

    <!-- Contact Information Section -->
    <div class="row">
        <div class="col-md-12">
            <div class="section">
                <h2>Contact Information</h2>
                @if(is_array($patient->answers) || is_object($patient->answers))
                    @foreach($patient->answers as $answer)
                        @if($answer['question_id'] === 5)
                            @php $patientPhone = $answer['answer']; @endphp
                        @endif
                        @if($answer['question_id'] === 6)
                            @php $patientEmail = $answer['answer']; @endphp
                        @endif
                    @endforeach
                @endif
                <p>Phone: <strong>{{ $patientPhone ?? 'None' }}</strong></p>
                <p>Email: <strong>{{ $patientEmail ?? 'None' }}</strong></p>
            </div>
        </div>
    </div>

    <!-- Complaint Section -->
    <div class="row">
        <div class="col-md-12">
            <div class="section">
                <h2>Complaint</h2>
                @if(is_array($questionData) || is_object($questionData))
                    @php $displaySection = false; @endphp
                    @foreach($questionData as $data)
                        @if($data['section_id'] === 2 && $data['id'] === 24)
                            @if(!is_null($data['answer']))
                                @php $displaySection = true; @endphp
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
                                @else
                                    <p><strong>A:</strong> <strong>{{ $data['answer'] }}</strong></p>
                                @endif
                            @endif
                        @endif
                    @endforeach
                    @if(!$displaySection)
                        <p>No information available.</p>
                    @endif
                @endif
            </div>
        </div>
    </div>

    <!-- Cause of AKI Section -->
    <div class="row">
        <div class="col-md-12">
            <div class="section">
                <h2>Cause of AKI</h2>
                @if(is_array($questionData) || is_object($questionData))
                    @foreach($questionData as $data)
                        @php
                            // Debugging the values to ensure they are correct
                        //dd($data);
                        @endphp
                        @if($data['section_id'] === 3)
                            @if(!is_null($data['answer']))
                                @php $displaySection = true; @endphp
                                <p> <strong>Q:</strong> {{ $data['question'] }}</p>
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
                                @else
                                    <p><strong>A:</strong> <strong>{{ $data['answer'] }}</strong></p>
                                @endif
                            @endif
                        @endif
                    @endforeach
                    @if(!$displaySection)
                        <p>No information available.</p>
                    @endif
                @endif
            </div>
        </div>
    </div>

    <!-- Risk factors for AKI Section -->
    <div class="row">
        <div class="col-md-12">
            <div class="section">
                <h2>Risk factors for AKI</h2>
                @if(is_array($questionData) || is_object($questionData))
                    @foreach($questionData as $data)
                        @php
                            // Debugging the values to ensure they are correct
                        //dd($data);
                        @endphp
                        @if($data['section_id'] === 4)
                            @php $displaySection = false; @endphp
                            @if(!is_null($data['answer']))
                                @php $displaySection = true; @endphp
                                <p> <strong>Q:</strong> {{ $data['question'] }}</p>
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
                                @else
                                    <p><strong>A:</strong> <strong>{{ $data['answer'] }}</strong></p>
                                @endif
                            @endif
                        @endif
                    @endforeach
                    @if(!$displaySection)
                        <p>No information available.</p>
                    @endif
                @endif
            </div>
        </div>
    </div>

    <!-- Medical decision Section -->
    <div class="row">
        <div class="col-md-12">
            <div class="section">
                <h2>Medical decision</h2>
                @if(is_array($questionData) || is_object($questionData))
                    @foreach($questionData as $data)
                        @php
                            // Debugging the values to ensure they are correct
                        //dd($data);
                        @endphp
                        @if($data['section_id'] === 7)
                            @php $displaySection = false; @endphp
                            @if(!is_null($data['answer']))
                                @php $displaySection = true; @endphp
                                <p> <strong>Q:</strong> {{ $data['question'] }}</p>
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
                                @else
                                    <p><strong>A:</strong> <strong>{{ $data['answer'] }}</strong></p>
                                @endif
                            @endif
                        @endif
                    @endforeach
                    @if(!$displaySection)
                        <p>No information available.</p>
                    @endif
                @endif
            </div>
        </div>
    </div>

    <!-- Outcome Section -->
    <div class="row">
        <div class="col-md-12">
            <div class="section">
                <h2>Outcome</h2>
                @if(is_array($questionData) || is_object($questionData))
                    @php $foundQuestion = false; @endphp
                    @foreach($questionData as $data)
                        @if($data['section_id'] === 8 && $data['id'] === 79)
                            @if(!is_null($data['answer']))
                                @php $foundQuestion = true; @endphp
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
                                @else
                                    <p><strong>A:</strong> <strong>{{ $data['answer'] }}</strong></p>
                                @endif
                            @endif
                        @endif
                    @endforeach
                    @if(!$foundQuestion)
                        <p>No information available.</p>
                    @endif
                @endif
            </div>
        </div>
    </div>


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
        <p>&copy; 2024 Patient Report Summary. All rights reserved.</p>
    </div>
</div>
<!-- Bootstrap JS (optional) -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>
