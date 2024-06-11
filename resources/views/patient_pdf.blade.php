<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Report Summary</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
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
                <p>Report is generted for Dr.{{ $patient->doctor->name }}</p>
            </div>
        </div>
    </div>

    <!-- Patient Information Section -->
    <div class="row">
        <div class="col-md-12">
            <div class="section">
                <h2>Patient Information</h2>
                @foreach($patient->answers as $answer)
                    @if($answer->question_id === 1)
                        <p>Patient Name: {{ $answer->answer }}</p>
                    @endif
                    @if($answer->question_id === 2)
                        <p>Hospital: {{ $answer->answer }}</p>
                    @endif
                    @if($answer->question_id === 7)
                        <p>Age: {{ $answer->answer }}</p>
                    @endif
                    @if($answer->question_id === 8)
                        <p>Gender: {{ $answer->answer }}</p>
                    @endif
                @endforeach
                <p>Doctor: Dr.{{ $patient->doctor->name }}</p>
            </div>
        </div>
    </div>


    <div class="row">
        <div class="col-md-12">
            <div class="section">
                <h2>Patient History</h2>
                @php
                    $question8Answer = '';
                    $question1Answer = '';
                    $question7Answer = '';
                @endphp
                @foreach($patient->answers as $answer)
                    @if($answer->question_id === 1)
                        <p>Patient ID: {{ $patient->id }}</p>
                    @endif
                    @if($answer->question_id === 8)
                        {{ $answer->answer }} Patient &nbsp;
                    @endif
                    @if($answer->question_id === 1)
                        Named {{ $answer->answer }} &nbsp;
                    @endif
                    @if($answer->question_id === 7)
                        Aged {{ $answer->answer }}&nbsp;
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="section">
                <h2>Complaint</h2>
                @foreach($questionData as $data)
                    @if($data['section_id'] === 2)
                        <p>Q{{ $data['id'] }}: {{ $data['question'] }}</p>
                        @if($data['type'] === 'multiple')
                            <p>Answer:</p>
                            <ul>
                                @foreach($data['answer']['answers'] as $answer)
                                    <li>
                                        @foreach($answer as $value)
                                            {{ $value }},
                                        @endforeach
                                    </li>
                                @endforeach
                            </ul>
                            @if($data['answer']['other_field'])
                                <p>Other Field: {{ $data['answer']['other_field'] }}</p>
                            @endif
                        @else
                            <p>Answer: {{ $data['answer'] }}</p>
                        @endif
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="section">
                <h2>Cause of AKI</h2>
                @foreach($questionData as $data)
                    @if($data['section_id'] === 3)
                        <p>Q{{ $data['id'] }}: {{ $data['question'] }}</p>
                        @if($data['type'] === 'multiple')
                            <p>Answer:</p>
                            <ul>
                                @foreach($data['answer']['answers'] as $answer)
                                    <li>
                                        @foreach($answer as $value)
                                            {{ $value }},
                                        @endforeach
                                    </li>
                                @endforeach
                            </ul>
                            @if($data['answer']['other_field'])
                                <p>Other Field: {{ $data['answer']['other_field'] }}</p>
                            @endif
                        @else
                            <p>Answer: {{ $data['answer'] }}</p>
                        @endif
                    @endif
                @endforeach
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="section">
                <h2>Risk factors for AKI</h2>
                @foreach($questionData as $data)
                    @if($data['section_id'] === 4)
                        <p>Q{{ $data['id'] }}: {{ $data['question'] }}</p>
                        @if($data['type'] === 'multiple')
                            <p>Answer:</p>
                            <ul>
                                @foreach($data['answer']['answers'] as $answer)
                                    <li>
                                        @foreach($answer as $value)
                                            {{ $value }},
                                        @endforeach
                                    </li>
                                @endforeach
                            </ul>
                            @if($data['answer']['other_field'])
                                <p>Other Field: {{ $data['answer']['other_field'] }}</p>
                            @endif
                        @else
                            <p>Answer: {{ $data['answer'] }}</p>
                        @endif
                    @endif
                @endforeach
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="section">
                <h2>Assessment of the patient</h2>
                @foreach($questionData as $data)
                    @if($data['section_id'] === 5)
                        <p>Q{{ $data['id'] }}: {{ $data['question'] }}</p>
                        @if($data['type'] === 'multiple')
                            <p>Answer:</p>
                            <ul>
                                @foreach($data['answer']['answers'] as $answer)
                                    <li>
                                        @foreach($answer as $value)
                                            {{ $value }},
                                        @endforeach
                                    </li>
                                @endforeach
                            </ul>
                            @if($data['answer']['other_field'])
                                <p>Other Field: {{ $data['answer']['other_field'] }}</p>
                            @endif
                        @else
                            <p>Answer: {{ $data['answer'] }}</p>
                        @endif
                    @endif
                @endforeach
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="section">
                <h2>Laboratory and radiology results</h2>
                @foreach($questionData as $data)
                    @if($data['section_id'] === 6)
                        <p>Q{{ $data['id'] }}: {{ $data['question'] }}</p>
                        @if($data['type'] === 'multiple')
                            <p>Answer:</p>
                            <ul>
                                @foreach($data['answer']['answers'] as $answer)
                                    <li>
                                        @foreach($answer as $value)
                                            {{ $value }},
                                        @endforeach
                                    </li>
                                @endforeach
                            </ul>
                            @if($data['answer']['other_field'])
                                <p>Other Field: {{ $data['answer']['other_field'] }}</p>
                            @endif
                        @else
                            <p>Answer: {{ $data['answer'] }}</p>
                        @endif
                    @endif
                @endforeach
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="section">
                <h2>Medical decision</h2>
                @foreach($questionData as $data)
                    @if($data['section_id'] === 7)
                        <p>Q{{ $data['id'] }}: {{ $data['question'] }}</p>
                        @if($data['type'] === 'multiple')
                            <p>Answer:</p>
                            <ul>
                                @foreach($data['answer']['answers'] as $answer)
                                    <li>
                                        @foreach($answer as $value)
                                            {{ $value }},
                                        @endforeach
                                    </li>
                                @endforeach
                            </ul>
                            @if($data['answer']['other_field'])
                                <p>Other Field: {{ $data['answer']['other_field'] }}</p>
                            @endif
                        @else
                            <p>Answer: {{ $data['answer'] }}</p>
                        @endif
                    @endif
                @endforeach
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="section">
                <h2>Outcome</h2>
                @foreach($questionData as $data)
                    @if($data['section_id'] === 8)
                        <p>Q{{ $data['id'] }}: {{ $data['question'] }}</p>
                        @if($data['type'] === 'multiple')
                            <p>Answer:</p>
                            <ul>
                                @foreach($data['answer']['answers'] as $answer)
                                    <li>
                                        @foreach($answer as $value)
                                            {{ $value }},
                                        @endforeach
                                    </li>
                                @endforeach
                            </ul>
                            @if($data['answer']['other_field'])
                                <p>Other Field: {{ $data['answer']['other_field'] }}</p>
                            @endif
                        @else
                            <p>Answer: {{ $data['answer'] }}</p>
                        @endif
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    <!-- Additional Sections -->
    <!-- Charts and Graphs Section -->
    <div class="row">
        <div class="col-md-12">
            <div class="section">
                <h2>Charts and Graphs</h2>
                <!-- Add charts and graphs here -->
            </div>
        </div>
    </div>

    <!-- Medications Section -->
    <div class="row">
        <div class="col-md-12">
            <div class="section">
                <h2>Medications</h2>
                <!-- Add medications list here -->
            </div>
        </div>
    </div>

    <!-- Lab Results Section -->
    <div class="row">
        <div class="col-md-12">
            <div class="section">
                <h2>Lab Results</h2>
                <!-- Add lab results table here -->
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>Generated on: </p>
        <p>Page 1 of 1</p>
    </div>
</div>

<!-- Bootstrap JS (optional) -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>
