<!-- search.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Patients and Doses</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<h1>Search Patients and Doses</h1>

<input type="text" id="dose" placeholder="Search by Dose">
<input type="text" id="patient" placeholder="Search by Patient">

<div id="results">
    <h2>Results:</h2>

    <h3>Patients:</h3>
    <ul id="patientResults"></ul>

    <h3>Doses:</h3>
    <ul id="doseResults"></ul>
</div>

<script>
    $(document).ready(function() {
        function fetchResults() {
            const dose = $('#dose').val();
            const patient = $('#patient').val();

            $.ajax({
                url: '{{ route('realTimeSearch') }}',
                method: 'GET',
                data: {
                    dose: dose,
                    patient: patient
                },
                success: function(response) {
                    $('#patientResults').empty();
                    $('#doseResults').empty();

                    if (response.value) {
                        response.data.patients.forEach(function(patient) {
                            $('#patientResults').append(`<li>${patient.name} - ${patient.hospital}</li>`);
                        });

                        response.data.doses.forEach(function(dose) {
                            $('#doseResults').append(`<li>${dose.title} - ${dose.dose}</li>`);
                        });
                    }
                }
            });
        }

        $('#dose, #patient').on('input', function() {
            fetchResults();
        });
    });
</script>
</body>
</html>
