<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Report Summary</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .strong {
            color: #0d1116;
        }

        .section {
            margin-bottom: 30px;
            padding: 20px;
            border-radius: 10px;
            background-color: #f8f9fa; /* Light gray background */
            transition: background-color 0.3s;
        }

        .section:hover {
            background-color: #e9ecef; /* Darker gray background on hover */
        }

        .section h2 {
            font-size: 20px;
            margin-bottom: 10px;
            color: #6f42c1; /* Purple heading */
        }

        .section p {
            font-size: 16px;
            margin-bottom: 5px;
            color: #6c757d; /* Gray text */
        }

        .logo {
            max-width: 150px;
            margin-bottom: 20px;
            margin-right: 20px;
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

        .header img {
            position: absolute;
            top: 10px;
            right: 20px;
            width: 100px;
        }

        .footer {
            background-color: #6f42c1; /* Purple footer */
            color: white;
            padding: 10px;
            text-align: center;
            margin-top: 30px;
            border-radius: 5px;
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
    @php
        if (is_string($patient->answers)) {
            $patient->answers = json_decode($patient->answers, true);
        }

        $patientInfoAvailable = false;
        $patientInfoFields = [
            1 => 'Patient Name',
            2 => 'Hospital',
            7 => 'Age',
            8 => 'Gender'
        ];

        foreach ($patientInfoFields as $questionId => $label) {
            foreach ($patient->answers as $answer) {
                if ($answer['question_id'] === $questionId && !empty($answer['answer'])) {
                    $patientInfoAvailable = true;
                    break 2;
                }
            }
        }
    @endphp

    @if($patientInfoAvailable)
        <div class="row">
            <div class="col-md-12">
                <div class="section">
                    <h2>Patient Information</h2>
                    @if(is_array($patient->answers) || is_object($patient->answers))
                        @foreach($patient->answers as $answer)
                            @if($answer['question_id'] === 1 && !empty($answer['answer']))
                                <p>Patient Name: <strong>{{ is_array($answer['answer']) ? implode(', ', $answer['answer']) : $answer['answer'] }}</strong></p>
                            @endif
                            @if($answer['question_id'] === 2 && !empty($answer['answer']))
                                <p>Hospital: <strong>{{ is_array($answer['answer']) ? implode(', ', $answer['answer']) : $answer['answer'] }}</strong></p>
                            @endif
                            @if($answer['question_id'] === 7 && !empty($answer['answer']))
                                <p>Age: <strong>{{ is_array($answer['answer']) ? implode(', ', $answer['answer']) : $answer['answer'] }}</strong></p>
                            @endif
                            @if($answer['question_id'] === 8 && !empty($answer['answer']))
                                <p>Gender: <strong>{{ is_array($answer['answer']) ? implode(', ', $answer['answer']) : $answer['answer'] }}</strong></p>
                            @endif
                        @endforeach
                    @endif
                    <p>Doctor: Dr.<strong>{{ $patient->doctor->name }}</strong></p>
                </div>
            </div>
        </div>
    @endif

    @php
        // Debugging the values to ensure they are correct
        // dd($patient->answers);
    @endphp

<!-- Patient History Section -->
    @php
        $patientHistoryAvailable = false;
        $patientHistoryFields = [1, 8, 7, 14, 16, 18];

        foreach ($patientHistoryFields as $questionId) {
            foreach ($patient->answers as $answer) {
                if ($answer['question_id'] === $questionId && !empty($answer['answer'])) {
                    $patientHistoryAvailable = true;
                    break 2;
                }
            }
        }
    @endphp

    @if($patientHistoryAvailable)
        <div class="row">
            <div class="col-md-12">
                <div class="section">
                    <h2>Patient History</h2>
                    @if(is_array($patient->answers) || is_object($patient->answers))
                        @foreach($patient->answers as $answer)
                            @if($answer['question_id'] === 1 && !empty($answer['answer']))
                                <p>Patient ID: <strong>{{ $patient->id }}</strong></p>
                                @php $patientName = is_array($answer['answer']) ? implode(', ', $answer['answer']) : $answer['answer']; @endphp
                            @endif
                            @if($answer['question_id'] === 8 && !empty($answer['answer']))
                                @php $patientGender = is_array($answer['answer']) ? implode(', ', $answer['answer']) : $answer['answer']; @endphp
                            @endif
                            @if($answer['question_id'] === 7 && !empty($answer['answer']))
                                @php $patientAge = is_array($answer['answer']) ? implode(', ', $answer['answer']) : $answer['answer']; @endphp
                            @endif
                            @if($answer['question_id'] === 14 && !isset($answer['type']) && !empty($answer['answer']))
                                @php $patientHabit = is_array($answer['answer']) ? implode(', ', $answer['answer']) : $answer['answer']; @endphp
                            @endif
                            @if($answer['question_id'] === 14 && isset($answer['type']) && $answer['type'] === 'other' && !empty($answer['answer']))
                                @php $patientHabitOther = is_array($answer['answer']) ? implode(', ', $answer['answer']) : $answer['answer']; @endphp
                            @endif
                            @if($answer['question_id'] === 16 && !empty($answer['answer']))
                                @php $patientDM = is_array($answer['answer']) ? implode(', ', $answer['answer']) : $answer['answer']; @endphp
                            @endif
                            @if($answer['question_id'] === 18 && !empty($answer['answer']))
                                @php $patientHTN = is_array($answer['answer']) ? implode(', ', $answer['answer']) : $answer['answer']; @endphp
                            @endif
                        @endforeach
                    @endif

                    <p><strong>{{ $patientGender ?? 'Unknown' }}</strong> Patient Named <strong>{{ $patientName ?? 'Unknown' }}</strong> Aged <strong>{{ $patientAge ?? 'Unknown' }}</strong></p>
                    <p>His special habit <strong>{{ $patientHabit ?? 'None' }}</strong> and <strong>{{ $patientHabitOther ?? '' }}</strong></p>
                    <p>DM, <strong>{{ $patientDM ?? 'None' }}</strong></p>
                    <p>HTN, <strong>{{ $patientHTN ?? 'None' }}</strong></p>
                </div>
            </div>
        </div>
    @endif

<!-- Contact Information Section -->
    @php
        $contactInfoAvailable = false;
        $contactInfoFields = [5, 6];

        foreach ($contactInfoFields as $questionId) {
            foreach ($patient->answers as $answer) {
                if ($answer['question_id'] === $questionId && !empty($answer['answer'])) {
                    $contactInfoAvailable = true;
                    break 2;
                }
            }
        }
    @endphp

    @if($contactInfoAvailable)
        <div class="row">
            <div class="col-md-12">
                <div class="section">
                    <h2>Contact Information</h2>
                    @if(is_array($patient->answers) || is_object($patient->answers))
                        @foreach($patient->answers as $answer)
                            @if($answer['question_id'] === 5 && !empty($answer['answer']))
                                <p>Patient Contact Number: <strong>{{ is_array($answer['answer']) ? implode(', ', $answer['answer']) : $answer['answer'] }}</strong></p>
                            @endif
                            @if($answer['question_id'] === 6 && !empty($answer['answer']))
                                <p>Patient Email: <strong>{{ is_array($answer['answer']) ? implode(', ', $answer['answer']) : $answer['answer'] }}</strong></p>
                            @endif
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
@endif

<!-- Question-Based Data Sections -->
    <div class="row">
        <div class="col-md-12">
            <div class="section">
                @if(is_array($patient->answers) || is_object($patient->answers))
                    @foreach($questionData as $key => $data)
                        @php
                            $answersAvailable = false;
                            if(isset($data['answer'])) {
                                if(is_array($data['answer'])) {
                                    foreach($data['answer'] as $answer) {
                                        if(!empty($answer)) {
                                            $answersAvailable = true;
                                            break;
                                        }
                                    }
                                } elseif(!empty($data['answer'])) {
                                    $answersAvailable = true;
                                }
                            }
                        @endphp

                        @if($answersAvailable)
                            <h2>{{ $data['question'] }}</h2>
                            <p>
                                @if(isset($data['answer']))
                                    @if(is_array($data['answer']))
                                        @foreach($data['answer'] as $answer)
                                            @if(is_array($answer))
                                                @foreach($answer as $value)
                                                    <strong>{{ $value }}</strong>,
                                                @endforeach
                                            @else
                                                <strong>{{ $answer }}</strong>,
                                            @endif
                                        @endforeach
                                    @else
                                        <strong>{{ $data['answer'] }}</strong>
                                    @endif
                                @endif
                            </p>
                            @if(isset($data['answer']['other_field']))
                                <p>Other Field: <strong>{{ is_array($data['answer']['other_field']) ? implode(', ', $data['answer']['other_field']) : $data['answer']['other_field'] }}</strong></p>
                            @endif
                        @endif
                    @endforeach
                @endif
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>&copy; 2024 Patient Report Summary. All rights reserved.</p>
    </div>
</div>
</body>

</html>
