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
            background-image: url('{{ asset('images/logo.png') }}'); /* Path to the image in the public directory */
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
                    @foreach($patient->answers as $answer)
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
                    @if(is_array($patient->answers) || is_object($patient->answers))
                        @foreach($patient->answers as $answer)
                            {{--****************************--}}
                            @if($answer['question_id'] === 92)
                                @php $pHmmhg_admission = $answer['answer']; @endphp
                            @endif
                            @if($answer['question_id'] === 116)
                                @php $pHmmhg_discharge = $answer['answer']; @endphp
                            @endif
                            {{--****************************--}}

                            {{--****************************--}}
                            @if($answer['question_id'] ===93 )
                                @php $HCO_admission = $answer['answer']; @endphp
                            @endif
                            @if($answer['question_id'] === 117)
                                @php $HCO_discharge = $answer['answer']; @endphp
                            @endif
                            {{--****************************--}}

                            {{--****************************--}}
                            @if($answer['question_id'] ===94 )
                                @php $pCO_admission = $answer['answer']; @endphp
                            @endif
                            @if($answer['question_id'] === 118)
                                @php $pCO_discharge = $answer['answer']; @endphp
                            @endif
                            {{--****************************--}}

                            {{--****************************--}}
                            @if($answer['question_id'] === 95)
                                @php $K_admission = $answer['answer']; @endphp
                            @endif
                            @if($answer['question_id'] ===119 )
                                @php $K_discharge = $answer['answer']; @endphp
                            @endif
                            {{--****************************--}}

                            {{--****************************--}}
                            @if($answer['question_id'] === 96)
                                @php $SGOT_admission = $answer['answer']; @endphp
                            @endif
                            @if($answer['question_id'] === 120)
                                @php $SGOT_discharge = $answer['answer']; @endphp
                            @endif
                            {{--****************************--}}

                            {{--****************************--}}
                            @if($answer['question_id'] ===97 )
                                @php $SGPT_admission = $answer['answer']; @endphp
                            @endif
                            @if($answer['question_id'] ===121 )
                                @php $SGPT_discharge = $answer['answer']; @endphp
                            @endif
                            {{--****************************--}}

                            {{--****************************--}}
                            @if($answer['question_id'] === 98)
                                @php $Albumin_admission = $answer['answer']; @endphp
                            @endif
                            @if($answer['question_id'] === 122)
                                @php $Albumin_discharge = $answer['answer']; @endphp
                            @endif
                            {{--****************************--}}

                            {{--****************************--}}
                            @if($answer['question_id'] === 99)
                                @php $HCV_admission = $answer['answer']; @endphp
                            @endif
                            {{--****************************--}}

                            {{--****************************--}}
                            @if($answer['question_id'] === 100)
                                @php $HBs_admission = $answer['answer']; @endphp
                            @endif
                            {{--****************************--}}

                            {{--****************************--}}
                            @if($answer['question_id'] === 101)
                                @php $HIV_admission = $answer['answer']; @endphp
                            @endif
                            {{--****************************--}}

                            {{--****************************--}}
                            @if($answer['question_id'] === 102)
                                @php $Hemoglobin_admission = $answer['answer']; @endphp
                            @endif
                            @if($answer['question_id'] === 126)
                                @php $Hemoglobin_discharge = $answer['answer']; @endphp
                            @endif
                            {{--****************************--}}

                            {{--****************************--}}
                            @if($answer['question_id'] === 103)
                                @php $WBCscount_admission = $answer['answer']; @endphp
                            @endif
                            @if($answer['question_id'] === 127)
                                @php $WBCscount_discharge = $answer['answer']; @endphp
                            @endif
                            {{--****************************--}}

                            {{--****************************--}}
                            @if($answer['question_id'] === 104)
                                @php $Platelets_admission = $answer['answer']; @endphp
                            @endif
                            @if($answer['question_id'] === 128)
                                @php $Platelets_discharge = $answer['answer']; @endphp
                            @endif
                            {{--****************************--}}

                            {{--****************************--}}
                            @if($answer['question_id'] === 105)
                                @php $Neutrophil_admission = $answer['answer']; @endphp
                            @endif
                            @if($answer['question_id'] === 129)
                                @php $Neutrophil_discharge = $answer['answer']; @endphp
                            @endif
                            {{--****************************--}}

                            {{--****************************--}}
                            @if($answer['question_id'] === 106)
                                @php $Lymphocytes_admission = $answer['answer']; @endphp
                            @endif
                            @if($answer['question_id'] === 130)
                                @php $Lymphocytes_discharge = $answer['answer']; @endphp
                            @endif
                            {{--****************************--}}

                            {{--****************************--}}
                            @if($answer['question_id'] === 71)
                                @php $Creatinine_admission = $answer['answer']; @endphp
                            @endif
                            @if($answer['question_id'] === 80)
                                @php $Creatinine_discharge = $answer['answer']; @endphp
                            @endif
                            {{--****************************--}}

                            {{--****************************--}}
                            @if($answer['question_id'] === 107)
                                @php $Urea_admission = $answer['answer']; @endphp
                            @endif
                            @if($answer['question_id'] === 131)
                                @php $Urea_discharge = $answer['answer']; @endphp
                            @endif
                            {{--****************************--}}

                            {{--****************************--}}
                            @if($answer['question_id'] === 108)
                                @php $BUN_admission = $answer['answer']; @endphp
                            @endif
                            @if($answer['question_id'] === 132)
                                @php $BUN_discharge = $answer['answer']; @endphp
                            @endif
                            {{--****************************--}}

                            {{--****************************--}}
                            @if($answer['question_id'] === 143)
                                @php $CRP_admission = $answer['answer']; @endphp
                            @endif
                            @if($answer['question_id'] === 144)
                                @php $CRP_discharge = $answer['answer']; @endphp
                            @endif
                            {{--****************************--}}

                            {{--****************************--}}
                            @if($answer['question_id'] === 109)
                                @php $Specific_admission = $answer['answer']; @endphp
                            @endif
                            @if($answer['question_id'] === 133)
                                @php $Specific_discharge = $answer['answer']; @endphp
                            @endif
                            {{--****************************--}}

                            {{--****************************--}}
                            @if($answer['question_id'] === 110)
                                @php $Clarity_admission = $answer['answer']; @endphp
                            @endif
                            @if($answer['question_id'] === 134)
                                @php $Clarity_discharge = $answer['answer']; @endphp
                            @endif
                            {{--****************************--}}

                            {{--****************************--}}
                            @if($answer['question_id'] === 111)
                                @php $Epithelial_admission = $answer['answer']; @endphp
                            @endif
                            @if($answer['question_id'] === 135)
                                @php $Epithelial_discharge = $answer['answer']; @endphp
                            @endif
                            {{--****************************--}}

                            {{--****************************--}}
                            @if($answer['question_id'] === 112)
                                @php $Crystals_admission = $answer['answer']; @endphp
                            @endif
                            @if($answer['question_id'] === 136)
                                @php $Crystals_discharge = $answer['answer']; @endphp
                            @endif
                            {{--****************************--}}

                            {{--****************************--}}
                            @if($answer['question_id'] === 113)
                                @php $Casts_admission = $answer['answer']; @endphp
                            @endif
                            @if($answer['question_id'] === 137)
                                @php $Casts_discharge = $answer['answer']; @endphp
                            @endif
                            {{--****************************--}}

                            {{--****************************--}}
                            @if($answer['question_id'] === 114)
                                @php $WBCs_admission = $answer['answer']; @endphp
                            @endif
                            @if($answer['question_id'] === 138)
                                @php $WBCs_discharge = $answer['answer']; @endphp
                            @endif
                            {{--****************************--}}

                            {{--****************************--}}
                            @if($answer['question_id'] === 115)
                                @php $RBCs_admission = $answer['answer']; @endphp
                            @endif
                            @if($answer['question_id'] === 139)
                                @php $RBCs_discharge = $answer['answer']; @endphp
                            @endif
                            {{--****************************--}}

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
                        <td class="center-text"><strong>{{ $pHmmhg_admission ?? '-' }}</strong></td>
                        <td class="center-text"><strong>{{ $pHmmhg_discharge ?? '-' }}</strong></td>
                    </tr>
                    <tr>
                        <td>HCO3 /mmhg</td>
                        <td class="center-text"><strong>{{ $HCO_admission ?? '-' }}</strong></td>
                        <td class="center-text"><strong>{{ $HCO_discharge ?? '-' }}</strong></td>
                    </tr>
                    <tr>
                        <td>pCO2 /mmhg</td>
                        <td class="center-text"><strong>{{ $pCO_admission ?? '-' }}</strong></td>
                        <td class="center-text"><strong>{{ $pCO_discharge ?? '-' }}</strong></td>
                    </tr>
                    <tr>
                        <td>K mg/dl</td>
                        <td class="center-text"><strong>{{ $K_admission ?? '-' }}</strong></td>
                        <td class="center-text"><strong>{{ $K_discharge ?? '-' }}</strong></td>
                    </tr>
                    <tr>
                        <td>SGOT u/l</td>
                        <td class="center-text"><strong>{{ $SGOT_admission ?? '-' }}</strong></td>
                        <td class="center-text"><strong>{{ $SGOT_discharge ?? '-' }}</strong></td>
                    </tr>
                    <tr>
                        <td>SGPT u/l</td>
                        <td class="center-text"><strong>{{ $SGPT_admission ?? '-' }}</strong></td>
                        <td class="center-text"><strong>{{ $SGPT_discharge ?? '-' }}</strong></td>
                    </tr>
                    <tr>
                        <td>Albumin gm/dl</td>
                        <td class="center-text"><strong>{{ $Albumin_admission ?? '-' }}</strong></td>
                        <td class="center-text"><strong>{{ $Albumin_discharge ?? '-' }}</strong></td>
                    </tr>
                    <tr>
                        <td>HCV Ab</td>
                        <td class="center-text"><strong>{{ $HCV_admission ?? '-' }}</strong></td>
                        <td class="center-text"><strong></strong></td>
                    </tr>
                    <tr>
                        <td>HBs Ag</td>
                        <td class="center-text"><strong>{{ $HBs_admission ?? '-' }}</strong></td>
                        <td class="center-text"><strong></strong></td>
                    </tr>
                    <tr>
                        <td>HIV Ab</td>
                        <td class="center-text"><strong>{{ $HIV_admission ?? '-' }}</strong></td>
                        <td class="center-text"><strong></strong></td>
                    </tr>
                    <tr>
                        <td>Hemoglobin gm/dl</td>
                        <td class="center-text"><strong>{{ $Hemoglobin_admission ?? '-' }}</strong></td>
                        <td class="center-text"><strong>{{ $Hemoglobin_discharge ?? '-' }}</strong></td>
                    </tr>
                    <tr>
                        <td>WBCs count</td>
                        <td class="center-text"><strong>{{ $WBCscount_admission ?? '-' }}</strong></td>
                        <td class="center-text"><strong>{{ $WBCscount_discharge ?? '-' }}</strong></td>
                    </tr>
                    <tr>
                        <td>Platelets count</td>
                        <td class="center-text"><strong>{{ $Platelets_admission ?? '-' }}</strong></td>
                        <td class="center-text"><strong>{{ $Platelets_discharge ?? '-' }}</strong></td>
                    </tr>
                    <tr>
                        <td>Neutrophil count</td>
                        <td class="center-text"><strong>{{ $Neutrophil_admission ?? '-' }}</strong></td>
                        <td class="center-text"><strong>{{ $Neutrophil_discharge ?? '-' }}</strong></td>
                    </tr>
                    <tr>
                        <td>Lymphocytes count</td>
                        <td class="center-text"><strong>{{ $Lymphocytes_admission ?? '-' }}</strong></td>
                        <td class="center-text"><strong>{{ $Lymphocytes_discharge ?? '-' }}</strong></td>
                    </tr>
                    <tr>
                        <td>Creatinine (mg/dl)</td>
                        <td class="center-text"><strong>{{ $Creatinine_admission ?? '-' }}</strong></td>
                        <td class="center-text"><strong>{{ $Creatinine_discharge ?? '-' }}</strong></td>
                    </tr>
                    <tr>
                        <td>Urea mg/dl</td>
                        <td class="center-text"><strong>{{ $Urea_admission ?? '-' }}</strong></td>
                        <td class="center-text"><strong>{{ $Urea_discharge ?? '-' }}</strong></td>
                    </tr>
                    <tr>
                        <td>BUN mg/dl</td>
                        <td class="center-text"><strong>{{ $BUN_admission ?? '-' }}</strong></td>
                        <td class="center-text"><strong>{{ $BUN_discharge ?? '-' }}</strong></td>
                    </tr>
                    <tr>
                        <td>CRP mg/l</td>
                        <td class="center-text"><strong>{{ $CRP_admission ?? '-' }}</strong></td>
                        <td class="center-text"><strong>{{ $CRP_discharge ?? '-' }}</strong></td>
                    </tr>
                    <tr>
                        <td>Specific gravity (Urine)</td>
                        <td class="center-text"><strong>{{ $Specific_admission ?? '-' }}</strong></td>
                        <td class="center-text"><strong>{{ $Specific_discharge ?? '-' }}</strong></td>
                    </tr>
                    <tr>
                        <td>Clarity (Urine)</td>
                        <td class="center-text"><strong>{{ $Clarity_admission ?? '-' }}</strong></td>
                        <td class="center-text"><strong>{{ $Clarity_discharge ?? '-' }}</strong></td>
                    </tr>
                    <tr>
                        <td>Epithelial cells (Urine)</td>
                        <td class="center-text"><strong>{{ $Epithelial_admission ?? '-' }}</strong></td>
                        <td class="center-text"><strong>{{ $Epithelial_discharge ?? '-' }}</strong></td>
                    </tr>
                    <tr>
                        <td>Crystals types (Urine)</td>
                        <td class="center-text"><strong>{{ $Crystals_admission ?? '-' }}</strong></td>
                        <td class="center-text"><strong>{{ $Crystals_discharge ?? '-' }}</strong></td>
                    </tr>
                    <tr>
                        <td>Casts (Urine)</td>
                        <td class="center-text"><strong>{{ $Casts_admission ?? '-' }}</strong></td>
                        <td class="center-text"><strong>{{ $Casts_discharge ?? '-' }}</strong></td>
                    </tr>
                    <tr>
                        <td>WBCs (Urine)</td>
                        <td class="center-text"><strong>{{ $WBCs_admission ?? '-' }}</strong></td>
                        <td class="center-text"><strong>{{ $WBCs_discharge ?? '-' }}</strong></td>
                    </tr>
                    <tr>
                        <td>RBCs (Urine)</td>
                        <td class="center-text"><strong>{{ $RBCs_admission ?? '-' }}</strong></td>
                        <td class="center-text"><strong>{{ $RBCs_discharge ?? '-' }}</strong></td>
                    </tr>
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
