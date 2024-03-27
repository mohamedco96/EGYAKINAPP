<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Report</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        /* Add custom styles here */
        body {
            padding-top: 50px;
            padding-bottom: 50px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1 class="mb-4">Data Report</h1>

    <!-- Table to display the data -->
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Age</th>
                <!-- Add more table headers as needed -->
            </tr>
            </thead>
            <tbody>
            @foreach($patient as $row)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>test</td>
                    <td>287278</td>
                    <!-- Add more table cells with data as needed -->
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <!-- Pagination links if needed -->
    <div class="d-flex justify-content-center">
    </div>
</div>

<!-- Bootstrap JS (optional, if you need JavaScript functionality) -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
