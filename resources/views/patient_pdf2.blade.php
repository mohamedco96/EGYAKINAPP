<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Report Summary</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .strong{
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
                <p>Report is generted for Dr.<strong>{{ $patient->doctor->name }}</strong></p>
            </div>
        </div>
    </div>


    <!-- Patient Information Section -->
    <div class="row">
        <div class="col-md-12">
            <div class="section">
                <h2>Patient Information</h2>
                @php
                    // Debugging the value of $patient->answers
                    dd($patient->answers);
                @endphp

                @if(is_string($patient->answers))
                    @php
                        $patient->answers = json_decode($patient->answers, true);
                    @endphp
                @endif

                @if(is_array($patient->answers) || is_object($patient->answers))
                    @foreach($patient->answers as $answer)
                        @if($answer->question_id === 1)
                            <p>Patient Name: <strong>{{ $answer->answer }}</strong></p>
                        @endif
                        @if($answer->question_id === 2)
                            <p>Hospital: <strong>{{ $answer->answer }}</strong></p>
                        @endif
                        @if($answer->question_id === 7)
                            <p>Age: <strong>{{ $answer->answer }}</strong></p>
                        @endif
                        @if($answer->question_id === 8)
                            <p>Gender: <strong>{{ $answer->answer }}</strong></p>
                        @endif
                    @endforeach
                @endif

            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>Thank you for using our application!</p>
        <p>EGYAKIN Scientific Team.</p>
    </div>
</div>

<!-- Bootstrap JS (optional) -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>
