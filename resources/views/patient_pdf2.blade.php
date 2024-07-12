<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Report Summary</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid black;
            text-align: left;
            padding: 8px;
        }
        th {
            background-color: #f2f2f2;
        }


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
                    @foreach($questionData as $data)
                        @php
                            // Debugging the values to ensure they are correct
                        //dd($data);
                        @endphp
                        @if($data['section_id'] === 2)
                            @php $displaySection = false; @endphp
                            @if(!is_null($data['answer']))
                                @php $displaySection = true; @endphp                                <p>
                                    Q{{ $data['id'] }}: {{ $data['question'] }}</p>
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
                            @php $displaySection = false; @endphp
                            @if(!is_null($data['answer']))
                                @php $displaySection = true; @endphp                            <p>Q{{ $data['id'] }}
                                    : {{ $data['question'] }}</p>
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
                                @php $displaySection = true; @endphp                                <p>
                                    Q{{ $data['id'] }}: {{ $data['question'] }}</p>
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
                                @php $displaySection = true; @endphp                                <p>
                                    Q{{ $data['id'] }}: {{ $data['question'] }}</p>
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
                    @foreach($questionData as $data)
                        @php
                            // Debugging the values to ensure they are correct
                        //dd($data);
                        @endphp
                        @if($data['section_id'] === 8)
                            @php $displaySection = false; @endphp
                            @if(!is_null($data['answer']))
                                @php $displaySection = true; @endphp
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
                    @if(!$displaySection)
                        <p>No information available.</p>
                    @endif
                @endif
            </div>
        </div>
    </div>

    <table>
        @if(is_array($patient->answers) || is_object($patient->answers))
            @foreach($patient->answers as $answer)
                @if($answer['question_id'] === 92)
                    @php $pHmmhg_admission = $answer['answer']; @endphp
                @endif
                @if($answer['question_id'] === 116)
                    @php $pHmmhg_discharge = $answer['answer']; @endphp
                @endif
            @endforeach
        @endif
        <thead>
        <tr>
            <th>Laboratory Parameters</th>
            <th>On admission</th>
            <th>On discharge</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>pH /mmhg</td>
            <td>{{ $pHmmhg_admission ?? '0' }}</td>
            <td>{{ $pHmmhg_discharge ?? '0' }}</td>
        </tr>
        <tr>
            <td>HCO3 /mmhg</td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td>pCO2 /mmhg</td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td>K mg/dl</td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td>SGOT u/l</td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td>SGPT u/l</td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td>Albumin mg/dl</td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td>HCV Ab</td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td>HBs Ag</td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td>HIV Ab</td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td>Hemoglobin gm/dl</td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td>WBCs count</td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td>Platelets count</td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td>Neutrophil count</td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td>Lymphocytes count</td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td>Creatinine (mg/dl)</td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td>Urea mg/dl</td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td>BUN mg/dl</td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td>CRP mg/l</td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td>Specific gravity (Urine)</td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td>Clarity (Urine)</td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td>Epithelial cells (Urine)</td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td>Crystals types (Urine)</td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td>Casts (Urine)</td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td>WBCs (Urine)</td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td>RBCs (Urine)</td>
            <td></td>
            <td></td>
        </tr>
        </tbody>
    </table>
    <!-- Footer -->
    <div class="footer">
        <p>&copy; 2024 Patient Report Summary. All rights reserved.</p>
    </div>
</div>
</body>

</html>
