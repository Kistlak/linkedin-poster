<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>LinkedIn Post Success</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .linkedin-logo {
            width: 40px;
            height: 40px;
        }

        .success-box {
            max-width: 600px;
            margin: 80px auto;
        }

        .back-to-btn {
            background: #0077b7;
            border-color: #0077b7;
        }
    </style>
</head>
<body>
<div class="container success-box">
    <div class="card shadow text-center">
        <div class="card-body p-5">
            <img src="{{ asset('images/linkedin logo 1.png') }}" alt="LinkedIn Logo" class="linkedin-logo mb-3">
            <h3 class="mb-3">Successfully Shared!</h3>
            <p class="text-muted mb-4">Your event has been posted to LinkedIn.</p>
            <a href="{{ route('home.index') }}" class="btn btn-primary back-to-btn">Back to Events</a>
        </div>
    </div>
</div>
</body>
</html>
