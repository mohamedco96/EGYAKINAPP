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
    <div class="row">
        <div class="col-md-12">
            <div class="section">
                <h2>Patient Information</h2>
                @php
                    if (is_string($patient->answers)) {
                        $patient->answers = json_decode($patient->answers, true);
                    }
                @endphp

                @if(is_array($patient->answers) || is_object($patient->answers))
                    @foreach($patient->answers as $answer)
                        @if($answer['question_id'] === 1)
                            <p>Patient Name: <strong>{{ $answer['answer'] }}</strong></p>
                        @endif
                        @if($answer['question_id'] === 2)
                            <p>Hospital: <strong>{{ $answer['answer'] }}</strong></p>
                        @endif
                        @if($answer['question_id'] === 7)
                            <p>Age: <strong>{{ $answer['answer'] }}</strong></p>
                        @endif
                        @if($answer['question_id'] === 8)
                            <p>Gender: <strong>{{ $answer['answer'] }}</strong></p>
                        @endif
                    @endforeach
                @endif

                <p>Doctor: Dr.<strong>{{ $patient->doctor->name }}</strong></p>
            </div>
        </div>
    </div>

@php
    // Debugging the values to ensure they are correct
    // dd($patient->answers);
@endphp

<!-- Patient History Section -->
    <div class="row">
        <div class="col-md-12">
            <div class="section">
                <h2>Patient History</h2>
                @if(is_array($patient->answers) || is_object($patient->answers))
                    @foreach($patient->answers as $answer)
                        @if($answer['question_id'] === 1)
                            <p>Patient ID: <strong>{{ $patient->id }}</strong></p>
                            @php $patientName = $answer['answer']; @endphp
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
                    @endforeach
                @endif

                <p><strong>{{ $patientGender ?? 'Unknown' }}</strong> Patient Named <strong>{{ $patientName ?? 'Unknown' }}</strong> Aged <strong>{{ $patientAge ?? 'Unknown' }}</strong></p>
                <p>His special habit <strong>{{ $patientHabit ?? 'None' }}</strong> and <strong>{{ $patientHabitOther ?? '' }}</strong></p>
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
                    @foreach($questionData as $data)
                        @if($data['section_id'] === 2)
                            <p>Q{{ $data['id'] }}: {{ $data['question'] }}</p>
                            @if($data['type'] === 'multiple')
                                <p>Answer:
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
                                    <p>Other Field: <strong>{{ $data['answer']['other_field'] }}</strong></p>
                                @endif
                            @else
                                <p>Answer: <strong>{{ $data['answer'] }}</strong></p>
                            @endif
                        @endif
                    @endforeach
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
                            @if(!is_null($data['answer']) || isset($data['answer']['answers']) && is_array($data['answer']['answers']) && count($data['answer']['answers']) > 0)
                            <p>Q{{ $data['id'] }}: {{ $data['question'] }}</p>
                            @if($data['type'] === 'multiple')
                                <p>Answer:
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
                                    <p>Other Field: <strong>{{ $data['answer']['other_field'] }}</strong></p>
                                @endif
                            @else
                                <p>Answer: <strong>{{ $data['answer'] }}</strong></p>
                            @endif
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
