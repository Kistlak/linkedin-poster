<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>DMCC - LinkedIn Share Preview</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .linkedin-logo {
            width: 30px;
            height: 30px;
        }

        .share-btn {
            background: #0077b7;
            border-color: #0077b7;
        }

        .linkedin-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 1rem;
        }

        textarea.form-control {
            resize: vertical;
        }

        .event-image {
            max-width: 50%;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">

            <div class="card shadow">
                <div class="card-body">
                    <div class="linkedin-header">
                        <img src="{{ asset('images/linkedin logo 1.png') }}" alt="LinkedIn Logo" class="linkedin-logo">
                        <h5 class="mb-0">LinkedIn Post Preview</h5>
                    </div>

                    <img src="{{ $linkedInPostImgUrl ?? asset('images/event-img-1.jpg') }}" alt="Event Image" class="event-image mb-3 rounded">

                    <form method="POST" action="{{ route('linkedin.share', $event->id) }}">
                        @csrf

                        <div class="mb-3">
                            <label for="linkedinContent" class="form-label">Post Content</label>
                            <small>
                                <p class="text-muted">You can edit the content before sharing it on LinkedIn:</p>
                            </small>
                            <textarea name="content" id="linkedinContent" class="form-control" rows="5">{{ $event->title_en }}&#10;{{ $event->desc_en }}</textarea>
                        </div>

                        <button type="submit" class="share-btn btn btn-primary float-end">Share on LinkedIn</button>

                        <a href="{{ route('home.index') }}" class="share-btn btn btn-primary me-3 float-end">Back to Events</a>

                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
</body>
</html>
